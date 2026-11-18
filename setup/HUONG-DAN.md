# HƯỚNG DẪN CÀI ĐẶT & VẬN HÀNH HỆ THỐNG (HUONG-DAN.md)

**Dự án:** Quản lý mượn – trả và kiểm kê tài sản bằng mã QR  
**Công nghệ:** PHP 8.2+ thuần (MySQLi prepared statements, `$db->execute_query()`), MySQL 8.0+, Bootstrap 5, jsQR, qrcode.js, Chart.js (100% offline vendor).

---

## 1. PHƯƠNG ÁN 1: TRIỂN KHAI NHANH BẰNG DOCKER (KHUYÊN DÙNG)

Chỉ cần máy tính đã cài đặt **Docker Desktop**, mở PowerShell tại thư mục dự án và chạy:

```powershell
docker compose up -d
```

- **Hệ thống Web:** [http://localhost:8088](http://localhost:8088)
- **phpMyAdmin:** [http://localhost:8081](http://localhost:8081)
- **Tài khoản đăng nhập có sẵn:**
  - `admin` / `admin123` (Quản trị viên)
  - `quanly01` / `quanly123` (Cán bộ Quản lý)
  - `nhanvien01` / `nhanvien123` (Nhân viên trực phòng D6)

*(Toàn bộ CSDL 13 bảng, dữ liệu mẫu thực tế và 3 tài khoản đã được nạp tự động hoàn toàn).*

---

## 2. PHƯƠNG ÁN 2: CÀI ĐẶT THỦ CÔNG QUA XAMPP (TRUYỀN THỐNG)

### Yêu cầu môi trường
- **Hệ điều hành:** Windows 10/11, Linux hoặc macOS.
- **PHP:** Phiên bản **8.2 trở lên** (đã bật extension `mysqli`, `mbstring`, `json`, `curl`).
- **MySQL / MariaDB:** Phiên bản **MySQL 8.0+** (hỗ trợ ràng buộc CHECK, Generated Columns STORED, InnoDB row-level locking `FOR UPDATE`).
- **Môi trường chạy đề xuất:** XAMPP for Windows hoặc PHP Built-in Server `php -S 127.0.0.1:8000 -t src`.

---

### Quy trình cài đặt thủ công (3 Bước)

#### Bước 1: Khởi tạo Cơ sở dữ liệu và Dữ liệu mẫu

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

## 7. QUY CHUẨN GIAO DIỆN (DESIGN SYSTEM ĐỒNG BỘ)

Hệ thống giao diện được thiết kế theo phong cách **phần mềm quản trị nội bộ hiện đại, chuyên nghiệp**, lấy cảm hứng từ bản thiết kế chuẩn dashboard quản lý tài sản QR. Giao diện đậm đà màu chủ đạo xanh dương, cấu trúc thông tin dày dặn, nhất quán, loại bỏ hoàn toàn "dáng vẻ AI" và hoạt động **100% ngoại tuyến (offline)** không phụ thuộc internet.

### 7.1. Bảng màu chính thức (`:root` trong `src/assets/css/app.css`)

Tuân thủ nguyên tắc tỷ lệ màu **60–30–10** (60% trung tính, 30% họ xanh dương, tối đa 10% màu nhấn và màu ngữ nghĩa):

| Biến CSS | Mã màu | Ý nghĩa & Phạm vi sử dụng |
| :--- | :--- | :--- |
| `--mau-chinh` | `#1768D8` | Màu chủ đạo: nút chính `.btn-chinh`, liên kết, mục menu đang chọn, viền nhấn. |
| `--mau-chinh-dam` | `#114FA7` | Trạng thái rê chuột (hover) hoặc nhấn giữ của nút chính. |
| `--mau-chinh-sang` | `#1D6FE0` | Nền mục menu đang chọn trên thanh bên sidebar (bo góc 10px). |
| `--mau-navy` | `#0F2747` | Chữ tiêu đề trang `.tieu-de-trang`, số liệu lớn thẻ KPI, tiêu đề khối. |
| `--mau-chinh-nhat` | `#E0EDFD` | Nền hàng bảng được chọn hoặc rê chuột (hover), nền khối mượn-trả. |
| `--thanh-ben-tren` | `#1A5BC4` | Điểm bắt đầu gradient nền thanh bên (xanh dương sáng hơn). |
| `--thanh-ben-duoi` | `#0B3380` | Điểm kết thúc gradient nền thanh bên (xanh navy đậm). |
| `--mau-nhan` | `#0F8B8D` | Màu nhấn xanh ngọc: chuỗi Trả trên biểu đồ xu hướng (tiết chế < 5%). |
| `--mau-nhan-chu` | `#0B6E70` | Màu chữ nhấn khi cần thiết. |
| `--chu-chinh` | `#1F2937` | Màu chữ nội dung chính trong bảng, biểu mẫu. |
| `--chu-phu` | `#5B6676` | Màu chữ phụ, nhãn trường, mô tả. |
| `--vien` | `#CBD5E1` | Đường viền mảnh 1px của thẻ, bảng, đường phân cách. |
| `--vien-dam` | `#94A3B8` | Viền ô nhập liệu `.form-control`, nút phụ `.btn-phu`. |
| `--nen-trang` | `#F1F5F9` | Nền tổng thể của ứng dụng (xám nhạt trung tính giúp thẻ trắng nổi bật). |
| `--nen-the` | `#FFFFFF` | Nền thẻ nội dung `.card`, bảng dữ liệu, biểu mẫu. |
| `--nen-tieu-de-bang` | `#F8FAFC` | Nền hàng tiêu đề bảng (`thead th`). |
| `--chu-tieu-de-bang` | `#0F2747` | Màu chữ hàng tiêu đề bảng (đậm 600, 13px). |
| `--the-xanh` / `--the-la` | `#EDF4FE` / `#EDFAF2` | Nền pha màu nhạt riêng cho thẻ Tổng tài sản / Có thể mượn. |
| `--the-cam` / `--the-do` | `#FEF3EB` / `#FDF1F1` | Nền pha màu nhạt riêng cho thẻ Đang mượn / Quá hạn. |
| `--the-tim` | `#F5EDFA` | Nền pha màu nhạt cho nút thao tác nhanh Quét mã QR. |
| `--hh-do` / `--hh-do-chu` | `#FBE6E7` / `#A82025` | Huy hiệu trạng thái Quá hạn, Hư hỏng, Mất. |
| `--hh-vang` / `--hh-vang-chu` | `#FEF3C7` / `#78350F` | Huy hiệu Đến hạn hôm nay, Bảo trì, Cảnh báo. |
| `--hh-xanh` / `--hh-xanh-chu` | `#E0EDFD` / `#1355B3` | Huy hiệu Đang mượn, Đang kiểm kê. |
| `--hh-la` / `--hh-la-chu` | `#DCF4E7` / `#0E6B34` | Huy hiệu Sẵn sàng, Tốt, Đã trả đủ, Hoàn tất. |
| `--hh-xam` / `--hh-xam-chu` | `#E9ECEF` / `#495057` | Huy hiệu Đã hủy, Trạng thái khác. |

### 7.2. Quy chuẩn bo góc, bóng mờ và cỡ chữ

- **Quy chuẩn bo góc (Border Radius 4 nấc cố định):**
  - **`12px` (`--bo-the`):** Thẻ nội dung (`.card`, `.the-noi-dung`, `.banner-tong-quan`).
  - **`10px` (`--bo-nut`):** Nút bấm chính/phụ (`.btn`, `.btn-nhanh`), mục menu điều hướng đang chọn.
  - **`8px` (`--bo-o`):** Ô nhập liệu (`.form-control`, `.o-nhap`, `.form-select`).
  - **`999px` (`--bo-vien-thuoc`):** Huy hiệu trạng thái (`.huy-hieu`, `.huy-hieu-tt`) và nút thao tác trong bảng (`.btn-vien-thuoc`).
- **Quy chuẩn bóng mờ (Shadow):**
  - Chỉ dùng một mức bóng duy nhất: `--bong-the: 0 2px 10px rgba(16, 62, 140, .08)`. Không dùng bóng mềm lan rộng mờ ảo.
- **Thang khoảng cách (Spacing Scale):**
  - Tuân thủ thang bội số chuẩn: `4px` (kc-1) $\rightarrow$ `8px` (kc-2) $\rightarrow$ `12px` (kc-3) $\rightarrow$ `16px` (kc-4) $\rightarrow$ `24px` (kc-5) $\rightarrow$ `32px` (kc-6).
- **Thang cỡ chữ (Typography Scale):**
  - Tiêu đề trang (`.tieu-de-trang`): `24px`, đậm `700`, màu `--mau-navy`.
  - Tiêu đề khối/thẻ (`.tieu-de-khoi`): `16px`, đậm `600`, màu `--mau-navy`.
  - Chữ nội dung & ô nhập: `14px`, đậm `400`.
  - Nhãn trường (`.form-label`, `.nhan-truong`): `13.5px`, đậm `600`.
  - Tiêu đề cột bảng: `13px`, đậm `600`, màu `--chu-tieu-de-bang`.
  - Số liệu lớn trên thẻ KPI: `28px`, đậm `700`, màu `--mau-navy`, `font-variant-numeric: tabular-nums`.

### 7.3. Khung chung toàn hệ thống

1. **Thanh bên (Sidebar):**
   - Chiều rộng cố định 240px, nền gradient từ `--thanh-ben-tren` xuống `--thanh-ben-duoi`.
   - Đáy thanh bên có họa tiết sóng SVG nội tuyến bán trong suốt tinh tế.
   - Logo "TÀI SẢN QR" kèm biểu tượng QR trắng sắc nét.
   - Mục menu đang chọn bo góc 10px, nền xanh sáng `--mau-chinh-sang`, có vạch sáng định vị.
   - Nút "Đăng xuất" ở chân menu có viền mờ trong suốt, chữ trắng rõ ràng.
2. **Thanh trên (Topbar):**
   - Chiều cao cố định 64px, nền trắng, đường viền đáy mảnh 1px.
   - Bên trái: Đường dẫn điều hướng (Breadcrumb) có biểu tượng ngôi nhà (`home`), mục hiện tại in đậm.
   - Bên phải: Chuông thông báo có chấm đỏ cảnh báo khi hệ thống có lượt mượn quá hạn (`tong_quan_co_qua_han`), ảnh đại diện tròn nền xanh chứa hai chữ cái viết tắt của cán bộ đăng nhập (`viet_tat_hai_chu_cai()`), khối tên vai trò và mũi tên mở menu thả xuống.

### 7.4. Quy chuẩn thành phần nghiệp vụ

1. **Thẻ thống kê KPI pha màu (`.the-kpi-pha-mau`):**
   - Mỗi thẻ có nền pha màu nhạt riêng (xanh dương, xanh lá, cam, đỏ).
   - Biểu tượng lớn màu đậm đặt bên trái số liệu. Số liệu đậm 28px, nhãn mô tả đặt bên dưới.
   - Biểu tượng khối mờ kích thước lớn đặt chìm làm hình nền trang trí ở góc phải dưới thẻ.
   - Nguyên tắc bảo toàn số liệu: $\text{Tổng} = \text{Có thể mượn} + \text{Đang mượn} + \text{Khác}$.
2. **Hàng thao tác nhanh (`.btn-nhanh`):**
   - Ba nút lớn nằm ngang: "Lập phiếu mượn" (xanh dương nhạt), "Nhận trả tài sản" (xanh lá nhạt), "Quét mã QR" (tím nhạt).
   - Mỗi nút có biểu tượng Lucide phía trước chữ, bo góc 10px, hiệu ứng hover nhấc nhẹ 1px.
3. **Nút thao tác trong bảng (`.btn-vien-thuoc`):**
   - Bo tròn hình viên thuốc 999px, viền mảnh 1px, kích thước nhỏ gọn (`padding: 3px 10px`, `font-size: 12px`, `font-weight: 500`).
   - Luôn có chữ biểu thị rõ nghĩa nghiệp vụ: `[Chi tiết]`, `[Sửa]`, `[Xóa]`, `[Nhận trả]`, `[Xem]`, `[Báo cáo]`, `[QR]`.
   - Loại bỏ hoàn toàn các nút vuông icon rời rạc (`btn-bieu-tuong`).
4. **Huy hiệu trạng thái (`.huy-hieu`, `.huy-hieu-tt`):**
   - Hình viên thuốc 999px, chiều cao 26px, `padding: 3px 12px`, `font-size: 12.5px`, `font-weight: 600`.
   - Màu sắc ngữ nghĩa tương phản cao: Đỏ (Quá hạn/Hư hỏng), Vàng (Đến hạn hôm nay/Bảo trì), Xanh dương (Đang mượn), Xanh lá (Sẵn sàng/Đã trả đủ/Hoàn tất). Không đặt biểu tượng bên trong huy hiệu.
5. **Hệ thống 3 biểu đồ Chart.js (Ngoại tuyến 100%):**
   - **Xu hướng mượn – trả trong tuần:** Biểu đồ vùng/đường 2 chuỗi (*Mượn* màu xanh dương, *Trả* màu xanh ngọc), trục T2..CN.
   - **Lượt mượn theo nhóm tài sản:** Biểu đồ cột xanh dương, số liệu hiển thị trực tiếp trên đầu cột, tổng số khớp 100% chuỗi Mượn của biểu đồ xu hướng.
   - **Tình trạng tài sản:** Biểu đồ vành khuyên (donut) độ rộng 70%, chính giữa hiển thị tổng số tài sản, chú giải bên phải có chấm tròn, tên, số lượng và phần trăm.

### 7.5. Kỷ luật giao diện & Không "dáng vẻ AI"

- **Không gradient tùy tiện:** Chỉ duy nhất thanh bên sidebar được sử dụng gradient xanh đậm có chủ đích; toàn bộ thẻ, nút và thanh trên đều dùng màu phẳng (flat color).
- **Không ảnh nền tùy tiện:** Chỉ duy nhất banner trang Tổng quan sử dụng ảnh minh họa phẳng bản vẽ; các trang chức năng khác không dùng ảnh nền hay banner chào mừng.
- **Không chữ thừa:** Loại bỏ hoàn toàn các câu chào hỏi quảng cáo ("Chúc một ngày tốt lành", "Hệ thống quản lý thông minh 4.0"), các dòng chú thích hiển nhiên dưới tiêu đề trang, và comment HTML giải thích mã nguồn trong views.
- **Hoạt động 100% ngoại tuyến:** Thư viện Bootstrap, Lucide icons, Chart.js, jsQR, QRCode.js đều được lưu trữ cục bộ trong thư mục `src/assets/vendor/`. Không sử dụng bất kỳ liên kết CDN hay tải font nào từ Internet.

