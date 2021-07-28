<?php

require_once("../.." . "/main.php");

session_unset();
session_destroy();

header("Location: " . PROGPATH);
