<?php
require_once 'api\config.php';
session_destroy();
header('Location: login.php');
exit();
?>