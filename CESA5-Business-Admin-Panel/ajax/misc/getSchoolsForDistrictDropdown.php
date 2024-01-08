<?php
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get additional required files
        include("../../includes/config.php");
        include("../../includes/functions.php");

        // get the period from POST
        if (isset($_POST["district_id"]) && $_POST["district_id"] <> "") { $district_id = $_POST["district_id"]; } else { $district_id = null; }

        if ($district_id != null)
        {
            // connect to the database
            $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

            // add the blank option
            echo "<option selected disabled value></option>";

            // get schools for the district
            echo "<optgroup label='District Schools'>";
                $getSchools = mysqli_prepare($conn, "SELECT id, name FROM schools WHERE district_id=? ORDER BY name ASC");
                mysqli_stmt_bind_param($getSchools, "i", $district_id);
                if (mysqli_stmt_execute($getSchools))
                {
                    $getSchoolsResults = mysqli_stmt_get_result($getSchools);
                    if (mysqli_num_rows($getSchoolsResults) > 0) // schools found for district
                    {
                        while ($school = mysqli_fetch_array($getSchoolsResults))
                        {
                            // store school details locally
                            $school_id = $school["id"];
                            $school_name = $school["name"];

                            // add the school to the dropdown
                            echo "<option value='".$school_id."'>".$school_name."</option>";
                        }
                    }
                }
            echo "</optgroup>";

            // get CESA 5 schools only if CESA 5 was not the selected district
            if ($district_id != 0) 
            {
                echo "<optgroup label='CESA 5 Programs'>";
                    $getCESASchools = mysqli_query($conn, "SELECT id, name FROM schools WHERE district_id=0 ORDER BY name ASC");
                    if (mysqli_num_rows($getCESASchools) > 0)
                    {
                        while ($school = mysqli_fetch_array($getCESASchools))
                        {
                            // store school details locally
                            $school_id = $school["id"];
                            $school_name = $school["name"];

                            // add the school to the dropdown
                            echo "<option value='".$school_id."'>".$school_name."</option>";
                        }
                    }
                echo "</optgroup>";
            }

            // add option for others
            echo "<optgroup label='Other'>
                <option value='-1'>Other</option>
                <option value='-2'>External Tutor</option>
                <option value='-3'>Home</option>
            </optgroup>";

            // disconnect from the database
            mysqli_close($conn);
        }
        else { echo "<option></option>"; }
    }
?>