<?php 
    include_once("header.php");

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1) 
    { 
        if ((isset($PERMISSIONS["VIEW_EMPLOYEES_ALL"]) && isset($PERMISSIONS["EDIT_EMPLOYEES"])) || (isset($PERMISSIONS["VIEW_EMPLOYEES_ASSIGNED"])))
        {
            // connect to the database
            $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

            // initialize an array to store all periods; then get all periods and store in the array
            $periods = [];
            $getPeriods = mysqli_query($conn, "SELECT id, name, active, start_date, end_date FROM `periods` ORDER BY active DESC, name ASC");
            if (mysqli_num_rows($getPeriods) > 0) // periods exist
            {
                while ($period = mysqli_fetch_array($getPeriods))
                {
                    // store period's data in array
                    $periods[] = $period;

                    // store the acitve period's name
                    if ($period["active"] == 1) 
                    { 
                        $active_period_label = $period["name"]; 
                        $active_start_date = date("m/d/Y", strtotime($period["start_date"]));
                        $active_end_date = date("m/d/Y", strtotime($period["end_date"])); 
                    }
                }
            }

            ?>
                <script>
                    /** function to create and display the modal to accept an employee change request */
                    function getAcceptChangeRequestModal(request_id)
                    {
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/employees/getAcceptChangeRequestModal.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // display the view caseload changes modal
                                document.getElementById("accept-request-modal-div").innerHTML = this.responseText;     
                                $("#acceptChangeRequestModal").modal("show");
                            }
                        };
                        xmlhttp.send("request_id="+request_id);
                    }

                    /** function to create and display the modal to reject an employee change request */
                    function getRejectChangeRequestModal(request_id)
                    {
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/employees/getRejectChangeRequestModal.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // display the view caseload changes modal
                                document.getElementById("reject-request-modal-div").innerHTML = this.responseText;     
                                $("#rejectChangeRequestModal").modal("show");
                            }
                        };
                        xmlhttp.send("request_id="+request_id);
                    }

                    /** function to create and display the modal to edit an employee change request */
                    function getEditChangeRequestModal(request_id)
                    {
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/employees/getEditChangeRequestModal.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // display the view caseload changes modal
                                document.getElementById("edit-request-modal-div").innerHTML = this.responseText;     
                                $("#editChangeRequestModal").modal("show");
                            }
                        };
                        xmlhttp.send("request_id="+request_id);
                    }

                    /** function to accept an employee change request */
                    function acceptChangeRequest(request_id)
                    {
                        // get form fields
                        let new_days = document.getElementById("acr-new_days").value;
                        let new_salary = document.getElementById("acr-new_salary").value;
                        let notes = document.getElementById("acr-notes").value;

                        // send the data to transfer the caseload
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/employees/acceptChangeRequest.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // create the status modal
                                let status_title = "Accept Change Request Status";
                                let status_body = this.responseText;
                                createStatusModal("refresh", status_title, status_body);

                                // hide the current modal
                                $("#acceptChangeRequestModal").modal("hide");
                            }
                        };
                        xmlhttp.send("request_id="+request_id+"&new_days="+new_days+"&new_salary="+new_salary+"&reason="+encodeURIComponent(notes));
                    }


                    /** function to reject an employee change request */
                    function rejectChangeRequest(request_id)
                    {
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/employees/rejectChangeRequest.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // create the status modal
                                let status_title = "Reject Change Request Status";
                                let status_body = this.responseText;
                                createStatusModal("refresh", status_title, status_body);

                                // hide the current modal
                                $("#rejectChangeRequestModal").modal("hide");
                            }
                        };
                        xmlhttp.send("request_id="+request_id);
                    }

                    /** function to edit an employee change request */
                    function editChangeRequest(request_id)
                    {
                        // get the form fields
                        let new_days = document.getElementById("ecr-new_days").value;
                        let comment = document.getElementById("ecr-notes").value;

                        // send the data to create the edit employee modal
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/employees/editChangeRequest.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // create the status modal
                                let status_title = "Edit Change Request Status";
                                let status_body = this.responseText;
                                createStatusModal("refresh", status_title, status_body);

                                // hide the current modal
                                $("#editChangeRequestModal").modal("hide");
                            }
                        }
                        xmlhttp.send("request_id="+request_id+"&new_days="+new_days+"&comment="+encodeURIComponent(comment));
                    }

                    /** function to estimate an employee's yearly salary based on day change */
                    function estimateYearlySalary(employee_id)
                    {
                        // get form fields
                        let days = document.getElementById("ecr-new_days").value;
                        let period_id = document.getElementById("ecr-period_id").value;

                        // send request
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/employees/estimateYearlySalary.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // update estimated salary field
                                document.getElementById("ecr-estimated_salary").value = this.responseText;
                            }
                        }
                        xmlhttp.send("employee_id="+employee_id+"&period_id="+period_id+"&days="+days);
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
                                                <select class="form-select" id="search-period" name="search-period" onchange="showRequests();">
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
                                                            <select class="form-select" id="search-status" name="search-status">
                                                                <option value="">Show All</option>
                                                                <option value="Pending" style="background-color: #6c757d; color: #ffffff" selected>Pending</option>
                                                                <option value="Accepted" style="background-color: #006900; color: #ffffff">Accepted</option>
                                                                <option value="Rejected" style="background-color: #e40000; color: #ffffff">Rejected</option>
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
                                    <h2 class="m-0">Employee Change Requests</h2>
                                </div>

                                <!-- Page Management Dropdown -->
                                <div class="col-6 col-sm-4 col-md-4 col-lg-3 col-xl-2 col-xxl-2 p-0"></div>
                            </div>
                        </div>

                        <table id="change_requests" class="report_table w-100">
                            <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th>Days</th>
                                    <th>Salary</th>
                                    <th>Reason</th>
                                    <th>Requested By</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                        </table>
                        <?php createTableFooterV2("change_requests", "BAP_EmployeeChangeRequests_PageLength", $USER_SETTINGS["page_length"], true, true); ?>
                    </div>
                </div>

                <!--
                    ### MODALS ###
                -->
                <!-- Accept Change Request Modal -->
                <div id="accept-request-modal-div"></div>
                <!-- End Accept Change Request Modal -->

                <!-- Reject Change Request Modal -->
                <div id="reject-request-modal-div"></div>
                <!-- End Reject Change Request Modal -->

                <!-- Edit Change Request Modal -->
                <div id="edit-request-modal-div"></div>
                <!-- End Edit Change Request Modal -->
                <!--
                    ### END MODALS ###
                -->

                <script>
                    // initialize variable to state if we've drawn the table or not
                    var drawn = 0; // assume we have not drawn the table (0)

                    // get the current active period
                    let active_period = "<?php echo $active_period_label; ?>"; 

                    // set the search filters to values we have saved in storage
                    if (sessionStorage["BAP_EmployeeChangeRequests_Search_Period"] != "" && sessionStorage["BAP_EmployeeChangeRequests_Search_Period"] != null && sessionStorage["BAP_EmployeeChangeRequests_Search_Period"] != undefined) { $('#search-period').val(sessionStorage["BAP_EmployeeChangeRequests_Search_Period"]); }
                    else { $('#search-period').val(active_period); } // no period set; default to active period 

                    /** function to show employee data for the selected period */
                    function showRequests()
                    {
                        // get the value of the period we are searching
                        var period = document.getElementById("search-period").value;

                        if (period != "" && period != null && period != undefined)
                        {
                            // update session storage stored search parameter
                            sessionStorage["BAP_EmployeeChangeRequests_Search_Period"] = period;

                            // set the fixed period
                            document.getElementById("fixed-period").value = period;

                            // if we have already drawn the table, destroy existing table
                            if (drawn == 1) { $("#change_requests").DataTable().destroy(); }

                            // initialize the caseloads_transfers table
                            var change_requests = $("#change_requests").DataTable({
                                ajax: {
                                    url: "ajax/employees/getEmployeeChangeRequests.php",
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
                                    { data: "employee", orderable: true, width: "15%" },
                                    { data: "days", orderable: true, width: "10%" },
                                    { data: "salary", orderable: true, width: "12.5%" },
                                    { data: "reason", orderable: true, width: "22.5%" },
                                    { data: "request_details", orderable: true, width: "17.5%" },
                                    { data: "status", orderable: true, width: "12.5%" },
                                    { data: "actions", orderable: false, width: "10%" },
                                    { data: "filter_status", orderable: true, visible: false },
                                ],
                                dom: 'rt',
                                language: {
                                    search: '<div class="input-group mb-1"><div class="input-group-prepend"><span class="d-flex align-items-center h-100 mx-2" id="nav-search-icon"><i class="fa fa-search" aria-hidden="true"></i></span></div>_INPUT_</div>',
                                    lengthMenu: '<div class="mx-1 mb-1">Show _MENU_ entries</div>',
                                    info: '<div class="mx-1">Showing _START_ to _END_ of _TOTAL_ entries</div>'
                                },
                                saveState: false,
                                rowCallback: function (row, data, index)
                                {
                                    updatePageSelection("change_requests");
                                }
                            });

                            // mark that we have drawn the table
                            drawn = 1;

                            // search table by custom search filter
                            $('#search-all').keyup(function() {
                                change_requests.search($(this).val()).draw();
                                sessionStorage["BAP_EmployeeChangeRequests_Search_All"] = $(this).val();
                            });

                            // search table by request status
                            $('#search-status').change(function() {
                                sessionStorage["BAP_CaseloadTransfers_Search_Status"] = $(this).val();
                                if ($(this).val() != "") { change_requests.columns(7).search("^" + $(this).val() + "$", true, false, true).draw(); }
                                else { change_requests.columns(7).search("").draw(); }
                            });

                            // function to clear search filters
                            $('#clearFilters').click(function() {
                                sessionStorage["BAP_EmployeeChangeRequests_Search_All"] = "";
                                sessionStorage["BAP_EmployeeChangeRequests_Search_Status"] = "";
                                $('#search-all').val("");
                                $('#search-status').val("");
                                change_requests.search("").columns().search("").draw();
                            });

                            // redraw table with current search fields
                            if ($('#search-all').val() != "") { change_requests.search($('#search-all').val()).draw(); }
                            if ($('#search-status').val() != "") { change_requests.columns(7).search("^" + $('#search-status').val() + "$", true, false, true).draw(); }
                        }
                    }

                    // display change requests table based on default parameters
                    showRequests();
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