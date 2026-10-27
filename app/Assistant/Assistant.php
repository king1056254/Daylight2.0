<?php
declare(strict_types=1);

namespace App\Assistant;

use App\Core\Config;
use App\Core\Errors;
use App\Core\RateLimiter;

/**
 * Decides how to answer: small-talk intents, then retrieval over the website's own content (FAQ mode),
 * or the AI mode (grounded on the same retrieved content) when it is configured.
 */
final class Assistant
{
    private const CONFIDENT_COVERAGE = 0.5;
    private const CONFIDENT_COVERAGE_UNKNOWN_WORD = 0.6;
    private const CONFIDENT_SCORE = 1.6;

    /** @return string|null  greeting | thanks | bye | human | null */
    public static function intent(string $message): ?string
    {
        $m = strtolower(trim((string) preg_replace('/[^a-z0-9\s\']+/i', ' ', $message)));
        if ($m === '') {
            return null;
        }
        if (preg_match('/\b(real|live) (person|human|agent|people)\b|\b(talk|speak|chat|connect|transfer)\b.{0,30}\b(human|person|agent|someone|somebody|representative|staff|team|people)\b|\b(human agent|customer service|representative)\b/', $m) === 1) {
            return 'human';
        }
        if (preg_match('/^(hi|hello|hey|hallo|habari|jambo|mambo|good (morning|afternoon|evening)|greetings)\b[\s\w]{0,20}$/', $m) === 1) {
            return 'greeting';
        }
        if (preg_match('/^(thanks|thank you|asante|cheers|great thanks|ok thanks|thx)\b[\s\w]{0,20}$/', $m) === 1) {
            return 'thanks';
        }
        if (preg_match('/^(bye|goodbye|see you|kwaheri)\b[\s\w]{0,20}$/', $m) === 1) {
            return 'bye';
        }
        return null;
    }

    /**
     * Pure FAQ-mode answer from a document list (unit-testable).
     * @param list<array<string,mixed>> $docs
     * @return array{answer:string,links:list<array{label:string,url:string}>,matched:bool}
     */
    public static function faqAnswer(string $message, array $docs): array
    {
        $hits = Retriever::search($message, $docs, 3);
        $top = $hits[0] ?? null;
        $needed = $top !== null && $top['unknown'] > 0 ? self::CONFIDENT_COVERAGE_UNKNOWN_WORD : self::CONFIDENT_COVERAGE;
        if ($top === null || $top['coverage'] < $needed || $top['score'] < self::CONFIDENT_SCORE) {
            return self::notFound();
        }
        $links = [self::link($top['doc'])];
        foreach (array_slice($hits, 1) as $h) {
            if ($h['coverage'] >= self::CONFIDENT_COVERAGE && $h['score'] >= $top['score'] * 0.75 && $h['doc']['url'] !== $top['doc']['url']) {
                $links[] = self::link($h['doc']);
            }
        }
        return ['answer' => self::shorten((string) $top['doc']['text'], 650), 'links' => array_slice($links, 0, 3), 'matched' => true];
    }

    /** @return array{answer:string,links:list<array{label:string,url:string}>,matched:bool} */
    public static function notFound(): array
    {
        return [
            'answer' => "I couldn't find an answer to that in our FAQs and website content. You can rephrase your question, browse the FAQ, or ask the team directly.",
            'links' => [['label' => 'Browse the FAQ', 'url' => '/faq'], ['label' => 'Contact the team', 'url' => '/contact'], ['label' => 'Request a quote', 'url' => '/quote']],
            'matched' => false,
        ];
    }

    /** @return array{answer:string,links:list<array{label:string,url:string}>,matched:bool,mode:string} */
    public static function reply(string $message, array $history): array
    {
        $intent = self::intent($message);
        if ($intent === 'greeting') {
            return ['answer' => "Hello! I'm Daylight's automated assistant. I can answer questions about our services, how projects work, pricing guidance from our FAQ and the client portal. What would you like to know?", 'links' => [], 'matched' => true, 'mode' => 'faq'];
        }
        if ($intent === 'thanks') {
            return ['answer' => "You're welcome! Ask me anything else, or contact the team if you'd like to talk to a person.", 'links' => [['label' => 'Contact the team', 'url' => '/contact']], 'matched' => true, 'mode' => 'faq'];
        }
        if ($intent === 'bye') {
            return ['answer' => 'Goodbye! Come back any time.', 'links' => [], 'matched' => true, 'mode' => 'faq'];
        }
        if ($intent === 'human') {
            return ['answer' => "I'm an automated assistant, so I can't connect you to a person here. The quickest way to reach the team is the Contact page; to start a project, request a quote.", 'links' => [['label' => 'Contact the team', 'url' => '/contact'], ['label' => 'Request a quote', 'url' => '/quote']], 'matched' => true, 'mode' => 'faq'];
        }

        $docs = Knowledge::load();
        $faq = self::faqAnswer($message, $docs);

        if (AiClient::enabled() && !RateLimiter::tooMany('assistant_ai_day', 'all', Config::int('ASSISTANT_DAILY_AI_LIMIT', 300), 86400)) {
            try {
                $hits = Retriever::search($message . ' ' . self::lastUser($history), $docs, 5);
                $context = '';
                foreach ($hits as $h) {
                    $context .= '- ' . $h['doc']['title'] . ': ' . mb_substr((string) $h['doc']['text'], 0, 800) . "\n";
                }
                $context .= '- Contact: see the Contact page (/contact). Request a quote: /quote. Terms: /terms. Privacy: /privacy.' . "\n";
                RateLimiter::hit('assistant_ai_day', 'all');
                $text = AiClient::complete($message, $history, $context);
                $links = [];
                foreach (array_slice($hits, 0, 2) as $h) {
                    if ($h['coverage'] >= 0.34) {
                        $links[] = self::link($h['doc']);
                    }
                }
                return ['answer' => $text, 'links' => $links, 'matched' => true, 'mode' => 'ai'];
            } catch (\Throwable $e) {
                Errors::log('assistant AI mode failed, using FAQ mode: ' . $e->getMessage());
            }
        }
        return $faq + ['mode' => 'faq'];
    }

    /** @param list<array{role:string,content:string}> $history */
    private static function lastUser(array $history): string
    {
        for ($i = count($history) - 1; $i >= 0; $i--) {
            if (($history[$i]['role'] ?? '') === 'user') {
                return (string) $history[$i]['content'];
            }
        }
        return '';
    }

    /** @param array<string,mixed> $doc @return array{label:string,url:string} */
    private static function link(array $doc): array
    {
        return ['label' => mb_substr(preg_replace('/\s*\((industry|case study)\)$/', '', (string) $doc['title']) ?? (string) $doc['title'], 0, 60), 'url' => (string) $doc['url']];
    }

    public static function shorten(string $text, int $max): string
    {
        $text = trim((string) preg_replace('/\s+/', ' ', $text));
        if (mb_strlen($text) <= $max) {
            return $text;
        }
        $cut = mb_substr($text, 0, $max);
        $dot = max((int) mb_strrpos($cut, '. '), (int) mb_strrpos($cut, '! '), (int) mb_strrpos($cut, '? '));
        return $dot > $max * 0.5 ? mb_substr($cut, 0, $dot + 1) : rtrim($cut, " ,;:") . '…';
    }
}
