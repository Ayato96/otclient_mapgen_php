<?php
// config:
$hiddenNames = ["GOD vod'ka", "Adm XX"];
$SQL = new PDO('mysql:host=localhost;dbname=tfs', 'root', '');


// CODE
foreach($hiddenNames as $id => $hiddenName)
{
	$hiddenNames[$id] = base64_encode($hiddenName);
}
$response = $SQL->query('SELECT * FROM `minimap_stream`');
$ret = [];
if($response)
{
	$result = $response->fetch();
	if($result)
	{
		$data = json_decode($result['info']);
		$ret = [];
		$ret['time'] = time() - $result['date'];
		$ret['players'] = [];
		foreach($data as $player)
		{
			if(!in_array($player[1], $hiddenNames))
			{
				$ret['players'][] = [$player[0],$player[1],$player[2],$player[3],$player[4]];
			}
		}
	}
}
echo json_encode($ret);