<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get additional required files
        include("../../includes/config.php");
        include("../../includes/functions.php");
        
        // initialize array to store projects
        $projects = [];

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        // get period name from POST
        if (isset($_POST["period"]) && $_POST["period"] <> "") { $period = $_POST["period"]; } else { $period = null; }

        if ($period != null && $period_id = getPeriodID($conn, $period)) // verify the period exists; if it exists, store the period ID
        {
            if (checkUserPermission($conn, "VIEW_PROJECTS_ALL") || checkUserPermission($conn, "VIEW_PROJECTS_ASSIGNED"))
            {
                // store user permissions for managing projects locally
                $can_user_edit = checkUserPermission($conn, "EDIT_PROJECTS");
                $can_user_delete = checkUserPermission($conn, "DELETE_PROJECTS");

                // build and prepare the query to get projects based on the user's permissions
                if (checkUserPermission($conn, "VIEW_PROJECTS_ALL")) // view all projects
                { 
                    $getProjects = mysqli_prepare($conn, "SELECT p.*, d.name AS department_name FROM projects p
                                                            LEFT JOIN departments d ON p.department_id=d.id"); 
                }
                else if (checkUserPermission($conn, "VIEW_PROJECTS_ASSIGNED")) // view only assigned projects
                {
                    $getProjects = mysqli_prepare($conn, "SELECT p.*, d.name AS department_name FROM projects p
                                                        JOIN departments d ON p.department_id=d.id
                                                        WHERE d.director_id=? OR d.secondary_director_id=?");
                    mysqli_stmt_bind_param($getProjects, "ii", $_SESSION["id"], $_SESSION["id"]);
                }

                // execute the query to get a list of projects
                if (mysqli_stmt_execute($getProjects))
                {
                    $getProjectsResults = mysqli_stmt_get_result($getProjects);
                    while ($project = mysqli_fetch_array($getProjectsResults))
                    {
                        // build the name div
                        $status = getProjectStatus($conn, $project["code"], $period_id);
                        $name_div = "<div class='my-1'>
                            <form class='w-100' method='POST' action='projects_budget.php'>
                                <input type='hidden' id='project_code' name='project_code' value='".$project["code"]."' aria-hidden='true'>
                                <input type='hidden' id='period_id' name='period_id' value='".$period_id."' aria-hidden='true'>
                                <button class='btn btn-link btn-therapist_caseload text-start text-nowrap w-100' type='submit'>
                                    ".$project["name"];
                                    if ($status == 1) { $name_div .= "<div class='active-div text-center px-3 py-1 float-end'>Active</div>"; }
                                    else { $name_div .= "<div class='inactive-div text-center px-3 py-1 float-end'>Inactive</div>"; }
                                $name_div .= "</button>
                            </form>
                        </div>";
                        
                        // build the project code div
                        $code_div = getProjectLink($project["code"], $period_id, true);

                        // get the department name from the department ID
                        if (isset($project["department_name"]) && trim($project["department_name"]) <> "") { $dept_name = $project["department_name"]; } else {$dept_name = "<span class=\"missing-field\">Unassigned</span>"; }

                        $temp = [];
                        $temp["code"] = $code_div;
                        $temp["export_code"] = $project["code"];
                        if (isset($project["fund_code"])) { $temp["fund"] = $project["fund_code"]; } else { $temp["fund"] = "<span class=\"missing-field\">Missing</span>"; }
                        if (isset($project["function_code"])) { $temp["func"] = $project["function_code"]; } else { $temp["func"] = "<span class=\"missing-field\">Missing</span>"; }
                        $temp["name"] = $name_div;
                        $temp["export_name"] = $project["name"];
                        $temp["department"] = $dept_name;
                        $temp["description"] = $project["description"];

                        // calculate the project's net income
                        $revenue = getProjectsTotalRevenue($conn, $project["code"], $period_id);
                        $expenses = getProjectsTotalExpenses($conn, $project["code"], $period_id);
                        $net = $revenue - $expenses;

                        $temp["revenues"] = printDollar($revenue,);
                        $temp["expenses"] = printDollar($expenses);
                        $temp["net"] = printDollar($net);
                        $temp["revenues_calc"] = round($revenue, 2);
                        $temp["expenses_calc"] = round($expenses, 2);
                        $temp["net_calc"] = round($net, 2);

                        // get the number of employees in the project
                        $employees_count = 0; // assume no employees are in the project
                        $getEmployeesCount = mysqli_prepare($conn, "SELECT COUNT(employee_id) AS num_of_emps FROM project_employees WHERE project_code=? AND period_id=?");
                        mysqli_stmt_bind_param($getEmployeesCount, "si", $project["code"], $period_id);
                        if (mysqli_stmt_execute($getEmployeesCount))
                        {
                            $getEmployeesCountResult = mysqli_stmt_get_result($getEmployeesCount);
                            if (mysqli_num_rows($getEmployeesCountResult) > 0) // employees found
                            {
                                $employees_count = mysqli_fetch_array($getEmployeesCountResult)["num_of_emps"];
                            }
                        }
                        $temp["employees_count"] = $employees_count;

                        // build the actions column
                        $actions = "<div class='d-flex justify-content-end'>";
                            if ($can_user_edit === true) { $actions .= "<button class='btn btn-primary btn-sm mx-1' type='button' onclick='getEditProjectModal(\"".$project["code"]."\");'><i class='fa-solid fa-pencil'></i></button>"; }
                            if ($can_user_delete === true) { $actions .= "<button class='btn btn-danger btn-sm mx-1' type='button' onclick='getDeleteProjectModal(\"".$project["code"]."\");'><i class='fa-solid fa-trash-can'></i></button>"; }
                        $actions .= "</div>";
                        $temp["actions"] = $actions;

                        // build the status column to be filtered by
                        $filter_status = "";
                        if ($status == 1) { $filter_status = "Active"; }
                        else { $filter_status = "Inactive"; }
                        $temp["status"] = $filter_status;

                        $projects[] = $temp;
                    }
                }
            }
        }

        // disconnect from the database
        mysqli_close($conn);

        // send data to be printed
        $fullData = [];
        $fullData["draw"] = 1;
        $fullData["data"] = $projects;
        echo json_encode($fullData);
    }
?>