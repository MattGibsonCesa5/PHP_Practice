<?php
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1) 
    { 
        if (isset($_SESSION["role"]) && !isset($_SESSION["masquerade"]))
        {        
            // get additional required files
            include("../../includes/functions.php");
            include("../../includes/config.php");

            // connect to the database
            $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

            // initialize the array to store messages
            $messages = [];

            // Super Admin Inbox (view all)
            if ($_SESSION["role"] == 1 && $_SESSION["id"] == 0)
            {
                // query the database to get all messages, order by most recent by default
                $getMessages = mysqli_query($conn, "SELECT * FROM messages ORDER BY timestamp DESC");
                if (mysqli_num_rows($getMessages) > 0)
                {
                    while ($message = mysqli_fetch_array($getMessages))
                    {
                        // store message details locally
                        $message_id = $message["id"];
                        $sender_id = $message["sender_id"];
                        $recipient_id = $message["recipient_id"];
                        $subject = $message["subject"];
                        $content = $message["message"];
                        $important = $message["important"];
                        $read_by_recipient = $message["read_by_recipient"];
                        $timestamp = $message["timestamp"];

                        // convert the time stored
                        $display_time = printDate($timestamp);

                        // get the sender name
                        if ($sender_id == 0) { $sender_name = "SUPER ADMIN"; } else { $sender_name = getUserDisplayName($conn, $sender_id); }

                        // get the recipient name
                        $recipient_name = getUserDisplayName($conn, $recipient_id);

                        // build the temporary array of data to send 
                        $temp = [];
                        $temp["id"] = $message_id;
                        $temp["from"] = $sender_name;
                        $temp["to"] = $recipient_name;
                        if (strlen($subject) > 32) { $temp["subject"] = substr($subject, 0, 32)."..."; } else { $temp["subject"] = $subject; }
                        if (strlen($content) > 64) { $temp["message"] = substr($content, 0, 64)."..."; } else { $temp["message"] = $content; }
                        $temp["time"] = $display_time;
                        $temp["actions"] = "";
                        $temp["read_by_recipient"] = $read_by_recipient;

                        // build the important column
                        $important_div = "";
                        if ($important == 1) { $important_div = "<div class='text-center'><i class='fa-solid fa-triangle-exclamation'></i></div>"; }
                        $temp["important"] = $important_div;

                        $messages[] = $temp;
                    }
                }
            }
            // User Inbox - view only messages received
            else
            {
                // query the database to get only messages that have been sent to the user, order by most recent by default
                $getMessages = mysqli_prepare($conn, "SELECT * FROM messages WHERE recipient_id=? AND is_deleted=0 ORDER BY timestamp DESC");
                mysqli_stmt_bind_param($getMessages, "i", $_SESSION["id"]);
                if (mysqli_stmt_execute($getMessages))
                {
                    $getMessagesResults = mysqli_stmt_get_result($getMessages);
                    if (mysqli_num_rows($getMessagesResults) > 0)
                    {
                        while ($message = mysqli_fetch_array($getMessagesResults))
                        {
                            // store message details locally
                            $message_id = $message["id"];
                            $sender_id = $message["sender_id"];
                            $recipient_id = $message["recipient_id"];
                            $subject = $message["subject"];
                            $content = $message["message"];
                            $important = $message["important"];
                            $read_by_recipient = $message["read_by_recipient"];
                            $timestamp = $message["timestamp"];

                            // convert the time stored
                            $display_time = printDate($timestamp);

                            // get the sender name
                            if ($sender_id == 0) { $sender_name = "SUPER ADMIN"; } else { $sender_name = getUserDisplayName($conn, $sender_id); }

                            // build the temporary array of data to send 
                            $temp = [];
                            $temp["id"] = $message_id;
                            $temp["from"] = $sender_name;
                            if (strlen($subject) > 32) { $temp["subject"] = substr($subject, 0, 32)."..."; } else { $temp["subject"] = $subject; }
                            if (strlen($content) > 64) { $temp["message"] = substr($content, 0, 64)."..."; } else { $temp["message"] = $content; }
                            $temp["time"] = $display_time;
                            $temp["read_by_recipient"] = $read_by_recipient;
                        
                            // build the important column
                            $important_div = "";
                            if ($important == 1) { $important_div = "<div class='text-center'><i class='fa-solid fa-triangle-exclamation'></i></div>"; }
                            $temp["important"] = $important_div;

                            // build the actions column
                            $actions = "";
                            $actions .= "<div class='row justify-content-end px-2'>
                                <button type='button' class='btn-inbox-actions w-auto px-1' onclick='deleteMessage(".$message_id.");'><i class='fa-solid fa-trash-can'></i></button>
                            </div>";
                            $temp["actions"] = $actions; 

                            $messages[] = $temp;
                        }
                    }
                }
            }   

            // disconnect from the database
            mysqli_close($conn);

            // send data to be printed
            $fullData = [];
            $fullData["draw"] = 1;
            $fullData["data"] = $messages;
            echo json_encode($fullData);
        }
    }
?>