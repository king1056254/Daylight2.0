<?php
declare(strict_types=1);

/*
 * Admin resources. One definition drives list / create / edit / delete screens, validation and permissions.
 * visible: who sees it in the menu ('staff' = staff+admins, 'admin' = admins only)
 * write:   who may change it ('staff' or 'admin'); everyone who can see it can read it.
 * field types: text, textarea, email, tel, url, select, int, decimal, date, datetime, slug, list, bool, rel
 */

$pub = ['type' => 'bool', 'label' => 'Published', 'default' => 1];
$sort = ['type' => 'int', 'label' => 'Sort order', 'default' => 0];

return [
    // ---------------- Inbox ----------------
    'messages' => [
        'title' => 'Contact messages', 'singular' => 'contact message', 'group' => 'Inbox', 'visible' => 'staff', 'write' => 'admin',
        'table' => 'contact_messages', 'order' => 'created_at DESC', 'no_create' => true,
        'list' => ['name', 'email', 'subject', 'status', 'created_at'], 'search' => ['name', 'email', 'subject', 'message', 'status'],
        'fields' => [
            'name' => ['readonly' => true], 'email' => ['readonly' => true], 'phone' => ['readonly' => true],
            'company' => ['readonly' => true], 'subject' => ['readonly' => true],
            'message' => ['type' => 'textarea', 'readonly' => true],
            'status' => ['type' => 'select', 'options' => ['New', 'In progress', 'Replied', 'Closed'], 'required' => true],
            'created_at' => ['type' => 'datetime', 'label' => 'Received', 'readonly' => true],
        ],
    ],
    'quotes' => [
        'title' => 'Quote requests', 'singular' => 'quote request', 'group' => 'Inbox', 'visible' => 'staff', 'write' => 'admin',
        'table' => 'quote_requests', 'order' => 'created_at DESC', 'no_create' => true,
        'list' => ['name', 'email', 'project_type', 'budget_range', 'status', 'created_at'], 'search' => ['name', 'email', 'company', 'details', 'status'],
        'fields' => [
            'name' => ['readonly' => true], 'email' => ['readonly' => true], 'phone' => ['readonly' => true],
            'company' => ['readonly' => true], 'country' => ['readonly' => true],
            'project_type' => ['readonly' => true], 'budget_range' => ['readonly' => true], 'timeline' => ['readonly' => true],
            'details' => ['type' => 'textarea', 'readonly' => true],
            'status' => ['type' => 'select', 'options' => ['New', 'In progress', 'Quoted', 'Won', 'Lost', 'Closed'], 'required' => true],
            'created_at' => ['type' => 'datetime', 'label' => 'Received', 'readonly' => true],
        ],
    ],
    'applications' => [
        'title' => 'Job applications', 'singular' => 'job application', 'group' => 'Inbox', 'visible' => 'staff', 'write' => 'admin',
        'table' => 'job_applications', 'order' => 'created_at DESC', 'no_create' => true,
        'list' => ['name', 'email', 'job_id', 'status', 'created_at'], 'search' => ['name', 'email', 'cover_letter', 'status'],
        'fields' => [
            'job_id' => ['type' => 'rel', 'label' => 'Role', 'rel' => ['table' => 'job_openings', 'label' => 'title'], 'readonly' => true],
            'name' => ['readonly' => true], 'email' => ['readonly' => true], 'phone' => ['readonly' => true],
            'portfolio_url' => ['label' => 'Portfolio / CV link', 'readonly' => true],
            'cover_letter' => ['type' => 'textarea', 'readonly' => true],
            'status' => ['type' => 'select', 'options' => ['Received', 'Reviewing', 'Interview', 'Offer', 'Hired', 'Rejected'], 'required' => true],
            'created_at' => ['type' => 'datetime', 'label' => 'Received', 'readonly' => true],
        ],
    ],

    'assistant-chats' => [
        'title' => 'Assistant chats', 'singular' => 'assistant message', 'group' => 'Inbox', 'visible' => 'admin', 'write' => 'admin',
        'table' => 'assistant_messages', 'order' => 'created_at DESC', 'no_create' => true,
        'list' => ['content', 'role', 'mode', 'matched', 'created_at'], 'search' => ['content', 'mode'],
        'fields' => [
            'role' => ['readonly' => true], 'content' => ['type' => 'textarea', 'readonly' => true], 'mode' => ['readonly' => true],
            'matched' => ['type' => 'bool', 'label' => 'Answered from site content', 'readonly' => true],
            'created_at' => ['type' => 'datetime', 'label' => 'Time', 'readonly' => true],
        ],
    ],

    // ---------------- Clients & delivery ----------------
    'clients' => [
        'title' => 'Clients', 'singular' => 'client', 'group' => 'Clients', 'visible' => 'staff', 'write' => 'staff',
        'table' => 'clients', 'order' => 'company_name ASC',
        'list' => ['company_name', 'contact_name', 'email', 'status'], 'search' => ['company_name', 'contact_name', 'email'],
        'fields' => [
            'company_name' => ['required' => true, 'label' => 'Company', 'max' => 200],
            'contact_name' => ['label' => 'Contact person', 'max' => 200],
            'email' => ['type' => 'email'], 'phone' => ['type' => 'tel'], 'industry' => ['max' => 200],
            'user_id' => ['type' => 'rel', 'label' => 'Portal user', 'rel' => ['table' => 'users', 'label' => 'email'], 'hint' => 'The signed-up account that may see this client in the portal.'],
            'status' => ['type' => 'select', 'options' => ['Active', 'Inactive'], 'required' => true, 'default' => 'Active'],
            'notes' => ['type' => 'textarea', 'label' => 'Internal notes'],
        ],
    ],
    'client-projects' => [
        'title' => 'Client projects', 'singular' => 'client project', 'group' => 'Clients', 'visible' => 'staff', 'write' => 'staff',
        'table' => 'client_projects', 'order' => 'created_at DESC',
        'list' => ['name', 'client_id', 'status', 'progress'], 'search' => ['name'],
        'fields' => [
            'client_id' => ['type' => 'rel', 'label' => 'Client', 'required' => true, 'rel' => ['table' => 'clients', 'label' => 'company_name']],
            'name' => ['required' => true, 'max' => 200],
            'description' => ['type' => 'textarea'],
            'status' => ['type' => 'select', 'options' => ['Planning', 'In Progress', 'On Hold', 'Completed'], 'required' => true, 'default' => 'In Progress'],
            'progress' => ['type' => 'int', 'min' => 0, 'maxval' => 100, 'default' => 0, 'hint' => 'Percent complete (0–100).'],
            'start_date' => ['type' => 'date'], 'expected_completion' => ['type' => 'date'],
        ],
    ],
    'milestones' => [
        'title' => 'Milestones', 'singular' => 'milestone', 'group' => 'Clients', 'visible' => 'staff', 'write' => 'staff',
        'table' => 'project_milestones', 'order' => 'project_id ASC, sort_order ASC',
        'list' => ['title', 'project_id', 'status', 'due_date'], 'search' => ['title'],
        'fields' => [
            'project_id' => ['type' => 'rel', 'label' => 'Project', 'required' => true, 'rel' => ['table' => 'client_projects', 'label' => 'name']],
            'title' => ['required' => true, 'max' => 200], 'description' => ['type' => 'textarea'],
            'status' => ['type' => 'select', 'options' => ['Pending', 'In Progress', 'Completed'], 'required' => true, 'default' => 'Pending'],
            'due_date' => ['type' => 'date'], 'sort_order' => $sort,
        ],
    ],
    'documents' => [
        'title' => 'Project documents', 'singular' => 'document', 'group' => 'Clients', 'visible' => 'staff', 'write' => 'staff',
        'table' => 'project_documents', 'order' => 'created_at DESC',
        'list' => ['title', 'project_id', 'file_type', 'visible_to_client'], 'search' => ['title'],
        'before' => ['uploader'],
        'fields' => [
            'project_id' => ['type' => 'rel', 'label' => 'Project', 'required' => true, 'rel' => ['table' => 'client_projects', 'label' => 'name']],
            'title' => ['required' => true, 'max' => 200],
            'file_path' => ['type' => 'url', 'label' => 'File link', 'max' => 500, 'hint' => 'Link to the file (for example Google Drive or Dropbox). File uploads are not part of this version.'],
            'file_type' => ['max' => 60, 'hint' => 'For example PDF, Contract, Design.'],
            'visible_to_client' => ['type' => 'bool', 'label' => 'Visible to client', 'default' => 1],
        ],
    ],
    'project-messages' => [
        'title' => 'Project messages', 'singular' => 'project message', 'group' => 'Clients', 'visible' => 'staff', 'write' => 'staff',
        'table' => 'project_messages', 'order' => 'created_at DESC',
        'list' => ['body', 'project_id', 'sender_name', 'created_at'], 'search' => ['body', 'sender_name'],
        'before' => ['sender'],
        'fields' => [
            'project_id' => ['type' => 'rel', 'label' => 'Project', 'required' => true, 'rel' => ['table' => 'client_projects', 'label' => 'name']],
            'body' => ['type' => 'textarea', 'required' => true, 'label' => 'Message'],
            'sender_name' => ['readonly' => true, 'label' => 'From'],
        ],
    ],
    'tickets' => [
        'title' => 'Support tickets', 'singular' => 'ticket', 'group' => 'Clients', 'visible' => 'staff', 'write' => 'staff',
        'table' => 'support_tickets', 'order' => 'created_at DESC',
        'list' => ['subject', 'client_id', 'priority', 'status', 'created_at'], 'search' => ['subject', 'description', 'status'],
        'fields' => [
            'client_id' => ['type' => 'rel', 'label' => 'Client', 'required' => true, 'rel' => ['table' => 'clients', 'label' => 'company_name']],
            'project_id' => ['type' => 'rel', 'label' => 'Project', 'rel' => ['table' => 'client_projects', 'label' => 'name']],
            'subject' => ['required' => true, 'max' => 250], 'description' => ['type' => 'textarea'],
            'priority' => ['type' => 'select', 'options' => ['Low', 'Normal', 'High', 'Urgent'], 'required' => true, 'default' => 'Normal'],
            'status' => ['type' => 'select', 'options' => ['Open', 'In Progress', 'Resolved', 'Closed'], 'required' => true, 'default' => 'Open'],
        ],
    ],

    // ---------------- Billing ----------------
    'invoices' => [
        'title' => 'Invoices', 'singular' => 'invoice', 'group' => 'Billing', 'visible' => 'staff', 'write' => 'staff',
        'table' => 'invoices', 'order' => 'created_at DESC',
        'list' => ['invoice_number', 'client_id', 'status', 'total', 'due_date'], 'search' => ['invoice_number', 'notes', 'status'],
        'before' => ['invoiceNumber', 'invoiceDefaults'], 'after' => ['recalcInvoiceFromRow'], 'unique' => ['invoice_number'],
        'fields' => [
            'invoice_number' => ['max' => 40, 'hint' => 'Leave empty to generate (INV-YYYY-0001).'],
            'client_id' => ['type' => 'rel', 'label' => 'Client', 'required' => true, 'rel' => ['table' => 'clients', 'label' => 'company_name']],
            'project_id' => ['type' => 'rel', 'label' => 'Project', 'rel' => ['table' => 'client_projects', 'label' => 'name']],
            'currency' => ['type' => 'select', 'options' => ['KES', 'USD', 'EUR', 'GBP'], 'required' => true, 'default' => 'KES'],
            'tax' => ['type' => 'decimal', 'min' => 0, 'default' => '0.00', 'hint' => 'Tax amount (not a percentage).'],
            'status' => ['type' => 'select', 'options' => ['Draft', 'Sent', 'Paid', 'Overdue', 'Cancelled'], 'required' => true, 'default' => 'Draft', 'hint' => 'Clients can only see invoices that are not Draft.'],
            'issue_date' => ['type' => 'date'], 'due_date' => ['type' => 'date'],
            'notes' => ['type' => 'textarea'],
            'subtotal' => ['type' => 'decimal', 'readonly' => true, 'hint' => 'Calculated from line items.'],
            'total' => ['type' => 'decimal', 'readonly' => true, 'hint' => 'Subtotal + tax.'],
        ],
    ],
    'invoice-items' => [
        'title' => 'Invoice items', 'singular' => 'invoice item', 'group' => 'Billing', 'visible' => 'staff', 'write' => 'staff',
        'table' => 'invoice_items', 'order' => 'invoice_id ASC, id ASC',
        'list' => ['description', 'invoice_id', 'quantity', 'unit_price', 'amount'], 'search' => ['description'],
        'before' => ['itemAmount'], 'after' => ['recalcInvoiceFromItem'], 'after_delete' => ['recalcInvoiceFromItem'],
        'fields' => [
            'invoice_id' => ['type' => 'rel', 'label' => 'Invoice', 'required' => true, 'rel' => ['table' => 'invoices', 'label' => 'invoice_number']],
            'description' => ['required' => true, 'max' => 400],
            'quantity' => ['type' => 'decimal', 'required' => true, 'min' => 0, 'default' => '1.00'],
            'unit_price' => ['type' => 'decimal', 'required' => true, 'min' => 0, 'default' => '0.00'],
            'amount' => ['type' => 'decimal', 'readonly' => true, 'hint' => 'Quantity × unit price.'],
        ],
    ],
    'payments' => [
        'title' => 'Payments', 'singular' => 'payment', 'group' => 'Billing', 'visible' => 'staff', 'write' => 'staff',
        'table' => 'payments', 'order' => 'paid_at DESC',
        'list' => ['invoice_id', 'amount', 'method', 'reference', 'paid_at'], 'search' => ['reference', 'method'],
        'after' => ['settleInvoice'], 'after_delete' => ['settleInvoice'], 'before' => ['paidAt'],
        'fields' => [
            'invoice_id' => ['type' => 'rel', 'label' => 'Invoice', 'required' => true, 'rel' => ['table' => 'invoices', 'label' => 'invoice_number']],
            'amount' => ['type' => 'decimal', 'required' => true, 'min' => 0.01],
            'method' => ['max' => 60, 'default' => 'M-Pesa'], 'reference' => ['max' => 120],
            'paid_at' => ['type' => 'datetime', 'label' => 'Paid at', 'notnull' => true, 'hint' => 'Leave empty to use the current time.'],
        ],
    ],

    // ---------------- Website content ----------------
    'services' => [
        'title' => 'Services', 'singular' => 'service', 'group' => 'Website content', 'visible' => 'admin', 'write' => 'admin',
        'table' => 'services', 'order' => 'sort_order ASC, title ASC', 'slug_from' => 'title', 'unique' => ['slug'],
        'list' => ['title', 'slug', 'sort_order', 'published'], 'search' => ['title', 'slug', 'summary'],
        'fields' => [
            'title' => ['required' => true, 'max' => 200], 'slug' => ['type' => 'slug', 'max' => 200, 'hint' => 'Leave empty to generate from the title.'],
            'summary' => ['type' => 'textarea', 'max' => 1000], 'description' => ['type' => 'textarea', 'max' => 20000],
            'icon' => ['max' => 60], 'features' => ['type' => 'list', 'hint' => 'One item per line.'], 'sort_order' => $sort, 'published' => $pub,
        ],
    ],
    'industries' => [
        'title' => 'Industries', 'singular' => 'industry', 'group' => 'Website content', 'visible' => 'admin', 'write' => 'admin',
        'table' => 'industries', 'order' => 'sort_order ASC, name ASC', 'slug_from' => 'name', 'unique' => ['slug'],
        'list' => ['name', 'slug', 'sort_order', 'published'], 'search' => ['name', 'summary'],
        'fields' => [
            'name' => ['required' => true, 'max' => 200], 'slug' => ['type' => 'slug', 'max' => 200, 'hint' => 'Leave empty to generate from the name.'],
            'summary' => ['type' => 'textarea', 'max' => 1000], 'description' => ['type' => 'textarea', 'max' => 20000],
            'icon' => ['max' => 60], 'sort_order' => $sort, 'published' => $pub,
        ],
    ],
    'technologies' => [
        'title' => 'Technologies', 'singular' => 'technology', 'group' => 'Website content', 'visible' => 'admin', 'write' => 'admin',
        'table' => 'technologies', 'order' => 'sort_order ASC, name ASC',
        'list' => ['name', 'category', 'sort_order', 'published'], 'search' => ['name', 'category'],
        'fields' => [
            'name' => ['required' => true, 'max' => 200], 'category' => ['required' => true, 'max' => 100, 'default' => 'Backend'],
            'sort_order' => $sort, 'published' => $pub,
        ],
    ],
    'work' => [
        'title' => 'Case studies', 'singular' => 'case study', 'group' => 'Website content', 'visible' => 'admin', 'write' => 'admin',
        'table' => 'portfolio_projects', 'order' => 'sort_order ASC, title ASC', 'slug_from' => 'title', 'unique' => ['slug'],
        'list' => ['title', 'industry', 'status', 'sort_order', 'published'], 'search' => ['title', 'client_name', 'industry'],
        'fields' => [
            'title' => ['required' => true, 'max' => 200], 'slug' => ['type' => 'slug', 'max' => 200, 'hint' => 'Leave empty to generate from the title.'],
            'client_name' => ['max' => 200], 'industry' => ['max' => 200],
            'summary' => ['type' => 'textarea', 'max' => 1000], 'problem' => ['type' => 'textarea', 'max' => 20000],
            'solution' => ['type' => 'textarea', 'max' => 20000], 'outcome' => ['type' => 'textarea', 'max' => 20000],
            'technologies' => ['type' => 'list', 'hint' => 'One per line.'], 'features' => ['type' => 'list', 'hint' => 'One per line.'],
            'status' => ['type' => 'select', 'options' => ['Live', 'In Development', 'Beta', 'Coming Soon'], 'required' => true, 'default' => 'Live'],
            'live_url' => ['type' => 'url', 'max' => 500], 'cover_url' => ['type' => 'url', 'max' => 500],
            'featured' => ['type' => 'bool', 'label' => 'Featured'], 'sort_order' => $sort, 'published' => $pub,
        ],
    ],
    'products' => [
        'title' => 'SaaS products', 'singular' => 'product', 'group' => 'Website content', 'visible' => 'admin', 'write' => 'admin',
        'table' => 'saas_products', 'order' => 'sort_order ASC, name ASC', 'slug_from' => 'name', 'unique' => ['slug'],
        'list' => ['name', 'status', 'sort_order', 'published'], 'search' => ['name', 'tagline'],
        'fields' => [
            'name' => ['required' => true, 'max' => 200], 'slug' => ['type' => 'slug', 'max' => 200, 'hint' => 'Leave empty to generate from the name.'],
            'tagline' => ['max' => 300], 'description' => ['type' => 'textarea', 'max' => 20000],
            'features' => ['type' => 'list', 'hint' => 'One per line.'],
            'status' => ['type' => 'select', 'options' => ['Live', 'Beta', 'In Development', 'Coming Soon'], 'required' => true, 'default' => 'Coming Soon'],
            'product_url' => ['type' => 'url', 'max' => 500], 'cover_url' => ['type' => 'url', 'max' => 500], 'sort_order' => $sort, 'published' => $pub,
        ],
    ],
    'posts' => [
        'title' => 'Blog posts', 'singular' => 'blog post', 'group' => 'Website content', 'visible' => 'admin', 'write' => 'admin',
        'table' => 'blog_posts', 'order' => 'created_at DESC', 'slug_from' => 'title', 'unique' => ['slug'], 'before' => ['publishStamp'],
        'list' => ['title', 'category', 'published', 'published_at'], 'search' => ['title', 'excerpt', 'category'],
        'fields' => [
            'title' => ['required' => true, 'max' => 250], 'slug' => ['type' => 'slug', 'max' => 250, 'hint' => 'Leave empty to generate from the title.'],
            'excerpt' => ['type' => 'textarea', 'max' => 1000], 'body' => ['type' => 'textarea', 'max' => 100000],
            'category' => ['max' => 100], 'tags' => ['type' => 'list', 'hint' => 'One tag per line.'],
            'cover_url' => ['type' => 'url', 'max' => 500], 'author_name' => ['max' => 200],
            'published' => ['type' => 'bool', 'label' => 'Published'],
            'published_at' => ['type' => 'datetime', 'label' => 'Publish date', 'hint' => 'Leave empty to use now when publishing.'],
        ],
    ],
    'team' => [
        'title' => 'Team members', 'singular' => 'team member', 'group' => 'Website content', 'visible' => 'admin', 'write' => 'admin',
        'table' => 'team_members', 'order' => 'sort_order ASC, name ASC',
        'list' => ['name', 'role', 'sort_order', 'published'], 'search' => ['name', 'role'],
        'fields' => [
            'name' => ['required' => true, 'max' => 200], 'role' => ['max' => 200], 'bio' => ['type' => 'textarea', 'max' => 3000],
            'photo_url' => ['type' => 'url', 'max' => 500], 'linkedin_url' => ['type' => 'url', 'max' => 500], 'sort_order' => $sort, 'published' => $pub,
        ],
    ],
    'testimonials' => [
        'title' => 'Testimonials', 'singular' => 'testimonial', 'group' => 'Website content', 'visible' => 'admin', 'write' => 'admin',
        'table' => 'testimonials', 'order' => 'sort_order ASC, created_at ASC',
        'list' => ['name', 'company', 'rating', 'published'], 'search' => ['name', 'company', 'quote'],
        'fields' => [
            'name' => ['required' => true, 'max' => 200], 'company' => ['max' => 200], 'role' => ['max' => 200],
            'quote' => ['type' => 'textarea', 'required' => true, 'max' => 2000],
            'rating' => ['type' => 'int', 'min' => 1, 'maxval' => 5, 'default' => 5],
            'photo_url' => ['type' => 'url', 'max' => 500], 'sort_order' => $sort, 'published' => $pub,
        ],
    ],
    'faqs' => [
        'title' => 'FAQs', 'singular' => 'FAQ', 'group' => 'Website content', 'visible' => 'admin', 'write' => 'admin',
        'table' => 'faqs', 'order' => 'sort_order ASC, created_at ASC',
        'list' => ['question', 'category', 'sort_order', 'published'], 'search' => ['question', 'answer'],
        'fields' => [
            'question' => ['required' => true, 'max' => 500], 'answer' => ['type' => 'textarea', 'required' => true, 'max' => 5000],
            'category' => ['max' => 100, 'default' => 'General'], 'sort_order' => $sort, 'published' => $pub,
        ],
    ],
    'jobs' => [
        'title' => 'Job openings', 'singular' => 'job opening', 'group' => 'Website content', 'visible' => 'admin', 'write' => 'admin',
        'table' => 'job_openings', 'order' => 'created_at DESC', 'slug_from' => 'title', 'unique' => ['slug'],
        'list' => ['title', 'department', 'location', 'status'], 'search' => ['title', 'department'],
        'fields' => [
            'title' => ['required' => true, 'max' => 200], 'slug' => ['type' => 'slug', 'max' => 200, 'hint' => 'Leave empty to generate from the title.'],
            'department' => ['max' => 100], 'location' => ['max' => 200], 'employment_type' => ['max' => 60, 'default' => 'Full-time'],
            'description' => ['type' => 'textarea', 'max' => 20000], 'requirements' => ['type' => 'list', 'hint' => 'One per line.'],
            'status' => ['type' => 'select', 'options' => ['Open', 'Closed'], 'required' => true, 'default' => 'Open'],
        ],
    ],
    'settings' => [
        'title' => 'Company settings', 'singular' => 'company settings', 'group' => 'Website content', 'visible' => 'admin', 'write' => 'admin',
        'table' => 'company_settings', 'order' => 'updated_at DESC', 'single' => true,
        'list' => ['company_name', 'email', 'phone'], 'search' => ['company_name'],
        'fields' => [
            'company_name' => ['required' => true, 'max' => 200], 'tagline' => ['max' => 300], 'description' => ['type' => 'textarea', 'max' => 2000],
            'email' => ['type' => 'email', 'hint' => 'Shown in the website footer and on the contact page.'], 'phone' => ['type' => 'tel'],
            'address' => ['max' => 300], 'whatsapp' => ['type' => 'tel'],
            'linkedin_url' => ['type' => 'url', 'max' => 500], 'twitter_url' => ['type' => 'url', 'max' => 500], 'github_url' => ['type' => 'url', 'max' => 500],
        ],
    ],
];
