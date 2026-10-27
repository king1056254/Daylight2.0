<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Enforces the retention periods promised in the privacy policy. Runs on demand (CLI or the super-admin button),
 * because free hosts often have no scheduled jobs. Periods here MUST match config/legal_privacy.php.
 */
final class Retention
{
    /** table, timestamp column, amount, unit (whitelisted), description */
    public const RULES = [
        ['contact_messages', 'created_at', 2, 'YEAR', 'Contact messages older than 2 years'],
        ['quote_requests', 'created_at', 2, 'YEAR', 'Quote requests older than 2 years'],
        ['job_applications', 'created_at', 12, 'MONTH', 'Job applications older than 12 months'],
        ['assistant_messages', 'created_at', 90, 'DAY', 'Assistant chat logs older than 90 days'],
        ['email_outbox', 'created_at', 2, 'YEAR', 'Notification email logs older than 2 years'],
        ['rate_limits', 'created_at', 1, 'DAY', 'Abuse-prevention hashes older than 24 hours'],
        ['auth_tokens', 'expires_at', 30, 'DAY', 'Expired password-reset and confirmation tokens older than 30 days'],
    ];

    /** @return array<string,array{label:string,count:int}> */
    public static function preview(): array
    {
        $out = [];
        foreach (self::RULES as [$table, $col, $n, $unit, $label]) {
            $out[$table] = ['label' => $label, 'count' => (int) Db::value(self::sql('SELECT COUNT(*) FROM', $table, $col, $unit), [$n])];
        }
        return $out;
    }

    /** @return array<string,int> rows deleted per table */
    public static function purge(): array
    {
        $out = [];
        foreach (self::RULES as [$table, $col, $n, $unit]) {
            $out[$table] = Db::exec(self::sql('DELETE FROM', $table, $col, $unit), [$n]);
        }
        return $out;
    }

    private static function sql(string $verb, string $table, string $col, string $unit): string
    {
        if (!in_array($unit, ['DAY', 'MONTH', 'YEAR'], true)) {
            throw new \InvalidArgumentException('Bad unit');
        }
        return $verb . ' ' . Db::ident($table) . ' WHERE ' . Db::ident($col) . ' < (NOW() - INTERVAL ? ' . $unit . ')';
    }
}
