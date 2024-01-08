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
        if (checkUserPermission($conn, "EDIT_PROJECTS"))
        {
            // get period name from POST
            if (isset($_POST["period"]) && $_POST["period"] <> "") { $period = $_POST["period"]; } else { $period = null; }

            // verify the period exists; if it exists, store the period ID
            if ($period != null && $period_id = getPeriodID($conn, $period))
            {
                // get parameters from POST
                if (isset($_POST["code"]) && $_POST["code"] <> "") { $code = $_POST["code"]; } else { $code = null; }
                if (isset($_POST["form_code"]) && $_POST["form_code"] <> "") { $form_code = $_POST["form_code"]; } else { $form_code = null; }
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

                if ($code != null && $form_code != null && $name != null && $fund != null && $func != null)
                {
                    if (is_numeric($code) && ($code >= 100 && $code <= 999))
                    {
                        if (is_numeric($fund) && ($fund >= 10 && $fund <= 99))
                        {
                            if (is_numeric($func) && ($func >= 100000 && $func <= 999999))
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
                                    // get the time we updated the project
                                    date_default_timezone_set("America/Chicago");
                                    $timestamp = date("Y-m-d H:i:s");

                                    $editProject = mysqli_prepare($conn, "UPDATE projects SET name=?, description=?, department_id=?, fund_code=?, function_code=?, supervision_costs=?, indirect_costs=?, calc_fte=?, FTE_days=?, leave_time=?, prep_work=?, personal_development=?, staff_location=?, updated=? WHERE code=?");
                                    mysqli_stmt_bind_param($editProject, "ssiiiiiiiiiiiss", $name, $desc, $dept, $fund, $func, $supervision_costs, $indirect_costs, $calc_fte, $FTE_days, $leave_time, $prep_work, $personal_development, $location, $timestamp, $code);
                                    if (mysqli_stmt_execute($editProject)) 
                                    { 
                                        echo "<span class=\"log-success\">Successfully</span> edited the project details.<br>"; 

                                        // get current project status
                                        $getStatus = mysqli_prepare($conn, "SELECT status FROM projects_status WHERE code=? AND period_id=?");
                                        mysqli_stmt_bind_param($getStatus, "si", $code, $period_id);
                                        if (mysqli_stmt_execute($getStatus))
                                        {
                                            $getStatusResult = mysqli_stmt_get_result($getStatus);
                                            if (mysqli_num_rows($getStatusResult) == 0) // status not set; insert into projects_status table
                                            {
                                                $setStatus = mysqli_prepare($conn, "INSERT INTO projects_status (code, period_id, status) VALUES (?, ?, ?)");
                                                mysqli_stmt_bind_param($setStatus, "sii", $code, $period_id, $status);
                                                mysqli_stmt_execute($setStatus);
                                            }
                                        }

                                        // update the project's status for just the period provided
                                        $setStatus = mysqli_prepare($conn, "UPDATE projects_status SET status=? WHERE code=? AND period_id=?");
                                        mysqli_stmt_bind_param($setStatus, "isi", $status, $code, $period_id);
                                        if (mysqli_stmt_execute($setStatus)) // successfully updated the project's status
                                        {
                                            // clear out the project's budget for the period if we are setting it to inactive
                                            if ($status == 0)
                                            {
                                                // log project employees clear
                                                echo "<span class=\"log-success\">Successfully</span> inactivated the project for $period.<br>";
                                                $message = "Successfully inactivated the project for $period (code: $code; period ID: $period_id).";
                                                $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                                                mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                                                mysqli_stmt_execute($log);

                                                // clear project employees
                                                $clearEmps = mysqli_prepare($conn, "DELETE FROM project_employees WHERE project_code=? AND period_id=?");
                                                mysqli_stmt_bind_param($clearEmps, "si", $code, $period_id);
                                                if (mysqli_stmt_execute($clearEmps))
                                                {
                                                    // log project employees clear
                                                    echo "<span class=\"log-success\">Successfully</span> cleared the project's employees for $period.<br>";
                                                    $message = "Successfully cleared the project's employees for $period (code: $code; period ID: $period_id).";
                                                    $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                                                    mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                                                    mysqli_stmt_execute($log);
                                                }
                                                else { echo "<span class=\"log-fail\">Failed</span> to clear the project's employees for $period.<br>"; }

                                                // clear project expenses
                                                $clearExps = mysqli_prepare($conn, "DELETE FROM project_expenses WHERE project_code=? AND period_id=?");
                                                mysqli_stmt_bind_param($clearExps, "si", $code, $period_id);
                                                if (mysqli_stmt_execute($clearExps))
                                                {
                                                    // log project expenses clear
                                                    echo "<span class=\"log-success\">Successfully</span> cleared the project's expenses for $period.<br>";
                                                    $message = "Successfully cleared the project's expenses for $period (code: $code; period ID: $period_id).";
                                                    $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                                                    mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                                                    mysqli_stmt_execute($log);
                                                } 
                                                else { echo "<span class=\"log-fail\">Failed</span> to clear the project's expenses for $period.<br>"; }

                                                // clear project test employees
                                                $clearTestEmps = mysqli_prepare($conn, "DELETE FROM project_employees_misc WHERE project_code=? AND period_id=?");
                                                mysqli_stmt_bind_param($clearTestEmps, "si", $code, $period_id);
                                                if (!mysqli_stmt_execute($clearTestEmps)) { /* TODO - handle query error */ }

                                                // set revenues to be projectless
                                                $nullRev = mysqli_prepare($conn, "UPDATE revenues SET project_code=NULL WHERE project_code=? AND period_id=?");
                                                mysqli_stmt_bind_param($nullRev, "si", $code, $period_id);
                                                if (mysqli_stmt_execute($nullRev))
                                                {
                                                    // log project employees clear
                                                    echo "<span class=\"log-success\">Successfully</span> unassigned revenues to this project for $period.<br>";
                                                    $message = "Successfully unassigned revenues to this project for $period (code: $code; period ID: $period_id).";
                                                    $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                                                    mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                                                    mysqli_stmt_execute($log);
                                                }
                                                else { echo "<span class=\"log-fail\">Failed</span> to unassign revenues to this project for $period.<br>"; }
                                            }
                                        }

                                        // recalculate the project's automated expenses
                                        recalculateAutomatedExpenses($conn, $code, $period_id);

                                        // log project edit
                                        $message = "Successfully edited the project with code $code. ";
                                        $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                                        mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                                        mysqli_stmt_execute($log);

                                        // we are not changing the project code; edit project normally
                                        if ($code != $form_code)
                                        {
                                            echo "Attempting to edit the project code...<br>"; 
                                        
                                            if (!verifyProject($conn, $form_code))
                                            {
                                                // update the project code
                                                $updateProject = mysqli_prepare($conn, "UPDATE projects SET code=? WHERE code=?");
                                                mysqli_stmt_bind_param($updateProject, "ss", $form_code, $code);
                                                if (mysqli_stmt_execute($updateProject))
                                                {
                                                    $updateCode = mysqli_prepare($conn, "UPDATE projects_status SET code=? WHERE code=?");
                                                    mysqli_stmt_bind_param($updateCode, "ss", $form_code, $code);
                                                    if (!mysqli_stmt_execute($updateCode)) { /* TODO - handle query error */ }

                                                    $updateCode = mysqli_prepare($conn, "UPDATE project_employees SET project_code=? WHERE project_code=?");
                                                    mysqli_stmt_bind_param($updateCode, "ss", $form_code, $code);
                                                    if (!mysqli_stmt_execute($updateCode)) { /* TODO - handle query error */ }

                                                    $updateCode = mysqli_prepare($conn, "UPDATE project_employees_misc SET project_code=? WHERE project_code=?");
                                                    mysqli_stmt_bind_param($updateCode, "ss", $form_code, $code);
                                                    if (!mysqli_stmt_execute($updateCode)) { /* TODO - handle query error */ }

                                                    $updateCode = mysqli_prepare($conn, "UPDATE project_expenses SET project_code=? WHERE project_code=?");
                                                    mysqli_stmt_bind_param($updateCode, "ss", $form_code, $code);
                                                    if (!mysqli_stmt_execute($updateCode)) { /* TODO - handle query error */ }

                                                    $updateCode = mysqli_prepare($conn, "UPDATE revenues SET project_code=? WHERE project_code=?");
                                                    mysqli_stmt_bind_param($updateCode, "ss", $form_code, $code);
                                                    if (!mysqli_stmt_execute($updateCode)) { /* TODO - handle query error */ }

                                                    $updateCode = mysqli_prepare($conn, "UPDATE services SET project_code=? WHERE project_code=?");
                                                    mysqli_stmt_bind_param($updateCode, "ss", $form_code, $code);
                                                    if (!mysqli_stmt_execute($updateCode)) { /* TODO - handle query error */ }

                                                    $updateCode = mysqli_prepare($conn, "UPDATE services_other_provided SET project_code=? WHERE project_code=?");
                                                    mysqli_stmt_bind_param($updateCode, "ss", $form_code, $code);
                                                    if (!mysqli_stmt_execute($updateCode)) { /* TODO - handle query error */ }

                                                    // log project edit
                                                    echo "<span class=\"log-success\">Successfully</span> updated the project code to $form_code from code $code.<br>";
                                                    $message = "Successfully updated the project code to $form_code from code $code.";
                                                    $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                                                    mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                                                    mysqli_stmt_execute($log);
                                                }
                                                else { echo "<span class=\"log-fail\">Failed</span> to edit the project code. An unexpected error has occurred! Please try again later.<br>"; }
                                            }
                                            else { echo "<span class=\"log-fail\">Failed</span> to edit the project code. A project with that code already exists! Project codes must be unique.<br>"; }
                                        }
                                    }
                                    else { echo "<span class=\"log-fail\">Failed</span> to edit the project.<br>"; }
                                }
                                else { echo "<span class=\"log-fail\">Failed</span> to edit the project. The department selected does not exist.<br>"; }
                            }
                            else { echo "<span class=\"log-fail\">Failed</span> to edit the project. The function code must follow the WUFAR convention and be a number within 100000 and 999999!<br>"; }
                        }
                        else { echo "<span class=\"log-fail\">Failed</span> to edit the project. The fund code must follow the WUFAR convention and be a number within 10 and 99!<br>"; }
                    }
                    else { echo "<span class=\"log-fail\">Failed</span> to edit the project. The project code must follow the WUFAR convention and be a number within 100 and 999!<br>"; }
                }
                else { echo "<span class=\"log-fail\">Failed</span> to edit the project. You must provide all the required parameters.<br>"; }
            }
            else { echo "<span class=\"log-fail\">Failed</span> to edit the project. The period provided was invalid!<br>"; }
        }
        else { echo "<span class=\"log-fail\">Failed</span> to edit the project. Your account does not have permission to edit projects!<br>"; }

        // disconnect from the database
        mysqli_close($conn);
    }
?>