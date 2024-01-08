<?php 
    include_once("header.php");

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1) 
    { 
        if (isset($PERMISSIONS["VIEW_CASELOADS_ALL"]))
        {
            // connect to the database
            $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

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
                    /** function to add a school */
                    function addSchool()
                    {
                        // get student information form fields
                        let district_id = document.getElementById("add-district_id").value;
                        let school_name = document.getElementById("add-school_name").value;
                        let grade_group = document.getElementById("add-grade_group").value;

                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/customers/addSchool.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // create the status modal
                                let status_title = "Add School Status";
                                let status_body = this.responseText;
                                createStatusModal("refresh", status_title, status_body);

                                // hide the current modal
                                $("#addSchoolModal").modal("hide");
                            }
                        };
                        xmlhttp.send("district_id="+district_id+"&school_name="+encodeURIComponent(school_name)+"&grade_group="+encodeURIComponent(grade_group));
                    }

                    /** function to get the modal to edit a school */
                    function getEditSchoolModal(school_id)
                    {
                        // send the data to create the edit school modal
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/customers/getEditSchoolModal.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // display the edit student modal
                                document.getElementById("edit-school-modal-div").innerHTML = this.responseText;
                                $("#editSchoolModal").modal("show");
                            }
                        };
                        xmlhttp.send("school_id="+school_id);
                    }

                    /** function to edit a school */
                    function editSchool(school_id)
                    {
                        // get student information form fields
                        let school_name = document.getElementById("edit-school_name").value;

                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/customers/editSchool.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // create the status modal
                                let status_title = "Edit School Status";
                                let status_body = this.responseText;
                                createStatusModal("refresh", status_title, status_body);

                                // hide the current modal
                                $("#editSchoolModal").modal("hide");
                            }
                        };
                        xmlhttp.send("school_id="+school_id+"&school_name="+encodeURIComponent(school_name));
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

                                                    <!-- Filter By District -->
                                                    <div class="row d-flex justify-content-between align-items-center mx-0 mt-0 mb-2">
                                                        <div class="col-4 ps-0 pe-1">
                                                            <label for="search-customers">District:</label>
                                                        </div>

                                                        <div class="col-8 ps-1 pe-0">
                                                            <select class="form-select" id="search-customers" name="search-customers">
                                                                <option></option>
                                                                <?php
                                                                    $getCustomers = mysqli_query($conn, "SELECT id, name FROM `customers` ORDER BY name ASC");
                                                                    if (mysqli_num_rows($getCustomers) > 0) // services exist
                                                                    {
                                                                        while ($customer = mysqli_fetch_array($getCustomers))
                                                                        {
                                                                            echo "<option value='".$customer["id"]."'>".$customer["name"]."</option>";
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
                                    <h1 class="m-0">Schools</h1>
                                </div>

                                <!-- Page Management Dropdown -->
                                <div class="col-6 col-sm-4 col-md-4 col-lg-3 col-xl-2 col-xxl-2 p-0">
                                    <div class="dropdown float-end">
                                        <button class="btn btn-primary dropdown-toggle" type="button" id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">
                                            Manage Schools
                                        </button>
                                        <ul class="dropdown-menu p-0" aria-labelledby="dropdownMenuButton1">
                                            <li><button class="dropdown-item quickNav-dropdown-item text-center px-3 py-2 rounded-0" type="button" data-bs-toggle="modal" data-bs-target="#addSchoolModal">Add School</button></li>      
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <table id="schools" class="report_table w-100">
                            <thead>
                                <tr>
                                    <th class="text-center py-1 px-2">District</th>
                                    <th class="text-center py-1 px-2">School</th>
                                    <th class="text-center py-1 px-2">Grade Group</th>
                                    <th class="text-center py-1 px-2"></th>
                                </tr>
                            </thead>
                        </table>
                        <?php createTableFooterV2("schools", "BAP_Schools_PageLength", $USER_SETTINGS["page_length"], true, true); ?>
                    </div>
                </div>

                <!--
                    ### MODALS ###
                -->
                <!-- Add School Modal -->
                <div class="modal fade" tabindex="-1" role="dialog" id="addSchoolModal" data-bs-backdrop="static" aria-labelledby="addSchoolModalLabel" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header primary-modal-header">
                                <h5 class="modal-title primary-modal-title" id="addSchoolModalLabel">Add School</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>

                            <div class="modal-body">
                                <div class="form-row d-flex justify-content-center align-items-center my-3">
                                    <!-- Name -->
                                    <div class="form-group col-11">
                                        <label for="add-district_id"><span class="required-field">*</span> District:</label>
                                        <select id="add-district_id" name="add-district_id" required>
                                            <option></option>
                                            <?php
                                                $getDistricts = mysqli_query($conn, "SELECT id, name FROM `customers` ORDER BY name ASC");
                                                if (mysqli_num_rows($getDistricts) > 0) // districts found
                                                {
                                                    while ($district = mysqli_fetch_array($getDistricts))
                                                    {
                                                        echo "<option value='".$district["id"]."'>".$district["name"]."</option>";
                                                    }
                                                }
                                            ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="form-row d-flex justify-content-center align-items-center my-3">
                                    <!-- Name -->
                                    <div class="form-group col-11">
                                        <label for="add-school_name"><span class="required-field">*</span> School Name:</label>
                                        <input type="text" class="form-control" id="add-school_name" name="add-school_name" autocomplete="off" required>
                                    </div>
                                </div>

                                <div class="form-row d-flex justify-content-center align-items-center my-3">
                                    <!-- Grade Group -->
                                    <div class="form-group col-11">
                                        <label for="add-grade_group"><span class="required-field">*</span> Grade Group:</label>
                                        <select class="form-select" id="add-grade_group" name="add-grade_group" required>
                                            <option></option>
                                            <option>Elementary School</option>
                                            <option>Middle School</option>
                                            <option>Junior High School</option>
                                            <option>High School</option>
                                            <option>Combined Elementary/Secondary School</option>
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
                                <button type="button" class="btn btn-primary" onclick="addSchool();"><i class="fa-solid fa-plus"></i> Add School</button>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> Close</button>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Add School Modal -->

                <!-- Edit School Modal -->
                <div id="edit-school-modal-div"></div>
                <!-- End Edit School Modal -->

                <script>
                    // initialize fields
                    $(function() {
                        $("#add-district_id").selectize();
                    });

                    // initialize the caseloads table
                    var schools = $("#schools").DataTable({
                        ajax: {
                            url: "ajax/customers/getSchools.php",
                            type: "POST",
                        },
                        autoWidth: false,
                        async: false,
                        pageLength: <?php if (isset($USER_SETTINGS["page_length"])) { echo $USER_SETTINGS["page_length"]; } else { echo 10; } ?>,
                        lengthMenu: [ [10, 25, 50, 100, 250, -1], [10, 25, 50, 100, 250, "All"] ],
                        columns: [
                            // display columns
                            { data: "district", orderable: true, width: "20%", className: "text-center" },
                            { data: "school", orderable: true, width: "25%", className: "text-center" },
                            { data: "grade_group", orderable: true, width: "25%", className: "text-center" },
                            { data: "actions", orderable: true, width: "50%" },
                            { data: "district_id", visible: false },
                        ],
                        dom: 'rt',
                        order: [
                            [ 0, "asc" ], [ 1, "asc" ]
                        ],
                        language: {
                            search: '<div class="input-group mb-1"><div class="input-group-prepend"><span class="d-flex align-items-center h-100 mx-2" id="nav-search-icon"><i class="fa fa-search" aria-hidden="true"></i></span></div>_INPUT_</div>',
                            lengthMenu: '<div class="mx-1 mb-1">Show _MENU_ entries</div>',
                            info: '<div class="mx-1">Showing _START_ to _END_ of _TOTAL_ entries</div>'
                        },
                        saveState: false,
                        rowCallback: function (row, data, index)
                        {
                            updatePageSelection("schools");
                        }
                    });

                    // search table by custom search filter
                    $('#search-all').keyup(function() {
                        schools.search($(this).val()).draw();
                        sessionStorage["BAP_Schools_Search_All"] = $(this).val();
                    });

                    // search table by customer
                    $('#search-customers').change(function() {
                        if ($(this).val() != "") { schools.columns(4).search("^" + $(this).val() + "$", true, false, true).draw(); }
                        else { schools.columns(4).search("").draw(); }
                        sessionStorage["BAP_Schools_Search_District"] = $(this).val();
                    });

                    // function to clear search filters
                    $('#clearFilters').click(function() {
                        sessionStorage["BAP_Schools_Search_All"] = "";
                        sessionStorage["BAP_Schools_Search_District"] = "";
                        $('#search-all').val("");
                        $('#search-customers').val("");
                        schools.search("").columns().search("").draw();
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