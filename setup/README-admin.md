# Phạm vi Quản trị viên

Tài khoản `QUAN_TRI` chỉ truy cập bốn mục: Tài khoản, Tài sản, Phòng trực và Phòng học. Trang Tổng quan hiển thị số lượng, số đang hoạt động và tình trạng tài sản của bốn mục này. Danh sách cuối trang dẫn đến các tài sản cập nhật gần đây.

- Quản trị viên thêm/sửa/xóa tài khoản, tài sản, phòng trực và phòng học. Tài sản hoặc danh mục đã có tham chiếu nghiệp vụ không thể xóa vĩnh viễn; dùng ngừng sử dụng hoặc ngừng hoạt động theo ràng buộc của hệ thống.
- Danh mục loại tài sản và hồ sơ người mượn thuộc vai trò `QUAN_LY`. Nhân viên có thể xem người mượn theo phạm vi phòng trực nhưng không được sửa hồ sơ.
- Quản trị viên không có quyền trực tiếp vào trang Người mượn, Loại tài sản, mượn–trả, kiểm kê, điều chuyển hoặc báo cáo nghiệp vụ. Kiểm tra quyền thực hiện ở máy chủ, kể cả khi nhập URL trực tiếp.

Kiểm thử dashboard bằng transaction, toàn bộ dữ liệu thử được hoàn tác:

```powershell
C:\xampp\php\php.exe setup/verify_admin_dashboard.php
```

Trang Tổng quan lấy số liệu thật từ CSDL; bản phác thảo `Trang tổng quan - Dashboard.pdf` chỉ được dùng làm tham khảo bố cục.
