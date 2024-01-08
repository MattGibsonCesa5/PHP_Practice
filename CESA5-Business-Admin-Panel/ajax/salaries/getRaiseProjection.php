<?php
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1) 
    { 
        // initialize array to store all employees current and projected compensation
        $master = [];

        // get additional required files
        include("../../includes/config.php");
        include("../../includes/functions.php");
        include("../../getSettings.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "VIEW_RAISE_PROJECTION"))
        {
            // get the required POST parameters 
            if (isset($_POST["rate"]) && is_numeric($_POST["rate"])) { $rate = $_POST["rate"]; } else { $rate = 0; }
            if (isset($_POST["period"]) && is_numeric($_POST["period"])) { $period = $_POST["period"]; } else { $period = null; }

            if ($rate >= 0 && $period != null)
            {
                if (verifyPeriod($conn, $period))
                {
                    // get active period details
                    $active_period = getPeriodDetails($conn, $GLOBAL_SETTINGS["active_period"]);
                    $active_period_label = $active_period["name"];

                    // get future period details
                    $future_period = getPeriodDetails($conn, $period);
                    $future_period_label = $future_period["name"];

                    $getEmployees = mysqli_query($conn, "SELECT id, fname, lname FROM employees WHERE status=1 ORDER BY lname ASC, fname ASC, id ASC");
                    if (mysqli_num_rows($getEmployees) > 0) // employees exist; continue
                    {
                        while ($employee = mysqli_fetch_array($getEmployees))
                        {
                            // store the employee details locally
                            $employee_id = $employee["id"];
                            $fname = $employee["fname"];
                            $lname = $employee["lname"];

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

                            // get the employee's total costs for the current active period
                            $active_salary = $active_days = 0; // initialize and assume 0
                            $getActiveComp = mysqli_prepare($conn, "SELECT yearly_rate, contract_days, health_insurance, dental_insurance, wrs_eligible FROM employee_compensation WHERE employee_id=? AND period_id=?");
                            mysqli_stmt_bind_param($getActiveComp, "ii", $employee_id, $GLOBAL_SETTINGS["active_period"]);
                            if (mysqli_stmt_execute($getActiveComp))
                            {
                                $getActiveCompResult = mysqli_stmt_get_result($getActiveComp);
                                if (mysqli_num_rows($getActiveCompResult) > 0) // compensation for active period found
                                {
                                    // store the active period's compensation locally
                                    $active_compensation = mysqli_fetch_array($getActiveCompResult);
                                    $active_salary = $active_compensation["yearly_rate"];
                                    $active_days = $active_compensation["contract_days"];
                                }
                            }
                            $active_health = getEmployeeHealthCosts($conn, $employee_id, $GLOBAL_SETTINGS["active_period"]);
                            $active_dental = getEmployeeDentalCosts($conn, $employee_id, $GLOBAL_SETTINGS["active_period"]);
                            $active_wrs = getEmployeeWRSCosts($conn, $employee_id, $GLOBAL_SETTINGS["active_period"], $active_salary);
                            $active_fica = getEmployeeFICACosts($conn, $employee_id, $GLOBAL_SETTINGS["active_period"], $active_salary);
                            $active_ltd = getEmployeeLTDCosts($conn, $employee_id, $GLOBAL_SETTINGS["active_period"], $active_salary);
                            $active_life = getEmployeeLifeCosts($conn, $employee_id, $GLOBAL_SETTINGS["active_period"], $active_salary);
                            $active_fringe = $active_health + $active_dental + $active_wrs + $active_fica + $active_ltd + $active_life;

                            // get the employees compensation for the current future period
                            $future_salary = $future_days = 0; // initialize and assume 0
                            $future_salary = $active_salary * (1 + ($rate / 100)); // calculate the future projected salary based on rate provided
                            $getFutureComp = mysqli_prepare($conn, "SELECT yearly_rate, contract_days, health_insurance, dental_insurance, wrs_eligible FROM employee_compensation WHERE employee_id=? AND period_id=?");
                            mysqli_stmt_bind_param($getFutureComp, "ii", $employee_id, $period);
                            if (mysqli_stmt_execute($getFutureComp))
                            {
                                $getFutureCompResult = mysqli_stmt_get_result($getFutureComp);
                                if (mysqli_num_rows($getFutureCompResult) > 0) // compensation for future period found
                                {
                                    // store the future period's compensation locally
                                    $future_compensation = mysqli_fetch_array($getFutureCompResult);
                                    // $future_salary = $future_compensation["yearly_rate"];
                                    $future_days = $future_compensation["contract_days"];
                                }
                            }
                            $future_health = getEmployeeHealthCosts($conn, $employee_id, $period);
                            $future_dental = getEmployeeDentalCosts($conn, $employee_id, $period);
                            $future_wrs = getEmployeeWRSCosts($conn, $employee_id, $period, $future_salary);
                            $future_fica = getEmployeeFICACosts($conn, $employee_id, $period, $future_salary);
                            $future_ltd = getEmployeeLTDCosts($conn, $employee_id, $period, $future_salary);
                            $future_life = getEmployeeLifeCosts($conn, $employee_id, $period, $future_salary);
                            $future_fringe = $future_health + $future_dental + $future_wrs + $future_fica + $future_ltd + $future_life;
                        
                            // calculate the current active period's total compensation
                            $active_total_comp = $active_salary + $active_fringe;

                            // calculate the future period's total compensation
                            $future_total_comp = $future_salary + $future_fringe;

                            // store the data in the temporary array
                            $temp = [];

                            // store visible data
                            $temp["id"] = $employee_id;
                            $temp["lname"] = $lname;
                            $temp["fname"] = $fname;
                            $temp["department"] = $department;
                            $temp["active_days"] = $active_days;
                            $temp["active_salary"] = printDollar($active_salary);
                            $temp["projected_days"] = $future_days;
                            $temp["projected_salary"] = printDollar($future_salary);
                            $temp["active_fica"] = printDollar($active_fica);
                            $temp["active_health"] = printDollar($active_health);
                            $temp["active_dental"] = printDollar($active_dental);
                            $temp["active_wrs"] = printDollar($active_wrs);
                            $temp["active_ltd"] = printDollar($active_ltd);
                            $temp["active_life"] = printDollar($active_life);
                            $temp["active_fringe"] = printDollar($active_fringe);
                            $temp["projected_fica"] = printDollar($future_fica);
                            $temp["projected_health"] = printDollar($future_health);
                            $temp["projected_dental"] = printDollar($future_dental);
                            $temp["projected_wrs"] = printDollar($future_wrs);
                            $temp["projected_ltd"] = printDollar($future_ltd);
                            $temp["projected_life"] = printDollar($future_life);
                            $temp["projected_fringe"] = printDollar($future_fringe);
                            $temp["active_total"] = printDollar($active_total_comp);
                            $temp["projected_total"] = printDollar($future_total_comp);
                            
                            // store data used for calculations
                            $temp["calc_active_days"] = $active_days;
                            $temp["calc_active_salary"] = $active_salary;
                            $temp["calc_projected_days"] = $future_days;
                            $temp["calc_projected_salary"] = $future_salary;
                            $temp["calc_active_fica"] = $active_fica;
                            $temp["calc_active_health"] = $active_health;
                            $temp["calc_active_dental"] = $active_dental;
                            $temp["calc_active_wrs"] = $active_wrs;
                            $temp["calc_active_ltd"] = $active_ltd;
                            $temp["calc_active_life"] = $active_life;
                            $temp["calc_active_fringe"] = $active_fringe;
                            $temp["calc_projected_fica"] = $future_fica;
                            $temp["calc_projected_health"] = $future_health;
                            $temp["calc_projected_dental"] = $future_dental;
                            $temp["calc_projected_wrs"] = $future_wrs;
                            $temp["calc_projected_ltd"] = $future_ltd;
                            $temp["calc_projected_life"] = $future_life;
                            $temp["calc_projected_fringe"] = $future_fringe;
                            $temp["calc_active_total"] = $active_total_comp;
                            $temp["calc_projected_total"] = $future_total_comp;

                            // add temporary array to master array
                            $master[] = $temp;
                        }
                    }
                }
            }
        }
        
        // disconnect from the database
        mysqli_close($conn);

        // return data
        $fullData = [];
        $fullData["draw"] = 1;
        $fullData["data"] = $master;
        echo json_encode($fullData);
    }
?>