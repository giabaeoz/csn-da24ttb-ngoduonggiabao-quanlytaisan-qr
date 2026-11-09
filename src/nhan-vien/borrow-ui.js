'use strict';
(() => {
  const layout = document.querySelector('.borrow-layout');
  if (!layout) return;
  const page = layout.dataset.page;
  const scannerRoot = document.querySelector('[data-qr-scanner]');
  const scanInput = document.getElementById('scan-code');
  const scanButton = document.getElementById('scan-add');
  const csrf = document.querySelector('input[name="csrf_token"]')?.value || '';
  const esc = (v) => String(v ?? '').replace(/[&<>"']/g, c => ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[c]));
  const api = (params) => fetch('muon-tra.php?' + new URLSearchParams(params), {headers: {Accept: 'application/json'}, credentials: 'same-origin'})
    .then(async r => { const data = await r.json().catch(() => ({error: 'Phản hồi không hợp lệ.'})); if (!r.ok) throw new Error(data.error || 'Lỗi xử lý.'); return data; });
  const fmtDate = (v) => { const d = v instanceof Date ? v : new Date(String(v).replace(' ', 'T')); return isNaN(d) ? '—' : d.toLocaleString('vi-VN', {hour: '2-digit', minute: '2-digit', day: '2-digit', month: '2-digit', year: 'numeric'}); };

  let scanner = null;
  let status = (text, tone) => {
    const el = scannerRoot?.querySelector('[data-qr-status]');
    if (el) { el.textContent = text; el.dataset.tone = tone || ''; }
  };

  /* ======================= TRANG MƯỢN ======================= */
  if (page === 'muon') {
    const form = document.getElementById('borrow-form');
    const room = document.getElementById('room');
    const due = document.getElementById('due');
    const borrowerId = document.getElementById('borrower-id');
    const searchInput = document.getElementById('borrower-search-input');
    const results = document.getElementById('borrower-results');
    const searchStatus = document.getElementById('borrower-search-status');
    const overview = document.getElementById('borrower-overview');
    const quick = document.getElementById('quick-borrower');
    const btnNew = document.getElementById('btn-new-borrower');
    const assetInputs = document.getElementById('asset-inputs');
    const tableBody = document.getElementById('scanned-table-body');
    const pendingCodes = new Set();

    const setSearchStatus = (text, tone) => { searchStatus.textContent = text; searchStatus.dataset.tone = tone || ''; };
    const roomCode = () => room.selectedOptions[0]?.dataset.code || '';
    const assets = () => [...assetInputs.querySelectorAll('input')];

    function selectBorrower(p) {
      borrowerId.value = p.id;
      Object.assign(borrowerId.dataset, {code: p.ma_so_truong, name: p.ho_ten, phone: p.so_dien_thoai || '', unit: p.lop_don_vi || ''});
      document.getElementById('disp-code').value = p.ma_so_truong;
      document.getElementById('disp-name').value = p.ho_ten;
      document.getElementById('disp-phone').value = p.so_dien_thoai || '';
      document.getElementById('disp-unit').value = p.lop_don_vi || '';
      searchInput.value = p.ma_so_truong;
      results.hidden = true;
      setSearchStatus(`Đã chọn ${p.ho_ten} (${p.ma_so_truong}).`, 'ok');
      loadOverview(p.id);
      render();
    }

    function loadOverview(id) {
      overview.hidden = true;
      api({ajax: 'overview', id}).then(data => {
        const open = data.open || [];
        const history = data.history || [];
        if (!open.length && !history.length) return;
        overview.innerHTML = `
          <div class="overview-col">
            <div class="overview-title">Đang mượn (${open.length})</div>
            ${open.length ? open.map(o => `<div class="overview-row"><span><strong>${esc(o.ma_tai_san)}</strong> ${esc(o.ten_tai_san)}</span><span class="${+o.qua_han ? 'text-overdue' : 'text-muted'}">${esc(o.ma_phieu_muon)} · Hạn ${esc(fmtDate(o.han_tra))}</span></div>`).join('') : '<div class="text-muted">Không có</div>'}
          </div>
          <div class="overview-col">
            <div class="overview-title">Lịch sử giao dịch</div>
            ${history.map(h => `<div class="overview-row"><a href="muon-tra.php?tab=tra&id=${+h.id}">${esc(h.ma_phieu_muon)}</a><span class="text-muted">${esc(fmtDate(h.thoi_gian_muon))} · ${esc(h.ma_phong)} · ${+h.so_tb} TB · ${h.trang_thai === 'DA_TRA' ? 'Đã trả' : 'Đang mượn'}</span></div>`).join('')}
          </div>`;
        overview.hidden = false;
      }).catch(() => {});
    }

    function search() {
      const q = searchInput.value.trim();
      if (!q) { setSearchStatus('Hãy nhập mã, họ tên hoặc số điện thoại cần tìm.', 'warn'); return; }
      setSearchStatus('Đang tìm…');
      api({ajax: 'borrowers', q}).then(({items}) => {
        if (!items.length) {
          results.hidden = true;
          setSearchStatus('Không tìm thấy người mượn. Có thể đăng ký người mượn mới.', 'warn');
          return;
        }
        if (items.length === 1) { selectBorrower(items[0]); return; }
        results.innerHTML = items.map((p, i) => `<button type="button" role="option" data-i="${i}"><strong>${esc(p.ma_so_truong)}</strong> — ${esc(p.ho_ten)}<small>${esc(p.lop_don_vi || '')}${p.so_dien_thoai ? ' · ' + esc(p.so_dien_thoai) : ''}${+p.dang_muon ? ' · đang mượn ' + (+p.dang_muon) : ''}</small></button>`).join('');
        results.querySelectorAll('button').forEach(b => b.addEventListener('click', () => selectBorrower(items[+b.dataset.i])));
        results.hidden = false;
        setSearchStatus(`Tìm thấy ${items.length} người mượn. Chọn một hồ sơ.`);
      }).catch(err => setSearchStatus(err.message, 'error'));
    }

    document.getElementById('btn-search-borrower').addEventListener('click', search);
    searchInput.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); search(); } if (e.key === 'Escape') results.hidden = true; });
    document.addEventListener('click', e => { if (!e.target.closest('.borrower-search')) results.hidden = true; });

    const toggleQuick = (show) => {
      quick.hidden = !show;
      btnNew.setAttribute('aria-expanded', String(show));
      if (show) {
        const q = searchInput.value.trim();
        if (q && !document.getElementById('qb-code').value) {
          document.getElementById(/^\+?[\d\s.]{9,}$/.test(q) ? 'qb-phone' : (/\d/.test(q) ? 'qb-code' : 'qb-name')).value = q;
        }
        document.getElementById('qb-code').focus();
      }
    };
    btnNew.addEventListener('click', () => toggleQuick(quick.hidden));
    document.getElementById('qb-cancel').addEventListener('click', () => toggleQuick(false));
    document.getElementById('qb-save').addEventListener('click', (ev) => {
      const btn = ev.currentTarget;
      const body = new URLSearchParams({
        csrf_token: csrf,
        loai_nguoi_muon: document.getElementById('qb-type').value,
        ma_so_truong: document.getElementById('qb-code').value.trim(),
        ho_ten: document.getElementById('qb-name').value.trim(),
        so_dien_thoai: document.getElementById('qb-phone').value.trim(),
        lop_don_vi: document.getElementById('qb-unit').value.trim(),
      });
      if (!body.get('ma_so_truong') || !body.get('ho_ten') || !body.get('so_dien_thoai')) {
        setSearchStatus('Nhập đủ Mã số, Họ tên và Số điện thoại.', 'error');
        return;
      }
      btn.disabled = true;
      fetch('muon-tra.php?ajax=quick_borrower', {method: 'POST', body, credentials: 'same-origin', headers: {Accept: 'application/json'}})
        .then(async r => { const d = await r.json(); if (!r.ok) throw new Error(d.error || 'Không lưu được.'); return d; })
        .then(({item}) => {
          quick.querySelectorAll('input').forEach(i => { i.value = ''; });
          toggleQuick(false);
          selectBorrower(item);
          setSearchStatus(`Đã đăng ký và chọn ${item.ho_ten} (${item.ma_so_truong}).`, 'ok');
        })
        .catch(err => setSearchStatus(err.message, 'error'))
        .finally(() => { btn.disabled = false; });
    });

    function render() {
      const list = assets();
      const code = roomCode();
      document.getElementById('scanned-count').textContent = list.length;
      tableBody.innerHTML = list.length ? list.map(i => `
        <tr>
          <td><strong>${esc(i.dataset.code)}</strong></td>
          <td>${esc(i.dataset.name)}</td>
          <td>${esc(code || '—')}</td>
          <td class="text-end"><button class="btn-remove" type="button" data-remove="${esc(i.value)}">Xóa</button></td>
        </tr>`).join('') : '<tr><td colspan="4" class="table-empty">Chưa có thiết bị nào được quét.</td></tr>';
      tableBody.querySelectorAll('[data-remove]').forEach(b => b.addEventListener('click', () => {
        assetInputs.querySelector(`input[value="${CSS.escape(b.dataset.remove)}"]`)?.remove();
        render();
      }));

      const codeInput = document.getElementById('disp-code')?.value.trim() || '';
      const nameInput = document.getElementById('disp-name')?.value.trim() || '';
      const phoneInput = document.getElementById('disp-phone')?.value.trim() || '';
      const unitInput = document.getElementById('disp-unit')?.value.trim() || '';
      const currentName = nameInput || (borrowerId.value ? borrowerId.dataset.name : '');
      const currentCode = codeInput || (borrowerId.value ? borrowerId.dataset.code : '');
      const currentPhone = phoneInput || (borrowerId.value ? borrowerId.dataset.phone : '');
      const currentUnit = unitInput || (borrowerId.value ? borrowerId.dataset.unit : '');

      const dueVal = due.value ? fmtDate(due.value) : '—';
      document.getElementById('rec-borrower').innerHTML = (currentName || currentCode)
        ? `<strong>${esc(currentName)} - ${esc(currentCode)}${currentUnit ? ' - ' + esc(currentUnit) : ''}</strong>`
        : '<strong>Chưa chọn người mượn</strong>';
      document.getElementById('rec-phone-room').textContent = `SĐT: ${currentPhone || '—'} · Phòng cần mượn: ${code || '—'}`;
      document.getElementById('rec-time').textContent = `Thời gian mượn: ${fmtDate(new Date())} · Hạn trả: ${dueVal}`;
      document.getElementById('rec-items-list').innerHTML = list.length
        ? list.map(i => `<div class="receipt-item-row"><span>${esc(i.dataset.code)} — ${esc(i.dataset.name)}</span><span>1</span></div>`).join('')
        : '<div class="receipt-item-row text-muted">Chưa quét thiết bị nào</div>';
      document.getElementById('rec-total-row').textContent = `Tổng cộng: ${list.length} thiết bị`;
    }

    function handleCode(raw) {
      let code = String(raw || '').trim();
      if (!code) return;
      if (code.includes('—')) code = code.split('—')[0].trim();
      const key = code.toLowerCase();
      if (pendingCodes.has(key)) return;
      pendingCodes.add(key);
      api({ajax: 'lookup', code, room: room.value || ''}).then(r => {
        if (r.type === 'room') {
          if (room.value === String(r.id)) { status(`Phòng ${r.ma_phong} đã được chọn.`, 'ok'); return; }
          if (assets().length && !confirm(`Đổi phòng sang ${r.ma_phong}? Danh sách thiết bị đã quét sẽ được kiểm tra lại.`)) return;
          room.value = String(r.id);
          room.dispatchEvent(new Event('change'));
          status(`Đã xác định phòng ${r.ma_phong}. Tiếp tục quét QR thiết bị.`, 'ok');
          return;
        }
        if (assets().some(i => i.value === String(r.id))) { status(`${r.ma_tai_san} đã có trong danh sách.`, 'warn'); return; }
        if (!r.ok) { status(r.message, 'error'); return; }

        if (!room.value && r.suggested_room?.id) {
          room.value = String(r.suggested_room.id);
          room.dispatchEvent(new Event('change'));
        }

        const input = document.createElement('input');
        Object.assign(input, {type: 'hidden', name: 'tai_san_id[]', value: String(r.id)});
        input.dataset.code = r.ma_tai_san;
        input.dataset.name = `${r.ten_loai} — ${r.ten_tai_san}`;
        assetInputs.appendChild(input);
        render();

        if (!room.value) {
          status(`Đã thêm ${r.ma_tai_san}. Hãy chọn phòng học để hoàn tất.`, 'ok');
        } else {
          status(r.message || `Đã thêm ${r.ma_tai_san}.`, 'ok');
        }
      }).catch(err => status(err.message, 'error'))
        .finally(() => pendingCodes.delete(key));
    }

    // Đổi phòng: kiểm tra lại từng thiết bị trên MySQL, loại bỏ thiết bị không phù hợp.
    room.addEventListener('change', () => {
      render();
      const list = assets();
      if (!room.value || !list.length) return;
      Promise.all(list.map(i => api({ajax: 'lookup', code: i.dataset.code, room: room.value}).then(r => ({i, r})).catch(() => ({i, r: null}))))
        .then(rows => {
          const removed = rows.filter(({r}) => r && !r.ok).map(({i}) => { i.remove(); return i.dataset.code; });
          render();
          if (removed.length) status(`Đã loại ${removed.join(', ')} vì không phù hợp phòng ${roomCode()}.`, 'warn');
        });
    });
    due.addEventListener('input', render);

    const dispCode = document.getElementById('disp-code');
    const dispName = document.getElementById('disp-name');
    const dispPhone = document.getElementById('disp-phone');
    const dispUnit = document.getElementById('disp-unit');

    [dispCode, dispName, dispPhone, dispUnit].forEach(el => {
      el?.addEventListener('input', () => {
        if (el === dispCode && borrowerId.value && borrowerId.dataset.code !== el.value.trim()) {
          borrowerId.value = '';
          overview.hidden = true;
        }
        render();
      });
    });

    let lookupTimer = null;
    const autoLookupBorrower = () => {
      const q = dispCode ? dispCode.value.trim() : '';
      if (!q || q.length < 2) return;
      api({ajax: 'borrowers', q}).then(({items}) => {
        const exact = items.find(it => it.ma_so_truong.toLowerCase() === q.toLowerCase());
        if (exact) {
          selectBorrower(exact);
        }
      }).catch(() => {});
    };

    dispCode?.addEventListener('blur', autoLookupBorrower);
    dispCode?.addEventListener('input', () => {
      clearTimeout(lookupTimer);
      lookupTimer = setTimeout(autoLookupBorrower, 500);
    });

    form.addEventListener('submit', (e) => {
      const problems = [];
      const bCode = dispCode ? dispCode.value.trim() : '';
      const bName = dispName ? dispName.value.trim() : '';
      if (!borrowerId.value && (!bCode || !bName)) problems.push('nhập mã số và họ tên người mượn');
      if (!room.value) problems.push('xác định phòng học');
      if (!assets().length) problems.push('quét ít nhất một thiết bị');
      if (!due.value) problems.push('nhập hạn trả');
      else if (new Date(due.value) <= new Date()) problems.push('hạn trả phải sau thời điểm hiện tại');
      if (problems.length) {
        e.preventDefault();
        status('Cần ' + problems.join(', ') + '.', 'error');
        scannerRoot?.scrollIntoView({behavior: 'smooth', block: 'start'});
        return;
      }
      const finalName = bName || borrowerId.dataset.name || 'Người mượn';
      const finalCode = bCode || borrowerId.dataset.code || '';
      if (!confirm(`Xác nhận lập phiếu mượn?\n\nNgười mượn: ${finalName} (${finalCode})\nPhòng: ${roomCode()}\nSố thiết bị: ${assets().length}\nHạn trả: ${fmtDate(due.value)}`)) {
        e.preventDefault();
        return;
      }
      const btn = document.getElementById('borrow-submit');
      btn.disabled = true;
      btn.textContent = 'Đang lưu…';
    });

    scanner = scannerRoot ? window.QRScanner?.bind(scannerRoot, (raw) => { handleCode(raw); return true; }, {continuous: true}) : null;
    if (scanner) status = scanner.setStatus;
    scanButton?.addEventListener('click', () => { handleCode(scanInput.value); scanInput.value = ''; scanInput.focus(); });
    scanInput?.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); scanButton.click(); } });

    if (borrowerId.value) {
      selectBorrower({id: borrowerId.value, ma_so_truong: borrowerId.dataset.code, ho_ten: borrowerId.dataset.name, so_dien_thoai: borrowerId.dataset.phone, lop_don_vi: borrowerId.dataset.unit});
    } else if (searchInput.value.trim()) {
      search();
    }
    render();
  }

  /* ======================= TRANG TRẢ ======================= */
  if (page === 'tra') {
    const form = document.getElementById('return-form');
    const rows = [...document.querySelectorAll('tr[data-detail]')];
    const boxes = () => [...document.querySelectorAll('input[name="chi_tiet_id[]"]')];

    function renderReturn() {
      const checked = boxes().filter(b => b.checked);
      const counter = document.getElementById('return-count');
      if (counter) counter.textContent = checked.length;
      const list = document.getElementById('ret-items-list');
      if (list) {
        list.innerHTML = checked.length ? checked.map(b => {
          const sel = b.closest('tr').querySelector('select');
          const bad = sel?.value === 'HU_HONG';
          return `<div class="receipt-item-row"><span>${esc(b.dataset.label)}</span><span class="${bad ? 'text-overdue' : ''}">${bad ? 'Hư hỏng' : 'Bình thường'}</span></div>`;
        }).join('') : '<div class="receipt-item-row text-muted">Chưa chọn thiết bị nhận trả</div>';
      }
      const total = document.getElementById('ret-total-row');
      if (total) total.textContent = `Tổng nhận: ${checked.length} / ${boxes().length} thiết bị${checked.length && checked.length === boxes().length ? ' • Trả đủ' : ''}`;
      rows.forEach(r => {
        const sel = r.querySelector('select');
        const note = r.querySelector('input[name^="ghi_chu"]');
        if (sel && note) note.required = sel.value === 'HU_HONG';
        r.classList.toggle('is-checked', !!r.querySelector('input[name="chi_tiet_id[]"]')?.checked);
      });
    }

    function markCode(raw) {
      const key = raw.trim().toLowerCase();
      const row = rows.find(r => r.dataset.code === key || r.dataset.qr === key || r.dataset.qr === 'tvu:' + key);
      if (!row) return null;
      const box = row.querySelector('input[name="chi_tiet_id[]"]');
      if (!box) { status(`${raw} đã được trả trước đó.`, 'warn'); return true; }
      if (box.checked) { status(`${raw} đã được quét.`, 'warn'); return true; }
      box.checked = true;
      renderReturn();
      row.scrollIntoView({behavior: 'smooth', block: 'nearest'});
      status(`Đã xác nhận ${box.dataset.label}.`, 'ok');
      return true;
    }

    function handleCode(raw) {
      let code = String(raw || '').trim();
      if (!code) return;
      if (code.includes('—')) code = code.split('—')[0].trim();
      if (form && markCode(code)) return;
      if (!form) { location.href = 'muon-tra.php?' + new URLSearchParams({tab: 'tra', q: code}); return; }
      // Không thuộc phiếu đang mở: kiểm tra trên MySQL để tránh trả nhầm phiếu.
      api({ajax: 'lookup', code}).then(r => {
        if (r.type === 'asset' && r.phieu_muon_id) {
          status(`${r.ma_tai_san} thuộc phiếu ${r.ma_phieu_muon}, không thuộc phiếu đang mở.`, 'error');
          if (confirm(`${r.ma_tai_san} thuộc phiếu ${r.ma_phieu_muon}. Mở phiếu đó?`)) {
            location.href = 'muon-tra.php?' + new URLSearchParams({tab: 'tra', id: r.phieu_muon_id, scan: code});
          }
        } else if (r.type === 'asset') {
          status(`${r.ma_tai_san} không có phiếu mượn chưa trả.`, 'error');
        } else {
          status('Mã vừa quét là mã phòng học, không phải thiết bị.', 'error');
        }
      }).catch(err => status(err.message, 'error'));
    }

    rows.forEach(r => r.addEventListener('change', renderReturn));
    form?.addEventListener('submit', (e) => {
      const checked = boxes().filter(b => b.checked);
      if (!checked.length) { e.preventDefault(); status('Quét hoặc chọn ít nhất một thiết bị nhận trả.', 'error'); return; }
      const missing = checked.find(b => {
        const r = b.closest('tr');
        return r.querySelector('select')?.value === 'HU_HONG' && !r.querySelector('input[name^="ghi_chu"]').value.trim();
      });
      if (missing) {
        e.preventDefault();
        missing.closest('tr').querySelector('input[name^="ghi_chu"]').focus();
        status('Ghi rõ tình trạng hư hỏng của thiết bị.', 'error');
        return;
      }
      const left = boxes().length - checked.length;
      if (!confirm(`Xác nhận nhận trả ${checked.length} thiết bị?${left ? `\nPhiếu còn ${left} thiết bị chưa trả.` : '\nPhiếu sẽ được hoàn tất.'}`)) { e.preventDefault(); return; }
      const btn = document.getElementById('return-submit');
      btn.disabled = true;
      btn.textContent = 'Đang lưu…';
    });

    scanner = scannerRoot ? window.QRScanner?.bind(scannerRoot, (raw) => { handleCode(raw); return true; }, {continuous: !!form}) : null;
    if (scanner) status = scanner.setStatus;
    scanButton?.addEventListener('click', () => { handleCode(scanInput.value); scanInput.value = ''; scanInput.focus(); });
    scanInput?.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); scanButton.click(); } });

    if (layout.dataset.scan) markCode(layout.dataset.scan);
    renderReturn();
  }
})();
