<?php 
    include_once("header.php");

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1) 
    { 
        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        // initialize an array to store all periods; then get all periods and store in the array
        $periods = [];
        $active_period = -1;
        $getPeriods = mysqli_query($conn, "SELECT id, name, active, start_date, end_date FROM `periods` ORDER BY active DESC, name ASC");
        if (mysqli_num_rows($getPeriods) > 0) // periods exist
        {
            while ($period = mysqli_fetch_array($getPeriods))
            {
                // store period's data in array
                $periods[] = $period;

                // store the acitve period's name
                if ($period["active"] == 1) 
                { 
                    $active_period = $period["id"];
                    $active_period_label = $period["name"]; 
                    $active_start_date = date("m/d/Y", strtotime($period["start_date"]));
                    $active_end_date = date("m/d/Y", strtotime($period["end_date"])); 
                }
            }
        }

        ///////////////////////////////////////////////////////////////////////////////////////////
        //
        //  USER VIEW
        //
        ///////////////////////////////////////////////////////////////////////////////////////////
        if (isset($PERMISSIONS["VIEW_SERVICE_CONTRACTS"]) || isset($PERMISSIONS["VIEW_QUARTERLY_INVOICES"]))
        {
            ?>  
                <style>
                    table.dataTable tbody td {
                        vertical-align: middle !important;
                    }
                </style>

                <div class="container-fluid">
                    <h1 class="mb-0">Customer Files</h1>

                    <div class="dv-parameters-container my-3">
                        <h2 class="px-3 py-2 my-0">Parameters</h2>
                        <div class="dv-parameters-content row px-3 py-2">
                            <div class="col-12 col-md-6 col-lg-4 col-xl-3 col-xxl-2">
                                <label class="mb-0" for="search-period">Fiscal Year</label>
                                <div class="input-group h-auto">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text h-100" id="nav-search-icon"><i class="fa-solid fa-calendar-days"></i></span>
                                    </div>
                                    <input id="fixed-period" type="hidden" value="" aria-hidden="true">
                                    <select class="form-select" id="search-period" name="search-period" onchange="getContracts();">
                                        <option value="-1">All</option>
                                        <?php
                                            for ($p = 0; $p < count($periods); $p++)
                                            {
                                                echo "<option value='".$periods[$p]["id"]."'>".$periods[$p]["name"]."</option>";
                                            }
                                        ?>
                                    </select>
                                </div>
                            </div>

                            <div class="col-12 col-md-6 col-lg-4 col-xl-3 col-xxl-2">
                                <label class="mb-0" for="search-file">File Type</label>
                                <div class="input-group h-auto">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text h-100" id="nav-search-icon"><i class="fa-solid fa-file-lines"></i></span>
                                    </div>
                                    <select class="form-select" id="search-file" name="search-file" onchange="getContracts();">
                                        <option value="-1">All</option>
                                        <?php
                                            $getTypes = mysqli_query($conn, "SELECT id, name FROM contract_types ORDER BY name ASC");
                                            if (mysqli_num_rows($getTypes) > 0)
                                            {
                                                while ($type = mysqli_fetch_assoc($getTypes))
                                                {
                                                    echo "<option value='".$type["id"]."'>".$type["name"]."</option>";
                                                }
                                            }
                                        ?>
                                    </select>
                                </div>
                            </div>

                            <div class="col-12 col-md-6 col-lg-4 col-xl-3 col-xxl-2">
                                <label class="mb-0" for="search-customer">Customer</label>
                                <div class="input-group h-auto">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text h-100" id="nav-search-icon"><i class="fa-solid fa-users-between-lines"></i></span>
                                    </div>
                                    <select class="form-select" id="search-customer" name="search-customer" onchange="getContracts();">
                                        <option value="-1">All</option>
                                        <?php
                                            $getCustomers = mysqli_query($conn, "SELECT DISTINCT c.id, c.name FROM customers c 
                                                                                JOIN contracts_created cc ON c.id=cc.customer_id 
                                                                                ORDER BY c.name ASC");
                                            if (mysqli_num_rows($getCustomers) > 0)
                                            {
                                                while ($customer = mysqli_fetch_assoc($getCustomers))
                                                {
                                                    echo "<option value='".$customer["id"]."'>".$customer["name"]."</option>";
                                                }
                                            }
                                        ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <table id="contracts" class="table table-striped shadow">
                        <thead class="table-header">
                            <tr>
                                <th>Year</th>
                                <th>Customer</th>
                                <th>Contract Type</th>
                                <th>File Name</th>
                                <th>Contract Created</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>

                        <?php createTableFooterV3("contracts", 7, "BAP_DistrictContracts_PageLength", $USER_SETTINGS["page_length"], true, true, false); ?>
                    </table>
                </div>

                <!-- MODALS -->
                <div id="view-contract-modal-div"></div>
                <div id="sign-contract-modal-div"></div>
                <div id="view_report-modal-div"></div>
                <!-- END MODALS -->

                <script>
                    // set parameters to saved parameters in session storage
                    // period
                    let active_period = <?php echo $active_period; ?>;
                    if (sessionStorage["BAP_District_ViewContracts_Period"] != "" && sessionStorage["BAP_District_ViewContracts_Period"] != null && sessionStorage["BAP_District_ViewContracts_Period"] != undefined) { $('#search-period').val(sessionStorage["BAP_District_ViewContracts_Period"]); }
                    else { $("#search-period").val(active_period); }
                    // contract type
                    if (sessionStorage["BAP_District_ViewContracts_ContractType"] != "" && sessionStorage["BAP_District_ViewContracts_ContractType"] != null && sessionStorage["BAP_District_ViewContracts_ContractType"] != undefined) { $('#search-file').val(sessionStorage["BAP_District_ViewContracts_ContractType"]); }
                    // customer
                    if (sessionStorage["BAP_District_ViewContracts_Customer"] != "" && sessionStorage["BAP_District_ViewContracts_Customer"] != null && sessionStorage["BAP_District_ViewContracts_Customer"] != undefined) { $('#search-customer').val(sessionStorage["BAP_District_ViewContracts_Customer"]); }

                    /** function to get contracts */
                    function getContracts()
                    {
                        // get selected period
                        let period_id = document.getElementById("search-period").value;
                        let type = document.getElementById("search-file").value;
                        let customer_id = document.getElementById("search-customer").value;

                        // initialize the contracts table
                        var contracts = $("#contracts").DataTable({
                            ajax: {
                                url: "ajax/contracts/getContracts.php",
                                type: "POST",
                                data: {
                                    period_id: period_id,
                                    type: type,
                                    customer_id: customer_id,
                                },
                                dataSrc: ""
                            },
                            autoWidth: false,
                            destroy: true,
                            async: true,
                            pageLength: <?php if (isset($USER_SETTINGS["page_length"])) { echo $USER_SETTINGS["page_length"]; } else { echo 10; } ?>,
                            lengthMenu: [ [10, 25, 50, 100, 250, -1], [10, 25, 50, 100, 250, "All"] ],
                            columns: [
                                // display columns
                                { data: "year", orderable: true, width: "10%" },
                                { data: "customer", orderable: true, width: "15%" },
                                { data: "title", orderable: true, width: "20%" },
                                { data: "file", orderable: true, width: "25%" },
                                { data: "created", orderable: true, width: "12.5%" },
                                { data: "status", orderable: true, width: "10%", className: "text-center" },
                                { data: "actions", orderable: true, width: "7.5%", className: "text-center" },
                            ],
                            order: [
                                [ 0, "asc" ],
                                [ 1, "asc" ]
                            ],
                            dom: 'rt',
                            language: {
                                search: '<div class="input-group mb-1"><div class="input-group-prepend"><span class="d-flex align-items-center h-100 mx-2" id="nav-search-icon"><i class="fa fa-search" aria-hidden="true"></i></span></div>_INPUT_</div>',
                                lengthMenu: '<div class="mx-1 mb-1">Show _MENU_ entries</div>',
                                info: '<div class="mx-1">Showing _START_ to _END_ of _TOTAL_ entries</div>',
                                loadingRecords: "<i class=\"fa-solid fa-spinner fa-spin\"></i> Loading...",
                            },
                            stateSave: false,
                            initComplete: function() {
                                // store selected period in session storage
                                sessionStorage["BAP_District_ViewContracts_Period"] = period_id;
                                sessionStorage["BAP_District_ViewContracts_ContractType"] = type;
                                sessionStorage["BAP_District_ViewContracts_Customer"] = customer_id;
                            },
                            rowCallback: function (row, data, index) {
                                // initialize page selection
                                updatePageSelection("contracts");
                            },
                        });
                    }

                    /** function to view a contract */
                    function viewContract(contract_id)
                    {
                        // send the data to create the view contract modal
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/contracts/viewContract.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // display the view contract modal
                                document.getElementById("view-contract-modal-div").innerHTML = this.responseText;     
                                $("#viewContractModal").modal("show");
                            }
                        }
                        xmlhttp.send("contract_id="+contract_id);
                    }

                    /** function to get the modal to sign a contract */
                    function getSignContractModal(contract_id)
                    {
                        // send the data to create the view contract modal
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/contracts/getSignContractModal.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // display the view contract modal
                                document.getElementById("sign-contract-modal-div").innerHTML = this.responseText;     
                                $("#signContractModal").modal("show");
                            }
                        }
                        xmlhttp.send("contract_id="+contract_id);
                    }
                    
                    /** function to create the view service contract modal */
                    function getViewServiceContractModal(period_id, customer_id)
                    {
                        // send the data to create the view contract modal
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/contracts/getViewServiceContractModal.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // display the view contract modal
                                document.getElementById("view-contract-modal-div").innerHTML = this.responseText;     
                                $("#viewContractModal").modal("show");
                            }
                        }
                        xmlhttp.send("period_id="+period_id+"&customer_id="+customer_id);
                    }

                    /** function to create the view service contract modal */
                    function getViewQuarterlyInvoiceModal(period_id, quarter, customer_id)
                    {
                        // send the data to create the view contract modal
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/contracts/getViewQuarterlyInvoiceModal.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // display the view contract modal
                                document.getElementById("view-contract-modal-div").innerHTML = this.responseText;     
                                $("#viewContractModal").modal("show");
                            }
                        }
                        xmlhttp.send("period_id="+period_id+"&quarter="+quarter+"&customer_id="+customer_id);
                    }

                    /** function to get the modal to view a PDF report for a district */
                    function getViewDistrictReport(customer_id, period, quarter, filename, internal = 0)
                    {
                        // send the data to create the view contract modal
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/caseloads/getViewDistrictQuarterlyReportModal.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // display the view report modal; hide the view reports modal
                                document.getElementById("view_report-modal-div").innerHTML = this.responseText;     
                                $("#viewDistrictReportModal").modal("show");
                            }
                        }
                        xmlhttp.send("period_id="+period+"&customer_id="+customer_id+"&quarter="+quarter+"&filename="+filename+"&internal="+internal);
                    }

                    // call the function to get contracts on page laod
                    getContracts();
                </script>
            <?php 
        }
        ///////////////////////////////////////////////////////////////////////////////////////////
        //
        //  DISTRICT VIEW
        //
        ///////////////////////////////////////////////////////////////////////////////////////////
        else if (isset($_SESSION["district"]) && $_SESSION["district"]["status"] == 1)
        {
            ?>
                <style>
                    table.dataTable tbody td {
                        vertical-align: middle !important;
                    }
                </style>

                <script>
                    /** function to get the modal to view the contract */
                    function viewContract(contract_id)
                    {
                        // send the data to create the view contract modal
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/contracts/viewContract.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // display the view contract modal
                                document.getElementById("view-contract-modal-div").innerHTML = this.responseText;     
                                $("#viewContractModal").modal("show");
                            }
                        }
                        xmlhttp.send("contract_id="+contract_id);
                    }

                    /** function to get the modal to sign a contract */
                    function getSignContractModal(contract_id)
                    {
                        // send the data to create the view contract modal
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/contracts/getSignContractModal.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // display the view contract modal
                                document.getElementById("sign-contract-modal-div").innerHTML = this.responseText;     
                                $("#signContractModal").modal("show");
                            }
                        }
                        xmlhttp.send("contract_id="+contract_id);
                    }

                    /** function to acknowledge a contract */
                    function acknowledgeContract(contract_id, status)
                    {
                        // store contract form element
                        let form = document.getElementById("contract-form");

                        // validate the form
                        if ((form.checkValidity() && status == 1) || status == 3)
                        {
                            // get parameters
                            let fname = $("#signature-fname").val();
                            let lname = $("#signature-lname").val();
                            let acknowledgement = 0;
                            if ($("#acknowledgement").is(":checked")) { 
                                acknowledgement = 1;
                            }

                            // send the data to accept/reject the contract
                            var xmlhttp = new XMLHttpRequest();
                            xmlhttp.open("POST", "ajax/contracts/acknowledgeContract.php", true);
                            xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                            xmlhttp.onreadystatechange = function() 
                            {
                                if (this.readyState == 4 && this.status == 200)
                                {
                                    // create the status modal
                                    let status_title = "Contract Acknowledgement Status";
                                    let status_body = this.responseText;
                                    createStatusModal("refresh", status_title, status_body);

                                    // hide the sign contract modal 
                                    $("#signContractModal").modal("hide");
                                }
                            }
                            xmlhttp.send("contract_id="+contract_id+"&status="+status+"&fname="+fname+"&lname="+lname+"&acknowledgement="+acknowledgement);
                        } else {
                            form.classList.add("was-validated");
                        }
                    }
                </script>

                <div class="container-fluid">
                    <h1 class="mb-0">District Files</h1>

                    <div class="dv-parameters-container my-3">
                        <h2 class="px-3 py-2 my-0">Parameters</h2>
                        <div class="dv-parameters-content row px-3 py-2">
                            <div class="col-12 col-md-6 col-lg-3 col-xl-2">
                                <label class="mb-0" for="search-period">Fiscal Year</label>
                                <div class="input-group h-auto">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text h-100" id="nav-search-icon"><i class="fa-solid fa-calendar-days"></i></span>
                                    </div>
                                    <input id="fixed-period" type="hidden" value="" aria-hidden="true">
                                    <select class="form-select" id="search-period" name="search-period" onchange="getContracts();">
                                        <option value="-1">All</option>
                                        <?php
                                            for ($p = 0; $p < count($periods); $p++)
                                            {
                                                echo "<option value='".$periods[$p]["id"]."'>".$periods[$p]["name"]."</option>";
                                            }
                                        ?>
                                    </select>
                                </div>
                            </div>

                            <div class="col-12 col-md-6 col-lg-4 col-xl-3 col-xxl-2">
                                <label class="mb-0" for="search-file">File Type</label>
                                <div class="input-group h-auto">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text h-100" id="nav-search-icon"><i class="fa-solid fa-file-lines"></i></span>
                                    </div>
                                    <select class="form-select" id="search-file" name="search-file" onchange="getContracts();">
                                        <option value="-1">All</option>
                                        <?php
                                            $getTypes = mysqli_query($conn, "SELECT id, name FROM contract_types ORDER BY name ASC");
                                            if (mysqli_num_rows($getTypes) > 0)
                                            {
                                                while ($type = mysqli_fetch_assoc($getTypes))
                                                {
                                                    echo "<option value='".$type["id"]."'>".$type["name"]."</option>";
                                                }
                                            }
                                        ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <table id="contracts" class="table table-striped shadow">
                        <thead class="table-header">
                            <tr>
                                <th>Year</th>
                                <th>File Type</th>
                                <th>File Name</th>
                                <th>File Created</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>

                        <?php createTableFooterV3("contracts", 6, "BAP_DistrictContracts_PageLength", $USER_SETTINGS["page_length"], true, true, false); ?>
                    </table>
                </div>

                <!-- MODALS -->
                <div id="view-contract-modal-div"></div>
                <div id="sign-contract-modal-div"></div>
                <div id="view_report-modal-div"></div>
                <!-- END MODALS -->

                <script>
                    // set parameters to saved parameters in session storage
                    if (sessionStorage["BAP_District_ViewContracts_Period"] != "" && sessionStorage["BAP_District_ViewContracts_Period"] != null && sessionStorage["BAP_District_ViewContracts_Period"] != undefined) { $('#search-period').val(sessionStorage["BAP_District_ViewContracts_Period"]); }
                    if (sessionStorage["BAP_District_ViewContracts_ContractType"] != "" && sessionStorage["BAP_District_ViewContracts_ContractType"] != null && sessionStorage["BAP_District_ViewContracts_ContractType"] != undefined) { $('#search-file').val(sessionStorage["BAP_District_ViewContracts_ContractType"]); }

                    /** function to get contracts */
                    function getContracts()
                    {
                        // get selected period
                        let period_id = document.getElementById("search-period").value;
                        let type = document.getElementById("search-file").value;

                        // initialize the contracts table
                        var contracts = $("#contracts").DataTable({
                            ajax: {
                                url: "ajax/contracts/getContracts.php",
                                type: "POST",
                                data: {
                                    period_id: period_id,
                                    type: type,
                                },
                                dataSrc: ""
                            },
                            autoWidth: false,
                            destroy: true,
                            async: true,
                            pageLength: <?php if (isset($USER_SETTINGS["page_length"])) { echo $USER_SETTINGS["page_length"]; } else { echo 10; } ?>,
                            lengthMenu: [ [10, 25, 50, 100, 250, -1], [10, 25, 50, 100, 250, "All"] ],
                            columns: [
                                // display columns
                                { data: "year", orderable: true, width: "10%" },
                                { data: "title", orderable: true, width: "20%" },
                                { data: "file", orderable: true, width: "30%" },
                                { data: "created", orderable: true, width: "15%" },
                                { data: "status", orderable: true, width: "15%", className: "text-center" },
                                { data: "actions", orderable: true, width: "10%", className: "text-center" },
                            ],
                            order: [
                                [ 0, "asc" ],
                                [ 1, "asc" ]
                            ],
                            dom: 'rt',
                            language: {
                                search: '<div class="input-group mb-1"><div class="input-group-prepend"><span class="d-flex align-items-center h-100 mx-2" id="nav-search-icon"><i class="fa fa-search" aria-hidden="true"></i></span></div>_INPUT_</div>',
                                lengthMenu: '<div class="mx-1 mb-1">Show _MENU_ entries</div>',
                                info: '<div class="mx-1">Showing _START_ to _END_ of _TOTAL_ entries</div>',
                                loadingRecords: "<i class=\"fa-solid fa-spinner fa-spin\"></i> Loading...",
                            },
                            stateSave: false,
                            initComplete: function() {
                                // store selected period in session storage
                                sessionStorage["BAP_District_ViewContracts_Period"] = period_id;
                                sessionStorage["BAP_District_ViewContracts_ContractType"] = type;
                            },
                            rowCallback: function (row, data, index) {
                                // initialize page selection
                                updatePageSelection("contracts");
                            },
                        });
                    }

                    // call the function to get contracts on page laod
                    getContracts();
                </script>
            <?php
        }
        else { denyAccess(); }

        // disconnect from the database
        mysqli_close($conn);
    }
    else { goToLogin(); }

    include_once("footer.php"); 
?>