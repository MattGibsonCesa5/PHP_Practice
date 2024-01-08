<?php
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get additional required files
        include("../../includes/config.php");
        include("../../includes/functions.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "DELETE_DEPARTMENTS"))
        {
            // get department ID from POST
            if (isset($_POST["department_id"]) && $_POST["department_id"] <> "") { $department_id = $_POST["department_id"]; } else { $department_id = null; }

            // check to see if department exists
            $checkDept = mysqli_prepare($conn, "SELECT id FROM departments WHERE id=?");
            mysqli_stmt_bind_param($checkDept, "i", $department_id);
            if (mysqli_stmt_execute($checkDept))
            {
                $checkDeptResult = mysqli_stmt_get_result($checkDept);
                if (mysqli_num_rows($checkDeptResult) > 0) // department exists; continue
                {
                    // delete the department
                    $deleteDepartment = mysqli_prepare($conn, "DELETE FROM departments WHERE id=?");
                    mysqli_stmt_bind_param($deleteDepartment, "i", $department_id);
                    if (mysqli_stmt_execute($deleteDepartment)) // successfully delete the department; delete other data associated to this department
                    {
                        echo "<span class=\"log-success\">Successfully</span> deleted the department.<br>";

                        // delete the members associated to the deleted department
                        $deleteDepartmentMembers = mysqli_prepare($conn, "DELETE FROM department_members WHERE department_id=?");
                        mysqli_stmt_bind_param($deleteDepartmentMembers, "i", $department_id);
                        if (!mysqli_stmt_execute($deleteDepartmentMembers)) { echo "<span class=\"log-fail\">Failed</span> to delete the department memberships associated with the department.<br>"; }

                        // unassign the department from any projects
                        $unassignProjects = mysqli_prepare($conn, "UPDATE projects SET department_id=0 WHERE department_id=?");
                        mysqli_stmt_bind_param($unassignProjects, "i", $department_id);
                        if (!mysqli_stmt_execute($unassignProjects)) { echo "<span class=\"log-fail\">Failed</span> to unassign this department from any projects it was assigned to.<br>"; }

                        // log department deletion
                        $message = "Successfully deleted the department with ID of $department_id. ";
                        $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                        mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                        mysqli_stmt_execute($log);
                    }
                    else { echo "<span class=\"log-fail\">Failed</span> to delete the department.<br>"; }
                }
                else { echo "<span class=\"log-fail\">Failed</span> to delete the department. The department you are trying to delete does not exist!<br>"; }
            }
            else { echo "<span class=\"log-fail\">Failed</span> to delete the department. An unexpected error has occurred. Please try again later!<br>"; }
        }
        else { echo "<span class=\"log-fail\">Failed</span> to delete the department. Your account does not have permission to delete departments!<br>"; }

        // disconnect from the database
        mysqli_close($conn);
    }
?>