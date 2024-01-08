<?php
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get the required files
        include("../../includes/config.php");
        include("../../includes/functions.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "BUDGET_PROJECTS_ALL") || checkUserPermission($conn, "BUDGET_PROJECTS_ASSIGNED"))
        {
            // get parameters from POST
            if (isset($_POST["id"]) && is_numeric($_POST["id"])) { $id = $_POST["id"]; } else { $id = null; }
            if (isset($_POST["code"]) && $_POST["code"] <> "") { $code = $_POST["code"]; } else { $code = null; }
            if (isset($_POST["label"]) && $_POST["label"] <> "") { $label = trim($_POST["label"]); } else { $label = null; }
            if (isset($_POST["rate"]) && is_numeric($_POST["rate"])) { $rate = $_POST["rate"]; } else { $rate = 0; }
            if (isset($_POST["days"]) && is_numeric($_POST["days"])) { $days = $_POST["days"]; } else { $days = 0; }
            if (isset($_POST["health"]) && is_numeric($_POST["health"])) { $health = $_POST["health"]; } else { $health = 0; }
            if (isset($_POST["dental"]) && is_numeric($_POST["dental"])) { $dental = $_POST["dental"]; } else { $dental = 0; }
            if (isset($_POST["wrs"]) && is_numeric($_POST["wrs"])) { $wrs = $_POST["wrs"]; } else { $wrs = 0; }
            if (isset($_POST["inclusion"]) && is_numeric($_POST["inclusion"])) { $inclusion = $_POST["inclusion"]; } else { $inclusion = 0; }
            if (isset($_POST["period"]) && $_POST["period"] <> "") { $period = $_POST["period"]; } else { $period = null; }

            if ($id != null && $code != null && $label != null && $period != null)
            {
                if ($rate > 0)
                {
                    if ($days > 0)
                    {
                        if ($period_id = getPeriodID($conn, $period)) // verify the period exists; if it exists, store the period ID
                        {
                            if (verifyProject($conn, $code)) // verify the project exists
                            {
                                // validate benefits
                                if ($health != 1 && $health != 2) { $health = 0; }
                                if ($dental != 1 && $dental != 2) { $dental = 0; }
                                if ($wrs != 1) { $wrs = 0; }

                                // validate cost inclusion
                                if ($inclusion != 1) { $inclusion = 0; }

                                if (verifyUserProject($conn, $_SESSION["id"], $code)) // user has been verified to make changes to this project
                                {
                                    // edit the test project employee
                                    $editTestEmployee = mysqli_prepare($conn, "UPDATE project_employees_misc SET employee_label=?, project_days=?, yearly_rate=?, health_insurance=?, dental_insurance=?, wrs_eligible=?, costs_inclusion=? WHERE id=? AND period_id=?");
                                    mysqli_stmt_bind_param($editTestEmployee, "sidiiiiii", $label, $days, $rate, $health, $dental, $wrs, $inclusion, $id, $period_id);
                                    if (mysqli_stmt_execute($editTestEmployee)) 
                                    { 
                                        echo "<span class=\"log-success\">Successfully</span> edited the test employee.<br>"; 

                                        // edit the project last updated time
                                        updateProjectEditTimestamp($conn, $code);

                                        // update the autocalculated expenses
                                        recalculateAutomatedExpenses($conn, $code, $period_id);
                                    }
                                    else { echo "<span class=\"log-fail\">Failed</span> to edit the test employee. An unknown error has occurred! Please try again later.<br>"; }
                                }
                                else { echo "<span class=\"log-fail\">Failed</span> to make changes to the project. The user is not verified to make changes to this project.<br>"; }
                            }
                            else { echo "<span class=\"log-fail\">Failed</span> to edit the test employee to the project. The project you are trying to assign the test employee to does not exist!<br>"; }
                        }
                        else { echo "<span class=\"log-fail\">Failed</span> to edit the test employee to the project. An unknown error has occurred. Please try again later.<br>"; }                
                    }
                    else { echo "<span class=\"log-fail\">Failed</span> to edit the test employee to the project. The days in the project must be a number greater than 0!<br>"; }
                }
                else { echo "<span class=\"log-fail\">Failed</span> to edit the test employee to the project. The test employee's yearly rate must be a number greater than 0!<br>"; }
            }
            else { echo "<span class=\"log-fail\">Failed</span> to edit the test employee to the project. Please fill out all of the required fields and try again!<br>"; } 
        }
        else { echo "<span class=\"log-fail\">Failed</span> to edit the test employee. Your account does not have permission to edit project budgets!<br>"; }
        
        // disconnect from the database
        mysqli_close($conn);
    }
?>