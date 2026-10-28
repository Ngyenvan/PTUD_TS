<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Core\Auth;use App\Core\DB;use App\Core\View;use App\Models\Directory;
final class HomeController {
 public function index():void{
  $q=trim((string)($_GET['q']??''));$type=(string)($_GET['type']??'');
  $params=[];$sql="SELECT s.* FROM schools s WHERE s.type='THPT'";
  if($type==='THCS')$sql="SELECT s.* FROM schools s WHERE s.type='THCS'";
  if($q!==''){$sql.=' AND (s.name LIKE ? OR s.district LIKE ?)';$params=["%$q%","%$q%"];} $sql.=' ORDER BY s.name LIMIT 24';
  View::render('home/index',['schools'=>DB::all($sql,$params),'news'=>Directory::news(),'search'=>$q,'type'=>$type]);
 }
 public function dashboard():void{
  Auth::require();View::render('admin/dashboard',['stats'=>Directory::dashboard(),'user'=>Auth::user()]);
 }
 public function result():void{
  Auth::require(); $u=Auth::user();$results=[];
  if($u['role']==='candidate'){ Auth::require('profile.view');
   $results=DB::all("SELECT ec.name exam_name,c.full_name,s.name school_name,a.status,a.priority,a.score FROM candidates c JOIN admissions a ON a.candidate_id=c.id JOIN exam_cycles ec ON ec.id=a.exam_id LEFT JOIN schools s ON s.id=a.school_id WHERE c.user_id=? AND ec.status='published' AND ec.published_at<=NOW()",[$u['id']]);
  } elseif($u['role']==='school_thpt' && $u['school_id']){ Auth::require('school.results');
   $results=DB::all("SELECT ec.name exam_name,c.full_name,s.name school_name,a.status,a.priority,a.score FROM admissions a JOIN exam_cycles ec ON ec.id=a.exam_id JOIN candidates c ON c.id=a.candidate_id LEFT JOIN schools s ON s.id=a.school_id WHERE a.school_id=? AND a.status='accepted' AND ec.status IN ('approved','published')",[$u['school_id']]);
  }elseif($u['role']==='school_thcs' && $u['school_id']){ Auth::require('school.profiles');
   $results=DB::all("SELECT ec.name exam_name,c.full_name,s.name school_name,a.status,a.priority,a.score FROM candidates c JOIN admissions a ON a.candidate_id=c.id JOIN exam_cycles ec ON ec.id=a.exam_id LEFT JOIN schools s ON s.id=a.school_id WHERE c.thcs_id=? AND ec.status IN ('approved','published')",[$u['school_id']]);
  }else{Auth::require('admissions.manage');$results=DB::all('SELECT ec.name exam_name,c.full_name,s.name school_name,a.status,a.priority,a.score FROM admissions a JOIN exam_cycles ec ON ec.id=a.exam_id JOIN candidates c ON c.id=a.candidate_id LEFT JOIN schools s ON s.id=a.school_id ORDER BY a.id DESC LIMIT 200');}
  View::render('admissions/results',['results'=>$results]);
 }
}
