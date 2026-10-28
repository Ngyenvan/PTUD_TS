# Kịch bản kiểm tra website IS4

1. **Cài đặt**: XAMPP Apache và MySQL chạy, `tools/install.php` tạo DB `is4_mvc_giaithich`. Mở `/IS4-MVC-GiaiThich/` thấy trang chủ và logo.
2. **Đăng nhập / phân quyền**: `admin.is4` vào Phân quyền; `thpt.is4` truy cập trực tiếp `/quan-tri/nguoi-dung` phải nhận HTTP 403. `thcs.is4` chỉ thấy hồ sơ trường đã phân công. `thisinh.is4` chỉ thấy thông tin gắn đúng user.
3. **Kỳ thi**: `so.is4` xem kỳ `IS4_DEMO_2026`. Thử tạo kỳ với ngày kết thúc trước ngày bắt đầu phải bị chặn; tạo chỉ tiêu của trường THCS phải bị chặn.
4. **Phách**: demo đã có 18 bài bàn giao để nhanh xét tuyển. Tạo một thí sinh *mới* trong màn hình xét tuyển → 3 bài ở trạng thái `new`. Đăng nhập `khaothi.is4`; chọn phòng/môn và đúng số bài chưa cấp; cấp phách → đối chiếu → niêm phong → bàn giao. Chọn sai số đầu phách hoặc bỏ qua bước phải bị chặn. Chỉ có `blind.mapping` mới thấy tên trong bảng ghép.
5. **Điểm**: chấm những bài `handed` (0–10) với căn cứ. Bài chưa bàn giao không xuất hiện. Điểm ngoài khoảng phải bị chặn.
6. **Điểm chuẩn**: đăng nhập Sở, xem gợi ý trên kỳ demo 6 người. Các mức minh họa: trường A NV1 27.00; B NV2 24.00; C NV3 21.00. Có thể xác nhận; sau đó phải tính lại xét tuyển.
7. **Xét tuyển**: với đúng bộ demo, xem dự kiến: HS01/02 → trường A NV1; HS03/04 → trường B NV2; HS05/06 → trường C NV3. Nhấn xác nhận, kiểm tra chỉ có một kết quả/thí sinh. Bấm xác nhận lại không được ghi đè.
8. **Điểm thiếu**: tạo thí sinh mới nhưng chưa làm phách/nhập đủ ba môn → kết quả dự kiến có cảnh báo; nút xác nhận bị khóa.
9. **Đồng điểm ranh giới**: tạo ba người cạnh tranh hai chỗ cùng mức điểm ở vị trí cắt chỉ tiêu → trạng thái chờ xử lý; không tự chọn theo mã hoặc tên, không lưu/công bố khi chưa giải quyết.
10. **Công bố**: Admin duyệt kỳ sau khi lưu kết quả, THPT thấy danh sách của trường; thí sinh chưa thấy cho đến khi công bố. Chỉ người có `results.publish` có thể công bố.

**Ghi chú:** test dùng dữ liệu giả lập. Không đưa mật khẩu tài khoản thật lên GitHub hoặc gửi vào nhóm/chat. Test quản lý phách và xét tuyển trên dữ liệu/demo khác nếu kỳ cũ đã khóa.
