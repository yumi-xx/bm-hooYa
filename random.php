<!DOCTYPE HTML>
<?php
include "includes/config.php";

include CONFIG_COMMON_PATH."includes/core.php";
include CONFIG_HOOYA_PATH."includes/database.php";
include CONFIG_HOOYA_PATH."includes/render.php";
?>
<html>
<head>
	<?php include CONFIG_COMMON_PATH."includes/head.php";
	include CONFIG_HOOYA_PATH."includes/head.php"; ?>
	<title>bigmike — hooYa! untagged</title>
</head>
<body>
<div id="container">
<div id="leftframe">
	<nav>
		<?php print_login(); ?>
	</nav>
	<aside>
		<h1 style="text-align:center;">hooYa!</h1>
		<?php render_min_search(); render_hooya_headers();?>
	</aside>
</div>
<div id="rightframe">
	<?php
	if (!isset($_GET['untagged'])) {
		print "<h2>random sixteen</h2>"
		. "<header>"
		. "<a href=?untagged&list>Show Untagged</a>"
		. "</header>";
		$results = db_getrandom(16);
	} else {
		print "<h2>random untagged sixteen</h2>"
		. "<header>"
		. "<a href=?>Show all</a>"
		. "</header>";
		$results = db_getuntaggedrandom(16);
	}
	if (!count($results)) {
		print '<header>'
		. 'No more pictures to index!'
		. '</header><main class=single><div id=hack>'
		. '<img src="' . CONFIG_HOOYA_WEBPATH . 'img/congrats.jpg">'
		. '</div></main>';
	}
	else {
		if (isset($_GET['list'])) {
			print '<main class="thumbs">';
			render_thumbs($results);
			print '</main>';
		}
		else {
			print '<main class="list">';
			render_list($results);
			print '</main>';
		}
		print '<footer>'
		. '<a href="#" onClick="location.reload()">more!</a>';
		$newGET = $_GET;
		if (!isset($_GET['list'])) {
			print "<a href='?" . http_build_query($newGET) . "&list'>"
			. "thumbnail view</a>";
		}
		else {
			unset($newGET['list']);
			print "<a href='?" . http_build_query($newGET) . "'>"
			. "full view</a>";
		}
		print '</footer>';
	}
	?>
</div>
</body>
</html>
