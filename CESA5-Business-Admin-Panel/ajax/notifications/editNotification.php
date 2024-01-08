<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // include config
        include("../../includes/config.php");
        include("../../includes/functions.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        // initialize array to store all users
        $users = [];

        // get notificaton and user IDs from POST
        if (isset($_POST["notification_id"])) { $notification_id = $_POST["notification_id"]; } else { $notification_id = null; }
        if (isset($_POST["status"]) && $_POST["status"] == 1) { $status = 1; } else { $status = 0; }
        if (isset($_POST["recipients"]) && $_POST["recipients"] <> "") { $recipients = json_decode($_POST["recipients"]); } else { $recipients = null; }

        if (isset($_SESSION["role"]) && $_SESSION["role"] == 1)
        {
            if ($notification_id != null)
            {
                // get the notification label
                $label = getNotificationLabel($conn, $notification_id);

                // update the status of the notification (1 = on; 0 = off)\
                $setStatus = mysqli_prepare($conn, "UPDATE email_types SET active=? WHERE id=?");
                mysqli_stmt_bind_param($setStatus, "ii", $status, $notification_id);
                if (mysqli_stmt_execute($setStatus))
                {
                    if ($status == 1) { 
                        echo "<span class=\"log-success\">Enabled</span> notifications for $label.<br>";
                    } else {
                        echo "<span class=\"log-fail\">Disabled</span> notifications for $label.<br>";
                    }
                }
                else { echo "<span class=\"log-fail\">Failed</span> to edit the status of the notification! Please try again later.<br>"; }

                // unsubscribe all current users for this notification
                $unsubscribe = mysqli_prepare($conn, "UPDATE email_recipients SET subscribed=0 WHERE type_id=? AND subscribed=1");
                mysqli_stmt_bind_param($unsubscribe, "i", $notification_id);
                if (mysqli_stmt_execute($unsubscribe)) // successfully unsubscribed all email recipients
                {
                    // subscribe selected recipients to receive notifications
                    if ($recipients != null && is_array($recipients))
                    {
                        for ($r = 0; $r < count($recipients); $r++)
                        {
                            // store recipient's user ID locally
                            $user_id = $recipients[$r];

                            // verify recipient is a valid user 
                            if ($user_id != null && verifyUser($conn, $user_id))
                            {
                                // check to see if the user is currently subscribed to this notification (or was once subscribed)
                                $check = mysqli_prepare($conn, "SELECT id FROM email_recipients WHERE user_id=? AND type_id=?");
                                mysqli_stmt_bind_param($check, "ii", $user_id, $notification_id);
                                if (mysqli_stmt_execute($check))
                                {
                                    $result = mysqli_stmt_get_result($check);
                                    if (mysqli_num_rows($result) > 0)
                                    {
                                        // subscribe the user to receive email notifications
                                        $subscribe = mysqli_prepare($conn, "UPDATE email_recipients SET subscribed=1 WHERE user_id=? AND type_id=?");
                                        mysqli_stmt_bind_param($subscribe, "ii", $user_id, $notification_id);
                                        mysqli_stmt_execute($subscribe);
                                    }
                                    else
                                    {
                                        // subscribe the user to receive email notifications
                                        $subscribe = mysqli_prepare($conn, "INSERT INTO email_recipients (user_id, type_id, subscribed) VALUES (?, ?, 1)");
                                        mysqli_stmt_bind_param($subscribe, "ii", $user_id, $notification_id);
                                        mysqli_stmt_execute($subscribe);
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        // disconnect from the database
        mysqli_close($conn);
    }
?>