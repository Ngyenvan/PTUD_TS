<?php
declare(strict_types=1);
namespace App\Core;
final class View {
    public static function render(string $name,array $data=[]):void {
        extract($data, EXTR_SKIP);
        ob_start(); require dirname(__DIR__).'/Views/'.$name.'.php'; $body=ob_get_clean();
        require dirname(__DIR__).'/Views/partials/layout.php';
    }
}
