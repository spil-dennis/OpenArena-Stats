<?php

/*
$known_players = array(
        'Aecrim'        => 'Mircea',
        'Revell'        => 'Jeroen',
        'Enrique'       => 'Enrique',
        'dennis'        => 'Dennis',
        'Schoende'      => 'Sven',
        'Archangel'     => 'Sven',
        'ArchAngel'     => 'Sven',
        'TT'            => 'Thijs T',
        'Thz'           => 'Thijs Z',
        'Sitting Duck'  => 'Martin',
        'Tamas'         => 'Tamas',
        'Rolph'         => 'Rolph',
        'Gerb'          => 'Gerben',
);
*/

class oaMapper {
        public static $playermap = array(
            "978E21D180BF6D8EBEAA5D3897170E94" => "Jeroen",
            "2EF47499FF1CAD2316F9FD0918CDDAAA" => "Enrique",
            "0FF9D843C712925CF1023CDDE010CD83" => "Thijs T",
            "09B7CCF049BACED173FDB92196EBE8B3" => "Dennis",
            "2BEA6F12C7F6A6FA1D2B46B4B41D1C44" => "Thijs Z",
            "56B7D7C81BF4B99AB068B03D5357DB51" => "Sven",
        	"38954A04BB21D38BA7C75955EFBE446E" => "Sven",
            "7A788D51E4ED819681A5D70F9CF0BBFB" => "Gerben",
            "913EEAB8EE1A36416CB417D6374CFB07" => "Tamas",
            "8D9D8FAD284359ED36F095C64975783B" => "Rolph",
            "32A851D406DF2479F05FF32BD765C8E5" => "Mircea",
        );

        public static $gameTypes = array(
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

        public static $weapon = array(
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

        public static $awards = array(
                0 => 'GAUNTLET',
                1 => 'EXCELLENT',
                2 => 'IMPRESSIVE',
                3 => 'DEFENCE',
                4 => 'CAPTURE',
                5 => 'ASSIST',
        );

        public static $ctfTypes = array(
                0 => 'PICKUP',
                1 => 'CAPTURE',
                2 => 'RETURN',
                3 => 'KILL_CARRIER'
        );

        public static $teams = array(
                0 => 'TEAM_FREE',
                1 => 'TEAM_RED',
                2 => 'TEAM_BLUE',
                3 => 'TEAM_SPECTATOR'

        );
}

?>
