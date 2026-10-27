# TEST_REPORT.md

## Summary
**No PHP code has been executed. No test of the PHP application has run. Nothing is Verified.**
The build environment has no PHP and no MySQL. It does have a browser engine, so the stylesheet and the JavaScript *were* tested in Chromium against an approximate rendering of the real templates with sample data (rows 10–12). What follows is exactly what was run, and what was not.

**Is everything OK, with no errors? Unknown — and unlikely.** Code that has never been executed almost certainly contains some mistakes. The checks below make the most common ones unlikely, but only `php -l` and the test suites on a real PHP server can show it.

## Checks that actually ran (all in the build environment)
| # | Check | Result | What it proves — and does not |
|---|---|---|---|
| 1 | `python3 tools/lint.py` — bracket balance, alternative-syntax balance in templates, duplicate methods, missing classes/views, calls to undefined static/`self::` methods, routes → controller methods, unknown helper functions in templates, admin hooks exist | 80 PHP files, 29 classes, **no problems** | Catches wiring mistakes. **Not** a PHP parser; will miss real syntax errors. |
| 2 | Fault-injection test of the checker (6 deliberate faults in a throw-away copy) | 5 reported (duplicate method, undefined static method, unbalanced template block, unknown function, bad route); the 6th (missing brace) was masked by another injected edit and not separately confirmed | Shows the checker is not vacuous. |
| 3 | Pygments PHP lexer scan of all non-template PHP files | 0 error tokens | Weak extra signal only. |
| 4 | Schema cross-check script: 28 tables, 16 foreign keys resolve; every column named in `config/resources.php` (fields, list, search, order, relations) and in `Db::insert` / `INSERT INTO` calls exists in the migrations | **0 problems** | Names agree. SQL was not executed on MySQL. |
| 5 | Seed SQL (`database/seeds/content.sql`) executed against an in-memory **SQLite stand-in** built from the schema column lists | Parsed; row counts 1 settings, 8 services, 7 industries, 19 technologies, 4 case studies, 3 products, 3 posts, 2 team, 3 testimonials, 5 FAQs, 3 jobs (match the original seed); all JSON arrays valid | Quoting/arity are right. Not MySQL; `UUID()`/`NOW()` were stubbed. |
| 6 | Grep of all templates for inline `style="` and inline `<script>` (the CSP forbids both) | none | Pages should not be blocked by the CSP. Unconfirmed in a browser. |
| 7 | Manual comparison of ported page copy and page titles/descriptions with the source for privacy, terms, services, work, blog, products, industries, technologies, FAQ, home, about, contact, quote, careers, auth | Differences found and fixed (static titles on detail pages, spacing classes) | Human reading, not automated. |
| 8 | `python3 tools/lint_deep.py` — lines that probably lack a `;`, and every variable a view uses is passed by each controller call | clean (69 call sites; one array built in a variable checked by hand). Fault-injected copy: 3 of 3 injected faults reported | Catches missing variables and terminators. Still not a parser. |
| 9 | `python3 tools/security_audit.py` — unescaped output, dangerous functions, raw superglobals, dynamic SQL, CSRF on all 17 POST routes, guard on every admin/portal/setup route, unchecked links, unsafe browser JS | **1 real finding** (product link scheme) fixed; the rest were false positives, fixed in the script; 11 dynamic-SQL spots reviewed by hand (trusted identifiers/placeholders only). Fault-injected copy: 4 of 4 real vulnerabilities reported | Static review only; see SECURITY_REVIEW.md. |
| 10 | Browser render check: 18 page types × 3 widths (375/768/1280) × light/dark = 108 renders of the real template markup with sample data, real `app.css` | no horizontal scrolling, no console errors. **1 real CSS bug found and fixed** (mobile sidebar/admin stretched by a full-height grid). Screenshots of home (light+dark), contact (mobile), portal dashboard, admin form (mobile) were looked at | Tests CSS only: fallback fonts (Google Fonts unreachable), sample text, and the original site is **not** compared. |
| 11 | Browser interaction checks with the real `app.js`: mobile menu open/close/auto-close, desktop nav, theme toggle + storage, careers apply toggle, FAQ exclusive accordion, double-submit protection, honeypot hidden, admin sidebar, delete confirmation | 25 of 25 passed | Behaviour of the JS, not of the PHP. |
| 12 | `theme-init.js` in isolation: stored choice beats OS setting, OS light/dark default, junk value ignored | 5 of 5 passed | |
| 13 | `python3 tools/schema_check.py` — foreign keys, all 24 admin screens (fields, list, search, order, relations), SQL column lists in PHP, `Content` and retention rules against the 29 tables | **0 problems.** (An earlier version of this check skipped every other admin screen because of a pattern bug; found while generating the documentation, fixed, re-run on all 24.) | Names agree; SQL still not run on MySQL. |
| 14 | Python port of the assistant's retriever (`tools/retriever_check.py`) run on the real content (77 documents) | 42/42 in-scope questions answered correctly and 0/18 off-topic answered (these were used to tune it: first run was 26/30 and 1 false answer). 7 fresh questions: 5/7 before one generic synonym was added, 6/7 after; 0/6 off-topic false answers | Tests the matching algorithm and thresholds, not the PHP. Real-world accuracy will be lower than on a tuning set. |
| 15 | FAQ migration `005_extended_faqs.sql` loaded into an SQLite stand-in twice | 37 statements, 37 rows, still 37 after the second run (idempotent), 8 categories | Not MySQL. |
| 16 | Browser tests of the chat widget, FAQ search and legal pages against the preview harness with a mocked endpoint (`tools/browser/assistant.js`) | **61 of 61 passed**: open/close/Escape/focus, suggestions, history sent, replies shown as text only (HTML injection attempt stays inert), only internal links, errors/timeouts, send disabled while waiting, 500-character limit, New chat, phone layout, dark theme, FAQ search/groups/exclusive accordion, legal contents anchors, no sideways scrolling, policy tables stack into cards on phones | Tests the widget's JavaScript and CSS, not the PHP endpoint. |
| 17 | Render check of 19 page types × 3 widths × light/dark (114 renders) after the new pages | no horizontal scrolling, no console errors | Sample data, fallback fonts. |
| 18 | Id-collision scan (legal section ids vs template ids) | none after fixing a real bug found by reading the tests: the widget and the privacy page's `#assistant` section shared an id, which would have broken the widget on that page | |

## Tests written but NOT run
- `tests/unit.php` — 34 tests (config parsing, validator, router, migration splitter, helpers, mailer header safety, CSRF, redirect safety, proxy-IP handling, admin resource definitions, **retriever, assistant intents, AI client payload/guard rails, legal-text escaping, legal documents vs retention rules, assistant knowledge, FAQ migration**).
- `tests/integration.php` — 46 scenario tests + 18 generated page tests, driving the real router against a scratch MySQL database: public pages, 404s, unpublished content, forms (valid/invalid/CSRF/honeypot/rate-limit), auth (register/login/logout/reset/rate limit), authorisation (anonymous/client/staff/admin/super admin), XSS escaping, portal isolation between clients, invoice totals and payment settlement, demo-data load/remove, **sitemap.xml and robots.txt**, and **attack simulations** (bad CSRF on every POST route, SQL-injection strings, stored XSS, mass assignment, header injection, setup page without token, no-store headers, deactivated accounts, access-control sweep over every admin screen), the **assistant endpoint** (answers, honest not-found, validation, CSRF, rate limit, no markup reflected, opt-in logging, AI-mode fallback, widget placement and off-switch), **legal pages**, the **FAQ page** and the **retention tool**.
While reading the tests back, wrong expectations were found and corrected by inspection (escaped apostrophes, MySQL JSON re-formatting). Others may remain.

## Not verified at all
`php -l` of every file · unit and integration tests · MySQL migrations and seed load · routing and `.htaccess` rewrites · sessions · every form and page in a browser · console/network errors · responsive layout and visual match with the original (no screenshots) · SMTP and email delivery · password-reset email · InfinityFree specifics (PHP version, rewrites, outbound SMTP, client IP) · security headers in a real response · performance.

## Known failures / blockers
| Item | Detail |
|---|---|
| No PHP/MySQL in the build environment | Blocks every execution test. Next step: `COMPUTER_SETUP_CHECKLIST.md` §1–3. |
| SMTP credentials | None provided. Email is implemented, not tested. |
| GitHub access | Could not clone; source read through the Lovable connector. |
| Original screenshots | Needed for visual comparison at 375/768/1280 px. |
| `favicon.ico` | Binary; must be copied from the original repo. |
