<?php 
    include "data.php";
    if(isset($_SESSION['unique_id'])){
        $outgoing_id = $_SESSION['unique_id'];
        $incoming_id = mysqli_real_escape_string($conn, $_POST['incoming_id']);
        $message = mysqli_real_escape_string($conn, $_POST['message']);
        if(!empty($message)){
            $sql = mysqli_query($conn, "INSERT INTO chat (sender_id, msg, receiver_id)
                                        VALUES ('$outgoing_id', '$message', '$incoming_id')");
        }else {
            die();
        }
    }else{
        header("location: entry.php");
    }


    
?>