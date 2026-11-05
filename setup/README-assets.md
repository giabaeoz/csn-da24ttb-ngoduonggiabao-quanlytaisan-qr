# Theo dõi tài sản và dữ liệu mẫu Word

Chạy tại thư mục gốc dự án bằng PHP 8.2 và MySQL 8.0.46 đã cấu hình.

```powershell
C:\xampp\php\php.exe setup/migrate_asset_compatibility.php
C:\xampp\php\php.exe setup/seed_assets_word.php
```

Sau khi cài mới từ database_dacsn_qlts_qr.sql, chạy hai lệnh trên. Migration có thể chạy lại, chỉ thêm hai cột và ràng buộc. Seed chạy trong transaction và bỏ qua mã tài sản đã tồn tại, không cập nhật tài sản khi chạy lại.

## Ánh xạ tài liệu

Nguồn là BẢNG DỮ LIỆU MẪU THIẾT BỊ TẠI ĐẠI HỌC TRÀ VINH.docx trong thư mục Khảo sát do người dùng cung cấp ngày 04/11/2026. Bảng có 8 dòng, được chép trong assets_word_samples.php. Không dùng AI sinh mã, tên hoặc phòng tương thích.

- Mã thiết bị → ma_tai_san.
- Tên thiết bị → ten_tai_san.
- Phạm vi tương thích → pham_vi_tuong_thich (THEO_PHONG / LINH_HOAT).
- Phòng tương thích → phong_tuong_thich, JSON chứa danh sách mã phòng.
- LINH_HOAT có danh sách rỗng. THEO_PHONG bắt buộc có ít nhất một mã phòng, không trùng.
- Hai cột NULL được dành cho hồ sơ cũ, khi đó trang hiển thị phòng_hoc_phu_hop_id nếu có.
- Nếu có đúng một phòng tương thích, seed điền thêm FK phong_hoc_phu_hop_id. Nếu nhiều phòng, FK này để NULL; cần đọc phạm vi và danh sách JSON, không diễn giải NULL thành linh hoạt.

Phòng trực được người dùng xác nhận là Phòng trực thiết bị khu 1 (khu B); mã kỹ thuật tạo mới là PT_K1B. Ba nhóm RML, RTV, MIC được suy ra từ tên thiết bị. Tên phòng hiển thị được tạo bằng tiền tố “Phòng” cộng mã nguồn. Giữ nguyên mã B21.2.7 trong Word.

Tình trạng Tốt và trạng thái Sẵn sàng là giá trị mặc định cho demo, không phải kết quả khảo sát. Phòng hiện tại và ngày sử dụng để NULL. QR được sinh ổn định từ chuỗi TVU: cộng mã tài sản.

JSON giữ nhiều mã phòng theo yêu cầu chỉ bổ sung thuộc tính, không tạo bảng quan hệ mới. JSON không có FK tới phong_hoc. Seed xác minh/tạo danh mục phòng; khi phát triển sửa mã phòng hoặc nghiệp vụ mượn, phải kiểm tra danh sách tương thích này cùng lúc. Trạng thái giao dịch đang mượn trên màn hình được xác định từ chi tiết chưa trả trước khi dùng trạng thái hồ sơ.

## Kiểm tra chức năng

Kiểm thử tự động CSDL (dữ liệu thử được hoàn tác sau khi chạy):

```powershell
C:\xampp\php\php.exe setup/verify_assets.php
```

Bộ kiểm thử đối chiếu tám dòng Word, phòng trực, lọc tương thích, ký tự SQL/LIKE, ràng buộc JSON, bốn nhóm lịch sử, mượn quá hạn, trạng thái sau trả, dữ liệu cũ và phân trang.

Đăng nhập QUAN_TRI, chọn Tài sản.

- Có 8 tài sản mẫu, 5 theo phòng và 3 linh hoạt nếu CSDL trước đó chưa có tài sản.
- RML-002 có B21.301, B21.302, B21.307.
- RTV-002 có B21.2.7, B21.206.
- Lọc “Dùng được tại phòng” B21.301 cho ra RML-002 cùng ba thiết bị linh hoạt.
- Xem QR, chọn tài sản để in, mở chi tiết và bốn nhóm lịch sử.
- NHAN_VIEN / QUAN_LY truy cập trang này bị từ chối. QUAN_TRI có thể thêm, sửa và xóa tài sản chưa có lịch sử; tài sản đã có lịch sử chỉ được ngừng sử dụng.

QR được tạo tại trình duyệt bằng qrcode-generator 1.4.4 (MIT), bản lưu tại `src/vendor`. Giao diện tài sản dùng chung nằm tại `src/shared/views` và `src/shared/assets.css`, `assets.js`. Nguồn thư viện: https://github.com/kazuhikoarase/qrcode-generator.
