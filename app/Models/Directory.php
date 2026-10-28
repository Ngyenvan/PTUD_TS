<?php
declare(strict_types=1);
namespace App\Models;
use App\Core\DB;
final class Directory {
 public static function exams():array {return DB::all('SELECT * FROM exam_cycles ORDER BY year DESC,id DESC');}
 public static function schools(?string $type=null):array{return $type?DB::all('SELECT * FROM schools WHERE type=? ORDER BY name',[$type]):DB::all('SELECT * FROM schools ORDER BY type,name');}
 public static function dashboard():array{
  $res=[];foreach(['candidates'=>'thí sinh','schools'=>'trường','exam_cycles'=>'kỳ thi','admissions'=>'kết quả'] as $tab=>$label){$res[$label]=(int)DB::one("SELECT COUNT(*) n FROM `$tab`")['n'];}return $res;
 }
 public static function news():array{return DB::all('SELECT * FROM announcements ORDER BY posted_at DESC LIMIT 6');}
 public static function paperRows(int $examId,?int $roomId=null):array {
  $query='SELECT p.*,a.exam_id,a.room_id,r.code room_code,c.full_name candidate_name,c.code candidate_code FROM exam_papers p JOIN allocations a ON a.id=p.allocation_id JOIN rooms r ON r.id=a.room_id JOIN candidates c ON c.id=a.candidate_id WHERE a.exam_id=?';
  $params=[$examId];if($roomId){$query.=' AND a.room_id=?';$params[]=$roomId;}
  return DB::all($query.' ORDER BY r.code,p.subject,p.id',$params);
 }
}
