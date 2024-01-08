<?php 
    include_once("header.php");

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1) 
    { 
        if (isset($PERMISSIONS["VIEW_REVENUES_ALL"]) || isset($PERMISSIONS["VIEW_REVENUES_ASSIGNED"]))
        {
            // connect to the database
            $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

            // initialize an array to store all periods; then get all periods and store in the array
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
                    // initialize the variable to indicate if we have drawn the table
                    var drawn = 0;

                    /** function to create the projects selection dropdown */
                    function createProjectsDropdown()
                    {
                        // get the selected period
                        let period = $("#search-period").val();

                        // get the currently selected project
                        let selected_project_code = document.getElementById("search-project").value;

                        // get the projectd dropdown
                        let content = $.ajax({
                            type: "POST",
                            url: "ajax/projects/getProjectsDropdown.php",
                            data: {
                                period: period
                            },
                            async: false,
                        }).responseText;

                        // fill dropdown and select the previously selected option
                        document.getElementById("search-project").innerHTML = content;
                        document.getElementById("search-project").value = selected_project_code;
                    }

                    /** function to add a new service */
                    function addRevenue()
                    {
                        // get the fixed period name
                        let period = encodeURIComponent(document.getElementById("fixed-period").value);
                        
                        // get revenue details from the modal
                        let name = encodeURIComponent(document.getElementById("revenue-name").value);
                        let desc = encodeURIComponent(document.getElementById("revenue-desc").value);
                        let date = encodeURIComponent(document.getElementById("revenue-date").value);
                        let rev = encodeURIComponent(document.getElementById("revenue-total").value);
                        let fund = encodeURIComponent(document.getElementById("revenue-fund").value);
                        let loc = encodeURIComponent(document.getElementById("revenue-loc").value);
                        let src = encodeURIComponent(document.getElementById("revenue-src").value);
                        let func = encodeURIComponent(document.getElementById("revenue-func").value);
                        let proj = encodeURIComponent(document.getElementById("revenue-proj").value);
                        let quantity = encodeURIComponent(document.getElementById("revenue-qty").value);

                        // create the string of data to send
                        let sendString = "period="+period+"&name="+name+"&desc="+desc+"&date="+date+"&revenue="+rev+"&fund="+fund+"&loc="+loc+"&src="+src+"&func="+func+"&proj="+proj+"&quantity="+quantity;
                        
                        // send the data to process the add customer request
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/revenues/addRevenue.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // create the status modal
                                let status_title = "Add Revenue Status";
                                let status_body = this.responseText;
                                createStatusModal("refresh", status_title, status_body);

                                // hide the current modal
                                $("#addRevenueModal").modal("hide");
                            }
                        };
                        xmlhttp.send(sendString);
                    }

                    /** function to get the delete revenue modal */
                    function getDeleteRevenueModal(id)
                    {
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/revenues/getDeleteRevenueModal.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                document.getElementById("delete-revenue-modal-div").innerHTML = this.responseText;     

                                // display the edit customer modal
                                $("#deleteRevenueModal").modal("show");
                            }
                        };
                        xmlhttp.send("id="+id);
                    }
                    
                    /** function to delete the revenue */
                    function deleteRevenue(id)
                    {
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/revenues/deleteRevenue.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // create the status modal
                                let status_title = "Delete Revenue Status";
                                let status_body = this.responseText;
                                createStatusModal("refresh", status_title, status_body);

                                // hide the current modal
                                $("#deleteRevenueModal").modal("hide");
                            }
                        };
                        xmlhttp.send("id="+id);
                    }

                    /** function to get the edit revenue modal */
                    function getEditRevenueModal(id)
                    {
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/revenues/getEditRevenueModal.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                document.getElementById("edit-revenue-modal-div").innerHTML = this.responseText;     
                                $("#editRevenueModal").modal("show");

                                $(function() {
                                    $("#edit-revenue-date").daterangepicker({
                                        singleDatePicker: true,
                                        showDropdowns: true,
                                        minYear: 2000,
                                        maxYear: <?php echo date("Y") + 10; ?>
                                    });
                                });
                            }
                        };
                        xmlhttp.send("id="+id);
                    }
                    
                    /** function to edit the revenue */
                    function editRevenue(id)
                    {
                        // get revenue details from the modal
                        let name = encodeURIComponent(document.getElementById("edit-revenue-name").value);
                        let desc = encodeURIComponent(document.getElementById("edit-revenue-desc").value);
                        let date = encodeURIComponent(document.getElementById("edit-revenue-date").value);
                        let rev = encodeURIComponent(document.getElementById("edit-revenue-total").value);
                        let fund = encodeURIComponent(document.getElementById("edit-revenue-fund").value);
                        let loc = encodeURIComponent(document.getElementById("edit-revenue-loc").value);
                        let src = encodeURIComponent(document.getElementById("edit-revenue-src").value);
                        let func = encodeURIComponent(document.getElementById("edit-revenue-func").value);
                        let proj = encodeURIComponent(document.getElementById("edit-revenue-proj").value);
                        let quantity = encodeURIComponent(document.getElementById("edit-revenue-qty").value);

                        // create the string of data to send
                        let sendString = "id="+id+"&name="+name+"&desc="+desc+"&date="+date+"&revenue="+rev+"&fund="+fund+"&loc="+loc+"&src="+src+"&func="+func+"&proj="+proj+"&quantity="+quantity;

                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/revenues/editRevenue.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // create the status modal
                                let status_title = "Edit Revenue Status";
                                let status_body = this.responseText;
                                createStatusModal("refresh", status_title, status_body);

                                // hide the current modal
                                $("#editRevenueModal").modal("hide");
                            }
                        };
                        xmlhttp.send(sendString);
                    }
                </script>

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
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text h-100" id="nav-search-icon">
                                                        <i class="fa-solid fa-calendar-days"></i>
                                                    </span>
                                                </div>
                                                <input id="fixed-period" type="hidden" value="" aria-hidden="true">
                                                <select class="form-select" id="search-period" name="search-period" onchange="createProjectsDropdown(); searchRevenues();">
                                                    <?php
                                                        for ($p = 0; $p < count($periods); $p++)
                                                        {
                                                            echo "<option value='".$periods[$p]["name"]."'>".$periods[$p]["name"]."</option>";
                                                        }
                                                    ?>
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

                                                <!-- Filter By Project -->
                                                <div class="row d-flex justify-content-between align-items-center mx-0 mt-0 mb-2">
                                                    <div class="col-4 ps-0 pe-1">
                                                        <label for="search-project">Project:</label>
                                                    </div>

                                                    <div class="col-8 ps-1 pe-0">
                                                        <select class="form-select" id="search-project" name="search-project">
                                                            <option></option>
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

                            <!-- Page Header -->
                            <div class="col-12 col-sm-12 col-md-12 col-lg-12 col-xl-8 col-xxl-8 p-0">
                                <h1 class="m-0">Other Revenues</h1>
                                <p class="report-description m-0">View a list of all additional revenues that do not fall under a service.</p>
                            </div>

                            <!-- Page Management Dropdown -->
                            <div class="col-6 col-sm-4 col-md-4 col-lg-3 col-xl-2 col-xxl-2 d-flex justify-content-end p-0">
                                <?php if (isset($PERMISSIONS["ADD_REVENUES"])) { ?>
                                    <div class="dropdown float-end">
                                        <button class="btn btn-primary dropdown-toggle" type="button" id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">
                                            Manage Revenues
                                        </button>
                                        <ul class="dropdown-menu p-0" aria-labelledby="dropdownMenuButton1">
                                            <li><button class="dropdown-item quickNav-dropdown-item text-center w-100 px-3 py-2 rounded-0" type="button" data-bs-toggle="modal" data-bs-target="#addRevenueModal">Add Revenue</button></li>
                                        </ul>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>
                    </div>

                    <div class="row report-body d-none m-0" id="revenues-table-div">
                        <!-- Revenues Table -->
                        <table id="revenues" class="report_table w-100">
                            <thead>
                                <tr>
                                    <th class="text-center py-1 px-2" colspan="4">Revenue Details</th>
                                    <th class="text-center py-1 px-2" colspan="5">WUFAR Codes</th>
                                    <th class="text-center py-1 px-2" colspan="2"><span id="table-period_totals-label"></span> Totals</th>
                                    <th class="text-center py-1 px-2" rowspan="2">Actions</th>
                                </tr>

                                <tr>
                                    <th class="text-center py-1 px-2">ID</th>
                                    <th class="text-center py-1 px-2">Name</th>
                                    <th class="text-center py-1 px-2">Description</th>
                                    <th class="text-center py-1 px-2">Date</th>
                                    <th class="text-center py-1 px-2">Fund</th>
                                    <th class="text-center py-1 px-2">Location</th>
                                    <th class="text-center py-1 px-2">Source</th>
                                    <th class="text-center py-1 px-2">Function</th>
                                    <th class="text-center py-1 px-2">Project</th>
                                    <th class="text-center py-1 px-2">Quantity</th>
                                    <th class="py-1 px-2" style="text-align: center !important;">Revenue</th>
                                </tr>
                            </thead>

                            <tfoot>
                                <tr>
                                    <th class="py-1 px-2" style="text-align: right !important;" colspan="10">TOTAL:</th>
                                    <th class="py-1 px-2" style="text-align: right !important;" id="sum-revenues"></th> <!-- Revenues Total -->
                                    <th class="py-1 px-2"></th>
                                </tr>
                            </tfoot>
                        </table>
                        <?php createTableFooterV2("revenues", "BAP_OtherRevenues_PageLength", $USER_SETTINGS["page_length"], true, true); ?>
                    </div>
                </div>

                <!--
                    ### MODALS ###
                -->
                <!-- Add Revenue Modal -->
                <div class="modal fade" tabindex="-1" role="dialog" id="addRevenueModal" aria-labelledby="addRevenueModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg" role="document">
                        <div class="modal-content">
                            <div class="modal-header primary-modal-header">
                                <h5 class="modal-title primary-modal-title" id="addRevenueModalLabel">Add Revenue</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>

                            <div class="modal-body px-5 py-4">
                                <div class="form-row d-flex justify-content-center align-items-center mb-3">
                                    <!-- Revenue Name -->
                                    <div class="form-group col px-1">
                                        <label for="revenue-name"><span class="required-field">*</span> Revenue Name:</label>
                                        <input type="text" class="form-control w-100" id="revenue-name" name="revenue-name" required>
                                    </div>
                                </div>

                                <div class="form-row d-flex justify-content-center align-items-center mb-3">
                                    <!-- Description -->
                                    <div class="form-group col px-1">
                                        <label for="revenue-desc">Description:</label>
                                        <textarea class="form-control w-100" id="revenue-desc" name="revenue-desc"></textarea>
                                    </div>
                                </div>

                                <div class="form-row d-flex justify-content-center align-items-center mb-3">
                                    <!-- Date Provided -->
                                    <div class="form-group col px-1">
                                        <label for="revenue-date"><span class="required-field">*</span> Date Provided:</label>
                                        <input type="text" class="form-control w-100" id="revenue-date" name="revenue-date" value="<?php echo date("m/d/Y"); ?>" required>
                                    </div>
                                </div>

                                <div class="form-row d-flex justify-content-center align-items-center mb-3">
                                    <!-- Revenue Amount -->
                                    <div class="form-group col px-1">
                                        <label for="revenue-total"><span class="required-field">*</span> Revenue Amount:</label>
                                        <input type="number" class="form-control w-100" id="revenue-total" name="revenue-total" value="0.00" required>
                                    </div>

                                    <!-- Quantity -->
                                    <div class="form-group col px-1">
                                        <label for="revenue-qty"><span class="required-field">*</span> Quantity:</label>
                                        <input type="number" class="form-control w-100" id="revenue-qty" name="revenue-qty" value="0" required>
                                    </div>
                                </div>

                                <h3 class="text-center m-0"><span class="required-field">*</span> WUFAR Codes</h3>
                                <div class="form-row d-flex justify-content-center align-items-center mb-2">
                                    <!-- Fund Code -->
                                    <div class="form-group col px-1">
                                        <label for="revenue-fund">Fund:</label>
                                        <input type="text" class="form-control w-100" id="revenue-fund" name="revenue-fund" required>
                                    </div>

                                    <!-- Location Code -->
                                    <div class="form-group col px-1">
                                        <label for="revenue-loc">Location:</label>
                                        <input type="text" class="form-control w-100" id="revenue-loc" name="revenue-loc" required>
                                    </div>

                                    <!-- Source Code -->
                                    <div class="form-group col px-1">
                                        <label for="revenue-src">Source:</label>
                                        <input type="text" class="form-control w-100" id="revenue-src" name="revenue-src" required>
                                    </div>

                                    <!-- Function Code -->
                                    <div class="form-group col px-1">
                                        <label for="revenue-func">Function:</label>
                                        <input type="text" class="form-control w-100" id="revenue-func" name="revenue-func" required>
                                    </div>

                                    <!-- Project Code -->
                                    <div class="form-group col px-1">
                                        <label for="revenue-proj">Project:</label>
                                        <select class="form-select w-100" id="revenue-proj" name="revenue-proj" value="<?php echo date("m/d/Y"); ?>" required>
                                            <option></option>
                                            <?php
                                                $getProjectCodes = mysqli_query($conn, "SELECT code, name FROM projects ORDER BY code ASC");
                                                if (mysqli_num_rows($getProjectCodes) > 0) // projects found; continue
                                                {
                                                    while ($project = mysqli_fetch_array($getProjectCodes))
                                                    {
                                                        $code = $project["code"];
                                                        $name = $project["name"];
                                                        $display = $code . " - " . $name;
                                                        echo "<option value='".$code."'>".$display."</option>";
                                                    }
                                                }
                                            ?>
                                        </select>
                                    </div>
                                </div>

                                <!-- Required Field Indicator -->
                                <div class="row justify-content-center">
                                    <div class="col text-center fst-italic">
                                        <span class="required-field">*</span> indicates a required field
                                    </div>
                                </div>
                            </div>

                            <div class="modal-footer">
                                <button type="button" class="btn btn-primary" onclick="addRevenue();"><i class="fa-solid fa-plus"></i> Add Revenue</button>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> Close</button>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- End Add Revenue Modal -->

                <!-- Edit Revenue Modal -->
                <div id="edit-revenue-modal-div"></div>
                <!-- End Edit Revenue Modal -->

                <!-- Delete Revenue Modal -->
                <div id="delete-revenue-modal-div"></div>
                <!-- End Delete Revenue Modal -->

                <script>
                    // get the current active period
                    let active_period = "<?php echo $active_period_label; ?>";
                    
                    // set page length to prior saved state
                    let saved_page_length = sessionStorage["BAP_OtherRevenues_PageLength"];
                    if (saved_page_length != "" && saved_page_length != null && saved_page_length != undefined)
                    {
                        $("#revenues-DT_PageLength").val(sessionStorage["BAP_OtherRevenues_PageLength"]);
                    }

                    // run the function to create the projects dropdown
                    createProjectsDropdown();

                    // set the search filters to values we have saved in storage
                    $('#search-all').val(sessionStorage["BAP_OtherRevenues_Search_All"]);
                    if (sessionStorage["BAP_OtherRevenues_Search_Period"] != "" && sessionStorage["BAP_OtherRevenues_Search_Period"] != null && sessionStorage["BAP_OtherRevenues_Search_Period"] != undefined) { $('#search-period').val(sessionStorage["BAP_OtherRevenues_Search_Period"]); }
                    else { $('#search-period').val(active_period); } // no period set; default to active period 
                    $('#search-project').val(sessionStorage["BAP_OtherRevenues_Search_Project"]);

                    /** function to generate the invoices table based on the period selected */
                    function searchRevenues()
                    {
                        // get the value of the period we are searching
                        var period = document.getElementById("search-period").value;

                        if (period != "" && period != null && period != undefined)
                        {
                            // set the fixed period
                            document.getElementById("fixed-period").value = period;

                            // update the table header
                            document.getElementById("table-period_totals-label").innerHTML = period;

                            // update session storage stored search parameter
                            sessionStorage["BAP_OtherRevenues_Search_Period"] = period;

                            // if we have already drawn the table, destroy existing table
                            if (drawn == 1) { $("#revenues").DataTable().destroy(); }

                            var revenues = $("#revenues").DataTable({
                                ajax: {
                                    url: "ajax/revenues/getRevenues.php",
                                    type: "POST",
                                    data: {
                                        period: period
                                    }
                                },
                                autoWidth: false,
                                pageLength: <?php if (isset($USER_SETTINGS["page_length"])) { echo $USER_SETTINGS["page_length"]; } else { echo 10; } ?>,
                                lengthMenu: [ [10, 25, 50, 100, 250, -1], [10, 25, 50, 100, 250, "All"] ],
                                columns: [
                                    { data: "revenue_id", orderable: true, width: "5%", className: "text-center" },
                                    { data: "name", orderable: true, width: "20%", className: "text-center" },
                                    { data: "description", orderable: true, width: "15%", className: "text-center" },
                                    { data: "date", orderable: true, width: "7%", className: "text-center" },
                                    { data: "fund", orderable: true, width: "6%", className: "text-center" },
                                    { data: "loc", orderable: true, width: "6%", className: "text-center" },
                                    { data: "src", orderable: true, width: "6%", className: "text-center" },
                                    { data: "func", orderable: true, width: "6%", className: "text-center" },
                                    { data: "proj", orderable: true, width: "6%", className: "text-center" },
                                    { data: "quantity", orderable: true, width: "7.5%", className: "text-center" },
                                    { data: "revenue", orderable: true, width: "8%", className: "text-end" },
                                    <?php if (isset($PERMISSIONS["EDIT_REVENUES"]) || isset($PERMISSIONS["DELETE_REVENUES"])) { ?>
                                    { data: "actions", orderable: false, width: "7.5%" },
                                    <?php } else { ?>
                                    { data: "actions", orderable: false, visible: false },
                                    <?php } ?>
                                    { data: "calc_revenue", orderable: false, visible: false }
                                ],
                                dom: 'rt',
                                language: {
                                    search: '<div class="input-group mb-1"><div class="input-group-prepend"><span class="d-flex align-items-center h-100 mx-2" id="nav-search-icon"><i class="fa fa-search" aria-hidden="true"></i></span></div>_INPUT_</div>',
                                    lengthMenu: '<div class="mx-1 mb-1">Show _MENU_ entries</div>',
                                    info: '<div class="mx-1">Showing _START_ to _END_ of _TOTAL_ entries</div>'
                                },
                                drawCallback: function ()
                                {
                                    var api = this.api();

                                    // get the sum of all filtered
                                    let total_sum = api.column(12, { search: "applied" }).data().sum().toFixed(2);

                                    // update the table footer
                                    document.getElementById("sum-revenues").innerHTML = "$"+numberWithCommas(total_sum);
                                },
                                stateSave: true,
                                rowCallback: function (row, data, index)
                                {
                                    updatePageSelection("revenues");
                                },
                            });

                            // mark that we have drawn the table
                            drawn = 1;

                            // search table by custom search filter
                            $('#search-all').keyup(function() {
                                revenues.search($(this).val()).draw();
                                sessionStorage["BAP_OtherRevenues_Search_All"] = $(this).val();
                            });
                            
                            // search table by project code
                            $('#search-project').change(function() {
                                revenues.columns(8).search($(this).val()).draw();
                                sessionStorage["BAP_OtherRevenues_Search_Project"] = $(this).val();
                            });

                            // function to clear search filters
                            $('#clearFilters').click(function() {
                                sessionStorage["BAP_OtherRevenues_Search_Project"] = "";
                                sessionStorage["BAP_OtherRevenues_Search_All"] = "";
                                $('#search-all').val("");
                                $('#search-project').val("");
                                revenues.search("").columns().search("").draw();
                            });

                            // display the table
                            document.getElementById("revenues-table-div").classList.remove("d-none");
                        }
                        else { createStatusModal("alert", "Loading Revenues Error", "Failed to load revenues. You must select a period to display revenues for."); }
                    }

                    // search revenues from the default parameters
                    searchRevenues();

                    $(function() {
                        $("#revenue-date").daterangepicker({
                            singleDatePicker: true,
                            showDropdowns: true,
                            minYear: 2000,
                            maxYear: <?php echo date("Y") + 10; ?>
                        });
                    });
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