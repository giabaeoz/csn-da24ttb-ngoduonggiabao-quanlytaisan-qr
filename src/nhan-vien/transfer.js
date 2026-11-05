'use strict';
(() => {
  const form = document.getElementById('transfer-form');
  if (!form) return;
  const origin = document.getElementById('transfer-origin');
  const destination = document.getElementById('transfer-destination');
  const reason = document.getElementById('transfer-reason');
  const codeInput = document.getElementById('transfer-code');
  const confirmation = document.getElementById('transfer-compatible');
  const assets = [...document.querySelectorAll('.transfer-asset')];
  const counters = [...document.querySelectorAll('[data-transfer-count]')];
  const previewAssets = document.querySelector('[data-transfer-preview-assets]');
  const originLabel = document.querySelector('[data-transfer-origin-label]');
  const destinationLabel = document.querySelector('[data-transfer-destination-label]');
  const reasonLabel = document.querySelector('[data-transfer-reason-label]');
  const scannerRoot = document.querySelector('[data-qr-scanner]');
  let scanner;

  function destinationAccepts(asset) {
    const roomCode = destination?.selectedOptions?.[0]?.dataset?.code || '';
    if (!destination?.value || !roomCode) return false;
    const compat = asset?.dataset?.compat || '';
    return compat === '*' || compat.split('|').includes(roomCode)
      || compat === '#' + destination.value;
  }

  function update() {
    const selected = assets.filter(asset => asset.querySelector('input')?.checked);
    for (const asset of assets) asset.hidden = !asset.querySelector('input')?.checked;
    counters.forEach(counter => { counter.textContent = String(selected.length); });
    if (originLabel) originLabel.textContent = origin?.selectedOptions?.[0]?.dataset?.code || '—';
    if (destinationLabel) destinationLabel.textContent = destination?.selectedOptions?.[0]?.dataset?.code || '—';
    if (reasonLabel) reasonLabel.textContent = reason?.value?.trim() || 'Chưa nhập';
    if (previewAssets) {
      if (selected.length) {
        previewAssets.replaceChildren(...selected.map(asset => {
          const line = document.createElement('div');
          line.textContent = asset.dataset.label;
          return line;
        }));
      } else previewAssets.textContent = 'Chưa quét thiết bị.';
    }
  }

  function clearInvalidSelection() {
    let removed = 0;
    for (const asset of assets) {
      const input = asset.querySelector('input');
      if (input.checked && (asset.dataset.origin !== origin.value || !destinationAccepts(asset))) {
        input.checked = false;
        removed++;
      }
    }
    if (confirmation) confirmation.checked = false;
    if (removed) scanner?.setStatus(`Đã bỏ ${removed} thiết bị không còn phù hợp với phòng vừa chọn.`);
    update();
  }

  function addCode(raw, api) {
    const value = raw.trim().toLocaleLowerCase('vi');
    if (!value || value.length > 100) { api.setStatus('Mã tài sản hoặc QR không hợp lệ.'); return false; }
    if (!origin?.value || !destination?.value) { api.setStatus('Hãy chọn phòng đi và phòng đến trước khi quét.'); return false; }
    if (origin.value === destination.value) { api.setStatus('Phòng đi và phòng đến phải khác nhau.'); return false; }
    const asset = assets.find(item => item?.dataset?.code === value || item?.dataset?.qr === value);
    if (!asset) { api.setStatus('Mã không thuộc tài sản sẵn sàng đang ở phòng học của phòng trực này.'); return false; }
    if (asset.dataset.origin !== origin.value) { api.setStatus(`Thiết bị đang ở ${asset.dataset.originCode}, không phải phòng đi đã chọn.`); return false; }
    if (!destinationAccepts(asset)) { api.setStatus('Thiết bị không phù hợp với phòng học đến.'); return false; }
    const input = asset.querySelector('input');
    if (input?.checked) { api.setStatus('Thiết bị đã có trong phiếu điều chuyển.'); return false; }
    if (assets.filter(item => item.querySelector('input')?.checked).length >= 30) { api.setStatus('Một phiếu chỉ được tối đa 30 thiết bị.'); return false; }
    if (input) input.checked = true;
    if (confirmation) confirmation.checked = false;
    if (codeInput) codeInput.value = '';
    update();
    api.setStatus('Đã thêm ' + (asset.querySelector('strong')?.textContent || '') + ' vào phiếu.');
    return true;
  }

  scanner = scannerRoot && window.QRScanner ? window.QRScanner.bind(scannerRoot, addCode) : null;
  document.getElementById('transfer-add')?.addEventListener('click', () => addCode(codeInput.value, scanner || {setStatus() {}}));
  codeInput?.addEventListener('keydown', event => { if (event.key === 'Enter') { event.preventDefault(); document.getElementById('transfer-add').click(); } });
  assets.forEach(asset => asset.querySelector('[data-transfer-remove]')?.addEventListener('click', event => {
    event.preventDefault();
    event.stopPropagation();
    const input = asset.querySelector('input');
    if (input) input.checked = false;
    if (confirmation) confirmation.checked = false;
    update();
  }));
  origin?.addEventListener('change', clearInvalidSelection);
  destination?.addEventListener('change', clearInvalidSelection);
  reason?.addEventListener('input', update);
  form.addEventListener('submit', event => {
    const selected = assets.filter(asset => asset.querySelector('input')?.checked);
    if (!selected.length) { event.preventDefault(); scanner?.setStatus('Hãy quét ít nhất một thiết bị cần điều chuyển.'); return; }
    if (origin?.value === destination?.value) { event.preventDefault(); destination?.setCustomValidity('Phòng đến phải khác phòng đi.'); destination?.reportValidity(); }
    else destination?.setCustomValidity('');
  });
  update();
})();
