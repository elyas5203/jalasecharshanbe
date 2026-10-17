const CACHE_NAME = 'charshanbe-jalase-v4';
// فقط دارایی‌های ایستا کش می‌شوند. صفحات وابسته به نشست (index/admin/login) هرگز کش نمی‌شوند.
const CORE = ['assets/app.css', 'assets/default-avatar.svg', 'assets/favicon.svg'];
// مسیرهای حساس به احراز هویت: همیشه از شبکه (کش نشوند تا داده‌ی کاربری لو نرود)
const NO_CACHE = ['/index.php', '/admin.php', '/login.php', '/logout.php', '/api.php'];

self.addEventListener('install', event => {
  event.waitUntil(caches.open(CACHE_NAME).then(cache => cache.addAll(CORE).catch(() => null)));
  self.skipWaiting();
});

self.addEventListener('activate', event => {
  event.waitUntil(caches.keys().then(keys => Promise.all(keys.filter(k => k !== CACHE_NAME).map(k => caches.delete(k)))));
  self.clients.claim();
});

self.addEventListener('fetch', event => {
  const req = event.request;
  const url = new URL(req.url);
  if (req.method !== 'GET') return;

  // API responses are live data; caching them causes stale dashboard/attendance state after writes.
  if (url.pathname.endsWith('/api.php')) {
    event.respondWith(fetch(req).catch(() => new Response(JSON.stringify({success:false, msg:'ارتباط با سرور برقرار نشد'}), {headers:{'Content-Type':'application/json; charset=utf-8'}})));
    return;
  }

  // صفحات وابسته به نشست: همیشه از شبکه، بدون کش‌کردن (جلوگیری از لو رفتن داده بین کاربران)
  if (NO_CACHE.some(p => url.pathname.endsWith(p))) {
    event.respondWith(fetch(req));
    return;
  }

  event.respondWith(fetch(req).then(res => {
    const copy = res.clone();
    caches.open(CACHE_NAME).then(cache => cache.put(req, copy)).catch(() => null);
    return res;
  }).catch(() => caches.match(req)));
});
