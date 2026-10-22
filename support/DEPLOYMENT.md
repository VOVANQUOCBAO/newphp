# Triển khai demo trên hosting miễn phí

## Kiến trúc được chọn

Ứng dụng hiện dùng PHP thuần, PDO và cú pháp MySQL/MariaDB. Đường triển khai ít thay đổi nhất là **hosting PHP có MySQL tích hợp**, ví dụ InfinityFree cho bản demo. Dữ liệu nằm trong MySQL của cùng tài khoản hosting; host, tên database, username và password phải lấy trong control panel, không mặc định `localhost`.

Supabase Free cung cấp PostgreSQL, không phải MySQL. Không thể thay `DB_HOST` là chạy: schema, kiểu dữ liệu, truy vấn, transaction và các đoạn đặc thù MySQL hiện tại cần được chuyển đổi và kiểm thử riêng. Tài liệu Supabase nêu giới hạn dung lượng cơ sở dữ liệu Free 500 MB và có thể tạm dừng project Free không hoạt động trong một tuần; nếu chọn Supabase sau này cần làm một dự án migration riêng. Với PHP host chỉ có IPv4, Supabase khuyên dùng pooler theo session khi cần kết nối trực tiếp.

Gói hosting miễn phí phù hợp thử nghiệm/portfolio, không nên dùng làm cam kết dịch vụ cho cửa hàng thật hoặc lưu dữ liệu nhạy cảm: tài nguyên, khả dụng, hỗ trợ, email gửi đi và giới hạn vận hành phụ thuộc nhà cung cấp và có thể đổi. Xác minh các thông số hiện hành trong control panel/điều khoản trước khi chọn. SMTP đi ra ngoài và kết nối HTTPS từ PHP đến Google cần được thử trên đúng tài khoản hosting.

## Chuẩn bị hosting và database

1. Tạo website/domain trong hosting control panel, bật HTTPS và chọn PHP **8.1 trở lên**. Google API Client đã khóa trong Composer yêu cầu PHP 8.1+. Chỉ chọn InfinityFree nếu account/control panel hiện tại cho chọn PHP version tương thích; không suy ra tính năng/version từ tên gói miễn phí.
2. Tạo MySQL database/user bằng công cụ hosting. Ghi lại host, port, database name, username và password. Không tạo database bằng SQL `CREATE DATABASE` trên shared host nếu nhà cung cấp không cấp quyền đó.
3. Tạo database phù hợp trong phpMyAdmin, sau đó import `database/schema.sql`. Chỉ import `database/du_lieu_mau.sql` nếu đang tạo dữ liệu demo; không đưa dữ liệu mẫu/tài khoản thử vào website công khai.
4. Cấu hình ứng dụng bằng biến môi trường `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS` nếu host hỗ trợ. Nếu không, sao chép `support/config/database.example.php` thành `support/config/database.local.php` và điền thông tin do hosting cấp. Không commit hoặc chia sẻ file local này.
5. Tạo tài khoản Super Admin đầu tiên bằng một trong các cách sau:
   - Chạy `support/scripts/khoitao_csdl.php` bằng PHP CLI trỏ tới đúng database nếu hosting cấp CLI và quyền cần thiết. Script yêu cầu database tồn tại/được cấp quyền, tạo Admin và in mật khẩu ngẫu nhiên một lần; lưu mật khẩu đó an toàn.
   - Nếu không có CLI trên hosting, tạo hash bằng PHP CLI trên máy riêng (`password_hash($password, PASSWORD_DEFAULT)`), rồi dùng phpMyAdmin thêm tài khoản với `vai_tro='SuperAdmin'`, `trang_thai=1` và email riêng. Không gửi mật khẩu thô/hash qua chat, không dùng mật khẩu công khai/mặc định và không đưa script khởi tạo có quyền ghi/xóa lên website.
   - Không dùng email `admin@local.test`, mật khẩu mẫu, hoặc tài khoản nhân viên mẫu trên hosting. Vai trò `Admin` cũ vẫn được hiểu là Super Admin để tương thích; tài khoản mới nên dùng `SuperAdmin`.
   - Sau khi đăng nhập bằng Super Admin, tạo các tài khoản khác trong mục **Quản lý nhân viên**. Chọn đúng vai trò; nhân viên chỉ tạo được tài khoản cấp thấp hơn khi Super Admin thực hiện.
6. Chạy `composer --working-dir=support install --no-dev --optimize-autoloader` trên máy có PHP 8.1+; upload thư mục `support/vendor/` cùng ứng dụng. Nếu máy build chỉ có PHP 8.0 thì dùng Composer/PHP 8.1+ trong môi trường build khác, không bỏ qua kiểm tra platform cho bản phát hành.
7. Upload mã nguồn và `support/vendor/`; loại bỏ test, dump chứa dữ liệu riêng và `.env`. Chỉ upload file cấu hình local cần thiết khi host không hỗ trợ biến môi trường, bảo vệ chúng khỏi HTTP và không đưa chúng lên Git. Trỏ document root về thư mục ứng dụng nếu hosting cho phép. Dùng HTTPS và kiểm tra các URL/đường dẫn sau khi upload.

Nếu buộc phải đặt file cấu hình local dưới document root, giữ `support/config/.htaccess` để chặn truy cập HTTP trực tiếp; xác nhận host hỗ trợ quy tắc Apache này. Tốt hơn nữa, đặt cấu hình ở ngoài document root nếu hosting hỗ trợ và cập nhật đường dẫn cấu hình tương ứng.

Nếu database cũ đã có bảng `khachhang`, chạy migration `google_sub` một lần trước khi bật đăng nhập Google; script `support/scripts/khoitao_csdl.php` có thể cần quyền tạo database không có trên shared host. Trên phpMyAdmin, xem `database/schema.sql` để lấy định nghĩa cột/index cần bổ sung, kiểm tra cấu trúc trước khi chạy lệnh migration và sao lưu database trước mọi thay đổi.

## Bật Google Sign-In thật

1. Trong Google Cloud Console, tạo/chọn project và cấu hình Google Auth Platform (branding, audience và email hỗ trợ). Khi ứng dụng còn ở trạng thái Testing, thêm các tài khoản test. Việc publish/verification phụ thuộc cấu hình và chính sách Google hiện hành.
2. Tạo OAuth Client ID loại **Web application**. Thêm origin HTTPS chính xác của website vào **Authorized JavaScript origins**, ví dụ `https://shop.example.com`. Thêm `https://shop.example.com/controller/index.php?act=google_login` vào **Authorized redirect URIs** nếu Google Console chấp nhận chính xác URI callback dùng bởi trang; phải trùng URL `login_uri` thực tế, bao gồm scheme, host và path.
3. Điền `GOOGLE_CLIENT_ID` và `APP_URL` vào biến môi trường hosting. Nếu không có giao diện biến môi trường, sao chép `support/config/google.example.php` thành `support/config/google.local.php`, điền Client ID và URL HTTPS chuẩn, rồi giữ file này ngoài Git. Client ID dùng phía trình duyệt nên không phải secret; không cần/không được đặt client secret vào file này.
4. Truy cập trang đăng nhập/đăng ký từ domain HTTPS chuẩn và thử tài khoản Google đã được phép. Nút dùng chế độ **popup**: callback JavaScript nhận ID token và gửi form POST từ chính origin của website tới `index.php?act=google_login`. Server kiểm tra CSRF gắn với PHP session, xác minh chữ ký/audience/issuer/expiry qua Google API Client, kiểm tra nonce gắn với phiên và email đã xác minh, rồi mới tạo/liên kết khách hàng theo Google `sub`. CSRF/nonce hết hạn sau 15 phút và được xóa khi đăng nhập thành công. Luồng redirect cũ vẫn kiểm tra double-submit CSRF cookie để tương thích trang đã mở trước khi cập nhật.
5. Nếu callback báo lỗi, xem PHP error log của host và kiểm tra thời gian hệ thống, HTTPS, Client ID, callback URL, dependency `support/vendor/` và khả năng kết nối HTTPS outbound tới Google. Không bật chế độ bỏ qua xác minh token để “sửa” lỗi deploy.

Client ID phải được sao chép **đầy đủ**, bao gồm đuôi `.apps.googleusercontent.com`; không chỉ lấy phần số và chuỗi trước dấu chấm. Giá trị `client_id` phía PHP phải trùng với Web client ID trong Google Cloud. Khi gặp `google_error=1`, không kết luận do tên miền miễn phí: đây là thông báo chung cho lỗi CSRF, cấu hình, thư viện, xác minh token hoặc tài khoản. Kiểm tra cấu hình và PHP error log trước khi đổi domain/hosting.

Nếu redirect từ Google quay về với `google_error=request`, controller nhận GET thay vì POST: dữ liệu callback đã mất trước khi được xử lý. Không sửa bằng cách nhận email từ GET hoặc bỏ CSRF/xác minh chữ ký. Popup và form cùng origin giữ nguyên các kiểm tra xác thực. Các mã lỗi còn lại gồm `csrf`, `credential`, `dependency`, `connection`, `token`, `claims`, `account`, `database_schema`; URL chỉ chứa mã bước, không chứa token hay thông tin exception.

Đã xác nhận đăng nhập Google thành công trên `https://bpstore.gt.tc` ngày 21/10/2026 sau khi sửa Client ID thiếu đuôi và chuyển sang popup. Bộ kiểm thử local đạt 119 PASS, 0 FAIL, gồm kiểm tra CSRF Google theo phiên và callback bị mất POST.

## Sau khi upload: smoke test và vận hành

### Ma trận quyền quản trị

| Vai trò trong CSDL | Quyền |
| --- | --- |
| `SuperAdmin` (`Admin` cũ vẫn tương thích) | Toàn quyền; xem dữ liệu, quản lý đơn/khách, sản phẩm, khuyến mãi, cấu hình và quản lý tài khoản/role nhân viên. |
| `QuanLy` | Chỉ xem sản phẩm, đơn hàng, khách hàng; được đổi chủ đề/font/nền. Không sửa nghiệp vụ, không xem CSDL và không quản lý tài khoản. |
| `NhanVien` | Chỉ xem danh sách sản phẩm và đổi mật khẩu của chính mình. |

Không cần sửa schema: cột `nhanvien.vai_tro` đã là `VARCHAR(50)`. Khi phát hành tính năng phân quyền, upload các file `controller/controller.php`, `controller/index.php`, `controller/admin/index.php`, `model/nhanvien.php`, `view/admin/header_admin.php`, `view/admin/sanpham.php`, `view/admin/nhanvien.php`, `view/admin/donhang.php`, `view/admin/khachhang.php`, `view/admin/dangnhap_admin.php` và `view/admin/doimatkhau.php` cùng một đợt. Bản test tích hợp local và script CLI không cần upload.

- Tạo tài khoản Google mới, đăng nhập lại cùng tài khoản, thử một email đã có trong bảng khách hàng và một tài khoản bị khóa. Xác nhận chỉ có một khách hàng cho cùng `google_sub`.
- Tạo đơn demo và xác nhận sản phẩm/kho, đơn hàng, chi tiết, email nội bộ và lịch sử đơn thay đổi trong MySQL. Thử tài khoản khác để xác nhận không thể xem đơn/thư của người khác.
- Kiểm tra nút theo dõi chỉ nhận ID đơn hợp lệ; kiểm tra thêm/khóa nhân viên chỉ thao tác được bởi Admin và bằng POST.
- Kiểm tra đăng nhập Admin bằng tài khoản thật vừa tạo; đăng xuất, thử đăng nhập bằng tài khoản nhân viên và xác nhận bị từ chối. Nếu đổi vai trò hoặc khóa Admin trong CSDL, phiên Admin hiện tại phải bị thu hồi ở request kế tiếp.
- Các form Admin phải gửi CSRF token và chỉ nhận POST cho thao tác ghi. Mật khẩu plaintext/MD5 cũ được chuyển sang `password_hash()` sau lần đăng nhập thành công; yêu cầu người quản trị đổi sang mật khẩu mạnh trước khi vận hành công khai.
- Kiểm tra mail OTP thật. Nếu nhà cung cấp chặn SMTP, đăng ký mật khẩu sẽ báo gửi thất bại và OTP không được hiển thị; dùng Google Sign-In hoặc một dịch vụ email được hosting cho phép.
- Sao lưu SQL thường xuyên, thử khôi phục trên database thử, dùng mật khẩu duy nhất và không lưu dữ liệu thanh toán nhạy cảm. PayPal Checkout đã được tích hợp; xem [PAYPAL_SETUP.md](PAYPAL_SETUP.md) về migration, Live/Sandbox, đối soát và giới hạn webhook của InfinityFree.
- Chạy bộ test local trước mỗi lần phát hành: `php support/tests/test_luong.php`. Bộ này không thay thế thử nghiệm trên hosting thực tế.

## Giới hạn xác nhận

Google login và kết nối xác minh token đã được kiểm chứng trực tiếp trên `bpstore.gt.tc` ngày 21/10/2026 với tài khoản được chọn. SMTP, hiệu năng và khả năng khôi phục cần được kiểm chứng riêng; kết quả đăng nhập không thay thế các kiểm tra đó.

## Tài liệu tham chiếu

- [Google Identity Services: Integrate Sign in with Google](https://developers.google.com/identity/gsi/web/guides/integrate)
- [Google Identity Services: Verify the Google ID token](https://developers.google.com/identity/gsi/web/guides/verify-google-id-token)
- [Supabase pricing and Free plan limits](https://supabase.com/pricing)
- [Supabase: Connect to your database](https://supabase.com/docs/guides/database/connecting-to-postgres)
- [InfinityFree](https://www.infinityfree.com/) — xác minh PHP version, SSL, giới hạn, database và chính sách mail/outbound trong control panel/support hiện hành trước khi triển khai.
