/**
 * Thư viện JavaScript Sinh mã QR và In tem (Offline QR Code Generator).
 * Tích hợp chặt chẽ với qrcode.js và qrcode_UTF8.js.
 */

'use strict';

/**
 * Sinh mã QR dạng SVG chất lượng cao vào một phần tử HTML.
 *
 * @param {HTMLElement|string} target Phần tử DOM hoặc ID của phần tử
 * @param {string} text Nội dung cần mã hóa (mã QR)
 * @param {object} options Tùy chọn: size (kích thước px), level ('L','M','Q','H'), margin
 * @returns {SVGElement|null}
 */
function taoMaQr(target, text, options = {}) {
    const el = typeof target === 'string' ? document.getElementById(target) : target;
    if (!el) {
        return null;
    }

    const str = String(text || '').trim();
    if (!str) {
        el.innerHTML = '';
        return null;
    }

    const size = options.size || 140;
    const level = options.level || 'M';
    const margin = (options.margin !== undefined) ? options.margin : 2;

    try {
        if (typeof qrcode !== 'function') {
            console.error('Thư viện qrcode.js chưa được nạp!');
            el.innerHTML = '<span class="text-danger small">Lỗi: Chưa nạp thư viện qrcode.js</span>';
            return null;
        }

        const qr = qrcode(0, level);
        qr.addData(str, 'Byte');
        qr.make();

        const cellSize = Math.max(2, Math.floor(size / qr.getModuleCount()));
        el.innerHTML = qr.createSvgTag({
            cellSize: cellSize,
            margin: margin,
            scalable: true
        });

        const svg = el.querySelector('svg');
        if (svg) {
            svg.setAttribute('width', size);
            svg.setAttribute('height', size);
            svg.style.maxWidth = '100%';
            svg.style.height = 'auto';
            svg.style.display = 'block';
            svg.style.margin = '0 auto';
        }

        return svg;
    } catch (err) {
        console.error('Lỗi khi sinh mã QR cho [' + str + ']:', err);
        el.innerHTML = '<span class="text-danger small">Không thể tạo mã QR: ' + err.message + '</span>';
        return null;
    }
}

// Cung cấp wrapper tương thích cho new QRCode(element, options)
if (typeof window.QRCode === 'undefined') {
    window.QRCode = function (element, options) {
        let text = '';
        let size = 140;
        let level = 'M';

        if (typeof options === 'string') {
            text = options;
        } else if (typeof options === 'object' && options !== null) {
            text = options.text || '';
            size = options.width || options.height || 140;
            if (options.correctLevel) {
                level = options.correctLevel;
            }
        }

        return taoMaQr(element, text, { size: size, level: level });
    };

    window.QRCode.CorrectLevel = {
        L: 'L',
        M: 'M',
        Q: 'Q',
        H: 'H'
    };
}

/**
 * Tự động tìm và sinh mã QR cho toàn bộ các phần tử có thuộc tính [data-qr].
 */
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-qr]').forEach(function (el) {
        const qrText = el.getAttribute('data-qr');
        const size = parseInt(el.getAttribute('data-qr-size') || '140', 10);
        if (qrText && qrText.trim() !== '') {
            taoMaQr(el, qrText.trim(), { size: size });
        }
    });
});
