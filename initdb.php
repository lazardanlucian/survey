<?php

/**
 * create prepopulated database
 * @since 0.0.1
 */

sql(function($conn){
	$stmt = $conn->prepare('SELECT id from users WHERE level = 1 LIMIT 1');
	
	if(!$stmt){
		$stmt = $conn->prepare('CREATE TABLE users (
			id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
			mail VARCHAR(256) NOT NULL UNIQUE,
			level INT NOT NULL DEFAULT("1"),
			first_name VARCHAR(64) NOT NULL,
			last_name VARCHAR(64) NOT NULL,
			password VARCHAR(256) NOT NULL
			)');
		$stmt->execute();
	}
	
	$stmt->close();

	$stmt = $conn->prepare('SELECT id from surveys WHERE LIMIT 1');
	
	if(!$stmt){
		$stmt = $conn->prepare('CREATE TABLE surveys (
			id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
			name VARCHAR(256) NOT NULL,
			description VARCHAR(1024) NOT NULL,
			max_entries INT NOT NULL,
			report_at INT NOT NULL,
			fields MEDIUMTEXT NOT NULL,
			status INT NOT NULL
			)');
		$stmt->execute();
	}

	$stmt = $conn->prepare('SELECT id from surveys LIMIT 1');
	$stmt->execute();
	$result = $stmt->get_result();
	$survey = $result->fetch_assoc();
	if(!isset($survey['id'])){
		$stmt = $conn->prepare('INSERT INTO surveys (name, description, max_entries, report_at, fields, status) VALUES (
			"example-survey",
			"A predefined survey",
			20000,
			100, ?, 1)');
		$fields = json_encode(array(
			'First Name' => 'input', 
			'Choose one' => array( 'dropdown' => ['one','two','three'])
		), true)  ;
		$stmt->bind_param('s', $fields);
		$stmt->execute();
	}
	$stmt->close();
});


if(!get_user('admin@localhost')){
	create_user('admin@localhost', 'password', 'admin', 'local', 1);
}

