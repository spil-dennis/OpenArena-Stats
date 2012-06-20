<?php include('header.php'); ?>
<?php  
        
	$p =& $stats[$currentPlayer];
        
    $showStats = array(
        			array('Nickname', "%s", array('nickname')),
        			array('Model', "%s", array('model')),
        			array('Games', "%d", array('games')),
        			array('Frags', "%d", array('kills')),
        			array('Killed', "%d", array('killedby')),
        			array('Suicides', "%d", array('suicides')),
        			array('Teamkills', "%d", array('teamkills')),
        			array('Ratio', "%0.2f", array('ratio')),
        			array('FPM', "%0.2f", array('killsperminute')),
        			array('DPM', "%0.2f", array('deathsperminute')),
        			
        			array('Deathstreak', "%d", array('maxdeathstreak')),
        			array('Killstreak', "%d", array('maxkillstreak')),
        			array('Frags/Game', "%0.2f(%d)", array('avgkillspergame', 'maxkillspergame')),
        			array('Killed/Game', "%0.2f(%d)", array('avgkilledbypergame', 'maxkilledbypergame')),
        			array('Suicides/Game', "%0.2f(%d)", array('avgsuicidespergame', 'maxsuicidespergame')),
        			array('Rating', "%0.2f", array('rating')),
    );
?>
        <div id="content">
            <section id="kill" class="kill">
                <div id="kills">
                    <h3>Kill stats</h3>
                    <table>
                    <?php
                    foreach($showStats as $config) {
                    	$params=array($config[1]);
                    	foreach($config[2] as $f) {
                    		$params[] = $p[$f];
                    	}
                    	$value = call_user_func_array("sprintf", $params);
                        echo '<tr><td class="stat">'. $config[0] .'</td><td>'. $value .'</td></tr>';
                    }
                    ?>
                    </table>
                </div>
                <div id="victims">
                    <h3>You killed</h3>
                    <table>
                    <?php
                    foreach($p['kill_who'] as $victim => $amount) {
                        echo '<tr><td class="stat">'. $victim .'</td><td>'. $amount .'</td></tr>';
                    }
                    ?>
                    </table>
                </div>
                <div id="enemies">
                    <h3>Killed by</h3>
                    <table>
                    <?php
                    foreach($p['killedby_who'] as $enemy => $amount) {
                        echo '<tr><td class="stat">'. $enemy .'</td><td>'. $amount .'</td></tr>';
                    }
                    ?>
                    </table>
                </div>
                <div id="weapons" class="right">
                    <h3>Weapons</h3>
                    <table>
                    <?php
                    foreach($p['kill_weapons'] as $weapon => $amount) {
                        if($amount > 0) {
                            echo '<tr><td class="stat">'. $weapon .'</td><td>'. $amount .'</td></tr>';
                        }
                    }
                    ?>
                    </table>
                </div>
            </section>
            
            <section class="kill">
            	<div id="legend"></div>
            	<div id="ratiochart"></div>
            	
            	<?php 
            		function gval($metric, $date) {
            			global $p;
            			if(isset($p[$metric][$date])) return $p[$metric][$date];
            			else return 0; 
            		}

            		$dpm = $kpm = $awards = $ratios = $frags = $deaths = $suicides = array();
            		foreach($p['duration_time'] as $date => $duration) {
            			$x = strtotime($date.' 00:00:00');
            			
            			if($duration < 300) continue;
            			if(!gval('kill_time', $date) && !gval('killedby_time', $date) ) {
            				continue;
            			}	
            			
            			$ratios[]= "{x: ".$x.", y:".(gval('ratio_time', $date)*100)."}";
            			$frags[]= "{x: ".$x.", y:".gval('kill_time', $date)."}";
            			$deaths[]= "{x: ".$x.", y:".gval('killedby_time', $date)."}";
            			$suicides[]= "{x: ".$x.", y:".gval('suicide_time', $date)."}";
            			$awards[]= "{x: ".$x.", y:".gval('awards_time', $date)."}";
            			$dpm[]= "{x: ".$x.", y:".(gval('killsperminute_time', $date)*50)."}";
            			$kpm[]= "{x: ".$x.", y:".(gval('deathsperminute_time', $date)*50)."}";
            			
            		}
            	
				?>
            	<script> 
            	var palette = new Rickshaw.Color.Palette( { scheme: 'classic9' } );
				var graph = new Rickshaw.Graph( {
				    element: document.querySelector("#ratiochart"), 
				    width: 600, 
				    height: 200,
				    renderer: 'line',
				    series: [
						{					    
						    color: palette.color(),
						    name: 'DPM',
						    data: [	<?php echo implode(",\r\n", $dpm); ?> ]
						},
						{					    
					        color: palette.color(),
					        name: 'KPM',
					        data: [	<?php echo implode(",\r\n", $kpm); ?> ]
				    	},
						
						
				    	
				    	{					    
					        color: palette.color(),
					        name: 'Suicides',
					        data: [	<?php echo implode(",\r\n", $suicides); ?> ]
				    	},
				    	{					    
					        color: palette.color(),
					        name: 'Awards',
					        data: [	<?php echo implode(",\r\n", $awards); ?> ]
				    	},
				    	{					    
					        color: palette.color(),
					        name: 'Deaths',
					        data: [	<?php echo implode(",\r\n", $deaths); ?> ]
				    	},
				    	{					    
					        color: palette.color(),
					        name: 'Frags',
					        data: [	<?php echo implode(",\r\n", $frags); ?> ]
				    	},
				    	{					    
					        color: palette.color(),
					        name: 'Ratio',
					        data: [	<?php echo implode(",\r\n", $ratios); ?> ]
				    	},
				    	
				    	
				    	
						
				    	]
				});


				var hoverDetail = new Rickshaw.Graph.HoverDetail( {
					graph: graph,
					formatter: function(series, x, y) {
						var value;
						var date = '<span class="date">' + new Date(x * 1000).toDateString() + '</span>';
						var swatch = '<span class="detail_swatch" style="background-color: ' + series.color + '"></span>';
						if(series.name == 'Ratio') value = (y / 100);
						else if(series.name == 'DPM' || series.name == "KPM") value = (y / 50);
						else value = y;
						
						var content = swatch + series.name + ": " + value + '<br>' + date;
						return content;
					},
					xFormatter: function(x) { return new Date(x * 1000).toDateString() },
				} );
				 
				
				var yAxis = new Rickshaw.Graph.Axis.Y({
				    graph: graph,
				    tickFormat: Rickshaw.Fixtures.Number.formatKMBT
				});

				var xAxis = new Rickshaw.Graph.Axis.Time( {
					graph: graph
				} );

				var legend = new Rickshaw.Graph.Legend({
				    graph: graph,
				    element: document.querySelector('#legend')
				});

				var shelving = new Rickshaw.Graph.Behavior.Series.Toggle({
				    graph: graph,
				    legend: legend
				});

				var highlighter = new Rickshaw.Graph.Behavior.Series.Highlight({
				    graph: graph,
				    legend: legend
				});
				
				graph.render();
				xAxis.render();
				yAxis.render();
				
				
				</script>
            
            </section>

            <section id="awards">
                <h3>Awards</h3>
                <div><img src="images/excellent.png" alt="Awarded when the player gains two frags within two seconds." title="Awarded when the player gains two frags within two seconds." width="65" height="65" /><span><?php echo (@$p['awards']['EXCELLENT'] ? $p['awards']['EXCELLENT'] : 0); ?></span></div>
                <div><img src="images/impressive.png" alt="Awarded when the player achieves two consecutive hits with the railgun." title="Awarded when the player achieves two consecutive hits with the railgun." width="65" height="65" /><span><?php echo (@$p['awards']['IMPRESSIVE'] ? $p['awards']['IMPRESSIVE'] : 0); ?></span></div>
                <div><img src="images/gauntlet.png" alt="Awarded when the player successfully frags someone with the gauntlet." title="Awarded when the player successfully frags someone with the gauntlet." width="65" height="65" /><span><?php echo (@$p['awards']['GAUNTLET'] ? $p['awards']['GAUNTLET'] : 0); ?></span></div>
                <div><img src="images/capture.jpg" alt="Awarded when the player captures the flag." title="Awarded when the player captures the flag." width="65" height="65" /><span><?php echo (@$p['awards']['CAPTURE'] ? $p['awards']['CAPTURE'] : 0) ?></span></div>
                <div><img src="images/assist.jpg" alt="Awarded when player returns the flag within ten seconds before a teammate makes a capture." title="Awarded when player returns the flag within ten seconds before a teammate makes a capture." width="65" height="65" /><span><?php echo (@$p['awards']['ASSIST'] ? $p['awards']['ASSIST'] : 0); ?></span></div>
                <div><img src="images/defence.jpg" alt="Awarded when the player kills an enemy that was inside his base, or was hitting a team-mate that was carrying the flag." title="Awarded when the player kills an enemy that was inside his base, or was hitting a team-mate that was carrying the flag." width="65" height="65" /><span><?php echo (@$p['awards']['DEFENCE'] ? $p['awards']['DEFENCE'] : 0); ?></span></div>
            </section>

            <section id="ctf" class="kill">
					
				<div id="dmrank">
                	<h3>Fraglimit rankings</h3>
	                <table>
	                    <?php
	                    foreach($p['position'] as $stat => $amount) {
	                        echo '<tr><td class="stat">'. $stat .'</td><td>'. $amount .'</td></tr>';
	                    }
	                    ?>
	                </table>
                </div>
            
            
            	<div id="ctf">
                	<h3>CTF</h3>
	                <table>
	                    <?php
	                    ksort($p['ctf']);
	                    foreach($p['ctf'] as $stat => $amount) {
	                        echo '<tr><td class="stat">'. $stat .'</td><td>'. $amount .'</td></tr>';
	                    }
	                    ?>
	                </table>
                </div>
            

	            <div id="ctfrank" class="right">
	                <h3>Capturelimit Rankings</h3>
	                <table>
	                    <?php
	                    foreach($p['position_ctf'] as $stat => $amount) {
	                        echo '<tr><td class="stat">'. $stat .'</td><td>'. $amount .'</td></tr>';
	                    }
	                    ?>
	                </table>
	            </div>
            
            </section>
            
            <section id="mapratio" class="kill">
            <h3>Ratio by map</h3>
            <?php $chunks = array_chunk(array_keys($p['map_ratio']), ceil(count($p['map_ratio'])/3));  
            foreach($chunks as $chunk) { ?>
            <div>
            	<table>
	                    <?php
	                    foreach($chunk as $map) {
	                    	$ratio = $p['map_ratio'][$map];
	                        echo '<tr><td class="stat">'. $map .'</td><td>'. (is_numeric($ratio) ? round($ratio, 2) : $ratio).'</td></tr>';
	                    }
	                    ?>
                </table>          
            </div>
            
            <?php } ?>
            </section>
        </div>
<?php include('footer.php'); ?>