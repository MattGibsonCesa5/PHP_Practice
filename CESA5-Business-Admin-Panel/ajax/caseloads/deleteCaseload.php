<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get additional required files
        include("../../includes/functions.php");
        include("../../includes/config.php");
        include("../../getSettings.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "REMOVE_THERAPISTS"))
        {
            // get the caseload ID from POST
            if (isset($_POST["caseload_id"]) && is_numeric($_POST["caseload_id"])) { $caseload_id = $_POST["caseload_id"]; } else { $caseload_id != null; }

            // verify the caseload exists
            if (verifyCaseload($conn, $caseload_id))
            {
                // get caseload display name
                $caseload_name = getCaseloadDisplayName($conn, $caseload_id);

                // delete the caseload
                $deleteCaseload = mysqli_prepare($conn, "DELETE FROM caseloads WHERE id=?");
                mysqli_stmt_bind_param($deleteCaseload, "i", $caseload_id);
                if (mysqli_stmt_execute($deleteCaseload)) 
                { 
                    // display caseload deletion status
                    echo "<span class=\"log-success\">Successfully</span> deleted the $caseload_name caseload.<br><br>Now attempting to delete all cases assigned to this caseload...<br>"; 

                    // log caseload deletion
                    $message = "Successfully deleted the $caseload_name caseload with the ID of $caseload_id.";
                    $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                    mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                    mysqli_stmt_execute($log);

                    // delete all cases assigned to this caseload
                    $deleteCases = mysqli_prepare($conn, "DELETE FROM cases WHERE caseload_id=?");
                    mysqli_stmt_bind_param($deleteCases, "i", $caseload_id);
                    if (mysqli_stmt_execute($deleteCases)) 
                    {                         
                        // store the number of affected rows
                        $deleted_cases = mysqli_affected_rows($conn);

                        // display cases deletion status
                        echo "<span class=\"log-success\">Successfully</span> deleted $deleted_cases cases assigned to the $caseload_name caseload!<br>"; 

                        // log caseload deletion
                        $message = "Successfully deleted $deleted_cases cases from the $caseload_name caseload (caseload ID: $caseload_id).";
                        $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                        mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                        mysqli_stmt_execute($log);
                    }
                    else { echo "<span class=\"log-fail\">Failed</span> to delete all cases assigned to the deleted caseload!<br>"; }
                }
                else { echo "<span class=\"log-fail\">Failed</span> to delete the caseload. An unexpected error has occurred! Please try again later.<br>"; }
            }
            else { echo "<span class=\"log-fail\">Failed</span> to delete the caseload. The caseload selected does not exist!<br>"; }
        }
        
        // disconnect from the database
        mysqli_close($conn);
    }
?>