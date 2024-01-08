<?php 
//want the header to show up on this pae so we include it
    include_once("header.php");
    //if the $_SESSION built in superglobal associative array has a value for the key "status" and that value is 1, it means the user is logged in to the system and we continue
    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1) 
    { 
        if (isset($PERMISSIONS["VIEW_THERAPISTS"]))
        {
            // connect to the database
            $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

            // initialize an array to store all periods; then get all periods and store in the array, a period is a fiscall period, there are 3 years in the db right now
            //
            $periods = [];
            $getPeriods = mysqli_query($conn, "SELECT id, name, active, start_date, end_date, task_term_start, task_term_end FROM `periods` ORDER BY active DESC, name ASC");
            if (mysqli_num_rows($getPeriods) > 0) // periods exist
            {
                while ($period = mysqli_fetch_array($getPeriods))
                {
                    // store period's data in array
                    $periods[] = $period;

                    // store the active period's name
                    if ($period["active"] == 1) 
                    { 
                        $active_period_label = $period["name"];
                        $active_start_date = date("m/d/Y", strtotime($period["start_date"]));
                        $active_end_date = date("m/d/Y", strtotime($period["end_date"])); 
                        $active_task_term_start_date = date("m/d/Y", strtotime($period["task_term_start"]));
                        $active_task_term_end_date = date("m/d/Y", strtotime($period["task_term_end"]));
                    }
                }
            }
            
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

                    div.dataTables_processing div 
                    {
                        z-index: 1052 !important;
                    }
                </style>

                <script>
                    /** function to add a new student */
                    function createTask()
                    {
                        // get the fixed period name
                        let period = document.getElementById("fixed-period").value;

                        // get form fields
                        let therapist = document.getElementById("add-therapist").value;
                        let category = document.getElementById("add-category").value;
                        let subcategory = document.getElementById("add-subcategory").value;

                        // send the data to process the add student request
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/tasks/createTask.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // create the status modal
                                let status_title = "Create Task Status";
                                let status_body = this.responseText;
                                createStatusModal("refresh", status_title, status_body);

                                // hide the current modal
                                $("#createTaskModal").modal("hide");
                            }
                        };
                        xmlhttp.send("therapist_id="+therapist+"&category_id="+category+"&subcategory_id="+subcategory+"&period="+period);
                    }

                    /** function to delete the therapist's task */
                    function deleteTask(task_id)
                    {
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/tasks/deleteTask.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // create the status modal
                                let status_title = "Delete Task Status";
                                let status_body = this.responseText;
                                createStatusModal("refresh", status_title, status_body);

                                // hide the current modal
                                $("#deleteTaskModal").modal("hide");
                            }
                        };
                        xmlhttp.send("task_id="+task_id);
                    }

                    /** function to get the delete task modal */
                    function getDeleteTaskModal(task_id)
                    {
                        // send the data to create the delete task modal
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/tasks/getDeleteTaskModal.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // display the delete therapist modal
                                document.getElementById("delete-task-modal-div").innerHTML = this.responseText;     
                                $("#deleteTaskModal").modal("show");
                            }
                        };
                        xmlhttp.send("task_id="+task_id);
                    }

                    /** function to edit the therapist */
                    function editTask(task_id)
                    {
                        // get the fixed period name
                        let period = document.getElementById("fixed-period").value;

                        // get form fields
                        let category_id = document.getElementById("edit-category").value;
                        let subcategory_id = document.getElementById("edit-subcategory").value;
                        let status = document.getElementById("edit-status").value;

                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/tasks/editTask.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // create the status modal
                                let status_title = "Edit Task Status";
                                let status_body = this.responseText;
                                createStatusModal("refresh", status_title, status_body);

                                // hide the current modal
                                $("#editTaskModal").modal("hide");
                            }
                        };
                        xmlhttp.send("task_id="+task_id+"&category_id="+category_id+"&subcategory_id="+subcategory_id+"&status="+status+"&period="+period);
                    }

                    /** function to get the modal to edit a therapist */
                    function getEditTaskModal(task_id)
                    {
                        // get the fixed period name
                        let period = document.getElementById("fixed-period").value;

                        // send the data to create the delete therapist modal
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/tasks/getEditTaskModal.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // display the edit task modal
                                document.getElementById("edit-task-modal-div").innerHTML = this.responseText;     
                                $("#editTaskModal").modal("show");
                            }
                        };
                        xmlhttp.send("task_id="+task_id+"&period="+period);
                    }

                    /** function to get the modal to transfer cases from one task to another */
                    function getTransferCasesModal(task_id)
                    {
                        // get the fixed period name
                        let period = document.getElementById("fixed-period").value;

                        // send the data to create the delete therapist modal
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/tasks/getTransferCasesModal.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                document.getElementById("transfer_cases-modal-div").innerHTML = this.responseText;     
                                $("#transferCasesModal").modal("show");
                                
                                // initialize elements
                                $("#transfer-new_task").selectize();
                                $("#transfer-transfer_date").datepicker();
                                $("#transfer-end_date").datepicker();
                            }
                        };
                        xmlhttp.send("task_id="+task_id+"&period="+period);
                    }

                    /** function to transfer students from one task to another en masse */
                    function transferCases(task_id)
                    {
                        // get the fixed period name
                        let period = document.getElementById("fixed-period").value;

                        // get form fields
                        let district_id = document.getElementById("transfer-district_id").value;
                        let new_task = document.getElementById("transfer-new_task").value;
                        let transfer_date = document.getElementById("transfer-transfer_date").value;
                        let end_date = document.getElementById("transfer-end_date").value;
                        let remove_iep = 0;
                        if (document.getElementById("transfer-remove_iep").checked == 1) { remove_iep = 1; }

                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/tasks/transferCases.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // create the status modal
                                let status_title = "Transfer Task Status";
                                let status_body = this.responseText;
                                createStatusModal("refresh", status_title, status_body);

                                // hide the current modal
                                $("#transferCasesModal").modal("hide");
                            }
                        };
                        xmlhttp.send("task_id="+task_id+"&district_id="+district_id+"&new_task="+new_task+"&transfer_date="+transfer_date+"&end_date="+end_date+"&remove_iep="+remove_iep+"&period="+period);
                    }

                    /** function to update the subcategory listing */
                    function categoryChanged(category_id, origin)
                    {
                        // get the subcategory options for the category selected
                        let subcategories = $.ajax({
                            type: "POST",
                            url: "ajax/tasks/getSubcategoryDropdown.php",
                            data: {
                                category_id: category_id
                            },
                            async: false,
                        }).responseText;
                        
                        // update element to include subcategories
                        document.getElementById(origin+"-subcategory").innerHTML = subcategories;
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

                    /** function to get the modal to rollover tasks from one period to another */
                    var rolloverModalDrawn = 0;
                    function getRolloverTasksModal(task_id)
                    {
                        // get the fixed period name
                        let period = document.getElementById("fixed-period").value;

                        // send the data to create the delete therapist modal
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/tasks/getRolloverTasksModal.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                document.getElementById("rollover-tasks-modal-div").innerHTML = this.responseText;     
                                $("#rolloverTasksModal").modal("show");
                                
                                // initialize datepickers
                                $("#rollover-start_date").datepicker();
                                $("#rollover-end_date").datepicker();

                                // if we have already drawn the table, destroy existing table
                                if (rolloverModalDrawn == 1) { $("#rollover-tasks").DataTable().destroy(); }

                                // initialize the tasks table
                                var rolloverTasksTable = $("#rollover-tasks").DataTable({
                                    ajax: {
                                        url: "ajax/tasks/getRolloverTasksTable.php",
                                        type: "POST",
                                        data: {
                                            period: period
                                        }
                                    },
                                    autoWidth: false,
                                    async: false,
                                    pageLength: -1,
                                    paging: false,
                                    lengthMenu: [ [10, 25, 50, 100, 250, -1], [10, 25, 50, 100, 250, "All"] ],
                                    columns: [
                                        // display columns
                                        { data: "is_checked", orderable: true, width: "10%" },
                                        { data: "task_id", orderable: true, visible: false },
                                        { data: "task_name", orderable: true, width: "70%" },
                                        { data: "task_count", orderable: true, width: "20%" },
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
                                    initComplete: function() {
                                        this.api().rows().select();
                                    }
                                });

                                // mark that we have drawn the table
                                rolloverModalDrawn = 1;
                            }
                        };
                        xmlhttp.send("&period="+period);
                    }

                    /** function to rollover tasks */
                    function rolloverTasks()
                    {
                        // get the form fields
                        let period_from = document.getElementById("rollover-period_from").value;
                        let period_to = document.getElementById("rollover-period_to").value;
                        let start_date = document.getElementById("rollover-start_date").value;
                        let end_date = document.getElementById("rollover-end_date").value;

                        // get the selected tasks to rollover
                        let rolloverTasksTable = $("#rollover-tasks").DataTable();
                        let tasksCount = rolloverTasksTable.rows({ selected: true }).count();
                        let rolloverTasks = [];
                        for (let c = 0; c < tasksCount; c++) { rolloverTasks.push(rolloverTasksTable.rows({ selected: true }).data()[c]["task_id"]); }

                        // send the data to process the tasks rollover request
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/tasks/rolloverTasks.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // create the status modal
                                let status_title = "Rollover Tasks Status";
                                let status_body = encodeURIComponent(this.responseText);
                                createStatusModal("refresh", status_title, status_body);

                                // hide the current modal
                                $("#rolloverTasksModal").modal("hide");
                            }
                        };
                        xmlhttp.send("period_from="+period_from+"&period_to="+period_to+"&start_date="+start_date+"&end_date="+end_date+"&tasks="+JSON.stringify(rolloverTasks));
                    }

                    /** function to select all rows in a given table */
                    function selectAll(table_id)
                    {
                        let table = $("#"+table_id).DataTable().rows().select();
                    }

                    /** function to deselect all rows in a given table */
                    function deselectAll(table_id)
                    {
                        let table = $("#"+table_id).DataTable().rows().deselect();
                    }
                </script>

                <div class="report">
                    <!-- Page Header -->
                    <div class="table-header p-0">
                        <div class="row d-flex justify-content-center align-items-center text-center py-2 px-3">
                            <!-- Period & Filters-->
                            <div class="col-6 col-sm-4 col-md-4 col-lg-3 col-xl-2 col-xxl-2 p-0">
                                <div class="row px-3">
                                    <!-- Period Selection -->
                                    <div class="col-9 p-0">
                                        <div class="input-group h-auto">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text h-100" id="nav-search-icon"><i class="fa-solid fa-calendar-days"></i></span>
                                            </div>
                                            <input id="fixed-period" type="hidden" value="" aria-hidden="true">
                                            <select class="form-select" id="search-period" name="search-period" onchange="searchTasks();">
                                                <?php
                                                    for ($p = 0; $p < count($periods); $p++)
                                                    {
                                                        echo "<option value='".$periods[$p]["name"]."'>".$periods[$p]["name"]."</option>";
                                                    }
                                                ?>
                                            </select>
                                        </div>
                                    </div>

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
                                                        <select class="form-select w-100" id="search-status" name="search-status">
                                                            <option value="">Show All</option>
                                                            <option value="Active" style="background-color: #006900; color: #ffffff;" selected>Active</option>
                                                            <option value="Inactive" style="background-color: #e40000; color: #ffffff;">Inactive</option>
                                                        </select>
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
                                                                $getCategories = mysqli_query($conn, "SELECT id, name FROM task_categories ORDER BY name ASC");
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
                                <h1 class="m-0">Tasks</h1>
                            </div>

                            <!-- Page Management Dropdown -->
                            <div class="col-6 col-sm-4 col-md-4 col-lg-3 col-xl-2 col-xxl-2 d-flex justify-content-end p-0">
                                <button class="btn btn-primary h-auto mx-1 dropdown-toggle" id="exportsMenu" type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                                    <i class="fa-solid fa-cloud-arrow-down"></i>
                                </button>
                                <ul class="quickNav-dropdown dropdown-menu p-0" aria-labelledby="exportsMenu" style="min-width: 32px !important;">
                                    <li id="csv-export-div" style="font-size: 24px; text-align: center !important; width: 100% !important;"></li>
                                    <li id="xlsx-export-div" style="font-size: 24px;"></li>
                                    <li id="pdf-export-div" style="font-size: 24px;"></li>
                                    <li id="print-export-div" style="font-size: 24px;"></li>
                                </ul>

                                <?php if ($_SESSION["role"] == 1 || isset($PERMISSIONS["ADD_THERAPISTS"])) { ?>
                                    <div class="dropdown">
                                        <button class="btn btn-primary dropdown-toggle px-4 py-2" type="button" id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">
                                            Manage Tasks
                                        </button>
                                        <ul class="dropdown-menu p-0" aria-labelledby="dropdownMenuButton1">
                                            <?php if (isset($PERMISSIONS["ADD_EMPLOYEES"])) { ?>
                                            <li><button class="dropdown-item quickNav-dropdown-item text-center w-100 px-3 py-2 rounded-0" type="button" data-bs-toggle="modal" data-bs-target="#createTaskModal">Create Task</button></li>
                                            <?php }
                                            if ($_SESSION["role"] == 1) { // ADMINISTRATOR ONLY ?>
                                            <li><button class="dropdown-item quickNav-dropdown-item text-center w-100 px-3 py-2 rounded-0" type="button" onclick="getRolloverTasksModal();">Rollover Tasks</button></li>
                                            <?php } ?>
                                        </ul>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>
                    </div>

                    <div class="row report-body m-0">
                        <table id="tasks" class="report_table w-100">
                            <thead>
                                <tr>
                                    <th class="text-center py-1 px-2" rowspan="2">Order</th>
                                    <th class="text-center py-1 px-2" rowspan="2">Name</th>
                                    <th class="text-center py-1 px-2" rowspan="2">Title</th>
                                    <th class="text-center py-1 px-2" rowspan="2">Category</th>
                                    <th class="text-center py-1 px-2" colspan="2"><span id="period-table_header-text"></span> Totals</th>
                                    <th class="text-center py-1 px-2" rowspan="2"></th>
                                    <th class="text-center py-1 px-2" rowspan="2"></th>
                                    <th class="text-center py-1 px-2" rowspan="2">Therapist</th>
                                    <th class="text-center py-1 px-2" rowspan="2">Status</th>
                                </tr>
                                
                                <tr>
                                    <th class="text-center py-1 px-2"># of Active Students</th>
                                    <th class="text-center py-1 px-2">Units</th>
                                </tr>
                            </thead>

                            <tfoot>
                                <tr>
                                    <th class="text-end py-1 px-2" colspan="4"></th>
                                    <th class="text-end py-1 px-2" id="total_students"></th>
                                    <th class="text-end py-1 px-2" id="total_units"></th>
                                    <th class="text-end py-1 px-2"></th>
                                </tr>
                            </tfoot>
                        </table>
                        <?php createTableFooterV2("tasks", "BAP_ManageTasks_PageLength", $USER_SETTINGS["page_length"], true, true); ?>
                    </div>
                </div>

                <!--
                    ### MODALS ###
                -->
                <!-- Create Task Modal -->
                <div class="modal fade" tabindex="-1" role="dialog" id="createTaskModal" data-bs-backdrop="static" aria-labelledby="createTaskModalLabel" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header primary-modal-header">
                                <h5 class="modal-title primary-modal-title" id="createTaskModalLabel">Create Task</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>

                            <div class="modal-body">
                                <div class="form-row d-flex justify-content-center align-items-center my-3">
                                    <!-- Therapist -->
                                    <div class="form-group col-11">
                                        <label for="add-therapist"><span class="required-field">*</span> Therapist:</label>
                                        <select id="add-therapist" name="add-therapist" placeholder="Please select a therapist to create the task for..." required>
                                            <option></option>
                                            <?php
                                                $getUsers = mysqli_query($conn, "SELECT u.id FROM users u 
                                                                                JOIN therapists t ON u.id=t.user_id 
                                                                                ORDER BY u.fname ASC, u.lname ASC");
                                                if (mysqli_num_rows($getUsers) > 0)
                                                {
                                                    while ($user = mysqli_fetch_array($getUsers))
                                                    {
                                                        // store employee details locally
                                                        $id = $user["id"];

                                                        // get the user's name
                                                        $user_name = getUserDisplayName($conn, $id);

                                                        // create selection option
                                                        echo "<option value='".$id."'>".$user_name."</option>";
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
                                                $getCategories = mysqli_query($conn, "SELECT id, name FROM task_categories ORDER BY name ASC");
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

                                <div class="form-row d-flex justify-content-center align-items-center my-3">
                                    <!-- Subcategory -->
                                    <div class="form-group col-11">
                                        <label for="add-subcategory">Subcategory:</label>
                                        <select class="form-select" id="add-subcategory" name="add-subcategory">
                                            <option></option>
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
                                <button type="button" class="btn btn-primary" onclick="createTask();"><i class="fa-solid fa-floppy-disk"></i> Create Task</button>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> Close</button>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- End Create Task Modal -->

                <!-- Edit Task Modal -->
                <div id="edit-task-modal-div"></div>
                <!-- End Edit Task Modal -->

                <!-- Delete Task Modal -->
                <div id="delete-task-modal-div"></div>
                <!-- End Delete Task Modal -->

                <!-- Transfer Cases Modal -->
                <div id="transfer_cases-modal-div"></div>
                <!-- End Transfer Cases Modal -->

                <!-- Rollover Tasks Modal -->
                <div id="rollover-tasks-modal-div"></div>
                <!-- End Rollover Tasks Modal -->

                <script>
                    // get the current active period
                    let active_period = "<?php echo $active_period_label; ?>"; 

                    // set the search filters to values we have saved in storage
                    if (sessionStorage["BAP_TasksManagement_Search_Period"] != "" && sessionStorage["BAP_TasksManagement_Search_Period"] != null && sessionStorage["BAP_TasksManagement_Search_Period"] != undefined) { $('#search-period').val(sessionStorage["BAP_TasksManagement_Search_Period"]); }
                    else { $('#search-period').val(active_period); } // no period set; default to active period 
                    if (sessionStorage["BAP_ManageTasks_Search_All"] != "" && sessionStorage["BAP_ManageTasks_Search_All"] != null && sessionStorage["BAP_ManageTasks_Search_All"] != undefined) { $('#search-all').val(sessionStorage["BAP_ManageTasks_Search_All"]); }
                    if (sessionStorage["BAP_ManageTasks_Search_Status"] != "" && sessionStorage["BAP_ManageTasks_Search_Status"] != null && sessionStorage["BAP_ManageTasks_Search_Status"] != undefined) { $('#search-status').val(sessionStorage["BAP_ManageTasks_Search_Status"]); }
                    else { $('#search-status').val("Active"); }
                    if (sessionStorage["BAP_ManageTasks_Search_Category"] != "" && sessionStorage["BAP_ManageTasks_Search_Category"] != null && sessionStorage["BAP_ManageTasks_Search_Category"] != undefined) { $('#search-category').val(sessionStorage["BAP_ManageTasks_Search_Category"]); }

                    // initialization
                    $("#add-therapist").selectize();

                    /** function to search for tasks */
                    function searchTasks()
                    {
                        // get the value of the period we are searching
                        var period = document.getElementById("search-period").value;

                        if (period != "" && period != null && period != undefined)
                        {
                            // update the table headers
                            document.getElementById("period-table_header-text").innerHTML = period;

                            // set the fixed period and task id
                            document.getElementById("fixed-period").value = period;

                            // update session storage stored search parameter
                            sessionStorage["BAP_TasksManagement_Search_Period"] = period;

                            // initialize the tasks table
                            var tasks = $("#tasks").DataTable({
                                ajax: {
                                    url: "ajax/tasks/getTasks.php",
                                    type: "POST",
                                    data: {
                                        period: period
                                    }
                                },
                                autoWidth: false,
                                destroy: true,
                                async: true,
                                processing: true,
                                pageLength: <?php if (isset($USER_SETTINGS["page_length"])) { echo $USER_SETTINGS["page_length"]; } else { echo 10; } ?>,
                                lengthMenu: [ [10, 25, 50, 100, 250, -1], [10, 25, 50, 100, 250, "All"] ],
                                columns: [
                                    // display columns
                                    { data: "order", orderable: true, visible: false },
                                    { data: "name", orderable: true, width: "25%" },
                                    { data: "title", orderable: true, width: "25%", className: "text-center" },
                                    { data: "task_category", orderable: true, width: "15%", className: "text-center" },
                                    { data: "task_count", orderable: true, width: "12.5%", className: "text-center" },
                                    { data: "task_units", orderable: true, width: "10%", className: "text-center" },
                                    { data: "actions", orderable: true, width: "12.5%" },
                                    { data: "task_status", orderable: true, visible: false },
                                    { data: "export_name", orderable: true, visible: false },
                                    { data: "export_status", orderable: true, visible: false },
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
                                    processing: "<i class=\"fa-solid fa-spinner fa-spin\"></i> Loading...",
                                },
                                stateSave: false,
                                rowCallback: function (row, data, index)
                                {
                                    // initialize page selection
                                    updatePageSelection("tasks");
                                },
                                drawCallback: function ()
                                {
                                    var api = this.api();

                                    // get the sum of all filtered quarterly costs
                                    let students = api.column(4, { search: "applied" }).data().sum();
                                    let units = api.column(5, { search: "applied" }).data().sum();
                                    
                                    // update the table footer
                                    document.getElementById("total_students").innerHTML = numberWithCommas(students);
                                    document.getElementById("total_units").innerHTML = numberWithCommas(units);
                                },
                                initComplete: function ()
                                {
                                    // create the export buttons
                                    new $.fn.dataTable.Buttons(tasks, {
                                        buttons: [
                                            // CSV BUTTON
                                            {
                                                extend: "csv",
                                                exportOptions: {
                                                    columns: [ 8, 2, 3, 4, 5, 9 ]
                                                },
                                                text: "<i class='fa-solid fa-file-csv'></i>",
                                                className: "dropdown-item quickNav-dropdown-item text-center w-100 px-3 py-2 rounded-0",
                                                title: period + " Tasks",
                                                init: function(api, node, config) {
                                                    // remove default button classes
                                                    $(node).removeClass('dt-button');
                                                    $(node).removeClass('buttons-csv');
                                                    $(node).removeClass('buttons-html5');
                                                }
                                            },
                                        ]
                                    });
                                    new $.fn.dataTable.Buttons(tasks, {
                                        buttons: [
                                            // EXCEL BUTTON
                                            {
                                                extend: "excel",
                                                exportOptions: {
                                                    columns: [ 8, 2, 3, 4, 5, 9 ]
                                                },
                                                text: "<i class='fa-solid fa-file-excel'></i>",
                                                className: "dropdown-item quickNav-dropdown-item text-center w-100 px-3 py-2 rounded-0",
                                                title: period + " Tasks",
                                                init: function(api, node, config) {
                                                    // remove default button classes
                                                    $(node).removeClass('dt-button');
                                                    $(node).removeClass('buttons-excel');
                                                    $(node).removeClass('buttons-html5');
                                                }
                                            },
                                        ]
                                    });
                                    new $.fn.dataTable.Buttons(tasks, {
                                        buttons: [
                                            // PDF BUTTON
                                            {
                                                extend: "pdf",
                                                exportOptions: {
                                                    columns: [ 8, 2, 3, 4, 5, 9 ]
                                                },
                                                orientation: "landscape",
                                                text: "<i class='fa-solid fa-file-pdf'></i>",
                                                className: "dropdown-item quickNav-dropdown-item text-center w-100 px-3 py-2 rounded-0",
                                                title: period + " Tasks",
                                                init: function(api, node, config) {
                                                    // remove default button classes
                                                    $(node).removeClass('dt-button');
                                                    $(node).removeClass('buttons-excel');
                                                    $(node).removeClass('buttons-html5');
                                                }
                                            },
                                        ]
                                    });
                                    new $.fn.dataTable.Buttons(tasks, {
                                        buttons: [
                                            // PRINT BUTTON
                                            {
                                                extend: "print",
                                                exportOptions: {
                                                    columns: [ 8, 2, 3, 4, 5, 9 ]
                                                },
                                                orientation: "landscape",
                                                text: "<i class='fa-solid fa-print'></i>",
                                                className: "dropdown-item quickNav-dropdown-item text-center w-100 px-3 py-2 rounded-0",
                                                title: period + " Tasks",
                                                init: function(api, node, config) {
                                                    // remove default button classes
                                                    $(node).removeClass('dt-button');
                                                    $(node).removeClass('buttons-excel');
                                                    $(node).removeClass('buttons-html5');
                                                }
                                            },
                                        ]
                                    });
                                    // add buttons to page description area
                                    tasks.buttons(0, null).container().appendTo("#csv-export-div");
                                    tasks.buttons(1, null).container().appendTo("#xlsx-export-div");
                                    tasks.buttons(2, null).container().appendTo("#pdf-export-div");
                                    tasks.buttons(3, null).container().appendTo("#print-export-div");
                                }
                            });

                            // search table by custom search filter
                            $('#search-all').keyup(function() {
                                tasks.search($(this).val()).draw();
                                sessionStorage["BAP_ManageTasks_Search_All"] = $(this).val();
                            });

                            // search table by custom search filter
                            $('#search-category').change(function() {
                                tasks.columns(3).search($(this).val()).draw();
                                sessionStorage["BAP_ManageTasks_Search_Category"] = $(this).val();
                            });

                            // search table by task status
                            $('#search-status').change(function() {
                                sessionStorage["BAP_ManageTasks_Search_Status"] = $(this).val();
                                if ($(this).val() != "") { tasks.columns(7).search("^" + $(this).val() + "$", true, false, true).draw(); }
                                else { tasks.columns(7).search("").draw(); }
                            });

                            // function to clear search filters
                            $('#clearFilters').click(function() {
                                sessionStorage["BAP_ManageTasks_Search_All"] = "";
                                sessionStorage["BAP_ManageTasks_Search_Category"] = "";
                                sessionStorage["BAP_ManageTasks_Search_Status"] = "";
                                $('#search-all').val("");
                                $('#search-category').val("");
                                $('#search-status').val("");
                                tasks.search("").columns().search("").draw();
                            });

                            // redraw table with current search fields
                            if ($('#search-all').val() != "" && $('#search-all').val() != null) { tasks.search($('#search-all').val()).draw(); }
                            if ($('#search-category').val() != "" && $('#search-category').val() != null) { tasks.columns(3).search($('#search-category').val()).draw(); }
                            if ($('#search-status').val() != "" && $('#search-status').val() != null) { tasks.columns(7).search("^" + $('#search-status').val() + "$", true, false, true).draw(); }
                        }
                    }

                    // search tasks from the default parameters
                    searchTasks();
                </script>
            <?php 

            // disconnect from the database
            mysqli_close($conn);
        }
        else { denyAccess(); }
    }
    //if person is not logged in, redirect to login page
    else { goToLogin(); }

    include("footer.php"); 
?>