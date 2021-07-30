<?php

/**
 * create prepopulated database
 *
 * @since 0.0.1
 */

sql(
    function ($conn) {
        $stmt = $conn->prepare('SELECT id from users WHERE level = 1 LIMIT 1');

        if (!$stmt) {
            $stmt = $conn->prepare(
                'CREATE TABLE users (
			id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
			mail VARCHAR(256) NOT NULL UNIQUE,
			level INT NOT NULL DEFAULT("1"),
			first_name VARCHAR(64) NOT NULL,
			last_name VARCHAR(64) NOT NULL,
			password VARCHAR(256) NOT NULL
			)'
            );
            $stmt->execute();
        }

        $stmt->close();

        $stmt = $conn->prepare('SELECT id from surveys WHERE LIMIT 1');

        if (!$stmt) {
            $stmt = $conn->prepare(
                'CREATE TABLE surveys (
			id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
			name VARCHAR(256) NOT NULL,
			description VARCHAR(1024) NOT NULL,
			max_entries INT NOT NULL,
			report_at INT NOT NULL,
			status INT NOT NULL,
			fields MEDIUMTEXT NOT NULL
			)'
            );
            $stmt->execute();
        }
    }
);


if (!get_user('admin@localhost')) {
    create_user('admin@localhost', 'password', 'admin', 'local', 1);
}

if (!get_survey('sample_survey')) {
    create_survey(
        'sample_survey',
        'some_description',
        20,
        20,
        1,
        array(
            array('name' => 'Name', 'type' => 'input', 'required' => true, 'label' => 'name'),
            array('name' => 'Color', 'type' => 'checkbox', 'required' => false, 'options' => ['blue','green','yellow']),
            array('name' => 'Year', 'type' => 'year', 'required' => false, 'store' => 'int'),
            array('name' => 'Pick', 'type' => 'dropdown', 'required' => true, 'options' => ['Yes','No','Maybe'])
        )
    );
}
