if (process.platform === 'linux' && (!process.env.PLAYWRIGHT_BROWSERS_PATH || process.env.PLAYWRIGHT_BROWSERS_PATH === '0')) {
    process.env.PLAYWRIGHT_BROWSERS_PATH = '/opt/playwright';
}
const { chromium } = await import('playwright');
import { access, mkdir } from 'node:fs/promises';
import { spawn } from 'node:child_process';
import { createRequire } from 'node:module';
import { fileURLToPath } from 'node:url';
import { dirname, join } from 'node:path';

const require = createRequire(import.meta.url);
const run = (command, args) => new Promise((resolve, reject) => {
    const child = spawn(command, args, { stdio: 'inherit', env: process.env });
    child.on('error', reject);
    child.on('exit', code => code === 0 ? resolve() : reject(new Error(`${command} exited with code ${code}`)));
});

export async function ensureReportBrowser(locked = false) {
    const executable = chromium.executablePath();
    try {
        await access(executable);
        return executable;
    } catch {
        // Install only when the browser required by the installed package is missing.
    }
    const cache = dirname(dirname(dirname(executable)));
    await mkdir(cache, { recursive: true });
    if (!locked && process.platform === 'linux') {
        // Serialize installations across simultaneous PDF requests and npm installs.
        await run('flock', ['-w', '240', `${cache}/.report-install.lock`, process.execPath,
            fileURLToPath(import.meta.url), '--locked']);
    } else {
        await run(process.execPath, [join(dirname(require.resolve('playwright/package.json')), 'cli.js'), 'install', 'chromium']);
    }
    await access(executable);
    return executable;
}

if (process.argv[1] === fileURLToPath(import.meta.url)) {
    // Docker serves reports on Linux; avoid downloading Linux browsers on the host.
    if (!process.argv.includes('--postinstall') || process.platform === 'linux') {
        await ensureReportBrowser(process.argv.includes('--locked'));
    }
}
