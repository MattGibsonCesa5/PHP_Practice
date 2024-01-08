<?php
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get additional required files
        include("../../includes/functions.php");
        include("../../includes/config.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "ADD_INVOICES"))
        {
            // get the parameters from POST
            if (isset($_POST["period"]) && $_POST["period"] <> "") { $period = $_POST["period"]; } else { $period = null; }
            if (isset($_POST["quarter"]) && $_POST["quarter"] <> "") { $quarter = $_POST["quarter"]; } else { $quarter = null; }

            if ($period != null && $period_id = getPeriodID($conn, $period))
            {
                if (isPeriodEditable($conn, $period_id))
                {
                    if (isset($quarter) && (is_numeric($quarter) && $quarter >= 1 && $quarter <= 4))
                    {
                        if (!checkLocked($conn, $quarter, $period_id))
                        {
                            // get a list of all caseload categories that are enrolled in auto billing
                            $getCategories = mysqli_query($conn, "SELECT * FROM caseload_categories WHERE auto_bill=1");
                            if (mysqli_num_rows($getCategories) > 0)
                            {
                                // for each category, attempt to bill districts
                                while ($category = mysqli_fetch_array($getCategories))
                                {
                                    // store category details locally
                                    $category_id = $category["id"];
                                    $category_name = $category["name"];
                                    $is_classroom = $category["is_classroom"];
                                    $uos_enabled = $category["uos_enabled"];
                                    $service_id = $category["service_id"];
                                    
                                    // print the category we are billing for
                                    echo "<b>======= $category_name =======</b><br>";

                                    // initialize counter for number of district we have billed for this category
                                    $billedDistrictsCount = 0;

                                    // get a list of all customers that have active cases
                                    $getDistricts = mysqli_prepare($conn, "SELECT DISTINCT d.id, d.name FROM customers d 
                                                                        JOIN cases c ON d.id=c.district_attending OR d.id=c.residency
                                                                        WHERE c.period_id=?
                                                                        ORDER BY d.name ASC");
                                    mysqli_stmt_bind_param($getDistricts, "i", $period_id);
                                    if (mysqli_stmt_execute($getDistricts))
                                    {
                                        $getDistrictsResults = mysqli_stmt_get_result($getDistricts);
                                        if (mysqli_num_rows($getDistrictsResults) > 0)
                                        {
                                            while ($district = mysqli_fetch_array($getDistrictsResults))
                                            {
                                                // store district details locally
                                                $district_id = $district["id"];
                                                $district_name = $district["name"];

                                                ///////////////////////////////////////////////////
                                                //
                                                //  CLASSROOM BILLING
                                                //
                                                ///////////////////////////////////////////////////
                                                if ($is_classroom == 1)
                                                {
                                                    // get a list of all classroom services for the category
                                                    $getClassroomServices = mysqli_prepare($conn, "SELECT DISTINCT(service_id) FROM caseload_classrooms WHERE category_id=?");
                                                    mysqli_stmt_bind_param($getClassroomServices, "i", $category_id);
                                                    if (mysqli_stmt_execute($getClassroomServices))
                                                    {
                                                        $getClassroomServicesResults = mysqli_stmt_get_result($getClassroomServices);
                                                        if (mysqli_num_rows($getClassroomServicesResults) > 0)
                                                        {
                                                            while ($service = mysqli_fetch_assoc($getClassroomServicesResults))
                                                            {
                                                                // store service ID locally
                                                                $service_id = $service["service_id"];

                                                                if (isset($service_id) && $service_id != null && verifyService($conn, $service_id))
                                                                {
                                                                    // get all cases for the customer where the student is attending the district and being billed
                                                                    $total_days = $total_ftes = 0; // initialize days and FTEs for the district
                                                                    $getCasesByDistrict = mysqli_prepare($conn, "SELECT c.* FROM cases c
                                                                                                                JOIN caseloads cl ON c.caseload_id=cl.id
                                                                                                                JOIN caseload_categories cc ON cl.category_id=cc.id
                                                                                                                JOIN caseload_classrooms ccl ON c.classroom_id=ccl.id
                                                                                                                WHERE c.period_id=? AND ((c.district_attending=? AND c.bill_to=2) OR (c.residency=? AND c.bill_to=1)) AND cc.id=? AND c.caseload_id>0 AND ccl.service_id=?");
                                                                    mysqli_stmt_bind_param($getCasesByDistrict, "iiiis", $period_id, $district_id, $district_id, $category_id, $service_id);
                                                                    if (mysqli_stmt_execute($getCasesByDistrict))
                                                                    {
                                                                        $getCasesByDistrictResults = mysqli_stmt_get_result($getCasesByDistrict);
                                                                        if ($numOfCases = mysqli_num_rows($getCasesByDistrictResults) > 0) // cases exist; continue
                                                                        {
                                                                            while ($case = mysqli_fetch_array($getCasesByDistrictResults))
                                                                            {
                                                                                // store caseload data locally
                                                                                $case_id = $case["id"];
                                                                                $case_days = $case["membership_days"];

                                                                                // calculate the FTE - round to nearest whole quarter // TODO - in future, allow custom FTE
                                                                                $case_fte = (floor(($case_days / 180) * 4) / 4);

                                                                                // add to district total
                                                                                $total_days += $case_days;
                                                                                $total_ftes += $case_fte;
                                                                            }
                                                                        }
                                                                    }

                                                                    // if there is a student for the district in the caseload, continue billing
                                                                    if ($numOfCases > 0)
                                                                    {
                                                                        // check to see if the district has already been invoiced
                                                                        $checkInvoices = mysqli_prepare($conn, "SELECT id, quantity FROM services_provided WHERE period_id=? AND service_id=? AND customer_id=?");
                                                                        mysqli_stmt_bind_param($checkInvoices, "isi", $period_id, $service_id, $district_id);
                                                                        if (mysqli_stmt_execute($checkInvoices))
                                                                        {
                                                                            $checkInvoicesResult = mysqli_stmt_get_result($checkInvoices);
                                                                            // district has already been billed for this period; update existing invoice if necessary
                                                                            if (mysqli_num_rows($checkInvoicesResult) > 0)
                                                                            {
                                                                                // store invoice results locally
                                                                                $invoiceDetails = mysqli_fetch_array($checkInvoicesResult);
                                                                                $invoice_id = $invoiceDetails["id"];
                                                                                $current_quantity = $invoiceDetails["quantity"];

                                                                                // store the current timestamp
                                                                                $timestamp = date("Y-m-d H:i:s");

                                                                                // for classrooms, compare the total FTEs to the invoices quantity
                                                                                if ($total_ftes != $current_quantity)
                                                                                {        
                                                                                    // edit the existing invoice
                                                                                    editInvoice($conn, $invoice_id, $service_id, $district_id, $period_id, "Billed at $timestamp.", $timestamp, 0, $total_ftes);

                                                                                    // increment counter
                                                                                    $billedDistrictsCount++;
                                                                                }
                                                                            }
                                                                            // district has not already been billed for this period; create new invoice
                                                                            else
                                                                            {
                                                                                // store the current timestamp
                                                                                $timestamp = date("Y-m-d H:i:s");
                                                                                
                                                                                // create the new invoice
                                                                                createInvoice($conn, $service_id, $district_id, $period_id, "Billed at $timestamp.", $timestamp, $total_ftes);
                                                                            
                                                                                // increment counter
                                                                                $billedDistrictsCount++;
                                                                            }
                                                                        }
                                                                    }
                                                                }
                                                                else { echo "<span class=\"log-fail\">Failed</span> to bill $district_name. The classroom $classroom_name was not assigned to a valid service!<br>"; }
                                                            }
                                                        }
                                                    }
                                                }
                                                ///////////////////////////////////////////////////
                                                //
                                                //  UNITS BILLING
                                                //
                                                ///////////////////////////////////////////////////
                                                else if ($uos_enabled == 1)
                                                {
                                                    if (isset($service_id) && verifyService($conn, $service_id))
                                                    {
                                                        // get all cases for the customer where the student is attending the district and being billed
                                                        $total_units = 0; // initialize days and FTEs for the district
                                                        $getCasesByDistrict = mysqli_prepare($conn, "SELECT c.* FROM cases c
                                                                                                    JOIN caseloads cl ON c.caseload_id=cl.id
                                                                                                    JOIN caseload_categories cc ON cl.category_id=cc.id
                                                                                                    WHERE c.period_id=? AND ((c.district_attending=? AND c.bill_to=2) OR (c.residency=? AND c.bill_to=1)) AND cc.id=? AND c.caseload_id>0");
                                                        mysqli_stmt_bind_param($getCasesByDistrict, "iiii", $period_id, $district_id, $district_id, $category_id);
                                                        if (mysqli_stmt_execute($getCasesByDistrict))
                                                        {
                                                            $getCasesByDistrictResults = mysqli_stmt_get_result($getCasesByDistrict);
                                                            if ($numOfCases = mysqli_num_rows($getCasesByDistrictResults) > 0) // cases exist; continue
                                                            {
                                                                while ($case = mysqli_fetch_array($getCasesByDistrictResults))
                                                                {
                                                                    // store case data locally
                                                                    $case_id = $case["id"];
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

                                                                    // add the case units to the total for the district
                                                                    $total_units += $case_units;
                                                                }
                                                            }
                                                        }

                                                        // if there is a student for the district in the caseload, continue billing
                                                        if ($numOfCases > 0)
                                                        {
                                                            // check to see if the district has already been invoiced
                                                            $checkInvoices = mysqli_prepare($conn, "SELECT id, quantity FROM services_provided WHERE period_id=? AND service_id=? AND customer_id=?");
                                                            mysqli_stmt_bind_param($checkInvoices, "isi", $period_id, $service_id, $district_id);
                                                            if (mysqli_stmt_execute($checkInvoices))
                                                            {
                                                                $checkInvoicesResult = mysqli_stmt_get_result($checkInvoices);
                                                                // district has already been billed for this period; update existing invoice if necessary
                                                                if (mysqli_num_rows($checkInvoicesResult) > 0)
                                                                {
                                                                    // store invoice results locally
                                                                    $invoiceDetails = mysqli_fetch_array($checkInvoicesResult);
                                                                    $invoice_id = $invoiceDetails["id"];
                                                                    $current_quantity = $invoiceDetails["quantity"];

                                                                    // for classrooms, compare the total FTEs to the invoices quantity
                                                                    if ($total_units != $current_quantity)
                                                                    {
                                                                        // store the current timestamp
                                                                        $timestamp = date("Y-m-d H:i:s");
                                                                        
                                                                        // edit the existing invoice
                                                                        editInvoice($conn, $invoice_id, $service_id, $district_id, $period_id, "Billed at $timestamp.", $timestamp, 0, $total_units);
                                                                    
                                                                        // increment counter
                                                                        $billedDistrictsCount++;
                                                                    }
                                                                }
                                                                // district has not already been billed for this period; create new invoice
                                                                else
                                                                {
                                                                    // store the current timestamp
                                                                    $timestamp = date("Y-m-d H:i:s");
                                                                    
                                                                    // create the new invoice
                                                                    createInvoice($conn, $service_id, $district_id, $period_id, "Billed at $timestamp.", $timestamp, $total_units);
                                                                
                                                                    // increment counter
                                                                    $billedDistrictsCount++;
                                                                }
                                                            }
                                                        }
                                                    }
                                                    else { echo "<span class=\"log-fail\">Failed</span> to bill $district_name. The caseload category $category_name is not assigned to a valid service!<br>"; }
                                                }
                                            }
                                        }
                                        else { echo "<span class=\"log-fail\">Failed</span> to bill districts as there are no districts with active cases.<br>"; }
                                    }
                                    else { echo "<span class=\"log-fail\">Failed</span> to bil districts. An unexpected error has occurred! Please try again later.<br>"; }

                                    // print line break
                                    echo "<br>";

                                    // get a list of districts that have been billed from SPED services but not in any caseload
                                    if ($uos_enabled == 1)
                                    {
                                        // echo "Checking for districts that are currently invoiced without students in caseloads for $category_name...<br>";
                                        $getDistricts = mysqli_prepare($conn, "SELECT d.id, d.name, i.id AS invoice_id FROM customers d
                                                                                LEFT JOIN services_provided i ON d.id=i.customer_id
                                                                                WHERE d.id NOT IN (
                                                                                    SELECT DISTINCT (c.residency) 
                                                                                    FROM cases c
                                                                                    JOIN caseloads cl ON c.caseload_id=cl.id
                                                                                    WHERE c.bill_to=1 AND cl.category_id=? AND c.period_id=?
                                                                                ) AND d.id NOT IN (
                                                                                    SELECT DISTINCT (c.district_attending)
                                                                                    FROM cases c
                                                                                    JOIN caseloads cl ON c.caseload_id=cl.id
                                                                                    WHERE c.bill_to=2 AND cl.category_id=? AND c.period_id=?
                                                                                ) AND d.id IN (
                                                                                    SELECT i.customer_id
                                                                                    FROM services_provided i
                                                                                    WHERE i.service_id=? AND i.period_id=? AND i.total_cost!=0
                                                                                ) AND i.service_id=? AND i.period_id=?");
                                        mysqli_stmt_bind_param($getDistricts, "iiiisisi", $category_id, $period_id, $category_id, $period_id, $service_id, $period_id, $service_id, $period_id);
                                        if (mysqli_stmt_execute($getDistricts))
                                        {
                                            $getDistrictsResults = mysqli_stmt_get_result($getDistricts);
                                            if (($numOfDistricts = mysqli_num_rows($getDistrictsResults)) > 0) // districts found
                                            {
                                                echo $numOfDistricts." districts found that are invoiced without students in caseloads for $category_name... Setting annual invoice cost to $0.00 for each district.<br>";
                                                while ($district = mysqli_fetch_assoc($getDistrictsResults))
                                                {
                                                    // store district data locally
                                                    $district_id = $district["id"];
                                                    $district_name = $district["name"];
                                                    $invoice_id = $district["invoice_id"];

                                                    // store the current timestamp
                                                    $timestamp = date("Y-m-d H:i:s");
                                                                        
                                                    // edit the existing invoice
                                                    editInvoice($conn, $invoice_id, $service_id, $district_id, $period_id, "Billed at $timestamp.", $timestamp, 0, 0);
                                                }
                                            }
                                            // else { echo "No districts found that are invoiced without students in caseloads for $category_name.<br>"; }
                                        }
                                    } 
                                    else if ($is_classroom == 1)
                                    {
                                        // echo "Checking for districts that are currently invoiced without students in caseloads for $category_name...<br>";

                                        // get a list of all classroom services for the category
                                        $getClassroomServices = mysqli_prepare($conn, "SELECT DISTINCT(service_id), name FROM caseload_classrooms WHERE category_id=?");
                                        mysqli_stmt_bind_param($getClassroomServices, "i", $category_id);
                                        if (mysqli_stmt_execute($getClassroomServices))
                                        {
                                            $getClassroomServicesResults = mysqli_stmt_get_result($getClassroomServices);
                                            if (mysqli_num_rows($getClassroomServicesResults) > 0)
                                            {
                                                while ($service = mysqli_fetch_assoc($getClassroomServicesResults))
                                                {
                                                    // store service ID locally
                                                    $class_service_id = $service["service_id"];
                                                    $classroom_name = $service["name"];

                                                    // echo "Checking for districts that are currently invoiced without students in caseloads for $category_name, attending the $classroom_name classroom...<br>";
                                                    $getDistricts = mysqli_prepare($conn, "SELECT d.id, d.name, i.id AS invoice_id FROM customers d
                                                                                            LEFT JOIN services_provided i ON d.id=i.customer_id
                                                                                            WHERE d.id NOT IN (
                                                                                                SELECT DISTINCT (c.residency) 
                                                                                                FROM cases c
                                                                                                JOIN caseloads cl ON c.caseload_id=cl.id
                                                                                                WHERE c.bill_to=1 AND cl.category_id=? AND c.period_id=?
                                                                                            ) AND d.id NOT IN (
                                                                                                SELECT DISTINCT (c.district_attending)
                                                                                                FROM cases c
                                                                                                JOIN caseloads cl ON c.caseload_id=cl.id
                                                                                                WHERE c.bill_to=2 AND cl.category_id=? AND c.period_id=?
                                                                                            ) AND d.id IN (
                                                                                                SELECT i.customer_id
                                                                                                FROM services_provided i
                                                                                                WHERE i.service_id=? AND i.period_id=? AND i.total_cost!=0
                                                                                            ) AND i.service_id=? AND i.period_id=?");
                                                    mysqli_stmt_bind_param($getDistricts, "iiiisisi", $category_id, $period_id, $category_id, $period_id, $class_service_id, $period_id, $class_service_id, $period_id);
                                                    if (mysqli_stmt_execute($getDistricts))
                                                    {
                                                        $getDistrictsResults = mysqli_stmt_get_result($getDistricts);
                                                        if (($numOfDistricts = mysqli_num_rows($getDistrictsResults)) > 0) // districts found
                                                        {
                                                            echo $numOfDistricts." districts found that are invoiced without students in caseloads for $category_name, attending the $classroom_name classroom... Setting annual invoice cost to $0.00 for each district.<br>";
                                                            while ($district = mysqli_fetch_assoc($getDistrictsResults))
                                                            {
                                                                // store district data locally
                                                                $district_id = $district["id"];
                                                                $district_name = $district["name"];
                                                                $invoice_id = $district["invoice_id"];

                                                                // store the current timestamp
                                                                $timestamp = date("Y-m-d H:i:s");
                                                                                    
                                                                // edit the existing invoice
                                                                editInvoice($conn, $invoice_id, $class_service_id, $district_id, $period_id, "Billed at $timestamp.", $timestamp, 0, 0);
                                                            }
                                                        }
                                                        // else { echo "No districts found that are invoiced without students in caseloads for $category_name, attending the $classroom_name classroom.<br>"; }
                                                    }
                                                }
                                            }
                                        }
                                    }

                                    // log district billing
                                    if ($billedDistrictsCount > 0) // only log if we billed districts
                                    {
                                        $message = "Successfully invoiced $billedDistrictsCount districts for all $category_name caseloads for $period Q$quarter. ";
                                        $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                                        mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                                        mysqli_stmt_execute($log);
                                    }
                                }
                            }
                            else { echo "<span class=\"log-fail\">Failed</span> to bill districts. There are no caseload categories set to bill for!<br>"; }
                        }
                        else { echo "<span class=\"log-fail\">Failed</span> to bill districts. The quarter you are trying to bill for is already locked!<br>"; }
                    }
                    else { echo "<span class=\"log-fail\">Failed</span> to bill districts. You must select a valid quarter to bill for.<br>"; }
                }
                else { echo "<span class=\"log-fail\">Failed</span> to bill districts. The period you are trying to bill in is not editable.<br>"; }
            }
            else { echo "<span class=\"log-fail\">Failed</span> to bill districts. You must select a valid period to bill for.<br>"; }
        }
        else { echo "Your account does not have permission to perform this task!<br>"; }

        // disconnect from the database
        mysqli_close($conn);
    }
?>