// آزمونِ دیداری پیش از انتشار: node tests/visual/visual.mjs http://127.0.0.1:8080
// در عرضِ گوشی و رایانه: بی اسکرولِ افقی، بی خطای جاوااسکریپت یا پی‌اچ‌پی، بخش‌های اصلی حاضر.
import { chromium } from 'playwright';
import { mkdirSync } from 'node:fs';

const BASE = (process.argv[2] || 'http://127.0.0.1:8080').replace(/\/$/, '');
const SHOTS = process.env.SHOTS_DIR || 'shots';
mkdirSync(SHOTS, { recursive: true });

const VIEWPORTS = [
  { name: 'mobile', width: 390, height: 844, isMobile: true },
  { name: 'desktop', width: 1366, height: 900, isMobile: false },
];

const PHP_ERR = /(Fatal error|Parse error|Warning:|Notice:|Deprecated:)/;

// صفحه‌ها و بخش‌هایی که باید حاضر باشند.
const PAGES = [
  { name: 'home', path: '/', must: ['body'] },
  { name: 'search', path: '/?s=%D8%A2%D8%B1%D8%A7%DB%8C%D8%B4', must: ['body'] },
  { name: 'updater', path: '/wp-admin/tools.php?page=golzari-updater', admin: true, must: ['.wrap h1'] },
  { name: 'brand', path: '/wp-admin/options-general.php?page=golzari-brand', admin: true, must: ['.wrap h1', 'input[name="golzari_labels[instagram]"]'] },
];

// هر ایرادی که با اسکرین‌شات خبر داده شد، این‌جا یک وارسیِ تازه می‌گیرد تا برنگردد.
// { name, path, viewport: 'mobile'|'desktop'|undefined, check: async (page) => 'پیامِ خطا' | null }
const REGRESSIONS = [
  {
    // ۱۴۰۵/۰۸/۰۸: نام‌های قالب و لینک‌های سایتِ نمونه روی صفحه‌ی اصلی مانده بود.
    name: 'brand-home',
    path: '/',
    check: async (page) => {
      const text = await page.locator('body').innerText();
      if (/زیبانو|راست چین/.test(text)) return 'نامِ قالب هنوز در متنِ صفحه است';
      if (!text.includes('golzari pmg')) return 'نامِ مجموعه در پایینِ صفحه نیامد';
      const hrefs = await page.$$eval('a[href]', (as) => as.map((a) => a.getAttribute('href')));
      if (hrefs.some((h) => /smdesign\.ir/.test(h))) return 'لینک به سایتِ نمونه‌ی قالب مانده';
      const insta = hrefs.filter((h) => /instagram\.com/.test(h));
      if (!insta.length || insta.some((h) => !h.includes('golzari_pmg'))) return 'لینکِ اینستاگرام به golzari_pmg نیست';
      if (!hrefs.includes('tel:09129178237')) return 'لینکِ تماس به شماره‌ی مجموعه نیست';
      // عکسِ بالای صفحه باید عکسِ مجموعه باشد و بار شود.
      const hero = await page.$eval('img.hero-sample', (img) => ({ src: img.currentSrc || img.src, w: img.naturalWidth })).catch(() => null);
      if (!hero || !hero.src.includes('golzari-brand/assets/hero-') || !hero.w) return 'عکسِ بالای صفحه عکسِ مجموعه نیست یا بار نشد';
      return null;
    },
  },
];

let failures = 0;
const fail = (msg) => { failures++; console.error('✗ ' + msg); };

async function login(context) {
  const page = await context.newPage();
  await page.goto(BASE + '/wp-login.php');
  await page.fill('#user_login', 'admin');
  await page.fill('#user_pass', 'admin');
  await Promise.all([page.waitForNavigation(), page.click('#wp-submit')]);
  await page.close();
}

async function checkPage(context, vp, def) {
  const page = await context.newPage();
  const errors = [];
  page.on('pageerror', (e) => errors.push('JS: ' + e.message));
  page.on('console', (m) => {
    if (m.type() === 'error' && !/favicon|Failed to load resource/.test(m.text())) errors.push('console: ' + m.text());
  });

  const res = await page.goto(BASE + def.path, { waitUntil: 'networkidle' });
  const tag = `${def.name}@${vp.name}`;
  if (!res || res.status() >= 500) fail(`${tag}: وضعیتِ ${res && res.status()}`);

  const text = await page.locator('body').innerText();
  if (PHP_ERR.test(text)) fail(`${tag}: خطای پی‌اچ‌پی در صفحه: ${text.match(PHP_ERR)[0]}`);

  const overflow = await page.evaluate(() => {
    const el = document.scrollingElement;
    return { sw: el.scrollWidth, cw: el.clientWidth };
  });
  if (overflow.sw > overflow.cw + 1) fail(`${tag}: اسکرولِ افقی (${overflow.sw} > ${overflow.cw})`);

  for (const sel of def.must) {
    if ((await page.locator(sel).count()) === 0) fail(`${tag}: بخشِ ${sel} نیست`);
  }
  for (const e of errors) fail(`${tag}: ${e}`);

  await page.screenshot({ path: `${SHOTS}/${tag}.png`, fullPage: true });
  await page.close();
  console.log(`✓ ${tag}`);
}

async function checkHealth(request) {
  const r = await request.get(BASE + '/?rest_route=/golzari/v1/health');
  const j = r.ok() ? await r.json() : null;
  if (!j || j.ok !== true || !j.plugins || !j.plugins['golzari-updater']) fail('نشانیِ سلامت درست جواب نداد');
  else console.log('✓ health ' + j.plugins['golzari-updater'].version);

  const priv = await request.get(BASE + '/?rest_route=/golzari/v1/status');
  if (priv.status() !== 401 && priv.status() !== 403) fail(`نشانیِ وضعیت بی ورود باز است (${priv.status()})`);
  else console.log('✓ status بسته برای مهمان');
}

const browser = await chromium.launch();
for (const vp of VIEWPORTS) {
  const context = await browser.newContext({
    viewport: { width: vp.width, height: vp.height },
    isMobile: vp.isMobile,
    hasTouch: vp.isMobile,
    locale: 'fa-IR',
  });
  if (vp.name === 'desktop') await checkHealth(context.request);
  for (const def of PAGES.filter((p) => !p.admin)) await checkPage(context, vp, def);
  await login(context);
  for (const def of PAGES.filter((p) => p.admin)) await checkPage(context, vp, def);

  for (const r of REGRESSIONS.filter((r) => !r.viewport || r.viewport === vp.name)) {
    const page = await context.newPage();
    await page.goto(BASE + r.path, { waitUntil: 'networkidle' });
    const msg = await r.check(page);
    if (msg) fail(`${r.name}@${vp.name}: ${msg}`);
    await page.close();
  }
  await context.close();
}
await browser.close();

if (failures) {
  console.error(`✗ ${failures} ایراد`);
  process.exit(1);
}
console.log('✓ آزمونِ دیداری سبز');
