<?php
declare(strict_types=1);

namespace App\Admin;

use App\Core\Auth;
use App\Core\Db;

/** Named before/after hooks referenced from config/resources.php. */
final class Hooks
{
    /**
     * @param array<string,mixed> $clean @param array<string,mixed>|null $existing null on create
     * @return array<string,mixed>
     */
    public static function before(string $name, array $clean, ?array $existing): array
    {
        return match ($name) {
            'publishStamp' => self::publishStamp($clean, $existing),
            'invoiceNumber' => self::invoiceNumber($clean, $existing),
            'invoiceDefaults' => self::invoiceDefaults($clean, $existing),
            'itemAmount' => self::itemAmount($clean),
            'paidAt' => self::paidAt($clean, $existing),
            'uploader' => $existing === null ? $clean + ['uploaded_by' => Auth::id()] : $clean,
            'sender' => self::sender($clean, $existing),
            default => throw new \InvalidArgumentException('Unknown hook ' . $name),
        };
    }

    /** @param array<string,mixed> $row @param array<string,mixed>|null $old */
    public static function after(string $name, array $row, ?array $old): void
    {
        switch ($name) {
            case 'recalcInvoiceFromRow':
                self::recalcInvoice((string) $row['id']);
                break;
            case 'recalcInvoiceFromItem':
                self::recalcInvoice((string) $row['invoice_id']);
                if ($old !== null && (string) $old['invoice_id'] !== (string) $row['invoice_id']) {
                    self::recalcInvoice((string) $old['invoice_id']);
                }
                break;
            case 'settleInvoice':
                self::settleInvoice((string) $row['invoice_id']);
                if ($old !== null && (string) $old['invoice_id'] !== (string) $row['invoice_id']) {
                    self::settleInvoice((string) $old['invoice_id']);
                }
                break;
            default:
                throw new \InvalidArgumentException('Unknown hook ' . $name);
        }
    }

    /** @param array<string,mixed> $clean @param array<string,mixed>|null $existing @return array<string,mixed> */
    private static function publishStamp(array $clean, ?array $existing): array
    {
        if (!empty($clean['published']) && empty($clean['published_at']) && empty($existing['published_at'])) {
            $clean['published_at'] = gmdate('Y-m-d H:i:s');
        }
        return $clean;
    }

    /** @param array<string,mixed> $clean @param array<string,mixed>|null $existing @return array<string,mixed> */
    private static function invoiceNumber(array $clean, ?array $existing): array
    {
        if (!empty($clean['invoice_number'])) {
            return $clean;
        }
        if ($existing !== null) {
            $clean['invoice_number'] = $existing['invoice_number'];
            return $clean;
        }
        $prefix = 'INV-' . gmdate('Y') . '-';
        $max = (int) Db::value('SELECT MAX(CAST(SUBSTRING(invoice_number, ?) AS UNSIGNED)) FROM invoices WHERE invoice_number LIKE ?', [strlen($prefix) + 1, $prefix . '%']);
        do {
            $max++;
            $candidate = $prefix . str_pad((string) $max, 4, '0', STR_PAD_LEFT);
        } while (Db::value('SELECT COUNT(*) FROM invoices WHERE invoice_number = ?', [$candidate]) > 0);
        $clean['invoice_number'] = $candidate;
        return $clean;
    }

    /** @param array<string,mixed> $clean @param array<string,mixed>|null $existing @return array<string,mixed> */
    private static function invoiceDefaults(array $clean, ?array $existing): array
    {
        if ($existing === null && empty($clean['issue_date'])) {
            $clean['issue_date'] = gmdate('Y-m-d');
        }
        return $clean;
    }

    /** @param array<string,mixed> $clean @return array<string,mixed> */
    private static function itemAmount(array $clean): array
    {
        $clean['amount'] = number_format(round((float) $clean['quantity'] * (float) $clean['unit_price'], 2), 2, '.', '');
        return $clean;
    }

    /** @param array<string,mixed> $clean @param array<string,mixed>|null $existing @return array<string,mixed> */
    private static function paidAt(array $clean, ?array $existing): array
    {
        if (empty($clean['paid_at']) && $existing === null) {
            $clean['paid_at'] = gmdate('Y-m-d H:i:s');
        }
        return $clean;
    }

    /** @param array<string,mixed> $clean @param array<string,mixed>|null $existing @return array<string,mixed> */
    private static function sender(array $clean, ?array $existing): array
    {
        if ($existing === null) {
            $u = Auth::user();
            $clean['sender_id'] = Auth::id();
            $clean['sender_name'] = $u !== null ? (string) ($u['full_name'] ?: 'Daylight') : 'Daylight';
        }
        return $clean;
    }

    public static function recalcInvoice(string $invoiceId): void
    {
        $sub = (string) Db::value('SELECT COALESCE(SUM(amount), 0) FROM invoice_items WHERE invoice_id = ?', [$invoiceId]);
        Db::exec('UPDATE invoices SET subtotal = ?, total = ? + tax WHERE id = ?', [$sub, $sub, $invoiceId]);
        self::settleInvoice($invoiceId);
    }

    /** Marks Sent/Overdue invoices Paid when fully paid; reopens Paid ones that are no longer fully paid. */
    public static function settleInvoice(string $invoiceId): void
    {
        $inv = Db::one('SELECT total, status FROM invoices WHERE id = ?', [$invoiceId]);
        if ($inv === null) {
            return;
        }
        $paid = (float) Db::value('SELECT COALESCE(SUM(amount), 0) FROM payments WHERE invoice_id = ?', [$invoiceId]);
        $total = (float) $inv['total'];
        if ($total > 0 && $paid + 0.001 >= $total && in_array($inv['status'], ['Sent', 'Overdue'], true)) {
            Db::exec("UPDATE invoices SET status = 'Paid' WHERE id = ?", [$invoiceId]);
        } elseif ($inv['status'] === 'Paid' && $paid + 0.001 < $total) {
            Db::exec("UPDATE invoices SET status = 'Sent' WHERE id = ?", [$invoiceId]);
        }
    }
}
