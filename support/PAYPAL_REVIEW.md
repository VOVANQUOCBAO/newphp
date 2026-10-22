# Điều tra checkout và phương án PayPal Live

Tài liệu điều tra ban đầu ngày 21/10/2026. **Luồng bên dưới mô tả trước khi tích hợp.** PayPal đã được triển khai sau lần điều tra; xem [PAYPAL_SETUP.md](PAYPAL_SETUP.md) để biết cấu hình, kết quả kiểm thử và giới hạn vận hành hiện tại.

## Luồng hiện tại

1. `view/thanhtoan.php` chỉ gửi địa chỉ, số điện thoại và voucher. Không có lựa chọn phương thức thanh toán hay nút PayPal.
2. `controller/index.php`, nhánh `thanhtoan`, gọi `donhang::taoDon()`.
3. Model tính lại giá/khuyến mãi/voucher/phí ship, tạo đơn Chờ xác nhận và chi tiết đơn, trừ kho và lượt voucher trong transaction.
4. Controller xóa giỏ, lưu/gửi thư xác nhận rồi hiển thị Đặt hàng thành công.
5. Admin cập nhật trạng thái giao hàng. Doanh thu hiện tính từ đơn có trạng thái Thành công, không đối chiếu một giao dịch thanh toán bên ngoài.

Không có trường phương thức/trạng thái thanh toán, bảng giao dịch, PayPal order/capture ID, create/capture API hay webhook. Logo Visa/Mastercard/VietQR/COD ở footer không phải tích hợp thanh toán. Luồng hiện tại mới xác nhận việc đặt hàng, chưa xác nhận đã nhận tiền.

## Luồng PayPal cần bổ sung

Giữ giá VND nếu chủ cửa hàng chọn quy đổi. Trước khi khách duyệt PayPal, server tính tổng đơn và chốt cả số tiền USD lẫn tỷ giá cho đơn đó. Hiển thị rõ VND, USD và tỷ giá; không tính lại bằng một tỷ giá khác khi capture.

1. Khách nhập thông tin nhận hàng và chọn PayPal.
2. Server tạo đơn nội bộ chờ thanh toán cùng phiên thanh toán, giữ chỗ hàng và chốt số tiền/currency.
3. Server gọi PayPal Orders API để tạo order với intent CAPTURE, gắn mã đơn nội bộ và tài khoản nhận đã cấu hình.
4. Khách đăng nhập/chấp thuận trong cửa sổ PayPal.
5. Trình duyệt gọi endpoint cùng origin của website; server kiểm tra đăng nhập, chủ đơn và CSRF, rồi gọi capture API.
6. Chỉ ghi đã thanh toán khi dữ liệu đọc trực tiếp từ PayPal chứng minh capture COMPLETED, đúng tài khoản nhận, đúng order, số tiền và USD. APPROVED hoặc trang return thành công chưa phải bằng chứng đã thu tiền.
7. Lưu capture ID duy nhất và xử lý mỗi giao dịch một lần. Lần retry phải dùng lại PayPal-Request-Id của thao tác gốc.
8. Giao dịch pending/timeout cần tra cứu lại qua API; không tự đánh thất bại hoặc tạo khoản thu mới khi chưa biết kết quả.
9. Khách/admin xem riêng trạng thái thanh toán và trạng thái giao hàng. Hủy đơn đã trả tiền phải đi qua quy trình hoàn tiền, không chỉ hoàn kho.

## Thay đổi dữ liệu và ứng dụng

- Đơn hàng: phương thức thanh toán, trạng thái thanh toán; không thay thế trạng thái giao hàng hiện có.
- Bảng giao dịch: mã đơn, môi trường Live/Sandbox, PayPal order ID, capture ID, số tiền/currency dự kiến và thực nhận, tỷ giá chốt, idempotency key, trạng thái và thời điểm xác nhận. Không lưu số thẻ/mật khẩu PayPal.
- Giữ chỗ hàng có hạn; không giải phóng khi capture đang xử lý hoặc chưa rõ kết quả. Cần xử lý cạnh tranh giữa capture, hủy và hết hạn.
- Cấu hình PayPal riêng ngoài Git: môi trường, Live Client ID/Secret, Merchant ID, currency, chính sách tỷ giá. Secret chỉ dùng phía server, không nhúng vào JavaScript.
- Admin: hiển thị thanh toán, đối soát, mã giao dịch và quy trình hoàn tiền. Thay đổi tỷ giá chỉ dành cho Super Admin, có CSRF và lịch sử thay đổi; đơn đã chốt không bị đổi tỷ giá theo cấu hình mới.
- Kiểm thử: giả mạo giá/order ID, sai chủ đơn, CSRF, approve chưa capture, sai tiền/currency/người nhận, capture pending/thất bại, timeout sau capture, gọi lặp/đồng thời, hủy/hết hạn và hoàn tiền.

## Giới hạn hosting hiện tại

InfinityFree cho phép nhiều kết nối HTTP đi ra ngoài nhưng chặn truy cập tự động/webhook đi vào bằng lớp kiểm tra trình duyệt. Vì vậy không được thiết kế việc xác nhận/hoàn tiền chỉ dựa vào webhook gửi thẳng về `bpstore.gt.tc` và coi như đã hoạt động.

Create/capture/tra cứu từ PHP tới PayPal có thể được triển khai theo API, nhưng cần kiểm chứng outbound trên hosting thật và có cơ chế đối soát bền vững khi browser đóng, request timeout hoặc có hoàn tiền/tranh chấp. Để vận hành tự động đầy đủ, hướng đơn giản hơn là backend có thể nhận webhook PayPal; mua tên miền riêng nhưng giữ nguyên lớp hosting này không gỡ giới hạn đó.

## Thông tin cần chốt trước khi bật Live

1. Tài khoản nhận PayPal Business có sẵn hay chưa; cặp Live Client ID/Secret và Merchant ID tương ứng. Chủ tài khoản tự hoàn tất bước xác minh nếu PayPal yêu cầu.
2. Giữ giá VND và quy đổi USD hay chuyển giá bán sang USD. PayPal không liệt kê VND trong danh sách currency của REST API. Nếu giữ VND cần chính sách/tỷ giá do chủ cửa hàng quyết định, không tự suy ra tỷ giá nhận tiền.
3. Phương án hosting/đối soát khi không nhận được webhook trực tiếp.

Sau khi có thông tin cấu hình: kiểm thử Sandbox với tài khoản thử, rồi bật Live. Giao dịch tiền thật cuối cùng do chủ tài khoản/khách chủ động thực hiện; không tự tạo một khoản thanh toán thật trong quá trình kiểm tra mã nguồn.

## Nguồn chính thức

- PayPal Checkout: https://developer.paypal.com/checkout/integrate
- Orders API: https://developer.paypal.com/api/orders/v2
- Authentication: https://developer.paypal.com/api/rest/authentication/
- Currency codes: https://developer.paypal.com/api/codes/currency
- Idempotency: https://developer.paypal.com/api/rest/reference/idempotency/
- Webhook verification: https://developer.paypal.com/api/rest/webhooks/rest/
- InfinityFree, nhận webhook: https://forum.infinityfree.com/t/is-infinityfree-hosting-support-webhook/71088
- InfinityFree, giới hạn truy cập tự động: https://forum.infinityfree.com/t/browser-security-system-features-and-limitations/49353
