<script data-theme-bootstrap>
    (() => {
        const storageKey = 'mawa-center-theme';
        let theme = null;

        try {
            const storedTheme = window.localStorage.getItem(storageKey);
            theme = storedTheme === 'light' || storedTheme === 'dark' ? storedTheme : null;
        } catch {
            // Fall back to the operating-system preference when storage is unavailable.
        }

        if (!theme) {
            theme = window.matchMedia?.('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        }

        document.documentElement.dataset.theme = theme;
    })();
</script>
