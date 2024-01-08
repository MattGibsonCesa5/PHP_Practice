<?php 
    include_once("header.php");

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1) 
    { 
        if (isset($PERMISSIONS["VIEW_CUSTOMER_GROUPS"]))
        {
            // connect to the database
            $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

            ?>
                <script>
                    /** function to add a new group */
                    function addGroup()
                    {
                        // create the string of data to send
                        let sendString = "";

                        // get group members
                        let members_table = $('#add-group_members').DataTable();
                        let count = members_table.rows({ selected: true }).count();
                        let members = [];
                        for (let m = 0; m < count; m++) { members.push(members_table.rows({ selected: true }).data()[m]["id"]); }

                        // get group details from the modal
                        let name = encodeURIComponent(document.getElementById("add-name").value);
                        let desc = encodeURIComponent(document.getElementById("add-desc").value);
                        sendString += "name="+name+"&desc="+desc+"&members="+JSON.stringify(members);
                        
                        // send the data to process the add customer request
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/customers/addGroup.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // create the status modal
                                let status_title = "Add Group Status";
                                let status_body = this.responseText;
                                createStatusModal("refresh", status_title, status_body);

                                // hide the current modal
                                $("#addGroupModal").modal("hide");
                            }
                        };
                        xmlhttp.send(sendString);
                    }

                    /** function to get the delete group modal */
                    function getDeleteGroupModal(group_id)
                    {
                        // send the data to create the edit group modal
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/customers/getDeleteGroupModal.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                document.getElementById("delete-group-modal-div").innerHTML = this.responseText;     

                                // display the edit group modal
                                $("#deleteGroupModal").modal("show");
                            }
                        };
                        xmlhttp.send("group_id="+group_id);
                    }

                    /** function to get the invoice group modal */
                    function getInvoiceGroupModal(group_id)
                    {
                        // send the data to create the edit group modal
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/customers/getInvoiceGroupModal.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                document.getElementById("invoice-group-modal-div").innerHTML = this.responseText;

                                $(function() {
                                    $("#bill-date").daterangepicker({
                                        singleDatePicker: true,
                                        showDropdowns: true,
                                        minYear: 2000,
                                        maxYear: <?php echo date("Y") + 10; ?>
                                    });
                                });

                                // display the edit group modal
                                $("#invoiceGroupModal").modal("show");
                            }
                        };
                        xmlhttp.send("group_id="+group_id);
                    }

                    /** function to get the delete group modal */
                    function getEditGroupModal(group_id)
                    {
                        // send the data to create the edit group modal
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/customers/getEditGroupModal.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                /** create the department members table in the add department modal */
                                $(document).ready(function () {
                                    var edit_group_members = $("#edit-group_members").DataTable({
                                        ajax: {
                                            url: "ajax/customers/getEditGroupMembersList.php",
                                            type: "POST",
                                            data: {
                                                group_id: group_id
                                            }
                                        },
                                        autoWidth: false,
                                        pageLength: <?php if (isset($USER_SETTINGS["page_length"])) { echo $USER_SETTINGS["page_length"]; } else { echo 10; } ?>,
                                        lengthMenu: [ [10, 25, 50, 100, 250, -1], [10, 25, 50, 100, 250, "All"] ],
                                        lengthChange: true,
                                        columns: [
                                            { data: "id", orderable: true, width: "25%" },
                                            { data: "name", orderable: true, width: "75%" },
                                            { data: "isMember", orderable: true, visible: false }
                                        ],
                                        order: [
                                            [ 2, "desc" ],
                                            [ 1, "asc" ],
                                            [ 0, "asc" ]
                                        ],
                                        select: {
                                            style: "multi"
                                        },
                                        dom: 'lfrtip',
                                        language: {
                                            search: '<div class="input-group mb-1"><div class="input-group-prepend"><span class="d-flex align-items-center h-100 mx-2" id="nav-search-icon"><i class="fa fa-search" aria-hidden="true"></i></span></div>_INPUT_</div>',
                                            lengthMenu: '<div class="mx-1 mb-1">Show _MENU_ entries</div>',
                                            info: '<div class="mx-1">Showing _START_ to _END_ of _TOTAL_ entries</div>'
                                        },
                                        paging: true,
                                        initComplete: function () {
                                            // pre-select rows of members that are already within the group
                                            let data = edit_group_members.rows().data();
                                            for (let r = 0; r < data.length; r++) { if (data[r]["isMember"] == 1) { edit_group_members.row(":eq("+r+")").select(); } }
                                        }
                                    });
                                });
                                
                                // display edit group modal
                                document.getElementById("edit-group-modal-div").innerHTML = this.responseText; 

                                // display the edit group modal
                                $("#editGroupModal").modal("show");
                            }
                        };
                        xmlhttp.send("group_id="+group_id);
                    }

                    /** function to delete the group */
                    function deleteGroup(id)
                    {
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/customers/deleteGroup.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // create the status modal
                                let status_title = "Delete Group Status";
                                let status_body = this.responseText;
                                createStatusModal("refresh", status_title, status_body);

                                // hide the current modal
                                $("#deleteGroupModal").modal("hide");
                            }
                        };
                        xmlhttp.send("group_id="+id);
                    }

                    /** function to edit the group */
                    function editGroup(group_id)
                    {
                        // create the string of data to send
                        let sendString = "";

                        // get group members
                        let members_table = $('#edit-group_members').DataTable();
                        let count = members_table.rows({ selected: true }).count();
                        let members = [];
                        for (let m = 0; m < count; m++) { members.push(members_table.rows({ selected: true }).data()[m]["id"]); }

                        // get group details from the modal
                        let name = encodeURIComponent(document.getElementById("edit-name").value);
                        let desc = encodeURIComponent(document.getElementById("edit-desc").value);
                        sendString += "id="+group_id+"&name="+name+"&desc="+desc+"&members="+JSON.stringify(members);
                        
                        // send the data to process the add customer request
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/customers/editGroup.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // create the status modal
                                let status_title = "Edit Group Status";
                                let status_body = this.responseText;
                                createStatusModal("refresh", status_title, status_body);

                                // hide the current modal
                                $("#editGroupModal").modal("hide");
                            }
                        };
                        xmlhttp.send(sendString);
                    }

                    /** function to create the view group members modal */
                    function getViewGroupModal(id)
                    {
                        // send the data to create the view group modal
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/customers/getViewGroupModal.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // initialize the view group members table                  
                                $(document).ready(function () {
                                    var edit_department_members = $("#view-group_members").DataTable({
                                        autoWidth: false,
                                        pageLength: -1,
                                        columns: [
                                            { orderable: true, width: "25%" },
                                            { orderable: true, width: "50%" },
                                            { orderable: true, width: "25%" }
                                        ],
                                        dom: 'frtip',
                                        language: {
                                            search: '<div class="input-group mb-1"><div class="input-group-prepend"><span class="d-flex align-items-center h-100 mx-2" id="nav-search-icon"><i class="fa fa-search" aria-hidden="true"></i></span></div>_INPUT_</div>',
                                            lengthMenu: '<div class="mx-1 mb-1">Show _MENU_ entries</div>',
                                            info: '<div class="mx-1">Showing _START_ to _END_ of _TOTAL_ entries</div>'
                                        },
                                        paging: false,
                                        order: [
                                            [ 1, "asc" ],
                                            [ 0, "asc" ]
                                        ]
                                    });
                                });

                                // display the delete department modal
                                document.getElementById("view-group-modal-div").innerHTML = this.responseText;     
                                $("#viewGroupModal").modal("show");
                            }
                        };
                        xmlhttp.send("group_id="+id);
                    }

                    /** function to update the total combined membership cost */
                    function updateMembershipCost()
                    {
                        // get the service ID
                        let service_id = document.getElementById("bill-service").value;
                        
                        // send data to get and then print total combined membership cost
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/customers/getTotalMembershipCost.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // update the cost in the modal
                                let total_cost = this.responseText;
                                document.getElementById("bill-total_cost").innerHTML = total_cost;
                            }
                        };
                        xmlhttp.send("service_id="+service_id);
                    }

                    /** function to invoice the group */
                    function invoiceGroup(group_id)
                    {
                        // get the invoice details
                        let service_id = document.getElementById("bill-service").value;
                        let date = document.getElementById("bill-date").value;
                        let desc = document.getElementById("bill-desc").value;

                        // create the string of data to send
                        let sendString = "group_id="+group_id+"&service_id="+service_id+"&date="+date+"&desc="+desc;
                        
                        // send data to get and then print total combined membership cost
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/customers/invoiceGroup.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // create the status modal
                                let status_title = "Invoice Group Status";
                                let status_body = this.responseText;
                                createStatusModal("refresh", status_title, status_body);

                                // hide the current modal
                                $("#invoiceGroupModal").modal("hide");
                            }
                        };
                        xmlhttp.send(sendString);
                    }
                </script>

                <div class="report">
                    <!-- Page Header -->
                    <div class="table-header p-0">
                        <div class="row d-flex justify-content-center align-items-center text-center py-2 px-3 m-0">
                            <!-- Period & Filters-->
                            <div class="col-6 col-sm-4 col-md-4 col-lg-3 col-xl-2 col-xxl-2 p-0">
                                <div class="row p-0">
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
                                <h1 class="m-0">Customer Groups</h1>
                            </div>

                            <!-- Page Management Dropdown -->
                            <div class="col-6 col-sm-4 col-md-4 col-lg-3 col-xl-2 col-xxl-2 d-flex justify-content-end p-0">
                                <?php if (isset($PERMISSIONS["ADD_CUSTOMER_GROUPS"])) { ?>
                                    <div class="dropdown float-end">
                                        <button class="btn btn-primary dropdown-toggle" type="button" id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">
                                            Manage Customer Groups
                                        </button>
                                        <ul class="dropdown-menu p-0" aria-labelledby="dropdownMenuButton1">
                                            <li><button class="dropdown-item quickNav-dropdown-item text-center w-100 px-3 py-2 rounded-0" type="button" data-bs-toggle="modal" data-bs-target="#addGroupModal">Add Group</button></li>
                                            <li><button class="dropdown-item quickNav-dropdown-item text-center w-100 px-3 py-2 rounded-0" type="button" data-bs-toggle="modal" data-bs-target="#uploadGroupsModal">Upload Groups</button></li>
                                        </ul>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>
                    </div>

                    <div class="row report-body m-0">
                        <table id="groups" class="report_table w-100">
                            <thead>
                                <tr>
                                    <th class="text-center py-1 px-2">ID</th>
                                    <th class="text-center py-1 px-2">Group Name</th>
                                    <th class="text-center py-1 px-2">Description</th>
                                    <th class="text-center py-1 px-2">Group Members</th>
                                    <th class="text-center py-1 px-2">Total Submembers</th>
                                    <th class="text-center py-1 px-2">Actions</th>
                                </tr>
                            </thead>
                        </table>
                        <?php createTableFooterV2("groups", "BAP_CustomerGroups_PageLength", $USER_SETTINGS["page_length"], true, true); ?>
                    </div>
                </div>

                <!--
                    ### MODALS ###
                -->
                <!-- Add Group Modal -->
                <div class="modal fade" tabindex="-1" role="dialog" id="addGroupModal" data-bs-backdrop="static" aria-labelledby="addGroupModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg" role="document">
                        <div class="modal-content">
                            <div class="modal-header primary-modal-header">
                                <h5 class="modal-title primary-modal-title" id="addGroupModalLabel">Add Group</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>

                            <div class="modal-body">
                                <!-- Group Details -->
                                <fieldset class="form-group border p-3 mb-3">
                                    <legend class="w-auto px-2 m-0 float-none fieldset-legend">Group Details</legend>

                                    <div class="row align-items-center my-2">
                                        <div class="col-3 text-end"><label for="add-name"><span class="required-field">*</span> Group Name:</label></div>
                                        <div class="col-9"><input type="text" class="form-control w-100" id="add-name" name="add-name" required></div>
                                    </div>

                                    <div class="row align-items-center my-2">
                                        <div class="col-3 text-end"><label for="add-desc">Description:</label></div>
                                        <div class="col-9"><input type="text" class="form-control w-100" id="add-desc" name="add-desc" required></div>
                                    </div>
                                </fieldset>

                                <!-- Group Members -->
                                <fieldset class="form-group border p-3 mb-3">
                                    <legend class="w-auto px-2 m-0 float-none fieldset-legend">Group Members</legend>

                                    <table id="add-group_members" class="report_table w-100">
                                        <thead>
                                            <tr>
                                                <th class="text-center">Customer ID</th>
                                                <th class="text-center">Customer Name</th>
                                            </tr>
                                        </thead>

                                        <tbody>
                                            <?php
                                                $getCustomers = mysqli_query($conn, "SELECT id, name FROM customers WHERE active=1 ORDER BY name ASC");
                                                if (mysqli_num_rows($getCustomers) > 0) // customers found; build table
                                                {
                                                    while ($customer = mysqli_fetch_array($getCustomers))
                                                    {
                                                        echo "<tr>
                                                            <td class='text-center'>".$customer["id"]."</td>
                                                            <td class='text-center'>".$customer["name"]."</td>
                                                        </tr>";
                                                    }
                                                }
                                            ?>
                                        </tbody>
                                    </table>
                                </fieldset>
                            </div>

                            <div class="modal-footer">
                                <button type="button" class="btn btn-primary" onclick="addGroup();"><i class="fa-solid fa-floppy-disk"></i> Save New Group</button>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> Close</button>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- End Add Group Modal -->

                <!-- Upload Groups Modal -->
                <div class="modal fade" tabindex="-1" role="dialog" id="uploadGroupsModal" data-bs-backdrop="static" aria-labelledby="uploadGroupsModalLabel" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header primary-modal-header">
                                <h5 class="modal-title primary-modal-title" id="uploadGroupsModalLabel">Upload Groups</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>

                            <form action="processUploadGroups.php" method="POST" enctype="multipart/form-data">
                                <div class="modal-body">
                                    <p><label for="fileToUpload">Select a CSV file following the <a class="template-link" href="https://docs.google.com/spreadsheets/d/1stB9O9__WNb4a6FZpWlwjcWAXZtxeafkucSVBahevmU/copy" target="_blank">correct upload template</a> to upload...</label></p>
                                    <input type="file" name="fileToUpload" id="fileToUpload">
                                </div>

                                <div class="modal-footer">
                                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-cloud-arrow-up"></i> Upload Groups</button>
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> Close</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <!-- End Upload Groups Modal -->

                <!-- Edit Group Modal -->
                <div id="edit-group-modal-div"></div>
                <!-- End Edit Group Modal -->

                <!-- Delete Group Modal -->
                <div id="delete-group-modal-div"></div>
                <!-- End Delete Group Modal -->

                <!-- Invoice Group Modal -->
                <div id="invoice-group-modal-div"></div>
                <!-- End Invoice Group Modal -->

                <!-- View Group Modal -->
                <div id="view-group-modal-div"></div>
                <!-- End View Group Modal -->

                <script>
                    var groups = $("#groups").DataTable({
                        ajax: {
                            url: "ajax/customers/getGroups.php",
                            type: "POST"
                        },
                        autoWidth: false,
                        pageLength: <?php if (isset($USER_SETTINGS["page_length"])) { echo $USER_SETTINGS["page_length"]; } else { echo 10; } ?>,
                        lengthMenu: [ [10, 25, 50, 100, 250, -1], [10, 25, 50, 100, 250, "All"] ],
                        columns: [
                            { data: "id", orderable: true, visible: false },
                            { data: "name", orderable: true, width: "25%", className: "text-center" },
                            { data: "desc", orderable: true, width: "30%", className: "text-center" },
                            { data: "members", orderable: true, width: "17.5%", className: "text-center" },
                            { data: "submembers", orderable: true, width: "17.5%", className: "text-center" },
                            { data: "actions", orderable: false, width: "10%" }
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
                        rowCallback: function (row, data, index)
                        {
                            updatePageSelection("groups");
                        },
                    });

                    /** create the department members table in the add department modal */
                    var group_members = $("#add-group_members").DataTable({
                        autoWidth: false,
                        pageLength: <?php if (isset($USER_SETTINGS["page_length"])) { echo $USER_SETTINGS["page_length"]; } else { echo 10; } ?>,
                        lengthMenu: [ [10, 25, 50, 100, 250, -1], [10, 25, 50, 100, 250, "All"] ],
                        lengthChange: true,
                        columns: [
                            { data: "id", orderable: true, width: "25%" },
                            { data: "name", orderable: true, width: "75%" },
                        ],
                        order: [
                            [1, "asc"],
                            [0, "asc"]
                        ],
                        select: {
                            style: "multi"
                        },
                        dom: 'lfrtip',
                        language: {
                            search: '<div class="input-group mb-1"><div class="input-group-prepend"><span class="d-flex align-items-center h-100 mx-2" id="nav-search-icon"><i class="fa fa-search" aria-hidden="true"></i></span></div>_INPUT_</div>',
                            lengthMenu: '<div class="mx-1 mb-1">Show _MENU_ entries</div>',
                            info: '<div class="mx-1">Showing _START_ to _END_ of _TOTAL_ entries</div>'
                        },
                        paging: true
                    });

                    // search table by custom search filter
                    $('#search-all').keyup(function() {
                        groups.search($(this).val()).draw();
                    });
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