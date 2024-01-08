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

            // build the suffix/type display
            if ($type == "SC" && checkUserPermission($conn, "BUILD_SERVICE_CONTRACTS")) { $contract_type_id = SERVICE_CONTRACT_TYPE_ID; }
            else if ($type == "QI" && checkUserPermission($conn, "BUILD_QUARTERLY_INVOICES")) { $contract_type_id = QUARTERLY_INVOICE_TYPE_ID; }
            else 
            { 
                $type = null;
                $contract_type_id = null; 
            }
            
            // build the modal title
            $modal_title = "";
            if ($type == "SC") { $modal_title = "Service Contract"; }
            else if ($type == "QI") { $modal_title = "Quarterly Invoice"; }

            if ($customer_id != null && $contract_type_id != null && $type != null)
            {
                // verify customer exists
                $checkCustomer = mysqli_prepare($conn, "SELECT id FROM customers WHERE id=?");
                mysqli_stmt_bind_param($checkCustomer, "i", $customer_id);
                if (mysqli_stmt_execute($checkCustomer))
                {
                    $checkCustomerResult = mysqli_stmt_get_result($checkCustomer);
                    if (mysqli_num_rows($checkCustomerResult) > 0) // customer exists; continue
                    {
                        // get customer's contract details
                        $getContractDetails = mysqli_prepare($conn, "SELECT * FROM customer_contracts WHERE customer_id=? AND period_id=? AND contract_type_id=?");
                        mysqli_stmt_bind_param($getContractDetails, "iii", $customer_id, $GLOBAL_SETTINGS["active_period"], $contract_type_id);
                        if (mysqli_stmt_execute($getContractDetails))
                        {
                            $getContractDetailsResults = mysqli_stmt_get_result($getContractDetails);
                            if (mysqli_num_rows($getContractDetailsResults) > 0) // details found; store currently set values
                            {
                                $contractDetails = mysqli_fetch_array($getContractDetailsResults);
                                $GS01 = $contractDetails["GS01"];
                                $GS02 = $contractDetails["GS02"];
                                $SI01 = $contractDetails["SI01"];
                                $SI02 = $contractDetails["SI02"];
                                $SI03 = $contractDetails["SI03"];
                                $SI04 = $contractDetails["SI04"];
                                $CT01 = $contractDetails["CT01"];
                                $CT02 = $contractDetails["CT02"];
                                $SH01 = $contractDetails["SH01"];
                                $ET01 = $contractDetails["ET01"];
                                $TS01 = $contractDetails["TS01"];
                                $SB01 = $contractDetails["SB01"];
                                $LS01 = $contractDetails["LS01"];
                                $OTHER1 = $contractDetails["OTHER1"];
                                $SP01 = $contractDetails["SP01"];
                                $SP02 = $contractDetails["SP02"];
                                $SP03 = $contractDetails["SP03"];
                                $SP04 = $contractDetails["SP04"];
                                $SP05 = $contractDetails["SP05"]; 
                                $SP06 = $contractDetails["SP06"]; 
                                $SP07 = $contractDetails["SP07"]; 
                                $SP08 = $contractDetails["SP08"]; 
                                $SP09 = $contractDetails["SP09"]; 
                                $SP10 = $contractDetails["SP10"]; 
                                $SP11 = $contractDetails["SP11"]; 
                                $SP12 = $contractDetails["SP12"]; 
                                $SP13 = $contractDetails["SP13"]; 
                                $SP14 = $contractDetails["SP14"]; 
                                $SP15A = $contractDetails["SP15A"]; 
                                $SP15B = $contractDetails["SP15B"]; 
                                $SP15C = $contractDetails["SP15C"]; 
                                $SP16 = $contractDetails["SP16"]; 
                                $SP17 = $contractDetails["SP17"]; 
                                $SP18 = $contractDetails["SP18"]; 
                                $SP19 = $contractDetails["SP19"]; 
                                $AE01 = $contractDetails["AE01"];
                                $AE02 = $contractDetails["AE02"];
                                $AE03 = $contractDetails["AE03"];
                                $AE04 = $contractDetails["AE04"];
                                $AE05 = $contractDetails["AE05"];
                                $AE06 = $contractDetails["AE06"];
                                $AE07 = $contractDetails["AE07"];
                                $AE08 = $contractDetails["AE08"];
                                $SN01 = $contractDetails["SN01"];
                                $SPOTHER1 = $contractDetails["SPOTHER1"];
                                $SPOTHER2 = $contractDetails["SPOTHER2"];
                                $SPOTHER3 = $contractDetails["SPOTHER3"];
                                $page1_comment = $contractDetails["page1_comment"];
                                $page2_comment = $contractDetails["page2_comment"];
                            }
                            else // details not found; set to default values
                            {
                                $GS01 = "GS01";
                                $GS02 = "GS02";
                                $SI01 = "SI01";
                                $SI02 = "SI02";
                                $SI03 = "SI03";
                                $SI04 = "SI04";
                                $CT01 = "CT01";
                                $CT02 = "CT02";
                                $SH01 = "SH01";
                                $ET01 = "ET01";
                                $TS01 = "TS01";
                                $SB01 = "SB01";
                                $LS01 = "LS01";
                                $OTHER1 = "OTHER1";
                                $SP01 = "SP01";
                                $SP02 = "SP02";
                                $SP03 = "SP03";
                                $SP04 = "SP04";
                                $SP05 = "SP05"; 
                                $SP06 = "SP06"; 
                                $SP07 = "SP07"; 
                                $SP08 = "SP08"; 
                                $SP09 = "SP09"; 
                                $SP10 = "SP10U"; 
                                $SP11 = "SP11"; 
                                $SP12 = "SP12"; 
                                $SP13 = "SP13"; 
                                $SP14 = "SP14"; 
                                $SP15A = "SP15"; 
                                $SP15B = "SP15B"; 
                                $SP15C = "SP15C"; 
                                $SP16 = "SP16"; 
                                $SP17 = "SP17"; 
                                $SP18 = "SP18"; 
                                $SP19 = "SP19"; 
                                $AE01 = "AE01";
                                $AE02 = "AE02";
                                $AE03 = "AE03";
                                $AE04 = "AE04";
                                $AE05 = "AE05";
                                $AE06 = "AE06";
                                $AE07 = "AE07";
                                $AE08 = "AE08";
                                $SN01 = "SN01";
                                $SPOTHER1 = "SPOTHER1";
                                $SPOTHER2 = "SPOTHER2";
                                $SPOTHER3 = "SPOTHER3";
                                $page1_comment = "";
                                $page2_comment = "";
                            }

                            // get a list of all services; store services in array
                            $services = [];
                            $getServices = mysqli_query($conn, "SELECT id, name FROM services ORDER BY name ASC, id ASC");
                            if (mysqli_num_rows($getServices) > 0) // services found
                            {
                                while ($service = mysqli_fetch_array($getServices))
                                {
                                    $services[] = $service;
                                }
                            }

                            // get a list of all other services; store other services in array
                            $other_services = [];
                            $getOtherServices = mysqli_query($conn, "SELECT id, name FROM services_other ORDER BY name ASC, id ASC");
                            if (mysqli_num_rows($getOtherServices) > 0) // other services found
                            {
                                while ($other_service = mysqli_fetch_array($getOtherServices))
                                {
                                    $other_services[] = $other_service;
                                }
                            }

                            ?>
                                <!-- Build Service Contract Modal -->
                                <div class="modal fade" tabindex="-1" role="dialog" id="<?php echo $type; ?>-Build-Modal" data-bs-backdrop="static" aria-labelledby="<?php echo $type; ?>-Build-Modal-Label" aria-hidden="true">
                                    <div class="modal-dialog" role="document">
                                        <div class="modal-content">
                                            <div class="modal-header primary-modal-header">
                                                <h5 class="modal-title primary-modal-title" id="<?php echo $type; ?>-Build-Modal-Label">Build <?php echo $modal_title; ?></h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>

                                            <div class="modal-body">
                                                <div class="accordion" id="<?php echo $type; ?>-accordion">
                                                    <!-- Contract Page 1 of 2 -->
                                                    <div class="accordion-item">
                                                        <h2 class="accordion-header" id="<?php echo $type; ?>-page1-heading">
                                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#<?php echo $type; ?>-page1" aria-expanded="false" aria-controls="<?php echo $type; ?>-page1">
                                                                Contract Page 1 of 2
                                                            </button>
                                                        </h2>
                                                        <div id="<?php echo $type; ?>-page1" class="accordion-collapse collapse" aria-labelledby="<?php echo $type; ?>-page1-heading">
                                                            <div class="accordion-body">
                                                                <!-- GS01 -->
                                                                <div class="row d-flex justify-content-center align-items-center mb-2">
                                                                    <div class="col-3">
                                                                        <label for="GS01"><b>GS01:</b></label>
                                                                    </div>
                                                                    <div class="col-9">
                                                                        <select class="form-select" id="GS01" name="GS01">
                                                                            <option></option>
                                                                            <?php
                                                                                for ($s = 0; $s < count($services); $s++)
                                                                                {
                                                                                    if ($GS01 == $services[$s]["id"]) { echo "<option value='".$services[$s]["id"]."' selected>".$services[$s]["name"]."</option>"; }
                                                                                    else { echo "<option value='".$services[$s]["id"]."'>".$services[$s]["name"]."</option>"; }
                                                                                }
                                                                            ?>
                                                                        </select>
                                                                    </div>
                                                                </div>

                                                                <!-- GS02 -->
                                                                <div class="row d-flex justify-content-center align-items-center mb-2">
                                                                    <div class="col-3">
                                                                        <label for="GS02"><b>GS02:</b></label>
                                                                    </div>
                                                                    <div class="col-9">
                                                                        <select class="form-select" id="GS02" name="GS02">
                                                                            <option></option>
                                                                            <?php
                                                                                for ($s = 0; $s < count($services); $s++)
                                                                                {
                                                                                    if ($GS02 == $services[$s]["id"]) { echo "<option value='".$services[$s]["id"]."' selected>".$services[$s]["name"]."</option>"; }
                                                                                    else { echo "<option value='".$services[$s]["id"]."'>".$services[$s]["name"]."</option>"; }
                                                                                }
                                                                            ?>
                                                                        </select>
                                                                    </div>
                                                                </div>

                                                                <!-- SI01 -->
                                                                <div class="row d-flex justify-content-center align-items-center mb-2">
                                                                    <div class="col-3">
                                                                        <label for="SI01"><b>SI01:</b></label>
                                                                    </div>
                                                                    <div class="col-9">
                                                                        <select class="form-select" id="SI01" name="SI01">
                                                                            <option></option>
                                                                            <?php
                                                                                for ($s = 0; $s < count($services); $s++)
                                                                                {
                                                                                    if ($SI01 == $services[$s]["id"]) { echo "<option value='".$services[$s]["id"]."' selected>".$services[$s]["name"]."</option>"; }
                                                                                    else { echo "<option value='".$services[$s]["id"]."'>".$services[$s]["name"]."</option>"; }
                                                                                }
                                                                            ?>
                                                                        </select>
                                                                    </div>
                                                                </div>

                                                                <!-- SI02 -->
                                                                <div class="row d-flex justify-content-center align-items-center mb-2">
                                                                    <div class="col-3">
                                                                        <label for="SI02"><b>SI02:</b></label>
                                                                    </div>
                                                                    <div class="col-9">
                                                                        <select class="form-select" id="SI02" name="SI02">
                                                                            <option></option>
                                                                            <?php
                                                                                for ($s = 0; $s < count($services); $s++)
                                                                                {
                                                                                    if ($SI02 == $services[$s]["id"]) { echo "<option value='".$services[$s]["id"]."' selected>".$services[$s]["name"]."</option>"; }
                                                                                    else { echo "<option value='".$services[$s]["id"]."'>".$services[$s]["name"]."</option>"; }
                                                                                }
                                                                            ?>
                                                                        </select>
                                                                    </div>
                                                                </div>

                                                                <!-- SI03 -->
                                                                <div class="row d-flex justify-content-center align-items-center mb-2">
                                                                    <div class="col-3">
                                                                        <label for="SI03"><b>SI03:</b></label>
                                                                    </div>
                                                                    <div class="col-9">
                                                                        <select class="form-select" id="SI03" name="SI03">
                                                                            <option></option>
                                                                            <?php
                                                                                for ($s = 0; $s < count($services); $s++)
                                                                                {
                                                                                    if ($SI03 == $services[$s]["id"]) { echo "<option value='".$services[$s]["id"]."' selected>".$services[$s]["name"]."</option>"; }
                                                                                    else { echo "<option value='".$services[$s]["id"]."'>".$services[$s]["name"]."</option>"; }
                                                                                }
                                                                            ?>
                                                                        </select>
                                                                    </div>
                                                                </div>

                                                                <!-- SI04 -->
                                                                <div class="row d-flex justify-content-center align-items-center mb-2">
                                                                    <div class="col-3">
                                                                        <label for="SI04"><b>SI04:</b></label>
                                                                    </div>
                                                                    <div class="col-9">
                                                                        <select class="form-select" id="SI04" name="SI04">
                                                                            <option></option>
                                                                            <?php
                                                                                for ($s = 0; $s < count($services); $s++)
                                                                                {
                                                                                    if ($SI04 == $services[$s]["id"]) { echo "<option value='".$services[$s]["id"]."' selected>".$services[$s]["name"]."</option>"; }
                                                                                    else { echo "<option value='".$services[$s]["id"]."'>".$services[$s]["name"]."</option>"; }
                                                                                }
                                                                            ?>
                                                                        </select>
                                                                    </div>
                                                                </div>

                                                                <!-- CT01 -->
                                                                <div class="row d-flex justify-content-center align-items-center mb-2">
                                                                    <div class="col-3">
                                                                        <label for="CT01"><b>CT01:</b></label>
                                                                    </div>
                                                                    <div class="col-9">
                                                                        <select class="form-select" id="CT01" name="CT01">
                                                                            <option></option>
                                                                            <?php
                                                                                for ($s = 0; $s < count($services); $s++)
                                                                                {
                                                                                    if ($CT01 == $services[$s]["id"]) { echo "<option value='".$services[$s]["id"]."' selected>".$services[$s]["name"]."</option>"; }
                                                                                    else { echo "<option value='".$services[$s]["id"]."'>".$services[$s]["name"]."</option>"; }
                                                                                }
                                                                            ?>
                                                                        </select>
                                                                    </div>
                                                                </div>

                                                                <!-- CT02 -->
                                                                <div class="row d-flex justify-content-center align-items-center mb-2">
                                                                    <div class="col-3">
                                                                        <label for="CT02"><b>CT02:</b></label>
                                                                    </div>
                                                                    <div class="col-9">
                                                                        <select class="form-select" id="CT02" name="CT02">
                                                                            <option></option>
                                                                            <?php
                                                                                for ($s = 0; $s < count($services); $s++)
                                                                                {
                                                                                    if ($CT02 == $services[$s]["id"]) { echo "<option value='".$services[$s]["id"]."' selected>".$services[$s]["name"]."</option>"; }
                                                                                    else { echo "<option value='".$services[$s]["id"]."'>".$services[$s]["name"]."</option>"; }
                                                                                }
                                                                            ?>
                                                                        </select>
                                                                    </div>
                                                                </div>

                                                                <!-- SH01 -->
                                                                <div class="row d-flex justify-content-center align-items-center mb-2">
                                                                    <div class="col-3">
                                                                        <label for="SH01"><b>SH01:</b></label>
                                                                    </div>
                                                                    <div class="col-9">
                                                                        <select class="form-select" id="SH01" name="SH01">
                                                                            <option></option>
                                                                            <?php
                                                                                for ($s = 0; $s < count($services); $s++)
                                                                                {
                                                                                    if ($SH01 == $services[$s]["id"]) { echo "<option value='".$services[$s]["id"]."' selected>".$services[$s]["name"]."</option>"; }
                                                                                    else { echo "<option value='".$services[$s]["id"]."'>".$services[$s]["name"]."</option>"; }
                                                                                }
                                                                            ?>
                                                                        </select>
                                                                    </div>
                                                                </div>

                                                                <!-- ET01 -->
                                                                <div class="row d-flex justify-content-center align-items-center mb-2">
                                                                    <div class="col-3">
                                                                        <label for="ET01"><b>ET01:</b></label>
                                                                    </div>
                                                                    <div class="col-9">
                                                                        <select class="form-select" id="ET01" name="ET01">
                                                                            <option></option>
                                                                            <?php
                                                                                for ($s = 0; $s < count($services); $s++)
                                                                                {
                                                                                    if ($ET01 == $services[$s]["id"]) { echo "<option value='".$services[$s]["id"]."' selected>".$services[$s]["name"]."</option>"; }
                                                                                    else { echo "<option value='".$services[$s]["id"]."'>".$services[$s]["name"]."</option>"; }
                                                                                }
                                                                            ?>
                                                                        </select>
                                                                    </div>
                                                                </div>

                                                                <!-- TS01 -->
                                                                <div class="row d-flex justify-content-center align-items-center mb-2">
                                                                    <div class="col-3">
                                                                        <label for="TS01"><b>TS01:</b></label>
                                                                    </div>
                                                                    <div class="col-9">
                                                                        <select class="form-select" id="TS01" name="TS01">
                                                                            <option></option>
                                                                            <?php
                                                                                for ($s = 0; $s < count($services); $s++)
                                                                                {
                                                                                    if ($TS01 == $services[$s]["id"]) { echo "<option value='".$services[$s]["id"]."' selected>".$services[$s]["name"]."</option>"; }
                                                                                    else { echo "<option value='".$services[$s]["id"]."'>".$services[$s]["name"]."</option>"; }
                                                                                }
                                                                            ?>
                                                                        </select>
                                                                    </div>
                                                                </div>

                                                                <!-- SB01 -->
                                                                <div class="row d-flex justify-content-center align-items-center mb-2">
                                                                    <div class="col-3">
                                                                        <label for="SB01"><b>SB01:</b></label>
                                                                    </div>
                                                                    <div class="col-9">
                                                                        <select class="form-select" id="SB01" name="SB01">
                                                                            <option></option>
                                                                            <?php
                                                                                for ($s = 0; $s < count($services); $s++)
                                                                                {
                                                                                    if ($SB01 == $services[$s]["id"]) { echo "<option value='".$services[$s]["id"]."' selected>".$services[$s]["name"]."</option>"; }
                                                                                    else { echo "<option value='".$services[$s]["id"]."'>".$services[$s]["name"]."</option>"; }
                                                                                }
                                                                            ?>
                                                                        </select>
                                                                    </div>
                                                                </div>

                                                                <!-- LS01 -->
                                                                <div class="row d-flex justify-content-center align-items-center mb-2">
                                                                    <div class="col-3">
                                                                        <label for="LS01"><b>LS01:</b></label>
                                                                    </div>
                                                                    <div class="col-9">
                                                                        <select class="form-select" id="LS01" name="LS01">
                                                                            <option></option>
                                                                            <?php
                                                                                for ($s = 0; $s < count($services); $s++)
                                                                                {
                                                                                    if ($LS01 == $services[$s]["id"]) { echo "<option value='".$services[$s]["id"]."' selected>".$services[$s]["name"]."</option>"; }
                                                                                    else { echo "<option value='".$services[$s]["id"]."'>".$services[$s]["name"]."</option>"; }
                                                                                }
                                                                            ?>
                                                                        </select>
                                                                    </div>
                                                                </div>

                                                                <!-- OTHER1 -->
                                                                <div class="row d-flex justify-content-center align-items-center mb-2">
                                                                    <div class="col-3">
                                                                        <label for="OTHER1"><b>OTHER1:</b></label>
                                                                    </div>
                                                                    <div class="col-9">
                                                                        <select class="form-select" id="OTHER1" name="OTHER1">
                                                                            <option></option>
                                                                            <?php
                                                                                for ($s = 0; $s < count($other_services); $s++)
                                                                                {
                                                                                    if ($OTHER1 == $other_services[$s]["id"]) { echo "<option value='".$other_services[$s]["id"]."' selected>".$other_services[$s]["name"]."</option>"; }
                                                                                    else { echo "<option value='".$other_services[$s]["id"]."'>".$other_services[$s]["name"]."</option>"; }
                                                                                }
                                                                            ?>
                                                                        </select>
                                                                    </div>
                                                                </div>

                                                                <!-- Page 1 Comment -->
                                                                <div class="row d-flex justify-content-center align-items-center mb-2">
                                                                    <div class="col-3">
                                                                        <label for="page1_comment"><b>Comment:</b></label>
                                                                    </div>
                                                                    <div class="col-9">
                                                                        <input class="form-control" value="<?php echo $page1_comment; ?>" id="page1_comment" name="page1_comment" maxlength="256">
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Contract Page 2 of 2 -->
                                                    <div class="accordion-item">
                                                        <h2 class="accordion-header" id="<?php echo $type; ?>-page2-heading">
                                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#<?php echo $type; ?>-page2" aria-expanded="false" aria-controls="<?php echo $type; ?>-page2">
                                                                Contract Page 2 of 2
                                                            </button>
                                                        </h2>
                                                        <div id="<?php echo $type; ?>-page2" class="accordion-collapse collapse" aria-labelledby="<?php echo $type; ?>-page2-heading">
                                                            <div class="accordion-body">
                                                                <!-- SP01 -->
                                                                <div class="row d-flex justify-content-center align-items-center mb-2">
                                                                    <div class="col-3">
                                                                        <label for="SP01"><b>SP01:</b></label>
                                                                    </div>
                                                                    <div class="col-9">
                                                                        <select class="form-select" id="SP01" name="SP01">
                                                                            <option></option>
                                                                            <?php
                                                                                for ($s = 0; $s < count($services); $s++)
                                                                                {
                                                                                    if ($SP01 == $services[$s]["id"]) { echo "<option value='".$services[$s]["id"]."' selected>".$services[$s]["name"]."</option>"; }
                                                                                    else { echo "<option value='".$services[$s]["id"]."'>".$services[$s]["name"]."</option>"; }
                                                                                }
                                                                            ?>
                                                                        </select>
                                                                    </div>
                                                                </div>

                                                                <!-- SP02 -->
                                                                <div class="row d-flex justify-content-center align-items-center mb-2">
                                                                    <div class="col-3">
                                                                        <label for="SP02"><b>SP02:</b></label>
                                                                    </div>
                                                                    <div class="col-9">
                                                                        <select class="form-select" id="SP02" name="SP02">
                                                                            <option></option>
                                                                            <?php
                                                                                for ($s = 0; $s < count($services); $s++)
                                                                                {
                                                                                    if ($SP02 == $services[$s]["id"]) { echo "<option value='".$services[$s]["id"]."' selected>".$services[$s]["name"]."</option>"; }
                                                                                    else { echo "<option value='".$services[$s]["id"]."'>".$services[$s]["name"]."</option>"; }
                                                                                }
                                                                            ?>
                                                                        </select>
                                                                    </div>
                                                                </div>

                                                                <!-- SP03 -->
                                                                <div class="row d-flex justify-content-center align-items-center mb-2">
                                                                    <div class="col-3">
                                                                        <label for="SP03"><b>SP03:</b></label>
                                                                    </div>
                                                                    <div class="col-9">
                                                                        <select class="form-select" id="SP03" name="SP03">
                                                                            <option></option>
                                                                            <?php
                                                                                for ($s = 0; $s < count($services); $s++)
                                                                                {
                                                                                    if ($SP03 == $services[$s]["id"]) { echo "<option value='".$services[$s]["id"]."' selected>".$services[$s]["name"]."</option>"; }
                                                                                    else { echo "<option value='".$services[$s]["id"]."'>".$services[$s]["name"]."</option>"; }
                                                                                }
                                                                            ?>
                                                                        </select>
                                                                    </div>
                                                                </div>

                                                                <!-- SP04 -->
                                                                <div class="row d-flex justify-content-center align-items-center mb-2">
                                                                    <div class="col-3">
                                                                        <label for="SP04"><b>SP04:</b></label>
                                                                    </div>
                                                                    <div class="col-9">
                                                                        <select class="form-select" id="SP04" name="SP04">
                                                                            <option></option>
                                                                            <?php
                                                                                for ($s = 0; $s < count($services); $s++)
                                                                                {
                                                                                    if ($SP04 == $services[$s]["id"]) { echo "<option value='".$services[$s]["id"]."' selected>".$services[$s]["name"]."</option>"; }
                                                                                    else { echo "<option value='".$services[$s]["id"]."'>".$services[$s]["name"]."</option>"; }
                                                                                }
                                                                            ?>
                                                                        </select>
                                                                    </div>
                                                                </div>

                                                                <!-- SP05 -->
                                                                <div class="row d-flex justify-content-center align-items-center mb-2">
                                                                    <div class="col-3">
                                                                        <label for="SP05"><b>SP05:</b></label>
                                                                    </div>
                                                                    <div class="col-9">
                                                                        <select class="form-select" id="SP05" name="SP05">
                                                                            <option></option>
                                                                            <?php
                                                                                for ($s = 0; $s < count($services); $s++)
                                                                                {
                                                                                    if ($SP05 == $services[$s]["id"]) { echo "<option value='".$services[$s]["id"]."' selected>".$services[$s]["name"]."</option>"; }
                                                                                    else { echo "<option value='".$services[$s]["id"]."'>".$services[$s]["name"]."</option>"; }
                                                                                }
                                                                            ?>
                                                                        </select>
                                                                    </div>
                                                                </div>

                                                                <!-- SP06 -->
                                                                <div class="row d-flex justify-content-center align-items-center mb-2">
                                                                    <div class="col-3">
                                                                        <label for="SP06"><b>SP06:</b></label>
                                                                    </div>
                                                                    <div class="col-9">
                                                                        <select class="form-select" id="SP06" name="SP06">
                                                                            <option></option>
                                                                            <?php
                                                                                for ($s = 0; $s < count($services); $s++)
                                                                                {
                                                                                    if ($SP06 == $services[$s]["id"]) { echo "<option value='".$services[$s]["id"]."' selected>".$services[$s]["name"]."</option>"; }
                                                                                    else { echo "<option value='".$services[$s]["id"]."'>".$services[$s]["name"]."</option>"; }
                                                                                }
                                                                            ?>
                                                                        </select>
                                                                    </div>
                                                                </div>

                                                                <!-- SP07 -->
                                                                <div class="row d-flex justify-content-center align-items-center mb-2">
                                                                    <div class="col-3">
                                                                        <label for="SP07"><b>SP07:</b></label>
                                                                    </div>
                                                                    <div class="col-9">
                                                                        <select class="form-select" id="SP07" name="SP07">
                                                                            <option></option>
                                                                            <?php
                                                                                for ($s = 0; $s < count($services); $s++)
                                                                                {
                                                                                    if ($SP07 == $services[$s]["id"]) { echo "<option value='".$services[$s]["id"]."' selected>".$services[$s]["name"]."</option>"; }
                                                                                    else { echo "<option value='".$services[$s]["id"]."'>".$services[$s]["name"]."</option>"; }
                                                                                }
                                                                            ?>
                                                                        </select>
                                                                    </div>
                                                                </div>

                                                                <!-- SP08 -->
                                                                <div class="row d-flex justify-content-center align-items-center mb-2">
                                                                    <div class="col-3">
                                                                        <label for="SP08"><b>SP08:</b></label>
                                                                    </div>
                                                                    <div class="col-9">
                                                                        <select class="form-select" id="SP08" name="SP08">
                                                                            <option></option>
                                                                            <?php
                                                                                for ($s = 0; $s < count($services); $s++)
                                                                                {
                                                                                    if ($SP08 == $services[$s]["id"]) { echo "<option value='".$services[$s]["id"]."' selected>".$services[$s]["name"]."</option>"; }
                                                                                    else { echo "<option value='".$services[$s]["id"]."'>".$services[$s]["name"]."</option>"; }
                                                                                }
                                                                            ?>
                                                                        </select>
                                                                    </div>
                                                                </div>

                                                                <!-- SP09 -->
                                                                <div class="row d-flex justify-content-center align-items-center mb-2">
                                                                    <div class="col-3">
                                                                        <label for="SP09"><b>SP09:</b></label>
                                                                    </div>
                                                                    <div class="col-9">
                                                                        <select class="form-select" id="SP09" name="SP09">
                                                                            <option></option>
                                                                            <?php
                                                                                for ($s = 0; $s < count($services); $s++)
                                                                                {
                                                                                    if ($SP09 == $services[$s]["id"]) { echo "<option value='".$services[$s]["id"]."' selected>".$services[$s]["name"]."</option>"; }
                                                                                    else { echo "<option value='".$services[$s]["id"]."'>".$services[$s]["name"]."</option>"; }
                                                                                }
                                                                            ?>
                                                                        </select>
                                                                    </div>
                                                                </div>

                                                                <!-- SP10 -->
                                                                <div class="row d-flex justify-content-center align-items-center mb-2">
                                                                    <div class="col-3">
                                                                        <label for="SP10"><b>SP10:</b></label>
                                                                    </div>
                                                                    <div class="col-9">
                                                                        <select class="form-select" id="SP10" name="SP10">
                                                                            <option></option>
                                                                            <?php
                                                                                for ($s = 0; $s < count($services); $s++)
                                                                                {
                                                                                    if ($SP10 == $services[$s]["id"]) { echo "<option value='".$services[$s]["id"]."' selected>".$services[$s]["name"]."</option>"; }
                                                                                    else { echo "<option value='".$services[$s]["id"]."'>".$services[$s]["name"]."</option>"; }
                                                                                }
                                                                            ?>
                                                                        </select>
                                                                    </div>
                                                                </div>

                                                                <!-- SP11 -->
                                                                <div class="row d-flex justify-content-center align-items-center mb-2">
                                                                    <div class="col-3">
                                                                        <label for="SP11"><b>SP11:</b></label>
                                                                    </div>
                                                                    <div class="col-9">
                                                                        <select class="form-select" id="SP11" name="SP11">
                                                                            <option></option>
                                                                            <?php
                                                                                for ($s = 0; $s < count($services); $s++)
                                                                                {
                                                                                    if ($SP11 == $services[$s]["id"]) { echo "<option value='".$services[$s]["id"]."' selected>".$services[$s]["name"]."</option>"; }
                                                                                    else { echo "<option value='".$services[$s]["id"]."'>".$services[$s]["name"]."</option>"; }
                                                                                }
                                                                            ?>
                                                                        </select>
                                                                    </div>
                                                                </div>

                                                                <!-- SP12 -->
                                                                <div class="row d-flex justify-content-center align-items-center mb-2">
                                                                    <div class="col-3">
                                                                        <label for="SP12"><b>SP12:</b></label>
                                                                    </div>
                                                                    <div class="col-9">
                                                                        <select class="form-select" id="SP12" name="SP12">
                                                                            <option></option>
                                                                            <?php
                                                                                for ($s = 0; $s < count($services); $s++)
                                                                                {
                                                                                    if ($SP12 == $services[$s]["id"]) { echo "<option value='".$services[$s]["id"]."' selected>".$services[$s]["name"]."</option>"; }
                                                                                    else { echo "<option value='".$services[$s]["id"]."'>".$services[$s]["name"]."</option>"; }
                                                                                }
                                                                            ?>
                                                                        </select>
                                                                    </div>
                                                                </div>

                                                                <!-- SP13 -->
                                                                <div class="row d-flex justify-content-center align-items-center mb-2">
                                                                    <div class="col-3">
                                                                        <label for="SP13"><b>SP13:</b></label>
                                                                    </div>
                                                                    <div class="col-9">
                                                                        <select class="form-select" id="SP13" name="SP13">
                                                                            <option></option>
                                                                            <?php
                                                                                for ($s = 0; $s < count($services); $s++)
                                                                                {
                                                                                    if ($SP13 == $services[$s]["id"]) { echo "<option value='".$services[$s]["id"]."' selected>".$services[$s]["name"]."</option>"; }
                                                                                    else { echo "<option value='".$services[$s]["id"]."'>".$services[$s]["name"]."</option>"; }
                                                                                }
                                                                            ?>
                                                                        </select>
                                                                    </div>
                                                                </div>

                                                                <!-- SP14 -->
                                                                <div class="row d-flex justify-content-center align-items-center mb-2">
                                                                    <div class="col-3">
                                                                        <label for="SP14"><b>SP14:</b></label>
                                                                    </div>
                                                                    <div class="col-9">
                                                                        <select class="form-select" id="SP14" name="SP14">
                                                                            <option></option>
                                                                            <?php
                                                                                for ($s = 0; $s < count($services); $s++)
                                                                                {
                                                                                    if ($SP14 == $services[$s]["id"]) { echo "<option value='".$services[$s]["id"]."' selected>".$services[$s]["name"]."</option>"; }
                                                                                    else { echo "<option value='".$services[$s]["id"]."'>".$services[$s]["name"]."</option>"; }
                                                                                }
                                                                            ?>
                                                                        </select>
                                                                    </div>
                                                                </div>

                                                                <!-- SP15A -->
                                                                <div class="row d-flex justify-content-center align-items-center mb-2">
                                                                    <div class="col-3">
                                                                        <label for="SP15A"><b>SP15A:</b></label>
                                                                    </div>
                                                                    <div class="col-9">
                                                                        <select class="form-select" id="SP15A" name="SP15A">
                                                                            <option></option>
                                                                            <?php
                                                                                for ($s = 0; $s < count($services); $s++)
                                                                                {
                                                                                    if ($SP15A == $services[$s]["id"]) { echo "<option value='".$services[$s]["id"]."' selected>".$services[$s]["name"]."</option>"; }
                                                                                    else { echo "<option value='".$services[$s]["id"]."'>".$services[$s]["name"]."</option>"; }
                                                                                }
                                                                            ?>
                                                                        </select>
                                                                    </div>
                                                                </div>

                                                                <!-- SP15B -->
                                                                <div class="row d-flex justify-content-center align-items-center mb-2">
                                                                    <div class="col-3">
                                                                        <label for="SP15B"><b>SP15B:</b></label>
                                                                    </div>
                                                                    <div class="col-9">
                                                                        <select class="form-select" id="SP15B" name="SP15B">
                                                                            <option></option>
                                                                            <?php
                                                                                for ($s = 0; $s < count($services); $s++)
                                                                                {
                                                                                    if ($SP15B == $services[$s]["id"]) { echo "<option value='".$services[$s]["id"]."' selected>".$services[$s]["name"]."</option>"; }
                                                                                    else { echo "<option value='".$services[$s]["id"]."'>".$services[$s]["name"]."</option>"; }
                                                                                }
                                                                            ?>
                                                                        </select>
                                                                    </div>
                                                                </div>

                                                                <!-- SP15C -->
                                                                <div class="row d-flex justify-content-center align-items-center mb-2">
                                                                    <div class="col-3">
                                                                        <label for="SP15C"><b>SP15C:</b></label>
                                                                    </div>
                                                                    <div class="col-9">
                                                                        <select class="form-select" id="SP15C" name="SP15C">
                                                                            <option></option>
                                                                            <?php
                                                                                for ($s = 0; $s < count($services); $s++)
                                                                                {
                                                                                    if ($SP15C == $services[$s]["id"]) { echo "<option value='".$services[$s]["id"]."' selected>".$services[$s]["name"]."</option>"; }
                                                                                    else { echo "<option value='".$services[$s]["id"]."'>".$services[$s]["name"]."</option>"; }
                                                                                }
                                                                            ?>
                                                                        </select>
                                                                    </div>
                                                                </div>

                                                                <!-- SP16 -->
                                                                <div class="row d-flex justify-content-center align-items-center mb-2">
                                                                    <div class="col-3">
                                                                        <label for="SP16"><b>SP16:</b></label>
                                                                    </div>
                                                                    <div class="col-9">
                                                                        <select class="form-select" id="SP16" name="SP16">
                                                                            <option></option>
                                                                            <?php
                                                                                for ($s = 0; $s < count($services); $s++)
                                                                                {
                                                                                    if ($SP16 == $services[$s]["id"]) { echo "<option value='".$services[$s]["id"]."' selected>".$services[$s]["name"]."</option>"; }
                                                                                    else { echo "<option value='".$services[$s]["id"]."'>".$services[$s]["name"]."</option>"; }
                                                                                }
                                                                            ?>
                                                                        </select>
                                                                    </div>
                                                                </div>

                                                                <!-- SP17 -->
                                                                <div class="row d-flex justify-content-center align-items-center mb-2">
                                                                    <div class="col-3">
                                                                        <label for="SP17"><b>SP17:</b></label>
                                                                    </div>
                                                                    <div class="col-9">
                                                                        <select class="form-select" id="SP17" name="SP17">
                                                                            <option></option>
                                                                            <?php
                                                                                for ($s = 0; $s < count($services); $s++)
                                                                                {
                                                                                    if ($SP17 == $services[$s]["id"]) { echo "<option value='".$services[$s]["id"]."' selected>".$services[$s]["name"]."</option>"; }
                                                                                    else { echo "<option value='".$services[$s]["id"]."'>".$services[$s]["name"]."</option>"; }
                                                                                }
                                                                            ?>
                                                                        </select>
                                                                    </div>
                                                                </div>

                                                                <!-- SP18 -->
                                                                <div class="row d-flex justify-content-center align-items-center mb-2">
                                                                    <div class="col-3">
                                                                        <label for="SP18"><b>SP18:</b></label>
                                                                    </div>
                                                                    <div class="col-9">
                                                                        <select class="form-select" id="SP18" name="SP18">
                                                                            <option></option>
                                                                            <?php
                                                                                for ($s = 0; $s < count($services); $s++)
                                                                                {
                                                                                    if ($SP18 == $services[$s]["id"]) { echo "<option value='".$services[$s]["id"]."' selected>".$services[$s]["name"]."</option>"; }
                                                                                    else { echo "<option value='".$services[$s]["id"]."'>".$services[$s]["name"]."</option>"; }
                                                                                }
                                                                            ?>
                                                                        </select>
                                                                    </div>
                                                                </div>

                                                                <!-- SP19 -->
                                                                <div class="row d-flex justify-content-center align-items-center mb-2">
                                                                    <div class="col-3">
                                                                        <label for="SP19"><b>SP19:</b></label>
                                                                    </div>
                                                                    <div class="col-9">
                                                                        <select class="form-select" id="SP19" name="SP19">
                                                                            <option></option>
                                                                            <?php
                                                                                for ($s = 0; $s < count($services); $s++)
                                                                                {
                                                                                    if ($SP19 == $services[$s]["id"]) { echo "<option value='".$services[$s]["id"]."' selected>".$services[$s]["name"]."</option>"; }
                                                                                    else { echo "<option value='".$services[$s]["id"]."'>".$services[$s]["name"]."</option>"; }
                                                                                }
                                                                            ?>
                                                                        </select>
                                                                    </div>
                                                                </div>

                                                                <!-- AE01 -->
                                                                <div class="row d-flex justify-content-center align-items-center mb-2">
                                                                    <div class="col-3">
                                                                        <label for="AE01"><b>AE01:</b></label>
                                                                    </div>
                                                                    <div class="col-9">
                                                                        <select class="form-select" id="AE01" name="AE01">
                                                                            <option></option>
                                                                            <?php
                                                                                for ($s = 0; $s < count($services); $s++)
                                                                                {
                                                                                    if ($AE01 == $services[$s]["id"]) { echo "<option value='".$services[$s]["id"]."' selected>".$services[$s]["name"]."</option>"; }
                                                                                    else { echo "<option value='".$services[$s]["id"]."'>".$services[$s]["name"]."</option>"; }
                                                                                }
                                                                            ?>
                                                                        </select>
                                                                    </div>
                                                                </div>

                                                                <!-- AE02 -->
                                                                <div class="row d-flex justify-content-center align-items-center mb-2">
                                                                    <div class="col-3">
                                                                        <label for="AE02"><b>AE02:</b></label>
                                                                    </div>
                                                                    <div class="col-9">
                                                                        <select class="form-select" id="AE02" name="AE02">
                                                                            <option></option>
                                                                            <?php
                                                                                for ($s = 0; $s < count($services); $s++)
                                                                                {
                                                                                    if ($AE02 == $services[$s]["id"]) { echo "<option value='".$services[$s]["id"]."' selected>".$services[$s]["name"]."</option>"; }
                                                                                    else { echo "<option value='".$services[$s]["id"]."'>".$services[$s]["name"]."</option>"; }
                                                                                }
                                                                            ?>
                                                                        </select>
                                                                    </div>
                                                                </div>

                                                                <!-- AE03 -->
                                                                <div class="row d-flex justify-content-center align-items-center mb-2">
                                                                    <div class="col-3">
                                                                        <label for="AE03"><b>AE03:</b></label>
                                                                    </div>
                                                                    <div class="col-9">
                                                                        <select class="form-select" id="AE03" name="AE03">
                                                                            <option></option>
                                                                            <?php
                                                                                for ($s = 0; $s < count($services); $s++)
                                                                                {
                                                                                    if ($AE03 == $services[$s]["id"]) { echo "<option value='".$services[$s]["id"]."' selected>".$services[$s]["name"]."</option>"; }
                                                                                    else { echo "<option value='".$services[$s]["id"]."'>".$services[$s]["name"]."</option>"; }
                                                                                }
                                                                            ?>
                                                                        </select>
                                                                    </div>
                                                                </div>

                                                                <!-- AE04 -->
                                                                <div class="row d-flex justify-content-center align-items-center mb-2">
                                                                    <div class="col-3">
                                                                        <label for="AE04"><b>AE04:</b></label>
                                                                    </div>
                                                                    <div class="col-9">
                                                                        <select class="form-select" id="AE04" name="AE04">
                                                                            <option></option>
                                                                            <?php
                                                                                for ($s = 0; $s < count($services); $s++)
                                                                                {
                                                                                    if ($AE04 == $services[$s]["id"]) { echo "<option value='".$services[$s]["id"]."' selected>".$services[$s]["name"]."</option>"; }
                                                                                    else { echo "<option value='".$services[$s]["id"]."'>".$services[$s]["name"]."</option>"; }
                                                                                }
                                                                            ?>
                                                                        </select>
                                                                    </div>
                                                                </div>

                                                                <!-- AE05 -->
                                                                <div class="row d-flex justify-content-center align-items-center mb-2">
                                                                    <div class="col-3">
                                                                        <label for="AE05"><b>AE05:</b></label>
                                                                    </div>
                                                                    <div class="col-9">
                                                                        <select class="form-select" id="AE05" name="AE05">
                                                                            <option></option>
                                                                            <?php
                                                                                for ($s = 0; $s < count($services); $s++)
                                                                                {
                                                                                    if ($AE05 == $services[$s]["id"]) { echo "<option value='".$services[$s]["id"]."' selected>".$services[$s]["name"]."</option>"; }
                                                                                    else { echo "<option value='".$services[$s]["id"]."'>".$services[$s]["name"]."</option>"; }
                                                                                }
                                                                            ?>
                                                                        </select>
                                                                    </div>
                                                                </div>

                                                                <!-- AE06 -->
                                                                <div class="row d-flex justify-content-center align-items-center mb-2">
                                                                    <div class="col-3">
                                                                        <label for="AE06"><b>AE06:</b></label>
                                                                    </div>
                                                                    <div class="col-9">
                                                                        <select class="form-select" id="AE06" name="AE06">
                                                                            <option></option>
                                                                            <?php
                                                                                for ($s = 0; $s < count($services); $s++)
                                                                                {
                                                                                    if ($AE06 == $services[$s]["id"]) { echo "<option value='".$services[$s]["id"]."' selected>".$services[$s]["name"]."</option>"; }
                                                                                    else { echo "<option value='".$services[$s]["id"]."'>".$services[$s]["name"]."</option>"; }
                                                                                }
                                                                            ?>
                                                                        </select>
                                                                    </div>
                                                                </div>

                                                                <!-- AE07 -->
                                                                <div class="row d-flex justify-content-center align-items-center mb-2">
                                                                    <div class="col-3">
                                                                        <label for="AE07"><b>AE07:</b></label>
                                                                    </div>
                                                                    <div class="col-9">
                                                                        <select class="form-select" id="AE07" name="AE07">
                                                                            <option></option>
                                                                            <?php
                                                                                for ($s = 0; $s < count($services); $s++)
                                                                                {
                                                                                    if ($AE07 == $services[$s]["id"]) { echo "<option value='".$services[$s]["id"]."' selected>".$services[$s]["name"]."</option>"; }
                                                                                    else { echo "<option value='".$services[$s]["id"]."'>".$services[$s]["name"]."</option>"; }
                                                                                }
                                                                            ?>
                                                                        </select>
                                                                    </div>
                                                                </div>

                                                                <!-- AE08 -->
                                                                <div class="row d-flex justify-content-center align-items-center mb-2">
                                                                    <div class="col-3">
                                                                        <label for="AE08"><b>AE08:</b></label>
                                                                    </div>
                                                                    <div class="col-9">
                                                                        <select class="form-select" id="AE08" name="AE08">
                                                                            <option></option>
                                                                            <?php
                                                                                for ($s = 0; $s < count($services); $s++)
                                                                                {
                                                                                    if ($AE08 == $services[$s]["id"]) { echo "<option value='".$services[$s]["id"]."' selected>".$services[$s]["name"]."</option>"; }
                                                                                    else { echo "<option value='".$services[$s]["id"]."'>".$services[$s]["name"]."</option>"; }
                                                                                }
                                                                            ?>
                                                                        </select>
                                                                    </div>
                                                                </div>

                                                                <!-- SN01 -->
                                                                <div class="row d-flex justify-content-center align-items-center mb-2">
                                                                    <div class="col-3">
                                                                        <label for="SN01"><b>SN01:</b></label>
                                                                    </div>
                                                                    <div class="col-9">
                                                                        <select class="form-select" id="SN01" name="SN01">
                                                                            <option></option>
                                                                            <?php
                                                                                for ($s = 0; $s < count($services); $s++)
                                                                                {
                                                                                    if ($SN01 == $services[$s]["id"]) { echo "<option value='".$services[$s]["id"]."' selected>".$services[$s]["name"]."</option>"; }
                                                                                    else { echo "<option value='".$services[$s]["id"]."'>".$services[$s]["name"]."</option>"; }
                                                                                }
                                                                            ?>
                                                                        </select>
                                                                    </div>
                                                                </div>

                                                                <!-- SPOTHER1 -->
                                                                <div class="row d-flex justify-content-center align-items-center mb-2">
                                                                    <div class="col-3">
                                                                        <label for="SPOTHER1"><b>SPOTHER1:</b></label>
                                                                    </div>
                                                                    <div class="col-9">
                                                                        <select class="form-select" id="SPOTHER1" name="SPOTHER1">
                                                                            <option></option>
                                                                            <?php
                                                                                for ($s = 0; $s < count($other_services); $s++)
                                                                                {
                                                                                    if ($SPOTHER1 == $other_services[$s]["id"]) { echo "<option value='".$other_services[$s]["id"]."' selected>".$other_services[$s]["name"]."</option>"; }
                                                                                    else { echo "<option value='".$other_services[$s]["id"]."'>".$other_services[$s]["name"]."</option>"; }
                                                                                }
                                                                            ?>
                                                                        </select>
                                                                    </div>
                                                                </div>

                                                                <!-- SPOTHER2 -->
                                                                <div class="row d-flex justify-content-center align-items-center mb-2">
                                                                    <div class="col-3">
                                                                        <label for="SPOTHER2"><b>SPOTHER2:</b></label>
                                                                    </div>
                                                                    <div class="col-9">
                                                                        <select class="form-select" id="SPOTHER2" name="SPOTHER2">
                                                                            <option></option>
                                                                            <?php
                                                                                for ($s = 0; $s < count($other_services); $s++)
                                                                                {
                                                                                    if ($SPOTHER2 == $other_services[$s]["id"]) { echo "<option value='".$other_services[$s]["id"]."' selected>".$other_services[$s]["name"]."</option>"; }
                                                                                    else { echo "<option value='".$other_services[$s]["id"]."'>".$other_services[$s]["name"]."</option>"; }
                                                                                }
                                                                            ?>
                                                                        </select>
                                                                    </div>
                                                                </div>

                                                                <!-- SPOTHER3 -->
                                                                <div class="row d-flex justify-content-center align-items-center mb-2">
                                                                    <div class="col-3">
                                                                        <label for="SPOTHER3"><b>SPOTHER3:</b></label>
                                                                    </div>
                                                                    <div class="col-9">
                                                                        <select class="form-select" id="SPOTHER3" name="SPOTHER3">
                                                                            <option></option>
                                                                            <?php
                                                                                for ($s = 0; $s < count($other_services); $s++)
                                                                                {
                                                                                    if ($SPOTHER3 == $other_services[$s]["id"]) { echo "<option value='".$other_services[$s]["id"]."' selected>".$other_services[$s]["name"]."</option>"; }
                                                                                    else { echo "<option value='".$other_services[$s]["id"]."'>".$other_services[$s]["name"]."</option>"; }
                                                                                }
                                                                            ?>
                                                                        </select>
                                                                    </div>
                                                                </div>

                                                                <!-- Page 2 Comment -->
                                                                <div class="row d-flex justify-content-center align-items-center mb-2">
                                                                    <div class="col-3">
                                                                        <label for="page2_comment"><b>Comment:</b></label>
                                                                    </div>
                                                                    <div class="col-9">
                                                                        <input class="form-control" value="<?php echo $page2_comment; ?>" id="page2_comment" name="page2_comment" maxlength="256">
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-primary" onclick="saveBuildSettings('<?php echo $type; ?>', <?php echo $customer_id; ?>);"><i class="fa-solid fa-floppy-disk"></i> Save Contract Settings</button>
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> Close</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- End Build Service Contract Modal -->
                            <?php
                        }
                    }
                }
            }
        }

        // disconnect from the database
        mysqli_close($conn);
    }
?>