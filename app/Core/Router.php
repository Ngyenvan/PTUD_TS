<?php
declare(strict_types=1);
namespace App\Core;
final class Router {
    private array $routes=[];
    public function get(string $uri,callable $action):void {$this->routes['GET'][$uri]=$action;}
    public function post(string $uri,callable $action):void {$this->routes['POST'][$uri]=$action;}
    public function dispatch():void {
        $uri=parse_url($_SERVER['REQUEST_URI']??'/',PHP_URL_PATH)?:'/';
        $base=rtrim((string)cfg('base_path'),'/');
        if($base!=='' && str_starts_with($uri,$base.'/')) $uri=substr($uri,strlen($base));
        elseif($uri===$base) $uri='/';
        $uri='/'.trim($uri,'/');
        if($uri==='//') $uri='/';
        $method=$_SERVER['REQUEST_METHOD']??'GET';
        if($method==='POST' && !hash_equals(csrf(),(string)($_POST['_csrf']??''))) { http_response_code(419); View::render('partials/error',['message'=>'Phiên xác nhận không hợp lệ (CSRF). Tải lại trang và thử lại.','code'=>419]); return; }
        $action=$this->routes[$method][$uri]??null;
        if(!$action){http_response_code(404);View::render('partials/error',['message'=>'Trang bạn tìm không tồn tại.','code'=>404]); return;}
        try {$action();} catch (\Throwable $ex) {
            http_response_code(500);
            error_log('[IS4] '.$ex);
            View::render('partials/error',['message'=>'Không thực hiện được thao tác. Kiểm tra dữ liệu và quyền, hoặc xem log PHP.','code'=>500,'debug'=>cfg('app_debug')?$ex->getMessage():null]);
        }
    }
}
