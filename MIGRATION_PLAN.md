# MIGRATION_PLAN.md — Daylight: TanStack Start (React) → PHP / MySQL

Last updated: build session 1 (phone-only). Phase: **3–6 implemented, nothing executed yet** (see TEST_REPORT.md).

## 1. Source and how it was audited
- Source of truth: Lovable project `051f9612-34ea-4eaa-bcc5-2cc224c1bcac` ("Pixel Perfect Canvas"; repo `king1056254/exact-screenshot`). Owner confirmed this is the Daylight source.
- GitHub could not be fetched (blocks automated access) and the sandbox has no network, so the source was read read-only through the Lovable connector. The original was **not modified**.
- Read in full: all 18 route files, Header, Footer, Page, LeadForm, theme, useAuth, `styles.css`, button/input/textarea, `queries.ts`, both Supabase migrations, `package.json`.
- **Not read:** `.env` (may contain keys; not needed), `supabase/config.toml`, `src/integrations/*`, other shadcn UI files (accordion, label, sonner), `README.md`/`AGENTS.md`, `public/` assets (favicon is binary and could not be copied).
- The live preview was **not viewed**; no screenshots exist, so there is no visual baseline yet.
- Page copy was ported from the source text. Privacy, Terms and all page titles/descriptions were re-compared with the source by reading, after the port.

## 2. Original architecture (verified)
TanStack Start 1.168 / Router 1.170, React 19, Vite 8, Tailwind v4 + shadcn/Radix, lucide icons, Google Fonts (Space Grotesk, IBM Plex Sans, IBM Plex Mono). Data and auth through Supabase (Postgres, email+password auth, RLS). No custom server functions, no email sending, no payments, no file uploads. The portal and admin existed only as database tables; the `/auth` page said they were "being built".

## 3. Target architecture (decisions)
| # | Decision | Status |
|---|---|---|
| D1 | **Native PHP 8, small MVC (front controller, router, controllers, PDO, PHP templates), no Composer packages.** Reason: first host is InfinityFree (no SSH/Composer/Artisan, `mail()` reportedly blocked), so Laravel would need a pre-built `vendor/` uploaded by FTP and could not run migrations. The owner approved Laravel earlier, conditional on this plan; the plan's own fallback applies. Reversible: schema, views and content map to Laravel. | Revised, **needs owner confirmation** |
| D2 | **Hand-ported plain CSS** (`public/css/app.css`, 400 lines) using the original design tokens unchanged, instead of Tailwind build output. Reason: owner is phone-only (no build step possible) and the host needs no Node. Risk: pixel drift vs the original. | Changed from "Tailwind CLI", **needs visual comparison** |
| D3 | Client portal and admin UI **built** (owner-approved), scoped to the existing schema. | Implemented |
| D4 | No real data to migrate; dummy data via a clearly-labelled demo seeder. | Implemented |
| D5 | Host: InfinityFree first; portable (single `public/` front controller, config from env/.env/config.local.php, no host-specific code). | Implemented, unverified |

## 4. Database (MySQL/MariaDB, utf8mb4)
Migrations in `database/migrations/*.sql` (ordered, run once each by `bin/migrate.php` or the `/setup` page, or importable in phpMyAdmin). Seed `database/seeds/content.sql` = the original seed rows 1:1.
- 28 tables: 14 content/lead tables (same columns as original), 9 portal tables (same columns as original), plus `users`, `user_roles`, `auth_tokens`, `rate_limits`, `email_outbox`.
- Mapping: `uuid` → `CHAR(36)` (generated in PHP, `UUID()` in seeds); `text[]` → `JSON`; `timestamptz` → `TIMESTAMP` (UTC); `app_role` enum → `ENUM`; RLS → server-side role/ownership checks in PHP; `auth.users`+`profiles` → single `users` table; triggers → `ON UPDATE CURRENT_TIMESTAMP`.
- Seed structure was checked against the schema columns with an SQLite stand-in (counts and JSON validity). MySQL execution is **unverified**.

## 5. Original → PHP mapping
| Original | PHP |
|---|---|
| `routes/*.tsx` (18) | `app/routes.php` → `PageController`, `FormController`, `AuthController` (same URLs) |
| `queries.ts` (Supabase reads) | `App\Models\Content` (published filter + ordering) |
| `LeadForm` + client-side Supabase inserts | `partials/lead_form` + `FormController` (server validation, CSRF, honeypot, rate limit, DB insert, staff email, success only after insert) |
| Supabase Auth | `AuthController` + `App\Core\Auth` (sessions, `password_hash`, reset tokens, optional email confirmation) |
| Header/Footer/PageHero/Section/StatusPill/theme toggle | `Views/partials/*`, `public/js/app.js`, `public/js/theme-init.js` |
| Accordion (Radix) | `<details name="faq">` |
| Portal/admin tables | `PortalController`, `AdminController`, `CrudController` (+ `config/resources.php`), `UsersController` |

## 6. Intentional deviations from the original (all to be reviewed)
1. CSS is a hand port, not Tailwind output (D2). Tokens identical; component sizes ported from the utility classes; **not yet compared visually**.
2. Signed-in header button goes to `/portal` or `/admin` (original always `/auth`); the `/auth` page for a signed-in user shows an account card instead of "being built".
3. Missing slugs return HTTP 404 with the same message (original returned 200 with the message). Unknown URLs get a standalone 404 page like the original root 404.
4. Theme is applied before first paint (no light→dark flash); default dark unless stored/OS light — same rule as the original.
5. Dates are rendered server-side (`n/j/Y`) then localised in the browser with `toLocaleDateString()`, as the original did.
6. FAQ accordion has no open/close animation; it is exclusive via `details[name]` (needs a recent browser).
7. Registration signs the user in immediately unless `REQUIRE_EMAIL_VERIFICATION=true` (original relied on Supabase email confirmation).
8. Added (required by project security rules, not in the original): CSRF, honeypot, DB rate limiting, security headers/CSP, staff email notification, password reset.
9. Draft invoices are hidden from clients (the original schema's policy showed all statuses).
10. `profiles` merged into `users`. Registration assigns **no role** (same as the original trigger); clients are linked to a company by an admin (`clients.user_id`).
11. Added `/sitemap.xml` and `/robots.txt` (the original had neither) at the owner's request; remove the two routes if unwanted.
12. `favicon.ico` not copied (binary). Lead status lists (e.g. New / In progress / Replied / Closed) are my assumption; the original only defined the default.

## 7. Assumptions
PHP 8.0+ on the target host (unverified for InfinityFree); password policy 8–72 bytes (the original had min length 8); `APP_URL` is an origin only; the portal/admin scope is exactly what the original tables imply (projects, milestones, documents as links, messages, tickets, invoices with items and manual payments); no payment gateway, no uploads.

## 8. Risks
- **Nothing has been executed.** Expect typos and integration bugs until `php -l`, unit and integration tests are run (COMPUTER_SETUP_CHECKLIST.md §2–3).
- InfinityFree: PHP version, `.htaccess` per-directory rewrites, outbound SMTP, visitor-IP handling behind their proxy (rate limits are per IP), session storage — all unverified.
- Visual fidelity unverified (D2). MySQL-vs-MariaDB JSON behaviour unverified.
- Email is implemented but never delivered anywhere; no SMTP credentials exist.

## 9. Phases
1 Audit ✔ (partial coverage listed above) · 2 Frontend ✔ implemented · 3 Backend/DB ✔ implemented · 4 Integrations: email implemented, **blocked on SMTP** · 5 Security: implemented, **untested** · 6 Connect ✔ implemented · 7 Test/verify: **pending a PHP environment** · 8 Deploy docs ✔ drafted (README, COMPUTER_SETUP_CHECKLIST).
