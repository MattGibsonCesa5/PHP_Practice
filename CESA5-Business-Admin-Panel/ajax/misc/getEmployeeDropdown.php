<?php
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        if (isset($_SESSION["role"]) && ($_SESSION["role"] == 1 || $_SESSION["role"] == 2))
        {
            // get additional required files
            include("../../includes/config.php");
            include("../../includes/functions.php");

            // get the period from POST
            if (isset($_POST["period"]) && $_POST["period"] <> "") { $period = $_POST["period"]; } else { $period = null; }

            if ($period != null)
            {
                // connect to the database
                $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

                // if the period is valid, create the modal
                if ($period_id = getPeriodID($conn, $period))
                {
                    ?>
                        <option></option>
                        <?php
                            if ($_SESSION["role"] == 1) // admin list - create a dropdown of all active employees
                            { 
                                $getEmployees = mysqli_prepare($conn, "SELECT e.id, e.fname, e.lname, ec.contract_days FROM employees e
                                                                    JOIN employee_compensation ec ON e.id=ec.employee_id
                                                                    WHERE ec.active=1 AND ec.period_id=?
                                                                    ORDER BY lname ASC, fname ASC"); 
                                mysqli_stmt_bind_param($getEmployees, "i", $period_id);
                                if (mysqli_stmt_execute($getEmployees))
                                {
                                    $getEmployeesResults = mysqli_stmt_get_result($getEmployees);
                                    if (mysqli_num_rows($getEmployeesResults) > 0) // employees found
                                    {
                                        while ($employee = mysqli_fetch_array($getEmployeesResults))
                                        {
                                            $id = $employee["id"];
                                            $fname = $employee["fname"];
                                            $lname = $employee["lname"];
                                            $name = $lname . ", " . $fname;
                                            $days = $employee["contract_days"];
                                            echo "<option value=".$id.">".$name." (".$days.")</option>";
                                        }
                                    }
                                }
                            }
                            else if ($_SESSION["role"] == 2) // director list - create a dropdown of all active employees in their department(s)
                            { 
                                $getEmployees = mysqli_prepare($conn, "SELECT e.id, e.fname, e.lname, e.contract_days FROM employees e 
                                                                    JOIN employee_compensation ec ON e.id=ec.employee_id
                                                                    JOIN department_members dm ON e.id=dm.employee_id 
                                                                    JOIN departments d ON d.id=dm.department_id 
                                                                    WHERE ec.active=1 AND ec.period_id=? AND ((d.director_id=? OR d.secondary_director_id=?) OR e.global=1) 
                                                                    ORDER BY e.lname ASC, e.fname ASC"); 
                                mysqli_stmt_bind_param($getEmployees, "iii", $period_id, $_SESSION["id"], $_SESSION["id"]);
                                if (mysqli_stmt_execute($getEmployees))
                                {
                                    $getEmployeesResults = mysqli_stmt_get_result($getEmployees);
                                    if (mysqli_num_rows($getEmployeesResults))
                                    {
                                        while ($employee = mysqli_fetch_array($getEmployeesResults))
                                        {
                                            $id = $employee["id"];
                                            $fname = $employee["fname"];
                                            $lname = $employee["lname"];
                                            $name = $lname . ", " . $fname;
                                            $days = $employee["contract_days"];
                                            echo "<option value=".$id.">".$name." (".$days.")</option>";
                                        }
                                    }
                                }
                            }
                        ?>
                    <?php
                }

                // disconnect from the database
                mysqli_close($conn);
            }
        }
    }
?>