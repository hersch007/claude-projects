import * as pdfjsLib from '/vendor/pdfjs/pdf.min.mjs';

pdfjsLib.GlobalWorkerOptions.workerSrc = '/vendor/pdfjs/pdf.worker.min.mjs';

/** Default sizes are fractions of page width / height (US Letter proportions). */
export const FIELD_META = {
  signature: { label: 'Signature', w: 0.26, h: 0.055 },
  initials: { label: 'Initials', w: 0.09, h: 0.045 },
  date: { label: 'Date signed', w: 0.14, h: 0.03 },
  name: { label: 'Full name', w: 0.2, h: 0.03 },
  text: { label: 'Text', w: 0.2, h: 0.03 },
  checkbox: { label: 'Checkbox', w: 0.025, h: 0.019 },
};

export const SIGNER_COLORS = ['#2563eb', '#16a34a', '#d97706', '#9333ea', '#dc2626', '#0891b2', '#be185d', '#4b5563'];
export const signerColor = (i) => SIGNER_COLORS[i % SIGNER_COLORS.length];

/**
 * Render every page of a PDF into `container`. Each page gets a canvas plus an
 * absolutely-positioned overlay div for placing field elements.
 * @returns {Promise<{pdf: any, pages: Array<{n:number, el:HTMLElement, overlay:HTMLElement, width:number, height:number}>}>}
 */
export async function renderPdf(url, container, { maxWidth = 820 } = {}) {
  const pdf = await pdfjsLib.getDocument({ url }).promise;
  container.innerHTML = '';
  const pages = [];
  const available = Math.max(320, Math.min(maxWidth, (container.clientWidth || maxWidth) - 24));
  const dpr = window.devicePixelRatio || 1;

  for (let n = 1; n <= pdf.numPages; n++) {
    const page = await pdf.getPage(n);
    const base = page.getViewport({ scale: 1 });
    const viewport = page.getViewport({ scale: available / base.width });

    const wrap = document.createElement('div');
    wrap.className = 'page';
    wrap.dataset.page = String(n);
    wrap.style.width = `${viewport.width}px`;
    wrap.style.height = `${viewport.height}px`;

    const canvas = document.createElement('canvas');
    canvas.width = Math.floor(viewport.width * dpr);
    canvas.height = Math.floor(viewport.height * dpr);
    canvas.style.width = `${viewport.width}px`;
    canvas.style.height = `${viewport.height}px`;
    const ctx = canvas.getContext('2d');
    await page.render({ canvasContext: ctx, viewport, transform: dpr !== 1 ? [dpr, 0, 0, dpr, 0, 0] : null }).promise;

    const overlay = document.createElement('div');
    overlay.className = 'overlay';
    const num = document.createElement('div');
    num.className = 'pagenum';
    num.textContent = `Page ${n} of ${pdf.numPages}`;
    wrap.append(canvas, overlay, num);
    container.append(wrap);
    pages.push({ n, el: wrap, overlay, width: viewport.width, height: viewport.height });
  }
  return { pdf, pages };
}

/** Apply fractional geometry to a field element. */
export function placeEl(el, f) {
  el.style.left = `${f.x * 100}%`;
  el.style.top = `${f.y * 100}%`;
  el.style.width = `${f.w * 100}%`;
  el.style.height = `${f.h * 100}%`;
}

export const clamp = (v, min, max) => Math.min(max, Math.max(min, v));
