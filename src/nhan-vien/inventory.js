'use strict';
(() => {
    const scannerRoot = document.querySelector('[data-qr-scanner]');
    const scanInput = document.getElementById('inventory-scan');
    if (scannerRoot && scanInput && window.QRScanner) {
        window.QRScanner.bind(scannerRoot, (raw, api) => {
            if (!raw || raw.length > 100) {
                api.setStatus('Mã QR không hợp lệ hoặc quá dài.');
                return false;
            }
            scanInput.value = raw;
            scanInput.form.requestSubmit();
            return true;
        });
    }

    const result = document.getElementById('inventory-result');
    const condition = document.getElementById('inventory-condition');
    const note = document.getElementById('inventory-note');
    const foundFields = [...document.querySelectorAll('.inventory-found-field')];
    function syncRecordForm() {
        if (!result || !condition || !note) return;
        const found = result.value === 'TIM_THAY';
        foundFields.forEach(field => {
            field.hidden = !found;
            field.querySelectorAll('select, input').forEach(input => { input.disabled = !found; });
        });
        note.required = !found || condition.value === 'HU_HONG';
    }
    result?.addEventListener('change', syncRecordForm);
    condition?.addEventListener('change', syncRecordForm);
    syncRecordForm();

    if (new URLSearchParams(location.search).has('scan')) {
        document.getElementById('inventory-record')?.scrollIntoView({block: 'start'});
    }
})();
