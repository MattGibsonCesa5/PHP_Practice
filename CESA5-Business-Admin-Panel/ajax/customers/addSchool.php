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
            // get form parameters
            if (isset($_POST["district_id"]) && $_POST["district_id"] <> "") { $district_id = $_POST["district_id"]; } else { $district_id = null; }
            if (isset($_POST["school_name"]) && $_POST["school_name"] <> "") { $school_name = $_POST["school_name"]; } else { $school_name = null; }
            if (isset($_POST["grade_group"]) && $_POST["grade_group"] <> "") { $grade_group = $_POST["grade_group"]; } else { $grade_group = null; }

            if (verifyCustomer($conn, $district_id))
            {
                if ($school_name != null)
                {
                    if ($grade_group != null && ($grade_group == "Elementary School" || $grade_group == "High School" || $grade_group == "Combined Elementary/Secondary School" || $grade_group == "Middle School" || $grade_group == "Junior High School"))
                    {
                        $addSchool = mysqli_prepare($conn, "INSERT INTO schools (name, grade_group, district_id) VALUES (?, ?, ?)");
                        mysqli_stmt_bind_param($addSchool, "ssi", $school_name, $grade_group, $district_id);
                        if (mysqli_stmt_execute($addSchool)) { echo "<span class=\"log-success\">Successfully</span> added the school!<br>"; }
                        else { echo "<span class=\"log-fail\">Failed</span> to add the school. An unexpected error has occurred! Please try again later.<br>"; }
                    }
                    else { echo "<span class=\"log-fail\">Failed</span> to add the school. The grade group selected was invalid!<br>"; }
                }
                else { echo "<span class=\"log-fail\">Failed</span> to add the school. The name cannot be blank!<br>"; }
            }
            else { echo "<span class=\"log-fail\">Failed</span> to add the school. The district selected does not exist!<br>"; }
        }

        // disconnect from the database
        mysqli_close($conn);
    }
?>