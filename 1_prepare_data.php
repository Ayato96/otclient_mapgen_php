<?php
if(!isset($_GET['groundLevel']) || !isset($_GET['lowestUndergroundLevel']))
{
	exit('Missing parameter "groundLevel" or "lowestUndergroundLevel".');
}
$groundLevel = $_GET['groundLevel'];
$lowestUndergroundLevel = $_GET['lowestUndergroundLevel'];

//----------------------
@mkdir('map_composed');

$copiedFloorGround = 0;
$copiedFloorUnderground = 0;

function getImageCords($imagePath)
{
	$temp1 = explode('.', basename($imagePath));
	$temp2 = explode('_', $temp1[0]);
	return ['x' => $temp2[0], 'y' => $temp2[1], 'z' => $temp2[2]];
}

$imagePaths = glob('map/*.png');

foreach($imagePaths as $imagePath)
{
	$pos = getImageCords($imagePath);
	if($pos['z'] == $groundLevel)
	{
		++$copiedFloorGround;
		copy($imagePath, 'map_composed/' . basename($imagePath));
	}
	elseif($pos['z'] == $lowestUndergroundLevel)
	{
		++$copiedFloorUnderground;
		copy($imagePath, 'map_composed/' . basename($imagePath));
	}
}

echo json_encode([
		'message' => [
			'Created directory "map_composed".',
			'Copied "' . $copiedFloorGround . '" floor ' . htmlspecialchars($_GET['groundLevel']) . ' images.',
			'Copied "' . $copiedFloorUnderground . '" floor ' . htmlspecialchars($_GET['lowestUndergroundLevel']) . ' images.'
		]
	]
);