// ── Global toast system ──────────────────────────────────────────────────────
(function () {
    // Create container once
    const container = document.createElement('div');
    container.id = 'toast-container';
    document.body.appendChild(container);

    window.showToast = function (message, type) {
        type = type || 'success';
        const icons = { success: 'fa-circle-check', error: 'fa-triangle-exclamation', info: 'fa-circle-info' };

        const toast = document.createElement('div');
        toast.className = 'toast toast-' + type;
        toast.innerHTML = '<i class="fa-solid ' + (icons[type] || icons.success) + '"></i>'
                        + '<span class="toast-msg">' + message + '</span>';
        container.appendChild(toast);

        // Auto-dismiss after 3 seconds (300ms out animation)
        setTimeout(function () {
            toast.classList.add('toast-out');
            setTimeout(function () { toast.remove(); }, 300);
        }, 3000);
    };

    // Auto-fire any server-rendered data-toast elements
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-toast]').forEach(function (el) {
            const msg  = el.getAttribute('data-toast');
            const type = el.getAttribute('data-toast-type') || 'success';
            if (msg) showToast(msg, type);
            el.remove();
        });
    });
})();
