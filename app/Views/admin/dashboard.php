<section class="container section"><div class="page-head"><div><p class="overline">KHÔNG GIAN LÀM VIỆC / <?=e($user['role_name'])?></p><h1>Xin chào, <?=e($user['full_name'])?>!</h1><p class="muted">Tất cả chức năng bên dưới được hiển thị theo quyền tài khoản của bạn.</p></div><span class="pill">● Đang đăng nhập: <?=e($user['login'])?></span></div>
<div class="stat-grid"><?php foreach($stats as $label=>$num):?><div class="stat"><strong><?=e($num)?></strong><span><?=e(ucfirst($label))?></span></div><?php endforeach;?></div>
<h2 class="h2-compact">Chức năng của bạn</h2><div class="cards3">
<?php foreach([
['users.manage','Tài khoản và phân quyền','Gán vai trò, cấp/thu hồi quyền, khóa tài khoản.','quan-tri/nguoi-dung','🔐'],
['exams.manage','Quản lý kỳ thi','Tạo kỳ, trường, chỉ tiêu và phòng thi.','quan-tri/ky-thi','📆'],
['blind.manage','Quản lý phách','Cấp phách, đối chiếu, niêm phong và bàn giao.','quan-tri/phach','📑'],
['scores.manage','Điểm theo phách','Ghi điểm không lộ danh tính khi chấm.','quan-tri/diem','🖊️'],
['admissions.manage','Xét tuyển thí sinh','Kiểm tra hồ sơ và xét NV1 → NV2 → NV3.','quan-tri/xet-tuyen','🎯'],
['cutoffs.manage','Xác định điểm chuẩn','Xem và xác nhận mức điểm theo trường/NV.','quan-tri/diem-chuan','📈'],
['results.publish','Phê duyệt & công bố','Duyệt kết quả sau xét tuyển rồi công bố.','quan-tri/cong-bo','📢'],
['audit.view','Nhật ký hoạt động','Kiểm tra lịch sử tác vụ trong hệ thống.','quan-tri/nhat-ky','🛡️'],
['school.profiles','Hồ sơ THCS','Rà soát học sinh thuộc trường được giao.','quan-tri/ho-so','🏫'],
['preferences.manage','Đăng ký nguyện vọng','Đăng ký Có thi / Không thi, chọn NV1–NV3 hoặc chỉnh sửa trong thời hạn.','nguyen-vong','📝'],
['profile.view','Hồ sơ của tôi','Xem thông tin cá nhân và trạng thái hồ sơ.','ho-so-cua-toi','👤']
] as [$perm,$title,$desc,$route,$ico]):if(($perm !== 'preferences.manage' || ($user['role']??'') === 'candidate') && \App\Core\Auth::has($perm)):?><a class="function-card" href="<?=e(url($route))?>"><span class="function-ico"><?=$ico?></span><h3><?=e($title)?></h3><p><?=e($desc)?></p><span class="enter">Mở chức năng →</span></a><?php endif;endforeach;?><a class="function-card" href="<?=e(url('ket-qua'))?>"><span class="function-ico">📋</span><h3>Kết quả được phép xem</h3><p>Thí sinh, THCS và THPT chỉ xem đúng phạm vi dữ liệu.</p><span class="enter">Xem kết quả →</span></a></div>
<div class="alert info">Các quy tắc tuyển sinh chi tiết chưa có trong tài liệu sẽ không được hệ thống tự giả định. Phiên bản này chỉ dành cho bài tập và dữ liệu demo.</div></section>
