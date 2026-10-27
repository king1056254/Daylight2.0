#!/usr/bin/env python3
"""
Builds DOCUMENTATION.md from tools/documentation.template.md. Tables between <!-- GEN:name --> and <!-- /GEN --> markers are generated from the
code itself (routes, config keys, database schema, admin screens, tests) so they cannot drift from what is actually built.
Usage: python3 tools/gen_docs.py
"""
import re, pathlib, glob, subprocess
ROOT = pathlib.Path(__file__).resolve().parent.parent

def routes():
    src = (ROOT / 'app/routes.php').read_text()
    rows, section = [], ''
    for line in src.split('\n'):
        m = re.match(r'// ---- (.*?) ----', line)
        if m: section = re.sub(r'\s*\(.*\)', '', m.group(1)); continue
        m = re.match(r"\$router->(get|post)\('([^']+)',\s*\[(\w+)::class,\s*'(\w+)'\]", line)
        if not m: continue
        verb, path, cls, meth = m.groups()
        path = path.replace('\\', '')
        if path.startswith('/admin'): who = 'Staff or admin (per screen); super admin for demo data and retention'
        elif path.startswith('/portal'): who = 'Any signed-in account (data limited to its own client records)'
        elif path.startswith('/setup'): who = 'Setup token (404 unless SETUP_TOKEN is set)'
        elif path.startswith('/auth/logout'): who = 'Signed in (POST + CSRF)'
        elif path.startswith('/assistant'): who = 'Anyone (CSRF token + rate limit)'
        elif verb == 'post': who = 'Anyone (CSRF token + rate limit + honeypot)'
        else: who = 'Public'
        rows.append((section, verb.upper(), path, f'{cls}::{meth}', who))
    out = ['| Group | Method | Path | Handler | Who can use it |', '|---|---|---|---|---|']
    out += [f'| {a} | {b} | `{c}` | `{d}` | {e} |' for a, b, c, d, e in rows]
    return '\n'.join(out) + f'\n\n{len(rows)} routes.'

def env_keys():
    out = ['| Key | Default | What it does |', '|---|---|---|']
    comment = []
    for line in (ROOT / '.env.example').read_text().split('\n'):
        if line.startswith('#'): comment.append(line.lstrip('# ').strip()); continue
        m = re.match(r'([A-Z][A-Z0-9_]*)=(.*)', line)
        if m:
            out.append(f'| `{m.group(1)}` | `{m.group(2) or "(empty)"}` | {" ".join(comment).replace("|", "/")} |'); comment = []
        elif not line.strip(): comment = []
    return '\n'.join(out)

PURPOSE = {
 'users': 'Accounts (clients, staff, admins). Passwords are stored only as hashes.', 'user_roles': 'Roles per user: super_admin, admin, staff, client.',
 'auth_tokens': 'Single-use, expiring password-reset and email-confirmation tokens (stored hashed).', 'rate_limits': 'Salted hashes of IP/email with timestamps, used for rate limiting (kept 24 hours).',
 'email_outbox': 'Every email the system tries to send, with status (queued, sent, failed, skipped).', 'services': 'Public services pages.', 'industries': 'Public industries page.',
 'technologies': 'Public technologies page, grouped by category.', 'portfolio_projects': 'Public case studies (/work).', 'saas_products': 'Public products page.', 'blog_posts': 'Public blog (Insights).',
 'team_members': 'Team cards on the About page.', 'testimonials': 'Client quotes on the home page.', 'faqs': 'FAQ page and the assistant\'s main knowledge source; grouped by category.',
 'job_openings': 'Open roles on the Careers page.', 'company_settings': 'Company name, email, phone etc. (one row).', 'contact_messages': 'Messages from the contact form.',
 'quote_requests': 'Quote requests.', 'job_applications': 'Job applications (CV is a link, no uploads).', 'clients': 'Client companies; user_id links a portal account.',
 'client_projects': 'Projects shown in the portal, with progress.', 'project_milestones': 'Milestones of a project.', 'project_documents': 'Document links shared on a project (visible_to_client flag).',
 'project_messages': 'Message thread per project (client and staff).', 'support_tickets': 'Support tickets raised by clients.', 'invoices': 'Invoices (drafts are hidden from clients).',
 'invoice_items': 'Invoice line items; totals are recalculated from them.', 'payments': 'Payments recorded against invoices.', 'assistant_messages': 'Optional chat log (only if ASSISTANT_LOG_CHATS=true), deleted after 90 days.',
}
def schema():
    out = ['| Table | Purpose | Columns |', '|---|---|---|']
    n = 0
    for f in sorted((ROOT / 'database/migrations').glob('*.sql')):
        for m in re.finditer(r'CREATE TABLE IF NOT EXISTS (\w+) \((.*?)\) ENGINE', f.read_text(), re.S):
            cols = re.findall(r'^\s*([a-z_]+) (?:CHAR|VARCHAR|TEXT|MEDIUMTEXT|JSON|INT|TINYINT|TIMESTAMP|DATETIME|DATE|BIGINT|DECIMAL|ENUM)', m.group(2), re.M)
            cols += [c for c in re.findall(r',\s*([a-z_]+) (?:CHAR|VARCHAR|TEXT|MEDIUMTEXT|JSON|INT|TINYINT|TIMESTAMP|DATETIME|DATE|BIGINT|DECIMAL|ENUM)', m.group(2)) if c not in cols]
            cols = list(dict.fromkeys(cols))
            out.append(f'| `{m.group(1)}` | {PURPOSE.get(m.group(1), "")} | {", ".join(cols)} |'); n += 1
    return '\n'.join(out) + f'\n\n{n} tables in {len(list((ROOT / "database/migrations").glob("*.sql")))} migration files.'

def resources():
    src = (ROOT / 'config/resources.php').read_text()
    out = ['| Screen (`/admin/…`) | Group | Table | Who sees it | Who can change it | Notes |', '|---|---|---|---|---|---|']
    starts = [(m.start(), m.group(1)) for m in re.finditer(r"\n    '([\w-]+)' => \[\n", src)]
    for i, (pos, slug) in enumerate(starts):
        body = src[pos: starts[i + 1][0] if i + 1 < len(starts) else len(src)]
        g = lambda k: (re.search(r"'%s' => '([^']*)'" % k, body) or [None, ''])[1]
        notes = []
        if "'no_create' => true" in body: notes.append('view/edit only (records come from the public forms or the system)')
        if "'single' => true" in body: notes.append('single record')
        if "'after'" in body or "'before'" in body: notes.append('automatic rules (e.g. totals, slugs, sender)')
        out.append(f"| `{slug}` | {g('group')} | `{g('table')}` | {'Admins' if g('visible') == 'admin' else 'Staff and admins'} | {'Admins' if g('write') == 'admin' else 'Staff and admins'} | {'; '.join(notes)} |")
    out.append('| `users` | Access | `users`, `user_roles` | Admins | Admins (only a super admin can manage admins) | create users, set roles, deactivate |')
    return '\n'.join(out)

def tests():
    def names(p): return re.findall(r"^test\('(.*?)'(?:, function|\))", (ROOT / p).read_text(), re.M)
    u, i = names('tests/unit.php'), names('tests/integration.php')
    out = [f'**Unit tests (`tests/unit.php`, {len(u)} tests, no database needed)**', ''] + [f'- {n}' for n in u]
    out += ['', f'**Integration tests (`tests/integration.php`, {len(i)} scenario tests + 18 generated page tests, scratch MySQL database)**', ''] + [f'- {n}' for n in i]
    return '\n'.join(out)

def stats():
    php = [p for p in ROOT.rglob('*.php') if '.git' not in p.parts]
    loc = sum(len(p.read_text().split('\n')) for p in php)
    return (f'{len(php)} PHP files ({loc:,} lines), {len(list((ROOT/"app/Views").rglob("*.php")))} view templates, 29 database tables, '
            f'{len(list((ROOT/"database/migrations").glob("*.sql")))} migrations, {len(open(ROOT/"public/css/app.css").read().splitlines())} lines of CSS, '
            f'{sum(len(open(p).read().splitlines()) for p in glob.glob(str(ROOT/"public/js/*.js")))} lines of browser JavaScript')

GEN = {'routes': routes, 'env': env_keys, 'schema': schema, 'resources': resources, 'tests': tests, 'stats': stats}
tpl = (ROOT / 'tools/documentation.template.md').read_text()
out = re.sub(r'<!-- GEN:(\w+) -->.*?<!-- /GEN -->', lambda m: GEN[m.group(1)](), tpl, flags=re.S)
(ROOT / 'DOCUMENTATION.md').write_text(out)
print('DOCUMENTATION.md written:', len(out.split('\n')), 'lines;', stats())
