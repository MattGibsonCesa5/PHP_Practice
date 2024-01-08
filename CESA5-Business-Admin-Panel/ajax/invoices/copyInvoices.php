<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        if (isset($_SESSION["role"]) && $_SESSION["role"] == 1)
        {
            // override server settings
            ini_set("max_execution_time", 600); // cap to 10 minutes
            ini_set("memory_limit", "256M"); // cap to 256 MB

            // bring in required additional files
            include("../../includes/config.php");
            include("../../getSettings.php");

            // connect to the database
            $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

            // get the parameters from POST
            if (isset($_POST["from"]) && is_numeric($_POST["from"])) { $from = $_POST["from"]; } else { $from = 0; }
            if (isset($_POST["to"]) && is_numeric($_POST["to"])) { $to = $_POST["to"]; } else { $to = 0; }

            if ($from != 0 && $to != 0) // both from and to periods selected; continue
            {
                // verify the from period exists
                $checkFrom = mysqli_prepare($conn, "SELECT id, name FROM periods WHERE id=?");
                mysqli_stmt_bind_param($checkFrom, "i", $from);
                if (mysqli_stmt_execute($checkFrom))
                {
                    $checkFromResult = mysqli_stmt_get_result($checkFrom);
                    if (mysqli_num_rows($checkFromResult) > 0) // period exists; continue
                    {
                        // store from period name locally
                        $from_label = mysqli_fetch_array($checkFromResult)["name"];

                        // verify the to period exists
                        $checkTo = mysqli_prepare($conn, "SELECT id, name, start_date FROM periods WHERE id=?");
                        mysqli_stmt_bind_param($checkTo, "i", $to);
                        if (mysqli_stmt_execute($checkTo))
                        {
                            $checkToResult = mysqli_stmt_get_result($checkTo);
                            if (mysqli_num_rows($checkToResult) > 0) // period exists; continue
                            {
                                // store the period details locally
                                $toPeriodDetails = mysqli_fetch_array($checkToResult);
                                $to_label = $toPeriodDetails["name"];
                                $startDate = $toPeriodDetails["start_date"];
                                $DB_date = date("Y-m-d", strtotime($startDate));

                                // verify we are not copying invoices into the same period
                                if ($from != $to) // copying invoices into different period; continue
                                {
                                    // initialize variable to keep running sum of all invoices total cost
                                    $GRAND_TOTAL_COST = 0;

                                    // intiailize variable to keep running total of number of successful copies
                                    $invoices_count = 0;

                                    // get all invoices from the "from" period
                                    $getFromInvoices = mysqli_prepare($conn, "SELECT * FROM services_provided WHERE period_id=?");
                                    mysqli_stmt_bind_param($getFromInvoices, "i", $from);
                                    if (mysqli_stmt_execute($getFromInvoices))
                                    {
                                        $getFromInvoicesResults = mysqli_stmt_get_result($getFromInvoices);
                                        if (mysqli_num_rows($getFromInvoicesResults) > 0) // invoices found; continue
                                        {
                                            // for each invoice; copy invoice into the "to" period based on the "to" period's costs
                                            while ($invoice = mysqli_fetch_array($getFromInvoicesResults))
                                            {
                                                // store invoice details locally
                                                $service_id = $invoice["service_id"];
                                                $customer_id = $invoice["customer_id"];
                                                $quantity = $invoice["quantity"];

                                                // get service details
                                                $getServiceDetails = mysqli_prepare($conn, "SELECT name, project_code, cost_type, round_costs FROM services WHERE id=?");
                                                mysqli_stmt_bind_param($getServiceDetails, "s", $service_id);
                                                if (mysqli_stmt_execute($getServiceDetails))
                                                {
                                                    $getServiceDetailsResult = mysqli_stmt_get_result($getServiceDetails);
                                                    if (mysqli_num_rows($getServiceDetailsResult) > 0) // service found; continue
                                                    {
                                                        // store service details locally
                                                        $serviceDetails = mysqli_fetch_array($getServiceDetailsResult);
                                                        $service_name = $serviceDetails["name"];
                                                        $project = $serviceDetails["project_code"];
                                                        $service_cost_type = $serviceDetails["cost_type"];
                                                        $roundCosts = $serviceDetails["round_costs"];

                                                        // get customer details
                                                        $getCustomerDetails = mysqli_prepare($conn, "SELECT name FROM customers WHERE id=?");
                                                        mysqli_stmt_bind_param($getCustomerDetails, "i", $customer_id);
                                                        if (mysqli_stmt_execute($getCustomerDetails))
                                                        {
                                                            $getCustomerDetailsResult = mysqli_stmt_get_result($getCustomerDetails);
                                                            if (mysqli_num_rows($getCustomerDetailsResult) > 0) // customer found; continue
                                                            {
                                                                // store customer details locally
                                                                $customerDetails = mysqli_fetch_array($getCustomerDetailsResult);
                                                                $customer_name = $customerDetails["name"];

                                                                // if cost type if fixed (0)
                                                                if ($service_cost_type == 0)
                                                                {
                                                                    $getServiceCost = mysqli_prepare($conn, "SELECT cost FROM costs WHERE service_id=? AND period_id=?");
                                                                    mysqli_stmt_bind_param($getServiceCost, "si", $service_id, $to);
                                                                    if (mysqli_stmt_execute($getServiceCost))
                                                                    {
                                                                        $result = mysqli_stmt_get_result($getServiceCost);
                                                                        if (mysqli_num_rows($result) > 0) // cost found
                                                                        {
                                                                            $cost = mysqli_fetch_array($result)["cost"];
                                                                            $total_cost = $cost * $quantity;
                                                                            if ($roundCosts == 1) { $total_cost = round($total_cost); } else { $total_cost = round($total_cost, 2); }

                                                                            // add the service provided to the database
                                                                            $provideService = mysqli_prepare($conn, "INSERT INTO services_provided (period_id, service_id, customer_id, quantity, description, date_provided, total_cost) VALUES (?, ?, ?, ?, ?, ?, ?)");
                                                                            mysqli_stmt_bind_param($provideService, "isidssd", $to, $service_id, $customer_id, $quantity, $description, $DB_date, $total_cost);
                                                                            if (mysqli_stmt_execute($provideService)) // successfully provided the service
                                                                            {
                                                                                // get the invoice_id for the new service provied
                                                                                $invoice_id = mysqli_insert_id($conn);

                                                                                // edit the project last updated time
                                                                                if (isset($project) && $project != null) { updateProjectEditTimestamp($conn, $project); }

                                                                                // increment total cost counter
                                                                                $GRAND_TOTAL_COST += $total_cost;

                                                                                // increment successful copies counter
                                                                                $invoices_count++;

                                                                                // by default, insert the quarterly costs equally divided for all quarters
                                                                                $getQuarters = mysqli_prepare($conn, "SELECT * FROM quarters WHERE period_id=?");
                                                                                mysqli_stmt_bind_param($getQuarters, "i", $to);
                                                                                if (mysqli_stmt_execute($getQuarters))
                                                                                {
                                                                                    $results = mysqli_stmt_get_result($getQuarters);
                                                                                    $unlockedQuarters = mysqli_num_rows($results);
                                                                                    
                                                                                    if ($unlockedQuarters > 0) // at least 1 quarter is unlocked
                                                                                    {
                                                                                        // calculate the quarterly cost
                                                                                        $quarterlyCost = number_format((str_replace(",", "", $total_cost) / $unlockedQuarters), 2, ".", "");

                                                                                        // insert the quarterly costs into the database for each quarter
                                                                                        while ($quarter = mysqli_fetch_array($results))
                                                                                        {
                                                                                            $insertQuarterlyCosts = mysqli_prepare($conn, "INSERT INTO quarterly_costs (invoice_id, service_id, customer_id, quarter, cost, period_id) VALUES (?, ?, ?, ?, ?, ?)");
                                                                                            mysqli_stmt_bind_param($insertQuarterlyCosts, "isiidi", $invoice_id, $service_id, $customer_id, $quarter["quarter"], $quarterlyCost, $to);
                                                                                            if (mysqli_stmt_execute($insertQuarterlyCosts))
                                                                                            {
                                                                                                // successfully inserted quarterly cost
                                                                                            }
                                                                                            else { /* TODO */ } // failed to insert quarterly cost; throw error
                                                                                        }
                                                                                    }
                                                                                    else { /* TODO */ } // no quarters are unlocked; throw error?
                                                                                }
                                                                            }
                                                                            else { echo "<span class=\"log-fail\">Failed</span> to invoice $customer_name ".printDollar($total_cost)." for the service $service_name. An unexpected error has occurred! Please try again later!<br>"; }
                                                                        }
                                                                        else { echo "<span class=\"log-fail\">Failed</span> to invoice $customer_name for the service $service_name. The service does not have a cost set in the to period!<br>"; }
                                                                    }
                                                                }
                                                                // if cost type is variable (1)
                                                                else if ($service_cost_type == 1)
                                                                {
                                                                    $getServiceCost = mysqli_prepare($conn, "SELECT * FROM costs WHERE service_id=? AND cost_type=? AND period_id=?");
                                                                    mysqli_stmt_bind_param($getServiceCost, "sii", $service_id, $service_cost_type, $to);
                                                                    if (mysqli_stmt_execute($getServiceCost))
                                                                    {
                                                                        $result = mysqli_stmt_get_result($getServiceCost);
                                                                        if (mysqli_num_rows($result) > 0) // cost found
                                                                        {
                                                                            $break = 0;
                                                                            while (($range = mysqli_fetch_array($result)) && $break == 0)
                                                                            {
                                                                                $min = $range["min_quantity"];
                                                                                $max = $range["max_quantity"];
                                                                                $cost = $range["cost"];

                                                                                if ($max != -1) // max is set
                                                                                {
                                                                                    if ($quantity >= $min && $quantity <= $max) // quantity is within the range
                                                                                    {
                                                                                        // calculate the total annual cost
                                                                                        $total_cost = $cost * $quantity;
                                                                                        if ($roundCosts == 1) { $total_cost = round($total_cost); } else { $total_cost = round($total_cost, 2); }
                                                                                        $break = 1; // break while loop
                                                                                    }
                                                                                }
                                                                                else // no max is set
                                                                                {
                                                                                    // calculate the total annual cost
                                                                                    $total_cost = $cost * $quantity;
                                                                                    if ($roundCosts == 1) { $total_cost = round($total_cost); } else { $total_cost = round($total_cost, 2); }
                                                                                    $break = 1; // break while loop
                                                                                }
                                                                            }

                                                                            if ($break == 1) // found the total cost
                                                                            {
                                                                                // add the service provided to the database
                                                                                $provideService = mysqli_prepare($conn, "INSERT INTO services_provided (period_id, service_id, customer_id, quantity, description, date_provided, total_cost) VALUES (?, ?, ?, ?, ?, ?, ?)");
                                                                                mysqli_stmt_bind_param($provideService, "isidssd", $to, $service_id, $customer_id, $quantity, $description, $DB_date, $total_cost);
                                                                                if (mysqli_stmt_execute($provideService)) // successfully provided the service
                                                                                {
                                                                                    // get the invoice_id for the new service provied
                                                                                    $invoice_id = mysqli_insert_id($conn);

                                                                                    // edit the project last updated time
                                                                                    updateProjectEditTimestamp($conn, $project);

                                                                                    // increment total cost counter
                                                                                    $GRAND_TOTAL_COST += $total_cost;

                                                                                    // increment successful copies counter
                                                                                    $invoices_count++;

                                                                                    // by default, insert the quarterly costs equally divided for all quarters
                                                                                    $getQuarters = mysqli_prepare($conn, "SELECT * FROM quarters WHERE period_id=?");
                                                                                    mysqli_stmt_bind_param($getQuarters, "i", $to);
                                                                                    if (mysqli_stmt_execute($getQuarters))
                                                                                    {
                                                                                        $results = mysqli_stmt_get_result($getQuarters);
                                                                                        $unlockedQuarters = mysqli_num_rows($results);
                                                                                        
                                                                                        if ($unlockedQuarters > 0) // at least 1 quarter is unlocked
                                                                                        {
                                                                                            // calculate the quarterly cost
                                                                                            $quarterlyCost = number_format((str_replace(",", "", $total_cost) / $unlockedQuarters), 2, ".", "");

                                                                                            // insert the quarterly costs into the database for each quarter
                                                                                            while ($quarter = mysqli_fetch_array($results))
                                                                                            {
                                                                                                $insertQuarterlyCosts = mysqli_prepare($conn, "INSERT INTO quarterly_costs (invoice_id, service_id, customer_id, quarter, cost, period_id) VALUES (?, ?, ?, ?, ?, ?)");
                                                                                                mysqli_stmt_bind_param($insertQuarterlyCosts, "isiidi", $invoice_id, $service_id, $customer_id, $quarter["quarter"], $quarterlyCost, $to);
                                                                                                if (mysqli_stmt_execute($insertQuarterlyCosts))
                                                                                                {
                                                                                                    // successfully inserted quarterly cost
                                                                                                }
                                                                                                else { /* TODO */ } // failed to insert quarterly cost; throw error
                                                                                            }
                                                                                        }
                                                                                        else { /* TODO */ } // no quarters are unlocked; throw error?
                                                                                    }
                                                                                }
                                                                                else { echo "<span class=\"log-fail\">Failed</span> to invoice $customer_name ".printDollar($total_cost)." for the service $service_name. An unexpected error has occurred! Please try again later!<br>"; }
                                                                            }
                                                                            else { echo "<span class=\"log-fail\">Failed</span> to to invoice $customer_name for the service $service_name. The service cost could not be found.<br>"; }
                                                                        }
                                                                        else { echo "<span class=\"log-fail\">Failed</span> to invoice $customer_name for the service $service_name. The service does not have a cost set in the to period!<br>"; }
                                                                    }
                                                                }
                                                                // if cost type is membership (2)
                                                                else if ($service_cost_type == 2)
                                                                {
                                                                    $getServiceCost = mysqli_prepare($conn, "SELECT * FROM costs WHERE service_id=? AND cost_type=? AND period_id=?");
                                                                    mysqli_stmt_bind_param($getServiceCost, "sii", $service_id, $service_cost_type, $to);
                                                                    if (mysqli_stmt_execute($getServiceCost))
                                                                    {
                                                                        $result = mysqli_stmt_get_result($getServiceCost);
                                                                        if (mysqli_num_rows($result) > 0) // cost found
                                                                        {
                                                                            // store cost details
                                                                            $cost_details = mysqli_fetch_array($result);
                                                                            $total_membership_cost = $cost_details["cost"];
                                                                            $membership_group = $cost_details["group_id"];

                                                                            // get total group submembers
                                                                            $total_submembers = 0;
                                                                            $getTotalMembers = mysqli_prepare($conn, "SELECT SUM(c.members) AS total_submembers FROM customers c
                                                                                                                    JOIN group_members g ON c.id=g.customer_id
                                                                                                                    WHERE g.group_id=?");
                                                                            mysqli_stmt_bind_param($getTotalMembers, "i", $membership_group);
                                                                            if (mysqli_stmt_execute($getTotalMembers))
                                                                            {
                                                                                $getTotalMembersResult = mysqli_stmt_get_result($getTotalMembers);
                                                                                if (mysqli_num_rows($getTotalMembersResult) > 0) // members found
                                                                                {
                                                                                    $total_submembers = mysqli_fetch_array($getTotalMembersResult)["total_submembers"];
                                                                                }
                                                                            }

                                                                            // get amount of members customer has
                                                                            $customer_members = 0; // assume 0 members
                                                                            $getCustomerMembers = mysqli_prepare($conn, "SELECT members FROM customers WHERE id=?");
                                                                            mysqli_stmt_bind_param($getCustomerMembers, "i", $customer_id);
                                                                            if (mysqli_stmt_execute($getCustomerMembers))
                                                                            {
                                                                                $getCustomerMembersResult = mysqli_stmt_get_result($getCustomerMembers);
                                                                                if (mysqli_num_rows($getCustomerMembersResult) > 0) // customer/members found
                                                                                {
                                                                                    $customer_members = mysqli_fetch_array($getCustomerMembersResult)["members"];
                                                                                }
                                                                            }

                                                                            // get percentage of customer members based on group total
                                                                            if ($total_submembers != 0) { $percentage_of_members = $customer_members / $total_submembers; }
                                                                            else { $percentage_of_members = 0; }

                                                                            // calculate the total cost based on percentage of members
                                                                            $total_cost = ($total_membership_cost * $percentage_of_members);
                                                                            if ($roundCosts == 1) { $total_cost = round($total_cost); } else { $total_cost = round($total_cost, 2); }

                                                                            // add the service provided to the database
                                                                            $provideService = mysqli_prepare($conn, "INSERT INTO services_provided (period_id, service_id, customer_id, quantity, description, date_provided, total_cost) VALUES (?, ?, ?, ?, ?, ?, ?)");
                                                                            mysqli_stmt_bind_param($provideService, "isidssd", $to, $service_id, $customer_id, $quantity, $description, $DB_date, $total_cost);
                                                                            if (mysqli_stmt_execute($provideService)) // successfully provided the service
                                                                            {
                                                                                // get the invoice_id for the new service provied
                                                                                $invoice_id = mysqli_insert_id($conn);

                                                                                // edit the project last updated time
                                                                                if (isset($project) && $project != null) { updateProjectEditTimestamp($conn, $project); }

                                                                                // increment total cost counter
                                                                                $GRAND_TOTAL_COST += $total_cost;

                                                                                // increment successful copies counter
                                                                                $invoices_count++;

                                                                                // by default, insert the quarterly costs equally divided for all quarters
                                                                                $getQuarters = mysqli_prepare($conn, "SELECT * FROM quarters WHERE period_id=?");
                                                                                mysqli_stmt_bind_param($getQuarters, "i", $to);
                                                                                if (mysqli_stmt_execute($getQuarters))
                                                                                {
                                                                                    $results = mysqli_stmt_get_result($getQuarters);
                                                                                    $unlockedQuarters = mysqli_num_rows($results);
                                                                                    
                                                                                    if ($unlockedQuarters > 0) // at least 1 quarter is unlocked
                                                                                    {
                                                                                        // calculate the quarterly cost
                                                                                        $quarterlyCost = number_format((str_replace(",", "", $total_cost) / $unlockedQuarters), 2, ".", "");

                                                                                        // insert the quarterly costs into the database for each quarter
                                                                                        while ($quarter = mysqli_fetch_array($results))
                                                                                        {
                                                                                            $insertQuarterlyCosts = mysqli_prepare($conn, "INSERT INTO quarterly_costs (invoice_id, service_id, customer_id, quarter, cost, period_id) VALUES (?, ?, ?, ?, ?, ?)");
                                                                                            mysqli_stmt_bind_param($insertQuarterlyCosts, "isiidi", $invoice_id, $service_id, $customer_id, $quarter["quarter"], $quarterlyCost, $to);
                                                                                            if (mysqli_stmt_execute($insertQuarterlyCosts))
                                                                                            {
                                                                                                // successfully inserted quarterly cost
                                                                                            }
                                                                                            else { /* TODO */ } // failed to insert quarterly cost; throw error
                                                                                        }
                                                                                    }
                                                                                    else { /* TODO */ } // no quarters are unlocked; throw error?
                                                                                }
                                                                            }
                                                                            else { echo "<span class=\"log-fail\">Failed</span> to invoice $customer_name ".printDollar($total_cost)." for the service $service_name. An unexpected error has occurred! Please try again later!<br>"; }
                                                                        }
                                                                        else { echo "<span class=\"log-fail\">Failed</span> to invoice $customer_name for the service $service_name. The service does not have a cost set in the to period!<br>"; }
                                                                    }
                                                                }
                                                                // if cost type is custom (3)
                                                                else if ($service_cost_type == 3)
                                                                {
                                                                    // get the custom cost from prior invoice
                                                                    $custom_cost = $invoice["total_cost"];
                                                                    
                                                                    if ($custom_cost != null)
                                                                    {
                                                                        if (is_numeric($custom_cost))
                                                                        {
                                                                            // add the service provided to the database
                                                                            $provideService = mysqli_prepare($conn, "INSERT INTO services_provided (period_id, service_id, customer_id, quantity, description, date_provided, total_cost) VALUES (?, ?, ?, ?, ?, ?, ?)");
                                                                            mysqli_stmt_bind_param($provideService, "isidssd", $to, $service_id, $customer_id, $quantity, $description, $DB_date, $custom_cost);
                                                                            if (mysqli_stmt_execute($provideService)) // successfully provided the service
                                                                            {
                                                                                // get the invoice_id for the new service provied
                                                                                $invoice_id = mysqli_insert_id($conn);

                                                                                // edit the project last updated time
                                                                                if (isset($project) && $project != null) { updateProjectEditTimestamp($conn, $project); }

                                                                                // increment total cost counter
                                                                                $GRAND_TOTAL_COST += $custom_cost;

                                                                                // increment successful copies counter
                                                                                $invoices_count++;

                                                                                // by default, insert the quarterly costs equally divided for all quarters
                                                                                $getQuarters = mysqli_prepare($conn, "SELECT * FROM quarters WHERE period_id=?");
                                                                                mysqli_stmt_bind_param($getQuarters, "i", $to);
                                                                                if (mysqli_stmt_execute($getQuarters))
                                                                                {
                                                                                    $results = mysqli_stmt_get_result($getQuarters);
                                                                                    $unlockedQuarters = mysqli_num_rows($results);
                                                                                    
                                                                                    if ($unlockedQuarters > 0) // at least 1 quarter is unlocked
                                                                                    {
                                                                                        // calculate the quarterly cost
                                                                                        $quarterlyCost = number_format((str_replace(",", "", $custom_cost) / $unlockedQuarters), 2, ".", "");

                                                                                        // insert the quarterly costs into the database for each quarter
                                                                                        while ($quarter = mysqli_fetch_array($results))
                                                                                        {
                                                                                            $insertQuarterlyCosts = mysqli_prepare($conn, "INSERT INTO quarterly_costs (invoice_id, service_id, customer_id, quarter, cost, period_id) VALUES (?, ?, ?, ?, ?, ?)");
                                                                                            mysqli_stmt_bind_param($insertQuarterlyCosts, "isiidi", $invoice_id, $service_id, $customer_id, $quarter["quarter"], $quarterlyCost, $to);
                                                                                            if (mysqli_stmt_execute($insertQuarterlyCosts))
                                                                                            {
                                                                                                // successfully inserted quarterly cost
                                                                                            }
                                                                                            else { /* TODO */ } // failed to insert quarterly cost; throw error
                                                                                        }
                                                                                    }
                                                                                    else { /* TODO */ } // no quarters are unlocked; throw error?
                                                                                }
                                                                            }
                                                                            else { echo "<span class=\"log-fail\">Failed</span> to invoice $customer_name ".printDollar($custom_cost)." for the service $service_name. An unexpected error has occurred! Please try again later!<br>"; }
                                                                        }
                                                                        else { echo "<span class=\"log-fail\">Failed</span> to invoice $customer_name for the service $service_name. The cost given was not a number. Please enter a numeric cost and try again!<br>"; }
                                                                    }
                                                                    else { echo "<span class=\"log-fail\">Failed</span> to invoice $customer_name for the service $service_name. The cost given was not a number. Please enter a numeric cost and try again!<br>"; }
                                                                }
                                                                // if cost type is rate-based (4)
                                                                else if ($service_cost_type == 4)
                                                                {
                                                                    // get the current rate from prior invoice
                                                                    $rate_cost = $invoice["total_cost"];

                                                                    // get the current rate tier based on current cost
                                                                    $getTier = mysqli_prepare($conn, "SELECT variable_order FROM costs WHERE service_id=? AND period_id=? AND cost=? AND cost_type=4");
                                                                    mysqli_stmt_bind_param($getTier, "sid", $service_id, $from, $rate_cost);
                                                                    if (mysqli_stmt_execute($getTier))
                                                                    {
                                                                        $getTierResult = mysqli_stmt_get_result($getTier);
                                                                        if (mysqli_num_rows($getTierResult) > 0) // tier found
                                                                        {
                                                                            // store the rate tier locally
                                                                            $rate_tier = mysqli_fetch_array($getTierResult)["variable_order"];

                                                                            // get new tier cost
                                                                            $getNewCost = mysqli_prepare($conn, "SELECT cost FROM costs WHERE service_id=? AND period_id=? AND variable_order=? AND cost_type=4");
                                                                            mysqli_stmt_bind_param($getNewCost, "sii", $service_id, $to, $rate_tier);
                                                                            if (mysqli_stmt_execute($getNewCost))
                                                                            {
                                                                                $getNewCostResult = mysqli_stmt_get_result($getNewCost);
                                                                                if (mysqli_num_rows($getNewCostResult) > 0) // cost found
                                                                                {
                                                                                    $total_cost = mysqli_fetch_array($getNewCostResult)["cost"];

                                                                                    // add the service provided to the database
                                                                                    $provideService = mysqli_prepare($conn, "INSERT INTO services_provided (period_id, service_id, customer_id, quantity, description, date_provided, total_cost) VALUES (?, ?, ?, ?, ?, ?, ?)");
                                                                                    mysqli_stmt_bind_param($provideService, "isidssd", $to, $service_id, $customer_id, $quantity, $description, $DB_date, $total_cost);
                                                                                    if (mysqli_stmt_execute($provideService)) // successfully provided the service
                                                                                    {
                                                                                        // get the invoice_id for the new service provied
                                                                                        $invoice_id = mysqli_insert_id($conn);

                                                                                        // edit the project last updated time
                                                                                        if (isset($project) && $project != null) { updateProjectEditTimestamp($conn, $project); }

                                                                                        // increment total cost counter
                                                                                        $GRAND_TOTAL_COST += $total_cost;

                                                                                        // increment successful copies counter
                                                                                        $invoices_count++;

                                                                                        // by default, insert the quarterly costs equally divided for all quarters
                                                                                        $getQuarters = mysqli_prepare($conn, "SELECT * FROM quarters WHERE period_id=?");
                                                                                        mysqli_stmt_bind_param($getQuarters, "i", $to);
                                                                                        if (mysqli_stmt_execute($getQuarters))
                                                                                        {
                                                                                            $results = mysqli_stmt_get_result($getQuarters);
                                                                                            $unlockedQuarters = mysqli_num_rows($results);
                                                                                            
                                                                                            if ($unlockedQuarters > 0) // at least 1 quarter is unlocked
                                                                                            {
                                                                                                // calculate the quarterly cost
                                                                                                $quarterlyCost = number_format((str_replace(",", "", $total_cost) / $unlockedQuarters), 2, ".", "");

                                                                                                // insert the quarterly costs into the database for each quarter
                                                                                                while ($quarter = mysqli_fetch_array($results))
                                                                                                {
                                                                                                    $insertQuarterlyCosts = mysqli_prepare($conn, "INSERT INTO quarterly_costs (invoice_id, service_id, customer_id, quarter, cost, period_id) VALUES (?, ?, ?, ?, ?, ?)");
                                                                                                    mysqli_stmt_bind_param($insertQuarterlyCosts, "isiidi", $invoice_id, $service_id, $customer_id, $quarter["quarter"], $quarterlyCost, $to);
                                                                                                    if (mysqli_stmt_execute($insertQuarterlyCosts))
                                                                                                    {
                                                                                                        // successfully inserted quarterly cost
                                                                                                    }
                                                                                                    else { /* TODO */ } // failed to insert quarterly cost; throw error
                                                                                                }
                                                                                            }
                                                                                            else { /* TODO */ } // no quarters are unlocked; throw error?
                                                                                        }
                                                                                    }
                                                                                    else { echo "<span class=\"log-fail\">Failed</span> to invoice $customer_name ".printDollar($custom_cost)." for the service $service_name. An unexpected error has occurred! Please try again later!<br>"; }
                                                                                }
                                                                                else { echo "<span class=\"log-fail\">Failed</span> to to invoice $customer_name for the service $service_name. The service cost could not be found.<br>"; }
                                                                            }
                                                                            else { echo "<span class=\"log-fail\">Failed</span> to to invoice $customer_name for the service $service_name. The service could not be found.<br>"; }
                                                                        }
                                                                        else { echo "<span class=\"log-fail\">Failed</span> to to invoice $customer_name for the service $service_name. The service cost rate tier could not be found.<br>"; }
                                                                    }
                                                                    else { echo "<span class=\"log-fail\">Failed</span> to to invoice $customer_name for the service $service_name. The service cost rate tier could not be found.<br>"; }
                                                                }
                                                                // if cost type is group-rate-based (5)
                                                                else if ($service_cost_type == 5)
                                                                {
                                                                    // get the current rate from prior invoice
                                                                    $rate_cost = $invoice["total_cost"];

                                                                    // get the cost associated to the selected tier
                                                                    $getRateGroup = mysqli_prepare($conn, "SELECT group_id FROM costs WHERE service_id=? AND period_id=? AND variable_order=1 AND cost_type=5 LIMIT 1");
                                                                    mysqli_stmt_bind_param($getRateGroup, "si", $service_id, $period_id);
                                                                    if (mysqli_stmt_execute($getRateGroup))
                                                                    {
                                                                        $getRateGroupResult = mysqli_stmt_get_result($getRateGroup);
                                                                        if (mysqli_num_rows($getRateGroupResult) > 0) // group found found
                                                                        {
                                                                            // store the rate group locally
                                                                            $rate_group = mysqli_fetch_array($getRateGroupResult)["group_id"];

                                                                            // check to see if the customer is a member of the group
                                                                            $isMember = 0; // assume customer is not a member of the group 
                                                                            $checkMembership = mysqli_prepare($conn, "SELECT id FROM group_members WHERE group_id=? AND customer_id=?");
                                                                            mysqli_stmt_bind_param($checkMembership, "ii", $rate_group, $customer_id);
                                                                            if (mysqli_stmt_execute($checkMembership))
                                                                            {
                                                                                $checkMembershipResult = mysqli_stmt_get_result($checkMembership);
                                                                                if (mysqli_num_rows($checkMembershipResult) > 0) { $isMember = 1; }
                                                                            }

                                                                            // get the current rate tier based on current cost
                                                                            $getTier = mysqli_prepare($conn, "SELECT variable_order FROM costs WHERE service_id=? AND period_id=? AND cost=? AND in_group=? AND cost_type=5");
                                                                            mysqli_stmt_bind_param($getTier, "sid", $service_id, $from, $rate_cost, $isMember);
                                                                            if (mysqli_stmt_execute($getTier))
                                                                            {
                                                                                $getTierResult = mysqli_stmt_get_result($getTier);
                                                                                if (mysqli_num_rows($getTierResult) > 0) // tier found
                                                                                {
                                                                                    // store the rate tier locally
                                                                                    $rate_tier = mysqli_fetch_array($getTierResult)["variable_order"];

                                                                                    // get new tier cost
                                                                                    $getNewCost = mysqli_prepare($conn, "SELECT cost FROM costs WHERE service_id=? AND period_id=? AND variable_order=? AND in_group=? AND cost_type=5");
                                                                                    mysqli_stmt_bind_param($getNewCost, "sii", $service_id, $to, $rate_tier, $isMember);
                                                                                    if (mysqli_stmt_execute($getNewCost))
                                                                                    {
                                                                                        $getNewCostResult = mysqli_stmt_get_result($getNewCost);
                                                                                        if (mysqli_num_rows($getNewCostResult) > 0) // cost found
                                                                                        {
                                                                                            $total_cost = mysqli_fetch_array($getNewCostResult)["cost"];

                                                                                            // add the service provided to the database
                                                                                            $provideService = mysqli_prepare($conn, "INSERT INTO services_provided (period_id, service_id, customer_id, quantity, description, date_provided, total_cost) VALUES (?, ?, ?, ?, ?, ?, ?)");
                                                                                            mysqli_stmt_bind_param($provideService, "isidssd", $to, $service_id, $customer_id, $quantity, $description, $DB_date, $total_cost);
                                                                                            if (mysqli_stmt_execute($provideService)) // successfully provided the service
                                                                                            {
                                                                                                // get the invoice_id for the new service provied
                                                                                                $invoice_id = mysqli_insert_id($conn);

                                                                                                // edit the project last updated time
                                                                                                if (isset($project) && $project != null) { updateProjectEditTimestamp($conn, $project); }

                                                                                                // increment total cost counter
                                                                                                $GRAND_TOTAL_COST += $total_cost;

                                                                                                // increment successful copies counter
                                                                                                $invoices_count++;

                                                                                                // by default, insert the quarterly costs equally divided for all quarters
                                                                                                $getQuarters = mysqli_prepare($conn, "SELECT * FROM quarters WHERE period_id=?");
                                                                                                mysqli_stmt_bind_param($getQuarters, "i", $to);
                                                                                                if (mysqli_stmt_execute($getQuarters))
                                                                                                {
                                                                                                    $results = mysqli_stmt_get_result($getQuarters);
                                                                                                    $unlockedQuarters = mysqli_num_rows($results);
                                                                                                    
                                                                                                    if ($unlockedQuarters > 0) // at least 1 quarter is unlocked
                                                                                                    {
                                                                                                        // calculate the quarterly cost
                                                                                                        $quarterlyCost = number_format((str_replace(",", "", $total_cost) / $unlockedQuarters), 2, ".", "");

                                                                                                        // insert the quarterly costs into the database for each quarter
                                                                                                        while ($quarter = mysqli_fetch_array($results))
                                                                                                        {
                                                                                                            $insertQuarterlyCosts = mysqli_prepare($conn, "INSERT INTO quarterly_costs (invoice_id, service_id, customer_id, quarter, cost, period_id) VALUES (?, ?, ?, ?, ?, ?)");
                                                                                                            mysqli_stmt_bind_param($insertQuarterlyCosts, "isiidi", $invoice_id, $service_id, $customer_id, $quarter["quarter"], $quarterlyCost, $to);
                                                                                                            if (mysqli_stmt_execute($insertQuarterlyCosts))
                                                                                                            {
                                                                                                                // successfully inserted quarterly cost
                                                                                                            }
                                                                                                            else { /* TODO */ } // failed to insert quarterly cost; throw error
                                                                                                        }
                                                                                                    }
                                                                                                    else { /* TODO */ } // no quarters are unlocked; throw error?
                                                                                                }
                                                                                            }
                                                                                            else { echo "<span class=\"log-fail\">Failed</span> to invoice $customer_name ".printDollar($custom_cost)." for the service $service_name. An unexpected error has occurred! Please try again later!<br>"; }
                                                                                        }
                                                                                        else { echo "<span class=\"log-fail\">Failed</span> to to invoice $customer_name for the service $service_name. The service cost could not be found.<br>"; }
                                                                                    }
                                                                                    else { echo "<span class=\"log-fail\">Failed</span> to to invoice $customer_name for the service $service_name. The service could not be found.<br>"; }
                                                                                }
                                                                                else { echo "<span class=\"log-fail\">Failed</span> to to invoice $customer_name for the service $service_name. The service cost rate tier could not be found.<br>"; }
                                                                            }
                                                                            else { echo "<span class=\"log-fail\">Failed</span> to to invoice $customer_name for the service $service_name. The service cost rate tier could not be found.<br>"; }
                                                                        }
                                                                        else { echo "<span class=\"log-fail\">Failed</span> to to invoice $customer_name for the service $service_name. The service cost rate group could not be found.<br>"; }
                                                                    }
                                                                    else { echo "<span class=\"log-fail\">Failed</span> to invoice $customer_name for the service $service_name. An unexpected error has occurred! Please try again later!<br>"; }
                                                                }
                                                                else { echo "<span class=\"log-fail\">Failed</span> to invoice $customer_name for the service $service_name. An unexpected error has occurred! Please try again later!<br>"; }
                                                            } 
                                                            else { echo "<span class=\"log-fail\">Failed</span> to copy an invoice for the service with ID $service_id. The customer with ID $customer_id no longer exists!<br>"; }
                                                        }
                                                    }
                                                    else { echo "<span class=\"log-fail\">Failed</span> to copy an invoice for the service with ID $service_id. The service no longer exists!<br>"; }
                                                }
                                            }
                                        }
                                        else { echo "<span class=\"log-fail\">Failed</span> to copy invoices. The period you are trying to copy invoices from did not have any existing invoices!<br>"; }
                                    }

                                    // get other services invoices
                                    $getOtherServicesInvoices = mysqli_prepare($conn, "SELECT * FROM services_other_provided WHERE period_id=?");
                                    mysqli_stmt_bind_param($getOtherServicesInvoices, "i", $from);
                                    if (mysqli_stmt_execute($getOtherServicesInvoices))
                                    {
                                        $getOtherServicesInvoicesResults = mysqli_stmt_get_result($getOtherServicesInvoices);
                                        if (mysqli_num_rows($getOtherServicesInvoicesResults) > 0) // invoices for "other services" found
                                        {
                                            // for each invoice; copy invoice into the "to" period based on the "to" period's costs
                                            while ($invoice = mysqli_fetch_array($getOtherServicesInvoicesResults))
                                            {
                                                // store invoice details locally
                                                $service_id = $invoice["service_id"];
                                                $customer_id = $invoice["customer_id"];
                                                $quantity = $invoice["quantity"];
                                                $project = $invoice["project_code"];
                                                $total_cost = $invoice["total_cost"];
                                                $description = $invoice["description"];
                                                $unit_label = $invoice["unit_label"];

                                                // get service details
                                                $getServiceDetails = mysqli_prepare($conn, "SELECT name FROM services_other WHERE id=?");
                                                mysqli_stmt_bind_param($getServiceDetails, "s", $service_id);
                                                if (mysqli_stmt_execute($getServiceDetails))
                                                {
                                                    $getServiceDetailsResult = mysqli_stmt_get_result($getServiceDetails);
                                                    if (mysqli_num_rows($getServiceDetailsResult) > 0) // service found; continue
                                                    {
                                                        // store service details locally
                                                        $serviceDetails = mysqli_fetch_array($getServiceDetailsResult);
                                                        $service_name = $serviceDetails["name"];

                                                        // get customer details
                                                        $getCustomerDetails = mysqli_prepare($conn, "SELECT name FROM customers WHERE id=?");
                                                        mysqli_stmt_bind_param($getCustomerDetails, "i", $customer_id);
                                                        if (mysqli_stmt_execute($getCustomerDetails))
                                                        {
                                                            $getCustomerDetailsResult = mysqli_stmt_get_result($getCustomerDetails);
                                                            if (mysqli_num_rows($getCustomerDetailsResult) > 0) // customer found; continue
                                                            {
                                                                // store customer details locally
                                                                $customerDetails = mysqli_fetch_array($getCustomerDetailsResult);
                                                                $customer_name = $customerDetails["name"];

                                                                // copy invoice over 1:1 (no cost adjustment)
                                                                $copyInvoice = mysqli_prepare($conn, "INSERT INTO services_other_provided (period_id, service_id, customer_id, total_cost, quantity, description, date_provided, unit_label, project_code) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                                                                mysqli_stmt_bind_param($copyInvoice, "isiddssss", $to, $service_id, $customer_id, $total_cost, $quantity, $description, $DB_date, $unit_label, $project);
                                                                if (mysqli_stmt_execute($copyInvoice)) // successfully copied the invoice
                                                                {
                                                                    // get the invoice_id for the new service provied
                                                                    $invoice_id = mysqli_insert_id($conn);

                                                                    // edit the project last updated time
                                                                    if (isset($project) && $project != null) { updateProjectEditTimestamp($conn, $project); }

                                                                    // increment total cost counter
                                                                    $GRAND_TOTAL_COST += $total_cost;

                                                                    // increment successful copies counter
                                                                    $invoices_count++;

                                                                    // by default, insert the quarterly costs equally divided for all quarters
                                                                    $getQuarters = mysqli_prepare($conn, "SELECT * FROM quarters WHERE period_id=?");
                                                                    mysqli_stmt_bind_param($getQuarters, "i", $to);
                                                                    if (mysqli_stmt_execute($getQuarters))
                                                                    {
                                                                        $results = mysqli_stmt_get_result($getQuarters);
                                                                        $unlockedQuarters = mysqli_num_rows($results);
                                                                        
                                                                        if ($unlockedQuarters > 0) // at least 1 quarter is unlocked
                                                                        {
                                                                            // calculate the quarterly cost
                                                                            $quarterlyCost = number_format((str_replace(",", "", $total_cost) / $unlockedQuarters), 2, ".", "");

                                                                            // insert the quarterly costs into the database for each quarter
                                                                            while ($quarter = mysqli_fetch_array($results))
                                                                            {
                                                                                $insertQuarterlyCosts = mysqli_prepare($conn, "INSERT INTO other_quarterly_costs (other_invoice_id, other_service_id, customer_id, quarter, cost, period_id) VALUES (?, ?, ?, ?, ?, ?)");
                                                                                mysqli_stmt_bind_param($insertQuarterlyCosts, "isiidi", $invoice_id, $service_id, $customer_id, $quarter["quarter"], $quarterlyCost, $to);
                                                                                if (mysqli_stmt_execute($insertQuarterlyCosts))
                                                                                {
                                                                                    // successfully inserted quarterly cost
                                                                                }
                                                                                else { /* TODO */ } // failed to insert quarterly cost; throw error
                                                                            }
                                                                        }
                                                                        else { /* TODO */ } // no quarters are unlocked; throw error?
                                                                    }
                                                                }
                                                                else { echo "<span class=\"log-fail\">Failed</span> to invoice $customer_name for service $service_name.<br>"; }
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }

                                    // log successful copies to screen
                                    echo "<span class=\"log-success\">Successfully</span> copied $invoices_count invoices from $from_label to $to_label for a grand total of ".printDollar($GRAND_TOTAL_COST)."<br>"; 
                                }
                                else { echo "<span class=\"log-fail\">Failed</span> to copy invoices. You cannot copy invoices into the same period you are copying invoices from.<br>"; }
                            }
                        }
                    }
                    else { echo "<span class=\"log-fail\">Failed</span> to copy invoices. The period you are trying to copy invoices from does not exist!<br>"; }
                }
            }
            else { echo "<span class=\"log-fail\">Failed</span> to copy invoices. You must select both a period to copy invoices from and a period to copy invoices to.<br>"; }

            // disconnect from the database
            mysqli_close($conn);
        }
    }
?>