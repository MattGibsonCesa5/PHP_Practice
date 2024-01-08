<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // initialize array to store all schools
        $schools = [];

        // get additional required files
        include("../../includes/config.php");
        include("../../includes/functions.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "VIEW_CASELOADS_ALL"))
        {
            $getSchools = mysqli_query($conn, "SELECT s.id AS school_id, s.name AS school_name, s.grade_group, d.id AS district_id, d.name AS district_name FROM schools s
                                                JOIN customers d ON s.district_id=d.id
                                                ORDER BY d.name ASC");
            if (mysqli_num_rows($getSchools) > 0)
            {
                while ($school = mysqli_fetch_array($getSchools))
                {
                    // store details locally
                    $school_id = $school["school_id"];
                    $school_name = $school["school_name"];
                    $district_id = $school["district_id"];
                    $district_name = $school["district_name"];
                    $grade_group = $school["grade_group"];

                    // build the actions column
                    $actions = "<div class='d-flex justify-content-end'>
                        <button class='btn btn-primary btn-sm mx-1' type='button' onclick='getEditSchoolModal(".$school_id.");'><i class='fa-solid fa-pencil'></i></button>
                    </div>";

                    // build temparary array of data to send
                    $temp = [];
                    $temp["district"] = $district_name;
                    $temp["school"] = $school_name;
                    $temp["grade_group"] = $grade_group;
                    $temp["actions"] = $actions;
                    $temp["district_id"] = $district_id;

                    // add school to master array
                    $schools[] = $temp;
                }
            }
        }

        // disconnect from the database
        mysqli_close($conn);

        // send data to be printed
        $fullData = [];
        $fullData["draw"] = 1;
        $fullData["data"] = $schools;
        echo json_encode($fullData);
    }
?>