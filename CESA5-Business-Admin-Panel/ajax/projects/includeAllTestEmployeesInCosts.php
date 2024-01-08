<?php
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        if (isset($_SESSION["role"]) && $_SESSION["role"] == 1)
        {
            include("../../includes/config.php");
            include("../../getSettings.php");

            // connect to the database
            $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

            $includeAll = mysqli_prepare($conn, "UPDATE project_employees_misc SET costs_inclusion=1 WHERE period_id=?");
            mysqli_stmt_bind_param($includeAll, "i", $GLOBAL_SETTINGS["active_period"]);
            if (mysqli_stmt_execute($includeAll)) { echo "<span class=\"log-success\">Successfully</span> included all test employees in cost calculations."; }
            else { echo "<span class=\"log-fail\">Failed</span> to include all test employees in cost calculations."; }

            // disconnect from the database
            mysqli_close($conn);
        }
    }
?>