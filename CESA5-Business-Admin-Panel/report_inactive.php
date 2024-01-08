<?php 
    include_once("header.php");

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1) 
    { 
        if (isset($PERMISSIONS["VIEW_REPORT_BUDGETED_INACTIVE_EMPLOYEES_ALL"]) || isset($PERMISSIONS["VIEW_REPORT_BUDGETED_INACTIVE_EMPLOYEES_ASSIGNED"]))
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
                                <h1 class="report-title m-0">Budgeted Inactive Employees</h1>
                                <p class="report-description m-0">
                                    This report displays a list of all employees who are budgeted to projects but set as an inactive employee within the current active period.
                                </p>
                            </div>

                            <!-- Page Management Dropdown -->
                            <div class="col-6 col-sm-4 col-md-4 col-lg-3 col-xl-2 col-xxl-2 p-0">
                                <span class="d-flex justify-content-end" id="report-buttons"></span>
                            </div>
                        </div>
                    </div>

                    <div class="row report-body m-0">
                        <table id="report_table" class="report_table w-100">
                            <thead>
                                <tr>
                                    <th class="text-center py-1 px-2" colspan="3">Employee</th>
                                    <th class="text-center py-1 px-2" colspan="2">Project</th>
                                    <th class="text-center py-1 px-2" rowspan="2">Contract Days</th>
                                    <th class="text-center py-1 px-2" rowspan="2">Days In Project</th>
                                    <th class="text-center py-1 px-2" rowspan="2">Actions</th>
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
                        <?php createTableFooterV2("report_table", "BAP_InactiveEmployeesReport_PageLength", $USER_SETTINGS["page_length"], true, true); ?>
                    </div>
                </div>

                <!-- MODALS -->
                <div id="remove-employee_from_project-modal-div"></div>
                <!-- END MODALS -->

                <script>
                    // get today's date
                    var today = new Date().toLocaleDateString();

                    // initialize variable to state if we've drawn the table or not
                    var drawn = 0; // assume we have not drawn the table (0)

                    // get the current active period
                    let active_period = "<?php echo $active_period_label; ?>"; 

                    // set page length to prior saved state
                    let saved_page_length = sessionStorage["BAP_InactiveEmployeesReport_PageLength"];
                    if (saved_page_length != "" && saved_page_length != null && saved_page_length != undefined)
                    {
                        $("#report_table-DT_PageLength").val(sessionStorage["BAP_InactiveEmployeesReport_PageLength"]);
                    }

                    // set the search filters to values we have saved in storage
                    if (sessionStorage["BAP_InactiveEmployeesReport_Search_Period"] != "" && sessionStorage["BAP_InactiveEmployeesReport_Search_Period"] != null && sessionStorage["BAP_InactiveEmployeesReport_Search_Period"] != undefined) { $('#search-period').val(sessionStorage["BAP_InactiveEmployeesReport_Search_Period"]); }
                    else { $('#search-period').val(active_period); } // no period set; default to active period 

                    function displayReport()
                    {
                        // get the value of the period we are searching
                        var period = document.getElementById("search-period").value;

                        if (period != "" && period != null && period != undefined)
                        {
                            // set the project as selected code
                            document.getElementById("fixed-period").value = period;
                            
                            // update session storage stored search parameter
                            sessionStorage["BAP_InactiveEmployeesReport_Search_Period"] = period;

                            // if we have already drawn the table, destroy existing table
                            if (drawn == 1) { $("#report_table").DataTable().destroy(); }

                            var table = $("#report_table").DataTable({
                                ajax: {
                                    url: "ajax/reports/getBudgetedInactive.php",
                                    type: "POST",
                                    data: {
                                        period: period
                                    }
                                },
                                autoWidth: false,
                                pageLength: <?php if (isset($USER_SETTINGS["page_length"])) { echo $USER_SETTINGS["page_length"]; } else { echo 10; } ?>,
                                lengthMenu: [ [10, 25, 50, 100, 250, -1], [10, 25, 50, 100, 250, "All"] ],
                                columns: [
                                    { data: "id", orderable: true },
                                    { data: "lname", orderable: true, className: "text-center" },
                                    { data: "fname", orderable: true, className: "text-center" },
                                    { data: "project_code", orderable: true, className: "text-center" },
                                    { data: "project_name", orderable: true, className: "text-center" },
                                    { data: "contract_days", orderable: true, className: "text-center" },
                                    { data: "project_days", orderable: true, className: "text-center" },
                                    <?php if (isset($PERMISSIONS["BUDGET_PROJECTS_ALL"]) || isset($PERMISSIONS["BUDGET_PROJECTS_ASSIGNED"])) { ?>
                                    { data: "actions", orderable: false, className: "text-center" }
                                    <?php } else { ?>
                                    { data: "actions", orderable: false, visible: false }
                                    <?php } ?>
                                ],
                                dom: 'rt',
                                language: {
                                    search: '<div class="input-group mb-1"><div class="input-group-prepend"><span class="d-flex align-items-center h-100 mx-2" id="nav-search-icon"><i class="fa fa-search" aria-hidden="true"></i></span></div>_INPUT_</div>',
                                    lengthMenu: '<div class="mx-1 mb-1">Show _MENU_ entries</div>',
                                    info: '<div class="mx-1">Showing _START_ to _END_ of _TOTAL_ entries</div>'
                                },
                                rowCallback: function (row, data, index)
                                {
                                    updatePageSelection("report_table");
                                }
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
                                        title: "Budgeted Inactive Employees - " + today,
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
                                ],
                            });

                            // add buttons to table footer container
                            table.buttons().container().appendTo("#report-buttons");

                            // mark that we have drawn the table
                            drawn = 1;

                            // search table by custom search filter
                            $('#search-all').keyup(function() {
                                table.search($(this).val()).draw();
                            });
                        }
                    }

                    /** function to get the delete department modal */
                    function getRemoveEmployeeFromProjectModal(id, code, record)
                    {
                        // send the data to create the delete department modal
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/projects/getRemoveEmployeeFromProjectModal.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // display the delete department modal
                                document.getElementById("remove-employee_from_project-modal-div").innerHTML = this.responseText;     
                                $("#removeEmployeeFromProjectModal").modal("show");
                            }
                        };
                        xmlhttp.send("id="+id+"&code="+code+"&record="+record);
                    }

                    /** function to remove an employee from the project */
                    function removeEmployeeFromProject(id, code, record)
                    {
                        // get the fixed project code and period
                        let period = document.getElementById("fixed-period").value;

                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/projects/removeEmployeeFromProject.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // create the status modal
                                let status_title = "Remove Employee From Project Status";
                                let status_body = this.responseText;
                                createStatusModal("refresh", status_title, status_body);

                                // hide the current modal
                                $("#removeEmployeeFromProjectModal").modal("hide");
                            }
                        };
                        xmlhttp.send("period="+period+"&code="+code+"&id="+id+"&record="+record);
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