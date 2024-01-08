<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // initialize the invoice array
        $invoice = [];

        // get additional required files
        include("../../includes/functions.php");
        include("../../includes/config.php");
        include("../../getSettings.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "EXPORT_INVOICES"))
        {
            /*
            *  Invoice File Format
            *  (1) cust#
            *  (2) Item : always "ITEM"
            *  (3) Item Description : services name
            *  (4) Invoice Descripion : user filled
            *  (5) Qty : always set to 1
            *  (6) Unit $
            *  (7) -
            *  (8) CR Acct #
            *  (9) -
            *  (10) -
            *  (11) Inv Date : current date YMD format, no dividers
            */

            // get the parameters from POST
            if (isset($_POST["quarter"])) { $quarter = $_POST["quarter"]; } else { $quarter = null; }
            if (isset($_POST["locked"])) { $locked = $_POST["locked"]; } else { $locked = null; }
            $invoice_description = "";

            if ($quarter != null && $locked != null)
            {
                // get a list of all active services
                $getServices = mysqli_prepare($conn, "SELECT id, name, export_label, fund_code, object_code, function_code, project_code FROM services WHERE active=1");
                if (mysqli_stmt_execute($getServices))
                {
                    $servicesResults = mysqli_stmt_get_result($getServices);
                    if (mysqli_num_rows($servicesResults) > 0) // services exist
                    {
                        while ($service = mysqli_fetch_array($servicesResults))
                        {
                            // store service details locally
                            $service_id = $service["id"];
                            $service_name = $service["name"];
                            $export_label = $service["export_label"];
                            $fund_code = $service["fund_code"];
                            $object_code = $service["object_code"];
                            $function_code = $service["function_code"];
                            $project_code = $service["project_code"];

                            // get a list of all customers who we have provided the current service to
                            $getCustomers = mysqli_prepare($conn, "SELECT DISTINCT customer_id FROM services_provided WHERE service_id=?");
                            mysqli_stmt_bind_param($getCustomers, "s", $service_id);
                            if (mysqli_stmt_execute($getCustomers))
                            {
                                $customersResults = mysqli_stmt_get_result($getCustomers);
                                if (mysqli_num_rows($customersResults) > 0)
                                {
                                    while ($customer = mysqli_fetch_array($customersResults)) 
                                    {
                                        // store customer ID locally
                                        $customer_id = $customer["customer_id"];

                                        // get the total quarterly cost for the customer for this service
                                        $getQuarterlyCost = mysqli_prepare($conn, "SELECT SUM(cost) AS cost FROM quarterly_costs WHERE service_id=? AND customer_id=? AND quarter=? AND period_id=?");
                                        mysqli_stmt_bind_param($getQuarterlyCost, "siii", $service_id, $customer_id, $quarter, $GLOBAL_SETTINGS["active_period"]);
                                        if (mysqli_stmt_execute($getQuarterlyCost))
                                        {
                                            $costResult = mysqli_stmt_get_result($getQuarterlyCost);
                                            if (mysqli_num_rows($costResult) > 0)
                                            {
                                                $quarterlyCost = mysqli_fetch_array($costResult)["cost"];

                                                // only export to invoice if cost is not 0
                                                if ($quarterlyCost != 0)
                                                {
                                                    // get the customer's location code
                                                    $getCustomerLocationCode = mysqli_prepare($conn, "SELECT location_code FROM customers WHERE id=?");
                                                    mysqli_stmt_bind_param($getCustomerLocationCode, "i", $customer_id);
                                                    if (mysqli_stmt_execute($getCustomerLocationCode))
                                                    {
                                                        $locationCodeResult = mysqli_stmt_get_result($getCustomerLocationCode);
                                                        if (mysqli_num_rows($locationCodeResult) > 0)
                                                        {
                                                            $location_code = mysqli_fetch_array($locationCodeResult)["location_code"];                                        
                                                            
                                                            // generate the "CR Acct #" for the customer
                                                            // CR Acct # = {FUND} {LOCATION CODE} {OBJECT/SOURCE CODE} {FUNCTION CODE} {PROJECT CODE}
                                                            $CR_Acct_Num = $fund_code . " R " . $location_code . " " . $object_code . " " . $function_code . " " . $project_code;

                                                            // add receipt to array
                                                            $temp = [];
                                                            $temp["customer_id"] = $customer_id;
                                                            $temp["service_label"] = $export_label;
                                                            $temp["cost"] = sprintf("%0.2f", $quarterlyCost);
                                                            $temp["CR_Acct"] = $CR_Acct_Num;
                                                            $temp["date"] = date("Ymd");
                                                            $invoice[] = $temp;
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

            // get a list of all active "other services"
            $getOtherServices = mysqli_prepare($conn, "SELECT id, name, export_label, fund_code, source_code, function_code FROM services_other WHERE active=1");
            if (mysqli_stmt_execute($getOtherServices))
            {
                $otherServicesResults = mysqli_stmt_get_result($getOtherServices);
                if (mysqli_num_rows($otherServicesResults) > 0) // "other services" exist
                {
                    while ($service = mysqli_fetch_array($otherServicesResults))
                    {
                        // store service details locally
                        $service_id = $service["id"];
                        $service_name = $service["name"];
                        $export_label = $service["export_label"];
                        $fund_code = $service["fund_code"];
                        $source_code = $service["source_code"];
                        $function_code = $service["function_code"];

                        // get a list of all customers who we have provided the current service to
                        $getCustomers = mysqli_prepare($conn, "SELECT customer_id, project_code FROM services_other_provided WHERE service_id=? AND period_id=?");
                        mysqli_stmt_bind_param($getCustomers, "si", $service_id, $GLOBAL_SETTINGS["active_period"]);
                        if (mysqli_stmt_execute($getCustomers))
                        {
                            $customersResults = mysqli_stmt_get_result($getCustomers);
                            if (mysqli_num_rows($customersResults) > 0)
                            {
                                while ($customer = mysqli_fetch_array($customersResults)) 
                                {
                                    // store customer ID locally
                                    $customer_id = $customer["customer_id"];

                                    // store the project code locally
                                    $project_code = $customer["project_code"];

                                    // get the total quarterly cost for the customer for this "other service"
                                    $getOtherQuarterlyCost = mysqli_prepare($conn, "SELECT SUM(cost) AS cost FROM other_quarterly_costs WHERE other_service_id=? AND customer_id=? AND quarter=? AND period_id=?");
                                    mysqli_stmt_bind_param($getOtherQuarterlyCost, "siii", $service_id, $customer_id, $quarter, $GLOBAL_SETTINGS["active_period"]);
                                    if (mysqli_stmt_execute($getOtherQuarterlyCost))
                                    {
                                        $costResult = mysqli_stmt_get_result($getOtherQuarterlyCost);
                                        if (mysqli_num_rows($costResult) > 0)
                                        {
                                            // store the quarterly cost locally
                                            $quarterlyCost = mysqli_fetch_array($costResult)["cost"];

                                            // only export to invoice if cost is not 0
                                            if ($quarterlyCost != 0)
                                            {
                                                // get the customer's location code
                                                $getCustomerLocationCode = mysqli_prepare($conn, "SELECT location_code FROM customers WHERE id=?");
                                                mysqli_stmt_bind_param($getCustomerLocationCode, "i", $customer_id);
                                                if (mysqli_stmt_execute($getCustomerLocationCode))
                                                {
                                                    $locationCodeResult = mysqli_stmt_get_result($getCustomerLocationCode);
                                                    if (mysqli_num_rows($locationCodeResult) > 0)
                                                    {
                                                        $location_code = mysqli_fetch_array($locationCodeResult)["location_code"];                                        
                                                        
                                                        // generate the "CR Acct #" for the customer
                                                        // CR Acct # = {FUND} {LOCATION CODE} {OBJECT/SOURCE CODE} {FUNCTION CODE} {PROJECT CODE}
                                                        $CR_Acct_Num = $fund_code . " R " . $location_code . " " . $source_code . " " . $function_code . " " . $project_code;

                                                        // add receipt to array
                                                        $temp = [];
                                                        $temp["customer_id"] = $customer_id;
                                                        $temp["service_label"] = $export_label;
                                                        $temp["cost"] = sprintf("%0.2f", $quarterlyCost);
                                                        $temp["CR_Acct"] = $CR_Acct_Num;
                                                        $temp["date"] = date("Ymd");
                                                        $invoice[] = $temp;
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

            // if the locked button was selected, lock the quarter after we create invoice
            if ($locked == 1)
            {
                $lockQuarter = mysqli_prepare($conn, "UPDATE quarters SET locked=1 WHERE quarter=? AND period_id=?");
                mysqli_stmt_bind_param($lockQuarter, "ii", $quarter, $GLOBAL_SETTINGS["active_period"]);
                mysqli_stmt_execute($lockQuarter);
            }
        }

        // send data to be printed
        $fullData = [];
        $fullData["draw"] = 1;
        $fullData["data"] = $invoice;
        echo json_encode($fullData);

        // disconnect from the database
        mysqli_close($conn);
    }
?>