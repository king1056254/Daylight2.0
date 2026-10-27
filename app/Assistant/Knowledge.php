<?php
declare(strict_types=1);

namespace App\Assistant;

use App\Core\Db;
use App\Models\Content;

/**
 * The assistant only knows what the website already says: published FAQs, services, industries, products, case studies,
 * technologies, open roles, company settings, plus the portal help and policy summaries in config/assistant_knowledge.php.
 * It never reads private client data.
 */
final class Knowledge
{
    /** @return list<array{id:string,title:string,text:string,keywords:string,url:string}> */
    public static function load(): array
    {
        $settings = Content::settings();
        $vars = [
            '{{email}}' => (string) ($settings['email'] ?? ''),
            '{{company}}' => (string) ($settings['company_name'] ?? 'Daylight'),
            '{{location}}' => 'Nairobi, Kenya',
        ];
        $docs = [];
        foreach (Content::all('faqs') as $f) {
            $docs[] = ['id' => 'faq-' . $f['id'], 'title' => (string) $f['question'], 'text' => (string) $f['answer'], 'keywords' => (string) ($f['category'] ?? ''), 'url' => '/faq'];
        }
        foreach (Content::all('services') as $s) {
            $docs[] = ['id' => 'service-' . $s['slug'], 'title' => (string) $s['title'], 'keywords' => 'service we build offer',
                'text' => trim($s['summary'] . ' ' . $s['description'] . ($s['features'] !== [] ? ' Includes: ' . implode(', ', $s['features']) . '.' : '')), 'url' => '/services/' . $s['slug']];
        }
        foreach (Content::all('industries') as $i) {
            $docs[] = ['id' => 'industry-' . $i['slug'], 'title' => (string) $i['name'] . ' (industry)', 'keywords' => 'industry sector clients we serve', 'text' => trim($i['summary'] . ' ' . ($i['description'] ?? '')), 'url' => '/industries'];
        }
        foreach (Content::all('saas_products') as $p) {
            $docs[] = ['id' => 'product-' . $p['slug'], 'title' => (string) $p['name'], 'keywords' => 'product saas software status',
                'text' => trim($p['tagline'] . '. ' . $p['description'] . ' Status: ' . $p['status'] . '.' . ($p['features'] !== [] ? ' Features: ' . implode(', ', $p['features']) . '.' : '')), 'url' => '/products'];
        }
        foreach (Content::all('portfolio_projects') as $c) {
            $docs[] = ['id' => 'work-' . $c['slug'], 'title' => (string) $c['title'] . ' (case study)', 'keywords' => 'case study project portfolio work example ' . $c['industry'],
                'text' => trim($c['summary'] . ' ' . ($c['technologies'] !== [] ? 'Built with ' . implode(', ', $c['technologies']) . '.' : '')), 'url' => '/work/' . $c['slug']];
        }
        $groups = [];
        foreach (Content::all('technologies') as $t) {
            $groups[(string) $t['category']][] = (string) $t['name'];
        }
        if ($groups !== []) {
            $parts = [];
            foreach ($groups as $cat => $names) {
                $parts[] = $cat . ': ' . implode(', ', $names);
            }
            $docs[] = ['id' => 'technologies', 'title' => 'Technologies we use', 'keywords' => 'technology stack tools languages frameworks database', 'text' => implode('. ', $parts) . '.', 'url' => '/technologies'];
        }
        $jobs = Content::all('job_openings');
        $docs[] = ['id' => 'careers', 'title' => 'Careers and open roles', 'keywords' => 'job career hiring vacancy apply work for us',
            'text' => $jobs === [] ? 'There are no open roles right now. Check the Careers page again soon.' : 'Open roles: ' . implode(', ', array_map(static fn (array $j): string => (string) $j['title'], $jobs)) . '. You can apply from the Careers page.',
            'url' => '/careers'];
        if (!empty($settings['description'])) {
            $docs[] = ['id' => 'about', 'title' => 'About ' . $vars['{{company}}'], 'keywords' => 'company who are you about', 'text' => (string) $settings['description'], 'url' => '/about'];
        }
        /** @var list<array{id:string,title:string,text:string,keywords?:string,url:string}> $static */
        $static = require ROOT . '/config/assistant_knowledge.php';
        foreach ($static as $s) {
            $docs[] = [
                'id' => $s['id'], 'title' => strtr($s['title'], $vars), 'text' => strtr($s['text'], $vars),
                'keywords' => (string) ($s['keywords'] ?? ''), 'url' => $s['url'],
            ];
        }
        return $docs;
    }
}
