# HƯỚNG DẪN CÀI ĐẶT & VẬN HÀNH HỆ THỐNG (HUONG-DAN.md)

**Dự án:** Quản lý mượn – trả và kiểm kê tài sản bằng mã QR  
**Công nghệ:** PHP 8.2+ thuần (MySQLi prepared statements, `$db->execute_query()`), MySQL 8.0+, Bootstrap 5, jsQR, qrcode.js, Chart.js (100% offline vendor).

---

## 1. YÊU CẦU MÔI TRƯỜNG

- **Hệ điều hành:** Windows 10/11, Linux hoặc macOS.
- **PHP:** Phiên bản **8.2 trở lên** (đã bật extension `mysqli`, `mbstring`, `json`, `curl`).
- **MySQL / MariaDB:** Phiên bản **MySQL 8.0+** (hỗ trợ ràng buộc CHECK, Generated Columns STORED, InnoDB row-level locking `FOR UPDATE`).
- **Môi trường chạy đề xuất:** XAMPP for Windows hoặc PHP Built-in Server `php -S 127.0.0.1:8000 -t src`.

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

### 5.0. Sơ đồ trạng thái vòng đời thiết bị (Vòng đời nghiệp vụ)

```text
               ┌───────────────────── Lập phiếu mượn ────────────────────┐
               │                                                         ▼
        ┌──────────────┐                                        ┌─────────────────┐
        │   SAN_SANG   │                                        │    DANG_MUON    │
        │ (tình trạng: │◀──────── Nhận trả đủ (TOT) ────────────│ (tình trạng:    │
        │     TOT)     │                                        │      TOT)       │
        └──────┬───────┘                                        └────────┬────────┘
               │                                                         │
               │                                      Nhận trả (HU_HONG) │
               │ Phát hiện hư hỏng                                       │
               │ hoặc kiểm kê sai lệch                                   ▼
               │                                                ┌─────────────────┐
               └───────────────────────────────────────────────▶│     BAO_TRI     │
                                                                │  (tình trạng:   │
                                                                │    HU_HONG)     │
               ┌────────────────────────────────────────────────└────────┬────────┘
               │                                                         │
               │ Hoàn tất xử lý bảo trì (sửa xong)                       │
               └─────────────────────────────────────────────────────────┘
                                     │
                    Thất thoát / Thanh lý / Dừng lưu hành
                                     ▼
                             ┌─────────────────┐
                             │  NGUNG_SU_DUNG  │
                             └─────────────────┘
```

---

### 5.1. Phân hệ Tài sản & Mã QR
- **Thêm thiết bị:** Chọn loại thiết bị, phòng trực sở hữu, phòng học phù hợp hoặc phạm vi tương thích linh hoạt (`THEO_THIET_BI`, `THEO_PHONG`, `LINH_HOAT`). Hệ thống tự động đề xuất mã hiển thị `ma_tai_san` và sinh chuỗi `ma_qr` duy nhất (ví dụ: `QR-8B7C2A1E90FD`).
- **In tem QR:** Trang `tai-san/in-qr.php` dùng thư viện client `qrcode.js` kết xuất hàng loạt mã QR kèm tên thiết bị, mã số lên khổ giấy in chuẩn tem dán.
- **Tra cứu QR:** Camera quét QR hoặc nhập mã thủ công gọi endpoint `api/tra-cuu-qr.php` để lấy thông tin tức thời và kiểm tra sớm hành động hợp lệ.

---

### 5.2. Phân hệ Mượn - Trả thiết bị
- **Lập phiếu mượn (`muon.php`):**
  1. Nhân viên tìm hoặc nhập nhanh người mượn (nếu mới sẽ tự động lưu vào CSDL).
  2. Chọn phòng học sử dụng.
  3. Quét QR lần lượt từ 1 đến 30 thiết bị thuộc phòng trực của mình.
  4. Hệ thống kiểm tra: thiết bị tốt (`tinh_trang = 'TOT'`), sẵn sàng (`trang_thai = 'SAN_SANG'`), tương thích với phòng học đích, phòng trực không có đợt kiểm kê đang mở.
  5. Thiết lập hạn trả tương lai, xác nhận tạo phiếu `PM-YYYYMMDD-XXXXXXXX`. Thiết bị chuyển `DANG_MUON` và cập nhật `phong_hoc_hien_tai_id`.
  6. Sử dụng khóa `SELECT ... FOR UPDATE` theo thứ tự ID tăng dần để ngăn ngừa deadlock và chặn triệt để tình huống 2 nhân viên cùng cho mượn 1 thiết bị.

- **Nhận trả thiết bị (`tra.php`):**
  1. Quét QR hoặc chọn phiếu mượn đang mở.
  2. Hỗ trợ trả từng phần: tick chọn các thiết bị trả trong lượt.
  3. Chọn tình trạng khi trả:
     - `TOT` ➔ Thiết bị chuyển `SAN_SANG`, giải phóng phòng học hiện tại về `NULL`.
     - `HU_HONG` ➔ Bắt buộc nhập mô tả hư hỏng, thiết bị chuyển `BAO_TRI`, hệ thống tự động sinh phiếu sự cố `SC-YYYYMMDD-XXXXXXXX`.
  4. Cột sinh tự động `tai_san_chua_tra_id` tự động chuyển về `NULL` khi `thoi_gian_tra` được ghi nhận.
  5. Khi mọi thiết bị trong phiếu đã trả hết, phiếu mượn tự động cập nhật `DA_TRA`.

---

### 5.3. Phân hệ Điều chuyển thiết bị
- Chuyển thiết bị giữa hai phòng học (`phong_hoc_di` ➔ `phong_hoc_den`).
- Thiết bị phải đang nằm ở phòng học đi, sẵn sàng, tình trạng tốt, phù hợp cấu hình thiết bị cố định tại phòng đến.
- Bắt buộc nhập lý do điều chuyển và tick xác nhận cam kết kiểm tra tương thích.
- Phiếu điều chuyển sinh mã `DC-YYYYMMDD-XXXXXXXX`, lưu trữ lịch sử để truy vết luân chuyển.

---

### 5.4. Phân hệ Kiểm kê đối soát
- **Mở đợt kiểm kê:** Hệ thống tự động chụp ảnh sổ sách (`tinh_trang_so_sach`, `trang_thai_so_sach`, `phong_hoc_so_sach_id`) cho toàn bộ tài sản thuộc bàn trực. Trong thời gian kiểm kê, nghiệp vụ mượn/trả/điều chuyển tại bàn trực tạm thời bị khóa nhằm đảm bảo tính toàn vẹn số liệu.
- **Quét kiểm kê thực tế:** Quét QR từng thiết bị đối chiếu: ghi nhận tìm thấy (kèm phòng thực tế và tình trạng thực tế) hoặc đánh dấu chưa tìm thấy.
- **Chốt đợt đối soát:** Tự động phân loại 4 nhóm đối soát:
  1. *Đã kiểm kê*: Vị trí và tình trạng khớp với sổ sách.
  2. *Chưa tìm thấy*: Nguy cơ thất thoát, tự động ghi nhận sự cố `loai_su_co = 'MAT'`.
  3. *Sai vị trí / Hư hỏng*: Thiết bị phát hiện ở phòng khác hoặc có hư hỏng, tự động ghi nhận sự cố `loai_su_co = 'SAI_VI_TRI'` hoặc `'HU_HONG'`.
  4. *Đang mượn lúc mở đợt*: Thiết bị đang lưu hành ngoài phòng học theo phiếu mượn hợp lệ.

---

### 5.5. Phân hệ Xử lý sự cố & Bảo trì
- **Báo cáo sự cố:** Phát sinh tự động khi trả hư hỏng, chốt kiểm kê sai lệch hoặc cán bộ báo cáo chủ động.
- **Quy trình xử lý:**
  1. Sự cố mới ở trạng thái `CHUA_XU_LY`.
  2. Cán bộ Quản lý chuyển sang `DANG_XU_LY` khi mang đi sửa chữa/bảo hành.
  3. Khi sửa chữa xong, nhập kết quả xử lý và xác nhận `DA_XU_LY`. Thiết bị tự động chuyển từ `BAO_TRI` về `SAN_SANG` và tình trạng về `TOT`.

---

### 5.6. Phân hệ Thống kê & Biểu đồ Chart.js
- Phân tích đa chiều dựa trên dữ liệu thật của hệ thống:
  - Biểu đồ xu hướng mượn - trả theo thời gian (7 ngày, 30 ngày, 12 tháng).
  - Biểu đồ phân bổ tài sản theo trạng thái (`SAN_SANG`, `DANG_MUON`, `BAO_TRI`, `NGUNG_SU_DUNG`).
  - Biểu đồ cơ cấu tài sản theo danh mục loại thiết bị.
  - Biểu đồ phân loại người mượn (Giảng viên / Sinh viên).
  - Biểu đồ phân loại nguyên nhân sự cố.
  - Danh sách Top 5 phòng học mượn nhiều nhất và Top 5 thiết bị được khai thác nhiều nhất.
  - Danh sách cảnh báo các phiếu mượn quá hạn cần thu hồi khẩn cấp.
  - Tích hợp giao diện in ấn báo cáo khổ giấy A4 chuẩn cho cán bộ báo cáo ban giám hiệu.

---

## 6. HƯỚNG DẪN DÀNH CHO SINH VIÊN BẢO VỆ ĐỒ ÁN

Khi bảo vệ trước Hội đồng chấm Đồ án Cơ sở ngành, giảng viên thường tập trung vào các câu hỏi kỹ thuật và kiến trúc:

### Câu 1: Tại sao không dùng Framework (Laravel, CodeIgniter) mà dùng PHP thuần?
> **Trả lời:** Đồ án cơ sở ngành yêu cầu sinh viên nắm vững nguyên lý hoạt động căn bản của web: xử lý HTTP Request/Response, cơ chế Cookie/Session, vòng đời xác thực, bảo mật chống SQL Injection bằng Prepared Statements và cấu trúc mã nguồn MVC/Service Layer. Việc viết PHP thuần chuẩn giúp kiểm soát hoàn toàn mã nguồn, hiệu năng cực cao, không phụ thuộc thư viện bên ngoài và chạy mượt mà ngay cả trên máy cấu hình yếu.

### Câu 2: Làm sao chống việc 2 nhân viên cùng cho mượn 1 thiết bị cùng lúc (Race Condition)?
> **Trả lời:** Em sử dụng giao dịch MySQL (`mysqli::begin_transaction()`) kết hợp câu lệnh khóa dòng `SELECT ... FOR UPDATE` với mệnh đề sắp xếp `ORDER BY id ASC`. Việc sắp xếp ID tăng dần giúp triệt tiêu hoàn toàn nguy cơ deadlock giữa các tiến trình. Khi nhân viên A đang thao tác trên thiết bị, giao dịch của nhân viên B sẽ phải chờ hoặc bị từ chối ngay khi thiết bị chuyển sang trạng thái `DANG_MUON`.

### Câu 3: Làm sao đảm bảo không một tài sản nào bị mượn 2 lần ở cấp độ CSDL?
> **Trả lời:** Ngoài việc kiểm tra ở tầng code, trong bảng `chi_tiet_muon` có cột sinh tự động `tai_san_chua_tra_id AS (CASE WHEN thoi_gian_tra IS NULL THEN tai_san_id ELSE NULL END) STORED` và chỉ mục `UNIQUE KEY uq_tai_san_chua_tra (tai_san_chua_tra_id)`. Do MySQL cho phép nhiều giá trị `NULL` trong chỉ mục UNIQUE nhưng chỉ cho phép 1 giá trị khác NULL, một tài sản khi chưa trả (thoi_gian_tra là NULL) chỉ có thể xuất hiện tối đa đúng 1 lần trong toàn bộ CSDL. Đây là cơ chế bảo vệ kép cực kỳ an toàn.

### Câu 4: Cấu trúc mã nguồn của hệ thống tuân theo quy tắc gì?
> **Trả lời:** Toàn bộ hệ thống áp dụng cấu trúc 4 bước nghiêm ngặt trên mỗi trang:
> 1. *Kiểm tra phân quyền:* Gọi `yeu_cau_quyen(...)` kiểm tra RBAC.
> 2. *Xử lý POST (PRG Pattern):* Kiểm tra CSRF token, gọi Service tương ứng để xử lý CSDL trong transaction, sau đó chuyển hướng `chuyen_huong(...)` để tránh F5 gửi lại form.
> 3. *Chuẩn bị dữ liệu hiển thị:* Gọi các hàm đọc dữ liệu từ Service, hoàn toàn không viết câu lệnh SQL trong Controller.
> 4. *Hiển thị View:* Gọi `hien_thi(...)` nạp View HTML, View chỉ việc lặp và in dữ liệu qua hàm bảo vệ XSS `e()`.

---

## 7. QUY CHUẨN GIAO DIỆN (DESIGN SYSTEM)

Hệ thống giao diện được thiết kế theo định hướng **sinh động nhưng chuyên nghiệp**, tối giản bảng màu, đồng bộ khoảng cách và hoạt động **100% ngoại tuyến (offline)**.

### 7.1. Bảng màu chính thức (`:root` trong `src/assets/css/app.css`)

Áp dụng nguyên tắc tỷ lệ màu **60–30–10** (60% trung tính, 30% họ xanh dương, tối đa 10% màu nhấn và màu ngữ nghĩa):

| Biến CSS | Mã màu | Ý nghĩa & Phạm vi sử dụng |
| :--- | :--- | :--- |
| `--mau-chinh` | `#1D5BBF` | Màu chủ đạo: nút chính `.btn-chinh`, liên kết, mục menu đang chọn, viền 2px tiêu đề bảng. |
| `--mau-chinh-dam` | `#164A9A` | Trạng thái rê chuột (hover) hoặc nhấn giữ của nút chính. |
| `--mau-navy` | `#0F2747` | Thanh bên sidebar, chữ tiêu đề trang `.tieu-de-trang`, số liệu lớn thẻ KPI. |
| `--mau-chinh-nhat` | `#E1EBFA` | Nền huy hiệu thông tin, hàng bảng được chọn hoặc rê chuột (hover). |
| `--mau-nhan` | `#0F8B8D` | Màu nhấn xanh ngọc: chuỗi dữ liệu thứ hai biểu đồ, vạch nhấn trang trí (tiết chế < 5%). |
| `--mau-nhan-chu` | `#0B6E70` | Màu chữ nhấn khi cần thiết. |
| `--chu-chinh` | `#1F2937` | Màu chữ nội dung chính trong bảng, biểu mẫu. |
| `--chu-phu` | `#5B6676` | Màu chữ phụ, nhãn trường, nhãn số liệu. |
| `--vien` | `#D8DFE8` | Đường viền mảnh 1px của thẻ, bảng, đường phân cách. |
| `--vien-dam` | `#C5CEDA` | Viền ô nhập liệu `.form-control`, nút phụ `.btn-phu`. |
| `--nen-trang` | `#EDF1F6` | Nền tổng thể của ứng dụng (đủ độ đậm để các thẻ trắng nổi rõ). |
| `--nen-the` | `#FFFFFF` | Nền thẻ nội dung `.card`, bảng dữ liệu, biểu mẫu. |
| `--nen-tieu-de-bang` | `#EAF0F8` | Nền hàng tiêu đề bảng (`thead th`). |
| `--chu-tieu-de-bang` | `#1F3A5F` | Màu chữ hàng tiêu đề bảng (đậm 600, 13px). |
| `--thanh-cong` / `--thanh-cong-nhat` | `#16803C` / `#DFF3E6` | Trạng thái tích cực (Sẵn sàng, Tốt, Đã trả đủ, Đã hoàn tất). |
| `--canh-bao` / `--canh-bao-nhat` | `#92400E` / `#FEF3C7` | Trạng thái lưu ý (Đang xử lý, Bảo trì, Chưa trả, vạch cảnh báo `#D97706`). |
| `--nguy-hiem` / `--nguy-hiem-nhat` | `#C0262D` / `#FBE4E4` | Trạng thái nghiêm trọng (Hư hỏng, Mất, Quá hạn, nút `.btn-nguy-hiem`). |
| `--trung-tinh-nhat` | `#EAEEF3` | Huy hiệu trạng thái trung tính, chữ `--chu-phu`. |

### 7.2. Thang khoảng cách, quy chuẩn bo góc và cỡ chữ

- **Quy chuẩn bo góc (Border Radius):**
  - Ô nhập liệu (`.form-control`, `.o-nhap`), nút bấm (`.btn`), huy hiệu (`.huy-hieu`): bo đúng **4px**.
  - Thẻ (`.card`, `.the-noi-dung`), bảng (`.table-responsive`): bo đúng **6px**.
  - Tuyệt đối không dùng bo góc lớn dạng viên thuốc, không dùng bóng mềm đổ bóng lan rộng.
- **Thang khoảng cách (Spacing Scale):**
  - `--kc-1`: `4px` | `--kc-2`: `8px` | `--kc-3`: `12px` | `--kc-4`: `16px` | `--kc-5`: `24px` | `--kc-6`: `32px`.
- **Thang cỡ chữ (Typography Scale):**
  - Tiêu đề trang (`.tieu-de-trang`): `24px`, đậm `700`, màu `--mau-navy`.
  - Tiêu đề khối/thẻ (`.tieu-de-khoi`): `16px`, đậm `600`, màu `--mau-navy`.
  - Chữ nội dung & ô nhập: `14px`, đậm `400`.
  - Nhãn trường (`.form-label`, `.nhan-truong`): `13.5px`, đậm `600`.
  - Tiêu đề cột bảng: `13px`, đậm `600`, màu `--chu-tieu-de-bang`.
  - Số liệu lớn trên thẻ KPI: `28px`, đậm `700`, màu `--mau-navy`, `font-variant-numeric: tabular-nums`.

### 7.3. Danh mục 30 biểu tượng SVG Lucide (`src/assets/icons/sprite.svg`)

Toàn bộ biểu tượng được đóng gói nội bộ tại `src/assets/vendor/lucide/` (kèm giấy phép ISC `LICENSE`) và gộp thành `src/assets/icons/sprite.svg`. Giao diện gọi hàm trợ giúp `icon(string $ten, string $lop = ''): string` trong `src/app/views/partials/icon.php`.

1. **Điều hướng thanh bên (10 biểu tượng):** `layout-dashboard`, `package`, `repeat`, `arrow-left-right`, `clipboard-check`, `users`, `folder-tree`, `user-cog`, `wrench`, `bar-chart-3`.
2. **Nghiệp vụ & thao tác (13 biểu tượng):** `qr-code`, `scan-line`, `plus`, `pencil`, `trash-2`, `eye`, `search`, `printer`, `save`, `log-out`, `menu`, `x`, `filter`, `rotate-ccw`, `lock`, `arrow-left`.
3. **Thông báo & trạng thái (4 biểu tượng):** `check-circle-2`, `alert-triangle`, `alert-circle`, `info`.

**Vị trí được phép đặt biểu tượng:**
- Mục điều hướng thanh bên, góc phải trên thẻ KPI (biểu tượng nét xám 20px, không có ô vuông hay hình tròn màu bao quanh), nút hành động chính/phụ/nguy hiểm, nút thao tác cuối dòng bảng (`.btn-bieu-tuong`), bên trái ô tìm kiếm (`.o-tim-kiem`), và đầu thông báo flash (`.alert`).
- **Không đặt biểu tượng** trong huy hiệu trạng thái (`.huy-hieu`), tiêu đề cột bảng (`<th>`), nhãn trường biểu mẫu (`<label>`), hoặc tiêu đề thẻ (`<h2>`, `<h3>`).

### 7.4. Ba kiểu nút bấm thống nhất toàn hệ thống

1. **Nút chính (`.btn-chinh`, `.btn-primary`):** Nền `--mau-chinh`, viền `--mau-chinh`, chữ trắng. Dùng cho hành động xác nhận, lưu, thêm mới quan trọng nhất.
2. **Nút phụ (`.btn-phu`, `.btn-outline-secondary`):** Nền trắng, viền `--vien-dam`, chữ `--chu-chinh`. Dùng cho hủy, quay lại, lọc, tác vụ thứ cấp.
3. **Nút nguy hiểm (`.btn-nguy-hiem`, `.btn-danger`):** Nền `--nguy-hiem`, chữ trắng. Dùng cho xóa, khóa đợt, hủy bỏ giao dịch.

### 7.5. Quy tắc kỷ luật giao diện & Hộp thoại tương tác bật lên (Popup Modal)

- **Không thêm chữ hiển thị vô nghĩa:** Giữ nguyên nội dung chữ nghiệp vụ tiếng Việt rõ ràng; các nút biểu tượng vuông `.btn-bieu-tuong` luôn có `aria-label` và `title` đầy đủ.
- **Không dùng CSS nội tuyến (`style=""`):** Toàn bộ quy tắc định kiểu phải đặt trong `src/assets/css/app.css` (hoặc `dang-nhap.css`).
- **Tương tác Xem / Thêm / Sửa / Xóa bằng hộp thoại bật lên:**
  - Mọi thao tác **Xem chi tiết**, **Thêm mới**, **Chỉnh sửa** của các đối tượng được mở trực tiếp trong hộp thoại `#modalTuongTac` (tải và gửi biểu mẫu qua AJAX trong `src/assets/js/app.js`), giúp cán bộ thao tác nhanh mà không bị gián đoạn ngữ cảnh danh sách.
  - Mọi thao tác **Xóa / Khóa / Mở khóa** có thuộc tính `data-xac-nhan` hiển thị hộp thoại xác nhận `#modalXacNhan` đồng bộ thay cho hộp thoại `confirm()` mặc định của trình duyệt.

