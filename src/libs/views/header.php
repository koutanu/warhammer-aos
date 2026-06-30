<input type="hidden" id="token" value="<?= $token; ?>">
<input type="hidden" id="doc_root" value="<?= $this->h(URL); ?>">
<input type="hidden" id="method" value="<?= $method; ?>">
<input type="hidden" id="class" value="<?= $class; ?>">

<div class="wrapper">
	<!-- 左側常時表示のサイドナビ -->
	<nav class="side-nav">
		<div class="nav-sticky-container">
			<!-- アプリロゴ・タイトル -->
			<h1 class="app-title">
				<a href="<?= $this->h(URL); ?>home">AoS Match<br>Assistant</a>
			</h1>

			<!-- メニューリンク -->
			<ul class="nav-menu">
				<li>
					<a href="<?= $this->h(URL); ?>home"
						class="nav-item <?= $class === 'home' ? 'active' : ''; ?>">Home</a>
				</li>
				<li>
					<a href="<?= $this->h(URL); ?>roster"
						class="nav-item <?= $class === 'roster' ? 'active' : ''; ?>">Roster</a>
				</li>
				<li>
					<a href="<?= $this->h(URL); ?>match/setup"
						class="nav-item <?= (strpos($class, 'match') === 0 && $method !== 'history') ? 'active' : ''; ?>">Match</a>
				</li>
				<li>
					<a href="<?= $this->h(URL); ?>match/history"
						class="nav-item <?= $class === 'match' && $method === 'history' ? 'active' : ''; ?>">Match History</a>
				</li>
				<li>
					<a href="<?= $this->h(URL); ?>unit"
						class="nav-item <?= $class === 'unit' ? 'active' : ''; ?>">Units</a>
				</li>
				<li>
					<a href="<?= $this->h(URL); ?>rule"
						class="nav-item <?= $class === 'rule' ? 'active' : ''; ?>">Rule</a>
				</li>
			</ul>

			<!-- 下部に配置したいメニュー（ログアウトなど） -->
			<div class="nav-footer">
				<a href="<?= $this->h(URL); ?>admin/logout" class="nav-item logout">Logout</a>
			</div>
		</div>
	</nav>

	<!-- 右側のメインコンテンツ領域 -->
	<main class="main-content">
		<!-- ここに「新規ロスター作成」などのビュー（<div class="roster index">）が入る -->