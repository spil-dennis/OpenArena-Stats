<?php
$dates = array(
		'Alltime' => array('alltime', false),
		'Last 4 weeks' => array('4weeks', false),
);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>OpenArena :: Statistics</title>
    <link rel="stylesheet" href="css/reset.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/rickshaw.css">
    <script src="vendor/d3.min.js"></script>
	<script src="vendor/d3.layout.min.js"></script>
    <script src="vendor/jquery-1.7.2.min.js"></script>
    <script src="vendor/jquery-ui-1.8.21.custom.min.js"></script>
	<script src="vendor/rickshaw.js"></script>
</head>
<body>
    <div id="wrapper">
        <div id="main">
        <header>
            <h1>OpenArena Statistics</h1>
            <?php if (isset($currentPlayer) && is_string($currentPlayer) && array_key_exists($currentPlayer, $stats)) : ?>
                <h2><?php echo $currentPlayer; ?></h2>
            <?php else: ?>
                <h2>All&nbsp;</h2>
            <?php endif; ?>
            <nav id="nav_date">
                <?php foreach ($dates as $title => $times) : ?>
                <a <?php echo ($times[0]==$_GET['start'] ? 'class="highlight" ' : ''); ?>href="?<?php echo($times[0] ? 'start='.$times[0] : '') ?><?php ($times[1] ? '&end='.$times[1] : '') ?><?php echo $queryPlayer; ?>"><?php echo $title; ?></a>
                <?php endforeach; ?>
            </nav>
            <nav id="nav_player">
            	<a href="?<?php echo $queryStart ?>">All</a>
                <?php foreach ($players as $name) {
                	if(!isset($stats[$name])) continue;
                	?>
                    <a href="?player=<?php echo $name; ?><?php echo $queryStart ?>"><?php echo $name; ?></a>
                <?php } ?>
            </nav>
        </header>