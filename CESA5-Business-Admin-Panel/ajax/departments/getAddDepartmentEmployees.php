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

        if (checkUserPermission($conn, "ADD_DEPARTMENTS"))
        {
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

                        $employees[] = $temp;
                    } 
                }
            }
        }
        
        // return data
        $fullData = [];
        $fullData["draw"] = 1;
        $fullData["data"] = $employees;
        echo json_encode($fullData);
        
        // disconnect from the database
        mysqli_close($conn);
    }
?>