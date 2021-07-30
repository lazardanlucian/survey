<?php

/**
 * Main file that has to be included in application.
 *
 * @version 0.0.1
 */

/**
 * csrf protection
 *
 * @since 0.0.1
 */

session_start();
if (empty($_SESSION['csrf_token'])) {
    generate_token();
}

/**
 * Initialize DB
 *
 * @since 0.0.1
 */

require_once __DIR__ . '/initdb.php';

/**
 * Require Function defs.
 */
require_once __DIR__ . '/main_functions.php';

/**
 * Session auto-expire
 *
 * @since 0.0.1
 */
$session_now = time();
if (isset($_SESSION['time'])) {
    if ($session_now - $_SESSION['time'] > 60 * 60) {
        session_unset();
        session_destroy();
    }
}
$_SESSION['time'] = time();

/**
 * Load default definitions;
 *
 * @since .0.0.1
 */

require_once __DIR__ . '/conf.php';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        error_log("Request without csrf!");
        header("HTTP/1.1 400 Bad Request");
        _e("Operation denied!");
        die();
    }
    generate_token();
}
