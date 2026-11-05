'use strict';
document.querySelectorAll('[data-qr-scanner]').forEach(root => {
    if (window.QRScanner) {
        window.QRScanner.bind(root, (raw, api) => {
            const input = document.querySelector('.asset-filter-form input[name="q"]');
            if (!input || !raw || raw.length > 100) {
                api.setStatus('Mã QR không hợp lệ hoặc quá dài.');
                return false;
            }
            input.value = raw;
            const url = new URL(input.form.getAttribute('action') || location.pathname, location.href);
            url.searchParams.set('q', raw);
            url.searchParams.set('scan', '1');
            location.assign(url.toString());
            return true;
        });
    }
});
