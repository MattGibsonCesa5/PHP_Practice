<?php 
    include_once("header.php");

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1) 
    { 
        if ($_SESSION["role"] == 1)
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
                                            <div class="input-group h-auto">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text h-100" id="nav-search-icon"><i class="fa-solid fa-calendar-days"></i></span>
                                                </div>
                                                <input id="fixed-period" type="hidden" value="" aria-hidden="true">
                                                <select class="form-select" id="search-period" name="search-period" onchange="searchCodes();">
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
                                    <h2 class="m-0">Codes</h2>
                                </div>

                                <!-- Page Management Dropdown -->
                                <div class="col-6 col-sm-4 col-md-4 col-lg-3 col-xl-2 col-xxl-2 p-0"></div>
                            </div>
                        </div>

                        <div id="codes-table-container" class="p-0">
                            <table id="codes" class="report_table w-100">
                                <thead>
                                    <tr>
                                        <th class="text-center py-1 px-2">Category</th>
                                        <th class="text-center py-1 px-2">Fund</th>
                                        <th class="text-center py-1 px-2">Location</th>
                                        <th class="text-center py-1 px-2">Source/Object</th>
                                        <th class="text-center py-1 px-2">Function</th>
                                        <th class="text-center py-1 px-2">Project</th>
                                        <th class="text-center py-1 px-2">Total</th>
                                    </tr>
                                </thead>

                                <tfoot>
                                    <tr>
                                        <th class="py-1 px-2" style="text-align: right !important;" colspan="6">TOTALS:</th>
                                        <th class="text-center py-1 px-2" id="sum"></th> <!-- budgeted employees sum -->
                                    </tr>
                                </tfoot>
                            </table>
                            <?php createTableFooterV2("codes", "BAP_ManageProjects_PageLength", $USER_SETTINGS["page_length"], true, true); ?>
                        </div>
                    </div>
                </div>

                <script>
                    // get the current active period
                    let active_period = "<?php echo $active_period_label; ?>"; 

                    // set page length to prior saved state
                    let saved_page_length = sessionStorage["BAP_ManageProjects_PageLength"];
                    if (saved_page_length != "" && saved_page_length != null && saved_page_length != undefined)
                    {
                        $("#projects-DT_PageLength").val(sessionStorage["BAP_ManageProjects_PageLength"]);
                    }

                    // set the search filters to values we have saved in storage
                    if (sessionStorage["BAP_ManageProjects_Search_Period"] != "" && sessionStorage["BAP_ManageProjects_Search_Period"] != null && sessionStorage["BAP_ManageProjects_Search_Period"] != undefined) { $('#search-period').val(sessionStorage["BAP_ManageProjects_Search_Period"]); }
                    else { $('#search-period').val(active_period); } // no period set; default to active period 
                    $('#search-all').val(sessionStorage["BAP_ManageProjects_Search_All"]);

                    /** function to generate the projects table based on the period selected */
                    function searchCodes()
                    {
                        // get the value of the period we are searching
                        var period = document.getElementById("search-period").value;

                        if (period != "" && period != null && period != undefined)
                        {
                            // set the period as fixed
                            document.getElementById("fixed-period").value = period;

                            // update session storage stored search parameter
                            sessionStorage["BAP_ManageProjects_Search_Period"] = period;

                            // initialize the projects table
                            var codes = $("#codes").DataTable({
                                ajax: {
                                    url: "ajax/projects/getCodes.php",
                                    type: "POST",
                                    data: {
                                        period: period
                                    },
                                    async: false,
                                },
                                destroy: true,
                                autoWidth: false,
                                processing: true,
                                pageLength: <?php if (isset($USER_SETTINGS["page_length"])) { echo $USER_SETTINGS["page_length"]; } else { echo 10; } ?>,
                                lengthMenu: [ [10, 25, 50, 100, 250, -1], [10, 25, 50, 100, 250, "All"] ],
                                columns: [
                                    { data: "category", orderable: true, width: "25%", className: "text-center" },
                                    { data: "fund", orderable: true, width: "12.5%", className: "text-center" },
                                    { data: "loc", orderable: true, width: "12.5%", className: "text-center" },
                                    { data: "obj", orderable: true, width: "12.5%", className: "text-center" },
                                    { data: "func", orderable: true, width: "12.5%", className: "text-center" },
                                    { data: "proj", orderable: true, width: "12.5%", className: "text-center" },
                                    { data: "total", orderable: true, width: "12.5%", className: "text-center" },
                                ],
                                order: [
                                    [0, "asc"],
                                ],
                                dom: 'rt',
                                language: {
                                    search: '<div class="input-group mb-1"><div class="input-group-prepend"><span class="d-flex align-items-center h-100 mx-2" id="nav-search-icon"><i class="fa fa-search" aria-hidden="true"></i></span></div>_INPUT_</div>',
                                    lengthMenu: '<div class="mx-1 mb-1">Show _MENU_ entries</div>',
                                    info: '<div class="mx-1">Showing _START_ to _END_ of _TOTAL_ entries</div>'
                                },
                                rowCallback: function (row, data, index)
                                {
                                    // initialize page selection
                                    updatePageSelection("codes");
                                },
                                drawCallback: function ()
                                {
                                    var api = this.api();

                                    // get the sums
                                    let total = api.column(6, { search: "applied" }).data().sum().toFixed(2);

                                    // update the table footer
                                    document.getElementById("sum").innerHTML = "$"+numberWithCommas(total);
                                },
                            });

                            // search table by custom search filter
                            $('#search-all').keyup(function() {
                                codes.search($(this).val()).draw();
                                sessionStorage["BAP_ManageProjects_Search_All"] = $(this).val();
                            });

                            // function to clear search filters
                            $('#clearFilters').click(function() {
                                sessionStorage["BAP_ManageProjects_Search_All"] = "";
                                $('#search-all').val("");
                                codes.search("").columns().search("").draw();
                            });
                        }
                    }

                    // search projects from the default parameters
                    searchCodes();
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