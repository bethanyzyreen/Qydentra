/* ============================================================
   QYDENTRA — THEME TOGGLE
   Persists choice in localStorage. Works on every page.
   Supports BOTH: body.light-mode class + data-theme attribute.
   ============================================================ */

(function () {
    // Apply saved theme immediately (before paint) to avoid flash
    var saved = localStorage.getItem('qydentra_theme');
    if (saved === 'light') {
        document.documentElement.classList.add('light-mode-pre');
        document.documentElement.setAttribute('data-theme', 'light');
    } else {
        document.documentElement.setAttribute('data-theme', 'dark');
    }
})();

document.addEventListener('DOMContentLoaded', function () {
    var saved = localStorage.getItem('qydentra_theme') || 'dark';

    // Move class from <html> to <body> once DOM is ready
    document.documentElement.classList.remove('light-mode-pre');

    applyTheme(saved);

    // Wire up every toggle button on this page
    document.querySelectorAll('.theme-toggle-btn').forEach(function (btn) {
        updateIcon(btn, saved);

        btn.addEventListener('click', function () {
            var current = localStorage.getItem('qydentra_theme') || 'dark';
            var next = current === 'light' ? 'dark' : 'light';
            localStorage.setItem('qydentra_theme', next);
            applyTheme(next);
            document.querySelectorAll('.theme-toggle-btn').forEach(function (b) {
                updateIcon(b, next);
            });
        });
    });

    function applyTheme(theme) {
        if (theme === 'light') {
            document.body.classList.add('light-mode');
            document.body.setAttribute('data-theme', 'light');
            document.documentElement.setAttribute('data-theme', 'light');
        } else {
            document.body.classList.remove('light-mode');
            document.body.setAttribute('data-theme', 'dark');
            document.documentElement.setAttribute('data-theme', 'dark');
        }
    }

    function updateIcon(btn, theme) {
        btn.innerHTML = theme === 'light'
            ? '<i class="fa-solid fa-moon"></i>'
            : '<i class="fa-solid fa-sun"></i>';
        btn.setAttribute('title', theme === 'light' ? 'Switch to Dark Mode' : 'Switch to Light Mode');
    }
});
