-- Chon CSDL trong phpMyAdmin truoc khi import, hoac chay support/scripts/khoitao_csdl.php.
-- Script chi tao bang con thieu, khong xoa bang hay du lieu dang co.

CREATE TABLE IF NOT EXISTS danhmuc (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ten_danhmuc VARCHAR(150) NOT NULL,
  mo_ta TEXT NULL,
  ngung_ban TINYINT NOT NULL DEFAULT 0,
  CONSTRAINT uq_dm_ten_flow UNIQUE (ten_danhmuc)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sanpham (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ma_sp VARCHAR(50) NOT NULL UNIQUE,
  ten_sp VARCHAR(255) NOT NULL,
  id_danhmuc INT NOT NULL,
  gia DECIMAL(12,2) NOT NULL,
  so_luong_goc INT NOT NULL,
  so_luong_ton INT NOT NULL,
  so_luong_loi INT NOT NULL DEFAULT 0,
  ngung_ban TINYINT NOT NULL DEFAULT 0,
  hinh VARCHAR(255) NULL,
  mo_ta TEXT NULL,
  thuong_hieu VARCHAR(150) NULL,
  thong_so TEXT NULL,
  bao_hanh VARCHAR(255) NULL,
  anh_phu TEXT NULL,
  ngay_tao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  ngay_nhap DATE NULL,
  han_su_dung DATE NULL,
  CONSTRAINT ck_sp_flow CHECK (gia>=0 AND so_luong_goc>=0 AND so_luong_ton>=0 AND so_luong_ton<=so_luong_goc AND so_luong_loi>=0),
  CONSTRAINT fk_sp_dm FOREIGN KEY (id_danhmuc) REFERENCES danhmuc(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS khachhang (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ho_ten VARCHAR(150) NOT NULL,
  ten_dang_nhap VARCHAR(100) NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  google_sub VARCHAR(255) NULL UNIQUE,
  sdt VARCHAR(20) NULL,
  dia_chi VARCHAR(255) NULL,
  mat_khau VARCHAR(255) NOT NULL,
  ngay_sinh DATE NULL,
  hang ENUM('Silver','Gold','Diamond') NOT NULL DEFAULT 'Silver',
  diem INT NOT NULL DEFAULT 0,
  trang_thai TINYINT NOT NULL DEFAULT 1,
  so_lan_huy INT NOT NULL DEFAULT 0,
  so_lan_bom INT NOT NULL DEFAULT 0,
  so_lan_tra INT NOT NULL DEFAULT 0,
  ngay_dang_ky DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS nhanvien (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ho_ten VARCHAR(150) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  mat_khau VARCHAR(255) NOT NULL,
  vai_tro VARCHAR(50) NOT NULL DEFAULT 'NhanVien',
  trang_thai TINYINT NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS danhgia (
  id INT AUTO_INCREMENT PRIMARY KEY,
  id_sanpham INT NOT NULL,
  id_khachhang INT NOT NULL,
  so_sao TINYINT NOT NULL,
  noi_dung TEXT NOT NULL,
  trang_thai TINYINT NOT NULL DEFAULT 1,
  ngay_tao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT ck_dg_flow CHECK (so_sao BETWEEN 1 AND 5),
  CONSTRAINT uq_dg_owner_flow UNIQUE (id_sanpham,id_khachhang),
  CONSTRAINT fk_dg_sp FOREIGN KEY (id_sanpham) REFERENCES sanpham(id),
  CONSTRAINT fk_dg_kh FOREIGN KEY (id_khachhang) REFERENCES khachhang(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS voucher (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ma_code VARCHAR(50) NOT NULL UNIQUE,
  pham_vi VARCHAR(20) NOT NULL DEFAULT 'tatca',
  id_danhmuc INT NULL,
  id_sanpham INT NULL,
  loai ENUM('tien','phantram','mienship') NOT NULL,
  gia_tri DECIMAL(12,2) NOT NULL DEFAULT 0,
  so_luong INT NOT NULL DEFAULT 0,
  ngay_bd DATE NOT NULL,
  ngay_kt DATE NOT NULL,
  don_toi_thieu DECIMAL(12,2) NOT NULL DEFAULT 0,
  hang_ap_dung VARCHAR(20) NULL,
  CONSTRAINT ck_vc_flow CHECK (gia_tri>=0 AND so_luong>=0 AND don_toi_thieu>=0 AND ngay_bd<=ngay_kt AND (loai<>'phantram' OR gia_tri<=100))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS khuyenmai (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ten_km VARCHAR(150) NOT NULL,
  loai ENUM('sanpham','danhmuc') NOT NULL,
  id_sanpham INT NULL,
  id_danhmuc INT NULL,
  phan_tram DECIMAL(5,2) NOT NULL DEFAULT 0,
  ngay_bd DATE NOT NULL,
  ngay_kt DATE NOT NULL,
  ghi_chu VARCHAR(255) NULL,
  CONSTRAINT ck_km_flow CHECK (phan_tram BETWEEN 0 AND 100 AND ngay_bd<=ngay_kt)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS donhang (
  id INT AUTO_INCREMENT PRIMARY KEY,
  id_khachhang INT NOT NULL,
  tong_tien DECIMAL(12,2) NOT NULL,
  phi_ship DECIMAL(12,2) NOT NULL DEFAULT 0,
  ma_voucher VARCHAR(50) NULL,
  dia_chi VARCHAR(255) NOT NULL,
  sdt VARCHAR(20) NOT NULL,
  trang_thai VARCHAR(50) NOT NULL DEFAULT 'Chờ xác nhận',
  ly_do_huy TEXT NULL,
  tam_tinh DECIMAL(12,2) NULL,
  tien_giam DECIMAL(12,2) NULL,
  voucher_pham_vi VARCHAR(20) NULL,
  voucher_id_doi_tuong INT NULL,
  voucher_doi_tuong VARCHAR(255) NULL,
  voucher_tien_ap_dung DECIMAL(12,2) NULL,
  id_voucher INT NULL,
  ngay_giao DATETIME NULL,
  diem_da_cong INT NOT NULL DEFAULT 0,
  checkout_key CHAR(64) NULL UNIQUE,
  ngay_dat DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  ngay_cap_nhat DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT ck_dh_flow CHECK (tong_tien>=0 AND phi_ship>=0),
  CONSTRAINT fk_dh_kh FOREIGN KEY (id_khachhang) REFERENCES khachhang(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS chitietdonhang (
  id INT AUTO_INCREMENT PRIMARY KEY,
  id_donhang INT NOT NULL,
  id_sanpham INT NOT NULL,
  so_luong INT NOT NULL,
  don_gia DECIMAL(12,2) NOT NULL,
  ten_sp_snapshot VARCHAR(255) NULL,
  ma_sp_snapshot VARCHAR(50) NULL,
  hinh_snapshot VARCHAR(255) NULL,
  CONSTRAINT ck_ct_flow CHECK (so_luong>0 AND don_gia>=0),
  CONSTRAINT fk_ct_dh FOREIGN KEY (id_donhang) REFERENCES donhang(id),
  CONSTRAINT fk_ct_sp FOREIGN KEY (id_sanpham) REFERENCES sanpham(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS email (
  id INT AUTO_INCREMENT PRIMARY KEY,
  id_khachhang INT NOT NULL,
  tieu_de VARCHAR(255) NOT NULL,
  noi_dung TEXT NOT NULL,
  ngay_gui DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  loai VARCHAR(50) NOT NULL DEFAULT 'hethong',
  CONSTRAINT fk_em_kh FOREIGN KEY (id_khachhang) REFERENCES khachhang(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Chon dung CSDL truoc khi import. Chi them bang moi; khong thay doi/xoa don hang cu.
CREATE TABLE IF NOT EXISTS thanhtoan_paypal (
  id_donhang INT PRIMARY KEY,
  checkout_key CHAR(64) NOT NULL UNIQUE,
  mode VARCHAR(10) NOT NULL,
  merchant_id VARCHAR(32) NOT NULL,
  client_id VARCHAR(255) NOT NULL,
  amount_vnd DECIMAL(12,2) NOT NULL,
  amount_usd DECIMAL(12,2) NOT NULL,
  vnd_per_usd DECIMAL(16,6) NOT NULL,
  rate_source VARCHAR(30) NOT NULL,
  rate_at DATETIME NOT NULL,
  provider_order_id VARCHAR(32) NULL UNIQUE,
  capture_id VARCHAR(32) NULL UNIQUE,
  create_request_id CHAR(32) NOT NULL UNIQUE,
  capture_request_id CHAR(32) NOT NULL UNIQUE,
  status VARCHAR(24) NOT NULL DEFAULT 'created',
  lease_until DATETIME NULL,
  expires_at DATETIME NOT NULL,
  paid_at DATETIME NULL,
  error_code VARCHAR(50) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_paypal_don FOREIGN KEY (id_donhang) REFERENCES donhang(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS lichsu_donhang (
  id INT AUTO_INCREMENT PRIMARY KEY,
  id_donhang INT NOT NULL,
  trang_thai_cu VARCHAR(50) NULL,
  trang_thai_moi VARCHAR(50) NOT NULL,
  tac_nhan VARCHAR(100) NOT NULL,
  ghi_chu TEXT NULL,
  ngay_tao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (id_donhang) REFERENCES donhang(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS trahang (
  id INT AUTO_INCREMENT PRIMARY KEY,
  id_donhang INT NOT NULL,
  ly_do TEXT NOT NULL,
  trang_thai VARCHAR(20) NOT NULL DEFAULT 'Chờ xử lý',
  phan_hoi TEXT NULL,
  so_tien_hoan DECIMAL(12,2) NULL,
  trang_thai_hoan VARCHAR(30) NULL,
  tham_chieu_hoan VARCHAR(255) NULL,
  nhap_lai_kho TINYINT NULL,
  ngay_tao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  ngay_xu_ly DATETIME NULL,
  UNIQUE KEY uq_tra_don (id_donhang),
  FOREIGN KEY (id_donhang) REFERENCES donhang(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS lichsu_kho (
  id INT AUTO_INCREMENT PRIMARY KEY,
  id_sanpham INT NOT NULL,
  id_donhang INT NULL,
  thay_doi INT NOT NULL,
  ton_sau INT NOT NULL,
  ly_do VARCHAR(255) NOT NULL,
  ngay_tao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX ix_kho_sp (id_sanpham, id),
  FOREIGN KEY (id_sanpham) REFERENCES sanpham(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS thanhtoan_qr (
  id_donhang INT PRIMARY KEY,
  bank_bin CHAR(6) NOT NULL,
  bank_name VARCHAR(100) NOT NULL,
  account_number VARCHAR(19) NOT NULL,
  account_name VARCHAR(100) NOT NULL,
  amount_vnd DECIMAL(12,2) NOT NULL,
  transfer_content VARCHAR(25) NOT NULL UNIQUE,
  status VARCHAR(24) NOT NULL DEFAULT 'waiting',
  customer_reported_at DATETIME NULL,
  received_reference VARCHAR(100) NULL,
  confirmed_by INT NULL,
  received_at DATETIME NULL,
  refund_reference VARCHAR(100) NULL,
  refunded_by INT NULL,
  refunded_at DATETIME NULL,
  expires_at DATETIME NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_qr_receipt (bank_bin,account_number,received_reference),
  UNIQUE KEY uq_qr_refund (bank_bin,account_number,refund_reference),
  INDEX ix_qr_pending (status,expires_at),
  CONSTRAINT fk_qr_order FOREIGN KEY (id_donhang) REFERENCES donhang(id),
  CONSTRAINT ck_qr_amount CHECK (amount_vnd>0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS lichsu_nhap (
  id INT AUTO_INCREMENT PRIMARY KEY,
  id_sanpham INT NOT NULL,
  so_luong INT NOT NULL,
  ngay_nhap DATE NOT NULL,
  nguoi_nhap VARCHAR(150) NOT NULL,
  ghi_chu VARCHAR(255) NOT NULL DEFAULT '',
  ngay_tao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX ix_nhap_sp (id_sanpham,id),
  FOREIGN KEY (id_sanpham) REFERENCES sanpham(id),
  CHECK (so_luong>0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS yeuthich (
  id_khachhang INT NOT NULL,
  id_sanpham INT NOT NULL,
  ngay_tao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_khachhang,id_sanpham),
  FOREIGN KEY (id_khachhang) REFERENCES khachhang(id),
  FOREIGN KEY (id_sanpham) REFERENCES sanpham(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS email_thanhtoan (
  id_donhang INT PRIMARY KEY,
  nguoi_nhan VARCHAR(150) NOT NULL,
  tieu_de VARCHAR(255) NOT NULL,
  noi_dung TEXT NOT NULL,
  trang_thai VARCHAR(20) NOT NULL DEFAULT 'cho_gui',
  so_lan_thu INT NOT NULL DEFAULT 0,
  loi VARCHAR(255) NULL,
  ngay_gui DATETIME NULL,
  ngay_tao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (id_donhang) REFERENCES donhang(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
