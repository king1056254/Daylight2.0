<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Assistant\Assistant;
use App\Core\Config;
use App\Core\Csrf;
use App\Core\Db;
use App\Core\Errors;
use App\Core\RateLimiter;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;

/** JSON endpoint behind the chat widget. Same-origin only, CSRF-protected, rate limited. */
final class AssistantController extends Controller
{
    private const MAX_MESSAGE = 500;
    private const MAX_HISTORY = 6;
    private const MAX_HISTORY_ITEM = 800;

    /** @param array<string,mixed> $data */
    private static function json(array $data, int $status = 200): Response
    {
        $body = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        return new Response(is_string($body) ? $body : '{"error":"Encoding error"}', $status, [
            'Content-Type' => 'application/json; charset=utf-8',
            'Cache-Control' => 'no-store',
        ]);
    }

    /** @return list<array{role:string,content:string}> */
    private static function history(string $raw): array
    {
        if ($raw === '' || strlen($raw) > 12000) {
            return [];
        }
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return [];
        }
        $out = [];
        foreach (array_slice(array_values($decoded), -self::MAX_HISTORY) as $turn) {
            if (!is_array($turn)) {
                continue;
            }
            $role = $turn['role'] ?? '';
            $content = $turn['content'] ?? '';
            if (($role === 'user' || $role === 'assistant') && is_string($content)) {
                $clean = Validator::clean($content, true);
                if ($clean !== '') {
                    $out[] = ['role' => $role, 'content' => mb_substr($clean, 0, self::MAX_HISTORY_ITEM)];
                }
            }
        }
        return $out;
    }

    public static function handleMessage(Request $r): Response
    {
        if (!Config::bool('ASSISTANT_ENABLED', true)) {
            return self::json(['error' => 'The assistant is switched off.'], 404);
        }
        if (!Csrf::verify($r)) {
            return self::json(['error' => 'Your session expired. Please reload the page and try again.'], 419);
        }
        if (RateLimiter::tooMany('assistant', $r->ip(), 20, 600)) {
            return self::json(['error' => 'You are sending messages quickly. Please wait a moment and try again.'], 429);
        }
        $message = Validator::clean($r->input('message'), false);
        if ($message === '') {
            return self::json(['error' => 'Please type a question first.'], 422);
        }
        if (mb_strlen($message) > self::MAX_MESSAGE) {
            return self::json(['error' => 'Please keep your question under ' . self::MAX_MESSAGE . ' characters.'], 422);
        }
        $history = self::history($r->input('history'));
        try {
            RateLimiter::hit('assistant', $r->ip());
            $result = Assistant::reply($message, $history);
        } catch (\Throwable $e) {
            Errors::log('assistant failed: ' . $e->getMessage());
            return self::json(['error' => "Sorry, I couldn't answer that just now. Please try again or contact the team."], 500);
        }
        if (Config::bool('ASSISTANT_LOG_CHATS')) {
            try {
                $session = RateLimiter::subject(Csrf::token());
                Db::insert('assistant_messages', ['session_hash' => $session, 'role' => 'user', 'content' => $message, 'mode' => $result['mode'], 'matched' => $result['matched'] ? 1 : 0]);
                if (random_int(1, 50) === 1) {
                    Db::exec('DELETE FROM assistant_messages WHERE created_at < (NOW() - INTERVAL 90 DAY)');
                }
                Db::insert('assistant_messages', ['session_hash' => $session, 'role' => 'assistant', 'content' => $result['answer'], 'mode' => $result['mode'], 'matched' => $result['matched'] ? 1 : 0]);
            } catch (\Throwable $e) {
                Errors::log('assistant log failed: ' . $e->getMessage());
            }
        }
        $links = [];
        foreach ($result['links'] as $l) {
            if (preg_match('~^/[A-Za-z0-9_\-/#.]*$~', $l['url']) === 1) {
                $links[] = ['label' => $l['label'], 'url' => url($l['url'])];
            }
        }
        return self::json(['answer' => $result['answer'], 'links' => $links, 'mode' => $result['mode'], 'matched' => $result['matched']]);
    }
}
