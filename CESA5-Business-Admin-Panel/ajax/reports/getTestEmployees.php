<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get additional required files
        include("../../includes/config.php");
        include("../../includes/functions.php");
        include("../../getSettings.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        // initialize the array to store data
        $testEmployees = [];

        if (checkUserPermission($conn, "VIEW_REPORT_TEST_EMPLOYEES_ALL") || checkUserPermission($conn, "VIEW_REPORT_TEST_EMPLOYEES_ASSIGNED"))
        {
            // store other permissions locally
            $can_user_budget_all = checkUserPermission($conn, "BUDGET_PROJECTS_ALL");
            $can_user_budget_assigned = checkUserPermission($conn, "BUDGET_PROJECTS_ASSIGNED");

            // build and prepare the query to get test employees based on user permissions
            if (checkUserPermission($conn, "VIEW_REPORT_TEST_EMPLOYEES_ALL"))
            {
                // get all test employees across all projects
                $getTestEmployees = mysqli_prepare($conn, "SELECT pem.*, p.name FROM project_employees_misc pem 
                                                        JOIN projects p ON pem.project_code=p.code 
                                                        WHERE period_id=?");
                mysqli_stmt_bind_param($getTestEmployees, "i", $GLOBAL_SETTINGS["active_period"]);
            }
            else if (checkUserPermission($conn, "VIEW_REPORT_TEST_EMPLOYEES_ASSIGNED"))
            {
                // get all test employees across in the user's assigned projects
                $getTestEmployees = mysqli_prepare($conn, "SELECT pem.*, p.name FROM project_employees_misc pem 
                                                        JOIN projects p ON pem.project_code=p.code
                                                        JOIN departments d ON p.department_id=d.id
                                                        WHERE pem.period_id=? AND (d.director_id=? OR d.secondary_director_id=?)");
                mysqli_stmt_bind_param($getTestEmployees, "iii", $GLOBAL_SETTINGS["active_period"], $_SESSION["id"], $_SESSION["id"]);
            }

            // execute the query to get test employees
            if (mysqli_stmt_execute($getTestEmployees))
            {
                $getTestEmployeesResults = mysqli_stmt_get_result($getTestEmployees);
                if (mysqli_num_rows($getTestEmployeesResults) > 0) // test employees found
                {
                    while ($testEmployee = mysqli_fetch_array($getTestEmployeesResults))
                    {
                        // store the test employee's data locally
                        $auto_id = $testEmployee["id"];
                        $employee_id = $testEmployee["employee_id"];
                        $label = $testEmployee["employee_label"];
                        $project_code = $testEmployee["project_code"];
                        $project_name = $testEmployee["name"];
                        $project_days = $testEmployee["project_days"];
                        $inclusion = $testEmployee["costs_inclusion"];

                        // build the actions column
                        $actions = "";
                        if ($can_user_budget_all === true || $can_user_budget_assigned === true)
                        {
                            $actions = "<div class='row justify-content-center'>
                                <div class='col-sm-12 col-md-12 col-lg-6 col-xl-6 col-xxl-4 p-1'><button class='btn btn-primary w-100' onclick='getRemoveTestEmployeeFromProjectModal($auto_id, \"$project_code\");'><i class='fa-solid fa-trash-can'></i></button></div>
                                <div class='col-sm-12 col-md-12 col-lg-6 col-xl-6 col-xxl-4 p-1'><button class='btn btn-primary w-100' onclick='toggleInclusion($auto_id, \"$project_code\");' title='Toggle cost inclusion.'><i class='fa-solid fa-calculator'></i></button></div>
                            </div>";
                        }

                        // build the temporary array
                        $temp = [];
                        $temp["id"] = $employee_id;
                        $temp["label"] = $label;
                        $temp["project_code"] = $project_code;
                        $temp["project_name"] = $project_name;
                        $temp["project_days"] = $project_days;
                        $temp["inclusion"] = $inclusion;
                        $temp["actions"] = $actions;
                        $testEmployees[] = $temp;
                    }
                }
            }
        }

        // send data to be printed
        $fullData = [];
        $fullData["draw"] = 1;
        $fullData["data"] = $testEmployees;
        echo json_encode($fullData);

        // disconnect from the database
        mysqli_close($conn);
    }
?>