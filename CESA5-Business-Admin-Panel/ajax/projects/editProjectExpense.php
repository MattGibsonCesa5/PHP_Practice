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
            if (isset($_POST["cost"]) && $_POST["cost"] <> "") { $cost = $_POST["cost"]; } else { $cost = null; }
            if (isset($_POST["fund"]) && $_POST["fund"] <> "") { $fund = $_POST["fund"]; } else { $fund = null; }
            if (isset($_POST["func"]) && $_POST["func"] <> "") { $func = $_POST["func"]; } else { $func = null; }
            if (isset($_POST["desc"]) && $_POST["desc"] <> "") { $desc = $_POST["desc"]; } else { $desc = null; }
            if (isset($_POST["period"]) && $_POST["period"] <> "") { $period = $_POST["period"]; } else { $period = null; }

            if ($code != null && $id != null && $period != null)
            {
                if ($period_id = getPeriodID($conn, $period)) // verify the period exists; if it exists, store the period ID
                {
                    if (verifyProject($conn, $code)) // verify the project exists
                    {
                        if (verifyUserProject($conn, $_SESSION["id"], $code)) // user has been verified to make changes to this project
                        {
                            if (isset($fund) && ($fund >= 10 && $fund <= 99))
                            {
                                if (isset($func) && is_numeric($func))
                                {
                                    if (is_numeric($cost) && $cost >= 0) // if cost is a number greater than or equal to 0; continue
                                    {
                                        // get the current time in UTC
                                        date_default_timezone_set("UTC");
                                        $update_time = date("Y-m-d H:i:s");

                                        // check to see if the project expense is autocalculated
                                        if (isExpenseAutocaclulated($conn, $id)) // expense is autocalculated; only update codes
                                        {
                                            $updateProjectExpense = mysqli_prepare($conn, "UPDATE project_expenses SET fund_code=?, function_code=?, timestamp=? WHERE id=? AND project_code=? AND period_id=?");
                                            mysqli_stmt_bind_param($updateProjectExpense, "sssisi", $fund, $func, $update_time, $id, $code, $period_id);
                                            if (mysqli_stmt_execute($updateProjectExpense)) 
                                            { 
                                                // log expense edit
                                                echo "<span class=\"log-success\">Successfully</span> updated the project expense.<br>"; 
                                                $message = "Successfully edited the AUTOMATED expense with ID $id in the project $code for period $period.";
                                                $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                                                mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                                                mysqli_stmt_execute($log);

                                                // update the autocalculated expenses
                                                recalculateAutomatedExpenses($conn, $code, $period_id);
                                            }
                                            else { echo "<span class=\"log-fail\">Failed</span> to update the project expense.<br>"; }
                                        }
                                        else // expense is not autocalculated; update all
                                        {
                                            $updateProjectExpense = mysqli_prepare($conn, "UPDATE project_expenses SET description=?, cost=?, fund_code=?, function_code=?, timestamp=? WHERE id=? AND project_code=? AND period_id=?");
                                            mysqli_stmt_bind_param($updateProjectExpense, "sdsssisi", $desc, $cost, $fund, $func, $update_time, $id, $code, $period_id);
                                            if (mysqli_stmt_execute($updateProjectExpense)) 
                                            { 
                                                // log expense edit
                                                echo "<span class=\"log-success\">Successfully</span> updated the project expense.<br>"; 
                                                $message = "Successfully edited the expense with ID $id in the project $code for period $period.";
                                                $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                                                mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                                                mysqli_stmt_execute($log);

                                                // update the autocalculated expenses
                                                recalculateAutomatedExpenses($conn, $code, $period_id);
                                            }
                                            else { echo "<span class=\"log-fail\">Failed</span> to update the project expense.<br>"; }
                                        }
                                    }
                                    else { echo "<span class=\"log-fail\">Failed</span> to update the project expense. The cost must be a number greater than or equal to $0.00!<br>"; }
                                }
                                else { echo "<span class=\"log-fail\">Failed</span> to edit the project expense. You must set the expense's function code to a number!<br>"; }
                            }
                            else { echo "<span class=\"log-fail\">Failed</span> to edit the project expense. The fund code must follow the WUFAR convention and be a number between 10 and 99!<br>"; }
                        }
                        else { echo "<span class=\"log-fail\">Failed</span> to make changes to the project. The user is not verified to make changes to this project.<br>"; }
                    }
                    else { echo "<span class=\"log-fail\">Failed</span> to update the project expense. The project selected was invalid. Please try again later.<br>"; }
                }
                else { echo "<span class=\"log-fail\">Failed</span> to update the project expense. The period selected was invalid. Please try again later.<br>"; }
            }
            else { echo "<span class=\"log-fail\">Failed</span> to edit the project expenses. Your must provide data for all of the required fields!<br>"; }
        }
        else { echo "<span class=\"log-fail\">Failed</span> to edit the project expense. Your account does not have permission to edit project budgets!<br>"; }
        
        // disconnect from the database
        mysqli_close($conn);
    }
?>