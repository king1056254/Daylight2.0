<?php
declare(strict_types=1);

namespace App\Core;

final class Response
{
    /** @param array<string,string> $headers */
    public function __construct(
        public string $body = '',
        public int $status = 200,
        public array $headers = []
    ) {
    }

    public static function html(string $html, int $status = 200): self
    {
        return new self($html, $status, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    /** Internal redirects only; $path is an app path such as "/contact". */
    public static function redirect(string $path, int $status = 303): self
    {
        if ($path === '' || $path[0] !== '/' || strncmp($path, '//', 2) === 0 || preg_match('/[\r\n]/', $path) === 1) {
            $path = '/';
        }
        return new self('', $status, ['Location' => url($path)]);
    }

    public function send(): void
    {
        if (!headers_sent()) {
            http_response_code($this->status);
            foreach ($this->headers as $name => $value) {
                header($name . ': ' . $value);
            }
        }
        echo $this->body;
    }
}
