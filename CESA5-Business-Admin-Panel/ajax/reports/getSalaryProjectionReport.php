<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // initialize the array to store employees listed in the report
        $reportEmployees = []; 

        // get additional required files
        include("../../includes/config.php");
        include("../../includes/functions.php");
        include("../../getSettings.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "VIEW_REPORT_SALARY_PROJECTION_ALL") || checkUserPermission($conn, "VIEW_REPORT_SALARY_PROJECTION_ASSIGNED"))
        {
            // get the salary projection rate from the database
            $salary_projection_rate = 1; // assume projection rate is 0% (1)
            $getSalaryProjectionRate = mysqli_query($conn, "SELECT salary_projection_rate FROM settings WHERE id=1");
            if (mysqli_num_rows($getSalaryProjectionRate) > 0)
            {
                $salary_projection_rate_value = mysqli_fetch_array($getSalaryProjectionRate)["salary_projection_rate"];
                $salary_projection_rate = (($salary_projection_rate_value / 100) + 1);
            }

            // build and prepare the query to get employees based on the user's permissions
            if (checkUserPermission($conn, "VIEW_REPORT_SALARY_PROJECTION_ALL"))
            {
                $getEmployees = mysqli_prepare($conn, "SELECT e.id, e.fname, e.lname, ec.yearly_rate, ec.contract_days, ec.assignment_position, ec.sub_assignment, ec.experience, ec.highest_degree FROM employees e
                                                    JOIN employee_compensation ec ON e.id=ec.employee_id
                                                    WHERE ec.period_id=?");
                mysqli_stmt_bind_param($getEmployees, "i", $GLOBAL_SETTINGS["active_period"]);
            }
            else if (checkUserPermission($conn, "VIEW_REPORT_SALARY_PROJECTION_ASSIGNED"))
            {
                $getEmployees = mysqli_prepare($conn, "SELECT e.id, e.fname, e.lname, ec.yearly_rate, ec.contract_days, ec.assignment_position, ec.sub_assignment, ec.experience, ec.highest_degree FROM employees e
                                                    JOIN employee_compensation ec ON e.id=ec.employee_id
                                                    JOIN department_members dm ON e.id=dm.employee_id
                                                    JOIN departments d ON dm.department_id=d.id
                                                    WHERE ec.period_id=? AND (d.director_id=? OR d.secondary_director_id=?)");
                mysqli_stmt_bind_param($getEmployees, "iii", $GLOBAL_SETTINGS["active_period"], $_SESSION["id"], $_SESSION["id"]);
            }

            // execute the query to get the employee list
            if (mysqli_stmt_execute($getEmployees))
            {
                $getEmployeesResults = mysqli_stmt_get_result($getEmployees);
                if (mysqli_num_rows($getEmployeesResults) > 0) // employee found
                {
                    while ($employee = mysqli_fetch_array($getEmployeesResults))
                    {
                        // store the employee details locally
                        $id = $employee["id"];
                        $fname = $employee["fname"];
                        $lname = $employee["lname"];
                        $salary = $employee["yearly_rate"];
                        $days = $employee["contract_days"];
                        $position = $employee["assignment_position"];
                        $area = $employee["sub_assignment"];
                        $experience = $employee["experience"];
                        $degree = $employee["highest_degree"];

                        // get the employee's primary department
                        $department = "";
                        $getDepartment = mysqli_prepare($conn, "SELECT d.name FROM departments d JOIN department_members dm ON d.id=dm.department_id WHERE dm.is_primary=1 AND dm.employee_id=?");
                        mysqli_stmt_bind_param($getDepartment, "i", $id);
                        if (mysqli_stmt_execute($getDepartment))
                        {
                            $getDepartmentResult = mysqli_stmt_get_result($getDepartment);
                            if (mysqli_num_rows($getDepartmentResult) > 0) // primary department found
                            {
                                $department = mysqli_fetch_array($getDepartmentResult)["name"];
                            }
                        }

                        // calculate the employee's daily salary
                        $daily_salary = 0;
                        if ($days > 0) { $daily_salary = $salary / $days; }

                        // get all DPI reported employees, whose salary is greater than 0, with matching details
                        $cummulative_contract_days = $cummulative_projected_salary = $avg_contract_days = $avg_projected_salary = $dpi_employee_count = 0; // initialize variables
                        $getDPIEmployees = mysqli_prepare($conn, "SELECT total_salary, total_experience, contract_days FROM dpi_employees 
                                                                WHERE assignment_position=? AND assignment_area=? AND contract_high_degree=? AND total_salary>0");
                        mysqli_stmt_bind_param($getDPIEmployees, "sss", $position, $area, $degree);
                        if (mysqli_stmt_execute($getDPIEmployees))
                        {
                            $getDPIEmployeesResults = mysqli_stmt_get_result($getDPIEmployees);
                            if (mysqli_num_rows($getDPIEmployeesResults) > 0) // employees with same parameters found
                            {
                                while ($dpi_employee = mysqli_fetch_array($getDPIEmployeesResults))
                                {
                                    // store the total salary of the DPI employee locally
                                    $dpi_employee_salary = $dpi_employee["total_salary"];
                                    $dpi_employee_total_experience = $dpi_employee["total_experience"];
                                    $dpi_employee_contract_days = $dpi_employee["contract_days"];

                                    if (($experience >= 16 && $dpi_employee_total_experience >= 16) || ($experience == $dpi_employee_total_experience))
                                    {
                                        // increase the DPI employee salary by our set salary projection rate
                                        $dpi_employee_projected_salary = ($dpi_employee_salary * $salary_projection_rate);

                                        /// add the contract days to the cummulative total
                                        $cummulative_contract_days += $dpi_employee_contract_days;

                                        // add the projected salary to the cummulative total
                                        $cummulative_projected_salary += $dpi_employee_projected_salary;
                                        
                                        // increment counter
                                        $dpi_employee_count++;
                                    }
                                }
                            }
                        }

                        // get the average projected yearly and daily salaries
                        $avg_projected_daily_salary = 0;
                        if ($dpi_employee_count > 0) 
                        { 
                            $avg_projected_salary = $cummulative_projected_salary / $dpi_employee_count; 
                            $avg_contract_days = $cummulative_contract_days / $dpi_employee_count;
                            if ($avg_contract_days > 0) { $avg_projected_daily_salary = ($avg_projected_salary / $avg_contract_days); }
                        }

                        // if our employee's rate is less than average, calculate % we need to increase and add the employee to the report
                        $match_yearly_rate = $match_daily_rate = 0;
                        if ($salary > 0) // if the employee's salary is greater than 0 (prevents divide by 0 error)
                        {
                            // calculate the % we need to increase the employee's salary to match the average
                            $match_yearly_rate = ((($avg_projected_salary - $salary) / $salary) * 100);
                        }

                        if ($daily_salary > 0) // if the employee's daily salary is greater than 0 (prevents divide by 0 error)
                        {
                            // calculate the % we need to increase the employee's salary to match the average
                            $match_daily_rate = ((($avg_projected_daily_salary - $daily_salary) / $daily_salary) * 100);
                        }

                        if ($daily_salary > 0) // if the employee's salary is greater than 0 (prevents divide by 0 error)
                        {
                            // build the dpi assignment div
                            $dpi_assignment = "<div class='card text-white bg-secondary w-100 m-0'>
                                <div class='row g-0'>
                                    <div class='col-12'>
                                        <div class='card-body px-2 py-0'>
                                            <h5 class='card-title my-1'>$position</h5>
                                            <h6 class='my-1'>$area</h6>
                                        </div>
                                    </div> 
                                </div>
                            </div>";

                            // build temporary array to store employee
                            $temp = [];
                            $temp["id"] = $id;
                            $temp["fname"] = $fname;
                            $temp["lname"] = $lname;
                            $temp["days"] = $days;
                            $temp["position"] = $position;
                            $temp["area"] = $area;
                            $temp["dpi_assignment"] = $dpi_assignment;
                            $temp["experience"] = $experience;
                            $temp["degree"] = $degree;
                            $temp["yearly_salary"] = printDollar($salary);
                            $temp["average_projected_position_yearly_salary"] = printDollar($avg_projected_salary)." (".$dpi_employee_count.")";
                            $temp["daily_salary"] = printDollar($daily_salary);
                            $temp["average_projected_position_daily_salary"] = printDollar($avg_projected_daily_salary)." (".$dpi_employee_count.")";
                            $temp["yearly_rate_increase"] = round($match_yearly_rate, 2)."%";
                            $temp["daily_rate_increase"] = round($match_daily_rate, 2)."%";
                            $temp["department"] = $department;
                            $temp["position"] = $position;
                            $temp["area"] = $area;
                            
                            // build the info column
                            $info = "<button class='btn btn-secondary w-100' type='button' onclick='getDetailsModal(".$id.");'><i class='fa-solid fa-info'></i></button>";
                            $temp["info"] = $info;
                            
                            // add employee to report
                            $reportEmployees[] = $temp;
                        }
                    }
                }
            }
        }

        // send data to be printed
        $fullData = [];
        $fullData["draw"] = 1;
        $fullData["data"] = $reportEmployees;
        echo json_encode($fullData);

        // disconnect from the database
        mysqli_close($conn);
    }
?>