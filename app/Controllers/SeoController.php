<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Mailer;
use App\Core\Response;
use App\Models\Content;

/** sitemap.xml and robots.txt. Absolute URLs come from APP_URL only (never from the Host header). */
final class SeoController extends Controller
{
    /** [path, priority, changefreq] for the public pages that always exist */
    private const STATIC_PAGES = [
        ['/', '1.0', 'weekly'], ['/services', '0.9', 'monthly'], ['/work', '0.9', 'monthly'], ['/products', '0.8', 'monthly'],
        ['/industries', '0.7', 'monthly'], ['/technologies', '0.5', 'yearly'], ['/about', '0.7', 'yearly'], ['/blog', '0.8', 'weekly'],
        ['/careers', '0.6', 'weekly'], ['/contact', '0.7', 'yearly'], ['/quote', '0.8', 'yearly'], ['/faq', '0.6', 'monthly'],
        ['/privacy', '0.3', 'yearly'], ['/terms', '0.3', 'yearly'],
    ];

    /** table => URL prefix for pages generated from published database rows */
    private const DYNAMIC = ['services' => '/services/', 'portfolio_projects' => '/work/', 'blog_posts' => '/blog/'];

    private static function text(string $body, string $type, int $status = 200): Response
    {
        return new Response($body, $status, ['Content-Type' => $type . '; charset=utf-8', 'Cache-Control' => 'public, max-age=3600']);
    }

    public static function sitemap(): Response
    {
        if (Mailer::absoluteUrl('/') === null) {
            return new Response("The sitemap needs APP_URL to be set (for example https://example.com).\n", 503, ['Content-Type' => 'text/plain; charset=utf-8', 'Cache-Control' => 'no-store']);
        }
        $entries = [];
        foreach (self::STATIC_PAGES as [$path, $priority, $freq]) {
            $entries[] = [$path, null, $freq, $priority];
        }
        foreach (self::DYNAMIC as $table => $prefix) {
            foreach (Content::all($table) as $row) {
                $stamp = strtotime((string) ($row['updated_at'] ?? ''));
                $entries[] = [$prefix . $row['slug'], $stamp === false ? null : gmdate('Y-m-d', $stamp), 'monthly', '0.6'];
            }
        }
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($entries as [$path, $lastmod, $freq, $priority]) {
            $loc = (string) Mailer::absoluteUrl($path);
            $xml .= "  <url>\n    <loc>" . htmlspecialchars($loc, ENT_XML1 | ENT_QUOTES, 'UTF-8') . "</loc>\n";
            if ($lastmod !== null) {
                $xml .= '    <lastmod>' . $lastmod . "</lastmod>\n";
            }
            $xml .= '    <changefreq>' . $freq . "</changefreq>\n    <priority>" . $priority . "</priority>\n  </url>\n";
        }
        return self::text($xml . "</urlset>\n", 'application/xml');
    }

    public static function robots(): Response
    {
        $lines = [
            'User-agent: *',
            'Allow: /',
            'Disallow: ' . url('/admin'),
            'Disallow: ' . url('/portal'),
            'Disallow: ' . url('/auth'),
            'Disallow: ' . url('/setup'),
        ];
        $sitemap = Mailer::absoluteUrl('/sitemap.xml');
        if ($sitemap !== null) {
            $lines[] = '';
            $lines[] = 'Sitemap: ' . $sitemap;
        }
        return self::text(implode("\n", $lines) . "\n", 'text/plain');
    }
}
