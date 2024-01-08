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
            // get parameters from POST
            if (isset($_POST["case_id"]) && $_POST["case_id"] <> "") { $case_id = $_POST["case_id"]; } else { $case_id = null; }
            if (isset($_POST["new_caseload"]) && $_POST["new_caseload"] <> "") { $caseload_id = $_POST["new_caseload"]; } else { $caseload_id = null; }
            if (isset($_POST["transfer_date"]) && $_POST["transfer_date"] <> "") { $transfer_date = $_POST["transfer_date"]; } else { $transfer_date = null; }
            if (isset($_POST["comments"]) && $_POST["comments"] <> "") { $comments = $_POST["comments"]; } else { $comments = ""; }
            if (isset($_POST["IEP_status"]) && is_numeric($_POST["IEP_status"])) { $IEP_status = $_POST["IEP_status"]; } else { $IEP_status = 0; }

            // verify the caseload exists
            if (verifyCase($conn, $case_id))
            {
                // verify the caseload exists
                if ($caseload_id != null && verifyCaseload($conn, $caseload_id))
                {
                    if ($transfer_date != null)
                    {
                        // convert the transfer and end dates to the correct database format
                        $DB_transfer_date = date("Y-m-d", strtotime($transfer_date));

                        // submit transfer request
                        $requestTransfer = mysqli_prepare($conn, "INSERT INTO caseload_transfers (case_id, new_caseload_id, iep_completed, transfer_date, comments, requested_by) VALUES (?, ?, ?, ?, ?, ?)");
                        mysqli_stmt_bind_param($requestTransfer, "iiissi", $case_id, $caseload_id, $IEP_status, $DB_transfer_date, $comments, $_SESSION["id"]);
                        if (mysqli_stmt_execute($requestTransfer)) { echo "<span class=\"log-success\">Successfully</span> submitted the caseload transfer request.<br>"; }
                        else { echo "<span class=\"log-fail\">Failed</span> to request a caseload transfer. An unexpected error has occurred! Please try again later.<br>"; }
                    }
                    else { echo "<span class=\"log-fail\">Failed</span> to request a caseload transfer. You are required to provide a transfer date!<br>"; }
                }
                else if ($caseload_id == null)
                {
                    if ($transfer_date != null)
                    {
                        // convert the transfer and end dates to the correct database format
                        $DB_transfer_date = date("Y-m-d", strtotime($transfer_date));

                        // submit transfer request
                        $requestTransfer = mysqli_prepare($conn, "INSERT INTO caseload_transfers (case_id, iep_completed, transfer_date, comments, requested_by) VALUES (?, ?, ?, ?, ?)");
                        mysqli_stmt_bind_param($requestTransfer, "iissi", $case_id, $IEP_status, $DB_transfer_date, $comments, $_SESSION["id"]);
                        if (mysqli_stmt_execute($requestTransfer)) { echo "<span class=\"log-success\">Successfully</span> submitted the caseload transfer request.<br>"; }
                        else { echo "<span class=\"log-fail\">Failed</span> to request a caseload transfer. An unexpected error has occurred! Please try again later.<br>"; }
                    }
                    else { echo "<span class=\"log-fail\">Failed</span> to request a caseload transfer. You are required to provide a transfer date!<br>"; }
                }
                else { echo "<span class=\"log-fail\">Failed</span> to request a caseload transfer. The caseload you want to transfer the student to does not exist!.<br>"; }
            }
            else { echo "<span class=\"log-fail\">Failed</span> to request a caseload transfer. The case selected does not exist!<br>"; }
        }
        else { echo "<span class=\"log-fail\">Failed</span> to request a caseload transfer. Your account does not have permission to edit caseloads.<br>"; }

        // disconnect from the database
        mysqli_close($conn);
    }
?>