<?php
$s = microtime(true);
ini_set('display_errors', 'on');
$dir = dir('games');
while($entry = $dir->read()) {
	if(substr($entry,0,1) != '.') {
		include('games/'.$entry);
		$game = array_pop($games);
		
		if(@$game['voted'] == true) {
			isset($votedmaps[$game['map']]) ? $votedmaps[$game['map']]++ : $votedmaps[$game['map']]=1;
		} else {
			isset($playedmaps[$game['map']]) ? $playedmaps[$game['map']]++ : $playedmaps[$game['map']]=1;
		}		
	}
}

$votedSum=0;$playedSum=0;
echo "Voted:\n";
arsort($votedmaps);
foreach($votedmaps as $map => $count) {
	echo $map.' - '.$count."\n";
	$votedSum+=$count;
}

echo "\nPlayed:\n";
arsort($playedmaps);
foreach($playedmaps as $map => $count) {
	echo $map.' - '.$count."\n";
	$playedSum+=$count;
}

echo "Total played: $playedSum, Total voted: $votedSum\n";
$e = microtime(true);
echo "Time :".($e-$s)."\n";