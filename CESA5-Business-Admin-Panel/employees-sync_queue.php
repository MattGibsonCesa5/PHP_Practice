<?php 
    include_once("header.php");

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1) 
    { 
        if (isset($_SESSION["role"]) && $_SESSION["role"] == 1)
        {
            // connect to the database
            $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

            // initialize an array to store all periods; then get all periods and store in the array
            $periods = [];
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
                        $active_period_label = $period["name"]; 
                        $active_start_date = date("m/d/Y", strtotime($period["start_date"]));
                        $active_end_date = date("m/d/Y", strtotime($period["end_date"])); 
                    }
                }
            }

            ?> 
                <script>
                    /** function to accept or reject a sync request */
                    function syncAction(queue_id, action = 0)
                    {
                        // send the data to process the sync request
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/employees/syncController.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                if (this.responseText == 1) {
                                    if (action == 1) {
                                        // successfully synced setting, disable buttons and show success
                                        document.getElementById("btn-action-success-"+queue_id).setAttribute("disabled", true);
                                        document.getElementById("btn-action-danger-"+queue_id).remove();
                                    } else {

                                    }
                                } else if (this.responseText == 2) {
                                    if (action == 0) {
                                        // successfully synced setting, disable buttons and show success
                                        document.getElementById("btn-action-danger-"+queue_id).setAttribute("disabled", true);
                                        document.getElementById("btn-action-success-"+queue_id).remove();
                                    } else {

                                    }
                                }
                            }
                        };
                        xmlhttp.send("queue_id="+queue_id+"&action="+action+"&new=0");   
                    }

                    /** function to accept or reject a new sync request */
                    function syncNew(employee_id, action = 0)
                    {
                        // send the data to process the sync request
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/employees/syncController.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                if (this.responseText == 1) {
                                    if (action == 1) {
                                        // successfully synced setting, disable buttons and show success
                                        document.getElementById("btn-action-success-new-"+employee_id).setAttribute("disabled", true);
                                        document.getElementById("btn-action-danger-new-"+employee_id).remove();
                                    } else {

                                    }
                                } else if (this.responseText == 2) {
                                    if (action == 0) {
                                        // successfully synced setting, disable buttons and show success
                                        document.getElementById("btn-action-danger-new-"+employee_id).setAttribute("disabled", true);
                                        document.getElementById("btn-action-success-new-"+employee_id).remove();
                                    } else {

                                    }
                                }
                            }
                        };
                        xmlhttp.send("employee_id="+employee_id+"&action="+action+"&new=1");   
                    }

                    /** function to toggle the page view */
                    function toggleView(type)
                    {
                        // hide both page views
                        document.getElementById("view-queue-div").classList.add("d-none");
                        document.getElementById("view-new-div").classList.add("d-none");
                        document.getElementById("view-history-div").classList.add("d-none");
                        document.getElementById("view-queue-button").classList.remove("btn-primary");
                        document.getElementById("view-new-button").classList.remove("btn-primary");
                        document.getElementById("view-history-button").classList.remove("btn-primary");
                        document.getElementById("view-queue-button").classList.add("btn-secondary");
                        document.getElementById("view-new-button").classList.add("btn-secondary");
                        document.getElementById("view-history-button").classList.add("btn-secondary");

                        // display and select the view toggled
                        document.getElementById("view-"+type+"-button").classList.add("btn-primary");
                        document.getElementById("view-"+type+"-div").classList.remove("d-none");
                    }
                </script>

                <div class="report">
                    <div class="row report-body m-0">
                        <!-- Page Header -->
                        <div class="table-header sticky-top p-0">
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
                                                <select class="form-select" id="search-period" name="search-period" onchange="showQueue();">
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

                                                    <!-- Filter By Field -->
                                                    <div class="row d-flex justify-content-between align-items-center mx-0 mt-0 mb-2">
                                                        <div class="col-4 ps-0 pe-1">
                                                            <label for="search-field">Field:</label>
                                                        </div>

                                                        <div class="col-8 ps-1 pe-0">
                                                            <select class="form-select" id="search-field" name="search-field">
                                                                <option></option>
                                                                <option value="active">Status</option>
                                                                <option value="calendar_type">Calendar Type</option>
                                                                <option value="contract_days">Contract Days</option>
                                                                <option value="contract_end_date">Contract End Date</option>
                                                                <option value="contract_start_date">Contract Start Date</option>
                                                                <option value="dental_insurance">Dental Insurance</option>
                                                                <option value="health_insurance">Health Insurance</option>
                                                                <option value="most_recent_end_date">Most Recent End Date</option>
                                                                <option value="most_recent_hire_date">Most Recent Hire Date</option>
                                                                <option value="number_of_pays">Number Of Pays</option>
                                                                <option value="original_end_date">Original End Date</option>
                                                                <option value="original_start_date">Original Start Date</option>
                                                                <option value="wrs_eligible">WRS Eligibility</option>
                                                                <option value="yearly_rate">Yearly Rate</option>
                                                            </select>
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
                                    <h2 class="m-0">Employees Sync</h2>
                                </div>

                                <!-- Page Queue Dropdown -->
                                <div class="col-6 col-sm-4 col-md-4 col-lg-3 col-xl-2 col-xxl-2 p-0">
                                    <div class="dropdown float-end">
                                        <button class="btn btn-primary dropdown-toggle" type="button" id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">
                                            Manage Queue
                                        </button>
                                        <ul class="dropdown-menu p-0" aria-labelledby="dropdownMenuButton1">
                                            <li><button class="dropdown-item quickNav-dropdown-item text-center w-100 px-3 py-2 rounded-0" type="button" data-bs-toggle="modal" data-bs-target="#"></button></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- View Subpage Buttons -->
                        <div class="btn-group w-100 m-0 p-0" role="group" aria-label="Button group to select which the page view">
                            <button class="btn btn-primary btn-subpages-primary w-100 rounded-0" id="view-queue-button" style="border-top: 1px solid white; border-bottom: 1px solid white;" onclick="toggleView('queue');" value="1">Queue</button>
                            <button class="btn btn-secondary btn-subpages-primary w-100 rounded-0" id="view-new-button" style="border-top: 1px solid white; border-bottom: 1px solid white;" onclick="toggleView('new');" value="0">New</button>
                            <button class="btn btn-secondary btn-subpages-primary w-100 rounded-0" id="view-history-button" style="border-top: 1px solid white; border-bottom: 1px solid white;" onclick="toggleView('history');" value="0">History</button>
                        </div>

                        <!--
                            --
                            --  QUEUE
                            --
                        -->
                        <div id="view-queue-div" class="p-0">
                            <table id="queue" class="report_table w-100">
                                <thead>
                                    <tr>
                                        <th class="text-center py-1 px-2">ID</th>
                                        <th class="text-center py-1 px-2">Last Name</th>
                                        <th class="text-center py-1 px-2">First Name</th>
                                        <th class="text-center py-1 px-2">Field</th>
                                        <th class="text-center py-1 px-2">Old Value</th>
                                        <th class="text-center py-1 px-2">New Value</th>
                                        <th class="text-center py-1 px-2">Sync Time</th>
                                        <th class="text-center py-1 px-2">Status</th>
                                        <th class="text-center py-1 px-2">Actions</th>
                                    </tr>
                                </thead>
                            </table>
                            <?php createTableFooterV2("queue", "BAP_EmployeesSyncQueue_PageLength", $USER_SETTINGS["page_length"], true, true); ?>
                        </div>

                        <!--
                            --
                            --  NEW
                            --
                        -->
                        <div id="view-new-div" class="d-none p-0">
                            <table id="new" class="report_table w-100">
                                <thead>
                                    <tr>
                                        <th class="text-center py-1 px-2">ID</th>
                                        <th class="text-center py-1 px-2">Last Name</th>
                                        <th class="text-center py-1 px-2">First Name</th>
                                        <th class="text-center py-1 px-2">Email</th>
                                        <th class="text-center py-1 px-2">Phone</th>
                                        <th class="text-center py-1 px-2">Hire Date</th>
                                        <th class="text-center py-1 px-2">End Date</th>
                                        <th class="text-center py-1 px-2">Sync Time</th>
                                        <th class="text-center py-1 px-2">Actions</th>
                                    </tr>
                                </thead>
                            </table>
                            <?php createTableFooterV2("new", "BAP_EmployeesSyncNew_PageLength", $USER_SETTINGS["page_length"], true, true); ?>
                        </div>

                        <!--
                            --
                            --  HISTORY
                            --
                        -->
                        <div id="view-history-div" class="d-none p-0">
                            <table id="history" class="report_table w-100">
                                <thead>
                                    <tr>
                                        <th class="text-center py-1 px-2">ID</th>
                                        <th class="text-center py-1 px-2">Last Name</th>
                                        <th class="text-center py-1 px-2">First Name</th>
                                        <th class="text-center py-1 px-2">Field</th>
                                        <th class="text-center py-1 px-2">Old Value</th>
                                        <th class="text-center py-1 px-2">New Value</th>
                                        <th class="text-center py-1 px-2">Sync Time</th>
                                        <th class="text-center py-1 px-2">Action</th>
                                        <th class="text-center py-1 px-2">Status</th>
                                        <th class="text-center py-1 px-2">Action Timestamp</th>
                                    </tr>
                                </thead>
                            </table>
                            <?php createTableFooterV2("history", "BAP_EmployeesSyncQueue_PageLength", $USER_SETTINGS["page_length"], true, true); ?>
                        </div>
                    </div>
                </div>

                <script>
                    /** function to show employee data for the selected period */
                    function showQueue()
                    {
                        // get the value of the period we are searching
                        var period = document.getElementById("search-period").value;

                        if (period != "" && period != null && period != undefined)
                        {
                            // update session storage stored search parameter
                            sessionStorage["BAP_EmployeesSyncQueue_Search_Period"] = period;

                            // set the fixed period
                            document.getElementById("fixed-period").value = period;

                            // initialize queue table
                            var queue = $("#queue").DataTable({
                                ajax: {
                                    url: "ajax/employees/getSyncQueue.php",
                                    type: "POST",
                                    data: {
                                        period: period
                                    }
                                },
                                destroy: true,
                                autoWidth: false,
                                async: false,
                                pageLength: <?php if (isset($USER_SETTINGS["page_length"])) { echo $USER_SETTINGS["page_length"]; } else { echo 10; } ?>,
                                lengthMenu: [ [10, 25, 50, 100, 250, -1], [10, 25, 50, 100, 250, "All"] ],
                                columns: [
                                    // display columns
                                    { data: "id", orderable: true, width: "5%", className: "text-center" }, // 0
                                    { data: "lname", orderable: true, width: "10%", className: "text-center" },
                                    { data: "fname", orderable: true, width: "10%", className: "text-center" },
                                    { data: "field", orderable: true, width: "10%", className: "text-center" },
                                    { data: "old", orderable: true, width: "10%", className: "text-center" },
                                    { data: "new", orderable: true, width: "10%", className: "text-center" },
                                    { data: "requested", orderable: true, width: "10%", className: "text-center" },
                                    { data: "status", orderable: true, width: "10%", className: "text-center", visible: false },
                                    { data: "actions", orderable: false, width: "10%" }, 
                                ],
                                order: [ // order alphabetically by default
                                    [ 1, "asc" ],
                                    [ 2, "asc" ]
                                ],
                                dom: 'rt',
                                language: {
                                    search: '<div class="input-group mb-1"><div class="input-group-prepend"><span class="d-flex align-items-center h-100 mx-2" id="nav-search-icon"><i class="fa fa-search" aria-hidden="true"></i></span></div>_INPUT_</div>',
                                    lengthMenu: '<div class="mx-1 mb-1">Show _MENU_ entries</div>',
                                    info: '<div class="mx-1">Showing _START_ to _END_ of _TOTAL_ entries</div>'
                                },
                                initComplete: function ()
                                {
                                    // if we are not redrawing the table (on page load), attempt to jump to prior spot
                                    let y = getCookie("BAP_EmployeesSyncQueue_yPos");
                                    window.scrollTo(0, y);
                                },
                                rowCallback: function (row, data, index)
                                {
                                    // initialie page selection
                                    updatePageSelection("queue");
                                },
                            });

                            // search table by custom search filter
                            $('#search-all').keyup(function() {
                                queue.search($(this).val()).draw();
                                sessionStorage["BAP_EmployeesSyncQueue_Search_All"] = $(this).val();
                            });

                            // search table by project code
                            $('#search-field').change(function() {
                                queue.columns(3).search($(this).val()).draw();
                                sessionStorage["BAP_EmployeesSyncQueue_Search_Field"] = $(this).val();
                            });

                            // function to clear search filters
                            $('#clearFilters').click(function() {
                                sessionStorage["BAP_EmployeesSyncQueue_Search_All"] = "";
                                sessionStorage["BAP_EmployeesSyncQueue_Search_Field"] = "";
                                $('#search-all').val("");
                                $('#search-field').val("");
                                queue.search("").columns().search("").draw();
                            });
                        }
                    }

                    /** function to show employee data for the selected period */
                    function showNew()
                    {
                        // initialize queue table
                        var newEmps = $("#new").DataTable({
                            ajax: {
                                url: "ajax/employees/getSyncNew.php",
                                type: "POST",
                            },
                            destroy: true,
                            autoWidth: false,
                            async: false,
                            pageLength: <?php if (isset($USER_SETTINGS["page_length"])) { echo $USER_SETTINGS["page_length"]; } else { echo 10; } ?>,
                            lengthMenu: [ [10, 25, 50, 100, 250, -1], [10, 25, 50, 100, 250, "All"] ],
                            columns: [
                                // display columns
                                { data: "id", orderable: true, width: "5%", className: "text-center" }, // 0
                                { data: "lname", orderable: true, width: "10%", className: "text-center" },
                                { data: "fname", orderable: true, width: "10%", className: "text-center" },
                                { data: "email", orderable: true, width: "10%", className: "text-center" },
                                { data: "phone", orderable: true, width: "10%", className: "text-center" },
                                { data: "hire_date", orderable: true, width: "10%", className: "text-center" },
                                { data: "end_date", orderable: true, width: "10%", className: "text-center" },
                                { data: "sync_time", orderable: true, width: "10%", className: "text-center" },
                                { data: "actions", orderable: false, width: "10%" }, 
                            ],
                            order: [ // order alphabetically by default
                                [ 1, "asc" ],
                                [ 2, "asc" ]
                            ],
                            dom: 'rt',
                            language: {
                                search: '<div class="input-group mb-1"><div class="input-group-prepend"><span class="d-flex align-items-center h-100 mx-2" id="nav-search-icon"><i class="fa fa-search" aria-hidden="true"></i></span></div>_INPUT_</div>',
                                lengthMenu: '<div class="mx-1 mb-1">Show _MENU_ entries</div>',
                                info: '<div class="mx-1">Showing _START_ to _END_ of _TOTAL_ entries</div>'
                            },
                            rowCallback: function (row, data, index)
                            {
                                // initialie page selection
                                updatePageSelection("new");
                            },
                        });

                        // search table by custom search filter
                        $('#search-all').keyup(function() {
                            queue.search($(this).val()).draw();
                            sessionStorage["BAP_EmployeesSyncNew_Search_All"] = $(this).val();
                        });

                        // function to clear search filters
                        $('#clearFilters').click(function() {
                            sessionStorage["BAP_EmployeesSyncNew_Search_All"] = "";
                            $('#search-all').val("");
                            newEmps.search("").columns().search("").draw();
                        });
                    }

                    /** function to show the history */
                    function showHistory()
                    {
                        // get the value of the period we are searching
                        var period = document.getElementById("search-period").value;

                        if (period != "" && period != null && period != undefined)
                        {
                            // update session storage stored search parameter
                            sessionStorage["BAP_EmployeesSyncQueue_Search_Period"] = period;

                            // set the fixed period
                            document.getElementById("fixed-period").value = period;

                            // initialize history table
                            var history = $("#history").DataTable({
                                ajax: {
                                    url: "ajax/employees/getSyncHistory.php",
                                    type: "POST",
                                    data: {
                                        period: period
                                    }
                                },
                                destroy: true,
                                autoWidth: false,
                                async: false,
                                pageLength: <?php if (isset($USER_SETTINGS["page_length"])) { echo $USER_SETTINGS["page_length"]; } else { echo 10; } ?>,
                                lengthMenu: [ [10, 25, 50, 100, 250, -1], [10, 25, 50, 100, 250, "All"] ],
                                columns: [
                                    // display columns
                                    { data: "id", orderable: true, width: "5%", className: "text-center" }, // 0
                                    { data: "lname", orderable: true, width: "15%", className: "text-center" },
                                    { data: "fname", orderable: true, width: "15%", className: "text-center" },
                                    { data: "field", orderable: true, width: "12.5%", className: "text-center" },
                                    { data: "old", orderable: true, width: "12.5%", className: "text-center" },
                                    { data: "new", orderable: true, width: "12.5%", className: "text-center" },
                                    { data: "requested", orderable: true, width: "10%", className: "text-center" },
                                    { data: "action", orderable: true, width: "10%", className: "text-center" },
                                    { data: "status", orderable: true, width: "5%", className: "text-center" },
                                    { data: "action_time", orderable: true, className: "text-center", visible: false },
                                ],
                                order: [ // order by most recent action, then alphabetically by default
                                    [ 8, "desc" ],
                                    [ 1, "asc" ],
                                    [ 2, "asc" ]
                                ],
                                dom: 'rt',
                                language: {
                                    search: '<div class="input-group mb-1"><div class="input-group-prepend"><span class="d-flex align-items-center h-100 mx-2" id="nav-search-icon"><i class="fa fa-search" aria-hidden="true"></i></span></div>_INPUT_</div>',
                                    lengthMenu: '<div class="mx-1 mb-1">Show _MENU_ entries</div>',
                                    info: '<div class="mx-1">Showing _START_ to _END_ of _TOTAL_ entries</div>'
                                },
                                rowCallback: function (row, data, index)
                                {
                                    // initialie page selection
                                    updatePageSelection("history");
                                },
                            });

                            // search table by custom search filter
                            $('#search-all').keyup(function() {
                                history.search($(this).val()).draw();
                                sessionStorage["BAP_EmployeesSyncQueue_Search_All"] = $(this).val();
                            });

                            // search table by project code
                            $('#search-field').change(function() {
                                history.columns(3).search($(this).val()).draw();
                                sessionStorage["BAP_EmployeesSyncQueue_Search_Field"] = $(this).val();
                            });

                            // function to clear search filters
                            $('#clearFilters').click(function() {
                                sessionStorage["BAP_EmployeesSyncQueue_Search_All"] = "";
                                sessionStorage["BAP_EmployeesSyncQueue_Search_Field"] = "";
                                $('#search-all').val("");
                                $('#search-field').val("");
                                history.search("").columns().search("").draw();
                            });
                        }
                    }

                    // get queue from default parameters
                    showQueue();
                    showNew();
                    showHistory();
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