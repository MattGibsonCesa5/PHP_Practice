<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get additional required files
        include("../../includes/config.php");
        include("../../includes/functions.php");
        include("../../getSettings.php");

        // initialize array to store all active employees
        $employees = [];

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "EDIT_DEPARTMENTS"))
        {
            // get department ID from POST
            if (isset($_POST["department_id"]) && $_POST["department_id"] <> "") { $department_id = $_POST["department_id"]; } else { $department_id = null; }

            // get a list of all active employees
            // get a list of all employees
            $getEmployees = mysqli_prepare($conn, "SELECT e.id, e.fname, e.lname, ec.active FROM employees e JOIN employee_compensation ec ON e.id=ec.employee_id WHERE ec.period_id=?");
            mysqli_stmt_bind_param($getEmployees, "i", $GLOBAL_SETTINGS["active_period"]);
            if (mysqli_stmt_execute($getEmployees))
            {
                $getEmployeesResults = mysqli_stmt_get_result($getEmployees);
                if (mysqli_num_rows($getEmployeesResults) > 0) 
                { 
                    while ($employee = mysqli_fetch_array($getEmployeesResults)) 
                    { 
                        // initialize temporary variables
                        $temp = [];
                        $isMember = 0; // assume employee is not a member of the department

                        // check to see if the employee is a member of the department or not
                        $checkMembership = mysqli_prepare($conn, "SELECT id FROM department_members WHERE department_id=? AND employee_id=?");
                        mysqli_stmt_bind_param($checkMembership, "ii", $department_id, $employee["id"]);
                        if (mysqli_stmt_execute($checkMembership))
                        {
                            $membershipResult = mysqli_stmt_get_result($checkMembership);
                            if (mysqli_num_rows($membershipResult) > 0) { $isMember = 1; }
                        }

                        // build the ID column
                        $id_div = "<div class='my-1'><span class='text-nowrap'>".$employee["id"]."</span>"; // initialize div
                        if ($employee["active"] == 1) { $id_div .= "<div class='active-div text-center px-3 py-1 float-end'>Active</div>"; }
                        else { $id_div .= "<div class='inactive-div text-center px-3 py-1 float-end'>Inactive</div>"; } 
                        $id_div .= "</div>";
                    
                        // build temporary employee array; add employee to master array
                        $temp["id"] = $employee["id"];
                        $temp["id_display"] = $id_div;
                        $temp["fname"] = $employee["fname"];
                        $temp["lname"] = $employee["lname"];
                        $temp["isMember"] = $isMember;
                        $employees[] = $temp;
                    } 
                }
            }
        }
        
        // disconnect from the database
        mysqli_close($conn);

        // return data
        $fullData = [];
        $fullData["draw"] = 1;
        $fullData["data"] = $employees;
        echo json_encode($fullData);
    }
?>