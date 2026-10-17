<?php
declare(strict_types=1);

namespace App\Core;

final class Router
{
    /** @var list<array{0:string,1:string,2:callable}> */
    private array $routes = [];

    public function get(string $pattern, callable $handler): void
    {
        $this->add('GET', $pattern, $handler);
    }

    public function post(string $pattern, callable $handler): void
    {
        $this->add('POST', $pattern, $handler);
    }

    private function add(string $method, string $pattern, callable $handler): void
    {
        $regex = (string) preg_replace('#\{([a-z_]+)\}#', '(?P<$1>[A-Za-z0-9_\-]+)', $pattern);
        $this->routes[] = [$method, '#^' . $regex . '$#', $handler];
    }

    /**
     * @return array{0:callable,1:array<string,string>}|int  handler+params, or 404/405
     */
    public function match(string $method, string $path): array|int
    {
        $pathMatched = false;
        foreach ($this->routes as [$m, $regex, $handler]) {
            if (preg_match($regex, $path, $matches) === 1) {
                if ($m === $method || ($method === 'HEAD' && $m === 'GET')) {
                    $params = [];
                    foreach ($matches as $k => $v) {
                        if (is_string($k)) {
                            $params[$k] = $v;
                        }
                    }
                    return [$handler, $params];
                }
                $pathMatched = true;
            }
        }
        return $pathMatched ? 405 : 404;
    }

    public function dispatch(Request $request): Response
    {
        $result = $this->match($request->method(), $request->path());
        if ($result === 405) {
            return Response::html(View::render('errors/message', [
                'code' => '405',
                'heading' => 'Method not allowed',
                'text' => 'That action is not supported on this page.',
            ], 'layout/bare'), 405);
        }
        if ($result === 404) {
            return Response::html(View::render('errors/message', [
                'code' => '404',
                'heading' => 'Page not found',
                'text' => "The page you're looking for doesn't exist or has been moved.",
            ], 'layout/bare'), 404);
        }
        [$handler, $params] = $result;
        $out = $handler($request, $params);
        return $out instanceof Response ? $out : Response::html((string) $out);
    }
}
