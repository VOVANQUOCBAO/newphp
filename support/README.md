# Hướng dẫn phát triển

## Chạy dự án

Từ thư mục gốc, nhấp đúp `setup.bat` rồi mở **http://127.0.0.1:8000/**. Dùng `127.0.0.1` để chắc chắn truy cập đúng PHP server của dự án, tránh trường hợp `localhost` trỏ tới một server khác. Script tự tìm XAMPP tại `C:\xampp`, `D:\xampp`, `D:\data` hoặc biến `XAMPP_PATH`, khởi tạo CSDL còn thiếu, thêm các cột khách hàng mới vào CSDL đã có mà không xóa dữ liệu, và chạy server PHP. Các tiến trình chạy nền; script in PID để có thể dừng bằng `Stop-Process -Id <PID>` khi xong.

Khi tạo mới tài khoản quản trị, mật khẩu ngẫu nhiên chỉ được in một lần trong cửa sổ dòng lệnh. Hãy lưu lại; trang đăng nhập không điền sẵn thông tin tài khoản. Có thể đặt biến môi trường `ADMIN_INITIAL_PASSWORD` (ít nhất 12 ký tự) trước khi chạy setup để tự chọn hoặc đặt lại mật khẩu Admin. Nếu tài khoản Admin cũ đang dùng mật khẩu mặc định `123` hoặc `password`, setup sẽ đổi sang mật khẩu mới và in một lần.

Nếu cần chỉ định thư mục hoặc cổng:

```powershell
.\setup.bat -XamppPath 'E:\xampp' -MySqlPort 3307 -WebPort 8000
```

Cấu hình mặc định là host `127.0.0.1`, port `3306`, CSDL `webbanhang_mvc`, user `root`, mật khẩu rỗng. Cấu hình riêng đặt tại `support/config/database.local.php`; lấy mẫu từ `database.example.php` trong cùng thư mục. Script giữ nguyên cấu hình đã có và tự nhận cổng MySQL XAMPP đang chạy. Các biến `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS` được ưu tiên hơn file local.

Khi cập nhật từ cấu trúc cũ, `setup.bat` tự sao chép cấu hình cũ ở `config/database.local.php` sang vị trí mới nếu cần. File chứa thông tin riêng không được đưa lên GitHub.

### Dùng Apache / phpMyAdmin

Đặt dự án trong `htdocs/phpweb`, bật Apache và MySQL, import [database/webbanhang_mvc.sql](../database/webbanhang_mvc.sql) rồi mở `http://localhost/phpweb/`. MySQL phải trùng host/port trong cấu hình. File SQL tạo 14 bảng, không xóa dữ liệu cũ.

Có thể khởi tạo bằng CLI:

```powershell
& 'C:\xampp\php\php.exe' support/scripts/khoitao_csdl.php
```

## Kiểm thử

```powershell
& 'C:\xampp\php\php.exe' support/tests/test_luong.php
```

Thay đường dẫn PHP theo máy. Test tự tạo CSDL `webbanhang_mvc_test_<chuỗi ngẫu nhiên>`, chạy Model và HTTP với các cookie Session riêng, kiểm tra cạnh tranh bằng các tiến trình đồng thời, rồi xóa riêng CSDL vừa tạo. Báo cáo nằm ở `support/tests/ketqua_test.json`; mã thoát 0 nghĩa là mọi kiểm tra đều đạt.

Các ca kiểm tra gồm đăng nhập, giỏ hàng, giá khuyến mãi, voucher, thanh toán, hủy/hoàn kho, bom/trả hàng, điểm/hạng, doanh thu, đánh giá và quyền sở hữu đơn. Một số ca chủ động gây lỗi INSERT/UPDATE để chứng minh rollback; thông báo `Test rollback` trong log là dự kiến. Test không gửi email thật.

## Hợp đồng Controller User với View

Mọi thao tác gọi `controller/index.php?act=...` (cũng nhận `case`). Dữ liệu form là các trường đơn, không phải mảng. Giỏ Session có dạng `[id_sanpham => so_luong]`. Các View chỉ đọc biến do `render()` đưa vào; không cần query SQL. Khi View chưa tồn tại, Controller trả JSON có cùng các trường.

| act | Dữ liệu gửi | View / biến chính |
| --- | --- | --- |
| `trangchu`, `timkiem` | GET `q`, `id_danhmuc`, `sapxep=moi/cu`, `trang`, `moi_trang` | `trangchu.php`: `ds_danhmuc`, `ds_sanpham`, `sp_moi`, `sp_km`, `trang`, `moi_trang`, `tong_sanpham`, `con_tiep` |
| `dangky` | POST `ho_ten`, `email`, `mat_khau`, `ngay_sinh` (YYYY-MM-DD, tùy chọn) | `dangky.php` |
| `dangnhap` | POST `email`, `mat_khau` | `dangnhap.php`; thành công lưu `user` vào Session và chuyển về trang chủ |
| `dangxuat` | GET | Xóa Session user và giỏ, đổi Session ID, chuyển về trang chủ |
| `chitiet` | GET `id` | `chitiet_sanpham.php`: `chitiet`, `ds_danhgia` |
| `themdanhgia` | POST `id_sanpham`, `so_sao` (1–5), `noi_dung` | `chitiet_sanpham.php`: cùng cấu trúc như xem chi tiết |
| `giohang` | GET | `giohang.php`: `giohang` gồm `items` và `tong` |
| `themgiohang`, `suagiohang`, `xoagiohang` | `id`, `sl` (thêm mặc định 1; sửa 0 để xóa dòng) | `giohang.php`; `sl` phải là số nguyên, không âm hoặc vượt tồn |
| `thanhtoan` | GET xem giỏ; POST `dia_chi`, `sdt`, `ma_voucher` (tùy chọn) | `thanhtoan.php`: `giohang`, `user`; thành công thêm `ok`, `id_don`, `donhang`, `email_result` |
| `lichsumuahang` | GET | `lichsu_muahang.php`: `ds_donhang` |
| `huydonhang` | `id`, POST `ly_do` | `lichsu_muahang.php`: `ds_donhang`; chỉ chủ đơn, trước lúc Đang giao (Chờ xác nhận hoặc Xác nhận đơn hàng) |
| `khieunai` | POST `id`, `noi_dung` | `lichsu_muahang.php`: lưu thư loại `khieunai` |
| `theodoidonhang` | GET `id` | `theodoi_donhang.php`: `donhang` có `chitiet`, `timeline` |
| `hopthu` | GET | `hopthu.php`: `ds_thu` của khách đang đăng nhập |

Các trang có `thongBao` và `loi` (null khi không có). `sp_moi`, `sp_km` và `ds_sanpham` đều là danh sách sản phẩm, có `gia_goc`, `gia_km`, `het_hang`. `sp_km` không phải danh sách chương trình khuyến mãi. Nút Xem thêm tăng `trang`, giữ nguyên bộ lọc, dùng `con_tiep` để biết còn dữ liệu.

Mỗi item giỏ có `id`, `ma_sp`, `ten_sp`, `hinh`, `gia`, `so_luong`, `ton`, `thanh_tien`, `het_hang`. Tổng giỏ là tổng hàng sau khuyến mãi, chưa tính voucher và phí ship. Giá luôn được tính lại phía server khi đặt hàng. Nếu nhiều chương trình khuyến mãi trùng nhau, lấy phần trăm cao nhất.

Trạng thái chính: Chờ xác nhận → Xác nhận đơn hàng → Đang giao → Đã giao → Thành công. Khách/admin hủy trước Đang giao. Đang giao có nhánh Bom hàng/Trả hàng sau khi nhận lại hàng. Đơn Đã giao/Thành công được yêu cầu trả trong 7 ngày từ ngày giao. Yêu cầu chưa hoàn kho; admin nhận hàng rồi chọn hoàn kho bán lại hoặc cách ly hàng lỗi. Trả đơn đã cộng điểm sẽ thu hồi đúng điểm của đơn và xét lại hạng. Hủy/bom/trả hoàn lượt voucher đúng một lần (vẫn phải còn hạn nếu dùng lại). PayPal đã trả tiền cần hoàn trên PayPal và đối soát trước khi kết thúc hủy/trả. COD có trạng thái hoàn tiền và chứng từ riêng. Xem [báo cáo chi tiết](../BAO_CAO_LUONG_DU_LIEU.md).

Quy ước có thể chỉnh trong Model: phí ship 30.000đ; mỗi 1.000đ tổng đơn thành công được 1 điểm; Gold từ 500 điểm, Diamond từ 2.000 điểm; Gold/Diamond miễn ship. Voucher gán hạng chỉ dùng cho đúng hạng đó. PDF không ấn định các ngưỡng này.

## Email Gmail SMTP

Đã cài PHPMailer bằng Composer, khóa phiên bản trong `support/composer.lock`. Khi chuyển máy dùng `composer --working-dir=support install`. Thư viện gửi mail này không phải framework MVC. Tài liệu: [PHPMailer chính thức](https://github.com/PHPMailer/PHPMailer).

Đặt biến môi trường `SMTP_USER` (Gmail), `SMTP_PASS` (mật khẩu ứng dụng), tùy chọn `SMTP_FROM`, rồi khởi động lại server PHP. Không ghi mật khẩu vào mã nguồn. SMTP dùng `smtp.gmail.com`, port 587, STARTTLS, timeout 10 giây.

Khi chưa cấu hình Gmail hoặc gửi thất bại, đơn vẫn được lưu, giỏ đã xóa và bản sao thư vẫn nằm trong bảng `email`. Kết quả `email_result.smtp.ok` cho biết trạng thái gửi ra ngoài. Đăng ký trực tiếp bằng mật khẩu được hỗ trợ; OTP là lựa chọn thêm. Khi chọn xác minh OTP mà gửi thất bại, trang báo lỗi và không tiết lộ mã. Test chủ động vô hiệu hóa SMTP để không gửi email thật. Việc gửi Gmail thật cần tài khoản và thông tin SMTP của bạn; chưa được kiểm chứng bằng hộp thư thật.

## Google Sign-In và hosting

Google Sign-In dùng Google Identity Services trên trình duyệt và xác minh ID token phía server bằng `google/apiclient`. Dependencies đã khóa trong Composer; cài Composer trên PHP 8.1+ rồi upload `support/vendor/` khi deploy. Cấu hình `GOOGLE_CLIENT_ID` và `APP_URL`, hoặc dùng file riêng `support/config/google.local.php` dựa trên `google.example.php`. Chi tiết bước tạo credential trong Google Cloud Console, chuẩn bị MySQL và checklist deploy nằm ở [DEPLOYMENT.md](DEPLOYMENT.md).

Ứng dụng dùng MySQL/MariaDB; Supabase PostgreSQL không tương thích trực tiếp. Hosting PHP/MySQL miễn phí là hướng demo, chưa được kiểm chứng trên tài khoản hosting thực và không được đảm bảo phù hợp production.

Các biểu mẫu ghi dữ liệu trong Admin cần POST kèm CSRF token; thao tác thay đổi qua GET bị từ chối. Tài khoản cũ có mật khẩu plaintext/MD5 sẽ được nâng cấp sang `password_hash()` ngay sau lần đăng nhập hợp lệ tiếp theo. Đổi mật khẩu Admin yêu cầu tối thiểu 12 ký tự.

## Biểu mẫu và kiểm tra mới

Các thao tác khách dùng POST kèm `_csrf` lấy từ `userCsrf` của phiên; checkout dùng thêm `_checkout_csrf`, COD dùng `_order_key` chống gửi lặp. Admin dùng `_csrf` riêng và mọi action phải giữ `khu=admin`. Đăng nhập chung nhận tài khoản khách hoặc nhân viên và tự điều hướng. Upload ảnh bằng multipart, `anh` cho ảnh chính, `album[]` cho tối đa 6 ảnh. Chỉ nhận JPG/PNG/WebP hợp lệ, mỗi ảnh tối đa 5 MB.

Chạy `php support/tests/test_luong.php` và `php support/tests/test_paypal.php`; cả hai dùng CSDL tạm. PayPal dùng bộ mô phỏng ngoại tuyến, không tạo giao dịch tiền thật. Các ca thêm nằm tại `support/tests/flow_scenarios.php`.
