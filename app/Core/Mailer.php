<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Minimal authenticated-SMTP mailer. Every message is recorded in email_outbox first.
 * STATUS: implemented, NOT tested against a real SMTP server (no credentials/network yet).
 */
final class Mailer
{
    public static function enabled(): bool
    {
        return Config::bool('MAIL_ENABLED') && (Config::get('SMTP_HOST', '') ?? '') !== '';
    }

    /** Sends (or records as skipped/failed). Returns true only if the SMTP server accepted the message. */
    public static function send(string $to, string $subject, string $body, ?string $replyTo = null): bool
    {
        $to = self::cleanHeader($to);
        $subject = self::cleanHeader($subject);
        $outboxId = self::record($to, $subject, $body, 'queued', null);
        if (filter_var($to, FILTER_VALIDATE_EMAIL) === false) {
            self::mark($outboxId, 'failed', 'Invalid recipient address');
            return false;
        }
        if (!self::enabled()) {
            self::mark($outboxId, 'skipped', 'Email is not enabled (MAIL_ENABLED / SMTP_HOST)');
            return false;
        }
        try {
            self::smtp($to, $subject, $body, $replyTo !== null ? self::cleanHeader($replyTo) : null);
            self::mark($outboxId, 'sent', null);
            return true;
        } catch (\Throwable $e) {
            Errors::log('Mail send failed: ' . $e->getMessage());
            self::mark($outboxId, 'failed', mb_substr($e->getMessage(), 0, 400));
            return false;
        }
    }

    /** Notify the configured staff address. Failure never breaks the caller. */
    public static function notifyStaff(string $subject, string $body, ?string $replyTo = null): void
    {
        $to = Config::get('MAIL_NOTIFY_TO', '') ?? '';
        if ($to === '') {
            return;
        }
        try {
            self::send($to, $subject, $body, $replyTo);
        } catch (\Throwable $e) {
            Errors::log('notifyStaff failed: ' . $e->getMessage());
        }
    }

    /** Absolute URL for emails. APP_URL must be the origin only (https://host). Returns null when it is not configured (never trusts the Host header). */
    public static function absoluteUrl(string $path): ?string
    {
        $base = rtrim(Config::get('APP_URL', '') ?? '', '/');
        if ($base === '' || preg_match('#^https?://[A-Za-z0-9.\-:]+$#', $base) !== 1) {
            return null;
        }
        return $base . url($path);
    }

    public static function cleanHeader(string $s): string
    {
        return trim((string) preg_replace('/[\r\n\0]+/', ' ', $s));
    }

    private static function record(string $to, string $subject, string $body, string $status, ?string $error): int
    {
        Db::exec('INSERT INTO email_outbox (to_email, subject, body, status, error) VALUES (?, ?, ?, ?, ?)', [
            mb_substr($to, 0, 254), mb_substr($subject, 0, 250), $body, $status, $error,
        ]);
        return (int) Db::pdo()->lastInsertId();
    }

    private static function mark(int $id, string $status, ?string $error): void
    {
        Db::exec('UPDATE email_outbox SET status = ?, error = ?, attempts = attempts + 1, sent_at = ' . ($status === 'sent' ? 'NOW()' : 'NULL') . ' WHERE id = ?', [$status, $error, $id]);
    }

    private static function smtp(string $to, string $subject, string $body, ?string $replyTo): void
    {
        $host = Config::get('SMTP_HOST', '') ?? '';
        $port = Config::int('SMTP_PORT', 587);
        $secure = strtolower(Config::get('SMTP_SECURE', 'tls') ?? 'tls'); // tls (STARTTLS) | ssl | none
        $user = Config::get('SMTP_USER', '') ?? '';
        $pass = Config::get('SMTP_PASS', '') ?? '';
        $from = self::cleanHeader(Config::get('MAIL_FROM', '') ?? '');
        $fromName = self::cleanHeader(Config::get('MAIL_FROM_NAME', 'Daylight') ?? 'Daylight');
        if (filter_var($from, FILTER_VALIDATE_EMAIL) === false) {
            throw new \RuntimeException('MAIL_FROM is not a valid address');
        }
        $appHost = parse_url(Config::get('APP_URL', '') ?? '', PHP_URL_HOST);
        $helo = is_string($appHost) && $appHost !== '' ? $appHost : 'localhost';

        $remote = ($secure === 'ssl' ? 'ssl://' : 'tcp://') . $host . ':' . $port;
        $fp = @stream_socket_client($remote, $errno, $errstr, 10);
        if ($fp === false) {
            throw new \RuntimeException('SMTP connection failed (' . (int) $errno . ')');
        }
        stream_set_timeout($fp, 10);
        try {
            self::expect($fp, [220]);
            self::cmd($fp, 'EHLO ' . $helo, [250]);
            if ($secure === 'tls') {
                self::cmd($fp, 'STARTTLS', [220]);
                if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    throw new \RuntimeException('STARTTLS negotiation failed');
                }
                self::cmd($fp, 'EHLO ' . $helo, [250]);
            }
            if ($user !== '') {
                self::cmd($fp, 'AUTH LOGIN', [334]);
                self::cmd($fp, base64_encode($user), [334]);
                self::cmd($fp, base64_encode($pass), [235]);
            }
            self::cmd($fp, 'MAIL FROM:<' . $from . '>', [250]);
            self::cmd($fp, 'RCPT TO:<' . $to . '>', [250, 251]);
            self::cmd($fp, 'DATA', [354]);

            $headers = [
                'Date: ' . gmdate('D, d M Y H:i:s') . ' +0000',
                'From: ' . self::encodeWord($fromName) . ' <' . $from . '>',
                'To: <' . $to . '>',
                'Subject: ' . self::encodeWord($subject),
                'Message-ID: <' . bin2hex(random_bytes(12)) . '@' . $helo . '>',
                'MIME-Version: 1.0',
                'Content-Type: text/plain; charset=UTF-8',
                'Content-Transfer-Encoding: base64',
            ];
            if ($replyTo !== null && filter_var($replyTo, FILTER_VALIDATE_EMAIL) !== false) {
                $headers[] = 'Reply-To: <' . $replyTo . '>';
            }
            $data = implode("\r\n", $headers) . "\r\n\r\n" . chunk_split(base64_encode($body), 76, "\r\n");
            $data = (string) preg_replace('/^\./m', '..', $data);
            fwrite($fp, $data . "\r\n.\r\n");
            self::expect($fp, [250]);
            @fwrite($fp, "QUIT\r\n");
        } finally {
            fclose($fp);
        }
    }

    private static function encodeWord(string $s): string
    {
        return preg_match('/^[\x20-\x7E]*$/', $s) === 1 ? $s : '=?UTF-8?B?' . base64_encode($s) . '?=';
    }

    /** @param resource $fp @param list<int> $codes */
    private static function cmd($fp, string $line, array $codes): void
    {
        fwrite($fp, $line . "\r\n");
        self::expect($fp, $codes);
    }

    /** @param resource $fp @param list<int> $codes */
    private static function expect($fp, array $codes): void
    {
        $reply = '';
        while (($line = fgets($fp, 1024)) !== false) {
            $reply .= $line;
            if (strlen($line) < 4 || $line[3] !== '-') {
                break;
            }
        }
        $code = (int) substr($reply, 0, 3);
        if (!in_array($code, $codes, true)) {
            throw new \RuntimeException('SMTP error: unexpected reply ' . ($code > 0 ? (string) $code : 'none'));
        }
    }
}
