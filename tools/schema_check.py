#!/usr/bin/env python3
"""
Cross-checks the database schema against the code: foreign keys resolve; every column named in config/resources.php (fields, list, search, order,
relations) exists; INSERT/Db::insert column lists in PHP exist; Content::TABLES whitelist and Retention rules use real tables/columns.
Usage: python3 tools/schema_check.py
"""
import re, sys, pathlib
ROOT = pathlib.Path(__file__).resolve().parent.parent
problems = []
TYPES = r'(?:CHAR|VARCHAR|TEXT|MEDIUMTEXT|JSON|INT|TINYINT|TIMESTAMP|DATETIME|DATE|BIGINT|DECIMAL|ENUM)'

tables, fks = {}, []
for f in sorted((ROOT / 'database/migrations').glob('*.sql')):
    for m in re.finditer(r'CREATE TABLE IF NOT EXISTS (\w+) \((.*?)\) ENGINE', f.read_text(), re.S):
        body = m.group(2)
        cols = list(dict.fromkeys(re.findall(r'(?:^|,)\s*([a-z_]+) ' + TYPES + r'\b', body, re.M)))
        tables[m.group(1)] = cols
        for fk in re.finditer(r'FOREIGN KEY \((\w+)\) REFERENCES (\w+)\((\w+)\)', body):
            fks.append((m.group(1), fk.group(1), fk.group(2), fk.group(3)))
for t, c, rt, rc in fks:
    if rt not in tables or rc not in tables[rt] or c not in tables[t]:
        problems.append(f'foreign key {t}.{c} -> {rt}.{rc} does not resolve')

# resources: split on the start of each top-level entry (robust: no overlapping matches)
src = (ROOT / 'config/resources.php').read_text()
starts = [(m.start(), m.group(1)) for m in re.finditer(r"\n    '([\w-]+)' => \[\n", src)]
blocks = {slug: src[pos: starts[i + 1][0] if i + 1 < len(starts) else len(src)] for i, (pos, slug) in enumerate(starts)}
for slug, body in blocks.items():
    tm = re.search(r"'table' => '(\w+)'", body)
    if not tm or tm.group(1) not in tables:
        problems.append(f'resource {slug}: unknown table {tm.group(1) if tm else None}'); continue
    t = tm.group(1)
    fields_part = body[body.index("'fields' => ["):]
    for n in re.findall(r"^\s{12}'(\w+)' => ", fields_part, re.M):
        if n not in tables[t]: problems.append(f'resource {slug}: field {n} not in {t}')
    for key in ('list', 'search'):
        km = re.search(r"'%s' => \[([^\]]*)\]" % key, body)
        for n in re.findall(r"'(\w+)'", km.group(1)) if km else []:
            if n not in tables[t]: problems.append(f'resource {slug}: {key} column {n} not in {t}')
    om = re.search(r"'order' => '([^']*)'", body)
    for n in re.findall(r"[a-z_]+", om.group(1)) if om else []:
        if n not in ('ASC', 'DESC', 'asc', 'desc') and n not in tables[t]: problems.append(f'resource {slug}: order column {n} not in {t}')
    for rm in re.finditer(r"'rel' => \['table' => '(\w+)', 'label' => '(\w+)'\]", body):
        if rm.group(1) not in tables or rm.group(2) not in tables[rm.group(1)]:
            problems.append(f'resource {slug}: relation {rm.group(1)}.{rm.group(2)} missing')
    for sk in re.findall(r"'slug_from' => '(\w+)'", body):
        if sk not in tables[t]: problems.append(f'resource {slug}: slug_from {sk} missing')
    for uq in re.findall(r"'unique' => \[([^\]]*)\]", body):
        for n in re.findall(r"'(\w+)'", uq):
            if n not in tables[t]: problems.append(f'resource {slug}: unique column {n} missing')

# PHP code: INSERT column lists and Db::insert arrays
for f in sorted((ROOT / 'app').rglob('*.php')):
    s = f.read_text()
    for m in re.finditer(r"INSERT INTO (\w+) \(([^)]*)\)", s):
        t = m.group(1)
        if t in tables:
            for c in [x.strip() for x in m.group(2).split(',')]:
                if c not in tables[t]: problems.append(f'{f.relative_to(ROOT)}: INSERT INTO {t} column {c} missing')
    for m in re.finditer(r"Db::insert\('(\w+)',\s*\[(.*?)\]\s*\)", s, re.S):
        t = m.group(1)
        if t in tables:
            for c in re.findall(r"'(\w+)'\s*=>", m.group(2)):
                if c not in tables[t]: problems.append(f'{f.relative_to(ROOT)}: Db::insert({t}) column {c} missing')

# Content::TABLES and Retention::RULES
content = (ROOT / 'app/Models/Content.php').read_text()
for m in re.finditer(r"'(\w+)' => \[(?:\"[^\"]*\"|'[^']*'), '([^']*)', \[", content):
    t = m.group(1)
    if t not in tables: problems.append(f'Content::TABLES unknown table {t}'); continue
    for c in re.findall(r"[a-z_]+", m.group(2)):
        if c not in ('ASC', 'DESC') and c not in tables[t]: problems.append(f'Content::TABLES {t}: order column {c} missing')
for m in re.finditer(r"\['(\w+)', '(\w+)', \d+, '(?:DAY|MONTH|YEAR)'", (ROOT / 'app/Core/Retention.php').read_text()):
    if m.group(1) not in tables or m.group(2) not in tables[m.group(1)]: problems.append(f'Retention rule {m.group(1)}.{m.group(2)} missing')

print(f'{len(tables)} tables, {len(fks)} foreign keys, {len(blocks)} admin resources checked.')
if problems:
    print('PROBLEMS:'); [print(' -', p) for p in problems]; sys.exit(1)
print('Schema, admin config, SQL column lists and retention rules agree (this does not run SQL).')
