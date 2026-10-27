# SECURITY_REVIEW.md

**Short answer: no vulnerabilities were found by the checks that could be run — but the application has never been executed, so this is a code review and scripted audit, not a penetration test, and it is not proof of security.**
A real attack against a running copy is still to be done (see "Still to do").

## How it was checked
- No PHP, web server or database exists in the build environment, so the page could not be attacked live.
- Instead: (1) a manual attacker-minded read of every entry point; (2) `tools/security_audit.py` — scripted checks for unescaped output, dangerous functions, raw superglobals, dynamic SQL, CSRF on every POST route, authorisation guards on every admin/portal/setup route, unchecked links, unsafe browser JS; (3) attack-simulation tests written into `tests/integration.php` (**written, not run**).
- The audit script itself was tested by injecting 5 deliberate vulnerabilities into a copy (stored XSS, raw `$_GET`, missing CSRF check, weak hash, missing guard): it reported 4; the fifth edit still contained a guard, so it was correctly not flagged.

## Results by attack class
| Attack | Defence in the code | Evidence |
|---|---|---|
| SQL injection | PDO prepared statements everywhere; identifiers only from fixed config/whitelists (`Db::ident`, `Content::TABLES`, `config/resources.php`); LIKE wildcards escaped; `IN (?,?)` lists built from server-side ids | audit: 11 dynamic-SQL spots reviewed by hand — all use trusted identifiers or placeholders; tests written |
| Stored / reflected XSS | every dynamic value goes through `e()`; helpers that return HTML escape internally; no `innerHTML`/`eval` in `app.js`; strict CSP (no inline scripts or styles); blog/case-study text is escaped, not rendered as HTML | audit: 0 unescaped echoes other than reviewed constants; tests written |
| `javascript:` links | document and product links must start with `http(s)://` before being shown; admin URL fields only accept http(s) | **finding fixed** (product link was unchecked) |
| CSRF | token in session, verified with `hash_equals` on all 17 POST routes; cookies `SameSite=Lax`; logout is POST-only | audit: all 17 routes checked; tests written |
| Broken access control / IDOR | every portal query is scoped to the signed-in user's clients; drafts and hidden documents are excluded in SQL; admin screens check role per resource (staff vs admin vs super admin); admins cannot edit admins or grant super_admin; nobody can edit their own roles; last super admin protected | audit: guard on every private route; sweep test over every admin screen written |
| Mass assignment | only fields declared in the admin config or the portal form are read; clients cannot set status/client/id; registration never assigns roles | tests written |
| Authentication | `password_hash`; dummy-hash comparison for unknown emails; rate limits per IP and per email; session id regenerated at login; deactivated users lose access on the next request; HttpOnly + SameSite (+ Secure on HTTPS) cookies | tests written |
| Password reset | 256-bit random token, only its SHA-256 stored, 1-hour expiry, single use, links built from `APP_URL` (not Host header), same response whether or not the account exists | tests written |
| Session fixation / theft | strict mode, cookies-only sessions, regenerate on login, destroy on logout; session files use the host's own folder (not the web root) unless `SESSION_PATH` is set | not tested live |
| Email header injection | CR/LF stripped from addresses and subjects; body base64-encoded; visitor email validated | tests written |
| Open redirect | only internal paths are ever redirected to | unit test written |
| Clickjacking / sniffing | `X-Frame-Options: DENY`, CSP `frame-ancestors 'none'`, `nosniff`, referrer and permissions policies | not tested live |
| Information disclosure | generic error page (details only in `storage/logs`), no stack traces, `Options -Indexes`, `.htaccess` blocks `app/ config/ database/ storage/ tests/ tools/ bin/` and dotfiles, setup page shows nothing without its token, private pages `no-store` | **findings fixed** (setup status, session location, caching); `.htaccess` untested |
| Spam / brute force | honeypots, per-IP form limit (8 per 15 min), login/reset/register limits, ≥24-char setup token with 0.7 s delay on a wrong guess | tests written |
| File upload / SSRF / deserialisation / command execution | none of these features exist (documents are links; no outbound fetches; no `unserialize`/`exec`/`eval`) | audit |

## Issues found during this review (all fixed in this version)
1. Product link from the database was used as `href` without checking its scheme.
2. `/setup` (when enabled) showed database/admin status to anyone; now shown only after a correct token.
3. Session files defaulted to `storage/sessions` inside the web root; now the host's session folder is used, with the old folder only as a fallback.
4. Private pages (portal, admin, sign-in, setup) had no explicit `Cache-Control: no-store`.
5. The HTTPS redirect used the client-supplied Host header; it now uses the host from `APP_URL` when configured.

## Known weaknesses / residual risks (not fixed)
- Registration reveals whether an email is already registered (409). Trade-off for usability; a generic message would need email delivery to work.
- No two-factor sign-in; no breached-password check; passwords 8–72 bytes.
- Existing sessions stay valid after a password reset (file sessions cannot be listed per user).
- Rate limits are per IP: behind a shared proxy they need `TRUST_PROXY_HEADERS` set correctly (see the setup checklist), otherwise visitors may block each other.
- The 500-error page uses inline styles that the CSP blocks, so it appears unstyled (still readable).
- Google Fonts are loaded from Google without integrity hashes.
- If the host ignores `.htaccess`, private folders inside the web root would be reachable. Prefer a host where the web root is `public/`.
- Admin/staff accounts are trusted: staff can link any user to a client record.

## Still to do (needs a running copy — see COMPUTER_SETUP_CHECKLIST.md §7)
1. Run `php tests/integration.php` (includes the attack simulations) and fix failures.
2. Run an automated scan against a local copy with test data: OWASP ZAP baseline scan, `nikto`, and `sqlmap` against the contact/quote/login forms and admin search.
3. Verify response headers, cookie flags and that `/.env`, `/app/`, `/storage/`, `/database/` are not reachable on the real host.
4. Review `storage/logs` for secrets after testing; test SMTP failure paths.
5. Have a person who did not write the code attempt privilege escalation with a client, staff and admin account.

## Addendum — chat assistant, legal pages, FAQ search, retention (session 4)
New attack surface: `POST /assistant/message` (JSON), the widget, optional calls to an AI provider, the FAQ search box, the retention button.

| Risk | Defence | Evidence |
|---|---|---|
| CSRF / cross-site use of the endpoint | same CSRF token as the forms (`419` otherwise); same-origin `fetch`; no CORS headers | audit (19 POST routes); tests written |
| Abuse and cost | 20 messages / 10 min / IP; 500-character questions; history capped at 6 turns × 800 characters; site-wide daily cap for AI replies; automatic fallback to the free FAQ mode | tests written |
| XSS through replies | replies are drawn with `textContent`; only links that start with a single `/` become anchors; JSON is encoded with `JSON_HEX_*`; CSP forbids inline scripts | **browser-tested** (an `<img onerror>` + `<script>` reply stays inert; `javascript:` and `//evil` links are dropped) |
| Prompt injection / data leakage | the AI sees only public site content and has no tools, no account data and no secrets; the system prompt tells it to ignore instructions to change the rules | by design; AI mode never tested live |
| Wrong or harmful advice | disclosure in the widget, the FAQ, the terms and the privacy policy; prompt forbids legal/financial advice and invented prices or terms | untested live |
| Privacy | FAQ mode sends nothing out; AI mode discloses the transfer; chat text is not stored unless `ASSISTANT_LOG_CHATS=true` (hashed session id, 90 days); the assistant cannot read account data | policy §6; tests written |
| API key exposure | key only in the server environment; never in markup (test written); not logged | test written |
| Outbound request abuse (SSRF) | the AI URL comes only from the owner's environment, must be HTTPS, redirects are not followed; no user-controlled URLs are fetched | code review |
| Retention button | super admin only, CSRF, confirmation dialog | tests written |
| Legal text rendering | content is escaped; only `**bold**`, internal links and `https://` links (with `rel="noopener noreferrer"`) are allowed | unit + browser tested |

Found and fixed while testing (not security-critical): duplicate HTML id between the widget and the privacy page; confidence rule that let an off-topic question through on a single generic word; policy tables that needed sideways scrolling on phones.
Residual: AI mode can still produce wrong text (it is advisory); the AI provider is a third party and a transfer outside Kenya; the assistant's matching is keyword-based and can miss or mis-match a question (it falls back politely when unsure).
