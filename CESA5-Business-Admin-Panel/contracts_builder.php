<?php 
    include_once("header.php");

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1) 
    { 
        if (isset($PERMISSIONS["BUILD_SERVICE_CONTRACTS"]) || isset($PERMISSIONS["BUILD_QUARTERLY_INVOICES"]))
        {
            // connect to the database
            $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

            ?>  
                <!-- Page Specific Styling -->
                <style>
                    <?php if (isset($USER_SETTINGS) && $USER_SETTINGS["dark_mode"] == 1) { ?>
                        .accordion-header, .accordion-button, .accordion-item
                        {
                            background-color: #1c1c1c !important;
                            color: #ffffff !important;
                        }
                    <?php } ?>
                </style>

                <!-- Header -->
                <div class="row m-0 p-0">
                    <h1 class="col-12 col-sm-8 col-md-6 col-lg-4 col-xl-4 col-xxl-4 page-header my-3 py-3 ps-3 pe-5">
                        <a class="back-button" href="contracts.php" title="Return to Contracts."><i class="fa-solid fa-angles-left"></i></a>
                        <div class="d-inline float-end">Contracts Builder</div>
                    </h1>
                </div>

                <!-- Contract Type Selection -->
                <div class="row m-0">
                    <div class="btn-group" role="group" aria-label="Button group to select which type of contract to create">
                        <?php if (isset($PERMISSIONS["BUILD_SERVICE_CONTRACTS"])) { ?>
                        <button class="btn btn-secondary btn-lg w-100" id="contract-annual-button" onclick="toggleContract('annual');">Build Service Contracts</button>
                        <?php } ?>

                        <?php if (isset($PERMISSIONS["BUILD_QUARTERLY_INVOICES"])) { ?>
                        <button class="btn btn-secondary btn-lg w-100" id="contract-quarterly-button" onclick="toggleContract('quarterly');">Build Quarterly Invoices</button>
                        <?php } ?>
                    </div>
                </div>

                <?php if (isset($PERMISSIONS["BUILD_SERVICE_CONTRACTS"])) { ?>
                <!-- Annual Service Contract -->
                <div class="d-none" id="contract-annual">
                    <div class="row justify-content-center m-0">
                        <!-- Tab Description -->
                        <div class="col-6 p-0">
                            <fieldset class="border p-2">
                                <legend class="float-none w-auto px-4 py-0 m-0 text-center"><h1 class="report-title m-0">Build Service Contracts</h1></legend>

                                <p class="report-description text-center mb-2">
                                    For each customer, customize how to build each service contract. You can also enable/disable the abiltiy to create service contracts for each customer.
                                </p>

                                <?php if ($_SESSION["role"] == 1) { ?>
                                <div class="row report-header justify-content-center m-0">
                                    <div class="col-sm-12 col-md-6 col-lg-6 col-xl-4 col-xxl-4 p-2">
                                        <button class="btn btn-primary w-100" type="button" data-bs-toggle="modal" data-bs-target="#copyBuildSettingsModal">Copy Build Settings</button>
                                    </div>
                                </div>
                                <?php } ?>
                            </fieldset>
                        </div>

                        <!-- Search Filters -->
                        <div class="row justify-content-center mb-3 mx-0">
                            <?php
                                createGroupFilter($conn, "customers", "SC");
                                createBuildContractsFilter($conn, "customers", "SC");
                                createClearFilters("SC");
                            ?>
                        </div>

                        <!-- Customers Table -->
                        <div class="col-12 p-0">
                            <table id="SC-customers" class="report_table w-100">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Location Code</th>
                                        <th>Address</th>
                                        <th>Contacts</th>
                                        <th>Contract Folder ID</th>
                                        <th>Actions</th>
                                        <th>Groups</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
                <?php } ?>

                <?php if (isset($PERMISSIONS["BUILD_QUARTERLY_INVOICES"])) { ?>
                <!-- Quarterly Service Invoice -->
                <div class="d-none" id="contract-quarterly">
                    <div class="row justify-content-center m-0">
                        <!-- Tab Description -->
                        <div class="col-6 p-0">
                            <fieldset class="border p-2">
                                <legend class="float-none w-auto px-4 py-0 m-0 text-center"><h1 class="report-title m-0">Build Quarterly Invoices</h1></legend>

                                <p class="report-description text-center mb-2">
                                    For each customer, customize how to build each quarterly invoice. You can also enable/disable the abiltiy to create quarterly invoices for each customer.
                                </p>

                                <?php if ($_SESSION["role"] == 1) { ?>
                                <div class="row report-header justify-content-center m-0">
                                    <div class="col-sm-12 col-md-6 col-lg-6 col-xl-4 col-xxl-4 p-2">
                                        <button class="btn btn-primary w-100" type="button" data-bs-toggle="modal" data-bs-target="#copyBuildSettingsModal">Copy Build Settings</button>
                                    </div>
                                </div>
                                <?php } ?>
                            </fieldset>
                        </div>

                        <!-- Search Filters -->
                        <div class="row justify-content-center mb-3 mx-0">
                            <?php
                                createGroupFilter($conn, "customers", "QI");
                                createBuildContractsFilter($conn, "customers", "QI");
                                createClearFilters("QI");
                            ?>
                        </div>

                        <!-- Customers Table -->
                        <div class="col-12 p-0">
                            <table id="QI-customers" class="report_table w-100">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Location Code</th>
                                        <th>Address</th>
                                        <th>Contacts</th>
                                        <th>Contract Folder ID</th>
                                        <th>Actions</th>
                                        <th>Groups</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
                <?php } ?>

                
                <!-- MODALS -->
                <?php if ($_SESSION["role"] == 1) { ?>
                <!-- Copy Build Settings Modal -->
                <div class="modal fade" tabindex="-1" role="dialog" id="copyBuildSettingsModal" data-bs-backdrop="static" aria-labelledby="copyBuildSettingsModalLabel" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header primary-modal-header">
                                <h5 class="modal-title primary-modal-title" id="copyBuildSettingsModalLabel">Copy Build Settings</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>

                            <div class="modal-body">
                                <div class="row align-items-center m-0">
                                    <p class="my-2">
                                        <b>Build settings</b> consist of the build toggle, as well as the individual contract settings.
                                    </p>

                                    <p class="my-2">
                                        <i class="fa-solid fa-triangle-exclamation"></i> <b>WARNING:</b> we will <b>copy all</b> build settings for all periods. 
                                    </p>
                                </div>

                                <div class="row align-items-center my-2">
                                    <div class="col-5 text-end"><label for="copy-from"><span class="required-field">*</span> Copy Settings From:</label></div>
                                    <div class="col-7">
                                        <select class="form-select w-100" id="copy-from" name="copy-from" required>
                                            <option></option>
                                            <option value="SC">Service Contracts</option>
                                            <option value="QI">Quarterly Invoices</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="row align-items-center my-2">
                                    <div class="col-5 text-end"><label for="copy-to"><span class="required-field">*</span> Copy Settings To:</label></div>
                                    <div class="col-7">
                                        <select class="form-select w-100" id="copy-to" name="copy-to" required>
                                            <option></option>
                                            <option value="SC">Service Contracts</option>
                                            <option value="QI">Quarterly Invoices</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="modal-footer">
                                <button type="button" class="btn btn-primary" onclick="copyBuildSettings();"><i class="fa-solid fa-floppy-disk"></i> Copy Build Settings</button>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> Close</button>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- End Copy Build Settings Modal -->
                <?php } ?>

                <div id="SC-build-modal-div"></div> <!-- build service contract modal -->
                <div id="QI-build-modal-div"></div> <!-- build quarterly invoice modal -->
                <!-- END MODALS -->

                <script>
                    // view the table stored in session
                    var view_table = sessionStorage["BAP_ContractsBuilder_ViewTable"];
                    if (view_table != null && view_table != "")
                    {
                        // select and display the table previously viewed within the session
                        document.getElementById("contract-"+view_table).classList.remove("d-none");
                        document.getElementById("contract-"+view_table+"-button").classList.remove("btn-secondary");
                        document.getElementById("contract-"+view_table+"-button").classList.add("btn-primary");
                    }

                    // set the search filters to values we have saved in storage
                    $('#SC-search-groups').val(sessionStorage["BAP_BuildContracts_SC_Search_Group"]);
                    $('#SC-search-build_contract').val(sessionStorage["BAP_BuildContracts_SC_Search_BuildContracts"]);
                    $('#QI-search-groups').val(sessionStorage["BAP_BuildContracts_QI_Search_Group"]);
                    $('#QI-search-build_contract').val(sessionStorage["BAP_BuildContracts_QI_Search_BuildContracts"]);

                    var sc_customers = $("#SC-customers").DataTable({
                        ajax: {
                            url: "ajax/contracts/getCustomersSC.php",
                            type: "POST"
                        },
                        autoWidth: false,
                        pageLength: <?php if (isset($USER_SETTINGS["page_length"])) { echo $USER_SETTINGS["page_length"]; } else { echo 10; } ?>,
                        lengthMenu: [ [10, 25, 50, 100, 250, -1], [10, 25, 50, 100, 250, "All"] ],
                        columns: [
                            { data: "id", orderable: true, width: "7.5%" },
                            { data: "name", orderable: true, width: "22.5%" },
                            { data: "location_code", orderable: true, width: "7.5%" },
                            { data: "address", orderable: false, width: "22.5%" },
                            { data: "contacts", orderable: true, width: "20%" },
                            { data: "folders", orderable: false, width: "15%" },
                            { data: "actions", orderable: false, width: "5%" },
                            { data: "groups_string", orderable: true, visible: false },
                            { data: "isBuild", orderable: true, visible: false }
                        ],
                        order: [
                            [1, "asc"],
                            [0, "asc"]
                        ],
                        dom: 'lfrtip',
                        language: {
                            search: '<div class="input-group mb-1"><div class="input-group-prepend"><span class="d-flex align-items-center h-100 mx-2" id="nav-search-icon"><i class="fa fa-search" aria-hidden="true"></i></span></div>_INPUT_</div>',
                            lengthMenu: '<div class="mx-1 mb-1">Show _MENU_ entries</div>',
                            info: '<div class="mx-1">Showing _START_ to _END_ of _TOTAL_ entries</div>'
                        },
                        stateSave: true
                    });

                    var qi_customers = $("#QI-customers").DataTable({
                        ajax: {
                            url: "ajax/contracts/getCustomersQI.php",
                            type: "POST"
                        },
                        autoWidth: false,
                        pageLength: <?php if (isset($USER_SETTINGS["page_length"])) { echo $USER_SETTINGS["page_length"]; } else { echo 10; } ?>,
                        lengthMenu: [ [10, 25, 50, 100, 250, -1], [10, 25, 50, 100, 250, "All"] ],
                        columns: [
                            { data: "id", orderable: true, width: "7.5%" },
                            { data: "name", orderable: true, width: "22.5%" },
                            { data: "location_code", orderable: true, width: "7.5%" },
                            { data: "address", orderable: false, width: "22.5%" },
                            { data: "contacts", orderable: true, width: "20%" },
                            { data: "folders", orderable: false, width: "15%" },
                            { data: "actions", orderable: false, width: "5%" },
                            { data: "groups_string", orderable: true, visible: false },
                            { data: "isBuild", orderable: true, visible: false }
                        ],
                        order: [
                            [1, "asc"],
                            [0, "asc"]
                        ],
                        dom: 'lfrtip',
                        language: {
                            search: '<div class="input-group mb-1"><div class="input-group-prepend"><span class="d-flex align-items-center h-100 mx-2" id="nav-search-icon"><i class="fa fa-search" aria-hidden="true"></i></span></div>_INPUT_</div>',
                            lengthMenu: '<div class="mx-1 mb-1">Show _MENU_ entries</div>',
                            info: '<div class="mx-1">Showing _START_ to _END_ of _TOTAL_ entries</div>'
                        },
                        stateSave: true
                    });

                    /** function to display the form for the contract selected */
                    function toggleContract(type)
                    {
                        // deselect both contracts
                        <?php if (isset($PERMISSIONS["BUILD_SERVICE_CONTRACTS"])) { ?>
                        document.getElementById("contract-annual").classList.add("d-none");
                        document.getElementById("contract-annual-button").classList.remove("btn-primary");
                        document.getElementById("contract-annual-button").classList.add("btn-secondary");
                        <?php } ?>

                        <?php if (isset($PERMISSIONS["BUILD_QUARTERLY_INVOICES"])) { ?>
                        document.getElementById("contract-quarterly").classList.add("d-none");
                        document.getElementById("contract-quarterly-button").classList.remove("btn-primary");
                        document.getElementById("contract-quarterly-button").classList.add("btn-secondary");
                        <?php } ?>

                        // select the contract toggled
                        document.getElementById("contract-"+type+"-button").classList.add("btn-primary");
                        document.getElementById("contract-"+type).classList.remove("d-none");
                        
                        // store the table we are viewing in session storage
                        sessionStorage["BAP_ContractsBuilder_ViewTable"] = type;
                    }

                    /** function to toggle the build contract option */
                    function toggleBuild(type, customer_id)
                    {
                        // get the current value of the build setting
                        let element = document.getElementById(type+"-"+customer_id);
                        let status = element.value;

                        // toggle the customers build contract setting
                        let build_status = $.ajax({
                            type: "POST",
                            url: "ajax/contracts/toggleBuildContract.php",
                            async: false,
                            data : {
                                customer_id: customer_id,
                                type: type,
                                status: status
                            }
                        }).responseText;

                        // toggle if we should build contract or not for customer
                        if (build_status == 1) // customer contract has been set to build
                        {
                            element.classList.remove("btn-danger");
                            element.classList.add("btn-success");
                            element.value = 1;
                            element.innerHTML = "<i class='fa-solid fa-check'></i>";
                        }
                        else if (build_status == 0) // customer contract has been set to not build
                        {
                            element.classList.remove("btn-success");
                            element.classList.add("btn-danger");
                            element.value = 0;
                            element.innerHTML = "<i class='fa-solid fa-xmark'></i>";
                        }
                    }

                    /** function to get the build quarterly invoice modal */
                    function getBuildModal(type, customer_id)
                    {
                        // send the data to create the build service contract modal
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/contracts/getBuildModal.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                if (this.responseText != "" && this.responseText != null)
                                {
                                    document.getElementById(type+"-build-modal-div").innerHTML = this.responseText;     

                                    // display the build modal
                                    $("#"+type+"-Build-Modal").modal("show");
                                }
                            }
                        };
                        xmlhttp.send("customer_id="+customer_id+"&type="+type);
                    }

                    /** function to save a customer's service contract settings */
                    function saveBuildSettings(type, customer_id)
                    {
                        // initialize the string of data to send
                        let sendString = "customer_id="+customer_id+"&type="+type;
                        
                        // get page 1 fields
                        let GS01 = document.getElementById("GS01").value;
                        let GS02 = document.getElementById("GS02").value;
                        let SI01 = document.getElementById("SI01").value;
                        let SI02 = document.getElementById("SI02").value;
                        let SI03 = document.getElementById("SI03").value;
                        let SI04 = document.getElementById("SI04").value;
                        let CT01 = document.getElementById("CT01").value;
                        let CT02 = document.getElementById("CT02").value;
                        let SH01 = document.getElementById("SH01").value;
                        let ET01 = document.getElementById("ET01").value;
                        let TS01 = document.getElementById("TS01").value;
                        let SB01 = document.getElementById("SB01").value;
                        let LS01 = document.getElementById("LS01").value;
                        let OTHER1 = document.getElementById("OTHER1").value;
                        let page1_comment = document.getElementById("page1_comment").value;
                        sendString += "&GS01="+GS01+"&GS02="+GS02+"&SI01="+SI01+"&SI02="+SI02+"&SI03="+SI03+"&SI04="+SI04+"&CT01="+CT01+"&CT02="+CT02;
                        sendString += "&SH01="+SH01+"&ET01="+ET01+"&TS01="+TS01+"&SB01="+SB01+"&LS01="+LS01+"&OTHER1="+OTHER1+"&page1_comment="+page1_comment;

                        // get page 2 fields
                        let SP01 = document.getElementById("SP01").value;
                        let SP02 = document.getElementById("SP02").value;
                        let SP03 = document.getElementById("SP03").value;
                        let SP04 = document.getElementById("SP04").value;
                        let SP05 = document.getElementById("SP05").value;
                        let SP06 = document.getElementById("SP06").value;
                        let SP07 = document.getElementById("SP07").value;
                        let SP08 = document.getElementById("SP08").value;
                        let SP09 = document.getElementById("SP09").value;
                        let SP10 = document.getElementById("SP10").value;
                        let SP11 = document.getElementById("SP11").value;
                        let SP12 = document.getElementById("SP12").value;
                        let SP13 = document.getElementById("SP13").value;
                        let SP14 = document.getElementById("SP14").value;
                        let SP15A = document.getElementById("SP15A").value;
                        let SP15B = document.getElementById("SP15B").value;
                        let SP15C = document.getElementById("SP15C").value;
                        let SP16 = document.getElementById("SP16").value;
                        let SP17 = document.getElementById("SP17").value;
                        let SP18 = document.getElementById("SP18").value;
                        let SP19 = document.getElementById("SP19").value;
                        let AE01 = document.getElementById("AE01").value;
                        let AE02 = document.getElementById("AE02").value;
                        let AE03 = document.getElementById("AE03").value;
                        let AE04 = document.getElementById("AE04").value;
                        let AE05 = document.getElementById("AE05").value;
                        let AE06 = document.getElementById("AE06").value;
                        let AE07 = document.getElementById("AE07").value;
                        let AE08 = document.getElementById("AE08").value;
                        let SN01 = document.getElementById("SN01").value;
                        let SPOTHER1 = document.getElementById("SPOTHER1").value;
                        let SPOTHER2 = document.getElementById("SPOTHER2").value;
                        let SPOTHER3 = document.getElementById("SPOTHER3").value;
                        let page2_comment = document.getElementById("page2_comment").value;
                        sendString += "&SP01="+SP01+"&SP02="+SP02+"&SP03="+SP03+"&SP04="+SP04+"&SP05="+SP05+"&SP06="+SP06+"&SP07="+SP07;
                        sendString += "&SP08="+SP08+"&SP09="+SP09+"&SP10="+SP10+"&SP11="+SP11+"&SP12="+SP12+"&SP13="+SP13+"&SP14="+SP14;
                        sendString += "&SP15A="+SP15A+"&SP15B="+SP15B+"&SP15C="+SP15C+"&SP16="+SP16+"&SP17="+SP17+"&SP18="+SP18+"&SP19="+SP19;
                        sendString += "&AE01="+AE01+"&AE02="+AE02+"&AE03="+AE03+"&AE04="+AE04+"&AE05="+AE05+"&AE06="+AE06+"&AE07="+AE07+"&AE08="+AE08;
                        sendString += "&SN01="+SN01+"&SPOTHER1="+SPOTHER1+"&SPOTHER2="+SPOTHER2+"&SPOTHER3="+SPOTHER3+"&page2_comment="+page2_comment;

                        // send the data to edit the customer's service contract settings
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/contracts/editBuildSettings.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // create the status modal
                                let status_title = "Edit Build Status";
                                let status_body = this.responseText;
                                createStatusModal("refresh", status_title, status_body);

                                // hide the current modal
                                if (type == "SC") { $("#SC-Build-Modal").modal("hide"); }
                                else if (type == "QI") { $("#QI-Build-Modal").modal("hide"); }
                            }
                        };
                        xmlhttp.send(sendString);
                    }

                    /** function to copy build settings from on contract type to another */
                    function copyBuildSettings()
                    {
                        // get the form data
                        let from = document.getElementById("copy-from").value;
                        let to = document.getElementById("copy-to").value;

                        // create the string of data to send
                        let sendString = "from="+from+"&to="+to;

                        // send the data to process the copy invoices request
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/contracts/copyBuildSettings.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // create the status modal
                                let status_title = "Copy Build Settings Status";
                                let status_body = encodeURIComponent(this.responseText);
                                createStatusModal("refresh", status_title, status_body);

                                // hide the current modal
                                $("#copyBuildSettingsModal").modal("hide");
                            }
                        };
                        xmlhttp.send(sendString);
                    }

                    <?php if (isset($PERMISSIONS["BUILD_QUARTERLY_INVOICES"])) { ?>
                    // search the hidden "Groups" column for service contracts
                    $('#SC-search-groups').change(function() {
                        sc_customers.columns(7).search($(this).val()).draw();
                        sessionStorage["BAP_BuildContracts_SC_Search_Group"] = $(this).val();
                    });

                    // search the hidden "Build Contracts" column for service contracts
                    $('#SC-search-build_contract').change(function() {
                        sc_customers.columns(8).search($(this).val()).draw();
                        sessionStorage["BAP_BuildContracts_SC_Search_BuildContracts"] = $(this).val();
                    });
                    
                    // function to clear search filters for service contracts
                    $('#SC-clearFilters').click(function() {
                        sessionStorage["BAP_BuildContracts_SC_Search_Group"] = "";
                        sessionStorage["BAP_BuildContracts_SC_Search_BuildContracts"] = "";
                        $('#SC-search-groups').val("");
                        $('#SC-search-build_contract').val("");
                        sc_customers.search("").columns().search("").draw();
                    });
                    <?php } ?>

                    <?php if (isset($PERMISSIONS["BUILD_QUARTERLY_INVOICES"])) { ?>
                    // search the hidden "Groups" column for quarterly invoices
                    $('#QI-search-groups').change(function() {
                        qi_customers.columns(7).search($(this).val()).draw();
                        sessionStorage["BAP_BuildContracts_QI_Search_Group"] = $(this).val();
                    });

                    // search the hidden "Build Contracts" column for quarterly invoices
                    $('#QI-search-build_contract').change(function() {
                        qi_customers.columns(8).search($(this).val()).draw();
                        sessionStorage["BAP_BuildContracts_QI_Search_BuildContracts"] = $(this).val();
                    });
                    
                    // function to clear search filters for quarterly invoices
                    $('#QI-clearFilters').click(function() {
                        sessionStorage["BAP_BuildContracts_QI_Search_Group"] = "";
                        sessionStorage["BAP_BuildContracts_QI_Search_BuildContracts"] = "";
                        $('#QI-search-groups').val("");
                        $('#QI-search-build_contract').val("");
                        qi_customers.search("").columns().search("").draw();
                    });
                    <?php } ?>
                </script>
            <?php 

            // disconnect from the database
            mysqli_close($conn);
        }
        else { denyAccess(); }
    }
    else { goToLogin(); }

    include_once("footer.php"); 
?>