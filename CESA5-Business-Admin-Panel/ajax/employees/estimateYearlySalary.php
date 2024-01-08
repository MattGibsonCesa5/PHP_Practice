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

        if (checkUserPermission($conn, "VIEW_EMPLOYEES_ASSIGNED"))
        {
            // get POST parameters
            if (isset($_POST["employee_id"]) && $_POST["employee_id"] <> "") { $employee_id = $_POST["employee_id"]; } else { $employee_id = null; }
            if (isset($_POST["period_id"]) && $_POST["period_id"] <> "") { $period_id = $_POST["period_id"]; } else { $period_id = null; }
            if (isset($_POST["days"]) && is_numeric($_POST["days"])) { $days = $_POST["days"]; } else { $days = null; }

            // verify days is over 0
            if ($days > 0)
            {
                // verify period exists
                if (verifyPeriod($conn, $period_id))
                {
                    // verify the employee exists
                    if (checkExistingEmployee($conn, $employee_id))
                    {
                        // verify the director has access to the employee
                        if (verifyUserEmployee($conn, $_SESSION["id"], $employee_id))
                        {
                            // get the employee's yearly salary and contract days for the period selected
                            $salary = getEmployeeSalary($conn, $employee_id, $period_id);
                            $contract_days = getEmployeeContractDays($conn, $employee_id, $period_id);

                            // calculate the employee's current daily rate
                            $daily_rate = $salary / $contract_days;

                            // estimate the new yearly salary based on new contract days
                            $estimated_salary = $daily_rate * $days;

                            // format the salary
                            $estimated_salary = number_format($estimated_salary, 2);

                            // return the estimated salary
                            echo $estimated_salary;
                        }
                        else { echo number_format(0, 2); }
                    }
                    else { echo number_format(0, 2); }
                }
                else { echo number_format(0, 2); }
            }
            else { echo number_format(0, 2); }
        }
        else { echo number_format(0, 2); }
    }
    else { echo number_format(0, 2); }
?>