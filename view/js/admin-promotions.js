(() => {
    'use strict';
    const form=document.getElementById('promotionForm');if(!form)return;
    const card=document.getElementById('promotion-form-card');
    const updateScope=()=>form.querySelectorAll('[data-promo-target]').forEach(group=>{
        const selected=group.dataset.promoTarget===form.elements.loai.value;
        group.hidden=!selected;group.querySelector('select').disabled=!selected;group.querySelector('select').required=selected;
    });
    const fill=(data)=>{
        Object.entries(data).forEach(([key,value])=>{const field=form.elements.namedItem(key);if(field && field.name!=='_csrf')field.value=value ?? '';});
        const editing=Number(data.id)>0;
        form.elements.id.disabled=!editing;
        form.action='index.php?khu=admin&act='+(editing?'suakhuyenmai':'themkhuyenmai');
        document.getElementById('promotion-form-title').textContent=editing?'Sửa chương trình giảm giá':'Tạo chương trình giảm giá';
        document.getElementById('promotion-submit').textContent=editing?'Lưu thay đổi':'Tạo chương trình';
        updateScope();
    };
    document.querySelectorAll('[data-promotion]').forEach(button=>button.addEventListener('click',()=>{
        fill(JSON.parse(button.dataset.promotion));card.open=true;card.scrollIntoView({behavior:'smooth',block:'start'});form.elements.ten_km.focus({preventScroll:true});
    }));
    form.elements.loai.addEventListener('change',updateScope);
    document.getElementById('promotion-reset').addEventListener('click',()=>{form.reset();fill({id:''});});
    const posted=JSON.parse(document.getElementById('promotion-posted').textContent);fill(posted);
})();
