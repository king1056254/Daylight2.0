// Browser tests for the chat widget, FAQ search and legal pages against the preview harness (see tools/preview_static.py).
// The /assistant/message endpoint is mocked with Playwright routes, so this tests the widget's behaviour, not the PHP backend.
const path = require('path');
const PREVIEW = process.env.PREVIEW_DIR || '/tmp/preview';
const { chromium } = require('playwright');
let pass = 0, fail = 0;
const ok = (name, cond, extra = '') => { (cond ? pass++ : fail++); console.log((cond ? '  PASS  ' : '  FAIL  ') + name + (cond ? '' : '   ' + extra)); };
const ENDPOINT = 'http://preview.test/assistant/message';

(async () => {
  const b = await chromium.launch();
  const open = async (file, w = 1280, scheme = 'light') => {
    const c = await b.newContext({ viewport: { width: w, height: 800 }, colorScheme: scheme });
    const p = await c.newPage();
    p.errs = []; p.dialogs = []; p.bodies = [];
    p.on('pageerror', e => p.errs.push(String(e)));
    p.on('dialog', d => { p.dialogs.push(d.message()); d.dismiss(); });
    await p.goto('file://' + PREVIEW + '/' + file);
    await p.evaluate(u => { const el = document.getElementById('daylight-assistant'); if (el) { el.setAttribute('data-endpoint', u); el.setAttribute('data-csrf', 'tok123'); } }, ENDPOINT);
    return p;
  };
  const mock = async (p, handler) => p.route('**/assistant/message', async route => { p.bodies.push(route.request().postData() || ''); await handler(route); });
  const reply = (json, status = 200, delay = 0) => async route => { if (delay) await new Promise(r => setTimeout(r, delay)); await route.fulfill({ status, contentType: 'application/json', headers: { 'access-control-allow-origin': '*' }, body: JSON.stringify(json) }); };

  // ---- open / close / focus ----
  let p = await open('home-light.html');
  ok('launcher visible, panel hidden at first', (await p.isVisible('.assistant-launch')) && !(await p.isVisible('.assistant-panel')));
  await p.click('.assistant-launch');
  ok('panel opens', await p.isVisible('.assistant-panel'));
  ok('launcher hides while open', !(await p.isVisible('.assistant-launch')));
  ok('launcher reports aria-expanded', (await p.getAttribute('.assistant-launch', 'aria-expanded')) === 'true');
  ok('greeting shown', (await p.textContent('.assistant-log')).includes("Daylight's automated assistant"));
  ok('focus moves to the input', await p.evaluate(() => document.activeElement && document.activeElement.id === 'assistant-input'));
  ok('honest disclosure is visible', (await p.textContent('.assistant-note')).includes('Automated assistant, not a person'));
  await p.keyboard.press('Escape');
  ok('Escape closes the panel', !(await p.isVisible('.assistant-panel')));
  ok('focus returns to the launcher', await p.evaluate(() => document.activeElement.classList.contains('assistant-launch')));
  await p.click('.assistant-launch'); await p.click('.assistant-head .icon-btn');
  ok('close button works', !(await p.isVisible('.assistant-panel')));

  // ---- sending, rendering, safety ----
  p = await open('home-light.html');
  await mock(p, reply({ answer: 'Websites take 2–4 weeks.\nSecond line.', links: [{ label: 'FAQ', url: '/faq' }, { label: 'bad js', url: 'javascript:alert(1)' }, { label: 'evil', url: '//evil.example/x' }, { label: 'ok2', url: '/contact' }], mode: 'faq', matched: true }));
  await p.click('.assistant-launch');
  await p.evaluate(() => document.querySelectorAll('[data-assistant-suggest]')[1].setAttribute('data-assistant-suggest', 'How long does a website take?'));
  await p.click('[data-assistant-suggest] >> nth=1');
  await p.waitForSelector('.assistant-msg.from-bot .assistant-links');
  const logText = await p.textContent('.assistant-log');
  ok('suggestion sends the chip text as the user message', logText.includes('How long does a website take?'));
  ok('reply text rendered', logText.includes('Websites take 2–4 weeks.'));
  ok('only safe internal links become anchors', (await p.$$eval('.assistant-links a', as => as.map(a => a.getAttribute('href')))).join(',') === '/faq,/contact');
  ok('suggestions hide after the first question', !(await p.isVisible('.assistant-suggest')));
  const first = new URLSearchParams(p.bodies[0]);
  ok('request carries message, csrf token and empty history', first.get('message') === 'How long does a website take?' && first.get('_csrf') === 'tok123' && first.get('history') === '[]');
  ok('line breaks in replies are kept', await p.$eval('.assistant-msg.from-bot:last-child .assistant-msg-text', el => getComputedStyle(el).whiteSpace === 'pre-line'));
  await p.fill('#assistant-input', 'And support?');
  await p.press('#assistant-input', 'Enter');
  await p.waitForFunction(() => document.querySelectorAll('.assistant-msg.from-bot').length >= 3);
  const second = new URLSearchParams(p.bodies[1]);
  const hist = JSON.parse(second.get('history'));
  ok('second request includes the earlier turns', hist.length === 2 && hist[0].role === 'user' && hist[1].role === 'assistant', JSON.stringify(hist));

  // ---- markup in replies is shown as text ----
  p = await open('home-light.html');
  await mock(p, reply({ answer: '<img src=x onerror="alert(1)"><script>alert(2)</script> **bold**', links: [], mode: 'faq', matched: true }));
  await p.click('.assistant-launch'); await p.fill('#assistant-input', 'test'); await p.press('#assistant-input', 'Enter');
  await p.waitForFunction(() => document.querySelectorAll('.assistant-msg.from-bot').length >= 2);
  ok('HTML in a reply is not interpreted', (await p.$$('.assistant-log img, .assistant-log script')).length === 0);
  ok('it is displayed literally instead', (await p.textContent('.assistant-log')).includes('<img src=x onerror="alert(1)">'));
  ok('no dialog or script error fired', p.dialogs.length === 0 && p.errs.length === 0);

  // ---- errors ----
  p = await open('home-light.html');
  await mock(p, reply({ error: 'You are sending messages quickly. Please wait a moment and try again.' }, 429));
  await p.click('.assistant-launch'); await p.fill('#assistant-input', 'hi'); await p.press('#assistant-input', 'Enter');
  await p.waitForSelector('.assistant-msg.is-error');
  ok('server error message is shown as an error bubble', (await p.textContent('.assistant-msg.is-error')).includes('sending messages quickly'));
  ok('sending is possible again after an error', !(await p.isDisabled('.assistant-send')));
  p = await open('home-light.html');
  await p.route('**/assistant/message', r => r.abort());
  await p.click('.assistant-launch'); await p.fill('#assistant-input', 'hi'); await p.press('#assistant-input', 'Enter');
  await p.waitForSelector('.assistant-msg.is-error');
  ok('network failure gives a friendly message', (await p.textContent('.assistant-msg.is-error')).includes("couldn't reach"));

  // ---- busy state, limits, reset, keyboard ----
  p = await open('home-light.html');
  await mock(p, reply({ answer: 'ok', links: [], mode: 'faq', matched: true }, 200, 400));
  await p.click('.assistant-launch'); await p.fill('#assistant-input', 'slow one'); await p.press('#assistant-input', 'Enter');
  ok('send button is disabled while waiting', await p.isDisabled('.assistant-send'));
  ok('typing indicator is announced', (await p.textContent('.assistant-log')).includes('The assistant is typing'));
  await p.waitForFunction(() => !document.querySelector('.assistant-typing'));
  ok('send button re-enabled afterwards', !(await p.isDisabled('.assistant-send')));
  const before = p.bodies.length;
  await p.evaluate(() => { const i = document.getElementById('assistant-input'); i.value = 'x'.repeat(501); });
  await p.press('#assistant-input', 'Enter');
  ok('over-long question is refused locally with an explanation', (await p.textContent('.assistant-log')).includes('under 500 characters') && p.bodies.length === before);
  ok('textarea limits typing to 500 characters', (await p.getAttribute('#assistant-input', 'maxlength')) === '500');
  await p.fill('#assistant-input', 'line one');
  await p.press('#assistant-input', 'Shift+Enter');
  ok('Shift+Enter does not send', p.bodies.length === before);
  await p.fill('#assistant-input', '');
  await p.click('[data-assistant-reset]');
  ok('New chat clears the conversation and shows suggestions again', (await p.$$('.assistant-msg')).length === 1 && await p.isVisible('.assistant-suggest'));

  // ---- layout on phones, dark theme, the id regression on the privacy page ----
  p = await open('home-light.html', 375);
  await p.click('.assistant-launch');
  const box = await p.$eval('.assistant-panel', el => { const r = el.getBoundingClientRect(); return { l: r.left, r: r.right, w: r.width, b: r.bottom, t: r.top }; });
  ok('panel fits a 375px phone', box.l >= 0 && box.r <= 375 && box.t >= 0 && box.b <= 800, JSON.stringify(box));
  ok('page does not scroll sideways with the panel open', await p.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth));
  p = await open('privacy-light.html');
  ok('privacy page has its own id="assistant" section AND a working widget', (await p.$$('#assistant')).length === 1 && (await p.$$('#daylight-assistant')).length === 1);
  await p.click('.assistant-launch');
  ok('widget opens on the privacy page', await p.isVisible('.assistant-panel'));
  p = await open('home-dark.html', 1280, 'dark');
  await p.click('.assistant-launch');
  const bg = await p.$eval('.assistant-panel', el => getComputedStyle(el).backgroundColor);
  ok('dark theme panel is dark', !/rgb\(2[0-9]{2}, 2[0-9]{2}, 2[0-9]{2}\)/.test(bg) && bg !== 'rgba(0, 0, 0, 0)', bg);
  p = await open('portal-dashboard-light.html');
  ok('widget is present in the client portal', await p.isVisible('.assistant-launch'));
  p = await open('admin-list-light.html');
  ok('widget is absent in the admin area', (await p.$$('#daylight-assistant')).length === 0);

  // ---- FAQ page: search and categories ----
  p = await open('faq-light.html', 768);
  ok('FAQ shows 42 questions in 8 categories', (await p.$$('[data-faq-item]')).length === 42 && (await p.$$('[data-faq-group]')).length === 8);
  ok('search box is available with JavaScript', await p.isVisible('#faq-search'));
  await p.fill('#faq-search', 'portal');
  const visible = await p.$$eval('[data-faq-item]:not([hidden])', els => els.length);
  ok('searching hides non-matching questions', visible > 0 && visible < 42, 'visible=' + visible);
  ok('groups with no match are hidden', (await p.$$eval('[data-faq-group]:not([hidden])', els => els.length)) < 8);
  await p.fill('#faq-search', 'portal password');
  ok('every typed word must match', (await p.$$eval('[data-faq-item]:not([hidden])', els => els.length)) >= 1 && (await p.$$eval('[data-faq-item]:not([hidden])', els => els.length)) < visible);
  await p.fill('#faq-search', 'zzzzqqqq');
  ok('no-match message appears', await p.isVisible('[data-faq-empty]'));
  await p.fill('#faq-search', '');
  ok('clearing the search restores everything', (await p.$$eval('[data-faq-item]:not([hidden])', els => els.length)) === 42 && !(await p.isVisible('[data-faq-empty]')));
  ok('category chips link to existing groups', await p.$$eval('.faq-cats a', as => as.every(a => document.querySelector(a.getAttribute('href')))));
  await p.click('details[data-faq-item] >> nth=0 >> summary'); await p.click('details[data-faq-item] >> nth=20 >> summary');
  ok('only one answer open at a time, across categories', (await p.$$eval('details[open]', els => els.length)) === 1);

  // ---- legal pages ----
  for (const f of ['privacy', 'terms']) {
    for (const w of [375, 1280]) {
      p = await open(f + '-light.html', w);
      const toc = await p.$$eval('.legal-toc a', as => as.map(a => a.getAttribute('href')));
      const targets = await p.evaluate(h => h.every(x => document.querySelector(x)), toc);
      ok(`${f} @${w}: contents list matches the sections and every anchor resolves`, toc.length === (await p.$$('.legal-section')).length && targets, `toc=${toc.length}`);
      ok(`${f} @${w}: no horizontal scroll`, await p.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth));
    }
    p = await open(f + '-light.html', 375);
    ok(`${f}: external links are safe`, await p.$$eval('a[target="_blank"]', as => as.every(a => /noopener/.test(a.rel))));
    ok(`${f}: sections are numbered`, (await p.textContent('.legal-section:first-of-type .legal-num')).trim() === '1.');
  }
  p = await open('privacy-light.html', 375);
  ok('policy tables stack into cards on phones (no horizontal scrolling needed)', await p.$$eval('.legal-table td', tds => tds.every(td => getComputedStyle(td).display === 'block' && td.getAttribute('data-label'))));
  ok('stacked cells show their column name', await p.$eval('.legal-table td', td => getComputedStyle(td, '::before').content.length > 2));
  p = await open('privacy-light.html', 1280);
  ok('tables stay real tables on desktop', await p.$$eval('.legal-table td', tds => tds.every(td => getComputedStyle(td).display === 'table-cell')));

  await b.close();
  console.log(`\nassistant / FAQ / legal browser tests (preview harness): ${pass} passed, ${fail} failed`);
  process.exit(fail ? 1 : 0);
})();
