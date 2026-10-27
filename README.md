# BP Store — Website bán điện thoại bằng PHP thuần

Đồ án môn **Lập trình Web nâng cao**, viết bằng **PHP thuần theo hướng đối tượng (OOP)** và tổ chức theo **MVC đơn giản**. Không sử dụng Laravel hay framework PHP.

- Website: [bpstore.gt.tc](https://bpstore.gt.tc/)
- Mã nguồn: [VOVANQUOCBAO/newphp](https://github.com/VOVANQUOCBAO/newphp)
- Giải thích lớp, kế thừa và luồng xử lý: [Hướng dẫn OOP](support/HUONG_DAN_OOP.md)

## Chức năng

**Khách hàng**

- Xem danh mục, tìm kiếm, phân trang và xem chi tiết sản phẩm.
- Đăng ký, đăng nhập, cập nhật hồ sơ và lưu sản phẩm yêu thích.
- Giỏ hàng, mua ngay, áp dụng voucher và đặt hàng.
- Thanh toán COD, chuyển khoản QR hoặc PayPal khi cửa hàng đã cấu hình.
- Theo dõi đơn, xem lịch sử mua hàng, gửi yêu cầu trả hàng và khiếu nại.
- Đánh giá sản phẩm đã nhận; xem hộp thư, điểm và hạng thành viên.

**Quản trị**

- Quản lý sản phẩm, danh mục, ảnh, tồn kho và các đợt nhập hàng.
- Xử lý đơn, đối soát thanh toán, hủy đơn và tiếp nhận trả hàng.
- Quản lý khách hàng, nhân viên, đánh giá, voucher và khuyến mãi.
- Xem doanh thu, đối chiếu dữ liệu và thay đổi giao diện.
- Cấu hình email, phản hồi khiếu nại và theo dõi thư xác nhận thanh toán.

## Công nghệ và yêu cầu

| Thành phần | Sử dụng |
| --- | --- |
| Xử lý phía server | PHP 8.1 trở lên, OOP |
| Cơ sở dữ liệu | MySQL/MariaDB, truy vấn qua PDO |
| Giao diện | HTML, CSS, JavaScript và Bootstrap |
| Gửi email | PHPMailer |
| Đăng nhập Google | Google API Client, cần cấu hình riêng |
| Thanh toán | COD, VietQR và PayPal |

PHP cần các extension `pdo_mysql`, `mbstring`, `curl`, `openssl` và `fileinfo`. Composer dùng để cài các dependency trong `support/composer.json`, đặc biệt khi bật đăng nhập Google. Dự án có sẵn bản PHPMailer dự phòng trong `support/lib/PHPMailer/`.

## Cấu trúc mã nguồn

```text
newphp/
├── index.php                  # Điểm vào website
├── controller/
│   ├── application.php        # Khởi tạo session, chọn khu khách/quản trị
│   ├── controller.php         # Render, chuyển trang, xác thực và CSRF
│   ├── storefront_controller.php
│   ├── giohang.php
│   ├── paypal.php
│   ├── bank.php
│   └── admin/                 # Điều phối các chức năng quản trị
├── model/                     # Dữ liệu và quy tắc nghiệp vụ
│   ├── csdl.php               # Kết nối PDO dùng chung
│   ├── xl_database.php        # Các thao tác truy vấn cơ bản
│   ├── sanpham.php
│   ├── khachhang.php
│   └── donhang.php
├── view/                      # Template PHP, CSS, JS và ảnh
│   ├── helpers.php            # Lớp view_helper
│   ├── components/            # Thành phần giao diện dùng lại
│   └── admin/
├── database/                  # Schema, migration SQL và dữ liệu mẫu
└── support/
    ├── config/                # Cấu hình mẫu và cấu hình riêng của môi trường
    ├── lib/                   # Thư viện hỗ trợ
    ├── scripts/               # Khởi tạo và cập nhật cấu trúc CSDL
    ├── tests/                 # Kiểm thử
    └── db-bridge/             # Cầu nối HTTPS tùy chọn cho TiDB
```

Luồng xử lý chính:

```text
Trình duyệt → application → controller → model → controller → view
```

Model sản phẩm, khách hàng, đơn hàng… kế thừa `xl_database`; lớp này kế thừa `csdl`. Controller khách và quản trị kế thừa `controller`. View nhận dữ liệu để hiển thị, không tự truy vấn CSDL.

## Cài đặt và chạy local

Các lệnh dưới đây dùng PowerShell, chạy từ thư mục gốc dự án. Cần khởi động MySQL/MariaDB trước và bảo đảm lệnh `php` trỏ đến bản PHP có đủ extension.

### 1. Tải mã nguồn

```powershell
git clone https://github.com/VOVANQUOCBAO/newphp.git
cd newphp
```

### 2. Cấu hình CSDL

```powershell
Copy-Item support/config/database.example.php support/config/database.local.php
```

Chỉnh `support/config/database.local.php` theo máy của bạn:

```php
<?php
return [
    'driver' => 'mysql',
    'host' => '127.0.0.1',
    'port' => 3306,
    'dbname' => 'webbanhang_mvc',
    'user' => 'root',
    'pass' => '',
    'charset' => 'utf8mb4',
];
```

Nếu MySQL chạy cổng khác, ví dụ `3307`, sửa trường `port`. Biến môi trường `DB_*` được ưu tiên hơn file cấu hình.

### 3. Cài dependency và khởi tạo dữ liệu

```powershell
composer install --working-dir=support
php support/scripts/khoitao_csdl.php
```

Script khởi tạo cần tài khoản CSDL có quyền tạo database và bảng. Script chạy bằng **PHP CLI**, không mở trực tiếp bằng trình duyệt.

Khi chưa có tài khoản quản trị, script tạo `admin@local.test` với mật khẩu ngẫu nhiên và hiển thị mật khẩu một lần trong terminal. Lưu mật khẩu đó để đăng nhập. Không có mật khẩu quản trị mặc định cố định.

Có thể thêm sản phẩm từ trang quản trị. File `database/du_lieu_mau.sql` là dữ liệu mẫu tùy chọn; kiểm tra lệnh `USE webbanhang_mvc` trong file trước khi import nếu dùng tên database khác.

### 4. Chạy website

```powershell
php -S 127.0.0.1:8000 -t .
```

Mở [http://127.0.0.1:8000/](http://127.0.0.1:8000/). Tài khoản khách và nhân viên dùng chung trang đăng nhập. Đăng nhập bằng tài khoản quản trị để mở các chức năng quản lý.

Với XAMPP trên Windows, có thể dùng script hỗ trợ thay cho các bước khởi động thủ công:

```powershell
.\support\scripts\chay_local.ps1 -XamppPath 'C:\xampp' -MySqlPort 3306 -WebPort 8000
```

## Cấu hình chức năng tùy chọn

Sao chép file `*.example.php` thành `*.local.php` tương ứng trong `support/config/`, rồi điền thông tin của môi trường triển khai.

| File riêng | Mục đích |
| --- | --- |
| `database.local.php` | Kết nối CSDL |
| `smtp.local.php` | Máy chủ và tài khoản gửi email |
| `google.local.php` | Google Web Client ID và URL ứng dụng |
| `paypal.local.php` | Chế độ Sandbox/Live và thông tin nhận thanh toán |
| `bank.local.php` | Thông tin tài khoản nhận chuyển khoản QR |

PayPal và QR chỉ khả dụng khi được bật và cấu hình hợp lệ. Với PayPal, kiểm tra bằng Sandbox trước khi chuyển sang Live. Email và đăng nhập Google cần dependency/cấu hình tương ứng.

`support/db-bridge/` là dịch vụ **Node.js tùy chọn** để nối PHP với TiDB qua HTTPS. Website PHP dùng MySQL/MariaDB trực tiếp không cần chạy dịch vụ này. Cấu hình tham khảo ở `support/config/database.https.example.php`.

## Quy tắc xử lý chính

- Giá, khuyến mãi, voucher và tổng tiền được tính lại phía server.
- Tạo đơn giữ kho và lượt voucher trong giao dịch; gửi lại cùng phiên đặt hàng không trừ lặp.
- Đơn chuyển trạng thái theo luồng cho phép; thao tác cập nhật kiểm tra đăng nhập, quyền, POST và CSRF.
- Khách báo đã chuyển khoản chưa được coi là cửa hàng đã nhận tiền.
- Điểm mỗi đơn bằng `floor(tổng tiền đã chốt / 1.000)`, chỉ cộng khi đơn thành công; trả hàng hợp lệ thu hồi điểm đã cộng.
- Hạng Silver dưới 500 điểm, Gold từ 500 điểm và Diamond từ 2.000 điểm. Gold/Diamond được miễn phí giao hàng theo chính sách hiện tại.

Chi tiết: [OOP và điểm thành viên](support/OOP_VA_DIEM_THANH_VIEN.md).

## Kiểm thử

```powershell
php support/tests/test_luong.php
php support/tests/test_paypal.php
php support/tests/homepage_presentation_scenarios.php
php support/tests/member_policy_email_scenarios.php
php support/tests/payment_email_template_scenarios.php
```

Bộ tích hợp tạo database test riêng, kiểm tra các luồng và đối chiếu database ứng dụng trước/sau. Tài khoản MySQL dùng để chạy bộ này cần quyền tạo/xóa database test. Bộ PayPal sử dụng dịch vụ giả, không thực hiện thanh toán thật. Không chạy kiểm thử tích hợp bằng cấu hình database production.

Kết quả kiểm tra bản OOP ngày 27/10/2026: **353 tình huống chức năng đạt**, **45 tình huống PayPal mô phỏng đạt**, cùng các kiểm thử giao diện và email. Đây là kết quả kiểm tra local, không phải trạng thái CI tự động.

## Cập nhật hosting

Đưa mã nguồn vào thư mục phục vụ website, ví dụ `htdocs` trên InfinityFree. Khi cập nhật website đang hoạt động:

1. Sao lưu mã nguồn và CSDL hiện tại.
2. Cập nhật các file ứng dụng, giữ cấu hình `support/config/*.local.php`, `theme.json`, `support/vendor/` và ảnh trong `view/image/uploads/` của hosting.
3. Chỉ chạy migration phù hợp nếu phiên bản mới yêu cầu; không import lại dữ liệu mẫu khi chỉ đổi code OOP.
4. Kiểm tra trang chủ, sản phẩm, giỏ hàng, đăng nhập và quyền truy cập quản trị.

Các file `.local.php`, `.env`, mật khẩu, thư viện đã cài, kết quả test và ảnh xem thử sinh tạm được loại khỏi Git bằng `.gitignore`. Tạo cấu hình riêng cho từng máy/hosting; không đưa thông tin đăng nhập thật lên repository.
