# Bổ sung Use Case Đăng ký nguyện vọng / Chỉnh sửa nguyện vọng

Nguồn: Báo cáo giữa kỳ Hệ thống tuyển sinh lớp 10 – nhóm IS4, trang 43–48. Đây là chức năng **của thí sinh**, không phải của Sở.

## Cài vào project đang chạy trên XAMPP

Sao lưu database trong phpMyAdmin trước. Sao chép thư mục `app`, `routes`, `public`, `tools`, `tests`, `database`, `docs` từ ZIP bản vá vào **`C:\xampp\htdocs\IS4-MVC-GiaiThich`**, chọn Merge/Replace cho các tệp trùng tên. Không xóa thư mục hiện có.

**Không chạy lại** `tools/install.php`: nó có thể tạo dữ liệu demo mới khi thiếu.

Chạy một lần trong PowerShell ở thư mục dự án:

```powershell
& 'C:\xampp\php\php.exe' tools/upgrade-preferences.php
& 'C:\xampp\php\php.exe' tests/preference_rules.php
```

Bản nâng cấp chỉ tạo thêm bảng `registration_choices`, quyền `preferences.manage` cho thí sinh (nếu chưa có), không xóa các `preferences` đang tồn tại. Lưu bằng transaction. Nếu đã bị thu hồi quyền có chủ đích, script không tự cấp lại.

## Để thao tác ĐĂNG KÝ LẦN ĐẦU trước cô

6 hồ sơ demo từ `install.php` đã có 3 NV, nên `thisinh.is4` chỉ được xem NV hiện có, chưa được sửa trong cửa sổ đăng ký. Dùng một hồ sơ mới:

```powershell
& 'C:\xampp\php\php.exe' tools/create-preference-demo.php
& 'C:\xampp\php\php.exe' tools/create-preference-demo.php --apply
```

Lưu mật khẩu in ra ở Terminal, không gửi qua chat. Chỉ tạo **một tài khoản `thisinh.nv.is4`** và hồ sơ `IS4-NV07` hợp lệ, **không tạo NV**, bài thi, phách, điểm hay kết quả. Không tự chạy lại nếu tài khoản đã có. Sau khi đăng ký, hồ sơ mới chưa có bài thi/điểm; cần hoàn thiện dữ liệu đó mới có thể xác nhận xét tuyển toàn kỳ.

Kỳ `IS4_DEMO_2026` tạo bởi cài đặt mặc định ở trạng thái `draft`. Trước khi thí sinh đăng ký, **đăng nhập Sở → Kỳ thi → nhấn "Mở đăng ký" cho kỳ demo**, nếu chưa mở. Khi đang trong khoảng thời gian đăng ký, thí sinh mới vào `http://localhost:88/IS4-MVC-GiaiThich/nguyen-vong` và chọn **Có thi → NV1/NV2/NV3 → Lưu**. Nếu chọn **Không thi**, hệ thống không yêu cầu nguyện vọng và ghi vào bảng `registration_choices`.

Muốn xem nhánh chỉnh sửa: tới thời gian chỉnh sửa trong kỳ, hồ sơ đã đăng ký được sửa ba NV. Không chỉnh trực tiếp SQL để bỏ qua cửa sổ thời gian. Thời gian được hiểu theo Asia/Ho_Chi_Minh. Các nút bị ẩn ngoài kỳ nhưng **server cũng kiểm tra** thời gian, quyền, tư cách thí sinh, trạng thái kỳ, trạng thái hồ sơ, trường có chỉ tiêu, trường trùng, kết quả xét tuyển đã lưu.

> **Lưu ý an toàn khi trình diễn:** Khi tạo thêm hồ sơ mới vào kỳ demo đang dùng để xét tuyển, sau khi đăng ký cần có bài thi/điểm và kiểm tra điều kiện trước khi xác nhận xét tuyển. Để giữ nguyên kịch bản xét tuyển sáu hồ sơ ban đầu, có thể tạo **kỳ demo khác**, cấu hình chỉ tiêu rồi chọn kỳ đó trong trang nguyện vọng. 

## Đối chiếu đặc tả

| Đặc tả | Đã thực hiện |
|---|---|
| UC Đăng ký 2–4 | Kiểm tra thời gian đăng ký và hiển thị thông tin cá nhân |
| UC Đăng ký 5–7 | Có thi/Không thi, 3 lựa chọn trường THPT có chỉ tiêu |
| UC Đăng ký 8–11 | Validate rồi lưu; thông báo thành công |
| Đăng ký Alternative 2.1/2.2 | Ngoài thời gian → chỉ xem, kèm thông báo |
| Đăng ký Alternative 5.1 | Không thi → lưu lựa chọn, không bắt buộc chọn trường |
| Đăng ký Exception 10.1 | Transaction rollback, thông báo lỗi, giữ bản nháp trong session |
| UC Chỉnh sửa 1–9 | Kiểm tra cửa sổ chỉnh sửa, tải danh sách cũ, thay thế 3 NV theo transaction |
| Chỉnh sửa Alternative 6.1/7.1 | Không thay đổi và dữ liệu NV sai → thông báo rõ, giữ dữ liệu |
| Chỉnh sửa Exception 3.1/8.1 | Chặn khi đọc lỗi, rollback khi ghi lỗi, giữ danh sách cũ |

**Giới hạn đã nêu rõ:** trang 43 có phần giao diện trường chuyên, lớp tích hợp nhưng đặc tả không đưa ra trường dữ liệu/quy tắc đăng ký cho hai loại này. Phiên bản này chỉ triển khai **3 nguyện vọng THPT thường**, không giả lập lưu hai loại khác. Có thể bổ sung sau khi nhóm thống nhất đặc tả.

## MVC để vấn đáp

- Router: `routes/web.php` khai báo `GET /nguyen-vong`, `POST /nguyen-vong/luu`.
- Controller: `PreferenceController.php` lấy user hiện tại bằng `Auth::user()`, kiểm tra quyền `preferences.manage`, nhận POST và thông báo kết quả.
- Model: `PreferenceRegistration.php` tra cứu trường, NV và lưu transaction; `PreferenceRules.php` kiểm tra thời gian và 3 trường khác nhau.
- View: `app/Views/preferences/index.php` hiển thị hồ sơ, thời gian, Có thi/Không thi, ba danh sách trường.
- DB: `preferences` giữ ba nguyện vọng, `registration_choices` giữ quyết định tham gia thi, `audit_logs` giữ nhật ký.
