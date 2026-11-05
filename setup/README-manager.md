# Phân hệ Quản lý danh mục và tài sản

Đăng nhập bằng tài khoản vai trò `QUAN_LY`, mở `quan-ly/index.php`. Giao diện bám bản phác thảo `Trang tài sản.pdf`: số liệu tổng quan, bộ lọc, bảng tài sản, chọn in QR, xem chi tiết và sửa từng dòng.

Thứ tự nhập mới: loại tài sản, phòng học, phòng trực, rồi tài sản. Trang tài sản có liên kết nhanh đến các danh mục. Mã tài sản tạo mã QR `TVU:<mã>` và cố định sau khi lưu để nhãn đã in vẫn dùng được. Phòng tương thích lưu bằng hai cột đã bổ sung ở `setup/migrate_asset_compatibility.php`; phải chạy migration này sau khi cài CSDL mới.

Các biểu mẫu dùng POST, token CSRF và kiểm tra vai trò phía máy chủ. Khi phòng hiện tại được chọn cho tài sản theo phòng, phòng đó phải có trong danh sách tương thích. Tài sản đang có lượt mượn chưa trả chỉ được sửa tên và mô tả. Thao tác ngừng sử dụng giữ nguyên mã QR và lịch sử, chỉ thực hiện sau khi tài sản được trả. Danh mục đang được dùng không thể xóa; mã phòng có trong danh sách tương thích tài sản không thể đổi hoặc xóa.

Trang `admin/tai-san.php` của Quản trị viên có thêm, sửa, xóa tài sản. Quản trị viên quản lý phòng học và phòng trực, còn loại tài sản do Quản lý quản lý. Quản lý cũng quản lý hồ sơ người mượn tại trang Người mượn. Quản trị viên không trực tiếp quản lý người mượn hoặc thực hiện mượn–trả, kiểm kê, điều chuyển. Nhân viên không thể vào trang quản lý tài sản.

Kiểm thử thủ công: tạo một phòng và loại mới, tạo tài sản theo phòng, mở chi tiết/QR, sửa tên, lọc theo phòng tương thích, rồi ngừng sử dụng. Với tài sản đang được mượn, kiểm tra biểu mẫu chặn đổi trạng thái và ngừng sử dụng.

Kiểm thử CSDL bằng transaction, tự hoàn tác mọi bản ghi thử:

```powershell
C:\xampp\php\php.exe setup/verify_manager.php
```
