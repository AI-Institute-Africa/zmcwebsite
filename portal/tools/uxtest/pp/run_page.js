// usage: node run_page.js <pagePath> <outJson> [phone]
// Opens an audit master page in real Chrome (real-time clock) and polls from Node until it reports DONE.
const puppeteer = require('puppeteer-core');
const fs = require('fs');
const sleep = ms => new Promise(r => setTimeout(r, ms));
(async () => {
  const [pagePath, out, mode] = process.argv.slice(2);
  const browser = await puppeteer.launch({
    executablePath: 'C:/Program Files/Google/Chrome/Application/chrome.exe',
    headless: 'new', args: ['--no-sandbox', '--disable-gpu'],
    protocolTimeout: 3600000,
  });
  const page = await browser.newPage();
  await page.setViewport(mode === 'phone' ? { width: 390, height: 844, isMobile: true, hasTouch: true, deviceScaleFactor: 1 } : { width: 1366, height: 900 });
  page.on('pageerror', e => console.log('PAGEERROR', String(e).slice(0, 160)));
  await page.goto('http://127.0.0.1:8767/' + pagePath, { waitUntil: 'load', timeout: 120000 });
  const started = Date.now();
  for (;;) {
    const title = await page.title();                 // short call every 5 s: never exceeds a protocol timeout
    if (title === 'DONE') break;
    if (Date.now() - started > 45 * 60 * 1000) throw new Error('audit exceeded 45 minutes');
    await sleep(5000);
  }
  const data = await page.$eval('#results', el => el.textContent);
  fs.writeFileSync(out, data);
  console.log('wrote', out, data.length, 'chars in', Math.round((Date.now() - started) / 1000), 's');
  await browser.close();
})().catch(e => { console.error('FAILED', String(e).slice(0, 300)); process.exit(1); });
