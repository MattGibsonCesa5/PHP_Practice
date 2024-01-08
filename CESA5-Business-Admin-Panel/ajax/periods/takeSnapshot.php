<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        if (isset($_SESSION["role"]) && $_SESSION["role"] == 1)
        {
            // get additional required files
            include("../../includes/config.php");
            include("../../includes/functions.php");

            // connect to the database
            $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

            // get parameters from POST
            if (isset($_POST["period_id"]) && is_numeric($_POST["period_id"])) { $period_id = $_POST["period_id"]; } else { $period_id = null; }
            if (isset($_POST["quarter"]) && is_numeric($_POST["quarter"])) { $quarter = $_POST["quarter"]; } else { $quarter = null; }

            // verify the period is valid
            if ($period_id != null && verifyPeriod($conn, $period_id))
            {
                // verify the quarter is valid 
                if ($quarter >= 1 && $quarter <= 4)
                {
                    // get the name of the period
                    $period_name = getPeriodName($conn, $period_id);

                    // take a snapshot of the quarter
                    if (snapshotQuarter($conn, $period_id, $quarter))
                    {
                        // display success
                        echo "<span class=\"log-success\">Successfully</span> took a snapshot of Q$quarter for $period_name.<br>";
                    }
                    else
                    {
                        // display error
                        echo "<span class=\"log-fail\">Failed</span> to take a snapshot of Q$quarter for $period_name.<br>";
                    }
                }
            }

            // disconnect from the database
            mysqli_close($conn);
        }
    }
?>