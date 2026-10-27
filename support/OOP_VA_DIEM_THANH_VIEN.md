# BP Store: hướng đối tượng, email có ảnh và điểm thành viên

## Cấu trúc xử lý

- Ba điểm vào `index.php`, `controller/index.php`, `controller/admin/index.php` gọi lớp `application` để khởi tạo phiên và điều phối.
- `storefront_controller` và `admin_request_controller` kế thừa `controller`; mỗi nhóm thao tác có phương thức riêng. Kiểm tra đăng nhập, quyền, POST và CSRF vẫn chạy trước thao tác.
- `admin_controller` xử lý nghiệp vụ quản trị; các model kế thừa `xl_database` / `csdl`. Các controller PayPal, QR và dịch vụ tải ảnh đã chuyển thành lớp.
- `chinhsach_thanhvien` chứa công thức, ngưỡng và quyền lợi dùng chung. `diem_thanhvien` đọc báo cáo theo khách; controller khách chỉ truyền ID từ phiên đăng nhập.
- View vẫn là template PHP. Các script CLI/migration và bộ kiểm thử là công cụ vận hành riêng.

## Sử dụng

- Admin → Sản phẩm: bấm ảnh hoặc tên để xem thông số, mô tả, album, giá hiện tại, hàng lỗi, tồn và 50 biến động/đợt nhập gần nhất. Xem được cả sản phẩm ngừng bán.
- Admin → Đơn hàng → mã đơn: có ảnh sản phẩm tại thời điểm đặt và liên kết chi tiết.
- Admin → Khách hàng: xem quy tắc chung; bấm **Xem cách tính** để đối chiếu điểm từng đơn.
- Người dùng → menu tài khoản → **Điểm và hạng thành viên**, hoặc từ **Hồ sơ của tôi**. Trang có điểm hiện có, hạng, tiến độ và lịch sử theo đơn (20 đơn/trang).

## Quy tắc giữ từ hệ thống hiện tại

`Điểm đơn = floor(tổng tiền đã chốt / 1.000)`; tổng gồm tiền hàng sau khuyến mãi, trừ voucher, cộng phí giao hàng. Mỗi đơn làm tròn riêng.

| Hạng | Điểm | Giao hàng |
| --- | --- | --- |
| Silver / Bạc | 0–499 | Phí theo đơn |
| Gold / Vàng | 500–1.999 | Miễn phí |
| Diamond / Kim cương | Từ 2.000 | Miễn phí |

Chỉ cộng khi đơn chuyển sang **Thành công**; thanh toán hoặc giao hàng riêng chưa cộng. Quyền lợi hạng áp dụng khi tạo đơn tiếp theo. Voucher vẫn kiểm tra điều kiện từng mã. Trả hàng hợp lệ thu hồi đúng `diem_da_cong` và xét lại hạng; khóa giao dịch và luồng trạng thái ngăn cộng/trừ lặp. Không tự hết hạn điểm, không đổi điểm thành tiền.

Báo cáo đọc số dư đang lưu và điểm còn ghi nhận trên các đơn; chênh lệch dữ liệu cũ được nêu rõ, không tự sửa số dư hay dựng lịch sử cộng/trừ chưa có.

## Email sản phẩm

Biên nhận thanh toán dùng `hinh_snapshot` của dòng đơn; nếu thiếu mới dùng ảnh hiện tại. `anh_sanpham` chỉ đọc ảnh trong thư mục cửa hàng và nhúng bằng MIME Content-ID qua PHPMailer, không tải URL từ người dùng. WebP chuyển sang PNG khi PHP có GD; khi chưa có GD vẫn nhúng WebP. SVG/ảnh thiếu dùng logo kèm mô tả chưa có ảnh. Ảnh gốc và tên sản phẩm được escape đúng ngữ cảnh.

Giữ nguyên thời điểm gửi biên nhận sau khi xác nhận tiền; thư xác nhận đặt hàng trong hộp thư website vẫn không được đánh dấu đã thanh toán. Không cần migration database cho bản này.

## Kiểm tra

Chạy bằng PHP 8 trở lên, có PDO MySQL và mbstring:

```text
D:\data\php\php.exe support/tests/test_luong.php
D:\data\php\php.exe support/tests/test_paypal.php
D:\data\php\php.exe support/tests/member_policy_email_scenarios.php
D:\data\php\php.exe support/tests/payment_email_template_scenarios.php
D:\data\php\php.exe support/tests/homepage_presentation_scenarios.php
```

Kiểm thử tích hợp tạo database test riêng, tắt SMTP thật, đối chiếu database ứng dụng trước/sau. Không có khoản thanh toán thật được thực hiện.
