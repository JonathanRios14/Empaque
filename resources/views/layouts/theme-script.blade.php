<script>
    (function () {
        const theme = localStorage.getItem('systemTheme');

        if (theme === 'dark-navy') {
            document.documentElement.classList.add('dark-navy');
        }

        if (theme === 'light') {
            document.documentElement.classList.remove('dark-navy');
        }
    })();
</script>