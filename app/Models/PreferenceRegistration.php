<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\DB;
use App\Core\Audit;
use PDO;
use RuntimeException;

final class PreferenceRegistration
{
    public static function candidate(int $userId): ?array
    {
        return DB::one('SELECT c.*, s.name AS thcs_name FROM candidates c JOIN schools s ON s.id=c.thcs_id WHERE c.user_id=?', [$userId]);
    }

    public static function exams(): array
    {
        return DB::all('SELECT * FROM exam_cycles ORDER BY year DESC, id DESC');
    }

    public static function availableSchools(int $examId): array
    {
        return DB::all("SELECT s.id,s.code,s.name,s.district,q.seats FROM schools s JOIN quotas q ON q.school_id=s.id WHERE q.exam_id=? AND s.type='THPT' AND q.seats>0 ORDER BY s.name,s.id", [$examId]);
    }

    public static function selected(int $examId, int $candidateId): array
    {
        $rows = DB::all('SELECT p.priority,p.school_id,s.name AS school_name FROM preferences p JOIN schools s ON s.id=p.school_id WHERE p.exam_id=? AND p.candidate_id=? ORDER BY p.priority', [$examId,$candidateId]);
        $selected=[];
        foreach ($rows as $row) $selected[(int)$row['priority']]=$row;
        return $selected;
    }

    public static function currentDecision(int $examId, int $candidateId): ?array
    {
        return DB::one('SELECT * FROM registration_choices WHERE exam_id=? AND candidate_id=?',[$examId,$candidateId]);
    }

    public static function store(int $examId, int $userId, string $mode, bool $willTakeExam, array $chosen): string
    {
        return DB::tx(function (PDO $pdo) use ($examId,$userId,$mode,$willTakeExam,$chosen): string {
            // Giữ khóa kỳ và hồ sơ xuyên suốt transaction để chặn race khi lưu.
            $statement=$pdo->prepare('SELECT * FROM exam_cycles WHERE id=? FOR UPDATE');
            $statement->execute([$examId]);
            $exam=$statement->fetch(PDO::FETCH_ASSOC);
            if (!$exam) throw new RuntimeException('Không tìm thấy kỳ tuyển sinh.');
            $statement=$pdo->prepare('SELECT c.* FROM candidates c WHERE c.user_id=? FOR UPDATE');
            $statement->execute([$userId]);
            $candidate=$statement->fetch(PDO::FETCH_ASSOC);
            if (!$candidate) throw new RuntimeException('Tài khoản chưa được liên kết với hồ sơ thí sinh.');
            if ($candidate['profile_status'] !== 'valid') throw new RuntimeException('Hồ sơ chưa được xác nhận hợp lệ để đăng ký nguyện vọng.');
            $candidateId=(int)$candidate['id'];

            if (DB::one('SELECT id FROM admissions WHERE exam_id=? AND candidate_id=? LIMIT 1',[$examId,$candidateId])) {
                throw new RuntimeException('Đã lưu kết quả xét tuyển, không được đổi nguyện vọng.');
            }
            $existing=DB::all('SELECT priority,school_id FROM preferences WHERE exam_id=? AND candidate_id=? ORDER BY priority',[$examId,$candidateId]);
            $decision=self::currentDecision($examId,$candidateId);
            $window=PreferenceRules::window($exam,$decision!==null,$existing!==[]);
            if ($window['mode']!==$mode) throw new RuntimeException($window['message']);

            if ($mode==='edit') {
                if (!$willTakeExam) throw new RuntimeException('Chức năng chỉnh sửa chỉ cho phép thay đổi các nguyện vọng đã đăng ký.');
                if (count($existing)!==3) throw new RuntimeException('Danh sách cũ chưa đủ 3 nguyện vọng, cần cán bộ kiểm tra.');
            } elseif ($mode!=='register') {
                throw new RuntimeException('Thao tác không hợp lệ.');
            }

            if (!$willTakeExam) {
                // Alternative flow 5.1: không thi, không tạo nguyện vọng.
                DB::run('INSERT INTO registration_choices(exam_id,candidate_id,will_take_exam) VALUES (?,?,0)',[$examId,$candidateId]);
                Audit::write('preference.no_exam',"Kỳ $examId; hồ sơ $candidateId");
                return 'Đã ghi nhận lựa chọn không tham gia thi.';
            }

            $schools=self::availableSchools($examId);
            $schoolIds=array_map(static fn(array $r)=>(int)$r['id'],$schools);
            $errors=PreferenceRules::validateThree($chosen,$schoolIds);
            if ($errors) throw new RuntimeException($errors[0]);
            $ids=array_map('intval',$chosen);
            if ($mode==='edit') {
                $before=[];
                foreach ($existing as $entry) $before[(int)$entry['priority']]=(int)$entry['school_id'];
                if ($before === [1=>$ids[0],2=>$ids[1],3=>$ids[2]]) {
                    throw new RuntimeException('Không có thông tin nguyện vọng thay đổi.');
                }
                DB::run('DELETE FROM preferences WHERE exam_id=? AND candidate_id=?',[$examId,$candidateId]);
            }
            foreach ($ids as $index=>$schoolId) {
                DB::run('INSERT INTO preferences(exam_id,candidate_id,school_id,priority) VALUES (?,?,?,?)',[$examId,$candidateId,$schoolId,$index+1]);
            }
            DB::run('INSERT INTO registration_choices(exam_id,candidate_id,will_take_exam) VALUES (?,?,1) ON DUPLICATE KEY UPDATE will_take_exam=1, updated_at=CURRENT_TIMESTAMP',[$examId,$candidateId]);
            Audit::write($mode==='edit'?'preference.edit':'preference.register',"Kỳ $examId; hồ sơ $candidateId; NV: ".implode(',',$ids));
            return $mode==='edit'?'Cập nhật nguyện vọng thành công.':'Đăng ký nguyện vọng thành công.';
        });
    }
}
