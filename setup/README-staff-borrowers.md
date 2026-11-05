# Người mượn: nghiệp vụ riêng theo vai trò

- **Quản lý:** `src/quan-ly/nguoi-muon.php` dùng `src/quan-ly/views/` để thêm, sửa, xóa hồ sơ toàn hệ thống.
- **Nhân viên:** `src/nhan-vien/nguoi-muon.php` dùng `src/nhan-vien/views/`, `borrowers.css` và `src/includes/staff-borrowers.php` để tra cứu, xem phiếu thuộc phòng trực và cập nhật bốn trường cơ bản của người đã có phiếu tại phòng trực mình. Mã hồ sơ và loại người mượn do Quản lý quản lý.
- **Quản trị:** không có quyền vào hai trang nghiệp vụ người mượn. URL cũ `src/admin/nguoi-muon.php` chỉ chuyển hướng người dùng đúng vai trò, trả 403 cho Quản trị.

Trang Nhân viên bám theo phác thảo `Trang người mượn.pdf`: tab Đang mượn/Tất cả/Quá hạn, bộ lọc người mượn và phòng học, bảng tổng hợp, hồ sơ đang chọn và lịch sử phiếu. Mỗi truy vấn lịch sử và mỗi lần lưu đều giới hạn theo phòng trực trên máy chủ. Lưu thay đổi dùng prepared statement, CSRF và giao dịch MySQL. Không cần đổi schema.

Chạy thử tại gốc dự án khi MySQL hoạt động:

```powershell
& 'C:/xampp/php/php.exe' setup/verify_staff_borrowers.php
```

Script tạo phòng trực, Nhân viên, người mượn, tài sản và phiếu tạm; kiểm tra quyền sửa đúng phòng trực rồi dọn dữ liệu trong `finally`.
