<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Core\Auth;use App\Core\View;
final class AuthController {
 public function form():void{if(Auth::check()) redirect('quan-tri');View::render('auth/login');}
 public function login():void{
  $login=input('login');$password=(string)($_POST['password']??'');
  if($login!==''&&Auth::login($login,$password)) redirect('quan-tri');
  flash('danger','Tên đăng nhập hoặc mật khẩu chưa đúng.');redirect('dang-nhap');
 }
 public function logout():void{Auth::logout();redirect('');}
}
