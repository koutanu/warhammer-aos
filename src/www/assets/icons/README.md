# PWA アイコン配置ガイド

このフォルダ（`www/assets/icons/`）に、以下のファイル名でアイコン画像を配置してください。
`manifest.json` と `head.php` はこの命名を前提に参照しています。

## 必須

| ファイル名 | サイズ | 用途 |
|---|---|---|
| `icon-192.png` | 192x192 | Android/汎用ホーム画面アイコン（purpose: any） |
| `icon-512.png` | 512x512 | スプラッシュ/高解像度（purpose: any） |
| `icon-maskable-512.png` | 512x512 | マスカブル（周囲に約20%のセーフゾーン余白を確保） |
| `apple-touch-icon.png` | 180x180 | iOS/iPadOS ホーム画面アイコン（角丸はOSが自動付与するため不要） |

## 任意

| ファイル名 | サイズ | 用途 |
|---|---|---|
| `favicon-32.png` | 32x32 | ブラウザタブ用ファビコン |

## アビリティ分類アイコン

アビリティ名の前に表示するアイコン。Wahapedia の `ability_type` カテゴリ（DBの `m_ability_master.icon_type`）に
対応しており、`www/js/match/phases.js` の `ICON_BY_TYPE` がこの命名を前提に参照します。20x20px 程度で表示されます。

| ファイル名 | カテゴリ(icon_type) |
|---|---|
| `abOffensive.png` | Offensive |
| `abDefensive.png` | Defensive |
| `abMovement.png` | Movement |
| `abShooting.png` | Shooting |
| `abDamage.png` | Damage（バトルダメージ等のダメージ表） |
| `abControl.png` | Control |
| `abRallying.png` | Rallying |
| `abSpecial.png` | Special（フォールバックにも使用） |

- `icon_type` が未設定の旧データは、フェイズ正規化（`ICON_BY_PHASE`）にフォールバックします。
- `abRallying.png` は暫定で `abControl.png` の複製を置いています。専用アートに差し替えてください。

## 補足

- すべて PNG（透過可）。`apple-touch-icon.png` は透過させず背景を塗りつぶすと綺麗です（iOS は透過部分を黒にします）。
- 画像を差し替えてもキャッシュが残る場合は、`www/sw.js` の `CACHE_VERSION` を更新してください。
- これらが未配置でもアプリのインストールは可能ですが、アイコンは既定の見た目になります。
