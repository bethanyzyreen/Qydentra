/* ============================================================
   QYDENTRA — THEME TOGGLE
   Persists choice in localStorage. Works on every page.
   ============================================================ */

(function () {
    // Apply saved theme immediately (before paint) to avoid flash
    var saved = localStorage.getItem('qydentra_theme');
    if (saved === 'light') {
        document.documentElement.classList.add('light-mode-pre');
    }
})();

document.addEventListener('DOMContentLoaded', function () {
    var saved = localStorage.getItem('qydentra_theme') || 'dark';

    // Move class from <html> to <body> once DOM is ready
    document.documentElement.classList.remove('light-mode-pre');
    if (saved === 'light') {
        document.body.classList.add('light-mode');
    }

    // Wire up every toggle button on this page
    document.querySelectorAll('.theme-toggle-btn').forEach(function (btn) {
        updateIcon(btn, saved);

        btn.addEventListener('click', function () {
            var isLight = document.body.classList.toggle('light-mode');
            var next    = isLight ? 'light' : 'dark';
            localStorage.setItem('qydentra_theme', next);
            document.querySelectorAll('.theme-toggle-btn').forEach(function (b) {
                updateIcon(b, next);
            });
        });
    });

    function updateIcon(btn, theme) {
        btn.innerHTML = theme === 'light'
            ? '<i class="fa-solid fa-moon"></i>'
            : '<i class="fa-solid fa-sun"></i>';
        btn.setAttribute('title', theme === 'light' ? 'Switch to Dark Mode' : 'Switch to Light Mode');
    }
});
