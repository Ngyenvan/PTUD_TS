// JS chỉ bổ trợ giao diện; mọi quyền và xác thực thực hiện trên backend.
document.querySelectorAll('input[type="datetime-local"]').forEach((input)=>{
  if (input.dataset.default) input.value = input.dataset.default;
});
