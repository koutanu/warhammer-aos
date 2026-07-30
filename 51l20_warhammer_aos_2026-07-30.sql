# ************************************************************
# Sequel Ace SQL dump
# バージョン 20104
#
# https://sequel-ace.com/
# https://github.com/Sequel-Ace/Sequel-Ace
#
# ホスト: localhost (MySQL 8.0.46)
# データベース: 51l20_warhammer_aos
# 生成時間: 2026-07-30 14:05:24 +0000
# ************************************************************


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
SET NAMES utf8mb4;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE='NO_AUTO_VALUE_ON_ZERO', SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


# テーブルのダンプ m_ability_master
# ------------------------------------------------------------

DROP TABLE IF EXISTS `m_ability_master`;

CREATE TABLE `m_ability_master` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `name_en` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `command_point` tinyint DEFAULT NULL,
  `casting_value` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `casting_type` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `ability_type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `activation` enum('active','passive','reaction') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'active',
  `usage_scope` enum('unlimited','once_per_turn','once_per_phase','once_per_battle') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'unlimited',
  `usage_per` enum('unit','army') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'unit',
  `trigger_phase` set('deployment','hero','movement','shooting','charge','combat','end','any') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `trigger_turn` enum('your','opponent','any','battle') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT 'your',
  `trigger_condition_en` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `trigger_condition_ja` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `icon_type` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `effect` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `effect_en` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `keywords` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `flavor_text` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

LOCK TABLES `m_ability_master` WRITE;
/*!40000 ALTER TABLE `m_ability_master` DISABLE KEYS */;

INSERT INTO `m_ability_master` (`id`, `name`, `name_en`, `command_point`, `casting_value`, `casting_type`, `ability_type`, `activation`, `usage_scope`, `usage_per`, `trigger_phase`, `trigger_turn`, `trigger_condition_en`, `trigger_condition_ja`, `icon_type`, `effect`, `effect_en`, `keywords`, `flavor_text`)
VALUES
	(1,'勇敢な守護者',NULL,NULL,NULL,NULL,'Passive','passive','unlimited','unit',NULL,'your',NULL,'パッシブ',NULL,'【効果】: 自軍領地内に全体が入っている作戦目標を争奪している間、このユニットの確保スコアは+3の修正を受ける。',NULL,NULL,'シグマーの領地として招かれた大地を、リベレイターは堅守する。'),
	(2,'殲滅者',NULL,NULL,NULL,NULL,'Once Per Turn (Army)','reaction','once_per_turn','army','any','any',NULL,'各ターンにつき1回(アーミー)、リアクション、コア以外の能力の対象に選ばれた時。',NULL,'【リアクション】: このユニットが「コアアビリティ以外」の能力の対象に選ばれた時。\r\n【効果】: 抵抗ロール（D6）を行う。4+の場合、そのアビリティはこのユニットに効果を及ぼさない。',NULL,NULL,NULL),
	(57,'定められた探求','Ordained Quest',NULL,NULL,NULL,'Once Per Battle (Army)','active','once_per_battle','army','deployment','your',NULL,'バトルにつき1回(アーミー)、配置フェイズ','Special','【効果】：自軍陣地の外に完全にある目標を1つ選ぶ。その目標はあなたにとってクエストマーク済みとして扱われる。','Effect: Pick an objective wholly outside friendly territory. That objective is considered by you to be questmarked.',NULL,'Questors travel deep into enemy territory to gain control of vital landmarks with hidden secrets.'),
	(58,'英雄的懲罰','Heroic Retribution',NULL,NULL,NULL,'Offensive','reaction','unlimited','unit','combat','any',NULL,'リアクション：このユニットが近接攻撃アビリティを宣言','Offensive','【効果】：このターンに近接アビリティを使用しておらず、このユニットの近接範囲内にいる味方クエスターソウルスウォーンーン・ユニット1個を対象に選ぶ。このユニットが使用した近接アビリティの解決直後に、対象は近接アビリティを使用することができる。','Effect: Pick a friendly Questor Soulsworn unit that has not used a FIGHT ability this turn and is within this unit’s combat range to be the target. The target can be picked to use a FIGHT ability immediately after the FIGHT ability used by this unit has been resolved.',NULL,'Should the need arise, the Knight-Questor will call upon their Soulsworn brethren to make the God-King’s justice a reality.'),
	(59,'御心のままに','His Will Be Done',NULL,NULL,NULL,'Passive','passive','unlimited','unit',NULL,'any',NULL,'パッシブ','Defensive','【効果】：このユニットがクエストマーク済みの目標を確保している間：\r\n・このユニットの確保力に+3する。\r\n・このユニットは加護(5+)を得る。','Effect: While this unit is contesting a questmarked objective:\nAdd 3 to this unit’s control score.\nThis unit has Ward (5+).',NULL,'Questors will stop at nothing to fulfil the sacred task given to them by the God-King himself.'),
	(61,'再鍛造者の軍旗','The Banner of the Reforged',NULL,NULL,NULL,'Special','active','unlimited','unit','hero','any',NULL,'任意のヒーローフェイズ','Special','【宣言】：このユニットから12mv以内に全体がいる味方ストームキャスト・ユニットを最大D3個まで対象に選ぶ。\r\n【効果】：このターンの残りの間、各対象の確保力に+3する。さらに各対象をD3回復する。','Declare: Pick up to D3 friendly STORMCAST ETERNALS units wholly within 12\" of this unit to be the targets.\n\nEffect: Add 3 to each target’s control score for the rest of the turn. In addition, Heal (D3) each target.',NULL,'When the Knight-Vexillor plants their banner, the fortifying energy that emanates from it hardens flesh and seals sundered armour, empowering the faithful with new resolve.'),
	(85,'断絶の杖','Staff of Abjuration',NULL,NULL,NULL,'Passive','passive','unlimited','unit',NULL,'any',NULL,'パッシブ','Special','【効果】：このユニットは魔術師(1)を持つかのように打ち消しアビリティを使用できる。','Effect: This unit can use Unbind abilities as if it had Wizard (1).',NULL,'The blessed light that shines from this staff can banish even the most potent sorceries.'),
	(86,'不浄な魔法への五感','Sense Unholy Sorcery',NULL,NULL,NULL,'Passive','passive','unlimited','unit',NULL,'any',NULL,'パッシブ','Special','【効果】：このユニットのグリフクロウはトークンである。グリフクロウが戦場にある間、このユニットから12インチ以内に一部でも入っている敵ユニットの詠唱ロールおよび祈願ロールに-1する。\r\n\r\nこのユニットの修正前セーブロールで1を出した場合、アタックアビリティの解決後にグリフクロウを戦場から取り除く(ダメージ点は与えられる)。','Effect: This unit’s Gryph-crow is a token. Subtract 1 from casting rolls and chanting rolls for enemy units within 12\" of this unit while its Gryph-crow is on the battlefield.\n\nIf you make an unmodified save roll of 1 for this unit, remove its Gryph-crow from the battlefield after the ATTACK ability has been resolved (the damage point is still inflicted).',NULL,'Gryph‑crows are sensitive to corruption and immediately alert their masters whenever unholy energies are nearby.'),
	(126,'不屈の伝承探求者','Indomitable Loreseekers',NULL,NULL,NULL,'Passive','passive','unlimited','unit',NULL,'any',NULL,'パッシブ','Special','【効果】：このユニットが奇蹟アビリティを使用する際の詠唱ロールは+1、このユニットの追放ロールは+1する。\r\n\r\n敵の顕現は、移動を開始した時点でこのユニットの近接範囲内にいたのでない限り、このユニットの近接範囲を通過したり、その中で移動を終えたりできない。\r\n\r\nこのユニットは、顕現による致命的ダメージに対して加護(4+)を得る。','Effect: Add 1 to casting rolls for this unit when it uses a SUMMON ability and add 1 to banishment rolls for this unit.\n\nEnemy MANIFESTATIONS cannot pass through or end any move within this unit’s combat range unless they started that move within this unit’s combat range.\n\nThis unit has Ward (4+) against mortal damage inflicted by MANIFESTATIONS.',NULL,'The thick tomes carried by Knight-Arcanums are filled with the magical secrets they have learnt, including how to resist the raging sorcerous energies of the realms.'),
	(129,'筆頭狩人','The Prime Huntress',NULL,NULL,NULL,'Passive','passive','unlimited','unit',NULL,'any',NULL,'パッシブ','Offensive','【効果】：大型獣を対象とする攻撃について、このユニットのゼンガヴァールのダメージ量を元の2倍の値にする。','Effect: Double the Damage characteristic of this unit’s Thengavar for attacks that target MONSTERS.',NULL,'Celestial strength, lifetimes of experience and the power of the spear Thengavar see even the most behemothic of terrors swiftly fall before Yndrasta’s fury.'),
	(130,'まばゆい輝き','Dazzling Radiance',NULL,NULL,NULL,'Passive','passive','unlimited','unit',NULL,'any',NULL,'パッシブ','Control','【効果】：このユニットから12インチ以内に全体がいる味方ストームキャスト・ユニットの確保力へのマイナス修正を無視する。','Effect: Ignore negative modifiers to the control scores of friendly STORMCAST ETERNALS units while they are wholly within 12\" of this unit.',NULL,'Yndrasta’s god-touched brilliance casts a protective aura of light around nearby Stormcasts.'),
	(131,'輝ける翼','On Wings of Brilliance',NULL,NULL,NULL,'Passive','passive','unlimited','unit',NULL,'your',NULL,'パッシブ','Movement','【効果】：このユニットの突撃ロールで振るダイスの数を1個増やす(最大3個)。','Effect: Add 1 to the number of dice rolled when making charge rolls for this unit, to a maximum of 3.',NULL,'Yndrasta soars across the battlefield to strike at the heart of Sigmar’s enemies.'),
	(132,'灼熱の衝撃 — アナイアレイター (Blazing Impact)','Blazing Impact — Annihilators',NULL,NULL,NULL,'Offensive','active','unlimited','unit','movement','your',NULL,NULL,'Offensive','【宣言】：このユニットが「Scions of the Storm」アビリティでこのターンに配置された場合、それから10インチ以内の敵ユニットを最大3個まで対象に選ぶ。\r\n【効果】：各対象についてD3を振る。2+の場合、その出目に等しい致命的ダメージを対象に与える。','Declare: If this unit was set up this turn using the ‘Scions of the Storm’ ability, pick up to 3 enemy units within 10\" of it to be the targets.\n\nEffect: Roll a D3 for each target. On a 2+, inflict an amount of mortal damage on the target equal to the roll.',NULL,'Annihilators arrive upon the field with devastating force, twin tailed trails streaking behind them as an earth-shattering shock-wave blasts apart the foes of Sigmar.'),
	(133,'衝撃に備えよ — アナイアレイター (Brace for Impact)','Brace for Impact — Annihilators',NULL,NULL,NULL,'Defensive','active','unlimited','unit','charge','any',NULL,NULL,'Defensive','【宣言】：このターンに突撃し、このユニットと交戦中の敵ユニット1個を対象に選ぶ。\r\n【効果】：このユニットの各モデルにつきダイスを1個振る。いずれかが6の場合、このターンの残りの間、対象は後手攻撃(Strike-last)を得る。','Declare: Pick an enemy unit that charged this turn and is in combat with this unit to be the target.\n\nEffect: Roll a dice for each model in this unit. If any of the rolls are a 6, the target has Strike-last for the rest of the turn.',NULL,'Annihilators brace behind their mighty shields to hold off even the most ferocious charge.'),
	(136,'雷の一撃','Lightning Strikes',NULL,NULL,NULL,'Passive','passive','unlimited','unit',NULL,'any',NULL,'パッシブ','Offensive','【効果】：5体以上のモデルを持つ敵ユニットを対象とする攻撃について、このユニットのCelestial Greatswordsのダメージ特性に+1する。','Effect: Add 1 to the Damage characteristic of this unit’s Celestial Greatswords for attacks that target an enemy unit that has 5 or more models.',NULL,'Vanquishers utilise a stance that allows them to make a flurry of rapid strikes against a numerous foe.'),
	(143,'復讐の炎','Fires of Retribution',NULL,NULL,NULL,'Passive','passive','unlimited','unit',NULL,'any',NULL,'パッシブ','Offensive','【効果】：このユニットのダメージ点が5〜9の間、その近接武器の攻撃回数に+1する。\r\n\r\nダメージ点が10以上の間は、代わりに+2する。','Effect: While this unit has 5-9 damage points, add 1 to the Attacks characteristic of its melee weapons. \n\nWhile this unit has 10 or more damage points, add 2 to the Attacks characteristic of its melee weapons instead.',NULL,'Vengeance burns blinding and everlasting in Karazai’s soul.'),
	(144,'厄災の尾の一撃','Calamitous Tail Sweep',NULL,NULL,NULL,'Once Per Turn (Army)','active','once_per_turn','army','combat','any',NULL,'ターン中1回(アーミー)、任意の近接フェイズ','Offensive','【宣言】：このユニットと交戦中の敵ユニット1個を対象に選ぶ。\r\n【効果】：対象ユニットの各モデルにつきダイスを1個振る。5+ごとに、対象に1点の致命的ダメージを与える。','Declare: Pick an enemy unit in combat with this unit to be the target.\n\nEffect: Roll a dice for each model in the target unit. For each 5+, inflict 1 mortal damage on the target.','蹂躙(RAMPAGE)','With a sweep of his tail, Karazai can lay waste to enemy formations.'),
	(145,'太古の強者','Ancient Master of War',NULL,NULL,NULL,'Defensive','active','unlimited','unit','combat','any',NULL,'任意の近接フェイズ','Defensive','【宣言】：このユニットの近接範囲内にいる敵ユニット1個を対象に選ぶ。\r\n【効果】：このターンの残りの間、対象の近接武器の攻撃回数を-1する。','Declare: Pick an enemy unit within this unit’s combat range to be the target.\n\nEffect: Subtract 1 from the Attacks characteristic of the target’s melee weapons for the rest of the turn.',NULL,'Karazai has seen countless battles over the heady span of his life, and the reptilian fury he displays can overpower any foe.'),
	(156,'セレスチアルの劫火','Celestial Blaze',NULL,NULL,NULL,'Passive','passive','unlimited','unit',NULL,'any',NULL,'パッシブ','Special','【効果】：このユニットが「強行突破」コマンドを使用する際、対象に追加でD3点の致命的ダメージを与え、そのアビリティの一部としてこのユニットが移動できる距離にD6mvを加える。','Effect: When this unit uses the ‘Power Through’ command, inflict an additional D3 mortal damage on the target and add D6\" to the distance this unit can move as part of that ability.',NULL,'The Stormstrike Chariot smashes through lesser foes in a blaze of celestial power.'),
	(157,'解き放たれしアジル','Azyr Unleashed',NULL,NULL,NULL,'Offensive','active','unlimited','unit','charge','any',NULL,'任意の突撃フェイズ','Offensive','【宣言】：このユニットがこのフェイズに突撃した場合、それから1mv以内の敵ユニット1個を対象に選ぶ。\r\n【効果】：D3を振る。2+の場合、その出目に等しい致命的ダメージを対象に与える。','Declare: If this unit charged this phase, pick an enemy unit within 1\" of it to be the target\n\nEffect: Roll a D3. On a 2+, inflict an amount of mortal damage on the target equal to the roll.',NULL,'The impact of a Stormstrike Chariot on the charge can obliterate even the most durable of shieldwalls.'),
	(159,'散開陣形','Dispersed Formation',NULL,NULL,NULL,'Passive','passive','unlimited','unit',NULL,'any',NULL,'パッシブ','Special','【効果】：このユニットの隊形範囲は2mvである。','Effect: This unit has a coherency range of 2\".',NULL,'These warriors maintain a wide formation, enabling more precise strikes and greater battlefield control.'),
	(169,'バトルダメージ','Battle Damaged — Ionus Cryptborn, Warden of Lost Souls',NULL,NULL,NULL,'Passive','passive','unlimited','unit',NULL,'any',NULL,'パッシブ','Damage','【効果】：このユニットのダメージ点が10以上の間、クトラックのいにしえの爪の攻撃回数は4となる。','Effect: While this unit has 10 or more damage points, the Attacks characteristic of Cthorak’s Ancient Claws is 4.',NULL,NULL),
	(170,'精魂の焔','Spirit-scouring Flames',NULL,NULL,NULL,'Once Per Turn (Army)','active','once_per_turn','army','shooting','any',NULL,'各ターンにつき1回(アーミー),任意の射撃フェイズ','Special','【宣言】：このターンにこのユニットの精魂の焔の攻撃でダメージ点を割り当てられた敵ユニット1個を対象に選ぶ。\r\n【効果】：このバトルの残りの間、対象は燃魂のキーワードを得る。','Declare: Pick an enemy unit that had any damage points allocated to it this turn by attacks made with this unit’s Spirit-scouring Flames to be the target.\n\nEffect: The target has the SOULBURNED keyword for the rest of the battle.','RANPAGE','Enemies engulfed by Cthorak’s pale-flame breath feel their very souls begin to burn away.'),
	(171,'燃ゆる魂','Soulburned',NULL,NULL,NULL,'Offensive','active','unlimited','unit','shooting','any',NULL,'任意の射撃フェイズ','Offensive','【宣言】：このアビリティはこのユニットが破壊されていても使用できる。任意の数の燃魂の敵ユニットを対象に選ぶ。\r\n【効果】：各対象についてD3を振る。2+の場合、その出目に等しい致命的ダメージを対象に与える。','Declare: This unit can use this ability even if it has been destroyed. Pick any number of SOULBURNED enemy units to be the targets.\n\nEffect: Roll a D3 for each target. On a 2+, inflict an amount of mortal damage on the target equal to the roll.',NULL,'Once an enemy has been burnt by Cthorak’s scouring flames, their soul ignites deep within their body.'),
	(172,'雷光の嵐','Lightning Tempest',NULL,NULL,NULL,'Offensive','active','unlimited','unit','hero','your',NULL,'自軍側ヒーローフェイズ','Offensive','【宣言】：このユニットから12インチ以内の、視認可能な敵ユニット1個を対象に選び、D6の祈願ロールを行う。\r\n【効果】：そのユニットにD3点の致命的ダメージを与え、その後ダイスを1個振る。1〜2なら手順は終了する。3+の場合、そのユニットから3mv以内に一部でも入っている別の敵ユニット1個を落雷の対象に選び、D3点の致命的ダメージを与える。詠唱ロールが10以上だった場合、3mvではなく6mv以内を選べる。手順が終了するか、落雷の対象にできる他の敵ユニットがいなくなるまで、この方法でダイスを振り続ける。1個のユニットは1ターンに2回以上落雷の対象にはできない。','Declare: Pick a visible enemy unit within 12\" of this unit to be struck by lightning, then make a chanting roll of D6.\n\nEffect: Inflict D3 mortal damage on that unit, then roll a dice. On a 1-2, the sequence ends. On a 3+, pick another enemy unit within 3\" of that unit to be struck by lightning and inflict D3 mortal damage on it. If the chanting roll was 10+, you can pick another enemy unit within 6\" of that unit instead of 3\". Keep rolling dice in this way until the sequence ends or there are no other enemy units eligible to be struck by lightning. A unit cannot be struck by lightning more than once per turn.','祈祷','イオヌスはシグマーの力を招来し、アズィルの憤怒を解き放つ。'),
	(175,'正義の使者','Heralds of Righteousness',NULL,NULL,NULL,'Passive','passive','unlimited','unit',NULL,'any',NULL,'パッシブ','Movement','【効果】：このユニットの突撃ロールで振るダイスの数を1個増やす(最大3個)。','Effect: Add 1 to the number of dice rolled when making charge rolls for this unit, to a maximum of 3.',NULL,'Prosecutors cross the battlefield in a blur of light.'),
	(180,'名誉の死の獲得','Earn an Honourable Death',NULL,NULL,NULL,'Offensive','reaction','unlimited','unit','combat','any',NULL,'リアクション：このユニットが近接攻撃アビリティを宣言','Offensive','【効果】：このターンに近接攻撃アビリティを使用しておらず、このユニットの近接範囲内にいる、英雄ではない味方ルイネーション・チャンバー・歩兵ユニット1個を対象に選ぶ。このユニットの近接攻撃アビリティの解決直後に、対象は近接攻撃アビリティを使用できる。そうする場合このターンの残りの間、選択した味方ユニットのヒットロールを+1する。','Effect: Pick a friendly non-HERO RUINATION CHAMBER INFANTRY unit that has not used a FIGHT ability this turn and is within this unit’s combat range to be the target. The target can be picked to use a FIGHT ability immediately after the FIGHT ability used by this unit has been resolved. If it is picked to do so, add 1 to hit rolls for the target’s attacks for the rest of the turn.',NULL,'The Lord-Terminos accompanies their Ruination chamber, the Stormcasts seeking to earn their final rest by proving themselves one last time in battle.'),
	(181,'メモリアン','Memorian',NULL,NULL,NULL,'Passive','passive','unlimited','unit',NULL,'any',NULL,'パッシブ','Control','【効果】：このユニットのメモリアンはトークンである。メモリアンが戦場にある間、このユニットから12mv以内に全体がいる味方ルイネーション・チャンバーユニットの確保力を+3する。\r\n\r\nこのユニットの修正前セーブロールで1を出した場合、アタックアビリティの解決後にメモリアンを戦場から取り除く(ダメージ点は与えられる)。','Effect: This unit’s Memorian is a token. Add 3 to the control scores of friendly RUINATION CHAMBER units wholly within 12\" of this unit while its Memorian is on the battlefield.\n\nIf you make an unmodified save roll of 1 for this unit, remove its Memorian from the battlefield after the Attack ability has been resolved (the damage point is still inflicted).',NULL,'Memorians serve to remind soul-damaged Stormcasts of their humanity.'),
	(182,'審判の執行','Deliver Judgement',NULL,NULL,NULL,'Once Per Battle (Army)','active','once_per_battle','army','combat','any',NULL,'バトル中1回限り(アーミー)、任意の近接フェイズ','Offensive','【宣言】：このユニットから12mv以内に全体がいる英雄ではない、味方ルイネーション・チャンバーユニット1個を対象に選ぶ。\r\n【効果】：対象はこのフェイズに近接攻撃アビリティを2回使用できる。ただし1回目の使用後、対象はこのターンの残りの間、後手効果を得る。','Declare: Pick a friendly non-HERO RUINATION CHAMBER unit wholly within 12\" of this unit to be the target.\n\nEffect: The target can use 2 FIGHT abilities this phase. After the first is used, however, the target has Strike-last for the rest of the turn.',NULL,'The Lord-Vigilant orders their soul-hollowed brethren to deliver Sigmar\'s wrath unto their enemies.'),
	(183,'メモリアンの末裔','Memorian Descendants',NULL,NULL,NULL,'Passive','passive','unlimited','unit',NULL,'any',NULL,'パッシブ','Special','【効果】：このユニットのメモリアンはトークンである。このユニット3体ごとにメモリアンが2体ある。このユニットのメモリアンのいずれかが戦場にある間、「殲滅者」アビリティ使用時の抵抗ロールに+1する。\r\n\r\nこのユニットの修正前セーブロールで1を出すたび、アタックアビリティの解決後にメモリアンを1体取り除く(ダメージ点は与えられる)。','Effect: This unit’s Memorians are tokens. There are 2 Memorians for every 3 models in this unit. While any of this unit’s Memorians are on the battlefield, add 1 to this unit’s resistance rolls when using the ‘Ruination Chamber’ ability. \n\nEach time you make an unmodified save roll of 1 for this unit, remove 1 of its Memorians from the battlefield after the ATTACK ability has been resolved (the damage point is still inflicted).',NULL,'Reclusians are accompanied by Memorians who serve to remind them of their humanity.'),
	(201,'ワープストーンの狙撃手','Warpstone Snipers',NULL,NULL,NULL,'Shooting','active','unlimited','unit','shooting','your',NULL,'自軍側遠隔フェイズ','Shooting','【効果】：このユニットがこのターンに移動アビリティを使用しておらず、このターンに配置されていない場合、このターンの残りの間、このユニットのワープロック・ジェザイルの射程を+6mvする。','Effect: If this unit has not used a MOVE ability this turn and was not set up this turn, add 6\" to the Range characteristic of this unit’s Warplock Jezzails for the rest of the turn.',NULL,'Having set up the Warplock Jezzail in position, the gunner can wait for the perfect moment to fire their shot.'),
	(202,'殺気だった大群','Seething Swarm',NULL,NULL,NULL,'Rallying','active','unlimited','unit','end','any',NULL,'任意のターン終了時','Rallying','【効果】：このユニットに撃破されたモデルをD3体復帰させられる。','Effect: You can return D3 slain models to this unit.',NULL,'Clanrats overwhelm their enemies with their seemingly endless numbers – biting, stabbing and trampling their own fallen beneath their bloody claws.'),
	(211,'姿見せぬ襲撃者','Shadowy Killers',NULL,NULL,NULL,'Passive','passive','unlimited','unit',NULL,'your',NULL,'パッシブ','Defensive','【効果】：このユニットを対象とする近接攻撃の修正前の命中ロールが1〜4の場合、その攻撃は失敗し、攻撃手順は終了する。','Effect: If the unmodified hit roll for an attack that targets this unit is 1-4, the attack fails and the attack sequence ends.',NULL,'Ulguan illusions and other tools of misdirection shroud Deathmasters in perpetual darkness, keeping them from harm.'),
	(212,'疾走する死の使い','Running Death',NULL,NULL,NULL,'Passive','passive','unlimited','unit',NULL,'your',NULL,'パッシブ','Special','【効果】：味方エシン・ユニットがこのユニットから13mv以内に全体がいる間、それらは同じターンに全力移動アビリティを使用していても、遠隔攻撃および/または突撃アビリティを使用できる。','Effect: While friendly ESHIN units are wholly within 13\" of this unit, they can use SHOOT and/or CHARGE abilities even if they used a RUN ability in the same turn.',NULL,'Deathmasters lead only the nimblest Eshin agents to war.'),
	(213,'歪み石の破片','Warpstone Shards',NULL,NULL,NULL,'Special','active','unlimited','unit','hero','your',NULL,'自軍側ヒーローフェイズ','Special','【効果】：このフェイズ中、このユニットの次の詠唱ロールは2D6の代わりに3D6を振る。このロールは振り直しも修正もできない。\r\n\r\n詠唱ロールが13の場合、その呪文は成功し、相殺されない。その呪文の効果の解決後、このユニットにD3点の致命的ダメージを与える。\r\n\r\n詠唱ロールが13でない場合、好きなダイスを1個取り除き、残りの2D6を詠唱ロールとする。','Effect: The next time you make a casting roll for this unit this phase, roll 3D6 instead of 2D6. This roll cannot be re-rolled or modified.\n\nIf the casting roll is 13, the spell is successfully cast and cannot be unbound. After the effect of that spell has been resolved, inflict D3 mortal damage on this unit.\n\nIf the casting roll is not 13, remove 1 dice of your choice from the casting roll and use the remaining 2D6 as the casting roll.',NULL,'Grey Seers consume potentially lethal shards of warpstone to enhance their spellcasting.'),
	(219,'一流スナイパー','Sniper-master',NULL,NULL,NULL,'Once Per Turn (Army)','active','once_per_turn','army','shooting','your',NULL,'各ターンにつき1回(アーミー)、自軍側遠隔フェイズ','Shooting','【宣言】：視認可能な敵英雄1個を対象に選ぶ。\r\n【効果】：このターンの残りの間、このユニットと、このユニットから13インチ以内に全体がいる味方ワープロック・ジェザイル・ユニットは、射撃攻撃の対象を選ぶ際に「護られし英雄」アビリティの効果を無視できる。','Declare: Pick a visible enemy HERO to be the target.\n\nEffect: For the rest of the turn, this unit and friendly Warplock Jezzails units wholly within 13\" of this unit can ignore the effects of the ‘Guarded Hero’ ability when picking the target for their shooting attacks.',NULL,'No enemy is safe from the warp-laced bullets of a Warlock Engineer and his underlings.'),
	(220,'モッと、モッと歪みのエネルギーを！','More-more Warp Energy!',NULL,NULL,NULL,'Shooting','reaction','unlimited','unit','shooting','your',NULL,'(遠隔攻撃アビリティを宣言)このターン中に移動アビリティを使用しておらず、かつ戦場に配置されていない場合可能','Shooting','【効果】：ダイスを1個振る。2+の場合、このターンの残りの間、このユニットのワープロック・マスケットのダメージ量を3にする。1の場合、このユニットにD3点の致命的ダメージを与える。','Effect: Roll a dice. On a 2+, set the Damage characteristic of this unit’s Warplock Musket to 3 for the rest of the turn. On a 1, inflict D3 mortal damage on this unit.',NULL,'The Engineer overcharges their weapon with volatile energy.'),
	(248,'グラップリングフック','Grappling Hooks',NULL,NULL,NULL,'Once Per Turn (Army)','active','once_per_turn','army','movement','your',NULL,'各ターンにつき1回(アーミー)、自軍側移動フェイズ','Movement','【宣言】：このユニットがこのターンに配置されていない場合、このユニットから3mv以内に一部でも入っている地形1つを対象に選ぶ。\r\n【効果】：このユニットを戦場から取り除き、対象から3mv以内に全体が入り、かつ全ての敵ユニットから3mvより遠く離れた位置に再配置する。','Declare: If this unit was not set up this turn, pick a terrain feature within 3\" of this unit to be the target.\n\nEffect: Remove this unit from the battlefield and set it up again wholly within 3\" of the target and more than 3\" from all enemy units.',NULL,'Night Runners are experts at scaling vertical terrain with their grapnels, either to ambush an unwary foe or retreat to where the enemy cannot follow.'),
	(249,'発煙弾','Smoke Bombs',NULL,NULL,NULL,'Once Per Turn (Army)','active','once_per_turn','army','combat','any',NULL,'各ターンにつき1回(アーミー)、任意の近接フェイズ','Movement','【宣言】：このユニットが交戦中、またはこのターンに突撃した場合、このユニットは接敵移動を行える。その後、このユニットが交戦中の場合、このユニットの攻撃の対象として敵ユニットを1個以上選ばなければならない。\r\n【効果】：近接攻撃を解決する。その後、このユニットが交戦中の場合、ダイスを1個振る。3+の場合、このユニットは移動力までの距離を移動できる。敵ユニットの近接範囲内を通過できるが、その移動を近接状態で終えられるのは、移動開始時に交戦していたユニットとの間のみである。近接状態で移動を終える必要はない。','Declare: If this unit is in combat or charged this turn, this unit can make a pile-in move. Then, if this unit is in combat, you must pick 1 or more enemy units to be the target(s) of this unit’s attacks.\n\nEffect: Resolve combat attacks against the target unit(s). Then, if this unit is in combat, roll a dice. On a 3+, this unit can move a distance up to its Move characteristic. It can pass through the combat ranges of enemy units but can only end that move in combat with units it was in combat with at the start of that move. It does not have to end the move in combat.','コア、アタック、近接攻撃','The Clans Eshin use smoke bombs to cause confusion and disorientate the enemy.'),
	(251,'歪みの激怒の解放','Unleashed Warp-fury',NULL,NULL,NULL,'Offensive','active','unlimited','unit','combat','any',NULL,'任意の近接フェイズ','Offensive','【効果】：このユニットにD3点の致命的ダメージを与える。その後、このターンの残りの間、このユニットの近接武器の攻撃回数に+1する。','Effect: Inflict D3 mortal damage on this unit. Then, add 1 to the Attacks characteristic of this unit’s melee weapons for the rest of the turn.',NULL,'The warpstone hammered into the flesh of these creatures crackles with volatile energies, driving them into a frenzy.'),
	(261,'圧倒的な火力','Overwhelming Fire',NULL,NULL,NULL,'Passive','passive','unlimited','unit',NULL,'any',NULL,'パッシブ','Shooting','【効果】：10体以上のモデルを持つ敵ユニットを対象とする、このユニットの射撃攻撃のヒットロールに+1する。','Effect: Add 1 to hit rolls for this unit’s shooting attacks that target an enemy unit that has 10 or more models.',NULL,'Large enemy formations are decimated by the sheer volume of fire a Ratling Warpblaster unleashes upon them.'),
	(262,'ワープストーンをモッと浴びせチマエ！','More-more Warpstone Bullets!',NULL,NULL,NULL,'Once Per Turn (Army)','active','once_per_turn','army','shooting','your',NULL,'各ターンにつき1回(アーミー)、自軍側遠隔フェイズ','Shooting','【効果】：このユニットが味方スクリール英雄の近接範囲内に一部でも入っている場合、このターンの残りの間、このユニットのワープストーン弾の嵐の攻撃回数は3D6+3ではなく6D6+3となる。\r\n\r\nただし、ワープストーン弾の嵐の攻撃の修正前ヒットロールで1を出すごとに、遠隔攻撃アビリティの解決後にこのユニットに1点の致命的ダメージを与える。','Effect: If this unit is within the combat range of a friendly SKRYRE HERO, this unit’s Hail of Warpstone Bullets has an Attacks characteristic of 6D6+3 instead of 3D6+3 for the rest of the turn. \n\nHowever, for each unmodified hit roll of 1 for an attack made with this unit’s Hail of Warpstone Bullets, inflict 1 mortal damage on this unit after the SHOOT ability has been resolved.',NULL,'Under the dubious supervision of the Warlocks of Clans Skryre, the gunner cranks up the Ratling Warpblaster’s velocity to the max.'),
	(273,'死の狂乱','The Death Frenzy',NULL,'8','prayer','Special','active','unlimited','unit','hero','any',NULL,'任意のヒーローフェイズ','Special','【宣言】：このユニットから13インチ以内の視認可能な敵ユニット1個、またはこのユニットから13インチ以内の視認可能な友軍SKAVEN INFANTRYユニット1個を対象に選び、D6で詠唱ロールを行う。\r\n【効果】：対象が敵ユニットの場合、次の自分のターン開始時まで、対象の白兵武器の攻撃数特性に-1する。\r\n\r\n対象が友軍ユニットの場合、このターンにFIGHTアビリティを2回使用できる。ただし1回目の使用後、対象はこのターンの残りの間、後手攻撃(STRIKE-LAST)を得る。\r\n\r\n詠唱ロールが12以上だった場合、1個ではなく2個の対象を選べる。','Declare: Pick a visible enemy unit within 13\"of this unit or a visible friendly SKAVEN INFANTRY unit within 13\" of this unit to be the target, then make a chanting roll of D6.\n\nEffect: If the target is an enemy unit, subtract 1 from the Attacks characteristic of the target’s melee weapons until the start of your next turn.\n\nIf the target is a friendly unit, it can use 2 FIGHT abilities this turn. After the first is used, however, the target has STRIKE-LAST for the rest of the turn.\n\nIf the chanting roll was 12+, you can pick 2 targets instead of 1.',NULL,'Such is the force of Vizzik\'s screeching oratory that the verminous hordes froth and spasm under his influence as mere mortals unravel to visions of their inevitable doom.'),
	(274,'角ある鼠の預言者','Prophet of the Horned Rat',NULL,NULL,NULL,'Once Per Battle','active','once_per_battle','army','hero','your',NULL,'バトル中1回、自軍側ヒーローフェイズ','Special','【効果】：次の自分のターン開始時まで、このユニットの詠唱ロールを振り直せる。','Effect: You can re-roll chanting rolls for this unit until the start of your next turn.',NULL,'Vizzik endlessly schemes to extend his god\'s influence - alongside his own.'),
	(275,'齧りの眼差し','Gaze of the Gnaw',NULL,NULL,NULL,'Once Per Turn (Army)','active','once_per_turn','army','combat','any',NULL,'各ターンにつき1回(アーミー)、任意の戦闘フェイズ','Defensive','【宣言】：このユニットと交戦中の敵ユニット1個を対象に選ぶ。\r\n【効果】：ダイスを1個振る。2+の場合、このターンの残りの間：\r\n・対象がFIGHTアビリティを使用するよう選ばれた際にこのユニットが対象と交戦している場合、対象の全ての攻撃はこのユニットを対象としなければならない。\r\n・このユニットを対象とする対象の白兵攻撃の命中ロールと負傷ロールに-1する。','Declare: Pick an enemy unit in combat with this unit to be the target.\n\nEffect: Roll a dice. On a 2+, for the rest of the turn:\nIf this unit is in combat with the target when the target is picked to use a FIGHT ability, all of the target’s attacks must target this unit.\nSubtract 1 from hit rolls and wound rolls for the target’s combat attacks that target this unit.',NULL,'To catch Vizzik\'s gaze is to be driven from one\'s mind by agonising raptures.'),
	(276,'現実の亀裂','Fissures In Reality',NULL,NULL,NULL,'Offensive','active','unlimited','unit','combat','any',NULL,'任意の戦闘フェイズ','Offensive','【宣言】：このユニットと交戦中の各敵ユニットを対象に選ぶ。\r\n【効果】：各対象についてD3を振る。2+の場合、その出目に等しい致命的ダメージを対象に与える。','Declare: Pick each enemy unit in combat with this unit to be the targets.\n\nEffect: Roll a D3 for each target. On a 2+, inflict an amount of mortal damage on the target equal to the roll.',NULL,'Those who resist Vizzik\'s will are swallowed by fang-mouthed fissures that wrench open in his presence.'),
	(277,'窮鼠の乱撃','Cornered Rat — Clawlord on Gnaw-beast',NULL,NULL,NULL,'Passive','passive','unlimited','unit',NULL,'your',NULL,'パッシブ','Offensive','【効果】：このユニットが損傷している間、ワープフォージド・ハルバードの攻撃回数に+3する。','Effect: While this unit is damaged, add 3 to the Attacks characteristic of its Warpforged Halberd.',NULL,'A Clawlord in fear for their life fights with rabid ferocity.'),
	(278,'残酷な指揮官','Cruel Commander',NULL,NULL,NULL,'Control','active','unlimited','unit','hero','your',NULL,'自軍側ヒーローフェイズ','Control','【宣言】：このユニットから13インチ以内に全体がいる英雄ではない味方ヴァーミヌス・歩兵ユニット1個を対象に選ぶ。\r\n【効果】：対象に1点の致命的ダメージを与える。その後、次の自分のターン開始時まで、対象の確保力を+5する。','Declare: Pick a friendly non-HERO VERMINUS INFANTRY unit wholly within 13\" of this unit to be the target.\n\nEffect: Inflict 1 mortal damage on the target. Then, add 5 to the target’s control score until the start of your next turn.',NULL,'The Clawlord strikes down one of their underlings to show their cruel power and then, with shrieks and hissed threats, commands their swarming underlings to overrun the foe.'),
	(288,'暗殺者の頂点','Master of Assassins',NULL,NULL,NULL,'Passive','passive','unlimited','unit',NULL,'your',NULL,NULL,'Offensive','【効果】：敵英雄を対象とする攻撃について、このユニットの近接武器のダメージ量を2倍にする。','Effect: Double the Damage characteristic of this unit’s melee weapons for attacks that target an enemy HERO.',NULL,'Crixxit is one of the most feared assassins of the Clans Eshin, if not the realms entire.'),
	(289,'陽動攻撃','Diversionary Assault',NULL,NULL,NULL,'Movement','active','unlimited','unit','charge','your',NULL,'自軍側突撃フェイズ','Movement','【宣言】：このユニットから13mv以内に全体がいる、視認可能な味方ガッターランナー・ユニット1個を対象に選ぶ。\r\n【効果】：このターンの残りの間、対象の突撃ロールに+2する。','Declare: Pick a visible friendly Gutter Runners unit wholly within 13\" of this unit to be the target.\n\nEffect: Add 2 to charge rolls for the target for the rest of the turn',NULL,'At a swift signal, be it a twitch of his tail, a deft claw-swipe or a hissed threat, Crixxit directs a group of his agents to launch a blistering diversionary attack to distract the guard of his intended target.'),
	(290,'影血の外套','Shadowblood Cloak',NULL,NULL,NULL,'Movement','active','unlimited','unit','combat','any',NULL,'任意の近接フェイズ','Movement','【宣言】：2体以上のモデルを持ち、このユニットから18インチ以内に全体がいる、視認可能な味方エシン・ユニット1個を対象に選ぶ。\r\n【効果】：ダイスを1個振る。3+の場合、このユニットを戦場から取り除き、対象から6インチ以内に全体が入るように再配置する。このユニットは近接戦闘状態で配置してもよい。','Declare: Pick a visible friendly ESHIN unit that has 2 or more models and that is wholly within 18\" of this unit to be the target.\n\nEffect: Roll a dice. On a 3+, remove this unit from the battlefield and set it up again wholly within 6\" of the target. This unit can be set up in combat.',NULL,'Crixxit’s cloak grants him the ability to melt into the darkness and instantly strike wherever a pack of his many agents cast their slinking shadows.'),
	(291,'ボム・ラット','Bomb Rats',NULL,NULL,NULL,'Once Per Turn (Army)','active','unlimited','unit',NULL,'your',NULL,NULL,'Special','【宣言】：このユニットのBomb Ratsはトークンである。敵ユニットの隣にある友軍Bomb Ratが2個未満の場合、このユニットと交戦中で、友軍Bomb Ratが隣にない敵ユニット1個を対象に選ぶ。\r\n【効果】：このユニットのBomb Ratを対象の隣に置く。','Declare: This unit’s Bomb Rats are tokens. If there are fewer than 2 friendly Bomb Rats next to enemy units, pick an enemy unit in combat with this unit that does not have a friendly Bomb Rat next to it to be the target.\n\nEffect: Place this unit’s Bomb Rat next to the target.',NULL,'Bomb Rats skitter amidst the enemy lines, largely unnoticed in the confusion of battle.'),
	(292,'起爆','Detonate',NULL,NULL,NULL,'Offensive','active','unlimited','unit',NULL,'your',NULL,NULL,'Offensive','【宣言】：このアビリティはこのユニットが破壊されていても使用できる。友軍Bomb Ratトークンを持つ各敵ユニットを対象に選ぶ。\r\n【効果】：対象の白兵範囲内にいる各敵ユニットについてD3を振る。2+の場合、その出目に等しい致命的ダメージをその敵ユニットに与える。その後、対象のBomb Ratを取り除く。','Declare: This unit can use this ability even if it has been destroyed. Pick each enemy unit that has a friendly Bomb Rat token to be a target.\n\nEffect: Roll a D3 for each enemy unit within a target’s combat range. On a 2+, inflict an amount of mortal damage on that enemy unit equal to the roll. Then, remove the target’s Bomb Rat.',NULL,'Many Gutter Runners are experts in the use of improvised explosives, deploying skittering Bomb Rats to rush the enemy and detonate amongst them in a burst of warpstone-fuelled fury.'),
	(293,'STEP INTO THE STORM',NULL,NULL,NULL,NULL,NULL,'active','unlimited','unit','movement','your',NULL,'自軍側移動フェイズ','Movement','【宣言】： この陣営地形の6mv以内に完全に入っていて、近接戦闘中ではなく、大型獣ではない味方ストームキャスト・エターナルユニット1個を対象として選択する。\r\n【効果】： 対象を戦場から取り除き、すべての敵ユニットから9mvより遠く離れた戦場に再び配置する。',NULL,NULL,'Stormreach Portals are used in battle to redirect the warriors of the Stormcast Eternals to where the fighting is fiercest.'),
	(294,'魔術的強化（ARCANE ENHANCEMENT）',NULL,NULL,NULL,NULL,'passive','active','unlimited','unit',NULL,'your',NULL,'パッシブ',NULL,'【効果】：「秘術の台座を召喚する」能力の対象として選ばれたユニットが、この顕現の台座の上にいる間、以下の効果を得る：\r\n\r\n・そのユニットは「加護（5+）」と「飛行」を得る。\r\n\r\n・そのユニットが〈魔術師〉である場合、そのユニットのパワーレベルに+1する。\r\n\r\n・そのユニットは「移動」アビリティを使用することができない。また、そのユニットが移動を行うたびに、この顕現の上に留まるものとする。\r\n\r\n・そのユニットに対する（あるいはそのユニットからの）射程や視線を測定する際、代わりにこの顕現を基準に測定すること。\r\n\r\n・この顕現は、〈追放〉アビリティ以外の能力の対象として選ぶことはできない。\r\n\r\n・そのユニットが「接敵移動」を行う対象として選ばれた場合、代わりにこの顕現を移動させること。\r\n\r\nこの顕現が追放された場合、戦場から取り除く前に、その台座の上にいたユニットを、この顕現から3mv以内の戦場に配置すること。そのユニットは、この顕現が追放された時点で既に接敵状態にあったユニットとのみ、接敵状態で配置できる。もしそのユニットを配置することが不可能な場合、そのユニットは破壊される。\r\n\r\n台座の上のユニットが戦場から取り除かれた場合、直ちにこの顕現をプレイから取り除くこと。',NULL,NULL,'Dais Arcanum（魔術の台座）」の上に召喚されたストームキャスト・エターナルは、アジールの天界のエネルギーが己の中を駆け巡るのを感じる。'),
	(295,'魔術の旋風（TORNADO OF MAGIC）',NULL,NULL,NULL,NULL,NULL,'active','unlimited','unit',NULL,'your',NULL,NULL,NULL,'【効果】：敵ユニットがこのマニフェステーション（顕現）から12\"以内の距離にいる間、その敵ユニットが行う射撃攻撃のヒットロールから1を引く。',NULL,NULL,'Celestian Vortex（天界の渦）の周囲を渦巻く激しい気流は、敵の遠距離部隊の照準を乱し、放たれた射撃兵器の飛行を妨げる。'),
	(296,'灼熱の報復（BURNING VENGEANCE）',NULL,NULL,NULL,NULL,NULL,'active','unlimited','unit',NULL,'your',NULL,NULL,NULL,'【宣言】：このマニフェステーション（顕現）がこのフェイズ中に召喚された場合、このマニフェステーションから6\"以内の敵ユニットを任意の数だけ対象として選ぶ。\r\n\r\n【効果】：各対象に対してD3をロールすること。2以上の出目が出た場合、その出目の数に等しい致命的ダメージをその対象に与える。',NULL,NULL,'Everblaze Comet（常燃の彗星）が凄まじい勢いで戦場に激突し、その光り輝く残骸が致死的なアジールのエネルギーのコロナを放射する。'),
	(297,'魔術の妨害（ARCANE DISRUPTION）',NULL,NULL,NULL,NULL,'Passive','active','unlimited','unit',NULL,'your',NULL,NULL,NULL,'【効果】：敵の〈ウィザード〉がこのマニフェステーション（顕現）から12\"以内にいる間、そのウィザードが行う詠唱ロールから1を引く。',NULL,NULL,'Everblaze Comet（常燃の彗星）から放たれるエマネーション（放射）が、付近のウィザードの魔術的アビリティを混乱させる。'),
	(298,'移動する大地',NULL,NULL,NULL,NULL,'Passive','passive','unlimited','unit',NULL,'your',NULL,'パッシブ','Offensive','【効果】：兵はこの特殊地形を通り抜けることができる。ただしこの地形のいかなる部分でも移動を完了できない。\r\n敵ユニットがこの特殊地形を通り抜けたり、乗り越えた移動を完了するたびに、D3を1個ロールする。2+であればロール結果に等しい数の致命的ダメージを受ける。',NULL,NULL,NULL),
	(299,'現実の帳を貫くトンネル',NULL,NULL,NULL,NULL,'Movement','active','unlimited','unit','movement','your',NULL,'自軍側移動フェイズ','Movement','【宣言】：この特殊地形の6mv以内に全体が入っており、かつ近接戦闘中ではない味方スケイヴン・ユニットを1個選択する。\r\n\r\n【効果】：選択された味方ユニットを戦場から取り除き、別の齧り穴の6mv以内に全体が入るように、かつあらゆる敵ユニットから9mvより遠く離れている位置に再配置する。',NULL,NULL,NULL),
	(300,'終わりなき大鼠波',NULL,1,NULL,NULL,'Once Per Turn','active','once_per_turn','army','end','your',NULL,'各ターンにつき1回(アーミー)、自軍側ターン終了時','Control','【宣言】：全滅している英雄ではない味方スケイヴン・歩兵ユニットを1個選択する。\r\n\r\n【効果】：端数切り上げの半数で構成された代替ユニットを味方齧り穴の6mv以内に全体が入るように、かつあらゆる敵ユニットから3mvより遠く離れた位置に配置する。',NULL,NULL,NULL),
	(301,'マルチパーツ',NULL,NULL,NULL,NULL,'Passive','active','unlimited','unit',NULL,'your',NULL,'パッシブ',NULL,'【効果】：この顕現の体力以上のダメージが割り振られた場合、この権限は全滅し、全てのパーツが取り除かれる。',NULL,NULL,NULL),
	(302,'ワープライトニング・ボルト',NULL,NULL,NULL,NULL,'Hero','active','unlimited','unit',NULL,'your',NULL,'任意のヒーローフェイズ',NULL,'【宣言】：この顕現がこのターン中に戦場に配置されていない場合、この顕現の6mv以内に一部でも入っている各敵ユニットを選択する。\r\n\r\n【効果】：選択された各敵ユニットに対して、それぞれダイスを1個ロールする。ロール結果が4+であれば、ロール対象はD3ポイントの致命的ダメージを受ける。',NULL,NULL,NULL),
	(303,'歪みの渦',NULL,NULL,NULL,NULL,'Passive','active','unlimited','unit',NULL,'your',NULL,'パッシブ',NULL,'【効果】：この顕現の6mv以内に一部でも入っている間、敵ユニットの全力移動ロールと突撃ロールは-2の修正を受ける。さらに敵ユニットがこの顕現を乗り越えた場合、その移動アビリティが解決された後に、その敵ユニットはD3ポイントの致命的ダメージを受ける。',NULL,NULL,NULL),
	(304,'モッと、モッと鼠を！',NULL,NULL,NULL,NULL,'Defensive','active','unlimited','unit',NULL,'your',NULL,'任意のターン終了時',NULL,'【効果】：この顕現を回復(D6)する。',NULL,NULL,NULL),
	(305,'鳴り響く破滅',NULL,NULL,NULL,NULL,'Passive','active','unlimited','unit',NULL,'your',NULL,'パッシブ',NULL,'【効果】：味方スケイヴン・歩兵ユニットがこの顕現の13mv以内に全体が入っている間、その味方ユニットを対象とする攻撃のウーンズロールは-1の修正を受ける。',NULL,NULL,NULL);

/*!40000 ALTER TABLE `m_ability_master` ENABLE KEYS */;
UNLOCK TABLES;


# テーブルのダンプ m_artefacts_of_power
# ------------------------------------------------------------

DROP TABLE IF EXISTS `m_artefacts_of_power`;

CREATE TABLE `m_artefacts_of_power` (
  `id` int NOT NULL AUTO_INCREMENT,
  `faction_id` int DEFAULT NULL,
  `category` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL COMMENT 'Artefacts of the Tempest / Relics of Sigmaron',
  `source_reference` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL COMMENT 'このルールの参照元',
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `points` int DEFAULT '0',
  `is_hero_only` tinyint(1) DEFAULT '1',
  `trigger_condition_ja` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci COMMENT '発動タイミング',
  `ability_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL COMMENT 'Passive / Active / Reaction',
  `activation` enum('active','passive','reaction') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'active',
  `usage_scope` enum('unlimited','once_per_turn','once_per_phase','once_per_battle') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'unlimited',
  `usage_per` enum('unit','army') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'unit',
  `trigger_phase` set('deployment','hero','movement','shooting','charge','combat','end','any') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `trigger_turn` enum('your','opponent','any','battle') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'your',
  `icon_type` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `effect` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `flavor_text` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `note` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci COMMENT 'デザイナーズノートなど',
  `is_hidden` tinyint NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_ap_faction` (`faction_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='アーティファクト・パワー管理';

LOCK TABLES `m_artefacts_of_power` WRITE;
/*!40000 ALTER TABLE `m_artefacts_of_power` DISABLE KEYS */;

INSERT INTO `m_artefacts_of_power` (`id`, `faction_id`, `category`, `source_reference`, `name`, `points`, `is_hero_only`, `trigger_condition_ja`, `ability_type`, `activation`, `usage_scope`, `usage_per`, `trigger_phase`, `trigger_turn`, `icon_type`, `effect`, `flavor_text`, `note`, `is_hidden`)
VALUES
	(1,1,'Artefacts of the Tempest','嵐の神器','無効化のペンダント NULL PENDANT',0,1,'バトルにつき1回、任意のターン終了時','Active','active','once_per_battle','unit','end','any','Controll','【宣言】：このユニットと戦闘中の敵ユニット1体を選択。\n【効果】：ターン終了時まで、対象のコントロールスコアを-5する。','この闇のアミュレットは敵の決意を削ぐ魔力を秘めている。',NULL,0),
	(2,1,'Artefacts of the Tempest','嵐の神器','水銀の霊薬 QUICKSILVER DRAUGHT',0,1,'バトルにつき1回、任意の近接フェイズ','Active','active','once_per_battle','unit','combat','any','Offensive','【効果】：ターン終了時まで、このユニットは「先手攻撃（STRIKE-FIRST）」を得る。','この薬は飲んだ者に不可解な速度を与える。',NULL,0),
	(3,1,'Artefacts of the Tempest','嵐の神器','鏡の盾 MIRRORSHIELD',0,1,'パッシブ','Passive','passive','unlimited','unit',NULL,'any','Defensive','【効果】：攻撃するモデルが9インチ以内にいない限り、このユニットは射撃攻撃の対象にできない。','この盾はハイシュの光を反射し、敵の狙撃手の目を眩ませる。',NULL,0),
	(4,1,'Relics of Sigmaron','グューランの禍事','シグマーの軍旗 BANNER OF SIGMAR',0,1,'Once Per Battle, Your Hero Phase','Active','active','unlimited','unit',NULL,'your',NULL,'【宣言】：18インチ以内の作戦目標1つを選択。\n【効果】：次の自軍ターン開始時まで、敵ユニットがその目標を争奪していない場合、敵はその目標をコントロールできない。','英雄が巨大な軍旗を掲げ、彗星の如き勢いで地面に突き立てる。',NULL,1),
	(5,1,'Relics of Sigmaron','グューランの禍事','モルダの印章 SIGIL OF MORRDA',0,1,'Once Per Turn, Reaction','Reaction','active','unlimited','unit',NULL,'your',NULL,'【トリガー】：戦闘中の敵ユニットがコマンドまたは「狂奔（RAMPAGE）」アビリティを宣言した時。\n【効果】：ダイスを振る。3+の場合、その能力は効果を発揮しない（使用回数・CPは消費される）。','死神モルダの印。かつての力は失われたとはいえ、今なお恐るべき力を秘めている。',NULL,1),
	(6,1,'Relics of Sigmaron','グューランの禍事','アズィルの篝火 BEACON OF AZYR',0,1,'Once Per Battle, Any Hero Phase','Active','active','unlimited','unit',NULL,'your',NULL,'【宣言】：12インチ以内の味方「ストームキャスト・エターナル」「ウィザード」または「プリースト」1体を選択。\n【効果】：対象が次に使用する祈祷または呪文の詠唱ロールは、ダイスを振る代わりに自動的に「7」となる。その後、対象の儀式ポイントを0にする。','この灯台は天界領域の魔力に満ちており、聖なる稲妻を解き放つ。',NULL,1),
	(8,10,'RELICS OF RUIN',NULL,'ファウルハイド (Foulhide)',0,1,'任意のターンの終了時',NULL,'active','unlimited','unit','end','any',NULL,'効果：このユニットを（D3）回復する。','ラット・オゴアの剥ぎ取られた肉から作られ、まとわりつくような錬金術の薬剤に浸されたこの悪臭を放つ鎧は、膨れ上がった第二の皮膚のように着用者に張り付き、切り裂かれるのと同じ速さで再生する。',NULL,0),
	(9,10,'RELICS OF RUIN',NULL,'ワープストーン・チャーム (Warpstone Charm)',0,1,'任意の近接フェイズ',NULL,'active','unlimited','unit','combat','any',NULL,'宣言：このユニットと交戦中の敵ユニット1個を対象として選択する。\n効果：ダイスを1個振る。3+の場合、そのターンの終了時まで、対象のセーヴロールに-1の修正を施す。','この不浄なタリスマンは変異のエネルギーを放射し、敵ユニットに向けてその装甲を腐食させることができる。',NULL,0),
	(10,10,'RELICS OF RUIN',NULL,'スケイヴンブリュー (Skavenbrew)',0,1,'バトル中につき1回(アーミー)、任意の近接フェイズ',NULL,'active','once_per_battle','army','combat','any',NULL,'宣言：このユニットから13インチ以内に完全に収まっている、［ヒーロー］ではない味方の［スケイヴン］・［インファントリー］ユニット1個を対象として選択する。\n効果：対象にD3の致命的ダメージを与える。その後、そのターンの終了時まで、対象の白兵戦武器の攻撃回数特性値に+1する。','血とワープストーンから醸造されたこの不浄な調合液は、使い捨ての下級兵に分け与えられ、彼らを短命ながらも壊滅的な狂乱の殺戮へと駆り立てる。',NULL,0);

/*!40000 ALTER TABLE `m_artefacts_of_power` ENABLE KEYS */;
UNLOCK TABLES;


# テーブルのダンプ m_battle_formations
# ------------------------------------------------------------

DROP TABLE IF EXISTS `m_battle_formations`;

CREATE TABLE `m_battle_formations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `faction_id` int NOT NULL COMMENT '所属陣営のID',
  `formation_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL COMMENT 'バトル・フォーメーション名（英文・和文）',
  `ability_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL COMMENT 'フォーメーション固有のアビリティ名',
  `trigger_condition_ja` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `effect` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL COMMENT '効果のテキスト本文',
  `activation` enum('active','passive','reaction') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'active',
  `usage_scope` enum('unlimited','once_per_turn','once_per_phase','once_per_battle') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'unlimited',
  `usage_per` enum('unit','army') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'unit',
  `trigger_phase` set('deployment','hero','movement','shooting','charge','combat','end','any') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `trigger_turn` enum('your','opponent','any','battle') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'your',
  `icon_type` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `flavor_text` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci COMMENT 'フレーバーテキスト',
  `is_hidden` tinyint NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `fk_battle_formations_faction` (`faction_id`),
  CONSTRAINT `fk_battle_formations_faction` FOREIGN KEY (`faction_id`) REFERENCES `m_factions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='戦闘陣形';

LOCK TABLES `m_battle_formations` WRITE;
/*!40000 ALTER TABLE `m_battle_formations` DISABLE KEYS */;

INSERT INTO `m_battle_formations` (`id`, `faction_id`, `formation_name`, `ability_name`, `trigger_condition_ja`, `effect`, `activation`, `usage_scope`, `usage_per`, `trigger_phase`, `trigger_turn`, `icon_type`, `flavor_text`, `is_hidden`)
VALUES
	(1,1,'寂滅の塔の護り手 (Sentinels of the Bleak Citadels)','古代のオーラ (Ancient Aura)','各ターンにつき1回(アーミー)、任意のヒーローフェイズ','【宣言】：味方の「ルイネーション・チェンバー（RUINATION CHAMBER）」ユニット1体を選択する。\n【効果】：そのターンの終了時まで、そのユニットは「加護（WARD）5+」を得る。','active','once_per_turn','army','hero','any','Defensive','嵐満つるエネルギーが殲滅者の戦士たちの周囲で激しく波打ち、奔流の力のオーラで彼らを包み込む。いかに強力な定命の敵が装備する武器であれ、この古の英雄たちにはそう簡単に通用しない。',0),
	(2,1,'先遣たる翼 (Vanguard Wing)','比類なき機動 (Peerless Manoeuvres)','各ターンにつき1回(アーミー)、自軍側移動フェイズ','【宣言】：このターンに「移動（MOVE）」アビリティを使用しておらず、かつ敵と交戦状態（白兵戦中）にない、味方の「ヴァンガード_チェンバー（VANGUARD CHAMBER）」ユニット1体を選択する。\r\n【効果】：ダイスを1個振る。3+の場合、そのユニットを戦場から取り除き、すべての敵ユニットから9mvより遠く離れた戦場の任意の場所に再配置する。','active','once_per_turn','army','movement','your','Movement','この戦士たちは神王の信頼に足る精密兵器とされ、視界から不意に消えたかと思えば、最重要の持ち場に再び出現するなど、自身の有利になるよう戦場を操る能力を持っている。',0),
	(3,1,'嵐雲の軍勢 (Thunderhead Host)','同時の一撃 (Synchronised Strikes)','パッシブ','【効果】：味方の「ウォーリアー・チェンバー（WARRIOR CHAMBER）」ユニットが、白兵戦攻撃を行う際、そのユニットが「ウォーリアー・チェンバー（WARRIOR CHAMBER）」キーワードを持たない味方の「ストームキャスト・エターナル」ユニット（ただし「ヒーロー（HERO）」および「魔獣（BEAST）」を除く）の12インチ以内に全体が入っている場合、そのヒット（Hit）ロールに+1する。','passive','unlimited','unit',NULL,'any','Offensive','サンダーヘッド・ホストの中核は、多くの場合ウォーリアー・チェンバーの戦士たちで構成されている。誓いを共にしたストームホストの同胞たちと容赦なき訓練を重ねてきた彼らは、油を注がれた歯車要塞（コグフォート）の如く一糸乱れぬ連動を見せる。',0),
	(4,1,'稲妻の梯団 (Lightning Echelon)','迫り来る嵐 (Oncoming Storm)','パッシブ','【宣言】：このターンに突撃（チャージ）を行った、味方の「エクストリーミス・チェンバー（EXTREMIS CHAMBER）」ユニット1体を選択する。\n【効果】：ダイスを1個振る。3+の場合、そのターンの終了時まで、そのユニットは「先手攻撃（STRIKE-FIRST）」を得る。','passive','unlimited','unit',NULL,'any','Offensive','誰よりも早く戦場へ到達するライトニング・エシュロンは、敵の心臓部へ突き立てられる槍の如き存在である。真に窮した時にのみ展開される彼らは、押し寄せる大嵐のような破壊力で敵の前衛部隊を粉砕する。',0),
	(5,1,'サクロスサンクト・コンヴォケーション (Sacrosanct Convocation)','嵐の循環 (Cycle of the Storm)',NULL,'【宣言】：このターン中に模型（モデル）が戦死（破壊）した、味方の「サクロスサンクト・チェンバー（SACROSANCT CHAMBER）」ユニット1体を対象として選択する。\n【効果】：対象となったユニットに、戦死した模型を1体復帰させる。','active','unlimited','unit',NULL,'your',NULL,'再鍛造（リフォージ）プロセスの達人であるサクロスサンクト・チェンバーの熟練者たちは、致命傷を負った戦友たちがアジィルへと送還される前にその力を集中させ、現世に繋ぎ止めて回復させることで、目の前の戦いを継続させることができる。',1),
	(6,10,'歪み歯車評議会(ワープコグ・コンヴォケイション)','スクリールの試作品','各ターンにつき1回(アーミー)、自軍側遠隔フェイズ','【宣言】：味方スクリール・ユニットを最大3個まで選択する。\r\n【効果】：選択された各味方ユニットに対して、それぞれダイスを1個ロールし、対応する効果を適用する。：\r\n「1」ドッカーン！：ロール対象はD3ポイントの致命的ダメージを受ける。\r\n「2-5」モッと力を！：そのターン中、ロール対象によるレンジアタックのウーンズロールは+1の修正を受ける。\r\n「6」モッと、モッと力を！：『モッと力を！』の効果に加えて、そのターン中、ロール対象が装備している遠隔武器の【貫通値】は+1の修正を受ける。','active','once_per_turn','army','shooting','your','Shooting','スクリール大氏族の頂点をなす、独創的な武器職人によって拵えられた残忍な武器を振るう鼠人たちは、敵の隊列を次々と屠り、流血の限りを尽くす。',0),
	(7,10,'肉体融合動物園 (フレッシュメルド・ミナジェリー)','優れた造形物','各ターンにつき1回(アーミー)、自軍側ヒーローフェイズ','【宣言】：英雄ではない味方モウルダー・ユニットを最大3個まで選択する。\r\n【効果】：選択された各味方ユニットに対して、それぞれダイスを1個ロールし、対応する効果を適用する。：\r\n「1-2」自滅の怒り：ロール対象はD3ポイントの致命的ダメージを受ける。\r\n「3-4」滾る力：次の自軍側ターン開始時まで、ロール対象が装備している近接武器の【攻撃回数】は+1の修正を受ける。\r\n「5-6」理性無き狂乱：『滾る力』の効果に加えて、次の自軍側ターン開始時まで、ロール対象は加護(5+)をもつ。','active','once_per_turn','army','hero','your','Special','形態への負担を犠牲にしつつも、モウルダーの戦獣にはワープストーンの結晶が嵌め込まれ、戦いの狂乱へと駆り立てる忌々しい液体がたっぷりと注入されている。',0),
	(8,10,'悪疫の葬列 (ヴィルレント・プロセッション)','腐敗した大地','各ターンにつき1回(アーミー)、任意のターン終了時','【宣言】：近接戦闘中である味方ペスティレンス・ユニットを最大3個まで選択する。\r\n【効果】：選択された各ユニットはそれぞれ：\r\n・接敵移動を1回実行する。\r\n・その後、選択された味方ユニットと近接戦闘中である敵ユニット1個選択し、D3を1個ロールする。ロール結果が2+であれば、その敵ユニットはロール結果に等しい数の致命的ダメージを受ける。','active','once_per_turn','army','end','any','Offensive','ペスティレンス大氏族は、疫病と腐敗の蔓延を躊躇いもなく進めていく。',0),
	(9,10,'クロウホード','爪に選ばれし者','各ターンにつき1回(アーミー)、自軍側近接フェイズ','【宣言】：このターン中に突撃を実行している味方ヴァーミヌス・ユニットを最大3個まで選択する。\r\n【効果】：そのターン中、選択された味方ユニットが装備している近接武器の【貫通値】は+1の修正を受ける。\r\n','active','once_per_turn','army','combat','your','Offensive','ヴァーミヌス大士族のクロウホードに属するストームヴァーミンとクランラットは、全ての鼠人の中でも随一の獰猛さを誇る。',0),
	(10,10,'死の執行群','殺セ！　殺セ！　走レ、ニゲロ！','初期配置フェイズ','【宣言】：敵英雄を１体選択する。\r\n【効果】：このバトル中、選択された敵ユニットが全滅している場合、味方\r\nエシン・ユニットは【移動力】に +2mv の修正を得る。','active','once_per_turn','unit','deployment','your','Movement','死の執行群の目的はただ一つ：標的の抹殺だ。だがそれを果たし奇襲の優位が失われた瞬間、エシン氏族の工作員は生存を第一に行動しなければならない……',0);

/*!40000 ALTER TABLE `m_battle_formations` ENABLE KEYS */;
UNLOCK TABLES;


# テーブルのダンプ m_battle_tactic_stages
# ------------------------------------------------------------

DROP TABLE IF EXISTS `m_battle_tactic_stages`;

CREATE TABLE `m_battle_tactic_stages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `battle_tactic_id` int NOT NULL,
  `stage` enum('affray','strike','domination') NOT NULL,
  `stage_order` tinyint NOT NULL COMMENT '1=Affray, 2=Strike, 3=Domination',
  `name` varchar(255) NOT NULL,
  `effect` text NOT NULL,
  `victory_points` tinyint NOT NULL DEFAULT '2',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_tactic_stage` (`battle_tactic_id`,`stage`),
  UNIQUE KEY `uk_tactic_stage_order` (`battle_tactic_id`,`stage_order`),
  KEY `idx_battle_tactic_id` (`battle_tactic_id`),
  CONSTRAINT `fk_tactic_stages_card` FOREIGN KEY (`battle_tactic_id`) REFERENCES `m_battle_tactics` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

LOCK TABLES `m_battle_tactic_stages` WRITE;
/*!40000 ALTER TABLE `m_battle_tactic_stages` DISABLE KEYS */;

INSERT INTO `m_battle_tactic_stages` (`id`, `battle_tactic_id`, `stage`, `stage_order`, `name`, `effect`, `victory_points`)
VALUES
	(7,1,'affray',1,'熟練の用兵 (MASTER OF ARMS)','自軍ターン終了時、同一の敵ユニットに対し、そのターンの3つの異なるフェーズでそれぞれ1点以上のダメージを与えていれば達成（5 VP）。',5),
	(8,1,'strike',2,'防御を打ち破れ (BREAK THEIR DEFENCES)','自軍ターン終了時、敵の「隠れ家」を支配していれば達成（5 VP）。',5),
	(9,1,'domination',3,'1人も生かしておくな (NO SURVIVORS)','自軍ターン終了時、そのターン中に2ユニット以上の敵を破壊し、かつ敵の「隠れ家」を支配していれば達成（5 VP）。',5),
	(10,2,'affray',1,'壁の構築(FORM A WALL)','自軍ターン終了時、味方ユニット2つ以上が、自軍陣地の外側に全体が入っており、かつ自軍陣地から6mv以内の範囲にあり、かつユニット同士が互いに3mv以内であれば達成（5 VP）。',5),
	(11,2,'strike',2,'拠点への再補給 (OUTPOST RESUPPLY)','自軍ターン終了時、敵軍陣地内に一部でも入っている目標を支配し、その目標に「そのターンに移動（MOVE）アビリティを使用していない味方1ユニット」と「そのターンに突撃（Charge）した味方1ユニット」の両方が争奪していれば達成（5 VP）。',5),
	(12,2,'domination',3,'攻撃者を撃退せよ (REPEL THE ATTACKERS)','自軍ターン終了時、戦場の中心から3mv以内に、近接戦闘中ではない味方ユニットが3つ以上あれば達成（5 VP）。',5),
	(13,3,'affray',1,'待ち伏せ (AMBUSCADE)','自軍ターン終了時、支配している2つ以上の目標または地形のそれぞれを、異なる味方ユニットが争奪しており、かつそれらの味方ユニットが自軍テリトリーから6\"以上離れ、そのターンに配置（set up）されたものでなければ達成（5 VP）。',5),
	(14,3,'strike',2,'敵を包囲せよ (SURROUND THE ENEMY)','自軍ターン終了時、2つ以上の味方ユニットがそれぞれ戦場の異なるコーナーから9\"以内かつ自軍テリトリー外にあり、かつそのターンに配置されたものでなければ達成（5 VP）。',5),
	(15,3,'domination',3,'正当なる所有者 (CLAIM WHAT’S YOURS)','自軍ターン終了時、敵軍テリトリー内にいる味方ユニット数が敵ユニット数より多く、かつ味方ヒーローが1体以上敵軍テリトリー内にいれば達成（5 VP）。',5),
	(16,4,'affray',1,'敵を手玉に取れ (KEEP THE ENEMY CLOSE)','自軍ターン終了時、敵ユニットが争奪している目標を支配していれば達成（5 VP）。',5),
	(17,4,'strike',2,'見せかけの弱み (FEIGN WEAKNESS)','自軍ターン終了時、そのターンに破壊された味方ユニット数が敵ユニット数より多ければ達成（5 VP）。',5),
	(18,4,'domination',3,'計画を実行せよ (EXECUTE THE PLAN)','自軍ターン終了時、敵軍テリトリー内のすべての目標を支配していれば達成（5 VP）。',5),
	(19,5,'affray',1,'くまなく探せ (SEARCH EVERY INCH)','自軍ターン終了時、戦場の4つの大区画のそれぞれに味方ユニットが1つずつおり、かつそれらが戦場中心から9\"以上離れ、そのターンに配置されたものが1つ以下であれば達成（5 VP）。',5),
	(20,5,'strike',2,'急追 (QUICK PURSUIT)','自軍ターン終了時、「戦闘状態でなく、かつ『逃亡者』から12\"以内にいる味方ユニットが3つ以上」または「戦場に『逃亡者』がいない」のどちらかであれば達成（5 VP）。',5),
	(21,5,'domination',3,'引っ立てろ (BRING THEM IN)','自軍ターン終了時、「『逃亡者』と戦闘状態にある味方ユニットが3つ以上」または「『逃亡者』がおらず、かつ自軍テリトリー外の同一目標を味方ユニット3つ以上が争奪している」のどちらかであれば達成（5 VP）。',5),
	(22,6,'affray',1,'大胆不敵な救援 (DARING RESCUE)','自軍ターン終了時、ターン開始時に戦闘状態にあった敵ユニットが、そのターンに突撃した味方ユニットによる近接攻撃で破壊されていれば達成（5 VP）。',5),
	(23,6,'strike',2,'陣頭指揮 (COMMAND FROM THE FRONT)','自軍ターン終了時、戦場に味方ヒーローが2体以上おり、すべての味方ヒーローが視認可能な敵ユニットから9\"以内にあり、かつそのターンに味方ヒーローが1体も倒されていなければ達成（5 VP）。',5),
	(24,6,'domination',3,'伝説の英雄 (HERO OF LEGEND)','自軍ターン終了時、ターン開始時には支配していなかった自軍テリトリー外の目標を支配し、かつ味方ヒーローがその目標を争奪していれば達成（5 VP）。',5);

/*!40000 ALTER TABLE `m_battle_tactic_stages` ENABLE KEYS */;
UNLOCK TABLES;


# テーブルのダンプ m_battle_tactics
# ------------------------------------------------------------

DROP TABLE IF EXISTS `m_battle_tactics`;

CREATE TABLE `m_battle_tactics` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `grand_alliance` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL COMMENT 'NULL=汎用',
  `season` varchar(16) NOT NULL DEFAULT '2024-25',
  `effect` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `sort_order` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

LOCK TABLES `m_battle_tactics` WRITE;
/*!40000 ALTER TABLE `m_battle_tactics` DISABLE KEYS */;

INSERT INTO `m_battle_tactics` (`id`, `name`, `grand_alliance`, `season`, `effect`, `sort_order`)
VALUES
	(1,'燃え盛る強襲 (Blazing Onslaught)',NULL,'2026-27','開始時: 初期配置フェイズのアビリティをすべて使用した後、先攻・後攻を決定する前に、対戦相手は自軍のテリトリー内に一部でも入っている「陣営地形ではない（NON-FACTION）」特殊地形1つを選び「隠れ家（Hideout）」とする。この地形はゲーム中、戦場から取り除くことはできない。',0),
	(2,'灰の攻囲戦 (Siege of Ashes)',NULL,'2026-27',NULL,0),
	(3,'背を焼く猛炎 (Flanking Firestorm)',NULL,'2026-27',NULL,0),
	(4,'煙幕 (Smokescreen)',NULL,'2026-27',NULL,0),
	(5,'燃え盛る復讐心 (Burning for Vengeance)',NULL,'2026-27','開始時: 初期配置フェイズのアビリティをすべて使用した後、先攻・後攻を決定する前に、戦場または予備戦力にいる敵英雄1体を選び「逃亡者（Fugitive）」とする。',0),
	(6,'大焦界の伝説 (Legend of The Parch)',NULL,'2026-27',NULL,0);

/*!40000 ALTER TABLE `m_battle_tactics` ENABLE KEYS */;
UNLOCK TABLES;


# テーブルのダンプ m_battle_traits
# ------------------------------------------------------------

DROP TABLE IF EXISTS `m_battle_traits`;

CREATE TABLE `m_battle_traits` (
  `id` int NOT NULL AUTO_INCREMENT,
  `faction_id` int NOT NULL COMMENT 'どの陣営（マニフェスト/サブファクション含む）のルールか',
  `sub_faction_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL COMMENT '特定のサブファクション（連隊など）限定の場合に設定',
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL COMMENT 'バトル・トレイト名（例：血の供物、印の力など）',
  `command_point` int DEFAULT NULL,
  `ability_type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL COMMENT 'これでフェイズのパッシブまとめの管理してる',
  `activation` enum('active','passive','reaction') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'active',
  `usage_scope` enum('unlimited','once_per_turn','once_per_phase','once_per_round','once_per_battle') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'unlimited',
  `usage_per` enum('unit','army') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'unit',
  `trigger_phase` set('deployment','round_start','hero','movement','shooting','charge','combat','end','any') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `trigger_turn` enum('your','opponent','any','battle') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'your',
  `icon_type` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `trigger_condition_ja` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `effect` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL COMMENT '効果のテキスト本文',
  `keywords` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `flavor_text` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci COMMENT '背景設定などのフレーバーテキスト',
  PRIMARY KEY (`id`),
  KEY `fk_battle_traits_faction` (`faction_id`),
  CONSTRAINT `fk_battle_traits_faction` FOREIGN KEY (`faction_id`) REFERENCES `m_factions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='戦闘特性';

LOCK TABLES `m_battle_traits` WRITE;
/*!40000 ALTER TABLE `m_battle_traits` DISABLE KEYS */;

INSERT INTO `m_battle_traits` (`id`, `faction_id`, `sub_faction_name`, `name`, `command_point`, `ability_type`, `activation`, `usage_scope`, `usage_per`, `trigger_phase`, `trigger_turn`, `icon_type`, `trigger_condition_ja`, `effect`, `keywords`, `flavor_text`)
VALUES
	(1,1,NULL,'天空の領域 (The Celestial Realm)',NULL,'Special','active','unlimited','unit','deployment','your','Special','初期配置フェイズ','【宣言】：戦場にいる味方「ストームキャスト・エターナル」ユニットの数が、現在リザーブ（待機状態）に設定されている数より多い場合、まだ配置（デプロイ）されていない味方「ストームキャスト・エターナル」ユニット1体を選択する。\n【効果】：そのユニットを天界領域のリザーブとして配置する。これにより、そのユニットは配置されたものとして扱われる。\n（キーワード：配置）','初期配置','ハイ・アジィル（高きアジィル）の地にて、ストームキャスト・エターナルは報復の鉄槌を下すその時を静かに待っている。'),
	(2,1,NULL,'嵐の御子 (Scions of the Storm)',NULL,NULL,'active','unlimited','unit','movement','your','Movement','自軍側移動フェイズ','【宣言】：天界領域（リザーブ）にいる味方「ストームキャスト・エターナル」ユニット1体を選択する。\r\n【効果】：そのユニットを、すべての敵ユニットから9mvより遠く離れた戦場の任意の場所に配置する。','','祝福されし稲妻に乗り、ストームキャスト・エターナルは戦場へと馳せ参じる。その到来を告げるのは、激しい雷鳴の轟きである。'),
	(3,1,NULL,'天からの遣い (Heavens-Sent)',1,NULL,'active','once_per_battle','army','movement','your','Rally','バトル中1回、自軍側移動フェイズ','【宣言】：バトル開始時に2体以上の兵で構成されており、すでに全滅（破壊）している、味方の「固有（UNIQUE）」ではないストームキャスト・エターナルの「歩兵（INFANTRY）」または「騎兵（CAVALRY）」ユニット1体を選択する。\r\n【効果】：選択したユニットの元の兵数の半分（端数切り上げ）の兵数を持つ「代替ユニット」を、すべての敵ユニットから9mvより遠く離れた戦場の任意の場所に配置する。','','戦友たちが窮地に立たされたその時、アジィルからさらなる増援の光が降り注ぐ。'),
	(4,1,NULL,'宿命の時 (Their Finest Hour)',NULL,NULL,'active','once_per_turn','army','hero','your','Special','各ターンにつき1回(アーミー)、自軍側ヒーローフェイズ','【宣言】：このバトル中にまだこのアビリティを使用していない、味方の「ストームキャスト・エターナル」ユニット1体を選択する。\r\n【効果】：そのターンの終了時まで、そのユニットの近接攻撃のウーンズロールに+1し、そのユニットのセーブロールに+1する。','','ストームキャスト・エターナルは、秩序の勢力における「希望の要塞」である。絶望的な苦境にあっても、戦士たちは敵を阻むために必要なすべてを成し遂げる。'),
	(5,10,NULL,'潜伏する大鼠波',NULL,'Special','active','unlimited','unit','deployment','your','Special','初期配置フェイズ','【宣言】：初期配置されていない味方スケイヴン・ユニットを1個選択する。\r\n【効果】：選択された味方ユニットを予備戦力として地下トンネルに配置する。そのユニットは初期配置されたものとみなされる。','初期配置','地下に蠢くものが姿を現す。'),
	(6,10,NULL,'常に三歩先へ',NULL,NULL,'active','once_per_turn','army','hero','opponent','Movement','各ターンにつき1回(アーミー)、敵軍側ヒーローフェイズ','【宣言】：このターン中に配置されたばかりではなく、かつ近接戦闘中でもない、大型獣ではない味方スケイヴン・ユニットを1個選択し、このアビリティを使用する。\r\n【効果】：選択された味方ユニットは、あたかも自軍側移動フェイズ中かのように『通常移動』アビリティを1回使用できる。','','スケイヴンが立てた最高の計画は決して失敗しない(と、彼らは主張している。)'),
	(7,10,NULL,'捉え難き素早さ',NULL,'Passive','passive','unlimited','unit',NULL,'your','Defensive','パッシブ','【効果】：味方スケイヴン・歩兵と騎兵・ユニットは『退却』アビリティによる致命的ダメージを受けない。','','この臆病な鼠人たちは戦闘から離脱するや否や、反撃を避けるために一目散に逃げ散らかしてしまう。'),
	(8,10,NULL,'ヴァーミンドゥームの傷跡',NULL,NULL,'active','once_per_round','army','round_start','any','Special','各バトルラウンドにつき1回(アーミー)、バトルラウンドの開始時','【宣言】：戦場に配置されている味方齧り穴が2個以下である場合、このアビリティを使用できる。\r\n【効果】：あらゆる敵ユニットから9mvより遠く離れており、かつあらゆる味方ユニットからも1mvより遠く離れており、尚且つあらゆる作戦目標と他の特殊地形から3mvより遠く離れている戦場の位置に齧り穴を一個配置する。','','〈滅びの蠱禍〉の余波の中、現実空間に入り込んだ齧り穴は終わりなき群れの鼠人を吐き出し、現実は引き裂かれ続ける。'),
	(9,10,NULL,'齧り穴からの奇襲',NULL,NULL,'active','unlimited','unit','movement','your','Movement','自軍側移動フェイズ','【宣言】：地下トンネルに配置されている味方スケイヴン・ユニットを1個選択し、このアビリティを使用する。\r\n【効果】：選択された味方ユニットを味方齧り穴の6mv以内に全体が入るように、かつあらゆる敵ユニットから9mvより遠く離れている位置に配置する。','',NULL);

/*!40000 ALTER TABLE `m_battle_traits` ENABLE KEYS */;
UNLOCK TABLES;


# テーブルのダンプ m_battleplan_abilities
# ------------------------------------------------------------

DROP TABLE IF EXISTS `m_battleplan_abilities`;

CREATE TABLE `m_battleplan_abilities` (
  `id` int NOT NULL AUTO_INCREMENT,
  `battleplan_id` int NOT NULL,
  `name` varchar(255) NOT NULL,
  `effect` text,
  `command_cost` tinyint DEFAULT NULL COMMENT 'CP費用。NULL=コマンドではない(CP不要)',
  `activation` enum('active','passive','reaction') NOT NULL DEFAULT 'active',
  `usage_scope` enum('unlimited','once_per_turn','once_per_phase','once_per_battle') NOT NULL DEFAULT 'unlimited',
  `usage_per` enum('unit','army') NOT NULL DEFAULT 'army',
  `trigger_phase` set('deployment','round_start','hero','movement','shooting','charge','combat','end','any') DEFAULT NULL,
  `trigger_turn` enum('your','opponent','any','battle') NOT NULL DEFAULT 'your',
  `icon_type` varchar(20) DEFAULT NULL COMMENT 'Offensive/Defensive/Movement/Shooting/Damage/Control/Rallying/Special',
  `trigger_condition_ja` text,
  `flavor_text` text,
  `is_hidden` tinyint(1) NOT NULL DEFAULT '0',
  `sort_order` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_battleplan_sort` (`battleplan_id`,`sort_order`),
  CONSTRAINT `fk_battleplan_abilities_battleplan` FOREIGN KEY (`battleplan_id`) REFERENCES `m_battleplans` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

LOCK TABLES `m_battleplan_abilities` WRITE;
/*!40000 ALTER TABLE `m_battleplan_abilities` DISABLE KEYS */;

INSERT INTO `m_battleplan_abilities` (`id`, `battleplan_id`, `name`, `effect`, `command_cost`, `activation`, `usage_scope`, `usage_per`, `trigger_phase`, `trigger_turn`, `icon_type`, `trigger_condition_ja`, `flavor_text`, `is_hidden`, `sort_order`)
VALUES
	(1,1,'SECURE THE GATEWAY','Effect: While you control the Place of Power wholly within friendly territory, friendly units’ melee weapons have Anti-charge (+1 Rend) while they are contesting an objective you control.',NULL,'passive','unlimited','army',NULL,'any',NULL,'パッシブ',NULL,0,0),
	(2,1,'DETERMINED DEFENDERS','Effect: Add 3 to the control scores of friendly units while they are wholly outside enemy territory.',NULL,'passive','unlimited','army',NULL,'any',NULL,'パッシブ',NULL,0,0);

/*!40000 ALTER TABLE `m_battleplan_abilities` ENABLE KEYS */;
UNLOCK TABLES;


# テーブルのダンプ m_battleplans
# ------------------------------------------------------------

DROP TABLE IF EXISTS `m_battleplans`;

CREATE TABLE `m_battleplans` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `rounds` tinyint NOT NULL DEFAULT '5',
  `rule_description` text NOT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

LOCK TABLES `m_battleplans` WRITE;
/*!40000 ALTER TABLE `m_battleplans` DISABLE KEYS */;

INSERT INTO `m_battleplans` (`id`, `name`, `rounds`, `rule_description`, `sort_order`)
VALUES
	(1,'炎の中へ',5,'TWIST: 両方のプレイヤー’ 軍隊には「ゲートウェイを確保する」機能があります。 あなたが弱者である間、あなたの軍隊には「決意の強い守備者」の能力があります。\r\n各プレイヤーは、各ターンの終了時に次のように勝利ポイントを獲得します:\r\n少なくとも 1 つの目標をコントロールすると、3 つの勝利ポイントを獲得できます。\r\n2 つ以上の目標をコントロールすると、3 つの勝利ポイントを獲得できます。\r\n相手よりも多くの目標をコントロールできれば、4 つの勝利ポイントを獲得できます。',1),
	(2,'血塗れ海岸',5,'',2),
	(3,'灰の雪崩',5,'',3),
	(4,'殺戮の洞窟',5,'',4),
	(5,'貴様らの物は我らの物',5,'',5),
	(6,'灰の雲の下で',5,'',6),
	(7,'ねじれた廃墟',5,'',7),
	(8,'大齧りの呪い',5,'',8),
	(9,'熾火を我が手に',5,'',9),
	(10,'危険な土地',5,'',10),
	(11,'海岸からの脱出',5,'',11),
	(12,'諸領域の力',5,'',12);

/*!40000 ALTER TABLE `m_battleplans` ENABLE KEYS */;
UNLOCK TABLES;


# テーブルのダンプ m_common_abilities
# ------------------------------------------------------------

DROP TABLE IF EXISTS `m_common_abilities`;

CREATE TABLE `m_common_abilities` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `sort_order` tinyint(1) DEFAULT NULL,
  `command_cost` tinyint DEFAULT NULL COMMENT 'CP費用。NULL=コマンドではない(CP不要)',
  `activation` enum('active','passive','reaction') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'active',
  `usage_scope` enum('unlimited','once_per_turn','once_per_battle') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'unlimited',
  `usage_per` enum('unit','army') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'unit',
  `trigger_phase` set('deployment','round_start','hero','movement','shooting','charge','combat','end','any') DEFAULT NULL,
  `trigger_turn` enum('your','opponent','any') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'your',
  `trigger_condition_ja` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `icon_type` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL COMMENT 'Offensive/Defensive/Movement/Shooting/Damage/Control/Rallying/Special',
  `effect` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `flavor_text` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `keywords` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci COMMENT 'キーワード保存用、システム利用なし',
  `is_hidden` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

LOCK TABLES `m_common_abilities` WRITE;
/*!40000 ALTER TABLE `m_common_abilities` DISABLE KEYS */;

INSERT INTO `m_common_abilities` (`id`, `name`, `sort_order`, `command_cost`, `activation`, `usage_scope`, `usage_per`, `trigger_phase`, `trigger_turn`, `trigger_condition_ja`, `icon_type`, `effect`, `flavor_text`, `keywords`, `is_hidden`)
VALUES
	(1,'初期配置',1,NULL,'active','unlimited','unit','deployment','your','初期配置フェイズ','Special','【宣言】：自軍のアーミーロスターから初期配置されていないユニットを1個選択する。\r\n【効果】：自軍側陣地に全体が入るように、かつあらゆる敵軍側陣地から9mvより遠く離れた位置に、選択されたユニットを配置する。配置された後、そのユニットは初期配置されたものとみなされる','追加の戦力が戦場へと投入される。','初期配置',0),
	(2,'顕現の追放',23,NULL,'active','unlimited','unit','hero','your','自軍側ヒーローフェイズ','Special','【宣言】：味方魔術師または神官を1体選択し、このアビリティを使用する。魔術師または神官の30mv 以内に一部でも入っている顕現を1個選択し、2D6 の追放ロールをする。\n【効果】：追放ロールが顕現のウォースクロールに示されている【追放値】以上であれば、顕現は追放され、戦場から取り除かれる。なお、同じターン中にこのアビリティの攻撃対象として同一の顕現を選択できるのは1回だけである。','魔術師または神官は、顕現を保たせる神秘なる力を打ち砕き、眼前の顕現を消し去る。','追放',0),
	(3,'全力攻撃',18,1,'reaction','unlimited','unit','shooting,combat','your','リアクション：自軍側が『アタック』アビリティを宣言','Offensive','【宣言】アビリティを使用しているユニット1個。\n【効果】：その『アタック』アビリティによる攻撃のヒットロールは+1の修正を受ける。この効果は、随行者・武器アビリティを有する武器にも適用される。','熱狂的な勢いで戦士たちは戦いにでる。',NULL,0),
	(4,'全力防御',19,1,'reaction','unlimited','unit','shooting,combat','opponent','リアクション：敵軍側が『アタック』アビリティを宣言','Defensive','【宣言】：その『アタック』アビリティの攻撃対象となったユニット1個。\n【効果】：このフェイズ中、その使用者ユニットのセーブロールは+1の修正を受ける。','身に迫る攻撃を防ぐため、兵士たちは隊列を組む。',NULL,0),
	(5,'再集結',11,1,'active','unlimited','unit','hero','your','任意のヒーローフェイズ','Rallying','【宣言】：近接戦闘中ではない味方ユニットを1 個選択し、このアビリティを使用する。\n【効果】：D6の再集結ロールを6回実行する。ロール結果で4+が出るたびに、1再集結ポイントを獲得する。再集結ポイントは、以下の方法で使用できる。\n\n・1再集結ポイントごとに、そのユニットを回復(1)する。\n・ユニットの【体力】に等しい数の再集結ポイントを消費して、そのユニットに撃破された兵を1体復帰させることができる。\n\n上記に示す任意の組み合わせで、再集結ポイントを消費してもよい。その後、消費されていない再集結ポイントは破棄される。','奮起を呼び起こす声によって、負傷した戦士たちは再び立ち上がり、新たな隊が倒れた同胞の意志を受け継ぎ戦場に立つ。',NULL,0),
	(6,'再配置',13,1,'active','unlimited','unit','movement','opponent','敵軍側移動フェイズ','Movement','【宣言】：近接戦闘中ではない味方ユニットを1個選択し、このアビリティを使用する。\n【効果】：選択された味方ユニット内の各兵は、最大D6mvまで移動できる。その移動は、敵ユニットの近接範囲内を通り抜けたり、近接範囲内で完了することはできない。','敵の動きに対して先手を打つため、戦士たちは急いで新しい位置に展開する。','移動、全力移動',0),
	(7,'駆け足、急げ！',14,1,'reaction','unlimited','unit','movement','your','リアクション：自軍側が全力移動アビリティを使用','Movement','【宣言】：その『全力移動』アビリティを使用しているユニット1個。\n【効果】：その『全力移動』アビリティの手順の中で、全力移動ロールを実行しない。代わりに、選択されたユニットの【移動力】に6mvが加算され、そのユニット内の各兵がその『全力移動』アビリティの手順の中で移動できる距離を決定する。','指揮官の号令とともに、戦士たちは戦場の要所へと全力疾走で向かう。',NULL,0),
	(8,'勝利に向かって進め',17,1,'reaction','unlimited','unit','charge','your','リアクション：自軍側が『突撃』アビリティを宣言','Movement','【宣言】：その『突撃』アビリティを使用しているユニット1個。\n【効果】：その突撃ロールをリロールできる。','軍勢の勢いが衰えようとした瞬間、魂が赤々と燃えるような指令が隊に新たな活力を与える。',NULL,0),
	(9,'強行突破',20,1,'active','unlimited','unit','end','any','任意のターン終了時','Special','【宣言】：このターン中に突撃を実行している味方ユニットを1個選択し、このアビリティを使用する。次に、選択された味方ユニットと近接戦闘中である敵ユニットを1個選択しなければならない。選択された敵ユニットの【体力】は、このアビリティを使用する味方ユニットの【体力】より低くなければならない。\n【効果】：その敵ユニットはD3ポイントの致命的ダメージを受ける。\n次に、このアビリティを使用するユニットは、自身の【移動力】に示された距離まで移動してもよい。その移動は、移動開始時に近接戦闘中であった敵ユニットの近接範囲を通り抜けたり、その近接範囲内で完了できる。ただし、その他の敵ユニットであれば、選択された味方ユニットはそのような敵ユニットを通り抜けたり、その近接範囲内で移動を完了することはできない。またその味方ユニットは、近接戦闘に突入するように移動を完了する必要はない。','戦士たちは巨躯なる塊となって、またその耐久力を駆使して、弱者どもを吹き飛ばしては敵陣を粉砕する。',NULL,0),
	(10,'援護射撃',15,1,'active','unlimited','unit','shooting','opponent','敵軍側遠隔フェイズ','Shooting','【宣言】：このターン中、『全力移動』アビリティを使用しておらず、かつ近接戦闘中ではない味方ユニットを1個選択し、このアビリティを使用する。選択された味方ユニットに最も近く、かつレンジアタックの対象として選択可能な敵ユニットを選択する。\n【効果】：選択された敵ユニットに対して、このアビリティを使用したユニットのレンジアタックを解決する。これらのヒットロールは-1の修正を受ける。このアビリティの対象として顕現あるいは陣営地形を選択できない。','戦士たちは近くの敵部隊に素早く一斉射撃を放ち、矢継ぎ早に戦闘へと突入する。','遠隔攻撃、アタック',0),
	(12,'陣営地形の初期配置',3,NULL,'active','unlimited','unit','deployment','your','初期配置フェイズ','Special','【宣言】：初期配置されていない、味方陣営地形を1個選択する。\r\n【効果】：自軍側陣地内に全体が入るように、かつあらゆる作戦目標と他の特殊地形から3mvより遠く離れた位置に、選択された陣営地形を配置する。配置された後、そのユニットは初期配置されたものとみなされる。','軍勢は重要で強力な建造物を囲むように陣地を構えた。','特殊地形の初期配置',0),
	(13,'連隊の初期配置',2,NULL,'active','unlimited','unit','deployment','your','初期配置フェイズ','Special','【宣言】：自軍のアーミーロスターから連隊を1個選択する。選択された連隊に属するすべてのユニットは、初期配置されていてはならない。\r\n【効果】：その連隊に属するすべてのユニットが初期配置されるまで、『初期配置』アビリティを交互ではなく連続して使用する。選択された連隊内に属していないユニットを、その一連の『初期配置』アビリティの対象として選択することはできない','偉大な英雄が従者を戦いに導く。','初期配置',0),
	(14,'通常移動',4,NULL,'active','unlimited','unit','movement','your','自軍側移動フェイズ','Movement','【宣言】：近接戦闘中ではない味方ユニットを1個選択し、このアビリティを使用する。\r\n【効果】：選択された味方ユニットは、自身が有する【移動力】の距離まで移動できる。その移動中、その味方ユニットは近接戦闘に突入するような移動を実行できない。',NULL,'コア、移動',1),
	(15,'全力移動',5,NULL,'active','unlimited','unit','movement','your','自軍側移動フェイズ','Movement','【宣言】：近接戦闘中ではない味方ユニットを1個選択し、このアビリティを使用する。\r\n【効果】：D6の全力移動ロールをする。選択された味方ユニットは、自身が有する【移動力】に全力移動ロールの結果を加算した距離まで移動できる。その移動中、その味方ユニットは近接戦闘に突入するような移動を実行できない。',NULL,'コア、移動、全力移動',0),
	(16,'退却',6,NULL,'active','unlimited','unit','movement','your','自軍側移動フェイズ','Movement','【宣言】：近接戦闘中である味方ユニットを1個選択し、このアビリティを使用する。\r\n【効果】：選択された味方ユニットはD3ポイントの致命的ダメージを受ける。その味方ユニットは自身が有する【移動力】の距離まで移動できる。その味方ユニットはあらゆる敵ユニットの近接範囲内を通り抜けることができる。ただし、敵ユニットの近接範囲内に一部でも入るような状態で移動を完了することはできない。',NULL,'コア、移動、退却',0),
	(17,'遠隔攻撃',7,NULL,'active','unlimited','unit','shooting','your','自軍側遠隔フェイズ','Shooting','【宣言】：このターン中に『全力移動』や『退却』アビリティを使用していない味方ユニットを1個選択し、このアビリティを使用する。その後、選択された味方ユニットが持つ攻撃の対象として、敵ユニットを1個または複数選択する。\r\n【効果】：攻撃対象である敵ユニットに対してレンジアタックを解決する。',NULL,'コア、アタック、遠隔攻撃',1),
	(18,'突撃',8,NULL,'active','unlimited','unit','charge','your','自軍側突撃フェイズ','Movement','【宣言】：このターン中に『全力移動』や『退却』アビリティを使用しておらず、かつ近接戦闘中ではない味方ユニットを1個選択し、このアビリティを使用する。その後、2D6の突撃ロールをする。\r\n【効果】：選択された味方ユニットは、突撃ロールの結果に等しい数の距離まで移動できる。その味方ユニットは、あらゆる敵ユニットの近接範囲内を通り抜けることができる。またその移動は、視認状態にある敵ユニットの½mv以内に一部が入るように完了されなければならない。そのような移動を実行した場合、このアビリティを使用した味方ユニットは突撃したものとみなされる。',NULL,'コア、移動、突撃',1),
	(19,'近接攻撃',9,NULL,'active','unlimited','unit','combat','your','自軍側近接フェイズ','Offensive','【宣言】：近接戦闘中またはこのターン中に突撃を実行している味方ユニットを1個選択し、このアビリティを使用する。選択された味方ユニットは接敵移動を1回実行できる。次に、選択された味方ユニットが近接戦闘中である場合、その味方ユニットが持つ攻撃の対象として、敵ユニットを1個または複数選ばなければならない。\r\n【効果】：攻撃対象であるユニットに対してメレーアタックを解決する。\r\n',NULL,'コア、アタック、近接攻撃',1),
	(20,'護られし英雄',10,NULL,'passive','unlimited','unit',NULL,'any','パッシブ','Defensive','【効果】：この英雄が、英雄ではない味方ユニットの近接範囲内に一部でも入っている場合\r\n\r\n• その英雄を攻撃対象とするレンジアタックは、ヒットロールに-1の修正を受ける。\r\n• その英雄が歩兵である場合、その英雄から12mvより遠く離れている兵は、その英雄をレンジアタックの攻撃対象として選択することはできない。',NULL,NULL,0),
	(21,'魔法の介入',12,1,'active','unlimited','unit','hero','opponent','敵軍側ヒーローフェイズ','Special','【宣言】：味方魔術師または神官を1体選択し、このアビリティを使用する。\r\n【効果】：選択された味方ユニットは、あたかも自軍側ヒーローフェイズ中かのように『呪文』または『奇蹟』アビリティ（必要に応じて）を1回使用できる。使用する場合、そのアビリティの詠唱ロールあるいは祈願ロールは-1の修正を受ける。','神秘なる力を習得せし戦士は、敵の策を妨害するために口早に呪文を唱える。',NULL,0),
	(22,'力を秘めし地形の解放',21,NULL,'active','unlimited','unit','round_start','any','任意のターン開始時','Special','【宣言】：力を秘めし地形の3mv以内に一部でも入っている味方英雄を1体選択し、このアビリティを使用する。\r\n【効果】：ダイスを1個ロールする。ロール結果が1であれば、選択された味方英雄はD3ポイントの致命的ダメージを受ける。ロール結果が2+であれば\r\n\r\n• 選択された味方英雄が魔術師または神官である場合、このターン中、その英雄の詠唱ロールまたは祈願ロールは+1の修正を受ける。\r\n• 選択された味方英雄が魔術師でも神官でもない場合、その英雄はあたかも魔術師(1)であるかのように『打ち消し』または『顕現の追放』アビリティを使用できる。','力を秘めし大地から、英雄は神秘なる息吹を引き出す','コア',0),
	(23,'打ち消し',22,NULL,'reaction','unlimited','unit','hero','opponent','リアクション：敵軍側が『呪文』アビリティを宣言','Special','【宣言】：呪文の詠唱を試みようとしている、敵魔術師の30mv以内に一部でも入っている味方魔術師1体。\r\n【効果】：2D6の打ち消しロールをする。打ち消しロールが呪文の詠唱ロールを上回る場合、その呪文は打ち消され、その呪文の効果は解決されない。このリアクションは、詠唱ロールごとに1回だけ使用できる。','魔法使いは敵が唱えんとする呪文の力を奪い、その効果を無と化す。','打消し',0),
	(24,'報復攻撃',16,2,'active','unlimited','unit','charge','opponent','敵軍側突撃フェイズ','Movement','【宣言】：近接戦闘中ではない味方ユニットを1 個選択し、このアビリティを使用する。\r\n【効果】：選択された味方ユニットは、あたかも自軍側突撃フェイズ中かのように『突撃』アビリティを1回使用できる。','決定的一打を必要とする戦士たちは、敵の前進を阻止したり、大胆な迎撃に打って出る。',NULL,0);

/*!40000 ALTER TABLE `m_common_abilities` ENABLE KEYS */;
UNLOCK TABLES;


# テーブルのダンプ m_core_abilities
# ------------------------------------------------------------

DROP TABLE IF EXISTS `m_core_abilities`;

CREATE TABLE `m_core_abilities` (
  `id` int NOT NULL AUTO_INCREMENT,
  `keyword` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `effect` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `keyword` (`keyword`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

LOCK TABLES `m_core_abilities` WRITE;
/*!40000 ALTER TABLE `m_core_abilities` DISABLE KEYS */;

INSERT INTO `m_core_abilities` (`id`, `keyword`, `name`, `effect`)
VALUES
	(1,'FLY','飛行 (FLY)','【効果】: 移動時、他のミニチュアや地形、および敵ユニットの接敵範囲を無視して移動できる（垂直方向の移動距離も無視）。ただし、敵の接敵範囲内で移動を終了することはできない。'),
	(2,'CHAMPION','チャンピオン (CHAMPION)','【効果】: この部隊の「チャンピオン（隊長）」が行う攻撃の「回数（Atk）」特性を+1する。'),
	(3,'MUSICIAN','楽士 (MUSICIAN)','【効果】: この部隊に「楽士」が含まれている場合、この部隊が「再集結（Rally）」コマンドを使用するとき、追加で抵抗ロール（D6）を1個多く振ることができる。'),
	(4,'STANDARD BEARER','旗手 (STANDARD BEARER)','【効果】: この部隊に「旗手」が含まれている場合、この部隊のコントロール値（Control Score）に+1する。'),
	(5,'加護セーブ','加護','【効果】: ダメージ手順のステップ1において、このユニットのダメージプールにあるダメージ1ポイントにつき、D6の加護ロールを1回実行する。加護ロールがこのユニットの加護の値以上であれば、その1ポイントのダメージはダメージプールから取り除かれる。');

/*!40000 ALTER TABLE `m_core_abilities` ENABLE KEYS */;
UNLOCK TABLES;


# テーブルのダンプ m_faction_season_enhancement_labels
# ------------------------------------------------------------

DROP TABLE IF EXISTS `m_faction_season_enhancement_labels`;

CREATE TABLE `m_faction_season_enhancement_labels` (
  `faction_id` int NOT NULL,
  `season` varchar(16) NOT NULL,
  `label_ja` varchar(255) NOT NULL,
  `label_en` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`faction_id`,`season`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

LOCK TABLES `m_faction_season_enhancement_labels` WRITE;
/*!40000 ALTER TABLE `m_faction_season_enhancement_labels` DISABLE KEYS */;

INSERT INTO `m_faction_season_enhancement_labels` (`faction_id`, `season`, `label_ja`, `label_en`)
VALUES
	(1,'2026-27','激闘の傷跡','Battle Scars'),
	(10,'2026-27','モウルダー変異','Moulder Mutations');

/*!40000 ALTER TABLE `m_faction_season_enhancement_labels` ENABLE KEYS */;
UNLOCK TABLES;


# テーブルのダンプ m_faction_terrains
# ------------------------------------------------------------

DROP TABLE IF EXISTS `m_faction_terrains`;

CREATE TABLE `m_faction_terrains` (
  `id` int NOT NULL AUTO_INCREMENT,
  `faction_id` int NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `effect` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `is_faction_specific` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `fk_faction_terrains_faction` (`faction_id`),
  CONSTRAINT `fk_faction_terrains_faction` FOREIGN KEY (`faction_id`) REFERENCES `m_factions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;



# テーブルのダンプ m_factions
# ------------------------------------------------------------

DROP TABLE IF EXISTS `m_factions`;

CREATE TABLE `m_factions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `name_en` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `grand_alliance` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `is_hidden` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

LOCK TABLES `m_factions` WRITE;
/*!40000 ALTER TABLE `m_factions` DISABLE KEYS */;

INSERT INTO `m_factions` (`id`, `name`, `name_en`, `grand_alliance`, `is_hidden`)
VALUES
	(1,'ストームキャスト・エターナル','Stormcast Eternals','Order',0),
	(2,'シルヴァネス','Sylvaneth','Order',1),
	(3,'ルミネス・レルムロード','Lumineth Realm-lords','Order',1),
	(4,'シティ・オヴ・シグマー','Cities of Sigmar','Order',0),
	(5,'セラフォン','Seraphon','Order',1),
	(6,'ドーター・オヴ・カイン','Daughters of Khaine','Order',1),
	(7,'イドネス・ディープキン','Idoneth Deepkin','Order',1),
	(8,'ファイアスレイヤー','Fyreslayers','Order',1),
	(9,'カラドロン・オーバーロード','Kharadron Overlords','Order',1),
	(10,'スケイヴン','Skaven','Chaos',0),
	(11,'スレイヴ・トゥ・ダークネス','Slaves to Darkness','Chaos',1),
	(12,'ブレイド・オヴ・コーン','Blades of Khorne','Chaos',1),
	(13,'ディサイプル・オヴ・ティーンチ','Disciples of Tzeentch','Chaos',1),
	(14,'マゴットキン・オヴ・ナーグル','Maggotkin of Nurgle','Chaos',1),
	(15,'ヘドナイト・オヴ・スラーネッシュ','Hedonites of Slaanesh','Chaos',1),
	(16,'ビースト・オヴ・カオス','Beasts of Chaos','Chaos',1),
	(17,'ソウルブライト・グレイヴロード','Soulblight Gravelords','Death',1),
	(18,'オシアーク・ボーンリーパー','Ossiarch Bonereapers','Death',1),
	(19,'フレッシュイーター・コート','Flesh-eater Courts','Death',1),
	(20,'ナイトホーント','Nighthaunt','Death',0),
	(21,'オルク・ウォークラン','Orruk Warclans','Destruction',1),
	(22,'オゴウル・モウトライブ','Ogor Mautribes','Destruction',1),
	(23,'グルームスパイト・ギット','Gloomspite Gitz','Destruction',1),
	(24,'サン・オヴ・ベヘマット','Sons of Behemat','Destruction',1),
	(25,'ヘルスミス・オヴ・ハシュット','Helsmiths of Hashut','Chaos',1);

/*!40000 ALTER TABLE `m_factions` ENABLE KEYS */;
UNLOCK TABLES;


# テーブルのダンプ m_heroic_traits
# ------------------------------------------------------------

DROP TABLE IF EXISTS `m_heroic_traits`;

CREATE TABLE `m_heroic_traits` (
  `id` int NOT NULL AUTO_INCREMENT,
  `faction_id` int DEFAULT NULL,
  `category` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL COMMENT '能力の分類 (Heroic Traits / Stormforged Qualities)',
  `source_reference` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL COMMENT 'このルールの参照元',
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL COMMENT '名称',
  `points` int DEFAULT '0' COMMENT 'ポイントコスト',
  `is_hero_only` tinyint(1) DEFAULT '1' COMMENT '1: HEROのみ, 0: 対象外',
  `trigger_turn` enum('your','opponent','any','battle') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'your',
  `ability_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL COMMENT 'Passive / Activeなど',
  `activation` enum('active','passive','reaction') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'active',
  `usage_scope` enum('unlimited','once_per_turn','once_per_phase','once_per_battle') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'unlimited',
  `usage_per` enum('unit','army') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'unit',
  `trigger_phase` set('deployment','hero','movement','shooting','charge','combat','end','any') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `icon_type` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `trigger_condition_ja` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `effect` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL COMMENT '効果本文',
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci COMMENT 'フレーバーテキスト',
  `is_hidden` tinyint NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_ht_faction` (`faction_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='ヒーロー特性・能力';

LOCK TABLES `m_heroic_traits` WRITE;
/*!40000 ALTER TABLE `m_heroic_traits` DISABLE KEYS */;

INSERT INTO `m_heroic_traits` (`id`, `faction_id`, `category`, `source_reference`, `name`, `points`, `is_hero_only`, `trigger_turn`, `ability_type`, `activation`, `usage_scope`, `usage_per`, `trigger_phase`, `icon_type`, `trigger_condition_ja`, `effect`, `description`, `is_hidden`)
VALUES
	(1,1,'Aspects of Azyr','アズィルの様相','衝撃と畏怖 SHOCK AND AWE',0,1,'your','Passive','passive','unlimited','unit',NULL,NULL,'パッシブ','【効果】：このユニットが「嵐の末裔（Scions of the Storm）」で配置されるたび、そのターン終了時まで、このユニットを対象とする攻撃のヒットロールから-1する。また、このユニットから12インチ以内に「嵐の末裔」で配置された味方の「ストームキャスト・エターナル」ユニットも同様の効果を得る。','この戦士と彼らのストームキャストが戦場に降り立つ時、その輝く威容は敵に恐怖を植え付ける。',0),
	(2,1,'Aspects of Azyr','アズィルの様相','天界の使節 ENVOY OF THE HEAVENS',20,1,'your','Active','active','unlimited','unit','hero',NULL,'自軍側ヒーローフェイズ','【宣言】：前ターンに味方の「ストームキャスト・エターナル」ユニットが全滅している場合、このユニットから12インチ以内の視線が通っている味方1体を選択する。\n【効果】：対象は次の自軍ターン開始時まで「加護（WARD）5+」を得る。','ストームキャスト・エターナルが倒れる時、その魂をアジィルへ運ぶ光が、戦い続ける者たちを鼓舞する。',0),
	(3,1,'Aspects of Azyr','アズィルの様相','断固たる守護者 STAUNCH DEFENDER',0,1,'your','Passive','passive','unlimited','unit',NULL,NULL,'パッシブ','【効果】：このユニットが自軍領地内の作戦目標を争奪している間、その目標を争奪しているすべての味方「ストームキャスト・エターナル」ユニットのコントロール値に+3する。','シグマーの聖地を争うことは、アジィルの全能の力を呼び起こすことと同義である。',0),
	(4,1,'Stormforged Qualities','グューランの禍事','伝説の不屈 LEGENDARY TENACITY',0,1,'your','Passive','passive','unlimited','unit',NULL,NULL,'パッシブ','【効果】：このユニットが最初に全滅する時、破壊を取り除く前にダイスを振る。3+の場合、このユニットは破壊されず、残りのダメージも無効化し、さらに(D3)回復する。','この英雄は打ち倒されるかもしれないが、死の国において彼らの再起を阻むものはほとんどない。',1),
	(5,1,'Stormforged Qualities','グューランの禍事','栄光の刻 HOUR OF GLORY',0,1,'your','Passive','passive','unlimited','unit',NULL,NULL,'パッシブ','【効果】：このユニットは「最高の瞬間（Their Finest Hour）」をバトル中に複数回使用できる。さらに、使用するたびにそのターンの終了時までコントロール値に+5する。','英雄の時が来た。その行いは悪を打ち砕き、弱きを救い、邪悪から定命の国を守るだろう。',1),
	(6,1,'Stormforged Qualities','グューランの禍事','強烈な敬虔 INTENSE PIETY',0,1,'your','Active','active','unlimited','unit','hero',NULL,'自軍側ヒーローフェイズ','【宣言】：18インチ以内の敵の「プリースト」または「ウィザード」1体を選択する。\n【効果】：ダイスを振る。3+の場合、次の自軍ターン開始時まで、対象の詠唱ロールおよび詠唱（チャント）ロールから-1する。','シグマーの使命に対する不屈の献身は、敵対する神の信徒たちを沈黙させるほどの重圧となる。',1),
	(7,10,'DEVIOUS MACHINATIONS',NULL,'逃ゲるが勝チ！ (Scurry Away)',0,1,'any',NULL,'active','unlimited','unit','combat','Movement','任意の近接フェイズ','効果：このユニットが白兵戦状態にある場合、ダイスを1個振る。3+の場合、このユニットは自身の移動フェイズであるかのように、ただちに「退却」能力を使用できる。','このスケイヴンの頭目は、状況が不利になりそうだと察すると、安全な場所へと逃げ出すことに躊躇いを持たない。彼らがこれほど長く生き延びてこられたのも、おそらくそれが理由だろう。',0),
	(8,10,'DEVIOUS MACHINATIONS',NULL,'癇癪持ち',0,1,'your','パッシブ','passive','unlimited','unit',NULL,'Movement','パッシブ','効果：味方の［スケイヴン］ユニットがこのユニットから13インチ以内に完全に収まっている間、それらのユニットの全力移動ロールと突撃ロールに+1する。','このピクピクと震える短気なスケイヴンは、配下のミニオンたちに、敵よりも自分自身の不興を買うことを恐れさせることに成功している。',0),
	(9,10,'DEVIOUS MACHINATIONS',NULL,'熟練の操り手',0,1,'your','パッシブ','passive','unlimited','unit',NULL,'Deffensive','パッシブ','効果：このユニットが、［ヒーロー］ではない味方の［スケイヴン］・［インファントリー］ユニットの白兵戦間合い内にいる間、以下の2つの効果を得る：\n・このユニットは加護（4+）を得る。\n・このユニットの加護ロールに成功するたびに、このユニットへのダメージ解決シーケンスが終了した後、このユニットの白兵戦間合い内にいる［ヒーロー］ではない味方の［スケイヴン］・［インファントリー］ユニット1個に1ダメージを割り振る（このダメージに対して加護ロールを行うことはできない）。','このスケイヴンのウォーロードは特に狡猾で利己的であり、潜在的な脅威と自分自身との間に、配下のミニオンたちが常に都合よく配置されるよう巧みに立ち回る。',0);

/*!40000 ALTER TABLE `m_heroic_traits` ENABLE KEYS */;
UNLOCK TABLES;


# テーブルのダンプ m_keywords_master
# ------------------------------------------------------------

DROP TABLE IF EXISTS `m_keywords_master`;

CREATE TABLE `m_keywords_master` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `keyword_type` enum('unit','faction') NOT NULL DEFAULT 'unit',
  `effect` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `sort_order` int NOT NULL DEFAULT '0',
  `accepts_param` tinyint(1) NOT NULL DEFAULT '0',
  `faction_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_m_keywords_master_name_type` (`name`,`keyword_type`),
  KEY `idx_keywords_master_faction` (`faction_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

LOCK TABLES `m_keywords_master` WRITE;
/*!40000 ALTER TABLE `m_keywords_master` DISABLE KEYS */;

INSERT INTO `m_keywords_master` (`id`, `name`, `keyword_type`, `effect`, `sort_order`, `accepts_param`, `faction_id`)
VALUES
	(1,'飛行','unit','【効果】: 移動時、他のミニチュアや地形、および敵ユニットの接敵範囲を無視して移動できる（垂直方向の移動距離も無視）。ただし、敵の接敵範囲内で移動を終了することはできない。',9,0,NULL),
	(2,'チャンピオン','unit','【効果】: この部隊の「チャンピオン（隊長）」が行う攻撃の「回数（Atk）」特性を+1する。',5,0,NULL),
	(3,'楽士','unit','【効果】: この部隊に「楽士」が含まれている場合、この部隊が「再集結（Rally）」コマンドを使用するとき、追加で抵抗ロール（D6）を1個多く振ることができる。',6,0,NULL),
	(4,'旗手','unit','【効果】: この部隊に「旗手」が含まれている場合、この部隊のコントロール値（Control Score）に+1する。',7,0,NULL),
	(5,'加護','unit','【効果】: ダメージ手順のステップ1において、このユニットのダメージプールにあるダメージ1ポイントにつき、D6の加護ロールを1回実行する。加護ロールがこのユニットの加護の値以上であれば、その1ポイントのダメージはダメージプールから取り除かれる。',8,1,NULL),
	(6,'固有','unit',NULL,12,0,NULL),
	(7,'総大将','unit',NULL,13,0,NULL),
	(8,'魔術師','unit',NULL,10,1,NULL),
	(9,'神官','unit',NULL,11,1,NULL),
	(10,'歩兵','unit',NULL,2,0,NULL),
	(11,'騎兵','unit',NULL,3,0,NULL),
	(12,'大型獣','unit',NULL,4,0,NULL),
	(13,'英雄','unit',NULL,1,0,NULL),
	(14,'戦闘兵器','unit',NULL,14,0,NULL),
	(15,'遮蔽物','unit',NULL,15,0,NULL),
	(16,'通行不可','unit',NULL,16,0,NULL),
	(17,'ルイネーションチャンバー','unit',NULL,0,0,1),
	(18,'ウォリアーチャンバー','unit',NULL,0,0,1);

/*!40000 ALTER TABLE `m_keywords_master` ENABLE KEYS */;
UNLOCK TABLES;


# テーブルのダンプ m_manifestation_lores
# ------------------------------------------------------------

DROP TABLE IF EXISTS `m_manifestation_lores`;

CREATE TABLE `m_manifestation_lores` (
  `id` int NOT NULL AUTO_INCREMENT,
  `faction_id` int NOT NULL,
  `unit_id` int DEFAULT NULL,
  `lore_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `manifestation_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `casting_value` int NOT NULL,
  `effect` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `activation` enum('active','passive','reaction') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'active',
  `usage_scope` enum('unlimited','once_per_turn','once_per_phase','once_per_battle') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'unlimited',
  `usage_per` enum('unit','army') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'unit',
  `trigger_phase` set('deployment','hero','movement','shooting','charge','combat','end','any') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `trigger_turn` enum('your','opponent','any','battle') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'your',
  `icon_type` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `trigger_condition_ja` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `keywords` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `flavor_text` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `points` int DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='顕現伝承';

LOCK TABLES `m_manifestation_lores` WRITE;
/*!40000 ALTER TABLE `m_manifestation_lores` DISABLE KEYS */;

INSERT INTO `m_manifestation_lores` (`id`, `faction_id`, `unit_id`, `lore_name`, `manifestation_name`, `casting_value`, `effect`, `activation`, `usage_scope`, `usage_per`, `trigger_phase`, `trigger_turn`, `icon_type`, `trigger_condition_ja`, `keywords`, `flavor_text`, `points`, `created_at`)
VALUES
	(1,1,201,'嵐の顕現 (Manifestations of the Storm)','秘術の台座を召喚',6,'【宣言】戦場に味方の秘術の台座が存在しない場合、この呪文を詠唱する味方の「ストームキャスト・エターナル・魔術師」1人を選ぶ。その後、その魔術師から12インチ以内に完全に収まっており、「飛行（Fly）」を持たない味方の「ストームキャスト・エターナル・歩兵・英雄」1体を対象として選び、2D6で詠唱ロールを行う。\r\n\r\n【効果】対象から1/2インチ以内に一部でも入っており、かつ詠唱者から視認状態にあり、さらに敵ユニットと戦闘状態にならない位置に秘術の台座1個を配置する。その後、対象（ヒーロー）を戦場から一度取り除き、その秘術の台座のプラットフォームの上に配置する。','active','unlimited','unit',NULL,'your',NULL,NULL,'呪文、召喚','魔術師は一筋の稲妻を足元の地面に呼び落とす。落雷が去った後には完璧なシグマライト製の円盤が残され、選ばれたストームキャストがそれに飛び乗ることで、空へと舞い上がることができる。',0,'2026-06-10 18:14:07'),
	(2,1,202,'嵐の顕現 (Manifestations of the Storm)','天空の渦を召喚',6,'【宣言】戦場に味方の天空の渦が存在しない場合、この呪文を詠唱する味方の「ストームキャスト・エターナル・ウィザード」1人を選び、2D6で詠唱ロールを行う。\r\n\r\n【効果】詠唱者から18インチ以内に完全に収まり、かつすべての敵ユニットから9インチより離れた位置に天空の渦1個を配置する。','active','unlimited','unit',NULL,'your',NULL,NULL,'呪文、召喚','魔術師は魔力が吹き込まれた一対のハンマーを空中に放り投げる。ハンマーは回転を始め、渦が激しさを増すにつれてその数を増やし、敵の頭蓋を粉砕する大嵐（メイルストローム）へと変貌していく。',0,'2026-06-10 18:14:07'),
	(3,1,203,'嵐の顕現 (Manifestations of the Storm)','恒久に燃ゆる彗星を召喚',8,'【宣言】戦場に味方の恒久に燃ゆる彗星が存在しない場合、この呪文を詠唱する味方の「ストームキャスト・エターナル・ウィザード」1人を選び、2D6で詠唱ロールを行う。\r\n\r\n【効果】詠唱者から18mv以内に完全に収まる位置に恒久に燃ゆる彗星1個を配置する。','active','unlimited','unit',NULL,'your',NULL,NULL,'呪文、召喚','天に向かって腕を突き出し、魔術師はアジィル（天界）のエネルギーでできた純粋な彗星を呼び落とす。それは凄まじい破壊力をもって敵の隊列へと激突する。',0,'2026-06-10 18:14:07'),
	(4,10,207,'死滅の顕現 (Manifestations of Doom)','歪みの雷渦を召喚',7,'【宣言】：戦場に味方の「ワープライトニング・ヴォルテクス（Warp Lightning Vortex）」が存在しない場合、この呪文を詠唱する味方の「スケイヴン・ウィザード（SKAVEN WIZARD）」1人を選び、2D6で詠唱ロールを行う。\r\n\r\n【効果】：最初の「ワープライトニング・ヴォルテクス」1個を、詠唱者から18インチ以内に一部でも入っており、視線が通っている位置に配置する。その後、2個目と3個目のパーツを、最初のパーツから正確に7インチ離れ、かつお互いから正確に7インチ離れた、三角形を形成する位置に配置する。','active','unlimited','unit',NULL,'your',NULL,NULL,'呪文、召喚','ワープストーンの破片が空中に投げつけられると、それらはあり得ないほど巨大化し、ワープライトニングの稲妻を放ち始める。',0,'2026-06-12 14:29:49'),
	(5,10,206,'死滅の顕現 (Manifestations of Doom)','大鼠波を召喚',7,'【宣言】：戦場に味方の「ヴァーミナタイド（Vermintide）」が存在しない場合、この呪文を詠唱する味方の「スケイヴン・ウィザード（SKAVEN WIZARD）」1人を選び、2D6で詠唱ロールを行う。\n\n【効果】：「ヴァーミナタイド」1個を、詠唱者から13インチ以内に完全に収まり、視線が通っており、かつすべての敵ユニットから9インチより離れた位置に配置する。','active','unlimited','unit',NULL,'your',NULL,NULL,'呪文、召喚','スケイヴンの呪文詠唱者は、その進路にあるすべてのものを食い尽くす、魔術的な大鼠のうごめく群れを召喚することができる。',0,'2026-06-12 14:29:49'),
	(6,10,205,'死滅の顕現 (Manifestations of Doom)','破滅の鐘を召喚',7,'【宣言】：戦場に味方の「破滅の鐘（Bell of Doom）」が存在しない場合、この呪文を詠唱する味方の「スケイヴン・ウィザード（SKAVEN WIZARD）」1人を選び、2D6で詠唱ロールを行う。\n\n【効果】：「破滅の鐘」1個を、詠唱者から13インチ以内に完全に収まり、視線が通っており、かつすべての敵ユニットから9インチより離れた位置に配置する。','active','unlimited','unit',NULL,'your',NULL,NULL,'呪文、召喚','古代の創世神話を呼び覚ますことで、呪文詠唱者は戦場に巨大な「破滅の鐘」を顕現させる。',0,'2026-06-12 14:29:49');

/*!40000 ALTER TABLE `m_manifestation_lores` ENABLE KEYS */;
UNLOCK TABLES;


# テーブルのダンプ m_prayer_lores
# ------------------------------------------------------------

DROP TABLE IF EXISTS `m_prayer_lores`;

CREATE TABLE `m_prayer_lores` (
  `id` int NOT NULL AUTO_INCREMENT,
  `faction_id` int NOT NULL COMMENT '所属陣営のID',
  `lore_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL COMMENT '奇跡伝承名',
  `prayer_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL COMMENT '祈祷名（英文・和文）',
  `chanting_value` int NOT NULL COMMENT '発動・蓄積に必要な祈祷値（Chanting Value）',
  `effect` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL COMMENT '宣言と効果のテキスト本文',
  `activation` enum('active','passive','reaction') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'active',
  `usage_scope` enum('unlimited','once_per_turn','once_per_phase','once_per_battle') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'unlimited',
  `usage_per` enum('unit','army') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'unit',
  `trigger_phase` set('deployment','hero','movement','shooting','charge','combat','end','any') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `trigger_turn` enum('your','opponent','any','battle') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'your',
  `icon_type` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `trigger_condition_ja` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `keywords` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL COMMENT 'キーワード（PRAYER, UNLIMITEDなど）',
  `flavor_text` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci COMMENT 'フレーバーテキスト',
  `points` int DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_prayer_lores_faction` (`faction_id`),
  CONSTRAINT `fk_prayer_lores_faction` FOREIGN KEY (`faction_id`) REFERENCES `m_factions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='奇跡伝承（祈祷）';

LOCK TABLES `m_prayer_lores` WRITE;
/*!40000 ALTER TABLE `m_prayer_lores` DISABLE KEYS */;

INSERT INTO `m_prayer_lores` (`id`, `faction_id`, `lore_name`, `prayer_name`, `chanting_value`, `effect`, `activation`, `usage_scope`, `usage_per`, `trigger_phase`, `trigger_turn`, `icon_type`, `trigger_condition_ja`, `keywords`, `flavor_text`, `points`, `created_at`)
VALUES
	(1,10,'有害なる祈り','汚濁、汚濁ヲ！ (FILTH-CRUST)',4,'【宣言】：味方の「スケイヴン・神官（SKAVEN PRIEST）」1体を選択してこの祈祷を唱え、その兵から13インチ以内に完全に収まっており、視線が通っている味方の「スケイヴン・歩兵（SKAVEN INFANTRY）」ユニット1個を対象として選び、D6で祈祷ロールを行う。\r\n【効果】：次の自軍側ターン開始時まで、対象の近接攻撃によるウーンズロールに+1する。さらに、祈祷ロールの結果が8+であった場合、次の自軍側ターン開始時まで、対象の近接武器は「クリティカル（致命傷）[Crit (Mortal)]」を得る。','active','unlimited','unit',NULL,'your',NULL,NULL,'奇蹟、無制限','大いなる角付きネズミは、この司祭の呼び声に応じ、従僕たちの武器に抗いがたい伝染病を纏わせる。',0,'2026-06-12 14:20:30'),
	(2,10,'有害なる祈り','菌塗れの奔流 (BILE-TORRENT)',4,'【宣言】：味方の「スケイヴン・神官（SKAVEN PRIEST）」1体を選択してこの祈祷を唱え、その兵から13インチ以内に一部でも入っており、視認状態である敵ユニット1個を対象として選び、D6の祈祷ロールを行う。\r\n\r\n【効果】：対象ユニットの兵数に等しい個数のダイスをロールする。5+が出るごとに、対象に1ポイントの致命的ダメージを与える。もし祈祷ロールの結果が8+であった場合は、代わりに4+が出るごとに1ポイントの致命的ダメージを与える。','active','unlimited','unit',NULL,'your',NULL,NULL,'奇蹟','緑色に妖しく光る眼をした司祭が金切声をあげると、菌塗れの奔流が敵を襲う。',0,'2026-06-12 14:20:30'),
	(3,10,'有害なる祈り','狂信的な粘り強さ (RABID-TOUGH)',5,'【宣言】：味方の「スケイヴン・プリースト（SKAVEN PRIEST）」1体を選択してこの祈祷を唱え、その兵から13インチ以内に完全に収まっており、視線が通っている味方の「スケイヴン・歩兵（SKAVEN INFANTRY）」ユニット1個を対象として選び、D6の祈願ロールを行う。\r\n\r\n【効果】：次の自軍側ターン開始時まで、対象ユニットを目標とする攻撃のウーンズロールの結果を-1する。さらに、祈願ロールの結果が8+であった場合、次の自軍側ターン開始時まで、対象ユニットのセーブロールに+1する。','active','unlimited','unit',NULL,'your',NULL,NULL,'奇蹟','司祭が唸り声をあげて濃密な毒霧を呼び出すと、追随者たちは狂乱状態に陥り、痛覚を麻痺させる。',0,'2026-06-12 14:20:30'),
	(4,1,'ストームホストの奇蹟','嵐の治療',4,'【宣言】：この祈祷を祈願する際に、味方ストームキャストエターナル・神官を1体を選択する。その神官の12mv以内に全体が入っており、かつその神官から視認状態である味方ストームキャストエターナル・ユニットを1個選択し、D6の祈願ロールをする。\r\n\r\n【効果】：選択された味方ユニットを回復(D3)する。この祈願ロール結果が8+であった場合、祈願者の12mv以内に全体が入っており、かつその祈願者から視認状態である各味方ストームキャストエターナル・ユニットをそれぞれ回復(D3)する。','active','unlimited','unit',NULL,'your',NULL,NULL,'奇蹟、無制限','The priest calls down cleansing bolts from the sky, their invigorating energies closing even the most grievous wounds.',0,'2026-06-19 23:35:35'),
	(5,1,'ストームホストの奇蹟','武器祝福',6,'【宣言】： この祈祷を唱える友軍のストームキャスト・エターナル：神官を1体選択する。次に、その神官から12mv以内の範囲に完全に収まっている、視認可能な味方ストームキャスト・エターナル：歩兵ユニットを1つ、対象として選択し、D6で祈願ロールを行う。\r\n\r\n【効果】： 次の自分の手番の開始時まで、対象となったユニットの近接武器の「攻撃回数」特性に1を加える。祈願ロールの結果が12以上だった場合、このアビリティの効果は、その神官から12mv以内の範囲に完全に収まっている、すべての視認可能なみストームキャスト・エターナルユニットに適用される。','active','unlimited','unit',NULL,'your',NULL,NULL,'奇蹟','The priest imbues the weapons of their brethren with the pure essence of the storm.',0,'2026-06-19 23:36:05'),
	(6,1,'ストームホストの奇蹟','転送',4,'【宣言】： この祈祷を唱える味方ストームキャスト・エターナル：神官を1体選択する。次に、その神官から12mv以内の範囲に完全に収まっている、視認可能な味方ストームキャスト・エターナルユニットを1つ、対象として選択し、D6で祈願ロールを行う。\n\n【効果】： 対象となったユニットを戦場から取り除き、すべての敵ユニットから9mv以上離れた戦場上の位置に再配置する。さらに、きロールの結果が8以上だった場合、そのバトルラウンド終了時まで、そのユニットを対象とする攻撃のヒットロールから1を引く。','active','unlimited','unit',NULL,'your',NULL,NULL,'奇蹟','The priest summons bolts of lightning to transport nearby warriors across the battlefield.',0,'2026-06-19 23:36:22');

/*!40000 ALTER TABLE `m_prayer_lores` ENABLE KEYS */;
UNLOCK TABLES;


# テーブルのダンプ m_regiment_options
# ------------------------------------------------------------

DROP TABLE IF EXISTS `m_regiment_options`;

CREATE TABLE `m_regiment_options` (
  `id` int NOT NULL AUTO_INCREMENT,
  `faction_id` int DEFAULT NULL,
  `option_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `option_code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `sort_order` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_faction_option` (`faction_id`,`option_name`),
  CONSTRAINT `fk_regiment_options_faction` FOREIGN KEY (`faction_id`) REFERENCES `m_factions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

LOCK TABLES `m_regiment_options` WRITE;
/*!40000 ALTER TABLE `m_regiment_options` DISABLE KEYS */;

INSERT INTO `m_regiment_options` (`id`, `faction_id`, `option_name`, `option_code`, `sort_order`)
VALUES
	(1,1,'ストームキャスト・エグゼンプラー','STORMCAST EXEMPLAR',4),
	(2,1,'グリフハウンズ','GRYPH-HOUNDS',5),
	(3,1,'ルイネーションチャンバー','RUINATION CHAMBER',2),
	(4,1,'ウォリアーチャンバー','WARRIOR CHAMBER',3),
	(7,1,'ストームキャスト・エターナル','STORMCAST ETERNALS',1),
	(8,1,'クエスター・ソウルスウォーン',NULL,6),
	(9,10,'スケイヴン',NULL,1),
	(10,10,'ヴァーミヌス',NULL,3),
	(11,10,'スクリール',NULL,4),
	(12,10,'エシン',NULL,6),
	(13,10,'マスタークラン',NULL,7),
	(14,10,'スケイヴン・オーバークロウ',NULL,8),
	(15,10,'クランラット',NULL,2),
	(16,10,'モウルダー',NULL,5);

/*!40000 ALTER TABLE `m_regiment_options` ENABLE KEYS */;
UNLOCK TABLES;


# テーブルのダンプ m_season_enhancement_keywords
# ------------------------------------------------------------

DROP TABLE IF EXISTS `m_season_enhancement_keywords`;

CREATE TABLE `m_season_enhancement_keywords` (
  `enhancement_id` int NOT NULL,
  `keyword_id` int NOT NULL,
  `requirement` enum('require','exclude') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'require' COMMENT '除外、含むどちらにするか',
  PRIMARY KEY (`enhancement_id`,`keyword_id`),
  KEY `idx_season_enh_kw_keyword` (`keyword_id`),
  CONSTRAINT `fk_season_enh_kw_enh` FOREIGN KEY (`enhancement_id`) REFERENCES `m_season_enhancements` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_season_enh_kw_keyword` FOREIGN KEY (`keyword_id`) REFERENCES `m_keywords_master` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='アキュシーの禍事専用追加能力のユニットキーワード登録用';

LOCK TABLES `m_season_enhancement_keywords` WRITE;
/*!40000 ALTER TABLE `m_season_enhancement_keywords` DISABLE KEYS */;

INSERT INTO `m_season_enhancement_keywords` (`enhancement_id`, `keyword_id`, `requirement`)
VALUES
	(1,13,'exclude'),
	(1,14,'exclude'),
	(2,13,'exclude'),
	(2,14,'exclude'),
	(3,13,'exclude'),
	(3,14,'exclude'),
	(4,12,'exclude'),
	(4,13,'exclude'),
	(5,12,'exclude'),
	(5,13,'exclude'),
	(6,12,'exclude'),
	(6,13,'exclude');

/*!40000 ALTER TABLE `m_season_enhancement_keywords` ENABLE KEYS */;
UNLOCK TABLES;


# テーブルのダンプ m_season_enhancements
# ------------------------------------------------------------

DROP TABLE IF EXISTS `m_season_enhancements`;

CREATE TABLE `m_season_enhancements` (
  `id` int NOT NULL AUTO_INCREMENT,
  `faction_id` int NOT NULL,
  `season` varchar(16) NOT NULL DEFAULT '2026-27',
  `name` varchar(255) NOT NULL,
  `effect` text NOT NULL,
  `points` int NOT NULL DEFAULT '0',
  `sort_order` int NOT NULL DEFAULT '0',
  `is_hidden` tinyint(1) NOT NULL DEFAULT '0',
  `activation` enum('active','passive','reaction') NOT NULL DEFAULT 'active',
  `usage_scope` enum('unlimited','once_per_turn','once_per_phase','once_per_battle') NOT NULL DEFAULT 'unlimited',
  `usage_per` enum('unit','army') NOT NULL DEFAULT 'unit',
  `trigger_phase` set('deployment','hero','movement','shooting','charge','combat','end','any') DEFAULT NULL,
  `trigger_turn` enum('your','opponent','any','battle') NOT NULL DEFAULT 'your',
  `trigger_condition_ja` varchar(255) DEFAULT NULL,
  `flavor` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_season_enh_faction_season` (`faction_id`,`season`),
  KEY `idx_season_enh_sort` (`faction_id`,`season`,`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='アキュシーの禍事専用能力';

LOCK TABLES `m_season_enhancements` WRITE;
/*!40000 ALTER TABLE `m_season_enhancements` DISABLE KEYS */;

INSERT INTO `m_season_enhancements` (`id`, `faction_id`, `season`, `name`, `effect`, `points`, `sort_order`, `is_hidden`, `activation`, `usage_scope`, `usage_per`, `trigger_phase`, `trigger_turn`, `trigger_condition_ja`, `flavor`)
VALUES
	(1,10,'2026-27','移植された脳','効果：以下の中から脳を1 つ選択する。そのターン中、選択された脳の効果を適用する：\n歪み石中毒者の脳：このユニットのきは、ヒットロールに +1 の修正を受ける。このユニットは最大 1 の確保スコアを持つ。\nウォーロックの幼鼠脳：このユニットの確保力は +10 の修正を受ける。',0,1,0,'active','unlimited','unit','hero','any','任意のヒーローフェイズ','モールダーの「ボランティア」たちの脳組織が変異体に移植されており、一瞬で人格を入れ替えることが可能だ。'),
	(2,10,'2026-27','同化促進剤','効果：このユニットがこのターン中に配置されていた場合、そのターン中、このユニットの突撃ロールのダイス個数は、1 個増加（ダイス個数は最大 3 個まで）する。ロール後に任意のダイスを 1 個取り除いて、残りのダイスを突撃ロールに用いる。',0,2,0,'active','unlimited','unit','charge','your','自軍側突撃フェイズ','四肢の筋肉に急速成長を促す血清が注入されており、それにより恐るべき速度で移動できる。'),
	(3,10,'2026-27','鋸骨突起','このユニットを対象とした敵ユニットのきのヒットロールで修正前の出目 1 が出た場合、『近接攻撃』アビリティが解決された直後に、攻撃側ユニットは 1 ポイントの致命的ダメージを受ける。',0,3,0,'passive','unlimited','unit','combat','any','パッシブ','鋭い骨片が皮膚を突き破り、このユニットを攻撃する者の肉を深く抉る。'),
	(4,1,'2026-27','束縛なき稲妻','このユニットと近接戦闘中である敵ユニットが移動するか、あるいは戦場から取り除かれる際、そのアビリティが解決された直後に、その敵ユニットが戦場に配置されており、かつこのユニットと近接戦闘中ではない場合、その敵ユニットにD3ポイントの致命的ダメージを与える。',0,1,0,'passive','unlimited','unit','any','any','パッシブ','これらの戦士たちが帯びる神秘の稲妻は不安定と化し、その憤怒から逃れようとする者に己の意思とは無関係に襲いかかる。'),
	(5,1,'2026-27','アンバーストーン歩哨の古参兵','敵ユニットがこのユニットと近接戦闘中である間、その敵ユニットは『先手効果』キーワードを持っていないものとして扱われる。\nデザイナーズ・ノート：『後手効果』を有する敵ユニットは『後手効果』を持ったままになる。『先手効果』も『後手効果』も両方持っていないものとしてはみなされない。',0,2,0,'passive','unlimited','unit','combat','any','パッシブ','狡猾な敵対者と戦い慣れている戦士たちは、有利に戦いを運ぶ。'),
	(6,1,'2026-27','炎の魂','このユニットが全滅したとき、自軍は3憤激ダイスを獲得する。',0,3,0,'passive','unlimited','unit','any','any','パッシブ','これらの戦士たちが物質の体を失うとき、華々しい爆発で散ってゆく。');

/*!40000 ALTER TABLE `m_season_enhancements` ENABLE KEYS */;
UNLOCK TABLES;


# テーブルのダンプ m_spell_lores
# ------------------------------------------------------------

DROP TABLE IF EXISTS `m_spell_lores`;

CREATE TABLE `m_spell_lores` (
  `id` int NOT NULL AUTO_INCREMENT,
  `faction_id` int NOT NULL,
  `lore_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `spell_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `casting_value` int NOT NULL,
  `effect` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `activation` enum('active','passive','reaction') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'active',
  `usage_scope` enum('unlimited','once_per_turn','once_per_phase','once_per_battle') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'unlimited',
  `usage_per` enum('unit','army') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'unit',
  `trigger_phase` set('deployment','hero','movement','shooting','charge','combat','end','any') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `trigger_turn` enum('your','opponent','any','battle') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'your',
  `icon_type` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `trigger_condition_ja` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `keywords` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `flavor_text` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `points` int DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='魔法伝承';

LOCK TABLES `m_spell_lores` WRITE;
/*!40000 ALTER TABLE `m_spell_lores` DISABLE KEYS */;

INSERT INTO `m_spell_lores` (`id`, `faction_id`, `lore_name`, `spell_name`, `casting_value`, `effect`, `activation`, `usage_scope`, `usage_per`, `trigger_phase`, `trigger_turn`, `icon_type`, `trigger_condition_ja`, `keywords`, `flavor_text`, `points`, `created_at`)
VALUES
	(1,1,'嵐の呪文伝承 (Lore of the Storm)','稲妻の猛爆',5,'【宣言】この呪文を詠唱する味方の「ストームキャスト・エターナル・魔術師」を1人選び、その兵から最も近くにいて、このターンにまだこのアビリティの対象に選ばれていない、視線が通っている敵ユニット1個を対象として選ぶ。もし2個以上の敵ユニットが同着で最も近い場合、どちらを対象にするかあなたが選ぶことができる。その後、2D6で詠唱ロールを行う。\n\n【効果】対象にD3の致命傷を与える。','active','unlimited','unit',NULL,'your',NULL,NULL,'呪文、無制限','魔術師は体内に溜め込んだ嵐のエネルギーを敵に向けて放出する。パチパチと音を立てる電弧によって敵が焼き尽くされ、吹き飛ばされると、オゾンの刺激臭と肉の焦げる臭いが辺りに立ち込める。',0,'2026-06-10 18:05:37'),
	(2,1,'嵐の呪文伝承 (Lore of the Storm)','雷鳴破',6,'【宣言】この呪文を詠唱する味方の「ストームキャスト・エターナル・魔術師」を1人選び、その兵から12mv以内に一部でも入っていて視線が通っている敵ユニット1個を対象として選び、2D6で詠唱ロールを行う。\r\n\r\n【効果】次の自軍側ターン開始時まで、対象の敵ユニットが行う攻撃のウーンズロールの結果は-1の修正を受ける。','active','unlimited','unit',NULL,'your',NULL,NULL,'呪文','魔術師はジグマーの嵐の力を純粋な衝撃波として解き放ち、敵を朦朧とさせ、方向感覚を失わせる。',0,'2026-06-10 18:05:37'),
	(3,1,'嵐の呪文伝承 (Lore of the Storm)','星降り',7,'【宣言】この呪文を詠唱する味方の「ストームキャスト・エターナル・魔術師」を1人選び、その兵から18mv以内に一部でも入っていて視線が通っている敵の「歩兵」または「騎兵」ユニット1個を対象として選び、2D6で詠唱ロールを行う。\r\n\r\n【効果】あなたの次のターンの開始時まで、対象のユニットが突撃ロールを行う際にロールするダイスの数を-1個する（最低1個まで）。','active','unlimited','unit',NULL,'your',NULL,NULL,'呪文','天を仰ぎ見ながら、魔術師は敵の進路に流星の雨を降らせ、アグレッシブな進撃を慎重な前進へと変えざるを得なくする。',0,'2026-06-10 18:05:37'),
	(4,10,'破滅の呪文体系','地走り',6,'【宣言】：この呪文を詠唱する際に、味方スケイヴン・魔術師を1体選択する。その際、選択された魔術師の13mv以内に全体が入っており、かつその魔術師から視認状態である味方スケイヴン・英雄を1対選択し、2D6の詠唱ロールをする。\r\n【効果】：選択された味方英雄を戦場から取り除き、あらゆる敵ユニットから9mvより遠く離れた戦場の位置に再配置する。','active','unlimited','unit',NULL,'your',NULL,NULL,'呪文、無制限',NULL,0,'2026-06-12 13:26:36'),
	(5,10,'破滅の呪文体系','萎れ病',6,'【宣言】：この呪文を詠唱する際に、味方スケイヴン・魔術師を1体選択する。その後、選択された魔術師の13mv以内に一部でも入っており、かつ視認状態である敵ユニットを1個選択し2D6の詠唱ロールをする。\r\n【効果】：選択された敵ユニットはD3ポイントの致命的ダメージを受ける。','active','unlimited','unit',NULL,'your',NULL,NULL,'呪文',NULL,0,'2026-06-12 13:48:54'),
	(6,10,'破滅の呪文体系','歪みの旋風',6,'【宣言】：この呪文を詠唱する際に、味方スケイヴン・魔術師を1対選択する。その後、選択された魔術師の18mv以内に一部でも入っており、かつ視認状態である敵ユニットを1個選択し、2D6の詠唱ロールをする。\r\n【効果】：そのターン中、選択された敵ユニットは『後手効果』を得る。','active','unlimited','unit',NULL,'your',NULL,NULL,'呪文','不浄なる言葉で呪文を唱え、魔術師は現実空間のとばりを引き裂き、超自然的なエネルギーの旋風を呼び寄せる。',0,'2026-06-12 13:52:39');

/*!40000 ALTER TABLE `m_spell_lores` ENABLE KEYS */;
UNLOCK TABLES;


# テーブルのダンプ m_unit_abilities
# ------------------------------------------------------------

DROP TABLE IF EXISTS `m_unit_abilities`;

CREATE TABLE `m_unit_abilities` (
  `id` int NOT NULL AUTO_INCREMENT,
  `unit_id` int NOT NULL,
  `ability_id` int NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_m_unit_abilities` (`unit_id`,`ability_id`),
  KEY `unit_id` (`unit_id`),
  KEY `ability_id` (`ability_id`),
  CONSTRAINT `m_unit_abilities_ibfk_1` FOREIGN KEY (`unit_id`) REFERENCES `m_units` (`id`) ON DELETE CASCADE,
  CONSTRAINT `m_unit_abilities_ibfk_2` FOREIGN KEY (`ability_id`) REFERENCES `m_ability_master` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='ユニットごとにアビリティとの連携';

LOCK TABLES `m_unit_abilities` WRITE;
/*!40000 ALTER TABLE `m_unit_abilities` DISABLE KEYS */;

INSERT INTO `m_unit_abilities` (`id`, `unit_id`, `ability_id`)
VALUES
	(1249,5,2),
	(1250,5,182),
	(1288,22,1),
	(1262,30,57),
	(1263,30,58),
	(1264,30,59),
	(1282,32,61),
	(1251,45,2),
	(1252,45,85),
	(1253,45,86),
	(1281,68,126),
	(1203,70,129),
	(1204,70,130),
	(1205,70,131),
	(1267,71,132),
	(1268,71,133),
	(1269,73,136),
	(1140,77,143),
	(1141,77,144),
	(1142,77,145),
	(1283,84,156),
	(1284,84,157),
	(1257,93,2),
	(1258,93,169),
	(1259,93,170),
	(1260,93,171),
	(1261,93,172),
	(1285,97,2),
	(1286,97,159),
	(1287,97,175),
	(1254,102,2),
	(1255,102,180),
	(1256,102,181),
	(1275,103,2),
	(1276,103,183),
	(1073,134,201),
	(1116,135,202),
	(1214,140,211),
	(1215,140,212),
	(1211,141,213),
	(1093,146,219),
	(1094,146,220),
	(1221,156,248),
	(1222,156,249),
	(1074,158,251),
	(1246,168,261),
	(1247,168,262),
	(1206,176,273),
	(1207,176,274),
	(1208,176,275),
	(1209,176,276),
	(1212,177,277),
	(1213,177,278),
	(1216,198,288),
	(1217,198,289),
	(1218,198,290),
	(1219,199,291),
	(1220,199,292),
	(1248,200,293),
	(963,201,294),
	(964,202,295),
	(968,203,296),
	(969,203,297),
	(1006,204,298),
	(1007,204,299),
	(1008,204,300),
	(992,205,305),
	(991,206,304),
	(988,207,301),
	(989,207,302),
	(990,207,303);

/*!40000 ALTER TABLE `m_unit_abilities` ENABLE KEYS */;
UNLOCK TABLES;


# テーブルのダンプ m_unit_keywords
# ------------------------------------------------------------

DROP TABLE IF EXISTS `m_unit_keywords`;

CREATE TABLE `m_unit_keywords` (
  `unit_id` int NOT NULL,
  `keyword_id` int NOT NULL,
  `param_value` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`unit_id`,`keyword_id`),
  UNIQUE KEY `uq_m_unit_keywords` (`unit_id`,`keyword_id`),
  KEY `idx_m_unit_keywords_keyword_id` (`keyword_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

LOCK TABLES `m_unit_keywords` WRITE;
/*!40000 ALTER TABLE `m_unit_keywords` DISABLE KEYS */;

INSERT INTO `m_unit_keywords` (`unit_id`, `keyword_id`, `param_value`)
VALUES
	(5,11,NULL),
	(5,13,NULL),
	(5,17,NULL),
	(22,2,NULL),
	(22,10,NULL),
	(22,18,NULL),
	(30,10,NULL),
	(30,13,NULL),
	(30,18,NULL),
	(32,10,NULL),
	(32,13,NULL),
	(32,18,NULL),
	(45,9,'1'),
	(45,10,NULL),
	(45,13,NULL),
	(45,17,NULL),
	(68,8,'1'),
	(68,10,NULL),
	(68,13,NULL),
	(68,18,NULL),
	(70,1,NULL),
	(70,5,'5'),
	(70,6,NULL),
	(70,7,NULL),
	(70,10,NULL),
	(70,13,NULL),
	(71,2,NULL),
	(71,10,NULL),
	(71,18,NULL),
	(73,2,NULL),
	(73,10,NULL),
	(73,18,NULL),
	(77,1,NULL),
	(77,6,NULL),
	(77,7,NULL),
	(77,8,'2'),
	(77,12,NULL),
	(77,13,NULL),
	(78,1,NULL),
	(78,6,NULL),
	(78,7,NULL),
	(78,8,'2'),
	(78,12,NULL),
	(78,13,NULL),
	(84,14,NULL),
	(84,18,NULL),
	(93,1,NULL),
	(93,6,NULL),
	(93,7,NULL),
	(93,9,'2'),
	(93,12,NULL),
	(93,13,NULL),
	(93,17,NULL),
	(97,1,NULL),
	(97,2,NULL),
	(97,10,NULL),
	(97,17,NULL),
	(102,10,NULL),
	(102,13,NULL),
	(102,17,NULL),
	(103,2,NULL),
	(103,10,NULL),
	(103,17,NULL),
	(134,2,NULL),
	(134,10,NULL),
	(135,2,NULL),
	(135,10,NULL),
	(140,5,'6'),
	(140,10,NULL),
	(140,13,NULL),
	(141,8,'1'),
	(141,10,NULL),
	(141,13,NULL),
	(146,10,NULL),
	(146,13,NULL),
	(156,2,NULL),
	(156,5,'6'),
	(156,10,NULL),
	(158,10,NULL),
	(168,14,NULL),
	(176,5,'5'),
	(176,6,NULL),
	(176,7,NULL),
	(176,9,'2'),
	(176,12,NULL),
	(176,13,NULL),
	(177,11,NULL),
	(177,13,NULL),
	(198,5,'5'),
	(198,6,NULL),
	(198,10,NULL),
	(198,13,NULL),
	(199,2,NULL),
	(199,5,'6'),
	(199,10,NULL),
	(200,15,NULL),
	(200,16,NULL),
	(201,1,NULL),
	(202,1,NULL),
	(204,15,NULL),
	(205,1,NULL);

/*!40000 ALTER TABLE `m_unit_keywords` ENABLE KEYS */;
UNLOCK TABLES;


# テーブルのダンプ m_unit_weapons
# ------------------------------------------------------------

DROP TABLE IF EXISTS `m_unit_weapons`;

CREATE TABLE `m_unit_weapons` (
  `id` int NOT NULL AUTO_INCREMENT,
  `unit_id` int NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `name_en` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `rng` int DEFAULT NULL,
  `atk` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `hit` int DEFAULT NULL,
  `wnd` int DEFAULT NULL,
  `rnd` int DEFAULT NULL,
  `dmg` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `abilities` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `keywords` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `memo` varchar(555) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_weapons_unit` (`unit_id`),
  CONSTRAINT `fk_weapons_unit` FOREIGN KEY (`unit_id`) REFERENCES `m_units` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

LOCK TABLES `m_unit_weapons` WRITE;
/*!40000 ALTER TABLE `m_unit_weapons` DISABLE KEYS */;

INSERT INTO `m_unit_weapons` (`id`, `unit_id`, `name`, `name_en`, `type`, `rng`, `atk`, `hit`, `wnd`, `rnd`, `dmg`, `abilities`, `keywords`, `memo`)
VALUES
	(926,202,'Storm of Vengeance',NULL,'melee',NULL,'12',4,4,1,'1','クリティカル(オートウーンズ)',NULL,NULL),
	(928,206,'ガチガチ鳴る噛みつき',NULL,'melee',NULL,'13',5,5,NULL,'1','クリティカル(自動ウーンズ)',NULL,NULL),
	(929,205,'不安定な歪みのエネルギー',NULL,'melee',NULL,'2D6',4,4,1,'1',NULL,NULL,NULL),
	(995,134,'ワープロック・ジェザイル (Warplock Jezzail)',NULL,'ranged',18,'2',4,3,2,'2','クリティカル(自動ウーンズ)',NULL,NULL),
	(996,134,'錆びたナイフ (Rusty Knives)',NULL,'melee',NULL,'2',4,5,NULL,'1',NULL,NULL,NULL),
	(997,158,'ワープファイア・ガン (Warpfire Gun)',NULL,'ranged',10,'2D6',2,4,2,'1','近接射撃',NULL,NULL),
	(998,158,'爪・刃・牙 (Claws, Blades and Fangs)',NULL,'melee',NULL,'5',4,3,1,'2',NULL,NULL,NULL),
	(1009,146,'ワープロック・マスケット (Warplock Musket)',NULL,'ranged',24,'2',3,3,2,'D3','クリティカル(自動ウーンズ)',NULL,NULL),
	(1010,146,'ワープフォージド・ダガー (Warpforged Dagger)',NULL,'melee',NULL,'3',4,4,NULL,'2',NULL,NULL,NULL),
	(1024,208,'Soulreach Grasp',NULL,'ranged',10,'2',4,3,2,'2','クリティカル(自動ウーンズ),近接射撃',NULL,NULL),
	(1025,135,'錆びた武器 (Rusty Weapon)',NULL,'melee',NULL,'2',4,5,NULL,'1','クリティカル(自動ウーンズ)',NULL,NULL),
	(1039,77,'炎の嵐 (Blazing Tempest)',NULL,'ranged',12,'1',2,3,2,'D3+3','近接射撃',NULL,NULL),
	(1040,77,'竜王の鉤爪 (Drake-lord’s Talons)',NULL,'melee',NULL,'6',3,2,2,'2',NULL,NULL,NULL),
	(1041,77,'殲滅の顎 (Annihilating Jaws)',NULL,'melee',NULL,'4',3,2,2,'4','対歩兵(+1貫通値)',NULL,NULL),
	(1084,70,'ゼンガヴァール (Thengavar)',NULL,'ranged',12,'1',3,2,2,'4','対大型獣(+1貫通値)',NULL,NULL),
	(1085,70,'ゼンガヴァール (Thengavar)',NULL,'melee',NULL,'2',2,2,2,'3','対大型獣(+1貫通値)',NULL,NULL),
	(1086,70,'高き天界の刃 (Blade of the High Heavens)',NULL,'melee',NULL,'5',3,3,2,'3',NULL,NULL,NULL),
	(1087,176,'不浄の齧り杖 (Unholy Gnawstaff)',NULL,'melee',NULL,'6',4,2,NULL,'D6','Crit (Mortal)',NULL,NULL),
	(1088,176,'害獣の群れ (Host of Vermin)',NULL,'melee',NULL,'10',5,5,NULL,'1','Crit (Auto-wound), Companion',NULL,NULL),
	(1090,141,'歪み石の杖 (Warpstone Staff)',NULL,'melee',NULL,'3',4,4,1,'D3',NULL,NULL,NULL),
	(1091,177,'ラットリング・ピストル (Ratling Pistol)',NULL,'ranged',10,'6',3,3,1,'1','近接射撃、クリティカル(自動ウーンズ)',NULL,NULL),
	(1092,177,'ワープフォージド・ハルバード (Warpforged Halberd)',NULL,'melee',NULL,'5',3,4,1,'2',NULL,NULL,NULL),
	(1093,177,'齧り獣の刻み牙 (Gnaw-beast’s Chisel Fangs)',NULL,'melee',NULL,'4',4,3,1,'D3','随行者',NULL,NULL),
	(1094,140,'エシンの手裏剣 (Eshin Throwing Stars)',NULL,'ranged',10,'5',3,4,NULL,'D3','クリティカル(自動ウーンズ),近接射撃',NULL,NULL),
	(1095,140,'悲嘆の刃 (Weeping Blade)',NULL,'melee',NULL,'5',3,4,1,'D3','対英雄(+1貫通値),クリティカル(致命的)',NULL,NULL),
	(1096,198,'十三の切り傷の刃 (The Blades of Thirteen Cuts)',NULL,'melee',NULL,'13',2,4,1,'1','対英雄(+1貫通値),クリティカル(致命的)',NULL,NULL),
	(1097,199,'サボター・ボム (Saboteur Bombs)',NULL,'ranged',10,'2',4,2,NULL,'D3','対歩兵(+1貫通値),近接射撃',NULL,NULL),
	(1098,199,'パンチダガーとブレード (Punch Dagger and Blade)',NULL,'melee',NULL,'3',3,4,1,'1','クリティカル(致命的)',NULL,NULL),
	(1099,156,'投石具と毒の手裏剣 (Slings and Poisoned Stars)',NULL,'ranged',10,'2',4,4,NULL,'1','クリティカル(自動ウーンズ),近接射撃',NULL,NULL),
	(1100,156,'ポイズンブレイド (Poisoned Blades)',NULL,'melee',NULL,'2',4,5,NULL,'1','クリティカル(致命的)',NULL,NULL),
	(1121,168,'ワープストーン弾の嵐 (Hail of Warpstone Bullets)',NULL,'ranged',20,'3D6+3',4,3,1,'1','クリティカル(自動ウーンズ)',NULL,NULL),
	(1122,168,'奴隷鼠の鉤爪 (Thrall-rats’ Claws)',NULL,'melee',NULL,'4',4,5,NULL,'1',NULL,NULL,NULL),
	(1123,5,'神聖なる大斧 (Hallowed Greataxe)',NULL,'melee',NULL,'5',3,3,2,'2',NULL,NULL,NULL),
	(1124,5,'グリフストーカーのくちばしと爪 (Gryph-stalker’s Beak and Talons)',NULL,'melee',NULL,'3',4,3,1,'2','随行者',NULL,NULL),
	(1125,45,'断絶の杖 (Staff of Abjuration)',NULL,'melee',NULL,'1',3,3,1,'3',NULL,NULL,NULL),
	(1126,45,'裁きの刃 (Judgement Blade)',NULL,'melee',NULL,'3',3,3,1,'D3','対神官(+1貫通値),対魔術師(+1貫通値)',NULL,NULL),
	(1127,102,'テルミノスの刃 (The Blade Terminos)',NULL,'melee',NULL,'4',3,2,2,'3','クリティカル(致命的)',NULL,NULL),
	(1128,93,'精魂の焔 (Spirit-scouring Flames)',NULL,'ranged',10,'9',2,4,2,'1','近接射撃、随行者',NULL,NULL),
	(1129,93,'魂砕き (Soulbreaker)',NULL,'melee',NULL,'4',3,3,1,'2',NULL,NULL,NULL),
	(1130,93,'クトラックのいにしえの爪 (Cthorak’s Ancient Claws)',NULL,'melee',NULL,'6',4,2,2,'3','随行者',NULL,NULL),
	(1131,30,'クエスター・ウォーブレード (Questor Warblade)',NULL,'melee',NULL,'5',3,3,1,'2','対英雄(+1貫通値),クリティカル(致命的)',NULL,NULL),
	(1134,71,'メテオリック・ハンマー (Meteoric Hammer)',NULL,'melee',NULL,'3',3,3,1,'2',NULL,NULL,NULL),
	(1135,73,'セレスチアル・グレートソード (Celestial Greatsword)',NULL,'melee',NULL,'2',3,3,NULL,'1','Anti-<span class=\"kwb\">INFANTRY</span> (+1 Rend)',NULL,NULL),
	(1142,103,'ルーンに祝福されし武器 (Rune-blessed Weapons)',NULL,'melee',NULL,'3',3,3,1,'2','クリティカル(致命的)',NULL,NULL),
	(1147,68,'告別者の杖 (Valedictor’s Stave)',NULL,'melee',NULL,'3',3,3,1,'D3','対顕現(+1貫通値)',NULL,NULL),
	(1148,32,'シグマライト・ウォーブレード (Sigmarite Warblade)',NULL,'melee',NULL,'4',3,3,1,'2',NULL,NULL,NULL),
	(1149,84,'グレート・ストームボウ (Great Stormbow)',NULL,'ranged',18,'2',3,3,1,'1','クリティカル(2 Hits), 対歩兵(+1貫通値)',NULL,NULL),
	(1150,84,'ストームストライク・アックス (Stormstrike Axe)',NULL,'melee',NULL,'3',3,3,1,'1','突撃(+1ダメージ)',NULL,NULL),
	(1151,84,'グリフチャージャーの剃刀の嘴 (Gryph-chargers’ Razor Beaks)',NULL,'melee',NULL,'6',4,3,1,'1','随行者',NULL,NULL),
	(1152,97,'ストームコール・ジャベリン (Stormcall Javelin)',NULL,'ranged',10,'1',3,3,1,'D3',NULL,NULL,NULL),
	(1153,97,'ストームコール・ジャベリン (Stormcall Javelin)',NULL,'melee',NULL,'3',3,3,1,'1','突撃+1ダメージ',NULL,NULL),
	(1154,22,'ウォーハンマー (Warhammer)',NULL,'melee',NULL,'2',3,3,1,'1','クリティカル(致命的)',NULL,NULL),
	(1155,22,'グランドハンマー (Grandhammer)',NULL,'melee',NULL,'2',3,3,1,'2','クリティカル(致命的)',NULL,NULL);

/*!40000 ALTER TABLE `m_unit_weapons` ENABLE KEYS */;
UNLOCK TABLES;


# テーブルのダンプ m_units
# ------------------------------------------------------------

DROP TABLE IF EXISTS `m_units`;

CREATE TABLE `m_units` (
  `id` int NOT NULL AUTO_INCREMENT,
  `faction_id` int DEFAULT NULL,
  `wahapedia_id` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `name_en` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `movement` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `wounds` int DEFAULT NULL,
  `save` int DEFAULT NULL,
  `control` int DEFAULT NULL,
  `points` int DEFAULT NULL,
  `unit_size` int DEFAULT NULL,
  `base_size` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `unit_keywords` varchar(555) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `faction_keywords` varchar(555) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `flavor_text` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `image` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `is_hero` tinyint(1) DEFAULT '0',
  `can_reinforce` tinyint(1) NOT NULL DEFAULT '0',
  `is_general` tinyint(1) NOT NULL DEFAULT '0',
  `is_unique` tinyint(1) NOT NULL DEFAULT '0',
  `is_terrain` tinyint(1) NOT NULL DEFAULT '0',
  `is_manifestation` tinyint(1) NOT NULL DEFAULT '0',
  `is_hidden` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_m_units_wahapedia_id` (`wahapedia_id`),
  KEY `fk_units_faction` (`faction_id`),
  CONSTRAINT `fk_units_faction` FOREIGN KEY (`faction_id`) REFERENCES `m_factions` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

LOCK TABLES `m_units` WRITE;
/*!40000 ALTER TABLE `m_units` DISABLE KEYS */;

INSERT INTO `m_units` (`id`, `faction_id`, `wahapedia_id`, `name`, `name_en`, `movement`, `wounds`, `save`, `control`, `points`, `unit_size`, `base_size`, `unit_keywords`, `faction_keywords`, `flavor_text`, `image`, `is_hero`, `can_reinforce`, `is_general`, `is_unique`, `is_terrain`, `is_manifestation`, `is_hidden`)
VALUES
	(5,1,'000001833','ロード・ヴィジラント（Lord-Vigilant on Gryph-Stalker）','Lord-Vigilant on Gryph-Stalker','12',8,3,2,120,1,NULL,'英雄,騎兵',NULL,NULL,'assets/images/order/stormcast_eternals/unit_5.webp',1,0,0,0,0,0,0),
	(22,1,'000000061','リベレイター（Liberators）','Liberators','5',2,3,1,90,5,NULL,'歩兵,チャンピオン',NULL,NULL,'assets/images/order/stormcast_eternals/unit_22.jpg',0,1,0,0,0,0,0),
	(30,1,'000000069','ナイト・クエスター（Knight-Questor）','Knight-Questor','5',6,3,2,110,1,NULL,'英雄,歩兵',NULL,NULL,'assets/images/order/stormcast_eternals/unit_30.webp',1,0,0,0,0,0,0),
	(32,1,'000000071','ナイト・ヴェクシラー（Knight-Vexillor）','Knight-Vexillor','5',6,3,5,100,1,NULL,'英雄,歩兵',NULL,NULL,'assets/images/order/stormcast_eternals/unit_32.jpg',1,0,0,0,0,0,0),
	(45,1,'000000084','ロード・ヴェリタント（Lord-Veritant）','Lord-Veritant','5',6,3,2,110,1,NULL,'英雄,神官(1),歩兵',NULL,NULL,'assets/images/order/stormcast_eternals/unit_45.webp',1,0,0,0,0,0,0),
	(68,1,'000001135','ナイト・アーケイナム（Knight-Arcanum）','Knight-Arcanum','5',6,3,2,120,1,NULL,'英雄,魔術師(1),歩兵',NULL,NULL,'assets/images/order/stormcast_eternals/unit_68.jpg',1,0,0,0,0,0,0),
	(70,1,'000001138','インドラスタ \"天空の槍\"（Yndrasta, the Celestial Spear）','Yndrasta, the Celestial Spear','12',8,3,2,270,1,NULL,'総大将,固有,英雄,歩兵,飛行,加護(5+)',NULL,NULL,'assets/images/order/stormcast_eternals/unit_70.jpg',1,0,1,1,0,0,0),
	(71,1,'000001139','アナイアレイター（Annihilators）','Annihilators','4',3,2,1,130,3,NULL,'歩兵,チャンピオン',NULL,NULL,'assets/images/order/stormcast_eternals/unit_71.jpg',0,1,0,0,0,0,0),
	(73,1,'000001349','ヴァンキッシャー（Vanquishers）','Vanquishers','5',2,3,1,90,5,NULL,'歩兵,チャンピオン',NULL,NULL,'assets/images/order/stormcast_eternals/unit_73.jpg',0,1,0,0,0,0,0),
	(77,1,'000001355','カラザイ \"傷を負いし者\"（Karazai the Scarred）','Karazai the Scarred','12',20,3,5,420,1,NULL,'総大将,固有,英雄,大型獣,飛行',NULL,NULL,'assets/images/order/stormcast_eternals/unit_77.jpg',1,0,1,1,0,0,0),
	(78,1,'000001356','クロンディス \"ドラコシオンの子\"（Krondys, Son of Dracothion）','Krondys, Son of Dracothion','12',20,3,5,480,1,NULL,'総大将,固有,英雄,大型獣,魔術師(2),飛行',NULL,NULL,'assets/images/order/stormcast_eternals/unit_78.jpg',1,0,1,1,0,0,0),
	(84,1,'000001365','ストームストライク・チャリオット（Stormstrike Chariot）','Stormstrike Chariot','10',10,3,2,110,1,NULL,'戦闘兵器',NULL,NULL,'assets/images/order/stormcast_eternals/unit_84.jpg',0,0,0,0,0,0,0),
	(93,1,'000001718','イオヌス・クリプトボーン \"喪われし魂の番人\"（Ionus Cryptborn, Warden of Lost Souls）','Ionus Cryptborn, Warden of Lost Souls','12',16,3,5,340,1,NULL,'総大将,固有,英雄,大型獣,神官(2),飛行',NULL,NULL,'assets/images/order/stormcast_eternals/unit_93.jpg',1,0,1,1,0,0,0),
	(97,1,'000001827','プロセキューター（Prosecutors）','Prosecutors','12',2,3,1,150,3,NULL,'歩兵,チャンピオン,飛行',NULL,NULL,'assets/images/order/stormcast_eternals/unit_97.jpg',0,1,0,0,0,0,0),
	(102,1,'000001832','ロード・テルミノス（Lord-Terminos）','Lord-Terminos','5',6,3,2,140,1,NULL,'英雄,歩兵',NULL,NULL,'assets/images/order/stormcast_eternals/unit_102.webp',1,0,0,0,0,0,0),
	(103,1,'000001834','リクルシアン（Reclusians）','Reclusians','5',3,3,1,140,3,NULL,'歩兵,チャンピオン',NULL,NULL,'assets/images/order/stormcast_eternals/unit_103.webp',0,1,0,0,0,0,0),
	(134,10,'000000250','ワープロック・ジェザイル（Warplock Jezzails）','Warplock Jezzails','6',2,4,1,120,3,NULL,'歩兵,チャンピオン',NULL,NULL,'assets/images/chaos/skaven/unit_134.jpg',0,1,0,0,0,0,0),
	(135,10,'000000251','クランラット（Clanrats）','Clanrats','6',1,5,1,150,20,NULL,'歩兵,チャンピオン,ミュージシャン(1/20),旗手(1/20)',NULL,NULL,'assets/images/chaos/skaven/unit_135.jpg',0,1,0,0,0,0,0),
	(140,10,'000000256','デスマスター（Deathmaster）','Deathmaster','7',5,5,2,120,1,NULL,'英雄,歩兵',NULL,NULL,'assets/images/chaos/skaven/unit_140.jpg',1,0,0,0,0,0,0),
	(141,10,'000000257','グレイ・シーア（Grey Seer）','Grey Seer','6',5,6,2,110,1,NULL,'英雄,魔術師(1),歩兵',NULL,NULL,'assets/images/chaos/skaven/unit_141.jpg',1,0,0,0,0,0,0),
	(146,10,'000000263','ウォーロック・エンジニア（Warlock Engineer）','Warlock Engineer','6',5,5,2,100,1,NULL,'英雄,歩兵',NULL,NULL,'assets/images/chaos/skaven/unit_146.jpg',1,0,0,0,0,0,0),
	(156,10,'000000275','ナイト・ランナー（Night Runners）','Night Runners','7',1,6,1,130,10,NULL,'歩兵,チャンピオン,加護(6+)',NULL,NULL,'assets/images/chaos/skaven/unit_156.jpg',0,0,0,0,0,0,0),
	(158,10,'000000279','ラット・オゴウル（Rat Ogors）','Rat Ogors','6',4,5,1,140,3,NULL,'歩兵',NULL,NULL,'assets/images/chaos/skaven/unit_158.webp',0,1,0,0,0,0,0),
	(168,10,'000001811','ラットリング・ワープブラスター（Ratling Warpblaster）','Ratling Warpblaster','6',7,4,2,110,1,NULL,'戦闘兵器',NULL,NULL,'assets/images/chaos/skaven/unit_168.jpg',0,0,0,0,0,0,0),
	(176,10,'000001819','”角戴きし鼠の預言者”ヴィジック・スカウア（Vizzik Skour, Prophet of the Horned Rat）','Vizzik Skour, Prophet of the Horned Rat','10',15,5,5,340,1,NULL,'総大将,固有,英雄,大型獣,神官(2),加護(5+)',NULL,NULL,'assets/images/chaos/skaven/unit_176.webp',1,0,1,1,0,0,0),
	(177,10,'000001820','クロウロード（Clawlord on Gnaw-beast）','Clawlord on Gnaw-beast','9',7,4,2,110,1,NULL,'英雄,騎兵',NULL,NULL,'assets/images/chaos/skaven/unit_177.jpg',1,0,0,0,0,0,0),
	(195,10,'000002998','マスク・オブ・ザ・ディシーヴァー（Mask of the Deceiver）','Mask of the Deceiver',NULL,NULL,NULL,NULL,0,NULL,NULL,'REGIMENT,OF,RENOWN,CHAOS,SKAVEN',NULL,NULL,NULL,0,0,0,0,0,0,0),
	(198,10,'000003177','デスマスター・クリックスィット（Deathmaster Crixxit）','Deathmaster Crixxit','7',6,5,2,150,1,NULL,'固有,歩兵,英雄,加護(5+)',NULL,NULL,'assets/images/chaos/skaven/unit_198.jpg',1,0,0,1,0,0,0),
	(199,10,'000003178','ガッター・ランナー（Gutter Runners）','Gutter Runners','7',1,6,1,150,10,NULL,'歩兵,チャンピオン,加護(6+)',NULL,NULL,'assets/images/chaos/skaven/unit_199.jpg',0,0,0,0,0,0,0),
	(200,1,NULL,'到嵐の転移門',NULL,NULL,12,4,0,20,1,NULL,'遮蔽,通行不可',NULL,NULL,'assets/images/order/stormcast_eternals/unit_200.jpg',0,0,0,0,1,0,0),
	(201,1,NULL,'秘術の台座',NULL,'8',NULL,NULL,7,NULL,NULL,NULL,'飛行',NULL,NULL,'assets/images/order/stormcast_eternals/unit_201.png',0,0,0,0,0,1,0),
	(202,1,NULL,'天空の渦',NULL,'8',7,6,7,NULL,NULL,NULL,'飛行,加護(6+)',NULL,NULL,'assets/images/order/stormcast_eternals/unit_202.jpg',0,0,0,0,0,1,0),
	(203,1,NULL,'恒久に燃ゆる彗星',NULL,NULL,5,4,6,NULL,NULL,NULL,'加護(6+)',NULL,NULL,'assets/images/order/stormcast_eternals/unit_203.jpg',0,0,0,0,0,1,0),
	(204,10,NULL,'齧り穴',NULL,NULL,6,4,NULL,0,NULL,NULL,'遮蔽物',NULL,NULL,'assets/images/chaos/skaven/unit_204.jpg',0,0,0,0,1,0,0),
	(205,10,NULL,'破滅の鐘',NULL,'3D6',7,6,7,NULL,NULL,NULL,'飛行,加護(6+)',NULL,NULL,NULL,0,0,0,0,0,1,0),
	(206,10,NULL,'大鼠波',NULL,'7',13,6,7,NULL,NULL,NULL,'加護(6+)',NULL,NULL,NULL,0,0,0,0,0,1,0),
	(207,10,NULL,'歪みの雷渦',NULL,NULL,7,6,7,NULL,NULL,NULL,'加護(6+)',NULL,NULL,NULL,0,0,0,0,0,1,0),
	(208,20,NULL,'ブラックコーチ',NULL,'10',12,5,5,200,NULL,NULL,'戦闘兵器、飛行、加護(5+)',NULL,NULL,NULL,0,0,0,0,0,0,0);

/*!40000 ALTER TABLE `m_units` ENABLE KEYS */;
UNLOCK TABLES;


# テーブルのダンプ m_users
# ------------------------------------------------------------

DROP TABLE IF EXISTS `m_users`;

CREATE TABLE `m_users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `account` varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `name` varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `password` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `auth` int DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `is_deleted` tinyint(1) DEFAULT '0' COMMENT '0:有効, 1:削除済み',
  `last_login_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

LOCK TABLES `m_users` WRITE;
/*!40000 ALTER TABLE `m_users` DISABLE KEYS */;

INSERT INTO `m_users` (`id`, `account`, `name`, `password`, `auth`, `created_at`, `is_deleted`, `last_login_at`)
VALUES
	(1,'admin','管理者','$2y$10$oo/hXOu8cu0cr6uVsRYvouGwR2TByAguqLPbKEGfOcsBMUG6FPTP6',NULL,'2026-03-10 15:17:14',0,'2026-05-28 09:18:03');

/*!40000 ALTER TABLE `m_users` ENABLE KEYS */;
UNLOCK TABLES;


# テーブルのダンプ t_hero_regiment_options
# ------------------------------------------------------------

DROP TABLE IF EXISTS `t_hero_regiment_options`;

CREATE TABLE `t_hero_regiment_options` (
  `hero_unit_id` int NOT NULL,
  `option_id` int NOT NULL,
  `max_limit` int DEFAULT '1',
  PRIMARY KEY (`hero_unit_id`,`option_id`),
  KEY `fk_hero_opts_option` (`option_id`),
  CONSTRAINT `fk_hero_opts_option` FOREIGN KEY (`option_id`) REFERENCES `m_regiment_options` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_hero_opts_unit` FOREIGN KEY (`hero_unit_id`) REFERENCES `m_units` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

LOCK TABLES `t_hero_regiment_options` WRITE;
/*!40000 ALTER TABLE `t_hero_regiment_options` DISABLE KEYS */;

INSERT INTO `t_hero_regiment_options` (`hero_unit_id`, `option_id`, `max_limit`)
VALUES
	(5,1,1),
	(5,2,1),
	(5,3,0),
	(5,4,0),
	(30,2,1),
	(30,3,0),
	(30,8,1),
	(32,2,1),
	(32,4,0),
	(45,1,1),
	(45,2,1),
	(45,3,0),
	(45,4,0),
	(68,2,1),
	(68,4,0),
	(70,1,1),
	(70,7,0),
	(77,1,1),
	(77,7,0),
	(93,1,1),
	(93,7,0),
	(102,1,1),
	(102,2,1),
	(102,3,0),
	(102,4,0),
	(140,12,0),
	(140,15,1),
	(141,9,0),
	(141,14,1),
	(146,11,0),
	(146,15,1),
	(176,9,0),
	(176,14,1),
	(177,10,0),
	(198,12,0);

/*!40000 ALTER TABLE `t_hero_regiment_options` ENABLE KEYS */;
UNLOCK TABLES;


# テーブルのダンプ t_match_ability_target_units
# ------------------------------------------------------------

DROP TABLE IF EXISTS `t_match_ability_target_units`;

CREATE TABLE `t_match_ability_target_units` (
  `id` int NOT NULL AUTO_INCREMENT,
  `match_id` int NOT NULL,
  `player_slot` tinyint NOT NULL COMMENT '1=player_a, 2=player_b',
  `ability_key` varchar(128) NOT NULL,
  `unit_key` varchar(64) NOT NULL COMMENT 'instanceKey (hero:/unit:)',
  `used_in_turn` int NOT NULL DEFAULT '1',
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_match_player_ability_unit` (`match_id`,`player_slot`,`ability_key`,`unit_key`),
  KEY `idx_match_id` (`match_id`),
  CONSTRAINT `fk_ability_target_units_match` FOREIGN KEY (`match_id`) REFERENCES `t_matches` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

LOCK TABLES `t_match_ability_target_units` WRITE;
/*!40000 ALTER TABLE `t_match_ability_target_units` DISABLE KEYS */;

INSERT INTO `t_match_ability_target_units` (`id`, `match_id`, `player_slot`, `ability_key`, `unit_key`, `used_in_turn`, `updated_at`)
VALUES
	(1,9,1,'army:battletrait:4','unit:238',1,'2026-07-30 23:01:17'),
	(2,9,1,'army:battletrait:4','unit:239',2,'2026-07-30 23:01:40'),
	(3,9,1,'army:battletrait:4','hero:115',3,'2026-07-30 23:01:54');

/*!40000 ALTER TABLE `t_match_ability_target_units` ENABLE KEYS */;
UNLOCK TABLES;


# テーブルのダンプ t_match_ability_usage
# ------------------------------------------------------------

DROP TABLE IF EXISTS `t_match_ability_usage`;

CREATE TABLE `t_match_ability_usage` (
  `id` int NOT NULL AUTO_INCREMENT,
  `match_id` int NOT NULL,
  `player_slot` tinyint NOT NULL COMMENT '1=player_a, 2=player_b',
  `ability_key` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `used_in_game_round` tinyint NOT NULL DEFAULT '1',
  `used_in_turn` int NOT NULL DEFAULT '1',
  `used_at_phase` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `target_unit_key` varchar(64) DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_match_player_ability` (`match_id`,`player_slot`,`ability_key`),
  KEY `idx_match_id` (`match_id`),
  CONSTRAINT `fk_ability_usage_match` FOREIGN KEY (`match_id`) REFERENCES `t_matches` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

LOCK TABLES `t_match_ability_usage` WRITE;
/*!40000 ALTER TABLE `t_match_ability_usage` DISABLE KEYS */;

INSERT INTO `t_match_ability_usage` (`id`, `match_id`, `player_slot`, `ability_key`, `used_in_game_round`, `used_in_turn`, `used_at_phase`, `target_unit_key`, `updated_at`)
VALUES
	(1,8,2,'army:formation:6',2,2,'shooting',NULL,'2026-07-30 12:34:56'),
	(4,9,1,'army:battletrait:4',3,3,'hero','hero:115','2026-07-30 23:01:54');

/*!40000 ALTER TABLE `t_match_ability_usage` ENABLE KEYS */;
UNLOCK TABLES;


# テーブルのダンプ t_match_battle_tactic_progress
# ------------------------------------------------------------

DROP TABLE IF EXISTS `t_match_battle_tactic_progress`;

CREATE TABLE `t_match_battle_tactic_progress` (
  `id` int NOT NULL AUTO_INCREMENT,
  `match_id` int NOT NULL,
  `player_slot` tinyint NOT NULL COMMENT '1 or 2',
  `battle_tactic_id` int NOT NULL,
  `highest_completed_order` tinyint NOT NULL DEFAULT '0' COMMENT '0=none, 1=Affray, 2=Strike, 3=Domination',
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_match_slot_tactic` (`match_id`,`player_slot`,`battle_tactic_id`),
  KEY `idx_match_id` (`match_id`),
  KEY `idx_battle_tactic_id` (`battle_tactic_id`),
  CONSTRAINT `fk_match_bt_progress_match` FOREIGN KEY (`match_id`) REFERENCES `t_matches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_match_bt_progress_tactic` FOREIGN KEY (`battle_tactic_id`) REFERENCES `m_battle_tactics` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

LOCK TABLES `t_match_battle_tactic_progress` WRITE;
/*!40000 ALTER TABLE `t_match_battle_tactic_progress` DISABLE KEYS */;

INSERT INTO `t_match_battle_tactic_progress` (`id`, `match_id`, `player_slot`, `battle_tactic_id`, `highest_completed_order`, `updated_at`)
VALUES
	(3,8,1,1,1,'2026-07-29 16:41:25'),
	(4,8,1,2,1,'2026-07-29 16:41:25');

/*!40000 ALTER TABLE `t_match_battle_tactic_progress` ENABLE KEYS */;
UNLOCK TABLES;


# テーブルのダンプ t_match_round_scores
# ------------------------------------------------------------

DROP TABLE IF EXISTS `t_match_round_scores`;

CREATE TABLE `t_match_round_scores` (
  `id` int NOT NULL AUTO_INCREMENT,
  `match_id` int NOT NULL,
  `player_slot` tinyint NOT NULL COMMENT '1=player_a, 2=player_b',
  `round_number` tinyint NOT NULL,
  `obj_hold_one` tinyint(1) NOT NULL DEFAULT '0',
  `obj_hold_two_plus` tinyint(1) NOT NULL DEFAULT '0',
  `obj_hold_more` tinyint(1) NOT NULL DEFAULT '0',
  `battle_tactic_id` int DEFAULT NULL,
  `battle_tactic_completed` tinyint(1) NOT NULL DEFAULT '0',
  `first_player_slot` tinyint DEFAULT NULL COMMENT 'そのラウンドの先攻 slot (1 or 2)',
  `is_double_turn` tinyint(1) NOT NULL DEFAULT '0',
  `round_vp` tinyint NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_match_player_round` (`match_id`,`player_slot`,`round_number`),
  KEY `idx_match_id` (`match_id`),
  CONSTRAINT `fk_round_scores_match` FOREIGN KEY (`match_id`) REFERENCES `t_matches` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

LOCK TABLES `t_match_round_scores` WRITE;
/*!40000 ALTER TABLE `t_match_round_scores` DISABLE KEYS */;

INSERT INTO `t_match_round_scores` (`id`, `match_id`, `player_slot`, `round_number`, `obj_hold_one`, `obj_hold_two_plus`, `obj_hold_more`, `battle_tactic_id`, `battle_tactic_completed`, `first_player_slot`, `is_double_turn`, `round_vp`)
VALUES
	(71,8,1,1,0,0,0,NULL,0,2,0,16),
	(72,8,2,1,0,0,0,NULL,0,2,0,10),
	(73,8,1,2,0,0,0,NULL,0,2,0,0),
	(74,8,2,2,0,0,0,NULL,0,2,0,0),
	(75,8,1,3,0,0,0,NULL,0,NULL,0,0),
	(76,8,2,3,0,0,0,NULL,0,NULL,0,0),
	(77,8,1,4,0,0,0,NULL,0,NULL,0,0),
	(78,8,2,4,0,0,0,NULL,0,NULL,0,0),
	(79,8,1,5,0,0,0,NULL,0,NULL,0,0),
	(80,8,2,5,0,0,0,NULL,0,NULL,0,0),
	(81,9,1,1,0,0,0,NULL,0,1,0,0),
	(82,9,2,1,0,0,0,NULL,0,1,0,0),
	(83,9,1,2,0,0,0,NULL,0,1,0,0),
	(84,9,2,2,0,0,0,NULL,0,1,0,0),
	(85,9,1,3,0,0,0,NULL,0,1,0,0),
	(86,9,2,3,0,0,0,NULL,0,1,0,0),
	(87,9,1,4,0,0,0,NULL,0,NULL,0,0),
	(88,9,2,4,0,0,0,NULL,0,NULL,0,0),
	(89,9,1,5,0,0,0,NULL,0,NULL,0,0),
	(90,9,2,5,0,0,0,NULL,0,NULL,0,0);

/*!40000 ALTER TABLE `t_match_round_scores` ENABLE KEYS */;
UNLOCK TABLES;


# テーブルのダンプ t_match_unit_status
# ------------------------------------------------------------

DROP TABLE IF EXISTS `t_match_unit_status`;

CREATE TABLE `t_match_unit_status` (
  `id` int NOT NULL AUTO_INCREMENT,
  `match_id` int NOT NULL,
  `player_slot` tinyint NOT NULL COMMENT '1=player_a, 2=player_b',
  `unit_key` varchar(64) NOT NULL COMMENT 'instanceKey (hero:/unit:/manifest:/terrain:)',
  `is_destroyed` tinyint(1) NOT NULL DEFAULT '1',
  `is_summoned` tinyint(1) NOT NULL DEFAULT '0',
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_match_player_unit` (`match_id`,`player_slot`,`unit_key`),
  KEY `idx_match_id` (`match_id`),
  CONSTRAINT `fk_match_unit_status_match` FOREIGN KEY (`match_id`) REFERENCES `t_matches` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

LOCK TABLES `t_match_unit_status` WRITE;
/*!40000 ALTER TABLE `t_match_unit_status` DISABLE KEYS */;

INSERT INTO `t_match_unit_status` (`id`, `match_id`, `player_slot`, `unit_key`, `is_destroyed`, `is_summoned`, `updated_at`)
VALUES
	(1,8,2,'manifest:205',0,1,'2026-07-30 12:23:09'),
	(2,9,1,'manifest:202',0,1,'2026-07-30 23:02:10'),
	(3,9,1,'manifest:201',0,1,'2026-07-30 23:02:19');

/*!40000 ALTER TABLE `t_match_unit_status` ENABLE KEYS */;
UNLOCK TABLES;


# テーブルのダンプ t_matches
# ------------------------------------------------------------

DROP TABLE IF EXISTS `t_matches`;

CREATE TABLE `t_matches` (
  `id` int NOT NULL AUTO_INCREMENT,
  `player_a_user_id` int DEFAULT NULL COMMENT '自分のユーザーID',
  `player_a_roster_id` int DEFAULT NULL,
  `player_b_user_id` int DEFAULT NULL COMMENT '対戦相手のユーザーID',
  `player_b_roster_id` int DEFAULT NULL,
  `player_a_vp` int DEFAULT '0',
  `player_b_vp` int DEFAULT '0',
  `battle_round` int DEFAULT '1',
  `winner` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `played_at` datetime DEFAULT NULL,
  `battleplan_id` int DEFAULT NULL,
  `status` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'active',
  `player_a_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `player_b_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `player_a_faction_id` int DEFAULT NULL,
  `player_b_faction_id` int DEFAULT NULL,
  `user_id` int DEFAULT NULL COMMENT '試合作成者',
  `completed_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `game_battle_round` tinyint NOT NULL DEFAULT '1',
  `active_player_slot` tinyint NOT NULL DEFAULT '1',
  `game_phase` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'hero',
  `game_turn_counter` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

LOCK TABLES `t_matches` WRITE;
/*!40000 ALTER TABLE `t_matches` DISABLE KEYS */;

INSERT INTO `t_matches` (`id`, `player_a_user_id`, `player_a_roster_id`, `player_b_user_id`, `player_b_roster_id`, `player_a_vp`, `player_b_vp`, `battle_round`, `winner`, `played_at`, `battleplan_id`, `status`, `player_a_name`, `player_b_name`, `player_a_faction_id`, `player_b_faction_id`, `user_id`, `completed_at`, `updated_at`, `game_battle_round`, `active_player_slot`, `game_phase`, `game_turn_counter`)
VALUES
	(8,1,11,NULL,12,16,13,2,NULL,'2026-07-29 16:41:03',1,'active','さお','しんや',1,10,1,NULL,'2026-07-30 12:48:54',2,1,'hero',2),
	(9,1,11,NULL,12,0,0,3,NULL,'2026-07-29 21:45:43',2,'active','さお','しんや',1,10,1,NULL,'2026-07-30 23:02:19',3,1,'hero',3);

/*!40000 ALTER TABLE `t_matches` ENABLE KEYS */;
UNLOCK TABLES;


# テーブルのダンプ t_roster_battle_tactics
# ------------------------------------------------------------

DROP TABLE IF EXISTS `t_roster_battle_tactics`;

CREATE TABLE `t_roster_battle_tactics` (
  `id` int NOT NULL AUTO_INCREMENT,
  `roster_id` int NOT NULL,
  `battle_tactic_id` int NOT NULL,
  `sort_order` tinyint NOT NULL DEFAULT '0' COMMENT '0..1 (最大2枚)',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_roster_tactic` (`roster_id`,`battle_tactic_id`),
  UNIQUE KEY `uk_roster_sort` (`roster_id`,`sort_order`),
  KEY `idx_roster_id` (`roster_id`),
  KEY `idx_battle_tactic_id` (`battle_tactic_id`),
  CONSTRAINT `fk_roster_battle_tactics_card` FOREIGN KEY (`battle_tactic_id`) REFERENCES `m_battle_tactics` (`id`),
  CONSTRAINT `fk_roster_battle_tactics_roster` FOREIGN KEY (`roster_id`) REFERENCES `t_rosters` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

LOCK TABLES `t_roster_battle_tactics` WRITE;
/*!40000 ALTER TABLE `t_roster_battle_tactics` DISABLE KEYS */;

INSERT INTO `t_roster_battle_tactics` (`id`, `roster_id`, `battle_tactic_id`, `sort_order`)
VALUES
	(31,11,1,0),
	(32,11,2,1),
	(39,12,2,0),
	(40,12,3,1);

/*!40000 ALTER TABLE `t_roster_battle_tactics` ENABLE KEYS */;
UNLOCK TABLES;


# テーブルのダンプ t_roster_regiment_units
# ------------------------------------------------------------

DROP TABLE IF EXISTS `t_roster_regiment_units`;

CREATE TABLE `t_roster_regiment_units` (
  `id` int NOT NULL AUTO_INCREMENT,
  `regiment_id` int NOT NULL,
  `unit_id` int NOT NULL,
  `assigned_option_id` int DEFAULT NULL,
  `sort_order` tinyint NOT NULL,
  `is_reinforced` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_roster_regiment_units_regiment` (`regiment_id`),
  KEY `fk_roster_regiment_units_unit` (`unit_id`),
  CONSTRAINT `fk_roster_regiment_units_regiment` FOREIGN KEY (`regiment_id`) REFERENCES `t_roster_regiments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_roster_regiment_units_unit` FOREIGN KEY (`unit_id`) REFERENCES `m_units` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

LOCK TABLES `t_roster_regiment_units` WRITE;
/*!40000 ALTER TABLE `t_roster_regiment_units` DISABLE KEYS */;

INSERT INTO `t_roster_regiment_units` (`id`, `regiment_id`, `unit_id`, `assigned_option_id`, `sort_order`, `is_reinforced`)
VALUES
	(238,115,84,7,0,0),
	(239,115,97,7,1,0),
	(240,115,22,7,2,0),
	(241,115,32,1,3,0),
	(257,127,135,9,0,1),
	(258,127,177,14,1,0),
	(259,127,158,9,2,0),
	(260,128,134,11,0,0);

/*!40000 ALTER TABLE `t_roster_regiment_units` ENABLE KEYS */;
UNLOCK TABLES;


# テーブルのダンプ t_roster_regiments
# ------------------------------------------------------------

DROP TABLE IF EXISTS `t_roster_regiments`;

CREATE TABLE `t_roster_regiments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `roster_id` int NOT NULL,
  `sort_order` tinyint NOT NULL,
  `hero_unit_id` int NOT NULL,
  `is_general` tinyint(1) NOT NULL DEFAULT '0',
  `enhancement_trait` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `enhancement_artefact` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_roster_regiments_roster` (`roster_id`),
  KEY `fk_roster_regiments_hero` (`hero_unit_id`),
  CONSTRAINT `fk_roster_regiments_hero` FOREIGN KEY (`hero_unit_id`) REFERENCES `m_units` (`id`),
  CONSTRAINT `fk_roster_regiments_roster` FOREIGN KEY (`roster_id`) REFERENCES `t_rosters` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

LOCK TABLES `t_roster_regiments` WRITE;
/*!40000 ALTER TABLE `t_roster_regiments` DISABLE KEYS */;

INSERT INTO `t_roster_regiments` (`id`, `roster_id`, `sort_order`, `hero_unit_id`, `is_general`, `enhancement_trait`, `enhancement_artefact`)
VALUES
	(115,11,0,70,1,NULL,NULL),
	(116,11,1,68,0,NULL,NULL),
	(117,11,2,5,0,NULL,NULL),
	(127,12,0,141,1,NULL,NULL),
	(128,12,1,146,0,NULL,NULL),
	(129,12,2,140,0,NULL,NULL);

/*!40000 ALTER TABLE `t_roster_regiments` ENABLE KEYS */;
UNLOCK TABLES;


# テーブルのダンプ t_roster_units
# ------------------------------------------------------------

DROP TABLE IF EXISTS `t_roster_units`;

CREATE TABLE `t_roster_units` (
  `id` int NOT NULL AUTO_INCREMENT,
  `roster_id` int NOT NULL,
  `unit_id` int NOT NULL,
  `quantity` int DEFAULT '1' COMMENT '同一ユニットの増強（Reinforced）などの管理用',
  PRIMARY KEY (`id`),
  KEY `roster_id` (`roster_id`),
  CONSTRAINT `t_roster_units_ibfk_1` FOREIGN KEY (`roster_id`) REFERENCES `t_rosters` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



# テーブルのダンプ t_rosters
# ------------------------------------------------------------

DROP TABLE IF EXISTS `t_rosters`;

CREATE TABLE `t_rosters` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `faction_id` int NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `total_points` int DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `battle_formation_id` int DEFAULT NULL,
  `spell_lore_id` int DEFAULT NULL,
  `prayer_lore_id` int DEFAULT NULL,
  `manifestation_lore_id` int DEFAULT NULL,
  `grand_alliance` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `point_limit` int DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `heroic_trait_id` int DEFAULT NULL,
  `trait_regiment_index` tinyint DEFAULT NULL,
  `artefact_id` int DEFAULT NULL,
  `artefact_regiment_index` tinyint DEFAULT NULL,
  `trait_target_unit_id` int DEFAULT NULL,
  `artefact_target_unit_id` int DEFAULT NULL,
  `trait_unit_slot` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `artefact_unit_slot` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `terrain_id` int DEFAULT NULL,
  `season_enhancement_id` int DEFAULT NULL,
  `season_enhancement_target_unit_id` int DEFAULT NULL,
  `season_enhancement_regiment_index` tinyint DEFAULT NULL,
  `season_enhancement_unit_slot` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `memo` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

LOCK TABLES `t_rosters` WRITE;
/*!40000 ALTER TABLE `t_rosters` DISABLE KEYS */;

INSERT INTO `t_rosters` (`id`, `user_id`, `faction_id`, `name`, `total_points`, `created_at`, `battle_formation_id`, `spell_lore_id`, `prayer_lore_id`, `manifestation_lore_id`, `grand_alliance`, `point_limit`, `updated_at`, `heroic_trait_id`, `trait_regiment_index`, `artefact_id`, `artefact_regiment_index`, `trait_target_unit_id`, `artefact_target_unit_id`, `trait_unit_slot`, `artefact_unit_slot`, `terrain_id`, `season_enhancement_id`, `season_enhancement_target_unit_id`, `season_enhancement_regiment_index`, `season_enhancement_unit_slot`, `memo`)
VALUES
	(11,1,1,'はじめてのろすたあ(アキュシー)',980,'2026-06-25 15:36:10',1,1,4,1,'Order',1000,'2026-07-23 14:41:12',3,1,3,2,68,5,'leader','leader',200,4,84,0,'0',NULL),
	(12,1,10,'ねずみいっぱい(アキュシー)',1000,'2026-06-25 22:49:47',6,4,1,4,'Chaos',1000,'2026-07-29 14:50:02',8,0,8,0,177,177,'1','1',204,3,158,0,'2',NULL);

/*!40000 ALTER TABLE `t_rosters` ENABLE KEYS */;
UNLOCK TABLES;


# テーブルのダンプ t_unit_regiment_eligibility
# ------------------------------------------------------------

DROP TABLE IF EXISTS `t_unit_regiment_eligibility`;

CREATE TABLE `t_unit_regiment_eligibility` (
  `unit_id` int NOT NULL,
  `option_id` int NOT NULL,
  PRIMARY KEY (`unit_id`,`option_id`),
  KEY `fk_eligibility_option` (`option_id`),
  CONSTRAINT `fk_eligibility_option` FOREIGN KEY (`option_id`) REFERENCES `m_regiment_options` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_eligibility_unit` FOREIGN KEY (`unit_id`) REFERENCES `m_units` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

LOCK TABLES `t_unit_regiment_eligibility` WRITE;
/*!40000 ALTER TABLE `t_unit_regiment_eligibility` DISABLE KEYS */;

INSERT INTO `t_unit_regiment_eligibility` (`unit_id`, `option_id`)
VALUES
	(30,1),
	(32,1),
	(102,1),
	(97,3),
	(103,3),
	(22,4),
	(71,4),
	(73,4),
	(84,4),
	(22,7),
	(71,7),
	(73,7),
	(84,7),
	(97,7),
	(103,7),
	(134,9),
	(135,9),
	(156,9),
	(158,9),
	(168,9),
	(199,9),
	(135,10),
	(134,11),
	(168,11),
	(156,12),
	(199,12),
	(140,14),
	(146,14),
	(177,14),
	(198,14),
	(135,15),
	(158,16);

/*!40000 ALTER TABLE `t_unit_regiment_eligibility` ENABLE KEYS */;
UNLOCK TABLES;



/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
