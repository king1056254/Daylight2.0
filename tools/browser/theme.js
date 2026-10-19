// Browser checks against the APPROXIMATE static preview (see tools/preview_static.py). Needs Node + `npm i playwright` (and `npx playwright install chromium`).
// Run from the project root:  python3 tools/preview_static.py /tmp/preview && node tools/browser/theme.js
// These test CSS/JS behaviour with sample data. They do not test PHP, and the original site is not compared.
const path = require('path');
const ROOT = path.resolve(__dirname, '..', '..');
const PREVIEW = process.env.PREVIEW_DIR || '/tmp/preview';
const { chromium } = require('playwright');
require('fs').writeFileSync(require('os').tmpdir() + '/daylight-themeonly.html', '<!doctype html><html><head><script src="file://' + ROOT + '/public/js/theme-init.js"></script></head><body>x</body></html>');
(async () => {
  const b = await chromium.launch();
  let pass = 0, fail = 0;
  const t = async (name, stored, scheme, expectDark) => {
    const c = await b.newContext({ colorScheme: scheme });
    const p = await c.newPage();
    if (stored) await p.addInitScript(v => localStorage.setItem('daylight-theme', v), stored);
    await p.goto('file://' + require('fs').realpathSync(require('os').tmpdir()) + '/daylight-themeonly.html');
    const dark = await p.evaluate(() => document.documentElement.classList.contains('dark'));
    const js = await p.evaluate(() => document.documentElement.classList.contains('js'));
    const good = dark === expectDark && js;
    good ? pass++ : fail++;
    console.log((good ? '  PASS  ' : '  FAIL  ') + name);
    await c.close();
  };
  await t('stored dark wins over OS light', 'dark', 'light', true);
  await t('stored light wins over OS dark', 'light', 'dark', false);
  await t('no stored value + OS light -> light', null, 'light', false);
  await t('no stored value + OS dark -> dark (original default)', null, 'dark', true);
  await t('junk stored value is ignored', 'purple', 'dark', true);
  await b.close();
  console.log(`theme-init: ${pass} passed, ${fail} failed`);
  process.exit(fail ? 1 : 0);
})();
