<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get additional required files
        include("../../includes/config.php");
        include("../../includes/functions.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "VIEW_CASELOADS_ALL"))
        {
            // get the student ID from POST
            if (isset($_POST["school_id"]) && $_POST["school_id"] <> "") { $school_id = $_POST["school_id"]; } else { $school_id = null; }
            if (isset($_POST["school_name"]) && $_POST["school_name"] <> "") { $school_name = $_POST["school_name"]; } else { $school_name = null; }

            if (verifySchool($conn, $school_id))
            {
                if ($school_name != null)
                {
                    $editSchool = mysqli_prepare($conn, "UPDATE schools SET name=? WHERE id=?");
                    mysqli_stmt_bind_param($editSchool, "si", $school_name, $school_id);
                    if (mysqli_stmt_execute($editSchool)) { echo "<span class=\"log-success\">Successfully</span> edited the school!<br>"; }
                    else { echo "<span class=\"log-fail\">Failed</span> to edit the school. An unexpected error has occurred! Please try again later.<br>"; }
                }
                else { echo "<span class=\"log-fail\">Failed</span> to edit the school. The name cannot be blank!<br>"; }
            }
            else { echo "<span class=\"log-fail\">Failed</span> to edit the school. The school selected does not exist!<br>"; }
        }

        // disconnect from the database
        mysqli_close($conn);
    }
?>