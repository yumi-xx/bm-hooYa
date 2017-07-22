<?php
include 'includes/config.php';

include CONFIG_COMMON_PATH.'includes/core.php';
if (CONFIG_REQUIRE_AUTHENTICATION)
	include CONFIG_COMMON_PATH.'includes/auth.php';
include CONFIG_HOOYA_PATH.'includes/video.php';
include CONFIG_HOOYA_PATH.'includes/database.php';

if (!isset($_GET['key']))
	die;
$key = rawurldecode($_GET['key']);

// Throw a 404 img if that key is not in the database
/*if (!($fileinfo = db_getfileinfo($key)) {
	$file = CONFIG_HOOYA_PATH . '/spoilers/404.jpg';
	bmfft_xsendfile($file);
	return;
}*/
$fileinfo = db_getfileinfo($key);
// Grab some basic details for the file
$class = $fileinfo['Class'];
$path = $fileinfo['Path'];
$mimetype = $fileinfo['Mimetype'];
$ftype = explode('/', $mimetype)[0];

// Assume that we are sending the raw file until we can deisprove that
$file = $path;

// Handle thumbnail requests
if (isset($_GET['thumb'])) {
	// Take a snapshot of the video and use that as a thumbnail
	if ($ftype == 'video') {
		$file = CONFIG_TEMPORARY_PATH . $key . '.jpg';
		if (!file_exists($file))
			exec('ffmpegthumbnailer -i '.escapeshellarg($path)
			.' -f -q 10 -s 500  -o '.$file);
		bmfft_xsendfile($file);
		return;
	}
	// Take the first frame of a .gif and thumbnail it
	if ($ftype == 'image' && $mimetype == 'image/gif') {
		$file = CONFIG_TEMPORARY_PATH . $key . '.jpg';
		if (!file_exists($file))
			exec('convert '.escapeshellarg($path)
			.'[0] -thumbnail "500x500>" '.$file);
		bmfft_xsendfile($file);
		return;
	}
	// Default to JPG thumbnails to save space and time
	if ($ftype == 'image' && $mimetype != 'image/png') {
		$file = CONFIG_TEMPORARY_PATH.$key . '.jpg';
		if (!file_exists($file))
			exec('convert '.escapeshellarg($path)
			.' -thumbnail "500x500>" '.$file);
		bmfft_xsendfile($file);
		return;
	}
	// PNGs need a PNG thumbnail because otherwise transparencies look funny
	if ($ftype == 'image') {
		$file = CONFIG_TEMPORARY_PATH . $key . '.png';
		if (!file_exists($file))
			exec('convert ' . escapeshellarg($path)
			. ' -thumbnail "500x500>" '.$file);
		bmfft_xsendfile($file);
		return;
	}
}
if (isset($_GET['partyhat'])) {
	// Coming soon -- watch imagemagick render party hats onto thumbnails!
}
if ($ftype == 'video' && isset($_GET['preview'])) {
	$percent = (isset($_GET['percent']) ? $_GET['percent']: '0');
	$file = CONFIG_TEMPORARY_PATH . $key . '_preview_' . $percent . '.png';
	if (!file_exists($file))
		exec('ffmpegthumbnailer -i ' . escapeshellarg($path)
		. ' -s 0 -o ' . $file . ' -t ' . $percent);
	bmfft_xsendfile($file);
	return;
}
// Otherwise, just send the file with no special rendering
bmfft_xsendfile($file);

function bmfft_xsendfile($file) {
	header('Content-Type:'.mime_content_type($file));
	header('Content-Disposition: inline; filename="bigmike-' . basename($file) . '"');
	header('X-Sendfile: '.$file);
}
?>
