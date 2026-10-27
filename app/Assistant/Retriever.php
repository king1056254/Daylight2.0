<?php
declare(strict_types=1);

namespace App\Assistant;

/**
 * Small, dependency-free keyword retriever (TF-IDF style) used by the FAQ mode and to ground the AI mode.
 * Pure functions: no database, no network, easy to unit test.
 */
final class Retriever
{
    private const STOP = [
        'a', 'an', 'the', 'and', 'or', 'but', 'if', 'of', 'to', 'in', 'on', 'at', 'for', 'with', 'about', 'as', 'by', 'from', 'into', 'than',
        'is', 'are', 'was', 'were', 'be', 'been', 'am', 'do', 'does', 'did', 'done', 'have', 'has', 'had', 'can', 'could', 'will', 'would',
        'should', 'may', 'might', 'i', 'me', 'my', 'we', 'our', 'us', 'you', 'your', 'it', 'its', 'this', 'that', 'these', 'those', 'there',
        'what', 'which', 'who', 'whom', 'how', 'when', 'where', 'why', 'please', 'tell', 'want', 'need', 'get', 'any', 'some', 'so', 'not',
        'no', 'yes', 'just', 'also', 'very', 'much', 'many', 'more', 'most', 'other', 'such', 'only', 'own', 'same', 'too', 'out', 'up',
        'see', 'show', 'find', 'look', 'know', 'give', 'let', 'ask', 'third', 'party', 'parties', 'provide', 'offer', 'time', 'today', 'now', 'day', 'days', 'thing', 'things', 'way', 'new', 'daylight', 'hi', 'hello', 'hey',
    ];

    /** word => canonical words added to the query (so "cost" also finds "pricing") */
    private const SYNONYMS = [
        'cost' => ['price', 'pricing', 'quote'], 'costs' => ['price', 'pricing', 'quote'], 'price' => ['pricing', 'cost', 'quote'],
        'prices' => ['pricing', 'cost', 'quote'], 'pricing' => ['price', 'cost', 'quote'], 'expensive' => ['price', 'cost'],
        'cheap' => ['price', 'cost'], 'budget' => ['price', 'cost'], 'fee' => ['price', 'cost'], 'fees' => ['price', 'cost'],
        'charge' => ['price', 'cost'], 'pay' => ['payment', 'invoice'], 'paying' => ['payment', 'invoice'], 'bill' => ['invoice', 'payment'],
        'long' => ['timeline', 'week', 'time'], 'duration' => ['timeline', 'week'], 'deadline' => ['timeline', 'week'], 'fast' => ['timeline', 'week'],
        'website' => ['web', 'site'], 'site' => ['website', 'web'], 'app' => ['mobile', 'application'], 'apps' => ['mobile', 'application'],
        'android' => ['mobile'], 'ios' => ['mobile'], 'iphone' => ['mobile'],
        'login' => ['account', 'sign', 'portal'], 'signin' => ['account', 'sign', 'portal'], 'password' => ['account', 'reset', 'sign'],
        'register' => ['account', 'portal'], 'signup' => ['account', 'portal'],
        'code' => ['source', 'ownership', 'own'], 'owner' => ['own', 'ownership'], 'ownership' => ['own', 'source'],
        'help' => ['support'], 'bug' => ['support', 'ticket'], 'problem' => ['support', 'ticket'], 'issue' => ['support', 'ticket'],
        'job' => ['career', 'role'], 'jobs' => ['career', 'role'], 'hiring' => ['career', 'role'], 'vacancy' => ['career', 'role'],
        'phone' => ['contact'], 'call' => ['contact'], 'email' => ['contact'], 'address' => ['contact', 'location'], 'office' => ['contact', 'location'],
        'mpesa' => ['pesa', 'payment', 'integration'], 'ecommerce' => ['commerce', 'online', 'store', 'shop'], 'shop' => ['commerce', 'store'],
        'nda' => ['confidential', 'disclosure', 'agreement'], 'confidentiality' => ['confidential'], 'secret' => ['confidential'], 'private' => ['confidential', 'privacy'],
        'reach' => ['contact'], 'sell' => ['share'], 'safe' => ['protect', 'secure', 'security'], 'safely' => ['protect', 'secure', 'security'], 'secure' => ['protect', 'security'], 'security' => ['protect', 'secure'], 'protected' => ['protect', 'secure'],
        'customer' => ['client'], 'customers' => ['client'], 'user' => ['client', 'account'], 'users' => ['client', 'account'],
        'vat' => ['tax'], 'taxes' => ['tax'], 'privacy' => ['data', 'protection'], 'gdpr' => ['data', 'protection'],
        'delete' => ['data', 'erasure'], 'cookies' => ['cookie'], 'ai' => ['assistant', 'automation'], 'chatbot' => ['assistant'], 'bot' => ['assistant'],
    ];

    /** @return list<string> normalised tokens (lowercase, stop words removed, lightly stemmed) */
    public static function tokens(string $text): array
    {
        $text = strtolower($text);
        $text = (string) preg_replace("/[^a-z0-9\s]+/u", ' ', str_replace(["'s ", "’s "], ' ', $text . ' '));
        $out = [];
        foreach (preg_split('/\s+/', trim($text)) ?: [] as $w) {
            if ($w === '' || in_array($w, self::STOP, true)) {
                continue;
            }
            $out[] = self::stem($w);
        }
        return $out;
    }

    public static function stem(string $w): string
    {
        $len = strlen($w);
        if ($len > 4 && substr($w, -3) === 'ies') {
            $w = substr($w, 0, -3) . 'y';
        } elseif ($len > 6 && substr($w, -3) === 'ing') {
            $w = substr($w, 0, -3);
        } elseif ($len > 5 && substr($w, -2) === 'ed') {
            $w = substr($w, 0, -2);
        } elseif ($len > 3 && substr($w, -1) === 's' && substr($w, -2) !== 'ss') {
            $w = substr($w, 0, -1);
        }
        if (strlen($w) > 4 && substr($w, -1) === 'e') {
            $w = substr($w, 0, -1); // price/pricing, website/websites meet at the same stem
        }
        return $w;
    }

    /**
     * @param list<array<string,mixed>> $docs each with title, text and optional keywords
     * @return list<array{doc:array<string,mixed>,score:float,coverage:float,unknown:int}> best first
     */
    public static function search(string $query, array $docs, int $limit = 3): array
    {
        $q = self::tokens($query);
        if ($q === [] || $docs === []) {
            return [];
        }
        // one group per meaningful word: its own stem plus the stems of its synonyms
        $groups = [];
        foreach (preg_split('/[^a-z0-9]+/', strtolower($query), -1, PREG_SPLIT_NO_EMPTY) ?: [] as $raw) {
            if (in_array($raw, self::STOP, true)) {
                continue;
            }
            $orig = self::stem($raw);
            $syn = [];
            foreach (self::SYNONYMS[$raw] ?? [] as $s) {
                $st = self::stem($s);
                if ($st !== $orig) {
                    $syn[$st] = true;
                }
            }
            $groups[$orig] = array_keys($syn);
        }

        // weighted term frequencies per document, and document frequency
        $index = [];
        $df = [];
        foreach ($docs as $i => $doc) {
            $weights = [];
            foreach ([[(string) ($doc['title'] ?? ''), 3.0], [(string) ($doc['keywords'] ?? ''), 2.0], [(string) ($doc['text'] ?? ''), 1.0]] as [$field, $w]) {
                foreach (self::tokens($field) as $t) {
                    $weights[$t] = ($weights[$t] ?? 0.0) + $w;
                }
            }
            $index[$i] = $weights;
            foreach (array_keys($weights) as $t) {
                $df[$t] = ($df[$t] ?? 0) + 1;
            }
        }
        $n = count($docs);
        $weight = static fn (string $t, array $w) => log(1 + $n / ($df[$t] ?? 1)) * (1 + log(1 + $w[$t]));
        // words that no document knows (neither the word nor a synonym): a sign the question is about something else
        $unknown = 0;
        foreach ($groups as $orig => $syns) {
            $known = isset($df[$orig]);
            foreach ($syns as $s) {
                $known = $known || isset($df[$s]);
            }
            if (!$known) {
                $unknown++;
            }
        }

        $results = [];
        foreach ($docs as $i => $doc) {
            $score = 0.0;
            $understood = 0;
            foreach ($groups as $orig => $syns) {
                $hit = false;
                if (isset($index[$i][$orig])) {
                    $score += $weight((string) $orig, $index[$i]);
                    $hit = true;
                }
                foreach ($syns as $s) {
                    if (isset($index[$i][$s])) {
                        $score += 0.6 * $weight($s, $index[$i]);
                        $hit = true;
                    }
                }
                if ($hit) {
                    $understood++;
                }
            }
            if ($score <= 0) {
                continue;
            }
            $titleList = self::tokens((string) ($doc['title'] ?? ''));
            $title = implode(' ', $titleList);
            if (count($q) > 1 && $title !== '' && str_contains($title, implode(' ', $q))) {
                $score *= 1.5; // the question is (almost) the title
            }
            $titleHits = 0;
            foreach (array_keys($groups) as $orig) {
                if (in_array((string) $orig, $titleList, true)) {
                    $titleHits++;
                }
            }
            $score *= 1 + 0.25 * $titleHits; // entries whose title uses the asker's own words rank higher
            $results[] = ['doc' => $doc, 'score' => $score / sqrt(count($groups)), 'coverage' => $understood / count($groups), 'unknown' => $unknown];
        }
        usort($results, static fn (array $x, array $y): int => $y['score'] <=> $x['score']);
        return array_slice($results, 0, $limit);
    }
}
