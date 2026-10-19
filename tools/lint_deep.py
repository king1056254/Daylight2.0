#!/usr/bin/env python3
"""
Extra best-effort checks (still NOT a PHP parser):
 1. Statement termination heuristics in class files (lines that probably lack a trailing ';').
 2. Every variable a view documents in its @var docblock is passed by each call site that renders it.
Usage: python3 tools/lint_deep.py
"""
import re, sys, pathlib
ROOT = pathlib.Path(__file__).resolve().parent.parent
issues = []

# ---------- 1. missing semicolons (class files only) ----------
CONT_END = tuple(['{', '}', ';', ',', '(', '[', '.', ':', '?', '=>', '&&', '||', '+', '-', '*', '/', '=', '|', '&', '<?php', '*/', '/**', '?:', '??'])
for f in sorted((ROOT / 'app').rglob('*.php')) + sorted((ROOT / 'tests').glob('*.php')) + sorted((ROOT / 'bin').glob('*.php')) + sorted((ROOT / 'public').glob('*.php')):
    if '/Views/' in str(f): continue
    lines = f.read_text().split('\n')
    in_doc = False
    prev_open = 0
    for i, raw in enumerate(lines, 1):
        s = raw.strip()
        if s.startswith('/*'): in_doc = not s.endswith('*/') or in_doc and False
        if in_doc:
            if s.endswith('*/'): in_doc = False
            continue
        if not s or s.startswith(('//', '#', '*', '<?php', 'declare(')): continue
        s = re.sub(r'\s+//.*$', '', s)
        if s.endswith(CONT_END): continue
        if re.match(r'^((abstract|final)\s+)?class\s+\w+', s): continue      # class header; "{" is on the next line
        if re.match(r'^(public|private|protected)?\s*(static\s+)?function\b.*\)\s*(:\s*[\w|?\\]+)?$', s): continue
        if re.match(r'^(\)|\]|\)\s*:\s*[\w|?\\]+|\)\s*\{)$', s): continue
        prev = next((l.strip() for l in reversed(lines[:i - 1]) if l.strip() and not l.strip().startswith(('//', '*', '/*'))), '')
        nxt = next((l.strip() for l in lines[i:] if l.strip()), '')
        # part of a multi-line expression / argument list / constructor promotion
        if prev.endswith(('.', '?', ':', ',', '(', '[', '=', '=>', '&&', '||', '+', '-', '??')) or nxt.startswith(('{', '->', '?', ':', '.', '&&', '||', ')', ']', '+', '-', '?:', '??')):
            continue
        issues.append(f'{f.relative_to(ROOT)}:{i}: line may be missing a terminator: {s[:90]}')

# ---------- 2. view variables vs call sites ----------
def match_bracket(src, start, open_c='[', close_c=']'):
    depth, i, q = 0, start, ''
    while i < len(src):
        c = src[i]
        if q:
            if c == '\\': i += 2; continue
            if c == q: q = ''
        elif c in '\'"': q = c
        elif c == open_c: depth += 1
        elif c == close_c:
            depth -= 1
            if depth == 0: return i
        i += 1
    return -1

def top_level_keys(arr_src):
    keys, depth, i, q = [], 0, 0, ''
    body = arr_src[1:-1]
    while i < len(body):
        c = body[i]
        if q:
            if c == '\\': i += 2; continue
            if c == q: q = ''
        elif c in '\'"':
            if depth == 0:
                m = re.match(r"""(['"])(\w+)\1\s*=>""", body[i:])
                if m: keys.append(m.group(2)); i += len(m.group(0)); continue
            q = c
        elif c in '([{': depth += 1
        elif c in ')]}': depth -= 1
        i += 1
    return set(keys)

views = {}
for v in (ROOT / 'app/Views').rglob('*.php'):
    name = str(v.relative_to(ROOT / 'app/Views'))[:-4]
    s = v.read_text()
    doc = re.search(r'/\*\*(.*?)\*/', s, re.S)
    if not doc: continue
    names = set(re.findall(r'\$(\w+)', doc.group(1)))
    nullable = set(re.findall(r'@var\s+[^\s$]*null[^\s$]*\s+\$(\w+)', doc.group(1)))
    # variables the view itself assigns before use (defaults) are not required from callers
    assigned = set(re.findall(r'^\s*\$(\w+)\s*=\s*\$\1\s*\?\?', s, re.M))
    body = re.sub(r'/\*\*.*?\*/', '', s, count=1, flags=re.S)
    for var in names:
        uses = re.findall(r'\$' + var + r'\b', body)
        guarded = re.findall(r'(?:empty|isset)\(\$' + var + r'\b|\$' + var + r'\s*\?\?', body)
        if uses and len(uses) == len(guarded):
            assigned.add(var)
    views[name] = (names - assigned - nullable - {'content'}, s)

EXTRA = {  # variables injected for every view of a kind
    'pages/': {'meta'}, 'auth/': {'meta'}, 'setup/': {'meta'}, 'portal/': set(), 'admin/': set(), 'layout/': set(), 'errors/': set(), 'partials/': set(),
}
call_re = re.compile(r"""(?:self::page|self::app|View::render|View::partial|partial)\(\s*(?:'(?:portal|admin)',\s*)?'([\w/]+)'\s*,\s*\[""")
seen = {}
var_calls = set()
var_re = re.compile(r"""(?:self::page|self::app|View::render)\(\s*(?:'(?:portal|admin)',\s*)?'([\w/]+)'\s*,\s*\$\w+""")
for f in list((ROOT / 'app').rglob('*.php')):
    s = f.read_text()
    for m in var_re.finditer(s):
        var_calls.add(m.group(1))
    for m in call_re.finditer(s):
        view = m.group(1)
        if view not in views: continue
        start = m.end() - 1
        end = match_bracket(s, start)
        if end < 0: continue
        keys = top_level_keys(s[start:end + 1])
        line = s.count('\n', 0, m.start()) + 1
        seen.setdefault(view, []).append((f.relative_to(ROOT), line, keys))

for view, (need, src) in sorted(views.items()):
    if view.startswith('layout/'): continue
    calls = seen.get(view, [])
    if not calls:
        if view in var_calls:
            print(f'note: {view} is rendered with a data array built in a variable; check its keys by hand')
        elif not view.startswith(('errors/', 'layout/')):
            issues.append(f'view {view}: no render call found by this checker (check manually)')
        continue
    for path, line, keys in calls:
        extra = set()
        for pre, ex in EXTRA.items():
            if view.startswith(pre): extra |= ex
        missing = need - keys - extra
        if missing:
            issues.append(f'{path}:{line}: view {view} documents {sorted(missing)} but this call does not pass them')

print(f'Deep checks done: {len(views)} views with docblocks, {sum(len(v) for v in seen.values())} call sites matched.')
if issues:
    print('POSSIBLE PROBLEMS (review each; some may be false positives):')
    for i in issues: print(' -', i)
    sys.exit(1)
print('No problems found by the deep checks (still NOT a PHP syntax check).')
