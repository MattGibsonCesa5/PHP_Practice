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
        if (checkUserPermission($conn, "VIEW_CASELOADS_ALL") && checkUserPermission($conn, "VIEW_THERAPISTS"))
        {
            // get parameters from POST
            if (isset($_POST["period"]) && $_POST["period"] <> "") { $period = $_POST["period"]; } else { $period = null; }
            if (isset($_POST["quarter"]) && $_POST["quarter"] <> "") { $quarter = $_POST["quarter"]; } else { $quarter = null; }

            // verify the period exists; if it exists, store the period ID
            if ($period != null && $period_id = getPeriodID($conn, $period))
            {
                if (is_numeric($quarter) && ($quarter >= 1 && $quarter <= 4))
                {
                    // get all categories
                    $getCategories = mysqli_query($conn, "SELECT c.id, c.name, c.is_classroom, c.uos_enabled, c.service_id, s.cost_type, s.round_costs FROM caseload_categories c 
                                                        LEFT JOIN services s ON c.service_id=s.id
                                                        ORDER BY c.name ASC");
                    if (mysqli_num_rows($getCategories) > 0)
                    {
                        while ($category = mysqli_fetch_array($getCategories))
                        {
                            // store category details locally
                            $category_id = $category["id"];
                            $category_name = $category["name"];
                            $is_classroom = $category["is_classroom"];
                            $uos_enabled = $category["uos_enabled"];
                            $service_id = $category["service_id"];
                            $service_cost_type = $category["cost_type"];
                            $service_round_costs = $category["round_costs"];

                            // for all customers with cases in the system for the selected period and category, find the number of units being billed to them for the period
                            $getCustomers = mysqli_prepare($conn, "SELECT DISTINCT d.id, d.name FROM customers d 
                                                                    JOIN cases c ON d.id=c.district_attending OR d.id=c.residency
                                                                    JOIN caseloads cl ON c.caseload_id=cl.id
                                                                    WHERE c.period_id=? AND cl.category_id=?
                                                                    ORDER BY d.name ASC");
                            mysqli_stmt_bind_param($getCustomers, "ii", $period_id, $category_id);
                            if (mysqli_stmt_execute($getCustomers))
                            {
                                $getCustomersResults = mysqli_stmt_get_result($getCustomers);
                                if (mysqli_num_rows($getCustomersResults) > 0)
                                {
                                    while ($customer = mysqli_fetch_array($getCustomersResults))
                                    {
                                        // store the customer ID and name locally
                                        $customer_id = $customer["id"];
                                        $customer_name = $customer["name"];

                                        // classroom-based caseload
                                        if ($is_classroom == 1)
                                        {
                                            // get all classrooms for the category
                                            $getClassrooms = mysqli_prepare($conn, "SELECT c.id, c.name, c.label, c.service_id, s.cost_type, s.round_costs FROM caseload_classrooms c
                                                                                    LEFT JOIN services s ON c.service_id=s.id
                                                                                    WHERE c.category_id=? ORDER BY c.name ASC, c.label ASC");
                                            mysqli_stmt_bind_param($getClassrooms, "i", $category_id);
                                            if (mysqli_stmt_execute($getClassrooms))
                                            {
                                                $getClassroomsResults = mysqli_stmt_get_result($getClassrooms);
                                                if (mysqli_num_rows($getClassroomsResults) > 0)
                                                {
                                                    while ($classroom = mysqli_fetch_array($getClassroomsResults))
                                                    {
                                                        // store classroom details locally
                                                        $classroom_id = $classroom["id"];
                                                        $classroom_name = $classroom["name"];
                                                        $classroom_label = $classroom["label"];
                                                        $service_id = $classroom["service_id"];
                                                        $service_cost_type = $classroom["cost_type"];
                                                        $service_round_costs = $classroom["round_costs"];

                                                        // build the classroom name
                                                        if ($classroom_label != null && trim($classroom_label) <> "") { $classroom_name = trim($classroom_label); }

                                                        // get all cases for the customer where the student is attending the district and being billed
                                                        $getCasesByDistrict = mysqli_prepare($conn, "SELECT c.* FROM cases c
                                                                                                    JOIN caseloads cl ON c.caseload_id=cl.id
                                                                                                    JOIN caseload_categories cc ON cl.category_id=cc.id
                                                                                                    WHERE c.period_id=? AND ((c.district_attending=? AND c.bill_to=2) OR (c.residency=? AND c.bill_to=1)) AND cc.id=? AND c.classroom_id=?");
                                                        mysqli_stmt_bind_param($getCasesByDistrict, "iiiii", $period_id, $customer_id, $customer_id, $category_id, $classroom_id);
                                                        if (mysqli_stmt_execute($getCasesByDistrict))
                                                        {
                                                            $getCasesByDistrictResults = mysqli_stmt_get_result($getCasesByDistrict);
                                                            if (mysqli_num_rows($getCasesByDistrictResults) > 0) // cases exist; continue
                                                            {
                                                                while ($case = mysqli_fetch_array($getCasesByDistrictResults))
                                                                {
                                                                    // store case data locally
                                                                    $case_id = $case["id"];
                                                                    $student_id = $case["student_id"];
                                                                    $caseload_id = $case["caseload_id"];
                                                                    $case_days = $case["membership_days"];

                                                                    // calculate the FTE - round to nearest whole quarter // TODO - in future, allow custom FTE
                                                                    $case_fte = (floor(($case_days / 180) * 4) / 4);

                                                                    // get the estimated cost of the service
                                                                    $case_cost = getInvoiceCost($conn, $service_id, $customer_id, $period_id, $service_cost_type, $service_round_costs, $case_fte);
                                                                    
                                                                    // get the student's name
                                                                    $student_name = getStudentDisplayName($conn, $student_id);

                                                                    // get the therapist for the case
                                                                    $therapist_id = getCaseloadTherapist($conn, $caseload_id);
                                                                    $therapist_name = getUserDisplayName($conn, $therapist_id);

                                                                    // only add to report if cost is > $0.00
                                                                    if ($case_cost > 0)
                                                                    {
                                                                        // build the temporary array
                                                                        $temp = [];
                                                                        $temp["district"] = $customer_name;
                                                                        $temp["student"] = $student_name;
                                                                        $temp["location"] = $classroom_name;
                                                                        $temp["therapist"] = $therapist_name;
                                                                        $temp["category"] = $category_name;
                                                                        $temp["membership_days"] = $case_days;
                                                                        $temp["case_units"] = $case_fte;
                                                                        $temp["student_cost"] = "$".number_format($case_cost, 2);
                                                                        $temp["student_cost_calc"] = $case_cost;
                                                                        $temp["classroom_id_filter"] = $classroom_id;
                                                                        $temp["category_id_filter"] = $category_id;
                                                                        $temp["district_id_filter"] = $customer_id;

                                                                        // add case data to report
                                                                        $report[] = $temp;
                                                                    }
                                                                }
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                        // unit-based caseload
                                        else if ($uos_enabled == 1)
                                        {
                                            // get the expected total units for the caseload category
                                            $expected_total_units = getDistrictUnitsTotalByCategory($conn, $customer_id, $category_id, $period_id);

                                            // get all cases for the customer where the student is attending the district and being billed
                                            $getCasesByDistrict = mysqli_prepare($conn, "SELECT c.* FROM cases c
                                                                                        JOIN caseloads cl ON c.caseload_id=cl.id
                                                                                        JOIN caseload_categories cc ON cl.category_id=cc.id
                                                                                        WHERE c.period_id=? AND ((c.district_attending=? AND c.bill_to=2) OR (c.residency=? AND c.bill_to=1)) AND cc.id=?");
                                            mysqli_stmt_bind_param($getCasesByDistrict, "iiii", $period_id, $customer_id, $customer_id, $category_id);
                                            if (mysqli_stmt_execute($getCasesByDistrict))
                                            {
                                                $getCasesByDistrictResults = mysqli_stmt_get_result($getCasesByDistrict);
                                                if (mysqli_num_rows($getCasesByDistrictResults) > 0) // cases exist; continue
                                                {
                                                    while ($case = mysqli_fetch_array($getCasesByDistrictResults))
                                                    {
                                                        // store case data locally
                                                        $case_id = $case["id"];
                                                        $student_id = $case["student_id"];
                                                        $caseload_id = $case["caseload_id"];
                                                        $evaluation_method = $case["evaluation_method"];
                                                        $extra_ieps = $case["extra_ieps"];
                                                        $extra_evals = $case["extra_evaluations"];

                                                        // get the student's name
                                                        $student_name = getStudentDisplayName($conn, $student_id);

                                                        // get the therapist for the case
                                                        $therapist_id = getCaseloadTherapist($conn, $caseload_id);
                                                        $therapist_name = getUserDisplayName($conn, $therapist_id);

                                                        // get the end of year units of service (prorated based on changes)
                                                        $case_units = 0;
                                                        if ($evaluation_method == 1) { $case_units = getProratedUOS($conn, $case_id); }
                                                        else if ($evaluation_method == 2) { $case_units = 16; }

                                                        // calculate the number of additional units based on extra IEPs or evaluations, then add to the EOY unit total
                                                        $additional_units = 0;
                                                        if (is_numeric($extra_ieps) && $extra_ieps > 0) { $additional_units += (12 * $extra_ieps); }
                                                        if (is_numeric($extra_evals) && $extra_evals > 0) { $additional_units += (16 * $extra_evals); }
                                                        $case_units += $additional_units;

                                                        // initialize estimated annual cost for the student
                                                        $case_cost = 0;

                                                        // get the cost of the service based on total units
                                                        $service_cost = getServiceCost($conn, $service_id, $period_id, $expected_total_units);

                                                        // calculate the cost of the student
                                                        if ($service_round_costs == 1) { $case_cost = round($service_cost * $case_units); }
                                                        else { $case_cost = $service_cost * $case_units; }

                                                        // only add to report if cost is > $0.00
                                                        if ($case_cost > 0)
                                                        {
                                                            // build the temporary array
                                                            $temp = [];
                                                            $temp["district"] = $customer_name;
                                                            $temp["student"] = $student_name;
                                                            $temp["location"] = "-";
                                                            $temp["therapist"] = $therapist_name;
                                                            $temp["category"] = $category_name;
                                                            $temp["membership_days"] = 0;
                                                            $temp["case_units"] = $case_units;
                                                            $temp["student_cost"] = "$".number_format($case_cost, 2);
                                                            $temp["student_cost_calc"] = $case_cost;
                                                            $temp["classroom_id_filter"] = null;
                                                            $temp["category_id_filter"] = $category_id;
                                                            $temp["district_id_filter"] = $customer_id;

                                                            // add case data to report
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