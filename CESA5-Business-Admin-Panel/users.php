<?php 
    include_once("header.php");

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1) 
    { 
        ///////////////////////////////////////////////////////////////////////////////////////////
        //
        //  Admin View
        //
        ///////////////////////////////////////////////////////////////////////////////////////////
        if (isset($_SESSION["role"]) && $_SESSION["role"] == 1)
        {
            // connect to the database
            $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

            ?>
                <script>
                    /** function to add a user */
                    function addUser()
                    {
                        // get the form values
                        let email = document.getElementById("add-email").value;
                        let fname = document.getElementById("add-fname").value;
                        let lname = document.getElementById("add-lname").value;
                        let role_id = document.getElementById("add-role_id").value;
                        let status = document.getElementById("add-status").value;

                        // send the data to process the add department request
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/users/addUser.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // create the status modal
                                let status_title = "Add User Status";
                                let status_body = this.responseText;
                                createStatusModal("refresh", status_title, status_body);

                                // hide the current modal
                                $("#addUserModal").modal("hide");
                            }
                        };
                        xmlhttp.send("email="+email+"&fname="+fname+"&lname="+lname+"&role_id="+role_id+"&status="+status);
                    }

                    /** function to get the edit user modal */
                    function getEditUserModal(user_id)
                    {
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/users/getEditUserModal.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                document.getElementById("edit-user-modal-div").innerHTML = this.responseText;
                                $("#editUserModal").modal("show");
                            }
                        };
                        xmlhttp.send("user_id="+user_id);
                    }

                    /** function to edit an existing user */
                    function editUser(user_id)
                    {
                        // get the form values
                        let email = document.getElementById("edit-email").value;
                        let fname = document.getElementById("edit-fname").value;
                        let lname = document.getElementById("edit-lname").value;
                        let role_id = document.getElementById("edit-role_id").value;
                        let status = document.getElementById("edit-status").value;

                        // send the data to edit the user
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/users/editUser.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // create the status modal
                                let status_title = "Edit User Status";
                                let status_body = this.responseText;
                                createStatusModal("refresh", status_title, status_body);

                                // hide the current modal
                                $("#editUserModal").modal("hide");
                            }
                        };
                        xmlhttp.send("user_id="+user_id+"&email="+email+"&fname="+fname+"&lname="+lname+"&role_id="+role_id+"&status="+status);
                    }

                    /** function to get the edit user modal */
                    function getDeleteUserModal(user_id)
                    {
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/users/getDeleteUserModal.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                document.getElementById("delete-user-modal-div").innerHTML = this.responseText;
                                $("#deleteUserModal").modal("show");
                            }
                        };
                        xmlhttp.send("user_id="+user_id);
                    }

                    /** function to delete a user */
                    function deleteUser(user_id)
                    {
                        // send the data to edit the user
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/users/deleteUser.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // create the status modal
                                let status_title = "Delete User Status";
                                let status_body = this.responseText;
                                createStatusModal("refresh", status_title, status_body);

                                // hide the current modal
                                $("#deleteUserModal").modal("hide");
                            }
                        };
                        xmlhttp.send("user_id="+user_id);
                    }

                    /** function to get the masquerade modal */
                    function getMasqueradeModal(user_id)
                    {
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/users/getMasqueradeModal.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // display the edit employee modal
                                document.getElementById("masquerade-modal-div").innerHTML = this.responseText;
                                $("#masqueradeModal").modal("show");
                            }
                        };
                        xmlhttp.send("user_id="+user_id);
                    }

                    /** function to masquerade as a user */
                    function masquerade(user_id)
                    {
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/users/masquerade.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                window.location.href = "dashboard.php";
                            }
                        };
                        xmlhttp.send("user_id="+user_id);
                    }

                    /** function to update the status element */
                    function updateStatus(id)
                    {
                        // get current status of the element
                        let element = document.getElementById(id);
                        let status = element.value;

                        if (status == 0) // currently set to inactive
                        {
                            // update status to active
                            element.value = 1;
                            element.innerHTML = "Active";
                            element.classList.remove("btn-danger");
                            element.classList.add("btn-success");
                        }
                        else // currently set to active, or other?
                        {
                            // update status to inactive
                            element.value = 0;
                            element.innerHTML = "Inactive";
                            element.classList.remove("btn-success");
                            element.classList.add("btn-danger");
                        }
                    }
                </script>

                <div class="report">
                    <!-- Page Header -->
                    <div class="table-header p-0">
                        <div class="row d-flex justify-content-center align-items-center text-center py-2 px-3">
                            <!-- Filters-->
                            <div class="col-6 col-sm-4 col-md-4 col-lg-3 col-xl-2 col-xxl-2 p-0">
                                <div class="row px-3">
                                    <!-- Filters -->
                                    <div class="col-3 ps-2 py-0">
                                        <div class="dropdown float-start">
                                            <button class="btn btn-primary" type="button" id="filtersMenu" data-bs-toggle="dropdown" aria-expanded="false">
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

                                                <!-- Filter By Status -->
                                                <div class="row d-flex justify-content-between align-items-center mx-0 mt-0 mb-2">
                                                    <div class="col-4 ps-0 pe-1">
                                                        <label for="search-status">Status:</label>
                                                    </div>

                                                    <div class="col-8 ps-1 pe-0">
                                                        <select class="form-select" id="search-status" name="search-status">
                                                            <option value="-1">Show All</option>
                                                            <option value="1" style="background-color: #006900; color: #ffffff" selected>Active</option>
                                                            <option value="0" style="background-color: #e40000; color: #ffffff">Inactive</option>
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
                                <h1 class="m-0">Users</h1>
                            </div>

                            <!-- Page Management Dropdown -->
                            <div class="col-6 col-sm-4 col-md-4 col-lg-3 col-xl-2 col-xxl-2 p-0">
                                <div class="dropdown float-end">
                                    <button class="btn btn-primary dropdown-toggle" type="button" id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">
                                        Manage Users
                                    </button>
                                    <ul class="dropdown-menu p-0" aria-labelledby="dropdownMenuButton1">
                                        <li><button class="dropdown-item quickNav-dropdown-item text-center w-100 px-3 py-2 rounded-0" type="button" data-bs-toggle="modal" data-bs-target="#addUserModal">Add User</button></li>
                                        <li><button class="dropdown-item quickNav-dropdown-item text-center w-100 px-3 py-2 rounded-0" type="button" data-bs-toggle="modal" data-bs-target="#uploadDistrictAdminsModal">Upload District Admins</button></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row report-body m-0">
                        <table id="users" class="report_table w-100">
                            <thead>
                                <tr>
                                    <th></th>
                                    <th>ID</th>
                                    <th>First Name</th>
                                    <th>Last Name</th>
                                    <th>Email</th>
                                    <th>User Role</th>
                                    <th>Is Employee?</th>
                                    <th>Employee Title</th>
                                    <th>Last Login</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                        </table>
                        <?php createTableFooterV2("users", "BAP_Users_PageLength", $USER_SETTINGS["page_length"], true, true); ?>
                    </div>
                </div>

                <!--
                    ### MODALS ###
                -->
                <!-- Add User Modal -->
                <div class="modal fade" tabindex="-1" role="dialog" id="addUserModal" data-bs-backdrop="static" aria-labelledby="addUserModalLabel" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header primary-modal-header">
                                <h5 class="modal-title primary-modal-title" id="addUserModalLabel">Add User</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>

                            <div class="modal-body">
                                <div class="form-row d-flex justify-content-center align-items-center my-3">
                                    <div class="form-group col-11">
                                        <label for="add-email"><span class="required-field">*</span> Email Address:</label>
                                        <input type="text" class="form-control w-100" id="add-email" name="add-email" autocomplete="off" required>
                                    </div>
                                </div>
                                
                                <div class="form-row d-flex justify-content-center align-items-center my-3">
                                    <!-- First Name -->
                                    <div class="form-group col-5">
                                        <label for="add-fname"><span class="required-field">*</span> First Name:</label>
                                        <input type="text" class="form-control w-100" id="add-fname" name="add-fname" autocomplete="off" required>
                                    </div>

                                    <!-- Divider -->
                                    <div class="form-group col-1 p-0"></div>

                                    <!-- Last Name -->
                                    <div class="form-group col-5">
                                        <label for="add-lname"><span class="required-field">*</span> Last Name:</label>
                                        <input type="text" class="form-control w-100" id="add-lname" name="add-lname" autocomplete="off" required>
                                    </div>
                                </div>

                                <div class="form-row d-flex justify-content-center align-items-center my-3">
                                    <div class="form-group col-11">
                                        <label for="add-role_id"><span class="required-field">*</span> Account Role:</label>
                                        <select class="form-select w-100" id="add-role_id" name="add-role_id" autocomplete="off" required>
                                            <option></option>
                                            <?php
                                                // create the role selection dropdown options
                                                $getRoles = mysqli_query($conn, "SELECT * FROM roles ORDER BY default_generated DESC, name ASC");
                                                if (mysqli_num_rows($getRoles) > 0) // roles found
                                                {
                                                    while ($role_details = mysqli_fetch_array($getRoles))
                                                    {
                                                        // store role details locally
                                                        $role_id = $role_details["id"];
                                                        $role_name = $role_details["name"];
                                                        $default_generated = $role_details["default_generated"];

                                                        // create the option (bold option if it is a default role)
                                                        if ($default_generated == 1) { echo "<option value='".$role_id."' class='fw-bold'>".$role_name."</option>"; }
                                                        else { echo "<option value='".$role_id."'>".$role_name."</option>"; }
                                                    }
                                                }
                                            ?>  
                                        </select>
                                    </div>
                                </div>

                                <div class="form-row d-flex justify-content-center align-items-center my-3">
                                    <!-- Status -->
                                    <div class="form-group col-11">
                                        <label for="add-status"><span class="required-field">*</span> Status:</label>
                                        <button class="btn btn-success w-100" id="add-status" name="add-status" value=1 onclick="updateStatus('add-status');">Active</button>
                                    </div>
                                </div>

                                <!-- Required Field Indicator -->
                                <div class="row justify-content-center">
                                    <div class="col-11 text-center fst-italic">
                                        <span class="required-field">*</span> indicates a required field
                                    </div>
                                </div>
                            </div>

                            <div class="modal-footer">
                                <button type="button" class="btn btn-primary" onclick="addUser();"><i class="fa-solid fa-floppy-disk"></i> Add User</button>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> Close</button>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- End Add User Modal -->

                <!-- Edit User Modal -->
                <div id="edit-user-modal-div"></div>
                <!-- End Edit User Modal -->

                <!-- Delete User Modal -->
                <div id="delete-user-modal-div"></div>
                <!-- End Delete User Modal -->

                <!-- Masquerade Modal -->
                <div id="masquerade-modal-div"></div>
                <!-- End Masquerade Modal -->

                <!-- Upload District Admins Modal -->
                <div class="modal fade" tabindex="-1" role="dialog" id="uploadDistrictAdminsModal" data-bs-backdrop="static" aria-labelledby="uploadDistrictAdminsModalLabel" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header primary-modal-header">
                                <h5 class="modal-title primary-modal-title" id="uploadDistrictAdminsModalLabel">Upload District Admins</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>

                            <form action="processUploadCustomerUsers.php" method="POST" enctype="multipart/form-data">
                                <div class="modal-body">
                                    <p><label for="fileToUpload">Select a CSV file following the <a class="template-link" href="https://docs.google.com/spreadsheets/d/1wnwv8QqX0cExA4zl5zS1EG7-QIYuFQm8PhzbT-oNYpA/copy" target="_blank">correct upload template</a> to upload...</label></p>
                                    <input type="file" name="fileToUpload" id="fileToUpload">
                                </div>

                                <div class="modal-footer">
                                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-cloud-arrow-up"></i> Upload District Admins</button>
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> Close</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <!-- End Upload District Admins Modal -->
                <!--
                    ### END MODALS ###
                -->

                <script>
                    // load in prior search parameters
                    if (sessionStorage["BAP_Users_Search_All"] != "" && sessionStorage["BAP_Users_Search_All"] != null && sessionStorage["BAP_Users_Search_All"] != undefined) { $('#search-all').val(sessionStorage["BAP_Users_Search_All"]); }
                    if (sessionStorage["BAP_Users_Search_Status"] != "" && sessionStorage["BAP_Users_Search_Status"] != null && sessionStorage["BAP_Users_Search_Status"] != undefined) { $('#search-status').val(sessionStorage["BAP_Users_Search_Status"]); }
                    else { $('#search-status').val(1); }

                    // initialize table
                    var users = $("#users").DataTable({
                        ajax: {
                            url: "ajax/users/getUsers.php",
                            type: "POST"
                        },
                        autoWidth: false,
                        pageLength: <?php if (isset($USER_SETTINGS["page_length"])) { echo $USER_SETTINGS["page_length"]; } else { echo 10; } ?>,
                        lengthMenu: [ [10, 25, 50, 100, 250, -1], [10, 25, 50, 100, 250, "All"] ],
                        columns: [
                            { data: "status", orderable: false, width: "1%" },
                            { data: "id", orderable: true, width: "4%", className: "text-center" },
                            { data: "fname", orderable: true, width: "12.5%", className: "text-center" },
                            { data: "lname", orderable: true, width: "12.5%", className: "text-center" },
                            { data: "email", orderable: true, width: "15%", className: "text-center" },
                            { data: "role", orderable: true, width: "15%", className: "text-center" },
                            { data: "is_employee", orderable: true, width: "7.75%", className: "text-center" },
                            { data: "title", orderable: true, width: "15%", className: "text-center" },
                            { data: "last_login", orderable: true, width: "10%", className: "text-center" },
                            { data: "actions", orderable: false, width: "7.25%", className: "text-center" },
                            { data: "export_status", orderable: false, visible: false },
                        ],
                        dom: 'rt',
                        order: [
                            [ 1, "asc" ],
                            [ 2, "asc" ],
                            [ 3, "asc" ]
                        ],
                        language: {
                            search: '<div class="input-group mb-1"><div class="input-group-prepend"><span class="d-flex align-items-center h-100 mx-2" id="nav-search-icon"><i class="fa fa-search" aria-hidden="true"></i></span></div>_INPUT_</div>',
                            lengthMenu: '<div class="mx-1 mb-1">Show _MENU_ entries</div>',
                            info: '<div class="mx-1">Showing _START_ to _END_ of _TOTAL_ entries</div>'
                        },
                        paging: true,
                        rowCallback: function (row, data, index)
                        {
                            // initialize page selection
                            updatePageSelection("users");

                            // check status
                            $("td:eq(0)", row).html(""); // hide status integer
                            if (data["status"] == 1) { $("td:eq(0)", row).addClass("period-active text-center m-0 p-0"); } 
                            else { $("td:eq(0)", row).addClass("period-inactive text-center m-0 p-0"); }
                        },
                    });

                    // search table by custom search filter
                    $('#search-all').keyup(function() {
                        users.search($(this).val()).draw();
                        sessionStorage["BAP_Users_Search_All"] = $(this).val();
                    });

                     // search table by caseload status
                     $('#search-status').change(function() {
                        if ($(this).val() != -1) { users.columns(10).search("^" + $(this).val() + "$", true, false, true).draw(); }
                        else { users.columns(10).search("").draw(); }
                        sessionStorage["BAP_Users_Search_Status"] = $(this).val();
                    });

                    // function to clear search filters
                    $('#clearFilters').click(function() {
                        sessionStorage["BAP_Users_Search_All"] = "";
                        sessionStorage["BAP_Users_Search_Status"] = -1;
                        $('#search-all').val("");
                        $('#search-status').val(-1);
                        users.search("").columns().search("").draw();
                    });

                    // redraw table with current search fields
                    if ($('#search-all').val() != "") { users.search($('#search-all').val()).draw(); }
                    if ($('#search-status').val() != -1) { users.columns(10).search("^" + $('#search-status').val() + "$", true, false, true).draw(); }
                    else { users.columns(10).search("").draw(); }
                </script>
            <?php 

            // disconnect from the database
            mysqli_close($conn);
        }
        ///////////////////////////////////////////////////////////////////////////////////////////
        //
        //  District View
        //
        ///////////////////////////////////////////////////////////////////////////////////////////
        else if (isset($_SESSION["district"]) && $_SESSION["district"]["status"] == 1 && ($_SESSION["district"]["role"] == "Admin" || $_SESSION["district"]["role"] == "Editor"))
        {
            // connect to the database
            $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

            ?>
                <div class="container-fluid">
                    <div class="row d-flex align-items-center">
                        <div class="col-12 col-md-6">
                            <h1 class="mb-0">Users</h1>
                        </div>

                        <div class="col-12 col-md-6 d-flex justify-content-end">
                            <!-- Page Management Dropdown -->
                            <div class="dropdown mx-1">
                                <button class="btn btn-primary dropdown-toggle" type="button" id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">
                                    Manage Users
                                </button>
                                <ul class="dropdown-menu p-0" aria-labelledby="dropdownMenuButton1">
                                    <li><button class="dropdown-item quickNav-dropdown-item text-center w-100 px-3 py-2 rounded-0" type="button" data-bs-toggle="modal" data-bs-target="#addUserModal">Add User</button></li>
                                </ul>
                            </div>

                            <!-- Filters -->
                            <div class="dropdown mx-1">
                                <button class="btn btn-primary" type="button" id="filtersMenu" data-bs-toggle="dropdown" aria-expanded="false">
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

                    <div class="row report-body m-0">
                        <table id="users" class="table table-striped shadow">
                            <thead class="table-header">
                                <tr>
                                    <th>Last Name</th>
                                    <th>First Name</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Last Login</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>

                            <?php createTableFooterV3("users", 7, "BAP_Users_PageLength", $USER_SETTINGS["page_length"], true, true, false); ?>
                        </table>
                    </div>
                </div>

                <!--
                    ### MODALS ###
                -->
                <!-- Add User Modal -->
                <div class="modal fade" tabindex="-1" role="dialog" id="addUserModal" data-bs-backdrop="static" aria-labelledby="addUserModalLabel" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header primary-modal-header">
                                <h5 class="modal-title primary-modal-title" id="addUserModalLabel">Add User</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>

                            <div class="modal-body">
                                <div class="form-row d-flex justify-content-center align-items-center my-3">
                                    <div class="form-group col-11">
                                        <label for="add-email"><span class="required-field">*</span> Email Address:</label>
                                        <input type="text" class="form-control w-100" id="add-email" name="add-email" autocomplete="off" required>
                                    </div>
                                </div>
                                
                                <div class="form-row d-flex justify-content-center align-items-center my-3">
                                    <!-- First Name -->
                                    <div class="form-group col-5">
                                        <label for="add-fname"><span class="required-field">*</span> First Name:</label>
                                        <input type="text" class="form-control w-100" id="add-fname" name="add-fname" autocomplete="off" required>
                                    </div>

                                    <!-- Divider -->
                                    <div class="form-group col-1 p-0"></div>

                                    <!-- Last Name -->
                                    <div class="form-group col-5">
                                        <label for="add-lname"><span class="required-field">*</span> Last Name:</label>
                                        <input type="text" class="form-control w-100" id="add-lname" name="add-lname" autocomplete="off" required>
                                    </div>
                                </div>

                                <div class="form-row d-flex justify-content-center align-items-center my-3">
                                    <div class="form-group col-11">
                                        <label for="add-role_id"><span class="required-field">*</span> Account Role:</label>
                                        <select class="form-select w-100" id="add-role_id" name="add-role_id" autocomplete="off" required>
                                            <option></option>
                                            <?php
                                                // create the role selection dropdown options
                                                $getRoles = mysqli_query($conn, "SELECT * FROM roles WHERE name='District Administrator' OR name='District Editor' OR name='District Viewer' ORDER BY name ASC");
                                                if (mysqli_num_rows($getRoles) > 0) // roles found
                                                {
                                                    while ($role_details = mysqli_fetch_array($getRoles))
                                                    {
                                                        // store role details locally
                                                        $role_id = $role_details["id"];
                                                        $role_name = $role_details["name"];

                                                        // create the option (bold option if it is a default role)
                                                        echo "<option value='".$role_id."'>".$role_name."</option>";
                                                    }
                                                }
                                            ?>  
                                        </select>
                                    </div>
                                </div>

                                <div class="form-row d-flex justify-content-center align-items-center my-3">
                                    <!-- Status -->
                                    <div class="form-group col-11">
                                        <label for="add-status"><span class="required-field">*</span> Status:</label>
                                        <button class="btn btn-success w-100" id="add-status" name="add-status" value=1 onclick="updateStatus('add-status');">Active</button>
                                    </div>
                                </div>

                                <!-- Required Field Indicator -->
                                <div class="row justify-content-center">
                                    <div class="col-11 text-center fst-italic">
                                        <span class="required-field">*</span> indicates a required field
                                    </div>
                                </div>
                            </div>

                            <div class="modal-footer">
                                <button type="button" class="btn btn-primary" onclick="addUser();"><i class="fa-solid fa-floppy-disk"></i> Add User</button>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> Close</button>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- End Add User Modal -->

                <!-- Edit User Modal -->
                <div id="edit-user-modal-div"></div>
                <!-- End Edit User Modal -->

                <!-- Delete User Modal -->
                <div id="delete-user-modal-div"></div>
                <!-- End Delete User Modal -->

                <script>
                    // load in prior search parameters
                    if (sessionStorage["BAP_Users_Search_All"] != "" && sessionStorage["BAP_Users_Search_All"] != null && sessionStorage["BAP_Users_Search_All"] != undefined) { $('#search-all').val(sessionStorage["BAP_Users_Search_All"]); }
                    
                    // initialize table
                    var users = $("#users").DataTable({
                        ajax: {
                            url: "ajax/users/getUsers.php",
                            type: "POST"
                        },
                        autoWidth: false,
                        pageLength: <?php if (isset($USER_SETTINGS["page_length"])) { echo $USER_SETTINGS["page_length"]; } else { echo 10; } ?>,
                        lengthMenu: [ [10, 25, 50, 100, 250, -1], [10, 25, 50, 100, 250, "All"] ],
                        columns: [
                            { data: "lname", orderable: true, width: "12.5%", className: "text-center" },
                            { data: "fname", orderable: true, width: "12.5%", className: "text-center" },
                            { data: "email", orderable: true, width: "15%", className: "text-center" },
                            { data: "role", orderable: true, width: "15%", className: "text-center" },
                            { data: "status", orderable: true, width: "7.75%", className: "text-center" },
                            { data: "last_login", orderable: true, width: "10%", className: "text-center" },
                            { data: "actions", orderable: false, width: "7.25%", className: "text-center" },
                        ],
                        dom: 'rt',
                        order: [
                            [ 0, "asc" ],
                            [ 1, "asc" ],
                        ],
                        language: {
                            search: '<div class="input-group mb-1"><div class="input-group-prepend"><span class="d-flex align-items-center h-100 mx-2" id="nav-search-icon"><i class="fa fa-search" aria-hidden="true"></i></span></div>_INPUT_</div>',
                            lengthMenu: '<div class="mx-1 mb-1">Show _MENU_ entries</div>',
                            info: '<div class="mx-1">Showing _START_ to _END_ of _TOTAL_ entries</div>'
                        },
                        paging: true,
                        rowCallback: function (row, data, index)
                        {
                            // initialize page selection
                            updatePageSelection("users");
                        },
                    });

                    // search table by custom search filter
                    $('#search-all').keyup(function() {
                        users.search($(this).val()).draw();
                        sessionStorage["BAP_Users_Search_All"] = $(this).val();
                    });

                    // function to clear search filters
                    $('#clearFilters').click(function() {
                        sessionStorage["BAP_Users_Search_All"] = "";
                        $('#search-all').val("");
                        users.search("").columns().search("").draw();
                    });

                    // redraw table with current search fields
                    if ($('#search-all').val() != "") { users.search($('#search-all').val()).draw(); }

                    /** function to update the status element */
                    function updateStatus(id)
                    {
                        // get current status of the element
                        let element = document.getElementById(id);
                        let status = element.value;

                        if (status == 0) // currently set to inactive
                        {
                            // update status to active
                            element.value = 1;
                            element.innerHTML = "Active";
                            element.classList.remove("btn-danger");
                            element.classList.add("btn-success");
                        }
                        else // currently set to active, or other?
                        {
                            // update status to inactive
                            element.value = 0;
                            element.innerHTML = "Inactive";
                            element.classList.remove("btn-success");
                            element.classList.add("btn-danger");
                        }
                    }

                    /** function to add a user */
                    function addUser()
                    {
                        // get the form values
                        let email = document.getElementById("add-email").value;
                        let fname = document.getElementById("add-fname").value;
                        let lname = document.getElementById("add-lname").value;
                        let role_id = document.getElementById("add-role_id").value;
                        let status = document.getElementById("add-status").value;

                        // send the data to process the add department request
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/users/addUser.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // create the status modal
                                let status_title = "Add User Status";
                                let status_body = this.responseText;
                                createStatusModal("refresh", status_title, status_body);

                                // hide the current modal
                                $("#addUserModal").modal("hide");
                            }
                        };
                        xmlhttp.send("email="+email+"&fname="+fname+"&lname="+lname+"&role_id="+role_id+"&status="+status);
                    }

                    /** function to get the edit user modal */
                    function getEditUserModal(user_id)
                    {
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/users/getEditUserModal.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                document.getElementById("edit-user-modal-div").innerHTML = this.responseText;
                                $("#editUserModal").modal("show");
                            }
                        };
                        xmlhttp.send("user_id="+user_id);
                    }

                    /** function to edit an existing user */
                    function editUser(user_id)
                    {
                        // get the form values
                        let email = document.getElementById("edit-email").value;
                        let fname = document.getElementById("edit-fname").value;
                        let lname = document.getElementById("edit-lname").value;
                        let role_id = document.getElementById("edit-role_id").value;
                        let status = document.getElementById("edit-status").value;

                        // send the data to edit the user
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/users/editUser.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // create the status modal
                                let status_title = "Edit User Status";
                                let status_body = this.responseText;
                                createStatusModal("refresh", status_title, status_body);

                                // hide the current modal
                                $("#editUserModal").modal("hide");
                            }
                        };
                        xmlhttp.send("user_id="+user_id+"&email="+email+"&fname="+fname+"&lname="+lname+"&role_id="+role_id+"&status="+status);
                    }

                    /** function to get the edit user modal */
                    function getDeleteUserModal(user_id)
                    {
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/users/getDeleteUserModal.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                document.getElementById("delete-user-modal-div").innerHTML = this.responseText;
                                $("#deleteUserModal").modal("show");
                            }
                        };
                        xmlhttp.send("user_id="+user_id);
                    }

                    /** function to delete a user */
                    function deleteUser(user_id)
                    {
                        // send the data to edit the user
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/users/deleteUser.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // create the status modal
                                let status_title = "Delete User Status";
                                let status_body = this.responseText;
                                createStatusModal("refresh", status_title, status_body);

                                // hide the current modal
                                $("#deleteUserModal").modal("hide");
                            }
                        };
                        xmlhttp.send("user_id="+user_id);
                    }
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