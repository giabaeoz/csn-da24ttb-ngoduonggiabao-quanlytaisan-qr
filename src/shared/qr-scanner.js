'use strict';

window.QRScanner = {
  bind(root, onScan, options = {}) {
    const start = root.querySelector('[data-qr-start]');
    const stopButton = root.querySelector('[data-qr-stop]');
    const file = root.querySelector('[data-qr-file]');
    const preview = root.querySelector('[data-qr-preview]');
    const message = root.querySelector('[data-qr-status]');
    if (!start || !stopButton || !file || !preview || !message) return;

    // continuous: giữ camera để quét nhiều mã; bỏ qua cùng một mã trong khoảng cooldown.
    const continuous = !!options.continuous;
    const cooldown = options.cooldown || 2500;
    let lastText = '';
    let lastAt = 0;
    let stream = null;
    let frame = 0;
    let busy = false;
    let lastFrame = 0;
    const canvas = document.createElement('canvas');
    const context = canvas.getContext('2d', {willReadFrequently: true});
    const originalPreview = [...preview.childNodes].map(node => node.cloneNode(true));
    const api = {
      setStatus(text, tone) {
        message.textContent = text;
        message.dataset.tone = tone || '';
      },
      stop() { stopCamera(); }
    };

    function decode(source, width, height) {
      if (!context || !width || !height || typeof window.jsQR !== 'function') return null;
      const maxDim = Math.max(width, height);
      const scales = [];
      if (maxDim <= 1200) scales.push(1);
      if (maxDim > 1000) scales.push(1000 / maxDim);
      if (maxDim > 800) scales.push(800 / maxDim);
      if (maxDim > 600) scales.push(600 / maxDim);
      if (!scales.length) scales.push(1);

      for (const scale of scales) {
        canvas.width = Math.max(1, Math.round(width * scale));
        canvas.height = Math.max(1, Math.round(height * scale));
        context.drawImage(source, 0, 0, canvas.width, canvas.height);
        const pixels = context.getImageData(0, 0, canvas.width, canvas.height);
        const res = window.jsQR(pixels.data, pixels.width, pixels.height, {inversionAttempts: 'attemptBoth'});
        if (res?.data) return res.data;
      }
      return null;
    }

    function stopCamera() {
      if (frame) cancelAnimationFrame(frame);
      frame = 0;
      if (stream) stream.getTracks().forEach(track => track.stop());
      stream = null;
      preview.replaceChildren(...originalPreview.map(node => node.cloneNode(true)));
      start.hidden = false;
      stopButton.hidden = true;
    }

    async function scanFrame(video, time) {
      if (!stream) return;
      if (!busy && video.readyState >= 2 && time - lastFrame >= 140) {
        busy = true;
        lastFrame = time;
        try {
          const text = decode(video, video.videoWidth, video.videoHeight)?.trim();
          if (text && continuous) {
            if (text !== lastText || time - lastAt > cooldown) {
              lastText = text;
              lastAt = time;
              onScan(text, api);
            }
          } else if (text && onScan(text, api) !== false) {
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
      const hasMedia = !!(navigator.mediaDevices && navigator.mediaDevices.getUserMedia);
      if (!hasMedia && !window.isSecureContext) {
        api.setStatus('Camera cần HTTPS hoặc localhost. Hãy chọn ảnh QR hoặc nhập mã bên dưới.');
        return;
      }
      if (typeof window.jsQR !== 'function') {
        api.setStatus('Chưa tải được bộ đọc QR. Hãy tải lại trang.');
        return;
      }
      try {
        stream = await navigator.mediaDevices.getUserMedia({
          audio: false,
          video: {facingMode: {ideal: 'environment'}, width: {ideal: 1280}, height: {ideal: 720}}
        });
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
        const msg = error.name === 'NotAllowedError' ? 'Chưa cấp quyền truy cập camera.' : 'Không mở được camera. Hãy chọn ảnh QR hoặc nhập mã bên dưới.';
        api.setStatus(msg);
      }
    });
    stopButton.addEventListener('click', () => {
      stopCamera();
      api.setStatus('Đã tắt camera.');
    });

    function loadImageElement(fileObj) {
      return new Promise((resolve, reject) => {
        const url = URL.createObjectURL(fileObj);
        const img = new Image();
        img.onload = () => { resolve(img); };
        img.onerror = () => { URL.revokeObjectURL(url); reject(new Error('Lỗi đọc ảnh')); };
        img.src = url;
      });
    }

    file.addEventListener('change', async () => {
      const imageFile = file.files?.[0];
      if (!imageFile) return;
      if (imageFile.size > 20 * 1024 * 1024) {
        api.setStatus('Chọn ảnh nhỏ hơn 20 MB.');
        file.value = '';
        return;
      }
      try {
        let text = null;
        if (window.createImageBitmap) {
          try {
            const bitmap = await createImageBitmap(imageFile);
            text = decode(bitmap, bitmap.width, bitmap.height);
            bitmap.close();
          } catch (_) {}
        }
        if (!text) {
          const img = await loadImageElement(imageFile);
          text = decode(img, img.naturalWidth || img.width, img.naturalHeight || img.height);
          URL.revokeObjectURL(img.src);
        }
        if (!text) {
          api.setStatus('Ảnh chưa có mã QR đọc được. Thử ảnh rõ hơn hoặc nhập mã bên dưới.');
        } else {
          onScan(text.trim(), api);
        }
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
