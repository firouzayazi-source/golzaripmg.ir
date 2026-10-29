// عکس از سایتِ زنده از راهِ پل، با حافظه‌ی محلی.
//   node tools/snap.mjs / --mobile
//   node tools/snap.mjs /services/ --desktop --refresh
//   node tools/snap.mjs /wp-json/golzari/v1/status --auth --json
// بارِ اول از پل می‌خواند و بعد از .cache/relay. هیچ‌وقت در حلقه به سایتِ زنده نزنید.
// --refresh فقط خودِ صفحه را تازه می‌خواند؛ فایل‌های ظاهری از حافظه‌ی محلی می‌آیند.
import { chromium } from 'playwright';
import { createHash } from 'node:crypto';
import { existsSync, mkdirSync, readFileSync, writeFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const ROOT = join(dirname(fileURLToPath(import.meta.url)), '..');
const CACHE = join(ROOT, '.cache', 'relay');
const SITE = 'https://golzaripmg.ir';
const HOSTS = new Set(['golzaripmg.ir', 'www.golzaripmg.ir']);

const RELAY = (process.env.GOLZARI_RELAY_URL || '').replace(/\/$/, '');
const RELAY_KEY = process.env.GOLZARI_RELAY_KEY || '';
const WP_USER = process.env.GOLZARI_WP_USER || '';
const WP_PASS = process.env.GOLZARI_WP_APP_PASSWORD || '';

const args = process.argv.slice(2);
const flag = (f) => args.includes(f);
const path = args.find((a) => !a.startsWith('--')) || '/';
const mobile = !flag('--desktop');
const out = (() => { const i = args.indexOf('--out'); return i >= 0 ? args[i + 1] : null; })();

if (!RELAY) {
  console.error('✗ متغیرِ GOLZARI_RELAY_URL تنظیم نشده.');
  process.exit(1);
}
mkdirSync(CACHE, { recursive: true });

// ⛔ هاست با حدودِ ۲۰ اتصالِ هم‌زمان آی‌پی را می‌بندد: حداکثر ۲ هم‌زمان، با فاصله.
const MAX_PARALLEL = 2;
const GAP_MS = 700;
const MAX_CONSECUTIVE_FAILS = 3;
let active = 0;
let lastStart = 0;
let consecutiveFails = 0;

async function slot() {
  while (active >= MAX_PARALLEL || Date.now() - lastStart < GAP_MS) {
    await new Promise((r) => setTimeout(r, 50));
  }
  active++;
  lastStart = Date.now();
  return () => { active--; };
}

async function relayGet(sitePath, { refresh = false, auth = false } = {}) {
  const ua = mobile ? 'mobile' : 'desktop';
  const key = createHash('sha1').update(`${ua}|${auth ? 'auth' : ''}|${sitePath}`).digest('hex');
  const bodyFile = join(CACHE, key + '.bin');
  const metaFile = join(CACHE, key + '.json');

  if (!refresh && existsSync(bodyFile) && existsSync(metaFile)) {
    return { ...JSON.parse(readFileSync(metaFile, 'utf8')), body: readFileSync(bodyFile), cached: true };
  }
  if (consecutiveFails >= MAX_CONSECUTIVE_FAILS) {
    throw new Error('پس از چند قطعِ پشتِ سرِ هم متوقف شد؛ چند دقیقه صبر کنید و دوباره بزنید.');
  }

  const release = await slot();
  try {
    const url = `${RELAY}/relay?path=${encodeURIComponent(sitePath)}${mobile ? '&ua=mobile' : ''}`;
    const headers = {};
    if (RELAY_KEY) headers['X-Relay-Key'] = RELAY_KEY;
    if (auth && WP_USER && WP_PASS) headers.Authorization = 'Basic ' + Buffer.from(`${WP_USER}:${WP_PASS}`).toString('base64');
    const res = await fetch(url, { headers, signal: AbortSignal.timeout(30000) });
    const body = Buffer.from(await res.arrayBuffer());
    if (res.status >= 500 || res.status === 0) throw new Error(`پل: ${res.status}`);
    consecutiveFails = 0;
    const meta = { status: res.status, type: res.headers.get('content-type') || 'application/octet-stream', path: sitePath };
    if (res.status < 400) {
      writeFileSync(bodyFile, body);
      writeFileSync(metaFile, JSON.stringify(meta));
    }
    return { ...meta, body, cached: false };
  } catch (e) {
    consecutiveFails++;
    throw e;
  } finally {
    release();
  }
}

if (flag('--json')) {
  const r = await relayGet(path, { refresh: flag('--refresh'), auth: flag('--auth') });
  process.stdout.write(r.body.toString('utf8') + '\n');
  process.exit(r.status < 400 ? 0 : 1);
}

const browser = await chromium.launch();
const context = await browser.newContext(
  mobile
    ? { viewport: { width: 390, height: 844 }, isMobile: true, hasTouch: true, deviceScaleFactor: 2, locale: 'fa-IR' }
    : { viewport: { width: 1366, height: 900 }, locale: 'fa-IR' },
);
const page = await context.newPage();
let fromRelay = 0;
let fromCache = 0;

await page.route('**/*', async (route) => {
  const u = new URL(route.request().url());
  if (!HOSTS.has(u.hostname) || route.request().method() !== 'GET') return route.abort();
  const isDoc = route.request().resourceType() === 'document';
  try {
    const r = await relayGet(u.pathname + u.search, { refresh: isDoc && flag('--refresh') });
    r.cached ? fromCache++ : fromRelay++;
    await route.fulfill({ status: r.status, contentType: r.type, body: r.body });
  } catch (e) {
    console.error(`• ${u.pathname}: ${e.message}`);
    await route.abort();
  }
});

await page.goto(SITE + path, { waitUntil: 'load', timeout: 120000 });
await page.waitForTimeout(1500);
const overflow = await page.evaluate(() => document.scrollingElement.scrollWidth - document.scrollingElement.clientWidth);
const file = out || join(ROOT, 'shots', `${(path.replace(/[^\w-]+/g, '_') || 'home')}-${mobile ? 'mobile' : 'desktop'}.png`);
mkdirSync(dirname(file), { recursive: true });
await page.screenshot({ path: file, fullPage: true });
await browser.close();

console.log(`✓ ${file}`);
console.log(`  از پل: ${fromRelay}  از حافظه‌ی محلی: ${fromCache}  اسکرولِ افقی: ${overflow > 1 ? overflow + ' پیکسل ✗' : 'ندارد ✓'}`);
