<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1) 
    { 
        if (isset($_SESSION["role"]) && $_SESSION["role"] == 1)
        {
            // initialize the array of data to send
            $masterData = [];

            // get additional required files
            include("../../includes/functions.php");
            include("../../includes/config.php");

            // connect to the database
            $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

            // get parameters from POST
            if (isset($_POST["period"]) && $_POST["period"] <> "") { $period = $_POST["period"]; } else { $period = null; }
            if (isset($_POST["quarter"]) && is_numeric($_POST["quarter"])) { $quarter = $_POST["quarter"]; } else { $quarter = null; }

            // verify the period exists; if it exists, store the period ID
            if ($period != null && $period_id = getPeriodID($conn, $period))
            {
                // verify the quarter is valid
                if ($quarter != null && ($quarter >= 1 && $quarter <= 4))
                {
                    // get a list of customers who have people in caseloads this period
                    $getCustomers = mysqli_prepare($conn, "SELECT DISTINCT d.id, d.name FROM customers d 
                                                            JOIN cases c ON d.id=c.district_attending OR d.id=c.residency
                                                            WHERE c.period_id=?
                                                            ORDER BY d.name ASC");
                    mysqli_stmt_bind_param($getCustomers, "i", $period_id);
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

                                // get a list of all caseload services that the district uses
                                $getServices = mysqli_prepare($conn, "SELECT DISTINCT cc.id, cc.name, cc.service_id AS uos_service_id FROM caseload_categories cc
                                                                    JOIN caseloads cl ON cc.id=cl.category_id
                                                                    JOIN cases c ON cl.id=c.caseload_id
                                                                    WHERE c.period_id=? AND ((c.district_attending=? AND c.bill_to=2) OR (c.residency=? AND c.bill_to=1))
                                                                    ORDER BY cc.name ASC");
                                mysqli_stmt_bind_param($getServices, "iii", $period_id, $customer_id, $customer_id);
                                if (mysqli_stmt_execute($getServices))
                                {
                                    $getServicesResults = mysqli_stmt_get_result($getServices);
                                    if (mysqli_num_rows($getServicesResults) > 0)
                                    {
                                        while ($service = mysqli_fetch_array($getServicesResults))
                                        {
                                            // store service details locally
                                            $category_id = $service["id"];
                                            $category_name = $service["name"];

                                            // get category settings
                                            $category_settings = getCaseloadCategorySettings($conn, $category_id);

                                            // get the data to be printed
                                            // classroom-based caseload
                                            if ($category_settings["is_classroom"] == 1)
                                            {
                                                // get all classrooms for this category in which the district has students in
                                                $getClassrooms = mysqli_prepare($conn, "SELECT DISTINCT cc.* FROM caseload_classrooms cc 
                                                                                        JOIN cases c ON cc.id=c.classroom_id
                                                                                        WHERE c.period_id=? AND cc.category_id=? AND ((c.district_attending=? AND c.bill_to=2) OR (c.residency=? AND c.bill_to=1)) 
                                                                                        ORDER BY cc.service_id ASC");
                                                mysqli_stmt_bind_param($getClassrooms, "iiii", $period_id, $category_id, $customer_id, $customer_id);
                                                if (mysqli_stmt_execute($getClassrooms))
                                                {
                                                    $getClassroomsResults = mysqli_stmt_get_result($getClassrooms);
                                                    if (mysqli_num_rows($getClassroomsResults) > 0) // classrooms found
                                                    {
                                                        while ($classroom = mysqli_fetch_array($getClassroomsResults))
                                                        {
                                                            // initialize variable to store total days sum and cost and total student count
                                                            $total_units = 0;
                                                            $total_cost = 0;

                                                            // store classroom details
                                                            $classroom_id = $classroom["id"];
                                                            $classroom_name = $classroom["name"];
                                                            $classroom_label = $classroom["label"];
                                                            $service_id = $classroom["service_id"];

                                                            // get all cases for the customer where the student is attending the district and being billed
                                                            $getCasesByDistrict = mysqli_prepare($conn, "SELECT c.* FROM cases c
                                                                                                        JOIN caseloads cl ON c.caseload_id=cl.id
                                                                                                        JOIN caseload_categories cc ON cl.category_id=cc.id
                                                                                                        WHERE c.period_id=? AND c.classroom_id=? AND ((c.district_attending=? AND c.bill_to=2) OR (c.residency=? AND c.bill_to=1)) AND cc.id=?");
                                                            mysqli_stmt_bind_param($getCasesByDistrict, "iiiii", $period_id, $classroom_id, $customer_id, $customer_id, $category_id);
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

                                                                        // add days to sum
                                                                        $total_units += $case_fte;
                                                                    }
                                                                }
                                                            }

                                                            // attempt to get details of the service
                                                            $service_details = getServiceDetails($conn, $service_id);
                                                            if (is_array($service_details)) // service exists; continue
                                                            {                
                                                                // store service details locally
                                                                $service_name = $service_details["name"];
                                                                $service_cost_type = $service_details["cost_type"];
                                                                $service_round_costs = $service_details["round_costs"];
                                                                $service_project_code = $service_details["project_code"];
                                                            }

                                                            // get the estimated cost of the service
                                                            $total_cost = getInvoiceCost($conn, $service_id, $customer_id, $period_id, $service_cost_type, $service_round_costs, $total_units);

                                                            // initialize invoice details to 0
                                                            $units_billed = 0;
                                                            $quarter_billed = 0;
                                                            $annual_billed = 0;
                                                            $quarter_billed = 0;

                                                            // get the current invoices details for the district for this service
                                                            $getInvoiceTotals = mysqli_prepare($conn, "SELECT SUM(quantity) AS annual_units, SUM(total_cost) AS annual_billed FROM services_provided WHERE service_id=? AND customer_id=? AND period_id=?");
                                                            mysqli_stmt_bind_param($getInvoiceTotals, "sii", $service_id, $customer_id, $period_id);
                                                            if (mysqli_stmt_execute($getInvoiceTotals))
                                                            {
                                                                $getInvoiceTotalsResults = mysqli_stmt_get_result($getInvoiceTotals);
                                                                if (mysqli_num_rows($getInvoiceTotalsResults) > 0)
                                                                {
                                                                    // store aggregate totals locally
                                                                    $aggregate = mysqli_fetch_array($getInvoiceTotalsResults); 
                                                                    $units_billed = $aggregate["annual_units"];
                                                                    $annual_billed = $aggregate["annual_billed"];
                                                                }
                                                            }

                                                            // get the quarterly cost for the district for this service
                                                            $getQuarterlyCost = mysqli_prepare($conn, "SELECT SUM(cost) AS quarterly_cost FROM quarterly_costs WHERE service_id=? AND customer_id=? AND period_id=? AND quarter=?");
                                                            mysqli_stmt_bind_param($getQuarterlyCost, "siii", $service_id, $customer_id, $period_id, $quarter);
                                                            if (mysqli_stmt_execute($getQuarterlyCost))
                                                            {
                                                                $getQuarterlyCostResult = mysqli_stmt_get_result($getQuarterlyCost);
                                                                if (mysqli_num_rows($getQuarterlyCostResult) > 0)
                                                                {
                                                                    $aggregate = mysqli_fetch_array($getQuarterlyCostResult);
                                                                    $quarter_billed = $aggregate["quarterly_cost"];
                                                                }
                                                            }

                                                            // create temporary array to store data
                                                            $temp = [];
                                                            $temp["service_id"] = $service_id;
                                                            $temp["service_name"] = $service_name;
                                                            $temp["customer_id"] = $customer_id;
                                                            $temp["customer_name"] = $customer_name;
                                                            if (isset($units_billed)) { $temp["units_billed"] = number_format($units_billed); } else { $temp["units_billed"] = 0; }
                                                            if (isset($total_units)) { $temp["units_expected"] = number_format($total_units); } else { $temp["units_expected"] = 0; }
                                                            if (isset($total_units) && isset($units_billed)) { $temp["units_difference"] = number_format($total_units - $units_billed); } else { $temp["units_difference"] = 0; }
                                                            $temp["quarter_billed"] = printDollar($quarter_billed);
                                                            $temp["quarter_expected"] = printDollar($total_cost / 4);
                                                            $temp["quarter_difference"] = printDollar(($total_cost / 4) - $quarter_billed);
                                                            $temp["annual_billed"] = printDollar($annual_billed);
                                                            $temp["annual_expected"] = printDollar($total_cost);
                                                            $temp["annual_difference"] = printDollar($total_cost - $annual_billed);
                                                            $temp["calc_units_difference"] = ($total_units - $units_billed);
                                                            $temp["calc_quarter_difference"] = (($total_cost / 4) - $quarter_billed);
                                                            $temp["calc_annual_difference"] = ($total_cost - $annual_billed);

                                                            // add the temporary array to the master report
                                                            $masterData[] = $temp;
                                                        }
                                                    }
                                                }
                                            }
                                            // unit-based caseload
                                            else if ($category_settings["uos_enabled"] == 1)
                                            {
                                                // initialize variable to store total days sum and cost and total student count
                                                $total_units = 0;
                                                $total_cost = 0;
                                                
                                                // store UOS service ID
                                                $service_id = $service["uos_service_id"];

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

                                                            // get the end of year units of service (prorated based on changes)
                                                            $case_units = 0;
                                                            if ($evaluation_method == 1) { $case_units = getProratedUOS($conn, $case_id); }
                                                            else if ($evaluation_method == 2) { $case_units = 16; }

                                                            // calculate the number of additional units based on extra IEPs or evaluations, then add to the EOY unit total
                                                            $additional_units = 0;
                                                            if (is_numeric($extra_ieps) && $extra_ieps > 0) { $additional_units += (12 * $extra_ieps); }
                                                            if (is_numeric($extra_evals) && $extra_evals > 0) { $additional_units += (16 * $extra_evals); }
                                                            $case_units += $additional_units;

                                                            // add units to sum
                                                            $total_units += $case_units;
                                                        }
                                                    }
                                                }

                                                // attempt to get details of the service
                                                $service_details = getServiceDetails($conn, $service_id);
                                                if (is_array($service_details)) // service exists; continue
                                                {                
                                                    // store service details locally
                                                    $service_name = $service_details["name"];
                                                    $service_cost_type = $service_details["cost_type"];
                                                    $service_round_costs = $service_details["round_costs"];
                                                    $service_project_code = $service_details["project_code"];
                                                }

                                                // get the estimated cost of the service
                                                $total_cost = getInvoiceCost($conn, $service_id, $customer_id, $period_id, $service_cost_type, $service_round_costs, $total_units);

                                                // initialize invoice details to 0
                                                $units_billed = 0;
                                                $quarter_billed = 0;
                                                $annual_billed = 0;
                                                $quarter_billed = 0;

                                                // get the current invoices details for the district for this service
                                                $getInvoiceTotals = mysqli_prepare($conn, "SELECT SUM(quantity) AS annual_units, SUM(total_cost) AS annual_billed FROM services_provided WHERE service_id=? AND customer_id=? AND period_id=?");
                                                mysqli_stmt_bind_param($getInvoiceTotals, "sii", $service_id, $customer_id, $period_id);
                                                if (mysqli_stmt_execute($getInvoiceTotals))
                                                {
                                                    $getInvoiceTotalsResults = mysqli_stmt_get_result($getInvoiceTotals);
                                                    if (mysqli_num_rows($getInvoiceTotalsResults) > 0)
                                                    {
                                                        // store aggregate totals locally
                                                        $aggregate = mysqli_fetch_array($getInvoiceTotalsResults); 
                                                        $units_billed = $aggregate["annual_units"];
                                                        $annual_billed = $aggregate["annual_billed"];
                                                    }
                                                }

                                                // get the quarterly cost for the district for this service
                                                $getQuarterlyCost = mysqli_prepare($conn, "SELECT SUM(cost) AS quarterly_cost FROM quarterly_costs WHERE service_id=? AND customer_id=? AND period_id=? AND quarter=?");
                                                mysqli_stmt_bind_param($getQuarterlyCost, "siii", $service_id, $customer_id, $period_id, $quarter);
                                                if (mysqli_stmt_execute($getQuarterlyCost))
                                                {
                                                    $getQuarterlyCostResult = mysqli_stmt_get_result($getQuarterlyCost);
                                                    if (mysqli_num_rows($getQuarterlyCostResult) > 0)
                                                    {
                                                        $aggregate = mysqli_fetch_array($getQuarterlyCostResult);
                                                        $quarter_billed = $aggregate["quarterly_cost"];
                                                    }
                                                }

                                                // create temporary array to store data
                                                $temp = [];
                                                $temp["service_id"] = $service_id;
                                                $temp["service_name"] = $service_name;
                                                $temp["customer_id"] = $customer_id;
                                                $temp["customer_name"] = $customer_name;
                                                if (isset($units_billed)) { $temp["units_billed"] = number_format($units_billed); } else { $temp["units_billed"] = 0; }
                                                if (isset($total_units)) { $temp["units_expected"] = number_format($total_units); } else { $temp["units_expected"] = 0; }
                                                if (isset($total_units) && isset($units_billed)) { $temp["units_difference"] = number_format($total_units - $units_billed); } else { $temp["units_difference"] = 0; }
                                                $temp["quarter_billed"] = printDollar($quarter_billed);
                                                $temp["quarter_expected"] = printDollar($total_cost / 4);
                                                $temp["quarter_difference"] = printDollar(($total_cost / 4) - $quarter_billed);
                                                $temp["annual_billed"] = printDollar($annual_billed);
                                                $temp["annual_expected"] = printDollar($total_cost);
                                                $temp["annual_difference"] = printDollar($total_cost - $annual_billed);
                                                $temp["calc_units_difference"] = ($total_units - $units_billed);
                                                $temp["calc_quarter_difference"] = (($total_cost / 4) - $quarter_billed);
                                                $temp["calc_annual_difference"] = ($total_cost - $annual_billed);

                                                // add the temporary array to the master report
                                                $masterData[] = $temp;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }

            // send data to be printed
            $fullData = [];
            $fullData["draw"] = 1;
            $fullData["data"] = $masterData;
            echo json_encode($fullData);

            // disconnect from the database
            mysqli_close($conn);
        }
    }
?>