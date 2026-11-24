(() => {
    const overlay = document.createElement('div');
    overlay.className = 'page-loading-skeleton';
    overlay.setAttribute('aria-hidden', 'true');
    overlay.innerHTML = `
        <aside class="loading-sidebar">
            <div class="loading-brand"><i></i><span></span></div>
            <div class="loading-nav"></div><div class="loading-nav"></div><div class="loading-nav"></div>
            <div class="loading-nav"></div><div class="loading-nav"></div>
            <div class="loading-help"></div>
        </aside>
        <main class="loading-main">
            <div class="loading-topbar"><i></i><span></span><b></b></div>
            <div class="loading-title"></div><div class="loading-subtitle"></div>
            <div class="loading-stats"><i></i><i></i><i></i><i></i></div>
            <div class="loading-panel"><i></i><i></i><i></i><i></i><i></i></div>
        </main>`;

    document.body.append(overlay);

    const showSkeleton = () => {
        document.body.classList.add('is-page-loading');
    };

    document.addEventListener('click', (event) => {
        if (event.defaultPrevented || !(event.target instanceof Element)) {
            return;
        }

        const link = event.target.closest('a[href]');

        if (!link || link.target === '_blank' || link.hasAttribute('download') || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
            return;
        }

        const destination = new URL(link.href, window.location.href);

        if (destination.origin !== window.location.origin || (destination.pathname === window.location.pathname && destination.search === window.location.search)) {
            return;
        }

        showSkeleton();
    });

    document.addEventListener('submit', (event) => {
        if (!event.defaultPrevented && event.target instanceof HTMLFormElement && !event.target.hasAttribute('data-no-loading') && (!event.target.target || event.target.target === '_self')) {
            showSkeleton();
        }
    });

    window.addEventListener('pageshow', () => {
        document.body.classList.remove('is-page-loading');
    });
})();
