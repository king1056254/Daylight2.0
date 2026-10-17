<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Db;
use App\Core\Errors;
use App\Core\Mailer;
use App\Core\RateLimiter;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;

/** Client portal. Every query is scoped to the clients linked to the signed-in user. */
final class PortalController extends Controller
{
    /** @return list<string> */
    private static function clientIds(): array
    {
        return array_column(Db::all('SELECT id FROM clients WHERE user_id = ?', [(string) Auth::id()]), 'id');
    }

    /** @param list<string> $ids */
    private static function in(array $ids): string
    {
        return implode(',', array_fill(0, count($ids), '?'));
    }

    /** @param list<string> $ids @return array<string,mixed>|null */
    private static function findProject(string $id, array $ids): ?array
    {
        if ($ids === []) {
            return null;
        }
        return Db::one('SELECT * FROM client_projects WHERE id = ? AND client_id IN (' . self::in($ids) . ')', array_merge([$id], $ids));
    }

    /** @param list<string> $ids @return array<string,mixed>|null */
    private static function findInvoice(string $id, array $ids): ?array
    {
        if ($ids === []) {
            return null;
        }
        return Db::one("SELECT * FROM invoices WHERE id = ? AND status <> 'Draft' AND client_id IN (" . self::in($ids) . ')', array_merge([$id], $ids));
    }

    private static function notFound(): Response
    {
        return self::message('404', 'Not found', "We couldn't find that record.", 404);
    }

    public static function dashboard(): Response
    {
        if (($g = self::requireLogin()) !== null) {
            return $g;
        }
        $ids = self::clientIds();
        $data = ['linked' => $ids !== [], 'projects' => [], 'invoices' => [], 'tickets' => [], 'outstanding' => [], 'openTickets' => 0];
        if ($ids !== []) {
            $in = self::in($ids);
            $data['projects'] = Db::all("SELECT * FROM client_projects WHERE client_id IN ($in) ORDER BY created_at DESC", $ids);
            $data['invoices'] = Db::all(
                "SELECT i.*, COALESCE((SELECT SUM(p.amount) FROM payments p WHERE p.invoice_id = i.id), 0) AS paid
                 FROM invoices i WHERE i.status <> 'Draft' AND i.client_id IN ($in) ORDER BY i.created_at DESC LIMIT 5",
                $ids
            );
            $data['tickets'] = Db::all("SELECT * FROM support_tickets WHERE client_id IN ($in) AND status IN ('Open','In Progress') ORDER BY created_at DESC LIMIT 5", $ids);
            $data['openTickets'] = (int) Db::value("SELECT COUNT(*) FROM support_tickets WHERE client_id IN ($in) AND status IN ('Open','In Progress')", $ids);
            $data['outstanding'] = Db::all(
                "SELECT i.currency, SUM(i.total - COALESCE((SELECT SUM(p.amount) FROM payments p WHERE p.invoice_id = i.id), 0)) AS due
                 FROM invoices i WHERE i.status NOT IN ('Draft','Paid','Cancelled') AND i.client_id IN ($in) GROUP BY i.currency",
                $ids
            );
        }
        return self::app('portal', 'portal/dashboard', $data, 'Overview — Client portal');
    }

    public static function projects(): Response
    {
        if (($g = self::requireLogin()) !== null) {
            return $g;
        }
        $ids = self::clientIds();
        $rows = $ids === [] ? [] : Db::all('SELECT * FROM client_projects WHERE client_id IN (' . self::in($ids) . ') ORDER BY created_at DESC', $ids);
        return self::app('portal', 'portal/projects', ['linked' => $ids !== [], 'projects' => $rows], 'Projects — Client portal');
    }

    /** @param array<string,string> $p */
    public static function project(Request $r, array $p, array $state = [], int $status = 200): Response
    {
        if (($g = self::requireLogin()) !== null) {
            return $g;
        }
        $project = self::findProject($p['id'], self::clientIds());
        if ($project === null) {
            return self::notFound();
        }
        return self::app('portal', 'portal/project', [
            'project' => $project,
            'milestones' => Db::all('SELECT * FROM project_milestones WHERE project_id = ? ORDER BY sort_order ASC, due_date ASC', [$project['id']]),
            'documents' => Db::all('SELECT * FROM project_documents WHERE project_id = ? AND visible_to_client = 1 ORDER BY created_at DESC', [$project['id']]),
            'messages' => Db::all('SELECT * FROM project_messages WHERE project_id = ? ORDER BY created_at ASC', [$project['id']]),
            'me' => Auth::id(),
            'error' => $state['error'] ?? '', 'old' => $state['old'] ?? '',
        ], (string) $project['name'] . ' — Client portal', $status);
    }

    /** @param array<string,string> $p */
    public static function postMessage(Request $r, array $p): Response
    {
        if (($g = self::requireLogin()) !== null) {
            return $g;
        }
        if (($c = self::csrfFailure($r)) !== null) {
            return $c;
        }
        $project = self::findProject($p['id'], self::clientIds());
        if ($project === null) {
            return self::notFound();
        }
        $body = Validator::clean($r->input('body'), true);
        $state = ['old' => mb_substr($body, 0, 5000)];
        if ($body === '' || mb_strlen($body) > 5000) {
            $state['error'] = $body === '' ? 'Write a message first.' : 'Messages can be at most 5000 characters.';
            return self::project($r, $p, $state, 422);
        }
        if (RateLimiter::tooMany('portal_msg', (string) Auth::id(), 20, 3600)) {
            $state['error'] = 'You are sending messages too quickly. Please wait a little.';
            return self::project($r, $p, $state, 429);
        }
        try {
            RateLimiter::hit('portal_msg', (string) Auth::id());
            $u = Auth::user();
            Db::insert('project_messages', [
                'project_id' => $project['id'], 'sender_id' => Auth::id(),
                'sender_name' => (string) ($u['full_name'] ?: $u['email']), 'body' => $body,
            ]);
        } catch (\Throwable $e) {
            Errors::log('portal message failed: ' . $e->getMessage());
            $state['error'] = "Couldn't send your message. Please try again.";
            return self::project($r, $p, $state, 500);
        }
        Mailer::notifyStaff('Client message on ' . $project['name'], "A client posted a message on project \"{$project['name']}\":\n\n{$body}\n");
        self::notice('success', 'Message sent.');
        return Response::redirect('/portal/projects/' . $project['id']);
    }

    public static function invoices(): Response
    {
        if (($g = self::requireLogin()) !== null) {
            return $g;
        }
        $ids = self::clientIds();
        $rows = $ids === [] ? [] : Db::all(
            "SELECT i.*, COALESCE((SELECT SUM(p.amount) FROM payments p WHERE p.invoice_id = i.id), 0) AS paid
             FROM invoices i WHERE i.status <> 'Draft' AND i.client_id IN (" . self::in($ids) . ') ORDER BY i.created_at DESC',
            $ids
        );
        return self::app('portal', 'portal/invoices', ['linked' => $ids !== [], 'invoices' => $rows], 'Invoices — Client portal');
    }

    /** @param array<string,string> $p */
    public static function invoiceDetail(Request $r, array $p): Response
    {
        if (($g = self::requireLogin()) !== null) {
            return $g;
        }
        $inv = self::findInvoice($p['id'], self::clientIds());
        if ($inv === null) {
            return self::notFound();
        }
        return self::app('portal', 'portal/invoice', [
            'invoice' => $inv,
            'client' => Db::one('SELECT company_name, contact_name FROM clients WHERE id = ?', [$inv['client_id']]),
            'items' => Db::all('SELECT * FROM invoice_items WHERE invoice_id = ? ORDER BY id', [$inv['id']]),
            'payments' => Db::all('SELECT * FROM payments WHERE invoice_id = ? ORDER BY paid_at ASC', [$inv['id']]),
        ], 'Invoice ' . $inv['invoice_number'] . ' — Client portal');
    }

    /** @param array<string,mixed> $state */
    public static function tickets(Request $r, array $p = [], array $state = [], int $status = 200): Response
    {
        if (($g = self::requireLogin()) !== null) {
            return $g;
        }
        $ids = self::clientIds();
        $in = $ids === [] ? '' : self::in($ids);
        return self::app('portal', 'portal/tickets', [
            'linked' => $ids !== [],
            'tickets' => $ids === [] ? [] : Db::all("SELECT * FROM support_tickets WHERE client_id IN ($in) ORDER BY created_at DESC", $ids),
            'projects' => $ids === [] ? [] : Db::all("SELECT id, name FROM client_projects WHERE client_id IN ($in) ORDER BY name", $ids),
            'errors' => $state['errors'] ?? [], 'old' => $state['old'] ?? [], 'error' => $state['error'] ?? '',
        ], 'Support — Client portal', $status);
    }

    public static function createTicket(Request $r): Response
    {
        if (($g = self::requireLogin()) !== null) {
            return $g;
        }
        if (($c = self::csrfFailure($r)) !== null) {
            return $c;
        }
        $ids = self::clientIds();
        $old = self::old($r, ['subject', 'description', 'priority', 'project_id']);
        if ($ids === []) {
            return self::tickets($r, [], ['error' => "Your account isn't linked to a client profile yet.", 'old' => $old], 403);
        }
        [$clean, $errors] = Validator::validate($r->post(), [
            'subject' => ['label' => 'Subject', 'required' => true, 'max' => 250],
            'description' => ['type' => 'textarea', 'label' => 'Description'],
            'priority' => ['type' => 'select', 'options' => ['Low', 'Normal', 'High'], 'label' => 'Priority'],
            'project_id' => ['type' => 'rel', 'label' => 'Project'],
        ]);
        $project = null;
        if (empty($errors['project_id']) && !empty($clean['project_id'])) {
            $project = self::findProject((string) $clean['project_id'], $ids);
            if ($project === null) {
                $errors['project_id'] = 'Choose one of your projects.';
            }
        }
        if ($errors !== []) {
            return self::tickets($r, [], ['errors' => $errors, 'old' => $old], 422);
        }
        if (RateLimiter::tooMany('portal_ticket', (string) Auth::id(), 10, 3600)) {
            return self::tickets($r, [], ['error' => 'Too many tickets in a short time. Please wait a little.', 'old' => $old], 429);
        }
        try {
            RateLimiter::hit('portal_ticket', (string) Auth::id());
            Db::insert('support_tickets', [
                'client_id' => $project !== null ? $project['client_id'] : $ids[0],
                'project_id' => $project['id'] ?? null,
                'subject' => $clean['subject'], 'description' => $clean['description'],
                'priority' => $clean['priority'] ?? 'Normal',
            ]);
        } catch (\Throwable $e) {
            Errors::log('ticket insert failed: ' . $e->getMessage());
            return self::tickets($r, [], ['error' => "Couldn't create the ticket. Please try again.", 'old' => $old], 500);
        }
        Mailer::notifyStaff('New support ticket: ' . $clean['subject'], "Priority: " . ($clean['priority'] ?? 'Normal') . "\n\n" . ($clean['description'] ?? '') . "\n");
        self::notice('success', 'Ticket created. We will reply as soon as we can.');
        return Response::redirect('/portal/tickets');
    }
}
