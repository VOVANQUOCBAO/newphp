(() => {
    'use strict';
    document.querySelectorAll('.admin-page label:not([for])').forEach((label,index) => {
        if (label.querySelector('input,select,textarea')) return;
        const field=label.parentElement.querySelector('input:not([type=hidden]),select,textarea');
        if (field) { if (!field.id) field.id='admin-field-'+index; label.htmlFor=field.id; }
    });
    const normalize=text=>text.normalize('NFD').replace(/[\u0300-\u036f]/g,'').replace(/đ/g,'d').replace(/Đ/g,'D').toLowerCase();
    document.querySelectorAll('[data-admin-search]').forEach(search => {
        const table=document.getElementById(search.dataset.adminSearch);
        if (!table) return;
        const select=document.querySelector('[data-admin-status="'+table.id+'"]');
        const counter=document.querySelector('[data-admin-count="'+table.id+'"]');
        const rows=Array.from(table.querySelectorAll('tbody>tr[data-admin-row]')).map(row=>{
            const copy=row.cloneNode(true);copy.querySelectorAll('form,button,details').forEach(node=>node.remove());
            return {row,text:normalize(copy.textContent)};
        });
        const empty=document.createElement('p');empty.className='admin-empty-filter mb-0';empty.textContent='Không có kết quả phù hợp.';empty.hidden=true;
        table.closest('.table-responsive').after(empty);
        const filter=()=>{
            const query=normalize(search.value.trim());let visible=0;
            rows.forEach(({row,text})=>{const match=text.includes(query) && (!select || !select.value || row.dataset.status===select.value);row.hidden=!match;if(match)visible++;});
            empty.hidden=visible>0 || rows.length===0;
            if(counter) counter.textContent=visible+'/'+rows.length+' mục';
        };
        search.addEventListener('input',filter);select?.addEventListener('change',filter);filter();
    });
    document.querySelectorAll('[data-order-action]').forEach(select=>{
        const form=select.form;
        const update=()=>{
            const receiving=['Bom hàng','Trả hàng'].includes(select.value);
            form.querySelectorAll('[data-return-fields]').forEach(group=>{group.hidden=!receiving;group.querySelectorAll('input').forEach(input=>input.disabled=!receiving);});
            const reason=form.querySelector('[name=ly_do]');
            if(reason){reason.hidden=!['Đã hủy','Bom hàng','Trả hàng'].includes(select.value);reason.required=!reason.hidden;}
        };
        select.addEventListener('change',update);update();
    });
})();
