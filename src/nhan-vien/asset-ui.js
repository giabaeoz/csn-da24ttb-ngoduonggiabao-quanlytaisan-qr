'use strict';

document.addEventListener('DOMContentLoaded', () => {
    const renderQrCode = (element) => {
        const payload = element.dataset.qr;
        if (!payload || typeof qrcode !== 'function') return;
        try {
            const qr = qrcode(0, 'M');
            qr.addData(payload, 'Byte');
            qr.make();
            element.innerHTML = qr.createSvgTag({ cellSize: 5, margin: 15, scalable: true });
            const svg = element.querySelector('svg');
            if (svg) {
                svg.setAttribute('width', '100%');
                svg.setAttribute('height', '100%');
            }
        } catch (err) {
            element.textContent = 'Mã QR: ' + payload;
        }
    };

    document.querySelectorAll('[data-qr]').forEach(renderQrCode);

    const scannerRoot = document.querySelector('[data-qr-scanner]');
    const searchInput = document.getElementById('asset-search-keyword');
    const filterForm = document.getElementById('asset-filter-form');

    if (scannerRoot && window.QRScanner) {
        window.QRScanner.bind(scannerRoot, (scannedText, api) => {
            let cleanCode = scannedText.trim();
            if (cleanCode.startsWith('TVU:')) {
                cleanCode = cleanCode.substring(4);
            }
            if (searchInput) {
                searchInput.value = cleanCode;
                api.setStatus('Đã nhận diện mã: ' + cleanCode);
                if (filterForm) {
                    setTimeout(() => filterForm.submit(), 400);
                }
                return true;
            }
            return false;
        });
    }

    const modalCodeInput = document.getElementById('form_ma_tai_san');
    const previewBox = document.getElementById('live-qr-preview-box');
    const previewText = document.getElementById('live-qr-preview-text');

    if (modalCodeInput && previewBox) {
        const updateLivePreview = () => {
            const code = modalCodeInput.value.trim();
            if (!code || !/^[A-Za-z0-9][A-Za-z0-9._-]*$/.test(code)) {
                previewBox.innerHTML = '<span class="text-muted small">Xem trước QR</span>';
                if (previewText) previewText.textContent = 'Nhập mã để xem QR tự sinh';
                return;
            }
            const payload = 'TVU:' + code;
            if (previewText) previewText.textContent = payload;
            try {
                const qr = qrcode(0, 'M');
                qr.addData(payload, 'Byte');
                qr.make();
                previewBox.innerHTML = qr.createSvgTag({ cellSize: 4, margin: 10, scalable: true });
            } catch (e) {
                previewBox.textContent = payload;
            }
        };

        modalCodeInput.addEventListener('input', updateLivePreview);
        updateLivePreview();
    }

    const scopeSelect = document.getElementById('form_pham_vi_tuong_thich');
    const roomContainer = document.getElementById('room-picker-container');

    if (scopeSelect && roomContainer) {
        const toggleRooms = () => {
            if (scopeSelect.value === 'LINH_HOAT') {
                roomContainer.classList.add('d-none');
                roomContainer.querySelectorAll('input[type="checkbox"]').forEach(cb => {
                    cb.checked = false;
                });
            } else {
                roomContainer.classList.remove('d-none');
            }
        };
        scopeSelect.addEventListener('change', toggleRooms);
        toggleRooms();
    }

    const selectAllCb = document.getElementById('select-all-assets');
    const assetCheckboxes = document.querySelectorAll('.asset-check-item');
    const printBtn = document.getElementById('btn-print-selected');
    const selectionCountEl = document.getElementById('selection-count');

    const updateSelectionState = () => {
        const checkedBoxes = document.querySelectorAll('.asset-check-item:checked');
        const checkedCount = checkedBoxes.length;

        if (printBtn) {
            printBtn.disabled = checkedCount === 0;
            printBtn.textContent = `In QR đã chọn (${checkedCount})`;
        }

        if (selectionCountEl) {
            selectionCountEl.textContent = `Đã chọn ${checkedCount} tài sản`;
        }

        assetCheckboxes.forEach(cb => {
            const row = cb.closest('tr');
            if (row) {
                row.classList.toggle('row-selected', cb.checked);
            }
        });
    };

    if (selectAllCb) {
        selectAllCb.addEventListener('change', () => {
            assetCheckboxes.forEach(cb => {
                cb.checked = selectAllCb.checked;
            });
            updateSelectionState();
        });
    }

    assetCheckboxes.forEach(cb => {
        cb.addEventListener('change', () => {
            if (selectAllCb) {
                selectAllCb.checked = assetCheckboxes.length > 0 &&
                    document.querySelectorAll('.asset-check-item:checked').length === assetCheckboxes.length;
            }
            updateSelectionState();
        });
    });

    updateSelectionState();

    if (printBtn) {
        printBtn.addEventListener('click', () => {
            const checkedIds = Array.from(document.querySelectorAll('.asset-check-item:checked'))
                .map(cb => cb.value);
            if (!checkedIds.length) return;

            document.querySelectorAll('#staff-print-sheet .staff-qr-print-card').forEach(card => {
                if (checkedIds.includes(card.dataset.assetId)) {
                    card.style.display = 'flex';
                } else {
                    card.style.display = 'none';
                }
            });

            window.print();
        });
    }

    document.querySelectorAll('[data-action="download-svg"]').forEach(btn => {
        btn.addEventListener('click', () => {
            const qrWrapper = document.getElementById('detail-modal-qr');
            const svg = qrWrapper ? qrWrapper.querySelector('svg') : null;
            if (!svg) return;
            const code = btn.dataset.assetCode || 'tai_san';
            const xml = new XMLSerializer().serializeToString(svg);
            const blob = new Blob([xml], { type: 'image/svg+xml;charset=utf-8' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `QR-${code}.svg`;
            a.click();
            setTimeout(() => URL.revokeObjectURL(url), 1000);
        });
    });

    document.querySelectorAll('[data-action="download-png"]').forEach(btn => {
        btn.addEventListener('click', () => {
            const qrWrapper = document.getElementById('detail-modal-qr');
            const svg = qrWrapper ? qrWrapper.querySelector('svg') : null;
            if (!svg) return;
            const code = btn.dataset.assetCode || 'tai_san';
            const xml = new XMLSerializer().serializeToString(svg);
            const img = new Image();
            img.src = 'data:image/svg+xml;base64,' + btoa(unescape(encodeURIComponent(xml)));
            img.onload = () => {
                const canvas = document.createElement('canvas');
                canvas.width = 512;
                canvas.height = 512;
                const ctx = canvas.getContext('2d');
                ctx.fillStyle = '#ffffff';
                ctx.fillRect(0, 0, 512, 512);
                ctx.drawImage(img, 20, 20, 472, 472);
                const a = document.createElement('a');
                a.href = canvas.toDataURL('image/png');
                a.download = `QR-${code}.png`;
                a.click();
            };
        });
    });

    document.querySelectorAll('[data-action="print-single-qr"]').forEach(btn => {
        btn.addEventListener('click', () => {
            const assetId = btn.dataset.assetId;
            if (!assetId) return;
            document.querySelectorAll('#staff-print-sheet .staff-qr-print-card').forEach(card => {
                if (card.dataset.assetId === assetId) {
                    card.style.display = 'flex';
                } else {
                    card.style.display = 'none';
                }
            });
            window.print();
        });
    });
});

