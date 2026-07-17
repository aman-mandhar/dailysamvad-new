export function initializeArticleShare() {
    document.querySelectorAll('[data-article-share]').forEach((share) => {
        const button = share.querySelector('[data-copy-link]');
        const status = share.querySelector('[data-copy-status]');

        if (!button || !status) return;

        button.addEventListener('click', async () => {
            try {
                await navigator.clipboard.writeText(button.dataset.url);
                status.textContent = 'Link copied';
            } catch {
                const input = document.createElement('textarea');
                input.value = button.dataset.url;
                input.setAttribute('readonly', '');
                input.style.position = 'fixed';
                input.style.opacity = '0';
                document.body.append(input);
                input.select();
                const copied = document.execCommand('copy');
                input.remove();
                status.textContent = copied ? 'Link copied' : 'Copy the link from your address bar';
            }
        });
    });
}
