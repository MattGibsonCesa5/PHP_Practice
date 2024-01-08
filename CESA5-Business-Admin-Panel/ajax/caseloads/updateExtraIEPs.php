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

        if (checkUserPermission($conn, "EDIT_CASELOADS"))
        {
            // get the caseload ID from POST
            if (isset($_POST["case_id"]) && $_POST["case_id"] <> "") { $case_id = $_POST["case_id"]; } else { $case_id = null; }
            if (isset($_POST["extra_ieps"]) && is_numeric($_POST["extra_ieps"])) { $extra_ieps = $_POST["extra_ieps"]; } else { $extra_ieps = null; }

            if (verifyCase($conn, $case_id))
            {
                if ($extra_ieps != null && is_numeric($extra_ieps))
                {
                    if ($extra_ieps >= 0)
                    {
                        $updateAdjustment = mysqli_prepare($conn, "UPDATE cases SET extra_ieps=? WHERE id=?");
                        mysqli_stmt_bind_param($updateAdjustment, "di", $extra_ieps, $case_id);
                        if (mysqli_stmt_execute($updateAdjustment))
                        {
                            // log case edit
                            $message = "Set number of extra IEPs to $extra_ieps for the case with ID of $case_id.";
                            $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                            mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                            mysqli_stmt_execute($log);
                        }
                    }
                }
            }
        }
    }
?>