<?php
$host = "localhost";
$username = "root";
$pword = "";
$db_name = "slf_system_db";
$port = 3308;

$conn = mysqli_connect($host, $username, $pword, $db_name, $port);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>