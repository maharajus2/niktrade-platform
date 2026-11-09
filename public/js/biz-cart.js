(() => {
    'use strict';
    const token = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const money = (n) => {
        const value = Number(n) || 0;
        return new Intl.NumberFormat('ru-RU', { minimumFractionDigits: Number.isInteger(value) ? 0 : 2, maximumFractionDigits: 2 }).format(value) + ' ₽';
    };
    const live = document.createElement('div');
    live.className = 'biz-toast';
    live.setAttribute('role', 'status');
    live.setAttribute('aria-live', 'polite');
    live.hidden = true;
    document.body.appendChild(live);
    let toastTimer;
    const toast = (text) => {
        live.textContent = text;
        live.hidden = false;
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => { live.hidden = true; }, 4000);
    };

    async function call(method, url, body) {
        const response = await fetch(url, {
            method,
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: body ? JSON.stringify(body) : undefined,
        });
        if (response.status === 401 || response.status === 419) { window.location.reload(); throw new Error('reload'); }
        const data = await response.json().catch(() => ({}));
        if (!response.ok) {
            const first = data.errors ? Object.values(data.errors).flat()[0] : null;
            throw new Error(first || data.message || 'Не удалось обновить заявку. Попробуйте ещё раз.');
        }
        return data;
    }

    function applySummary(summary) {
        document.querySelectorAll('[data-biz-cart-count]').forEach((el) => {
            el.textContent = summary.items_count;
            el.hidden = summary.items_count < 1;
        });
        document.querySelectorAll('[data-biz-cart-total]').forEach((el) => { el.textContent = money(summary.total); });
        const status = summary.can_submit ? 'Можно отправлять заявку' : summary.missing > 0
            ? 'До минимума ещё ' + money(summary.missing) : 'Проверьте товары в заявке';
        document.querySelectorAll('[data-biz-min-note]').forEach((el) => {
            el.textContent = summary.items_count < 1 ? 'Заявка пуста' : status;
            el.classList.toggle('is-ok', summary.can_submit);
        });
        document.querySelectorAll('[data-biz-bar]').forEach((el) => { el.hidden = summary.items_count < 1; });
        document.querySelectorAll('[data-biz-submit]').forEach((el) => { el.disabled = !summary.can_submit; });
    }

    async function refreshCartPrices() {
        if (!document.querySelector('[data-biz-row]')) return;
        // The JSON contract deliberately contains no subtotal or discount amount.
        // Read those values from the server-rendered cart rather than infer rounded prices.
        const response = await fetch(window.location.href, { credentials: 'same-origin', headers: { 'Accept': 'text/html' } });
        if (!response.ok) throw new Error('Не удалось обновить суммы заявки. Обновите страницу.');
        const html = new DOMParser().parseFromString(await response.text(), 'text/html');
        for (const key of ['data-biz-subtotal', 'data-biz-discount']) {
            const value = html.querySelector('[' + key + ']');
            if (value) document.querySelectorAll('[' + key + ']').forEach((el) => { el.textContent = value.textContent; });
        }
        html.querySelectorAll('[data-biz-row]').forEach((row) => {
            const current = document.querySelector('[data-biz-row][data-product-id="' + row.dataset.productId + '"] [data-biz-unit-price]');
            const value = row.querySelector('[data-biz-unit-price]');
            if (current && value) current.textContent = value.textContent;
        });
    }

    function setQty(item, qty) {
        item.dataset.qty = String(qty);
        item.querySelectorAll('[data-biz-num]').forEach((el) => { if ('value' in el) el.value = qty; else el.textContent = qty; });
        item.querySelectorAll('[data-biz-qty]').forEach((el) => { el.hidden = qty < 1; });
        item.querySelectorAll('[data-biz-add-wrap]').forEach((el) => { el.hidden = qty >= 1; });
        item.querySelectorAll('[data-biz-cart-link]').forEach((el) => { el.hidden = qty < 1; });
    }

    async function change(item, action, value) {
        if (item.dataset.busy === '1') return;
        const productId = item.dataset.productId;
        const current = Number(item.dataset.qty || 0);
        item.dataset.busy = '1';
        item.classList.add('is-busy');
        try {
            let data;
            if (action === 'add') {
                data = await call('POST', item.dataset.addUrl, { product_id: Number(productId), quantity: 1 });
            } else {
                const next = action === 'set' ? value : action === 'inc' ? current + 1 : action === 'remove' ? 0 : current - 1;
                data = await call('PATCH', item.dataset.updateUrl, { quantity: Math.max(0, next) });
            }
            const qty = data.item ? data.item.quantity : 0;
            setQty(item, qty);
            if (item.matches('[data-biz-row]')) {
                const line = item.querySelector('[data-biz-line]');
                if (line && data.item) line.textContent = money(data.item.line_total);
                if (qty < 1) {
                    item.remove();
                    if (!document.querySelector('[data-biz-row]')) { window.location.reload(); return; }
                }
            }
            applySummary(data.summary);
            if (item.matches('[data-biz-row]')) await refreshCartPrices();
        } catch (error) {
            if (error.message !== 'reload') { toast(error.message); setQty(item, current); }
        } finally {
            item.dataset.busy = '0';
            item.classList.remove('is-busy');
        }
    }

    document.addEventListener('click', (event) => {
        const control = event.target.closest('[data-biz-add],[data-biz-inc],[data-biz-dec],[data-biz-remove]');
        if (!control) return;
        const item = control.closest('[data-biz-item]');
        if (!item) return;
        event.preventDefault();
        const action = control.hasAttribute('data-biz-add') ? 'add'
            : control.hasAttribute('data-biz-inc') ? 'inc'
            : control.hasAttribute('data-biz-remove') ? 'remove' : 'dec';
        change(item, action);
    });

    // Ручной ввод количества: только цифры, 1..9999, применяется по Enter или при выходе из поля.
    document.addEventListener('input', (event) => {
        const input = event.target.closest('input[data-biz-num]');
        if (input) input.value = input.value.replace(/\D+/g, '').slice(0, 4);
    });
    document.addEventListener('focusin', (event) => {
        const input = event.target.closest('input[data-biz-num]');
        if (input) input.select();
    });
    document.addEventListener('keydown', (event) => {
        const input = event.target.closest('input[data-biz-num]');
        if (input && event.key === 'Enter') { event.preventDefault(); input.blur(); }
    });
    document.addEventListener('change', (event) => {
        const input = event.target.closest('input[data-biz-num]');
        const item = input?.closest('[data-biz-item]');
        if (!item) return;
        const current = Number(item.dataset.qty || 0);
        const typed = parseInt(input.value, 10);
        if (!typed || typed < 1) { input.value = current; return; }
        const next = Math.min(typed, 9999);
        input.value = next;
        if (next !== current) change(item, 'set', next);
    });

    // Форма «В заявку» без JS отправляется обычным POST; с JS перехватываем.
    document.addEventListener('submit', (event) => {
        const form = event.target.closest('[data-biz-add-form]');
        if (!form) return;
        const item = form.closest('[data-biz-item]');
        if (!item) return;
        event.preventDefault();
        change(item, 'add');
    });
})();
