-- Original site content, ported 1:1 from the Supabase seed (20260908192417 migration).
-- NOTE: team members, testimonials and portfolio clients are PLACEHOLDERS from the original source.
-- Replace them with real data from the admin area. Import with utf8mb4 (phpMyAdmin: choose utf8mb4).
INSERT INTO company_settings (id, company_name, tagline, description, email, phone, address, linkedin_url)
VALUES (UUID(), 'Daylight','Software that runs the business','Daylight is a Kenyan software company building custom business software, web and mobile applications, and its own SaaS products.','daylightcompany13@gmail.co','+254 700 000 000','Nairobi, Kenya','https://linkedin.com');

INSERT INTO services (id, title, slug, summary, description, icon, features, sort_order) VALUES
(UUID(), 'Custom Business Software','custom-business-software','Systems built around how your business actually works.','We design and build operational systems — inventory, POS, HR, school management, membership, logistics — tailored to your workflows instead of forcing you into someone else''s.','Boxes','["Requirements & process mapping","Custom database design","Role-based access","Reporting dashboards"]',1),
(UUID(), 'Web Applications','web-applications','Fast, secure web platforms and company websites.','From marketing sites to complex portals, we build responsive web applications engineered for speed, search visibility and reliability.','Globe','["Company websites","Client portals","Admin dashboards","SEO & performance"]',2),
(UUID(), 'Mobile Applications','mobile-applications','Android and iOS apps that people actually use.','Native and cross-platform mobile apps with offline support, payments and clean interfaces.','Smartphone','["Android & iOS","Offline-first","Push notifications","App store deployment"]',3),
(UUID(), 'SaaS Development','saas-development','Turn a product idea into a subscription business.','Multi-tenant architecture, billing, onboarding and analytics — the full stack behind a software product.','Layers','["Multi-tenant architecture","Subscription billing","Usage analytics","Product onboarding"]',4),
(UUID(), 'AI & Automation','ai-automation','Remove the repetitive work from your operations.','Document processing, chat assistants, smart search and workflow automation wired into your existing systems.','Sparkles','["AI assistants","Document processing","Workflow automation","Smart search"]',5),
(UUID(), 'E-Commerce','e-commerce','Sell online with payments that work in Kenya.','Online stores with M-Pesa and card payments, inventory sync, delivery and order management.','ShoppingCart','["M-Pesa & card payments","Inventory sync","Order management","Delivery tracking"]',6),
(UUID(), 'API & Integrations','api-integrations','Make your systems talk to each other.','We connect payment providers, accounting tools, SMS gateways and third-party platforms into one coherent flow.','Plug','["Payment gateways","SMS & email gateways","Accounting sync","Third-party APIs"]',7),
(UUID(), 'Cloud, Deployment & Support','cloud-deployment-support','We stay after launch.','Deployment, monitoring, backups, security updates and ongoing support so your system keeps running.','Server','["Cloud deployment","Monitoring & backups","Security updates","Ongoing support"]',8);

INSERT INTO industries (id, name, slug, summary, icon, sort_order) VALUES
(UUID(), 'SMEs','smes','Operational systems for growing businesses.','Building2',1),
(UUID(), 'Schools','schools','Student, fee and academic management platforms.','GraduationCap',2),
(UUID(), 'Churches & Organizations','churches-organizations','Membership, giving and event systems.','Users',3),
(UUID(), 'NGOs','ngos','Programme, beneficiary and reporting tools.','HeartHandshake',4),
(UUID(), 'Startups','startups','MVPs and product engineering from zero to launch.','Rocket',5),
(UUID(), 'Retail','retail','POS, inventory and online storefronts.','Store',6),
(UUID(), 'Professional Services','professional-services','Client, billing and case management.','Briefcase',7);

INSERT INTO technologies (id, name, category, sort_order) VALUES
(UUID(), 'PHP','Backend',1),(UUID(), 'Laravel','Backend',2),(UUID(), 'Node.js','Backend',3),(UUID(), 'Python','Backend',4),
(UUID(), 'React','Frontend',5),(UUID(), 'TypeScript','Frontend',6),(UUID(), 'Tailwind CSS','Frontend',7),
(UUID(), 'PostgreSQL','Database',8),(UUID(), 'MySQL','Database',9),(UUID(), 'Redis','Database',10),
(UUID(), 'Android','Mobile',11),(UUID(), 'iOS','Mobile',12),(UUID(), 'Flutter','Mobile',13),
(UUID(), 'Docker','DevOps',14),(UUID(), 'Linux VPS','DevOps',15),(UUID(), 'GitHub Actions','DevOps',16),
(UUID(), 'M-Pesa Daraja','Integrations',17),(UUID(), 'Stripe','Integrations',18),(UUID(), 'Africa''s Talking','Integrations',19);

INSERT INTO portfolio_projects (id, title, slug, client_name, industry, summary, problem, solution, outcome, technologies, features, status, featured, sort_order) VALUES
(UUID(), 'Shulesoft Academic Portal','shulesoft-academic-portal','Sample School Group','Schools','A school management portal for fees, results and parent communication.','Fee tracking lived in spreadsheets and parents had no visibility into balances or results.','A single portal with student records, fee statements, results publishing and SMS notifications to parents.','Fee collection cycles shortened and the office stopped rebuilding reports by hand.','["Laravel","MySQL","Tailwind CSS"]','["Student records","Fee statements","Results publishing","Parent SMS"]','Live',1,1),
(UUID(), 'Mauzo Retail POS','mauzo-retail-pos','Sample Retail Chain','Retail','Point of sale and inventory across multiple shop branches.','Stock counts differed between branches and daily sales were reconciled manually.','An offline-capable POS with central inventory, branch transfers and daily sales reporting.','Stock accuracy improved and branch reports arrive automatically each evening.','["React","Node.js","PostgreSQL"]','["Offline POS","Multi-branch stock","M-Pesa payments","Daily reports"]','Live',1,2),
(UUID(), 'Jamii Church Platform','jamii-church-platform','Sample Church','Churches & Organizations','Membership, giving and events for a growing congregation.','Member records and giving were spread across notebooks and phone messages.','A membership database with giving records, small groups, events and giving statements.','Leadership can see attendance and giving trends in one place.','["Laravel","MySQL","Alpine.js"]','["Member directory","Giving records","Events","Statements"]','Live',0,3),
(UUID(), 'Afya Clinic Manager','afya-clinic-manager','Sample Clinic','Professional Services','Patient scheduling, records and billing for a private clinic.','Paper files slowed down consultations and billing errors were common.','Digital patient records, appointment scheduling, prescriptions and invoicing in one system.','Consultation turnaround improved and billing is now traceable per visit.','["React","Node.js","PostgreSQL"]','["Patient records","Scheduling","Prescriptions","Billing"]','In Development',0,4);

INSERT INTO saas_products (id, name, slug, tagline, description, features, status, sort_order) VALUES
(UUID(), 'Daylight Books','daylight-books','Simple accounting for Kenyan small businesses','Invoicing, expenses, M-Pesa reconciliation and tax-ready reports built for SMEs.','["Invoicing & quotes","M-Pesa reconciliation","Expense tracking","Tax-ready reports"]','In Development',1),
(UUID(), 'Daylight Desk','daylight-desk','Support ticketing for small teams','A lightweight helpdesk with email, WhatsApp and web intake in one shared inbox.','["Shared inbox","WhatsApp intake","SLA timers","Customer history"]','Coming Soon',2),
(UUID(), 'Daylight Sites','daylight-sites','Managed websites for organizations','Hosted, maintained and updated websites for schools, churches and small businesses.','["Managed hosting","Content editing","SEO basics","Monthly maintenance"]','Beta',3);

INSERT INTO blog_posts (id, title, slug, excerpt, body, category, tags, author_name, published, published_at) VALUES
(UUID(), 'Why custom software beats another spreadsheet','why-custom-software-beats-spreadsheets','Spreadsheets are where good processes go to quietly break. Here is how to know when it is time to build.','Spreadsheets are excellent until three people need the same file at once. This piece walks through the signals that a business has outgrown them: duplicated data entry, month-end reconciliations that take days, and reports nobody trusts. We look at what a purpose-built system changes, what it costs, and how to phase the move so operations never stop.','Engineering','["custom software","operations"]','Daylight Team',1,NOW() - INTERVAL 10 DAY),
(UUID(), 'Accepting M-Pesa payments in your product','accepting-mpesa-payments','A practical look at integrating Daraja into a production application without surprises.','M-Pesa integration looks simple until you meet timeouts, duplicate callbacks and reconciliation. We cover STK push, callback idempotency, ledger design and what to log so support can answer customer questions confidently.','Integrations','["m-pesa","payments","api"]','Daylight Team',1,NOW() - INTERVAL 24 DAY),
(UUID(), 'What we look for before starting a project','discovery-before-development','Discovery is not paperwork. It is the cheapest place to be wrong.','Before a line of code, we map the process, the people and the data. This post explains our discovery checklist and why projects that skip it almost always cost more.','Process','["discovery","planning"]','Daylight Team',1,NOW() - INTERVAL 40 DAY);

INSERT INTO team_members (id, name, role, bio, sort_order) VALUES
(UUID(), 'Placeholder Founder','Founder & Lead Engineer','Replace this with a real team member profile.',1),
(UUID(), 'Placeholder Engineer','Software Engineer','Replace this with a real team member profile.',2);

INSERT INTO testimonials (id, name, company, role, quote, sort_order) VALUES
(UUID(), 'Sample Client','Sample Retail Chain','Operations Manager','They understood our branch operations before proposing anything. The system fits how we actually work.',1),
(UUID(), 'Sample Client','Sample School Group','Administrator','Fee statements that used to take days now take minutes. Parents finally have visibility.',2),
(UUID(), 'Sample Client','Sample Church','Church Administrator','Clear communication throughout, and they stayed with us after launch.',3);

INSERT INTO faqs (id, question, answer, category, sort_order) VALUES
(UUID(), 'How long does a typical project take?','A focused website takes 2–4 weeks. A custom business system usually runs 8–16 weeks depending on scope, and we deliver in phases so you see progress early.','Projects',1),
(UUID(), 'What does a project cost?','Small websites start around KSh 50,000. Custom business systems typically range from KSh 300,000 upwards. We give a fixed quote after discovery.','Pricing',2),
(UUID(), 'Do you support the system after launch?','Yes. We offer monthly maintenance covering hosting, monitoring, backups, security updates and a set number of support hours.','Support',3),
(UUID(), 'Who owns the code?','You do. On final payment, source code and infrastructure access are handed over to you.','Legal',4),
(UUID(), 'Can you work with our existing system?','Often yes. We can integrate with your current tools through APIs, or migrate your data into a new system.','Projects',5);

INSERT INTO job_openings (id, title, slug, department, location, employment_type, description, requirements, status) VALUES
(UUID(), 'Laravel Engineer','laravel-engineer','Engineering','Nairobi (Hybrid)','Full-time','Build and maintain client business systems end to end.','["3+ years PHP/Laravel","Strong SQL","Experience with payment integrations"]','Open'),
(UUID(), 'Frontend Engineer','frontend-engineer','Engineering','Nairobi (Hybrid)','Full-time','Build interfaces that are fast, accessible and pleasant to use.','["React & TypeScript","Strong CSS fundamentals","Eye for detail"]','Open'),
(UUID(), 'Product Designer','product-designer','Design','Remote (Kenya)','Contract','Design product interfaces for our SaaS products and client systems.','["Portfolio of shipped product work","Figma","Systems thinking"]','Open');
