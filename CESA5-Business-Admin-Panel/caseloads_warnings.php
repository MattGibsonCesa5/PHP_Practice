<?php 
    include_once("header.php");
    include("getSettings.php");

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
                                                <select class="form-select" id="search-period" name="search-period" onchange="buildReport();">
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

                                                    <!-- Filter By Category -->
                                                    <div class="row d-flex justify-content-between align-items-center mx-0 mt-0 mb-2">
                                                        <div class="col-4 ps-0 pe-1">
                                                            <label for="search-category">Category:</label>
                                                        </div>

                                                        <div class="col-8 ps-1 pe-0">
                                                            <select class="form-select" id="search-category" name="search-category">
                                                                <option></option>
                                                                <?php
                                                                    $getCategories = mysqli_query($conn, "SELECT id, name FROM caseload_categories WHERE uos_enabled=1 AND uos_required=1 ORDER BY name ASC");
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
                                    <h1 class="m-0">Case Unit Warnings</h1>
                                    <p class="report-description m-0">
                                        A report of all cases for the selected period that exceed the set unit amount that triggers a warning, or are below 12 units.<br>
                                        The caseload unit warning amount is set to <?php echo $GLOBAL_SETTINGS["caseloads_units_warning"]; ?>
                                    </p>
                                </div>

                                <!-- Page Management Dropdown -->
                                <div class="col-6 col-sm-4 col-md-4 col-lg-3 col-xl-2 col-xxl-2 p-0"></div>
                            </div>
                        </div>

                        <table id="caseloads_warnings" class="report_table w-100">
                            <thead>
                                <tr>
                                    <th class="py-1 px-2" style="text-align: center !important;">Caseload</th>
                                    <th class="py-1 px-2" style="text-align: center !important;">Student</th>
                                    <th class="py-1 px-2" style="text-align: center !important;">Units Of Service (UOS)</th>
                                </tr>
                            </thead>
                        </table>
                        <?php createTableFooterV2("caseloads_warnings", "BAP_CaseloadTransfers_PageLength", $USER_SETTINGS["page_length"], true, true); ?>
                    </div>
                </div>

                <!--
                    ### MODALS ###
                -->
                <!-- View Student Modal -->
                <div id="view-student-modal-div"></div>
                <!-- End View Student Modal -->
                <!--
                    ### END MODALS ###
                -->

                <script>
                    // initialize variable to indicate if we have drawn the caseloads table
                    var drawn = 0; // assume we have not drawn the table yet

                    // get the current active period
                    let active_period = "<?php echo $active_period_label; ?>"; 

                    // set the search filters to values we have saved in storage
                    if (sessionStorage["BAP_CaseloadsWarningsReport_Search_Period"] != "" && sessionStorage["BAP_CaseloadsWarningsReport_Search_Period"] != null && sessionStorage["BAP_CaseloadsWarningsReport_Search_Period"] != undefined) { $('#search-period').val(sessionStorage["BAP_CaseloadsWarningsReport_Search_Period"]); }
                    else { $('#search-period').val(active_period); } // no period set; default to active period 

                    /** function to build the report */
                    function buildReport()
                    {
                        // get the value of the period we are searching
                        var period = document.getElementById("search-period").value;

                        if (period != "" && period != null && period != undefined)
                        {
                            // set the fixed period and caseload id
                            document.getElementById("fixed-period").value = period;

                            // update session storage stored search parameter
                            sessionStorage["BAP_CaseloadsWarningsReport_Search_Period"] = period;

                            // if we have already drawn the table, destroy existing table
                            if (drawn == 1) { $("#caseloads_warnings").DataTable().destroy(); }

                            // initialize the caseloads_warnings table
                            var caseloads_warnings = $("#caseloads_warnings").DataTable({
                                ajax: {
                                    url: "ajax/caseloads/getCaseloadsWarningsReport.php",
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
                                    { data: "caseload", orderable: true, width: "30%" },
                                    { data: "student", orderable: true, width: "25%" },
                                    { data: "units", orderable: true, width: "45%", className: "text-end" },
                                    { data: "category", orderable: true, visible: false }
                                ],
                                order: [
                                    [ 2, "desc" ],
                                    [ 0, "asc" ],
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
                                    updatePageSelection("caseloads_warnings");
                                }
                            });

                            // mark that we have drawn the table
                            drawn = 1;

                            // search table by custom search filter
                            $('#search-all').keyup(function() {
                                caseloads_warnings.search($(this).val()).draw();
                                sessionStorage["BAP_CaseloadsWarningsReport_Search_All"] = $(this).val();
                            });

                            // search table by custom search filter
                            $('#search-category').change(function() {
                                caseloads_warnings.columns(3).search($(this).val()).draw();
                                sessionStorage["BAP_CaseloadsWarningsReport_Search_Category"] = $(this).val();
                            });

                            // function to clear search filters
                            $('#clearFilters').click(function() {
                                sessionStorage["BAP_CaseloadsWarningsReport_Search_All"] = "";
                                sessionStorage["BAP_CaseloadsWarningsReport_Search_Category"] = "";
                                $('#search-all').val("");
                                $('#search-category').val("");
                                caseloads_warnings.search("").columns().search("").draw();
                            });

                            // redraw table with current search fields
                            if ($('#search-all').val() != "") { caseloads_warnings.search($('#search-all').val()).draw(); }
                            if ($('#search-category').val() != "") { caseloads_warnings.columns(3).search("^" + $('#search-category').val() + "$", true, false, true).draw(); }
                        }
                    }

                    // build the report with default parameters
                    buildReport();

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