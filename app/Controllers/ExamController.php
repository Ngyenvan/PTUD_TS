<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Core\Auth;use App\Core\DB;use App\Core\Audit;use App\Core\View;use App\Models\Directory;
final class ExamController {
 public function index():void{Auth::require('exams.manage');View::render('exams/index',['exams'=>Directory::exams(),'schools'=>Directory::schools(),'rooms'=>DB::all('SELECT r.*,ec.name exam_name,s.name school_name FROM rooms r JOIN exam_cycles ec ON ec.id=r.exam_id JOIN schools s ON s.id=r.school_id ORDER BY r.id'),'quotas'=>DB::all('SELECT q.*,ec.code exam_code,s.name school_name FROM quotas q JOIN exam_cycles ec ON ec.id=q.exam_id JOIN schools s ON s.id=q.school_id ORDER BY q.id')]);}
 public function create():void{
  Auth::require('exams.manage');$name=input('name');$code=input('code');$year=(int)input('year');$start=input('register_from');$end=input('register_to');$edit1=input('edit_from');$edit2=input('edit_to');$date=input('exam_date');
  $a=strtotime($start);$b=strtotime($end);$c=strtotime($edit1);$d=strtotime($edit2);$e=strtotime($date);
  if(!$name||!preg_match('/^[A-Z0-9_-]{3,40}$/i',$code)||$year<2020||!$a||!$b||!$c||!$d||!$e||$a>=$b||$b>$c||$c>=$d||$d>=strtotime(date('Y-m-d 23:59:59',$e))){flash('danger','Kiểm tra mã, năm và mốc thời gian của kỳ thi.');redirect('quan-tri/ky-thi');}
  try{DB::run('INSERT INTO exam_cycles(code,name,year,register_from,register_to,edit_from,edit_to,exam_date) VALUES (?,?,?,?,?,?,?,?)',[$code,$name,$year,date('Y-m-d H:i:s',$a),date('Y-m-d H:i:s',$b),date('Y-m-d H:i:s',$c),date('Y-m-d H:i:s',$d),date('Y-m-d',$e)]);Audit::write('exam.create',$code);flash('success','Đã tạo kỳ thi.');}
  catch(\Throwable $ex){flash('danger','Không lưu được kỳ thi (mã trùng hoặc dữ liệu chưa hợp lệ).');}
  redirect('quan-tri/ky-thi');
 }
 public function school():void{
  Auth::require('schools.manage');$code=input('code');$name=input('name');$type=input('type');$district=input('district');
  if(!$code||!$name||!in_array($type,['THCS','THPT'],true)||!$district){flash('danger','Thông tin trường chưa hợp lệ.');redirect('quan-tri/ky-thi');}
  try{DB::run('INSERT INTO schools(code,name,type,district,address,description) VALUES (?,?,?,?,?,?)',[$code,$name,$type,$district,input('address'),input('description')]);Audit::write('school.create',$code);flash('success','Đã thêm trường.');}catch(\Throwable $e){flash('danger','Không tạo được trường, mã có thể đã tồn tại.');}redirect('quan-tri/ky-thi');
 }
 public function quota():void{
  Auth::require('schools.manage');$exam=(int)input('exam_id');$school=(int)input('school_id');$seats=(int)input('seats');
  $s=DB::one("SELECT id FROM schools WHERE id=? AND type='THPT'",[$school]);
  if(!$s||$seats<0||!DB::one('SELECT id FROM exam_cycles WHERE id=?',[$exam])){flash('danger','Chỉ tiêu/trường/kỳ chưa hợp lệ.');redirect('quan-tri/ky-thi');}
  if(DB::one('SELECT id FROM admissions WHERE exam_id=? LIMIT 1',[$exam])){flash('danger','Đã chốt kết quả, không thể đổi chỉ tiêu.');redirect('quan-tri/ky-thi');}
  DB::run('INSERT INTO quotas(exam_id,school_id,seats) VALUES (?,?,?) ON DUPLICATE KEY UPDATE seats=VALUES(seats)',[$exam,$school,$seats]);Audit::write('quota.save',"$exam/$school: $seats");flash('success','Đã lưu chỉ tiêu.');redirect('quan-tri/ky-thi');
 }
 public function room():void{
  Auth::require('exams.manage');$exam=(int)input('exam_id');$sid=(int)input('school_id');$cap=(int)input('capacity');$code=input('code');
  if($cap<1||!$code||!DB::one('SELECT id FROM exam_cycles WHERE id=?',[$exam])||!DB::one('SELECT id FROM schools WHERE id=?',[$sid])){flash('danger','Thông tin phòng không hợp lệ.');redirect('quan-tri/ky-thi');}
  try{DB::run('INSERT INTO rooms(exam_id,school_id,code,capacity) VALUES (?,?,?,?)',[$exam,$sid,$code,$cap]);Audit::write('room.create',$code);flash('success','Đã thêm phòng.');}catch(\Throwable $ex){flash('danger','Mã phòng đã tồn tại trong kỳ.');}redirect('quan-tri/ky-thi');
 }
 /** Chỉ cán bộ có quyền mới mở kỳ đang ở trạng thái nháp để thí sinh đăng ký. */
 public function openRegistration():void {
  Auth::require('exams.manage');
  $exam=(int)input('exam_id');
  $affected=DB::run("UPDATE exam_cycles SET status='open' WHERE id=? AND status='draft'",[$exam]);
  if($affected===1){Audit::write('exam.open_registration',"Kỳ $exam");flash('success','Đã mở kỳ để thí sinh đăng ký theo lịch đã cấu hình.');}
  else flash('danger','Chỉ mở được kỳ đang ở trạng thái bản nháp.');
  redirect('quan-tri/ky-thi');
 }

}
