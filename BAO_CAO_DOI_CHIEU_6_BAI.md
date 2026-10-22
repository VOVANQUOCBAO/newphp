# Đối chiếu mã nguồn PHPWeb với 6 bài giảng

> **Đã triển khai lên bpstore.gt.tc ngày 21/10/2026:** catalog 12 sản phẩm mới, 4 voucher; giữ dữ liệu đơn/tài khoản và cấu hình Google/PayPal. Xem [hướng dẫn voucher và catalog](HUONG_DAN_VOUCHER_VA_CATALOG.md).

> **Cập nhật sau sửa ngày 21/10/2026:** Đã thực hiện sửa và kiểm tra luồng theo yêu cầu tại [BÁO CÁO LUỒNG DỮ LIỆU](BAO_CAO_LUONG_DU_LIEU.md). Kết quả mới: **158 kiểm tra luồng + 42 kiểm tra PayPal ngoại tuyến đạt; PHP lint 61 tệp đạt**. Cấu trúc CSDL local đã được bổ sung, các bài test dùng CSDL tạm và xác nhận dữ liệu ứng dụng không đổi. Các nhận xét/số dòng/kết quả 119 PASS bên dưới là **ảnh chụp khảo sát trước sửa**, không mô tả nguyên trạng hiện tại.

## Cập nhật các phát hiện F01–F14

| Mã | Hiện trạng sau sửa |
| --- | --- |
| F01 | Đã sửa tuyến mọi form Admin và action JS; kiểm tra HTTP → DB → trang khách |
| F02 | Đã escape tên tài khoản, ảnh, nội dung; chat hiển thị text; upload dùng tên ngẫu nhiên/MIME thực |
| F03 | Đã bỏ đồng bộ mật khẩu sang tài khoản khách admin cố định |
| F04 | JSON lọc thông tin nhạy cảm theo cơ chế chung; ca QuanLy xác minh không có hash mật khẩu |
| F05 | Đã có upload ảnh chính/album, xem trước, kiểm tra nội dung/kích thước và dọn file khi lưu lỗi |
| F06 | Nhận xét từ CSDL, chỉ khách đã nhận đúng sản phẩm; bỏ bình luận mẫu và sao trang sản phẩm |
| F07 | Đã bỏ fetch ngầm/thông báo thành công trước server; mỗi thao tác thêm giỏ một POST |
| F08 | Liên kết phân trang giữ tìm kiếm/sắp xếp/kích thước trang, có trang trước/sau |
| F09 | Đã bỏ lựa chọn ghi nhớ đăng nhập chưa triển khai; không tuyên bố có remember-me cookie |
| F10 | Đã gom truy vấn sản phẩm, thêm CHECK/unique hợp lệ; chưa phân trang toàn bộ Admin hoặc kiểm thử tải lớn |
| F11 | Đã thêm POST/CSRF khách và khóa đặt COD chống gửi lặp; giữ cổng PayPal/Google riêng |
| F12 | Chi tiết, banner nổi bật, sản phẩm liên quan và chat dự phòng lấy DB; thông tin chưa nhập được để trống/ẩn |
| F13 | Đã sửa giới hạn ký tự sản phẩm/danh mục/địa chỉ và thông báo kết quả, SMTP, lưu cấu hình; thêm bắt lỗi Admin |
| F14 | Đã cập nhật tài liệu OTP tự chọn, bỏ phần hướng dẫn DB sai trong UI, thống nhất đăng nhập và dọn script chi tiết |

Luồng hủy/trả được mở rộng có yêu cầu, ngày giao, nhận lại hàng, cách ly hàng lỗi, thu hồi điểm, hoàn lượt voucher, chứng từ hoàn COD và kiểm soát refund PayPal. Xem báo cáo mới để biết quy tắc, dữ liệu cũ còn thiếu và giới hạn dịch vụ ngoài thực tế. Đây không phải tuyên bố đã bổ sung mọi bài tập rời trong slide hoặc đã kiểm chứng thanh toán/email thật.

---

## Bản khảo sát sáu bài trước khi sửa


Ngày kiểm tra: **21/10/2026**, giờ Việt Nam. Mã nguồn được kiểm tra tại `D:/phpweb-main`.

**Kết luận: hệ thống đã áp dụng phần lớn kiến thức nền tảng, nhưng chưa triển khai đầy đủ mọi nội dung trong 6 bài và còn một số luồng làm chưa đúng.** Khoảng thiếu rõ nhất là upload file, cookie ghi nhớ đăng nhập, cách đưa dữ liệu đánh giá thật ra giao diện và việc tối ưu truy vấn/phân trang. Có lỗi điều hướng form Admin cần sửa trước khi bổ sung tính năng.

Không có căn cứ để chấm một tỷ lệ “đạt X%”: các slide gồm kiến thức, ví dụ và bài tập rời, không đưa ra thang điểm hay bộ yêu cầu nghiệm thu cho website bán hàng.

## 1. Phạm vi và cách đánh giá

Đã trích xuất nội dung **421 slide**, đồng thời xem trực tiếp các ảnh chứa ví dụ quan trọng về AJAX, upload, SMTP, class/OOP và phân trang:

| Tài liệu | Số slide | Nội dung chính |
| --- | ---: | --- |
| [Bài 1](<C:/Users/jaysm/Downloads/bai 1.pptx>) | 137 | HTML, CSS, layout, responsive, iframe, form |
| [Bài 2](<C:/Users/jaysm/Downloads/bai 2.pptx>) | 43 | Client/server, JavaScript, DOM, LocalStorage, API |
| [Bài 3](<C:/Users/jaysm/Downloads/bai 3.pptx>) | 137 | PHP cơ bản, biến, kiểu, điều khiển, hàm, GET/POST |
| [Bài 4](<C:/Users/jaysm/Downloads/bai 4.pptx>) | 36 | MVC, OOP, lớp kết nối CSDL, PDO/prepared statements |
| [Bài 5](<C:/Users/jaysm/Downloads/bài 5.pptx>) | 34 | CRUD, xử lý kết quả/lỗi, ràng buộc, tối ưu, phân trang |
| [Bài 6](<C:/Users/jaysm/Downloads/bài 6.pptx>) | 34 | AJAX, upload, PHPMailer, cookie, session |

Phạm vi code gồm điểm vào, cả hai khu vực controller, **12 model**, các view khách hàng/Admin, CSS/JavaScript, SQL, script khởi tạo/chạy local, bộ kiểm thử và cấu hình mẫu. Thư viện PHPMailer có kiểm tra sự tích hợp và cú pháp; đây không phải kiểm toán riêng toàn bộ mã thư viện bên thứ ba. Hai file ZIP trong thư mục gốc được xem là gói lưu trữ, không phải bản ứng dụng đang chạy. Ảnh/SVG trang trí không được xem là luồng nghiệp vụ.

Các nội dung trong slide được dùng làm tiêu chí đối chiếu. Ví dụ về tài khoản mẫu, câu lệnh cài thư viện, gửi email hoặc upload không được coi là chỉ thị phải thực thi. Không sửa code nghiệp vụ trong lần kiểm tra này.

Quy ước:

- **Đã có:** có đường đi thực tế trong mã nguồn, có bằng chứng cụ thể.
- **Một phần/có lỗi:** có triển khai nhưng thiếu mắt xích, khác dữ liệu thực hoặc còn lỗi.
- **Chưa có:** chưa tìm thấy phần triển khai tương ứng trong code ứng dụng.
- **Khác mẫu, hợp lý:** khác tên lớp/cách viết nhưng vẫn đạt mục đích kỹ thuật.
- **Ví dụ/bài tập riêng:** chưa có bản thực hành độc lập; không tự động xem là lỗi của cửa hàng.

## 2. Kết quả tổng quan theo từng bài

| Bài | Đánh giá | Điểm đã có | Phần thiếu hoặc cần sửa |
| --- | --- | --- | --- |
| 1 | Đã áp dụng nền tảng | HTML5, CSS ngoài, Bootstrap, Flexbox, responsive, form, animation | Admin còn hạn chế responsive; nhiều inline style; chưa có ví dụ iframe/float-clear/border-image riêng |
| 2 | Đã áp dụng, còn lỗi tương tác | PHP ở server; JS/DOM, LocalStorage, fetch, JSON | Handler thêm giỏ không phối hợp đúng với submit form và phản hồi server |
| 3 | Đã áp dụng kiến thức cốt lõi | Biến, mảng, ép kiểu, điều kiện, switch, vòng lặp, hàm, ngày/chuỗi, GET/POST | Không có tập bài thực hành độc lập như in số/normalize/login mẫu; không nên thêm máy móc vào website |
| 4 | Có MVC/OOP, chưa tách lớp hoàn toàn | Model kế thừa lớp CSDL, controller điều phối, view render dữ liệu | SQL nằm trong controller xem CSDL; view giỏ còn gọi model; chi tiết sản phẩm trộn dữ liệu mẫu |
| 5 | Có CRUD, bảo vệ truy vấn và phân trang cơ bản | PDO prepare, transaction, fetch/fetchAll, FK/UNIQUE, LIMIT/OFFSET | Form Admin mất tuyến; N+1, SELECT *, nhiều danh sách không phân trang; thiếu CHECK; xử lý lỗi chưa đều |
| 6 | Có AJAX/email/session; còn thiếu rõ | Chat fetch, PHPMailer SMTP, session user/Admin/giỏ, cookie phiên | Chưa upload ảnh; chưa cookie ghi nhớ đăng nhập; AJAX giỏ có hai cơ chế gửi đồng thời; email thật chưa xác minh |

## 3. Đối chiếu chi tiết

### Bài 1: HTML, CSS, layout và form

| Slide | Nội dung bài | Hiện trạng và bằng chứng | Kết luận |
| --- | --- | --- | --- |
| 4–6 | HTML, DOCTYPE, html/head/body | [header.php](D:/phpweb-main/view/header.php:55) có HTML5, `lang=vi`, UTF-8, viewport; [footer.php](D:/phpweb-main/view/footer.php:311) đóng tài liệu | Đã có |
| 13–21 | Inline/internal/external CSS | [header.php](D:/phpweb-main/view/header.php:64) nạp Bootstrap và hai file CSS; [header_admin.php](D:/phpweb-main/view/admin/header_admin.php:29) có internal CSS; các view dùng nhiều inline style | Đã có cả ba cách; nên gom phần lặp, không phải cứ inline là sai bài |
| 23–51 | ID, class, selector, pseudo-class/pseudo-element | `#heroCarousel`, `.product-card`, selector lồng nhau, hover, `::before` trong [theme.css](D:/phpweb-main/view/css/theme.css:129) | Đã áp dụng các nhóm chính; không cần dùng mọi selector ví dụ |
| 53–88 | Đơn vị, màu/font, box model, display, position, nền/viền/bóng | CSS và Bootstrap sử dụng px/rem/%, padding/margin, flex, relative/absolute/fixed/sticky, gradient, radius và shadow | Đã có |
| 90 | Border-image | Không tìm thấy `border-image` trong CSS tự viết | Chưa có ví dụ này; không phải chức năng bắt buộc |
| 93–96 | Float và clear | Không thấy khai báo `float`/`clear` trong CSS tự viết; layout dựa trên Flexbox/Bootstrap | Khác kỹ thuật bố cục, hợp lý; chỉ thiếu nếu phải nộp demo float riêng |
| 98–105 | Flexbox | [theme.css](D:/phpweb-main/view/css/theme.css:82), nhiều `d-flex`, `flex-column`, `justify-content-*`, `align-items-*` trong view | Đã có |
| 107–110 | CSS Grid | Có utility Bootstrap `d-grid`, ví dụ [sanpham.php](D:/phpweb-main/view/admin/sanpham.php:217); chưa có layout tự viết bằng `grid-template-columns/rows` | Có sử dụng gián tiếp; chưa có bài Grid riêng |
| 113–120 | Transition và animation/keyframes | [theme.css](D:/phpweb-main/view/css/theme.css:437) và [season_effects.css](D:/phpweb-main/view/css/season_effects.css:1) có transition/keyframes cho UI và bốn mùa | Đã có |
| 123–127 | Responsive/media queries | [theme.css](D:/phpweb-main/view/css/theme.css:108), Bootstrap `col-12 col-sm-6 col-md-4 col-lg-3`, navbar collapse; CSS mùa có breakpoint 576px | Đã có ở storefront; chưa xác nhận đầy đủ bằng kiểm tra màn hình thực |
| 123–127 | Hiển thị tốt trên màn hình nhỏ | Sidebar Admin cố định 260px và `flex-shrink-0`, layout hàng ngang trong [header_admin.php](D:/phpweb-main/view/admin/header_admin.php:30), chưa có cơ chế thu gọn riêng | Một phần; cần kiểm tra/bổ sung responsive Admin |
| 129–132 | iframe | Không có thẻ iframe trong view ứng dụng | Chưa áp dụng; chỉ là ví dụ kỹ thuật nếu đề không yêu cầu nhúng nội dung |
| 134–137 | Form/action/method, input/select/textarea/radio/checkbox | Đăng nhập/đăng ký/đơn hàng/Admin có các loại điều khiển này; form thực gửi GET/POST | Đã có; trường file upload chưa có, xem Bài 6 |

### Bài 2: Client/server và JavaScript

| Slide | Nội dung bài | Hiện trạng và bằng chứng | Kết luận |
| --- | --- | --- | --- |
| 5–12 | Client xử lý tương tác giao diện | Tăng/giảm số lượng, đổi ảnh, modal, toast và hiệu ứng mùa trong các view/[season_effects.js](D:/phpweb-main/view/js/season_effects.js:1) | Đã có |
| 14–22, 38–43 | Server nhận request, kiểm tra đăng nhập, truy vấn và trả HTML/JSON | [controller/index.php](D:/phpweb-main/controller/index.php:180), [controller.php](D:/phpweb-main/controller/controller.php:5), các model PDO | Đã có đúng sự phân chia cơ bản |
| 27–31 | Sự kiện, DOM, thêm/xóa/sửa nội dung | `addEventListener`, `createElement`, `appendChild`, `replaceChild`, `classList`, `textContent` trong [footer.php](D:/phpweb-main/view/footer.php:255) | Đã có |
| 28 | Kiểm tra dữ liệu đầu vào | Form có required/type/min/max; backend có filter, kiểm tra tồn kho, ngày tháng, số sao | Đã có nhiều lớp; validation độ dài chuỗi cần đồng bộ CSDL |
| 32 | LocalStorage | [footer.php](D:/phpweb-main/view/footer.php:204) lưu font/nền/mùa; [giaodien.php](D:/phpweb-main/view/admin/giaodien.php:326) ghi lựa chọn khi preview | Đã có; đây không phải cookie PHP |
| 33 | Gọi API từ client | [footer.php](D:/phpweb-main/view/footer.php:285) POST JSON tới route chatbot, parse JSON và cập nhật DOM | Đã có |
| 34 | Hiệu ứng động | JS tạo phần tử hiệu ứng, CSS keyframes điều khiển chuyển động | Đã có |
| 9, 36, 43 | Dữ liệu/logic quan trọng cần bảo vệ ở server; SQLi/XSS | Giá mua được tính lại từ DB trong [donhang.php](D:/phpweb-main/model/donhang.php:86); PDO prepare có dùng. Tuy nhiên tên user trên menu chưa escape và một số dữ liệu ảnh/HTML chat đi vào HTML chưa lọc phù hợp | Có bảo vệ nhưng chưa đầy đủ; xem phát hiện F02/F04 |

Không cần chuyển dự án sang React/Vue/Node/ASP chỉ vì các công nghệ đó được liệt kê. Chúng là ví dụ lựa chọn, không phải yêu cầu phải đồng thời có trong website.

### Bài 3: PHP cơ bản

| Slide | Nội dung bài | Hiện trạng và bằng chứng | Kết luận |
| --- | --- | --- | --- |
| 2–11 | PHP server, XAMPP, file .php, nhúng HTML | [setup.bat](D:/phpweb-main/setup.bat:1), [chay_local.ps1](D:/phpweb-main/support/scripts/chay_local.ps1:1), view PHP | Đã có; PHP built-in server local là lựa chọn hợp lý, không bắt buộc Apache cho mọi cách chạy |
| 12–22 | Comment, biến, gán, phạm vi/static | Có comment, biến cục bộ, thuộc tính; [csdl.php](D:/phpweb-main/model/csdl.php:6) giữ kết nối ở thuộc tính static | Đã áp dụng; thuộc tính static khác với bài demo biến static bên trong hàm |
| 23–29 | Xuất dữ liệu, định dạng, hằng | `echo`, `<?=...?>`, `number_format`, `define(BASE_PATH)` và hằng trạng thái trong [donhang.php](D:/phpweb-main/model/donhang.php:12) | Đã có chức năng tương đương; không bắt buộc dùng đủ print/printf/sprintf |
| 30–40 | Kiểu, array/object, ép kiểu | Mảng session, object model, khai báo type và ép `(int)/(float)/(string)` | Đã có |
| 41–49 | Toán tử, nối chuỗi, tham chiếu | Tính tiền/voucher; nối chuỗi; `foreach (... as &$sp)` và `unset($sp)` trong [controller/index.php](D:/phpweb-main/controller/index.php:428) | Đã có |
| 50–71 | if/elseif, switch, for/foreach/while, break/continue | Switch route; if nghiệp vụ; for/foreach duyệt; continue/break. Không có nhu cầu dùng mọi kiểu vòng lặp trong mỗi chức năng | Đã có các cấu trúc chính; thiếu demo do-while riêng không phải lỗi nghiệp vụ |
| 73–81 | Hàm, return, tham trị/tham biến, mặc định | Các method model, hàm JS/PHP; [sanpham.php](D:/phpweb-main/model/sanpham.php:81) nhận mảng bằng tham chiếu; nhiều tham số mặc định | Đã có |
| 82–89 | isset/empty/kiểm tra kiểu và số | [controller/index.php](D:/phpweb-main/controller/index.php:181), [xl_database.php](D:/phpweb-main/model/xl_database.php:77) | Đã có; dùng FILTER_VALIDATE_INT chặt hơn chỉ is_numeric cho số lượng |
| 90–94 | Hàm toán học | Dùng round/floor/min/max trong giá, điểm, giới hạn; random_int/random_bytes cho OTP/token | Đã có theo nhu cầu; pi/pow/sqrt và rand không cần xuất hiện nếu không có bài toán tương ứng |
| 95–113 | Hàm chuỗi | trim/ltrim/rtrim, strtolower/strtoupper, strlen, explode/implode, tìm chuỗi và preg_replace | Đã có; không cần đưa đủ hàm minh họa vào ứng dụng |
| 116–122 | Ngày và thời gian | date/time/strtotime, timezone Việt Nam; [xl_database.php](D:/phpweb-main/model/xl_database.php:90) kiểm tra ngày qua DateTimeImmutable | Đã có; không gọi checkdate không có nghĩa là thiếu kiểm tra ngày |
| 123–134 | Form và GET/POST | Tìm kiếm GET, login/đăng ký/checkout POST, xử lý qua superglobals | Đã có; một số thao tác thay đổi trạng thái còn nhận GET, cần sửa theo nguyên tắc an toàn |

Các bài tập rời chưa thấy có bản nộp độc lập trong repo:

- Slide 54: giảm tiền theo số lượng nhỏ hơn 10 / 10–20 / trên 20.
- Slide 56–57: đổi số thành chữ, lời chúc theo ngày.
- Slide 60–61, 66, 69: tính tổng, số lẻ, kiểm tra số nguyên tố.
- Slide 72: in 1–200, số chẵn đỏ/in đậm và số lẻ xanh/in nghiêng.
- Slide 76: hàm tính tổng hai số.
- Slide 114–115: hàm `normalize()` chuẩn hóa khoảng trắng giữa từ.
- Slide 135–137: login cố định `admin/12345`, lời chào Tahoma đỏ.

Nếu mục tiêu là nộp **từng bài thực hành**, đây là phần còn thiếu bằng chứng. Nếu mục tiêu là website bán hàng áp dụng kiến thức, không nên thay đăng nhập DB/hash hiện tại bằng tài khoản cứng chỉ để giống ví dụ.

### Bài 4: MVC, OOP và CSDL

| Slide | Nội dung bài | Hiện trạng và bằng chứng | Kết luận |
| --- | --- | --- | --- |
| 3–6, 8–9 | Model/View/Controller và index khởi động | [index.php](D:/phpweb-main/index.php:7) chọn khu; controller gọi model và `render`; view nhận data | Đã có cấu trúc MVC thực |
| 4 | Model chịu trách nhiệm thao tác DB | 12 model tách bảng/nghiệp vụ, kế thừa `xl_database` → `csdl` | Đã có phần lớn |
| 5 | Controller làm trung gian | User/Admin route xử lý request, kiểm tra quyền và gọi model | Đã có; controller User khá lớn, còn lẫn logic chat/dựng HTML |
| 6 | View nhận dữ liệu rồi hiển thị | Các trang lịch sử/theo dõi/hộp thư hiện dùng data thật. [giohang.php](D:/phpweb-main/view/giohang.php:34) vẫn có fallback tự tạo model/query; [header.php](D:/phpweb-main/view/header.php:14) tự đọc file cấu hình | Chưa tách hoàn toàn |
| 4–6 | Truy vấn nên ở Model | Route xem bảng dữ liệu gọi PDO/SHOW COLUMNS/SELECT ngay trong [controller/admin/index.php](D:/phpweb-main/controller/admin/index.php:340) | Làm khác phân lớp trong bài; nên chuyển sang model/service dữ liệu |
| 11–18 | Class, thuộc tính, method, object, $this | [csdl.php](D:/phpweb-main/model/csdl.php:3), [xl_database.php](D:/phpweb-main/model/xl_database.php:5) và các model có class, kế thừa, public/protected/private và $this | Đã có OOP |
| 17, 22 | Ví dụ Person/Student có private field/getter | Model hiện hoạt động theo kiểu lớp truy vấn, dữ liệu trả mảng; không dựng entity sản phẩm với đủ getter/setter như ảnh mẫu | Khác mẫu; vẫn là OOP. Chỉ thiếu nếu tiêu chí chấm yêu cầu đúng kiểu entity đó |
| 23–28 | Tái sử dụng kết nối, prepared statement, xử lý lỗi | [csdl.php](D:/phpweb-main/model/csdl.php:43) dùng kết nối chung; [xl_database.php](D:/phpweb-main/model/xl_database.php:7) prepare/execute tập trung | Đã có; xử lý exception Admin chưa nhất quán |
| 30–34 | MySQLi hoặc PDO | [csdl.php](D:/phpweb-main/model/csdl.php:68) dùng PDO MySQL, ERRMODE_EXCEPTION, fetch assoc, tắt emulate prepares | Đã có PDO; không cần cài thêm MySQLi vào luồng ứng dụng |
| 36 | database, xl_data, sanpham, controller, view | Code dùng tên `csdl`, `xl_database`, `sanpham`, `controller` | Khác tên mẫu nhưng tương đương vai trò |

### Bài 5: CSDL, CRUD, bảo mật và phân trang

| Slide | Nội dung bài | Hiện trạng và bằng chứng | Kết luận |
| --- | --- | --- | --- |
| 3–4 | Cấu hình kết nối | File local/env, host/port/db/user/pass trong [csdl.php](D:/phpweb-main/model/csdl.php:13) | Đã có; không in giá trị cấu hình nhạy cảm trong báo cáo |
| 6–18 | SELECT/INSERT/UPDATE/DELETE | [xl_database.php](D:/phpweb-main/model/xl_database.php:7) có đủ; sản phẩm/danh mục có model CRUD và giao diện | Đã có ở backend; form sản phẩm/danh mục Admin bị lỗi định tuyến F01 |
| 8–10 | WHERE/ORDER BY/LIMIT/GROUP BY/AVG | Tìm sản phẩm, sắp xếp, tính sao, thống kê doanh thu trong model | Đã có; không bắt buộc dùng mọi mẫu employees/salary |
| 20–22 | Nhận và duyệt kết quả | fetchAll/fetch/fetchColumn, foreach ở view | Đã có; riêng đánh giá thật chưa được view chi tiết dùng |
| 25 | Đóng kết nối, tài nguyên | Có kết nối dùng chung; `resetKetNoi()` xóa tham chiếu static, không có luồng close riêng thường ngày | Khác mẫu, chưa phải bằng chứng rò rỉ; PHP đóng PDO khi script kết thúc, xem ghi chú kỹ thuật |
| 26 | PRIMARY KEY/FK/NOT NULL/UNIQUE/CHECK | [schema.sql](D:/phpweb-main/database/schema.sql:4) có bốn nhóm đầu trên nhiều bảng; không có CHECK cho tồn/giá/số sao/phần trăm | Một phần; kiểm tra ứng dụng chưa thay thế hoàn toàn ràng buộc DB |
| 26 | Bảo vệ tham chiếu khi xóa | Sản phẩm đã có giao dịch không được xóa; danh mục còn sản phẩm/khuyến mãi không được xóa; có transaction | Đã có và có test |
| 27 | Kiểm tra kết quả thao tác/ghi log/lỗi rõ | Sản phẩm, đơn có catch/rollback/log; `voucher::xoa` luôn trả ok sau delete; `guiQuaTang`/controller có thể báo đã gửi dù SMTP thất bại | Chưa đồng đều |
| 28–30 | Chống SQL injection | Query có input dùng prepare; sort theo whitelist; table ở trang xem CSDL theo whitelist | Đã có bảo vệ chính; không kết luận SQLi chỉ vì thấy nối tên bảng hợp lệ |
| 31 | Tránh SELECT * và tối ưu query | Nhiều `SELECT *`; bổ sung số bán/sao và khuyến mãi theo từng sản phẩm trong vòng lặp | Chưa làm tốt; có N+1 |
| 31 | INDEX phù hợp | Có index do PRIMARY/UNIQUE/FK; schema chưa có các index ghép phục vụ nhóm lọc/sort/status-date | Một phần; nên dùng EXPLAIN trước khi chọn index, chưa đo hiệu năng thực |
| 31–34 | LIMIT/OFFSET/phân trang | [sanpham.php](D:/phpweb-main/model/sanpham.php:32) và controller có trang, tổng, giới hạn; trang xem CSDL có 25 dòng/trang | Đã có |
| 31–34 | Giữ đúng tập dữ liệu giữa các trang | Link Xem thêm giữ q/danh mục nhưng mất sort và moi_trang; đổi sort cũng mất từ khóa | Có lỗi F08 |
| 31 | Không tải toàn bộ danh sách lớn | Đơn/khách/nhân viên/voucher/đánh giá Admin và lịch sử/hộp thư chưa có phân trang nghiệp vụ | Còn thiếu khi dữ liệu tăng |

### Bài 6: AJAX, upload, email, cookie và session

| Slide | Nội dung bài | Hiện trạng và bằng chứng | Kết luận |
| --- | --- | --- | --- |
| 3–10 | AJAX/fetch hoặc XMLHttpRequest, cập nhật DOM | Chat dùng fetch POST JSON, nhận kết quả và thay bubble trong [footer.php](D:/phpweb-main/view/footer.php:285) | Đã có kỹ thuật AJAX; không bắt buộc dùng XMLHttpRequest nếu dùng fetch |
| 9–10 | Ví dụ tìm danh mục khi keyup qua route timdm | Không có route/luồng tìm danh mục live giống ví dụ; tìm sản phẩm hiện submit trang | Chưa có đúng ví dụ này, nhưng hệ thống đã chứng minh AJAX ở chức năng khác |
| 3–10 | Giao tiếp bất đồng bộ đúng kết quả | Nút thêm giỏ ở chi tiết vừa có fetch vừa submit form; chưa parse/kiểm tra kết quả trước toast/badge | Một phần, có lỗi F07 |
| 12–13 | Chọn file, $_FILES, move_uploaded_file | Admin chỉ có input text `hinh`; controller nhận $_POST, không có xử lý $_FILES/move_uploaded_file | **Chưa triển khai upload ảnh như bài** |
| 15–16 | PHPMailer, Gmail SMTP, STARTTLS | [xuly_email.php](D:/phpweb-main/controller/xuly_email.php:7) có Composer hoặc thư viện bundled; [xuly_email.php](D:/phpweb-main/controller/xuly_email.php:107) cấu hình SMTP/587/TLS, gửi OTP/chào mừng/đơn | Đã có code; gửi email thật phụ thuộc cấu hình và chưa xác minh hộp thư thật |
| 18–23 | Tạo/đọc/xóa cookie, expiry | PHP có cookie phiên qua session, và đọc `g_csrf_token` cho Google. Không thấy ứng dụng tự gọi setcookie cho ghi nhớ username/theme/đăng nhập | Có cookie phiên; chưa có bài thực hành cookie riêng |
| 23 | Ghi nhớ đăng nhập | Checkbox rememberMe không có name; lifetime cookie phiên là 0; không có token lâu dài/backend remember | **UI có nhưng chức năng chưa có** |
| 24, 26–33 | Session login/cart/security | [controller/index.php](D:/phpweb-main/controller/index.php:10) session_start/cookie params; lưu user/Admin/giỏ; đổi ID sau login; token CSRF Admin | Đã có, nhiều hơn ví dụ cơ bản |
| 32 | unset/session_unset/session_destroy | Logout xóa user/Admin/giỏ và đổi session ID; không hủy toàn bộ dữ liệu session | Khác cách mẫu, vẫn đáp ứng logout các phần chính; không bắt buộc gọi đủ mọi hàm minh họa |

`enctype="multipart/form-data"` đang có ở form **sửa số lượng giỏ hàng** không chứng minh đã upload ảnh: form đó không có input file và backend không xử lý file.

## 4. Các phát hiện cần xử lý

Mức ưu tiên: **P1** ảnh hưởng chức năng chính/quyền hoặc dữ liệu nhạy cảm; **P2** thiếu tính năng, sai dữ liệu hiển thị hoặc vấn đề vận hành; **P3** chất lượng code/tài liệu/bài minh họa.

### F01 · P1 · Form sản phẩm/danh mục Admin mất khu vực quản trị

Bằng chứng: [sanpham.php](D:/phpweb-main/view/admin/sanpham.php:43), cùng các form tại dòng 155, 182, 203, 224 và JS tại dòng 255/264. Chúng dùng `index.php?act=...` mà không có `khu=admin`.

Khi mở theo URL trong README là `/index.php?khu=admin`, hoặc từ `/controller/index.php?khu=admin`, trình duyệt sẽ POST tới `index.php?act=themsanpham` thuộc điểm vào User. [index.php](D:/phpweb-main/index.php:7) chỉ chọn Admin nếu query có `khu=admin`; route User không có action quản lý sản phẩm/danh mục và trả lỗi action không rõ. Việc đang có session Admin không tự chọn lại khu.

Nếu mở trực tiếp `/controller/admin/index.php`, relative URL có thể vẫn vào đúng controller Admin. Điều này giải thích vì sao backend tồn tại và test vẫn PASS nhưng đường vào chính bị lỗi.

**Cần sửa:** dùng một hàm sinh URL Admin thống nhất, giữ `khu=admin` ở cả form và JS; kiểm tra submit qua URL thật trong README. Liên quan Bài 1 slide 135 và Bài 4 slide 5/9.

### F02 · P1 · Escape đầu ra chưa nhất quán

[menu.php](D:/phpweb-main/view/menu.php:85) xuất thẳng tên trong session. Tên lấy từ đăng ký/DB, vì vậy có thể được diễn giải thành HTML. Kiểm tra render cục bộ bằng tên chứa một thẻ `<b>` vô hại xác nhận HTML đó không bị escape.

Tên file ảnh cũng được nối trực tiếp vào thuộc tính src tại [trangchu.php](D:/phpweb-main/view/trangchu.php:46), [chitiet_sanpham.php](D:/phpweb-main/view/chitiet_sanpham.php:172), [giohang.php](D:/phpweb-main/view/giohang.php:101). Model ảnh mới kiểm tra độ dài, chưa ràng buộc tên/đường dẫn. [footer.php](D:/phpweb-main/view/footer.php:299) đưa phản hồi API chat vào innerHTML, nên cũng cần chính sách lọc HTML phù hợp.

**Cần sửa:** escape theo ngữ cảnh HTML/attribute; giới hạn tên ảnh; nếu chat hỗ trợ định dạng, sanitize danh sách thẻ cho phép. Liên quan cảnh báo XSS ở Bài 2 slide 43. Đây là kết luận từ code/render cục bộ, không phải kiểm thử khai thác trên website công khai.

### F03 · P1 · Đổi mật khẩu của một nhân viên có thể sửa tài khoản khách “admin”

[controller.php](D:/phpweb-main/controller/controller.php:145) cho cả QuanLy/NhanVien vào `doimatkhau`. Sau khi đổi đúng mật khẩu của actor, [controller/admin/index.php](D:/phpweb-main/controller/admin/index.php:409) tìm một khách hàng bằng username/email cố định của admin rồi cập nhật mật khẩu khách đó theo mật khẩu vừa nhập, không kiểm tra khách đó có thuộc actor hay không.

**Hệ quả có điều kiện:** nếu tồn tại bản ghi khách hàng admin tương ứng, nhân viên/Quản lý đổi mật khẩu cá nhân cũng làm đổi mật khẩu của khách admin. Đây không phải kết luận họ đổi được mật khẩu SuperAdmin trong bảng nhanvien.

**Cần sửa:** bỏ đồng bộ theo tên cố định; nếu cần liên kết hai tài khoản thì phải có khóa liên kết rõ ràng và kiểm tra chủ sở hữu. Đây là lỗi phân quyền bổ sung phát hiện khi rà code, không phải yêu cầu tính năng cụ thể của slide.

### F04 · P1 · JSON quản lý khách có thể chứa hash mật khẩu

[khachhang.php](D:/phpweb-main/model/khachhang.php:16) trả `kh.*`; danh sách vi phạm cũng SELECT *. [admin_controller.php](D:/phpweb-main/controller/admin/admin_controller.php:104) chuyển cả hai tập dữ liệu tới render. [controller.php](D:/phpweb-main/controller/controller.php:17) trả nguyên data khi `format=json`/`ajax=1`/Accept JSON.

Vì QuanLy được xem `khachhang`, phản hồi JSON của chức năng này có thể chứa `mat_khau`, dù bảng HTML không hiện cột đó. Trang `act=csdl` đã lọc cột nhạy cảm nhưng cơ chế này không áp dụng cho mọi route khác.

**Cần sửa:** chọn cột cần thiết hoặc loại hash/token trước khi dữ liệu rời model/service; không dùng việc ẩn cột trên HTML làm bảo vệ API. Chưa kết luận truy cập ẩn danh được dữ liệu này. Liên quan Bài 2 slide 9/43, Bài 4 slide 24 và Bài 5 slide 31.

### F05 · P2 · Chưa có upload ảnh

[sanpham.php](D:/phpweb-main/view/admin/sanpham.php:84) chỉ nhập tên tệp. Không tìm thấy luồng input file → $_FILES → kiểm tra file → move_uploaded_file → lưu tên mới.

**Cần bổ sung để bám Bài 6 slide 12–13:** form multipart có input file; kiểm tra upload error, kích thước và MIME thực; tên tệp sinh riêng; lưu vào thư mục ảnh; xử lý lỗi di chuyển và giữ ảnh cũ khi sửa thất bại. Ví dụ trong slide chỉ mô tả cơ chế cơ bản, cần bổ sung validation khi áp dụng.

### F06 · P2 · Đánh giá thật không xuất hiện ở trang chi tiết

Controller lấy `ds_danhgia` tại [controller/index.php](D:/phpweb-main/controller/index.php:471), nhưng view không duyệt danh sách đó. [chitiet_sanpham.php](D:/phpweb-main/view/chitiet_sanpham.php:313) cố định “4.9 / 5”, 56 đánh giá và hai bình luận tên Hoàng Quân/Trần Mai Anh.

Kiểm tra render với một đánh giá giả lập có dấu nhận diện cho kết quả: **đánh giá truyền vào không xuất hiện; bình luận mẫu vẫn xuất hiện**. Model/Admin có thể thêm/ẩn đánh giá đúng nhưng giao diện khách không phản ánh nội dung thật. Số sao trên phần đầu trang có đọc DB, làm hai khu vực trên cùng trang không nhất quán.

**Cần sửa:** render ds_danhgia; tính tổng/phân bố sao từ dữ liệu thật; bỏ nhãn “đã mua” nếu chưa xác minh mua hàng. Liên quan Bài 4 slide 6, Bài 5 slide 20–22.

### F07 · P2 · Nút thêm giỏ kích hoạt fetch và submit cùng lúc

Form chi tiết ở [chitiet_sanpham.php](D:/phpweb-main/view/chitiet_sanpham.php:242) submit GET với số lượng người dùng chọn. Button đồng thời có class `.btn-add-cart`. [footer.php](D:/phpweb-main/view/footer.php:165) bắt click, gửi fetch thêm cùng sản phẩm nhưng không gửi số lượng và không gọi preventDefault.

**Hệ quả:** một lần bấm có hai cơ chế gửi. Tùy thời điểm điều hướng, fetch có thể bị hủy hoặc được xử lý thêm 1 sản phẩm trước/sau request form. Toast/badge còn tăng trước khi server xác nhận, nên vẫn có thể báo thành công khi server từ chối do tồn kho. Rủi ro hai request được xác định từ code; chưa khẳng định mọi lần bấm trên mọi trình duyệt đều cộng đôi.

**Cần sửa:** chọn một cơ chế submit; nếu AJAX thì preventDefault, lấy đúng số lượng, chờ JSON thành công rồi cập nhật badge/toast từ giỏ server trả về. Liên quan Bài 2 slide 28 và Bài 6 slide 5.

### F08 · P2 · Phân trang làm mất sắp xếp và kích thước trang

[trangchu.php](D:/phpweb-main/view/trangchu.php:244) giữ từ khóa/danh mục nhưng không truyền `sort/sapxep` hoặc `moi_trang`. [controller/index.php](D:/phpweb-main/controller/index.php:420) vì thế quay về mặc định “mới” và 12 dòng.

Ví dụ: đang xem trang 1 với `sort=cu`, bấm Xem thêm thì trang 2 lại theo thứ tự mới. Nếu trang 1 dùng 24 dòng, trang 2 quay về 12 dòng còn làm thay đổi offset, có thể lặp/bỏ sót sản phẩm. Ô đổi sort ở dòng 157 cũng bỏ từ khóa tìm kiếm.

Link hiện chuyển sang trang mới, không nối thêm vào danh sách cũ và không có nút Trang trước. Đây là lựa chọn UX chưa hoàn chỉnh; riêng việc tải lại trang không vi phạm kỹ thuật phân trang trong slide. **Cần sửa:** xây query từ toàn bộ trạng thái bộ lọc, thêm điều hướng phù hợp. Liên quan Bài 5 slide 31–34.

### F09 · P2 · Ghi nhớ đăng nhập mới có giao diện

[dangnhap.php](D:/phpweb-main/view/dangnhap.php:96): checkbox không có name. Backend không đọc lựa chọn, không phát token nhớ đăng nhập, cookie session đặt lifetime 0.

**Cần sửa:** triển khai token ngẫu nhiên có thời hạn, lưu hash token ở server và cookie phù hợp, hỗ trợ thu hồi; hoặc bỏ lựa chọn nếu chưa triển khai. Không lưu mật khẩu thô trong cookie. Liên quan Bài 6 slide 18–23.

### F10 · P2 · Chưa theo đầy đủ hướng dẫn tối ưu dữ liệu

- Mỗi sản phẩm được gọi `giaSauKM()` một query, `boSungThongTinReal()` thêm hai query tính bán/sao. Controller lặp ở ba danh sách, có thể query lặp cho cùng sản phẩm. Đây là khoảng **3 query bổ sung cho mỗi lượt sản phẩm được render**, chưa tính query danh sách.
- [sanpham.php](D:/phpweb-main/model/sanpham.php:7), [donhang.php](D:/phpweb-main/model/donhang.php:35), [khachhang.php](D:/phpweb-main/model/khachhang.php:13) và các danh sách Admin khác chưa giới hạn trang.
- Schema thiếu CHECK để bảo vệ giá/tồn/số sao/phần trăm khi ghi trực tiếp hoặc có code khác ghi DB. Không phải “không có index”: PK/UNIQUE/FK đã tạo nhiều index cơ bản.

**Cần sửa:** aggregate theo nhóm hoặc batch ID, chọn cột cần dùng, phân trang danh sách, thiết kế CHECK/index theo nghiệp vụ và EXPLAIN. Không thêm index bừa vào mọi cột. Chưa có đo tải/benchmark để khẳng định thời gian đáp ứng. Liên quan Bài 5 slide 26/31.

### F11 · P2 · POST/CSRF chưa phủ hết khu vực khách

Admin đã có cổng POST và CSRF cho mutation. Phía User, thêm/sửa/xóa giỏ và hủy đơn còn nhận GET; checkout/đánh giá/khiếu nại chưa xác minh CSRF tương ứng. Đăng nhập thường ở route User cũng không dùng cổng CSRF của login Admin.

**Cần sửa:** quy định POST cho ghi dữ liệu, token CSRF cho phiên khách và kiểm tra tại backend. SameSite=Lax không thay thế toàn bộ việc này, đặc biệt khi request GET đang thay đổi trạng thái. Liên quan ứng dụng session chống spam/bảo mật ở Bài 6 slide 33; đây là phần kiểm tra an toàn bổ sung, không khẳng định slide quy định chi tiết từng route.

### F12 · P2 · Dữ liệu mẫu và dữ liệu thật còn trộn

[chitiet_sanpham.php](D:/phpweb-main/view/chitiet_sanpham.php:135) bắt đầu từ sản phẩm mẫu theo ID, mặc định Samsung nếu ID chưa có trong mảng, rồi ghi đè một số trường DB. Brand/specs/discount không được ghi đè tương ứng. Có giá 512GB/1TB và “Tiết kiệm 5.000.000đ” cố định nhưng khi thêm giỏ không gửi một biến thể sản phẩm tương ứng.

Banner và chatbot fallback cũng dùng ID/tên/giá viết cứng. Vì vậy sản phẩm mới hoặc DB khác dữ liệu mẫu có thể hiển thị sai thương hiệu/thông số/khuyến mãi hay dẫn nhầm sản phẩm. Hệ thống không hoàn toàn là dữ liệu giả: tên/giá/kho/đơn hàng chính có đọc DB; vấn đề là một số trường vẫn bị gắn dữ liệu mẫu.

**Cần sửa:** mô hình hóa thuộc tính/biến thể hoặc ẩn phần chưa có dữ liệu; tính giá giảm và tổng quan đánh giá thực; tìm sản phẩm theo dữ liệu thực trong chat/banner. Liên quan Bài 4 slide 6 và Bài 5 xử lý kết quả.

### F13 · P2 · Thông báo kết quả và validation chưa đồng bộ

- [voucher.php](D:/phpweb-main/model/voucher.php:59) trả thành công dù không có bản ghi bị xóa; khóa khách/ẩn đánh giá cũng chưa xác nhận tồn tại/row count đầy đủ.
- [admin_controller.php](D:/phpweb-main/controller/admin/admin_controller.php:118) trả top-level ok cho gửi quà, trong khi kết quả SMTP bên trong có thể thất bại; route báo “đã gửi” theo số khách tìm được.
- [admin_controller.php](D:/phpweb-main/controller/admin/admin_controller.php:227) ghi theme.json nhưng không kiểm tra file_put_contents trước khi báo thành công.
- [sanpham.php](D:/phpweb-main/model/sanpham.php:335) dùng ngưỡng byte 200 cho mã VARCHAR(50), ngưỡng 1020 cho tên VARCHAR(255). Ví dụ chuỗi ASCII 51 ký tự mã hàng vượt cột nhưng vẫn qua kiểm tra trước INSERT. Địa chỉ cũng cho phép tới 1000 byte trong khi cột VARCHAR(255). Không phải mọi lỗi này làm mất dữ liệu, nhưng thường đẩy lỗi sang DB và thông báo không sát nguyên nhân.
- Admin router chưa có khối bắt lỗi tổng tương đương User; lỗi CSDL bất ngờ ở một số model có thể thoát ra thay vì trả thông báo đã chuẩn hóa.

**Cần sửa:** kiểm tra kết quả thật, tách “lưu thư trong app” và “SMTP đã gửi”, dùng độ dài ký tự phù hợp cột, xử lý exception nhất quán. Liên quan Bài 5 slide 26–27.

### F14 · P3 · Tài liệu và ví dụ cần phân biệt rõ

[support/README.md](D:/phpweb-main/support/README.md:74) ghi đăng ký mật khẩu yêu cầu gửi OTP thành công, nhưng [dangky.php](D:/phpweb-main/view/dangky.php:191) có nút đăng ký trực tiếp và controller có nhánh tạo tài khoản một bước. OTP hiện là lựa chọn thêm, không phải điều kiện bắt buộc. Slide không buộc phải dùng OTP, nên đây là bất nhất tài liệu/chính sách hiện tại, không phải tự động kết luận sai Bài 6.

Quên mật khẩu mới có alert liên hệ hỗ trợ. Chức năng này không được 6 bài quy định thành một yêu cầu cụ thể, nhưng không nên hiểu UI hiện tại là đã có quy trình reset mật khẩu.

Hai đoạn JS `changeDetailImage/decQty/incQty` bị lặp trong view chi tiết. Một số trang Admin chưa đóng đầy đủ wrapper tài liệu như trang sản phẩm. CSS có tên keyframe pulse lặp. Các điểm này nên dọn sau khi sửa luồng và dữ liệu chính.

## 5. Những phần đã có và không nên đánh giá thiếu theo ghi chú cũ

Các kết luận sau phản ánh **code đang có**, không lấy nguyên nhận xét cũ trong data.md:

- Lịch sử mua hàng, theo dõi và hộp thư hiện đã render dữ liệu DB và kiểm tra chủ sở hữu.
- Nhân viên đã có danh sách động, form tên trường phù hợp, khóa/mở khóa bằng POST và ba cấp quyền được chặn ở backend.
- Google login có code xác minh ID token/verified email, CSRF và nonce; không còn chỉ tin email gửi lên.
- Admin có CSRF/kiểm tra POST, đọc lại trạng thái/vai trò từ DB ở mỗi request và không chỉ ẩn menu.
- Tài khoản mới dùng password_hash; dữ liệu mật khẩu cũ có cơ chế nâng cấp sau đăng nhập hợp lệ.
- Checkout tính lại giá/KM/voucher/phí ship ở server; trừ tồn/voucher trong transaction; hủy/bom/trả hoàn kho; chuyển trạng thái kiểm soát, cộng điểm đúng một lần. Bộ test có cả ca cạnh tranh và rollback.

Google/AI/thanh toán qua cổng không phải yêu cầu bắt buộc trong sáu file. Google có code tích hợp nhưng checkout hiện không có `support/vendor/autoload.php`; các file cấu hình Google/SMTP/chatbot local cũng không hiện diện lúc kiểm tra. Cấu hình có thể lấy qua biến môi trường, vì vậy không suy ra chắc chắn dịch vụ đã tắt chỉ từ việc không có file local. Riêng thiếu vendor sẽ cần xử lý trước khi luồng xác minh Google chạy được. Không gửi email thật hoặc thử đăng nhập Google thật trong lần kiểm tra này.

Trong lúc rà soát, [DEPLOYMENT.md](D:/phpweb-main/support/DEPLOYMENT.md:41) được cập nhật, ghi nhận Google login đã được xác nhận trên `bpstore.gt.tc` ngày 21/10/2026. Đây là ghi nhận của tài liệu triển khai, khác với tình trạng dependency của checkout local. Báo cáo này không kiểm thử lại đăng nhập trên hosting và không phủ nhận kết quả đó. Không thấy file code ứng dụng thay đổi so với bản chụp dùng để đối chiếu sau khi đã truy cập đủ sáu bài.

## 6. Kết quả kiểm tra và giới hạn

| Kiểm tra | Kết quả | Ý nghĩa |
| --- | --- | --- |
| PHP lint bằng PHP XAMPP 8.2.12 | **55/55 file PASS** | Không phát hiện lỗi cú pháp PHP |
| Bộ support/tests/test_luong.php | **119 PASS, 0 FAIL** | Kiểm tra Model/HTTP, quyền, giỏ, đơn, tồn, voucher, rollback, một số HTML |
| An toàn dữ liệu khi chạy test | CSDL thử ngẫu nhiên riêng; kiểm tra cuối xác nhận số bản ghi DB ứng dụng không đổi | Test không chạy thao tác nghiệp vụ trên DB ứng dụng; đây là kiểm tra số bản ghi, không phải so sánh mọi giá trị từng ô |
| Render tên user chứa thẻ HTML vô hại | Thẻ được xuất nguyên | Xác nhận thiếu escape tại menu |
| Render chi tiết với đánh giá có dấu nhận diện | Review thật không hiển thị, review mẫu vẫn hiện | Xác nhận đứt đoạn Controller → View ở nội dung đánh giá |
| Phân giải action form Admin theo URL trình duyệt | Mất khu=admin ở hai đường vào chính | Xác nhận điều kiện gây lỗi tuyến form |
| Google/SMTP thật, upload, tương tác mọi trình duyệt/độ rộng màn hình | Chưa xác minh end-to-end | Không dùng kết quả test PHP làm bảo đảm các phần này hoạt động |

Kết quả integration gốc được lưu tại [ketqua_test.json](D:/phpweb-main/support/tests/ketqua_test.json:1), thời gian `2026-10-21T18:09:10+07:00`. Những log “Test rollback” xuất hiện trong quá trình chạy là tình huống test cố ý gây lỗi rồi hoàn tác.

Bộ test tự dựng request Admin với đúng khu và kiểm tra dữ liệu JSON, nên **không chứng minh action của các form HTML đúng**. Tương tự, ca kiểm tra danhgia JSON đã đạt nhưng view vẫn có thể hiển thị nội dung mẫu. Vì vậy 119 PASS không mâu thuẫn với các phát hiện bên trên.

## 7. Thứ tự hoàn thiện đề xuất

1. **Sửa lỗi đang làm hỏng luồng/quyền:** F01 tuyến form Admin; F02 escape; F03 đồng bộ mật khẩu sai actor; F04 lọc dữ liệu nhạy cảm khỏi JSON.
2. **Hoàn thiện nội dung đã có UI:** F06 đánh giá thật; F07 một request thêm giỏ; F08 giữ filter/sort/page size; F12 dữ liệu mẫu/biến thể; F11 POST/CSRF khách.
3. **Bổ sung phần thiếu trực tiếp của bài học:** F05 upload ảnh, F09 cookie ghi nhớ đăng nhập nếu muốn giữ lựa chọn đó; xác minh SMTP sau khi cấu hình riêng.
4. **Hoàn thiện Bài 5:** batch query, phân trang Admin/lịch sử/hộp thư, CHECK/index phù hợp, thông báo lỗi/kết quả chính xác.
5. **Dọn cấu trúc và hồ sơ nộp bài:** đưa SQL khỏi controller, bỏ query fallback trong view, gom CSS/JS lặp, kiểm tra mobile Admin, cập nhật tài liệu. Nếu giảng viên yêu cầu bài tập từng slide thì tách thư mục bài thực hành; không chèn mọi ví dụ vào giao diện bán hàng.

## 8. Một số chi tiết trong slide không nên sao chép nguyên xi

Đây là đối chiếu với tài liệu học, không phải xác nhận mọi đoạn ví dụ đều hoàn chỉnh cho PHP đang dùng:

- Bài 3 slide 18/20 viết `$_GLOBALS`; tên biến dựng sẵn đúng là `$GLOBALS`. [PHP manual: $GLOBALS](https://www.php.net/manual/en/reserved.variables.globals.php).
- Bài 3 slide 107 xếp strnatcmp vào nhóm không phân biệt hoa/thường; hàm này thực tế có phân biệt hoa/thường. [PHP manual: strnatcmp](https://www.php.net/manual/en/function.strnatcmp.php).
- Ví dụ normalize ở slide 115 trộn tham số `$st` với `$str` và dùng kiểm tra vị trí chưa xử lý rõ vị trí 0. Không dùng nguyên đoạn mẫu làm tiêu chuẩn đúng/sai của ứng dụng.
- Ví dụ AJAX Bài 6 slide 9–10 mới thể hiện gửi request/nhận tham số; ảnh mẫu chưa có đầy đủ phần nhận phản hồi và render kết quả tìm kiếm. Luồng chat fetch/JSON trong repo là bằng chứng áp dụng AJAX rõ hơn, dù handler giỏ cần sửa.
- Bài 5 slide 25 nêu cần đóng kết nối; với PDO không persistent, PHP tự đóng khi script kết thúc. Việc chưa có hàm close riêng không đủ để khẳng định rò rỉ tài nguyên. [PHP manual: quản lý kết nối PDO](https://www.php.net/manual/en/pdo.connections.php).
- Tài khoản mẫu cố định, thông tin SMTP và cách ghép tên file upload trong ảnh là ví dụ giảng dạy. Không đưa nguyên thông tin đó vào cấu hình chạy thật.

## 9. Bản đồ mã nguồn để đối chiếu lại

| Nhóm | Tệp/đường dẫn | Vai trò |
| --- | --- | --- |
| Điểm vào | [index.php](D:/phpweb-main/index.php:1) | Chọn User/Admin, bảo toàn POST, redirect GET |
| User | [controller/index.php](D:/phpweb-main/controller/index.php:1) | Tài khoản, Google/OTP, sản phẩm, giỏ, đặt hàng, lịch sử, thư, chatbot |
| Dùng chung | [controller.php](D:/phpweb-main/controller/controller.php:1) | Render HTML/JSON, redirect, session user/Admin, quyền, CSRF |
| Giỏ/email | [giohang.php](D:/phpweb-main/controller/giohang.php:1), [xuly_email.php](D:/phpweb-main/controller/xuly_email.php:1) | Session giỏ, tra giá/kho; gửi và lưu email |
| Admin | [index.php](D:/phpweb-main/controller/admin/index.php:1), [admin_controller.php](D:/phpweb-main/controller/admin/admin_controller.php:1) | Route và điều phối quản trị |
| CSDL dùng chung | [csdl.php](D:/phpweb-main/model/csdl.php:1), [xl_database.php](D:/phpweb-main/model/xl_database.php:1) | PDO, prepare/CRUD, transaction, validation, nâng cấp hash |
| Hàng hóa | [sanpham.php](D:/phpweb-main/model/sanpham.php:1), [danhmuc.php](D:/phpweb-main/model/danhmuc.php:1), [khuyenmai.php](D:/phpweb-main/model/khuyenmai.php:1), [voucher.php](D:/phpweb-main/model/voucher.php:1) | Sản phẩm/danh mục/kho/giảm giá |
| Tài khoản | [khachhang.php](D:/phpweb-main/model/khachhang.php:1), [nhanvien.php](D:/phpweb-main/model/nhanvien.php:1) | Đăng ký/login/quyền/điểm/hạng/trạng thái |
| Đơn/thư/đánh giá | [donhang.php](D:/phpweb-main/model/donhang.php:1), [chitietdonhang.php](D:/phpweb-main/model/chitietdonhang.php:1), [email.php](D:/phpweb-main/model/email.php:1), [danhgia.php](D:/phpweb-main/model/danhgia.php:1) | Giao dịch, chi tiết, thư và review |
| View khách | [view](D:/phpweb-main/view) | header/menu/footer, trangchu, chitiet_sanpham, dangky/dangnhap, giohang/thanhtoan, lichsu_muahang/theodoi_donhang/hopthu |
| View Admin | [view/admin](D:/phpweb-main/view/admin) | header/login, sản phẩm, nhân viên, khách, review, đơn, doanh thu, voucher/KM, giao diện, mật khẩu, xem DB |
| Giao diện | [theme.css](D:/phpweb-main/view/css/theme.css:1), [season_effects.css](D:/phpweb-main/view/css/season_effects.css:1), [season_effects.js](D:/phpweb-main/view/js/season_effects.js:1) | CSS, responsive, keyframes, tạo DOM hiệu ứng mùa |
| SQL | [schema.sql](D:/phpweb-main/database/schema.sql:1), [webbanhang_mvc.sql](D:/phpweb-main/database/webbanhang_mvc.sql:1), [du_lieu_mau.sql](D:/phpweb-main/database/du_lieu_mau.sql:1) | Schema/import/seed |
| Khởi tạo | [khoitao_csdl.php](D:/phpweb-main/support/scripts/khoitao_csdl.php:1), [chay_local.ps1](D:/phpweb-main/support/scripts/chay_local.ps1:1), [setup.bat](D:/phpweb-main/setup.bat:1) | Tạo bảng/cột còn thiếu, tài khoản đầu tiên, chạy PHP/MySQL |
| Kiểm thử | [test_luong.php](D:/phpweb-main/support/tests/test_luong.php:1), [test_worker.php](D:/phpweb-main/support/tests/test_worker.php:1) | Integration HTTP/DB và cạnh tranh |
| Phụ thuộc/cấu hình | [composer.json](D:/phpweb-main/support/composer.json:1), [support/config](D:/phpweb-main/support/config) | Thư viện Google/PHPMailer, cấu hình mẫu và theme |

Kết luận nghiệm thu phù hợp là: **đã có website MVC/PDO áp dụng phần cốt lõi của cả sáu bài; chưa thể nói đã làm đầy đủ và đúng mọi nội dung.** Cần ưu tiên các lỗi luồng/dữ liệu thực trước, rồi bổ sung upload và cookie, sau đó hoàn thiện tối ưu/phân lớp và các demo riêng nếu đó là yêu cầu nộp bài.

### Cập nhật kiểm chứng ngày 22/10/2026

Đã sửa và triển khai lỗi hosting trả 403 khi gửi form thêm/sửa sản phẩm dạng multipart. Form mới gửi dữ liệu URL-encoded và tải từng ảnh qua endpoint có kiểm tra phiên admin/quyền/CSRF/MIME/dung lượng. Luồng thêm mới → CSDL → HTML khách, ảnh chính/album, ảnh tối đa 5 MB và các trường hợp từ chối đã đạt trong **168 kiểm tra, 0 lỗi** trên CSDL tạm. Trên hosting đã gửi được form thêm bằng mã trùng (không tạo dữ liệu thử), cập nhật ảnh sản phẩm có sẵn và xác nhận ảnh hiển thị bên khách; sau thử đã khôi phục các trường gốc. Chi tiết và giới hạn kiểm tra ở [báo cáo luồng](BAO_CAO_LUONG_DU_LIEU.md).

Đợt bổ sung QR/voucher sau đó: **206 kiểm tra luồng và 43 kiểm tra PayPal ngoại tuyến đạt**. Đã tách xem tổng/voucher khỏi tạo đơn, sửa lỗi JSON khi chọn PayPal, thêm chuyển khoản QR do admin xác nhận và bảng giao dịch có chứng từ/người/thời gian. Trên hosting đã áp SHIP0, tạo rồi hủy đơn thử #11, xác minh QR giải mã đúng, kho và lượt voucher hoàn đủ. Giữ lịch sử đơn và cấu hình dịch vụ cũ; không thực hiện giao dịch tiền thật. Xem [hướng dẫn QR/voucher](HUONG_DAN_QR_VA_VOUCHER.md).

Đợt bổ sung phạm vi voucher: **232 kiểm tra luồng và 43 PayPal ngoại tuyến đạt**. Admin chọn toàn cửa hàng, một danh mục hoặc một sản phẩm; tạo/sửa phạm vi lưu CSDL, checkout chỉ giảm phần đủ điều kiện và chụp phạm vi vào đơn. Mã cũ/đơn cũ giữ nguyên khi migrate; IPHONE18 để chủ shop tự chọn, không tự suy luận theo tên mã. Đã triển khai, kiểm chứng hash và kiểm tra giao diện chọn danh mục/sản phẩm trên hosting.

## Kiểm tra tiền giảm và hoàn lượt voucher ngày 22/10/2026

- Phát hiện mã 99K có loại giảm tiền nhưng giá trị 0đ. Đã sửa thành 99.000đ theo xác nhận của chủ shop; giữ nguyên phạm vi/ngày/hạng và không tính lại đơn đã lưu.
- Chặn tạo/sửa hoặc dùng mã giảm tiền/phần trăm giá trị 0; phần trăm phải trong (0,100]. Riêng miễn ship dùng giá trị 0. Admin có thể sửa giá trị cùng phạm vi, không đặt lại số lượt.
- Bấm Áp dụng chỉ xem trước, chưa trừ lượt; lưu đơn thành công mới giữ một lượt. Giao dịch lỗi rollback cả kho và voucher. Hủy hợp lệ hoàn đúng một lần; hủy lặp không tăng thêm.
- Kiểm thử trên CSDL tạm: 252 PASS, 0 FAIL; toàn bộ giá trị và số bản ghi của CSDL ứng dụng giữ nguyên sau test.
- Hosting: đơn thử #19 có tiền hàng 100.000đ, giảm 99.000đ, phí ship 30.000đ, tổng DB và QR 31.000đ. Lượt 99K 98 → 97 → 98 sau hủy; tồn sản phẩm #18 48 → 47 → 48. Chưa chuyển tiền; đơn thử đã hủy, giữ bản ghi để đối soát. Các đơn khác không bị chỉnh sửa trong lần thử này.
- Đã triển khai và đọc lại hash bốn file runtime; các cấu hình CSDL, Google, PayPal, giao diện và QR giữ nguyên. Bằng chứng: D:/phpweb-hosting-sync/20261022-voucher-usage/live-verification.json và flow.log.

## Sắp xếp và rà soát khu vực quản trị ngày 22/10/2026

Đã cập nhật sidebar thành Bán hàng, Ưu đãi, Khách hàng và Quản trị; gom tra cứu CSDL/kiểm tra sai lệch trong Công cụ dữ liệu. Bỏ nút giao diện/xem shop trùng lặp, rút gọn tên, thêm mô tả và hướng dẫn theo từng trang. Chức năng vẫn hiển thị theo đúng quyền cũ.

- Sản phẩm: form tạo thu gọn; nhóm bán hàng/ảnh/nội dung; thêm mới chỉ nhập một số lượng. Sửa nội dung/nhập thêm không ghi đè tồn kiểm kê; kiểm kê giảm tồn là tác vụ chọn riêng. Làm mới xóa ID cũ.
- Ưu đãi: tách rõ giảm giá sẵn và voucher khách nhập. Form giảm giá chỉ có một phạm vi đang hoạt động; bảng dùng tên hàng/danh mục thay ID, có thời hạn và trạng thái, bấm Sửa được. Máy chủ chặn mức giảm 0, đối tượng ngừng bán và chương trình giống hệt đối tượng/mức giảm/thời hạn; chương trình trùng thời gian lấy mức cao nhất. Đối tượng không còn bán hiện Ngừng áp dụng.
- Đơn: không chọn sẵn hủy; lý do/nhận hàng/nhập lại kho chỉ hiện khi liên quan. Lựa chọn PayPal tuân theo điều kiện trạng thái thanh toán phía máy chủ.
- Nhận xét: bỏ thao tác gộp ẩn và khóa khách vì ít sao; cho phép hiển thị lại, vẫn kiểm tra điều kiện đã mua khi hiển thị công khai.
- Khách hàng: làm rõ tổng giá trị đơn đã tạo gồm cả đơn hủy/trả, không gọi là doanh thu. Thư sinh nhật thu gọn; chặn bỏ trống tất cả bộ lọc hoặc ngày không tồn tại, không gửi nhầm toàn bộ khách.
- Hệ thống: khắc phục GET trang đổi mật khẩu bị chặn 405; POST vẫn cần CSRF. Tra cứu dữ liệu dùng một bộ chọn bảng; thống kê giải thích kỳ nhóm dữ liệu. Sửa cấu trúc HTML và chuẩn hóa phần mở/đóng layout trên các trang.

Kiểm tra: 279 PASS, 0 FAIL và không cảnh báo trên CSDL tạm; toàn bộ giá trị/số bản ghi của CSDL ứng dụng được giữ sau test. Đã kiểm tra đọc 12 trang trên hosting, thử tìm kiếm 1/14 sản phẩm, nạp Sửa/Làm mới và đổi phạm vi khuyến mãi. Không gửi biểu mẫu nghiệp vụ, không tạo đơn/sản phẩm mới trên hosting trong lần chỉnh giao diện.

Triển khai 22 tệp runtime, có bản sao trước sửa và xác minh SHA-256; cấu hình CSDL, Google, PayPal, giao diện cửa hàng và tài khoản QR được bảo toàn. Kết quả tại D:/phpweb-hosting-sync/20261022-admin-clean/flow.log, manifest.json, live-verification.json. Hướng dẫn sử dụng theo mục: HUONG_DAN_ADMIN.md.
