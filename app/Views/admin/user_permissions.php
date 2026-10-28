<?php
$u=$editor['user'];$matrix=$editor['matrix'];$effective=$editor['effective'];$overrides=$editor['overrides'];
?>
<section class="container section">
 <div class="page-head"><div><p class="overline">HỆ THỐNG / PHÂN QUYỀN NGƯỜI DÙNG</p><h1>Sửa phân quyền tài khoản</h1><p class="muted">Tài khoản <code><?=e($u['login'])?></code>. Quyền riêng sẽ ưu tiên hơn quyền mặc định của vai trò.</p></div><a class="btn btn-light" href="<?=e(url('quan-tri/nguoi-dung'))?>">Quay lại</a></div>
 <form method="post" action="<?=e(url('quan-tri/nguoi-dung/cap-nhat'))?>" onsubmit="return confirm('Xác nhận cập nhật vai trò, phạm vi và quyền của tài khoản này?')">
  <?=field('user-permissions-update')?>
  <input type="hidden" name="user_id" value="<?=$u['id']?>">
  <section class="card">
   <h2>Vai trò và phạm vi phụ trách</h2>
   <div class="form-grid">
    <label>Họ tên<input name="name" required value="<?=e($u['full_name'])?>"></label>
    <label>Vai trò<select name="role" id="permission-role" required><?php foreach($roles as $role):?><option value="<?=e($role['code'])?>" <?=$role['code']===$u['role']?'selected':''?>><?=e($role['label'])?></option><?php endforeach;?></select></label>
    <label>Trường được gán<select name="school_id"><option value="0">— Không áp dụng —</option><?php foreach($schools as $school):?><option value="<?=$school['id']?>" <?=(int)$u['school_id']===(int)$school['id']?'selected':''?>><?=e($school['type'].' · '.$school['name'])?></option><?php endforeach;?></select></label>
    <label>Kỳ được phân công<select name="exam_id"><option value="0">— Không áp dụng —</option><?php foreach($exams as $item):?><option value="<?=$item['id']?>" <?=(int)$u['exam_id']===(int)$item['id']?'selected':''?>><?=e($item['name'])?></option><?php endforeach;?></select></label>
    <label>Hồ sơ thí sinh liên kết<select name="candidate_id"><option value="0">— Không áp dụng —</option><?php foreach($candidates as $candidate):?><option value="<?=$candidate['id']?>" <?=(int)($u['candidate_id']??0)===(int)$candidate['id']?'selected':''?>><?=e($candidate['code'].' – '.$candidate['full_name'])?></option><?php endforeach;?></select></label>
   </div>
   <p class="hint">THCS/THPT phải chọn đúng loại trường; Khảo thí phải chọn kỳ; Thí sinh phải chọn hồ sơ chưa liên kết với tài khoản khác.</p>
  </section>
  <section class="card spaced">
   <h2>Quyền hiệu lực của tài khoản</h2>
   <p class="muted">Đánh dấu quyền cần cấp. Khi đổi vai trò, danh sách tự chuyển về quyền mặc định của vai trò mới; bạn vẫn có thể cấp hoặc thu hồi ngoại lệ trước khi xác nhận.</p>
   <div class="table-wrap"><table><thead><tr><th>Cấp</th><th>Quyền</th><th>Nguồn quyền hiện tại</th></tr></thead><tbody>
   <?php foreach($matrix['permissions'] as $permission):
    $code=$permission['code'];$compatible=[];$roleDefaults=[];
    foreach($roles as $role){if(\App\Models\Accounts::canUsePermission($role['code'],$code))$compatible[]=$role['code'];if($role['code']==='admin'||isset($matrix['grants'][$role['code']][$code]))$roleDefaults[]=$role['code'];}
    $isCompatible=\App\Models\Accounts::canUsePermission($u['role'],$code);$checked=!empty($effective[$code]);$override=$overrides[$code]??null;
    $source=$u['role']==='admin'?'Admin luôn có quyền':($override==='grant'?'Cấp riêng':($override==='revoke'?'Thu hồi riêng':(isset($matrix['grants'][$u['role']][$code])?'Theo vai trò':'Chưa cấp')));
   ?>
    <tr><td><label class="checkline"><input class="permission-check" type="checkbox" name="permissions[]" value="<?=e($code)?>" data-compatible="<?=e(implode(',',$compatible))?>" data-role-defaults="<?=e(implode(',',$roleDefaults))?>" data-current="<?=$checked?'1':'0'?>" <?=$checked?'checked':''?> <?=(!$isCompatible||$u['role']==='admin')?'disabled':''?>><span class="sr-only"><?=e($permission['label'])?></span></label></td><td><strong><?=e($permission['label'])?></strong><small class="monospace"><?=e($code)?></small><?php if(!$isCompatible):?><small>Không áp dụng cho vai trò hiện tại</small><?php endif;?></td><td><span class="tag <?=$checked?'green':'gray'?>"><?=e($source)?></span></td></tr>
   <?php endforeach;?></tbody></table></div>
   <div class="form-actions"><button class="btn btn-main">Xác nhận cập nhật</button><a class="btn btn-light" href="<?=e(url('quan-tri/nguoi-dung'))?>">Hủy</a></div>
  </section>
 </form>
</section>
<script>
(()=>{const role=document.getElementById('permission-role');const initial=role.value;const boxes=[...document.querySelectorAll('.permission-check')];role.addEventListener('change',()=>{const selected=role.value;for(const box of boxes){const compatible=box.dataset.compatible.split(',').includes(selected);const defaults=box.dataset.roleDefaults.split(',').includes(selected);if(selected===initial){box.checked=box.dataset.current==='1';}else{box.checked=defaults;}box.disabled=selected==='admin'||!compatible;}});})();
</script>
