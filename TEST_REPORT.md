# TEST_REPORT.md

## Summary
**No PHP code has been executed. No test of the application has run. Nothing is Verified.**
The environment used so far has no PHP, no MySQL, no browser and no network. What follows is exactly what *was* run, and what was not.

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

## Tests written but NOT run
- `tests/unit.php` — 21 tests (config parsing, validator, router, migration splitter, helpers, mailer header safety, CSRF, redirect safety, proxy-IP handling, admin resource definitions).
- `tests/integration.php` — 23 scenario tests + 18 generated page tests, driving the real router against a scratch MySQL database: public pages, 404s, unpublished content, forms (valid/invalid/CSRF/honeypot/rate-limit), auth (register/login/logout/reset/rate limit), authorisation (anonymous/client/staff/admin/super admin), XSS escaping, portal isolation between clients, invoice totals and payment settlement, demo-data load/remove.
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
