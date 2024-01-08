<?php
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get additional required files
        include("../../includes/functions.php");
        include("../../includes/config.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "VIEW_CASELOADS_ALL"))
        {
            // get the caseload ID from POST
            if (isset($_POST["case_id"]) && $_POST["case_id"] <> "") { $case_id = $_POST["case_id"]; } else { $case_id = null; }
            if (isset($_POST["uos"]) && is_numeric($_POST["uos"])) { $uos = $_POST["uos"]; } else { $uos = null; }

            if (verifyCase($conn, $case_id))
            {
                if ($uos != null && is_numeric($uos))
                {
                    $updateAdjustment = mysqli_prepare($conn, "UPDATE cases SET uos_adjustment=? WHERE id=?");
                    mysqli_stmt_bind_param($updateAdjustment, "di", $uos, $case_id);
                    mysqli_stmt_execute($updateAdjustment);
                }
            }
        }

        // disconnect from the database
        mysqli_close($conn);
    }
?>