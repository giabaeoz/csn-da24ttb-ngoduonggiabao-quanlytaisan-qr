# TÀI LIỆU QUY TẮC NGHIỆP VỤ & THIẾT KẾ HỆ THỐNG
**Dự án:** Quản lý mượn – trả và kiểm kê tài sản bằng mã QR  
**Mục đích:** Tài liệu nguồn sự thật (Single Source of Truth) về quy trình nghiệp vụ, ràng buộc dữ liệu, sơ đồ trạng thái và ma trận phân quyền phục vụ bảo vệ đồ án và phát triển mã nguồn.

---

## 1. KHÁI NIỆM VÀ ĐỐI TƯỢNG TRONG HỆ THỐNG

1. **Phòng trực thiết bị (`phong_truc`):**
   - Nơi lưu trữ, bảo quản và cấp phát tài sản/thiết bị (ví dụ: Phòng trực D6, B2, Victory).
   - Mỗi tài sản thuộc sở hữu và quản lý của đúng một phòng trực (`tai_san.phong_truc_id`).
   - Mỗi Nhân viên trực được phân công làm việc tại một phòng trực cụ thể (`nhan_vien.phong_truc_id`).
   
2. **Phòng học / Giảng đường (`phong_hoc`):**
   - Nơi tài sản được mang đến để phục vụ giảng dạy, học tập (ví dụ: B31.101, B71.101).
   - Mỗi phòng học chứa cấu hình các thiết bị cố định được trang bị sẵn trong phòng (`thiet_bi_phong` dạng JSON: máy lạnh, máy chiếu treo trần, dàn âm thanh... kèm thông tin hãng sản xuất và model).
   - Tài sản khi cho mượn hoặc điều chuyển sẽ được ghi nhận phòng học hiện tại (`tai_san.phong_hoc_hien_tai_id`). Khi thiết bị nằm tại phòng trực, giá trị này là `NULL`.

3. **Người mượn (`nguoi_muon`):**
   - Sinh viên hoặc Giảng viên có nhu cầu mượn thiết bị phục vụ học tập/giảng dạy.
   - Không cần tài khoản đăng nhập hệ thống. Mọi giao dịch do Nhân viên trực phòng thực hiện.
   - Định danh qua `ma_so_truong` (MSSV/MSGV), `so_dien_thoai`, `ho_ten`, `lop_don_vi`.

4. **Tài sản / Thiết bị (`tai_san`):**
   - Thiết bị độc lập cần quản lý: remote máy chiếu, remote máy lạnh, micro, máy chiếu di động, loa trợ giảng, cáp HDMI...
   - Mỗi tài sản có:
     - `ma_tai_san` (định danh hiển thị) và `ma_qr` duy nhất (chuỗi mã vạch 2D dán trên tem thiết bị).
     - `hang_san_xuat` (Daikin, Panasonic, Sony, Epson, Shure, Soundking...) và `model` (mã kiểu máy kỹ thuật).
     - `phong_hoc_phu_hop_id`: Mã phòng học gợi ý dán trên tem thiết bị, giúp nhân viên tìm kiếm thiết bị nhanh chóng trên kệ tủ phân loại, không mang tính khóa cứng.
     - `pham_vi_tuong_thich`: `THEO_THIET_BI` (khớp hãng/model thiết bị phòng), `THEO_PHONG` (khớp danh sách phòng), hoặc `LINH_HOAT` (dùng chung cho mọi phòng).

---

## 2. VÒNG ĐỜI VÀ TRẠNG THÁI TÀI SẢN

### 2.1. Các trường trạng thái trong CSDL
Mỗi tài sản có hai thuộc tính phản ánh trạng thái hoạt động:
- **`tinh_trang`** (Chất lượng vật lý):
  - `TOT`: Thiết bị hoạt động bình thường, không hư hỏng.
  - `HU_HONG`: Thiết bị có khiếm khuyết, trục trặc hoặc hỏng hóc cần sửa chữa.
  - `MAT`: Thiết bị bị thất thoát, không tìm thấy qua kiểm kê hoặc xác nhận mất.
- **`trang_thai`** (Tình trạng vận hành trong hệ thống):
  - `SAN_SANG`: Thiết bị đang ở phòng trực, tình trạng tốt, sẵn sàng cho mượn.
  - `DANG_MUON`: Thiết bị đang được người mượn sử dụng tại một phòng học.
  - `BAO_TRI`: Thiết bị đang chờ sửa chữa hoặc đang bảo trì sau khi phát hiện hư hỏng.
  - `NGUNG_SU_DUNG`: Thiết bị thanh lý, hỏng không thể sửa, mất hoặc dừng lưu hành.

### 2.2. Ràng buộc toàn vẹn CSDL
1. **Ràng buộc sẵn sàng:** `CHECK (trang_thai <> 'SAN_SANG' OR tinh_trang = 'TOT')`. Tài sản chỉ được ở trạng thái `SAN_SANG` khi và chỉ khi `tinh_trang = 'TOT'`.
2. **Ràng buộc vị trí:**
   - Khi ở phòng trực (`trang_thai = 'SAN_SANG'`): `phong_hoc_hien_tai_id IS NULL`.
   - Khi đang mượn (`trang_thai = 'DANG_MUON'`): `phong_hoc_hien_tai_id` lưu ID phòng học sử dụng.
   - Khi điều chuyển giữa các phòng học: `phong_hoc_hien_tai_id` cập nhật phòng học đích.

### 2.3. Sơ đồ chuyển đổi trạng thái tài sản

```
                   ┌─────────────── lập phiếu mượn ──────────────┐
                   │                                             ▼
            ┌──────────────┐                            ┌─────────────────┐
            │   SAN_SANG   │                            │    DANG_MUON    │
            │(tình trạng:  │◀─── nhận trả (TOT) ────────│(tình trạng: TOT)│
            │     TOT)     │                            └────────┬────────┘
            └──────┬───────┘                                     │
                   │                                nhận trả (HU_HONG)
         phát hiện │ hoặc kiểm kê phát hiện                      │
          hư hỏng  │                                             ▼
                   │                                    ┌─────────────────┐
                   └───────────────────────────────────▶│     BAO_TRI     │
                                                        │ (tình trạng:    │
                                                        │   HU_HONG)      │
                   ┌───────────────────────────────────▶└────────┬────────┘
                   │                                             │
                   │ xử lý sự cố hoàn tất (sửa xong)             │
                   └─────────────────────────────────────────────┘
                                        │
                         thất lạc / hư hỏng nặng / thanh lý
                                        ▼
                                ┌─────────────────┐
                                │  NGUNG_SU_DUNG  │
                                └─────────────────┘
```

---

## 3. QUY TRÌNH NGHIỆP VỤ CHI TIẾT

### 3.1. Nghiệp vụ Mượn tài sản (`muon_tao_phieu`)

#### Điều kiện hợp lệ (kiểm tra trong Transaction):
1. **Phòng trực không bị khóa:** Phòng trực của nhân viên hiện không có đợt kiểm kê nào đang mở (`dot_kiem_ke.trang_thai = 'DANG_KIEM_KE'`).
2. **Người mượn hợp lệ:** Người mượn tồn tại trong CSDL. Cho phép nhân viên nhập nhanh: nếu chưa có thì tự động tạo mới (kiểm tra không trùng `ma_so_truong` hoặc `so_dien_thoai`).
3. **Phòng học hợp lệ:** Phòng học đích tồn tại và đang hoạt động (`dang_hoat_dong = 1`).
4. **Danh sách thiết bị:**
   - Số lượng từ 1 đến 30 tài sản trong một phiếu.
   - Không được chứa ID trùng lặp trong cùng một yêu cầu.
   - Khoá các dòng tài sản bằng `SELECT ... FOR UPDATE` theo thứ tự ID tăng dần (`ORDER BY id ASC`) nhằm triệt tiêu hoàn toàn nguy cơ deadlock.
5. **Điều kiện của từng tài sản:**
   - Thuộc phòng trực của nhân viên đang thao tác (`tai_san.phong_truc_id == nhan_vien.phong_truc_id`).
   - Đang sẵn sàng: `trang_thai = 'SAN_SANG'`, `tinh_trang = 'TOT'`, `phong_hoc_hien_tai_id IS NULL`.
   - Không có giao dịch mượn dở dang: Không có bản ghi nào trong `chi_tiet_muon` mà `thoi_gian_tra IS NULL`.
   - **Độ tương thích với phòng học đích:**
     - Nếu `pham_vi_tuong_thich = 'LINH_HOAT'`: Luôn hợp lệ cho mọi phòng học (máy chiếu di động, loa kéo, cáp HDMI, ổ cắm...).
     - Nếu `pham_vi_tuong_thich = 'THEO_THIET_BI'`: Hệ thống duyệt danh sách thiết bị cố định (`thiet_bi_phong`) của phòng học đích:
       - Phòng học phải có ít nhất một thiết bị có cùng `hang_san_xuat` (so khớp không phân biệt hoa thường).
       - Nếu tài sản có khai báo `model` cụ thể, thiết bị trong phòng cũng phải có model tương thích.
       - *Ví dụ:* Remote Daikin model FTKB25W sẽ dùng được cho mọi phòng có máy lạnh Daikin model FTKB25W; Micro Soundking SK-12 dùng được cho mọi phòng có dàn âm thanh Soundking SK-12.
     - Nếu `pham_vi_tuong_thich = 'THEO_PHONG'`: Mã phòng học đích phải nằm trong mảng JSON `tai_san.phong_tuong_thich`.
     - **Ý nghĩa nhãn dán phòng (`phong_hoc_phu_hop_id`):** Là thông tin hỗ trợ sắp xếp vị trí trên giá kệ phòng trực. Khi nhân viên chọn phòng học, hệ thống ưu tiên đề xuất các thiết bị có nhãn phòng này để nhân viên lấy nhanh, nhưng không khóa cứng nếu thiết bị thỏa mãn độ tương thích Hãng/Model với phòng học đích.
6. **Hạn trả hợp lệ:** Chuỗi thời gian tương lai so với thời điểm hiện tại của máy chủ MySQL (`han_tra > NOW()`).

#### Các bước thực thi:
1. Sinh mã phiếu mượn tự động: `PM-YYYYMMDD-XXXXXXXX` (8 ký tự hex ngẫu nhiên in hoa).
2. Tạo bản ghi trong `phieu_muon` với trạng thái `DANG_MUON`.
3. Tạo các dòng trong `chi_tiet_muon` với `tinh_trang_khi_muon = 'TOT'`, `thoi_gian_tra = NULL`.
4. Cập nhật từng tài sản: `trang_thai = 'DANG_MUON'`, `phong_hoc_hien_tai_id = phong_hoc_id`.
5. Cam kết Transaction (Commit).

---

### 3.2. Nghiệp vụ Trả tài sản (`muon_nhan_tra`)

#### Quy tắc nghiệp vụ:
1. **Phạm vi thẩm quyền:** Nhân viên chỉ được nhận trả các phiếu mượn thuộc phòng trực được phân công.
2. **Hỗ trợ trả từng phần (Partial Return):** Một phiếu mượn có thể trả làm nhiều đợt. Mỗi đợt chọn 1 hoặc nhiều thiết bị đang chờ trả.
3. **Thời điểm trả chung:** Mọi thiết bị trong cùng một thao tác nhận trả được gán cùng một mốc `thoi_gian_tra = NOW()` để dễ nhóm chứng từ bàn giao trả.
4. **Ghi nhận tình trạng khi trả:**
   - Tình trạng: `TOT` hoặc `HU_HONG`.
   - Nếu trả `TOT`:
     - Tài sản cập nhật: `tinh_trang = 'TOT'`, `trang_thai = 'SAN_SANG'`, `phong_hoc_hien_tai_id = NULL`.
   - Nếu trả `HU_HONG`:
     - **Bắt buộc nhập ghi chú** mô tả chi tiết hư hỏng.
     - Tài sản cập nhật: `tinh_trang = 'HU_HONG'`, `trang_thai = 'BAO_TRI'`, `phong_hoc_hien_tai_id = NULL`.
     - **Tự động sinh sự cố tài sản:** Thêm bản ghi vào `su_co_tai_san` với mã `SC-YYYYMMDD-XXXXXXXX`, liên kết với `chi_tiet_muon_id`, loại sự cố `HU_HONG`, mô tả từ ghi chú.
5. **Ghi nhận nhân viên tiếp nhận:** Lưu `nhan_vien_nhan_id = staffId` trên từng dòng `chi_tiet_muon`.
6. **Tự động đóng phiếu:** Đếm số thiết bị còn chưa trả (`thoi_gian_tra IS NULL`). Nếu bằng 0, cập nhật `phieu_muon.trang_thai = 'DA_TRA'`.

---

### 3.3. Nghiệp vụ Điều chuyển tài sản (`dieu_chuyen_tao`)

Nghiệp vụ chuyển dịch thiết bị giữa các phòng học khi có nhu cầu bố trí lại phòng thực hành hoặc chuyển lớp học.

#### Điều kiện hợp lệ:
1. **Phòng học đi và đến:** `phong_hoc_di_id != phong_hoc_den_id`, cả hai phòng đều phải đang hoạt động (`dang_hoat_dong = 1`).
2. **Xác nhận tương thích:** Người dùng phải tick xác nhận đã kiểm tra độ tương thích (`xac_nhan_tuong_thich = '1'`).
3. **Lý do điều chuyển:** Bắt buộc nhập lý do (tối đa 2000 ký tự).
4. **Phòng trực không kiểm kê:** Không có đợt kiểm kê đang diễn ra tại phòng trực.
5. **Điều kiện tài sản được điều chuyển:**
   - Thuộc phòng trực của nhân viên.
   - Vị trí hiện tại phải đúng là phòng học nguồn: `tai_san.phong_hoc_hien_tai_id == phong_hoc_di_id`.
   - Tình trạng và trạng thái: `trang_thai = 'SAN_SANG'` và `tinh_trang = 'TOT'`.
   - Không có phiếu mượn chưa trả.
   - Tương thích với phòng học đích (`transfer_asset_compatible`).
6. **Thực thi:**
   - Sinh mã điều chuyển `DC-YYYYMMDD-XXXXXXXX`.
   - Thêm bản ghi `dieu_chuyen` và `chi_tiet_dieu_chuyen`.
   - Cập nhật `tai_san.phong_hoc_hien_tai_id = phong_hoc_den_id`. Phòng trực quản lý (`phong_truc_id`) giữ nguyên.

---

### 3.4. Nghiệp vụ Kiểm kê tài sản (`kiem_ke`)

Quy trình đối soát định kỳ hoặc đột xuất để đảm bảo khớp giữa số liệu sổ sách và thực tế tại phòng trực và phòng học.

#### 1. Mở đợt kiểm kê (`kiem_ke_mo_dot`):
- Loại kiểm kê: `CUOI_BUOI` (cuối buổi trực), `DINH_KY` (cuối kỳ/tháng), `DOT_XUAT`.
- Mỗi phòng trực chỉ được phép có **tối đa một đợt đang mở** (`DANG_KIEM_KE`).
- Khi mở đợt, hệ thống **chụp lại toàn bộ sổ sách** cho tất cả tài sản không ngừng sử dụng của phòng trực:
  - Sinh dòng `chi_tiet_kiem_ke` lưu `tinh_trang_so_sach`, `trang_thai_so_sach`, `phong_hoc_so_sach_id`.
  - Nếu tài sản đang có phiếu mượn chưa trả lúc mở đợt, `trang_thai_so_sach` được ghi là `DANG_MUON`.

#### 2. Quét ghi nhận kiểm kê (`kiem_ke_ghi_nhan`):
- Nhân viên dùng camera quét QR hoặc nhập mã thủ công từng tài sản.
- Tài sản đang mượn lúc mở đợt (`trang_thai_so_sach = 'DANG_MUON'`) không kiểm tra trực tiếp trong đợt này.
- Ghi kết quả:
  - `TIM_THAY`: Nhập tình trạng thực tế (`TOT` hoặc `HU_HONG`), vị trí thực tế (nếu khác phòng sổ sách). Nếu `HU_HONG` bắt buộc nhập ghi chú.
  - `CHUA_TIM_THAY`: Bắt buộc nhập ghi chú giải trình.

#### 3. Chốt đợt kiểm kê (`kiem_ke_chot_dot`):
- Bắt buộc tất cả tài sản cần kiểm kê đều đã có kết quả (`TIM_THAY` hoặc `CHUA_TIM_THAY`). Không được còn bản ghi `CHUA_KIEM_TRA`.
- Kiểm tra tính toàn vẹn: tài sản không bị sửa đổi ngoài luồng trong suốt thời gian đợt mở.
- Tự động sinh sự cố và đồng bộ trạng thái:
  - `CHUA_TIM_THAY`: Sinh sự cố loại `MAT`, cập nhật tài sản sang `NGUNG_SU_DUNG`.
  - `TIM_THAY` nhưng thực tế `HU_HONG` (sổ sách `TOT`): Sinh sự cố loại `HU_HONG`, cập nhật tài sản sang `tinh_trang = 'HU_HONG'`, `trang_thai = 'BAO_TRI'`.
  - `TIM_THAY` nhưng sai phòng học: Sinh sự cố loại `SAI_VI_TRI`, cập nhật tài sản sang `NGUNG_SU_DUNG` để chờ Quản lý rà soát.
- Cập nhật đợt kiểm kê sang `HOAN_TAT`, lưu `thoi_gian_ket_thuc = NOW()`.

#### 4. Hủy đợt kiểm kê (`kiem_ke_huy_dot`):
- Cho phép hủy đợt đang mở khi có sai sót. Bắt buộc nhập lý do hủy.
- Cập nhật `trang_thai = 'DA_HUY'`, ghi nhận `nhan_vien_huy_id`, `ly_do_huy`, `thoi_gian_ket_thuc = NOW()`.

#### 5. Bốn nhóm kết quả đối chiếu hiển thị trên giao diện:
1. **Đã kiểm kê khớp:** Tìm thấy, tình trạng tốt, đúng vị trí.
2. **Chưa tìm thấy (Mất/Thất lạc):** Cần điều tra, lập sự cố mất tài sản.
3. **Sai vị trí / Hư hỏng:** Tìm thấy nhưng khác vị trí sổ sách hoặc bị hư hỏng.
4. **Đang mượn / điều chuyển:** Thiết bị đang phục vụ giảng dạy khi mở đợt kiểm kê.

---

### 3.5. Nghiệp vụ Xử lý sự cố tài sản (`su_co_xu_ly`)

Sự cố tài sản sinh ra từ hai nguồn:
1. Trả thiết bị mượn bị hỏng (`loai_su_co = 'HU_HONG'`).
2. Chốt đợt kiểm kê phát hiện mất (`MAT`), hỏng (`HU_HONG`), hoặc sai vị trí (`SAI_VI_TRI`).

**Quy trình xử lý (dành cho Quản lý):**
- Trạng thái sự cố: `CHUA_XU_LY` ➔ `DANG_XU_LY` ➔ `DA_XU_LY`.
- Khi khắc phục xong sự cố hư hỏng:
  - Quản lý nhập kết quả xử lý (ví dụ: đã thay linh kiện, đã sửa chữa xong).
  - Hệ thống cập nhật tài sản tương ứng: `tinh_trang = 'TOT'`, `trang_thai = 'SAN_SANG'`, đưa tài sản trở lại phục vụ.
- Nếu không thể khắc phục: Chuyển tài sản sang `NGUNG_SU_DUNG` (thanh lý).

---

## 4. QUY TẮC MÃ QR VÀ TEM IN

1. **Cấu trúc nội dung mã QR:**
   - **QR Tài sản:** Là chuỗi ký tự duy nhất lưu trong cột `tai_san.ma_qr`. Gợi ý: tiền tố `QR-` kết hợp 12 ký tự hex ngẫu nhiên in hoa (ví dụ: `QR-A1F8C90E43D2`). **Mã QR là chuỗi định danh, tuyệt đối không chứa URL hay thông tin cá nhân.**
   - **QR Phòng học:** Chuỗi định danh cố định theo mã phòng, dạng `TVU:PHONG:<ma_phong>` (ví dụ: `TVU:PHONG:B31.103`).
2. **Mã hiển thị (`ma_tai_san`):**
   - Đề xuất tự động theo quy tắc: `<ma_loai>-<4 chữ số thứ tự>` (ví dụ: `MC-0001`, `RM-0012`). Cho phép người dùng chỉnh sửa trước khi lưu, đảm bảo tính duy nhất.
3. **Tạo tem QR in:**
   - Dùng thư viện `qrcode.js` cục bộ phía client để dựng canvas/SVG.
   - Hỗ trợ in đơn lẻ hoặc in hàng loạt nhiều tem trên một trang giấy A4 (gồm Logo trường, tên tài sản, mã tài sản và hình QR).
4. **Quét QR qua Camera:**
   - Dùng thư viện `jsQR` truy cập camera qua API `navigator.mediaDevices.getUserMedia`.
   - **Luôn có ô nhập mã thủ công** song song với camera phòng trường hợp camera mờ, hỏng hoặc thiết bị không hỗ trợ.
5. **Endpoint API tra cứu QR (`api/tra-cuu-qr.php`):**
   - Tiếp nhận chuỗi quét hoặc nhập tay.
   - Nhận diện loại mã: phòng học hay tài sản.
   - Trả về JSON bao gồm: thông tin chi tiết, trạng thái hiện tại, và cờ kiểm tra các hành động hợp lệ (`cho_muon_duoc`, `tra_duoc`, `dieu_chuyen_duoc`, `kiem_ke_duoc`) kèm thông điệp cảnh báo sớm cho người dùng.

---

## 5. NGUYÊN TẮC TOÀN VẸN VÀ XOÁ DỮ LIỆU

1. **Dữ liệu giao dịch bất biến (Immutable Transactions):**
   - Bảng giao dịch: `phieu_muon`, `chi_tiet_muon`, `dieu_chuyen`, `chi_tiet_dieu_chuyen`, `dot_kiem_ke`, `chi_tiet_kiem_ke`, `su_co_tai_san`.
   - **Chỉ thêm mới (Insert), không bao giờ sửa (Update) làm thay đổi bản chất lịch sử, tuyệt đối không xoá (Delete).**
2. **Dữ liệu danh mục và tài sản:**
   - Mọi khoá ngoại đều đặt `ON DELETE RESTRICT ON UPDATE RESTRICT`.
   - Khi tài sản, phòng học, phòng trực hoặc loại tài sản đã phát sinh bất kỳ giao dịch nào trong lịch sử, CSDL sẽ từ chối thao tác xoá vật lý.
   - Để ngừng sử dụng: cập nhật `dang_hoat_dong = 0` (đối với danh mục) hoặc `trang_thai = 'NGUNG_SU_DUNG'` (đối với tài sản).
   - Giao diện người dùng bắt ngoại lệ `mysqli_sql_exception` (mã lỗi MySQL 1451) và hiển thị thông báo tiếng Việt rõ ràng, thân thiện.

---

## 6. MA TRẬN PHÂN QUYỀN HỆ THỐNG

| Quyền hạn (`QUYEN`) | Ý nghĩa nghiệp vụ | Quản trị (`QUAN_TRI`) | Quản lý (`QUAN_LY`) | Nhân viên (`NHAN_VIEN`) |
|---|---|:---:|:---:|:---:|
| `tong_quan.xem` | Xem trang tổng quan (Dashboard) | ✔ | ✔ | ✔ |
| `tai_san.xem` | Xem danh sách và chi tiết tài sản | ✔ (toàn trường) | ✔ (toàn trường) | ✔ (phòng trực của mình) |
| `tai_san.quan_ly` | Thêm, sửa, sinh QR, ngừng sử dụng tài sản | ✔ | ✔ | ✘ |
| `danh_muc.loai_tai_san` | Quản lý loại tài sản | ✘ | ✔ | ✘ |
| `danh_muc.phong` | Quản lý phòng trực, phòng học | ✔ | ✔ | ✘ |
| `nguoi_muon.xem` | Tra cứu hồ sơ người mượn | ✘ | ✔ | ✔ |
| `nguoi_muon.quan_ly` | Thêm, sửa, xoá hồ sơ người mượn | ✘ | ✔ | ✘ |
| `nguoi_muon.cap_nhat_han_che` | Cập nhật thông tin liên hệ người mượn (đã từng mượn tại phòng trực) | ✘ | ✘ | ✔ |
| `tai_khoan.quan_ly` | Tạo và phân quyền tài khoản cán bộ | ✔ | ✘ | ✘ |
| `muon_tra.thuc_hien` | Lập phiếu mượn và tiếp nhận trả thiết bị | ✘ | ✘ | ✔ |
| `muon_tra.xem` | Tra cứu lịch sử mượn – trả | ✔ (toàn trường) | ✔ (toàn trường) | ✔ (phòng trực của mình) |
| `dieu_chuyen.thuc_hien` | Lập phiếu điều chuyển thiết bị | ✘ | ✘ | ✔ |
| `dieu_chuyen.xem` | Tra cứu lịch sử điều chuyển | ✔ (toàn trường) | ✔ (toàn trường) | ✔ (phòng trực của mình) |
| `kiem_ke.thuc_hien` | Mở đợt, quét kiểm kê, chốt/hủy đợt kiểm kê | ✘ | ✘ | ✔ |
| `kiem_ke.xem` | Xem lịch sử và kết quả đối soát kiểm kê | ✔ (toàn trường) | ✔ (toàn trường) | ✔ (phòng trực của mình) |
| `su_co.xem` | Xem danh sách sự cố tài sản | ✔ (toàn trường) | ✔ (toàn trường) | ✔ (phòng trực của mình) |
| `su_co.xu_ly` | Phê duyệt xử lý sự cố, phục hồi thiết bị | ✘ | ✔ | ✘ |
| `thong_ke.xem` | Xem báo cáo biểu đồ Chart.js và số liệu | ✔ | ✔ | ✘ |

---

## 7. CÁC TRƯỜNG HỢP BIÊN & GIẢI PHÁP KỸ THUẬT

1. **Xung đột mượn đồng thời (Race Condition):**
   - *Nguy cơ:* Hai nhân viên quét cùng một tài sản và cùng bấm "Xác nhận mượn" tại cùng một phần nghìn giây.
   - *Giải pháp:*
     1. Khóa bi quan: `SELECT id FROM tai_san WHERE id = ? FOR UPDATE` theo thứ tự ID tăng dần.
     2. Ràng buộc cấp CSDL: Cột sinh `chi_tiet_muon.tai_san_chua_tra_id` kết hợp ràng buộc `UNIQUE`. CSDL MySQL chủ động từ chối bản ghi thứ hai ngay cả khi logic ứng dụng bị vượt qua.
2. **Phong tỏa tài sản khi đang kiểm kê:**
   - *Nguy cơ:* Nhân viên vừa kiểm kê vừa cho mượn hoặc điều chuyển làm sai lệch số liệu đối chiếu.
   - *Giải pháp:* Trước khi tạo phiếu mượn hoặc điều chuyển, service kiểm tra truy vấn `SELECT id FROM dot_kiem_ke WHERE phong_truc_id = ? AND trang_thai = 'DANG_KIEM_KE'`. Nếu có đợt kiểm kê đang mở, chặn giao dịch và báo lỗi rõ ràng.
3. **Giả mạo quyền phòng trực qua Session:**
   - *Nguy cơ:* Session client lưu phòng trực cũ nhưng tài khoản đã bị điều động sang phòng trực khác.
   - *Giải pháp:* Không đọc `phong_truc_id` từ session khi thực thi các nghiệp vụ ghi. Mọi service đọc lại `phong_truc_id` và `dang_hoat_dong` trực tiếp từ bảng `nhan_vien` bên trong Transaction.
4. **Đồng bộ thời gian:**
   - Luôn sử dụng hàm `NOW()` của MySQL hoặc lấy thời gian hiện tại từ DB để tính toán hạn trả và thời gian mượn/trả, tránh chênh lệch giữa giờ PHP và giờ MySQL.

