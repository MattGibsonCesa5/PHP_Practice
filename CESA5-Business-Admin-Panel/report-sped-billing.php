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

                    #quarter-icon-div:hover #quarter-label
                    {
                        display: inline;
                        color: #000000;
                        transform: translate(4px, 00%);
                    }

                    #quarter-label
                    {
                        display: none;
                        color: #000000;
                        transition: 1s;
                    }
                </style>

                <div class="report">
                    <!-- Page Header -->
                    <div class="table-header p-0">
                        <div class="row d-flex justify-content-center align-items-center text-center py-2 px-3 m-0">
                            <!-- Period & Filters-->
                            <div class="col-6 col-sm-4 col-md-4 col-lg-3 col-xl-2 col-xxl-2 p-0">
                                <div class="row p-0">
                                    <!-- Period Selection -->
                                    <div class="col-9 p-0">
                                        <div class="row mb-1">
                                            <div class="input-group h-auto">
                                                <div class="input-group-prepend" id="period-icon-div">
                                                    <span class="input-group-text h-100" id="nav-search-icon">
                                                        <i class="fa-solid fa-calendar-days"></i>
                                                        <span id="period-label">Period</span>
                                                    </span>
                                                </div>
                                                <input id="fixed-period" type="hidden" value="" aria-hidden="true">
                                                <select class="form-select" id="search-period" name="search-period" onchange="generateReport();">
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
                                            <div class="input-group h-auto">
                                                <div class="input-group-prepend" id="quarter-icon-div">
                                                    <span class="input-group-text h-100" id="nav-search-icon">
                                                        <i class="fa-solid fa-calendar-week"></i>
                                                        <span id="quarter-label">Quarter</span>
                                                    </span>
                                                </div>
                                                <select class="form-select" id="search-quarter" name="search-quarter" placeholder="Search quarters" aria-describedby="nav-search-icon" onchange="generateReport();">
                                                    <option value="1" selected>Q1</option>
                                                    <option value="2">Q2</option>
                                                    <option value="3">Q3</option>
                                                    <option value="4">Q4</option>
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
                                <h1 class="report-title m-0">SPED Quarterly Billing Verification</h1>
                            </div>

                            <!-- Page Management Dropdown -->
                            <div class="col-6 col-sm-4 col-md-4 col-lg-3 col-xl-2 col-xxl-2 p-0"></div>
                        </div>
                    </div>

                    <div id="report-div" class="d-none">
                        <div id="SPEDBillingVerification-container"></div>
                    </div>
                </div>

                <script>
                    // initialize variable to state if we've drawn the table or not
                    var drawn = 0; // assume we have not drawn the table (0)

                    // get the current active period
                    let active_period = "<?php echo $active_period_label; ?>"; 

                    // set the search filters to values we have saved in storage
                    if (sessionStorage["BAP_Report_SPEDBillingVerification_Search_Period"] != "" && sessionStorage["BAP_Report_SPEDBillingVerification_Search_Period"] != null && sessionStorage["BAP_Report_SPEDBillingVerification_Search_Period"] != undefined) { $('#search-period').val(sessionStorage["BAP_Report_SPEDBillingVerification_Search_Period"]); }
                    else { $('#search-period').val(active_period); } // no period set; default to active period 
                    if (sessionStorage["BAP_Report_SPEDBillingVerification_Search_Quarter"] != "" && sessionStorage["BAP_Report_SPEDBillingVerification_Search_Quarter"] != null && sessionStorage["BAP_Report_SPEDBillingVerification_Search_Quarter"] != undefined) { $('#search-quarter').val(sessionStorage["BAP_Report_SPEDBillingVerification_Search_Quarter"]); }
                    else { $('#search-quarter').val(""); } // no quarter set; TODO: default to active quarter 

                    /** function to generate the report */
                    function generateReport()
                    {
                        // get the period and quarter selected
                        let period = document.getElementById("search-period").value;
                        let quarter = document.getElementById("search-quarter").value;

                        // only attempt to generate the report if both a period and project are selected
                        if ((period != null && period != undefined && period != "") && (quarter != null && quarter != undefined && quarter != ""))
                        {
                            // update session storage stored search parameter
                            sessionStorage["BAP_Report_SPEDBillingVerification_Search_Period"] = period;
                            sessionStorage["BAP_Report_SPEDBillingVerification_Search_Quarter"] = quarter;

                            // if we have already drawn the table, destroy existing table
                            if (drawn == 1) { $("#SPEDBillingVerification").DataTable().destroy(); }

                            // create the table container                      
                            var xmlhttp = new XMLHttpRequest();
                            xmlhttp.open("POST", "ajax/reports/getSPEDBillingVerificationReportTable.php", true);
                            xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                            xmlhttp.send("period="+period+"&quarter="+quarter);
                            xmlhttp.onreadystatechange = function() 
                            {
                                if (this.readyState == 4 && this.status == 200)
                                {
                                    // set the report
                                    document.getElementById("SPEDBillingVerification-container").innerHTML = this.responseText;

                                    // initialize and get the report data
                                    var report = $("#SPEDBillingVerification").DataTable({
                                        ajax: {
                                            url: "ajax/reports/getSPEDBillingVerificationReportData.php",
                                            type: "POST",
                                            data: {
                                                period: period,
                                                quarter: quarter,
                                            }
                                        },
                                        autoWidth: false,
                                        paging: true,
                                        pageLength: <?php if (isset($USER_SETTINGS["page_length"])) { echo $USER_SETTINGS["page_length"]; } else { echo 10; } ?>,
                                        lengthMenu: [ [10, 25, 50, 100, 250, -1], [10, 25, 50, 100, 250, "All"] ],
                                        columns: [
                                            { data: "service_id", orderable: true, className: "text-center" },
                                            { data: "service_name", orderable: true, className: "text-center" },
                                            { data: "customer_id", orderable: true, className: "text-center" },
                                            { data: "customer_name", orderable: true, className: "text-center" },
                                            { data: "units_billed", orderable: true, className: "text-center" },
                                            { data: "units_expected", orderable: true, className: "text-center" },
                                            { data: "units_difference", orderable: true, className: "text-center" },
                                            { data: "quarter_billed", orderable: true, className: "text-end" },
                                            { data: "quarter_expected", orderable: true, className: "text-end" },
                                            { data: "quarter_difference", orderable: true, className: "text-end" },
                                            { data: "annual_billed", orderable: true, className: "text-end" },
                                            { data: "annual_expected", orderable: true, className: "text-end" },
                                            { data: "annual_difference", orderable: true, className: "text-end" },
                                            { data: "calc_units_difference", orderable: true, visible: false }, 
                                            { data: "calc_quarter_difference", orderable: true, visible: false }, 
                                            { data: "calc_annual_difference", orderable: true, visible: false }, 
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
                                            updatePageSelection("SPEDBillingVerification");

                                            // check units difference
                                            if (parseFloat(data["calc_units_difference"]) > 0) { $("td:eq(6)", row).addClass("verified-box-fail"); }
                                            else if (parseFloat(data["calc_units_difference"]) < 0) { $("td:eq(6)", row).addClass("verified-box-fail-over"); }
                                            else { $("td:eq(6)", row).addClass("verified-box-pass"); }

                                            // check quarterly cost difference
                                            if (parseFloat(data["calc_quarter_difference"]) > 0) { $("td:eq(9)", row).addClass("verified-box-fail"); }
                                            else if (parseFloat(data["calc_quarter_difference"]) < 0) { $("td:eq(9)", row).addClass("verified-box-fail-over"); }
                                            else { $("td:eq(9)", row).addClass("verified-box-pass"); }

                                            // check annual cost difference
                                            if (parseFloat(data["calc_annual_difference"]) > 0) { $("td:eq(12)", row).addClass("verified-box-fail"); }
                                            else if (parseFloat(data["calc_annual_difference"]) < 0) { $("td:eq(12)", row).addClass("verified-box-fail-over"); }
                                            else { $("td:eq(12)", row).addClass("verified-box-pass"); }
                                        },
                                    });

                                    // display the report container
                                    document.getElementById("report-div").classList.remove("d-none");
                                }
                            }
                        }
                    }

                    // attempt generte the report based on current parameters
                    generateReport();
                </script>
            <?php

            // disconnect from the database
            mysqli_close($conn);
        }
    }
?>