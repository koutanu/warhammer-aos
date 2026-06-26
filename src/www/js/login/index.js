document.addEventListener("DOMContentLoaded", () => {
	const loginForm = document.querySelector("#form");
	const loginButton = document.querySelector("#login");

	if (!loginForm) return;

	loginForm.addEventListener("submit", (e) => {
		// 1. バリデーション
		const accountInput = loginForm.querySelector(
			'input[name="login_account"]',
		);
		const passwordInput = loginForm.querySelector('input[name="password"]');
		const account = accountInput?.value.trim() || "";
		const password = passwordInput?.value || "";

		if (!account || !password) {
			e.preventDefault(); // 送信を止める
			alert("アカウント名とパスワードを入力してください。");
			return;
		}

		// 2. セッションのクリーンアップ
		sessionStorage.clear();

		// 3. 連打防止（送信ボタンを無効化）
		if (loginButton) {
			loginButton.disabled = true;
			loginButton.textContent = "ログイン中...";
		}

		// e.preventDefault() を呼んでいないので、このまま送信される
	});
});
