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
            if (isset($_POST["extra_evals"]) && is_numeric($_POST["extra_evals"])) { $extra_evals = $_POST["extra_evals"]; } else { $extra_evals = null; }

            if (verifyCase($conn, $case_id))
            {
                if ($extra_evals != null && is_numeric($extra_evals))
                {
                    if ($extra_evals >= 0)
                    {
                        $updateAdjustment = mysqli_prepare($conn, "UPDATE cases SET extra_evaluations=? WHERE id=?");
                        mysqli_stmt_bind_param($updateAdjustment, "di", $extra_evals, $case_id);
                        if (mysqli_stmt_execute($updateAdjustment))
                        {
                            // log case edit
                            $message = "Set number of extra evaluations to $extra_evals for the case with ID of $case_id.";
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