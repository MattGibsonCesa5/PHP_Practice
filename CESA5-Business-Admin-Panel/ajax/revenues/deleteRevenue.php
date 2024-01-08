<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get additional required files
        include("../../includes/config.php");
        include("../../includes/functions.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "DELETE_REVENUES"))
        {
            // get the parameters from POST
            if (isset($_POST["id"]) && $_POST["id"] <> "") { $id = $_POST["id"]; } else { $id = null; }

            if ($id != null)
            {
                if (verifyRevenue($conn, $id)) // verify the revenue exists
                {
                    $deleteRevenue = mysqli_prepare($conn, "DELETE FROM revenues WHERE id=?");
                    mysqli_stmt_bind_param($deleteRevenue, "i", $id);
                    if (mysqli_stmt_execute($deleteRevenue)) 
                    { 
                        echo "<span class=\"log-success\">Successfully</span> deleted the revenue.<br>"; 

                        // log revenue deletion
                        $message = "Successfully deleted the revenue with the ID of $id. ";
                        $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                        mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                        mysqli_stmt_execute($log);
                    }
                    else { echo "<span class=\"log-fail\">Failed</span> to delete the revenue.<br>"; }
                }
                else { echo "<span class=\"log-fail\">Failed</span> to delete the revenue. The revenue does not exist!<br>"; }
            }
            else { echo "<span class=\"log-fail\">Failed</span> to delete the revenue. No revenue was selected.<br>"; }
        }
        else { echo "<span class=\"log-fail\">Failed</span> to delete the revenue. Your account does not have permission to delete revenues!<br>"; }

        // disconnect from the database
        mysqli_close($conn);
    }
?>