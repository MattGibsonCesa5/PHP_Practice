<?php 
    include_once("header.php");

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1) 
    { 
        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (isset($PERMISSIONS["VIEW_CASELOADS_ALL"]) || (checkUserPermission($conn, "VIEW_CASELOADS_ASSIGNED") && verifyCoordinator($conn, $_SESSION["id"])))
        {
            // initialize an array to store all periods; then get all periods and store in the array
            $periods = [];
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
                </style>

                <script>
                    /** function to toggle the page view */
                    function toggleView(type)
                    {
                        // hide both page views
                        document.getElementById("view-district-div").classList.add("d-none");
                        document.getElementById("view-therapist-div").classList.add("d-none");
                        document.getElementById("view-district-button").classList.remove("btn-primary");
                        document.getElementById("view-therapist-button").classList.remove("btn-primary");
                        document.getElementById("view-district-button").classList.add("btn-secondary");
                        document.getElementById("view-therapist-button").classList.add("btn-secondary");

                        // display and select the view toggled
                        document.getElementById("view-"+type+"-button").classList.add("btn-primary");
                        document.getElementById("view-"+type+"-div").classList.remove("d-none");
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
                                        <div class="row">
                                            <div class="input-group w-100 h-auto">
                                                <div class="input-group-prepend" id="period-icon-div">
                                                    <span class="input-group-text h-100" id="nav-search-icon">
                                                        <i class="fa-solid fa-calendar-days"></i>
                                                        <span id="period-label">Period</span>
                                                    </span>
                                                </div>
                                                <select class="form-select" id="search-period" name="search-period" onchange="displayReport();">
                                                    <option></option>
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
                                            <div class="dropdown-menu filters-menu px-2" aria-labelledby="filtersMenu" style="width: 320px;">
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
                                                        <label for="search-district">District:</label>
                                                    </div>

                                                    <div class="col-8 ps-1 pe-0">
                                                        <select class="form-select" id="search-district" name="search-district">
                                                            <option></option>
                                                            <?php
                                                                $getCustomers = mysqli_query($conn, "SELECT DISTINCT c.id, c.name FROM `customers` c 
                                                                                                    JOIN cases ON (c.id=cases.residency OR c.id=cases.district_attending)
                                                                                                    ORDER BY c.name ASC");
                                                                if (mysqli_num_rows($getCustomers) > 0) // services exist
                                                                {
                                                                    while ($customer = mysqli_fetch_array($getCustomers))
                                                                    {
                                                                        echo "<option>".$customer["name"]."</option>";
                                                                    }
                                                                }
                                                            ?>
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

                                                <!-- Filter By Classroom -->
                                                <div class="row d-flex justify-content-between align-items-center mx-0 mt-0 mb-2">
                                                    <div class="col-4 ps-0 pe-1">
                                                        <label for="search-classroom">Classroom:</label>
                                                    </div>

                                                    <div class="col-8 ps-1 pe-0">
                                                        <select class="form-select" id="search-classroom" name="search-classroom">
                                                            <option></option>
                                                            <?php
                                                                // get categories/classrooms to populate options
                                                                $getCategories = mysqli_query($conn, "SELECT id, name FROM caseload_categories WHERE is_classroom=1 ORDER BY name ASC");
                                                                if (mysqli_num_rows($getCategories) > 0)
                                                                {
                                                                    while ($category = mysqli_fetch_array($getCategories))
                                                                    {
                                                                        // store category details locally
                                                                        $category_id = $category["id"];
                                                                        $category_name = $category["name"];

                                                                        // get classrooms for each category
                                                                        $getClassrooms = mysqli_prepare($conn, "SELECT id, name, label FROM caseload_classrooms WHERE category_id=? ORDER BY name ASC, label ASC");
                                                                        mysqli_stmt_bind_param($getClassrooms, "i", $category_id);
                                                                        if (mysqli_stmt_execute($getClassrooms))
                                                                        {
                                                                            $getClassroomsResults = mysqli_stmt_get_result($getClassrooms);
                                                                            if (mysqli_num_rows($getClassroomsResults) > 0) // classrooms found
                                                                            { 
                                                                                echo "<optgroup label='".$category_name."'>";
                                                                                while ($classroom = mysqli_fetch_array($getClassroomsResults))
                                                                                {
                                                                                    if (isset($classroom["label"]) && trim($classroom["label"] <> ""))
                                                                                    {
                                                                                        echo "<option value='".$classroom["id"]."'>".$classroom["label"]."</option>";
                                                                                    } else {
                                                                                        echo "<option value='".$classroom["id"]."'>".$classroom["name"]."</option>";
                                                                                    }
                                                                                }
                                                                                echo "</optgroup>";
                                                                            }
                                                                        }
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
                                <h1 class="report-title m-0">Billing Summary</h1>
                            </div>

                            <!-- Page Management Dropdown -->
                            <div class="col-6 col-sm-4 col-md-4 col-lg-3 col-xl-2 col-xxl-2 p-0"></div>
                        </div>
                    </div>

                    <div id="report-div" class="d-none">
                        <!-- View Buttons -->
                        <div class="btn-group w-100 m-0 p-0" role="group" aria-label="Button group to select which the page view">
                            <button class="btn btn-primary btn-subpages-primary w-100 rounded-0" id="view-district-button" onclick="toggleView('district');" style="border-top: 1px solid white; border-bottom: 1px solid white;">District View</button>
                            <button class="btn btn-secondary btn-subpages-primary w-100 rounded-0" id="view-therapist-button" onclick="toggleView('therapist');" style="border-top: 1px solid white; border-bottom: 1px solid white;">Therapist View</button>
                        </div>

                        <!-- District View -->
                        <div id="view-district-div" class="p-0">
                            <!-- container to store table -->
                            <table id="UOSBilling_district" class="report_table w-100">
                                <thead>
                                    <tr>
                                        <th class="text-center py-1 px-2">District</th>
                                        <th class="text-center py-1 px-2">Category</th>
                                        <th class="text-center py-1 px-2">Location</th>
                                        <th class="text-center py-1 px-2"># Of Students</th>
                                        <th class="text-center py-1 px-2">Membership Days</th>
                                        <th class="text-center py-1 px-2"><span data-bs-toggle="tooltip" data-bs-placement="bottom" title="Total units differs based on the service category. Some units be display the units of service (UOS), while others may display the full-time equivalent (FTE).">Total Units</span></th>
                                        <th class="text-center py-1 px-2"><span data-bs-toggle="tooltip" data-bs-placement="bottom" title="The projected annual cost the district will spend for the service based on the current units provided.">Projected Cost</span></th>
                                    </tr>
                                </thead>

                                <tfoot>
                                    <tr>
                                        <th class="text-center py-1 px-2" colspan="3"></th>
                                        <th class="text-center py-1 px-2" id="district-sum-students"></th>
                                        <th class="text-center py-1 px-2" id="district-sum-days"></th>
                                        <th class="text-center py-1 px-2" id="district-sum-units"></th>
                                        <th class="text-center py-1 px-2" id="district-sum-cost"></th>
                                    </tr>
                                </tfoot>
                            </table>
                            <?php createTableFooterV2("UOSBilling_district", "BAP_Caseloads_UOSBilling_District_PageLength", $USER_SETTINGS["page_length"], true, true); ?>
                        </div>

                        <!-- Therapist View -->
                        <div id="view-therapist-div" class="d-none p-0">
                            <!-- container to store table -->
                            <table id="UOSBilling_therapist" class="report_table w-100">
                                <thead>
                                    <tr>
                                        <th class="text-center py-1 px-2">Therapist</th>
                                        <th class="text-center py-1 px-2">Category</th>
                                        <th class="text-center py-1 px-2"># Of Students</th>
                                        <th class="text-center py-1 px-2">Membership Days</th>
                                        <th class="text-center py-1 px-2"><span data-bs-toggle="tooltip" data-bs-placement="bottom" title="Total units differs based on the service category. Some units be display the units of service (UOS), while others may display the full-time equivalent (FTE).">Total Units</span></th>
                                    </tr>
                                </thead>

                                <tfoot>
                                    <tr>
                                        <th class="text-center py-1 px-2" colspan="2"></th>
                                        <th class="text-center py-1 px-2" id="therapist-sum-students"></th>
                                        <th class="text-center py-1 px-2" id="therapist-sum-days"></th>
                                        <th class="text-center py-1 px-2" id="therapist-sum-units"></th>
                                    </tr>
                                </tfoot>
                            </table>
                            <?php createTableFooterV2("UOSBilling_therapist", "BAP_Caseloads_UOSBilling_Therapist_PageLength", $USER_SETTINGS["page_length"], true, true); ?>
                        </div>
                    </div>
                </div>

                <script>
                    // initialize tooltips
                    $("[data-bs-toggle=\"tooltip\"]").tooltip();

                    // get today's date
                    var today = new Date().toLocaleDateString();

                    // initialize variable to state if we've drawn the tables or not
                    var drawn = 0; // assume we have not drawn the tables (0)

                    // get the current active period
                    let active_period = "<?php echo $active_period_label; ?>"; 

                    // set the search filters to values we have saved in storage
                    if (sessionStorage["BAP_UOSBilling_Search_Period"] != "" && sessionStorage["BAP_UOSBilling_Search_Period"] != null && sessionStorage["BAP_UOSBilling_Search_Period"] != undefined) { $('#search-period').val(sessionStorage["BAP_UOSBilling_Search_Period"]); }
                    else { $('#search-period').val(active_period); } // no period set; default to active period 

                    function displayReport()
                    {
                        // get the value of the period we are searching
                        var period = document.getElementById("search-period").value;

                        if (period != "" && period != null && period != undefined)
                        {
                            // update session storage stored search parameter
                            sessionStorage["BAP_UOSBilling_Search_Period"] = period;

                            // call the funtion to build the report by district
                            let district = createDistrictReport(period);

                            // call the funtion to build the report by therapist
                            let therapist = createTherapistReport(period);

                            // search table by custom search filter
                            $('#search-all').keyup(function() {
                                district.search($(this).val()).draw();
                                therapist.search($(this).val()).draw();
                                // sessionStorage["BAP_UOSBilling_District_Search_All"] = $(this).val();
                            });

                            // search table by distrcit
                            $('#search-district').change(function() {
                                if ($(this).val() != "") { district.columns(0).search("^" + $(this).val() + "$", true, false, true).draw(); }
                                else { district.columns(0).search("").draw(); }
                            });

                            // search table by distrcit
                            $('#search-category').change(function() {
                                if ($(this).val() != "") { district.columns(1).search("^" + $(this).val() + "$", true, false, true).draw(); }
                                else { district.columns(1).search("").draw(); }

                                if ($(this).val() != "") { therapist.columns(1).search("^" + $(this).val() + "$", true, false, true).draw(); }
                                else { therapist.columns(1).search("").draw(); }
                            });

                            // search table by classroom
                            $('#search-classroom').change(function() {
                                if ($(this).val() != "") { district.columns(8).search("^" + $(this).val() + "$", true, false, true).draw(); }
                                else { district.columns(8).search("").draw(); }
                            });

                            // function to clear search filters
                            $('#clearFilters').click(function() {
                                $('#search-all').val("");
                                $('#search-district').val("");
                                $('#search-category').val("");
                                $('#search-classroom').val("");
                                district.search("").columns().search("").draw();
                                therapist.search("").columns().search("").draw();
                            });

                            // display the report container
                            document.getElementById("report-div").classList.remove("d-none");
                        }
                        else { createStatusModal("alert", "Loading Report Error", "Failed to load the report. You must select a period to generate the report for."); }
                    }

                    /** function to create the district report */
                    function createDistrictReport(period)
                    {   
                        var district = $("#UOSBilling_district").DataTable({
                            ajax: {
                                url: "ajax/caseloads/getDistrictCaseloadBillingReport.php",
                                type: "POST",
                                data: {
                                    period: period,
                                }
                            },
                            destroy: true,
                            autoWidth: false,
                            pageLength: <?php if (isset($USER_SETTINGS["page_length"])) { echo $USER_SETTINGS["page_length"]; } else { echo 10; } ?>,
                            lengthMenu: [ [10, 25, 50, 100, 250, -1], [10, 25, 50, 100, 250, "All"] ],
                            columns: [
                                { data: "district", orderable: true, width: "20%", className: "text-center" },
                                { data: "category", orderable: true, width: "20%", className: "text-center" },
                                { data: "location", orderable: true, width: "20%", className: "text-center" },
                                { data: "students", orderable: true, width: "10%", className: "text-center" },
                                { data: "days", orderable: true, width: "10%", className: "text-center" },
                                { data: "units", orderable: true, width: "9%", className: "text-center" },
                                { data: "cost", orderable: true, width: "11%", className: "text-center" },
                                { data: "calc_cost", orderable: true, visible: false },
                                { data: "classroom_id_filter", orderable: false, visible: false }
                            ],
                            dom: 'rt',
                            pagination: true,
                            language: {
                                search: '<div class="input-group mb-1"><div class="input-group-prepend"><span class="d-flex align-items-center h-100 mx-2" id="nav-search-icon"><i class="fa fa-search" aria-hidden="true"></i></span></div>_INPUT_</div>',
                                lengthMenu: '<div class="mx-1 mb-1">Show _MENU_ entries</div>',
                                info: '<div class="mx-1">Showing _START_ to _END_ of _TOTAL_ entries</div>',
                                loadingRecords: '<i class="fa-solid fa-spinner fa-spin"></i> Loading...',
                            },
                            rowCallback: function (row, data, index)
                            {
                                updatePageSelection("UOSBilling_district");
                            },
                            drawCallback: function ()
                            {
                                var api = this.api();

                                // get the sum of all filtered districts
                                let students_sum = api.column(3, { search: "applied" }).data().sum().toFixed(0);
                                let days_sum = api.column(4, { search: "applied" }).data().sum().toFixed(0);
                                let units_sum = api.column(5, { search: "applied" }).data().sum().toFixed(2);
                                let cost_sum = api.column(7, { search: "applied" }).data().sum().toFixed(2);

                                // update the table footer
                                document.getElementById("district-sum-students").innerHTML = numberWithCommas(students_sum);
                                document.getElementById("district-sum-days").innerHTML = numberWithCommas(days_sum);
                                document.getElementById("district-sum-units").innerHTML = numberWithCommas(units_sum);
                                document.getElementById("district-sum-cost").innerHTML = "$"+numberWithCommas(cost_sum);
                            },
                        });
                        
                        // return table
                        return district;
                    }

                    /** function to create the therapist report */
                    function createTherapistReport(period)
                    {  
                        // initialize table
                        var therapist = $("#UOSBilling_therapist").DataTable({
                            ajax: {
                                url: "ajax/caseloads/getTherapistCaseloadBillingReport.php",
                                type: "POST",
                                data: {
                                    period: period,
                                }
                            },
                            destroy: true,
                            autoWidth: false,
                            pageLength: <?php if (isset($USER_SETTINGS["page_length"])) { echo $USER_SETTINGS["page_length"]; } else { echo 10; } ?>,
                            lengthMenu: [ [10, 25, 50, 100, 250, -1], [10, 25, 50, 100, 250, "All"] ],
                            columns: [
                                { data: "therapist", orderable: true, width: "22.5%", className: "text-center" },
                                { data: "category", orderable: true, width: "20%", className: "text-center" },
                                { data: "students", orderable: true, width: "12.5%", className: "text-center" },
                                { data: "days", orderable: true, width: "10%", className: "text-center" },
                                { data: "units", orderable: true, width: "10%", className: "text-center" },
                            ],
                            dom: 'rt',
                            pagination: true,
                            language: {
                                search: '<div class="input-group mb-1"><div class="input-group-prepend"><span class="d-flex align-items-center h-100 mx-2" id="nav-search-icon"><i class="fa fa-search" aria-hidden="true"></i></span></div>_INPUT_</div>',
                                lengthMenu: '<div class="mx-1 mb-1">Show _MENU_ entries</div>',
                                info: '<div class="mx-1">Showing _START_ to _END_ of _TOTAL_ entries</div>',
                                loadingRecords: '<i class="fa-solid fa-spinner fa-spin"></i> Loading...',
                            },
                            rowCallback: function (row, data, index)
                            {
                                updatePageSelection("UOSBilling_therapist");
                            },
                            drawCallback: function ()
                            {
                                var api = this.api();

                                // get the sum of all filtered quarterly costs
                                let students_sum = api.column(2, { search: "applied" }).data().sum().toFixed(0);
                                let days_sum = api.column(3, { search: "applied" }).data().sum().toFixed(0);
                                let units_sum = api.column(4, { search: "applied" }).data().sum().toFixed(0);

                                // update the table footer
                                document.getElementById("therapist-sum-students").innerHTML = numberWithCommas(students_sum);
                                document.getElementById("therapist-sum-days").innerHTML = numberWithCommas(days_sum);
                                document.getElementById("therapist-sum-units").innerHTML = numberWithCommas(units_sum);
                            }
                        });

                        // return table
                        return therapist;
                    }

                    /** function to load the report on page load if parameters are already set */
                    function generateOnPageLoad()
                    {
                        // get the value of the period we are searching
                        var period = document.getElementById("search-period").value;

                        if (period != "" && period != null && period != undefined)
                        {
                            // if both parameters are set, load the reports on page load
                            displayReport();
                        }
                    }

                    // call the function to attempt to generate the report on page load
                    generateOnPageLoad();
                </script>
            <?php 
        }
        else { denyAccess(); }
        
        // disconnect from the database
        mysqli_close($conn);
    }
    else { goToLogin(); }

    include("footer.php"); 
?>