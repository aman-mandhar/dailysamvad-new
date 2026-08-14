export function initializeEpaper() {
    const page = document.querySelector('[data-epaper-page]');
    if (!page) return;

    const sheet = page.querySelector('[data-epaper-sheet]');
    const status = page.querySelector('[data-epaper-status]');
    const captureButtons = [...page.querySelectorAll('[data-epaper-download], [data-epaper-share-jpeg]')];
    const filename = `${sheet.dataset.epaperFilename || 'daily-samvad'}-epaper.jpg`;

    const setBusy = (busy, message = '') => {
        captureButtons.forEach((button) => { button.disabled = busy; });
        status.textContent = message;
    };

    const capture = async () => {
        setBusy(true, 'Preparing JPEG…');
        try {
            const { default: html2canvas } = await import('html2canvas');
            const canvas = await html2canvas(sheet, {
                backgroundColor: '#ffffff',
                logging: false,
                scale: Math.min(2, window.devicePixelRatio || 1),
                useCORS: true,
            });

            return await new Promise((resolve, reject) => canvas.toBlob(
                (blob) => blob ? resolve(blob) : reject(new Error('JPEG creation failed.')),
                'image/jpeg',
                0.92,
            ));
        } finally {
            setBusy(false);
        }
    };

    const download = (blob) => {
        const url = URL.createObjectURL(blob);
        const link = Object.assign(document.createElement('a'), { href: url, download: filename });
        link.click();
        setTimeout(() => URL.revokeObjectURL(url), 1000);
    };

    page.querySelector('[data-epaper-print]')?.addEventListener('click', () => window.print());
    page.querySelector('[data-epaper-download]')?.addEventListener('click', async () => {
        try {
            download(await capture());
            status.textContent = 'JPEG downloaded.';
        } catch {
            status.textContent = 'Could not create the JPEG. Please use Print instead.';
        }
    });
    page.querySelector('[data-epaper-share-jpeg]')?.addEventListener('click', async () => {
        try {
            const blob = await capture();
            const file = new File([blob], filename, { type: 'image/jpeg' });
            if (navigator.share && (!navigator.canShare || navigator.canShare({ files: [file] }))) {
                await navigator.share({ title: document.title, files: [file] });
                status.textContent = 'JPEG shared.';
            } else {
                download(blob);
                status.textContent = 'Sharing is unavailable; the JPEG was downloaded.';
            }
        } catch (error) {
            if (error?.name !== 'AbortError') status.textContent = 'Could not share the JPEG.';
        }
    });
    page.querySelector('[data-epaper-share-page]')?.addEventListener('click', async () => {
        if (navigator.share) {
            try {
                await navigator.share({ title: document.title, url: window.location.href });
                return;
            } catch (error) {
                if (error?.name === 'AbortError') return;
            }
        }
        await navigator.clipboard?.writeText(window.location.href);
        status.textContent = 'ePaper link copied.';
    });
}
