<?php
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1) 
    { 
        if (isset($_SESSION["role"]) && !isset($_SESSION["masquerade"]))
        {        
            // get additional required files
            include("../../includes/functions.php");
            include("../../includes/config.php");

            // get the message ID from POST
            if (isset($_POST["message_id"]) && is_numeric($_POST["message_id"])) { $message_id = $_POST["message_id"]; } else { $message_id = null; }

            if ($message_id != null) // message ID was set; continue
            {
                // connect to the database
                $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

                // verify the message exists and the recipient is the current user
                $verifyMessage = mysqli_prepare($conn, "SELECT id, recipient_id, is_deleted FROM messages WHERE id=?");
                mysqli_stmt_bind_param($verifyMessage, "i", $message_id);
                if (mysqli_stmt_execute($verifyMessage))
                {
                    $verifyMessageResult = mysqli_stmt_get_result($verifyMessage);
                    if (mysqli_num_rows($verifyMessageResult) > 0) // message exists; continue
                    {
                        // store message details locally
                        $message = mysqli_fetch_array($verifyMessageResult);
                        $recipient_id = $message["recipient_id"];
                        $is_deleted = $message["is_deleted"];

                        // if the recipient ID of the message is the same as current user (or super admin), continue message deletion
                        if ($recipient_id == $_SESSION["id"])
                        {
                            $deleteMessage = mysqli_prepare($conn, "UPDATE messages SET is_deleted=1 WHERE id=?");
                            mysqli_stmt_bind_param($deleteMessage, "i", $message_id);
                            if (mysqli_stmt_execute($deleteMessage)) { echo 1; } // successfully deleted the message
                            else { echo 0; } // failed to delete the message
                        }
                    }
                }

                // disconnect from the database
                mysqli_close($conn);
            }
        }
    }
?>