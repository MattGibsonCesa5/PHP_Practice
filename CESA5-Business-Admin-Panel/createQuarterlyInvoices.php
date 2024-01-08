<?php
    session_start();

    ///////////////////////////////////////////////////////////////////////////////////////////////
    //
    //  Initialize and require necessary files
    //
    ///////////////////////////////////////////////////////////////////////////////////////////////

    // include the Google configuration settings
    include("includes/google_config.php");

    // store Google vars locally
    $client_id = GOOGLE_CLIENT_ID;
    $client_secret = GOOGLE_CLIENT_SECRET;
    $redirect_uri = GOOGLE_QUARTERLY_INVOICE_REDIRECT_URI;

    // include the autoloader
    require_once("vendor/autoload.php");

    // include the PDF creation tool
    use mikehaertl\wkhtmlto\Pdf;
    
    // initialize the Google API client
    $client = new Google\Client();
    $client->setClientId($client_id);
    $client->setClientSecret($client_secret);
    $client->setRedirectUri($redirect_uri);
    $client->setScopes("https://www.googleapis.com/auth/drive");

    // initialize the array to store contracted services; in order that gets displayed
    $contracted_services_keys = [
        "GS01", "GS02", "SI01", "SI02", "SI03", "SI04", "CT01", "CT02", "SH01", "ET01", "TS01", "SB01", "LS01", "OTHER1", // page 1
        "SP01", "SP02", "SP03", "SP04", "SP05", "SP06", "SP07", "SP08", "SP09", "SP10", "SP11", "SP12", "SP13", "SP14", "SP15A", "SP15B", "SP15C", "SP16", "SP17", "SP18", "SP19", // page 2
        "AE01", "AE02", "AE03", "AE04", "AE05", "AE06", "AE07", "AE08", "SN01", "SPOTHER1", "SPOTHER2", "SPOTHER3" // page 2 (continued)
    ];

    ///////////////////////////////////////////////////////////////////////////////////////////////
    //
    //  Handle new form POST request
    //  - Since we'll have to authorize via Google, we'll be redirected back to this page,
    //    because of this, we'll have to store the POST data in the $_SESSION array
    //
    ///////////////////////////////////////////////////////////////////////////////////////////////

    // check to see if we have any POST parameters
    if (isset($_POST["filename"])) { $POST_filename = $_POST["filename"]; } else { $POST_filename = null; }
    if (isset($_POST["QI-period"])) { $POST_period = $_POST["QI-period"]; } else { $POST_period = null; }
    if (isset($_POST["quarter"])) { $POST_quarter = $_POST["quarter"]; } else { $POST_quarter = null; }
    if (isset($_POST["customers"])) { $POST_customers = $_POST["customers"]; } else { $POST_customers = null; }
    if (isset($_POST["QI_upload_input"])) { $POST_upload = $_POST["QI_upload_input"]; } else { $POST_upload = 0; }

    // if all required fields were filled out, store them in the $_SESSION array 
    if ($POST_filename != null && $POST_period != null && $POST_quarter != null && $POST_customers != null)
    {
        $quarterly_invoice = [];
        $quarterly_invoice["filename"] = $POST_filename;
        $quarterly_invoice["period"] = $POST_period;
        $quarterly_invoice["quarter"] = $POST_quarter;
        $quarterly_invoice["customers"] = $POST_customers;
        $quarterly_invoice["upload"] = $POST_upload;
        $_SESSION["quarterly_invoice"] = $quarterly_invoice;
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////
    //
    //  Handle form request that was stored within the $_SESSION array
    //
    ///////////////////////////////////////////////////////////////////////////////////////////////

    // check to see if we have received the authentication code
    if (isset($_GET["code"])) // authentication code received; continue
    {
        // verify login status
        if (isset($_SESSION["status"]) && $_SESSION["status"] == 1) 
        { 
            // include header and additional settings
            include("header.php");
            include("getSettings.php");

            // connect to the database
            $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

            if (checkUserPermission($conn, "CREATE_SERVICE_CONTRACTS"))
            {
                // store local variables
                $contract_type_id = QUARTERLY_INVOICE_TYPE_ID;

                // store the fixed file paths locally
                $CONTRACT_STYLESHEET_PATH = CONTRACT_STYLESHEET_PATH;
                $CESA5_LOGO_PATH = CESA5_LOGO_PATH;

                // if we already have stored newly POSTed quarterly invoice data
                if (isset($_SESSION["quarterly_invoice"]) && is_array($_SESSION["quarterly_invoice"]))
                {
                    $SESSION_quarterly_invoice = $_SESSION["quarterly_invoice"];
                    if (isset($SESSION_quarterly_invoice["filename"]) && $SESSION_quarterly_invoice["filename"] <> "") { $filename = $SESSION_quarterly_invoice["filename"]; } else { $filename = null; }
                    if (isset($SESSION_quarterly_invoice["period"]) && $SESSION_quarterly_invoice["period"] <> "") { $period = $SESSION_quarterly_invoice["period"]; } else { $period = null; }
                    if (isset($SESSION_quarterly_invoice["quarter"]) && $SESSION_quarterly_invoice["quarter"] <> "") { $quarter = $SESSION_quarterly_invoice["quarter"]; } else { $quarter = null; }
                    if (isset($SESSION_quarterly_invoice["customers"]) && $SESSION_quarterly_invoice["customers"] <> "") { $customers = $SESSION_quarterly_invoice["customers"]; } else { $customers = null; }
                    if (isset($SESSION_quarterly_invoice["upload"]) && $SESSION_quarterly_invoice["upload"] <> "") { $uploadToDrive = $SESSION_quarterly_invoice["upload"]; } else { $uploadToDrive = 0; }

                    ?>
                        <div class="row text-center">
                            <div class="col-2"></div>
                            <div class="col-8"><h1 class="upload-status-header">Create Quarterly Invoices Status</h1></div>
                            <div class="col-2"></div>
                        </div>

                        <div class="row text-center">
                            <div class="col-2"></div>
                            <div class="col-8 upload-status-report">
                            <?php
                                // verify that we have all the required parameters
                                if ($filename != null && $period != null && $quarter != null && $customers != null)
                                {
                                    if (verifyPeriod($conn, $period))
                                    {
                                        if (is_numeric($quarter) && ($quarter >= 1 && $quarter <= 4))
                                        {
                                            // fetch the access token
                                            $client->fetchAccessTokenWithAuthCode($_GET["code"]);

                                            // attempt to get the access token
                                            if ($token = $client->getAccessToken())
                                            {
                                                // set the access token
                                                $client->setAccessToken($token["access_token"]);
                                                
                                                // create a new Google Drive API service
                                                $service = new Google\Service\Drive($client);

                                                // initialize counter variables for successes and errors
                                                $total_successes = $errors = 0;

                                                // store all Google user's folders
                                                // we must store these because if a folder does not exist; the folder would be uploaded to their drive home directory - we don't want this to happen
                                                // we will only upload the file to Google Drive if the assigned folder exists
                                                // only attempt to get all folders if we are uploading contracts
                                                $folders_found = []; // initialize empty array to store folders found in Google Drive
                                                if ($uploadToDrive == 1)
                                                {
                                                    // scan the google drive directory
                                                    $folders_found = scanGoogleDrive($service, $GLOBAL_SETTINGS["quarterly_invoices_gid"]);
                                                }

                                                // connect to the database
                                                $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

                                                // get the period name
                                                $period_name = getPeriodName($conn, $period);

                                                // get all quarter labels
                                                $getQuarterLabels = mysqli_prepare($conn, "SELECT * FROM quarters WHERE period_id=?");
                                                mysqli_stmt_bind_param($getQuarterLabels, "i", $period);
                                                if (mysqli_stmt_execute($getQuarterLabels))
                                                {
                                                    $getQuarterLabelsResults = mysqli_stmt_get_result($getQuarterLabels);
                                                    while ($quarterResults = mysqli_fetch_array($getQuarterLabelsResults))
                                                    {
                                                        $quarters[$quarterResults["quarter"]]["quarter"] = $quarterResults["quarter"];
                                                        $quarters[$quarterResults["quarter"]]["label"] = $quarterResults["label"];
                                                        $quarters[$quarterResults["quarter"]]["locked"] = $quarterResults["locked"];
                                                    }
                                                }

                                                // for all customers we are creating a quarterly invoice for, attempt to get their name and Google Drive folder ID (GID)
                                                for ($c = 0; $c < count($customers); $c++)
                                                {
                                                    // store the customer ID locally
                                                    $customer_id = $customers[$c];

                                                    // query the database to get additional customer data
                                                    $getCustomerData = mysqli_prepare($conn, "SELECT name, invoice_number, invoice_folder_id FROM customers WHERE id=?");
                                                    mysqli_stmt_bind_param($getCustomerData, "i", $customer_id);
                                                    if (mysqli_stmt_execute($getCustomerData))
                                                    {
                                                        $getCustomerDataResult = mysqli_stmt_get_result($getCustomerData);
                                                        if (mysqli_num_rows($getCustomerDataResult) > 0) // customer exists; get their data
                                                        {
                                                            ///////////////////////////////////////////////////////////////////////////////////////////////
                                                            //
                                                            //  Get and build customer data
                                                            //
                                                            ///////////////////////////////////////////////////////////////////////////////////////////////

                                                            $customer_data = mysqli_fetch_array($getCustomerDataResult);
                                                            if (isset($customer_data["name"])) { $customer_name = $customer_data["name"]; } else { $customer_name = null; }
                                                            if (isset($customer_data["invoice_folder_id"])) { $customer_folder = $customer_data["invoice_folder_id"]; } else { $customer_folder = null; }
                                                            if (isset($customer_data["invoice_number"])) { $Invoice_Number = $customer_data["invoice_number"]; } else { $Invoice_Number = ""; }

                                                            // create the filename for the customer's quarterly invoice
                                                            $customer_filename = str_replace("{QUARTER}", "Q".$quarter, str_replace("{PERIOD}", $period_name, str_replace("{CUSTOMER}", $customer_name, $filename)));

                                                            // create the customer display name
                                                            $customer_display_name = $customer_name;

                                                            // initialize the array to store contracted services
                                                            $contracted_services = [];

                                                            // get the customer's quarterly invoice settings
                                                            $getContractDetails = mysqli_prepare($conn, "SELECT * FROM customer_contracts WHERE customer_id=? AND period_id=? AND contract_type_id=?");
                                                            mysqli_stmt_bind_param($getContractDetails, "iii", $customer_id, $period, $contract_type_id);
                                                            if (mysqli_stmt_execute($getContractDetails))
                                                            {
                                                                $getContractDetailsResults = mysqli_stmt_get_result($getContractDetails);
                                                                if (mysqli_num_rows($getContractDetailsResults) > 0) // details found; store currently set values
                                                                {
                                                                    $contractDetails = mysqli_fetch_array($getContractDetailsResults);
                                                                    $contracted_services["GS01"]["service"] = $contractDetails["GS01"];
                                                                    $contracted_services["GS02"]["service"] = $contractDetails["GS02"];
                                                                    $contracted_services["SI01"]["service"] = $contractDetails["SI01"];
                                                                    $contracted_services["SI02"]["service"] = $contractDetails["SI02"];
                                                                    $contracted_services["SI03"]["service"] = $contractDetails["SI03"];
                                                                    $contracted_services["SI04"]["service"] = $contractDetails["SI04"];
                                                                    $contracted_services["CT01"]["service"] = $contractDetails["CT01"];
                                                                    $contracted_services["CT02"]["service"] = $contractDetails["CT02"];
                                                                    $contracted_services["SH01"]["service"] = $contractDetails["SH01"];
                                                                    $contracted_services["ET01"]["service"] = $contractDetails["ET01"];
                                                                    $contracted_services["TS01"]["service"] = $contractDetails["TS01"];
                                                                    $contracted_services["SB01"]["service"] = $contractDetails["SB01"];
                                                                    $contracted_services["LS01"]["service"] = $contractDetails["LS01"];
                                                                    $contracted_services["OTHER1"]["service"] = $contractDetails["OTHER1"];
                                                                    $contracted_services["SP01"]["service"] = $contractDetails["SP01"];
                                                                    $contracted_services["SP02"]["service"] = $contractDetails["SP02"];
                                                                    $contracted_services["SP03"]["service"] = $contractDetails["SP03"];
                                                                    $contracted_services["SP04"]["service"] = $contractDetails["SP04"];
                                                                    $contracted_services["SP05"]["service"] = $contractDetails["SP05"]; 
                                                                    $contracted_services["SP06"]["service"] = $contractDetails["SP06"]; 
                                                                    $contracted_services["SP07"]["service"] = $contractDetails["SP07"]; 
                                                                    $contracted_services["SP08"]["service"] = $contractDetails["SP08"]; 
                                                                    $contracted_services["SP09"]["service"] = $contractDetails["SP09"]; 
                                                                    $contracted_services["SP10"]["service"] = $contractDetails["SP10"]; 
                                                                    $contracted_services["SP11"]["service"] = $contractDetails["SP11"]; 
                                                                    $contracted_services["SP12"]["service"] = $contractDetails["SP12"]; 
                                                                    $contracted_services["SP13"]["service"] = $contractDetails["SP13"]; 
                                                                    $contracted_services["SP14"]["service"] = $contractDetails["SP14"]; 
                                                                    $contracted_services["SP15A"]["service"] = $contractDetails["SP15A"]; 
                                                                    $contracted_services["SP15B"]["service"] = $contractDetails["SP15B"]; 
                                                                    $contracted_services["SP15C"]["service"] = $contractDetails["SP15C"]; 
                                                                    $contracted_services["SP16"]["service"] = $contractDetails["SP16"]; 
                                                                    $contracted_services["SP17"]["service"] = $contractDetails["SP17"]; 
                                                                    $contracted_services["SP18"]["service"] = $contractDetails["SP18"]; 
                                                                    $contracted_services["SP19"]["service"] = $contractDetails["SP19"]; 
                                                                    $contracted_services["AE01"]["service"] = $contractDetails["AE01"];
                                                                    $contracted_services["AE02"]["service"] = $contractDetails["AE02"];
                                                                    $contracted_services["AE03"]["service"] = $contractDetails["AE03"];
                                                                    $contracted_services["AE04"]["service"] = $contractDetails["AE04"];
                                                                    $contracted_services["AE05"]["service"] = $contractDetails["AE05"];
                                                                    $contracted_services["AE06"]["service"] = $contractDetails["AE06"];
                                                                    $contracted_services["AE07"]["service"] = $contractDetails["AE07"];
                                                                    $contracted_services["AE08"]["service"] = $contractDetails["AE08"];
                                                                    $contracted_services["SN01"]["service"] = $contractDetails["SN01"];
                                                                    $contracted_services["SPOTHER1"]["service"] = $contractDetails["SPOTHER1"];
                                                                    $contracted_services["SPOTHER2"]["service"] = $contractDetails["SPOTHER2"];
                                                                    $contracted_services["SPOTHER3"]["service"] = $contractDetails["SPOTHER3"];
                                                                    $page1_comment = $contractDetails["page1_comment"];
                                                                }
                                                                else // details not found; set to default values
                                                                {
                                                                    $contracted_services["GS01"]["service"] = "GS01";
                                                                    $contracted_services["GS02"]["service"] = "GS02";
                                                                    $contracted_services["SI01"]["service"] = "SI01";
                                                                    $contracted_services["SI02"]["service"] = "SI02";
                                                                    $contracted_services["SI03"]["service"] = "SI03";
                                                                    $contracted_services["SI04"]["service"] = "SI04";
                                                                    $contracted_services["CT01"]["service"] = "CT01";
                                                                    $contracted_services["CT02"]["service"] = "CT02";
                                                                    $contracted_services["SH01"]["service"] = "SH01";
                                                                    $contracted_services["ET01"]["service"] = "ET01";
                                                                    $contracted_services["TS01"]["service"] = "TS01";
                                                                    $contracted_services["SB01"]["service"] = "SB01";
                                                                    $contracted_services["LS01"]["service"] = "LS01";
                                                                    $contracted_services["OTHER1"]["service"] = "OTHER1";
                                                                    $contracted_services["SP01"]["service"] = "SP01";
                                                                    $contracted_services["SP02"]["service"] = "SP02";
                                                                    $contracted_services["SP03"]["service"] = "SP03";
                                                                    $contracted_services["SP04"]["service"] = "SP04";
                                                                    $contracted_services["SP05"]["service"] = "SP05"; 
                                                                    $contracted_services["SP06"]["service"] = "SP06"; 
                                                                    $contracted_services["SP07"]["service"] = "SP07"; 
                                                                    $contracted_services["SP08"]["service"] = "SP08"; 
                                                                    $contracted_services["SP09"]["service"] = "SP09"; 
                                                                    $contracted_services["SP10"]["service"] = "SP10U"; 
                                                                    $contracted_services["SP11"]["service"] = "SP11"; 
                                                                    $contracted_services["SP12"]["service"] = "SP12"; 
                                                                    $contracted_services["SP13"]["service"] = "SP13"; 
                                                                    $contracted_services["SP14"]["service"] = "SP14"; 
                                                                    $contracted_services["SP15A"]["service"] = "SP15"; 
                                                                    $contracted_services["SP15B"]["service"] = "SP15B"; 
                                                                    $contracted_services["SP15C"]["service"] = "SP15C"; 
                                                                    $contracted_services["SP16"]["service"] = "SP16"; 
                                                                    $contracted_services["SP17"]["service"] = "SP17"; 
                                                                    $contracted_services["SP18"]["service"] = "SP18"; 
                                                                    $contracted_services["SP19"]["service"] = "SP19"; 
                                                                    $contracted_services["AE01"]["service"] = "AE01";
                                                                    $contracted_services["AE02"]["service"] = "AE02";
                                                                    $contracted_services["AE03"]["service"] = "AE03";
                                                                    $contracted_services["AE04"]["service"] = "AE04";
                                                                    $contracted_services["AE05"]["service"] = "AE05";
                                                                    $contracted_services["AE06"]["service"] = "AE06";
                                                                    $contracted_services["AE07"]["service"] = "AE07";
                                                                    $contracted_services["AE08"]["service"] = "AE08";
                                                                    $contracted_services["SN01"]["service"] = "SN01";
                                                                    $contracted_services["SPOTHER1"]["service"] = "SPOTHER1";
                                                                    $contracted_services["SPOTHER2"]["service"] = "SPOTHER2";
                                                                    $contracted_services["SPOTHER3"]["service"] = "SPOTHER3";
                                                                    $page1_comment = "";
                                                                }

                                                                // for each contracted service, get the quarterly cost, quantity, and total
                                                                for ($s = 0; $s < count($contracted_services); $s++)
                                                                {
                                                                    // store the service ID
                                                                    $service_id = $contracted_services[$contracted_services_keys[$s]]["service"];

                                                                    // check to see if we have provided the service to the customer
                                                                    $checkInvoice = mysqli_prepare($conn, "SELECT id FROM services_provided WHERE service_id=? AND customer_id=? AND period_id=?");
                                                                    mysqli_stmt_bind_param($checkInvoice, "sii", $service_id, $customer_id, $period);
                                                                    if (mysqli_stmt_execute($checkInvoice))
                                                                    {
                                                                        $checkInvoiceResult = mysqli_stmt_get_result($checkInvoice);
                                                                        if (mysqli_num_rows($checkInvoiceResult) > 0) // invoice found
                                                                        {
                                                                            // get the quantity and total cost for the provided service
                                                                            $getInvoiceDetails = mysqli_prepare($conn, "SELECT SUM(total_cost) AS total_cost, SUM(quantity) AS total_qty FROM services_provided WHERE service_id=? AND customer_id=? AND period_id=?");
                                                                            mysqli_stmt_bind_param($getInvoiceDetails, "sii", $service_id, $customer_id, $period);
                                                                            if (mysqli_stmt_execute($getInvoiceDetails))
                                                                            {
                                                                                $getInvoiceDetailsResults = mysqli_stmt_get_result($getInvoiceDetails);
                                                                                if (mysqli_num_rows($getInvoiceDetailsResults) > 0) // invoice details found
                                                                                {
                                                                                    // store the invoice details locally
                                                                                    $invoiceDetails = mysqli_fetch_array($getInvoiceDetailsResults);
                                                                                    $total_cost = $invoiceDetails["total_cost"];
                                                                                    $total_qty = $invoiceDetails["total_qty"];

                                                                                    // add the total cost and total quantity to the contracted services array
                                                                                    $contracted_services[$contracted_services_keys[$s]]["total_qty"] = $total_qty;
                                                                                    $contracted_services[$contracted_services_keys[$s]]["total_cost"] = 0; // initialize to 0

                                                                                    // for each quarter, get the total quarterly cost for the service
                                                                                    for ($q = 1; $q <= 4; $q++)
                                                                                    {
                                                                                        $getQuarterlyCost = mysqli_prepare($conn, "SELECT SUM(cost) AS quarter_costs FROM quarterly_costs WHERE service_id=? AND customer_id=? AND period_id=? AND quarter=?");
                                                                                        mysqli_stmt_bind_param($getQuarterlyCost, "siii", $service_id, $customer_id, $period, $q);
                                                                                        if (mysqli_stmt_execute($getQuarterlyCost))
                                                                                        {
                                                                                            $getQuarterlyCostResult = mysqli_stmt_get_result($getQuarterlyCost);
                                                                                            if (mysqli_num_rows($getQuarterlyCostResult) > 0) // quarterly costs found
                                                                                            {
                                                                                                // store the quarter's cost sum
                                                                                                $quarterly_cost = mysqli_fetch_array($getQuarterlyCostResult)["quarter_costs"];

                                                                                                // add the quarterly cost to the contracted services array
                                                                                                if (isset($quarterly_cost) && is_numeric($quarterly_cost)) 
                                                                                                { 
                                                                                                    $contracted_services[$contracted_services_keys[$s]]["Q".$q] = round($quarterly_cost, 2); 
                                                                                                    $contracted_services[$contracted_services_keys[$s]]["total_cost"] += round($quarterly_cost, 2);
                                                                                                } 
                                                                                                else { $contracted_services[$contracted_services_keys[$s]]["Q".$q] = 0; }
                                                                                            }
                                                                                            else // quarterly costs not found; store 0
                                                                                            {
                                                                                                $contracted_services[$contracted_services_keys[$s]]["Q".$q] = 0;
                                                                                            }
                                                                                        }
                                                                                        else // invoice details not found; set everything to 0
                                                                                        {
                                                                                            $contracted_services[$contracted_services_keys[$s]]["Q1"] = 0;
                                                                                            $contracted_services[$contracted_services_keys[$s]]["Q2"] = 0;
                                                                                            $contracted_services[$contracted_services_keys[$s]]["Q3"] = 0;
                                                                                            $contracted_services[$contracted_services_keys[$s]]["Q4"] = 0;
                                                                                        }
                                                                                    }
                                                                                }
                                                                                else // invoice not found; set all to 0
                                                                                {
                                                                                    $contracted_services[$contracted_services_keys[$s]]["total_cost"] = 0;
                                                                                    $contracted_services[$contracted_services_keys[$s]]["total_qty"] = 0;
                                                                                    $contracted_services[$contracted_services_keys[$s]]["Q1"] = 0;
                                                                                    $contracted_services[$contracted_services_keys[$s]]["Q2"] = 0;
                                                                                    $contracted_services[$contracted_services_keys[$s]]["Q3"] = 0;
                                                                                    $contracted_services[$contracted_services_keys[$s]]["Q4"] = 0;
                                                                                }
                                                                            }
                                                                        }
                                                                        else // invoice details not found in services_provided; check services_other_provided
                                                                        {
                                                                            $getOtherInvoiceDetails = mysqli_prepare($conn, "SELECT id, description, total_cost, quantity, unit_label FROM services_other_provided WHERE service_id=? AND customer_id=? AND period_id=?");
                                                                            mysqli_stmt_bind_param($getOtherInvoiceDetails, "sii", $service_id, $customer_id, $period);
                                                                            if (mysqli_stmt_execute($getOtherInvoiceDetails))
                                                                            {
                                                                                $getOtherInvoiceDetailsResults = mysqli_stmt_get_result($getOtherInvoiceDetails);
                                                                                if (mysqli_num_rows($getOtherInvoiceDetailsResults) > 0) // invoice details found
                                                                                {
                                                                                    // store the invoice details locally
                                                                                    $invoiceDetails = mysqli_fetch_array($getOtherInvoiceDetailsResults);
                                                                                    $invoice_id = $invoiceDetails["id"];
                                                                                    $invoice_desc = $invoiceDetails["description"];
                                                                                    $unit_label = $invoiceDetails["unit_label"];
                                                                                    $total_cost = $invoiceDetails["total_cost"];
                                                                                    $total_qty = $invoiceDetails["quantity"];

                                                                                    // add the total cost, total quantity, and description to the contracted services array
                                                                                    $contracted_services[$contracted_services_keys[$s]]["description"] = $invoice_desc;
                                                                                    $contracted_services[$contracted_services_keys[$s]]["unit_label"] = $unit_label;
                                                                                    $contracted_services[$contracted_services_keys[$s]]["total_cost"] = 0; // initialize to 0
                                                                                    $contracted_services[$contracted_services_keys[$s]]["total_qty"] = $total_qty;

                                                                                    // for each quarter, get the total quarterly cost for the service
                                                                                    for ($q = 1; $q <= 4; $q++)
                                                                                    {
                                                                                        $getOtherQuarterlyCost = mysqli_prepare($conn, "SELECT cost FROM other_quarterly_costs WHERE other_invoice_id=? AND quarter=?");
                                                                                        mysqli_stmt_bind_param($getOtherQuarterlyCost, "ii", $invoice_id, $q);
                                                                                        if (mysqli_stmt_execute($getOtherQuarterlyCost))
                                                                                        {
                                                                                            $getOtherQuarterlyCostResult = mysqli_stmt_get_result($getOtherQuarterlyCost);
                                                                                            if (mysqli_num_rows($getOtherQuarterlyCostResult) > 0) // quarterly costs found
                                                                                            {
                                                                                                // store the quarter's cost sum
                                                                                                $quarterly_cost = mysqli_fetch_array($getOtherQuarterlyCostResult)["cost"];

                                                                                                // add the quarterly cost to the contracted services array
                                                                                                if (isset($quarterly_cost) && is_numeric($quarterly_cost)) 
                                                                                                { 
                                                                                                    $contracted_services[$contracted_services_keys[$s]]["Q".$q] = round($quarterly_cost, 2); 
                                                                                                    $contracted_services[$contracted_services_keys[$s]]["total_cost"] += round($quarterly_cost, 2);
                                                                                                } 
                                                                                                else { $contracted_services[$contracted_services_keys[$s]]["Q".$q] = 0; }
                                                                                            }
                                                                                            else // quarterly costs not found; store 0
                                                                                            {
                                                                                                $contracted_services[$contracted_services_keys[$s]]["Q".$q] = 0;
                                                                                            }
                                                                                        }
                                                                                        else // invoice details not found; set everything to 0
                                                                                        {
                                                                                            $contracted_services[$contracted_services_keys[$s]]["Q1"] = 0;
                                                                                            $contracted_services[$contracted_services_keys[$s]]["Q2"] = 0;
                                                                                            $contracted_services[$contracted_services_keys[$s]]["Q3"] = 0;
                                                                                            $contracted_services[$contracted_services_keys[$s]]["Q4"] = 0;
                                                                                        }
                                                                                    }
                                                                                }
                                                                                else // invoice details not found; set everything to 0
                                                                                {
                                                                                    $contracted_services[$contracted_services_keys[$s]]["description"] = "";
                                                                                    $contracted_services[$contracted_services_keys[$s]]["unit_label"] = "";
                                                                                    $contracted_services[$contracted_services_keys[$s]]["total_cost"] = 0;
                                                                                    $contracted_services[$contracted_services_keys[$s]]["total_qty"] = 0;
                                                                                    $contracted_services[$contracted_services_keys[$s]]["Q1"] = 0;
                                                                                    $contracted_services[$contracted_services_keys[$s]]["Q2"] = 0;
                                                                                    $contracted_services[$contracted_services_keys[$s]]["Q3"] = 0;
                                                                                    $contracted_services[$contracted_services_keys[$s]]["Q4"] = 0;
                                                                                }
                                                                            }
                                                                            else // invoice details not found; set everything to 0
                                                                            {
                                                                                $contracted_services[$contracted_services_keys[$s]]["description"] = "";
                                                                                $contracted_services[$contracted_services_keys[$s]]["unit_label"] = "";
                                                                                $contracted_services[$contracted_services_keys[$s]]["total_cost"] = 0;
                                                                                $contracted_services[$contracted_services_keys[$s]]["total_qty"] = 0;
                                                                                $contracted_services[$contracted_services_keys[$s]]["Q1"] = 0;
                                                                                $contracted_services[$contracted_services_keys[$s]]["Q2"] = 0;
                                                                                $contracted_services[$contracted_services_keys[$s]]["Q3"] = 0;
                                                                                $contracted_services[$contracted_services_keys[$s]]["Q4"] = 0;
                                                                            }
                                                                        }
                                                                    }
                                                                    else // invoice details not found; set everything to 0
                                                                    {
                                                                        $contracted_services[$contracted_services_keys[$s]]["description"] = "";
                                                                        $contracted_services[$contracted_services_keys[$s]]["unit_label"] = "";
                                                                        $contracted_services[$contracted_services_keys[$s]]["total_cost"] = 0;
                                                                        $contracted_services[$contracted_services_keys[$s]]["total_qty"] = 0;
                                                                        $contracted_services[$contracted_services_keys[$s]]["Q1"] = 0;
                                                                        $contracted_services[$contracted_services_keys[$s]]["Q2"] = 0;
                                                                        $contracted_services[$contracted_services_keys[$s]]["Q3"] = 0;
                                                                        $contracted_services[$contracted_services_keys[$s]]["Q4"] = 0;
                                                                    }
                                                                }

                                                                // initialize the totals variables
                                                                $Q1_Total = $Q2_Total = $Q3_Total = $Q4_Total = $Total = 0;
                                                                
                                                                // for each service, add costs to totals
                                                                for ($s = 0; $s < count($contracted_services); $s++)
                                                                {
                                                                    $Q1_Total += $contracted_services[$contracted_services_keys[$s]]["Q1"];
                                                                    $Q2_Total += $contracted_services[$contracted_services_keys[$s]]["Q2"];
                                                                    $Q3_Total += $contracted_services[$contracted_services_keys[$s]]["Q3"];
                                                                    $Q4_Total += $contracted_services[$contracted_services_keys[$s]]["Q4"];
                                                                    $Total += $contracted_services[$contracted_services_keys[$s]]["total_cost"];
                                                                }

                                                                // initialize the variables to store the amount due for each quarter
                                                                $Q1_Due = $Q2_Due = $Q3_Due = $Q4_Due = $Amount_Due = "";
                                                                // build the amount due display
                                                                if ($quarter == 1) 
                                                                { 
                                                                    $Q1_Due = printDollar($Q1_Total); 
                                                                    $Amount_Due = $Q1_Due;
                                                                }
                                                                if ($quarter == 2) 
                                                                { 
                                                                    $Q2_Due = printDollar($Q2_Total); 
                                                                    $Amount_Due = $Q2_Due;
                                                                }
                                                                if ($quarter == 3) 
                                                                { 
                                                                    $Q3_Due = printDollar($Q3_Total);
                                                                    $Amount_Due = $Q3_Due;
                                                                }
                                                                if ($quarter == 4) 
                                                                { 
                                                                    $Q4_Due = printDollar($Q4_Total); 
                                                                    $Amount_Due = $Q4_Due;
                                                                }
                                                                
                                                                // set all totals in number format
                                                                if (isset($Q1_Total) && is_numeric($Q1_Total)) { $Q1_Total = number_format($Q1_Total, 2); }
                                                                if (isset($Q2_Total) && is_numeric($Q2_Total)) { $Q2_Total = number_format($Q2_Total, 2); }
                                                                if (isset($Q3_Total) && is_numeric($Q3_Total)) { $Q3_Total = number_format($Q3_Total, 2); }
                                                                if (isset($Q4_Total) && is_numeric($Q4_Total)) { $Q4_Total = number_format($Q4_Total, 2); }

                                                                // create amount due label
                                                                $Q1_Due_Label = $Q2_Due_Label = $Q3_Due_Label = $Q4_Due_Label = "";
                                                                if ($quarter == 1) { $Q1_Due_Label = "AMOUNT DUE"; }
                                                                if ($quarter == 2) { $Q2_Due_Label = "AMOUNT DUE"; }
                                                                if ($quarter == 3) { $Q3_Due_Label = "AMOUNT DUE"; }
                                                                if ($quarter == 4) { $Q4_Due_Label = "AMOUNT DUE"; }

                                                                // format the total cost
                                                                $Total = printDollar($Total);

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

                                                                // create the column classes
                                                                $Q1_Class = $Q2_Class = $Q3_Class = $Q4_Class = "text-center";
                                                                $Q1_Header_Class = $Q2_Header_Class = $Q3_Header_Class = $Q4_Header_Class = "text-center";
                                                                if ($quarter == 1) 
                                                                { 
                                                                    $Q1_Class .= " contract-quarter"; 
                                                                    $Q1_Header_Class .= " contract-quarter"; 
                                                                }
                                                                if ($quarter == 2) 
                                                                { 
                                                                    $Q2_Class .= " contract-quarter"; 
                                                                    $Q2_Header_Class .= " contract-quarter"; 
                                                                }
                                                                if ($quarter == 3) 
                                                                { 
                                                                    $Q3_Class .= " contract-quarter"; 
                                                                    $Q3_Header_Class .= " contract-quarter"; 
                                                                }
                                                                if ($quarter == 4) 
                                                                { 
                                                                    $Q4_Class .= " contract-quarter"; 
                                                                    $Q4_Header_Class .= " contract-quarter"; 
                                                                }

                                                                // create the quarter labels
                                                                $Q1_Label = $Q2_Label = $Q3_Label = $Q4_Label = "";
                                                                $Q1_Label_Break = $Q2_Label_Break = $Q3_Label_Break = $Q4_Label_Break = "";
                                                                // create labels that are not broken on a space
                                                                if (isset($quarters[1]["label"])) { $Q1_Label = $quarters[1]["label"]; }
                                                                if (isset($quarters[2]["label"])) { $Q2_Label = $quarters[2]["label"]; }
                                                                if (isset($quarters[3]["label"])) { $Q3_Label = $quarters[3]["label"]; }
                                                                if (isset($quarters[4]["label"])) { $Q4_Label = $quarters[4]["label"]; }
                                                                // create labels broken on space
                                                                if (isset($quarters[1]["label"])) 
                                                                { 
                                                                    $Q1_Label_Arr = explode(" ", $quarters[1]["label"]);
                                                                    $Q1_Label_Break = $Q1_Label_Arr[0];
                                                                    if (isset($Q1_Label_Arr[1])) { $Q1_Label_Break .= "<br>".$Q1_Label_Arr[1]; }
                                                                }
                                                                if (isset($quarters[2]["label"])) 
                                                                { 
                                                                    $Q2_Label_Arr = explode(" ", $quarters[2]["label"]);
                                                                    $Q2_Label_Break = $Q2_Label_Arr[0];
                                                                    if (isset($Q2_Label_Arr[1])) { $Q2_Label_Break .= "<br>".$Q2_Label_Arr[1]; }
                                                                }
                                                                if (isset($quarters[3]["label"])) 
                                                                { 
                                                                    $Q3_Label_Arr = explode(" ", $quarters[3]["label"]);
                                                                    $Q3_Label_Break = $Q3_Label_Arr[0];
                                                                    if (isset($Q3_Label_Arr[1])) { $Q3_Label_Break .= "<br>".$Q3_Label_Arr[1]; }
                                                                }
                                                                if (isset($quarters[4]["label"])) 
                                                                { 
                                                                    $Q4_Label_Arr = explode(" ", $quarters[4]["label"]);
                                                                    $Q4_Label_Break = $Q4_Label_Arr[0];
                                                                    if (isset($Q4_Label_Arr[1])) { $Q4_Label_Break .= "<br>".$Q4_Label_Arr[1]; }
                                                                }



                                                                // create the invoice date
                                                                $Invoice_Date = "";
                                                                if ($quarter == 1) { $Invoice_Date = $Q1_Label; }
                                                                if ($quarter == 2) { $Invoice_Date = $Q2_Label; }
                                                                if ($quarter == 3) { $Invoice_Date = $Q3_Label; }
                                                                if ($quarter == 4) { $Invoice_Date = $Q4_Label; }

                                                                // get the customer's address
                                                                $address_line1 = $address_line2 = $street = $city = $state = $zip = ""; // initialize variables to store address
                                                                $getAddress = mysqli_prepare($conn, "SELECT a.street, a.city, a.zip, s.abbreviation AS state FROM customer_addresses a
                                                                                                    JOIN states s ON a.state_id=s.id
                                                                                                    WHERE a.customer_id=?");
                                                                mysqli_stmt_bind_param($getAddress, "i", $customer_id);
                                                                if (mysqli_stmt_execute($getAddress))
                                                                {
                                                                    $getAddressResult = mysqli_stmt_get_result($getAddress);
                                                                    if (mysqli_num_rows($getAddressResult) > 0) // address found
                                                                    {
                                                                        // store address details locally
                                                                        $addressDetails = mysqli_fetch_array($getAddressResult);
                                                                        $street = $addressDetails["street"];
                                                                        $city = $addressDetails["city"];
                                                                        $state = $addressDetails["state"];
                                                                        $zip = $addressDetails["zip"];
                                                                    }
                                                                }
                                                                // create the address
                                                                $address_line1 = $street;
                                                                $address_line2 = $city . ", " . $state . " " . $zip;

                                                                ///////////////////////////////////////////////////////////////////////////////////////////////
                                                                //
                                                                //  PDF PAGE 1
                                                                //
                                                                ///////////////////////////////////////////////////////////////////////////////////////////////
                                                                $pdf->addPage("<html>
                                                                    <!-- Custom Stylesheet -->
                                                                    <link href='$CONTRACT_STYLESHEET_PATH' rel='stylesheet'>
                                        
                                                                    <!-- Bootstrap Stylesheet -->
                                                                    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css' rel='stylesheet' integrity='sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC' crossorigin='anonymous'>
                                                                    <script src='https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js' integrity='sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM' crossorigin='anonymous'></script>
                                        
                                                                    <!-- Header -->
                                                                    <table class='border-0 w-100'>
                                                                        <thead class='border-0'>
                                                                            <!-- set column widths -->
                                                                            <tr class='border-0'>
                                                                                <th class='border-0' style='width: 40%;'></th>
                                                                                <th class='border-0' style='width: 20%;'></th>
                                                                                <th class='border-0' style='width: 20%;'></th>
                                                                                <th class='border-0' style='width: 20%;'></th>
                                                                            </tr>
                                        
                                                                            <tr class='border-0'>
                                                                                <td class='border-0'><img class='w-75 m-0 p-0' src='$CESA5_LOGO_PATH' alt='CESA 5 Logo'></td>
                                                                                <td class='border-0'></td>
                                                                                <td class='border-0' colspan='2'>                
                                                                                    <h3 class='fw-bold' style='font-size: 16px !important;'>
                                                                                        Cooperative Educational Service Agency 5<br>
                                                                                        626 E. Slifer St.<br>
                                                                                        Portage, WI 53901<br>
                                                                                        (608) 745-5400
                                                                                    </h3>
                                                                                </td>
                                                                            </tr>
                                        
                                                                            <tr class='border-0'><td class='table-section-divider border-0'>*</td></tr>
                                        
                                                                            <tr class='border-0'>
                                                                                <th class='border-0 text-start'>$customer_display_name School District</th>
                                                                                <th class='border-0'></th>
                                                                                <th>Invoice No.</th>
                                                                                <th>$Invoice_Number</th>
                                                                            </tr>
                                        
                                                                            <tr class='border-0'>
                                                                                <th class='border-0 text-start'>$address_line1</th>
                                                                                <th class='border-0'></th>
                                                                                <th>Invoice Date</th>
                                                                                <th>$Invoice_Date</th>
                                                                            </tr>
                                        
                                                                            <tr class='border-0'>
                                                                                <th class='border-0 text-start'>$address_line2</th>
                                                                                <th class='border-0'></th>
                                                                                <th>Amount Due</th>
                                                                                <th>$Amount_Due</th>
                                                                            </tr>
                                                                        </thead>
                                                                    </table>
                                                                    <!-- End Header -->
                                        
                                                                    <!-- Spacer -->
                                                                    <div class='table-section-divider'>*</div>
                                        
                                                                    <!-- Contract Description -->
                                                                    <div class='text-center'>
                                                                        <h3 class='fw-bold'>$customer_display_name School District</h1>
                                                                        <h3 class='fw-bold'>$period_name CONTRACTED SERVICES INVOICE</h1>
                                                                        <p class='fst-italic mb-0'>Amounts listed are based on current information regarding special education students in your school district.</p>
                                                                        <p class='fst-italic'>Adjustments are made each quarter as student information changes.</p>
                                                                    </div>
                                                                    <!-- End Contract Description --> 
                                        
                                                                    <!-- Spacer -->
                                                                    <div class='table-section-divider'>*</div>
                                        
                                                                    <!-- Contract -->
                                                                    <table class='border-0 w-100'>
                                                                        <thead class='border-0'>
                                                                            <!-- set column widths -->
                                                                            <tr class='border-0'>
                                                                                <th class='border-0' style='width: 37.5%'></th>
                                                                                <th class='border-0' style='width: 1.5%'></th>
                                                                                <th class='border-0' style='width: 6%'></th>
                                                                                <th class='border-0' style='width: 6%'></th>
                                                                                <th class='border-0' style='width: 9.75%'></th>
                                                                                <th class='border-0' style='width: 9.75%'></th>
                                                                                <th class='border-0' style='width: 9.75%'></th>
                                                                                <th class='border-0' style='width: 9.75%'></th>
                                                                                <th class='border-0' style='width: 10%'></th>
                                                                            </tr>
                                        
                                                                            <!-- header row & general services-->
                                                                            <tr class='border-0'>
                                                                                <th class='text-start text-decoration-underline border-0'>General Services (GS)</th>
                                                                                <th class='table-section-divider border-0'>*</th>
                                                                                <th>Unit</th>
                                                                                <th>Qty</th>
                                                                                <th class='$Q1_Header_Class'>$Q1_Label_Break</th>
                                                                                <th class='$Q2_Header_Class'>$Q2_Label_Break</th>
                                                                                <th class='$Q3_Header_Class'>$Q3_Label_Break</th>
                                                                                <th class='$Q4_Header_Class'>$Q4_Label_Break</th>
                                                                                <th>Annual<br>Amount</th>
                                                                            </tr>
                                        
                                                                            <tr class='border-0'>
                                                                                <td>GS01 - District Membership Fee</td>
                                                                                <td class='table-cell-divider border-0'></td>
                                                                                <td></td>
                                                                                <td></td>
                                                                                <td class='$Q1_Class'>".number_format($contracted_services["GS01"]["Q1"], 2)."</td>
                                                                                <td class='$Q2_Class'>".number_format($contracted_services["GS01"]["Q2"], 2)."</td>
                                                                                <td class='$Q3_Class'>".number_format($contracted_services["GS01"]["Q3"], 2)."</td>
                                                                                <td class='$Q4_Class'>".number_format($contracted_services["GS01"]["Q4"], 2)."</td>
                                                                                <td class='text-center'>".number_format($contracted_services["GS01"]["total_cost"], 2)."</td>
                                                                            </tr>
                                        
                                                                            <tr class='border-0'>
                                                                                <td>GS02 - Driver's Education</td>
                                                                                <td class='table-cell-divider border-0'></td>
                                                                                <td class='text-center'>FTE</td>
                                                                                <td class='text-center'>".round($contracted_services["GS02"]["total_qty"], 2)."</td>
                                                                                <td class='$Q1_Class'>".number_format($contracted_services["GS02"]["Q1"], 2)."</td>
                                                                                <td class='$Q2_Class'>".number_format($contracted_services["GS02"]["Q2"], 2)."</td>
                                                                                <td class='$Q3_Class'>".number_format($contracted_services["GS02"]["Q3"], 2)."</td>
                                                                                <td class='$Q4_Class'>".number_format($contracted_services["GS02"]["Q4"], 2)."</td>
                                                                                <td class='text-center'>".number_format($contracted_services["GS02"]["total_cost"], 2)."</td>
                                                                            </tr>
                                        
                                                                            <!-- spacer -->
                                                                            <tr class='border-0'>
                                                                                <td colspan='1' class='table-section-divider border-0'>*</td>
                                                                                <td colspan='1' class='table-section-divider border-0'>*</td>
                                                                                <td colspan='1' class='table-section-divider border-0'>*</td>
                                                                                <td colspan='1' class='table-section-divider border-0'>*</td>
                                                                                <td colspan='1' class='table-section-divider border-0 $Q1_Class'></td>
                                                                                <td colspan='1' class='table-section-divider border-0 $Q2_Class'></td>
                                                                                <td colspan='1' class='table-section-divider border-0 $Q3_Class'></td>
                                                                                <td colspan='1' class='table-section-divider border-0 $Q4_Class'></td>
                                                                                <td colspan='1' class='table-section-divider border-0'>*</td>
                                                                            </tr>
                                        
                                                                            <!-- Instructional Services -->
                                                                            <tr class='border-0'>
                                                                                <th class='text-start text-decoration-underline border-0'>Instructional Services (SI/SW/SH)</th>
                                                                                <td colspan='1' class='table-section-divider border-0'>*</td>
                                                                                <td colspan='1' class='table-section-divider border-0'>*</td>
                                                                                <td colspan='1' class='table-section-divider border-0'>*</td>
                                                                                <td colspan='1' class='table-section-divider border-0 $Q1_Class'></td>
                                                                                <td colspan='1' class='table-section-divider border-0 $Q2_Class'></td>
                                                                                <td colspan='1' class='table-section-divider border-0 $Q3_Class'></td>
                                                                                <td colspan='1' class='table-section-divider border-0 $Q4_Class'></td>
                                                                                <td colspan='1' class='table-section-divider border-0'>*</td>
                                                                            </tr>

                                                                            <tr class='border-0'>
                                                                                <td class='text-center fst-italic border-0'>Center for School Improvement (SI)</td>
                                                                                <td colspan='1' class='table-section-divider border-0'>*</td>
                                                                                <td colspan='1' class='table-section-divider border-0'>*</td>
                                                                                <td colspan='1' class='table-section-divider border-0'>*</td>
                                                                                <td colspan='1' class='table-section-divider border-0 $Q1_Class'></td>
                                                                                <td colspan='1' class='table-section-divider border-0 $Q2_Class'></td>
                                                                                <td colspan='1' class='table-section-divider border-0 $Q3_Class'></td>
                                                                                <td colspan='1' class='table-section-divider border-0 $Q4_Class'></td>
                                                                                <td colspan='1' class='table-section-divider border-0'>*</td>
                                                                            </tr>
                                        
                                                                            <tr class='border-0'>
                                                                                <td style='font-size: 14px !important;'>SI01 - School Improvement Services (Curr. & Instr.)</td>
                                                                                <td class='table-cell-divider border-0'></td>
                                                                                <td></td>
                                                                                <td></td>
                                                                                <td class='$Q1_Class'>".number_format($contracted_services["SI01"]["Q1"], 2)."</td>
                                                                                <td class='$Q2_Class'>".number_format($contracted_services["SI01"]["Q2"], 2)."</td>
                                                                                <td class='$Q3_Class'>".number_format($contracted_services["SI01"]["Q3"], 2)."</td>
                                                                                <td class='$Q4_Class'>".number_format($contracted_services["SI01"]["Q4"], 2)."</td>
                                                                                <td class='text-center'>".number_format($contracted_services["SI01"]["total_cost"], 2)."</td>
                                                                            </tr>
                                        
                                                                            <tr class='border-0'>
                                                                                <td>SI02 - Curriculum Specialist</td>
                                                                                <td class='table-cell-divider border-0'></td>
                                                                                <td class='text-center'>Days</td>
                                                                                <td class='text-center'>".round($contracted_services["SI02"]["total_qty"], 2)."</td>
                                                                                <td class='$Q1_Class'>".number_format($contracted_services["SI02"]["Q1"], 2)."</td>
                                                                                <td class='$Q2_Class'>".number_format($contracted_services["SI02"]["Q2"], 2)."</td>
                                                                                <td class='$Q3_Class'>".number_format($contracted_services["SI02"]["Q3"], 2)."</td>
                                                                                <td class='$Q4_Class'>".number_format($contracted_services["SI02"]["Q4"], 2)."</td>
                                                                                <td class='text-center'>".number_format($contracted_services["SI02"]["total_cost"], 2)."</td>
                                                                            </tr>
                                        
                                                                            <tr class='border-0'>
                                                                                <td>SI03 - Coaching and Mentoring Consortium</td>
                                                                                <td class='table-cell-divider border-0'></td>
                                                                                <td></td>
                                                                                <td></td>
                                                                                <td class='$Q1_Class'>".number_format($contracted_services["SI03"]["Q1"], 2)."</td>
                                                                                <td class='$Q2_Class'>".number_format($contracted_services["SI03"]["Q2"], 2)."</td>
                                                                                <td class='$Q3_Class'>".number_format($contracted_services["SI03"]["Q3"], 2)."</td>
                                                                                <td class='$Q4_Class'>".number_format($contracted_services["SI03"]["Q4"], 2)."</td>
                                                                                <td class='text-center'>".number_format($contracted_services["SI03"]["total_cost"], 2)."</td>
                                                                            </tr>
                                        
                                                                            <tr class='border-0'>
                                                                                <td>SI04 - Title III Consortium</td>
                                                                                <td class='table-cell-divider border-0'></td>
                                                                                <td></td>
                                                                                <td></td>
                                                                                <td class='$Q1_Class'>".number_format($contracted_services["SI04"]["Q1"], 2)."</td>
                                                                                <td class='$Q2_Class'>".number_format($contracted_services["SI04"]["Q2"], 2)."</td>
                                                                                <td class='$Q3_Class'>".number_format($contracted_services["SI04"]["Q3"], 2)."</td>
                                                                                <td class='$Q4_Class'>".number_format($contracted_services["SI04"]["Q4"], 2)."</td>
                                                                                <td class='text-center'>".number_format($contracted_services["SI04"]["total_cost"], 2)."</td>
                                                                            </tr>
                                        
                                                                            <tr class='border-0'>
                                                                                <td class='text-center fst-italic border-0'>School-to-Work (SW)</td>
                                                                                <td colspan='1' class='table-section-divider border-0'>*</td>
                                                                                <td colspan='1' class='table-section-divider border-0'>*</td>
                                                                                <td colspan='1' class='table-section-divider border-0'>*</td>
                                                                                <td colspan='1' class='table-section-divider border-0 $Q1_Class'></td>
                                                                                <td colspan='1' class='table-section-divider border-0 $Q2_Class'></td>
                                                                                <td colspan='1' class='table-section-divider border-0 $Q3_Class'></td>
                                                                                <td colspan='1' class='table-section-divider border-0 $Q4_Class'></td>
                                                                                <td colspan='1' class='table-section-divider border-0'>*</td>
                                                                            </tr>
                                        
                                                                            <tr class='border-0'>
                                                                                <td style='font-size: 14px !important;'>CT01 - Career and Technical Education Council</td>
                                                                                <td class='table-cell-divider border-0'></td>
                                                                                <td></td>
                                                                                <td></td>
                                                                                <td class='$Q1_Class'>".number_format($contracted_services["CT01"]["Q1"], 2)."</td>
                                                                                <td class='$Q2_Class'>".number_format($contracted_services["CT01"]["Q2"], 2)."</td>
                                                                                <td class='$Q3_Class'>".number_format($contracted_services["CT01"]["Q3"], 2)."</td>
                                                                                <td class='$Q4_Class'>".number_format($contracted_services["CT01"]["Q4"], 2)."</td>
                                                                                <td class='text-center'>".number_format($contracted_services["CT01"]["total_cost"], 2)."</td>
                                                                            </tr>
                                        
                                                                            <tr class='border-0'>
                                                                                <td style='font-size: 14px !important;'>CT02 - Career and Technical Education Leadership</td>
                                                                                <td class='table-cell-divider border-0'></td>
                                                                                <td></td>
                                                                                <td></td>
                                                                                <td class='$Q1_Class'>".number_format($contracted_services["CT02"]["Q1"], 2)."</td>
                                                                                <td class='$Q2_Class'>".number_format($contracted_services["CT02"]["Q2"], 2)."</td>
                                                                                <td class='$Q3_Class'>".number_format($contracted_services["CT02"]["Q3"], 2)."</td>
                                                                                <td class='$Q4_Class'>".number_format($contracted_services["CT02"]["Q4"], 2)."</td>
                                                                                <td class='text-center'>".number_format($contracted_services["CT02"]["total_cost"], 2)."</td>
                                                                            </tr>
                                        
                                                                            <tr class='border-0'>
                                                                                <td class='text-center fst-italic border-0'>Safe and Healthy Schools (SH)</td>
                                                                                <td colspan='1' class='table-section-divider border-0'>*</td>
                                                                                <td colspan='1' class='table-section-divider border-0'>*</td>
                                                                                <td colspan='1' class='table-section-divider border-0'>*</td>
                                                                                <td colspan='1' class='table-section-divider border-0 $Q1_Class'></td>
                                                                                <td colspan='1' class='table-section-divider border-0 $Q2_Class'></td>
                                                                                <td colspan='1' class='table-section-divider border-0 $Q3_Class'></td>
                                                                                <td colspan='1' class='table-section-divider border-0 $Q4_Class'></td>
                                                                                <td colspan='1' class='table-section-divider border-0'>*</td>
                                                                            </tr>
                                        
                                                                            <!-- BORDER STYLING (SH01) ???
                                                                            <tr style='border: 0px solid white !important;'>
                                                                                <td class='cell-border border-1'>SH01 - Safe and Healthy Schools Consortium</td>
                                                                                <td class='table-section-divider'></td>
                                                                                <td class='cell-border border-1'></td>
                                                                                <td class='cell-border border-1'></td>
                                                                                <td class='$Q1_Class cell-border border-1'>".number_format($contracted_services["SH01"]["Q1"], 2)."</td>
                                                                                <td class='$Q2_Class cell-border border-1'>".number_format($contracted_services["SH01"]["Q2"], 2)."</td>
                                                                                <td class='$Q3_Class cell-border border-1'>".number_format($contracted_services["SH01"]["Q3"], 2)."</td>
                                                                                <td class='$Q4_Class cell-border border-1'>".number_format($contracted_services["SH01"]["Q4"], 2)."</td>
                                                                                <td class='text-center cell-border border-1'>".number_format($contracted_services["SH01"]["total_cost"], 2)."</td>
                                                                            </tr>
                                                                            -->

                                                                            <tr class='border-0'>
                                                                                <td style='font-size: 14px !important;'>SH01 - Safe and Healthy Schools Consortium</td>
                                                                                <td class='table-cell-divider border-0'></td>
                                                                                <td></td>
                                                                                <td></td>
                                                                                <td class='$Q1_Class'>".number_format($contracted_services["SH01"]["Q1"], 2)."</td>
                                                                                <td class='$Q2_Class'>".number_format($contracted_services["SH01"]["Q2"], 2)."</td>
                                                                                <td class='$Q3_Class'>".number_format($contracted_services["SH01"]["Q3"], 2)."</td>
                                                                                <td class='$Q4_Class'>".number_format($contracted_services["SH01"]["Q4"], 2)."</td>
                                                                                <td class='text-center'>".number_format($contracted_services["SH01"]["total_cost"], 2)."</td>
                                                                            </tr>
                                        
                                                                            <!-- spacer -->
                                                                            <tr class='border-0'>
                                                                                <td colspan='1' class='table-section-divider border-0'>*</td>
                                                                                <td colspan='1' class='table-section-divider border-0'>*</td>
                                                                                <td colspan='1' class='table-section-divider border-0'>*</td>
                                                                                <td colspan='1' class='table-section-divider border-0'>*</td>
                                                                                <td colspan='1' class='table-section-divider border-0 $Q1_Class'></td>
                                                                                <td colspan='1' class='table-section-divider border-0 $Q2_Class'></td>
                                                                                <td colspan='1' class='table-section-divider border-0 $Q3_Class'></td>
                                                                                <td colspan='1' class='table-section-divider border-0 $Q4_Class'></td>
                                                                                <td colspan='1' class='table-section-divider border-0'>*</td>
                                                                            </tr>
                                        
                                                                            <!-- Educational Technology -->
                                                                            <tr class='border-0'>
                                                                                <th class='text-start text-decoration-underline border-0'>Educational Technology (ET)</th>
                                                                                <td colspan='1' class='table-section-divider border-0'>*</td>
                                                                                <td colspan='1' class='table-section-divider border-0'>*</td>
                                                                                <td colspan='1' class='table-section-divider border-0'>*</td>
                                                                                <td colspan='1' class='table-section-divider border-0 $Q1_Class'></td>
                                                                                <td colspan='1' class='table-section-divider border-0 $Q2_Class'></td>
                                                                                <td colspan='1' class='table-section-divider border-0 $Q3_Class'></td>
                                                                                <td colspan='1' class='table-section-divider border-0 $Q4_Class'></td>
                                                                                <td colspan='1' class='table-section-divider border-0'>*</td>
                                                                            </tr>
                                        
                                                                            <tr class='border-0'>
                                                                                <td style='font-size: 14px !important;'>ET01 - Instructional Technology Support Service</td>
                                                                                <td class='table-cell-divider border-0'></td>
                                                                                <td></td>
                                                                                <td></td>
                                                                                <td class='$Q1_Class'>".number_format($contracted_services["ET01"]["Q1"], 2)."</td>
                                                                                <td class='$Q2_Class'>".number_format($contracted_services["ET01"]["Q2"], 2)."</td>
                                                                                <td class='$Q3_Class'>".number_format($contracted_services["ET01"]["Q3"], 2)."</td>
                                                                                <td class='$Q4_Class'>".number_format($contracted_services["ET01"]["Q4"], 2)."</td>
                                                                                <td class='text-center'>".number_format($contracted_services["ET01"]["total_cost"], 2)."</td>
                                                                            </tr>
                                        
                                                                            <!-- spacer -->
                                                                            <tr class='border-0'>
                                                                                <td colspan='1' class='table-section-divider border-0'>*</td>
                                                                                <td colspan='1' class='table-section-divider border-0'>*</td>
                                                                                <td colspan='1' class='table-section-divider border-0'>*</td>
                                                                                <td colspan='1' class='table-section-divider border-0'>*</td>
                                                                                <td colspan='1' class='table-section-divider border-0 $Q1_Class'></td>
                                                                                <td colspan='1' class='table-section-divider border-0 $Q2_Class'></td>
                                                                                <td colspan='1' class='table-section-divider border-0 $Q3_Class'></td>
                                                                                <td colspan='1' class='table-section-divider border-0 $Q4_Class'></td>
                                                                                <td colspan='1' class='table-section-divider border-0'>*</td>
                                                                            </tr>
                                        
                                                                            <!-- Technology Support -->
                                                                            <tr class='border-0'>
                                                                                <th class='text-start text-decoration-underline border-0'>Technology Support (TS)</th>
                                                                                <td colspan='1' class='table-section-divider border-0'>*</td>
                                                                                <td colspan='1' class='table-section-divider border-0'>*</td>
                                                                                <td colspan='1' class='table-section-divider border-0'>*</td>
                                                                                <td colspan='1' class='table-section-divider border-0 $Q1_Class'></td>
                                                                                <td colspan='1' class='table-section-divider border-0 $Q2_Class'></td>
                                                                                <td colspan='1' class='table-section-divider border-0 $Q3_Class'></td>
                                                                                <td colspan='1' class='table-section-divider border-0 $Q4_Class'></td>
                                                                                <td colspan='1' class='table-section-divider border-0'>*</td>
                                                                            </tr>
                                        
                                                                            <tr class='border-0'>
                                                                                <td style='font-size: 14px !important;'>TS01 - Managed IT Services (Technology Support)</td>
                                                                                <td class='table-cell-divider border-0'></td>
                                                                                <td class='text-center'>Days</td>
                                                                                <td class='text-center'>".round($contracted_services["TS01"]["total_qty"], 2)."</td>
                                                                                <td class='$Q1_Class'>".number_format($contracted_services["TS01"]["Q1"], 2)."</td>
                                                                                <td class='$Q2_Class'>".number_format($contracted_services["TS01"]["Q2"], 2)."</td>
                                                                                <td class='$Q3_Class'>".number_format($contracted_services["TS01"]["Q3"], 2)."</td>
                                                                                <td class='$Q4_Class'>".number_format($contracted_services["TS01"]["Q4"], 2)."</td>
                                                                                <td class='text-center'>".number_format($contracted_services["TS01"]["total_cost"], 2)."</td>
                                                                            </tr>
                                        
                                                                            <!-- spacer -->
                                                                            <tr class='border-0'>
                                                                                <td colspan='1' class='table-section-divider border-0'>*</td>
                                                                                <td colspan='1' class='table-section-divider border-0'>*</td>
                                                                                <td colspan='1' class='table-section-divider border-0'>*</td>
                                                                                <td colspan='1' class='table-section-divider border-0'>*</td>
                                                                                <td colspan='1' class='table-section-divider border-0 $Q1_Class'></td>
                                                                                <td colspan='1' class='table-section-divider border-0 $Q2_Class'></td>
                                                                                <td colspan='1' class='table-section-divider border-0 $Q3_Class'></td>
                                                                                <td colspan='1' class='table-section-divider border-0 $Q4_Class'></td>
                                                                                <td colspan='1' class='table-section-divider border-0'>*</td>
                                                                            </tr>
                                        
                                                                            <!-- Business Services -->
                                                                            <tr class='border-0'>
                                                                                <th class='text-start text-decoration-underline border-0'>Business Services</th>
                                                                                <td colspan='1' class='table-section-divider border-0'>*</td>
                                                                                <td colspan='1' class='table-section-divider border-0'>*</td>
                                                                                <td colspan='1' class='table-section-divider border-0'>*</td>
                                                                                <td colspan='1' class='table-section-divider border-0 $Q1_Class'></td>
                                                                                <td colspan='1' class='table-section-divider border-0 $Q2_Class'></td>
                                                                                <td colspan='1' class='table-section-divider border-0 $Q3_Class'></td>
                                                                                <td colspan='1' class='table-section-divider border-0 $Q4_Class'></td>
                                                                                <td colspan='1' class='table-section-divider border-0'>*</td>
                                                                            </tr>
                                        
                                                                            <tr class='border-0'>
                                                                                <td style='font-size: 14px !important;'>SB01 - School Business Administration and Support</td>
                                                                                <td class='table-cell-divider border-0'></td>
                                                                                <td class='text-center'>Days</td>
                                                                                <td class='text-center'>".round($contracted_services["SB01"]["total_qty"], 2)."</td>
                                                                                <td class='$Q1_Class'>".number_format($contracted_services["SB01"]["Q1"], 2)."</td>
                                                                                <td class='$Q2_Class'>".number_format($contracted_services["SB01"]["Q2"], 2)."</td>
                                                                                <td class='$Q3_Class'>".number_format($contracted_services["SB01"]["Q3"], 2)."</td>
                                                                                <td class='$Q4_Class'>".number_format($contracted_services["SB01"]["Q4"], 2)."</td>
                                                                                <td class='text-center'>".number_format($contracted_services["SB01"]["total_cost"], 2)."</td>
                                                                            </tr>
                                        
                                                                            <!-- spacer -->
                                                                            <tr class='border-0'>
                                                                                <td colspan='1' class='table-section-divider border-0'>*</td>
                                                                                <td colspan='1' class='table-section-divider border-0'>*</td>
                                                                                <td colspan='1' class='table-section-divider border-0'>*</td>
                                                                                <td colspan='1' class='table-section-divider border-0'>*</td>
                                                                                <td colspan='1' class='table-section-divider border-0 $Q1_Class'></td>
                                                                                <td colspan='1' class='table-section-divider border-0 $Q2_Class'></td>
                                                                                <td colspan='1' class='table-section-divider border-0 $Q3_Class'></td>
                                                                                <td colspan='1' class='table-section-divider border-0 $Q4_Class'></td>
                                                                                <td colspan='1' class='table-section-divider border-0'>*</td>
                                                                            </tr>
                                        
                                                                            <!-- Other Services -->
                                                                            <tr class='border-0'>
                                                                                <th class='text-start text-decoration-underline border-0'>Other Services</th>
                                                                                <td colspan='1' class='table-section-divider border-0'>*</td>
                                                                                <td colspan='1' class='table-section-divider border-0'>*</td>
                                                                                <td colspan='1' class='table-section-divider border-0'>*</td>
                                                                                <td colspan='1' class='table-section-divider border-0 $Q1_Class'></td>
                                                                                <td colspan='1' class='table-section-divider border-0 $Q2_Class'></td>
                                                                                <td colspan='1' class='table-section-divider border-0 $Q3_Class'></td>
                                                                                <td colspan='1' class='table-section-divider border-0 $Q4_Class'></td>
                                                                                <td colspan='1' class='table-section-divider border-0'>*</td>
                                                                            </tr>
                                                                            <tr class='border-0'>
                                                                                <td>LS01 - Librarian Services</td>
                                                                                <td class='table-cell-divider border-0'></td>
                                                                                <td class='text-center'>Days</td>
                                                                                <td class='text-center'>".round($contracted_services["LS01"]["total_qty"], 2)."</td>
                                                                                <td class='$Q1_Class'>".number_format($contracted_services["LS01"]["Q1"], 2)."</td>
                                                                                <td class='$Q2_Class'>".number_format($contracted_services["LS01"]["Q2"], 2)."</td>
                                                                                <td class='$Q3_Class'>".number_format($contracted_services["LS01"]["Q3"], 2)."</td>
                                                                                <td class='$Q4_Class'>".number_format($contracted_services["LS01"]["Q4"], 2)."</td>
                                                                                <td class='text-center'>".number_format($contracted_services["LS01"]["total_cost"], 2)."</td>
                                                                            </tr>
                                                                            <tr class='border-0'>
                                                                                <td>".$contracted_services["OTHER1"]["description"]."</td>
                                                                                <td class='table-cell-divider border-0'></td>
                                                                                <td class='text-center'>".$contracted_services["OTHER1"]["unit_label"]."</td>
                                                                                <td class='text-center'>".round($contracted_services["SPOTHER2"]["total_qty"], 2)."</td>
                                                                                <td class='$Q1_Class'>".number_format($contracted_services["OTHER1"]["Q1"], 2)."</td>
                                                                                <td class='$Q2_Class'>".number_format($contracted_services["OTHER1"]["Q2"], 2)."</td>
                                                                                <td class='$Q3_Class'>".number_format($contracted_services["OTHER1"]["Q3"], 2)."</td>
                                                                                <td class='$Q4_Class'>".number_format($contracted_services["OTHER1"]["Q4"], 2)."</td>
                                                                                <td class='text-center'>".number_format($contracted_services["OTHER1"]["total_cost"], 2)."</td>
                                                                            </tr>
                                        
                                                                            <!-- spacer -->
                                                                            <tr class='border-0'>
                                                                                <td colspan='1' class='table-section-divider border-0'>*</td>
                                                                                <td colspan='1' class='table-section-divider border-0'>*</td>
                                                                                <td colspan='1' class='table-section-divider border-0'>*</td>
                                                                                <td colspan='1' class='table-section-divider border-0'>*</td>
                                                                                <td colspan='1' class='table-section-divider border-0'></td>
                                                                                <td colspan='1' class='table-section-divider border-0'></td>
                                                                                <td colspan='1' class='table-section-divider border-0'></td>
                                                                                <td colspan='1' class='table-section-divider border-0'></td>
                                                                                <td colspan='1' class='table-section-divider border-0'>*</td>
                                                                            </tr>
                                        
                                                                            <!-- comments -->
                                                                            <tr>
                                                                                <td colspan='9'>Comments:<br>$page1_comment</td>
                                                                            </tr>
                                        
                                                                            <!-- spacer -->
                                                                            <tr class='border-0'>
                                                                                <td colspan='1' class='table-section-divider border-0'>*</td>
                                                                                <td colspan='1' class='table-section-divider border-0'>*</td>
                                                                                <td colspan='1' class='table-section-divider border-0'>*</td>
                                                                                <td colspan='1' class='table-section-divider border-0'>*</td>
                                                                                <td colspan='1' class='table-section-divider border-0'></td>
                                                                                <td colspan='1' class='table-section-divider border-0'></td>
                                                                                <td colspan='1' class='table-section-divider border-0'></td>
                                                                                <td colspan='1' class='table-section-divider border-0'></td>
                                                                                <td colspan='1' class='table-section-divider border-0'>*</td>
                                                                            </tr>
                                        
                                                                            <!-- footer -->
                                                                            <tr class='border-0'>
                                                                                <th class='text-center border-0' colspan='9'>See Page 2 for Special Education & Alternative Education Contracted Services.</th>
                                                                            </tr>
                                                                            <tr class='border-0'><td colspan='9' class='table-section-divider border-0'>*</td></tr>
                                                                            <tr class='border-0'>
                                                                                <td class='text-center border-0' colspan='9'>If you need clarification or additional information, please contact us at (608) 745-5416.</td>
                                                                            </tr>
                                                                            <tr class='border-0'><td colspan='9' class='table-section-divider border-0'>*</td></tr>
                                                                            <tr class='border-0'>
                                                                                <th class='text-center border-0' colspan='9'>Page 1 of 2</th>
                                                                            </tr>
                                                                        </thead>
                                                                    </table>
                                                                </html>");

                                                                ///////////////////////////////////////////////////////////////////////////////////////////////
                                                                //
                                                                //  PDF PAGE 2
                                                                //
                                                                ///////////////////////////////////////////////////////////////////////////////////////////////
                                                                $pdf->addPage("<html>
                                                                    <!-- Custom Stylesheet -->
                                                                    <link href='$CONTRACT_STYLESHEET_PATH' rel='stylesheet'>
                                        
                                                                    <!-- Bootstrap Stylesheet -->
                                                                    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css' rel='stylesheet' integrity='sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC' crossorigin='anonymous'>
                                        
                                                                    <!-- Header -->
                                                                    <table class='border-0 w-100'>
                                                                        <thead class='border-0'>
                                                                            <!-- set column widths -->
                                                                            <tr class='border-0'>
                                                                                <th class='border-0' style='width: 40%;'></th>
                                                                                <th class='border-0' style='width: 60%;'></th>
                                                                            </tr>
                                        
                                                                            <tr class='border-0'>
                                                                                <td class='border-0'><img class='w-75 m-0 p-0' src='$CESA5_LOGO_PATH' alt='CESA 5 Logo'></td>
                                                                                <td class='border-0 text-center'>                
                                                                                    <h3 class='fw-bold'>
                                                                                        $period_name CONTRACTED SERVICES INVOICE
                                                                                        <br>
                                                                                        <br>
                                                                                        $customer_display_name School District
                                                                                    </h3>
                                                                                </td>
                                                                            </tr>
                                                                        </thead>
                                                                    </table>
                                                                    <!-- End Header -->
                                                                    
                                                                    <!-- Spacer -->
                                                                    <div class='table-section-divider'>*</div>
                                                                    
                                                                    <!-- Contract -->
                                                                    <table class='border-0 w-100'>
                                                                        <thead class='border-0'>
                                                                            <!-- set column widths -->
                                                                            <tr class='border-0'>
                                                                                <th class='border-0' style='width: 37.5%'></th>
                                                                                <th class='border-0' style='width: 1.5%'></th>
                                                                                <th class='border-0' style='width: 6%'></th>
                                                                                <th class='border-0' style='width: 6%'></th>
                                                                                <th class='border-0' style='width: 9.75%'></th>
                                                                                <th class='border-0' style='width: 9.75%'></th>
                                                                                <th class='border-0' style='width: 9.75%'></th>
                                                                                <th class='border-0' style='width: 9.75%'></th>
                                                                                <th class='border-0' style='width: 10%'></th>
                                                                            </tr>
                                        
                                                                            <!-- Header Row -->
                                                                            <tr class='border-0'>
                                                                                <th class='border-0'></th>
                                                                                <th class='border-0'></th>
                                                                                <th>Unit</th>
                                                                                <th>Qty</th>
                                                                                <th class='$Q1_Header_Class'>$Q1_Label_Break</th>
                                                                                <th class='$Q2_Header_Class'>$Q2_Label_Break</th>
                                                                                <th class='$Q3_Header_Class'>$Q3_Label_Break</th>
                                                                                <th class='$Q4_Header_Class'>$Q4_Label_Break</th>
                                                                                <th>Annual<br>Amount</th>
                                                                            </tr>
                                                                        </thead>
                                        
                                                                        <tbody class='border-0'>
                                                                            <!-- Special Education -->
                                                                            <tr class='border-0'>
                                                                                <th class='text-start text-decoration-underline border-0'>Special Education (SP)</th>
                                                                                <td colspan='1' class='table-section-divider border-0'>*</td>
                                                                                <td colspan='1' class='table-section-divider border-0'>*</td>
                                                                                <td colspan='1' class='table-section-divider border-0'>*</td>
                                                                                <td colspan='1' class='table-section-divider border-0 $Q1_Class'></td>
                                                                                <td colspan='1' class='table-section-divider border-0 $Q2_Class'></td>
                                                                                <td colspan='1' class='table-section-divider border-0 $Q3_Class'></td>
                                                                                <td colspan='1' class='table-section-divider border-0 $Q4_Class'></td>
                                                                                <td colspan='1' class='table-section-divider border-0'>*</td>
                                                                            </tr>
                                        
                                                                            <!-- SP section 1 -->
                                                                            <tr class='border-0'>
                                                                                <td>SP01 - Assistive Technology Specialist</td>
                                                                                <td class='table-cell-divider border-0'></td>
                                                                                <td></td>
                                                                                <td></td>
                                                                                <td class='$Q1_Class'>".number_format($contracted_services["SP01"]["Q1"], 2)."</td>
                                                                                <td class='$Q2_Class'>".number_format($contracted_services["SP01"]["Q2"], 2)."</td>
                                                                                <td class='$Q3_Class'>".number_format($contracted_services["SP01"]["Q3"], 2)."</td>
                                                                                <td class='$Q4_Class'>".number_format($contracted_services["SP01"]["Q4"], 2)."</td>
                                                                                <td class='text-center'>".number_format($contracted_services["SP01"]["total_cost"], 2)."</td>
                                                                            </tr>
                                                                            <tr class='border-0'>
                                                                                <td style='font-size: 14px !important;'>SP02 - Special Ed. Instructional Materials Center</td>
                                                                                <td class='table-cell-divider border-0'></td>
                                                                                <td></td>
                                                                                <td></td>
                                                                                <td class='$Q1_Class'>".number_format($contracted_services["SP02"]["Q1"], 2)."</td>
                                                                                <td class='$Q2_Class'>".number_format($contracted_services["SP02"]["Q2"], 2)."</td>
                                                                                <td class='$Q3_Class'>".number_format($contracted_services["SP02"]["Q3"], 2)."</td>
                                                                                <td class='$Q4_Class'>".number_format($contracted_services["SP02"]["Q4"], 2)."</td>
                                                                                <td class='text-center'>".number_format($contracted_services["SP02"]["total_cost"], 2)."</td>
                                                                            </tr>
                                                                            <tr class='border-0'>
                                                                                <td>SP03 - Audiologist</td>
                                                                                <td class='table-cell-divider border-0'></td>
                                                                                <td class='text-center'>UOS</td>
                                                                                <td class='text-center'>".round($contracted_services["SP03"]["total_qty"], 2)."</td>
                                                                                <td class='$Q1_Class'>".number_format($contracted_services["SP03"]["Q1"], 2)."</td>
                                                                                <td class='$Q2_Class'>".number_format($contracted_services["SP03"]["Q2"], 2)."</td>
                                                                                <td class='$Q3_Class'>".number_format($contracted_services["SP03"]["Q3"], 2)."</td>
                                                                                <td class='$Q4_Class'>".number_format($contracted_services["SP03"]["Q4"], 2)."</td>
                                                                                <td class='text-center'>".number_format($contracted_services["SP03"]["total_cost"], 2)."</td>
                                                                            </tr>
                                                                            <tr class='border-0'>
                                                                                <td>SP04 - Virtual Speech Services</td>
                                                                                <td class='table-cell-divider border-0'></td>
                                                                                <td></td>
                                                                                <td></td>
                                                                                <td class='$Q1_Class'>".number_format($contracted_services["SP04"]["Q1"], 2)."</td>
                                                                                <td class='$Q2_Class'>".number_format($contracted_services["SP04"]["Q2"], 2)."</td>
                                                                                <td class='$Q3_Class'>".number_format($contracted_services["SP04"]["Q3"], 2)."</td>
                                                                                <td class='$Q4_Class'>".number_format($contracted_services["SP04"]["Q4"], 2)."</td>
                                                                                <td class='text-center'>".number_format($contracted_services["SP04"]["total_cost"], 2)."</td>
                                                                            </tr>
                                                                            <tr class='border-0'>
                                                                                <td style='font-size: 14px !important;'>SP05 - Intensive Services Classroom</td>
                                                                                <td class='table-cell-divider border-0'></td>
                                                                                <td class='text-center'>FTE</td>
                                                                                <td class='text-center'>".round($contracted_services["SP05"]["total_qty"], 2)."</td>
                                                                                <td class='$Q1_Class'>".number_format($contracted_services["SP05"]["Q1"], 2)."</td>
                                                                                <td class='$Q2_Class'>".number_format($contracted_services["SP05"]["Q2"], 2)."</td>
                                                                                <td class='$Q3_Class'>".number_format($contracted_services["SP05"]["Q3"], 2)."</td>
                                                                                <td class='$Q4_Class'>".number_format($contracted_services["SP05"]["Q4"], 2)."</td>
                                                                                <td class='text-center'>".number_format($contracted_services["SP05"]["total_cost"], 2)."</td>
                                                                            </tr>
                                                                            
                                                                            <!-- spacer -->
                                                                            <tr class='border-0'>
                                                                                <td colspan='1' class='table-section-divider border-0'>*</td>
                                                                                <td colspan='1' class='table-section-divider border-0'>*</td>
                                                                                <td colspan='1' class='table-section-divider border-0'>*</td>
                                                                                <td colspan='1' class='table-section-divider border-0'>*</td>
                                                                                <td colspan='1' class='table-section-divider border-0 $Q1_Class'></td>
                                                                                <td colspan='1' class='table-section-divider border-0 $Q2_Class'></td>
                                                                                <td colspan='1' class='table-section-divider border-0 $Q3_Class'></td>
                                                                                <td colspan='1' class='table-section-divider border-0 $Q4_Class'></td>
                                                                                <td colspan='1' class='table-section-divider border-0'>*</td>
                                                                            </tr>
                                        
                                                                            <!-- SP section 2 -->
                                                                            <tr class='border-0'>
                                                                                <td>SP06 - Early Childhood Classroom</td>
                                                                                <td class='table-cell-divider border-0'></td>
                                                                                <td class='text-center'>Days</td>
                                                                                <td class='text-center'>".round($contracted_services["SP06"]["total_qty"], 2)."</td>
                                                                                <td class='$Q1_Class'>".number_format($contracted_services["SP06"]["Q1"], 2)."</td>
                                                                                <td class='$Q2_Class'>".number_format($contracted_services["SP06"]["Q2"], 2)."</td>
                                                                                <td class='$Q3_Class'>".number_format($contracted_services["SP06"]["Q3"], 2)."</td>
                                                                                <td class='$Q4_Class'>".number_format($contracted_services["SP06"]["Q4"], 2)."</td>
                                                                                <td class='text-center'>".number_format($contracted_services["SP06"]["total_cost"], 2)."</td>
                                                                            </tr>
                                                                            <tr class='border-0'>
                                                                                <td>SP07 - Educational Sign Language Interpreter</td>
                                                                                <td class='table-cell-divider border-0'></td>
                                                                                <td class='text-center'>FTE</td>
                                                                                <td class='text-center'>".round($contracted_services["SP07"]["total_qty"], 2)."</td>
                                                                                <td class='$Q1_Class'>".number_format($contracted_services["SP07"]["Q1"], 2)."</td>
                                                                                <td class='$Q2_Class'>".number_format($contracted_services["SP07"]["Q2"], 2)."</td>
                                                                                <td class='$Q3_Class'>".number_format($contracted_services["SP07"]["Q3"], 2)."</td>
                                                                                <td class='$Q4_Class'>".number_format($contracted_services["SP07"]["Q4"], 2)."</td>
                                                                                <td class='text-center'>".number_format($contracted_services["SP07"]["total_cost"], 2)."</td>
                                                                            </tr>
                                                                            <tr class='border-0'>
                                                                                <td style='font-size: 14px !important;'>SP08 - Classroom for the Deaf & Hard of Hearing</td>
                                                                                <td class='table-cell-divider border-0'></td>
                                                                                <td></td>
                                                                                <td></td>
                                                                                <td class='$Q1_Class'>".number_format($contracted_services["SP08"]["Q1"], 2)."</td>
                                                                                <td class='$Q2_Class'>".number_format($contracted_services["SP08"]["Q2"], 2)."</td>
                                                                                <td class='$Q3_Class'>".number_format($contracted_services["SP08"]["Q3"], 2)."</td>
                                                                                <td class='$Q4_Class'>".number_format($contracted_services["SP08"]["Q4"], 2)."</td>
                                                                                <td class='text-center'>".number_format($contracted_services["SP08"]["total_cost"], 2)."</td>
                                                                            </tr>
                                                                            <tr class='border-0'>
                                                                                <td>SP09 - Teacher for the Deaf & Hard of Hearing</td>
                                                                                <td class='table-cell-divider border-0'></td>
                                                                                <td class='text-center'>UOS</td>
                                                                                <td class='text-center'>".round($contracted_services["SP09"]["total_qty"], 2)."</td>
                                                                                <td class='$Q1_Class'>".number_format($contracted_services["SP09"]["Q1"], 2)."</td>
                                                                                <td class='$Q2_Class'>".number_format($contracted_services["SP09"]["Q2"], 2)."</td>
                                                                                <td class='$Q3_Class'>".number_format($contracted_services["SP09"]["Q3"], 2)."</td>
                                                                                <td class='$Q4_Class'>".number_format($contracted_services["SP09"]["Q4"], 2)."</td>
                                                                                <td class='text-center'>".number_format($contracted_services["SP09"]["total_cost"], 2)."</td>
                                                                            </tr>
                                                                            <tr class='border-0'>
                                                                                <td>SP10 - Occupational Therapy</td>
                                                                                <td class='table-cell-divider border-0'></td>
                                                                                <td class='text-center'>UOS</td>
                                                                                <td class='text-center'>".round($contracted_services["SP10"]["total_qty"], 2)."</td>
                                                                                <td class='$Q1_Class'>".number_format($contracted_services["SP10"]["Q1"], 2)."</td>
                                                                                <td class='$Q2_Class'>".number_format($contracted_services["SP10"]["Q2"], 2)."</td>
                                                                                <td class='$Q3_Class'>".number_format($contracted_services["SP10"]["Q3"], 2)."</td>
                                                                                <td class='$Q4_Class'>".number_format($contracted_services["SP10"]["Q4"], 2)."</td>
                                                                                <td class='text-center'>".number_format($contracted_services["SP10"]["total_cost"], 2)."</td>
                                                                            </tr>
                                                                            
                                                                            <!-- spacer -->
                                                                            <tr class='border-0'>
                                                                                <td colspan='1' class='table-section-divider border-0'>*</td>
                                                                                <td colspan='1' class='table-section-divider border-0'>*</td>
                                                                                <td colspan='1' class='table-section-divider border-0'>*</td>
                                                                                <td colspan='1' class='table-section-divider border-0'>*</td>
                                                                                <td colspan='1' class='table-section-divider border-0 $Q1_Class'></td>
                                                                                <td colspan='1' class='table-section-divider border-0 $Q2_Class'></td>
                                                                                <td colspan='1' class='table-section-divider border-0 $Q3_Class'></td>
                                                                                <td colspan='1' class='table-section-divider border-0 $Q4_Class'></td>
                                                                                <td colspan='1' class='table-section-divider border-0'>*</td>
                                                                            </tr>
                                        
                                                                            <!-- SP section 3 -->
                                                                            <tr class='border-0'>
                                                                                <td>SP11 - Orientation & Mobility</td>
                                                                                <td class='table-cell-divider border-0'></td>
                                                                                <td class='text-center'>UOS</td>
                                                                                <td class='text-center'>".round($contracted_services["SP11"]["total_qty"], 2)."</td>
                                                                                <td class='$Q1_Class'>".number_format($contracted_services["SP11"]["Q1"], 2)."</td>
                                                                                <td class='$Q2_Class'>".number_format($contracted_services["SP11"]["Q2"], 2)."</td>
                                                                                <td class='$Q3_Class'>".number_format($contracted_services["SP11"]["Q3"], 2)."</td>
                                                                                <td class='$Q4_Class'>".number_format($contracted_services["SP11"]["Q4"], 2)."</td>
                                                                                <td class='text-center'>".number_format($contracted_services["SP11"]["total_cost"], 2)."</td>
                                                                            </tr>
                                                                            <tr class='border-0'>
                                                                                <td>SP12 - Physical Therapy</td>
                                                                                <td class='table-cell-divider border-0'></td>
                                                                                <td class='text-center'>UOS</td>
                                                                                <td class='text-center'>".round($contracted_services["SP12"]["total_qty"], 2)."</td>
                                                                                <td class='$Q1_Class'>".number_format($contracted_services["SP12"]["Q1"], 2)."</td>
                                                                                <td class='$Q2_Class'>".number_format($contracted_services["SP12"]["Q2"], 2)."</td>
                                                                                <td class='$Q3_Class'>".number_format($contracted_services["SP12"]["Q3"], 2)."</td>
                                                                                <td class='$Q4_Class'>".number_format($contracted_services["SP12"]["Q4"], 2)."</td>
                                                                                <td class='text-center'>".number_format($contracted_services["SP12"]["total_cost"], 2)."</td>
                                                                            </tr>
                                                                            <tr class='border-0'>
                                                                                <td>SP13 - School Psychology Services</td>
                                                                                <td class='table-cell-divider border-0'></td>
                                                                                <td class='text-center'>Days</td>
                                                                                <td class='text-center'>".round($contracted_services["SP13"]["total_qty"], 2)."</td>
                                                                                <td class='$Q1_Class'>".number_format($contracted_services["SP13"]["Q1"], 2)."</td>
                                                                                <td class='$Q2_Class'>".number_format($contracted_services["SP13"]["Q2"], 2)."</td>
                                                                                <td class='$Q3_Class'>".number_format($contracted_services["SP13"]["Q3"], 2)."</td>
                                                                                <td class='$Q4_Class'>".number_format($contracted_services["SP13"]["Q4"], 2)."</td>
                                                                                <td class='text-center'>".number_format($contracted_services["SP13"]["total_cost"], 2)."</td>
                                                                            </tr>
                                                                            <tr class='border-0'>
                                                                                <td>SP14 - SEEDS4Schools Software Support</td>
                                                                                <td class='table-cell-divider border-0'></td>
                                                                                <td></td>
                                                                                <td></td>
                                                                                <td class='$Q1_Class'>".number_format($contracted_services["SP14"]["Q1"], 2)."</td>
                                                                                <td class='$Q2_Class'>".number_format($contracted_services["SP14"]["Q2"], 2)."</td>
                                                                                <td class='$Q3_Class'>".number_format($contracted_services["SP14"]["Q3"], 2)."</td>
                                                                                <td class='$Q4_Class'>".number_format($contracted_services["SP14"]["Q4"], 2)."</td>
                                                                                <td class='text-center'>".number_format($contracted_services["SP14"]["total_cost"], 2)."</td>
                                                                            </tr>
                                                                            <tr class='border-0'>
                                                                                <td>SP15a - Special Education Leadership</td>
                                                                                <td class='table-cell-divider border-0'></td>
                                                                                <td></td>
                                                                                <td class='text-center'>".round($contracted_services["SP15A"]["total_qty"], 2)."</td>
                                                                                <td class='$Q1_Class'>".number_format($contracted_services["SP15A"]["Q1"], 2)."</td>
                                                                                <td class='$Q2_Class'>".number_format($contracted_services["SP15A"]["Q2"], 2)."</td>
                                                                                <td class='$Q3_Class'>".number_format($contracted_services["SP15A"]["Q3"], 2)."</td>
                                                                                <td class='$Q4_Class'>".number_format($contracted_services["SP15A"]["Q4"], 2)."</td>
                                                                                <td class='text-center'>".number_format($contracted_services["SP15A"]["total_cost"], 2)."</td>
                                                                            </tr>
                                                                            <tr class='border-0'>
                                                                                <td>SP15b - Special Ed. Fiscal Management</td>
                                                                                <td class='table-cell-divider border-0'></td>
                                                                                <td></td>
                                                                                <td></td>
                                                                                <td class='$Q1_Class'>".number_format($contracted_services["SP15B"]["Q1"], 2)."</td>
                                                                                <td class='$Q2_Class'>".number_format($contracted_services["SP15B"]["Q2"], 2)."</td>
                                                                                <td class='$Q3_Class'>".number_format($contracted_services["SP15B"]["Q3"], 2)."</td>
                                                                                <td class='$Q4_Class'>".number_format($contracted_services["SP15B"]["Q4"], 2)."</td>
                                                                                <td class='text-center'>".number_format($contracted_services["SP15B"]["total_cost"], 2)."</td>
                                                                            </tr>
                                                                            <tr class='border-0' style='height: inherit !important;'>
                                                                                <td>SP15c - Special Ed. Leadership Mentoring</td>
                                                                                <td class='table-cell-divider border-0'></td>
                                                                                <td></td>
                                                                                <td></td>
                                                                                <td class='$Q1_Class'>".number_format($contracted_services["SP15C"]["Q1"], 2)."</td>
                                                                                <td class='$Q2_Class'>".number_format($contracted_services["SP15C"]["Q2"], 2)."</td>
                                                                                <td class='$Q3_Class'>".number_format($contracted_services["SP15C"]["Q3"], 2)."</td>
                                                                                <td class='$Q4_Class'>".number_format($contracted_services["SP15C"]["Q4"], 2)."</td>
                                                                                <td class='text-center'>".number_format($contracted_services["SP15C"]["total_cost"], 2)."</td>
                                                                            </tr>

                                                                            <!-- spacer -->
                                                                            <tr class='border-0'>
                                                                                <td colspan='1' class='table-section-divider border-0'>*</td>
                                                                                <td colspan='1' class='table-section-divider border-0'>*</td>
                                                                                <td colspan='1' class='table-section-divider border-0'>*</td>
                                                                                <td colspan='1' class='table-section-divider border-0'>*</td>
                                                                                <td colspan='1' class='table-section-divider border-0 $Q1_Class'></td>
                                                                                <td colspan='1' class='table-section-divider border-0 $Q2_Class'></td>
                                                                                <td colspan='1' class='table-section-divider border-0 $Q3_Class'></td>
                                                                                <td colspan='1' class='table-section-divider border-0 $Q4_Class'></td>
                                                                                <td colspan='1' class='table-section-divider border-0'>*</td>
                                                                            </tr>
                                        
                                                                            <!-- SP section 4 -->
                                                                            <tr class='border-0'>
                                                                                <td>SP16 - Speech and Language Therapy</td>
                                                                                <td class='table-cell-divider border-0'></td>
                                                                                <td class='text-center'>Days</td>
                                                                                <td class='text-center'>".round($contracted_services["SP16"]["total_qty"], 2)."</td>
                                                                                <td class='$Q1_Class'>".number_format($contracted_services["SP16"]["Q1"], 2)."</td>
                                                                                <td class='$Q2_Class'>".number_format($contracted_services["SP16"]["Q2"], 2)."</td>
                                                                                <td class='$Q3_Class'>".number_format($contracted_services["SP16"]["Q3"], 2)."</td>
                                                                                <td class='$Q4_Class'>".number_format($contracted_services["SP16"]["Q4"], 2)."</td>
                                                                                <td class='text-center'>".number_format($contracted_services["SP16"]["total_cost"], 2)."</td>
                                                                            </tr>
                                                                            <tr class='border-0'>
                                                                                <td>SP17 - Virtual Secretary</td>
                                                                                <td class='table-cell-divider border-0'></td>
                                                                                <td class='text-center'>Days</td>
                                                                                <td class='text-center'>".round($contracted_services["SP17"]["total_qty"], 2)."</td>
                                                                                <td class='$Q1_Class'>".number_format($contracted_services["SP17"]["Q1"], 2)."</td>
                                                                                <td class='$Q2_Class'>".number_format($contracted_services["SP17"]["Q2"], 2)."</td>
                                                                                <td class='$Q3_Class'>".number_format($contracted_services["SP17"]["Q3"], 2)."</td>
                                                                                <td class='$Q4_Class'>".number_format($contracted_services["SP17"]["Q4"], 2)."</td>
                                                                                <td class='text-center'>".number_format($contracted_services["SP17"]["total_cost"], 2)."</td>
                                                                            </tr>
                                                                            <tr class='border-0'>
                                                                                <td>SP18 - Classroom of the Visually Impaired</td>
                                                                                <td class='table-cell-divider border-0'></td>
                                                                                <td class='text-center'>UOS</td>
                                                                                <td class='text-center'>".round($contracted_services["SP18"]["total_qty"], 2)."</td>
                                                                                <td class='$Q1_Class'>".number_format($contracted_services["SP18"]["Q1"], 2)."</td>
                                                                                <td class='$Q2_Class'>".number_format($contracted_services["SP18"]["Q2"], 2)."</td>
                                                                                <td class='$Q3_Class'>".number_format($contracted_services["SP18"]["Q3"], 2)."</td>
                                                                                <td class='$Q4_Class'>".number_format($contracted_services["SP18"]["Q4"], 2)."</td>
                                                                                <td class='text-center'>".number_format($contracted_services["SP18"]["total_cost"], 2)."</td>
                                                                            </tr>
                                                                            <tr class='border-0'>
                                                                                <td>SP19 - Teacher of the Visually Impaired </td>
                                                                                <td class='table-cell-divider border-0'></td>
                                                                                <td class='text-center'>UOS</td>
                                                                                <td class='text-center'>".round($contracted_services["SP19"]["total_qty"], 2)."</td>
                                                                                <td class='$Q1_Class'>".number_format($contracted_services["SP19"]["Q1"], 2)."</td>
                                                                                <td class='$Q2_Class'>".number_format($contracted_services["SP19"]["Q2"], 2)."</td>
                                                                                <td class='$Q3_Class'>".number_format($contracted_services["SP19"]["Q3"], 2)."</td>
                                                                                <td class='$Q4_Class'>".number_format($contracted_services["SP19"]["Q4"], 2)."</td>
                                                                                <td class='text-center'>".number_format($contracted_services["SP19"]["total_cost"], 2)."</td>
                                                                            </tr>
                                        
                                                                            <!-- spacer -->
                                                                            <tr class='border-0'>
                                                                                <td colspan='1' class='table-section-divider border-0'>*</td>
                                                                                <td colspan='1' class='table-section-divider border-0'>*</td>
                                                                                <td colspan='1' class='table-section-divider border-0'>*</td>
                                                                                <td colspan='1' class='table-section-divider border-0'>*</td>
                                                                                <td colspan='1' class='table-section-divider border-0 $Q1_Class'></td>
                                                                                <td colspan='1' class='table-section-divider border-0 $Q2_Class'></td>
                                                                                <td colspan='1' class='table-section-divider border-0 $Q3_Class'></td>
                                                                                <td colspan='1' class='table-section-divider border-0 $Q4_Class'></td>
                                                                                <td colspan='1' class='table-section-divider border-0'>*</td>
                                                                            </tr>
                                        
                                                                            <!-- Alternative Education -->
                                                                            <tr class='border-0'>
                                                                                <th class='text-start text-decoration-underline border-0'>Alternative Education (AE)</th>
                                                                                <td colspan='1' class='table-section-divider border-0'>*</td>
                                                                                <td colspan='1' class='table-section-divider border-0'>*</td>
                                                                                <td colspan='1' class='table-section-divider border-0'>*</td>
                                                                                <td colspan='1' class='table-section-divider border-0 $Q1_Class'></td>
                                                                                <td colspan='1' class='table-section-divider border-0 $Q2_Class'></td>
                                                                                <td colspan='1' class='table-section-divider border-0 $Q3_Class'></td>
                                                                                <td colspan='1' class='table-section-divider border-0 $Q4_Class'></td>
                                                                                <td colspan='1' class='table-section-divider border-0'>*</td>
                                                                            </tr>
                                                                            <tr class='border-0'>
                                                                                <td>AE01 - REACH Academy for Elementary</td>
                                                                                <td class='table-cell-divider border-0'></td>
                                                                                <td class='text-center'>FTE</td>
                                                                                <td class='text-center'>".round($contracted_services["AE01"]["total_qty"], 2)."</td>
                                                                                <td class='$Q1_Class'>".number_format($contracted_services["AE01"]["Q1"], 2)."</td>
                                                                                <td class='$Q2_Class'>".number_format($contracted_services["AE01"]["Q2"], 2)."</td>
                                                                                <td class='$Q3_Class'>".number_format($contracted_services["AE01"]["Q3"], 2)."</td>
                                                                                <td class='$Q4_Class'>".number_format($contracted_services["AE01"]["Q4"], 2)."</td>
                                                                                <td class='text-center'>".number_format($contracted_services["AE01"]["total_cost"], 2)."</td>
                                                                            </tr>
                                                                            <tr class='border-0'>
                                                                                <td style='font-size: 13px !important;'>AE02 - Columbia Marquette Adolescent Needs School</td>
                                                                                <td class='table-cell-divider border-0'></td>
                                                                                <td class='text-center'>FTE</td>
                                                                                <td class='text-center'>".round($contracted_services["AE02"]["total_qty"], 2)."</td>
                                                                                <td class='$Q1_Class'>".number_format($contracted_services["AE02"]["Q1"], 2)."</td>
                                                                                <td class='$Q2_Class'>".number_format($contracted_services["AE02"]["Q2"], 2)."</td>
                                                                                <td class='$Q3_Class'>".number_format($contracted_services["AE02"]["Q3"], 2)."</td>
                                                                                <td class='$Q4_Class'>".number_format($contracted_services["AE02"]["Q4"], 2)."</td>
                                                                                <td class='text-center'>".number_format($contracted_services["AE02"]["total_cost"], 2)."</td>
                                                                            </tr>
                                                                            <tr class='border-0'>
                                                                                <td>AE03 - Juneau County Adolescent Program</td>
                                                                                <td class='table-cell-divider border-0'></td>
                                                                                <td class='text-center'>FTE</td>
                                                                                <td class='text-center'>".round($contracted_services["AE03"]["total_qty"], 2)."</td>
                                                                                <td class='$Q1_Class'>".number_format($contracted_services["AE03"]["Q1"], 2)."</td>
                                                                                <td class='$Q2_Class'>".number_format($contracted_services["AE03"]["Q2"], 2)."</td>
                                                                                <td class='$Q3_Class'>".number_format($contracted_services["AE03"]["Q3"], 2)."</td>
                                                                                <td class='$Q4_Class'>".number_format($contracted_services["AE03"]["Q4"], 2)."</td>
                                                                                <td class='text-center'>".number_format($contracted_services["AE03"]["total_cost"], 2)."</td>
                                                                            </tr>
                                                                            <tr class='border-0'>
                                                                                <td>AE04 - Sauk County Adolescent Needs School</td>
                                                                                <td class='table-cell-divider border-0'></td>
                                                                                <td class='text-center'>FTE</td>
                                                                                <td class='text-center'>".round($contracted_services["AE04"]["total_qty"], 2)."</td>
                                                                                <td class='$Q1_Class'>".number_format($contracted_services["AE04"]["Q1"], 2)."</td>
                                                                                <td class='$Q2_Class'>".number_format($contracted_services["AE04"]["Q2"], 2)."</td>
                                                                                <td class='$Q3_Class'>".number_format($contracted_services["AE04"]["Q3"], 2)."</td>
                                                                                <td class='$Q4_Class'>".number_format($contracted_services["AE04"]["Q4"], 2)."</td>
                                                                                <td class='text-center'>".number_format($contracted_services["AE04"]["total_cost"], 2)."</td>
                                                                            </tr>
                                                                            <tr class='border-0'>
                                                                                <td>AE05 - Wood County Alternative School</td>
                                                                                <td class='table-cell-divider border-0'></td>
                                                                                <td class='text-center'>FTE</td>
                                                                                <td class='text-center'>".round($contracted_services["AE05"]["total_qty"], 2)."</td>
                                                                                <td class='$Q1_Class'>".number_format($contracted_services["AE05"]["Q1"], 2)."</td>
                                                                                <td class='$Q2_Class'>".number_format($contracted_services["AE05"]["Q2"], 2)."</td>
                                                                                <td class='$Q3_Class'>".number_format($contracted_services["AE05"]["Q3"], 2)."</td>
                                                                                <td class='$Q4_Class'>".number_format($contracted_services["AE05"]["Q4"], 2)."</td>
                                                                                <td class='text-center'>".number_format($contracted_services["AE05"]["total_cost"], 2)."</td>
                                                                            </tr>
                                                                            <tr class='border-0'>
                                                                                <td>AE06 - Waupaca County Alternative Program</td>
                                                                                <td class='table-cell-divider border-0'></td>
                                                                                <td class='text-center'>FTE</td>
                                                                                <td class='text-center'>".round($contracted_services["AE06"]["total_qty"], 2)."</td>
                                                                                <td class='$Q1_Class'>".number_format($contracted_services["AE06"]["Q1"], 2)."</td>
                                                                                <td class='$Q2_Class'>".number_format($contracted_services["AE06"]["Q2"], 2)."</td>
                                                                                <td class='$Q3_Class'>".number_format($contracted_services["AE06"]["Q3"], 2)."</td>
                                                                                <td class='$Q4_Class'>".number_format($contracted_services["AE06"]["Q4"], 2)."</td>
                                                                                <td class='text-center'>".number_format($contracted_services["AE06"]["total_cost"], 2)."</td>
                                                                            </tr>
                                                                            <tr class='border-0'>
                                                                                <td style='font-size: 14px !important;'>AE07 - Waupaca County Alt. Program - Elementary</td>
                                                                                <td class='table-cell-divider border-0'></td>
                                                                                <td class='text-center'>FTE</td>
                                                                                <td class='text-center'>".round($contracted_services["AE07"]["total_qty"], 2)."</td>
                                                                                <td class='$Q1_Class'>".number_format($contracted_services["AE07"]["Q1"], 2)."</td>
                                                                                <td class='$Q2_Class'>".number_format($contracted_services["AE07"]["Q2"], 2)."</td>
                                                                                <td class='$Q3_Class'>".number_format($contracted_services["AE07"]["Q3"], 2)."</td>
                                                                                <td class='$Q4_Class'>".number_format($contracted_services["AE07"]["Q4"], 2)."</td>
                                                                                <td class='text-center'>".number_format($contracted_services["AE07"]["total_cost"], 2)."</td>
                                                                            </tr>
                                                                            <tr class='border-0'>
                                                                                <td>AE08 - Project SEARCH at Kalahari</td>
                                                                                <td class='table-cell-divider border-0'></td>
                                                                                <td class='text-center'>FTE</td>
                                                                                <td class='text-center'>".round($contracted_services["AE08"]["total_qty"], 2)."</td>
                                                                                <td class='$Q1_Class'>".number_format($contracted_services["AE08"]["Q1"], 2)."</td>
                                                                                <td class='$Q2_Class'>".number_format($contracted_services["AE08"]["Q2"], 2)."</td>
                                                                                <td class='$Q3_Class'>".number_format($contracted_services["AE08"]["Q3"], 2)."</td>
                                                                                <td class='$Q4_Class'>".number_format($contracted_services["AE08"]["Q4"], 2)."</td>
                                                                                <td class='text-center'>".number_format($contracted_services["AE08"]["total_cost"], 2)."</td>
                                                                            </tr>
                                        
                                                                            <!-- spacer -->
                                                                            <tr class='border-0'>
                                                                                <td colspan='1' class='table-section-divider border-0'>*</td>
                                                                                <td colspan='1' class='table-section-divider border-0'>*</td>
                                                                                <td colspan='1' class='table-section-divider border-0'>*</td>
                                                                                <td colspan='1' class='table-section-divider border-0'>*</td>
                                                                                <td colspan='1' class='table-section-divider border-0 $Q1_Class'></td>
                                                                                <td colspan='1' class='table-section-divider border-0 $Q2_Class'></td>
                                                                                <td colspan='1' class='table-section-divider border-0 $Q3_Class'></td>
                                                                                <td colspan='1' class='table-section-divider border-0 $Q4_Class'></td>
                                                                                <td colspan='1' class='table-section-divider border-0'>*</td>
                                                                            </tr>
                                        
                                                                            <!-- Other Services -->
                                                                            <tr class='border-0'>
                                                                                <th class='text-start text-decoration-underline border-0'>Other Services</th>
                                                                                <td colspan='1' class='table-section-divider border-0'>*</td>
                                                                                <td colspan='1' class='table-section-divider border-0'>*</td>
                                                                                <td colspan='1' class='table-section-divider border-0'>*</td>
                                                                                <td colspan='1' class='table-section-divider border-0 $Q1_Class'></td>
                                                                                <td colspan='1' class='table-section-divider border-0 $Q2_Class'></td>
                                                                                <td colspan='1' class='table-section-divider border-0 $Q3_Class'></td>
                                                                                <td colspan='1' class='table-section-divider border-0 $Q4_Class'></td>
                                                                                <td colspan='1' class='table-section-divider border-0'>*</td>
                                                                            </tr>
                                                                            <tr class='border-0'>
                                                                                <td>SN01 - School Nursing Services</td>
                                                                                <td class='table-cell-divider border-0'></td>
                                                                                <td class='text-center'>Days</td>
                                                                                <td class='text-center'>".round($contracted_services["SN01"]["total_qty"], 2)."</td>
                                                                                <td class='$Q1_Class'>".number_format($contracted_services["SN01"]["Q1"], 2)."</td>
                                                                                <td class='$Q2_Class'>".number_format($contracted_services["SN01"]["Q2"], 2)."</td>
                                                                                <td class='$Q3_Class'>".number_format($contracted_services["SN01"]["Q3"], 2)."</td>
                                                                                <td class='$Q4_Class'>".number_format($contracted_services["SN01"]["Q4"], 2)."</td>
                                                                                <td class='text-center'>".number_format($contracted_services["SN01"]["total_cost"], 2)."</td>
                                                                            </tr>
                                                                            <tr class='border-0'>
                                                                                <td>".$contracted_services["SPOTHER1"]["description"]."</td>
                                                                                <td class='table-cell-divider border-0'></td>
                                                                                <td class='text-center'>".$contracted_services["SPOTHER1"]["unit_label"]."</td>
                                                                                <td class='text-center'>".round($contracted_services["SPOTHER1"]["total_qty"], 2)."</td>
                                                                                <td class='$Q1_Class'>".number_format($contracted_services["SPOTHER1"]["Q1"], 2)."</td>
                                                                                <td class='$Q2_Class'>".number_format($contracted_services["SPOTHER1"]["Q2"], 2)."</td>
                                                                                <td class='$Q3_Class'>".number_format($contracted_services["SPOTHER1"]["Q3"], 2)."</td>
                                                                                <td class='$Q4_Class'>".number_format($contracted_services["SPOTHER1"]["Q4"], 2)."</td>
                                                                                <td class='text-center'>".number_format($contracted_services["SPOTHER1"]["total_cost"], 2)."</td>
                                                                            </tr>
                                                                            <tr class='border-0'>
                                                                                <td>".$contracted_services["SPOTHER2"]["description"]."</td>
                                                                                <td class='table-cell-divider border-0'></td>
                                                                                <td class='text-center'>".$contracted_services["SPOTHER2"]["unit_label"]."</td>
                                                                                <td class='text-center'>".round($contracted_services["SPOTHER2"]["total_qty"], 2)."</td>
                                                                                <td class='$Q1_Class'>".number_format($contracted_services["SPOTHER2"]["Q1"], 2)."</td>
                                                                                <td class='$Q2_Class'>".number_format($contracted_services["SPOTHER2"]["Q2"], 2)."</td>
                                                                                <td class='$Q3_Class'>".number_format($contracted_services["SPOTHER2"]["Q3"], 2)."</td>
                                                                                <td class='$Q4_Class'>".number_format($contracted_services["SPOTHER2"]["Q4"], 2)."</td>
                                                                                <td class='text-center'>".number_format($contracted_services["SPOTHER2"]["total_cost"], 2)."</td>
                                                                            </tr>
                                                                            <tr class='border-0'>
                                                                                <td>".$contracted_services["SPOTHER3"]["description"]."</td>
                                                                                <td class='table-cell-divider border-0'></td>
                                                                                <td class='text-center'>".$contracted_services["SPOTHER3"]["unit_label"]."</td>
                                                                                <td class='text-center'>".round($contracted_services["SPOTHER3"]["total_qty"], 2)."</td>
                                                                                <td class='$Q1_Class'>".number_format($contracted_services["SPOTHER3"]["Q1"], 2)."</td>
                                                                                <td class='$Q2_Class'>".number_format($contracted_services["SPOTHER3"]["Q2"], 2)."</td>
                                                                                <td class='$Q3_Class'>".number_format($contracted_services["SPOTHER3"]["Q3"], 2)."</td>
                                                                                <td class='$Q4_Class'>".number_format($contracted_services["SPOTHER3"]["Q4"], 2)."</td>
                                                                                <td class='text-center'>".number_format($contracted_services["SPOTHER3"]["total_cost"], 2)."</td>
                                                                            </tr>
                                                                            
                                                                            <!-- spacer -->
                                                                            <tr class='border-0'>
                                                                                <td colspan='1' class='table-section-divider border-0'>*</td>
                                                                                <td colspan='1' class='table-section-divider border-0'>*</td>
                                                                                <td colspan='1' class='table-section-divider border-0'>*</td>
                                                                                <td colspan='1' class='table-section-divider border-0'>*</td>
                                                                                <td colspan='1' class='table-section-divider border-0 $Q1_Class'></td>
                                                                                <td colspan='1' class='table-section-divider border-0 $Q2_Class'></td>
                                                                                <td colspan='1' class='table-section-divider border-0 $Q3_Class'></td>
                                                                                <td colspan='1' class='table-section-divider border-0 $Q4_Class'></td>
                                                                                <td colspan='1' class='table-section-divider border-0'>*</td>
                                                                            </tr>
                                                                            
                                                                            <!-- totals -->
                                                                            <tr class='border-0'>
                                                                                <th class='text-center'>TOTALS</th>
                                                                                <th style='border: 1px white !important;'></th>
                                                                                <th style='border: 1px white !important;'></th>
                                                                                <th style='border: 1px white !important;'></th>
                                                                                <th class='$Q1_Class' style='font-size: 15px !important;'>$Q1_Total</th>
                                                                                <th class='$Q2_Class' style='font-size: 15px !important;'>$Q2_Total</th>
                                                                                <th class='$Q3_Class' style='font-size: 15px !important;'>$Q3_Total</th>
                                                                                <th class='$Q4_Class' style='font-size: 15px !important;'>$Q4_Total</th>
                                                                                <th class='text-center' style='font-size: 15px !important;'>$Total</th>
                                                                            </tr>
                                                                            <tr class='border-0'>
                                                                                <th class='border-0'></th>
                                                                                <td class='border-0' colspan='3'></td>
                                                                                <th class='border-0 text-center $Q1_Class p-0' style='font-size: 12px !important;'>$Q1_Due_Label</th>
                                                                                <th class='border-0 text-center $Q2_Class p-0' style='font-size: 12px !important;'>$Q2_Due_Label</th>
                                                                                <th class='border-0 text-center $Q3_Class p-0' style='font-size: 12px !important;'>$Q3_Due_Label</th>
                                                                                <th class='border-0 text-center $Q4_Class p-0' style='font-size: 12px !important;'>$Q4_Due_Label</th>
                                                                                <td class='border-0'></th>
                                                                            </tr>
                                                                        </tbody>
                                                                    </table>

                                                                    <!-- Footer -->
                                                                    <table class='border-0 w-100'>
                                                                        <thead class='border-0'>
                                                                            <!-- set column widths -->
                                                                            <tr class='border-0'>
                                                                                <th class='border-0' style='width: 40% !important;'></th>
                                                                                <th class='border-0' style='width: 20% !important;'></th>
                                                                                <th class='border-0' style='width: 20% !important;'></th>
                                                                                <th class='border-0' style='width: 20% !important;'></th>
                                                                            </tr>
                                                                        </thead>

                                                                        <tbody class='border-0'>
                                                                            <!-- spacer -->
                                                                            <tr class='border-0'><td class='table-section-divider border-0' colspan='4'>*</td></tr>

                                                                            <tr class='border-0 p-0 m-0'>
                                                                                <td class='border-0 p-0' colspan='2'></td>
                                                                                <td class='p-0 text-center m-0'><b class='m-0'>Invoice No.</b></td>
                                                                                <td class='p-0 text-center m-0'><b class='m-0'>$Invoice_Number</b></td>
                                                                            </tr>
                                                                            <tr class='border-0'>
                                                                                <td class='border-0 p-0' colspan='2'></td>
                                                                                <td class='p-0 text-center'><b>Invoice Date</b></td>
                                                                                <td class='p-0 text-center'><b>$Invoice_Date</b></td>
                                                                            </tr>

                                                                            <!-- spacer -->
                                                                            <tr class='border-0'><td class='table-section-divider border-0' colspan='4'>*</td></tr>

                                                                            <tr class='border-0'>
                                                                                <td class='border-0 text-center' colspan='4' style='font-size: 14px !important;'>* Amounts listed for prior quarters indicate payments received by CESA 5. Amounts in future quarters are projected future invoice amounts.</td>
                                                                            </tr>

                                                                            <!-- spacer -->
                                                                            <tr class='border-0'><td class='table-section-divider border-0' colspan='4'>*</td></tr>

                                                                            <tr class='border-0'>
                                                                                <th class='border-0 text-center' colspan='4'>Page 2 of 2</th>
                                                                            </tr>
                                                                        </tbody>
                                                                    </table>
                                                                </html>");

                                                                // check to see if we have created a directory to store quarterly invoices for the selected period
                                                                if (is_dir("local_data/quarter_contracts/$period/$quarter")) // directory exists 
                                                                {

                                                                }
                                                                else // directory does not exists; create new directory
                                                                {
                                                                    // create the directoy where owner and group can read, write, and execute to the directory
                                                                    mkdir("local_data/quarter_contracts/$period/$quarter", 0770, true);
                                                                }

                                                                // attempt to save the PDF to a local directory
                                                                if (!$pdf->saveAs("local_data/quarter_contracts/$period/$quarter/$customer_id.pdf"))
                                                                {
                                                                    $error = $pdf->getError();
                                                                    $errors++;
                                                                }
                                                                else 
                                                                {
                                                                    // log successful PDF save
                                                                    echo "Saved PDF for $customer_name locally.<br>";
                                                                    
                                                                    // increment success counter
                                                                    $total_successes++;
                                                                            
                                                                    $result = false;
                                                                    if ($uploadToDrive == 1)
                                                                    {
                                                                        // check to see if Google Drive folder is valid
                                                                        if (in_array($customer_folder, $folders_found))
                                                                        {
                                                                            // Attempt to upload the contract to Google Drive
                                                                            $resource = new Google\Service\Drive\DriveFile([
                                                                                "name" => "$customer_filename.pdf",
                                                                                "parents" => [$customer_folder]
                                                                            ]);
                                                    
                                                                            $result = $service->files->create($resource, [
                                                                                "data" => file_get_contents("local_data/quarter_contracts/$period/$quarter/$customer_id.pdf"),
                                                                                "mimeType" => "application/pdf",
                                                                                "uploadType" => "multipart"
                                                                            ]);
                                                    
                                                                            if ($result) { echo "Quarterly invoice successfully uploaded for $customer_name.<br>"; }
                                                                            else { echo "Failed to upload quarterly invoice for $customer_name!<br>"; }
                                                                        }
                                                                        else { echo "Failed to upload quarterly invoice for $customer_name, the folder with GID $customer_folder was not found!<br>"; }
                                                                    }

                                                                    // log quarterly invoice creation
                                                                    $message = "Successfully created the Q$quarter invoice for $customer_name for the period $period_name. ";
                                                                    if ($uploadToDrive == 1) 
                                                                    { 
                                                                        if ($result) { $message .= "Uploaded the invoice to the Google Drive folder with GID $customer_folder. "; }
                                                                        else { $message .= "Failed to upload the invoice to the Google Drive folder with GID $customer_folder. "; }
                                                                    }
                                                                    $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                                                                    mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                                                                    mysqli_stmt_execute($log);
                                                                }
                                                            }
                                                        }
                                                        else { echo "Failed to create the quarterly invoice for the customer with ID of $customer_id. This customer does not exist!<br>"; }
                                                    }
                                                    else { echo "Failed to create the quarterly invoice for the customer with ID of $customer_id. An unexpected error has occurred! Please try again later.<br>"; }
                                                }

                                                // log invoice creation
                                                $message = "Successfully created $total_successes quarterly invoices for $period_name. ";
                                                if ($errors > 0) { $message .= "Failed to create $errors quarterly invoices for $period_name. "; }
                                                $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                                                mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                                                mysqli_stmt_execute($log);
                                            }
                                            else { echo "Failed to create the quarterly invoices. Please return to the \"Create Contracts\" page and try again.<br>"; }
                                        }
                                        else { echo "Failed to create the quarterly invoices. The quarter selected was invalid. Please try again later.<br>"; }
                                    }
                                    else { echo "Failed to create the quarterly invoices. The period selected was invalid. Please try again later.<br>"; }
                                }
                                else { echo "Failed to create the quarterly invoices. You must provide all the required fields.<br>"; } // error - did not receive all required parameters
                            ?>
                            </div>
                            <div class="col-2"></div>
                        </div>

                        <div class="row justify-content-center text-center my-3">
                            <div class="col-2"><button class="btn btn-primary w-100" onclick="goToCreateContracts();">Return To Create Contracts</button></div>
                            <div class="col-2"><button class="btn btn-primary w-100" onclick="goToViewContracts();">Go To View Contracts</button></div>
                        </div>
                        <script>
                            function goToCreateContracts() { window.location.href = "contracts_create.php"; }
                            function goToViewContracts() { window.location.href = "customer_files.php"; }
                        </script>
                    <?php
                }
            }

            // disconnect from the database
            mysqli_close($conn);
        }
    }
    else { header("Location: " . $client->createAuthUrl()); } // authentication code not set; redirect to Google authentication page
?>