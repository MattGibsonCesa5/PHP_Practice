<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get additional required files
        include("../../includes/config.php");
        include("../../includes/functions.php");
        include("../../getSettings.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "BUILD_SERVICE_CONTRACTS") || checkUserPermission($conn, "BUILD_QUARTERLY_INVOICES"))
        {
            // get POSTed parameter(s)
            if (isset($_POST["customer_id"]) && $_POST["customer_id"] <> "") { $customer_id = $_POST["customer_id"]; } else { $customer_id = null; }
            if (isset($_POST["type"]) && $_POST["type"] <> "") { $type = $_POST["type"]; } else { $type = null; }            
            
            // contract page 1
            if (isset($_POST["GS01"]) && $_POST["GS01"] <> "") { $GS01 = $_POST["GS01"]; } else { $GS01 = null; }
            if (isset($_POST["GS02"]) && $_POST["GS02"] <> "") { $GS02 = $_POST["GS02"]; } else { $GS02 = null; }
            if (isset($_POST["SI01"]) && $_POST["SI01"] <> "") { $SI01 = $_POST["SI01"]; } else { $SI01 = null; }
            if (isset($_POST["SI02"]) && $_POST["SI02"] <> "") { $SI02 = $_POST["SI02"]; } else { $SI02 = null; }
            if (isset($_POST["SI03"]) && $_POST["SI03"] <> "") { $SI03 = $_POST["SI03"]; } else { $SI03 = null; }
            if (isset($_POST["SI04"]) && $_POST["SI04"] <> "") { $SI04 = $_POST["SI04"]; } else { $SI04 = null; }
            if (isset($_POST["CT01"]) && $_POST["CT01"] <> "") { $CT01 = $_POST["CT01"]; } else { $CT01 = null; }
            if (isset($_POST["CT02"]) && $_POST["CT02"] <> "") { $CT02 = $_POST["CT02"]; } else { $CT02 = null; }
            if (isset($_POST["SH01"]) && $_POST["SH01"] <> "") { $SH01 = $_POST["SH01"]; } else { $SH01 = null; }
            if (isset($_POST["ET01"]) && $_POST["ET01"] <> "") { $ET01 = $_POST["ET01"]; } else { $ET01 = null; }
            if (isset($_POST["TS01"]) && $_POST["TS01"] <> "") { $TS01 = $_POST["TS01"]; } else { $TS01 = null; }
            if (isset($_POST["SB01"]) && $_POST["SB01"] <> "") { $SB01 = $_POST["SB01"]; } else { $SB01 = null; }
            if (isset($_POST["LS01"]) && $_POST["LS01"] <> "") { $LS01 = $_POST["LS01"]; } else { $LS01 = null; }
            if (isset($_POST["OTHER1"]) && $_POST["OTHER1"] <> "") { $OTHER1 = $_POST["OTHER1"]; } else { $OTHER1 = null; }
            if (isset($_POST["page1_comment"]) && $_POST["page1_comment"] <> "") { $page1_comment = $_POST["page1_comment"]; } else { $page1_comment = null; }

            // contract page 2
            if (isset($_POST["SP01"]) && $_POST["SP01"] <> "") { $SP01 = $_POST["SP01"]; } else { $SP01 = null; }
            if (isset($_POST["SP02"]) && $_POST["SP02"] <> "") { $SP02 = $_POST["SP02"]; } else { $SP02 = null; }
            if (isset($_POST["SP03"]) && $_POST["SP03"] <> "") { $SP03 = $_POST["SP03"]; } else { $SP03 = null; }
            if (isset($_POST["SP04"]) && $_POST["SP04"] <> "") { $SP04 = $_POST["SP04"]; } else { $SP04 = null; }
            if (isset($_POST["SP05"]) && $_POST["SP05"] <> "") { $SP05 = $_POST["SP05"]; } else { $SP05 = null; }
            if (isset($_POST["SP06"]) && $_POST["SP06"] <> "") { $SP06 = $_POST["SP06"]; } else { $SP06 = null; }
            if (isset($_POST["SP07"]) && $_POST["SP07"] <> "") { $SP07 = $_POST["SP07"]; } else { $SP07 = null; }
            if (isset($_POST["SP08"]) && $_POST["SP08"] <> "") { $SP08 = $_POST["SP08"]; } else { $SP08 = null; }
            if (isset($_POST["SP09"]) && $_POST["SP09"] <> "") { $SP09 = $_POST["SP09"]; } else { $SP09 = null; }
            if (isset($_POST["SP10"]) && $_POST["SP10"] <> "") { $SP10 = $_POST["SP10"]; } else { $SP10 = null; }
            if (isset($_POST["SP11"]) && $_POST["SP11"] <> "") { $SP11 = $_POST["SP11"]; } else { $SP11 = null; }
            if (isset($_POST["SP12"]) && $_POST["SP12"] <> "") { $SP12 = $_POST["SP12"]; } else { $SP12 = null; }
            if (isset($_POST["SP13"]) && $_POST["SP13"] <> "") { $SP13 = $_POST["SP13"]; } else { $SP13 = null; }
            if (isset($_POST["SP14"]) && $_POST["SP14"] <> "") { $SP14 = $_POST["SP14"]; } else { $SP14 = null; }
            if (isset($_POST["SP15A"]) && $_POST["SP15A"] <> "") { $SP15A = $_POST["SP15A"]; } else { $SP15A = null; }
            if (isset($_POST["SP15B"]) && $_POST["SP15B"] <> "") { $SP15B = $_POST["SP15B"]; } else { $SP15B = null; }
            if (isset($_POST["SP15C"]) && $_POST["SP15C"] <> "") { $SP15C = $_POST["SP15C"]; } else { $SP15C = null; }
            if (isset($_POST["SP16"]) && $_POST["SP16"] <> "") { $SP16 = $_POST["SP16"]; } else { $SP16 = null; }
            if (isset($_POST["SP17"]) && $_POST["SP17"] <> "") { $SP17 = $_POST["SP17"]; } else { $SP17 = null; }
            if (isset($_POST["SP18"]) && $_POST["SP18"] <> "") { $SP18 = $_POST["SP18"]; } else { $SP18 = null; }
            if (isset($_POST["SP19"]) && $_POST["SP19"] <> "") { $SP19 = $_POST["SP19"]; } else { $SP19 = null; }
            if (isset($_POST["AE01"]) && $_POST["AE01"] <> "") { $AE01 = $_POST["AE01"]; } else { $AE01 = null; }
            if (isset($_POST["AE02"]) && $_POST["AE02"] <> "") { $AE02 = $_POST["AE02"]; } else { $AE02 = null; }
            if (isset($_POST["AE03"]) && $_POST["AE03"] <> "") { $AE03 = $_POST["AE03"]; } else { $AE03 = null; }
            if (isset($_POST["AE04"]) && $_POST["AE04"] <> "") { $AE04 = $_POST["AE04"]; } else { $AE04 = null; }
            if (isset($_POST["AE05"]) && $_POST["AE05"] <> "") { $AE05 = $_POST["AE05"]; } else { $AE05 = null; }
            if (isset($_POST["AE06"]) && $_POST["AE06"] <> "") { $AE06 = $_POST["AE06"]; } else { $AE06 = null; }
            if (isset($_POST["AE07"]) && $_POST["AE07"] <> "") { $AE07 = $_POST["AE07"]; } else { $AE07 = null; }
            if (isset($_POST["AE08"]) && $_POST["AE08"] <> "") { $AE08 = $_POST["AE08"]; } else { $AE08 = null; }
            if (isset($_POST["SN01"]) && $_POST["SN01"] <> "") { $SN01 = $_POST["SN01"]; } else { $SN01 = null; }
            if (isset($_POST["SPOTHER1"]) && $_POST["SPOTHER1"] <> "") { $SPOTHER1 = $_POST["SPOTHER1"]; } else { $SPOTHER1 = null; }
            if (isset($_POST["SPOTHER2"]) && $_POST["SPOTHER2"] <> "") { $SPOTHER2 = $_POST["SPOTHER2"]; } else { $SPOTHER2 = null; }
            if (isset($_POST["SPOTHER3"]) && $_POST["SPOTHER3"] <> "") { $SPOTHER3 = $_POST["SPOTHER3"]; } else { $SPOTHER3 = null; }
            if (isset($_POST["page2_comment"]) && $_POST["page2_comment"] <> "") { $page2_comment = $_POST["page2_comment"]; } else { $page2_comment = null; }

            // store the service contract ID type locally
            $contract_type_id = null;
            $log_contract_display = "";
            if ($type == "SC" && checkUserPermission($conn, "BUILD_SERVICE_CONTRACTS")) 
            { 
                $contract_type_id = SERVICE_CONTRACT_TYPE_ID; 
                $log_contract_display = "service contract";
            }
            else if ($type == "QI" && checkUserPermission($conn, "BUILD_QUARTERLY_INVOICES")) 
            { 
                $contract_type_id = QUARTERLY_INVOICE_TYPE_ID; 
                $log_contract_display = "quarterly invoice";
            }

            if ($customer_id != null)
            {
                if ($contract_type_id != null)
                {
                    // verify customer exists
                    $checkCustomer = mysqli_prepare($conn, "SELECT id, name FROM customers WHERE id=?");
                    mysqli_stmt_bind_param($checkCustomer, "i", $customer_id);
                    if (mysqli_stmt_execute($checkCustomer))
                    {
                        $checkCustomerResult = mysqli_stmt_get_result($checkCustomer);
                        if (mysqli_num_rows($checkCustomerResult) > 0) // customer exists; continue
                        {
                            // store customer name locally
                            $customer_name = mysqli_fetch_array($checkCustomerResult)["name"];

                            // check to see if we have this customer's service contract settings set already
                            $checkContractSettings = mysqli_prepare($conn, "SELECT id FROM customer_contracts WHERE customer_id=? AND period_id=? AND contract_type_id=?");
                            mysqli_stmt_bind_param($checkContractSettings, "iii", $customer_id, $GLOBAL_SETTINGS["active_period"], $contract_type_id);
                            if (mysqli_stmt_execute($checkContractSettings))
                            {
                                $checkContractSettingsResult = mysqli_stmt_get_result($checkContractSettings);
                                if (mysqli_num_rows($checkContractSettingsResult) > 0) // settings already exist; update current record
                                {
                                    $updateSettings = mysqli_prepare($conn, "UPDATE customer_contracts SET GS01=?, GS02=?, SI01=?, SI02=?, SI03=?, SI04=?, CT01=?, CT02=?,
                                                                                                            SH01=?, ET01=?, TS01=?, SB01=?, LS01=?, OTHER1=?, page1_comment=?,
                                                                                                            SP01=?, SP02=?, SP03=?, SP04=?, SP05=?, SP06=?, SP07=?, SP08=?,
                                                                                                            SP09=?, SP10=?, SP11=?, SP12=?, SP13=?, SP14=?, SP15A=?, SP15B=?, SP15C=?,
                                                                                                            SP16=?, SP17=?, SP18=?, SP19=?,
                                                                                                            AE01=?, AE02=?, AE03=?, AE04=?, AE05=?, AE06=?, AE07=?, AE08=?,
                                                                                                            SN01=?, SPOTHER1=?, SPOTHER2=?, SPOTHER3=?, page2_comment=?
                                                                            WHERE customer_id=? AND period_id=? AND contract_type_id=?");
                                    mysqli_stmt_bind_param($updateSettings, "sssssssssssssssssssssssssssssssssssssssssssssssssiii", $GS01, $GS02, $SI01, $SI02, $SI03, $SI04, $CT01, $CT02,
                                                                                                                                    $SH01, $ET01, $TS01, $SB01, $LS01, $OTHER1, $page1_comment,
                                                                                                                                    $SP01, $SP02, $SP03, $SP04, $SP05, $SP06, $SP07, $SP08,
                                                                                                                                    $SP09, $SP10, $SP11, $SP12, $SP13, $SP14, $SP15A, $SP15B, $SP15C,
                                                                                                                                    $SP16, $SP17, $SP18, $SP19,
                                                                                                                                    $AE01, $AE02, $AE03, $AE04, $AE05, $AE06, $AE07, $AE08,
                                                                                                                                    $SN01, $SPOTHER1, $SPOTHER2, $SPOTHER3, $page2_comment,
                                                                                                                                    $customer_id, $GLOBAL_SETTINGS["active_period"], $contract_type_id);
                                    if (mysqli_stmt_execute($updateSettings)) { echo "<span class=\"log-success\">Successfully</span> updated the build $log_contract_display settings for $customer_name."; }
                                    else { echo "<span class=\"log-fail\">Failed</span> to update the build $log_contract_display settings for $customer_name."; }
                                }
                                else // settings do not exist; insert new record
                                {
                                    $insertSettings = mysqli_prepare($conn, "INSERT INTO customer_contracts (
                                                                                GS01, GS02, SI01, SI02, SI03, SI04, CT01, CT02,
                                                                                SH01, ET01, TS01, SB01, LS01, OTHER1, page1_comment,
                                                                                SP01, SP02, SP03, SP04, SP05, SP06, SP07, SP08,
                                                                                SP09, SP10, SP11, SP12, SP13, SP14, SP15A, SP15B, SP15C,
                                                                                SP16, SP17, SP18, SP19,
                                                                                AE01, AE02, AE03, AE04, AE05, AE06, AE07, AE08,
                                                                                SN01, SPOTHER1, SPOTHER2, SPOTHER3, page2_comment,
                                                                                customer_id, period_id, contract_type_id
                                                                            ) VALUES (
                                                                                ?, ?, ?, ?, ?, ?, ?, ?,
                                                                                ?, ?, ?, ?, ?, ?, ?,
                                                                                ?, ?, ?, ?, ?, ?, ?, ?,
                                                                                ?, ?, ?, ?, ?, ?, ?, ?, ?,
                                                                                ?, ?, ?, ?,
                                                                                ?, ?, ?, ?, ?, ?, ?, ?,
                                                                                ?, ?, ?, ?, ?,
                                                                                ?, ?, ?
                                                                            );");
                                    mysqli_stmt_bind_param($insertSettings, "sssssssssssssssssssssssssssssssssssssssssssssssssiii", $GS01, $GS02, $SI01, $SI02, $SI03, $SI04, $CT01, $CT02,
                                                                            $SH01, $ET01, $TS01, $SB01, $LS01, $OTHER1, $page1_comment,
                                                                            $SP01, $SP02, $SP03, $SP04, $SP05, $SP06, $SP07, $SP08,
                                                                            $SP09, $SP10, $SP11, $SP12, $SP13, $SP14, $SP15A, $SP15B, $SP15C,
                                                                            $SP16, $SP17, $SP18, $SP19,
                                                                            $AE01, $AE02, $AE03, $AE04, $AE05, $AE06, $AE07, $AE08,
                                                                            $SN01, $SPOTHER1, $SPOTHER2, $SPOTHER3, $page2_comment,
                                                                            $customer_id, $GLOBAL_SETTINGS["active_period"], $contract_type_id);
                                    if (mysqli_stmt_execute($insertSettings)) { echo "<span class=\"log-success\">Successfully</span> updated the build $log_contract_display settings for $customer_name."; }
                                    else { echo "<span class=\"log-fail\">Failed</span> to update the build $log_contract_display settings for $customer_name."; }
                                }
                            }
                            else { echo "<span class=\"log-fail\">Failed</span> to update the build $log_contract_display settings for $customer_name."; }
                        }
                        else { echo "<span class=\"log-fail\">Failed</span> to update the build $log_contract_display settings. Selected customer does not exist!."; }
                    }
                    else { echo "<span class=\"log-fail\">Failed</span> to update the build $log_contract_display settings. An unexpected error has occurred! Please try again later.<br>"; }
                }
                else { echo "<span class=\"log-fail\">Failed</span> to update the build contract settings. Contract type not found. Please try again later."; }
            }
            else { echo "<span class=\"log-fail\">Failed</span> to update the build contract settings. Customer not found. Please try again later."; }
        }
        else { echo "<span class=\"log-fail\">Failed</span> to update build settings. Your account does not have permission to build contracts."; }

        // disconnect from the database
        mysqli_close($conn);
    }
?>