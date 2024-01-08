<?php
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get additional required files
        include("../../includes/functions.php");
        include("../../includes/config.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if ($_SESSION["role"] == 1)
        {
            // get the caseload ID from POST
            if (isset($_POST["caseload_id"]) && $_POST["caseload_id"] <> "") { $caseload_id = $_POST["caseload_id"]; } else { $caseload_id = null; }
            if (isset($_POST["period"]) && $_POST["period"] <> "") { $period = $_POST["period"]; } else { $period = null; }

            // verify the caseload exists
            if (verifyCaseload($conn, $caseload_id))
            {
                if ($caseload_id <> "" && $caseload_id != null && $caseload_id != "undefined")
                {
                    if ($period != null && $period_id = getPeriodID($conn, $period))
                    {
                        // get the caseload name
                        $caseload_name = getCaseloadDisplayName($conn, $caseload_id);

                        // delete all cases for the caseload in the period
                        $clearCases = mysqli_prepare($conn, "DELETE FROM cases WHERE caseload_id=? AND period_id=?");
                        mysqli_stmt_bind_param($clearCases, "ii", $caseload_id, $period_id);
                        if (mysqli_stmt_execute($clearCases))
                        {
                            // get the number of cases deleted
                            $casesCleared = mysqli_affected_rows($conn);

                            // log caseload clear
                            echo "<span class=\"log-success\">Successfully</span> cleared the caseload. There were $casesCleared cases cleared from the $caseload_name caseload for $period.<br>";
                            $message = "Successfully cleared the caseload. There were $casesCleared cases cleared from the $caseload_name caseload (caseload ID: $caseload_id) for $period (period ID: $period_id).";
                            $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                            mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                            mysqli_stmt_execute($log);
                        }
                        else { echo "<span class=\"log-fail\">Failed</span> to clear the $caseload_name caseload for $period. An unexpected error has occurred! Please try again later.<br>"; }
                    }
                    else { echo "<span class=\"log-fail\">Failed</span> to clear the caseload. The period selected was invalid!<br>"; }
                }
                else { echo "<span class=\"log-fail\">Failed</span> to clear the caseload. The caseload selected was invalid!<br>"; }
            }
            else { echo "<span class=\"log-fail\">Failed</span> to clear the caseload. The caseload selected was invalid!<br>"; }
        }
        else { echo "Your account does not have permission to perform this action!<br>"; }

        // disconnect from the database
        mysqli_close($conn);
    }
?>