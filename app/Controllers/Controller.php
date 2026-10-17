<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;

abstract class Controller
{
    /** @param array<string,mixed> $data @param array<string,string> $meta */
    protected static function page(string $view, array $data = [], array $meta = [], int $status = 200): Response
    {
        return Response::html(View::render($view, $data + ['meta' => $meta]), $status);
    }

    protected static function notFoundItem(string $heading, string $backUrl, string $backLabel, string $title): Response
    {
        return self::page('pages/not_found_item', [
            'heading' => $heading, 'backUrl' => $backUrl, 'backLabel' => $backLabel,
        ], ['title' => $title, 'noindex' => '1'], 404);
    }

    protected static function message(string $code, string $heading, string $text, int $status): Response
    {
        return Response::html(View::render('errors/message', [
            'code' => $code, 'heading' => $heading, 'text' => $text,
        ], 'layout/bare'), $status);
    }

    /** Returns an error response for an invalid CSRF token, or null when fine. */
    protected static function csrfFailure(Request $request): ?Response
    {
        if (Csrf::verify($request)) {
            return null;
        }
        return self::message('419', 'Session expired', 'Please go back, reload the page and try again.', 419);
    }

    /**
     * Raw (unvalidated) submitted strings for redisplay, limited to the given field names.
     * @param list<string> $names @return array<string,string>
     */
    protected static function old(Request $request, array $names): array
    {
        $out = [];
        foreach ($names as $n) {
            $out[$n] = mb_substr($request->input($n), 0, 5000);
        }
        return $out;
    }

    /** Notice shown at the top of the next portal/admin page. */
    protected static function notice(string $type, string $text): void
    {
        Session::flash('app_notice', ['type' => $type, 'text' => $text]);
    }

    /** Redirect to sign-in when logged out, 403 when lacking the role. Returns null when access is fine. */
    protected static function requireStaff(): ?Response
    {
        if (!Auth::check()) {
            return Response::redirect('/auth');
        }
        if (!Auth::isStaff()) {
            return self::message('403', 'Not allowed', "Your account doesn't have access to this area.", 403);
        }
        return null;
    }

    protected static function requireAdmin(): ?Response
    {
        if (($r = self::requireStaff()) !== null) {
            return $r;
        }
        if (!Auth::isAdmin()) {
            return self::message('403', 'Not allowed', 'This action needs an administrator.', 403);
        }
        return null;
    }

    protected static function requireLogin(): ?Response
    {
        return Auth::check() ? null : Response::redirect('/auth');
    }

    /** @param array<string,mixed> $data */
    protected static function app(string $area, string $view, array $data, string $title, int $status = 200): Response
    {
        $notice = Session::pull('app_notice');
        $content = View::render($view, $data, null);
        $html = View::render('layout/app', [
            'content' => $content,
            'area' => $area,
            'nav' => $area === 'admin' ? \App\Admin\Resources::nav() : self::portalNav(),
            'pageTitle' => $title,
            'notice' => is_array($notice) ? $notice : null,
        ], null);
        return Response::html($html, $status);
    }

    /** @return list<array{0:string,1:list<array{0:string,1:string}>}> */
    private static function portalNav(): array
    {
        return [['Client portal', [
            ['/portal', 'Overview'], ['/portal/projects', 'Projects'], ['/portal/invoices', 'Invoices'], ['/portal/tickets', 'Support'],
        ]]];
    }
}
