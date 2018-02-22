<?php
include "includes/config.php";

include CONFIG_COMMON_PATH."includes/core.php";
include CONFIG_HOOYA_PATH."includes/database.php";
include CONFIG_HOOYA_PATH."includes/render.php";
include CONFIG_COMMON_PATH."includes/auth.php";

// View the first page of results
// unless the user specified otherwise
$page = 1;
if (isset($_GET['page']))
	$page = $_GET['page'];


// A list of pictures w/ new tags
$results = db_getrecent($page);
$totalpages = floor($results['Count']/CONFIG_THUMBS_PER_PAGE) + 1;
unset($results['Count']);
?>
<!DOCTYPE HTML>
<html>
<head>
	<?php include CONFIG_COMMON_PATH."includes/head.php";
	include CONFIG_HOOYA_PATH."includes/head.php"; ?>
	<title>bigmike — hooYa!</title>
</head>
<body>
<div id="container">
<div id="leftframe">
	<nav>
		<?php print_login(); ?>
	</nav>
	<aside>
		<h1 style="text-align:center;">hooYa!</h1>
		<?php render_min_search(); render_hooya_headers(); ?>
	</aside>
</div>
<div id="rightframe">
	<?php if (!count($results)) {
		print '<header>'
		. 'No recent activity to show!'
		. '</header><main class=single><div id=hack>'
		. '<img src="' . CONFIG_HOOYA_WEBPATH . 'img/none.jpg">'
		. '</div></main>';
	} else if (isset($_GET['thumbs'])) {
		print '<main class=thumbs>';
		render_thumbs($results);
		print '</main>';
	}
	else {
		print '<main class=list>';
		render_list($results);
		print '</main>';
	}?>
	<footer>
		<?php
		foreach($_GET as $param => $value) {
			if ($param != 'page') $q[$param] = $value;
		}
		render_pagenav($page, $totalpages, $q);?>
		<div><?php print ($totalpages . " pages")?></div>
		<?php $newGET = $_GET;

		if (!isset($_GET['thumbs'])) {
			print "<a href='?" . http_build_query($newGET) . "&thumbs'>"
			. "thumbnail view</a>";
		}
		else {
			unset($newGET['thumbs']);
			print "<a href='?" . http_build_query($newGET) . "'>"
			. "full view</a>";
		}
		?>

	</footer>
</div>
</body>
</html>
