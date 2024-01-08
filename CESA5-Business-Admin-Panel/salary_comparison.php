<?php 
    include_once("header.php");

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1) 
    { 
        if (isset($PERMISSIONS["VIEW_SALARY_COMPARISON_STATE"]) || isset($PERMISSIONS["VIEW_SALARY_COMPARISON_INTERNAL_ALL"]) || isset($PERMISSIONS["VIEW_SALARY_COMPARISON_INTERNAL_ASSIGNED"]) || isset($PERMISSIONS["VIEW_RAISE_PROJECTION"]))
        {
            // connect to the database
            $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

            // get the year of DPI report
            $dpi_report_year = null; 
            $getDPIYear = mysqli_query($conn, "SELECT DISTINCT(year) FROM dpi_employees ORDER BY year DESC LIMIT 1");
            if (mysqli_num_rows($getDPIYear) > 0) { $dpi_report_year = mysqli_fetch_array($getDPIYear)["year"]; }

            // get the count of how many DPI employees have been reported
            $dpi_employees_count = 0; // initialize count to 0
            $getDPIEmployeesCount = mysqli_query($conn, "SELECT COUNT(id) AS employees_count FROM dpi_employees WHERE total_salary>0");
            if (mysqli_num_rows($getDPIEmployeesCount) > 0) { $dpi_employees_count = mysqli_fetch_array($getDPIEmployeesCount)["employees_count"]; }

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
                    .dt-buttons 
                    {
                        position: absolute !important;
                        left: 0;
                        right: 0;
                        text-align: right;
                        margin-top: 4px;
                    }
                </style>

                <script>
                    /** function to display the container for the salary calculator selected */
                    function toggleSalaryCalculator(type)
                    {
                        // display only container selected; hide other containers
                        <?php if (isset($PERMISSIONS["VIEW_SALARY_COMPARISON_STATE"])) { ?>
                        document.getElementById("salary_comparison-external-container").classList.add("d-none");
                        document.getElementById("salary_comparison-external-button").classList.remove("btn-primary");
                        document.getElementById("salary_comparison-external-button").classList.add("btn-secondary");
                        <?php } ?>

                        <?php if (isset($PERMISSIONS["VIEW_SALARY_COMPARISON_INTERNAL_ALL"]) || isset($PERMISSIONS["VIEW_SALARY_COMPARISON_INTERNAL_ASSIGNED"])) { ?>
                        document.getElementById("salary_comparison-internal-container").classList.add("d-none");
                        document.getElementById("salary_comparison-internal-button").classList.remove("btn-primary");
                        document.getElementById("salary_comparison-internal-button").classList.add("btn-secondary");
                        <?php } ?>
                        
                        <?php if (isset($PERMISSIONS["VIEW_RAISE_PROJECTION"])) { ?>
                        document.getElementById("salary_comparison-projection-container").classList.add("d-none");
                        document.getElementById("salary_comparison-projection-button").classList.remove("btn-primary");
                        document.getElementById("salary_comparison-projection-button").classList.add("btn-secondary");
                        <?php } ?>

                        // select the salary calculator toggled; then view
                        document.getElementById("salary_comparison-"+type+"-button").classList.add("btn-primary");
                        document.getElementById("salary_comparison-"+type+"-container").classList.remove("d-none");
                        
                        // store the salary calculator we are viewing in session storage
                        sessionStorage["BAP_SalaryCalculator_Selection"] = type;
                    }
                </script>
                
                <!-- Header -->
                <div class="row m-0 p-0">
                    <h1 class="col-12 col-sm-8 col-md-6 col-lg-4 col-xl-4 col-xxl-4 page-header my-3 py-3 ps-3 pe-5">
                        <a class="back-button" href="employees.php" title="Return to Employees."><i class="fa-solid fa-angles-left"></i></a>
                        <div class="d-inline float-end">Salary Comparison</div>
                    </h1>
                </div>

                <div class="report">
                    <!-- Salary Comparison Type Selection -->
                    <div class="row m-0 mb-3">
                        <div class="btn-group" role="group" aria-label="Button group to select which type of salary calculator to launch">
                            <?php if (isset($PERMISSIONS["VIEW_SALARY_COMPARISON_STATE"])) { ?><button class="btn btn-secondary btn-lg w-100" id="salary_comparison-external-button" onclick="toggleSalaryCalculator('external');">State-wide Salary Comparison</button><?php } ?>
                            <?php if (isset($PERMISSIONS["VIEW_SALARY_COMPARISON_INTERNAL_ALL"]) || isset($PERMISSIONS["VIEW_SALARY_COMPARISON_INTERNAL_ASSIGNED"])) { ?><button class="btn btn-secondary btn-lg w-100" id="salary_comparison-internal-button" onclick="toggleSalaryCalculator('internal');">Internal Salary Comparison</button><?php } ?>
                            <?php if (isset($PERMISSIONS["VIEW_RAISE_PROJECTION"])) { ?><button class="btn btn-secondary btn-lg w-100" id="salary_comparison-projection-button" onclick="toggleSalaryCalculator('projection');">Raise Projection</button><?php } ?>
                        </div>
                    </div>

                    <?php if (isset($PERMISSIONS["VIEW_SALARY_COMPARISON_STATE"])) { ?>
                    <div class="d-none" id="salary_comparison-external-container">
                        <div class="row justify-content-center">
                            <div class="col-8 text-center">
                                <p class="body-desc mb-1"><?php if (isset($dpi_report_year)) { echo "There are ".number_format($dpi_employees_count)." employees reported to the DPI includeded in the DPI's Public All Staff Report that had a salary greater than $0.00 in the year $dpi_report_year."; } ?></p>    

                                <?php if ($_SESSION["role"] == 1) { // admin has ability to upload public DPI employees and DPI positions ?>
                                    <div class="row justify-content-center mb-1"> 
                                        <div class="col-sm-12 col-md-6 col-lg-4 col-xl-4 col-xxl-4 p-2">
                                            <button class="btn btn-primary w-100" type="button" data-bs-toggle="modal" data-bs-target="#uploadDPIPositionsModal">Upload DPI Position Assignment Codes</button>
                                        </div>

                                        <div class="col-sm-12 col-md-6 col-lg-4 col-xl-4 col-xxl-4 p-2">
                                            <button class="btn btn-primary w-100" type="button" data-bs-toggle="modal" data-bs-target="#uploadDPIEmployeesModal">Upload DPI Public Staff Report</button>
                                        </div>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>

                        <div class="row justify-content-center report-body mb-3 px-3">
                            <?php
                                createPositionsFilter($conn);
                                createAreasFilter();
                                createWorkTypeFilter($conn);
                                createWorkCountyFilter($conn);
                                createWorkLevelFilter($conn);
                            ?>
                            <!-- display results button -->
                            <div class="col-2 justify-content-center px-2">
                                <label class="text-center w-100" aria-hidden="true"></label>
                                <button class="btn btn-primary w-100" type="button" onclick="displayStateSalaryComparison();">Display Results</button>
                            </div>
                        </div>

                        <div class="row justify-content-center d-none" id="salaries-tables-div">
                            <div class="col-12">
                                <div id="salaries-breakdown-div"></div>
                                <hr>
                                <div id="salaries-by_experience-div"></div>
                                <hr>
                                <div id="salaries-by_degree-div"></div>
                                <hr>
                                <div id="salaries-by_gender-div"></div>
                                <hr>
                                <div id="salaries-by_race-div"></div>
                                <hr>
                                <div id="salaries-by_CESA-div"></div>
                            </div>
                        </div>
                    </div>
                    <?php } ?>

                    <?php if (isset($PERMISSIONS["VIEW_SALARY_COMPARISON_INTERNAL_ALL"]) || isset($PERMISSIONS["VIEW_SALARY_COMPARISON_INTERNAL_ASSIGNED"])) { ?>
                    <div class="d-none" id="salary_comparison-internal-container">
                        <div class="row justify-content-center report-body mb-3 px-3">
                            <?php
                                createTitleFilter($conn, "salaries", 0);
                                createDepartmentFilter($conn, "salaries", $_SESSION["id"], 0, 1);
                            ?>

                            <!-- display results button -->
                            <div class="col-2 justify-content-center px-2">
                                <label class="text-center w-100" aria-hidden="true"></label>
                                <button class="btn btn-primary w-100" type="button" onclick="displayInternalSalaryComparison();">Display Results</button>
                            </div>
                        </div>

                        <div class="row justify-content-center d-none" id="internal-salaries-tables-div">
                            <div class="col-12">
                                <div id="internal-salaries-breakdown-div"></div>
                            </div>
                        </div>
                    </div>
                    <?php } ?>

                    <?php if (isset($PERMISSIONS["VIEW_RAISE_PROJECTION"])) { ?>
                        <div class="d-none" id="salary_comparison-projection-container" stlye="overflow-x: hidden;">
                            <div class="row justify-content-center report-body mb-3 px-3">
                                <div class="col-12 col-sm-6 col-md-4 col-lg-4 col-xl-3 col-xxl-2 p-2">
                                    <label class="filter-label" for="raise_projection-rate"><b>Raise Projection Rate</b></label>

                                    <div class="input-group w-100 h-auto">
                                        <input class="form-control" type="number" id="raise_projection-rate" name="raise_projection-rate" value="0.00" min="0" step="0.1">
                                        <div class="input-group-prepend"><span class="input-group-text h-100" id="nav-search-icon"><i class="fa-solid fa-percent"></i></span></div>
                                    </div>
                                </div>

                                <div class="col-12 col-sm-6 col-md-4 col-lg-4 col-xl-3 col-xxl-2 p-2">
                                    <label class="filter-label" for="raise_projection-period"><b>Projected Period</b></label>

                                    <div class="input-group w-100 h-auto">
                                        <div class="input-group-prepend"><span class="input-group-text h-100" id="nav-search-icon"><i class="fa-solid fa-calendar-days"></i></span></div>
                                        <select class="form-select" id="raise_projection-period" name="raise_projection-period">
                                            <option></option>
                                            <?php
                                                for ($p = 0; $p < count($periods); $p++)
                                                {
                                                    echo "<option value='".$periods[$p]["id"]."'>".$periods[$p]["name"]."</option>";
                                                }
                                            ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-12 col-sm-12 col-md-4 col-lg-4 col-xl-3 col-xxl-2 p-2">
                                    <label aria-hidden="true"></label>
                                    <button class="btn btn-primary w-100" onclick="calculateRaiseProjection();">Calculate Raise Projection</button>
                                </div>
                            </div>

                            <div class="row justify-content-center d-none" id="raise-projection-table-container">
                                <div class="col-12">
                                    <div id="raise-projection-table-div">
                                        <div class="row d-flex justify-content-center mb-3">
                                            <div class="col-12 col-sm-12 col-md-12 col-lg-12 col-xl-9 col-xxl-6 p-2">
                                                <table class="report_table report_table_border w-100">
                                                    <thead>
                                                        <tr>
                                                            <th class="text-center" colspan="4"><h1 class="m-0">Total Package Estimation</h1></th>
                                                        </tr>

                                                        <tr>
                                                            <th></th>
                                                            <th class="text-center">Total Salary</th>
                                                            <th class="text-center">Total Fringe</th>
                                                            <th class="text-center">Total Compensation</th>
                                                        </tr>
                                                    </thead>

                                                    <tbody>
                                                        <tr>
                                                            <td class="text-start fw-bolder"><?php echo $active_period_label; ?></td>
                                                            <td class="text-center" id="active-total_salary"></td>
                                                            <td class="text-center" id="active-total_fringe"></td>
                                                            <td class="text-center" id="active-total_compensation"></td>
                                                        </tr>

                                                        <tr>
                                                            <td class="text-start fw-bolder">PROJECTED</td>
                                                            <td class="text-center" id="projected-total_salary"></td>
                                                            <td class="text-center" id="projected-total_fringe"></td>
                                                            <td class="text-center" id="projected-total_compensation"></td>
                                                        </tr>

                                                        <tr>
                                                            <td class="text-start fst-italic">Difference</td>
                                                            <td class="text-center" id="diff-total_salary"></td>
                                                            <td class="text-center" id="diff-total_fringe"></td>
                                                            <td class="text-center" id="diff-total_compensation"></td>
                                                        </tr>

                                                        <tr>
                                                            <td class="text-start fst-italic">Est. % Increase</td>
                                                            <td class="text-center" id="inc-total_salary"></td>
                                                            <td class="text-center" id="inc-total_fringe"></td>
                                                            <td class="text-center" id="inc-total_compensation"></td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>

                                        <div class="row my-3">
                                            <div class="col-12">
                                                <table class="report_table w-100" id="raise-projection-table">
                                                    <thead>
                                                        <tr>
                                                            <th>ID</th>
                                                            <th>Last Name</th>
                                                            <th>First Name</th>
                                                            <th>Primary Department</th>
                                                            <th style="text-align: left !important;"><?php echo $active_period_label; ?> Days</th>
                                                            <th style="text-align: left !important;"><?php echo $active_period_label; ?> Salary</th>
                                                            <th style="text-align: left !important;">Projected Days</th>
                                                            <th style="text-align: left !important;">Projected Salary</th>
                                                            <th style="text-align: left !important;"><?php echo $active_period_label; ?> FICA</th>
                                                            <th style="text-align: left !important;"><?php echo $active_period_label; ?> Health</th>
                                                            <th style="text-align: left !important;"><?php echo $active_period_label; ?> Dental</th>
                                                            <th style="text-align: left !important;"><?php echo $active_period_label; ?> WRS</th>
                                                            <th style="text-align: left !important;"><?php echo $active_period_label; ?> LTD</th>
                                                            <th style="text-align: left !important;"><?php echo $active_period_label; ?> Life Insurance</th>
                                                            <th style="text-align: left !important;"><?php echo $active_period_label; ?> Total Fringe</th>
                                                            <th style="text-align: left !important;">Projected FICA</th>
                                                            <th style="text-align: left !important;">Projected Health</th>
                                                            <th style="text-align: left !important;">Projected Dental</th>
                                                            <th style="text-align: left !important;">Projected WRS</th>
                                                            <th style="text-align: left !important;">Projected LTD</th>
                                                            <th style="text-align: left !important;">Projected Life Insurance</th>
                                                            <th style="text-align: left !important;">Projected Total Fringe</th>
                                                            <th style="text-align: left !important;"><?php echo $active_period_label; ?> Total Compensation</th>
                                                            <th style="text-align: left !important;">Projected Total Compensation</th>
                                                        </tr>
                                                    </thead>

                                                    <tbody></tbody>

                                                    <tfoot>
                                                        <tr>
                                                            <th colspan="4" class="text-end">
                                                            <th class="text-end" id="active-days"></th>
                                                            <th class="text-end" id="active-salary"></th>
                                                            <th class="text-end" id="projected-days"></th>
                                                            <th class="text-end" id="projected-salary"></th>
                                                            <th class="text-end" id="active-fica"></th>
                                                            <th class="text-end" id="active-health"></th>
                                                            <th class="text-end" id="active-dental"></th>
                                                            <th class="text-end" id="active-wrs"></th>
                                                            <th class="text-end" id="active-ltd"></th>
                                                            <th class="text-end" id="active-life"></th>
                                                            <th class="text-end" id="active-fringe"></th>
                                                            <th class="text-end" id="projected-fica"></th>
                                                            <th class="text-end" id="projected-health"></th>
                                                            <th class="text-end" id="projected-dental"></th>
                                                            <th class="text-end" id="projected-wrs"></th>
                                                            <th class="text-end" id="projected-ltd"></th>
                                                            <th class="text-end" id="projected-life"></th>
                                                            <th class="text-end" id="projected-fringe"></th>
                                                            <th class="text-end" id="active-total"></th>
                                                            <th class="text-end" id="projected-total"></th>
                                                        </tr>
                                                    </tfoot>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                </div>

                <?php if ($_SESSION["role"] == 1) { ?>
                    <!-- Upload DPI Positions Modal -->
                    <div class="modal fade" tabindex="-1" role="dialog" id="uploadDPIPositionsModal" data-bs-backdrop="static" aria-labelledby="uploadDPIPositionsModalLabel" aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header primary-modal-header">
                                    <h5 class="modal-title primary-modal-title" id="uploadDPIPositionsModalLabel">Upload DPI Positions</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>

                                <form action="processDPIPositionsUpload.php" method="POST" enctype="multipart/form-data">
                                    <div class="modal-body">
                                        <p><label for="fileToUpload">Select a CSV file downloaded from the <a class="template-link" href="https://publicstaffreports.dpi.wi.gov/PubStaffReport/Public/PublicReport/AssignmentCodeList" target="_blank">DPI's "Assignment Code List"</a> to upload...</label></p>
                                        <p><i class="fa-solid fa-triangle-exclamation"></i> Uploading DPI positions will clear out all current DPI positions that have been uploaded. Proceed with caution.</p>
                                        <input type="file" name="fileToUpload" id="fileToUpload">
                                    </div>

                                    <div class="modal-footer">
                                        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-cloud-arrow-up"></i> Upload Positions</button>
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> Close</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <!-- End Upload DPI Positions Modal -->

                    <!-- Upload Public DPI Employees Modal -->
                    <div class="modal fade" tabindex="-1" role="dialog" id="uploadDPIEmployeesModal" data-bs-backdrop="static" aria-labelledby="uploadDPIEmployeesModalLabel" aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header primary-modal-header">
                                    <h5 class="modal-title primary-modal-title" id="uploadDPIEmployeesModalLabel">Upload Public DPI Employees</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>

                                <form action="processDPIStaffUpload.php" method="POST" enctype="multipart/form-data">
                                    <div class="modal-body">
                                        <p><label for="fileToUpload">Select a CSV file downloaded from the <a class="template-link" href="https://publicstaffreports.dpi.wi.gov/PubStaffReport/Public/PublicReport/AllStaffReport" target="_blank">DPI's "Public All Staff Report"</a> to upload...</label></p>
                                        <p><i class="fa-solid fa-triangle-exclamation"></i> Uploading public DPI employees will clear out all current DPI employees that have been uploaded. Proceed with caution.</p>
                                        <input type="file" name="fileToUpload" id="fileToUpload">
                                    </div>

                                    <div class="modal-footer">
                                        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-cloud-arrow-up"></i> Upload Employees</button>
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> Close</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <!-- End Upload Public DPI Employees Modal -->
                <?php } ?>

                <script>
                    // initialize variable for if we've drawn a table or not
                    let rate_projection_drawn = 0;

                    // set the search filters for the external salary calculator from prior search saved in session
                    let prior_position = sessionStorage["BAP_SalaryCalculator_External_Position"];
                    let prior_area = sessionStorage["BAP_SalaryCalculator_External_Area"];
                    $('#search-position').val(prior_position);
                    if (getAreas()) { $('#search-area').val(prior_area); } // once we repopulate area selection; set area to prior selection
                    $('#search-work_type').val(sessionStorage["BAP_SalaryCalculator_External_Type"]);
                    $('#search-work_county').val(sessionStorage["BAP_SalaryCalculator_External_County"]);
                    $('#search-work_level').val(sessionStorage["BAP_SalaryCalculator_External_Level"]);

                    // set the search filters for the internal salary comparison from prior search saved in session
                    let prior_title = sessionStorage["BAP_SalaryCalculator_Internal_Title"];
                    let prior_dept = sessionStorage["BAP_SalaryCalculator_Internal_Dept"];
                    $('#search-title').val(prior_title);
                    $('#search-dept').val(prior_dept);

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
                    
                    // view the salary calculator stored in session
                    var view_salary_comparison = sessionStorage["BAP_SalaryCalculator_Selection"];
                    if (view_salary_comparison != null && view_salary_comparison != "")
                    {
                        // select and display the salary calculator previously viewed within the session
                        document.getElementById("salary_comparison-"+view_salary_comparison+"-container").classList.remove("d-none");
                        document.getElementById("salary_comparison-"+view_salary_comparison+"-button").classList.remove("btn-secondary");
                        document.getElementById("salary_comparison-"+view_salary_comparison+"-button").classList.add("btn-primary");

                        // auto-display table based on prior searches
                        if (view_salary_comparison == "external")
                        {
                            // only display if required fields are set
                            if ((prior_position != "" && prior_position != null && prior_position != undefined) && (prior_area != "" && prior_area != null && prior_area != undefined))
                            {
                                displayStateSalaryComparison();
                            }
                        }
                        else if (view_salary_comparison == "internal")
                        {
                            // only display if required fields are set
                            if ((prior_title != "" && prior_title != null && prior_title != undefined) && (prior_dept != "" && prior_dept != null && prior_dept != undefined))
                            {
                                displayInternalSalaryComparison();
                            }
                        }
                    }

                    /** function to display the salaray comparisons for the given position and area */
                    function displayStateSalaryComparison()
                    {
                        // get the position and area
                        let position = document.getElementById("search-position").value;
                        let area = document.getElementById("search-area").value;
                        
                        // get additional filters
                        let work_type = document.getElementById("search-work_type").value;
                        let work_county = document.getElementById("search-work_county").value;
                        let work_level = document.getElementById("search-work_level").value;

                        // store filters in session storage
                        sessionStorage["BAP_SalaryCalculator_External_Position"] = position;
                        sessionStorage["BAP_SalaryCalculator_External_Area"] = area;
                        sessionStorage["BAP_SalaryCalculator_External_Type"] = work_type;
                        sessionStorage["BAP_SalaryCalculator_External_County"] = work_county;
                        sessionStorage["BAP_SalaryCalculator_External_Level"] = work_level;

                        // display loading spinners
                        document.getElementById("salaries-breakdown-div").classList.add("text-center");
                        document.getElementById("salaries-by_experience-div").classList.add("text-center");
                        document.getElementById("salaries-by_degree-div").classList.add("text-center");
                        document.getElementById("salaries-by_gender-div").classList.add("text-center");
                        document.getElementById("salaries-by_race-div").classList.add("text-center");
                        document.getElementById("salaries-by_CESA-div").classList.add("text-center");
                        document.getElementById("salaries-breakdown-div").innerHTML = "<i class='fa-solid fa-spinner fa-spin'></i> Loading overall salaries breakdown...";
                        document.getElementById("salaries-by_experience-div").innerHTML = "<i class='fa-solid fa-spinner fa-spin'></i> Loading salaries by experience breakdown...";
                        document.getElementById("salaries-by_degree-div").innerHTML = "<i class='fa-solid fa-spinner fa-spin'></i> Loading salaries by degree breakdown...";
                        document.getElementById("salaries-by_gender-div").innerHTML = "<i class='fa-solid fa-spinner fa-spin'></i> Loading salaries by gender breakdown...";
                        document.getElementById("salaries-by_race-div").innerHTML = "<i class='fa-solid fa-spinner fa-spin'></i> Loading salaries by race breakdown...";
                        document.getElementById("salaries-by_CESA-div").innerHTML = "<i class='fa-solid fa-spinner fa-spin'></i> Loading salaries by CESA breakdown...";

                        // create the string of data to send
                        let sendString = "position="+encodeURIComponent(position)+"&area="+encodeURIComponent(area)+"&work_type="+encodeURIComponent(work_type)+"&work_county="+encodeURIComponent(work_county)+"&work_level="+encodeURIComponent(work_level);

                        // send the data to create the overall salary breakdown display
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/salaries/createSalaryBreakdownDisplay.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                document.getElementById("salaries-breakdown-div").innerHTML = this.responseText;

                                // initialize the salaries breakdown table                  
                                $(document).ready(function () {
                                    var salaries_breakdown = $("#salaries-breakdown").DataTable({
                                        autoWidth: false,
                                        columns: [
                                            { orderable: false, width: "20%" },
                                            { orderable: false, width: "20%" },
                                            { orderable: false, width: "20%" },
                                            { orderable: false, width: "20%" },
                                            { orderable: false, width: "20%" }
                                        ],
                                        dom: 'rt'
                                    });
                                });

                                document.getElementById("salaries-breakdown-div").classList.remove("text-center");
                            }
                        };
                        xmlhttp.send(sendString);

                        // send the data to create the salary breakdown by experience display
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/salaries/createSalaryByExperienceDisplay.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                document.getElementById("salaries-by_experience-div").innerHTML = this.responseText;

                                // initialize the salaries by experience table                  
                                $(document).ready(function () {
                                    var salaries_by_experience = $("#salaries-by_experience").DataTable({
                                        autoWidth: false,
                                        columns: [
                                            { orderable: false, width: "5.88%" },
                                            { orderable: false, width: "5.88%" },
                                            { orderable: false, width: "5.88%" },
                                            { orderable: false, width: "5.88%" },
                                            { orderable: false, width: "5.88%" },
                                            { orderable: false, width: "5.88%" },
                                            { orderable: false, width: "5.88%" },
                                            { orderable: false, width: "5.88%" },
                                            { orderable: false, width: "5.88%" },
                                            { orderable: false, width: "5.88%" },
                                            { orderable: false, width: "5.88%" },
                                            { orderable: false, width: "5.88%" },
                                            { orderable: false, width: "5.88%" },
                                            { orderable: false, width: "5.89%" },
                                            { orderable: false, width: "5.89%" },
                                            { orderable: false, width: "5.89%" },
                                            { orderable: false, width: "5.89%" }
                                        ],
                                        dom: 'rt'
                                    });
                                });

                                document.getElementById("salaries-by_experience-div").classList.remove("text-center");
                            }
                        };
                        xmlhttp.send(sendString);

                        // send the data to create the salary breakdown by degree display
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/salaries/createSalaryByDegreeDisplay.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                document.getElementById("salaries-by_degree-div").innerHTML = this.responseText;

                                // initialize the salaries by gender table                  
                                $(document).ready(function () {
                                    var salaries_by_gender = $("#salaries-by_degree").DataTable({
                                        autoWidth: false,
                                        columns: [
                                            { orderable: false, width: "15%" },
                                            { orderable: true, width: "5%" },
                                            { orderable: true, width: "5%" },
                                            { orderable: true, width: "5%" },
                                            { orderable: true, width: "5%" },
                                            { orderable: true, width: "5%" },
                                            { orderable: true, width: "5%" },
                                            { orderable: true, width: "5%" },
                                            { orderable: true, width: "5%" },
                                            { orderable: true, width: "5%" },
                                            { orderable: true, width: "5%" },
                                            { orderable: true, width: "5%" },
                                            { orderable: true, width: "5%" },
                                            { orderable: true, width: "5%" },
                                            { orderable: true, width: "5%" },
                                            { orderable: true, width: "5%" },
                                            { orderable: true, width: "5%" },
                                            { orderable: true, width: "5%" }
                                        ],
                                        dom: 'rt'
                                    });
                                });

                                document.getElementById("salaries-by_degree-div").classList.remove("text-center");
                            }
                        };
                        xmlhttp.send(sendString);


                        // send the data to create the salary breakdown by gender display
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/salaries/createSalaryByGenderDisplay.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                document.getElementById("salaries-by_gender-div").innerHTML = this.responseText;

                                // initialize the salaries by gender table                  
                                $(document).ready(function () {
                                    var salaries_by_gender = $("#salaries-by_gender").DataTable({
                                        autoWidth: false,
                                        columns: [
                                            { orderable: false, width: "15%" },
                                            { orderable: true, width: "5%" },
                                            { orderable: true, width: "5%" },
                                            { orderable: true, width: "5%" },
                                            { orderable: true, width: "5%" },
                                            { orderable: true, width: "5%" },
                                            { orderable: true, width: "5%" },
                                            { orderable: true, width: "5%" },
                                            { orderable: true, width: "5%" },
                                            { orderable: true, width: "5%" },
                                            { orderable: true, width: "5%" },
                                            { orderable: true, width: "5%" },
                                            { orderable: true, width: "5%" },
                                            { orderable: true, width: "5%" },
                                            { orderable: true, width: "5%" },
                                            { orderable: true, width: "5%" },
                                            { orderable: true, width: "5%" },
                                            { orderable: true, width: "5%" }
                                        ],
                                        dom: 'rt'
                                    });
                                });

                                document.getElementById("salaries-by_gender-div").classList.remove("text-center");
                            }
                        };
                        xmlhttp.send(sendString);

                        // send the data to create the salary breakdown by race display
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/salaries/createSalaryByRaceDisplay.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                document.getElementById("salaries-by_race-div").innerHTML = this.responseText;

                                // initialize the salaries by race table                  
                                $(document).ready(function () {
                                    var salaries_by_race = $("#salaries-by_race").DataTable({
                                        autoWidth: false,
                                        columns: [
                                            { orderable: false, width: "15%" },
                                            { orderable: true, width: "5%" },
                                            { orderable: true, width: "5%" },
                                            { orderable: true, width: "5%" },
                                            { orderable: true, width: "5%" },
                                            { orderable: true, width: "5%" },
                                            { orderable: true, width: "5%" },
                                            { orderable: true, width: "5%" },
                                            { orderable: true, width: "5%" },
                                            { orderable: true, width: "5%" },
                                            { orderable: true, width: "5%" },
                                            { orderable: true, width: "5%" },
                                            { orderable: true, width: "5%" },
                                            { orderable: true, width: "5%" },
                                            { orderable: true, width: "5%" },
                                            { orderable: true, width: "5%" },
                                            { orderable: true, width: "5%" },
                                            { orderable: true, width: "5%" }
                                        ],
                                        dom: 'rt'
                                    });
                                });

                                document.getElementById("salaries-by_race-div").classList.remove("text-center");
                            }
                        };
                        xmlhttp.send(sendString);

                        // send the data to create the salary breakdown by CESA display
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/salaries/createSalaryByCESADisplay.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                document.getElementById("salaries-by_CESA-div").innerHTML = this.responseText;

                                // initialize the salaries by CESA table                  
                                $(document).ready(function () {
                                    var salaries_by_CESA = $("#salaries-by_CESA").DataTable({
                                        autoWidth: false,
                                        columns: [
                                            { orderable: false, width: "8.33%" },
                                            { orderable: false, width: "8.33%" },
                                            { orderable: false, width: "8.33%" },
                                            { orderable: false, width: "8.33%" },
                                            { orderable: false, width: "8.34%" },
                                            { orderable: false, width: "8.33%" },
                                            { orderable: false, width: "8.33%" },
                                            { orderable: false, width: "8.33%" },
                                            { orderable: false, width: "8.33%" },
                                            { orderable: false, width: "8.33%" },
                                            { orderable: false, width: "8.33%" },
                                            { orderable: false, width: "8.33%" }
                                        ],
                                        dom: 'rt'
                                    });
                                });

                                document.getElementById("salaries-by_CESA-div").classList.remove("text-center");
                            }
                        };
                        xmlhttp.send(sendString);

                        // display the div that holds all tables
                        document.getElementById("salaries-tables-div").classList.remove("d-none");
                    }

                    /** function to display the internal salaray comparisons for the given title and department */
                    function displayInternalSalaryComparison()
                    {
                        // get the position and area
                        let title = document.getElementById("search-title").value;
                        let dept = document.getElementById("search-dept").value;
                        
                        // store filters in session storage
                        sessionStorage["BAP_SalaryCalculator_Internal_Title"] = title;
                        sessionStorage["BAP_SalaryCalculator_Internal_Dept"] = dept;

                        // create the string of data to send
                        let sendString = "title="+encodeURIComponent(title)+"&dept="+encodeURIComponent(dept);

                        // send the data to create the overall salary breakdown display
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/salaries/createInternalSalaryBreakdown.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                document.getElementById("internal-salaries-breakdown-div").innerHTML = this.responseText;

                                // initialize the salaries breakdown table                  
                                $(document).ready(function () {
                                    var internal_salaries_breakdown = $("#internal-salaries-breakdown").DataTable({
                                        dom: 'lfrtiB',
                                        paging: true,
                                        language: {
                                            search: '<div class="input-group mb-1"><div class="input-group-prepend"><span class="d-flex align-items-center h-100 mx-2" id="nav-search-icon"><i class="fa fa-search" aria-hidden="true"></i></span></div>_INPUT_</div>',
                                            lengthMenu: '<div class="mx-1 mb-1">Show _MENU_ entries</div>',
                                            info: '<div class="mx-1">Showing _START_ to _END_ of _TOTAL_ entries</div>'
                                        },
                                        columns: [
                                            { orderable: true }, // id
                                            { orderable: true }, // first name
                                            { orderable: true }, // last name
                                            { orderable: true }, // primary department
                                            { orderable: true }, // DPI assignment
                                            { orderable: true }, // experience
                                            { orderable: true }, // contract days
                                            { orderable: true }, // yearly rate
                                            { orderable: true }, // daily rate
                                            { orderable: true }, // hourly rate 
                                            { orderable: true, visible: false }, // health insurance
                                            { orderable: true, visible: false }, // dental insurance
                                            { orderable: true, visible: false }, // wrs eligibility
                                        ],
                                        order: [ [5, "asc"], [1, "asc"], [2, "asc"] ],
                                        buttons: [
                                            {
                                                extend:    'colvis',
                                                text:      '<i class="fa-solid fa-eye"></i>',
                                                titleAttr: 'Column Visibility',
                                                columns: [10, 11, 12], // only toggle visibility for benefits
                                            }
                                        ],
                                    });
                                });
                            }
                        };
                        xmlhttp.send(sendString);

                        // display the div that holds all tables
                        document.getElementById("internal-salaries-tables-div").classList.remove("d-none");
                    }

                    <?php if ($_SESSION["role"] == 1) { ?>
                        $('#raise_projection-rate').val(sessionStorage["BAP_SalaryCalculator_RaiseProjection_Rate"]);
                        $('#raise_projection-period').val(sessionStorage["BAP_SalaryCalculator_RaiseProjection_Period"]);

                        /** function to create the raise projection table */
                        function calculateRaiseProjection()
                        {
                            // get the selected period and rate
                            let rate = document.getElementById("raise_projection-rate").value;
                            let period = document.getElementById("raise_projection-period").value;
                            
                            if (rate > 0 && (period != undefined && period != "" && period != null))
                            {
                                // update session storage stored search parameter
                                sessionStorage["BAP_SalaryCalculator_RaiseProjection_Rate"] = rate;
                                sessionStorage["BAP_SalaryCalculator_RaiseProjection_Period"] = period;

                                // if we have already drawn the table, destroy the table then redraw
                                if (rate_projection_drawn > 0) { $("#raise-projection-table").DataTable().destroy(); }

                                // initialize the salaries breakdown table                  
                                $(document).ready(function () {
                                    var raise_projection_table = $("#raise-projection-table").DataTable({
                                        ajax: {
                                            url: "ajax/salaries/getRaiseProjection.php",
                                            type: "POST",
                                            data: {
                                                rate: rate,
                                                period: period
                                            }
                                        },
                                        autoWidth: false,
                                        scrollX: "200%",
                                        dom: 'lfrtip',
                                        pageLength: <?php if (isset($USER_SETTINGS["page_length"])) { echo $USER_SETTINGS["page_length"]; } else { echo 10; } ?>,
                                        lengthMenu: [ [10, 25, 50, 100, 250, -1], [10, 25, 50, 100, 250, "All"] ],
                                        order: [], // do not order by default
                                        columns: [
                                            { data: "id" }, // 0
                                            { data: "lname" },
                                            { data: "fname" },
                                            { data: "department" },
                                            { data: "active_days", className: "text-end" },
                                            { data: "active_salary", className: "text-end" }, // 5
                                            { data: "projected_days", className: "text-end" },
                                            { data: "projected_salary", className: "text-end" },
                                            { data: "active_fica", className: "text-end" },
                                            { data: "active_health", className: "text-end" },
                                            { data: "active_dental", className: "text-end" }, // 10
                                            { data: "active_wrs", className: "text-end" },
                                            { data: "active_ltd", className: "text-end" },
                                            { data: "active_life", className: "text-end" },
                                            { data: "active_fringe", className: "text-end" },
                                            { data: "projected_fica", className: "text-end" }, // 15
                                            { data: "projected_health", className: "text-end" },
                                            { data: "projected_dental", className: "text-end" },
                                            { data: "projected_wrs", className: "text-end" },
                                            { data: "projected_ltd", className: "text-end" },
                                            { data: "projected_life", className: "text-end" }, // 20
                                            { data: "projected_fringe", className: "text-end" },
                                            { data: "active_total", className: "text-end" },
                                            { data: "projected_total", className: "text-end" },
                                            { data: "calc_active_days", visible: false },
                                            { data: "calc_active_salary", visible: false }, // 25
                                            { data: "calc_projected_days", visible: false },
                                            { data: "calc_projected_salary", visible: false },
                                            { data: "calc_active_fica", visible: false },
                                            { data: "calc_active_health", visible: false },
                                            { data: "calc_active_dental", visible: false }, // 30
                                            { data: "calc_active_wrs", visible: false },
                                            { data: "calc_active_ltd", visible: false },
                                            { data: "calc_active_life", visible: false },
                                            { data: "calc_active_fringe", visible: false },
                                            { data: "calc_projected_fica", visible: false }, // 35
                                            { data: "calc_projected_health", visible: false },
                                            { data: "calc_projected_dental", visible: false },
                                            { data: "calc_projected_wrs", visible: false },
                                            { data: "calc_projected_ltd", visible: false },
                                            { data: "calc_projected_life", visible: false }, // 40
                                            { data: "calc_projected_fringe", visible: false },
                                            { data: "calc_active_total", visible: false },
                                            { data: "calc_projected_total", visible: false },
                                        ],
                                        drawCallback: function ()
                                        {
                                            var api = this.api();

                                            // get the sum of all filtered expenses
                                            let active_days = api.column(24, { search: "applied" }).data().sum();
                                            let active_salary = api.column(25, { search: "applied" }).data().sum().toFixed(2);
                                            let projected_days = api.column(26, { search: "applied" }).data().sum();
                                            let projected_salary = api.column(27, { search: "applied" }).data().sum().toFixed(2);
                                            let active_fica = api.column(28, { search: "applied" }).data().sum().toFixed(2);
                                            let active_health = api.column(29, { search: "applied" }).data().sum().toFixed(2);
                                            let active_dental = api.column(30, { search: "applied" }).data().sum().toFixed(2);
                                            let active_wrs = api.column(31, { search: "applied" }).data().sum().toFixed(2);
                                            let active_ltd = api.column(32, { search: "applied" }).data().sum().toFixed(2);
                                            let active_life = api.column(33, { search: "applied" }).data().sum().toFixed(2);
                                            let active_fringe = api.column(34, { search: "applied" }).data().sum().toFixed(2);
                                            let projected_fica = api.column(35, { search: "applied" }).data().sum().toFixed(2);
                                            let projected_health = api.column(36, { search: "applied" }).data().sum().toFixed(2);
                                            let projected_dental = api.column(37, { search: "applied" }).data().sum().toFixed(2);
                                            let projected_wrs = api.column(38, { search: "applied" }).data().sum().toFixed(2);
                                            let projected_ltd = api.column(39, { search: "applied" }).data().sum().toFixed(2);
                                            let projected_life = api.column(40, { search: "applied" }).data().sum().toFixed(2);
                                            let projected_fringe = api.column(41, { search: "applied" }).data().sum().toFixed(2);
                                            let active_total = api.column(42, { search: "applied" }).data().sum().toFixed(2);
                                            let projected_total = api.column(43, { search: "applied" }).data().sum().toFixed(2);

                                            // calculate difference between active and projected totals
                                            let salary_difference = (projected_salary - active_salary).toFixed(2);
                                            let fringe_difference = (projected_fringe - active_fringe).toFixed(2);
                                            let total_difference = (projected_total - active_total).toFixed(2);

                                            // calculate the percentage increase from the active to projected totals
                                            let salary_increase = ((salary_difference / active_salary) * 100);
                                            let fringe_increase = ((fringe_difference / active_fringe) * 100);
                                            let total_increase = ((total_difference / active_total) * 100);

                                            // update the table footer
                                            document.getElementById("active-days").innerHTML = numberWithCommas(active_days);
                                            document.getElementById("active-salary").innerHTML = "$"+numberWithCommas(active_salary);
                                            document.getElementById("projected-days").innerHTML = numberWithCommas(projected_days);
                                            document.getElementById("projected-salary").innerHTML = "$"+numberWithCommas(projected_salary);
                                            document.getElementById("active-fica").innerHTML = "$"+numberWithCommas(active_fica);
                                            document.getElementById("active-health").innerHTML = "$"+numberWithCommas(active_health);
                                            document.getElementById("active-dental").innerHTML = "$"+numberWithCommas(active_dental);
                                            document.getElementById("active-wrs").innerHTML = "$"+numberWithCommas(active_wrs);
                                            document.getElementById("active-ltd").innerHTML = "$"+numberWithCommas(active_ltd);
                                            document.getElementById("active-life").innerHTML = "$"+numberWithCommas(active_life);
                                            document.getElementById("active-fringe").innerHTML = "$"+numberWithCommas(active_fringe);
                                            document.getElementById("projected-fica").innerHTML = "$"+numberWithCommas(projected_fica);
                                            document.getElementById("projected-health").innerHTML = "$"+numberWithCommas(projected_health);
                                            document.getElementById("projected-dental").innerHTML = "$"+numberWithCommas(projected_dental);
                                            document.getElementById("projected-wrs").innerHTML = "$"+numberWithCommas(projected_wrs);
                                            document.getElementById("projected-ltd").innerHTML = "$"+numberWithCommas(projected_ltd);
                                            document.getElementById("projected-life").innerHTML = "$"+numberWithCommas(projected_life);
                                            document.getElementById("projected-fringe").innerHTML = "$"+numberWithCommas(projected_fringe);
                                            document.getElementById("active-total").innerHTML = "$"+numberWithCommas(active_total);
                                            document.getElementById("projected-total").innerHTML = "$"+numberWithCommas(projected_total);

                                            // update totals 
                                            document.getElementById("active-total_salary").innerHTML = "$"+numberWithCommas(active_salary);
                                            document.getElementById("active-total_fringe").innerHTML = "$"+numberWithCommas(active_fringe);
                                            document.getElementById("active-total_compensation").innerHTML = "$"+numberWithCommas(active_total);
                                            document.getElementById("projected-total_salary").innerHTML = "$"+numberWithCommas(projected_salary);
                                            document.getElementById("projected-total_fringe").innerHTML = "$"+numberWithCommas([projected_fringe]);
                                            document.getElementById("projected-total_compensation").innerHTML = "$"+numberWithCommas(projected_total);
                                            document.getElementById("diff-total_salary").innerHTML = "$"+numberWithCommas(salary_difference);
                                            document.getElementById("diff-total_fringe").innerHTML = "$"+numberWithCommas(fringe_difference);
                                            document.getElementById("diff-total_compensation").innerHTML = "$"+numberWithCommas(total_difference);
                                            document.getElementById("inc-total_salary").innerHTML = salary_increase.toFixed(2)+"%";
                                            document.getElementById("inc-total_fringe").innerHTML = fringe_increase.toFixed(2)+"%";
                                            document.getElementById("inc-total_compensation").innerHTML = total_increase.toFixed(2)+"%";
                                        },
                                    });

                                    // mark that we have drawn the table
                                    rate_projection_drawn = 1;

                                    // display the div that holds all tables
                                    document.getElementById("raise-projection-table-container").classList.remove("d-none");
                                });
                            }
                        }

                        // auto-display data
                        calculateRaiseProjection();
                    <?php } ?>
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