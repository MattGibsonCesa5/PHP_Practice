<?php
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1) 
    { 
        if (isset($_SESSION["role"]) && !isset($_SESSION["masquerade"]))
        {        
            // get additional required files
            include("../../includes/functions.php");
            include("../../includes/config.php");

            // initialize variables
            $successes = $errors = 0;

            // get parameters from POST
            if (isset($_POST["recipients"]) && $_POST["recipients"] <> "") { $recipients = json_decode($_POST["recipients"]); } else { $recipients = null; }
            if (isset($_POST["subject"]) && $_POST["subject"] <> "") { $subject = trim($_POST["subject"]); } else { $subject = null; }
            if (isset($_POST["message"]) && $_POST["message"] <> "") { $message = trim($_POST["message"]); } else { $message = null; }
            if (isset($_POST["important"]) && is_numeric($_POST["important"]) && $_SESSION["role"] == 1) { $important = $_POST["important"]; } else { $important = 0; }

            // validate the important variable
            if ($important != 1) { $important = 0; }

            if ($recipients != null)
            {
                if ($subject != null)
                {
                    if ($message != null)
                    {
                        // connect to the database
                        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

                        for ($r = 0; $r < count($recipients); $r++)
                        {
                            // verify the recipient exists
                            if (verifyUser($conn, $recipients[$r])) // recipient exists; continue
                            {
                                // send the message
                                $sendMessage = mysqli_prepare($conn, "INSERT INTO messages (sender_id, recipient_id, subject, message, important) VALUES (?, ?, ?, ?, ?)");
                                mysqli_stmt_bind_param($sendMessage, "iissi", $_SESSION["id"], $recipients[$r], $subject, $message, $important);
                                if (mysqli_stmt_execute($sendMessage)) { $successes++; } // successfully sent the message
                                else // failed to send the message
                                { 
                                    $name = getUserDisplayName($conn, $recipients[$r]);
                                    echo "<span class=\"log-fail\">Failed</span> to send the message to $name.<br>";
                                    $errors++; 
                                } 
                            }
                            else // recipient does not exist; display error, but continue sending to other recipients 
                            { 
                                echo "<span class=\"log-fail\">Failed</span> to send the message to the recipient with the ID of ".$recipients[$r].". The recipient does not exist!<br>";
                                $errors++;
                            }
                        }

                        // print status to be returned
                        if ($errors == 0 && $successes > 0) { echo 0; }
                        else if ($errors == 0 && $successes == 0) { echo 4; }

                        // disconnect from the database
                        mysqli_close($conn);
                    }
                    else { echo 3; }
                }
                else { echo 2; }
            }
            else { echo 1; }
        }
    }
?>