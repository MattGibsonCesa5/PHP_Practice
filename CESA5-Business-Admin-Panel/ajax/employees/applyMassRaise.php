<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        if (isset($_SESSION["role"]) && $_SESSION["role"] == 1)
        {
            // get additional required files
            include("../../includes/functions.php");
            include("../../includes/config.php");

            // initialize variable to store how many employees successfully got a raise or failed to get a raise
            $successes = $errors = 0; // initialize to 0

            // get selected period from POST
            if (isset($_POST["base_period"]) && $_POST["base_period"] <> "") { $base_period = $_POST["base_period"]; } else { $base_period = null; }
            if (isset($_POST["raise_period"]) && $_POST["raise_period"] <> "") { $raise_period = $_POST["raise_period"]; } else { $raise_period = null; }
            if (isset($_POST["raise_rate"]) && is_numeric($_POST["raise_rate"])) { $raise_rate = $_POST["raise_rate"]; } else { $raise_rate = null; }

            // if the period is set, continue
            if ($base_period != null && $raise_period != null && $raise_rate != null)
            {
                // connect to the database
                $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

                // verify the base period exists
                if (verifyPeriod($conn, $base_period))
                {
                    // verify the raise period exists
                    if (verifyPeriod($conn, $raise_period))
                    {
                        // get the period label
                        $period_details = getPeriodDetails($conn, $raise_period);
                        $period_label = $period_details["name"];

                        // get a list of all active employees
                        $getEmployees = mysqli_prepare($conn, "SELECT e.id, ec.yearly_rate FROM employees e JOIN employee_compensation ec ON e.id=ec.employee_id WHERE ec.active=1 AND ec.period_id=?");
                        mysqli_stmt_bind_param($getEmployees, "i", $base_period); 
                        if (mysqli_stmt_execute($getEmployees))
                        {
                            $getEmployeesResults = mysqli_stmt_get_result($getEmployees);
                            if (mysqli_num_rows($getEmployeesResults) > 0)
                            {
                                while ($employee = mysqli_fetch_array($getEmployeesResults))
                                {
                                    // store employee details locally
                                    $employee_id = $employee["id"];
                                    $base_salary = $employee["yearly_rate"];

                                    // get the emplyoee's display name
                                    $display_name = getEmployeeDisplayName($conn, $employee_id);

                                    // calculate the employee's new yearly rate
                                    $raise_salary = round($base_salary * (1 + ($raise_rate / 100)), 2);

                                    // check to see if the employee compensation is set for the raise period
                                    $checkComp = mysqli_prepare($conn, "SELECT id FROM employee_compensation WHERE employee_id=? AND period_id=?");
                                    mysqli_stmt_bind_param($checkComp, "ii", $employee_id, $raise_period);
                                    if (mysqli_stmt_execute($checkComp))
                                    {
                                        $checkCompResult = mysqli_stmt_get_result($checkComp);
                                        if (mysqli_num_rows($checkCompResult) > 0) // compensation for raise period exists; update existing compensation
                                        {
                                            $updateComp = mysqli_prepare($conn, "UPDATE employee_compensation SET yearly_rate=? WHERE employee_id=? AND period_id=?");
                                            mysqli_stmt_bind_param($updateComp, "dii", $raise_salary, $employee_id, $raise_period);
                                            if (mysqli_stmt_execute($updateComp)) { $successes++; }
                                            else 
                                            { 
                                                $errors++;
                                                echo "<span class=\"log-fail\">Failed</span> to give $display_name a raise of $raise_rate% for the period $period_label. An unexpected error has occurred.<br>"; 
                                            }
                                        }
                                        else // compensation for raise period does not exist; insert new entry
                                        {
                                            $copyComp = mysqli_prepare($conn, "INSERT INTO employee_compensation (employee_id, yearly_rate, contract_days, contract_type, health_insurance, dental_insurance, wrs_eligible, assignment_position, sub_assignment, experience, highest_degree, period_id) SELECT employee_id, yearly_rate, contract_days, contract_type, health_insurance, dental_insurance, wrs_eligible, assignment_position, sub_assignment, experience, highest_degree, ? FROM employee_compensation WHERE period_id=?");
                                            mysqli_stmt_bind_param($copyEmployeeCompensation, "ii", $raise_period, $base_period);
                                            if (mysqli_stmt_execute($copyEmployeeCompensation))
                                            {
                                                $updateComp = mysqli_prepare($conn, "UPDATE employee_compensation SET yearly_rate=? WHERE employee_id=? AND period_id=?");
                                                mysqli_stmt_bind_param($updateComp, "dii", $raise_salary, $employee_id, $raise_period);
                                                if (mysqli_stmt_execute($updateComp)) { $successes++; }
                                                else 
                                                { 
                                                    $errors++;
                                                    echo "<span class=\"log-fail\">Failed</span> to give $display_name a raise of $raise_rate% for the period $period_label. An unexpected error has occurred.<br>"; 
                                                }
                                            }
                                            else 
                                            { 
                                                $errors++;
                                                echo "<span class=\"log-fail\">Failed</span> to give $display_name a raise of $raise_rate% for the period $period_label. An unexpected error has occurred.<br>"; 
                                            }
                                        }
                                    }
                                    else 
                                    { 
                                        $errors++;
                                        echo "<span class=\"log-fail\">Failed</span> to give $display_name a raise of $raise_rate% for the period $period_label. An unexpected error has occurred.<br>"; 
                                    }
                                }
                            }
                        }

                        // log and display status
                        $message = "Successfully gave $successes employees raises of $raise_rate% for the $period_label period.<br>";
                        if ($errors > 0) { $message .= "Failed to create $errors service contracts for $period_name.<br>"; }
                        $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                        mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                        mysqli_stmt_execute($log);
                        echo $message;
                    }
                }

                // disconnect from the database
                mysqli_close($conn);
            }
        }
    }
?>