import { chromium } from 'playwright';
import { readFile } from 'node:fs/promises';
const [input, directory, mode] = process.argv.slice(2);
const browser = await chromium.launch({ headless: true });
try {
    const page = await browser.newPage({ viewport: { width: 1100, height: 1200 }, deviceScaleFactor: 2 });
    await page.route('**/*', route => route.abort());
    await page.setContent(await readFile(input, 'utf8'), { waitUntil: 'load' });
    await page.emulateMedia({ media: 'print' });
    await page.evaluate(async () => {
        await document.fonts.ready;
        await Promise.all([...document.images].map(image => image.decode()));
    });
    if (mode === 'pdf') {
        await page.pdf({ path: `${directory}/report.pdf`, preferCSSPageSize: true, printBackground: true, displayHeaderFooter: false });
    } else {
        const sheets = page.locator('.sheet');
        for (let i = 0; i < await sheets.count(); i++) {
            await sheets.nth(i).screenshot({ path: `${directory}/page-${i + 1}.png` });
        }
    }
} finally {
    await browser.close();
}
