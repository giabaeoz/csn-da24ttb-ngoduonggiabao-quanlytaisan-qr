# Hệ thống Quản lý Mượn – Trả và Kiểm kê Tài sản bằng Mã QR

**Đề tài Đồ án Cơ sở ngành Công nghệ Thông tin**

---

## 1. Thông tin sinh viên & Giảng viên hướng dẫn

- **Sinh viên thực hiện:** Ngô Dương Gia Bảo  
- **Mã số sinh viên:** 110124008  
- **Lớp:** DA24TTB  
- **Email:** ngoduonggiabao19@gmail.com  
- **Số điện thoại:** 0788919073  
- **Giảng viên hướng dẫn:** Th.S Nguyễn Ngọc Đan Thanh  

---

## 2. Giới thiệu đề tài

Hệ thống cung cấp giải pháp chuyển đổi số toàn diện cho công tác quản lý tài sản, trang thiết bị tại các phòng trực thiết bị của trường đại học (ví dụ: phòng D6, B2, Victory). Thay vì ghi chép thủ công trên sổ sách giấy tờ dễ thất lạc và nhầm lẫn, mỗi thiết bị được cấp một mã QR định danh duy nhất. Nhân viên sử dụng camera điện thoại/laptop hoặc đầu đọc để quét mã QR khi lập phiếu mượn, nhận trả, điều chuyển giữa các phòng học và kiểm kê định kỳ.

### Các phân hệ cốt lõi:
1. **Quản lý danh mục & tài sản:** Quản lý phòng trực, phòng học, loại thiết bị và hồ sơ từng thiết bị; tự động tạo mã tài sản và sinh tem QR.
2. **Quy trình Mượn thiết bị:** Quét QR nhiều thiết bị (1 đến 30 món/phiếu), kiểm tra tương thích phòng học, kiểm tra khóa kiểm kê và kiểm soát hạn trả.
3. **Quy trình Trả thiết bị:** Cho phép trả từng phần hoặc trả đủ, ghi nhận chất lượng thiết bị khi trả (`TOT` hoặc `HU_HONG`), tự động kích hoạt tạo phiếu sự cố khi thiết bị hư hỏng.
4. **Quy trình Điều chuyển thiết bị:** Chuyển thiết bị giữa các phòng học có cam kết tương thích hãng/model thiết bị cố định và lưu lịch sử truy vết.
5. **Kiểm kê đối soát tài sản:** Chụp ảnh sổ sách khi mở đợt, quét QR đối chiếu thực tế và tự động phân loại 4 nhóm: Đã kiểm kê, Chưa tìm thấy (thất thoát), Sai vị trí/hư hỏng, Đang mượn.
6. **Báo cáo sự cố & Bảo trì:** Ghi nhận và theo dõi xử lý hư hỏng, chuyển đổi trạng thái phục hồi thiết bị về `SAN_SANG`.
7. **Thống kê & Biểu đồ Chart.js:** Dashboard trực quan hóa xu hướng mượn trả, phân bổ trạng thái tài sản, cơ cấu chủng loại, đối tượng mượn và danh sách thiết bị quá hạn.

---

## 3. Công nghệ sử dụng

- **Ngôn ngữ lập trình:** PHP 8.2+ thuần (`declare(strict_types=1);`, MySQLi Prepared Statements, `$db->execute_query()`). Không dùng Composer, không dùng Framework cồng kềnh.
- **Cơ sở dữ liệu:** MySQL 8.0+ (hỗ trợ ràng buộc CHECK, Generated Column `STORED`, Transaction `FOR UPDATE`).
- **Giao diện & Trực quan hóa:** HTML5, CSS3, Bootstrap 5.3, Bootstrap Icons.
- **Thư viện JavaScript cục bộ (100% Offline, không phụ thuộc CDN):**
  - `qrcode.js`: Sinh mã QR phía trình duyệt trên tem in tài sản.
  - `jsQR`: Giải mã camera luồng video trực tiếp để nhận diện mã QR.
  - `chart.umd.min.js` (Chart.js): Vẽ biểu đồ thống kê dạng đường, cột, tròn.

---

## 4. Cấu trúc thư mục chuẩn

Cấu trúc dự án tuân thủ nghiêm ngặt quy định cấp gốc của trường và quy ước cấu trúc mã nguồn sạch:

```text
quanlytaisan-qr/
├── README.md                        (Tài liệu tổng quan, cài đặt, ma trận quyền)
├── setup/                           (Công cụ cài đặt, CSDL, kiểm thử tự động)
│   ├── database_dacsn_qlts_qr.sql   (Schema DUY NHẤT: CREATE DATABASE + 13 bảng chuẩn)
│   ├── du_lieu_mau.sql              (Dữ liệu ban đầu: phòng trực, phòng học, loại, thiết bị)
│   ├── tao_tai_khoan.php            (Script CLI tạo tài khoản QUAN_TRI, QUAN_LY, NHAN_VIEN)
│   ├── kiem_tra_moi_truong.php      (Script CLI chẩn đoán hệ thống)
│   ├── kiem_tra_cu_phap.php         (Script CLI kiểm tra cú pháp toàn bộ file mã nguồn)
│   ├── kiem_tra_ham_chua_dinh_nghia.php (Script CLI phân tích tĩnh hàm)
│   ├── thu_toan_bo_trang_web.php    (Script HTTP verifier tự động mở 27 màn hình)
│   ├── chay_kiem_thu.php            (Runner chạy toàn bộ các bài kiểm thử)
│   ├── HUONG-DAN.md                 (Hướng dẫn cài đặt chi tiết & giải thích luồng hoạt động)
│   ├── NGHIEP-VU.md                 (Đặc tả quy tắc nghiệp vụ, ma trận quyền và CSDL)
│   └── tests/                       (Thư mục chứa 11 bộ bài thử Unit/Integration)
│       ├── chay_tat_ca.php          (Bộ chạy test toàn diện 11 test suites)
│       ├── ho_tro.php               (Hàm bổ trợ test, transaction rollback tự động)
│       ├── thu_xac_thuc.php         (Bài thử 1: Xác thực & Session)
│       ├── thu_phan_quyen.php       (Bài thử 2: Phân quyền RBAC 3 vai trò)
│       ├── thu_danh_muc.php         (Bài thử 3: Quản lý danh mục)
│       ├── thu_tai_khoan.php        (Bài thử 4: Quản lý tài khoản cán bộ)
│       ├── thu_nguoi_muon.php       (Bài thử 5: Quản lý người mượn & phạm vi phân quyền)
│       ├── thu_tai_san.php          (Bài thử 6: Nghiệp vụ tài sản & QR)
│       ├── thu_muon_tra.php         (Bài thử 7a: Lập phiếu mượn tài sản)
│       ├── thu_tra_tai_san.php      (Bài thử 7b: Tiếp nhận trả & ghi nhận sự cố)
│       ├── thu_dieu_chuyen.php      (Bài thử 8: Điều chuyển tài sản giữa phòng học)
│       ├── thu_kiem_ke.php          (Bài thử 9: Kiểm kê đối soát tài sản)
│       ├── thu_su_co.php            (Bài thử 10: Xử lý sự cố & bảo trì)
│       └── thu_thong_ke.php         (Bài thử 11: Thống kê & dữ liệu Chart.js)
│
├── src/                             (Thư mục Web Root)
│   ├── index.php                    (Điều hướng thông minh theo phiên làm việc)
│   ├── login.php                    (Màn hình đăng nhập an toàn CSRF)
│   ├── logout.php                   (Xử lý đăng xuất bảo mật qua phương thức POST)
│   ├── tong-quan.php                (Dashboard trung tâm hiển thị theo vai trò)
│   ├── 403.php, 404.php             (Trang thông báo lỗi chuẩn)
│   ├── tai-san/                     (Danh sách, thêm, sửa, chi tiết, in tem QR)
│   ├── muon-tra/                    (Lập phiếu mượn, nhận trả, danh sách, chi tiết phiếu)
│   ├── dieu-chuyen/                 (Lập phiếu điều chuyển, danh sách, chi tiết phiếu)
│   ├── kiem-ke/                     (Mở đợt, quét kiểm kê trực tiếp, báo cáo kết quả)
│   ├── su-co/                       (Báo cáo sự cố, danh sách sự cố, tiếp nhận xử lý)
│   ├── thong-ke/                    (Báo cáo phân tích số liệu và biểu đồ Chart.js)
│   ├── nguoi-muon/                  (Quản lý sinh viên/giảng viên, lịch sử mượn)
│   ├── danh-muc/                    (Phòng trực, phòng học, loại tài sản)
│   ├── tai-khoan/                   (Quản lý tài khoản cán bộ nội bộ)
│   ├── api/                         (Endpoints JSON: tra cứu QR, nạp dữ liệu biểu đồ)
│   ├── assets/                      (Tài nguyên CSS, JS và thư viện vendor offline)
│   └── app/                         (Tầng ứng dụng lõi - BỊ KHÓA TRUY CẬP TRỰC TIẾP)
│       ├── .htaccess                (Require all denied)
│       ├── config/                  (Cấu hình hệ thống và CSDL)
│       ├── core/                    (Khởi động, CSDL, Session, CSRF, Phân quyền, Tiện ích)
│       ├── services/                (TOÀN BỘ LOGIC NGHIỆP VỤ VÀ SQL Ở ĐÂY)
│       └── views/                   (Chỉ chứa mã HTML/CSS hiển thị giao diện)
│
├── progress-report/                 (Báo cáo tiến độ tuần theo quy định)
├── thesis/                          (Tài liệu đồ án: abs, doc, html, pdf, refs)
├── soft/                            (Phần mềm liên quan)
└── docker/                          (Cấu hình môi trường container nếu có)
```

---

## 5. Hướng dẫn cài đặt và khởi chạy hệ thống

### Bước 1: Yêu cầu môi trường
- Máy tính cài đặt **XAMPP for Windows** (PHP 8.2 trở lên, MySQL 8.0 trở lên).
- Đảm bảo đã bật các extension trong `php.ini`: `extension=mysqli`, `extension=mbstring`, `extension=curl`.

### Bước 2: Khởi tạo Cơ sở dữ liệu MySQL
1. Khởi động module **Apache** và **MySQL** trên XAMPP Control Panel.
2. Mở cửa sổ dòng lệnh (PowerShell hoặc CMD) tại thư mục dự án và chạy:

```powershell
# Nạp cấu trúc 13 bảng chuẩn của CSDL dacsn_qlts_qr
C:\xampp\mysql\bin\mysql.exe -u root -p dacsn_qlts_qr < setup/database_dacsn_qlts_qr.sql

# Nạp dữ liệu mẫu ban đầu (danh mục, phòng học, phòng trực, tài sản mẫu)
C:\xampp\mysql\bin\mysql.exe -u root -p dacsn_qlts_qr < setup/du_lieu_mau.sql
```

*(Hoặc mở phpMyAdmin tại `http://localhost/phpmyadmin`, chọn thẻ **Import** và lần lượt nhập hai tập tin trên).*

### Bước 3: Cấu hình kết nối CSDL
Tạo tập tin cấu hình cục bộ từ tập tin mẫu:

```powershell
Copy-Item src/app/config/config.example.php src/app/config/config.local.php
```

Mở `src/app/config/config.local.php` và cập nhật thông tin kết nối MySQL của máy bạn (ví dụ: mật khẩu root). Tập tin này đã nằm trong `.gitignore` để không bao giờ làm lộ thông tin mật khẩu lên kho mã nguồn.

### Bước 4: Chạy kiểm tra chẩn đoán môi trường
Chạy script kiểm tra để xác nhận môi trường đã sẵn sàng:

```powershell
C:\xampp\php\php.exe setup/kiem_tra_moi_truong.php
```

Khi nhận được thông báo `KẾT QUẢ: 12 tiêu chí đạt, 0 tiêu chí chưa đạt`, hệ thống đã sẵn sàng 100%.

### Bước 5: Khởi tạo 3 tài khoản mẫu mặc định
Tạo tài khoản thử nghiệm qua công cụ CLI với mật khẩu được mã hóa chuẩn `password_hash()`:

```powershell
# 1. Tài khoản Quản trị viên (Toàn quyền quản trị hệ thống và người dùng)
C:\xampp\php\php.exe setup/tao_tai_khoan.php --username=admin --role=QUAN_TRI --password=admin123

# 2. Tài khoản Cán bộ Quản lý (Quản lý tài sản, xử lý sự cố, xem thống kê)
C:\xampp\php\php.exe setup/tao_tai_khoan.php --username=quanly01 --role=QUAN_LY --password=quanly123

# 3. Tài khoản Nhân viên trực phòng D6 (Trực tiếp quét QR mượn/trả/kiểm kê)
C:\xampp\php\php.exe setup/tao_tai_khoan.php --username=nhanvien01 --role=NHAN_VIEN --desk=1 --password=nhanvien123
```

### Bước 6: Khởi động máy chủ Web
Chạy PHP Built-in Server tại thư mục `src`:

```powershell
C:\xampp\php\php.exe -S 127.0.0.1:8000 -t src
```

Mở trình duyệt truy cập: **[http://127.0.0.1:8000](http://127.0.0.1:8000)** (hoặc `http://localhost:8000`).

---

## 6. Ma trận phân quyền kiểm soát truy cập (RBAC)

Hệ thống triển khai mô hình kiểm soát truy cập 3 lớp:
1. **Lớp xác thực:** Kiểm tra danh tính, trạng thái tài khoản (`dang_hoat_dong = 1`), bảo vệ phiên làm việc.
2. **Lớp ủy quyền:** Kiểm soát phân quyền chức năng máy chủ thông qua bảng ánh xạ `QUYEN` trong `src/app/core/phan-quyen.php`.
3. **Lớp dữ liệu:** Ràng buộc phạm vi phòng trực trong tầng Service (Nhân viên chỉ được thao tác trên tài sản và phiếu của phòng trực mình phụ trách).

| Quyền hạn chức năng | QUAN_TRI | QUAN_LY | NHAN_VIEN | Ghi chú phạm vi |
| :--- | :---: | :---: | :---: | :--- |
| `tong_quan.xem` | ✔ | ✔ | ✔ | Dashboard tổng quan theo vai trò |
| `tai_san.xem` | ✔ | ✔ | ✔ | Nhân viên chỉ xem tài sản bàn trực mình |
| `tai_san.quan_ly` | ✔ | ✔ | ✘ | Thêm, sửa, sinh mã QR, ngừng sử dụng |
| `danh_muc.loai_tai_san` | ✘ | ✔ | ✘ | Cấu hình chủng loại tài sản |
| `danh_muc.phong` | ✔ | ✔ | ✘ | Quản lý phòng trực và danh mục phòng học |
| `nguoi_muon.xem` | ✘ | ✔ | ✔ | Tra cứu hồ sơ người mượn |
| `nguoi_muon.quan_ly` | ✘ | ✔ | ✘ | Thêm, cập nhật đầy đủ, đổi trạng thái |
| `nguoi_muon.cap_nhat_han_che` | ✘ | ✘ | ✔ | Cập nhật người mượn có giao dịch tại bàn trực |
| `tai_khoan.quan_ly` | ✔ | ✘ | ✘ | Cấp phát, khóa tài khoản cán bộ |
| `muon_tra.thuc_hien` | ✘ | ✘ | ✔ | Lập phiếu mượn & Tiếp nhận trả thiết bị |
| `muon_tra.xem` | ✔ | ✔ | ✔ | Tra cứu, xem chi tiết lịch sử phiếu mượn |
| `dieu_chuyen.thuc_hien`| ✘ | ✘ | ✔ | Lập phiếu điều chuyển giữa các phòng học |
| `dieu_chuyen.xem` | ✔ | ✔ | ✔ | Tra cứu, xem chi tiết phiếu điều chuyển |
| `kiem_ke.thuc_hien` | ✘ | ✘ | ✔ | Mở đợt, quét QR kiểm kê, chốt đối soát |
| `kiem_ke.xem` | ✔ | ✔ | ✔ | Xem báo cáo kết quả kiểm kê |
| `su_co.xem` | ✔ | ✔ | ✔ | Tra cứu danh sách và chi tiết sự cố |
| `su_co.xu_ly` | ✘ | ✔ | ✘ | Tiếp nhận sửa chữa & phục hồi tài sản về sẵn sàng |
| `thong_ke.xem` | ✔ | ✔ | ✘ | Báo cáo phân tích số liệu & Biểu đồ Chart.js |

---

## 7. Kiểm thử hệ thống tự động (Automated Test Suite)

Dự án cung cấp bộ kiểm thử tự động toàn diện giúp bảo đảm độ tin cậy và sự toàn vẹn của mã nguồn:

### 1. Chạy toàn bộ 11 bộ kiểm thử đơn vị & tích hợp (100% PASS):
```powershell
C:\xampp\php\php.exe setup/tests/chay_tat_ca.php
```
*(Hoặc chạy lệnh tương đương `C:\xampp\php\php.exe setup/chay_kiem_thu.php`).*

Bộ kiểm thử bao gồm đầy đủ các kịch bản kiểm tra giao dịch, khóa deadlock, cô lập phòng trực, tính tương thích phòng học, trả từng phần, đối soát kiểm kê 4 nhóm, ghi nhận sự cố tự động và tổng hợp dữ liệu Chart.js.

### 2. Kiểm tra phản hồi HTTP toàn bộ 27 trang giao diện:
```powershell
C:\xampp\php\php.exe setup/thu_toan_bo_trang_web.php
```
Kết quả xác nhận tất cả 27 màn hình đều phản hồi **HTTP 200 OK**, không có lỗi `Fatal error` hay `Uncaught Exception`.

### 3. Kiểm tra cú pháp PHP & Phân tích tĩnh hàm:
```powershell
C:\xampp\php\php.exe setup/kiem_tra_cu_phap.php
C:\xampp\php\php.exe setup/kiem_tra_ham_chua_dinh_nghia.php
```

---

## 8. Tiêu chuẩn bảo mật (Security Checklist)

Hệ thống đã được kiểm tra và đáp ứng các tiêu chuẩn an toàn thông tin:
- [x] **Chống SQL Injection:** 100% các câu truy vấn có dữ liệu người dùng đều sử dụng MySQLi Prepared Statements (`bind_param` hoặc `$db->execute_query()`).
- [x] **Chống tấn công XSS:** Mọi chuỗi ký tự hiển thị ra màn hình HTML đều được lọc qua hàm `e()` (`htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`).
- [x] **Chống tấn công CSRF:** Mọi form gửi dữ liệu POST và hành động đăng xuất đều được gắn kèm mã token CSRF ngẫu nhiên 64 ký tự hex hợp lệ trong phiên làm việc.
- [x] **Bảo vệ mật khẩu:** Mật khẩu người dùng được băm an toàn bằng thuật toán `password_hash()` (Bcrypt). Không lưu trữ hoặc ghi log mật khẩu thô.
- [x] **Bảo vệ phiên đăng nhập:** Thiết lập cờ `HttpOnly`, `SameSite=Lax` cho Session Cookie; tự động đổi định danh phiên `session_regenerate_id(true)` khi đăng nhập thành công để chống Session Fixation.
- [x] **Bảo vệ mã nguồn ứng dụng lõi:** Thư mục `src/app/` được bảo vệ hai lớp bằng `.htaccess` (`Require all denied`) và câu lệnh chặn cứng `if (!defined('APP_KHOI_DONG'))` ở đầu mỗi tệp, ngăn chặn hoàn toàn việc truy cập trực tiếp từ URL trình duyệt.
- [x] **Tiêu đề HTTP an toàn:** Tự động gửi các HTTP Headers bảo vệ: `X-Content-Type-Options: nosniff`, `X-Frame-Options: SAMEORIGIN`, `Referrer-Policy: same-origin`.
- [x] **Bảo vệ điểm cuối API:** Các endpoint trong `src/api/` kiểm tra quyền tương tự trang web và chỉ trả dữ liệu thuộc phạm vi phòng trực của người dùng.
- [x] **Bảo mật tham số URL:** Tất cả tham số định danh `id` đều được ép kiểu số nguyên dương thông qua `doc_so_nguyen()` trước khi xử lý nghiệp vụ.
