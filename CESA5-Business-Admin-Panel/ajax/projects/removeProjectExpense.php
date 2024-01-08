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
            if (isset($_POST["period"]) && $_POST["period"] <> "") { $period = $_POST["period"]; } else { $period = null; }

            if ($code != null && $id != null && $period != null)
            {
                if ($period_id = getPeriodID($conn, $period)) // verify the period exists; if it exists, store the period ID
                {
                    if (verifyProject($conn, $code)) // verify the project exists
                    {
                        if (verifyUserProject($conn, $_SESSION["id"], $code)) // user has been verified to make changes to this project
                        {
                            // remove the expense from the project
                            $removeEmployee = mysqli_prepare($conn, "DELETE FROM project_expenses WHERE id=? AND project_code=? AND period_id=?");
                            mysqli_stmt_bind_param($removeEmployee, "isi", $id, $code, $period_id);
                            if (mysqli_stmt_execute($removeEmployee)) 
                            { 
                                // log project expense removal
                                echo "<span class=\"log-success\">Successfully</span> removed the expense from the project.<br>"; 
                                $message = "Successfully removed an expense with ID $id from the project $code for the period $period.";
                                $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                                mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                                mysqli_stmt_execute($log);

                                // update the autocalculated expenses
                                recalculateAutomatedExpenses($conn, $code, $period_id);
                            }
                            else { echo "<span class=\"log-fail\">Failed</span> to remove the expense from the project.<br>"; }
                        }
                        else { echo "<span class=\"log-fail\">Failed</span> to make changes to the project. The user is not verified to make changes to this project.<br>"; }
                    }
                    else { echo "<span class=\"log-fail\">Failed</span> to make changes to the project. The project selected does not exist!<br>"; }
                }
                else { echo "<span class=\"log-fail\">Failed</span> to make changes to the project. The period selected does not exist!<br>"; }
            }
            else { echo "<span class=\"log-fail\">Failed</span> to remove the expense from the project. An unexpected error has occurred! Please try again later.<br>"; }
        }
        else { echo "<span class=\"log-fail\">Failed</span> to remove the expense from the project. Your account does not have permission to edit project budgets.<br>"; }
        
        // disconnect from the database
        mysqli_close($conn);
    }
?>