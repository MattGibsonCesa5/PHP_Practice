<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // initialize array to hold the classrooms
        $classrooms = [];

        // get additional required files
        include("../../includes/functions.php");
        include("../../includes/config.php");
        include("../../getSettings.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "VIEW_CASELOADS_ALL"))
        {
            $getClassrooms = mysqli_query($conn, "SELECT * FROM caseload_classrooms");
            if (mysqli_num_rows($getClassrooms) > 0) // classrooms found
            {
                while ($classroom = mysqli_fetch_array($getClassrooms))
                {
                    // store classroom details locally
                    $id = $classroom["id"];
                    $name = $classroom["name"];
                    $category_id = $classroom["category_id"];
                    $service_id = $classroom["service_id"];

                    // get the name of the classroom's category
                    $category_name = getCaseloadCategoryName($conn, $category_id);

                    // get the number of students within the classroom for the current active period
                    $num_of_students = 0; // initialize student count to 0
                    $getStudentCount = mysqli_prepare($conn, "SELECT COUNT(student_id) AS num_of_students FROM cases WHERE classroom_id=? AND period_id=?");
                    mysqli_stmt_bind_param($getStudentCount, "ii", $id, $GLOBAL_SETTINGS["active_period"]);
                    if (mysqli_stmt_execute($getStudentCount))
                    {
                        $getStudentCountResult = mysqli_stmt_get_result($getStudentCount);
                        if (mysqli_num_rows($getStudentCountResult) > 0)
                        {
                            $num_of_students = mysqli_fetch_array($getStudentCountResult)["num_of_students"];
                        }
                    }

                    // get the name of the service
                    $service_name = getServiceName($conn, $service_id);

                    // build the actions column
                    $actions = "";

                    // build the temporary array of data
                    $temp = [];
                    $temp["classroom_id"] = $id;
                    $temp["name"] = $name;
                    $temp["category"] = $category_name;
                    $temp["num_of_students"] = $num_of_students;
                    $temp["service_id"] = $service_id;
                    $temp["service_name"] = $service_name;
                    $temp["actions"] = $actions;

                    // add the temporary array to the master classrooms array
                    $classrooms[] = $temp;
                }
            }
        }

        // return data
        $fullData = [];
        $fullData["draw"] = 1;
        $fullData["data"] = $classrooms;
        echo json_encode($fullData);

        // disconnect from the database
        mysqli_close($conn);
    }
?>