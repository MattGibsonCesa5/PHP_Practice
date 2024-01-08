<?php
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        if (isset($_SESSION["role"]) && $_SESSION["role"] == 1)
        {
            // get additional required files
            include("../../includes/functions.php");
            include("../../includes/config.php");

            // connect to the database
            $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

            // get parameters from POST
            if (isset($_POST["change_id"]) && $_POST["change_id"] <> "") { $change_id = $_POST["change_id"]; } else { $change_id = null; }

            // add the employee change notes
            $removeMarkedChange = mysqli_prepare($conn, "DELETE FROM employee_changes WHERE id=?");
            mysqli_stmt_bind_param($removeMarkedChange, "i", $change_id);
            if (mysqli_stmt_execute($removeMarkedChange)) { echo "<span class=\"log-success\">Successfully</span> removed the marked employee change.<br>"; }
            else { echo "<span class=\"log-fail\">Failed</span> to remove the marked employee change. An unexpected error has occurred! Please try again later.<br>"; }

            // disconnect from the database
            mysqli_close($conn);
        }
    }
?>