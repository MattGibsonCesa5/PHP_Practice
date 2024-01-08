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

            if (mysqli_query($conn, "TRUNCATE `services`"))
            {
                echo "<span class=\"log-success\">Successfully</span> deleted all services.<br>"; 

                // delete service costs, invoices, and quarterly costs
                if (mysqli_query($conn, "TRUNCATE `costs`")) { echo "<span class=\"log-success\">Successfully</span> deleted all service costs.<br>"; } else { echo "<span class=\"log-fail\">Failed</span> to delete all service costs.<br>"; }
                if (mysqli_query($conn, "TRUNCATE `services_provided`")) { echo "<span class=\"log-success\">Successfully</span> deleted all invoices.<br>"; } else { echo "<span class=\"log-fail\">Failed</span> to delete all invoices.<br>"; }
                if (!mysqli_query($conn, "TRUNCATE `quarterly_costs`")) { echo "<span class=\"log-fail\">Failed</span> to delete all quarterly costs.<br>"; }
            }

            // log clear
            $message = "Deleted all services. ";
            $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
            mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
            mysqli_stmt_execute($log);

            // disconnect from the database
            mysqli_close($conn);
        }
    }
?>