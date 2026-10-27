#!/usr/bin/env python3
"""
Quality check for the FAQ-mode assistant. This is a faithful PYTHON PORT of app/Assistant/Retriever.php + Assistant::faqAnswer
(stop words, synonyms and thresholds are read from the PHP source) run against the site's real content (seed + FAQ migration + static knowledge).
It tests the matching ALGORITHM and the thresholds; it does not execute the PHP.
Usage: python3 tools/retriever_check.py [-v]
"""
import re, sys, math, sqlite3, uuid, pathlib
ROOT = pathlib.Path(__file__).resolve().parent.parent
php = (ROOT / 'app/Assistant/Retriever.php').read_text()
asst = (ROOT / 'app/Assistant/Assistant.php').read_text()
STOP = set(re.findall(r"'([a-z0-9]+)'", re.search(r"private const STOP = \[(.*?)\];", php, re.S).group(1)))
SYN = {}
for m in re.finditer(r"'([a-z0-9]+)' => \[([^\]]*)\]", re.search(r"private const SYNONYMS = \[(.*?)\n    \];", php, re.S).group(1)):
    SYN[m.group(1)] = re.findall(r"'([a-z0-9]+)'", m.group(2))
COV = float(re.search(r"CONFIDENT_COVERAGE = ([0-9.]+)", asst).group(1))
COV_UNKNOWN = float(re.search(r"CONFIDENT_COVERAGE_UNKNOWN_WORD = ([0-9.]+)", asst).group(1))
SCORE = float(re.search(r"CONFIDENT_SCORE = ([0-9.]+)", asst).group(1))

def stem(w):
    n = len(w)
    if n > 4 and w.endswith('ies'): w = w[:-3] + 'y'
    elif n > 6 and w.endswith('ing'): w = w[:-3]
    elif n > 5 and w.endswith('ed'): w = w[:-2]
    elif n > 3 and w.endswith('s') and not w.endswith('ss'): w = w[:-1]
    if len(w) > 4 and w.endswith('e'): w = w[:-1]
    return w

def tokens(text):
    t = (text.lower() + ' ').replace("'s ", ' ').replace('’s ', ' ')
    t = re.sub(r'[^a-z0-9\s]+', ' ', t)
    return [stem(w) for w in t.split() if w and w not in STOP]

def search(query, docs, limit=3):
    q = tokens(query)
    if not q or not docs: return []
    groups = {}
    for raw in re.split(r'[^a-z0-9]+', query.lower()):
        if not raw or raw in STOP: continue
        orig = stem(raw)
        syn = []
        for s in SYN.get(raw, []):
            st = stem(s)
            if st != orig and st not in syn: syn.append(st)
        groups[orig] = syn
    index, df = [], {}
    for d in docs:
        w = {}
        for field, wt in ((d['title'], 3.0), (d.get('keywords', ''), 2.0), (d['text'], 1.0)):
            for t in tokens(field): w[t] = w.get(t, 0.0) + wt
        index.append(w)
        for t in w: df[t] = df.get(t, 0) + 1
    n = len(docs)
    weight = lambda t, w: math.log(1 + n / df.get(t, 1)) * (1 + math.log(1 + w[t]))
    unknown = sum(1 for orig, syns in groups.items() if orig not in df and not any(s in df for s in syns))
    out = []
    for i, d in enumerate(docs):
        score, understood = 0.0, 0
        for orig, syns in groups.items():
            hit = False
            if orig in index[i]: score += weight(orig, index[i]); hit = True
            for s in syns:
                if s in index[i]: score += 0.6 * weight(s, index[i]); hit = True
            if hit: understood += 1
        if score <= 0: continue
        tl = tokens(d['title']); title = ' '.join(tl)
        if len(q) > 1 and title and ' '.join(q) in title: score *= 1.5
        score *= 1 + 0.25 * sum(1 for orig in groups if orig in tl)
        out.append({'doc': d, 'score': score / math.sqrt(len(groups)), 'coverage': understood / len(groups), 'unknown': unknown})
    out.sort(key=lambda r: -r['score'])
    return out[:limit]

def confident(hit): return hit is not None and hit['coverage'] >= (COV_UNKNOWN if hit['unknown'] > 0 else COV) and hit['score'] >= SCORE

# ---------- load the real content ----------
def split_sql(sql):
    out, buf, q, i = [], '', '', 0
    while i < len(sql):
        c = sql[i]
        if q:
            buf += c
            if c == q:
                if sql[i + 1:i + 2] == q: buf += sql[i + 1]; i += 1
                else: q = ''
        elif c in "'\"": q = c; buf += c
        elif c == '-' and sql[i:i + 2] == '--':
            while i < len(sql) and sql[i] != '\n': i += 1
            continue
        elif c == ';':
            if buf.strip(): out.append(buf.strip())
            buf = ''
        else: buf += c
        i += 1
    return out

schema = ''.join(p.read_text() for p in sorted((ROOT / 'database/migrations').glob('00[123]_*.sql')))
con = sqlite3.connect(':memory:')
con.create_function('UUID', 0, lambda: str(uuid.uuid4()))
for m in re.finditer(r'CREATE TABLE IF NOT EXISTS (\w+) \((.*?)\) ENGINE', schema, re.S):
    cols = re.findall(r'^\s*(?:[a-z_]+ (?:CHAR|VARCHAR|TEXT|MEDIUMTEXT|JSON|INT|TINYINT|TIMESTAMP|DATE|BIGINT|DECIMAL|ENUM)[^,\n]*,?\s*)+', m.group(2), re.M)
    names = re.findall(r'([a-z_]+) (?:CHAR|VARCHAR|TEXT|MEDIUMTEXT|JSON|INT|TINYINT|TIMESTAMP|DATE|BIGINT|DECIMAL|ENUM)', m.group(2))
    con.execute(f'CREATE TABLE {m.group(1)} (' + ','.join(f'{c} TEXT' for c in dict.fromkeys(names)) + ')')
seed = re.sub(r"NOW\(\) - INTERVAL (\d+) DAY", r"datetime('now','-\1 day')", (ROOT / 'database/seeds/content.sql').read_text())
con.executescript(seed.replace('NOW()', "datetime('now')"))
for st in split_sql((ROOT / 'database/migrations/005_extended_faqs.sql').read_text()):
    con.execute(st.replace('FROM DUAL', ''))
import json
jl = lambda v: ', '.join(json.loads(v)) if v else ''
docs = []
for q, a, c in con.execute("select question, answer, category from faqs"): docs.append({'title': q, 'text': a, 'keywords': c or '', 'url': '/faq', 'id': 'faq'})
for t, s, d, f in con.execute("select title, summary, description, features from services"): docs.append({'title': t, 'keywords': 'service we build offer', 'text': f'{s} {d} Includes: {jl(f)}.', 'url': '/services', 'id': 'service'})
for n, s in con.execute("select name, summary from industries"): docs.append({'title': n + ' (industry)', 'keywords': 'industry sector clients we serve', 'text': s, 'url': '/industries', 'id': 'industry'})
for n, t, d, f, st in con.execute("select name, tagline, description, features, status from saas_products"): docs.append({'title': n, 'keywords': 'product saas software status', 'text': f'{t}. {d} Status: {st}. Features: {jl(f)}.', 'url': '/products', 'id': 'product'})
for t, s, ind, tech in con.execute("select title, summary, industry, technologies from portfolio_projects"): docs.append({'title': t + ' (case study)', 'keywords': 'case study project portfolio work example ' + (ind or ''), 'text': f'{s} Built with {jl(tech)}.', 'url': '/work', 'id': 'work'})
techs = {}
for n, c in con.execute("select name, category from technologies"): techs.setdefault(c, []).append(n)
docs.append({'title': 'Technologies we use', 'keywords': 'technology stack tools languages frameworks database', 'text': '. '.join(f'{c}: ' + ', '.join(v) for c, v in techs.items()) + '.', 'url': '/technologies', 'id': 'technologies'})
jobs = [r[0] for r in con.execute("select title from job_openings")]
docs.append({'title': 'Careers and open roles', 'keywords': 'job career hiring vacancy apply work for us', 'text': 'Open roles: ' + ', '.join(jobs) + '. You can apply from the Careers page.', 'url': '/careers', 'id': 'careers'})
static = (ROOT / 'config/assistant_knowledge.php').read_text()
for m in re.finditer(r"\['id' => '([\w-]+)', 'title' => '((?:[^'\\]|\\.)*)', 'url' => '([^']*)', 'keywords' => '((?:[^'\\]|\\.)*)',\s*'text' => '((?:[^'\\]|\\.)*)'\]", static, re.S):
    fill = lambda s: s.replace("\\'", "'").replace('{{email}}', 'team@example.com').replace('{{company}}', 'Daylight').replace('{{location}}', 'Nairobi, Kenya')
    docs.append({'id': m.group(1), 'title': fill(m.group(2)), 'url': m.group(3), 'keywords': m.group(4), 'text': fill(m.group(5))})

# ---------- scenarios: (question, substring that the top title/text must contain) ----------
SHOULD_ANSWER = [
    ("How long does a website take?", "2–4 weeks"), ("how much does a website cost", "KSh 50,000"), ("What is the price of a custom system?", "KSh 300,000"),
    ("Who owns the source code?", "source code"), ("Do you offer support after launch?", "maintenance"), ("can you work with my existing system", "APIs"),
    ("How do I get access to the client portal", "Create an account"), ("I forgot my password", "reset link"), ("how do i raise a support ticket", "ticket"),
    ("where can I see my invoices", "Invoices"), ("Do you build mobile apps?", "mobile"), ("do you do ecommerce with mpesa", "M-Pesa"),
    ("how can I contact you", "contact"), ("are you hiring", "role"), ("what technologies do you use", "PHP"),
    ("do you use cookies", "cookie"), ("how do I delete my personal data", "Email us"), ("is the chat assistant a real person", "automated"),
    ("what law governs the contract", "Kenya"), ("will you sign an NDA", "non-disclosure"), ("how long is a quote valid", "30 days"),
    ("how do payments work", "payment schedule"), ("can I ask for changes during the project", "change request"), ("what is discovery", "Discovery"),
    ("which industries do you serve", "SMEs"), ("tell me about your products", "SaaS"), ("do you share my data with third parties", "sell personal data"),
    ("how is my data protected in the portal", "hashes"), ("can several people use the portal", "linked to one account"), ("do your prices include VAT", "Value Added Tax"),
    # ---- added after tuning, to check generalisation (not used to tune the algorithm) ----
    ("what is included in your maintenance plan", "hosting"), ("can you build a website for my school", "web"), ("do you do AI or automation", "automation"),
    ("how do I apply for a job", "apply"), ("where is your office", "Nairobi"), ("what happens after the project is finished", "hand over"),
    ("can I follow progress on my project", "progress"), ("are passwords stored safely", "one-way"), ("what cloud hosting do you provide", "Cloud"),
    ("tell me about daylight books", "accounting"), ("how do I report a bug", "ticket"), ("is the assistant giving legal advice", "legal"),
]
SHOULD_NOT_ANSWER = [
    "what is the weather in nairobi today", "who won the football match", "can you write me a poem", "what is the capital of france",
    "how do I cook ugali", "tell me a joke", "what's the exchange rate for dollars", "recommend a good movie",
    "what is your favourite colour", "how tall is mount kenya", "translate hello to french", "solve two plus two",
    "who is the president", "what time is it", "can you book me a flight", "write python code to sort a list", "what is the best phone to buy", "give me a recipe for pilau",
]
ROUND3_IN = [("do you build web apps", "web"), ("can I get the source code", "source code"), ("can you integrate with my accounting software", "integrat"),
    ("how do I change my password", "password"), ("how do I know my project status", "progress"), ("can I pay with mpesa", "pay"), ("is there a customer portal", "portal")]
ROUND3_OUT = ["what is your refund policy", "how many employees do you have", "what is bitcoin", "can you hack my competitor", "when was daylight founded", "do you have an office in london"]
verbose = '-v' in sys.argv
good, bad = 0, []
for q, needle in SHOULD_ANSWER:
    hits = search(q, docs); top = hits[0] if hits else None
    ok = confident(top) and needle.lower() in (top['doc']['title'] + ' ' + top['doc']['text']).lower()
    good += ok
    if not ok: bad.append((q, needle, top['doc']['title'] if top else None, round(top['score'], 2) if top else 0, round(top['coverage'], 2) if top else 0))
    elif verbose: print(f"  ok  {q!r:55} -> {top['doc']['title'][:50]!r} (score {top['score']:.1f}, cov {top['coverage']:.2f})")
false_pos = []
for q in SHOULD_NOT_ANSWER:
    hits = search(q, docs); top = hits[0] if hits else None
    if confident(top): false_pos.append((q, top['doc']['title'], round(top['score'], 2), round(top['coverage'], 2)))
print(f"documents indexed: {len(docs)}   thresholds: coverage>={COV}, score>={SCORE}")
print(f"in-scope questions answered correctly: {good}/{len(SHOULD_ANSWER)}")
for b in bad: print('  MISS', b)
print(f"out-of-scope questions wrongly answered: {len(false_pos)}/{len(SHOULD_NOT_ANSWER)}")
for f in false_pos: print('  FALSE POSITIVE', f)
r3_miss = [q for q, n in ROUND3_IN if not (lambda t: confident(t) and n.lower() in (t['doc']['title'] + ' ' + t['doc']['text']).lower())((search(q, docs) or [None])[0])]
r3_fp = [q for q in ROUND3_OUT if confident((search(q, docs) or [None])[0])]
print(f"round 3 (fresh questions, not used for tuning): in-scope {len(ROUND3_IN) - len(r3_miss)}/{len(ROUND3_IN)}, out-of-scope wrongly answered {len(r3_fp)}/{len(ROUND3_OUT)}")
for q in r3_miss: print('  ROUND3 MISS', q)
for q in r3_fp: print('  ROUND3 FALSE POSITIVE', q)
sys.exit(0 if not bad and not false_pos else 1)
