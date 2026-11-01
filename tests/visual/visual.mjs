// آزمونِ دیداری پیش از انتشار: node tests/visual/visual.mjs http://127.0.0.1:8080
// در عرضِ گوشی و رایانه: بی اسکرولِ افقی، بی خطای جاوااسکریپت یا پی‌اچ‌پی، بخش‌های اصلی حاضر.
import { chromium } from 'playwright';
import { mkdirSync } from 'node:fs';
import { createHmac } from 'node:crypto';

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
  { name: 'home', path: '/', must: ['.gz-header', '.gz-hero h1', '#services .gz-card', '#works .gz-grid__item', '.gz-footer'] },
  { name: 'sample', path: '/?pagename=sample', must: ['.gz-header', '.gz-article', '.gz-footer'] },
  { name: 'booking', path: '/?pagename=booking', must: ['.gz-header', '.gz-bookform', '.gz-footer'] },
  { name: 'p404', path: '/?p=999999', must: ['.gz-header', '.gz-empty', '.gz-footer'] },
  { name: 'bookings-admin', path: '/wp-admin/admin.php?page=golzari-bookings', admin: true, must: ['.wrap h1'] },
  { name: 'booking-opts', path: '/wp-admin/admin.php?page=golzari-booking-opts', admin: true, must: ['select[name="start"]', 'input[name="staff[golzari]"]', 'input[name="services[brow-micro]"]'] },
  { name: 'search', path: '/?s=%D8%A2%D8%B1%D8%A7%DB%8C%D8%B4', must: ['body'] },
  { name: 'updater', path: '/wp-admin/tools.php?page=golzari-updater', admin: true, must: ['.wrap h1'] },
  { name: 'brand', path: '/wp-admin/options-general.php?page=golzari-brand', admin: true, must: ['.wrap h1', 'input[name="golzari_labels[instagram]"]'] },
];

// هر ایرادی که با اسکرین‌شات خبر داده شد، این‌جا یک وارسیِ تازه می‌گیرد تا برنگردد.
// { name, path, viewport: 'mobile'|'desktop'|undefined, check: async (page) => 'پیامِ خطا' | null }
const REGRESSIONS = [
  {
    // ۱۴۰۵/۰۸/۰۸: نام‌های قالب و لینک‌های سایتِ نمونه روی صفحه‌ی اصلی مانده بود.
    name: 'brand-sample',
    path: '/?pagename=sample',
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
      // ۱۴۰۵/۰۸/۰۸: لوگوی زیبانو در منوی گوشی مانده بود.
      const logo = await page.$eval('img.logo-sample', (img) => img.src).catch(() => '');
      if (!logo.includes('golzari-brand/assets/logo.webp')) return 'لوگوی قالب به لوگوی مجموعه عوض نشد';
      // ۱۴۰۵/۰۸/۰۹: با قالبِ «گلزاری» هیچ ردی از قالبِ قبلی و صفحه‌ساز نمی‌ماند.
      if (/zibanoo|elementor|noorgfx/i.test(await page.content())) return 'ردِ قالبِ قبلی یا صفحه‌ساز در صفحه';
      return null;
    },
  },
  {
    // ۱۴۰۵/۰۸/۰۸: صفحه‌ی اصلیِ اختصاصی؛ هیچ ردی از قالب، رزرو و تماس و اینستاگرامِ مجموعه.
    name: 'home-custom',
    path: '/',
    check: async (page) => {
      const html = await page.content();
      if (/zibanoo|زیبانو|elementor|nour-/i.test(html)) return 'ردِ قالب یا صفحه‌سازِ قالب در صفحه‌ی اصلی';
      const text = await page.locator('body').innerText();
      if (!text.includes('درباره سمیه گلزاری') || !text.includes('مزایای ویژه هنرجویان') || !text.includes('ورکشاپ‌ها و دوره‌های تخصصی')) return 'بخش‌های محتوای تازه نیامد';
      const hrefs = await page.$$eval('a[href]', (as) => as.map((a) => a.getAttribute('href')));
      if (!hrefs.some((h) => /\/booking\/$/.test(h))) return 'پیوندِ رزرو نیست';
      if (!hrefs.includes('tel:09129178237')) return 'پیوندِ تماس نیست';
      if (!hrefs.some((h) => h.includes('instagram.com/golzari_pmg'))) return 'اینستاگرامِ مجموعه نیست';
      const broken = await page.$$eval('img', (ims) => ims.filter((i) => i.getAttribute('src') && i.complete && !i.naturalWidth && i.loading !== 'lazy').map((i) => i.src));
      if (broken.length) return 'عکسِ بارنشده: ' + broken.join(' ');
      const font = await page.evaluate(() => getComputedStyle(document.body).fontFamily);
      if (!/GZ/.test(font)) return 'فونتِ مجموعه بار نشد';
      return null;
    },
  },
  {
    // ۱۴۰۵/۰۸/۰۹: سیستمِ نوبت‌دهی؛ یک نوبتِ واقعی از اول تا آخر.
    name: 'booking-flow',
    path: '/?pagename=booking',
    check: async (page) => {
      const html = await page.content();
      if (/zibanoo|زیبانو|elementor|sab-select/i.test(html)) return 'ردِ قالب یا فرمِ قالب در صفحه‌ی رزرو';
      await page.waitForSelector('.gz-day', { timeout: 10000 });
      await page.click('.gz-choice span');
      const slot = await page.$('.gz-slot:not([disabled])');
      if (!slot) return 'ساعتِ آزادی پیدا نشد';
      await slot.click();
      await page.fill('input[name="name"]', 'آزمونِ نوبت');
      await page.fill('input[name="phone"]', '۰۹۱۲۱۲۳۴۵۶۷');
      await page.waitForTimeout(3500);
      await page.click('.gz-bookform__submit');
      try {
        await page.waitForSelector('#gz-done:not([hidden])', { timeout: 10000 });
      } catch (e) {
        return 'نوبت ثبت نشد: ' + (await page.$eval('#gz-formerr', (x) => x.textContent).catch(() => ''));
      }
      // ۱۴۰۵/۰۸/۰۹: پیگمنتر در خلاصه‌ی نوبت.
      const done = await page.locator('#gz-done').innerText();
      if (!done.includes('سمیه گلزاری')) return 'نامِ پیگمنتر در خلاصه‌ی نوبت نیست';
      return null;
    },
  },
  {
    name: 'home-mobile-menu',
    path: '/',
    viewport: 'mobile',
    check: async (page) => {
      await page.click('#gz-burger');
      await page.waitForTimeout(400);
      const open = await page.$eval('#gz-nav', (n) => n.classList.contains('is-open') && getComputedStyle(n).visibility === 'visible');
      return open ? null : 'منوی گوشی باز نشد';
    },
  },
];

let failures = 0;
const fail = (msg) => { failures++; console.error('✗ ' + msg); };

async function login(context) {
  const page = await context.newPage();
  await page.goto(BASE + '/wp-login.php');
  // ۱۴۰۵/۰۸/۰۹: صفحه‌ی ورود با لوگوی مجموعه.
  const bg = await page.$eval('.login h1 a', (a) => getComputedStyle(a).backgroundImage).catch(() => '');
  if (!bg.includes('golzari-brand/assets/logo-192.webp')) fail('صفحه‌ی ورود لوگوی مجموعه را ندارد');
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
  if (!j || !j.extra || !j.extra.cleanup || j.extra.cleanup.stage !== 'done' || j.extra.cleanup.theme !== 'golzari-site') fail('پاک‌سازیِ یک‌باره تمام نشده یا قالبِ گلزاری فعال نیست: ' + JSON.stringify(j && j.extra && j.extra.cleanup));

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
    if (r.admin) continue;
    const page = await context.newPage();
    await page.goto(BASE + r.path, { waitUntil: 'networkidle' });
    const msg = await r.check(page);
    if (msg) fail(`${r.name}@${vp.name}: ${msg}`);
    await page.close();
  }
  await context.close();
}
// نوبت‌های آزمون در فهرستِ پیشخوان دیده شوند.
{
  const context = await browser.newContext({ viewport: { width: 1366, height: 900 }, locale: 'fa-IR' });
  await login(context);
  const page = await context.newPage();
  await page.goto(BASE + '/wp-admin/admin.php?page=golzari-bookings', { waitUntil: 'networkidle' });
  const rows = await page.locator('table.widefat tbody tr', { hasText: 'آزمونِ نوبت' }).count();
  if (rows < 1) fail('نوبتِ ثبت‌شده در فهرستِ پیشخوان نیست');
  else console.log(`✓ نوبت‌ها در پیشخوان: ${rows}`);
  await context.close();
}
// ۱۴۰۵/۰۸/۰۹: پاک‌سازیِ قالب: صفحه‌ی اصلی و رزرو قفل‌اند و پس از پاک‌سازی سالم می‌مانند.
{
  const context = await browser.newContext({ viewport: { width: 1366, height: 900 }, locale: 'fa-IR' });
  await login(context);
  const page = await context.newPage();
  await page.goto(BASE + '/wp-admin/tools.php?page=golzari-cleanup', { waitUntil: 'networkidle' });
  const html = await page.content();
  if (!html.includes('/sample</code>')) fail('برگه‌ی نمونه در فهرستِ پاک‌سازی نیست');
  if (html.includes('/booking</code>')) fail('صفحه‌ی رزرو در فهرستِ پاک‌سازی آمده؛ باید قفل باشد');
  await page.check('input[name="confirm"]');
  await Promise.all([page.waitForNavigation(), page.click('#submit')]);
  try {
    await page.waitForSelector('text=پاک‌سازی تمام شد', { timeout: 60000 });
  } catch (e) {
    fail('پاک‌سازی تمام نشد');
  }
  for (const [path, sel] of [['/', '.gz-hero'], ['/?pagename=booking', '.gz-bookform']]) {
    const p2 = await context.newPage();
    await p2.goto(BASE + path, { waitUntil: 'networkidle' });
    if ((await p2.locator(sel).count()) === 0) fail(`پس از پاک‌سازی ${path} سالم نیست`);
    await p2.close();
  }
  const gone = await context.request.get(BASE + '/?pagename=sample');
  if (gone.status() !== 404) fail(`برگه‌ی نمونه پس از پاک‌سازی هنوز باز است (${gone.status()})`);
  console.log('✓ پاک‌سازی');
  await context.close();
}

// ۱۴۰۵/۰۸/۰۹: ربات تلگرام از راهِ قلاب: اتصال، فهرست، تنظیمات، و صفِ خبرها.
{
  const TOKEN = '123456789:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA';
  const hm = (w, n) => createHmac('sha256', TOKEN).update(w).digest('hex').slice(0, n);
  const ctx = await browser.newContext();
  const rq = ctx.request;
  const hook = BASE + '/?rest_route=/golzari/v1/telegram/hook/' + hm('hook', 24);
  const post = (body, secret = hm('secret', 32)) => rq.post(hook, { data: body, headers: { 'X-Telegram-Bot-Api-Secret-Token': secret } });
  const chat = { id: 777, type: 'private' };
  try {
    let r = await post({ message: { chat, from: { first_name: 'آزمون' }, text: '/start 123456' } }, 'wrong');
    if (r.status() !== 403) fail(`قلاب بی رمزِ درست باز است (${r.status()})`);
    r = await post({ message: { chat, from: { first_name: 'آزمون' }, text: 'سلام' } });
    let j = await r.json();
    if (!/خصوصی/.test(j.text || '')) fail('ربات به گفت‌وگوی وصل‌نشده جوابِ خصوصی نداد');
    r = await post({ message: { chat, from: { first_name: 'آزمون' }, text: '/start 123456' } });
    j = await r.json();
    if (j.method !== 'sendMessage' || !/وصل شد/.test(j.text || '')) fail('اتصالِ ربات با کد نشد: ' + JSON.stringify(j).slice(0, 200));
    const cb = (data) => post({ callback_query: { id: 'q', data, message: { message_id: 5, chat } } }).then((x) => x.json());
    j = await cb('l:7');
    if (j.method !== 'editMessageText' || !/نوبت/.test(j.text)) fail('فهرستِ نوبت‌ها در ربات نیامد');
    j = await cb('sd:5');
    if (!(j.reply_markup?.inline_keyboard || []).flat().some((b) => b.callback_data === 'sd:5' && b.text.startsWith('✅'))) fail('روشن کردنِ جمعه از ربات نشد');
    await cb('sd:5');
    j = await cb('sl:30');
    if (!/۳۰ دقیقه/.test(j.text || '')) fail('مدتِ نوبت از ربات عوض نشد');
    await cb('sl:60');
    // نوبتِ تازه پس از اتصال باید در صفِ خبرها بیاید.
    const av = await (await rq.get(BASE + '/?rest_route=/golzari/v1/booking&staff=golzari')).json();
    const day = av.days.find((d) => d.slots.some((s) => s.free));
    const slot = day.slots.find((s) => s.free);
    r = await rq.post(BASE + '/?rest_route=/golzari/v1/booking', { data: { service: 'remo-eye', staff: 'golzari', date: day.d, time: slot.t, name: 'خبرِ ربات', phone: '09121234567', elapsed: 9 } });
    if (!(await r.json()).ok) fail('نوبتِ آزمونِ ربات ثبت نشد');
    const bad = await rq.get(BASE + '/?rest_route=/golzari/v1/telegram/outbox&key=bad');
    if (bad.status() !== 403) fail('صفِ خبرها بی کلید باز است');
    await new Promise((res) => setTimeout(res, 1500));
    const box = await (await rq.get(BASE + '/?rest_route=/golzari/v1/telegram/outbox&key=' + hm('outbox', 32))).json();
    if (!box.webhook?.url || !(box.messages || []).some((m) => /خبرِ ربات/.test(m.text))) fail('خبرِ نوبتِ تازه در صفِ تلگرام نیست: ' + JSON.stringify(box).slice(0, 200));
    else console.log('✓ ربات تلگرام');
  } catch (e) {
    fail('ربات تلگرام: ' + e.message);
  }
  await ctx.close();
}
await browser.close();

if (failures) {
  console.error(`✗ ${failures} ایراد`);
  process.exit(1);
}
console.log('✓ آزمونِ دیداری سبز');
