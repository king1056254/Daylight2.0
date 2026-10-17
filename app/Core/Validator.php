<?php
declare(strict_types=1);

namespace App\Core;

final class Validator
{
    /**
     * @param array<string,mixed> $input
     * @param array<string,array<string,mixed>> $fields  name => ['type','label','required','max','options','min']
     * @return array{0:array<string,mixed>,1:array<string,string>}  [clean values, errors]
     */
    public static function validate(array $input, array $fields): array
    {
        $clean = [];
        $errors = [];
        foreach ($fields as $name => $def) {
            $type = (string) ($def['type'] ?? 'text');
            $label = (string) ($def['label'] ?? ucfirst(str_replace('_', ' ', $name)));
            $raw = $input[$name] ?? null;

            if ($type === 'bool') {
                $clean[$name] = ($raw !== null && $raw !== '' && $raw !== '0') ? 1 : 0;
                continue;
            }

            $multiline = in_array($type, ['textarea', 'list'], true);
            $val = is_string($raw) ? self::clean($raw, $multiline) : '';
            if ($val === '') {
                if (!empty($def['required'])) {
                    $errors[$name] = $label . ' is required.';
                }
                $clean[$name] = null;
                continue;
            }

            $max = (int) ($def['max'] ?? ($multiline ? 5000 : 300));
            if (mb_strlen($val) > $max) {
                $errors[$name] = $label . ' must be at most ' . $max . ' characters.';
                continue;
            }

            switch ($type) {
                case 'email':
                    if (mb_strlen($val) > 254 || filter_var($val, FILTER_VALIDATE_EMAIL) === false) {
                        $errors[$name] = 'Enter a valid email address.';
                        continue 2;
                    }
                    $val = strtolower($val);
                    break;
                case 'tel':
                    if (preg_match('/^[0-9+()\-\s.]{5,30}$/', $val) !== 1) {
                        $errors[$name] = 'Enter a valid phone number.';
                        continue 2;
                    }
                    break;
                case 'url':
                    $parts = filter_var($val, FILTER_VALIDATE_URL) !== false ? parse_url($val) : false;
                    if ($parts === false || !in_array(strtolower((string) ($parts['scheme'] ?? '')), ['http', 'https'], true)) {
                        $errors[$name] = 'Enter a valid web address starting with http:// or https://.';
                        continue 2;
                    }
                    break;
                case 'select':
                    if (!in_array($val, array_map('strval', (array) ($def['options'] ?? [])), true)) {
                        $errors[$name] = 'Choose a valid option for ' . $label . '.';
                        continue 2;
                    }
                    break;
                case 'int':
                    if (preg_match('/^-?\d{1,9}$/', $val) !== 1) {
                        $errors[$name] = $label . ' must be a whole number.';
                        continue 2;
                    }
                    $val = (int) $val;
                    if (isset($def['min']) && $val < (int) $def['min']) {
                        $errors[$name] = $label . ' must be at least ' . (int) $def['min'] . '.';
                        continue 2;
                    }
                    if (isset($def['maxval']) && $val > (int) $def['maxval']) {
                        $errors[$name] = $label . ' must be at most ' . (int) $def['maxval'] . '.';
                        continue 2;
                    }
                    break;
                case 'decimal':
                    if (preg_match('/^-?\d{1,10}(\.\d{1,2})?$/', $val) !== 1) {
                        $errors[$name] = $label . ' must be a number with up to 2 decimals.';
                        continue 2;
                    }
                    if (isset($def['min']) && (float) $val < (float) $def['min']) {
                        $errors[$name] = $label . ' must be at least ' . $def['min'] . '.';
                        continue 2;
                    }
                    break;
                case 'date':
                    $d = \DateTime::createFromFormat('!Y-m-d', $val);
                    if ($d === false || $d->format('Y-m-d') !== $val) {
                        $errors[$name] = 'Enter a valid date (YYYY-MM-DD).';
                        continue 2;
                    }
                    break;
                case 'datetime':
                    $d = \DateTime::createFromFormat('Y-m-d\TH:i', $val) ?: \DateTime::createFromFormat('Y-m-d H:i:s', $val) ?: \DateTime::createFromFormat('!Y-m-d', $val);
                    if ($d === false) {
                        $errors[$name] = 'Enter a valid date and time.';
                        continue 2;
                    }
                    $val = $d->format('Y-m-d H:i:s');
                    break;
                case 'slug':
                    if (preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $val) !== 1) {
                        $errors[$name] = 'Use lowercase letters, numbers and hyphens only.';
                        continue 2;
                    }
                    break;
                case 'rel':
                    if (preg_match('/^[0-9a-fA-F\-]{36}$/', $val) !== 1) {
                        $errors[$name] = 'Choose a valid ' . $label . '.';
                        continue 2;
                    }
                    break;
                case 'list':
                    $items = [];
                    foreach (preg_split('/\n/', $val) ?: [] as $line) {
                        $line = trim($line);
                        if ($line !== '') {
                            $items[] = mb_substr($line, 0, 300);
                        }
                    }
                    if (count($items) > 100) {
                        $errors[$name] = 'Too many lines in ' . $label . '.';
                        continue 2;
                    }
                    $val = $items;
                    break;
                default: // text, textarea
                    break;
            }
            $clean[$name] = $val;
        }
        return [$clean, $errors];
    }

    public static function clean(string $s, bool $multiline): string
    {
        $s = str_replace("\0", '', $s);
        $s = str_replace(["\r\n", "\r"], "\n", $s);
        $s = (string) preg_replace('/[\x01-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $s);
        if (!$multiline) {
            $s = (string) preg_replace('/\s+/u', ' ', $s);
        }
        return trim($s);
    }

    public static function slugify(string $s): string
    {
        $s = strtolower($s);
        $s = (string) preg_replace('/[^a-z0-9]+/', '-', $s);
        return trim($s, '-');
    }
}
