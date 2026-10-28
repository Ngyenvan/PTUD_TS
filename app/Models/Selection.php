<?php
declare(strict_types=1);
namespace App\Models;
use App\Core\DB;
final class Selection {
 /** Công thức đồ án: Văn + Ngoại ngữ + Toán + điểm ưu tiên + khuyến khích. */
 public static function preview(int $examId):array {
  $candidates=DB::all('SELECT DISTINCT c.id,c.code,c.full_name,el.status eligible,el.priority_bonus,el.encouragement_bonus FROM candidates c JOIN preferences pref ON pref.candidate_id=c.id AND pref.exam_id=? LEFT JOIN eligibility el ON el.candidate_id=c.id AND el.exam_id=? ORDER BY c.id',[$examId,$examId]);
  $papers=DB::all('SELECT a.candidate_id,p.subject,p.score FROM allocations a JOIN exam_papers p ON p.allocation_id=a.id WHERE a.exam_id=?',[$examId]);
  $marks=[];foreach($papers as $p) $marks[(int)$p['candidate_id']][$p['subject']]=$p['score'];
  $prefs=DB::all('SELECT candidate_id,school_id,priority FROM preferences WHERE exam_id=? ORDER BY priority,id',[$examId]);
  $choices=[];foreach($prefs as $p)$choices[(int)$p['candidate_id']][(int)$p['priority']]=(int)$p['school_id'];
  $quota=DB::all('SELECT q.school_id,q.seats,s.name FROM quotas q JOIN schools s ON s.id=q.school_id WHERE q.exam_id=?',[$examId]);
  $seats=[];$names=[];foreach($quota as $q){$seats[(int)$q['school_id']]=(int)$q['seats'];$names[(int)$q['school_id']]=$q['name'];}
  $floorRows=DB::all('SELECT school_id,priority,threshold FROM cutoffs WHERE exam_id=?',[$examId]);
  $floors=[];foreach($floorRows as $f)$floors[(int)$f['school_id']][(int)$f['priority']]=(float)$f['threshold'];
  $result=[];$issues=[];
  if(!$candidates)$issues[]='Chưa có hồ sơ/nguyện vọng cho kỳ này.';
  if(!$quota)$issues[]='Chưa có chỉ tiêu trường THPT cho kỳ này.';
  foreach($candidates as $c){$id=(int)$c['id'];$subjects=$marks[$id]??[];$missing=[];$sum=0;
    foreach(['Ngữ văn','Ngoại ngữ','Toán'] as $subject){if(!isset($subjects[$subject]))$missing[]=$subject;else $sum+=(float)$subjects[$subject];}
    $status=$c['eligible']??'pending';
    if(count($choices[$id]??[])!==3)$issues[]=$c['code'].': chưa đủ 3 nguyện vọng hợp lệ';
    if($status==='eligible' && $missing){$issues[]=$c['code'].': thiếu điểm '.implode(', ',$missing);}
    if($status==='pending'){$issues[]=$c['code'].': chưa kết luận điều kiện dự tuyển';}
    $score=($missing||$status!=='eligible')?null:round($sum+(float)$c['priority_bonus']+(float)$c['encouragement_bonus'],2);
    $result[$id]=['id'=>$id,'code'=>$c['code'],'name'=>$c['full_name'],'eligible'=>$status,'score'=>$score,'school_id'=>null,'school'=>'—','priority'=>null,'status'=>$status==='ineligible'?'Không đủ điều kiện':'Chờ dữ liệu'];
  }
  // Chỉ ứng viên đủ điều kiện + có đủ ba điểm mới tham gia xếp hạng.
  $assigned=[];$tied=[];$allocated=array_fill_keys(array_keys($seats),0);
  for($rank=1;$rank<=3;$rank++){
   foreach($seats as $sid=>$capacity){
    $pool=[];
    foreach($result as $cid=>$row){if(isset($assigned[$cid])||isset($tied[$cid])||$row['score']===null)continue;
     if(($choices[$cid][$rank]??null)!==$sid)continue;
     if(isset($floors[$sid][$rank]) && $row['score']+0.00001<$floors[$sid][$rank]) continue;
     $pool[]=$cid;
    }
    usort($pool,static fn($a,$b)=>($result[$b]['score']<=>$result[$a]['score'])?:($a<=>$b));
    $remaining=$capacity-$allocated[$sid];
    for($i=0;$i<count($pool);){
     $group=[$pool[$i]];$current=$result[$pool[$i]]['score'];$j=$i+1;
     while($j<count($pool)&&abs($result[$pool[$j]]['score']-$current)<0.00001){$group[]=$pool[$j];$j++;}
     if($remaining<=0)break;
     if(count($group)>$remaining){
      $issues[]="Đồng điểm ở ranh giới chỉ tiêu: {$names[$sid]}, NV$rank, mức $current";
      foreach($group as $cid){$tied[$cid]=true;$result[$cid]['status']='Chờ xử lý đồng điểm';}
      break;
     }
     foreach($group as $cid){$assigned[$cid]=true;$allocated[$sid]++;$result[$cid]['school_id']=$sid;$result[$cid]['school']=$names[$sid];$result[$cid]['priority']=$rank;$result[$cid]['status']='Trúng tuyển dự kiến';}
     $remaining-=count($group);$i=$j;
    }
   }
  }
  foreach($result as &$row) if($row['score']!==null&&$row['school_id']===null&&$row['status']!=='Chờ xử lý đồng điểm')$row['status']='Không trúng tuyển';
  unset($row);
  return ['results'=>array_values($result),'issues'=>array_values(array_unique($issues)),'allocated'=>$allocated,'schools'=>$names];
 }
 public static function fingerprint(int $examId):string {
  $a=[];
  foreach(['SELECT * FROM exam_cycles WHERE id=?','SELECT * FROM quotas WHERE exam_id=? ORDER BY id','SELECT * FROM preferences WHERE exam_id=? ORDER BY id','SELECT * FROM eligibility WHERE exam_id=? ORDER BY id','SELECT p.* FROM exam_papers p JOIN allocations a ON a.id=p.allocation_id WHERE a.exam_id=? ORDER BY p.id','SELECT * FROM cutoffs WHERE exam_id=? ORDER BY id'] as $sql)$a[]=DB::all($sql,[$examId]);
  return hash('sha256',json_encode($a,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR));
 }
 public static function recommendations(int $examId):array {
  $preview=self::preview($examId);$min=[];
  foreach($preview['results'] as $r)if($r['school_id']!==null){$k=$r['school_id'].':'.$r['priority']; $min[$k]=isset($min[$k])?min($min[$k],$r['score']):$r['score'];}
  return $min;
 }
}
