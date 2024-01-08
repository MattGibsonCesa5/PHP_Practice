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
            if (isset($_POST["period"]) && $_POST["period"] <> "") { $period = $_POST["period"]; } else { $period = null; } 
            if (isset($_POST["expenses"]) && $_POST["expenses"] <> "") { $expenses = json_decode($_POST["expenses"]); } else { $expenses = null; }

            if ($code != null && $period != null && $expenses != null)
            {
                if ($period_id = getPeriodID($conn, $period)) // verify the period exists; if it exists, store the period ID
                {
                    if (verifyProject($conn, $code)) // verify the project exists
                    {
                        if (verifyUserProject($conn, $_SESSION["id"], $code)) // user has been verified to make changes to this project
                        {
                            // for each expense selected; attempt to add to the project
                            for ($e = 0; $e < count($expenses); $e++)
                            {
                                // store project employee data locally
                                $expense = $expenses[$e][0];
                                $cost = $expenses[$e][1];
                                $fund = $expenses[$e][2];
                                $func = $expenses[$e][3];
                                $desc = $expenses[$e][4];

                                // verify that the expense selected exists and is active
                                $checkExpense = mysqli_prepare($conn, "SELECT * FROM expenses WHERE id=? AND global=0");
                                mysqli_stmt_bind_param($checkExpense, "i", $expense);
                                if (mysqli_stmt_execute($checkExpense))
                                {
                                    $checkExpenseResult = mysqli_stmt_get_result($checkExpense);
                                    if (mysqli_num_rows($checkExpenseResult) > 0) // expense exists; continue process
                                    {
                                        if (isset($fund) && ($fund >= 10 && $fund <= 99))
                                        {
                                            if (isset($func) && is_numeric($func))
                                            {
                                                // verify that the cost is a number greater than or equal to 0
                                                if (is_numeric($cost) && $cost >= 0)
                                                {
                                                    $addExpense = mysqli_prepare($conn, "INSERT INTO project_expenses (project_code, expense_id, description, cost, fund_code, function_code, period_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
                                                    mysqli_stmt_bind_param($addExpense, "sisdssi", $code, $expense, $desc, $cost, $fund, $func, $period_id);
                                                    if (mysqli_stmt_execute($addExpense)) 
                                                    { 
                                                        // log project expense add
                                                        echo "<span class=\"log-success\">Successfully</span> added an expense to the project.<br>"; 
                                                        $message = "Successfully added an expense (ID: $expense) to project $code for period $period.";
                                                        $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                                                        mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                                                        mysqli_stmt_execute($log);

                                                        // edit the project last updated time
                                                        updateProjectEditTimestamp($conn, $code);

                                                        // update the autocalculated expenses
                                                        recalculateAutomatedExpenses($conn, $code, $period_id);
                                                    }
                                                    else { echo "<span class=\"log-fail\">Failed</span> to add an expense to the project. An unknown error has occurred. Please try again later.<br>"; }
                                                }
                                                else { echo "<span class=\"log-fail\">Failed</span> to add an expense to the project. The cost provided was not a number greater than or equal to $0.00!<br>"; }
                                            }
                                            else { echo "<span class=\"log-fail\">Failed</span> to an expense to the project. You must set the expense's function code to a number!<br>"; }
                                        }
                                        else { echo "<span class=\"log-fail\">Failed</span> to an expense to the project. The fund code must follow the WUFAR convention and be a number between 10 and 99!<br>"; }
                                    }
                                    else { echo "<span class=\"log-fail\">Failed</span> to add an expense to the project. The expense selected does not exist!<br>"; }
                                }
                                else { echo "<span class=\"log-fail\">Failed</span> to add an expense to the project. An unknown error has occurred. Please try again later.<br>"; }
                            }
                        }
                        else { echo "<span class=\"log-fail\">Failed</span> to make changes to the project. The user is not verified to make changes to this project.<br>"; }
                    }
                    else { echo "<span class=\"log-fail\">Failed</span> the add the expense to the project. The project selected was invalid. Please try again later.<br>"; }
                }
                else { echo "<span class=\"log-fail\">Failed</span> to add the expense to the project. The period selected was invalid. Please try again later.<br>"; }
            }
            else { echo "<span class=\"log-fail\">Failed</span> to add the expense to the project. You must select an expense to add!<br>"; }
        }
        else { echo "<span class=\"log-fail\">Failed</span> to add the expense to the project. Your account does not have permssion to edit a project's budget!<br>"; }
        
        // disconnect from the database
        mysqli_close($conn);
    }
?>