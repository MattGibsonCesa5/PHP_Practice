<?php 
    include_once("header.php");

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1) 
    { 
        if (isset($PERMISSIONS["VIEW_THERAPISTS"]))
        {
            // connect to the database
            $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

            // get the active period label
            $active_period_label = getActivePeriodLabel($conn);

            ?>
                <!-- Page Styling Override -->
                <style>
                    .selectize-dropdown .selected
                    {
                        background-color: #f05323 !important;
                    }
                    
                    .selectize-dropdown .option:hover
                    {
                        background-color: #f0532399 !important;
                    }
                </style>

                <script>
                    /** function to add a new student */
                    function addAssistant()
                    {
                        // get form fields
                        let therapist = document.getElementById("add-therapist").value;
                        let category = document.getElementById("add-category").value;

                        // send the data to process the add student request
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/caseloads/addAssistant.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // create the status modal
                                let status_title = "Add Assistant Status";
                                let status_body = this.responseText;
                                createStatusModal("refresh", status_title, status_body);

                                // hide the current modal
                                $("#addAssistantModal").modal("hide");
                            }
                        };
                        xmlhttp.send("therapist_id="+therapist+"&category_id="+category);
                    }

                    /** function to remove the employee as an assistant */
                    function removeAssistant(assistant_id)
                    {
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/caseloads/removeAssistant.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // create the status modal
                                let status_title = "Remove Assistant Status";
                                let status_body = this.responseText;
                                createStatusModal("refresh", status_title, status_body);

                                // hide the current modal
                                $("#removeAssistantModal").modal("hide");
                            }
                        };
                        xmlhttp.send("assistant_id="+assistant_id);
                    }

                    /** function to get the remove assistant modal */
                    function getRemoveAssistantModal(assistant_id)
                    {
                        // send the data to create the delete caseload modal
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/caseloads/getRemoveAssistantModal.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // display the remove assistant modal
                                document.getElementById("remove-assistant-modal-div").innerHTML = this.responseText;     
                                $("#removeAssistantModal").modal("show");
                            }
                        };
                        xmlhttp.send("assistant_id="+assistant_id);
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

                                                    <!-- Filter By Category -->
                                                    <div class="row d-flex justify-content-between align-items-center mx-0 mt-0 mb-2">
                                                        <div class="col-4 ps-0 pe-1">
                                                            <label for="search-category">Category:</label>
                                                        </div>

                                                        <div class="col-8 ps-1 pe-0">
                                                            <select class="form-select" id="search-category" name="search-category">
                                                                <option></option>
                                                                <?php
                                                                    $getCategories = mysqli_query($conn, "SELECT id, name FROM caseload_categories ORDER BY name ASC");
                                                                    if (mysqli_num_rows($getCategories) > 0)
                                                                    {
                                                                        while ($category = mysqli_fetch_array($getCategories))
                                                                        {
                                                                            // store category details locally
                                                                            $category_id = $category["id"];
                                                                            $category_name = $category["name"];

                                                                            // create selection option
                                                                            echo "<option>".$category_name."</option>";
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
                                    <h1 class="m-0">Caseload Assistants</h1>
                                </div>

                                <!-- Page Management Dropdown -->
                                <div class="col-6 col-sm-4 col-md-4 col-lg-3 col-xl-2 col-xxl-2 p-0">
                                    <button class="btn btn-primary px-5 py-2 float-end" type="button" data-bs-toggle="modal" data-bs-target="#addAssistantModal">Add Assistant</button>
                                </div>
                            </div>
                        </div>

                        <table id="assistants" class="report_table w-100">
                            <thead>
                                <tr>
                                    <th class="text-center py-1 px-2">Name</th>
                                    <th class="text-center py-1 px-2">Title</th>
                                    <th class="text-center py-1 px-2">Category</th>
                                    <th class="text-center py-1 px-2"># of <?php echo $active_period_label; ?> Students</th>
                                    <th class="text-center py-1 px-2"></th>
                                </tr>
                            </thead>
                        </table>
                        <?php createTableFooter("assistants", "BAP_ManageAssistants_PageLength", $USER_SETTINGS["page_length"], true, true); ?>
                    </div>
                </div>

                <!--
                    ### MODALS ###
                -->
                <!-- Add Assistant Modal -->
                <div class="modal fade" tabindex="-1" role="dialog" id="addAssistantModal" data-bs-backdrop="static" aria-labelledby="addAssistantModalLabel" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header primary-modal-header">
                                <h5 class="modal-title primary-modal-title" id="addAssistantModalLabel">Add Assistant</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>

                            <div class="modal-body">
                                <div class="form-row d-flex justify-content-center align-items-center my-3">
                                    <!-- Therapist -->
                                    <div class="form-group col-11">
                                        <label for="add-therapist"><span class="required-field">*</span> Assistant:</label>
                                        <select id="add-therapist" name="add-therapist" placeholder="Please select a therapist to create the caseload for..." required>
                                            <option></option>
                                            <?php
                                                $getEmployees = mysqli_query($conn, "SELECT id, fname, lname FROM employees WHERE status=1 ORDER BY lname ASC, fname ASC");
                                                if (mysqli_num_rows($getEmployees) > 0)
                                                {
                                                    while ($employee = mysqli_fetch_array($getEmployees))
                                                    {
                                                        // store employee details locally
                                                        $id = $employee["id"];
                                                        $fname = $employee["fname"];
                                                        $lname = $employee["lname"];

                                                        // create selection option
                                                        echo "<option value='".$id."'>".$lname.", ".$fname."</option>";
                                                    }
                                                }
                                            ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="form-row d-flex justify-content-center align-items-center my-3">
                                    <!-- Category -->
                                    <div class="form-group col-11">
                                        <label for="add-category"><span class="required-field">*</span> Category:</label>
                                        <select class="form-select" id="add-category" name="add-category" required onchange="categoryChanged(this.value, 'add');">
                                            <option></option>
                                            <?php
                                                $getCategories = mysqli_query($conn, "SELECT id, name FROM caseload_categories WHERE allow_assistants=1 ORDER BY name ASC");
                                                if (mysqli_num_rows($getCategories) > 0)
                                                {
                                                    while ($category = mysqli_fetch_array($getCategories))
                                                    {
                                                        // store category details locally
                                                        $category_id = $category["id"];
                                                        $category_name = $category["name"];

                                                        // create selection option
                                                        echo "<option value='".$category_id."'>".$category_name."</option>";
                                                    }
                                                }
                                            ?>
                                        </select>
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
                                <button type="button" class="btn btn-primary" onclick="addAssistant();"><i class="fa-solid fa-floppy-disk"></i> Add Assistant</button>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> Close</button>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- End Add Assistant Modal -->
                
                <!-- Remove Assistant Modal -->
                <div id="remove-assistant-modal-div"></div>
                <!-- End Remove Assistant Modal -->

                <script>
                    // initialization
                    $("#add-therapist").selectize();

                    // initialize the caseloads table
                    var assistants = $("#assistants").DataTable({
                        ajax: {
                            url: "ajax/caseloads/getAssistants.php",
                            type: "POST"
                        },
                        autoWidth: false,
                        async: false,
                        pageLength: <?php if (isset($USER_SETTINGS["page_length"])) { echo $USER_SETTINGS["page_length"]; } else { echo 10; } ?>,
                        lengthMenu: [ [10, 25, 50, 100, 250, -1], [10, 25, 50, 100, 250, "All"] ],
                        columns: [
                            // display columns
                            { data: "name", orderable: true, width: "25%" },
                            { data: "title", orderable: true, width: "25%", className: "text-center" },
                            { data: "caseload_category", orderable: true, width: "20%", className: "text-center" },
                            { data: "caseload_count", orderable: true, width: "15%", className: "text-center" },
                            { data: "actions", orderable: true, width: "15%" },
                        ],
                        dom: 'rt',
                        language: {
                            search: '<div class="input-group mb-1"><div class="input-group-prepend"><span class="d-flex align-items-center h-100 mx-2" id="nav-search-icon"><i class="fa fa-search" aria-hidden="true"></i></span></div>_INPUT_</div>',
                            lengthMenu: '<div class="mx-1 mb-1">Show _MENU_ entries</div>',
                            info: '<div class="mx-1">Showing _START_ to _END_ of _TOTAL_ entries</div>'
                        },
                        stateSave: false,
                        rowCallback: function (row, data, index)
                        {
                            updatePageSelection("assistants");
                        }
                    });

                    // search table by custom search filter
                    $('#search-all').keyup(function() {
                        assistants.search($(this).val()).draw();
                        sessionStorage["BAP_ManageAssistants_Search_All"] = $(this).val();
                    });

                    // search table by custom search filter
                    $('#search-category').change(function() {
                        assistants.columns(2).search($(this).val()).draw();
                        sessionStorage["BAP_ManageAssistants_Search_Category"] = $(this).val();
                    });

                    // function to clear search filters
                    $('#clearFilters').click(function() {
                        sessionStorage["BAP_ManageAssistants_Search_All"] = "";
                        sessionStorage["BAP_ManageAssistants_Search_Category"] = "";
                        $('#search-all').val("");
                        $('#search-category').val("");
                        assistants.search("").columns().search("").draw();
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