# Hướng dẫn khu vực quản trị

Bản cập nhật ngày 22/10/2026. Mỗi trang có mô tả ngắn và mục **Cách dùng mục này**. Sidebar chỉ hiện chức năng tài khoản được phép dùng.

| Nhóm | Mục | Dùng khi nào |
|---|---|---|
| Bán hàng | Sản phẩm & danh mục | Tạo danh mục, thêm/sửa sản phẩm, ảnh, nội dung và tồn kho |
| Bán hàng | Đơn hàng & thanh toán | Xem đơn, đối soát QR/PayPal, cập nhật giao hàng, hủy và trả |
| Ưu đãi | Giảm giá sản phẩm | Giá giảm sẵn tại cửa hàng trong thời gian chương trình; khách không cần mã |
| Ưu đãi | Voucher (mã giảm giá) | Khách nhập mã khi thanh toán, có điều kiện ngày/hạng/lượt/hàng áp dụng |
| Khách hàng | Khách hàng | Xem tài khoản, hạng/điểm, lịch sử đơn; khóa/mở khóa khi cần |
| Khách hàng | Đánh giá khách mua | Kiểm duyệt và hiển thị lại nhận xét; không khóa khách chỉ vì ít sao |
| Quản trị | Doanh thu | Tổng hợp đơn thành công theo tuần/tháng/năm và xem hàng bán chạy/chậm |
| Quản trị | Nhân viên & quyền | Tạo tài khoản, chọn vai trò, khóa/mở tài khoản nhân viên |
| Quản trị | Giao diện cửa hàng | Chọn chủ đề, phông chữ và nền trang bán hàng |
| Công cụ dữ liệu | Kiểm tra sai lệch | Tìm các ID cần đối chiếu chứng từ, đơn, kho và thanh toán |
| Công cụ dữ liệu | Tra cứu dữ liệu gốc | Chọn bảng để xem bản ghi; chỉ đọc, không sửa trực tiếp |
| Chân sidebar | Đổi mật khẩu / Đăng xuất | Thao tác với chính tài khoản đang đăng nhập |

## Sản phẩm, ảnh và kho

Danh sách là phần chính. Mở **Thêm sản phẩm** khi cần nhập mới; **Quản lý danh mục** mở danh sách danh mục trong cùng trang.

- Thêm mới: nhập mã, tên, danh mục, giá và **Số lượng nhập ban đầu**. Tồn ban đầu mặc định bằng số lượng này, không cần nhập lặp hai ô.
- Ảnh: tải ảnh chính, tối đa 6 ảnh bổ sung. Nhập thương hiệu, bảo hành, thông số và mô tả ở nhóm nội dung cho khách.
- Tên ảnh có sẵn và hạn sử dụng nằm trong **Tùy chọn khác**. Điện thoại thông thường không cần hạn sử dụng.
- Sửa: tổng nhập chỉ xem; **Nhập thêm hàng** tăng tổng nhập và tồn. Không phải nhập lại số tồn để sửa nội dung.
- **Điều chỉnh giảm tồn sau kiểm kê** chỉ mở khi cần ghi chênh lệch thực tế. Không cộng hàng đang giữ cho đơn vào tồn khả dụng. Nếu tồn đã đổi do giao dịch khác, tải lại trước khi lưu.
- **Làm mới** đưa form về thêm mới, bỏ ID cũ và vô hiệu hóa phần nhập thêm/kiểm kê.

## Hai loại ưu đãi

**Giảm giá sản phẩm:** chọn một sản phẩm hoặc một danh mục. Form chỉ hiện đối tượng của phạm vi đang chọn; phía máy chủ cũng bỏ ID thừa. Mức giảm phải lớn hơn 0 và không quá 100%. Chương trình giống hệt đối tượng/mức giảm/thời hạn bị chặn để tránh tạo trùng. Có thể bấm **Sửa** để cập nhật chương trình hiện có.

Nếu nhiều chương trình cùng áp dụng, dùng phần trăm cao nhất, không cộng dồn. Thời gian tính cả ngày bắt đầu và ngày kết thúc theo giờ Việt Nam. Đơn đã đặt giữ nguyên giá.

Danh sách có trạng thái đang chạy, sắp bắt đầu, hết hạn hoặc **Ngừng áp dụng** khi sản phẩm/danh mục đích không còn bán. Còn thời hạn không có nghĩa là hàng đã ngừng bán vẫn được áp dụng.

**Voucher:** khách chủ động nhập mã. Mức tối thiểu và phần tiền giảm được tính trên hàng thuộc phạm vi chọn, sau khuyến mãi sản phẩm. Xem tổng không trừ lượt; lưu đơn thành công giữ một lượt; hủy/trả hợp lệ hoàn đúng một lần. Mã vẫn phải còn hạn và đúng điều kiện để dùng lại.

## Đơn và thanh toán

Tìm theo mã đơn/tên/điện thoại hoặc lọc trạng thái. Bấm mã đơn để xem chi tiết.

- Mặc định **Chọn thao tác…**, không chọn sẵn hủy hoặc giao hàng. Chỉ khi chọn hủy/bom/trả mới cần lý do; nhận hàng/nhập lại kho chỉ hiện ở bom/trả.
- QR cần kiểm tra giao dịch trong ngân hàng trước khi xác nhận. Không coi nút khách báo chuyển là bằng chứng đã nhận tiền.
- Đơn đã thu tiền phải hoàn/đối soát theo phương thức trước khi hủy. Đơn đã giao dùng luồng trả hàng, không hủy ngược tùy ý.
- Lựa chọn PayPal trong form tuân theo cùng điều kiện của máy chủ: chưa thu tiền không được đưa sang giao; đã thu tiền không được hủy/trả trước khi đối soát hoàn.
- Xem hướng dẫn chi tiết: [QR và voucher](HUONG_DAN_QR_VA_VOUCHER.md).

## Khách hàng, nhận xét và thư

Tổng giá trị các đơn đã tạo trong danh sách khách gồm cả đơn hủy/trả, khác với doanh thu hoặc chi tiêu thành công. Hạng/điểm được xử lý theo đơn thành công. Có lịch sử hủy/trả không mặc nhiên là vi phạm.

Nhận xét có thể ẩn và hiển thị lại. Ẩn nhận xét không khóa tài khoản. Điều kiện hiển thị công khai vẫn kiểm tra khách đã có đơn nhận sản phẩm; khôi phục nhận xét không bỏ qua điều kiện mua hàng.

**Gửi thư theo ngày sinh** là tác vụ riêng, mặc định thu gọn. Bắt buộc có ít nhất một điều kiện ngày/tháng/năm hợp lệ và nội dung. Không tự tạo voucher hoặc chuyển quà. Không bỏ trống toàn bộ điều kiện để gửi nhầm tất cả khách.

## Quyền truy cập

| Vai trò | Quyền |
|---|---|
| Quản trị viên (Admin / SuperAdmin) | Toàn bộ nghiệp vụ quản trị |
| Quản lý | Xem sản phẩm, đơn, khách; sửa giao diện; đổi mật khẩu của mình |
| Nhân viên | Xem sản phẩm và đổi mật khẩu của mình |

Ẩn menu không thay cho kiểm tra quyền: máy chủ vẫn kiểm tra từng hành động, phương thức POST và CSRF. Trang mật khẩu mở bằng GET; lưu mật khẩu cần POST có CSRF. Bản cập nhật này không tự thay đổi tài khoản, vai trò hay mật khẩu.

## Kiểm tra sau cập nhật

1. Mở mới trang quản trị; thử tìm kiếm có/không dấu và lọc trạng thái. Danh sách không phù hợp phải báo không có kết quả.
2. Mở Thêm sản phẩm, Sửa một hàng rồi Làm mới: kiểm tra ID/mode và các ô kho không bị dùng nhầm. Chưa bấm lưu nếu chỉ xem thử.
3. Trong giảm giá, đổi phạm vi sản phẩm/danh mục: chỉ có một đối tượng hoạt động. Bấm Sửa nạp đúng giá trị cũ; đổi sang tạo mới xóa ID.
4. Trong đơn, chưa chọn thao tác không thể gửi cập nhật. Chọn hủy hiện lý do; chọn bom/trả hiện nhận hàng và điều kiện nhập lại.
5. Tại nhận xét, kiểm tra Ẩn / Hiển thị lại; không còn nút gộp khóa khách vì điểm thấp.
6. Mở Công cụ dữ liệu; tra cứu bảng và kiểm tra sai lệch là hai việc riêng. Chỉ dùng trang nghiệp vụ để sửa dữ liệu.

Bộ kiểm thử tự động mới: 279 ca luồng trên CSDL tạm, gồm thao tác kho đơn giản hóa, khuyến mãi, nhận xét, thư, 12 trang và quyền từng vai trò. CSDL ứng dụng được đối chiếu toàn bộ giá trị và số bản ghi sau test. Bản sao triển khai và kết quả được lưu tại `D:/phpweb-hosting-sync/20261022-admin-clean/`.
