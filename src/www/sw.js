/**
 * AoS Match Assistant - Service Worker
 *
 * 戦略:
 *  - install: app shell（CSS/JS/オフラインページ等）を precache
 *  - activate: 旧バージョンのキャッシュを削除して即時制御
 *  - fetch:
 *      - 非GET           : 介入しない（POST等はネットワークへ）
 *      - ページ遷移(navigate): ネットワーク優先 → 失敗時キャッシュ → offline.html
 *      - 同一オリジン静的 : stale-while-revalidate
 *      - クロスオリジン   : cache-first（CDN等を日和見キャッシュ）
 *
 * ※ CSS/JS を更新したら CACHE_VERSION を上げること。
 */

const CACHE_VERSION = "v8";
const STATIC_CACHE = "aos-static-" + CACHE_VERSION;
const RUNTIME_CACHE = "aos-runtime-" + CACHE_VERSION;
const OFFLINE_URL = "./offline.html";

// install 時に必ずキャッシュしたい app shell（存在が確実なもののみ）
const PRECACHE_URLS = [
	"./offline.html",
	"./manifest.json",
	"./css/style.css",
	"./js/main.js",
];

self.addEventListener("install", (event) => {
	event.waitUntil(
		(async () => {
			const cache = await caches.open(STATIC_CACHE);
			// 1つでも404だと addAll は全体失敗するため、個別に追加して耐性を持たせる
			await Promise.allSettled(
				PRECACHE_URLS.map((url) =>
					cache.add(new Request(url, { cache: "reload" })),
				),
			);
			await self.skipWaiting();
		})(),
	);
});

self.addEventListener("activate", (event) => {
	event.waitUntil(
		(async () => {
			const keys = await caches.keys();
			await Promise.all(
				keys
					.filter(
						(key) => key !== STATIC_CACHE && key !== RUNTIME_CACHE,
					)
					.map((key) => caches.delete(key)),
			);
			await self.clients.claim();
		})(),
	);
});

self.addEventListener("fetch", (event) => {
	const { request } = event;

	// GET 以外（POSTでの保存など）は介入しない
	if (request.method !== "GET") {
		return;
	}

	const url = new URL(request.url);
	const sameOrigin = url.origin === self.location.origin;

	// ページ遷移: ネットワーク優先、失敗時はキャッシュ→オフラインページ
	if (request.mode === "navigate") {
		event.respondWith(networkFirstForNavigation(request));
		return;
	}

	if (sameOrigin) {
		// 同一オリジンの静的アセット: stale-while-revalidate
		event.respondWith(staleWhileRevalidate(request));
		return;
	}

	// クロスオリジン（CDN等）: cache-first で日和見キャッシュ
	event.respondWith(cacheFirst(request));
});

async function networkFirstForNavigation(request) {
	const cache = await caches.open(RUNTIME_CACHE);
	try {
		const response = await fetch(request);
		if (response && response.ok) {
			cache.put(request, response.clone());
		}
		return response;
	} catch (err) {
		const cached = await cache.match(request);
		if (cached) {
			return cached;
		}
		const offline = await caches.match(OFFLINE_URL);
		return (
			offline ||
			new Response("オフラインです。", {
				status: 503,
				headers: { "Content-Type": "text/plain; charset=utf-8" },
			})
		);
	}
}

async function staleWhileRevalidate(request) {
	const cache = await caches.open(RUNTIME_CACHE);
	const cached = await cache.match(request);

	const network = fetch(request)
		.then((response) => {
			if (response && response.ok && response.type === "basic") {
				cache.put(request, response.clone());
			}
			return response;
		})
		.catch(() => null);

	return cached || (await network) || fetch(request);
}

async function cacheFirst(request) {
	const cache = await caches.open(RUNTIME_CACHE);
	const cached = await cache.match(request);
	if (cached) {
		return cached;
	}
	try {
		const response = await fetch(request);
		// CDNは opaque(type=='opaque') になることがある。status 0 でも保存はしておく。
		if (response && (response.ok || response.type === "opaque")) {
			cache.put(request, response.clone());
		}
		return response;
	} catch (err) {
		return cached || Response.error();
	}
}
