# Catalog mới và hướng dẫn voucher

Ngày cập nhật: 21/10/2026. Website: https://bpstore.gt.tc/

Bộ 12 sản phẩm, giá và tồn khởi tạo được chuẩn bị để kiểm thử theo yêu cầu. Cần duyệt lại giá bán, tồn thực tế và thời hạn bảo hành trước khi vận hành bán hàng thật. Không phải bảng báo giá thị trường đã xác nhận.

| Sản phẩm | Giá thử (đ) | Tồn khởi tạo | Nguồn thông số |
|---|---:|---:|---|
| iPhone 16 256GB · Xanh lưu ly | 22.990.000 | 15 | [Hãng](https://support.apple.com/en-us/121029) |
| Samsung Galaxy S24 Ultra 256GB · Xám Titan | 24.990.000 | 8 | [Hãng](https://www.samsung.com/vn/smartphones/galaxy-s24-ultra/specs/) |
| iPhone 15 Plus 128GB · Hồng | 18.990.000 | 8 | [Hãng](https://support.apple.com/en-us/111830) |
| Samsung Galaxy A56 5G 256GB · Đen Marble | 10.990.000 | 20 | [Hãng](https://www.samsung.com/vn/smartphones/galaxy-a/galaxy-a56-5g-awesome-graphite-256gb-sm-a566bzkuxxv/) |
| iPhone 14 128GB · Xanh dương | 13.990.000 | 10 | [Hãng](https://support.apple.com/en-us/111850) |
| iPhone 13 128GB · Đêm xanh thẳm | 11.990.000 | 12 | [Hãng](https://support.apple.com/en-us/111872) |
| Nokia 3210 4G (2024) · Vàng | 1.590.000 | 18 | [Hãng](https://www.hmd.com/en_int/nokia-3210/specs) |
| Nokia 235 4G (2024) · Xanh | 1.190.000 | 25 | [Hãng](https://www.hmd.com/en_int/nokia-235-4g-2024/specs) |
| AirPods 4 · Hộp sạc USB-C | 3.290.000 | 25 | [Hãng](https://support.apple.com/en-us/121203) |
| Bộ sạc Apple USB-C 20W | 490.000 | 50 | [Hãng](https://www.apple.com/vn/shop/product/md3j4za/a/) |
| Cáp Apple USB-C 60W · 1 mét | 390.000 | 60 | [Hãng](https://www.apple.com/vn/shop/product/mw493za/a/) |
| Bộ sạc Samsung 25W kèm cáp USB-C | 490.000 | 40 | [Hãng](https://www.samsung.com/vn/mobile-accessories/25w-power-adapter-black-ep-t2510xbegww/) |

## Tạo voucher trong quản trị

1. Mở trang đăng nhập chung và đăng nhập bằng tài khoản quản trị. Tài khoản có quyền sẽ tự vào trang quản trị.
2. Vào **Voucher → Tạo voucher**.
3. Điền mã, loại giảm, giá trị, lượt dùng, ngày bắt đầu/kết thúc, hạng tối thiểu và đơn tối thiểu.
4. Bấm **Tạo voucher**. Khách nhập mã ở trang thanh toán rồi bấm **Áp dụng** để xem tổng tiền.

- Giảm tiền: giá trị `50000` là giảm 50.000đ.
- Phần trăm: giá trị `5` là giảm 5% tiền hàng; hiện chưa có mức giảm tối đa theo tiền.
- Miễn ship: giá trị `0`.
- Ngày, lượt, đơn tối thiểu và hạng phải cùng thỏa mãn; tính theo giờ Việt Nam, gồm cả ngày bắt đầu/kết thúc.
- Gold dùng được cho Gold/Diamond; một đơn một voucher.
- Giỏ hàng chưa giữ lượt; khi đặt đơn mới trừ lượt. Hủy/trả hợp lệ hoàn lại đúng một lần.

## Các mã đã chuẩn bị

| Mã | Ưu đãi | Đơn tối thiểu | Hạng | Lượt ban đầu |
|---|---|---:|---|---:|
| BP50K | Giảm 50.000đ | 500.000đ | Mọi hạng | 100 |
| SHIP0 | Miễn phí giao hàng | 200.000đ | Mọi hạng | 100 |
| GOLD5 | Giảm 5% | 1.000.000đ | Gold/Diamond | 50 |
| DIAMOND10 | Giảm 10% | 2.000.000đ | Diamond | 25 |

Thời gian: 21/10/2026–20/11/2026, bao gồm hai ngày. Gold/Diamond vốn được miễn ship nên SHIP0 không tạo thêm tiền giảm cho các hạng đó.

Cập nhật 22/10: mã miễn ship được từ chối khi đơn đã miễn ship để không tiêu lượt vô ích. Nút áp voucher hoạt động với QR, COD và PayPal; chỉ đặt đơn thành công mới giữ lượt. Đã thêm **Bỏ voucher**, giữ phương thức thanh toán sau áp mã và hiển thị lý do chưa đủ điều kiện theo đơn. QR chuyển khoản do admin đối soát được hướng dẫn tại [Hướng dẫn QR và voucher](HUONG_DAN_QR_VA_VOUCHER.md).

## Bảo toàn dữ liệu và kiểm thử

- 5 sản phẩm cũ được đánh dấu ngừng bán và ẩn khỏi danh sách hiện hành; không xóa bản ghi liên quan tới đơn cũ.
- Danh mục Xiaomi cũ được ẩn; 4 danh mục hiện hành là iPhone, Samsung, Nokia, Phụ kiện.
- Giữ 9 đơn, 10 dòng chi tiết đơn, 5 khách, 3 nhân viên và giao dịch PayPal hiện có tại thời điểm sao lưu.
- Giữ cấu hình database/Google/PayPal/theme và thư viện trên hosting; không đưa cấu hình local lên.
- Bản sao đầy đủ 38.127 tệp nguồn được giữ trong 77 gói tar.gz có SHA-256, tại `D:/phpweb-hosting-sync/20261021/backup-parts`; danh sách ở `backup-manifest.json`. Bản ZIP đầy đủ: `D:/phpweb-hosting-sync/20261021/hosting-original-all.zip`.
- CSDL trước sửa: `D:/phpweb-hosting-sync/20261021/database-before.sql` (có dữ liệu riêng, không chia sẻ hoặc upload công khai).
- Bản ghép kiểm tra: 158 kiểm tra luồng + 42 PayPal ngoại tuyến đạt.
- Cấu hình PayPal trên hosting đang là **live** và được giữ nguyên. Dùng COD khi muốn test mà không thanh toán tiền thật; bộ test tự động không tạo giao dịch PayPal thật.

## Luồng nên test trực tiếp

1. Đăng nhập admin → sửa tên/mô tả/giá/ảnh một sản phẩm → mở lại trang khách, kiểm tra dữ liệu mới.
2. Chọn Nokia hoặc phụ kiện → Mua ngay → thử BP50K không đủ và đủ mức đơn; thử SHIP0 bằng tài khoản Silver.
3. Thêm vào giỏ → đổi số lượng → đặt COD → xem lịch sử khách và đơn trong admin.
4. Hủy trước giao → kiểm tra kho/voucher hoàn đúng một lần. Thử hủy khi đang giao phải bị chặn.
5. Chuyển Đã giao → khách được đánh giá; tài khoản chưa mua không được đánh giá.
6. Yêu cầu trả trong 7 ngày → admin nhận hàng, chọn nhập lại hoặc cách ly hàng lỗi → ghi chứng từ hoàn COD.
7. Đăng nhập Google bằng tài khoản hiện có để kiểm tra phiên thực tế; không cần đăng ký lại.
