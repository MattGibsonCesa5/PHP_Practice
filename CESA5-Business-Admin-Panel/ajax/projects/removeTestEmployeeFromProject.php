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
                            // attempt to remove the test employee from the project
                            $removeTestEmployee = mysqli_prepare($conn, "DELETE FROM project_employees_misc WHERE id=? AND project_code=? AND period_id=?");
                            mysqli_stmt_bind_param($removeTestEmployee, "isi", $id, $code, $period_id);
                            if (mysqli_stmt_execute($removeTestEmployee)) 
                            { 
                                echo "<span class=\"log-success\">Successfully</span> removed the test employee from the project.<br>";
                                
                                // edit the project last updated time
                                updateProjectEditTimestamp($conn, $code);

                                // update the autocalculated expenses
                                recalculateAutomatedExpenses($conn, $code, $period_id);
                            }
                            else { echo "<span class=\"log-fail\">Failed</span> to remove the test employee from the project. An unexpected error has occurred! Please try again later.<br>"; }
                        }
                        else { echo "<span class=\"log-fail\">Failed</span> to make changes to the project. The user is not verified to make changes to this project.<br>"; }
                    }
                    else { echo "<span class=\"log-fail\">Failed</span> to remove the test employee from the project. The project you are trying to assign the test employee to does not exist!<br>"; }
                }
                else { echo "<span class=\"log-fail\">Failed</span> to remove the test employee from the project. An unknown error has occurred. Please try again later.<br>"; }                
            }
            else { echo "<span class=\"log-fail\">Failed</span> to remove the test employee from the project. An unknown error has occurred. Please try again later.<br>"; }
        }
        else { echo "<span class=\"log-fail\">Failed</span> to remove the test employee from the project. Your account does not have permission to edit project budgets!<br>"; }
        
        // disconnect from the database
        mysqli_close($conn);
    }
?>