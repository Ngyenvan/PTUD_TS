<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\Auth;
use App\Core\DB;
use App\Core\View;
use App\Models\PreferenceRegistration;
use App\Models\PreferenceRules;

final class PreferenceController
{
    private function candidateUser(): array
    {
        Auth::require('preferences.manage');
        $user=Auth::user();
        if (!$user || $user['role']!=='candidate') {
            http_response_code(403);
            View::render('partials/error',['code'=>403,'message'=>'Chức năng chỉ dành cho thí sinh.']);
            exit;
        }
        return $user;
    }

    public function index(): void
    {
        $user=$this->candidateUser();
        $candidate=PreferenceRegistration::candidate((int)$user['id']);
        $exams=PreferenceRegistration::exams();
        $examId=(int)($_GET['exam_id']??0);
        if ($examId===0 && $exams) $examId=(int)$exams[0]['id'];
        $exam=null;
        foreach ($exams as $row) if ((int)$row['id']===$examId) {$exam=$row;break;}
        $choices=[];$decision=null;$schools=[];$window=['mode'=>'view','message'=>'Chưa có kỳ tuyển sinh.'];
        if ($exam && $candidate) {
            $choices=PreferenceRegistration::selected($examId,(int)$candidate['id']);
            $decision=PreferenceRegistration::currentDecision($examId,(int)$candidate['id']);
            $schools=PreferenceRegistration::availableSchools($examId);
            $window=PreferenceRules::window($exam,$decision!==null,$choices!==[]);
            if ($candidate['profile_status']!=='valid' && $window['mode']!=='view') {
                $window=['mode'=>'view','message'=>'Hồ sơ chưa hợp lệ. Liên hệ trường THCS để kiểm tra trước khi đăng ký.'];
            }
            // Phiên bản demo không chỉnh sửa hồ sơ đã có kết quả xét tuyển lưu.
            if (DB::one('SELECT id FROM admissions WHERE exam_id=? AND candidate_id=? LIMIT 1',[$examId,$candidate['id']])) {
                $window=['mode'=>'view','message'=>'Đã lưu kết quả xét tuyển nên nguyện vọng không còn được chỉnh sửa.'];
            }
        }
        $draft=$_SESSION['preference_draft'][$examId]??null;
        unset($_SESSION['preference_draft'][$examId]);
        View::render('preferences/index',compact('user','candidate','exams','exam','examId','choices','decision','schools','window','draft'));
    }

    public function save(): void
    {
        $user=$this->candidateUser();
        $examId=(int)($_POST['exam_id']??0);
        if ($examId<1) {flash('danger','Kỳ tuyển sinh không hợp lệ.');redirect('nguyen-vong');}
        $mode=input('mode');
        $participation=input('participation');
        $chosen=[input('nv1'),input('nv2'),input('nv3')];
        // Lưu bản nháp ngắn trong session nếu có lỗi, không làm mất trường đã chọn.
        $previous=['participation'=>$participation,'nv1'=>$chosen[0],'nv2'=>$chosen[1],'nv3'=>$chosen[2]];
        if (!in_array($participation,['yes','no'],true)) {
            $_SESSION['preference_draft'][$examId]=$previous;
            flash('danger','Vui lòng chọn Có thi hoặc Không thi.');
            redirect('nguyen-vong?exam_id='.$examId);
        }
        try {
            $notice=PreferenceRegistration::store($examId,(int)$user['id'],$mode,$participation==='yes',$chosen);
            flash('success',$notice);
        } catch (\Throwable $ex) {
            $_SESSION['preference_draft'][$examId]=$previous;
            flash('danger',$ex instanceof \RuntimeException ? $ex->getMessage() : 'Đăng ký nguyện vọng không thành công. Vui lòng thử lại.');
            error_log('[IS4 preferences] '.get_class($ex).': '.$ex->getMessage());
        }
        redirect('nguyen-vong?exam_id='.$examId);
    }
}
