<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // initialize array to store departments
        $departments = [];

        // get additional required files
        include("../../includes/config.php");
        include("../../includes/functions.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "VIEW_DEPARTMENTS_ALL") || checkUserPermission($conn, "VIEW_DEPARTMENTS_ASSIGNED"))
        {
            // build and prepare the query to get departments based on role permissions
            if (checkUserPermission($conn, "VIEW_DEPARTMENTS_ALL")) { $getDepartments = mysqli_prepare($conn, "SELECT * FROM departments"); }
            else if (checkUserPermission($conn, "VIEW_DEPARTMENTS_ASSIGNED")) 
            { 
                $getDepartments = mysqli_prepare($conn, "SELECT * FROM departments WHERE (director_id=? OR secondary_director_id=?)");
                mysqli_stmt_bind_param($getDepartments, "ii", $_SESSION["id"], $_SESSION["id"]);
            }

            // execute the query to get departments
            if (mysqli_stmt_execute($getDepartments))
            {
                $getDepartmentsResult = mysqli_stmt_get_result($getDepartments);
                if (mysqli_num_rows($getDepartmentsResult) > 0)
                {
                    while ($department = mysqli_fetch_array($getDepartmentsResult))
                    {
                        // store the department details locally
                        $department_id = $department["id"];
                        $primary_director_id = $department["director_id"];
                        $secondary_director_id = $department["secondary_director_id"];

                        // build the directors column
                        $directors_div = "";
                        // build primary director display
                        if (isset($primary_director_id)) { $primary_director_name = "<span>".getUserDisplayName($conn, $primary_director_id)."</span>";  } 
                        else { $primary_director_name = "<span class=\"missing-field\">No primary director assigned</span>"; }
                        // build secondary director display
                        if (isset($secondary_director_id)) { $secondary_director_name = "<span>".getUserDisplayName($conn, $secondary_director_id)."</span>"; } 
                        else { $secondary_director_name = "<span class=\"missing-field\">No secondary director assigned</span>"; }
                        // build director div
                        $directors_div .= "<div><b>Primary: </b>".$primary_director_name."</div>";
                        $directors_div .= "<div><b>Secondary: </b>".$secondary_director_name."</div>";

                        // get the number of employees in the department
                        $employee_count = 0; // initialize employee count
                        $getEmployeeCount = mysqli_prepare($conn, "SELECT COUNT(id) AS employee_count FROM department_members WHERE department_id=?");
                        mysqli_stmt_bind_param($getEmployeeCount, "i", $department_id);
                        if (mysqli_stmt_execute($getEmployeeCount))
                        {
                            $employeeCountResult = mysqli_stmt_get_result($getEmployeeCount);
                            if (mysqli_num_rows($employeeCountResult) > 0) { $employee_count = mysqli_fetch_array($employeeCountResult)["employee_count"]; }
                            else { $employee_count = 0; }
                        }

                        // create the view department employees button
                        if ($employee_count > 0) { $view_employees = "<button type='button' class='btn btn-primary w-100' onclick='getViewDepartmentModal(".$department_id.");'>View ". $employee_count ." department members</button>"; }
                        else { $view_employees = "<button type='button' class='btn btn-primary w-100' disabled>". $employee_count ." department members</button>"; }
                        
                        // build the actions column
                        $actions = "<div class='d-flex justify-content-end'>";
                            if (checkUserPermission($conn, "EDIT_DEPARTMENTS")) { $actions .= "<button class='btn btn-primary btn-sm mx-1' type='button' onclick='getEditDepartmentModal(".$department_id.");'><i class='fa-solid fa-pencil'></i></button>"; }
                            if (checkUserPermission($conn, "DELETE_DEPARTMENTS")) { $actions .= "<button class='btn btn-danger btn-sm mx-1' type='button' onclick='getDeleteDepartmentModal(".$department_id.");'><i class='fa-solid fa-trash-can'></i></button>"; }
                        $actions .= "</div>";

                        // build the temporary array to store department details to be displayed
                        $temp = [];
                        $temp["id"] = $department["id"];
                        $temp["name"] = $department["name"];
                        $temp["description"] = $department["description"];
                        $temp["directors"] = $directors_div;
                        $temp["employees_count"] = $employee_count;
                        $temp["view_employees"] = $view_employees;
                        $temp["actions"] = $actions;

                        // append department to master array
                        $departments[] = $temp;
                    }
                }
            }
        }
        
        // disconnect from the database
        mysqli_close($conn);

        // send data to be printed
        $fullData = [];
        $fullData["draw"] = 1;
        $fullData["data"] = $departments;
        echo json_encode($fullData);
    }
?>