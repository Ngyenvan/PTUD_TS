<?php
declare(strict_types=1);
namespace App\Models;

/** Thuần nghiệp vụ: không đọc DB, dễ viết test và giải thích khi vấn đáp. */
final class PreferenceRules
{
    public static function window(array $exam, bool $hasDecision, bool $hasPreferences, ?\DateTimeImmutable $now = null): array
    {
        $now ??= new \DateTimeImmutable('now', new \DateTimeZone('Asia/Ho_Chi_Minh'));
        if (($exam['status'] ?? '') !== 'open') {
            return ['mode' => 'view', 'message' => 'Đợt tuyển sinh chưa mở hoặc đã được chốt. Bạn chỉ có thể xem thông tin hiện tại.'];
        }
        $timezone = new \DateTimeZone('Asia/Ho_Chi_Minh');
        $startRegistration = new \DateTimeImmutable($exam['register_from'], $timezone);
        $endRegistration = new \DateTimeImmutable($exam['register_to'], $timezone);
        $startEditing = new \DateTimeImmutable($exam['edit_from'], $timezone);
        $endEditing = new \DateTimeImmutable($exam['edit_to'], $timezone);

        if (!$hasDecision && !$hasPreferences) {
            if ($now < $startRegistration) return ['mode'=>'view','message'=>'Chưa đến thời gian đăng ký nguyện vọng. Bắt đầu: '.$startRegistration->format('d/m/Y H:i')];
            if ($now <= $endRegistration) return ['mode'=>'register','message'=>'Đang trong thời gian đăng ký nguyện vọng lần đầu.'];
            return ['mode'=>'view','message'=>'Đã hết thời gian đăng ký nguyện vọng.'];
        }
        if (!$hasPreferences) {
            return ['mode'=>'view','message'=>'Đã ghi nhận lựa chọn không tham gia thi.'];
        }
        if ($now < $startEditing) return ['mode'=>'view','message'=>'Chưa đến thời gian chỉnh sửa nguyện vọng. Bắt đầu: '.$startEditing->format('d/m/Y H:i')];
        if ($now <= $endEditing) return ['mode'=>'edit','message'=>'Đang trong thời gian chỉnh sửa nguyện vọng.'];
        return ['mode'=>'view','message'=>'Đã hết thời gian chỉnh sửa nguyện vọng. Chỉ được xem danh sách đã đăng ký.'];
    }

    public static function validateThree(array $chosen, array $availableSchoolIds): array
    {
        if (count($chosen) !== 3) return ['Phải đăng ký đủ 3 nguyện vọng NV1, NV2 và NV3.'];
        $numbers=[];
        foreach ($chosen as $index => $value) {
            $string=(string)$value;
            if (!preg_match('/^[1-9][0-9]*$/D', $string)) return ['Nguyện vọng '.($index+1).' chưa chọn trường hợp lệ.'];
            $numbers[]=(int)$string;
        }
        if (count(array_unique($numbers)) !== 3) return ['NV1, NV2 và NV3 phải là ba trường khác nhau.'];
        foreach ($numbers as $schoolId) {
            if (!in_array($schoolId, $availableSchoolIds, true)) return ['Một hoặc nhiều trường không được phép đăng ký trong kỳ này.'];
        }
        return [];
    }
}
