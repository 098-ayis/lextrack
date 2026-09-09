import { renderAsync } from 'docx-preview';

const container = document.getElementById('document');
const status = document.getElementById('status');

try {
    const data = Uint8Array.from(atob(JSON.parse(document.getElementById('document-data').textContent)), char => char.charCodeAt(0));
    await renderAsync(data, container, null, { renderAltChunks: false, useBase64URL: true });
    status.hidden = true;
    const fit = () => {
        const page = container.querySelector('section.docx');
        if (!page) return;
        const width = page.offsetWidth;
        const scale = Math.min(1, document.documentElement.clientWidth / width);
        container.style.width = `${width}px`;
        container.style.transform = `scale(${scale})`;
        document.getElementById('preview').style.height = `${container.offsetHeight * scale}px`;
    };
    await document.fonts.ready;
    fit();
    window.addEventListener('resize', fit);
    container.addEventListener('load', fit, true);
    container.addEventListener('click', event => event.preventDefault());
} catch {
    container.replaceChildren();
    status.textContent = 'Preview unavailable. Download the DOCX file to view it in Word.';
}
