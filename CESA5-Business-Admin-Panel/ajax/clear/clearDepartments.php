<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        if (isset($_SESSION["role"]) && $_SESSION["role"] == 1)
        {
            // include config
            include("../../includes/config.php");
            include("../../getSettings.php");

            // get parameters from POST
            if (isset($_POST["clearAll"]) && $_POST["clearAll"] <> "") { $clearAll = $_POST["clearAll"]; } else { $clearAll = 0; }
            if (isset($_POST["clearMembers"]) && $_POST["clearMembers"] <> "") { $clearMembers = $_POST["clearMembers"]; } else { $clearMembers = 0; }

            // connect to the database
            $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

            if ($clearAll == 1)
            {
                // delete all departments and remove all department members
                if (mysqli_query($conn, "TRUNCATE `department_members`")) // successfully cleared all department members
                {
                    echo "<span class=\"log-success\">Successfully</span> removed all department members.<br>";

                    // delete all departments
                    if (mysqli_query($conn, "TRUNCATE `departments`")) { echo "<span class=\"log-success\">Successfully</span> deleted all departments.<br>"; }
                    else { echo "<span class=\"log-fail\">Failed</span> to delete all departments.<br>"; }
                }
                else { echo "<span class=\"log-fail\">Failed</span> to remove all department members. Skipping deleting all departments.<br>"; } // failed to clear all department members

                // log clear
                $message = "Cleared all departments.";
                $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                mysqli_stmt_execute($log);
            }
            else if ($clearMembers == 1)
            {
                // only delete all department members
                if (mysqli_query($conn, "TRUNCATE `department_members`")) { echo "<span class=\"log-success\">Successfully</span> removed all department members.<br>"; } // successfully cleared all department members
                else { echo "<span class=\"log-fail\">Failed</span> to remove all department members.<br>"; } // failed to clear all department members

                // log clear
                $message = "Cleared all department members.";
                $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                mysqli_stmt_execute($log);
            }

            // disconnect from the database
            mysqli_close($conn);
        }
    }
?>