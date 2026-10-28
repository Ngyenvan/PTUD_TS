<?php
declare(strict_types=1);
require dirname(__DIR__).'/app/bootstrap.php';
if (session_status()!==PHP_SESSION_ACTIVE)session_start();
$input=[
 'user'=>['id'=>7,'role'=>'candidate'],
 'candidate'=>['id'=>8,'full_name'=>'Thí sinh mẫu','code'=>'DEMO','thcs_name'=>'THCS mẫu','profile_status'=>'valid'],
 'exams'=>[['id'=>1,'name'=>'Kỳ demo','code'=>'DEMO']],
 'exam'=>['id'=>1,'name'=>'Kỳ demo','code'=>'DEMO','register_from'=>'2026-10-26 08:00:00','register_to'=>'2026-11-02 17:00:00','edit_from'=>'2026-11-03 08:00:00','edit_to'=>'2026-11-05 17:00:00','status'=>'open'],
 'examId'=>1,'choices'=>[], 'decision'=>null,
 'schools'=>[['id'=>1,'name'=>'THPT A','district'=>'Gò Vấp'],['id'=>2,'name'=>'THPT B','district'=>'Tân Bình'],['id'=>3,'name'=>'THPT C','district'=>'Bình Thạnh']],
 'window'=>['mode'=>'register','message'=>'Đang đăng ký'], 'draft'=>null
];
// View::render() vẫn dùng layout thật; không cần kết nối CSDL khi chưa login.
ob_start();
\App\Core\View::render('preferences/index',$input);
$out=ob_get_clean();
foreach(['Đăng ký nguyện vọng','name="nv1"','name="nv2"','name="nv3"','value="no"','_csrf','preferences.css'] as $expect){
 if(strpos($out,$expect)===false)throw new RuntimeException('Missing: '.$expect);
}
echo "PASS rendered candidate registration form with real layout and CSRF fields.\n";
