<?php
declare(strict_types=1);
require dirname(__DIR__).'/app/bootstrap.php';
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('IS4MVC');
    session_set_cookie_params(['httponly'=>true,'samesite'=>'Lax','secure'=>!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off']);
    session_start();
}
csrf();
$router=new App\Core\Router();
require dirname(__DIR__).'/routes/web.php';
$router->dispatch();
