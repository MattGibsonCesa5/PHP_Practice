<?php
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get the required files
        include("../../includes/config.php");
        include("../../includes/functions.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if ((checkUserPermission($conn, "BUDGET_PROJECTS_ALL") || checkUserPermission($conn, "BUDGET_PROJECTS_ASSIGNED")) && checkUserPermission($conn, "ADD_REVENUES"))
        {
            // get parameters from POST
            if (isset($_POST["proj"]) && $_POST["proj"] <> "") { $proj = $_POST["proj"]; } else { $proj = null; }
            if (isset($_POST["fund"]) && $_POST["fund"] <> "") { $fund = $_POST["fund"]; } else { $fund = null; }
            if (isset($_POST["loc"]) && $_POST["loc"] <> "") { $loc = $_POST["loc"]; } else { $loc = null; }
            if (isset($_POST["src"]) && $_POST["src"] <> "") { $src = $_POST["src"]; } else { $src = null; }
            if (isset($_POST["func"]) && $_POST["func"] <> "") { $func = $_POST["func"]; } else { $func = null; }
            if (isset($_POST["name"]) && $_POST["name"] <> "") { $name = $_POST["name"]; } else { $name = null; }
            if (isset($_POST["desc"]) && $_POST["desc"] <> "") { $desc = $_POST["desc"]; } else { $desc = null; }
            if (isset($_POST["date"]) && $_POST["date"] <> "") { $date = date("Y-m-d", strtotime($_POST["date"])); } else { $date = null; }
            if (isset($_POST["cost"]) && $_POST["cost"] <> "") { $cost = $_POST["cost"]; } else { $cost = null; }
            if (isset($_POST["quantity"]) && is_numeric($_POST["quantity"])) { $quantity = $_POST["quantity"]; } else { $quantity = 0; }
            if (isset($_POST["period"]) && $_POST["period"] <> "") { $period = $_POST["period"]; } else { $period = null; }

            if ($proj != null && $fund != null && $loc != null && $src != null && $func != null && $period != null)
            {
                if ($name != null && $date != null && $cost != null)
                {
                    if ($period_id = getPeriodID($conn, $period)) // verify the period exists; if it exists, store the period ID
                    {
                        if (verifyProject($conn, $proj)) // verify the project exists
                        {
                            if (verifyUserProject($conn, $_SESSION["id"], $proj)) // user has been verified to make changes to this project
                            {
                                // check to see if the total cost is a number and greater than 0
                                if (is_numeric($cost) && $cost > 0)
                                {
                                    // attempt to add the revenue
                                    $addRevenue = mysqli_prepare($conn, "INSERT INTO revenues (name, description, date, fund_code, location_code, source_code, function_code, project_code, total_cost, period_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                                    mysqli_stmt_bind_param($addRevenue, "ssssssssdi", $name, $desc, $date, $fund, $loc, $src, $func, $proj, $cost, $period_id);
                                    if (mysqli_stmt_execute($addRevenue)) 
                                    { 
                                        // log project revenue add
                                        echo "<span class=\"log-success\">Successfully</span> added the revenue to the project."; 
                                        $message = "Successfully added a revenue named $name to project $proj for amount $$cost for period $period.";
                                        $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                                        mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                                        mysqli_stmt_execute($log);
                                    }
                                    else { echo "<span class=\"log-fail\">Failed</span> to add the revenue to the project. An unexpected error has occurred. Please try again later!"; }
                                }
                                else { echo "<span class=\"log-fail\">Failed</span> to add the revenue to the project. The cost must be a number greater than $0.00!"; }
                            }
                            else { echo "<span class=\"log-fail\">Failed</span> to make changes to the project. The user is not verified to make changes to this project. "; }
                        }
                        else { echo "<span class=\"log-fail\">Failed</span> to add the revenue to the project. The project code selected ($proj) does not correspond to an existing project!"; }
                    }
                    else { echo "<span class=\"log-fail\">Failed</span> to add the revenue to the project. An unknown error has occurred. Please try again later!"; }
                }
                else { echo "<span class=\"log-fail\">Failed</span> to add the revenue to the project. You must provide a revenue name, date, and total revenue amount."; }
            }
            else { echo "<span class=\"log-fail\">Failed</span> to add the revenue to the project. You must provide all the required WUFAR codes."; }
        }
        else { echo "<span class=\"log-fail\">Failed</span> to add the revenue to the project. Your account does not have permission to either add revenues or edit project budgets!<br>"; }
        
        // disconnect from the database
        mysqli_close($conn);
    }
?>