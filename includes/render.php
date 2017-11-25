<?php
function render_file($key, $ftype)
{
	$key = rawurlencode($key);
	switch($ftype) {
	case 'image':
		print '<main class="single">'
		// Wrap the img in a div to preserve the aspect ratio
		. "<div id=hack>"
		. "<a href='download.php?key=$key'>"
		. "<img src='download.php?key=$key'>"
		. '</a>'
		. "</div>"
		. '</main>';
		break;
	case 'video':
		print '<main class=single>'
		. "<div id=hack>"
		. "<a href='download.php?key=$key'>"
		. "<img src='download.php?key=$key&preview'>"
		. '</a>'
		. '</div>'
		. '</main>';
		break;
	}
}
function render_properties($key, $class, $editmode = True)
{
	if (!isset(DB_FILE_EXTENDED_PROPERTIES[$class])) {
		syslog(LOG_INFO|LOG_DAEMON, "$class not defined in"
		. " DB_FILE_EXTENDED_PROPERTIES in includes/database.php");
		return;
	}
	$fileproperties = db_getproperties($key);
	$fileproperties['property'] = htmlspecialchars(
		$fileproperties['property'], ENT_QUOTES
	);
	print '<table id="properties">';
	foreach (DB_FILE_EXTENDED_PROPERTIES[$class] as $property => $value) {
		print '<tr>'
		. "<td>$property</td>"
		. '<td>';
		if ($value['Renderer']) {
			call_user_func($value['Renderer'], $fileproperties[$property]);
		}
		else if (!logged_in() || $value['Immutable'] || !$editmode) {
			print $fileproperties[$property];
		}
		else {
			print "<input name='properties[$property]' id='box'";
			if ($value['Type'])
				print " type='" . $value['Type'] . "'";
			print " value='" . $fileproperties[$property] . "'>";
		}
		print '</td></tr>';
	}
	print '</table>';
}
function render_tags($key)
{
	$tags = db_get_tags([$key])[$key];
	print '<table id="tags">';
	foreach ($tags as $tag) {
		$space = ucwords(
			htmlspecialchars($tag['Space'], ENT_QUOTES)
		);
		$mem = ucwords(
			htmlspecialchars($tag['Member'], ENT_QUOTES)
		);

		print '<tr><td>';
		if (logged_in())
			print "<input class='space' name='tag_space[]'"
			. " list=namespace-suggest-list value='$space'>";
		else
			print $space;
		print '</td>';
		print '<td>';
		if (logged_in())
			print "<input class='member' name='tag_member[]'"
			. " list=member-suggest-list value='$mem'>";
		else
			print $mem;
		print '</td></tr>';
	}
	print '<datalist id=namespace-suggest-list></datalist>';
	print '<datalist id=member-suggest-list></datalist>';
	print '</table>';
}
function render_classmenu($class = NULL)
{
	foreach(DB_MEDIA_CLASSES as $c => $value) {
		if ($value['Restricted'] && !logged_in() ) continue;
		print "<option value='$c'";
		if ($c == $class) print " selected";
		print ">$c</option>";
	}
}
function render_prettyquery($query)
{
	// Construct a pretty header on the fly from the given query
	if (isset($query['query']))
		echo $query['query'];
	else
		echo 'All ';
	if (isset($query['media_class']))
		echo ' ' . $query['media_class'];
	if (isset($query['properties'])) {
		echo ' (';
		$i = 0;
		foreach ($query['properties'] as $property => $value) {
			if ($i++) echo " ";
			echo "$property: $value";
		}
		echo ')';
	}
}
function render_list($results)
{
	foreach ($results as $key => $result) $keys[] = $key;
	$tags = db_get_tags($keys);
	$tags_by_space = db_get_tags_by_space($keys);
	foreach ($results as $key => $result) {
		$class = $result['Class'];
		$indexed = parse_timestamp($result['Indexed']);
		$fileproperties = db_getproperties($key);
		print "<div id=searchresult>"
		. "<div id=preview><a"
		. " href='view.php?key=".rawurlencode($key) . "'>"
		. "<img"
		. " src='download.php?key=".rawurlencode($key)."&thumb'"
		. " >"
		. "</a></img>"
		. "</div>"
		. "<div id=details>"
		. "<h4>$class</h4>"
		. "<span>Indexed on $indexed</span>";

		print "<dl>";
		foreach (DB_FILE_EXTENDED_PROPERTIES[$class] as $property => $value) {
			if (is_null($fileproperties[$property])) continue;
			// Allow a function to do the rendering for us
			if ($value['Renderer']) {
				call_user_func($value['Renderer'], $fileproperties[$property]);
			// Legacy fall-back
			} else {
				print "<div id=tag><dt>$property</dt>"
				. "<dd>".$fileproperties[$property]."</dd></div>";
			}
		}
		$taglist = $tags_by_space[$key];
		foreach ($taglist as $space => $tags) {
			$space = ucwords(
				htmlspecialchars($space, ENT_QUOTES)
			);
			print "<div id=tag>";
			if ($space)
			print "<dt>$space</dt>";

			foreach ($tags as $tag) {
				$mem = ucwords(
					htmlspecialchars($tag['Member'], ENT_QUOTES)
				);
				print "<dd><a href='browse.php?query="
				. urlencode($mem) . "'>$mem</a></dd>";
			}
			print "</div>";
		}
		print "</dl></div></div>";
	}
}
function render_thumbs($results)
{
	foreach ($results as $key => $result) $keys[] = $key;
	$tags = db_get_tags($keys);
	foreach ($results as $key => $result) {
		$class = $result['Class'];
		$indexed = parse_timestamp($result['Indexed']);
		$fileproperties = db_getproperties($key);
		print "<a"
		. " href='view.php?key=".rawurlencode($key) . "'>"
		. "<img"
		. " src='download.php?key=".rawurlencode($key)."&thumb'"
		. " >"
		. "</a></img>"
		. "";
	}
}
function render_titles($results)
{
	foreach ($results as $key => $result) {
		print "<span"
		. " onClick='window.location.href=\"view.php?key="
		. rawurlencode($key)
		. "\"'>";
		render_title($key);
		print "</span>";
	}
}
function render_pagenav($currpage, $totalpages, $q = NULL)
{
	print '<form method="GET">';
	if ($currpage > 1) {
		if ($q) print "<a href='?".http_build_query($q)."&page=".($currpage-1)."'><</a> ";
		else print "<a href='?page=".($currpage-1)."'><</a> ";
	}
	print '<input style="text-align:center;width:50px;"'
	. ' name="page" type="text" Value=' . $currpage . '>';

	if ($q) render_hidden_inputs($q);

	if ($currpage < $totalpages) {
		if ($q) print " <a href='?" .http_build_query($q)."&page=".($currpage+1)."'>></a>";
		else print " <a href='?page=".($currpage+1)."'>></a>";
	}
	print '</form>';
}
function render_hidden_inputs($array, $path = NULL)
{
	foreach ($array as $k => $v) {
		if (!is_array($v)) {
			// leaf node
			if ($path)
				$fullpath = $path.'['.$k.']';
			else
				$fullpath = $k;
			print "<input type='hidden' name='$fullpath' value='$v'>";
		}
		else {
			// directory node
			render_hidden_inputs($v, $path.$k);
		}
	}
}
function render_title($key)
{
	print '<tr><td>';
	print "<a href=view.php?key=$key>";

	// Print the important part of tags
	foreach (db_get_tags([$key])[$key] as $pair) {
		print ucwords($pair['Member']) . ' ';
	}
	// Output important properties by formatting them according to the
	// formatting specified in includes/database.php
	$class = db_getfileinfo($key)['Class'];
	$properties = db_getproperties($key);
	foreach ($properties as $p => $value) {
		$format = DB_FILE_EXTENDED_PROPERTIES[$class][$p]['Format'];
		if ($format) {
			print str_replace('?', $value, $format);
		}
	}
	print '</a>';
	print '</td></tr>';
}
function render_bargraph($data, $linkify = NULL)
{
	foreach($data as $label => $value) {
		if ($value > $max) $max = $value;
	}
	print '<div id="bargraph"><dl>';
	foreach($data as $label => $value) {
		$ratio = $value/$max;
		$width = $ratio*100 . "%";
		if (isset($linkify)) {
			$link = str_replace('{?}', urlencode($label), $linkify);
			print "<dt><a href='" . $link
			. "'>$label</a></dt>";
		}
		else {
			print "<dt>$label</dt>";
		}
		print "<dd style='width:$width;'>$value</dd>";
	}
	print '</dl></div>';
}
function render_colorbar($data)
{
	foreach ($data as $color => $value) {
		$total += $value;
	}
	print '<div class=colorbar>';
	foreach ($data as $color => $value) {
		$textcolor = getTextColor($color);
		$htmlname = resolve_color($color);
		$ratio = $value/$total;
		$width = $ratio*100 . "%";
		print "<div class=color style='width:$width;background-color:$color'>"
		. "<span class=colorname style='background-color:$color;color:$textcolor'>$htmlname ($color)</span>"
		. "&nbsp</div>";
	}
	print '</div>';
}
function render_search()
{
	print "<form id='search' action='" . CONFIG_HOOYA_WEBPATH . "browse.php'>"
	. "<div id='searchbox'>"
		. "<input id='query' type='search'"
		. "name='query' list='suggest-list' placeholder='search,terms'>"
		. "<datalist id='suggest-list'></datalist>"
	. "</div>"
	. "<div id='params'>"
	. "<section>"
	. "<div><input type='submit' value='Go！'></input></div>"
	. "</section>"
	. "<section id='filters'>"
	. "<div><select id='media_class'"
	. " name='media_class' onChange='changeExtAttrs(this.value)'>";
	render_classmenu();
	print "</select></div>";
	foreach (DB_MEDIA_CLASSES as $c => $more) {
		if ($more['Restricted'] && !logged_in() ) continue;
		$properties = DB_FILE_EXTENDED_PROPERTIES[$c];
		print "<div id='$c' style='display:none;'>";
		foreach ($properties as $p => $value) {
			print "<div><input";
			if ($value['Type']) {
				print " type='" . $value['Type'] . "'";
			}
			print " name='properties[$p]'"
			. " placeholder=$p"
			. " disabled></div>";
		}
		if ($more['Default'])
			print '<input type="hidden"'
			. ' name=' . $more['Default']
			. ' value=y'
			. '>';
		if ($properties['Width'] && $properties['Height'])
			print '<select name="properties[Ratio]">'
			. '<option value>Exact Dimensions</option>'
			. '<option value=ratio>Respect W:H Ratio</option>'
			. '</select>';
		print '</div>';
	}
	print "</section></div>"
	. "</form>";

	// Include the JS for search bars
	print "<script src='" . CONFIG_HOOYA_WEBPATH . "js/remote.js'></script>"
	. "<script src='" . CONFIG_HOOYA_WEBPATH . "js/search.js'></script>";


	print "<script> var classes = ";
	foreach (DB_MEDIA_CLASSES as $class => $property) {
		if ($property['Restricted'] && !logged_in()) continue;
		$classes[] = $class;
	}
	// Update the media class filter for its initial value
	print json_encode($classes) . ";"
	// On-submit hook to fine-tune searching and drop all empty form fields
	. "enhance_powersearch();"
	. "</script>";
}
function render_simple_search()
{
	print "<form id='search' action='" . CONFIG_HOOYA_WEBPATH . "browse.php'>"
	. "<div id=searchbox>"
		. "<input id='query' type='search' list='suggest-list'"
		. "name='query' placeholder='search,terms'>"
		. "<datalist id='suggest-list'></datalist>"
	. "</div>"
	. "<div><input type='submit' value='いこう！'></input></div>"
	// Include the JS for search bars
	. "<script src='" . CONFIG_HOOYA_WEBPATH . "js/remote.js'></script>"
	. "<script src='" . CONFIG_HOOYA_WEBPATH . "js/search.js'></script>"
	. "</form>";
}
function render_min_search($q = NULL)
{
	print "<form id='search' action='" . CONFIG_HOOYA_WEBPATH . "browse.php'>"
	. "<div id=searchbox>"
		. "<input id='query' value='$q' list='suggest-list'"
		. " name='query' placeholder='search,terms'>"
		. "<datalist id='suggest-list'></datalist>"
	. "</div>"
	// Include the JS for search bars
	. "<script src='" . CONFIG_HOOYA_WEBPATH . "js/remote.js'></script>"
	. "<script src='" . CONFIG_HOOYA_WEBPATH . "js/search.js'></script>"
	. "</form>";

}
function render_hooya_headers()
{
	$h = CONFIG_HOOYA_WEBPATH;
	print "<footer style='margin:auto;'>"
	. "<a href='$h'>Main</a>"
	. "<a href='$h" . "power.php'>Search</a>"
	. "<a href='$h" . "stats.php?overview'>Metrics</a>"
	. "<a href='$h" . "upload.php'>U/L</a>"
	. "<a href='$h" . "random.php?untagged&list'>Random</a>"
	. "</footer>";
}

/* Rendering properties from raw SQL data */
function render_colors($colors_sql)
{
	$colors = json_decode($colors_sql);
	print '<div class=colorbar>';
	foreach ($colors as $color) {
		$htmlname = resolve_color($color);
		$textcolor = getTextColor($color);

		$ratio = 1/count($colors);
		$width = $ratio*100 . "%";
		print "<div class=color style='width:$width;background-color:$color'>"
		. "<span class=colorname style='background-color:$color;color:$textcolor'>$htmlname ($color)</span>"
		. "&nbsp</div>";
	}
	print '</div>';
}

/* Misc. functions */
function getTextColor($hexcolor){
	$r = hexdec(substr($hexcolor,1,2));
	$g = hexdec(substr($hexcolor,3,2));
	$b = hexdec(substr($hexcolor,5,2));
	$yiq = (($r*299)+($g*587)+($b*114))/1000;
	return ($yiq >= 128) ? 'black' : 'white';
}
function resolve_color($color)
{
	$colors = [
	"Acid Green" => array(176, 191, 26),
	"Aero" => array(124, 185, 232),
	"Aero Blue" => array(201, 255, 229),
	"African Violet" => array(178, 132, 190),
	"Air Force Blue (RAF)" => array(93, 138, 168),
	"Air Force Blue (USAF)" => array(0, 48, 143),
	"Air Superiority Blue" => array(114, 160, 193),
	"Alabama Crimson" => array(175, 0, 42),
	"Alice Blue" => array(240, 248, 255),
	"Alizarin Crimson" => array(227, 38, 54),
	"Alloy Orange" => array(196, 98, 16),
	"Almond" => array(239, 222, 205),
	"Amaranth" => array(229, 43, 80),
	"Amaranth Deep Purple" => array(171, 39, 79),
	"Amaranth Pink" => array(241, 156, 187),
	"Amaranth Purple" => array(171, 39, 79),
	"Amaranth Red" => array(211, 33, 45),
	"Amazon" => array(59, 122, 87),
	"Amber" => array(255, 191, 0),
	"Amber (SAE/ECE)" => array(255, 126, 0),
	"American Rose" => array(255, 3, 62),
	"Amethyst" => array(153, 102, 204),
	"Android Green" => array(164, 198, 57),
	"Anti-Flash White" => array(242, 243, 244),
	"Antique Brass" => array(205, 149, 117),
	"Antique Bronze" => array(102, 93, 30),
	"Antique Fuchsia" => array(145, 92, 131),
	"Antique Ruby" => array(132, 27, 45),
	"Antique White" => array(250, 235, 215),
	"Ao (English)" => array(0, 128, 0),
	"Apple Green" => array(141, 182, 0),
	"Apricot" => array(251, 206, 177),
	"Aqua" => array(0, 255, 255),
	"Aquamarine" => array(127, 255, 212),
	"Arctic Lime" => array(208, 255, 20),
	"Army Green" => array(75, 83, 32),
	"Arsenic" => array(59, 68, 75),
	"Artichoke" => array(143, 151, 121),
	"Arylide Yellow" => array(233, 214, 107),
	"Ash Grey" => array(178, 190, 181),
	"Asparagus" => array(135, 169, 107),
	"Atomic Tangerine" => array(255, 153, 102),
	"Auburn" => array(165, 42, 42),
	"Aureolin" => array(253, 238, 0),
	"AuroMetalSaurus" => array(110, 127, 128),
	"Avocado" => array(86, 130, 3),
	"Azure" => array(0, 127, 255),
	"Azure (Web Color)" => array(240, 255, 255),
	"Azure Mist" => array(240, 255, 255),
	"Azureish White" => array(219, 233, 244),
	"Baby Blue" => array(137, 207, 240),
	"Baby Blue Eyes" => array(161, 202, 241),
	"Baby Pink" => array(244, 194, 194),
	"Baby Powder" => array(254, 254, 250),
	"Baker-Miller Pink" => array(255, 145, 175),
	"Ball Blue" => array(33, 171, 205),
	"Banana Mania" => array(250, 231, 181),
	"Banana Yellow" => array(255, 225, 53),
	"Bangladesh Green" => array(0, 106, 78),
	"Barbie Pink" => array(224, 33, 138),
	"Barn Red" => array(124, 10, 2),
	"Battleship Grey" => array(132, 132, 130),
	"Bazaar" => array(152, 119, 123),
	"Beau Blue" => array(188, 212, 230),
	"Beaver" => array(159, 129, 112),
	"Beige" => array(245, 245, 220),
	"B'dazzled Blue" => array(46, 88, 148),
	"Big Dip O’ruby" => array(156, 37, 66),
	"Bisque" => array(255, 228, 196),
	"Bistre" => array(61, 43, 31),
	"Bistre Brown" => array(150, 113, 23),
	"Bitter Lemon" => array(202, 224, 13),
	"Bitter Lime" => array(191, 255, 0),
	"Bittersweet" => array(254, 111, 94),
	"Bittersweet Shimmer" => array(191, 79, 81),
	"Black" => array(0, 0, 0),
	"Black Bean" => array(61, 12, 2),
	"Black Leather Jacket" => array(37, 53, 41),
	"Black Olive" => array(59, 60, 54),
	"Blanched Almond" => array(255, 235, 205),
	"Blast-Off Bronze" => array(165, 113, 100),
	"Bleu De France" => array(49, 140, 231),
	"Blizzard Blue" => array(172, 229, 238),
	"Blond" => array(250, 240, 190),
	"Blue" => array(0, 0, 255),
	"Blue (Crayola)" => array(31, 117, 254),
	"Blue (Munsell)" => array(0, 147, 175),
	"Blue (NCS)" => array(0, 135, 189),
	"Blue (Pantone)" => array(0, 24, 168),
	"Blue (Pigment)" => array(51, 51, 153),
	"Blue (RYB)" => array(2, 71, 254),
	"Blue Bell" => array(162, 162, 208),
	"Blue-Gray" => array(102, 153, 204),
	"Blue-Green" => array(13, 152, 186),
	"Blue Lagoon" => array(172, 229, 238),
	"Blue-Magenta Violet" => array(85, 53, 146),
	"Blue Sapphire" => array(18, 97, 128),
	"Blue-Violet" => array(138, 43, 226),
	"Blue Yonder" => array(80, 114, 167),
	"Blueberry" => array(79, 134, 247),
	"Bluebonnet" => array(28, 28, 240),
	"Blush" => array(222, 93, 131),
	"Bole" => array(121, 68, 59),
	"Bondi Blue" => array(0, 149, 182),
	"Bone" => array(227, 218, 201),
	"Boston University Red" => array(204, 0, 0),
	"Bottle Green" => array(0, 106, 78),
	"Boysenberry" => array(135, 50, 96),
	"Brandeis Blue" => array(0, 112, 255),
	"Brass" => array(181, 166, 66),
	"Brick Red" => array(203, 65, 84),
	"Bright Cerulean" => array(29, 172, 214),
	"Bright Green" => array(102, 255, 0),
	"Bright Lavender" => array(191, 148, 228),
	"Bright Lilac" => array(216, 145, 239),
	"Bright Maroon" => array(195, 33, 72),
	"Bright Navy Blue" => array(25, 116, 210),
	"Bright Pink" => array(255, 0, 127),
	"Bright Turquoise" => array(8, 232, 222),
	"Bright Ube" => array(209, 159, 232),
	"Brilliant Azure" => array(51, 153, 255),
	"Brilliant Lavender" => array(244, 187, 255),
	"Brilliant Rose" => array(255, 85, 163),
	"Brink Pink" => array(251, 96, 127),
	"British Racing Green" => array(0, 66, 37),
	"Bronze" => array(205, 127, 50),
	"Bronze Yellow" => array(115, 112, 0),
	"Brown (Traditional)" => array(150, 75, 0),
	"Brown (Web)" => array(165, 42, 42),
	"Brown-Nose" => array(107, 68, 35),
	"Brown Yellow" => array(204, 153, 102),
	"Brunswick Green" => array(27, 77, 62),
	"Bubble Gum" => array(255, 193, 204),
	"Bubbles" => array(231, 254, 255),
	"Buff" => array(240, 220, 130),
	"Bud Green" => array(123, 182, 97),
	"Bulgarian Rose" => array(72, 6, 7),
	"Burgundy" => array(128, 0, 32),
	"Burlywood" => array(222, 184, 135),
	"Burnt Orange" => array(204, 85, 0),
	"Burnt Sienna" => array(233, 116, 81),
	"Burnt Umber" => array(138, 51, 36),
	"Byzantine" => array(189, 51, 164),
	"Byzantium" => array(112, 41, 99),
	"Cadet" => array(83, 104, 114),
	"Cadet Blue" => array(95, 158, 160),
	"Cadet Grey" => array(145, 163, 176),
	"Cadmium Green" => array(0, 107, 60),
	"Cadmium Orange" => array(237, 135, 45),
	"Cadmium Red" => array(227, 0, 34),
	"Cadmium Yellow" => array(255, 246, 0),
	"Café Au Lait" => array(166, 123, 91),
	"Café Noir" => array(75, 54, 33),
	"Cal Poly Green" => array(30, 77, 43),
	"Cambridge Blue" => array(163, 193, 173),
	"Camel" => array(193, 154, 107),
	"Cameo Pink" => array(239, 187, 204),
	"Camouflage Green" => array(120, 134, 107),
	"Canary Yellow" => array(255, 239, 0),
	"Candy Apple Red" => array(255, 8, 0),
	"Candy Pink" => array(228, 113, 122),
	"Capri" => array(0, 191, 255),
	"Caput Mortuum" => array(89, 39, 32),
	"Cardinal" => array(196, 30, 58),
	"Caribbean Green" => array(0, 204, 153),
	"Carmine" => array(150, 0, 24),
	"Carmine (M&P)" => array(215, 0, 64),
	"Carmine Pink" => array(235, 76, 66),
	"Carmine Red" => array(255, 0, 56),
	"Carnation Pink" => array(255, 166, 201),
	"Carnelian" => array(179, 27, 27),
	"Carolina Blue" => array(86, 160, 211),
	"Carrot Orange" => array(237, 145, 33),
	"Castleton Green" => array(0, 86, 63),
	"Catalina Blue" => array(6, 42, 120),
	"Catawba" => array(112, 54, 66),
	"Cedar Chest" => array(201, 90, 73),
	"Ceil" => array(146, 161, 207),
	"Celadon" => array(172, 225, 175),
	"Celadon Blue" => array(0, 123, 167),
	"Celadon Green" => array(47, 132, 124),
	"Celeste" => array(178, 255, 255),
	"Celestial Blue" => array(73, 151, 208),
	"Cerise" => array(222, 49, 99),
	"Cerise Pink" => array(236, 59, 131),
	"Cerulean" => array(0, 123, 167),
	"Cerulean Blue" => array(42, 82, 190),
	"Cerulean Frost" => array(109, 155, 195),
	"CG Blue" => array(0, 122, 165),
	"CG Red" => array(224, 60, 49),
	"Chamoisee" => array(160, 120, 90),
	"Champagne" => array(247, 231, 206),
	"Charcoal" => array(54, 69, 79),
	"Charleston Green" => array(35, 43, 43),
	"Charm Pink" => array(230, 143, 172),
	"Chartreuse (Traditional)" => array(223, 255, 0),
	"Chartreuse (Web)" => array(127, 255, 0),
	"Cherry" => array(222, 49, 99),
	"Cherry Blossom Pink" => array(255, 183, 197),
	"Chestnut" => array(149, 69, 53),
	"China Pink" => array(222, 111, 161),
	"China Rose" => array(168, 81, 110),
	"Chinese Red" => array(170, 56, 30),
	"Chinese Violet" => array(133, 96, 136),
	"Chocolate (Traditional)" => array(123, 63, 0),
	"Chocolate (Web)" => array(210, 105, 30),
	"Chrome Yellow" => array(255, 167, 0),
	"Cinereous" => array(152, 129, 123),
	"Cinnabar" => array(227, 66, 52),
	"Cinnamon[Citation Needed]" => array(210, 105, 30),
	"Citrine" => array(228, 208, 10),
	"Citron" => array(159, 169, 31),
	"Claret" => array(127, 23, 52),
	"Classic Rose" => array(251, 204, 231),
	"Cobalt Blue" => array(0, 71, 171),
	"Cocoa Brown" => array(210, 105, 30),
	"Coconut" => array(150, 90, 62),
	"Coffee" => array(111, 78, 55),
	"Columbia Blue" => array(196, 216, 226),
	"Congo Pink" => array(248, 131, 121),
	"Cool Black" => array(0, 46, 99),
	"Cool Grey" => array(140, 146, 172),
	"Copper" => array(184, 115, 51),
	"Copper (Crayola)" => array(218, 138, 103),
	"Copper Penny" => array(173, 111, 105),
	"Copper Red" => array(203, 109, 81),
	"Copper Rose" => array(153, 102, 102),
	"Coquelicot" => array(255, 56, 0),
	"Coral" => array(255, 127, 80),
	"Coral Pink" => array(248, 131, 121),
	"Coral Red" => array(255, 64, 64),
	"Cordovan" => array(137, 63, 69),
	"Corn" => array(251, 236, 93),
	"Cornell Red" => array(179, 27, 27),
	"Cornflower Blue" => array(100, 149, 237),
	"Cornsilk" => array(255, 248, 220),
	"Cosmic Latte" => array(255, 248, 231),
	"Coyote Brown" => array(129, 97, 62),
	"Cotton Candy" => array(255, 188, 217),
	"Cream" => array(255, 253, 208),
	"Crimson" => array(220, 20, 60),
	"Crimson Glory" => array(190, 0, 50),
	"Crimson Red" => array(153, 0, 0),
	"Cyan" => array(0, 255, 255),
	"Cyan Azure" => array(78, 130, 180),
	"Cyan-Blue Azure" => array(70, 130, 191),
	"Cyan Cobalt Blue" => array(40, 88, 156),
	"Cyan Cornflower Blue" => array(24, 139, 194),
	"Cyan (Process)" => array(0, 183, 235),
	"Cyber Grape" => array(88, 66, 124),
	"Cyber Yellow" => array(255, 211, 0),
	"Daffodil" => array(255, 255, 49),
	"Dandelion" => array(240, 225, 48),
	"Dark Blue" => array(0, 0, 139),
	"Dark Blue-Gray" => array(102, 102, 153),
	"Dark Brown" => array(101, 67, 33),
	"Dark Brown-Tangelo" => array(136, 101, 78),
	"Dark Byzantium" => array(93, 57, 84),
	"Dark Candy Apple Red" => array(164, 0, 0),
	"Dark Cerulean" => array(8, 69, 126),
	"Dark Chestnut" => array(152, 105, 96),
	"Dark Coral" => array(205, 91, 69),
	"Dark Cyan" => array(0, 139, 139),
	"Dark Electric Blue" => array(83, 104, 120),
	"Dark Goldenrod" => array(184, 134, 11),
	"Dark Gray" => array(169, 169, 169),
	"Dark Green" => array(1, 50, 32),
	"Dark Green" => array(0, 100, 0),
	"Dark Gunmetal" => array(0, 100, 0),
	"Dark Imperial Blue" => array(110, 110, 249),
	"Dark Jungle Green" => array(26, 36, 33),
	"Dark Khaki" => array(189, 183, 107),
	"Dark Lava" => array(72, 60, 50),
	"Dark Lavender" => array(115, 79, 150),
	"Dark Liver" => array(83, 75, 79),
	"Dark Magenta" => array(139, 0, 139),
	"Dark Medium Gray" => array(169, 169, 169),
	"Dark Midnight Blue" => array(0, 51, 102),
	"Dark Moss Green" => array(74, 93, 35),
	"Dark Olive Green" => array(85, 107, 47),
	"Dark Orange" => array(255, 140, 0),
	"Dark Orchid" => array(153, 50, 204),
	"Dark Pastel Blue" => array(119, 158, 203),
	"Dark Pastel Green" => array(3, 192, 60),
	"Dark Pastel Purple" => array(150, 111, 214),
	"Dark Pastel Red" => array(194, 59, 34),
	"Dark Pink" => array(231, 84, 128),
	"Dark Powder Blue" => array(0, 51, 153),
	"Dark Puce" => array(79, 58, 60),
	"Dark Purple" => array(48, 25, 52),
	"Dark Raspberry" => array(135, 38, 87),
	"Dark Red" => array(139, 0, 0),
	"Dark Salmon" => array(233, 150, 122),
	"Dark Scarlet" => array(86, 3, 25),
	"Dark Sea Green" => array(143, 188, 143),
	"Dark Sienna" => array(60, 20, 20),
	"Dark Sky Blue" => array(140, 190, 214),
	"Dark Slate Blue" => array(72, 61, 139),
	"Dark Slate Gray" => array(47, 79, 79),
	"Dark Spring Green" => array(23, 114, 69),
	"Dark Tan" => array(145, 129, 81),
	"Dark Tangerine" => array(255, 168, 18),
	"Dark Taupe" => array(72, 60, 50),
	"Dark Terra Cotta" => array(204, 78, 92),
	"Dark Turquoise" => array(0, 206, 209),
	"Dark Vanilla" => array(209, 190, 168),
	"Dark Violet" => array(148, 0, 211),
	"Dark Yellow" => array(155, 135, 12),
	"Dartmouth Green" => array(0, 112, 60),
	"Davy's Grey" => array(85, 85, 85),
	"Debian Red" => array(215, 10, 83),
	"Deep Aquamarine" => array(64, 130, 109),
	"Deep Carmine" => array(169, 32, 62),
	"Deep Carmine Pink" => array(239, 48, 56),
	"Deep Carrot Orange" => array(233, 105, 44),
	"Deep Cerise" => array(218, 50, 135),
	"Deep Champagne" => array(250, 214, 165),
	"Deep Chestnut" => array(185, 78, 72),
	"Deep Coffee" => array(112, 66, 65),
	"Deep Fuchsia" => array(193, 84, 193),
	"Deep Green" => array(5, 102, 8),
	"Deep Green-Cyan Turquoise" => array(14, 124, 97),
	"Deep Jungle Green" => array(0, 75, 73),
	"Deep Koamaru" => array(51, 51, 102),
	"Deep Lemon" => array(245, 199, 26),
	"Deep Lilac" => array(153, 85, 187),
	"Deep Magenta" => array(204, 0, 204),
	"Deep Maroon" => array(130, 0, 0),
	"Deep Mauve" => array(212, 115, 212),
	"Deep Moss Green" => array(53, 94, 59),
	"Deep Peach" => array(255, 203, 164),
	"Deep Pink" => array(255, 20, 147),
	"Deep Puce" => array(169, 92, 104),
	"Deep Red" => array(133, 1, 1),
	"Deep Ruby" => array(132, 63, 91),
	"Deep Saffron" => array(255, 153, 51),
	"Deep Sky Blue" => array(0, 191, 255),
	"Deep Space Sparkle" => array(74, 100, 108),
	"Deep Spring Bud" => array(85, 107, 47),
	"Deep Taupe" => array(126, 94, 96),
	"Deep Tuscan Red" => array(102, 66, 77),
	"Deep Violet" => array(51, 0, 102),
	"Deer" => array(186, 135, 89),
	"Denim" => array(21, 96, 189),
	"Desaturated Cyan" => array(102, 153, 153),
	"Desert" => array(193, 154, 107),
	"Desert Sand" => array(237, 201, 175),
	"Desire" => array(234, 60, 83),
	"Diamond" => array(185, 242, 255),
	"Dim Gray" => array(105, 105, 105),
	"Dirt" => array(155, 118, 83),
	"Dodger Blue" => array(30, 144, 255),
	"Dogwood Rose" => array(215, 24, 104),
	"Dollar Bill" => array(133, 187, 101),
	"Donkey Brown" => array(102, 76, 40),
	"Drab" => array(150, 113, 23),
	"Duke Blue" => array(0, 0, 156),
	"Dust Storm" => array(229, 204, 201),
	"Dutch White" => array(239, 223, 187),
	"Earth Yellow" => array(225, 169, 95),
	"Ebony" => array(85, 93, 80),
	"Ecru" => array(194, 178, 128),
	"Eerie Black" => array(27, 27, 27),
	"Eggplant" => array(97, 64, 81),
	"Eggshell" => array(240, 234, 214),
	"Egyptian Blue" => array(16, 52, 166),
	"Electric Blue" => array(125, 249, 255),
	"Electric Crimson" => array(255, 0, 63),
	"Electric Cyan" => array(0, 255, 255),
	"Electric Green" => array(0, 255, 0),
	"Electric Indigo" => array(111, 0, 255),
	"Electric Lavender" => array(244, 187, 255),
	"Electric Lime" => array(204, 255, 0),
	"Electric Purple" => array(191, 0, 255),
	"Electric Ultramarine" => array(63, 0, 255),
	"Electric Violet" => array(143, 0, 255),
	"Electric Yellow" => array(255, 255, 51),
	"Emerald" => array(80, 200, 120),
	"Eminence" => array(108, 48, 130),
	"English Green" => array(27, 77, 62),
	"English Lavender" => array(180, 131, 149),
	"English Red" => array(171, 75, 82),
	"English Violet" => array(86, 60, 92),
	"Eton Blue" => array(150, 200, 162),
	"Eucalyptus" => array(68, 215, 168),
	"Fallow" => array(193, 154, 107),
	"Falu Red" => array(128, 24, 24),
	"Fandango" => array(181, 51, 137),
	"Fandango Pink" => array(222, 82, 133),
	"Fashion Fuchsia" => array(244, 0, 161),
	"Fawn" => array(229, 170, 112),
	"Feldgrau" => array(77, 93, 83),
	"Feldspar" => array(253, 213, 177),
	"Fern Green" => array(79, 121, 66),
	"Ferrari Red" => array(255, 40, 0),
	"Field Drab" => array(108, 84, 30),
	"Firebrick" => array(178, 34, 34),
	"Fire Engine Red" => array(206, 32, 41),
	"Flame" => array(226, 88, 34),
	"Flamingo Pink" => array(252, 142, 172),
	"Flattery" => array(107, 68, 35),
	"Flavescent" => array(247, 233, 142),
	"Flax" => array(238, 220, 130),
	"Flirt" => array(162, 0, 109),
	"Floral White" => array(255, 250, 240),
	"Fluorescent Orange" => array(255, 191, 0),
	"Fluorescent Pink" => array(255, 20, 147),
	"Fluorescent Yellow" => array(204, 255, 0),
	"Folly" => array(255, 0, 79),
	"Forest Green (Traditional)" => array(1, 68, 33),
	"Forest Green (Web)" => array(34, 139, 34),
	"French Beige" => array(166, 123, 91),
	"French Bistre" => array(133, 109, 77),
	"French Blue" => array(0, 114, 187),
	"French Fuchsia" => array(253, 63, 146),
	"French Lilac" => array(134, 96, 142),
	"French Lime" => array(158, 253, 56),
	"French Mauve" => array(212, 115, 212),
	"French Pink" => array(253, 108, 158),
	"French Plum" => array(129, 20, 83),
	"French Puce" => array(78, 22, 9),
	"French Raspberry" => array(199, 44, 72),
	"French Rose" => array(246, 74, 138),
	"French Sky Blue" => array(119, 181, 254),
	"French Violet" => array(136, 6, 206),
	"French Wine" => array(172, 30, 68),
	"Fresh Air" => array(166, 231, 255),
	"Fuchsia" => array(255, 0, 255),
	"Fuchsia (Crayola)" => array(193, 84, 193),
	"Fuchsia Pink" => array(255, 119, 255),
	"Fuchsia Purple" => array(204, 57, 123),
	"Fuchsia Rose" => array(199, 67, 117),
	"Fulvous" => array(228, 132, 0),
	"Fuzzy Wuzzy" => array(204, 102, 102),
	"Gainsboro" => array(220, 220, 220),
	"Gamboge" => array(228, 155, 15),
	"Gamboge Orange (Brown)" => array(153, 102, 0),
	"Generic Viridian" => array(0, 127, 102),
	"Ghost White" => array(248, 248, 255),
	"Giants Orange" => array(254, 90, 29),
	"Ginger" => array(176, 101, 0),
	"Glaucous" => array(96, 130, 182),
	"Glitter" => array(230, 232, 250),
	"GO Green" => array(0, 171, 102),
	"Metallic Gold" => array(212, 175, 55),
	"Gold" => array(255, 215, 0),
	"Gold Fusion" => array(133, 117, 78),
	"Golden Brown" => array(153, 101, 21),
	"Golden Poppy" => array(252, 194, 0),
	"Golden Yellow" => array(255, 223, 0),
	"Goldenrod" => array(218, 165, 32),
	"Granny Smith Apple" => array(168, 228, 160),
	"Grape" => array(111, 45, 168),
	"Gray" => array(128, 128, 128),
	"Gray-Asparagus" => array(70, 89, 69),
	"Gray-Blue" => array(140, 146, 172),
	"Green" => array(0, 255, 0),
	"Crayola Green" => array(28, 172, 120),
	"Green-Blue" => array(17, 100, 180),
	"Green-Cyan" => array(0, 153, 102),
	"Green-Yellow" => array(173, 255, 47),
	"Grizzly" => array(136, 88, 24),
	"Grullo" => array(169, 154, 134),
	"Guppie Green" => array(0, 255, 127),
	"Gunmetal" => array(42, 52, 57),
	"Halayà Úbe" => array(102, 56, 84),
	"Han Blue" => array(68, 108, 207),
	"Han Purple" => array(82, 24, 250),
	"Hansa Yellow" => array(233, 214, 107),
	"Harlequin" => array(63, 255, 0),
	"Harlequin Green" => array(70, 203, 24),
	"Harvard Crimson" => array(201, 0, 22),
	"Harvest Gold" => array(218, 145, 0),
	"Heart Gold" => array(128, 128, 0),
	"Heliotrope" => array(223, 115, 255),
	"Heliotrope Gray" => array(170, 152, 169),
	"Heliotrope Magenta" => array(170, 0, 187),
	"Hollywood Cerise" => array(244, 0, 161),
	"Honeydew" => array(240, 255, 240),
	"Honolulu Blue" => array(0, 109, 176),
	"Hooker's Green" => array(73, 121, 107),
	"Hot Magenta" => array(255, 29, 206),
	"Hot Pink" => array(255, 105, 180),
	"Hunter Green" => array(53, 94, 59),
	"Iceberg" => array(113, 166, 210),
	"Icterine" => array(252, 247, 94),
	"Illuminating Emerald" => array(49, 145, 119),
	"Imperial" => array(96, 47, 107),
	"Imperial Blue" => array(0, 35, 149),
	"Imperial Purple" => array(102, 2, 60),
	"Imperial Red" => array(237, 41, 57),
	"Inchworm" => array(178, 236, 93),
	"Independence" => array(76, 81, 109),
	"India Green" => array(19, 136, 8),
	"Indian Red" => array(205, 92, 92),
	"Indian Yellow" => array(227, 168, 87),
	"Indigo" => array(75, 0, 130),
	"Indigo Dye" => array(9, 31, 146),
	"International Klein Blue" => array(0, 47, 167),
	"Iris" => array(90, 79, 207),
	"Irresistible" => array(179, 68, 108),
	"Isabelline" => array(244, 240, 236),
	"Islamic Green" => array(0, 144, 0),
	"Italian Sky Blue" => array(178, 255, 255),
	"Ivory" => array(255, 255, 240),
	"Jade" => array(0, 168, 107),
	"Japanese Carmine" => array(157, 41, 51),
	"Japanese Indigo" => array(38, 67, 72),
	"Japanese Violet" => array(91, 50, 86),
	"Jasmine" => array(248, 222, 126),
	"Jasper" => array(215, 59, 62),
	"Jazzberry Jam" => array(165, 11, 94),
	"Jelly Bean" => array(218, 97, 78),
	"Jet" => array(52, 52, 52),
	"Jonquil" => array(244, 202, 22),
	"Jordy Blue" => array(138, 185, 241),
	"June Bud" => array(189, 218, 87),
	"Jungle Green" => array(41, 171, 135),
	"Kelly Green" => array(76, 187, 23),
	"Kenyan Copper" => array(124, 28, 5),
	"Keppel" => array(58, 176, 158),
	"Khaki" => array(195, 176, 145),
	"Light Khaki" => array(240, 230, 140),
	"Kobe" => array(136, 45, 23),
	"Kobi" => array(231, 159, 196),
	"Kobicha" => array(107, 68, 35),
	"Kombu Green" => array(53, 66, 48),
	"KU Crimson" => array(232, 0, 13),
	"La Salle Green" => array(8, 120, 48),
	"Languid Lavender" => array(214, 202, 221),
	"Lapis Lazuli" => array(38, 97, 156),
	"Laser Lemon" => array(255, 255, 102),
	"Laurel Green" => array(169, 186, 157),
	"Lava" => array(207, 16, 32),
	"Lavender" => array(230, 230, 250),
	"Lavender Blue" => array(204, 204, 255),
	"Lavender Blush" => array(255, 240, 245),
	"Lavender Gray" => array(196, 195, 208),
	"Lavender Indigo" => array(148, 87, 235),
	"Lavender Magenta" => array(238, 130, 238),
	"Lavender Mist" => array(230, 230, 250),
	"Lavender Pink" => array(251, 174, 210),
	"Lavender Purple" => array(150, 123, 182),
	"Lavender Rose" => array(251, 160, 227),
	"Lawn Green" => array(124, 252, 0),
	"Lemon" => array(255, 247, 0),
	"Lemon Chiffon" => array(255, 250, 205),
	"Lemon Curry" => array(204, 160, 29),
	"Lemon Glacier" => array(253, 255, 0),
	"Lemon Lime" => array(227, 255, 0),
	"Lemon Meringue" => array(246, 234, 190),
	"Lemon Yellow" => array(255, 244, 79),
	"Lenurple" => array(186, 147, 216),
	"Licorice" => array(26, 17, 16),
	"Liberty" => array(84, 90, 167),
	"Light Apricot" => array(253, 213, 177),
	"Light Blue" => array(173, 216, 230),
	"Light Brilliant Red" => array(254, 46, 46),
	"Light Brown" => array(181, 101, 29),
	"Light Carmine Pink" => array(230, 103, 113),
	"Light Cobalt Blue" => array(136, 172, 224),
	"Light Coral" => array(240, 128, 128),
	"Light Cornflower Blue" => array(147, 204, 234),
	"Light Crimson" => array(245, 105, 145),
	"Light Cyan" => array(224, 255, 255),
	"Light Deep Pink" => array(255, 92, 205),
	"Light French Beige" => array(200, 173, 127),
	"Light Fuchsia Pink" => array(249, 132, 239),
	"Light Goldenrod Yellow" => array(250, 250, 210),
	"Light Gray" => array(211, 211, 211),
	"Light Grayish Magenta" => array(204, 153, 204),
	"Light Green" => array(144, 238, 144),
	"Light Hot Pink" => array(255, 179, 222),
	"Light Khaki" => array(240, 230, 140),
	"Light Medium Orchid" => array(211, 155, 203),
	"Light Moss Green" => array(173, 223, 173),
	"Light Orchid" => array(230, 168, 215),
	"Light Pastel Purple" => array(177, 156, 217),
	"Light Pink" => array(255, 182, 193),
	"Light Red Ochre" => array(233, 116, 81),
	"Light Salmon" => array(255, 160, 122),
	"Light Salmon Pink" => array(255, 153, 153),
	"Light Sea Green" => array(32, 178, 170),
	"Light Sky Blue" => array(135, 206, 250),
	"Light Slate Gray" => array(119, 136, 153),
	"Light Steel Blue" => array(176, 196, 222),
	"Light Taupe" => array(179, 139, 109),
	"Light Thulian Pink" => array(230, 143, 172),
	"Light Yellow" => array(255, 255, 224),
	"Lilac" => array(200, 162, 200),
	"Lime" => array(191, 255, 0),
	"Lime Green" => array(50, 205, 50),
	"Limerick" => array(157, 194, 9),
	"Lincoln Green" => array(25, 89, 5),
	"Linen" => array(250, 240, 230),
	"Lion" => array(193, 154, 107),
	"Liseran Purple" => array(222, 111, 161),
	"Little Boy Blue" => array(108, 160, 220),
	"Liver" => array(103, 76, 71),
	"Liver (Dogs)" => array(184, 109, 41),
	"Liver (Organ)" => array(108, 46, 31),
	"Liver Chestnut" => array(152, 116, 86),
	"Livid" => array(102, 153, 204),
	"Lumber" => array(255, 228, 205),
	"Lust" => array(230, 32, 32),
	"Macaroni And Cheese" => array(255, 189, 136),
	"Magenta" => array(255, 0, 255),
	"Crayola Magenta" => array(255, 85, 163),
	"Magenta" => array(202, 31, 123),
	"Magenta Haze" => array(159, 69, 118),
	"Magenta-Pink" => array(204, 51, 139),
	"Magic Mint" => array(170, 240, 209),
	"Magnolia" => array(248, 244, 255),
	"Mahogany" => array(192, 64, 0),
	"Maize" => array(251, 236, 93),
	"Majorelle Blue" => array(96, 80, 220),
	"Malachite" => array(11, 218, 81),
	"Manatee" => array(151, 154, 170),
	"Mango Tango" => array(255, 130, 67),
	"Mantis" => array(116, 195, 101),
	"Mardi Gras" => array(136, 0, 133),
	"Marigold" => array(234, 162, 33),
	"Maroon (Crayola)" => array(195, 33, 72),
	"Maroon" => array(176, 48, 96),
	"Mauve" => array(224, 176, 255),
	"Mauve Taupe" => array(145, 95, 109),
	"Mauvelous" => array(239, 152, 170),
	"May Green" => array(76, 145, 65),
	"Maya Blue" => array(115, 194, 251),
	"Meat Brown" => array(229, 183, 59),
	"Medium Aquamarine" => array(102, 221, 170),
	"Medium Blue" => array(0, 0, 205),
	"Medium Candy Apple Red" => array(226, 6, 44),
	"Medium Carmine" => array(175, 64, 53),
	"Medium Champagne" => array(243, 229, 171),
	"Medium Electric Blue" => array(3, 80, 150),
	"Medium Jungle Green" => array(28, 53, 45),
	"Medium Lavender Magenta" => array(221, 160, 221),
	"Medium Orchid" => array(186, 85, 211),
	"Medium Persian Blue" => array(0, 103, 165),
	"Medium Purple" => array(147, 112, 219),
	"Medium Red-Violet" => array(187, 51, 133),
	"Medium Ruby" => array(170, 64, 105),
	"Medium Sea Green" => array(60, 179, 113),
	"Medium Sky Blue" => array(128, 218, 235),
	"Medium Slate Blue" => array(123, 104, 238),
	"Medium Spring Bud" => array(201, 220, 135),
	"Medium Spring Green" => array(0, 250, 154),
	"Medium Taupe" => array(103, 76, 71),
	"Medium Turquoise" => array(72, 209, 204),
	"Medium Tuscan Red" => array(121, 68, 59),
	"Medium Vermilion" => array(217, 96, 59),
	"Medium Violet-Red" => array(199, 21, 133),
	"Mellow Apricot" => array(248, 184, 120),
	"Mellow Yellow" => array(248, 222, 126),
	"Melon" => array(253, 188, 180),
	"Metallic Seaweed" => array(10, 126, 140),
	"Metallic Sunburst" => array(156, 124, 56),
	"Mexican Pink" => array(228, 0, 124),
	"Midnight Blue" => array(25, 25, 112),
	"Midnight Green (Eagle Green)" => array(0, 73, 83),
	"Mikado Yellow" => array(255, 196, 12),
	"Mindaro" => array(227, 249, 136),
	"Ming" => array(54, 116, 125),
	"Mint" => array(62, 180, 137),
	"Mint Cream" => array(245, 255, 250),
	"Mint Green" => array(152, 255, 152),
	"Misty Rose" => array(255, 228, 225),
	"Moccasin" => array(250, 235, 215),
	"Mode Beige" => array(150, 113, 23),
	"Moonstone Blue" => array(115, 169, 194),
	"Mordant Red 19" => array(174, 12, 0),
	"Moss Green" => array(138, 154, 91),
	"Mountain Meadow" => array(48, 186, 143),
	"Mountbatten Pink" => array(153, 122, 141),
	"MSU Green" => array(24, 69, 59),
	"Mughal Green" => array(48, 96, 48),
	"Mulberry" => array(197, 75, 140),
	"Mustard" => array(255, 219, 88),
	"Myrtle Green" => array(49, 120, 115),
	"Nadeshiko Pink" => array(246, 173, 198),
	"Napier Green" => array(42, 128, 0),
	"Naples Yellow" => array(250, 218, 94),
	"Navajo White" => array(255, 222, 173),
	"Navy" => array(0, 0, 128),
	"Navy Purple" => array(148, 87, 235),
	"Neon Carrot" => array(255, 163, 67),
	"Neon Fuchsia" => array(254, 65, 100),
	"Neon Green" => array(57, 255, 20),
	"New Car" => array(33, 79, 198),
	"New York Pink" => array(215, 131, 127),
	"Non-Photo Blue" => array(164, 221, 237),
	"North Texas Green" => array(5, 144, 51),
	"Nyanza" => array(233, 255, 219),
	"Ocean Boat Blue" => array(0, 119, 190),
	"Ochre" => array(204, 119, 34),
	"Office Green" => array(0, 128, 0),
	"Old Burgundy" => array(67, 48, 46),
	"Old Gold" => array(207, 181, 59),
	"Old Heliotrope" => array(86, 60, 92),
	"Old Lace" => array(253, 245, 230),
	"Old Lavender" => array(121, 104, 120),
	"Old Mauve" => array(103, 49, 71),
	"Old Moss Green" => array(134, 126, 54),
	"Old Rose" => array(192, 128, 129),
	"Old Silver" => array(132, 132, 130),
	"Olive" => array(128, 128, 0),
	"Olive Drab (" => array(107, 142, 35),
	"Olive Drab" => array(60, 52, 31),
	"Olivine" => array(154, 185, 115),
	"Onyx" => array(53, 56, 57),
	"Opera Mauve" => array(183, 132, 167),
	"Orange" => array(255, 127, 0),
	"Orange Peel" => array(255, 159, 0),
	"Orange-Red" => array(255, 69, 0),
	"Orange-Yellow" => array(248, 213, 104),
	"Orchid" => array(218, 112, 214),
	"Orchid Pink" => array(242, 189, 205),
	"Orioles Orange" => array(251, 79, 20),
	"Otter Brown" => array(101, 67, 33),
	"Outer Space" => array(65, 74, 76),
	"Outrageous Orange" => array(255, 110, 74),
	"Oxford Blue" => array(0, 33, 71),
	"OU Crimson Red" => array(153, 0, 0),
	"Pacific Blue" => array(28, 169, 201),
	"Pakistan Green" => array(0, 102, 0),
	"Palatinate Blue" => array(39, 59, 226),
	"Palatinate Purple" => array(104, 40, 96),
	"Pale Aqua" => array(188, 212, 230),
	"Pale Blue" => array(175, 238, 238),
	"Pale Brown" => array(152, 118, 84),
	"Pale Carmine" => array(175, 64, 53),
	"Pale Cerulean" => array(155, 196, 226),
	"Pale Chestnut" => array(221, 173, 175),
	"Pale Copper" => array(218, 138, 103),
	"Pale Cornflower Blue" => array(171, 205, 239),
	"Pale Cyan" => array(135, 211, 248),
	"Pale Gold" => array(230, 190, 138),
	"Pale Goldenrod" => array(238, 232, 170),
	"Pale Green" => array(152, 251, 152),
	"Pale Lavender" => array(220, 208, 255),
	"Pale Magenta" => array(249, 132, 229),
	"Pale Magenta-Pink" => array(255, 153, 204),
	"Pale Pink" => array(250, 218, 221),
	"Pale Plum" => array(221, 160, 221),
	"Pale Red-Violet" => array(219, 112, 147),
	"Pale Robin Egg Blue" => array(150, 222, 209),
	"Pale Silver" => array(201, 192, 187),
	"Pale Spring Bud" => array(236, 235, 189),
	"Pale Taupe" => array(188, 152, 126),
	"Pale Turquoise" => array(175, 238, 238),
	"Pale Violet" => array(204, 153, 255),
	"Pale Violet-Red" => array(219, 112, 147),
	"Pansy Purple" => array(120, 24, 74),
	"Paolo Veronese Green" => array(0, 155, 125),
	"Papaya Whip" => array(255, 239, 213),
	"Paradise Pink" => array(230, 62, 98),
	"Paris Green" => array(80, 200, 120),
	"Pastel Blue" => array(174, 198, 207),
	"Pastel Brown" => array(131, 105, 83),
	"Pastel Gray" => array(207, 207, 196),
	"Pastel Green" => array(119, 221, 119),
	"Pastel Magenta" => array(244, 154, 194),
	"Pastel Orange" => array(255, 179, 71),
	"Pastel Pink" => array(222, 165, 164),
	"Pastel Purple" => array(179, 158, 181),
	"Pastel Red" => array(255, 105, 97),
	"Pastel Violet" => array(203, 153, 201),
	"Pastel Yellow" => array(253, 253, 150),
	"Patriarch" => array(128, 0, 128),
	"Payne's Grey" => array(83, 104, 120),
	"Peach" => array(255, 203, 164),
	"Peach-Orange" => array(255, 204, 153),
	"Peach Puff" => array(255, 218, 185),
	"Peach-Yellow" => array(250, 223, 173),
	"Pear" => array(209, 226, 49),
	"Pearl" => array(234, 224, 200),
	"Pearl Aqua" => array(136, 216, 192),
	"Pearly Purple" => array(183, 104, 162),
	"Peridot" => array(230, 226, 0),
	"Periwinkle" => array(204, 204, 255),
	"Permanent Geranium Lake" => array(225, 44, 44),
	"Persian Blue" => array(28, 57, 187),
	"Persian Green" => array(0, 166, 147),
	"Persian Indigo" => array(50, 18, 122),
	"Persian Orange" => array(217, 144, 88),
	"Persian Pink" => array(247, 127, 190),
	"Persian Plum" => array(112, 28, 28),
	"Persian Red" => array(204, 51, 51),
	"Persian Rose" => array(254, 40, 162),
	"Persimmon" => array(236, 88, 0),
	"Peru" => array(205, 133, 63),
	"Phlox" => array(223, 0, 255),
	"Phthalo Blue" => array(0, 15, 137),
	"Phthalo Green" => array(18, 53, 36),
	"Picton Blue" => array(69, 177, 232),
	"Pictorial Carmine" => array(195, 11, 78),
	"Piggy Pink" => array(253, 221, 230),
	"Pine Green" => array(1, 121, 111),
	"Pineapple" => array(86, 60, 92),
	"Pink" => array(255, 192, 203),
	"Pink Flamingo" => array(252, 116, 253),
	"Pink Lace" => array(255, 221, 244),
	"Pink Lavender" => array(216, 178, 209),
	"Pink-Orange" => array(255, 153, 102),
	"Pink Pearl" => array(231, 172, 207),
	"Pink Raspberry" => array(152, 0, 54),
	"Pink Sherbet" => array(247, 143, 167),
	"Pistachio" => array(147, 197, 114),
	"Platinum" => array(229, 228, 226),
	"Plum" => array(142, 69, 133),
	"Pomp And Power" => array(134, 96, 142),
	"Popstar" => array(190, 79, 98),
	"Portland Orange" => array(255, 90, 54),
	"Powder Blue" => array(176, 224, 230),
	"Princeton Orange" => array(245, 128, 37),
	"Prune" => array(112, 28, 28),
	"Prussian Blue" => array(0, 49, 83),
	"Psychedelic Purple" => array(223, 0, 255),
	"Puce" => array(204, 136, 153),
	"Puce Red" => array(114, 47, 55),
	"Pullman Brown" => array(100, 65, 23),
	"Pullman Green" => array(59, 51, 28),
	"Pumpkin" => array(255, 117, 24),
	"Purple" => array(160, 32, 240),
	"Purple Heart" => array(105, 53, 156),
	"Purple Mountain Majesty" => array(150, 120, 182),
	"Purple Navy" => array(78, 81, 128),
	"Purple Pizzazz" => array(254, 78, 218),
	"Purple Taupe" => array(80, 64, 77),
	"Purpureus" => array(154, 78, 174),
	"Quartz" => array(81, 72, 79),
	"Queen Blue" => array(67, 107, 149),
	"Queen Pink" => array(232, 204, 215),
	"Quinacridone Magenta" => array(142, 58, 89),
	"Rackley" => array(93, 138, 168),
	"Radical Red" => array(255, 53, 94),
	"Raisin Black" => array(36, 33, 36),
	"Rajah" => array(251, 171, 96),
	"Raspberry" => array(227, 11, 93),
	"Raspberry Glace" => array(145, 95, 109),
	"Raspberry Pink" => array(226, 80, 152),
	"Raspberry Rose" => array(179, 68, 108),
	"Raw Sienna" => array(214, 138, 89),
	"Raw Umber" => array(130, 102, 68),
	"Razzle Dazzle Rose" => array(255, 51, 204),
	"Razzmatazz" => array(227, 37, 107),
	"Razzmic Berry" => array(141, 78, 133),
	"Rebecca Purple" => array(102, 51, 153),
	"Red" => array(255, 0, 0),
	"Crayola Red" => array(238, 32, 77),
	"Red-Brown" => array(165, 42, 42),
	"Red Devil" => array(134, 1, 17),
	"Red-Orange" => array(255, 83, 73),
	"Red-Purple" => array(228, 0, 120),
	"Red-Violet" => array(199, 21, 133),
	"Redwood" => array(164, 90, 82),
	"Regalia" => array(82, 45, 128),
	"Registration Black" => array(0, 0, 0),
	"Resolution Blue" => array(0, 35, 135),
	"Rhythm" => array(119, 118, 150),
	"Rich Black" => array(0, 64, 64),
	"Rich Brilliant Lavender" => array(241, 167, 254),
	"Rich Carmine" => array(215, 0, 64),
	"Rich Electric Blue" => array(8, 146, 208),
	"Rich Lavender" => array(167, 107, 207),
	"Rich Lilac" => array(182, 102, 210),
	"Rich Maroon" => array(176, 48, 96),
	"Rifle Green" => array(68, 76, 56),
	"Roast Coffee" => array(112, 66, 65),
	"Robin Egg Blue" => array(0, 204, 204),
	"Rocket Metallic" => array(138, 127, 128),
	"Roman Silver" => array(131, 137, 150),
	"Rose" => array(255, 0, 127),
	"Rose Bonbon" => array(249, 66, 158),
	"Rose Ebony" => array(103, 72, 70),
	"Rose Gold" => array(183, 110, 121),
	"Rose Madder" => array(227, 38, 54),
	"Rose Pink" => array(255, 102, 204),
	"Rose Quartz" => array(170, 152, 169),
	"Rose Red" => array(194, 30, 86),
	"Rose Taupe" => array(144, 93, 93),
	"Rose Vale" => array(171, 78, 82),
	"Rosewood" => array(101, 0, 11),
	"Rosso Corsa" => array(212, 0, 0),
	"Rosy Brown" => array(188, 143, 143),
	"Royal Azure" => array(0, 56, 168),
	"Royal Blue" => array(65, 105, 225),
	"Royal Fuchsia" => array(202, 44, 146),
	"Royal Purple" => array(120, 81, 169),
	"Royal Yellow" => array(250, 218, 94),
	"Ruber" => array(206, 70, 118),
	"Rubine Red" => array(209, 0, 86),
	"Ruby" => array(224, 17, 95),
	"Ruby Red" => array(155, 17, 30),
	"Ruddy" => array(255, 0, 40),
	"Ruddy Brown" => array(187, 101, 40),
	"Ruddy Pink" => array(225, 142, 150),
	"Rufous" => array(168, 28, 7),
	"Russet" => array(128, 70, 27),
	"Russian Green" => array(103, 146, 103),
	"Russian Violet" => array(50, 23, 77),
	"Rust" => array(183, 65, 14),
	"Rusty Red" => array(218, 44, 67),
	"Sacramento State Green" => array(0, 86, 63),
	"Saddle Brown" => array(139, 69, 19),
	"Safety Orange" => array(255, 120, 0),
	"Blaze Orange" => array(255, 103, 0),
	"Safety Yellow" => array(238, 210, 2),
	"Saffron" => array(244, 196, 48),
	"Sage" => array(188, 184, 138),
	"St. Patrick's Blue" => array(35, 41, 122),
	"Salmon" => array(250, 128, 114),
	"Salmon Pink" => array(255, 145, 164),
	"Sand" => array(194, 178, 128),
	"Sand Dune" => array(150, 113, 23),
	"Sandstorm" => array(236, 213, 64),
	"Sandy Brown" => array(244, 164, 96),
	"Sandy Taupe" => array(150, 113, 23),
	"Sangria" => array(146, 0, 10),
	"Sap Green" => array(80, 125, 42),
	"Sapphire" => array(15, 82, 186),
	"Sapphire Blue" => array(0, 103, 165),
	"Satin Sheen Gold" => array(203, 161, 53),
	"Scarlet" => array(253, 14, 53),
	"Schauss Pink" => array(255, 145, 175),
	"School Bus Yellow" => array(255, 216, 0),
	"Screamin' Green" => array(118, 255, 122),
	"Sea Blue" => array(0, 105, 148),
	"Sea Green" => array(46, 139, 87),
	"Seal Brown" => array(89, 38, 11),
	"Seashell" => array(255, 245, 238),
	"Selective Yellow" => array(255, 186, 0),
	"Sepia" => array(112, 66, 20),
	"Shadow" => array(138, 121, 93),
	"Shadow Blue" => array(119, 139, 165),
	"Shampoo" => array(255, 207, 241),
	"Shamrock Green" => array(0, 158, 96),
	"Sheen Green" => array(143, 212, 0),
	"Shimmering Blush" => array(217, 134, 149),
	"Shocking Pink" => array(252, 15, 192),
	"Shocking Pink (Crayola)" => array(255, 111, 255),
	"Sienna" => array(136, 45, 23),
	"Silver" => array(192, 192, 192),
	"Silver Chalice" => array(172, 172, 172),
	"Silver Lake Blue" => array(93, 137, 186),
	"Silver Pink" => array(196, 174, 173),
	"Silver Sand" => array(191, 193, 194),
	"Sinopia" => array(203, 65, 11),
	"Skobeloff" => array(0, 116, 116),
	"Sky Blue" => array(135, 206, 235),
	"Sky Magenta" => array(207, 113, 175),
	"Slate Blue" => array(106, 90, 205),
	"Slate Gray" => array(112, 128, 144),
	"Smalt" => array(0, 51, 153),
	"Smitten" => array(200, 65, 134),
	"Smoke" => array(115, 130, 118),
	"Smoky Black" => array(16, 12, 8),
	"Smoky Topaz" => array(147, 61, 65),
	"Snow" => array(255, 250, 250),
	"Soap" => array(206, 200, 239),
	"Solid Pink" => array(137, 56, 67),
	"Sonic Silver" => array(117, 117, 117),
	"Spartan Crimson" => array(158, 19, 22),
	"Space Cadet" => array(29, 41, 81),
	"Spanish Bistre" => array(128, 117, 50),
	"Spanish Blue" => array(0, 112, 184),
	"Spanish Carmine" => array(209, 0, 71),
	"Spanish Crimson" => array(229, 26, 76),
	"Spanish Gray" => array(152, 152, 152),
	"Spanish Green" => array(0, 145, 80),
	"Spanish Orange" => array(232, 97, 0),
	"Spanish Pink" => array(247, 191, 190),
	"Spanish Red" => array(230, 0, 38),
	"Spanish Sky Blue" => array(0, 255, 255),
	"Spanish Violet" => array(76, 40, 130),
	"Spanish Viridian" => array(0, 127, 92),
	"Spicy Mix" => array(139, 95, 77),
	"Spiro Disco Ball" => array(15, 192, 252),
	"Spring Bud" => array(167, 252, 0),
	"Spring Green" => array(0, 255, 127),
	"Star Command Blue" => array(0, 123, 184),
	"Steel Blue" => array(70, 130, 180),
	"Steel Pink" => array(204, 51, 204),
	"Stil De Grain Yellow" => array(250, 218, 94),
	"Stizza" => array(153, 0, 0),
	"Stormcloud" => array(79, 102, 106),
	"Straw" => array(228, 217, 111),
	"Strawberry" => array(252, 90, 141),
	"Sunglow" => array(255, 204, 51),
	"Sunray" => array(227, 171, 87),
	"Sunset" => array(250, 214, 165),
	"Sunset Orange" => array(253, 94, 83),
	"Super Pink" => array(207, 107, 169),
	"Tan" => array(210, 180, 140),
	"Tangelo" => array(249, 77, 0),
	"Tangerine" => array(242, 133, 0),
	"Tangerine Yellow" => array(255, 204, 0),
	"Tango Pink" => array(228, 113, 122),
	"Taupe" => array(72, 60, 50),
	"Taupe Gray" => array(139, 133, 137),
	"Tea Green" => array(208, 240, 192),
	"Tea Rose" => array(244, 194, 194),
	"Teal" => array(0, 128, 128),
	"Teal Blue" => array(54, 117, 136),
	"Teal Deer" => array(153, 230, 179),
	"Teal Green" => array(0, 130, 127),
	"Telemagenta" => array(207, 52, 118),
	"Tenné" => array(205, 87, 0),
	"Terra Cotta" => array(226, 114, 91),
	"Thistle" => array(216, 191, 216),
	"Thulian Pink" => array(222, 111, 161),
	"Tickle Me Pink" => array(252, 137, 172),
	"Tiffany Blue" => array(10, 186, 181),
	"Tiger's Eye" => array(224, 141, 60),
	"Timberwolf" => array(219, 215, 210),
	"Titanium Yellow" => array(238, 230, 0),
	"Tomato" => array(255, 99, 71),
	"Toolbox" => array(116, 108, 192),
	"Topaz" => array(255, 200, 124),
	"Tractor Red" => array(253, 14, 53),
	"Trolley Grey" => array(128, 128, 128),
	"Tropical Rain Forest" => array(0, 117, 94),
	"Tropical Violet" => array(205, 164, 222),
	"True Blue" => array(0, 115, 207),
	"Tufts Blue" => array(65, 125, 193),
	"Tulip" => array(255, 135, 141),
	"Tumbleweed" => array(222, 170, 136),
	"Turkish Rose" => array(181, 114, 129),
	"Turquoise" => array(64, 224, 208),
	"Turquoise Blue" => array(0, 255, 239),
	"Turquoise Green" => array(160, 214, 180),
	"Tuscan" => array(250, 214, 165),
	"Tuscan Brown" => array(111, 78, 55),
	"Tuscan Red" => array(124, 72, 72),
	"Tuscan Tan" => array(166, 123, 91),
	"Tuscany" => array(192, 153, 153),
	"Twilight Lavender" => array(138, 73, 107),
	"Tyrian Purple" => array(102, 2, 60),
	"UA Blue" => array(0, 51, 170),
	"UA Red" => array(217, 0, 76),
	"Ube" => array(136, 120, 195),
	"UCLA Blue" => array(83, 104, 149),
	"UCLA Gold" => array(255, 179, 0),
	"UFO Green" => array(60, 208, 112),
	"Ultramarine" => array(63, 0, 255),
	"Ultramarine Blue" => array(65, 102, 245),
	"Ultra Pink" => array(255, 111, 255),
	"Ultra Red" => array(252, 108, 133),
	"Umber" => array(99, 81, 71),
	"Unbleached Silk" => array(255, 221, 202),
	"United Nations Blue" => array(91, 146, 229),
	"University Of California Gold" => array(183, 135, 39),
	"Unmellow Yellow" => array(255, 255, 102),
	"UP Forest Green" => array(1, 68, 33),
	"UP Maroon" => array(123, 17, 19),
	"Upsdell Red" => array(174, 32, 41),
	"Urobilin" => array(225, 173, 33),
	"USAFA Blue" => array(0, 79, 152),
	"USC Cardinal" => array(153, 0, 0),
	"USC Gold" => array(255, 204, 0),
	"University Of Tennessee Orange" => array(247, 127, 0),
	"Utah Crimson" => array(211, 0, 63),
	"Vanilla" => array(243, 229, 171),
	"Vanilla Ice" => array(243, 143, 169),
	"Vegas Gold" => array(197, 179, 88),
	"Venetian Red" => array(200, 8, 21),
	"Verdigris" => array(67, 179, 174),
	"Vermilion" => array(217, 56, 30),
	"Veronica" => array(160, 32, 240),
	"Very Light Azure" => array(116, 187, 251),
	"Very Light Blue" => array(102, 102, 255),
	"Very Light Malachite Green" => array(100, 233, 134),
	"Very Light Tangelo" => array(255, 176, 119),
	"Very Pale Orange" => array(255, 223, 191),
	"Very Pale Yellow" => array(255, 255, 191),
	"Violet" => array(143, 0, 255),
	"Violet-Blue" => array(50, 74, 178),
	"Violet-Red" => array(247, 83, 148),
	"Viridian" => array(64, 130, 109),
	"Viridian Green" => array(0, 150, 152),
	"Vista Blue" => array(124, 158, 217),
	"Vivid Amber" => array(204, 153, 0),
	"Vivid Auburn" => array(146, 39, 36),
	"Vivid Burgundy" => array(159, 29, 53),
	"Vivid Cerise" => array(218, 29, 129),
	"Vivid Cerulean" => array(0, 170, 238),
	"Vivid Crimson" => array(204, 0, 51),
	"Vivid Gamboge" => array(255, 153, 0),
	"Vivid Lime Green" => array(166, 214, 8),
	"Vivid Malachite" => array(0, 204, 51),
	"Vivid Mulberry" => array(184, 12, 227),
	"Vivid Orange" => array(255, 95, 0),
	"Vivid Orange Peel" => array(255, 160, 0),
	"Vivid Orchid" => array(204, 0, 255),
	"Vivid Raspberry" => array(255, 0, 108),
	"Vivid Red" => array(247, 13, 26),
	"Vivid Red-Tangelo" => array(223, 97, 36),
	"Vivid Sky Blue" => array(0, 204, 255),
	"Vivid Tangelo" => array(240, 116, 39),
	"Vivid Tangerine" => array(255, 160, 137),
	"Vivid Vermilion" => array(229, 96, 36),
	"Vivid Violet" => array(159, 0, 255),
	"Vivid Yellow" => array(255, 227, 2),
	"Volt" => array(206, 255, 0),
	"Warm Black" => array(0, 66, 66),
	"Waterspout" => array(164, 244, 249),
	"Weldon Blue" => array(124, 152, 171),
	"Wenge" => array(100, 84, 82),
	"Wheat" => array(245, 222, 179),
	"White" => array(255, 255, 255),
	"White Smoke" => array(245, 245, 245),
	"Wild Blue Yonder" => array(162, 173, 208),
	"Wild Orchid" => array(212, 112, 162),
	"Wild Strawberry" => array(255, 67, 164),
	"Wild Watermelon" => array(252, 108, 133),
	"Willpower Orange" => array(253, 88, 0),
	"Windsor Tan" => array(167, 85, 2),
	"Wine" => array(114, 47, 55),
	"Wine Dregs" => array(103, 49, 71),
	"Wisteria" => array(201, 160, 220),
	"Wood Brown" => array(193, 154, 107),
	"Xanadu" => array(115, 134, 120),
	"Yale Blue" => array(15, 77, 146),
	"Yankees Blue" => array(28, 40, 65),
	"Yellow" => array(255, 255, 0),
	"Yellow (Crayola)" => array(252, 232, 131),
	"Yellow (Munsell)" => array(239, 204, 0),
	"Yellow (NCS)" => array(255, 211, 0),
	"Yellow (Pantone)" => array(254, 223, 0),
	"Yellow (Process)" => array(255, 239, 0),
	"Yellow (RYB)" => array(254, 254, 51),
	"Yellow-Green" => array(154, 205, 50),
	"Yellow Orange" => array(255, 174, 66),
	"Yellow Rose" => array(255, 240, 0),
	"Zaffre" => array(0, 20, 168),
	"Zinnwaldite Brown" => array(44, 22, 8),
	"Zomp" => array(57, 167, 142)
	];
	$r = hexdec(substr($color, 1, 2));
	$g = hexdec(substr($color, 3, 2));
	$b = hexdec(substr($color, 5, 2));
	foreach ($colors as $name => $code) {
		$currvar = abs(pow($code[0] - $r, 2)
			+ pow($code[1] - $g, 2)
			+ pow($code[2] - $b, 2));
		if (!isset($minvar)) {
			$closest = $name;
			$minvar = $currvar;
		}
		if ($currvar < $minvar) {
			$closest = $name;
			$minvar = $currvar;
		}
	}
	return $closest;
}
?>
