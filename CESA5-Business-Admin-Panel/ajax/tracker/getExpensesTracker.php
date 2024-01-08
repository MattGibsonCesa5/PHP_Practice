<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        if (isset($_SESSION["role"]) && $_SESSION["role"] == 1)
        {
            // get required additional files
            include("../../includes/config.php");
            include("../../includes/functions.php");
            
            // initialize an array to store expenses
            $total_revenues = [];

            // connect to the database
            $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

            // get all periods
            $getPeriods = mysqli_query($conn, "SELECT id, name FROM periods");
            if (mysqli_num_rows($getPeriods) > 0) // periods found; continue
            {
                while ($period = mysqli_fetch_array($getPeriods))
                {
                    // store the period details locally
                    $period_id = $period["id"];
                    $period_name = $period["name"];

                    // initialize variables
                    $salary = $health = $dental = $wrs = $fica = $ltd = $life = 0;

                    $project_expenses = getTotalProjectExpenses($conn, $period_id);

                    $getEmployees = mysqli_query($conn, "SELECT id, fname, lname FROM employees WHERE status=1 ORDER BY lname ASC, fname ASC, id ASC");
                    if (mysqli_num_rows($getEmployees) > 0) // employees exist; continue
                    {
                        while ($employee = mysqli_fetch_array($getEmployees))
                        {
                            // store the employee details locally
                            $employee_id = $employee["id"];

                            // get the employee's total costs for the current active period
                            $days = 0; // initialize and assume 0
                            $getComp = mysqli_prepare($conn, "SELECT yearly_rate, contract_days, health_insurance, dental_insurance, wrs_eligible FROM employee_compensation WHERE employee_id=? AND period_id=?");
                            mysqli_stmt_bind_param($getComp, "ii", $employee_id, $period_id);
                            if (mysqli_stmt_execute($getComp))
                            {
                                $getCompResult = mysqli_stmt_get_result($getComp);
                                if (mysqli_num_rows($getCompResult) > 0) // compensation for active period found
                                {
                                    // store the active period's compensation locally
                                    $compensation = mysqli_fetch_array($getCompResult);
                                    $salary += $compensation["yearly_rate"];
                                    $days = $compensation["contract_days"];
                                }
                            }
                            $health += getEmployeeHealthCosts($conn, $employee_id, $period_id);
                            $dental += getEmployeeDentalCosts($conn, $employee_id, $period_id);
                            $wrs += getEmployeeWRSCosts($conn, $employee_id, $period_id, $salary);
                            $fica += getEmployeeFICACosts($conn, $employee_id, $period_id, $salary);
                            $ltd += getEmployeeLTDCosts($conn, $employee_id, $period_id, $salary);
                            $life += getEmployeeLifeCosts($conn, $employee_id, $period_id, $salary);
                        }
                    }

                    // create the array to store data
                    $period_array = [$period_name, $project_expenses, $salary, $health, $dental, $wrs, $fica, $ltd, $life, ""];
                    $total_revenues[] = $period_array;
                }
            }

            // disconnect from the database
            mysqli_close($conn);

            echo json_encode($total_revenues);
        }
    }
?>