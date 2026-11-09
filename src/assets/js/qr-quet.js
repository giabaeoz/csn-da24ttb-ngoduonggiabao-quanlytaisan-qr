/**
 * Thư viện JavaScript Quét mã QR bằng Camera qua jsQR (Offline QR Scanner).
 */

'use strict';

class MayQuetQr {
    constructor(config = {}) {
        this.videoId = config.videoId || 'videoQuet';
        this.canvasId = config.canvasId || 'canvasQuet';
        this.containerId = config.containerId || 'khuVucCamera';
        this.onScan = config.onScan || function () {};
        this.onError = config.onError || function () {};

        this.stream = null;
        this.isRunning = false;
        this.rafId = null;
    }

    bat() {
        if (this.isRunning) {
            return;
        }

        const video = document.getElementById(this.videoId);
        const container = document.getElementById(this.containerId);

        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            this.onError(new Error('Trình duyệt không hỗ trợ truy cập Camera.'));
            return;
        }

        const constraints = {
            video: {
                facingMode: 'environment',
                width: { ideal: 1280 },
                height: { ideal: 720 }
            }
        };

        navigator.mediaDevices.getUserMedia(constraints)
            .then((stream) => {
                this.stream = stream;
                video.srcObject = stream;
                video.setAttribute('playsinline', 'true');
                video.play();
                this.isRunning = true;

                if (container) {
                    container.classList.remove('d-none');
                }

                this.rafId = requestAnimationFrame(() => this._quetKhungHinh());
            })
            .catch((err) => {
                this.onError(err);
            });
    }

    tat() {
        if (!this.isRunning) {
            return;
        }

        this.isRunning = false;
        if (this.rafId) {
            cancelAnimationFrame(this.rafId);
            this.rafId = null;
        }

        if (this.stream) {
            this.stream.getTracks().forEach((track) => track.stop());
            this.stream = null;
        }

        const video = document.getElementById(this.videoId);
        if (video) {
            video.srcObject = null;
        }

        const container = document.getElementById(this.containerId);
        if (container) {
            container.classList.add('d-none');
        }
    }

    chuyenDoi() {
        if (this.isRunning) {
            this.tat();
        } else {
            this.bat();
        }
    }

    _quetKhungHinh() {
        if (!this.isRunning) {
            return;
        }

        const video = document.getElementById(this.videoId);
        const canvas = document.getElementById(this.canvasId);

        if (video && canvas && video.readyState === video.HAVE_ENOUGH_DATA) {
            const ctx = canvas.getContext('2d');
            canvas.height = video.videoHeight;
            canvas.width = video.videoWidth;
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

            const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
            if (typeof jsQR === 'function') {
                const code = jsQR(imageData.data, imageData.width, imageData.height, {
                    inversionAttempts: 'dontInvert',
                });

                if (code && code.data && code.data.trim() !== '') {
                    this.onScan(code.data.trim());
                    return;
                }
            }
        }

        this.rafId = requestAnimationFrame(() => this._quetKhungHinh());
    }
}

