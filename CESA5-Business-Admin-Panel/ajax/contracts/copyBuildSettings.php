<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        if (isset($_SESSION["role"]) && $_SESSION["role"] == 1)
        {
            include("../../includes/config.php");
            include("../../getSettings.php");

            // get POSTed parameter(s)
            if (isset($_POST["from"]) && $_POST["from"] <> "") { $from = $_POST["from"]; } else { $from = null; }
            if (isset($_POST["to"]) && $_POST["to"] <> "") { $to = $_POST["to"]; } else { $to = null; }            

            // store the service contract ID type locally for the from
            $from_contract_type_id = $from_contract_type_field = null;
            $from_log_contract_display = "";
            if ($from == "SC") 
            { 
                $from_contract_type_id = SERVICE_CONTRACT_TYPE_ID; 
                $from_contract_type_field = "build_service_contract";
                $from_log_contract_display = "service contract";
            }
            else if ($from == "QI") 
            { 
                $from_contract_type_id = QUARTERLY_INVOICE_TYPE_ID; 
                $from_contract_type_field = "build_quarterly_invoice";
                $from_log_contract_display = "quarterly invoice";
            }

            // store the service contract ID type locally for the to
            $to_contract_type_id = $to_contract_type_field = null;
            $to_log_contract_display = "";
            if ($to == "SC") 
            { 
                $to_contract_type_id = SERVICE_CONTRACT_TYPE_ID; 
                $to_contract_type_field = "build_service_contract";
                $to_log_contract_display = "service contract";
            }
            else if ($to == "QI") 
            { 
                $to_contract_type_id = QUARTERLY_INVOICE_TYPE_ID; 
                $to_contract_type_field = "build_quarterly_invoice";
                $to_log_contract_display = "quarterly invoice";
            }

            if (($from_contract_type_id != null && $to_contract_type_id != null) && ($from_contract_type_field == "build_service_contract" || $from_contract_type_field == "build_quarterly_invoice") && ($to_contract_type_field == "build_service_contract" || $to_contract_type_field == "build_quarterly_invoice"))
            {
                // connect to the database
                $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

                if (mysqli_query($conn, "UPDATE customers SET `$to_contract_type_field`=`$from_contract_type_field` WHERE active=1")) 
                { 
                    echo "<span class=\"log-success\">Successfully</span> copied build settings for $from_log_contract_display to $to_log_contract_display.<br>";
                    
                    // delete all current contract data for the contract type we are copying into only for customers that have existing settings that will be replaced
                    $getExistingContractSettings = mysqli_prepare($conn, "SELECT id FROM `customer_contracts` WHERE contract_type_id=? AND customer_id IN (
                                                                            SELECT customer_id FROM customer_contracts 
                                                                            WHERE contract_type_id=?
                                                                        );");
                    mysqli_stmt_bind_param($getExistingContractSettings, "ii", $to_contract_type_id, $from_contract_type_id);
                    if (mysqli_stmt_execute($getExistingContractSettings)) // successfully cleared contract settings
                    {
                        $getExistingContractSettingsResults = mysqli_stmt_get_result($getExistingContractSettings);
                        if (mysqli_num_rows($getExistingContractSettingsResults) > 0)
                        {
                            while ($entry = mysqli_fetch_array($getExistingContractSettingsResults))
                            {
                                $primary_id = $entry["id"];

                                // delete existing entry
                                $deleteCustomerContractSetting = mysqli_prepare($conn, "DELETE FROM customer_contracts WHERE id=?");
                                mysqli_stmt_bind_param($deleteCustomerContractSetting, "i", $primary_id);
                                mysqli_stmt_execute($deleteCustomerContractSetting);
                            }
                        }
                    }

                    // attempt to copy contract settings from one contract type to the other
                    try
                    {
                        // copy customer contract settings
                        $copyContractSettings = mysqli_prepare($conn, "INSERT INTO customer_contracts (customer_id, contract_type_id, GS01, GS02, SI01, SI02, SI03, SI04, CT01, CT02, SH01, ET01, TS01, SB01, LS01, OTHER1,
                                                                                                        SP01, SP02, SP03, SP04, SP05, SP06, SP07, SP08, SP09, SP10, SP11, SP12, SP13, SP14, SP15A, SP15B, SP15C, SP16, SP17, SP18, SP19,
                                                                                                        AE01, AE02, AE03, AE04, AE05, AE06, AE07, AE08, SN01, SPOTHER1, SPOTHER2, SPOTHER3, page1_comment, page2_comment, period_id)
                                                                                                SELECT customer_id, ?, GS01, GS02, SI01, SI02, SI03, SI04, CT01, CT02, SH01, ET01, TS01, SB01, LS01, OTHER1,
                                                                                                        SP01, SP02, SP03, SP04, SP05, SP06, SP07, SP08, SP09, SP10, SP11, SP12, SP13, SP14, SP15A, SP15B, SP15C, SP16, SP17, SP18, SP19,
                                                                                                        AE01, AE02, AE03, AE04, AE05, AE06, AE07, AE08, SN01, SPOTHER1, SPOTHER2, SPOTHER3, page1_comment, page2_comment, period_id
                                                                                                FROM customer_contracts WHERE contract_type_id=?");
                        mysqli_stmt_bind_param($copyContractSettings, "ii", $to_contract_type_id, $from_contract_type_id);
                        if (!mysqli_stmt_execute($copyContractSettings)) { echo "<span class=\"log-fail\">Failed</span> to copy contract settings from $from_log_contract_display to $to_log_contract_display.<br>"; }
                    }
                    catch (Exception $e) // duplicate index entries found
                    {
                        // duplicate found
                        // TODO - handle error
                    }
                }
                else { echo "<span class=\"log-fail\">Failed</span> to copy build settings for $from_log_contract_display to $to_log_contract_display.<br>"; }

                // disconnect from the database
                mysqli_close($conn);
            }
            else { echo "<span class=\"log-fail\">Failed</span> to copy build contract settings. You must select both a valid contract type to copy from and a valid contract type to copy to.<br>"; }
        }
    }
?>