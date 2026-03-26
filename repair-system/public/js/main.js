/**
 * main.js — Core UI interactions
 * No external dependencies required.
 */
(function () {
    'use strict';

    // ── DOM ready helper ──────────────────────────────────────────────────────
    function ready(fn) {
        if (document.readyState !== 'loading') { fn(); }
        else { document.addEventListener('DOMContentLoaded', fn); }
    }

    ready(function () {
        initSidebar();
        initUserMenu();
        initFlashMessages();
        initDeleteConfirm();
        initSubNavs();
        initStatusBadgeColors();
    });

    // ── Sidebar toggle (mobile) ───────────────────────────────────────────────
    function initSidebar() {
        var toggle  = document.getElementById('sidebarToggle');
        var sidebar = document.getElementById('sidebar');
        var overlay = document.getElementById('sidebarOverlay');
        var closeBtn = document.getElementById('sidebarClose');

        if (!toggle || !sidebar) return;

        function openSidebar() {
            sidebar.classList.add('open');
            overlay && overlay.classList.add('visible');
            toggle.setAttribute('aria-expanded', 'true');
        }

        function closeSidebar() {
            sidebar.classList.remove('open');
            overlay && overlay.classList.remove('visible');
            toggle.setAttribute('aria-expanded', 'false');
        }

        toggle.addEventListener('click', function () {
            sidebar.classList.contains('open') ? closeSidebar() : openSidebar();
        });

        closeBtn && closeBtn.addEventListener('click', closeSidebar);
        overlay  && overlay.addEventListener('click', closeSidebar);

        // Close on Escape
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') closeSidebar();
        });
    }

    // ── User dropdown menu ────────────────────────────────────────────────────
    function initUserMenu() {
        var btn      = document.getElementById('userMenuBtn');
        var dropdown = document.getElementById('userDropdown');
        if (!btn || !dropdown) return;

        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            var isOpen = !dropdown.hidden;
            dropdown.hidden = isOpen;
            btn.setAttribute('aria-expanded', String(!isOpen));
        });

        document.addEventListener('click', function () {
            if (!dropdown.hidden) {
                dropdown.hidden = true;
                btn.setAttribute('aria-expanded', 'false');
            }
        });

        dropdown.addEventListener('click', function (e) { e.stopPropagation(); });
    }

    // ── Flash message auto-dismiss ────────────────────────────────────────────
    function initFlashMessages() {
        var container = document.getElementById('flashContainer');
        if (!container) return;

        // Close button
        container.addEventListener('click', function (e) {
            if (e.target.classList.contains('flash-close')) {
                e.target.closest('.flash').remove();
            }
        });

        // Auto-dismiss after 5 seconds
        setTimeout(function () {
            var flashes = container.querySelectorAll('.flash');
            flashes.forEach(function (f) {
                f.style.transition = 'opacity .4s ease';
                f.style.opacity    = '0';
                setTimeout(function () { f.remove(); }, 400);
            });
        }, 5000);
    }

    // ── Delete confirmation dialogs ───────────────────────────────────────────
    function initDeleteConfirm() {
        document.addEventListener('submit', function (e) {
            var form = e.target;
            if (!form.dataset.confirm) return;

            var msg = form.dataset.confirm || 'Are you sure you want to delete this item? This cannot be undone.';
            if (!confirm(msg)) {
                e.preventDefault();
            }
        });

        // Also handle [data-confirm] on buttons/links
        document.addEventListener('click', function (e) {
            var el = e.target.closest('[data-confirm]');
            if (!el || el.tagName === 'FORM') return;
            if (!confirm(el.dataset.confirm)) {
                e.preventDefault();
            }
        });
    }

    // ── Collapsible sub-navs in sidebar ──────────────────────────────────────
    function initSubNavs() {
        var toggles = document.querySelectorAll('.nav-group-toggle');
        toggles.forEach(function (btn) {
            btn.addEventListener('click', function () {
                var sub      = btn.closest('.nav-item').querySelector('.sub-nav');
                var isOpen   = sub && sub.classList.contains('open');
                var expanded = !isOpen;

                // Close all subs first
                document.querySelectorAll('.sub-nav.open').forEach(function (el) {
                    el.classList.remove('open');
                });
                document.querySelectorAll('.nav-group-toggle[aria-expanded="true"]').forEach(function (b) {
                    b.setAttribute('aria-expanded', 'false');
                });

                // Open clicked
                if (!isOpen && sub) {
                    sub.classList.add('open');
                    btn.setAttribute('aria-expanded', 'true');
                }
            });
        });
    }

    // ── Color overdue days in repair tables ───────────────────────────────────
    function initStatusBadgeColors() {
        document.querySelectorAll('[data-days-in-lab]').forEach(function (el) {
            var days = parseInt(el.dataset.daysInLab, 10);
            if (days > 14) {
                el.style.color = 'var(--error)';
                el.style.fontWeight = '600';
            } else if (days > 7) {
                el.style.color = 'var(--warning)';
            }
        });
    }

    // ── Expose global helpers ─────────────────────────────────────────────────

    /**
     * Show a temporary toast notification (used from AJAX responses, etc.)
     * @param {string} message
     * @param {string} type  'success'|'error'|'warning'|'info'
     */
    window.showToast = function (message, type) {
        type = type || 'info';
        var container = document.getElementById('flashContainer');
        if (!container) {
            container = document.createElement('div');
            container.id        = 'flashContainer';
            container.className = 'flash-container';
            document.body.appendChild(container);
        }

        var toast = document.createElement('div');
        toast.className = 'flash flash-' + type;
        toast.innerHTML =
            '<span>' + String(message).replace(/</g, '&lt;') + '</span>' +
            '<button class="flash-close" aria-label="Dismiss">&times;</button>';

        container.appendChild(toast);

        setTimeout(function () {
            toast.style.transition = 'opacity .4s';
            toast.style.opacity    = '0';
            setTimeout(function () { toast.remove(); }, 400);
        }, 5000);
    };

    /**
     * POST a form via fetch (AJAX) and handle JSON response.
     * @param {HTMLFormElement} form
     * @param {function} onSuccess  Called with parsed JSON
     */
    window.ajaxForm = function (form, onSuccess) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var btn = form.querySelector('[type="submit"]');
            if (btn) { btn.disabled = true; btn.textContent = 'Saving…'; }

            fetch(form.action, {
                method:  form.method || 'POST',
                body:    new FormData(form),
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (btn) { btn.disabled = false; btn.textContent = 'Save'; }
                onSuccess && onSuccess(data);
            })
            .catch(function (err) {
                if (btn) { btn.disabled = false; btn.textContent = 'Save'; }
                window.showToast('An error occurred. Please try again.', 'error');
                console.error(err);
            });
        });
    };

})();
