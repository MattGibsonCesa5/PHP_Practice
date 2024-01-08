<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get additional required files
        include("../../includes/functions.php");
        include("../../includes/config.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "DELETE_STUDENTS"))
        {
            // get the student ID from POST
            if (isset($_POST["student_id"]) && trim($_POST["student_id"]) <> "") { $student_id = trim($_POST["student_id"]); } else { $student_id != null; }

            if ($student_id != null)
            {
                // delete the student
                deleteStudent($conn, $student_id);
            }
            else { echo "<span class=\"log-fail\">Failed</span> to delete the student. No student was selected!<br>"; }
        }
        
        // disconnect from the database
        mysqli_close($conn);
    }
?>