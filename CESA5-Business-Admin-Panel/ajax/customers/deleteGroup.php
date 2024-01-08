<?php
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get additional required files
        include("../../includes/config.php");
        include("../../includes/functions.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "DELETE_CUSTOMER_GROUPS"))
        {
            // get group ID from POST
            if (isset($_POST["group_id"]) && $_POST["group_id"] <> "") { $group_id = $_POST["group_id"]; } else { $group_id = null; }

            if ($group_id != null && is_numeric($group_id))
            {
                // delete the group
                $deleteGroup = mysqli_prepare($conn, "DELETE FROM `groups` WHERE id=?");
                mysqli_stmt_bind_param($deleteGroup, "i", $group_id);
                if (mysqli_stmt_execute($deleteGroup)) // successfully deleted the group; delete other data associated with the group
                {
                    echo "<span class=\"log-success\">Successfully</span> deleted the group.<br>";

                    // delete the group members
                    $deleteGroupMembers = mysqli_prepare($conn, "DELETE FROM group_members WHERE group_id=?");
                    mysqli_stmt_bind_param($deleteGroupMembers, "i", $group_id);
                    if (!mysqli_stmt_execute($deleteGroupMembers)) { echo "<span class=\"log-fail\">Failed</span> to remove all members from the group.<br>"; }

                    // log group deletion
                    $message = "Successfully deleted the group with the ID of $group_id. ";
                    $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                    mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                    mysqli_stmt_execute($log);
                }
                else { echo "<span class=\"log-fail\">Failed</span> to delete the group. An unknown error has occurred. Please try again later.<br>"; }
            }
            else { echo "<span class=\"log-fail\">Failed</span> to delete the group. The group ID was invalid.<br>"; }
        }
        else { echo "<span class=\"log-fail\">Failed</span> to delete the group. Your account does not have permission to delete customer groups!<br>"; }

        // disconnect from the database
        mysqli_close($conn);
    }
?>