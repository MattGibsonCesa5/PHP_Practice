<?php 
    include_once("header.php");

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1) 
    { 
        if (isset($PERMISSIONS["VIEW_CASELOADS_ALL"]))
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

                <script>
                    /** function to create and display the modal to transfer a caseload from one therapist to another */
                    function getTransferCaseloadModal(case_id, request_id)
                    {
                        // send the data to create the view caseload changes modal
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/caseloads/getTransferCaseloadModal.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // display the view caseload changes modal
                                document.getElementById("transfer-caseload-modal-div").innerHTML = this.responseText;     
                                $("#transferCaseloadModal").modal("show");

                                // initialize fields
                                $(function() {
                                    $("#transfer-transfer_date").datepicker();
                                    $("#transfer-end_date").datepicker();
                                    $("#transfer-new_caseload").selectize();
                                });
                            }
                        };
                        xmlhttp.send("case_id="+case_id+"&request_id="+request_id);
                    }

                    /** function to transfer a caseload from one therapist to another */
                    function transferCaseload(case_id)
                    {
                        // get form fields
                        let request_id = document.getElementById("transfer-request_id").value;
                        let new_caseload = document.getElementById("transfer-new_caseload").value;
                        let transfer_date = document.getElementById("transfer-transfer_date").value;
                        let end_date = document.getElementById("transfer-end_date").value;
                        let frequency = document.getElementById("transfer-frequency").value;
                        let uos = document.getElementById("transfer-uos").value;
                        let days = document.getElementById("transfer-days").value;
                        let classroom = document.getElementById("transfer-classroom").value;

                        // get IEP status
                        let iep_status = 0;
                        if ($("#tranfer-IEP_status").is(":checked")) { iep_status = 1; }

                        // build the string of data to send
                        let sendString = "case_id="+case_id+"&new_caseload="+new_caseload+"&transfer_date="+transfer_date+"&end_date="+end_date+"&frequency="+frequency+"&uos="+uos+"&IEP_status="+iep_status+"&request_id="+request_id+"&days="+days+"&classroom="+classroom;

                        // send the data to transfer the caseload
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/caseloads/transferCaseload.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // create the status modal
                                let status_title = "Transfer Caseload Status";
                                let status_body = this.responseText;
                                createStatusModal("refresh", status_title, status_body);

                                // hide the current modal
                                $("#transferCaseloadModal").modal("hide");
                            }
                        };
                        xmlhttp.send(sendString);
                    }

                    /** function to get the modal to reject a caseload transfer */
                    function getRejectTransferCaseloadModal(request_id)
                    {
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/caseloads/getRejectTransferCaseloadModal.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // display the view caseload changes modal
                                document.getElementById("reject_transfer-caseload-modal-div").innerHTML = this.responseText;     
                                $("#rejectTransferCaseloadModal").modal("show");

                                // initialize fields
                                $(function() {
                                    $("#transfer-transfer_date").datepicker();
                                    $("#transfer-end_date").datepicker();
                                    $("#transfer-new_caseload").selectize();
                                });
                            }
                        };
                        xmlhttp.send("request_id="+request_id);
                    }

                    /** function to reject a caseload transfer request */
                    function rejectTransferCaseload(request_id)
                    {
                        // send the data to transfer the caseload
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/caseloads/rejectTransferCaseload.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // create the status modal
                                let status_title = "Reject Transfer Caseload Status";
                                let status_body = this.responseText;
                                createStatusModal("refresh", status_title, status_body);

                                // hide the current modal
                                $("#rejectTransferCaseloadModal").modal("hide");
                            }
                        };
                        xmlhttp.send("request_id="+request_id);
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
                                                <select class="form-select" id="search-period" name="search-period" onchange="searchTransferRequests();">
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
                                                                <option value="Transferred" style="background-color: #006900; color: #ffffff">Transferred</option>
                                                                <option value="Rejected" style="background-color: #e40000; color: #ffffff">Rejected</option>
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
                                    <h1 class="m-0">Caseload Transfer Requests</h1>
                                </div>

                                <!-- Page Management Dropdown -->
                                <div class="col-6 col-sm-4 col-md-4 col-lg-3 col-xl-2 col-xxl-2 p-0"></div>
                            </div>
                        </div>

                        <table id="caseloads_transfers" class="report_table w-100">
                            <thead>
                                <tr>
                                    <th class="text-center py-1 px-2">Current Caseload</th>
                                    <th class="text-center py-1 px-2">Transfer To Caseload</th>
                                    <th class="text-center py-1 px-2">Student Name</th>
                                    <th class="text-center py-1 px-2">Request Comments</th>
                                    <th class="text-center py-1 px-2">Requested By</th>
                                    <th class="text-center py-1 px-2">Transfer Status</th>
                                    <th class="text-center py-1 px-2">Actions</th>
                                </tr>
                            </thead>
                        </table>
                        <?php createTableFooter("caseloads_transfers", "BAP_CaseloadTransfers_PageLength", $USER_SETTINGS["page_length"], true, true); ?>
                    </div>
                </div>

                <!--
                    ### MODALS ###
                -->
                <!-- Transfer Caseload Modal -->
                <div id="transfer-caseload-modal-div"></div>
                <!-- End Transfer Caseload Modal -->

                <!-- Reject Transfer Caseload Modal -->
                <div id="reject_transfer-caseload-modal-div"></div>
                <!-- End Reject Transfer Caseload Modal -->
                <!--
                    ### END MODALS ###
                -->

                <script>
                    // initialize variable to indicate if we have drawn the caseloads table
                    var drawn = 0; // assume we have not drawn the table yet

                    // get the current active period
                    let active_period = "<?php echo $active_period_label; ?>"; 

                    // set the search filters to values we have saved in storage
                    if (sessionStorage["BAP_CaseloadTransfers_Search_Period"] != "" && sessionStorage["BAP_CaseloadTransfers_Search_Period"] != null && sessionStorage["BAP_CaseloadTransfers_Search_Period"] != undefined) { $('#search-period').val(sessionStorage["BAP_CaseloadTransfers_Search_Period"]); }
                    else { $('#search-period').val(active_period); } // no period set; default to active period 

                    /** function to search for caseload transfer requests */
                    function searchTransferRequests()
                    {
                        // get the value of the period we are searching
                        var period = document.getElementById("search-period").value;

                        if (period != "" && period != null && period != undefined)
                        {
                            // set the fixed period and caseload id
                            document.getElementById("fixed-period").value = period;

                            // update session storage stored search parameter
                            sessionStorage["BAP_CaseloadTransfers_Search_Period"] = period;

                            // if we have already drawn the table, destroy existing table
                            if (drawn == 1) { $("#caseloads_transfers").DataTable().destroy(); }

                            // initialize the caseloads_transfers table
                            var caseloads_transfers = $("#caseloads_transfers").DataTable({
                                ajax: {
                                    url: "ajax/caseloads/getTransferRequests.php",
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
                                    { data: "current_caseload", orderable: true, width: "20%" },
                                    { data: "new_caseload", orderable: true, width: "20%" },
                                    { data: "student", orderable: true, width: "12.5%" },
                                    { data: "comments", orderable: true, width: "15%" },
                                    { data: "request_details", orderable: true, width: "15%" },
                                    { data: "status", orderable: true, width: "10%" },
                                    { data: "actions", orderable: false, width: "7.5%" },
                                    { data: "filter_status", orderable: true, visible: false }
                                ],
                                dom: 'rt',
                                language: {
                                    search: '<div class="input-group mb-1"><div class="input-group-prepend"><span class="d-flex align-items-center h-100 mx-2" id="nav-search-icon"><i class="fa fa-search" aria-hidden="true"></i></span></div>_INPUT_</div>',
                                    lengthMenu: '<div class="mx-1 mb-1">Show _MENU_ entries</div>',
                                    info: '<div class="mx-1">Showing _START_ to _END_ of _TOTAL_ entries</div>'
                                },
                                saveState: false,
                                drawCallback: function ()
                                {

                                },
                                rowCallback: function (row, data, index)
                                {
                                    updatePageSelection("caseloads_transfers");
                                }
                            });

                            // mark that we have drawn the table
                            drawn = 1;

                            // search table by custom search filter
                            $('#search-all').keyup(function() {
                                caseloads_transfers.search($(this).val()).draw();
                                sessionStorage["BAP_CaseloadTransfers_Search_All"] = $(this).val();
                            });

                            // search table by request status
                            $('#search-status').change(function() {
                                sessionStorage["BAP_CaseloadTransfers_Search_Status"] = $(this).val();
                                if ($(this).val() != "") { caseloads_transfers.columns(7).search("^" + $(this).val() + "$", true, false, true).draw(); }
                                else { caseloads_transfers.columns(7).search("").draw(); }
                            });

                            // function to clear search filters
                            $('#clearFilters').click(function() {
                                sessionStorage["BAP_CaseloadTransfers_Search_Status"] = "";
                                sessionStorage["BAP_CaseloadTransfers_Search_All"] = "";
                                $('#search-all').val("");
                                $('#search-status').val("");
                                caseloads_transfers.search("").columns().search("").draw();
                            });

                            // redraw table with current search fields
                            if ($('#search-all').val() != "") { caseloads_transfers.search($('#search-all').val()).draw(); }
                            if ($('#search-status').val() != "") { caseloads_transfers.columns(7).search("^" + $('#search-status').val() + "$", true, false, true).draw(); }
                        }
                    }

                    // search caseloads from the default parameters
                    searchTransferRequests();
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