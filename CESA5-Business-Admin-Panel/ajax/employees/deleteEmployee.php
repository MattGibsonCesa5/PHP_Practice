<?php
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get additional required files
        include("../../includes/functions.php");
        include("../../includes/config.php");
        include("../../getSettings.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "DELETE_EMPLOYEES"))
        {
            // get employee ID from POST
            if (isset($_POST["employee_id"]) && $_POST["employee_id"] <> "") { $employee_id = $_POST["employee_id"]; } else { $employee_id = null; }

            // verify the employee exists
            $checkEmployee = mysqli_prepare($conn, "SELECT id, address_id FROM employees WHERE id=?");
            mysqli_stmt_bind_param($checkEmployee, "i", $employee_id);
            if (mysqli_stmt_execute($checkEmployee))
            {
                $checkEmployeeResult = mysqli_stmt_get_result($checkEmployee);
                if (mysqli_num_rows($checkEmployeeResult) > 0) // employee exists; continue deletion process
                {
                    $employee = mysqli_fetch_array($checkEmployeeResult);

                    // get the employee's address ID
                    $address_id = $employee["address_id"];

                    // delete the employee
                    $deleteEmployee = mysqli_prepare($conn, "DELETE FROM employees WHERE id=?");
                    mysqli_stmt_bind_param($deleteEmployee, "i", $employee_id);
                    if (mysqli_stmt_execute($deleteEmployee)) // successfully deleted the employee; continue deletion process
                    {
                        // delete all employee's compensation
                        $deleteCompensation = mysqli_prepare($conn, "DELETE FROM employee_compensation WHERE employee_id=?");
                        mysqli_stmt_bind_param($deleteCompensation, "i", $employee_id);
                        if (mysqli_stmt_execute($deleteCompensation)) { echo "<span class=\"log-success\">Successfully</span> removed all of the employee's benefits and compensation. "; }
                        else { echo "<span class=\"log-fail\">Failed</span> to remove the deleted employee's benefits and compensation. "; }

                        // delete the employee from the active period's projects
                        $removeFromProjects = mysqli_prepare($conn, "DELETE FROM project_employees WHERE employee_id=? AND period_id=?");
                        mysqli_stmt_bind_param($removeFromProjects, "ii", $employee_id, $GLOBAL_SETTINGS["active_period"]);
                        if (mysqli_stmt_execute($removeFromProjects)) { echo "<span class=\"log-success\">Successfully</span> remove the deleted emplyoee from the active period's projects. "; }
                        else { echo "<span class=\"log-fail\">Failed</span> to remove the deleted employee from the active period's projects. "; }

                        // delete the employee's address
                        $deleteEmployeeAddress = mysqli_prepare($conn, "DELETE FROM employee_addresses WHERE employee_id=? AND id=?");
                        mysqli_stmt_bind_param($deleteEmployeeAddress, "ii", $employee_id, $address_id);
                        if (!mysqli_stmt_execute($deleteEmployeeAddress)) { echo "<span class=\"log-fail\">Failed</span> to delete the employee's address. "; }

                        // remove the employee from their department(s)
                        $removeFromDepartments = mysqli_prepare($conn, "DELETE FROM department_members WHERE employee_id=?");
                        mysqli_stmt_bind_param($removeFromDepartments, "i", $employee_id);
                        if (mysqli_stmt_execute($removeFromDepartments)) { echo "<span class=\"log-success\">Successfully</span> removed the deleted employee from their department(s). "; }
                        else { echo "<span class=\"log-fail\">Failed</span> to remove the deleted employee from their department(s). "; }

                        // log employee deletion
                        $message = "Successfully deleted the employee with the ID of $employee_id. ";
                        $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                        mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                        mysqli_stmt_execute($log);
                    }
                    else { echo "<span class=\"log-fail\">Failed</span> to delete the employee. An unknown error has occurred. Please try again later. "; }
                }
                else { echo "<span class=\"log-fail\">Failed</span> to delete the employee. The employee selected does not exist! "; }
            }
        }
        else { echo "<span class=\"log-fail\">Failed</span> to delete the employee. Your account does not have access to delete employees.<br>"; }
        
        // disconnect from the database
        mysqli_close($conn);
    }
?>