<?php
declare(strict_types=1);

spl_autoload_register(function (string $class): void {
    if (!str_starts_with($class, 'App\\')) return;
    $path = __DIR__ . '/' . str_replace('\\', '/', substr($class, 4)) . '.php';
    if (is_file($path)) require $path;
});
$GLOBALS['is4_config'] = require dirname(__DIR__) . '/config/app.php';
if (is_file(dirname(__DIR__) . '/config/local.php')) {
    $GLOBALS['is4_config'] = array_replace($GLOBALS['is4_config'], require dirname(__DIR__) . '/config/local.php');
}
function cfg(string $key): mixed { return $GLOBALS['is4_config'][$key] ?? null; }
function url(string $path = ''): string { return rtrim((string)cfg('base_path'), '/') . '/' . ltrim($path, '/'); }
function e(mixed $value): string { return htmlspecialchars((string)($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function csrf(): string {
    if (!isset($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(24));
    return $_SESSION['csrf'];
}
function field(string $name): string { return '<input type="hidden" name="_csrf" value="' . e(csrf()) . '"><input type="hidden" name="_form" value="' . e($name) . '">'; }
function flash(string $type, string $text): void { $_SESSION['flash'] = ['type'=>$type,'text'=>$text]; }
function redirect(string $path): never { header('Location: '.url($path), true, 303); exit; }
function input(string $key, string $default = ''): string { return trim((string)($_POST[$key] ?? $default)); }
function id(): string { return strtoupper(bin2hex(random_bytes(8))); }
function once(?string $value): string { return $value ?: ''; }
function fmt(mixed $value): string { return $value === null ? '—' : e($value); }
