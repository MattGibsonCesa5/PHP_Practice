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
            if (isset($_POST["category"]) && $_POST["category"] <> "") { $category_id = $_POST["category"]; } else { $category_id = null; }
            if (isset($_POST["district_id"]) && $_POST["district_id"] <> "") { $district_id = $_POST["district_id"]; } else { $district_id = null; }

            // verify the period exists; if it exists, store the period ID
            if ($period != null && $period_id = getPeriodID($conn, $period))
            {
                if ($category_id != null && verifyCaseloadCategory($conn, $category_id))
                {
                    // get category settings
                    $category_settings = getCaseloadCategorySettings($conn, $category_id);

                    if ($district_id != null && verifyCustomer($conn, $district_id))
                    {
                        // get the customer name
                        $customerDetails = getCustomerDetails($conn, $district_id);
                        $customer_name = $customerDetails["name"];

                        // classroom-based caseload
                        if ($category_settings["is_classroom"] == 1)
                        {
                            // get all cases for the customer where the student is attending the district and being billed
                            $getCasesByDistrict = mysqli_prepare($conn, "SELECT c.* FROM cases c
                                                                        JOIN caseloads cl ON c.caseload_id=cl.id
                                                                        JOIN caseload_categories cc ON cl.category_id=cc.id
                                                                        WHERE c.period_id=? AND ((c.district_attending=? AND c.bill_to=2) OR (c.residency=? AND c.bill_to=1)) AND cc.id=?");
                            mysqli_stmt_bind_param($getCasesByDistrict, "iiii", $period_id, $district_id, $district_id, $category_id);
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
                                        $classroom_id = $case["classroom_id"];
                                        $case_days = $case["membership_days"];
                                        
                                        // get the student's name
                                        $student_name = getStudentDisplayName($conn, $student_id);

                                        // get the classroom's name
                                        $classroom_name = getCaseloadClassroomName($conn, $classroom_id);

                                        // build the temporary array
                                        $temp = [];
                                        $temp["district"] = $customer_name;
                                        $temp["student"] = $student_name;
                                        $temp["location"] = $classroom_name;
                                        $temp["membership_days"] = $case_days;

                                        // add case data to report
                                        $report[] = $temp;
                                    }
                                }
                            }
                        }
                        // unit-based caseload
                        else if ($category_settings["uos_enabled"] == 1)
                        {
                            // get all cases for the customer where the student is attending the district and being billed
                            $getCasesByDistrict = mysqli_prepare($conn, "SELECT c.* FROM cases c
                                                                        JOIN caseloads cl ON c.caseload_id=cl.id
                                                                        JOIN caseload_categories cc ON cl.category_id=cc.id
                                                                        WHERE c.period_id=? AND ((c.district_attending=? AND c.bill_to=2) OR (c.residency=? AND c.bill_to=1)) AND cc.id=?");
                            mysqli_stmt_bind_param($getCasesByDistrict, "iiii", $period_id, $district_id, $district_id, $category_id);
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

                                        // build the temporary array
                                        $temp = [];
                                        $temp["district"] = $customer_name;
                                        $temp["student"] = $student_name;
                                        $temp["therapist"] = $therapist_name;
                                        $temp["units"] = $case_units;

                                        // add case data to report
                                        $report[] = $temp;
                                    }
                                }
                            }
                        }
                    }
                    else if ($district_id == null)
                    {
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
                                    if ($category_settings["is_classroom"] == 1)
                                    {
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
                                                    $classroom_id = $case["classroom_id"];
                                                    $case_days = $case["membership_days"];
                                                    
                                                    // get the student's name
                                                    $student_name = getStudentDisplayName($conn, $student_id);

                                                    // get the classroom's name
                                                    $classroom_name = getCaseloadClassroomName($conn, $classroom_id);

                                                    // build the temporary array
                                                    $temp = [];
                                                    $temp["district"] = $customer_name;
                                                    $temp["student"] = $student_name;
                                                    $temp["location"] = $classroom_name;
                                                    $temp["membership_days"] = $case_days;

                                                    // add case data to report
                                                    $report[] = $temp;
                                                }
                                            }
                                        }
                                    }
                                    // unit-based caseload
                                    else if ($category_settings["uos_enabled"] == 1)
                                    {
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

                                                    // build the temporary array
                                                    $temp = [];
                                                    $temp["district"] = $customer_name;
                                                    $temp["student"] = $student_name;
                                                    $temp["therapist"] = $therapist_name;
                                                    $temp["units"] = $case_units;

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

        // disconnect from the database
        mysqli_close($conn);

        // return data
        $fullData = [];
        $fullData["draw"] = 1;
        $fullData["data"] = $report;
        echo json_encode($fullData);
    }
?>