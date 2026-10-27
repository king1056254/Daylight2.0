<?php
declare(strict_types=1);

/*
 * Privacy policy content. {{company}}, {{email}} and {{location}} come from Admin > Company settings.
 * Block types: ['p', text] ['ul', [items]] ['ol', [items]] ['table', [head], [[cells]...]]. Inline: **bold**, [label](/internal) or [label](https://external).
 * DRAFT FOR LEGAL REVIEW: see LEGAL_REVIEW.md for the assumptions and the facts that must be true in operation.
 */
return [
    'title' => 'Privacy policy',
    'version' => '2.0',
    'effective' => '19 October 2026',
    'description' => 'How Daylight collects, uses, shares, keeps and protects personal data, and the rights you have under the Data Protection Act, 2019 of Kenya and other applicable laws.',
    'intro' => [
        "This policy explains how {{company}} (“we”, “us”, “our”) collects, uses, shares and protects personal data when you visit our website, create a client portal account, use our chat assistant, contact us, request a quote or apply for a job. We are based in {{location}}.",
        "We process personal data in line with the Data Protection Act, 2019 of Kenya and the regulations made under it. Where the law of another country also applies to you, for example the EU or UK General Data Protection Regulation, we respect those rights too (see section 14).",
        "Please read this policy together with our [Terms of service](/terms). If you have a question, write to us at {{email}}.",
    ],
    'sections' => [
        ['id' => 'who', 'title' => 'Who is responsible for your data', 'blocks' => [
            ['p', "{{company}} is the **data controller** for the personal data described in this policy: we decide why and how it is used. You can contact us at {{email}} or at our address in {{location}}."],
            ['p', "When we build or run a system for a client, the client is usually the controller of the personal data in that system and we act as a **data processor** on the client’s documented instructions. In that case the client’s own privacy notice applies, and our obligations are set out in our agreement with the client."],
            ['p', "Where the law requires it, we maintain our registration with the Office of the Data Protection Commissioner of Kenya (ODPC) as a data controller and data processor. Questions about data protection can be sent to {{email}}."],
        ]],
        ['id' => 'terms', 'title' => 'Key terms', 'blocks' => [
            ['ul', [
                "**Personal data** means any information relating to an identified or identifiable person, such as a name, email address or an online identifier.",
                "**Processing** means anything we do with personal data: collecting, storing, using, sharing or deleting it.",
                "**Data subject** means the person the data is about (you).",
                "**Sensitive personal data** is a special category under the Data Protection Act, for example data revealing health, race or ethnic origin, religious or philosophical beliefs, sex life or biometric data.",
            ]],
        ]],
        ['id' => 'collect', 'title' => 'What personal data we collect', 'blocks' => [
            ['p', "The table below shows what we collect, in which situation, and where it comes from."],
            ['table', ['Situation', 'Personal data', 'Source'], [
                ['Visiting the website', 'Technical data handled by our web server and hosting provider, such as IP address, browser type, pages requested and time. Our application itself stores only a salted, hashed form of your IP address, for up to 24 hours, to limit abuse (rate limiting).', 'Your device'],
                ['Contact form', 'Name, email, phone (optional), company (optional), subject and message', 'You'],
                ['Quote request', 'Name, email, phone, company, country, project type, budget range, timeline and project details', 'You'],
                ['Job application', 'Name, email, phone, a portfolio or CV link, cover letter text and the role applied for. We do not take file uploads; links you give may lead to documents hosted elsewhere.', 'You'],
                ['Account', 'Name, email, password (stored only as a one-way hash), role, sign-up date, and password-reset or email-confirmation tokens (stored hashed)', 'You, and our administrators when they create or link an account'],
                ['Client portal', 'Projects, milestones, shared document links, messages, support tickets, invoices and payment records linked to your company, and the messages you send', 'You and us'],
                ['Business records', 'Quotes, contracts, invoices, payment references and correspondence', 'You and us'],
                ['Chat assistant', 'The questions you type and the recent messages of that conversation. If chat logging is switched on, we also store the conversation text and a hashed session identifier.', 'You'],
                ['Email', 'Emails you send us and the notification emails our system sends (recipient, subject, content and delivery status)', 'You and us'],
            ]],
            ['p', "We do not knowingly collect sensitive personal data. Please do not include it in forms or in the chat. We never ask for your password, or for full payment card numbers, by email or chat."],
        ]],
        ['id' => 'use', 'title' => 'How and why we use personal data', 'blocks' => [
            ['p', "We only process personal data when we have a lawful basis under the Data Protection Act, 2019 (and, where it applies, Article 6 of the GDPR):"],
            ['table', ['Purpose', 'Examples', 'Lawful basis'], [
                ['Answering enquiries and preparing quotes', 'Replying to the contact form and quote requests', 'Taking steps at your request before a contract; our legitimate interest in running our business'],
                ['Providing and supporting our services and the client portal', 'Delivering projects, showing progress, handling support tickets and messages', 'Performance of a contract with you or your organisation'],
                ['Billing, accounting and tax', 'Issuing invoices, recording payments, keeping accounting records', 'Performance of a contract; compliance with legal obligations'],
                ['Account security and abuse prevention', 'Rate limiting, spam traps, sign-in protection, security logs', 'Our legitimate interest in keeping the service secure; legal obligations to protect personal data'],
                ['Recruitment', 'Reviewing applications and contacting applicants', 'Taking steps at your request; our legitimate interest in hiring'],
                ['Answering questions in the chat assistant', 'Matching your question to our website content, or generating a reply in AI mode', 'Your request; our legitimate interest in helping visitors and clients'],
                ['Improving the website and the assistant', 'Reviewing unanswered questions, only where chat logging is on', 'Our legitimate interest, with the data kept to the minimum'],
                ['Legal compliance and protecting our rights', 'Responding to lawful requests, handling disputes and claims', 'Legal obligation; legitimate interest; establishing, exercising or defending legal claims'],
            ]],
            ['p', "Where we rely on your consent you can withdraw it at any time; withdrawing does not affect processing that already took place. We do not make decisions about you based solely on automated processing that produce legal or similarly significant effects, and we do not sell personal data."],
        ]],
        ['id' => 'marketing', 'title' => 'Direct marketing', 'blocks' => [
            ['p', "We do not currently send marketing newsletters. If we start to, we will ask for your consent first (opt-in), tell you what you are signing up to, and include a simple way to unsubscribe, as required by the Data Protection Act, 2019 and the Data Protection (General) Regulations, 2021 on the commercial use of personal data. You can object to direct marketing at any time."],
        ]],
        ['id' => 'assistant', 'title' => 'The chat assistant', 'blocks' => [
            ['p', "Our website has a chat assistant that helps visitors and clients. It is an automated tool, not a person, and it works in one of two modes. The widget tells you which mode is active."],
            ['ul', [
                "**FAQ mode (default).** Your question is matched against the published content of this website (FAQs, services, products, case studies and portal help) on our own server. Nothing is sent to an outside AI provider.",
                "**AI mode (only when we switch it on).** Your question, the last few messages of the conversation and short extracts from our public website content are sent from our server to an AI provider (currently Anthropic) so that it can write a reply. The provider acts as our processor and the data is not sent to it for any other purpose of ours. This can involve a transfer outside Kenya (section 9).",
            ]],
            ['p', "The assistant cannot see your account, projects or invoices and cannot take actions. It can make mistakes and is not legal, financial or tax advice; please check anything important with our team."],
            ['p', "Chat text is kept in your browser only while the page is open. We store conversation text on our server only if chat logging is switched on, in which case it is used to improve the assistant, is stored with a hashed session identifier rather than your name, and is deleted after 90 days. Please do not type passwords, card numbers, ID numbers or other sensitive information into the chat."],
        ]],
        ['id' => 'cookies', 'title' => 'Cookies and similar technologies', 'blocks' => [
            ['table', ['Name', 'What it does', 'How long', 'Type'], [
                ['daylight_session (cookie)', 'Keeps you signed in and protects forms against forgery (CSRF token)', 'Until you close your browser', 'Strictly necessary, first party'],
                ['daylight-theme (browser storage)', 'Remembers your light or dark display choice', 'Until you clear your browser data', 'Functional, stored only on your device'],
            ]],
            ['p', "We do not use advertising, tracking or analytics cookies. If we add any, we will ask for your consent first and update this policy. Strictly necessary cookies do not require consent."],
            ['p', "Our pages load fonts from Google Fonts. When your browser requests them, Google receives your IP address and browser details. Please see [Google’s privacy policy](https://policies.google.com/privacy)."],
        ]],
        ['id' => 'sharing', 'title' => 'Who we share personal data with', 'blocks' => [
            ['ul', [
                "**Hosting and infrastructure providers** that run our servers and databases.",
                "**Email providers** that deliver the emails our system sends.",
                "**AI provider** (Anthropic), only when AI mode of the chat assistant is switched on (section 6).",
                "**Professional advisers** such as lawyers, accountants and auditors, under a duty of confidentiality.",
                "**Payment and mobile-money providers**, if you pay through them. They handle your payment details under their own terms and privacy notices.",
                "**Authorities and regulators**, courts and law enforcement, where the law requires or allows it.",
                "**A buyer or successor**, if our business is restructured or sold, who must continue to protect your data under this policy.",
            ]],
            ['p', "We require our processors to protect personal data and to use it only on our instructions. We do not sell personal data and we do not share it for advertising."],
        ]],
        ['id' => 'transfers', 'title' => 'Transfers outside Kenya', 'blocks' => [
            ['p', "We are based in Kenya, but some of our providers (for example hosting, email or AI services) may process data in other countries. We transfer personal data out of Kenya only where the Data Protection Act, 2019 allows it: for example where the recipient is bound by appropriate safeguards (such as data transfer agreements), where the destination offers adequate protection, or where you have consented or the transfer is necessary to perform a contract with you. Where the GDPR applies, we use an adequacy decision or the European Commission’s standard contractual clauses. Write to {{email}} if you would like to know more about the safeguards for a particular transfer."],
        ]],
        ['id' => 'retention', 'title' => 'How long we keep personal data', 'blocks' => [
            ['table', ['Data', 'How long'], [
                ['Contact and quote enquiries', 'Up to 2 years after our last contact with you'],
                ['Job applications', 'Up to 12 months after the recruitment decision, unless you ask us to keep your details for future roles, or ask us to delete them sooner'],
                ['Account and client portal data', 'While your account or project is active, and afterwards only as needed for the records below'],
                ['Project and financial records (contracts, invoices, payments)', '7 years, as required for accounting, tax and audit purposes'],
                ['Chat conversation text (only if logging is on)', 'Up to 90 days'],
                ['Hashed IP address used for rate limiting', 'Up to 24 hours'],
                ['Notification email logs', 'Up to 2 years'],
                ['Backups', 'For a limited period under our backup schedule, then overwritten'],
            ]],
            ['p', "When a retention period ends we delete or anonymise the data, unless we must keep it to meet a legal obligation or to deal with a legal claim."],
        ]],
        ['id' => 'security', 'title' => 'How we protect personal data', 'blocks' => [
            ['p', "We use technical and organisational measures that fit the risk, including:"],
            ['ul', [
                "access limited by role, and each client can only see the records linked to their own company;",
                "passwords stored only as one-way hashes, and reset and confirmation tokens stored hashed, single-use and time-limited;",
                "protection of forms against forgery and automated abuse (CSRF tokens, rate limiting and spam traps);",
                "encrypted connections (HTTPS) and browser security headers;",
                "error handling that does not reveal technical details to visitors, and logs that do not contain passwords;",
                "keeping the software and server configuration up to date.",
            ]],
            ['p', "No system is completely secure. If a personal data breach is likely to put your rights and freedoms at risk, we will notify the Office of the Data Protection Commissioner within 72 hours of becoming aware of it, and tell the people affected without unreasonable delay."],
        ]],
        ['id' => 'rights', 'title' => 'Your rights', 'blocks' => [
            ['p', "Under the Data Protection Act, 2019 you have the right to:"],
            ['table', ['Right', 'What it means'], [
                ['Be informed', 'To know how and why your personal data is used (this policy)'],
                ['Access', 'To ask for a copy of the personal data we hold about you'],
                ['Object', 'To object to processing, including direct marketing, in the circumstances the law allows'],
                ['Correct', 'To have inaccurate or incomplete data corrected'],
                ['Delete', 'To ask us to delete data we no longer need or have no lawful basis to keep'],
                ['Data portability', 'To receive data you gave us in a commonly used format, where the law applies'],
                ['Withdraw consent', 'To withdraw consent you gave, at any time'],
                ['Automated decisions', 'Not to be subject to a decision based solely on automated processing that has legal or similarly significant effects'],
            ]],
            ['p', "To use any of these rights, email {{email}}. We may ask you to confirm your identity (using as little extra information as possible) before we act. We respond as quickly as possible and within the time the law requires, and in any case no later than 30 days. There is no charge unless a request is clearly unfounded or excessive."],
            ['p', "If you are unhappy with how we handle your data, please tell us first so we can put it right. You also have the right to complain to the Office of the Data Protection Commissioner of Kenya ([odpc.go.ke](https://www.odpc.go.ke))."],
        ]],
        ['id' => 'children', 'title' => 'Children', 'blocks' => [
            ['p', "Our website and services are for businesses and organisations and are not directed at children under 18. We do not knowingly collect children’s personal data without the consent of a parent or guardian, as the Data Protection Act, 2019 and the Children Act, 2022 require. If you believe a child has given us personal data, contact us and we will delete it."],
        ]],
        ['id' => 'international', 'title' => 'If you are in the EEA, the UK, California or elsewhere', 'blocks' => [
            ['p', "**EU and UK.** If the GDPR or the UK GDPR applies to our processing of your data, our lawful bases are those in section 4. In addition to the rights above you may ask us to restrict processing, and you may complain to your local supervisory authority."],
            ['p', "**California.** We collect identifiers, contact details, commercial information (such as quotes and invoices) and limited internet activity data. We do not sell personal information or share it for cross-context behavioural advertising. California residents may ask us what we hold, and ask us to correct or delete it; we will not discriminate against you for using these rights."],
            ['p', "**Other countries.** We respect the data protection laws of the countries where our clients and visitors are located, to the extent they apply to us, for example in Uganda, Tanzania and Rwanda."],
        ]],
        ['id' => 'links', 'title' => 'Links to other websites', 'blocks' => [
            ['p', "Our website may link to other websites. We do not control them and are not responsible for their privacy practices. Please read their policies."],
        ]],
        ['id' => 'changes', 'title' => 'Changes to this policy', 'blocks' => [
            ['p', "We may update this policy as our services or the law change. The version number and date at the top show when it last changed. If we make a material change we will tell account holders, for example by email or a notice on the website, before it takes effect where that is practical."],
        ]],
        ['id' => 'contact', 'title' => 'Contact', 'blocks' => [
            ['p', "{{company}}, {{location}}. Email: {{email}}. You can also use our [contact form](/contact)."],
        ]],
    ],
];
