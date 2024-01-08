<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get additional required files
        include("../../includes/functions.php");
        include("../../includes/config.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "VIEW_CASELOADS_ALL") && checkUserPermission($conn, "VIEW_THERAPISTS"))
        {
            // get request ID from POST (not required)
            if (isset($_POST["request_id"]) && $_POST["request_id"] <> "") { $request_id = $_POST["request_id"]; } else { $request_id = null; }

            // verify the request ID exists
            $checkRequest = mysqli_prepare($conn, "SELECT id FROM caseload_transfers WHERE id=?");
            mysqli_stmt_bind_param($checkRequest, "i", $request_id);
            if (mysqli_stmt_execute($checkRequest))
            {
                $checkRequestResult = mysqli_stmt_get_result($checkRequest);
                if (mysqli_num_rows($checkRequestResult) > 0) // request exists; continue rejection
                {
                    // get the current timestamp
                    $timestamp = date("Y-m-d H:i:s");

                    // reject the transfer
                    $rejectRequest = mysqli_prepare($conn, "UPDATE caseload_transfers SET transfer_status=2, accepted_by=?, accepted_at=? WHERE id=?");
                    mysqli_stmt_bind_param($rejectRequest, "isi", $_SESSION["id"], $timestamp, $request_id);
                    if (mysqli_stmt_execute($rejectRequest)) { echo "<span class=\"log-success\">Successfully</span> rejected the caseload transfer request!<br>"; }
                    else { echo "<span class=\"log-fail\">Failed</span> to reject the caseload transfer request. An unexpected error has occurred! Please try again later.<br>"; }
                }
                else { echo "<span class=\"log-fail\">Failed</span> to reject the caseload transfer request. The request selected no longer exists!<br>"; }
            }
            else { echo "<span class=\"log-fail\">Failed</span> to reject the caseload transfer request. An unexpected error has occurred! Please try again later.<br>"; }
        }
        else { echo "<span class=\"log-fail\">Failed</span> to reject the caseload transfer request. An unexpected error has occurred! Please try again later.<br>"; }

        // disconnect from the database
        mysqli_close($conn);
    }
?>