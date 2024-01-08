<?php
    session_start();

    ///////////////////////////////////////////////////////////////////////////////////////////////
    //
    //  Initialize and require necessary files
    //
    ///////////////////////////////////////////////////////////////////////////////////////////////

    // include the autoloader
    require_once("vendor/autoload.php");

    // get PDF creator
    use mikehaertl\wkhtmlto\Pdf;

    // include header and additional settings
    include("header.php");
    include("getSettings.php");

    // store local variables
    $CONTRACT_STYLESHEET_PATH = CONTRACT_STYLESHEET_PATH;
    $CESA5_LOGO_PATH = CESA5_LOGO_PATH;

    // initialize counter variables for successes and errors
    $total_successes = $errors = 0;
    $contract_id = 1; // TODO - handle custom contracts

    // DEV VARS
    $show_revisions = 1; // TODO - pull from "contracts" table

    // verify login status
    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1) 
    { 
        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        // verify the user has permission to create contracts
        if (checkUserPermission($conn, "CREATE_SERVICE_CONTRACTS"))
        {
            // get POST parameters
            if (isset($_POST["name"])) { $name = $_POST["name"]; } else { $name = null; }
            if (isset($_POST["type"])) { $contract_type_id = $_POST["type"]; } else { $contract_type_id = null; }
            if (isset($_POST["contract_period"])) { $contract_period = $_POST["contract_period"]; } else { $contract_period = null; }
            if (isset($_POST["qty_period"])) { $qty_period = $_POST["qty_period"]; } else { $qty_period = null; }
            if (isset($_POST["customers"])) { $customers = $_POST["customers"]; } else { $customers = null; }

            // if all required fields were filled out, continue
            if ($contract_type_id != null && $name != null && $contract_period != null && $qty_period != null && $customers != null)
            {
                // verify the contract period exists
                if ($contract_period != null && verifyPeriod($conn, $contract_period))
                {
                    // get the period name
                    $period_name = getPeriodName($conn, $contract_period);

                    // verify the quantity period exists
                    if (($contract_type_id == 1 && $qty_period != null && verifyPeriod($conn, $qty_period)) // service contracts - require qty period
                        || $contract_type_id != 1 // not a service contract, skip qty period
                    ) {
                        // display process
                        echo "<div class='container'>";
                        echo "Building ".count($customers)." contracts for ".$period_name."<br>";

                        // for all customers we are creating a contract for, attempt to get their name and Google Drive folder ID (GID)
                        for ($customer_num = 0; $customer_num < count($customers); $customer_num++)
                        {
                            // store the customer ID locally
                            $customer_id = $customers[$customer_num];

                            // get customer name
                            $customer_name = getCustomerDetails($conn, $customer_id)["name"];

                            // initialize new PDF
                            $pdf = new Pdf();
                            $pdf->setOptions(array(
                                'ignoreWarnings' => true,
                                'commandOptions' => array(
                                    'useExec' => true,      // Can help on Windows systems
                                    'procEnv' => array(
                                        // Check the output of 'locale -a' on your system to find supported languages
                                        'LANG' => 'en_US.utf-8',
                                    ),
                                ),
                                'enable-local-file-access'
                            ));

                            $header_logo = __DIR__."/img/CESA 5.png";
                            $getPages = mysqli_prepare($conn, "SELECT * FROM contract_pages WHERE contract_id=? ORDER BY page_order ASC");
                            mysqli_stmt_bind_param($getPages, "i", $contract_id);
                            if (mysqli_stmt_execute($getPages))
                            {
                                $getPagesResults = mysqli_stmt_get_result($getPages);
                                if (($page_count = mysqli_num_rows($getPagesResults)) > 0)
                                {
                                    $page_num = 1;
                                    $grand_total = 0;
                                    $all_revisions_list = "";
                                    while ($page = mysqli_fetch_assoc($getPagesResults))
                                    {
                                        // store page details locally
                                        $page_id = $page["id"];
                                        $show_header_logo = $page["show_header_logo"];
                                        $page_header = $page["page_header"];
                                        $show_page_header = $page["show_page_header"];
                                        $page_desc = $page["page_description"];
                                        $show_page_desc = $page["show_page_description"];
                                        $sections_header = $page["sections_header"];
                                        $show_sections_header = $page["show_sections_header"];
                                        $key = $page["legend"];
                                        $show_key = $page["show_legend"];
                                        $footer_desc = $page["footer_description"];
                                        $show_footer_desc = $page["show_footer_description"];
                                        $page_notes = $page["notes"];
                                        $show_notes = $page["show_notes"];
                                        $show_year = $page["show_year_above_columns"];
                                        $show_page_subtotal = $page["show_page_subtotal"];
                                        $show_combined_total = $page["show_combined_total"];
                                        $show_revisions_on_page = $page["show_revisions"];

                                        // initialize page total
                                        $page_total = 0;

                                        // create the filename for the customer's service contract
                                        $filename = str_replace("{PERIOD}", $period_name, str_replace("{CUSTOMER}", $customer_name, $name));

                                        // build header
                                        if ($show_page_header != 1) { $page_header = ""; } else { $page_header = str_replace("{{PERIOD}}", $period_name, str_replace("{{CUSTOMER}}", $customer_name, $page_header)); }
                                        if ($show_page_desc != 1) { $page_desc = ""; }

                                        // get columns
                                        $columns = [];
                                        $getColumns = mysqli_prepare($conn, "SELECT * FROM contract_columns WHERE contract_id=? ORDER BY column_order ASC");
                                        mysqli_stmt_bind_param($getColumns, "i", $contract_id);
                                        if (mysqli_stmt_execute($getColumns))
                                        {
                                            $getColumnsResults = mysqli_stmt_get_result($getColumns);
                                            if (mysqli_num_rows($getColumnsResults) > 0)
                                            {
                                                while ($columns[] = mysqli_fetch_assoc($getColumnsResults)) { }
                                            }
                                        }

                                        // initialize footer section
                                        $footer_body = "<tfoot>";

                                        // initialize revisions list for the page
                                        $page_revisions_list = "";

                                        // build sections
                                        $sections_body = "<table class='contract border-0 border-none border-white w-100'>
                                            <thead>";
                                                // build year row
                                                if ($show_year == 1)
                                                {
                                                    // initialize variables
                                                    $yearCols = 0;
                                                    $yearStart = 0;
                                                    $showNameStart = null;
                                                    $showNoNames = 1;
                                                    $sectionsHeaderPrinted = 0;

                                                    // build row
                                                    $sections_body .= "<tr>";
                                                        // get data to build row
                                                        for ($c = 0; $c < count($columns) - 1; $c++)
                                                        {
                                                            if ($columns[$c]["show_year_above"] == 1) 
                                                            { 
                                                                if ($yearCols == 0) { $yearStart = $c; }
                                                                $yearCols++; 
                                                            }

                                                            if ($columns[$c]["show_name"] == 1 && $showNameStart == null)
                                                            {
                                                                $showNameStart = $c;
                                                                $showNoNames++;
                                                            }
                                                        }

                                                        // build row
                                                        for ($c = 0; $c < count($columns) - 1; $c++)
                                                        {
                                                            if ($columns[$c]["show_year_above"] == 1 && $c == $yearStart) 
                                                            { 
                                                                $sections_body .= "<th style='width: ".$columns[$c]["width"]."% !important;' class='border border-dark' colspan='".$yearCols."'>$period_name</th>";
                                                            } else if ($c < $yearStart) {
                                                                $sections_body .= "<th style='width: ".$columns[$c]["width"]."% !important;' class='border-0 border-none border-white'></th>";
                                                            }
                                                        }
                                                    $sections_body .= "</tr>";
                                                }
                                            
                                                $sections_body .= "<tr>";
                                                    // build header row
                                                    for ($c = 0; $c < count($columns) - 1; $c++)
                                                    {
                                                        if ($columns[$c]["show_name"] == 1) { 
                                                            $sections_body .= "<th style='width: ".$columns[$c]["width"]."% !important;' class='border border-dark'>".$columns[$c]["name"]."</th>";
                                                        } else {
                                                            if ($columns[$c]["show_year_above"] == 1) {
                                                                $sections_body .= "<th style='width: ".$columns[$c]["width"]."% !important; background-color: #bcbcbc !important;' class='border border-dark'></th>";
                                                            } else {
                                                                if ($sectionsHeaderPrinted == 0 && $show_sections_header == 1)
                                                                {
                                                                    $sections_body .= "<th style='width: ".$columns[$c]["width"]."% !important; text-align: left !important; font-size: 18px !important;' class='border border-dark' colspan='".$showNoNames."'>".$sections_header."</th>";
                                                                    $sectionsHeaderPrinted = 1;
                                                                } else if ($sectionsHeaderPrinted == 1 && $show_sections_header == 1) {
                                                                    // do nothing
                                                                } else {
                                                                    $sections_body .= "<th style='width: ".$columns[$c]["width"]."% !important;' class='border-0'></th>";
                                                                }
                                                            }
                                                        }
                                                    }
                                                $sections_body .= "</tr>
                                            </thead>
                                        ";
                                        $getSections = mysqli_prepare($conn, "SELECT * FROM contract_sections WHERE page_id=? AND contract_id=?");
                                        mysqli_stmt_bind_param($getSections, "ii", $page_id, $contract_id);
                                        if (mysqli_stmt_execute($getSections))
                                        {
                                            $getSectionsResults = mysqli_stmt_get_result($getSections);
                                            if (mysqli_num_rows($getSectionsResults) > 0)
                                            {
                                                while ($section = mysqli_fetch_assoc($getSectionsResults))
                                                {
                                                    // store section details locally
                                                    $section_id = $section["id"];
                                                    $section_name = $section["name"];
                                                    $section_color = $section["color"];
                                                    $show_section_total = $section["show_section_total"];
                                                    $show_if_zero = $section["show_if_zero"];
                                                    $other_services = $section["other_services"];

                                                    // iniitalize section body and total
                                                    $section_body = "";
                                                    $section_total = 0;

                                                    // build section header row
                                                    if ($show_revisions == 1) {
                                                        $section_body .= "<tr>
                                                            <th class='border border-dark p-0' style='background-color: ".$section_color." !important; text-align: left !important; font-size: 16px !important;' colspan=".(count($columns) - 2)."><h6 class='fst-italic fw-bold mb-0'>".$section_name."</h6></th>
                                                        </tr>";
                                                    } else {
                                                        $section_body .= "<tr>
                                                            <th class='border border-dark p-0' style='background-color: ".$section_color." !important; text-align: left !important; font-size: 16px !important;' colspan=".(count($columns) - 1)."><h6 class='fst-italic fw-bold mb-0'>".$section_name."</h6></th>
                                                        </tr>";
                                                    }

                                                    // add section to display
                                                    if ($contract_type_id == 1)
                                                    {
                                                        if ($other_services == 0)
                                                        {
                                                            $getSectionServices = mysqli_prepare($conn, "SELECT * FROM contract_services WHERE contract_id=? AND contract_section_id=?");
                                                            mysqli_stmt_bind_param($getSectionServices, "ii", $contract_id, $section_id);
                                                            if (mysqli_stmt_execute($getSectionServices))
                                                            {
                                                                $getSectionServicesResults = mysqli_stmt_get_result($getSectionServices);
                                                                if (mysqli_num_rows($getSectionServicesResults) > 0)
                                                                {
                                                                    while ($service = mysqli_fetch_assoc($getSectionServicesResults))
                                                                    {
                                                                        // store service details locally$
                                                                        $service_id = $service["service_id"];
                                                                        $display_id = $service["display_id"];
                                                                        $service_desc = $service["description"];
                                                                        $unit_label = $service["unit_label"];
                                                                        $show_qty = $service["show_qty"];
                                                                        $show_unit = $service["show_unit"];
                                                                        $show_if_zero = $service["show_if_zero"];

                                                                        // get the quantity based on quantity period
                                                                        $qty = getQuantity($conn, $service_id, $customer_id, $qty_period);

                                                                        // get projected cost
                                                                        $projected_cost = getProjectedCost($conn, $service_id, $customer_id, $contract_period, $qty_period, $qty);

                                                                        // add projected cost to totals
                                                                        $grand_total += $projected_cost;
                                                                        $page_total += $projected_cost;
                                                                        $section_total += $projected_cost;

                                                                        // build row based on columns
                                                                        $section_body .= "<tr>";
                                                                        for ($c = 0; $c < count($columns) - 1; $c++)
                                                                        {
                                                                            if ($columns[$c]["data_id"] == 1)
                                                                            {
                                                                                $section_body .= "<td class='border border-dark'>";
                                                                                if (isset($display_id) && trim($display_id) <> "") { $section_body .= $display_id; }
                                                                                else { $section_body .= $service_id; }
                                                                            }

                                                                            else if ($columns[$c]["data_id"] == 2)
                                                                            {
                                                                                $section_body .= "<td class='border border-dark'>".$service_desc; 
                                                                            } 

                                                                            else if ($columns[$c]["data_id"] == 3)
                                                                            {
                                                                                if ($show_unit == 1) { $section_body .= "<td class='text-center border border-dark'>".$unit_label; } else { $section_body .= "<td class='border border-dark' style='background-color: #bcbcbc !important;'>"; }
                                                                            } 

                                                                            else if ($columns[$c]["data_id"] == 4)
                                                                            {
                                                                                if ($show_qty == 1) { $section_body .= "<td class='text-center border border-dark'>".$qty; } else { $section_body .= "<td class='border border-dark' style='background-color: #bcbcbc !important;'>"; }
                                                                            } 

                                                                            else if ($columns[$c]["data_id"] == 5)
                                                                            {
                                                                                $section_body .= "<td class='text-end border border-dark border-end'>$".number_format($projected_cost, 2); 
                                                                            } 

                                                                            else if ($columns[$c]["data_id"] == 11)
                                                                            {
                                                                                if ($updated = checkIfUpdated($conn, $service_id, $customer_id, $contract_period, 1)) {
                                                                                    $section_body .= "<td class='text-center border border-none border-0 border-white text-danger'>*";
                                                                                    if ($page_revisions_list <> "") {
                                                                                        $page_revisions_list .= "; ".$display_id." revised on ".date("n/j/Y", $updated);
                                                                                    } else {
                                                                                        $page_revisions_list .= $display_id." revised on ".date("n/j/Y", $updated);
                                                                                    }
                                                                                } else { 
                                                                                    $section_body .= "<td class='text-center border border-none border-0 border-white text-danger fw-bold'>";
                                                                                }
                                                                            }

                                                                            // close cell
                                                                            $section_body .= "</td>";
                                                                        }
                                                                        $section_body .= "</tr>";
                                                                    }
                                                                }
                                                            }
                                                        }
                                                        else if ($other_services == 1)
                                                        {
                                                            // get a list of other services
                                                            $otherServicesArray = [];
                                                            $otherServicesCount = 0;
                                                            $getOtherServices = mysqli_query($conn, "SELECT id FROM services_other ORDER BY id ASC");
                                                            if (($otherServicesCount = mysqli_num_rows($getOtherServices)) > 0)
                                                            {
                                                                while ($other_service = mysqli_fetch_assoc($getOtherServices))
                                                                {
                                                                    // store other service ID
                                                                    $other_id = $other_service["id"];

                                                                    // get invoice details for the other service if applicable
                                                                    $qty = getOtherQuantity($conn, $other_id, $customer_id, $qty_period);
                                                                    $projected_cost = getProjectedOtherCost($conn, $other_id, $customer_id, $qty_period);
                                                                    $desc = getOtherServiceDescription($conn, $other_id, $customer_id, $qty_period);
                                                                    $unit_label = getOtherServiceUnit($conn, $other_id, $customer_id, $qty_period);

                                                                    // add projected cost to totals
                                                                    $grand_total += $projected_cost;
                                                                    $page_total += $projected_cost;
                                                                    $section_total += $projected_cost;

                                                                    // check for revisions
                                                                    $hasRevision = false; // TODO - handle revisions for other services
                                                                    
                                                                    if ($projected_cost > 0)
                                                                    {
                                                                        $temp = [];
                                                                        $temp["id"] = $other_id;
                                                                        $temp["desc"] = $desc;
                                                                        $temp["label"] = $unit_label;
                                                                        $temp["qty"] = $qty;
                                                                        $temp["cost"] = $projected_cost;
                                                                        $temp["revision"] = $hasRevision;
                                                                        $otherServicesArray[] = $temp;
                                                                    }
                                                                }
                                                            }

                                                            // build other services rows
                                                            for ($x = 0; $x < $otherServicesCount; $x++)
                                                            {
                                                                if (isset($otherServicesArray[$x]))
                                                                {
                                                                    // build row based on columns
                                                                    $section_body .= "<tr>";
                                                                    for ($c = 0; $c < count($columns) - 1; $c++)
                                                                    {
                                                                        if ($columns[$c]["data_id"] == 1 && $projected_cost > 0)
                                                                        {
                                                                            $section_body .= "<td class='border border-dark'>".$otherServicesArray[$x]["id"];
                                                                        } 

                                                                        else if ($columns[$c]["data_id"] == 2)
                                                                        {
                                                                            $section_body .= "<td class='border border-dark'>".$otherServicesArray[$x]["desc"]; 
                                                                        } 

                                                                        else if ($columns[$c]["data_id"] == 3)
                                                                        {
                                                                            if ($show_unit == 1) { $section_body .= "<td class='text-center border border-dark'>".$otherServicesArray[$x]["label"]; } else { $section_body .= "<td class='border border-dark' style='background-color: #bcbcbc !important;'>"; }
                                                                        } 

                                                                        else if ($columns[$c]["data_id"] == 4)
                                                                        {
                                                                            if ($show_qty == 1) { $section_body .= "<td class='text-center border border-dark'>".$otherServicesArray[$x]["qty"]; } else { $section_body .= "<td class='border border-dark' style='background-color: #bcbcbc !important;'>"; }
                                                                        } 

                                                                        else if ($columns[$c]["data_id"] == 5)
                                                                        {
                                                                            $section_body .= "<td class='text-end border border-dark border-end'>$".number_format($otherServicesArray[$x]["cost"], 2); 
                                                                        }
                                                                        
                                                                        else if ($columns[$c]["data_id"] == 11)
                                                                        {
                                                                            if ($hasRevision === true) {
                                                                                $section_body .= "<td class='text-center border border-none border-0 border-white text-danger'>*";
                                                                            } else { 
                                                                                $section_body .= "<td class='text-center border border-none border-0 border-white text-danger'>";
                                                                            }
                                                                        }

                                                                        else
                                                                        {
                                                                            $section_body .= "<td class='border border-dark'>";
                                                                        }

                                                                        // close cell
                                                                        $section_body .= "</td>";
                                                                    }
                                                                    $section_body .= "</tr>";
                                                                } else {
                                                                    // build row based on columns
                                                                    $section_body .= "<tr>";
                                                                    for ($c = 0; $c < count($columns) - 1; $c++)
                                                                    {
                                                                        if ($columns[$c]["data_id"] == 1 && $projected_cost > 0)
                                                                        {
                                                                            $section_body .= "<td class='border border-dark'>";
                                                                        } 

                                                                        else if ($columns[$c]["data_id"] == 2)
                                                                        {
                                                                            $section_body .= "<td class='border border-dark'>"; 
                                                                        } 

                                                                        else if ($columns[$c]["data_id"] == 3)
                                                                        {
                                                                            if ($show_unit == 1) { $section_body .= "<td class='text-center border border-dark'>"; } else { $section_body .= "<td class='border border-dark' style='background-color: #bcbcbc !important;'>"; }
                                                                        } 

                                                                        else if ($columns[$c]["data_id"] == 4)
                                                                        {
                                                                            if ($show_qty == 1) { $section_body .= "<td class='text-center border border-dark'>"; } else { $section_body .= "<td class='border border-dark' style='background-color: #bcbcbc !important;'>"; }
                                                                        } 

                                                                        else if ($columns[$c]["data_id"] == 5)
                                                                        {
                                                                            $section_body .= "<td class='text-end border border-dark border-end'>$0.00"; 
                                                                        }
                                                                        
                                                                        else if ($columns[$c]["data_id"] == 11)
                                                                        {
                                                                            if ($hasRevision === true) {
                                                                                $section_body .= "<td class='text-center border border-none border-0 border-white text-danger'>*";
                                                                            } else { 
                                                                                $section_body .= "<td class='text-center border border-none border-0 border-white text-danger'>";
                                                                            }
                                                                        }

                                                                        else
                                                                        {
                                                                            $section_body .= "<td class='border border-dark'>";
                                                                        }

                                                                        // close cell
                                                                        $section_body .= "</td>";
                                                                    }
                                                                    $section_body .= "</tr>";
                                                                }
                                                            }
                                                        }
                                                    }

                                                    // build divider row
                                                    if ($show_revisions == 1) {
                                                        $section_body .= "<tr>
                                                            <th class='border-0' style='background-color: #ffffff !important; text-align: left !important; height: 16px !important;' colspan=".(count($columns) - 2)."></th>
                                                        </tr>";
                                                    } else { 
                                                        $section_body .= "<tr>
                                                            <th class='border-0' style='background-color: #ffffff !important; text-align: left !important; height: 16px !important;' colspan=".(count($columns) - 1)."></th>
                                                        </tr>";
                                                    }

                                                    // add section if sum is greater than 0 or show if zero is enabled
                                                    if ($show_if_zero == 1 || ($show_if_zero == 0 && $section_total > 0))
                                                    {
                                                        $sections_body .= $section_body;
                                                    }
                                                }

                                                
                                                // add totals if enabled
                                                if ($show_page_subtotal == 1)
                                                {
                                                    $footer_body .= "<tr>";
                                                    for ($c = 0; $c < count($columns) - 1; $c++)
                                                    {
                                                        // build cell depending on column data
                                                        if ($columns[$c]["data_id"] == 1)
                                                        {
                                                            $footer_body .= "<td class='border border-dark' style='background-color: #bcbcbc !important;'>";
                                                        }
                                                        if ($columns[$c]["data_id"] == 2)
                                                        {
                                                            $footer_body .= "<td class='text-center border border-dark'><i>PAGE ".$page_num." SUBTOTAL</i>"; 
                                                        } 
                                                        else if ($columns[$c]["data_id"] == 3)
                                                        {
                                                            $footer_body .= "<td class='border border-dark' style='background-color: #bcbcbc !important;'>";
                                                        } 
                                                        else if ($columns[$c]["data_id"] == 4)
                                                        {
                                                            $footer_body .= "<td class='border border-dark' style='background-color: #bcbcbc !important;'>";
                                                        } 
                                                        else if ($columns[$c]["data_id"] == 5)
                                                        {
                                                            $footer_body .= "<td class='text-end border border-dark border-end'>$".number_format($page_total, 2); 
                                                        } 

                                                        // close cell
                                                        $footer_body .= "</td>";
                                                    }
                                                    $footer_body .= "</tr>";
                                                }
                                                if ($page_num == $page_count && $show_combined_total == 1) // grand total only on final page
                                                {
                                                    $footer_body .= "<tr>";
                                                    for ($c = 0; $c < count($columns) - 1; $c++)
                                                    {
                                                        // build cell depending on column data
                                                        if ($columns[$c]["data_id"] == 1)
                                                        {
                                                            $footer_body .= "<td class='border border-dark' style='background-color: #bcbcbc !important;'>";
                                                        }
                                                        if ($columns[$c]["data_id"] == 2)
                                                        {
                                                            $footer_body .= "<td class='text-center border border-dark fw-bold'>TOTAL PROJECTED COST"; 
                                                        } 
                                                        else if ($columns[$c]["data_id"] == 3)
                                                        {
                                                            $footer_body .= "<td class='border border-dark' style='background-color: #bcbcbc !important;'>";
                                                        } 
                                                        else if ($columns[$c]["data_id"] == 4)
                                                        {
                                                            $footer_body .= "<td class='border border-dark' style='background-color: #bcbcbc !important;'>";
                                                        }
                                                        else if ($columns[$c]["data_id"] == 5)
                                                        {
                                                            $footer_body .= "<td class='text-end border border-dark border-end fw-bold'>$".number_format($grand_total, 2); 
                                                        } 

                                                        // close cell
                                                        $footer_body .= "</td>";
                                                    }
                                                    $footer_body .= "</tr>";
                                                }
                                            }
                                        }
                                        // build key section
                                        if ($show_key == 1 && (isset($key) && trim($key) <> ""))
                                        {
                                            // build divider row
                                            if ($show_revisions == 1) {
                                                $footer_body .= "<tr>
                                                    <td class='border-0' style='background-color: #ffffff !important; text-align: left !important; height: 12px !important;' colspan=".(count($columns) - 2)."></td>
                                                </tr>";
                                            } else {
                                                $footer_body .= "<tr>
                                                    <td class='border-0' style='background-color: #ffffff !important; text-align: left !important; height: 12px !important;' colspan=".(count($columns) - 1)."></td>
                                                </tr>";
                                            }

                                            if ($show_revisions == 1) {
                                                $footer_body .= "<tr>
                                                    <td style=\"vertical-align: top !important; text-align: center !important;\"><b>Key:</b></td>
                                                    <td colspan=\"".(count($columns) - 3)."\">".$key."</td>
                                                </tr>";
                                            } else {
                                                $footer_body .= "<tr>
                                                    <td style=\"vertical-align: top !important; text-align: center !important;\"><b>Key:</b></td>
                                                    <td colspan=\"".(count($columns) - 2)."\">".$key."</td>
                                                </tr>";
                                            }
                                        }
                                        $footer_body .= "</tfoot>";

                                        // add footer to sections body and close the table
                                        $sections_body .= $footer_body."</table>";

                                        // build note section 
                                        $notes_body = "";
                                        if ($show_notes == 1)
                                        {
                                            $notes_body .= "<!-- Notes -->
                                            <div>
                                                <h6 class='fst-italic fw-bold mb-0'>Notes:</h6>
                                                <p class='mb-0'>$page_notes</p>
                                            </div>";
                                        }

                                        // build revisions section
                                        $revisions_body = "";
                                        if ($all_revisions_list <> "") { $all_revisions_list .= "; ".$page_revisions_list; } else { $all_revisions_list .= $page_revisions_list; }
                                        if ($show_revisions_on_page == 1 && $page_revisions_list <> "")
                                        {
                                            $revisions_body .= "<div>
                                                <h6 class='fst-italic fw-bold mb-0'>Revisions (Page $page_num):</h6>
                                                <p class='mb-0'>$page_revisions_list</p>
                                            </div>";
                                        } else if ($show_revisions_on_page == 2 && $all_revisions_list <> "") {
                                            $revisions_body .= "<div>
                                                <h6 class='fst-italic fw-bold mb-0'>Revisions:</h6>
                                                <p class='mb-0'>$all_revisions_list</p>
                                            </div>";
                                        }

                                        // builder header logo 
                                        $header_img = "";
                                        if ($show_header_logo == 1) { $header_img = "<img src='".$header_logo."' style='width: 25% !important; margin-bottom: 8px !important;' alt='Logo'>"; }

                                        // build footer description
                                        $footer_section = "";
                                        if ($show_footer_desc == 1) {
                                            $footer_section = "<div class='text-center my-3'>".$footer_desc."</div>";
                                        }

                                        if ($page_num == 1)
                                        {
                                            $pdf->addPage("<html>
                                                <!-- Bootstrap Stylesheet -->
                                                <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css' rel='stylesheet' integrity='sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC' crossorigin='anonymous'>
                                                <script src='https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js' integrity='sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM' crossorigin='anonymous'></script>

                                                <style>
                                                    table.contract tbody td, table.contract tbody th, table.contract thead th, table.contract tfoot td {
                                                        vertical-align: middle !important;
                                                        padding-top: 2px !important;
                                                        padding-bottom: 2px !important;
                                                    }
                                                </style>
                                                
                                                <!-- Body -->
                                                <body style='font-family: Arial, Helvetica, sans-serif !important;'>
                                                    <!-- Header -->
                                                    <div class='text-center'>
                                                        $header_img
                                                        $page_header
                                                        $page_desc
                                                    </div>

                                                    <!-- Sections -->
                                                    <div>
                                                        $sections_body
                                                    </div>

                                                    $notes_body
                                                    $revisions_body

                                                    <!-- Footer -->
                                                    <footer style='position: absolute; bottom: 0px; width: 100% !important;'>
                                                        $footer_section
                                                        <div class='text-center'>Page $page_num of $page_count</div>
                                                    </footer>
                                                </body>
                                            </html>");
                                        } 
                                        else 
                                        {
                                            $pdf->addPage("<html>
                                                <!-- Bootstrap Stylesheet -->
                                                <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css' rel='stylesheet' integrity='sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC' crossorigin='anonymous'>

                                                <style>
                                                    table.contract tbody td, table.contract tbody th, table.contract thead th, table.contract tfoot td {
                                                        vertical-align: middle !important;
                                                        padding-top: 2px !important;
                                                        padding-bottom: 2px !important;
                                                    }
                                                </style>

                                                <!-- Body -->
                                                <body style='font-family: Arial, Helvetica, sans-serif !important;'>
                                                    <!-- Header -->
                                                    <div class='text-center'>
                                                        $header_img
                                                        $page_header
                                                        $page_desc
                                                    </div>

                                                    <!-- Sections -->
                                                    <div>
                                                        $sections_body
                                                    </div>

                                                    $notes_body
                                                    $revisions_body

                                                    <!-- Footer -->
                                                    <footer style='position: absolute; bottom: 0px; width: 100% !important;'>
                                                        <div class='text-center'>Page $page_num of $page_count</div>
                                                    </footer>
                                                </body>
                                            </html>");
                                        }

                                        // increment page number
                                        $page_num++;
                                    }
                                }
                            }

                            // build the directory path and file path
                            $dirpath = "local_data/$contract_type_id/$contract_period/$customer_id";
                            $filepath = $dirpath."/".$filename.".pdf";

                            try
                            {
                                // check to see if we have created a directory to store contracts for the selected period
                                if (!is_dir($dirpath)) // directory exists 
                                {
                                    // create the directoy where owner and group can read, write, and execute to the directory
                                    mkdir($dirpath, 0770, true);
                                }

                                if (!file_exists($filepath) || (file_exists($filepath) && (isset($_POST["overwrite"]) && $_POST["overwrite"] == 1)))
                                {
                                    // attempt to save the PDF to a local directory
                                    if (!$pdf->saveAs($filepath))
                                    {
                                        echo $error = $pdf->getError();
                                        $errors++;
                                    }
                                    else 
                                    {
                                        // print to screen success
                                        echo "<span class='log-success'>Successfully</span> saved the contract for $customer_name.<br>";

                                        // get the current time in UTC
                                        date_default_timezone_set("UTC");
                                        $creation_time = date("Y-m-d H:i:s");

                                        // add to table that we created a contract
                                        $addCreated = mysqli_prepare($conn, "INSERT INTO contracts_created (period_id, customer_id, contract_type, filename, filepath, timestamp) VALUES (?, ?, ?, ?, ?, ?)");
                                        mysqli_stmt_bind_param($addCreated, "iiisss", $contract_period, $customer_id, $contract_type_id, $filename, $filepath, $creation_time);
                                        if (mysqli_stmt_execute($addCreated)) // successfully log we created the contract; attempt to upload to Google if selected
                                        {
                                            // log service contract creation
                                            $total_successes++;
                                            $message = "Successfully created the service contract for $customer_name for the period $period_name.";
                                            $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                                            mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                                            mysqli_stmt_execute($log);
                                        } else {
                                            $errors++;
                                        }
                                    }
                                }
                                else
                                {
                                    // print to screen error
                                    $errors++;
                                    echo "<span class='log-fail'>Failed</span> to save the contract for $customer_name. A file with the name $filename.pdf already exists!<br>";
                                }
                            }
                            catch (Exception $e)
                            {
                                // print to screen error
                                $errors++;
                                echo "<span class='log-fail'>Failed</span> to save the contract for $customer_name. An unexpected error has occurred! Please try again later.<br>";
                            }                                            
                        }

                        // log process
                        echo "==============================================================<br>";
                        echo "<span class='log-success'>Successfully</span> created $total_successes total contracts for $period_name.<br>";
                        if ($errors > 0) { echo "<span class='log-fail'>Failed</span> to create $errors total contracts for $period_name.<br>"; }
                        echo "</div>";

                        // log contract creation
                        $message = "Successfully created $total_successes service contracts for $period_name. ";
                        if ($errors > 0) { $message .= "Failed to create $errors service contracts for $period_name. "; }
                        $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                        mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                        mysqli_stmt_execute($log);
                    }
                    else { echo "<span class='log-fail'>Failed</span> to create contracts. The quantity period selected was invalid!<br>"; }
                }
                else { echo "<span class='log-fail'>Failed</span> to create contracts. The contract period selected was invalid!<br>"; }
            }
            else { echo "<span class='log-fail'>Failed</span> to create contracts. Missing required parameters!<br>"; }
        }
        else { echo "<span class='log-fail'>Failed</span> to perform action! User unauthorized!<br>"; }
        
        // disconnect from the database
        mysqli_close($conn);
    }

    /** function to calculate the projected cost for the selected contract period */
    function getProjectedCost($conn, $service_id, $customer_id, $contract_period, $qty_period, $qty)
    {
        // initialize variables
        $total_cost = 0;

        // get the service cost type
        $getServiceDetails = mysqli_prepare($conn, "SELECT cost_type, round_costs FROM services WHERE id=?");
        mysqli_stmt_bind_param($getServiceDetails, "s", $service_id);
        if (mysqli_stmt_execute($getServiceDetails))
        {
            $getServiceDetailsResult = mysqli_stmt_get_result($getServiceDetails);
            if (mysqli_num_rows($getServiceDetailsResult) > 0)
            {
                // store service details locally
                $service_details = mysqli_fetch_array($getServiceDetailsResult);
                $cost_type = $service_details["cost_type"];
                $round_costs = $service_details["round_costs"];

                // fixed cost
                if ($cost_type == 0)
                {
                    $getServiceCost = mysqli_prepare($conn, "SELECT cost FROM costs WHERE service_id=? AND cost_type=? AND period_id=?");
                    mysqli_stmt_bind_param($getServiceCost, "sii", $service_id, $cost_type, $contract_period);
                    if (mysqli_stmt_execute($getServiceCost))
                    {
                        $result = mysqli_stmt_get_result($getServiceCost);
                        if (mysqli_num_rows($result) > 0)
                        {
                            $cost = mysqli_fetch_array($result)["cost"];
                            $total_cost = $cost * $qty;
                        }
                    }
                }
                // variable cost
                else if ($cost_type == 1)
                {
                    $getServiceCost = mysqli_prepare($conn, "SELECT * FROM costs WHERE service_id=? AND cost_type=? AND period_id=? ORDER BY variable_order ASC");
                    mysqli_stmt_bind_param($getServiceCost, "sii", $service_id, $cost_type, $contract_period);
                    if (mysqli_stmt_execute($getServiceCost))
                    {
                        $result = mysqli_stmt_get_result($getServiceCost);
                        if (mysqli_num_rows($result) > 0)
                        {
                            $break = 0;
                            while (($range = mysqli_fetch_array($result)) && $break == 0)
                            {
                                $min = $range["min_quantity"];
                                $max = $range["max_quantity"];
                                $cost = $range["cost"];

                                if ($max != -1) // max is set
                                {
                                    if ($qty >= $min && $qty <= $max) // quantity is within the range
                                    {
                                        // calculate the total annual cost
                                        $total_cost = $cost * $qty;
                                        $break = 1; // break while loop
                                    }
                                }
                                else // no max is set
                                {
                                    // calculate the total annual cost
                                    $total_cost = $cost * $qty;
                                    $break = 1; // break while loop
                                }
                            }
                        }
                    }
                }
                // group membershiup
                else if ($cost_type == 2)
                {
                    $total_cost = getProjectedGroupMembershipCost($conn, $service_id, $customer_id, $contract_period, $qty_period);
                }
                // custom cost
                else if ($cost_type == 3)
                {
                    $total_cost = getCustomCost($conn, $service_id, $customer_id, $contract_period);
                }
                // rate cost
                else if ($cost_type == 4)
                {
                    $total_cost = getProjectedRate($conn, $service_id, $customer_id, $contract_period, $qty_period) * $qty;
                }
                // group-rates-based cost
                else if ($cost_type == 5)
                {
                    $total_cost = getProjectedGroupRateCost($conn, $service_id, $customer_id, $contract_period, $qty_period) * $qty;
                }
            }
        }

        // round cost to nearest dollar if set
        if (isset($round_costs) && $round_costs == 1) { $total_cost = round($total_cost); }

        // return the total cost
        return $total_cost;
    }

    /** function to get the active periods quantity of a service we have provided */
    function getQuantity($conn, $service_id, $customer_id, $qty_period)
    {
        $qty = 0;
        $getQty = mysqli_prepare($conn, "SELECT SUM(quantity) AS quantity_sum FROM services_provided WHERE period_id=? AND service_id=? AND customer_id=?");
        mysqli_stmt_bind_param($getQty, "isi", $qty_period, $service_id, $customer_id);
        if (mysqli_stmt_execute($getQty))
        {
            $getQtyResult = mysqli_stmt_get_result($getQty);
            if (mysqli_num_rows($getQtyResult) > 0) { $qty = mysqli_fetch_array($getQtyResult)["quantity_sum"]; }
            else { $qty = 0; }
        }
        return $qty;
    }

    /** function to get the custom cost for the given period */
    function getCustomCost($conn, $service_id, $customer_id, $qty_period)
    {
        $total_cost = 0;
        // get last year's cost
        $getPriorCost = mysqli_prepare($conn, "SELECT total_cost FROM services_provided WHERE service_id=? AND customer_id=? AND period_id=?");
        mysqli_stmt_bind_param($getPriorCost, "sii", $service_id, $customer_id, $qty_period);
        if (mysqli_stmt_execute($getPriorCost))
        {
            $getPriorCostResult = mysqli_stmt_get_result($getPriorCost);
            if (mysqli_num_rows($getPriorCostResult) > 0) // cost found
            {
                $total_cost = mysqli_fetch_array($getPriorCostResult)["total_cost"];
            }
        }
        return $total_cost;
    }

    /** function to get the projected rate cost based on active period's rate tier */
    function getProjectedRate($conn, $service_id, $customer_id, $contract_period, $qty_period)
    {
        // initialize the new rate cost
        $new_rate_cost = 0; // assume $0.00

        // attempt to find the new rate based on prior year's cost/tier
        $getCurrentRate = mysqli_prepare($conn, "SELECT total_cost FROM services_provided WHERE service_id=? AND customer_id=? AND period_id=?");
        mysqli_stmt_bind_param($getCurrentRate, "sii", $service_id, $customer_id, $qty_period);
        if (mysqli_stmt_execute($getCurrentRate))
        {
            $getCurrentRateResult = mysqli_stmt_get_result($getCurrentRate);
            if (mysqli_num_rows($getCurrentRateResult) > 0)
            {
                // store the current rate cost locally
                $current_rate_cost = mysqli_fetch_array($getCurrentRateResult)["total_cost"];

                // find current rate tier based on current rate cost
                $getCurrentTier = mysqli_prepare($conn, "SELECT variable_order FROM costs WHERE service_id=? AND period_id=? AND cost=? AND cost_type=4");
                mysqli_stmt_bind_param($getCurrentTier, "sid", $service_id, $qty_period, $current_rate_cost);
                if (mysqli_stmt_execute($getCurrentTier))
                {
                    $getCurrentTierResult = mysqli_stmt_get_result($getCurrentTier);
                    if (mysqli_num_rows($getCurrentTierResult) > 0) // current tier found
                    {
                        // store the current rate tier locally
                        $current_rate_tier = mysqli_fetch_array($getCurrentTierResult)["variable_order"];

                        // get contract period's rate cost based on active period's rate tier
                        $getNewRate = mysqli_prepare($conn, "SELECT cost FROM costs WHERE service_id=? AND period_id=? AND variable_order=? AND cost_type=4");
                        mysqli_stmt_bind_param($getNewRate, "sii", $service_id, $contract_period, $current_rate_tier);
                        if (mysqli_stmt_execute($getNewRate))
                        {
                            $getNewRateResult = mysqli_stmt_get_result($getNewRate);
                            if (mysqli_num_rows($getNewRateResult) > 0) // new rate cost found
                            {
                                // store the new rate cost locally
                                $new_rate_cost = mysqli_fetch_array($getNewRateResult)["cost"];
                            }
                        }
                    }
                }
            }
        }

        // return the projected rate
        return $new_rate_cost;
    }

    /** function to get the projected membership cost for memberships based on groups */
    function getProjectedGroupMembershipCost($conn, $service_id, $customer_id, $contract_period, $qty_period)
    {
        // initialize variables
        $total_cost = 0;

        // verify the service exists; get additional service details
        $checkService = mysqli_prepare($conn, "SELECT id, round_costs FROM services WHERE id=?");
        mysqli_stmt_bind_param($checkService, "s", $service_id);
        if (mysqli_stmt_execute($checkService))
        {
            $checkServiceResult = mysqli_stmt_get_result($checkService);
            if (mysqli_num_rows($checkServiceResult) > 0) // service exists; continue
            {
                // store service details locally
                $service_details = mysqli_fetch_array($checkServiceResult);
                $round_costs = $service_details["round_costs"];

                // check to see if the customer is an active member of the membership group based on active period's billing
                $checkMembership = mysqli_prepare($conn, "SELECT id FROM services_provided WHERE service_id=? AND customer_id=? AND period_id=?");
                mysqli_stmt_bind_param($checkMembership, "sii", $service_id, $customer_id, $qty_period);
                if (mysqli_stmt_execute($checkMembership))
                {
                    $checkMembershipResult = mysqli_stmt_get_result($checkMembership);
                    if (mysqli_num_rows($checkMembershipResult) > 0) // customer is an active member of the service
                    {
                        // get the projected cost of this service for the contract period
                        $getCostDetails = mysqli_prepare($conn, "SELECT cost, group_id FROM costs WHERE service_id=? AND period_id=? AND cost_type=2");
                        mysqli_stmt_bind_param($getCostDetails, "si", $service_id, $contract_period);
                        if (mysqli_stmt_execute($getCostDetails))
                        {
                            $getCostDetailsResult = mysqli_stmt_get_result($getCostDetails);
                            if (mysqli_num_rows($getCostDetailsResult) > 0)
                            {
                                // store cost details
                                $cost_details = mysqli_fetch_array($getCostDetailsResult);
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
                                if ($round_costs == 1) { $total_cost = round($total_membership_cost * $percentage_of_members); }
                                else { $total_cost = ($total_membership_cost * $percentage_of_members); }
                            }
                        }
                    }
                }
            }
        }

        return $total_cost;
    }

    /**
     *  function to get the cost of an invoice for a group rate service
    */
    function getProjectedGroupRateCost($conn, $service_id, $customer_id, $contract_period, $qty_period)
    {
        // initialize the new rate cost
        $new_rate_cost = 0; // assume $0.00

        // attempt to find the new rate based on prior year's cost/tier
        $getCurrentRate = mysqli_prepare($conn, "SELECT quantity, total_cost FROM services_provided WHERE service_id=? AND customer_id=? AND period_id=?");
        mysqli_stmt_bind_param($getCurrentRate, "sii", $service_id, $customer_id, $qty_period);
        if (mysqli_stmt_execute($getCurrentRate))
        {
            $getCurrentRateResult = mysqli_stmt_get_result($getCurrentRate);
            if (mysqli_num_rows($getCurrentRateResult) > 0)
            {
                // store the current rate cost locally
                $current_invoice = mysqli_fetch_assoc($getCurrentRateResult);
                $current_invoice_qty = $current_invoice["quantity"];
                $current_invoice_cost = $current_invoice["total_cost"];

                // calculate current rate cost
                $current_rate_cost = 0;
                if ($current_invoice_qty > 0) { $current_rate_cost = $current_invoice_cost / $current_invoice_qty; }

                // find current rate tier based on current rate cost
                $getCurrentTier = mysqli_prepare($conn, "SELECT in_group FROM costs WHERE service_id=? AND period_id=? AND cost=? AND cost_type=5");
                mysqli_stmt_bind_param($getCurrentTier, "sid", $service_id, $qty_period, $current_rate_cost);
                if (mysqli_stmt_execute($getCurrentTier))
                {
                    $getCurrentTierResult = mysqli_stmt_get_result($getCurrentTier);
                    if (mysqli_num_rows($getCurrentTierResult) > 0) // current tier found
                    {
                        // store the current rate tier locally
                        $current_rate_tier = mysqli_fetch_array($getCurrentTierResult)["in_group"];

                        // get contract period's rate cost based on active period's rate tier
                        $getNewRate = mysqli_prepare($conn, "SELECT cost FROM costs WHERE service_id=? AND period_id=? AND in_group=? AND cost_type=5");
                        mysqli_stmt_bind_param($getNewRate, "sii", $service_id, $contract_period, $current_rate_tier);
                        if (mysqli_stmt_execute($getNewRate))
                        {
                            $getNewRateResult = mysqli_stmt_get_result($getNewRate);
                            if (mysqli_num_rows($getNewRateResult) > 0) // new rate cost found
                            {
                                // store the new rate cost locally
                                $new_rate_cost = mysqli_fetch_array($getNewRateResult)["cost"];
                            }
                        }
                    }
                }
            }
        }

        // return the projected rate
        return $new_rate_cost;
    }

    /** function to get the active periods quantity of a service we have provided */
    function getOtherQuantity($conn, $service_id, $customer_id, $qty_period)
    {
        $qty = 0;
        $getQty = mysqli_prepare($conn, "SELECT SUM(quantity) AS quantity_sum FROM services_other_provided WHERE period_id=? AND service_id=? AND customer_id=?");
        mysqli_stmt_bind_param($getQty, "isi", $qty_period, $service_id, $customer_id);
        if (mysqli_stmt_execute($getQty))
        {
            $getQtyResult = mysqli_stmt_get_result($getQty);
            if (mysqli_num_rows($getQtyResult) > 0) { $qty = mysqli_fetch_array($getQtyResult)["quantity_sum"]; }
            else { $qty = 0; }
        }
        return $qty;
    }

    /** function to calculate the projected cost for the selected contract period */
    function getProjectedOtherCost($conn, $service_id, $customer_id, $qty_period)
    {
        // initialize variables
        $total_cost = 0;

        // projected cost for other service will remain the same as the active period
        $getActiveCost = mysqli_prepare($conn, "SELECT total_cost FROM services_other_provided WHERE service_id=? AND period_id=? AND customer_id=?");
        mysqli_stmt_bind_param($getActiveCost, "sii", $service_id, $qty_period, $customer_id);
        if (mysqli_stmt_execute($getActiveCost))
        {
            $getActiveCostResult = mysqli_stmt_get_result($getActiveCost);
            if (mysqli_num_rows($getActiveCostResult) > 0) // cost found
            {
                $total_cost = mysqli_fetch_array($getActiveCostResult)["total_cost"];
            }
        }

        return $total_cost;
    }

    /** function to get the description of the "other service" */
    function getOtherServiceDescription($conn, $service_id, $customer_id, $qty_period)
    {
        // initialize variables
        $desc = "";

        $getDesc = mysqli_prepare($conn, "SELECT description FROM services_other_provided WHERE service_id=? AND period_id=? AND customer_id=?");
        mysqli_stmt_bind_param($getDesc, "sii", $service_id, $qty_period, $customer_id);
        if (mysqli_stmt_execute($getDesc))
        {
            $getDescResult = mysqli_stmt_get_result($getDesc);
            if (mysqli_num_rows($getDescResult) > 0) // service description found
            {
                $desc = mysqli_fetch_array($getDescResult)["description"];
            }
        }

        return $desc;
    }

    /** function to get the unit of the "other service" */
    function getOtherServiceUnit($conn, $service_id, $customer_id, $qty_period)
    {
        // initialize variables
        $unit_label = "";

        $getUnit = mysqli_prepare($conn, "SELECT unit_label FROM services_other_provided WHERE service_id=? AND period_id=? AND customer_id=?");
        mysqli_stmt_bind_param($getUnit, "sii", $service_id, $qty_period, $customer_id);
        if (mysqli_stmt_execute($getUnit))
        {
            $getUnitResult = mysqli_stmt_get_result($getUnit);
            if (mysqli_num_rows($getUnitResult) > 0) // unit label found
            {
                $unit_label = mysqli_fetch_array($getUnitResult)["unit_label"];
            }
        }

        return $unit_label;
    }

    /** function to check if an invoice has been updated since contract creation */
    function checkIfUpdated($conn, $service_id, $customer_id, $contract_period, $contract_type)
    {
        // initialize variables
        $initial_time = null;
        $last_updated = null;

        // get the time the initial contract was created
        $getInitialTime = mysqli_prepare($conn, "SELECT timestamp FROM contracts_created WHERE period_id=? AND customer_id=? AND contract_type=? ORDER BY timestamp ASC LIMIT 1");
        mysqli_stmt_bind_param($getInitialTime, "iii", $contract_period, $customer_id, $contract_type);
        if (mysqli_stmt_execute($getInitialTime))
        {
            $getInitialTimeResult = mysqli_stmt_get_result($getInitialTime);
            if (mysqli_num_rows($getInitialTimeResult) > 0) // contract was created; get and store initial time
            {
                $initial_time = strtotime(mysqli_fetch_array($getInitialTimeResult)["timestamp"]);
            }
        }

        // initial time is set
        if ($initial_time != null)
        {
            // check to see if the service has been updated since the time
            $checkServiceUpdates = mysqli_prepare($conn, "SELECT updated_time FROM services_provided WHERE period_id=? AND service_id=? AND customer_id=? ORDER BY updated_time DESC LIMIT 1");
            mysqli_stmt_bind_param($checkServiceUpdates, "isi", $contract_period, $service_id, $customer_id);
            if (mysqli_stmt_execute($checkServiceUpdates))
            {
                $checkServiceUpdatesResult = mysqli_stmt_get_result($checkServiceUpdates);
                if (mysqli_num_rows($checkServiceUpdatesResult) > 0)
                {
                    // store the time an invoice for this service within the period was updated for this customer
                    $last_updated = strtotime(mysqli_fetch_array($checkServiceUpdatesResult)["updated_time"]);

                    // check to see if the updated time was more recent than the initial contract creation
                    if ($last_updated > $initial_time) // updated more recently than contract published
                    { 
                        // return the timestamp (in EPOCH)
                        return $last_updated;
                    }
                }
            }
        }

        // return false if we reach the end of the function without returning
        return false;
    }
?>