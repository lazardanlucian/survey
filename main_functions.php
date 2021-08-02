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
                foreach ($userdata as $key => $value) {
                    $_SESSION[$key] = $value;
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
 * Get users.
 *
 * @since 0.0.1
 *
 * @param string $search Optional, all users if not specified. All matching if specified.
 *
 * @return array $user {
 * @type   int id
 * @type   string mail
 * @type   string first_name
 * @type   string last_name
 * @type   int level
 *         }
 * null on failure.
 */

function get_users($search = null)
{
    if (!$search) {
        $search = '';
    }
    $search = mb_strtolower('%' . $search . '%');

    return(sql(
        function ($conn) use ($search) {
            $stmt = $conn->prepare(
                "SELECT id, mail, first_name, last_name, level FROM users
                WHERE mail like LOWER(?)
                OR first_name like LOWER(?)
                OR last_name like LOWER(?)"
            );
            if ($stmt) {
                $stmt->bind_param('sss', $search, $search, $search);
                $stmt->execute();
                $result = $stmt->get_result();
                while ($user = $result->fetch_assoc()) {
                    $users[] = $user;
                }
                $stmt->close();
                if (!empty($users)) {
                    return $users;
                }
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
function create_field($name, $label, $description, $type, $required = false, $options = null, $is_unique = null)
{
    if (!$options) {
        $options = array();
    }

    $is_unique = $is_unique ? true : false;
    $required = $required ? true : false;
    return sql(
        function ($conn) use ($name, $label, $description, $type, $required, $options, $is_unique) {
            $stmt = $conn->prepare("INSERT INTO fields (name, label, description, type, required, options, is_unique) 
            VALUES ( ?,?,?,?,?,?,? )");

            if ($stmt) {
                $json_options = json_encode($options, true);
                $stmt->bind_param(
                    'ssssisi',
                    $name,
                    $label,
                    $description,
                    $type,
                    $required,
                    $json_options,
                    $is_unique
                );
                $stmt->execute();
                $lastid = $stmt->insert_id;
                $stmt->close();
                return $lastid;
            }
            var_dump($conn->error);
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
            $stmt = $conn->prepare("SELECT id, name, label, description, type, required, options, is_unique
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
 * @param string $url
 *
 * @return int $id on success, null on failure
 */
function create_canonical($url)
{
    return sql(
        function ($conn) use ($url) {
            $stmt = $conn->prepare("INSERT INTO canonicals (url) VALUES ( ? )");
            if ($stmt) {
                $stmt->bind_param('s', $url);
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
 * @param string $url
 *
 * @return array {id}
 */
function get_canonical($url)
{
    return sql(
        function ($conn) use ($url) {
            $stmt = $conn->prepare("SELECT id FROM canonicals WHERE url = ?");
            if ($stmt) {
                $stmt->bind_param('s', $url);
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
 * @param string $post_message Optional. Message shown after survey is complete.
 *
 * @return int $id on succes, null on failure.
 */

function create_survey(
    $id_canonical,
    $name,
    $description,
    $max_entries,
    $report_at,
    $status,
    $fields,
    $post_message = null
) {
    $post_message = $post_message ? $post_message : '';
    return sql(
        function ($conn) use (
            $id_canonical,
            $name,
            $description,
            $max_entries,
            $report_at,
            $status,
            $fields,
            $post_message
        ) {
            $stmt = $conn->prepare(
                "INSERT INTO surveys 
                (id_canonical, name, description, max_entries, report_at, status, fields, post_message) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
            );
            if ($stmt) {
                $json_fields = json_encode($fields, 1);
                $stmt->bind_param(
                    'issiiiss',
                    $id_canonical,
                    $name,
                    $description,
                    $max_entries,
                    $report_at,
                    $status,
                    $json_fields,
                    $post_message
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
                "SELECT id, id_canonical, name, description, max_entries, report_at, status, fields, post_message
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
 * Get Surveys
 *
 * @since 0.0.1
 *
 * @param string $search Optional. If not set, retrieve all.
 *
 * @return mixed $survey array of survey arrays.
 */

function get_surveys($search = null)
{
    if (!$search) {
        $search = "%%";
    } else {
        $search = '%' . mb_strtolower($search) . '%';
    }
    return(sql(
        function ($conn) use ($search) {
            $stmt = $conn->prepare(
                "SELECT surveys.id, id_canonical, name, description, max_entries, report_at, status, fields, post_message, url
                FROM surveys LEFT JOIN canonicals on id_canonical = canonicals.id
                WHERE name like LOWER(?)
                OR description like LOWER(?)"
            );
            if ($stmt) {
                $stmt->bind_param('ss', $search, $search);
                $stmt->execute();
                $result = $stmt->get_result();
                while ($survey = $result->fetch_assoc()) {
                    $surveys[] = $survey;
                }
                $stmt->close();
                if (!empty($surveys)) {
                    return($surveys);
                }
            }
            return null;
        }
    ));
}

/**
 * Get Submissions
 *
 * @since 0.0.1
 *
 * @param int $survey_id if of survey.
 *
 * @return mixed $submissions array of submissions.
 */

function get_submissions($survey_id)
{
    return(sql(
        function ($conn) use ($survey_id) {
            $stmt = $conn->prepare(
                "SELECT id, id_canonical, id_field, original, value 
                FROM submissions WHERE id = ?"
            );
            if ($stmt) {
                $stmt->bind_param('i', $survey_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $result = $stmt->get_result();
                while ($submission = $result->fetch_assoc()) {
                    $submissions[] = $submission;
                }
                $stmt->close();
                if (!empty($submissions)) {
                    return($submissions);
                }
            }
            return null;
        }
    ));
}

/**
 * Get Survey by id.
 *
 * @since 0.0.1
 *
 * @param int $id
 *
 * @return mixed $survey Return survey definition, on failure return null.
 */

function get_survey_by_id($id)
{
    return(sql(
        function ($conn) use ($id) {
            $stmt = $conn->prepare(
                "SELECT id, id_canonical, name, description, max_entries, report_at, status, fields, post_message
                FROM surveys WHERE id like ?"
            );
            if ($stmt) {
                $stmt->bind_param('i', $id);
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

function create_submission_group($id_survey, $id_canonical)
{
    return(sql(
        function ($conn) use ($id_survey, $id_canonical) {
            $user_agent = $_SERVER['HTTP_USER_AGENT'];
            $ip = $_SERVER['REMOTE_ADDR'];

            $ipv4 = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
            $ipv6 = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6);

            $stmt = $conn->prepare(
                "INSERT INTO submissions_group (id_survey, id_canonical, user_agent, ipv4, ipv6)
                VALUES (?, ?, ?, ?, ?)"
            );
            if ($stmt) {
                $stmt->bind_param('iisss', $id_survey, $id_canonical, $user_agent, $ipv4, $ipv6);
                $stmt->execute();
                $last_id = $stmt->insert_id;
                $stmt->close();
                return $last_id;
            }
            var_dump($conn->error);
            $stmt->close();
            return false;
        }
    ));
}

function create_submission_data($id_group, $id_field, $name_field, $value)
{
    return(sql(
        function ($conn) use ($id_group, $id_field, $name_field, $value) {
            $stmt = $conn->prepare(
                "INSERT INTO submissions_data (id_submission_group, id_field, name_field, value)
                VALUES (?, ?, ?, ?)"
            );
            if ($stmt) {
                $stmt->bind_param('iiss', $id_group, $id_field, $name_field, $value);
                $stmt->execute();
                $last_id = $stmt->insert_id;
                $stmt->close();
                return $last_id;
            }
            $stmt->close();
            return false;
        }
    ));
}


function get_survey_data($id_survey, $field_id = null, $userdata = null)
{
    $groups = sql(
        function ($conn) use ($id_survey) {
            $stmt = $conn->prepare(
                "SELECT * FROM submissions_group WHERE id_survey like ?"
            );
            if ($stmt) {
                $stmt->bind_param('i', $id_survey);
                $stmt->execute();
                $result = $stmt->get_result();
                while ($data = $result->fetch_assoc()) {
                        $survey_data[] = $data;
                }
                $stmt->close();
                if ($survey_data) {
                    return $survey_data;
                }
            }
            $stmt->close();
            return null;
        }
    );

    $submissions_data = array();

    foreach ($groups as $group) {
        $id_group = $group['id'];
        $submission_data = sql(
            function ($conn) use ($id_group, $field_id, $userdata) {
                if ($field_id && $userdata) {
                    $stmt = $conn->prepare(
                        "SELECT * FROM submissions_data WHERE id_submission_group like ? and id_field = ? and value like ?"
                    );
                } else {
                    $stmt = $conn->prepare(
                        "SELECT * FROM submissions_data WHERE id_submission_group like ?"
                    );
                }
                if ($stmt) {
                    if ($field_id && $userdata) {
                        $stmt->bind_param('iis', $id_group, $field_id, $userdata);
                    } else {
                        $stmt->bind_param('i', $id_group);
                    }
                    $stmt->execute();
                    $result = $stmt->get_result();
                    while ($data = $result->fetch_assoc()) {
                            $survey_data[] = $data;
                    }
                    $stmt->close();
                    if (isset($survey_data)) {
                        return $survey_data;
                    }
                }
                return null;
            }
        );

        if ($submission_data) {
            array_push($submissions_data, $submission_data);
        }
    }

        return $submissions_data;
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

/**
 * Generate Password
 *
 * @param int $length
 *
 * @return string $str
 */
function generate_password($length = 20)
{
    $chars =  'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz' .
              '0123456789`-=~!@#$%^&*()_+,./<>?;:[]{}\|';
    $str = '';
    $max = strlen($chars) - 1;

    for ($i = 0; $i < $length; $i++) {
        $str .= $chars[random_int(0, $max)];
    }

    return $str;
}

/**
 * Filter the canonical url of the survey.
 *
 * @param int $type
 * @param string $var_name
 *
 * @return mixed false or null on failure, string on success.
 */
function filter_input_survey_url($type, $var_name)
{
    return(
        filter_input(
            $type,
            $var_name,
            FILTER_VALIDATE_REGEXP,
            array('options' => array( 'regexp' => '/^[a-zA-Z0-9_-]+$/'))
        )
    );
}
