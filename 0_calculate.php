<?php
if(!isset($_GET['threadsCount']))
{
	exit('Missing parameter "threadsCount".');
}
$threadsCount = $_GET['threadsCount'];

//----------------------
function getImageCords($imagePath)
{
	$temp1 = explode('.', basename($imagePath));
	$temp2 = explode('_', $temp1[0]);
	return ['x' => $temp2[0], 'y' => $temp2[1], 'z' => $temp2[2]];
}

$imagePaths = glob('map/*.png');

$saturationMap = [];
$totalCount = 0;

$minY = 99999;
$maxY = -1;

$minZ = 100;
$maxZ = -1;

foreach($imagePaths as $imagePath)
{
	$pos = getImageCords($imagePath);

	if(isset($saturationMap[$pos['x']]))
	{
		++$saturationMap[$pos['x']];
	}
	else
	{
		$saturationMap[$pos['x']] = 1;
	}

	if($pos['y'] < $minY)
	{
		$minY = $pos['y'];
	}
	if($pos['y'] > $maxY)
	{
		$maxY = $pos['y'];
	}

	if($pos['z'] < $minZ)
	{
		$minZ = $pos['z'];
	}
	if($pos['z'] > $maxZ)
	{
		$maxZ = $pos['z'];
	}

	++$totalCount;
}

$perThread = $totalCount / $threadsCount;
$currentSum = 0;
$lastX = 0;
$threads = [];
$threadId = 0;

ksort($saturationMap);

foreach($saturationMap as $x => $value)
{
	if($currentSum >= $perThread)
	{
		$threads[$threadId] = [$lastX, $x];
		$threadId++;
		$lastX = $x;
		$currentSum = 0;
	}

	$currentSum += $value;
}
$threads[$threadId] = [$lastX, $x];
// first thread 'minX' is not 0, it's 'x' of first element of saturation map
$threads[0][0] = array_keys($saturationMap)[0];

echo json_encode([
		'threads' => $threads,
		'y' => [$minY, $maxY],
		'z' => [$minZ, $maxZ]
	]
);