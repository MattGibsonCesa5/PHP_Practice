<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get additional required files
        include("../../includes/functions.php");
        include("../../includes/config.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        // verify the user has permission to view caseloads
        if (checkUserPermission($conn, "VIEW_CASELOADS_ALL"))
        {
            // get parameters from POST
            if (isset($_POST["case_id"]) && $_POST["case_id"] <> "") { $case_id = $_POST["case_id"]; } else { $case_id = null; }
            if (isset($_POST["status"]) && $_POST["status"] == 1) { $status = 1; } else { $status = 0; }

            // verify the caseload
            if ($case_id != null && verifyCase($conn, $case_id))
            {
                // update medicaid billing status
                $updateStatus = mysqli_prepare($conn, "UPDATE cases SET medicaid_billed=? WHERE id=?");
                mysqli_stmt_bind_param($updateStatus, "ii", $status, $case_id);
                if (mysqli_stmt_execute($updateStatus)) 
                {
                    // echo status
                    echo 1;

                    // log medicaid billing status change
                    $message = "Set the medicaid billed status to $status for the case with the ID of $case_id.";
                    $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                    mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                    mysqli_stmt_execute($log);
                }
            }
        }

        // disconnect from the database
        mysqli_close($conn);
    }
?>