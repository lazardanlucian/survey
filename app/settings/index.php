<?php

require_once('../..' . '/main.php');

if (!isset($_SESSION['user_id'])) {
    header("HTTP/1.1 400 Bad Request");
    _e("Not allowed");
    _end();
}

$user = get_user();
ob_start();
?>
<p>User Options:</p>
<div class="row">
<form method="POST" class="settings">
    <input type="checkbox" id="password_update" name="password_update">
    <div class="six columns">
    <label for="mail" >Mail address:</label>
    <input type="text" name="mail" id="mail" value="<?php printf('%1$s', $user['mail']); ?>"/>
    <label for="first_name">First Name:</label>
    <input type="text" name="first_name" id="first_name" value="<?php printf('%1$s', $user['first_name']); ?>"/>
    <label for="last_name">Last Name:</label>
    <input type="text" name="last_name" id="last_name" value="<?php printf('%1$s', $user['last_name']); ?>"/>
    <label for="password_update"><a nohref>Change Password</a></label><br>
    <button type="submit">Update</button>
    </div>
    <div class="six columns change_password">
    <label for="old_password">Old Password: </label>
    <input type="password" name="old_password" id="old_password" />
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