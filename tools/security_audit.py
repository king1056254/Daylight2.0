#!/usr/bin/env python3
"""
Static security audit (attacker's checklist). Lists places that need a human to look. NOT a penetration test and not a PHP parser.
 1. Template output that is not escaped.
 2. Dangerous PHP functions.
 3. Direct superglobal use outside the request/config layer.
 4. SQL built with variable interpolation or concatenation.
 5. Every POST route has CSRF protection (or is documented as exempt).
 6. Every admin/portal route has an authorisation guard.
 7. Hrefs fed by database values.
 8. Browser JS: innerHTML / eval / document.write.
Usage: python3 tools/security_audit.py
"""
import re, sys, pathlib
ROOT = pathlib.Path(__file__).resolve().parent.parent
findings, notes = [], []

SAFE_ECHO = re.compile(r"^(e\(|url\(|asset\(|partial\(|icon\(|status_pill\(|fmt_date\(|money\(|csrf_field\(|Cells::render\(|\(int\)|count\(|\d)")
# variables that only ever hold constants or already-escaped markup (reviewed by hand)
TRUSTED_VARS = {'$content': 'layout body, already rendered by a template', '$successHtml': 'literal strings from controllers', '$dot': 'constant CSS class chosen from a status'}

def split_top(expr, ch):
    """Split on a single character at nesting depth 0, outside strings."""
    parts, depth, q, cur = [], 0, '', ''
    i = 0
    while i < len(expr):
        c = expr[i]
        if q:
            cur += c
            if c == '\\': cur += expr[i + 1]; i += 1
            elif c == q: q = ''
        elif c in '\'"': q = c; cur += c
        elif c in '([{': depth += 1; cur += c
        elif c in ')]}': depth -= 1; cur += c
        elif c == ch and depth == 0: parts.append(cur); cur = ''
        else: cur += c
        i += 1
    parts.append(cur)
    return parts

def piece_safe(p):
    p = p.strip()
    if re.fullmatch(r"'[^']*'|\"[^\"$]*\"", p) or p == '': return True
    if SAFE_ECHO.match(p): return True
    if p in TRUSTED_VARS: return True
    return False

def concat_safe(expr):
    return all(piece_safe(x) for x in split_top(expr, '.'))

def expr_safe(expr):
    expr = expr.strip()
    if concat_safe(expr): return True
    q = split_top(expr, '?')
    if len(q) == 2:
        branches = split_top(q[1], ':')
        if len(branches) == 2 and concat_safe(branches[0]) and concat_safe(branches[1]): return True
    return False

for f in sorted((ROOT / 'app/Views').rglob('*.php')):
    s = f.read_text()
    for m in re.finditer(r'<\?=\s*(.*?)\s*\?>', s, re.S):
        expr = m.group(1)
        if expr_safe(expr): continue
        line = s.count('\n', 0, m.start()) + 1
        findings.append(f'{f.relative_to(ROOT)}:{line}: unescaped echo: <?= {expr[:80]} ?>')
notes.append('trusted template variables: ' + ', '.join(f'{k} ({v})' for k, v in TRUSTED_VARS.items()))

# ---- 2. dangerous functions ----
DANGER = r'(?<![>:\w])(?<!function )\b(eval|exec|shell_exec|system|passthru|popen|proc_open|unserialize|assert|create_function|extract\s*\(\s*\$_(GET|POST|REQUEST)|md5|sha1|call_user_func(?:_array)?|preg_replace\s*\(\s*[\'"][^\'"]*/e)\s*\('
for f in sorted(ROOT.rglob('*.php')):
    if '.git' in f.parts or 'tests' in f.parts: continue
    s = re.sub(r'//.*|/\*.*?\*/', '', f.read_text(), flags=re.S)
    for m in re.finditer(DANGER, s):
        findings.append(f'{f.relative_to(ROOT)}: dangerous function {m.group(1)}()')
    for m in re.finditer(r'\b(include|require)(_once)?\s*\(?\s*\$(?!__file)\w+', s):
        if 'View.php' in str(f) or 'Config.php' in str(f) or str(f).endswith('app/bootstrap.php'): continue  # autoloader / view loader build paths from fixed class and view names
        findings.append(f'{f.relative_to(ROOT)}: dynamic include/require')

# ---- 3. superglobals ----
ALLOWED_SG = {'app/Core/Request.php', 'app/Core/Config.php', 'app/Core/Security.php', 'app/Core/Session.php', 'app/Core/Csrf.php', 'app/Core/Auth.php',
              'app/Core/Errors.php', 'app/Core/helpers.php', 'public/index.php', 'app/Controllers/Controller.php'}
for f in sorted((ROOT / 'app').rglob('*.php')):
    rel = str(f.relative_to(ROOT))
    s = f.read_text()
    for m in re.finditer(r'\$_(GET|POST|REQUEST|COOKIE|FILES|SERVER)\b', s):
        if rel not in ALLOWED_SG:
            findings.append(f'{rel}: direct ${m.group(0)[1:]} use (go through Request)')
    if '/Views/' in rel:
        for m in re.finditer(r'\$_SESSION\b', s): findings.append(f'{rel}: $_SESSION used in a template')

# ---- 4. SQL built dynamically ----
for f in sorted((ROOT / 'app').rglob('*.php')):
    s = f.read_text()
    rel = str(f.relative_to(ROOT))
    for m in re.finditer(r'(Db::(?:all|one|value|exec)|->(?:exec|query|prepare))\(\s*("(?:[^"\\]|\\.)*"|\'(?:[^\'\\]|\\.)*\')', s, re.S):
        sql = m.group(2)
        if sql[0] == '"':
            interp = re.findall(r'\{\$[^}]+\}|\$\w+', sql)
            interp = [x for x in interp if x not in ('$in',)] if False else interp
            if interp:
                line = s.count('\n', 0, m.start()) + 1
                notes.append(f'{rel}:{line}: interpolated SQL ({", ".join(interp)}) -> verify only trusted identifiers/placeholders: {sql[:70]!r}')
    for m in re.finditer(r'Db::(?:all|one|value|exec)\(\s*[^,)\n]*\.\s*\$', s):
        line = s.count('\n', 0, m.start()) + 1
        notes.append(f'{rel}:{line}: SQL concatenated with a variable -> verify')

# ---- 5. CSRF on every POST route ----
routes = (ROOT / 'app/routes.php').read_text()
posts = re.findall(r"\$router->post\('([^']+)',\s*\[(\w+)::class,\s*'(\w+)'\]", routes)
ctrl_src = {p.stem: p.read_text() for p in (ROOT / 'app/Controllers').glob('*.php')}
def method_body(src, name):
    m = re.search(r'function\s+' + name + r'\s*\(', src)
    if not m: return ''
    i = src.index('{', m.end()); d = 0
    for j in range(i, len(src)):
        if src[j] == '{': d += 1
        elif src[j] == '}':
            d -= 1
            if d == 0: return src[i:j]
    return ''
CSRF_RE = re.compile(r'Csrf::verify|csrfFailure\(|self::guard\(')
for path, cls, meth in posts:
    body = method_body(ctrl_src.get(cls, ''), meth)
    if not CSRF_RE.search(body):
        findings.append(f'POST {path} ({cls}::{meth}) has no CSRF check')
notes.append(f'{len(posts)} POST routes checked for CSRF')

# ---- 6. authorisation guard on private routes ----
GUARD = re.compile(r'requireStaff\(|requireAdmin\(|requireLogin\(|self::load\(|Auth::isSuperAdmin|enabled\(\)')
for path, cls, meth in re.findall(r"\$router->(?:get|post)\('(/(?:admin|portal|setup)[^']*)',\s*\[(\w+)::class,\s*'(\w+)'\]", routes):
    body = method_body(ctrl_src.get(cls, ''), meth)
    if not GUARD.search(body):
        findings.append(f'{path} ({cls}::{meth}) has no authorisation guard')

# ---- 7. hrefs from data ----
for f in sorted((ROOT / 'app/Views').rglob('*.php')):
    s = f.read_text()
    for m in re.finditer(r'href="<\?=\s*e\((\$[\w\[\]\'>-]+)\)\s*\?>"', s):
        ctx = s[max(0, m.start() - 400):m.start()]
        if not re.search(r"preg_match\('#\^https\?|\$isUrl|http", ctx):
            findings.append(f'{f.relative_to(ROOT)}: href from data without scheme check: {m.group(1)}')

# ---- 8. browser JS ----
for f in sorted((ROOT / 'public/js').glob('*.js')):
    s = f.read_text()
    for pat in (r'\.innerHTML\s*=', r'\beval\s*\(', r'document\.write', r'insertAdjacentHTML', r'new Function'):
        if re.search(pat, s): findings.append(f'{f.relative_to(ROOT)}: uses {pat}')

print('Notes:'); [print(' -', n) for n in notes]
if findings:
    print('\nFINDINGS (review each):'); [print(' -', x) for x in findings]; sys.exit(1)
print('\nNo findings from the static security audit (this is NOT a penetration test).')
