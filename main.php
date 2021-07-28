<?php

/**
 * Main file that has to be included in application.
 *
 * @version 0.0.1
 */

/**
 * csrf protection
 * @since 0.0.1
 */

session_start();
if (empty($_SESSION['csrf_token'])) {
	generate_token();
}

if($_SERVER['REQUEST_METHOD'] === 'POST'){
	if (!empty($_POST['token']) && !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
		if (!hash_equals($_SESSION['token'], $_POST['token'])) {
			error_log("Request without csrf!");
			error_log($_REQUEST);
			header("HTTP/1.1 400");
			_e("Operation denied!");
			die();
		}
		generate_token();
	}
}

/**
 * Output csrf token
 * @since 0.0.1
 */ 
function csrf_token(){
	printf('<input type="hidden" name="csrf_token" value="%1$s" />', $_SESSION['csrf_token']);
}

/**
 * Generate csrf token
 * @since 0.0.1
 */

 function generate_token(){
	$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
 }

/**
 * Load default definitions;
 * @since 0.0.1
 */

require_once(__dir__ . '/conf.php');
require_once(__dir__ . '/initdb.php');

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




function sql($callback){
	$conn = new mysqli(DBHOST, DBUSER, DBPASS, DBNAME);

	if ($conn->connect_error) {
		_e('<div class="error">Cannot connect to database</div>');
		die();
	}

	return($callback($conn));

	$conn->close();
}


/**
 *
 * Create user.
 *
 * @since 0.0.1
 *
 * @param string $mail
 * @param string $password
 * @param string $first_name
 * @param string $last_name
 * @param int $level Starts with 0 = not enabled, 1 = admin, 2 = editor. Optional, default = 0.
 * 
 * @return mixed $id, if an error occurs, null is returned, otherwise the id is returned.
 */

function create_user($mail, $password, $first_name, $last_name, $level = 0){
	return(sql(function($conn) use ($mail, $password, $first_name, $last_name, $level){
		$stmt = $conn->prepare('INSERT INTO users (mail, password, first_name, last_name, level) VALUES ( ?, ?, ?, ?, ? )');
		if($stmt){
			$hashed = password_hash($password, PASSWORD_DEFAULT );
			$stmt->bind_param("ssssi", $mail, $hashed, $first_name, $last_name, $level);
			$stmt->execute();
			$lastid = $stmt->insert_id;
			$stmt->close();
			return $lastid;
		}
		return null;
	}));
}

/**
 *
 * Get user by mail.
 *
 * @since 0.0.1
 *
 * @param string $mail.
 *
 * @return array $user {
 * 		@type int id
 * 		@type string mail
 * 		@type string first_name
 * 		@type string last_name
 * 		} 
 * nulll on failure.
 *
 */

function get_user($mail){
	return(sql(function($conn) use ($mail){
		$stmt = $conn->prepare("SELECT id, mail, first_name, last_name, level FROM users WHERE mail like ?");
		if($stmt){
			$stmt->bind_param('s', $mail);
			$stmt->execute();
			$result = $stmt->get_result();
			$user = $result->fetch_assoc();
			$stmt->close();
			return $user;
		}
		_e(var_export($conn->error,1));
		return null;
	}));

}

