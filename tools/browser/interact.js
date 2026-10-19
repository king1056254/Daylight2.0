// Browser checks against the APPROXIMATE static preview (see tools/preview_static.py). Needs Node + `npm i playwright` (and `npx playwright install chromium`).
// Run from the project root:  python3 tools/preview_static.py /tmp/preview && node tools/browser/interact.js
// These test CSS/JS behaviour with sample data. They do not test PHP, and the original site is not compared.
const path = require('path');
const ROOT = path.resolve(__dirname, '..', '..');
const PREVIEW = process.env.PREVIEW_DIR || '/tmp/preview';
const { chromium } = require('playwright');
let pass = 0, fail = 0;
const ok = (name, cond, extra='') => { (cond ? pass++ : fail++); console.log((cond ? '  PASS  ' : '  FAIL  ') + name + (cond ? '' : '  ' + extra)); };
(async () => {
  const b = await chromium.launch();
  const open = async (file, w=375) => { const c = await b.newContext({ viewport: { width: w, height: 800 } }); const p = await c.newPage(); const errs=[]; p.on('pageerror', e=>errs.push(String(e))); await p.goto('file://' + PREVIEW + '/' + file); p._errs = errs; return p; };

  // 1 mobile menu
  let p = await open('home-light.html');
  ok('mobile nav hidden initially', !(await p.isVisible('#mobile-nav')));
  await p.click('.menu-btn');
  ok('menu button opens mobile nav', await p.isVisible('#mobile-nav'));
  ok('aria-expanded true', (await p.getAttribute('.menu-btn', 'aria-expanded')) === 'true');
  await p.click('.menu-btn');
  ok('menu button closes mobile nav', !(await p.isVisible('#mobile-nav')));
  await p.click('.menu-btn'); await p.click('#mobile-nav nav > a >> nth=0');
  ok('tapping a mobile link closes the menu', !(await p.isVisible('#mobile-nav')));
  ok('desktop nav hidden on phones', !(await p.isVisible('.main-nav')));
  ok('no JS errors (home)', p._errs.length === 0, p._errs.join('|'));

  // 2 desktop: nav visible, menu button hidden
  p = await open('home-light.html', 1280);
  ok('desktop nav visible', await p.isVisible('.main-nav'));
  ok('menu button hidden on desktop', !(await p.isVisible('.menu-btn')));

  // 3 theme toggle persists
  p = await open('home-light.html');
  ok('starts light', !(await p.evaluate(() => document.documentElement.classList.contains('dark'))));
  await p.click('[data-theme-toggle]');
  ok('toggle switches to dark', await p.evaluate(() => document.documentElement.classList.contains('dark')));
  ok('choice stored', (await p.evaluate(() => localStorage.getItem('daylight-theme'))) === 'dark');
  const bg = await p.evaluate(() => getComputedStyle(document.body).backgroundColor);
  ok('dark background is dark', /^(oklch|color\(|rgb)/.test(bg) && !/rgb\(2[0-9]{2}/.test(bg), bg);

  // 4 careers apply toggle
  p = await open('careers-light.html', 768);
  const form = p.locator('.apply-form').first();
  ok('apply form hidden by default (JS on)', !(await form.isVisible()));
  await p.click('[data-apply-toggle] >> nth=0');
  ok('Apply opens the form', await form.isVisible());
  ok('button label becomes Close', (await p.locator('[data-apply-toggle]').first().textContent()).trim() === 'Close');
  await p.click('[data-apply-toggle] >> nth=0');
  ok('Close hides the form', !(await form.isVisible()));

  // 5 FAQ accordion is exclusive
  p = await open('faq-light.html', 768);
  await p.click('.accordion details >> nth=0 >> summary');
  await p.click('.accordion details >> nth=1 >> summary');
  const opened = await p.evaluate(() => [...document.querySelectorAll('.accordion details')].filter(d => d.open).length);
  ok('only one FAQ open at a time', opened === 1, 'open=' + opened);

  // 6 double-submit protection + honeypot hidden
  p = await open('contact-light.html', 375);
  const info = await p.evaluate(() => {
    const f = document.querySelector('form.form-card'); const btn = f.querySelector('button[type=submit]');
    f.action = 'about:blank'; f.method = 'get';
    f.querySelectorAll('[required]').forEach(e => e.removeAttribute('required'));
    const hp = f.querySelector('input[name=website]'); const r = hp.getBoundingClientRect();
    f.requestSubmit(btn);
    return { disabled: btn.disabled, label: btn.textContent, hpTiny: r.width <= 2 || r.height <= 2 || getComputedStyle(hp.closest('.sr-only')).width === '1px' };
  });
  ok('submit button disables and says Sending…', info.disabled && info.label === 'Sending…', JSON.stringify(info));
  ok('honeypot is visually hidden', info.hpTiny);

  // 7 admin mobile sidebar + delete confirmation
  p = await open('admin-form-light.html', 375);
  ok('sidebar nav collapsed on phones', !(await p.isVisible('#sidebar-nav')));
  await p.click('.sidebar .menu-btn');
  ok('sidebar nav opens', await p.isVisible('#sidebar-nav'));
  p = await open('admin-form-light.html', 1280);
  ok('sidebar always visible on desktop', await p.isVisible('#sidebar-nav'));
  let dialogMsg = '';
  p.on('dialog', async d => { dialogMsg = d.message(); await d.dismiss(); });
  const navigated = await p.evaluate(() => { const f = document.querySelector('form[data-confirm]'); f.action = 'about:blank'; f.requestSubmit(); return 'submitted'; });
  await p.waitForTimeout(150);
  ok('delete asks for confirmation', /Delete this/.test(dialogMsg), dialogMsg);
  ok('cancelling the confirmation keeps the page', (await p.url()).includes('/admin-form'));

  await b.close();
  console.log(`\ninteraction tests (preview harness): ${pass} passed, ${fail} failed`);
  process.exit(fail ? 1 : 0);
})();
