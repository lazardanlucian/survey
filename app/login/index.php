<?php 
require('../..' . '/main.php'); 

if(isset($_POST['mail']) && isset($_POST['password'])){
	auth_user($_POST['mail'], $_POST['password']);
}

if(isset($_POST['new_password']) && isset($_POST['repeat_password'])){
	if($_POST['new_password'] === $_POST['repeat_password']){
		
		update_user(array('password'=>$_POST['new_password']));
  }
}

if(isset($_SESSION['mail'])){
   $user = get_user($_SESSION['mail']);
   _e(sprintf('Welcome, %1$s!', $user['first_name']));
   if(auth_user($_SESSION['mail'], 'password')){
	   ob_start(); ?>
	   <p>Please change admin password!</p>
	   <form method="POST">
		<label for="new_password">New Password:</label><input type="pasword" name="new_password" />
		<label for="new_password">Repeat Password:</label><input type="pasword" name="repeat_password" />
		<?php csrf_token(); ?>
		<button type="submit">Update</button>
	   </form>
	  <?php _e(ob_get_clean()); 
	   _e(null,null,true);
   }
}

ob_start(); ?>

<form method="POST">
<label for="mail">Mail:</label><input type="text" id="mail" name="mail" />
<label for="password">Password:</label><input type="password" id="password" name="password" />
<button type="submit">Login</button>

<?php csrf_token(); ?>

<form>

<?php
_e(ob_get_clean()); 
_e(null, null, true);
?>

