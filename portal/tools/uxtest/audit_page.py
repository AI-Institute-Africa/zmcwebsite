import os
_HERE = os.path.dirname(os.path.abspath(__file__))
WORK = os.environ.get("UXTEST_WORK", os.path.join(_HERE, "work")).replace("\\", "/")
PORTAL = os.environ.get("PORTAL_ROOT", os.path.normpath(os.path.join(_HERE, "..", ".."))).replace("\\", "/")
"""Build the combined audit master page. usage: audit_page.py <theme> [lite]
Per view: contrast (after guard), axe violations, overflow, headings, perf, table/modal/confirm/dropdown/form feature tests."""
import re, sys
S = WORK
root = PORTAL
theme = sys.argv[1]
lite = len(sys.argv) > 2 and sys.argv[2] == "lite"
layout = open(root + '/resources/views/layouts/portal.blade.php', encoding='utf-8').read()
_blocks = [b for b in re.findall(r'<style[^>]*>(.*?)</style>', layout, re.S) if '{{' not in b and '@if' not in b and '@php' not in b]
css = "\n".join(b for b in _blocks if 'LIGHT MODE CONTRAST FIXES' not in b)
uiblock = "\n".join(b for b in _blocks if 'LIGHT MODE CONTRAST FIXES' in b)
uxcss = open(root + '/public/css/zmc-ux.css', encoding='utf-8').read()
darkcss = open(root + '/public/css/zmc-dark.css', encoding='utf-8').read()
uxjs = open(root + '/public/js/zmc-ux.js', encoding='utf-8').read()

page = r'''<!doctype html><html data-bs-theme="__THEME__"><head><meta charset=utf-8><title>audit</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="/vendor/bootstrap/bootstrap.min.css" rel=stylesheet><link href="/vendor/remixicon/remixicon.css" rel=stylesheet>
<style>__CSS__</style><style>__UXCSS__</style><style>__DARKCSS__</style><style>__UIBLOCK__</style>
<script src="/vendor/bootstrap/bootstrap.bundle.min.js"></script><script src="/vendor/axe.min.js"></script></head>
<body><div class="app"><aside class="vertical-menu"><ul class="sidebar-menu"><li class="menu-title">Section</li><li class="active"><a href="/staff/x"><i class="ri-home-line"></i> <span>Item</span></a></li><li><a href="/staff/y"><i class="ri-home-line"></i> <span>Item 2</span></a></li></ul></aside>
<main class="main"><header class="topbar"><div class="topbar-row--main"></div></header><section class="content staff-page-surface" id="stage"></section></main></div>
<pre id="results" style="display:none"></pre>
<script>__UXJS__</script>
<script>
const THEME = "__THEME__", LITE = __LITE__;
const errs = []; window.addEventListener('error', e => errs.push(String(e.message).slice(0, 120)));
const sleep = ms => new Promise(r => setTimeout(r, ms));
const lin = v => { v /= 255; return v <= .03928 ? v / 12.92 : Math.pow((v + .055) / 1.055, 2.4); };
const lum = c => .2126 * lin(c.r) + .7152 * lin(c.g) + .0722 * lin(c.b);
const parse = s => { const m = (s || '').match(/rgba?\(([^)]+)\)/); if (!m) return null; const p = m[1].split(/[ ,\/]+/).map(Number); return { r: p[0], g: p[1], b: p[2], a: p.length > 3 ? p[3] : 1 }; };
function effectiveBg(el) {
  const layers = []; let hasImage = false;
  for (let e = el; e; e = e.parentElement) {
    const cs = getComputedStyle(e);
    if (cs.backgroundImage && cs.backgroundImage !== 'none') { hasImage = true; break; }
    const c = parse(cs.backgroundColor);
    if (c && c.a > 0) { layers.push(c); if (c.a >= 1) break; }
  }
  if (hasImage) return null;
  let out = THEME === 'dark' ? { r: 15, g: 23, b: 42 } : { r: 247, g: 249, b: 252 };
  if (layers.length && layers[layers.length - 1].a >= 1) out = { r: 0, g: 0, b: 0 };
  for (let i = layers.length - 1; i >= 0; i--) { const l = layers[i]; out = { r: l.r * l.a + out.r * (1 - l.a), g: l.g * l.a + out.g * (1 - l.a), b: l.b * l.a + out.b * (1 - l.a) }; }
  return out;
}
const AXE_OFF = ['region', 'page-has-heading-one', 'landmark-one-main', 'document-title', 'html-has-lang', 'bypass', 'landmark-unique', 'landmark-no-duplicate-banner', 'landmark-banner-is-top-level', 'landmark-complementary-is-top-level', 'landmark-contentinfo-is-top-level', 'landmark-main-is-top-level', 'landmark-no-duplicate-contentinfo', 'landmark-no-duplicate-main', 'meta-viewport', 'duplicate-id', 'duplicate-id-aria', 'duplicate-id-active'];
function dataRows(tbody) { return [...tbody.children].filter(tr => tr.tagName === 'TR' && tr.children.length > 1 && !tr.querySelector('td[colspan]:only-child')); }
function csvLine(l) { const out = []; let cur = '', q = false; for (let i = 0; i < l.length; i++) { const c = l[i]; if (q) { if (c === '"' && l[i + 1] === '"') { cur += '"'; i++; } else if (c === '"') q = false; else cur += c; } else if (c === '"') q = true; else if (c === ',') { out.push(cur); cur = ''; } else cur += c; } out.push(cur); return out; }

(async () => {
  const manifest = await (await fetch('/frag/manifest.json')).json();
  const stage = document.getElementById('stage');
  const report = [];
  const ONLY = new URLSearchParams(location.search).get('only'); const onlySet = ONLY === null ? null : ONLY.split(',').map(Number);
  for (let i = 0; i < manifest.length; i++) {
    if (onlySet && !onlySet.includes(i)) continue;
    const html = await (await fetch('/frag/' + i + '.html')).text();
    errs.length = 0;
    stage.innerHTML = html;
    stage.querySelectorAll('.modal.show, .modal-backdrop').forEach(n => n.remove());
    document.querySelectorAll('.modal-backdrop').forEach(n => n.remove());
    await sleep(700);
    const r = { v: manifest[i], severe: 0, low: 0, ex: [], fixed: 0, ms_guard: 0, ms_enh: 0, tables: 0, tableBad: [], modals: 0, modalBad: [], confirmBad: 0, confirms: 0, ddBad: 0, dds: 0, reqBad: 0, reqs: 0, axe: [], h1: 0, hskip: 0, overflowX: 0, overflowEx: [], errors: [] };

    // performance of the two scanners (re-run on a clean state)
    let t0 = performance.now(); window.zmcEnhanceTables && window.zmcEnhanceTables(); r.ms_enh = +(performance.now() - t0).toFixed(1);
    t0 = performance.now(); window.zmcA11y && window.zmcA11y(); window.zmcBindValidation && window.zmcBindValidation(); r.ms_a11y = +(performance.now() - t0).toFixed(1);
    t0 = performance.now(); r.fixed = window.zmcContrastGuard ? window.zmcContrastGuard(stage) : 0; r.ms_guard = +(performance.now() - t0).toFixed(1);
    await sleep(350);                                   // let CSS colour transitions finish before measuring (computed style is mid-transition otherwise)

    // contrast (after guard, as a real user sees it)
    stage.querySelectorAll('*').forEach(el => {
      if (el.closest('.modal:not(.show)') || el.closest('[hidden], template, script, style')) return;
      const own = [...el.childNodes].some(n => n.nodeType === 3 && n.textContent.trim().length > 1);
      if (!own) return;
      const rect = el.getBoundingClientRect(); if (rect.width < 2 || rect.height < 2) return;
      const cs = getComputedStyle(el); if (cs.visibility === 'hidden' || cs.display === 'none' || parseFloat(cs.opacity) < 0.3) return;
      const fg = parse(cs.color), bg = effectiveBg(el); if (!fg || !bg) return;
      const a = fg.a, mix = { r: fg.r * a + bg.r * (1 - a), g: fg.g * a + bg.g * (1 - a), b: fg.b * a + bg.b * (1 - a) };
      const L1 = lum(mix), L2 = lum(bg), ratio = (Math.max(L1, L2) + .05) / (Math.min(L1, L2) + .05);
      if (ratio < 3) { r.severe++; r.low++; if (r.ex.length < 4) r.ex.push(ratio.toFixed(1) + ' "' + el.textContent.trim().slice(0, 24) + '" ' + el.tagName.toLowerCase() + '.' + String(el.className).trim().replace(/\s+/g, '.') + ' fg=' + cs.color + ' bg=' + Math.round(bg.r) + ',' + Math.round(bg.g) + ',' + Math.round(bg.b)); }
      else if (ratio < 4.5) { r.low++; if (r.ex.length < 4) r.ex.push(ratio.toFixed(1) + ' "' + el.textContent.trim().slice(0, 24) + '" ' + el.tagName.toLowerCase() + '.' + String(el.className).trim().replace(/\s+/g, '.') + ' fg=' + cs.color + ' bg=' + Math.round(bg.r) + ',' + Math.round(bg.g) + ',' + Math.round(bg.b)); }
    });

    // overflow (horizontal) + headings
    const vw = document.documentElement.clientWidth;
    r.overflowX = Math.max(0, document.documentElement.scrollWidth - vw);
    if (r.overflowX > 4) stage.querySelectorAll('*').forEach(el => { if (r.overflowEx.length < 3) { const b = el.getBoundingClientRect(); if (b.width > 0 && b.right > vw + 4 && !el.closest('.table-responsive, .zmc-table-scroll, .modal, [style*="overflow"]')) r.overflowEx.push(el.tagName.toLowerCase() + '.' + String(el.className).split(' ')[0] + ' right=' + Math.round(b.right)); } });
    const hs = [...stage.querySelectorAll('h1,h2,h3,h4,h5,h6')].filter(h => !h.closest('.modal')).map(h => +h.tagName[1]);
    r.h1 = hs.filter(x => x === 1).length; for (let k = 1; k < hs.length; k++) if (hs[k] - hs[k - 1] > 1) r.hskip++;

    if (!LITE) {
      // tables
      for (const t of stage.querySelectorAll('table.zmc-table')) {
        r.tables++;
        try {
          const tb = t.tBodies[0]; const before = dataRows(tb).map(tr => tr.cells[0].textContent.trim()).sort().join('|');
          const heads = [...t.tHead.rows[t.tHead.rows.length - 1].cells];
          for (const th of heads.filter(h => h.classList.contains('zmc-sortable'))) { th.click(); th.click(); }
          const after = dataRows(tb).map(tr => tr.cells[0].textContent.trim()).sort().join('|');
          if (before !== after) r.tableBad.push('sort lost/dup rows');
          const bar = t.closest('.table-responsive, .zmc-table-scroll') ? (t.closest('.table-responsive, .zmc-table-scroll').previousElementSibling) : t.previousElementSibling;
          if (bar && bar.classList && bar.classList.contains('zmc-tabletools')) {
            const inp = bar.querySelector('input'); const first = (dataRows(tb)[0].textContent.trim().split(/\s+/).find(w => w.length > 2) || '').toLowerCase();
            if (first) { inp.value = first; inp.dispatchEvent(new Event('input', { bubbles: true })); const vis = dataRows(tb).filter(tr => !tr.hidden).length; if (vis < 1) r.tableBad.push('filter hides everything for "' + first + '"'); inp.value = ''; inp.dispatchEvent(new Event('input', { bubbles: true })); if (dataRows(tb).some(tr => tr.hidden)) r.tableBad.push('filter clear leaves hidden rows'); }
            let csv = ''; const OB = window.Blob, OC = HTMLAnchorElement.prototype.click; window.Blob = function (p) { csv = p.join(''); return new OB(p); }; HTMLAnchorElement.prototype.click = function () { };
            bar.querySelector('[data-act="csv"]').click(); window.Blob = OB; HTMLAnchorElement.prototype.click = OC;
            const lines = csv.replace('\ufeff', '').split('\r\n').filter(Boolean); const rows = dataRows(tb).filter(tr => !tr.hidden).length;
            const hdr = csvLine(lines[0] || '').length;
            if (lines.length !== rows + 1) r.tableBad.push('csv rows ' + (lines.length - 1) + ' vs ' + rows);
            else if (lines.slice(1).some(l => csvLine(l).length !== hdr)) r.tableBad.push('csv column count mismatch');
          }
        } catch (e) { r.tableBad.push('EXC ' + String(e.message).slice(0, 60)); }
      }
      // confirm dialogs on delete forms
      for (const f of stage.querySelectorAll('form')) {
        const m = f.querySelector('input[name="_method"]'); const act = (f.getAttribute('action') || '').toLowerCase();
        if (!((m && /^delete$/i.test(m.value)) || /\/(delete|destroy|remove)(\/|$|\?)/.test(act)) || f.getAttribute('onsubmit') || f.hasAttribute('data-no-confirm')) continue;
        r.confirms++; const tgt = f.getAttribute('target'); f.setAttribute('target', '_blank'); f.removeAttribute('data-zmc-confirmed');
        try { f.requestSubmit(); } catch (e) { }
        await sleep(450);
        if (!document.querySelector('#zmcConfirm.show')) r.confirmBad++;
        const c = document.querySelector('#zmcConfirm [data-bs-dismiss=modal].btn-light'); if (c) c.click(); await sleep(350);
        document.querySelectorAll('.modal-backdrop').forEach(n => n.remove()); document.body.classList.remove('modal-open');
        tgt === null ? f.removeAttribute('target') : f.setAttribute('target', tgt);
      }
      // dropdown stability (Popper owns the transform)
      const dd = stage.querySelector('[data-bs-toggle="dropdown"]');
      if (dd && dd.offsetParent !== null) {
        r.dds++;
        try { dd.scrollIntoView({ block: 'center', behavior: 'instant' }); await sleep(120); dd.click(); await sleep(30); const menu = dd.parentElement.querySelector('.dropdown-menu'); const a = menu.getBoundingClientRect(); await sleep(450); const b = menu.getBoundingClientRect();
          if (Math.abs(a.top - b.top) > 3 || Math.abs(a.left - b.left) > 3) r.ddBad++; dd.click(); } catch (e) { r.ddBad++; }
      }
      // required fields: empty required input shows inline message on blur
      const req = stage.querySelector('form:not([novalidate]) [required]:not([type=hidden]):not([disabled])');
      if (req && req.offsetParent !== null && !req.value && req.willValidate) { r.reqs++; req.dispatchEvent(new Event('focusout', { bubbles: true })); await sleep(30); if (!(req.classList.contains('is-invalid') && req.getAttribute('aria-invalid') === 'true')) r.reqBad++; }
    }

    // modals: on top, Esc closes, scroll lock released
    for (const m of [...stage.querySelectorAll('.modal')]) {
      r.modals++;
      try {
        const inst = bootstrap.Modal.getOrCreateInstance(m); inst.show(); await sleep(450);
        const c = m.querySelector('.modal-content') || m; const b = c.getBoundingClientRect();
        const hit = document.elementFromPoint(Math.max(1, Math.min(b.left + b.width / 2, innerWidth - 1)), Math.max(1, Math.min(b.top + 30, innerHeight - 5)));
        if (!(hit && m.contains(hit))) r.modalBad.push('covered');
        if (!LITE) { m.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape', bubbles: true })); await sleep(800); if (m.classList.contains('show') && !m.dataset.bsBackdrop) { inst.hide(); await sleep(350); r.modalBad.push('esc-no-close'); } }
        else { inst.hide(); await sleep(350); }
        document.querySelectorAll('.modal-backdrop').forEach(n => n.remove());
        if (document.body.classList.contains('modal-open')) { r.modalBad.push('scroll-lock'); document.body.classList.remove('modal-open'); }
        document.body.style.removeProperty('overflow'); document.body.style.removeProperty('padding-right');
      } catch (e) { r.modalBad.push('err ' + String(e.message).slice(0, 40)); }
    }

    // axe
    if (!LITE) {
      try {
        const runAxe = () => axe.run(stage, { runOnly: { type: 'tag', values: ['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'best-practice'] }, resultTypes: ['violations'], rules: Object.fromEntries(AXE_OFF.map(id => [id, { enabled: false }])) });
        let res; try { res = await runAxe(); } catch (e1) { await sleep(600); res = await runAxe(); }
        r.axe = res.violations.map(v => ({ id: v.id, impact: v.impact, n: v.nodes.length, html: String((v.nodes[0] || {}).html || '').slice(0, 150), why: String(((v.nodes[0] || {}).any || [{}])[0].message || '').slice(0, 90) }));
      } catch (e) { r.axe = [{ id: 'axe-error', impact: 'n/a', n: 1 }]; }
    }
    r.errors = errs.slice(0, 2);
    report.push(r);
  }
  const out = JSON.stringify(report);
  document.getElementById('results').textContent = out;
  if (window.parent !== window) window.parent.postMessage({ type: 'audit-done', data: out }, '*');
  document.title = 'DONE';
})();
</script></body></html>'''
page = (page.replace("__THEME__", theme).replace("__LITE__", "true" if lite else "false")
        .replace("__CSS__", css).replace("__UXCSS__", uxcss).replace("__DARKCSS__", darkcss).replace("__UIBLOCK__", uiblock).replace("__UXJS__", uxjs))
name = f"audit_{theme}{'_lite' if lite else ''}.html"
open(f"{S}/srv/{name}", "w", encoding="utf-8").write(page)

wrap = f'''<!doctype html><html><body><iframe id="f" src="/{name}" style="width:390px;height:900px;border:0"></iframe><pre id="results" style="display:none"></pre>
<script>window.addEventListener('message', e => {{ if (e.data && e.data.type === 'audit-done') {{ document.getElementById('results').textContent = e.data.data; document.title = 'DONE'; }} }});</script></body></html>'''
open(f"{S}/srv/audit_phone_{theme}.html", "w", encoding="utf-8").write(wrap)
print("wrote", name)
