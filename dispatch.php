<?php

if(!isset($__recursion)) {
	session_start();
	define("PAGE", "dispatch");

	require "game-data.php";

	function get_content($file) {
		$content = @file_get_contents($file);
		if($content !== false) {
			return $content;
		}
		return null;
	}
}

$page = isset($_GET['p']) ? $_GET['p'] : null;

if($page === null) {
	http_response_code(500);
	echo "NO PAGE TO LOAD";
	exit;
}

$pageExt = ".html";
$pageUrl = "pages/" . $page;
$pageUrlExt = $pageUrl . $pageExt;

$isPagePublic = false;
$isUserLoggedIn = isset($_SESSION['cafeUsername']);
$isUserFullLoggedIn = $isUserLoggedIn && isset($_SESSION['cafeDrink']);

$day = date("j");
$dayChanged = isset($_SESSION['cafeDayChanged']);

$data = null;

switch($page) {
	/*** Pages accessibles à tous ***/
	case "defm":
		// NOTE : Cette page est appelée par "swf/client.swf" avec le paramètre GET "sid=[key1];m=[key2]".
		// Sert-elle à établir la connexion avec le serveur de jeu ?
		$isPagePublic = true;
		$data = "";
		break;
	case "head":
	case "_special/head":
		$isPagePublic = true;
		if(!$isUserFullLoggedIn || $dayChanged) {
			$data = get_content($pageUrl . "_guest" . $pageExt);
			if($dayChanged && $page === "head") {
				$data .= '<fill id="headBar"></fill><fill id="menu"></fill>';
			}
		} else {
			$d = intval($_SESSION['cafeDrink']);
			$coldCup = $d >= 11 ? '<div class="coldCup"></div>' : "";
			$data = get_content($pageUrl . ($page === "head" ? "_logged" : "") . $pageExt);
			$data = str_replace("{ARCHIVE_DRINK_IMG}", $globalDrinks[$d]['img'], $data);
			$data = str_replace("{ARCHIVE_DRINK_CSS}", $globalDrinks[$d]['css'], $data);
			$data = str_replace("{ARCHIVE_DRINK_DESC}", str_replace("'", "\'", $globalDrinks[$d]['desc']), $data);
			$data = str_replace("{ARCHIVE_DRINK_COLDCUP}", $coldCup, $data);
		}
		break;
	case "help":
		$isPagePublic = true;
		$section = "firstSteps";
		if(isset($_GET['section'])) {
			$s = explode(";", $_GET['section'])[0];
			switch($s) {
				case "connection": case "contact": case "faq": case "firstSteps": case "forum": case "furns": case "group": case "history": case "prizeTokens": case "ranking": case "tokens": case "xp":
				case "game/1": case "game/2": case "game/3": case "game/4": case "game/5": case "game/6": case "game/7": case "game/8": case "game/9": case "game/11": $section = $s; break;
			}
		}
		$data = get_content($pageUrlExt);
		$data = str_replace("{ARCHIVE_SECTION}", $section, $data);
		break;
	case "help/connection":
	case "help/contact":
	case "help/faq":
	case "help/firstSteps":
	case "help/forum":
	case "help/furns":
	case "help/game/1":
	case "help/game/2":
	case "help/game/3":
	case "help/game/4":
	case "help/game/5":
	case "help/game/6":
	case "help/game/7":
	case "help/game/8":
	case "help/game/9":
	case "help/game/11":
	case "help/group":
	case "help/history":
	case "help/prizeTokens":
	case "help/ranking":
	case "help/tokens":
	case "help/xp":
	case "log": // NOTE : Cette page existe... mais impossible de connaître réellement son utilité.
	case "partnerFrame":
	case "static/present":
	case "static/present2":
	case "static/require": // NOTE : Cette page s'affiche quand le site ne parvient pas à s'initialiser. Pour cette raison, elle contient tout le code HTML nécessaire.
		$isPagePublic = true;
		$data = get_content($pageUrlExt);
		break;
	case "redir":
		$isPagePublic = true;
		$data = "";
		$url = isset($_GET['url']) ? $_GET['url'] : "";
		if(strpos($url, "http://") === 0 || strpos($url, "https://") === 0) {
			// NOTE : Cette fonctionnalité servait à rediriger les utilisateurs vers une page externe à cafejeux.com. Elle était par exemple utilisée pour les liens postés sur les forums.
			// Le code HTTP 302 était bien renvoyé. Comme tous les liens postés sont désormais morts ou douteux, on n'effectue aucune redirection ici.
			http_response_code(302);
			$data = '<script type="text/javascript">window.close();</script>';
		}
		break;
	case "user/askPass":
		$isPagePublic = true;
		if(!isset($_POST['nameOrMail'])) {
			$data = get_content($pageUrlExt);
		} else {
			// NOTE : En cas de succès, cafejeux.com affichait seulement la première lettre de la partie précédant le symbole "@" dans l'email.
			$pNameOrMail = $_POST['nameOrMail'];
			$f = "_noUser";
			if(strlen($pNameOrMail) >= 6) $f = "_ok";
			elseif(strlen($pNameOrMail) >= 4) $f = (mt_rand(0, 1) === 0 ? "_noMail" : "_already"); // prétexte pour afficher l'un de ces messages
			$data = get_content($pageUrl . $f . $pageExt);
		}
		break;
	case "user/ident":
		$isPagePublic = true;
		$pIdentName = isset($_POST['ident_name']) ? $_POST['ident_name'] : "";
		$pIdentPass = isset($_POST['ident_pass']) ? $_POST['ident_pass'] : "";
		$error = "";
		if(strlen($pIdentName) < 4 || strlen($pIdentName) > 20) {
			$error = "Un pseudo doit faire entre 4 et 20 caractères.";
			$pIdentName = "";
		} elseif(preg_match('/[^A-Za-z0-9]/', $pIdentName)) {
			$error = "Un pseudo ne doit contenir que des chiffres et des lettres.";
			$pIdentName = "";
		} elseif(strlen($pIdentPass) < 6 || strlen($pIdentPass) > 32) {
			$error = "Votre code secret doit faire entre 6 et 32 caractères.";
		} elseif($pIdentName === strtoupper($pIdentName)) {
			$error = "Cet utilisateur n'existe pas.";
			$pIdentName = "";
		} elseif($pIdentName === $pIdentPass) {
			$error = "Ce code secret n'est pas valide. Attention, les différences entre minuscules et majuscules sont prises en compte.";
		}
		if(strlen($error) > 0 || $isUserLoggedIn) {
			$data = get_content($pageUrl . "_error" . $pageExt);
			$data = str_replace("{ARCHIVE_ERROR}", $error, $data);
			$data = str_replace("{ARCHIVE_USERNAME}", htmlentities($pIdentName), $data);
		} else {
			$_SESSION['cafeUsername'] = $pIdentName;
			if(isset($_SESSION['cafeDay']) && $_SESSION['cafeDay'] !== $day) $_SESSION['cafeDayChanged'] = true;
			$data = get_content($pageUrl . "_ok" . $pageExt);
		}
		break;
	case "user/register":
		$isPagePublic = true;
		$f = "_ok";
		if(!$isUserLoggedIn) {
			$f = "";
		}
		$data = get_content($pageUrl . $f . $pageExt);
		break;
	case "sponsor/embed.js":
		$isPagePublic = true;
		$gInfo = isset($_GET['sz']) ? explode(";ref=", $_GET['sz']) : "";
		$sz = explode("x", $gInfo[0]);
		$szW = intval($sz[0]);
		$szH = isset($sz[1]) ? intval($sz[1]) : 0;
		$name = isset($gInfo[1]) ? str_replace("ref=", "", htmlentities($gInfo[1])) : "";
		$data = "";
		if($szW > 0 && $szH > 0 && strlen($name) > 0) {
			header("Content-Type: text/javascript");
			$data = get_content($pageUrl);
			$data = str_replace("{ARCHIVE_SPONSOR_WIDTH}", strval($szW), $data);
			$data = str_replace("{ARCHIVE_SPONSOR_HEIGHT}", strval($szH), $data);
			$data = str_replace("{ARCHIVE_SPONSOR_NAME}", $name, $data);
		}
		break;
	/*** Pages réservées aux membres ***/
	case "bank": // TODO
	case "ctpl/chat.mtt";
	case "ctpl/global.mtt":
	case "ctpl/shop.mtt":
		$data = get_content($pageUrl);
		break;
	case "_special/1": case "_special/2": case "_special/3": case "_special/4": case "_special/5": case "_special/6": case "_special/7": case "_special/8": case "_special/9": case "_special/11":
	case "_special/game": case "_special/bar_1": case "_special/bar_11": case "_special/unlock_3": case "_special/unlock_8": case "_special/unlock_9":
	case "defy":
	case "defy/choosePlayer":
	case "forum":
	case "forum/2":
	case "forum/5":
	case "forum/bookmarks": // NOTE : Pas de sujet = `<p class="dyk">Vous n'avez aucun sujet en favoris actuellement. Pour ajouter un sujet dans vos favoris, il vous suffit de cliquer sur le lien en bas de la page du sujet.</p>`
	case "forum/thread/64083": case "forum/thread/68580": case "forum/thread/131526": case "forum/thread/156621": case "forum/thread/160307": case "forum/thread/163937": case "forum/thread/186881": case "forum/thread/186884":
	case "game":
	case "game/1":
	case "game/1/bar":
	case "game/1/ranking":
	case "game/2":
	case "game/2/bar":
	case "game/2/ranking":
	case "game/3":
	case "game/3/bar":
	case "game/3/ranking":
	case "game/4":
	case "game/4/bar":
	case "game/4/ranking":
	case "game/5":
	case "game/5/bar":
	case "game/5/ranking":
	case "game/6":
	case "game/6/bar":
	case "game/6/ranking":
	case "game/7":
	case "game/7/bar":
	case "game/7/ranking":
	case "game/8":
	case "game/8/bar":
	case "game/8/ranking":
	case "game/9":
	case "game/9/bar":
	case "game/9/ranking":
	case "game/11":
	case "game/11/bar":
	case "game/11/ranking":
	case "game/observe":
	case "group":
	case "group/420/forum":
	case "group/420/partyHistory":
	case "group/420/ranking":
	case "group/420/recruit":
	case "group/420/recruit_alt":
	case "group/6951/forum":
	case "group/6951/partyHistory":
	case "group/6951/ranking":
	case "group/6951/recruit":
	case "group/list":
	case "group/my":
	case "pvmsg":
	case "pvmsg/38365":
	case "pvmsg/42443":
	case "pvmsg/43268":
	case "pvmsg/677329":
	case "pvmsg/1591972":
	case "pvmsg/2284448":
	case "pvmsg/6381127":
	case "pvmsg/6392115":
	case "pvmsg/list": // NOTE : cafejeux.com affichait 10 messages par page au maximum.
	case "pvmsg/mDelete":
	case "pvmsg/prefs":
	case "shop/1": case "shop/3": case "shop/4": case "shop/5": case "shop/6": case "shop/7": case "shop/8": case "shop/9": case "shop/10": case "shop/11": case "shop/12": case "shop/13": case "shop/14": case "shop/15":
	case "shop/16": case "shop/17": case "shop/18": case "shop/19": case "shop/20": case "shop/21": case "shop/22": case "shop/23": case "shop/24": case "shop/25": case "shop/26": case "shop/27": case "shop/28": case "shop/29":
	case "shop/30": case "shop/31": case "shop/32": case "shop/33": case "shop/34": case "shop/35": case "shop/36": case "shop/37": case "shop/38": case "shop/39": case "shop/40": case "shop/41": case "shop/42": case "shop/43":
	case "shop/44": case "shop/45": case "shop/46": case "shop/47": case "shop/48": case "shop/49": case "shop/50": case "shop/54": case "shop/55": case "shop/65": case "shop/67": case "shop/68":
	case "shop/type/Color":
	case "shop/type/Drink":
	case "shop/type/Furniture":
	case "shop/type/GameOption":
	case "shop/type/Goodies":
	case "shop/type/Sugar":
	case "user/81": case "user/16732": case "user/33208": case "user/243719": case "user/258748": case "user/265566": case "user/275028": case "user/296239":
	case "user/302576": case "user/307984": case "user/328173": case "user/340881": case "user/353165": case "user/455222":
	case "user/33208/tip": case "user/190420/tip": case "user/243719/tip": case "user/258748/tip": case "user/265566/tip": case "user/296239/tip":
	case "user/302576/tip": case "user/307984/tip": case "user/328173/tip": case "user/340881/tip":
	case "user/18269/furnitures":
	case "user/dayChanged":
	case "user/shipment": // NOTE : Ou `<p>Vous n'avez encore commandé aucun cadeau.</p><a href="#shop?type=Goodies" class="button" onclick="js.XmlHttp.get('shop?type=Goodies',this); return false;">Les cadeaux disponibles</a>`
	case "user/sponsor": // NOTE : Quand le nombre de filleuls est 0, le texte suivant est affiché : "<p>Lorsque vous aurez des filleuls, un lien apparaîtra sur cette page afin de consulter vos statistiques de parrainage.</p>"
	case "user/sponsorList":
	case "user/sponsorWeb":
		$data = get_content($pageUrlExt);
		break;
	case "forum/999999/post":
	case "forum/thread/999999/reply":
		if(isset($__recursiondata) && is_int($__recursiondata) && $__recursiondata > 0) {
			if($page === "forum/999999/post") {
				$forumName = $__recursiondata === 2 ? "<strong>Les</strong> tables" : "<strong>Le</strong> Café Jeux";
				$topicName = "";
			} else {
				$forumName = "<strong>Les</strong> tables";
				switch($__recursiondata) { case 131526: case 156621: case 160307: case 186881: case 186884: $forumName = "<strong>Le</strong> Café Jeux"; break; }
				$topicName = null;
				switch($__recursiondata) {
					case 64083: $topicName = "Sortir de Terre"; break; case 68580: $topicName = "Présentation de table"; break;
					case 131526: $topicName = "(HELP) Questions & réponses"; break; case 186881: $topicName = "Hello la MT"; break; case 186884: $topicName = "Infos en vrac sur Café Jeux"; break;
				}
			}
			if($topicName !== null) {
				$f = "_main";
				$errorArray = array(); $fullDate = ""; $partialDate = ""; $parsedContent = "";
				if(isset($_POST['title']) || isset($_POST['content'])) {
					$pTitle = strlen($topicName) !== 0 ? null : (isset($_POST['title']) ? trim($_POST['title']) : "");
					$pContent = isset($_POST['content']) ? trim($_POST['content']) : "";
					if(isset($_POST['preview'])) {
						$f = "_preview";
						$date = cj_date_today();
						$fullDate = $date[0];
						$partialDate = $date[1];
						$parsedContent = htmlentities(parse_message($pContent));
					} else {
						if($pTitle !== null && strlen($pTitle) === 0) {
							$errorArray[0] = '<div class="nack">Le titre ne doit pas être vide.</div>';
						} elseif(strlen($pContent) < 30) {
							$errorArray[1] = '<div class="nack">Le message est trop court.</div>';
						} else {
							$errorArray[99] = "<load>forum/thread/" . (strlen($topicName) === 0 ? "999999" : strval($__recursiondata)) . "</load>";
						}
					}
				}
				if(isset($errorArray[99])) {
					$data = $errorArray[99];
				} else {
					$data = get_content($pageUrl . $f . $pageExt);
					$data = str_replace("{ARCHIVE_FORUM_POST_FORUM_NAME}", $forumName, $data);
					$data = str_replace("{ARCHIVE_FORUM_POST_TOPIC_NAME}", $topicName, $data);
					$data = str_replace("{ARCHIVE_FORUM_POST_INDEX}", strval($__recursiondata), $data);
					$data = str_replace("{ARCHIVE_FORUM_TITLE_ERROR}", (isset($errorArray[0]) ? $errorArray[0] : ""), $data);
					$data = str_replace("{ARCHIVE_FORUM_MESSAGE_ERROR}", (isset($errorArray[1]) ? $errorArray[1] : ""), $data);
					$data = str_replace("{ARCHIVE_FORUM_TITLE_POST}", (isset($pTitle) ? htmlentities($pTitle) : ""), $data);
					$data = str_replace("{ARCHIVE_FORUM_CONTENT_POST}", (isset($pContent) ? htmlentities($pContent) : ""), $data);
					$data = str_replace("{ARCHIVE_FORUM_PREVIEW_DATE_FULL}", $fullDate, $data);
					$data = str_replace("{ARCHIVE_FORUM_PREVIEW_DATE_PARTIAL}", $partialDate, $data);
					$data = str_replace("{ARCHIVE_FORUM_PREVIEW_CONTENT}", $parsedContent, $data);
				}
			}
		}
		break;
	case "forum/thread/999999":
		$data = get_content(str_replace("999999", "186884", $pageUrlExt));
		break;
	case "forum/thread/999999/bookmark":
	case "forum/thread/999999/unbookmark":
		if(isset($__recursiondata) && is_int($__recursiondata) && $__recursiondata > 0) {
			$f = "";
			if($__recursiondata === 156621 || $__recursiondata === 160307 || $__recursiondata === 163937) {
				$f = "_closed";
			}
			$data = get_content($pageUrl . $f . $pageExt);
			$data = str_replace("{ARCHIVE_FORUM_BOOKMARK_INDEX}", strval($__recursiondata), $data);
		}
		break;
	case "forum/search":
		$gpThemeId = isset($_GET['themeId']) ? intval($_GET['themeId']) : 0;
		if(isset($_POST['submit'])) {
			$pSearch = isset($_POST['search']) ? htmlentities($_POST['search']) : "";
			$gpThemeId = isset($_POST['themeId']) ? intval($_POST['themeId']) : $gpThemeId;
			$data = get_content($pageUrl . "_form" . $pageExt);
			$data = str_replace("{ARCHIVE_FORUM_SEARCH_REQUEST}", $pSearch, $data);
		} else {
			$data = get_content($pageUrlExt);
		}
		$data = str_replace("{ARCHIVE_FORUM_SEARCH_THEME_2}", ($gpThemeId === 2 ? 'selected="selected" ' : ""), $data);
		$data = str_replace("{ARCHIVE_FORUM_SEARCH_THEME_5}", ($gpThemeId === 5 ? 'selected="selected" ' : ""), $data);
		break;
	case "forum/readAll":
		// NOTE : Cette action devrait en réalité retourner "<load>forum</load>". A la place, on se contente d'en "émuler" la conséquence visuelle sur la page.
		$data = "<script>while(true) { var elem = document.querySelector('.readed_false'); if(!elem) break; elem.className = 'readed_true'; }</script>";
		break;
	case "game/rankings":
		$gGame = isset($_GET['game']) ? intval($_GET['game']) : 0;
		$gOld = isset($_GET['old']); // NOTE : Si "true", les liens des jeux sur cette page devraient être modifiés pour contenir le paramètre ";old=1".
		$load = "";
		if($gGame > 0 && $gGame < 12 && $gGame !== 10) {
			$load = "<load>game/" . $gGame . "/ranking?page=1</load>";
		}
		$recent = !$gOld ? '<h1><strong>Classements</strong></h1>' : "";
		$old = $gOld ? '<h1>Anciens <strong>classements</strong></h1>' : "";
		$data = get_content($pageUrlExt);
		$data = str_replace("{ARCHIVE_LOAD_RANKINGS}", $load, $data);
		$data = str_replace("{ARCHIVE_RANKINGS_RECENT}", $recent, $data);
		$data = str_replace("{ARCHIVE_RANKINGS_OLD}", $old, $data);
		break;
	case "group/420":
	case "group/6864":
	case "group/6951":
		$idTable = explode("/", $page)[1];
		$gGo = isset($_GET['go']) ? htmlentities(explode(";", $_GET['go'])[0]) : "";
		switch($gGo) {
			case "ad": case "changeGame": case "chat": case "description": case "forum?page=1": case "members": case "ranking": case "recruit": break;
			default: $gGo = "description"; break;
		}
		$data = get_content($pageUrlExt);
		$data = str_replace("{ARCHIVE_LOAD_TABLE}", $gGo, $data);
		if($idTable === "6864") {
			// NOTE : Prétexte pour différencier une table à laquelle on peut ou ne peut plus adhérer (limite maximum : 60 membres par table).
			$_SESSION['cafeTableDummy'] = isset($__recursion);
			$data = str_replace("{ARCHIVE_TABLE_TOTAL_MEMBERS}", (!isset($__recursion) ? "59" : "60"), $data);
		}
		break;
	case "group/420/ad":
		if(!isset($_POST['content'])) {
			$data = get_content($pageUrl . "_main" . $pageExt);
		} else {
			$pContent = $_POST['content'];
			if(strlen($pContent) < 30 || strlen($pContent) > 180) {
				$data = "<alert>Votre annonce doit faire entre 30 et 180 caractères.</alert>";
			} else {
				$data = get_content($pageUrl . "_ok" . $pageExt);
			}
		}
		break;
	case "group/420/auth":
		$id = isset($_GET['id']) ? $_GET['id'] : "";
		$idSplit = explode(";", $id);
		$otherId = intval($idSplit[0]);
		$type = isset($idSplit[1]) ? str_replace("auth=", "", htmlentities($idSplit[1])) : "";
		$enabled = isset($idSplit[2]) ? str_replace("v=", "", htmlentities($idSplit[2])) : "";
		if($otherId > 0 && strlen($type) > 0 && strlen($enabled) > 0) {
			$p = "";
			switch($type) {
				case "EditDescription": $p = "Editer la présentation"; break; case "ConfirmMember": $p = "Accepter un nouveau membre"; break; case "DeletePost": $p = "Supprimer un message du forum"; break;
				case "PostAd": $p = "Poster une annonce (avec ses propres sucres)"; break; case "EjectPublic": $p = "Bannir un visiteur du chat"; break; case "EjectMember": $p = "Ejecter un membre"; break;
			}
			if(strlen($p) > 0) {
				$enabled = $enabled === "true" || $enabled === "1" ? "": "_off";
				$data = get_content($pageUrlExt);
				$data = str_replace("{ARCHIVE_AUTH_OTHER_USER_ID}", strval($otherId), $data);
				$data = str_replace("{ARCHIVE_AUTH_TYPE}", $type, $data);
				$data = str_replace("{ARCHIVE_AUTH_STATE_IMG}", $enabled, $data);
				$data = str_replace("{ARCHIVE_AUTH_LINK}", ($enabled === "_off" ? "true" : "false"), $data);
				$data = str_replace("{ARCHIVE_AUTH_DESC}", $p, $data);
			}
		}
		break;
	case "group/420/autoAccept":
		$gV = isset($_GET['v']) ? explode(";", $_GET['v'])[0] : "true";
		if($gV === "true" || $gV === "1") {
			$data = "<load>group/420/recruit</load>";
		} else {
			$data = "<load>group/420/recruit_alt</load>";
		}
		break;
	case "group/420/changeGame":
		if(!isset($_POST['submit'])) {
			$data = get_content($pageUrl . "_main" . $pageExt);
		} elseif(!isset($_POST['game_1']) && !isset($_POST['game_2']) && !isset($_POST['game_3']) && !isset($_POST['game_4']) && !isset($_POST['game_5']) && 
				 !isset($_POST['game_6']) && !isset($_POST['game_7']) && !isset($_POST['game_8']) && !isset($_POST['game_9']) && !isset($_POST['game_11'])) {
			$data = get_content($pageUrl . "_error" . $pageExt);
		} else {
			$data = get_content($pageUrl . "_ok" . $pageExt);
		}
		break;
	case "group/420/chat":
	case "group/6951/chat":
		$date = date("Y-m-d H:i:s");
		$data = get_content($pageUrlExt);
		$data = str_replace("{ARCHIVE_TABLE_DATETIME}", $date, $data);
		break;
	case "group/420/delete":
		if(isset($_GET['sid'])) {
			// NOTE : Le sid doit servir à vérifier que l'action provient bien de l'utilisateur.
			// Impossible toutefois de savoir ce qu'il se passe précisément après avoir supprimé la table : cette fonctionnalité de cafejeux.com était hors d'usage au moment du test.
		}
		$data = get_content($pageUrlExt);
		break;
	case "group/420/deletePost":
		$gId = isset($_GET['id']) ? intval($_GET['id']) : 0;
		if($gId > 0) {
			$data = "<load>group/420/forum</load>";
		}
		break;
	case "group/420/description":
	case "group/6864/description":
	case "group/6951/description":
		$idTable = explode("/", $page)[1];
		$date = date("Y-m-d H:i:s");
		$f = "";
		if($idTable === "420") {
			$f = "_main";
			if(isset($_GET['edit'])) {
				$f = "_edit";
			}
		}
		// NOTE : Le "seed" ci-dessous ne devrait pas être généré aléatoirement. Sur cafejeux.com, sa valeur était toujours la même pour une table donnée.
		// La valeur du "seed" correspond en effet à la position et la couleur du tapis de la salle. Celui-ci ne change jamais et est sélectionné aléatoirement à la création de la table.
		// Néanmoins, le "seed" influence aussi la position des personnages et leurs interactions dans la salle, sur la vignette Flash. Pour cette raison, il est intéressant ici de le générer aléatoirement.
		$data = get_content($pageUrl . $f . $pageExt);
		$data = str_replace("{ARCHIVE_TABLE_DATETIME}", $date, $data);
		$data = str_replace("{ARCHIVE_TABLE_SEED}", strval(mt_rand(10000, 99999)), $data);
		if($idTable === "6864") {
			$isDummy = isset($_SESSION['cafeTableDummy']) ? $_SESSION['cafeTableDummy'] : true;
			$data = str_replace("{ARCHIVE_TABLE_TOTAL_MEMBERS}", (!$isDummy ? "59" : "60"), $data);
			$data = str_replace("{ARCHIVE_TABLE_60TH_MEMBER}", (!$isDummy ? "" : ":29,0,4,2,3,0,0,0,2,0,0,13,0,0,3,6,6,0"), $data);
		}
		break;
	case "group/420/editDescription":
		// NOTE : Les champs "logo" et "description" envoyés par le formulaire peuvent être vides. Si l'adresse du logo ne commence pas par http://, une valeur vide semblait enregistrée en base de données.
		$data = "<load>group/420/description</load>";
		break;
	case "group/420/editRoom":
	case "group/6951/editRoom":
		if(isset($_POST['raw'])) {
			// NOTE : Ce formulaire est envoyé par "swf/rooms.swf" lorsque les changements apportés à l'organisation des meubles sont validés.
			$data = "";
		} else {
			$data = get_content($pageUrlExt);
		}
		break;
	case "group/420/eject":
		$gId = isset($_GET['id']) ? intval($_GET['id']) : 0;
		if($gId >= 0) {
			// NOTE : Cette fonctionnalité de cafejeux.com était hors d'usage au moment du test.
			$data = "";
		}
		break;
	case "group/420/invite":
	case "group/6951/invite":
		// NOTE : Lorsque le nom saisi est correct, un message est envoyé au membre invité via la messagerie interne du site. Néanmoins, cette fonctionnalité de cafejeux.com était hors d'usage au moment du test.
		$pName = isset($_POST['name']) ? $_POST['name'] : "";
		$data = '<fill class="error" id="formError"></fill>';
		if(strlen($pName) < 4 || strlen($pName) > 20) {
			$data = get_content($pageUrl . "_error" . $pageExt);
		}
		break;
	case "group/420/members":
	case "group/6951/members":
		$date = cj_date_today();
		$fullDate = $date[0];
		$partialDate = $date[1];
		$data = get_content($pageUrlExt);
		$data = str_replace("{ARCHIVE_TABLE_USER_DATE_FULL}", $fullDate, $data);
		$data = str_replace("{ARCHIVE_TABLE_USER_DATE_PARTIAL}", $partialDate, $data);
		break;
	case "group/420/post":
	case "group/6951/post":
		$idTable = explode("/", $page)[1];
		$pMessage = isset($_POST['message']) ? $_POST['message'] : "";
		$data = "";
		if(strlen($pMessage) >= 4) {
			$data = "<load>group/" . $idTable . "/forum</load>";
		}
		break;
	case "group/420/watchChat":
	case "group/6951/watchChat":
		$idTable = explode("/", $page)[1];
		$gV = isset($_GET['v']) ? htmlentities(explode(";", $_GET['v'])[0]) : "";
		if($gV === "0") $gV = "false";
		elseif($gV === "1") $gV = "true";
		if($gV === "true" || $gV === "false") {
			$data = "<script>js.App.updateWatchChat('group_" . $idTable . "'," . $gV . ");</script>";
		}
		break;
	case "group/6864/join":
		$isDummy = isset($_SESSION['cafeTableDummy']) ? $_SESSION['cafeTableDummy'] : true;
		$gConfirm = isset($_GET['confirm']) ? intval($_GET['confirm']) : 0;
		$f = "";
		if($gConfirm === 1) {
			if(!$isDummy) {
				// NOTE : Impossible de connaître le message affiché quand l'utilisateur postule à une table qui n'est pas pleine (< 60 membres), cette fonctionnalité de cafejeux.com était hors d'usage au moment du test.
				// Si l'utilisateur faisait déjà partie de huit tables, le message suivant s'affichait sur cafejeux.com : `<p class="nack">Vous ne pouvez pas faire partie de plus de 8 tables à la fois.</p>`
			} else {
				$f = "_full";
			}
		} elseif($isDummy) {
			$f = "_full";
		}
		$data = get_content($pageUrl . $f . $pageExt);
		break;
	case "group/6864/members":
		$isDummy = isset($_SESSION['cafeTableDummy']) ? $_SESSION['cafeTableDummy'] : true;
		$data = get_content((!$isDummy ? $pageUrlExt : $pageUrl . "_dummy" . $pageExt));
		break;
	case "group/6951/part":
		$gSid = isset($_GET['sid']) ? explode(";", $_GET['sid'])[0] : "";
		if(strlen($gSid) >= 0) {
			// NOTE : Cette fonctionnalité de cafejeux.com était hors d'usage au moment du test.
			$data = "";
		}
		break;
	case "group/create":
	case "group/createFromSpecial":
		if(!isset($_POST['submit'])) {
			// NOTE : La page "group/create_main_error.html" ne devrait être affichée que si le nombre de sucres roux est insuffisant (inférieur à 30).
			// Si l'utilisateur faisait déjà partie de huit tables, le message ci-dessous s'affichait sur cafejeux.com :
			// `<p class="nack">Vous ne pouvez pas faire partie de plus de 8 tables à la fois, il vous est donc impossible de créer une table pour le moment.</p>`
			if($page === "group/createFromSpecial") {
				$data = get_content(str_replace("FromSpecial", "", $pageUrl) . "_main_error" . $pageExt);
			} else {
				$data = get_content($pageUrl . "_main_ok" . $pageExt);
			}
		} else {
			$pName = isset($_POST['name']) ? $_POST['name'] : "";
			$pDescription = isset($_POST['description']) ? $_POST['description'] : "";
			$error = "";
			if(strlen($pName) < 4 || strlen($pName) > 30) {
				$error = "Le nom d'une table doit faire entre 4 et 30 caractères.";
			} elseif(strtolower($pName) === "table des habitués") {
				// NOTE : Sur cafejeux.com, le nom était bien insensible à la casse mais pas aux espaces entre les mots (donc "table    des habitués" aurait pu être créée).
				$error = "Une table utilise déjà ce nom, choisissez en un autre.";
			} elseif(strlen($pDescription) < 20) {
				$error = "Merci d'écrire une petite présentation de votre table.";
			} elseif(!isset($_POST['game_1']) && !isset($_POST['game_2']) && !isset($_POST['game_3']) && !isset($_POST['game_4']) && !isset($_POST['game_5']) && 
					 !isset($_POST['game_6']) && !isset($_POST['game_7']) && !isset($_POST['game_8']) && !isset($_POST['game_9']) && !isset($_POST['game_11'])) {
				$error = "Vous devez choisir au moins un jeu pour le classement interne.";
			}
			if(strlen($error) <= 0) {
				// NOTE : Quand il n'y a pas d'erreur, il n'y a pas de message à afficher, cafejeux.com redirigeait alors vers la table fraichement créée.
				$data = '<user money="{ARCHIVE_USER_MONEY}" freeMoney="{ARCHIVE_USER_FREE_MONEY}"/><load>menu</load><load>group/6951</load>';
			} else {
				$data = get_content($pageUrl . "_form_error" . $pageExt);
				$data = str_replace("{ARCHIVE_TABLE_CREATION_ERROR}", $error, $data);
			}
		}
		break;
	case "group/search":
		$pName = isset($_POST['name']) ? $_POST['name'] : "";
		if(strlen($pName) < 4 || strlen($pName) > 30) {
			$data = "<alert>Le nom d'une table doit faire entre 4 et 30 caractères.</alert>";
		} else {
			// NOTE : Les tables les plus anciennes sont affichées en premier, à condition que leur nom débute exactement par la requête indiquée.
			$data = get_content($pageUrl . "_ok" . $pageExt);
			$pName = htmlentities($pName);
			$data = str_replace("{ARCHIVE_SEARCH_TABLE_USER_REQUEST}", $pName, $data);
			$data = str_replace("{ARCHIVE_SEARCH_TABLE_1}", $pName . mt_rand(1, 5), $data);
			$data = str_replace("{ARCHIVE_SEARCH_TABLE_2}", $pName . mt_rand(60, 99), $data);
			$data = str_replace("{ARCHIVE_SEARCH_TABLE_3}", $pName . mt_rand(3000, 5000), $data);
		}
		break;
	case "news":
		if(isset($_GET['hide'])) {
			// NOTE : Cela ne fait rien d'autre que cacher la news en haut du site - et sans doute enregistrer ce choix en base de données sur le site cafejeux.com (cf. "_special/head.html").
			$data = "";
		} else {
			$data = get_content($pageUrlExt);
		}
		break;
	case "party/history":
		$gMode = isset($_GET['mode']) ? explode(";", $_GET['mode'])[0] : "";
		$type1 = ""; $type2 = ""; $type3 = "";
		switch($gMode) { case "friendly": $type2 = "selected"; break; case "group": $type3 = "selected"; break; case "default": default: $type1 = "selected"; break; }
		$data = get_content($pageUrlExt);
		$data = str_replace("{ARCHIVE_HISTORY_1}", $type1, $data);
		$data = str_replace("{ARCHIVE_HISTORY_2}", $type2, $data);
		$data = str_replace("{ARCHIVE_HISTORY_3}", $type3, $data);
		break;
	case "party/historyCsv":
		header('Content-Disposition: attachment; filename="historique_cafejeux.csv"');
		header("Content-Type: text/csv");
		$data = get_content($pageUrl . ".csv");
		break;
	case "play":
		$data = "<load>game</load>";
		break;
	case "pvmsg/[id]/delete":
	case "pvmsg/[id]/report":
		if(isset($_GET['id'])) {
			$gPage = isset($_GET['page']) ? intval($_GET['page']) : 1;
			$data = '<load>pvmsg/list?page=' . $gPage . '</load><fill id="msgView"></fill>';
			if($page === "pvmsg/[id]/report") {
				// NOTE : L'ID réel devrait bien sûr être utilisé ici.
				$data .= '<blacklist add="[other_user_index]"/>';
			}
		}
		break;
	case "pvmsg/new":
		$gTo = isset($_GET['to']) ? htmlentities(explode(";", $_GET['to'])[0]) : "";
		$data = get_content($pageUrlExt);
		$data = str_replace("{ARCHIVE_RECIPIENT_NAME}", $gTo, $data);
		break;
	case "pvmsg/send":
		$gTo = isset($_POST['to']) ? $_POST['to'] : "";
		$gContent = isset($_POST['content']) ? $_POST['content'] : "";
		$errors = "";
		if(strlen($gTo) < 4 || strlen($gTo) > 20) {
			$errors .= "<li>Un pseudo doit faire entre 4 et 20 caractères.</li>";
		} elseif($gTo === strtoupper($gTo)) { // prétexte pour afficher un message d'erreur
			$errors .= "<li>Cet utilisateur n'existe pas.</li>";
		}
		if(strlen($gContent) < 4) {
			$errors .= "<li>Votre message doit faire au moins 4 caractères.</li>";
		}
		if(strlen($errors) > 0) {
			$data = get_content($pageUrl . "_error" . $pageExt);
			$data = str_replace("{ARCHIVE_SEND_PVMESSAGE_ERRORS}", $errors, $data);
		} else {
			// NOTE : Que doit-il s'afficher lorsque le message est bien envoyé ? Cette fonctionnalité de cafejeux.com était hors d'usage au moment du test.
			$data = '<fill class="error" id="formError"></fill>';
		}
		break;
	case "shop":
		$gType = isset($_GET['item']) ? intval($_GET['item']) : 0;
		$gType = $gType === 0 && isset($_GET['type']) ? htmlentities(explode(";", $_GET['type'])[0]) : $gType;
		$load = "";
		if(is_int($gType)) {
			if(($gType >= 1 && $gType <= 50 && $gType !== 2) || $gType === 54 || $gType === 55 || $gType === 65 || $gType === 67 ||  $gType === 68) {
				$load = "<load>shop/" . $gType . "</load>";
			}
		} elseif(strlen($gType) > 0) {
			switch($gType) {
				case "Color": case "Drink": case "Furniture": case "GameOption": case "Goodies": case "Sugar": $load = "<load>shop/type/" . $gType . "</load>"; break;
			}
		}
		$data = get_content($pageUrlExt);
		$data = str_replace("{ARCHIVE_LOAD_SUBSHOP}", $load, $data);
		break;
	case "shop/[id]/buy":
		if(isset($_GET['id'])) {
			$gId = intval($_GET['id']);
			if(!isset($_GET['sid'])) {
				$sid = mt_rand(1000, 10000);
				// NOTE : cafejeux.com indiquait bien sûr le nom réel de l'objet et son prix en chiffres, dans la balise <confirm/> ci-dessous.
				// Le sid doit servir à vérifier que la transaction provient bien de l'utilisateur. Il devrait normalement correspondre à la valeur passée en argument sur la fonction JS "js.App.main".
				$data = '<confirm macro="shop@confirmBuy" name="cet objet" price="le prix indiqué en" url="shop/' . $gId . '/buy?sid=' . $sid . '"/>';
			} else {
				// NOTE : L'achat d'une boisson devrait changer le header, voir un exemple dans "shop/buy_drink.html".
				$data = get_content("pages/shop/buy_generic.html");
				$data = str_replace("{ARCHIVE_BUY_SUBSHOP}", "Furniture", $data);
			}
		}
		break;
	case "smileyTip":
		$id = isset($_GET['id']) ? $_GET['id'] : "st_14331";
		$idSplit = explode(";", $id);
		$elementId = htmlentities($idSplit[0]);
		$type = isset($idSplit[1]) ? str_replace("input=", "", htmlentities($idSplit[1])) : "chatInput";
		$subtype = isset($idSplit[2]) ? str_replace("room=", "", htmlentities($idSplit[2])) : "";
		$data = get_content($pageUrlExt);
		$data = str_replace("{ARCHIVE_SMILEY_ELEMENT_ID}", $elementId, $data);
		$data = str_replace("{ARCHIVE_SMILEY_TYPE}", $type, $data);
		$data = str_replace("{ARCHIVE_SMILEY_SUBTYPE}", $subtype, $data);
		break;
	case "user/18269":
	case "user/18269/tip":
		if($isUserFullLoggedIn) {
			$drink = $globalDrinks[$_SESSION['cafeDrink']];
			$data = get_content($pageUrlExt);
			$data = str_replace("{ARCHIVE_USER_DRINK_NAME}", $drink['name'], $data);
			if($page === "user/18269") {
				// NOTE : La date affichée devrait en réalité correspondre à celle de la dernière utilisation du formulaire de connexion ou du "<reboot/>".
				$date = cj_date_today();
				$data = str_replace("{ARCHIVE_USER_DRINK_IMG}", $drink['img'], $data);
				$data = str_replace("{ARCHIVE_USER_DRINK_DESC}", str_replace("'", "\'", $drink['desc']), $data);
				$data = str_replace("{ARCHIVE_USER_LAST_SEEN_FULL}", $date[0], $data);
				$data = str_replace("{ARCHIVE_USER_LAST_SEEN_PARTIAL}", $date[1], $data);
			}
		}
		break;
	case "user/999999":
	case "user/999999/tip":
		$randomAvatar = random_avatar();
		$data = get_content($pageUrlExt);
		$data = str_replace("{ARCHIVE_OTHER_USER_GFX}", $randomAvatar, $data);
		$data = str_replace("{ARCHIVE_OTHER_USER_ICON}", "female", $data); // TODO => pas connecté / pas connectée (dépend de random_avatar())
		$data = str_replace("{ARCHIVE_OTHER_USER_DRINK}", $globalDrinks[mt_rand(0, array_keys($globalDrinks)[count($globalDrinks) - 1])]['name'], $data);
		break;
	case "user/999999/barHistory":
	case "user/999999/furnitures":
	case "user/999999/giveMoney":
		$playerId = "999999"; $playerName = "Joueur";
		if(isset($__recursiondata) && is_int($__recursiondata)) {
			if(isset($globalPlayers[$__recursiondata])) {
				$playerId = strval($__recursiondata);
				$playerName = $globalPlayers[$__recursiondata];
			}
		}
		if($page === "user/999999/giveMoney" && isset($_POST['submit'])) {
			$pPass = isset($_POST['pass']) ? $_POST['pass'] : "";
			$pSugar = isset($_POST['qty']) ? intval($_POST['qty']) : 0;
			$error = "";
			if(strlen($pPass) < 6 || strlen($pPass) > 32) {
				$error = "Ce code secret n'est pas valide. Attention, les différences entre minuscules et majuscules sont prises en compte.";
			} elseif($pSugar <= 0 || $pSugar > ($globalUserFreeMoney + $globalUserMoney)) {
				$error = "Vous n'avez pas assez de sucres.";
			} elseif($isUserLoggedIn && $playerName === $_SESSION['cafeUsername']) {
				$error = "Vous essayez de faire quoi là exactement ?";
			}
			if(strlen($error) > 0) {
				$data = get_content($pageUrl . "_form_error" . $pageExt);
				$data = str_replace("{ARCHIVE_GIVE_MONEY_ERROR}", $error, $data);
			} else {
				// NOTE : Un message est envoyé au bénéficiaire en cas de succès. Impossible toutefois d'en savoir plus car cette fonctionnalité de cafejeux.com était hors d'usage au moment du test.
				$data = '<fill class="error" id="formError"></fill>';
			}
		} else {
			$data = get_content($pageUrlExt);
		}
		$data = str_replace("{ARCHIVE_OTHER_ID}", $playerId, $data);
		$data = str_replace("{ARCHIVE_OTHER_USERNAME}", $playerName, $data);
		break;
	case "user/[id]/addBlackContact";
	case "user/[id]/addContact":
	case "user/[id]/remBlackContact":
	case "user/[id]/remContact":
		// NOTE : Il était techniquement possible sur le site cafejeux.com qu'un utilisateur soit à la fois sur la liste d'amis et sur liste noire. :)
		$action = explode("[id]/", $page)[1];
		if(isset($_GET['id'])) {
			$gOtherId = intval($_GET['id']);
			switch($action) {
				case "addBlackContact":
					$data = '<alert>Cet utilisateur a été ajouté à votre liste noire.</alert><blacklist add="' . $gOtherId . '"/>';
					break;
				case "addContact":
					$data = "<alert>Cet utilisateur a été ajouté à votre liste de contacts.</alert>";
					break;
				case "remBlackContact":
					// NOTE : "<load>[data]</load>" ne devrait être présent que si l'URL a le paramètre GET "redir=1".
					$data = '<alert>Cet utilisateur a été retiré de votre liste noire.</alert><load>user/' . $gOtherId . '</load><blacklist remove="' . $gOtherId . '"/>';
					break;
				case "remContact":
					$data = "<alert>Cet utilisateur a été retiré de votre liste de contacts.</alert>";
					break;
			}
		}
		break;
	case "user/cancelDelete":
		if(isset($_GET['submit'])) {
			$data = "<alert>La demande de suppression a été annulée. Votre compte ne sera pas supprimé.</alert><load>user/18269</load>";
		} else {
			$date = cj_date_today();
			$data = get_content($pageUrlExt);
			$data = str_replace("{ARCHIVE_USER_DELETE_FULL_DATE}", $date[0], $data);
			$data = str_replace("{ARCHIVE_USER_DELETE_PARTIAL_DATE}", $date[1], $data);
		}
		break;
	case "user/changeFriendlyObservable":
		// NOTE : Le changement sur la visibilité des matchs amicaux devrait être effectué ici, puis la page rechargée avec "<load>user/18269</load>".
		// A la place, on utilise quelques lignes de JavaScript pour modifier immédiatement le texte.
		$data = "<script><![CDATA[";
		$data .= 'var ArchiveMatchsElement = document.getElementsByClassName("misc")[0].getElementsByTagName("li")[3].getElementsByTagName("span")[0];';
		$data .= 'if(!window.ArchiveMatchsObservables) window.ArchiveMatchsObservables = ArchiveMatchsElement.outerHTML;';
		$data .= 'if(!window.ArchiveMatchsPrives) window.ArchiveMatchsPrives = "<span onmouseover=\"mt.js.Tip.show(this,\'Vos parties amicales ne peuvent actuellement &lt;strong&gt;pas être observées par d&bsol;\'autres ';
		$data .= 'joueurs&lt;/strong&gt;. Cliquez sur Modifier pour les rendre à nouveau publiques.\',null)\" onmouseout=\"mt.js.Tip.hide()\">Privés</span>";';
		$data .= 'ArchiveMatchsElement.outerHTML = ArchiveMatchsElement.outerHTML === ArchiveMatchsObservables ? ArchiveMatchsPrives : ArchiveMatchsObservables;';
		$data .= "]]></script>";
		break;
	case "user/chooseDrink":
		if($isUserFullLoggedIn && (($dayChanged) || (isset($_SESSION['cafePrevUsername']) && strtolower($_SESSION['cafePrevUsername']) !== strtolower($_SESSION['cafeUsername'])))) {
			// NOTE : 1 seule boisson par jour par utilisateur, cette portion de code ne sert qu'à simuler ce principe.
			$isUserFullLoggedIn = false;
			unset($_SESSION['cafeDrink']);
			if($dayChanged) unset($_SESSION['cafeDayChanged']);
		}
		if($isUserFullLoggedIn) {
			$data = "<load>head</load><load>game</load>";
		} else {
			$_SESSION['cafeDay'] = $day;
			$gDrinkId = isset($_GET['id']) ? intval(explode(";", $_GET['id'])[0]) : -1;
			if($gDrinkId < 0 || !array_key_exists($gDrinkId, $globalDrinks)) {
				$data = get_content($pageUrlExt);
			} else {
				$_SESSION['cafeDrink'] = $gDrinkId;
				$data = "<load>head</load><load>game</load>";
			}
		}
		break;
	case "user/delete":
		if(isset($_POST['submit'])) {
			$pPass = isset($_POST['pass']) ? $_POST['pass'] : "";
			$pSid = isset($_POST['sid']) ? $_POST['sid'] : ""; // inutilisé ici
			if(strlen($pPass) < 6 || strlen($pPass) > 32) {
				$data = "<alert>Ce code secret n'est pas valide. Attention, les différences entre minuscules et majuscules sont prises en compte.</alert>";
			} else {
				// NOTE : Dans cette situation, le lien "Supprimer mon compte !" sur "Ma page" devrait être remplacé par le code HTML ci-dessous et $data devrait renvoyer vers "user/18269" au lieu de "user/cancelDelete".
				// <a href="#user/cancelDelete" class="button" onclick="js.XmlHttp.get('user/cancelDelete',this); return false;">Annuler la suppression en cours</a>
				// La page principale devrait également afficher ceci, juste après `<div class="cache">(...)</div>` :
				// <div class="nack">Attention ! La suppression de votre compte a été demandée, elle sera effectuée prochainement !
				// <a href="#user/cancelDelete" onclick="js.XmlHttp.get('user/cancelDelete',this); return false;">en savoir plus</a>
				// </div>
				// A noter enfin que la suppression de compte semblait nécessiter l'intervention d'un modérateur ou d'un administrateur pour être effective (ou alors la fonctionnalité ne fonctionnait plus au moment du test) :
				// en effet, le compte testé était toujours actif plus de dix jours après la demande de suppression !
				$data = "<alert>Votre demande a été enregistrée avec succès. Votre compte sera supprimé dans 72 heures environ. Vous pouvez annuler votre demande d'ici là.</alert><load>user/cancelDelete</load>";
			}
		} else {
			$data = get_content($pageUrlExt);
		}
		break;
	case "user/goptions":
		if(isset($_POST['submit'])) {
			// NOTE : Il faudrait également récupérer ici les différentes options activées ou désactivées, par exemple "$_POST['opt_4_0']".
			// La page devrait ensuite être actualisée, donc $data devrait en réalité commencer avec "<load>user/goptions</load>".
			$data = "<alert>Vos choix d'options ont été enregistrés.</alert>";
		} else {
			$data = get_content($pageUrlExt);
		}
		break;
	case "user/logout":
		$_SESSION['cafePrevUsername'] = $_SESSION['cafeUsername'];
		unset($_SESSION['cafeUsername']);
		$data = "<reboot/>";
		break;
	case "user/mailMyFriends":
		if(!isset($_POST['submit'])) {
			if(isset($_GET['group'])) {
				$gGroup = intval($_GET['group']);
				$data = get_content($pageUrl . "_group" . $pageExt);
				$data = str_replace("{ARCHIVE_GROUP_ID}", strval($gGroup), $data);
				$data = str_replace("{ARCHIVE_GROUP_NAME}", ($gGroup === 420 ? "Table des Habitués" : "CaféJeux 2007-2020"), $data);
				$data = str_replace("{ARCHIVE_GROUP_REQUIRE_VALIDATION}", ($gGroup !== 420 ? '<p class="help">Il devra être accepté avant de faire partie de la table, comme tout autre joueur.</p>' : ""), $data);
			} else {
				$data = get_content($pageUrl . "_sponsor" . $pageExt);
			}
		} else {
			$pEmail = isset($_POST['email']) ? $_POST['email'] : "";
			$pText = isset($_POST['text']) ? $_POST['text'] : "";
			$pGroup = isset($_POST['group']) ? intval($_POST['group']) : 0;
			if(strlen($pEmail) < 10 || !preg_match('`^\w([-_.]?\w)*@\w([-_.]?\w)*\.([a-z]{1,6})$`', $pEmail)) {
				$data = get_content($pageUrl . "_form_error" . $pageExt);
			} elseif(isset($_POST['group']) && $pGroup <= 0) {
				$data = "<alert>Une erreur inconnue s'est produite.</alert>";
			} else {
				// NOTE : Si un email avait déjà été envoyé au destinataire, cafejeux.com affichait le message d'erreur suivant : "Un message a déjà été envoyé à cette adresse." (adresse email insensible à la casse).
				$data = get_content($pageUrl . "_form_ok" . $pageExt);
			}
		}
		break;
	case "user/modifInfos":
		$year = date("Y");
		if(isset($_POST['submit'])) {
			$pEmail = isset($_POST['email']) ? $_POST['email'] : "";
			$pBirthdayD = (isset($_POST['birthdayD']) && !empty($_POST['birthdayD'])) ? intval($_POST['birthdayD']) : "";
			$pBirthdayM = (isset($_POST['birthdayM']) && !empty($_POST['birthdayM'])) ? intval($_POST['birthdayM']) : "";
			$pBirthdayY = (isset($_POST['birthdayY']) && !empty($_POST['birthdayY'])) ? intval($_POST['birthdayY']) : "";
			$pCity = isset($_POST['city']) ? $_POST['city'] : "";
			$pPass = isset($_POST['pass']) ? $_POST['pass'] : "";
			$errors = "";
			if(strlen($pPass) < 6 || strlen($pPass) > 32) {
				$errors .= "<li>Ce code secret n'est pas valide. Attention, les différences entre minuscules et majuscules sont prises en compte.</li>";
			}
			if((is_int($pBirthdayY) && ($pBirthdayY < 1900 || $pBirthdayY > intval($year))) ||
			   (is_int($pBirthdayM) && ($pBirthdayM < 1 || $pBirthdayM > 12)) ||
			   (is_int($pBirthdayD) && ($pBirthdayD < 1 || $pBirthdayD > 31))) {
				// NOTE : La vérification n'était pas plus poussée que cela sur cafejeux.com, ce qui causait certains bugs. Par exemple :
				// - En précisant le jour, le mois mais en omettant l'année de naissance, la page de profil indiquait "null ans".
				// - En mettant 31 comme jour, 12 comme mois, et l'année civile en cours, la page de profil indiquait "-1 ans" (sauf le 31/12).
				$errors .= "<li>La date de naissance n'est pas valide.</li>";
			}
			if(strlen($pCity) > 50) {
				$errors .= "<li>Votre ville ou région ne doit pas dépasser 50 caractères.</li>";
			}
			if(strlen($pEmail) > 0 && !preg_match('`^\w([-_.]?\w)*@\w([-_.]?\w)*\.([a-z]{1,6})$`', $pEmail)) {
				$errors .= "<li>Cette adresse email n'est pas à un format valide.</li>";
			}
			$f = "_form_ok";
			if(strlen($errors) > 0) {
				$f = "_form_error";
			}
			$data = get_content($pageUrl . $f . $pageExt);
			$data = str_replace("{ARCHIVE_USER_MODIF_ERRORS}", $errors, $data);
		} else {
			$data = get_content($pageUrlExt);
		}
		$data = str_replace("{ARCHIVE_USER_BIRTH_YEAR}", $year, $data);
		break;
	case "user/modifPass":
		if(isset($_POST['submit'])) {
			$pOldPass = isset($_POST['oldPass']) ? $_POST['oldPass'] : "";
			$pPass = isset($_POST['pass']) ? $_POST['pass'] : "";
			$pPass2 = isset($_POST['pass2']) ? $_POST['pass2'] : "";
			$errors = "";
			if(strlen($pOldPass) < 6 || strlen($pOldPass) > 32) {
				$errors .= "<li>Ce code secret n'est pas valide. Attention, les différences entre minuscules et majuscules sont prises en compte.</li>";
			}
			if(strlen($pPass) < 6 || strlen($pPass) > 32) {
				$errors .= "<li>Votre code secret doit faire entre 6 et 32 caractères.</li>";
			} elseif($pPass !== $pPass2) {
				$errors .= "<li>Vous avez tapé deux codes secrets différents, veuillez donner deux fois le même.</li>";
			}
			$f = "_form_ok";
			if(strlen($errors) > 0) {
				$f = "_form_error";
			}
			$data = get_content($pageUrl . $f . $pageExt);
			$data = str_replace("{ARCHIVE_USER_MODIF_ERRORS}", $errors, $data);
		} else {
			$data = get_content($pageUrlExt);
		}
		break;
	case "user/search":
		if(isset($_POST['name'])) {
			$f = "_error";
			$error = "";
			$pName = "";
			if(strlen($_POST['name']) < 4) {
				$error = "Vous devez indiquer au moins 4 caractères pour effectuer une recherche.";
			} else {
				if(!isset($_POST['online'])) {
					$f = "_ok";
					$pName = htmlentities($_POST['name']);
				} else {
					$error = "Aucun utilisateur ne correspond à votre recherche.";
				}
			}
			$data = get_content($pageUrl . "_post" . $f . $pageExt);
			if(strlen($error) > 0) {
				$data = str_replace("{ARCHIVE_SEARCH_ERROR}", $error, $data);
			} else {
				// NOTE : S'il n'y avait qu'un seul résultat correspondant à la requête, cafejeux.com redirigeait immédiatement vers la page de profil en question : "<load>user/[id]</load>"
				// Si le nombre de profils pouvant correspondre dépassait 15, le message suivant était affiché : "Au moins 15 utilisateurs correspondent à votre recherche."
				// Enfin, seuls les pseudos commençant exactement par la requête effectuée (casse insensible) étaient affichés.
				$data = str_replace("{ARCHIVE_SEARCH_NAME_1}", $pName . mt_rand(1, 9), $data);
				$data = str_replace("{ARCHIVE_SEARCH_NAME_2}", $pName . mt_rand(1000, 9999), $data);
			}
		} else {
			$gOnline = isset($_GET['online']) ? intval($_GET['online']) : 0;
			$checked = "";
			if($gOnline === 1) {
				$checked = 'checked="checked"';
			}
			$data = get_content($pageUrl . "_main" . $pageExt);
			$data = str_replace("{ARCHIVE_SEARCH_ONLINE}", $checked, $data);
		}
		break;
	case "user/siteSound":
		// NOTE : L'état du son semblait être enregistré en base de données sur cafejeux.com.
		$gSound = isset($_GET['v']) ? intval($_GET['v']) : 0;
		if($gSound < 0 || $gSound > 1) $gSound = 0;
		$data = get_content($pageUrlExt);
		$data = str_replace("{ARCHIVE_SOUND_CURRENT_STATE_ID}", strval($gSound), $data);
		$data = str_replace("{ARCHIVE_SOUND_CURRENT_STATE_DESC}", str_replace("{}", ($gSound === 1 ? "" : "dés"), "Sons {}activés"), $data);
		$data = str_replace("{ARCHIVE_SOUND_CURRENT_STATE_STR}", ($gSound === 1 ? "on" : "off"), $data);
		$data = str_replace("{ARCHIVE_SOUND_UPDATE_STATE_ID}", ($gSound === 1 ? "0" : "1"), $data);
		break;
	case "user/tipContact":
		$gElementId = isset($_GET['rid']) ? htmlentities(explode(";", $_GET['rid'])[0]) : "cl_96463";
		if($gElementId !== "cl_96463") {
			$elementIdField = ($gElementId === "cl_72259" || $gElementId === "cl_27685") ? "name" : "to";
			$data = get_content($pageUrl . "_form" . $pageExt);
			$data = str_replace("{ARCHIVE_ELEMENT_ID_CONTACTS}", $gElementId, $data);
			$data = str_replace("{ARCHIVE_ELEMENT_ID_FIELD}", $elementIdField, $data);
		} else {
			$data = get_content($pageUrl . "_main" . $pageExt);
		}
		break;
	case "user/tkFurniture":
		$gId = isset($_GET['id']) ? intval($_GET['id']) : 0;
		if($gId >= 0) {
			// NOTE : Cette fonctionnalité de cafejeux.com était hors d'usage au moment du test.
			$data = "";
		}
		break;
	default:
		// NOTE : Toutes les pages d'utilisateurs et de groupes ne sont bien sûr pas archivées.
		// Cette section a donc simplement pour but d'éviter les erreurs 404 en redirigeant vers la page d'utilisateur ou de groupe par défaut.
		if(!isset($__recursion)) {
			$__recursion = true;
			$checkPage = explode("/", $page, 3);
			$recursionValue = "0";
			switch($checkPage[0]) {
				case "forum":
					$recursionValue = "999999";
					if(isset($checkPage[1])) {
						if(!is_numeric($checkPage[1])) {
							$checkPage[0] .= "/" . $checkPage[1]; $checkPage[1] = "";
							if(isset($checkPage[2])) {
								$__recursiondata = intval($checkPage[2]);
								if(substr_count($checkPage[2], "/") >= 1) { $checkPage[2] = explode("/", $checkPage[2])[1]; } else { unset($checkPage[2]); }
							}
						} else {
							$__recursiondata = intval($checkPage[1]);
						}
					}
					break;
				case "group": $recursionValue = "6864"; break;
				case "user": $recursionValue = "999999"; if(isset($checkPage[2])) { $__recursiondata = intval($checkPage[1]); } break;
			}
			if($recursionValue !== "0") {
				$gp2 = isset($checkPage[2]) ? "/" . $checkPage[2] : "";
				$_GET['p'] = $checkPage[0] . "/" . $recursionValue . $gp2;
				include "dispatch.php";
				exit;
			}
		}
		break;
}

$isPageAvailable = $isPagePublic || $isUserLoggedIn;

if($data !== null) {
	if($isPageAvailable) {
		if($isUserLoggedIn && !$isUserFullLoggedIn && $page !== "user/chooseDrink") {
			http_response_code(302);
			$data = "<load>user/chooseDrink</load>";
		} elseif(isset($_SESSION['cafeUsername'])) {
			if(isset($_SESSION['cafeDay']) && $_SESSION['cafeDay'] !== $day && !isset($_SESSION['cafeDayChanged'])) {
				// NOTE : En se rendant sur la page du bar (liste des jeux), cafejeux.com forçait toujours l'affichage de la page de choix de boisson.
				$_SESSION['cafeDayChanged'] = true;
				$data = '<user money="{ARCHIVE_USER_MONEY}" freeMoney="0"/><load>user/dayChanged</load>'; // NOTE : Dès le changement de jour, le nombre de sucres blancs restant de la veille passe à 0.
			}
			$data = str_replace("{ARCHIVE_USERNAME}", htmlentities($_SESSION['cafeUsername']), $data);
			$data = str_replace("{ARCHIVE_USER_MONEY}", strval($globalUserMoney), $data);
			$data = str_replace("{ARCHIVE_USER_FREE_MONEY}", strval($globalUserFreeMoney), $data);
			$data = str_replace("{ARCHIVE_USER_PRIZE_TOKEN}", strval($globalUserPrizeToken), $data);
			$data = str_replace("{ARCHIVE_USER_CITY}", $globalUserCity, $data);
		}
		echo $data;
	} elseif(!$isUserLoggedIn) {
		echo "<reboot/>";
	}
} else {
	http_response_code(301);
	echo "<load>game</load>";
}

?>