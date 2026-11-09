# Hệ thống quản lý tài sản bằng mã QR

## Thông tin sinh viên

- Họ và tên: Ngô Dương Gia Bảo  
- MSSV: 110124008
- Lớp: DA24TTB
- Email: ngoduonggiabao19@gmail.com
- Điện thoại: 0788919073
- Giảng viên hướng dẫn: Th.S Nguyễn Ngọc Đan Thanh

## Giới thiệu

Đề tài xây dựng một ứng dụng web hỗ trợ quản lý tài sản, thiết bị trong phòng thực hành, phòng thí nghiệm, thư viện thiết bị, văn phòng hoặc đơn vị có nhu cầu theo dõi việc cấp phát và sử dụng tài sản. Mỗi tài sản được gán một mã QR riêng để nhận diện nhanh.

## Công nghệ sử dụng

- PHP, MySQL/MySQLi, HTML5, CSS3, Bootstrap 5, JavaScript, thư viện tạo/quét QR Code, Chart.js và Git/GitHub.

## Cấu trúc thư mục

```text
quanlytaisan-qr/
├── setup/                 Tập tin cài đặt, CSDL và script khởi tạo tài khoản CLI
│   ├── database_dacsn_qlts_qr.sql  Schema dùng để cài đặt CSDL
│   ├── tao_tai_khoan.php           Script CLI tạo tài khoản QUAN_TRI, QUAN_LY, NHAN_VIEN
│   ├── kiem_thu_he_thong.php       Kiểm thử xác thực và phân quyền bằng tài khoản tạm
│   └── verify_*.php               Kiểm thử các nghiệp vụ với dữ liệu thử được dọn sau khi chạy
├── src/                   Mã nguồn phân hệ web ứng dụng
│   ├── config.local.php.example    Cấu hình CSDL mẫu
│   ├── includes/                   Các module lõi dùng chung
│   │   ├── database.php            Kết nối CSDL an toàn qua MySQLi Prepared Statements
│   │   ├── session.php             Quản lý Session bảo mật, phòng chống CSRF & XSS
│   │   ├── auth.php                Xử lý xác thực, password_verify, quản lý phiên
│   │   ├── rbac.php                Kiểm soát quyền hạn (RBAC) phía máy chủ (HTTP 403)
│   │   ├── navigation.php          Menu dùng chung cho ba vai trò
│   │   ├── borrow-service.php      Nghiệp vụ mượn–trả
│   │   ├── inventory-service.php   Nghiệp vụ kiểm kê
│   │   ├── transfer-service.php    Điều chuyển giữa các phòng học
│   │   └── staff-borrowers.php     Quyền cập nhật người mượn của Nhân viên
│   ├── admin/                      Phân hệ dành cho QUẢN TRỊ VIÊN (QUAN_TRI)
│   │   └── index.php               Tổng quan tài khoản, tài sản, phòng trực, phòng học
│   ├── quan-ly/                    Phân hệ dành cho QUẢN LÝ (QUAN_LY)
│   │   ├── index.php               Trang khởi đầu quản lý danh mục và tài sản
│   │   └── nguoi-muon.php          Thêm, sửa, xóa hồ sơ người mượn
│   ├── nhan-vien/                  Phân hệ dành cho NHÂN VIÊN (NHAN_VIEN)
│   │   ├── index.php               Tổng quan phòng trực
│   │   ├── muon-tra.php            Mượn–trả; giao diện con trong views/
│   │   ├── kiem-ke.php             Kiểm kê; giao diện con trong views/
│   │   ├── dieu-chuyen.php         Điều chuyển tài sản giữa phòng học
│   │   └── nguoi-muon.php          Tra cứu và cập nhật có giới hạn
│   ├── shared/                     Giao diện tài sản dùng chung, CSS đăng nhập, quét QR
│   ├── vendor/                     Thư viện QR dùng chung
│   ├── views/                      Giao diện đăng nhập
│   ├── 403.php                     Trang thông báo từ chối truy cập (HTTP 403)
│   ├── login.php                   Xử lý đăng nhập, giao diện theo bản phác thảo
│   ├── logout.php                  Xử lý đăng xuất bảo mật qua phương thức POST
│   ├── index.php                   Router điều hướng thông minh theo vai trò
│   └── kiem-tra-moi-truong.php     Công cụ kiểm tra chẩn đoán môi trường hệ thống
├── progress-report/       Báo cáo tiến độ hằng tuần (bắt buộc)
├── thesis/                Tài liệu đồ án (bắt buộc)
│   ├── doc/               Tài liệu Word
│   ├── pdf/               Tài liệu PDF
│   ├── html/              Tài liệu dạng web
│   ├── abs/               Slide, video và tài liệu bảo vệ
│   └── refs/              Tài liệu tham khảo
├── soft/                  Phần mềm liên quan (nếu có)
├── docker/                Cấu hình triển khai Docker (nếu có)
└── README.md              Thông tin và hướng dẫn dự án
```

## Cài đặt và chạy trên máy cục bộ

### 1. Yêu cầu

- PHP 8.2 trở lên, có bật extension `mysqli`.
- MySQL 8.0 trở lên.
- Có thể sử dụng XAMPP để cung cấp PHP và phpMyAdmin.

### 2. Khởi tạo cơ sở dữ liệu

Mở phpMyAdmin, chọn **Import** và nhập tập tin:

```text
setup/database_dacsn_qlts_qr.sql
```

Tập tin SQL sẽ tạo database `dacsn_qlts_qr` nếu database này chưa tồn tại.

Tập tin `database_dacsn_qlts_qr.sql` ở thư mục gốc là bản sao có thêm lệnh kiểm tra sau import; dùng tập tin trong `setup/` để cài đặt.

### 3. Cấu hình kết nối

Sao chép tập tin cấu hình mẫu:

```powershell
Copy-Item .\src\config.local.php.example .\src\config.local.php
```

Mở `src/config.local.php`, sau đó nhập tài khoản và mật khẩu MySQL của máy đang chạy. Tập tin này được Git bỏ qua để không làm lộ mật khẩu.

### 4. Khởi tạo tài khoản Quản trị đầu tiên

Chạy script tạo tài khoản an toàn qua CLI (mật khẩu được băm tự động bằng `password_hash`, không lưu thô):

```powershell
# Chế độ tương tác nhập liệu:
C:\xampp\php\php.exe setup/tao_tai_khoan.php

# Hoặc truyền nhanh tham số tạo Quản Trị Viên:
C:\xampp\php\php.exe setup/tao_tai_khoan.php --username=admin --role=QUAN_TRI --name="Quản Trị Viên" --code="NV_ADMIN01" --password="MatKhauCuaBan@123"
```

Bạn cũng có thể tạo tài khoản mẫu cho các vai trò khác (`--role=QUAN_LY` hoặc `--role=NHAN_VIEN`). Script sẽ tự động kiểm tra trước để tránh tạo trùng hoặc ghi đè tài khoản đã có.

### 5. Chạy ứng dụng

Tại thư mục gốc của repository, chạy PHP built-in server:

```powershell
C:\xampp\php\php.exe -S 127.0.0.1:8000 -t src
```

Truy cập [http://localhost:8000](http://localhost:8000) trên trình duyệt để vào trang đăng nhập.

*(Nếu dùng qua Apache của XAMPP, truy cập địa chỉ alias: [http://localhost/quanlytaisan-qr](http://localhost/quanlytaisan-qr)).*

### 6. Chạy bộ kiểm thử tự động (Unit / Integration Test)

Các bài thử tạo dữ liệu tạm và tự dọn hoặc hoàn tác sau khi chạy:

```powershell
C:\xampp\php\php.exe setup/kiem_thu_he_thong.php
C:\xampp\php\php.exe setup/verify_admin_dashboard.php
C:\xampp\php\php.exe setup/verify_admin_accounts.php
C:\xampp\php\php.exe setup/verify_admin_borrowers.php
C:\xampp\php\php.exe setup/verify_assets.php
C:\xampp\php\php.exe setup/verify_manager.php
C:\xampp\php\php.exe setup/verify_borrow.php
C:\xampp\php\php.exe setup/verify_inventory.php
C:\xampp\php\php.exe setup/verify_transfer.php
C:\xampp\php\php.exe setup/verify_staff_borrowers.php
```

## Ma trận phân quyền (RBAC)

| Vai trò | Phân hệ khởi đầu | Trách nhiệm và quyền hạn chính |
| :--- | :--- | :--- |
| **QUAN_TRI** | `/admin/` | Quản lý tài khoản nhân viên, tài sản, hồ sơ người mượn, phòng trực và phòng học. Không quản lý loại tài sản hoặc trực tiếp thực hiện nghiệp vụ mượn–trả, kiểm kê, điều chuyển. |
| **QUAN_LY** | `/quan-ly/` | Quản lý loại tài sản, thêm/sửa/xóa hồ sơ người mượn, thông tin và mã QR tài sản, phòng trực và phòng học. |
| **NHAN_VIEN** | `/nhan-vien/` | Vận hành mượn–trả và kiểm kê trong phòng trực; tra cứu người mượn, cập nhật thông tin cơ bản của người đã có phiếu tại phòng trực mình. |

Mọi hành vi truy cập trái quyền vào phân hệ khác sẽ bị chặn ở tầng máy chủ và trả về **HTTP 403 Forbidden**.

## Quy ước lưu tập tin

- Mã nguồn, thư viện của dự án và dữ liệu thử nghiệm đặt trong `src`.
- Script cài đặt, script cơ sở dữ liệu và dữ liệu dùng để tái hiện kết quả đặt trong `setup`.
- Báo cáo tiến độ đặt trong `progress-report`, nên đặt tên dạng `tuan-01.md`, `tuan-02.md`, ...
- Luận văn Word/PDF và tài liệu bảo vệ đặt đúng thư mục con trong `thesis`.
- Không commit mật khẩu, khóa API, dữ liệu cá nhân nhạy cảm hoặc tập tin `config.local.php`.
