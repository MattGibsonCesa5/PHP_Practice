<?php 
    include_once("header.php");

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1) 
    { 
        if (isset($PERMISSIONS["VIEW_CASELOADS_ALL"]) && isset($PERMISSIONS["VIEW_THERAPISTS"]))
        {
            // connect to the database
            $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

            // get the active period label
            $active_period_label = getActivePeriodLabel($conn);

            ?>
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
                                    <h1 class="m-0">Caseload Coordinators</h1>
                                </div>

                                <!-- Page Management Dropdown -->
                                <div class="col-6 col-sm-4 col-md-4 col-lg-3 col-xl-2 col-xxl-2 p-0"></div>
                            </div>
                        </div>

                        <table id="caseload_coordinators" class="report_table w-100">
                            <thead>
                                <tr>
                                    <th class="text-center py-1 px-2">Name</th>
                                    <th class="text-center py-1 px-2">Caseloads Assigned</th>
                                    <th class="text-center py-1 px-2">Actions</th>
                                </tr>
                            </thead>
                        </table>
                        <?php createTableFooterV2("caseload_coordinators", "BAP_CaseloadCoordinators_PageLength", $USER_SETTINGS["page_length"], true, true); ?>
                    </div>
                </div>

                <!--
                    ### MODALS ###
                -->
                <!-- Add Coordinator Modal -->
                <div id="add-coordinator-modal-div"></div>
                <!-- End Add Coordinator Modal -->

                <!-- Remove Coordinator Modal -->
                <div id="remove-coordinator-modal-div"></div>
                <!-- End Remove Coordinator Modal -->

                <!-- Edit Coordinator Modal -->
                <div id="edit-coordinator-modal-div"></div>
                <!-- End Edit Coordinator Modal -->
                <!--
                    ### END MODALS ###
                -->

                <script>
                    // initialize the caseload_coordinators table
                    var caseload_coordinators = $("#caseload_coordinators").DataTable({
                        ajax: {
                            url: "ajax/caseloads/getCaseloadCoordinators.php",
                            type: "POST",
                        },
                        autoWidth: false,
                        async: false,
                        pageLength: <?php if (isset($USER_SETTINGS["page_length"])) { echo $USER_SETTINGS["page_length"]; } else { echo 10; } ?>,
                        lengthMenu: [ [10, 25, 50, 100, 250, -1], [10, 25, 50, 100, 250, "All"] ],
                        columns: [
                            // display columns
                            { data: "name", orderable: true, width: "20%" },
                            { data: "caseloads", orderable: true, width: "30%" },
                            { data: "actions", orderable: false, width: "50%" },
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
                            updatePageSelection("caseload_coordinators");
                        }
                    });

                    // search table by custom search filter
                    $('#search-all').keyup(function() {
                        caseload_coordinators.search($(this).val()).draw();
                        sessionStorage["BAP_CaseloadCoordinators_Search_All"] = $(this).val();
                    });

                    // function to clear search filters
                    $('#clearFilters').click(function() {
                        sessionStorage["BAP_CaseloadCoordinators_Search_All"] = "";
                        $('#search-all').val("");
                        caseload_coordinators.search("").columns().search("").draw();
                    });

                    /** function to get the add coordinator modal */
                    var addCoordinatorsCaseloadsModalDrawn = 0;
                    function getAddCoordinatorModal(coordinator_id)
                    {
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/caseloads/getAddCoordinatorModal.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                document.getElementById("add-coordinator-modal-div").innerHTML = this.responseText;     
                                $("#addCoordinatorModal").modal("show");

                                // if we have already drawn the table, destroy existing table
                                if (addCoordinatorsCaseloadsModalDrawn == 1) { $("#add-coordinators-caseloads").DataTable().destroy(); }

                                // initialize the caseloads table
                                var coordinatorsCaseloadsTable = $("#add-coordinators-caseloads").DataTable({
                                    ajax: {
                                        url: "ajax/caseloads/getAddCoordinatorTable.php",
                                        type: "POST",
                                    },
                                    autoWidth: false,
                                    async: false,
                                    pageLength: -1,
                                    paging: false,
                                    columns: [
                                        // display columns
                                        { data: "checked", orderable: true, width: "10%" },
                                        { data: "caseload_id", orderable: true, visible: false },
                                        { data: "caseload_name", orderable: true, width: "90%" },
                                    ],
                                    columnDefs: [{
                                        orderable: true,
                                        className: "select-checkbox",
                                        targets: 0
                                    }],
                                    select: {
                                        style: "multi",
                                        selector: "td:first-child",
                                    },
                                    order: [
                                        [ 2, "asc" ]
                                    ],
                                    dom: 'rt',
                                    language: {
                                        search: '<div class="input-group mb-1"><div class="input-group-prepend"><span class="d-flex align-items-center h-100 mx-2" id="nav-search-icon"><i class="fa fa-search" aria-hidden="true"></i></span></div>_INPUT_</div>',
                                        lengthMenu: '<div class="mx-1 mb-1">Show _MENU_ entries</div>',
                                        info: '<div class="mx-1">Showing _START_ to _END_ of _TOTAL_ entries</div>'
                                    },
                                    stateSave: false,
                                });

                                // mark that we have drawn the table
                                addCoordinatorsCaseloadsModalDrawn = 1;
                            }
                        };
                        xmlhttp.send();
                    }

                    /** function to add a coordinator */
                    function addCoordinator()
                    {
                        // get the form values
                        let coordinator_id = document.getElementById("add-coordinator_id").value;
                        
                        // get the selected caseloads the coordinator will be assigned to
                        let caseloadsTable = $("#add-coordinators-caseloads").DataTable();
                        let caseloadsCount = caseloadsTable.rows({ selected: true }).count();
                        let assignments = [];
                        for (let c = 0; c < caseloadsCount; c++) { assignments.push(caseloadsTable.rows({ selected: true }).data()[c]["caseload_id"]); }

                        // send the data to process the add department request
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/caseloads/addCoordinator.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // create the status modal
                                let status_title = "Add Coordinator Status";
                                let status_body = this.responseText;
                                createStatusModal("refresh", status_title, status_body);

                                // hide the current modal
                                $("#addCoordinatorModal").modal("hide");
                            }
                        };
                        xmlhttp.send("coordinator_id="+coordinator_id+"&caseloads="+JSON.stringify(assignments));
                    }

                    /** function to remove a coordinator */
                    function removeCoordinator(coordinator_id)
                    {
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/caseloads/removeCoordinator.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // create the status modal
                                let status_title = "Remove Coordinator Status";
                                let status_body = this.responseText;
                                createStatusModal("refresh", status_title, status_body);

                                // hide the current modal
                                $("#removeCoordinatorModal").modal("hide");
                            }
                        };
                        xmlhttp.send("coordinator_id="+coordinator_id);
                    }

                    /** function to get the remove coordinator modal */
                    function getRemoveCoordinatorModal(coordinator_id)
                    {
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/caseloads/getRemoveCoordinatorModal.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                document.getElementById("remove-coordinator-modal-div").innerHTML = this.responseText;     
                                $("#removeCoordinatorModal").modal("show");
                            }
                        };
                        xmlhttp.send("coordinator_id="+coordinator_id);
                    }

                    /** function to edit a coordinator */
                    function editCoordinator(coordinator_id)
                    {
                        // get the selected caseloads the coordinator will be assigned to
                        let caseloadsTable = $("#edit-coordinators-caseloads").DataTable();
                        let caseloadsCount = caseloadsTable.rows({ selected: true }).count();
                        let assignments = [];
                        for (let c = 0; c < caseloadsCount; c++) { assignments.push(caseloadsTable.rows({ selected: true }).data()[c]["caseload_id"]); }

                        // send the request to edit the coordinator
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/caseloads/editCoordinator.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // create the status modal
                                let status_title = "Edit Coordinator Status";
                                let status_body = this.responseText;
                                createStatusModal("refresh", status_title, status_body);

                                // hide the current modal
                                $("#editCoordinatorModal").modal("hide");
                            }
                        };
                        xmlhttp.send("coordinator_id="+coordinator_id+"&caseloads="+JSON.stringify(assignments));
                    }

                    /** function to get the edit coordinator modal */
                    var editCoordinatorsCaseloadsModalDrawn = 0;
                    function getEditCoordinatorModal(coordinator_id)
                    {
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/caseloads/getEditCoordinatorModal.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                document.getElementById("edit-coordinator-modal-div").innerHTML = this.responseText;     
                                $("#editCoordinatorModal").modal("show");

                                // if we have already drawn the table, destroy existing table
                                if (editCoordinatorsCaseloadsModalDrawn == 1) { $("#edit-coordinators-caseloads").DataTable().destroy(); }

                                // initialize the caseloads table
                                var coordinatorsCaseloadsTable = $("#edit-coordinators-caseloads").DataTable({
                                    ajax: {
                                        url: "ajax/caseloads/getEditCoordinatorTable.php",
                                        type: "POST",
                                        data: {
                                            coordinator_id: coordinator_id
                                        }
                                    },
                                    autoWidth: false,
                                    async: false,
                                    pageLength: -1,
                                    paging: false,
                                    columns: [
                                        // display columns
                                        { data: "checked", orderable: true, width: "10%" },
                                        { data: "caseload_id", orderable: true, visible: false },
                                        { data: "caseload_name", orderable: true, width: "90%" },
                                        { data: "assigned", orderable: true, visible: false },
                                    ],
                                    columnDefs: [{
                                        orderable: true,
                                        className: "select-checkbox",
                                        targets: 0
                                    }],
                                    select: {
                                        style: "multi",
                                        selector: "td:first-child",
                                    },
                                    order: [
                                        [ 3, "desc" ],
                                        [ 2, "asc" ]
                                    ],
                                    dom: 'rt',
                                    language: {
                                        search: '<div class="input-group mb-1"><div class="input-group-prepend"><span class="d-flex align-items-center h-100 mx-2" id="nav-search-icon"><i class="fa fa-search" aria-hidden="true"></i></span></div>_INPUT_</div>',
                                        lengthMenu: '<div class="mx-1 mb-1">Show _MENU_ entries</div>',
                                        info: '<div class="mx-1">Showing _START_ to _END_ of _TOTAL_ entries</div>'
                                    },
                                    stateSave: false,
                                    initComplete: function() {
                                        // pre-select rows of caseloads coordinator is assigned to
                                        let data = coordinatorsCaseloadsTable.rows().data();
                                        for (let r = 0; r < data.length; r++) { if (data[r]["assigned"] == 1) { coordinatorsCaseloadsTable.row(":eq("+r+")").select(); } }
                                    }
                                });

                                // mark that we have drawn the table
                                editCoordinatorsCaseloadsModalDrawn = 1;
                            }
                        };
                        xmlhttp.send("coordinator_id="+coordinator_id);
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