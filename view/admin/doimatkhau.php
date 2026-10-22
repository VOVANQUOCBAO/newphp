<?php
require_once __DIR__ . '/header_admin.php';
?>

<div class="container-fluid px-0">

    <!-- Thông báo thành công / lỗi -->
    <?php if (!empty($thongBao)): ?>
        <div class="alert alert-success rounded-4 d-flex align-items-center gap-2 shadow-sm mb-4">
            <i class="fas fa-check-circle fs-4 text-success flex-shrink-0"></i>
            <div>
                <strong>Thành công!</strong> <?=$thongBao?>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($loi)): ?>
        <div class="alert alert-danger rounded-4 d-flex align-items-center gap-2 shadow-sm mb-4">
            <i class="fas fa-exclamation-triangle fs-4 text-danger flex-shrink-0"></i>
            <div>
                <strong>Lỗi:</strong> <?=$loi?>
            </div>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Form đổi mật khẩu -->
        <div class="col-12 col-lg-7">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="fw-bold text-dark mb-0">
                        <i class="fas fa-lock text-primary me-2"></i> Mật khẩu mới
                    </h6>
                </div>
                <div class="card-body p-4">
                    <form action="<?=$admin_ctrl_url?>&act=doimatkhau" method="POST">
                        <input type="hidden" name="_csrf" value="<?=$e($csrfToken)?>">
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Tài khoản đang đăng nhập</label>
                            <input type="text" class="form-control bg-light" value="<?=$e($_SESSION['admin']['hoten'] ?? $_SESSION['admin']['ho_ten'] ?? $_SESSION['admin']['email'] ?? '')?> · <?=$e($_SESSION['admin']['email'] ?? '')?>" readonly>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Mật khẩu mới <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="fas fa-key text-muted"></i></span>
                                <input type="password" name="mat_khau_moi" id="matKhauMoi" class="form-control" minlength="12" autocomplete="new-password" placeholder="Ít nhất 12 ký tự" required>
                                <button class="btn btn-outline-secondary" type="button" aria-label="Hiện hoặc ẩn mật khẩu mới" onclick="togglePassword('matKhauMoi')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="form-text small text-muted">Dùng mật khẩu duy nhất, tối thiểu 12 ký tự.</div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold small">Nhập lại mật khẩu mới <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="fas fa-check-double text-muted"></i></span>
                                <input type="password" name="xac_nhan_mat_khau" id="xacNhanMatKhau" class="form-control" placeholder="Nhập lại mật khẩu mới vừa nhập" required>
                                <button class="btn btn-outline-secondary" type="button" aria-label="Hiện hoặc ẩn xác nhận mật khẩu" onclick="togglePassword('xacNhanMatKhau')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary rounded-pill px-4 py-2 fw-bold shadow-sm">
                                <i class="fas fa-save me-2"></i> Lưu mật khẩu
                            </button>
                            <a href="<?=$admin_ctrl_url?>&act=sanpham" class="btn btn-light rounded-pill px-3 py-2 text-muted">
                                Hủy bỏ
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

</div></div>
<script>function togglePassword(id) {const input=document.getElementById(id);input.type=input.type==='password'?'text':'password';}</script>
