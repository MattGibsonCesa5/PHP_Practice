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
        if (isset($_POST["period1"]) && $_POST["period1"] <> "") { $period1 = $_POST["period1"]; } else { $period1 = null; }
        if (isset($_POST["period2"]) && $_POST["period2"] <> "") { $period2 = $_POST["period2"]; } else { $period2 = null; }

        if (($period1 != null && $base_period_id = getPeriodID($conn, $period1)) && ($period2 != null && $comp_period_id = getPeriodID($conn, $period2))) // verify the period exists; if it exists, store the period ID
        {
            ///////////////////////////////////////////////////////////////////////////////////////////
            //
            //  REGULAR SERVICE INVOICES
            //
            ///////////////////////////////////////////////////////////////////////////////////////////
            if (checkUserPermission($conn, "VIEW_INVOICES_ALL") || checkUserPermission($conn, "VIEW_INVOICES_ASSIGNED"))
            {
                // build and prepare query to get invoices based on the user's role
                if (checkUserPermission($conn, "VIEW_INVOICES_ALL"))
                {
                    $getBaseInvoices = mysqli_prepare($conn, "SELECT sp.*, s.name AS service_name, c.name AS customer_name FROM services_provided sp 
                                                            JOIN services s ON sp.service_id=s.id
                                                            JOIN customers c ON sp.customer_id=c.id
                                                            WHERE sp.period_id=?");
                    mysqli_stmt_bind_param($getBaseInvoices, "i", $base_period_id);
                }
                else if (checkUserPermission($conn, "VIEW_INVOICES_ASSIGNED"))
                {
                    $getBaseInvoices = mysqli_prepare($conn, "SELECT sp.*, s.name AS service_name, c.name AS customer_name FROM services_provided sp 
                                                            JOIN services s ON sp.service_id=s.id 
                                                            JOIN customers c ON sp.customer_id=c.id
                                                            JOIN projects p ON s.project_code=p.code 
                                                            JOIN departments d ON p.department_id=d.id 
                                                            WHERE (d.director_id=? OR d.secondary_director_id=?) AND sp.period_id=?");
                    mysqli_stmt_bind_param($getBaseInvoices, "iii", $_SESSION["id"], $_SESSION["id"], $base_period_id);
                }

                // execute the query to get invoices
                if (mysqli_stmt_execute($getBaseInvoices))
                {
                    $getBaseInvoicesResults = mysqli_stmt_get_result($getBaseInvoices);
                    if (mysqli_num_rows($getBaseInvoicesResults) > 0)
                    {
                        while ($invoice = mysqli_fetch_array($getBaseInvoicesResults)) 
                        {
                            // get invoice details 
                            $invoice_id = $invoice["id"];
                            $period_id = $invoice["period_id"];
                            $service_id = $invoice["service_id"];
                            $service_name = $invoice["service_name"];
                            $invoice_cost = $invoice["total_cost"];
                            $customer_id = $invoice["customer_id"];
                            $customer_name = $invoice["customer_name"];

                            // build the invoice ID column
                            $display_invoice_id = "<button class='btn btn-link w-100 p-1' type='button' onclick='getInvoiceDetailsModal(".$invoice_id.");'>".$invoice_id."</button>";

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

                            // get quarterly cost total (total billed) for the base period
                            $quarterlyCostsSum = $quarterlyCosts["Q1"] + $quarterlyCosts["Q2"] + $quarterlyCosts["Q3"] + $quarterlyCosts["Q4"];

                            // initialize comp period data
                            $comp_qty = $comp_total_cost = 0;
                            $compQuarterlyCosts = [];
                            $compQuarterlyCosts["Q1"] = $compQuarterlyCosts["Q2"] = $compQuarterlyCosts["Q3"] = $compQuarterlyCosts["Q4"] = 0;
                            $comp_invoice_id = "-";

                            // get comparison period invoice data
                            if (checkUserPermission($conn, "VIEW_INVOICES_ALL"))
                            {
                                $getCompInvoices = mysqli_prepare($conn, "SELECT sp.* FROM services_provided sp WHERE sp.period_id=? AND sp.service_id=? AND sp.customer_id=?");
                                mysqli_stmt_bind_param($getCompInvoices, "isi", $comp_period_id, $service_id, $customer_id);
                            }
                            else if (checkUserPermission($conn, "VIEW_INVOICES_ASSIGNED"))
                            {
                                $getCompInvoices = mysqli_prepare($conn, "SELECT sp.* FROM services_provided sp 
                                                                        JOIN services s ON sp.service_id=s.id
                                                                        JOIN projects p ON s.project_code=p.code 
                                                                        JOIN departments d ON p.department_id=d.id 
                                                                        WHERE (d.director_id=? OR d.secondary_director_id=?) AND sp.period_id=? AND sp.service_id=? AND sp.customer_id=?");
                                mysqli_stmt_bind_param($getCompInvoices, "iiisi", $_SESSION["id"], $_SESSION["id"], $comp_period_id, $service_id, $customer_id);
                            }
                            // execute the query to get invoices
                            if (mysqli_stmt_execute($getCompInvoices))
                            {
                                $getCompInvoicesResults = mysqli_stmt_get_result($getCompInvoices);
                                if (mysqli_num_rows($getCompInvoicesResults) > 0)
                                {
                                    while ($compInvoice = mysqli_fetch_array($getCompInvoicesResults)) 
                                    {
                                        // store comp invoice ID
                                        $comp_invoice_id = $compInvoice["id"];
                                        $comp_qty = $compInvoice["quantity"];

                                        // get the quarterly costs for the invoice
                                        $getCompQuarterlyCosts = mysqli_prepare($conn, "SELECT cost, quarter FROM quarterly_costs WHERE invoice_id=?");
                                        mysqli_stmt_bind_param($getCompQuarterlyCosts, "i", $comp_invoice_id);
                                        if (mysqli_stmt_execute($getCompQuarterlyCosts))
                                        {
                                            $getCompQuarterlyCostsResults = mysqli_stmt_get_result($getCompQuarterlyCosts);
                                            if (mysqli_num_rows($getCompQuarterlyCostsResults) > 0) // quarterly costs found
                                            {
                                                // for each quarterly cost found, add to array
                                                while ($quarter = mysqli_fetch_array($getCompQuarterlyCostsResults))
                                                {
                                                    // store the quarter's cost locally
                                                    $q = $quarter["quarter"];
                                                    $cost = $quarter["cost"];

                                                    // add the quarter's cost to the array
                                                    $compQuarterlyCosts["Q$q"] = $cost;
                                                }
                                            }
                                        }
                                    }
                                }
                            }

                            // calculate comp total cost
                            $comp_total_cost = $compQuarterlyCosts["Q1"] + $compQuarterlyCosts["Q2"] + $compQuarterlyCosts["Q3"] + $compQuarterlyCosts["Q4"];

                            // build comp quantity display
                            $qty_display = $invoice["quantity"];
                            if ($comp_qty > $invoice["quantity"]) { $qty_display .= "<i class=\"fa-solid fa-arrow-trend-up font-trendup mx-1\"></i>"; }
                            else if ($comp_qty < $invoice["quantity"]) { $qty_display .= "<i class=\"fa-solid fa-arrow-trend-down font-trenddown mx-1\"></i>"; }
                            else { $qty_display .= "<i class=\"fa-solid fa-minus mx-1\"></i>"; }
                            $qty_display .= $comp_qty;

                            // build comp Q1 display
                            $q1_display = printDollar($quarterlyCosts["Q1"]);
                            if ($compQuarterlyCosts["Q1"] > $quarterlyCosts["Q1"]) { $q1_display .= "<i class=\"fa-solid fa-arrow-trend-up font-trendup mx-1\"></i>"; }
                            else if ($compQuarterlyCosts["Q1"] < $quarterlyCosts["Q1"]) { $q1_display .= "<i class=\"fa-solid fa-arrow-trend-down font-trenddown mx-1\"></i>"; }
                            else { $q1_display .= "<i class=\"fa-solid fa-minus mx-1\"></i>"; }
                            $q1_display .= printDollar($compQuarterlyCosts["Q1"]);

                            // build comp Q2 display
                            $q2_display = printDollar($quarterlyCosts["Q2"]);
                            if ($compQuarterlyCosts["Q2"] > $quarterlyCosts["Q2"]) { $q2_display .= "<i class=\"fa-solid fa-arrow-trend-up font-trendup mx-1\"></i>"; }
                            else if ($compQuarterlyCosts["Q2"] < $quarterlyCosts["Q2"]) { $q2_display .= "<i class=\"fa-solid fa-arrow-trend-down font-trenddown mx-1\"></i>"; }
                            else { $q2_display .= "<i class=\"fa-solid fa-minus mx-1\"></i>"; }
                            $q2_display .= printDollar($compQuarterlyCosts["Q2"]);

                            // build comp Q3 display
                            $q3_display = printDollar($quarterlyCosts["Q3"]);
                            if ($compQuarterlyCosts["Q3"] > $quarterlyCosts["Q3"]) { $q3_display .= "<i class=\"fa-solid fa-arrow-trend-up font-trendup mx-1\"></i>"; }
                            else if ($compQuarterlyCosts["Q3"] < $quarterlyCosts["Q3"]) { $q3_display .= "<i class=\"fa-solid fa-arrow-trend-down font-trenddown mx-1\"></i>"; }
                            else { $q3_display .= "<i class=\"fa-solid fa-minus mx-1\"></i>"; }
                            $q3_display .= printDollar($compQuarterlyCosts["Q3"]);

                            // build comp Q4 display
                            $q4_display = printDollar($quarterlyCosts["Q4"]);
                            if ($compQuarterlyCosts["Q4"] > $quarterlyCosts["Q4"]) { $q4_display .= "<i class=\"fa-solid fa-arrow-trend-up font-trendup mx-1\"></i>"; }
                            else if ($compQuarterlyCosts["Q4"] < $quarterlyCosts["Q4"]) { $q4_display .= "<i class=\"fa-solid fa-arrow-trend-down font-trenddown mx-1\"></i>"; }
                            else { $q4_display .= "<i class=\"fa-solid fa-minus mx-1\"></i>"; }
                            $q4_display .= printDollar($compQuarterlyCosts["Q4"]);

                            // build comp total cost display
                            $total_display = printDollar($quarterlyCostsSum);
                            if ($comp_total_cost > $quarterlyCostsSum) { $total_display .= "<i class=\"fa-solid fa-arrow-trend-up font-trendup mx-1\"></i>"; }
                            else if ($comp_total_cost < $quarterlyCostsSum) { $total_display .= "<i class=\"fa-solid fa-arrow-trend-down font-trenddown mx-1\"></i>"; }
                            else { $total_display .= "<i class=\"fa-solid fa-minus mx-1\"></i>"; }
                            $total_display .= printDollar($comp_total_cost);

                            // build array of data to return
                            $temp = [];
                            $temp["service_id"] = $service_id;
                            $temp["service_name"] = $service_name;
                            $temp["customer_id"] = $customer_id;
                            $temp["customer_name"] = $customer_name;
                            $temp["quantity"] = $qty_display;
                            $temp["q1_cost"] = $q1_display;
                            $temp["q2_cost"] = $q2_display;
                            $temp["q3_cost"] = $q3_display;
                            $temp["q4_cost"] = $q4_display;
                            $temp["total_cost"] = $total_display;

                            // add temporary array to master invoice listing
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