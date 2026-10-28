<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Core\Auth;use App\Core\DB;use App\Core\Audit;use App\Core\View;
use App\Models\Directory;
final class BlindController {
 private function checkExam(int $exam):void{
  Auth::require('blind.manage');$u=Auth::user();
  if($u['role']==='examiner' && (int)$u['exam_id']!==$exam){http_response_code(403);exit('403: Bạn chưa được phân công kỳ này.');}
 }
 private function currentExam():int{ $u=Auth::user(); return (int)($_REQUEST['exam_id']??$u['exam_id']??0);}
 public function index():void{
  Auth::require('blind.manage');$u=Auth::user();$exam=$this->currentExam();
  if($u['role']==='examiner')$exam=(int)$u['exam_id'];
  $exams=$u['role']==='examiner'?DB::all('SELECT * FROM exam_cycles WHERE id=?',[$exam]):Directory::exams();
  $rooms=$exam?DB::all('SELECT r.*,s.name school_name FROM rooms r JOIN schools s ON s.id=r.school_id WHERE r.exam_id=?',[$exam]):[];
  $papers=$exam?Directory::paperRows($exam):[];
  $packages=$exam?DB::all("SELECT p.package_code,p.subject,r.code room_code,a.room_id,COUNT(*) qty,MIN(p.blind_status) state FROM exam_papers p JOIN allocations a ON a.id=p.allocation_id JOIN rooms r ON r.id=a.room_id WHERE a.exam_id=? AND p.package_code IS NOT NULL GROUP BY p.package_code,p.subject,r.code,a.room_id ORDER BY room_code,subject",[$exam]):[];
  View::render('blind/index',['exam'=>$exam,'exams'=>$exams,'rooms'=>$rooms,'papers'=>$papers,'packages'=>$packages]);
 }
 public function allocate():void{
  $exam=(int)input('exam_id');$this->checkExam($exam);$room=(int)input('room_id');$subject=input('subject');$quantity=(int)input('count');
  if(!in_array($subject,['Ngữ văn','Ngoại ngữ','Toán'],true)||!DB::one('SELECT id FROM rooms WHERE id=? AND exam_id=?',[$room,$exam])){flash('danger','Chọn đúng phòng và môn thi.');redirect('quan-tri/phach?exam_id='.$exam);}
  if(DB::one('SELECT id FROM admissions WHERE exam_id=? LIMIT 1',[$exam])){flash('danger','Kỳ đã chốt kết quả, không thay đổi phách.');redirect('quan-tri/phach?exam_id='.$exam);}
  try{DB::tx(function()use($room,$subject,$quantity,$exam){
   $papers=DB::all("SELECT p.id,p.blind_status FROM exam_papers p JOIN allocations a ON a.id=p.allocation_id WHERE a.room_id=? AND a.exam_id=? AND p.subject=? FOR UPDATE",[$room,$exam,$subject]);
   if(count($papers)!==$quantity||$quantity===0)throw new \RuntimeException('Số bài thực tế không khớp số bài theo phòng và môn.');
   foreach($papers as $p)if($p['blind_status']!=='new')throw new \RuntimeException('Nhóm bài đã cấp phách, không cấp lại.');
   $package='TU'.strtoupper(bin2hex(random_bytes(7)));
   foreach($papers as $p)DB::run("UPDATE exam_papers SET blind_code=?,package_code=?,blind_status='numbered' WHERE id=?",['PH'.strtoupper(bin2hex(random_bytes(7))),$package,$p['id']]);
   Audit::write('blind.allocate',"Kỳ $exam, phòng $room, môn $subject, túi $package, số bài $quantity");
  });flash('success','Đã cấp phách, chuyển sang đối chiếu/niêm phong/bàn giao.');}
  catch(\Throwable $ex){flash('danger',$ex->getMessage());}
  redirect('quan-tri/phach?exam_id='.$exam);
 }
 public function status():void{
  $exam=(int)input('exam_id');$this->checkExam($exam);$pkg=input('package_code');$next=input('next_status');$count=(int)input('count');$heads=(int)input('heads');
  $nextMap=['numbered'=>'checked','checked'=>'sealed','sealed'=>'handed'];
  if(DB::one('SELECT id FROM admissions WHERE exam_id=? LIMIT 1',[$exam])){flash('danger','Kỳ đã chốt kết quả, không thay đổi phách.');redirect('quan-tri/phach?exam_id='.$exam);}
  try{DB::tx(function()use($exam,$pkg,$next,$count,$heads,$nextMap){
   $items=DB::all('SELECT p.id,p.blind_status FROM exam_papers p JOIN allocations a ON a.id=p.allocation_id WHERE a.exam_id=? AND p.package_code=? FOR UPDATE',[$exam,$pkg]);
   if(!$items||$count!==count($items)||$heads!==count($items))throw new \RuntimeException('Số bài làm/đầu phách không khớp hoặc túi phách không thuộc kỳ.');
   foreach($items as $p)if(($nextMap[$p['blind_status']]??null)!==$next)throw new \RuntimeException('Sai thứ tự bước làm phách.');
   if(input('confirmed')!=='yes')throw new \RuntimeException('Bạn cần xác nhận thao tác đã được mô phỏng/thực hiện.');
   DB::run('UPDATE exam_papers p JOIN allocations a ON a.id=p.allocation_id SET p.blind_status=? WHERE a.exam_id=? AND p.package_code=?',[$next,$exam,$pkg]);
   Audit::write('blind.'.$next,"Túi $pkg, số lượng $count");
  });flash('success','Đã cập nhật trạng thái túi phách.');}
  catch(\Throwable $ex){flash('danger',$ex->getMessage());}
  redirect('quan-tri/phach?exam_id='.$exam);
 }
 public function scores():void{
  Auth::require('scores.manage');$u=Auth::user();$exam=$this->currentExam();if($u['role']==='examiner')$exam=(int)$u['exam_id'];
  $exams=$u['role']==='examiner'?DB::all('SELECT * FROM exam_cycles WHERE id=?',[$exam]):Directory::exams();
  $rows=$exam?DB::all("SELECT p.id,p.blind_code,p.score,p.subject,p.score_basis,r.code room_code FROM exam_papers p JOIN allocations a ON a.id=p.allocation_id JOIN rooms r ON r.id=a.room_id WHERE a.exam_id=? AND p.blind_status='handed' ORDER BY r.code,p.subject,p.id",[$exam]):[];
  View::render('blind/scores',['exam'=>$exam,'exams'=>$exams,'rows'=>$rows]);
 }
 public function grade():void{
  Auth::require('scores.manage');$exam=(int)input('exam_id');$u=Auth::user();
  if($u['role']==='examiner'&&(int)$u['exam_id']!==$exam){http_response_code(403);exit('Không được phân công kỳ này.');}
  $pid=(int)input('paper_id');$score=input('score');$basis=input('basis');
  if(!is_numeric($score)||(float)$score<0||(float)$score>10||$basis===''){flash('danger','Điểm phải nằm trong khoảng 0–10, kèm căn cứ.');redirect('quan-tri/diem?exam_id='.$exam);}
  $paper=DB::one("SELECT p.id,p.blind_status,ec.status FROM exam_papers p JOIN allocations a ON a.id=p.allocation_id JOIN exam_cycles ec ON ec.id=a.exam_id WHERE p.id=? AND a.exam_id=?",[$pid,$exam]);
  if(!$paper||$paper['blind_status']!=='handed'||in_array($paper['status'],['approved','published'],true)){flash('danger','Bài thi chưa bàn giao hoặc kỳ thi đã khóa.');redirect('quan-tri/diem?exam_id='.$exam);}
  if(DB::one('SELECT id FROM admissions WHERE exam_id=? LIMIT 1',[$exam])){flash('danger','Đã xác nhận kết quả, không sửa điểm.');redirect('quan-tri/diem?exam_id='.$exam);}
  DB::run('UPDATE exam_papers SET score=?,score_basis=? WHERE id=?',[round((float)$score,2),$basis,$pid]);Audit::write('score.save',"Bài #$pid / $score");flash('success','Đã lưu điểm theo mã phách.');redirect('quan-tri/diem?exam_id='.$exam);
 }
 public function mapping():void{
  Auth::require('blind.mapping');$exam=(int)($_GET['exam_id']??0);$u=Auth::user();if($u['role']==='examiner'&&(int)$u['exam_id']!==$exam){http_response_code(403);exit('Chưa được giao kỳ thi này.');}
  $rows=DB::all('SELECT p.blind_code,p.subject,c.code candidate_code,c.full_name FROM exam_papers p JOIN allocations a ON a.id=p.allocation_id JOIN candidates c ON c.id=a.candidate_id WHERE a.exam_id=? ORDER BY p.subject,p.id',[$exam]);
  Audit::write('blind.mapping.view',"Kỳ $exam");View::render('blind/mapping',['rows'=>$rows]);
 }
}
