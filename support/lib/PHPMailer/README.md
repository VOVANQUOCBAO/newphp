# PHPMailer 7.1.1

Ba file PHP gốc `Exception.php`, `PHPMailer.php`, `SMTP.php` được chép nguyên vẹn từ gói `phpmailer/phpmailer` phiên bản 7.1.1 đã khóa trong `support/composer.lock`.

Nguồn: [PHPMailer/PHPMailer v7.1.1](https://github.com/PHPMailer/PHPMailer/tree/v7.1.1/src). Giấy phép GNU LGPL 2.1 đi kèm trong `LICENSE`; các thông báo bản quyền trong mã nguồn được giữ nguyên.

Bản kèm này phục vụ Gmail SMTP bằng tài khoản/mật khẩu ứng dụng để người tải dự án không bắt buộc cài Composer. Khi có `support/vendor/autoload.php`, ứng dụng ưu tiên Composer. Khi không có, `controller/xuly_email.php` nạp ba file ở đây. Các tính năng OAuth2 không được sử dụng trong bài tập này.
