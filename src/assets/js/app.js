/**
 * JavaScript Dùng chung toàn hệ thống
 */

document.addEventListener('DOMContentLoaded', function () {
    // 1. Tự động ẩn thông báo flash sau 5 giây để không che khuất màn hình
    const thongBao = document.getElementById('thong-bao-he-thong');
    if (thongBao) {
        setTimeout(function () {
            const alertInstance = bootstrap.Alert.getOrCreateInstance(thongBao);
            if (alertInstance) {
                alertInstance.close();
            }
        }, 5000);
    }
});

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

