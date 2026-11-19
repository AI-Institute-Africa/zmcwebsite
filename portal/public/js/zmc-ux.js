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
    ths.forEach(function (th) { if (!th.getAttribute('scope')) th.setAttribute('scope', 'col'); });

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
  function a11y() {
    var content = $('section.content, .content');
    if (content && !content.id) { content.id = 'main-content'; content.setAttribute('tabindex', '-1'); }
    if (content && !$('.zmc-skip')) {
      var s = document.createElement('a'); s.className = 'zmc-skip'; s.href = '#main-content'; s.textContent = 'Skip to main content';
      s.addEventListener('click', function () { setTimeout(function () { content.focus(); }, 0); });
      document.body.insertBefore(s, document.body.firstChild);
    }
    var words = function (c) { var m = /ri-([a-z0-9-]+?)(-line|-fill)?(\s|$)/.exec(c || ''); return m ? m[1].replace(/-/g, ' ') : ''; };
    $$('button, a.btn, a.icon-btn').forEach(function (b) {
      if (b.getAttribute('aria-label') || b.getAttribute('aria-labelledby')) return;
      var txt = (b.textContent || '').replace(/\s+/g, '').length;
      if (txt) return;
      var label = b.getAttribute('title') || words((b.querySelector('i') || {}).className);
      if (label) b.setAttribute('aria-label', label.charAt(0).toUpperCase() + label.slice(1));
    });
    $$('input:not([type=hidden]):not([type=submit]):not([type=button]), select, textarea').forEach(function (f) {
      if (f.getAttribute('aria-label') || f.getAttribute('aria-labelledby')) return;
      if (f.id && document.querySelector('label[for="' + f.id + '"]')) return;
      if (f.closest('label')) return;
      var l = f.getAttribute('placeholder') || f.getAttribute('title') || f.name;
      if (l) f.setAttribute('aria-label', l.replace(/[_\[\]]+/g, ' ').trim());
    });
    // server-side validation errors: connect message to field + announce
    $$('.is-invalid').forEach(function (f, i) {
      f.setAttribute('aria-invalid', 'true');
      var fb = f.parentElement && f.parentElement.querySelector('.invalid-feedback');
      if (fb) { if (!fb.id) fb.id = 'zmcerr' + i + Math.random().toString(36).slice(2, 5); f.setAttribute('aria-describedby', fb.id); }
    });
    $$('.icon-btn i, .btn i[class^=ri-], .btn i[class*=" ri-"], .sidebar-menu i').forEach(function (i) { i.setAttribute('aria-hidden', 'true'); });
    var first = $('.is-invalid'); if (first && first.focus) { try { first.focus({ preventScroll: false }); } catch (e) { /* ignore */ } }
  }

  /* ---------------------------------------------------- search shortcut */
  document.addEventListener('keydown', function (e) {
    if (e.key !== '/' || e.ctrlKey || e.metaKey || e.altKey) return;
    var t = e.target; if (t && (/^(INPUT|TEXTAREA|SELECT)$/.test(t.tagName) || t.isContentEditable)) return;
    var box = $('#zmcGlobalSearch'); if (box) { e.preventDefault(); box.focus(); box.select(); }
  });

  /* ------------------------------------------- forms: drafts + validation */
  function forms() {
    var steps = $$('.step-content');
    var main = steps.length ? steps[0].closest('form') : null;

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

  /* ---------------------------------------------------------------- boot */
  function boot() {
    convertFlashAlerts(); enhanceTables(); sidebar(); a11y(); forms();
    // late-rendered tables (AJAX partials)
    if (window.MutationObserver) {
      var t; new MutationObserver(function () { clearTimeout(t); t = setTimeout(enhanceTables, 150); }).observe(document.body, { childList: true, subtree: true });
    }
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot); else boot();
})();
