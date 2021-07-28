<?php

$defaults = array(
	'PROGPATH'=>'/survey',
	'DBNAME' => 'survey',
	'DBUSER' => 'survey',
	'DBPASS' => 'survey',
	'DBHOST' => '127.0.0.1',
       	'DBPORT' => null);

if(file_exists('../../survey.json')){
	$cfgfile = json_decode(file_get_contents('../../survey.json'));
	$loadcfg=true;
	foreach($defaults as $key =>$value){
		if(!array_key_exists($key, $cfgfile)){
			$loadcfg = false;
		}
	}
	if($loadcfg){
		$defaults = $cfgfile;
	}
}

foreach($defaults as $key => $value){
	define($key, $value);
}


/**
 * Save config.
 *
 * @param array $arr {
 *  	@type string $PROGPATH
 *  	@type string $DBNAME
 *  	@type string $DBUSER
 *  	@type string $DBPASS
 *  	@type string $DBHOST
 *  	@type mixed  $DBPORT
 *  	}
 */
function save_config($arr){
	file_put_contents('../../survey.json', json_encode($arr, true));
}

