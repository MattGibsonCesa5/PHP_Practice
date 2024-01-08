<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get additional required files
        include("../../includes/functions.php");
        include("../../includes/config.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "ADD_STUDENTS"))
        {
            // get parameters from POST
            if (isset($_POST["fname"]) && trim($_POST["fname"]) <> "") { $fname = trim($_POST["fname"]); } else { $fname = null; }
            if (isset($_POST["lname"]) && trim($_POST["lname"]) <> "") { $lname = trim($_POST["lname"]); } else { $lname = null; }
            if (isset($_POST["date_of_birth"]) && $_POST["date_of_birth"] <> "") { $date_of_birth = $_POST["date_of_birth"]; } else { $date_of_birth = null; }
            if (isset($_POST["status"]) && is_numeric($_POST["status"])) { $status = $_POST["status"]; } else { $status = 0; }

            // verify the student does not already exist
            if ($student_id = checkForStudent($conn, $fname, $lname, $date_of_birth))
            {
                // if student_id returned is -1, student does not already exist, create the new student
                if ($student_id == -1)
                {
                    // add the student
                    addStudent($conn, $fname, $lname, $status, $date_of_birth);
                }
                else { echo "<span class=\"log-fail\">Failed</span> to add the new student as the student you are trying to add already exists!<br>"; }
            }
            else { echo "<span class=\"log-fail\">Failed</span> to add the new student. An unexpected error has occurred! Please try again later.<br>"; }
        }
        else { echo "Your account does not have permission to perform this action.<br>"; }

        // disconnect from the database
        mysqli_close($conn);
    }
?>