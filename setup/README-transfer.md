# Điều chuyển tài sản giữa các phòng học

Nhân viên mở `src/nhan-vien/dieu-chuyen.php`, chọn **Phòng đi** và **Phòng đến**, nhập lý do, rồi quét QR hoặc nhập mã từng thiết bị. Phiếu chỉ thay đổi `tai_san.phong_hoc_hien_tai_id`; `tai_san.phong_truc_id` luôn được giữ nguyên.

Hệ thống chỉ nhận tài sản:

- thuộc phòng trực đang được giao cho Nhân viên;
- đang ở đúng phòng học nguồn;
- có tình trạng Tốt và trạng thái Sẵn sàng;
- không có chi tiết mượn chưa trả;
- phù hợp với phòng học đích theo phạm vi linh hoạt, danh sách phòng tương thích hoặc phòng phù hợp cũ;
- không nằm trong phòng trực đang có đợt kiểm kê mở.

Khi xác nhận, `dieu_chuyen`, `chi_tiet_dieu_chuyen` và vị trí hiện tại của mọi tài sản được ghi trong một transaction, có khóa dòng và prepared statements. Quản lý và Quản trị bị chặn khỏi trang nghiệp vụ này.

## Cấu trúc

- `src/includes/transfer-service.php`: validation, phân quyền phạm vi và transaction.
- `src/nhan-vien/dieu-chuyen.php`: controller và truy vấn trang.
- `src/nhan-vien/views/transfer-*.php`: biểu mẫu, chi tiết phiếu và lịch sử.
- `src/nhan-vien/transfer.js`, `transfer.css`: quét QR, danh sách tạm và giao diện.
- `setup/verify_transfer.php`: bài thử nghiệp vụ tự tạo và dọn dữ liệu.

## Kiểm thử

```powershell
& 'C:/xampp/php/php.exe' setup/verify_transfer.php
```
