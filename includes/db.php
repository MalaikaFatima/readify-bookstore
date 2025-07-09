<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
$conn = mysqli_connect("localhost", "root", "", "readify_store");
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>