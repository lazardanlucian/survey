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

        $stmt = $conn->prepare('SELECT id from surveys LIMIT 1');

        if (!$stmt) {
            $stmt = $conn->prepare(
                'CREATE TABLE surveys (
			id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
            id_canonical INT NOT NULL,
			name VARCHAR(256) NOT NULL,
			description VARCHAR(1024),
			max_entries INT NOT NULL,
			report_at INT NOT NULL,
			status INT NOT NULL,
			fields TEXT NOT NULL
			)'
            );
            $stmt->execute();
        }

        $stmt->close();

        $stmt = $conn->prepare('SELECT id from canonicals LIMIT 1');

        if (!$stmt) {
            $stmt = $conn->prepare(
                'CREATE TABLE canonicals (
			id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
            url VARCHAR(256) NOT NULL UNIQUE
			)'
            );
            $stmt->execute();
        }
        $stmt->close();

        $stmt = $conn->prepare('SELECT id from fields LIMIT 1');

        if (!$stmt) {
            $stmt = $conn->prepare(
                'CREATE TABLE fields (
			id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(256) NOT NULL,
            label VARCHAR(256) NOT NULL,
            description VARCHAR(256),
            type VARCHAR(256) NOT NULL,
            options TEXT,
            required BOOLEAN NOT NULL DEFAULT FALSE
			)'
            );
            $stmt->execute();
        }

        $stmt->close();

        $stmt = $conn->prepare('SELECT id from submissions LIMIT 1');

        if (!$stmt) {
            $stmt = $conn->prepare(
                'CREATE TABLE submissions (
			id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
            id_survey INT NOT NULL,
            id_canonical INT NOT NULL,
            id_field INT NOT NULL,
            original VARCHAR(256) NOT NULL,
			value TEXT NOT NULL
			)'
            );
            $stmt->execute();
        }

        $stmt->close();
    }
);


if (!get_user('admin@local.host')) {
    create_user('admin@local.host', 'password', 'admin', 'local', 1);
}

if (!get_canonical('sample_link')) {
    create_canonical('sample_link');
}

if (!get_field(1)) {
    create_field(
        'Name',
        'Name',
        '',
        'input',
        true
    );
}

if (!get_field(2)) {
    create_field(
        'checkbox',
        'Select any of the following:',
        '',
        'checkbox',
        false,
        array(
            'Option 1',
            'Option 2',
            'Option 3'
        )
    );
}

if (!get_field(3)) {
    create_field(
        'Color',
        'Select a color:',
        '',
        'dropdown',
        array(
            'Red',
            'Green',
            'Blue'
        ),
        false
    );
}

if (!get_survey('sample survey')) {
    create_survey(
        1,
        'sample survey',
        'some_description',
        20,
        20,
        1,
        array(1,2,3)
    );
}
