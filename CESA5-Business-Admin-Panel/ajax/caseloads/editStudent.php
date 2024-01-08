<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get additional required files
        include("../../includes/functions.php");
        include("../../includes/config.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "EDIT_STUDENTS"))
        {
            // get parameters from POST
            if (isset($_POST["id"]) && $_POST["id"] <> "") { $id = $_POST["id"]; } else { $id = null; }
            if (isset($_POST["fname"]) && trim($_POST["fname"]) <> "") { $fname = trim($_POST["fname"]); } else { $fname = null; }
            if (isset($_POST["lname"]) && trim($_POST["lname"]) <> "") { $lname = trim($_POST["lname"]); } else { $lname = null; }
            if (isset($_POST["date_of_birth"]) && $_POST["date_of_birth"] <> "") { $date_of_birth = $_POST["date_of_birth"]; } else { $date_of_birth = null; }
            if (isset($_POST["status"]) && is_numeric($_POST["status"])) { $status = $_POST["status"]; } else { $status = 0; }

            // add the student
            editStudent($conn, $id, $fname, $lname, $status, $date_of_birth);
        }
        
        // disconnect from the database
        mysqli_close($conn);
    }
?>