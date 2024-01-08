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
                                <h1 class="report-title m-0">Consecutive Years Of Experience Report</h1>
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
                                    <th class="text-center py-1 px-2">ID</th>
                                    <th class="text-center py-1 px-2">Name</th>
                                    <th class="text-center py-1 px-2">Title</th>
                                    <th class="text-center py-1 px-2">Hired</th>
                                    <th class="text-center py-1 px-2">Adjustment</th>
                                    <th class="text-center py-1 px-2">Consecutive Years Of Experience</th>
                                </tr>
                            </thead>
                        </table>
                        <?php createTableFooterV2("report_table", "BAP_Report_ConsecutiveYearsOfExperience_PageLength", $USER_SETTINGS["page_length"], true, true); ?>
                    </div>
                </div>

                <script>
                    // initialize variable to state if we've drawn the table or not
                    var drawn = 0; // assume we have not drawn the table (0)

                    /** function to generate the report */
                    function generateReport()
                    {
                        // if we have already drawn the table, destroy existing table
                        if (drawn == 1) { $("#report_table").DataTable().destroy(); }

                        // initialize and get the report data
                        var report = $("#report_table").DataTable({
                            ajax: {
                                url: "ajax/reports/getConsecutiveYOEReport.php",
                                type: "POST",
                            },
                            autoWidth: false,
                            paging: true,
                            pageLength: <?php if (isset($USER_SETTINGS["page_length"])) { echo $USER_SETTINGS["page_length"]; } else { echo 10; } ?>,
                            lengthMenu: [ [10, 25, 50, 100, 250, -1], [10, 25, 50, 100, 250, "All"] ],
                            columns: [
                                { data: "id", orderable: true, className: "text-center", width: "10%" },
                                { data: "name", orderable: true, className: "text-center", width: "25%" },
                                { data: "title", orderable: true, className: "text-center", width: "25%" },
                                { data: "hire", orderable: true, className: "text-center", width: "15%" },
                                { data: "adj", orderable: true, className: "text-center", width: "10%" },
                                { data: "yoe", orderable: true, className: "text-center", width: "15%" },
                            ],
                            order: [
                                [ 3, "desc" ],
                                [ 1, "asc" ],
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
                                        columns: [ 0, 1, 2, 3, 4, 5 ]
                                    },
                                    text: "<i class='fa-solid fa-file-csv'></i>",
                                    className: "dropdown-item quickNav-dropdown-item text-center w-100 px-3 py-2 rounded-0",
                                    title: "Consecutive Years Of Experience Report",
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
                                        columns: [ 0, 1, 2, 3, 4, 5 ]
                                    },
                                    text: "<i class='fa-solid fa-file-excel'></i>",
                                    className: "dropdown-item quickNav-dropdown-item text-center w-100 px-3 py-2 rounded-0",
                                    title: "Consecutive Years Of Experience Report",
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
                                        columns: [ 0, 1, 2, 3, 4, 5 ]
                                    },
                                    orientation: "portrait",
                                    text: "<i class='fa-solid fa-file-pdf'></i>",
                                    className: "dropdown-item quickNav-dropdown-item text-center w-100 px-3 py-2 rounded-0",
                                    title: "Consecutive Years Of Experience Report",
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
                                        columns: [ 0, 1, 2, 3, 4, 5 ]
                                    },
                                    orientation: "portrait",
                                    text: "<i class='fa-solid fa-print'></i>",
                                    className: "dropdown-item quickNav-dropdown-item text-center w-100 px-3 py-2 rounded-0",
                                    title: "Consecutive Years Of Experience Report",
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
                            sessionStorage["BAP_Report_ConsecutiveYearsOfExperience_Search_All"] = $(this).val();
                        });

                        // function to clear search filters
                        $('#clearFilters').click(function() {
                            sessionStorage["BAP_Report_ConsecutiveYearsOfExperience_Search_All"] = "";
                            $('#search-all').val("");
                            report.search("").columns().search("").draw();
                        });
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