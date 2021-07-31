<?php

require_once('../..' . '/main.php');

$can_create = false;
if (get_user()['level'] === 1) {
    $can_create = true;
}

if ($_SESSION['level'] < 1) {
    _e("Operation not allowed");
    _end();
}

$errors = array(
    'first_name' => '',
    'last_name' => '',
    'mail' => '',
    'level' => ''
);

$s_mail = filter_input(INPUT_POST, 'mail', FILTER_SANITIZE_EMAIL);
$mail = filter_input(INPUT_POST, 'mail', FILTER_VALIDATE_EMAIL);
$first_name = filter_input(INPUT_POST, 'first_name');
$last_name = filter_input(INPUT_POST, 'last_name');
$level = filter_input(INPUT_POST, 'level', FILTER_VALIDATE_INT);

if ($_SERVER['REQUEST_METHOD'] === "POST") {
    if ($s_mail !== $mail) {
        $errors['mail'] = '<p class="ierror">Malformed e-mail address.</p>';
    }

    if (!$s_mail) {
        $errors['mail'] = '<p class="ierror">An e-mail address has to be specified</p>';
    }

    if (!$first_name) {
        $errors['first_name'] .= '<p class="ierror">First name cannot be empty</p>';
    }

    if (!$last_name) {
        $errors['last_name'] .= '<p class="ierror">Last name cannot be empty</p>';
    }

    if (!$level && $level !== 0) {
        $errors['level'] .= '<p class="ierror">This is either an error, or you should apply for a 
        <a href="/fictivecareer">job</a> at us!</p>';
    }

    if (!$errors['mail'] && !$errors['first_name'] && !$errors['last_name'] && !$errors['level']) {
        create_user($s_mail, generate_password(12), $first_name, $last_name, $level);
    }
}



$users = get_users();
ob_start();
if ($can_create) {
    printf('<form method="post">');
}
printf('<table>');
printf('<tr>');
printf('<th>First Name</th>');
printf('<th>Last Name</th>');
printf('<th>E-Mail</th>');
printf('<th>Status</th>');
printf('<th>Options</th>');
printf('</tr>');

$levels = array('Disabled', 'Admin', 'User');
$can_edit = sprintf('<a href="%1$s">Edit User</a>', PROGPATH . '/app/edit/user');
$options = array($can_edit, '', $can_edit);

$select_options = '<option value=0>Disabled</a>
<option value=1>Admin</a>
<option value=2>User</a>';

if ($can_create) {
    printf('<tr>');
    printf('<td>%1$s<input type="text" name="first_name"/></td>', $errors['first_name']);
    printf('<td>%1$s<input type="text" name="last_name"/></td>', $errors['last_name']);
    printf('<td>%1$s<input type="text" name="mail"/></td>', $errors['mail']);
    printf('<td>%1$s<select name="level">%2$s</select>', $errors['level'], $select_options);
    printf('<td><button type="submit">Add user</button></td>');
    printf('</tr>');
}

foreach ($users as $user) {
    printf('<tr>');
    printf('<td>%1$s</td>', $user['first_name']);
    printf('<td>%1$s</td>', $user['last_name']);
    printf('<td>%1$s</td>', $user['mail']);
    printf('<td>%1$s</td>', $levels[$user['level']]);
    printf('<td>%1$s</td>', $options[$user['level']]);
    printf('</tr>');
}

printf("</table>");

if ($can_create) {
    csrf_token();
    printf('</form>');
}
_e(ob_get_clean());
