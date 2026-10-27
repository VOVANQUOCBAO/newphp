<main class="container py-4" style="max-width:960px">
    <h1 class="h3 mb-4">Hồ sơ của tôi</h1>
    <?php if ($loi): ?><div class="alert alert-danger"><?=$e($loi)?></div><?php endif; ?>
    <?php if ($thongBao): ?><div class="alert alert-success"><?=$e($thongBao)?></div><?php endif; ?>
    <div class="row g-4"><section class="col-md-8"><form class="card p-4" action="index.php?act=luuhoso" method="post">
        <input type="hidden" name="_csrf" value="<?=$e($userCsrf)?>">
        <label class="form-label" for="profileName">Họ tên</label><input id="profileName" class="form-control mb-3" name="ho_ten" maxlength="150" required value="<?=$e($_POST['ho_ten'] ?? $user['ho_ten'])?>" autocomplete="name">
        <label class="form-label" for="profileEmail">Email tài khoản</label><input id="profileEmail" class="form-control mb-1" type="email" value="<?=$e($user['email'])?>" readonly><small class="text-muted mb-3">Email này nhận thông báo của đơn hàng.</small>
        <label class="form-label" for="profilePhone">Số điện thoại</label><input id="profilePhone" class="form-control mb-3" name="sdt" type="tel" maxlength="20" value="<?=$e($_POST['sdt'] ?? $user['sdt'] ?? '')?>" autocomplete="tel">
        <label class="form-label" for="profileBirthday">Ngày sinh</label><input id="profileBirthday" class="form-control mb-3" name="ngay_sinh" type="date" max="<?=date('Y-m-d')?>" value="<?=$e($_POST['ngay_sinh'] ?? $user['ngay_sinh'] ?? '')?>">
        <label class="form-label" for="profileAddress">Địa chỉ giao hàng mặc định</label><textarea id="profileAddress" class="form-control mb-3" name="dia_chi" maxlength="255" rows="3" autocomplete="street-address"><?=$e($_POST['dia_chi'] ?? $user['dia_chi'] ?? '')?></textarea>
        <button class="btn btn-primary">Lưu hồ sơ</button>
    </form></section><aside class="col-md-4"><div class="card p-4"><h2 class="h5">Thành viên <?=$e($user['hang'])?></h2><p><?=(int)$user['diem']?> điểm</p><a class="btn btn-primary mb-3" href="index.php?act=diemthanhvien">Điểm và tiến độ lên hạng</a><a class="mb-2" href="index.php?act=yeuthich">Sản phẩm yêu thích</a><a href="index.php?act=lichsumuahang">Đơn hàng của tôi</a></div></aside></div>
</main>
