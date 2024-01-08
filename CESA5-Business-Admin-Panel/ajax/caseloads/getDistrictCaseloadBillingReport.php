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

            // verify period exists
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
                            // for all customers with cases in the system for the selected period and category, find the number of units being billed to them for the period
                            $getCustomers = mysqli_prepare($conn, "SELECT DISTINCT d.id, d.name FROM customers d 
                                                                    JOIN cases c ON d.id=c.district_attending OR d.id=c.residency
                                                                    JOIN caseloads cl ON c.caseload_id=cl.id
                                                                    WHERE c.period_id=? AND cl.category_id=?
                                                                    ORDER BY d.name ASC");
                            mysqli_stmt_bind_param($getCustomers, "ii", $period_id, $category_id);
                        }
                        else if (checkUserPermission($conn, "VIEW_CASELOADS_ASSIGNED") && verifyCoordinator($conn, $_SESSION["id"]))
                        {
                            // for all customers with cases in the system for the selected period and category, find the number of units being billed to them for the period
                            $getCustomers = mysqli_prepare($conn, "SELECT DISTINCT d.id, d.name FROM customers d 
                                                                    JOIN cases c ON d.id=c.district_attending OR d.id=c.residency
                                                                    JOIN caseloads cl ON c.caseload_id=cl.id
                                                                    JOIN caseload_coordinators_assignments ca ON cl.id=ca.caseload_id
                                                                    WHERE c.period_id=? AND cl.category_id=? AND ca.user_id=?
                                                                    ORDER BY d.name ASC");
                            mysqli_stmt_bind_param($getCustomers, "iii", $period_id, $category_id, $_SESSION["id"]);
                        }

                        // execute query to get customer data
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

                                    if ($is_classroom == 1)
                                    {
                                        // for each classroom, get membership days and student FTEs for each district
                                        $getClassrooms = mysqli_prepare($conn, "SELECT id, name, label, service_id FROM caseload_classrooms WHERE category_id=?");
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

                                                    // build the classroom name
                                                    if (isset($classroom_label) && $classroom_label != null && trim($classroom_label) <> "") { $classroom_name = trim($classroom_label); }

                                                    // get all cases for the customer where the student is attending the district and being billed
                                                    $getCasesByDistrict = mysqli_prepare($conn, "SELECT c.* FROM cases c
                                                                                                JOIN caseloads cl ON c.caseload_id=cl.id
                                                                                                JOIN caseload_categories cc ON cl.category_id=cc.id
                                                                                                WHERE c.period_id=? AND c.classroom_id=? AND ((c.district_attending=? AND c.bill_to=2) OR (c.residency=? AND c.bill_to=1)) AND cc.id=?");
                                                    mysqli_stmt_bind_param($getCasesByDistrict, "iiiii", $period_id, $classroom_id, $customer_id, $customer_id, $category_id);
                                                    if (mysqli_stmt_execute($getCasesByDistrict))
                                                    {
                                                        $getCasesByDistrictResults = mysqli_stmt_get_result($getCasesByDistrict);
                                                        if (($num_of_cases = mysqli_num_rows($getCasesByDistrictResults)) > 0) // cases exist; continue
                                                        {
                                                            // initialize days and FTEs for the district
                                                            $total_days = 0;
                                                            $total_ftes = 0;
                                                            $cost = 0;

                                                            while ($caseload = mysqli_fetch_array($getCasesByDistrictResults))
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

                                                            // attempt to get details of the service
                                                            $service = getServiceDetails($conn, $service_id);
                                                            if (is_array($service)) // service exists; continue
                                                            { 
                                                                // store service details locally
                                                                $service_name = $service["name"];
                                                                $service_cost_type = $service["cost_type"];
                                                                $service_round_costs = $service["round_costs"];
                                                                $service_project_code = $service["project_code"];

                                                                // attempt to get details of the customer
                                                                $customer = getCustomerDetails($conn, $customer_id);
                                                                if (is_array($customer)) // customer exists; continue
                                                                {
                                                                    // store customer details locally
                                                                    $customer_name = $customer["name"];
                                                                    $customer_members = $customer["members"];

                                                                    // get the cost of the invoice
                                                                    $cost = getInvoiceCost($conn, $service_id, $customer_id, $period_id, $service_cost_type, $service_round_costs, $total_ftes, $cost, $customer_members);
                                                                }
                                                            }
                                                            
                                                            // build temporary array
                                                            $temp = [];
                                                            $temp["district"] = $customer_name;
                                                            $temp["category"] = $category_name;
                                                            $temp["location"] = $classroom_name;
                                                            $temp["students"] = $num_of_cases;
                                                            $temp["days"] = $total_days;
                                                            $temp["units"] = $total_ftes;
                                                            $temp["cost"] = printDollar($cost);
                                                            $temp["calc_cost"] = $cost;
                                                            $temp["classroom_id_filter"] = $classroom_id;

                                                            // add array to master list
                                                            $report[] = $temp;
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                    else if ($uos_enabled == 1)
                                    {
                                        // get all cases for the customer where the student is attending the district and being billed
                                        $getCasesByDistrict = mysqli_prepare($conn, "SELECT c.*, cc.service_id FROM cases c
                                                                                    JOIN caseloads cl ON c.caseload_id=cl.id
                                                                                    JOIN caseload_categories cc ON cl.category_id=cc.id
                                                                                    WHERE c.period_id=? AND ((c.district_attending=? AND c.bill_to=2) OR (c.residency=? AND c.bill_to=1)) AND cc.id=?");
                                        mysqli_stmt_bind_param($getCasesByDistrict, "iiii", $period_id, $customer_id, $customer_id, $category_id);
                                        if (mysqli_stmt_execute($getCasesByDistrict))
                                        {
                                            $getCasesByDistrictResults = mysqli_stmt_get_result($getCasesByDistrict);
                                            if (($num_of_cases = mysqli_num_rows($getCasesByDistrictResults)) > 0) // cases exist; continue
                                            {
                                                // initialize units for the district
                                                $total_units = 0;
                                                $cost = 0;

                                                while ($caseload = mysqli_fetch_array($getCasesByDistrictResults))
                                                {
                                                    // store caseload data locally
                                                    $case_id = $caseload["id"];
                                                    $caseload_id = $caseload["caseload_id"];
                                                    $evaluation_method = $caseload["evaluation_method"];
                                                    $extra_ieps = $caseload["extra_ieps"];
                                                    $extra_evals = $caseload["extra_evaluations"];
                                                    $service_id = $caseload["service_id"];

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

                                                    // get the caseload category and subcategory
                                                    $subcategory_id = getCaseloadSubcategoryName($conn, $caseload_id);
                                                    $subcategory_name = getCaseloadSubcategoryName($conn, $subcategory_id);
                                                }

                                                // attempt to get details of the service
                                                $service = getServiceDetails($conn, $service_id);
                                                if (is_array($service)) // service exists; continue
                                                { 
                                                    // store service details locally
                                                    $service_name = $service["name"];
                                                    $service_cost_type = $service["cost_type"];
                                                    $service_round_costs = $service["round_costs"];
                                                    $service_project_code = $service["project_code"];

                                                    // attempt to get details of the customer
                                                    $customer = getCustomerDetails($conn, $customer_id);

                                                    if (is_array($customer)) // customer exists; continue
                                                    {
                                                        // store customer details locally
                                                        $customer_name = $customer["name"];
                                                        $customer_members = $customer["members"];

                                                        // get the cost of the invoice
                                                        $cost = getInvoiceCost($conn, $service_id, $customer_id, $period_id, $service_cost_type, $service_round_costs, $total_units, $cost, $customer_members);
                                                    }
                                                }

                                                // build temporary array
                                                $temp = [];
                                                $temp["district"] = $customer_name;
                                                $temp["category"] = $category_name;
                                                $temp["location"] = "";
                                                $temp["students"] = $num_of_cases;
                                                $temp["days"] = "";
                                                $temp["units"] = $total_units;
                                                $temp["cost"] = printDollar($cost);
                                                $temp["calc_cost"] = $cost;
                                                $temp["classroom_id_filter"] = null;

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