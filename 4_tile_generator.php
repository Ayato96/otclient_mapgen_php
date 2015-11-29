<?php
/*
if(!isset($_GET['minZ']) || !isset($_GET['maxZ']))
{
	exit('Missing parameter.');
}

$minZ = $_GET['minZ'];
$maxZ = $_GET['maxZ'];
*/
$start = microtime(true);
echo 'Start time: ' . date('H:i:s');

function getImageCords($imagePath)
{
	$temp1 = explode('.', basename($imagePath));
	$temp2 = explode('_', $temp1[0]);
	return ['x' => $temp2[0], 'y' => $temp2[1], 'z' => $temp2[2]];
}

if(file_exists('map_composed'))
{
	if(!is_dir('map_tiled') && !@mkdir('map_tiled'))
	{
		exit('Cannot create folder "map_tiled".');
	}
	if(!@rename('map_composed', 'map_tiled/16'))
	{
		exit('Cannot move folder "map_composed" to "map_tiled/16".');
	}
}

if(!is_dir('map_tiled') || !is_dir('map_tiled/16'))
{
	exit('Folder "map_tiled" or "map_tiles/16" does not exist.');
}

$directory = getcwd() . DIRECTORY_SEPARATOR . 'map_tiled';
echo '<br />Folders for tiles generator created and images moved to new folder in ' . (microtime(true)-$start) . ' seconds. Tiles generator started: ' . date('H:i:s');
$start = microtime(true);

// for each zoom level
for($zoom = 16; $zoom > 0; $zoom--)
{
	@mkdir($directory . '/' . ($zoom-1));

	$imagesGenerated = 0;
	foreach(glob($directory . '/' . $zoom . '/*.png') as $imagePath)
	{
		$position = getImageCords($imagePath);
		$x = floor($position['x'] / 2) * 2;
		$y = floor($position['y'] / 2) * 2;
		$z = $position['z'];
		$toPath = $directory . '/' . ($zoom-1) . '/' . ($x/2) . '_' . ($y/2) . '_' . $z . '.png';
		if(!file_exists($toPath))
		{
			++$imagesGenerated;
			$topLeftPath = $directory . '/' . $zoom . '/' . $x . '_' . $y . '_' . $z . '.png';
			$topRightPath = $directory . '/' . $zoom . '/' . ($x+1) . '_' . $y . '_' . $z . '.png';
			$bottomLeftPath = $directory . '/' . $zoom . '/' . $x . '_' . ($y+1) . '_' . $z . '.png';
			$bottomRightPath = $directory . '/' . $zoom . '/' . ($x+1) . '_' . ($y+1) . '_' . $z . '.png';

			// IMAGIC VERSION, SLOWER
			/*
			$image = new Imagick();
			$image->newImage(512, 512, new ImagickPixel('transparent'));
			if(file_exists($topLeftPath))
			{
				$partImage = new Imagick($topLeftPath);
				$image->compositeImage($partImage, Imagick::COMPOSITE_DEFAULT, 0, 0);
				$partImage->clear();
			}
			if(file_exists($topRightPath))
			{
				$partImage = new Imagick($topRightPath);
				$image->compositeImage($partImage, Imagick::COMPOSITE_DEFAULT, 256, 0);
				$partImage->clear();
			}
			if(file_exists($bottomLeftPath))
			{
				$partImage = new Imagick($bottomLeftPath);
				$image->compositeImage($partImage, Imagick::COMPOSITE_DEFAULT, 0, 256);
				$partImage->clear();
			}
			if(file_exists($bottomRightPath))
			{
				$partImage = new Imagick($bottomRightPath);
				$image->compositeImage($partImage, Imagick::COMPOSITE_DEFAULT, 256, 256);
				$partImage->clear();
			}
			//$image->resizeImage(256, 256, Imagick::FILTER_LANCZOS, 1);
			$image->scaleImage(256, 256);
			$image->writeImage($toPath);
			$image->clear();
			*/

			// PHP GD version, 3-4 times faster on my CPU (+SSD) and uses only 1 core, wtf..
			$imgBig = imagecreatetruecolor (512,512);
			imagecolorallocate($imgBig, 0, 0, 0);
			if(file_exists($topLeftPath))
			{
				$img1 = imagecreatefrompng($topLeftPath);
				imagecopy($imgBig, $img1, 0, 0, 0, 0, 256, 256);
				imagedestroy($img1);
			}
			if(file_exists($topRightPath))
			{
				$img2 = imagecreatefrompng($topRightPath);
				imagecopy($imgBig, $img2, 256, 0, 0, 0, 256, 256);
				imagedestroy($img2);
			}
			if(file_exists($bottomLeftPath))
			{
				$img3 = imagecreatefrompng($bottomLeftPath);
				imagecopy($imgBig, $img3, 0, 256, 0, 0, 256, 256);
				imagedestroy($img3);
			}
			if(file_exists($bottomRightPath))
			{
				$img4 = imagecreatefrompng($bottomRightPath);
				imagecopy($imgBig, $img4, 256, 256, 0, 0, 256, 256);
				imagedestroy($img4);
			}

			$img = imagecreatetruecolor (256,256);
			imagecopyresampled($img, $imgBig, 0, 0, 0, 0, 256, 256, 512, 512);
			imagepng($img, $toPath, 1);
			imagedestroy($img);
			imagedestroy($imgBig);
		}
	}
	echo '<br />Zoom level "' . $zoom . '" tiles (' . $imagesGenerated . ' images) generated in ' . (microtime(true)-$start) . ' seconds - ' . date('H:i:s');
	$start = microtime(true);
}