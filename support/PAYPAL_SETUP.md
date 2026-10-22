# PayPal Checkout — cấu hình và vận hành

Website: https://bpstore.gt.tc/controller/index.php?act=thanhtoan

## Môi trường

- Hosting dùng Live app **BPStore Checkout**, tài khoản nhận của chủ cửa hàng. Client Secret chỉ được dùng trong PHP; không xuất ra JSON/HTML/JavaScript. Cấu hình thật không nằm trong gói mã nguồn.
- Local `http://127.0.0.1:8000` dùng Sandbox app có sẵn, cùng mã nguồn nhưng tài khoản và giao dịch thử riêng. Chạy `setup.bat` để bật MySQL/PHP. Không chép cấu hình Live về local để test.
- File `support/config/paypal.local.php` ghi đè `paypal.example.php`; biến môi trường `PAYPAL_*` được ưu tiên. Các trường: `enabled`, `mode`, `client_id`, `client_secret`, `merchant_id`, `app_url`, `vnd_per_usd`. Luôn giữ Client ID, Secret, Merchant ID cùng một app/tài khoản/môi trường.
- Giá hàng vẫn là VND; PayPal thu USD. `vnd_per_usd=0` lấy tỷ giá USD/VND hằng ngày từ ExchangeRate-API, cache một giờ, từ chối dữ liệu quá 48 giờ. Giá trị dương là tỷ giá thủ công. Trang xác nhận hiển thị tổng VND, USD, nguồn và tỷ giá; lưu cố định vào từng đơn. Phí PayPal/quy đổi của nguồn tiền do PayPal/ngân hàng hiển thị cho khách trước khi duyệt.

## Cài trên hosting khác

1. PHP 8.2 trở lên với PDO MySQL, cURL, HTTPS và CA hợp lệ. Cấu hình cơ sở dữ liệu bằng `support/config/database.local.php`.
2. Chọn đúng cơ sở dữ liệu rồi import `database/paypal.sql`. Migration chỉ thêm bảng `thanhtoan_paypal`; không xóa hay sửa đơn cũ. `database/schema.sql` và bản SQL đầy đủ cũng chứa bảng này cho cài mới.
3. Chép `paypal.example.php` thành `paypal.local.php`, điền thông tin app và URL gốc. Giữ `enabled=false` trong lúc triển khai.
4. Kiểm tra Sandbox, outbound HTTPS, xác thực OAuth, tài khoản nhận và tỷ giá; sau đó mới bật Live trên tên miền HTTPS.
5. Không đưa file cấu hình thật, bản sao lưu dữ liệu hoặc log có credential vào Git/gói upload công khai.

## Khách mua hàng

1. Đăng nhập, thêm sản phẩm vào giỏ, nhập địa chỉ/số điện thoại, chọn PayPal.
2. Website tính lại giá/voucher/phí giao hàng trong transaction, giữ hàng 30 phút và mở trang xác nhận số USD. Đơn ở trạng thái chưa thanh toán, vẫn xem được trong lịch sử.
3. Khách nhấn nút PayPal, duyệt khoản thu ở PayPal rồi trở về website. Website gọi capture từ server, kiểm tra mã đơn, tài khoản nhận, currency và số tiền.
4. Chỉ capture **COMPLETED** đúng dữ liệu mới thành **Đã thanh toán** và cho phép xác nhận/giao hàng. **APPROVED** chỉ là khách đã duyệt, chưa phải đã thu tiền.
5. Nếu đóng tab hoặc mất kết nối, mở **Lịch sử đơn → Xem thanh toán → Kiểm tra trạng thái PayPal**. Không tạo một khoản thanh toán khác khi kết quả còn chưa rõ. Nút hoàn tất khoản thu dùng lại request ID gốc.

## Đối soát, hủy và hoàn tiền

- Super Admin mở **Đơn hàng → Đối soát PayPal**. Thao tác này chỉ đọc Orders/Capture API, không thu thêm tiền. Kiểm tra mã capture, USD và trạng thái riêng với giao hàng.
- InfinityFree chặn nhiều request tự động đi vào bằng lớp kiểm tra trình duyệt. Vì vậy phiên bản này **không dựa vào webhook**: khách quay về website để capture; khách/admin tra cứu lại bằng API khi cần. Refund, pending, tranh chấp và trường hợp khách đóng trình duyệt cần được người bán theo dõi trên PayPal và đối soát. Muốn tự động hóa webhook/cron liên tục cần backend/hosting hỗ trợ.
- Đơn chưa trả tiền có thể hủy, hoàn kho đúng một lần. Hết 30 phút, đơn chưa thu tiền được hủy khi hệ thống kiểm tra lại (hiện tại khi khách vào checkout hoặc mở trang thanh toán). Đây không phải tác vụ nền chạy đúng phút hết hạn. Giao dịch pending/không rõ kết quả không tự hoàn kho.
- Đơn đã trả tiền không được hủy trực tiếp. Người bán hoàn tiền trong PayPal trước, rồi đối soát tại admin và xử lý trạng thái đơn. Website không tự gửi lệnh refund; hoàn một phần hoặc dữ liệu lệch được chuyển **Cần đối soát**.
- Đơn COD cũ/mới vẫn dùng luồng thu tiền khi giao hàng. Báo cáo doanh thu hiện vẫn theo trạng thái giao hàng thành công, không phải báo cáo số dư PayPal.

## Kiểm tra đã thực hiện ngày 21/10/2026

- 38 kiểm thử thanh toán offline/HTTP: tính tiền phía server, giả mạo giá, đúng chủ đơn/CSRF, return giả, không giao khi chưa thanh toán, retry/idempotency, timeout sau thu tiền, pending, refund và hoàn kho.
- 119 kiểm thử hồi quy của website đạt. Kiểm tra cú pháp toàn bộ file PHP thay đổi đạt.
- Sandbox thật qua giao diện: đơn local #4, **497.500 VND → 19,25 USD**, PayPal order `8HC4371793826812A`, capture `0JH67960JX8537911`, trạng thái **COMPLETED / Đã thanh toán**. Đọc lại Orders và Capture API vẫn xác nhận đúng.
- Hosting thật: PHP 8.4.25, cURL/HTTPS, Live OAuth và nguồn tỷ giá hoạt động. Chưa thực hiện giao dịch tiền thật; khoản thu thật đầu tiên do người mua chủ động duyệt.
- Orders API Live tạo được một phiên kiểm tra **chưa duyệt** (`02D015921T6187829`, `PAYER_ACTION_REQUIRED`), đúng tài khoản nhận và USD; không có capture và không có tiền bị thu. Phiên kiểm tra này không phải đơn bán hàng trong database.

Chạy kiểm thử từ thư mục dự án:

```powershell
& D:\data\php\php.exe support/tests/test_paypal.php
& D:\data\php\php.exe support/tests/test_luong.php
```

Hai bộ kiểm thử dùng database tạm riêng, không gọi PayPal Live và không xóa dữ liệu ứng dụng. Sandbox buyer và tài khoản demo được lưu riêng ngoài web root tại `D:\phpweb-local`; không đưa vào gói deploy.

## Tài liệu chính thức

- [PayPal Orders v2](https://developer.paypal.com/api/orders/v2)
- [Capture details](https://developer.paypal.com/api/payments/v2/captures-get)
- [Idempotency](https://developer.paypal.com/api/rest/reference/idempotency/)
- [Currency codes](https://developer.paypal.com/api/codes/currency)
- [ExchangeRate-API open endpoint](https://www.exchangerate-api.com/docs/free)
- [InfinityFree: giới hạn request tự động](https://forum.infinityfree.com/t/browser-security-system-features-and-limitations/49353)
