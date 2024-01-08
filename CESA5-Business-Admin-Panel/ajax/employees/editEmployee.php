<?php
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get additional required files
        include("../../includes/functions.php");
        include("../../includes/config.php");
        include("../../getSettings.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "EDIT_EMPLOYEES"))
        {
            // get period name from POST
            if (isset($_POST["period"]) && $_POST["period"] <> "") { $period = $_POST["period"]; } else { $period = null; }

            if ($period != null)
            {
                if ($period_id = getPeriodID($conn, $period)) // verify the period exists; if it exists, store the period ID
                {
                    /* 
                    * get parameters from POST
                    */        
                    // Employee ID
                    if (isset($_POST["employee_id"]) && $_POST["employee_id"] <> "") { $employee_id = $_POST["employee_id"]; } else { $employee_id = null; }
                    // Employee Information
                    if (isset($_POST["fname"]) && $_POST["fname"] <> "") { $fname = $_POST["fname"]; } else { $fname = null; }
                    if (isset($_POST["lname"]) && $_POST["lname"] <> "") { $lname = $_POST["lname"]; } else { $lname = null; }
                    if (isset($_POST["email"]) && $_POST["email"] <> "") { $email = $_POST["email"]; } else { $email = null; }
                    if (isset($_POST["phone"]) && $_POST["phone"] <> "") { $phone = $_POST["phone"]; } else { $phone = null; }
                    if (isset($_POST["birthday"]) && $_POST["birthday"] <> "") { $birthday = $_POST["birthday"]; } else { $birthday = null; }
                    if (isset($_POST["gender"]) && $_POST["gender"] <> "") { $gender = $_POST["gender"]; } else { $gender = null; }
                    if (isset($_POST["marital_status"]) && $_POST["marital_status"] <> "") { $marital_status = $_POST["marital_status"]; } else { $marital_status = null; }
                    // Employee Address
                    if (isset($_POST["line1"]) && $_POST["line1"] <> "") { $line1 = $_POST["line1"]; } else { $line1 = null; }
                    if (isset($_POST["line2"]) && $_POST["line2"] <> "") { $line2 = $_POST["line2"]; } else { $line2 = null; }
                    if (isset($_POST["city"]) && $_POST["city"] <> "") { $city = $_POST["city"]; } else { $city = null; }
                    if (isset($_POST["state"]) && $_POST["state"] <> "") { $state = $_POST["state"]; } else { $state = null; }
                    if (isset($_POST["zip"]) && $_POST["zip"] <> "") { $zip = $_POST["zip"]; } else { $zip = null; }
                    // Role Details
                    if (isset($_POST["title"]) && $_POST["title"] <> "") { $title = $_POST["title"]; } else { $title = null; }
                    if (isset($_POST["department"]) && $_POST["department"] <> "") { $department = $_POST["department"]; } else { $department = null; }
                    if (isset($_POST["supervisor"]) && $_POST["supervisor"] <> "") { $supervisor = $_POST["supervisor"]; } else { $supervisor = null; }
                    if (isset($_POST["position"]) && $_POST["position"] <> "") { $position = $_POST["position"]; } else { $position = null; }
                    if (isset($_POST["area"]) && $_POST["area"] <> "") { $area = $_POST["area"]; } else { $area = null; }
                    if (isset($_POST["experience"]) && $_POST["experience"] <> "") { $experience = $_POST["experience"]; } else { $experience = null; }
                    if (isset($_POST["experience_adjustment"]) && is_numeric($_POST["experience_adjustment"]) && $_POST["experience_adjustment"] >= 0) { $experience_adjustment = $_POST["experience_adjustment"]; } else { $experience_adjustment = 0; }
                    if (isset($_POST["degree"]) && $_POST["degree"] <> "") { $degree = $_POST["degree"]; } else { $degree = null; }
                    // Contract Details
                    if (isset($_POST["hire_date"]) && $_POST["hire_date"] <> "") { $hire_date = $_POST["hire_date"]; } else { $hire_date = null; }
                    if (isset($_POST["end_date"]) && $_POST["end_date"] <> "") { $end_date = $_POST["end_date"]; } else { $end_date = null; }
                    if (isset($_POST["original_hire_date"]) && $_POST["original_hire_date"] <> "") { $original_hire_date = $_POST["original_hire_date"]; } else { $original_hire_date = null; }
                    if (isset($_POST["original_end_date"]) && $_POST["original_end_date"] <> "") { $original_end_date = $_POST["original_end_date"]; } else { $original_end_date = null; }
                    if (isset($_POST["contract_start_date"]) && $_POST["contract_start_date"] <> "") { $contract_start_date = $_POST["contract_start_date"]; } else { $contract_start_date = null; }
                    if (isset($_POST["contract_end_date"]) && $_POST["contract_end_date"] <> "") { $contract_end_date = $_POST["contract_end_date"]; } else { $contract_end_date = null; }
                    if (isset($_POST["contract_type"]) && is_numeric($_POST["contract_type"])) { $contract_type = $_POST["contract_type"]; } else { $contract_type = 0; }
                    if (isset($_POST["days"]) && $_POST["days"] <> "") { $days = $_POST["days"]; } else { $days = null; }
                    if (isset($_POST["calendar_type"]) && is_numeric($_POST["calendar_type"])) { $calendar_type = $_POST["calendar_type"]; } else { $calendar_type = 0; }
                    if (isset($_POST["rate"]) && $_POST["rate"] <> "") { $rate = $_POST["rate"]; } else { $rate = null; }
                    if (isset($_POST["num_of_pays"]) && is_numeric($_POST["num_of_pays"])) { $num_of_pays = $_POST["num_of_pays"]; } else { $num_of_pays = 0; }
                    if (isset($_POST["health"]) && $_POST["health"] <> "") { $health = $_POST["health"]; } else { $health = null; }
                    if (isset($_POST["dental"]) && $_POST["dental"] <> "") { $dental = $_POST["dental"]; } else { $dental = null; }
                    if (isset($_POST["wrs"]) && $_POST["wrs"] <> "") { $wrs = $_POST["wrs"]; } else { $wrs = null; }
                    // Account Information
                    if (isset($_POST["status"]) && is_numeric($_POST["status"])) { $status = $_POST["status"]; } else { $status = 0; }
                    if (isset($_POST["role"]) && $_POST["role"] <> "") { $role = $_POST["role"]; } else { $role = null; }
                    if (isset($_POST["global"]) && is_numeric($_POST["global"])) { $global = $_POST["global"]; } else { $global = 0; }
                    // Syncing Statuses
                    if (isset($_POST["sync_demographics"]) && is_numeric($_POST["sync_demographics"]) && $_POST["sync_demographics"] == 1) { $sync_demographics = 1; } else { $sync_demographics = 0; }
                    if (isset($_POST["sync_position"]) && is_numeric($_POST["sync_position"]) && $_POST["sync_position"] == 1) { $sync_position = 1; } else { $sync_position = 0; }
                    if (isset($_POST["sync_contract"]) && is_numeric($_POST["sync_contract"]) && $_POST["sync_contract"] == 1) { $sync_contract = 1; } else { $sync_contract = 0; }

                    // call the function to edit the employee
                    if (editEmployee($conn, $period_id, $employee_id, $fname, $lname, $email, $phone, $birthday, $gender, $marital_status, $line1, $line2, $city, $state, $zip, $title, $department, $supervisor, $hire_date, $end_date, $original_hire_date, $original_end_date, $rate, $days, $contract_type, $contract_start_date, $contract_end_date, $calendar_type, $num_of_pays, $health, $dental, $wrs, $position, $area, $experience, $experience_adjustment, $degree, $role, $global, $status))
                    {
                        // update the employee sync status
                        $updateSyncStatus = mysqli_prepare($conn, "UPDATE employees SET sync_demographics=?, sync_position=?, sync_contract=? WHERE id=?");
                        mysqli_stmt_bind_param($updateSyncStatus, "iiii", $sync_demographics, $sync_position, $sync_contract, $employee_id);
                        mysqli_stmt_execute($updateSyncStatus);
                    }
                }
            }
        }
        else { echo "<span class=\"log-fail\">Failed</span> to edit the employee. Your account does not have permission to edit employees.<br>"; }
        
        // disconnect from the database
        mysqli_close($conn);
    }
?>