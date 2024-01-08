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
    $redirect_uri = GOOGLE_CASELOAD_BILLING_REDIRECT_URI;

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

    ///////////////////////////////////////////////////////////////////////////////////////////////
    //
    //  Handle new form POST request
    //  - Since we'll have to authorize via Google, we'll be redirected back to this page,
    //    because of this, we'll have to store the POST data in the $_SESSION array
    //
    ///////////////////////////////////////////////////////////////////////////////////////////////

    // check to see if we have any POST parameters
    if (isset($_POST["export-filename"])) { $POST_filename = $_POST["export-filename"]; } else { $POST_filename = null; }
    if (isset($_POST["export-period"])) { $POST_period = $_POST["export-period"]; } else { $POST_period = null; }
    if (isset($_POST["export-quarter"])) { $POST_quarter = $_POST["export-quarter"]; } else { $POST_quarter = null; }
    if (isset($_POST["export-customers"])) { $POST_customers = $_POST["export-customers"]; } else { $POST_customers = null; }
    if (isset($_POST["export-upload"])) { $POST_upload = $_POST["export-upload"]; } else { $POST_upload = 0; }

    // if all required fields were filled out, store them in the $_SESSION array 
    if ($POST_filename != null && $POST_period != null && $POST_quarter != null && $POST_customers != null)
    {
        $caseload_billing = [];
        $caseload_billing["filename"] = $POST_filename;
        $caseload_billing["period"] = $POST_period;
        $caseload_billing["quarter"] = $POST_quarter;
        $caseload_billing["customers"] = $POST_customers;
        $caseload_billing["upload"] = $POST_upload;
        $_SESSION["caseload_billing"] = $caseload_billing;
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

            // verify the user is an admin
            if ($_SESSION["role"] == 1)
            {
                // store the fixed file paths locally
                $STYLESHEET_PATH = QUARTERLY_BILLING_STYLESHEET_PATH;
                $CESA5_LOGO_PATH = CESA5_LOGO_PATH;

                // if we already have stored newly POSTed quarterly reports data
                if (isset($_SESSION["caseload_billing"]) && is_array($_SESSION["caseload_billing"]))
                {
                    $SESSION_caseload_billing = $_SESSION["caseload_billing"];
                    if (isset($SESSION_caseload_billing["filename"]) && $SESSION_caseload_billing["filename"] <> "") { $filename = $SESSION_caseload_billing["filename"]; } else { $filename = null; }
                    if (isset($SESSION_caseload_billing["period"]) && $SESSION_caseload_billing["period"] <> "") { $period = $SESSION_caseload_billing["period"]; } else { $period = null; }
                    if (isset($SESSION_caseload_billing["quarter"]) && $SESSION_caseload_billing["quarter"] <> "") { $quarter = $SESSION_caseload_billing["quarter"]; } else { $quarter = null; }
                    if (isset($SESSION_caseload_billing["customers"]) && $SESSION_caseload_billing["customers"] <> "") { $customers = $SESSION_caseload_billing["customers"]; } else { $customers = null; }
                    if (isset($SESSION_caseload_billing["upload"]) && $SESSION_caseload_billing["upload"] <> "") { $uploadToDrive = $SESSION_caseload_billing["upload"]; } else { $uploadToDrive = 0; }

                    ?>
                        <div class="row text-center">
                            <div class="col-2"></div>
                            <div class="col-8"><h1 class="upload-status-header">Create Quarterly Billing Reports Status</h1></div>
                            <div class="col-2"></div>
                        </div>

                        <div class="row text-center">
                            <div class="col-2"></div>
                            <div class="col-8 upload-status-report">
                            <?php
                                // verify that we have all the required parameters
                                if ($filename != null && $period != null && $quarter != null && $customers != null)
                                {
                                    // verify the period exists
                                    if (verifyPeriod($conn, $period))
                                    {
                                        // verify the quarter is valid
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
                                                $client_service = new Google\Service\Drive($client);

                                                // initialize counter variables for successes and errors
                                                $total_successes = $internal_total_successes = $errors = $internal_errors = 0;

                                                // store all Google user's folders
                                                // we must store these because if a folder does not exist; the folder would be uploaded to their drive home directory - we don't want this to happen
                                                // we will only upload the file to Google Drive if the assigned folder exists
                                                // only attempt to get all folders if we are uploading contracts
                                                $folders_found = []; // initialize empty array to store folders found in Google Drive
                                                if ($uploadToDrive == 1)
                                                {
                                                    // scan the google drive directory
                                                    $folders_found = scanGoogleDrive($client_service, $GLOBAL_SETTINGS["caseloads_billing_gid"]);
                                                }

                                                // connect to the database
                                                $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

                                                // get the period name
                                                $period_name = getPeriodName($conn, $period);

                                                // get the quarter's label
                                                $quarterLabel = "";
                                                $getQuarterLabel = mysqli_prepare($conn, "SELECT * FROM quarters WHERE period_id=? AND quarter=?");
                                                mysqli_stmt_bind_param($getQuarterLabel, "ii", $period, $quarter);
                                                if (mysqli_stmt_execute($getQuarterLabel))
                                                {
                                                    $getQuarterLabelResult = mysqli_stmt_get_result($getQuarterLabel);
                                                    $quarterResult = mysqli_fetch_array($getQuarterLabelResult);
                                                    $quarterLabel = $quarterResult["label"];
                                                }

                                                // for all customers we are creating a quarterly billing report for, attempt to get their name and Google Drive folder ID (GID)
                                                for ($c = 0; $c < count($customers); $c++)
                                                {
                                                    // store the customer ID locally
                                                    $customer_id = $customers[$c];

                                                    // query the database to get additional customer data
                                                    $getCustomerData = mysqli_prepare($conn, "SELECT name, caseload_billing_folder_id FROM customers WHERE id=?");
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
                                                            if (isset($customer_data["caseload_billing_folder_id"])) { $customer_folder = $customer_data["caseload_billing_folder_id"]; } else { $customer_folder = null; }

                                                            // create the filename for the customer's quarterly report
                                                            $customer_filename = str_replace("{QUARTER}", "Q".$quarter, str_replace("{PERIOD}", $period_name, str_replace("{CUSTOMER}", $customer_name, $filename)));

                                                            // create the customer display name
                                                            $customer_display_name = $customer_name;

                                                            // build the datestamp
                                                            $datestamp = date("Y-m-d_H-i-s");

                                                            // initialize the PDF we are creating
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
                                                                'enable-local-file-access',
                                                                'disable-smart-shrinking',
                                                                'orientation' => 'landscape',
                                                            ));

                                                            // initialize the PDF we are creating for the internal report
                                                            $internal_pdf = new Pdf();
                                                            $internal_pdf->setOptions(array(
                                                                'ignoreWarnings' => true,
                                                                'commandOptions' => array(
                                                                    'useExec' => true,      // Can help on Windows systems
                                                                    'procEnv' => array(
                                                                        // Check the output of 'locale -a' on your system to find supported languages
                                                                        'LANG' => 'en_US.utf-8',
                                                                    ),
                                                                ),
                                                                'enable-local-file-access',
                                                                'disable-smart-shrinking',
                                                                'orientation' => 'landscape',
                                                            ));

                                                            ///////////////////////////////////////////////////////////////////////////////
                                                            //
                                                            //  COVER PAGE (EXTERNAL)
                                                            //
                                                            ///////////////////////////////////////////////////////////////////////////////
                                                            $pdf->addPage("<html>
                                                                <!-- Custom Stylesheet -->
                                                                <link href='$STYLESHEET_PATH' rel='stylesheet'>

                                                                <!-- Bootstrap Stylesheet -->
                                                                <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css' rel='stylesheet' integrity='sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC' crossorigin='anonymous'>
                                                                <script src='https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js' integrity='sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM' crossorigin='anonymous'></script>

                                                                <body>
                                                                    <div class='container w-100 h-100'>
                                                                        <div class='box'>
                                                                            <img class='w-25 my-2' src='$CESA5_LOGO_PATH' alt='CESA 5 Logo'>
                                                                            <h2 class='my-2'><b>Quarterly Invoice Billing Details Report</b></h2>
                                                                            <h1 class='my-2'><b>".$customer_name."</b></h1>
                                                                            <h2 class='my-2'><b>".$period_name." ".printNumber($quarter)." Quarter</b></h2>
                                                                            <p class='my-2' style='font-size: 16px;'>
                                                                                This report contains the names of students associated with various special education services provided to your district by CESA 5.<br>
                                                                                The information is current as of the printing of this report. Any adjustments to units will be made on your subsequent invoices.
                                                                            </p>
                                                                            
                                                                            <p class='my-2' style='font-size: 16px;'>
                                                                                If there are any questions, please contact our business office at (608) 745-5416<br>
                                                                                or our special education office at (608) 745-5440.
                                                                            </p>

                                                                            <p class='my-2' style='font-size: 16px;'>
                                                                                <b>KEY: </b>UOS = Units of Service
                                                                            </p>
                                                                        </div>
                                                                    </div>
                                                                </body>
                                                            </html>");

                                                            ///////////////////////////////////////////////////////////////////////////////
                                                            //
                                                            //  COVER PAGE (INTERNAL)
                                                            //
                                                            ///////////////////////////////////////////////////////////////////////////////
                                                            $internal_pdf->addPage("<html>
                                                                <!-- Custom Stylesheet -->
                                                                <link href='$STYLESHEET_PATH' rel='stylesheet'>

                                                                <!-- Bootstrap Stylesheet -->
                                                                <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css' rel='stylesheet' integrity='sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC' crossorigin='anonymous'>
                                                                <script src='https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js' integrity='sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM' crossorigin='anonymous'></script>

                                                                <body>
                                                                    <div class='container w-100 h-100'>
                                                                        <div class='box'>
                                                                            <img class='w-25 my-2' src='$CESA5_LOGO_PATH' alt='CESA 5 Logo'>
                                                                            <h2 class='my-2'><b>Quarterly Invoice Billing Details Report (Internal Use Only)</b></h2>
                                                                            <h1 class='my-2'><b>".$customer_name."</b></h1>
                                                                            <h2 class='my-2'><b>".$period_name." ".printNumber($quarter)." Quarter</b></h2>
                                                                            <p class='my-2' style='font-size: 16px;'>
                                                                                This report contains the names of students associated with various special education services provided to your district by CESA 5.<br>
                                                                                The information is current as of the printing of this report. Any adjustments to units will be made on your subsequent invoices.
                                                                            </p>
                                                                            
                                                                            <p class='my-2' style='font-size: 16px;'>
                                                                                If there are any questions, please contact our business office at (608) 745-5416<br>
                                                                                or our special education office at (608) 745-5440.
                                                                            </p>

                                                                            <p class='my-2' style='font-size: 16px;'>
                                                                                <b>KEY: </b>UOS = Units of Service
                                                                            </p>
                                                                        </div>
                                                                    </div>
                                                                </body>
                                                            </html>");

                                                            // get a list of all caseload services that the district uses
                                                            $getServices = mysqli_prepare($conn, "SELECT DISTINCT cc.id, cc.name, cc.service_id AS uos_service_id FROM caseload_categories cc
                                                                                                    JOIN caseloads cl ON cc.id=cl.category_id
                                                                                                    JOIN cases c ON cl.id=c.caseload_id
                                                                                                    WHERE c.period_id=? AND ((c.district_attending=? AND c.bill_to=2) OR (c.residency=? AND c.bill_to=1)) AND cc.id!=9
                                                                                                    ORDER BY cc.name ASC");
                                                            mysqli_stmt_bind_param($getServices, "iii", $period, $customer_id, $customer_id);
                                                            if (mysqli_stmt_execute($getServices))
                                                            {
                                                                $getServicesResults = mysqli_stmt_get_result($getServices);
                                                                if (mysqli_num_rows($getServicesResults) > 0)
                                                                {
                                                                    while ($service = mysqli_fetch_array($getServicesResults))
                                                                    {
                                                                        // store service details locally
                                                                        $category_id = $service["id"];
                                                                        $category_name = $service["name"];

                                                                        // get category settings
                                                                        $category_settings = getCaseloadCategorySettings($conn, $category_id);

                                                                        // initialize the strings that will store page
                                                                        $table_data = $internal_table_data = "";
                                                                        $totals_header = "";

                                                                        // get the data to be printed
                                                                        ///////////////////////////////////////////////////////////////////////////////////
                                                                        // 
                                                                        //  classroom-based caseload
                                                                        //
                                                                        ///////////////////////////////////////////////////////////////////////////////////
                                                                        if ($category_settings["is_classroom"] == 1)
                                                                        {
                                                                            // build the table header (EXTERNAL)
                                                                            $table_data .= "<thead style='border-left: 0px solid #ffffff; border-right: 0px solid #ffffff; border-top: 0px solid #ffffff; border-bottom: 2px solid #000000;'>
                                                                                <th class='text-center' style='width: 25%;'>Student</th>
                                                                                <th class='text-center' style='width: 25%;'>Location</th>
                                                                                <th class='text-center' style='width: 25%;'>Projected Annual FTE</th>
                                                                                <th class='text-center' style='width: 25%;'>Projected Annual Cost</th>
                                                                            </thead>";

                                                                            // build the table header (INTERNAL)
                                                                            $internal_table_data .= "<thead style='border-left: 0px solid #ffffff; border-right: 0px solid #ffffff; border-top: 0px solid #ffffff; border-bottom: 2px solid #000000;'>
                                                                                <th class='text-center' style='width: 25%;'>Student</th>
                                                                                <th class='text-center' style='width: 7.5%;'>Grade</th>
                                                                                <th class='text-center' style='width: 25%;'>Location</th>
                                                                                <th class='text-center' style='width: 15%;'>Projected Annual Days</th>
                                                                                <th class='text-center' style='width: 12.5%;'>Projected FTE</th>
                                                                                <th class='text-center' style='width: 15%;'>Projected Annual Cost</th>
                                                                            </thead>";


                                                                            // initialize variable to store total days sum and cost and total student count
                                                                            $total_days = 0;
                                                                            $total_FTE = 0;
                                                                            $total_cost = 0;
                                                                            $student_count = 0;

                                                                            // build the table body
                                                                            $table_data .= "<tbody class='border-0'>";
                                                                            $internal_table_data .= "<tbody class='border-0'>";

                                                                            ///////////////////////////////////////////////////////////////
                                                                            //
                                                                            //  EXTERNAL
                                                                            //
                                                                            ///////////////////////////////////////////////////////////////
                                                                            // get all cases for the customer where the student is attending the district and being billed
                                                                            $getCasesByDistrict = mysqli_prepare($conn, "SELECT c.* FROM cases c
                                                                                                                        JOIN caseloads cl ON c.caseload_id=cl.id
                                                                                                                        JOIN caseload_categories cc ON cl.category_id=cc.id
                                                                                                                        JOIN caseload_students cs ON c.student_id=cs.id
                                                                                                                        WHERE c.period_id=? AND ((c.district_attending=? AND c.bill_to=2) OR (c.residency=? AND c.bill_to=1)) AND cc.id=?
                                                                                                                        ORDER BY cs.lname ASC, cs.fname ASC");
                                                                            mysqli_stmt_bind_param($getCasesByDistrict, "iiii", $period, $customer_id, $customer_id, $category_id);
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
                                                                                        $grade = $case["grade_level"];
                                                                                        $caseload_id = $case["caseload_id"];
                                                                                        $classroom_id = $case["classroom_id"];
                                                                                        $case_days = $case["membership_days"];

                                                                                        // get the service ID based on the classroom the student is in
                                                                                        $service_id = $label = null;
                                                                                        $getClassroomDetails = mysqli_prepare($conn, "SELECT name, label, service_id FROM caseload_classrooms WHERE id=? AND category_id=? ORDER BY name ASC");
                                                                                        mysqli_stmt_bind_param($getClassroomDetails, "ii", $classroom_id, $category_id);
                                                                                        if (mysqli_stmt_execute($getClassroomDetails))
                                                                                        {
                                                                                            $getClassroomDetailsResult = mysqli_stmt_get_result($getClassroomDetails);
                                                                                            if (mysqli_num_rows($getClassroomDetailsResult) > 0)
                                                                                            {
                                                                                                // store classroom details
                                                                                                $classroomDetails = mysqli_fetch_array($getClassroomDetailsResult);
                                                                                                $label = $classroomDetails["label"];
                                                                                                $service_id = $classroomDetails["service_id"];
                                                                                            }
                                                                                        }

                                                                                        // build the classroom name
                                                                                        $classroom_name = getCaseloadClassroomName($conn, $classroom_id);
                                                                                        if ($label != null && $label <> "") { $classroom_name = trim($label); }

                                                                                        // calculate the FTE - round to nearest whole quarter // TODO - in future, allow custom FTE
                                                                                        $case_fte = (floor(($case_days / 180) * 4) / 4);

                                                                                        // initialize estimated annual cost for the student
                                                                                        $case_cost = 0;

                                                                                        // attempt to get details of the service
                                                                                        $service = getServiceDetails($conn, $service_id);
                                                                                        if (is_array($service)) // service exists; continue
                                                                                        {                
                                                                                            // store service details locally
                                                                                            $service_name = $service["name"];
                                                                                            $service_cost_type = $service["cost_type"];
                                                                                            $service_round_costs = $service["round_costs"];
                                                                                            $service_project_code = $service["project_code"];

                                                                                            // attempt to get details of the customer
                                                                                            $customer = getCustomerDetails($conn, $customer_id);
                                                                                            if (is_array($customer)) // customer exists; continue
                                                                                            {
                                                                                                // store customer details locally
                                                                                                $customer_name = $customer["name"];
                                                                                                $customer_members = $customer["members"];

                                                                                                // get the estimated cost of the service
                                                                                                $case_cost = getInvoiceCost($conn, $service_id, $customer_id, $period, $service_cost_type, $service_round_costs, $case_fte);
                                                                                            }
                                                                                        }
                                                                                        
                                                                                        // get the student's name
                                                                                        $student_name = getStudentDisplayName($conn, $student_id);

                                                                                        // only add student to report if units is greater than 0
                                                                                        if ($case_days > 0)
                                                                                        {
                                                                                            // build the table row (EXTERNAL)
                                                                                            $table_data .= "<tr class='border-0'>
                                                                                                <td class='text-center border-0'>".$student_name."</td>
                                                                                                <td class='text-center border-0'>".$classroom_name."</td>
                                                                                                <td class='text-center border-0'>".number_format($case_fte, 2)."</td>
                                                                                                <td class='text-end border-0'>$".number_format($case_cost, 2)."</td>
                                                                                            </tr>";
                                                                                        }

                                                                                        // add days to sum
                                                                                        $total_days += $case_days;

                                                                                        // add FTE to sum
                                                                                        $total_FTE += $case_fte;

                                                                                        // add cost to sum
                                                                                        $total_cost += $case_cost;

                                                                                        // increment student count
                                                                                        $student_count++;
                                                                                    }
                                                                                }
                                                                            }

                                                                            ///////////////////////////////////////////////////////////////
                                                                            //
                                                                            //  INTERNAL
                                                                            //
                                                                            ///////////////////////////////////////////////////////////////
                                                                            // get all classrooms for the category
                                                                            $getClassrooms = mysqli_prepare($conn, "SELECT id, name, label, service_id FROM caseload_classrooms WHERE category_id=? ORDER BY name ASC, label ASC");
                                                                            mysqli_stmt_bind_param($getClassrooms, "i", $category_id);
                                                                            if (mysqli_stmt_execute($getClassrooms))
                                                                            {
                                                                                $getClassroomsResults = mysqli_stmt_get_result($getClassrooms);
                                                                                if (mysqli_num_rows($getClassroomsResults) > 0)
                                                                                {
                                                                                    while ($classroom = mysqli_fetch_array($getClassroomsResults))
                                                                                    {
                                                                                        // store classroom details locally
                                                                                        $classroom_id = $classroom["id"];
                                                                                        $classroom_name = $classroom["name"];
                                                                                        $classroom_label = $classroom["label"];
                                                                                        $service_id = $classroom["service_id"];

                                                                                        // initialize classroom subtotals
                                                                                        $classroom_subtotal_cost = 0;
                                                                                        $classroom_subtotal_days = 0;
                                                                                        $classroom_subtotal_students = 0;
                                                                                        $classroom_subtotal_FTE = 0;

                                                                                        // get all cases for the customer where the student is attending the district and being billed
                                                                                        $getCasesByDistrict = mysqli_prepare($conn, "SELECT c.* FROM cases c
                                                                                                                                    JOIN caseloads cl ON c.caseload_id=cl.id
                                                                                                                                    JOIN caseload_categories cc ON cl.category_id=cc.id
                                                                                                                                    JOIN caseload_students cs ON c.student_id=cs.id
                                                                                                                                    WHERE c.period_id=? AND ((c.district_attending=? AND c.bill_to=2) OR (c.residency=? AND c.bill_to=1)) AND cc.id=? AND c.classroom_id=?
                                                                                                                                    ORDER BY cs.lname ASC, cs.fname ASC");
                                                                                        mysqli_stmt_bind_param($getCasesByDistrict, "iiiii", $period, $customer_id, $customer_id, $category_id, $classroom_id);
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
                                                                                                    $grade = $case["grade_level"];
                                                                                                    $caseload_id = $case["caseload_id"];
                                                                                                    $case_days = $case["membership_days"];

                                                                                                    // build the classroom name
                                                                                                    if ($classroom_label != null && trim($classroom_label) <> "") { $classroom_name = trim($classroom_label); }

                                                                                                    // calculate the FTE - round to nearest whole quarter // TODO - in future, allow custom FTE
                                                                                                    $case_fte = (floor(($case_days / 180) * 4) / 4);

                                                                                                    // initialize estimated annual cost for the student
                                                                                                    $case_cost = 0;

                                                                                                    // attempt to get details of the service
                                                                                                    $service = getServiceDetails($conn, $service_id);
                                                                                                    if (is_array($service)) // service exists; continue
                                                                                                    {                
                                                                                                        // store service details locally
                                                                                                        $service_name = $service["name"];
                                                                                                        $service_cost_type = $service["cost_type"];
                                                                                                        $service_round_costs = $service["round_costs"];
                                                                                                        $service_project_code = $service["project_code"];

                                                                                                        // attempt to get details of the customer
                                                                                                        $customer = getCustomerDetails($conn, $customer_id);
                                                                                                        if (is_array($customer)) // customer exists; continue
                                                                                                        {
                                                                                                            // store customer details locally
                                                                                                            $customer_name = $customer["name"];
                                                                                                            $customer_members = $customer["members"];

                                                                                                            // get the estimated cost of the service
                                                                                                            $case_cost = getInvoiceCost($conn, $service_id, $customer_id, $period, $service_cost_type, $service_round_costs, $case_fte);
                                                                                                        }
                                                                                                    }
                                                                                                    
                                                                                                    // get the student's name
                                                                                                    $student_name = getStudentDisplayName($conn, $student_id);

                                                                                                    // only add student to report if units is greater than 0
                                                                                                    if ($case_days > 0)
                                                                                                    {
                                                                                                        // build the table row (INTERNAL)
                                                                                                        $internal_table_data .= "<tr class='border-0'>
                                                                                                            <td class='text-center border-0'>".$student_name."</td>
                                                                                                            <td class='text-center border-0'>".printGradeLevel($grade)."</td>
                                                                                                            <td class='text-center border-0'>".$classroom_name."</td>
                                                                                                            <td class='text-center border-0'>".number_format($case_days)."</td>
                                                                                                            <td class='text-center border-0'>".number_format($case_fte, 2)."</td>
                                                                                                            <td class='text-end border-0'>$".number_format($case_cost, 2)."</td>
                                                                                                        </tr>";
                                                                                                    }

                                                                                                    // add days to sum
                                                                                                    $classroom_subtotal_days += $case_days;

                                                                                                    // add FTE to sum
                                                                                                    $classroom_subtotal_FTE += $case_fte;

                                                                                                    // add cost to sum
                                                                                                    $classroom_subtotal_cost += $case_cost;

                                                                                                    // increment student count
                                                                                                    $classroom_subtotal_students++;
                                                                                                }
                                                                                            }
                                                                                        }

                                                                                        // print subtotal only if students were placed in a classroom
                                                                                        if ($classroom_subtotal_students > 0)
                                                                                        {
                                                                                            // build classroom subtotal
                                                                                            $internal_table_data .= "<tr class='border-0'>
                                                                                                <td class='text-center border-0'></td>
                                                                                                <td class='text-center border-0'></td>
                                                                                                <td class='text-center border-0'><i><b>".$classroom_name."</b></i></td>
                                                                                                <td class='text-center border-0'><i><b>".number_format($classroom_subtotal_days)."</b></i></td>
                                                                                                <td class='text-center border-0'><i><b>".number_format($classroom_subtotal_FTE)."</b></i></td>
                                                                                                <td class='text-end border-0'><i><b>$".number_format($classroom_subtotal_cost, 2)."</b></i></td>
                                                                                            </tr>";
                                                                                        }
                                                                                    }
                                                                                }
                                                                            }

                                                                            // close the table body
                                                                            $table_data .= "</tbody>";
                                                                            $internal_table_data .= "</tbody>";

                                                                            // build the totals header
                                                                            $totals_header = "<div>
                                                                                <table class='w-100 border-0'>
                                                                                    <thead class='border-0' style='border: 0px solid #ffffff;'>
                                                                                        <tr class='border-0'>
                                                                                            <th style='width: 33.34%' class='text-center border-0'></th>
                                                                                            <th style='width: 33.33%' class='text-center border-0'></th>
                                                                                            <th style='width: 33.334%' class='text-center border-0'></th>
                                                                                        </tr>
                                                                                    </thead>

                                                                                    <tbody class='border-0' style='border: 0px solid #ffffff;'>
                                                                                        <tr class='border-0'>
                                                                                            <td class='text-center border-0'><h3><b>Total Students: ".$student_count."</b></h3></td>
                                                                                            <td class='text-center border-0'><h3><b>Projected Annual FTE: ".number_format($total_FTE, 2)."</b></h3></td>
                                                                                            <td class='text-center border-0'><h3><b>Projected Annual Cost: $".number_format($total_cost, 2)."</b></h3></td>
                                                                                        </tr>
                                                                                    </tbody>
                                                                                </table>
                                                                            </div>";
                                                                        }
                                                                        ///////////////////////////////////////////////////////////////////////////////////
                                                                        // 
                                                                        //  unit-based caseload
                                                                        //
                                                                        ///////////////////////////////////////////////////////////////////////////////////
                                                                        else if ($category_settings["uos_enabled"] == 1)
                                                                        {
                                                                            // store UOS service ID
                                                                            $service_id = $service["uos_service_id"];

                                                                            // build the table header (EXTERNAL)
                                                                            $table_data .= "<thead style='border-left: 0px solid #ffffff; border-right: 0px solid #ffffff; border-top: 0px solid #ffffff; border-bottom: 2px solid #000000;'>
                                                                                <th class='text-center border-0' style='width: 25%;'>Student</th>
                                                                                <th class='text-center border-0' style='width: 25%;'>Therapist</th>
                                                                                <th class='text-center border-0' style='width: 25%;'>Projected Annual UOS</th>
                                                                                <th class='text-center border-0' style='width: 25%;'>Projected Annual Cost</th>
                                                                            </thead>";

                                                                            // build the table header (INTERNAL)
                                                                            $internal_table_data .= "<thead style='border-left: 0px solid #ffffff; border-right: 0px solid #ffffff; border-top: 0px solid #ffffff; border-bottom: 2px solid #000000;'>
                                                                                <th class='text-center border-0' style='width: 25%;'>Student</th>
                                                                                <th class='text-center border-0' style='width: 10%;'>Grade</th>
                                                                                <th class='text-center border-0' style='width: 25%;'>Therapist</th>
                                                                                <th class='text-center border-0' style='width: 20%;'>Projected Annual UOS</th>
                                                                                <th class='text-center border-0' style='width: 20%;'>Projected Annual Cost</th>
                                                                            </thead>";

                                                                            // initialize variable to store total units sum and total cost and total student count
                                                                            $total_units = 0;
                                                                            $total_cost = 0;
                                                                            $student_count = 0;

                                                                            // get the expected total units for the caseload category
                                                                            $expected_total_units = getDistrictUnitsTotalByCategory($conn, $customer_id, $category_id, $period);

                                                                            // build the table body
                                                                            $table_data .= "<tbody class='border-0'>";
                                                                            $internal_table_data .= "<tbody class='border-0'>";

                                                                            ///////////////////////////////////////////////////////////////
                                                                            //
                                                                            //  EXTERNAL
                                                                            //
                                                                            ///////////////////////////////////////////////////////////////
                                                                            // get all cases for the customer where the student is attending the district and being billed
                                                                            $getCasesByDistrict = mysqli_prepare($conn, "SELECT c.* FROM cases c
                                                                                                                        JOIN caseloads cl ON c.caseload_id=cl.id
                                                                                                                        JOIN caseload_categories cc ON cl.category_id=cc.id
                                                                                                                        JOIN caseload_students cs ON c.student_id=cs.id
                                                                                                                        WHERE c.period_id=? AND ((c.district_attending=? AND c.bill_to=2) OR (c.residency=? AND c.bill_to=1)) AND cc.id=?
                                                                                                                        ORDER BY cs.lname ASC, cs.fname ASC");
                                                                            mysqli_stmt_bind_param($getCasesByDistrict, "iiii", $period, $customer_id, $customer_id, $category_id);
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
                                                                                        $grade = $case["grade_level"];
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

                                                                                        // initialize estimated annual cost for the student
                                                                                        $case_cost = 0;

                                                                                        // get the cost of the service based on total units
                                                                                        $service_cost = getServiceCost($conn, $service_id, $period, $expected_total_units);

                                                                                        // attempt to get details of the service
                                                                                        $service = getServiceDetails($conn, $service_id);
                                                                                        if (is_array($service)) // service exists; continue
                                                                                        {                
                                                                                            // store service details locally
                                                                                            $service_name = $service["name"];
                                                                                            $service_cost_type = $service["cost_type"];
                                                                                            $service_round_costs = $service["round_costs"];
                                                                                            $service_project_code = $service["project_code"];

                                                                                            // calculate the cost of the student
                                                                                            if ($service_round_costs == 1) { $case_cost = round($service_cost * $case_units); }
                                                                                            else { $case_cost = $service_cost * $case_units; }
                                                                                        }

                                                                                        // only add student to report if units is greater than 0
                                                                                        if ($case_units > 0)
                                                                                        {
                                                                                            // build the table row (EXTERNAL)
                                                                                            $table_data .= "<tr class='border-0'>
                                                                                                <td class='text-center border-0'>".$student_name."</td>
                                                                                                <td class='text-center border-0'>".$therapist_name."</td>
                                                                                                <td class='text-center border-0'>".number_format($case_units)."</td>
                                                                                                <td class='text-end border-0'>$".number_format($case_cost, 2)."</td>
                                                                                            </tr>";
                                                                                        }

                                                                                        // add units to sum
                                                                                        $total_units += $case_units;

                                                                                        // add cost to sum
                                                                                        $total_cost += $case_cost;

                                                                                        // increment student count
                                                                                        $student_count++;
                                                                                    }
                                                                                }
                                                                            }
                                                                            ///////////////////////////////////////////////////////////////
                                                                            //
                                                                            //  INTERNAL
                                                                            //
                                                                            ///////////////////////////////////////////////////////////////
                                                                            $getCaseloads = mysqli_prepare($conn, "SELECT DISTINCT caseload_id, u.lname, u.fname FROM cases c
                                                                                                                    JOIN caseloads cl ON c.caseload_id=cl.id
                                                                                                                    JOIN caseload_categories cc ON cl.category_id=cc.id
                                                                                                                    JOIN users u ON cl.employee_id=u.id
                                                                                                                    WHERE c.period_id=? AND ((c.district_attending=? AND c.bill_to=2) OR (c.residency=? AND c.bill_to=1)) AND cc.id=?
                                                                                                                    ORDER BY u.lname ASC, u.fname ASC");
                                                                            mysqli_stmt_bind_param($getCaseloads, "iiii", $period, $customer_id, $customer_id, $category_id);
                                                                            if (mysqli_stmt_execute($getCaseloads))
                                                                            {
                                                                                $getCaseloadsResults = mysqli_stmt_get_result($getCaseloads);
                                                                                if (mysqli_num_rows($getCaseloadsResults) > 0)
                                                                                {
                                                                                    while ($caseload = mysqli_fetch_array($getCaseloadsResults))
                                                                                    {
                                                                                        // store caseload details locally
                                                                                        $caseload_id = $caseload["caseload_id"];
                                                                                        $therapist_lname = $caseload["lname"];
                                                                                        $therapist_fname = $caseload["fname"];

                                                                                        // build therapist name display
                                                                                        $therapist_name = $therapist_lname.", ".$therapist_fname;

                                                                                        // initialize subtotals
                                                                                        $caseload_subtotal_cost = 0;
                                                                                        $caseload_subtotal_units = 0;
                                                                                        $caseload_subtotal_students = 0;

                                                                                        // get all cases for the customer where the student is attending the district and being billed
                                                                                        $getCasesByDistrict = mysqli_prepare($conn, "SELECT c.* FROM cases c
                                                                                                                                    JOIN caseloads cl ON c.caseload_id=cl.id
                                                                                                                                    JOIN caseload_categories cc ON cl.category_id=cc.id
                                                                                                                                    JOIN caseload_students cs ON c.student_id=cs.id
                                                                                                                                    WHERE c.period_id=? AND ((c.district_attending=? AND c.bill_to=2) OR (c.residency=? AND c.bill_to=1)) AND cc.id=? AND c.caseload_id=?
                                                                                                                                    ORDER BY cs.lname ASC, cs.fname ASC");
                                                                                        mysqli_stmt_bind_param($getCasesByDistrict, "iiiii", $period, $customer_id, $customer_id, $category_id, $caseload_id);
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
                                                                                                    $grade = $case["grade_level"];
                                                                                                    $caseload_id = $case["caseload_id"];
                                                                                                    $evaluation_method = $case["evaluation_method"];
                                                                                                    $extra_ieps = $case["extra_ieps"];
                                                                                                    $extra_evals = $case["extra_evaluations"];

                                                                                                    // get the student's name
                                                                                                    $student_name = getStudentDisplayName($conn, $student_id);

                                                                                                    // get the end of year units of service (prorated based on changes)
                                                                                                    $case_units = 0;
                                                                                                    if ($evaluation_method == 1) { $case_units = getProratedUOS($conn, $case_id); }
                                                                                                    else if ($evaluation_method == 2) { $case_units = 16; }

                                                                                                    // calculate the number of additional units based on extra IEPs or evaluations, then add to the EOY unit total
                                                                                                    $additional_units = 0;
                                                                                                    if (is_numeric($extra_ieps) && $extra_ieps > 0) { $additional_units += (12 * $extra_ieps); }
                                                                                                    if (is_numeric($extra_evals) && $extra_evals > 0) { $additional_units += (16 * $extra_evals); }
                                                                                                    $case_units += $additional_units;

                                                                                                    // initialize estimated annual cost for the student
                                                                                                    $case_cost = 0;

                                                                                                    // get the cost of the service based on total units
                                                                                                    $service_cost = getServiceCost($conn, $service_id, $period, $expected_total_units);

                                                                                                    // attempt to get details of the service
                                                                                                    $service = getServiceDetails($conn, $service_id);
                                                                                                    if (is_array($service)) // service exists; continue
                                                                                                    {                
                                                                                                        // store service details locally
                                                                                                        $service_name = $service["name"];
                                                                                                        $service_cost_type = $service["cost_type"];
                                                                                                        $service_round_costs = $service["round_costs"];
                                                                                                        $service_project_code = $service["project_code"];

                                                                                                        // calculate the cost of the student
                                                                                                        if ($service_round_costs == 1) { $case_cost = round($service_cost * $case_units); }
                                                                                                        else { $case_cost = $service_cost * $case_units; }
                                                                                                    }

                                                                                                    // only add student to report if units is greater than 0
                                                                                                    if ($case_units > 0)
                                                                                                    {
                                                                                                        // build the table row (INTERNAL)
                                                                                                        $internal_table_data .= "<tr class='border-0'>
                                                                                                            <td class='text-center border-0'>".$student_name."</td>
                                                                                                            <td class='text-center border-0'>".printGradeLevel($grade)."</td>
                                                                                                            <td class='text-center border-0'>".$therapist_name."</td>
                                                                                                            <td class='text-center border-0'>".number_format($case_units)."</td>
                                                                                                            <td class='text-end border-0'>$".number_format($case_cost, 2)."</td>
                                                                                                        </tr>";
                                                                                                    }

                                                                                                    // add units to sum
                                                                                                    $caseload_subtotal_units += $case_units;

                                                                                                    // add cost to sum
                                                                                                    $caseload_subtotal_cost += $case_cost;

                                                                                                    // increment student count
                                                                                                    $caseload_subtotal_students++;
                                                                                                }
                                                                                            }
                                                                                        }

                                                                                        // print subtotal only if students were placed in a classroom
                                                                                        if ($caseload_subtotal_students > 0)
                                                                                        {
                                                                                            // build classroom subtotal
                                                                                            $internal_table_data .= "<tr class='border-0'>
                                                                                                <td class='text-center border-0'></td>
                                                                                                <td class='text-center border-0'></td>
                                                                                                <td class='text-center border-0'><i><b>".$therapist_name."</b></i></td>
                                                                                                <td class='text-center border-0'><i><b>".number_format($caseload_subtotal_units)."</b></i></td>
                                                                                                <td class='text-end border-0'><i><b>$".number_format($caseload_subtotal_cost, 2)."</b></i></td>
                                                                                            </tr>";
                                                                                        }
                                                                                    }
                                                                                }
                                                                            }

                                                                            // get the total projected cost
                                                                            $total_cost = getInvoiceCost($conn, $service_id, $customer_id, $period, $service_cost_type, $service_round_costs, $expected_total_units);

                                                                            // close the table body
                                                                            $table_data .= "</tbody>";
                                                                            $internal_table_data .= "</tbody>";

                                                                            // build the totals header
                                                                            $totals_header = "<div>
                                                                                <table class='w-100 border-0' style='border: 0px solid #ffffff;'>
                                                                                    <thead class='border-0' style='border: 0px solid #ffffff;'>
                                                                                        <tr class='border-0'>
                                                                                            <th style='width: 33.34%' class='text-center border-0'></th>
                                                                                            <th style='width: 33.33%' class='text-center border-0'></th>
                                                                                            <th style='width: 33.334%' class='text-center border-0'></th>
                                                                                        </tr>
                                                                                    </thead>

                                                                                    <tbody class='border-0' style='border: 0px solid #ffffff;'>
                                                                                        <tr class='border-0'>
                                                                                            <td class='text-center border-0'><h3><b>Total Students: ".$student_count."</b></h3></td>
                                                                                            <td class='text-center border-0'><h3><b>Projected Annual UOS: ".number_format($total_units)."</b></h3></td>
                                                                                            <td class='text-center border-0'><h3><b>Projected Annual Cost: $".number_format($total_cost, 2)."</b></h3></td
                                                                                        </tr>
                                                                                    </tbody>
                                                                                </table>
                                                                            </div>";
                                                                        }

                                                                        ///////////////////////////////////////////////////////////////////////////////
                                                                        //
                                                                        //  SERVICE PAGE (EXTERNAL)
                                                                        //
                                                                        ///////////////////////////////////////////////////////////////////////////////
                                                                        $pdf->addPage("<html>
                                                                            <!-- Custom Stylesheet -->
                                                                            <link href='$STYLESHEET_PATH' rel='stylesheet'>

                                                                            <!-- Bootstrap Stylesheet -->
                                                                            <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css' rel='stylesheet' integrity='sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC' crossorigin='anonymous'>

                                                                            <body>
                                                                                <table class='w-100 border-0' style='border: 0px solid #ffffff;'>
                                                                                    <thead class='border-0 py-1 my-0' style='border: 0px solid #ffffff;'>
                                                                                        <tr class='border-0 py-1 my-0'>
                                                                                            <td class='border-0 py-1 my-0' style='width: 33.33%; border: 0px solid #ffffff;'></td>
                                                                                            <td class='border-0 py-1 my-0' style='width: 33.34%; text-align: center !important; border: 0px solid #ffffff;'>
                                                                                                <img class='my-2' style='width: 75%;' src='$CESA5_LOGO_PATH' alt='CESA 5 Logo'>
                                                                                            </td>
                                                                                            <td class='border-0 py-1 my-0' style='width: 33.33%; text-align: right !important; border: 0px solid #ffffff;'>
                                                                                                <h2 class='mb-1'><b>".$period_name."</b></h2>
                                                                                                <h3 class='mb-1'><b>".printNumber($quarter)." Quarter</b></h3>
                                                                                            </td>
                                                                                        </tr>
                                                                                    </thead>
                                                                                </table>
                                                                                <div class='text-center'>
                                                                                    <h2 class='my-2'><b>".$category_name."</b></h2>
                                                                                    <div class='w-100'>".$totals_header."</div>
                                                                                </div>

                                                                                <div class=''>
                                                                                    <table class='w-100 border-0'>
                                                                                        ".$table_data."
                                                                                    </table>
                                                                                </div>
                                                                            </body>
                                                                        </html>");

                                                                        ///////////////////////////////////////////////////////////////////////////////
                                                                        //
                                                                        //  SERVICE PAGE (INTERNAL)
                                                                        //
                                                                        ///////////////////////////////////////////////////////////////////////////////
                                                                        $internal_pdf->addPage("<html>
                                                                            <!-- Custom Stylesheet -->
                                                                            <link href='$STYLESHEET_PATH' rel='stylesheet'>

                                                                            <!-- Bootstrap Stylesheet -->
                                                                            <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css' rel='stylesheet' integrity='sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC' crossorigin='anonymous'>

                                                                            <body>
                                                                                <table class='w-100 border-0' style='border: 0px solid #ffffff;'>
                                                                                    <thead class='border-0 py-1 my-0' style='border: 0px solid #ffffff;'>
                                                                                        <tr class='border-0 py-1 my-0'>
                                                                                            <td class='border-0 py-1 my-0' style='width: 33.33%; border: 0px solid #ffffff;'></td>
                                                                                            <td class='border-0 py-1 my-0' style='width: 33.34%; text-align: center !important; border: 0px solid #ffffff;'>
                                                                                                <img class='my-2' style='width: 75%;' src='$CESA5_LOGO_PATH' alt='CESA 5 Logo'>
                                                                                            </td>
                                                                                            <td class='border-0 py-1 my-0' style='width: 33.33%; text-align: right !important; border: 0px solid #ffffff;'>
                                                                                                <h2 class='mb-1'><b>".$period_name."</b></h2>
                                                                                                <h3 class='mb-1'><b>".printNumber($quarter)." Quarter</b></h3>
                                                                                            </td>
                                                                                        </tr>
                                                                                    </thead>
                                                                                </table>
                                                                                <div class='text-center'>
                                                                                    <h2 class='my-2'><b>".$category_name."</b></h2>
                                                                                    <div class='w-100'>".$totals_header."</div>
                                                                                </div>

                                                                                <div class=''>
                                                                                    <table class='w-100 border-0'>
                                                                                        ".$internal_table_data."
                                                                                    </table>
                                                                                </div>
                                                                            </body>
                                                                        </html>");
                                                                    }
                                                                }
                                                            }

                                                            ///////////////////////////////////////////////////////////
                                                            //
                                                            //  EXTERNAL
                                                            //
                                                            ///////////////////////////////////////////////////////////
                                                            // check to see if we have created a directory to store quarterly billing reports for the selected period
                                                            if (is_dir("local_data/caseloads/quarterly_billing/".$period."/".$quarter."/".$customer_id)) // directory exists 
                                                            {

                                                            }
                                                            else // directory does not exists; create new directory
                                                            {
                                                                // create the directoy where owner and group can read, write, and execute to the directory
                                                                mkdir("local_data/caseloads/quarterly_billing/".$period."/".$quarter."/".$customer_id, 0770, true);
                                                            }

                                                            // attempt to save the PDF to a local directory
                                                            if (!$pdf->saveAs("local_data/caseloads/quarterly_billing/".$period."/".$quarter."/".$customer_id."/".$datestamp."_".$customer_filename.".pdf"))
                                                            {
                                                                $error = $pdf->getError();
                                                                $errors++;
                                                            }
                                                            else 
                                                            {
                                                                // log successful PDF save
                                                                echo "<span class=\"log-success\">Successfully</span> saved PDF for $customer_name locally.<br>";
                                                                
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
                                                    
                                                                        try 
                                                                        {
                                                                            $result = $client_service->files->create($resource, [
                                                                                "data" => file_get_contents("local_data/caseloads/quarterly_billing/".$period."/".$quarter."/".$customer_id."/".$datestamp."_".$customer_filename.".pdf"),
                                                                                "mimeType" => "application/pdf",
                                                                                "uploadType" => "multipart"
                                                                            ]);
                                                                        }
                                                                        catch (Exception $e)
                                                                        {
                                                                            echo "<span class=\"log-fail\">Failed</span> to create the Google Drive resource for $customer_name! We failed to create the PDF file on Google Drive.<br>";
                                                                        }
                                                                        catch (Error $e)
                                                                        {
                                                                            echo "<span class=\"log-fail\">Failed</span> to create the Google Drive resource for $customer_name! ".$e->getMessage()."<br>";
                                                                        }
                                                
                                                                        if ($result) { echo "<span class=\"log-success\">Successfully</span> uploaded the quarterly billing report to Google Drive for $customer_name.<br>"; }
                                                                        else { echo "<span class=\"log-fail\">Failed</span> to upload quarterly billing report for $customer_name!<br>"; }
                                                                    }
                                                                    else { echo "<span class=\"log-fail\">Failed</span> to upload quarterly billing report for $customer_name, the folder with GID $customer_folder was not found!<br>"; }
                                                                }

                                                                // log quarterly report creation
                                                                $message = "Successfully created the Q$quarter billing report for $customer_name for the period $period_name. ";
                                                                if ($uploadToDrive == 1) 
                                                                { 
                                                                    if ($result) { $message .= "Uploaded the quarterly billing report to the Google Drive folder with GID $customer_folder. "; }
                                                                    else { $message .= "Failed to upload the quarterly billing report to the Google Drive folder with GID $customer_folder. "; }
                                                                }
                                                                $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                                                                mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                                                                mysqli_stmt_execute($log);
                                                            }

                                                            ///////////////////////////////////////////////////////////
                                                            //
                                                            //  INTERNAL
                                                            //
                                                            ///////////////////////////////////////////////////////////
                                                            // check to see if we have created a directory to store quarterly billing reports for the selected period
                                                            if (is_dir("local_data/caseloads/internal_quarterly_billing/".$period."/".$quarter."/".$customer_id)) // directory exists 
                                                            {

                                                            }
                                                            else // directory does not exists; create new directory
                                                            {
                                                                // create the directoy where owner and group can read, write, and execute to the directory
                                                                mkdir("local_data/caseloads/internal_quarterly_billing/".$period."/".$quarter."/".$customer_id, 0770, true);
                                                            }

                                                            // attempt to save the PDF to a local directory
                                                            if (!$internal_pdf->saveAs("local_data/caseloads/internal_quarterly_billing/".$period."/".$quarter."/".$customer_id."/".$datestamp."_".$customer_filename.".pdf"))
                                                            {
                                                                $error = $internal_pdf->getError();
                                                                $internal_errors++;
                                                            }
                                                            else 
                                                            {
                                                                // log successful PDF save
                                                                echo "<span class=\"log-success\">Successfully</span> saved the internal PDF for $customer_name locally.<br>";
                                                                
                                                                // increment success counter
                                                                $internal_total_successes++;

                                                                // log quarterly report creation
                                                                $message = "Successfully created the internal Q$quarter billing report for $customer_name for the period $period_name. ";
                                                                $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                                                                mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                                                                mysqli_stmt_execute($log);
                                                            }
                                                        }
                                                        else { echo "<span class=\"log-fail\">Failed</span> to create the quarterly billing report for the customer with ID of $customer_id. This customer does not exist!<br>"; }
                                                    }
                                                    else { echo "<span class=\"log-fail\">Failed</span> to create the quarterly billing report for the customer with ID of $customer_id. An unexpected error has occurred! Please try again later.<br>"; }
                                                }

                                                // log reports creation
                                                $message = "Successfully created $total_successes quarterly billing reports for $period_name. ";
                                                $message .= "Successfully created $internal_total_successes internal quarterly billing reports for $period_name. ";
                                                if ($errors > 0) { $message .= "Failed to create $errors quarterly billing reports for $period_name. "; }
                                                if ($internal_errors > 0) { $message .= "Failed to create $errors internal quarterly billing reports for $period_name. "; }
                                                $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                                                mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                                                mysqli_stmt_execute($log);
                                            }
                                            else { echo "<span class=\"log-fail\">Failed</span> to create the quarterly billing report. Please try again later.<br>"; }
                                        }
                                        else { echo "<span class=\"log-fail\">Failed</span> to create the quarterly billing report. The quarter selected was invalid. Please try again later.<br>"; }
                                    }
                                    else { echo "<span class=\"log-fail\">Failed</span> to create the quarterly billing report. The period selected was invalid. Please try again later.<br>"; }
                                }
                                else { echo "<span class=\"log-fail\">Failed</span> to create the quarterly billing report. You must provide all the required fields.<br>"; } // error - did not receive all required parameters
                            ?>
                            </div>
                            <div class="col-2"></div>
                        </div>

                        <div class="row justify-content-center text-center my-2">
                            <div class="col-2"><button class="btn btn-primary w-100" onclick="goToQuarterlyBilling();">Return To Quarterly Billing</button></div>
                        </div>
                        <script>
                            function goToQuarterlyBilling() { window.location.href = "caseloads_billing_quarterly.php"; }
                        </script>
                    <?php
                }
                else { echo "<span class=\"log-fail\">Failed</span> to create the quarterly billing report. You must provide all the required fields.<br>"; } // error - did not receive all required parameters
            }
            else { echo "Your account does not have permission to perform this task.<br>"; } // error - user is not an admin

            // disconnect from the database
            mysqli_close($conn);
        }
    }
    else { header("Location: " . $client->createAuthUrl()); } // authentication code not set; redirect to Google authentication page
?>