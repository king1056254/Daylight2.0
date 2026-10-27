-- Extended client FAQs (idempotent: a question is only inserted if it does not exist yet).
-- Every answer restates facts already published in the original FAQ, About page, Terms or Privacy policy, or describes the portal and assistant as built.
-- Review and edit them in Admin > FAQs. Commercial details that are not published elsewhere are deliberately left to the written proposal or contract.
INSERT INTO faqs (id, question, answer, category, sort_order, published)
SELECT UUID(), 'How do I start a project with Daylight?', 'Use the Request a quote page or send us a message from the Contact page. We begin with discovery, where we map your process, people and data, and then send a written scope, timeline and fixed quote. Work starts once the written scope and the payment schedule are agreed.', 'Getting started', 100, 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM faqs WHERE question = 'How do I start a project with Daylight?');
INSERT INTO faqs (id, question, answer, category, sort_order, published)
SELECT UUID(), 'What should I prepare before asking for a quote?', 'A short description of the problem you want to solve, who will use the system, the tools or spreadsheets you use today, any integrations you need (for example M-Pesa), your timeline and an approximate budget range. The more detail you give, the more realistic our scope and price will be.', 'Getting started', 101, 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM faqs WHERE question = 'What should I prepare before asking for a quote?');
INSERT INTO faqs (id, question, answer, category, sort_order, published)
SELECT UUID(), 'What kinds of organisations do you work with?', 'We work with SMEs, schools, churches and other organisations, NGOs, startups, retailers and professional-services firms. See the Industries page for examples.', 'Getting started', 102, 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM faqs WHERE question = 'What kinds of organisations do you work with?');
INSERT INTO faqs (id, question, answer, category, sort_order, published)
SELECT UUID(), 'Do you only work with clients in Nairobi?', 'We are based in Nairobi, Kenya, and build systems for organisations across Kenya and East Africa. If you are elsewhere, contact us and we will tell you whether we can help.', 'Getting started', 103, 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM faqs WHERE question = 'Do you only work with clients in Nairobi?');
INSERT INTO faqs (id, question, answer, category, sort_order, published)
SELECT UUID(), 'What is discovery and why does it come first?', 'Discovery is where we map your process, the people involved and your data before writing code. It is the cheapest place to find out that something is wrong, which is why projects that skip it usually cost more. The result is the scope and fixed quote we send you.', 'Getting started', 104, 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM faqs WHERE question = 'What is discovery and why does it come first?');
INSERT INTO faqs (id, question, answer, category, sort_order, published)
SELECT UUID(), 'Can you replace or improve a system I already use?', 'Often yes. We can connect to your current tools through APIs, or migrate your data into a new system. Tell us what you use today when you request a quote.', 'Getting started', 105, 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM faqs WHERE question = 'Can you replace or improve a system I already use?');
INSERT INTO faqs (id, question, answer, category, sort_order, published)
SELECT UUID(), 'Is the quote fixed, and what if the scope changes?', 'We give a fixed quote after discovery. If you ask for work outside the agreed scope, that is a change request: we tell you the effect on cost and timeline first and start only once you agree in writing. An email or a portal message is enough.', 'Pricing', 106, 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM faqs WHERE question = 'Is the quote fixed, and what if the scope changes?');
INSERT INTO faqs (id, question, answer, category, sort_order, published)
SELECT UUID(), 'How long is a quote valid?', 'Quotes are valid for 30 days unless the quote says otherwise.', 'Pricing', 107, 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM faqs WHERE question = 'How long is a quote valid?');
INSERT INTO faqs (id, question, answer, category, sort_order, published)
SELECT UUID(), 'How do payments work?', 'The payment schedule and the accepted payment methods are agreed in writing before work begins. Invoices appear in your client portal once issued, together with the payments recorded against them and the balance due.', 'Pricing', 108, 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM faqs WHERE question = 'How do payments work?');
INSERT INTO faqs (id, question, answer, category, sort_order, published)
SELECT UUID(), 'Do your prices include tax?', 'Amounts are exclusive of taxes unless the quote says otherwise. Value Added Tax, where it applies, is shown separately on your invoice.', 'Pricing', 109, 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM faqs WHERE question = 'Do your prices include tax?');
INSERT INTO faqs (id, question, answer, category, sort_order, published)
SELECT UUID(), 'What happens during a project?', 'After the scope and quote are agreed we deliver in two-week cycles, with a demo at the end of each one. Between demos you can follow progress, milestones, documents and invoices in your client portal.', 'Projects', 110, 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM faqs WHERE question = 'What happens during a project?');
INSERT INTO faqs (id, question, answer, category, sort_order, published)
SELECT UUID(), 'How will I know how my project is going?', 'Your client portal shows each project’s progress, its milestones, the documents we have shared with you and a message thread with the team, and we demonstrate the work at the end of every cycle.', 'Projects', 111, 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM faqs WHERE question = 'How will I know how my project is going?');
INSERT INTO faqs (id, question, answer, category, sort_order, published)
SELECT UUID(), 'What do you need from me during the project?', 'Timely feedback and approvals, access to the people and information we need, and content you have the right to use. Delays on your side can move delivery dates.', 'Projects', 112, 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM faqs WHERE question = 'What do you need from me during the project?');
INSERT INTO faqs (id, question, answer, category, sort_order, published)
SELECT UUID(), 'Can I ask for changes while the project is running?', 'Yes. Send the request through the portal or by email. Clarifications within the agreed scope are part of the work; anything outside it is a change request that we price and agree with you first.', 'Projects', 113, 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM faqs WHERE question = 'Can I ask for changes while the project is running?');
INSERT INTO faqs (id, question, answer, category, sort_order, published)
SELECT UUID(), 'What happens when the project is finished?', 'We launch and hand over. On full payment you own the custom source code and assets we built for you, and we hand over the source code and infrastructure access. Many clients then choose a monthly maintenance plan.', 'Projects', 114, 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM faqs WHERE question = 'What happens when the project is finished?');
INSERT INTO faqs (id, question, answer, category, sort_order, published)
SELECT UUID(), 'How do I get access to the client portal?', 'Choose Create an account on the Sign in page. Daylight then links your account to your company. Until that happens the portal tells you your account is not linked yet; contact us from the email you registered with and we will link it.', 'Client portal', 115, 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM faqs WHERE question = 'How do I get access to the client portal?');
INSERT INTO faqs (id, question, answer, category, sort_order, published)
SELECT UUID(), 'What can I see in the client portal?', 'Your projects with progress and milestones, the documents we have shared with you, the message thread for each project, your support tickets, and your invoices with line items, payments and the balance due.', 'Client portal', 116, 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM faqs WHERE question = 'What can I see in the client portal?');
INSERT INTO faqs (id, question, answer, category, sort_order, published)
SELECT UUID(), 'Why can’t I see my project, document or invoice?', 'Common reasons: your account has not been linked to your company yet; you registered with a different email; the document has not been shared with you; or the invoice is still a draft (drafts are not shown to clients). Contact us and we will check.', 'Client portal', 117, 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM faqs WHERE question = 'Why can’t I see my project, document or invoice?');
INSERT INTO faqs (id, question, answer, category, sort_order, published)
SELECT UUID(), 'I forgot my password. What do I do?', 'Choose Forgot your password on the Sign in page and enter your email. If email delivery is available we send a reset link that works once and expires after one hour. If it does not arrive, contact us.', 'Client portal', 118, 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM faqs WHERE question = 'I forgot my password. What do I do?');
INSERT INTO faqs (id, question, answer, category, sort_order, published)
SELECT UUID(), 'How do I report a problem or ask for help?', 'Open Support in the client portal and create a ticket: give a subject, choose a priority (Low, Normal or High), optionally choose the project, and describe the issue. You can follow the status of your tickets on the same page. You can also contact us directly.', 'Client portal', 119, 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM faqs WHERE question = 'How do I report a problem or ask for help?');
INSERT INTO faqs (id, question, answer, category, sort_order, published)
SELECT UUID(), 'Can several people from my company use the portal?', 'Each portal account is personal, and each client record is currently linked to one account. If several colleagues need access, contact us so we can arrange it.', 'Client portal', 120, 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM faqs WHERE question = 'Can several people from my company use the portal?');
INSERT INTO faqs (id, question, answer, category, sort_order, published)
SELECT UUID(), 'What does monthly maintenance include?', 'Our monthly maintenance covers hosting, monitoring, backups, security updates and a set number of support hours. The details and fees are in your maintenance agreement.', 'Support', 121, 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM faqs WHERE question = 'What does monthly maintenance include?');
INSERT INTO faqs (id, question, answer, category, sort_order, published)
SELECT UUID(), 'How quickly will you respond to a request?', 'General enquiries sent through the contact form get a reply within one working day. Response times for support tickets are set out in your maintenance agreement.', 'Support', 122, 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM faqs WHERE question = 'How quickly will you respond to a request?');
INSERT INTO faqs (id, question, answer, category, sort_order, published)
SELECT UUID(), 'Do you back up my system?', 'Backups and monitoring are part of our monthly maintenance plan. Ask us which backup schedule applies to your system when you agree your maintenance plan.', 'Support', 123, 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM faqs WHERE question = 'Do you back up my system?');
INSERT INTO faqs (id, question, answer, category, sort_order, published)
SELECT UUID(), 'Who owns the software you build for me?', 'You do. On full payment, the custom source code and assets we created for you are yours, and we hand over source code and infrastructure access. Our reusable internal libraries and our own SaaS products remain ours and are licensed to you for use in your system.', 'Legal', 124, 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM faqs WHERE question = 'Who owns the software you build for me?');
INSERT INTO faqs (id, question, answer, category, sort_order, published)
SELECT UUID(), 'Will you publish my project as a case study?', 'Only with your permission. We name clients or show their projects publicly only after they agree.', 'Legal', 125, 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM faqs WHERE question = 'Will you publish my project as a case study?');
INSERT INTO faqs (id, question, answer, category, sort_order, published)
SELECT UUID(), 'Will you keep my business information confidential?', 'Yes. We treat your non-public business, technical and financial information as confidential and use it only for your project. If you want a separate non-disclosure agreement before sharing details, tell us.', 'Legal', 126, 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM faqs WHERE question = 'Will you keep my business information confidential?');
INSERT INTO faqs (id, question, answer, category, sort_order, published)
SELECT UUID(), 'Which law applies to our agreement?', 'The laws of Kenya. We first try to settle disputes through good-faith discussion; if that fails, the dispute may go to mediation or arbitration if both sides agree, and otherwise to the courts of Kenya. See our Terms of service for the details.', 'Legal', 127, 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM faqs WHERE question = 'Which law applies to our agreement?');
INSERT INTO faqs (id, question, answer, category, sort_order, published)
SELECT UUID(), 'What personal information do you collect through this website?', 'Only what you give us in forms and your account (such as name, email, phone, company and your message), the information in your client portal, and basic technical data needed to run and protect the site. Our Privacy policy lists each item, why we use it and how long we keep it.', 'Data protection', 128, 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM faqs WHERE question = 'What personal information do you collect through this website?');
INSERT INTO faqs (id, question, answer, category, sort_order, published)
SELECT UUID(), 'How can I see, correct or delete my personal data?', 'Email us at the address on the Contact page and tell us what you need. We may ask you to confirm your identity, and we respond as quickly as possible and within the time the law requires, no later than 30 days. You can also complain to the Office of the Data Protection Commissioner of Kenya.', 'Data protection', 129, 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM faqs WHERE question = 'How can I see, correct or delete my personal data?');
INSERT INTO faqs (id, question, answer, category, sort_order, published)
SELECT UUID(), 'Do you use cookies or trackers?', 'We use one essential session cookie to keep you signed in and protect forms, and we store your light or dark theme choice in your browser. We do not use advertising or analytics cookies.', 'Data protection', 130, 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM faqs WHERE question = 'Do you use cookies or trackers?');
INSERT INTO faqs (id, question, answer, category, sort_order, published)
SELECT UUID(), 'How is my data protected in the client portal?', 'Passwords are stored only as one-way hashes, each client can see only the records linked to their own company, forms are protected against forgery and automated abuse, and connections use HTTPS. See the Privacy policy for the full list of measures.', 'Data protection', 131, 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM faqs WHERE question = 'How is my data protected in the client portal?');
INSERT INTO faqs (id, question, answer, category, sort_order, published)
SELECT UUID(), 'Do you share my personal data or sell it?', 'We never sell personal data. We share it only with providers who help us run the service (such as hosting and email), advisers under confidentiality, payment providers if you pay through them, and authorities when the law requires. The Privacy policy lists the recipients.', 'Data protection', 132, 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM faqs WHERE question = 'Do you share my personal data or sell it?');
INSERT INTO faqs (id, question, answer, category, sort_order, published)
SELECT UUID(), 'Is the chat assistant a real person?', 'No. It is an automated assistant. By default it answers by matching your question to our FAQs and website content; when AI mode is switched on, it uses an AI service to write the reply. The widget shows which mode is active.', 'AI assistant', 133, 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM faqs WHERE question = 'Is the chat assistant a real person?');
INSERT INTO faqs (id, question, answer, category, sort_order, published)
SELECT UUID(), 'What happens to what I type into the assistant?', 'In FAQ mode your question is matched on our own server. In AI mode your question, the last few messages and short extracts from our public website content are sent to our AI provider to produce a reply. Please don’t type passwords, card numbers or ID numbers. The Privacy policy has the details.', 'AI assistant', 134, 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM faqs WHERE question = 'What happens to what I type into the assistant?');
INSERT INTO faqs (id, question, answer, category, sort_order, published)
SELECT UUID(), 'Can the assistant see my project, invoices or account?', 'No. It only knows what is on this website, so it cannot look up your projects, invoices or account. Use the client portal for those, or contact the team.', 'AI assistant', 135, 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM faqs WHERE question = 'Can the assistant see my project, invoices or account?');
INSERT INTO faqs (id, question, answer, category, sort_order, published)
SELECT UUID(), 'Can I rely on the assistant’s answers?', 'Use them as general guidance. The assistant can be wrong and it is not legal, financial or tax advice. Prices, timelines and contract terms must be confirmed in writing with our team.', 'AI assistant', 136, 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM faqs WHERE question = 'Can I rely on the assistant’s answers?');
