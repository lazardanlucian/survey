<?php

/**
 * Main file that has to be included in application.
 *
 * @version 0.0.1
 */

/**
 * Load default definitions;
 *
 */
require_once(__dir__ . '/conf.php');


/**
 * render html.
 *
 * @since 0.0.1
 *
 * @param string $html
 * @param array $args {
 * 	Optional. An array of arguments.
 *
 * 	@type string $title Title as seen in browser.
 * 	@type mixed $no_menu If exists, menu is hidden.
 * 	@type array $css [
 * 		Load extra css files via url.
 * 		@type string
 * 		]
 * 	@type array $js [
 * 		Load extra js files via url.
 * 		@type string
 * 		]
 */

function _e($html, $args = null){
	$title = $args && array_key_exists('title', $args) ? $args['title'] : 'Survey Tool';

	require_once(__DIR__ . '/templates/header.php');
	if(!isset($args['no_menu'])){
		require_once(__DIR__ . '/templates/menu.php');
	}
	print($html);
	require_once(__DIR__ . '/templates/footer.php');

}


