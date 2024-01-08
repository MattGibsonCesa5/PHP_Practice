<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get additional required files
        include("../../includes/config.php");
        include("../../includes/functions.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "EDIT_DEPARTMENTS"))
        {
            // get parameters from POST
            if (isset($_POST["department_id"]) && $_POST["department_id"] <> "") { $department_id = $_POST["department_id"]; } else { $department_id = null; }
            if (isset($_POST["name"]) && $_POST["name"] <> "") { $name = $_POST["name"]; } else { $name = null; }
            if (isset($_POST["desc"]) && $_POST["desc"] <> "") { $desc = $_POST["desc"]; } else { $desc = null; }
            if (isset($_POST["director_id"]) && $_POST["director_id"] <> "") { $director_id = $_POST["director_id"]; } else { $director_id = null; }
            if (isset($_POST["secondary_director"]) && $_POST["secondary_director"] <> "") { $secondary_director = $_POST["secondary_director"]; } else { $secondary_director = null; }
            if (isset($_POST["employees"]) && $_POST["employees"] <> "") { $employees = json_decode($_POST["employees"]); } else { $employees = null; }

            // initialize the variable to store the error message
            $error_msg = "";

            // verify the department and ID are set
            if ($name != null && $department_id != null)
            {
                // check to see if department exists
                $checkDept = mysqli_prepare($conn, "SELECT id FROM departments WHERE id=?");
                mysqli_stmt_bind_param($checkDept, "i", $department_id);
                if (mysqli_stmt_execute($checkDept))
                {
                    $checkDeptResult = mysqli_stmt_get_result($checkDept);
                    if (mysqli_num_rows($checkDeptResult) > 0) // department exists; continue
                    {
                        // primary director selected; verify director before creating department
                        if ($director_id != null) 
                        {
                            if (!verifyUser($conn, $director_id))
                            {
                                $director_id = null;
                                $error_msg .= "Failed to assign the primary director. The director does not exist!<br>";
                            }
                        }

                        // secondary director selected; verify director before creating department
                        if ($secondary_director != null) 
                        {
                            if (!verifyUser($conn, $secondary_director))
                            {
                                $secondary_director = null;
                                $error_msg .= "Failed to assign the secondary director. The director does not exist!<br>";
                            }
                        }

                        // prepare and execute the query to edit the department
                        $editDepartment = mysqli_prepare($conn, "UPDATE departments SET name=?, description=?, director_id=?, secondary_director_id=? WHERE id=?");
                        mysqli_stmt_bind_param($editDepartment, "ssiii", $name, $desc, $director_id, $secondary_director, $department_id);
                        if (mysqli_stmt_execute($editDepartment))
                        {
                            // clear all current department members and re-assign selected employees to the department
                            $clearMembers = mysqli_prepare($conn, "DELETE FROM department_members WHERE department_id=?");
                            mysqli_stmt_bind_param($clearMembers, "i", $department_id);
                            if (mysqli_stmt_execute($clearMembers)) // successfully cleared department members
                            {
                                // add selected employees into the department
                                if ($employees != null && is_array($employees))
                                {
                                    for ($e = 0; $e < count($employees); $e++)
                                    {
                                        $employee_id = $employees[$e];

                                        // verify the employee exists
                                        $checkEmployee = mysqli_prepare($conn, "SELECT id FROM employees WHERE id=?");
                                        mysqli_stmt_bind_param($checkEmployee, "i", $employee_id);
                                        if (mysqli_stmt_execute($checkEmployee))
                                        {
                                            $checkEmployeeResult = mysqli_stmt_get_result($checkEmployee);
                                            if (mysqli_num_rows($checkEmployeeResult) > 0) // employee exists; add employee to department
                                            {
                                                $addEmployee = mysqli_prepare($conn, "INSERT INTO department_members (department_id, employee_id) VALUES (?, ?)");
                                                mysqli_stmt_bind_param($addEmployee, "ii", $department_id, $employee_id);
                                                mysqli_stmt_execute($addEmployee);
                                            }
                                        }    
                                    }
                                }
                            }

                            // print to screen that we edited the department
                            echo "<span class=\"log-success\">Successfully</span> edited the department.<br>";
                            if ($error_msg <> "") { echo $error_msg; }

                            // log department edit
                            $message = "Successfully edited the department with ID of $department_id. ";
                            $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                            mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                            mysqli_stmt_execute($log);
                        }
                        else { echo "<span class=\"log-fail\">Failed</span> to edit the department.<br>"; }
                    }
                    else { echo "<span class=\"log-fail\">Failed</span> to edit the department. The department you are trying to edit does not exist!<br>"; }
                }
                else { echo "<span class=\"log-fail\">Failed</span> to edit the department. An unexpected error has occurred. Please try again later!<br>"; }
            }  
        }
        else { echo "<span class=\"log-fail\">Failed</span> to edit the department. Your account does not have permission to edit departments!<br>"; }
        
        // disconnect from the database
        mysqli_close($conn);
    }
?>