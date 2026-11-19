/*!
 * ZMC portal UX layer (2026-11-19)
 * Progressive enhancement: toasts, confirm dialogs, loading buttons, table tools,
 * sidebar groups/badges/recent/pins, accessibility fixes, form drafts + inline validation.
 * Everything is defensive: if something is missing the page keeps working unchanged.
 */
(function () {
  'use strict';
  if (window.__zmcUx) return;
  window.__zmcUx = true;

  var $ = function (s, r) { return (r || document).querySelector(s); };
  var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };
  var store = {
    get: function (k, d) { try { var v = localStorage.getItem(k); return v === null ? d : JSON.parse(v); } catch (e) { return d; } },
    set: function (k, v) { try { localStorage.setItem(k, JSON.stringify(v)); } catch (e) { /* storage unavailable */ } },
    del: function (k) { try { localStorage.removeItem(k); } catch (e) { /* ignore */ } }
  };
  var esc = function (s) { return String(s).replace(/[&<>"']/g, function (c) { return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]; }); };

  /* ---------------------------------------------------------------- toasts */
  function toastHost() {
    var h = $('#zmcToasts');
    if (!h) {
      h = document.createElement('div');
      h.id = 'zmcToasts'; h.className = 'zmc-toasts'; h.setAttribute('aria-live', 'polite'); h.setAttribute('aria-atomic', 'false');
      document.body.appendChild(h);
    }
    return h;
  }
  function toast(message, type, ms) {
    type = type || 'info';
    var icons = { success: 'ri-checkbox-circle-fill', danger: 'ri-error-warning-fill', warning: 'ri-alert-fill', info: 'ri-information-fill' };
    var t = document.createElement('div');
    t.className = 'zmc-toast zmc-toast--' + type;
    t.setAttribute('role', type === 'danger' ? 'alert' : 'status');
    t.innerHTML = '<i class="' + (icons[type] || icons.info) + '" aria-hidden="true"></i><div class="zmc-toast__msg"></div>' +
      '<button type="button" class="zmc-toast__x" aria-label="Dismiss notification">&times;</button>';
    t.querySelector('.zmc-toast__msg').textContent = message;
    var kill = function () { t.classList.add('is-leaving'); setTimeout(function () { t.remove(); }, 250); };
    t.querySelector('.zmc-toast__x').addEventListener('click', kill);
    toastHost().appendChild(t);
    setTimeout(kill, ms || (type === 'danger' ? 9000 : 5000));
    return t;
  }
  window.zmcToast = toast;

  function convertFlashAlerts() {
    $$('.content .alert.alert-success').forEach(function (a) {
      if (a.closest('.modal') || a.closest('form') || a.hasAttribute('data-keep') || a.dataset.zmcDone) return;
      var txt = (a.textContent || '').replace(/\s+/g, ' ').trim();
      if (!txt) return;
      a.dataset.zmcDone = '1';
      toast(txt, 'success');
      a.remove();
    });
    $$('.alert.alert-danger, .alert.alert-warning').forEach(function (a) { if (!a.getAttribute('role')) a.setAttribute('role', 'alert'); });
  }

  /* ----------------------------------------------------------- confirm modal */
  var confirmModal;
  function askConfirm(message, okLabel, danger) {
    return new Promise(function (resolve) {
      if (!window.bootstrap || !window.bootstrap.Modal) { resolve(window.confirm(message)); return; }
      if (!confirmModal) {
        var d = document.createElement('div');
        d.className = 'modal fade'; d.id = 'zmcConfirm'; d.tabIndex = -1; d.setAttribute('aria-hidden', 'true');
        d.setAttribute('aria-labelledby', 'zmcConfirmTitle');
        d.innerHTML = '<div class="modal-dialog modal-dialog-centered"><div class="modal-content">' +
          '<div class="modal-header"><h5 class="modal-title" id="zmcConfirmTitle">Please confirm</h5>' +
          '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>' +
          '<div class="modal-body" id="zmcConfirmBody"></div>' +
          '<div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>' +
          '<button type="button" class="btn btn-danger" id="zmcConfirmOk">Confirm</button></div></div></div>';
        document.body.appendChild(d);
        confirmModal = d;
      }
      var ok = $('#zmcConfirmOk', confirmModal);
      $('#zmcConfirmBody', confirmModal).textContent = message;
      ok.textContent = okLabel || 'Confirm';
      ok.className = 'btn ' + (danger === false ? 'btn-primary' : 'btn-danger');
      var inst = window.bootstrap.Modal.getOrCreateInstance(confirmModal);
      var decided = false;
      var onOk = function () { decided = true; inst.hide(); resolve(true); };
      var onHide = function () { confirmModal.removeEventListener('hidden.bs.modal', onHide); ok.removeEventListener('click', onOk); if (!decided) resolve(false); };
      ok.addEventListener('click', onOk, { once: true });
      confirmModal.addEventListener('hidden.bs.modal', onHide);
      inst.show();
      setTimeout(function () { ok.focus(); }, 300);
    });
  }
  window.zmcConfirm = askConfirm;

  function wantsConfirm(form) {
    if (form.hasAttribute('data-no-confirm') || form.getAttribute('onsubmit')) return null;
    if (form.hasAttribute('data-confirm')) return form.getAttribute('data-confirm') || 'Are you sure?';
    var m = form.querySelector('input[name="_method"]');
    var action = (form.getAttribute('action') || '').toLowerCase();
    if ((m && /^delete$/i.test(m.value)) || /\/(delete|destroy|remove)(\/|$|\?)/.test(action)) return 'This will permanently remove the record. This cannot be undone. Continue?';
    return null;
  }
  document.addEventListener('submit', function (e) {
    var form = e.target;
    if (!(form instanceof HTMLFormElement) || form.dataset.zmcConfirmed) return;
    var msg = wantsConfirm(form);
    if (!msg) return;
    e.preventDefault(); e.stopImmediatePropagation();
    var submitter = e.submitter;
    askConfirm(msg, submitter && submitter.textContent.trim() || 'Confirm', true).then(function (yes) {
      if (!yes) return;
      form.dataset.zmcConfirmed = '1';
      if (form.requestSubmit) { try { form.requestSubmit(submitter || undefined); return; } catch (err) { /* fall through */ } }
      form.submit();
    });
  }, true);
  document.addEventListener('click', function (e) {
    var el = e.target.closest && e.target.closest('a[data-confirm], button[data-confirm]:not([type=submit])');
    if (!el) return;
    e.preventDefault();
    askConfirm(el.getAttribute('data-confirm') || 'Are you sure?', el.getAttribute('data-confirm-ok') || 'Continue', el.getAttribute('data-confirm-danger') !== 'false').then(function (yes) {
      if (yes && el.href) window.location.href = el.href;
    });
  });

  /* --------------------------------------------------------- loading buttons */
  document.addEventListener('submit', function (e) {
    var form = e.target;
    if (!(form instanceof HTMLFormElement) || e.defaultPrevented) return;
    if (form.hasAttribute('data-no-loading') || form.target === '_blank') return;
    if (/export|download|pdf|print|csv/i.test(form.getAttribute('action') || '')) return;
    var btn = e.submitter || form.querySelector('button[type=submit], input[type=submit]');
    if (!btn || btn.disabled || btn.hasAttribute('data-no-loading')) return;
    setTimeout(function () {
      if (btn.tagName === 'BUTTON') {
        btn.dataset.zmcHtml = btn.innerHTML;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>' + (btn.getAttribute('data-loading') || 'Working…');
      }
      btn.disabled = true; btn.setAttribute('aria-busy', 'true');
      setTimeout(function () { restoreBtn(btn); }, 20000);
    }, 0);
  });
  function restoreBtn(btn) {
    if (!btn || !btn.disabled) return;
    if (btn.dataset.zmcHtml) { btn.innerHTML = btn.dataset.zmcHtml; delete btn.dataset.zmcHtml; }
    btn.disabled = false; btn.removeAttribute('aria-busy');
  }
  window.addEventListener('pageshow', function () { $$('button[aria-busy=true]').forEach(restoreBtn); });

  /* -------------------------------------------------------------- tables */
  function cellText(td) { return (td ? td.textContent : '').replace(/\s+/g, ' ').trim(); }
  function parseVal(t) {
    var n = parseFloat(t.replace(/[,\s$%]/g, ''));
    if (t !== '' && !isNaN(n) && /^[\s$+-]*[\d.,]+\s*%?$/.test(t)) return { n: n };
    var d = Date.parse(t);
    if (t.length >= 8 && !isNaN(d) && /\d{4}|\d{1,2}[\/-]\d{1,2}/.test(t)) return { n: d };
    return { s: t.toLowerCase() };
  }
  function dataRows(tbody) {
    return $$(':scope > tr', tbody).filter(function (tr) {
      var tds = tr.children;
      return tds.length > 1 && !(tds.length === 1) && !tr.querySelector('td[colspan]:only-child');
    });
  }
  function enhanceTable(table) {
    if (table.dataset.zmcEnhanced || table.hasAttribute('data-zmc-plain') || table.closest('.modal') || table.closest('.zmc-no-enhance')) return;
    var thead = table.tHead; var tbody = table.tBodies[0];
    if (!thead || !tbody) return;
    table.dataset.zmcEnhanced = '1';
    table.classList.add('zmc-table');
    var ths = $$('th', thead.rows[thead.rows.length - 1]);
    ths.forEach(function (th) {
      if (!th.getAttribute('scope')) th.setAttribute('scope', 'col');
      if (!cellText(th) && !th.getAttribute('aria-label') && !th.querySelector('input,button,select')) th.setAttribute('aria-label', tbody.querySelector('a.btn, button') ? 'Actions' : 'Column');
    });

    // empty state
    var allRows = $$(':scope > tr', tbody);
    var real = dataRows(tbody);
    if (real.length === 0 && allRows.length === 1 && allRows[0].querySelector('td[colspan]')) {
      var td = allRows[0].querySelector('td[colspan]');
      td.classList.add('zmc-empty');
      if (!td.querySelector('i')) td.insertAdjacentHTML('afterbegin', '<i class="ri-inbox-line zmc-empty__icon" aria-hidden="true"></i>');
      return;
    }

    // labels for the phone "stacked card" layout
    var labels = ths.map(function (th) { return cellText(th); });
    if (ths.length <= 8) table.classList.add('zmc-stack');
    real.forEach(function (tr) {
      Array.prototype.forEach.call(tr.children, function (td, i) { if (labels[i] && !td.hasAttribute('data-label')) td.setAttribute('data-label', labels[i]); });
    });
    if (real.length < 3) return;

    // sticky header: constrain tall tables inside their scroll wrapper
    var wrap = table.closest('.table-responsive');
    if (real.length > 12) {
      if (wrap) wrap.classList.add('zmc-table-scroll');
      else { wrap = document.createElement('div'); wrap.className = 'zmc-table-scroll'; table.parentNode.insertBefore(wrap, table); wrap.appendChild(table); }
    }

    // sorting (this page only)
    ths.forEach(function (th, idx) {
      var label = cellText(th);
      if (!label || /^(actions?|#|select)$/i.test(label) || th.querySelector('input[type=checkbox]') || th.hasAttribute('data-nosort')) return;
      th.classList.add('zmc-sortable'); th.setAttribute('tabindex', '0'); th.setAttribute('role', 'columnheader'); th.setAttribute('aria-sort', 'none');
      th.title = 'Sort this page by ' + label;
      var run = function () {
        var dir = th.getAttribute('aria-sort') === 'ascending' ? 'descending' : 'ascending';
        ths.forEach(function (o) { if (o !== th && o.classList.contains('zmc-sortable')) o.setAttribute('aria-sort', 'none'); });
        th.setAttribute('aria-sort', dir);
        var rows = dataRows(tbody);
        var keyed = rows.map(function (tr, i) { return { tr: tr, i: i, v: parseVal(cellText(tr.children[idx])) }; });
        keyed.sort(function (a, b) {
          var r;
          if (a.v.n !== undefined && b.v.n !== undefined) r = a.v.n - b.v.n;
          else r = String(a.v.s !== undefined ? a.v.s : a.v.n).localeCompare(String(b.v.s !== undefined ? b.v.s : b.v.n), undefined, { numeric: true });
          return (dir === 'ascending' ? r : -r) || a.i - b.i;
        });
        keyed.forEach(function (k) { tbody.appendChild(k.tr); });
      };
      th.addEventListener('click', run);
      th.addEventListener('keydown', function (e) { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); run(); } });
    });

    // toolbar: quick filter, count, CSV, print
    if (real.length >= 5) {
      var bar = document.createElement('div');
      bar.className = 'zmc-tabletools no-print';
      var id = 'zmcf' + Math.random().toString(36).slice(2, 7);
      bar.innerHTML = '<label class="visually-hidden" for="' + id + '">Filter rows on this page</label>' +
        '<div class="zmc-tabletools__filter"><i class="ri-filter-3-line" aria-hidden="true"></i><input id="' + id + '" type="search" placeholder="Filter this page…" autocomplete="off"></div>' +
        '<span class="zmc-tabletools__count" aria-live="polite"></span>' +
        '<span class="zmc-tabletools__spacer"></span>' +
        '<button type="button" class="btn btn-sm btn-light" data-act="csv"><i class="ri-download-2-line" aria-hidden="true"></i> CSV</button>' +
        '<button type="button" class="btn btn-sm btn-light" data-act="print"><i class="ri-printer-line" aria-hidden="true"></i> Print</button>';
      (wrap || table).parentNode.insertBefore(bar, wrap || table);
      var count = $('.zmc-tabletools__count', bar);
      var input = $('input', bar);
      var update = function () {
        var q = input.value.trim().toLowerCase(); var shown = 0; var rows = dataRows(tbody);
        rows.forEach(function (tr) { var hit = !q || tr.textContent.toLowerCase().indexOf(q) !== -1; tr.hidden = !hit; if (hit) shown++; });
        count.textContent = 'Showing ' + shown + ' of ' + rows.length + ' on this page';
        var none = $('.zmc-nomatch', tbody);
        if (shown === 0 && !none) {
          var tr = document.createElement('tr'); tr.className = 'zmc-nomatch';
          tr.innerHTML = '<td colspan="' + ths.length + '" class="zmc-empty"><i class="ri-search-line zmc-empty__icon" aria-hidden="true"></i>No rows match your filter.</td>';
          tbody.appendChild(tr);
        } else if (shown > 0 && none) none.remove();
      };
      input.addEventListener('input', update); update();
      bar.addEventListener('click', function (e) {
        var b = e.target.closest('button[data-act]'); if (!b) return;
        if (b.dataset.act === 'print') { table.classList.add('zmc-print-target'); document.body.classList.add('zmc-printing-table'); window.print(); setTimeout(function () { table.classList.remove('zmc-print-target'); document.body.classList.remove('zmc-printing-table'); }, 500); return; }
        var lines = [];
        var head = ths.map(function (th) { return cellText(th); });
        var keep = head.map(function (h) { return !/^(actions?|select)?$/i.test(h); });
        var q = function (v) { v = String(v).replace(/"/g, '""'); return /[",\n]/.test(v) ? '"' + v + '"' : v; };
        lines.push(head.filter(function (h, i) { return keep[i]; }).map(q).join(','));
        dataRows(tbody).filter(function (tr) { return !tr.hidden; }).forEach(function (tr) {
          lines.push(Array.prototype.map.call(tr.children, function (td) { return cellText(td); }).filter(function (v, i) { return keep[i]; }).map(q).join(','));
        });
        var blob = new Blob(['﻿' + lines.join('\r\n')], { type: 'text/csv;charset=utf-8' });
        var a = document.createElement('a'); a.href = URL.createObjectURL(blob);
        a.download = (document.title.split('|')[0].trim().replace(/[^\w]+/g, '-').toLowerCase() || 'export') + '-' + new Date().toISOString().slice(0, 10) + '.csv';
        document.body.appendChild(a); a.click(); setTimeout(function () { URL.revokeObjectURL(a.href); a.remove(); }, 500);
        toast('CSV downloaded (rows on this page).', 'success', 3500);
      });
    }
  }
  function enhanceTables() { $$('.content table.table').forEach(enhanceTable); }
  window.zmcEnhanceTables = enhanceTables;

  /* -------------------------------------------------------------- sidebar */
  function sidebar() {
    var menu = $('.sidebar-menu'); if (!menu) return;
    var here = location.pathname;

    // count badges
    var badges = window.ZMC_BADGES || {};
    $$('li:not(.menu-title) > a', menu).forEach(function (a) {
      var p; try { p = new URL(a.href, location.href).pathname; } catch (e) { return; }
      if (badges[p] && !$('.zmc-badge', a)) a.insertAdjacentHTML('beforeend', '<span class="zmc-badge" aria-label="' + badges[p] + ' waiting">' + (badges[p] > 99 ? '99+' : badges[p]) + '</span>');
    });

    // recent pages
    var title = (document.title.split('|')[0] || '').trim();
    var recent = store.get('zmc.recent', []);
    if (title && /^\/staff\//.test(here) && !/\/(dashboard)?\/?$/.test(here.replace(/^\/staff\/[^/]+/, ''))) {
      recent = recent.filter(function (r) { return r.p !== here; });
      recent.unshift({ p: here, t: title }); recent = recent.slice(0, 6); store.set('zmc.recent', recent);
    }
    var pins = store.get('zmc.pins', []);

    function group(label, items, cls) {
      if (!items.length) return;
      var t = document.createElement('li'); t.className = 'menu-title zmc-extra ' + cls; t.textContent = label;
      var frag = document.createDocumentFragment(); frag.appendChild(t);
      items.forEach(function (it) {
        var li = document.createElement('li'); li.className = 'zmc-extra ' + cls;
        li.innerHTML = '<a href="' + esc(it.p) + '"><i class="' + (cls === 'zmc-pinned' ? 'ri-pushpin-2-fill' : 'ri-history-line') + '" aria-hidden="true"></i> <span>' + esc(it.t) + '</span></a>';
        frag.appendChild(li);
      });
      menu.insertBefore(frag, menu.firstChild);
    }
    var recentShown = recent.filter(function (r) { return r.p !== here && !pins.some(function (p) { return p.p === r.p; }); }).slice(0, 3);
    group('Recent', recentShown, 'zmc-recent');
    group('Pinned', pins, 'zmc-pinned');

    // pin buttons on regular links
    $$('li:not(.menu-title):not(.zmc-extra)', menu).forEach(function (li) {
      var a = li.querySelector(':scope > a'); if (!a || li.querySelector('.zmc-pin')) return;
      var path; try { path = new URL(a.href, location.href).pathname; } catch (e) { return; }
      if (!/^\/staff\//.test(path)) return;
      var label = (a.textContent || '').replace(/\s+/g, ' ').trim();
      var b = document.createElement('button'); b.type = 'button'; b.className = 'zmc-pin';
      var on = pins.some(function (p) { return p.p === path; });
      b.setAttribute('aria-pressed', on ? 'true' : 'false'); b.setAttribute('aria-label', (on ? 'Unpin ' : 'Pin ') + label);
      b.innerHTML = '<i class="ri-pushpin-line" aria-hidden="true"></i>';
      b.addEventListener('click', function (e) {
        e.preventDefault(); e.stopPropagation();
        var list = store.get('zmc.pins', []);
        var i = list.findIndex(function (p) { return p.p === path; });
        if (i >= 0) list.splice(i, 1); else list.unshift({ p: path, t: label });
        store.set('zmc.pins', list.slice(0, 6));
        toast(i >= 0 ? 'Unpinned "' + label + '".' : 'Pinned "' + label + '". Reload to see it at the top.', 'info', 2800);
        b.setAttribute('aria-pressed', i >= 0 ? 'false' : 'true');
      });
      li.appendChild(b);
    });

    // collapsible groups
    var collapsed = store.get('zmc.collapsed', {});
    $$('li.menu-title', menu).forEach(function (title) {
      var key = (title.textContent || '').trim(); if (!key) return;
      var members = [];
      for (var n = title.nextElementSibling; n && !n.classList.contains('menu-title'); n = n.nextElementSibling) members.push(n);
      if (members.length < 2 || title.classList.contains('zmc-recent') || title.classList.contains('zmc-pinned')) return;
      title.classList.add('zmc-collapsible'); title.setAttribute('role', 'button'); title.setAttribute('tabindex', '0');
      var hasActive = members.some(function (m) { return m.classList.contains('active'); });
      var set = function (open) {
        title.setAttribute('aria-expanded', open ? 'true' : 'false');
        title.classList.toggle('is-closed', !open);
        members.forEach(function (m) { m.hidden = !open; });
      };
      set(hasActive || !collapsed[key]);
      var toggle = function () { var open = title.getAttribute('aria-expanded') !== 'true'; set(open); collapsed[key] = !open; store.set('zmc.collapsed', collapsed); };
      title.addEventListener('click', toggle);
      title.addEventListener('keydown', function (e) { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); toggle(); } });
    });
  }

  /* --------------------------------------------------------- accessibility */
  function a11yFixes() {
    var words = function (c) { var m = /ri-([a-z0-9-]+?)(-line|-fill)?(\s|$)/.exec(c || ''); return m ? m[1].replace(/-/g, ' ') : ''; };
    $$('button, a.btn, a.icon-btn').forEach(function (b) {
      if (b.getAttribute('aria-label') || b.getAttribute('aria-labelledby')) return;
      var txt = (b.textContent || '').replace(/\s+/g, '').length;
      if (txt) return;
      var label = b.getAttribute('title') || words((b.querySelector('i') || {}).className);
      if (!label) label = 'Action';
      b.setAttribute('aria-label', label.charAt(0).toUpperCase() + label.slice(1));
    });
    $$('input:not([type=hidden]):not([type=submit]):not([type=button]), select, textarea').forEach(function (f) {
      if (f.getAttribute('aria-label') || f.getAttribute('aria-labelledby')) return;
      if (f.id && document.querySelector('label[for="' + f.id + '"]')) return;
      if (f.closest('label')) return;
      var l = f.getAttribute('placeholder') || f.getAttribute('title') || f.name;
      if (!l && f.tagName === 'SELECT' && f.options && f.options.length) l = (f.options[0].textContent || '').trim();
      if (!l) l = f.tagName === 'SELECT' ? 'Select an option' : (f.type === 'checkbox' ? 'Checkbox' : 'Field');
      f.setAttribute('aria-label', String(l).replace(/[_\[\]]+/g, ' ').trim());
    });
    // icon-only links need a name; images need an alt (decorative when nothing better is known)
    $$('a[href]').forEach(function (a) {
      if (a.getAttribute('aria-label') || a.getAttribute('aria-labelledby')) return;
      if ((a.textContent || '').replace(/\s+/g, '').length || a.querySelector('img[alt]:not([alt=""])')) return;
      var nm = a.getAttribute('title') || words((a.querySelector('i') || {}).className) || String(a.getAttribute('href') || '').split('/').filter(Boolean).pop();
      if (nm) a.setAttribute('aria-label', String(nm).replace(/[-_]+/g, ' '));
    });
    $$('img:not([alt])').forEach(function (img) { img.setAttribute('alt', img.getAttribute('title') || ''); });
    // Bootstrap tab strips: <li> inside role=tablist must be presentational (fixes aria-required-parent/children + listitem)
    $$('[role=tablist] > li:not([role])').forEach(function (li) { li.setAttribute('role', 'presentation'); });
    $$('[role=tablist] .nav-link:not([role])').forEach(function (a) {
      a.setAttribute('role', 'tab'); a.setAttribute('aria-selected', a.classList.contains('active') ? 'true' : 'false');
    });
    // keyboard users must be able to scroll scrollable regions (cheap size check first, computed style only if it overflows)
    var scan = $$('.content *'), budget = 4000;
    for (var si = 0; si < scan.length && budget > 0; si++, budget--) {
      var el = scan[si];
      if (el.hasAttribute('tabindex') || el.scrollWidth <= el.clientWidth + 1 && el.scrollHeight <= el.clientHeight + 1) continue;
      if (el.closest('.vertical-menu, [hidden], .modal:not(.show)') || /^(HTML|BODY|TEXTAREA|SELECT|INPUT|IFRAME|SVG|CANVAS)$/i.test(el.tagName)) continue;
      var ocs = getComputedStyle(el);
      if (!/(auto|scroll)/.test(ocs.overflowY + ocs.overflowX)) continue;
      if (el.querySelector('a[href], button, input, select, textarea, [tabindex]')) continue;
      el.setAttribute('tabindex', '0');
      if (!el.getAttribute('role')) el.setAttribute('role', 'region');
      if (!el.getAttribute('aria-label')) el.setAttribute('aria-label', 'Scrollable content');
    }
    headingLevels();
    $$('th').forEach(function (th) {
      if ((th.textContent || '').trim() || th.getAttribute('aria-label') || th.querySelector('input,button,select')) return;
      th.setAttribute('aria-label', th.closest('table') && th.closest('table').querySelector('tbody a.btn, tbody button') ? 'Actions' : 'Column');
    });
    // server-side validation errors: connect message to field + announce
    $$('.is-invalid').forEach(function (f, i) {
      f.setAttribute('aria-invalid', 'true');
      var fb = f.parentElement && f.parentElement.querySelector('.invalid-feedback');
      if (fb) { if (!fb.id) fb.id = 'zmcerr' + i + Math.random().toString(36).slice(2, 5); f.setAttribute('aria-describedby', fb.id); }
    });
    $$('.icon-btn i, .btn i[class^=ri-], .btn i[class*=" ri-"], .sidebar-menu i').forEach(function (i) { i.setAttribute('aria-hidden', 'true'); });
  }


  /* Heading outline for assistive tech: exactly one level-1 heading per page and no skipped levels.
     Uses aria-level only, so nothing changes visually. */
  function headingLevels() {
    var root = $('.content'); if (!root) return;
    var hs = $$('h1, h2, h3, h4, h5, h6, [role=heading]', root).filter(function (h) { return !h.closest('.modal, [hidden]'); });
    if (!hs.length) return;
    var hasH1 = hs.some(function (h) { return h.tagName === 'H1' || h.getAttribute('aria-level') === '1' && !h.hasAttribute('data-zmc-lvl'); });
    var prev = 0;
    hs.forEach(function (h, i) {
      var natural = /^H[1-6]$/.test(h.tagName) ? +h.tagName[1] : (+h.getAttribute('aria-level') || 2);
      var want = natural;
      if (i === 0 && !hasH1) want = 1;                       // first heading is the page title
      else if (prev && want > prev + 1) want = prev + 1;      // never skip a level
      if (want !== natural || h.hasAttribute('data-zmc-lvl')) {
        h.setAttribute('role', 'heading'); h.setAttribute('aria-level', String(want)); h.setAttribute('data-zmc-lvl', '1');
      }
      prev = want;
    });
  }

  function a11yInit() {
    var content = $('section.content, .content');
    if (content && !content.id) { content.id = 'main-content'; content.setAttribute('tabindex', '-1'); }
    if (content && !$('.zmc-skip')) {
      var s = document.createElement('a'); s.className = 'zmc-skip'; s.href = '#main-content'; s.textContent = 'Skip to main content';
      s.addEventListener('click', function () { setTimeout(function () { content.focus(); }, 0); });
      document.body.insertBefore(s, document.body.firstChild);
    }
    a11yFixes();
    var first = $('.is-invalid'); if (first && first.focus) { try { first.focus({ preventScroll: false }); } catch (e) { /* ignore */ } }
  }

  /* ---------------------------------------------------- search shortcut */
  document.addEventListener('keydown', function (e) {
    if (e.key !== '/' || e.ctrlKey || e.metaKey || e.altKey) return;
    var t = e.target; if (t && (/^(INPUT|TEXTAREA|SELECT)$/.test(t.tagName) || t.isContentEditable)) return;
    var box = $('#zmcGlobalSearch'); if (box) { e.preventDefault(); box.focus(); box.select(); }
  });

  /* ------------------------------------------- forms: drafts + validation */
  function bindValidation() {
    // inline validation for any form
    $$('form').forEach(function (form) {
      if (form.dataset.zmcValidated || form.hasAttribute('novalidate') || form.closest('.modal')) return;
      form.dataset.zmcValidated = '1';
      $$('[required]', form).forEach(function (f) {
        var lab = f.id && form.querySelector('label[for="' + f.id + '"]') || f.closest('.mb-3, .form-group, .col, [class*=col-]') && f.closest('.mb-3, .form-group, .col, [class*=col-]').querySelector('label');
        if (lab && !lab.classList.contains('zmc-required') && !/\*/.test(lab.textContent)) lab.classList.add('zmc-required');
      });
      var show = function (f) {
        if (f.type === 'hidden' || f.disabled || !f.willValidate) return;
        var msg = $('.zmc-fielderr[data-for="' + (f.id || f.name) + '"]', form.parentNode);
        if (f.validity.valid) {
          f.classList.remove('is-invalid'); f.removeAttribute('aria-invalid'); f.removeAttribute('aria-describedby'); if (msg) msg.remove(); return;
        }
        f.classList.add('is-invalid'); f.setAttribute('aria-invalid', 'true');
        if (f.parentElement.querySelector('.invalid-feedback:not(.zmc-fielderr)')) return;
        if (!msg) {
          msg = document.createElement('div'); msg.className = 'invalid-feedback d-block zmc-fielderr';
          msg.setAttribute('data-for', f.id || f.name); msg.id = 'zmcmsg' + Math.random().toString(36).slice(2, 7);
          (f.closest('.input-group') || f).insertAdjacentElement('afterend', msg);
        }
        msg.textContent = f.validationMessage; f.setAttribute('aria-describedby', msg.id);
      };
      form.addEventListener('focusout', function (e) { if (e.target.matches && e.target.matches('input, select, textarea') && (e.target.required || e.target.value)) show(e.target); });
      form.addEventListener('input', function (e) { if (e.target.classList && e.target.classList.contains('is-invalid')) show(e.target); });
      form.addEventListener('invalid', function (e) { show(e.target); }, true);
    });

  }

  function forms() {
    var steps = $$('.step-content');
    var main = steps.length ? steps[0].closest('form') : null;

    bindValidation();

    if (!main) return;
    // draft autosave (this browser only)
    var key = 'zmc.draft:' + location.pathname;
    var SENSITIVE = /pass(word|port)|national|nrc|id[_-]?(no|num|number|card)|ssn|bank|account[_-]?(no|num)|card|cvv|iban|swift|dob|birth|phone|mobile|msisdn|whatsapp/i;
    var TTL = 7 * 864e5;
    var statusEl = document.createElement('div');
    statusEl.id = 'zmcDraftStatus'; statusEl.className = 'zmc-draft no-print'; statusEl.setAttribute('role', 'status'); statusEl.setAttribute('aria-live', 'polite');
    statusEl.innerHTML = '<i class="ri-save-3-line" aria-hidden="true"></i><span>Your progress is saved on this device as you type.</span>' +
      '<button type="button" class="zmc-draft__clear" aria-label="Clear the saved draft from this device">Clear</button>';
    statusEl.querySelector('.zmc-draft__clear').addEventListener('click', function () {
      store.del(key); statusEl.classList.remove('is-saved');
      statusEl.querySelector('span').textContent = 'Saved draft cleared from this device.';
      toast('Saved draft cleared from this device.', 'info', 2500);
    });
    document.body.appendChild(statusEl);

    var snapshot = function () {
      var o = {};
      Array.prototype.forEach.call(main.elements, function (f) {
        if (!f.name || f.disabled || /^(file|password|submit|button|image|reset)$/.test(f.type) || f.name === '_token' || f.name === '_method') return;
        if (SENSITIVE.test(f.name) || SENSITIVE.test(f.id || '')) return;   // never keep identifiers in browser storage
        if (f.type === 'checkbox') { (o[f.name] = o[f.name] || []); if (f.checked) o[f.name].push(f.value); }
        else if (f.type === 'radio') { if (f.checked) o[f.name] = f.value; }
        else if (f.type !== 'hidden') o[f.name] = f.value;
      });
      return o;
    };
    var timer;
    var save = function () {
      clearTimeout(timer);
      timer = setTimeout(function () {
        var s = snapshot();
        var filled = Object.keys(s).filter(function (k) { var v = s[k]; return Array.isArray(v) ? v.length : (v && String(v).trim()); }).length;
        if (!filled) return;
        store.set(key, { at: Date.now(), data: s });
        var d = new Date();
        $('span', statusEl).textContent = 'Draft saved on this device at ' + ('0' + d.getHours()).slice(-2) + ':' + ('0' + d.getMinutes()).slice(-2) + '.';
        statusEl.classList.add('is-saved');
      }, 800);
    };
    main.addEventListener('input', save); main.addEventListener('change', save);
    main.addEventListener('submit', function (e) { if (!e.defaultPrevented) store.del(key); });

    var saved = store.get(key, null);
    if (saved && Date.now() - saved.at >= TTL) { store.del(key); saved = null; }
    if (saved && saved.data && Date.now() - saved.at < TTL) {
      var current = snapshot();
      var empty = Object.keys(current).every(function (k) { var v = current[k]; return Array.isArray(v) ? !v.length : !(v && String(v).trim()); });
      if (empty) {
        var ban = document.createElement('div');
        ban.className = 'zmc-restore alert alert-info d-flex align-items-center gap-2 flex-wrap'; ban.setAttribute('role', 'region'); ban.setAttribute('aria-label', 'Saved draft');
        ban.innerHTML = '<i class="ri-history-line" aria-hidden="true"></i><span class="flex-grow-1">We found a draft saved on this device ' + esc(new Date(saved.at).toLocaleString()) + '. Restore it?</span>' +
          '<button type="button" class="btn btn-sm btn-primary" data-r="1">Restore draft</button><button type="button" class="btn btn-sm btn-light" data-r="0">Discard</button>';
        main.parentNode.insertBefore(ban, main);
        ban.addEventListener('click', function (e) {
          var b = e.target.closest('button[data-r]'); if (!b) return;
          if (b.dataset.r === '1') {
            Object.keys(saved.data).forEach(function (name) {
              var v = saved.data[name]; var fields = main.elements[name]; if (!fields) return;
              var list = fields.length !== undefined && !fields.tagName ? Array.prototype.slice.call(fields) : [fields];
              list.forEach(function (f) {
                if (f.type === 'checkbox') f.checked = Array.isArray(v) && v.indexOf(f.value) !== -1;
                else if (f.type === 'radio') f.checked = (f.value === v);
                else if (f.type !== 'file' && f.type !== 'hidden') f.value = v;
                f.dispatchEvent(new Event('input', { bubbles: true })); f.dispatchEvent(new Event('change', { bubbles: true }));
              });
            });
            toast('Draft restored. Please review it before submitting.', 'success');
          } else { store.del(key); toast('Saved draft discarded.', 'info', 2500); }
          ban.remove();
        });
      }
    }
  }


  /* ---------------------------------------------------- contrast safety net */
  // Last line of defence for text that stylesheets can't fix (inline !important, colours buried in custom
  // properties, third-party blocks): find text under 3:1 against its real solid background and set a readable colour.
  // Original inline colours are restored on theme change. Skips anything over images/gradients or unknown backgrounds.
  var cgLin = function (v) { v /= 255; return v <= 0.03928 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4); };
  var cgLum = function (c) { return 0.2126 * cgLin(c.r) + 0.7152 * cgLin(c.g) + 0.0722 * cgLin(c.b); };
  var cgParse = function (str) { var m = /rgba?\(([^)]+)\)/.exec(str || ''); if (!m) return null; var q = m[1].split(/[ ,\/]+/).map(Number); return { r: q[0], g: q[1], b: q[2], a: q.length > 3 ? q[3] : 1 }; };

  // Inline !important always beats stylesheet rules (including the generated dark remaps), but writing through el.style
  // would re-serialise the whole style attribute ("background:#e0e7ff" -> "background: rgb(224, 231, 255)") and the
  // attribute-selector remaps would stop matching. So we APPEND to the original attribute text with setAttribute: the
  // original declarations stay byte-for-byte intact, and the later declaration wins.
  var cgOrig = new Map();
  function cgApply(el, color) {
    if (!cgOrig.has(el)) cgOrig.set(el, el.getAttribute('style') || '');
    var base = cgOrig.get(el), sep = base && !/;\s*$/.test(base) ? ';' : '';
    el.setAttribute('style', base + sep + 'color:' + color + ' !important');
    el.setAttribute('data-zmc-cg', '1');
  }
  function cgFlush() { /* nothing to flush: colours are written inline */ }

  function cgBg(el, memo) {
    if (memo.has(el)) return memo.get(el);
    var layers = [], result = null, e = el, solid = false;
    for (; e && e.nodeType === 1; e = e.parentElement) {
      if (memo.has(e) && e !== el) { result = memo.get(e); layers = null; break; }
      var cs = getComputedStyle(e);
      if (cs.backgroundImage && cs.backgroundImage !== 'none') { result = false; layers = null; break; }
      var c = cgParse(cs.backgroundColor);
      if (c && c.a > 0) { layers.push(c); if (c.a >= 1) { solid = true; break; } }
    }
    if (layers) {
      if (!solid) { result = false; }
      else {
        var out = { r: 0, g: 0, b: 0 };
        for (var i = layers.length - 1; i >= 0; i--) { var l = layers[i]; out = { r: l.r * l.a + out.r * (1 - l.a), g: l.g * l.a + out.g * (1 - l.a), b: l.b * l.a + out.b * (1 - l.a) }; }
        result = out;
      }
    }
    memo.set(el, result);
    return result;
  }

  function cgNudge(fg, bg, L2, target) {
    var r = fg.r / 255, g = fg.g / 255, b = fg.b / 255, mx = Math.max(r, g, b), mn = Math.min(r, g, b), l = (mx + mn) / 2, h = 0, sat = 0;
    if (mx !== mn) {
      var d = mx - mn; sat = l > 0.5 ? d / (2 - mx - mn) : d / (mx + mn);
      h = mx === r ? (g - b) / d + (g < b ? 6 : 0) : mx === g ? (b - r) / d + 2 : (r - g) / d + 4; h /= 6;
    }
    var hue2 = function (p, q, t) { if (t < 0) t += 1; if (t > 1) t -= 1; return t < 1 / 6 ? p + (q - p) * 6 * t : t < 1 / 2 ? q : t < 2 / 3 ? p + (q - p) * (2 / 3 - t) * 6 : p; };
    var toRgb = function (hh, ss, ll) {
      if (ss === 0) return { r: ll * 255, g: ll * 255, b: ll * 255 };
      var q = ll < 0.5 ? ll * (1 + ss) : ll + ss - ll * ss, p = 2 * ll - q;
      return { r: hue2(p, q, hh + 1 / 3) * 255, g: hue2(p, q, hh) * 255, b: hue2(p, q, hh - 1 / 3) * 255 };
    };
    var dir = L2 < 0.4 ? 1 : -1;                                           // light bg: darken text; dark bg: lighten text
    for (var step = 1; step <= 60; step++) {
      var c = toRgb(h, sat, Math.min(1, Math.max(0, l + dir * step * 0.01)));
      var Lc = cgLum(c); var cr = (Math.max(Lc, L2) + 0.05) / (Math.min(Lc, L2) + 0.05);
      if (cr >= target) return 'rgb(' + Math.round(c.r) + ',' + Math.round(c.g) + ',' + Math.round(c.b) + ')';
    }
    return null;
  }

  function contrastGuard(scope) {
    var root = scope || $('.content'); if (!root) return 0;
    var memo = new Map(), fixed = 0, n = 0;
    var walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT, null);
    var seen = new Set(), node;
    while ((node = walker.nextNode())) {
      var el = node.parentElement;
      if (!el || seen.has(el) || !node.textContent.trim() || el.hasAttribute('data-zmc-nocg')) continue;
      seen.add(el); if (++n > 5000) break;
      if (/^(SCRIPT|STYLE|NOSCRIPT|TEXTAREA|OPTION)$/.test(el.tagName) || el.closest('[hidden], .vertical-menu')) continue;
      var rect = el.getBoundingClientRect(); if (rect.width < 2 || rect.height < 2) continue;
      var cs = getComputedStyle(el);
      if (cs.visibility === 'hidden' || cs.display === 'none' || parseFloat(cs.opacity) < 0.3) continue;
      var fg = cgParse(cs.color), bg = cgBg(el, memo); if (!fg || !bg) continue;
      var a = fg.a, mix = { r: fg.r * a + bg.r * (1 - a), g: fg.g * a + bg.g * (1 - a), b: fg.b * a + bg.b * (1 - a) };
      var L1 = cgLum(mix), L2 = cgLum(bg);
      var ratio = (Math.max(L1, L2) + 0.05) / (Math.min(L1, L2) + 0.05);
      var px = parseFloat(cs.fontSize) || 14, bold = (parseInt(cs.fontWeight, 10) || 400) >= 700;
      var need = (px >= 24 || (px >= 18.66 && bold)) ? 3 : 4.5;          // WCAG large-text threshold
      if (ratio >= need) continue;
      if (ratio >= 3) {                                                    // close: nudge lightness, keep the hue
        var nudged = cgNudge(fg, bg, L2, need + 0.15);
        if (nudged) { cgApply(el, nudged); fixed++; continue; }
      }
      var white = 1.05 / (L2 + 0.05), black = (L2 + 0.05) / 0.05;
      cgApply(el, white >= black ? '#f8fafc' : '#0f172a');
      fixed++;
    }
    cgFlush();
    return fixed;
  }
  function contrastReset() {
    cgOrig.forEach(function (orig, el) {
      if (orig === '') { el.removeAttribute('style'); } else { el.setAttribute('style', orig); }
      el.removeAttribute('data-zmc-cg');
    });
    cgOrig.clear();
  }
  window.zmcContrastGuard = function (scope) { contrastReset(); return contrastGuard(scope); };
  function startContrastGuard() {
    var idle = window.requestIdleCallback || function (f) { setTimeout(f, 60); };
    var run = function () { idle(function () { contrastReset(); contrastGuard(); }); };
    setTimeout(run, 900);                                         // after entry animations settle
    if (window.MutationObserver) {
      new MutationObserver(function () { setTimeout(run, 450); }).observe(document.documentElement, { attributes: true, attributeFilter: ['data-bs-theme'] });
    }
    document.addEventListener('shown.bs.modal', function (e) { contrastGuard(e.target); });
    document.addEventListener('shown.bs.tab', function () { setTimeout(function () { contrastGuard(); }, 120); });
  }


  /* ------------------------------------------------------------ chart theming */
  // ApexCharts / Chart.js colours are set in JS and ignore stylesheets, so in dark mode their text and grids stay dark grey.
  // We set library-wide defaults (for charts created later) and update the ones that already exist.
  function chartPalette() {
    var dark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
    return { dark: dark, fore: dark ? '#cbd5e1' : '#334155', grid: dark ? '#334155' : '#e2e8f0' };
  }
  // ApexCharts keeps no public registry for ungrouped charts, so we record each chart when it renders.
  var apexRegistry = [];
  function trackApex() {
    var A = window.ApexCharts;
    if (!A || !A.prototype || A.prototype.__zmcTracked) return;
    var origRender = A.prototype.render;
    A.prototype.render = function () { if (apexRegistry.indexOf(this) < 0) apexRegistry.push(this); return origRender.apply(this, arguments); };
    A.prototype.__zmcTracked = true;
  }
  function applyChartTheme() {
    var pal = chartPalette(), mode = pal.dark ? 'dark' : 'light';
    try {
      if (window.Apex) {
        var A = window.Apex;
        A.chart = Object.assign({}, A.chart, { foreColor: pal.fore, background: 'transparent' });
        A.grid = Object.assign({}, A.grid, { borderColor: pal.grid });
        A.tooltip = Object.assign({}, A.tooltip, { theme: mode });
        A.theme = Object.assign({}, A.theme, { mode: mode });
      }
    } catch (e) { /* defaults are best effort */ }
    apexRegistry.slice().forEach(function (chart) {
      try {
        if (!chart.el || !document.body.contains(chart.el)) { apexRegistry.splice(apexRegistry.indexOf(chart), 1); return; }
        chart.updateOptions({ chart: { foreColor: pal.fore, background: 'transparent' }, grid: { borderColor: pal.grid }, tooltip: { theme: mode }, theme: { mode: mode } }, false, false);
      } catch (e) { /* chart may be destroyed */ }
    });
    try {
      var C = window.Chart;
      if (C && C.defaults) {
        C.defaults.color = pal.fore; C.defaults.borderColor = pal.grid;       // defaults only: per-axis writes recurse inside Chart.js 4's options proxy
        // Existing charts resolved their scale options at creation, so write the colours into each chart's own user config
        // (writing through chart.options recurses inside Chart.js 4's proxy). Colours the page set itself are left alone.
        var themeOwned = ['#cbd5e1', '#334155', '#e2e8f0'];
        Object.keys(C.instances || {}).forEach(function (k) {
          try {
            var ch = C.instances[k]; if (!ch || !ch.update || !ch.config || !ch.config.options) return;
            var uo = ch.config.options; uo.scales = uo.scales || {};
            Object.keys(ch.scales || {}).forEach(function (id) {
              var sc = uo.scales[id] = uo.scales[id] || {};
              var tk = sc.ticks || {}, gd = sc.grid || {};
              if (tk.color === undefined || themeOwned.indexOf(tk.color) >= 0) sc.ticks = Object.assign({}, tk, { color: pal.fore });
              if (gd.color === undefined || themeOwned.indexOf(gd.color) >= 0) sc.grid = Object.assign({}, gd, { color: pal.grid });
            });
            ch.update('none');
          } catch (e) { /* ignore */ }
        });
      }
    } catch (e) { /* charts are optional */ }
  }
  function startChartTheme() {
    trackApex();
    applyChartTheme();                                               // defaults first: charts created afterwards pick them up
    window.addEventListener('load', function () { setTimeout(applyChartTheme, 250); });
    window.addEventListener('zmc:theme-changed', function () { setTimeout(applyChartTheme, 50); });
    if (window.MutationObserver) {
      new MutationObserver(function () { setTimeout(applyChartTheme, 50); }).observe(document.documentElement, { attributes: true, attributeFilter: ['data-bs-theme'] });
    }
    window.zmcApplyChartTheme = applyChartTheme;
  }

  /* ---------------------------------------------------------------- boot */
  function boot() {
    convertFlashAlerts(); enhanceTables(); sidebar(); a11yInit(); forms(); startContrastGuard(); startChartTheme();
    // late-rendered tables (AJAX partials)
    if (window.MutationObserver) {
      var t; new MutationObserver(function () { clearTimeout(t); t = setTimeout(function () { enhanceTables(); a11yFixes(); bindValidation(); }, 150); }).observe(document.body, { childList: true, subtree: true });
      window.zmcA11y = a11yFixes; window.zmcBindValidation = bindValidation;
    }
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot); else boot();
})();
