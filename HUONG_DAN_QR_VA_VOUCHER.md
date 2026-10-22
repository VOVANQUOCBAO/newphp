# Chuyển khoản QR và voucher — 22/10/2026

## Khách mua hàng

1. Chọn sản phẩm → **Mua ngay** hoặc mở giỏ rồi thanh toán.
2. Nhập mã và bấm **Áp dụng voucher / xem tổng tiền**. Xem tổng không tạo đơn, giữ kho hoặc trừ lượt voucher. Nút **Bỏ voucher** trả về tổng chưa dùng mã. Phương thức thanh toán và thông tin nhận hàng đã nhập được giữ trên form.
3. Chọn **Chuyển khoản QR — cửa hàng xác nhận**, điền địa chỉ/số điện thoại và đặt hàng. Máy chủ tính lại giá, khuyến mãi, phí ship, hạng và voucher; chỉ khi đơn được lưu thành công mới giữ kho và một lượt voucher.
4. Trang QR hiện số tiền VND, ngân hàng, tài khoản, tên người nhận và nội dung riêng cho đơn. Thông tin nhận tiền là bản chụp lưu trong `thanhtoan_qr`, không thay đổi theo cấu hình mới của cửa hàng.
5. Kiểm tra tên người nhận trong ứng dụng ngân hàng rồi chuyển đúng tiền/nội dung. Sau khi chuyển, bấm **Tôi đã chuyển khoản**. Đây là thông báo chờ đối soát, chưa phải xác nhận đã thanh toán. Không chuyển thêm khi đang chờ admin.
6. Mở **Kiểm tra trạng thái**, lịch sử hoặc theo dõi đơn để xem kết quả admin đã lưu.

Đơn QR chưa báo chuyển khoản giữ hàng 30 phút. Hệ thống hủy đơn hết hạn khi xử lý các lượt mở trang cửa hàng/thanh toán/lịch sử hoặc trang QR; hosting hiện tại không có tác vụ nền liên tục. Đơn đã báo chuyển khoản không tự hết hạn trong khi chờ admin kiểm tra. Khách được hủy trước khi giao nếu chưa báo chuyển hoặc đã được hoàn tiền; khi có khoản tiền cần đối soát phải chờ admin xử lý.

## Admin đối soát

Vào **Quản lý Đơn hàng → Đối soát chuyển khoản QR** ở đúng đơn.

- Kiểm tra giao dịch thực tế trong ngân hàng: tài khoản nhận, nội dung và số tiền phải khớp đơn. Nhập **số tiền thực nhận** cùng **mã giao dịch ngân hàng** rồi xác nhận đã kiểm tra. Không dùng mã nội dung đơn thay cho mã giao dịch.
- Chỉ Admin/SuperAdmin được xác nhận. Hệ thống lưu người, thời gian và chứng từ. Một mã giao dịch nhận tiền không gắn được hai đơn trong cùng tài khoản ngân hàng; bấm lại cùng chứng từ không ghi nhận thêm.
- Đơn chưa nhận đủ tiền không được chuyển sang xác nhận/giao hàng. Số tiền thiếu hoặc thừa cần đối soát riêng, không tự coi là đã thanh toán.
- Nếu khách báo chuyển nhưng kiểm tra chưa thấy tiền: chỉ ghi **chưa nhận tiền** sau khi đã kiểm tra ngân hàng. Đơn trở lại trạng thái có thể hủy/hết hạn; nếu tiền đến sau đó vẫn phải ghi nhận khoản đến muộn.

## Hủy, tiền đến muộn và trả hàng

- Chưa nhận tiền: hủy/hết hạn hoàn kho và lượt voucher đúng một lần.
- Đã nhận tiền, chưa giao: admin chuyển lại tiền ngoài hệ thống, nhập chứng từ **Xác nhận đã hoàn chuyển khoản**, rồi hủy đơn để giải phóng kho. Trong khoảng đã hoàn nhưng chưa hủy, đơn bị chặn giao hàng và xuất hiện trong đối chiếu dữ liệu.
- Tiền đến sau khi đơn đã hủy: admin ghi nhận ở chính đơn cũ. Hệ thống chuyển khoản tiền sang **Chờ hoàn tiền**, giữ đơn đã hủy, không trừ lại kho/voucher và không tự phục hồi đơn. Sau khi chuyển hoàn, ghi chứng từ hoàn.
- Trả hàng: khách gửi yêu cầu theo chính sách hiện tại; admin nhận lại/kiểm tra toàn bộ hàng rồi xác nhận nhập lại kho hoặc cách ly hàng lỗi. Lúc này khoản đã thu chuyển sang **Chờ hoàn tiền**. Admin chuyển hoàn bên ngoài rồi ghi chứng từ; trạng thái thanh toán và yêu cầu trả được đồng bộ. Không dùng nút hoàn COD cho đơn QR.
- Website ghi nhận kết quả kiểm tra của admin; bản này không kết nối lịch sử giao dịch ngân hàng và không thực hiện chuyển/hoàn tiền tự động.

## Quy tắc voucher

- Một đơn dùng một mã; mã được chuẩn hóa chữ hoa và bỏ khoảng trắng đầu/cuối.
- Bấm **Áp dụng** chỉ xem tổng, không trừ lượt trong CSDL. Đặt đơn thành công mới giữ một lượt, kể cả đơn chờ thanh toán; hủy hợp lệ hoàn lượt đúng một lần.
- Mã giảm tiền/phần trăm phải có giá trị lớn hơn 0; phần trăm tối đa 100. Tên như 99K không tự sinh mức giảm. Mã cũ cấu hình 0 bị chặn trước khi lưu đơn để không mất lượt vô ích. Miễn ship dùng giá trị 0 và giảm phí giao hàng, không giảm dòng tiền hàng.
- Đồng thời thỏa ngày bắt đầu/kết thúc (gồm cả hai ngày, giờ Việt Nam), lượt còn lại, hạng tối thiểu và tiền hàng tối thiểu sau khuyến mãi sản phẩm. Phí ship không tính vào ngưỡng tiền hàng.
- Giảm tiền/phần trăm chỉ giảm tiền hàng, không làm tiền hàng âm. Miễn ship đưa phí giao về 0.
- Gold/Diamond đã miễn ship thì mã miễn ship bị từ chối để không tiêu phí một lượt; có thể chọn mã giảm tiền khác.
- Danh sách mã còn hiệu lực ghi rõ ưu đãi và đủ/chưa đủ điều kiện cho đơn đang mua. Mã hợp lệ khi xem tổng vẫn được kiểm tra lại trong giao dịch lưu đơn. Hai người tranh lượt cuối chỉ một người được dùng; lỗi đặt đơn rollback cả kho lẫn lượt mã.
- QR chỉ thu số tiền VND nguyên dương. Nếu voucher khiến tổng bằng 0đ, chọn COD để đặt đơn miễn phí, không tạo QR chuyển 0đ.
- Hủy/trả toàn bộ hợp lệ hoàn lượt đúng một lần; chỉ áp dụng được lại khi mã còn trong thời hạn và thỏa các điều kiện. Chính sách hiện tại không có trả một phần.

Ví dụ theo bộ mã đã tạo ngày 21/10: BP50K yêu cầu 500.000đ tiền hàng; SHIP0 yêu cầu 200.000đ; GOLD5 cần Gold/Diamond và 1.000.000đ; DIAMOND10 cần Diamond và 2.000.000đ. Điều kiện thực tế luôn đọc từ CSDL nên thay đổi trong admin được áp dụng khi khách xem/lưu đơn tiếp theo.

## Ca kiểm thử

1. Chọn PayPal rồi áp mã: vẫn ở form thanh toán, hiển thị tổng hoặc lý do không đủ điều kiện, không trả JSON lỗi. Lặp lại với QR và COD.
2. Mã không tồn tại, chưa tới ngày/hết hạn, hết lượt, thiếu hạng, thiếu ngưỡng: không tạo đơn và không trừ kho. Bỏ mã rồi kiểm tra tổng.
3. Mã giảm tiền/phần trăm/miễn ship đủ điều kiện: tổng xem trước khớp tổng đơn và số tiền QR. Mở lại đơn không đổi giá đã chốt.
4. Tạo QR: kiểm tra tài khoản/ngân hàng/tên/nội dung/số tiền khi quét bằng ứng dụng ngân hàng. Chưa cần xác nhận chuyển tiền để kiểm tra dữ liệu quét.
5. Trong CSDL thử riêng: báo chuyển không thành paid; quyền/CSRF; sai tiền; giao dịch trùng; bấm xác nhận lại; hủy đồng thời nhận tiền; hết hạn; tiền đến sau hủy; trả/hoàn; kho/voucher và chứng từ đúng một lần.
6. Với tiền thật, chỉ admin xác nhận sau khi thấy đúng giao dịch trong ngân hàng. Kiểm thử tự động dùng CSDL tạm và giao dịch mô phỏng, không chứng minh ngân hàng đã chuyển/hoàn thật.

Tài liệu QR: [VietQR Quick Link](https://www.vietqr.io/qr-dong). Cấu hình nhận tiền riêng tại `support/config/bank.local.php`; số thẻ, mật khẩu và OTP không dùng trong tích hợp này.

## Phạm vi voucher theo danh mục hoặc sản phẩm

Trong **Quản lý voucher**, form tạo có ba phạm vi: **Toàn cửa hàng**, **Theo danh mục**, **Theo sản phẩm**. Chọn phạm vi riêng rồi chọn một danh mục hoặc một sản phẩm đang bán. Tên mã như IPHONE18 không tự xác định phạm vi.

Với mã đã có, mở **Sửa giá trị / phạm vi** ở dòng của mã, nhập mức giảm, chọn phạm vi/đối tượng rồi bấm **Lưu voucher**. Việc sửa không đặt lại lượt còn dùng và không thay đổi tổng tiền của các đơn đã đặt. Các điều kiện ngày, hạng, tiền hàng tối thiểu và lượt vẫn phải đồng thời thỏa mãn. Phiên bản hiện tại chọn một danh mục hoặc một sản phẩm cho mỗi mã.

- **Toàn cửa hàng:** tiền hàng áp dụng là toàn bộ tiền hàng sau khuyến mãi sản phẩm.
- **Theo danh mục:** chỉ cộng các sản phẩm đang mua thuộc danh mục đã chọn. Hàng khác không được cộng vào ngưỡng tối thiểu và không được giảm.
- **Theo sản phẩm:** chỉ cộng số lượng của đúng sản phẩm đã chọn, không bao gồm sản phẩm khác dù cùng danh mục.
- **Giảm tiền:** tối đa bằng phần tiền hàng được áp dụng. **Phần trăm:** tính trên phần đó. **Miễn ship:** cần hàng phù hợp và đạt ngưỡng, sau đó miễn phí giao của cả đơn.

Ví dụ voucher 20% cho iPhone: giỏ có 1.500.000đ iPhone và 500.000đ phụ kiện thì giảm 300.000đ tiền iPhone; không giảm 400.000đ trên cả giỏ. Nếu mã yêu cầu 2.000.000đ, giỏ này vẫn chưa đủ vì chỉ có 1.500.000đ hàng iPhone.

Khi đặt đơn, hệ thống đọc lại giá/danh mục của sản phẩm và phạm vi voucher dưới transaction; không tin ID/phần tiền giảm do trình duyệt gửi lên. Phạm vi, đối tượng và tiền hàng được áp dụng được chụp vào `donhang`; tiền giảm đã có trường riêng. Sửa voucher hoặc đổi danh mục sản phẩm sau đó không làm tính lại đơn đã đặt, QR hay PayPal của đơn cũ.

Migration chỉ thêm cột: mã cũ giữ **Toàn cửa hàng**, đơn cũ giữ nguyên giá/giảm đã chốt. IPHONE18 được giữ nguyên để chủ shop tự chọn phạm vi trong admin. Sản phẩm/danh mục không còn bán không được chọn cho mã mới; voucher trỏ tới đối tượng đã ngừng bán không tự chuyển thành toàn cửa hàng.
