<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

/** @var list<array{0:string,1:callable}> $GLOBALS['__tests'] */
$GLOBALS['__tests'] = [];
$GLOBALS['__failures'] = [];

function test(string $name, callable $fn): void
{
    $GLOBALS['__tests'][] = [$name, $fn];
}

function fail(string $message): void
{
    throw new \RuntimeException($message);
}

function assertTrue(bool $cond, string $msg = 'Expected true'): void
{
    if (!$cond) {
        fail($msg);
    }
}

function assertSame(mixed $expected, mixed $actual, string $msg = ''): void
{
    if ($expected !== $actual) {
        fail(($msg !== '' ? $msg . ': ' : '') . 'expected ' . var_export($expected, true) . ' got ' . var_export($actual, true));
    }
}

function assertContains(string $needle, string $haystack, string $msg = ''): void
{
    if (!str_contains($haystack, $needle)) {
        fail(($msg !== '' ? $msg . ': ' : '') . 'missing "' . $needle . '"');
    }
}

function assertNotContains(string $needle, string $haystack, string $msg = ''): void
{
    if (str_contains($haystack, $needle)) {
        fail(($msg !== '' ? $msg . ': ' : '') . 'unexpected "' . $needle . '"');
    }
}

function runTests(string $suite): int
{
    $pass = 0;
    $failed = 0;
    foreach ($GLOBALS['__tests'] as [$name, $fn]) {
        try {
            $fn();
            $pass++;
            echo "  PASS  {$name}\n";
        } catch (\Throwable $e) {
            $failed++;
            echo "  FAIL  {$name}\n        " . $e->getMessage() . "\n";
        }
    }
    echo "\n{$suite}: {$pass} passed, {$failed} failed\n";
    return $failed === 0 ? 0 : 1;
}
