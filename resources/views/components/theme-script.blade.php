<script>
    (() => {
        try {
            const storedTheme = localStorage.getItem('theme');
            const useDarkTheme = storedTheme === 'dark';

            document.documentElement.classList.toggle('dark', useDarkTheme);
            document.documentElement.style.colorScheme = useDarkTheme ? 'dark' : 'light';
        } catch (error) {
            document.documentElement.classList.remove('dark');
            document.documentElement.style.colorScheme = 'light';
        }
    })();
</script>
