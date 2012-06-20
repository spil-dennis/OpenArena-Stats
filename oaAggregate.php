<?php

include 'oaMapper.php';

class oaAggregate {
    private $maps;
    private $players;
    private $dateStart;
    private $dateEnd;
    private $gameDir = 'games/';
    private $games;

    public function __construct($dateRange = null, $map = null) {
        if(!$dateRange) {
            $this->dateStart = (int)date('Ymd', mktime(0,0,0,date('m'), date('d'), date('Y'))-7*86400);
            $this->dateEnd = (int)date('Ymd', time());
        } elseif(is_array($dateRange)) {
            $this->dateStart = (int)$dateRange['start'];
            $this->dateEnd = (int)$dateRange['end'];
        }

        if(strpos($this->gameDir, '/') !== 0) {
            $this->gameDir = dirname(__FILE__).'/'.$this->gameDir;
        }
        
        if(is_array($map)) {
        	$this->maps=array();
        } elseif(strlen($map)) {
        	$this->maps=array($map);
        } else {
        	$this->maps=array();
        }
    }

    public function process() {
        $cwd = getcwd();

        $start = $this->dateStart;
        $end = $this->dateEnd;

        $dir = dir($this->gameDir);

        $games = array();

        while($entry = $dir->read()) {

            if(preg_match('/^game_(\d+).log$/', $entry, $match)) {
                $date = substr($match[1],0,8);
                if($date >= $start && $date <= $end) {
                    include($this->gameDir.$entry);
                }
            }
        }

        ksort($games);
        $this->games =& $games;

        $this->aggregate();
        
        return $this->players;

    }

    public function gameCount() {
    	return count($this->games);
    }
    
    public function aggregate() {
        foreach($this->games as &$game) {
        	if(count($this->maps) && in_array($game['map'], $this->maps)) {
        		continue;
        	}
        	
            foreach($game['players'] as &$player) {
                $this->processPlayer($player, $game);
            }
            
            if(isset($game['kills']))
            foreach($game['kills'] as &$kill) {
            	$this->processKill($kill, $game);
            }
            
            if(isset($game['awards']))
            foreach($game['awards'] as &$award) {
            	$this->processAwards($award, $game);
            }
            
            if(isset($game['ctf']))
            foreach($game['ctf'] as &$ctf) {
            	$this->processCTF($ctf, $game);
            }
            
            foreach($this->players as &$player) {
            	$this->postProcessGame($player, $game);
            }
            
        }
        
        foreach($this->players as &$player) {
        	$this->postProcess($player, $game);
        }
        
    }
    
    private function getMapname(&$game) {
    	return $game['map'].'('.(oaMapper::$gameTypes[$game['type']]).')';
    }
    
    private function postProcessGame(&$p, &$game) {
    	$name = $p['name'];
    	$t =& $this->temp[$name];
    	if($t['killsthisgame'] > $p['maxkillspergame']) {
    		$p['maxkillspergame'] = $t['killsthisgame'];
    	}
    	if($t['killedbythisgame'] > $p['maxkilledbypergame']) {
    		$p['maxkilledbypergame'] = $t['killedbythisgame'];
    	}
    	
    	if($t['suicidesthisgame'] > $p['maxsuicidespergame']) {
    		$p['maxsuicidespergame'] = $t['suicidesthisgame'];
    	}
    	
    	$mapname = $this->getMapname($game);
    	
    	$p['maps'][$mapname]['type'] = oaMapper::$gameTypes[$game['type']];
    	isset($p['maps'][$mapname]['games']) ? $p['maps'][$mapname]['games']++ : $p['maps'][$mapname]['games']=1; 
    	isset($p['maps'][$mapname]['kills']) ? $p['maps'][$mapname]['kills'] += $t['killsthisgame'] : $p['maps'][$mapname]['kills'] = $t['killsthisgame'];
    	isset($p['maps'][$mapname]['killedby']) ? $p['maps'][$mapname]['killedby'] += $t['killedbythisgame'] : $p['maps'][$mapname]['killedby'] = $t['killedbythisgame'];
    	isset($p['maps'][$mapname]['rating']) ? $p['maps'][$mapname]['rating']+=$t['rating'] : $p['maps'][$mapname]['rating']=$t['rating'];
    }
    
    private function postProcess(&$p, &$game) {
    	$p['rating'] = $p['rating']/$p['games'];
    	$p['avgkillspergame'] = round($p['kills'] / $p['games'],2);
    	$p['avgkilledbypergame'] = round($p['killedby'] / $p['games'],2);
    	$p['avgsuicidespergame'] = round($p['suicides'] / $p['games'],2);
    	if($p['killedby'] > 0) $p['ratio'] = round($p['kills'] / $p['killedby'],2);
    	
    	foreach($p['maps'] as $map => &$data) {
    		$p['map_ratio'][$map] = $data['killedby'] > 0 ? round($data['kills']/$data['killedby'],3) : 'infinite';
    		$data['rating'] = $data['rating']/$data['games'];
    		
    	}

    	ksort($p['position']);
    	ksort($p['position_ctf']);
    	arsort($p['kill_who']);
    	arsort($p['killedby_who']);
    	arsort($p['kill_weapons']);
    	arsort($p['killedby_weapons']);
    	arsort($p['teamkill_weapons']);
    	arsort($p['teamkilledby_weapons']);
    	arsort($p['suicide_weapons']);
    	arsort($p['map_ratio']);
    	
    	$p['killsperminute'] = round($p['kills'] / ($p['duration']/60),3);
    	$p['deathsperminute'] = round(($p['killedby']+$p['suicides']) / ($p['duration']/60),3);
    	
    	foreach($p['duration_time'] as $date => $seconds) {
    		
    		if(isset($p['killedby_time'][$date]) && $p['killedby_time'][$date] > 0) {
    			$p['ratio_time'][$date] = round($p['kill_time'][$date] / $p['killedby_time'][$date], 2);
    		} else {
    			if(@$p['kill_time'][$date] > 0) {
    				$p['ratio_time'][$date]='infinite';
    			}
    		}

    		$minutes=$seconds/60;
    		if($minutes > 0.0) {
	    		$p['killsperminute_time'][$date] = round(@$p['kill_time'][$date] / $minutes,3);
	    		$p['deathsperminute_time'][$date] = round( (@$p['killedby_time'][$date] + @$p['suicides'][$date]) / $minutes,3);
    		}
    	}
    	
    	
    	
    }
    
    private function processPlayer(&$player, &$game) {
    	if(!isset(oaMapper::$playermap[$player['id']])) {
    		$index = $player['nickname'];
    	} else {
    		$index = oaMapper::$playermap[$player['id']];
    	}
    
    
    	if(!isset($this->players[$index])) {
    		$this->temp[$index] = array(
    				'deathstreak' => 0,
    				'killstreak' => 0,
    				'killsthisgame' => 0,
    				'killedbythisgame' => 0,
    				'suicidesthisgame' => 0,
    				'rating' => 0.
    		);
    		
    		$this->players[$index] = array(
    				'name' => $index,
    				'id' => $player['id'],
    				'nickname' => $player['nickname'],
    				'model' => $player['model'],
    				'games' => 0,
    				'duration' => 0,
    				'rating' => 0,
    
    				'maxdeathstreak' => 0,
    				'maxkillstreak' => 0,
    				
    				'maxkillspergame' => 0,
    				'avgkillspergame' => 0,
    				'maxkilledbypergame' => 0,
    				'avgkilledbypergame' => 0,
    				'maxsuicidesbypergame' => 0,
    				'avgsuicidespergame' => 0,
    				
    				
    				'kills' => 0,
    				'killedby' => 0,
    				'teamkills' => 0,
    				'teamkilledby' => 0,
    				'suicides' => 0,

    				'ratio' => 0,
    				'killsperminute' => 0,
    				'deathsperminute' => 0,
    
    				'ctf' => array(),
    				'awards' => array(),
    				'position' => array(),
    				'position_ctf' => array(),
    				 
    
    				'duration_time' => array(),
    
    				'kill_who' => array(),
    				'killedby_who' => array(),
    				
    				'kill_weapons' => array(),
    				'killedby_weapons' => array(),
    				'teamkill_weapons' => array(),
    				'teamkilledby_weapons' => array(),
    				'suicide_weapons' => array(),
    
    				'kill_time' => array(),
    				'killedby_time' => array(),
    				'teamkill_time' => array(),
    				'teamkilledby_time' => array(),
    				'suicide_time' => array(),
    				'awards_time' => array(),
    				'ratio_time'=> array(),
    				'killsperminute_time' => array(),
    				'deathsperminute_time' => array()
    		);
    	}
    
    	$this->temp[$index]['killsthisgame'] = 0;
    	$this->temp[$index]['killedbythisgame'] = 0;
    	
    	$p =& $this->players[$index];
    	$p['games']++;
    	$p['duration'] += $player['duration'];
    	$date = date('Y-m-d', $game['timestamp']);
    	isset($p['duration_time'][$date]) ? $p['duration_time'][$date]+=$player['duration'] : $p['duration_time'][$date] = $player['duration'];
    	if(isset($player['position'])) {
    		
    		$rating = (count($game['score'])-$player['position'])/(count($game['score'])-1) *10;
    		
    		$this->temp[$index]['rating']=$rating;
    		
			$p['rating']+=$rating;
    		    		
    		if($game['type'] == 4) {
    			isset($p['position_ctf'][$player['position']]) ? $p['position_ctf'][$player['position']]++ : $p['position_ctf'][$player['position']]=1;
    		} else {
    			isset($p['position'][$player['position']]) ? $p['position'][$player['position']]++ : $p['position'][$player['position']]=1;
    		}
    	}
    }

    private function processAwards(&$award, &$game) {
    	$date = date('Y-m-d', $award['timestamp']);
    	$player = isset(oaMapper::$playermap[$award['player']]) ? oaMapper::$playermap[$award['player']] : $game['players'][$award['player']]['nickname'];		
		$p =& $this->players[$player];		
		$a = isset(oaMapper::$awards[$award['award']]) ? oaMapper::$awards[$award['award']] : $award['award'];
		isset($p['awards'][$a]) ? $p['awards'][$a]++ : $p['awards'][$a]=1;
		isset($p['awards_time'][$date]) ? $p['awards_time'][$date]++ : $p['awards_time'][$date]=1;		
    }

    private function processCTF(&$ctf, &$game) {
    	$date = date('Y-m-d', $ctf['timestamp']);
    	$player = isset(oaMapper::$playermap[$ctf['player']]) ? oaMapper::$playermap[$ctf['player']] : $game['players'][$ctf['player']]['nickname'];
    	$p =& $this->players[$player];
    	$a = isset(oaMapper::$ctfTypes[$ctf['type']]) ? oaMapper::$ctfTypes[$ctf['type']] : $ctf['type'];
    	isset($p['ctf'][$a]) ? $p['ctf'][$a]++ : $p['ctf'][$a]=1;
    }
    

    private function processScore(&$ctf, &$game) {
    	$date = date('Y-m-d', $ctf['timestamp']);
    	$player = isset(oaMapper::$playermap[$ctf['player']]) ? oaMapper::$playermap[$ctf['player']] : $game['players'][$ctf['player']]['nickname'];
    	$p =& $this->players[$player];
    	$a = isset(oaMapper::$ctfTypes[$ctf['type']]) ? oaMapper::$ctfTypes[$ctf['type']] : $ctf['type'];
    	isset($p['ctf'][$a]) ? $p['ctf'][$a]++ : $p['ctf'][$a]=1;
    }
    
    
    private function processKill(&$kill, &$game) {
    	// Killer
    	if($kill['killer'] == 'SELF') {
    		$killer = $kill['killer'];
    	} elseif(!isset(oaMapper::$playermap[$kill['killer']])) {	
    		$killer = $game['players'][$kill['killer']]['nickname'];
    	} else {
    		$killer = oaMapper::$playermap[$kill['killer']];
    	}
    	    	
    	// Victim
    	if(!isset(oaMapper::$playermap[$kill['victim']])) {
    		$victim = $game['players'][$kill['victim']]['nickname'];
    	} else {
    		$victim = oaMapper::$playermap[$kill['victim']];
    	}
    	
    	$date = date('Y-m-d', $kill['timestamp']);
    	
    	if($killer != 'SELF') {
    		$k =& $this->players[$killer];
    		$ktemp =& $this->temp[$killer];
    	}
    	$v =& $this->players[$victim];
    	$vtemp =& $this->temp[$victim];
    	
    	// Weapon
    	$weapon = isset(oaMapper::$weapon[$kill['weapon']]) ? oaMapper::$weapon[$kill['weapon']] : $kill['weapon'];

    	// Any death ends a kill streak and advances your deathstreak
    	$vtemp['killstreak']=0;
    	$vtemp['deathstreak']++;
    	if($vtemp['deathstreak'] > $v['maxdeathstreak']) $v['maxdeathstreak'] = $vtemp['deathstreak'];
    	
    	if($killer == 'SELF') {
    		$v['suicides']++;    		
    		!isset($v['suicide_weapons'][$weapon]) ? $v['suicide_weapons'][$weapon]=1 : $v['suicide_weapons'][$weapon]++;
    		!isset($v['suicide_time'][$date]) ? $v['suicide_time'][$date]=1 : $v['suicide_time'][$date]++;
    		$vtemp['suicidesthisgame']++;
    	} elseif(isset($kill['teamkill']) && $kill['teamkill'] == true) {
    		$k['teamkills']++;
    		!isset($k['teamkill_weapons'][$weapon]) ? $k['teamkill_weapons'][$weapon]=1 : $k['teamkill_weapons'][$weapon]++;
    		!isset($k['teamkill_time'][$date]) ? $k['teamkill_time'][$date]=1 : $k['teamkill_time'][$date]++;

    		$v['teamkilledby']++;
    		!isset($v['teamkilledby_weapons'][$weapon]) ? $v['teamkilledby_weapons'][$weapon]=1 : $v['teamkilledby_weapons'][$weapon]++;
    		!isset($v['teamkilledby_time'][$date]) ? $v['teamkilledby_time'][$date]=1 : $v['teamkilledby_time'][$date]++;
    	} else {
    		$ktemp['killsthisgame']++;
    		$vtemp['killedbythisgame']++;
    		
    		// Only kills advance your kill streak
    		$ktemp['killstreak']++;
    		$ktemp['deathstreak']=0;
    		if($ktemp['killstreak'] > $k['maxkillstreak']) $k['maxkillstreak'] = $ktemp['killstreak'];
    		
    		$k['kills']++;
    		!isset($k['kill_weapons'][$weapon]) ? $k['kill_weapons'][$weapon]=1 : $k['kill_weapons'][$weapon]++;
    		!isset($k['kill_time'][$date]) ? $k['kill_time'][$date]=1 : $k['kill_time'][$date]++;
    		!isset($k['kill_who'][$victim]) ? $k['kill_who'][$victim]=1 : $k['kill_who'][$victim]++;
    		
    		$v['killedby']++;
    		!isset($v['killedby_weapons'][$weapon]) ? $v['killedby_weapons'][$weapon]=1 : $v['killedby_weapons'][$weapon]++;
    		!isset($v['killedby_time'][$date]) ? $v['killedby_time'][$date]=1 : $v['killedby_time'][$date]++;
    		!isset($v['killedby_who'][$killer]) ? $v['killedby_who'][$killer]=1 : $v['killedby_who'][$killer]++;
    	}
    }
}

/*
$aggregator = new oaAggregate(array('start'=>20120511, 'end'=>20120518));
//$aggregator = new oaAggregate(array('start'=>20120501, 'end'=>20120525), 'Dennis');


$s = microtime(true);
$res  = $aggregator->process();
$e = microtime(true);

echo $e-$s."\n";

var_export($res);

*/

?>