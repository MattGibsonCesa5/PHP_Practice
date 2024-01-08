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
                    .choices
                    {
                        width: 100% !important;
                    }

                    .choices__list--multiple .choices__item 
                    {
                        background-color: #00376d !important;
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
                                        <div class="input-group h-auto">
                                            <div class="input-group-prepend" id="period-icon-div">
                                                <span class="input-group-text h-100" id="nav-search-icon">
                                                    <i class="fa-solid fa-calendar-days"></i>
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

                                                <!-- Filter By Number Of Pays -->
                                                <div class="row d-flex justify-content-between align-items-center mx-0 mt-0 mb-2">
                                                    <div class="col-4 ps-0 pe-1">
                                                        <label for="search-num_of_pays"># of Pays:</label>
                                                    </div>

                                                    <div class="col-8 ps-1 pe-0">
                                                        <select id="search-num_of_pays" name="search-num_of_pays" multiple>
                                                            <option></option>
                                                            <?php
                                                                $getNumberOfPays = mysqli_query($conn, "SELECT DISTINCT number_of_pays FROM employee_compensation ORDER BY number_of_pays ASC");
                                                                if (mysqli_num_rows($getNumberOfPays) > 0)
                                                                {
                                                                    while ($result = mysqli_fetch_array($getNumberOfPays))
                                                                    {
                                                                        echo "<option>".$result["number_of_pays"]."</option>";
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
                                <h1 class="report-title m-0">Payroll Report</h1>
                            </div>

                            <!-- Page Management Dropdown -->
                            <div class="col-6 col-sm-4 col-md-4 col-lg-3 col-xl-2 col-xxl-2 p-0">
                                <button class="btn btn-primary float-end mx-1 dropdown-toggle" id="exportsMenu" type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                                    <i class="fa-solid fa-cloud-arrow-down"></i>
                                </button>
                                <ul class="quickNav-dropdown dropdown-menu p-0" aria-labelledby="exportsMenu" style="min-width: 32px !important;">
                                    <li id="csv-export-div" style="font-size: 24px; text-align: center !important; width: 100% !important;"></li>
                                    <li id="xlsx-export-div" style="font-size: 24px;"></li>
                                    <li id="pdf-export-div" style="font-size: 24px;"></li>
                                    <li id="print-export-div" style="font-size: 24px;"></li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="row report-body m-0">
                        <table id="report_table" class="report_table w-100">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Yearly Salary</th>
                                    <th>Contract Days</th>
                                    <th>Number Of Pays</th>
                                    <th>Per Pay Gross</th>
                                    <th>Status</th>
                                    <th>Per Pay Gross</th>
                                    <th>ID</th>
                                </tr>
                            </thead>
                        </table>
                        <?php createTableFooterV2("report_table", "BAP_Report_Payroll_PageLength", $USER_SETTINGS["page_length"], true, true); ?>
                    </div>
                </div>

                <script>
                    // intialize number of pays filter
                    var numOfPays = new Choices('#search-num_of_pays', 
                        { 
                            removeItemButton: true,
                            allowHTML: true,
                        }
                    );

                    // initialize variable to state if we've drawn the table or not
                    var drawn = 0; // assume we have not drawn the table (0)

                    // get the current active period
                    let active_period = "<?php echo $active_period_label; ?>"; 

                    // set the search filters to values we have saved in storage
                    if (sessionStorage["BAP_Report_Payroll_Search_Period"] != "" && sessionStorage["BAP_Report_Payroll_Search_Period"] != null && sessionStorage["BAP_Report_Payroll_Search_Period"] != undefined) { $('#search-period').val(sessionStorage["BAP_Report_Payroll_Search_Period"]); }
                    else { $('#search-period').val(active_period); } // no period set; default to active period 

                    /** function to generate the report */
                    function generateReport()
                    {
                        // get the period and quarter selected
                        let period = document.getElementById("search-period").value;

                        // only attempt to generate the report if both a period and project are selected
                        if (period != null && period != undefined && period != "")
                        {
                            // update session storage stored search parameter
                            sessionStorage["BAP_Report_Payroll_Search_Period"] = period;

                            // if we have already drawn the table, destroy existing table
                            if (drawn == 1) { $("#report_table").DataTable().destroy(); }

                            // initialize and get the report data
                            var report = $("#report_table").DataTable({
                                ajax: {
                                    url: "ajax/reports/getPayrollReport.php",
                                    type: "POST",
                                    data: {
                                        period: period,
                                    }
                                },
                                autoWidth: false,
                                paging: true,
                                pageLength: <?php if (isset($USER_SETTINGS["page_length"])) { echo $USER_SETTINGS["page_length"]; } else { echo 10; } ?>,
                                lengthMenu: [ [10, 25, 50, 100, 250, -1], [10, 25, 50, 100, 250, "All"] ],
                                columns: [
                                    { data: "id", orderable: true, className: "text-center", width: "15%" },
                                    { data: "name", orderable: true, className: "text-center", width: "30%" },
                                    { data: "salary", orderable: true, className: "text-center", width: "15%" },
                                    { data: "days", orderable: true, className: "text-center", width: "10%"},
                                    { data: "num_of_pays", orderable: true, className: "text-center", width: "15%" },
                                    { data: "per_pay_gross", orderable: true, className: "text-center", width: "15%" },
                                    { data: "status", orderable: true, visible: false },
                                    { data: "calc_per_pay_gross", orderable: true, visible: false },
                                    { data: "export_id", orderable: false, visible: false, }
                                ],
                                order: [
                                    [ 4, "desc" ],
                                    [ 6, "asc" ],
                                    [ 1, "asc" ]
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
                                    updatePageSelection("report_table");
                                },
                            });

                            // create the export buttons
                            new $.fn.dataTable.Buttons(report, {
                                buttons: [
                                    // CSV BUTTON
                                    {
                                        extend: "csv",
                                        exportOptions: {
                                            columns: [ 8, 1, 6, 2, 3, 4, 5 ]
                                        },
                                        text: "<i class='fa-solid fa-file-csv'></i>",
                                        className: "dropdown-item quickNav-dropdown-item text-center w-100 px-3 py-2 rounded-0",
                                        title: "Payroll Report",
                                        init: function(api, node, config) {
                                            // remove default button classes
                                            $(node).removeClass('dt-button');
                                            $(node).removeClass('buttons-csv');
                                            $(node).removeClass('buttons-html5');
                                        }
                                    },
                                ]
                            });
                            new $.fn.dataTable.Buttons(report, {
                                buttons: [
                                    // EXCEL BUTTON
                                    {
                                        extend: "excel",
                                        exportOptions: {
                                            columns: [ 8, 1, 6, 2, 3, 4, 5 ]
                                        },
                                        text: "<i class='fa-solid fa-file-excel'></i>",
                                        className: "dropdown-item quickNav-dropdown-item text-center w-100 px-3 py-2 rounded-0",
                                        title: "Payroll Report",
                                        init: function(api, node, config) {
                                            // remove default button classes
                                            $(node).removeClass('dt-button');
                                            $(node).removeClass('buttons-excel');
                                            $(node).removeClass('buttons-html5');
                                        }
                                    },
                                ]
                            });
                            new $.fn.dataTable.Buttons(report, {
                                buttons: [
                                    // PDF BUTTON
                                    {
                                        extend: "pdf",
                                        exportOptions: {
                                            columns: [ 8, 1, 6, 2, 3, 4, 5 ]
                                        },
                                        orientation: "portrait",
                                        text: "<i class='fa-solid fa-file-pdf'></i>",
                                        className: "dropdown-item quickNav-dropdown-item text-center w-100 px-3 py-2 rounded-0",
                                        title: "Payroll Report",
                                        init: function(api, node, config) {
                                            // remove default button classes
                                            $(node).removeClass('dt-button');
                                            $(node).removeClass('buttons-excel');
                                            $(node).removeClass('buttons-html5');
                                        }
                                    },
                                ]
                            });
                            new $.fn.dataTable.Buttons(report, {
                                buttons: [
                                    // PRINT BUTTON
                                    {
                                        extend: "print",
                                        exportOptions: {
                                            columns: [ 8, 1, 6, 2, 3, 4, 5 ]
                                        },
                                        orientation: "portrait",
                                        text: "<i class='fa-solid fa-print'></i>",
                                        className: "dropdown-item quickNav-dropdown-item text-center w-100 px-3 py-2 rounded-0",
                                        title: "Payroll Report",
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
                            report.buttons(0, null).container().appendTo("#csv-export-div");
                            report.buttons(1, null).container().appendTo("#xlsx-export-div");
                            report.buttons(2, null).container().appendTo("#pdf-export-div");
                            report.buttons(3, null).container().appendTo("#print-export-div");

                            // mark that we have drawn the table
                            drawn = 1; 

                            // search table by custom search filter
                            $('#search-all').keyup(function() {
                                report.search($(this).val()).draw();
                                sessionStorage["BAP_Report_Payroll_Search_All"] = $(this).val();
                            });
                            
                            // search table by employee status
                            $('#search-status').change(function() {
                                sessionStorage["BAP_Report_Payroll_Search_Status"] = $(this).val();
                                if ($(this).val() != "") { report.columns(6).search("^" + $(this).val() + "$", true, false, true).draw(); }
                                else { report.columns(6).search("").draw(); }
                            });

                            // search table by employee status
                            $('#search-num_of_pays').change(function() {
                                sessionStorage["BAP_Report_Payroll_Search_NumOfPays"] = $(this).val();
                                if ($(this).val().length > 0) { report.columns(4).search($(this).val().join("|"), true, false, true).draw(); }
                                else { report.columns(4).search("").draw(); }
                            });

                            // function to clear search filters
                            $('#clearFilters').click(function() {
                                sessionStorage["BAP_Report_Payroll_Search_All"] = "";
                                sessionStorage["BAP_Report_Payroll_Search_Status"] = "";
                                sessionStorage["BAP_Report_Payroll_Search_NumOfPays"] = "";
                                $('#search-all').val("");
                                $('#search-status').val("");
                                $('#search-num_of_pays').val("");
                                report.search("").columns().search("").draw();
                            });

                            // redraw table based on currently set search parameters
                            if ($('#search-all').val() != "") { report.search($('#search-all').val()).draw(); }
                            if ($('#search-status').val() != "") { report.columns(6).search("^" + $('#search-status').val() + "$", true, false, true).draw(); }
                            if ($('#search-num_of_pays').val() != "") { report.columns(4).search("^" + $('#search-num_of_pays').val() + "$", true, false, true).draw(); }
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