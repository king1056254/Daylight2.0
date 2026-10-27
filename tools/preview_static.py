#!/usr/bin/env python3
"""
APPROXIMATE static renderer for CSS checks only (no PHP needed). It expands the real template markup with sample data:
loops repeat 3x, "empty state" branches are skipped, dynamic values become "Sample". It is NOT the PHP app and proves nothing about PHP logic.
Usage: python3 tools/preview_static.py OUT_DIR   -> writes OUT_DIR/<name>-{light,dark}.html
"""
import re, sys, pathlib
ROOT = pathlib.Path(__file__).resolve().parent.parent
VIEWS = ROOT / 'app/Views'
ICONS = dict(re.findall(r"'([\w-]+)' => '(<[^\n]*?)',\n", (ROOT / 'app/Core/helpers.php').read_text()))
TOKEN = re.compile(r'<\?(=|php)(.*?)\?>', re.S)

def match_paren(s, i):
    d, q = 0, ''
    while i < len(s):
        c = s[i]
        if q:
            if c == '\\': i += 2; continue
            if c == q: q = ''
        elif c in '\'"': q = c
        elif c == '(': d += 1
        elif c == ')':
            d -= 1
            if d == 0: return i
        i += 1
    return -1

OPEN = re.compile(r'\b(foreach|if|elseif|while|for)\s*\(')
CLOSE = re.compile(r'\b(endforeach|endif|endwhile|endfor)\s*;|\belse\s*:')

RET = re.compile(r'\breturn\s*;')

def code_events(code):
    ev, i = [], 0
    while True:
        cands = [m for m in (OPEN.search(code, i), CLOSE.search(code, i), RET.search(code, i)) if m]
        if not cands: break
        m = min(cands, key=lambda x: x.start())
        text = m.group(0)
        if text.startswith('return'):
            before = code[:m.start()]
            if before.count('{') - before.count('}') <= 0:
                ev.append(('return',))
            i = m.end(); continue
        if text.startswith(('end', 'else')) and not text.startswith('elseif'):
            ev.append(('else',) if text.startswith('else') else ('end',)); i = m.end(); continue
        end = match_paren(code, m.end() - 1)
        if end < 0: break
        after = code[end + 1:].lstrip()
        if after.startswith(':'):
            ev.append((m.group(1), code[m.end():end])); i = end + 1 + (len(code[end + 1:]) - len(after)) + 1
        else:
            i = end + 1
    return ev, code

def build(src):
    root = []; stack = [root]; ifs = []
    pos = 0
    def add(n): stack[-1].append(n)
    for m in TOKEN.finditer(src):
        if m.start() > pos: add(('text', src[pos:m.start()]))
        pos = m.end()
        if m.group(1) == '=':
            add(('echo', m.group(2).strip())); continue
        code = m.group(2)
        evs, _ = code_events(code)
        for e in evs:
            k = e[0]
            if k in ('foreach', 'while', 'for'):
                body = []; add(('loop', body)); stack.append(body); ifs.append(None)
            elif k == 'if':
                branches = [[e[1], []]]; add(('if', branches)); stack.append(branches[0][1]); ifs.append(branches)
            elif k in ('elseif', 'else'):
                br = ifs[-1]; stack.pop(); br.append([e[1] if k == 'elseif' else 'else', []]); stack.append(br[-1][1])
            elif k == 'end':
                stack.pop(); ifs.pop()
            elif k == 'return':
                add(('return',))
    if pos < len(src): add(('text', src[pos:]))
    return root

def cond_true(cond):
    c = cond.strip()
    if c == 'else': return True
    if re.search(r'\b(done|doneJob|error|formError|notice|errors)\b', c) and 'empty' not in c.split('(')[0] or re.search(r'\$(done|doneJob|error|formError|notice)\b', c): return False
    if '=== []' in c or c.startswith('!') and not c.startswith(('!empty', '!isset')): return False
    return True

def echo_val(code, ctx):
    c = code.strip()
    if c.startswith('partial('):
        name = re.match(r"partial\('([\w/]+)'", c).group(1)
        vars_ = dict(ctx)
        for m in re.finditer(r"'(\w+)'\s*=>\s*(?:'((?:[^'\\]|\\.)*)'|\"((?:[^\"\\]|\\.)*)\")", c):
            vars_[m.group(1)] = (m.group(2) if m.group(2) is not None else m.group(3)).replace("\\'", "'")
        return render_file(name, vars_)
    if c.startswith('icon('):
        m = re.match(r"icon\('([\w-]+)'(?:,\s*(\d+))?(?:,\s*'([\w -]*)')?", c)
        size = m.group(2) or '16'; cls = m.group(3) or ''
        return f'<svg class="icon {cls}" xmlns="http://www.w3.org/2000/svg" width="{size}" height="{size}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">{ICONS.get(m.group(1), "")}</svg>'
    if c.startswith('status_pill('): return '<span class="pill pill-primary">In Progress</span>'
    if c.startswith(('fmt_date(',)): return '<time>1/2/2026</time>'
    if c.startswith('money('): return 'KES 12,000.00'
    if c.startswith('csrf_field('): return '<input type="hidden" name="_csrf" value="x">'
    if c.startswith(('url(', 'e(url(', 'e(asset(', 'asset(')): return '#'
    if re.search(r'\?[^?]', c) and not c.startswith('e('): return ''
    if c == '$content': return ctx.get('__content', '')
    m = re.match(r"e\(\$(\w+)\)$", c)
    if m and m.group(1) in ctx: return str(ctx[m.group(1)])
    if c.startswith('Cells::'): return 'Sample'
    if re.match(r"e\(.*\?\?.*''\)$", c): return ''
    if re.match(r"\(int\)|count\(|number_format|\$\w+\s*\+", c): return '3'
    return 'Sample'

def render_nodes(nodes, ctx):
    out = []
    for n in nodes:
        if n[0] == 'text': out.append(n[1])
        elif n[0] == 'echo': out.append(echo_val(n[1], ctx))
        elif n[0] == 'loop':
            for _ in range(3):
                s, stop = render_nodes(n[1], ctx); out.append(s)
                if stop: return ''.join(out), True
        elif n[0] == 'if':
            for cond, body in n[1]:
                if cond_true(cond):
                    s, stop = render_nodes(body, ctx); out.append(s)
                    if stop: return ''.join(out), True
                    break
        elif n[0] == 'return': return ''.join(out), True
    return ''.join(out), False

def render_file(name, ctx):
    src = (VIEWS / (name + '.php')).read_text()
    s, _ = render_nodes(build(src), ctx)
    return s


import html as _html, json as _json
ESC = lambda v: _html.escape(str(v), quote=True)

def php_array(src):
    """Tiny parser for the PHP array literals in config/legal_*.php (strings, nested [] arrays, => pairs)."""
    src = re.sub(r'/\*.*?\*/', '', src, flags=re.S)
    src = src[src.index('return') + 6:]
    pos = [0]
    def ws():
        while pos[0] < len(src) and src[pos[0]] in ' \t\r\n,;': pos[0] += 1
    def string():
        q = src[pos[0]]; pos[0] += 1; out = ''
        while src[pos[0]] != q:
            if src[pos[0]] == '\\' and src[pos[0] + 1] in (q, '\\'): out += src[pos[0] + 1]; pos[0] += 2
            else: out += src[pos[0]]; pos[0] += 1
        pos[0] += 1
        return out
    def value():
        ws()
        if src[pos[0]] == '[': return array()
        if src[pos[0]] in '\'"': return string()
        m = re.compile(r'[A-Za-z0-9_.]+').match(src, pos[0]); pos[0] = m.end(); return m.group(0)
    def array():
        pos[0] += 1; items = []; assoc = False
        while True:
            ws()
            if src[pos[0]] == ']': pos[0] += 1; break
            v = value(); ws()
            if src.startswith('=>', pos[0]):
                pos[0] += 2; items.append((v, value())); assoc = True
            else: items.append(v)
        return dict(items) if assoc else items
    return value()

def legal_text(text, vars_):
    for k, v in vars_.items(): text = text.replace('{{' + k + '}}', v)
    h = ESC(text)
    h = re.sub(r'\*\*(.+?)\*\*', r'<strong>\1</strong>', h)
    def link(m):
        href = m.group(2)
        if href.startswith('/'): return f'<a href="{ESC(href)}" class="legal-link">{m.group(1)}</a>'
        return f'<a href="{ESC(href)}" class="legal-link" target="_blank" rel="noopener noreferrer">{m.group(1)}</a>'
    return re.sub(r'\[([^\]]+)\]\((/[A-Za-z0-9_\-/#.]*|https://[A-Za-z0-9._~\-/#?=%:]+)\)', link, h)

def legal_page(name):
    doc = php_array((ROOT / f'config/legal_{name}.php').read_text())
    v = {'company': 'Daylight', 'email': 'team@example.com', 'location': 'Nairobi, Kenya'}
    out = [f'<section class="hero"><div class="grid-backdrop"></div><div class="hero-inner"><p class="eyebrow">Legal</p><h1>{ESC(doc["title"])}</h1><p class="lead">Version {ESC(doc["version"])} · Last updated {ESC(doc["effective"])}</p></div></section>',
           '<section class="section section-3xl legal">']
    out += [f'<p class="legal-intro">{legal_text(p, v)}</p>' for p in doc['intro']]
    out.append('<nav class="legal-toc" aria-label="Contents"><h2>Contents</h2><ol>' + ''.join(f'<li><a href="#{ESC(s["id"])}">{ESC(s["title"])}</a></li>' for s in doc['sections']) + '</ol></nav>')
    for n, sec in enumerate(doc['sections'], 1):
        out.append(f'<section id="{ESC(sec["id"])}" class="legal-section"><h2><span class="legal-num">{n}.</span> {ESC(sec["title"])}</h2>')
        for b in sec['blocks']:
            if b[0] == 'p': out.append(f'<p>{legal_text(b[1], v)}</p>')
            elif b[0] in ('ul', 'ol'): out.append(f'<{b[0]} class="legal-list legal-{b[0]}">' + ''.join(f'<li>{legal_text(i, v)}</li>' for i in b[1]) + f'</{b[0]}>')
            elif b[0] == 'table':
                out.append('<div class="table-wrap legal-table"><table class="data-table"><thead><tr>' + ''.join(f'<th>{ESC(h)}</th>' for h in b[1]) + '</tr></thead><tbody>'
                           + ''.join('<tr>' + ''.join(f'<td data-label="{ESC(b[1][ci])}">{legal_text(c, v)}</td>' for ci, c in enumerate(row)) + '</tr>' for row in b[2]) + '</tbody></table></div>')
        out.append('</section>')
    out.append(f'<p class="legal-foot">Version {ESC(doc["version"])} · effective {ESC(doc["effective"])}</p></section>')
    return ''.join(out)

def faq_page():
    sql = (ROOT / 'database/seeds/content.sql').read_text() + (ROOT / 'database/migrations/005_extended_faqs.sql').read_text()
    rows = []
    for m in re.finditer(r"\(UUID\(\), '((?:[^']|'')*)','((?:[^']|'')*)','([^']*)',(\d+)\)", sql.split('INSERT INTO faqs')[1].split('INSERT INTO job_openings')[0]):
        rows.append((m.group(1).replace("''", "'"), m.group(2).replace("''", "'"), m.group(3)))
    for m in re.finditer(r"SELECT UUID\(\), '((?:[^']|'')*)', '((?:[^']|'')*)', '([^']*)', (\d+), 1", sql):
        rows.append((m.group(1).replace("''", "'"), m.group(2).replace("''", "'"), m.group(3)))
    order = ['Getting started', 'Pricing', 'Projects', 'Client portal', 'Support', 'Legal', 'Data protection', 'AI assistant']
    groups = {}
    for i, (q, a, c) in enumerate(rows): groups.setdefault(c, []).append((i, q, a))
    cats = sorted(groups, key=lambda c: (order.index(c) if c in order else 999, c))
    slug = lambda c: re.sub(r'[^a-z0-9]+', '-', c.lower()).strip('-')
    chev = ICONS['chevron-down']
    out = ['<section class="hero"><div class="grid-backdrop"></div><div class="hero-inner"><p class="eyebrow">FAQ</p><h1>Questions we get asked</h1><p class="lead">If your question isn\'t here, send it to us and we\'ll answer it properly.</p></div></section><section class="section section-3xl">',
           '<div class="faq-tools"><div class="faq-search"><label class="sr-only" for="faq-search">Search the questions</label><input id="faq-search" class="input" type="search" placeholder="Search the questions…" autocomplete="off" data-faq-filter></div><nav class="faq-cats" aria-label="Question categories">'
           + ''.join(f'<a href="#faq-cat-{slug(c)}" class="assistant-chip">{ESC(c)}</a>' for c in cats) + '</nav></div>',
           '<p class="faq-empty muted text-sm" data-faq-empty hidden>No question matches your search. Try different words, or ask us directly.</p>']
    for c in cats:
        out.append(f'<div class="faq-group" id="faq-cat-{slug(c)}" data-faq-group><h2 class="eyebrow">{ESC(c)}</h2><div class="accordion">')
        for i, q, a in groups[c]:
            out.append(f'<details name="faq" id="faq-{i}" data-faq-item><summary><span>{ESC(q)}</span><svg class="icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">{chev}</svg></summary><div class="answer">{ESC(a)}</div></details>')
        out.append('</div></div>')
    out.append('<div class="mt-10"><a href="#" class="btn">Ask us something else</a></div></section>')
    return ''.join(out)

def shell(body, dark, css_path, title='Preview'):
    js = str(ROOT / 'public/js')
    return f'''<!DOCTYPE html><html lang="en" class="{'dark' if dark else ''}"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>{title}</title>
<link rel="stylesheet" href="file://{css_path}"><script>localStorage.setItem('daylight-theme', '{'dark' if dark else 'light'}');</script><script src="file://{js}/theme-init.js"></script></head><body>{body}
<script src="file://{js}/app.js"></script></body></html>'''

PAGES = {
    'home': ('site', 'pages/home'), 'services': ('site', 'pages/services'), 'work-detail': ('site', 'pages/work_detail'),
    'contact': ('site', 'pages/contact'), 'quote': ('site', 'pages/quote'), 'faq': ('site', 'pages/faq'), 'careers': ('site', 'pages/careers'),
    'about': ('site', 'pages/about'), 'auth': ('site', 'auth/index'), 'privacy': ('site', 'pages/legal'), 'terms': ('site', 'pages/legal'),
    'portal-dashboard': ('app', 'portal/dashboard'), 'portal-project': ('app', 'portal/project'), 'portal-invoice': ('app', 'portal/invoice'),
    'portal-tickets': ('app', 'portal/tickets'), 'admin-dashboard': ('app', 'admin/dashboard'), 'admin-list': ('app', 'admin/list'),
    'admin-form': ('app', 'admin/form'), 'admin-users': ('app', 'admin/users'),
}

if __name__ == '__main__':
    out = pathlib.Path(sys.argv[1]); out.mkdir(parents=True, exist_ok=True)
    css = str(ROOT / 'public/css/app.css')
    for key, (kind, view) in PAGES.items():
        area_is_portal = key.startswith('portal')
        ctx = {'title': 'Sample page title', 'eyebrow': 'Eyebrow', 'description': 'Sample description text that explains the page in a sentence or two.', 'mode': 'in', 'user': None, 'area': 'portal' if key.startswith('portal') else 'admin'}
        widget = lambda: render_file('partials/assistant', ctx) + f'<script src="file://{ROOT}/public/js/assistant.js"></script>'
        if kind == 'site':
            main = {'faq': faq_page, 'privacy': lambda: legal_page('privacy'), 'terms': lambda: legal_page('terms')}.get(key, lambda: render_file(view, ctx))()
            body = '<div class="page">' + render_file('partials/header', ctx) + '<main>' + main + '</main>' + render_file('partials/footer', ctx) + '</div>' + widget()
        else:
            ctx['__content'] = render_file(view, ctx)
            ctx['pageTitle'] = 'Preview'
            body = render_file('layout/app', ctx)
            body = re.sub(r'<!DOCTYPE.*?<body>', '', body, flags=re.S).replace('</body>', '').replace('</html>', '')
            body = re.sub(r'<script[^>]*></script>', '', body)
            if area_is_portal: body += f'<script src="file://{ROOT}/public/js/assistant.js"></script>'
            if not area_is_portal:  # the layout includes the widget for the portal only (the fake renderer cannot evaluate that condition)
                body = re.sub(r'<div id="daylight-assistant".*?</section>\s*</div>', '', body, flags=re.S)
                body = re.sub(r'<script src="[^"]*assistant\.js"></script>', '', body)
        for dark in (False, True):
            (out / f'{key}-{"dark" if dark else "light"}.html').write_text(shell(body, dark, css, key))
    print('wrote', len(PAGES) * 2, 'files to', out)
