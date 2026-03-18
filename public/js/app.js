/**
 * JW Finanças Pessoais — Main App JS
 */

'use strict';

// ================================================================
// DOM Ready
// ================================================================
document.addEventListener('DOMContentLoaded', function () {

    initSidebar();
    initUserDropdown();
    initFlashAutoDismiss();
    initMasks();
    initModals();

});

// ================================================================
// Sidebar toggle (mobile)
// ================================================================
function initSidebar() {
    const toggleBtn  = document.getElementById('sidebarToggle');
    const closeBtn   = document.getElementById('sidebarCloseBtn');
    const sidebar    = document.getElementById('sidebar');
    const overlay    = document.getElementById('sidebarOverlay');

    if (!sidebar) return;

    function openSidebar() {
        sidebar.classList.add('open');
        overlay.classList.add('open');
        document.body.style.overflow = 'hidden';
    }

    function closeSidebar() {
        sidebar.classList.remove('open');
        overlay.classList.remove('open');
        document.body.style.overflow = '';
    }

    if (toggleBtn) toggleBtn.addEventListener('click', openSidebar);
    if (closeBtn)  closeBtn.addEventListener('click', closeSidebar);
    if (overlay)   overlay.addEventListener('click', closeSidebar);

    // Close on Escape
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeSidebar();
    });
}

// ================================================================
// User dropdown
// ================================================================
function initUserDropdown() {
    const btn      = document.getElementById('userMenuBtn');
    const dropdown = document.getElementById('userDropdown');

    if (!btn || !dropdown) return;

    btn.addEventListener('click', function (e) {
        e.stopPropagation();
        dropdown.classList.toggle('open');
    });

    document.addEventListener('click', function () {
        dropdown.classList.remove('open');
    });

    dropdown.addEventListener('click', function (e) {
        e.stopPropagation();
    });
}

// ================================================================
// Flash messages auto-dismiss after 5 seconds
// ================================================================
function initFlashAutoDismiss() {
    const flashes = document.querySelectorAll('.flash');
    flashes.forEach(function (el) {
        setTimeout(function () {
            el.style.transition = 'opacity 0.4s ease';
            el.style.opacity    = '0';
            setTimeout(function () { el.remove(); }, 400);
        }, 5000);
    });
}

// ================================================================
// Input masks (auto-apply based on class)
// ================================================================
function initMasks() {
    // CPF
    document.querySelectorAll('.cpf-input, input[name="cpf"]').forEach(function (inp) {
        inp.addEventListener('input', function () { cpfMask(this); });
    });

    // Phone
    document.querySelectorAll('.phone-input, input[name="telefone"]').forEach(function (inp) {
        inp.addEventListener('input', function () { phoneMask(this); });
    });

    // Currency
    document.querySelectorAll('.currency-input').forEach(function (inp) {
        inp.addEventListener('input', function () { currencyMask(this); });
    });
}

// ================================================================
// Modals
// ================================================================
function initModals() {
    // Close on overlay click
    document.querySelectorAll('.modal-overlay').forEach(function (overlay) {
        overlay.addEventListener('click', function (e) {
            if (e.target === this) {
                this.style.display = 'none';
            }
        });
    });

    // Close on Escape
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal-overlay').forEach(function (m) {
                m.style.display = 'none';
            });
        }
    });
}

/**
 * Open a modal by ID.
 * @param {string} id
 */
function openModal(id) {
    const el = document.getElementById(id);
    if (el) {
        el.style.display = 'flex';
        // Focus first input
        setTimeout(function () {
            const first = el.querySelector('input:not([type="hidden"]), select, textarea');
            if (first) first.focus();
        }, 100);
    }
}

/**
 * Close a modal by ID.
 * @param {string} id
 */
function closeModal(id) {
    const el = document.getElementById(id);
    if (el) el.style.display = 'none';
}

// ================================================================
// Toast notifications
// ================================================================

let toastContainer = null;

function getToastContainer() {
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.className = 'toast-container';
        document.body.appendChild(toastContainer);
    }
    return toastContainer;
}

/**
 * Show a toast notification.
 * @param {string} message
 * @param {'success'|'error'|'warning'|'info'} type
 * @param {number} duration  ms
 */
function showToast(message, type = 'info', duration = 3000) {
    const container = getToastContainer();

    const icons = {
        success: 'fa-circle-check',
        error:   'fa-circle-xmark',
        warning: 'fa-triangle-exclamation',
        info:    'fa-circle-info',
    };

    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerHTML = `<i class="fa-solid ${icons[type] || icons.info}"></i> ${message}`;
    container.appendChild(toast);

    setTimeout(function () {
        toast.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
        toast.style.opacity    = '0';
        toast.style.transform  = 'translateX(20px)';
        setTimeout(function () { toast.remove(); }, 400);
    }, duration);
}

// ================================================================
// AJAX: Validate / Revert movements
// (also defined in movements/index.php - here as fallback)
// ================================================================

/**
 * Generic POST request with CSRF token.
 * @param {string} url
 * @param {string} csrf
 * @param {Object} extra  Additional body params
 * @returns {Promise<Object>}
 */
async function postAction(url, csrf, extra = {}) {
    const params = new URLSearchParams({ _csrf: csrf, ...extra });
    const res = await fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: params.toString(),
    });

    if (!res.ok) {
        throw new Error('HTTP ' + res.status);
    }

    return res.json();
}

// ================================================================
// Dynamic category → subcategory loading via AJAX
// ================================================================

/**
 * Load subcategories for a given category ID.
 * @param {string|number} categoriaId
 * @param {HTMLSelectElement} targetSelect
 */
async function loadSubcategories(categoriaId, targetSelect) {
    if (!categoriaId) {
        targetSelect.innerHTML = '<option value="">Selecione a categoria primeiro...</option>';
        return;
    }

    const basePath = window.BASE_PATH || '/financas/public';
    try {
        const res  = await fetch(`${basePath}/api/subcategorias/${categoriaId}`);
        const subs = await res.json();

        targetSelect.innerHTML = '<option value="">Selecione...</option>';
        subs.forEach(function (sub) {
            const opt       = document.createElement('option');
            opt.value       = sub.id;
            opt.textContent = sub.nome;
            targetSelect.appendChild(opt);
        });
    } catch (err) {
        console.warn('Erro ao carregar subcategorias:', err);
    }
}

// ================================================================
// Button loading state
// ================================================================

/**
 * Set a button into loading state.
 * @param {HTMLButtonElement} btn
 * @param {boolean} loading
 */
function setButtonLoading(btn, loading) {
    const textEl    = btn.querySelector('.btn-text');
    const spinnerEl = btn.querySelector('.btn-spinner');

    if (loading) {
        btn.disabled = true;
        if (textEl)    textEl.classList.add('hidden');
        if (spinnerEl) spinnerEl.classList.remove('hidden');
    } else {
        btn.disabled = false;
        if (textEl)    textEl.classList.remove('hidden');
        if (spinnerEl) spinnerEl.classList.add('hidden');
    }
}

// ================================================================
// Form: prevent double-submit
// ================================================================
document.querySelectorAll('form[data-once]').forEach(function (form) {
    form.addEventListener('submit', function () {
        const btn = this.querySelector('[type="submit"]');
        if (btn) btn.disabled = true;
    });
});
