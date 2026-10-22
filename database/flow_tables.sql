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
