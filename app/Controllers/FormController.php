<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Db;
use App\Core\Errors;
use App\Core\Mailer;
use App\Core\RateLimiter;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Core\Csrf;
use App\Models\Content;

/** Public lead forms: contact, quote, job application. */
final class FormController extends Controller
{
    public const QUOTE_TYPES = ['Custom business system', 'Website', 'Web application', 'Mobile app', 'Integration / API', 'Other'];
    public const QUOTE_BUDGETS = ['Under 250,000', '250,000 – 1M', '1M – 5M', 'Over 5M', 'Not sure yet'];
    public const QUOTE_TIMELINES = ['As soon as possible', '1–3 months', '3–6 months', 'Flexible'];

    /** @return array{0:int,1:string}|null  [http status, message] when the request must be rejected */
    private static function guard(Request $r): ?array
    {
        if (!Csrf::verify($r)) {
            return [419, 'Your session expired. Please reload the page and try again.'];
        }
        if ($r->input('website') !== '') {
            return [400, "Couldn't send that. Please try again."];
        }
        if (RateLimiter::tooMany('form', $r->ip(), 8, 900)) {
            return [429, 'Too many submissions from your connection. Please wait a few minutes and try again.'];
        }
        return null;
    }

    /** @param array<string,mixed> $clean @return array<string,mixed> */
    private static function present(array $clean): array
    {
        return array_filter($clean, static fn ($v): bool => $v !== null && $v !== '');
    }

    /** @param array<string,mixed> $data */
    private static function emailBody(string $heading, array $data): string
    {
        $lines = [$heading, str_repeat('-', 40)];
        foreach ($data as $k => $v) {
            if ($v === null || $v === '') {
                continue;
            }
            $lines[] = ucfirst(str_replace('_', ' ', (string) $k)) . ': ' . (is_array($v) ? implode(', ', $v) : (string) $v);
        }
        return implode("\n", $lines) . "\n";
    }

    public static function contact(Request $r): Response
    {
        $names = ['name', 'email', 'phone', 'company', 'subject', 'message'];
        $rules = [
            'name' => ['label' => 'Your name', 'required' => true],
            'email' => ['type' => 'email', 'required' => true, 'label' => 'Email'],
            'phone' => ['type' => 'tel', 'label' => 'Phone'],
            'company' => ['label' => 'Company'],
            'subject' => ['label' => 'Subject'],
            'message' => ['type' => 'textarea', 'required' => true, 'label' => 'Message'],
        ];
        $old = self::old($r, $names);
        if (($g = self::guard($r)) !== null) {
            return PageController::contact($r, ['old' => $old, 'formError' => $g[1]], $g[0]);
        }
        [$clean, $errors] = Validator::validate($r->post(), $rules);
        if ($errors !== []) {
            return PageController::contact($r, ['old' => $old, 'errors' => $errors], 422);
        }
        try {
            RateLimiter::hit('form', $r->ip());
            Db::insert('contact_messages', self::present($clean));
        } catch (\Throwable $e) {
            Errors::log('contact insert failed: ' . $e->getMessage());
            return PageController::contact($r, ['old' => $old, 'formError' => "Couldn't send that. Please try again."], 500);
        }
        Mailer::notifyStaff('New contact message: ' . ($clean['subject'] ?? $clean['name']), self::emailBody('New contact message', $clean), (string) $clean['email']);
        Session::flash('form_done', 'contact');
        return Response::redirect('/contact');
    }

    public static function quote(Request $r): Response
    {
        $names = ['name', 'email', 'phone', 'company', 'country', 'project_type', 'budget_range', 'timeline', 'details'];
        $rules = [
            'name' => ['label' => 'Your name', 'required' => true],
            'email' => ['type' => 'email', 'required' => true, 'label' => 'Email'],
            'phone' => ['type' => 'tel', 'label' => 'Phone'],
            'company' => ['label' => 'Company'],
            'country' => ['label' => 'Country'],
            'project_type' => ['type' => 'select', 'options' => self::QUOTE_TYPES, 'required' => true, 'label' => 'Project type'],
            'budget_range' => ['type' => 'select', 'options' => self::QUOTE_BUDGETS, 'label' => 'Budget'],
            'timeline' => ['type' => 'select', 'options' => self::QUOTE_TIMELINES, 'label' => 'Timeline'],
            'details' => ['type' => 'textarea', 'required' => true, 'label' => 'Project details'],
        ];
        $old = self::old($r, $names);
        if (($g = self::guard($r)) !== null) {
            return PageController::quote($r, ['old' => $old, 'formError' => $g[1]], $g[0]);
        }
        [$clean, $errors] = Validator::validate($r->post(), $rules);
        if ($errors !== []) {
            return PageController::quote($r, ['old' => $old, 'errors' => $errors], 422);
        }
        try {
            RateLimiter::hit('form', $r->ip());
            Db::insert('quote_requests', self::present($clean));
        } catch (\Throwable $e) {
            Errors::log('quote insert failed: ' . $e->getMessage());
            return PageController::quote($r, ['old' => $old, 'formError' => "Couldn't send that. Please try again."], 500);
        }
        Mailer::notifyStaff('New quote request from ' . $clean['name'], self::emailBody('New quote request', $clean), (string) $clean['email']);
        Session::flash('form_done', 'quote');
        return Response::redirect('/quote');
    }

    public static function apply(Request $r): Response
    {
        $names = ['name', 'email', 'phone', 'portfolio_url', 'cover_letter'];
        $rules = [
            'job_id' => ['type' => 'rel', 'required' => true, 'label' => 'Role'],
            'name' => ['label' => 'Full name', 'required' => true],
            'email' => ['type' => 'email', 'required' => true, 'label' => 'Email'],
            'phone' => ['type' => 'tel', 'label' => 'Phone'],
            'portfolio_url' => ['type' => 'url', 'label' => 'Portfolio / LinkedIn / CV link'],
            'cover_letter' => ['type' => 'textarea', 'label' => 'Why this role?'],
        ];
        $old = self::old($r, $names);
        $jobId = substr($r->input('job_id'), 0, 36);
        if (($g = self::guard($r)) !== null) {
            return PageController::careers($r, ['openJob' => $jobId, 'old' => $old, 'formError' => $g[1]], $g[0]);
        }
        [$clean, $errors] = Validator::validate($r->post(), $rules);
        $job = isset($errors['job_id']) ? null : Content::byId('job_openings', (string) $clean['job_id']);
        if ($job === null) {
            return PageController::careers($r, ['formError' => 'That role is no longer open.'], 404);
        }
        unset($errors['job_id']);
        if ($errors !== []) {
            return PageController::careers($r, ['openJob' => $jobId, 'old' => $old, 'errors' => $errors], 422);
        }
        try {
            RateLimiter::hit('form', $r->ip());
            Db::insert('job_applications', self::present($clean));
        } catch (\Throwable $e) {
            Errors::log('application insert failed: ' . $e->getMessage());
            return PageController::careers($r, ['openJob' => $jobId, 'old' => $old, 'formError' => "Couldn't send that. Please try again."], 500);
        }
        $data = $clean;
        unset($data['job_id']);
        Mailer::notifyStaff('New application: ' . $job['title'], self::emailBody('New job application for ' . $job['title'], $data), (string) $clean['email']);
        Session::flash('form_done', 'job:' . $job['id']);
        return Response::redirect('/careers');
    }
}
