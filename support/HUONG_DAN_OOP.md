# BP Store — PHP hướng đối tượng đơn giản

Ngày rà soát: 27/10/2026.

## Cách tổ chức

Ứng dụng dùng MVC cơ bản, PHP 8.1 trở lên, PDO MySQL và các template PHP. Không cần framework, ORM hoặc bộ chứa dependency.

```text
index.php / controller/index.php / controller/admin/index.php
    → application: khởi tạo session và chọn khu vực
    → storefront_controller hoặc admin_request_controller
    → model: đọc dữ liệu, kiểm tra và xử lý nghiệp vụ
    → controller::render()
    → view: hiển thị HTML
```

| Thành phần | Vai trò |
| --- | --- |
| `controller/application.php` | Điểm khởi động chung cho ba URL vào ứng dụng. |
| `controller/controller.php` | Hiển thị HTML/JSON, chuyển trang, kiểm tra tham số, phiên đăng nhập và CSRF. |
| `controller/storefront_controller.php` | Các thao tác của khách. Mỗi thao tác có phương thức riêng; model dùng chung được giữ bằng thuộc tính. |
| `controller/admin/request_controller.php` | Kiểm tra quyền, POST, CSRF rồi gọi chức năng quản trị. |
| `controller/admin/admin_controller.php` | Phối hợp các model cho màn hình quản trị. |
| `controller/giohang.php` | Quản lý giỏ trong session, giỏ mua ngay và chi tiết tính giá. |
| `controller/paypal.php`, `controller/bank.php` | Điều phối màn hình và yêu cầu thanh toán. |
| `controller/product_upload.php` | Kiểm tra, lưu ảnh và dọn ảnh tạm. |
| `controller/xuly_email.php` | Gửi email qua PHPMailer. |
| `model/csdl.php` | Đóng gói kết nối PDO dùng chung. |
| `model/xl_database.php` | Các thao tác truy vấn có tham số, thêm/sửa/xóa và giao dịch. |
| Các model sản phẩm, danh mục, khách, nhân viên, đơn, voucher… | Mỗi lớp xử lý một nhóm dữ liệu và quy tắc tương ứng. |
| `model/thanhtoan.php` | Chuẩn bị báo giá và danh sách voucher, chưa tạo đơn. |
| `model/giaodien.php` | Đọc, kiểm tra và lưu cấu hình giao diện. |
| `view/helpers.php` | Lớp `view_helper` định dạng tiền, đường dẫn ảnh và gọi template thẻ sản phẩm. |
| `view/components/product_card.php` | Template thẻ sản phẩm dùng lại tại trang chủ. |
| `support/scripts/migrate_*.php` | Các lớp migration bổ sung cấu trúc, dùng khi cài đặt hoặc quản trị chủ động kích hoạt. |

View giữ HTML, vòng lặp, điều kiện hiển thị và các lời gọi định dạng. View không truy vấn SQL, không tạo controller, không tự đọc cấu hình giao diện. CSS/JavaScript vẫn đảm nhiệm giao diện trình duyệt. SQL là cấu trúc dữ liệu; thư viện PHPMailer giữ nguyên mã của thư viện.

## Bốn ý OOP có thể trình bày

**Lớp và đối tượng:** `sanpham` là lớp; `(new sanpham())` tạo đối tượng để gọi phương thức.

```php
$sanPhamModel = new sanpham();
$sanPham = $sanPhamModel->layChiTiet(1);
```

**Đóng gói:** `$pdo` là thuộc tính `protected`; các thuộc tính model trong controller là `private`. Bên ngoài gọi phương thức thay vì sửa trực tiếp dữ liệu nội bộ.

**Kế thừa:** `sanpham extends xl_database`, `xl_database extends csdl`; model tái sử dụng kết nối và các hàm truy vấn. Hai controller HTTP kế thừa `controller` để dùng render, chuyển trang và xác thực.

**Ghi đè:** `storefront_controller::render()` bổ sung dữ liệu báo giá cho trang thanh toán rồi gọi `parent::render()`. Các trang khác dùng phần hiển thị chung.

Các hàm định dạng không cần trạng thái được đặt là `static`, ví dụ `view_helper::money(150000)`. Các nghiệp vụ có dữ liệu dùng đối tượng model. Không cần thêm interface hoặc nhiều tầng chỉ để minh họa OOP.

## Luồng thêm một chức năng

1. Thêm phương thức đọc/ghi và kiểm tra dữ liệu trong model phù hợp.
2. Thêm `case` trong controller và gọi một phương thức `action_...()`.
3. Với thao tác thay đổi dữ liệu, đưa tên thao tác vào danh sách yêu cầu POST/CSRF và quyền truy cập tương ứng.
4. Controller truyền mảng dữ liệu qua `render()`; view chỉ dùng mảng đó để hiển thị.

## Những phần được giữ khi đổi cấu trúc

- URL và các tên `act` hiện có; giao diện và dữ liệu đang dùng.
- Đơn hàng tính tiền tại server; giữ kho và voucher trong giao dịch, chống đặt/cập nhật trùng.
- COD, QR và PayPal vẫn kiểm tra quyền sở hữu, trạng thái và thông tin thanh toán.
- Điểm thành viên chỉ cộng khi đơn thành công, thu hồi khi trả hợp lệ.
- Mật khẩu cũ được nâng cấp khi đăng nhập đúng. Không chấp nhận chuỗi hash BCrypt hoặc MD5 làm mật khẩu đăng nhập.

## Chạy kiểm tra

Dùng PHP có `pdo_mysql`, `mbstring`, `curl`, `openssl`, `fileinfo`; MySQL/MariaDB local phải đang chạy. Trên máy hiện tại:

```powershell
& 'D:\data\php\php.exe' support/tests/test_luong.php
& 'D:\data\php\php.exe' support/tests/test_paypal.php
& 'D:\data\php\php.exe' support/tests/homepage_presentation_scenarios.php
& 'D:\data\php\php.exe' support/tests/member_policy_email_scenarios.php
& 'D:\data\php\php.exe' support/tests/payment_email_template_scenarios.php
```

`test_luong.php` tạo database test riêng, chạy các tình huống con rồi xóa database test. Bộ PayPal dùng dịch vụ giả, không thực hiện thanh toán. Bộ kiểm thử đối chiếu dữ liệu ứng dụng trước/sau để kiểm tra không sửa nhầm database đang dùng. Các thử nghiệm TiDB/HTTPS bridge riêng chỉ chạy khi có môi trường đó; bản MySQL hosting không cần bridge.

## Cập nhật hosting

Gói ZIP triển khai chứa đường dẫn tương đối tính từ `htdocs`. Giải nén ghi đè file tương ứng. Giữ cấu hình `support/config/*.local.php`, thư mục ảnh khách đã tải và thư viện `support/vendor` hiện có trên hosting. Gói không chứa thông tin đăng nhập, database thật, kết quả test hoặc script chẩn đoán công khai.

Không import lại `schema.sql` để cập nhật mã nguồn OOP. Cấu trúc OOP mới không yêu cầu đổi dữ liệu. Nếu hosting thiếu các bảng tính năng từ phiên bản trước, dùng màn hình kích hoạt tương ứng của quản trị viên sau khi sao lưu CSDL.
