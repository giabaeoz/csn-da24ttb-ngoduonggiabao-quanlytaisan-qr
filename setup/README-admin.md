# Phạm vi Quản trị viên

Tài khoản `QUAN_TRI` quản lý năm mục: Tài khoản, Tài sản, Người mượn, Phòng trực và Phòng học. Trang Tổng quan hiển thị số lượng và tình trạng dữ liệu của các mục này. Danh sách cuối trang dẫn đến các tài sản cập nhật gần đây.

- Quản trị viên thêm/sửa/xóa tài khoản, tài sản, phòng trực và phòng học. Tài sản hoặc danh mục đã có tham chiếu nghiệp vụ không thể xóa vĩnh viễn; dùng ngừng sử dụng hoặc ngừng hoạt động theo ràng buộc của hệ thống.
- Hồ sơ người mượn do cả `QUAN_TRI` và `QUAN_LY` quản lý trên toàn hệ thống. Nhân viên chỉ thao tác theo phạm vi nghiệp vụ của phòng trực.
- Quản trị viên không có quyền trực tiếp vào trang Loại tài sản, mượn–trả, kiểm kê, điều chuyển hoặc báo cáo nghiệp vụ. Kiểm tra quyền thực hiện ở máy chủ, kể cả khi nhập URL trực tiếp.

Kiểm thử dashboard bằng transaction, toàn bộ dữ liệu thử được hoàn tác:

```powershell
C:\xampp\php\php.exe setup/verify_admin_dashboard.php
```

Trang Tổng quan lấy số liệu thật từ CSDL; bản phác thảo `Trang tổng quan - Dashboard.pdf` chỉ được dùng làm tham khảo bố cục.

## Kiểm thử ổn định tài khoản

Tài khoản không bị xóa vĩnh viễn. Quản trị viên dùng chức năng khóa/mở khóa để giữ nguyên lịch sử nghiệp vụ, đồng thời có thể đặt lại mật khẩu khi cần.

```powershell
C:\xampp\php\php.exe setup/verify_admin_accounts.php
C:\xampp\php\php.exe setup/verify_admin_borrowers.php
```
