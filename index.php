<?php

// Parse todays logs
	$argv = array('-s','-r',date('Y-m-d'));
	$s = microtime(true);
	include('oaStatistics.php');
	$e = microtime(true)-$s;

    include('oaAggregate.php');


	$players = array_unique(array_values(oaMapper::$playermap));

	$argv = array('-s','-r',date('Y-m-d'));

	$log = array('parsedfiles' => $stats->logFileCount, 'gamesparsed' => $stats->gameCount, 'time' => round($e,3));


	$weeks = array(date('W') => $t=mktime(0,0,0,date('m'), date('d')-date('N')+1, date('Y')));
	for($i=0; $i<16; $i++) {
		$t=($t - 86400*7);
		$w=date('W', $t);
		$weeks[$w] = $t;
	}

	if(isset($_GET['start']) && isset($_GET['end']) && is_numeric($_GET['start']) && is_numeric($_GET['end'])) {
		$start = $_GET['start'];
		$end = $_GET['end'];
		$queryStart = '&start='.$start.'&end='.$end;
	} elseif(isset($_GET['start']) && $_GET['start'] == 'alltime') {
		$start = 0;
		$end = date('Ymd');
		$queryStart = '&start=alltime';
	} else {
		$_GET['start'] = '4weeks';
		$start = date('Ymd', time()-(28*86400));
		$end = date('Ymd');
		$queryStart = '&start=4weeks';
	}


	if(isset($_GET['player']) && in_array($_GET['player'], oaMapper::$playermap)) {
		$currentPlayer = $_GET['player'];
		$queryPlayer = "&player=".$currentPlayer;
	} else {
		$currentPlayer = false;
		$queryPlayer = "";
	}

	$aggregator = new oaAggregate(array('start'=>$start, 'end'=>$end));

	$s = microtime(true);
	$stats = $aggregator->process();
	$e = microtime(true)-$s;


	$log['processtime'] = round($e,3);
	$log['gamesprocessed'] = $aggregator->gameCount();



	if(isset($_GET['player']) && is_string($_GET['player']) && array_key_exists($_GET['player'], $stats)) {
		$player = $_GET['player'];
		include('views/player.php');
	} else {
		include('views/overview.php');
	}
