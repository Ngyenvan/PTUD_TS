<?php
declare(strict_types=1);
/** Thêm quyền ngoại lệ theo tài khoản; không xóa hoặc thay đổi quyền hiện có. */
if (PHP_SAPI !== 'cli') {http_response_code(403);exit;}
require dirname(__DIR__).'/app/bootstrap.php';
use App\Core\DB;

try {
    DB::get()->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS user_permission_overrides (
 user_id INT NOT NULL, permission_id INT NOT NULL, effect ENUM('grant','revoke') NOT NULL,
 updated_by INT NOT NULL, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 PRIMARY KEY(user_id,permission_id),
 FOREIGN KEY(user_id) REFERENCES users(id),
 FOREIGN KEY(permission_id) REFERENCES permissions(id),
 FOREIGN KEY(updated_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL);
    echo "Hoàn tất: quyền ngoại lệ theo tài khoản đã sẵn sàng; dữ liệu cũ được giữ nguyên.\n";
} catch (Throwable $error) {
    fwrite(STDERR,'Lỗi nâng cấp phân quyền: '.$error->getMessage().PHP_EOL);
    exit(1);
}
