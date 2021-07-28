<?php

define( 'ABSPATH', dirname( __FILE__ ) . '/' );

$defaults = array(
	'PROGPATH'=>'/survey',
	'DBNAME' => 'survey',
	'DBUSER' => 'survey',
	'DBPASS' => 'survey',
	'DBHOST' => '127.0.0.1',
       	'DBPORT' => null);

if(file_exists(ABSPATH . '/../../survey.json')){
	$cfgfile = json_decode(file_get_contents(ABSPATH . '/../../survey.json'));
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
 * @since 0.0.1
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
	file_put_contents(ABSPATH . '/../../survey.json', json_encode($arr, true));
}
