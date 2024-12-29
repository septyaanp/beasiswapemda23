<?php

//Koneksi Database
$server = "localhost";
$user = "u514737544_seanp";
$password = "+8;Y&CJs";
$database = "u514737544_bp23";

//Buat Koneksi
$koneksi = mysqli_connect($server, $user, $password, $database) or die(mysqli_error($koneksi));