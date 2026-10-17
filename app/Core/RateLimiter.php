<?php
declare(strict_types=1);

namespace App\Core;

/** Simple DB-backed limiter (no cron or cache server required). */
final class RateLimiter
{
    public static function subject(string $value): string
    {
        return hash('sha256', (Config::get('APP_KEY', '') ?? '') . '|' . strtolower($value));
    }

    public static function tooMany(string $bucket, string $subject, int $max, int $windowSeconds): bool
    {
        $n = (int) Db::value(
            'SELECT COUNT(*) FROM rate_limits WHERE bucket = ? AND subject_hash = ? AND created_at >= (NOW() - INTERVAL ? SECOND)',
            [$bucket, self::subject($subject), $windowSeconds]
        );
        return $n >= $max;
    }

    public static function hit(string $bucket, string $subject): void
    {
        Db::exec('INSERT INTO rate_limits (bucket, subject_hash) VALUES (?, ?)', [$bucket, self::subject($subject)]);
        if (random_int(1, 50) === 1) {
            Db::exec('DELETE FROM rate_limits WHERE created_at < (NOW() - INTERVAL 1 DAY)');
        }
    }
}
