<?php 
    include_once("header.php");

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1) 
    { 
        if (isset($PERMISSIONS["VIEW_CASELOADS_ALL"]) && isset($PERMISSIONS["VIEW_THERAPISTS"]))
        {
            // connect to the database
            $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

            // initialize an array to store all periods; then get all periods and store in the array
            $periods = [];
            $getPeriods = mysqli_query($conn, "SELECT id, name, active, start_date, end_date, caseload_term_start, caseload_term_end FROM `periods` ORDER BY active DESC, name ASC");
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
                        $active_caseload_term_start_date = date("m/d/Y", strtotime($period["caseload_term_start"]));
                        $active_caseload_term_end_date = date("m/d/Y", strtotime($period["caseload_term_end"]));
                    }
                }
            }
            
            ?> 
                <style>
                    #period-icon-div:hover #period-label
                    {
                        display: inline;
                        color: #000000;
                        transform: translate(4px, 00%);
                    }

                    #period-label
                    {
                        display: none;
                        color: #000000;
                        transition: 1s;
                    }

                    #category-icon-div:hover #category-label
                    {
                        display: inline;
                        color: #000000;
                        transform: translate(4px, 00%);
                    }

                    #category-label
                    {
                        display: none;
                        color: #000000;
                        transition: 1s;
                    }
                </style>

                <script>
                    /** function to toggle report view */
                    function toggleStartEndView(type)
                    {
                        // hide both report views
                        document.getElementById("view-startEndReport-startReport-div").classList.add("d-none");
                        document.getElementById("view-startEndReport-endReport-div").classList.add("d-none");
                        document.getElementById("view-startEndReport-startReport-button").classList.remove("btn-primary");
                        document.getElementById("view-startEndReport-endReport-button").classList.remove("btn-primary");
                        document.getElementById("view-startEndReport-startReport-button").classList.add("btn-secondary");
                        document.getElementById("view-startEndReport-endReport-button").classList.add("btn-secondary");

                        // display and select the view toggled
                        document.getElementById("view-startEndReport-"+type+"-button").classList.add("btn-primary");
                        document.getElementById("view-startEndReport-"+type+"-div").classList.remove("d-none");
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
                                        <!-- Period Selection -->
                                        <div class="col-9 p-0">
                                            <div class="row">
                                                <div class="input-group h-auto">
                                                    <div class="input-group-prepend" id="period-icon-div">
                                                        <span class="input-group-text h-100" id="nav-search-icon">
                                                            <i class="fa-solid fa-calendar-days"></i>
                                                            <span id="period-label">Period</span>
                                                        </span>
                                                    </div>
                                                    <input id="fixed-period" type="hidden" value="" aria-hidden="true">
                                                    <select class="form-select" id="search-period" name="search-period" onchange="buildReport();">
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
                                    <h1 class="m-0">Master Start-End Changes</h1>
                                </div>

                                <!-- Page Management Dropdown -->
                                <div class="col-6 col-sm-4 col-md-4 col-lg-3 col-xl-2 col-xxl-2 p-0"></div>
                            </div>

                            <!-- View Start-End Report Buttons -->
                            <div class="btn-group w-100 m-0 p-0" id="view-startEndReport-buttons-div" role="group" aria-label="Button group to select which the page view">
                                <button class="btn btn-primary w-100 rounded-0" id="view-startEndReport-startReport-button" onclick="toggleStartEndView('startReport');" style="border-top: 1px solid white;">Start Changes (start date after 9/1)</button>
                                <button class="btn btn-secondary w-100 rounded-0" id="view-startEndReport-endReport-button" onclick="toggleStartEndView('endReport');" style="border-top: 1px solid white;">End Changes (end date before 6/1)</button>
                            </div>
                        </div>

                        <div id="view-startEndReport-startReport-div" class="p-0">
                            <table id="startReport" class="report_table w-100">
                                <thead>
                                    <tr>
                                        <th class="py-1 px-2" style="text-align: center !important;">Student</th>
                                        <th class="py-1 px-2" style="text-align: center !important;">Location</th>
                                        <th class="py-1 px-2" style="text-align: center !important;">Start Date</th>
                                        <th class="py-1 px-2" style="text-align: center !important;">End Date</th>
                                        <th class="py-1 px-2" style="text-align: center !important;">Month Evaluation Started</th>
                                        <th class="py-1 px-2" style="text-align: center !important;">Therapist</th>
                                        <th class="py-1 px-2" style="text-align: center !important;">Assistant</th>
                                    </tr>
                                </thead>
                            </table>
                            <?php createTableFooterV2("startReport", "BAP_MasterStartEndChanges_PageLength", $USER_SETTINGS["page_length"], true, true); ?>
                        </div>

                        <div id="view-startEndReport-endReport-div" class="p-0 d-none">
                            <table id="endReport" class="report_table w-100">
                                <thead>
                                    <tr>
                                        <th class="py-1 px-2" style="text-align: center !important;">Student</th>
                                        <th class="py-1 px-2" style="text-align: center !important;">Location</th>
                                        <th class="py-1 px-2" style="text-align: center !important;">Start Date</th>
                                        <th class="py-1 px-2" style="text-align: center !important;">End Date</th>
                                        <th class="py-1 px-2" style="text-align: center !important;">Month Evaluation Started</th>
                                        <th class="py-1 px-2" style="text-align: center !important;">Therapist</th>
                                        <th class="py-1 px-2" style="text-align: center !important;">Assistant</th>
                                        <th class="py-1 px-2" style="text-align: center !important;">Actions</th>
                                    </tr>
                                </thead>
                            </table>
                            <?php createTableFooterV2("endReport", "BAP_MasterStartEndChanges_PageLength", $USER_SETTINGS["page_length"], true, true); ?>
                        </div>
                    </div>
                </div>

                <!--
                    ### MODALS ###
                -->
                <!-- View Student Modal -->
                <div id="view-student-modal-div"></div>
                <!-- End View Student Modal -->
                <!--
                    ### END MODALS ###
                -->

                <script>
                    // get the current active period
                    let active_period = "<?php echo $active_period_label; ?>"; 

                    // set the search filters to values we have saved in storage
                    if (sessionStorage["BAP_MasterStartEndChanges_Search_Period"] != "" && sessionStorage["BAP_MasterStartEndChanges_Search_Period"] != null && sessionStorage["BAP_MasterStartEndChanges_Search_Period"] != undefined) { $('#search-period').val(sessionStorage["BAP_MasterStartEndChanges_Search_Period"]); }
                    else { $('#search-period').val(active_period); } // no period set; default to active period 

                    /** function to build the report */
                    function buildReport()
                    {
                        // get search parameters
                        var period = document.getElementById("search-period").value;

                        if (period != "" && period != null && period != undefined)
                        {
                            // set the fixed period
                            document.getElementById("fixed-period").value = period;

                            // update session storage stored search parameter
                            sessionStorage["BAP_MasterStartEndChanges_Search_Period"] = period;

                            // initialize the start changes report table
                            var startReport = $("#startReport").DataTable({
                                ajax: {
                                    url: "ajax/caseloads/getMasterStartChangesReport.php",
                                    type: "POST",
                                    data: {
                                        period: period,
                                    }
                                },
                                destroy: true,
                                autoWidth: false,
                                async: false,
                                pageLength: <?php if (isset($USER_SETTINGS["page_length"])) { echo $USER_SETTINGS["page_length"]; } else { echo 10; } ?>,
                                lengthMenu: [ [10, 25, 50, 100, 250, -1], [10, 25, 50, 100, 250, "All"] ],
                                columns: [
                                    // display columns
                                    { data: "student", orderable: true, width: "17.5%" },
                                    { data: "location", orderable: true, width: "17.5%" },
                                    { data: "start_date", orderable: true, width: "7.5%", className: "text-center" },
                                    { data: "end_date", orderable: true, width: "7.5%", className: "text-center" },
                                    { data: "month", orderable: true, width: "10%", className: "text-center" },
                                    { data: "therapist", orderable: true, width: "15%", className: "text-center" },
                                    { data: "assistant", orderable: true, width: "12.5%", className: "text-center" },
                                    { data: "status", orderable: true, visible: false },
                                    { data: "district", orderable: true, visible: false },
                                    { data: "grade", orderable: true, visible: false },
                                    { data: "category", orderable: true, visible: false },
                                ],
                                order: [
                                    [ 0, "asc" ],
                                    [ 1, "asc" ],
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
                                    updatePageSelection("startReport");
                                }
                            });

                            // initialize the end changes report table
                            var endReport = $("#endReport").DataTable({
                                ajax: {
                                    url: "ajax/caseloads/getMasterEndChangesReport.php",
                                    type: "POST",
                                    data: {
                                        period: period,
                                    }
                                },
                                destroy: true,
                                autoWidth: false,
                                async: false,
                                pageLength: <?php if (isset($USER_SETTINGS["page_length"])) { echo $USER_SETTINGS["page_length"]; } else { echo 10; } ?>,
                                lengthMenu: [ [10, 25, 50, 100, 250, -1], [10, 25, 50, 100, 250, "All"] ],
                                columns: [
                                    // display columns  
                                    { data: "student", orderable: true, width: "17.5%" },
                                    { data: "location", orderable: true, width: "17.5%" },
                                    { data: "start_date", orderable: true, width: "7.5%", className: "text-center" },
                                    { data: "end_date", orderable: true, width: "7.5%", className: "text-center" },
                                    { data: "month", orderable: true, width: "10%", className: "text-center" },
                                    { data: "therapist", orderable: true, width: "15%", className: "text-center" },
                                    { data: "assistant", orderable: true, width: "12.5%", className: "text-center" },
                                    { data: "actions", orderable: true, width: "12.5%", className: "text-center" },
                                    { data: "status", orderable: true, visible: false },
                                    { data: "district", orderable: true, visible: false },
                                    { data: "grade", orderable: true, visible: false },
                                    { data: "category", orderable: true, visible: false },
                                ],
                                order: [
                                    [ 0, "asc" ],
                                    [ 1, "asc" ],
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
                                    updatePageSelection("endReport");
                                }
                            });

                            // search table by custom search filter
                            $('#search-all').keyup(function() {
                                startReport.search($(this).val()).draw();
                                endReport.search($(this).val()).draw();
                                sessionStorage["BAP_MasterStartEndChanges_Search_All"] = $(this).val();
                            });

                            // search table by custom search filter
                            $('#search-category').change(function() {
                                startReport.columns(10).search($(this).val()).draw();
                                endReport.columns(11).search($(this).val()).draw();
                                sessionStorage["BAP_MasterStartEndChanges_Search_Category"] = $(this).val();
                            });

                            // function to clear search filters
                            $('#clearFilters').click(function() {
                                sessionStorage["BAP_MasterStartEndChanges_Search_All"] = "";
                                sessionStorage["BAP_MasterStartEndChanges_Search_Category"] = "";
                                $('#search-all').val("");
                                $('#search-category').val("");
                                startReport.search("").columns().search("").draw();
                                endReport.search("").columns().search("").draw();
                            });

                            // redraw caseload table with current search fields
                            if ($('#search-all').val() != "") 
                            { 
                                startReport.search($('#search-all').val()).draw(); 
                                endReport.search($('#search-all').val()).draw(); 
                            }
                            if ($('#search-category').val() != "") 
                            { 
                                startReport.columns(10).search($(this).val()).draw();
                                endReport.columns(11).search($(this).val()).draw();
                            }
                        }
                    }

                    // build the report with default parameters
                    buildReport();

                    /** function to get the modal to view which other caseloads the student is in */
                    function getViewStudentModal(case_id, student_id)
                    {
                        // get the fixed period name
                        let period = document.getElementById("fixed-period").value;

                        // send the data to create and display the modal
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/caseloads/getViewStudentModal.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // display the modal
                                document.getElementById("view-student-modal-div").innerHTML = this.responseText;     
                                $("#viewStudentModal").modal("show");
                            }
                        };
                        xmlhttp.send("student_id="+student_id+"&period="+period+"&case_id="+case_id);
                    }

                    /** function to toggle medicaid billing completed */
                    function toggleMedicaidBillingDone(case_id, checked)
                    {
                        // convert checked status to int
                        let done = 0;
                        if (checked === true) { 
                            done = 1;
                        } else {
                            done = 0;
                        }

                        // send the request 
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/caseloads/updateMedicaidBillingDoneStatus.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {

                            }
                        }
                        xmlhttp.send("case_id="+case_id+"&status="+done);
                    }
                </script>
            <?php
        }
    }
?>