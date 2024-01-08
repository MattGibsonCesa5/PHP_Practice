<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        if (isset($_SESSION["role"]) && $_SESSION["role"] == 1)
        {
            // get additional required files
            include("../../includes/config.php");

            // get the new rate from POST
            if (isset($_POST["rate"]) && is_numeric($_POST["rate"])) { $newRate = $_POST["rate"]; } else { $newRate = null; }

            if ($newRate != null)
            {
                // connect to the database
                $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

                // update the rate
                $updateRate = mysqli_prepare($conn, "UPDATE settings SET salary_projection_rate=? WHERE id=1");
                mysqli_stmt_bind_param($updateRate, "d", $newRate);
                if (!mysqli_stmt_execute($updateRate)) { echo "<span class=\"log-fail\">Failed</span> to update the salary projection rate. An unexpected error has occurred! Please try again later. "; }

                // disconnect from the database
                mysqli_close($conn);
            }
            else { echo "<span class=\"log-fail\">Failed</span> to update the salary projection rate. An unexpected error has occurred! Please try again later. "; }
        }
    }
?>