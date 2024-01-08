<?php 
    include_once("header.php");

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1) 
    { 
        if (isset($PERMISSIONS["VIEW_CUSTOMERS"]))
        {
            // connect to the database
            $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

            // initialize an array to store all periods; then get all periods and store in the array
            $getPeriods = mysqli_query($conn, "SELECT id, name, active FROM `periods` ORDER BY active DESC, name ASC");
            if (mysqli_num_rows($getPeriods) > 0) // periods exist
            {
                while ($period = mysqli_fetch_array($getPeriods))
                {
                    // store period's data in array
                    $periods[] = $period;

                    // store the acitve period's name
                    if ($period["active"] == 1) { $active_period_label = $period["name"]; }
                }
            }

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

                <script>
                    /** function to add a new customer */
                    function addCustomer()
                    {
                        // create the string of data to send
                        let sendString = "";

                        // get customer details from the modal
                        let customer_id = encodeURIComponent(document.getElementById("add-customer_id").value);
                        let customer_name = encodeURIComponent(document.getElementById("add-customer_name").value);
                        let location_code = encodeURIComponent(document.getElementById("add-location_code").value);
                        let members = encodeURIComponent(document.getElementById("add-members").value);
                        let invoice_number = encodeURIComponent(document.getElementById("add-invoice_number").value);
                        let sc_folder_id = encodeURIComponent(document.getElementById("add-sc-folder_id").value);
                        let qi_folder_id = encodeURIComponent(document.getElementById("add-qi-folder_id").value);
                        let cb_folder_id = encodeURIComponent(document.getElementById("add-cb-folder_id").value);
                        sendString += "customer_id="+customer_id+"&customer_name="+customer_name+"&location_code="+location_code+"&members="+members+"&invoice_number="+invoice_number+"&contract_folder_id="+sc_folder_id+"&invoice_folder_id="+qi_folder_id+"&caseload_billing_folder_id="+cb_folder_id;

                        // get customer address from the modal
                        let address_street = encodeURIComponent(document.getElementById("add-address_street").value);
                        let address_city = encodeURIComponent(document.getElementById("add-address_city").value);
                        let address_state = encodeURIComponent(document.getElementById("add-address_state").value);
                        let address_zip = encodeURIComponent(document.getElementById("add-address_zip").value);
                        sendString += "&address_street="+address_street+"&address_city="+address_city+"&address_state="+address_state+"&address_zip="+address_zip;

                        // get primary contact from the modal
                        let pc_fname = encodeURIComponent(document.getElementById("add-pc_fname").value);
                        let pc_lname = encodeURIComponent(document.getElementById("add-pc_lname").value);
                        let pc_email = encodeURIComponent(document.getElementById("add-pc_email").value);
                        let pc_phone = encodeURIComponent(document.getElementById("add-pc_phone").value);
                        let pc_title = encodeURIComponent(document.getElementById("add-pc_title").value);
                        sendString += "&pc_fname="+pc_fname+"&pc_lname="+pc_lname+"&pc_email="+pc_email+"&pc_phone="+pc_phone+"&pc_title="+pc_title;

                        // get secondary contact from the modal
                        let sc_fname = encodeURIComponent(document.getElementById("add-sc_fname").value);
                        let sc_lname = encodeURIComponent(document.getElementById("add-sc_lname").value);
                        let sc_email = encodeURIComponent(document.getElementById("add-sc_email").value);
                        let sc_phone = encodeURIComponent(document.getElementById("add-sc_phone").value);
                        let sc_title = encodeURIComponent(document.getElementById("add-sc_title").value);
                        sendString += "&sc_fname="+sc_fname+"&sc_lname="+sc_lname+"&sc_email="+sc_email+"&sc_phone="+sc_phone+"&sc_title="+sc_title;
                        
                        // send the data to process the add customer request
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/customers/addCustomer.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // create the status modal
                                let status_title = "Add Customer Status";
                                let status_body = this.responseText;
                                createStatusModal("refresh", status_title, status_body);

                                // hide the current modal
                                $("#addCustomerModal").modal("hide");
                            }
                        };
                        xmlhttp.send(sendString);
                    }

                    /** function to edit the customer */
                    function editCustomer(id)
                    {
                        // get the customer ID from the modal
                        let customer_id = document.getElementById("edit-customer_id").value;

                        // check to see if IDs match; if so, continue edit request
                        if (customer_id == id)
                        {
                            // create the string of data to send
                            let sendString = "";

                            // get customer details from the modal
                            let customer_name = encodeURIComponent(document.getElementById("edit-customer_name").value);
                            let location_code = encodeURIComponent(document.getElementById("edit-location_code").value);
                            let members = encodeURIComponent(document.getElementById("edit-members").value);
                            let invoice_number = encodeURIComponent(document.getElementById("edit-invoice_number").value);
                            let contract_folder_id = encodeURIComponent(document.getElementById("edit-sc-folder_id").value);
                            let invoice_folder_id = encodeURIComponent(document.getElementById("edit-qi-folder_id").value);
                            let caseload_billing_folder_id = encodeURIComponent(document.getElementById("edit-cb-folder_id").value);
                            sendString += "customer_id="+customer_id+"&customer_name="+customer_name+"&location_code="+location_code+"&members="+members+"&invoice_number="+invoice_number+"&contract_folder_id="+contract_folder_id+"&invoice_folder_id="+invoice_folder_id+"&caseload_billing_folder_id="+caseload_billing_folder_id;

                            // get customer address from the modal
                            let address_street = encodeURIComponent(document.getElementById("edit-address_street").value);
                            let address_city = encodeURIComponent(document.getElementById("edit-address_city").value);
                            let address_state = encodeURIComponent(document.getElementById("edit-address_state").value);
                            let address_zip = encodeURIComponent(document.getElementById("edit-address_zip").value);
                            sendString += "&address_street="+address_street+"&address_city="+address_city+"&address_state="+address_state+"&address_zip="+address_zip;

                            // get primary contact from the modal
                            let pc_fname = encodeURIComponent(document.getElementById("edit-pc_fname").value);
                            let pc_lname = encodeURIComponent(document.getElementById("edit-pc_lname").value);
                            let pc_email = encodeURIComponent(document.getElementById("edit-pc_email").value);
                            let pc_phone = encodeURIComponent(document.getElementById("edit-pc_phone").value);
                            let pc_title = encodeURIComponent(document.getElementById("edit-pc_title").value);
                            sendString += "&pc_fname="+pc_fname+"&pc_lname="+pc_lname+"&pc_email="+pc_email+"&pc_phone="+pc_phone+"&pc_title="+pc_title;

                            // get secondary contact from the modal
                            let sc_fname = encodeURIComponent(document.getElementById("edit-sc_fname").value);
                            let sc_lname = encodeURIComponent(document.getElementById("edit-sc_lname").value);
                            let sc_email = encodeURIComponent(document.getElementById("edit-sc_email").value);
                            let sc_phone = encodeURIComponent(document.getElementById("edit-sc_phone").value);
                            let sc_title = encodeURIComponent(document.getElementById("edit-sc_title").value);
                            sendString += "&sc_fname="+sc_fname+"&sc_lname="+sc_lname+"&sc_email="+sc_email+"&sc_phone="+sc_phone+"&sc_title="+sc_title;

                            // send the data to process the edit customer request
                            var xmlhttp = new XMLHttpRequest();
                            xmlhttp.open("POST", "ajax/customers/editCustomer.php", true);
                            xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                            xmlhttp.onreadystatechange = function() 
                            {
                                if (this.readyState == 4 && this.status == 200)
                                {
                                    // create the status modal
                                    let status_title = "Edit Customer Status";
                                    let status_body = this.responseText;
                                    createStatusModal("refresh", status_title, status_body);

                                    // hide the current modal
                                    $("#editCustomerModal").modal("hide");
                                }
                            };
                            xmlhttp.send(sendString);
                        }
                    }

                    /** function to delete the customer */
                    function deleteCustomer(id)
                    {
                        // send the data to process the edit customer request
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/customers/deleteCustomer.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // create the status modal
                                let status_title = "Delete Customer Status";
                                let status_body = this.responseText;
                                createStatusModal("refresh", status_title, status_body);

                                // hide the current modal
                                $("#deleteCustomerModal").modal("hide");
                            }
                        };
                        xmlhttp.send("customer_id="+id);
                    }

                    /** function to get the edit customer modal */
                    function getEditCustomerModal(customer_id)
                    {
                        // send the data to create the edit customer modal
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/customers/getEditCustomerModal.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                document.getElementById("edit-customer-modal-div").innerHTML = this.responseText;     

                                // display the edit customer modal
                                $("#editCustomerModal").modal("show");
                            }
                        };
                        xmlhttp.send("customer_id="+customer_id);
                    }

                    /** function to get the delete customer modal */
                    function getDeleteCustomerModal(customer_id)
                    {
                        // send the data to create the edit customer modal
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/customers/getDeleteCustomerModal.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                document.getElementById("delete-customer-modal-div").innerHTML = this.responseText;     

                                // display the edit customer modal
                                $("#deleteCustomerModal").modal("show");
                            }
                        };
                        xmlhttp.send("customer_id="+customer_id);
                    }

                    /** function to get the view customer services modal */
                    function getViewServicesModal(customer_id, period_id)
                    {
                        // send the data to create the view services modal
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/customers/getViewCustomerServicesModal.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                document.getElementById("view-services-modal-div").innerHTML = this.responseText;     

                                // display the view customer services modal
                                $("#viewCustomerServicesModal").modal("show");
                            }
                        };
                        xmlhttp.send("customer_id="+customer_id+"&period_id="+period_id);
                    }

                    /** function to get the modal to view customer users */
                    function getViewCustomerUsersModal(customer_id)
                    {
                        // send the data to create the view services modal
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/customers/getViewCustomerUsersModal.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // initialize the view role users table                  
                                $(document).ready(function () {
                                    var role_users = $("#customer_users").DataTable({
                                        autoWidth: false,
                                        pageLength: <?php if (isset($USER_SETTINGS["page_length"])) { echo $USER_SETTINGS["page_length"]; } else { echo 10; } ?>,
                                        lengthMenu: [ [10, 25, 50, 100, 250, -1], [10, 25, 50, 100, 250, "All"] ],
                                        columns: [
                                            { data: "id", orderable: true, width: "10%", className: "text-center" },
                                            { data: "lname", orderable: true, width: "15%", className: "text-center" },
                                            { data: "fname", orderable: true, width: "15%", className: "text-center" },
                                            { data: "email", orderable: true, width: "40%", className: "text-center" },
                                            { data: "role", orderable: true, width: "20%", className: "text-center" },
                                        ],
                                        dom: 'rt',
                                        language: {
                                            search: '<div class="input-group mb-1"><div class="input-group-prepend"><span class="d-flex align-items-center h-100 mx-2" id="nav-search-icon"><i class="fa fa-search" aria-hidden="true"></i></span></div>_INPUT_</div>',
                                            lengthMenu: '<div class="mx-1 mb-1">Show _MENU_ entries</div>',
                                            info: '<div class="mx-1">Showing _START_ to _END_ of _TOTAL_ entries</div>'
                                        },
                                        order: [
                                            [ 1, "asc" ],
                                            [ 2, "asc" ]
                                        ],
                                        rowCallback: function (row, data, index)
                                        {
                                            // initialize page selection dropdown
                                            updatePageSelection("customer_users");
                                        },
                                    });
                                });

                                // display the view customer services modal
                                document.getElementById("view-users-modal-div").innerHTML = this.responseText;     
                                $("#viewCustomerUsersModal").modal("show");
                            }
                        };
                        xmlhttp.send("customer_id="+customer_id);
                    }
                </script>

                <div class="report">
                    <!-- Page Header -->
                    <div class="table-header p-0">
                        <div class="row d-flex justify-content-center align-items-center text-center py-2 px-3 m-0">
                            <!-- Period & Filters-->
                            <div class="col-6 col-sm-4 col-md-4 col-lg-3 col-xl-2 col-xxl-2 p-0">
                                <div class="row p-0">
                                    <!-- Period Selection -->
                                    <div class="col-9 p-0">
                                        <div class="row mb-1">
                                            <div class="input-group h-auto">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text h-100" id="nav-search-icon">
                                                        <i class="fa-solid fa-calendar-days"></i>
                                                    </span>
                                                </div>
                                                <input id="fixed-period" type="hidden" value="" aria-hidden="true">
                                                <select class="form-select" id="search-period" name="search-period" onchange="searchCustomers();">
                                                    <?php
                                                        for ($p = 0; $p < count($periods); $p++)
                                                        {
                                                            echo "<option value='".$periods[$p]["name"]."'>".$periods[$p]["name"]."</option>";
                                                        }
                                                    ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Filters -->
                                    <div class="col-3 d-flex ps-2 py-0">
                                        <div class="dropdown float-start">
                                            <button class="btn btn-primary h-100" type="button" id="filtersMenu" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="fa-solid fa-magnifying-glass"></i>
                                            </button>
                                            <div class="dropdown-menu filters-menu px-2" aria-labelledby="filtersMenu" style="width: 288px;">
                                                <!-- Search Table -->
                                                <div class="row mx-0 mt-0 mb-2">
                                                    <div class="input-group h-auto p-0">
                                                        <div class="input-group-prepend">
                                                            <span class="input-group-text h-100" id="nav-search-icon">
                                                                <label for="search-all"><i class="fa-solid fa-magnifying-glass"></i></label>
                                                            </span>
                                                        </div>
                                                        <input class="form-control" type="text" placeholder="Search table" id="search-all" name="search-all" autocomplete="off">
                                                    </div>
                                                </div>

                                                <!-- Filter By Group -->
                                                <div class="row d-flex justify-content-between align-items-center mx-0 mt-0 mb-2">
                                                    <div class="col-4 ps-0 pe-1">
                                                        <label for="search-groups">Group:</label>
                                                    </div>

                                                    <div class="col-8 ps-1 pe-0">
                                                        <select class="form-select" id="search-groups" name="search-groups">
                                                            <option></option>
                                                            <?php
                                                                $getGroups = mysqli_query($conn, "SELECT id, name FROM `groups` ORDER BY name ASC");
                                                                if (mysqli_num_rows($getGroups) > 0) // groups exist
                                                                {
                                                                    while ($group = mysqli_fetch_array($getGroups))
                                                                    {
                                                                        echo "<option value='".$group["name"]."'>".$group["name"]."</option>";
                                                                    }
                                                                }
                                                            ?>
                                                        </select>
                                                    </div>
                                                </div>

                                                <!-- Clear Filters -->
                                                <div class="row m-0">
                                                    <button class="btn btn-secondary w-100" id="clearFilters"><i class="fa-solid fa-xmark"></i> Clear Filters</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Page Header -->
                            <div class="col-12 col-sm-12 col-md-12 col-lg-12 col-xl-8 col-xxl-8 p-0">
                                <h1 class="m-0">Customers</h1>
                            </div>

                            <!-- Page Management Dropdown -->
                            <div class="col-6 col-sm-4 col-md-4 col-lg-3 col-xl-2 col-xxl-2 d-flex justify-content-end p-0">
                                <?php if (isset($PERMISSIONS["ADD_CUSTOMERS"]) || $_SESSION["role"] == 1) { ?> 
                                    <div class="dropdown float-end">
                                        <button class="btn btn-primary dropdown-toggle" type="button" id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">
                                            Manage Customers
                                        </button>
                                        <ul class="dropdown-menu p-0" aria-labelledby="dropdownMenuButton1">
                                            <li><button class="dropdown-item quickNav-dropdown-item text-center w-100 px-3 py-2 rounded-0" type="button" data-bs-toggle="modal" data-bs-target="#addCustomerModal">Add Customer</button></li>
                                            <li><button class="dropdown-item quickNav-dropdown-item text-center w-100 px-3 py-2 rounded-0" type="button" data-bs-toggle="modal" data-bs-target="#uploadCustomersModal">Upload Customers</button></li>
                                            <?php if ($_SESSION["role"] == 1) { ?><li><button class="dropdown-item quickNav-dropdown-item text-center w-100 px-3 py-2 rounded-0" type="button" data-bs-toggle="modal" data-bs-target="#uploadInvoiceNumbersModal">Upload Invoice Numbers</button></li><?php } ?>
                                        </ul>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row report-body m-0">
                    <table id="customers" class="report_table w-100">
                        <thead>
                            <tr>
                                <th class="text-center py-1 px-2">ID</th>
                                <th class="text-center py-1 px-2">Name</th>
                                <th class="text-center py-1 px-2">Location Code</th>
                                <th class="text-center py-1 px-2">Address</th>
                                <th class="text-center py-1 px-2">Contacts</th>
                                <th class="text-center py-1 px-2">Users</th>
                                <th class="text-center py-1 px-2"><span id="table-period-label"></span> Services</th>
                                <th class="text-center py-1 px-2">Members</th>
                                <th class="text-center py-1 px-2">Actions</th>
                                <th class="text-center py-1 px-2">Groups</th>
                            </tr>
                        </thead>
                    </table>
                    <?php createTableFooterV2("customers", "BAP_Customers_PageLength", $USER_SETTINGS["page_length"], true, true); ?>
                </div>

                <!--
                    ### MODALS ###
                -->
                <!-- Add Customer Modal -->
                <div class="modal fade" tabindex="-1" role="dialog" id="addCustomerModal" data-bs-backdrop="static" aria-labelledby="addCustomerModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg" role="document">
                        <div class="modal-content">
                            <div class="modal-header primary-modal-header">
                                <h5 class="modal-title primary-modal-title" id="addCustomerModalLabel">Add Customer</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>

                            <div class="modal-body">
                                <!-- Customer Details -->
                                <fieldset class="form-group border p-3 mb-3">
                                    <legend class="w-auto px-2 m-0 float-none fieldset-legend">Customer Details</legend>

                                    <div class="row align-items-center my-2">
                                        <div class="col-3 text-end"><label for="add-customer_id"><span class="required-field">*</span> Customer ID:</label></div>
                                        <div class="col-9"><input type="number" class="form-control w-100" id="add-customer_id" name="add-customer_id" required></div>
                                    </div>

                                    <div class="row align-items-center my-2">
                                        <div class="col-3 text-end"><label for="add-customer_name"><span class="required-field">*</span> Name:</label></div>
                                        <div class="col-9"><input type="text" class="form-control w-100" id="add-customer_name" name="add-customer_name" required></div>
                                    </div>

                                    <div class="row align-items-center my-2">
                                        <div class="col-3 text-end"><label for="add-location_code"><span class="required-field">*</span> Location Code:</label></div>
                                        <div class="col-9"><input type="text" class="form-control w-100" id="add-location_code" name="add-location_code" maxlength="3" required></div>
                                    </div>

                                    <div class="row align-items-center my-2">
                                        <div class="col-3 text-end"><label for="add-members">Members:</label></div>
                                        <div class="col-9"><input type="number" class="form-control w-100" id="add-members" name="add-members"></div>
                                    </div>

                                    <div class="row align-items-center my-2">
                                        <div class="col-3 text-end"><label for="add-invoice_number">Invoice Number:</label></div>
                                        <div class="col-9"><input type="text" class="form-control w-100" id="add-invoice_number" name="add-invoice_number" required></div>
                                    </div>

                                    <div class="row align-items-center my-2">
                                        <div class="col-3 text-end"><label for="add-sc-folder_id">Service Contract Folder ID:</label></div>
                                        <div class="col-9"><input type="text" class="form-control w-100" id="add-sc-folder_id" name="add-sc-folder_id"></div>
                                    </div>

                                    <div class="row align-items-center my-2">
                                        <div class="col-3 text-end"><label for="add-qi-folder_id">Quarterly Invoice Folder ID:</label></div>
                                        <div class="col-9"><input type="text" class="form-control w-100" id="add-qi-folder_id" name="add-qi-folder_id"></div>
                                    </div>

                                    <div class="row align-items-center my-2">
                                        <div class="col-3 text-end"><label for="add-cb-folder_id">SPED Billing Details Folder ID:</label></div>
                                        <div class="col-9"><input type="text" class="form-control w-100" id="add-cb-folder_id" name="add-cb-folder_id"></div>
                                    </div>
                                </fieldset>

                                <!-- Customer Address -->
                                <fieldset class="form-group border p-3 mb-3">
                                    <legend class="w-auto px-2 m-0 float-none fieldset-legend">Customer Address</legend>

                                    <div class="row align-items-center my-2">
                                        <div class="col-3 text-end"><label for="add-address_street"><span class="required-field">*</span> Street:</label></div>
                                        <div class="col-9"><input type="text" class="form-control w-100" id="add-address_street" name="add-address_street" required></div>
                                    </div>

                                    <div class="row align-items-center my-2">
                                        <div class="col-3 text-end"><label for="add-address_city"><span class="required-field">*</span> City:</label></div>
                                        <div class="col-9"><input type="text" class="form-control w-100" id="add-address_city" name="add-address_city" required></div>
                                    </div>

                                    <div class="row align-items-center my-2">
                                        <div class="col-3 text-end"><label for="add-address_state"><span class="required-field">*</span> State:</label></div>
                                        <div class="col-9">
                                            <select class="form-select w-100" id="add-address_state" name="add-address_state" required>
                                                <option value=0></option>
                                                <?php
                                                    $getStates = mysqli_query($conn, "SELECT id, state FROM states");
                                                    while ($state = mysqli_fetch_array($getStates)) 
                                                    { 
                                                        if ($state["state"] == "Wisconsin") { echo "<option value='".$state["id"]."' selected>".$state["state"]."</option>"; }
                                                        else { echo "<option value='".$state["id"]."'>".$state["state"]."</option>"; }
                                                    }
                                                ?>
                                            </select> 
                                        </div>
                                    </div>

                                    <div class="row align-items-center my-2">
                                        <div class="col-3 text-end"><label for="add-address_zip"><span class="required-field">*</span> Zip Code:</label></div>
                                        <div class="col-9"><input type="text" class="form-control w-100" id="add-address_zip" name="add-address_zip" required></div>
                                    </div>
                                </fieldset>

                                <!-- Primary Contact -->
                                <fieldset class="form-group border p-3 mb-3">
                                    <legend class="w-auto px-2 m-0 float-none fieldset-legend">Primary Contact</legend>

                                    <div class="row align-items-center my-2">
                                        <div class="col-3 text-end"><label for="add-pc_fname">First Name:</label></div>
                                        <div class="col-9"><input type="text" class="form-control w-100" id="add-pc_fname" name="add-pc_fname" required></div>
                                    </div>

                                    <div class="row align-items-center my-2">
                                        <div class="col-3 text-end"><label for="add-pc_lname">Last Name:</label></div>
                                        <div class="col-9"><input type="text" class="form-control w-100" id="add-pc_lname" name="add-pc_lname" required></div>
                                    </div>

                                    <div class="row align-items-center my-2">
                                        <div class="col-3 text-end"><label for="add-pc_email">Email:</label></div>
                                        <div class="col-9"><input type="text" class="form-control w-100" id="add-pc_email" name="add-pc_email" required></div>
                                    </div>

                                    <div class="row align-items-center my-2">
                                        <div class="col-3 text-end"><label for="add-pc_phone">Phone:</label></div>
                                        <div class="col-9"><input type="text" class="form-control w-100" id="add-pc_phone" name="add-pc_phone"></div>
                                    </div>

                                    <div class="row align-items-center my-2">
                                        <div class="col-3 text-end"><label for="add-pc_title">Title:</label></div>
                                        <div class="col-9"><input type="text" class="form-control w-100" id="add-pc_title" name="add-pc_title" required></div>
                                    </div>
                                </fieldset>

                                <!-- Secondary Contact -->
                                <fieldset class="form-group border p-3 mb-3">
                                    <legend class="w-auto px-2 m-0 float-none fieldset-legend">Secondary Contact</legend>

                                    <div class="row align-items-center my-2">
                                        <div class="col-3 text-end"><label for="add-sc_fname">First Name:</label></div>
                                        <div class="col-9"><input type="text" class="form-control w-100" id="add-sc_fname" name="add-sc_fname"></div>
                                    </div>

                                    <div class="row align-items-center my-2">
                                        <div class="col-3 text-end"><label for="add-sc_lname">Last Name:</label></div>
                                        <div class="col-9"><input type="text" class="form-control w-100" id="add-sc_lname" name="add-sc_lname"></div>
                                    </div>

                                    <div class="row align-items-center my-2">
                                        <div class="col-3 text-end"><label for="add-sc_email">Email:</label></div>
                                        <div class="col-9"><input type="text" class="form-control w-100" id="add-sc_email" name="add-sc_email"></div>
                                    </div>
                                    
                                    <div class="row align-items-center my-2">
                                        <div class="col-3 text-end"><label for="add-sc_phone">Phone:</label></div>
                                        <div class="col-9"><input type="text" class="form-control w-100" id="add-sc_phone" name="add-sc_phone"></div>
                                    </div>

                                    <div class="row align-items-center my-2">
                                        <div class="col-3 text-end"><label for="add-sc_title">Title:</label></div>
                                        <div class="col-9"><input type="text" class="form-control w-100" id="add-sc_title" name="add-sc_title" required></div>
                                    </div>
                                </fieldset>
                            </div>

                            <div class="modal-footer">
                                <button type="button" class="btn btn-primary" onclick="addCustomer();"><i class="fa-solid fa-floppy-disk"></i> Save New Customer</button>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> Close</button>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- End Add Customer Modal -->

                <!-- Upload Customers Modal -->
                <div class="modal fade" tabindex="-1" role="dialog" id="uploadCustomersModal" data-bs-backdrop="static" aria-labelledby="uploadCustomersModalLabel" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header primary-modal-header">
                                <h5 class="modal-title primary-modal-title" id="uploadCustomersModalLabel">Upload Customers</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>

                            <form action="processUploadCustomers.php" method="POST" enctype="multipart/form-data">
                                <div class="modal-body">
                                    <p><label for="fileToUpload">Select a CSV file following the <a class="template-link" href="https://docs.google.com/spreadsheets/d/1LKriiVh2A6ykOUtsiQqYKSUc8SMKK-NPBF7jYFb_tZQ/copy" target="_blank">correct upload template</a> to upload...</label></p>
                                    <input type="file" name="fileToUpload" id="fileToUpload">
                                </div>

                                <div class="modal-footer">
                                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-cloud-arrow-up"></i> Upload Customers</button>
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> Close</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <!-- End Upload Customers Modal -->

                <!-- Upload Invoice Numbers Modal -->
                <div class="modal fade" tabindex="-1" role="dialog" id="uploadInvoiceNumbersModal" data-bs-backdrop="static" aria-labelledby="uploadInvoiceNumbersModalLabel" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header primary-modal-header">
                                <h5 class="modal-title primary-modal-title" id="uploadInvoiceNumbersModalLabel">Upload Invoice Numbers</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>

                            <form action="processUploadInvoiceNumbers.php" method="POST" enctype="multipart/form-data">
                                <div class="modal-body">
                                    <p><label for="fileToUpload">Select a CSV file following the <a class="template-link" href="https://docs.google.com/spreadsheets/d/1TnS3VIw48Hu5lbeAZ4XRSnYrb9QkCGl7MOz_tFaAMe8/copy" target="_blank">correct upload template</a> to upload...</label></p>
                                    <input type="file" name="fileToUpload" id="fileToUpload">
                                </div>

                                <div class="modal-footer">
                                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-cloud-arrow-up"></i> Upload Invoice Numbers</button>
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> Close</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <!-- End Upload Invoice Numbers Modal -->

                <!-- Edit Customer Modal -->
                <div id="edit-customer-modal-div"></div>
                <!-- End Edit Customer Modal -->

                <!-- Delete Customer Modal -->
                <div id="delete-customer-modal-div"></div>
                <!-- End Delete Customer Modal -->

                <!-- View Customer Services Modal -->
                <div id="view-services-modal-div"></div>
                <!-- End View Customer Services Modal -->

                <!-- View Customer Users Modal -->
                <div id="view-users-modal-div"></div>
                <!-- End View Customer Users Modal -->

                <script>
                    // initialize the variable to indicate if we have drawn the table
                    var drawn = 0;

                    // get the current active period
                    let active_period = "<?php echo $active_period_label; ?>";

                    // set page length to prior saved state
                    let saved_page_length = sessionStorage["BAP_Customers_PageLength"];
                    if (saved_page_length != "" && saved_page_length != null && saved_page_length != undefined)
                    {
                        $("#customers-DT_PageLength").val(sessionStorage["BAP_Customers_PageLength"]);
                    }

                    // set the search filters to values we have saved in storage
                    if (sessionStorage["BAP_Customers_Search_Period"] != "" && sessionStorage["BAP_Customers_Search_Period"] != null && sessionStorage["BAP_Customers_Search_Period"] != undefined) { $('#search-period').val(sessionStorage["BAP_Customers_Search_Period"]); }
                    else { $('#search-period').val(active_period); } // no period set; default to active period 
                    $('#search-all').val(sessionStorage["BAP_Customers_Search_All"]);
                    $('#search-groups').val(sessionStorage["BAP_Customers_Search_Group"]);

                    /** function to generate the invoices table based on the period selected */
                    function searchCustomers()
                    {
                        // get the value of the period we are searching
                        var period = document.getElementById("search-period").value;

                        if (period != "" && period != null && period != undefined)
                        {
                            // update the table header
                            document.getElementById("table-period-label").innerHTML = period;

                            // update session storage stored search parameter
                            sessionStorage["BAP_Customers_Search_Period"] = period;

                            // if we have already drawn the table, destroy existing table
                            if (drawn == 1) { $("#customers").DataTable().destroy(); }

                            var customers = $("#customers").DataTable({
                                ajax: {
                                    url: "ajax/customers/getCustomers.php",
                                    type: "POST",
                                    data: {
                                        period: period
                                    }
                                },
                                autoWidth: false,
                                pageLength: <?php if (isset($USER_SETTINGS["page_length"])) { echo $USER_SETTINGS["page_length"]; } else { echo 10; } ?>,
                                lengthMenu: [ [10, 25, 50, 100, 250, -1], [10, 25, 50, 100, 250, "All"] ],
                                columns: [
                                    { data: "id", orderable: true, width: "5%", className: "text-center" },
                                    { data: "name", orderable: true, width: "15%", className: "text-center" },
                                    { data: "location_code", orderable: true, width: "7.5%", className: "text-center" },
                                    { data: "address", orderable: false, width: "15%" },
                                    { data: "contacts", orderable: false, width: "12.5%" },
                                    { data: "users", orderable: false, width: "7.5%" },
                                    { data: "services", orderable: false, width: "12.5%" },
                                    { data: "members", orderable: true, width: "6.5%", className: "text-center" },
                                    <?php if (isset($PERMISSIONS["EDIT_CUSTOMERS"]) || isset($PERMISSIONS["DELETE_CUSTOMERS"])) { ?>
                                    { data: "actions", orderable: false, width: "7.5%", className: "text-center" },
                                    <?php } else { ?>
                                    { data: "actions", orderable: false, visible: false },
                                    <?php } ?>
                                    { data: "groups_string", orderable: true, visible: false }
                                ],
                                order: [
                                    [1, "asc"],
                                    [0, "asc"]
                                ],
                                dom: 'rt',
                                language: {
                                    search: '<div class="input-group mb-1"><div class="input-group-prepend"><span class="d-flex align-items-center h-100 mx-2" id="nav-search-icon"><i class="fa fa-search" aria-hidden="true"></i></span></div>_INPUT_</div>',
                                    lengthMenu: '<div class="mx-1 mb-1">Show _MENU_ entries</div>',
                                    info: '<div class="mx-1">Showing _START_ to _END_ of _TOTAL_ entries</div>'
                                },
                                stateSave: true,
                                rowCallback: function (row, data, index)
                                {
                                    // initialie page selection
                                    updatePageSelection("customers");

                                    // initialize popovers
                                    $(document).ready(function(){
                                        $('[data-bs-toggle="popover"]').popover({
                                            trigger: "hover click", // triggers on hover and click
                                            placement: "bottom",
                                            container: "body",
                                            html: true,
                                        });
                                    });
                                },
                            });

                            // mark that we have drawn the table
                            drawn = 1;

                            // search table by custom search filter
                            $('#search-all').keyup(function() {
                                customers.search($(this).val()).draw();
                                sessionStorage["BAP_Customers_Search_All"] = $(this).val();
                            });

                            // search the hidden "Groups" column
                            $('#search-groups').change(function() {
                                customers.columns(8).search($(this).val()).draw();
                                sessionStorage["BAP_Customers_Search_Group"] = $(this).val();
                            });
                            
                            // function to clear search filters
                            $('#clearFilters').click(function() {
                                sessionStorage["BAP_Customers_Search_Group"] = "";
                                sessionStorage["BAP_Customers_Search_All"] = "";
                                $('#search-all').val("");
                                $('#search-groups').val("");
                                customers.search("").columns().search("").draw();
                            });
                        }
                    }

                    // search customers based on default parameters
                    searchCustomers();
                </script>
            <?php 

            // disconnect from the database
            mysqli_close($conn);
        }
        else { denyAccess(); }
    }
    else { goToLogin(); }

    include("footer.php"); 
?>