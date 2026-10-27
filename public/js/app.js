(function () {
  'use strict';

  // Theme toggle
  document.addEventListener('click', function (e) {
    var t = e.target.closest('[data-theme-toggle]');
    if (!t) return;
    var dark = !document.documentElement.classList.contains('dark');
    document.documentElement.classList.toggle('dark', dark);
    try { window.localStorage.setItem('daylight-theme', dark ? 'dark' : 'light'); } catch (err) { /* ignore */ }
  });

  // Mobile menu (site) and sidebar (portal/admin)
  document.addEventListener('click', function (e) {
    var b = e.target.closest('[data-menu-toggle]');
    if (!b) return;
    var target = document.getElementById(b.getAttribute('aria-controls'));
    if (!target) return;
    var open = b.getAttribute('aria-expanded') !== 'true';
    b.setAttribute('aria-expanded', open ? 'true' : 'false');
    if (target.classList.contains('sidebar-collapsible')) {
      target.classList.toggle('open', open);
    } else {
      target.hidden = !open;
    }
  });
  document.addEventListener('click', function (e) {
    var a = e.target.closest('#mobile-nav a');
    if (!a) return;
    var btn = document.querySelector('[data-menu-toggle][aria-controls="mobile-nav"]');
    var nav = document.getElementById('mobile-nav');
    if (btn) btn.setAttribute('aria-expanded', 'false');
    if (nav) nav.hidden = true;
  });

  // Careers: show/hide the application form for one role
  document.addEventListener('click', function (e) {
    var b = e.target.closest('[data-apply-toggle]');
    if (!b) return;
    var form = document.getElementById(b.getAttribute('data-apply-toggle'));
    if (!form) return;
    var open = !form.classList.contains('is-open');
    form.classList.toggle('is-open', open);
    b.textContent = open ? 'Close' : 'Apply';
    b.classList.toggle('btn-outline', open);
  });

  // Disable submit buttons once a form is submitted (prevents double posts)
  document.addEventListener('submit', function (e) {
    var form = e.target;
    if (!(form instanceof HTMLFormElement) || e.defaultPrevented) return;
    var btn = form.querySelector('button[type="submit"][data-busy-label]');
    if (btn) {
      btn.disabled = true;
      btn.textContent = btn.getAttribute('data-busy-label');
    }
  });
  // Re-enable when returning via back/forward cache
  window.addEventListener('pageshow', function (ev) {
    if (!ev.persisted) return;
    document.querySelectorAll('button[type="submit"][data-busy-label]').forEach(function (b) {
      b.disabled = false;
      if (b.getAttribute('data-label')) b.textContent = b.getAttribute('data-label');
    });
  });

  // Confirm destructive actions
  document.addEventListener('submit', function (e) {
    var msg = e.target.getAttribute && e.target.getAttribute('data-confirm');
    if (msg && !window.confirm(msg)) e.preventDefault();
  }, true);

  // FAQ search: hides questions that do not contain every word typed
  var faqBox = document.querySelector('[data-faq-filter]');
  if (faqBox) {
    faqBox.addEventListener('input', function () {
      var words = faqBox.value.toLowerCase().split(/\s+/).filter(Boolean);
      var shown = 0;
      document.querySelectorAll('[data-faq-item]').forEach(function (item) {
        var text = item.textContent.toLowerCase();
        var match = words.every(function (w) { return text.indexOf(w) !== -1; });
        item.hidden = !match;
        if (match) shown++;
      });
      document.querySelectorAll('[data-faq-group]').forEach(function (g) {
        g.hidden = !g.querySelector('[data-faq-item]:not([hidden])');
      });
      var empty = document.querySelector('[data-faq-empty]');
      if (empty) empty.hidden = shown > 0;
    });
  }

  // Locale dates (the original used toLocaleDateString)
  document.querySelectorAll('time[data-local-date]').forEach(function (el) {
    var d = new Date(el.getAttribute('datetime') + 'T00:00:00Z');
    if (!isNaN(d.getTime())) el.textContent = d.toLocaleDateString(undefined, { timeZone: 'UTC' });
  });

  // Footer year
  document.querySelectorAll('[data-year]').forEach(function (el) { el.textContent = String(new Date().getFullYear()); });
})();
