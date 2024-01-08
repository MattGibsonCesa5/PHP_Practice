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

        if (checkUserPermission($conn, "VIEW_PROJECT_BUDGETS_ALL") || checkUserPermission($conn, "VIEW_PROJECT_BUDGETS_ASSIGNED"))
        {
            // get the period from POST
            if (isset($_POST["period"]) && $_POST["period"] <> "") { $period = $_POST["period"]; } else { $period = null; }

            if ($period != null && $period_id = getPeriodID($conn, $period)) // verify the period exists; if it exists, store the period ID
            {
                // build and prepare the query to get a list of employees (both active and inactive) based on the user's permissions
                if (checkUserPermission($conn, "VIEW_PROJECT_BUDGETS_ALL"))
                {
                    $getBudgetedEmployees = mysqli_prepare($conn, "SELECT e.id, e.fname, e.lname, ec.contract_days, ec.number_of_pays, ec.active, p.code, p.name, pe.project_days, pe.fund_code, pe.location_code, pe.object_code, pe.function_code FROM projects p
                                                    JOIN project_employees pe ON p.code=pe.project_code
                                                    JOIN projects_status ps ON p.code=ps.code AND pe.period_id=ps.period_id
                                                    JOIN employees e ON pe.employee_id=e.id
                                                    JOIN employee_compensation ec ON e.id=ec.employee_id AND ec.period_id=pe.period_id
                                                    WHERE pe.period_id=? AND ps.status=1
                                                    ORDER BY e.lname ASC, e.fname ASC");
                    mysqli_stmt_bind_param($getBudgetedEmployees, "i", $period_id);
                }
                else if (checkUserPermission($conn, "VIEW_PROJECT_BUDGETS_ASSIGNED"))
                {
                    $getBudgetedEmployees = mysqli_prepare($conn, "SELECT e.id, e.fname, e.lname, ec.contract_days, ec.number_of_pays, ec.active, p.code, p.name, pe.project_days, pe.fund_code, pe.location_code, pe.object_code, pe.function_code FROM projects p
                                                    JOIN project_employees pe ON p.code=pe.project_code
                                                    JOIN projects_status ps ON p.code=ps.code AND pe.period_id=ps.period_id
                                                    JOIN employees e ON pe.employee_id=e.id
                                                    JOIN employee_compensation ec ON e.id=ec.employee_id AND ec.period_id=pe.period_id
                                                    JOIN department_members dm ON e.id=dm.employee_id 
                                                    JOIN departments d ON dm.department_id=d.id
                                                    WHERE pe.period_id=? AND ps.status=1 AND (d.director_id=? OR d.secondary_director_id=?)
                                                    ORDER BY e.lname ASC, e.fname ASC");
                    mysqli_stmt_bind_param($getBudgetedEmployees, "iii", $period_id, $_SESSION["id"], $_SESSION["id"]);
                }

                // execute the query to get employees list
                if (mysqli_stmt_execute($getBudgetedEmployees))
                {
                    $getBudgetedEmployeesResults = mysqli_stmt_get_result($getBudgetedEmployees);
                    if (mysqli_num_rows($getBudgetedEmployeesResults) > 0) // employees found; continue
                    {
                        while ($budgeted_employee = mysqli_fetch_array($getBudgetedEmployeesResults))
                        {
                            // store employee details locally
                            $employee_id = $budgeted_employee["id"];
                            $fname = $budgeted_employee["fname"];
                            $lname = $budgeted_employee["lname"];
                            $active = $budgeted_employee["active"];
                            $contract_days = $budgeted_employee["contract_days"];
                            $num_of_pays = $budgeted_employee["number_of_pays"];
                            $project_code = $budgeted_employee["code"];
                            $project_name = $budgeted_employee["name"];
                            $project_days = $budgeted_employee["project_days"];
                            $fund_code = $budgeted_employee["fund_code"];
                            $location_code = $budgeted_employee["location_code"];
                            $object_code = $budgeted_employee["object_code"];
                            $function_code = $budgeted_employee["function_code"];

                            // build the ID / status column
                            $id_div = ""; // initialize div
                            if ($active == 1) { $id_div .= "<div class='my-1'><span class='text-nowrap'>$employee_id</span><div class='active-div text-center px-3 py-1 float-end'>Active</div></div>"; }
                            else { $id_div .= "<div class='my-1'><span class='text-nowrap'>$employee_id</span><div class='inactive-div text-center px-3 py-1 float-end'>Inactive</div></div>"; } 

                            // build the project code display
                            $code_div = getProjectLink($project_code, $period_id, true);

                            // get the employees primary department
                            $dept = null; // assume employee has no primary dept
                            $getDept = mysqli_prepare($conn, "SELECT d.name FROM departments d JOIN department_members dm ON d.id=dm.department_id WHERE dm.employee_id=? AND dm.is_primary=1 LIMIT 1");
                            mysqli_stmt_bind_param($getDept, "i", $employee_id);
                            if (mysqli_stmt_execute($getDept))
                            {
                                $getDeptResult = mysqli_stmt_get_result($getDept);
                                if (mysqli_num_rows($getDeptResult) > 0) // primary dept found
                                {
                                    $dept = mysqli_fetch_array($getDeptResult)["name"];
                                }
                            }

                            // get the total number of days the employee is budgeted
                            $total_budgeted_days = getEmployeeBudgetedDays($conn, $employee_id, $period_id);

                            // calculate the percentage of days in the project
                            $project_percentage = 0;
                            if ($contract_days > 0) { $project_percentage = round(($project_days / $contract_days) * 100); }
                            $project_percentage .= "%";

                            $temp = [];
                            $temp["id"] = $id_div;
                            $temp["export_id"] = $employee_id;
                            $temp["name"] = $lname.", ".$fname;

                            // build the primary department column
                            if ($dept != null) { $temp["primary_department"] = $dept; }
                            else { $temp["primary_department"] = "<span class='missing-field'>No primary department assigned</span>"; }

                            $temp["num_of_pays"] = $num_of_pays;
                            if (isset($fund_code) && $fund_code <> "") { $temp["fund_code"] = $fund_code." E"; } else { $temp["fund_code"] = "<span class='missing-field'>Missing</span>"; }
                            if (isset($location_code) && $location_code <> "") { $temp["location_code"] = $location_code; } else { $temp["location_code"] = "<span class='missing-field'>Missing</span>"; }
                            if (isset($object_code) && $object_code <> "") { $temp["object_code"] = $object_code; } else { $temp["object_code"] = "<span class='missing-field'>Missing</span>"; }
                            if (isset($function_code) && $function_code <> "") { $temp["function_code"] = $function_code; } else { $temp["function_code"] = "<span class='missing-field'>Missing</span>"; }
                            $temp["project_code"] = $code_div;
                            $temp["project_name"] = $project_name;
                            $temp["contract_days"] = $contract_days;
                            $temp["project_days"] = $project_days;
                            $temp["project_percentage"] = $project_percentage;
                            $temp["budgeted_days"] = $total_budgeted_days;
                            $temp["days_diff"] = $total_budgeted_days - $contract_days;
                            $temp["export_project_code"] = $project_code;
                            $masterData[] = $temp;
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