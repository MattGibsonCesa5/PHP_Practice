<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get additional required files
        include("../../includes/config.php");
        include("../../includes/functions.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "ADD_REVENUES"))
        {
            // get the parameters from POST
            if (isset($_POST["period"]) && $_POST["period"] <> "") { $period = $_POST["period"]; } else { $period = null; }
            if (isset($_POST["name"]) && $_POST["name"] <> "") { $name = $_POST["name"]; } else { $name = null; }
            if (isset($_POST["desc"]) && $_POST["desc"] <> "") { $desc = $_POST["desc"]; } else { $desc = null; }
            if (isset($_POST["date"]) && $_POST["date"] <> "") { $date = $_POST["date"]; } else { $date = null; }
            if (isset($_POST["revenue"]) && $_POST["revenue"] <> "") { $revenue = $_POST["revenue"]; } else { $revenue = 0; }
            if (isset($_POST["quantity"]) && $_POST["quantity"] <> "") { $quantity = $_POST["quantity"]; } else { $quantity = 0; }
            if (isset($_POST["fund"]) && $_POST["fund"] <> "") { $fund = $_POST["fund"]; } else { $fund = null; }
            if (isset($_POST["loc"]) && $_POST["loc"] <> "") { $loc = $_POST["loc"]; } else { $loc = null; }
            if (isset($_POST["src"]) && $_POST["src"] <> "") { $src = $_POST["src"]; } else { $src = null; }
            if (isset($_POST["func"]) && $_POST["func"] <> "") { $func = $_POST["func"]; } else { $func = null; }
            if (isset($_POST["proj"]) && $_POST["proj"] <> "") { $proj = $_POST["proj"]; } else { $proj = null; }

            // create the database dates
            $DB_date = date("Y-m-d", strtotime($date));

            if ($name != null && $date != null && $fund != null && $loc != null && $src != null && $func != null && $proj != null && $period != null)
            {
                if ($period_id = getPeriodID($conn, $period)) // verify the period exists
                {
                    if (is_numeric($revenue) && $revenue > 0) // verify the revenue is a number larger than 0
                    {
                        if (verifyProject($conn, $proj)) // verify the project exists 
                        {
                            $addRevenue = mysqli_prepare($conn, "INSERT INTO revenues (name, description, date, fund_code, location_code, source_code, function_code, project_code, total_cost, quantity, period_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                            mysqli_stmt_bind_param($addRevenue, "ssssssssddi", $name, $desc, $DB_date, $fund, $loc, $src, $func, $proj, $revenue, $quantity, $period_id);
                            if (mysqli_stmt_execute($addRevenue)) // successfully added revenue
                            { 
                                echo "<span class=\"log-success\">Successfully</span> added the revenue.<br>"; 

                                // log revenue add
                                $message = "Successfully added a revenue labeled $name into the project with code $proj. ";
                                $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                                mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                                mysqli_stmt_execute($log);
                            }
                            else { echo "<span class=\"log-fail\">Failed</span> to add the revenue. An unexpected error has occurred. Please try again later!<br>"; } // failed to add revenue
                        }
                        else { echo "<span class=\"log-fail\">Failed</span> to add the revenue. The project selected does not exist!<br>"; }
                    }
                    else { echo "<span class=\"log-fail\">Failed</span> to add the revenue. The revenue amount must be a number greater than $0.00!<br>"; } // failed to add revenue
                }
            }
            else { echo "<span class=\"log-fail\">Failed</span> to add the revenue. You must provide all of the required fields!<br>"; } // failed to add revenue
        }
        else { echo "<span class=\"log-fail\">Failed</span> to add the revenue. Your account does not have permission to add revenues!<br>"; }

        // disconnect from the database
        mysqli_close($conn);
    }
?>