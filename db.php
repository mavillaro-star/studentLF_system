<?php

$host= "localhost";
$username= "root";
$pword= "";
$db_name= "slf_system_db";

$conn= mysqli_connect($host,$username,$pword,$db_name,3308);

if(!$conn){
    die("Connection failed" . $conn->connect_error);
}