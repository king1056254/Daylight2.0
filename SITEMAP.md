# SITEMAP.md — structure of the Daylight website

The XML sitemap for search engines is generated automatically at **`/sitemap.xml`** (and `/robots.txt` points to it). This file documents the structure behind it.
The sitemap needs `APP_URL` (for example `https://your-domain.example`) in the configuration; without it `/sitemap.xml` answers 503 on purpose, because it never trusts the Host header for absolute URLs.

## Public pages (in `sitemap.xml`)
```
/                              Home                      (priority 1.0)
├── /services                  Services                  → /services/{slug}   one page per published service        (8 in the seed)
├── /work                      Case studies              → /work/{slug}       one page per published case study     (4 in the seed)
├── /products                  SaaS products
├── /industries                Industries
├── /technologies              Technologies
├── /about                     About
├── /blog                      Insights                  → /blog/{slug}       one page per published post           (3 in the seed)
├── /careers                   Careers (open roles + application forms; no separate job pages)
├── /contact                   Contact form
├── /quote                     Request a quote
├── /faq                       FAQ
├── /privacy                   Privacy policy
└── /terms                     Terms of service
```
14 fixed pages + one entry for every published service, case study and blog post (29 URLs with the original seed data).
Unpublished items are never listed. `lastmod` comes from each row's `updated_at`.

## Not in the sitemap (and disallowed in `robots.txt`)
```
/auth ... sign in, register, /auth/forgot, /auth/reset/{token}, /auth/verify/{token}
/portal ... client portal: /portal, /portal/projects, /portal/projects/{id}, /portal/invoices, /portal/invoices/{id}, /portal/tickets
/admin ... admin area: dashboard, inbox, clients, billing, website content, users, demo data
/setup ... one-time installer (404 unless SETUP_TOKEN is set)
```
These pages also send `noindex` and `Cache-Control: no-store`.

## After deployment
1. Open `https://your-domain/sitemap.xml` and `https://your-domain/robots.txt` and check they look right.
2. Submit the sitemap URL in Google Search Console and Bing Webmaster Tools.
3. Note: the sitemap and `robots.txt` are SEO helpers added by this migration; the original React site had neither. Delete the `SeoController` routes if you do not want them.
