<div>
  <a href="<?php echo PROGPATH; ?>/">Home</a>
  <?php if (!isset($_SESSION['level'])) { ?>
  <a href="<?php echo PROGPATH; ?>/app/login">Login</a>
  <?php } else { ?>
  <a href="<?php echo PROGPATH; ?>/app/surveys">Surveys</a>
  <a href="<?php echo PROGPATH; ?>/app/settings">Settings</a>
      <?php if ($_SESSION['level'] === 1) { ?>
  <a href="<?php echo PROGPATH; ?>/app/users">Users</a>
      <?php } ?>
  <a href="<?php echo PROGPATH; ?>/app/logout">Logout</a>
  <?php } ?>
</div>
