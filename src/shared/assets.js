'use strict';

document.querySelectorAll('[data-qr]').forEach((element) => {
    try {
        const code = qrcode(0, 'M');
        code.addData(element.dataset.qr, 'Byte');
        code.make();
        element.innerHTML = code.createSvgTag({ cellSize: 5, margin: 20, scalable: true });
        element.querySelector('svg')?.setAttribute('width', '256');
        element.querySelector('svg')?.setAttribute('height', '256');
    } catch (error) {
        element.textContent = 'Chưa hiển thị được QR. Mã nhận diện: ' + element.dataset.qr;
    }
});

const livePreview = document.getElementById('asset-qr-preview');
const assetCodeInput = document.querySelector('#asset-form [name="ma_tai_san"]');
if (livePreview && assetCodeInput) {
    const renderPreview = () => {
        const code = assetCodeInput.value.trim();
        const text = livePreview.querySelector('[data-qr-text]');
        const graphic = livePreview.querySelector('[data-qr-live]');
        graphic.replaceChildren();
        if (!/^[A-Za-z0-9][A-Za-z0-9._-]{0,29}$/.test(code)) {
            text.textContent = 'Nhập mã tài sản hợp lệ để xem QR sẽ được tạo.';
            return;
        }
        const payload = livePreview.dataset.existingQr || 'TVU:' + code;
        text.textContent = 'Nội dung QR: ' + payload;
        try {
            const qr = qrcode(0, 'M');
            qr.addData(payload, 'Byte');
            qr.make();
            graphic.innerHTML = qr.createSvgTag({cellSize: 5, margin: 20, scalable: true});
            graphic.querySelector('svg')?.setAttribute('width', '256');
            graphic.querySelector('svg')?.setAttribute('height', '256');
        } catch (error) {
            graphic.textContent = 'Chưa tạo được ảnh xem trước QR.';
        }
    };
    assetCodeInput.addEventListener('input', renderPreview);
    renderPreview();
}

document.querySelectorAll('[data-download-qr]').forEach(button => {
    button.addEventListener('click', () => {
        const svg = document.querySelector('#asset-qr svg');
        if (!svg) return;
        const xml = new XMLSerializer().serializeToString(svg);
        const url = URL.createObjectURL(new Blob([xml], {type: 'image/svg+xml;charset=utf-8'}));
        const link = document.createElement('a');
        link.href = url;
        link.download = 'QR-' + button.dataset.assetCode.replace(/[^A-Za-z0-9._-]/g, '_') + '.svg';
        link.click();
        setTimeout(() => URL.revokeObjectURL(url), 1000);
    });
});

document.querySelectorAll('[data-download-qr-png]').forEach(button => {
    button.addEventListener('click', async () => {
        const svg = document.querySelector('#asset-qr svg');
        if (!svg) return;
        const xml = new XMLSerializer().serializeToString(svg);
        const image = new Image();
        image.src = 'data:image/svg+xml;charset=utf-8,' + encodeURIComponent(xml);
        try { await image.decode(); } catch { return; }
        const canvas = document.createElement('canvas');
        canvas.width = canvas.height = 512;
        const context = canvas.getContext('2d');
        context.fillStyle = 'white';
        context.fillRect(0, 0, 512, 512);
        context.drawImage(image, 0, 0, 512, 512);
        canvas.toBlob(blob => {
            if (!blob) return;
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = 'QR-' + button.dataset.assetCode.replace(/[^A-Za-z0-9._-]/g, '_') + '.png';
            link.click();
            setTimeout(() => URL.revokeObjectURL(url), 1000);
        }, 'image/png');
    });
});

const checkboxes = Array.from(document.querySelectorAll('.asset-check'));
const selectAll = document.getElementById('select-all');
const printButton = document.getElementById('print-selected');
const count = document.getElementById('selection-count');

function updateSelection() {
    const selected = checkboxes.filter((checkbox) => checkbox.checked).map((checkbox) => checkbox.value);
    if (count) count.textContent = 'Đã chọn ' + selected.length + ' tài sản';
    if (printButton) {
        printButton.textContent = 'In QR đã chọn (' + selected.length + ')';
        printButton.disabled = selected.length === 0;
    }
    if (selectAll) {
        selectAll.checked = checkboxes.length > 0 && selected.length === checkboxes.length;
        selectAll.indeterminate = selected.length > 0 && selected.length < checkboxes.length;
    }
    document.querySelectorAll('.qr-label').forEach((label) => {
        label.classList.toggle('print-selected', selected.includes(label.dataset.assetId));
    });
}

if (selectAll) {
    selectAll.addEventListener('change', () => {
        checkboxes.forEach((checkbox) => { checkbox.checked = selectAll.checked; });
        updateSelection();
    });
}
checkboxes.forEach((checkbox) => checkbox.addEventListener('change', updateSelection));
if (printButton) {
    printButton.addEventListener('click', () => window.print());
}
