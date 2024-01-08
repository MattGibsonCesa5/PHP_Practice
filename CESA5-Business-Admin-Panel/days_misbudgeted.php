<?php 
    include_once("header.php");

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1) 
    { 
        if (isset($PERMISSIONS["VIEW_REPORT_MISBUDGETED_EMPLOYEES_ALL"]) || isset($PERMISSIONS["VIEW_REPORT_MISBUDGETED_EMPLOYEES_ASSIGNED"]))
        {
            // connect to the database
            $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

            // iniitalize variable to store how many departments the director has
            $director_departments_count = 0;
            $director_departments_count = getDirectorDepartmentsCount($conn, $_SESSION["id"]);

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

                                                <!-- Filter By Department -->
                                                <div class="row d-flex justify-content-between align-items-center mx-0 mt-0 mb-2">
                                                    <div class="col-4 ps-0 pe-1">
                                                        <label for="search-dept">Department:</label>
                                                    </div>

                                                    <div class="col-8 ps-1 pe-0">
                                                        <select class="form-select" id="search-dept" name="search-dept">
                                                            <option></option>
                                                            <option>No primary department assigned</option>
                                                            <?php
                                                                if (isset($PERMISSIONS["VIEW_PROJECT_BUDGETS_ALL"]))
                                                                { 
                                                                    $getDepts = mysqli_query($conn, "SELECT id, name FROM departments ORDER BY name ASC");
                                                                    if (mysqli_num_rows($getDepts) > 0) // departments found
                                                                    {
                                                                        while ($dept = mysqli_fetch_array($getDepts))
                                                                        {
                                                                            echo "<option>".$dept["name"]."</option>";
                                                                        }
                                                                    }
                                                                }
                                                                else if (isset($PERMISSIONS["VIEW_PROJECT_BUDGETS_ASSIGNED"])) // director's department list
                                                                {
                                                                    $getDepts = mysqli_prepare($conn, "SELECT id, name FROM departments WHERE director_id=? OR secondary_director_id=? ORDER BY name ASC");
                                                                    mysqli_stmt_bind_param($getDepts, "ii", $_SESSION["id"], $_SESSION["id"]);
                                                                    if (mysqli_stmt_execute($getDepts))
                                                                    {
                                                                        $getDeptsResults = mysqli_stmt_get_result($getDepts);
                                                                        if (mysqli_num_rows($getDeptsResults) > 0) // departments found; populate list
                                                                        {
                                                                            while ($dept = mysqli_fetch_array($getDeptsResults))
                                                                            {
                                                                                echo "<option>".$dept["name"]."</option>";
                                                                            }
                                                                        }
                                                                    }
                                                                }
                                                            ?>
                                                        </select>
                                                    </div>
                                                </div>

                                                <!-- Filter By Role -->
                                                <div class="row d-flex justify-content-between align-items-center mx-0 mt-0 mb-2">
                                                    <div class="col-4 ps-0 pe-1">
                                                        <label for="search-project">Project:</label>
                                                    </div>

                                                    <div class="col-8 ps-1 pe-0">
                                                        <select class="form-select" id="search-project" name="search-project">
                                                            <option></option>
                                                            <?php
                                                                if (isset($PERMISSIONS["VIEW_PROJECT_BUDGETS_ALL"])) // admin and maintenance accounts projects list
                                                                { 
                                                                    $getProjects = mysqli_query($conn, "SELECT code, name FROM projects ORDER BY code ASC");
                                                                    if (mysqli_num_rows($getProjects) > 0) // departments found
                                                                    {
                                                                        while ($proj = mysqli_fetch_array($getProjects))
                                                                        {
                                                                            echo "<option value='".$proj["code"]."'>".$proj["code"]." - ".$proj["name"]."</option>";
                                                                        }
                                                                    }
                                                                }
                                                                else if (isset($PERMISSIONS["VIEW_PROJECT_BUDGETS_ASSIGNED"])) // director's projects list
                                                                {
                                                                    $getProjects = mysqli_prepare($conn, "SELECT p.code, p.name FROM projects p
                                                                                                        JOIN departments d ON p.department_id=d.id 
                                                                                                        WHERE director_id=? OR secondary_director_id=? ORDER BY code ASC");
                                                                    mysqli_stmt_bind_param($getProjects, "ii", $_SESSION["id"], $_SESSION["id"]);
                                                                    if (mysqli_stmt_execute($getProjects))
                                                                    {
                                                                        $getProjectsResults = mysqli_stmt_get_result($getProjects);
                                                                        if (mysqli_num_rows($getProjectsResults) > 0) // departments found; populate list
                                                                        {
                                                                            while ($proj = mysqli_fetch_array($getProjectsResults))
                                                                            {
                                                                                echo "<option value='".$proj["code"]."'>".$proj["code"]." - ".$proj["name"]."</option>";
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
                                <h1 class="report-title m-0">Misbudgeted Employees</h1>
                                <p class="report-description m-0">
                                    This report displays a list of all employees who are budgeted to work more or less days than their contract requires within the current active period.
                                    An employee will be displayed in this report multiple times if they are budgeted to multiple projects. We'll display the amount of days they are budgeted for each project,
                                    their total combined budgeted days, as well as their total contract days. The "Days Off" column indicates the difference between the total budgeted days and total contract days.
                                </p>
                            </div>

                            <!-- Page Management Dropdown -->
                            <div class="col-6 col-sm-4 col-md-4 col-lg-3 col-xl-2 col-xxl-2 p-0">
                                <span class="d-flex justify-content-end" id="report-buttons"></span>
                            </div>
                        </div>
                    </div>

                    <div class="row report-body d-none m-0" id="report-table-div">
                        <table id="report_table" class="report_table w-100">
                            <thead>
                                <tr>
                                    <th class="text-center py-1 px-2" colspan="3">Employee</th>
                                    <th class="text-center py-1 px-2" rowspan="2">Primary Department</th>
                                    <th class="text-center py-1 px-2" colspan="2">Project</th>
                                    <th class="text-center py-1 px-2" rowspan="2">Contract Days</th>
                                    <th class="text-center py-1 px-2" rowspan="2">Days In Project</th>
                                    <th class="text-center py-1 px-2" rowspan="2">Total Budgeted Days</th>
                                    <th class="text-center py-1 px-2" rowspan="2">Days Off</th>
                                </tr>

                                <tr>
                                    <th class="text-center py-1 px-2">ID</th>
                                    <th class="text-center py-1 px-2">Last Name</th>
                                    <th class="text-center py-1 px-2">First Name</th>
                                    <th class="text-center py-1 px-2">Code</th>
                                    <th class="text-center py-1 px-2">Name</th>
                                </tr>
                            </thead>
                        </table>
                        <?php createTableFooterV2("report_table", "BAP_MisbudgetedDaysReport_PageLength", $USER_SETTINGS["page_length"], true, true); ?>
                    </div>
                </div>

                <script>
                    // get today's date
                    var today = new Date().toLocaleDateString();

                    // initialize variable to state if we've drawn the table or not
                    var drawn = 0; // assume we have not drawn the table (0)

                    // get the current active period
                    let active_period = "<?php echo $active_period_label; ?>"; 

                    // set page length to prior saved state
                    let saved_page_length = sessionStorage["BAP_MisbudgetedDaysReport_PageLength"];
                    if (saved_page_length != "" && saved_page_length != null && saved_page_length != undefined)
                    {
                        $("#report_table-DT_PageLength").val(sessionStorage["BAP_MisbudgetedDaysReport_PageLength"]);
                    }

                    // set the search filters to values we have saved in storage
                    $('#search-all').val(sessionStorage["BAP_MisbudgetedEmployees_Search_All"]);
                    $('#search-status').val(sessionStorage["BAP_MisbudgetedEmployees_Search_Status"]);
                    $('#search-dept').val(sessionStorage["BAP_MisbudgetedEmployees_Search_Dept"]);
                    $('#search-project').val(sessionStorage["BAP_MisbudgetedEmployees_Search_Project"]);
                    if (sessionStorage["BAP_MisbudgetedDaysReport_Search_Period"] != "" && sessionStorage["BAP_MisbudgetedDaysReport_Search_Period"] != null && sessionStorage["BAP_MisbudgetedDaysReport_Search_Period"] != undefined) { $('#search-period').val(sessionStorage["BAP_MisbudgetedDaysReport_Search_Period"]); }
                    else { $('#search-period').val(active_period); } // no period set; default to active period 
                        
                    function displayReport()
                    {
                        // get the value of the period we are searching
                        var period = document.getElementById("search-period").value;

                        if (period != "" && period != null && period != undefined)
                        {
                            // update session storage stored search parameter
                            sessionStorage["BAP_MisbudgetedDaysReport_Search_Period"] = period;

                            // if we have already drawn the table, destroy existing table
                            if (drawn == 1) { $("#report_table").DataTable().destroy(); }

                            var table = $("#report_table").DataTable({
                                ajax: {
                                    url: "ajax/reports/getMisbudgetedEmployees.php",
                                    type: "POST",
                                    data: {
                                        period: period
                                    }
                                },
                                autoWidth: false,
                                pageLength: <?php if (isset($USER_SETTINGS["page_length"])) { echo $USER_SETTINGS["page_length"]; } else { echo 10; } ?>,
                                lengthMenu: [ [10, 25, 50, 100, 250, -1], [10, 25, 50, 100, 250, "All"] ],
                                columns: [
                                    { data: "id", orderable: true, width: "10%" },
                                    { data: "lname", orderable: true, width: "12.5%", className: "text-center" },
                                    { data: "fname", orderable: true, width: "12.5%", className: "text-center" },
                                    { data: "primary_department", orderable: true, width: "16.5%", className: "text-center" },
                                    { data: "project_code", orderable: true, width: "5%", className: "text-center" },
                                    { data: "project_name", orderable: true, width: "16.5%", className: "text-center" },
                                    { data: "contract_days", orderable: true, width: "6%", className: "text-center" },
                                    { data: "project_days", orderable: true, width: "6%", className: "text-center" },
                                    { data: "budgeted_days", orderable: true, width: "9%", className: "text-center" }, 
                                    { data: "days_diff", orderable: true, width: "6%", className: "text-center" }, 
                                    { data: "status", orderable: true, visible: false } // 10
                                ],
                                dom: 'rt',
                                pagination: true,
                                language: {
                                    search: '<div class="input-group mb-1"><div class="input-group-prepend"><span class="d-flex align-items-center h-100 mx-2" id="nav-search-icon"><i class="fa fa-search" aria-hidden="true"></i></span></div>_INPUT_</div>',
                                    lengthMenu: '<div class="mx-1 mb-1">Show _MENU_ entries</div>',
                                    info: '<div class="mx-1">Showing _START_ to _END_ of _TOTAL_ entries</div>'
                                },
                                rowCallback: function (row, data, index)
                                {
                                    updatePageSelection("report_table");

                                    // check budgeted days in comparison to contract days
                                    if (parseFloat(data["days_diff"]) > 0) { $("td:eq(9)", row).addClass("verified-box-fail"); }
                                    else if (parseFloat(data["days_diff"]) < 0) { $("td:eq(9)", row).addClass("verified-box-fail-over"); }
                                    else { $("td:eq(9)", row).addClass("verified-box-pass"); }
                                },
                                stateSave: true
                            });

                            // create the export buttons
                            new $.fn.dataTable.Buttons(table, {
                                buttons: [
                                    // PDF BUTTON
                                    {
                                        extend: "pdf",
                                        text: "<i class=\"fa-solid fa-file-pdf fa-xl\"></i>",
                                        className: "btn btn-primary ms-1 py-2 px-3",
                                        orientation: "landscape",
                                        title: "Misbudgeted Employees - " + today,
                                        init: function(api, node, config) {
                                            // remove default button classes
                                            $(node).removeClass('dt-button');
                                            $(node).removeClass('buttons-pdf');
                                            $(node).removeClass('buttons-html5');
                                        }
                                    },
                                    // CSV BUTTON
                                    {
                                        extend: "csv",
                                        text: "<i class=\"fa-solid fa-file-csv fa-xl\"></i>",
                                        className: "btn btn-primary ms-1 py-2 px-3",
                                        title: "Misbudgeted Employees - " + today,
                                        init: function(api, node, config) {
                                            // remove default button classes
                                            $(node).removeClass('dt-button');
                                            $(node).removeClass('buttons-csv');
                                            $(node).removeClass('buttons-html5');
                                        }
                                    },
                                    // EXCEL BUTTON
                                    {
                                        extend: "excel",
                                        text: "<i class=\"fa-solid fa-file-excel fa-xl\"></i>",
                                        className: "btn btn-primary ms-1 py-2 px-3",
                                        title: "Misbudgeted Employees - " + today,
                                        init: function(api, node, config) {
                                            // remove default button classes
                                            $(node).removeClass('dt-button');
                                            $(node).removeClass('buttons-excel');
                                            $(node).removeClass('buttons-html5');
                                        }
                                    },
                                    // PRINT BUTTON
                                    {
                                        extend: "print",
                                        text: "<i class=\"fa-solid fa-print fa-xl\"></i>",
                                        className: "btn btn-primary ms-1 py-2 px-3",
                                        orientation: "landscape",
                                        title: "Misbudgeted Employees - " + today,
                                        init: function(api, node, config) {
                                            // remove default button classes
                                            $(node).removeClass('dt-button');
                                            $(node).removeClass('buttons-print');
                                            $(node).removeClass('buttons-html5');
                                        }
                                    }
                                ]
                            });

                            // add buttons to table footer container
                            table.buttons().container().appendTo("#report-buttons");

                            // mark that we have drawn the table
                            drawn = 1;

                            // search table by custom search filter
                            $('#search-all').keyup(function() {
                                table.search($(this).val()).draw();
                                sessionStorage["BAP_MisbudgetedEmployees_Search_All"] = $(this).val();
                            });

                            // search table by employee status
                            $('#search-status').change(function() {
                                sessionStorage["BAP_MisbudgetedEmployees_Search_Status"] = $(this).val();
                                if ($(this).val() != "") { table.columns(10).search("^" + $(this).val() + "$", true, false, true).draw(); }
                                else { table.columns(10).search("").draw(); }
                            });

                            // search table by department
                            $('#search-dept').change(function() {
                                table.columns(3).search($(this).val()).draw();
                                sessionStorage["BAP_MisbudgetedEmployees_Search_Dept"] = $(this).val();
                            });

                            // search table by project code
                            $('#search-project').change(function() {
                                table.columns(4).search($(this).val()).draw();
                                sessionStorage["BAP_MisbudgetedEmployees_Search_Project"] = $(this).val();
                            });

                            // function to clear search filters
                            $('#clearFilters').click(function() {
                                sessionStorage["BAP_MisbudgetedEmployees_Search_Dept"] = "";
                                sessionStorage["BAP_MisbudgetedEmployees_Search_Project"] = "";
                                sessionStorage["BAP_MisbudgetedEmployees_Search_All"] = "";
                                sessionStorage["BAP_MisbudgetedEmployees_Search_Status"] = "";
                                $('#search-all').val("");
                                $('#search-status').val("");
                                $('#search-dept').val("");
                                $('#search-project').val("");
                                table.search("").columns().search("").draw();
                            });

                            // display the table
                            document.getElementById("report-table-div").classList.remove("d-none");
                        }
                        else { createStatusModal("alert", "Loading Report Error", "Failed to load the report. You must select a period to display the report for."); }
                    }

                    // display the report with default parameters
                    displayReport();
                </script>
            <?php 
        }
        else { denyAccess(); }
    }
    else { goToLogin(); }

    include("footer.php"); 
?>