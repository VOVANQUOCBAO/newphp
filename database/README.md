# Cơ sở dữ liệu

`webbanhang_mvc.sql` chứa cấu trúc 14 bảng hiện tại; tài khoản quản trị được tạo riêng bằng setup. Đây là file SQL có thể đưa lên GitHub; dữ liệu MySQL đang chạy vẫn nằm trên máy của mỗi người.

## Cách nhanh

Chạy `setup.bat` tại thư mục gốc. Script tự khởi tạo CSDL/bảng bằng `database/schema.sql`, không cần import tay. File `database/schema.sql` là bản chuẩn để code và test sử dụng; `database/webbanhang_mvc.sql` là bản đóng gói để import trong phpMyAdmin.

## Import qua phpMyAdmin

1. Bật Apache và MySQL trong XAMPP.
2. Mở `http://localhost/phpmyadmin` và chọn Import.
3. Chọn `database/webbanhang_mvc.sql`, bấm Go/Thực hiện.
4. File tự tạo CSDL `webbanhang_mvc` và 14 bảng. Không có lệnh DROP hay TRUNCATE.

Nếu không có quyền tạo CSDL, tạo `webbanhang_mvc` trước, chọn CSDL đó rồi import `database/schema.sql`.

## Nạp dữ liệu mẫu

Trước tiên, tạo cấu trúc CSDL bằng `setup.bat` hoặc import `database/webbanhang_mvc.sql`. Nếu chỉ import bằng phpMyAdmin, sau đó chạy `setup.bat` một lần để tạo tài khoản quản trị ban đầu. Sau đó chọn CSDL `webbanhang_mvc` trong phpMyAdmin và import [database/du_lieu_mau.sql](du_lieu_mau.sql).

File dữ liệu mẫu có thể chạy lại: chỉ thêm các bản ghi chưa có theo mã sản phẩm, email, mã voucher và nội dung mẫu; không xóa dữ liệu hiện tại. File cập nhật riêng cột ảnh của sản phẩm để dùng minh họa SVG nguyên bản theo danh mục (`view/image/ai-*.svg`). Trạng thái đơn hàng và tổng tiền đã căn theo quy tắc của ứng dụng. Tài khoản nhân viên mẫu dùng mật khẩu `password`; không dùng mật khẩu này cho tài khoản Admin.

Máy XAMPP mặc định dùng host `127.0.0.1`, port `3306`, user `root`, mật khẩu rỗng. Cấu hình riêng đặt trong `support/config/database.local.php` theo file mẫu `support/config/database.example.php`. Máy đang phát triển dùng port `3307`; cấu hình riêng đó không được đưa lên GitHub.

File SQL không chứa khách hàng, đơn hàng, email thật hoặc mật khẩu kết nối MySQL. Tài khoản quản trị được tạo riêng khi chạy `setup.bat`; mật khẩu ngẫu nhiên chỉ được in một lần trong cửa sổ dòng lệnh. Hãy lưu lại mật khẩu đó. Các bảng nghiệp vụ khác ban đầu rỗng để nhóm tự nhập dữ liệu.

Email trong dữ liệu mẫu dùng miền `example.test` để không gửi thư tới địa chỉ cá nhân ngoài ý muốn. Chỉ dùng dữ liệu và mật khẩu mẫu trong môi trường thử riêng.

Không đưa thư mục vật lý `xampp/mysql/data` lên GitHub. Khi thay đổi bảng, cập nhật `database/schema.sql` rồi tạo lại bản đóng gói `database/webbanhang_mvc.sql` với phần đầu CREATE DATABASE/USE tương ứng.

## Cập nhật luồng dữ liệu

Chạy `php support/scripts/khoitao_csdl.php` sau khi cập nhật mã. Migration `support/scripts/migrate_flow.php` bổ sung cột chi tiết sản phẩm, bản chụp đơn, trả/hoàn tiền, lịch sử đơn/kho và các ràng buộc hợp lệ. Không thay thế giá/tên/giảm giá của hóa đơn cũ bằng giá trị sản phẩm hiện tại. Nếu dữ liệu cũ vi phạm ràng buộc, migration ghi nhận để đối chiếu và giữ nguyên dữ liệu đó.

Chạy `php support/scripts/audit_flow.php` để đối chiếu chỉ đọc. Admin cũng có mục **Đối chiếu dữ liệu**.
