<div class="login">
	<form action="<?= $this->h(URL) ?>login/zikkou" method="post" id="form" class="login-form">
		<?= $alert; ?>
		<img src="<?= URL ?>assets/images/aos_logo.png" class="logo-img">
		<div>
			<span>アカウント</span>
			<input type="text" name="login_account" placeholder="アカウント" autocomplete="username">
		</div>
		<div>
			<span>パスワード</span>
			<input type="password" name="password" placeholder="パスワード" autocomplete="current-password">
		</div>
		<!-- ハニーポット -->
		<div class="hp-field" aria-hidden="true">
			<input type="text" name="hp_email" tabindex="-1" autocomplete="off">
		</div>
		<div>
			<button type="submit" id="login" class="btn login-btn">ログイン</button>
		</div>
	</form>
	<!-- <a href="<?= $this->h(URL); ?>login/account">アカウント新規追加</a> -->
</div>