<?php
/** Smoke test tĩnh: chạy php tests/structure.php, không cần MariaDB */
declare(strict_types=1);
$root=dirname(__DIR__);
$files=[
 'routes/web.php','public/index.php','public/assets/style.css','public/assets/logo-is4.png',
 'app/Core/Auth.php','app/Core/DB.php','app/Core/Router.php','app/Core/View.php',
 'app/Models/Selection.php','app/Controllers/AdmissionController.php',
 'app/Controllers/CutoffController.php','app/Controllers/ExamController.php',
 'app/Controllers/BlindController.php','app/Controllers/UserController.php',
 'database/schema.sql','tools/install.php'
];
foreach($files as $f){if(!is_file($root.'/'.$f)) {fwrite(STDERR,"FAIL: $f\n");exit(1);}echo "PASS $f\n";}
$routes=file_get_contents($root.'/routes/web.php');
foreach(['/quan-tri/nguoi-dung','/quan-tri/ky-thi','/quan-tri/phach','/quan-tri/xet-tuyen','/quan-tri/diem-chuan'] as $path) {
 if(!str_contains($routes,"'".$path."'")){fwrite(STDERR,"FAIL route $path\n");exit(1);}echo "PASS route $path\n";
}
$selection=file_get_contents($root.'/app/Models/Selection.php');
foreach(['Ngữ văn','Ngoại ngữ','Toán','Chờ xử lý đồng điểm'] as $p)if(!str_contains($selection,$p)){fwrite(STDERR,"FAIL selection: $p\n");exit(1);}
echo "PASS selection markers\n";
