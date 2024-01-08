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
                $verifyMessage = mysqli_prepare($conn, "SELECT * FROM messages WHERE id=?");
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

                        // if the recipient ID of the message is the same as current user (or super admin), and the message is not deleted; continue
                        if (($recipient_id == $_SESSION["id"] && $is_deleted == 0) || ($_SESSION["id"] == 0 && $_SESSION["email"] == "super@cesa5.org"))
                        {
                            // continue storing message details locally
                            $sender_id = $message["sender_id"];
                            $subject = $message["subject"];
                            $content = $message["message"];
                            $important = $message["important"];
                            $read_by_recipient = $message["read_by_recipient"];
                            $timestamp = $message["timestamp"];

                            // convert the timestamp to date format
                            // example: Tue, Feb 21, 8:18 AM
                            $date = date("D, M j, Y, g:i A", strtotime($timestamp));

                            // get sender name
                            if ($sender_id == 0) { $sender = "SUPER ADMIN"; } else { $sender = getUserDisplayName($conn, $sender_id); }

                            // create the message modal to be displayed
                            ?>
                                <div class="modal fade" tabindex="-1" role="dialog" id="messageModal" data-bs-backdrop="static" aria-labelledby="messageModalLabel" aria-hidden="true">
                                    <div class="modal-dialog modal-lg" role="document">
                                        <div class="modal-content">
                                            <div class="modal-header primary-modal-header">
                                                <h5 class="modal-title primary-modal-title" id="messageModalLabel"></h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>

                                            <div class="modal-body">
                                                <div class="form-row mb-3">
                                                    <label for="sender" class="form-label m-0">From:</label>
                                                    <div class="input-group flex-nowrap">
                                                        <span class="input-group-text" id="sender-icon"><i class="fa-solid fa-user"></i></span>
                                                        <input id="sender" name="sender" class="form-control" type="text" aria-label="sender" aria-describedby="sender-icon" disabled readonly value="<?php echo $sender; ?>">
                                                    </div>
                                                </div>

                                                <div class="form-row mb-3">
                                                    <label for="recipients" class="form-label m-0">Subject:</label>
                                                    <div class="input-group flex-nowrap">
                                                        <span class="input-group-text" id="subject-icon"><i class="fa-solid fa-star"></i></span>
                                                        <input id="subject" name="subject" class="form-control" type="text" aria-label="subject" aria-describedby="subject-icon" disabled readonly value="<?php echo $subject; ?>">
                                                    </div>
                                                </div>

                                                <div class="form-row mb-3">
                                                    <label for="recipients" class="form-label m-0">Message:</label>
                                                    <div class="input-group flex-nowrap">
                                                        <span class="input-group-text" id="message-icon"><i class="fa-solid fa-comment"></i></span>
                                                        <textarea id="message" name="message" class="form-control" type="text" aria-label="message" aria-describedby="message-icon" rows="8" disabled readonly><?php echo $content; ?></textarea>
                                                    </div>
                                                </div>

                                                <div class="form-row">
                                                    <p class="text-end m-0"><?php echo $date; ?></p>
                                                </div>
                                            </div>

                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-danger" onclick="deleteMessage(<?php echo $message_id; ?>);"><i class="fa-solid fa-trash-can"></i> Delete Message</button>
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> Close</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php

                            // set the message as read only if the message was not already read; do not let super admin account update read status
                            if ($read_by_recipient == 0 && $recipient_id == $_SESSION["id"])
                            {
                                $markAsRead = mysqli_prepare($conn, "UPDATE messages SET read_by_recipient=1 WHERE id=?");
                                mysqli_stmt_bind_param($markAsRead, "i", $message_id);
                                if (!mysqli_stmt_execute($markAsRead)) { } // TODO: handle error if we failed to update read status
                            }
                        }
                    }
                }

                // disconnect from the database
                mysqli_close($conn);
            }
        }
    }
?>