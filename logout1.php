<?php
require_once 'config/session.php';

// Destroy session
session_destroy();

// Redirect to login
header('Location: login1.php');
exit();
?>
