<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        if (isset($_SESSION["role"]) && $_SESSION["role"] == 1)
        {
            // include additional required files
            include("../../includes/config.php");
            include("../../includes/functions.php");

            if (isset($_POST["setting"]) && $_POST["setting"] <> "") { $setting = $_POST["setting"]; } else { $setting = null; }
            if (isset($_POST["value"]) && $_POST["value"] <> "") { $value = $_POST["value"]; } else { $value = null; }

            if ($setting != null)
            {
                // connect to the database
                $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

                // Maintenance Mode
                if ($setting == "MM_Button")
                {
                    if ($value == 1 || $value == 0)
                    {
                        $updateSetting = mysqli_prepare($conn, "UPDATE settings SET maintenance_mode=? WHERE id=1");
                        mysqli_stmt_bind_param($updateSetting, "i", $value);
                        if (mysqli_stmt_execute($updateSetting)) { echo 1; } else { echo 0; }

                        if ($value == 1)
                        {
                            $message = "Maintenance mode enabled.";
                            $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                            mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                            mysqli_stmt_execute($log);
                        }
                        else if ($value == 0)
                        {
                            $message = "Maintenance mode disabled.";
                            $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                            mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                            mysqli_stmt_execute($log);
                        }
                    }
                    else { echo 0; }
                }
                // FTE Days
                else if ($setting == "FTE_days")
                {
                    echo "Attempting to update FTE days.";
                    if (is_numeric($value)) // only update if value is a number
                    {
                        if ($value > 0) // value must be a number greater than 0
                        {
                            $updateSetting = mysqli_prepare($conn, "UPDATE settings SET FTE_days=? WHERE id=1");
                            mysqli_stmt_bind_param($updateSetting, "i", $value);
                            if (mysqli_stmt_execute($updateSetting)) 
                            { 
                                echo 1; 
                                $message = "FTE days set to $value.";
                                $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                                mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                                mysqli_stmt_execute($log);
                            } 
                            else { echo 0; }
                        }
                        else { echo 0; }
                    }
                    else { echo 0; }
                }
                // Hours Per Workday
                else if ($setting == "hours_per_workday")
                {
                    if (is_numeric($value)) // only update if value is a number
                    {
                        if ($value > 0) // value must be a number greater than 0
                        {
                            $updateSetting = mysqli_prepare($conn, "UPDATE settings SET hours_per_workday=? WHERE id=1");
                            mysqli_stmt_bind_param($updateSetting, "d", $value);
                            if (mysqli_stmt_execute($updateSetting)) 
                            { 
                                echo 1; 
                                $message = "Hours per workday set to $value.";
                                $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                                mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                                mysqli_stmt_execute($log);
                            } 
                            else { echo 0; }
                        }
                        else { echo 0; }
                    }
                    else { echo 0; }
                }
                // Overhead Costs Fund Code
                else if ($setting == "overhead_costs_fund")
                {
                    if (is_numeric($value) && ($value >= 10 && $value <= 99))
                    {
                        $updateSetting = mysqli_prepare($conn, "UPDATE settings SET overhead_costs_fund=? WHERE id=1");
                        mysqli_stmt_bind_param($updateSetting, "s", $value);
                        if (mysqli_stmt_execute($updateSetting)) 
                        { 
                            echo 1; 
                            $message = "Overhead costs fund code set to $value.";
                            $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                            mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                            mysqli_stmt_execute($log);
                        } 
                        else { echo 0; }
                    }
                    else { echo 0; }
                }
                // Inactivity Timeout
                else if ($setting == "inactivity_timeout")
                {
                    if ($value == 15 || $value == 30 || $value == 60 || $value == -1)
                    {
                        $updateSetting = mysqli_prepare($conn, "UPDATE settings SET inactivity_timeout=? WHERE id=1");
                        mysqli_stmt_bind_param($updateSetting, "i", $value);
                        if (mysqli_stmt_execute($updateSetting)) 
                        { 
                            echo 1; 
                            $message = "Inactivitiy timeout set to $value.";
                            $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                            mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                            mysqli_stmt_execute($log);
                        } 
                    }
                    else { echo 0; }
                }
                // Grant Indirect Rate
                else if ($setting == "grant_indirect_rate")
                {
                    if (is_numeric($value))
                    {
                        $updateSetting = mysqli_prepare($conn, "UPDATE settings SET grant_indirect_rate=? WHERE id=1");
                        mysqli_stmt_bind_param($updateSetting, "d", $value);
                        if (mysqli_stmt_execute($updateSetting)) 
                        { 
                            echo 1; 
                            $message = "Grant indirect rate set to $value.";
                            $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                            mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                            mysqli_stmt_execute($log);
                        } 
                    }
                    else { echo 0; }
                }
                // Annual Service Contracts GID
                else if ($setting == "service_contracts_gid")
                {
                    $updateSetting = mysqli_prepare($conn, "UPDATE settings SET service_contracts_gid=? WHERE id=1");
                    mysqli_stmt_bind_param($updateSetting, "s", $value);
                    if (mysqli_stmt_execute($updateSetting)) 
                    { 
                        echo 1; 
                        $message = "Service contracts GID set to $value.";
                        $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                        mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                        mysqli_stmt_execute($log);
                    } 
                }
                // Quarterly Invoices GID
                else if ($setting == "quarterly_invoices_gid")
                {
                    $updateSetting = mysqli_prepare($conn, "UPDATE settings SET quarterly_invoices_gid=? WHERE id=1");
                    mysqli_stmt_bind_param($updateSetting, "s", $value);
                    if (mysqli_stmt_execute($updateSetting)) 
                    { 
                        echo 1; 
                        $message = "Quarterly invoices GID set to $value.";
                        $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                        mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                        mysqli_stmt_execute($log);
                    } 
                }
                // Caseloads Units Warning
                else if ($setting == "caseloads_units_warning")
                {
                    $updateSetting = mysqli_prepare($conn, "UPDATE settings SET caseloads_units_warning=? WHERE id=1");
                    mysqli_stmt_bind_param($updateSetting, "s", $value);
                    if (mysqli_stmt_execute($updateSetting)) 
                    { 
                        echo 1; 
                        $message = "Caseload units warning set to $value.";
                        $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                        mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                        mysqli_stmt_execute($log);
                    } 
                }
                
                // disconnect from the database
                mysqli_close($conn);
            } 
        }
    }
?>