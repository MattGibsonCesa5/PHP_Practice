<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        if (isset($_SESSION["role"]) && $_SESSION["role"] == 1)
        {
            // include config
            include("../../includes/config.php");
            include("../../getSettings.php");

            // connect to the database
            $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

            if (mysqli_query($conn, "TRUNCATE `employees`"))
            {
                echo "<span class=\"log-success\">Successfully</span> cleared all employees. ";

                // clear all user settings
                if (!mysqli_query($conn, "TRUNCATE `user_settings`")) { echo "<span class=\"log-fail\">Failed</span> to delete all users' settings. "; }

                // attempt to delete all employees addresses
                if (!mysqli_query($conn, "TRUNCATE `employee_addresses`")) { echo "<span class=\"log-fail\">Failed</span> to delete all employee addresses. "; }

                // attempt to delete all department members
                if (!mysqli_query($conn, "TRUNCATE `department_members`")) { echo "<span class=\"log-fail\">Failed</span> to remvoe all employees from their department(s). "; }

                // attempt to remove all employees from projects in the current active period
                $clearEmpsFromProjects = mysqli_prepare($conn, "DELETE FROM `project_employees` WHERE period_id=?");
                mysqli_stmt_bind_param($clearEmpsFromProjects, "i", $GLOBAL_SETTINGS["active_period"]);
                if (!mysqli_stmt_execute($clearEmpsFromProjects)) { echo "<span class=\"log-fail\">Failed</span> to remove all employees from projects in the active period. "; }
            }
            else { echo "<span class=\"log-fail\">Failed</span> to clear all employees. An unexpected error has occurred. Please try again later. "; }

            // log clear
            $message = "Cleared all employees.";
            $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
            mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
            mysqli_stmt_execute($log);

            // disconnect from the database
            mysqli_close($conn);
        }
    }
?>