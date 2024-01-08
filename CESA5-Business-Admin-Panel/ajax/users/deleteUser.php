<?php
    // start the session
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1) 
    { 
        // get additional required files
        include("../../includes/functions.php");
        include("../../includes/config.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        // get the parameter from POST
        if (isset($_POST["user_id"]) && trim($_POST["user_id"]) <> "") { $user_id = trim($_POST["user_id"]); } else { $user_id = null; }

        // validate parameters
        if ($user_id != null && verifyUser($conn, $user_id))
        {
            // ADMIN DELETE
            if (isset($_SESSION["role"]) && $_SESSION["role"] == 1)
            {
                $delete = mysqli_prepare($conn, "UPDATE users SET status=2 WHERE id=?");
                mysqli_stmt_bind_param($delete, "i", $user_id);
                if (mysqli_stmt_execute($delete)) 
                {
                    // log the user deletion
                    echo "<span class=\"log-success\">Successfully</span> deleted the user with ID $user_id.<br>"; 
                    $message = "Successfully deleted the user with ID $user_id.";
                    $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                    mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                    mysqli_stmt_execute($log);
                }
                else { echo "<span class=\"log-fail\">Failed</span> to delete the user. An unexpected error has occurred! Please try again later.<br>"; }
            }
            // DISTRICT ADMIN DELETE
            else if (isset($_SESSION["district"]) && $_SESSION["district"]["status"] == 1 && ($_SESSION["district"]["role"] == "Admin" || $_SESSION["district"]["role"] == "Editor"))
            {
                $delete = mysqli_prepare($conn, "UPDATE users SET status=2 WHERE id=? AND customer_id=?");
                mysqli_stmt_bind_param($delete, "ii", $user_id, $_SESSION["district"]["id"]);
                if (mysqli_stmt_execute($delete)) 
                {
                    if (mysqli_affected_rows($conn) == 1)
                    {
                        // log the user deletion
                        echo "<span class=\"log-success\">Successfully</span> deleted the user.<br>"; 
                        $message = "Successfully deleted the user with ID $user_id.";
                        $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                        mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                        mysqli_stmt_execute($log);
                    }
                    else { echo "<span class=\"log-fail\">Failed</span> to delete the user. An unexpected error has occurred! Please try again later.<br>"; }
                }
                else { echo "<span class=\"log-fail\">Failed</span> to delete the user. An unexpected error has occurred! Please try again later.<br>"; }
            }
            else { echo "Unauthorized to perform this action!"; }
        }
        else { echo "<span class=\"log-fail\">Failed</span> to delete the user. The user you are trying to delete does not exist!<br>"; }

        // disconnect from the database
        mysqli_close($conn);
    }
?>