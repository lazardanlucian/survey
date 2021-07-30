<div>
  <a href="<?php echo PROGPATH; ?>/">Home</a>
  <?php if (!isset($_SESSION['level'])) { ?>
  <a href="<?php echo PROGPATH; ?>/app/login">Login</a>
  <?php } else { ?>
  <a href="<?php echo PROGPATH; ?>/app/logout">Logout</a>
  <?php } ?>
</div>
