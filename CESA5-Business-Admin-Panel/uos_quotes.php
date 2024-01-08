<?php 
    include_once("header.php");
    include("getSettings.php");

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1) 
    { 
        if (isset($PERMISSIONS["VIEW_CASELOADS_ALL"]) && isset($PERMISSIONS["VIEW_THERAPISTS"]))
        {
            // connect to the database
            $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

            // initialize an array to store all periods; then get all periods and store in the array
            $periods = [];
            $term_start = "2023-09-01";
            $term_end = "2024-06-01";
            $getPeriods = mysqli_query($conn, "SELECT id, name, active, caseload_term_start, caseload_term_end FROM `periods` ORDER BY active DESC, name ASC");
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
                        $term_start = $period["caseload_term_start"];
                        $term_end = $period["caseload_term_end"];
                    }
                }
            }

            // set term start and end dates in proper format
            $term_start = date("m/d/Y", strtotime($term_start));
            $term_end = date("m/d/Y", strtotime($term_end));

            // calculate the number of days between the term start and term end dates
            $days_in_term = getDaysBetween($term_start, $term_end);

            ?>
                <style>
                    /* date picker hover */
                    .ui-state-hover,
                    .ui-widget-content .ui-state-hover,
                    .ui-widget-header .ui-state-hover,
                    .ui-state-focus,
                    .ui-widget-content .ui-state-focus,
                    .ui-widget-header .ui-state-focus 
                    {
                        background: #f05323CC;
                        color: #ffffff;
                        font-weight: 600;
                    }

                    /* date picker active */
                    .ui-state-active,
                    .ui-widget-content .ui-state-active,
                    .ui-widget-header .ui-state-active 
                    {
                        border: 1px solid #fbd850;
                        background: #f05323;
                        font-weight: 600;
                        color: #ffffff;
                    }
                </style>

                <script>
                    /** function to calculate the annaul units of service for a frequency */
                    function calculateUOS(value, type)
                    {
                        // initialize # of days in the term cycle
                        let days_in_term = <?php echo $days_in_term; ?>;

                        // get dates
                        let startInput = document.getElementById("start_date").value;
                        let endInput = document.getElementById("end_date").value;

                        // convert date inputs to Date types
                        let start = new Date(startInput);
                        let end = new Date(endInput);

                        // calculate time between two dates
                        let time_between = end.getTime() - start.getTime();

                        // calculate days between
                        let days_in_cycle = time_between / (1000 * 3600 * 24);

                        // calculate the percentage of days the cycle is relative to the term
                        let percentage_of_term = 1;
                        if (days_in_term > 0) { percentage_of_term = days_in_cycle / days_in_term; } else { percentage_of_term = 0; }

                        // iniitalize UOS
                        let annual_uos = 0;

                        // parse the input
                        let parsed_value = parseInt(value);

                        // determine the multiplier based on frequency type
                        let type_multiply = 0;
                        if (type == "week") { type_multiply = 36; }
                        else if (type == "month") { type_multiply = 9; }
                        else if (type == "quarter") { type_multiply = 4; }
                        else if (type == "trimester") { type_multiply = 3; }
                        else if (type == "semester") { type_multiply = 2; }
                        else if (type == "year") { type_multiply = 1; }

                        // calculate the annual units of service
                        if (parsed_value > 0) { annual_uos = (((((parsed_value * type_multiply) * 0.3) + (parsed_value * type_multiply)) / 15) + 12); }
                        annual_uos = annual_uos * percentage_of_term;

                        document.getElementById("total-"+type).value = annual_uos.toFixed(2);

                        calculateTotalUOS();
                    }

                    /** function to calculate the combined annual UOS total */
                    function calculateTotalUOS()
                    {
                        // initialize the total (assume 0)
                        let total = 0;

                        // get the current element values - parse to an integer
                        let weekly = parseFloat(document.getElementById("total-week").value);
                        let monthly = parseFloat(document.getElementById("total-month").value);
                        let quarterly = parseFloat(document.getElementById("total-quarter").value);
                        let trimesterly = parseFloat(document.getElementById("total-trimester").value);
                        let semesterly = parseFloat(document.getElementById("total-semester").value);
                        let yearly = parseFloat(document.getElementById("total-year").value);

                        if (isNaN(weekly)) { weekly = 0; }
                        if (isNaN(monthly)) { monthly = 0; }
                        if (isNaN(quarterly)) { quarterly = 0; }
                        if (isNaN(trimesterly)) { trimesterly = 0; }
                        if (isNaN(semesterly)) { semesterly = 0; }
                        if (isNaN(yearly)) { yearly = 0; }

                        total = weekly + monthly + quarterly + trimesterly + semesterly + yearly;

                        document.getElementById("total-uos").value = Math.ceil(total);

                        // get estimated cost
                        estimateCost();
                    }

                    /** function to clear the UOS calculator */
                    function clearCalculator()
                    {
                        // set all form elements to blank
                        document.getElementById("input-week").value = "";
                        document.getElementById("input-month").value = "";
                        document.getElementById("input-quarter").value = "";
                        document.getElementById("input-trimester").value = "";
                        document.getElementById("input-semester").value = "";
                        document.getElementById("input-year").value = "";
                        document.getElementById("total-week").value = "";
                        document.getElementById("total-month").value = "";
                        document.getElementById("total-quarter").value = "";
                        document.getElementById("total-trimester").value = "";
                        document.getElementById("total-semester").value = "";
                        document.getElementById("total-year").value = "";
                        document.getElementById("total-uos").value = "";
                        document.getElementById("total-cost").value = 0;
                    }

                    /** function to update the estimated invoice cost */
                    function estimateCost()
                    {
                        // get form fields
                        let period = document.getElementById("period").value;
                        let service_id = document.getElementById("service").value;
                        let qty = document.getElementById("total-uos").value;

                        // send the data to process the add customer request
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/services/manage/getEstimatedCost.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                if (this.responseText != "<span class='missing-field'>Cost not found</span>") {
                                    document.getElementById("total-cost").value = this.responseText;
                                } else {
                                    document.getElementById("total-cost").value = 0;
                                }
                            }
                        }
                        xmlhttp.send("service_id="+service_id+"&period="+period+"&quantity="+qty);
                    }

                    $(document).ready(function() {
                        $("#start_date").datepicker({
                            changeMonth: true,
                        });

                        $("#end_date").datepicker({
                            changeMonth: true,
                        });
                    });
                </script>

                <div class="report">
                    <div class="row justify-content-center report-header mb-3 mx-0"> 
                        <div class="col-sm-12 col-md-8 col-lg-8 col-xl-6 col-xxl-6 p-0">
                            <h1 class="report-title m-0">UOS Quotes</h1>
                            <p class="report-description m-0">Enter in the fields and get the units of service based on the frequency.</p>
                        </div>
                    </div>

                    <div class="row report-body justify-content-center m-0">
                        <div class="col-12 col-sm-12 col-md-10 col-lg-8 col-xl-6 col-xxl-6">
                            <div class="form-row d-flex justify-content-center align-items-center mb-3">
                                <!-- Period -->
                                <div class="form-group col-5">
                                    <label for="period"><h3 class="mb-0">Period</h3></label>
                                    <div class="input-group h-auto">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text h-100" id="nav-search-icon"><i class="fa-solid fa-calendar-days"></i></span>
                                        </div>
                                        <select class="form-select" id="period" name="period" onchange="calculateTotalUOS();">
                                            <?php
                                                for ($p = 0; $p < count($periods); $p++)
                                                {
                                                    echo "<option value='".$periods[$p]["name"]."'>".$periods[$p]["name"]."</option>";
                                                }
                                            ?>
                                        </select>
                                    </div>
                                </div>

                                <!-- Divider -->
                                <div class="form-group col-1 p-0"></div>

                                <!-- Service -->
                                <div class="form-group col-5">
                                    <label for="service"><h3 class="mb-0">Service</h3></label>
                                    <select class="form-select" id="service" name="service" onchange="calculateTotalUOS();">
                                        <option></option>
                                        <?php
                                            // get services for all caseload categories with UOS enabled
                                            $getServices = mysqli_query($conn, "SELECT DISTINCT cc.service_id, s.name FROM caseload_categories cc
                                                                                JOIN services s ON cc.service_id=s.id
                                                                                WHERE cc.uos_enabled=1
                                                                                ORDER BY cc.service_id ASC");
                                            if (mysqli_num_rows($getServices) > 0)
                                            {
                                                while ($service = mysqli_fetch_assoc($getServices))
                                                {
                                                    // store service details locally
                                                    $service_id = $service["service_id"];
                                                    $service_name = $service["name"];

                                                    // create option
                                                    echo "<option value='".$service_id."'>".$service_id." - ".$service_name."</option>";
                                                }
                                            }
                                        ?>
                                    </select>
                                </div>
                            </div>

                            <div class="form-row d-flex justify-content-center align-items-center mb-3">
                                <!-- Start Date -->
                                <div class="form-group col-5">
                                    <label for="start_date"><h3 class="mb-0">Start Date</h3></label>
                                    <div class="input-group h-auto">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text h-100" id="nav-search-icon"><i class="fa-solid fa-calendar-day"></i></span>
                                        </div>
                                        <input type="text" class="form-control " id="start_date" name="start_date" value="<?php echo $term_start; ?>" required>
                                    </div>
                                </div>

                                <!-- Divider -->
                                <div class="form-group col-1 p-0"></div>
                                
                                <!-- End Date -->
                                <div class="form-group col-5">
                                    <label for="end_date"><h3 class="mb-0">End Date</h3></label>
                                    <div class="input-group h-auto">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text h-100" id="nav-search-icon"><i class="fa-solid fa-calendar-day"></i></span>
                                        </div>
                                        <input type="text" class="form-control" id="end_date" name="end_date" value="<?php echo $term_end; ?>" required>
                                    </div>
                                </div>
                            </div>

                            <div class="form-row d-flex justify-content-center align-items-center">
                                <!-- Enrollment Type -->
                                <div class="form-group col-3">
                                    <h2><b>Frequency</b></h2>
                                </div>

                                <!-- Divider -->
                                <div class="form-group col-1"></div>
                                
                                <!-- Enrollment Type -->
                                <div class="form-group col-3">
                                    
                                </div>

                                <!-- Divider -->
                                <div class="form-group col-1"></div>

                                <!-- Enrollment Type -->
                                <div class="form-group col-3">
                                    <h2><b>Annual UOS</b></h2>
                                </div>
                            </div>

                            <div class="form-row d-flex justify-content-center align-items-center mt-2 mb-4">
                                <div class="form-group col-3">
                                    <label for="total-week"><h3>Minutes/Week</h3></label>
                                </div>

                                <!-- Divider -->
                                <div class="form-group col-1"></div>
                                
                                <div class="form-group col-3">
                                    <input class="form-control" type="number" id="input-week" name="input-week" onkeyup="calculateUOS(this.value, 'week');">
                                </div>

                                <!-- Divider -->
                                <div class="form-group col-1"></div>

                                <div class="form-group col-3">
                                    <input class="form-control" type="number" id="total-week" name="total-week" readonly disabled>
                                </div>
                            </div>

                            <div class="form-row d-flex justify-content-center align-items-center my-4">
                                <div class="form-group col-3">
                                    <label for="total-month"><h3>Minutes/Month</h3></label>
                                </div>

                                <!-- Divider -->
                                <div class="form-group col-1"></div>
                                
                                <div class="form-group col-3">
                                    <input class="form-control" type="number" id="input-month" name="input-month" onkeyup="calculateUOS(this.value, 'month');">
                                </div>

                                <!-- Divider -->
                                <div class="form-group col-1"></div>

                                <div class="form-group col-3">
                                    <input class="form-control" type="number" id="total-month" name="total-month" readonly disabled>
                                </div>
                            </div>

                            <div class="form-row d-flex justify-content-center align-items-center my-4">
                                <div class="form-group col-3">
                                    <label for="total-quarter"><h3>Minutes/Quarter</h3></label>
                                </div>

                                <!-- Divider -->
                                <div class="form-group col-1"></div>
                                
                                <div class="form-group col-3">
                                    <input class="form-control" type="number" id="input-quarter" name="input-quarter" onkeyup="calculateUOS(this.value, 'quarter');">
                                </div>

                                <!-- Divider -->
                                <div class="form-group col-1"></div>

                                <div class="form-group col-3">
                                    <input class="form-control" type="number" id="total-quarter" name="total-quarter" readonly disabled>
                                </div>
                            </div>

                            <div class="form-row d-flex justify-content-center align-items-center my-4">
                                <div class="form-group col-3">
                                    <label for="total-trimester"><h3>Minutes/Trimester</h3></label>
                                </div>

                                <!-- Divider -->
                                <div class="form-group col-1"></div>
                                
                                <div class="form-group col-3">
                                    <input class="form-control" type="number" id="input-trimester" name="input-trimester" onkeyup="calculateUOS(this.value, 'trimester');">
                                </div>

                                <!-- Divider -->
                                <div class="form-group col-1"></div>

                                <div class="form-group col-3">
                                    <input class="form-control" type="number" id="total-trimester" name="total-trimester" readonly disabled>
                                </div>
                            </div>

                            <div class="form-row d-flex justify-content-center align-items-center my-4">
                                <div class="form-group col-3">
                                    <label for="total-semester"><h3>Minutes/Semester</h3></label>
                                </div>

                                <!-- Divider -->
                                <div class="form-group col-1"></div>
                                
                                <div class="form-group col-3">
                                    <input class="form-control" type="number" id="input-semester" name="input-semester" onkeyup="calculateUOS(this.value, 'semester');">
                                </div>

                                <!-- Divider -->
                                <div class="form-group col-1"></div>

                                <div class="form-group col-3">
                                    <input class="form-control" type="number" id="total-semester" name="total-semester" readonly disabled>
                                </div>
                            </div>

                            <div class="form-row d-flex justify-content-center align-items-center my-4">
                                <div class="form-group col-3">
                                    <label for="total-year"><h3>Minutes/Year</h3></label>
                                </div>

                                <!-- Divider -->
                                <div class="form-group col-1"></div>
                                
                                <div class="form-group col-3">
                                    <input class="form-control" type="number" id="input-year" name="input-year" onkeyup="calculateUOS(this.value, 'year');">
                                </div>

                                <!-- Divider -->
                                <div class="form-group col-1"></div>

                                <div class="form-group col-3">
                                    <input class="form-control" type="number" id="total-year" name="total-year" readonly disabled>
                                </div>
                            </div>

                            <div class="form-row d-flex justify-content-center align-items-center my-4">
                                <div class="form-group col-3">
                                    <h3><label for="total-uos"><b>TOTAL</b> <i>(rounded)</i></label></h3>
                                </div>

                                <!-- Divider -->
                                <div class="form-group col-5"></div>

                                <div class="form-group col-3">
                                    <input class="form-control" type="number" id="total-uos" readonly disabled>
                                </div>
                            </div>

                            <div class="form-row d-flex justify-content-center align-items-center my-4">
                                <!-- Clear Calculator -->
                                <div class="form-group col-7">
                                    <button class="btn btn-secondary w-100" onclick="clearCalculator();">
                                        <div class="row">
                                            <div class="col-3"><i class="fa-solid fa-delete-left"></i></div>
                                            <div class="col-6">Clear</div>
                                            <div class="col-3"></div>
                                        </div>
                                    </button>
                                </div>

                                <!-- Divider -->
                                <div class="form-group col-1"></div>

                                <!-- Estimated Cost -->
                                <div class="form-group col-3">
                                    <!-- Estimated Cost -->
                                    <div class="input-group h-auto">
                                        <span class="input-group-text" id="nav-search-icon"><i class="fa-solid fa-dollar-sign"></i></span>
                                        <input type="text" class="form-control" id="total-cost" name="total-cost" value="0"  readonly disabled>
                                        <span class="input-group-text">.00</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php 

            // disconnect from the database
            mysqli_close($conn);
        }
        else { denyAccess(); }
    }
    else { goToLogin(); }

    include("footer.php"); 
?>