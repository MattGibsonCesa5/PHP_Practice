<?php
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get additional required files
        include("../../../includes/config.php");
        include("../../../includes/functions.php");
        include("../../../getSettings.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "INVOICE_OTHER_SERVICES"))
        {
            // get service details from POST
            if (isset($_POST["period"]) && $_POST["period"] <> "") { $period = $_POST["period"]; } else { $period = null; }
            if (isset($_POST["service_id"]) && $_POST["service_id"] <> "") { $service_id = $_POST["service_id"]; } else { $service_id = null; }
            if (isset($_POST["customer_id"]) && $_POST["customer_id"] <> "") { $customer_id = $_POST["customer_id"]; } else { $customer_id = null; }
            if (isset($_POST["project_code"]) && $_POST["project_code"] <> "") { $project_code = $_POST["project_code"]; } else { $project_code = null; }
            if (isset($_POST["total_cost"]) && $_POST["total_cost"] <> "" && is_numeric($_POST["total_cost"])) { $total_cost = $_POST["total_cost"]; } else { $total_cost = 0; }
            if (isset($_POST["quantity"]) && $_POST["quantity"] <> "" && is_numeric($_POST["quantity"])) { $quantity = $_POST["quantity"]; } else { $quantity = 0; }
            if (isset($_POST["unit_label"]) && $_POST["unit_label"] <> "") { $unit_label = $_POST["unit_label"]; } else { $unit_label = null; }
            if (isset($_POST["description"]) && $_POST["description"] <> "") { $description = $_POST["description"]; } else { $description = null; }
            if (isset($_POST["date"]) && $_POST["date"] <> "") { $date = date("Y-m-d", strtotime($_POST["date"])); } else { $date = date("Y-m-d"); }

            if ($period_id = getPeriodID($conn, $period))
            {
                if ($service_id != null && $customer_id != null)
                {
                    if ($unit_label != null)
                    {
                        if ($description != null)
                        {
                            // initialize variable to verify project code
                            $project_verified = false;

                            if ($project_code != null) // project code was selected; verify it exists
                            {
                                $checkProject = mysqli_prepare($conn, "SELECT code FROM projects WHERE code=?");
                                mysqli_stmt_bind_param($checkProject, "s", $project_code);
                                if (mysqli_stmt_execute($checkProject))
                                {
                                    $checkProjectResult = mysqli_stmt_get_result($checkProject);
                                    if (mysqli_num_rows($checkProjectResult) > 0) // project exists; verify
                                    {
                                        $project_verified = true;
                                    }
                                }
                            }
                            else { $project_verified = true; } // no project selected; verify project exists (project not required)

                            if ($project_verified === true) // project is verified (or no project selected)
                            {
                                // verify the service exists
                                $checkService = mysqli_prepare($conn, "SELECT id, name FROM services_other WHERE id=?");
                                mysqli_stmt_bind_param($checkService, "s", $service_id);
                                if (mysqli_stmt_execute($checkService))
                                {
                                    $checkServiceResult = mysqli_stmt_get_result($checkService);
                                    if (mysqli_num_rows($checkServiceResult) > 0) // service exists; continue
                                    {
                                        // store the service name
                                        $service_name = mysqli_fetch_array($checkServiceResult)["name"];

                                        // verify the customer exists
                                        $checkCustomer = mysqli_prepare($conn, "SELECT id, name FROM customers WHERE id=?");
                                        mysqli_stmt_bind_param($checkCustomer, "i", $customer_id);
                                        if (mysqli_stmt_execute($checkCustomer))
                                        {
                                            $checkCustomerResult = mysqli_stmt_get_result($checkCustomer);
                                            if (mysqli_num_rows($checkCustomerResult) > 0) // customer exists; continue
                                            {
                                                // store the customer name
                                                $customer_name = mysqli_fetch_array($checkCustomerResult)["name"];

                                                // check to see if the customer has already been provided this other service (limited to the service once)
                                                $checkProvided = mysqli_prepare($conn, "SELECT id FROM services_other_provided WHERE period_id=? AND service_id=? AND customer_id=?");
                                                mysqli_stmt_bind_param($checkProvided, "isi", $period_id, $service_id, $customer_id);
                                                if (mysqli_stmt_execute($checkProvided))
                                                {
                                                    $checkProvidedResult = mysqli_stmt_get_result($checkProvided);
                                                    if (mysqli_num_rows($checkProvidedResult) == 0) // other service has not been provided to this customer yet; continue
                                                    {
                                                        // provide the service
                                                        $addInvoice = mysqli_prepare($conn, "INSERT INTO services_other_provided (period_id, service_id, customer_id, total_cost, quantity, description, date_provided, unit_label, project_code) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                                                        mysqli_stmt_bind_param($addInvoice, "isiddssss", $period_id, $service_id, $customer_id, $total_cost, $quantity, $description, $date, $unit_label, $project_code);
                                                        if (mysqli_stmt_execute($addInvoice)) // successfully created the invoice
                                                        {
                                                            echo "<span class=\"log-success\">Successfully</span> invoiced $customer_name for ".printDollar($total_cost)." for the service $service_name.";

                                                            // get the newly created invoice id
                                                            $invoice_id = mysqli_insert_id($conn);

                                                            // by default, insert the quarterly costs equally divided for quarters that are unlocked
                                                            $getQuarters = mysqli_prepare($conn, "SELECT * FROM quarters WHERE locked=0 AND period_id=?");
                                                            mysqli_stmt_bind_param($getQuarters, "i", $period_id);
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
                                                                        mysqli_stmt_bind_param($insertQuarterlyCosts, "isiidi", $invoice_id, $service_id, $customer_id, $quarter["quarter"], $quarterlyCost, $period_id);
                                                                        mysqli_stmt_execute($insertQuarterlyCosts);
                                                                    }
                                                                }
                                                            }
                                                        }
                                                        else { echo "<span class=\"log-fail\">Failed</span> to invoice $customer_name for the service $service_name. An unexpected error has occurred. Please try again later!"; }
                                                    }
                                                    else // this other service has already been provided to this customer; break
                                                    {
                                                        echo "<span class=\"log-fail\">Failed</span> to invoice $customer_name for the service $service_name. This service has already been provided to this customer. Either delete or edit the current invoice to make changes.";
                                                    }
                                                }
                                                else { echo "<span class=\"log-fail\">Failed</span> to create the invoice. An unexpected error has occurred. Please try again later!"; }
                                            }
                                            else { echo "<span class=\"log-fail\">Failed</span> to create the invoice. The customer with ID $customer_id does not exist!"; }
                                        }
                                        else { echo "<span class=\"log-fail\">Failed</span> to create the invoice. An unexpected error has occurred. Please try again later!"; }
                                    }
                                    else { echo "<span class=\"log-fail\">Failed</span> to create the invoice. The service with ID $service_id does not exist!"; }
                                }
                                else { echo "<span class=\"log-fail\">Failed</span> to create the invoice. An unexpected error has occurred. Please try again later!"; }
                            }
                            else { echo "<span class=\"log-fail\">Failed</span> to create the invoice. The project selected does not exist!"; }
                        }
                        else { echo "<span class=\"log-fail\">Failed</span> to create the invoice. You must provide a description for this invoice."; }
                    }
                    else { echo "<span class=\"log-fail\">Failed</span> to create the invoice. You must provide a unit label for this invoice."; }
                }
                else { echo "<span class=\"log-fail\">Failed</span> to create the invoice. You must select both a customer and service to create the invoice for."; }
            }
            else { echo "<span class=\"log-fail\">Failed</span> to create the invoice. Failed to verify the period selected. Please try again later!<br>"; }
        }
        else { echo "<span class=\"log-fail\">Failed</span> to create the invoice. Your account does not have permission to manage invoices for other services!<br>"; }

        // disconnect from the database
        mysqli_close($conn);
    }
?>