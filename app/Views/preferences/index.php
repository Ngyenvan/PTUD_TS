<section class="container section">
    <div class="page-head">
        <div>
            <p class="overline">THÍ SINH / NGUYỆN VỌNG</p>
            <h1>Đăng ký nguyện vọng lớp 10</h1>
            <p class="muted">Đăng ký lần đầu và chỉnh sửa nguyện vọng theo thời gian của kỳ tuyển sinh.</p>
        </div>
        <a class="btn btn-light" href="<?= e(url('ho-so-cua-toi')) ?>">Hồ sơ của tôi →</a>
    </div>

    <?php if (!$candidate): ?>
        <div class="alert warning">Tài khoản chưa liên kết hồ sơ thí sinh. Vui lòng liên hệ quản trị viên để được gắn đúng hồ sơ.</div>
    <?php else: ?>
        <div class="card spaced">
            <h2>Thông tin thí sinh</h2>
            <div class="pref-info-grid">
                <div><span class="muted">Họ và tên</span><strong><?= e($candidate['full_name']) ?></strong></div>
                <div><span class="muted">Mã thí sinh</span><strong><?= e($candidate['code']) ?></strong></div>
                <div><span class="muted">Trường THCS</span><strong><?= e($candidate['thcs_name']) ?></strong></div>
                <div><span class="muted">Trạng thái hồ sơ</span><strong><?= e($candidate['profile_status']) ?></strong></div>
            </div>
        </div>
    <?php endif; ?>

    <form method="get" action="<?= e(url('nguyen-vong')) ?>" class="filter-bar spaced">
        <label>Chọn kỳ tuyển sinh
            <select name="exam_id" onchange="this.form.submit()">
                <?php foreach ($exams as $item): ?>
                    <option value="<?= (int)$item['id'] ?>" <?= (int)$item['id'] === $examId ? 'selected' : '' ?>>
                        <?= e($item['code'].' – '.$item['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <button class="btn btn-light">Xem kỳ</button>
    </form>

    <?php if ($exam && $candidate): ?>
        <section class="card spaced">
            <h2>Thời gian đăng ký và chỉnh sửa</h2>
            <div class="pref-info-grid">
                <div><span class="muted">Đăng ký</span><strong><?= e($exam['register_from']) ?> → <?= e($exam['register_to']) ?></strong></div>
                <div><span class="muted">Chỉnh sửa</span><strong><?= e($exam['edit_from']) ?> → <?= e($exam['edit_to']) ?></strong></div>
                <div><span class="muted">Trạng thái kỳ</span><strong><?= e($exam['status']) ?></strong></div>
                <div><span class="muted">Thời gian tham chiếu</span><strong>Asia/Ho_Chi_Minh (UTC+7)</strong></div>
            </div>
            <div class="alert <?= $window['mode']==='view'?'warning':'info' ?>">
                <?= e($window['message']) ?>
            </div>
        </section>

        <section class="card spaced">
            <h2>Nguyện vọng đã ghi nhận</h2>
            <?php if ($choices): ?>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Thứ tự</th><th>Trường THPT</th></tr></thead>
                        <tbody>
                            <?php foreach ([1,2,3] as $rank): ?>
                                <tr><td><strong>NV<?= $rank ?></strong></td><td><?= e($choices[$rank]['school_name']??'Chưa chọn') ?></td></tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php elseif ($decision && !(int)$decision['will_take_exam']): ?>
                <p>Đã ghi nhận: <strong>Không tham gia thi</strong>.</p>
            <?php else: ?>
                <p class="muted">Chưa có nguyện vọng được ghi nhận trong kỳ.</p>
            <?php endif; ?>
        </section>

        <?php if ($window['mode']==='register' || $window['mode']==='edit'): ?>
            <?php
                $isRegister=$window['mode']==='register';
                $participation=$draft['participation']??'yes';
                $values=[];
                foreach ([1,2,3] as $rank) $values[$rank]=(string)($draft['nv'.$rank]??($choices[$rank]['school_id']??''));
            ?>
            <section class="card spaced">
                <h2><?= $isRegister?'Đăng ký lần đầu':'Chỉnh sửa nguyện vọng' ?></h2>
                <p class="hint">Theo đặc tả: hệ thống kiểm tra 3 trường THPT thường trước khi lưu; mỗi NV có thứ tự ưu tiên 1, 2 và 3.</p>
                <form class="stack" method="post" action="<?= e(url('nguyen-vong/luu')) ?>">
                    <?= field('save-preferences') ?>
                    <input type="hidden" name="exam_id" value="<?= $examId ?>">
                    <input type="hidden" name="mode" value="<?= e($window['mode']) ?>">
                    <fieldset class="pref-fieldset">
                        <legend>Bạn có tham gia kỳ thi tuyển sinh lớp 10 không?</legend>
                        <label class="pref-choice"><input type="radio" name="participation" value="yes" <?= $participation==='yes'?'checked':'' ?> required> Có thi</label>
                        <?php if ($isRegister): ?>
                            <label class="pref-choice"><input type="radio" name="participation" value="no" <?= $participation==='no'?'checked':'' ?>> Không thi</label>
                        <?php endif; ?>
                    </fieldset>
                    <div id="regular-preferences">
                        <h3>Đăng ký trường THPT thường</h3>
                        <div class="form-grid">
                            <?php foreach ([1,2,3] as $rank): ?>
                                <label>Nguyện vọng <?= $rank ?>
                                    <select name="nv<?= $rank ?>" data-preference-select>
                                        <option value="">-- Chọn trường THPT --</option>
                                        <?php foreach ($schools as $school): ?>
                                            <option value="<?= (int)$school['id'] ?>" <?= $values[$rank]===(string)$school['id']?'selected':'' ?>><?= e($school['name'].' – '.$school['district']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        <p class="hint">Bạn cần chọn đủ 3 trường khác nhau. Chỉ những trường THPT có chỉ tiêu trong kỳ mới được hiển thị.</p>
                        <?php if (!$schools): ?><div class="alert warning">Chưa có trường THPT đủ điều kiện nhận nguyện vọng. Hãy liên hệ cán bộ Sở.</div><?php endif; ?>
                    </div>
                    <div class="alert info">
                        <strong>Trường chuyên / lớp tích hợp:</strong> đặc tả yêu cầu hiển thị nhóm đăng ký này nhưng chưa cung cấp quy tắc chọn/lưu chi tiết. Bản thực hành chưa nhận đăng ký hai nhóm này, tránh lưu sai nghiệp vụ.
                    </div>
                    <button class="btn btn-main" type="submit"><?= $isRegister?'Lưu đăng ký nguyện vọng':'Lưu thay đổi nguyện vọng' ?></button>
                </form>
            </section>
        <?php endif; ?>
    <?php endif; ?>
</section>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const radios = document.querySelectorAll('input[name="participation"]');
    const region = document.getElementById('regular-preferences');
    if (!region) return;
    function update() {
        const yes = document.querySelector('input[name="participation"]:checked')?.value !== 'no';
        region.hidden = !yes;
        region.querySelectorAll('select[data-preference-select]').forEach(function (select) {
            select.required = yes;
            select.disabled = !yes;
        });
    }
    radios.forEach(radio => radio.addEventListener('change', update));
    update();
});
</script>
