/**
 * AppForm Theme Switcher + App Utilities
 * Manages Light / Dark / System theme preference via localStorage
 */
;(function() {
  'use strict';

  const STORAGE_KEY = 'appform_theme';
  const THEMES = ['light', 'dark', 'system'];
  console.log('APPFORM CONFIRM FIX BUILD 2026-07-18-01');


  // ── Apply theme on page load (before render to avoid flash) ──────────
  function getStoredTheme() {
    try { return localStorage.getItem(STORAGE_KEY) || 'system'; } catch { return 'system'; }
  }

  function getResolvedTheme(theme) {
    if (theme === 'system') {
      return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    }
    return theme;
  }

  function applyTheme(theme) {
    const resolved = getResolvedTheme(theme);
    document.documentElement.setAttribute('data-theme', resolved);
    document.documentElement.setAttribute('data-theme-pref', theme);
    // Update Chart.js defaults if loaded
    if (typeof Chart !== 'undefined') updateChartDefaults(resolved);
  }

  // Apply immediately to avoid FOUC
  applyTheme(getStoredTheme());

  // ── After DOM ready ──────────────────────────────────────────────────
  document.addEventListener('DOMContentLoaded', function() {

    // ── Theme Switcher Buttons ─────────────────────────────────────
    const themeBtns = document.querySelectorAll('.theme-btn[data-theme-value]');
    const currentTheme = getStoredTheme();

    function updateThemeBtns(theme) {
      themeBtns.forEach(btn => {
        btn.classList.toggle('active', btn.dataset.themeValue === theme);
        btn.setAttribute('aria-pressed', btn.dataset.themeValue === theme ? 'true' : 'false');
      });
    }

    updateThemeBtns(currentTheme);

    themeBtns.forEach(btn => {
      btn.addEventListener('click', function() {
        const theme = this.dataset.themeValue;
        try { localStorage.setItem(STORAGE_KEY, theme); } catch {}
        applyTheme(theme);
        updateThemeBtns(theme);
      });
    });

    // Listen for system preference changes
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function() {
      const pref = getStoredTheme();
      if (pref === 'system') applyTheme('system');
    });

    // ── Mobile Sidebar Toggle ──────────────────────────────────────
    const mobileMenuBtn = document.getElementById('btn-mobile-menu');
    const sidebar = document.getElementById('app-sidebar');
    const backdrop = document.getElementById('sidebar-backdrop');

    function openSidebar() {
      if (sidebar) sidebar.classList.add('open');
      if (backdrop) backdrop.classList.add('open');
      document.body.style.overflow = 'hidden';
    }

    function closeSidebar() {
      if (sidebar) sidebar.classList.remove('open');
      if (backdrop) backdrop.classList.remove('open');
      document.body.style.overflow = '';
    }

    if (mobileMenuBtn) mobileMenuBtn.addEventListener('click', openSidebar);
    if (backdrop) backdrop.addEventListener('click', closeSidebar);

    // Close on Escape
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') closeSidebar();
    });

    // ── Auto-dismiss alerts ────────────────────────────────────────
    document.querySelectorAll('.alert[data-auto-dismiss]').forEach(function(el) {
      const delay = parseInt(el.dataset.autoDismiss) || 4000;
      setTimeout(function() {
        el.style.transition = 'opacity 0.4s ease';
        el.style.opacity = '0';
        setTimeout(() => el.remove(), 400);
      }, delay);
    });

    // ── Confirm delete dialogs ─────────────────────────────────────
    document.querySelectorAll('form.js-confirm-form').forEach(function (form) {
      form.addEventListener('submit', function (event) {
        const message = form.dataset.confirmMessage || 'Είστε σίγουροι;';
        if (!window.confirm(message)) {
          event.preventDefault();
        }
      });
    });



    // ── Tooltip init (Bootstrap) ───────────────────────────────────
    if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
      document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function(el) {
        new bootstrap.Tooltip(el, { trigger: 'hover' });
      });
    }

    // ── Table search filter ────────────────────────────────────────
    document.querySelectorAll('[data-table-search]').forEach(function(input) {
      const targetId = input.dataset.tableSearch;
      const table = document.getElementById(targetId);
      if (!table) return;
      const rows = table.querySelectorAll('tbody tr');
      input.addEventListener('input', function() {
        const q = this.value.toLowerCase().trim();
        rows.forEach(function(row) {
          row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
        });
      });
    });

    // ── Status badge helper ────────────────────────────────────────
    document.querySelectorAll('[data-status-badge]').forEach(function(el) {
      const status = (el.textContent || el.dataset.statusBadge || '').toLowerCase().trim();
      const map = {
        'draft':        'badge-status-draft',
        'submitted':    'badge-status-submitted',
        'under_review': 'badge-status-review',
        'approved':     'badge-status-approved',
        'rejected':     'badge-status-rejected',
        'published':    'badge-status-published',
        'archived':     'badge-status-archived',
      };
      if (map[status]) el.classList.add(map[status]);
    });

    // ── Global Custom Modal Confirm Handlers ───────────────────────
    const modal = document.getElementById('app-confirm-modal');
    if (modal) {
      const title = document.getElementById('app-confirm-title');
      const message = document.getElementById('app-confirm-message');
      const cancelButton = document.getElementById('app-confirm-cancel');
      const acceptButton = document.getElementById('app-confirm-accept');

      let pendingForm = null;

      document.addEventListener('submit', function (event) {
        const form = event.target.closest('form.js-confirm-action');
        if (!form) return;
        if (form.dataset.confirmed === 'true') return;

        event.preventDefault();
        pendingForm = form;

        title.textContent = form.dataset.confirmTitle || 'Επιβεβαίωση ενέργειας';
        message.textContent = form.dataset.confirmMessage || 'Είστε σίγουροι ότι θέλετε να συνεχίσετε;';
        acceptButton.textContent = form.dataset.confirmButton || 'Επιβεβαίωση';

        // Apply visual variant formatting
        acceptButton.className = 'btn btn-sm ' + (form.dataset.confirmVariant === 'danger' ? 'btn-danger' : 'btn-warning');

        modal.classList.add('is-visible');
        modal.setAttribute('aria-hidden', 'false');
        cancelButton.focus();
      });

      cancelButton.addEventListener('click', function () {
        pendingForm = null;
        modal.classList.remove('is-visible');
        modal.setAttribute('aria-hidden', 'true');
      });

      acceptButton.addEventListener('click', function () {
        if (!pendingForm) return;
        const formToSubmit = pendingForm;
        pendingForm = null;
        modal.classList.remove('is-visible');
        modal.setAttribute('aria-hidden', 'true');
        formToSubmit.dataset.confirmed = 'true';
        formToSubmit.submit();
      });

      document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && modal.classList.contains('is-visible')) {
          pendingForm = null;
          modal.classList.remove('is-visible');
          modal.setAttribute('aria-hidden', 'true');
        }
      });
    }

  }); // DOMContentLoaded


  // ── Chart.js theme defaults ──────────────────────────────────────────
  function updateChartDefaults(theme) {
    const isDark = theme === 'dark';
    const textColor = isDark ? '#9CA3AF' : '#6B7280';
    const gridColor = isDark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.06)';

    Chart.defaults.color = textColor;
    Chart.defaults.borderColor = gridColor;
    Chart.defaults.plugins = Chart.defaults.plugins || {};
    Chart.defaults.plugins.legend = Chart.defaults.plugins.legend || {};
    Chart.defaults.plugins.legend.labels = {
      ...(Chart.defaults.plugins.legend.labels || {}),
      color: textColor,
      font: { family: "'Inter', 'Segoe UI', sans-serif", size: 12 }
    };
  }

  // ── Appform namespace ────────────────────────────────────────────────
  window.AppForm = window.AppForm || {};
  window.AppForm.theme = {
    get: getStoredTheme,
    set: function(theme) {
      try { localStorage.setItem(STORAGE_KEY, theme); } catch {}
      applyTheme(theme);
    },
    resolved: function() { return getResolvedTheme(getStoredTheme()); }
  };

})();
