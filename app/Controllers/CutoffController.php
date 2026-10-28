<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Core\Auth;use App\Core\DB;use App\Core\Audit;use App\Core\View;use App\Models\Selection;use App\Models\Directory;
final class CutoffController {
 public function index():void{
  Auth::require('cutoffs.manage');$exam=(int)($_GET['exam_id']??(Directory::exams()[0]['id']??0));
  $suggestions=$exam?Selection::recommendations($exam):[];
  $saved=$exam?DB::all('SELECT c.*,s.name school_name FROM cutoffs c JOIN schools s ON s.id=c.school_id WHERE c.exam_id=? ORDER BY c.priority,s.name',[$exam]):[];
  $preview=$exam?Selection::preview($exam):['issues'=>[]];
  View::render('cutoffs/index',['exams'=>Directory::exams(),'exam'=>$exam,'quotas'=>$exam?DB::all('SELECT q.*,s.name FROM quotas q JOIN schools s ON s.id=q.school_id WHERE q.exam_id=? ORDER BY s.name',[$exam]):[],'suggestions'=>$suggestions,'saved'=>$saved,'warnings'=>$preview['issues']]);
 }
 public function save():void{
  Auth::require('cutoffs.manage');$exam=(int)input('exam_id');$school=(int)input('school_id');$priority=(int)input('priority');$threshold=input('threshold');
  if(!DB::one('SELECT id FROM quotas WHERE exam_id=? AND school_id=?',[$exam,$school])||$priority<1||$priority>3||!is_numeric($threshold)||(float)$threshold<0||(float)$threshold>999){flash('danger','Trường/NV/điểm chuẩn chưa hợp lệ.');redirect('quan-tri/diem-chuan?exam_id='.$exam);}
  if(DB::one('SELECT id FROM admissions WHERE exam_id=? LIMIT 1',[$exam])){flash('danger','Đã chốt xét tuyển, không được thay đổi điểm chuẩn.');redirect('quan-tri/diem-chuan?exam_id='.$exam);}
  $p=Selection::preview($exam);if($p['issues']){flash('danger','Còn thiếu dữ liệu hoặc đồng điểm, chưa cho phép xác nhận điểm chuẩn.');redirect('quan-tri/diem-chuan?exam_id='.$exam);}
  DB::run('INSERT INTO cutoffs(exam_id,school_id,priority,threshold,confirmed_by) VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE threshold=VALUES(threshold),confirmed_by=VALUES(confirmed_by),confirmed_at=NOW()',[$exam,$school,$priority,$threshold,Auth::user()['id']]);
  Audit::write('cutoff.confirm',"$exam/$school/NV$priority: $threshold");flash('success','Đã lưu điểm chuẩn; tính lại bản dự kiến xét tuyển trước khi xác nhận.');redirect('quan-tri/diem-chuan?exam_id='.$exam);
 }
}
