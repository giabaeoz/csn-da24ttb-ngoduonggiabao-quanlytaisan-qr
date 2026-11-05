'use strict';
(() => {
  const room = document.getElementById('room');
  const list = document.getElementById('asset-list');
  const filter = document.getElementById('asset-filter');
  const preview = document.getElementById('borrow-preview');
  const scannerRoot = document.querySelector('[data-qr-scanner]');
  let pending = null;
  let scanner;

  function update() {
    if (!list) return;
    const code = room?.selectedOptions?.[0]?.dataset?.code || '';
    const id = room?.value || '';
    const term = filter ? filter.value.trim().toLocaleLowerCase() : '';
    let visible = 0;
    for (const el of list.querySelectorAll('.asset-item')) {
      const compat = el.dataset.compat || '';
      const compatible = !!code && (compat === '*' || compat.split('|').includes(code) || compat === id);
      const input = el.querySelector('input');
      const assetCode = el.dataset.code || '';
      el.hidden = !compatible || !assetCode.includes(term);
      input.disabled = !compatible;
      if (!compatible) input.checked = false;
      if (!el.hidden) visible++;
    }
    if (pending && code) {
      if (pending.querySelector('input')?.disabled) scanner?.setStatus('Mã đã quét không phù hợp phòng học này. Hãy chọn phòng phù hợp.');
      else {
        if (filter) filter.value = '';
        const pendingInput = pending.querySelector('input');
        if (pendingInput) pendingInput.checked = true;
        pending = null;
        scanner?.setStatus('Đã thêm thiết bị từ mã QR.');
        return update();
      }
    }
    const empty = document.getElementById('asset-empty');
    if (empty) {
      empty.hidden = visible > 0;
      empty.textContent = code ? 'Không có thiết bị phù hợp và sẵn sàng tại phòng trực.' : 'Chọn phòng học để xem thiết bị phù hợp.';
    }
    const chosen = [...list.querySelectorAll('input:checked')].map(input => input.dataset.name || '');
    if (preview) {
      preview.textContent = [
        document.getElementById('borrower')?.selectedOptions?.[0]?.textContent || 'Chưa chọn người mượn',
        'Phòng: ' + (code || '—'),
        'Hạn trả: ' + (document.getElementById('due')?.value || '—'),
        'Thiết bị: ' + (chosen.length ? chosen.join('\n') : 'Chưa chọn'),
        'Tổng cộng: ' + chosen.length
      ].join('\n');
    }
  }

  function addCode(raw, api) {
    const value = raw.trim().toLocaleLowerCase();
    if (!value || !list) return false;
    const match = [...list.querySelectorAll('.asset-item')].find(el =>
      (el.dataset.assetQr && el.dataset.assetQr.toLocaleLowerCase() === value) ||
      (el.dataset.assetCode && el.dataset.assetCode.toLocaleLowerCase() === value));
    if (!match) { api.setStatus('Mã không thuộc tài sản sẵn sàng của phòng trực này.'); return false; }
    if (!room || !room.value) {
      pending = match;
      api.setStatus('Đã đọc ' + (match.dataset.assetCode || '') + '. Hãy chọn phòng học để kiểm tra tương thích.');
      return true;
    }
    if (match.querySelector('input')?.disabled) {
      api.setStatus('Tài sản không phù hợp phòng học đã chọn.');
      return false;
    }
    if (filter) filter.value = '';
    const matchInput = match.querySelector('input');
    if (matchInput) matchInput.checked = true;
    update();
    api.setStatus('Đã thêm ' + (match.dataset.assetCode || '') + ' vào phiếu mượn.');
    return true;
  }

  scanner = scannerRoot ? window.QRScanner?.bind(scannerRoot, (raw, api) => {
    if (list) return addCode(raw, api);
    const search = document.getElementById('return-search');
    if (!search) return false;
    search.value = raw.trim();
    search.form.requestSubmit();
    return true;
  }) : null;
  [room, filter, list, document.getElementById('borrower'), document.getElementById('due')]
    .forEach(el => el?.addEventListener('input', update));
  update();
  document.getElementById('scan-add')?.addEventListener('click', () =>
    addCode(document.getElementById('scan-code').value, scanner));
})();
