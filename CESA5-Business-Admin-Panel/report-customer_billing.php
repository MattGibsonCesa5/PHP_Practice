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

                                                <!-- Filter By Group -->
                                                <div class="row d-flex justify-content-between align-items-center mx-0 mt-0 mb-2">
                                                    <div class="col-4 ps-0 pe-1">
                                                        <label for="search-groups">Group:</label>
                                                    </div>

                                                    <div class="col-8 ps-1 pe-0">
                                                        <select class="form-select" id="search-groups" name="search-groups">
                                                            <option></option>
                                                            <?php
                                                                $getGroups = mysqli_query($conn, "SELECT id, name FROM `groups` ORDER BY name ASC");
                                                                if (mysqli_num_rows($getGroups) > 0) // groups exist
                                                                {
                                                                    while ($group = mysqli_fetch_array($getGroups))
                                                                    {
                                                                        echo "<option value='".$group["name"]."'>".$group["name"]."</option>";
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
                                <h1 class="report-title m-0">Customer Billing Report</h1>
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
                                    <th class="text-center py-1 px-2" colspan="2">Customer Details</th>
                                    <th class="text-center py-1 px-2" colspan="5">Costs</th>
                                </tr>

                                <tr>
                                    <th class="text-center py-1 px-2">Customer Number</th>
                                    <th class="text-center py-1 px-2">Customer Name</th>
                                    <th class="text-center py-1 px-2">Q1</th>
                                    <th class="text-center py-1 px-2">Q2</th>
                                    <th class="text-center py-1 px-2">Q3</th>
                                    <th class="text-center py-1 px-2">Q4</th>
                                    <th class="text-center py-1 px-2">Annual</th>
                                </tr>
                            </thead>

                            <tfoot>
                                <tr>
                                    <th class="text-end py-1 px-2" colspan="2">TOTAL:</th>
                                    <th class="text-end py-1 px-2" id="sum-q1"></th> <!-- Q1 Total -->
                                    <th class="text-end py-1 px-2" id="sum-q2"></th> <!-- Q2 Total -->
                                    <th class="text-end py-1 px-2" id="sum-q3"></th> <!-- Q3 Total -->
                                    <th class="text-end py-1 px-2" id="sum-q4"></th> <!-- Q4 Total -->
                                    <th class="text-end py-1 px-2" id="sum-total"></th> <!-- TOTAL -->
                                </tr>
                            </tfoot>
                        </table>
                        <?php createTableFooterV2("report_table", "BAP_Report_CustomerBilling_PageLength", $USER_SETTINGS["page_length"], true, true); ?>
                    </div>
                </div>

                <script>
                    // get the current active period
                    let active_period = "<?php echo $active_period_label; ?>"; 

                    // set the search filters to values we have saved in storage
                    if (sessionStorage["BAP_Report_CustomerBilling_Search_Period"] != "" && sessionStorage["BAP_Report_CustomerBilling_Search_Period"] != null && sessionStorage["BAP_Report_CustomerBilling_Search_Period"] != undefined) { $('#search-period').val(sessionStorage["BAP_Report_CustomerBilling_Search_Period"]); }
                    else { $('#search-period').val(active_period); } // no period set; default to active period 
                    if (sessionStorage["BAP_Report_CustomerBilling_Search_Groups"] != "" && sessionStorage["BAP_Report_CustomerBilling_Search_Groups"] != null && sessionStorage["BAP_Report_CustomerBilling_Search_Groups"] != undefined) { $('#search-groups').val(sessionStorage["BAP_Report_CustomerBilling_Search_Groups"]); }

                    /** function to generate the report */
                    function generateReport()
                    {
                        // get the period and quarter selected
                        let period = document.getElementById("search-period").value;
                        // only attempt to generate the report if both a period and project are selected
                        if (period != null && period != undefined && period != "")
                        {
                            // update session storage stored search parameter
                            sessionStorage["BAP_Report_CustomerBilling_Search_Period"] = period;

                            // initialize and get the report data
                            var report = $("#report_table").DataTable({
                                ajax: {
                                    url: "ajax/reports/getCustomerBilling.php",
                                    type: "POST",
                                    data: {
                                        period: period,
                                    },
                                    dataSrc: ""
                                },
                                destroy: true,
                                autoWidth: false,
                                paging: true,
                                pageLength: <?php if (isset($USER_SETTINGS["page_length"])) { echo $USER_SETTINGS["page_length"]; } else { echo 10; } ?>,
                                lengthMenu: [ [10, 25, 50, 100, 250, -1], [10, 25, 50, 100, 250, "All"] ],
                                columns: [
                                    { data: "id", orderable: true, className: "text-center", width: "10%" },
                                    { data: "name", orderable: true, className: "text-center", width: "27.5%" },
                                    { data: "q1", orderable: true, className: "text-center", width: "12.5%" },
                                    { data: "q2", orderable: true, className: "text-center", width: "12.5%" },
                                    { data: "q3", orderable: true, className: "text-center", width: "12.5%" },
                                    { data: "q4", orderable: true, className: "text-center", width: "12.5%" },
                                    { data: "total", orderable: true, className: "text-center", width: "12.5%" },
                                    { data: "groups_string", orderable: true, className: "text-center", visible: false },
                                    { data: "calc_q1", orderable: true, className: "text-center", visible: false },
                                    { data: "calc_q2", orderable: true, className: "text-center", visible: false },
                                    { data: "calc_q3", orderable: true, className: "text-center", visible: false },
                                    { data: "calc_q4", orderable: true, className: "text-center", visible: false },
                                    { data: "calc_total", orderable: true, className: "text-center", visible: false },
                                ],
                                order: [
                                    [ 1, "asc" ],
                                ],
                                dom: 'rt',
                                language: {
                                    search: '<div class="input-group mb-1"><div class="input-group-prepend"><span class="d-flex align-items-center h-100 mx-2" id="nav-search-icon"><i class="fa fa-search" aria-hidden="true"></i></span></div>_INPUT_</div>',
                                    lengthMenu: '<div class="mx-1 mb-1">Show _MENU_ entries</div>',
                                    info: '<div class="mx-1">Showing _START_ to _END_ of _TOTAL_ entries</div>',
                                    loadingRecords: '<i class="fa-solid fa-spinner fa-spin"></i> Loading...',
                                },
                                drawCallback: function ()
                                {
                                    var api = this.api();

                                    // get the sum of all filtered amounts
                                    let q1_sum = api.column(8, { search: "applied" }).data().sum().toFixed(2);
                                    let q2_sum = api.column(9, { search: "applied" }).data().sum().toFixed(2);
                                    let q3_sum = api.column(10, { search: "applied" }).data().sum().toFixed(2);
                                    let q4_sum = api.column(11, { search: "applied" }).data().sum().toFixed(2);
                                    let total_sum = api.column(12, { search: "applied" }).data().sum().toFixed(2);
                                    
                                    // update the table footer
                                    document.getElementById("sum-q1").innerHTML = "$"+numberWithCommas(q1_sum);
                                    document.getElementById("sum-q2").innerHTML = "$"+numberWithCommas(q2_sum);
                                    document.getElementById("sum-q3").innerHTML = "$"+numberWithCommas(q3_sum);
                                    document.getElementById("sum-q4").innerHTML = "$"+numberWithCommas(q4_sum);
                                    document.getElementById("sum-total").innerHTML = "$"+numberWithCommas(parseFloat(total_sum).toFixed(2));
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
                                            columns: [ 0, 1, 2, 3, 4, 5, 6 ]
                                        },
                                        text: "<i class='fa-solid fa-file-csv'></i>",
                                        className: "dropdown-item quickNav-dropdown-item text-center w-100 px-3 py-2 rounded-0",
                                        title: "Customer Billing Report",
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
                                            columns: [ 0, 1, 2, 3, 4, 5, 6 ]
                                        },
                                        text: "<i class='fa-solid fa-file-excel'></i>",
                                        className: "dropdown-item quickNav-dropdown-item text-center w-100 px-3 py-2 rounded-0",
                                        title: "Customer Billing Report",
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
                                            columns: [ 0, 1, 2, 3, 4, 5, 6 ]
                                        },
                                        orientation: "portrait",
                                        text: "<i class='fa-solid fa-file-pdf'></i>",
                                        className: "dropdown-item quickNav-dropdown-item text-center w-100 px-3 py-2 rounded-0",
                                        title: "Customer Billing Report",
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
                                            columns: [ 0, 1, 2, 3, 4, 5, 6 ]
                                        },
                                        orientation: "portrait",
                                        text: "<i class='fa-solid fa-print'></i>",
                                        className: "dropdown-item quickNav-dropdown-item text-center w-100 px-3 py-2 rounded-0",
                                        title: "Customer Billing Report",
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

                            // search table by custom search filter
                            $('#search-all').keyup(function() {
                                report.search($(this).val()).draw();
                                sessionStorage["BAP_Report_CustomerBilling_Search_All"] = $(this).val();
                            });

                            // search the hidden "Groups" column
                            $('#search-groups').change(function() {
                                report.columns(7).search($(this).val()).draw();
                                sessionStorage["BAP_Report_CustomerBilling_Search_Groups"] = $(this).val();
                            });

                            // function to clear search filters
                            $('#clearFilters').click(function() {
                                sessionStorage["BAP_Report_CustomerBilling_Search_All"] = "";
                                sessionStorage["BAP_Report_CustomerBilling_Search_Groups"] = "";
                                $('#search-all').val("");
                                $('#search-groups').val("");
                                report.search("").columns().search("").draw();
                            });

                            // redraw table based on currently set search parameters
                            if ($('#search-all').val() != "") { report.search($('#search-all').val()).draw(); }
                            if ($('#search-groups').val() != "") { report.columns(7).search($('#search-groups').val()).draw(); }
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