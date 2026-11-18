# LEGAL_REVIEW.md — read before relying on the Privacy policy and Terms of service

**These documents are carefully structured drafts written by an AI assistant. They are not legal advice and have not been reviewed by a lawyer.** No document can be made "as legal as possible" without a qualified person checking it against your actual business. Please have a **Kenyan advocate** (ideally one who practises data protection and technology law) review `config/legal_privacy.php` and `config/legal_terms.php`, and keep this file as the checklist for that review.

Current version of both documents: **2.0, effective 19 October 2026** (change the `version` and `effective` fields in each file whenever the text changes).

## 1. Laws the drafts are built around
| Law | Used for |
|---|---|
| Constitution of Kenya, 2010 (right to privacy) | background principle |
| **Data Protection Act, 2019** (No. 24 of 2019) | principles, lawful bases, data-subject rights, security, breach notification (72 hours), cross-border transfers, children, direct marketing, complaints to the ODPC |
| Data Protection (General) Regulations, 2021 | commercial use of personal data / direct marketing, rights handling |
| Data Protection (Registration of Data Controllers and Data Processors) Regulations, 2021 | registration with the Office of the Data Protection Commissioner (ODPC) "where required" |
| Computer Misuse and Cybercrimes Act, 2018 | acceptable-use clause (unauthorised access) |
| Consumer Protection Act, 2012 | non-excludable consumer rights preserved in the disclaimer and disputes clauses |
| Kenya Information and Communications Act, 1998 | validity of electronic communications and signatures |
| Copyright Act, 2001; Industrial Property Act, 2001; Trade Marks Act | intellectual-property clause |
| Value Added Tax Act, 2013; Tax Procedures Act, 2015; Companies Act, 2015 | taxes on invoices; 7-year retention of accounting/tax records |
| Arbitration Act, 1995 | optional mediation/arbitration |
| Bribery Act, 2016; Proceeds of Crime and Anti-Money Laundering Act, 2009 | compliance clause |
| Children Act, 2022 | children's data |
| GDPR (EU 2016/679) and UK GDPR / Data Protection Act 2018 | "to the extent applicable" section, lawful bases, transfers (SCCs) |
| ePrivacy Directive 2002/58/EC | cookies (only strictly necessary cookies are used) |
| California CCPA/CPRA | short "California" paragraph |

Section numbers of statutes are deliberately **not** quoted, because they cannot be verified here. Ask the advocate to add precise citations if desired.

## 2. Facts the documents state that must be TRUE when the site is live
Tick each one; if one is not true, change the document or change the practice.

- [ ] The site is served over **HTTPS** (the policy says "encrypted connections (HTTPS)").
- [ ] The contact email in Admin → Company settings is correct and **someone reads it** (the policy promises responses to data requests within the legal time and no later than 30 days). The seed value `daylightcompany13@gmail.com` looks like a typo.
- [ ] **Registration with the ODPC** has been done or you have confirmed you are exempt. The policy only says "where the law requires it, we maintain our registration".
- [ ] **Company legal name and registered address**: the documents say "Daylight, Nairobi, Kenya" (from Company settings). Put the exact registered name (for example "Daylight Limited") in Admin → Company settings → Company name and consider adding the registered address to the contact sections.
- [ ] **Processor agreements** exist with the hosting provider and the email provider (and with Anthropic if AI mode is on) that bind them to use data only on your instructions.
- [ ] **Cross-border transfers**: hosting/email/AI providers may be outside Kenya. Confirm which safeguard you rely on (contractual clauses, adequacy, consent) and that you can show it.
- [ ] A **breach procedure** exists so that the ODPC can be notified within 72 hours.
- [ ] The **retention clean-up** is run (monthly): `php bin/purge.php` or the super-admin button. Project and financial records are kept 7 years.
- [ ] **Chat logging** stays **off** (`ASSISTANT_LOG_CHATS=false`) unless you want it; if on, the 90-day deletion applies.
- [ ] **AI mode** stays off unless a processor agreement is in place; if on, the policy section "The chat assistant" is accurate.
- [ ] **No analytics or advertising cookies** are added without updating the policy and adding consent.
- [ ] **Google Fonts** are loaded from Google (the policy discloses this). Under EU case law this can itself be an issue for EU visitors; the cleaner fix is to self-host the fonts (not done: no internet in the build environment).
- [ ] You really do keep software updated and use role-based access (statements in the security section).
- [ ] The statement "We will name you as a client or show your project publicly only with your permission" matches your practice (the seed case studies use placeholder client names).

## 3. Drafting choices that are mine, not from the original site — please confirm
The original privacy page stated only two retention periods (enquiries 2 years; project and financial records 7 years), a 30-day response time and general rights. The original terms covered quotes (30 days), ownership on full payment, portal accounts, liability limited to fees paid, and Kenyan governing law. Everything else below is new drafting:

| Topic | Draft says | Decision needed |
|---|---|---|
| Job-application retention | up to 12 months after the decision | confirm or change (also in `Retention::RULES`) |
| Assistant chat logs | off by default; 90 days if on | confirm |
| Notification email logs | 2 years | confirm |
| Change requests | priced and agreed in writing before work | confirm this is how you work |
| Non-payment | work may be paused after written notice; interest only if the Agreement says so | confirm |
| Taxes | amounts exclusive of tax; VAT shown separately; withholding handled by client | confirm VAT status |
| Acceptance of work | feedback in writing at each demo; Agreement may set a procedure | confirm |
| Liability | capped at fees paid for the project; usual exclusions; statutory carve-outs | confirm the cap with an advocate |
| Indemnity by client | for content/data supplied | confirm |
| Termination | per Agreement or serious breach with notice; pay for work done; handover after payment | confirm |
| Confidentiality & processor terms | included | confirm; offer a separate DPA/NDA if clients ask |
| Publishing clients/case studies | only with permission | confirm |
| Disputes | negotiation → optional mediation/arbitration (Arbitration Act 1995) → Kenyan courts | choose seat/rules if arbitration is wanted |
| Direct marketing | none now; opt-in only if introduced | confirm |
| Data-protection contact | the company email | consider naming a data protection officer |
| Support and maintenance | details in "maintenance Agreement" | have a template agreement ready |
| Intellectual property wording | as in original, extended to third-party/open-source components | confirm |

## 4. Questions to ask the advocate
1. Are we required to register with the ODPC, and has the registration been done correctly (fees, renewal)?
2. Is the cross-border transfer wording sufficient for hosting, email and (optionally) AI providers, and do we need data transfer agreements?
3. Are the retention periods right for each category (including tax and company-law records)?
4. Is the limitation-of-liability and indemnity wording enforceable for our client mix (businesses and consumers)?
5. Do we need a separate data processing agreement and a standard NDA template?
6. Are the electronic-signature and electronic-communications clauses sufficient for contracts concluded online?
7. Is the arbitration/mediation clause what you would recommend, and should the seat be Nairobi (for example under the Nairobi Centre for International Arbitration)?
8. Do any of our clients' sectors (schools, health, NGOs, finance) bring sector-specific obligations we should reference?
9. Is a cookie banner needed (we use only a strictly necessary session cookie and a theme preference)?
10. Should the documents also be provided in Kiswahili?

## 5. How the documents are kept consistent with the software
- A unit test fails if the retention periods written in the policy stop matching the retention tool, if a section ID is duplicated or a table row has the wrong width, or if a placeholder other than company/email/location appears.
- The FAQ answers about ownership, quote validity, cookies, data rights and the assistant restate the same facts as the policy and terms. If you change one, change the other (Admin → FAQs).
- The assistant links to `/privacy#assistant`; keep the section id `assistant` in `config/legal_privacy.php`.
