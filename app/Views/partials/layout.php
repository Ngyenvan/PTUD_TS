<?php $me=\App\Core\Auth::user();$notice=$_SESSION['flash']??null;unset($_SESSION['flash']); ?>
<!doctype html><html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="theme-color" content="#5e36db"><title>IS4 | Hệ thống tuyển sinh lớp 10</title>
<link rel="stylesheet" href="<?=e(url('public/assets/style.css'))?>">
<link rel="stylesheet" href="<?=e(url('public/assets/preferences.css'))?>"></head><body>
<div class="top-strip"><div class="container flex-between"><span>IS4 · Hệ thống tuyển sinh lớp 10</span><span>Bản thực hành học tập · Không thuộc cơ quan tuyển sinh</span></div></div>
<header class="header"><div class="container topnav">
<a class="brand" href="<?=e(url())?>"><img src="<?=e(url('public/assets/logo-is4.png'))?>" alt="Logo IS4"><span><b>IS4 Tuyển sinh</b><small>CỔNG THÔNG TIN LỚP 10</small></span></a>
<nav class="desktop-nav"><a href="<?=e(url())?>">Trang chủ</a><a href="<?=e(url(''))?>#truong">Thông tin trường THPT</a><a href="<?=e(url(''))?>#thongbao">Thông báo</a><a href="<?=e(url(''))?>#congcu">Công cụ hỗ trợ</a></nav>
<div class="nav-actions"><?php if($me):?><a class="btn btn-main" href="<?=e(url('quan-tri'))?>">Không gian làm việc</a><form method="post" action="<?=e(url('dang-xuat'))?>"><?=field('logout')?><button class="textbtn">Đăng xuất</button></form><?php else:?><a class="btn btn-main" href="<?=e(url('dang-nhap'))?>">Đăng nhập</a><?php endif;?></div>
</div></header>
<?php if($me):?><nav class="subnav"><div class="container nav-links">
<a href="<?=e(url('quan-tri'))?>">Tổng quan</a>
<?php foreach([
['users.manage','quan-tri/nguoi-dung','Phân quyền'],['exams.manage','quan-tri/ky-thi','Kỳ thi'],['blind.manage','quan-tri/phach','Quản lý phách'],['scores.manage','quan-tri/diem','Nhập điểm'],['admissions.manage','quan-tri/xet-tuyen','Xét tuyển'],['cutoffs.manage','quan-tri/diem-chuan','Điểm chuẩn'],['results.publish','quan-tri/cong-bo','Phê duyệt'],['audit.view','quan-tri/nhat-ky','Nhật ký'],['school.profiles','quan-tri/ho-so','Hồ sơ THCS'],['profile.view','ho-so-cua-toi','Hồ sơ của tôi'],['preferences.manage','nguyen-vong','Đăng ký NV']] as [$permission,$route,$label]):if(($permission !== 'preferences.manage' || ($me['role']??'') === 'candidate') && \App\Core\Auth::has($permission)):?><a href="<?=e(url($route))?>"><?=e($label)?></a><?php endif;endforeach;?><a href="<?=e(url('ket-qua'))?>">Kết quả của tôi / trường</a></div></nav><?php endif;?>
<?php if($notice):?><div class="container"><div role="alert" class="alert <?=e($notice['type'])?>"><?=e($notice['text'])?></div></div><?php endif;?>
<main><?=$body?></main>
<footer class="footer"><div class="container flex-between"><span><b>IS4</b> · Nhóm IS4 – Hệ thống tuyển sinh lớp 10</span><span>Website minh họa phục vụ học tập · Không sử dụng dữ liệu tuyển sinh thật</span></div></footer>
<script src="<?=e(url('public/assets/app.js'))?>" defer></script></body></html>
