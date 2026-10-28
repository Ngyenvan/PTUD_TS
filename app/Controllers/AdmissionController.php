<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Core\Auth;use App\Core\DB;use App\Core\Audit;use App\Core\View;use App\Models\Directory;use App\Models\Selection;
final class AdmissionController {
 private function cycle():int{return (int)($_REQUEST['exam_id']??0);}
 public function index():void{
  Auth::require('admissions.manage');$exam=$this->cycle();if(!$exam)$exam=(int)(Directory::exams()[0]['id']??0);
  $candidates=$exam?DB::all('SELECT c.*,e.status eligible,e.note,e.priority_bonus,e.encouragement_bonus, (SELECT GROUP_CONCAT(CONCAT(p.priority,": ",s.name) ORDER BY p.priority SEPARATOR " • ") FROM preferences p JOIN schools s ON s.id=p.school_id WHERE p.candidate_id=c.id AND p.exam_id=?) choices FROM candidates c LEFT JOIN eligibility e ON e.candidate_id=c.id AND e.exam_id=? WHERE EXISTS (SELECT 1 FROM preferences p2 WHERE p2.candidate_id=c.id AND p2.exam_id=?) ORDER BY c.id',[$exam,$exam,$exam]):[];
  $preview=$_SESSION['selection_preview'][$exam]??null;
  $admitted=$exam?DB::all('SELECT a.*,c.full_name,s.name school_name FROM admissions a JOIN candidates c ON c.id=a.candidate_id LEFT JOIN schools s ON s.id=a.school_id WHERE a.exam_id=? ORDER BY c.id',[$exam]):[];
  View::render('admissions/index',['exam'=>$exam,'exams'=>Directory::exams(),'candidates'=>$candidates,'preview'=>$preview,'saved'=>$admitted,'schools'=>Directory::schools('THPT'),'thcs'=>Directory::schools('THCS'),'rooms'=>$exam?DB::all('SELECT * FROM rooms WHERE exam_id=? ORDER BY code',[$exam]):[]]);
 }
 public function add():void{
  Auth::require('admissions.manage');$exam=$this->cycle();$name=input('name');$thcs=(int)input('thcs_id');$room=(int)input('room_id');$code=input('code');
  if(!$name||!$code||!DB::one("SELECT id FROM schools WHERE id=? AND type='THCS'",[$thcs])||!DB::one('SELECT id FROM rooms WHERE id=? AND exam_id=?',[$room,$exam])){flash('danger','Hồ sơ hoặc trường/phòng/kỳ chưa hợp lệ.');redirect('quan-tri/xet-tuyen?exam_id='.$exam);}
  if(DB::one('SELECT id FROM admissions WHERE exam_id=? LIMIT 1',[$exam])||DB::one("SELECT id FROM exam_cycles WHERE id=? AND status IN ('approved','published')",[$exam])){flash('danger','Kỳ đã chốt xét tuyển, không thêm hồ sơ.');redirect('quan-tri/xet-tuyen?exam_id='.$exam);}
  $choices=[(int)input('nv1'),(int)input('nv2'),(int)input('nv3')];
  if(count(array_unique($choices))!==3){flash('danger','Ba nguyện vọng phải khác nhau.');redirect('quan-tri/xet-tuyen?exam_id='.$exam);}
  foreach($choices as $sid)if(!DB::one('SELECT id FROM quotas WHERE exam_id=? AND school_id=?',[$exam,$sid])){flash('danger','Trường nguyện vọng chưa có chỉ tiêu.');redirect('quan-tri/xet-tuyen?exam_id='.$exam);}
  try{DB::tx(function()use($name,$code,$thcs,$room,$exam,$choices){
   DB::run('INSERT INTO candidates(code,full_name,birth_date,thcs_id) VALUES (?,?,?,?)',[$code,$name,'2011-06-01',$thcs]);$cid=(int)DB::get()->lastInsertId();
   foreach($choices as $i=>$school)DB::run('INSERT INTO preferences(exam_id,candidate_id,school_id,priority) VALUES (?,?,?,?)',[$exam,$cid,$school,$i+1]);
   DB::run('INSERT INTO eligibility(exam_id,candidate_id,status) VALUES (?,? ,?)',[$exam,$cid,'pending']);
   DB::run('INSERT INTO allocations(exam_id,room_id,candidate_id,candidate_number) VALUES (?,?,?,?)',[$exam,$room,$cid,'IS4-'.str_pad((string)$cid,5,'0',STR_PAD_LEFT)]);
   $alloc=(int)DB::get()->lastInsertId();foreach(['Ngữ văn','Ngoại ngữ','Toán'] as $subject)DB::run('INSERT INTO exam_papers(allocation_id,subject) VALUES (?,?)',[$alloc,$subject]);
   Audit::write('candidate.create',$code);
  });flash('success','Đã tạo hồ sơ và ba bài thi chờ cấp phách.');}
  catch(\Throwable $e){flash('danger','Không thêm được hồ sơ, có thể trùng mã.');}
  redirect('quan-tri/xet-tuyen?exam_id='.$exam);
 }
 public function eligibility():void{
  Auth::require('admissions.manage');$exam=$this->cycle();$cid=(int)input('candidate_id');$status=input('status');$note=input('note');
  if(!in_array($status,['eligible','ineligible','pending'],true)||!DB::one('SELECT id FROM preferences WHERE exam_id=? AND candidate_id=?',[$exam,$cid])){flash('danger','Dữ liệu xét tuyển không hợp lệ.');redirect('quan-tri/xet-tuyen?exam_id='.$exam);}
  if(DB::one('SELECT id FROM admissions WHERE exam_id=? LIMIT 1',[$exam])){flash('danger','Không chỉnh điều kiện khi đã lưu kết quả xét tuyển.');redirect('quan-tri/xet-tuyen?exam_id='.$exam);}
  $p=input('priority_bonus');$k=input('encouragement_bonus');
  if(!is_numeric($p)||!is_numeric($k)||(float)$p<0||(float)$k<0){flash('danger','Điểm cộng phải là số không âm.');redirect('quan-tri/xet-tuyen?exam_id='.$exam);}
  DB::run('INSERT INTO eligibility(exam_id,candidate_id,status,priority_bonus,encouragement_bonus,note,reviewed_by) VALUES (?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE status=VALUES(status),priority_bonus=VALUES(priority_bonus),encouragement_bonus=VALUES(encouragement_bonus),note=VALUES(note),reviewed_by=VALUES(reviewed_by)',[$exam,$cid,$status,$p,$k,$note,Auth::user()['id']]);
  Audit::write('eligibility.review',"Kỳ $exam, học sinh $cid: $status");flash('success','Đã cập nhật kết luận điều kiện dự tuyển.');redirect('quan-tri/xet-tuyen?exam_id='.$exam);
 }
 public function preview():void{
  Auth::require('admissions.manage');$exam=$this->cycle();
  if(!DB::one('SELECT id FROM exam_cycles WHERE id=?',[$exam]))redirect('quan-tri/xet-tuyen');
  $_SESSION['selection_preview'][$exam]=['fingerprint'=>Selection::fingerprint($exam),'data'=>Selection::preview($exam)];
  redirect('quan-tri/xet-tuyen?exam_id='.$exam);
 }
 public function confirm():void{
  Auth::require('admissions.manage');$exam=$this->cycle();$pre=$_SESSION['selection_preview'][$exam]??null;
  if(!$pre){flash('danger','Hãy xem dự kiến trước khi xác nhận.');redirect('quan-tri/xet-tuyen?exam_id='.$exam);}
  try{DB::tx(function()use($exam,$pre){
   $cycle=DB::one('SELECT * FROM exam_cycles WHERE id=? FOR UPDATE',[$exam]);
   if(!$cycle||in_array($cycle['status'],['approved','published'],true))throw new \RuntimeException('Kỳ tuyển sinh đã được phê duyệt hoặc công bố.');
   if(DB::one('SELECT id FROM admissions WHERE exam_id=? LIMIT 1',[$exam]))throw new \RuntimeException('Kết quả đã lưu. Không ghi đè.');
   if(Selection::fingerprint($exam)!==$pre['fingerprint'])throw new \RuntimeException('Dữ liệu thay đổi từ khi xem trước. Hãy tính lại.');
   $current=Selection::preview($exam);if($current['issues'])throw new \RuntimeException('Chưa đủ điều kiện: '.implode('; ',$current['issues']));
   foreach($current['results'] as $r){
    if($r['eligible']==='ineligible'){$status='rejected';}else{$status=$r['school_id']?'accepted':'rejected';}
    DB::run('INSERT INTO admissions(exam_id,candidate_id,school_id,priority,score,status,confirmed_by) VALUES (?,?,?,?,?,?,?)',[$exam,$r['id'],$r['school_id'],$r['priority'],$r['score'],$status,Auth::user()['id']]);
   }
   Audit::write('admissions.confirm',"Kỳ $exam: ".count($current['results']).' hồ sơ');
  });unset($_SESSION['selection_preview'][$exam]);flash('success','Đã xác nhận lưu kết quả xét tuyển. Chưa công bố cho thí sinh.');}
  catch(\Throwable $e){flash('danger',$e->getMessage());}
  redirect('quan-tri/xet-tuyen?exam_id='.$exam);
 }
 public function publish():void{
  Auth::require('results.publish');$exam=$this->cycle();$action=input('action');$basis=input('basis');
  if(strlen($basis)<5){flash('danger','Cần ghi căn cứ phê duyệt/công bố.');redirect('quan-tri/cong-bo');}
  $cycle=DB::one('SELECT * FROM exam_cycles WHERE id=?',[$exam]);if(!$cycle)redirect('quan-tri/cong-bo');
  if($action==='approve' && !in_array($cycle['status'],['approved','published'],true)){
   if(!DB::one('SELECT id FROM admissions WHERE exam_id=? LIMIT 1',[$exam])){flash('danger','Chưa có kết quả xét tuyển đã xác nhận.');redirect('quan-tri/cong-bo');}
   DB::run("UPDATE exam_cycles SET status='approved' WHERE id=?",[$exam]);Audit::write('admissions.approve',"$exam: $basis");flash('success','Đã phê duyệt. Chưa công bố công khai.');
  }elseif($action==='publish'&&$cycle['status']==='approved'){
   DB::run("UPDATE exam_cycles SET status='published',published_at=NOW() WHERE id=?",[$exam]);Audit::write('admissions.publish',"$exam: $basis");flash('success','Đã công bố kết quả demo.');
  }else flash('danger','Trạng thái kỳ không cho phép thao tác này.');
  redirect('quan-tri/cong-bo');
 }
 public function publication():void{Auth::require('results.publish');View::render('admissions/publish',['exams'=>Directory::exams()]);}
}
