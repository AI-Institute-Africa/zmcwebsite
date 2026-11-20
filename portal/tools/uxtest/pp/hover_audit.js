// Hover / focus audit with REAL mouse hover (the main audit cannot see :hover).
// usage: node hover_audit.js <light|dark> <port> <view-indices comma list | all> <out.json> [max-views]
// Needs shots.py build_page(<theme>) to have written srv/shot_<theme>.html and the srv folder to be served on <port>.
// Reports, per view: hover contrast failures (text unreadable while hovering) and "dead" hovers
// (an element matches a :hover CSS rule but nothing visibly changes).
const puppeteer = require('puppeteer-core');
const fs = require('fs');
const sleep = ms => new Promise(r => setTimeout(r, ms));
const [theme, port, which, out, maxViews] = process.argv.slice(2);

const PICK = `
(() => {
  const stage = document.getElementById('stage');
  const groups = [
    ['btn', 'a.btn, button.btn'],
    ['row', 'table tbody tr'],
    ['list', '.list-group-item-action'],
    ['nav', '.nav-link, .nav-tabs button, .nav-pills button'],
    ['link', 'a:not(.btn):not(.nav-link):not(.list-group-item-action)'],
  ];
  let id = 0; const picked = [];
  for (const [kind, sel] of groups) {
    let n = 0;
    for (const el of stage.querySelectorAll(sel)) {
      if (n >= 3) break;
      if (el.closest('.modal:not(.show), [hidden], template')) continue;
      if (el.classList.contains('active') || el.getAttribute('aria-selected') === 'true' || el.getAttribute('aria-current')) continue;  // current item: no hover change expected
      const r = el.getBoundingClientRect(); if (r.width < 12 || r.height < 12) continue;
      const cs = getComputedStyle(el); if (cs.visibility === 'hidden' || cs.display === 'none' || cs.pointerEvents === 'none') continue;
      el.setAttribute('data-hv', String(id)); picked.push({ id, kind }); id++; n++;
    }
  }
  return picked;
})()`;

const SNAP = `
(el => {
  const lin = v => { v /= 255; return v <= .03928 ? v / 12.92 : Math.pow((v + .055) / 1.055, 2.4); };
  const lum = c => .2126 * lin(c.r) + .7152 * lin(c.g) + .0722 * lin(c.b);
  const parse = s => { const m = (s || '').match(/rgba?\\(([^)]+)\\)/); if (!m) return null; const p = m[1].split(/[ ,\\/]+/).map(Number); return { r: p[0], g: p[1], b: p[2], a: p.length > 3 ? p[3] : 1 }; };
  const THEME = document.documentElement.getAttribute('data-bs-theme');
  function bgOf(e0) {
    const layers = []; let img = false;
    for (let e = e0; e; e = e.parentElement) {
      const cs = getComputedStyle(e);
      if (cs.backgroundImage && cs.backgroundImage !== 'none') { img = true; break; }
      const c = parse(cs.backgroundColor); if (c && c.a > 0) { layers.push(c); if (c.a >= 1) break; }
    }
    if (img) return null;
    let o = THEME === 'dark' ? { r: 15, g: 23, b: 42 } : { r: 247, g: 249, b: 252 };
    if (layers.length && layers[layers.length - 1].a >= 1) o = { r: 0, g: 0, b: 0 };
    for (let i = layers.length - 1; i >= 0; i--) { const l = layers[i]; o = { r: l.r * l.a + o.r * (1 - l.a), g: l.g * l.a + o.g * (1 - l.a), b: l.b * l.a + o.b * (1 - l.a) }; }
    return o;
  }
  // element that actually carries the text
  let t = el;
  if (![...el.childNodes].some(n => n.nodeType === 3 && n.textContent.trim().length > 1)) {
    t = [...el.querySelectorAll('*')].find(x => [...x.childNodes].some(n => n.nodeType === 3 && n.textContent.trim().length > 1)) || el;
  }
  const cs = getComputedStyle(t), ecs = getComputedStyle(el);
  const fg = parse(cs.color), bg = bgOf(t);
  let ratio = null;
  if (fg && bg) {
    const mix = { r: fg.r * fg.a + bg.r * (1 - fg.a), g: fg.g * fg.a + bg.g * (1 - fg.a), b: fg.b * fg.a + bg.b * (1 - fg.a) };
    const L1 = lum(mix), L2 = lum(bg); ratio = +((Math.max(L1, L2) + .05) / (Math.min(L1, L2) + .05)).toFixed(2);
  }
  return { ratio, color: cs.color, bg: bg ? [bg.r, bg.g, bg.b].map(Math.round).join(',') : 'img',
    sig: [ecs.backgroundColor, ecs.color, ecs.borderTopColor, ecs.boxShadow, ecs.transform, ecs.textDecorationLine, ecs.opacity, cs.color, cs.backgroundColor].join('|') + '||' + [...el.querySelectorAll('*')].slice(0, 12).map(d => { const c = getComputedStyle(d); return c.backgroundColor + c.color + c.boxShadow + c.transform + c.borderTopColor; }).join('|'),
    text: (t.textContent || '').trim().slice(0, 22), tag: el.tagName.toLowerCase() + '.' + String(el.className).trim().replace(/\\s+/g, '.').slice(0, 40) };
})`;

const HOVER_BASES = `
(() => {
  const bases = [];
  const walk = rules => { for (const r of rules) {
    if (r.cssRules && !r.selectorText) { try { walk(r.cssRules); } catch (e) {} continue; }
    if (!r.selectorText || r.selectorText.indexOf(':hover') < 0) continue;
    for (const part of r.selectorText.split(',')) { if (part.indexOf(':hover') < 0 || part.indexOf('::') >= 0) continue; bases.push(part.replace(/:hover/g, '').trim()); }
  } };
  for (const ss of document.styleSheets) { try { walk(ss.cssRules); } catch (e) {} }
  return bases;
})()`;

(async () => {
  const manifest = JSON.parse(fs.readFileSync(process.env.UXTEST_WORK + '/srv/frag/manifest.json', 'utf8'));
  let idx = which === 'all' ? manifest.map((_, i) => i) : which.split(',').map(Number);
  if (maxViews) idx = idx.slice(0, +maxViews);
  const browser = await puppeteer.launch({ executablePath: 'C:/Program Files/Google/Chrome/Application/chrome.exe', headless: 'new', args: ['--no-sandbox'], protocolTimeout: 600000 });
  const results = [];
  for (const i of idx) {
    const page = await browser.newPage(); await page.setViewport({ width: 1366, height: 900 });
    const r = { v: manifest[i], tested: 0, lowHover: [], deadHover: [] };
    try {
      await page.goto(`http://127.0.0.1:${port}/shot_${theme}.html?i=${i}`, { waitUntil: 'load', timeout: 60000 });
      await page.waitForFunction('document.title==="READY"', { timeout: 30000 });
      await sleep(400);
      const picked = await page.evaluate(PICK);
      const bases = await page.evaluate(HOVER_BASES);
      for (const { id, kind } of picked) {
        const h = await page.$(`[data-hv="${id}"]`); if (!h) continue;
        await page.mouse.move(2, 2); await sleep(120);
        const rest = await h.evaluate(eval(SNAP));
        try { await h.hover(); } catch (e) { continue; }
        await sleep(300);
        const hov = await h.evaluate(eval(SNAP));
        r.tested++;
        const note = `${kind} ${rest.tag} "${rest.text}"`;
        if (hov.ratio !== null && (hov.ratio < 3 || (hov.ratio < 4.5 && rest.ratio !== null && rest.ratio >= 4.5))) r.lowHover.push(`${hov.ratio} (rest ${rest.ratio}) ${note} fg=${hov.color} bg=${hov.bg}`);
        if (rest.sig === hov.sig) {
          const has = await h.evaluate((el, b) => b.some(s => { try { return el.matches(s); } catch (e) { return false; } }), bases);
          if (has) r.deadHover.push(note);
        }
      }
    } catch (e) { r.error = String(e).slice(0, 120); }
    results.push(r); await page.close();
  }
  fs.writeFileSync(out, JSON.stringify(results));
  const low = results.filter(x => x.lowHover.length), dead = results.filter(x => x.deadHover.length);
  console.log(`hover audit ${theme}: ${results.length} views, ${results.reduce((a, x) => a + x.tested, 0)} elements | low-contrast hover on ${low.length} views | dead hover on ${dead.length} views`);
  await browser.close();
})().catch(e => { console.error('FAILED', String(e).slice(0, 300)); process.exit(1); });
