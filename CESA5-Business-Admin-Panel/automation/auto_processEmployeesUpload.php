<?php
    require_once("../includes/functions.php");
    require_once("../includes/config.php");

    // initialize log message
    $message = "";

    try
    {
        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);
        
        // connect to the SFTP server
        if ($ssh_conn = ssh2_connect(SFTP_HOST, SFTP_PORT))
        {
            // authenticate SFTP connection
            if (ssh2_auth_password($ssh_conn, SFTP_USER, SFTP_PASS))
            {
                // open SFTP stream
                if ($sftp = ssh2_sftp($ssh_conn))
                {
                    // open SFTP directory
                    if ($dir = opendir("ssh2.sftp://{$sftp}/./".SFTP_DIR_USER."/uploads"))
                    {
                        // initialize variables
                        $updated = $inserted = $errors = $queues = 0;

                        // pre-initialize all codes
                        $healthCodes = [];
                        $healthCodes["Single"] = "";
                        $healthCodes["Family"] = "";
                        $healthCodes["None"] = "";

                        $dentalCodes = [];
                        $dentalCodes["Single"] = "";
                        $dentalCodes["Family"] = "";
                        $dentalCodes["None"] = "";

                        $WRSCodes = [];
                        $WRSCodes["Yes"] = "";
                        $WRSCodes["No"] = "";

                        $GenderCodes = [];
                        $GenderCodes["Male"] = "";
                        $GenderCodes["Female"] = "";

                        $AddressTypes = [];
                        $AddressTypes["Street"] = "";
                        $AddressTypes["PO"] = "";

                        // get current health codes from database
                        $getHealthCodes = mysqli_query($conn, "SELECT code, plan FROM codes WHERE indicator='Health'");
                        while ($code = mysqli_fetch_array($getHealthCodes))
                        {
                            if ($code["plan"] == "None") { $healthCodes["None"] = $code["code"]; }
                            else if ($code["plan"] == "Single") { $healthCodes["Single"] = $code["code"]; }
                            else if ($code["plan"] == "Family") { $healthCodes["Family"] = $code["code"]; }
                        }

                        // get current dental codes from database
                        $getDentalCodes = mysqli_query($conn, "SELECT code, plan FROM codes WHERE indicator='Dental'");
                        while ($code = mysqli_fetch_array($getDentalCodes))
                        {
                            if ($code["plan"] == "None") { $dentalCodes["None"] = $code["code"]; }
                            else if ($code["plan"] == "Single") { $dentalCodes["Single"] = $code["code"]; }
                            else if ($code["plan"] == "Family") { $dentalCodes["Family"] = $code["code"]; }
                        }

                        // get current WRS codes from database
                        $getWRSCodes = mysqli_query($conn, "SELECT code, plan FROM codes WHERE indicator='WRS'");
                        while ($code = mysqli_fetch_array($getWRSCodes))
                        {
                            if ($code["plan"] == "Yes") { $WRSCodes["Yes"] = $code["code"]; }
                            else if ($code["plan"] == "No") { $WRSCodes["No"] = $code["code"]; }
                        }

                        // get current gender codes from database
                        $getGenderCodes = mysqli_query($conn, "SELECT code, plan FROM codes WHERE indicator='Gender'");
                        while ($code = mysqli_fetch_array($getGenderCodes))
                        {
                            if ($code["plan"] == "Male") { $GenderCodes["Male"] = $code["code"]; }
                            else if ($code["plan"] == "Female") { $GenderCodes["Female"] = $code["code"]; }
                        }

                        // get address types codes
                        $getAddressCodes = mysqli_query($conn, "SELECT code, plan FROM codes WHERE indicator='Address Type'");
                        while ($code = mysqli_fetch_array($getAddressCodes))
                        {
                            if ($code["plan"] == "Street") { $AddressTypes["Street"] = $code["code"]; }
                            else if ($code["plan"] == "PO") { $AddressTypes["PO"] = $code["code"]; }
                        }

                        // create an array to store valid degrees
                        $degrees = [];
                        $getDegrees = mysqli_query($conn, "SELECT * FROM degrees ORDER BY code ASC");
                        if (mysqli_num_rows($getDegrees) > 0) // degrees found
                        {
                            while ($degree_result = mysqli_fetch_array($getDegrees))
                            {
                                $degree_code = $degree_result["code"];
                                $degree_label = $degree_result["label"];
                                $degree_str = $degree_code." - ".$degree_label;
                                $degrees[] = $degree_str;
                            }
                        }

                        // create an array to store valid positions
                        $positions = [];
                        $getPositions = mysqli_query($conn, "SELECT DISTINCT assignment_position FROM dpi_employees");
                        if (mysqli_num_rows($getPositions) > 0) // positions found
                        {
                            while ($dpi_position = mysqli_fetch_array($getPositions))
                            {
                                $positions[] = $dpi_position["assignment_position"];
                            }
                        }

                        // create an array to store valid areas
                        $areas = [];
                        $getAreas = mysqli_query($conn, "SELECT DISTINCT assignment_area FROM dpi_employees");
                        if (mysqli_num_rows($getAreas) > 0) // areas found
                        {
                            while ($dpi_area = mysqli_fetch_array($getAreas))
                            {
                                $areas[] = $dpi_area["assignment_area"];
                            }
                        }

                        // initialize array to store all files within directory
                        $files = [];

                        // get all files within the SFTP directory
                        while (false !== ($file = readdir($dir)))
                        {
                            if ($file == "." || $file == "..") { continue; }
                            else { $files[] = $file; }
                        }

                        if (count($files) > 0)
                        {
                            foreach ($files as $file)
                            {
                                if ($file == "BAP Nightly Export Final.csv")
                                {
                                    // open the file for reading
                                    if ($filestream = fopen("ssh2.sftp://{$sftp}/./".SFTP_DIR_USER."/uploads/{$file}", "r"))
                                    {
                                        // disable all current queued changes
                                        $disableQueue = mysqli_query($conn, "UPDATE sync_queue_employee_compensation SET status=3, action_time=CURRENT_TIMESTAMP(), action_user=-2 WHERE status=0");

                                        // for each employee in file, sync data
                                        while ($data = fgetcsv($filestream, 1000, ",", '"'))
                                        {
                                            if (isset($data[0]) && ($data[0] != "Employee Information" && $data[0] != "Employee ID" && $data[0] != "ID" && $data[0] != "C5 BAP - Employees Import")) // skip the first two rows by looking at cell data
                                            {
                                                // get employee information from CSV
                                                if (isset($data[0])) { $employee_id = clean_data($data[0]); } else { $employee_id = null; } // employee_id
                                                if (isset($data[1])) { $fname = clean_data($data[1]); } else { $fname = null; } // first name
                                                if (isset($data[2])) { $lname = clean_data($data[2]); } else { $lname = null; } // last name

                                                // get employee demographics from CSV
                                                if (isset($data[3])) { $DOB = clean_data($data[3]); } else { $DOB = null; } // date of birth
                                                if (isset($data[4])) { $email = clean_data($data[4]); } else { $email = null; } // email
                                                if (isset($data[5])) { $phone = clean_data($data[5]); } else { $phone = null; } // phone
                                                if (isset($data[6])) { $gender = clean_data($data[6]); } else { $gender = null; } // gender

                                                // get employee address from CSV
                                                if (isset($data[7])) { $address_type = clean_data($data[7]); } else { $address_type = null; } // type
                                                if (isset($data[8])) { $line1 = clean_data($data[8]); } else { $line1 = null; } // line1
                                                if (isset($data[9])) { $line2 = clean_data($data[9]); } else { $line2 = null; } // line2
                                                if (isset($data[10])) { $po_box = clean_data($data[10]); } else { $po_box = null; } // PO Box
                                                if (isset($data[11])) { $city = clean_data($data[11]); } else { $city = null; } // city
                                                if (isset($data[12])) { $state = clean_data($data[12]); } else { $state = null; } // state
                                                if (isset($data[13])) { $zip = clean_data($data[13]); } else { $zip = null; } // zip code

                                                // get contract details
                                                if (isset($data[14])) { $original_start_date = clean_data($data[14]); } else { $original_start_date = null; } 
                                                if (isset($data[15])) { $original_end_date = clean_data($data[15]); } else { $original_end_date = null; } 
                                                if (isset($data[16])) { $most_recent_start_date = clean_data($data[16]); } else { $most_recent_start_date = null; } 
                                                if (isset($data[17])) { $most_recent_end_date = clean_data($data[17]); } else { $most_recent_end_date = null; } 
                                                if (isset($data[18])) { $calendar = clean_data($data[18]); } else { $calendar = null; } 
                                                if (isset($data[19])) { $yearly_pay = clean_data($data[19]); } else { $yearly_pay = null; } 
                                                if (isset($data[20])) { $daily_pay = clean_data($data[20]); } else { $daily_pay = null; } 
                                                if (isset($data[21])) { $hourly_pay = clean_data($data[21]); } else { $hourly_pay = null; } 
                                                if (isset($data[22])) { $health = clean_data($data[22]); } else { $health = null; } 
                                                if (isset($data[23])) { $dental = clean_data($data[23]); } else { $dental = null; } 
                                                if (isset($data[24])) { $status = $data[24]; } else { $status = null; } 
                                                if (isset($data[25])) { $contract_start_date = clean_data($data[25]); } else { $contract_start_date = null; } 
                                                if (isset($data[26])) { $contract_end_date = clean_data($data[26]); } else { $contract_end_date = null; } 
                                                if (isset($data[27])) { $contract_days = clean_data($data[27]); } else { $contract_days = null; } 
                                                if (isset($data[28])) { $calendar_type = clean_data($data[28]); } else { $calendar_type = null; } 
                                                if (isset($data[29])) { $num_of_pays = clean_data($data[29]); } else { $num_of_pays = null; } 
                                                if (isset($data[30])) { $total_yoe = clean_data($data[30]); } else { $total_yoe = null; }
                                                if (isset($data[32])) { $degree = clean_data($data[32]); } else { $degree = null; }
                                                if (isset($data[33])) { $dept = clean_data($data[33]); } else { $dept = null; }
                                                if (isset($data[34])) { $wrs_code = clean_data($data[34]); } else { $wrs_code = null; }
                                                if (isset($data[35])) { $wrs = clean_data($data[35]); } else { $wrs = null; }

                                                // get period name
                                                if (isset($data[31])) { $period_name = clean_data($data[31]); } else { $period_name = null; }

                                                // verify the period exists; if it exists, store the period ID
                                                if ($period_name != null && $period_id = getPeriodID($conn, $period_name))
                                                {
                                                    // store if the period is editable
                                                    $is_editable = isPeriodEditable($conn, $period_id);

                                                    // if the period is editable; continue
                                                    if ($is_editable == 1)
                                                    {
                                                        // verify and convert data from upload to database values if necessary
                                                        if ($status == "Active" || strtoupper($status) == "TRUE" || $status == true) { $DB_status = 1; } else { $DB_status = 0; }
                                                        if (is_numeric($contract_days)) { $DB_days = $contract_days; } else { $DB_days = 0; }
                                                        if (!is_numeric($total_yoe) || $total_yoe == null) { $total_yoe = 0; }
                                                        if (is_numeric($yearly_pay)) { $DB_rate = $yearly_pay; } 
                                                        else 
                                                        { 
                                                            // attempt to remove common characters from string
                                                            $clean_rate = str_replace("$", "", $yearly_pay); // remove $ if found
                                                            $clean_rate = str_replace(",", "", $clean_rate); // remove , if found 
                                                            if (is_numeric($clean_rate)) { $DB_rate = $clean_rate; }
                                                            else { $DB_rate = 0; }
                                                        }
                                                        $DB_DOB = date("Y-m-d", strtotime($DOB));

                                                        // for variables that look at a database code; set according to code found in upload
                                                        if ($health == $healthCodes["Single"]) { $DB_health = 2; }
                                                        else if ($health == $healthCodes["Family"]) { $DB_health = 1; }
                                                        else if ($health == $healthCodes["None"]) { $DB_health = 0; }
                                                        else { $DB_health = 0; }

                                                        if ($dental == $dentalCodes["Single"]) { $DB_dental = 2; }
                                                        else if ($dental == $dentalCodes["Family"]) { $DB_dental = 1; }
                                                        else if ($dental == $dentalCodes["None"]) { $DB_dental = 0; }
                                                        else { $DB_dental = 0; }

                                                        if ($wrs == $WRSCodes["Yes"]) { $DB_wrs = 1; }
                                                        else if ($wrs == $WRSCodes["No"]) { $DB_wrs = 0; }
                                                        else { $DB_wrs = 0; }

                                                        if ($gender == $GenderCodes["Male"]) { $DB_gender = 1; }
                                                        else if ($gender == $GenderCodes["Female"]) { $DB_gender = 2; }
                                                        else { $DB_gender = 0; }

                                                        if ($address_type == $AddressTypes["Street"]) 
                                                        { 
                                                            // use regular addresses
                                                        }
                                                        else if ($address_type == $AddressTypes["PO"]) 
                                                        { 
                                                            // set line1 to PO box
                                                            $line1 = $po_box;
                                                            $line2 = null;
                                                        }
                                                        else 
                                                        { 
                                                            $line1 = null;
                                                            $line2 = null;
                                                            $street = null;
                                                            $city = null;
                                                            $state = null;
                                                            $zip = null; 
                                                        }

                                                        // set calendar type and salary; convert salary if needed based on calendar type
                                                        if (trim($calendar_type) == "Hourly")
                                                        {
                                                            // calculate yearly pay based on daily pay * days
                                                            $DB_rate = $daily_pay * $contract_days;
                                                            $DB_calendar_type = 1;
                                                        }
                                                        else if (trim($calendar_type) == "Salary")
                                                        {
                                                            // use full assignment pay as yearly pay
                                                            $DB_rate = $yearly_pay;
                                                            $DB_calendar_type = 2;
                                                        }
                                                        else
                                                        {
                                                            $DB_rate = 0;
                                                            $DB_calendar_type = 0;
                                                        }

                                                        if (isset($most_recent_start_date) && $most_recent_start_date != null) { $most_recent_start_date = date("Y-m-d", strtotime($most_recent_start_date)); } else { $most_recent_start_date = null; }
                                                        if (isset($most_recent_end_date) && $most_recent_end_date != null) { $most_recent_end_date = date("Y-m-d", strtotime($most_recent_end_date)); } else { $most_recent_end_date = null; }
                                                        if (isset($original_start_date) && $original_start_date != null) { $original_start_date = date("Y-m-d", strtotime($original_start_date)); } else { $original_start_date = null; }
                                                        if (isset($original_end_date) && $original_end_date != null) { $original_end_date = date("Y-m-d", strtotime($original_end_date)); } else { $original_end_date = null; }
                                                        if (isset($contract_start_date) && $contract_start_date != null) { $contract_start_date = date("Y-m-d", strtotime($contract_start_date)); } else { $contract_start_date = null; }
                                                        if (isset($contract_end_date) && $contract_end_date != null) { $contract_end_date = date("Y-m-d", strtotime($contract_end_date)); } else { $contract_end_date = null; }

                                                        ///////////////////////////////////////////////////////////////
                                                        //
                                                        //  ADD NEW EMPLOYEE
                                                        //
                                                        ///////////////////////////////////////////////////////////////
                                                        if (!checkExistingEmployee($conn, $employee_id))
                                                        {
                                                            // attempt to add the new employee to the database
                                                            $addEmployee = mysqli_prepare($conn, "INSERT INTO employees (id, fname, lname, email, phone, birthday, gender, original_hire_date, original_end_date, most_recent_hire_date, most_recent_end_date, queued) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");
                                                            mysqli_stmt_bind_param($addEmployee, "isssssissss", $employee_id, $fname, $lname, $email, $phone, $DB_DOB, $DB_gender, $original_start_date, $original_end_date, $most_recent_start_date, $most_recent_end_date);
                                                            if (mysqli_stmt_execute($addEmployee)) // successfully added the employee
                                                            {
                                                                // log status to screen
                                                                // echo "<span class=\"log-success\">Successfully</span> added $fname $lname.<br>";

                                                                // add employee's benefits, compensation, and additional role details
                                                                if (!setEmployeeCompensation($conn, $period_id, $employee_id)) 
                                                                { 
                                                                    // echo "<span class=\"log-fail\">Failed</span> to set $fname $lname's benefits, compensation, and role details. An unexpected error has occurred. Please try again later!<br>"; 
                                                                }

                                                                // add employee's address
                                                                if (!setEmployeeAddress($conn, $employee_id, $line1, $line2, $city, $state, $zip)) 
                                                                { 
                                                                    // echo "<span class=\"log-fail\">Failed</span> to set $fname $lname's address. An unexpected error has occurred. Please try again later!<br>"; 
                                                                }
                                                            
                                                                // log employee creation
                                                                $message = "Successfully queued $fname $lname as a new employee with the ID of $employee_id via automation.";
                                                                $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                                                                mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                                                                mysqli_stmt_execute($log);
                                                            }
                                                        }

                                                        ///////////////////////////////////////////////////////////////
                                                        //
                                                        //  EDIT EXISTING EMPLOYEE
                                                        //
                                                        ///////////////////////////////////////////////////////////////
                                                        if (checkExistingEmployee($conn, $employee_id))
                                                        { 
                                                            // get current employee settings for global
                                                            $role_id = 3; // assume 3 (employee)
                                                            $sync_demographics = $sync_position = $sync_contract = $global = 0; // assume 0
                                                            $getCurrentSettings = mysqli_prepare($conn, "SELECT role_id, global, sync_demographics, sync_position, sync_contract FROM employees WHERE id=?");
                                                            mysqli_stmt_bind_param($getCurrentSettings, "i", $employee_id);
                                                            if (mysqli_stmt_execute($getCurrentSettings))
                                                            {
                                                                $getCurrentSettingsResult = mysqli_stmt_get_result($getCurrentSettings);
                                                                if (mysqli_num_rows($getCurrentSettingsResult) > 0)
                                                                {
                                                                    $current_settings = mysqli_fetch_array($getCurrentSettingsResult);
                                                                    $role_id = $current_settings["role_id"];
                                                                    $global = $current_settings["global"];
                                                                    $sync_demographics = $current_settings["sync_demographics"];
                                                                    $sync_position = $current_settings["sync_position"];
                                                                    $sync_contract = $current_settings["sync_contract"];
                                                                }
                                                            }

                                                            // get the employee's current compensation
                                                            $DB_contract_type = 0; // initialize contract type
                                                            $title_id = null; // initialize title ID
                                                            $getEmployeeDetails = mysqli_prepare($conn, "SELECT contract_type, title_id, assignment_position, sub_assignment, highest_degree, experience_adjustment FROM employee_compensation WHERE employee_id=? AND period_id=?");
                                                            mysqli_stmt_bind_param($getEmployeeDetails, "ii", $employee_id, $period_id);
                                                            if (mysqli_stmt_execute($getEmployeeDetails))
                                                            {
                                                                $getEmployeeDetailsResult = mysqli_stmt_get_result($getEmployeeDetails);
                                                                if (mysqli_num_rows($getEmployeeDetailsResult) > 0)
                                                                {
                                                                    $employeeDetails = mysqli_fetch_array($getEmployeeDetailsResult);
                                                                    $DB_contract_type = $employeeDetails["contract_type"];
                                                                    $title_id = $employeeDetails["title_id"];
                                                                    $position = $employeeDetails["assignment_position"];
                                                                    $area = $employeeDetails["sub_assignment"];
                                                                    $degree = $employeeDetails["highest_degree"];
                                                                    $adjustment = $employeeDetails["experience_adjustment"];
                                                                }
                                                            }

                                                            // get timestamp
                                                            $timestamp = date("Y-m-d H:i:s");

                                                            ///////////////////////////////////////////
                                                            //
                                                            //  DEMOGRAPHICS
                                                            //
                                                            ///////////////////////////////////////////
                                                            if ($sync_demographics == 1)
                                                            {
                                                                $editDemographics = mysqli_prepare($conn, "UPDATE employees SET fname=?, lname=?, email=?, phone=?, birthday=?, gender=?, global=?, updated=? WHERE id=?");
                                                                mysqli_stmt_bind_param($editDemographics, "sssssiisi", $fname, $lname, $email, $phone, $DB_DOB, $DB_gender, $global, $timestamp, $employee_id);
                                                                if (mysqli_stmt_execute($editDemographics))
                                                                {
                                                                    // log demographics update
                                                                    // echo "Successfully updated the employee demographics for $lname, $fname (ID: $employee_id).<br>";

                                                                    // attempt to update employee address
                                                                    if (setEmployeeAddress($conn, $employee_id, $line1, $line2, $city, $state, $zip))
                                                                    {
                                                                        // log address update
                                                                        // echo "Successfully updated the address for $lname, $fname (ID: $employee_id).<br>";
                                                                    }

                                                                    // increment updates
                                                                    $updated++;
                                                                }
                                                            }
                                                            
                                                            ///////////////////////////////////////////
                                                            //
                                                            //  POSITION
                                                            //
                                                            ///////////////////////////////////////////
                                                            if ($sync_position == 1)
                                                            {
                                                                /*
                                                                $editStartEndDates = mysqli_prepare($conn, "UPDATE employees SET original_hire_date=?, original_end_date=?, most_recent_hire_date=?, most_recent_end_date=? WHERE id=?");
                                                                mysqli_stmt_bind_param($editStartEndDates, "ssssi", $original_start_date, $original_end_date, $most_recent_start_date, $most_recent_end_date, $employee_id);
                                                                if (mysqli_stmt_execute($editStartEndDates))
                                                                {
                                                                    // log start & end date updates
                                                                    echo "Successfully updated the original and most recent start and end dates for $lname, $fname (ID: $employee_id).<br>";
                                                                }
                                                                */

                                                                // check original start date
                                                                $checkOriginalStart = mysqli_prepare($conn, "SELECT id FROM employees WHERE id=? AND original_hire_date=?");
                                                                mysqli_stmt_bind_param($checkOriginalStart, "is", $employee_id, $original_start_date);
                                                                if (mysqli_stmt_execute($checkOriginalStart))
                                                                {
                                                                    $checkOriginalStartResult = mysqli_stmt_get_result($checkOriginalStart);
                                                                    if (mysqli_num_rows($checkOriginalStartResult) == 0) // dates do not match; queue change for approval
                                                                    {
                                                                        // queue original start date for approval
                                                                        $queueOriginalStart = mysqli_prepare($conn, "INSERT INTO sync_queue_employee_compensation (employee_id, period_id, field, value) VALUES (?, ?, 'original_hire_date', ?)");
                                                                        mysqli_stmt_bind_param($queueOriginalStart, "iis", $employee_id, $period_id, $original_start_date);
                                                                        if (mysqli_stmt_execute($queueOriginalStart)) { $queues++; }
                                                                    }
                                                                }

                                                                // only queue end date if set
                                                                if (isset($original_end_date) && $original_end_date != null && trim($original_end_date) <> "")
                                                                {
                                                                    // check original end date
                                                                    $checkOriginalEnd = mysqli_prepare($conn, "SELECT id FROM employees WHERE id=? AND original_end_date=?");
                                                                    mysqli_stmt_bind_param($checkOriginalEnd, "is", $employee_id, $original_end_date);
                                                                    if (mysqli_stmt_execute($checkOriginalEnd))
                                                                    {
                                                                        $checkOriginalEndResult = mysqli_stmt_get_result($checkOriginalEnd);
                                                                        if (mysqli_num_rows($checkOriginalEndResult) == 0) // dates do not match; queue change for approval
                                                                        {
                                                                            // queue original end date for approval
                                                                            $queueOriginalEnd = mysqli_prepare($conn, "INSERT INTO sync_queue_employee_compensation (employee_id, period_id, field, value) VALUES (?, ?, 'original_end_date', ?)");
                                                                            mysqli_stmt_bind_param($queueOriginalEnd, "iis", $employee_id, $period_id, $original_end_date);
                                                                            if (mysqli_stmt_execute($queueOriginalEnd)) { $queues++; }
                                                                        }
                                                                    }
                                                                }

                                                                // check most recent start date
                                                                $checkMostRecentStart = mysqli_prepare($conn, "SELECT id FROM employees WHERE id=? AND most_recent_hire_date=?");
                                                                mysqli_stmt_bind_param($checkMostRecentStart, "is", $employee_id, $most_recent_start_date);
                                                                if (mysqli_stmt_execute($checkMostRecentStart))
                                                                {
                                                                    $checkMostRecentStartResult = mysqli_stmt_get_result($checkMostRecentStart);
                                                                    if (mysqli_num_rows($checkMostRecentStartResult) == 0) // dates do not match; queue change for approval
                                                                    {
                                                                        // queue most recent start date for approval
                                                                        $queueMostRecentStart = mysqli_prepare($conn, "INSERT INTO sync_queue_employee_compensation (employee_id, period_id, field, value) VALUES (?, ?, 'most_recent_hire_date', ?)");
                                                                        mysqli_stmt_bind_param($queueMostRecentStart, "iis", $employee_id, $period_id, $most_recent_start_date);
                                                                        if (mysqli_stmt_execute($queueMostRecentStart)) { $queues++; }
                                                                    }
                                                                }

                                                                // only queue end date if set
                                                                if (isset($most_recent_end_date) && $most_recent_end_date != null && trim($most_recent_end_date) <> "")
                                                                {
                                                                    // check most recent end date
                                                                    $checkMostRecentEnd = mysqli_prepare($conn, "SELECT id FROM employees WHERE id=? AND most_recent_end_date=?");
                                                                    mysqli_stmt_bind_param($checkMostRecentEnd, "is", $employee_id, $most_recent_end_date);
                                                                    if (mysqli_stmt_execute($checkMostRecentEnd))
                                                                    {
                                                                        $checkMostRecentEndResult = mysqli_stmt_get_result($checkMostRecentEnd);
                                                                        if (mysqli_num_rows($checkMostRecentEndResult) == 0) // dates do not match; queue change for approval
                                                                        {
                                                                            // queue most recent end date for approval
                                                                            $queueMostRecentEnd = mysqli_prepare($conn, "INSERT INTO sync_queue_employee_compensation (employee_id, period_id, field, value) VALUES (?, ?, 'most_recent_end_date', ?)");
                                                                            mysqli_stmt_bind_param($queueMostRecentEnd, "iis", $employee_id, $period_id, $most_recent_end_date);
                                                                            if (mysqli_stmt_execute($queueMostRecentEnd)) { $queues++; }
                                                                        }
                                                                    }
                                                                }
                                                            }

                                                            ///////////////////////////////////////////
                                                            //
                                                            //  CONTRACT
                                                            //
                                                            ///////////////////////////////////////////
                                                            if ($sync_contract == 1)
                                                            {
                                                                // check field
                                                                $check = mysqli_prepare($conn, "SELECT id FROM employee_compensation WHERE employee_id=? AND period_id=? AND health_insurance=?");
                                                                mysqli_stmt_bind_param($check, "iii", $employee_id, $period_id, $DB_health);
                                                                if (mysqli_stmt_execute($check))
                                                                {
                                                                    $result = mysqli_stmt_get_result($check);
                                                                    if (mysqli_num_rows($result) == 0) // different; queue change for approval
                                                                    {
                                                                        // queue for approval
                                                                        $queue = mysqli_prepare($conn, "INSERT INTO sync_queue_employee_compensation (employee_id, period_id, field, value) VALUES (?, ?, 'health_insurance', ?)");
                                                                        mysqli_stmt_bind_param($queue, "iis", $employee_id, $period_id, $DB_health);
                                                                        if (mysqli_stmt_execute($queue)) { $queues++; }
                                                                    }
                                                                }

                                                                // check field
                                                                $check = mysqli_prepare($conn, "SELECT id FROM employee_compensation WHERE employee_id=? AND period_id=? AND dental_insurance=?");
                                                                mysqli_stmt_bind_param($check, "iii", $employee_id, $period_id, $DB_dental);
                                                                if (mysqli_stmt_execute($check))
                                                                {
                                                                    $result = mysqli_stmt_get_result($check);
                                                                    if (mysqli_num_rows($result) == 0) // different; queue change for approval
                                                                    {
                                                                        // queue for approval
                                                                        $queue = mysqli_prepare($conn, "INSERT INTO sync_queue_employee_compensation (employee_id, period_id, field, value) VALUES (?, ?, 'dental_insurance', ?)");
                                                                        mysqli_stmt_bind_param($queue, "iis", $employee_id, $period_id, $DB_dental);
                                                                        if (mysqli_stmt_execute($queue)) { $queues++; }
                                                                    }
                                                                }

                                                                // check field
                                                                $check = mysqli_prepare($conn, "SELECT id FROM employee_compensation WHERE employee_id=? AND period_id=? AND wrs_eligible=?");
                                                                mysqli_stmt_bind_param($check, "iii", $employee_id, $period_id, $DB_wrs);
                                                                if (mysqli_stmt_execute($check))
                                                                {
                                                                    $result = mysqli_stmt_get_result($check);
                                                                    if (mysqli_num_rows($result) == 0) // different; queue change for approval
                                                                    {
                                                                        // queue for approval
                                                                        $queue = mysqli_prepare($conn, "INSERT INTO sync_queue_employee_compensation (employee_id, period_id, field, value) VALUES (?, ?, 'wrs_eligible', ?)");
                                                                        mysqli_stmt_bind_param($queue, "iis", $employee_id, $period_id, $DB_wrs);
                                                                        if (mysqli_stmt_execute($queue)) { $queues++; }
                                                                    }
                                                                }

                                                                // check field
                                                                $check = mysqli_prepare($conn, "SELECT id FROM employee_compensation WHERE employee_id=? AND period_id=? AND yearly_rate=?");
                                                                mysqli_stmt_bind_param($check, "iid", $employee_id, $period_id, $DB_rate);
                                                                if (mysqli_stmt_execute($check))
                                                                {
                                                                    $result = mysqli_stmt_get_result($check);
                                                                    if (mysqli_num_rows($result) == 0) // different; queue change for approval
                                                                    {
                                                                        // queue for approval
                                                                        $queue = mysqli_prepare($conn, "INSERT INTO sync_queue_employee_compensation (employee_id, period_id, field, value) VALUES (?, ?, 'yearly_rate', ?)");
                                                                        mysqli_stmt_bind_param($queue, "iis", $employee_id, $period_id, $DB_rate);
                                                                        if (mysqli_stmt_execute($queue)) { $queues++; }
                                                                    }
                                                                }

                                                                // check field
                                                                $check = mysqli_prepare($conn, "SELECT id FROM employee_compensation WHERE employee_id=? AND period_id=? AND contract_days=?");
                                                                mysqli_stmt_bind_param($check, "iid", $employee_id, $period_id, $DB_days);
                                                                if (mysqli_stmt_execute($check))
                                                                {
                                                                    $result = mysqli_stmt_get_result($check);
                                                                    if (mysqli_num_rows($result) == 0) // different; queue change for approval
                                                                    {
                                                                        // queue for approval
                                                                        $queue = mysqli_prepare($conn, "INSERT INTO sync_queue_employee_compensation (employee_id, period_id, field, value) VALUES (?, ?, 'contract_days', ?)");
                                                                        mysqli_stmt_bind_param($queue, "iis", $employee_id, $period_id, $DB_days);
                                                                        if (mysqli_stmt_execute($queue)) { $queues++; }
                                                                    }
                                                                }

                                                                // check field
                                                                $check = mysqli_prepare($conn, "SELECT id FROM employee_compensation WHERE employee_id=? AND period_id=? AND number_of_pays=?");
                                                                mysqli_stmt_bind_param($check, "iii", $employee_id, $period_id, $num_of_pays);
                                                                if (mysqli_stmt_execute($check))
                                                                {
                                                                    $result = mysqli_stmt_get_result($check);
                                                                    if (mysqli_num_rows($result) == 0) // different; queue change for approval
                                                                    {
                                                                        // queue for approval
                                                                        $queue = mysqli_prepare($conn, "INSERT INTO sync_queue_employee_compensation (employee_id, period_id, field, value) VALUES (?, ?, 'number_of_pays', ?)");
                                                                        mysqli_stmt_bind_param($queue, "iis", $employee_id, $period_id, $num_of_pays);
                                                                        if (mysqli_stmt_execute($queue)) { $queues++; }
                                                                    }
                                                                }

                                                                // check field
                                                                $check = mysqli_prepare($conn, "SELECT id FROM employee_compensation WHERE employee_id=? AND period_id=? AND calendar_type=?");
                                                                mysqli_stmt_bind_param($check, "iii", $employee_id, $period_id, $DB_calendar_type);
                                                                if (mysqli_stmt_execute($check))
                                                                {
                                                                    $result = mysqli_stmt_get_result($check);
                                                                    if (mysqli_num_rows($result) == 0) // different; queue change for approval
                                                                    {
                                                                        // queue for approval
                                                                        $queue = mysqli_prepare($conn, "INSERT INTO sync_queue_employee_compensation (employee_id, period_id, field, value) VALUES (?, ?, 'calendar_type', ?)");
                                                                        mysqli_stmt_bind_param($queue, "iis", $employee_id, $period_id, $DB_calendar_type);
                                                                        if (mysqli_stmt_execute($queue)) { $queues++; }
                                                                    }
                                                                }

                                                                // check field
                                                                $check = mysqli_prepare($conn, "SELECT id FROM employee_compensation WHERE employee_id=? AND period_id=? AND contract_start_date=?");
                                                                mysqli_stmt_bind_param($check, "iis", $employee_id, $period_id, $contract_start_date);
                                                                if (mysqli_stmt_execute($check))
                                                                {
                                                                    $result = mysqli_stmt_get_result($check);
                                                                    if (mysqli_num_rows($result) == 0) // different; queue change for approval
                                                                    {
                                                                        // queue for approval
                                                                        $queue = mysqli_prepare($conn, "INSERT INTO sync_queue_employee_compensation (employee_id, period_id, field, value) VALUES (?, ?, 'contract_start_date', ?)");
                                                                        mysqli_stmt_bind_param($queue, "iis", $employee_id, $period_id, $contract_start_date);
                                                                        if (mysqli_stmt_execute($queue)) { $queues++; }
                                                                    }
                                                                }

                                                                // check field
                                                                $check = mysqli_prepare($conn, "SELECT id FROM employee_compensation WHERE employee_id=? AND period_id=? AND contract_end_date=?");
                                                                mysqli_stmt_bind_param($check, "iis", $employee_id, $period_id, $contract_end_date);
                                                                if (mysqli_stmt_execute($check))
                                                                {
                                                                    $result = mysqli_stmt_get_result($check);
                                                                    if (mysqli_num_rows($result) == 0) // different; queue change for approval
                                                                    {
                                                                        // queue for approval
                                                                        $queue = mysqli_prepare($conn, "INSERT INTO sync_queue_employee_compensation (employee_id, period_id, field, value) VALUES (?, ?, 'contract_end_date', ?)");
                                                                        mysqli_stmt_bind_param($queue, "iis", $employee_id, $period_id, $contract_end_date);
                                                                        if (mysqli_stmt_execute($queue)) { $queues++; }
                                                                    }
                                                                }

                                                                // check field
                                                                $check = mysqli_prepare($conn, "SELECT id FROM employee_compensation WHERE employee_id=? AND period_id=? AND active=?");
                                                                mysqli_stmt_bind_param($check, "iii", $employee_id, $period_id, $DB_status);
                                                                if (mysqli_stmt_execute($check))
                                                                {
                                                                    $result = mysqli_stmt_get_result($check);
                                                                    if (mysqli_num_rows($result) == 0) // different; queue change for approval
                                                                    {
                                                                        // queue for approval
                                                                        $queue = mysqli_prepare($conn, "INSERT INTO sync_queue_employee_compensation (employee_id, period_id, field, value) VALUES (?, ?, 'active', ?)");
                                                                        mysqli_stmt_bind_param($queue, "iis", $employee_id, $period_id, $DB_status);
                                                                        if (mysqli_stmt_execute($queue)) { $queues++; }
                                                                    }
                                                                }
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }

                                        // close the file
                                        fclose($filestream);

                                        // delete the file
                                        ssh2_sftp_unlink($sftp, "/./".SFTP_DIR_USER."/uploads/".$file);

                                        // build log message
                                        $total_successes = $inserted + $updated;
                                        $message = "Successfully uploaded $total_successes employees via automation. Queued $queues changes for approval. ";
                                        if ($errors > 0) { $message .= "Failed to upload $errors employees. "; }
                                    }
                                    else { $message = "Failed to upload employees via automation. Failed to open the file."; }
                                }
                                else { $message = "Failed to upload employees via automation. Failed to find a file labelled 'BAP Nightly Export Final.csv'."; }
                            }
                        }
                        else { $message = "Failed to upload employees via automation. No files found in /uploads directory."; }
                    }
                    else { $message = "Failed to upload employees via automation. Failed to open the SFTP directory."; }
                }
                else { $message = "Failed to upload employees via automation. Failed to open the SFTP stream."; }
            }
            else { $message = "Failed to upload employees via automation. Failed to authorize the SFTP connection."; }
        }
        else { $message = "Failed to upload employees via automation. Failed to connect to the SFTP server."; }

        // log upload
        $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (-2, ?)");
        mysqli_stmt_bind_param($log, "s", $message);
        mysqli_stmt_execute($log);

        // disconnect from the database
        mysqli_close($conn);
    }
    catch (Exception $e)
    {

    }
?>