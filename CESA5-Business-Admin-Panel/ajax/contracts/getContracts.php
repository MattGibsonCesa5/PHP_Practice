<?php
    session_start();

    // set timezone to Central
    date_default_timezone_set("America/Chicago");

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // initialize array to store data
        $data = [];

        // get additional required files
        include("../../includes/config.php");
        include("../../includes/functions.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        // get period from POST
        if (isset($_POST["period_id"]) && is_numeric($_POST["period_id"])) { $period_id = $_POST["period_id"]; } else { $period_id = null; }
        if (isset($_POST["type"]) && is_numeric($_POST["type"])) { $type = $_POST["type"]; } else { $type = null; }

        // get customer ID
        $customer_id = null;
        if (isset($_SESSION["district"]) && $_SESSION["district"]["status"] == 1 && (isset($_SESSION["district"]["id"]) && verifyCustomer($conn, $_SESSION["district"]["id"]))) {
            $customer_id = $_SESSION["district"]["id"];
        } else if ($_SESSION["role"] == 1 && ((isset($_POST["customer_id"]) && verifyCustomer($conn, $_POST["customer_id"])) || $_POST["customer_id"] == -1)) {
            $customer_id = $_POST["customer_id"];
        }

        // verify the period selected is valud
        if (($period_id != null && verifyPeriod($conn, $period_id)) || $period_id == -1)
        {
            // if customer ID is valid; continue
            if ((isset($customer_id) && $customer_id != null) || $customer_id == -1)
            {
                // build the parameters
                $filters = "";
                $bindTypes = "";
                $bindVars = [];
                if ((isset($period_id) && $period_id != null) && $period_id != -1) {
                    if ($filters <> "") { 
                        $filters .= " AND period_id=?";
                    } else {
                        $filters .= "WHERE period_id=?";
                    }
                    $bindTypes .= "i";
                    $bindVars[] = $period_id;
                }
                if ((isset($customer_id) && $customer_id != null) && $customer_id != -1) {
                    if ($filters <> "") { 
                        $filters .= " AND customer_id=?";
                    } else {
                        $filters .= "WHERE customer_id=?";
                    }
                    $bindTypes .= "i";
                    $bindVars[] = $customer_id;
                }
                if ((isset($type) && $type != null) && $type != -1) {
                    if ($filters <> "") { 
                        $filters .= " AND contract_type=?";
                    } else {
                        $filters .= "WHERE contract_type=?";
                    }
                    $bindTypes .= "i";
                    $bindVars[] = $type;
                }

                if (trim($bindTypes) <> "" && count($bindVars) > 0) {
                    $query = "SELECT cc.id, cc.period_id, cc.filename, cc.filepath, cc.timestamp, cc.hide, cc.status, c.name AS customer, ct.name AS contract_type, ct.requires_signature FROM contracts_created cc 
                            JOIN customers c ON cc.customer_id=c.id
                            JOIN contract_types ct ON cc.contract_type=ct.id
                            ".$filters." 
                            ORDER BY cc.timestamp DESC";
                    $getContracts = mysqli_prepare($conn, $query);
                    mysqli_stmt_bind_param($getContracts, $bindTypes, ...$bindVars);
                } else {
                    $query = "SELECT cc.id, cc.period_id, cc.filename, cc.filepath, cc.timestamp, cc.hide, cc.status, c.name AS customer, ct.name AS contract_type, ct.requires_signature FROM contracts_created cc
                            JOIN customers c ON cc.customer_id=c.id
                            JOIN contract_types ct ON cc.contract_type=ct.id
                            ORDER BY timestamp DESC";
                    $getContracts = mysqli_prepare($conn, $query);
                }

                // get a list of contracts
                if (mysqli_stmt_execute($getContracts))
                {
                    $getContractsResults = mysqli_stmt_get_result($getContracts);
                    if (mysqli_num_rows($getContractsResults) > 0)
                    {
                        while ($contract = mysqli_fetch_assoc($getContractsResults))
                        {
                            // only display the row if the contract is visible or the user is an admin
                            if ($contract["hide"] == 0 || $_SESSION["role"] == 1)
                            {
                                // build the status column
                                $status_display = "";
                                if ($contract["status"] == 0) { $status_display = "<span class=\"badge bg-warning px-3 py-2\"><i class=\"fa-solid fa-triangle-exclamation\"></i> Pending Approval</span>"; }
                                else if ($contract["status"] == 1) { $status_display = "<span class=\"badge bg-success px-3 py-2\"><i class=\"fa-solid fa-check\"></i> Approved</span>"; }
                                else if ($contract["status"] == 2) { $status_display = "<span class=\"badge bg-danger px-3 py-2\"><i class=\"fa-solid fa-x\"></i> Rejected</span>"; }
                                else if ($contract["status"] == 3) { $status_display = "<span class=\"badge bg-secondary px-3 py-2\"><i class=\"fa-solid fa-pencil\"></i> Changes Requested</span>"; }

                                // build the actions column
                                $actions = "<div class='d-flex justify-content-end'>
                                    <button class='btn btn-primary btn-sm mx-1' type='button' onclick='viewContract(\"".$contract["id"]."\");'>
                                        <i class='fa-solid fa-eye'></i>
                                    </button>";
                                    if ($contract["requires_signature"] == 1) { 
                                        $actions .= "<button class='btn btn-primary btn-sm mx-1' type='button' onclick='getSignContractModal(\"".$contract["id"]."\");'>
                                            <i class='fa-solid fa-feather-pointed'></i>
                                        </button>";
                                    }
                                $actions .= "</div>";

                                $temp = [];
                                $temp["year"] = getPeriodName($conn, $contract["period_id"]);
                                $temp["customer"] = $contract["customer"];
                                $temp["title"] = $contract["contract_type"];
                                $temp["file"] = $contract["filename"];
                                $temp["created"] = date("n/j/Y g:ia", strtotime($contract["timestamp"]));
                                $temp["status"] = $status_display;
                                $temp["actions"] = $actions;
                                $data[] = $temp;
                            }
                        }
                    }
                }

                // allow admins to view old contracts before transition
                if ($_SESSION["role"] == 1)
                {
                    // get original service contracts
                    if ($type == -1 || $type == 1)
                    {
                        if ($period_id != -1)
                        {
                            // get all service contracts that were created for the period
                            $directory = "../../local_data/service_contracts/$period_id/";
                            if (is_dir($directory))
                            {
                                $files = scandir($directory, 1);
                                for ($f = 0; $f < count($files); $f++)
                                {
                                    // get the customer ID from the file name (ID is pre .pdf file extension)
                                    $file = $files[$f];
                                    $file_customer_id = pathinfo($file, PATHINFO_FILENAME);

                                    // verify the customer ID is a number
                                    if (is_numeric($file_customer_id) && (($file_customer_id == $customer_id) || $customer_id == -1))
                                    {
                                        // check to see if the customer still exists
                                        $checkCustomer = mysqli_prepare($conn, "SELECT id, name FROM customers WHERE id=?");
                                        mysqli_stmt_bind_param($checkCustomer, "i", $file_customer_id);
                                        if (mysqli_stmt_execute($checkCustomer))
                                        {
                                            $checkCustomerResult = mysqli_stmt_get_result($checkCustomer);
                                            if (mysqli_num_rows($checkCustomerResult) > 0) // customer exists; continue
                                            {
                                                $customer_details = mysqli_fetch_array($checkCustomerResult);
                                                $customer_name = $customer_details["name"];

                                                // build the actions column
                                                $actions = "";
                                                $actions = "<div class='d-flex justify-content-end'>
                                                    <button class='btn btn-primary btn-sm' type='button' onclick='getViewServiceContractModal(".$period_id.", \"".$file_customer_id."\");'><i class='fa-solid fa-eye'></i></button></div>
                                                </div>";

                                                $temp = [];
                                                $temp["year"] = getPeriodName($conn, $period_id);
                                                $temp["customer"] = $customer_name;
                                                $temp["title"] = "Service Contract";
                                                $temp["file"] = getPeriodName($conn, $period_id)." Service Contract";
                                                $temp["created"] = "-";
                                                $temp["status"] = "<span class=\"badge bg-danger px-3 py-2 fst-italic\">Outdated</span>";
                                                $temp["actions"] = $actions;
                                                $data[] = $temp;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                        else if ($period_id == -1)
                        {
                            $getPeriods = mysqli_query($conn, "SELECT id FROM periods ORDER BY name DESC");
                            if (mysqli_num_rows($getPeriods) > 0)
                            {
                                while ($period = mysqli_fetch_assoc($getPeriods))
                                {
                                    // get all service contracts that were created for the period
                                    $directory = "../../local_data/service_contracts/".$period["id"]."/";
                                    if (is_dir($directory))
                                    {
                                        $files = scandir($directory, 1);
                                        for ($f = 0; $f < count($files); $f++)
                                        {
                                            // get the customer ID from the file name (ID is pre .pdf file extension)
                                            $file = $files[$f];
                                            $file_customer_id = pathinfo($file, PATHINFO_FILENAME);

                                            // verify the customer ID is a number
                                            if (is_numeric($file_customer_id) && (($file_customer_id == $customer_id) || $customer_id == -1))
                                            {
                                                // check to see if the customer still exists
                                                $checkCustomer = mysqli_prepare($conn, "SELECT id, name FROM customers WHERE id=?");
                                                mysqli_stmt_bind_param($checkCustomer, "i", $file_customer_id);
                                                if (mysqli_stmt_execute($checkCustomer))
                                                {
                                                    $checkCustomerResult = mysqli_stmt_get_result($checkCustomer);
                                                    if (mysqli_num_rows($checkCustomerResult) > 0) // customer exists; continue
                                                    {
                                                        $customer_details = mysqli_fetch_array($checkCustomerResult);
                                                        $customer_name = $customer_details["name"];

                                                        // build the actions column
                                                        $actions = "";
                                                        $actions = "<div class='d-flex justify-content-end'>
                                                            <button class='btn btn-primary btn-sm' type='button' onclick='getViewServiceContractModal(".$period["id"].", \"".$file_customer_id."\");'><i class='fa-solid fa-eye'></i></button></div>
                                                        </div>";

                                                        $temp = [];
                                                        $temp["year"] = getPeriodName($conn, $period["id"]);
                                                        $temp["customer"] = $customer_name;
                                                        $temp["title"] = "Service Contract";
                                                        $temp["file"] = getPeriodName($conn, $period["id"])." Service Contract";
                                                        $temp["created"] = "-";
                                                        $temp["status"] = "<span class=\"badge bg-danger px-3 py-2 fst-italic\">Outdated</span>";
                                                        $temp["actions"] = $actions;
                                                        $data[] = $temp;
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }

                    // get original quarterly invoices
                    if ($type == -1 || $type == 2)
                    {
                        if ($period_id != -1)
                        {
                            for ($q = 1; $q <= 4; $q++)
                            {
                                // get all contracts that were created for the period
                                $directory = "../../local_data/quarter_contracts/$period_id/$q/";
                                if (is_dir($directory)) // directory exists; continue
                                {
                                    $files = scandir($directory, 1);
                                    for ($f = 0; $f < count($files); $f++)
                                    {
                                        // get the customer ID from the file name (ID is pre .pdf file extension)
                                        $file = $files[$f];
                                        $file_customer_id = pathinfo($file, PATHINFO_FILENAME);

                                        // verify the customer ID is a number
                                        if (is_numeric($file_customer_id) && (($file_customer_id == $customer_id) || $customer_id == -1))
                                        {
                                            // check to see if the customer still exists
                                            $checkCustomer = mysqli_prepare($conn, "SELECT id, name FROM customers WHERE id=?");
                                            mysqli_stmt_bind_param($checkCustomer, "i", $file_customer_id);
                                            if (mysqli_stmt_execute($checkCustomer))
                                            {
                                                $checkCustomerResult = mysqli_stmt_get_result($checkCustomer);
                                                if (mysqli_num_rows($checkCustomerResult) > 0) // customer exists; continue
                                                {
                                                    $customer_details = mysqli_fetch_array($checkCustomerResult);
                                                    $customer_name = $customer_details["name"];

                                                    // build the actions column
                                                    $actions = "<div class='d-flex justify-content-end'>
                                                        <button class='btn btn-primary btn-sm' type='button' onclick='getViewQuarterlyInvoiceModal(".$period_id.", ".$q.",\"".$file_customer_id."\");'><i class='fa-solid fa-eye'></i></button>
                                                    </div>";

                                                    $temp = [];
                                                    $temp["year"] = getPeriodName($conn, $period_id);
                                                    $temp["customer"] = $customer_name;
                                                    $temp["title"] = "Quarterly Invoice";
                                                    $temp["file"] = getPeriodName($conn, $period_id)." Q$q Quarterly Invoice";
                                                    $temp["created"] = "-";
                                                    $temp["status"] = "<span class=\"badge bg-danger px-3 py-2 fst-italic\">Outdated</span>";
                                                    $temp["actions"] = $actions;
                                                    $data[] = $temp;
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                        else if ($period_id == -1)
                        {
                            $getPeriods = mysqli_query($conn, "SELECT id FROM periods ORDER BY name DESC");
                            if (mysqli_num_rows($getPeriods) > 0)
                            {
                                while ($period = mysqli_fetch_assoc($getPeriods))
                                {
                                    for ($q = 1; $q <= 4; $q++)
                                    {
                                        // get all contracts that were created for the period
                                        $directory = "../../local_data/quarter_contracts/".$period["id"]."/$q/";
                                        if (is_dir($directory)) // directory exists; continue
                                        {
                                            $files = scandir($directory, 1);
                                            for ($f = 0; $f < count($files); $f++)
                                            {
                                                // get the customer ID from the file name (ID is pre .pdf file extension)
                                                $file = $files[$f];
                                                $file_customer_id = pathinfo($file, PATHINFO_FILENAME);

                                                // verify the customer ID is a number
                                                if (is_numeric($file_customer_id) && (($file_customer_id == $customer_id) || $customer_id == -1))
                                                {
                                                    // check to see if the customer still exists
                                                    $checkCustomer = mysqli_prepare($conn, "SELECT id, name FROM customers WHERE id=?");
                                                    mysqli_stmt_bind_param($checkCustomer, "i", $file_customer_id);
                                                    if (mysqli_stmt_execute($checkCustomer))
                                                    {
                                                        $checkCustomerResult = mysqli_stmt_get_result($checkCustomer);
                                                        if (mysqli_num_rows($checkCustomerResult) > 0) // customer exists; continue
                                                        {
                                                            $customer_details = mysqli_fetch_array($checkCustomerResult);
                                                            $customer_name = $customer_details["name"];

                                                            // build the actions column
                                                            $actions = "<div class='d-flex justify-content-end'>
                                                                <button class='btn btn-primary btn-sm' type='button' onclick='getViewQuarterlyInvoiceModal(".$period["id"].", ".$q.",\"".$file_customer_id."\");'><i class='fa-solid fa-eye'></i></button>
                                                            </div>";

                                                            $temp = [];
                                                            $temp["year"] = getPeriodName($conn, $period["id"]);
                                                            $temp["customer"] = $customer_name;
                                                            $temp["title"] = "Quarterly Invoice";
                                                            $temp["file"] = getPeriodName($conn, $period["id"])." Q$q Quarterly Invoice";
                                                            $temp["created"] = "-";
                                                            $temp["status"] = "<span class=\"badge bg-danger px-3 py-2 fst-italic\">Outdated</span>";
                                                            $temp["actions"] = $actions;
                                                            $data[] = $temp;
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

                    // get original caseload quarterly billing reports
                    if ($type == -1 || $type == 3)
                    {
                        if ($period_id != -1)
                        {
                            for ($q = 1; $q <= 4; $q++)
                            {
                                // get all reports created for the districts
                                $directory = "../../local_data/caseloads/quarterly_billing/$period_id/$q/";
                                if (is_dir($directory) && $files = scandir($directory, 1))
                                {
                                    for ($f = 0; $f < count($files); $f++)
                                    {
                                        // get the customer ID from the file name (ID is pre .pdf file extension)
                                        $file = $files[$f];
                                        $file_customer_id = pathinfo($file, PATHINFO_FILENAME);

                                        // verify the customer ID is a number
                                        if ((is_numeric($file_customer_id) && verifyCustomer($conn, $file_customer_id)) && (($file_customer_id == $customer_id) || $customer_id == -1))
                                        {
                                            // get the cusotmer's name
                                            $customer_details = getCustomerDetails($conn, $file_customer_id);
                                            $customer_name = $customer_details["name"];

                                            // get all reports saved for this customer for this period and quarter
                                            $customer_directory = "../../local_data/caseloads/quarterly_billing/$period_id/$q/$file_customer_id/";
                                            $customer_files = scandir($customer_directory, 1);
                                            for ($cf = 0; $cf < count($customer_files); $cf++)
                                            {
                                                // get the file
                                                $customer_file = $customer_files[$cf];
                                                $file_name = pathinfo($customer_file, PATHINFO_FILENAME);
                                                $file_ext = pathinfo($customer_file, PATHINFO_EXTENSION);
                                                
                                                // ensure that the file is a pdf
                                                if ($file_ext == "pdf")
                                                {
                                                    // build the actions column
                                                    $actions = "<div class='d-flex justify-content-end'>
                                                        <button class='btn btn-primary btn-sm' type='button' onclick='getViewDistrictReport($file_customer_id, $period_id, $q, \"".$file_name."\", 0);'><i class='fa-solid fa-eye'></i></button>
                                                    </div>";

                                                    $temp = [];
                                                    $temp["year"] = getPeriodName($conn, $period_id);
                                                    $temp["customer"] = $customer_name;
                                                    $temp["title"] = "SPED District Quarterly Billing";
                                                    $temp["file"] = getPeriodName($conn, $period_id)." SPED District Q$q Billing";
                                                    $temp["created"] = "-";
                                                    $temp["status"] = "<span class=\"badge bg-danger px-3 py-2 fst-italic\">Outdated</span>";
                                                    $temp["actions"] = $actions;
                                                    $data[] = $temp;
                                                }
                                            }
                                        }
                                    }
                                }

                                // get all reports created for the districts
                                $directory = "../../local_data/caseloads/internal_quarterly_billing/$period_id/$q/";
                                if (is_dir($directory) && $files = scandir($directory, 1))
                                {
                                    for ($f = 0; $f < count($files); $f++)
                                    {
                                        // get the customer ID from the file name (ID is pre .pdf file extension)
                                        $file = $files[$f];
                                        $file_customer_id = pathinfo($file, PATHINFO_FILENAME);

                                        // verify the customer is valid
                                        if ((is_numeric($file_customer_id) && verifyCustomer($conn, $file_customer_id)) && (($file_customer_id == $customer_id) || $customer_id == -1))
                                        {
                                            // get the cusotmer's name
                                            $customer_details = getCustomerDetails($conn, $file_customer_id);
                                            $customer_name = $customer_details["name"];

                                            // get all reports saved for this customer for this period and quarter
                                            $customer_directory = "../../local_data/caseloads/internal_quarterly_billing/$period_id/$q/$file_customer_id/";
                                            $customer_files = scandir($customer_directory, 1);
                                            for ($cf = 0; $cf < count($customer_files); $cf++)
                                            {
                                                // get the file
                                                $customer_file = $customer_files[$cf];
                                                $file_name = pathinfo($customer_file, PATHINFO_FILENAME);
                                                $file_ext = pathinfo($customer_file, PATHINFO_EXTENSION);
                                                
                                                // ensure that the file is a pdf
                                                if ($file_ext == "pdf")
                                                {
                                                    // build the actions column
                                                    $actions = "<div class='d-flex justify-content-end'>
                                                        <button class='btn btn-primary btn-sm' type='button' onclick='getViewDistrictReport($file_customer_id, $period_id, $q, \"".$file_name."\", 1);'><i class='fa-solid fa-eye'></i></button>
                                                    </div>";

                                                    $temp = [];
                                                    $temp["year"] = getPeriodName($conn, $period_id);
                                                    $temp["customer"] = $customer_name;
                                                    $temp["title"] = "<i><b>INTERNAL</b></i> SPED District Quarterly Billing";
                                                    $temp["file"] = getPeriodName($conn, $period_id)." SPED District Q$q Billing";
                                                    $temp["created"] = "-";
                                                    $temp["status"] = "<span class=\"badge bg-danger px-3 py-2 fst-italic\">Outdated</span>";
                                                    $temp["actions"] = $actions;
                                                    $data[] = $temp;
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                        else if ($period_id == -1)
                        {
                            $getPeriods = mysqli_query($conn, "SELECT id FROM periods ORDER BY name DESC");
                            if (mysqli_num_rows($getPeriods) > 0)
                            {
                                while ($period = mysqli_fetch_assoc($getPeriods))
                                {
                                    for ($q = 1; $q <= 4; $q++)
                                    {
                                        // get all reports created for the districts
                                        $directory = "../../local_data/caseloads/quarterly_billing/".$period["id"]."/$q/";
                                        if (is_dir($directory) && $files = scandir($directory, 1))
                                        {
                                            for ($f = 0; $f < count($files); $f++)
                                            {
                                                // get the customer ID from the file name (ID is pre .pdf file extension)
                                                $file = $files[$f];
                                                $file_customer_id = pathinfo($file, PATHINFO_FILENAME);

                                                // verify the customer ID is a number
                                                if ((is_numeric($file_customer_id) && verifyCustomer($conn, $file_customer_id)) && (($file_customer_id == $customer_id) || $customer_id == -1))
                                                {
                                                    // get the cusotmer's name
                                                    $customer_details = getCustomerDetails($conn, $file_customer_id);
                                                    $customer_name = $customer_details["name"];

                                                    // get all reports saved for this customer for this period and quarter
                                                    $customer_directory = "../../local_data/caseloads/quarterly_billing/".$period["id"]."/$q/$file_customer_id/";
                                                    $customer_files = scandir($customer_directory, 1);
                                                    for ($cf = 0; $cf < count($customer_files); $cf++)
                                                    {
                                                        // get the file
                                                        $customer_file = $customer_files[$cf];
                                                        $file_name = pathinfo($customer_file, PATHINFO_FILENAME);
                                                        $file_ext = pathinfo($customer_file, PATHINFO_EXTENSION);
                                                        
                                                        // ensure that the file is a pdf
                                                        if ($file_ext == "pdf")
                                                        {
                                                            // build the actions column
                                                            $actions = "<div class='d-flex justify-content-end'>
                                                                <button class='btn btn-primary btn-sm' type='button' onclick='getViewDistrictReport($file_customer_id, ".$period["id"].", $q, \"".$file_name."\", 0);'><i class='fa-solid fa-eye'></i></button>
                                                            </div>";

                                                            $temp = [];
                                                            $temp["year"] = getPeriodName($conn, $period["id"]);
                                                            $temp["customer"] = $customer_name;
                                                            $temp["title"] = "SPED District Quarterly Billing";
                                                            $temp["file"] = getPeriodName($conn, $period["id"])." SPED District Q$q Billing";
                                                            $temp["created"] = "-";
                                                            $temp["status"] = "<span class=\"badge bg-danger px-3 py-2 fst-italic\">Outdated</span>";
                                                            $temp["actions"] = $actions;
                                                            $data[] = $temp;
                                                        }
                                                    }
                                                }
                                            }
                                        }

                                        // get all reports created for the districts
                                        $directory = "../../local_data/caseloads/internal_quarterly_billing/".$period["id"]."/$q/";
                                        if (is_dir($directory) && $files = scandir($directory, 1))
                                        {
                                            for ($f = 0; $f < count($files); $f++)
                                            {
                                                // get the customer ID from the file name (ID is pre .pdf file extension)
                                                $file = $files[$f];
                                                $file_customer_id = pathinfo($file, PATHINFO_FILENAME);

                                                // verify the customer is valid
                                                if ((is_numeric($file_customer_id) && verifyCustomer($conn, $file_customer_id)) && (($file_customer_id == $customer_id) || $customer_id == -1))
                                                {
                                                    // get the cusotmer's name
                                                    $customer_details = getCustomerDetails($conn, $file_customer_id);
                                                    $customer_name = $customer_details["name"];

                                                    // get all reports saved for this customer for this period and quarter
                                                    $customer_directory = "../../local_data/caseloads/internal_quarterly_billing/".$period["id"]."/$q/$file_customer_id/";
                                                    $customer_files = scandir($customer_directory, 1);
                                                    for ($cf = 0; $cf < count($customer_files); $cf++)
                                                    {
                                                        // get the file
                                                        $customer_file = $customer_files[$cf];
                                                        $file_name = pathinfo($customer_file, PATHINFO_FILENAME);
                                                        $file_ext = pathinfo($customer_file, PATHINFO_EXTENSION);
                                                        
                                                        // ensure that the file is a pdf
                                                        if ($file_ext == "pdf")
                                                        {
                                                            // build the actions column
                                                            $actions = "<div class='d-flex justify-content-end'>
                                                                <button class='btn btn-primary btn-sm' type='button' onclick='getViewDistrictReport($file_customer_id, ".$period["id"].", $q, \"".$file_name."\", 1);'><i class='fa-solid fa-eye'></i></button>
                                                            </div>";

                                                            $temp = [];
                                                            $temp["year"] = getPeriodName($conn, $period["id"]);
                                                            $temp["customer"] = $customer_name;
                                                            $temp["title"] = "<i><b>INTERNAL</b></i> SPED District Quarterly Billing";
                                                            $temp["file"] = getPeriodName($conn, $period["id"])." SPED District Q$q Billing";
                                                            $temp["created"] = "-";
                                                            $temp["status"] = "<span class=\"badge bg-danger px-3 py-2 fst-italic\">Outdated</span>";
                                                            $temp["actions"] = $actions;
                                                            $data[] = $temp;
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
        }

        // disconnect from the database
        mysqli_close($conn);

        // send data to be printed
        echo json_encode($data);
    }
?>