<?php
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get additional required files
        include("../../includes/functions.php");
        include("../../includes/config.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "EDIT_EMPLOYEES"))
        {
            // get parameters from POST
            if (isset($_POST["employee_id"]) && $_POST["employee_id"] <> "") { $employee_id = $_POST["employee_id"]; } else { $employee_id = null; }
            if (isset($_POST["item_changed"]) && $_POST["item_changed"] <> "") { $item_changed = $_POST["item_changed"]; } else { $item_changed = null; }
            if (isset($_POST["initial_period"]) && $_POST["initial_period"] <> "") { $initial_period = $_POST["initial_period"]; } else { $initial_period = null; }
            if (isset($_POST["change_period"]) && $_POST["change_period"] <> "") { $change_period = $_POST["change_period"]; } else { $change_period = null; }
            if (isset($_POST["notes"]) && $_POST["notes"] <> "") { $notes = $_POST["notes"]; } else { $notes = ""; }

            // verify the initial period exists
            if (verifyPeriod($conn, $initial_period))
            {
                // verify the change period exists
                if (verifyPeriod($conn, $change_period))
                {
                    // verify the employee exists
                    if (checkExistingEmployee($conn, $employee_id))
                    {
                        // add the employee change notes
                        $markChange = mysqli_prepare($conn, "INSERT INTO employee_changes (employee_id, from_period_id, to_period_id, field_changed, notes, change_user_id) VALUES (?, ?, ?, ?, ?, ?)");
                        mysqli_stmt_bind_param($markChange, "iiissi", $employee_id, $initial_period, $change_period, $item_changed, $notes, $_SESSION["id"]);
                        if (mysqli_stmt_execute($markChange)) { echo "<span class=\"log-success\">Successfully</span> marked the employee changes.<br>"; }
                        else { echo "<span class=\"log-fail\">Failed</span> to mark employee changes. An unexpected error has occurred! Please try again later.<br>"; }
                    }
                    else { echo "<span class=\"log-fail\">Failed</span> to mark employee changes. The employee selected does not exist!<br>"; }
                }
                else { echo "<span class=\"log-fail\">Failed</span> to mark employee changes. The change period selected does not exist!<br>"; }
            }
            else { echo "<span class=\"log-fail\">Failed</span> to mark employee changes. The initial period selected does not exist!<br>"; }
        }
        else { echo "<span class=\"log-fail\">Failed</span> to mark employee changes. Your account does not have access to mark employee changes!<br>"; }

        // disconnect from the database
        mysqli_close($conn);
    }
?>