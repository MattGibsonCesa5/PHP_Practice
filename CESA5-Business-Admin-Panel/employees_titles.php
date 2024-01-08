<?php 
    include_once("header.php");

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1) 
    { 
        if (isset($PERMISSIONS["VIEW_EMPLOYEES_ALL"]) && isset($PERMISSIONS["ADD_EMPLOYEES"]))
        {
            // connect to the database
            $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

            // get the active period label
            $active_period_label = getActivePeriodLabel($conn);

            ?>
                <script>
                    /** function to add a new title */
                    function addTitle()
                    {
                        // get form parameters
                        let title = document.getElementById("add-title").value;

                        // send request
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/employees/addTitle.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // create the status modal
                                let status_title = "Add Title Status";
                                let status_body = this.responseText;
                                createStatusModal("refresh", status_title, status_body);

                                // hide the current modal
                                $("#addTitleModal").modal("hide");
                            }
                        };
                        xmlhttp.send("title="+title);
                    }

                    /** function to get the edit title modal */
                    function getEditTitleModal(title_id)
                    {
                        // send the data to create the edit title modal
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/employees/getEditTitleModal.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // display the edit title modal
                                document.getElementById("edit-title-modal-div").innerHTML = this.responseText;
                                $("#editTitleModal").modal("show");
                            }
                        };
                        xmlhttp.send("title_id="+title_id);
                    }

                    /** function to edit an exisiting title */
                    function editTitle(title_id)
                    {
                        // get form parameters
                        let title = document.getElementById("edit-title").value;

                        // send request
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/employees/editTitle.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // create the status modal
                                let status_title = "Edit Title Status";
                                let status_body = this.responseText;
                                createStatusModal("refresh", status_title, status_body);

                                // hide the current modal
                                $("#editTitleModal").modal("hide");
                            }
                        };
                        xmlhttp.send("title_id="+title_id+"&title="+title);
                    }

                    /** function to get the delete title modal */
                    function getDeleteTitleModal(title_id)
                    {
                        // send the data to create the delete title modal
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/employees/getDeleteTitleModal.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // display the delete title modal
                                document.getElementById("delete-title-modal-div").innerHTML = this.responseText;
                                $("#deleteTitleModal").modal("show");
                            }
                        };
                        xmlhttp.send("title_id="+title_id);
                    }

                    /** function to edit an exisiting title */
                    function deleteTitle(title_id)
                    {
                        // send request
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/employees/deleteTitle.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // create the status modal
                                let status_title = "Delete Title Status";
                                let status_body = this.responseText;
                                createStatusModal("refresh", status_title, status_body);

                                // hide the current modal
                                $("#deleteTitleModal").modal("hide");
                            }
                        };
                        xmlhttp.send("title_id="+title_id);
                    }
                </script>

                <div class="report">
                    <div class="row report-body m-0">
                        <!-- Page Header -->
                        <div class="table-header p-0">
                            <div class="row d-flex justify-content-center align-items-center text-center py-2 px-3">
                                <!-- Period & Filters-->
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
                                    <h2 class="m-0">Employee Position Titles</h2>
                                </div>

                                <!-- Page Management Dropdown -->
                                <div class="col-6 col-sm-4 col-md-4 col-lg-3 col-xl-2 col-xxl-2 p-0">
                                    <div class="dropdown float-end">
                                        <button class="btn btn-primary dropdown-toggle" type="button" id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">
                                            Manage Titles
                                        </button>
                                        <ul class="dropdown-menu p-0" aria-labelledby="dropdownMenuButton1">
                                            <li><button class="dropdown-item quickNav-dropdown-item text-center w-100 px-3 py-2 rounded-0" type="button" data-bs-toggle="modal" data-bs-target="#addTitleModal">Add Title</button></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <table id="titles" class="report_table w-100">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th># of Employees</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                        </table>
                        <?php createTableFooterV2("titles", "BAP_EmployeeTitles_PageLength", $USER_SETTINGS["page_length"], true, true); ?>
                    </div>
                </div>

                <!-- Add Title Modal -->
                <div class="modal fade" tabindex="-1" role="dialog" id="addTitleModal" data-bs-backdrop="static" aria-labelledby="addTitleModalLabel" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header primary-modal-header">
                                <h5 class="modal-title primary-modal-title" id="addTitleModalLabel">Add Title</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>

                            <div class="modal-body">
                                <div class="form-row d-flex justify-content-center align-items-center my-3">
                                    <!-- Title -->
                                    <div class="form-group col-11">
                                        <label for="add-title"><span class="required-field">*</span> Title:</label>
                                        <input type="text" maxlength="128" class="form-control w-100" id="add-title" name="add-title" autocomplete="off" required>
                                    </div>
                                </div>
                            </div>

                            <div class="modal-footer">
                                <button type="button" class="btn btn-primary" onclick="addTitle();"><i class="fa-solid fa-plus"></i> Add Title</button>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> Close</button>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- End Add Title Modal -->

                <!-- Edit Title Modal -->
                <div id="edit-title-modal-div"></div>
                <!-- End Edit Title Modal -->

                <!-- Delete Title Modal -->
                <div id="delete-title-modal-div"></div>
                <!-- End Delete Title Modal -->

                <script>
                    // initialize the caseloads_categories table
                    var titles = $("#titles").DataTable({
                        ajax: {
                            url: "ajax/employees/getTitles.php",
                            type: "POST",
                        },
                        autoWidth: false,
                        async: false,
                        pageLength: <?php if (isset($USER_SETTINGS["page_length"])) { echo $USER_SETTINGS["page_length"]; } else { echo 10; } ?>,
                        lengthMenu: [ [10, 25, 50, 100, 250, -1], [10, 25, 50, 100, 250, "All"] ],
                        columns: [
                            // display columns
                            { data: "name", orderable: true, width: "30%" },
                            { data: "employees_count", orderable: true, width: "15%" },
                            { data: "actions", orderable: false, width: "55%" }
                        ],
                        dom: 'rt',
                        language: {
                            search: '<div class="input-group mb-1"><div class="input-group-prepend"><span class="d-flex align-items-center h-100 mx-2" id="nav-search-icon"><i class="fa fa-search" aria-hidden="true"></i></span></div>_INPUT_</div>',
                            lengthMenu: '<div class="mx-1 mb-1">Show _MENU_ entries</div>',
                            info: '<div class="mx-1">Showing _START_ to _END_ of _TOTAL_ entries</div>'
                        },
                        saveState: false,
                        rowCallback: function (row, data, index)
                        {
                            updatePageSelection("titles");
                        }
                    });

                    // search table by custom search filter
                    $('#search-all').keyup(function() {
                        titles.search($(this).val()).draw();
                    });

                    // function to clear search filters
                    $('#clearFilters').click(function() {
                        $('#search-all').val("");
                        titles.search("").columns().search("").draw();
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