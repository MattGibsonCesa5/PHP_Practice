<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // initialize the array of data to send
        $masterData = [];

        // get additional required files
        include("../../includes/functions.php");
        include("../../includes/config.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "VIEW_REPORT_MISBUDGETED_EMPLOYEES_ALL") || checkUserPermission($conn, "VIEW_REPORT_MISBUDGETED_EMPLOYEES_ASSIGNED"))
        {
            // get the period from POST
            if (isset($_POST["period"]) && $_POST["period"] <> "") { $period = $_POST["period"]; } else { $period = null; }

            // verify the period exists; if it exists, store the period ID
            if ($period != null && $period_id = getPeriodID($conn, $period))
            {
                // build and prepare the query to get a list of employees (both active and inactive) based on the user's permissions
                if (checkUserPermission($conn, "VIEW_REPORT_MISBUDGETED_EMPLOYEES_ALL"))
                {
                    $getEmployees = mysqli_prepare($conn, "SELECT e.id, e.fname, e.lname, ec.contract_days, ec.active FROM employees e
                                                    JOIN employee_compensation ec ON e.id=ec.employee_id
                                                    WHERE ec.period_id=?
                                                    ORDER BY e.lname ASC, e.fname ASC");
                    mysqli_stmt_bind_param($getEmployees, "i", $period_id);
                }
                else if (checkUserPermission($conn, "VIEW_REPORT_MISBUDGETED_EMPLOYEES_ASSIGNED"))
                {
                    $getEmployees = mysqli_prepare($conn, "SELECT e.id, e.fname, e.lname, ec.contract_days, ec.active FROM employees e 
                                                    JOIN employee_compensation ec ON e.id=ec.employee_id
                                                    JOIN department_members dm ON e.id=dm.employee_id 
                                                    JOIN departments d ON dm.department_id=d.id
                                                    WHERE ec.period_id=? AND ((d.director_id=? OR d.secondary_director_id=?) OR e.global=1)
                                                    ORDER BY e.lname ASC, e.fname ASC");
                    mysqli_stmt_bind_param($getEmployees, "iii", $period_id, $_SESSION["id"], $_SESSION["id"]);
                }

                // execute the query to get employees list
                if (mysqli_stmt_execute($getEmployees))
                {
                    $getEmployeesResults = mysqli_stmt_get_result($getEmployees);
                    if (mysqli_num_rows($getEmployeesResults) > 0) // employees found; continue
                    {
                        while ($emp = mysqli_fetch_array($getEmployeesResults))
                        {
                            // store employee details locally
                            $emp_id = $emp["id"];
                            $fname = $emp["fname"];
                            $lname = $emp["lname"];
                            $active = $emp["active"];
                            $contract_days = $emp["contract_days"];

                            // build the ID / status column
                            $id_div = ""; // initialize div
                            if ($active == 1) { $id_div = "<div class='d-none' aria-hidden='true'>$emp_id</div><div class='active-div text-center p-1 my-1'>Active</div><div class='my-1'>$emp_id</div>"; }
                            else { $id_div = "<div class='d-none' aria-hidden='true'>$emp_id</div><div class='inactive-div text-center p-1'>Inactive</div><div class='my-1'>$emp_id</div>"; } 

                            // build the ID / status column
                            $id_div = ""; // initialize div
                            if ($active == 1) { $id_div .= "<div class='my-1'><span class='text-nowrap'>$emp_id</span><div class='active-div text-center px-3 py-1 float-end'>Active</div></div>"; }
                            else { $id_div .= "<div class='my-1'><span class='text-nowrap'>$emp_id</span><div class='inactive-div text-center px-3 py-1 float-end'>Inactive</div></div>"; } 

                            // get the employees primary department
                            $dept = null; // assume employee has no primary dept
                            $getDept = mysqli_prepare($conn, "SELECT d.name FROM departments d JOIN department_members dm ON d.id=dm.department_id WHERE dm.employee_id=? AND dm.is_primary=1 LIMIT 1");
                            mysqli_stmt_bind_param($getDept, "i", $emp_id);
                            if (mysqli_stmt_execute($getDept))
                            {
                                $getDeptResult = mysqli_stmt_get_result($getDept);
                                if (mysqli_num_rows($getDeptResult) > 0) // primary dept found
                                {
                                    $dept = mysqli_fetch_array($getDeptResult)["name"];
                                }
                            }

                            // initialize an array to store all instances an employee is in the budget
                            $emp_instances = [];

                            // initialize a counter to store total days an employee is budgeted
                            $total_project_days = 0;

                            // get each instance in which an employee is budgeted
                            $getBudgetedDays = mysqli_prepare($conn, "SELECT p.code, p.name, pe.project_days FROM projects p
                                                                        JOIN project_employees pe ON p.code=pe.project_code
                                                                        WHERE pe.employee_id=? AND pe.period_id=?");
                            mysqli_stmt_bind_param($getBudgetedDays, "ii", $emp_id, $period_id);
                            if (mysqli_stmt_execute($getBudgetedDays))
                            {
                                $getBudgetedDaysResults = mysqli_stmt_get_result($getBudgetedDays);
                                if (mysqli_num_rows($getBudgetedDaysResults) > 0) // employee has been budgeted
                                {
                                    while ($budgeted_emp = mysqli_fetch_array($getBudgetedDaysResults))
                                    {
                                        // add budget instance to array
                                        $temp = [];
                                        $temp["code"] = $budgeted_emp["code"];
                                        $temp["name"] = $budgeted_emp["name"];
                                        $temp["project_days"] = $budgeted_emp["project_days"];
                                        $emp_instances[] = $temp;

                                        // add project days
                                        $total_project_days += $budgeted_emp["project_days"];
                                    }
                                }
                            }

                            // if the employee is misbudgeted; add to report
                            if ($total_project_days != $contract_days)
                            {
                                if (count($emp_instances) > 0) // employee was budgeted; add each instance to report
                                {
                                    // for each instance; add to report
                                    for ($i = 0; $i < count($emp_instances); $i++)
                                    {
                                        $temp = [];
                                        $temp["id"] = $id_div;
                                        $temp["fname"] = $fname;
                                        $temp["lname"] = $lname;
                                        if ($active == 1) { $temp["status"] = "Active"; } else { $temp["status"] = "Inactive"; }

                                        // build the primary department column
                                        if ($dept != null) { $temp["primary_department"] = $dept; }
                                        else { $temp["primary_department"] = "<span class='missing-field'>Missing</span>"; }

                                        // build the project code display
                                        $code_div = getProjectLink($emp_instances[$i]["code"], $period_id, true);

                                        $temp["project_code"] = $code_div;
                                        $temp["export_project_code"] = $emp_instances[$i]["code"];
                                        $temp["project_name"] = $emp_instances[$i]["name"];
                                        $temp["contract_days"] = $contract_days;
                                        $temp["project_days"] = $emp_instances[$i]["project_days"];
                                        $temp["budgeted_days"] = $total_project_days;
                                        $temp["days_diff"] = $total_project_days - $contract_days;
                                        $masterData[] = $temp;
                                    }
                                }
                                else // employee was not budgeted; add row just with employee data
                                {
                                    $temp = [];
                                    $temp["id"] = $id_div;
                                    $temp["fname"] = $fname;
                                    $temp["lname"] = $lname;
                                    if ($active == 1) { $temp["status"] = "Active"; } else { $temp["status"] = "Inactive"; }

                                    // build the primary department column
                                    if ($dept != null) { $temp["primary_department"] = $dept; }
                                    else { $temp["primary_department"] = "<span class='missing-field'>Missing</span>"; }

                                    $temp["project_code"] = "<span class='missing-field'>Missing</span>";
                                    $temp["export_project_code"] = "<span class='missing-field'>Missing</span>";
                                    $temp["project_name"] = "<span class='missing-field'>Missing</span>";
                                    $temp["contract_days"] = $contract_days;
                                    $temp["project_days"] = 0;
                                    $temp["budgeted_days"] = $total_project_days;
                                    $temp["days_diff"] = $total_project_days - $contract_days;
                                    $masterData[] = $temp;
                                }
                            }
                        }
                    }
                }
            }
        }

        // send data to be printed
        $fullData = [];
        $fullData["draw"] = 1;
        $fullData["data"] = $masterData;
        echo json_encode($fullData);
        
        // disconnect from the database
        mysqli_close($conn);
    }
?>