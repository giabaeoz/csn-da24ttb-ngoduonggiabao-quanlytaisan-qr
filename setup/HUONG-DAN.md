# HƯỚNG DẪN CÀI ĐẶT & VẬN HÀNH HỆ THỐNG (HUONG-DAN.md)
**Dự án:** Quản lý mượn – trả và kiểm kê tài sản bằng mã QR  
**Công nghệ:** PHP 8.2+ thuần (mysqli prepared statements), MySQL 8.0+, Bootstrap 5, jsQR, qrcode.js, Chart.js.

---

## 1. YÊU CẦU MÔI TRƯỜNG

- **Hệ điều hành:** Windows 10/11, Linux hoặc macOS.
- **PHP:** Phiên bản **8.2 trở lên** (đã bật extension `mysqli`, `mbstring`, `json`).
- **MySQL / MariaDB:** Phiên bản **MySQL 8.0+** (hỗ trợ ràng buộc CHECK, Generated Columns STORED).
- **Môi trường chạy đề xuất:** XAMPP for Windows (PHP 8.2+, MySQL 8.0+) hoặc lệnh `php -S 127.0.0.1:8000 -t src`.

---

## 2. QUY TRÌNH CÀI ĐẶT TỪ ĐẦU (3 BƯỚC)

### Bước 1: Khởi tạo Cơ sở dữ liệu và Dữ liệu mẫu

Mở Command Prompt hoặc PowerShell tại thư mục dự án và thực hiện import file schema và dữ liệu mẫu vào MySQL:

```powershell
# 1. Khởi tạo CSDL dacsn_qlts_qr và toàn bộ 13 bảng cấu trúc chuẩn
C:\xampp\mysql\bin\mysql.exe -u root -p dacsn_qlts_qr < setup/database_dacsn_qlts_qr.sql

# 2. Nạp dữ liệu danh mục ban đầu (phòng trực, phòng học, loại thiết bị, người mượn, tài sản)
C:\xampp\mysql\bin\mysql.exe -u root -p dacsn_qlts_qr < setup/du_lieu_mau.sql
```

*(Nếu dùng phpMyAdmin, chọn thẻ **Import** và lần lượt nhập 2 tệp: `setup/database_dacsn_qlts_qr.sql` trước, sau đó là `setup/du_lieu_mau.sql`)*.

---

### Bước 2: Thiết lập Cấu hình CSDL cục bộ

Sao chép tệp mẫu cấu hình thành tệp cấu hình thực tế:

```powershell
Copy-Item src/app/config/config.example.php src/app/config/config.local.php
```

Mở tệp `src/app/config/config.local.php` và điền tài khoản, mật khẩu MySQL của máy bạn:

```php
return [
    'csdl' => [
        'host'     => '127.0.0.1',
        'port'     => 3306,
        'user'     => 'root',
        'password' => 'mat_khau_cua_ban',
        'database' => 'dacsn_qlts_qr',
        'charset'  => 'utf8mb4',
    ],
    // ...
];
```

> **Lưu ý:** Tệp `config.local.php` đã được `.gitignore` bảo vệ tự động, không bao giờ bị đẩy lên Git.

---

### Bước 3: Chạy Kiểm tra Chẩn đoán Môi trường

Thực thi script kiểm tra chẩn đoán môi trường:

```powershell
C:\xampp\php\php.exe setup/kiem_tra_moi_truong.php
```

Khi màn hình thông báo:
```text
KẾT QUẢ: 12 tiêu chí đạt, 0 tiêu chí chưa đạt.
```
tức là toàn bộ môi trường PHP, MySQL, 13 bảng CSDL và các trường nghiệp vụ đặc thù đã sẵn sàng 100%.

---

## 3. KHỞI TẠO TÀI KHOẢN MẪU

Dữ liệu mẫu không cài sẵn mật khẩu nhằm tuân thủ nguyên tắc an toàn. Khởi tạo 3 tài khoản cho 3 vai trò qua CLI:

```powershell
# 1. Tài khoản Quản trị viên (QUAN_TRI)
C:\xampp\php\php.exe setup/tao_tai_khoan.php --username=admin --role=QUAN_TRI --password=admin123

# 2. Tài khoản Cán bộ Quản lý (QUAN_LY)
C:\xampp\php\php.exe setup/tao_tai_khoan.php --username=quanly01 --role=QUAN_LY --password=quanly123

# 3. Tài khoản Nhân viên trực phòng D6 (NHAN_VIEN)
C:\xampp\php\php.exe setup/tao_tai_khoan.php --username=nhanvien01 --role=NHAN_VIEN --desk=1 --password=nhanvien123
```

---

## 4. KHỞI ĐỘNG MÁY CHỦ WEB

### Cách 1: Sử dụng PHP Built-in Server (Khuyên dùng khi phát triển/kiểm thử)

Mở một cửa sổ terminal mới và chạy:

```powershell
C:\xampp\php\php.exe -S 127.0.0.1:8000 -t src
```

Mở trình duyệt truy cập:
👉 `http://127.0.0.1:8000/login.php`

### Cách 2: Chạy qua XAMPP Apache

Đặt thư mục dự án `quanlytaisan-qr` vào `C:\xampp\htdocs\` hoặc tạo Alias trong Apache.
Mở trình duyệt truy cập:
👉 `http://localhost/quanlytaisan-qr/login.php`

---

## 5. GIẢI THÍCH LUỒNG HOẠT ĐỘNG TỪNG PHÂN HỆ

### 5.1. Phân hệ Tài sản & Mã QR
- **Thêm thiết bị:** Chọn loại thiết bị, phòng trực sở hữu, phòng học phù hợp hoặc phạm vi tương thích linh hoạt. Hệ thống tự động đề xuất mã hiển thị `ma_tai_san` và sinh chuỗi `ma_qr` duy nhất.
- **In tem QR:** Trang `tai-san/in-qr.php` dùng thư viện client `qrcode.js` kết xuất hàng loạt mã QR kèm tên thiết bị, mã số lên khổ giấy in.
- **Tra cứu QR:** Camera quét QR hoặc nhập mã thủ công gọi endpoint `api/tra-cuu-qr.php` để lấy thông tin tức thời.

### 5.2. Phân hệ Mượn – Trả thiết bị
- **Lập phiếu mượn:**
  1. Nhân viên tìm hoặc nhập nhanh người mượn.
  2. Chọn phòng học sử dụng.
  3. Quét QR lần lượt từ 1 đến 30 thiết bị thuộc phòng trực của mình.
  4. Hệ thống kiểm tra: thiết bị tốt (`tinh_trang = 'TOT'`), sẵn sàng (`trang_thai = 'SAN_SANG'`), tương thích với phòng học đích, phòng trực không có đợt kiểm kê đang mở.
  5. Thiết lập hạn trả tương lai, xác nhận tạo phiếu `PM-YYYYMMDD-XXXXXXXX`. Thiết bị chuyển `DANG_MUON`.
- **Nhận trả thiết bị:**
  1. Quét QR hoặc chọn phiếu mượn đang mở.
  2. Hỗ trợ trả từng phần: tick chọn các thiết bị trả trong lượt.
  3. Chọn tình trạng khi trả:
     - `TOT` ➔ Thiết bị chuyển `SAN_SANG`.
     - `HU_HONG` ➔ Bắt buộc nhập mô tả hư hỏng, thiết bị chuyển `BAO_TRI`, hệ thống tự động sinh phiếu sự cố `SC-YYYYMMDD-XXXXXXXX`.
  4. Khi mọi thiết bị trong phiếu đã trả hết, phiếu mượn tự động cập nhật `DA_TRA`.

### 5.3. Phân hệ Điều chuyển thiết bị
- Chuyển thiết bị giữa hai phòng học (`phong_hoc_di` ➔ `phong_hoc_den`).
- Thiết bị phải đang nằm ở phòng học đi, sẵn sàng, tình trạng tốt, phù hợp phòng đến.
- Bắt buộc nhập lý do điều chuyển và tick xác nhận kiểm tra tương thích.

### 5.4. Phân hệ Kiểm kê đối soát
- Mở đợt kiểm kê: Hệ thống tự động chụp ảnh sổ sách (`tinh_trang_so_sach`, `trang_thai_so_sach`, `phong_hoc_so_sach_id`).
- Quét QR từng thiết bị đối chiếu thực tế: `TIM_THAY` hoặc `CHUA_TIM_THAY`.
- Chốt đợt: Phân loại 4 nhóm đối soát: Đã khớp, Chưa tìm thấy (mất), Sai vị trí/hư hỏng, Đang mượn lúc mở đợt. Tự động sinh sự cố tài sản tương ứng.

### 5.5. Phân hệ Xử lý sự cố & Thống kê
- Cán bộ Quản lý tiếp nhận sự cố, điều phối bảo trì/sửa chữa và phục hồi trạng thái thiết bị về `SAN_SANG`.
- Thống kê biểu đồ Chart.js trực quan: phân bổ theo trạng thái, theo loại, danh sách quá hạn, số lượt mượn theo ngày.

