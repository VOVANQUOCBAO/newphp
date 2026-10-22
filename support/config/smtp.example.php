<?php
/**
 * Hướng dẫn cấu hình gửi Email thật bằng Gmail SMTP cho website ĐiệnThoạiZ
 * 
 * 1. Bật xác thực 2 bước (2-Step Verification) cho tài khoản Google của bạn.
 * 2. Truy cập: https://myaccount.google.com/apppasswords
 * 3. Tạo một "Mật khẩu ứng dụng" (App Password) mới với tên tuỳ ý (ví dụ: WebBanDienThoai).
 * 4. Google sẽ cấp cho bạn một mật khẩu 16 ký tự (ví dụ: abcd efgh ijkl mnop).
 * 5. Điền email của bạn vào 'user' và mật khẩu 16 ký tự vào 'pass' bên dưới.
 * 6. Lưu file này thành support/config/smtp.local.php.
 */

return [
    'host' => 'smtp.gmail.com',
    'port' => 587,
    'user' => 'your_gmail@gmail.com',         // Điền Gmail thật của bạn ở đây
    'pass' => 'xxxx xxxx xxxx xxxx',          // Điền Mật khẩu ứng dụng Google (16 ký tự)
    'from' => 'your_gmail@gmail.com',         // Thường giống user
    'from_name' => 'ĐiệnThoạiZ - Smartphone Chính Hãng',
];

