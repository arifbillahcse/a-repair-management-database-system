/**
 * components/qr.js — port of src/QRCode.php
 *
 * Renders the tracking code as an SVG QR entirely in the browser via the
 * `qrcode-generator` library loaded from a CDN. Deliberately NOT an image
 * service: nothing about a repair job leaves the visitor's browser.
 */
'use strict';

const QR = {

    render(mountId, text, size = 150) {
        const el = document.getElementById(mountId);
        if (!el) return;

        if (!text) { el.innerHTML = '<p class="text-muted small">No code assigned.</p>'; return; }

        if (typeof qrcode === 'undefined') {
            // Library blocked or offline — the code itself is still printed below.
            el.innerHTML = `<div class="qr-fallback">${Icon.box('')}<span>QR unavailable offline</span></div>`;
            return;
        }

        try {
            const q = qrcode(0, 'M');             // auto type, medium error correction
            q.addData(text);
            q.make();

            const count = q.getModuleCount();
            const cell  = size / count;
            let path = '';
            for (let r = 0; r < count; r++) {
                for (let c = 0; c < count; c++) {
                    if (q.isDark(r, c)) {
                        path += `M${(c * cell).toFixed(2)} ${(r * cell).toFixed(2)}h${cell.toFixed(2)}v${cell.toFixed(2)}h-${cell.toFixed(2)}z`;
                    }
                }
            }

            el.innerHTML = `
                <svg viewBox="0 0 ${size} ${size}" width="${size}" height="${size}"
                     xmlns="http://www.w3.org/2000/svg" shape-rendering="crispEdges">
                    <rect width="${size}" height="${size}" fill="#ffffff"/>
                    <path d="${path}" fill="#000000"/>
                </svg>`;
        } catch (err) {
            console.error('[QR] render failed', err);
            el.innerHTML = `<div class="qr-fallback">${Icon.box('')}<span>Could not draw the code</span></div>`;
        }
    },
};
