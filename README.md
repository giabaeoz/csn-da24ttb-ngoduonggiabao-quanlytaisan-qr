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
├── setup/                 Tập tin cài đặt và dữ liệu mẫu
├── src/                   Mã nguồn và cấu hình mẫu
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

### 3. Cấu hình kết nối

Sao chép tập tin cấu hình mẫu:

```powershell
Copy-Item .\src\config.local.php.example .\src\config.local.php
```

Mở `src/config.local.php`, sau đó nhập tài khoản và mật khẩu MySQL của máy đang chạy. Tập tin này được Git bỏ qua để không làm lộ mật khẩu.

### 4. Chạy ứng dụng

Tại thư mục gốc của repository, chạy:

```powershell
C:\xampp\php\php.exe -S 127.0.0.1:8000 -t src
```

Truy cập [http://localhost:8000](http://localhost:8000) trên trình duyệt.

## Quy ước lưu tập tin

- Mã nguồn, thư viện của dự án và dữ liệu thử nghiệm đặt trong `src`.
- Script cài đặt, script cơ sở dữ liệu và dữ liệu dùng để tái hiện kết quả đặt trong `setup`.
- Báo cáo tiến độ đặt trong `progress-report`, nên đặt tên dạng `tuan-01.md`, `tuan-02.md`, ...
- Luận văn Word/PDF và tài liệu bảo vệ đặt đúng thư mục con trong `thesis`.
- Không commit mật khẩu, khóa API, dữ liệu cá nhân nhạy cảm hoặc tập tin `config.local.php`.
