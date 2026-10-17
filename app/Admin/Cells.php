<?php
declare(strict_types=1);

namespace App\Admin;

/** Renders one value for the admin list/detail tables. Returns safe HTML. */
final class Cells
{
    /** @param array<string,mixed> $field @param array<string,array<string,string>> $relLabels */
    public static function render(array $field, mixed $value, array $relLabels = []): string
    {
        $type = (string) $field['type'];
        $name = (string) $field['name'];
        if ($value === null || $value === '') {
            return '<span class="muted">—</span>';
        }
        switch ($type) {
            case 'bool':
                return (int) $value === 1 ? 'Yes' : '<span class="muted">No</span>';
            case 'rel':
                return e($relLabels[$name][(string) $value] ?? '(missing)');
            case 'date':
            case 'datetime':
                return fmt_date((string) $value);
            case 'decimal':
                return e(number_format((float) $value, 2));
            case 'list':
                return e(implode(', ', as_list($value)));
            case 'select':
                return $name === 'status' ? status_pill((string) $value) : e($value);
            default:
                $s = (string) $value;
                return e(mb_strlen($s) > 80 ? mb_substr($s, 0, 77) . '…' : $s);
        }
    }
}
