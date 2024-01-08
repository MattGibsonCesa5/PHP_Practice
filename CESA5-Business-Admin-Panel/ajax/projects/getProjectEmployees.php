<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // initialize array to store project employees
        $employees = [];

        // get the required files
        include("../../includes/config.php");
        include("../../includes/functions.php");
        include("../../getSettings.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "VIEW_PROJECT_BUDGETS_ALL") || checkUserPermission($conn, "VIEW_PROJECT_BUDGETS_ASSIGNED"))
        {
            // store user permissions for budgeting projects locally
            $can_user_budget_all = checkUserPermission($conn, "BUDGET_PROJECTS_ALL"); 
            $can_user_budget_assigned = checkUserPermission($conn, "BUDGET_PROJECTS_ASSIGNED");

            // get the parameters from POST
            if (isset($_POST["code"]) && $_POST["code"] <> "") { $code = $_POST["code"]; } else { $code = null; }
            if (isset($_POST["period"]) && $_POST["period"] <> "") { $period = $_POST["period"]; } else { $period = null; }

            if ($period != null&& $period_id = getPeriodID($conn, $period)) // verify the period exists; if it exists, store the period ID
            {
                // get the period's details
                $periodDetails = getPeriodDetails($conn, $period_id);

                if (($code != null && verifyProject($conn, $code)) && verifyUserCanViewProject($conn, $_SESSION["id"], $code)) // verify the project exists and user is assigned to it
                {
                    // get the project's location type
                    $staff_location = 0;
                    $getCodes = mysqli_prepare($conn, "SELECT staff_location FROM projects WHERE code=?");
                    mysqli_stmt_bind_param($getCodes, "s", $code);
                    if (mysqli_stmt_execute($getCodes))
                    {
                        $getCodesResult = mysqli_stmt_get_result($getCodes);
                        if (mysqli_num_rows($getCodesResult) > 0)
                        {
                            $codes = mysqli_fetch_array($getCodesResult);
                            $staff_location = $codes["staff_location"];
                        }
                    }

                    $getEmployees = mysqli_prepare($conn, "SELECT * FROM project_employees WHERE project_code=? AND period_id=?");
                    mysqli_stmt_bind_param($getEmployees, "si", $code, $period_id);
                    if (mysqli_stmt_execute($getEmployees))
                    {
                        $getEmployeesResult = mysqli_stmt_get_result($getEmployees);
                        while ($employee = mysqli_fetch_array($getEmployeesResult))
                        {
                            // store project employee data locally
                            $record = $employee["id"];
                            $employee_id = $employee["employee_id"];
                            $project_days = $employee["project_days"];
                            $fund_code = $employee["fund_code"];
                            $location_code = $employee["location_code"];
                            $object_code = $employee["object_code"];
                            $function_code = $employee["function_code"];
                            $location_id = $employee["location_id"];

                            // get additional employee details based on the employee ID
                            $getEmployeeDetails = mysqli_prepare($conn, "SELECT * FROM employees WHERE id=?");
                            mysqli_stmt_bind_param($getEmployeeDetails, "i", $employee_id);
                            if (mysqli_stmt_execute($getEmployeeDetails))
                            {
                                $getEmployeeDetailsResult = mysqli_stmt_get_result($getEmployeeDetails);
                                if (mysqli_num_rows($getEmployeeDetailsResult) > 0) // employee details found
                                {
                                    // EMPLOYEE INFORMATION
                                    $employeeDetails = mysqli_fetch_array($getEmployeeDetailsResult);
                                    $fname = $employeeDetails["fname"];
                                    $lname = $employeeDetails["lname"];
                                    $name = $lname . ", " . $fname;

                                    // get the employee's compensation
                                    $status = 1; // initialize employee active status to active (1)
                                    $health = $dental = $wrs = $rate = $contract_days = 0; // initialize benefits and compensation to 0
                                    $getCompensation = mysqli_prepare($conn, "SELECT * FROM employee_compensation WHERE employee_id=? AND period_id=?");
                                    mysqli_stmt_bind_param($getCompensation, "ii", $employee_id, $period_id);
                                    if (mysqli_stmt_execute($getCompensation))
                                    {
                                        $getCompensationResult = mysqli_stmt_get_result($getCompensation);
                                        if (mysqli_num_rows($getCompensationResult) > 0) // benefits and compensation found
                                        {
                                            // store the employee's benefits and compensation locally
                                            $employeeCompensation = mysqli_fetch_array($getCompensationResult);
                                            $health = $employeeCompensation["health_insurance"];
                                            $dental = $employeeCompensation["dental_insurance"];
                                            $wrs = $employeeCompensation["wrs_eligible"];
                                            $rate = $employeeCompensation["yearly_rate"];
                                            $contract_days = $employeeCompensation["contract_days"];
                                            $status = $employeeCompensation["active"];
                                        }
                                    }

                                    // initialize the array to store employee data
                                    $temp = [];
                                    $temp["id"] = $employeeDetails["id"];

                                    // build the name column
                                    $name_div = "<div class='my-1'>
                                        <span class='text-nowrap float-start'>$name</span>";
                                        if ($status == 1) { $name_div .= "<div class='active-div text-center px-3 py-1 float-end'>Active</div>"; }
                                        else { $name_div .= "<div class='inactive-div text-center px-3 py-1 float-end'>Inactive</div>"; } 
                                    $name_div .= "</div>";
                                    $temp["name"] = $name_div;

                                    // calculate the employees daily rate
                                    if ($contract_days != 0) { $daily_rate = $rate / $contract_days; }
                                    else { $daily_rate = 0; }

                                    // calculate the percentage of benefits based on days
                                    if ($contract_days >= $GLOBAL_SETTINGS["FTE_days"]) { $FTE_Benefits_Percentage = 1; }
                                    else { $FTE_Benefits_Percentage = ($contract_days / $GLOBAL_SETTINGS["FTE_days"]); }

                                    // if percentage is <= 50%; set to 0
                                    if ($FTE_Benefits_Percentage < 0.5) { $FTE_Benefits_Percentage = 0; }

                                    $temp["FTE"] = $FTE_Benefits_Percentage;

                                    // build the benefits display
                                    $benefits = "";
                                    $health_benefits = "<b>Health:</b> ";
                                    $dental_benefits = "<b>Dental:</b> ";
                                    $wrs_benefits = "<b>WRS:</b> ";
                                    if ($health == 0) { $health_benefits .= "None"; } else if ($health == 1) { $health_benefits .= "Family"; } else if ($health == 2) { $health_benefits .= "Single"; } else { $health_benefits = "<span class='missing-field'>Unknown</span>"; }
                                    if ($dental == 0) { $dental_benefits .= "None"; } else if ($dental == 1) { $dental_benefits .= "Family"; } else if ($dental == 2) { $dental_benefits .= "Single"; } else { $dental_benefits = "<span class='missing-field'>Unknown</span>"; }
                                    if ($wrs == 0) { $wrs_benefits .= "No"; } else if ($wrs == 1) { $wrs_benefits .= "Yes"; } else { $wrs_benefits = "<span class='missing-field'>Unknown</span>"; }
                                    $benefits .= $health_benefits . "<br>" . $dental_benefits . "<br>" . $wrs_benefits;
                                    $temp["benefits"] = $benefits;

                                    $temp["rate"] = printDollar($daily_rate);
                                    $temp["contract_days"] = $contract_days;
                                    $temp["project_days"] = $project_days;

                                    if (isset($fund_code) && $fund_code <> "") { $temp["fund_code"] = $fund_code." E"; } else { $temp["fund_code"] = "<span class='missing-field'>Missing</span>"; }
                                    if (isset($location_code) && $location_code <> "") { $temp["location_code"] = $location_code; } else { $temp["location_code"] = "<span class='missing-field'>Missing</span>"; }
                                    if (isset($object_code) && $object_code <> "") { $temp["object_code"] = $object_code; } else { $temp["object_code"] = "<span class='missing-field'>Missing</span>"; }
                                    if (isset($function_code) && $function_code <> "") { $temp["function_code"] = $function_code; } else { $temp["function_code"] = "<span class='missing-field'>Missing</span>"; }
                                    $temp["project_code"] = $code;

                                    // staff location
                                    $staff_location_display = "";
                                    if ($staff_location == 1) 
                                    {
                                        // get customer name
                                        $location_name = "";
                                        $getLocation = mysqli_prepare($conn, "SELECT name FROM customers WHERE id=?");
                                        mysqli_stmt_bind_param($getLocation, "i", $location_id);
                                        if (mysqli_stmt_execute($getLocation))
                                        {
                                            $getLocationResult = mysqli_stmt_get_result($getLocation);
                                            if (mysqli_num_rows($getLocationResult) > 0)
                                            {
                                                $location_name = mysqli_fetch_array($getLocationResult)["name"];
                                            }
                                        }
                                        $staff_location_display = $location_name;
                                    }
                                    else if ($staff_location == 2)
                                    {
                                        // get classroom name
                                        $location_name = "";
                                        $getLocation = mysqli_prepare($conn, "SELECT name, label FROM caseload_classrooms WHERE id=?");
                                        mysqli_stmt_bind_param($getLocation, "i", $location_id);
                                        if (mysqli_stmt_execute($getLocation))
                                        {
                                            $getLocationResult = mysqli_stmt_get_result($getLocation);
                                            if (mysqli_num_rows($getLocationResult) > 0)
                                            {
                                                // store classroom details locally
                                                $location_details =  mysqli_fetch_array($getLocationResult);
                                                $location_name = $location_details["name"];
                                                $location_label = $location_details["label"];

                                                // set name to label if one is provided
                                                if (isset($location_label) && trim($location_label) <> "") { $location_name = $location_label; }
                                            }
                                        }
                                        $staff_location_display = $location_name;
                                    }
                                    $temp["staff_location"] = $staff_location_display;

                                    // EMPLOYEE COSTS
                                    $getRates = mysqli_prepare($conn, "SELECT * FROM global_expenses WHERE period_id=?");
                                    mysqli_stmt_bind_param($getRates, "i", $period_id);
                                    if (mysqli_stmt_execute($getRates))
                                    {
                                        $getRatesResult = mysqli_stmt_get_result($getRates);
                                        if (mysqli_num_rows($getRatesResult) > 0) // rates for current period exist
                                        {
                                            $rates = mysqli_fetch_array($getRatesResult);

                                            $project_salary = $daily_rate * $project_days;
                                            $temp["project_salary"] = printDollar($project_salary);

                                            $FICA_Cost = $project_salary * $rates["FICA"];
                                            $temp["FICA_Cost"] = printDollar($FICA_Cost);

                                            if ($wrs == 1) { $WRS_Cost = $project_salary * $rates["wrs_rate"]; }
                                            else { $WRS_Cost = 0; }
                                            $temp["WRS_Cost"] = printDollar($WRS_Cost);

                                            if ($contract_days != 0)
                                            {
                                                if ($health == 1) { $Health_Cost = ($rates["health_family"] * $FTE_Benefits_Percentage * ($project_days / $contract_days)); }
                                                else if ($health == 2) { $Health_Cost = ($rates["health_single"] * $FTE_Benefits_Percentage * ($project_days / $contract_days)); }
                                                else { $Health_Cost = 0; }
                                                $temp["Health_Cost"] = printDollar($Health_Cost);
                                            }
                                            else 
                                            { 
                                                $temp["Health_Cost"] = "$0.00";
                                                $Health_Cost = 0; 
                                            }

                                            if ($contract_days != 0)
                                            {
                                                if ($dental == 1) { $Dental_Cost = ($rates["dental_family"] * $FTE_Benefits_Percentage * ($project_days / $contract_days)); }
                                                else if ($dental == 2) { $Dental_Cost = ($rates["dental_single"] * $FTE_Benefits_Percentage * ($project_days / $contract_days)); }
                                                else { $Dental_Cost = 0; }
                                                $temp["Dental_Cost"] = printDollar($Dental_Cost);
                                            }
                                            else 
                                            { 
                                                $temp["Dental_Cost"] = "$0.00";
                                                $Dental_Cost = 0; 
                                            }

                                            if ($contract_days != 0)
                                            {
                                                $LTD_Cost = ($project_salary / 100) * ($rates["LTD"] * $FTE_Benefits_Percentage * ($project_days / $contract_days));
                                                $temp["LTD_Cost"] = printDollar($LTD_Cost);
                                            }
                                            else 
                                            { 
                                                $temp["LTD_Cost"] = "$0.00"; 
                                                $LTD_Cost = 0;
                                            }

                                            if ($contract_days != 0)
                                            {
                                                $Life_Cost = (($project_salary / 1000) * ($rates["life"] * 12 * ($project_days / $contract_days)) * 0.2);
                                                $temp["Life_Cost"] = printDollar($Life_Cost);
                                            }
                                            else 
                                            { 
                                                $temp["Life_Cost"] = "$0.00"; 
                                                $Life_Cost = 0;
                                            }

                                            $project_benefits = $FICA_Cost + $WRS_Cost + $Health_Cost + $Dental_Cost + $LTD_Cost + $Life_Cost;
                                            $temp["project_benefits"] = printDollar($project_benefits);

                                            $project_compensation = $project_salary + $project_benefits;
                                            $temp["project_compensation"] = printDollar($project_compensation);

                                            // calculate the daily cost
                                            $dailyCost = 0;
                                            if ($project_days > 0) { 
                                                $dailyCost = ($project_compensation / $project_days);
                                            }
                                            $temp["daily_cost"] = printDollar($dailyCost);
                                        }
                                        else // no rates for active period exist; default to 0
                                        { 
                                            $project_salary = $daily_rate * $project_days;
                                            $temp["project_salary"] = printDollar($project_salary);

                                            $FICA_Cost = 0;
                                            $temp["FICA_Cost"] = printDollar($FICA_Cost);

                                            $WRS_Cost = 0;
                                            $temp["WRS_Cost"] = printDollar($WRS_Cost);

                                            $Health_Cost = 0;
                                            $temp["Health_Cost"] = printDollar($Health_Cost);

                                            $Dental_Cost = 0;
                                            $temp["Dental_Cost"] = printDollar($Dental_Cost);

                                            $LTD_Cost = 0;
                                            $temp["LTD_Cost"] = printDollar($LTD_Cost);

                                            $Life_Cost = 0;
                                            $temp["Life_Cost"] = printDollar($Life_Cost);

                                            $project_benefits = $FICA_Cost + $WRS_Cost + $Health_Cost + $Dental_Cost + $LTD_Cost + $Life_Cost;
                                            $temp["project_benefits"] = printDollar($project_benefits);

                                            $project_compensation = $project_salary + $project_benefits;
                                            $temp["project_compensation"] = printDollar($project_compensation);

                                            $temp["daily_cost"] = printDollar(0);
                                        }
                                    }

                                    // build the actions column
                                    $actions = "";
                                    if (($can_user_budget_all === true || $can_user_budget_assigned === true) && $periodDetails["editable"] == 1) 
                                    { 
                                        $actions .= "<div class='d-flex justify-content-end'>
                                            <!-- Edit Project Employee -->
                                            <button class='btn btn-primary btn-sm mx-1' type='button' onclick='getEditProjectEmployeeModal(".$employee_id.", \"".$code."\", ".$record.");'>
                                                <i class='fa-solid fa-pencil'></i>
                                            </button>

                                            <!-- Remove Project Employee -->
                                            <button class='btn btn-danger btn-sm mx-1' type='button' onclick='getRemoveEmployeeFromProjectModal(".$employee_id.", \"".$code."\", ".$record.");'>
                                                <i class='fa-solid fa-trash-can'></i>
                                            </button>
                                        </div>"; 
                                    }
                                    $temp["actions"] = $actions;

                                    $employees[] = $temp;
                                }
                            }
                        }
                    }
                    
                    // get test employees that are in the project
                    $getTestEmployees = mysqli_prepare($conn, "SELECT * FROM project_employees_misc WHERE project_code=? AND period_id=?");
                    mysqli_stmt_bind_param($getTestEmployees, "si", $code, $period_id);
                    if (mysqli_stmt_execute($getTestEmployees))
                    {
                        $getTestEmployeesResults = mysqli_stmt_get_result($getTestEmployees);
                        if (mysqli_num_rows($getTestEmployeesResults) > 0) // test employees found
                        {
                            // for each test employee, build row and calculate expenses to be displayed
                            while ($test_employee = mysqli_fetch_array($getTestEmployeesResults))
                            {
                                // store the test employees data locally
                                $auto_id = $test_employee["id"];
                                $employee_id = $test_employee["employee_id"];
                                $label = $test_employee["employee_label"];
                                $yearly_rate = $test_employee["yearly_rate"];
                                $days = $test_employee["project_days"];
                                $health = $test_employee["health_insurance"];
                                $dental = $test_employee["dental_insurance"];
                                $wrs = $test_employee["wrs_eligible"];

                                // initialize and begin building array of test employee data
                                $temp = [];
                                $temp["id"] = "<i class='fa-solid fa-clipboard-user'></i> $employee_id";
                                $temp["name"] = $label;

                                // calculate the employees daily rate
                                if ($days != 0) { $daily_rate = $yearly_rate / $days; }
                                else { $daily_rate = 0; }

                                // calculate the percentage of benefits based on days
                                if ($days >= $GLOBAL_SETTINGS["FTE_days"]) { $FTE_Benefits_Percentage = 1; }
                                else { $FTE_Benefits_Percentage = ($days / $GLOBAL_SETTINGS["FTE_days"]); }

                                // if percentage is <= 50%; set to 0
                                if ($FTE_Benefits_Percentage < 0.5) { $FTE_Benefits_Percentage = 0; }

                                $temp["FTE"] = $FTE_Benefits_Percentage;

                                // build the benefits display
                                $benefits = "";
                                $health_benefits = "<b>Health:</b> ";
                                $dental_benefits = "<b>Dental:</b> ";
                                $wrs_benefits = "<b>WRS:</b> ";
                                if ($health == 0) { $health_benefits .= "None"; } else if ($health == 1) { $health_benefits .= "Family"; } else if ($health == 2) { $health_benefits .= "Single"; } else { $health_benefits = "<span class='missing-field'>Unknown</span>"; }
                                if ($dental == 0) { $dental_benefits .= "None"; } else if ($dental == 1) { $dental_benefits .= "Family"; } else if ($dental == 2) { $dental_benefits .= "Single"; } else { $dental_benefits = "<span class='missing-field'>Unknown</span>"; }
                                if ($wrs == 0) { $wrs_benefits .= "No"; } else if ($wrs == 1) { $wrs_benefits .= "Yes"; } else { $wrs_benefits = "<span class='missing-field'>Unknown</span>"; }
                                $benefits .= $health_benefits . "<br>" . $dental_benefits . "<br>" . $wrs_benefits;
                                $temp["benefits"] = $benefits;

                                $temp["rate"] = printDollar($daily_rate);
                                $temp["contract_days"] = $days;
                                $temp["project_days"] = $days;

                                $temp["fund_code"] = "N/A";
                                $temp["location_code"] = "N/A";
                                $temp["object_code"] = "N/A";
                                $temp["function_code"] = "N/A";
                                $temp["project_code"] = $code;
                                $temp["staff_location"] = "";

                                // EMPLOYEE COSTS
                                $getRates = mysqli_prepare($conn, "SELECT * FROM global_expenses WHERE period_id=?");
                                mysqli_stmt_bind_param($getRates, "i", $period_id);
                                if (mysqli_stmt_execute($getRates))
                                {
                                    $getRatesResult = mysqli_stmt_get_result($getRates);
                                    if (mysqli_num_rows($getRatesResult) > 0) // rates for current period exist
                                    {
                                        $rates = mysqli_fetch_array($getRatesResult);

                                        $project_salary = $daily_rate * $days;
                                        $temp["project_salary"] = printDollar($project_salary);

                                        $FICA_Cost = $project_salary * $rates["FICA"];
                                        $temp["FICA_Cost"] = printDollar($FICA_Cost);

                                        if ($wrs == 1) { $WRS_Cost = $project_salary * $rates["wrs_rate"]; }
                                        else { $WRS_Cost = 0; }
                                        $temp["WRS_Cost"] = printDollar($WRS_Cost);

                                        if ($contract_days != 0)
                                        {
                                            if ($health == 1) { $Health_Cost = ($rates["health_family"] * $FTE_Benefits_Percentage * ($days / $days)); }
                                            else if ($health == 2) { $Health_Cost = ($rates["health_single"] * $FTE_Benefits_Percentage * ($days / $days)); }
                                            else { $Health_Cost = 0; }
                                            $temp["Health_Cost"] = printDollar($Health_Cost);
                                        }
                                        else { $temp["Health_Cost"] = "$0.00"; }

                                        if ($contract_days != 0)
                                        {
                                            if ($dental == 1) { $Dental_Cost = ($rates["dental_family"] * $FTE_Benefits_Percentage * ($days / $days)); }
                                            else if ($dental == 2) { $Dental_Cost = ($rates["dental_single"] * $FTE_Benefits_Percentage * ($days / $days)); }
                                            else { $Dental_Cost = 0; }
                                            $temp["Dental_Cost"] = printDollar($Dental_Cost);
                                        }
                                        else { $temp["Dental_Cost"] = "$0.00"; }

                                        if ($contract_days != 0)
                                        {
                                            $LTD_Cost = ($project_salary / 100) * ($rates["LTD"] * $FTE_Benefits_Percentage * ($days / $days));
                                            $temp["LTD_Cost"] = printDollar($LTD_Cost);
                                        }
                                        else { $temp["LTD_Cost"] = "$0.00"; }

                                        if ($contract_days != 0)
                                        {
                                            $Life_Cost = (($project_salary / 1000) * ($rates["life"] * 12 * ($days / $days)) * 0.2);
                                            $temp["Life_Cost"] = printDollar($Life_Cost);
                                        }
                                        else { $temp["LTD_Cost"] = "$0.00"; }

                                        $project_benefits = $FICA_Cost + $WRS_Cost + $Health_Cost + $Dental_Cost + $LTD_Cost + $Life_Cost;
                                        $temp["project_benefits"] = printDollar($project_benefits);

                                        $project_compensation = $project_salary + $project_benefits;
                                        $temp["project_compensation"] = printDollar($project_compensation);

                                        // calculate the daily cost
                                        $dailyCost = 0;
                                        if ($days > 0) { 
                                            $dailyCost = ($project_compensation / $days);
                                        }
                                        $temp["daily_cost"] = printDollar($dailyCost);
                                    }
                                    else // no rates for active period exist; default to 0
                                    { 
                                        $project_salary = $daily_rate * $days;
                                        $temp["project_salary"] = printDollar($project_salary);

                                        $FICA_Cost = 0;
                                        $temp["FICA_Cost"] = printDollar($FICA_Cost);

                                        $WRS_Cost = 0;
                                        $temp["WRS_Cost"] = printDollar($WRS_Cost);

                                        $Health_Cost = 0;
                                        $temp["Health_Cost"] = printDollar($Health_Cost);

                                        $Dental_Cost = 0;
                                        $temp["Dental_Cost"] = printDollar($Dental_Cost);

                                        $LTD_Cost = 0;
                                        $temp["LTD_Cost"] = printDollar($LTD_Cost);

                                        $Life_Cost = 0;
                                        $temp["Life_Cost"] = printDollar($Life_Cost);

                                        $project_benefits = $FICA_Cost + $WRS_Cost + $Health_Cost + $Dental_Cost + $LTD_Cost + $Life_Cost;
                                        $temp["project_benefits"] = printDollar($project_benefits);

                                        $project_compensation = $project_salary + $project_benefits;
                                        $temp["project_compensation"] = printDollar($project_compensation);

                                        $temp["daily_cost"] = printDollar(0);
                                    }
                                }

                                // build the actions column
                                $actions = "";
                                if (($can_user_budget_all === true || $can_user_budget_assigned === true) && $periodDetails["editable"] == 1) 
                                { 
                                    $actions .= "<div class='d-flex justify-content-end'>
                                        <button class='btn btn-primary btn-sm mx-1' type='button' onclick='getEditTestProjectEmployeeModal(".$auto_id.");'><i class='fa-solid fa-pencil'></i></button>
                                        <button class='btn btn-danger btn-sm mx-1' type='button' onclick='getRemoveTestEmployeeFromProjectModal(".$auto_id.");'><i class='fa-solid fa-trash-can'></i></button>
                                    </div>";
                                }
                                $temp["actions"] = $actions;

                                $employees[] = $temp;
                            }
                        }
                    }
                }
            }
        } 
        
        // send data to be printed
        $fullData = [];
        $fullData["draw"] = 1;
        $fullData["data"] = $employees;
        echo json_encode($fullData);
        
        // disconnect from the database
        mysqli_close($conn);
    }
?>