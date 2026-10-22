CREATE DATABASE IF NOT EXISTS dacsn_qlts_qr
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE dacsn_qlts_qr;

CREATE TABLE phong_truc (
    id INT UNSIGNED AUTO_INCREMENT,

    ma_phong_truc VARCHAR(20) NOT NULL,

    ten_phong_truc VARCHAR(100) NOT NULL,

    vi_tri VARCHAR(150) NULL,

    dang_hoat_dong TINYINT UNSIGNED NOT NULL DEFAULT 1,

    tao_luc DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP,

    cap_nhat_luc DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT pk_phong_truc
        PRIMARY KEY (id),

    CONSTRAINT uq_phong_truc_ma
        UNIQUE (ma_phong_truc),

    CONSTRAINT ck_phong_truc_hoat_dong
        CHECK (dang_hoat_dong IN (0, 1)),

    CONSTRAINT ck_phong_truc_ma
        CHECK (CHAR_LENGTH(TRIM(ma_phong_truc)) > 0),

    CONSTRAINT ck_phong_truc_ten
        CHECK (CHAR_LENGTH(TRIM(ten_phong_truc)) > 0)

) ENGINE = InnoDB
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
  describe dacsn_qlts_qr.phong_truc;
  USE dacsn_qlts_qr;

CREATE TABLE phong_hoc (
    id INT UNSIGNED AUTO_INCREMENT,

    ma_phong VARCHAR(30) NOT NULL,

    ten_phong VARCHAR(100) NOT NULL,

    toa_nha VARCHAR(50) NULL,

    tang VARCHAR(20) NULL,

    dang_hoat_dong TINYINT UNSIGNED NOT NULL DEFAULT 1,

    tao_luc DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP,

    cap_nhat_luc DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT pk_phong_hoc
        PRIMARY KEY (id),

    CONSTRAINT uq_phong_hoc_ma
        UNIQUE (ma_phong),

    CONSTRAINT ck_phong_hoc_hoat_dong
        CHECK (dang_hoat_dong IN (0, 1)),

    CONSTRAINT ck_phong_hoc_ma
        CHECK (CHAR_LENGTH(TRIM(ma_phong)) > 0),

    CONSTRAINT ck_phong_hoc_ten
        CHECK (CHAR_LENGTH(TRIM(ten_phong)) > 0)

) ENGINE = InnoDB
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
  
DESCRIBE dacsn_qlts_qr.phong_hoc;
USE dacsn_qlts_qr;

CREATE TABLE loai_tai_san (
    id INT UNSIGNED AUTO_INCREMENT,

    ma_loai VARCHAR(30) NOT NULL,

    ten_loai VARCHAR(100) NOT NULL,

    mo_ta TEXT NULL,

    dang_hoat_dong TINYINT UNSIGNED NOT NULL DEFAULT 1,

    tao_luc DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP,

    cap_nhat_luc DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT pk_loai_tai_san
        PRIMARY KEY (id),

    CONSTRAINT uq_loai_tai_san_ma
        UNIQUE (ma_loai),

    CONSTRAINT ck_loai_tai_san_hoat_dong
        CHECK (dang_hoat_dong IN (0, 1)),

    CONSTRAINT ck_loai_tai_san_ma
        CHECK (CHAR_LENGTH(TRIM(ma_loai)) > 0),

    CONSTRAINT ck_loai_tai_san_ten
        CHECK (CHAR_LENGTH(TRIM(ten_loai)) > 0)

) ENGINE = InnoDB
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
  
  describe dacsn_qlts_qr.loai_tai_san;
  
  USE dacsn_qlts_qr;

CREATE TABLE nhan_vien (
    id INT UNSIGNED AUTO_INCREMENT,

    ma_nhan_vien VARCHAR(30) NOT NULL,

    ho_ten VARCHAR(100) NOT NULL,

    so_dien_thoai VARCHAR(20) NULL,

    ten_dang_nhap VARCHAR(50) NOT NULL,

    mat_khau_hash VARCHAR(255) NOT NULL,

    vai_tro ENUM(
        'NHAN_VIEN',
        'QUAN_LY',
        'QUAN_TRI'
    ) NOT NULL DEFAULT 'NHAN_VIEN',

    phong_truc_id INT UNSIGNED NULL,

    dang_hoat_dong TINYINT UNSIGNED NOT NULL DEFAULT 1,

    tao_luc DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP,

    cap_nhat_luc DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT pk_nhan_vien
        PRIMARY KEY (id),

    CONSTRAINT uq_nhan_vien_ma
        UNIQUE (ma_nhan_vien),

    CONSTRAINT uq_nhan_vien_ten_dang_nhap
        UNIQUE (ten_dang_nhap),

    CONSTRAINT fk_nhan_vien_phong_truc
        FOREIGN KEY (phong_truc_id)
        REFERENCES phong_truc(id)
        ON DELETE RESTRICT
        ON UPDATE RESTRICT,

    CONSTRAINT ck_nhan_vien_hoat_dong
        CHECK (dang_hoat_dong IN (0, 1)),

    CONSTRAINT ck_nhan_vien_ma
        CHECK (
            CHAR_LENGTH(TRIM(ma_nhan_vien)) > 0
        ),

    CONSTRAINT ck_nhan_vien_ho_ten
        CHECK (
            CHAR_LENGTH(TRIM(ho_ten)) > 0
        ),

    CONSTRAINT ck_nhan_vien_ten_dang_nhap
        CHECK (
            CHAR_LENGTH(TRIM(ten_dang_nhap)) > 0
        ),

    CONSTRAINT ck_nhan_vien_phong_truc
        CHECK (
            vai_tro <> 'NHAN_VIEN'
            OR phong_truc_id IS NOT NULL
        )

) ENGINE = InnoDB
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
  
  SHOW CREATE TABLE dacsn_qlts_qr.nhan_vien;
  DESCRIBE dacsn_qlts_qr.nhan_vien;
  SHOW TABLES FROM dacsn_qlts_qr;
  
  SHOW CREATE TABLE dacsn_qlts_qr.nhan_vien;
  
  USE dacsn_qlts_qr;

CREATE TABLE nguoi_muon (
    id INT UNSIGNED AUTO_INCREMENT,

    ma_nguoi_muon VARCHAR(30) NOT NULL,

    ma_so_truong VARCHAR(30) NOT NULL,

    ho_ten VARCHAR(100) NOT NULL,

    loai_nguoi_muon ENUM(
        'SINH_VIEN',
        'GIANG_VIEN'
    ) NOT NULL,

    lop_don_vi VARCHAR(100) NULL,

    so_dien_thoai VARCHAR(20) NULL,

    tao_luc DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP,

    cap_nhat_luc DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT pk_nguoi_muon
        PRIMARY KEY (id),

    CONSTRAINT uq_nguoi_muon_ma
        UNIQUE (ma_nguoi_muon),

    CONSTRAINT uq_nguoi_muon_loai_ma_so
        UNIQUE (loai_nguoi_muon, ma_so_truong),

    CONSTRAINT ck_nguoi_muon_ma
        CHECK (
            CHAR_LENGTH(TRIM(ma_nguoi_muon)) > 0
        ),

    CONSTRAINT ck_nguoi_muon_ma_so_truong
        CHECK (
            CHAR_LENGTH(TRIM(ma_so_truong)) > 0
        ),

    CONSTRAINT ck_nguoi_muon_ho_ten
        CHECK (
            CHAR_LENGTH(TRIM(ho_ten)) > 0
        )

) ENGINE = InnoDB
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
  
  USE dacsn_qlts_qr;

CREATE TABLE tai_san (
    id INT UNSIGNED AUTO_INCREMENT,

    ma_tai_san VARCHAR(30) NOT NULL,

    ma_qr VARCHAR(100) NOT NULL,

    ten_tai_san VARCHAR(150) NOT NULL,

    loai_tai_san_id INT UNSIGNED NOT NULL,

    phong_truc_id INT UNSIGNED NOT NULL,

    phong_hoc_phu_hop_id INT UNSIGNED NULL,

    phong_hoc_hien_tai_id INT UNSIGNED NULL,

    tinh_trang ENUM(
        'TOT',
        'HU_HONG',
        'MAT'
    ) NOT NULL DEFAULT 'TOT',

    trang_thai ENUM(
        'SAN_SANG',
        'DANG_MUON',
        'BAO_TRI',
        'NGUNG_SU_DUNG'
    ) NOT NULL DEFAULT 'SAN_SANG',

    ngay_dua_vao_su_dung DATE NULL,

    mo_ta TEXT NULL,

    tao_luc DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP,

    cap_nhat_luc DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT pk_tai_san
        PRIMARY KEY (id),

    CONSTRAINT uq_tai_san_ma
        UNIQUE (ma_tai_san),

    CONSTRAINT uq_tai_san_qr
        UNIQUE (ma_qr),

    CONSTRAINT fk_tai_san_loai
        FOREIGN KEY (loai_tai_san_id)
        REFERENCES loai_tai_san(id)
        ON DELETE RESTRICT
        ON UPDATE RESTRICT,

    CONSTRAINT fk_tai_san_phong_truc
        FOREIGN KEY (phong_truc_id)
        REFERENCES phong_truc(id)
        ON DELETE RESTRICT
        ON UPDATE RESTRICT,

    CONSTRAINT fk_tai_san_phong_phu_hop
        FOREIGN KEY (phong_hoc_phu_hop_id)
        REFERENCES phong_hoc(id)
        ON DELETE RESTRICT
        ON UPDATE RESTRICT,

    CONSTRAINT fk_tai_san_phong_hien_tai
        FOREIGN KEY (phong_hoc_hien_tai_id)
        REFERENCES phong_hoc(id)
        ON DELETE RESTRICT
        ON UPDATE RESTRICT,

    CONSTRAINT ck_tai_san_ma
        CHECK (
            CHAR_LENGTH(TRIM(ma_tai_san)) > 0
        ),

    CONSTRAINT ck_tai_san_qr
        CHECK (
            CHAR_LENGTH(TRIM(ma_qr)) > 0
        ),

    CONSTRAINT ck_tai_san_ten
        CHECK (
            CHAR_LENGTH(TRIM(ten_tai_san)) > 0
        ),

    CONSTRAINT ck_tai_san_san_sang
        CHECK (
            trang_thai <> 'SAN_SANG'
            OR tinh_trang = 'TOT'
        )

) ENGINE = InnoDB
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
  
  USE dacsn_qlts_qr;

CREATE TABLE phieu_muon (
    id INT UNSIGNED AUTO_INCREMENT,

    ma_phieu_muon VARCHAR(30) NOT NULL,

    nguoi_muon_id INT UNSIGNED NOT NULL,

    phong_hoc_id INT UNSIGNED NOT NULL,

    phong_truc_id INT UNSIGNED NOT NULL,

    nhan_vien_lap_id INT UNSIGNED NOT NULL,

    thoi_gian_muon DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP,

    han_tra DATETIME NOT NULL,

    trang_thai ENUM(
        'DANG_MUON',
        'DA_TRA'
    ) NOT NULL DEFAULT 'DANG_MUON',

    ghi_chu TEXT NULL,

    tao_luc DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP,

    cap_nhat_luc DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT pk_phieu_muon
        PRIMARY KEY (id),

    CONSTRAINT uq_phieu_muon_ma
        UNIQUE (ma_phieu_muon),

    CONSTRAINT fk_phieu_muon_nguoi_muon
        FOREIGN KEY (nguoi_muon_id)
        REFERENCES nguoi_muon(id)
        ON DELETE RESTRICT
        ON UPDATE RESTRICT,

    CONSTRAINT fk_phieu_muon_phong_hoc
        FOREIGN KEY (phong_hoc_id)
        REFERENCES phong_hoc(id)
        ON DELETE RESTRICT
        ON UPDATE RESTRICT,

    CONSTRAINT fk_phieu_muon_phong_truc
        FOREIGN KEY (phong_truc_id)
        REFERENCES phong_truc(id)
        ON DELETE RESTRICT
        ON UPDATE RESTRICT,

    CONSTRAINT fk_phieu_muon_nhan_vien_lap
        FOREIGN KEY (nhan_vien_lap_id)
        REFERENCES nhan_vien(id)
        ON DELETE RESTRICT
        ON UPDATE RESTRICT,

    CONSTRAINT ck_phieu_muon_ma
        CHECK (
            CHAR_LENGTH(TRIM(ma_phieu_muon)) > 0
        ),

    CONSTRAINT ck_phieu_muon_han_tra
        CHECK (
            han_tra > thoi_gian_muon
        ),

    INDEX idx_phieu_muon_trang_thai_han_tra (
        trang_thai,
        han_tra
    )

) ENGINE = InnoDB
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
  
  USE dacsn_qlts_qr;

CREATE TABLE chi_tiet_muon (
    id INT UNSIGNED AUTO_INCREMENT,

    phieu_muon_id INT UNSIGNED NOT NULL,

    tai_san_id INT UNSIGNED NOT NULL,

    tinh_trang_khi_muon ENUM(
        'TOT',
        'HU_HONG'
    ) NOT NULL DEFAULT 'TOT',

    thoi_gian_tra DATETIME NULL,

    tinh_trang_khi_tra ENUM(
        'TOT',
        'HU_HONG'
    ) NULL,

    ghi_chu TEXT NULL,

    tai_san_chua_tra_id INT UNSIGNED
        GENERATED ALWAYS AS (
            CASE
                WHEN thoi_gian_tra IS NULL
                    THEN tai_san_id
                ELSE NULL
            END
        ) STORED,

    tao_luc DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP,

    cap_nhat_luc DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT pk_chi_tiet_muon
        PRIMARY KEY (id),

    CONSTRAINT uq_chi_tiet_muon_phieu_tai_san
        UNIQUE (phieu_muon_id, tai_san_id),

    CONSTRAINT uq_chi_tiet_muon_chua_tra
        UNIQUE (tai_san_chua_tra_id),

    CONSTRAINT fk_chi_tiet_muon_phieu
        FOREIGN KEY (phieu_muon_id)
        REFERENCES phieu_muon(id)
        ON DELETE RESTRICT
        ON UPDATE RESTRICT,

    CONSTRAINT fk_chi_tiet_muon_tai_san
        FOREIGN KEY (tai_san_id)
        REFERENCES tai_san(id)
        ON DELETE RESTRICT
        ON UPDATE RESTRICT,

    CONSTRAINT ck_chi_tiet_muon_thong_tin_tra
        CHECK (
            (
                thoi_gian_tra IS NULL
                AND tinh_trang_khi_tra IS NULL
            )
            OR
            (
                thoi_gian_tra IS NOT NULL
                AND tinh_trang_khi_tra IS NOT NULL
            )
        )

) ENGINE = InnoDB
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
  
  USE dacsn_qlts_qr;

CREATE TABLE dieu_chuyen (
    id INT UNSIGNED AUTO_INCREMENT,

    ma_dieu_chuyen VARCHAR(30) NOT NULL,

    phong_hoc_di_id INT UNSIGNED NOT NULL,

    phong_hoc_den_id INT UNSIGNED NOT NULL,

    nhan_vien_lap_id INT UNSIGNED NOT NULL,

    thoi_gian_dieu_chuyen DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP,

    ly_do TEXT NOT NULL,

    ghi_chu TEXT NULL,

    tao_luc DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP,

    cap_nhat_luc DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT pk_dieu_chuyen
        PRIMARY KEY (id),

    CONSTRAINT uq_dieu_chuyen_ma
        UNIQUE (ma_dieu_chuyen),

    CONSTRAINT fk_dieu_chuyen_phong_di
        FOREIGN KEY (phong_hoc_di_id)
        REFERENCES phong_hoc(id)
        ON DELETE RESTRICT
        ON UPDATE RESTRICT,

    CONSTRAINT fk_dieu_chuyen_phong_den
        FOREIGN KEY (phong_hoc_den_id)
        REFERENCES phong_hoc(id)
        ON DELETE RESTRICT
        ON UPDATE RESTRICT,

    CONSTRAINT fk_dieu_chuyen_nhan_vien_lap
        FOREIGN KEY (nhan_vien_lap_id)
        REFERENCES nhan_vien(id)
        ON DELETE RESTRICT
        ON UPDATE RESTRICT,

    CONSTRAINT ck_dieu_chuyen_ma
        CHECK (
            CHAR_LENGTH(TRIM(ma_dieu_chuyen)) > 0
        ),

    CONSTRAINT ck_dieu_chuyen_khac_phong
        CHECK (
            phong_hoc_di_id <> phong_hoc_den_id
        ),

    CONSTRAINT ck_dieu_chuyen_ly_do
        CHECK (
            CHAR_LENGTH(TRIM(ly_do)) > 0
        )

) ENGINE = InnoDB
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
  
  USE dacsn_qlts_qr;

CREATE TABLE chi_tiet_dieu_chuyen (
    id INT UNSIGNED AUTO_INCREMENT,

    dieu_chuyen_id INT UNSIGNED NOT NULL,

    tai_san_id INT UNSIGNED NOT NULL,

    ghi_chu TEXT NULL,

    tao_luc DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP,

    cap_nhat_luc DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT pk_chi_tiet_dieu_chuyen
        PRIMARY KEY (id),

    CONSTRAINT uq_ct_dieu_chuyen_phieu_tai_san
        UNIQUE (dieu_chuyen_id, tai_san_id),

    CONSTRAINT fk_ct_dieu_chuyen_phieu
        FOREIGN KEY (dieu_chuyen_id)
        REFERENCES dieu_chuyen(id)
        ON DELETE RESTRICT
        ON UPDATE RESTRICT,

    CONSTRAINT fk_ct_dieu_chuyen_tai_san
        FOREIGN KEY (tai_san_id)
        REFERENCES tai_san(id)
        ON DELETE RESTRICT
        ON UPDATE RESTRICT

) ENGINE = InnoDB
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
  
  USE dacsn_qlts_qr;

CREATE TABLE dot_kiem_ke (
    id INT UNSIGNED AUTO_INCREMENT,

    ma_dot_kiem_ke VARCHAR(30) NOT NULL,

    ten_dot_kiem_ke VARCHAR(150) NOT NULL,

    phong_truc_id INT UNSIGNED NOT NULL,

    nhan_vien_lap_id INT UNSIGNED NOT NULL,

    loai_kiem_ke ENUM(
        'CUOI_BUOI',
        'DINH_KY',
        'DOT_XUAT'
    ) NOT NULL,

    thoi_gian_bat_dau DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP,

    thoi_gian_ket_thuc DATETIME NULL,

    trang_thai ENUM(
        'DANG_KIEM_KE',
        'HOAN_TAT'
    ) NOT NULL DEFAULT 'DANG_KIEM_KE',

    ghi_chu TEXT NULL,

    tao_luc DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP,

    cap_nhat_luc DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT pk_dot_kiem_ke
        PRIMARY KEY (id),

    CONSTRAINT uq_dot_kiem_ke_ma
        UNIQUE (ma_dot_kiem_ke),

    CONSTRAINT fk_dot_kiem_ke_phong_truc
        FOREIGN KEY (phong_truc_id)
        REFERENCES phong_truc(id)
        ON DELETE RESTRICT
        ON UPDATE RESTRICT,

    CONSTRAINT fk_dot_kiem_ke_nhan_vien_lap
        FOREIGN KEY (nhan_vien_lap_id)
        REFERENCES nhan_vien(id)
        ON DELETE RESTRICT
        ON UPDATE RESTRICT,

    CONSTRAINT ck_dot_kiem_ke_ma
        CHECK (
            CHAR_LENGTH(TRIM(ma_dot_kiem_ke)) > 0
        ),

    CONSTRAINT ck_dot_kiem_ke_ten
        CHECK (
            CHAR_LENGTH(TRIM(ten_dot_kiem_ke)) > 0
        ),

    CONSTRAINT ck_dot_kiem_ke_thoi_gian
        CHECK (
            thoi_gian_ket_thuc IS NULL
            OR thoi_gian_ket_thuc >= thoi_gian_bat_dau
        ),

    CONSTRAINT ck_dot_kiem_ke_hoan_tat
        CHECK (
            (
                trang_thai = 'DANG_KIEM_KE'
                AND thoi_gian_ket_thuc IS NULL
            )
            OR
            (
                trang_thai = 'HOAN_TAT'
                AND thoi_gian_ket_thuc IS NOT NULL
            )
        )

) ENGINE = InnoDB
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
  
  USE dacsn_qlts_qr;

CREATE TABLE chi_tiet_kiem_ke (
    id INT UNSIGNED AUTO_INCREMENT,

    dot_kiem_ke_id INT UNSIGNED NOT NULL,

    tai_san_id INT UNSIGNED NOT NULL,

    tinh_trang_so_sach ENUM(
        'TOT',
        'HU_HONG',
        'MAT'
    ) NOT NULL,

    trang_thai_so_sach ENUM(
        'SAN_SANG',
        'DANG_MUON',
        'BAO_TRI',
        'NGUNG_SU_DUNG'
    ) NOT NULL,

    phong_hoc_so_sach_id INT UNSIGNED NULL,

    ket_qua ENUM(
        'CHUA_KIEM_TRA',
        'TIM_THAY',
        'CHUA_TIM_THAY'
    ) NOT NULL DEFAULT 'CHUA_KIEM_TRA',

    tinh_trang_thuc_te ENUM(
        'TOT',
        'HU_HONG'
    ) NULL,

    phong_hoc_thuc_te_id INT UNSIGNED NULL,

    thoi_gian_kiem_tra DATETIME NULL,

    ghi_chu TEXT NULL,

    tao_luc DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP,

    cap_nhat_luc DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT pk_chi_tiet_kiem_ke
        PRIMARY KEY (id),

    CONSTRAINT uq_ct_kiem_ke_dot_tai_san
        UNIQUE (dot_kiem_ke_id, tai_san_id),

    CONSTRAINT fk_ct_kiem_ke_dot
        FOREIGN KEY (dot_kiem_ke_id)
        REFERENCES dot_kiem_ke(id)
        ON DELETE RESTRICT
        ON UPDATE RESTRICT,

    CONSTRAINT fk_ct_kiem_ke_tai_san
        FOREIGN KEY (tai_san_id)
        REFERENCES tai_san(id)
        ON DELETE RESTRICT
        ON UPDATE RESTRICT,

    CONSTRAINT fk_ct_kiem_ke_phong_so_sach
        FOREIGN KEY (phong_hoc_so_sach_id)
        REFERENCES phong_hoc(id)
        ON DELETE RESTRICT
        ON UPDATE RESTRICT,

    CONSTRAINT fk_ct_kiem_ke_phong_thuc_te
        FOREIGN KEY (phong_hoc_thuc_te_id)
        REFERENCES phong_hoc(id)
        ON DELETE RESTRICT
        ON UPDATE RESTRICT,

    CONSTRAINT ck_ct_kiem_ke_ket_qua
        CHECK (
            (
                ket_qua = 'CHUA_KIEM_TRA'
                AND thoi_gian_kiem_tra IS NULL
                AND tinh_trang_thuc_te IS NULL
                AND phong_hoc_thuc_te_id IS NULL
            )
            OR
            (
                ket_qua = 'TIM_THAY'
                AND thoi_gian_kiem_tra IS NOT NULL
                AND tinh_trang_thuc_te IS NOT NULL
            )
            OR
            (
                ket_qua = 'CHUA_TIM_THAY'
                AND thoi_gian_kiem_tra IS NOT NULL
                AND tinh_trang_thuc_te IS NULL
                AND phong_hoc_thuc_te_id IS NULL
            )
        )

) ENGINE = InnoDB
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
  
  USE dacsn_qlts_qr;

CREATE TABLE su_co_tai_san (
    id INT UNSIGNED AUTO_INCREMENT,

    ma_su_co VARCHAR(30) NOT NULL,

    tai_san_id INT UNSIGNED NOT NULL,

    nhan_vien_lap_id INT UNSIGNED NOT NULL,

    chi_tiet_muon_id INT UNSIGNED NULL,

    chi_tiet_kiem_ke_id INT UNSIGNED NULL,

    loai_su_co ENUM(
        'HU_HONG',
        'MAT',
        'SAI_VI_TRI'
    ) NOT NULL,

    thoi_gian_phat_hien DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP,

    mo_ta TEXT NOT NULL,

    trang_thai ENUM(
        'CHUA_XU_LY',
        'DANG_XU_LY',
        'DA_XU_LY'
    ) NOT NULL DEFAULT 'CHUA_XU_LY',

    thoi_gian_xu_ly DATETIME NULL,

    ket_qua_xu_ly TEXT NULL,

    tao_luc DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP,

    cap_nhat_luc DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT pk_su_co_tai_san
        PRIMARY KEY (id),

    CONSTRAINT uq_su_co_tai_san_ma
        UNIQUE (ma_su_co),

    CONSTRAINT fk_su_co_tai_san
        FOREIGN KEY (tai_san_id)
        REFERENCES tai_san(id)
        ON DELETE RESTRICT
        ON UPDATE RESTRICT,

    CONSTRAINT fk_su_co_nhan_vien_lap
        FOREIGN KEY (nhan_vien_lap_id)
        REFERENCES nhan_vien(id)
        ON DELETE RESTRICT
        ON UPDATE RESTRICT,

    CONSTRAINT fk_su_co_chi_tiet_muon
        FOREIGN KEY (chi_tiet_muon_id)
        REFERENCES chi_tiet_muon(id)
        ON DELETE RESTRICT
        ON UPDATE RESTRICT,

    CONSTRAINT fk_su_co_chi_tiet_kiem_ke
        FOREIGN KEY (chi_tiet_kiem_ke_id)
        REFERENCES chi_tiet_kiem_ke(id)
        ON DELETE RESTRICT
        ON UPDATE RESTRICT,

    CONSTRAINT ck_su_co_ma
        CHECK (
            CHAR_LENGTH(TRIM(ma_su_co)) > 0
        ),

    CONSTRAINT ck_su_co_mo_ta
        CHECK (
            CHAR_LENGTH(TRIM(mo_ta)) > 0
        ),

    CONSTRAINT ck_su_co_thoi_gian
        CHECK (
            thoi_gian_xu_ly IS NULL
            OR thoi_gian_xu_ly >= thoi_gian_phat_hien
        ),

    CONSTRAINT ck_su_co_ket_qua_xu_ly
        CHECK (
            (
                trang_thai IN (
                    'CHUA_XU_LY',
                    'DANG_XU_LY'
                )
                AND thoi_gian_xu_ly IS NULL
                AND ket_qua_xu_ly IS NULL
            )
            OR
            (
                trang_thai = 'DA_XU_LY'
                AND thoi_gian_xu_ly IS NOT NULL
                AND ket_qua_xu_ly IS NOT NULL
                AND CHAR_LENGTH(TRIM(ket_qua_xu_ly)) > 0
            )
        )

) ENGINE = InnoDB
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
  
