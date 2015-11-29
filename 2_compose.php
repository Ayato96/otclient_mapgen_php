<?php
if(!isset($_GET['minX']) || !isset($_GET['maxX']) || !isset($_GET['minY']) || !isset($_GET['maxY']) || !isset($_GET['minZ']) || !isset($_GET['maxZ']))
{
	exit('Missing parameter.');
}
$minX = $_GET['minX'];
$maxX = $_GET['maxX'];

$minY = $_GET['minY'];
$maxY = $_GET['maxY'];

$minZ = $_GET['minZ'];
$maxZ = $_GET['maxZ'];

//--------------------------------------------------
$start = microtime(true);
echo 'Start time: ' . date('H:i:s');
$imagePaths = glob('map_composed/*.png');
$clearImagePaths = glob('map/*.png');

$filesMap = [];
$clearFilesMap = [];


class ImageLoader
{
	public $image = null;
	public $loaded = false;

	function __construct($path)
	{
		$this->image = new Imagick();
		$this->loaded = ($this->image->readImage(realpath($path)) === true);
	}

	function clear()
	{
		if($this->loaded)
		{
			$this->image->clear();
		}
	}
}

foreach($imagePaths as $imagePath)
{
	$pos = getImageCords($imagePath);
	$filesMap[$pos['x']][$pos['y']][$pos['z']] = true;
}

foreach($clearImagePaths as $imagePath)
{
	$pos = getImageCords($imagePath);
	$clearFilesMap[$pos['x']][$pos['y']][$pos['z']] = true;
}

function getImage($x, $y, $z)
{
	return new ImageLoader('map_composed/' . $x . '_' . $y . '_' . $z . '.png');
}

function getClearImage($x, $y, $z)
{
	return new ImageLoader('map/' . $x . '_' . $y . '_' . $z . '.png');
}

function getImageCords($imagePath)
{
	$temp1 = explode('.', basename($imagePath));
	$temp2 = explode('_', $temp1[0]);
	return ['x' => $temp2[0], 'y' => $temp2[1], 'z' => $temp2[2]];
}

// over ground
$savePath = getcwd() . DIRECTORY_SEPARATOR . 'map_composed';

echo '<br />List of images generated in ' . (microtime(true)-$start) . ' seconds. Floor image generator started: ' . date('H:i:s');
$start = microtime(true);
// we don't need to generate lowest level ($maxZ) as there is nothing below
for($z = $maxZ - 1; $z >= $minZ; $z--)
{
	// skip ground floor, you cannot see lower floors when you are on ground floor
	if($z == 7)
		continue;

	$floorImages = 0;
	for($x = $minX; $x <= $maxX; $x++)
	{
		for($y = $minY; $y <= $maxY; $y++)
		{
			if(!isset($clearFilesMap[$x][$y][$z]) && !isset($filesMap[$x-1][$y-1][$z+1]) && !isset($filesMap[$x][$y-1][$z+1]) && !isset($filesMap[$x-1][$y][$z+1]) && !isset($filesMap[$x][$y][$z+1]))
			{
				continue;
			}

			$image = new Imagick();
			$image->newImage(256, 256, new ImagickPixel('transparent'));

			// top-left
			if(isset($filesMap[$x-1][$y-1][$z+1]))
			{
				$partImage = getImage($x-1, $y-1, $z+1);
				$image->compositeImage($partImage->image, Imagick::COMPOSITE_DEFAULT, -224, -224);
				$partImage->clear();
			}
			// top-right
			if(isset($filesMap[$x][$y-1][$z+1]))
			{
				$partImage = getImage($x, $y-1, $z+1);
				$image->compositeImage($partImage->image, Imagick::COMPOSITE_DEFAULT, 32, -224);
				$partImage->clear();
			}
			// bottom-left
			if(isset($filesMap[$x-1][$y][$z+1]))
			{
				$partImage = getImage($x-1, $y, $z+1);
				$image->compositeImage($partImage->image, Imagick::COMPOSITE_DEFAULT, -224, 32);
				$partImage->clear();
			}
			// bottom-left
			if(isset($filesMap[$x][$y][$z+1]))
			{
				$partImage = getImage($x, $y, $z+1);
				$image->compositeImage($partImage->image, Imagick::COMPOSITE_DEFAULT, 32, 32);
				$partImage->clear();
			}

// NOT NEEDED, WE WILL ADD IT AFTER 'SHADOW'
			// current floor
			if(isset($clearFilesMap[$x][$y][$z]))
			{
				$imageFile = getClearImage($x, $y, $z);
				$image->compositeImage($imageFile->image, Imagick::COMPOSITE_DEFAULT, 0, 0);
				$imageFile->clear();
				//imagecopyresampled($image, $src, 0, 0, 0, 0, 256, 256, 256, 256);
			}

			// Save the finished image.
			$floorImages++;
			$filesMap[$x][$y][$z] = true;
			$image->writeImage($savePath . DIRECTORY_SEPARATOR . $x . '_' . $y . '_' . $z . '.png');
			$image->clear();
		}
	}

	echo '<br />Floor ' . $z . ' generated in ' . (microtime(true)-$start) . ' seconds - ' . $floorImages . ' images generated - time: ' . date('H:i:s');
	$start = microtime(true);
}