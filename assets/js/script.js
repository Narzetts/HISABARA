// Modern confirm dialog
function showConfirm(message, options = {}) {
    return new Promise((resolve) => {
        const overlay = document.createElement('div');
        overlay.className = 'confirm-overlay';

        const iconType = options.danger ? 'danger' : 'warning';
        const iconMap = { warning: 'exclamation-triangle-fill', danger: 'x-circle-fill', info: 'info-circle-fill' };
        const confirmText = options.confirmText || (options.danger ? 'Ya, Hapus' : 'Ya, Lanjutkan');
        const btnClass = options.danger ? 'btn-danger' : 'btn-accent';

        overlay.innerHTML = `
            <div class="confirm-dialog">
                <div class="confirm-icon ${iconType}">
                    <i class="bi bi-${iconMap[iconType]}"></i>
                </div>
                <div class="confirm-message">${message}</div>
                <div class="confirm-actions">
                    <button class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button class="btn ${btnClass}" data-confirm-btn>${confirmText}</button>
                </div>
            </div>
        `;

        document.body.appendChild(overlay);

        function close(result) {
            overlay.classList.add('closing');
            setTimeout(() => { overlay.remove(); resolve(result); }, 200);
        }

        overlay.querySelector('[data-confirm-btn]').addEventListener('click', () => close(true));
        overlay.querySelector('[data-dismiss]').addEventListener('click', () => close(false));

        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) close(false);
        });

        const keyHandler = (e) => {
            if (e.key === 'Escape') { close(false); document.removeEventListener('keydown', keyHandler); }
            if (e.key === 'Enter') { close(true); document.removeEventListener('keydown', keyHandler); }
        };
        document.addEventListener('keydown', keyHandler);
    });
}

// Sidebar Toggle
document.addEventListener('DOMContentLoaded', function() {
    const toggle = document.getElementById('sidebarToggle');
    if (toggle) {
        toggle.addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('show');
        });
    }

    // Close sidebar on outside click (mobile)
    document.addEventListener('click', function(e) {
        const sidebar = document.querySelector('.sidebar');
        const toggleBtn = document.getElementById('sidebarToggle');
        if (window.innerWidth <= 768 && sidebar && sidebar.classList.contains('show')) {
            if (!sidebar.contains(e.target) && toggleBtn && !toggleBtn.contains(e.target)) {
                sidebar.classList.remove('show');
            }
        }
    });

    // Theme Toggle
    const themeToggle = document.getElementById('themeToggle');
    if (themeToggle) {
        themeToggle.addEventListener('click', function() {
            const html = document.documentElement;
            const current = html.getAttribute('data-bs-theme');
            const next = current === 'dark' ? 'light' : 'dark';
            html.setAttribute('data-bs-theme', next);
            document.cookie = 'theme=' + next + ';path=/';
            const icon = themeToggle.querySelector('i');
            icon.className = next === 'dark' ? 'bi bi-sun-fill' : 'bi bi-moon-fill';
        });
    }

    // Modern confirm dialogs
    async function handleConfirm(e) {
        const msg = this.getAttribute('data-confirm') || this.form?.getAttribute('data-confirm');
        if (!msg) return;
        e.preventDefault();
        const confirmed = await showConfirm(msg, { danger: this.hasAttribute('data-danger') });
        if (confirmed) {
            if (this.tagName === 'FORM') {
                this.removeAttribute('data-confirm');
                this.submit();
            } else if (this.tagName === 'A') {
                window.location.href = this.href;
            } else if (this.form) {
                const form = this.form;
                form.removeAttribute('data-confirm');
                form.requestSubmit();
            }
        }
    }

    document.querySelectorAll('[data-confirm]').forEach(function(el) {
        el.addEventListener('click', handleConfirm);
    });

    document.querySelectorAll('form[data-confirm]').forEach(function(form) {
        form.addEventListener('submit', handleConfirm);
    });

    // Auto-hide alerts
    document.querySelectorAll('.alert-dismissible').forEach(function(el) {
        setTimeout(function() {
            const bsAlert = new bootstrap.Alert(el);
            bsAlert.close();
        }, 5000);
    });

    // Toast notification
    const toastEl = document.getElementById('toastNotification');
    if (toastEl) {
        const toast = new bootstrap.Toast(toastEl, { delay: 4000 });
        toast.show();
    }
});

// Rupiah input formatting
function formatRupiahInput(input) {
    let val = input.value.replace(/[^\d]/g, '');
    if (val) {
        input.value = new Intl.NumberFormat('id-ID').format(val);
    }
}

document.querySelectorAll('input[placeholder="0"]').forEach(function(el) {
    el.addEventListener('keyup', function() {
        formatRupiahInput(this);
    });
    el.addEventListener('blur', function() {
        if (this.value) {
            formatRupiahInput(this);
        }
    });
});

// Toggle Password
function togglePassword(id) {
    const el = document.getElementById(id);
    if (el) {
        el.type = el.type === 'password' ? 'text' : 'password';
    }
}
