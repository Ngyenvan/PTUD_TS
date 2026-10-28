# Giải thích project IS4 để vấn đáp

## 1. MVC là gì?

- **Model** là nơi xử lý dữ liệu và quy tắc. Ví dụ `app/Models/Selection.php` nhận thông tin điểm thi, nguyện vọng, chỉ tiêu rồi tính kết quả.
- **View** là giao diện HTML. Ví dụ `app/Views/admissions/index.php` hiển thị bảng thí sinh và nút *Tính dự kiến*.
- **Controller** là cầu nối. Ví dụ `app/Controllers/AdmissionController.php` nhận yêu cầu từ nút bấm, kiểm tra quyền, yêu cầu Model tính toán rồi trả View.
- **Router** (`routes/web.php`) chọn Controller theo URL và HTTP method.
- **PDO** (`app/Core/DB.php`) thực hiện câu SQL có tham số, tránh ghép dữ liệu người dùng vào SQL.

### Câu trả lời mẫu khi cô hỏi

> "Nhóm em dùng PHP thuần theo mô hình MVC. Khi cán bộ vào trang Xét tuyển, Router chuyển yêu cầu đến AdmissionController. Controller kiểm tra quyền `admissions.manage`, gọi Model Selection lấy dữ liệu điểm thi, nguyện vọng, chỉ tiêu từ MariaDB và tính kết quả theo NV1–NV3. Cuối cùng Controller truyền kết quả sang View để hiển thị. Khi cán bộ bấm xác nhận, hệ thống kiểm tra lại dữ liệu và sử dụng transaction để lưu kết quả." 

## 2. Năm chức năng chính và vị trí mã nguồn

| Chức năng | Controller | Model / bảng | View |
|---|---|---|---|
| Phân quyền | `UserController` | `Accounts`, `Auth`; `users`, `roles`, `permissions`, `role_permissions` | `admin/users.php` |
| Quản lý kỳ thi | `ExamController` | `Directory`; `exam_cycles`, `schools`, `rooms`, `quotas` | `exams/index.php` |
| Quản lý phách | `BlindController` | `exam_papers`, `allocations`, `rooms` | `blind/index.php`, `blind/scores.php` |
| Điểm chuẩn | `CutoffController` | `Selection`, `cutoffs`, `quotas` | `cutoffs/index.php` |
| Xét tuyển | `AdmissionController` | `Selection`, `preferences`, `eligibility`, `admissions` | `admissions/index.php` |

## 3. Phân quyền RBAC khác phân chia menu như thế nào?

1. Admin cấp quyền cho **vai trò**, không nhất thiết cấp từng người.
2. Mỗi tài khoản có một vai trò; vai trò có danh sách quyền.
3. Controller dùng `Auth::require('tên.quyền')`. Nếu không có quyền → trả 403, ngay cả khi người dùng nhập URL trực tiếp.
4. Người dùng THCS/THPT/khảo thí còn bị giới hạn theo **dữ liệu**: ví dụ ở `SchoolController::list()`, câu SQL có `WHERE c.thcs_id = school_id trong session`. Thí sinh liên kết bằng `c.user_id = user_id đăng nhập`.
5. Mọi thay đổi phân quyền có nhật ký `audit_logs`.

**Cần phân biệt:** `Auth::has` có tác dụng ẩn menu, còn `Auth::require` và điều kiện SQL phía server mới bảo vệ nghiệp vụ.

## 4. Xét tuyển hoạt động như thế nào?

- Nhận thí sinh đã kiểm tra **đủ điều kiện**.
- Đảm bảo có đủ ba điểm môn **Ngữ văn, Ngoại ngữ, Toán**.
- Điểm xét tuyển demo = Văn + Ngoại ngữ + Toán + ưu tiên + khuyến khích.
- Xét NV1 trước, chỉ xét tiếp NV2/NV3 cho người chưa trúng.
- Mỗi trường chỉ nhận tối đa số người bằng chỉ tiêu; không gán hai nguyện vọng cho cùng một thí sinh.
- Nếu nhóm điểm bằng nhau bị cắt ngang ranh giới chỉ tiêu, **không tự chọn người**; báo "Chờ xử lý đồng điểm" và chặn xác nhận.
- Trước khi xác nhận, Controller tính lại `fingerprint` dữ liệu và so sánh với lúc xem trước; nếu hồ sơ, điểm, chỉ tiêu hoặc điểm chuẩn đã đổi → không lưu.
- Bước lưu dùng `DB::tx()`: nếu lỗi, mọi kết quả trong transaction được rollback.

## 5. Tính điểm chuẩn

`CutoffController` dùng `Selection::recommendations()` lấy **mức điểm thấp nhất trong nhóm trúng tuyển dự kiến**, nhóm theo trường và thứ tự NV. Cán bộ có quyền có thể xác nhận hoặc nhập mức điểm điều chỉnh khi dữ liệu đã đủ. Mức đã xác nhận lưu tại `cutoffs`, có người xác nhận và thời gian. Mỗi lần điều chỉnh làm thay đổi đầu vào xét tuyển; phải xem dự kiến lại.

## 6. Phách là gì? Vì sao cần bảo mật?

Phách là mã ẩn danh cho bài thi. Cán bộ chấm bài chỉ cần xem mã phách và bài làm, không xem tên thí sinh. Ở `blind/index.php`, danh sách thông thường không truy vấn họ tên. Khi cần đối chiếu danh tính dùng chức năng riêng `blind.mapping`, giới hạn kỳ được giao và có nhật ký. Demo mô phỏng 4 trạng thái: mới → cấp phách → đối chiếu → niêm phong → bàn giao; sau bàn giao mới nhập điểm.

## 7. Điểm khác website gốc

`ts10.hcm.edu.vn` là website tham khảo **phong cách giao diện trang công khai** (tím gradient, thanh điều hướng, tìm trường, công cụ hỗ trợ). IS4 sử dụng logo, tên miền localhost, trường và dữ liệu mô phỏng **riêng**, không mượn biểu trưng nhà nước hay tự nhận là cổng tuyển sinh thật.

## 8. Giới hạn và các phần có thể mở rộng

Schema đã được **giản lược 17 bảng** cho dễ học, chưa đồng bộ hoàn toàn lược đồ 27 bảng Tuần 8. Có thể mở rộng thêm thủ tục nhập Excel, hội đồng thi, xác nhận nhập học, phiếu PDF, quy tắc phụ được phê duyệt, lịch sử sửa sau công bố. Đó là tính năng tương lai, không nhận là đã hoàn thành.
