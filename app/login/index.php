<?php
require_once('../..' . '/main.php');

$s_mail = filter_input(INPUT_POST, 'mail', FILTER_SANITIZE_EMAIL);
$mail = filter_input(INPUT_POST, 'mail', FILTER_VALIDATE_EMAIL);
$errors = array(
    'mail' => '',
    'password' => '',
    'general' => ''
);

$password = filter_input(INPUT_POST, 'password');
$new_password = filter_input(INPUT_POST, 'new_password');
$repeat_password = filter_input(INPUT_POST, 'repeat_password');

if ($_SERVER['REQUEST_METHOD'] === "POST") {
    if ($s_mail !== $mail) {
        $errors['mail'] .= '<p class="ierror">Malformed e-mail address.</p>';
    }

    if (!$s_mail) {
        $errors['mail'] .= '<p class="ierror">An e-mail address is needed to login</p>';
    }

    if (!$password) {
        $errors['password'] .= '<p class="ierror">Please type a password</p>';
    }

    if ($mail && $password && $s_mail === $mail) {
        if (!auth_user($s_mail, $password)) {
            $errors['general'] .= '<p class="ierror">User or Password does not match</p>';
        }
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

        if (!filter_var($repeat_password, FILTER_VALIDATE_REGEXP, $filter_options)) {
            $errors['general'] .= '<p class="ierror">Password needs minimum eight characters, 
            at least one letter, one number and one special character</p>';
        }

        if (!$errors['general']) {
            $password_update = update_user(
                array(
                    'password' => $repeat_password
                    )
            );

            if ($password_update) {
                $errors['general'] .= '<p> Password succesfully updated </p>';
            } else {
                $errors['general'] .= '<p> Password not updated.</p>';
            }
        }
    }
}

if (isset($_SESSION['mail'])) {
    $user = get_user($_SESSION['mail']);
    if (auth_user($_SESSION['mail'], 'surveys')) {
        $errors['general'] = '<p>Please change default password!</p>' . $errors['general'];
        ob_start(); ?>
       <form method="POST">
        <?php echo $errors['general']; ?>
        <label for="new_password">New Password:</label><input type="password" name="new_password" />
        <label for="new_password">Repeat Password:</label><input type="password" name="repeat_password" />
        <?php csrf_token(); ?>
        <button type="submit">Update</button>
       </form>
        <?php _e(ob_get_clean(), array('no_menu' => true));
        _end();
    }
    _e(sprintf($errors['general'] . 'Welcome, %1$s!', $user['first_name']));

    _end();
}

ob_start(); ?>

<form method="POST">
<?php echo $errors['general']; ?>
<label for="mail">Mail:</label>
<?php echo $errors['mail']; ?>
<input type="text" id="mail" name="mail" />
<label for="password">Password:</label>
<?php echo $errors['password']; ?>
<input type="password" id="password" name="password" />
<button type="submit">Login</button>

<?php csrf_token(); ?>

<form>

<?php
_e(ob_get_clean());
_end();
?>

