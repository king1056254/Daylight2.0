# FEATURE_CHECKLIST.md

Status vocabulary — **Implemented**: code written · **Static**: passed the custom static checks (`tools/lint.py`; this is *not* `php -l`) · **Tested**: a test actually ran and passed · **Verified**: behaviour confirmed in a real environment · **Blocked**.

Today: every item below is **Implemented + Static** at best. Nothing is Tested or Verified, because there is no PHP/MySQL in the environment used so far.
Tests exist for most items (`tests/unit.php`, `tests/integration.php`) but have **not been run**.

## Public pages (original routes, same URLs)
| Page | Status | Notes |
|---|---|---|
| `/` home | Implemented · Static | stats are hardcoded exactly as in the original (unverified claims) |
| `/services`, `/services/{slug}` | Implemented · Static | |
| `/work`, `/work/{slug}` | Implemented · Static | |
| `/products` | Implemented · Static | |
| `/industries`, `/technologies` | Implemented · Static | |
| `/about` | Implemented · Static | |
| `/blog`, `/blog/{slug}` | Implemented · Static | |
| `/careers` | Implemented · Static | apply forms toggle with JS |
| `/contact`, `/quote` | Implemented · Static | |
| `/faq`, `/privacy`, `/terms` | Implemented · Static | text compared with source by reading |
| `/auth` (+ forgot/reset/verify) | Implemented · Static | |
| 404 / 405 / 419 / 500 pages | Implemented · Static | |

## Shared UI
Header (sticky, 6 nav links, mobile menu, theme toggle, account button, CTA) · Footer · PageHero · Section heading · Status pill · Lead form · Dark/light tokens · No-flash theme: all **Implemented · Static**. Visual comparison with the original: **not done** (no screenshots).

## Functional features
| Feature | Status | Notes |
|---|---|---|
| Contact / quote / job-application forms (validation, CSRF, honeypot, rate limit, DB insert) | Implemented · Static | success shown only after insert |
| Staff email on new submission | Implemented · Static · **Blocked** (no SMTP credentials) | delivery never tested |
| Register / sign in / sign out | Implemented · Static | |
| Password reset by email | Implemented · Static · **Blocked** (SMTP) | form says honestly when email is off |
| Optional email confirmation | Implemented · Static · **Blocked** (SMTP) | off by default |
| Roles + server-side access control | Implemented · Static | staff / admin / super_admin / client |
| DB-backed public content | Implemented · Static | published flag, ordering as original |
| Original seed content | Implemented · structure checked with SQLite stand-in | MySQL load unverified |
| One-time web setup (`/setup`) | Implemented · Static | for hosts without SSH; shows nothing without the token |
| `/sitemap.xml` + `/robots.txt` | Implemented · Static | added; not in the original site; needs `APP_URL` |
| Website chat assistant, FAQ mode (retrieval over site content, CSRF, rate limit, text-only rendering) | Implemented · Static · algorithm evaluated in a Python port (42/42 tuning questions, 0/24 off-topic answered) · widget **Tested in a browser** with a mocked endpoint (61 checks) | PHP endpoint not yet run |
| Chat assistant, AI mode (Anthropic, server-side, grounded prompt, daily cap, fallback) | Implemented · Static · **Blocked** (no API key; never called) | off by default |
| Privacy policy v2.0 and Terms of service v2.0 (17 and 22 numbered sections, tables, anchors) | Implemented · Static · layout tested in a browser | **legal review required** (LEGAL_REVIEW.md) |
| Client FAQ: 42 questions, 8 categories, live search | Implemented · migration SQL structure-checked (SQLite) · search tested in a browser | |
| Data retention tool (CLI + super-admin button) | Implemented · Static | matches the periods in the privacy policy (unit test written) |
| Security hardening (CSRF, CSP, rate limits, no-store, role checks) | Implemented · Static (audit script) | see SECURITY_REVIEW.md |
| Public CSS/JS (menu, theme toggle, accordion, forms) | Implemented · **Tested in a browser against sample data** (not against the PHP app) | 108 renders + 30 interaction checks |

## Client portal (new UI, approved)
Dashboard, projects, project detail (milestones, shared documents, messages), invoices + detail (items, payments, balance), support tickets (create/list): **Implemented · Static**. Ownership is enforced in every query; draft invoices and hidden documents are not shown.

## Admin (new UI, approved)
Dashboard; inbox (contact, quotes, applications: view + status); clients, client projects, milestones, documents (links), project messages, tickets; invoices (auto number, auto totals), invoice items, payments (auto "Paid"); website content (services, industries, technologies, case studies, products, blog, team, testimonials, FAQs, jobs, company settings); users and roles; demo-data loader: **Implemented · Static**.

## Not built (by decision or not in the original)
File uploads (documents are links) · payment gateway (M-Pesa/card) · two-factor sign-in · image display for cover/photo URLs (the original did not render them either) · server-side session invalidation after password reset.

## Open items for the owner
Placeholder content (team, testimonials, case-study clients) · unverified home statistics · company email `daylightcompany13@gmail.co` (possible typo) · SMTP provider · owner confirmation of decision D1 (native PHP) · screenshots of the original at 375/768/1280 px.

## Next task (for the next session)
Follow `COMPUTER_SETUP_CHECKLIST.md` §2–3 first (`php -l`, unit tests, migrations, integration tests), fix what they find, then §4–5 (run the site, visual comparison).

## Session 4 additions — what is still outstanding
- Run `php -l`, `tests/unit.php` and `tests/integration.php` (they now also cover the assistant endpoint, legal pages, FAQ page and retention).
- Legal: owner decisions and advocate review (`LEGAL_REVIEW.md`); confirm the facts the policy states.
- Assistant: provide an API key only if AI mode is wanted (then test it live); decide whether to log chats.
- Content: review the 37 new FAQ answers in Admin → FAQs; self-host the fonts (privacy).
