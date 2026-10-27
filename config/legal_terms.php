<?php
declare(strict_types=1);

/*
 * Terms of service content. {{company}}, {{email}} and {{location}} come from Admin > Company settings.
 * Block types: ['p', text] ['ul', [items]] ['ol', [items]] ['table', [head], [[cells]...]]. Inline: **bold**, [label](/internal) or [label](https://external).
 * DRAFT FOR LEGAL REVIEW: see LEGAL_REVIEW.md for the assumptions and the facts that must be true in operation.
 */
return [
    'title' => 'Terms of service',
    'version' => '2.0',
    'effective' => '19 October 2026',
    'description' => 'The terms that apply to using the Daylight website, client portal and chat assistant, and to our software development services.',
    'intro' => [
        "These terms apply to your use of the {{company}} website, client portal and chat assistant, and to the services we provide to clients. “We”, “us” and “our” mean {{company}} of {{location}}; “you” and “client” mean the person or organisation using our website or buying our services.",
        "If you buy services from us, your **written proposal or contract** (the “Agreement”) forms part of the deal. If the Agreement and these terms differ, the Agreement prevails for that project. By using the website or portal you accept these terms; if you do not accept them, please do not use them.",
        "Our [Privacy policy](/privacy) explains how we handle personal data. Questions about these terms: {{email}}.",
    ],
    'sections' => [
        ['id' => 'services', 'title' => 'Our services and how an engagement works', 'blocks' => [
            ['p', "We design, build, deploy and support custom business software, web and mobile applications, integrations, SaaS products and related services, as described on our website and in your Agreement."],
            ['ol', [
                "**Discovery.** We map your process, people and data.",
                "**Scope and quote.** We send a written scope, timeline and fixed quote.",
                "**Agreement.** Work begins once the written scope and the payment schedule are agreed.",
                "**Delivery.** We deliver in cycles and demonstrate progress at the end of each cycle; you can follow progress in the client portal.",
                "**Launch and handover.** On launch and full payment, ownership passes as set out in section 6.",
            ]],
            ['p', "Website content describes our services in general terms. It is not an offer capable of acceptance; an engagement exists only when we both agree an Agreement."],
        ]],
        ['id' => 'quotes', 'title' => 'Quotes, fees, invoices and taxes', 'blocks' => [
            ['ul', [
                "Quotes are estimates based on the scope described at the time and are valid for **30 days** unless they say otherwise.",
                "The payment schedule and accepted payment methods are set out in the Agreement. Invoices are issued from our system and appear in your client portal once issued.",
                "Amounts are stated in the currency shown on the quote or invoice and are **exclusive of taxes** unless stated otherwise. Value Added Tax and any other tax that applies is shown separately on the invoice.",
                "If you are required by law to withhold tax from a payment, you must remit it to the authority and give us the certificate; the amount we receive after any lawful withholding must equal the invoiced amount.",
                "If an invoice is not paid by its due date we may pause work, after giving you written notice, until it is paid. Interest or other late-payment charges apply only if the Agreement says so.",
                "Work outside the agreed scope is a **change request**. We will tell you the effect on cost and timeline, and we start the change only once you agree in writing (email or portal message is enough).",
            ]],
        ]],
        ['id' => 'client', 'title' => 'Your responsibilities', 'blocks' => [
            ['ul', [
                "Give us accurate information, timely feedback and approvals, and access to the people, systems and content we reasonably need. Delays on your side may move delivery dates.",
                "Make sure you have the right to use, and to give us, any content, data, logos, software or materials you provide, and that using them as you instruct does not break the law or anyone’s rights.",
                "Keep your account details confidential and tell us at once if you think an account has been compromised. You are responsible for activity under your account.",
                "Comply with the laws that apply to your business and to how you use the software we deliver, including data protection, consumer protection, tax and electronic-communications laws.",
            ]],
        ]],
        ['id' => 'delivery', 'title' => 'Delivery, acceptance and changes', 'blocks' => [
            ['p', "Dates are targets based on the scope and on timely input from you, not guaranteed deadlines, unless the Agreement says otherwise. At the end of each delivery cycle we demonstrate the work; you should tell us promptly, in writing, what does not match the agreed scope so that we can fix it. The Agreement may set a specific acceptance procedure; if it does, that procedure applies."],
        ]],
        ['id' => 'support', 'title' => 'Support and maintenance', 'blocks' => [
            ['p', "After launch, ongoing support is available as a monthly maintenance plan covering hosting, monitoring, backups, security updates and a set number of support hours. The details, response times and fees are those in your maintenance Agreement. Support requests can be raised through the client portal or by contacting us."],
        ]],
        ['id' => 'ip', 'title' => 'Intellectual property', 'blocks' => [
            ['ul', [
                "**Your work.** On full payment of the amounts due for a project, you own the custom source code and assets we create specifically for you, and we hand over the source code and infrastructure access.",
                "**Our materials.** Our reusable internal libraries, tools, templates and know-how, and our own SaaS products, remain ours. We license them to you, non-exclusively, to use as part of the system we deliver.",
                "**Third-party and open-source components** stay under their own licences, which apply to your use of them.",
                "**Your content.** You keep ownership of the content and data you give us. You give us a licence to use it only to provide the services to you.",
                "**Showing our work.** We will name you as a client, or show your project publicly (for example as a case study), only with your permission.",
                "Names, logos and the design of this website belong to {{company}} or its licensors and are protected by the Copyright Act, 2001, the Trade Marks Act and the Industrial Property Act, 2001. You may not copy or reuse them without our written permission.",
            ]],
        ]],
        ['id' => 'confidentiality', 'title' => 'Confidentiality', 'blocks' => [
            ['p', "Each of us will keep the other’s non-public business, technical and financial information confidential, use it only for the engagement, and share it only with people who need it and are bound by confidentiality. This does not apply to information that is public through no fault of ours, that we already knew or lawfully received from someone else, or that we must disclose by law or by order of a court or regulator. If you need a separate non-disclosure agreement before sharing details, tell us."],
        ]],
        ['id' => 'data', 'title' => 'Personal data', 'blocks' => [
            ['p', "We handle personal data under the Data Protection Act, 2019 and our [Privacy policy](/privacy). When we process personal data on your behalf in a system we build or run for you, you are the controller and we are your processor: we process it only on your documented instructions, keep it confidential, apply appropriate security measures, help you respond to data-subject requests, tell you without undue delay if we become aware of a breach, and delete or return it at the end of the engagement. If you need a separate written data processing agreement, we will agree one with you."],
        ]],
        ['id' => 'accounts', 'title' => 'Client portal accounts', 'blocks' => [
            ['ul', [
                "Portal accounts are personal. Do not share your password. We may suspend an account that is used in a way that breaks these terms or puts the portal or other clients at risk.",
                "Daylight links your account to your company’s records. The portal shows only the records linked to your account. Tell us if you see something that is not yours.",
                "Documents in the portal are provided as links that we choose to share with you. Information in the portal is for your use in connection with your project.",
            ]],
        ]],
        ['id' => 'acceptable', 'title' => 'Acceptable use of the website, portal and assistant', 'blocks' => [
            ['p', "You must not:"],
            ['ul', [
                "break the law or use our services to harm, harass, defraud or mislead anyone;",
                "try to gain unauthorised access to our systems, accounts or data, or to another person’s account, or test our security without our written permission. Unauthorised access and interference with computer systems are offences under the Computer Misuse and Cybercrimes Act, 2018;",
                "introduce malware, overload or disrupt the website, or bypass rate limits and other safeguards;",
                "scrape the website or collect personal data from it in bulk without our written permission;",
                "submit false or unlawful content in forms or messages, or send spam.",
            ]],
            ['p', "If you find a security problem, please tell us at {{email}} and give us a reasonable time to fix it before telling anyone else."],
        ]],
        ['id' => 'assistant', 'title' => 'The chat assistant', 'blocks' => [
            ['p', "The chat assistant is an automated tool that gives general information drawn from our website. It is not a person and it is not legal, financial, tax or professional advice. It may be wrong or out of date. It cannot give quotes, agree changes or bind us. Anything that matters, including prices, timelines and contract terms, must be confirmed in writing with our team. Do not enter sensitive personal data into it. How chats are handled is explained in the [Privacy policy](/privacy#assistant)."],
        ]],
        ['id' => 'third-parties', 'title' => 'Third-party services', 'blocks' => [
            ['p', "Our work often connects to services run by others, such as mobile-money and payment providers (for example Safaricom M-Pesa), hosting, email and SMS providers. Those services have their own terms, fees and availability. We are not responsible for their acts or omissions, but we will tell you about problems we become aware of that affect your system."],
        ]],
        ['id' => 'warranties', 'title' => 'Our standard of work and disclaimers', 'blocks' => [
            ['p', "We provide our services with reasonable skill and care. We do not promise that software or the website will be uninterrupted or free of every error; we will correct defects that we are responsible for as set out in the Agreement. Except as stated in these terms and the Agreement, and to the extent the law allows, we give no other warranties. Nothing in these terms limits any right you have under the Consumer Protection Act, 2012 or other laws that cannot be excluded."],
        ]],
        ['id' => 'liability', 'title' => 'Limitation of liability', 'blocks' => [
            ['p', "To the extent the law allows:"],
            ['ul', [
                "our total liability to you for any claim arising from a project is limited to the fees you have paid us for that project;",
                "we are not liable for indirect or consequential loss, or for loss of profit, revenue, goodwill or data that could not reasonably have been foreseen;",
                "we are not liable for loss caused by events outside our reasonable control, by your instructions or content, or by third-party services.",
            ]],
            ['p', "Nothing in these terms limits liability for death or personal injury caused by negligence, for fraud or fraudulent misrepresentation, or for anything else that the law does not allow to be limited."],
        ]],
        ['id' => 'indemnity', 'title' => 'Your indemnity', 'blocks' => [
            ['p', "You will compensate us for losses, costs and claims brought by third parties because of the content, data or materials you gave us, or because you used our deliverables in breach of the law or of these terms, as long as we tell you promptly, let you handle the claim, and cooperate reasonably."],
        ]],
        ['id' => 'term', 'title' => 'Term and termination', 'blocks' => [
            ['p', "Each project runs until completed or ended under the Agreement. Either of us may end an engagement as the Agreement provides, or if the other commits a serious breach and does not fix it within a reasonable time after written notice. On termination you pay for work done and costs incurred up to that date, and, once those amounts are paid, we hand over the completed work as described in section 6. Sections that by their nature should continue (including payment, intellectual property, confidentiality, liability and governing law) continue after termination."],
        ]],
        ['id' => 'law', 'title' => 'Compliance with laws', 'blocks' => [
            ['p', "Each of us will comply with the laws that apply to our dealings, including the Bribery Act, 2016 and the Proceeds of Crime and Anti-Money Laundering Act, 2009. We may refuse or end a transaction that would break the law."],
        ]],
        ['id' => 'force', 'title' => 'Events outside our control', 'blocks' => [
            ['p', "Neither of us is in breach for delay or failure caused by events beyond our reasonable control, such as power or internet outages, natural disasters, strikes, government action or failures of third-party networks. The affected party must tell the other promptly and take reasonable steps to limit the effect."],
        ]],
        ['id' => 'electronic', 'title' => 'Electronic communications and signatures', 'blocks' => [
            ['p', "We may communicate and contract electronically. You agree that emails, portal messages and electronic signatures are valid and enforceable to the extent permitted by the Kenya Information and Communications Act, 1998 and other applicable law. Notices to us go to {{email}}; notices to you go to the email address on your account or Agreement."],
        ]],
        ['id' => 'general', 'title' => 'General', 'blocks' => [
            ['ul', [
                "**Entire agreement.** These terms and the Agreement are the whole agreement about their subject matter.",
                "**Changes.** We may update these terms. The version number and date at the top show the current version; we will tell account holders before a material change takes effect where practical. Changes do not alter an Agreement already signed unless you agree.",
                "**Assignment and subcontracting.** You may not transfer your rights without our written consent. We may use subcontractors, and remain responsible for their work.",
                "**Severability.** If part of these terms is found unenforceable, the rest continues to apply.",
                "**No waiver.** Not enforcing a right straight away does not mean we give it up.",
                "**Third parties.** Only you and we have rights under these terms.",
            ]],
        ]],
        ['id' => 'disputes', 'title' => 'Governing law and disputes', 'blocks' => [
            ['p', "These terms and any dispute arising from them or from an engagement are governed by the laws of Kenya. We will first try to settle any dispute by good-faith discussion. If that does not work, the dispute may be referred to mediation or to arbitration under the Arbitration Act, 1995 if both of us agree, and otherwise to the courts of Kenya, which have jurisdiction. If you are a consumer, nothing here removes rights or remedies you have under the mandatory laws of the place where you live."],
        ]],
        ['id' => 'contact', 'title' => 'Contact', 'blocks' => [
            ['p', "{{company}}, {{location}}. Email: {{email}}. You can also use our [contact form](/contact)."],
        ]],
    ],
];
