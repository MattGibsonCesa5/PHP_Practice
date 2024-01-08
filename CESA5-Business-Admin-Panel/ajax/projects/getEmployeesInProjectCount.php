<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // initialize the variable to store the number of employees in project for the given period
        $numOfEmployees = 0;

        // get the required files
        include("../../includes/config.php");
        include("../../includes/functions.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "VIEW_PROJECT_BUDGETS_ALL") || checkUserPermission($conn, "VIEW_PROJECT_BUDGETS_ASSIGNED"))
        {
            // get the parameters from POST
            if (isset($_POST["code"]) && $_POST["code"] <> "") { $code = $_POST["code"]; } else { $code = null; }
            if (isset($_POST["period"]) && $_POST["period"] <> "") { $period = $_POST["period"]; } else { $period = null; }

            if ($code != null && $period != null)
            {
                // connect to the database
                $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

                if ($period_id = getPeriodID($conn, $period)) // verify the period exists; if it exists, store the period ID
                {
                    if (verifyProject($conn, $code)) // verify the project exists
                    {
                        $getEmployeesCount = mysqli_prepare($conn, "SELECT id FROM project_employees WHERE project_code=? AND period_id=?");
                        mysqli_stmt_bind_param($getEmployeesCount, "si", $code, $period_id);
                        if (mysqli_stmt_execute($getEmployeesCount))
                        {
                            $getEmployeesCountResults = mysqli_stmt_get_result($getEmployeesCount);
                            $numOfEmployees = mysqli_num_rows($getEmployeesCountResults);
                        }
                    }
                }
            }
        }

        // disconnect from the database
        mysqli_close($conn);
        
        // echo the directors string to return
        echo $numOfEmployees;
    }
?>