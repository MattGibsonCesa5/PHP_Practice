<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // initialize array to store data to be printed
        $masterData = [];

        // get additional required files
        include("../../includes/config.php");
        include("../../includes/functions.php");
        include("../../getSettings.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "VIEW_REPORT_BUDGETED_INACTIVE_EMPLOYEES_ALL") || checkUserPermission($conn, "VIEW_REPORT_BUDGETED_INACTIVE_EMPLOYEES_ASSIGNED"))
        {
            // store other permissions locally
            $can_user_budget_all = checkUserPermission($conn, "BUDGET_PROJECTS_ALL");
            $can_user_budget_assigned = checkUserPermission($conn, "BUDGET_PROJECTS_ASSIGNED");

            // get the period from POST
            if (isset($_POST["period"]) && $_POST["period"] <> "") { $period = $_POST["period"]; } else { $period = null; }

            // verify the period exists; if it exists, store the period ID
            if ($period != null && $period_id = getPeriodID($conn, $period))
            {
                // build and prepare the query depending on user role
                if (checkUserPermission($conn, "VIEW_REPORT_BUDGETED_INACTIVE_EMPLOYEES_ALL")) 
                { 
                    $getEmployees = mysqli_prepare($conn, "SELECT e.id, e.fname, e.lname, ec.contract_days, pe.id AS record, pe.project_code, p.name AS project_name, pe.project_days, 
                                                                (SELECT d.name FROM departments d JOIN department_members dm ON d.id=dm.department_id WHERE dm.is_primary=1 AND dm.employee_id=e.id) AS primary_department, 
                                                                (SELECT SUM(pesq.project_days) FROM project_employees pesq WHERE pesq.employee_id=e.id AND pesq.period_id=?) AS budgeted_days 
                                                            FROM employees e
                                                            JOIN employee_compensation ec ON e.id=ec.employee_id
                                                            JOIN project_employees pe ON e.id=pe.employee_id
                                                            JOIN projects p ON pe.project_code=p.code
                                                            WHERE pe.period_id=? AND ec.period_id=? AND ec.active=0"); 
                    mysqli_stmt_bind_param($getEmployees, "iii", $period_id, $period_id, $period_id);
                }
                else if (checkUserPermission($conn, "VIEW_REPORT_BUDGETED_INACTIVE_EMPLOYEES_ASSIGNED")) 
                { 
                    $getEmployees = mysqli_prepare($conn, "SELECT e.id, e.fname, e.lname, ec.contract_days, pe.id AS record, pe.project_code, p.name AS project_name, pe.project_days, dm.department_id, d.name AS primary_department, dm.is_primary FROM employees e
                                                            JOIN employee_compensation ec ON e.id=ec.employee_id
                                                            JOIN project_employees pe ON e.id=pe.employee_id
                                                            JOIN projects p ON pe.project_code=p.code
                                                            JOIN department_members dm ON e.id=dm.employee_id
                                                            JOIN departments d ON dm.department_id=d.id
                                                            WHERE pe.period_id=? AND ec.period_id=? AND (d.director_id=? OR d.secondary_director_id=?) AND ec.active=0");
                    mysqli_stmt_bind_param($getEmployees, "iiii", $period_id, $period_id, $_SESSION["id"], $_SESSION["id"]);
                }

                // execute the query
                if (mysqli_stmt_execute($getEmployees))
                {
                    $getEmployeesResults = mysqli_stmt_get_result($getEmployees);
                    if (mysqli_num_rows($getEmployeesResults) > 0)
                    {
                        while ($employee = mysqli_fetch_array($getEmployeesResults))
                        {
                            // store employee data in local vars
                            $id = $employee["id"];
                            $fname = $employee["fname"];
                            $lname = $employee["lname"];
                            $contract_days = $employee["contract_days"];
                            $project_code = $employee["project_code"];
                            $project_name = $employee["project_name"];
                            $project_days = $employee["project_days"];
                            $record = $employee["record"];

                            // build the ID / status column
                            $id_div = "<div class='my-1'><span class='text-nowrap'>$id</span><div class='inactive-div text-center px-3 py-1 float-end'>Inactive</div></div>";

                            // build the project code div
                            $code_div = getProjectLink($project_code, $period_id, true);

                            // build the actions column
                            $actions = "";
                            if ($can_user_budget_all === true || $can_user_budget_assigned === true)
                            {
                                $actions .= "<button class='btn btn-danger btn-sm mx-1' onclick='getRemoveEmployeeFromProjectModal(".$id.", \"".$project_code."\", ".$record.");'><i class='fa-solid fa-trash-can'></i> Remove From Project</button>";
                            }

                            $temp = [];
                            $temp["id"] = $id_div;
                            $temp["fname"] = $fname;
                            $temp["lname"] = $lname;
                            $temp["project_code"] = $code_div;
                            $temp["project_name"] = $project_name;
                            $temp["contract_days"] = $contract_days;
                            $temp["project_days"] = $project_days;
                            $temp["status"] = "Inactive";
                            $temp["actions"] = $actions;
                            $masterData[] = $temp;
                        }
                    }
                }
            }
        }

        // disconnect from the database
        mysqli_close($conn);

        // send data to be printed
        $fullData = [];
        $fullData["draw"] = 1;
        $fullData["data"] = $masterData;
        echo json_encode($fullData);
    }
?>