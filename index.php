<?php
// If already logged in → go to notes.php
session_start();
if (isset($_SESSION["user_id"])) {
    header("Location: notes.php");
    exit();
}

// Otherwise → go to login page
header("Location: login.php");
exit();
?>