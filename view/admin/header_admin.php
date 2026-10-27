<?php
require_once __DIR__ . '/../helpers.php';
$admin_base_url=view_helper::shopBase().'/view/';
$admin_ctrl_url=view_helper::shopBase().'/index.php?khu=admin';
$user_home_url=view_helper::shopBase().'/index.php?act=trangchu';
$adminRole=(string)($adminRole ?? ($_SESSION['admin']['vai_tro'] ?? ''));
$isSuperAdmin=$isSuperAdmin ?? in_array($adminRole,['Admin','SuperAdmin'],true);
$canManageStore=$canManageStore ?? in_array($adminRole,['Admin','SuperAdmin','QuanLy','NhanVien'],true);
$isManager=$isManager ?? $adminRole==='QuanLy';
$isEmployee=$isEmployee ?? $adminRole==='NhanVien';
$roleLabel=match($adminRole){'Admin','SuperAdmin'=>'Quản trị viên','QuanLy'=>'Quản lý','NhanVien'=>'Nhân viên',default=>'Quản trị'};
$adminEscape=static fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
$adminPages=[
    'sanpham'=>['Sản phẩm & danh mục','Bán hàng','Thêm hàng, cập nhật ảnh và nội dung, theo dõi tồn kho.',[
        'Tạo danh mục trước, sau đó mở Thêm sản phẩm. Giá, ảnh, mô tả và thông số đã lưu được hiển thị ở cửa hàng.',
        'Bấm Sửa tại sản phẩm cần thay đổi. Dùng Nhập thêm để tăng kho; Kiểm kê chỉ dùng khi cần điều chỉnh giảm tồn thực tế.',
        'Tồn khả dụng chưa gồm hàng đã giữ cho đơn. Đã bán chỉ tính đơn thành công.',
        'Xóa hàng đã có giao dịch sẽ chuyển sang Đã ngừng bán, giữ lịch sử đơn và cho phép mở bán lại. Hàng chưa có giao dịch được xóa khỏi hệ thống.']],
    'nhapkho'=>['Lịch sử nhập hàng','Bán hàng','Theo dõi ngày nhập và số lượng của từng đợt.', ['Nhập thêm tại sản phẩm để ghi nhận đợt nhập mới. Lịch sử cũ được giữ nguyên.']],
    'thongbao'=>['Email thanh toán','Quản trị','Theo dõi thư cảm ơn sau khi đã nhận tiền.', ['Email tài khoản khách là địa chỉ nhận. Cửa hàng cần cấu hình dịch vụ gửi thư.']],
    'donhang'=>['Đơn hàng & thanh toán','Bán hàng','Xử lý đơn, kiểm tra tiền nhận, hủy và tiếp nhận trả hàng.',[
        'Bấm mã đơn để xem sản phẩm, tổng tiền và lịch sử. Chọn thao tác trước khi bấm Cập nhật.',
        'QR: đối soát giao dịch ngân hàng rồi xác nhận đã nhận tiền. Khách báo chuyển khoản chưa có nghĩa là cửa hàng đã nhận tiền.',
        'Hủy trước khi giao; đơn đã thu tiền cần hoàn tiền và đối soát trước. Trả hàng cần yêu cầu của khách, nhận lại và kiểm tra hàng. Kho và lượt voucher được hoàn khi xử lý hợp lệ.']],
    'khuyenmai'=>['Giảm giá sản phẩm','Ưu đãi','Tự giảm giá niêm yết theo thời gian; khách không cần nhập mã.',[
        'Chọn một sản phẩm hoặc một danh mục, nhập phần trăm giảm và thời hạn. Chỉ chọn đối tượng đúng với phạm vi áp dụng.',
        'Nếu nhiều chương trình trùng nhau, sản phẩm dùng mức giảm cao nhất, không cộng dồn các phần trăm.',
        'Mã giảm giá ở mục Voucher áp dụng thêm khi thanh toán trên giá hàng đã khuyến mãi. Đơn đã đặt giữ giá đã chốt.']],
    'voucher'=>['Voucher (mã giảm giá)','Ưu đãi','Khách nhập mã tại thanh toán; có thể giới hạn ngày, hạng, lượt và hàng áp dụng.',[
        'Giảm tiền: nhập 99000 để giảm 99.000đ. Phần trăm: nhập 20 để giảm 20%. Miễn ship dùng giá trị 0.',
        'Chọn toàn cửa hàng, danh mục hoặc sản phẩm. Tiền hàng tối thiểu chỉ tính phần hàng thuộc phạm vi đã chọn, sau khuyến mãi sản phẩm.',
        'Áp dụng chỉ xem tổng, chưa trừ lượt. Đặt đơn thành công giữ một lượt; hủy/trả hợp lệ hoàn đúng một lần. Sửa mã không thay đổi đơn đã đặt.']],
    'khachhang'=>['Khách hàng','Khách hàng','Xem tài khoản, hạng thành viên và lịch sử mua hàng.',[
        'Khóa hoặc mở khóa tài khoản tại đúng dòng khách hàng; kiểm tra lịch sử trước khi xử lý.',
        'Hủy/trả hàng không mặc nhiên là vi phạm. Số lần được ghi để đối chiếu theo từng trường hợp.',
        'Gửi thư sinh nhật là tác vụ riêng: lọc ngày sinh, nhập nội dung rồi gửi. Không tự tạo voucher hoặc chuyển quà.']],
    'danhgia'=>['Đánh giá khách mua','Khách hàng','Xem và kiểm duyệt nhận xét của khách đã nhận sản phẩm.',[
        'Ẩn nội dung không phù hợp hoặc hiển thị lại khi đã kiểm tra. Không khóa khách chỉ vì đánh giá ít sao.',
        'Khách phải có đơn đã giao hoặc thành công và chỉ được nhận xét mỗi sản phẩm một lần.']],
    'doanhthu'=>['Doanh thu','Quản trị','Theo dõi doanh thu đơn thành công và tình hình bán hàng.',[
        'Tuần / Tháng / Năm là cách nhóm dữ liệu theo kỳ, không phải bộ lọc chỉ lấy kỳ hiện tại.',
        'Doanh thu chỉ cộng đơn thành công. Số tiền chờ thanh toán hoặc đơn đã hủy/trả không được tính là doanh thu.']],
    'nhanvien'=>['Nhân viên & quyền','Quản trị','Quản lý tài khoản và quyền truy cập khu vực quản trị.',[
        'Nhân viên và quản lý được xử lý sản phẩm, kho, đơn, khách hàng, ưu đãi và báo cáo.',
        'Chỉ quản trị viên được tạo/cấp quyền quản trị. Tài khoản quản trị được bảo vệ khỏi nhân viên/quản lý sửa hoặc khóa.']],
    'giaodien'=>['Giao diện cửa hàng','Quản trị','Chọn chủ đề, phông chữ và nền của trang bán hàng.',[
        'Chọn các tùy chọn, xem trước rồi lưu. Dùng Xem cửa hàng ở thanh trên để kiểm tra kết quả.']],
    'doimatkhau'=>['Đổi mật khẩu','Tài khoản','Đổi mật khẩu cho chính tài khoản đang đăng nhập.',[
        'Nhập mật khẩu mới và xác nhận giống nhau. Việc này không đổi mật khẩu của nhân viên khác.']],
    'kiemtra'=>['Kiểm tra sai lệch','Công cụ dữ liệu','Tìm đơn, tồn kho hoặc thanh toán cần kiểm tra lại.',[
        'Xem số lượng và ID ở từng cảnh báo, rồi mở nghiệp vụ tương ứng để kiểm tra chứng từ.',
        'Báo cáo không tự sửa dữ liệu lịch sử. Số lượng bằng 0 nghĩa là không phát hiện sai lệch theo quy tắc đó.']],
    'csdl'=>['Tra cứu dữ liệu gốc','Công cụ dữ liệu','Xem bản ghi để đối chiếu khi cần kiểm tra chi tiết.',[
        'Chọn bảng để xem. Trang này chỉ đọc; thêm hoặc sửa tại Sản phẩm, Đơn hàng, Voucher và các mục nghiệp vụ.',
        'Mật khẩu và token được ẩn. Kiểm tra sai lệch dùng để tìm vấn đề; tra cứu dữ liệu gốc dùng để xem từng bản ghi.']],
];
$adminPageKey=(string)($act ?? $_GET['act'] ?? 'sanpham');
if($adminPageKey==='chitietdonhang') $adminPageKey='donhang';
$adminPage=$adminPages[$adminPageKey] ?? $adminPages['sanpham'];
$isOrderDetail=($act ?? '')==='chitietdonhang';
if ($isOrderDetail) $adminPage[0]='Chi tiết đơn hàng'.(!empty($chitiet['id'])?' #'.(int)$chitiet['id']:'');
$adminNavGroups=[
    'Bán hàng'=>[['sanpham','fa-boxes-stacked','Sản phẩm & danh mục',true],['nhapkho','fa-warehouse','Lịch sử nhập hàng',$canManageStore],['donhang','fa-truck-fast','Đơn hàng & thanh toán',$canManageStore]],
    'Ưu đãi'=>[['khuyenmai','fa-tags','Giảm giá sản phẩm',$canManageStore],['voucher','fa-ticket','Voucher (mã giảm giá)',$canManageStore]],
    'Khách hàng'=>[['khachhang','fa-users','Khách hàng',$canManageStore],['danhgia','fa-comment-dots','Đánh giá khách mua',$canManageStore]],
    'Quản trị'=>[['doanhthu','fa-chart-line','Doanh thu',$canManageStore],['nhanvien','fa-user-gear','Nhân viên & quyền',$canManageStore],['giaodien','fa-palette','Giao diện cửa hàng',$canManageStore],['thongbao','fa-envelope','Email thanh toán',$canManageStore]],
];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?=$adminEscape($adminPage[0])?> · BP Store</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?=$admin_base_url?>css/theme.css?v=20261023-bpstore">
    <link rel="stylesheet" href="<?=$admin_base_url?>css/admin.css?v=20261023-order-detail">
    <link rel="stylesheet" href="<?=$admin_base_url?>css/storefront.css?v=20261027-design-1">
    <link rel="stylesheet" href="<?=$admin_base_url?>css/admin-refined.css?v=20261027-design-1">
    <script src="<?=$admin_base_url?>js/admin.js?v=20261022-clean" defer></script>
</head>
<body class="admin-layout">
<a class="visually-hidden-focusable admin-skip" href="#admin-main">Đến nội dung</a>
<aside class="admin-sidebar" aria-label="Điều hướng quản trị">
    <div class="admin-brand"><img src="<?=$admin_base_url?>image/bpstore-logo.png" alt="BP Store" width="58" height="44"><div><strong>BP Store</strong><span>Quản lý cửa hàng</span></div>
        <button class="btn btn-outline-light btn-sm d-lg-none ms-auto" type="button" data-bs-toggle="collapse" data-bs-target="#adminNavigation" aria-expanded="false" aria-controls="adminNavigation">Menu</button>
    </div>
    <div id="adminNavigation" class="collapse d-lg-flex admin-navigation">
        <nav class="admin-nav" aria-label="Chức năng">
        <?php foreach($adminNavGroups as $group=>$items): $visible=array_filter($items,static fn($item)=>$item[3]); if(!$visible) continue; ?>
            <div class="admin-nav-group"><p class="admin-nav-label"><?=$adminEscape($group)?></p>
            <?php foreach($visible as [$key,$icon,$label]): $active=$adminPageKey===$key; ?>
                <a href="<?=$admin_ctrl_url?>&amp;act=<?=$key?>" class="admin-nav-link <?=$active?'active':''?>" <?=$active?'aria-current="page"':''?> title="<?=$adminEscape($adminPages[$key][2])?>"><i class="fa-solid <?=$icon?>" aria-hidden="true"></i><span><?=$adminEscape($label)?></span></a>
            <?php endforeach; ?></div>
        <?php endforeach; ?>
        <?php if($canManageStore): ?><details class="admin-advanced" <?=in_array($adminPageKey,['csdl','kiemtra'],true)?'open':''?>>
            <summary>Công cụ dữ liệu</summary>
            <?php foreach(['kiemtra'=>'Kiểm tra sai lệch','csdl'=>'Tra cứu dữ liệu gốc'] as $key=>$label): $active=$adminPageKey===$key; ?><a class="admin-nav-link <?=$active?'active':''?>" <?=$active?'aria-current="page"':''?> href="<?=$admin_ctrl_url?>&amp;act=<?=$key?>"><i class="fa-solid <?=$key==='csdl'?'fa-database':'fa-check-double'?>" aria-hidden="true"></i><span><?=$label?></span></a><?php endforeach; ?>
        </details><?php endif; ?>
        </nav>
        <div class="admin-account"><strong><?=$adminEscape($_SESSION['admin']['hoten'] ?? 'Tài khoản')?></strong><span><?=$roleLabel?></span><div class="admin-account-actions">
            <a href="<?=$admin_ctrl_url?>&amp;act=doimatkhau" <?= $adminPageKey==='doimatkhau'?'aria-current="page"':''?>>Đổi mật khẩu</a>
            <form action="<?=$admin_ctrl_url?>&amp;act=dangxuat" method="post"><input type="hidden" name="_csrf" value="<?=$adminEscape($csrfToken)?>"><button type="submit">Đăng xuất</button></form>
        </div></div>
    </div>
</aside>
<main class="admin-content" id="admin-main">
<?php if ($isSuperAdmin && !$storeToolsReady): ?>
    <form class="alert alert-info m-3" action="index.php?khu=admin&amp;act=khoitaotienich" method="post"><input type="hidden" name="_csrf" value="<?=$adminEscape($csrfToken)?>"><p>Bật cấu trúc dữ liệu cho hồ sơ, yêu thích, ngày nhập và thư cảm ơn thanh toán. Dữ liệu và đơn cũ được giữ nguyên.</p><button class="btn btn-primary">Bật các tính năng mới</button></form>
<?php endif; ?>
    <div class="admin-topbar"><span>Quản trị <span aria-hidden="true">/</span> <?=$adminEscape($adminPage[0])?></span><div><time datetime="<?=date('Y-m-d')?>"><?=date('d/m/Y')?></time><a class="btn btn-outline-primary btn-sm" href="<?=$user_home_url?>" target="_blank" rel="noopener">Xem cửa hàng <i class="fa-solid fa-arrow-up-right-from-square ms-1" aria-hidden="true"></i></a></div></div>
    <div class="admin-page">
        <?php if (!$isOrderDetail): ?><header class="admin-page-heading"><h1><?=$adminEscape($adminPage[0])?></h1><p><?=$adminEscape($adminPage[2])?></p>
            <details class="admin-guide"><summary>Cách dùng mục này</summary><ol><?php foreach($adminPage[3] as $step): ?><li><?=$adminEscape($step)?></li><?php endforeach; ?></ol></details>
        </header><?php endif; ?>
