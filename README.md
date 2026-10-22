# PHPWeb

Website bán hàng theo mô hình **MVC**, viết bằng **PHP thuần**, kết nối **MySQL qua PDO**.

Mã nguồn: [VOVANQUOCBAO/newphp](https://github.com/VOVANQUOCBAO/newphp). Phiên bản hiện tại có quản trị sản phẩm/danh mục, ưu đãi theo phạm vi, voucher, QR chuyển khoản do admin xác nhận, PayPal và đăng nhập Google.

**PHP 8+ cho chạy local** · **PHP 8.1+ khi cài Google API Client/deploy** · **MySQL / MariaDB**

> Giao diện khách hàng và khu vực quản trị MVC cùng đọc/ghi một CSDL MySQL qua Model. CSDL mới có thể nạp danh mục, sản phẩm và dữ liệu thử từ `database/du_lieu_mau.sql`.

## Chạy nhanh

1. Tải mã nguồn và giải nén; máy cần cài XAMPP có PHP 8 trở lên.
2. Nhấp đúp **`setup.bat`**. CSDL và các bảng còn thiếu sẽ được khởi tạo tự động.
3. Mở **http://127.0.0.1:8000/**.

Chạy local không cần cài Composer. Để bật Google Sign-In cần cài dependencies bằng Composer; xem [hướng dẫn triển khai](support/DEPLOYMENT.md). Nếu MySQL có mật khẩu riêng, sửa `support/config/database.local.php`. [Xem cấu hình và cách chạy khác →](support/README.md)

Đăng nhập chung tại **http://127.0.0.1:8000/index.php?act=dangnhap**. Tài khoản nhân viên/quản trị tự chuyển vào khu quản lý. Khi tạo mới, mật khẩu Admin ngẫu nhiên chỉ được in một lần ở cửa sổ setup; không có thông tin đăng nhập mẫu an toàn để dùng trên website công khai.

Sau khi đăng nhập admin, mở **http://127.0.0.1:8000/index.php?khu=admin&act=csdl** để xem trực tiếp các bảng, số bản ghi và dữ liệu mới nhất trong CSDL. Trang chỉ đọc, chỉ dành cho admin; cột mật khẩu/token được ẩn. Mỗi thao tác của user/admin được lưu qua các luồng ứng dụng và xuất hiện khi tải lại bảng tương ứng.

## Cấu trúc

```text
phpweb/
├── controller/     Điều hướng và xử lý yêu cầu
├── model/          Truy vấn dữ liệu và nghiệp vụ
├── view/           Giao diện do nhóm phát triển
├── database/       SQL và hướng dẫn import
├── support/        Cấu hình, thư viện, script và kiểm thử
├── index.php       Điểm vào ứng dụng
└── setup.bat       Khởi động với XAMPP
```

## Tài liệu

| Nội dung | Tài liệu |
| --- | --- |
| Import CSDL bằng phpMyAdmin | [database/README.md](database/README.md) |
| Cấu hình, kiểm thử và ghép View | [support/README.md](support/README.md) |
| Hosting PHP/MySQL miễn phí và Google Sign-In | [support/DEPLOYMENT.md](support/DEPLOYMENT.md) |
| PayPal Live/Sandbox, USD và đối soát thanh toán | [support/PAYPAL_SETUP.md](support/PAYPAL_SETUP.md) |
| Các mục admin, phân quyền và cách thao tác | [HUONG_DAN_ADMIN.md](HUONG_DAN_ADMIN.md) |
| QR, voucher, hủy và hoàn tiền | [HUONG_DAN_QR_VA_VOUCHER.md](HUONG_DAN_QR_VA_VOUCHER.md) |
| Kịch bản kiểm thử thủ công | [HUONG_DAN_TEST_THU_CONG.md](HUONG_DAN_TEST_THU_CONG.md) |

**Kiểm thử:** Chạy `support/tests/test_luong.php` để xem kết quả mới nhất. Bộ test dùng CSDL tạm riêng, giữ nguyên dữ liệu ứng dụng.

Kết quả gần nhất ngày 22/10/2026: **279 ca luồng đạt**. Đã kiểm tra mở 12 trang quản trị trên hosting; xem báo cáo và hướng dẫn để biết phạm vi kiểm tra.

## Cấu hình riêng khi lấy mã từ GitHub

Repo gồm mã ứng dụng, ảnh sản phẩm/giao diện, SQL cấu trúc/dữ liệu mẫu và bộ kiểm thử. CSDL đang vận hành, mật khẩu, token, file `*.local.php`, bản ZIP phát hành cũ và kết quả chạy thử không được lưu trong Git.

Tạo các file cấu hình riêng từ `support/config/*.example.php` khi cần bật MySQL, Google, PayPal, SMTP hoặc QR. Cài dependencies bằng Composer theo hướng dẫn triển khai và `support/composer.lock`; thư mục dependency cài bằng Composer không được commit. Bản cấu hình riêng trên hosting đang chạy được giữ độc lập với repo.

**Email:** gửi Gmail thật cần `SMTP_USER` và `SMTP_PASS`; nếu gửi OTP thất bại, mã không được hiển thị ra trình duyệt. Bản sao email của đơn hàng vẫn được lưu trong CSDL.

**Chatbox AI:** khung chat ở footer đã hoạt động với phản hồi mặc định. Để bật AI thật, tạo `support/config/chatbot.local.php` dựa trên `support/config/chatbot.example.php` và điền key API. Nếu không cấu hình hoặc key không hợp lệ, chatbot sẽ tự động dùng logic gợi ý sản phẩm của website.

Luồng dữ liệu, quy tắc hủy/trả, voucher và kết quả kiểm tra: [Báo cáo luồng dữ liệu](BAO_CAO_LUONG_DU_LIEU.md). Sau khi cập nhật mã, chạy `D:/data/php/php.exe support/scripts/khoitao_csdl.php` (hoặc PHP của máy bạn) để bổ sung cấu trúc, giữ dữ liệu cũ.
