<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // initialize array to store all project revenues
        $revenues = [];

        // get the required files
        include("../../includes/config.php");
        include("../../includes/functions.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "VIEW_PROJECT_BUDGETS_ALL") || checkUserPermission($conn, "VIEW_PROJECT_BUDGETS_ASSIGNED"))
        {
            // store user permissions locally
            $can_user_edit_invoices = checkUserPermission($conn, "EDIT_INVOICES");
            $can_user_delete_invoices = checkUserPermission($conn, "DELETE_INVOICES");
            $can_user_add_revenues = checkUserPermission($conn, "ADD_REVENUES");
            $can_user_edit_revenues = checkUserPermission($conn, "EDIT_REVENUES");
            $can_user_delete_revenues = checkUserPermission($conn, "DELETE_REVENUES");
            $can_user_invoice_other_services = checkUserPermission($conn, "INVOICE_OTHER_SERVICES");

            // get the parameters from POST
            if (isset($_POST["code"]) && $_POST["code"] <> "") { $code = $_POST["code"]; } else { $code = null; }
            if (isset($_POST["period"]) && $_POST["period"] <> "") { $period = $_POST["period"]; } else { $period = null; }

            if ($code != null && $period != null)
            {
                if ($period_id = getPeriodID($conn, $period)) // verify the period exists; if it exists, store the period ID
                {
                    // get the period's details
                    $periodDetails = getPeriodDetails($conn, $period_id);

                    if (verifyProject($conn, $code) && verifyUserCanViewProject($conn, $_SESSION["id"], $code)) // verify the project exists and user is assigned to it
                    {
                        // get a list of services that are assigned to the project
                        $getServices = mysqli_prepare($conn, "SELECT * FROM services WHERE project_code=?");
                        mysqli_stmt_bind_param($getServices, "s", $code);
                        if (mysqli_stmt_execute($getServices))
                        {
                            $getServicesResult = mysqli_stmt_get_result($getServices);
                            if (mysqli_num_rows($getServicesResult) > 0) // service(s) are assigend to this project
                            {
                                while ($service = mysqli_fetch_array($getServicesResult))
                                {
                                    // store service details locally
                                    $service_id = $service["id"];
                                    $service_name = $service["name"];
                                    $fund = $service["fund_code"];
                                    $obj = $service["object_code"];
                                    $func = $service["function_code"];

                                    // get a list of the times we provided this service this period
                                    $getProvided = mysqli_prepare($conn, "SELECT * FROM services_provided WHERE service_id=? AND period_id=?");
                                    mysqli_stmt_bind_param($getProvided, "si", $service_id, $period_id);
                                    if (mysqli_stmt_execute($getProvided))
                                    {
                                        $getProvidedResults = mysqli_stmt_get_result($getProvided);
                                        if (mysqli_num_rows($getProvidedResults) > 0) // we have provided this service this period
                                        {
                                            while ($provided = mysqli_fetch_array($getProvidedResults))
                                            {
                                                // store invoice details locally
                                                $invoice_id = $provided["id"];
                                                $customer_id = $provided["customer_id"];
                                                $quantity = $provided["quantity"];
                                                $date = date("m/d/Y", strtotime($provided["date_provided"]));
                                                $cost = $provided["total_cost"];

                                                // get customer name and location code based on customer ID
                                                $getCustomerName = mysqli_prepare($conn, "SELECT name, location_code FROM customers WHERE id=?");
                                                mysqli_stmt_bind_param($getCustomerName, "i", $customer_id);
                                                if (mysqli_stmt_execute($getCustomerName))
                                                {
                                                    $getCustomerNameResult = mysqli_stmt_get_result($getCustomerName);
                                                    if (mysqli_num_rows($getCustomerNameResult) > 0) // customer exists
                                                    {
                                                        $customer = mysqli_fetch_array($getCustomerNameResult);
                                                        $customer_name = $customer["name"];
                                                        $loc = $customer["location_code"];

                                                        $temp = [];
                                                        $temp["customer_id"] = $customer_id;
                                                        $temp["customer_name"] = $customer_name;
                                                        $temp["service_id"] = $service_id;
                                                        $temp["service_name"] = $service_name;
                                                        $temp["fund"] = $fund." R";
                                                        $temp["obj"] = $obj;
                                                        $temp["func"] = $func;
                                                        $temp["proj"] = $code;
                                                        $temp["loc"] = $loc;
                                                        $temp["invoice_id"] = $invoice_id;
                                                        $temp["date"] = $date;
                                                        $temp["qty"] = $quantity;
                                                        $temp["cost"] = printDollar($cost);

                                                        // build the actions column
                                                        $actions = "<div class='d-flex justify-content-end'>";
                                                        if ($periodDetails["editable"] == 1) 
                                                        { 
                                                            if ($can_user_edit_invoices === true) { $actions .= "<button class='btn btn-primary btn-sm mx-1' type='button' onclick='getEditInvoiceModal(".$invoice_id.");'><i class='fa-solid fa-pencil'></i></button>"; }
                                                            if ($can_user_delete_invoices === true) { $actions .= "<button class='btn btn-danger btn-sm mx-1' type='button' onclick='getRemoveInvoiceFromProjectModal(".$invoice_id.");'><i class='fa-solid fa-trash-can'></i></button>"; }
                                                        }
                                                        $actions .= "</div>";
                                                        $temp["actions"] = $actions;

                                                        $revenues[] = $temp;
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }

                        // get a list of additional revenues that are associated with this project
                        $getOtherRevenues = mysqli_prepare($conn, "SELECT * FROM revenues WHERE project_code=? AND period_id=?");
                        mysqli_stmt_bind_param($getOtherRevenues, "si", $code, $period_id);
                        if (mysqli_stmt_execute($getOtherRevenues))
                        {
                            $getOtherRevenuesResults = mysqli_stmt_get_result($getOtherRevenues);
                            if (mysqli_num_rows($getOtherRevenuesResults) > 0) // other revenues found; continue
                            {
                                while ($revenue = mysqli_fetch_array($getOtherRevenuesResults))
                                {
                                    // store data locally
                                    $name = $revenue["name"];
                                    $fund = $revenue["fund_code"];
                                    $loc = $revenue["location_code"];
                                    $src = $revenue["source_code"];
                                    $func = $revenue["function_code"];
                                    $proj = $revenue["project_code"];
                                    $qty = $revenue["quantity"];
                                    $revenue_id = $revenue["id"];
                                    $date = date("m/d/Y", strtotime($revenue["date"]));
                                    $cost = $revenue["total_cost"];

                                    $temp = [];
                                    $temp["customer_id"] = "-";
                                    $temp["customer_name"] = "-";
                                    $temp["service_id"] = "-";
                                    $temp["service_name"] = $name;
                                    $temp["fund"] = $fund." R";
                                    $temp["obj"] = $src;
                                    $temp["func"] = $func;
                                    $temp["proj"] = $code;
                                    $temp["loc"] = $loc;
                                    $temp["invoice_id"] = $revenue_id;
                                    $temp["date"] = $date;
                                    $temp["qty"] = $qty;
                                    $temp["cost"] = printDollar($cost);
                                    
                                    // build the actions column
                                    $actions = "<div class='d-flex justify-content-end'>";
                                    if ($periodDetails["editable"] == 1) 
                                    { 
                                        if ($can_user_edit_revenues === true) { $actions .= "<button class='btn btn-primary btn-sm mx-1' type='button' onclick='getEditRevenueModal(".$revenue_id.");'><i class='fa-solid fa-pencil'></i></button>"; }
                                        if ($can_user_add_revenues === true) { $actions .= "<button class='btn btn-primary btn-sm mx-1' type='button' onclick='getCloneRevenueModal(".$revenue_id.");'><i class='fa-solid fa-clone'></i></button>"; }
                                        if ($can_user_delete_revenues === true) { $actions .= "<button class='btn btn-danger btn-sm mx-1' type='button' onclick='getRemoveRevenueFromProjectModal(".$revenue_id.");'><i class='fa-solid fa-trash-can'></i></button>"; }
                                    }
                                    $actions .= "</div>";
                                    $temp["actions"] = $actions;

                                    $revenues[] = $temp;
                                }
                            }
                        }

                        // get a list of additional revenues that are associated with this project
                        $getOtherServices = mysqli_prepare($conn, "SELECT sop.id AS invoice_id, sop.service_id, sop.customer_id, sop.total_cost, sop.quantity, sop.description, sop.date_provided, so.name, so.fund_code, so.source_code, so.function_code FROM services_other_provided sop
                                                                    JOIN services_other so ON sop.service_id=so.id
                                                                    WHERE project_code=? AND period_id=?");
                        mysqli_stmt_bind_param($getOtherServices, "si", $code, $period_id);
                        if (mysqli_stmt_execute($getOtherServices))
                        {
                            $getOtherServicesResuls = mysqli_stmt_get_result($getOtherServices);
                            if (mysqli_num_rows($getOtherServicesResuls) > 0) // other revenues found; continue
                            {
                                while ($revenue = mysqli_fetch_array($getOtherServicesResuls))
                                {
                                    // store data locally
                                    $service_id = $revenue["service_id"];
                                    $service_name = $revenue["name"];
                                    $customer_id = $revenue["customer_id"];
                                    $fund = $revenue["fund_code"];
                                    $src = $revenue["source_code"];
                                    $func = $revenue["function_code"];
                                    $invoice_id = $revenue["invoice_id"];
                                    $quantity = $revenue["quantity"];
                                    $date = date("m/d/Y", strtotime($revenue["date_provided"]));
                                    $cost = $revenue["total_cost"];

                                    // get customer name and location code based on customer ID
                                    $getCustomerName = mysqli_prepare($conn, "SELECT name, location_code FROM customers WHERE id=?");
                                    mysqli_stmt_bind_param($getCustomerName, "i", $customer_id);
                                    if (mysqli_stmt_execute($getCustomerName))
                                    {
                                        $getCustomerNameResult = mysqli_stmt_get_result($getCustomerName);
                                        if (mysqli_num_rows($getCustomerNameResult) > 0) // customer exists
                                        {
                                            $customer = mysqli_fetch_array($getCustomerNameResult);
                                            $customer_name = $customer["name"];
                                            $loc = $customer["location_code"];

                                            $temp = [];
                                            $temp["customer_id"] = $customer_id;
                                            $temp["customer_name"] = $customer_name;
                                            $temp["service_id"] = $service_id;
                                            $temp["service_name"] = $service_name;
                                            $temp["fund"] = $fund." R";
                                            $temp["obj"] = $src;
                                            $temp["func"] = $func;
                                            $temp["proj"] = $code;
                                            $temp["loc"] = $loc;
                                            $temp["invoice_id"] = $invoice_id;
                                            $temp["date"] = $date;
                                            $temp["qty"] = $quantity;
                                            $temp["cost"] = printDollar($cost);

                                            // build the actions columns
                                            $actions = "<div class='d-flex justify-content-end'>";
                                            if ($periodDetails["editable"] == 1 && $can_user_invoice_other_services) 
                                            { 
                                                $actions .= "<button class='btn btn-primary btn-sm mx-1' type='button' onclick='getEditOtherInvoiceModal(".$invoice_id.");'><i class='fa-solid fa-pencil'></i></button>";
                                                $actions .= "<button class='btn btn-danger btn-sm mx-1' type='button' onclick='getRemoveOtherInvoiceFromProjectModal(".$invoice_id.");'><i class='fa-solid fa-trash-can'></i></button>";
                                            }
                                            $actions .= "</div>";
                                            $temp["actions"] = $actions;

                                            $revenues[] = $temp;
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
        $fullData["data"] = $revenues;
        echo json_encode($fullData);
        
        // disconnect from the database
        mysqli_close($conn);
    }
?>