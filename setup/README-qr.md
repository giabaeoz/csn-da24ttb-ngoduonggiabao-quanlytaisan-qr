# Tạo và quét mã QR tài sản

## Quy ước mã

Khi Quản trị hoặc Quản lý tạo tài sản, hệ thống lưu `tai_san.ma_qr = 'TVU:' + ma_tai_san`. Cột `ma_qr` đã có ràng buộc duy nhất; không cần migration mới. Mã tài sản và nội dung QR được giữ cố định khi sửa để nhãn đã in tiếp tục dùng được. QR chỉ chứa mã nhận diện, không chứa thông tin cá nhân hay quyền truy cập.

Trang **Tài sản** có ảnh xem trước QR ngay khi nhập mã, mã QR ở phần chi tiết, nút tải SVG/PNG và chức năng in các nhãn đã chọn. Quản trị và Quản lý có thể bật camera hoặc chọn ảnh QR để mở trực tiếp hồ sơ tài sản. Nhân viên dùng cùng mã QR trên trang **Mượn – trả**. Khi quét để mượn, trang kiểm tra phòng học và tình trạng tài sản; khi quét để trả, trang tìm phiếu chưa trả thuộc đúng phòng trực và chọn sẵn thiết bị.

Để demo: tạo người mượn bằng tài khoản Quản lý; đăng nhập Nhân viên được phân công phòng trực có tài sản; ở trang Tài sản tải QR PNG của một tài sản sẵn sàng, rồi đưa ảnh đó vào ô **Chọn ảnh mã QR** trên trang Mượn. Chọn phòng học tương thích, xác nhận phiếu; qua thẻ Trả, quét lại QR để mở phiếu. Quét camera cần HTTPS hoặc localhost và người dùng cấp quyền camera. Ảnh hỗ trợ PNG, JPEG, WebP tối đa 12 MB; SVG dùng để in, PNG dùng để chọn lại trong bộ quét.

QR do trình duyệt giải mã; máy chủ vẫn đối chiếu mã tài sản, trạng thái, phòng trực, hạn trả, quyền Nhân viên và CSRF trước khi ghi giao dịch. Không xem mã QR như bằng chứng phân quyền.

Thư viện lưu trong dự án để chạy ngoại tuyến:

- `src/vendor/qrcode.js`: qrcode-generator 1.4.4, MIT, nguồn https://github.com/kazuhikoarase/qrcode-generator
- `src/vendor/jsQR.js`: jsQR 1.4.0, Apache-2.0, nguồn https://github.com/cozmo/jsQR ; giấy phép tại `src/vendor/LICENSE-jsQR.txt`.

Kiểm thử nghiệp vụ CSDL bằng `C:\xampp\php\php.exe setup/verify_borrow.php`. Luồng trình duyệt đã được thử bằng Edge cục bộ: QR mới sinh được giải mã đúng `ma_qr`, SVG/PNG tải được, ảnh PNG tìm tài sản và mở đúng phiếu trả, camera bật/tắt được với thiết bị giả lập, Quản trị/Quản lý/Nhân viên truy cập đúng trang. Dữ liệu thử được dọn sau khi chạy.
