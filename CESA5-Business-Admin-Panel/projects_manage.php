<?php 
    include_once("header.php");

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1) 
    { 
        if (isset($PERMISSIONS["VIEW_PROJECTS_ALL"]) || isset($PERMISSIONS["VIEW_PROJECTS_ASSIGNED"]))
        {
            // connect to the database
            $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

            // iniitalize variable to store how many departments the director has
            $director_departments_count = 0;
            if ($_SESSION["role"] == 2) { $director_departments_count = getDirectorDepartmentsCount($conn, $_SESSION["id"]); }

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
                <script>
                    /** function to add a new project */
                    function addProject()
                    {
                        // get the fixed period
                        let period = document.getElementById("fixed-period").value;

                        // get the form fields
                        let code = document.getElementById("add-code").value;
                        let name = document.getElementById("add-name").value;
                        let desc = document.getElementById("add-desc").value;
                        let dept = document.getElementById("add-dept").value;
                        let fund = document.getElementById("add-fund").value;
                        let func = document.getElementById("add-func").value;
                        let status = document.getElementById("add-status").value;
                        let fte_days = document.getElementById("add-fte").value;
                        let leave_time = document.getElementById("add-leave_time").value;
                        let prep_work = document.getElementById("add-prep_work").value;
                        let personal_development = document.getElementById("add-personal_development").value;

                        // initialize checkbox values to 0
                        let supervision = calc_fte = 0;
                        if ($("#add-supervision").is(":checked")) { supervision = 1; }
                        if ($("#add-calc_fte").is(":checked")) { calc_fte = 1; }

                        // get location result
                        let location = 0;
                        if (document.getElementById("add-location-customer").value == 1) { location = 1;}
                        else if (document.getElementById("add-location-classroom").value == 1) { location = 2; }

                        // get indirect rate result
                        let indirect = 0;
                        if (document.getElementById("add-indirect-agency").value == 1) { indirect = 1;}
                        else if (document.getElementById("add-indirect-grant").value == 1) { indirect = 2; }
                        else if (document.getElementById("add-indirect-dpi_grant").value == 1) { indirect = 3; }
                        
                        // create the string of parameters to send
                        let sendString = "code="+code+"&name="+name+"&desc="+desc+"&dept="+dept+"&fund="+fund+"&func="+func+"&supervision_costs="+supervision+"&indirect_costs="+indirect+"&status="+status+"&period="+period+"&FTE_days="+fte_days+"&leave_time="+leave_time+"&prep_work="+prep_work+"&personal_development="+personal_development+"&calc_fte="+calc_fte+"&location="+location;

                        // send the data to process the add project request
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/projects/addProject.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // create the status modal
                                let status_title = "Add Project Status";
                                let status_body = this.responseText;
                                createStatusModal("refresh", status_title, status_body);

                                // hide the current modal
                                $("#addProjectModal").modal("hide");
                            }
                        };
                        xmlhttp.send(sendString);
                    }

                    /** function to delete the project */
                    function deleteProject(code)
                    {
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/projects/deleteProject.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // create the status modal
                                let status_title = "Delete Project Status";
                                let status_body = this.responseText;
                                createStatusModal("refresh", status_title, status_body);

                                // hide the current modal
                                $("#deleteProjectModal").modal("hide");
                            }
                        };
                        xmlhttp.send("code="+code);
                    }

                    /** function to get the delete project modal */
                    function getDeleteProjectModal(code)
                    {
                        // send the data to create the delete project modal
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/projects/getDeleteProjectModal.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                document.getElementById("delete-project-modal-div").innerHTML = this.responseText;     

                                // display the delete project modal
                                $("#deleteProjectModal").modal("show");
                            }
                        };
                        xmlhttp.send("code="+code);
                    }

                    /** function to edit the project */
                    function editProject(code)
                    {
                        // get the fixed period
                        let period = document.getElementById("fixed-period").value;

                        // get the form fields
                        let form_code = document.getElementById("edit-code").value;
                        let name = document.getElementById("edit-name").value;
                        let desc = document.getElementById("edit-desc").value;
                        let dept = document.getElementById("edit-dept").value;
                        let fund = document.getElementById("edit-fund").value;
                        let func = document.getElementById("edit-func").value;
                        let status = document.getElementById("edit-status").value;
                        let fte_days = document.getElementById("edit-fte").value;
                        let leave_time = document.getElementById("edit-leave_time").value;
                        let prep_work = document.getElementById("edit-prep_work").value;
                        let personal_development = document.getElementById("edit-personal_development").value;

                        // initialize checkbox values to 0
                        let supervision = calc_fte = 0;
                        if ($("#edit-supervision").is(":checked")) { supervision = 1; }
                        if ($("#edit-calc_fte").is(":checked")) { calc_fte = 1; }

                        // get location result
                        let location = 0;
                        if (document.getElementById("edit-location-customer").value == 1) { location = 1;}
                        else if (document.getElementById("edit-location-classroom").value == 1) { location = 2; }

                        // get indirect rate result
                        let indirect = 0;
                        if (document.getElementById("edit-indirect-agency").value == 1) { indirect = 1;}
                        else if (document.getElementById("edit-indirect-grant").value == 1) { indirect = 2; }
                        else if (document.getElementById("edit-indirect-dpi_grant").value == 1) { indirect = 3; }
                        
                        // create the string of parameters to send
                        let sendString = "code="+code+"&form_code="+form_code+"&name="+name+"&desc="+desc+"&dept="+dept+"&fund="+fund+"&func="+func+"&supervision_costs="+supervision+"&indirect_costs="+indirect+"&status="+status+"&period="+period+"&FTE_days="+fte_days+"&leave_time="+leave_time+"&prep_work="+prep_work+"&personal_development="+personal_development+"&calc_fte="+calc_fte+"&location="+location;

                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/projects/editProject.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // create the status modal
                                let status_title = "Edit Project Status";
                                let status_body = this.responseText;
                                createStatusModal("refresh", status_title, status_body);

                                // hide the current modal
                                $("#editProjectModal").modal("hide");
                            }
                        };
                        xmlhttp.send(sendString);
                    }

                    /** function to get the edit project modal */
                    function getEditProjectModal(code)
                    {
                        // get the fixed period
                        let period = document.getElementById("fixed-period").value;
                        
                        // send the data to create the edit project modal
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/projects/getEditProjectModal.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                document.getElementById("edit-project-modal-div").innerHTML = this.responseText;     

                                // display the delete project modal
                                $("#editProjectModal").modal("show");
                            }
                        };
                        xmlhttp.send("code="+code+"&period="+period);
                    }

                    /** function to update the status element */
                    function updateStatus(id)
                    {
                        // get current status of the element
                        let element = document.getElementById(id);
                        let status = element.value;

                        if (status == 0) // currently set to inactive
                        {
                            // update status to active
                            element.value = 1;
                            element.innerHTML = "Active";
                            element.classList.remove("btn-danger");
                            element.classList.add("btn-success");
                        }
                        else // currently set to active, or other?
                        {
                            // update status to inactive
                            element.value = 0;
                            element.innerHTML = "Inactive";
                            element.classList.remove("btn-success");
                            element.classList.add("btn-danger");
                        }
                    }

                    /** function to update the supervision and/or indirect costs element */
                    function updateCosts(id)
                    {
                        // get current status of the element
                        let element = document.getElementById(id);
                        let status = element.value;

                        if (status == 0) // currently set to inactive
                        {
                            // update status to active
                            element.value = 1;
                            element.innerHTML = "Yes";
                            element.classList.remove("btn-danger");
                            element.classList.add("btn-success");
                        }
                        else // currently set to active, or other?
                        {
                            // update status to inactive
                            element.value = 0;
                            element.innerHTML = "No";
                            element.classList.remove("btn-success");
                            element.classList.add("btn-danger");
                        }
                    }

                    /** function to toggle additional filters */
                    function toggleFilters(value)
                    {
                        if (value == 1) // filters are currently displayed; hide filters
                        {
                            // hide div
                            document.getElementById("showFilters").value = 0;
                            document.getElementById("showFilters-label").innerHTML = "Show More Filters";
                            document.getElementById("showFilters-icon").innerHTML = "<i class='fa-solid fa-angle-down'></i>";
                            document.getElementById("manage_projects-filters-div").classList.add("d-none");
                            
                            // store current status in a cookie
                            document.cookie = "BAP_ManageProjects_FiltersDisplayed=0; expires=Tue, 19 Jan 2038 04:14:07 GMT";
                        }
                        else // filters are currently hidden; display filters
                        {
                            // display div
                            document.getElementById("showFilters").value = 1;
                            document.getElementById("showFilters-label").innerHTML = "Hide Filters";
                            document.getElementById("showFilters-icon").innerHTML = "<i class='fa-solid fa-angle-up'></i>";
                            document.getElementById("manage_projects-filters-div").classList.remove("d-none");

                            // store current status in a cookie
                            document.cookie = "BAP_ManageProjects_FiltersDisplayed=1; expires=Tue, 19 Jan 2038 04:14:07 GMT";
                        }
                    }

                    /** function to toggle location type */
                    function toggleLocation(origin, type)
                    {
                        // remove primary class from all
                        document.getElementById(origin+"-location-none").classList.remove("btn-primary");
                        document.getElementById(origin+"-location-customer").classList.remove("btn-primary");
                        document.getElementById(origin+"-location-classroom").classList.remove("btn-primary");

                        // add secondary class to all
                        document.getElementById(origin+"-location-none").classList.add("btn-secondary");
                        document.getElementById(origin+"-location-customer").classList.add("btn-secondary");
                        document.getElementById(origin+"-location-classroom").classList.add("btn-secondary");

                        // set all to off 
                        document.getElementById(origin+"-location-none").value = 0;
                        document.getElementById(origin+"-location-customer").value = 0;
                        document.getElementById(origin+"-location-classroom").value = 0;

                        // set selected to on
                        document.getElementById(origin+"-location-"+type).classList.add("btn-primary");
                        document.getElementById(origin+"-location-"+type).value = 1;
                    }

                    /** function to toggle indirect rate type */
                    function toggleIndirect(origin, type)
                    {
                        // remove primary class from all
                        document.getElementById(origin+"-indirect-none").classList.remove("btn-primary");
                        document.getElementById(origin+"-indirect-agency").classList.remove("btn-primary");
                        document.getElementById(origin+"-indirect-grant").classList.remove("btn-primary");
                        document.getElementById(origin+"-indirect-dpi_grant").classList.remove("btn-primary");

                        // add secondary class to all
                        document.getElementById(origin+"-indirect-none").classList.add("btn-secondary");
                        document.getElementById(origin+"-indirect-agency").classList.add("btn-secondary");
                        document.getElementById(origin+"-indirect-grant").classList.add("btn-secondary");
                        document.getElementById(origin+"-indirect-dpi_grant").classList.add("btn-secondary");

                        // set all to off 
                        document.getElementById(origin+"-indirect-none").value = 0;
                        document.getElementById(origin+"-indirect-agency").value = 0;
                        document.getElementById(origin+"-indirect-grant").value = 0;
                        document.getElementById(origin+"-indirect-dpi_grant").value = 0;

                        // set selected to on
                        document.getElementById(origin+"-indirect-"+type).classList.add("btn-primary");
                        document.getElementById(origin+"-indirect-"+type).value = 1;
                    }

                    /** function to check if the "Grant Project" option is checked */
                    function toggleGrantProject(checked, origin)
                    {
                        if (!checked) {
                            document.getElementById(origin+"-grantProjectHelpBlock").classList.add("d-none");
                        } else {
                            document.getElementById(origin+"-grantProjectHelpBlock").classList.remove("d-none");
                        }
                    }

                    /** function to check if the "Grant Project" option is checked */
                    function toggleDPIGrantProject(checked, origin)
                    {
                        if (!checked) {
                            document.getElementById(origin+"-DPIgrantProjectHelpBlock").classList.add("d-none");
                        } else {
                            document.getElementById(origin+"-DPIgrantProjectHelpBlock").classList.remove("d-none");
                        }
                    }

                    /** function to check if the "Grant Project" option is checked */
                    function toggleCalcFTE(checked, origin)
                    {
                        if (!checked) {
                            document.getElementById(origin+"-calc_fte-div").classList.add("d-none");
                        } else {
                            document.getElementById(origin+"-calc_fte-div").classList.remove("d-none");
                        }
                    }
                </script>

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
                                                <select class="form-select" id="search-period" name="search-period" onchange="searchProjects();">
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

                                                    <!-- Filter By Fund Code -->
                                                    <div class="row d-flex justify-content-between align-items-center mx-0 mt-0 mb-2">
                                                        <div class="col-4 ps-0 pe-1">
                                                            <label for="search-fund">Fund:</label>
                                                        </div>

                                                        <div class="col-8 ps-1 pe-0">
                                                            <select class="form-select" id="search-fund" name="search-fund">
                                                                <option></option>
                                                                <?php
                                                                    $getFunds = mysqli_query($conn, "SELECT DISTINCT fund_code FROM projects ORDER BY fund_code ASC");
                                                                    if (mysqli_num_rows($getFunds) > 0) // fund codes found
                                                                    {
                                                                        while ($fund = mysqli_fetch_array($getFunds))
                                                                        {
                                                                            if (isset($fund["fund_code"]) && $fund["fund_code"] <> "") { echo "<option value='".$fund["fund_code"]."'>".$fund["fund_code"]."</option>"; }
                                                                        }
                                                                    }
                                                                ?>
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
                                                                <?php
                                                                    if (checkUserPermission($conn, "VIEW_PROJECTS_ALL")) // admin and maintenance departments list
                                                                    { 
                                                                        echo "<option>No primary department assigned</option>";
                                                                        $getDepts = mysqli_query($conn, "SELECT id, name FROM departments ORDER BY name ASC");
                                                                        if (mysqli_num_rows($getDepts) > 0) // departments found
                                                                        {
                                                                            while ($dept = mysqli_fetch_array($getDepts))
                                                                            {
                                                                                echo "<option>".$dept["name"]."</option>";
                                                                            }
                                                                        }
                                                                    }
                                                                    else if (checkUserPermission($conn, "VIEW_PROJECTS_ASSIGNED")) // director's department list
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
                                    <h2 class="m-0">Projects</h2>
                                    <p class="report-description m-0">Go to the <a href="https://docs.google.com/spreadsheets/d/1SbVqG19hlGzMRaQ9mv15uq-iWWmOCoDGQhd7_WfBbGE/edit?usp=sharing" target="_blank">Grant / Contract Budget Summary and Claims spreadsheet</a>.</p>
                                </div>

                                <!-- Page Management Dropdown -->
                                <div class="col-6 col-sm-4 col-md-4 col-lg-3 col-xl-2 col-xxl-2 p-0">
                                    <?php if (checkUserPermission($conn, "ADD_PROJECTS") || $_SESSION["role"] == 1) { ?>
                                        <div class="dropdown float-end">
                                            <button class="btn btn-primary dropdown-toggle" type="button" id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">
                                                Manage Projects
                                            </button>
                                            <ul class="dropdown-menu p-0" aria-labelledby="dropdownMenuButton1">
                                                <li><button class="dropdown-item quickNav-dropdown-item text-center w-100 px-3 py-2 rounded-0" type="button" data-bs-toggle="modal" data-bs-target="#addProjectModal">Add Project</button></li>
                                                <li><button class="dropdown-item quickNav-dropdown-item text-center w-100 px-3 py-2 rounded-0" type="button" data-bs-toggle="modal" data-bs-target="#uploadProjectsModal">Upload Projects</button></li>
                                                <?php if ($_SESSION["role"] == 1) { // ADMIN ONLY TOOLS ?>
                                                    <li><button class="dropdown-item quickNav-dropdown-item text-center w-100 px-3 py-2 rounded-0" type="button" data-bs-toggle="modal" data-bs-target="#recalculateExpensesModal">Recalculate Automated Expenses</button></li>
                                                    <li>
                                                        <form action="ajax/projects/exportFinancialCodes.php" method="POST">
                                                            <input type="hidden" id="export-period" name="export-period" aria-hidden="true">
                                                            <button class="dropdown-item quickNav-dropdown-item text-center w-100 px-3 py-2 rounded-0" type="submit" onclick="processCodesExport();">Export Financial Codes</button>
                                                        </form>
                                                    </li>
                                                <?php } ?>
                                            </ul>
                                        </div>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>

                        <div class="table-header">
                            <div class="row report-header justify-content-center my-2">
                                <div class="col-12 col-sm-12 col-md-10 col-lg-9 col-xl-8 col-xxl-6 project-totals p-0">
                                    <table class="report_table-inverse w-100">
                                        <thead>
                                            <tr>
                                                <th class="text-center">Gross Revenue</th>
                                                <th class="text-center">Gross Expenses</th>
                                                <th class="text-center">Net Earnings</th>
                                            </tr>
                                        </thead>

                                        <tbody>
                                            <tr style="background-color: #ffffff !important;">
                                                <td id="projects-total_revenues">$0.00</td>
                                                <td id="projects-total_expenses">$0.00</td>
                                                <td id="projects-net_income">$0.00</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div id="projects-table-container" class="p-0">
                            <table id="projects" class="report_table w-100">
                                <thead>
                                    <tr>
                                        <th class="text-center py-1 px-2" rowspan="2">Name</th>
                                        <th class="text-center py-1 px-2" colspan="3">WUFAR Codes</th>
                                        <th class="text-center py-1 px-2" rowspan="2">Department</th>
                                        <th class="text-center py-1 px-2" rowspan="2">Employees In Project</th>
                                        <th class="py-1 px-2" style="text-align: center !important;" rowspan="2">Revenues</th>
                                        <th class="py-1 px-2" style="text-align: center !important;" rowspan="2">Expenses</th>
                                        <th class="py-1 px-2" style="text-align: center !important;" rowspan="2">Net</th>
                                        <th class="text-center py-1 px-2" rowspan="2">Actions</th>

                                        <!-- Hidden Columns -->
                                        <th class="text-center py-1 px-2" rowspan="2">Code</th>
                                        <th class="text-center py-1 px-2" rowspan="2">Name</th>
                                        <th class="text-center py-1 px-2" rowspan="2">Revenues</th>
                                        <th class="text-center py-1 px-2" rowspan="2">Expenses</th>
                                        <th class="text-center py-1 px-2" rowspan="2">Net</th>
                                        <th class="text-center py-1 px-2" rowspan="2">Status</th>
                                    </tr>

                                    <tr>
                                        <th class="text-center py-1 px-2">Project</th>
                                        <th class="text-center py-1 px-2">Fund</th>
                                        <th class="text-center py-1 px-2">Function</th>
                                    </tr>
                                </thead>

                                <tfoot>
                                    <tr>
                                        <th class="py-1 px-2" style="text-align: right !important;" colspan="5">TOTALS:</th>
                                        <th class="text-center py-1 px-2" id="sum-emps"></th> <!-- budgeted employees sum -->
                                        <th class="text-end py-1 px-2" id="sum-rev"></th> <!-- revenues sum -->
                                        <th class="text-end py-1 px-2" id="sum-exp"></th> <!-- expenses sum -->
                                        <th class="text-end py-1 px-2" id="sum-net"></th> <!-- net sum -->
                                        <th colspan="3"></th> <!-- actions -->
                                    </tr>
                                </tfoot>
                            </table>
                            <?php createTableFooterV2("projects", "BAP_ManageProjects_PageLength", $USER_SETTINGS["page_length"], true, true); ?>
                        </div>
                    </div>
                </div>

                <!--
                    ### MODALS ###
                -->
                <!-- Add Project Modal -->
                <div class="modal fade" tabindex="-1" role="dialog" id="addProjectModal" data-bs-backdrop="static" aria-labelledby="addProjectModalLabel" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header primary-modal-header">
                                <h5 class="modal-title primary-modal-title" id="addProjectModalLabel">Add Project</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>

                            <div class="modal-body px-5">
                                <div class="form-row d-flex justify-content-center align-items-center mt-3 mb-0">
                                    <!-- Project Code -->
                                    <div class="form-group col-12">
                                        <label for="add-code"><span class="required-field">*</span> Project Code:</label>
                                        <input type="text" class="form-control w-100" id="add-code" name="add-code" min="100" max="999" aria-describedby="projCodeHelpBlock" required>
                                    </div>
                                </div>
                                <div id="projCodeHelpBlock" class="form-text p-0">
                                    The fund code must follow the WUFAR convention. It must be a number between 100 and 999.
                                </div>

                                <div class="form-row d-flex justify-content-center align-items-center my-3">
                                    <!-- Project Name -->
                                    <div class="form-group col-12">
                                        <label for="add-name"><span class="required-field">*</span> Name:</label>
                                        <input type="text" class="form-control w-100" id="add-name" name="add-name" required>
                                    </div>
                                </div>

                                <div class="form-row d-flex justify-content-center align-items-center my-3">
                                    <!-- Project Description -->
                                    <div class="form-group col-12">
                                        <label for="add-desc">Description:</label>
                                        <input type="text" class="form-control w-100" id="add-desc" name="add-desc" required>
                                    </div>
                                </div>

                                <div class="form-row d-flex justify-content-center align-items-center my-3">
                                    <!-- Department -->
                                    <div class="form-group col-12">
                                        <label for="add-dept">Department:</label>
                                        <select class="form-select w-100" id="add-dept" name="add-dept" required>
                                            <option value=0></option>
                                            <?php 
                                                // create the dropdown options of departments
                                                $getDepartments = mysqli_query($conn, "SELECT id, name FROM departments");
                                                while ($dept = mysqli_fetch_array($getDepartments))
                                                {
                                                    if ($dept["name"] <> "") { echo "<option value=".$dept["id"].">".$dept["name"]."</option>"; }
                                                }
                                            ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="form-row d-flex justify-content-center align-items-center mt-3">
                                    <!-- Fund Code -->
                                    <div class="form-group col-12">
                                        <label for="add-fund"><span class="required-field">*</span> Fund Code:</label>
                                        <input type="number" class="form-control w-100" id="add-fund" name="add-fund" min="10" max="99" aria-describedby="fundCodeHelpBlock" required>
                                    </div>
                                </div>
                                <div id="fundCodeHelpBlock" class="form-text p-0">
                                    The fund code must follow the WUFAR convention. It must be a number between 10 and 99.
                                </div>

                                <div class="form-row d-flex justify-content-center align-items-center mt-3">
                                    <!-- Function Code -->
                                    <div class="form-group col-12">
                                        <label for="add-func"><span class="required-field">*</span> Function Code:</label>
                                        <input type="number" class="form-control w-100" id="add-func" name="add-func" min="100000" max="999999" aria-describedby="funcCodeHelpBlock" required>
                                    </div>
                                </div>
                                <div id="funcCodeHelpBlock" class="form-text p-0">
                                    The function code must follow the WUFAR convention. It must be a number between 100000 and 999999.
                                </div>

                                <!-- Staff Locations -->
                                <label class="mt-3 mb-0">Staff Location:</label>
                                <div class="form-row mb-3">
                                    <div class="btn-group w-100" role="group">
                                        <button type="button" class="btn btn-sm btn-primary" id="add-location-none" value="1" onclick="toggleLocation('add', 'none');">No Location</button>
                                        <button type="button" class="btn btn-sm btn-secondary" id="add-location-customer" value="0" onclick="toggleLocation('add', 'customer');">Customer</button>
                                        <button type="button" class="btn btn-sm btn-secondary" id="add-location-classroom" value="0" onclick="toggleLocation('add', 'classroom');">Classroom</button>
                                    </div>
                                </div>

                                <!-- Indirect Rate -->
                                <label class="m-0">Indirect Rate:</label>
                                <div class="form-row mb-3">
                                    <div class="btn-group w-100" role="group">
                                        <button type="button" class="btn btn-sm btn-primary" id="add-indirect-none" value="1" onclick="toggleIndirect('add', 'none');">None</button>
                                        <button type="button" class="btn btn-sm btn-secondary" id="add-indirect-agency" value="0" onclick="toggleIndirect('add', 'agency');">Agency Rate</button>
                                        <button type="button" class="btn btn-sm btn-secondary" id="add-indirect-grant" value="0" onclick="toggleIndirect('add', 'grant');">Grant Rate</button>
                                        <button type="button" class="btn btn-sm btn-secondary" id="add-indirect-dpi_grant" value="0" onclick="toggleIndirect('add', 'dpi_grant');">DPI Rate</button>
                                    </div>
                                </div>

                                <div class="form-row my-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="add-supervision">
                                        <label class="form-check-label" for="add-supervision">Supervision Costs</label>
                                    </div>

                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="add-calc_fte" onchange="toggleCalcFTE(this.checked, 'add');">
                                        <label class="form-check-label" for="add-calc_fte">Calculate Project FTE</label>
                                    </div>
                                </div>

                                <!-- Calculate Project FTE -->
                                <div class="d-none" id="add-calc_fte-div">
                                    <!-- Project Costs By Days -->
                                    <div class="form-row d-flex justify-content-center align-items-center my-3">
                                        <!-- FTE -->
                                        <div class="form-group col-12">
                                            <label for="add-fte">FTE (Days):</label>
                                            <input type="number" class="form-control w-100" id="add-fte" name="add-fte" min="0" max="365" required>
                                        </div>
                                    </div>
                                    <div class="form-row d-flex justify-content-center align-items-center my-3">
                                        <!-- Leave Time -->
                                        <div class="form-group col-12">
                                            <label for="add-leave_time">Leave Time (Days):</label>
                                            <input type="number" class="form-control w-100" id="add-leave_time" name="add-leave_time" min="0" max="365" required>
                                        </div>
                                    </div>
                                    <div class="form-row d-flex justify-content-center align-items-center my-3">
                                        <!-- Prep Work -->
                                        <div class="form-group col-12">
                                            <label for="add-prep_work">Prep Work (Days):</label>
                                            <input type="number" class="form-control w-100" id="add-prep_work" name="add-prep_work" min="0" max="365" required>
                                        </div>
                                    </div>
                                    <div class="form-row d-flex justify-content-center align-items-center my-3">
                                        <!-- Personal Development -->
                                        <div class="form-group col-12">
                                            <label for="add-personal_development">Personal Development (Days):</label>
                                            <input type="number" class="form-control w-100" id="add-personal_development" name="add-personal_development" min="0" max="365" required>
                                        </div>
                                    </div>

                                    <div class="form-row d-flex justify-content-center align-items-center my-3">
                                        <!-- Status -->
                                        <div class="form-group col-12">
                                            <label for="add-status"><span class="required-field">*</span> Status:</label>
                                            <button class="btn btn-success w-100" id="add-status" value=1 onclick="updateStatus('add-status');">Active</button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="modal-footer">
                                <button type="button" class="btn btn-primary" onclick="addProject();"><i class="fa-solid fa-floppy-disk"></i> Save New Project</button>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> Close</button>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- End Add Project Modal -->

                <!-- Upload Projects Modal -->
                <div class="modal fade" tabindex="-1" role="dialog" id="uploadProjectsModal" data-bs-backdrop="static" aria-labelledby="uploadProjectsModalLabel" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header primary-modal-header">
                                <h5 class="modal-title primary-modal-title" id="uploadProjectsModalLabel">Upload Projects</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>

                            <form action="processUploadProjects.php" method="POST" enctype="multipart/form-data">
                                <div class="modal-body">
                                    <p><label for="fileToUpload">Select a CSV file following the <a class="template-link" href="https://docs.google.com/spreadsheets/d/1o_wLYf_WlaFy_USzcaOs4NAh_GcLdR9j2Y1k4T3U0IE/copy" target="_blank">correct upload template</a> to upload...</label></p>
                                    <input type="file" name="fileToUpload" id="fileToUpload">
                                </div>

                                <div class="modal-footer">
                                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-cloud-arrow-up"></i> Upload Projects</button>
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> Close</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <!-- End Upload Projects Modal -->

                <!-- Recalculate Automated Expenses Modal -->
                <div class="modal fade" tabindex="-1" role="dialog" id="recalculateExpensesModal" data-bs-backdrop="static" aria-labelledby="recalculateExpensesModalLabel" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header primary-modal-header">
                                <h5 class="modal-title primary-modal-title" id="recalculateExpensesModalLabel">Recalculate Automated Expenses</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>

                            <form action="recalculateAutomatedExpenses.php" method="POST" enctype="multipart/form-data">
                                <div class="modal-body">
                                    <p>Are you sure you want to recalculate all automated expenses for all projects for the current active period?
                                </div>

                                <div class="modal-footer">
                                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-calculator"></i> Recalculate Automated Expenses</button>
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> Close</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <!-- End Upload Projects Modal -->

                <!-- Edit Project Modal -->
                <div id="edit-project-modal-div"></div>
                <!-- End Edit Project Modal -->

                <!-- Delete Project Modal -->
                <div id="delete-project-modal-div"></div>
                <!-- End Delete Project Modal -->
                <!-- 
                    ### END MODALS ###
                -->

                <script>
                    // initialize variable to state if we've drawn the table or not
                    var drawn = 0; // assume we have not drawn the table (0)

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
                    <?php if (isset($PERMISSIONS["VIEW_PROJECTS_ALL"]) || (isset($PERMISSIONS["VIEW_PROJECTS_ASSIGNED"]) && $director_departments_count > 1)) { ?> 
                        // set the search filters to values we have saved in storage
                        <?php if (isset($PERMISSIONS["VIEW_PROJECTS_ALL"])) { ?> 
                            $('#search-fund').val(sessionStorage["BAP_ManageProjects_Search_Fund"]);
                        <?php } ?>
                        $('#search-dept').val(sessionStorage["BAP_ManageProjects_Search_Dept"]);
                    <?php } ?>

                    /** function to generate the projects table based on the period selected */
                    function searchProjects()
                    {
                        // get the value of the period we are searching
                        var period = document.getElementById("search-period").value;

                        if (period != "" && period != null && period != undefined)
                        {
                            // set the period as fixed
                            document.getElementById("fixed-period").value = period;

                            // update session storage stored search parameter
                            sessionStorage["BAP_ManageProjects_Search_Period"] = period;

                            <?php if (isset($PERMISSIONS["VIEW_PROJECTS_ALL"]) && $_SESSION["role"] == 1) { ?>
                                // update the form for exporting codes period
                                document.getElementById("export-period").value = period;
                            <?php } ?>

                            // if we have already drawn the table, destroy existing table
                            if (drawn == 1) { $("#projects").DataTable().destroy(); }

                            // initialize the projects table
                            var projects = $("#projects").DataTable({
                                ajax: {
                                    url: "ajax/projects/getProjects.php",
                                    type: "POST",
                                    data: {
                                        period: period
                                    },
                                    async: false,
                                },
                                autoWidth: false,
                                processing: true,
                                pageLength: <?php if (isset($USER_SETTINGS["page_length"])) { echo $USER_SETTINGS["page_length"]; } else { echo 10; } ?>,
                                lengthMenu: [ [10, 25, 50, 100, 250, -1], [10, 25, 50, 100, 250, "All"] ],
                                columns: [
                                    { data: "name", orderable: true, width: "22.5%" },
                                    { data: "code", orderable: true, width: "5%", className: "text-center" },
                                    { data: "fund", orderable: true, width: "5%", className: "text-center" },
                                    { data: "func", orderable: true, width: "5%", className: "text-center" },
                                    { data: "department", orderable: true, width: "22.5%", className: "text-center" },
                                    { data: "employees_count", orderable: true, width: "7%", className: "text-center" },
                                    { data: "revenues", orderable: true, width: "9%", class: "text-end" },
                                    { data: "expenses", orderable: true, width: "9%", class: "text-end" },
                                    { data: "net", orderable: true, width: "9%", class: "text-end" },
                                    <?php if (isset($PERMISSIONS["EDIT_PROJECTS"]) || isset($PERMISSIONS["DELETE_PROJECTS"])) { ?>
                                    { data: "actions", orderable: false, width: "7%" },
                                    <?php } else { ?>
                                    { data: "actions", orderable: false, visible: false },
                                    <?php } ?>
                                    { data: "export_code", orderable: false, visible: false },
                                    { data: "export_name", orderable: false, visible: false },
                                    { data: "revenues_calc", orderable: false, visible: false },
                                    { data: "expenses_calc", orderable: false, visible: false },
                                    { data: "net_calc", orderable: false, visible: false },
                                    { data: "status", orderable: false, visible: false }
                                ],
                                order: [
                                    [1, "asc"],
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
                                    updatePageSelection("projects");

                                    // style the net income box
                                    if (data["net_calc"] > 0) { $("td:eq(8)", row).addClass("project-net_profit"); }
                                    else if (data["net_calc"] < 0) { $("td:eq(8)", row).addClass("project-net_loss"); }
                                },
                                drawCallback: function ()
                                {
                                    var api = this.api();

                                    // get the sums
                                    let emps_sum = api.column(5, { search: "applied" }).data().sum().toFixed(0);
                                    let rev_sum = api.column(12, { search: "applied" }).data().sum().toFixed(2);
                                    let exp_sum = api.column(13, { search: "applied" }).data().sum().toFixed(2);
                                    let net_sum = api.column(14, { search: "applied" }).data().sum().toFixed(2);

                                    // update the table footer
                                    document.getElementById("sum-emps").innerHTML = numberWithCommas(emps_sum);
                                    document.getElementById("sum-rev").innerHTML = "$"+numberWithCommas(rev_sum);
                                    document.getElementById("sum-exp").innerHTML = "$"+numberWithCommas(exp_sum);
                                    if (net_sum < 0) { document.getElementById("sum-net").innerHTML = "($"+numberWithCommas(Math.abs(net_sum))+")"; }
                                    else { document.getElementById("sum-net").innerHTML = "$"+numberWithCommas(net_sum); }
                                },
                                <?php if (isset($PERMISSIONS["VIEW_PROJECTS_ALL"])) { ?> stateSave: true <?php } ?>
                            });

                            // mark that we have drawn the table
                            drawn = 1;

                            <?php if (isset($PERMISSIONS["VIEW_PROJECTS_ALL"])) { ?>
                                // calculate the total revenues
                                let total_revenue = parseFloat($.ajax({
                                    type: "POST",
                                    url: "ajax/projects/getTotalRevenue.php",
                                    data: {
                                        period: period
                                    },
                                    async: false
                                }).responseText);

                                // calculate the total expenses
                                let total_expenses = parseFloat($.ajax({
                                    type: "POST",
                                    url: "ajax/projects/getTotalExpenses.php",
                                    data: {
                                        period: period
                                    },
                                    async: false
                                }).responseText);

                                // calculate the net income
                                let net_income = total_revenue - total_expenses;

                                // display the global project data
                                document.getElementById("projects-total_revenues").innerHTML = "$" + numberWithCommas(total_revenue.toFixed(2));
                                document.getElementById("projects-total_expenses").innerHTML = "$" + numberWithCommas(total_expenses.toFixed(2));
                                document.getElementById("projects-net_income").innerHTML = "$" + numberWithCommas(net_income.toFixed(2));

                                // style the project net income table cell
                                if (net_income > 0) { document.getElementById("projects-net_income").classList.add("project-net_profit"); }
                                else if (net_income < 0) { document.getElementById("projects-net_income").classList.add("project-net_loss"); }
                                else 
                                { 
                                    document.getElementById("projects-net_income").classList.remove("project-net_profit"); 
                                    document.getElementById("projects-net_income").classList.remove("project-net_loss"); 
                                }
                            <?php } else if (isset($PERMISSIONS["VIEW_PROJECTS_ASSIGNED"])) { ?>
                                // calculate the total revenues
                                let my_total_revenue = parseFloat($.ajax({
                                    type: "POST",
                                    url: "ajax/projects/getMyTotalRevenue.php",
                                    data: {
                                        period: period
                                    },
                                    async: false
                                }).responseText);

                                // calculate the total expenses
                                let my_total_expenses = parseFloat($.ajax({
                                    type: "POST",
                                    url: "ajax/projects/getMyTotalExpenses.php",
                                    data: {
                                        period: period
                                    },
                                    async: false
                                }).responseText);

                                // calculate the net income
                                let my_net_income = my_total_revenue - my_total_expenses;

                                // display the global project data
                                document.getElementById("projects-total_revenues").innerHTML = "$" + numberWithCommas(my_total_revenue.toFixed(2));
                                document.getElementById("projects-total_expenses").innerHTML = "$" + numberWithCommas(my_total_expenses.toFixed(2));
                                document.getElementById("projects-net_income").innerHTML = "$" + numberWithCommas(my_net_income.toFixed(2));

                                // style the project net income table cell
                                if (my_net_income > 0) { document.getElementById("projects-net_income").classList.add("project-net_profit"); }
                                else if (my_net_income < 0) { document.getElementById("projects-net_income").classList.add("project-net_loss"); }
                                else 
                                { 
                                    document.getElementById("projects-net_income").classList.remove("project-net_profit"); 
                                    document.getElementById("projects-net_income").classList.remove("project-net_loss"); 
                                }
                            <?php } ?>

                            // search table by custom search filter
                            $('#search-all').keyup(function() {
                                projects.search($(this).val()).draw();
                                sessionStorage["BAP_ManageProjects_Search_All"] = $(this).val();
                            });

                            // search table by status
                            $('#search-status').change(function() {
                                sessionStorage["BAP_ManageProjects_Search_Status"] = $(this).val();
                                if ($(this).val() != "") { projects.columns(15).search("^" + $(this).val() + "$", true, false, true).draw(); }
                                else { projects.columns(15).search("").draw(); }
                            });

                            // search table by fund code
                            $('#search-fund').change(function() {
                                projects.columns(2).search($(this).val()).draw();
                                sessionStorage["BAP_ManageProjects_Search_Fund"] = $(this).val();
                            });

                            // search table by department
                            $('#search-dept').change(function() {
                                projects.columns(4).search($(this).val()).draw();
                                sessionStorage["BAP_ManageProjects_Search_Dept"] = $(this).val();
                            });

                            // function to clear search filters
                            $('#clearFilters').click(function() {
                                sessionStorage["BAP_ManageProjects_Search_Dept"] = "";
                                sessionStorage["BAP_ManageProjects_Search_Fund"] = "";
                                sessionStorage["BAP_ManageProjects_Search_Status"] = "";
                                sessionStorage["BAP_ManageProjects_Search_All"] = "";
                                $('#search-all').val("");
                                $('#search-status').val("");
                                $('#search-dept').val("");
                                $('#search-fund').val("");
                                projects.search("").columns().search("").draw();
                            });

                            // redraw table with current search fields
                            if ($('#search-status').val() != "") { projects.columns(15).search("^" + $('#search-status').val() + "$", true, false, true).draw(); }

                            <?php if ($_SESSION["role"] == 1) { ?>
                                // create the export buttons
                                new $.fn.dataTable.Buttons(projects, {
                                    buttons: [
                                        // CSV BUTTON
                                        {
                                            extend: "csv",
                                            exportOptions: {
                                                columns: [ 9, 10, 11, 12, 13, 14 ]
                                            },
                                            text: "<i class=\"fa-solid fa-file-csv fa-xl\"></i>",
                                            className: "btn btn-primary py-2 px-3 mx-1",
                                            title: "Projects List",
                                            titleAttr: "Export the projects list to a .csv file",
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
                                            exportOptions: {
                                                columns: [ 9, 10, 11, 12, 13, 14 ]
                                            },
                                            text: "<i class=\"fa-solid fa-file-excel fa-xl\"></i>",
                                            className: "btn btn-primary py-2 px-3 mx-1",
                                            title: "Projects List",
                                            titleAttr: "Export the projects list to a .xlsx file",
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
                                projects.buttons().container().appendTo("#export-buttons-div");
                            <?php } ?>
                        }
                        else { createStatusModal("alert", "Loading Projects Error", "Failed to load projects. You must select a period to display projects for."); }
                    }

                    // search projects from the default parameters
                    searchProjects();
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