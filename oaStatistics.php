<?php

class oaStatistics {
    private $logPath = 'logfiles/';
    private $gamePath = 'games/';
    private $playerMap = array();
    private $unknownTypes = array();
    private $currentGame = array();
    private $regenerate = false;
    public $logFileCount = 0;
    public $gameCount = 0;

    public $gameTypes = array(
            0 => 'GT_FFA',						// Deathmatch
            1 => 'GT_TOURNAMENT',				// 1 on 1
            2 => 'GT_SINGLE_PLAYER', 			// GT_SINGLE_PLAYER            
    		3 => 'GT_TEAM',						// Team deathmatch
            4 => 'GT_CTF',						// Capture the flag
            5 => 'GT_1FCTF',					// GT_1FCTF
    		6 => 'GT_OBELISK',					// GT_OBELISK
    		7 => 'GT_HARVESTER',				// GT_HARVESTER    		
    );

    const WORLD = 1022;
    
    public $weapons = array(
    		0 => "MOD_UNKNOWN",
    		1 => "MOD_SHOTGUN",
    		2 => "MOD_GAUNTLET",
    		3 => "MOD_MACHINEGUN",
    		4 => "MOD_GRENADE",
    		5 => "MOD_GRENADE_SPLASH",
    		6 => "MOD_ROCKET",
    		7 => "MOD_ROCKET_SPLASH",
    		8 => "MOD_PLASMA",
    		9 => "MOD_PLASMA_SPLASH",
    		10 => "MOD_RAILGUN",
    		11 => "MOD_LIGHTNING",
    		12 => "MOD_BFG",
    		13 => "MOD_BFG_SPLASH",
    		14 => "MOD_WATER",
    		15 => "MOD_SLIME",
    		16 => "MOD_LAVA",
    		17 => "MOD_CRUSH",
    		18 => "MOD_TELEFRAG",
    		19 => "MOD_FALLING",
    		20 => "MOD_SUICIDE",
    		21 => "MOD_TARGET_LASER",
    		22 => "MOD_TRIGGER_HURT",
    		23 => "MOD_NAIL",
    		24 => "MOD_CHAINGUN",
    		25 => "MOD_PROXIMITY_MINE",
    		26 => "MOD_KAMIKAZE",
    		27 => "MOD_JUICED",
    		28 => "MOD_GRAPPLE"
   	);
    
    public $awards = array(
    		0 => 'GAUNTLET',
    		1 => 'EXCELLENT',
    		2 => 'IMPRESSIVE',
    		3 => 'DEFENCE',
    		4 => 'CAPTURE',
    		5 => 'ASSIST',
    );
    
    public $ctfTypes = array(
    		0 => 'PICKUP',
    		1 => 'CAPTURE',
    		2 => 'RETURN',
    		3 => 'KILL_CARRIER'		
   	);
    
    public $teams = array(
    		0 => 'TEAM_FREE',
    		1 => 'TEAM_RED',
    		2 => 'TEAM_BLUE',
    		3 => 'TEAM_SPECTATOR'
    		
    		);

    private $handlers = array(
    		'InitGame' => 'setCurrentGame',
    		'ClientUserinfoChanged' => 'setPlayer',
    		'ClientBegin' => 'playerStart',
    		'ClientDisconnect' => 'playerEnd',
    		'Exit' => 'endCurrentGame',
    		'ShutdownGame' => 'finishCurrentGame',
    		'broadcast' => 'handleBroadcast',
    		'score' => 'handleScore',
    		'Kill' => 'handleKill',
    		'Item' => 'handleItem',
    		'Award' => 'handleAward',
    		'CTF' => 'handleCTF',
    		'red' => 'handleCTFResult',
    		'PlayerScore' => 'handlePlayerScore',
    
    );    
    
    public function __construct($logPath = null, $gamePath = null) {
    	if($logPath) {
        	$this->logPath = rtrim(trim($logPath), '/');
    	} 
    	if($gamePath) {
    		$this->gamePath = rtrim(trim($gamePath), '/');
    	}
    	
    	if(!file_exists($this->logPath)) {
    		$msg = "Log Path doesn't exist : ".$logPath;
    		if(!$this->silent) echo $msg."\n";
    		throw Exception($msg);    		
    	}
    	if(!file_exists($this->gamePath)) {
    		$msg = "Game Path doesn't exist : ".$logPath;
    		if(!$this->silent) echo $msg."\n";
    		throw Exception($msg);
    	}
    	 
    }

    public function process($minDate = false, $options = array()) {
        $dir = dir($this->logPath);
        
        if($minDate) {
        	$minTimestamp = strtotime($minDate.' 00:00:00');
        }
        
        $this->regenerate = @$options['regenerate'] ? true : false;
        $this->silent = @$options['silent'] ? true : false;
        
        while($entry = $dir->read()) {
            if(preg_match("/^openarena_(\d+-\d+-\d+)\.log/", $entry, $match)) {
            	
            	if($minDate && $minTimestamp > strtotime($match[1].' 00:00:00')) {
            		continue;
            	}
            	
            	$this->logfileCount++;
                $this->parseLog($entry, $match[1]);
            }
        }
    }

    private function parseLog($entry, $date) {
        if(!$this->silent) echo "Parsing ".$entry."\n";

        $this->playerMap = array();
        $f = fopen($this->logPath.'/'.$entry, 'r');
        $this->lineCount = 0;
        while($line = fgets($f, 4096)) {
            $this->lineCount++;
            try {
                $this->parseLine($line);
            } catch(ParseException $e) {}
        }

       
    }
    
    private function parseLine($line) {
        list($timestamp, $logLine) = $this->split("|", $line);
        if(!is_numeric($timestamp)) return;
        list($type, $data) = $this->split(":", $logLine);

       
        if(isset($this->handlers[$type])) {
        	$handler = $this->handlers[$type];
        	$this->{$handler}($data, $timestamp);
        } else {
        	$this->setUnknownTypes($type);
        }
    }

    private function split($delim, $line) {
        $parts = explode($delim, $line, 2);
        if(count($parts) != 2) {
            throw new ParseException();
        }
        else return $parts;
    }


    private function getGameFilename($timestamp) {
    	return $this->gamePath.'game_'.date('YmdHis', $timestamp).'.log';
    }
    
    /**
     * Detect current game type and map (and set starttime for now.. usually within one second of AAS. initialized, or maybe should be when first player is connected.)
     */
    private function setCurrentGame($data, $timestamp) {
    	if(!$this->regenerate && file_exists($this->getGameFilename($timestamp))) {
    	//	if(!$this->silent) echo "Skipping game ".$this->getGameFilename($timestamp)."\n";
    		$this->currentGame = array();
    		$this->playerMap = array();
    		return;
    	} else {
    		if(!$this->silent) echo "Processing game ".$timestamp."\n";
    	}
    	
    	$this->gameCount++;
    	
    	// If game has no end after 4 hours, write a file..
    	if(count($this->currentGame) && $timestamp < (time()-14400)) {
    		
    		$file = $this->getGameFilename($this->currentGame['timestamp']);
    		$this->finishCurrentGame('No end detected.', $timestamp);
    	}
    	
        $this->currentGame = array('timestamp' => $timestamp, 'start' => 0, 'map' => 'unknown', 'type' => 'unknown', 'players' => array(), 'score' => array(), 'kills' => array(), 'awards' => array(), 'CTF' => array());
        $this->playerMap = array();

        if(preg_match('/gametype.([^\\\\]+).*mapname.([^\\\\]+)./', $data, $match)) {
                $this->currentGame['map'] = $match[2];
                $this->currentGame['type'] = $match[1];
        }
        // \com_protocol\71\sv_hostname\Spil OA Server\sv_maxclients\16\sv_minPing\0\sv_maxPing\400\sv_maxRate\25000\sv_allowdownload\1\sv_privateClients\2\capturelimit\5\timelimit\0\fraglimit\15\g_allowvote\1\g_voteGametypes\/0/3/4/5/8/10\g_delagHitscan\1\dmflags\88\g_instantgib\0\g_rockets\0\sv_dlRate\100\g_voteMaxTimelimit\1000\g_voteMinTimelimit\0\g_voteMaxFraglimit\0\g_voteMinFraglimit\0\g_gametype\0\g_lms_mode\0\elimination_roundtime\120\g_doWarmup\0\videoflags\7\g_maxGameClients\0\sv_minRate\0\sv_floodProtect\1\version\ioq3 1.36+svn2224-3/Debian linux-x86_64 Mar 31 2012\com_gamename\Quake3Arena\mapname\am_galmevish2\gamename\baseoa\elimflags\0\voteflags\0\g_needpass\0\g_obeliskRespawnDelay\10\g_enableDust\0\g_enableBreath\0\g_altExcellent\0\g_timestamp\2012-05-07 13:52:36
    }

    /**
     * Game ended with EXIT (frag limit or CTF cap), log game end time. 
     */
    private function endCurrentGame($data, $timestamp) {
        if(!count($this->currentGame)) return;
        $this->currentGame['end'] = $timestamp;
        
        $data = trim($data);
        if(strlen($data)) {
        	$this->currentGame['endreason'] = $data;
        }
    }

    /**
     * Game ended. Finish up and push the stats to the tree.
     * @param unknown_type $data
     * @param unknown_type $timestamp
     */
    private function finishCurrentGame($data, $timestamp) {
        if(!count($this->currentGame)) return;

        $data = trim($data);
        if(strlen($data)) {
            $this->currentGame['endreason'] = $data;
        }
        if(!isset($this->currentGame['end'])) {
            $this->currentGame['end'] = $timestamp;
        }

        if(!isset($this->currentGame['start'])) {
            if(!$this->silent) echo  "no game start ".$this->lineCount."\n";
        }
        $this->currentGame['duration']=$timestamp - $this->currentGame['start'];

        foreach(array_keys($this->playerMap) as $player) {
            $this->playerEnd($player, $timestamp);
        }


        if(!isset($this->currentGame['endreason']) && $this->currentGame['voted']) {
        	$this->currentGame['endreason']="Vote passed.";
        }
        
        $file = $this->getGameFilename($this->currentGame['timestamp']);
        
        if($data == 'No end detected.') {
        	$comment = "/* Game not properly ended. Data not used.\n\n";
        } else {
        	$comment = "";
        }
        
        file_put_contents($file, "<?php\n$comment\$games[".$this->currentGame['timestamp']."]=".var_export($this->currentGame, true).";\n");
        
        if(!$this->silent) echo "Saved ".$file."\r\n";
        
        $this->currentGame = array();
        $this->playerMap = array();
    }

    /**
     * Initialize or update player data / mapping
     */
    private function setPlayer($data, $timestamp) {
    	if(!count($this->currentGame)) return;
        // 2 n\Enrique\t\0\model\gargoyle/stone\hmodel\gargoyle/stone\g_redteam\\g_blueteam\\c1\4\c2\5\hc\100\w\0\l\0\tt\0\tl\0\id\2EF47499FF1CAD2316F9FD0918CDDAAA
        	
        if(preg_match('/ (\d+) n.([^\\\\]+)\\\\t\\\\(\d+).model.([^\\\\]+).*id.(.+)$/', $data, $match)) {
        	list($_, $index, $nickname, $team, $model, $id) = $match;

          	// Only record teams for team games.
            if($this->currentGame['type'] < 3) {
            	$team = 0; 
            }
            	
            if(!isset($this->currentGame['players'][$id]))
            {
            	$this->currentGame['players'][$id] = array(
                            'id' => $id,
                			'team' => $team,
                            'nickname' => $nickname,
            				'model' => $model,
            				'start' => 0,
            				'end' => 0,
            				'duration' => 0,
            				'score' => 0,
            				'kills' => array(),
            				'killedby' => array(),
            				'teamkills' => array(),
            				'teamswitch' => array(),
            				'ctf' => array(),
            				'scoreInfo' => array()
                        );
            } else {
            	if($this->currentGame['players'][$id]['team'] != $team) {
            		
            		$this->currentGame['players'][$id]['teamswitch'][] = array('timestamp' => $timestamp, 'oldteam' => $this->currentGame['players'][$id]['team'], 'newteam'=> $team);
            		$this->currentGame['players'][$id]['team'] = $team;
            	}
            	
            }
            	
            // Update mapping
            $this->playerMap[$index] = $id;
		}    
    }

    /**
     * Start the clock for a player (or the map, for the first one)
     */
    private function playerStart($data, $timestamp) {
    	if(!count($this->currentGame)) return;
    	
    	// Game starts on first connect.
    	if(!$this->currentGame['start']) {
    		$this->currentGame['start']=$timestamp;
    	}
    	
    	$id = @$this->playerMap[trim($data)];
    	if(!$id) return;
    	
    	// Just continue if we had no end but we're already started.
    	if($this->currentGame['players'][$id]['start'] && !$this->currentGame['players'][$id]['end']) return;
    	
        $this->currentGame['players'][$id]['start']=$timestamp;
        $this->currentGame['players'][$id]['end']=false;
    }

    /**
     * End player
     */
    private function playerEnd($data, $timestamp) {
    	if(!count($this->currentGame)) return;
    	
        $id = @$this->playerMap[trim($data)];
        if(!$id) return;
        
        // Prevent ending twice if the player is already ended or ending if not started..
        if(!$this->currentGame['players'][$id]['start'] || $this->currentGame['players'][$id]['end']) return;
        
        $this->currentGame['players'][$id]['end']=$timestamp;
        $duration = $timestamp - $this->currentGame['players'][$id]['start'];

        // Add duration if the player has been disconnected duroin play
        if(!isset($this->currentGame['players'][$id]['duration'])) {
            $this->currentGame['players'][$id]['duration'] = $duration;
        } else {
            $this->currentGame['players'][$id]['duration'] += $duration;
        }
    }

    private function setUnknownTypes($type) {
        if(isset($this->unknownTypes[$type]) ) {
            $this->unknownTypes[$type]++;
        } else {
            $this->unknownTypes[$type] = 1;
        }
    }

    private function handleBroadCast($data, $timestamp) {
        if(!count($this->currentGame)) return;
        
        if(strpos($data, 'Vote passed')) {
            $this->currentGame['voted'] = true;
        }
    }

    private function handleScore($data, $timestamp) {
        if(!count($this->currentGame)) return;

        if(preg_match('/^ (\d+)  ping: \d+  client: (\d+) (.*)$/', $data, $match)) {
        	list($_, $score, $index, $nickname) = $match;
            $playerId = $this->playerMap[$index];

            if(!isset($this->currentGame['score'])) {
                $this->currentGame['score'] = array();
                $position = 1;
            } else {
            	// Some logic to detect shared places. for example if 3 people have the same highest score, there would be 3 players with position 1, but the 4th will still be in 4th place. (no 2nd or 3rd..)
            	$lastScoreRecord = end($this->currentGame['score']);
            	$lastPosition = $lastScoreRecord['position'];
            	$lastScore = $lastScoreRecord['score'];
            	if($lastScore == $score) {
            		$position = $lastPosition;
            	} else {
            		$position = count($this->currentGame['score'])+1;
            	}
            }

            
            $lastScore = end($this->currentGame['score']);
            
            $scoreRecord = array(
                        'position' => $position,
                        'id' => $playerId,
                        'nickname' => $nickname,
                        'score' => $score
                    );

            $this->currentGame['score'][] = $scoreRecord;
            $this->currentGame['players'][$playerId]['position'] = $scoreRecord['position'];
            $this->currentGame['players'][$playerId]['score'] = $scoreRecord['score'];

        }
    }

    private function handleKill($data, $timestamp) {
    	if(!$this->currentGame) return;
    	
    	if(preg_match("/(\d+) (\d+) (\d+):/", trim($data), $match)) {
    		list($_, $killer, $victim, $weapon) = $match;
    		
    		if($killer == self::WORLD) {
    			$suicide = true;
    			$killerId = "SELF";
    		} else {
    			$suicide = false;
    			$killerId = isset($this->playerMap[$killer]) ? $this->playerMap[$killer] : '';
    			$killerRecord =& $this->currentGame['players'][$killerId];
    		}

    		$victimId = isset($this->playerMap[$victim]) ? $this->playerMap[$victim] : '';
    		$victimRecord =& $this->currentGame['players'][$victimId];
    		
    		$killRecord = array('killer' => $killerId, 'victim' => $victimId, 'weapon' => $weapon, 'timestamp' => $timestamp);    		
    		$this->currentGame['kills'][] =& $killRecord;
    	
    		if($suicide) {
    			!isset($victimRecord['suicide'][$weapon]) ?  $victimRecord['suicide'][$weapon]=1 : $victimRecord['suicide'][$weapon]++;
    			return;    			     			
    		} 
    		    		
    		if($this->currentGame['type'] >= 3) {
    			$killRecord['teamkill'] = ($this->currentGame['players'][$killerId]['team'] == $this->currentGame['players'][$victimId]['team']);    			    			
    		} 
    		
    		if($this->currentGame['type'] >= 3 && $killRecord['teamkill']) {
    			!isset($killerRecord['teamkills'][$victimId]) ? $killerRecord['teamkills'][$victimId]=1 : $killerRecord['teamkills'][$victimId]++;
    		} else {
    			!isset($killerRecord['kills'][$victimId]) ? $killerRecord['kills'][$victimId]=1 : $killerRecord['kills'][$victimId]++;
    			!isset($victimRecord['killedby'][$killerId]) ? $victimRecord['killedby'][$killerId]=1 : $victimRecord['killedby'][$killerId]++;
    			!isset($killerRecord['killweapon'][$weapon]) ? $killerRecord['killweapon'][$weapon]=1 : $killerRecord['killweapon'][$weapon]++;
    			!isset($victimRecord['killedbyweapon'][$weapon]) ? $victimRecord['killedbyweapon'][$weapon]=1 : $victimRecord['killedbyweapon'][$weapon]++;    			 
    		}
    	}
    }
    
    private function handleItem($data, $timestamp) {
    	if(!$this->currentGame) return;
    	if(preg_match("/^(\d+) (.*)$/", trim($data), $match)) {
    		list($_, $player, $item) = $match;
    		$playerId = $this->playerMap[$player];
    		$playerRecord =& $this->currentGame['players'][$playerId];
    		!isset($playerRecord['items'][$item]) ? $playerRecord['items'][$item] = 1 : $playerRecord['items'][$item]++;
    		
    	}
    }
    
    private function handleAward($data, $timestamp) {
    	if(!$this->currentGame) return;
    	
    	if(preg_match("/^(\d+) (\d+):.*$/", trim($data), $match)) {
    		list($_, $player, $award) = $match;
    		$playerId = $this->playerMap[$player];
    		$playerRecord =& $this->currentGame['players'][$playerId];
    		!isset($playerRecord['awards'][$award]) ? $playerRecord['awards'][$award] = 1 : $playerRecord['awards'][$award]++;
    		$awardRecord = array('timestamp' => $timestamp, 'player' => $playerId, 'award' => $award);
    		$this->currentGame['awards'][]=$awardRecord;
    
    	}
    }
    
    private function handleCTF($data, $timestamp) {
    	if(!$this->currentGame) return;
    	 
    	if(preg_match("/^(\d+) (\d+) (\d+):.*$/", trim($data), $match)) {
    		list($_, $player, $team, $type) = $match;
    		$playerId = $this->playerMap[$player];
    		$playerRecord =& $this->currentGame['players'][$playerId];
    		!isset($playerRecord['ctf'][$type]) ? $playerRecord['ctf'][$type] = 1 : $playerRecord['ctf'][$type]++;
    		$ctfRecord = array('timestamp' => $timestamp, 'team' => $team, 'type' => $type, 'player' => $playerId);
    		$this->currentGame['ctf'][]=$ctfRecord;
    
    	}
    }    
    
    
    private function handleCTFResult($data, $timestamp) {
    	if(!$this->currentGame) return;
    
    	if(preg_match("/^(\d+).*blue:(\d+)$/", trim($data), $match)) {
    		list($_, $redScore, $blueScore) = $match;
    		$this->currentGame['ctfResult']=array(1 => $redScore, 2 => $blueScore);
    
    	}
    }
    
    private function handlePlayerScore($data, $timestamp) {
    	if(!$this->currentGame) return;
    	if(preg_match("/^(\d+) (\d+):.*$/", trim($data), $match)) {
    		list($_, $player, $points) = $match;
    		$playerId = $this->playerMap[$player];
    		$playerRecord =& $this->currentGame['players'][$playerId];
    		
    		if(!isset($playerRecord['scoreInfo']['current'])) {
    			$playerRecord['scoreInfo'] = array (
    					'current' => $points,
    					'gained' => ($points > 0 ? $points : 0),
    					'lost' => ($points < 0 ? -$points : 0),
    					'highest' => ($points > 0 ? $points : 0),
    					);
    		} else {
    			if($points > $playerRecord['scoreInfo']['highest']) {
    				$playerRecord['scoreInfo']['highest'] = $points;
    			} 
    			$diff = $points - $playerRecord['scoreInfo']['current'];
    			if($diff > 0) {
    				$playerRecord['scoreInfo']['gained'] += $diff;
    			} 
    			if($diff < 0) {
    				$playerRecord['scoreInfo']['lost'] -= $diff;
    			}
    			$playerRecord['scoreInfo']['current'] = $points;
    		}
    	}    	
    }
}

class ParseException extends Exception {}

$stats = new oaStatistics('./logfiles');

$minDate = false;
$regen = false;

foreach($argv as $arg) {
	if(preg_match('/\d{4}-\d{1,2}-\d{1,2}/', $arg)) {
		$minDate = $arg;
	}
	
	$options = array();
	if($arg == '-r') {
		$options['regenerate'] = true;
	}
	if($arg == '-s') {
		$options['silent'] = true;
	}
}


$stats->process($minDate, $options);

?>