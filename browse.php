<!DOCTYPE HTML>
<?php
include "includes/config.php";

include CONFIG_COMMON_PATH."includes/core.php";
include "includes/search.php";
if (CONFIG_REQUIRE_AUTHENTICATION)
	include CONFIG_COMMON_PATH."includes/auth.php";
include CONFIG_HOOYA_PATH."includes/database.php";
include CONFIG_HOOYA_PATH."includes/render.php";

foreach($_GET as $param => $value) {
	if ($param != 'page') $q[$param] = $value;
}
$page = 1;
if (isset($_GET['page']))
	$page = $_GET['page'];

// Get all results
$results = hooya_search($q, $page);
$totalpages = floor($results['Count']/CONFIG_THUMBS_PER_PAGE) + 1;
// Unset the extra parameters we were given (so they are not listed
// as results
unset($results['Count']);

?>
<html>
<head>
	<?php include CONFIG_COMMON_PATH."includes/head.php";
	include CONFIG_HOOYA_PATH."includes/head.php"; ?>
	<title>bigmike — hooYa! <?php echo $_GET['q']?></title>
	<script>var currpage = <?php echo $page?></script>
	<script type="text/javascript" src="js/hotkeys.js"></script>
	<script type="text/javascript" src="js/browse.js"></script>
</head>
<body>
<div id="container">
<div id="leftframe">
	<nav>
		<?php print_login();?>
	</nav>
	<aside>
		<h1 style="text-align:center;">hooYa!</h1>
		<?php render_min_search($q['query'])?>
	</aside>
</div>
<div id="rightframe">
	<header>
		<a href=".">back to search</a>
		<div><?php render_prettyquery($q); ?></div>
		<?php
		$newGET = $_GET;
		if (!isset($_GET['list'])) {
			print "<a href='?" . http_build_query($newGET) . "&list'>"
			. "list view</a>";
		}
		else {
			unset($newGET['list']);
			print "<a href='?" . http_build_query($newGET) . "'>"
			. "thumbnail view</a>";
		}
		?>
	</header>

	<?php
	if ($results['message']) print $results['message'];
	else if (isset($_GET['list'])) {
		print '<main>'
		. '<table>';
		render_titles($results);
		print '</table>'
		. '</main>';
	}
	else {
		print '<main class="thumbs">';
		render_thumbnails($results);
		print '</main>';
	}
	?>

	<footer>
	<?php
		render_pagenav($page, $totalpages, $q);
	?>
	<div><?php print ($totalpages . " pages")?></div>
	</footer>
</div>

</body>
</html>
