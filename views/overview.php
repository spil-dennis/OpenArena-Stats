<?php include('header.php');

foreach($stats as $player => &$p) {
	$sort[$player] = $p['ratio'];
}
arsort($sort);

?>
<div id="content">
            <section id="overview" class="kill">
<table id="overview">
	<thead>
		<th>Player</th>
		<th>Ratio</th>
		<th>Rating</th>
		<th>Frags</th>
		<th>Deaths</th>
		<th>Suicides</th>
		<th>FPM</th>
		<th>DPM</th>
		<th>FPG</th>
		<th>DPG</th>
		<th>Games</th>
		<th>Weapon</th>

	</thead>
	<tbody>
<?php foreach($sort as $player => $ratio) {
$p = &$stats[$player];

	?>
<tr>
	<td> <a href="?player=<?php echo $player; ?><?php echo $queryStart ?>"><?php echo $player; ?></a></td>
	<td><?php echo round($p['ratio'],2) ?></td>
	<td><?php echo round($p['rating'],2) ?></td>
	<td><?php echo $p['kills'] ?></td>
	<td><?php echo $p['killedby'] ?></td>
	<td><?php echo $p['suicides'] ?></td>
	<td><?php echo round($p['killsperminute'],2) ?></td>
	<td><?php echo round($p['deathsperminute'],2) ?></td>
	<td><?php echo round($p['avgkillspergame'],2) ?>(<?php echo $p['maxkillspergame']?>)</td>
	<td><?php echo round($p['avgkilledbypergame'],2) ?>(<?php echo $p['maxkilledbypergame']?>)</td>
	<td><?php echo $p['games'] ?></td>
	<td><?php echo reset(array_keys($p['kill_weapons'])) ?>(<?php echo reset($p['kill_weapons']) ?>)</td>


	</tr>
<?php } ?>
	</tbody>

</table>
</section>

<section class="kill">
            	<div id="legend"></div>
            	<div id="ratiochart"></div>

            	<?php
            		$dt =array();
            		foreach($stats as $player => &$p) {

            			foreach($p['ratio_time'] as $date => $value) {
            				$d = strtotime($date.' 00:00:00');
            				$dt[$d][$player] = $value;
            				$series[$player][$d]="{x:$d, y:$value}";
            			}
            		}

            		ksort($dt);

            		$lastplayer = $player;
            		$pd = 0;
            		foreach(array_keys($dt) as $d) {
            			foreach($series as $player => &$v) {
            				if(!isset($v[$d])) {
								$dt[$d][$player]=(isset($dt[$pd][$player]) ? $dt[$pd][$player] : "0");
            					$v[$d]="{x:$d, y:".$dt[$d][$player]."}";
            				}
            			}
            			$pd = $d;
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
					<?php foreach($series as $player => $data) { ksort($data);?>
						{
						    color: palette.color(),
						    name: '<?php echo $player; ?>',
						    data: [	<?php echo implode(",\r\n", $data); ?> ]
						}<?php echo $lastplayer != $player ? ",\r\n"  : "\r\n" ?>
					<?php } ?>
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


</div>

<?php include('footer.php'); ?>