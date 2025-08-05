<button id="theme-toggle" type="button" class="group relative inline-flex items-center justify-center w-10 h-10 rounded-full
           bg-white/80 backdrop-blur-sm border border-gray-200/50 shadow-lg
           hover:bg-white hover:shadow-xl hover:scale-105
           dark:bg-gray-800/80 dark:border-gray-700/50 dark:hover:bg-gray-700
           text-gray-700 dark:text-gray-200
           transition-all duration-300 ease-in-out
           focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:ring-offset-2 focus:ring-offset-transparent"
    aria-label="Cambiar tema">

    <!-- Ícono Sol (modo claro) - visible cuando está en dark mode -->
    <svg class="absolute w-5 h-5 transition-all duration-500 ease-in-out
               opacity-100 rotate-0 scale-100 dark:opacity-0 dark:rotate-180 dark:scale-75" fill="currentColor"
        viewBox="0 0 24 24">
        <path
            d="M12 2.25a.75.75 0 01.75.75v2.25a.75.75 0 01-1.5 0V3a.75.75 0 01.75-.75zM7.5 12a4.5 4.5 0 119 0 4.5 4.5 0 01-9 0zM18.894 6.166a.75.75 0 00-1.06-1.06l-1.591 1.59a.75.75 0 101.06 1.061l1.591-1.59zM21.75 12a.75.75 0 01-.75.75h-2.25a.75.75 0 010-1.5H21a.75.75 0 01.75.75zM17.834 18.894a.75.75 0 001.06-1.06l-1.59-1.591a.75.75 0 10-1.061 1.06l1.59 1.591zM12 18a.75.75 0 01.75.75V21a.75.75 0 01-1.5 0v-2.25A.75.75 0 0112 18zM7.758 17.303a.75.75 0 00-1.061-1.06l-1.591 1.59a.75.75 0 001.06 1.061l1.591-1.59zM6 12a.75.75 0 01-.75.75H3a.75.75 0 010-1.5h2.25A.75.75 0 016 12zM6.697 7.757a.75.75 0 001.06-1.06l-1.59-1.591a.75.75 0 00-1.061 1.06l1.59 1.591z" />
    </svg>

    <!-- Ícono Luna (modo oscuro) - visible cuando está en light mode -->
    <svg class="absolute w-5 h-5 transition-all duration-500 ease-in-out
               opacity-0 rotate-180 scale-75 dark:opacity-100 dark:rotate-0 dark:scale-100" fill="currentColor"
        viewBox="0 0 24 24">
        <path fill-rule="evenodd"
            d="M9.528 1.718a.75.75 0 01.162.819A8.97 8.97 0 009 6a9 9 0 009 9 8.97 8.97 0 003.463-.69.75.75 0 01.981.98 10.503 10.503 0 01-9.694 6.46c-5.799 0-10.5-4.701-10.5-10.5 0-4.368 2.667-8.112 6.46-9.694a.75.75 0 01.818.162z"
            clip-rule="evenodd" />
    </svg>

    <!-- Efecto de brillo al hover -->
    <div class="absolute inset-0 rounded-full bg-gradient-to-r from-blue-400/0 via-purple-400/0 to-pink-400/0 
                group-hover:from-blue-400/20 group-hover:via-purple-400/20 group-hover:to-pink-400/20 
                transition-all duration-300 ease-in-out opacity-0 group-hover:opacity-100"></div>
</button>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const themeToggle = document.getElementById('theme-toggle');

    // Función para aplicar el tema
    function applyTheme(theme) {
        if (theme === 'dark') {
            document.documentElement.classList.add('dark');
            localStorage.setItem('theme', 'dark');
        } else {
            document.documentElement.classList.remove('dark');
            localStorage.setItem('theme', 'light');
        }
    }

    // Detectar tema inicial
    function getInitialTheme() {
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme) {
            return savedTheme;
        }
        return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    }

    // Aplicar tema inicial
    applyTheme(getInitialTheme());

    // Toggle al hacer click
    themeToggle.addEventListener('click', function() {
        const currentTheme = document.documentElement.classList.contains('dark') ? 'dark' : 'light';
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        applyTheme(newTheme);
    });

    // Escuchar cambios en el sistema
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function(e) {
        if (!localStorage.getItem('theme')) {
            applyTheme(e.matches ? 'dark' : 'light');
        }
    });
});
</script>