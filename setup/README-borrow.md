# Nghiệp vụ mượn – trả cho Nhân viên

Không cần thay đổi schema: dùng `phieu_muon`, `chi_tiet_muon`, `tai_san`, `su_co_tai_san` hiện có. Cần chạy migration tương thích phòng trong `README-assets.md` trước.

## Vai trò và luồng thao tác

- **Quản trị** phân công phòng trực cho tài khoản Nhân viên và quản lý tài sản, phòng trực, phòng học.
- **Quản lý** thêm, sửa và xóa hồ sơ người mượn tại `src/quan-ly/nguoi-muon.php`.
- **Nhân viên** tra cứu người mượn tại `src/nhan-vien/nguoi-muon.php`; có thể cập nhật mã số trường, họ tên, lớp/đơn vị và điện thoại khi người đó đã có phiếu tại phòng trực mình. Nhân viên không tạo, xóa hoặc đổi loại/mã hồ sơ.
- **Nhân viên** vào `src/nhan-vien/index.php`, chọn **Lập phiếu mượn** hoặc **Nhận trả thiết bị**. Chỉ phiếu và tài sản thuộc phòng trực được phân công mới được thao tác.

Trên trang Mượn, tìm/chọn người mượn, chọn phòng học đang hoạt động, hạn trả trong tương lai, rồi chọn tài sản đang sẵn sàng và phù hợp phòng. Có thể nhập mã tài sản hoặc mã QR `TVU:...`, quét bằng camera trên HTTPS/localhost, hoặc chọn ảnh QR PNG/JPEG/WebP. Có thể quét trước khi chọn phòng; tài sản chỉ được thêm sau khi phòng đã chọn phù hợp. Một phiếu có thể gồm nhiều tài sản.

Trên trang Trả, tìm theo mã phiếu, mã số người mượn, tên, mã tài sản hoặc QR. Quét đúng QR của tài sản chưa trả sẽ mở phiếu tương ứng và chọn sẵn thiết bị đó. Chọn từng thiết bị để nhận. Trả một phần giữ phiếu ở trạng thái đang mượn. Khi trả hư hỏng phải ghi mô tả; tài sản chuyển sang Bảo trì và hệ thống lập sự cố. Khi mọi thiết bị đã trả, phiếu chuyển sang Đã trả.

Ứng dụng kiểm tra vai trò trên máy chủ, CSRF, phòng trực và dữ liệu ngay trước khi ghi. Ghi phiếu, chi tiết, trạng thái tài sản và sự cố trong giao dịch MySQL; khóa dòng và ràng buộc unique của CSDL chặn mượn trùng. Các trạng thái cũ được đọc từ chi tiết chưa trả.

Bootstrap 5.3.3 đã lưu tại `src/nhan-vien/vendor` để demo ngoại tuyến. Giấy phép MIT đi kèm trong cùng thư mục. Nguồn: https://getbootstrap.com/docs/5.3/getting-started/download/

## Kiểm thử

Chạy tại thư mục gốc dự án khi MySQL đã khởi động:

```powershell
C:\xampp\php\php.exe setup/verify_borrow.php
```

Script tự tạo phòng trực, Nhân viên, hồ sơ người mượn và thiết bị thử với mã `TEST-...`; nó kiểm tra mượn sai phòng, mượn trùng, trả từng phần, trả hư hỏng và phân quyền phòng trực, rồi xóa dữ liệu thử trong `finally`. Bài thử không phụ thuộc vào phòng trực đang kiểm kê của hệ thống thật. Giao diện cần đăng nhập tài khoản `NHAN_VIEN` được gán phòng trực có tài sản; nếu chưa có người mượn thật, Quản lý phải tạo hồ sơ trước.
