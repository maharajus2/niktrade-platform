// Окно-предупреждение при переходе из розничного магазина в портал корпоративных клиентов.
(() => {
    const gate = document.getElementById('bizGate');
    if (!gate) return;
    const KEY = 'biz.gate.ok';
    const goBtn = gate.querySelector('[data-biz-gate-go]');
    const stayBtn = gate.querySelector('[data-biz-gate-stay]');
    let target = '';
    let lastFocus = null;

    const accepted = () => { try { return sessionStorage.getItem(KEY) === '1'; } catch (e) { return false; } };
    const open = (href) => {
        target = href;
        lastFocus = document.activeElement;
        gate.hidden = false;
        document.body.style.overflow = 'hidden';
        goBtn.focus();
    };
    const close = () => {
        gate.hidden = true;
        document.body.style.overflow = '';
        if (lastFocus && lastFocus.focus) lastFocus.focus();
    };

    document.addEventListener('click', (e) => {
        const link = e.target.closest('a[data-biz-link]');
        if (!link || e.defaultPrevented) return;
        if (e.metaKey || e.ctrlKey || e.shiftKey || e.button === 1) return; // новая вкладка работает как обычно
        if (accepted()) return;
        e.preventDefault();
        open(link.href);
    });

    goBtn.addEventListener('click', () => {
        try { sessionStorage.setItem(KEY, '1'); } catch (e) { /* без хранилища спросим снова */ }
        window.location.href = target;
    });
    stayBtn.addEventListener('click', close);
    gate.addEventListener('click', (e) => { if (e.target === gate) close(); });
    document.addEventListener('keydown', (e) => {
        if (gate.hidden) return;
        if (e.key === 'Escape') { close(); return; }
        if (e.key === 'Tab') {
            const items = [goBtn, stayBtn];
            const i = items.indexOf(document.activeElement);
            e.preventDefault();
            items[(i + (e.shiftKey ? -1 : 1) + items.length) % items.length].focus();
        }
    });
})();
