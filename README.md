# Daylight — PHP / MySQL edition

Rebuild of the Daylight website (originally TanStack Start + React + Supabase) as server-rendered PHP with MySQL/MariaDB.
No React, no Node and no Composer packages are needed at runtime. The original React project is untouched and still lives in its own repository.

> **Status: implemented, not yet run.** The code was written in an environment with no PHP or MySQL, so nothing here has been executed
> against a real server. See `TEST_REPORT.md` for exactly what has and has not been checked, and `COMPUTER_SETUP_CHECKLIST.md`
> for the first things to do when you have a computer.

## Documentation

| Read this | For |
|---|---|
| `DOCUMENTATION.md` | **everything that was built**, how it works, how to run and extend it |
| `LEGAL_REVIEW.md` | what a lawyer must check before the privacy policy and terms are relied on |
| `SECURITY_REVIEW.md`, `TEST_REPORT.md` | what was checked and what was not |
| `COMPUTER_SETUP_CHECKLIST.md` | the first things to do when you have a computer |
| `MIGRATION_PLAN.md`, `FEATURE_CHECKLIST.md`, `SITEMAP.md` | decisions, status of every feature, site structure |

## What is included

- **Public site** — same URLs and copy as the original: `/`, `/services`, `/services/{slug}`, `/work`, `/work/{slug}`, `/products`, `/industries`,
  `/technologies`, `/about`, `/blog`, `/blog/{slug}`, `/careers`, `/contact`, `/quote`, `/faq`, `/privacy`, `/terms`.
- **Forms** (contact, quote, job application) — server validation, CSRF, honeypot, DB rate limiting, saved to the database, staff email notification (when SMTP is configured).
- **Accounts** — register, sign in/out, password reset and optional email confirmation (both need working SMTP).
- **Client portal** (`/portal`) — projects with progress, milestones, shared documents, project messages, support tickets, invoices and payments. Clients only see records linked to their own account.
- **Admin** (`/admin`) — dashboard, inbox (contact messages, quote requests, job applications), clients, projects, milestones, documents, tickets, invoices (auto totals), payments, all website content, company settings, users and roles.
- **Website chat assistant** — floating "Ask Daylight" widget on the public site and client portal. Default FAQ mode answers from the site's own content on your server; optional AI mode (off by default, needs an API key). See `DOCUMENTATION.md` §9.
- **Privacy policy and Terms of service** — detailed, structured, with contents lists and anchors (Kenya Data Protection Act 2019, GDPR and others). **Drafts: read `LEGAL_REVIEW.md` and have a Kenyan advocate review them.**
- **Client FAQ** — 42 questions in 8 categories with search (5 original + 37 new).
- **Data retention tool** — `php bin/purge.php` or a super-admin button deletes records older than the periods promised in the privacy policy.
- **Search engines** — `/sitemap.xml` (generated from the database, published pages only) and `/robots.txt`; see `SITEMAP.md`.
- **Setup page** (`/setup`) for hosts without SSH — creates tables, loads the original content, creates the first super admin. Off unless `SETUP_TOKEN` is set.

## Requirements

- PHP **8.0+** (8.1+ recommended) with `pdo_mysql`, `mbstring`, `json`, `openssl`
- MySQL 5.7+/8 or MariaDB 10.3+ (JSON columns, utf8mb4)
- Apache with `mod_rewrite` (an `.htaccess` is provided), or nginx with an equivalent `try_files` rule
- Writable folders: `storage/logs`, `storage/sessions`

## Run locally

```bash
cp .env.example .env            # then edit DB_* and set APP_KEY
php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"   # use the output as APP_KEY
php bin/migrate.php --seed-content                  # tables + the original site content
php -S localhost:8000 -t public tools/dev-router.php
```

Create the first admin: set `SETUP_TOKEN` to a random 32+ character string in `.env`, open <http://localhost:8000/setup>, then remove the token.
(With SSH/CLI you can also insert a user and a `super_admin` row in `user_roles` by hand.)

Search engines: set `APP_URL`, then open `/sitemap.xml`; submit it in Google Search Console.

Chat assistant: it works out of the box in FAQ mode. To turn on AI mode set `AI_ASSISTANT_ENABLED=true` and `ANTHROPIC_API_KEY` (see `.env.example`); to hide the assistant set `ASSISTANT_ENABLED=false`.

Optional dummy data for testing the portal: set `ALLOW_DEMO_DATA=true`, sign in as super admin and use **Dashboard → Demo data**. Everything is labelled `[DEMO]` and can be removed again.

## Configuration

Values come from real environment variables first, then `.env`, then `config.local.php` (for hosts that cannot create dotfiles). See `.env.example` for every key. Key ones:

| Key | Purpose |
|---|---|
| `APP_URL` | Site origin only (`https://example.com`), used in emails. Never taken from the Host header. |
| `APP_BASE_PATH` | Only if installed in a sub-folder (`/daylight`). |
| `APP_KEY` | Random secret used to salt rate-limit hashes. |
| `APP_FORCE_HTTPS` | Turn on **after** HTTPS works: redirect, HSTS, secure cookies. |
| `DB_*` | Database connection. Use a least-privilege DB user. |
| `MAIL_ENABLED`, `SMTP_*`, `MAIL_FROM`, `MAIL_NOTIFY_TO` | Email. Keep `MAIL_ENABLED=false` until delivery is tested. |
| `SETUP_TOKEN` | Enables `/setup`. Remove after setup. |

## Deploy to InfinityFree (first target) — *unverified, follow with care*

InfinityFree has no SSH/Composer, so the app is designed to be uploaded by FTP and set up in the browser.

1. In the hosting control panel create a MySQL database; note the host, database name, user and password it shows.
2. Upload **everything** in this folder (including the hidden `.htaccess` files) into the site's `htdocs` folder. The root `.htaccess` sends requests to `public/` and blocks `app/`, `config/`, `database/`, `storage/`, `tests/`, `tools/`, `bin/`.
3. Create `.env` (or `config.local.php`) from the examples with the DB details, `APP_URL`, a fresh `APP_KEY`, and a random `SETUP_TOKEN`.
4. Open `https://your-site/setup`, run **database setup** (tick "load the original site content"), then create the super admin.
5. Delete `SETUP_TOKEN` from your configuration. Confirm `https://your-site/setup` now shows 404.
6. Sign in at `/auth` and open `/admin`. Replace the placeholder team, testimonials and case-study clients with real information.
7. When HTTPS is confirmed working, set `APP_FORCE_HTTPS=true`.

Alternative to step 4: import `database/migrations/*.sql` in order, then `database/seeds/content.sql`, using phpMyAdmin (choose **utf8mb4**).

Email on free hosting: PHP `mail()` is reported as blocked, and whether outbound SMTP is allowed has not been checked. Until a provider is configured and a test email arrives, submissions are still saved and show in the admin inbox; the dashboard shows how many emails were skipped or failed.

## Deploy elsewhere (VPS or paid shared hosting)

Point the web root at `public/` (delete the root `.htaccess`), keep everything else outside the web root, put real environment variables in the server configuration, run `php bin/migrate.php`, and set `APP_FORCE_HTTPS=true`. For nginx use `try_files $uri /index.php?$query_string;` and deny `/.env`, `/storage`, `/app`.

## Tests and checks

```bash
php tests/unit.php                       # no database needed
TEST_DB_HOST=127.0.0.1 TEST_DB_NAME=daylight_test TEST_DB_USER=root TEST_DB_PASS= php tests/integration.php   # DROPS all tables in that DB
python3 tools/lint.py                    # best-effort static checks (not a replacement for `php -l`)
python3 tools/lint_deep.py               # missing terminators, views vs. controller data
python3 tools/security_audit.py          # attacker's checklist over the code (see SECURITY_REVIEW.md)
python3 tools/schema_check.py            # schema vs admin config vs SQL column lists
python3 tools/retriever_check.py         # quality of the assistant's answers on the real content
python3 tools/gen_docs.py                # regenerate DOCUMENTATION.md tables after editing tools/documentation.template.md
node tools/browser/assistant.js          # widget, FAQ search and legal-page checks (after preview_static.py)
# CSS/JS in a real browser against an approximate rendering (needs Node + `npm i playwright`):
python3 tools/preview_static.py /tmp/preview && node tools/browser/shoot.js && node tools/browser/interact.js && node tools/browser/theme.js
for f in $(find app public tests bin tools config -name '*.php'); do php -l "$f"; done
```

## Layout

```
public/            document root: index.php (front controller), css/app.css, js/
app/Core/          config, request/response, router, db (PDO), session, csrf, auth, validator, mailer, migrator
app/Controllers/   public pages, forms, auth, portal, admin (generic CRUD driven by config/resources.php), users, setup, sitemap/robots
app/Admin/         resource registry, hooks (invoice totals, slugs…), demo-data seeder
app/Views/         PHP templates (layouts, partials, pages, auth, portal, admin)
config/            resources.php (admin screens), .env.example lives in the project root
database/          migrations/*.sql, seeds/content.sql (original site content)
bin/migrate.php    CLI migrator
bin/purge.php      retention clean-up (CLI)
tests/, tools/     unit + integration tests, static checker, dev router
```

## Security notes

Prepared statements everywhere (identifiers come from fixed config), `password_hash`, CSRF on every POST, output escaping in all templates,
session cookie `HttpOnly` + `SameSite=Lax` (+ `Secure` over HTTPS), session ID regenerated on login, rate limits on forms/login/reset,
honeypots, strict CSP (no inline scripts or styles), `X-Frame-Options`, `nosniff`, server-side role checks on every admin/portal route,
generic error page (details only in `storage/logs`), `no-store` on private pages. Password-reset responses do not reveal whether an account exists. Full review: `SECURITY_REVIEW.md`.
Not yet implemented: file uploads (documents are links), two-factor sign-in, account-lockout emails, server-side session invalidation on password reset.

## Known limitations

See `FEATURE_CHECKLIST.md` and `MIGRATION_PLAN.md` (deviations from the original are listed there).
#   D a y l i g h t 2 . 0  
 