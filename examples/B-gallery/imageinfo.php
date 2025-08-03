<?php declare(strict_types = 1);

$file = $_GET['path'];

if(file_exists($file))
{
	echo 'File: ' . basename($file) . PHP_EOL;
	echo 'Size: ' . convertFileSize(filesize($file)) . PHP_EOL;
	//get width and height
	if(($image_info = getimagesize($file)) !== false)
	{
		echo 'Type: ' . image_type_to_mime_type($image_info[2]) . PHP_EOL;
		echo 'Dimension: ' . $image_info[0] . ' x ' . $image_info[1] . PHP_EOL;
	}

	//exif
	if(extension_loaded('exif'))
	{
		if(($exif = exif_read_data($file, 'IFD0', true)) !== false) //exif_read_data($file, 'IFD0')
		{
			echo 'Camera: ' . ($exif['IFD0']['Make'] ?? '') . ' ' . ($exif['IFD0']['Model'] ?? '') . PHP_EOL;
			if (isset($exif['EXIF']['DateTimeOriginal'])) echo 'Date: ' . $exif['EXIF']['DateTimeOriginal'] . PHP_EOL;
			//if (isset($exif['EXIF']['FNumber'])) echo 'Aperture: ' . $exif['EXIF']['FNumber'] . PHP_EOL;
			if (isset($exif['COMPUTED']['ApertureFNumber'])) echo 'Aperture: ' . $exif['COMPUTED']['ApertureFNumber'] . PHP_EOL;
			elseif (isset($exif_data['COMPUTED']['ApertureValue'])) echo 'Aperture: ' . round(pow(sqrt(2), $exif_data['COMPUTED']['ApertureValue']), 2) . PHP_EOL;
			if (isset($exif['EXIF']['ISOSpeedRatings'])) echo 'ISO: ' . $exif['EXIF']['ISOSpeedRatings'] . PHP_EOL;
			//if (isset($exif['EXIF']['ShutterSpeedValue'])) echo 'Shutter: ' . $exif['EXIF']['ShutterSpeedValue'] . PHP_EOL;
			if (isset($exif['EXIF']['ExposureTime'])) echo 'Shutter: ' . simplifyFraction($exif['EXIF']['ExposureTime']) . ' sec' . PHP_EOL;
			if (isset($exif['EXIF']['ExposureBiasValue'])) echo 'Exposure: ' . convertFraction($exif['EXIF']['ExposureBiasValue']) . ' EV' . PHP_EOL;
			if (isset($exif['EXIF']['MeteringMode'])) echo 'Metering: ' . match($exif['EXIF']['MeteringMode'])
			{
				0 => 'Unknown',
				1 => 'Average',
				2 => 'Center-weighted', //Center-weighted average
				3 => 'Spot',
				4 => 'Multi-spot',
				5 => 'Pattern', //Pattern (Multi-segment)
				6 => 'Partial',
				255 => 'Other',
				default => 'Unknown (' . $exif['EXIF']['MeteringMode'] . ')',
			} . PHP_EOL;
			if (isset($exif['EXIF']['Flash'])) echo 'Flash: ' . match ($exif['EXIF']['Flash']) {
				0x0 => 'Flash did not fire',
				0x1 => 'Flash fired',
				0x5 => 'Flash fired, return not detected',
				0x7 => 'Flash fired, return light detected',
				0x8 => 'On, did not fire',
				0x9 => 'Flash fired, compulsory flash mode',
				0xD => 'Flash fired, compulsory mode, return not detected',
				0xF => 'Flash fired, compulsory mode, return light detected',
				0x10 => 'Off', //Off, did not fire, compulsory flash mode
				0x18 => 'Flash did not fire, auto mode',
				0x19 => 'Flash fired, auto mode',
				0x1D => 'Flash fired, auto mode, return not detected',
				0x1F => 'Flash fired, auto mode, return light detected',
				0x20 => 'No flash function',
				0x41 => 'Flash fired, red-eye reduction mode',
				0x45 => 'Flash fired, red-eye reduction mode, return not detected',
				0x47 => 'Flash fired, red-eye reduction mode, return light detected',
				0x49 => 'Flash fired, compulsory flash mode, red-eye reduction mode',
				0x4D => 'Flash fired, compulsory mode, red-eye reduction mode, return not detected',
				0x4F => 'Flash fired, compulsory mode, red-eye reduction mode, return light detected',
				0x50 => 'Flash did not fire, red-eye reduction mode',
				0x58 => 'Flash did not fire, auto mode, red-eye reduction mode',
				0x59 => 'Flash fired, auto mode, red-eye reduction mode',
				0x5D => 'Flash fired, auto mode, red-eye reduction mode, return not detected',
				0x5F => 'Flash fired, auto mode, red-eye reduction mode, return light detected',
				default => 'Unknown flash status (' . $exif['EXIF']['Flash'] . ')',
			} . PHP_EOL;
			if (isset($exif['EXIF']['FocalLength'])) echo 'Focal Length: ' . convertFraction($exif['EXIF']['FocalLength']) . ' mm' . PHP_EOL;
		}
	}
}

function convertFileSize(int $bytes): string
{
	$units = array('Bytes', 'KiB', 'MiB', 'GiB', 'TiB');
	$bytes = max($bytes, 0);

	$pow = floor(log($bytes, 1024));
	$pow = min($pow, count($units) - 1);

	$result = $bytes / pow(1024, $pow);

	return round($result, 2) . ' ' . $units[$pow];
}

function simplifyFraction(string $fraction):string 
{
	if (strpos($fraction, '/') !== false)
	{
		list($numerator, $denominator) = explode('/', $fraction);
		if (intval($numerator) > 1) return '1/' . round($denominator / $numerator);
	}
	return $fraction;
}

function convertFraction(string $fraction):string 
{
	if (strpos($fraction, '/') !== false)
	{
		list($numerator, $denominator) = explode('/', $fraction);
		if (intval($denominator) !== 0) return strval(round((float)$numerator / (float)$denominator, 2));
	}
	return $fraction;
}