<script>
    (function () {
        const theme = localStorage.getItem('systemTheme');

        if (theme === 'dark-navy') {
            document.documentElement.classList.add('dark-navy');
        }

        if (theme === 'light') {
            document.documentElement.classList.remove('dark-navy');
        }

        const sidebarOpen = localStorage.getItem('sidebarOpen');

        document.documentElement.classList.add('sidebar-preload');

        if (sidebarOpen === 'false') {
            document.documentElement.classList.add('sidebar-preload-collapsed');
        } else {
            document.documentElement.classList.add('sidebar-preload-expanded');
        }
    })();
</script>
