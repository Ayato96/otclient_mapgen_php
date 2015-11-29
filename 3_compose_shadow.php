<?php
if(!isset($_GET['minX']) || !isset($_GET['maxX']))
{
	exit('Missing parameter.');
}
$minX = $_GET['minX'];
$maxX = $_GET['maxX'];

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
$shadowImage = new Imagick(realpath('shadow.png'));
// over ground
$savePath = getcwd() . DIRECTORY_SEPARATOR . 'map_composed';

echo '<br />List of images generated in ' . (microtime(true)-$start) . ' seconds. Shadow generator started: ' . date('H:i:s');

$start = microtime(true);
$imagesCount = 0;
foreach($filesMap as $x => $filesMap2)
{
	if($x < $minX && $x > $maxX)
		continue;

	foreach($filesMap2 as $y => $filesMap3)
	{
		foreach($filesMap3 as $z => $filesMap4)
		{
			$image = getImage($x, $y, $z)->image;
			// OTClient generate some bugged PNGs, cannot 'modulate' their colors [white change to green], only blend with shadow image
			//$image->modulateImage(70,100,100);
			$image->compositeImage($shadowImage, Imagick::COMPOSITE_DEFAULT, 0, 0);
			if(isset($clearFilesMap[$x][$y][$z]))
			{
				$clearImage = getClearImage($x, $y, $z);
				$image->compositeImage($clearImage->image, Imagick::COMPOSITE_DEFAULT, 0, 0);
				$clearImage->clear();
			}
			++$imagesCount;
			$image->writeImage($savePath . DIRECTORY_SEPARATOR . $x . '_' . $y . '_' . $z . '.png');
		}
	}
}
echo '<br />Shadows generated in ' . (microtime(true)-$start) . ' seconds - ' . $imagesCount . ' images generated - time: ' . date('H:i:s');
