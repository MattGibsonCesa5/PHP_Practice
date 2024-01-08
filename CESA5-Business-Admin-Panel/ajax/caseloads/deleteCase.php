<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get additional required files
        include("../../includes/functions.php");
        include("../../includes/config.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "DELETE_CASELOADS"))
        {
            // get the caseload ID from POST
            if (isset($_POST["case_id"]) && trim($_POST["case_id"]) <> "") { $case_id = trim($_POST["case_id"]); } else { $case_id != null; }

            if ($case_id != null && verifyCase($conn, $case_id))
            {
                // get the caseload ID
                $caseload_id = getCaseloadID($conn, $case_id);

                // delete the caseload
                $deleteCase = mysqli_prepare($conn, "DELETE FROM cases WHERE id=?");
                mysqli_stmt_bind_param($deleteCase, "i", $case_id);
                if (mysqli_stmt_execute($deleteCase)) 
                { 
                    // display on screen caseload deletion status
                    echo "<span class=\"log-success\">Successfully</span> removed the student from the caseload.<br>"; 

                    // log case deletion
                    $message = "Successfully deleted the case with the ID of $case_id from the caseload with the ID of $caseload_id.";
                    $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                    mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                    mysqli_stmt_execute($log);
                }
                else { echo "<span class=\"log-fail\">Failed</span> to remove the student from the caseload. An unexpected error has occurred! Please try again later.<br>"; }
            }
            else { echo "<span class=\"log-fail\">Failed</span> to remove the student from the caseload. You must select a valid student to remove from the caseload.<br>"; }
        }

        // disconnect from the database
        mysqli_close($conn);
    }
?>