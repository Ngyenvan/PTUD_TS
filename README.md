# IS4 – Hệ thống tuyển sinh lớp 10 (PHP MVC độc lập)

Đây là **project mới**, **không sử dụng mã nguồn Codex trước đây**, cũng **không đụng vào** `C:\xampp\htdocs\IS4` hoặc database `is4_tuyensinh10`.

## 1. Website gồm những gì?

- **Trang chủ** theo tinh thần giao diện tham khảo `https://ts10.hcm.edu.vn/`: nền tím gradient, tìm trường, danh sách trường, thông báo, khối tiện ích; dùng logo IS4 đã chọn. **Không giả danh** cổng chính thức TP.HCM.
- **PHP thuần theo MVC**: Router → Controller → Model (PDO/MySQL) → View, không dùng Laravel.
- **Phân quyền RBAC sáu vai trò**: Admin, Cán bộ Sở, THCS, THPT, Khảo thí, Thí sinh. Quyền kiểm tra tại PHP trên mỗi request, thêm kiểm tra phạm vi trường/kỳ/hồ sơ.
- **5 chức năng chính**: Quản lý kỳ thi; Quản lý phách + nhập điểm; Xác định điểm chuẩn; Xét tuyển NV1–NV3; Quản lý tài khoản & phân quyền. Có thêm phê duyệt/công bố, hồ sơ và kết quả theo vai trò, nhật ký hoạt động.
- Dữ liệu mẫu gồm 1 kỳ **IS4_DEMO_2026**, 5 trường giả lập, 6 thí sinh, 3 nguyện vọng/người, 2 phòng, 18 bài đã có điểm **DEMO**. Điểm mô phỏng được nhập để bạn xem ngay kết quả xét tuyển; muốn thử cấp phách từ đầu, hãy tạo thêm hồ sơ thử từ mục Xét tuyển.

> **Giới hạn:** Đây là **nguyên mẫu học tập**, không phải phần mềm tuyển sinh chính thức. Mô hình CSDL 17 bảng được giản lược để dễ hiểu; **chưa thay thế lược đồ 27 bảng** ở tài liệu Tuần 8. Các chi tiết chưa có quy định (đồng điểm ranh giới, điểm liệt, loại trừ khác, hậu kiểm sau công bố) không được tự quyết. Chưa kiểm thử với database XAMPP thực của bạn; cần chạy và kiểm tra trên máy.

## 2. Cài đặt trên XAMPP Windows – cổng 88

1. Giải nén thư mục `IS4-MVC-GiaiThich` vào `C:\xampp\htdocs\`. **Giữ nguyên tên thư mục**. Không chép đè vào `C:\xampp\htdocs\IS4`.
2. Bật **Apache cổng 88** và **MySQL/MariaDB** trong XAMPP.
3. Mở PowerShell:

```powershell
cd C:\xampp\htdocs\IS4-MVC-GiaiThich
Copy-Item config\local.example.php config\local.php
```

Nếu database dùng tài khoản/mật khẩu/port khác, chỉnh trong `config/local.php` cho đúng; `db_name` mặc định là **`is4_mvc_giaithich`**, riêng với database cũ.

4. Cài CSDL, dữ liệu mẫu và 6 tài khoản bằng **một lệnh**:

```powershell
& 'C:\xampp\php\php.exe' tools/install.php
```

Nếu đây là database đã cài từ phiên bản trước khi có quyền riêng theo tài khoản, chạy thêm một lần:

```powershell
& 'D:\Learn_Space\Documents\PHP-Code\xampp\php\php.exe' tools/upgrade-user-permissions.php
```

Trang **Phân quyền** cho phép đổi vai trò/phạm vi của từng tài khoản, cấp hoặc thu hồi quyền ngoại lệ cho một người dùng, đồng thời vẫn duy trì ma trận quyền mặc định theo vai trò. Mọi thay đổi và lỗi cập nhật nhạy cảm đều được ghi nhật ký.

Script tạo database nếu chưa có, dùng `CREATE TABLE IF NOT EXISTS`, không dùng `DROP`/`TRUNCATE`. Nó sinh mật khẩu **ngẫu nhiên riêng cho các tài khoản mới**, chỉ in một lần trong Terminal sau khi tạo. **Tự lưu mật khẩu ở nơi riêng tư**, không gửi mật khẩu trong ảnh/chat. Chạy lại không đặt lại mật khẩu tài khoản hiện có hoặc cấp lại quyền đã thu hồi. Nếu tài khoản đã tồn tại, script in `đã có` và không đổi mật khẩu.

5. Mở:

**http://localhost:88/IS4-MVC-GiaiThich/**

Đăng nhập: **http://localhost:88/IS4-MVC-GiaiThich/dang-nhap**

> Nếu bị **404**, kiểm tra `mod_rewrite` và `AllowOverride All` của Apache. Nếu lỗi **could not find driver**, kích hoạt `pdo_mysql` trong `C:\xampp\php\php.ini` rồi khởi động lại Apache. Nếu CSS không tải, kiểm tra thư mục/cấu hình `base_path`. Trang hoạt động ở cổng 80 nếu Apache dùng 80: bỏ `:88` trên URL.

### Tài khoản được tạo trong lần đầu

| Login | Vai trò | Ý nghĩa |
|---|---|---|
| `admin.is4` | Admin | Tài khoản, phân quyền, nhật ký, các chức năng quản trị |
| `so.is4` | Cán bộ Sở | Kỳ thi, xét tuyển, điểm chuẩn (chỉ có quyền công bố nếu Admin cấp) |
| `thcs.is4` | Cán bộ THCS | Rà soát hồ sơ thuộc THCS Hoa Phượng |
| `thpt.is4` | Cán bộ THPT | Chỉ xem kết quả đã được duyệt của THPT Bình Minh |
| `khaothi.is4` | Khảo thí | Chỉ xem phách/điểm của kỳ demo được phân công |
| `thisinh.is4` | Thí sinh | Chỉ xem hồ sơ HS01 và kết quả sau khi công bố |

Mật khẩu **không có trong README**: đọc một lần trên Terminal khi chạy `tools/install.php`.

## 3. Thử cả 5 chức năng nhanh

1. **Admin / Phân quyền:** đăng nhập `admin.is4`; vào **Phân quyền**, xem ma trận, tạo thử một tài khoản trường, thử cấp/thu hồi quyền cán bộ Sở. Không thu hồi quyền Admin cuối cùng.
2. **Quản lý kỳ thi:** vào **Kỳ thi**, xem `IS4_DEMO_2026`, danh sách THCS/THPT, mỗi THPT có 2 chỉ tiêu, 2 phòng `P01/P02`. Có biểu mẫu tạo kỳ/trường/phòng/chỉ tiêu mới.
3. **Quản lý phách:** đăng nhập `khaothi.is4`, vào **Phách**. Các 18 bài DEMO đã ở trạng thái bàn giao và có điểm để làm xét tuyển ngay. Để thực hành cấp phách từ đầu: đăng nhập cán bộ Sở, thêm thí sinh mới trong Xét tuyển → hệ thống tạo ba bài chưa cấp → khảo thí chọn phòng/môn, cấp phách → đối chiếu → niêm phong → bàn giao, rồi nhập điểm.
4. **Xét tuyển:** cán bộ Sở chọn kỳ demo → **Tính và xem kết quả dự kiến**. Kỳ vọng: HS01/02 trúng NV1 THPT A, HS03/04 trúng NV2 THPT B, HS05/06 trúng NV3 THPT C. Đây là kết quả minh họa, không có giá trị thực tế. Khi đã xác nhận, hệ thống không ghi đè kết quả.
5. **Điểm chuẩn:** cán bộ Sở xem gợi ý (NV1:27, NV2:24, NV3:21) và có thể xác nhận ngưỡng tại các trường/nguyện vọng tương ứng. Sau khi thay đổi điểm chuẩn, quay lại xem dự kiến xét tuyển rồi xác nhận kết quả.
6. **Phê duyệt & công bố:** Admin hoặc Sở được cấp `results.publish` mới có quyền duyệt và công bố. THPT chỉ xem kết quả đã duyệt của trường mình; thí sinh chỉ thấy kết quả cá nhân khi đã công bố.

**Thứ tự giới thiệu khi vấn đáp:** trang chủ → phân quyền → kỳ thi → phách/điểm → điểm chuẩn → xét tuyển → công bố. Mỗi chức năng có Controller, Model/truy vấn DB và View rõ ràng để chỉ ra.

## 4. Cấu trúc MVC

```text
IS4-MVC-GiaiThich/
├── app/
│   ├── Controllers/      # Nhận yêu cầu HTTP, kiểm tra quyền, điều phối
│   ├── Models/           # Đọc/ghi database, tính điểm/xét tuyển
│   ├── Views/            # HTML giao diện theo từng chức năng
│   ├── Core/             # Router, Auth, DB, View, Audit
│   └── bootstrap.php     # Tự tải class và helper
├── routes/web.php        # Bản đồ URL → Controller
├── database/schema.sql   # Bảng và các khóa
├── tools/install.php     # Khởi tạo CSDL + demo + login
├── config/app.php        # Cấu hình chung
├── config/local.php      # Cấu hình máy cá nhân, gitignore
├── public/index.php      # Điểm vào duy nhất
├── public/assets/        # CSS, JS, logo IS4
└── docs/                 # Giải thích và bài kiểm thử
```

**Ví dụ MVC:** Người dùng vào `/quan-tri/xet-tuyen` → `routes/web.php` gọi `AdmissionController@index` → `Selection`/`DB` đọc dữ liệu hồ sơ/điểm/nguyện vọng → `app/Views/admissions/index.php` hiển thị kết quả. Khi nhấn xác nhận, Controller kiểm tra quyền và snapshot dữ liệu, Model tính lại, ghi database trong transaction, View hiển thị thông báo.

Đọc tiếp [`docs/GIAI_THICH_MVC.md`](docs/GIAI_THICH_MVC.md) và [`docs/KIEM_THU.md`](docs/KIEM_THU.md).

## 5. Giới hạn kỹ thuật cần biết

- Không được đưa dự án lên Internet để dùng thật nếu chưa bổ sung bảo mật nâng cao, chính sách dữ liệu cá nhân, triển khai HTTPS, kiểm thử toàn diện và phê duyệt các quy định tuyển sinh.
- Đồng điểm ở **ranh giới chỉ tiêu** không có tiêu chí phụ được duyệt sẽ **chặn xác nhận**; các trường hợp thiếu điểm/thiếu 3 NV cũng bị chặn.
- Quản lý phách ghi trạng thái **mô phỏng thao tác vật lý**, không tự cắt giấy hay niêm phong ngoài đời.
- Chỉ tiêu, điểm thi, trường, danh tính thí sinh và các ngày trong dữ liệu mẫu đều là **giả lập**.
- Các lệnh của project này chưa được kiểm chứng trực tiếp với XAMPP/MariaDB trên Windows. Đã kiểm tra cú pháp PHP tại môi trường tạo file; bạn cần chạy và gửi lỗi nếu có để chỉnh.
