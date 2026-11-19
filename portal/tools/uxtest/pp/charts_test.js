const puppeteer = require('puppeteer-core');
const S = process.argv[2];
(async () => {
  const b = await puppeteer.launch({ executablePath: 'C:/Program Files/Google/Chrome/Application/chrome.exe', headless: 'new', args: ['--no-sandbox'] });
  const p = await b.newPage(); await p.setViewport({ width: 1300, height: 700 });
  p.on('pageerror', e => console.log('PAGEERROR', String(e).slice(0, 200)));
  await p.goto('http://127.0.0.1:8767/charts_test.html'); await new Promise(r => setTimeout(r, 1800));
  const read = () => p.evaluate(() => {
    const apexText = document.querySelector('.apexcharts-xaxis-label, .apexcharts-text');
    const fill = apexText ? getComputedStyle(apexText).fill : null;
    const tick = window.__cjs && window.__cjs.scales.x.options.ticks.color;
    const grid = window.__cjs && window.__cjs.scales.y.options.grid.color;
    const legend = window.__cjs && window.__cjs.legend && window.__cjs.legend.options.labels.color;
    return { theme: document.documentElement.getAttribute('data-bs-theme'), apexAxisFill: fill, chartjsTick: tick, chartjsGrid: grid, chartjsLegend: legend, ApexDefaultFore: window.Apex && window.Apex.chart && window.Apex.chart.foreColor };
  });
  const lum = c => { const m = String(c).match(/\d+/g); if (!m) return null; const a = m.slice(0, 3).map(v => { v /= 255; return v <= .03928 ? v / 12.92 : Math.pow((v + .055) / 1.055, 2.4); }); return .2126 * a[0] + .7152 * a[1] + .0722 * a[2]; };
  const hexToRgb = h => { const v = parseInt(String(h).replace('#', ''), 16); return `rgb(${v >> 16 & 255}, ${v >> 8 & 255}, ${v & 255})`; };
  const ratio = (fg, bg) => { const a = lum(fg), c = lum(bg); return ((Math.max(a, c) + .05) / (Math.min(a, c) + .05)).toFixed(1); };
  const dark = await read(); await p.screenshot({ path: S + '/charts_dark.png' });
  console.log('DARK ', JSON.stringify(dark));
  console.log('  axis label contrast on dark card (#1e293b):', ratio(dark.apexAxisFill, 'rgb(30,41,59)'), '| chart.js tick:', ratio(hexToRgb(dark.chartjsTick), 'rgb(30,41,59)'));
  await p.evaluate(() => document.documentElement.setAttribute('data-bs-theme', 'light')); await new Promise(r => setTimeout(r, 900));
  const light = await read(); await p.screenshot({ path: S + '/charts_light.png' });
  console.log('LIGHT', JSON.stringify(light));
  console.log('  axis label contrast on white:', ratio(light.apexAxisFill, 'rgb(255,255,255)'), '| chart.js tick:', ratio(hexToRgb(light.chartjsTick), 'rgb(255,255,255)'));
  await p.evaluate(() => window.dispatchEvent(new CustomEvent('zmc:theme-changed', { detail: 'dark' }))); await p.evaluate(() => document.documentElement.setAttribute('data-bs-theme', 'dark')); await new Promise(r => setTimeout(r, 900));
  const back = await read(); console.log('BACK TO DARK', JSON.stringify(back));
  await b.close();
})().catch(e => { console.error('ERR', e); process.exit(1); });
