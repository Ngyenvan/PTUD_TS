<?php
declare(strict_types=1);
/** Tài khoản thử ĐĂNG KÝ LẦN ĐẦU, tách biệt 6 học sinh có sẵn; chỉ chạy CLI. */
if (PHP_SAPI!=='cli') {http_response_code(403);exit;}
require dirname(__DIR__).'/app/bootstrap.php';
use App\Core\DB;

$apply=in_array('--apply',$argv,true);
$login='thisinh.nv.is4';
$code='IS4-NV07';
try {
    $existing=DB::one('SELECT id FROM users WHERE login=?',[$login]);
    $candidate=DB::one('SELECT id FROM candidates WHERE code=?',[$code]);
    if ($existing || $candidate) {
        echo "Đã tồn tại $login hoặc $code; không thay đổi mật khẩu và hồ sơ.\n";
        exit(0);
    }
    $role=DB::one("SELECT id FROM roles WHERE code='candidate'");
    $school=DB::one("SELECT id FROM schools WHERE code='THCS-A' AND type='THCS'");
    if (!$role || !$school) throw new RuntimeException('Chưa có vai trò Thí sinh hoặc trường THCS-A; kiểm tra cài đặt.');
    if (!DB::one('SELECT id FROM permissions WHERE code=?',['preferences.manage'])) {
        throw new RuntimeException('Chạy tools/upgrade-preferences.php trước.');
    }
    if (!$apply) {
        echo "SẼ TẠO tài khoản $login (mật khẩu ngẫu nhiên) và hồ sơ mới $code, không tạo NV/điểm/phách.\n";
        echo "Không ghi dữ liệu. Để xác nhận chạy: php tools/create-preference-demo.php --apply\n";
        exit(0);
    }
    $password=bin2hex(random_bytes(14)).'Aa!';
    DB::tx(function() use($login,$code,$role,$school,$password) {
        DB::run('INSERT INTO users(login,password_hash,full_name,role_id) VALUES (?,?,?,?)',[$login,password_hash($password,PASSWORD_DEFAULT),'Học sinh thử đăng ký NV 07',$role['id']]);
        $userId=(int)DB::get()->lastInsertId();
        DB::run('INSERT INTO candidates(code,full_name,birth_date,thcs_id,user_id,profile_status) VALUES (?,?,?,?,?,?)',[$code,'Học sinh thử đăng ký NV 07','2011-05-12',$school['id'],$userId,'valid']);
    });
    echo "Tạo thành công tài khoản '$login' và hồ sơ '$code'.\n";
    echo "Mật khẩu (chỉ xuất hiện lần này): $password\n";
    echo "Không có nguyện vọng, bài thi, điểm hay kết quả nào được tạo.\n";
} catch (Throwable $ex) {
    fwrite(STDERR,'Không thể tạo tài khoản demo: '.$ex->getMessage().PHP_EOL);exit(1);
}
