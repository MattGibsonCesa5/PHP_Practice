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

            $excludeAll = mysqli_prepare($conn, "UPDATE project_employees_misc SET costs_inclusion=0 WHERE period_id=?");
            mysqli_stmt_bind_param($excludeAll, "i", $GLOBAL_SETTINGS["active_period"]);
            if (mysqli_stmt_execute($excludeAll)) { echo "<span class=\"log-success\">Successfully</span> excluded all test employees from cost calculations."; }
            else { echo "<span class=\"log-fail\">Failed</span> to exclude all test employees from cost calculations."; }

            // disconnect from the database
            mysqli_close($conn);
        }
    }
?>