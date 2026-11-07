// Мини-корзина и «полёт» товара. Страница не перезагружается.
(() => {
    const cfg = window.V3_CART || {};
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const dock = document.getElementById('v3Dock');
    const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const cards = () => Array.from(document.querySelectorAll('[data-v3-card]'));
    let busy = false;

    const request = async (url, method, body) => {
        const res = await fetch(url, {
            method,
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: body ? JSON.stringify(body) : undefined,
        });
        if (!res.ok) throw new Error('cart ' + res.status);
        return res.json();
    };

    const plural = (n, f) => {
        const a = Math.abs(n) % 100, l = a % 10;
        if (a >= 11 && a <= 14) return f[2];
        return l === 1 ? f[0] : (l >= 2 && l <= 4 ? f[1] : f[2]);
    };

    const updateHeader = (data) => {
        const cart = document.querySelector('.v3-cart');
        if (!cart) return;
        const ico = cart.querySelector('.v3-cart-ico');
        const total = cart.querySelector('.total');
        let badge = ico.querySelector('.v3-cart-count');
        if (data.count > 0) {
            if (!badge) {
                badge = document.createElement('span');
                badge.className = 'v3-cart-count';
                ico.appendChild(badge);
            }
            badge.textContent = data.count;
            if (!reduce) badge.animate([{ transform: 'scale(1.5)' }, { transform: 'scale(1)' }], { duration: 300, easing: 'ease-out' });
        } else if (badge) {
            badge.remove();
        }
        if (total) {
            total.textContent = data.count > 0 ? data.total_formatted : 'Корзина';
            total.classList.toggle('is-empty', data.count === 0);
        }
    };

    const updateCards = (data) => {
        const byProduct = new Map(data.items.map((i) => [i.product_id, i]));
        cards().forEach((card) => {
            const item = byProduct.get(Number(card.dataset.productId));
            const qty = item ? item.quantity : 0;
            card.dataset.qty = qty;
            card.dataset.itemId = item ? item.id : '';
            const cartLink = card.querySelector('[data-v3-cart-link]');
            if (cartLink) cartLink.hidden = qty < 1;
            const stepper = card.querySelector('.v3-qty');
            const form = card.querySelector('[data-v3-add-form]');
            const num = card.querySelector('[data-v3-num]');
            if (!stepper || !form) return;
            const out = !!form.querySelector('.v3-add')?.disabled;
            stepper.hidden = qty < 1 || out;
            form.hidden = qty > 0 && !out;
            if (num) num.textContent = qty;
        });
    };

    const renderDock = (data, changedId) => {
        if (!dock) return null;
        const list = dock.querySelector('[data-v3-dock-items]');
        list.innerHTML = '';
        let changedEl = null;
        data.items.forEach((item) => {
            const el = document.createElement('div');
            el.className = 'v3-dock-item';
            el.title = item.name;
            el.dataset.productId = item.product_id;
            el.dataset.itemId = item.id;
            el.dataset.qty = item.quantity;
            el.innerHTML = '<img alt="">'
                + '<div class="v3-dock-ctl">'
                + '<button type="button" data-v3-dock-dec aria-label="Уменьшить количество">−</button>'
                + '<span class="v3-dock-n"></span>'
                + '<button type="button" data-v3-dock-inc aria-label="Увеличить количество">+</button>'
                + '</div>'
                + '<button type="button" class="v3-dock-q" data-v3-dock-remove aria-label="Убрать из корзины">'
                + '<span class="n"></span><span class="x">×</span></button>';
            el.querySelector('img').src = item.image || '';
            el.querySelector('.v3-dock-n').textContent = item.quantity;
            el.querySelector('.v3-dock-q .n').textContent = item.quantity;
            list.appendChild(el);
            if (item.product_id === changedId) changedEl = el;
        });
        dock.querySelector('[data-v3-dock-count]').textContent =
            data.count + ' ' + plural(data.count, ['товар', 'товара', 'товаров']);
        dock.querySelector('[data-v3-dock-total]').textContent = data.total_formatted;
        const show = data.count > 0;
        dock.hidden = !show;
        document.body.classList.toggle('has-dock', show);
        if (show) requestAnimationFrame(() => dock.classList.add('is-open'));
        else dock.classList.remove('is-open');
        return changedEl;
    };

    // Анимация внутри карточки: снизу выезжает верх корзинки, товар падает в неё, корзинка уезжает вниз.
    const BASKET_FRONT = '<svg viewBox="0 0 160 92" width="100%" height="100%" aria-hidden="true">'
        + '<path d="M17 24 27 84Q28 90 35 90H125Q132 90 133 84L143 24Z" fill="#5348d8"/>'
        + '<path d="M44 24 48 90M72 24 73 90M100 24 99 90M128 24 124 90M20 48H140M24 70H136" stroke="#fff" stroke-opacity=".28" stroke-width="3" stroke-linecap="round" fill="none"/>'
        + '<rect x="4" y="8" width="152" height="18" rx="9" fill="#4339bb"/>'
        + '<rect x="8" y="11" width="144" height="5" rx="2.5" fill="#fff" fill-opacity=".25"/></svg>';
    const BASKET_BACK = '<svg viewBox="0 0 160 92" width="100%" height="100%" aria-hidden="true">'
        + '<ellipse cx="80" cy="18" rx="72" ry="12" fill="#2b2480"/></svg>';

    const basketDrop = (card, img, gate, onDescend) => new Promise((resolve, reject) => {
        const finish = (err) => { [back, front, clone].forEach((n) => n.remove()); img.style.visibility = ''; card.classList.remove('is-adding'); err ? reject(err) : resolve(); };
        const cr = card.getBoundingClientRect();
        const ir = img.getBoundingClientRect();
        const W = 150, H = 86;

        const mk = (cls, html) => { const n = document.createElement('div'); n.className = cls; n.innerHTML = html; card.appendChild(n); return n; };
        const back = mk('v3-bk v3-bk-back', BASKET_BACK);
        const front = mk('v3-bk v3-bk-front', BASKET_FRONT);
        [back, front].forEach((n) => { n.style.width = W + 'px'; n.style.height = H + 'px'; });

        const clone = img.cloneNode();
        clone.className = 'v3-bk-img';
        Object.assign(clone.style, { left: ir.left - cr.left + 'px', top: ir.top - cr.top + 'px', width: ir.width + 'px', height: ir.height + 'px' });
        card.appendChild(clone);
        card.classList.add('is-adding');
        img.style.visibility = 'hidden';

        const hidden = 'translateY(115%)', shown = 'translateY(16px)';
        const both = (kf, opt) => [back, front].map((n) => n.animate(kf, opt));
        const up = both([{ transform: hidden }, { transform: shown }], { duration: 320, easing: 'cubic-bezier(.2,1.3,.4,1)', fill: 'forwards' });

        // центр рамки корзинки относительно карточки
        const rimY = cr.height - 10 - H + 16 + 14;
        const cy0 = ir.top - cr.top + ir.height / 2;
        const toRim = rimY - cy0;

        up[0].onfinish = () => {
            const fall = clone.animate([
                { transform: 'translateY(0) scale(1)', opacity: 1, offset: 0 },
                { transform: `translateY(${toRim * 0.85}px) scale(.62)`, opacity: 1, offset: .7 },
                { transform: `translateY(${toRim + 46}px) scale(.42)`, opacity: 1, offset: 1 },
            ], { duration: 520, easing: 'cubic-bezier(.5,0,.9,.6)', fill: 'forwards' });

            fall.onfinish = () => {
                clone.style.visibility = 'hidden';
                // корзинка «принимает» товар
                const squash = both([
                    { transform: shown + ' scale(1,1)', transformOrigin: '50% 100%' },
                    { transform: shown + ' scale(1.06,.9)', transformOrigin: '50% 100%', offset: .35 },
                    { transform: shown + ' scale(.98,1.04)', transformOrigin: '50% 100%', offset: .7 },
                    { transform: shown + ' scale(1,1)', transformOrigin: '50% 100%' },
                ], { duration: 260, easing: 'ease-out', fill: 'forwards' });

                squash[0].onfinish = async () => {
                    let data;
                    try { data = await gate; } catch (e) { return finish(e); }
                    onDescend(data);
                    const down = both([{ transform: shown }, { transform: hidden }], { duration: 340, easing: 'ease-in', fill: 'forwards' });
                    down[0].onfinish = () => finish();
                };
            };
        };
    });

    // Показываем мини-корзину сразу, если после загрузки страницы в корзине уже есть товары.
    if (dock && Number(cfg.initialCount) > 0 && Array.isArray(cfg.initialItems)) {
        renderDock({ count: Number(cfg.initialCount), total_formatted: cfg.initialTotal, items: cfg.initialItems }, null);
    }

    const apply = (data) => {
        renderDock(data, data.changed_product_id);
        updateCards(data);
        updateHeader(data);
    };

    const addToCart = async (card, animate) => {
        const req = request(card.dataset.addUrl, 'POST');
        const img = card.querySelector('.v3-card-img img');
        if (animate && !reduce && img) {
            req.catch(() => {});
            await basketDrop(card, img, req, apply);
        } else {
            apply(await req);
        }
    };

    const run = async (fn, fallback) => {
        if (busy) return;
        busy = true;
        try { await fn(); } catch (e) { fallback(); } finally { busy = false; }
    };

    document.addEventListener('submit', (e) => {
        const form = e.target.closest('[data-v3-add-form]');
        if (!form) return;
        e.preventDefault();
        const card = form.closest('[data-v3-card]');
        run(() => addToCart(card, true), () => form.submit());
    });

    document.addEventListener('click', (e) => {
        const inc = e.target.closest('[data-v3-inc]');
        const dec = e.target.closest('[data-v3-dec]');
        if (!inc && !dec) return;
        const card = (inc || dec).closest('[data-v3-card]');
        const qty = Number(card.dataset.qty || 0);
        const id = card.dataset.itemId;
        if (inc) {
            // Анимация корзинки только при первом добавлении; дальше просто меняется число.
            run(() => addToCart(card, qty === 0), () => location.reload());
        } else if (qty > 1) {
            run(async () => apply(await request(cfg.updateUrl.replace('__ID__', id), 'PATCH', { quantity: qty - 1 })), () => location.reload());
        } else {
            run(async () => apply(await request(cfg.destroyUrl.replace('__ID__', id), 'DELETE')), () => location.reload());
        }
    });

    // Управление количеством прямо в мини-корзине.
    const noHover = window.matchMedia('(hover: none)');
    const activeItems = () => dock ? Array.from(dock.querySelectorAll('.v3-dock-item.is-active')) : [];

    document.addEventListener('click', (e) => {
        if (!dock) return;
        const item = e.target.closest('.v3-dock-item');
        if (!item) { activeItems().forEach((n) => n.classList.remove('is-active')); return; }

        const id = item.dataset.itemId;
        const qty = Number(item.dataset.qty || 0);
        const productId = item.dataset.productId;
        const keepActive = async (fn) => {
            const wasActive = item.classList.contains('is-active');
            await fn();
            if (wasActive) dock.querySelector(`.v3-dock-item[data-product-id="${productId}"]`)?.classList.add('is-active');
        };
        const patch = (q) => async () => apply(await request(cfg.updateUrl.replace('__ID__', id), 'PATCH', { quantity: q }));
        const destroy = async () => apply(await request(cfg.destroyUrl.replace('__ID__', id), 'DELETE'));
        const reload = () => location.reload();

        if (e.target.closest('[data-v3-dock-remove]')) {
            if (noHover.matches && !item.classList.contains('is-active')) {
                activeItems().forEach((n) => n.classList.remove('is-active'));
                item.classList.add('is-active');
                return;
            }
            run(destroy, reload);
        } else if (e.target.closest('[data-v3-dock-inc]')) {
            run(() => keepActive(patch(qty + 1)), reload);
        } else if (e.target.closest('[data-v3-dock-dec]')) {
            run(() => keepActive(qty > 1 ? patch(qty - 1) : destroy), reload);
        } else {
            const open = !item.classList.contains('is-active');
            activeItems().forEach((n) => n.classList.remove('is-active'));
            if (open) item.classList.add('is-active');
        }
    });

    // Очистка корзины: два нажатия (второе подтверждает), без системных окон.
    const clearBtn = dock?.querySelector('[data-v3-dock-clear]');
    let clearTimer = null;
    const resetClear = () => {
        if (!clearBtn) return;
        clearTimeout(clearTimer);
        clearBtn.classList.remove('is-confirm');
        clearBtn.textContent = 'Очистить';
    };
    clearBtn?.addEventListener('click', () => {
        if (!clearBtn.classList.contains('is-confirm')) {
            clearBtn.classList.add('is-confirm');
            clearBtn.textContent = 'Точно очистить?';
            clearTimer = setTimeout(resetClear, 3500);
            return;
        }
        resetClear();
        const ids = Array.from(dock.querySelectorAll('.v3-dock-item')).map((n) => n.dataset.itemId);
        run(async () => {
            let data = null;
            for (const id of ids) data = await request(cfg.destroyUrl.replace('__ID__', id), 'DELETE');
            if (data) apply(data);
        }, () => location.reload());
    });

    dock?.querySelector('[data-v3-dock-close]')?.addEventListener('click', () => {
        dock.classList.remove('is-open');
        dock.hidden = true;
        document.body.classList.remove('has-dock');
    });

    // Переход на страницу «Назад» из кэша: актуализируем состояние.
    window.addEventListener('pageshow', (e) => { if (e.persisted) location.reload(); });
})();

(() => {
    const btn = document.querySelector('[data-v3-mega-toggle]');
    const panel = document.getElementById('v3Mega');
    if (!btn || !panel) return;

    const setOpen = (open, returnFocus = false) => {
        panel.hidden = !open;
        btn.setAttribute('aria-expanded', String(open));
        if (!open && returnFocus) btn.focus();
    };

    btn.addEventListener('click', () => setOpen(panel.hidden));

    document.addEventListener('click', (e) => {
        if (!panel.hidden && !panel.contains(e.target) && !btn.contains(e.target)) setOpen(false);
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !panel.hidden) setOpen(false, true);
    });
})();

// Живой поиск: подсказки с допуском опечаток.
(() => {
    const form = document.querySelector('[data-v3-search]');
    if (!form) return;
    const input = form.querySelector('input[name="search"]');
    const box = form.querySelector('.v3-suggest');
    const url = form.dataset.suggestUrl;
    let timer = null;
    let ctrl = null;
    let active = -1;

    const esc = (s) => String(s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const items = () => Array.from(box.querySelectorAll('.v3-sg-item'));
    const close = () => { box.hidden = true; input.setAttribute('aria-expanded', 'false'); active = -1; };
    const open = () => { box.hidden = false; input.setAttribute('aria-expanded', 'true'); };
    const highlight = (i) => {
        const list = items();
        list.forEach((el, n) => el.classList.toggle('is-active', n === i));
        active = i;
        if (list[i]) list[i].scrollIntoView({ block: 'nearest' });
    };

    const render = (data, q) => {
        if (!data.items.length) {
            box.innerHTML = '<div class="v3-sg-empty">Ничего не нашли по запросу «' + esc(q) + '». Проверьте написание или попробуйте другое слово.</div>';
            open();
            return;
        }
        let html = data.fuzzy ? '<div class="v3-sg-note">Возможно, вы искали:</div>' : '';
        data.items.forEach((p) => {
            html += '<a class="v3-sg-item" role="option" href="' + esc(p.url) + '">'
                + '<span class="v3-sg-pic">' + (p.image ? '<img src="' + esc(p.image) + '" alt="">' : '') + '</span>'
                + '<span class="v3-sg-txt"><span class="v3-sg-name">' + esc(p.name) + '</span>'
                + '<span class="v3-sg-meta">' + esc(p.brand) + (p.direction ? ' · ' + esc(p.direction) : '') + (p.in_stock ? '' : ' · скоро появится') + '</span></span>'
                + '<span class="v3-sg-price">' + esc(p.price) + '</span></a>';
        });
        html += '<a class="v3-sg-all" href="' + esc(data.all_url) + '">Показать все результаты (' + data.total + ')</a>';
        box.innerHTML = html;
        open();
        active = -1;
    };

    const search = async () => {
        const q = input.value.trim();
        if (q.length < 2) { close(); return; }
        if (ctrl) ctrl.abort();
        ctrl = new AbortController();
        try {
            const res = await fetch(url + '?q=' + encodeURIComponent(q), { signal: ctrl.signal, headers: { 'Accept': 'application/json' } });
            if (!res.ok) return;
            render(await res.json(), q);
        } catch (e) { /* запрос отменён или сеть недоступна */ }
    };

    input.addEventListener('input', () => { clearTimeout(timer); timer = setTimeout(search, 160); });
    input.addEventListener('focus', () => { if (input.value.trim().length >= 2 && box.innerHTML) open(); });

    input.addEventListener('keydown', (e) => {
        const list = items();
        if (e.key === 'ArrowDown' && !box.hidden && list.length) { e.preventDefault(); highlight((active + 1) % list.length); }
        else if (e.key === 'ArrowUp' && !box.hidden && list.length) { e.preventDefault(); highlight((active - 1 + list.length) % list.length); }
        else if (e.key === 'Enter' && active >= 0 && list[active]) { e.preventDefault(); location.href = list[active].href; }
        else if (e.key === 'Escape' && !box.hidden) { close(); }
    });

    document.addEventListener('click', (e) => { if (!form.contains(e.target)) close(); });
})();
