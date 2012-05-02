<?php

/**
 * This method collects all logfiles from the logdir.
 */
function getLogFiles($logdir) {
    $files  = scandir($logdir);
    $logs   = array();

    foreach($files as $file) {
        if(preg_match('/openarena_([0-9-]+)\.log/', $file)) {
            $logs[] = $logdir .'/'. $file;
        }
    }

    return $logs;
}

/**
 * This method generates the player skeletons.
 */
function generatePlayerSkeletons($known_players) {
    $stats      = array();
    $players    = array();

    foreach($known_players as $nickname => $realname) {
        $players[$realname] = 0;
    }

    foreach($known_players as $nickname => $realname) {
        $filtered_players   = array_diff($players, array($realname));
        $stats[$realname]   = array(
            'AWARDS'    => array(
                'Excellent'     => 0,
                'Impressive'    => 0,
                'Gauntlet'      => 0,
                'Capture'       => 0,
                'Assist'        => 0,
                'Defence'       => 0,
            ),
            'CTF'       => array(
                'Flags taken'           => 0,
                'Flags captured'        => 0,
                'Flags returned'        => 0,
                'Flagcarriers killed'   => 0,
            ),
            'KILLS'     => array(
                'Frags'     => 0,
                'Deaths'    => 0,
                'Suicides'  => 0,
                'Ratio'     => 0.0,
            ),
            'VICTIMS'   => $filtered_players,
            'ENEMIES'   => $filtered_players,
            'WEAPONS'   => array(
                'MOD_UNKNOWN'           => 0,
                'MOD_SHOTGUN'           => 0,
                'MOD_GAUNTLET'          => 0,
                'MOD_MACHINEGUN'        => 0,
                'MOD_GRENADE'           => 0,
                'MOD_GRENADE_SPLASH'    => 0,
                'MOD_ROCKET'            => 0,
                'MOD_ROCKET_SPLASH'     => 0,
                'MOD_PLASMA'            => 0,
                'MOD_PLASMA_SPLASH'     => 0,
                'MOD_RAILGUN'           => 0,
                'MOD_LIGHTNING'         => 0,
                'MOD_BFG'               => 0,
                'MOD_BFG_SPLASH'        => 0,
                'MOD_WATER'             => 0,
                'MOD_SLIME'             => 0,
                'MOD_LAVA'              => 0,
                'MOD_CRUSH'             => 0,
                'MOD_TELEFRAG'          => 0,
                'MOD_FALLING'           => 0,
                'MOD_SUICIDE'           => 0,
                'MOD_TARGET_LASER'      => 0,
                'MOD_TRIGGER_HURT'      => 0,
                'MOD_NAIL'              => 0,
                'MOD_CHAINGUN'          => 0,
                'MOD_PROXIMITY_MINE'    => 0,
                'MOD_KAMIKAZE'          => 0,
                'MOD_JUICED'            => 0,
                'MOD_GRAPPLE'           => 0,
            ),
        );
    }

    return $stats;
}
