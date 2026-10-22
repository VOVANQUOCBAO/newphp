<?php
include __DIR__ . '/header_admin.php';
$e = static fn(mixed $value): string => htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$adminUrl = 'index.php?khu=admin';
?>
<div class="container-fluid">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
        </div>
        <button class="btn btn-primary rounded-pill px-4" type="button" data-bs-toggle="modal" data-bs-target="#modalThemNV">
            <i class="fas fa-user-plus me-1"></i>Thêm nhân viên
        </button>
    </div>

    <?php if (!empty($thongBao)): ?><div class="alert alert-success"><?=$e($thongBao)?></div><?php endif; ?>
    <?php if (!empty($loi)): ?><div class="alert alert-danger"><?=$e($loi)?></div><?php endif; ?>

    <?php $tableId='staff-table';$searchPlaceholder='Tên, email hoặc vai trò';$statusOptions=['1'=>'Đang hoạt động','0'=>'Đã khóa'];require __DIR__.'/_table_tools.php'; ?>
    <section class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-header bg-white fw-bold">Nhân viên (<?=count($ds_nhanvien)?>)</div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="staff-table">
                <thead><tr><th>ID</th><th>Họ tên</th><th>Email</th><th>Vai trò</th><th>Trạng thái</th><th>Thao tác</th></tr></thead>
                <tbody>
                <?php foreach ($ds_nhanvien as $nhanVien): ?>
                    <tr data-admin-row data-status="<?=(int)$nhanVien['trang_thai']?>">
                        <td><?=$e($nhanVien['id'])?></td>
                        <td><?=$e($nhanVien['ho_ten'])?></td>
                        <td><?=$e($nhanVien['email'])?></td>
                        <td><?=$e(match ($nhanVien['vai_tro']) {
                            'Admin', 'SuperAdmin' => 'Quản trị viên',
                            'QuanLy' => 'Quản lý',
                            default => 'Nhân viên',
                        })?></td>
                        <td><?=$nhanVien['trang_thai'] ? '<span class="badge text-bg-success">Đang hoạt động</span>' : '<span class="badge text-bg-secondary">Đã khóa</span>'?></td>
                        <td>
                            <?php if (nhanvien::laSuperAdmin((string) $nhanVien['vai_tro'])): ?>
                                <span class="text-muted">Tài khoản quản trị được bảo vệ</span>
                            <?php else: ?>
                                <div class="d-flex flex-wrap gap-2">
                                    <form action="<?=$adminUrl?>&amp;act=doivaitronhanvien" method="post" class="d-flex gap-1">
                                        <input type="hidden" name="_csrf" value="<?=$e($csrfToken)?>">
                                        <input type="hidden" name="id" value="<?=$e($nhanVien['id'])?>">
                                        <select class="form-select form-select-sm" name="vai_tro" aria-label="Vai trò nhân viên">
                                            <option value="NhanVien" <?=$nhanVien['vai_tro'] === 'NhanVien' ? 'selected' : ''?>>Nhân viên</option>
                                            <option value="QuanLy" <?=$nhanVien['vai_tro'] === 'QuanLy' ? 'selected' : ''?>>Quản lý</option>
                                            <option value="SuperAdmin">Quản trị viên — toàn quyền</option>
                                        </select>
                                        <button class="btn btn-sm btn-outline-primary">Lưu quyền</button>
                                    </form>
                                    <form action="<?=$adminUrl?>&amp;act=khoanhanvien" method="post">
                                        <input type="hidden" name="_csrf" value="<?=$e($csrfToken)?>">
                                        <input type="hidden" name="id" value="<?=$e($nhanVien['id'])?>">
                                        <input type="hidden" name="trang_thai" value="<?=$nhanVien['trang_thai'] ? '0' : '1'?>">
                                        <button class="btn btn-sm <?=$nhanVien['trang_thai'] ? 'btn-outline-danger' : 'btn-outline-success'?>">
                                            <?=$nhanVien['trang_thai'] ? 'Khóa' : 'Mở khóa'?>
                                        </button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php if ($ds_nhanvien === []): ?><p class="text-muted p-3 mb-0">Chưa có nhân viên.</p><?php endif; ?>
    </section>
</div>

<div class="modal fade" id="modalThemNV" tabindex="-1" aria-labelledby="modalThemNVLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="modalThemNVLabel">Tạo tài khoản nhân viên</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <form action="<?=$adminUrl?>&amp;act=themnhanvien" method="post">
                <input type="hidden" name="_csrf" value="<?=$e($csrfToken)?>">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label" for="staffName">Họ tên</label>
                        <input class="form-control" id="staffName" name="ho_ten" maxlength="150" autocomplete="name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="staffEmail">Email</label>
                        <input class="form-control" id="staffEmail" name="email" type="email" maxlength="150" autocomplete="email" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="staffPassword">Mật khẩu khởi tạo</label>
                        <input class="form-control" id="staffPassword" name="mat_khau" type="password" minlength="12" autocomplete="new-password" required>
                        <div class="form-text">Tối thiểu 12 ký tự. Dùng mật khẩu riêng và chia sẻ an toàn với chủ tài khoản.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="staffRole">Vai trò</label>
                        <select class="form-select" id="staffRole" name="vai_tro" required>
                            <option value="NhanVien">Nhân viên — chỉ xem sản phẩm</option>
                            <option value="QuanLy">Quản lý — xem sản phẩm/đơn/khách, đổi giao diện</option>
                            <option value="SuperAdmin">Quản trị viên — toàn quyền</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-primary">Tạo tài khoản</button>
                </div>
            </form>
        </div>
    </div>
</div>
