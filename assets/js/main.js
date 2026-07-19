'use strict';

// ============================================================
// Theme (Dark / Light)
// ============================================================
(function initTheme() {
    const saved = localStorage.getItem('theme') || 'light';
    document.documentElement.setAttribute('data-theme', saved);
    updateThemeIcon(saved);
})();

function updateThemeIcon(theme) {
    const icon = document.getElementById('themeIcon');
    if (icon) icon.className = theme === 'dark' ? 'ri-sun-line' : 'ri-moon-line';
}

document.addEventListener('DOMContentLoaded', () => {
    // Theme toggle
    document.getElementById('themeToggle')?.addEventListener('click', () => {
        const current = document.documentElement.getAttribute('data-theme');
        const next    = current === 'dark' ? 'light' : 'dark';
        document.documentElement.setAttribute('data-theme', next);
        localStorage.setItem('theme', next);
        updateThemeIcon(next);
    });

    initNavbar();
    initDropdowns();
    initToasts();
    initCounters();
    initNotificationDropdown();
    initAutoFlash();
});

// ============================================================
// Sticky Navbar shadow
// ============================================================
function initNavbar() {
    const nav = document.getElementById('mainNavbar');
    if (!nav) return;
    window.addEventListener('scroll', () => {
        nav.classList.toggle('scrolled', window.scrollY > 10);
    }, { passive: true });

    // Hamburger
    const hamburger = document.getElementById('hamburger');
    const mobileNav = document.getElementById('mobileNav');
    const mobileOverlay = document.getElementById('mobileNavOverlay');
    const mobileClose = document.getElementById('mobileNavClose');

    function openMobileNav() {
        mobileNav?.classList.add('open');
        mobileOverlay?.classList.add('show');
        document.body.style.overflow = 'hidden';
    }
    function closeMobileNav() {
        mobileNav?.classList.remove('open');
        mobileOverlay?.classList.remove('show');
        document.body.style.overflow = '';
    }

    hamburger?.addEventListener('click', openMobileNav);
    mobileClose?.addEventListener('click', closeMobileNav);
    mobileOverlay?.addEventListener('click', closeMobileNav);
}

// ============================================================
// Dropdowns
// ============================================================
function initDropdowns() {
    document.addEventListener('click', e => {
        const trigger = e.target.closest('[data-dropdown]');
        if (trigger) {
            e.stopPropagation();
            const menuId = trigger.dataset.dropdown;
            const menu   = document.getElementById(menuId);
            if (!menu) return;
            // Close all others
            document.querySelectorAll('.dropdown-menu.open').forEach(m => {
                if (m !== menu) m.classList.remove('open');
            });
            menu.classList.toggle('open');
            return;
        }
        // Click outside — close all
        document.querySelectorAll('.dropdown-menu.open').forEach(m => m.classList.remove('open'));
    });
}

// ============================================================
// Toast Notification System
// ============================================================
window.showToast = function(message, type = 'info', duration = 4000) {
    let container = document.getElementById('toastContainer');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toastContainer';
        container.style.cssText = 'position:fixed;bottom:1.5rem;right:1.5rem;z-index:9999;display:flex;flex-direction:column;gap:0.5rem;pointer-events:none';
        document.body.appendChild(container);
    }

    const icons = { success: 'checkbox-circle', error: 'error-warning', warning: 'alert-line', info: 'information' };
    const toast = document.createElement('div');
    toast.className = `toast toast-${type} show`;
    toast.style.pointerEvents = 'auto';
    toast.innerHTML = `
        <i class="ri-${icons[type] || 'information'}-line"></i>
        <span>${message}</span>
        <button class="toast-close" onclick="this.parentElement.remove()"><i class="ri-close-line"></i></button>
    `;
    container.appendChild(toast);

    // Animate in
    requestAnimationFrame(() => { toast.style.transform = 'translateX(0)'; toast.style.opacity = '1'; });

    // Auto-dismiss
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(110%)';
        setTimeout(() => toast.remove(), 300);
    }, duration);
};

// ============================================================
// Auto-dismiss flash toasts (from PHP)
// ============================================================
function initAutoFlash() {
    document.querySelectorAll('[data-auto-dismiss]').forEach(toast => {
        setTimeout(() => {
            toast.style.opacity = '0';
            setTimeout(() => toast.remove(), 300);
        }, 4000);
        toast.querySelector('.toast-close')?.addEventListener('click', () => toast.remove());
    });
}

// ============================================================
// Animated stat counters
// ============================================================
function initCounters() {
    const counters = document.querySelectorAll('[data-counter]');
    if (!counters.length) return;
    const io = new IntersectionObserver(entries => {
        entries.forEach(entry => {
            if (!entry.isIntersecting) return;
            const el     = entry.target;
            const target = parseInt(el.dataset.counter, 10);
            const duration = 1500;
            const step   = Math.max(1, Math.floor(target / (duration / 16)));
            let current  = 0;
            const timer  = setInterval(() => {
                current = Math.min(current + step, target);
                el.textContent = current.toLocaleString();
                if (current >= target) clearInterval(timer);
            }, 16);
            io.unobserve(el);
        });
    }, { threshold: 0.3 });
    counters.forEach(el => io.observe(el));
}

// ============================================================
// Notification Dropdown Polling
// ============================================================
function initNotificationDropdown() {
    const notifList = document.getElementById('notifList');
    const notifMenu = document.getElementById('notifMenu');
    if (!notifList || !window.IS_LOGGED_IN) return;

    let loaded = false;

    // Load on first open
    document.querySelector('[data-dropdown="notifMenu"]')?.addEventListener('click', () => {
        if (!loaded) { loadNotifications(); loaded = true; }
    });

    // Poll every 60s
    setInterval(loadNotifications, 60000);

    async function loadNotifications() {
        try {
            const res  = await fetch(window.BASE_URL + '/api/notifications.php?limit=8');
            const data = await res.json();
            if (!data.success) return;
            const notifs = data.notifications;
            if (!notifs.length) {
                notifList.innerHTML = '<div style="padding:1.5rem;text-align:center;color:var(--color-text-3);font-size:0.875rem">No notifications</div>';
                return;
            }
            notifList.innerHTML = notifs.map(n => `
                <div class="notif-item ${!n.is_read ? 'unread' : ''}" data-id="${n.id}">
                    <div class="notif-dot"></div>
                    <div class="notif-text">
                        <strong>${escHtml(n.title)}</strong>
                        <p>${escHtml(n.message)}</p>
                        <time>${n.created_at}</time>
                    </div>
                </div>
            `).join('');
        } catch {}
    }

    // Mark all read
    document.querySelector('[data-action="mark-all-read"]')?.addEventListener('click', async e => {
        e.preventDefault();
        try {
            await fetch(window.BASE_URL + '/api/notifications.php', {
                method: 'POST', headers: {'Content-Type':'application/json'},
                body: JSON.stringify({action:'mark_all_read'})
            });
            document.querySelectorAll('.notif-item.unread').forEach(el => el.classList.remove('unread'));
            document.querySelectorAll('.notif-badge,.notif-count').forEach(el => el.remove());
        } catch {}
    });
}

// ============================================================
// Global AJAX helper
// ============================================================
window.apiPost = async function(url, data) {
    try {
        const res = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        if (!res.ok) throw new Error('HTTP ' + res.status);
        return await res.json();
    } catch (err) {
        return { success: false, message: 'Network error. Please try again.' };
    }
};

// ============================================================
// Escape HTML (used in JS templates)
// ============================================================
function escHtml(str) {
    if (!str) return '';
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ============================================================
// Image preview helper (for file inputs)
// ============================================================
window.previewImage = function(input, previewId) {
    const file = input.files[0];
    if (!file) return;
    if (file.size > 5 * 1024 * 1024) { showToast('Image must be under 5MB.', 'warning'); return; }
    const reader = new FileReader();
    reader.onload = e => {
        const img = document.getElementById(previewId);
        if (img) { img.src = e.target.result; img.style.display = 'block'; }
    };
    reader.readAsDataURL(file);
};

// ============================================================
// Modal helper
// ============================================================
window.openModal = function(id) {
    const modal = document.getElementById(id);
    if (modal) { modal.classList.add('open'); document.body.style.overflow = 'hidden'; }
};
window.closeModal = function(id) {
    const modal = document.getElementById(id);
    if (modal) { modal.classList.remove('open'); document.body.style.overflow = ''; }
};
document.addEventListener('click', e => {
    if (e.target.classList.contains('modal-overlay')) {
        e.target.closest('[id]')?.classList.remove('open');
        document.body.style.overflow = '';
    }
});
