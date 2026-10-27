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
