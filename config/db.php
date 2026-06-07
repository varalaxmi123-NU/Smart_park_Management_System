<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "smartpark";

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("<h2 style='color:red;font-family:sans-serif;padding:20px;'>
        ❌ Database Connection Failed!<br>
        <small>" . mysqli_connect_error() . "</small><br><br>
        <b>Fix:</b> Open phpMyAdmin → Create database named <b>smartpark</b> → Import smartpark.sql
    </h2>");
}
?>
