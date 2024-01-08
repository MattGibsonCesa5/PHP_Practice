<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // initialize variable to store the report data to return
        $report = [];

        // get additional required files
        include("../../includes/functions.php");
        include("../../includes/config.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        // verify user permissions
        if (checkUserPermission($conn, "VIEW_CASELOADS_ALL") || (checkUserPermission($conn, "VIEW_CASELOADS_ASSIGNED") && verifyCoordinator($conn, $_SESSION["id"])))
        {
            // get period name from POST
            if (isset($_POST["period"]) && $_POST["period"] <> "") { $period = $_POST["period"]; } else { $period = null; }

            // verify the period exists; if it exists, store the period ID
            if ($period != null && $period_id = getPeriodID($conn, $period)) 
            {
                // get all categories
                $getCategories = mysqli_query($conn, "SELECT id, name, is_classroom, uos_enabled FROM caseload_categories ORDER BY name ASC");
                if (mysqli_num_rows($getCategories) > 0)
                {
                    while ($category = mysqli_fetch_array($getCategories))
                    {
                        // store category details locally
                        $category_id = $category["id"];
                        $category_name = $category["name"];
                        $is_classroom = $category["is_classroom"];
                        $uos_enabled = $category["uos_enabled"];

                        // build query depending on account permissions
                        if (checkUserPermission($conn, "VIEW_CASELOADS_ALL"))
                        {
                            $getCaseloads = mysqli_prepare($conn, "SELECT id, employee_id FROM caseloads WHERE category_id=?");
                            mysqli_stmt_bind_param($getCaseloads, "i", $category_id);
                        }
                        else if (checkUserPermission($conn, "VIEW_CASELOADS_ASSIGNED") && verifyCoordinator($conn, $_SESSION["id"]))
                        {
                            $getCaseloads = mysqli_prepare($conn, "SELECT c.id, c.employee_id FROM caseloads c
                                                                    JOIN caseload_coordinators_assignments ca ON c.id=ca.caseload_id
                                                                    WHERE c.category_id=? AND ca.user_id=?");
                            mysqli_stmt_bind_param($getCaseloads, "ii", $category_id, $_SESSION["id"]);
                        }
                        
                        // execute query to get caseloads 
                        if (mysqli_stmt_execute($getCaseloads))
                        {
                            $getCaseloadsResults = mysqli_stmt_get_result($getCaseloads);
                            if (mysqli_num_rows($getCaseloadsResults) > 0)
                            {
                                while ($caseload = mysqli_fetch_array($getCaseloadsResults))
                                {
                                    // store caseload details locally
                                    $caseload_id = $caseload["id"];
                                    $employee_id = $caseload["employee_id"];

                                    // get user name
                                    $employee_name = getUserDisplayName($conn, $employee_id);

                                    ///////////////////////////////////////////////////////////////
                                    //
                                    //  Classroom-based Caseloads
                                    //
                                    ///////////////////////////////////////////////////////////////
                                    if ($is_classroom == 1)
                                    {
                                        // get all cases for the customer where the student is attending the district and being billed
                                        $getCasesByCaseload = mysqli_prepare($conn, "SELECT c.* FROM cases c WHERE c.period_id=? AND c.caseload_id=?");
                                        mysqli_stmt_bind_param($getCasesByCaseload, "ii", $period_id, $caseload_id);
                                        if (mysqli_stmt_execute($getCasesByCaseload))
                                        {
                                            $getCasesByCaseloadResult = mysqli_stmt_get_result($getCasesByCaseload);
                                            if (($num_of_cases = mysqli_num_rows($getCasesByCaseloadResult)) > 0) // cases exist; continue
                                            {
                                                // initialize days and FTEs for the district
                                                $total_days = 0;
                                                $total_ftes = 0;

                                                while ($caseload = mysqli_fetch_array($getCasesByCaseloadResult))
                                                {
                                                    // store caseload data locally
                                                    $case_id = $caseload["id"];
                                                    $caseload_id = $caseload["caseload_id"];
                                                    $case_days = $caseload["membership_days"];

                                                    // calculate the FTE equivalent - round to nearest whole quarter // TODO - in future, allow custom FTE
                                                    $case_fte = (floor(($case_days / 180) * 4) / 4);

                                                    // add to district total
                                                    $total_days += $case_days;
                                                    $total_ftes += $case_fte;
                                                }

                                                // build therapist name column 
                                                $name_div = "<div class='my-1'>
                                                    <form class='w-100' method='POST' action='caseload.php'>
                                                        <input type='hidden' id='caseload_id' name='caseload_id' value='".$caseload_id."' aria-hidden='true'>
                                                        <input type='hidden' id='period_id' name='period_id' value='".$period_id."' aria-hidden='true'>
                                                        <button class='btn btn-therapist_caseload text-center w-100' type='submit'>
                                                            <span class='text-nowrap'>$employee_name</span>
                                                        </button>
                                                    </form>
                                                </div>";
                                                
                                                // build temporary array
                                                $temp = [];
                                                $temp["therapist"] = $name_div;
                                                $temp["category"] = $category_name;
                                                $temp["students"] = $num_of_cases;
                                                $temp["days"] = $total_days;
                                                $temp["units"] = $total_ftes;

                                                // add array to master list
                                                $report[] = $temp;
                                            }
                                        }
                                    }
                                    ///////////////////////////////////////////////////////////////
                                    //
                                    //  UOS-based Caseloads
                                    //
                                    ///////////////////////////////////////////////////////////////
                                    else if ($uos_enabled == 1)
                                    {
                                        // get all cases for the customer where the student is attending the district and being billed
                                        $getCasesByCaseload = mysqli_prepare($conn, "SELECT c.* FROM cases c WHERE c.period_id=? AND c.caseload_id=?");
                                        mysqli_stmt_bind_param($getCasesByCaseload, "ii", $period_id, $caseload_id);
                                        if (mysqli_stmt_execute($getCasesByCaseload))
                                        {
                                            $getCasesByCaseloadResult = mysqli_stmt_get_result($getCasesByCaseload);
                                            if (($num_of_cases = mysqli_num_rows($getCasesByCaseloadResult)) > 0) // cases exist; continue
                                            {
                                                // initialize units for the caseload (staff member)
                                                $total_units = 0;

                                                while ($case = mysqli_fetch_array($getCasesByCaseloadResult))
                                                {
                                                    // store caseload data locally
                                                    $case_id = $case["id"];
                                                    $evaluation_method = $case["evaluation_method"];
                                                    $extra_ieps = $case["extra_ieps"];
                                                    $extra_evals = $case["extra_evaluations"];

                                                    // get the end of year units of service (prorated based on changes)
                                                    $case_units = 0;
                                                    if ($evaluation_method == 1) { $case_units = getProratedUOS($conn, $case_id); }
                                                    else if ($evaluation_method == 2) { $case_units = 16; }

                                                    // calculate the number of additional units based on extra IEPs or evaluations, then add to the EOY unit total
                                                    $additional_units = 0;
                                                    if (is_numeric($extra_ieps) && $extra_ieps > 0) { $additional_units += (12 * $extra_ieps); }
                                                    if (is_numeric($extra_evals) && $extra_evals > 0) { $additional_units += (16 * $extra_evals); }
                                                    $case_units += $additional_units;

                                                    // add the case units to the total for the district
                                                    $total_units += $case_units;
                                                }

                                                // build therapist name column 
                                                $name_div = "<div class='my-1'>
                                                    <form class='w-100' method='POST' action='caseload.php'>
                                                        <input type='hidden' id='caseload_id' name='caseload_id' value='".$caseload_id."' aria-hidden='true'>
                                                        <input type='hidden' id='period_id' name='period_id' value='".$period_id."' aria-hidden='true'>
                                                        <button class='btn btn-therapist_caseload text-center w-100' type='submit'>
                                                            <span class='text-nowrap'>$employee_name</span>
                                                        </button>
                                                    </form>
                                                </div>";
                                                
                                                // build temporary array
                                                $temp = [];
                                                $temp["therapist"] = $name_div;
                                                $temp["category"] = $category_name;
                                                $temp["students"] = $num_of_cases;
                                                $temp["days"] = "";
                                                $temp["units"] = $total_units;

                                                // add array to master list
                                                $report[] = $temp;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        // disconnect from the database
        mysqli_close($conn);

        // return data
        $fullData = [];
        $fullData["draw"] = 1;
        $fullData["data"] = $report;
        echo json_encode($fullData);
    }
?>