// Browser checks against the APPROXIMATE static preview (see tools/preview_static.py). Needs Node + `npm i playwright` (and `npx playwright install chromium`).
// Run from the project root:  python3 tools/preview_static.py /tmp/preview && node tools/browser/shoot.js
// These test CSS/JS behaviour with sample data. They do not test PHP, and the original site is not compared.
const path = require('path');
const ROOT = path.resolve(__dirname, '..', '..');
const PREVIEW = process.env.PREVIEW_DIR || '/tmp/preview';
const { chromium } = require('playwright');
const fs = require('fs');
(async () => {
  const b = await chromium.launch();
  const files = fs.readdirSync(PREVIEW).filter(f => f.endsWith('.html')).sort();
  const widths = [375, 768, 1280];
  const rows = [];
  for (const w of widths) {
    const ctx = await b.newContext({ viewport: { width: w, height: 900 } });
    for (const f of files) {
      const p = await ctx.newPage();
      const errs = [];
      p.on('console', m => { if (m.type() === 'error') errs.push(m.text()); });
      p.on('pageerror', e => errs.push(String(e)));
      await p.goto('file://' + PREVIEW + '/' + f);
      await p.waitForTimeout(60);
      const m = await p.evaluate(() => {
        const de = document.documentElement;
        let widest = null, wmax = 0;
        document.querySelectorAll('body *').forEach(el => {
          const r = el.getBoundingClientRect();
          if (r.right > window.innerWidth + 1 && r.width > 0 && getComputedStyle(el).position !== 'fixed') {
            const sc = el.closest('.table-wrap, .sr-only');
            if (!sc && r.right > wmax) { wmax = r.right; widest = el.tagName + '.' + (el.className.baseVal ?? el.className); }
          }
        });
        return { sw: de.scrollWidth, iw: window.innerWidth, widest, h: de.scrollHeight };
      });
      rows.push({ f, w, ...m, errs: errs.length });
      if (!f.includes('dark') || w === 1280) await p.screenshot({ path: `${process.env.SHOTS_DIR || '/tmp/shots'}/${f.replace('.html','')}-${w}.png`, fullPage: true });
      await p.close();
    }
    await ctx.close();
  }
  await b.close();
  const bad = rows.filter(r => r.sw > r.iw + 1 || r.widest || r.errs);
  console.log('pages x widths checked:', rows.length);
  console.log('with overflow/errors:', bad.length);
  bad.slice(0, 30).forEach(r => console.log(JSON.stringify(r)));
})();
