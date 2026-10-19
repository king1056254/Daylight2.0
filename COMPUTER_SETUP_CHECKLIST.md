# COMPUTER_SETUP_CHECKLIST.md

Everything below needs a computer (or a cloud PHP environment). **Nothing here has been done yet.** Tick a box only after you have actually seen it work.

## 0. Get the code
- [ ] Download `daylight-php.zip`, unzip it. It contains a git repo on branch `php-migration` with the commit history.
- [ ] Add it to your GitHub repo **on a new branch** (do not touch the React branch). From the unzipped folder: `git remote add origin https://github.com/king1056254/exact-screenshot.git` then `git push origin php-migration`.
- [ ] Copy `public/favicon.ico` from the original React repo into `public/` (I could not copy the binary file).

## 1. PHP environment
- [ ] Install PHP 8.1+ with `pdo_mysql`, `mbstring`, `openssl` (XAMPP is the simplest on Windows) and MariaDB/MySQL (XAMPP includes it). Check: `php -v`, `php -m`.
- [ ] Create an empty database `daylight` and a second, empty scratch database `daylight_test`.

## 2. Syntax and unit checks (first, before anything else)
- [ ] `php -l` on every PHP file (command in README). Fix anything it reports. **Expect a few typos: this code has never been run.**
- [ ] `php tests/unit.php` → all pass.
- [ ] `python3 tools/lint.py` → no problems (optional, needs Python).

## 3. Database
- [ ] Copy `.env.example` to `.env`; set `DB_*`, `APP_KEY` (generate with the command in README).
- [ ] `php bin/migrate.php --seed-content` runs without errors on a clean database.
- [ ] Run migrations a second time → "Nothing to migrate".
- [ ] Spot-check in phpMyAdmin: 28 tables exist, `services` has 8 rows, `faqs` 5, `job_openings` 3.
- [ ] `TEST_DB_NAME=daylight_test … php tests/integration.php` → all pass (this wipes the test DB each run). Record failures in `TEST_REPORT.md`.

## 4. Run the site and look at it
- [ ] `php -S localhost:8000 -t public tools/dev-router.php`
- [ ] Set `SETUP_TOKEN`, open `/setup`, create a super admin, then remove the token.
- [ ] Click through every page on the list in the README; check the browser console and Network tab for errors/404s (favicon until copied).
- [ ] Submit contact, quote and job-application forms: valid, invalid, and double-submit. Confirm rows appear in `/admin`.
- [ ] Register a client account, sign in/out; sign in as admin; try `/admin` as the client (must be blocked).
- [ ] Enable `ALLOW_DEMO_DATA=true`, load demo data, sign in as the demo client, check projects/milestones/messages/tickets/invoices; try opening another client's URL (must 404).
- [ ] Create an invoice with items and a payment; totals and "Paid" status update.

## 5. Visual comparison with the original
- [ ] Capture screenshots of the original preview and the PHP site at **375 px, 768 px, 1280 px** for each public page, light and dark theme.
- [ ] Compare spacing, fonts (Space Grotesk / IBM Plex), colours, hero, cards, header, mobile menu, footer, FAQ accordion, hover states.
- [ ] Record each difference (page, viewport, what differs) in `TEST_REPORT.md`; fix or document.

## 6. Email (needs SMTP details — none provided yet)
- [ ] Choose an SMTP provider/account; put values in `.env` (`SMTP_*`, `MAIL_FROM`, `MAIL_NOTIFY_TO`), set `MAIL_ENABLED=true`.
- [ ] Submit the contact form → a real email arrives at `MAIL_NOTIFY_TO` (check spam). Check `email_outbox` status = `sent`.
- [ ] Test password reset and (optionally) `REQUIRE_EMAIL_VERIFICATION=true` end to end.
- [ ] Test wrong credentials/blocked port → `email_outbox` shows `failed` and the form still saves.

## 7. Security checks (see SECURITY_REVIEW.md)
- [ ] `python3 tools/security_audit.py` is clean on the code you actually deploy.
- [ ] `php tests/integration.php` passes, including the "security:" tests (CSRF on every POST, SQL-injection strings, stored XSS, mass assignment, access-control sweep, setup page, no-store).
- [ ] Run OWASP ZAP baseline (or similar) against the local site; try `sqlmap` on the login, contact, quote forms and admin search (local test database only).
- [ ] Try to reach another client's project/invoice by changing the id in the URL while signed in as a different client (must 404).
- [ ] Open `/sitemap.xml` and `/robots.txt` (set `APP_URL` first); check private areas are absent/disallowed.
- [ ] Try a `<script>` payload in every form field and in admin fields: it must display as text.
- [ ] POST without CSRF token (e.g. `curl`) → rejected.
- [ ] Confirm `/.env`, `/app/`, `/storage/`, `/database/` return 403/404 on the deployed host.
- [ ] Check response headers (CSP, X-Frame-Options, nosniff; HSTS once HTTPS is forced).
- [ ] Confirm logs contain no passwords/tokens; confirm errors show the generic page with `APP_DEBUG=false`.

## 8. InfinityFree deployment (after everything above works locally)
- [ ] Confirm the host's PHP version is 8.0+ and `pdo_mysql` is available (control panel / `phpinfo()` page, then delete it).
- [ ] Upload by FTP, including hidden `.htaccess` files; confirm `.htaccess` rewriting works (clean URLs load; `/app/` is blocked).
- [ ] Run `/setup`, remove `SETUP_TOKEN`, confirm `/setup` is 404.
- [ ] Check HTTPS; then set `APP_FORCE_HTTPS=true`.
- [ ] Check that sessions persist (sign in, navigate) and `storage/` is writable.
- [ ] Check that the app sees each visitor's real IP (not a shared proxy address). Rate limits are per IP: if every visitor shares one address they will lock each other out. Only if the host's proxy sets `X-Forwarded-For`, set `TRUST_PROXY_HEADERS=true`.
- [ ] Check whether outbound SMTP works from the host (step 6 again on the live host). If not, choose another host or an HTTP email API (would need a small code change).
- [ ] Set up backups (database export schedule) and write down the rollback plan **before** pointing the real domain at it.

- [ ] Submit `https://your-domain/sitemap.xml` to Google Search Console and Bing Webmaster Tools.

## 9. Content and business items to confirm with the owner
- [ ] Replace placeholder data (team, testimonials, case-study clients) with real information.
- [ ] Confirm the home-page statistics (40+ systems, 12 industries, 3 SaaS products, 98% retention) are true — copied from the original, unverified.
- [ ] Confirm the company email: the original has `daylightcompany13@gmail.co` (possible typo for `.com`); change it in Admin → Company settings.
- [ ] Have counsel review Privacy and Terms (copied unchanged from the original).

## Added during development
- [ ] Decide whether file uploads for project documents are needed (currently links only).
- [ ] Decide whether M-Pesa / card payments should be integrated (payments are recorded manually).
