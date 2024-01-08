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
            if (isset($_POST["code"]) && $_POST["code"] <> "") { $code = $_POST["code"]; } else { $code = null; }
            if (isset($_POST["id"]) && $_POST["id"] <> "") { $id = $_POST["id"]; } else { $id = null; }
            if (isset($_POST["days"]) && $_POST["days"] <> "") { $days = $_POST["days"]; } else { $days = 0; }
            if (isset($_POST["fund"]) && $_POST["fund"] <> "") { $fund = $_POST["fund"]; } else { $fund = null; }
            if (isset($_POST["loc"]) && $_POST["loc"] <> "") { $loc = $_POST["loc"]; } else { $loc = null; }
            if (isset($_POST["obj"]) && $_POST["obj"] <> "") { $obj = $_POST["obj"]; } else { $obj = null; }
            if (isset($_POST["func"]) && $_POST["func"] <> "") { $func = $_POST["func"]; } else { $func = null; }
            if (isset($_POST["period"]) && $_POST["period"] <> "") { $period = $_POST["period"]; } else { $period = null; }
            if (isset($_POST["location_id"]) && $_POST["location_id"] <> "") { $location_id = $_POST["location_id"]; } else { $location_id = null; }
            if (isset($_POST["record"]) && $_POST["record"] <> "") { $record = $_POST["record"]; } else { $record = null; }

            if ($period != null && $period_id = getPeriodID($conn, $period)) // verify the period exists; if it exists, store the period ID
            {
                if ($code != null && verifyProject($conn, $code)) // verify the project exists
                {
                    if (verifyUserProject($conn, $_SESSION["id"], $code)) // user has been verified to make changes to this project
                    {
                        if (isset($fund) && ($fund >= 10 && $fund <= 99))
                        {
                            if (isset($loc) && ($loc >= 100 && $loc <= 999))
                            {
                                if (isset($obj) && ($obj >= 100 && $obj <= 999))
                                {
                                    if (isset($func) && is_numeric($func))
                                    {
                                        // verify that the employee selected exists and is active
                                        $checkEmployee = mysqli_prepare($conn, "SELECT * FROM employees WHERE id=?");
                                        mysqli_stmt_bind_param($checkEmployee, "i", $id);
                                        if (mysqli_stmt_execute($checkEmployee))
                                        {
                                            $checkEmployeeResult = mysqli_stmt_get_result($checkEmployee);
                                            if (mysqli_num_rows($checkEmployeeResult) > 0) // employee exists; continue process
                                            {
                                                $employeeDetails = mysqli_fetch_array($checkEmployeeResult);
                                                $fname = $employeeDetails["fname"];
                                                $lname = $employeeDetails["lname"];
                                                $name = $lname . ", " . $fname;

                                                // verify the new project days is a number
                                                if (is_numeric($days))
                                                {
                                                    // update the project days of the employee
                                                    $updateProjectDays = mysqli_prepare($conn, "UPDATE project_employees SET project_days=?, fund_code=?, location_code=?, object_code=?, function_code=?, location_id=? WHERE id=?");
                                                    mysqli_stmt_bind_param($updateProjectDays, "iiissii", $days, $fund, $loc, $obj, $func, $location_id, $record);
                                                    if (mysqli_stmt_execute($updateProjectDays)) 
                                                    { 
                                                        // log project employee edit
                                                        echo "<span class=\"log-success\">Successfully</span> updated project details for $name.<br>";
                                                        $message = "Successfully edited $name (ID: $id) in the project $code for period $period.";
                                                        $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                                                        mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                                                        mysqli_stmt_execute($log);

                                                        // edit the project last updated time
                                                        updateProjectEditTimestamp($conn, $code);

                                                        // update the autocalculated expenses
                                                        recalculateAutomatedExpenses($conn, $code, $period_id); 
                                                    }
                                                    else { echo "<span class=\"log-fail\">Failed</span> to update project details for $name.<br>"; }
                                                }
                                                else { echo "<span class=\"log-fail\">Failed</span> to update the days in project for $name. The number of days in the project must be a number!<br>"; }
                                            }
                                            else { echo "<span class=\"log-fail\">Failed</span> to edit the employee. The employee selected does not exist!<br>"; }
                                        }
                                        else { echo "<span class=\"log-fail\">Failed</span> to edit the employee. An unknown error has occurred. Please try again later.<br>"; }
                                    }
                                    else { echo "<span class=\"log-fail\">Failed</span> to edit the employee in the project. You must set the employee's function code to a number!<br>"; }
                                }
                                else { echo "<span class=\"log-fail\">Failed</span> to edit the employee in the project. The object code must follow the WUFAR convention and be a number between 100 and 999!<br>"; }
                            }
                            else { echo "<span class=\"log-fail\">Failed</span> to edit the employee in the project. The location code must follow the WUFAR convention and be a number between 100 and 999!<br>"; }
                        }
                        else { echo "<span class=\"log-fail\">Failed</span> to edit the employee in the project. The fund code must follow the WUFAR convention and be a number between 10 and 99!<br>"; }
                    }
                    else { echo "<span class=\"log-fail\">Failed</span> to make changes to the project. The user is not verified to make changes to this project.<br>"; }
                }
                else { echo "<span class=\"log-fail\">Failed</span> to edit the employee in the project. The project you are trying to make changes to does not exist!<br>"; }
            }
            else { echo "<span class=\"log-fail\">Failed</span> to edit the employee in the project. The period you are trying to make changes in does not exist!<br>"; }
        }
        else { echo "<span class=\"log-fail\">Failed</span> to edit the project employee. Your account does not have permission to edit project budgets!<br>"; }
        
        // disconnect from the database
        mysqli_close($conn);
    }
?>