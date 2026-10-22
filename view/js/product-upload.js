(() => {
    'use strict';
    const form = document.getElementById('spForm');
    if (!form) return;
    const button = document.getElementById('form-submit');
    const status = document.getElementById('product-upload-status');
    const mainInput = document.getElementById('form_anh');
    const galleryInput = document.getElementById('form_album');
    const uploaded = new WeakMap();
    let submitting = false;

    function readImage(file) {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.onload = () => resolve(String(reader.result).split(',')[1].replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, ''));
            reader.onerror = () => reject(new Error('Không đọc được ảnh đã chọn. Vui lòng chọn lại ảnh.'));
            reader.readAsDataURL(file);
        });
    }

    form.addEventListener('submit', async event => {
        event.preventDefault();
        if (submitting || !form.reportValidity()) return;
        submitting = true;
        button.disabled = true;
        const originalLabel = button.innerHTML;
        status.hidden = false;
        status.className = 'alert alert-info mt-3 mb-0';
        status.textContent = 'Đang chuẩn bị lưu sản phẩm…';
        try {
            const gallery = Array.from(galleryInput.files || []);
            const main = mainInput.files[0] || null;
            if (gallery.length > 6) throw new Error('Tối đa 6 ảnh bổ sung.');
            const files = [...(main ? [main] : []), ...gallery];
            for (const file of files) {
                if (!file.size || file.size > 5 * 1024 * 1024) throw new Error('Mỗi ảnh phải có dung lượng từ 1 byte đến 5 MB.');
                if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) throw new Error('Chỉ nhận ảnh JPG, PNG hoặc WebP.');
            }
            const endpoint = new URL(form.action, window.location.href);
            endpoint.searchParams.set('act', 'taianhsanpham');
            endpoint.searchParams.set('format', 'json');
            const tokens = [];
            for (let index = 0; index < files.length; index++) {
                const file = files[index];
                status.textContent = `Đang tải ảnh ${index + 1}/${files.length}…`;
                if (!uploaded.has(file)) {
                    const body = new URLSearchParams({_csrf: form.elements._csrf.value, image: await readImage(file)});
                    const abort = new AbortController();
                    const timeout = setTimeout(() => abort.abort(), 60000);
                    try {
                        const response = await fetch(endpoint, {method: 'POST', credentials: 'same-origin',
                            headers: {'Accept': 'application/json'}, body, signal: abort.signal});
                        let result;
                        try { result = await response.json(); }
                        catch (_) { throw new Error('Máy chủ chưa nhận được ảnh. Vui lòng thử lại.'); }
                        if (!response.ok || !result.ok || !/^[a-f0-9]{32}$/.test(result.upload_id || '')) {
                            throw new Error(result.loi || 'Không tải được ảnh. Vui lòng tải lại trang nếu phiên đăng nhập đã hết hạn.');
                        }
                        uploaded.set(file, result.upload_id);
                    } finally { clearTimeout(timeout); }
                }
                tokens.push(uploaded.get(file));
            }
            form.elements._image_uploads.value = JSON.stringify({main: main ? tokens.shift() : null, gallery: tokens});
            // Native URL-encoded POST keeps normal redirects, role checks and CSRF validation.
            mainInput.disabled = true;
            galleryInput.disabled = true;
            form.enctype = 'application/x-www-form-urlencoded';
            status.textContent = 'Đang lưu sản phẩm…';
            HTMLFormElement.prototype.submit.call(form);
        } catch (error) {
            status.className = 'alert alert-danger mt-3 mb-0';
            status.textContent = (error.name === 'AbortError' ? 'Tải ảnh quá lâu. Vui lòng thử lại.' : error.message) + ' Thông tin đã nhập vẫn được giữ trên biểu mẫu.';
            mainInput.disabled = false;
            galleryInput.disabled = false;
            button.innerHTML = originalLabel;
            button.disabled = false;
            submitting = false;
        }
    });
})();
