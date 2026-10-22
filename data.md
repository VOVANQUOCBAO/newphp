# Ghi chú rà soát ban đầu (lịch sử)

Tài liệu này ghi nhận trạng thái trước các đợt sửa, không mô tả phiên bản hiện tại. Xem [báo cáo luồng dữ liệu](BAO_CAO_LUONG_DU_LIEU.md) và [hướng dẫn admin](HUONG_DAN_ADMIN.md) để dùng bản đã cập nhật.

Kết quả kiểm tra ban đầu
Mình đã rà luồng từ giao diện → controller → model → cơ sở dữ liệu, các form/nút chính và cấu hình triển khai. Chưa sửa code vì yêu cầu hiện tại là kiểm tra và lập hướng xử lý.

Luồng hiện tại là: trình duyệt gửi GET/POST → index.php chuyển yêu cầu đến controller user hoặc admin → controller gọi các model → csdl.php mở kết nối PDO tới MySQL. Giỏ hàng lưu trong PHP session; khách hàng, sản phẩm, đơn hàng, voucher, đánh giá và email lưu trong CSDL.

Đã chạy bộ kiểm thử tích hợp sẵn: 84 PASS, 0 FAIL. Test dùng CSDL thử riêng và xác nhận số lượng bản ghi trong CSDL ứng dụng không đổi. Đây là kết quả tốt cho logic PHP/MySQL được test, nhưng không chứng minh các trang demo, đăng nhập Google hay tích hợp cloud đã sẵn sàng.

Các luồng và nút
Luồng	Đánh giá
Trang chủ, tìm kiếm, lọc, phân trang, chi tiết sản phẩm	Dữ liệu sản phẩm chính được lấy từ CSDL. Một số thông tin phụ như thông số kỹ thuật vẫn là nội dung mẫu.
Thêm/sửa/xóa giỏ hàng	Backend có kiểm tra sản phẩm, số lượng và tồn kho; test đã chạy đạt. Nút xóa hiện gửi GET thay vì POST.
Đăng ký và đăng nhập bằng mật khẩu	Luồng cơ bản được test; mật khẩu mới được hash. Đăng nhập vẫn chấp nhận cả mật khẩu thường và MD5 để tương thích dữ liệu cũ, không nên giữ cách này khi chạy thật.
Đặt hàng	Backend tính lại giá, khuyến mãi, voucher và phí ship; tạo đơn, trừ kho/voucher trong transaction. Đây là phần có cơ chế giao dịch và xử lý cạnh tranh tương đối tốt.
Lịch sử đơn, theo dõi đơn, hộp thư	Giao diện đang hiển thị dữ liệu mẫu cố định, không dùng dữ liệu controller truyền vào. Các nút hủy/khiếu nại và link theo dõi mẫu vì thế không gắn với đơn thật. Xem lichsu_muahang.php, theodoi_donhang.php và hopthu.php.
Theo dõi đơn	Controller nhận ID số và kiểm tra chủ sở hữu; form lại gợi ý mã DH-... hoặc số điện thoại. Mã DH-... bị ép thành ID số 0, còn tra cứu bằng số điện thoại chưa được triển khai. Xem controller/index.php.
Quên mật khẩu / ghi nhớ đăng nhập	Chưa hoạt động thật: quên mật khẩu chỉ hiện alert; checkbox “Ghi nhớ đăng nhập” không có name và không được backend xử lý. Xem dangnhap.php.
Admin sản phẩm, danh mục, khách hàng, đánh giá, đơn hàng, voucher, khuyến mãi	Nhiều luồng gọi backend và CSDL; một số đã nằm trong kiểm thử. Tuy nhiên quyền, CSRF và cách gọi các thao tác cần siết trước khi public.
Admin nhân viên	Có lỗi chức năng rõ ràng: bảng nhân viên là dữ liệu cứng; form gửi hoten, password, chucvu trong khi model đọc ho_ten, mat_khau, vai_tro, nên thêm nhân viên sẽ báo thiếu thông tin. Nút mở khóa gửi status=unlock, còn controller đọc trang_thai và mặc định về 0, nên không mở khóa được. Xem view/admin/nhanvien.php, model/nhanvien.php và controller/admin/index.php.
Các mục chặn triển khai
P0 — Đăng nhập Google hiện không phải Google OAuth và không an toàn. Nút mở form nhập email/tên; backend nhận google_email, tìm hoặc tạo tài khoản rồi đặt session, không xác thực token/chữ ký từ Google. Người gửi request có thể mạo danh email của khách hàng khác. Tắt luồng này trước khi mở site cho người dùng thật, hoặc thay bằng Google Identity/OIDC có xác minh token phía server. Xem form đăng nhập, form đăng ký và route google_login.

P0 — Nhân viên đang được cấp quyền admin đầy đủ. Mọi nhân viên đăng nhập thành công đều được đưa vào session admin; requireAdmin() chỉ kiểm tra có ID, không kiểm tra vai_tro. Vì vậy tài khoản nhân viên thường cũng vào được khu vực quản trị. Xem controller/index.php và controller.php. Dữ liệu mẫu còn có nhân viên dùng mật khẩu password; không import tài khoản mẫu như vậy lên production. Xem database/README.md.

P0 — Supabase PostgreSQL không tương thích trực tiếp với code hiện tại. Ứng dụng dùng DSN mysql:, còn schema và truy vấn dựa trên cú pháp MySQL như AUTO_INCREMENT, backtick, ENUM, CURDATE(), SHOW COLUMNS và FOR UPDATE. Muốn dùng Supabase PostgreSQL phải chuyển driver/schema/truy vấn và kiểm thử lại; chỉ import file SQL hiện tại lên Supabase sẽ không chạy. Xem csdl.php, schema.sql và phần xem CSDL trong controller/admin/index.php.

P1 — Thao tác thay đổi dữ liệu thiếu bảo vệ CSRF và có route dùng GET. Các thao tác quản trị cần kiểm tra method nhất quán; một số route xử lý khóa nhân viên/khách, xóa voucher và đổi trạng thái đơn nhận được GET. Phần lớn form POST cũng chưa thấy token CSRF. Cookie session chưa được cấu hình rõ các thuộc tính Secure, HttpOnly, SameSite. Đây là phần cần xử lý trước khi dùng tài khoản và dữ liệu thật.

P1 — OTP chưa đảm bảo xác minh email. Khi SMTP lỗi, route đăng ký hiển thị OTP ngay trên trang; điều đó làm mất ý nghĩa xác minh email. Đăng ký cũng có nhánh trực tiếp không qua OTP. Cần xác định một chính sách nhất quán, giới hạn gửi/nhập lại mã và không trả mã qua trình duyệt. Xem controller/index.php.

P1 — Các trang lịch sử, theo dõi, hộp thư cần thay dữ liệu mẫu bằng dữ liệu thật. Controller đã truy vấn đơn hàng/email, nhưng template hiện không render các danh sách đó. Cần làm trước khi quảng bá các chức năng này.

Đường triển khai đề xuất
Chốt CSDL trước khi sửa: nếu giữ PHP hiện tại, hướng ít thay đổi nhất là dùng MySQL mà hosting hỗ trợ. Nếu bắt buộc dùng Supabase PostgreSQL, cần coi đó là một đợt chuyển đổi backend, không phải chỉ đổi cấu hình.
Chặn rủi ro tài khoản: thay Google giả bằng OAuth thật hoặc tắt; tách quyền Admin/Nhân viên; bỏ mật khẩu mẫu và fallback mật khẩu thường/MD5; thêm CSRF và thiết lập session an toàn.
Hoàn thiện nút và dữ liệu: sửa form quản lý nhân viên, làm danh sách nhân viên động, đưa lịch sử đơn/hộp thư/theo dõi sang dùng dữ liệu CSDL thật, sửa định dạng ID tra cứu.
Kiểm tra môi trường InfinityFree/Supabase: xác nhận PHP version, driver PDO cần thiết, kết nối CSDL từ hosting, TLS/outbound network và SMTP. Bộ kiểm thử hiện chỉ xác nhận PHP/MySQL local; chưa xác minh kết nối cloud, Gmail thật hay callback OAuth trên domain HTTPS.
Chạy lại kiểm thử và thử bằng trình duyệt: đặc biệt đăng ký/đăng nhập, mua hàng, hủy đơn, phân quyền nhân viên và lỗi khi dịch vụ email/CSDL không kết nối được.
Tài liệu cũng cần đồng bộ: README.md ghi mật khẩu Admin mặc định password, trong khi script khởi tạo tạo mật khẩu ngẫu nhiên; README ghi 82 test nhưng lần chạy này đạt 84.

## Cập nhật 2026-10-19

Phần trên là ghi chú đánh giá trước đó; các trạng thái bên dưới phản ánh lần kiểm tra mới nhất:

- Luồng Google trong mã nguồn hiện xác minh ID token phía máy chủ; vẫn cần xác nhận callback thật trên domain với Client ID đã cấu hình.
- Các thao tác Admin đã có kiểm tra CSRF/POST và phiên cookie có `Secure`, `HttpOnly`, `SameSite=Lax`. Vừa bổ sung việc đọc lại vai trò/trạng thái Admin từ CSDL ở mỗi request; nếu tài khoản bị hạ quyền hoặc khóa, phiên bị thu hồi ngay ở request kế tiếp.
- Phân quyền Admin ba cấp đã triển khai local: SuperAdmin có toàn quyền; QuanLy chỉ xem sản phẩm/đơn/khách và đổi giao diện; NhanVien chỉ xem sản phẩm và đổi mật khẩu. Thao tác backend cũng kiểm tra vai trò, không chỉ ẩn menu.
- Bộ test tích hợp hiện tại: **114 PASS, 0 FAIL** sau các thay đổi phân quyền; tất cả mới ở mã nguồn local, chưa được upload lên InfinityFree.
- `https://bpstore.gt.tc/db-check.php` hiện trả trang 404 của InfinityFree, tức file không truy cập công khai được tại URL đó. Chưa có quyền File Manager/FTP để xác nhận file vật lý đã bị xóa khỏi `htdocs`.
- Việc tiếp theo: upload đồng bộ các file phân quyền; vì bảng `nhanvien` trên hosting hiện trống, cần tạo SuperAdmin đầu tiên với email riêng và hash mật khẩu an toàn, rồi tạo Manager/Nhân viên từ trang quản lý nhân viên. Không gửi mật khẩu hoặc hash qua chat.
- Chưa triển khai giới hạn số lần thử mật khẩu hoặc MFA cho Admin; trước khi lưu dữ liệu khách hàng thật, cần bật biện pháp chống dò mật khẩu ở hosting nếu có, dùng mật khẩu duy nhất/mạnh và tiếp tục theo dõi truy cập.
