<?php
declare(strict_types=1);

/*
 * Static knowledge for the website assistant. Only facts that are already true of the Daylight site and portal.
 * {{email}}, {{company}} and {{location}} are filled from Admin > Company settings.
 * Do not add prices, timelines or promises here that are not already published elsewhere on the site.
 */
return [
    ['id' => 'process', 'title' => 'How a Daylight project works', 'url' => '/about', 'keywords' => 'process steps discovery scope quote delivery demo cycle how we work',
     'text' => 'We start with discovery: we map the process, the people and the data. Then we agree a fixed scope and quote. We deliver in two-week cycles with a demo at the end of each one, and every client gets a portal where they can follow progress, milestones, documents and invoices in one place.'],
    ['id' => 'contact', 'title' => 'How to contact {{company}}', 'url' => '/contact', 'keywords' => 'contact email phone address location office reach talk human person team',
     'text' => 'You can use the contact form on the Contact page, or email {{email}}. We are based in {{location}} and reply to messages within one working day. To start a project, use the Request a quote page.'],
    ['id' => 'quote-how', 'title' => 'How to request a quote', 'url' => '/quote', 'keywords' => 'quote proposal estimate start project begin',
     'text' => 'Use the Request a quote page: tell us your name, email, the type of project, your budget range and timeline, and describe the project. We review requests and reply within two working days.'],
    ['id' => 'portal-access', 'title' => 'How to get access to the client portal', 'url' => '/auth', 'keywords' => 'portal account register sign up login access client',
     'text' => 'Create an account from the Sign in page (choose Create an account). After that, Daylight links your account to your company. Until it is linked, the portal tells you your account is not linked yet; contact us from the email you registered with and we will link it. Once linked you will see your projects, milestones, shared documents, messages, support tickets and invoices.'],
    ['id' => 'portal-invoices', 'title' => 'Where to find invoices and payments in the portal', 'url' => '/portal/invoices', 'keywords' => 'invoice payment balance bill portal receipt',
     'text' => 'Open Invoices in the client portal. Each issued invoice shows its line items, tax, total, the payments recorded against it and the balance due. Invoices that are still drafts are not shown to clients.'],
    ['id' => 'portal-projects', 'title' => 'Following project progress in the portal', 'url' => '/portal/projects', 'keywords' => 'project progress milestone document message portal status',
     'text' => 'Open Projects in the client portal and choose a project to see its progress, milestones, the documents Daylight has shared with you, and the message thread with the team. You can send the team a message from the same page.'],
    ['id' => 'portal-support', 'title' => 'How to raise a support request in the portal', 'url' => '/portal/tickets', 'keywords' => 'support ticket help bug problem issue report',
     'text' => 'Open Support in the client portal and create a new ticket: give a subject, choose a priority (Low, Normal or High), optionally pick the related project, and describe the issue. You can see the status of your tickets on the same page.'],
    ['id' => 'portal-password', 'title' => 'Resetting a forgotten password', 'url' => '/auth/forgot', 'keywords' => 'password forgot reset locked sign in',
     'text' => 'Choose Forgot your password on the Sign in page and enter your email. If email delivery is available, we send a reset link that works once and expires after one hour. If you do not receive it, contact us.'],
    ['id' => 'assistant-about', 'title' => 'About this assistant', 'url' => '/privacy#assistant', 'keywords' => 'assistant chatbot bot ai human robot automated who are you',
     'text' => 'I am an automated assistant, not a person. I answer from the Daylight website: the FAQs, services, products, case studies and portal help. I cannot see anyone\'s private project or invoice data and I cannot make quotes or agree anything on behalf of the team. For quotes, contracts and account-specific questions, please contact the team.'],
    ['id' => 'privacy-summary', 'title' => 'Privacy and personal data', 'url' => '/privacy', 'keywords' => 'privacy data protection personal information gdpr delete rights cookies',
     'text' => 'Our Privacy policy explains what personal data we collect, why, how long we keep it, who we share it with, and your rights under the Data Protection Act, 2019 of Kenya and other applicable laws. To access, correct or delete your data, email {{email}}.'],
    ['id' => 'terms-summary', 'title' => 'Terms of service and legal terms', 'url' => '/terms', 'keywords' => 'terms conditions legal contract liability ownership ip law dispute',
     'text' => 'Our Terms of service cover quotes and payment, ownership of work, confidentiality, client portal use, support, liability, and governing law (Kenya). Your written proposal or contract takes priority over the general terms where they differ.'],
];
