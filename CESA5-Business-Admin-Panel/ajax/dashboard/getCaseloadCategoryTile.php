<?php
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // initialize response
        $response_code = 0; // default to error
        $response_body = ""; // empty string

        // get additional required files
        include("../../includes/config.php");
        include("../../includes/functions.php");
        include("../../getSettings.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "VIEW_CASELOADS_ALL") && checkUserPermission($conn, "VIEW_STUDENTS_ALL") && checkUserPermission($conn, "VIEW_THERAPISTS"))
        {
            // get category ID from POST
            if (isset($_POST["category_id"])) { $category_id = $_POST["category_id"]; } else { $category_id = null; }
            
            // verify category
            if ($category_id != null && verifyCaseloadCategory($conn, $category_id))
            {
                $getCategorySettings = mysqli_prepare($conn, "SELECT * FROM caseload_categories WHERE id=?");
                mysqli_stmt_bind_param($getCategorySettings, "i", $category_id);
                if (mysqli_stmt_execute($getCategorySettings))
                {
                    $getCategorySettingsResults = mysqli_stmt_get_result($getCategorySettings);
                    if (mysqli_num_rows($getCategorySettingsResults) > 0)
                    {
                        // store category settings locally
                        $category_settings = mysqli_fetch_assoc($getCategorySettingsResults);
                        $category_name = $category_settings["name"];
                        $is_classroom = $category_settings["is_classroom"];
                        $uos_enabled = $category_settings["uos_enabled"];
                        $service_id = $category_settings["service_id"];

                        // get all caseloads for the category
                        $num_of_caseloads = $total_units = 0; // initialize category caseload totals
                        $getCaseloads = mysqli_prepare($conn, "SELECT id, employee_id FROM caseloads WHERE category_id=?");
                        mysqli_stmt_bind_param($getCaseloads, "i", $category_id);
                        if (mysqli_stmt_execute($getCaseloads))
                        {
                            $getCaseloadsResults = mysqli_stmt_get_result($getCaseloads);
                            if (($num_of_caseloads = mysqli_num_rows($getCaseloadsResults)) > 0)
                            {
                                while ($caseload = mysqli_fetch_array($getCaseloadsResults))
                                {
                                    // store caseload details locallay
                                    $caseload_id = $caseload["id"];
                                    $therapist_id = $caseload["employee_id"];

                                    // get the total number of units the caseload has for the active year
                                    $total_units += getCaseloadUnits($conn, $caseload_id, $GLOBAL_SETTINGS["active_period"]);
                                }
                            }
                        }

                        // get number of students within the category
                        $num_of_students = 0;
                        $getNumOfStudents = mysqli_prepare($conn, "SELECT DISTINCT c.student_id FROM cases c
                                                                    JOIN caseloads cl ON c.caseload_id=cl.id
                                                                    WHERE c.period_id=? AND cl.category_id=?");
                        mysqli_stmt_bind_param($getNumOfStudents, "ii", $GLOBAL_SETTINGS["active_period"], $category_id);
                        if (mysqli_stmt_execute($getNumOfStudents))
                        {
                            $getNumOfStudentsResults = mysqli_stmt_get_result($getNumOfStudents);
                            $num_of_students = mysqli_num_rows($getNumOfStudentsResults);
                        }

                        // build the tile depending on category type
                        ///////////////////////////////////////////////////////////
                        //
                        //  Classroom Tile
                        //
                        ///////////////////////////////////////////////////////////
                        if ($is_classroom == 1)
                        {
                            // initialize total revenue
                            $total_revenue = 0;

                            // get all services for the category
                            $getServices = mysqli_prepare($conn, "SELECT DISTINCT service_id FROM caseload_classrooms WHERE category_id=? ORDER BY name ASC, label ASC");
                            mysqli_stmt_bind_param($getServices, "i", $category_id);
                            if (mysqli_stmt_execute($getServices))
                            {
                                $getServicesResults = mysqli_stmt_get_result($getServices);
                                if (mysqli_num_rows($getServicesResults) > 0)
                                {
                                    while ($service = mysqli_fetch_assoc($getServicesResults))
                                    {
                                        // store classroom details locally
                                        $service_id = $service["service_id"];

                                        // get total revenue for the classroom
                                        $getBilled = mysqli_prepare($conn, "SELECT SUM(cost) AS total_revenue FROM quarterly_costs WHERE service_id=? AND period_id=?");
                                        mysqli_stmt_bind_param($getBilled, "si", $service_id, $GLOBAL_SETTINGS["active_period"]);
                                        if (mysqli_stmt_execute($getBilled))
                                        {
                                            $getBilledResult = mysqli_stmt_get_result($getBilled);
                                            if (mysqli_num_rows($getBilledResult) > 0)
                                            {
                                                $total_revenue += mysqli_fetch_assoc($getBilledResult)["total_revenue"];
                                            }
                                        }
                                    }
                                }
                            }

                            $response_body = "<h3 class=\"card-title card-title-sped_dash m-0\">".$category_name."</h3>
                                                <p class=\"my-1\">Total Students: ".number_format($num_of_students)."</p>
                                                <p class=\"my-1\">Total Days: ".number_format($total_units)."</p>
                                                <p class=\"my-1\">Total Cost: $".number_format($total_revenue, 2)."</p>";
                            $response_code = 1;
                        }
                        ///////////////////////////////////////////////////////////
                        //
                        //  UOS Tile
                        //
                        ///////////////////////////////////////////////////////////
                        else if ($uos_enabled == 1)
                        {
                            // get live billing data for the service
                            $total_revenue = 0;
                            $getBilled = mysqli_prepare($conn, "SELECT SUM(cost) AS total_revenue FROM quarterly_costs WHERE service_id=? AND period_id=?");
                            mysqli_stmt_bind_param($getBilled, "si", $service_id, $GLOBAL_SETTINGS["active_period"]);
                            if (mysqli_stmt_execute($getBilled))
                            {
                                $getBilledResult = mysqli_stmt_get_result($getBilled);
                                if (mysqli_num_rows($getBilledResult) > 0)
                                {
                                    $total_revenue = mysqli_fetch_assoc($getBilledResult)["total_revenue"];
                                }
                            }

                            $response_body = "<h3 class=\"card-title card-title-sped_dash m-0\">".$category_name."</h3>
                                                <p class=\"my-1\">Total Students: ".number_format($num_of_students)."</p>
                                                <p class=\"my-1\">Total UOS: ".number_format($total_units)."</p>
                                                <p class=\"my-1\">Total Cost: $".number_format($total_revenue, 2)."</p>";
                            $response_code = 1;
                        }
                    }
                }
            }
        }

        // build and return response array
        $response = [];
        $response["code"] = $response_code;
        $response["body"] = $response_body;
        echo json_encode($response);

        // disconnect from the database
        mysqli_close($conn);
    }
?>