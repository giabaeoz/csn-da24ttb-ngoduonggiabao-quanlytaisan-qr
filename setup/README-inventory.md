# Kiểm kê tài sản cho Nhân viên

## Cách sử dụng

1. Đăng nhập bằng tài khoản **Nhân viên** đã được gán một phòng trực đang hoạt động.
2. Vào **Kiểm kê** ở thanh bên và chọn **Tạo đợt kiểm kê**. Nhập tên, loại kiểm kê, ghi chú nếu cần. Hệ thống chụp danh sách tài sản đang sử dụng của phòng trực tại thời điểm mở đợt.
3. Quét QR bằng camera hoặc ảnh, hoặc nhập mã tài sản. Với mỗi tài sản cần kiểm thực tế, chọn **Có mặt** hoặc **Chưa tìm thấy**. Nếu có mặt, ghi tình trạng và vị trí thực tế. Mỗi lần bấm **Lưu kết quả đối chiếu** được lưu ngay; có thể quay lại sửa khi đợt còn mở.
4. Khi số **Chưa kiểm** bằng 0, bấm **Hoàn tất kiểm kê**. Đợt đã chốt chỉ xem được.

Tài sản đang mượn lúc mở đợt vẫn xuất hiện để đối chiếu sổ sách nhưng không cần kiểm thực tế. Có thể mở phiếu mượn từ hàng tài sản đó. Chỉ có một đợt đang mở cho mỗi phòng trực. Trong lúc đợt mở, hệ thống chặn phiếu mượn mới và thay đổi hồ sơ tài sản trong phòng trực; nhận trả thiết bị vẫn thực hiện được.

Khi chốt, kết quả **chưa tìm thấy**, **hư hỏng** hoặc **sai vị trí** tự tạo bản ghi `su_co_tai_san`. Tài sản liên quan được chuyển sang trạng thái không cho mượn để chờ xử lý. Đợt kiểm kê chỉ thuộc phòng trực của Nhân viên đang đăng nhập. Vai trò Quản lý và Quản trị không được thao tác trang này.

## Cấu trúc mã nguồn

| Tệp | Nhiệm vụ |
| --- | --- |
| `src/nhan-vien/kiem-ke.php` | Điều phối yêu cầu, kiểm tra quyền và nạp dữ liệu trang |
| `src/nhan-vien/views/inventory-*.php` | Các khối giao diện tạo đợt, đối chiếu và lịch sử |
| `src/nhan-vien/inventory.css`, `inventory.js` | Trình bày và tương tác biểu mẫu/QR |
| `src/includes/inventory-service.php` | Giao dịch mở đợt, ghi nhận, chốt đợt và sự cố |
| `src/includes/staff-scope.php` | Kiểm tra nhân viên và khóa phòng trực được giao |
| `src/nhan-vien/muon-tra.php`, `views/borrow-form.php`, `views/return-form.php`, `borrow-ui.js` | Trang mượn–trả đã tách điều phối, giao diện và tương tác |

Giao diện dựa theo `Phát thảo giao diện/Trang kiểm kê.pdf`: khu quét QR và thông tin đợt, bốn ô số liệu, bộ lọc, bảng đối chiếu, kết quả và các đợt gần đây. CSDL hiện tại dùng các bảng `dot_kiem_ke`, `chi_tiet_kiem_ke`, `su_co_tai_san`; bước này không cần thay đổi schema. Vì `dot_kiem_ke` chỉ gắn với `phong_truc_id`, phạm vi đợt là phòng trực; vị trí phòng học thực tế được ghi trên từng tài sản.

## Kiểm thử

Chạy tại thư mục gốc dự án bằng PHP của XAMPP:

```powershell
& 'C:/xampp/php/php.exe' setup/verify_inventory.php
& 'C:/xampp/php/php.exe' setup/verify_borrow.php
```

Các lệnh trên tạo dữ liệu thử riêng rồi dọn trong `finally`. Trước khi trình diễn, nên thử thủ công với một phòng trực đã có tài sản: mở đợt, quét QR của một thiết bị, ghi nhận từng thiết bị và chốt. Kiểm tra sự cố được tạo khi có chênh lệch.
