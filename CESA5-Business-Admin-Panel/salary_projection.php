<?php 
    include_once("header.php");

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1) 
    { 
        if (isset($PERMISSIONS["VIEW_REPORT_SALARY_PROJECTION_ALL"]) || isset($PERMISSIONS["VIEW_REPORT_SALARY_PROJECTION_ASSIGNED"]))
        {
            // connect to the database
            $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

            // get the set salary projection rate
            $salary_projection_rate = 0;
            $getSalaryProjectionRate = mysqli_query($conn, "SELECT salary_projection_rate FROM settings WHERE id=1");
            if (mysqli_num_rows($getSalaryProjectionRate) > 0)
            {
                $salary_projection_rate = mysqli_fetch_array($getSalaryProjectionRate)["salary_projection_rate"];
            }

            ?> 
                <div class="report">
                    <div class="row justify-content-center report-header mb-3 mx-0"> 
                        <div class="col-sm-12 col-md-8 col-lg-8 col-xl-6 col-xxl-6 p-0">
                            <fieldset class="border p-2">
                                <legend class="float-none w-auto px-4 py-0 m-0"><h1 class="report-title m-0">Salary Projection Report</h1></legend>
                                <div class="report-description">
                                    <p>
                                        A report that shows employees whose currently salary is below the projected average salary, 
                                        as well as the percentage of salary increase needed for that employee to be at the average.
                                    </p>

                                    <p>
                                        When calculating the projected average salary, we increase the salary of all DPI reported public staff that are stored by
                                        the salary projection rate, and then find the average salary of those new salaries for public staff who match the criteria.
                                        The criteria we match on are the DPI position, DPI area, years of total experience, and highest degree obtained.
                                    </p>
                                </div>

                                <div class="row report-header justify-content-center mx-0"> 
                                    <div class="col-12 col-sm-8 col-md-6 col-lg-6 col-xl-3 col-xxl-3 p-2">
                                        <b>Salary Projection Rate</b>

                                        <div class="input-group w-100 h-auto">
                                            <?php if ($_SESSION["role"] == 1) { ?>
                                            <input class="form-control" type="number" id="salary-projection" name="salary-projection" value="<?php echo $salary_projection_rate; ?>" min="0" step="0.1" onchange="modifySalaryProjection(this.value);">
                                            <?php } else { ?>
                                            <input class="form-control" type="number" id="salary-projection" name="salary-projection" value="<?php echo $salary_projection_rate; ?>" readonly disabled>
                                            <?php } ?>
                                            <div class="input-group-prepend"><span class="input-group-text h-100" id="nav-search-icon"><i class="fa-solid fa-percent"></i></span></div>
                                        </div>
                                    </div>
                                </div>
                            </fieldset>
                        </div>
                    </div>

                    <!-- Filters -->
                    <div class="row justify-content-center mb-3 mx-0">
                        <?php 
                            createPositionsFilter($conn, "report", null, 1, 0); 
                            createAreasFilter($conn, "report", null, 1, 0); 
                            createDepartmentFilter($conn, "report", $_SESSION["id"], 0, 1);
                            createClearFilters();
                        ?>
                    </div>

                    <div class="row report-body m-0">
                        <table id="report_table" class="report_table w-100">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>First Name</th>
                                    <th>Last Name</th>
                                    <th>Contract Days</th>
                                    <th>DPI Position Assignment</th>
                                    <th>Total Experience</th>
                                    <th>Highest Degree</th>
                                    <th>Current Daily Salary</th>
                                    <th>Projected Average Daily Salary</th>
                                    <th>Rate Increase Needed</th>
                                    <th>Info</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>

                <!-- MODALS -->
                <div id="details-modal-div">
                <!-- END MODALS -->

                <script>
                    /** function to update the salary projection rate */
                    function modifySalaryProjection(value)
                    {
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/misc/editSalaryProjectionRate.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                if (this.responseText != "")
                                {
                                    // create the status modal
                                    let status_title = "Edit Salary Projection Rate Status";
                                    let status_body = encodeURIComponent(this.responseText);
                                    createStatusModal("refresh", status_title, status_body);
                                }
                            }
                        };
                        xmlhttp.send("rate="+value);
                    }

                    var table = $("#report_table").DataTable({
                        ajax: {
                            url: "ajax/reports/getSalaryProjectionReport.php",
                            type: "POST"
                        },
                        autoWidth: false,
                        pageLength: <?php if (isset($USER_SETTINGS["page_length"])) { echo $USER_SETTINGS["page_length"]; } else { echo 10; } ?>,
                        lengthMenu: [ [10, 25, 50, 100, 250, -1], [10, 25, 50, 100, 250, "All"] ],
                        columns: [
                            { data: "id", width: "5%", orderable: true },
                            { data: "fname", width: "10%", orderable: true },
                            { data: "lname", width: "10%", orderable: true },
                            { data: "days", width: "6.5%", orderable: true },
                            { data: "dpi_assignment", width: "20%", orderable: true },
                            { data: "experience", width: "5.5%", orderable: true },
                            { data: "degree", width: "10%", orderable: true },
                            { data: "daily_salary", width: "8%", orderable: true },
                            { data: "average_projected_position_daily_salary", width: "10%", orderable: true },
                            { data: "daily_rate_increase", width: "10%", orderable: true },
                            { data: "info", width: "4%", orderable: false },
                            { data: "department", orderable: false, visible: false },
                            { data: "position", orderable: false, visible: false },
                            { data: "area", orderable: false, visible: false }
                        ],
                        order: [ [9, "asc"], [1, "asc"] ],
                        dom: 'lfrtip',
                        language: {
                            search: '<div class="input-group mb-1"><div class="input-group-prepend"><span class="d-flex align-items-center h-100 mx-2" id="nav-search-icon"><i class="fa fa-search" aria-hidden="true"></i></span></div>_INPUT_</div>',
                            lengthMenu: '<div class="mx-1 mb-1">Show _MENU_ entries</div>',
                            info: '<div class="mx-1">Showing _START_ to _END_ of _TOTAL_ entries</div>'
                        },
                        rowCallback: function (row, data, index)
                        {
                            // style the rate column
                            let rate = parseFloat(data["daily_rate_increase"]);
                            // greater than 0
                            if (rate > 0)
                            {
                                if (rate > 0 && rate < 5) { $("td:eq(9)", row).addClass("rate-increase-1"); }
                                else if (rate >= 5 && rate < 10) { $("td:eq(9)", row).addClass("rate-increase-2"); }
                                else if (rate >= 10 && rate < 20) { $("td:eq(9)", row).addClass("rate-increase-3"); }
                                else if (rate >= 20 && rate < 50) { $("td:eq(9)", row).addClass("rate-increase-4"); }
                                else if (rate >= 50) { $("td:eq(9)", row).addClass("rate-increase-5"); }
                            }
                            // less than zero
                            else if (rate < 0)
                            {
                                if (rate < 0 && rate > -5) { $("td:eq(9)", row).addClass("rate-decrease-1"); }
                                else if (rate <= -5 && rate > -10) { $("td:eq(9)", row).addClass("rate-decrease-2"); }
                                else if (rate <= -10 && rate > -20) { $("td:eq(9)", row).addClass("rate-decrease-3"); }
                                else if (rate <= -20 && rate > -50) { $("td:eq(9)", row).addClass("rate-decrease-4"); }
                                else if (rate <= -50) { $("td:eq(9)", row).addClass("rate-decrease-5"); }
                            }
                        },
                        paging: true,
                        stateSave: true
                    });

                    /** function to create the modal to get additional details */
                    function getDetailsModal(employee_id)
                    {
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/reports/getSalaryProjectionDetailsModal.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // display the delete department modal
                                document.getElementById("details-modal-div").innerHTML = this.responseText;     
                                $("#infoModal").modal("show");
                            }
                        };
                        xmlhttp.send("employee_id="+employee_id);
                    }

                    // set the search filters to values we have saved in storage
                    $('#search-dept').val(sessionStorage["BAP_SalaryProjectionReport_Search_Dept"]);
                    $('#search-position').val(sessionStorage["BAP_SalaryProjectionReport_Search_Position"]);
                    if (getAreas()) { $('#search-area').val(sessionStorage["BAP_SalaryProjectionReport_Search_Area"]); } // once we repopulate area selection; set area to prior selection

                    // search table by employee primary department
                    $('#search-dept').change(function() {
                        table.columns(11).search($(this).val()).draw();
                        sessionStorage["BAP_SalaryProjectionReport_Search_Dept"] = $(this).val();
                    });

                    // search table by employee DPI position
                    $('#search-position').change(function() {
                        table.columns(12).search($(this).val()).draw();
                        sessionStorage["BAP_SalaryProjectionReport_Search_Position"] = $(this).val();
                    });

                    // search table by employee DPI area
                    $('#search-area').change(function() {
                        table.columns(13).search($(this).val()).draw();
                        sessionStorage["BAP_SalaryProjectionReport_Search_Area"] = $(this).val();
                    });

                    // function to clear search filters
                    $('#clearFilters').click(function() {
                        $('#search-position').val("");
                        $('#search-area').val("");
                        $('#search-dept').val("");
                        table.search("").columns().search("").draw();
                    });

                    /** function to get DPI assignment areas for a given position */
                    function getAreas()
                    {
                        // get the current position
                        let value = document.getElementById("search-position").value;

                        // get the average cost of services provided
                        document.getElementById("search-area").innerHTML = $.ajax({
                            type: "POST",
                            url: "ajax/misc/getPositionAreas.php",
                            async: false,
                            data: {
                                position: value
                            }
                        }).responseText;

                        // return true once we finish setting area selection container
                        return true;
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