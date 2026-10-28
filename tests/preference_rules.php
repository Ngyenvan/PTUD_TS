<?php
declare(strict_types=1);
require dirname(__DIR__).'/app/bootstrap.php';
use App\Models\PreferenceRules;
$exam=['status'=>'open','register_from'=>'2026-10-26 08:00:00','register_to'=>'2026-11-02 17:00:00','edit_from'=>'2026-11-03 08:00:00','edit_to'=>'2026-11-05 17:00:00'];
$tz=new DateTimeZone('Asia/Ho_Chi_Minh');
$cases=[
 ['before register',false,false,'2026-10-25 09:00','view'],
 ['in register',false,false,'2026-10-27 09:00','register'],
 ['after register unregistered',false,false,'2026-11-04 09:00','view'],
 ['registered before edit',true,true,'2026-10-27 09:00','view'],
 ['registered during edit',true,true,'2026-11-04 09:00','edit'],
 ['registered after edit',true,true,'2026-11-06 09:00','view'],
 ['no exam has been recorded',true,false,'2026-10-27 09:00','view'],
];
$count=0;
foreach($cases as [$label,$decision,$prefs,$datetime,$expected]){
 $actual=PreferenceRules::window($exam,$decision,$prefs,new DateTimeImmutable($datetime,$tz))['mode'];
 if($actual!==$expected)throw new RuntimeException("$label: $actual != $expected");
 echo "PASS $label\n";$count++;
}
$draft=$exam;$draft['status']='draft';
if(PreferenceRules::window($draft,false,false,new DateTimeImmutable('2026-10-27 09:00',$tz))['mode']!=='view')throw new RuntimeException('Draft must be blocked');
$count++;
$valid=PreferenceRules::validateThree(['1','2','3'],[1,2,3,4]);
$duplicate=PreferenceRules::validateThree(['1','2','2'],[1,2,3]);
$invalid=PreferenceRules::validateThree(['1','2','5'],[1,2,3]);
$missing=PreferenceRules::validateThree(['1','0','3'],[1,2,3]);
if($valid || !$duplicate || !$invalid || !$missing)throw new RuntimeException('Validation mismatch');
$count+=4;
echo "PASS $count business-rule checks. Tests did not connect to or change MariaDB.\n";
