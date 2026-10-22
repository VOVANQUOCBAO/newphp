USE webbanhang_mvc;
SET NAMES utf8mb4;
START TRANSACTION;

-- Du lieu mau cho website; chi them ban ghi con thieu, khong xoa du lieu cu.
-- Anh san pham duoc gan lai theo bo minh hoa SVG nguyen ban trong view/image.

INSERT INTO danhmuc (ten_danhmuc, mo_ta)
SELECT 'Điện thoại iPhone', 'Các dòng điện thoại Apple iPhone chính hãng VN/A'
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM danhmuc WHERE ten_danhmuc = 'Điện thoại iPhone');

INSERT INTO danhmuc (ten_danhmuc, mo_ta)
SELECT 'Điện thoại Samsung', 'Điện thoại thông minh Samsung Galaxy cao cấp'
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM danhmuc WHERE ten_danhmuc = 'Điện thoại Samsung');

INSERT INTO danhmuc (ten_danhmuc, mo_ta)
SELECT 'Điện thoại Xiaomi', 'Điện thoại Android cấu hình cao, giá tốt từ Xiaomi'
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM danhmuc WHERE ten_danhmuc = 'Điện thoại Xiaomi');

INSERT INTO danhmuc (ten_danhmuc, mo_ta)
SELECT 'Phụ kiện', 'Củ sạc, cáp sạc, tai nghe và ốp lưng chính hãng'
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM danhmuc WHERE ten_danhmuc = 'Phụ kiện');

SET @dm_iphone := (SELECT id FROM danhmuc WHERE ten_danhmuc = 'Điện thoại iPhone' ORDER BY id LIMIT 1);
SET @dm_samsung := (SELECT id FROM danhmuc WHERE ten_danhmuc = 'Điện thoại Samsung' ORDER BY id LIMIT 1);
SET @dm_xiaomi := (SELECT id FROM danhmuc WHERE ten_danhmuc = 'Điện thoại Xiaomi' ORDER BY id LIMIT 1);
SET @dm_phukien := (SELECT id FROM danhmuc WHERE ten_danhmuc = 'Phụ kiện' ORDER BY id LIMIT 1);

INSERT INTO sanpham (ma_sp, ten_sp, id_danhmuc, gia, so_luong_goc, so_luong_ton, hinh, mo_ta)
SELECT 'IP15PM256', 'iPhone 15 Pro Max 256GB', @dm_iphone, 29990000.00, 100, 85,
       'ai-phone-elegance.svg',
       'iPhone 15 Pro Max thiết kế titan, camera zoom quang học 5x, chip A17 Pro siêu mạnh mẽ.'
FROM DUAL
WHERE @dm_iphone IS NOT NULL AND NOT EXISTS (SELECT 1 FROM sanpham WHERE ma_sp = 'IP15PM256');

INSERT INTO sanpham (ma_sp, ten_sp, id_danhmuc, gia, so_luong_goc, so_luong_ton, hinh, mo_ta)
SELECT 'IP14P128', 'iPhone 14 Pro 128GB', @dm_iphone, 24590000.00, 50, 20,
       NULL, 'Màn hình Dynamic Island, chip A16 Bionic tối ưu hiệu năng.'
FROM DUAL
WHERE @dm_iphone IS NOT NULL AND NOT EXISTS (SELECT 1 FROM sanpham WHERE ma_sp = 'IP14P128');

INSERT INTO sanpham (ma_sp, ten_sp, id_danhmuc, gia, so_luong_goc, so_luong_ton, hinh, mo_ta)
SELECT 'SS24U512', 'Samsung Galaxy S24 Ultra 512GB', @dm_samsung, 31990000.00, 80, 70,
       'ai-phone-innovation.svg',
       'Tích hợp AI tiên tiến, khung viền titan và bút S-Pen thế hệ mới nhất.'
FROM DUAL
WHERE @dm_samsung IS NOT NULL AND NOT EXISTS (SELECT 1 FROM sanpham WHERE ma_sp = 'SS24U512');

INSERT INTO sanpham (ma_sp, ten_sp, id_danhmuc, gia, so_luong_goc, so_luong_ton, hinh, mo_ta)
SELECT 'X14PRO256', 'Xiaomi 14 Pro 256GB', @dm_xiaomi, 21990000.00, 60, 45,
       NULL, 'Hợp tác camera Leica, sạc siêu nhanh 120W, màn hình độ sáng cực cao.'
FROM DUAL
WHERE @dm_xiaomi IS NOT NULL AND NOT EXISTS (SELECT 1 FROM sanpham WHERE ma_sp = 'X14PRO256');

INSERT INTO sanpham (ma_sp, ten_sp, id_danhmuc, gia, so_luong_goc, so_luong_ton, hinh, mo_ta)
SELECT 'AP20W', 'Củ sạc Apple 20W Type-C', @dm_phukien, 550000.00, 200, 150,
       NULL, 'Củ sạc nhanh 20W chính hãng Apple dùng cho iPhone, iPad.'
FROM DUAL
WHERE @dm_phukien IS NOT NULL AND NOT EXISTS (SELECT 1 FROM sanpham WHERE ma_sp = 'AP20W');

UPDATE sanpham SET hinh = 'ai-phone-elegance.svg' WHERE id_danhmuc = @dm_iphone;
UPDATE sanpham SET hinh = 'ai-phone-innovation.svg' WHERE id_danhmuc = @dm_samsung;
UPDATE sanpham SET hinh = 'ai-phone-portrait.svg' WHERE id_danhmuc = @dm_xiaomi;
UPDATE sanpham SET hinh = 'ai-accessories.svg' WHERE id_danhmuc = @dm_phukien;

INSERT INTO khachhang (ho_ten, email, mat_khau, ngay_sinh, hang, diem, trang_thai)
SELECT 'Nguyễn Văn An', 'nguyenvanan@example.test',
       '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
       '1995-05-12', 'Diamond', 5500, 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM khachhang WHERE email = 'nguyenvanan@example.test');

INSERT INTO khachhang (ho_ten, email, mat_khau, ngay_sinh, hang, diem, trang_thai)
SELECT 'Trần Thị Bình', 'binhtran99@example.test',
       '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
       '1999-10-20', 'Gold', 2500, 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM khachhang WHERE email = 'binhtran99@example.test');

INSERT INTO khachhang (ho_ten, email, mat_khau, ngay_sinh, hang, diem, trang_thai)
SELECT 'Lê Hoàng Minh', 'minhlehoang@example.test',
       '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
       '2001-02-28', 'Silver', 100, 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM khachhang WHERE email = 'minhlehoang@example.test');

INSERT INTO nhanvien (ho_ten, email, mat_khau, vai_tro, trang_thai)
SELECT 'Phạm Tấn Phát', 'phat.sales@local.test',
       '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'NhanVien', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM nhanvien WHERE email = 'phat.sales@local.test');

INSERT INTO nhanvien (ho_ten, email, mat_khau, vai_tro, trang_thai)
SELECT 'Đỗ Thị Lan', 'lan.cskh@local.test',
       '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'NhanVien', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM nhanvien WHERE email = 'lan.cskh@local.test');

SET @kh_an := (SELECT id FROM khachhang WHERE email = 'nguyenvanan@example.test' ORDER BY id LIMIT 1);
SET @kh_binh := (SELECT id FROM khachhang WHERE email = 'binhtran99@example.test' ORDER BY id LIMIT 1);
SET @kh_minh := (SELECT id FROM khachhang WHERE email = 'minhlehoang@example.test' ORDER BY id LIMIT 1);
SET @sp_iphone15 := (SELECT id FROM sanpham WHERE ma_sp = 'IP15PM256' LIMIT 1);
SET @sp_samsung := (SELECT id FROM sanpham WHERE ma_sp = 'SS24U512' LIMIT 1);
SET @sp_sac := (SELECT id FROM sanpham WHERE ma_sp = 'AP20W' LIMIT 1);

INSERT INTO danhgia (id_sanpham, id_khachhang, so_sao, noi_dung)
SELECT @sp_iphone15, @kh_an, 5,
       'Máy cầm rất nhẹ do viền titan, camera zoom chụp xa xuất sắc. Giao hàng cực kỳ nhanh chóng!'
FROM DUAL
WHERE @sp_iphone15 IS NOT NULL AND @kh_an IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM danhgia
      WHERE id_sanpham = @sp_iphone15 AND id_khachhang = @kh_an
        AND noi_dung = 'Máy cầm rất nhẹ do viền titan, camera zoom chụp xa xuất sắc. Giao hàng cực kỳ nhanh chóng!'
  );

INSERT INTO danhgia (id_sanpham, id_khachhang, so_sao, noi_dung)
SELECT @sp_samsung, @kh_binh, 5,
       'S-Pen dùng để note công việc rất tiện lợi, tính năng AI dịch trực tiếp gọi điện rất hay.'
FROM DUAL
WHERE @sp_samsung IS NOT NULL AND @kh_binh IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM danhgia
      WHERE id_sanpham = @sp_samsung AND id_khachhang = @kh_binh
        AND noi_dung = 'S-Pen dùng để note công việc rất tiện lợi, tính năng AI dịch trực tiếp gọi điện rất hay.'
  );

INSERT INTO danhgia (id_sanpham, id_khachhang, so_sao, noi_dung)
SELECT @sp_sac, @kh_minh, 4,
       'Sạc nhanh chuẩn, cắm vào iPhone nhận ngay nhưng củ sạc hơi nóng một chút khi sạc từ 0-50%.'
FROM DUAL
WHERE @sp_sac IS NOT NULL AND @kh_minh IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM danhgia
      WHERE id_sanpham = @sp_sac AND id_khachhang = @kh_minh
        AND noi_dung = 'Sạc nhanh chuẩn, cắm vào iPhone nhận ngay nhưng củ sạc hơi nóng một chút khi sạc từ 0-50%.'
  );

INSERT INTO voucher (ma_code, loai, gia_tri, so_luong, ngay_bd, ngay_kt, hang_ap_dung)
SELECT 'GIAM100K', 'tien', 100000.00, 499, '2024-01-01', '2026-12-31', NULL
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM voucher WHERE ma_code = 'GIAM100K');

INSERT INTO voucher (ma_code, loai, gia_tri, so_luong, ngay_bd, ngay_kt, hang_ap_dung)
SELECT 'FREESHIP', 'mienship', 30000.00, 999, '2024-01-01', '2026-12-31', NULL
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM voucher WHERE ma_code = 'FREESHIP');

INSERT INTO voucher (ma_code, loai, gia_tri, so_luong, ngay_bd, ngay_kt, hang_ap_dung)
SELECT 'VIPDIAMOND', 'phantram', 5.00, 100, '2024-01-01', '2026-12-31', 'Diamond'
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM voucher WHERE ma_code = 'VIPDIAMOND');

INSERT INTO khuyenmai (ten_km, loai, id_sanpham, id_danhmuc, phan_tram, ngay_bd, ngay_kt, ghi_chu)
SELECT 'Tuần lễ vàng Apple', 'sanpham', @sp_iphone15, NULL, 3.00,
       '2024-10-01', '2026-12-31', 'Giảm trực tiếp iPhone 15 Pro Max'
FROM DUAL
WHERE @sp_iphone15 IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM khuyenmai WHERE ten_km = 'Tuần lễ vàng Apple');

INSERT INTO khuyenmai (ten_km, loai, id_sanpham, id_danhmuc, phan_tram, ngay_bd, ngay_kt, ghi_chu)
SELECT 'Xả kho phụ kiện', 'danhmuc', NULL, @dm_phukien, 15.00,
       '2024-11-01', '2026-12-31', 'Giảm giá toàn bộ củ sạc, ốp lưng'
FROM DUAL
WHERE @dm_phukien IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM khuyenmai WHERE ten_km = 'Xả kho phụ kiện');

INSERT INTO donhang (id_khachhang, tong_tien, phi_ship, ma_voucher, dia_chi, sdt, trang_thai)
SELECT @kh_an, 29990000.00, 0.00, 'FREESHIP',
       '123 Nguyễn Văn Cừ, Phường 4, Quận 5, TP.HCM', '0901234567', 'Thành công'
FROM DUAL
WHERE @kh_an IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM donhang
      WHERE id_khachhang = @kh_an AND sdt = '0901234567'
        AND dia_chi = '123 Nguyễn Văn Cừ, Phường 4, Quận 5, TP.HCM'
  );

INSERT INTO donhang (id_khachhang, tong_tien, phi_ship, ma_voucher, dia_chi, sdt, trang_thai)
SELECT @kh_binh, 31890000.00, 0.00, 'GIAM100K',
       '456 Lê Lợi, Phường Bến Nghé, Quận 1, TP.HCM', '0987654321', 'Đang giao'
FROM DUAL
WHERE @kh_binh IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM donhang
      WHERE id_khachhang = @kh_binh AND sdt = '0987654321'
        AND dia_chi = '456 Lê Lợi, Phường Bến Nghé, Quận 1, TP.HCM'
  );

INSERT INTO donhang (id_khachhang, tong_tien, phi_ship, ma_voucher, dia_chi, sdt, trang_thai)
SELECT @kh_minh, 580000.00, 30000.00, NULL,
       '789 Điện Biên Phủ, Phường 22, Quận Bình Thạnh, TP.HCM', '0933445566', 'Chờ xác nhận'
FROM DUAL
WHERE @kh_minh IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM donhang
      WHERE id_khachhang = @kh_minh AND sdt = '0933445566'
        AND dia_chi = '789 Điện Biên Phủ, Phường 22, Quận Bình Thạnh, TP.HCM'
  );

SET @dh_an := (
    SELECT id FROM donhang
    WHERE id_khachhang = @kh_an AND sdt = '0901234567'
      AND dia_chi = '123 Nguyễn Văn Cừ, Phường 4, Quận 5, TP.HCM'
    ORDER BY id LIMIT 1
);
SET @dh_binh := (
    SELECT id FROM donhang
    WHERE id_khachhang = @kh_binh AND sdt = '0987654321'
      AND dia_chi = '456 Lê Lợi, Phường Bến Nghé, Quận 1, TP.HCM'
    ORDER BY id LIMIT 1
);
SET @dh_minh := (
    SELECT id FROM donhang
    WHERE id_khachhang = @kh_minh AND sdt = '0933445566'
      AND dia_chi = '789 Điện Biên Phủ, Phường 22, Quận Bình Thạnh, TP.HCM'
    ORDER BY id LIMIT 1
);

INSERT INTO chitietdonhang (id_donhang, id_sanpham, so_luong, don_gia)
SELECT @dh_an, @sp_iphone15, 1, 29990000.00
FROM DUAL
WHERE @dh_an IS NOT NULL AND @sp_iphone15 IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM chitietdonhang
      WHERE id_donhang = @dh_an AND id_sanpham = @sp_iphone15
  );

INSERT INTO chitietdonhang (id_donhang, id_sanpham, so_luong, don_gia)
SELECT @dh_binh, @sp_samsung, 1, 31990000.00
FROM DUAL
WHERE @dh_binh IS NOT NULL AND @sp_samsung IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM chitietdonhang
      WHERE id_donhang = @dh_binh AND id_sanpham = @sp_samsung
  );

INSERT INTO chitietdonhang (id_donhang, id_sanpham, so_luong, don_gia)
SELECT @dh_minh, @sp_sac, 1, 550000.00
FROM DUAL
WHERE @dh_minh IS NOT NULL AND @sp_sac IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM chitietdonhang
      WHERE id_donhang = @dh_minh AND id_sanpham = @sp_sac
  );

INSERT INTO email (id_khachhang, tieu_de, noi_dung, loai)
SELECT @kh_an, CONCAT('Xác nhận đơn hàng #', @dh_an, ' thành công') COLLATE utf8mb4_unicode_ci,
       'Cảm ơn bạn đã tin tưởng mua sắm iPhone 15 Pro Max tại hệ thống. Đơn hàng của bạn đã được giao thành công.',
       'hethong'
FROM DUAL
WHERE @kh_an IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM email
      WHERE id_khachhang = @kh_an
        AND tieu_de = CONCAT('Xác nhận đơn hàng #', @dh_an, ' thành công') COLLATE utf8mb4_unicode_ci
  );

INSERT INTO email (id_khachhang, tieu_de, noi_dung, loai)
SELECT @kh_binh, CONCAT('Đơn hàng #', @dh_binh, ' đang được vận chuyển') COLLATE utf8mb4_unicode_ci,
       'Đơn hàng Samsung Galaxy S24 Ultra của bạn đã được đóng gói và giao cho đơn vị vận chuyển Giao Hàng Nhanh.',
       'hethong'
FROM DUAL
WHERE @kh_binh IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM email
      WHERE id_khachhang = @kh_binh
        AND tieu_de = CONCAT('Đơn hàng #', @dh_binh, ' đang được vận chuyển') COLLATE utf8mb4_unicode_ci
  );

INSERT INTO email (id_khachhang, tieu_de, noi_dung, loai)
SELECT @kh_minh, 'Cảnh báo đăng nhập thiết bị lạ',
       'Chúng tôi phát hiện tài khoản của bạn được đăng nhập từ một thiết bị mới vào lúc 14:00 hôm nay.',
       'baomat'
FROM DUAL
WHERE @kh_minh IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM email
      WHERE id_khachhang = @kh_minh AND tieu_de = 'Cảnh báo đăng nhập thiết bị lạ'
  );

COMMIT;
