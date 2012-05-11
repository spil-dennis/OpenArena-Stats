<?php

require_once('players.php');
require_once('functions.php');
require_once('definitions.php');

$starttime = microtime(true);

$logdir     = '/var/log/openarena';
$logfiles   = getLogFiles($logdir);
$size       = 0;

$stats      = generatePlayerSkeletons($known_players);

$kill_pattern = '/(?P<killer>[a-zA-Z0-9-_\ ]+)\ killed\ (?P<victim>[a-zA-Z0-9-_\ ]+)\ by\ (?P<weapon>[A-Z_]+)/';

$taken_pattern   = '/(?P<player>[a-zA-Z0-9-_\ ]+)\ got\ the\ (RED|BLUE)\ flag/';
$capture_pattern = '/(?P<player>[a-zA-Z0-9-_\ ]+)\ captured\ the\ (RED|BLUE)\ flag/';
$return_pattern  = '/(?P<player>[a-zA-Z0-9-_\ ]+)\ returned\ the\ (RED|BLUE)\ flag/';
$frag_pattern    = '/(?P<player>[a-zA-Z0-9-_\ ]+)\ fragged\ (RED|BLUE)\'s\ flag\ carrier/';

$award_pattern = '/(?P<player>[a-zA-Z0-9-_\ ]+)\ gained\ the\ (?P<award>[A-Z]+)\ award/';

$score_pattern = '/[0-9]+\ (?P<player>[a-zA-Z0-9-_\ ]+)/';

// Loop through all returned logfiles
foreach($logfiles as $log) {
    // Reset the current streaks
    resetCurrentStreaks( $stats );

    // Previous line was not a score, used for counting.
    $fraglimit_stats = false;
    $fraglimit_pos = 0;

    // Open the current logfile
    $handle = fopen($log, 'r');
    // Walk through all lines in the logfile
    while(($line = fgets($handle, 4096)) !== false) {

        // We're currently having three different timestamp types
        $parts = explode('|', $line);
        if (count($parts) == 1) {
            // No "|" character, assume the "01:00 TYPE" format
            $parts      = array();
            $parts[0]   = substr($line, 1, 5);
            $parts[1]   = substr($line, 7);
        }

        // Rename for clarity
        $time = $parts[0];
        $text = $parts[1];

        // Explode string
        $exploded = explode(': ', $text);

        if (count($exploded) <= 1) {
            // We can skip this line, nothing interesting
            continue;
        }

        $type = $exploded[0];
        $ids  = explode(' ', $exploded[1]);

        switch($type) {
            case 'CTF':
                switch($ids[2]) {
                    case 0:
                        if (preg_match($taken_pattern, $exploded[2], $taken_match)) {
                            if (isset($known_players[$taken_match['player']])) {
                                $player = $known_players[$taken_match['player']];

                                $stats[$player]['CTF']['Flags taken']++;
                            }
                        }
                    break;

                    case 1:
                        if (preg_match($capture_pattern, $exploded[2], $capture_match)) {
                            if (isset($known_players[$capture_match['player']])) {
                                $player = $known_players[$capture_match['player']];

                                $stats[$player]['CTF']['Flags captured']++;
                            }
                        }
                    break;

                    case 2:
                        if (preg_match($return_pattern, $exploded[2], $return_match)) {
                            if (isset($known_players[$return_match['player']])) {
                                $player = $known_players[$return_match['player']];

                                $stats[$player]['CTF']['Flags returned']++;
                            }
                        }
                    break;

                    case 3:
                        if (preg_match($frag_pattern, $exploded[2], $frag_match)) {
                            if (isset($known_players[$frag_match['player']])) {
                                $player = $known_players[$frag_match['player']];

                                $stats[$player]['CTF']['Flagcarriers killed']++;
                            }
                        }
                    break;
                }
            break;

            case 'Award':
                if (preg_match($award_pattern, $exploded[2], $award_match)) {
                    if (isset($known_players[$award_match['player']])) {
                        $player = $known_players[$award_match['player']];

                        $stats[$player]['AWARDS'][ucfirst(strtolower($award_match['award']))]++;
                    }
                }
            break;

            case 'Exit':
		        $fraglimit_test = strcasecmp(trim($exploded[1]), 'Fraglimit hit.');
                if ($fraglimit_test==0) {
                    $fraglimit_stats=true;
	                $fraglimit_pos=0;
                }
		        else {
		            $fraglimit_stats=false;
		            $fraglimit_pos=0;
		        }
            break;

            case 'score':
                if ($fraglimit_stats && preg_match($score_pattern, $exploded[3], $score_match)) {
                    if (isset($known_players[$score_match['player']])) {
                        $player = $known_players[$score_match['player']];
                        $stats[$player]['RANKINGS'][$fraglimit_pos]++;
                    }
		            $fraglimit_pos++;
                }
            break;

            case 'Kill':
                if(preg_match($kill_pattern, $exploded[2], $kill_match)) {
                    if (isset($known_players[$kill_match['killer']]) && isset($known_players[$kill_match['victim']])) {
                        $killer = $known_players[$kill_match['killer']];
                        $victim = $known_players[$kill_match['victim']];

                        if($kill_match['killer'] != $kill_match['victim']) {
                            $stats[$killer]['KILLS']['Frags']++;
                            $stats[$killer]['VICTIMS'][$victim]++;
                            $stats[$killer]['WEAPONS'][$kill_match['weapon']]++;

                            $stats[$victim]['KILLS']['Deaths']++;
                            $stats[$victim]['ENEMIES'][$killer]++;

                            increaseStreak( $stats, $killer, 'kill' );
                            increaseStreak( $stats, $victim, 'death' );
                        } else {
                            $stats[$killer]['KILLS']['Suicides']++;
                            $stats[$killer]['WEAPONS'][$kill_match['weapon']]++;

                            increaseStreak( $stats, $victim, 'death' );
                        }
                    }
                }
            break;
        }

    }
    $size += filesize($log);
    fclose($handle);
}

//Remove current streak counters
removeCurrentStreaks( $stats );

// Sort by name
uksort($stats, 'strnatcmp');

foreach($stats as $player => $info) {
    // Sort awards
    arsort($stats[$player]['AWARDS']);

    // Sort weapons
    arsort($stats[$player]['WEAPONS']);

    // Sort victims
    arsort($stats[$player]['VICTIMS']);

    // Sort enemies
    arsort($stats[$player]['ENEMIES']);

    // Calculate K/D ratio
    if ($stats[$player]['KILLS']['Frags'] > 0 && $stats[$player]['KILLS']['Deaths'] > 0) {
        $stats[$player]['KILLS']['Ratio'] = number_format($stats[$player]['KILLS']['Frags'] / $stats[$player]['KILLS']['Deaths'], 2);
    }

    // Calculate average position
    $position_total=0;
    $position_count=0;
    foreach($stats[$player]['RANKINGS'] as $position => $count) {
	    $position_total += ($position+1)*$count;
	    $position_count += $count;
    }
    if($position_count>0) {
	    $stats[$player]['KILLS']['Position'] = number_format($position_total/$position_count, 2);
    }
}

$endtime    = microtime(true);
$totaltime  = ($endtime - $starttime);
