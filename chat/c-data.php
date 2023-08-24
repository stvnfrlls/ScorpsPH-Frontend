<?php
    while($row = mysqli_fetch_assoc($query)){
        $sql2 = "SELECT * FROM customer_chat WHERE (receiver_id = {$row['user_unique_id']}
                OR sender_id = {$row['user_unique_id']}) AND (sender_id = {$outgoing_id} 
                OR receiver_id = {$outgoing_id}) ORDER BY chat_num DESC LIMIT 1";
        $query2 = mysqli_query($conn, $sql2);
        $row2 = mysqli_fetch_assoc($query2);
        (mysqli_num_rows($query2) > 0) ? $result = $row2['msg'] : $result ="No message available";
        (strlen($result) > 28) ? $msg =  substr($result, 0, 28) . '...' : $msg = $result;
        if(isset($row2['outgoing_msg_id'])){
            ($outgoing_id == $row2['sender_id']) ? $you = "You: " : $you = "";
        }else{
            $you = "";
        }
        ($outgoing_id == $row['user_unique_id']) ? $hid_me = "hide" : $hid_me = "";

        $output .= '<a href="chat.php?user_id='. $row['user_unique_id'] .'">
                    <div class="content">
                    <div class="details">
                        <span>'. $row['user_fname']. " " . $row['user_lname'] .'</span>
                        <p>'. $you . $msg .'</p>
                    </div>
                    </div>
                </a>';
    }
?>