<?php

$progpath = '/survey';
$dbname = 'survey';
$dbuser = 'survey';
$dbpass = 'survey';
$dbhost = '127.0.0.1';
$dbport = null;

if (file_exists(ABSPATH . '/../../survey.json')) {
    $cfgfile = json_decode(file_get_contents(ABSPATH . '/../../survey.json'), 1);
    foreach ($cfgfile as $definition => $value) {
        if (isset(${strtolower($definition)})) {
            ${strtolower($definition)} = $value;
        }
    }
}

$protocol = (
    isset($_SERVER['HTTPS']) && filter_var($_SERVER['HTTPS'], FILTER_VALIDATE_BOOLEAN) )
    || ( isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https'
    ) ? 'https' : 'http' ;
