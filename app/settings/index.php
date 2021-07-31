<?php

require_once('../..' . '/main.php');

if (!isset($_SESSION['user_id'])) {
    header("HTTP/1.1 400 Bad Request");
    _e("Not allowed");
    _end();
}


$s_mail = filter_input(INPUT_POST, 'mail', FILTER_SANITIZE_EMAIL);
$mail = filter_input(INPUT_POST, 'mail', FILTER_VALIDATE_EMAIL);
$errors = array(
    'mail' => '',
    'old_password' => '',
    'new_password' => '',
    'repeat_password' => '',
    'first_name' => '',
    'last_name' => '',
    'general' => ''
);

$old_password = filter_input(INPUT_POST, 'password');
$new_password = filter_input(INPUT_POST, 'new_password');
$repeat_password = filter_input(INPUT_POST, 'repeat_password');
$first_name = filter_input(INPUT_POST, 'first_name');
$last_name = filter_input(INPUT_POST, 'last_name');
$password_update = filter_input(INPUT_POST, 'password_update', FILTER_VALIDATE_BOOLEAN);


if ($_SERVER['REQUEST_METHOD'] === "POST") {
    if ($s_mail !== $mail) {
        $errors['mail'] .= '<p class="ierror">Malformed e-mail address.</p>';
    }

    if (!$s_mail) {
        $errors['mail'] .= '<p class="ierror">An e-mail address has to be specified</p>';
    }

    if ($new_password && $repeat_password) {
        if ($new_password !== $repeat_password) {
            $errors['general'] .= '<p class="ierror">Passwords do not match</p>';
        }

        $filter_options = array(
            'options' => array(
                'regexp' => "/^(?=.*[A-Za-z])(?=.*\d)(?=.*[@$!%*#?&])[A-Za-z\d@$!%*#?&]{8,}$/"
                )
            );
        $repeat_password = filter_var($repeat_password, FILTER_VALIDATE_REGEXP, $filter_options);

        if (!$repeat_password) {
            $errors['general'] .= '<p class="ierror">Password needs minimum eight characters, 
            at least one letter, one number and one special character</p>';
        }
    }

    if ($old_password && $repeat_password && $new_password === $repeat_password) {
        if (!auth_user($s_mail, $old_password)) {
            $errors['old_password'] .= '<p class="ierror">Current password incorrect</p>';
        }
    }

    $update = array();
    if (!$errors['general']) {
        if (!$errors['mail'] && !$password_update) {
            $update['mail'] = $s_mail;
        }
        if (!$errors['first_name'] && !$password_update) {
            $update['first_name'] = $first_name;
        }
        if (!$errors['last_name'] && !$password_update) {
            $update['last_name'] = $last_name;
        }
        if (!$errors['old_password'] && !$errors['new_password'] && !$errors['repeat_password'] && $password_update) {
            $update['password'] = $repeat_password;
        }

        if (!empty($update)) {
            update_user($update);
            $errors['general'] .= "<p>User updated!</p>";
        }
    }
}

$user = get_user();
ob_start();
?>
<p>User Options:</p>
<div class="row">
<form method="POST" class="settings">
    <?php echo $errors['general']; ?>
    <input type="checkbox" id="password_update" name="password_update">
    <div class="six columns">
    <label for="mail" >Mail address:</label>
    <?php echo $errors['mail']; ?>
    <input type="text" name="mail" id="mail" value="<?php printf('%1$s', $user['mail']); ?>"/>
    <label for="first_name">First Name:</label>
    <?php echo $errors['first_name']; ?>
    <input type="text" name="first_name" id="first_name" value="<?php printf('%1$s', $user['first_name']); ?>"/>
    <label for="last_name">Last Name:</label>
    <?php echo $errors['last_name']; ?>
    <input type="text" name="last_name" id="last_name" value="<?php printf('%1$s', $user['last_name']); ?>"/>
    <label for="password_update"><a nohref>Change Password</a></label><br>
    <button type="submit">Update</button>
    </div>
    <div class="six columns change_password">
    <label for="old_password">Old Password: </label>
    <?php echo $errors['old_password']; ?>
    <input type="password" name="old_password" id="old_password" />
    <?php echo $errors['new_password']; ?>
    <label for="new_password">New Password: </label>
    <input type="password" name="new_password" id="new_password" />
    <label for="repeat_password">Repeat Password: </label>
    <input type="password" name="repeat_password" id="repeat_password" />
    <label for="password_update"><a nohref>Back</a></label><br>
    <button type="submit">Update Password</button>
    </div>
    <?php csrf_token(); ?>
</form>
</div>
<?php
_e(ob_get_clean());
_end();