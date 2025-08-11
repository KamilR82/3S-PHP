<?php declare(strict_types = 1);

define('SIZE', 250); //long side max px
//define('EXIF', true); //thumbnail from exif
//define('SAVE', true); //save thumbnail

$file = $_GET['path'];
$viewport_width = intval($_GET['w'] ?? SIZE);
$viewport_height = intval($_GET['h'] ?? SIZE);

if(file_exists($file))
{
	$thumbnail = $file . '.thumbnail';

	//try to create thumbnail
	if(!file_exists($thumbnail) && extension_loaded('gd'))
	{
		$image_info = false;
		$image_orig = false;
		$width = 0;
		$height = 0;
		$rotate = 0;

		//read image
		if(defined('EXIF') && extension_loaded('exif') && ($data = exif_thumbnail($file)) !== false) //thumbnail from exif
		{
			$image_info = getimagesizefromstring($data);
			if($image_info !== false)
			{
				list($width, $height) = $image_info;
				$image_orig = imageCreateFromString($data);
			}
		}
		else //read original image file
		{
			$image_info = getimagesize($file);
			if($image_info !== false)
			{
				list($width, $height) = $image_info;
				$image_orig = match($image_info[2])
				{
					IMAGETYPE_JPEG => imagecreatefromjpeg($file),
					IMAGETYPE_PNG => imagecreatefrompng($file),
					IMAGETYPE_BMP => imagecreatefrombmp($file),
					default => false,
				};
			}
		}
		//edit image (resize + rotate)
		if($image_orig !== false)
		{
			//get rotation
			$rotate = 0;
			if(extension_loaded('exif') && ($exif = exif_read_data($file, 'IFD0')) !== false)
			{
				$rotate = match($exif['Orientation'])
				{
					3 => 180, //rotate upside down
					6 => -90, //rotate right
					8 => 90, //rotate left
					default => 0,
				};
			}
			if($viewport_width < $width && $viewport_height < $height) //downscale only
			{
				$sideways = abs($rotate) == 90 ? true : false;
				//calc aspect ratio
				$screenAR = $viewport_width / $viewport_height;
				$imageAR = $sideways ? $height / $width : $width / $height;
				//resize
				if($imageAR > $screenAR) // The image is wider in proportion than the screen, so we will limit it to the screen's width.
				{
					$width = $viewport_width;
					$height = round($viewport_width / $imageAR);
				}
				else // The image is taller in proportion than the screen, so we will limit it to the screen's height.
				{
					$height = $viewport_height;
					$width = $viewport_height * $imageAR;
				}
				//scale
				if($sideways)
					$image_orig = imagescale($image_orig, intval($height), intval($width));
				else
					$image_orig = imagescale($image_orig, intval($width), intval($height));
			}
			//rotate
			if($rotate !== 0) $image_orig = imagerotate($image_orig, $rotate, 0);
			//output
			if(defined('SAVE')) //to file
			{
				imagegif($image_orig, $thumbnail);
				imagedestroy($image_orig);
			}
			else //to output
			{
				header('Content-Type: ' . $image_info['mime']);
				//header('Content-Length: ' . ???);
				if(isset($_GET['dl'])) header('Content-Disposition: attachment; filename="' . basename($file) . '"');
				match($image_info[2])
				{
					IMAGETYPE_JPEG => imagejpeg($image_orig),
					IMAGETYPE_PNG => imagepng($image_orig),
					IMAGETYPE_BMP => imagewbmp($image_orig),
					default => imagegif($image_orig),
				};
				imagedestroy($image_orig);
				exit;
			}
		}
	}

	//thumbnail from file
	if(file_exists($thumbnail))
	{
		$image_info = getimagesize($thumbnail);
		if($image_info !== false)
		{
			header('Content-Type: ' . $image_info['mime']);
			header('Content-Length: ' . filesize($thumbnail));
			if(isset($_GET['dl'])) header('Content-Disposition: attachment; filename="' . basename($file) . '"');

			readfile($thumbnail);
			exit;
		}
	}
}

//something wrong
http_response_code(404);
echo '404 Not Found';
