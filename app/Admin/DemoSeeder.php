<?php
declare(strict_types=1);

namespace App\Admin;

use App\Core\Auth;
use App\Core\Db;

/** Dummy portal data for testing. Everything is marked DEMO so remove() can delete exactly this data. */
final class DemoSeeder
{
    public const EMAIL = 'demo.client@example.com';
    public const MARK = '[DEMO]';

    public static function exists(): bool
    {
        return Db::value('SELECT COUNT(*) FROM users WHERE email = ?', [self::EMAIL]) > 0;
    }

    /** @return array{email:string,password:string}|null null when demo data already exists */
    public static function load(): ?array
    {
        if (self::exists()) {
            return null;
        }
        $password = bin2hex(random_bytes(6));
        $uid = Db::insert('users', [
            'email' => self::EMAIL, 'password_hash' => Auth::hashPassword($password),
            'full_name' => 'Demo Client', 'email_verified_at' => gmdate('Y-m-d H:i:s'),
        ]);
        Db::insert('user_roles', ['user_id' => $uid, 'role' => 'client']);
        $client = Db::insert('clients', [
            'user_id' => $uid, 'company_name' => self::MARK . ' Mwangaza Traders Ltd', 'contact_name' => 'Demo Client',
            'email' => self::EMAIL, 'industry' => 'Retail', 'status' => 'Active', 'notes' => 'Dummy data for testing only.',
        ]);
        $d = static fn (string $rel): string => gmdate('Y-m-d', (int) strtotime($rel));

        $p1 = Db::insert('client_projects', [
            'client_id' => $client, 'name' => self::MARK . ' Inventory & POS System',
            'description' => 'Multi-branch point of sale with central inventory and daily sales reports.',
            'status' => 'In Progress', 'progress' => 60, 'start_date' => $d('-45 days'), 'expected_completion' => $d('+30 days'),
        ]);
        $p2 = Db::insert('client_projects', [
            'client_id' => $client, 'name' => self::MARK . ' Company Website', 'description' => 'Marketing website with quote form.',
            'status' => 'Completed', 'progress' => 100, 'start_date' => $d('-120 days'), 'expected_completion' => $d('-60 days'),
        ]);
        foreach ([
            ['Discovery & scoping', 'Completed', '-40 days', 1], ['Interface design', 'Completed', '-20 days', 2],
            ['Core build', 'In Progress', '+10 days', 3], ['User testing & launch', 'Pending', '+30 days', 4],
        ] as [$title, $status, $due, $order]) {
            Db::insert('project_milestones', ['project_id' => $p1, 'title' => $title, 'status' => $status, 'due_date' => $d($due), 'sort_order' => $order]);
        }
        Db::insert('project_documents', ['project_id' => $p1, 'title' => 'Project scope (demo)', 'file_path' => 'https://example.com/demo-scope.pdf', 'file_type' => 'PDF', 'visible_to_client' => 1]);
        Db::insert('project_documents', ['project_id' => $p1, 'title' => 'Internal estimate (demo, hidden from client)', 'file_path' => 'https://example.com/demo-internal.pdf', 'file_type' => 'PDF', 'visible_to_client' => 0]);
        Db::insert('project_messages', ['project_id' => $p1, 'sender_name' => 'Daylight Team', 'body' => 'Welcome to your portal. The first two milestones are complete; core build is under way.']);
        Db::insert('project_messages', ['project_id' => $p1, 'sender_id' => $uid, 'sender_name' => 'Demo Client', 'body' => 'Thanks! Can we add M-Pesa reconciliation to the reports?']);
        Db::insert('support_tickets', ['client_id' => $client, 'project_id' => $p2, 'subject' => 'Demo: contact form emails arrive late', 'description' => 'Dummy ticket for testing.', 'priority' => 'High', 'status' => 'Open']);

        self::invoice($client, $p1, 'INV-DEMO-0001', 'Sent', [['Discovery workshop', '1', '45000'], ['Development — phase 1', '1', '120000']], 50000.0);
        self::invoice($client, $p2, 'INV-DEMO-0002', 'Sent', [['Website design and build', '1', '80000']], 80000.0);
        self::invoice($client, $p1, 'INV-DEMO-0003', 'Draft', [['Development — phase 2', '1', '150000']], 0.0);
        return ['email' => self::EMAIL, 'password' => $password];
    }

    /** @return int number of demo clients removed */
    public static function remove(): int
    {
        $n = 0;
        foreach (Db::all('SELECT id FROM clients WHERE company_name LIKE ?', [self::MARK . '%']) as $c) {
            Db::exec('DELETE FROM clients WHERE id = ?', [$c['id']]); // cascades to projects, tickets, invoices, ...
            $n++;
        }
        Db::exec('DELETE FROM users WHERE email = ?', [self::EMAIL]);
        return $n;
    }

    /** @param list<array{0:string,1:string,2:string}> $items */
    private static function invoice(string $client, string $project, string $number, string $status, array $items, float $paid): void
    {
        $id = Db::insert('invoices', [
            'invoice_number' => $number, 'client_id' => $client, 'project_id' => $project, 'currency' => 'KES',
            'status' => $status, 'issue_date' => gmdate('Y-m-d', (int) strtotime('-14 days')), 'due_date' => gmdate('Y-m-d', (int) strtotime('+16 days')),
            'notes' => 'Dummy invoice for testing.',
        ]);
        foreach ($items as [$desc, $qty, $unit]) {
            Db::insert('invoice_items', [
                'invoice_id' => $id, 'description' => $desc, 'quantity' => $qty, 'unit_price' => $unit,
                'amount' => number_format((float) $qty * (float) $unit, 2, '.', ''),
            ]);
        }
        Hooks::recalcInvoice($id);
        if ($paid > 0) {
            Db::insert('payments', ['invoice_id' => $id, 'amount' => number_format($paid, 2, '.', ''), 'method' => 'M-Pesa', 'reference' => 'DEMO' . strtoupper(bin2hex(random_bytes(3)))]);
            Hooks::settleInvoice($id);
        }
    }
}
