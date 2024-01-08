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
                    #period-icon-div:hover #period-label, #category-icon-div:hover #category-label
                    {
                        display: inline;
                        color: #000000;
                        transform: translate(4px, 00%);
                    }

                    #period-label, #category-label
                    {
                        display: none;
                        color: #000000;
                        transition: 1s;
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
                                        <div class="row mb-1">
                                            <div class="input-group w-100 h-auto">
                                                <div class="input-group-prepend" id="period-icon-div">
                                                    <span class="input-group-text h-100" id="nav-search-icon">
                                                        <i class="fa-solid fa-calendar-days"></i>
                                                        <span id="period-label">Period</span>
                                                    </span>
                                                </div>
                                                <input id="fixed-period" type="hidden" value="" aria-hidden="true">
                                                <select class="form-select" id="search-period" name="search-period" onchange="displayReport();">
                                                    <option></option>
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
                                            <div class="dropdown-menu filters-menu px-2" aria-labelledby="filtersMenu" style="width: 352px;">
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

                                                <!-- Filter By Therapist -->
                                                <div class="row d-flex justify-content-between align-items-center mx-0 mt-0 mb-2">
                                                    <div class="col-4 ps-0 pe-1">
                                                        <label for="search-therapist">Therapist:</label>
                                                    </div>

                                                    <div class="col-8 ps-1 pe-0">
                                                        <select class="form-select" id="search-therapist" name="search-therapist">
                                                            <option></option>
                                                            <?php
                                                                $getTherapists = mysqli_query($conn, "SELECT u.lname, u.fname FROM caseloads c 
                                                                                                    JOIN caseload_categories cc ON c.category_id=cc.id
                                                                                                    JOIN users u ON c.employee_id=u.id
                                                                                                    WHERE cc.medicaid=1 
                                                                                                    ORDER BY u.lname ASC, u.fname ASC");
                                                                if (mysqli_num_rows($getTherapists) > 0) // services exist
                                                                {
                                                                    while ($therapist = mysqli_fetch_array($getTherapists))
                                                                    {
                                                                        echo "<option>".$therapist["lname"].", ".$therapist["fname"]."</option>";
                                                                    }
                                                                }
                                                            ?>
                                                        </select>
                                                    </div>
                                                </div>

                                                <!-- Filter By District -->
                                                <div class="row d-flex justify-content-between align-items-center mx-0 mt-0 mb-2">
                                                    <div class="col-4 ps-0 pe-1">
                                                        <label for="search-district">Bill To District:</label>
                                                    </div>

                                                    <div class="col-8 ps-1 pe-0">
                                                        <select class="form-select" id="search-district" name="search-district">
                                                            <option></option>
                                                            <?php
                                                                $getCustomers = mysqli_query($conn, "SELECT DISTINCT c.id, c.name FROM `customers` c 
                                                                                                    JOIN cases ON (c.id=cases.residency OR c.id=cases.district_attending)
                                                                                                    ORDER BY c.name ASC");
                                                                if (mysqli_num_rows($getCustomers) > 0) // services exist
                                                                {
                                                                    while ($customer = mysqli_fetch_array($getCustomers))
                                                                    {
                                                                        echo "<option>".$customer["name"]."</option>";
                                                                    }
                                                                }
                                                            ?>
                                                        </select>
                                                    </div>
                                                </div>

                                                <!-- Filter By Category -->
                                                <div class="row d-flex justify-content-between align-items-center mx-0 mt-0 mb-2">
                                                    <div class="col-4 ps-0 pe-1">
                                                        <label for="search-category">Category:</label>
                                                    </div>

                                                    <div class="col-8 ps-1 pe-0">
                                                        <select class="form-select" id="search-category" name="search-category">
                                                            <option></option>
                                                            <?php
                                                                $getCategories = mysqli_query($conn, "SELECT id, name FROM caseload_categories WHERE medicaid=1 ORDER BY name ASC");
                                                                if (mysqli_num_rows($getCategories) > 0)
                                                                {
                                                                    while ($category = mysqli_fetch_array($getCategories))
                                                                    {
                                                                        // store category details locally
                                                                        $category_id = $category["id"];
                                                                        $category_name = $category["name"];

                                                                        // create selection option
                                                                        echo "<option>".$category_name."</option>";
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
                                <h1 class="report-title m-0">Medicaid Billing</h1>
                            </div>

                            <!-- Page Management Dropdown -->
                            <div class="col-6 col-sm-4 col-md-4 col-lg-3 col-xl-2 col-xxl-2 p-0"></div>
                        </div>
                    </div>

                    <div id="report-div" class="d-none">
                        <!-- Key -->
                        <div class="table-header p-1">
                            <div class="row justify-content-center">
                                <div class="col-3 col-lg-2">
                                    <div class="evaluation_method-div d-flex justify-content-center align-items-center text-center p-1 w-100 h-100"><b>Evaluation Method</b></div>
                                </div>

                                <div class="col-3 col-lg-2">
                                    <div class="enrollment_type-div d-flex justify-content-center align-items-center text-center p-1 w-100 h-100"><b>Enrollment Type</b></div>
                                </div>

                                <div class="col-3 col-lg-2">
                                    <div class="educational_plan-div d-flex justify-content-center align-items-center text-center p-1 w-100 h-100"><b>Educational Plan</b></div>
                                </div>

                                <div class="col-3 col-lg-2">
                                    <div class="billing_to-div d-flex justify-content-center align-items-center text-center p-1 w-100 h-100"><b>Billing To</b></div>
                                </div>
                            </div>  
                        </div>

                        <!-- container to store table -->
                        <table id="medicaid" class="report_table w-100">
                            <thead>
                                <tr>
                                    <th class="text-center py-1 px-2">Student</th>
                                    <th class="text-center py-1 px-2">Bill To</th>
                                    <th class="text-center py-1 px-2">Start Date</th>
                                    <th class="text-center py-1 px-2">End Date</th>
                                    <th class="text-center py-1 px-2">Month</th>
                                    <th class="text-center py-1 px-2">Therapist</th>
                                    <th class="text-center py-1 px-2">Assistant</th>
                                    <th class="text-center py-1 px-2">Actions</th>
                                </tr>
                            </thead>
                        </table>
                        <?php createTableFooterV2("medicaid", "BAP_Caseloads_Medicaid_PageLength", $USER_SETTINGS["page_length"], true, true); ?>
                    </div>
                </div>

                <!-- View Student Modal -->
                <div id="view-student-modal-div"></div>
                <!-- End View Student Modal -->

                <script>
                    // get the current active period
                    let active_period = "<?php echo $active_period_label; ?>"; 

                    // set the search filters to values we have saved in storage
                    if (sessionStorage["BAP_Caseloads_Medicaid_Search_Period"] != "" && sessionStorage["BAP_Caseloads_Medicaid_Search_Period"] != null && sessionStorage["BAP_Caseloads_Medicaid_Search_Period"] != undefined) { $('#search-period').val(sessionStorage["BAP_Caseloads_Medicaid_Search_Period"]); }
                    else { $('#search-period').val(active_period); } // no period set; default to active period 

                    function displayReport()
                    {
                        // get the value of the period we are searching
                        var period = document.getElementById("search-period").value;

                        if (period != "" && period != null && period != undefined)
                        {
                            // set the fixed period and caseload id
                            document.getElementById("fixed-period").value = period;

                            // update session storage stored search parameter
                            sessionStorage["BAP_Caseloads_Medicaid_Search_Period"] = period;

                            // initialize the caseloads table
                            var caseloads_medicaid = $("#medicaid").DataTable({
                                ajax: {
                                    url: "ajax/caseloads/getMedicaidReport.php",
                                    type: "POST",
                                    data: {
                                        period: period
                                    }
                                },
                                destroy: true,
                                autoWidth: false,
                                async: false,
                                pageLength: <?php if (isset($USER_SETTINGS["page_length"])) { echo $USER_SETTINGS["page_length"]; } else { echo 10; } ?>,
                                lengthMenu: [ [10, 25, 50, 100, 250, -1], [10, 25, 50, 100, 250, "All"] ],
                                columns: [
                                    // display columns
                                    { data: "student", orderable: true, width: "17.5%" },
                                    { data: "location", orderable: true, width: "17.5%" },
                                    { data: "start", orderable: true, width: "7.5%", className: "text-center" },
                                    { data: "end", orderable: true, width: "7.5%", className: "text-center" },
                                    { data: "month", orderable: true, width: "10%", className: "text-center" },
                                    { data: "therapist", orderable: true, width: "15%", className: "text-center" },
                                    { data: "assistant", orderable: true, width: "15%", className: "text-center" },
                                    { data: "actions", orderable: true, width: "10%" },
                                    { data: "filter_district", orderable: false, visible: false }, // 8
                                    { data: "filter_therapist", orderable: false, visible: false },
                                    { data: "filter_category", orderable: false, visible: false },
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
                                    updatePageSelection("medicaid");
                                },
                                initComplete: function() {
                                    // initialize tooltips
                                    $("[data-bs-toggle=\"tooltip\"]").tooltip();
                                }
                            });

                            // search table by custom search filter
                            $('#search-all').keyup(function() {
                                caseloads_medicaid.search($(this).val()).draw();
                                sessionStorage["BAP_Caseloads_Medicaid_Search_All"] = $(this).val();
                            });
                            $('#search-district').change(function() {
                                sessionStorage["BAP_Caseloads_Medicaid_Search_BillTo"] = $(this).val();
                                if ($(this).val() != "") { caseloads_medicaid.columns(8).search("^" + $(this).val() + "$", true, false, true).draw(); }
                                else { caseloads_medicaid.columns(8).search("").draw(); }
                            });
                            $('#search-therapist').change(function() {
                                sessionStorage["BAP_Caseloads_Medicaid_Search_Therapist"] = $(this).val();
                                if ($(this).val() != "") { caseloads_medicaid.columns(9).search("^" + $(this).val() + "$", true, false, true).draw(); }
                                else { caseloads_medicaid.columns(9).search("").draw(); }
                            });
                            $('#search-category').change(function() {
                                sessionStorage["BAP_Caseloads_Medicaid_Search_Category"] = $(this).val();
                                if ($(this).val() != "") { caseloads_medicaid.columns(10).search("^" + $(this).val() + "$", true, false, true).draw(); }
                                else { caseloads_medicaid.columns(10).search("").draw(); }
                            });

                            // function to clear search filters
                            $('#clearFilters').click(function() {
                                sessionStorage["BAP_Caseloads_Medicaid_Search_All"] = "";
                                sessionStorage["BAP_Caseloads_Medicaid_Search_BillTo"] = "";
                                sessionStorage["BAP_Caseloads_Medicaid_Search_Therapist"] = "";
                                sessionStorage["BAP_Caseloads_Medicaid_Search_Category"] = "";
                                $('#search-all').val("");
                                $('#search-district').val("");
                                $('#search-therapist').val("");
                                $('#search-category').val("");
                                caseloads_medicaid.search("").columns().search("").draw();
                            });

                            // redraw caseload table with current search fields
                            if ($('#search-all').val() != "") { caseloads_medicaid.search($('#search-all').val()).draw(); }
                            if ($('#search-district').val() != "") { caseloads_medicaid.columns(8).search("^" + $('#search-district').val() + "$", true, false, true).draw(); }
                            if ($('#search-therapist').val() != "") { caseloads_medicaid.columns(9).search("^" + $('#search-therapist').val() + "$", true, false, true).draw(); }
                            if ($('#search-category').val() != "") { caseloads_medicaid.columns(10).search("^" + $('#search-category').val() + "$", true, false, true).draw(); }

                            // display the report container
                            document.getElementById("report-div").classList.remove("d-none");
                        }
                        else { createStatusModal("alert", "Loading Report Error", "Failed to load the report. You must select a period to generate the report for."); }
                    }

                    // call the function to attempt to generate the report on page load
                    displayReport();

                    /** function to get the modal to view which other caseloads the student is in */
                    function getViewStudentModal(case_id, student_id)
                    {
                        // get the fixed period name
                        let period = document.getElementById("fixed-period").value;

                        // send the data to create and display the modal
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/caseloads/getViewStudentModal.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // display the modal
                                document.getElementById("view-student-modal-div").innerHTML = this.responseText;     
                                $("#viewStudentModal").modal("show");
                            }
                        };
                        xmlhttp.send("student_id="+student_id+"&period="+period+"&case_id="+case_id);
                    }

                    /** function to toggle medicaid billing completed */
                    function toggleMedicaidBillingDone(case_id, checked)
                    {
                        // convert checked status to int
                        let done = 0;
                        if (checked === true) { 
                            done = 1;
                        } else {
                            done = 0;
                        }

                        // send the request 
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/caseloads/updateMedicaidBillingDoneStatus.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {

                            }
                        }
                        xmlhttp.send("case_id="+case_id+"&status="+done);
                    }
                        
                    /** function to toggle medicaid billed  */
                    function toggleMedicaidBilled(case_id, checked)
                    {
                        // convert checked status to int
                        let done = 0;
                        if (checked === true) { 
                            done = 1;
                        } else {
                            done = 0;
                        }

                        // send the request 
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/caseloads/updatedMedicaidBilledStatus.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {

                            }
                        }
                        xmlhttp.send("case_id="+case_id+"&status="+done);
                    }
                </script>
            <?php 
        }
        else { denyAccess(); }
    }
    else { goToLogin(); }

    include("footer.php"); 
?>