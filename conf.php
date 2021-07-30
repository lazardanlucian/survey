<?php

$defaults = array(
    'PROGPATH' => '/survey',
    'DBNAME' => 'survey',
    'DBUSER' => 'survey',
    'DBPASS' => 'survey',
    'DBHOST' => '127.0.0.1',
           'DBPORT' => null);

if (file_exists(ABSPATH . '/../../survey.json')) {
    $cfgfile = json_decode(file_get_contents(ABSPATH . '/../../survey.json'));
    $loadcfg = true;
    foreach ($defaults as $key => $value) {
        if (!array_key_exists($key, $cfgfile)) {
            $loadcfg = false;
        }
    }
    if ($loadcfg) {
        $defaults = $cfgfile;
    }
}

foreach ($defaults as $key => $value) {
    define($key, $value);
}
