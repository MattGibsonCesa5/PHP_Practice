<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // initialize array to store all employees
        $employees = [];

        // include additional required files
        include("../../includes/config.php");
        include("../../includes/functions.php");
        include("../../getSettings.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        // get period name from POST
        if (isset($_POST["period"]) && $_POST["period"] <> "") { $period = $_POST["period"]; } else { $period = null; }

        // verify the period exists; if it exists, store the period ID
        if ($period != null && $period_id = getPeriodID($conn, $period)) 
        {
            // store if the period is editable
            $is_editable = isPeriodEditable($conn, $period_id);

            ///////////////////////////////////////////////////////////////////////////////////////
            //
            //  MASTER EMPLOYEES LIST
            //
            ///////////////////////////////////////////////////////////////////////////////////////
            if (checkUserPermission($conn, "VIEW_EMPLOYEES_ALL")) 
            {
                // store user permissions for managing employees locally
                $can_user_edit = checkUserPermission($conn, "EDIT_EMPLOYEES");
                $can_user_delete = checkUserPermission($conn, "DELETE_EMPLOYEES");
                $can_view_budgets_all = checkUserPermission($conn, "VIEW_PROJECT_BUDGETS_ALL");
                $can_view_budgets_assigned = checkUserPermission($conn, "VIEW_PROJECT_BUDGETS_ASSIGNED");

                // get a list of all employees
                $getEmployees = mysqli_prepare($conn, "SELECT e.id, e.fname, e.lname, e.email, e.phone, e.birthday, e.gender, e.address_id, e.most_recent_hire_date, e.most_recent_end_date, e.original_hire_date, e.original_end_date, e.role_id, e.global, e.sync_demographics, e.sync_position, e.sync_contract,
                                                                ec.contract_days, ec.contract_type, ec.yearly_rate, ec.health_insurance, ec.dental_insurance, ec.wrs_eligible, ec.assignment_position, ec.sub_assignment, ec.experience, ec.experience_adjustment, ec.highest_degree, ec.active,
                                                                ec.title_id, ec.contract_start_date, ec.contract_end_date, ec.calendar_type, ec.number_of_pays, ec.supervisor_id
                                                        FROM employees e
                                                        LEFT JOIN employee_compensation ec ON e.id=ec.employee_id
                                                        WHERE ec.period_id=? AND e.queued=0");
                mysqli_stmt_bind_param($getEmployees, "i", $period_id);
                if (mysqli_stmt_execute($getEmployees))
                {
                    $getEmployeesResults = mysqli_stmt_get_result($getEmployees);
                    if (mysqli_num_rows($getEmployeesResults) > 0) // there are employees
                    {
                        while ($employee = mysqli_fetch_array($getEmployeesResults))
                        {
                            $employee_id = $employee["id"];
                            $fname = $employee["fname"];
                            $lname = $employee["lname"];
                            $email = $employee["email"];
                            $phone = $employee["phone"];
                            $address_id = $employee["address_id"];
                            $birthday = date("m/d/Y", strtotime($employee["birthday"]));
                            $gender = $employee["gender"];
                            $hire_date = $employee["most_recent_hire_date"];
                            $end_date = $employee["most_recent_end_date"];
                            $original_hire_date = $employee["original_hire_date"];
                            $original_end_date = $employee["original_end_date"];
                            $role_id = $employee["role_id"];
                            $global = $employee["global"];
                            $sync_demographics = $employee["sync_demographics"];
                            $sync_position = $employee["sync_position"];
                            $sync_contract = $employee["sync_contract"];
                            $days = $employee["contract_days"];
                            $contract_type = $employee["contract_type"];
                            $rate = $employee["yearly_rate"];
                            $health = $employee["health_insurance"];
                            $dental = $employee["dental_insurance"];
                            $wrs = $employee["wrs_eligible"];
                            $position = $employee["assignment_position"];
                            $area = $employee["sub_assignment"];
                            $experience = $employee["experience"];
                            $experience_adjustment = $employee["experience_adjustment"];
                            $degree = $employee["highest_degree"];
                            $active = $employee["active"];
                            $title_id = $employee["title_id"];
                            $contract_start_date = $employee["contract_start_date"];
                            $contract_end_date = $employee["contract_end_date"];
                            $calendar_type = $employee["calendar_type"];
                            $num_of_pays = $employee["number_of_pays"];
                            $supervisor_id = $employee["supervisor_id"];

                            // handle date validation
                            if (isset($hire_date) && $hire_date != null) { $hire_date = date("m/d/Y", strtotime($hire_date)); } else { $hire_date = ""; }
                            if (isset($end_date) && $end_date != null) { $end_date = date("m/d/Y", strtotime($end_date)); } else { $end_date = ""; }
                            if (isset($contract_start_date) && $contract_start_date != null) { $contract_start_date = date("m/d/Y", strtotime($contract_start_date)); } else { $contract_start_date = ""; }
                            if (isset($contract_end_date) && $contract_end_date != null) { $contract_end_date = date("m/d/Y", strtotime($contract_end_date)); } else { $contract_end_date = ""; }

                            // get the employee's title name
                            $title = getTitleName($conn, $title_id);
                            if ($title == "") { $title = "<span class='missing-field'>No title assigned</span>"; }
                            
                            // calculate the employee's daily rate
                            if ($days != 0 && is_numeric($rate)) { $daily_rate = $rate / $days; }
                            else { $daily_rate = 0; }

                            // build the ID / status column
                            $id_div = "<div class='d-none' aria-hidden='true'>$employee_id</div>"; // initialize div
                            if ($active == 1) { $id_div .= "<div class='active-div text-center p-1 my-1'>Active</div>"; }
                            else { $id_div = "<div class='inactive-div text-center p-1'>Inactive</div>"; } 
                            $id_div .= "<div class='my-1'>$employee_id</div>";

                            // calculate the hourly rate
                            if ($GLOBAL_SETTINGS["hours_per_workday"] != 0) { $hourly_rate = $daily_rate / $GLOBAL_SETTINGS["hours_per_workday"]; }
                            else { $hourly_rate = 0; }

                            // build the employee's birthday
                            $age = getAge($birthday);
                            if (isset($birthday)) { $displayDOB = date("n/j/Y", strtotime($birthday)) . " ($age)"; } else { $DOB = "<span class='missing-field'>Missing</span>"; }
                            if (isset($birthday)) { $DOB = date("n/j/Y", strtotime($birthday)); } else { $DOB = "<span class='missing-field'>Missing</span>"; }

                            // build the employee address
                            $address = $line1 = $line2 = $city = $state = $zip = "";
                            $getAddress = mysqli_prepare($conn, "SELECT ea.line1, ea.line2, ea.city, s.abbreviation, ea.zip FROM employee_addresses ea JOIN states s ON ea.state_id=s.id WHERE ea.id=? AND ea.employee_id=?");
                            mysqli_stmt_bind_param($getAddress, "ii", $address_id, $employee_id);
                            if (mysqli_stmt_execute($getAddress))
                            {
                                $result = mysqli_stmt_get_result($getAddress);
                                if (mysqli_num_rows($result) > 0)
                                {
                                    // store the employee's address locally
                                    $addressDetails = mysqli_fetch_array($result);
                                    $line1 = $addressDetails["line1"];
                                    $line2 = $addressDetails["line2"];
                                    $city = $addressDetails["city"];
                                    $state = $addressDetails["abbreviation"];
                                    $zip = $addressDetails["zip"];

                                    // build the display
                                    $address = $line1 . "<br>";
                                    if ($line2 <> "") { $address .= $line2 . "<br>"; }
                                    $address .= $city . ", " . $state . " " . $zip;
                                }
                            }

                            // build the employee contact card
                            $contact_card = "<div class='contact-card text-center'>";
                                if ($email <> "") { $contact_card .= "<button type='button' class='btn btn-secondary btn-sm mx-1' data-bs-container='body' data-bs-toggle='popover' data-bs-placement='bottom' data-bs-content='".htmlspecialchars($email)."'><i class='fa-solid fa-envelope'></i></button>"; }
                                if ($phone <> "") { $contact_card .= "<button type='button' class='btn btn-secondary btn-sm mx-1' data-bs-container='body' data-bs-toggle='popover' data-bs-placement='bottom' data-bs-content='".htmlspecialchars($phone)."'><i class='fa-solid fa-phone'></i></button>"; }
                                if ($address <> "") { $contact_card .= "<button type='button' class='btn btn-secondary btn-sm mx-1' data-bs-container='body' data-bs-toggle='popover' data-bs-placement='bottom' data-bs-content='".htmlspecialchars($address)."'><i class='fa-solid fa-house'></i></button>"; }
                            $contact_card .= "</div>";

                            // get the employee's primary department
                            $getDepartment = mysqli_prepare($conn, "SELECT d.name FROM departments d JOIN department_members dm ON d.id=dm.department_id WHERE dm.is_primary=1 AND dm.employee_id=?");
                            mysqli_stmt_bind_param($getDepartment, "i", $employee_id);
                            if (mysqli_stmt_execute($getDepartment))
                            {
                                $getDepartmentResult = mysqli_stmt_get_result($getDepartment);
                                if (mysqli_num_rows($getDepartmentResult) > 0) // primary department found
                                {
                                    $department = mysqli_fetch_array($getDepartmentResult)["name"];
                                }
                                else { $department = "<span class='missing-field'>No primary department assigned</span>"; }
                            }
                            else { $department = "<span class='missing-field'>No primary department assigned</span>"; }

                            // get the number of projects the employee is budgeted into 
                            $numOfProjects = 0;
                            $getNumOfProjects = mysqli_prepare($conn, "SELECT DISTINCT(project_code) FROM project_employees WHERE employee_id=? AND period_id=?");
                            mysqli_stmt_bind_param($getNumOfProjects, "ii", $employee_id, $period_id);
                            if (mysqli_stmt_execute($getNumOfProjects))
                            {
                                $getNumOfProjectsResults = mysqli_stmt_get_result($getNumOfProjects);
                                $numOfProjects = mysqli_num_rows($getNumOfProjectsResults);
                            }

                            // get the employee's supervisor
                            $supervisor = "";
                            if ($supervisor_id != null && verifyUser($conn, $supervisor_id)) { $supervisor = getUserDisplayName($conn, $supervisor_id); }
                            else { $supervisor = "<span class='missing-field'>No supervisor assigned</span>"; }

                            // build the employee's position display
                            $position_display = "<div class='position-card'>
                                <div class='my-1'><b>Title: </b>$title</div>
                                <div class='my-1'><b>Department: </b>$department</div>
                                <div class='my-1'><b>Supervisor: </b>$supervisor</div>";
                                if ($can_view_budgets_all === true || $can_view_budgets_assigned === true) { $position_display .= "<div class='my-1'><button class='btn btn-link btn-view_projects text-start text-nowrap w-100 p-0' onclick='getViewEmployeeProjectsModal($employee_id, $period_id);'>View Projects</button></div>"; }
                            $position_display .= "</div>";

                            // build the benefits display
                            $benefits = "<div class='benefits-card'>";
                            $health_benefits = "<b>Health:</b> ";
                            $dental_benefits = "<b>Dental:</b> ";
                            $wrs_benefits = "<b>WRS:</b> ";
                            if ($health == 0) { $health_benefits .= "None"; } else if ($health == 1) { $health_benefits .= "Family"; } else if ($health == 2) { $health_benefits .= "Single"; } else { $health_benefits = "<span class='missing-field'>Unknown</span>"; }
                            if ($dental == 0) { $dental_benefits .= "None"; } else if ($dental == 1) { $dental_benefits .= "Family"; } else if ($dental == 2) { $dental_benefits .= "Single"; } else { $dental_benefits = "<span class='missing-field'>Unknown</span>"; }
                            if ($wrs == 0) { $wrs_benefits .= "No"; } else if ($wrs == 1) { $wrs_benefits .= "Yes"; } else { $wrs_benefits = "<span class='missing-field'>Unknown</span>"; }
                            $benefits .= $health_benefits . "<br>" . $dental_benefits . "<br>" . $wrs_benefits;
                            $benefits .= "</div>";

                            // build the role to be filtered by
                            $role = $role_div = "";
                            $getRoleLabel = mysqli_prepare($conn, "SELECT name FROM roles WHERE id=?");
                            mysqli_stmt_bind_param($getRoleLabel, "i", $role_id);
                            if (mysqli_stmt_execute($getRoleLabel))
                            {
                                $getRoleLabelResult = mysqli_stmt_get_result($getRoleLabel);
                                if (mysqli_num_rows($getRoleLabelResult) > 0) // role found
                                {
                                    $role = mysqli_fetch_array($getRoleLabelResult)["name"];
                                }
                                else { $role = "Unknown"; }
                            }
                            else { $role = "Unknown"; }

                            // build the role display
                            if ($role_id == 1) { $role_div = "<div class='employees-admin-div text-center p-1'>".$role."</div>"; }
                            else if ($role_id == 2) { $role_div = "<div class='employees-director-div text-center p-1'>".$role."</div>"; }
                            else if ($role_id == 4) { $role_div = "<div class='employees-maintenance-div text-center p-1 mb-2'>".$role."</div>"; }

                            // build the role to be filtered by
                            $getRoleLabel = mysqli_prepare($conn, "SELECT name FROM roles WHERE id=?");
                            mysqli_stmt_bind_param($getRoleLabel, "i", $role_id);
                            if (mysqli_stmt_execute($getRoleLabel))
                            {
                                $getRoleLabelResult = mysqli_stmt_get_result($getRoleLabel);
                                if (mysqli_num_rows($getRoleLabelResult) > 0) // role found
                                {
                                    $role = mysqli_fetch_array($getRoleLabelResult)["name"];
                                }
                                else { $role = "Unknown"; }
                            }
                            else { $role = "Unknown"; }
                            
                            // build the title display
                            $title_display = "";
                            if ($role_div <> "") { $title_display .= $role_div; }
                            $title_display .= $title;

                            // build the actions column
                            $actions = "<div class='d-flex justify-content-end'>";
                                if ($can_user_edit === true && $is_editable === true) { $actions .= "<button class='btn btn-primary btn-sm mx-1' type='button' onclick='getEditEmployeeModal(".$employee_id.");'><i class='fa-solid fa-pencil'></i></button>"; }
                                $actions .= "<button class='btn btn-primary btn-sm mx-1' type='button' onclick='getRequestEmployeeChangesModal(".$employee_id.");'><i class='fa-solid fa-feather-pointed'></i></button>";
                                if ($can_user_edit === true) { $actions .= "<button class='btn btn-primary btn-sm mx-1' type='button' onclick='getMarkEmployeeChangesModal(".$employee_id.");'><i class='fa-solid fa-thumbtack'></i></button>"; }
                                if ($can_user_delete === true) { $actions .= "<button class='btn btn-danger btn-sm mx-1' type='button' onclick='getDeleteEmployeeModal(".$employee_id.");'><i class='fa-solid fa-trash-can'></i></button>"; }
                            $actions .= "</div>";

                            // get the employee's next period contract details
                            $next_contract_days = $next_contract_rate = $rate_diff = 0;
                            $getNextPeriod = mysqli_query($conn, "SELECT id FROM periods WHERE next=1");
                            if (mysqli_num_rows($getNextPeriod) > 0) // next period found
                            {
                                $next_period_id = mysqli_fetch_array($getNextPeriod)["id"];
                                $getNextCompensation = mysqli_prepare($conn, "SELECT * FROM employee_compensation WHERE employee_id=? AND period_id=?");
                                mysqli_stmt_bind_param($getNextCompensation, "ii", $employee_id, $next_period_id);
                                if (mysqli_stmt_execute($getNextCompensation))
                                {
                                    $getNextCompensationResult = mysqli_stmt_get_result($getNextCompensation);
                                    if (mysqli_num_rows($getNextCompensationResult) > 0) // benefits and compensation found
                                    {
                                        // store the employee's benefits and compensation locally
                                        $futureEmployeeCompensation = mysqli_fetch_array($getNextCompensationResult);
                                        $next_contract_rate = $futureEmployeeCompensation["yearly_rate"];
                                        $next_contract_days = $futureEmployeeCompensation["contract_days"];
                                        if ($rate != 0) { $rate_diff = ((($next_contract_rate - $rate) / $rate) * 100); }
                                    }
                                }
                            }

                            // build the temporary array
                            $temp = [];
                            $temp["id"] = $id_div;
                            $temp["export_id"] = $employee_id;
                            $temp["fname"] = $fname;
                            $temp["lname"] = $lname;
                            $temp["email"] = $email;
                            $temp["phone"] = $phone;
                            $temp["birthday"] = $displayDOB;
                            $temp["DOB"] = $DOB;
                            $temp["contact"] = $contact_card;
                            $temp["address"] = $address;
                            $temp["title"] = $title_display;
                            $temp["position"] = $position_display;
                            $temp["export_title"] = $title;
                            $temp["department"] = $department;
                            $temp["supervisor"] = $supervisor;
                            $temp["days"] = $days;
                            if (isset($rate) && is_numeric($rate)) { $temp["yearly_rate"] = printDollar($rate); } else { $temp["yearly_rate"] = "$0.00"; }
                            if (isset($rate) && is_numeric($rate)) { $temp["export_yearly_rate"] = number_format($rate, 2); } else { $temp["export_yearly_rate"] = "0"; }
                            if (isset($daily_rate) && is_numeric($daily_rate)) { $temp["daily_rate"] = printDollar($daily_rate); } else { $temp["daily_rate"] = "$0.00"; }
                            if (isset($hourly_rate) && is_numeric($hourly_rate)) { $temp["hourly_rate"] = printDollar($hourly_rate); } else { $temp["hourly_rate"] = "$0.00"; }
                            if (isset($hourly_rate) && is_numeric($hourly_rate)) { $temp["export_hourly_rate"] = number_format($hourly_rate); } else { $temp["export_hourly_rate"] = "0.00"; }
                            $temp["benefits"] = $benefits;
                            $temp["role"] = $role;
                            $temp["actions"] = $actions;

                            // build the benefits costs column
                            $health_costs = $dental_costs = $wrs_costs = $fica_costs = $life_costs = $ltd_costs = 0;
                            // get costs
                            $health_costs = getEmployeeHealthCosts($conn, $employee_id, $period_id);
                            $dental_costs = getEmployeeDentalCosts($conn, $employee_id, $period_id);
                            $wrs_costs = getEmployeeWRSCosts($conn, $employee_id, $period_id);
                            $fica_costs = getEmployeeFICACosts($conn, $employee_id, $period_id);
                            $life_costs = getEmployeeLifeCosts($conn, $employee_id, $period_id);
                            $ltd_costs = getEmployeeLTDCosts($conn, $employee_id, $period_id);

                            // calculate total fringe
                            $total_fringe = ($health_costs + $dental_costs + $wrs_costs + $fica_costs + $life_costs + $ltd_costs);

                            // calculate total compensation
                            $total_compensation = $rate + $total_fringe;

                            // calculate daily compensation
                            $daily_compensation = 0;
                            if ($days > 0) { $daily_compensation = ($total_compensation / $days); }

                            // add costs to return array
                            $temp["health_costs"] = "$".number_format($health_costs, 2);
                            $temp["dental_costs"] = "$".number_format($dental_costs, 2); 
                            $temp["wrs_costs"] = "$".number_format($wrs_costs, 2);
                            $temp["fica_costs"] = "$".number_format($fica_costs, 2);
                            $temp["life_costs"] = "$".number_format($life_costs, 2);
                            $temp["ltd_costs"] = "$".number_format($ltd_costs, 2);
                            $temp["total_fringe"] = "$".number_format($total_fringe, 2);
                            $temp["total_compensation"] = "$".number_format($total_compensation, 2);
                            $temp["daily_compensation"] = "$".number_format($daily_compensation, 2);

                            // build the export columns
                            if ($gender == 1) { $temp["gender"] = "Male"; } else if ($gender == 2) { $temp["gender"] = "Female"; } else { $temp["gender"] = ""; }
                            $temp["line1"] = $line1;
                            $temp["line2"] = $line2;
                            $temp["city"] = $city;
                            $temp["state"] = $state;
                            $temp["zip"] = $zip;
                            if ($health == 1) { $temp["health"] = "Family"; } else if ($health == 2) { $temp["health"] = "Single"; } else { $temp["health"] = ""; }
                            if ($dental == 1) { $temp["dental"] = "Family"; } else if ($dental == 2) { $temp["dental"] = "Single"; } else { $temp["dental"] = ""; }
                            if ($wrs == 1) { $temp["wrs"] = "Yes"; } else { $temp["wrs"] = "No"; }
                            $temp["DPI_position"] = $position; 
                            $temp["DPI_area"] = $area;
                            $temp["experience"] = $experience;
                            $temp["degree"] = $degree;
                            if ($active == 1) { $temp["export_status"] = "Active"; } else { $temp["export_status"] = "Inactive"; }
                            $temp["nextPeriod_contract_days"] = $next_contract_days;
                            $temp["nextPeriod_contract_rate"] = $next_contract_rate;
                            $temp["nextPeriod_rate_diff"] = round($rate_diff, 2);

                            // build export contract type column
                            $export_contract_type = "";
                            if ($contract_type == 0) { $export_contract_type = "Regular"; }
                            else if ($contract_type == 1) { $export_contract_type = "Limited"; } 
                            else if ($contract_type == 2) { $export_contract_type = "At-Will"; } 
                            else if ($contract_type == 3) { $export_contract_type = "Section 118"; } 
                            else if ($contract_type == 4) { $export_contract_type = "Hourly"; } 
                            $temp["export_contract_type"] = $export_contract_type;

                            // build export contract type column
                            $export_calendar_type = "";
                            if ($calendar_type == 0) { $export_calendar_type = "N/A"; }
                            else if ($calendar_type == 1) { $export_calendar_type = "Hourly"; } 
                            else if ($calendar_type == 2) { $export_calendar_type = "Salary"; } 
                            $temp["export_calendar_type"] = $export_calendar_type;

                            $temp["number_of_pays"] = $num_of_pays;
                            $temp["contract_start_date"] = $contract_start_date;
                            $temp["contract_end_date"] = $contract_end_date;
                            $temp["hire_date"] = $hire_date;
                            $temp["end_date"] = $end_date;

                            $employees[] = $temp;
                        }
                    }
                }
            }
            ///////////////////////////////////////////////////////////////////////////////////////
            //
            //  ASSIGNED EMPLOYEES LIST
            //
            ///////////////////////////////////////////////////////////////////////////////////////
            else if (checkUserPermission($conn, "VIEW_EMPLOYEES_ASSIGNED")) 
            {
                // store user permissions for managing employees locally
                $can_user_edit = checkUserPermission($conn, "EDIT_EMPLOYEES");
                $can_user_delete = checkUserPermission($conn, "DELETE_EMPLOYEES");
                $can_view_budgets_all = checkUserPermission($conn, "VIEW_PROJECT_BUDGETS_ALL");
                $can_view_budgets_assigned = checkUserPermission($conn, "VIEW_PROJECT_BUDGETS_ASSIGNED");

                // get a list of employees the director has access to
                $employees = [];
                $getEmployees = mysqli_prepare($conn, "SELECT DISTINCT e.id, e.fname, e.lname, e.email, e.phone, e.birthday, e.gender, e.address_id, e.most_recent_hire_date, e.most_recent_end_date, e.original_hire_date, e.original_end_date, e.role_id, e.global, e.sync_demographics, e.sync_position, e.sync_contract,
                                                                        ec.contract_days, ec.contract_type, ec.yearly_rate, ec.health_insurance, ec.dental_insurance, ec.wrs_eligible, ec.assignment_position, ec.sub_assignment, ec.experience, ec.experience_adjustment, ec.highest_degree, ec.active,
                                                                        ec.title_id, ec.contract_start_date, ec.contract_end_date, ec.calendar_type, ec.number_of_pays, ec.supervisor_id
                                                        FROM employees e
                                                        LEFT JOIN employee_compensation ec ON e.id=ec.employee_id
                                                        JOIN department_members dm ON e.id=dm.employee_id 
                                                        JOIN departments d ON dm.department_id=d.id
                                                        WHERE ec.period_id=? AND ((d.director_id=? OR d.secondary_director_id=?) OR e.global=1) AND e.queued=0");
                mysqli_stmt_bind_param($getEmployees, "iii", $period_id, $_SESSION["id"], $_SESSION["id"]);
                if (mysqli_stmt_execute($getEmployees))
                {
                    $getEmployeesResults = mysqli_stmt_get_result($getEmployees);
                    if (mysqli_num_rows($getEmployeesResults) > 0) // there are employees
                    {
                        while ($employee = mysqli_fetch_array($getEmployeesResults))
                        {
                            $employee_id = $employee["id"];
                            $fname = $employee["fname"];
                            $lname = $employee["lname"];
                            $email = $employee["email"];
                            $phone = $employee["phone"];
                            $address_id = $employee["address_id"];
                            $birthday = date("m/d/Y", strtotime($employee["birthday"]));
                            $gender = $employee["gender"];
                            $hire_date = $employee["most_recent_hire_date"];
                            $end_date = $employee["most_recent_end_date"];
                            $original_hire_date = $employee["original_hire_date"];
                            $original_end_date = $employee["original_end_date"];
                            $role_id = $employee["role_id"];
                            $global = $employee["global"];
                            $sync_demographics = $employee["sync_demographics"];
                            $sync_position = $employee["sync_position"];
                            $sync_contract = $employee["sync_contract"];
                            $days = $employee["contract_days"];
                            $contract_type = $employee["contract_type"];
                            $rate = $employee["yearly_rate"];
                            $health = $employee["health_insurance"];
                            $dental = $employee["dental_insurance"];
                            $wrs = $employee["wrs_eligible"];
                            $position = $employee["assignment_position"];
                            $area = $employee["sub_assignment"];
                            $experience = $employee["experience"];
                            $experience_adjustment = $employee["experience_adjustment"];
                            $degree = $employee["highest_degree"];
                            $active = $employee["active"];
                            $title_id = $employee["title_id"];
                            $contract_start_date = $employee["contract_start_date"];
                            $contract_end_date = $employee["contract_end_date"];
                            $calendar_type = $employee["calendar_type"];
                            $num_of_pays = $employee["number_of_pays"];
                            $supervisor_id = $employee["supervisor_id"];

                            // get the employee's title name
                            $title = getTitleName($conn, $title_id);
                            if ($title == "") { $title = "<span class='missing-field'>No title assigned</span>"; }
                            
                            // calculate the employee's daily rate
                            if ($days != 0 && is_numeric($rate)) { $daily_rate = $rate / $days; }
                            else { $daily_rate = 0; }

                            // calculate the hourly rate
                            if ($GLOBAL_SETTINGS["hours_per_workday"] != 0) { $hourly_rate = $daily_rate / $GLOBAL_SETTINGS["hours_per_workday"]; }
                            else { $hourly_rate = 0; }

                            // build the ID / status column
                            $id_div = "<div class='d-none' aria-hidden='true'>$employee_id</div>"; // initialize div
                            if ($active == 1) { $id_div .= "<div class='active-div text-center p-1 my-1'>Active</div>"; }
                            else { $id_div = "<div class='inactive-div text-center p-1'>Inactive</div>"; } 
                            $id_div .= "<div class='my-1'>$employee_id</div>";

                            // build the employee's birthday
                            $age = getAge($birthday);
                            if (isset($birthday)) { $displayDOB = date("n/j/Y", strtotime($birthday)) . " ($age)"; } else { $DOB = "<span class='missing-field'>Missing</span>"; }

                            // build the employee address
                            $address = $line1 = $line2 = $city = $state = $zip = "";
                            $getAddress = mysqli_prepare($conn, "SELECT ea.line1, ea.line2, ea.city, s.abbreviation, ea.zip FROM employee_addresses ea JOIN states s ON ea.state_id=s.id WHERE ea.id=? AND ea.employee_id=?");
                            mysqli_stmt_bind_param($getAddress, "ii", $address_id, $employee_id);
                            if (mysqli_stmt_execute($getAddress))
                            {
                                $result = mysqli_stmt_get_result($getAddress);
                                if (mysqli_num_rows($result) > 0)
                                {
                                    // store the employee's address locally
                                    $addressDetails = mysqli_fetch_array($result);
                                    $line1 = $addressDetails["line1"];
                                    $line2 = $addressDetails["line2"];
                                    $city = $addressDetails["city"];
                                    $state = $addressDetails["abbreviation"];
                                    $zip = $addressDetails["zip"];

                                    // build the display
                                    $address = $line1 . "<br>";
                                    if ($line2 <> "") { $address .= $line2 . "<br>"; }
                                    $address .= $city . ", " . $state . " " . $zip;
                                }
                            }

                            // build the employee contact card
                            $contact_card = "<div class='contact-card text-center'>";
                                if ($email <> "") { $contact_card .= "<button type='button' class='btn btn-secondary btn-sm mx-1' data-bs-container='body' data-bs-toggle='popover' data-bs-placement='bottom' data-bs-content='".htmlspecialchars($email)."'><i class='fa-solid fa-envelope'></i></button>"; }
                                if ($phone <> "") { $contact_card .= "<button type='button' class='btn btn-secondary btn-sm mx-1' data-bs-container='body' data-bs-toggle='popover' data-bs-placement='bottom' data-bs-content='".htmlspecialchars($phone)."'><i class='fa-solid fa-phone'></i></button>"; }
                                if ($address <> "") { $contact_card .= "<button type='button' class='btn btn-secondary btn-sm mx-1' data-bs-container='body' data-bs-toggle='popover' data-bs-placement='bottom' data-bs-content='".htmlspecialchars($address)."'><i class='fa-solid fa-house'></i></button>"; }
                            $contact_card .= "</div>";

                            // get the employee's primary department
                            $getDepartment = mysqli_prepare($conn, "SELECT d.name FROM departments d JOIN department_members dm ON d.id=dm.department_id WHERE dm.is_primary=1 AND dm.employee_id=?");
                            mysqli_stmt_bind_param($getDepartment, "i", $employee_id);
                            if (mysqli_stmt_execute($getDepartment))
                            {
                                $getDepartmentResult = mysqli_stmt_get_result($getDepartment);
                                if (mysqli_num_rows($getDepartmentResult) > 0) // primary department found
                                {
                                    $department = mysqli_fetch_array($getDepartmentResult)["name"];
                                }
                                else { $department = "<span class='missing-field'>No primary department assigned</span>"; }
                            }
                            else { $department = "<span class='missing-field'>No primary department assigned</span>"; }

                            // build the employee's position display
                            $position_display = "<div class='position-card'>
                                <div class='my-1'><b>Title: </b>$title</div>
                                <div class='my-1'><b>Department: </b>$department</div>";
                                if ($can_view_budgets_all === true || $can_view_budgets_assigned === true) { $position_display .= "<div class='my-1'><button class='btn btn-secondary btn-sm py-1 px-3' onclick='getViewEmployeeProjectsModal($employee_id, $period_id);'>View Projects</button></div>"; }
                            $position_display .= "</div>";

                            // build the benefits display
                            $benefits = "";
                            $health_benefits = "<b>Health:</b> ";
                            $dental_benefits = "<b>Dental:</b> ";
                            $wrs_benefits = "<b>WRS:</b> ";
                            if ($health == 0) { $health_benefits .= "None"; } else if ($health == 1) { $health_benefits .= "Family"; } else if ($health == 2) { $health_benefits .= "Single"; } else { $health_benefits = "<span class='missing-field'>Unknown</span>"; }
                            if ($dental == 0) { $dental_benefits .= "None"; } else if ($dental == 1) { $dental_benefits .= "Family"; } else if ($dental == 2) { $dental_benefits .= "Single"; } else { $dental_benefits = "<span class='missing-field'>Unknown</span>"; }
                            if ($wrs == 0) { $wrs_benefits .= "No"; } else if ($wrs == 1) { $wrs_benefits .= "Yes"; } else { $wrs_benefits = "<span class='missing-field'>Unknown</span>"; }
                            $benefits .= $health_benefits . "<br>" . $dental_benefits . "<br>" . $wrs_benefits;

                            // build the benefits costs column
                            $health_costs = $dental_costs = $wrs_costs = $fica_costs = $life_costs = $ltd_costs = 0;
                            // get costs
                            $health_costs = getEmployeeHealthCosts($conn, $employee_id, $period_id);
                            $dental_costs = getEmployeeDentalCosts($conn, $employee_id, $period_id);
                            $wrs_costs = getEmployeeWRSCosts($conn, $employee_id, $period_id);
                            $fica_costs = getEmployeeFICACosts($conn, $employee_id, $period_id);
                            $life_costs = getEmployeeLifeCosts($conn, $employee_id, $period_id);
                            $ltd_costs = getEmployeeLTDCosts($conn, $employee_id, $period_id);
                            
                            // calculate total fringe
                            $total_fringe = ($health_costs + $dental_costs + $wrs_costs + $fica_costs + $life_costs + $ltd_costs);

                            // calculate total compensation
                            $total_compensation = $rate + $total_fringe;

                            // calculate daily compensation
                            $daily_compensation = 0;
                            if ($days > 0) { $daily_compensation = ($total_compensation / $days); }

                            // build the role to be displayed
                            $role = "";
                            $getRoleLabel = mysqli_prepare($conn, "SELECT name FROM roles WHERE id=?");
                            mysqli_stmt_bind_param($getRoleLabel, "i", $role_id);
                            if (mysqli_stmt_execute($getRoleLabel))
                            {
                                $getRoleLabelResult = mysqli_stmt_get_result($getRoleLabel);
                                if (mysqli_num_rows($getRoleLabelResult) > 0) // role found
                                {
                                    $role = mysqli_fetch_array($getRoleLabelResult)["name"];
                                }
                                else { $role = "Unknown"; }
                            }
                            else { $role = "Unknown"; }

                            // build the actions column
                            $actions = "<div class='d-flex justify-content-end'>";
                                if ($can_user_edit === true && $is_editable === true) { $actions .= "<button class='btn btn-primary btn-sm mx-1' type='button' onclick='getEditEmployeeModal(".$employee_id.");'><i class='fa-solid fa-pencil'></i></button>"; }
                                $actions .= "<button class='btn btn-primary btn-sm mx-1' type='button' onclick='getRequestEmployeeChangesModal(".$employee_id.");'><i class='fa-solid fa-feather-pointed'></i></button>";
                                if ($can_user_edit === true) { $actions .= "<button class='btn btn-primary btn-sm mx-1' type='button' onclick='getMarkEmployeeChangesModal(".$employee_id.");'><i class='fa-solid fa-thumbtack'></i></button>"; }
                                if ($can_user_delete === true) { $actions .= "<button class='btn btn-danger btn-sm mx-1' type='button' onclick='getDeleteEmployeeModal(".$employee_id.");'><i class='fa-solid fa-trash-can'></i></button>"; }
                            $actions .= "</div>";

                            // build the temporary array
                            $temp = [];
                            $temp["id"] = $id_div;
                            $temp["fname"] = $fname;
                            $temp["lname"] = $lname;
                            $temp["birthday"] = $displayDOB;
                            $temp["contact"] = $contact_card;
                            $temp["address"] = $address;
                            $temp["title"] = $title;
                            $temp["position"] = $position_display;
                            $temp["department"] = $department;
                            $temp["days"] = $days;
                            if (isset($rate) && is_numeric($rate)) { $temp["yearly_rate"] = printDollar($rate); } else { $temp["yearly_rate"] = "$0.00"; }
                            if (isset($daily_rate) && is_numeric($daily_rate)) { $temp["daily_rate"] = printDollar($daily_rate); } else { $temp["daily_rate"] = "$0.00"; }
                            if (isset($hourly_rate) && is_numeric($hourly_rate)) { $temp["hourly_rate"] = printDollar($hourly_rate); } else { $temp["hourly_rate"] = "$0.00"; }
                            $temp["benefits"] = $benefits;
                            $temp["health_costs"] = "$".number_format($health_costs, 2);
                            $temp["dental_costs"] = "$".number_format($dental_costs, 2); 
                            $temp["wrs_costs"] = "$".number_format($wrs_costs, 2);
                            $temp["fica_costs"] = "$".number_format($fica_costs, 2);
                            $temp["life_costs"] = "$".number_format($life_costs, 2);
                            $temp["ltd_costs"] = "$".number_format($ltd_costs, 2);
                            $temp["total_fringe"] = "$".number_format($total_fringe, 2);
                            $temp["total_compensation"] = "$".number_format($total_compensation, 2);
                            $temp["daily_compensation"] = "$".number_format($daily_compensation, 2);
                            $temp["role"] = $role;
                            $temp["actions"] = $actions;

                            $employees[] = $temp;
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