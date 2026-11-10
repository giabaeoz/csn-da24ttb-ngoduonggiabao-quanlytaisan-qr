/**
 * JavaScript dùng chung toàn hệ thống (tương tác giao diện thuần & hộp thoại bật lên)
 */

(function () {
    let urlModalHienTai = '';
    let formChoXacNhan = null;
    let nutGuiChoXacNhan = null;

    /**
     * Khởi tạo tự động ẩn thông báo flash sau 5 giây.
     */
    function khoiTaoThongBaoFlash(root = document) {
        const thongBao = root.getElementById ? root.getElementById('thong-bao-he-thong') : document.getElementById('thong-bao-he-thong');
        if (thongBao && typeof bootstrap !== 'undefined' && bootstrap.Alert) {
            setTimeout(function () {
                if ( document.body.contains(thongBao) ) {
                    const alertInstance = bootstrap.Alert.getOrCreateInstance(thongBao);
                    if (alertInstance) {
                        alertInstance.close();
                    }
                }
            }, 5000);
        }
    }

    /**
     * Chuyển đổi các biểu mẫu có onsubmit="return confirm('...')" sang thuộc tính data-xac-nhan
     * để hiển thị hộp thoại xác nhận bật lên thay vì hộp thoại mặc định của trình duyệt.
     */
    function chuanHoaFormXacNhan(root = document) {
        const forms = root.querySelectorAll('form[onsubmit]');
        forms.forEach(function (f) {
            const attr = f.getAttribute('onsubmit') || '';
            const match = attr.match(/confirm\(\s*(['"])([\s\S]*?)\1\s*\)/);
            if (match && match[2]) {
                f.setAttribute('data-xac-nhan', match[2]);
                f.removeAttribute('onsubmit');
            }
        });
    }

    /**
     * Kiểm tra liên kết có thuộc nhóm thao tác Xem / Thêm / Sửa đối tượng cần mở bằng form bật lên không.
     */
    function laLienKetMoModal(anchor) {
        if (!anchor || !anchor.href) return false;
        if (anchor.target === '_blank' || anchor.hasAttribute('download')) return false;
        if (anchor.hasAttribute('data-bs-toggle') || anchor.hasAttribute('data-khong-modal')) return false;
        if (anchor.closest('.sidebar-app') || anchor.closest('.topbar-app') || anchor.closest('.pagination')) return false;

        let urlObj;
        try {
            urlObj = new URL(anchor.href, window.location.origin);
        } catch (e) {
            return false;
        }
        if (urlObj.origin !== window.location.origin) return false;

        const path = urlObj.pathname;
        // Các trang thêm, sửa, tạo, báo cáo, chi tiết của mọi đối tượng
        if (/\/(tai-san|nguoi-muon|tai-khoan|muon-tra|dieu-chuyen|kiem-ke|su-co)\/(them|sua|tao|bao-cao|chi-tiet)\.php$/i.test(path)) {
            return true;
        }
        // Liên kết chọn xem nhanh người mượn trong bảng người mượn
        if (/\/nguoi-muon\/index\.php$/i.test(path) && urlObj.searchParams.has('chon_id') && !urlObj.searchParams.has('trang')) {
            const text = (anchor.textContent || '').trim();
            const aria = anchor.getAttribute('aria-label') || '';
            if (aria === 'Xem' || text === 'Xem') {
                return true;
            }
        }
        return false;
    }

    /**
     * Nạp các thẻ <script src="..."> chưa có trên trang trước khi chạy script nội tuyến.
     */
    async function napScriptNgoai(doc) {
        const scripts = Array.from(doc.querySelectorAll('script[src]'));
        for (const s of scripts) {
            const src = s.getAttribute('src');
            if (!src) continue;
            if (/bootstrap\.bundle|assets\/js\/app\.js/i.test(src)) continue;

            const daCo = Array.from(document.scripts).some(function (existing) {
                return existing.getAttribute('src') === src || existing.src === s.src;
            });
            if (daCo) continue;

            await new Promise(function (resolve) {
                const tag = document.createElement('script');
                tag.src = src;
                tag.onload = resolve;
                tag.onerror = resolve;
                document.body.appendChild(tag);
            });
        }
    }

    /**
     * Thực thi các đoạn script nội tuyến bên trong vùng nội dung vừa nạp vào modal.
     */
    function chayScriptNoiTuyen(container) {
        const inlineScripts = Array.from(container.querySelectorAll('script:not([src])'));
        inlineScripts.forEach(function (oldScript) {
            const code = oldScript.textContent || '';
            oldScript.remove();
            if (!code.trim()) return;

            // Chuyển các khai báo hàm cấp cao nhất sang thuộc tính của window để các sự kiện onclick="..." gọi được,
            // đồng thời bọc trong IIFE để tránh lỗi khai báo trùng biến const/let khi mở modal nhiều lần.
            const transformed = code.replace(
                /(^|\n)\s*(async\s+)?function\s+([a-zA-Z0-9_]+)\s*\(/g,
                '$1window.$3 = $2function $3('
            );

            const origAddEventListener = document.addEventListener.bind(document);
            document.addEventListener = function (type, listener, options) {
                if (type === 'DOMContentLoaded' && typeof listener === 'function') {
                    try {
                        listener();
                    } catch (err) {
                        console.error(err);
                    }
                    return;
                }
                return origAddEventListener(type, listener, options);
            };

            try {
                const fn = new Function(transformed);
                fn();
            } catch (err) {
                console.error('Lỗi thực thi script trong modal:', err);
            } finally {
                document.addEventListener = origAddEventListener;
            }
        });
    }

    /**
     * Chuẩn hóa bố cục và nút bấm bên trong thân modal để tối ưu tương tác bật lên.
     */
    function chuanHoaNoiDungModal(modalBody, modalLabel, url) {
        chuanHoaFormXacNhan(modalBody);

        // Lấy tiêu đề trang đưa lên thanh tiêu đề của modal
        const tieuDeEl = modalBody.querySelector('.tieu-de-trang, h1.h3, h1');
        if (tieuDeEl && modalLabel) {
            modalLabel.textContent = tieuDeEl.textContent.trim();
        }

        // Bỏ thông báo flash trùng lặp bên trong modal nếu có
        const flashTrongModal = modalBody.querySelector('#thong-bao-he-thong');
        if (flashTrongModal && !flashTrongModal.classList.contains('alert-danger')) {
            flashTrongModal.remove();
        }

        // Nếu mở từ nguoi-muon/index.php?chon_id=..., chỉ hiển thị khối chi tiết 2 cột của người mượn
        if (/\/nguoi-muon\/index\.php/i.test(url) && url.indexOf('chon_id=') !== -1) {
            const cacHang = modalBody.querySelectorAll('.row.g-4.mb-4');
            if (cacHang.length > 0) {
                const khoiChiTiet = cacHang[cacHang.length - 1];
                modalBody.innerHTML = '';
                modalBody.appendChild(khoiChiTiet);
                if (modalLabel) {
                    modalLabel.textContent = 'Thông tin người mượn';
                }
            }
        }

        // Ẩn thanh breadcrumb và khối tiêu đề trang dư thừa nếu không chứa nút tác vụ đặc biệt
        const breadcrumbs = modalBody.querySelectorAll('nav[aria-label="breadcrumb"], .breadcrumb-app');
        breadcrumbs.forEach(function (b) { b.remove(); });

        // Chuyển các nút "Quay lại", "Quay lại danh sách", "Hủy", "Hủy bỏ" thành nút đóng modal
        const links = modalBody.querySelectorAll('a.btn, a');
        links.forEach(function (a) {
            const txt = (a.textContent || '').trim();
            if (/^(Quay lại( danh sách)?|Hủy( bỏ)?)$/i.test(txt)) {
                a.setAttribute('href', '#');
                a.setAttribute('data-dong-modal', '1');
            }
        });

        // Nếu khối tiêu đề đầu trang trong modal chỉ chứa tiêu đề (đã đưa lên thanh modal-header)
        // và nút Quay lại, ẩn nút Quay lại trên đầu hoặc ẩn cả hàng tiêu đề trùng lặp
        if (tieuDeEl) {
            const hangDau = tieuDeEl.closest('.d-flex.justify-content-between');
            if (hangDau) {
                const nutQuayLaiDau = hangDau.querySelectorAll('a[data-dong-modal="1"]');
                nutQuayLaiDau.forEach(function (btn) { btn.remove(); });
                const nutConLai = hangDau.querySelectorAll('a.btn, button.btn, form');
                if (nutConLai.length === 0) {
                    hangDau.remove();
                }
            }
        }

        // Nếu trong modal có modal con (ví dụ #modalHoanTatXuLy trong su-co/chi-tiet.php),
        // khi bấm nút mở modal con sẽ hiển thị thẳng biểu mẫu đó ngay trong modal hiện tại.
        const nutMoModalCon = modalBody.querySelectorAll('[data-bs-toggle="modal"][data-bs-target]');
        nutMoModalCon.forEach(function (btn) {
            const targetSel = btn.getAttribute('data-bs-target');
            const targetModal = targetSel ? modalBody.querySelector(targetSel) : null;
            if (targetModal) {
                btn.removeAttribute('data-bs-toggle');
                btn.addEventListener('click', function (ev) {
                    ev.preventDefault();
                    const dialog = targetModal.querySelector('.modal-content');
                    if (dialog) {
                        targetModal.classList.remove('modal', 'fade');
                        targetModal.style.display = 'block';
                        targetModal.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                });
            }
        });
    }

    /**
     * Mở hộp thoại tương tác (#modalTuongTac) và nạp nội dung từ đường dẫn đích.
     */
    async function moModalTuongTac(url) {
        const modalEl = document.getElementById('modalTuongTac');
        const modalDialog = document.getElementById('modalTuongTacDialog');
        const modalLabel = document.getElementById('modalTuongTacLabel');
        const modalBody = document.getElementById('modalTuongTacBody');
        if (!modalEl || !modalBody || typeof bootstrap === 'undefined') {
            window.location.href = url;
            return;
        }

        urlModalHienTai = url;
        const laFormDon = /\/(them|sua|tao|bao-cao)\.php/i.test(url) && !/dieu-chuyen\/them\.php/i.test(url);
        if (modalDialog) {
            modalDialog.className = 'modal-dialog modal-dialog-centered modal-dialog-scrollable ' + (laFormDon ? 'modal-lg' : 'modal-xl');
        }
        if (modalLabel) {
            modalLabel.textContent = 'Đang tải...';
        }
        modalBody.innerHTML = '<div class="text-center py-5 text-phu"><span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Đang tải dữ liệu...</div>';

        const modalInstance = bootstrap.Modal.getOrCreateInstance(modalEl);
        modalInstance.show();

        try {
            const res = await fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin'
            });
            const html = await res.text();
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const mainEl = doc.querySelector('main.noi-dung-chinh');
            if (!mainEl) {
                window.location.href = url;
                return;
            }

            await napScriptNgoai(doc);
            modalBody.innerHTML = mainEl.innerHTML;
            chuanHoaNoiDungModal(modalBody, modalLabel, res.url || url);
            chayScriptNoiTuyen(modalBody);
        } catch (err) {
            window.location.href = url;
        }
    }

    /**
     * Hiển thị nhanh thông tin chi tiết của một bản ghi dưới dạng hộp thoại bật lên.
     */
    window.xemChiTietNhanh = function (tieuDe, cacTruong, urlSua) {
        const modalEl = document.getElementById('modalTuongTac');
        const modalDialog = document.getElementById('modalTuongTacDialog');
        const modalLabel = document.getElementById('modalTuongTacLabel');
        const modalBody = document.getElementById('modalTuongTacBody');
        if (!modalEl || !modalBody || typeof bootstrap === 'undefined') return;

        if (modalDialog) {
            modalDialog.className = 'modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable';
        }
        if (modalLabel) {
            modalLabel.textContent = tieuDe || 'Chi tiết thông tin';
        }

        let rowsHtml = '';
        (cacTruong || []).forEach(function (muc) {
            const nhan = String(muc.nhan || '').replace(/</g, '&lt;');
            const giaTri = String(muc.gia_tri ?? '---').replace(/</g, '&lt;');
            rowsHtml += `
                <div class="col-12 col-sm-6">
                    <div class="mo-ta-phu mb-1">${nhan}</div>
                    <div class="fw-semibold text-dark">${giaTri || '---'}</div>
                </div>
            `;
        });

        let footerHtml = `
            <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                <button type="button" class="btn btn-phu" data-bs-dismiss="modal">Đóng</button>
                ${urlSua ? `<a href="${urlSua}" class="btn btn-chinh">Sửa thông tin</a>` : ''}
            </div>
        `;

        modalBody.innerHTML = `<div class="row g-3">${rowsHtml}</div>${footerHtml}`;
        const modalInstance = bootstrap.Modal.getOrCreateInstance(modalEl);
        modalInstance.show();
    };

    /**
     * Làm mới nội dung vùng <main class="noi-dung-chinh"> của trang hiện tại từ tài liệu HTML hoặc tải lại qua AJAX.
     */
    async function capNhatNoiDungTrangChinh(docTuPhanHoi, urlPhanHoi) {
        const mainHienTai = document.querySelector('main.noi-dung-chinh');
        if (!mainHienTai) {
            window.location.reload();
            return;
        }

        let doc = docTuPhanHoi;
        const cungTrang = urlPhanHoi && (new URL(urlPhanHoi, window.location.origin).pathname === window.location.pathname);

        if (!doc || !cungTrang) {
            try {
                const res = await fetch(window.location.href, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin'
                });
                const html = await res.text();
                doc = new DOMParser().parseFromString(html, 'text/html');
            } catch (e) {
                window.location.reload();
                return;
            }
        }

        const mainMoi = doc.querySelector('main.noi-dung-chinh');
        if (!mainMoi) {
            window.location.reload();
            return;
        }

        await napScriptNgoai(doc);
        mainHienTai.innerHTML = mainMoi.innerHTML;
        chuanHoaFormXacNhan(mainHienTai);
        chayScriptNoiTuyen(mainHienTai);
        khoiTaoThongBaoFlash(document);
    }

    /**
     * Gửi biểu mẫu bằng AJAX (dùng cho biểu mẫu trong modal và biểu mẫu xác nhận xóa/khóa).
     */
    async function guiFormAjax(form, submitter) {
        const actionUrl = form.getAttribute('action') || urlModalHienTai || window.location.href;
        const method = (form.getAttribute('method') || 'POST').toUpperCase();
        const formData = new FormData(form);
        if (submitter && submitter.name) {
            formData.append(submitter.name, submitter.value || '');
        }

        const nutGui = submitter || form.querySelector('button[type="submit"]');
        if (nutGui) {
            nutGui.disabled = true;
        }

        try {
            const res = await fetch(actionUrl, {
                method: method,
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin'
            });
            const html = await res.text();
            const doc = new DOMParser().parseFromString(html, 'text/html');
            const mainMoi = doc.querySelector('main.noi-dung-chinh');

            const modalEl = document.getElementById('modalTuongTac');
            const modalBody = document.getElementById('modalTuongTacBody');
            const modalLabel = document.getElementById('modalTuongTacLabel');
            const dangTrongModalTuongTac = modalEl && modalEl.classList.contains('show') && modalBody && modalBody.contains(form);

            // Kiểm tra xem phản hồi có phải là lỗi kiểm tra dữ liệu ngay trên form vừa gửi không
            const coLoiTrenForm = mainMoi && mainMoi.querySelector('.alert-danger') && !mainMoi.querySelector('#thong-bao-he-thong.alert-success');
            const vanOTrangForm = /\/(them|sua|tao|bao-cao)\.php/i.test(res.url || actionUrl);

            if (dangTrongModalTuongTac && coLoiTrenForm && vanOTrangForm) {
                modalBody.innerHTML = mainMoi.innerHTML;
                chuanHoaNoiDungModal(modalBody, modalLabel, res.url || actionUrl);
                chayScriptNoiTuyen(modalBody);
                return;
            }

            // Nếu gửi từ su-co/chi-tiet.php hoặc trang chi tiết đang mở trong modal và vẫn ở trang chi tiết đó
            if (dangTrongModalTuongTac && /\/chi-tiet\.php/i.test(res.url || '') && mainMoi) {
                modalBody.innerHTML = mainMoi.innerHTML;
                chuanHoaNoiDungModal(modalBody, modalLabel, res.url);
                chayScriptNoiTuyen(modalBody);
                await capNhatNoiDungTrangChinh(null, '');
                return;
            }

            // Đóng tất cả các modal đang mở
            document.querySelectorAll('.modal.show').forEach(function (m) {
                const inst = bootstrap.Modal.getInstance(m);
                if (inst) inst.hide();
            });

            await capNhatNoiDungTrangChinh(doc, res.url || actionUrl);
        } catch (err) {
            form.submit();
        } finally {
            if (nutGui) {
                nutGui.disabled = false;
                delete nutGui.dataset.dangXuLy;
            }
        }
    }

    /**
     * Mở hộp thoại xác nhận bật lên (#modalXacNhan) khi người dùng bấm Xóa / Khóa / Mở khóa.
     */
    function moModalXacNhan(thongDiep, form, submitter) {
        const modalEl = document.getElementById('modalXacNhan');
        const noiDungEl = document.getElementById('modalXacNhanNoiDung');
        const btnDongY = document.getElementById('btnDongYXacNhan');
        if (!modalEl || !noiDungEl || !btnDongY || typeof bootstrap === 'undefined') {
            if (window.confirm(thongDiep)) {
                guiFormAjax(form, submitter);
            }
            return;
        }

        formChoXacNhan = form;
        nutGuiChoXacNhan = submitter;
        noiDungEl.textContent = thongDiep;

        const hanhDongInput = form.querySelector('input[name="hanh_dong"]');
        const laXoa = (hanhDongInput && hanhDongInput.value === 'xoa') ||
                      (submitter && (submitter.classList.contains('btn-bieu-tuong-xoa') || submitter.classList.contains('btn-nguy-hiem')));

        btnDongY.className = laXoa ? 'btn btn-nguy-hiem' : 'btn btn-chinh';

        const inst = bootstrap.Modal.getOrCreateInstance(modalEl);
        inst.show();
    }

    document.addEventListener('DOMContentLoaded', function () {
        khoiTaoThongBaoFlash(document);
        chuanHoaFormXacNhan(document);

        // Dừng camera nếu người dùng đóng modal tương tác
        const modalTuongTacEl = document.getElementById('modalTuongTac');
        if (modalTuongTacEl) {
            modalTuongTacEl.addEventListener('hidden.bs.modal', function () {
                if (typeof window.dungCamera === 'function') {
                    try { window.dungCamera(); } catch (e) {}
                }
            });
        }

        // Xử lý nút Đồng ý trên Modal Xác nhận (#modalXacNhan)
        const btnDongYXacNhan = document.getElementById('btnDongYXacNhan');
        if (btnDongYXacNhan) {
            btnDongYXacNhan.addEventListener('click', function () {
                const modalXacNhanEl = document.getElementById('modalXacNhan');
                if (modalXacNhanEl && typeof bootstrap !== 'undefined') {
                    const inst = bootstrap.Modal.getInstance(modalXacNhanEl);
                    if (inst) inst.hide();
                }
                if (formChoXacNhan) {
                    const f = formChoXacNhan;
                    const s = nutGuiChoXacNhan;
                    formChoXacNhan = null;
                    nutGuiChoXacNhan = null;
                    guiFormAjax(f, s);
                }
            });
        }

        // Lắng nghe sự kiện click toàn cục cho các liên kết mở modal hoặc đóng modal
        document.addEventListener('click', function (e) {
            const nutDong = e.target.closest('[data-dong-modal="1"]');
            if (nutDong) {
                e.preventDefault();
                const m = nutDong.closest('.modal');
                if (m && typeof bootstrap !== 'undefined') {
                    const inst = bootstrap.Modal.getInstance(m);
                    if (inst) inst.hide();
                }
                return;
            }

            if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) {
                return;
            }

            const anchor = e.target.closest('a');
            if (anchor && laLienKetMoModal(anchor)) {
                e.preventDefault();
                moModalTuongTac(anchor.href);
            }
        });

        // Lắng nghe sự kiện submit biểu mẫu toàn cục (xác nhận xóa/khóa, gửi form trong modal, chống bấm đúp)
        document.addEventListener('submit', function (e) {
            const form = e.target;
            if (!(form instanceof HTMLFormElement) || e.defaultPrevented) {
                return;
            }

            const method = (form.getAttribute('method') || 'GET').toUpperCase();
            const submitter = e.submitter || form.querySelector('button[type="submit"]');

            // 1. Nếu biểu mẫu có yêu cầu xác nhận bật lên (data-xac-nhan)
            const thongDiepXacNhan = form.getAttribute('data-xac-nhan');
            if (thongDiepXacNhan) {
                e.preventDefault();
                moModalXacNhan(thongDiepXacNhan, form, submitter);
                return;
            }

            // 2. Nếu là biểu mẫu POST nằm trong các hộp thoại modal
            const trongModal = form.closest('#modalTuongTac, #modalForm, #modalDatLaiMatKhau, #modalPhongTruc, #modalPhongHoc, #modalLoai, #modalGhiNhan');
            if (trongModal && method === 'POST') {
                if (typeof form.checkValidity === 'function' && !form.checkValidity()) {
                    return;
                }
                e.preventDefault();
                guiFormAjax(form, submitter);
                return;
            }

            // 3. Chống bấm đúp cho các biểu mẫu thông thường
            if (typeof form.checkValidity === 'function' && !form.checkValidity()) {
                return;
            }
            if (!submitter || submitter.dataset.dangXuLy === '1') {
                return;
            }
            submitter.dataset.dangXuLy = '1';

            const laNutBieuTuongDon = submitter.classList.contains('btn-bieu-tuong');
            if (!laNutBieuTuongDon && !submitter.querySelector('.spinner-border')) {
                const spinner = document.createElement('span');
                spinner.className = 'spinner-border spinner-border-sm';
                spinner.setAttribute('role', 'status');
                spinner.setAttribute('aria-hidden', 'true');
                submitter.insertBefore(spinner, submitter.firstChild);
            }

            setTimeout(function () {
                submitter.disabled = true;
            }, 0);
        });
    });
})();

/**
 * Hàm hỗ trợ gửi request AJAX/fetch kèm CSRF token tự động.
 *
 * @param {string} url Đường dẫn API
 * @param {object} options Tùy chọn fetch
 * @returns {Promise<Response>}
 */
async function fetchWithCsrf(url, options = {}) {
    const metaCsrf = document.querySelector('input[name="csrf_token"]');
    const csrfToken = metaCsrf ? metaCsrf.value : '';

    options.headers = options.headers || {};
    if (csrfToken && !options.headers['X-CSRF-TOKEN']) {
        options.headers['X-CSRF-TOKEN'] = csrfToken;
    }

    return fetch(url, options);
}
