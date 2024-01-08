<?php
    session_start();
    
    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get additional required files
        include("../../includes/config.php");
        include("../../includes/functions.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "DELETE_PROJECT_EXPENSES"))
        {
            // get the expense ID from POST
            if (isset($_POST["expense_id"]) && $_POST["expense_id"] <> "") { $expense_id = $_POST["expense_id"]; } else { $expense_id = null; }

            if ($expense_id != null)
            {
                // verify that the expense exists
                $checkExpense = mysqli_prepare($conn, "SELECT id FROM expenses WHERE id=?");
                mysqli_stmt_bind_param($checkExpense, "i", $expense_id);
                if (mysqli_stmt_execute($checkExpense))
                {
                    $checkExpenseResult = mysqli_stmt_get_result($checkExpense);
                    if (mysqli_num_rows($checkExpenseResult) > 0) // expense exists; continue deletion
                    {
                        // delete the expense
                        $deleteExpense = mysqli_prepare($conn, "DELETE FROM expenses WHERE id=?");
                        mysqli_stmt_bind_param($deleteExpense, "i", $expense_id);
                        if (mysqli_stmt_execute($deleteExpense))
                        {
                            echo "<span class=\"log-success\">Successfully</span> deleted the expense.<br>";

                            // delete any active period expenses for this expense
                            $deleteActiveExpenses = mysqli_prepare($conn, "DELETE FROM project_expenses WHERE expense_id=? AND period_id=?");
                            mysqli_stmt_bind_param($deleteActiveExpenses, "ii", $expense_id, $GLOBAL_SETTINGS["active_period"]);
                            if (mysqli_stmt_execute($deleteActiveExpenses)) { echo "<span class=\"log-success\">Successfully</span> deleted all project expenses in the active period for this expense.<br>"; }
                            else { echo "<span class=\"log-fail\">Failed</span> to delete project expenses in the active period for this expense.<br>"; }

                            // log expense deletion
                            $message = "Successfully deleted the expense with the ID $expense_id. ";
                            $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                            mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                            mysqli_stmt_execute($log);
                        }
                        else { echo "<span class=\"log-fail\">Failed</span> to delete the expense. An unknown error has occurred. Please try again later.<br>"; }
                    }
                    else { echo "<span class=\"log-fail\">Failed</span> to delete the expense. The expense selected does not exist!<br>"; }
                }
                else { echo "<span class=\"log-fail\">Failed</span> to delete the expense. An unknown error has occurred. Please try again later.<br>"; }
            }
            else { echo "<span class=\"log-fail\">Failed</span> to delete the expense. The expense selected does not exist!<br>"; }
        }
        else { echo "<span class=\"log-fail\">Failed</span> to delete the expense. Your account does not have permission to delete expenses!<br>"; }
        
        // disconnect from the database
        mysqli_close($conn);
    }
?>