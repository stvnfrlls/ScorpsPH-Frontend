<?php
    include "data.php";
    $outgoing_id = $_SESSION['unique_id'];
    $sql = "SELECT * FROM customers WHERE NOT user_unique_id = {$outgoing_id} ORDER BY user_num DESC";
    $query = mysqli_query($conn, $sql);
    $output = "";
    if(mysqli_num_rows($query) == 0){
        $output .= "No users are available to chat";
    }elseif(mysqli_num_rows($query) > 0){
        include_once "c-data.php";
    }
    echo $output;
?>