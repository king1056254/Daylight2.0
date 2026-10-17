<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Admin\DemoSeeder;
use App\Core\Auth;
use App\Core\Config;
use App\Core\Db;
use App\Core\Errors;
use App\Core\Request;
use App\Core\Response;

final class AdminController extends Controller
{
    public static function dashboard(): Response
    {
        if (($g = self::requireStaff()) !== null) {
            return $g;
        }
        $count = static fn (string $sql): int => (int) Db::value($sql);
        $stats = [
            ['New contact messages', $count("SELECT COUNT(*) FROM contact_messages WHERE status = 'New'"), '/admin/messages?q=New'],
            ['New quote requests', $count("SELECT COUNT(*) FROM quote_requests WHERE status = 'New'"), '/admin/quotes?q=New'],
            ['New applications', $count("SELECT COUNT(*) FROM job_applications WHERE status = 'Received'"), '/admin/applications?q=Received'],
            ['Open tickets', $count("SELECT COUNT(*) FROM support_tickets WHERE status IN ('Open','In Progress')"), '/admin/tickets'],
            ['Active clients', $count("SELECT COUNT(*) FROM clients WHERE status = 'Active'"), '/admin/clients'],
            ['Unpaid invoices', $count("SELECT COUNT(*) FROM invoices WHERE status IN ('Sent','Overdue')"), '/admin/invoices'],
        ];
        $demoAllowed = Config::bool('ALLOW_DEMO_DATA') && Auth::isSuperAdmin();
        return self::app('admin', 'admin/dashboard', [
            'stats' => $stats,
            'recentMessages' => Db::all('SELECT id, name, subject, status, created_at FROM contact_messages ORDER BY created_at DESC LIMIT 5'),
            'recentQuotes' => Db::all('SELECT id, name, project_type, status, created_at FROM quote_requests ORDER BY created_at DESC LIMIT 5'),
            'failedEmails' => $count("SELECT COUNT(*) FROM email_outbox WHERE status = 'failed'"),
            'skippedEmails' => $count("SELECT COUNT(*) FROM email_outbox WHERE status = 'skipped'"),
            'demoAllowed' => $demoAllowed,
            'demoExists' => $demoAllowed && DemoSeeder::exists(),
        ], 'Dashboard — Admin');
    }

    public static function demo(Request $r): Response
    {
        if (($g = self::requireStaff()) !== null) {
            return $g;
        }
        if (!Auth::isSuperAdmin() || !Config::bool('ALLOW_DEMO_DATA')) {
            return self::message('403', 'Not allowed', 'Demo data is disabled on this server.', 403);
        }
        if (($c = self::csrfFailure($r)) !== null) {
            return $c;
        }
        try {
            if ($r->input('action') === 'remove') {
                $n = DemoSeeder::remove();
                self::notice('success', "Demo data removed ({$n} demo client" . ($n === 1 ? '' : 's') . ').');
            } else {
                $creds = DemoSeeder::load();
                if ($creds === null) {
                    self::notice('error', 'Demo data already exists. Remove it first to load it again.');
                } else {
                    self::notice('success', "Demo data loaded. Sign in to the portal with {$creds['email']} and password {$creds['password']} — this password is shown only once.");
                }
            }
        } catch (\Throwable $e) {
            Errors::log('demo data failed: ' . $e->getMessage());
            self::notice('error', "Couldn't change the demo data. Check the logs.");
        }
        return Response::redirect('/admin');
    }
}
