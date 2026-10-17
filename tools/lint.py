#!/usr/bin/env python3
"""
Best-effort static checks for the Daylight PHP code when `php -l` is unavailable.
NOT a substitute for `php -l` / PHPStan / the test suite. It catches: unbalanced brackets, unbalanced
alternative control syntax in templates, duplicate method names, missing classes, calls to undefined
static methods on project classes, and unknown helper functions used in templates.
Usage: python3 tools/lint.py
"""
import re, sys, pathlib

ROOT = pathlib.Path(__file__).resolve().parent.parent
problems = []

def strip_php(src):
    """Remove comments and string/char literals from PHP code, keeping structure characters."""
    out, i, n = [], 0, len(src)
    while i < n:
        c = src[i]
        two = src[i:i+2]
        if two == '//' or c == '#' and src[i:i+2] != '#[':
            while i < n and src[i] != '\n': i += 1
            continue
        if two == '/*':
            j = src.find('*/', i + 2); i = n if j < 0 else j + 2; continue
        if c in ('"', "'"):
            q = c; i += 1
            while i < n:
                if src[i] == '\\': i += 2; continue
                if src[i] == q: break
                i += 1
            i += 1; out.append('""'); continue
        out.append(c); i += 1
    return ''.join(out)

def balanced(code, label):
    pairs = {')': '(', ']': '[', '}': '{'}
    st = []
    for ch in code:
        if ch in '([{': st.append(ch)
        elif ch in ')]}':
            if not st or st.pop() != pairs[ch]:
                problems.append(f'{label}: unbalanced {ch}'); return
    if st: problems.append(f'{label}: unclosed {st[-1]}')

def php_segments(src):
    """Yield PHP code segments from a template (between <?php/<?= and ?>)."""
    for m in re.finditer(r'<\?(?:php|=)(.*?)(?:\?>|\Z)', src, re.S):
        yield m.group(1)

classes = {}   # short name -> (path, set(methods), namespace)
for f in sorted((ROOT / 'app').rglob('*.php')):
    s = f.read_text()
    m = re.search(r'^namespace\s+([\w\\]+);', s, re.M)
    cm = re.search(r'^(?:abstract\s+|final\s+)?class\s+(\w+)', s, re.M)
    if cm:
        meths = re.findall(r'function\s+(\w+)\s*\(', s)
        classes[cm.group(1)] = (f, set(meths), m.group(1) if m else '')
        dup = {x for x in meths if meths.count(x) > 1}
        for d in dup: problems.append(f'{f.relative_to(ROOT)}: duplicate method {d}()')

helpers = set(re.findall(r'^function\s+(\w+)\s*\(', (ROOT / 'app/Core/helpers.php').read_text(), re.M))

files = [f for f in sorted(ROOT.rglob('*.php')) if '.git' not in f.parts]
for f in files:
    rel = str(f.relative_to(ROOT))
    s = f.read_text()
    is_template = '/Views/' in '/' + rel or rel.startswith('app/Views')
    if is_template:
        segs = list(php_segments(s))
        braces = ''
        for seg in segs:
            code = strip_php(seg)
            for pair in ('()', '[]'):
                if code.count(pair[0]) != code.count(pair[1]):
                    problems.append(f'{rel}: unbalanced {pair} in segment: {seg.strip()[:60]!r}')
            braces += code
        if braces.count('{') != braces.count('}'):
            problems.append(f'{rel}: unbalanced braces in template')
        code_all = '\n'.join(strip_php(x) for x in segs)
        opens = len(re.findall(r'\b(?:if|foreach|for|while|switch)\s*\([^\n]*\)\s*:\s*(?:\?>|$|\n)', code_all)) + \
                len(re.findall(r'\b(?:if|foreach|for|while|switch)\s*\(.*\)\s*:', code_all)) * 0
        # count by regex over raw text for accuracy
        raw_open = len(re.findall(r'\b(if|foreach|for|while|switch)\s*\((?:[^()]|\((?:[^()]|\([^()]*\))*\))*\)\s*:', '\n'.join(segs)))
        raw_close = len(re.findall(r'\bend(?:if|foreach|for|while|switch)\s*;', '\n'.join(segs)))
        if raw_open != raw_close:
            problems.append(f'{rel}: alternative-syntax blocks open={raw_open} close={raw_close}')
        for seg in segs:
            for m in re.finditer(r'(?<![\w>:$\\])([a-z_][a-z0-9_]*)\s*\(', strip_php(seg)):
                name = m.group(1)
                if name in helpers or name in {'if','foreach','for','while','switch','elseif','array','isset','empty','count','implode','in_array','array_slice','array_filter','array_sum','array_map','rtrim','number_format','max','min','strncmp','strlen','preg_match','gmdate','mb_strlen','mb_substr','match','function','fn','list','static','return','echo','rawurlencode','ceil','str_replace','ucfirst','array_column','array_values','array_unique','array_fill','is_array','is_string','strtotime','date','rawurldecode','str_pad','array_keys','array_merge','substr','strtolower','array_key_exists','trim','explode','ucwords','unset','exit','use'}:
                    continue
                problems.append(f'{rel}: unknown function {name}()')
    else:
        code = strip_php(s)
        balanced(code, rel)
        if not s.startswith('<?php'):
            problems.append(f'{rel}: does not start with <?php')
        # static calls to project classes
        own = re.search(r'^(?:abstract\s+|final\s+)?class\s+(\w+)(?:\s+extends\s+(\w+))?', s, re.M)
        for m in re.finditer(r'\b([A-Z]\w+)::(\w+)\(', code):
            cls, meth = m.group(1), m.group(2)
            if cls in ('self', 'static', 'parent'): continue
            if cls in classes and meth not in classes[cls][1] and meth not in ('class',):
                problems.append(f'{rel}: {cls}::{meth}() is not defined')
        for m in re.finditer(r'\b(self|static)::(\w+)\(', code):
            meth = m.group(2)
            if own:
                cls = own.group(1); parent = own.group(2)
                known = set(classes.get(cls, (None, set()))[1])
                if parent and parent in classes: known |= classes[parent][1]
                if meth not in known:
                    problems.append(f'{rel}: self::{meth}() is not defined in {cls}' + (f' or {parent}' if parent else ''))
        # `use` imports must exist
        for m in re.finditer(r'^use\s+App\\([\w\\]+)(?:\s+as\s+\w+)?;', s, re.M):
            p = ROOT / 'app' / (m.group(1).replace('\\', '/') + '.php')
            if not p.exists(): problems.append(f'{rel}: imports missing class App\\{m.group(1)}')
        for m in re.finditer(r'^use\s+App\\([\w\\]+)\\\{([^}]+)\};', s, re.M):
            pass

# routes -> controller methods exist
routes = (ROOT / 'app/routes.php').read_text()
for m in re.finditer(r'\[(\w+)::class,\s*\'(\w+)\'\]', routes):
    cls, meth = m.group(1), m.group(2)
    if cls not in classes: problems.append(f'routes.php: unknown controller {cls}')
    elif meth not in classes[cls][1]: problems.append(f'routes.php: {cls}::{meth} is not defined')

# views referenced exist
for f in files:
    if '/Views/' in str(f): 
        pass
    s = f.read_text()
    for m in re.finditer(r"(?:partial|View::render|self::page|self::app)\(\s*'((?:partials|pages|auth|portal|admin|errors|layout|setup)/[\w/]+)'", s):
        p = ROOT / 'app/Views' / (m.group(1) + '.php')
        if not p.exists(): problems.append(f'{f.relative_to(ROOT)}: missing view {m.group(1)}')

# resources.php hooks exist
res = (ROOT / 'config/resources.php').read_text()
hooks_src = (ROOT / 'app/Admin/Hooks.php').read_text()
for kind in ("'before'", "'after'", "'after_delete'"):
    for m in re.finditer(kind + r"\s*=>\s*\[([^\]]*)\]", res):
        for h in re.findall(r"'(\w+)'", m.group(1)):
            if f"'{h}'" not in hooks_src and f'function {h}(' not in hooks_src:
                problems.append(f'config/resources.php: hook {h} is not implemented in Hooks.php')

print(f'Checked {len(files)} PHP files, {len(classes)} classes.')
if problems:
    print('PROBLEMS FOUND:')
    for p in problems: print(' -', p)
    sys.exit(1)
print('No problems found by this checker (this is NOT a PHP syntax check).')
