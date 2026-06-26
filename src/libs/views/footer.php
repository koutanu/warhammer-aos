<!--wrapper用div-->
</div>
<!-- フッター -->
<script src="<?= $this->h(URL); ?>js/main.js" charset="UTF-8"></script>
<?php // $this->js ではなく、展開された $js (配列) があるかチェック 
?>
<?php if (isset($js) && is_array($js)) : ?>
	<?php foreach ($js as $scriptFile) : ?>
		<script src="<?= $this->h(URL); ?>js/<?= $scriptFile; ?>" charset="UTF-8"></script>
	<?php endforeach; ?>
<?php endif; ?>

<!-- PWA: Service Worker 登録（サブディレクトリ配信のため絶対パスで登録） -->
<script>
	if ("serviceWorker" in navigator) {
		window.addEventListener("load", function () {
			navigator.serviceWorker
				.register("<?= $this->h(URL); ?>sw.js", { scope: "<?= $this->h(URL); ?>" })
				.catch(function (err) {
					console.warn("Service Worker registration failed:", err);
				});
		});
	}
</script>
</body>

</html>