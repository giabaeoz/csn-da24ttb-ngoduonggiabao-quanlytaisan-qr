-- Bổ sung nhân viên tiếp nhận trả cho từng dòng chi tiết mượn.
-- Không xóa/sửa dữ liệu hiện có: cột mới cho phép NULL (các lần trả cũ giữ NULL).
USE dacsn_qlts_qr;

ALTER TABLE chi_tiet_muon
    ADD COLUMN nhan_vien_nhan_id INT UNSIGNED NULL AFTER tinh_trang_khi_tra;

ALTER TABLE chi_tiet_muon
    ADD CONSTRAINT fk_chi_tiet_muon_nhan_vien_nhan
        FOREIGN KEY (nhan_vien_nhan_id) REFERENCES nhan_vien(id)
        ON DELETE RESTRICT ON UPDATE RESTRICT;

-- Tra cứu chứng từ trả theo lần trả (phiếu + thời điểm trả).
ALTER TABLE chi_tiet_muon
    ADD INDEX idx_chi_tiet_muon_lan_tra (phieu_muon_id, thoi_gian_tra);

