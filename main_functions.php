<?php

/**
 * Output csrf token
 *
 * @since 0.0.1
 */
function csrf_token()
{
    printf('<input type="hidden" name="csrf_token" value="%1$s" />', $_SESSION['csrf_token']);
}

/**
 * Generate csrf token
 *
 * @since 0.0.1
 */

function generate_token()
{
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/**
 * render html.
 *
 * @since 0.0.1
 *
 * @param string $html
 * @param array  $args { Optional. An array of arguments.
 *
 * @type  string $title Title as seen in browser.
 * @type  mixed $no_menu If exists, menu is hidden.
 * @type  array $css [
 *         Load extra css files via url.
 * @type  string
 *         ]
 * @type  array $js [
 *         Load extra js files via url.
 * @type  string
 *         ]
 * @param bool   $end  If true, inserts end of html and exits
 */

function _e($html, $args = null, $end = false)
{
    if ($end) {
        include_once __DIR__ . '/templates/footer.php';
        die();
    }
    $title = $args && array_key_exists('title', $args) ? $args['title'] : 'Survey Tool';

    include_once __DIR__ . '/templates/header.php';
    if (!isset($args['no_menu'])) {
        include_once __DIR__ . '/templates/menu.php';
    }
    print('<div>' . $html . '</div>');
}




function sql($callback)
{
    $conn = new mysqli(DBHOST, DBUSER, DBPASS, DBNAME);

    if ($conn->connect_error) {
        _e('<div class="error">Cannot connect to database</div>');
        die();
    }

    return($callback($conn));

    $conn->close();
}


/**
 * Create user.
 *
 * @since 0.0.1
 *
 * @param string $mail
 * @param string $password
 * @param string $first_name
 * @param string $last_name
 * @param int    $level      Starts with 0 = not enabled, 1 = admin, 2 = editor. Optional, default = 0.
 *
 * @return mixed $id, if an error occurs, null is returned, otherwise the id is returned.
 */

function create_user($mail, $password, $first_name, $last_name, $level = 0)
{
    return(sql(
        function ($conn) use ($mail, $password, $first_name, $last_name, $level) {
            $stmt = $conn->prepare(
                'INSERT INTO users (mail, password, first_name, last_name, level) 
            VALUES ( ?, ?, ?, ?, ? )'
            );
            if ($stmt) {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt->bind_param("ssssi", $mail, $hashed, $first_name, $last_name, $level);
                $stmt->execute();
                $lastid = $stmt->insert_id;
                $stmt->close();
                return $lastid;
            }
            return null;
        }
    ));
}


/**
 * Update user.
 *
 * @since  0.0.1
 * @param  array $data
 * @return bool True/False
 */

function update_user($userdata)
{
    var_dump("Dummy");
}

/**
 * Get user by mail.
 *
 * @since 0.0.1
 *
 * @param string $mail.
 *
 * @return array $user {
 * @type   int id
 * @type   string mail
 * @type   string first_name
 * @type   string last_name
 *         }
 * null on failure.
 */

function get_user($mail)
{
    return(sql(
        function ($conn) use ($mail) {
            $stmt = $conn->prepare(
                "SELECT id, mail, first_name, last_name, level FROM users WHERE mail like ?"
            );
            if ($stmt) {
                $stmt->bind_param('s', $mail);
                $stmt->execute();
                $result = $stmt->get_result();
                $user = $result->fetch_assoc();
                $stmt->close();
                return $user;
            }
            _e(var_export($conn->error, 1));
            return null;
        }
    ));
}

/**
 * Auth user.
 *
 * @since 0.0.1
 *
 * @param string $mail.
 * @param string $password.
 *
 * @return boolean $valid True if validated
 */

function auth_user($mail, $password)
{
    $user = get_user($mail);
    if (!$user) {
        return false;
    }

    $auth = (sql(
        function ($conn) use ($mail, $password) {
            $stmt = $conn->prepare("SELECT password FROM users WHERE mail like ?");
            if ($stmt) {
                $stmt->bind_param('s', $mail);
                $stmt->execute();
                $result = $stmt->get_result();
                $hash = $result->fetch_assoc()['password'];
                return password_verify($password, $hash);
            }
            return false;
        }
    ));

    if ($auth) {
        $_SESSION['mail'] = $mail;
        $_SESSION['level'] = $user['level'];
        $_SESSION['user_id'] = $user['id'];
        return true;
    }
    return false;
}


/**
 * Create Survey.
 *
 * @since 0.0.1
 *
 * @param string $name.
 * @param string $description.
 * @param int    $max_entries  Stop at this many submissions.
 * @param int    $report_at    Send report at this interval.
 * @param int    $status       Starts with 0 = disabled, 1 = enabled.
 * @param array  $fields       Fields array.
 *
 * @return int $id on succes, null on failure.
 */

function create_survey($name, $description, $max_entries, $report_at, $status, $fields)
{
    $survey_id = sql(
        function ($conn) use ($name, $description, $max_entries, $report_at, $status, $fields) {
            $stmt = $conn->prepare(
                "INSERT INTO surveys (name, description, max_entries, report_at, status, fields) 
                VALUES (?, ?, ?, ?, ?, ?)"
            );
            if ($stmt) {
                $json_fields = json_encode($fields, 1);
                $stmt->bind_param('ssiiis', $name, $description, $max_entries, $report_at, $status, $json_fields);
                $stmt->execute();
                $lastid = $stmt->insert_id;
                $stmt->close();
                return $lastid;
            }
            return null;
        }
    );

    if (!$survey_id) {
        return null;
    }

    $surveyfields = sql(
        function ($conn) use ($survey_id, $fields) {
            $prepare = "CREATE TABLE survey_$survey_id ( id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,";
            $prepare .= join(
                ',',
                array_map(function ($i) {
                            $type = " VARCHAR(254)";

                    if (isset($i['store']) && $i['store'] === 'int') {
                            $type = " INT";
                    }

                    if (isset($i['required']) && $i['required']) {
                            $type .= " NOT NULL";
                    }

                    return $i['name'] . $type;
                }, $fields)
            );
            $prepare .= ")";

            $stmt = $conn->prepare($prepare);
            if ($stmt) {
                 $stmt->execute();
                 $stmt->close();
                 return true;
            }
            var_dump($conn->error);
            return null;
        }
    );
}

/**
 * Get Survey by name.
 *
 * @since 0.0.1
 *
 * @param string $name
 *
 * @return mixed $survey Return survey definition, on failure return null.
 */

function get_survey($name)
{
    return(sql(
        function ($conn) use ($name) {
            $stmt = $conn->prepare(
                "SELECT id, name, description, max_entries, report_at, status, fields FROM surveys WHERE name like ?"
            );
            if ($stmt) {
                $stmt->bind_param('s', $name);
                $stmt->execute();
                $result = $stmt->get_result();
                $survey = $result->fetch_assoc();
                $stmt->close();
                $survey['fields'] = json_decode($survey['fields'], 1);
                return $survey;
            }
            _e(var_export($conn->error, 1));
            return null;
        }
    ));
}
