<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get additional required files
        include("../../includes/functions.php");
        include("../../includes/config.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "EDIT_CASELOADS"))
        {
            // get parameters from POST
            if (isset($_POST["change_id"]) && $_POST["change_id"] <> "") { $change_id = $_POST["change_id"]; } else { $change_id = null; }

            // get the case based on the change
            if ($case_id = getCaseIDFromChange($conn, $change_id))
            {
                if (verifyCase($conn, $case_id))
                {
                    // remove the caseload change
                    $removeChange = mysqli_prepare($conn, "DELETE FROM case_changes WHERE id=?");
                    mysqli_stmt_bind_param($removeChange, "i", $change_id);
                    if (mysqli_stmt_execute($removeChange)) 
                    { 
                        // log successful change removal
                        echo "<span class=\"log-success\">Successfully</span> removed the change from the caseload.<br>"; 
                        $message = "Successfully removed a change in the case (case ID: $case_id) with case change ID $change_id.";
                        $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                        mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                        mysqli_stmt_execute($log); 
                    }
                    else { echo "<span class=\"log-fail\">Failed</span> to remove the change from the caseload. An unexpected error has occurred! Please try again later.<br>"; }
                }
                else { echo "<span class=\"log-fail\">Failed</span> to remove the case change. The case you are attempting to edit the change for is invalid!<br>"; }
            }
            else { echo "<span class=\"log-fail\">Failed</span> to remove the case change. The case you are attempting to edit the change for is invalid!<br>"; }
        }

        // disconnect from the database
        mysqli_close($conn);
    }
?>