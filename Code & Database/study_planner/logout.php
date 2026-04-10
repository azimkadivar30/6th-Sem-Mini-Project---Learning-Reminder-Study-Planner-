<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Unset all session variables
$_SESSION = [];

// Destroy session
session_destroy();

// Redirect to login page
header("Location: index.php");
exit();
?>