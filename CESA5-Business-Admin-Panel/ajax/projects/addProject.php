<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get additional required files
        include("../../includes/config.php");
        include("../../includes/functions.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        // verify the user has permission to perform this action
        if (checkUserPermission($conn, "ADD_PROJECTS"))
        {
            // get period name from POST
            if (isset($_POST["period"]) && $_POST["period"] <> "") { $period = $_POST["period"]; } else { $period = null; }

            // verify the period exists; if it exists, store the period ID
            if ($period != null && $period_id = getPeriodID($conn, $period))
            {
                // get parameters from POST
                if (isset($_POST["code"]) && $_POST["code"] <> "") { $code = $_POST["code"]; } else { $code = null; }
                if (isset($_POST["name"]) && $_POST["name"] <> "") { $name = $_POST["name"]; } else { $name = null; }
                if (isset($_POST["desc"]) && $_POST["desc"] <> "") { $desc = $_POST["desc"]; } else { $desc = null; }
                if (isset($_POST["dept"]) && $_POST["dept"] <> "") { $dept = $_POST["dept"]; } else { $dept = null; }
                if (isset($_POST["fund"]) && $_POST["fund"] <> "") { $fund = $_POST["fund"]; } else { $fund = null; }
                if (isset($_POST["func"]) && $_POST["func"] <> "") { $func = $_POST["func"]; } else { $func = null; }
                if (isset($_POST["supervision_costs"]) && $_POST["supervision_costs"] <> "") { $supervision_costs = $_POST["supervision_costs"]; } else { $supervision_costs = null; }
                if (isset($_POST["indirect_costs"]) && $_POST["indirect_costs"] <> "") { $indirect_costs = $_POST["indirect_costs"]; } else { $indirect_costs = null; }
                if (isset($_POST["calc_fte"]) && $_POST["calc_fte"] <> "") { $calc_fte = $_POST["calc_fte"]; } else { $calc_fte = 0; }
                if (isset($_POST["status"]) && $_POST["status"] <> "") { $status = $_POST["status"]; } else { $status = null; }
                if (isset($_POST["FTE_days"]) && is_numeric($_POST["FTE_days"]) && ($_POST["FTE_days"] >= 0 && $_POST["FTE_days"] <= 365)) { $FTE_days = $_POST["FTE_days"]; } else { $FTE_days = 250; }
                if (isset($_POST["leave_time"]) && is_numeric($_POST["leave_time"]) && ($_POST["leave_time"] >= 0 && $_POST["leave_time"] <= 365)) { $leave_time = $_POST["leave_time"]; } else { $leave_time = 0; }
                if (isset($_POST["prep_work"]) && is_numeric($_POST["prep_work"]) && ($_POST["prep_work"] >= 0 && $_POST["prep_work"] <= 365)) { $prep_work = $_POST["prep_work"]; } else { $prep_work = 0; }
                if (isset($_POST["personal_development"]) && is_numeric($_POST["personal_development"]) && ($_POST["personal_development"] >= 0 && $_POST["personal_development"] <= 365)) { $personal_development = $_POST["personal_development"]; } else { $personal_development = 0; }
                if (isset($_POST["location"]) && is_numeric($_POST["location"]) && ($_POST["location"] >= 0 && $_POST["location"] <= 2)) { $location = $_POST["location"]; } else { $location = 0; }

                // validate supervision costs :: if supervision costs is anything but 1 (yes); set to 0 (no)
                if (is_numeric($supervision_costs) && $supervision_costs != 1) { $supervision_costs = 0; }
                else if (!is_numeric($supervision_costs)) { $supervision_costs = 0; }

                // validate indirect costs :: if indirect costs is anything but 1 (Agency), 2 (Grant), 3 (DPI); set to 0 (none)
                if (is_numeric($indirect_costs) && $indirect_costs != 1 && $indirect_costs != 2 && $indirect_costs != 3) { $indirect_costs = 0; }
                else if (!is_numeric($indirect_costs)) { $indirect_costs = 0; }

                // validate calc FTE
                if (is_numeric($calc_fte) && $calc_fte != 1) { $calc_fte = 0; }
                else if (!is_numeric($calc_fte)) { $calc_fte = 0; }

                // validate status :: if status is anything but 1 (active); set to 0 (inactive)
                if (is_numeric($status) && $status != 1) { $status = 0; }
                else if (!is_numeric($status)) { $status = 0; }

                if ($code != null && $name != null && $fund != null && $func != null)
                {
                    if (is_numeric($code) && ($code >= 100 && $code <= 999))
                    {
                        if (is_numeric($fund) && ($fund >= 10 && $fund <= 99))
                        {
                            if (is_numeric($func) && ($func >= 100000 && $func <= 999999))
                            {
                                // verify that the project code is unique
                                $checkCode = mysqli_prepare($conn, "SELECT code FROM projects WHERE code=?");
                                mysqli_stmt_bind_param($checkCode, "s", $code);
                                if (mysqli_stmt_execute($checkCode))
                                {
                                    $checkCodeResult = mysqli_stmt_get_result($checkCode);
                                    if (mysqli_num_rows($checkCodeResult) == 0) // code is unique; continue project creation
                                    {
                                        // verify the department selected exists
                                        $verified_dept = false; // assume the department is not verified
                                        if ($dept != null && $dept != 0)
                                        {
                                            $checkDept = mysqli_prepare($conn, "SELECT id FROM departments WHERE id=?");
                                            mysqli_stmt_bind_param($checkDept, "i", $dept);
                                            if (mysqli_stmt_execute($checkDept))
                                            {
                                                $checkDeptResult = mysqli_stmt_get_result($checkDept);
                                                if (mysqli_num_rows($checkDeptResult) > 0) // department exists; continue project creation
                                                {
                                                    $verified_dept = true;
                                                }
                                            }
                                        }
                                        else { $verified_dept = true; }

                                        if ($verified_dept === true)
                                        {
                                            $addProject = mysqli_prepare($conn, "INSERT INTO projects (code, name, description, department_id, fund_code, function_code, supervision_costs, indirect_costs, calc_fte, FTE_days, leave_time, prep_work, personal_development, staff_location, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                                            mysqli_stmt_bind_param($addProject, "sssiiiiiiiiiiii", $code, $name, $desc, $dept, $fund, $func, $supervision_costs, $indirect_costs, $calc_fte, $FTE_days, $leave_time, $prep_work, $personal_development, $location, $_SESSION["id"]);
                                            if (mysqli_stmt_execute($addProject)) 
                                            { 
                                                echo "<span class=\"log-success\">Successfully</span> created the project.<br>"; 

                                                // set the project's status for all existing periods
                                                $getPeriods = mysqli_query($conn, "SELECT id FROM periods");
                                                if (mysqli_num_rows($getPeriods) > 0)
                                                {
                                                    // loop through all existiing periods
                                                    while ($period = mysqli_fetch_array($getPeriods))
                                                    {
                                                        // store period ID
                                                        $period_id = $period["id"];

                                                        // set the project's status for the looped period
                                                        $setStatus = mysqli_prepare($conn, "INSERT INTO projects_status (code, period_id, status) VALUES (?, ?, ?)");
                                                        mysqli_stmt_bind_param($setStatus, "sii", $code, $period_id, $status);
                                                        mysqli_stmt_execute($setStatus);
                                                    }
                                                }

                                                // create the automated global expenses
                                                createAutomatedExpenses($conn, $code, $period_id);

                                                // log project creation
                                                $message = "Successfully created the project with code $code. ";
                                                $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                                                mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                                                mysqli_stmt_execute($log);
                                            }
                                            else { echo "<span class=\"log-fail\">Failed</span> to create the project.<br>"; }
                                        }
                                        else { echo "<span class=\"log-fail\">Failed</span> to create the project. The department selected does not exist.<br>"; }
                                    }
                                    else { echo "<span class=\"log-fail\">Failed</span> to create the project. A project with the code $code already exists!<br>"; }
                                }
                                else { echo "<span class=\"log-fail\">Failed</span> to create the project. An unexpected error has occurred! Please try again later.<br>"; }
                            }
                            else { echo "<span class=\"log-fail\">Failed</span> to create the project. The function code must follow the WUFAR convention and be a number within 100000 and 999999!<br>"; }
                        }
                        else { echo "<span class=\"log-fail\">Failed</span> to create the project. The fund code must follow the WUFAR convention and be a number within 10 and 99!<br>"; }
                    }
                    else { echo "<span class=\"log-fail\">Failed</span> to create the project. The project code must follow the WUFAR convention and be a number within 100 and 999!<br>"; }
                }
                else { echo "<span class=\"log-fail\">Failed</span> to create the project. You must provide all the of the required parameters.<br>"; }
            }
            else { echo "<span class=\"log-fail\">Failed</span> to create the project. The period provided was invalid!<br>"; }
        }
        else { echo "<span class=\"log-fail\">Failed</span> to create the project. Your account does not have permission to add new projects!<br>"; }

        // disconnect from the database
        mysqli_close($conn);
    }
?>