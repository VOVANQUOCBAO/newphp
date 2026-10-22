<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-12 col-md-7 col-lg-5">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden border-blue">
                <div class="card-header bg-primary text-white text-center py-4">
                    <div class="display-6 mb-1"><i class="fas fa-sign-in-alt"></i></div>
                    <h4 class="fw-bold mb-0 text-uppercase">Đăng nhập</h4>
                    <p class="small opacity-75 mb-0">Chào mừng bạn quay lại với ĐiệnThoạiZ</p>
                </div>

                <div class="card-body p-4 p-md-5">
                    
                    <!-- Thông báo Thành công -->
                    <?php if (!empty($thongBao)): ?>
                        <div class="alert alert-success rounded-3 small d-flex align-items-center gap-2 shadow-sm">
                            <i class="fas fa-check-circle fs-4 text-success"></i>
                            <div><?=$thongBao?></div>
                        </div>
                    <?php endif; ?>

                    <!-- Thông báo Lỗi -->
                    <?php if (!empty($loi)): ?>
                        <div class="alert alert-danger rounded-3 small d-flex align-items-center gap-2 shadow-sm">
                            <i class="fas fa-exclamation-triangle fs-4 text-danger"></i>
                            <div><?=$loi?></div>
                        </div>
                    <?php elseif (isset($_GET['error']) && $_GET['error'] == 'locked'): ?>
                        <div class="alert alert-danger rounded-3 small d-flex align-items-center gap-2">
                            <i class="fas fa-ban fs-4"></i>
                            <div>
                                <strong>Tài khoản đã bị tạm khóa!</strong><br>
                                Tài khoản của bạn đã bị quản trị viên khóa do vi phạm chính sách hoặc bom hàng/trả hàng. Vui lòng liên hệ hotline 1800.6868.
                            </div>
                        </div>
                    <?php elseif (isset($error_msg)): ?>
                        <div class="alert alert-danger rounded-3 small">
                            <i class="fas fa-exclamation-circle me-1"></i> <?=$error_msg?>
                        </div>
                    <?php endif; ?>

                    <!-- Send the signed token from our own origin so hosting redirects cannot discard Google's cross-site POST. -->
                    <div class="mb-4">
                        <?php if (!empty($googleClientId)): ?>
                            <div id="g_id_onload"
                                 data-client_id="<?=htmlspecialchars($googleClientId, ENT_QUOTES, 'UTF-8')?>"
                                 data-context="signin"
                                 data-ux_mode="popup"
                                 data-callback="submitGoogleCredential"
                                 data-nonce="<?=htmlspecialchars($googleNonce, ENT_QUOTES, 'UTF-8')?>"
                                 data-auto_prompt="false"></div>
                            <div class="g_id_signin" data-type="standard" data-shape="pill" data-theme="outline" data-text="continue_with" data-size="large" data-width="320"></div>
                            <form id="google-signin-form" action="index.php?act=google_login" method="POST" hidden><input type="hidden" name="_csrf" value="<?=$e($userCsrf)?>">
                                <input type="hidden" name="credential" value="">
                                <input type="hidden" name="_google_csrf" value="<?=htmlspecialchars($googleCsrfToken, ENT_QUOTES, 'UTF-8')?>">
                            </form>
                            <script>
                                function submitGoogleCredential(response) {
                                    if (!response || typeof response.credential !== 'string' || !response.credential) return;
                                    const form = document.getElementById('google-signin-form');
                                    form.elements.credential.value = response.credential;
                                    form.submit();
                                }
                            </script>
                            <script src="https://accounts.google.com/gsi/client" async defer></script>
                        <?php else: ?>
                            
                        <?php endif; ?>
                        <div class="position-relative text-center my-3">
                            <hr class="text-muted">
                            <span class="position-absolute top-50 start-50 translate-middle bg-white px-3 small text-muted">hoặc đăng nhập bằng tài khoản</span>
                        </div>
                    </div>

                    <!-- Form Đăng nhập -->
                    <form action="index.php?act=dangnhap" method="POST"><input type="hidden" name="_csrf" value="<?=$e($userCsrf)?>">
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Tên đăng nhập hoặc Email</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="fas fa-user text-muted"></i></span>
                                <input type="text" name="username" class="form-control" placeholder="Nhập email hoặc tên đăng nhập của bạn" value="<?=htmlspecialchars($_POST['username'] ?? $_POST['email'] ?? '')?>" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="d-flex justify-content-between">
                                <label class="form-label fw-semibold small">Mật khẩu</label>
                            </div>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="fas fa-lock text-muted"></i></span>
                                <input type="password" name="password" class="form-control" placeholder="••••••••" autocomplete="current-password" required>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 rounded-pill py-2 fw-bold shadow">
                            <i class="fas fa-arrow-right-to-bracket me-1"></i> ĐĂNG NHẬP
                        </button>
                    </form>

                    <div class="text-center mt-4 small text-secondary">
                        Chưa có tài khoản? <a href="index.php?act=dangky" class="text-primary fw-bold text-decoration-none">Đăng ký tài khoản</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
