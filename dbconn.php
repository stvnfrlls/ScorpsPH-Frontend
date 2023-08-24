<?php 
    $db_hostname = "localhost";
    $db_username = "root";
    $db_password = "";
    $db_name = "scorps_db";
    $port = "3306";

    $conn = mysqli_connect($db_hostname, $db_username, $db_password, $db_name, $port);
    if (!$conn) {
        echo mysqli_connect_error($conn);
    }
?>