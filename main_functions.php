<?php

define('PROGPATH', $progpath);
define('DBNAME', $dbname);
define('DBUSER', $dbuser);
define('DBPASS', $dbpass);
define('DBHOST', $dbhost);
define('DBPORT', $dbport);

define('PROTOCOL', $protocol);

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

function _e($html, $args = null)
{
    $title = $args && array_key_exists('title', $args) ? $args['title'] : 'Survey Tool';

    include_once __DIR__ . '/templates/header.php';
    if (!isset($args['no_menu'])) {
        include_once __DIR__ . '/templates/menu.php';
    }
    print('<div>' . $html . '</div>');
}

/**
 * End output with footer.
 *
 * @since 0.0.1
 *
 * @return void
 */
function _end()
{
    include_once __DIR__ . '/templates/footer.php';
    die();
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
    $predefined = array(
        'mail' => 's',
        'password' => 's',
        'first_name' => 's',
        'last_name' => 's',
        'level' => 'i'
    );

    if (is_array($userdata) && !empty($userdata) && isset($_SESSION['user_id'])) {
        $query = "UPDATE users SET ";
        $binds = "";
        $params = array();
        foreach ($userdata as $key => $value) {
            if (array_key_exists($key, $predefined)) {
                $query .= $key . '=?, ';
                $binds .= $predefined[$key];
                if ($key == 'password') {
                    $value = password_hash($value, PASSWORD_DEFAULT);
                }
                array_push($params, $value);
            }
        }

        /** remove last comma and space */
        $query = substr($query, 0, -2);
        $query .= " WHERE id=?";
        array_push($params, $_SESSION['user_id']);
        $binds .= "i";

        return sql(function ($conn) use ($query, $binds, $params, $userdata) {
            $stmt = $conn->prepare($query);
            if ($stmt) {
                $stmt->bind_param($binds, ...$params);
                $stmt->execute();
                $stmt->close();
                if (isset($userdata['mail'])) {
                    $_SESSION['mail'] = $userdata['mail'];
                }
                return true;
            }

            return false;
        });
    }

    return false;
}

/**
 * Get user by mail.
 *
 * @since 0.0.1
 *
 * @param string $mail Optional, current user if not specified.
 *
 * @return array $user {
 * @type   int id
 * @type   string mail
 * @type   string first_name
 * @type   string last_name
 *         }
 * null on failure.
 */

function get_user($mail = null)
{
    if (!$mail) {
        $mail = $_SESSION['mail'];
    }

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
 * Create field.
 *
 * Saves a new field link in db.
 *
 * @since 0.0.1
 *
 * @param string @name
 * @param string @label
 * @param string @description
 * @param string @type
 * @param string @required Optional, default false, if the field is compulsory;
 *
 * @return int $id on success, null on failure
 */
function create_field($name, $label, $description, $type, $required = false, $options = null)
{
    return sql(
        function ($conn) use ($name, $label, $description, $type, $required, $options) {
            if ($options) {
                $stmt = $conn->prepare("INSERT INTO fields (name, label, description, type, required, options) 
                VALUES ( ?,?,?,?,?,? )");
            } else {
                $stmt = $conn->prepare("INSERT INTO fields (name, label, description, type, required) 
                VALUES ( ?,?,?,?,? )");
            }
            if ($stmt) {
                if ($options) {
                    $json_options = json_encode($options, true);
                    $stmt->bind_param(
                        'ssssis',
                        $name,
                        $label,
                        $description,
                        $type,
                        $required,
                        $json_options
                    );
                } else {
                    $stmt->bind_param('ssssi', $name, $label, $description, $type, $required);
                }
                $stmt->execute();
                $lastid = $stmt->insert_id;
                $stmt->close();
                return $lastid;
            }

            $stmt->close();
            return null;
        }
    );
}

/**
 * Get field.
 *
 * @since 0.0.1
 *
 * @param string @id
 *
 * @return array Null on failure
 */
function get_field($id)
{
    return sql(
        function ($conn) use ($id) {
            $stmt = $conn->prepare("SELECT id, name, label, description, type, required, options
            FROM fields WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param('i', $id);
                $stmt->execute();
                $result = $stmt->get_result();
                $field = $result->fetch_assoc();
                if ($field) {
                    $field['options'] = json_decode($field['options'], true);
                }
                $stmt->close();
                return $field;
            }
            $stmt->close();
            return null;
        }
    );
}

/**
 * Create canonical.
 *
 * Saves a new canonical link in db.
 *
 * @since 0.0.1
 *
 * @param string @name
 *
 * @return int $id on success, null on failure
 */
function create_canonical($name)
{
    return sql(
        function ($conn) use ($name) {
            $stmt = $conn->prepare("INSERT INTO canonicals (name) VALUES ( ? )");
            if ($stmt) {
                $stmt->bind_param('s', $name);
                $stmt->execute();
                $lastid = $stmt->insert_id;
                $stmt->close();
                return $lastid;
            }
            $stmt->close();
            return null;
        }
    );
}

/**
 * Get canonical.
 *
 * @since 0.0.1
 *
 * @param string @name
 *
 * @return array {id}
 */
function get_canonical($name)
{
    return sql(
        function ($conn) use ($name) {
            $stmt = $conn->prepare("SELECT id FROM canonicals WHERE name = ?");
            if ($stmt) {
                $stmt->bind_param('s', $name);
                $stmt->execute();
                $result = $stmt->get_result();
                $canonical = $result->fetch_assoc();
                $stmt->close();
                return $canonical;
            }
            $stmt->close();
            return null;
        }
    );
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

function create_survey($id_canonical, $name, $description, $max_entries, $report_at, $status, $fields)
{
    return sql(
        function ($conn) use ($id_canonical, $name, $description, $max_entries, $report_at, $status, $fields) {
            $stmt = $conn->prepare(
                "INSERT INTO surveys (id_canonical, name, description, max_entries, report_at, status, fields) 
                VALUES (?, ?, ?, ?, ?, ?, ?)"
            );
            if ($stmt) {
                $json_fields = json_encode($fields, 1);
                $stmt->bind_param(
                    'issiiis',
                    $id_canonical,
                    $name,
                    $description,
                    $max_entries,
                    $report_at,
                    $status,
                    $json_fields
                );
                $stmt->execute();
                $lastid = $stmt->insert_id;
                $stmt->close();
                return $lastid;
            }
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
                "SELECT id, id_canonical, name, description, max_entries, report_at, status, fields 
                FROM surveys WHERE name like ?"
            );
            if ($stmt) {
                $stmt->bind_param('s', $name);
                $stmt->execute();
                $result = $stmt->get_result();
                $survey = $result->fetch_assoc();
                $stmt->close();
                if ($survey) {
                    $survey['fields'] = json_decode($survey['fields'], 1);
                    return $survey;
                }
            }
            return null;
        }
    ));
}

/**
 * Save config.
 *
 * @since 0.0.1
 *
 * @param array $arr {
 * @type  string $PROGPATH
 * @type  string $DBNAME
 * @type  string $DBUSER
 * @type  string $DBPASS
 * @type  string $DBHOST
 * @type  mixed  $DBPORT
 *      }
 */
function save_config($arr)
{
    file_put_contents(ABSPATH . '/../../survey.json', json_encode($arr, true));
}

/**
 * Send e-mail.
 *
 * @param string $to
 * @param string $subject
 * @param string $message
 * @param string $bcc comma separated e-mail address list
 *
 * @return boolean True if success, false otherwise
 */
function e_mail($to, $subject, $message, $bcc = null)
{
    /**
     * Wrapper for mail().
     * TODO add more mailing services.
     * SMTP, MailGrid, etc.
     */
    $headers = null;
    if ($bcc) {
        $headers = 'BCC: ' . $bcc . "\r\n";
    }
    mail($to, $subject, $message, $headers);
}
