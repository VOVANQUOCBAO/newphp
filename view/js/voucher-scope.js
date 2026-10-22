(() => {
    'use strict';
    document.querySelectorAll('.voucher-scope-fields').forEach(group => {
        const scope = group.querySelector('.voucher-scope-select');
        const update = () => group.querySelectorAll('[data-voucher-target]').forEach(target => {
            const selected = target.dataset.voucherTarget === scope.value;
            target.hidden = !selected;
            target.querySelectorAll('select').forEach(input => { input.disabled = !selected; input.required = selected; });
        });
        scope.addEventListener('change', update);
        update();
    });
    const form = document.getElementById('createVoucherForm');
    if (form) {
        const type = form.elements.namedItem('loai');
        const value = form.elements.namedItem('gia_tri');
        const hint = form.querySelector('[data-voucher-value-hint]');
        const updateValue = () => {
            const freeShipping = type.value === 'mienship';
            value.min = freeShipping ? '0' : '0.01';
            value.readOnly = freeShipping;
            if (freeShipping) value.value = '0';
            else if (Number(value.value) === 0) value.value = '';
            if (type.value === 'phantram') value.max = '100';
            else value.removeAttribute('max');
            value.placeholder = type.value === 'phantram' ? 'Ví dụ: 20' : 'Ví dụ: 99000';
            hint.textContent = freeShipping ? 'Miễn phí giao hàng; không giảm tiền hàng.'
                : type.value === 'phantram' ? 'Nhập phần trăm lớn hơn 0, tối đa 100.' : 'Nhập số tiền giảm bằng đồng.';
        };
        type.addEventListener('change', updateValue);
        updateValue();
    }
})();
