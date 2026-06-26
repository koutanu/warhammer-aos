/**
 * ベースURLの取得
 * $('#doc_root').val() を Vanilla JS に置換
 */
function getBaseURL() {
	const docRoot = document.getElementById("doc_root");
	return docRoot ? docRoot.value : "";
}

/**
 * モーダル表示中の背景スクロール制御（iOS/iPad対策）
 * - body を position:fixed で固定し、背景スクロール・スクロールチェーンを防ぐ
 * - 複数モーダルの同時表示に備え、開いているモーダルIDを Set で管理する
 */
const ModalScroll = (function () {
	const openModals = new Set();

	function applyLock() {
		const scrollY = window.scrollY || window.pageYOffset || 0;
		const body = document.body;
		body.dataset.modalScrollY = String(scrollY);
		body.style.position = "fixed";
		body.style.top = `-${scrollY}px`;
		body.style.left = "0";
		body.style.right = "0";
		body.style.width = "100%";
		body.classList.add("modal-scroll-locked");
	}

	function releaseLock() {
		const body = document.body;
		const scrollY = parseInt(body.dataset.modalScrollY || "0", 10);
		body.style.position = "";
		body.style.top = "";
		body.style.left = "";
		body.style.right = "";
		body.style.width = "";
		body.classList.remove("modal-scroll-locked");
		delete body.dataset.modalScrollY;
		window.scrollTo(0, scrollY);
	}

	function lock(id) {
		const key = id || "default";
		if (openModals.has(key)) return;
		if (openModals.size === 0) applyLock();
		openModals.add(key);
	}

	function unlock(id) {
		const key = id || "default";
		if (!openModals.has(key)) return;
		openModals.delete(key);
		if (openModals.size === 0) releaseLock();
	}

	return { lock, unlock };
})();
window.ModalScroll = ModalScroll;

/**
 * トークンの取得
 */
function getToken() {
	const token = document.getElementById("token");
	return token ? token.value : "";
}

/**
 * 日付から「〇月」を取得
 */
function setDateFormatMonth(data) {
	if (!data || data === "0000-00-00 00:00:00") {
		return "";
	} else {
		// ハイフンをスラッシュに置換（ブラウザ互換性のため）
		const date = new Date(data.replace(/-/g, "/"));
		// getMonth()は0から始まるので+1する
		return date.getMonth() + 1 + "月";
	}
}

/**
 * 指定日が今日より前かどうかを判定
 * ロジックを簡略化：Dateオブジェクト同士で直接比較可能
 */
function lowerThanDateFromToday(d) {
	const date = new Date(d);
	const today = new Date();

	// 時間・分・秒をリセットして日付のみで比較
	date.setHours(0, 0, 0, 0);
	today.setHours(0, 0, 0, 0);

	return date < today;
}

/**
 * 指定日が訪問日より前かどうかを判定
 */
function lowerThanDateFromVisitDay(d, visit) {
	const date = new Date(d);
	const visitDate = new Date(visit);

	date.setHours(0, 0, 0, 0);
	visitDate.setHours(0, 0, 0, 0);

	return date < visitDate;
}

/**
 * 戻るボタンのイベントリスナー
 * $('.history_back').on('click', ...) を置換
 */
document.addEventListener("click", function (e) {
	if (
		e.target.classList.contains("history_back") ||
		e.target.closest(".history_back")
	) {
		history.back();
	}
});
/**
 * 全ての input 要素からフォーカスが外れた際、全角数字を半角に変換する
 */
document.addEventListener(
	"blur",
	function (e) {
		// イベントが発生した要素が input タグであるか確認
		if (e.target.tagName.toLowerCase() === "input") {
			const originalValue = e.target.value;

			// 全角数字を半角数字に置換する処理
			const convertedValue = originalValue.replace(
				/[０-９]/g,
				function (s) {
					return String.fromCharCode(s.charCodeAt(0) - 0xfee0);
				},
			);

			// 変換が必要な場合のみ値を書き換え
			if (originalValue !== convertedValue) {
				e.target.value = convertedValue;
			}
		}
	},
	true,
); // 第3引数を true にしてイベントキャプチャを有効化（blurはバブリングしないため）
