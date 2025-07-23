<?php declare(strict_types = 1);

define('SIZE', 250); //long side max px
//define('EXIF', true); //thumbnail from exif
//define('SAVE', true); //save thumbnail

$file = $_GET['path'];

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
			//resize
			if(max($width, $height) > SIZE)
			{
				$new_width = SIZE;
				$new_height = -1;
				if($width > $height)
				{
					$new_height  = ($height / $width) * SIZE;
				}
				else
				{
					$new_width    = ($width / $height) * SIZE;
					$new_height   = SIZE;
				}
				$image_orig = imagescale($image_orig, intval($new_width), intval($new_height));
			}
			//rotate
			if(extension_loaded('exif') && ($exif = exif_read_data($file, 'IFD0')) !== false)
			{
				$rotate = match($exif['Orientation'])
				{
					3 => 180, //rotate upside down
					6 => -90, //rotate right
					8 => 90, //rotate left
					default => 0,
				};
				if($rotate !== 0) $image_orig = imagerotate($image_orig, $rotate, 0);
			}
			//output
			if(defined('SAVE')) //to file
			{
				imagegif($image_orig, $thumbnail);
				imagedestroy($image_orig);
			}
			else //to output
			{
				header('Content-Type: ' . $image_info['mime']);
				imagegif($image_orig);
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

			readfile($thumbnail);
			exit;
		}
	}
}

//something wrong
http_response_code(404);
echo '404 Not Found';
