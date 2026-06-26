<form action="<?= $this->h(URL) ?>login/createUser" method="post" id="form" class="login-form">
	<div class="p-2">
		<div class="text-red">
			<?= $alert; ?>
		</div>
		<div class="mt-3">
			<span>アカウント：</span>
			<input type="text" name="account" placeholder="アカウント">
		</div>
		<div>
			<span>名前：</span>
			<input type="text" name="name" placeholder="名前">
		</div>
		<div>
			<span>パスワード：</span>
			<input type="password" name="password" placeholder="パスワード">
		</div>
		<div class="mt-3">
			<button type="submit" id="login" class="btn-submit btn">登録</button>
		</div>
	</div>
</form>