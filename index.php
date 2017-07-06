<!DOCTYPE HTML>
<?php
include "includes/config.php";

include CONFIG_COMMON_PATH."/includes/core.php";
if (CONFIG_REQUIRE_AUTHENTICATION)
	include CONFIG_COMMON_PATH."/includes/auth.php";
include "includes/database.php";
?>
<html>
<head>
	<?php include CONFIG_COMMON_PATH."/includes/head.php"; ?>
	<title>bmffd — hooYa!</title>
	<script type="text/javascript">
	function toggleFilter() {
		var filter = document.getElementById('filter');
		if (filter.style.display == 'none') filter.style.display = 'block';
		else filter.style.display = 'none';
	}
	function changeExtAttrs(media_class) {
		var ext_attrs = document.getElementById('ext_attrs');
		ext_attrs.innerHTML = '';
		if (media_class == 'anime') {
			var row = document.createElement('div');
			row.style.display = 'table-row';
			row.style.height = '30px';
			row.style.width = '100%';
			ext_attrs.appendChild(row);

			var episode = document.createElement('div');
			episode.style.display = 'table-cell';
			episode.style.height = '100%';
			episode.style.float = 'left';
			episode.innerHTML = 'Episode';
			row.appendChild(episode);

			episode = document.createElement('div');
			episode.style.display = 'table-cell';
			episode.style.height = '100%';
			episode.innerHTML = '<input type="number" name="episode"></input>';
			episode.style.float = 'right';
			row.appendChild(episode);

			row = document.createElement('div');
			row.style.display = 'table-row';
			row.style.height = '30px';
			row.style.width = '100%';
			ext_attrs.appendChild(row);

			var season = document.createElement('div');
			season.style.display = 'table-cell';
			season.style.height = '100%';
			season.style.float = 'left';
			season.innerHTML = 'Season';
			row.appendChild(season);

			var season = document.createElement('div');
			season.style.display = 'table-cell';
			season.style.height = '100%';
			season.style.float = 'right';
			season.innerHTML = '<input type="number" name="season"></input>';
			row.appendChild(season);
		}
	}
	</script>
</head>
<body>
<div id="container">
<div id="left_frame">
	<div id="logout">
		<?php
		if (isset($_SESSION['userid'])) {
			print('<a href="'.CONFIG_WEBHOMEPAGE.'">home</a></br>');
			print('<a href="'.CONFIG_COMMON_WEBPATH.'logout.php">logout</a>');
		}
		else {
			print('<a href="'.CONFIG_COMMON_WEBPATH.'login.php?ref='.$_SERVER['REQUEST_URI'].'">login</a>');
		}
		?>
	</div>
	<img id="mascot" src=<?php echo $_SESSION['mascot'];?>>
</div>
<div id="right_frame">
	<div id="header" style="margin-bottom:20px;">
		<div style="width:33%;float:left;">&nbsp</div>
		<div style="width:33%;float:left;text-align:center;">&nbsp</div>
		<div style="width:33%;float:left;text-align:right;"><a href='help/'>search help</a><br/><a href="random.php">random untagged</a></div>
	</div>
	<div style="width:100%;padding-bottom:20px;text-align:center;">
		<h1>hooYa!</h1>
	</div>
	<form style="width:100%;" action="browse.php" method="get" >
		<div><input type="text" style="margin:auto;display:block;width:70%;margin-bottom:10px;" name="query" placeholder="search_terms"></input></div>
		<div style="width:70%;display:block;margin:auto;margin-bottom:10px;vertical-align:top;">
		<input type="submit" style="width:20%;vertical-align:top;" value="いこう！"></input>
		<a onClick="toggleFilter()" style="float:right;">filter</a>

		</div>
		<div id="filter" style="width:70%;margin:auto;display:none;">
		<div style="float:left;vertical-align:bottom;margin-bottom:10px;">Media Type</div>
			<select name="media_class" onChange="changeExtAttrs(this.value)" style="margin-bottom:10px;width:30%;text-align:center;float:right;border-bottom:0px;">
			<option value=""> </option>
			<option value="anime">anime</option>
			<option value="single_image">single_image</option>
			<option value="movie">movie</option>
			<option value="manga">manga</option>
			<option value="music">music</option>
			<option value="video">video</option>
			</select>
		<div id="ext_attrs" style="display:table;width:100%;padding-bottom:50px;"></div>
		</div>
	</form>
	<div style="width:100%;text-align:center;">
		<?php print("now serving ");
		$info = db_info();
		print number_format($info['Files']);
		print " files";
		?>
	</div>
	<div style="width:100%;text-align:center;">
		<a href="popular/">check out our popular tags!</a>
	</div>

</div>
</div>
</body>
</html>