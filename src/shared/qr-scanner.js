'use strict';

// Camera and image-file QR reader shared by the asset and staff pages.
// The decoded text is passed to the page; no QR content is treated as a URL.
window.QRScanner = {
  bind(root, onScan) {
    const start = root.querySelector('[data-qr-start]');
    const stopButton = root.querySelector('[data-qr-stop]');
    const file = root.querySelector('[data-qr-file]');
    const preview = root.querySelector('[data-qr-preview]');
    const message = root.querySelector('[data-qr-status]');
    if (!start || !stopButton || !file || !preview || !message) return;

    let stream = null;
    let frame = 0;
    let busy = false;
    let lastFrame = 0;
    const canvas = document.createElement('canvas');
    const context = canvas.getContext('2d', {willReadFrequently: true});
    const originalPreview = preview.textContent;
    const api = {
      setStatus(text) { message.textContent = text; },
      stop() { stopCamera(); }
    };

    function decode(source, width, height) {
      if (!context || !width || !height || typeof window.jsQR !== 'function') return null;
      const scale = Math.min(1, 1000 / Math.max(width, height));
      canvas.width = Math.max(1, Math.round(width * scale));
      canvas.height = Math.max(1, Math.round(height * scale));
      context.drawImage(source, 0, 0, canvas.width, canvas.height);
      const pixels = context.getImageData(0, 0, canvas.width, canvas.height);
      return window.jsQR(pixels.data, pixels.width, pixels.height, {inversionAttempts: 'attemptBoth'})?.data || null;
    }

    function stopCamera() {
      if (frame) cancelAnimationFrame(frame);
      frame = 0;
      if (stream) stream.getTracks().forEach(track => track.stop());
      stream = null;
      preview.replaceChildren(document.createTextNode(originalPreview));
      start.hidden = false;
      stopButton.hidden = true;
    }

    async function scanFrame(video, time) {
      if (!stream) return;
      if (!busy && video.readyState >= 2 && time - lastFrame >= 140) {
        busy = true;
        lastFrame = time;
        try {
          const text = decode(video, video.videoWidth, video.videoHeight);
          if (text && onScan(text.trim(), api) !== false) {
            stopCamera();
            return;
          }
        } catch (error) {
          api.setStatus('Không đọc được khung hình. Có thể nhập mã hoặc chọn ảnh QR.');
          stopCamera();
          return;
        } finally {
          busy = false;
        }
      }
      frame = requestAnimationFrame(time => scanFrame(video, time));
    }

    start.addEventListener('click', async () => {
      if (!navigator.mediaDevices?.getUserMedia || !window.isSecureContext) {
        api.setStatus('Camera cần HTTPS hoặc localhost. Hãy chọn ảnh QR hoặc nhập mã.');
        return;
      }
      if (typeof window.jsQR !== 'function') {
        api.setStatus('Chưa tải được bộ đọc QR. Hãy tải lại trang.');
        return;
      }
      try {
        stream = await navigator.mediaDevices.getUserMedia({audio: false, video: {facingMode: {ideal: 'environment'}}});
        const video = document.createElement('video');
        video.autoplay = true;
        video.playsInline = true;
        video.muted = true;
        video.srcObject = stream;
        preview.replaceChildren(video);
        await video.play();
        start.hidden = true;
        stopButton.hidden = false;
        api.setStatus('Đưa mã QR vào giữa khung hình.');
        frame = requestAnimationFrame(time => scanFrame(video, time));
      } catch (error) {
        stopCamera();
        api.setStatus('Không mở được camera. Hãy cấp quyền hoặc chọn ảnh QR.');
      }
    });
    stopButton.addEventListener('click', () => {
      stopCamera();
      api.setStatus('Đã tắt camera.');
    });

    file.addEventListener('change', async () => {
      const imageFile = file.files?.[0];
      if (!imageFile) return;
      if (!['image/png', 'image/jpeg', 'image/webp'].includes(imageFile.type) || imageFile.size > 12 * 1024 * 1024) {
        api.setStatus('Chọn ảnh QR PNG/JPEG/WebP nhỏ hơn 12 MB.');
        file.value = '';
        return;
      }
      try {
        const bitmap = await createImageBitmap(imageFile);
        const text = decode(bitmap, bitmap.width, bitmap.height);
        bitmap.close();
        if (!text) api.setStatus('Ảnh chưa có mã QR đọc được. Thử ảnh rõ hơn hoặc nhập mã.');
        else onScan(text.trim(), api);
      } catch (error) {
        api.setStatus('Không mở được ảnh. Hãy dùng ảnh PNG/JPEG hoặc nhập mã.');
      } finally {
        file.value = '';
      }
    });
    window.addEventListener('pagehide', stopCamera);
    document.addEventListener('visibilitychange', () => { if (document.hidden) stopCamera(); });
    return api;
  }
};
