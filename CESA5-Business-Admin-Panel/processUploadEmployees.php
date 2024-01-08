<?php
    include("header.php");

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1) 
    { 
        // get additional settings
        include("getSettings.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "ADD_EMPLOYEES"))
        {
            // store the active period locally
            $period = $GLOBAL_SETTINGS["active_period"];

            ?>
                <div class="row text-center">
                    <h1 class="upload-status-header">Employees Upload Status</h1>
                </div>

                <div class="row text-center">
                    <div class="col-2"></div>
                    <div class="col-8 upload-status-report">
                    <?php
                        if (isset($_FILES["fileToUpload"])) 
                        {
                            // get and open the file
                            $file = $_FILES['fileToUpload']['tmp_name'];
                            $file_type = $_FILES["fileToUpload"]["type"];

                            // verify the file is set and it is a .csv file
                            if (isset($file) && (isset($file_type) && $file_type == "text/csv"))
                            {
                                // initialize variables
                                $updated = $inserted = $errors = 0;

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

                                // open the file for reading
                                $handle = fopen($file, "r");

                                while ($data = fgetcsv($handle, 1000, ",", '"'))
                                {
                                    if (isset($data[0]) && ($data[0] != "Employee Information" && $data[0] != "Employee ID" && $data[0] != "ID")) // skip the first two rows by looking at cell data
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
                                        if (isset($data[7])) { $street = clean_data($data[7]); } else { $street = null; } // street
                                        if (isset($data[8])) { $city = clean_data($data[8]); } else { $city = null; } // city
                                        if (isset($data[9])) { $state = clean_data($data[9]); } else { $state = null; } // state
                                        if (isset($data[10])) { $zip = clean_data($data[10]); } else { $zip = null; } // zip

                                        // get employee benefits and compensation from CSV
                                        if (isset($data[11])) { $hire_date = clean_data($data[11]); } else { $hire_date = null; } // hire date
                                        if (isset($data[12])) { $end_date = clean_data($data[12]); } else { $end_date = null; } // end date
                                        if (isset($data[13])) { $contract_start_date = clean_data($data[13]); } else { $contract_start_date = null; } // contract start date
                                        if (isset($data[14])) { $contract_end_date = clean_data($data[14]); } else { $contract_end_date = null; } // contract end date
                                        if (isset($data[15])) { $contract_type = clean_data($data[15]); } else { $contract_type = null; } // contract type
                                        if (isset($data[16])) { $days = clean_data($data[16]); } else { $days = null; } // contract days
                                        if (isset($data[17])) { $calendar_type = clean_data($data[17]); } else { $calendar_type = null; } // calendar type
                                        if (isset($data[18])) { $rate = clean_data($data[18]); } else { $rate = null; } // yearly rate
                                        if (isset($data[19])) { $num_of_pays = clean_data($data[19]); } else { $num_of_pays = null; } // number of pays
                                        if (isset($data[20])) { $health = clean_data($data[20]); } else { $health = null; } // health coverage (None, Single, Family)
                                        if (isset($data[21])) { $dental = clean_data($data[21]); } else { $dental = null; } // dental coverage (None, Single, Family)
                                        if (isset($data[22])) { $wrs = clean_data($data[22]); } else { $wrs = null; } // WRS eligibility (Yes, No)

                                        // get employee role from CSV
                                        if (isset($data[23])) { $title = clean_data($data[23]); } else { $title = null; } // title
                                        if (isset($data[24])) { $department = clean_data($data[24]); } else { $department = null; } // primary department
                                        if (isset($data[25])) { $position = clean_data($data[25]); } else { $position = null; } // DPI position
                                        if (isset($data[26])) { $area = clean_data($data[26]); } else { $area = null; } // DPI area
                                        if (isset($data[27])) { $experience = clean_data($data[27]); } else { $experience = null; } // years of total experience
                                        if (isset($data[28])) { $degree = clean_data($data[28]); } else { $degree = null; } // highest degree
                                        
                                        // get employee BAP account information from CSV
                                        if (isset($data[29])) { $status = clean_data($data[29]); } else { $status = null; } // employee status (Active, Inactive)
                                        if (isset($data[30])) { $role = clean_data($data[30]); } else { $role = null; } // employee role (Admin, Director, Employee, Maintenance)

                                        // verify and convert data from upload to database values if necessary
                                        if ($status == "Inactive") { $DB_status = 0; } else if ($status == "Active") { $DB_status = 1; } else { $DB_status = 0; }
                                        if (is_numeric($days)) { $DB_days = $days; } else { $DB_days = 0; }
                                        if (!is_numeric($experience) || $experience == null) { $experience = 0; }
                                        if (is_numeric($rate)) { $DB_rate = $rate; } 
                                        else 
                                        { 
                                            // attempt to remove common characters from string
                                            $clean_rate = str_replace("$", "", $rate); // remove $ if found
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

                                        // set marital status to 0 (removed from upload in V3.23.15)
                                        $DB_marital_status = 0;

                                        // convert contract type to integer
                                        $DB_contract_type = 0;
                                        if ($contract_type == "Regular") { $DB_contract_type = 0; }
                                        else if ($contract_type == "Limited") { $DB_contract_type = 1; }
                                        else if ($contract_type == "At-Will") { $DB_contract_type = 2; }
                                        else if ($contract_type == "Section 118") { $DB_contract_type = 3; }
                                        else if ($contract_type == "Hourly") { $DB_contract_type = 4; }

                                        // convert contract type to integer
                                        $DB_calendar_type = 0;
                                        if ($calendar_type == "N/A") { $DB_calendar_type = 0; }
                                        else if ($calendar_type == "Week") { $DB_calendar_type = 1; }
                                        else if ($calendar_type == "Month") { $DB_calendar_type = 2; }

                                        // get title ID from the list
                                        $title_id = getTitleID($conn, $title);

                                        // get role ID from the list
                                        $role_id = getRoleID($conn, $role);

                                        // if employee exists, update; otherwise, add new employee
                                        if (checkExistingEmployee($conn, $employee_id)) // employee exists; edit employee
                                        { 
                                            // get current employee settings for global
                                            $global = 0; // assume 0
                                            $getCurrentSettings = mysqli_prepare($conn, "SELECT global FROM employees WHERE id=?");
                                            mysqli_stmt_bind_param($getCurrentSettings, "i", $employee_id);
                                            if (mysqli_stmt_execute($getCurrentSettings))
                                            {
                                                $getCurrentSettingsResult = mysqli_stmt_get_result($getCurrentSettings);
                                                if (mysqli_num_rows($getCurrentSettingsResult) > 0)
                                                {
                                                    $current_settings = mysqli_fetch_array($getCurrentSettingsResult);
                                                    $global = $current_settings["global"];
                                                }
                                            }

                                            // edit the existing employee
                                            editEmployee($conn, $period, $employee_id, $fname, $lname, $email, $phone, $DB_DOB, $DB_gender, $DB_marital_status, $street, $city, $state, $zip, $title_id, $department, null, $hire_date, $end_date, $DB_rate, $DB_days, $DB_contract_type, $contract_start_date, $contract_end_date, $DB_calendar_type, $num_of_pays, $DB_health, $DB_dental, $DB_wrs, $position, $area, $experience, $degree, $role_id, $global, $DB_status); 
                                        }
                                        // add the new employee since the employee does not exist
                                        else { addEmployee($conn, $period, $employee_id, $fname, $lname, $email, $phone, $DB_DOB, $DB_gender, $DB_marital_status, $street, $city, $state, $zip, $title_id, $department, null, $hire_date, $end_date, $DB_rate, $DB_days, $DB_contract_type, $contract_start_date, $contract_end_date, $DB_calendar_type, $num_of_pays, $DB_health, $DB_dental, $DB_wrs, $position, $area, $experience, $degree, $role_id, 0, $DB_status); }
                                    }
                                }

                                echo "<i class=\"fa-solid fa-check\"></i> Upload complete!";

                                // log upload
                                $total_successes = $inserted + $updated;
                                $message = "Successfully uploaded $total_successes employees ($inserted inserts; $updated updates). ";
                                if ($errors > 0) { $message .= "Failed to upload $errors employees. "; }
                                $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                                mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                                mysqli_stmt_execute($log);
                            }
                            else { echo "ERROR! You must select a .csv file to upload."; }
                        }   
                        else { echo "ERROR! No upload file was found. Please select a file to upload and try again. "; }
                    ?>
                    </div>
                    <div class="col-2"></div>
                </div>

                <div class="row text-center mt-3">
                    <div class="col-5"></div>
                    <div class="col-2"><button class="btn btn-primary w-100" onclick="goToEmployees();">Return To Employees</button></div>
                    <div class="col-5"></div>
                </div>

                <script>function goToEmployees() { window.location.href = "employees_list.php"; }</script>
            <?php
        }
        else { denyAccess(); }

        // disconnect from the database
        mysqli_close($conn);
    }
    else { goToLogin(); }
?>