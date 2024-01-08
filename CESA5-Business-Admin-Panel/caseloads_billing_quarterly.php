<?php 
    include_once("header.php");

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1) 
    { 
        if (isset($PERMISSIONS["VIEW_CASELOADS_ALL"]))
        {
            // connect to the database
            $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

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
                    #period-icon-div:hover #period-label, #quarter-icon-div:hover #quarter-label, #district-icon-div:hover #district-label, #category-icon-div:hover #category-label
                    {
                        display: inline;
                        color: #000000;
                        transform: translate(4px, 00%);
                    }

                    #period-label, #quarter-label, #district-label, #category-label
                    {
                        display: none;
                        color: #000000;
                        transition: 1s;
                    }
                </style>

                <script>
                    /** function to get and display the "Bill Districts" modal */
                    function getBillDistrictsModal()
                    {
                        // get the search parameters
                        var period = document.getElementById("search-period").value;
                        var quarter = document.getElementById("search-quarter").value;

                        // send the data to create the bill districts modal
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/caseloads/getBillDistrictsModal.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                document.getElementById("bill_districts-modal-div").innerHTML = this.responseText;     
                                $("#billDistrictsModal").modal("show");
                            }
                        };
                        xmlhttp.send("period="+period+"&quarter="+quarter);
                    }

                    /** function to bill districts for the selected period and quarter for the generated report */
                    function billDistricts()
                    {
                        // get the search parameters
                        var period = document.getElementById("search-period").value;
                        var quarter = document.getElementById("search-quarter").value;

                        // disable the "Bill Districts" button, add processing spinner
                        let billDistrictsBtn = document.getElementById("billDistrictsBtn");
                        billDistrictsBtn.setAttribute("disabled", true);
                        let spinner = document.createElement("i");
                        spinner.className = "fa-solid fa-spinner fa-spin ms-2";
                        billDistrictsBtn.append(spinner);

                        // send the data to create the bill districts modal
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/caseloads/billDistricts.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            { 
                                // hide the current modal
                                $("#billDistrictsModal").modal("hide");

                                // create the status modal
                                let status_title = "Bill Districts Status";
                                let status_body = encodeURIComponent(this.responseText);
                                createStatusModal("refresh", status_title, status_body);
                            }
                        };
                        xmlhttp.send("period="+period+"&quarter="+quarter);
                    }

                    /** function to get the modal to export district reports */
                    function getExportDistrictReportsModal()
                    {
                        // get the search parameters
                        var period = document.getElementById("search-period").value;
                        var quarter = document.getElementById("search-quarter").value;

                        // send the data to create the bill districts modal
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/caseloads/getExportDistrictQuarterlyReportModal.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                document.getElementById("export_reports-modal-div").innerHTML = this.responseText;     
                                $("#exportDistrictReportModal").modal("show");
                            }
                        };
                        xmlhttp.send("period="+period+"&quarter="+quarter);
                    }

                    /** function to toggle the uplaod button */
                    function toggleUpload(element_id)
                    {
                        // store the element
                        var element = document.getElementById(element_id+"-btn");
                        var input_element = document.getElementById(element_id);
                        
                        // get current status of the element
                        var status = element.value;
                        
                        if (status == 1) // disable the upload button
                        {
                            element.classList.remove("btn-success");
                            element.classList.add("btn-secondary");
                            element.innerHTML = "No, do not upload to Google Drive";
                            element.value = 0;
                            input_element.value = 0;
                        }
                        else // enable the upload button
                        {
                            element.classList.remove("btn-secondary");
                            element.classList.add("btn-success");
                            element.innerHTML = "Yes, upload to Google Drive";
                            element.value = 1;
                            input_element.value = 1;
                        }
                    }

                    /** function to get the modal to export district reports */
                    function getViewDistrictReportsModal()
                    {
                        // get the search parameters
                        var period = document.getElementById("search-period").value;
                        var quarter = document.getElementById("search-quarter").value;

                        // send the data to create the bill districts modal
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/caseloads/getViewDistrictQuarterlyReportsModal.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // load in the modal
                                document.getElementById("view_reports-modal-div").innerHTML = this.responseText;     

                                // initialize the datatable
                                var externalReports = $("#viewReports").DataTable({
                                    autoWidth: false,
                                    pageLength: <?php if (isset($USER_SETTINGS["page_length"])) { echo $USER_SETTINGS["page_length"]; } else { echo 10; } ?>,
                                    lengthMenu: [ [10, 25, 50, 100, 250, -1], [10, 25, 50, 100, 250, "All"] ],
                                    dom: 'rt',
                                    language: {
                                        search: '<div class="input-group mb-1"><div class="input-group-prepend"><span class="d-flex align-items-center h-100 mx-2" id="nav-search-icon"><i class="fa fa-search" aria-hidden="true"></i></span></div>_INPUT_</div>',
                                        lengthMenu: '<div class="mx-1 mb-1">Show _MENU_ entries</div>',
                                        info: '<div class="mx-1">Showing _START_ to _END_ of _TOTAL_ entries</div>',
                                        loadingRecords: '<i class="fa-solid fa-spinner fa-spin"></i> Loading...',
                                    },
                                    rowCallback: function (row, data, index)
                                    {
                                        updatePageSelection("viewReports", false);
                                    },
                                });

                                // initialize the datatable
                                var internalReports = $("#viewInternalReports").DataTable({
                                    autoWidth: false,
                                    pageLength: <?php if (isset($USER_SETTINGS["page_length"])) { echo $USER_SETTINGS["page_length"]; } else { echo 10; } ?>,
                                    lengthMenu: [ [10, 25, 50, 100, 250, -1], [10, 25, 50, 100, 250, "All"] ],
                                    dom: 'rt',
                                    language: {
                                        search: '<div class="input-group mb-1"><div class="input-group-prepend"><span class="d-flex align-items-center h-100 mx-2" id="nav-search-icon"><i class="fa fa-search" aria-hidden="true"></i></span></div>_INPUT_</div>',
                                        lengthMenu: '<div class="mx-1 mb-1">Show _MENU_ entries</div>',
                                        info: '<div class="mx-1">Showing _START_ to _END_ of _TOTAL_ entries</div>',
                                        loadingRecords: '<i class="fa-solid fa-spinner fa-spin"></i> Loading...',
                                    },
                                    rowCallback: function (row, data, index)
                                    {
                                        updatePageSelection("viewInternalReports", false);
                                    },
                                });

                                // search table by custom search filter
                                $('#external-view_reports-search-all').keyup(function() {
                                    externalReports.search($(this).val()).draw();
                                });

                                // search table by custom search filter
                                $('#internal-view_reports-search-all').keyup(function() {
                                    internalReports.search($(this).val()).draw();
                                });

                                // display the modal
                                $("#viewDistrictReportsModal").modal("show");
                            }
                        };
                        xmlhttp.send("period="+period+"&quarter="+quarter);
                    }

                    /** function to get the modal to view a PDF report for a district */
                    function getViewDistrictReport(customer_id, period, quarter, filename, internal = 0)
                    {
                        // send the data to create the view contract modal
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/caseloads/getViewDistrictQuarterlyReportModal.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // display the view report modal; hide the view reports modal
                                document.getElementById("view_report-modal-div").innerHTML = this.responseText;     
                                $("#viewDistrictReportsModal").modal("hide");
                                $("#viewDistrictReportModal").modal("show");

                                // on view report close, show the districts viewer
                                document.getElementById("viewDistrictReportModal").addEventListener("hide.bs.modal", event => {
                                    $("#viewDistrictReportModal").modal("hide");
                                    $("#viewDistrictReportsModal").modal("show");
                                });
                            }
                        }
                        xmlhttp.send("period_id="+period+"&customer_id="+customer_id+"&quarter="+quarter+"&filename="+filename+"&internal="+internal);
                    }

                    /** function to toggle the page view */
                    function toggleView(type)
                    {
                        // hide both page views
                        document.getElementById("external-div").classList.add("d-none");
                        document.getElementById("internal-div").classList.add("d-none");
                        document.getElementById("view-external-button").classList.remove("btn-primary");
                        document.getElementById("view-internal-button").classList.remove("btn-primary");
                        document.getElementById("view-external-button").classList.add("btn-secondary");
                        document.getElementById("view-internal-button").classList.add("btn-secondary");

                        // display and select the view toggled
                        document.getElementById("view-"+type+"-button").classList.add("btn-primary");
                        document.getElementById(type+"-div").classList.remove("d-none");
                    }
                </script>

                <div class="report">
                    <!-- Page Header -->
                    <div class="table-header">
                        <div class="row d-flex justify-content-center align-items-center text-center m-0 p-2">
                            <!-- Search & Filters -->
                            <div class="col-6 col-sm-4 col-md-4 col-lg-3 col-xl-2 col-xxl-2">
                                <div class="row p-0">
                                    <!-- Period Selection -->
                                    <div class="col-9 p-0">
                                        <div class="row mb-1">
                                            <div class="input-group w-100 h-auto">
                                                <div class="input-group-prepend" id="period-icon-div">
                                                    <span class="input-group-text h-100" id="nav-search-icon">
                                                        <i class="fa-solid fa-calendar-days"></i>
                                                        <span id="period-label">Period</span>
                                                    </span>
                                                </div>
                                                <select class="form-select" id="search-period" name="search-period" onchange="displayReport();">
                                                    <?php
                                                        for ($p = 0; $p < count($periods); $p++)
                                                        {
                                                            echo "<option value='".$periods[$p]["name"]."'>".$periods[$p]["name"]."</option>";
                                                        }
                                                    ?>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="row mt-1">
                                            <div class="input-group w-100 h-auto">
                                                <div class="input-group-prepend" id="quarter-icon-div">
                                                    <span class="input-group-text h-100" id="nav-search-icon">
                                                        <i class="fa-solid fa-calendar-week"></i>
                                                        <span id="quarter-label">Quarter</span>
                                                    </span>
                                                </div>
                                                <select class="form-select" id="search-quarter" name="search-quarter" onchange="displayReport();">
                                                    <option value="1">1st Quarter (July)</option>
                                                    <option value="2">2nd Quarter (Oct.)</option>
                                                    <option value="3">3rd Quarter (Mar.)</option>
                                                    <option value="4">4th Quarter (May)</option>
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
                                                <div class="row d-flex align-items-center mx-0 mt-0 mb-2">
                                                    <div class="col-4 ps-0 pe-1">
                                                        <label for="search-district">District:</label>
                                                    </div>

                                                    <div class="col-8 ps-1 pe-0">
                                                        <select class="form-select" id="search-district" name="search-district">
                                                            <option></option>
                                                            <?php
                                                                $getDistricts = mysqli_query($conn, "SELECT DISTINCT d.id, d.name FROM customers d 
                                                                                                    JOIN cases c ON d.id=c.district_attending OR d.id=c.residency
                                                                                                    ORDER BY d.name ASC");
                                                                if (mysqli_num_rows($getDistricts) > 0)
                                                                {
                                                                    while ($district = mysqli_fetch_array($getDistricts))
                                                                    {
                                                                        // store district details locally
                                                                        $district_id = $district["id"];
                                                                        $district_name = $district["name"];

                                                                        // create the dropdown option
                                                                        echo "<option value='".$district_id."'>".$district_name."</option>";
                                                                    }
                                                                }
                                                            ?>
                                                        </select>
                                                    </div>
                                                </div>

                                                <!-- Filter By Category -->
                                                <div class="row d-flex align-items-center mx-0 mt-0 mb-2">
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

                                                                        // create the dropdown option
                                                                        echo "<option value='".$category_id."'>".$category_name."</option>";
                                                                    }
                                                                }
                                                            ?>
                                                        </select>
                                                    </div>
                                                </div>

                                                <!-- Filter By Location -->
                                                <div class="row d-flex align-items-center mx-0 mt-0 mb-2">
                                                    <div class="col-4 ps-0 pe-1">
                                                        <label for="search-location">Location:</label>
                                                    </div>

                                                    <div class="col-8 ps-1 pe-0">
                                                        <select class="form-select" id="search-location" name="location-classroom">
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

                                                <!-- Filter By Therapist -->
                                                <div class="row d-flex align-items-center mx-0 mt-0 mb-2">
                                                    <div class="col-4 ps-0 pe-1">
                                                        <label for="search-therapist">Therapist:</label>
                                                    </div>

                                                    <div class="col-8 ps-1 pe-0">
                                                        <select class="form-select" id="search-therapist" name="search-therapist">
                                                            <option></option>
                                                            <?php
                                                                $getTherapists = mysqli_query($conn, "SELECT u.lname, u.fname FROM caseloads c 
                                                                                                    JOIN caseload_categories cc ON c.category_id=cc.id
                                                                                                    JOIN users u ON c.employee_id=u.id
                                                                                                    ORDER BY u.lname ASC, u.fname ASC");
                                                                if (mysqli_num_rows($getTherapists) > 0) // services exist
                                                                {
                                                                    while ($therapist = mysqli_fetch_array($getTherapists))
                                                                    {
                                                                        echo "<option>".$therapist["lname"].", ".$therapist["fname"]."</option>";
                                                                    }
                                                                }
                                                            ?>
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

                            <!-- Page Title -->
                            <div class="col-12 col-sm-12 col-md-12 col-lg-12 col-xl-8 col-xxl-8 p-0">
                                <h1 class="report-title m-0">Quarterly Billing <span id="table-header-text"></span></h1>
                            </div>

                            <!-- Page Management Dropdown -->
                            <div class="col-6 col-sm-4 col-md-4 col-lg-3 col-xl-2 col-xxl-2 d-flex justify-content-end p-0">
                                <?php if (isset($PERMISSIONS["ADD_INVOICES"]) || isset($PERMISSIONS["VIEW_CASELOADS_ALL"]) || (isset($_SESSION["role"]) && $_SESSION["role"] == 1)) { ?>
                                    <div class="dropdown float-end">
                                        <button class="btn btn-primary dropdown-toggle" type="button" id="dropdownMenuButton1" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                                            Manage Quarterly Billing
                                        </button>
                                        <ul class="dropdown-menu p-0" aria-labelledby="dropdownMenuButton1">
                                            <?php if (isset($PERMISSIONS["ADD_INVOICES"])) { ?>
                                                <li><button class="dropdown-item quickNav-dropdown-item text-center w-100 px-3 py-2 rounded-0" type="button" onclick="getBillDistrictsModal();"><i class="fa-solid fa-dollar-sign"></i> Bill Districts</button></li>
                                            <?php }
                                            if (isset($PERMISSIONS["VIEW_CASELOADS_ALL"])) { ?>
                                                <li><button class="dropdown-item quickNav-dropdown-item text-center w-100 px-3 py-2 rounded-0" type="button" onclick="getViewDistrictReportsModal();"><i class="fa-solid fa-eye"></i> View District Reports</button></li>
                                            <?php } ?>
                                            <?php if (isset($_SESSION["role"]) && $_SESSION["role"] == 1) { // ADMINISTRATOR ONLY  ?>
                                                <li><button class="dropdown-item quickNav-dropdown-item text-center w-100 px-3 py-2 rounded-0" type="button" onclick="getExportDistrictReportsModal();"><i class="fa-solid fa-arrow-up-from-bracket"></i> Export District Report</button></li>
                                            <?php } ?>
                                        </ul>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>
                    </div>

                    <div id="report-div" class="d-none">
                        <table id="UOSQuarterlyBilling_district" class="report_table w-100">
                            <thead>
                                <tr>
                                    <th style="text-align: center !important;">District</th>
                                    <th style="text-align: center !important;">Student</th>
                                    <th style="text-align: center !important;">Location</th>
                                    <th style="text-align: center !important;">Therapist</th>
                                    <th style="text-align: center !important;">Category</th>
                                    <th style="text-align: center !important;">Days</th>
                                    <th style="text-align: center !important;">Total Units</th>
                                    <th style="text-align: center !important;">Cost</th>
                                </tr>
                            </thead>

                            <tfoot>
                                <tr>
                                    <th class="py-1" colspan="5"></th>
                                    <th class="py-1" id="district-sum-days"></th>
                                    <th class="py-1" id="district-sum-units"></th>
                                    <th class="py-1 text-end" id="district-sum-cost"></th>
                                </tr>
                            </tfoot>
                        </table>
                        <?php createTableFooterV2("UOSQuarterlyBilling_district", "BAP_Caseloads_UOSQuarterlyBilling_PageLength", $USER_SETTINGS["page_length"], true, true); ?>
                    </div>
                </div>

                <!-- Bill Districts Modal -->
                <div id="bill_districts-modal-div"></div>
                <!-- End Bill Districts Modal -->

                <!-- Export District Reports Modal -->
                <div id="export_reports-modal-div"></div>
                <!-- End Export District Reports Modal -->

                <!-- View District Reports Modal -->
                <div id="view_reports-modal-div"></div>
                <!-- End View District Reports Modal -->

                <!-- View District Report Modal -->
                <div id="view_report-modal-div"></div>
                <!-- End View District Report Modal -->

                <script>
                    // get the current active period
                    let active_period = "<?php echo $active_period_label; ?>"; 

                    // set the search filters to values we have saved in storage
                    if (sessionStorage["BAP_UOSQuarterlyBilling_Search_Period"] != "" && sessionStorage["BAP_UOSQuarterlyBilling_Search_Period"] != null && sessionStorage["BAP_UOSQuarterlyBilling_Search_Period"] != undefined) { $('#search-period').val(sessionStorage["BAP_UOSQuarterlyBilling_Search_Period"]); }
                    else { $('#search-period').val(active_period); } // no period set; default to active period 
                    if (sessionStorage["BAP_UOSQuarterlyBilling_Search_Quarter"] != "" && sessionStorage["BAP_UOSQuarterlyBilling_Search_Quarter"] != null && sessionStorage["BAP_UOSQuarterlyBilling_Search_Quarter"] != undefined) { $('#search-quarter').val(sessionStorage["BAP_UOSQuarterlyBilling_Search_Quarter"]); }
                    else { $('#search-quarter').val(""); } // no quarter set; TODO: default to active quarter 
                        
                    function displayReport()
                    {
                        // get the search parameters
                        var period = document.getElementById("search-period").value;
                        var quarter = document.getElementById("search-quarter").value;

                        if ((period != "" && period != null && period != undefined) && 
                            (quarter != "" && quarter != null && quarter != undefined)
                        ) {
                            // update the fieldset and table headers
                            document.getElementById("table-header-text").innerHTML = " - " + period + " (Q" + quarter + ")";

                            // update session storage stored search parameter
                            sessionStorage["BAP_UOSQuarterlyBilling_Search_Period"] = period;
                            sessionStorage["BAP_UOSQuarterlyBilling_Search_Quarter"] = quarter;

                            // call the funtion to build the report for the district
                            createDistrictReport(period, quarter);

                            // display the report container
                            document.getElementById("report-div").classList.remove("d-none");
                        }
                        else { createStatusModal("alert", "Loading Report Error", "Failed to load the report. You must select a period, quarter, and category to generate the report for."); }
                    }

                    /** function to create the district report */
                    function createDistrictReport(period, quarter, district_id, category)
                    {                   
                        var quarterly_billing = $("#UOSQuarterlyBilling_district").DataTable({
                            ajax: {
                                url: "ajax/caseloads/getDistrictCaseloadQuarterlyBillingReport.php",
                                type: "POST",
                                data: {
                                    period: period,
                                    quarter: quarter,
                                    category: category,
                                    district_id: district_id,
                                }
                            },
                            destroy: true,
                            autoWidth: false,
                            paging: true,
                            pageLength: <?php if (isset($USER_SETTINGS["page_length"])) { echo $USER_SETTINGS["page_length"]; } else { echo 10; } ?>,
                            lengthMenu: [ [10, 25, 50, 100, 250, -1], [10, 25, 50, 100, 250, "All"] ],
                            columns: [
                                { data: "district", orderable: true, width: "16.25%", className: "text-center" },
                                { data: "student", orderable: true, width: "15%", className: "text-center" },
                                { data: "location", orderable: true, width: "15%", className: "text-center" },
                                { data: "therapist", orderable: true, width: "16.25%", className: "text-center" },
                                { data: "category", orderable: true, width: "15%", className: "text-center" },
                                { data: "membership_days", orderable: true, width: "7.5%", className: "text-center" },
                                { data: "case_units", orderable: true, width: "7.5%", className: "text-center" },
                                { data: "student_cost", orderable: true, width: "7.5%", className: "text-end" },
                                { data: "student_cost_calc", orderable: false, visible: false },
                                { data: "category_id_filter", orderable: false, visible: false },
                                { data: "classroom_id_filter", orderable: false, visible: false },
                                { data: "district_id_filter", orderable: false, visible: false },
                            ],
                            dom: 'rt',
                            language: {
                                search: '<div class="input-group mb-1"><div class="input-group-prepend"><span class="d-flex align-items-center h-100 mx-2" id="nav-search-icon"><i class="fa fa-search" aria-hidden="true"></i></span></div>_INPUT_</div>',
                                lengthMenu: '<div class="mx-1 mb-1">Show _MENU_ entries</div>',
                                info: '<div class="mx-1">Showing _START_ to _END_ of _TOTAL_ entries</div>',
                                loadingRecords: '<i class="fa-solid fa-spinner fa-spin"></i> Loading...',
                            },
                            rowCallback: function (row, data, index)
                            {
                                // initialize page selection
                                updatePageSelection("UOSQuarterlyBilling_district");
                            },
                            drawCallback: function ()
                            {
                                var api = this.api();

                                // get the sum of all filtered districts
                                let days_sum = api.column(5, { search: "applied" }).data().sum().toFixed(0);
                                let units_sum = api.column(6, { search: "applied" }).data().sum().toFixed(0);
                                let costs_sum = api.column(8, { search: "applied" }).data().sum().toFixed(2);

                                // update the table footer
                                document.getElementById("district-sum-days").innerHTML = numberWithCommas(days_sum);
                                document.getElementById("district-sum-units").innerHTML = numberWithCommas(units_sum);
                                document.getElementById("district-sum-cost").innerHTML = "$"+numberWithCommas(costs_sum);
                            },
                        });

                        // search table by custom search filter
                        $('#search-all').keyup(function() {
                            quarterly_billing.search($(this).val()).draw();
                            sessionStorage["BAP_UOSQuarterlyBilling_Search_All"] = $(this).val();
                        });
                        $('#search-district').change(function() {
                            sessionStorage["BAP_UOSQuarterlyBilling_Search_District"] = $(this).val();
                            if ($(this).val() != "") { quarterly_billing.columns(11).search("^" + $(this).val() + "$", true, false, true).draw(); }
                            else { quarterly_billing.columns(11).search("").draw(); }
                        });
                        $('#search-therapist').change(function() {
                            sessionStorage["BAP_UOSQuarterlyBilling_Search_Therapist"] = $(this).val();
                            if ($(this).val() != "") { quarterly_billing.columns(3).search("^" + $(this).val() + "$", true, false, true).draw(); }
                            else { quarterly_billing.columns(3).search("").draw(); }
                        });
                        $('#search-category').change(function() {
                            sessionStorage["BAP_UOSQuarterlyBilling_Search_Category"] = $(this).val();
                            if ($(this).val() != "") { quarterly_billing.columns(9).search("^" + $(this).val() + "$", true, false, true).draw(); }
                            else { quarterly_billing.columns(9).search("").draw(); }
                        });
                        $('#search-location').change(function() {
                            sessionStorage["BAP_UOSQuarterlyBilling_Search_Location"] = $(this).val();
                            if ($(this).val() != "") { quarterly_billing.columns(10).search("^" + $(this).val() + "$", true, false, true).draw(); }
                            else { quarterly_billing.columns(10).search("").draw(); }
                        });

                        // function to clear search filters
                        $('#clearFilters').click(function() {
                            sessionStorage["BAP_UOSQuarterlyBilling_Search_Period"] = "";
                            sessionStorage["BAP_UOSQuarterlyBilling_Search_District"] = "";
                            sessionStorage["BAP_UOSQuarterlyBilling_Search_Therapist"] = "";
                            sessionStorage["BAP_UOSQuarterlyBilling_Search_Category"] = "";
                            sessionStorage["BAP_UOSQuarterlyBilling_Search_Location"] = "";
                            $('#search-all').val("");
                            $('#search-district').val("");
                            $('#search-therapist').val("");
                            $('#search-category').val("");
                            $('#search-location').val("");
                            quarterly_billing.search("").columns().search("").draw();
                        });

                        // redraw caseload table with current search fields
                        if ($('#search-all').val() != "") { quarterly_billing.search($('#search-all').val()).draw(); }
                        if ($('#search-district').val() != "") { quarterly_billing.columns(11).search("^" + $('#search-district').val() + "$", true, false, true).draw(); }
                        if ($('#search-therapist').val() != "") { quarterly_billing.columns(3).search("^" + $('#search-therapist').val() + "$", true, false, true).draw(); }
                        if ($('#search-category').val() != "") { quarterly_billing.columns(9).search("^" + $('#search-category').val() + "$", true, false, true).draw(); }
                        if ($('#search-location').val() != "") { quarterly_billing.columns(10).search("^" + $('#search-category').val() + "$", true, false, true).draw(); }
                    }

                    /** function to load the report on page load if parameters are already set */
                    function generateOnPageLoad()
                    {
                        // get the search parameters
                        var period = document.getElementById("search-period").value;
                        var quarter = document.getElementById("search-quarter").value;

                        if ((period != "" && period != null && period != undefined) && 
                            (quarter != "" && quarter != null && quarter != undefined)
                        ) {
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
    }
    else { goToLogin(); }

    include("footer.php"); 
?>