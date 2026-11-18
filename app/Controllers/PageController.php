<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\Content;

final class PageController extends Controller
{
    public static function home(): Response
    {
        return self::page('pages/home', [
            'services' => Content::all('services'),
            'work' => Content::all('portfolio_projects'),
            'products' => Content::all('saas_products'),
            'testimonials' => Content::all('testimonials'),
        ], [
            'title' => 'Daylight — Custom software company in Nairobi, Kenya',
            'description' => 'Daylight builds custom business systems, web and mobile apps, and SaaS products for organisations across Kenya and East Africa.',
            'og_title' => 'Daylight — Custom software company in Nairobi',
            'og_description' => 'Custom business systems, web and mobile apps, and SaaS products built in Nairobi.',
        ]);
    }

    public static function services(): Response
    {
        return self::page('pages/services', ['services' => Content::all('services')], [
            'title' => 'Services — Daylight software development',
            'description' => 'Custom software, web and mobile apps, systems integration, cloud, and support services from Daylight in Nairobi.',
            'og_title' => 'Services — Daylight',
            'og_description' => 'Custom software, web and mobile apps, integration, cloud and support.',
        ]);
    }

    /** @param array<string,string> $p */
    public static function service(Request $r, array $p): Response
    {
        $row = Content::bySlug('services', $p['slug']);
        if ($row === null) {
            return self::notFoundItem('Service not found', '/services', 'Back to services', 'Service — Daylight');
        }
        return self::page('pages/service', ['service' => $row], [
            'title' => 'Service — Daylight',
            'description' => 'How Daylight delivers this service for clients in Kenya.',
            'og_title' => 'Service — Daylight',
            'og_description' => 'How Daylight delivers this service.',
        ]);
    }

    public static function work(): Response
    {
        return self::page('pages/work', ['projects' => Content::all('portfolio_projects')], [
            'title' => 'Our work — Daylight case studies',
            'description' => 'Case studies of business systems, portals and mobile apps Daylight has delivered for Kenyan organisations.',
            'og_title' => 'Our work — Daylight case studies',
            'og_description' => 'Systems, portals and apps we have delivered.',
        ]);
    }

    /** @param array<string,string> $p */
    public static function workDetail(Request $r, array $p): Response
    {
        $row = Content::bySlug('portfolio_projects', $p['slug']);
        if ($row === null) {
            return self::notFoundItem('Case study not found', '/work', 'Back to our work', 'Case study — Daylight');
        }
        return self::page('pages/work_detail', ['project' => $row], [
            'title' => 'Case study — Daylight',
            'description' => 'The problem, the solution and the outcome of a Daylight project.',
            'og_title' => 'Case study — Daylight',
            'og_description' => 'Problem, solution and outcome of a Daylight project.',
        ]);
    }

    public static function products(): Response
    {
        return self::page('pages/products', ['products' => Content::all('saas_products')], [
            'title' => 'SaaS products — Daylight',
            'description' => 'Subscription software products built, owned and operated by Daylight in Nairobi.',
            'og_title' => 'SaaS products — Daylight',
            'og_description' => 'Subscription software built and operated by Daylight.',
        ]);
    }

    public static function industries(): Response
    {
        return self::page('pages/industries', ['industries' => Content::all('industries')], [
            'title' => 'Industries we serve — Daylight',
            'description' => 'Daylight builds software for health, education, logistics, finance, agriculture, retail and the public sector in Kenya.',
            'og_title' => 'Industries we serve — Daylight',
            'og_description' => 'Sectors where Daylight software is running today.',
        ]);
    }

    public static function technologies(): Response
    {
        $groups = [];
        foreach (Content::all('technologies') as $t) {
            $groups[(string) $t['category']][] = (string) $t['name'];
        }
        return self::page('pages/technologies', ['groups' => $groups], [
            'title' => 'Our technology stack — Daylight',
            'description' => 'The languages, frameworks, databases and cloud tools Daylight uses to build software.',
            'og_title' => 'Our technology stack — Daylight',
            'og_description' => 'Languages, frameworks, databases and cloud tools we use.',
        ]);
    }

    public static function about(): Response
    {
        return self::page('pages/about', ['team' => Content::all('team_members')], [
            'title' => 'About Daylight — a Kenyan software company',
            'description' => 'Who we are, how we work, and the people behind Daylight, a software company based in Nairobi, Kenya.',
            'og_title' => 'About Daylight',
            'og_description' => 'Who we are and how we work, from Nairobi.',
        ]);
    }

    public static function blog(): Response
    {
        return self::page('pages/blog', ['posts' => Content::all('blog_posts')], [
            'title' => 'Insights — the Daylight blog',
            'description' => 'Practical writing on building, buying and running business software in Kenya, from the Daylight team.',
            'og_title' => 'Insights — the Daylight blog',
            'og_description' => 'Practical writing on business software in Kenya.',
        ]);
    }

    /** @param array<string,string> $p */
    public static function blogPost(Request $r, array $p): Response
    {
        $row = Content::bySlug('blog_posts', $p['slug']);
        if ($row === null) {
            return self::notFoundItem('Article not found', '/blog', 'Back to insights', 'Article — Daylight Insights');
        }
        return self::page('pages/blog_post', ['post' => $row], [
            'title' => 'Article — Daylight Insights',
            'description' => 'An article from the Daylight team on building business software.',
            'og_title' => 'Article — Daylight Insights',
            'og_description' => 'An article from the Daylight team.',
        ]);
    }

    public static function faq(): Response
    {
        return self::page('pages/faq', ['faqs' => Content::all('faqs')], [
            'title' => 'Frequently asked questions — Daylight',
            'description' => 'Answers about pricing, timelines, ownership, support and how Daylight runs software projects.',
            'og_title' => 'Frequently asked questions — Daylight',
            'og_description' => 'Pricing, timelines, ownership and support answered.',
        ]);
    }

    /** @return array<string,string> */
    private static function legalVars(): array
    {
        $settings = Content::settings();
        return [
            'company' => (string) ($settings['company_name'] ?? 'Daylight'),
            'email' => (string) ($settings['email'] ?? ''),
            'location' => 'Nairobi, Kenya',
        ];
    }

    public static function privacy(): Response
    {
        /** @var array<string,mixed> $doc */
        $doc = require ROOT . '/config/legal_privacy.php';
        return self::page('pages/legal', ['doc' => $doc, 'vars' => self::legalVars()], [
            'title' => 'Privacy policy — Daylight',
            'description' => (string) $doc['description'],
            'og_title' => 'Privacy policy — Daylight',
            'og_description' => 'How we handle personal data.',
        ]);
    }

    public static function terms(): Response
    {
        /** @var array<string,mixed> $doc */
        $doc = require ROOT . '/config/legal_terms.php';
        return self::page('pages/legal', ['doc' => $doc, 'vars' => self::legalVars()], [
            'title' => 'Terms of service — Daylight',
            'description' => (string) $doc['description'],
            'og_title' => 'Terms of service — Daylight',
            'og_description' => 'Terms for using our website, portal and services.',
        ]);
    }

    // ---- Form pages (also re-rendered by FormController on validation errors) ----

    /** @param array<string,mixed> $state */
    public static function contact(Request $r, array $state = [], int $status = 200): Response
    {
        $email = (string) (Content::settings()['email'] ?? 'daylightcompany13@gmail.com');
        return self::page('pages/contact', [
            'email' => $email,
            'errors' => $state['errors'] ?? [], 'old' => $state['old'] ?? [],
            'formError' => $state['formError'] ?? '',
            'done' => Session::pull('form_done') === 'contact',
        ], [
            'title' => 'Contact Daylight — Nairobi software company',
            'description' => 'Get in touch with Daylight about a project, a product or a partnership.',
            'og_title' => 'Contact Daylight',
            'og_description' => 'Talk to us about a project, product or partnership.',
        ], $status);
    }

    /** @param array<string,mixed> $state */
    public static function quote(Request $r, array $state = [], int $status = 200): Response
    {
        return self::page('pages/quote', [
            'errors' => $state['errors'] ?? [], 'old' => $state['old'] ?? [],
            'formError' => $state['formError'] ?? '',
            'done' => Session::pull('form_done') === 'quote',
        ], [
            'title' => 'Request a quote — Daylight',
            'description' => 'Tell Daylight about your software project and get a scoped, fixed quote.',
            'og_title' => 'Request a quote — Daylight',
            'og_description' => 'Describe your project and get a fixed quote.',
        ], $status);
    }

    /** @param array<string,mixed> $state */
    public static function careers(Request $r, array $state = [], int $status = 200): Response
    {
        $done = Session::pull('form_done');
        return self::page('pages/careers', [
            'jobs' => Content::all('job_openings'),
            'openJob' => (string) ($state['openJob'] ?? ''),
            'doneJob' => is_string($done) && strncmp($done, 'job:', 4) === 0 ? substr($done, 4) : '',
            'errors' => $state['errors'] ?? [], 'old' => $state['old'] ?? [],
            'formError' => $state['formError'] ?? '',
        ], [
            'title' => 'Careers at Daylight — software jobs in Nairobi',
            'description' => 'Open engineering, design and delivery roles at Daylight in Nairobi, Kenya.',
            'og_title' => 'Careers at Daylight',
            'og_description' => 'Open roles at a Nairobi software company.',
        ], $status);
    }
}
