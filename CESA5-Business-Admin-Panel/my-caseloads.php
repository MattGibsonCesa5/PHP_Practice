<?php 
    include_once("header.php");

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1) 
    { 
        if (isset($PERMISSIONS["VIEW_CASELOADS_ASSIGNED"]))
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
                </style>

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
                                            <select class="form-select" id="search-period" name="search-period" onchange="searchCaseloads();">
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
                                <h1 class="report-title m-0">My Caseloads</h1>
                            </div>

                            <!-- Page Management Dropdown -->
                            <div class="col-6 col-sm-4 col-md-4 col-lg-3 col-xl-2 col-xxl-2 p-0"></div>
                        </div>
                    </div>

                    <div class="row report-body m-0">
                        <table id="caseloads" class="report_table w-100">
                            <thead>
                                <tr>
                                    <th class="text-center py-1 px-2" rowspan="2">Order</th>
                                    <th class="text-center py-1 px-2" rowspan="2">Caseload</th>
                                    <th class="text-center py-1 px-2" colspan="2"><span id="period-table_header-text"></span> Totals</th>
                                </tr>

                                <tr>
                                    <th class="text-center py-1 px-2"># of Active Students</th>
                                    <th class="text-center py-1 px-2">Units</th>
                                </tr>
                            </thead>
                        </table>
                        <?php createTableFooterV2("caseloads", "BAP_MyCaseloads_PageLength", $USER_SETTINGS["page_length"], true, true); ?>
                    </div>
                </div>

                <script>
                    // initialize variable to indicate if we have drawn the caseloads table
                    var drawn = 0; // assume we have not drawn the table yet

                    // get the current active period
                    let active_period = "<?php echo $active_period_label; ?>"; 

                    // set the search filters to values we have saved in storage
                    if (sessionStorage["BAP_MyCaseloads_Search_Period"] != "" && sessionStorage["BAP_MyCaseloads_Search_Period"] != null && sessionStorage["BAP_MyCaseloads_Search_Period"] != undefined) { $('#search-period').val(sessionStorage["BAP_MyCaseloads_Search_Period"]); }
                    else { $('#search-period').val(active_period); } // no period set; default to active period 

                    /** function to search for caseloads */
                    function searchCaseloads()
                    {
                        // get the value of the period we are searching
                        var period = document.getElementById("search-period").value;

                        if (period != "" && period != null && period != undefined)
                        {
                            // update the table headers
                            document.getElementById("period-table_header-text").innerHTML = period;

                            // set the fixed period and caseload id
                            document.getElementById("fixed-period").value = period;

                            // update session storage stored search parameter
                            sessionStorage["BAP_MyCaseloads_Search_Period"] = period;

                            // if we have already drawn the table, destroy existing table
                            if (drawn == 1) { $("#caseloads").DataTable().destroy(); }

                            // initialize the caseloads table
                            var caseloads = $("#caseloads").DataTable({
                                ajax: {
                                    url: "ajax/caseloads/getCaseloads.php",
                                    type: "POST",
                                    data: {
                                        period: period
                                    }
                                },
                                autoWidth: false,
                                async: false,
                                pageLength: <?php if (isset($USER_SETTINGS["page_length"])) { echo $USER_SETTINGS["page_length"]; } else { echo 10; } ?>,
                                lengthMenu: [ [10, 25, 50, 100, 250, -1], [10, 25, 50, 100, 250, "All"] ],
                                columns: [
                                    // display columns
                                    { data: "order", orderable: true, visible: false },
                                    { data: "name", orderable: true, width: "60%" },
                                    { data: "caseload_count", orderable: true, width: "20%", className: "text-center" },
                                    { data: "caseload_units", orderable: true, width: "20%", className: "text-center" },
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
                                },
                                stateSave: false,
                                rowCallback: function (row, data, index)
                                {
                                    updatePageSelection("caseloads");
                                }
                            });

                            // mark that we have drawn the table
                            drawn = 1;

                            // search table by custom search filter
                            $('#search-all').keyup(function() {
                                caseloads.search($(this).val()).draw();
                                sessionStorage["BAP_MyCaseloads_Search_All"] = $(this).val();
                            });
                        }
                    }

                    // search caseloads from the default parameters
                    searchCaseloads();
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