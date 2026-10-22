# Kịch bản test thủ công luồng bán hàng

Test trên bản local tại http://127.0.0.1:8000/. Dùng sản phẩm, danh mục và voucher có tiền tố `TEST`, tài khoản khách mới bằng email bạn quản lý. Bắt đầu bằng COD. Kịch bản dưới đây có thao tác tạo đơn thử trong CSDL local.

## 1. Chuẩn bị

1. Mở website; nếu chưa chạy được thì mở `setup.bat` trong `D:/phpweb-main` rồi thử lại.
2. Chrome thường: đăng nhập tài khoản quản trị hiện có qua **Đăng nhập**; hệ thống phải tự mở Admin.
3. Chrome ẩn danh: đăng ký/đăng nhập khách mới, hạng Silver, điểm 0. Hai tab thường dùng chung phiên nên không dùng để đóng hai vai.
4. Nếu cần khách thứ hai để thử quyền hoặc tranh mua: dùng Edge/Firefox hoặc hồ sơ trình duyệt riêng. Hai cửa sổ ẩn danh cùng Chrome cũng thường dùng chung phiên.
5. Admin mở thêm tab **Dữ liệu CSDL**, dùng để đọc các bảng. Dùng **Đối chiếu dữ liệu** để kiểm tra các bất thường. Không sửa trực tiếp số liệu để làm cho một ca test đạt.

Mỗi ca ghi: mã ca, bước thực hiện, mã sản phẩm/ID đơn, giá trị trước, kết quả mong đợi, kết quả thực tế, PASS/FAIL và ảnh nếu lỗi. Luôn tải lại trang bên còn lại sau khi có thay đổi; hiện chưa có cơ chế đẩy realtime vào tab đang mở.

## 2. Bộ dữ liệu thử

Tạo danh mục mới `TEST-LUONG-01` để không bị khuyến mãi của danh mục cũ ảnh hưởng.

| Dữ liệu | Giá trị |
| --- | --- |
| Sản phẩm A | Mã `TEST-A-01`; tên `Sản phẩm thử A`; giá 100.000đ; tổng nhập 10; tồn khả dụng 10 |
| Sản phẩm B | Mã `TEST-B-01`; giá 200.000đ; tổng nhập 5; tồn khả dụng 5; cùng danh mục với A |
| Nội dung A | Thương hiệu `TEST BRAND`; thông số `RAM: 8GB` và `Bộ nhớ: 128GB` trên hai dòng; bảo hành `12 tháng`; mô tả có dấu nhận diện `MOTA-TEST-01` |
| Ảnh | Một ảnh chính và hai ảnh phụ JPG/PNG/WebP, mỗi ảnh dưới 5 MB |
| Voucher | `TEST10K01`; giảm tiền 10.000đ; 3 lượt; đang trong thời hạn; mọi hạng; đơn tối thiểu 200.000đ |

Giữ A ở giá 100.000đ, không có khuyến mãi, và khách Silver khi thực hiện các phép tính mẫu. Nếu đã đổi giá/hạng/khuyến mãi thì phải tính lại kết quả tương ứng.

## 3. Admin → CSDL → trang khách

| Mã | Thao tác | Kết quả cần thấy |
| --- | --- | --- |
| SP01 | Admin tạo danh mục | Có trong bảng `danhmuc`, lựa chọn danh mục của form và bộ lọc trang khách |
| SP02 | Admin tạo A và B, tải ảnh, nhập đầy đủ chi tiết | Có bản ghi `sanpham`; đúng giá/tồn/nội dung; có tên file ảnh và album |
| SP03 | Khách mở trang chủ, tìm mã A, lọc danh mục rồi mở chi tiết | Đúng sản phẩm, giá 100.000đ, tồn 10; mô tả/thông số/bảo hành/ảnh khớp Admin; đổi ảnh phụ được |
| SP04 | Xem sản phẩm liên quan của A | Có B; không có chính A; không gắn sản phẩm danh mục khác |
| SP05 | Admin đổi mô tả thành `MOTA-TEST-02`, đổi tên và ảnh; giữ giá | Sau khi khách tải lại trang, các trường mới xuất hiện; mở lại Sửa trong Admin vẫn có dữ liệu đã lưu |
| SP06 | Sửa mô tả, không chọn ảnh mới | Giữ ảnh chính/album cũ |
| SP07 | Thử mã sản phẩm trùng; giá/tồn âm; tồn lớn hơn tổng nhập; file không phải ảnh | Báo lỗi, không tạo sản phẩm sai, dữ liệu cũ không bị mất |
| SP08 | Admin nhập thêm 2 khi sửa | Tổng nhập và tồn cùng tăng 2; có lịch sử kho. Nếu làm ca này, nhớ dùng tồn mới thay cho mốc 10 trong các ca sau |

Không đánh giá chỉ dựa vào dòng “thành công”. Phải tìm bản ghi theo mã sản phẩm, sau đó đọc trang khách.

## 4. Giỏ hàng và Mua ngay

| Mã | Thao tác | Kết quả cần thấy |
| --- | --- | --- |
| GH01 | Bấm Thêm vào giỏ hàng trên thẻ B | Mở chi tiết B để chọn số lượng |
| GH02 | Tại chi tiết B, thêm 1 vào giỏ | Giỏ có đúng 1 B; tồn B vẫn 5 vì chưa đặt đơn |
| GH03 | Tại chi tiết A, chọn 2 rồi Mua ngay | Checkout chỉ có 2 A, không kèm B |
| GH04 | Áp dụng `TEST10K01` ở checkout | Tạm tính 200.000đ; giảm 10.000đ; ship 30.000đ; tổng **220.000đ**. Chưa đặt đơn thì tồn/lượt voucher chưa giảm |
| GH05 | Đặt COD, ghi ID đơn là **Đơn 1** | Chờ xác nhận; tồn A từ 10 xuống 8; voucher từ 3 xuống 2. Giỏ B vẫn còn 1 |
| GH06 | Tải lại trang kết quả hoặc gửi lại cùng form đặt đơn cũ | Không tạo thêm đơn, không trừ kho/voucher lần hai |
| GH07 | Đối chiếu Admin và CSDL | Cùng ID đơn, khách, số lượng, đơn giá, tổng tiền; có `donhang`, `chitietdonhang`, `lichsu_kho`, `lichsu_donhang` |
| GH08 | Sau khi đặt, Admin đổi giá A thành 120.000đ và đổi tên | Giá mới hiện ở trang sản phẩm; đơn cũ vẫn đơn giá 100.000đ và tên lúc đặt. Sau khi kiểm tra, đưa giá A về 100.000đ cho các ca tiếp theo |

## 5. Tách riêng từng nhánh đơn hàng

Không dùng một đơn để vừa hủy vừa giao thành công. Đơn đã kết thúc không được quay lại trạng thái cũ. Trước mỗi ca ghi tồn hiện tại là `S`, lượt voucher là `V`, điểm khách là `P`.

### Đơn 1: khách hủy khi chờ xác nhận

1. Khách vào Lịch sử đơn → Hủy, nhập lý do.
2. Khách và Admin phải cùng thấy Đã hủy, đúng lý do/ID.
3. Với mẫu trên, tồn A về 10, voucher về 3.
4. Tải lại hoặc thử hủy lần hai: không hoàn thêm kho/voucher.

### Đơn 2: Admin hủy sau xác nhận

1. Đặt một đơn COD mới; ghi ID và số lần hủy hiện tại của khách.
2. Admin chuyển Chờ xác nhận → Xác nhận đơn hàng.
3. Admin chuyển Đã hủy, ghi lý do của cửa hàng.
4. Kho được hoàn đúng số lượng đã đặt; số lần hủy của khách không tăng.
5. Khách cũng được hủy ở Xác nhận đơn hàng, trước Đang giao; muốn thử nhánh này thì tạo thêm một đơn riêng.

### Đơn 3: giao thành công → nhận xét → trả hàng bán lại

1. Đặt mới 2 A, dùng voucher mẫu. Nếu khách vẫn Silver/giá chưa đổi, tổng là 220.000đ.
2. Trước khi giao, mở chi tiết A: khách chưa nhận A thì chưa được gửi nhận xét.
3. Admin đi lần lượt **Xác nhận đơn hàng → Đang giao → Đã giao → Thành công**. Tải lại trang khách sau mỗi bước.
4. Ở Đang giao, cả khách và Admin không được hủy trực tiếp. Không được nhảy từ Chờ xác nhận thẳng Thành công.
5. Ở Đã giao hoặc Thành công, khách mở chi tiết A để nhận xét. Nội dung thật xuất hiện; gửi lần hai cho cùng sản phẩm phải bị chặn. Đăng nhập khách khác chưa nhận A thì không được nhận xét.
6. Khi Thành công, điểm tăng `floor(tổng tiền / 1.000)`: ví dụ tăng 220 điểm. Không trừ kho thêm ở bước giao/hoàn tất. Đơn được tính doanh thu.
7. Khách gửi Yêu cầu trả toàn bộ đơn, nhập lý do. Admin thấy yêu cầu, nhưng lúc này kho/voucher/điểm vẫn giữ nguyên.
8. Thử chuyển Trả hàng khi chưa xác nhận đã nhận lại: phải bị chặn.
9. Trong dữ liệu thử, Admin chọn Trả hàng, xác nhận đã nhận và kiểm tra hàng, giữ chọn **Hàng đủ điều kiện bán lại**, rồi cập nhật.
10. Kho cộng lại 2 A; voucher hoàn 1 lượt; điểm của đơn được thu hồi; doanh thu loại đơn trả. Hạng xét lại theo điểm còn lại. Không tự tăng bộ đếm vi phạm trả hàng.
11. COD có trạng thái Chờ hoàn tiền. Với đơn thử không phát sinh tiền thật, dùng tham chiếu `TEST-HOAN-COD-01` để thử thao tác xác nhận; khách phải thấy Đã hoàn tiền và đúng số tiền. Đây là ghi nhận chứng từ, không chuyển tiền ngân hàng.
12. Thử xử lý lặp: không cộng kho/voucher hoặc trừ điểm thêm lần nữa.

### Đơn 4: trả hàng lỗi/cách ly

1. Tạo đơn mới 1 A, đưa tới Đã giao/Thành công, rồi gửi yêu cầu trả.
2. Admin xác nhận đã nhận lại; **bỏ chọn Hàng đủ điều kiện bán lại** trước khi chuyển Trả hàng.
3. Tồn khả dụng không tăng; Hàng lỗi tăng 1; điểm đã cộng bởi đơn được thu hồi.

### Các nhánh bổ sung

| Mã | Ca thử | Kết quả cần thấy |
| --- | --- | --- |
| DH01 | Đơn riêng Đang giao → Bom hàng | Chỉ xử lý sau xác nhận đã nhận lại; hàng bán lại được hoàn kho; không hoàn lặp |
| DH02 | Đơn riêng có yêu cầu trả → Admin từ chối có lý do | Khách thấy phản hồi; không hoàn kho/điểm/tiền |
| DH03 | Khách khác mở ID theo dõi của Đơn 3 | Không được xem hoặc xử lý đơn của người khác |
| DH04 | Khách gửi khiếu nại trên đơn đã nhận | Có nội dung trong hộp thư và mục Khiếu nại khách hàng của Admin |
| DH05 | Admin xóa sản phẩm đã có giao dịch | Bị chặn để giữ lịch sử đơn |

## 6. Voucher và hạng

Tạo các mã thử riêng; không sửa trực tiếp đơn hoặc điểm trong DB để ép kết quả.

| Ca | Kết quả mong đợi |
| --- | --- |
| Mã không tồn tại, hết hạn, chưa tới ngày hoặc 0 lượt | Không áp dụng, không tạo đơn sai/không mất kho |
| Voucher tối thiểu 200.000đ, chỉ mua 1 A giá 100.000đ | Từ chối vì chưa đủ tiền hàng; phí ship không giúp đạt mức tối thiểu |
| Voucher Gold, khách Silver | Không áp dụng |
| Voucher Gold, khách Gold hoặc Diamond | Áp dụng nếu các điều kiện còn lại đều đủ |
| Voucher Diamond, khách Gold | Không áp dụng |
| Miễn ship với khách Silver | Phí ship về 0 |
| Tạo khuyến mãi 10% cho A | A từ 100.000đ xuống 90.000đ trên trang khách và checkout; 3 A + voucher 10.000đ + ship 30.000đ = 290.000đ với khách Silver |
| Khuyến mãi hết ngày | Giá trở lại theo dữ liệu hiện hành, không sửa đơn cũ |
| Hoàn tất đơn để đạt từ 500 điểm / 2.000 điểm | Lần lượt Gold / Diamond; các đơn sau được tính quyền lợi theo hạng mới |

Để thử nhận xét và hạng, dùng tài khoản mới thay vì các khách mẫu đang có sai lệch lịch sử đã ghi trong báo cáo. Muốn thử trả quá 7 ngày mà không chờ thật, cần CSDL thử riêng và điều chỉnh ngày giao của đúng đơn thử; không đổi ngày giao của đơn thật.

## 7. Hai tình huống dễ phát hiện lỗi kho

1. **Hai khách tranh sản phẩm cuối:** tạo sản phẩm TEST khác, tồn 1. Dùng hai trình duyệt đăng nhập hai khách; cả hai mở checkout trước, rồi lần lượt bấm đặt. Chỉ một đơn thành công; khách còn lại báo không đủ hàng, tồn không âm.
2. **Admin mở form cũ:** Admin bấm Sửa A rồi giữ nguyên. Khách đặt 1 A. Admin quay lại form cũ và Lưu. Phải báo tồn đã thay đổi, không ghi tồn cũ đè lên tồn sau mua; tải lại danh sách rồi mở Sửa lại để tiếp tục.

## 8. PayPal: thực hiện sau khi COD đạt

Chỉ tiếp tục khi màn hình thanh toán xác nhận **Sandbox** và bạn có tài khoản thử Sandbox. Nếu đang Live hoặc chưa rõ chế độ, dùng COD cho bài test này.

1. Đặt PayPal: trước khi trả tiền phải là chưa thanh toán/chờ, Admin không được đưa sang giao.
2. Đóng PayPal rồi quay lại: không được tự hiện đã thanh toán. Hủy đơn chưa trả tiền thì kho hoàn đúng một lần.
3. Thanh toán Sandbox thành công: website/đối soát xác nhận đã thanh toán, không chỉ dựa vào việc quay lại website.
4. Refresh/gửi lại bước thanh toán: không tạo capture mới hoặc trừ kho thêm.
5. Với đơn Sandbox đã trả tiền: hủy/trả phải chờ hoàn tiền trên PayPal và đối soát. Sau đó hoàn kho một lần theo điều kiện hàng trả.
6. Để một đơn chưa thanh toán quá 30 phút; mở trang chủ hoặc danh sách đơn Admin: đơn hết hạn được hủy và giải phóng tồn. Khoản đang xử lý hoặc chưa rõ kết quả phải được đối soát, không tự coi là đã hủy an toàn.

## 9. Đối chiếu và ghi lỗi

| Cần kiểm tra | Nơi xem |
| --- | --- |
| Danh mục, chi tiết/ảnh/giá/tồn | `danhmuc`, `sanpham` |
| Tổng tiền, phí, giảm, trạng thái, ngày giao | `donhang` |
| Số lượng, giá và tên lúc mua | `chitietdonhang` |
| Các lần giữ/hoàn/kiểm kê kho | `lichsu_kho` |
| Ai xử lý, lúc nào, lý do | `lichsu_donhang` |
| Lượt, ngày, hạng tối thiểu, tiền tối thiểu | `voucher` |
| Điểm/hạng | `khachhang` |
| Yêu cầu trả, phản hồi, tiền/chứng từ hoàn | `trahang` |
| Nhận xét; thư/khiếu nại | `danhgia`, `email` |
| Trạng thái/capture của PayPal | `thanhtoan_paypal` |

Mẫu ghi lỗi: `GH05 | TEST-A-01 | đơn #... | trước: tồn 10, voucher 3 | mong đợi: tồn 8, voucher 2, tổng 220.000đ | thực tế: ... | ảnh/URL: ...`.

Chỉ đối chiếu các ID vừa tạo. Mục Đối chiếu dữ liệu còn các bản ghi cũ cần xác minh trong [báo cáo luồng](BAO_CAO_LUONG_DU_LIEU.md); không coi chúng là lỗi mới của ca thử. Đơn đã có giao dịch được giữ lại để kiểm tra lịch sử; không xóa/truncate bảng chung để dọn test.

## Kiểm tra lại form sản phẩm sau bản sửa 403 ngày 22/10/2026

1. Mở lại **Quản lý Sản phẩm** từ menu admin để lấy form mới. Không tải lại trang POST đang báo 403 cũ. Nếu tab cũ còn mã JavaScript trước sửa, tải mới một lần bằng Ctrl+F5.
2. Thêm một mã sản phẩm chưa dùng, điền danh mục/giá/tổng nhập/tồn. Thử không ảnh trước; kết quả phải có thông báo **Đã thêm sản phẩm**, một dòng mới trong admin, bản ghi trong **Dữ liệu CSDL → sanpham** và sản phẩm trên trang khách.
3. Sửa chính sản phẩm đó, chọn ảnh JPG/PNG/WebP tối đa 5 MB và tối đa 6 ảnh bổ sung. Khi gửi phải có trạng thái đang tải ảnh rồi đang lưu. Mở mới trang khách để kiểm tra ảnh, mô tả, thông số, giá và tồn.
4. Thử mã trùng: phải nhận thông báo mã đã tồn tại và giữ nội dung form; chọn lại ảnh nếu máy chủ đã xử lý lần gửi trước. Thử ảnh sai loại hoặc quá 5 MB: phải có lỗi rõ ràng và vẫn giữ nội dung văn bản.
5. Chọn sản phẩm khác để Sửa rồi bấm Làm mới: phải trở lại chế độ Thêm và không ghi nhầm ID cũ. Trong một lần gửi, chỉ bấm nút lưu một lần và chờ phản hồi.

Đã thử trực tiếp form không ảnh, ảnh chính + album bằng mã trùng, và lưu ảnh trên sản phẩm có sẵn thành công; dữ liệu sản phẩm gốc đã được khôi phục sau thử. Bộ tự động 168/168 đạt trên CSDL tạm, có ca thêm mới thành công.

## QR chuyển khoản và voucher (bổ sung 22/10/2026)

Đã sửa lỗi áp voucher khi chọn PayPal trả JSON và thêm QR do admin xác nhận. Kịch bản thao tác, hủy/tiền đến muộn/trả/hoàn và điều kiện voucher ở [HUONG_DAN_QR_VA_VOUCHER.md](HUONG_DAN_QR_VA_VOUCHER.md). Bộ mới nhất: 206 kiểm tra luồng và 43 PayPal ngoại tuyến đạt. Đơn #11 trên hosting là đơn thử đã hủy, kho/voucher đã hoàn; không chuyển tiền theo QR của đơn này. Hãy tạo đơn mới khi cần test tiếp.

## Phạm vi voucher

Tạo mã hoặc mở **Sửa giá trị / phạm vi** của mã cũ → nhập mức giảm, chọn toàn cửa hàng/danh mục/sản phẩm → chọn đúng đối tượng → lưu. Tên mã không tự xác định phạm vi hay mức giảm. Ví dụ 20% cho iPhone: giỏ có iPhone và Samsung/phụ kiện chỉ giảm phần iPhone; tiền ngoài phạm vi không góp vào mức tối thiểu. Thử giỏ chỉ có hàng không phù hợp phải bị từ chối, preview không trừ lượt. Đặt đơn rồi đổi giá trị/phạm vi mã: tổng/giảm/QR của đơn cũ phải giữ nguyên; đơn mới áp dụng giá trị/phạm vi mới. Hủy hợp lệ hoàn đúng một lượt mã và kho. Bản mới nhất đạt 252 kiểm tra luồng; 43 kiểm tra PayPal của bản trước vẫn được lưu trong báo cáo. Hướng dẫn đầy đủ ở tài liệu QR/voucher.

## Kiểm tra tiền giảm và số lượt voucher

1. Tạo mã giảm tiền với giá trị 99000: không chỉ nhập tên mã 99K. Thử giá trị 0 hoặc phần trăm 0/101 phải bị từ chối. Riêng miễn ship dùng giá trị 0.
2. Ghi số lượt N và tồn kho trước khi bấm Áp dụng. Sau xem trước: tổng giảm đúng, lượt và tồn không đổi. Ví dụ tiền hàng 200.000đ + ship 30.000đ, mã giảm 99.000đ thì tổng 131.000đ.
3. Đặt đơn thành công: `donhang.tien_giam=99000`, `tong_tien=131000`, lượt còn N−1; QR dùng đúng tổng đã chốt. Lỗi lưu đơn phải giữ lượt N và tồn ban đầu.
4. Hủy chính đơn thử khi chưa chuyển tiền: lượt về N và hoàn kho. Gửi hủy lại không được tăng thành N+1. Chỉ xem đơn, tải lại hoặc bấm Áp dụng nhiều lần không tiêu thêm lượt.
5. Sửa giá trị/phạm vi mã không đặt lại lượt và không tính lại đơn đã lưu. Mã quá hạn vẫn không dùng lại được dù lượt đã được hoàn. Tiền đã thanh toán cần xử lý hoàn tiền riêng theo phương thức.

## Giao diện quản trị đã sắp xếp lại

Xem [HUONG_DAN_ADMIN.md](HUONG_DAN_ADMIN.md) để biết từng mục sidebar dùng khi nào, phân biệt giảm giá sản phẩm/voucher, nhập thêm/kiểm kê và các quyền tài khoản. Tìm kiếm/lọc nằm ngay trên danh sách; các form tạo mới thu gọn, bấm tiêu đề để mở. Công cụ dữ liệu nằm trong một nhóm mở rộng.

Bản cập nhật đạt 279 kiểm tra luồng trên CSDL tạm; đã mở lại 12 trang trên hosting và thử trực tiếp tìm sản phẩm, Sửa/Làm mới, chuyển phạm vi khuyến mãi. Không gửi biểu mẫu nghiệp vụ hoặc tạo đơn/sản phẩm thử mới trên hosting trong đợt chỉnh giao diện này.
