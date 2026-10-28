<?php
/** Chạy một lần: php tools/install.php. KHÔNG XÓA/ĐẶT LẠI bảng có sẵn. */
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
use App\Core\DB;
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('CLI only'); }
$conf=cfg('db_name');
if (!preg_match('/^[a-zA-Z0-9_]+$/', $conf)) die('Tên database không hợp lệ.');
try {
 $pdo=new PDO('mysql:host='.cfg('db_host').';port='.cfg('db_port').';charset=utf8mb4',cfg('db_user'),cfg('db_password'),[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
 $pdo->exec('CREATE DATABASE IF NOT EXISTS `'.$conf.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
} catch(Throwable $e) {die('Không kết nối được MariaDB. Kiểm tra config/local.php: '.$e->getMessage().PHP_EOL);}
// schema.sql gồm từng CREATE TABLE riêng, không chứa data; MySQL CREATE TABLE auto commit.
$schema=file_get_contents(__DIR__.'/../database/schema.sql');
foreach(explode(';',$schema) as $statement) {
 $statement=preg_replace('/^\s*--[^\r\n]*$/m','',$statement);
 if (trim($statement)!=='') DB::get()->exec($statement);
}
$roles=['admin'=>'Quản trị viên','admissions'=>'Cán bộ Sở','school_thcs'=>'Cán bộ THCS','school_thpt'=>'Cán bộ THPT','examiner'=>'Cán bộ khảo thí','candidate'=>'Thí sinh'];
$perms=['users.manage'=>'Quản lý người dùng','roles.manage'=>'Cấp/thu hồi quyền','exams.manage'=>'Quản lý kỳ thi','schools.manage'=>'Quản lý trường / chỉ tiêu','blind.manage'=>'Quản lý phách','blind.mapping'=>'Đối chiếu danh tính','scores.manage'=>'Nhập điểm theo phách','admissions.manage'=>'Xét tuyển','cutoffs.manage'=>'Xác định điểm chuẩn','results.publish'=>'Công bố kết quả','audit.view'=>'Nhật ký','profile.view'=>'Xem hồ sơ bản thân','school.profiles'=>'Hồ sơ THCS','school.results'=>'Kết quả THPT'];
foreach($roles as $code=>$label) DB::run('INSERT IGNORE INTO roles(code,label) VALUES (?,?)',[$code,$label]);
foreach($perms as $code=>$label) DB::run('INSERT IGNORE INTO permissions(code,label) VALUES (?,?)',[$code,$label]);
$policy=[
 'admin'=>array_keys($perms),
 'admissions'=>['exams.manage','schools.manage','admissions.manage','cutoffs.manage'],
 'school_thcs'=>['school.profiles'], 'school_thpt'=>['school.results'],
 'examiner'=>['blind.manage','scores.manage'], 'candidate'=>['profile.view']
];
if ((int)DB::one('SELECT COUNT(*) AS n FROM role_permissions')['n']===0) foreach($policy as $role=>$grants){
 $rid=DB::one('SELECT id FROM roles WHERE code=?',[$role])['id'];
 foreach($grants as $code){
   $pid=DB::one('SELECT id FROM permissions WHERE code=?',[$code])['id'];
   DB::run('INSERT IGNORE INTO role_permissions (role_id,permission_id) VALUES (?,?)',[$rid,$pid]);
 }
}
$examId=(int)(DB::one("SELECT id FROM exam_cycles WHERE code='IS4_DEMO_2026'")['id']??0);
if (!$examId){
 DB::run("INSERT INTO exam_cycles(code,name,year,register_from,register_to,edit_from,edit_to,exam_date,status) VALUES ('IS4_DEMO_2026','Tuyển sinh lớp 10 – Đợt minh họa',2026,'2026-10-26 08:00:00','2026-11-02 17:00:00','2026-11-03 08:00:00','2026-11-05 17:00:00','2026-11-12','draft')");
 $examId=(int)DB::get()->lastInsertId();
}
$schools=[['THCS-A','THCS Hoa Phượng','THCS','Gò Vấp'],['THCS-B','THCS Hướng Dương','THCS','Phú Nhuận'],['THPT-A','THPT Bình Minh','THPT','Gò Vấp'],['THPT-B','THPT Tương Lai','THPT','Tân Bình'],['THPT-C','THPT Ngôi Sao','THPT','Bình Thạnh']];
foreach($schools as [$code,$name,$type,$district]) DB::run('INSERT IGNORE INTO schools(code,name,type,district,address,description) VALUES (?,?,?,?,?,?)',[$code,$name,$type,$district,'TP. Hồ Chí Minh','Dữ liệu mô phỏng của nhóm IS4']);
$ids=[]; foreach($schools as [$code])$ids[$code]=(int)DB::one('SELECT id FROM schools WHERE code=?',[$code])['id'];
foreach(['THPT-A','THPT-B','THPT-C'] as $code) DB::run('INSERT IGNORE INTO quotas(exam_id,school_id,seats) VALUES (?,?,2)',[$examId,$ids[$code]]);
foreach(['P01','P02'] as $room) DB::run('INSERT IGNORE INTO rooms(exam_id,school_id,code,capacity) VALUES (?,?,?,3)',[$examId,$ids['THPT-A'],$room]);
$roomIds=[]; foreach(['P01','P02'] as $room)$roomIds[$room]=(int)DB::one('SELECT id FROM rooms WHERE exam_id=? AND code=?',[$examId,$room])['id'];
$grades=[9.5,9.0,8.5,8.0,7.5,7.0];
for($i=1;$i<=6;$i++){
 $code=sprintf('IS4-TS%02d',$i);
 DB::run('INSERT IGNORE INTO candidates(code,full_name,birth_date,thcs_id,profile_status) VALUES (?,?,?,?,?)',[$code,'Học sinh minh họa '.sprintf('%02d',$i),'2011-05-12',$ids[$i%2?'THCS-A':'THCS-B'],'valid']);
 $cid=(int)DB::one('SELECT id FROM candidates WHERE code=?',[$code])['id'];
 foreach(['THPT-A','THPT-B','THPT-C'] as $j=>$school) DB::run('INSERT IGNORE INTO preferences(exam_id,candidate_id,school_id,priority) VALUES (?,?,?,?)',[$examId,$cid,$ids[$school],$j+1]);
 DB::run('INSERT IGNORE INTO eligibility(exam_id,candidate_id,status,note) VALUES (?,?,?,?)',[$examId,$cid,'eligible','Đã duyệt dữ liệu demo, không phải quy định chính thức']);
 DB::run('INSERT IGNORE INTO allocations(exam_id,room_id,candidate_id,candidate_number) VALUES (?,?,?,?)',[$examId,$roomIds[$i<=3?'P01':'P02'], $cid,sprintf('SBD%04d',$i)]);
 $alloc=(int)DB::one('SELECT id FROM allocations WHERE exam_id=? AND candidate_id=?',[$examId,$cid])['id'];
 foreach(['Ngữ văn','Ngoại ngữ','Toán'] as $subj){
  // Điểm DEMO đã hoàn thành phách trong kịch bản riêng; có thể thay đổi qua UI chấm phách.
  DB::run('INSERT IGNORE INTO exam_papers(allocation_id,subject,blind_code,package_code,blind_status,score,score_basis) VALUES (?,?,?,?,?,?,?)',[$alloc,$subj,'IS4M'.strtoupper(bin2hex(random_bytes(7))),'PK'.($i<=3?'P01':'P02').substr(hash('sha256',$subj),0,6),'handed',$grades[$i-1],'Điểm mô phỏng, không phải điểm thi chính thức']);
 }
}
foreach([
 ['Hướng dẫn sử dụng hệ thống IS4','Đây là cổng tuyển sinh mô phỏng cho bài tập môn Phát triển ứng dụng. Thông tin trường và kỳ thi chỉ là dữ liệu demo.'],
 ['Thông báo về lịch tuyển sinh minh họa','Hãy xem thời gian đăng ký, thời gian chỉnh sửa và ngày thi trên trang Quản lý kỳ thi.']
] as [$title,$body]){if(!DB::one('SELECT id FROM announcements WHERE title=?',[$title]))DB::run('INSERT INTO announcements(title,body) VALUES (?,?)',[$title,$body]);}
$users=[
 ['admin.is4','Quản trị viên IS4','admin',null,null],
 ['so.is4','Cán bộ Sở GD&ĐT','admissions',null,null],
 ['thcs.is4','Cán bộ THCS Hoa Phượng','school_thcs',$ids['THCS-A'],null],
 ['thpt.is4','Cán bộ THPT Bình Minh','school_thpt',$ids['THPT-A'],null],
 ['khaothi.is4','Cán bộ khảo thí','examiner',null,$examId],
 ['thisinh.is4','Học sinh minh họa 01','candidate',null,null],
];
echo "Đã chuẩn bị database ".cfg('db_name')."; kỳ demo $examId\n";
echo "TÀI KHOẢN TẠO MỚI (mật khẩu chỉ in lúc tạo; không ghi vào file):\n";
foreach($users as [$login,$name,$role,$school,$exam]){
 if(DB::one('SELECT id FROM users WHERE login=?',[$login])){echo " - $login: đã có, KHÔNG đổi mật khẩu\n";continue;}
 $secret=rtrim(strtr(base64_encode(random_bytes(18)),'+/','-_'),'=');
 $rid=DB::one('SELECT id FROM roles WHERE code=?',[$role])['id'];
 DB::run('INSERT INTO users(login,password_hash,full_name,role_id,school_id,exam_id) VALUES (?,?,?,?,?,?)',[$login,password_hash($secret,PASSWORD_DEFAULT),$name,$rid,$school,$exam]);
 $uid=(int)DB::get()->lastInsertId();
 if($role==='candidate')DB::run('UPDATE candidates SET user_id=? WHERE code=? AND user_id IS NULL',[$uid,'IS4-TS01']);
 echo " - $login | $secret\n";
}
echo "Xong. Mở http://localhost:88/IS4-MVC-GiaiThich/\n";
