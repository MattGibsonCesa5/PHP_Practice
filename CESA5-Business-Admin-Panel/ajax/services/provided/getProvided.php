<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // initialize the array of data we will print
        $invoices = [];

        // get additional required files
        include("../../../includes/config.php");
        include("../../../includes/functions.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        // get the period from POST
        if (isset($_POST["period"]) && $_POST["period"] <> "") { $period = $_POST["period"]; } else { $period = null; }

        if ($period != null && $period_id = getPeriodID($conn, $period)) // verify the period exists; if it exists, store the period ID
        {
            // get additional details of the period selected
            $period_name = "";
            $getPeriodDetails = mysqli_prepare($conn, "SELECT name, active, editable FROM periods WHERE id=?");
            mysqli_stmt_bind_param($getPeriodDetails, "i", $period_id);
            if (mysqli_stmt_execute($getPeriodDetails))
            {
                $getPeriodDetailsResult = mysqli_stmt_get_result($getPeriodDetails);
                if (mysqli_num_rows($getPeriodDetailsResult) > 0)
                {
                    $periodDetails = mysqli_fetch_array($getPeriodDetailsResult);
                    $period_name = $periodDetails["name"];
                    $is_active = $periodDetails["active"];
                    $is_editable = $periodDetails["editable"];
                }
            }

            // get the quarters status
            $q1_locked = checkLocked($conn, 1, $period_id);
            $q2_locked = checkLocked($conn, 2, $period_id);
            $q3_locked = checkLocked($conn, 3, $period_id);
            $q4_locked = checkLocked($conn, 4, $period_id);

            ///////////////////////////////////////////////////////////////////////////////////////////
            //
            //  REGULAR SERVICE INVOICES
            //
            ///////////////////////////////////////////////////////////////////////////////////////////
            if (checkUserPermission($conn, "VIEW_INVOICES_ALL") || checkUserPermission($conn, "VIEW_INVOICES_ASSIGNED"))
            {
                // store the user's permission for editiing invoices locally
                $user_can_edit = checkUserPermission($conn, "EDIT_INVOICES");

                // build and prepare query to get invoices based on the user's role
                if (checkUserPermission($conn, "VIEW_INVOICES_ALL"))
                {
                    $getInvoices = mysqli_prepare($conn, "SELECT sp.*, s.name AS service_name, c.name AS customer_name FROM services_provided sp 
                                                            JOIN services s ON sp.service_id=s.id
                                                            JOIN customers c ON sp.customer_id=c.id
                                                            WHERE sp.period_id=?");
                    mysqli_stmt_bind_param($getInvoices, "i", $period_id);
                }
                else if (checkUserPermission($conn, "VIEW_INVOICES_ASSIGNED"))
                {
                    $getInvoices = mysqli_prepare($conn, "SELECT sp.*, s.name AS service_name, c.name AS customer_name FROM services_provided sp 
                                                            JOIN services s ON sp.service_id=s.id 
                                                            JOIN customers c ON sp.customer_id=c.id
                                                            JOIN projects p ON s.project_code=p.code 
                                                            JOIN departments d ON p.department_id=d.id 
                                                            WHERE (d.director_id=? OR d.secondary_director_id=?) AND sp.period_id=?");
                    mysqli_stmt_bind_param($getInvoices, "iii", $_SESSION["id"], $_SESSION["id"], $period_id);
                }

                // execute the query to get invoices
                if (mysqli_stmt_execute($getInvoices))
                {
                    $getInvoicesResults = mysqli_stmt_get_result($getInvoices);
                    if (mysqli_num_rows($getInvoicesResults) > 0)
                    {
                        while ($invoice = mysqli_fetch_array($getInvoicesResults)) 
                        {
                            // initialize temporary array to store invoice details to be returned
                            $temp = [];

                            // get invoice details 
                            $invoice_id = $invoice["id"];
                            $period_id = $invoice["period_id"];
                            $service_id = $invoice["service_id"];
                            $service_name = $invoice["service_name"];
                            $invoice_cost = $invoice["total_cost"];
                            $customer_id = $invoice["customer_id"];
                            $customer_name = $invoice["customer_name"];

                            // get the quarterly costs for the invoice
                            $quarterlyCosts = [];
                            $quarterlyCosts["Q1"] = $quarterlyCosts["Q2"] = $quarterlyCosts["Q3"] = $quarterlyCosts["Q4"] = 0;
                            $getQuarterlyCosts = mysqli_prepare($conn, "SELECT cost, quarter FROM quarterly_costs WHERE invoice_id=?");
                            mysqli_stmt_bind_param($getQuarterlyCosts, "i", $invoice_id);
                            if (mysqli_stmt_execute($getQuarterlyCosts))
                            {
                                $getQuarterlyCostsResults = mysqli_stmt_get_result($getQuarterlyCosts);
                                if (mysqli_num_rows($getQuarterlyCostsResults) > 0) // quarterly costs found
                                {
                                    // for each quarterly cost found, add to array
                                    while ($quarter = mysqli_fetch_array($getQuarterlyCostsResults))
                                    {
                                        // store the quarter's cost locally
                                        $q = $quarter["quarter"];
                                        $cost = $quarter["cost"];

                                        // add the quarter's cost to the array
                                        $quarterlyCosts["Q$q"] = $cost;
                                    }
                                }
                            }

                            // build the invoice ID column
                            $display_invoice_id = "<button class='btn btn-link w-100 p-1' type='button' onclick='getInvoiceDetailsModal(".$invoice_id.");'>".$invoice_id."</button>";
                            $temp["invoice_id"] = $display_invoice_id;

                            // create the hidden quarterly costs for calculations
                            if (isset($quarterlyCosts["Q1"])) { $temp["q1_cost"] = $quarterlyCosts["Q1"]; } else { $temp["q1_cost"] = 0; }
                            if (isset($quarterlyCosts["Q2"])) { $temp["q2_cost"] = $quarterlyCosts["Q2"]; } else { $temp["q2_cost"] = 0; }
                            if (isset($quarterlyCosts["Q3"])) { $temp["q3_cost"] = $quarterlyCosts["Q3"]; } else { $temp["q3_cost"] = 0; }
                            if (isset($quarterlyCosts["Q4"])) { $temp["q4_cost"] = $quarterlyCosts["Q4"]; } else { $temp["q4_cost"] = 0; }

                            // quarter 1
                            if ($q1_locked === true || $is_editable == 0 || $user_can_edit === false) { $temp["q1_cost_display"] = "<input class='form-control' id='edit-q1_cost-$invoice_id' aria-labelledby='edit-q1_cost' value='".sprintf("%0.2f", $quarterlyCosts["Q1"])."' type='number' onchange='checkQuarterlyCosts($invoice_id);' disabled>"; }
                            else { $temp["q1_cost_display"] = "<input class='form-control' id='edit-q1_cost-$invoice_id' aria-labelledby='edit-q1_cost' value='".sprintf("%0.2f", $quarterlyCosts["Q1"])."' type='number' onchange='checkQuarterlyCosts($invoice_id);'>"; }
                            // quarter 2
                            if ($q2_locked === true || $is_editable == 0 || $user_can_edit === false) { $temp["q2_cost_display"] = "<input class='form-control' id='edit-q2_cost-$invoice_id' aria-labelledby='edit-q2_cost' value='".sprintf("%0.2f", $quarterlyCosts["Q2"])."' type='number' onchange='checkQuarterlyCosts($invoice_id);' disabled>"; }
                            else { $temp["q2_cost_display"] = "<input class='form-control' id='edit-q2_cost-$invoice_id' aria-labelledby='edit-q2_cost' value='".sprintf("%0.2f", $quarterlyCosts["Q2"])."' type='number' onchange='checkQuarterlyCosts($invoice_id);'>"; }
                            // quarter 3
                            if ($q3_locked === true || $is_editable == 0 || $user_can_edit === false) { $temp["q3_cost_display"] = "<input class='form-control' id='edit-q3_cost-$invoice_id' aria-labelledby='edit-q3_cost' value='".sprintf("%0.2f", $quarterlyCosts["Q3"])."' type='number' onchange='checkQuarterlyCosts($invoice_id);' disabled>"; }
                            else { $temp["q3_cost_display"] = "<input class='form-control' id='edit-q3_cost-$invoice_id' aria-labelledby='edit-q3_cost' value='".sprintf("%0.2f", $quarterlyCosts["Q3"])."' type='number' onchange='checkQuarterlyCosts($invoice_id);'>"; }
                            // quarter 4
                            if ($q4_locked === true || $is_editable == 0 || $user_can_edit === false) { $temp["q4_cost_display"] = "<input class='form-control' id='edit-q4_cost-$invoice_id' aria-labelledby='edit-q4_cost' value='".sprintf("%0.2f", $quarterlyCosts["Q4"])."' type='number' onchange='checkQuarterlyCosts($invoice_id);' disabled>"; }
                            else { $temp["q4_cost_display"] = "<input class='form-control' id='edit-q4_cost-$invoice_id' aria-labelledby='edit-q4_cost' value='".sprintf("%0.2f", $quarterlyCosts["Q4"])."' type='number' onchange='checkQuarterlyCosts($invoice_id);'>"; }

                            // create the cost columns
                            $quarterlyCostsSum = $quarterlyCosts["Q1"] + $quarterlyCosts["Q2"] + $quarterlyCosts["Q3"] + $quarterlyCosts["Q4"];
                            $temp["quarterly_cost_sum"] = "<input class='form-control' id='edit-quarterly_cost_sum-$invoice_id' aria-labelledby='edit-quarterly_cost_sum' value='".sprintf("%0.2f", $quarterlyCostsSum)."' type='number' disabled readonly>";
                            $temp["total_cost"] = printDollar($invoice_cost);

                            // build the actions column
                            $actions = "<div class='d-flex justify-content-end'>
                                <button class='btn btn-success btn-sm mx-1' id='edit-status-$invoice_id'><i class='fa-solid fa-check'></i></button>";
                                if ($is_editable == 1 && $user_can_edit === true) // only display save and reset if we are allowed to make changes in the period selected
                                { 
                                    $actions .= "<button class='btn btn-secondary btn-sm mx-1' id='edit-update-$invoice_id' aria-labelledby='edit-actions' onclick='updateQuarterlyCosts($invoice_id);' disabled><i class='fa-solid fa-floppy-disk'></i></button>
                                    <button class='btn btn-secondary btn-sm mx-1' id='edit-reset-$invoice_id' aria-labelledby='edit-actions' onclick='resetQuarterlyCosts($invoice_id);'><i class='fa-solid fa-rotate-left'></i></button>";
                                }
                                if (checkUserPermission($conn, "EDIT_INVOICES") && $is_editable == 1) { $actions .= "<button class='btn btn-primary btn-sm mx-1' type='button' onclick='getEditInvoiceModal(".$invoice_id.");'><i class='fa-solid fa-pencil'></i></button>"; }
                                if (checkUserPermission($conn, "DELETE_INVOICES") && $is_editable == 1) { $actions .= "<button class='btn btn-danger btn-sm mx-1' type='button' onclick='getDeleteInvoiceModal(".$invoice_id.");'><i class='fa-solid fa-trash-can'></i></button>"; }
                            $actions .= "</div>";

                            // add more data to temporary array 
                            $temp["invoice_id"] = $display_invoice_id;
                            $temp["service_id"] = $service_id;
                            $temp["service_name"] = $service_name;
                            $temp["customer_id"] = $customer_id;
                            $temp["customer_name"] = $customer_name;
                            $temp["description"] = $invoice["description"];
                            $temp["date"] = date("n/j/Y", strtotime($invoice["date_provided"]));
                            $temp["quantity"] = $invoice["quantity"];
                            $temp["total_cost"] = printDollar($invoice["total_cost"]);
                            $temp["actions"] = $actions;

                            // add temporary array to master invoice listing
                            $invoices[] = $temp;
                        }                                    
                    }
                }    
            }

            ///////////////////////////////////////////////////////////////////////////////////////////
            //
            //  OTHER SERVICE INVOICES
            //
            ///////////////////////////////////////////////////////////////////////////////////////////
            if (checkUserPermission($conn, "VIEW_OTHER_SERVICES"))
            {
                // store the user's permission for editiing invoices locally
                $user_can_edit = checkUserPermission($conn, "INVOICE_OTHER_SERVICES");

                // get other services invoices
                $getBilling = mysqli_prepare($conn, "SELECT i.id AS invoice_id, i.service_id, i.customer_id, s.name AS service_name, c.name AS customer_name FROM services_other_provided i 
                                                    JOIN services_other s ON i.service_id=s.id
                                                    JOIN customers c ON i.customer_id=c.id
                                                    WHERE period_id=?");
                mysqli_stmt_bind_param($getBilling, "i", $period_id);
                if (mysqli_stmt_execute($getBilling))
                {
                    $getBillingResults = mysqli_stmt_get_result($getBilling);
                    if (mysqli_num_rows($getBillingResults) > 0) // service has been provided to the customer
                    {
                        while ($invoice = mysqli_fetch_array($getBillingResults))
                        {
                            // get billing details;
                            $invoice_id = $invoice["invoice_id"];
                            $service_id = $invoice["service_id"];
                            $service_name = $invoice["service_name"];
                            $customer_id = $invoice["customer_id"];
                            $customer_name = $invoice["customer_name"];

                            // get the total cost and total quantity from the services_provided table
                            $getProvidedSums = mysqli_prepare($conn, "SELECT SUM(quantity) AS quantity, SUM(total_cost) AS total_cost, description, date_provided FROM services_other_provided WHERE service_id=? AND customer_id=? AND period_id=?");
                            mysqli_stmt_bind_param($getProvidedSums, "sii", $service_id, $customer_id, $period_id);
                            if (mysqli_stmt_execute($getProvidedSums))
                            {
                                $providedSumsResult = mysqli_stmt_get_result($getProvidedSums);
                                $providedSums = mysqli_fetch_array($providedSumsResult);
                                $total_quantity = $providedSums["quantity"];
                                $description = $providedSums["description"];
                                $date = $providedSums["date_provided"];
                                $total_cost = $providedSums["total_cost"];
                            }

                            // get the total quarterly costs from the quarterly_costs table for each quarter
                            $quarterlyCosts = [];
                            for ($q = 1; $q <= 4; $q++)
                            {
                                $getQuarterlySum = mysqli_prepare($conn, "SELECT SUM(cost) AS cost FROM other_quarterly_costs WHERE other_service_id=? AND customer_id=? AND quarter=? AND period_id=?");
                                mysqli_stmt_bind_param($getQuarterlySum, "siii", $service_id, $customer_id, $q, $period_id);
                                if (mysqli_stmt_execute($getQuarterlySum))
                                {
                                    $quarterlyResult = mysqli_stmt_get_result($getQuarterlySum);
                                    if (mysqli_num_rows($quarterlyResult) > 0)
                                    {
                                        $quarterlyCosts[$q] = mysqli_fetch_array($quarterlyResult)["cost"];
                                    }
                                }
                            }

                            // build the invoice ID column
                            $display_invoice_id = "<button class='btn btn-link w-100 p-1' type='button' onclick='getOtherInvoiceDetailsModal(".$invoice_id.");'>".$invoice_id."</button>";

                            // create the temporary array that stores the service provided for the customer provided
                            $temp = [];
                            $temp["invoice_id"] = $display_invoice_id;
                            $temp["customer_id"] = $customer_id;
                            $temp["customer_name"] = $customer_name;
                            $temp["service_id"] = $service_id;
                            $temp["service_name"] = $service_name;
                            $temp["quantity"] = $total_quantity;
                            $temp["description"] = $description;
                            $temp["date"] = date("n/j/Y", strtotime($date));

                            // create the hidden quarterly costs for summing
                            if (isset($quarterlyCosts[1])) { $temp["q1_cost"] = number_format($quarterlyCosts[1], 2, ".", ""); } else { $temp["q1_cost"] = 0; }
                            if (isset($quarterlyCosts[2])) { $temp["q2_cost"] = number_format($quarterlyCosts[2], 2, ".", ""); } else { $temp["q2_cost"] = 0; }
                            if (isset($quarterlyCosts[3])) { $temp["q3_cost"] = number_format($quarterlyCosts[3], 2, ".", ""); } else { $temp["q3_cost"] = 0; }
                            if (isset($quarterlyCosts[4])) { $temp["q4_cost"] = number_format($quarterlyCosts[4], 2, ".", ""); } else { $temp["q4_cost"] = 0; }

                            // quarter 1
                            if (checkLocked($conn, 1, $period_id) || $is_editable == 0 || $user_can_edit === false) { $temp["q1_cost_display"] = "<input class='form-control' id='edit-q1_cost-$invoice_id' aria-labelledby='edit-q1_cost' value='".sprintf("%0.2f", $quarterlyCosts[1])."' type='number' onchange='checkOtherQuarterlyCosts($invoice_id);' disabled>"; }
                            else { $temp["q1_cost_display"] = "<input class='form-control' id='edit-q1_cost-$invoice_id' aria-labelledby='edit-q1_cost' value='".sprintf("%0.2f", $quarterlyCosts[1])."' type='number' onchange='checkOtherQuarterlyCosts($invoice_id);'>"; }
                            // quarter 2
                            if (checkLocked($conn, 2, $period_id) || $is_editable == 0 || $user_can_edit === false) { $temp["q2_cost_display"] = "<input class='form-control' id='edit-q2_cost-$invoice_id' aria-labelledby='edit-q2_cost' value='".sprintf("%0.2f", $quarterlyCosts[2])."' type='number' onchange='checkOtherQuarterlyCosts($invoice_id);' disabled>"; }
                            else { $temp["q2_cost_display"] = "<input class='form-control' id='edit-q2_cost-$invoice_id' aria-labelledby='edit-q2_cost' value='".sprintf("%0.2f", $quarterlyCosts[2])."' type='number' onchange='checkOtherQuarterlyCosts($invoice_id);'>"; }
                            // quarter 3
                            if (checkLocked($conn, 3, $period_id) || $is_editable == 0 || $user_can_edit === false) { $temp["q3_cost_display"] = "<input class='form-control' id='edit-q3_cost-$invoice_id' aria-labelledby='edit-q3_cost' value='".sprintf("%0.2f", $quarterlyCosts[3])."' type='number' onchange='checkOtherQuarterlyCosts($invoice_id);' disabled>"; }
                            else { $temp["q3_cost_display"] = "<input class='form-control' id='edit-q3_cost-$invoice_id' aria-labelledby='edit-q3_cost' value='".sprintf("%0.2f", $quarterlyCosts[3])."' type='number' onchange='checkOtherQuarterlyCosts($invoice_id);'>"; }
                            // quarter 4
                            if (checkLocked($conn, 4, $period_id) || $is_editable == 0 || $user_can_edit === false) { $temp["q4_cost_display"] = "<input class='form-control' id='edit-q4_cost-$invoice_id' aria-labelledby='edit-q4_cost' value='".sprintf("%0.2f", $quarterlyCosts[4])."' type='number' onchange='checkOtherQuarterlyCosts($invoice_id);' disabled>"; }
                            else { $temp["q4_cost_display"] = "<input class='form-control' id='edit-q4_cost-$invoice_id' aria-labelledby='edit-q4_cost' value='".sprintf("%0.2f", $quarterlyCosts[4])."' type='number' onchange='checkOtherQuarterlyCosts($invoice_id);'>"; }

                            // create the cost columns
                            $quarterlyCostsSum = $quarterlyCosts[1] + $quarterlyCosts[2] + $quarterlyCosts[3] + $quarterlyCosts[4];
                            $temp["quarterly_cost_sum"] = "<input class='form-control' id='edit-quarterly_cost_sum-$invoice_id' aria-labelledby='edit-quarterly_cost_sum' value='".sprintf("%0.2f", $quarterlyCostsSum)."' type='number' disabled readonly>";
                            $temp["total_cost"] = printDollar($total_cost);

                            // create the actions column
                            $actions = "<div class='d-flex justify-content-end'>";
                                $actions .= "<button class='btn btn-success btn-sm mx-1' id='edit-status-$invoice_id'><i class='fa-solid fa-check'></i></button>";
                                if ($user_can_edit === true) 
                                {
                                    $actions .= "<button class='btn btn-secondary btn-sm mx-1' id='edit-update-$invoice_id' aria-labelledby='edit-actions' onclick='updateOtherQuarterlyCosts($invoice_id);' disabled><i class='fa-solid fa-floppy-disk'></i></button>
                                    <button class='btn btn-secondary btn-sm mx-1' id='edit-reset-$invoice_id' aria-labelledby='edit-actions' onclick='resetOtherQuarterlyCosts($invoice_id);'><i class='fa-solid fa-rotate-left'></i></button>
                                    <button class='btn btn-primary btn-sm mx-1' type='button' onclick='getEditOtherInvoiceModal($invoice_id);'><i class='fa-solid fa-pencil'></i></button>
                                    <button class='btn btn-danger btn-sm mx-1' type='button' onclick='getDeleteOtherInvoiceModal($invoice_id);'><i class='fa-solid fa-trash-can'></i></button>";
                                } 
                            $actions .= "</div>";
                            $temp["actions"] = $actions;

                            $invoices[] = $temp;
                        }
                    }
                }      
            }
        }
            
        // send data to be printed
        $fullData = [];
        $fullData["draw"] = 1;
        $fullData["data"] = $invoices;
        echo json_encode($fullData);

        // disconnect from the database
        mysqli_close($conn);
    }
?>