<?php

declare(strict_types=1);

define('SIZE_MIN', 160); //px min thumbnail size
define('SIZE_DEF', 255); //px default thumbnail size
define('SIZE_MAX', 2600); //px max thumbnail size (or send original image)

//path
$file = $_GET['path'];
$file = realpath(dirname(__FILE__) . DIRECTORY_SEPARATOR . $file); //relative path
if ($file === false || !is_file($file) || !is_readable($file)) //file_exists
{
	http_response_code(404); //file not found
	echo '404 Not Found';
	exit;
}

//file extension
$extension = strtolower(pathinfo($file, PATHINFO_EXTENSION)); //get extension
if (!in_array($extension, ['jpg', 'jpeg', 'png', 'bmp'])) //supported image file
{
	http_response_code(415); //unsupported file type
	echo '415 Unsupported Media Type';
	exit;
}

//request resolution
$viewport_width = intval($_GET['w'] ?? SIZE_DEF);
$viewport_height = intval($_GET['h'] ?? SIZE_DEF);
if ($viewport_width <= 0 || $viewport_height <= 0) //invalid viewport size
{
	http_response_code(400);
	echo '400 Bad Request';
	exit;
}
if ($viewport_width < SIZE_MIN) $viewport_width = SIZE_MIN; //min viewport width
if ($viewport_height < SIZE_MIN) $viewport_height = SIZE_MIN; //min viewport height

//original image size
$img_width = 0;
$img_height = 0;
$image_info = getimagesize($file);
if ($image_info !== false) list($img_width, $img_height) = $image_info;
if ($img_width <= 0 || $img_height <= 0) //invalid image size
{
	http_response_code(415); //unsupported file type
	echo '415 Unsupported Media Type';
	exit;
}

//the requested size is larger than the original image size
if ($viewport_width > $img_width || $viewport_height > $img_height || $viewport_width > SIZE_MAX || $viewport_height > SIZE_MAX || isset($_GET['dl'])) {
	if (isset($_GET['dl'])) header('Content-Disposition: attachment; filename="' . basename($file) . '"'); //download
	header('Content-Type: ' . $image_info['mime']);
	header('Content-Length: ' . filesize($file));
	readfile($file); //output original image
	exit;
}

if (!extension_loaded('gd')) {
	http_response_code(503); //service unavailable
	echo '503 Service Unavailable';
	exit;
}

//load original image
$image_orig = match ($image_info[2]) {
	IMAGETYPE_JPEG => imagecreatefromjpeg($file),
	IMAGETYPE_PNG => imagecreatefrompng($file),
	IMAGETYPE_BMP => imagecreatefrombmp($file),
	default => false,
};

if ($image_orig === false) {
	http_response_code(415); //unsupported file type
	echo '415 Unsupported Media Type';
	exit;
}

//get rotation
$rotate = 0;
if (extension_loaded('exif') && ($exif = exif_read_data($file, 'IFD0')) !== false) {
	$rotate = match ($exif['Orientation']) {
		3 => 180, //rotate upside down
		6 => -90, //rotate right
		8 => 90, //rotate left
		default => 0,
	};
}

//scale image
if ($viewport_width < $img_width && $viewport_height < $img_height) //downscale only
{
	$sideways = abs($rotate) == 90 ? true : false;
	//calc aspect ratio
	$screenAR = $viewport_width / $viewport_height;
	$imageAR = $sideways ? $img_height / $img_width : $img_width / $img_height;
	//resize
	if ($imageAR > $screenAR) // The image is wider in proportion than the screen, so we will limit it to the screen's width.
	{
		$img_width = $viewport_width;
		$img_height = round($viewport_width / $imageAR);
	} else // The image is taller in proportion than the screen, so we will limit it to the screen's height.
	{
		$img_height = $viewport_height;
		$img_width = $viewport_height * $imageAR;
	}
	//scale
	if ($sideways)
		$image_orig = imagescale($image_orig, intval($img_height), intval($img_width));
	else
		$image_orig = imagescale($image_orig, intval($img_width), intval($img_height));
}

//rotate image
if ($rotate !== 0) $image_orig = imagerotate($image_orig, $rotate, 0);

//output image
header('Content-Type: ' . $image_info['mime']);
match ($image_info[2]) {
	IMAGETYPE_JPEG => imagejpeg($image_orig),
	IMAGETYPE_PNG => imagepng($image_orig),
	IMAGETYPE_BMP => imagewbmp($image_orig),
	default => imagegif($image_orig),
};

//free memory
imagedestroy($image_orig);
