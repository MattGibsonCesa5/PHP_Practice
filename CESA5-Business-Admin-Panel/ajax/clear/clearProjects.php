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
            if (isset($_POST["clearEmps"]) && $_POST["clearEmps"] <> "") { $clearEmps = $_POST["clearEmps"]; } else { $clearEmps = 0; }
            if (isset($_POST["clearExps"]) && $_POST["clearExps"] <> "") { $clearExps = $_POST["clearExps"]; } else { $clearExps = 0; }
            if (isset($_POST["clearRevs"]) && $_POST["clearRevs"] <> "") { $clearRevs = $_POST["clearRevs"]; } else { $clearRevs = 0; }
            if (isset($_POST["period"]) && $_POST["period"] <> "") { $period = $_POST["period"]; } else { $period = 0; }

            // connect to the database
            $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);
            
            if ($clearAll == 1 || $clearEmps == 1 || $clearExps == 1 || $clearRevs == 1)
            {
                if ($clearAll == 1) // deleting all projects and data; skip period selection
                {
                    // delete all projects, project employees, project expenses, and invoices
                    if (mysqli_query($conn, "TRUNCATE `projects`")) // successfully deleted all projects; delete other associated data
                    {
                        echo "<span class=\"log-success\">Successfully</span> deleted all projects. Beginning to delete associated data.<br>";

                        if (mysqli_query($conn, "TRUNCATE `project_employees`")) { echo "<span class=\"log-success\">Successfully</span> delete all project employees for all periods.<br>"; } else { echo "<span class=\"log-fail\">Failed</span> to delete all project employees for all periods.<br>"; }
                        if (mysqli_query($conn, "TRUNCATE `project_expenses`")) { echo "<span class=\"log-success\">Successfully</span> delete all project expenses for all periods.<br>"; } else { echo "<span class=\"log-fail\">Failed</span> to delete all project expenses for all periods.<br>"; }
                        if (mysqli_query($conn, "TRUNCATE `services_provided`")) { echo "<span class=\"log-success\">Successfully</span> deleted all invoices for all periods.<br>"; } else { echo "<span class=\"log-fail\">Failed</span> to delete all invoices for all periods.<br>"; }
                        if (!mysqli_query($conn, "TRUNCATE `quarterly_costs`")) { echo "<span class=\"log-fail\">Failed</span> to delete all quarterly costs for all periods.<br>"; }
                    }
                    else { echo "<span class=\"log-fail\">Failed</span> to delete all projects.<br>"; }
                }
                else // clearing only certain project elements; period must be selected
                {
                    if ($period != null && $period != -1) // specific period selected (or all periods)
                    {
                        if ($period == -2) // "All Periods" option is selected
                        {
                            // clear project employees selected
                            if ($clearEmps == 1)
                            {
                                if (mysqli_query($conn, "TRUNCATE `project_employees`")) { echo "<span class=\"log-success\">Successfully</span> delete all project employees for all periods.<br>"; } 
                                else { echo "<span class=\"log-fail\">Failed</span> to delete all project employees for all periods.<br>"; }
                            }

                            // clear project expenses selected
                            if ($clearExps == 1)
                            {
                                if (mysqli_query($conn, "TRUNCATE `project_expenses`")) { echo "<span class=\"log-success\">Successfully</span> delete all project expenses for all periods.<br>"; } else { echo "<span class=\"log-fail\">Failed</span> to delete all project expenses for all periods.<br>"; }
                            }
                            
                            // clear project revenues (invoices) selected
                            if ($clearRevs == 1)
                            {
                                if (mysqli_query($conn, "TRUNCATE `services_provided`")) 
                                { 
                                    echo "<span class=\"log-success\">Successfully</span> deleted all invoices for all periods.<br>"; 
                            
                                    // delete quarterly costs
                                    if (!mysqli_query($conn, "TRUNCATE `quarterly_costs`")) { echo "<span class=\"log-fail\">Failed</span> to delete all quarterly costs for all periods.<br>"; }
                                } 
                                else { echo "<span class=\"log-fail\">Failed</span> to delete all invoices for all periods.<br>"; }
                                
                            }
                        }
                        else // specific period selected
                        {
                            // verify the period exists
                            $checkPeriod = mysqli_prepare($conn, "SELECT id FROM periods WHERE id=?");
                            mysqli_stmt_bind_param($checkPeriod, "i", $period);
                            if (mysqli_stmt_execute($checkPeriod))
                            {
                                $checkPeriodResult = mysqli_stmt_get_result($checkPeriod);
                                if (mysqli_num_rows($checkPeriodResult) > 0) // period exists; continue
                                {
                                    // clear project employees selected
                                    if ($clearEmps == 1)
                                    {
                                        $clearEmpsQuery = mysqli_prepare($conn, "DELETE FROM project_employees WHERE period_id=?");
                                        mysqli_stmt_bind_param($clearEmpsQuery, "i", $period);
                                        if (mysqli_stmt_execute($clearEmpsQuery)) { echo "<span class=\"log-success\">Successfully</span> cleared all project employess for the selected period.<br>"; } else { echo "<span class=\"log-fail\">Failed</span> to clear all project employees for the selected period.<br>"; }
                                    }

                                    // clear project expenses selected
                                    if ($clearExps == 1)
                                    {
                                        $clearExpsQuery = mysqli_prepare($conn, "DELETE FROM project_expenses WHERE period_id=?");
                                        mysqli_stmt_bind_param($clearExpsQuery, "i", $period);
                                        if (mysqli_stmt_execute($clearExpsQuery)) { echo "<span class=\"log-success\">Successfully</span> cleared all project expenses for the selected period.<br>"; } else { echo "<span class=\"log-fail\">Failed</span> to clear all project expenses for the selected period.<br>"; }
                                    }

                                    // clear project revenues (invoices) selected
                                    if ($clearRevs == 1)
                                    {
                                        $clearInvoicesQuery = mysqli_prepare($conn, "DELETE FROM services_provided WHERE period_id=?");
                                        mysqli_stmt_bind_param($clearInvoicesQuery, "i", $period);
                                        if (mysqli_stmt_execute($clearInvoicesQuery)) 
                                        { 
                                            echo "<span class=\"log-success\">Successfully</span> cleared all project expenses for the selected period.<br>"; 

                                            $clearQuarterlyCostsQuery = mysqli_prepare($conn, "DELETE FROM quarterly_costs WHERE period_id=?");
                                            mysqli_stmt_bind_param($clearQuarterlyCostsQuery, "i", $period);
                                            if (!mysqli_stmt_execute($clearQuarterlyCostsQuery)) { echo "<span class=\"log-fail\">Failed</span> to clear all quarterly costs for the selected period.<br>"; }
                                        } 
                                        else { echo "<span class=\"log-fail\">Failed</span> to clear all project revenues (invoices) for the selected period.<br>"; }
                                    }
                                }
                                else { echo "<span class=\"log-fail\">Failed</span> to clear out projects. The period selected does not exist!<br>"; }
                            }
                            else { echo "<span class=\"log-fail\">Failed</span> to clear out projects. An unexpected error has occurred. Please try again later.<br>"; }
                        }
                    }
                    else { echo "<span class=\"log-fail\">Failed</span> to clear out projects. You must select a period to continue!<br>"; }
                }
            }
            else { echo "<span class=\"log-fail\">Failed</span> to clear out projects. No options were selected.<br>"; }

            // log clear
            $message = "Cleared projects with options [$clearAll, $clearEmps, $clearExps, $clearRevs] => [Delete All, Clear Employees, Clear Expenses, Clear Revenus]. ";
            $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
            mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
            mysqli_stmt_execute($log);

            // disconnect from the database
            mysqli_close($conn);
        }
    }
?>