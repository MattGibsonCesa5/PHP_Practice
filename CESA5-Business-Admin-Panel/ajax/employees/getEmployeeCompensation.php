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

        if (checkUserPermission($conn, "VIEW_EMPLOYEES_ASSIGNED") || checkUserPermission($conn, "VIEW_EMPLOYEES_ALL"))
        {
            // get POST parameters
            if (isset($_POST["employee_id"]) && $_POST["employee_id"] <> "") { $employee_id = $_POST["employee_id"]; } else { $employee_id = null; }
            if (isset($_POST["period_id"]) && $_POST["period_id"] <> "") { $period_id = $_POST["period_id"]; } else { $period_id = null; }

            // verify period exists
            if (verifyPeriod($conn, $period_id))
            {
                // verify the employee exists
                if (checkExistingEmployee($conn, $employee_id))
                {
                    // verify the director has access to the employee
                    if (verifyUserEmployee($conn, $_SESSION["id"], $employee_id))
                    {
                        // get the employee's compensation
                        $salary = getEmployeeSalary($conn, $employee_id, $period_id);
                        $days = getEmployeeContractDays($conn, $employee_id, $period_id);

                        // calculate the employee's daily rate
                        $daily_rate = 0;
                        if ($days > 0) { $daily_rate = $salary / $days; }

                        // build the array to return
                        $comp = [];
                        $comp["salary"] = number_format($salary, 2);
                        $comp["days"] = $days;
                        $comp["daily"] = number_format($daily_rate, 2);

                        // return the array
                        echo json_encode($comp);
                    }
                }
            }
        }
    }
?>