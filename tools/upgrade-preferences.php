<?php
declare(strict_types=1);
/** Cập nhật CSDL đã cài đặt, chỉ thêm bảng và quyền mới, KHÔNG chạy install/seed lại. */
if (PHP_SAPI !== 'cli') {http_response_code(403);exit;}
require dirname(__DIR__).'/app/bootstrap.php';
use App\Core\DB;

try {
    $sql=<<<'SQL'
CREATE TABLE IF NOT EXISTS registration_choices (
 exam_id INT NOT NULL, candidate_id INT NOT NULL, will_take_exam TINYINT(1) NOT NULL,
 updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 PRIMARY KEY(exam_id,candidate_id),
 FOREIGN KEY(exam_id) REFERENCES exam_cycles(id),
 FOREIGN KEY(candidate_id) REFERENCES candidates(id),
 CONSTRAINT chk_registration_choice CHECK(will_take_exam IN (0,1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL;
    DB::get()->exec($sql);
    $existing=DB::one('SELECT id FROM permissions WHERE code=?',['preferences.manage']);
    if (!$existing) {
        DB::run('INSERT INTO permissions(code,label) VALUES (?,?)',['preferences.manage','Đăng ký / chỉnh sửa nguyện vọng']);
        $role=DB::one("SELECT id FROM roles WHERE code='candidate'");
        $permission=DB::one('SELECT id FROM permissions WHERE code=?',['preferences.manage']);
        if (!$role) throw new RuntimeException('Thiếu vai trò candidate trong cơ sở dữ liệu.');
        DB::run('INSERT IGNORE INTO role_permissions(role_id,permission_id) VALUES (?,?)',[(int)$role['id'],(int)$permission['id']]);
        echo "Đã tạo quyền preferences.manage và cấp lần đầu cho vai trò candidate.\n";
    } else {
        echo "Quyền preferences.manage đã tồn tại; không cấp lại quyền từng bị thu hồi.\n";
    }
    echo "Hoàn tất: registration_choices đã sẵn sàng. Không xóa hay thay đổi dữ liệu cũ.\n";
} catch (Throwable $error) {
    fwrite(STDERR,'Lỗi nâng cấp: '.$error->getMessage().PHP_EOL);
    exit(1);
}
