<?php 
    include_once("header.php");

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1) 
    { 
        if (isset($_SESSION["role"]) && $_SESSION["role"] == 1)
        {
            // connect to the database
            $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

            // initialize all default automaion settings
            $automation["employees"]["enabled"] = 0;
            $automation["employees"]["cycle"] = "";
            $automation["employees"]["daily"]["runtime"] = "12:00 AM";
            $automation["employees"]["weekly"]["runtime"] = "12:00 AM";
            $automation["employees"]["custom"]["runtime"] = "12:00 AM";
            $automation["employees"]["daily"]["days"] = [];
            $automation["employees"]["weekly"]["days"] = [];
            $automation["employees"]["custom"]["days"] = [];

            // get currently set automation settings
            $getAutomation = mysqli_query($conn, "SELECT DISTINCT setting FROM automation WHERE enabled=1");
            if (mysqli_num_rows($getAutomation) > 0) // automation settings found
            {
                while ($distinctAuto = mysqli_fetch_array($getAutomation))
                {
                    // store the setting label locally
                    $distinctSetting = $distinctAuto["setting"];

                    // set setting to enabled
                    if ($distinctSetting == "autoEmployeeUpload") 
                    { 
                        $automation["employees"]["enabled"] = 1;
                    }
                }
            }
                                
            // get all automation settings
            $getAutoSettings = mysqli_query($conn, "SELECT * FROM automation");
            if (mysqli_num_rows($getAutoSettings) > 0)
            {
                while ($entry = mysqli_fetch_array($getAutoSettings))
                {
                    // initialize variables to store days
                    $days = [];

                    // store automation settings locally
                    $setting = $entry["setting"];
                    $cycle = $entry["cycle"];
                    $runtime = date("H:i:00", strtotime($entry["runtime"]));
                    $sun = $entry["sunday"];
                    $mon = $entry["monday"];
                    $tue = $entry["tuesday"];
                    $wed = $entry["wednesday"];
                    $thu = $entry["thursday"];
                    $fri = $entry["friday"];
                    $sat = $entry["saturday"];
                    $enabled = $entry["enabled"];

                    // set the days automation is enabled on
                    if ($sun == 1) { $days[] = "sun"; }
                    if ($mon == 1) { $days[] = "mon"; }
                    if ($tue == 1) { $days[] = "tue"; }
                    if ($wed == 1) { $days[] = "wed"; }
                    if ($thu == 1) { $days[] = "thu"; }
                    if ($fri == 1) { $days[] = "fri"; }
                    if ($sat == 1) { $days[] = "sat"; }

                    // update automation settings variables
                    if ($setting == "autoEmployeeUpload") 
                    { 
                        $automation["employees"]["cycle"] = $cycle;
                        $automation["employees"][$cycle]["runtime"] = $runtime;
                        $automation["employees"][$cycle]["days"] = $days;
                    }
                }
            }

            ?> 
                <!-- Page Specific Styling -->
                <style>
                    .accordion-header, .accordion-button
                    {
                        font-size: 20px !important;
                        font-weight: 500 !important;
                    }

                    <?php if (isset($USER_SETTINGS) && $USER_SETTINGS["dark_mode"] == 1) { ?>
                        .accordion-header, .accordion-button
                        {
                            background-color: #1c1c1c !important;
                            color: #ffffff !important;
                        }

                        .accordion-item
                        {
                            background-color: #1c1c1c !important;
                            color: #ffffff !important;
                        }
                    <?php } ?>
                </style>

                <script>
                    /** function to toggle automation for the setting clicked */
                    function toggleAutomation(element_id)
                    {
                        let element = document.getElementById(element_id);
                        let value = element.value;
                        
                        if (value == 0)
                        {
                            element.classList.remove("btn-outline-secondary");
                            element.classList.remove("switch-inactive");
                            element.classList.add("btn-success");
                            element.classList.add("switch-active");
                            element.value = 1;
                        }
                        else
                        {
                            element.classList.remove("btn-success");
                            element.classList.remove("switch-active");
                            element.classList.add("btn-outline-secondary");
                            element.classList.add("switch-inactive");
                            element.value = 0;
                        }
                    }

                    /** function to toggle the employees upload setting */
                    function toggleEmployeesUpload(setting)
                    {
                        // deselect both settings
                        document.getElementById("auto-empsUpload-daily").classList.add("d-none");
                        document.getElementById("auto-empsUpload-weekly").classList.add("d-none");
                        document.getElementById("auto-empsUpload-custom").classList.add("d-none");
                        document.getElementById("auto-empsUpload-daily-btn").classList.remove("btn-primary");
                        document.getElementById("auto-empsUpload-weekly-btn").classList.remove("btn-primary");
                        document.getElementById("auto-empsUpload-custom-btn").classList.remove("btn-primary");
                        document.getElementById("auto-empsUpload-daily-btn").classList.add("btn-secondary");
                        document.getElementById("auto-empsUpload-weekly-btn").classList.add("btn-secondary");
                        document.getElementById("auto-empsUpload-custom-btn").classList.add("btn-secondary");
                        document.getElementById("auto-empsUpload-daily-btn").value = 0;
                        document.getElementById("auto-empsUpload-weekly-btn").value = 0;
                        document.getElementById("auto-empsUpload-custom-btn").value = 0;

                        // select the setting toggled
                        document.getElementById("auto-empsUpload-"+setting+"-btn").classList.add("btn-primary");
                        document.getElementById("auto-empsUpload-"+setting).classList.remove("d-none");
                        document.getElementById("auto-empsUpload-"+setting+"-btn").value = 1;
                    }
                    
                    /** function to toggle days for the employee upload automation CUSTOM DAYS set */
                    function toggleEmployeesUploadDay(day, type, multiple = 0)
                    {
                        // if multiple options is not selected; disable all days
                        if (multiple == 0)
                        {
                            let days = ["sun", "mon", "tue", "wed", "thu", "fri", "sat"];
                            for (let d = 0; d < days.length; d++)
                            {
                                document.getElementById("auto-empsUpload-"+type+"-"+days[d]).classList.remove("btn-primary");
                                document.getElementById("auto-empsUpload-"+type+"-"+days[d]).classList.add("btn-outline-secondary");
                                document.getElementById("auto-empsUpload-"+type+"-"+days[d]).value = 0;
                            }
                        }

                        // store the element locally
                        let element = document.getElementById("auto-empsUpload-"+type+"-"+day);
                    
                        // get the current value of the day
                        let value = element.value;

                        if (value == 0)
                        {
                            element.classList.remove("btn-outline-secondary");
                            element.classList.add("btn-primary");
                            element.value = 1;
                        }
                        else
                        {
                            element.classList.remove("btn-primary");
                            element.classList.add("btn-outline-secondary");
                            element.value = 0;
                        }
                    }

                    $(document).ready(function(){
                        $("#auto-empsUpload-daily-time").timepicker({
                            timeFormat: "h:mm p",
                            interval: 30,
                            defaultTime: "<?php echo $automation["employees"]["daily"]["runtime"]; ?>",
                            startTime: "0:00",
                            dynamic: true,
                            dropdown: true,
                            scrollbar: true
                        });

                        $("#auto-empsUpload-weekly-time").timepicker({
                            timeFormat: "h:mm p",
                            interval: 30,
                            defaultTime: "<?php echo $automation["employees"]["weekly"]["runtime"]; ?>",
                            startTime: "0:00",
                            dynamic: true,
                            dropdown: true,
                            scrollbar: true
                        });

                        $("#auto-empsUpload-custom-time").timepicker({
                            timeFormat: "h:mm p",
                            interval: 30,
                            defaultTime: "<?php echo $automation["employees"]["custom"]["runtime"]; ?>",
                            startTime: "0:00",
                            dynamic: true,
                            dropdown: true,
                            scrollbar: true
                        });
                    });

                    /** function to save automation settings */
                    function saveAutomation()
                    {
                        saveAutomatedEmployeesUpload();
                    }

                    /** function to save automated employees upload automation settings */
                    function saveAutomatedEmployeesUpload()
                    {
                        // initialize variable to store days
                        let days = ["sun", "mon", "tue", "wed", "thu", "fri", "sat"];

                        // initialize variable to store cycle setting 
                        let cycle = "unknown";

                        // initialize variables to store time and day settings
                        let runtime = "12:00 AM";
                        let day_val = 0;
                        let runDays = [];

                        // get cycle settings
                        let enabled = document.getElementById("auto-empsUpload-switch").value;
                        let cycle_daily = document.getElementById("auto-empsUpload-daily-btn").value;
                        let cycle_weekly = document.getElementById("auto-empsUpload-weekly-btn").value;
                        let cycle_custom = document.getElementById("auto-empsUpload-custom-btn").value;
                        if (cycle_daily == 1) { cycle = "daily"; }
                        else if (cycle_weekly == 1) { cycle = "weekly"; }
                        else if (cycle_custom == 1) { cycle = "custom"; }

                        // build the string of data to send
                        let sendString = "setting=autoEmployeeUpload&enabled="+enabled+"&cycle="+cycle;

                        // get time and day settings
                        if (cycle == "daily")
                        {
                            runtime = document.getElementById("auto-empsUpload-daily-time").value;
                            runDays = days;
                        }
                        else if (cycle == "weekly" || cycle == "custom")
                        {
                            runtime = document.getElementById("auto-empsUpload-"+cycle+"-time").value;
                            for (let d = 0; d < days.length; d++)
                            {
                                day_val = document.getElementById("auto-empsUpload-"+cycle+"-"+days[d]).value;
                                if (day_val == 1) { runDays.push(days[d]); }
                            }                    
                        }
                        
                        // append time and day settings to string of data to send
                        sendString += "&runtime="+runtime+"&days="+JSON.stringify(runDays);

                        // create and send AJAX request to update the automation setting
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "automation/saveAutomation.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {

                            }
                        };
                        xmlhttp.send(sendString);
                    }
                </script>

                <!-- Header -->
                <div class="row m-0 p-0">
                    <h1 class="col-12 col-sm-8 col-md-6 col-lg-4 col-xl-4 col-xxl-4 page-header my-3 py-3 ps-3 pe-5">
                        <a class="back-button" href="manage.php" title="Return to Manage."><i class="fa-solid fa-angles-left"></i></a>
                        <div class="d-inline float-end">Automation</div>
                    </h1>
                </div>

                <div class="alert alert-danger text-center mx-3 mb-3" role="alert">
                    <i class="fa-solid fa-triangle-exclamation"></i> <b>THIS PAGE IS CURRENTLY UNDERGOING DEVELOPMENT. AUTOMATION FEATURES WILL NOT FUNCTION YET.</b>
                </div>

                <!-- Body -->
                <div class="row d-flex justify-content-center align-items-top m-0">
                    <div class="col-2 col-sm-2 col-md-2 col-lg-1 col-xl-1 col-xxl-1 btn-toggle-switch">
                        <?php if ($automation["employees"]["enabled"] == 1) { ?> 
                            <button class="btn btn-success switch-active w-100 h-100" id="auto-empsUpload-switch" value="1" onclick="toggleAutomation('auto-empsUpload-switch');"><i class="fa-solid fa-power-off fa-lg"></i></button>
                        <?php } else { ?>
                            <button class="btn btn-outline-secondary switch-inactive w-100 h-100" id="auto-empsUpload-switch" value="0" onclick="toggleAutomation('auto-empsUpload-switch');"><i class="fa-solid fa-power-off fa-lg"></i></button>
                        <?php } ?>
                    </div>

                    <div class="col-10 col-sm-10 col-md-10 col-lg-11 col-xl-11 col-xxl-11">
                        <div class="accordion" id="accordionExample">
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingTwo">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                        Automated Employees Upload
                                    </button>
                                </h2>
                                <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#accordionExample">
                                    <div class="accordion-body">
                                        <p>
                                            Automatically upload employees at the set time depending on the cycle you select. You can upload employees daily or weekly, at whatever time you set.
                                            At the time selected, we will scan the SFTP directly for any files, and then upload each file found within the directory. We suggest either clearing out
                                            the directory after upload, or overriding the previous file with the new file each time you upload employees.
                                        </p>

                                        <div class="row d-flex justify-content-center my-3">
                                            <div class="col-12 col-sm-12 col-md-12 col-lg-8 col-xl-8 col-xxl-8">
                                                <div class="btn-group w-100" role="group">
                                                    <?php if ($automation["employees"]["cycle"] == "daily" && $automation["employees"]["enabled"] == 1) { ?>
                                                        <button class="btn btn-primary btn-lg w-100" id="auto-empsUpload-daily-btn" value="1" onclick="toggleEmployeesUpload('daily');">Daily</button>
                                                        <button class="btn btn-secondary btn-lg w-100" id="auto-empsUpload-weekly-btn" value="0" onclick="toggleEmployeesUpload('weekly');">Weekly</button>
                                                        <button class="btn btn-secondary btn-lg w-100" id="auto-empsUpload-custom-btn" value="0" onclick="toggleEmployeesUpload('custom');">Custom</button>
                                                    <?php } else if ($automation["employees"]["cycle"] == "weekly" && $automation["employees"]["enabled"] == 1) { ?>
                                                        <button class="btn btn-secondary btn-lg w-100" id="auto-empsUpload-daily-btn" value="0" onclick="toggleEmployeesUpload('daily');">Daily</button>
                                                        <button class="btn btn-primary btn-lg w-100" id="auto-empsUpload-weekly-btn" value="1" onclick="toggleEmployeesUpload('weekly');">Weekly</button>
                                                        <button class="btn btn-secondary btn-lg w-100" id="auto-empsUpload-custom-btn" value="0" onclick="toggleEmployeesUpload('custom');">Custom</button>
                                                    <?php } else if ($automation["employees"]["cycle"] == "custom" && $automation["employees"]["enabled"] == 1) { ?>
                                                        <button class="btn btn-secondary btn-lg w-100" id="auto-empsUpload-daily-btn" value="0" onclick="toggleEmployeesUpload('daily');">Daily</button>
                                                        <button class="btn btn-secondary btn-lg w-100" id="auto-empsUpload-weekly-btn" value="0" onclick="toggleEmployeesUpload('weekly');">Weekly</button>
                                                        <button class="btn btn-primary btn-lg w-100" id="auto-empsUpload-custom-btn" value="1" onclick="toggleEmployeesUpload('custom');">Custom</button>
                                                    <?php } else { ?>
                                                        <button class="btn btn-secondary btn-lg w-100" id="auto-empsUpload-daily-btn" value="0" onclick="toggleEmployeesUpload('daily');">Daily</button>
                                                        <button class="btn btn-secondary btn-lg w-100" id="auto-empsUpload-weekly-btn" value="0" onclick="toggleEmployeesUpload('weekly');">Weekly</button>
                                                        <button class="btn btn-secondary btn-lg w-100" id="auto-empsUpload-custom-btn" value="0" onclick="toggleEmployeesUpload('custom');">Custom</button>
                                                    <?php } ?>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row d-flex justify-content-center <?php if ($automation["employees"]["cycle"] != "daily" || $automation["employees"]["enabled"] == 0) { echo "d-none"; } ?>" id="auto-empsUpload-daily">
                                            <div class="col-12 col-sm-12 col-md-12 col-lg-8 col-xl-8 col-xxl-8 my-2">
                                                <label for="auto-empsUpload-daily-time">Run time:</label>
                                                <input class="form-control timepicker" id="auto-empsUpload-daily-time" name="auto-empsUpload-daily-time" value="<?php echo $automation["employees"]["daily"]["runtime"]; ?>">
                                            </div>
                                        </div>

                                        <div class="row d-flex justify-content-center <?php if ($automation["employees"]["cycle"] != "weekly" || $automation["employees"]["enabled"] == 0) { echo "d-none"; } ?>" id="auto-empsUpload-weekly">
                                            <div class="col-12 col-sm-12 col-md-12 col-lg-8 col-xl-8 col-xxl-8 my-2">
                                                <label for="auto-empsUpload-weekly-time">Run time:</label>
                                                <input class="form-control timepicker" id="auto-empsUpload-weekly-time" name="auto-empsUpload-weekly-time" value="<?php echo $automation["employees"]["weekly"]["runtime"]; ?>">
                                            </div>

                                            <div class="col-12 col-sm-12 col-md-12 col-lg-8 col-xl-8 col-xxl-8 my-2">
                                                <div class="btn-group w-100" role="group">
                                                    <button class="btn <?php if (in_array("sun", $automation["employees"]["weekly"]["days"])) { echo "btn-primary"; } else { echo "btn-outline-secondary"; } ?> btn-md w-100" id="auto-empsUpload-weekly-sun" value="<?php if (in_array("sun", $automation["employees"]["weekly"]["days"])) { echo 1; } else { echo 0; } ?>" onclick="toggleEmployeesUploadDay('sun', 'weekly', 0);">Sunday</button>
                                                    <button class="btn <?php if (in_array("mon", $automation["employees"]["weekly"]["days"])) { echo "btn-primary"; } else { echo "btn-outline-secondary"; } ?> btn-md w-100" id="auto-empsUpload-weekly-mon" value="<?php if (in_array("mon", $automation["employees"]["weekly"]["days"])) { echo 1; } else { echo 0; } ?>" onclick="toggleEmployeesUploadDay('mon', 'weekly', 0);">Monday</button>
                                                    <button class="btn <?php if (in_array("tue", $automation["employees"]["weekly"]["days"])) { echo "btn-primary"; } else { echo "btn-outline-secondary"; } ?> btn-md w-100" id="auto-empsUpload-weekly-tue" value="<?php if (in_array("tue", $automation["employees"]["weekly"]["days"])) { echo 1; } else { echo 0; } ?>" onclick="toggleEmployeesUploadDay('tue', 'weekly', 0);">Tuesday</button>
                                                    <button class="btn <?php if (in_array("wed", $automation["employees"]["weekly"]["days"])) { echo "btn-primary"; } else { echo "btn-outline-secondary"; } ?> btn-md w-100" id="auto-empsUpload-weekly-wed" value="<?php if (in_array("wed", $automation["employees"]["weekly"]["days"])) { echo 1; } else { echo 0; } ?>" onclick="toggleEmployeesUploadDay('wed', 'weekly', 0);">Wednesday</button>
                                                    <button class="btn <?php if (in_array("thu", $automation["employees"]["weekly"]["days"])) { echo "btn-primary"; } else { echo "btn-outline-secondary"; } ?> btn-md w-100" id="auto-empsUpload-weekly-thu" value="<?php if (in_array("thu", $automation["employees"]["weekly"]["days"])) { echo 1; } else { echo 0; } ?>" onclick="toggleEmployeesUploadDay('thu', 'weekly', 0);">Thursday</button>
                                                    <button class="btn <?php if (in_array("fri", $automation["employees"]["weekly"]["days"])) { echo "btn-primary"; } else { echo "btn-outline-secondary"; } ?> btn-md w-100" id="auto-empsUpload-weekly-fri" value="<?php if (in_array("fri", $automation["employees"]["weekly"]["days"])) { echo 1; } else { echo 0; } ?>" onclick="toggleEmployeesUploadDay('fri', 'weekly', 0);">Friday</button>
                                                    <button class="btn <?php if (in_array("sat", $automation["employees"]["weekly"]["days"])) { echo "btn-primary"; } else { echo "btn-outline-secondary"; } ?> btn-md w-100" id="auto-empsUpload-weekly-sat" value="<?php if (in_array("sat", $automation["employees"]["weekly"]["days"])) { echo 1; } else { echo 0; } ?>" onclick="toggleEmployeesUploadDay('sat', 'weekly', 0);">Saturday</button>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row d-flex justify-content-center <?php if ($automation["employees"]["cycle"] != "custom" || $automation["employees"]["enabled"] == 0) { echo "d-none"; } ?>" id="auto-empsUpload-custom">
                                            <div class="col-12 col-sm-12 col-md-12 col-lg-8 col-xl-8 col-xxl-8 my-2">
                                                <label for="auto-empsUpload-custom-time">Run time:</label>
                                                <input class="form-control timepicker" id="auto-empsUpload-custom-time" name="auto-empsUpload-custom-time" value="<?php echo $automation["employees"]["custom"]["runtime"]; ?>">
                                            </div>

                                            <div class="col-12 col-sm-12 col-md-12 col-lg-8 col-xl-8 col-xxl-8 my-2">
                                                <div class="btn-group w-100" role="group">
                                                    <button class="btn <?php if (in_array("sun", $automation["employees"]["custom"]["days"])) { echo "btn-primary"; } else { echo "btn-outline-secondary"; } ?> btn-md w-100" id="auto-empsUpload-custom-sun" value="<?php if (in_array("sun", $automation["employees"]["custom"]["days"])) { echo 1; } else { echo 0; } ?>" onclick="toggleEmployeesUploadDay('sun', 'custom', 1);">Sunday</button>
                                                    <button class="btn <?php if (in_array("mon", $automation["employees"]["custom"]["days"])) { echo "btn-primary"; } else { echo "btn-outline-secondary"; } ?> btn-md w-100" id="auto-empsUpload-custom-mon" value="<?php if (in_array("mon", $automation["employees"]["custom"]["days"])) { echo 1; } else { echo 0; } ?>" onclick="toggleEmployeesUploadDay('mon', 'custom', 1);">Monday</button>
                                                    <button class="btn <?php if (in_array("tue", $automation["employees"]["custom"]["days"])) { echo "btn-primary"; } else { echo "btn-outline-secondary"; } ?> btn-md w-100" id="auto-empsUpload-custom-tue" value="<?php if (in_array("tue", $automation["employees"]["custom"]["days"])) { echo 1; } else { echo 0; } ?>" onclick="toggleEmployeesUploadDay('tue', 'custom', 1);">Tuesday</button>
                                                    <button class="btn <?php if (in_array("wed", $automation["employees"]["custom"]["days"])) { echo "btn-primary"; } else { echo "btn-outline-secondary"; } ?> btn-md w-100" id="auto-empsUpload-custom-wed" value="<?php if (in_array("wed", $automation["employees"]["custom"]["days"])) { echo 1; } else { echo 0; } ?>" onclick="toggleEmployeesUploadDay('wed', 'custom', 1);">Wednesday</button>
                                                    <button class="btn <?php if (in_array("thu", $automation["employees"]["custom"]["days"])) { echo "btn-primary"; } else { echo "btn-outline-secondary"; } ?> btn-md w-100" id="auto-empsUpload-custom-thu" value="<?php if (in_array("thu", $automation["employees"]["custom"]["days"])) { echo 1; } else { echo 0; } ?>" onclick="toggleEmployeesUploadDay('thu', 'custom', 1);">Thursday</button>
                                                    <button class="btn <?php if (in_array("fri", $automation["employees"]["custom"]["days"])) { echo "btn-primary"; } else { echo "btn-outline-secondary"; } ?> btn-md w-100" id="auto-empsUpload-custom-fri" value="<?php if (in_array("fri", $automation["employees"]["custom"]["days"])) { echo 1; } else { echo 0; } ?>" onclick="toggleEmployeesUploadDay('fri', 'custom', 1);">Friday</button>
                                                    <button class="btn <?php if (in_array("sat", $automation["employees"]["custom"]["days"])) { echo "btn-primary"; } else { echo "btn-outline-secondary"; } ?> btn-md w-100" id="auto-empsUpload-custom-sat" value="<?php if (in_array("sat", $automation["employees"]["custom"]["days"])) { echo 1; } else { echo 0; } ?>" onclick="toggleEmployeesUploadDay('sat', 'custom', 1);">Saturday</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Save Button -->
                    <div class="row d-flex justify-content-center position-sticky bottom-0 mx-auto mt-3 mb-1">
                        <div class="col-12 col-sm-10 col-md-8 col-lg-6 col-xl-4 col-xxl-4">
                            <button class="btn btn-primary btn-lg px-5 py-3 w-100" type="button" onclick="saveAutomation();">
                                <i class="fas fa-save"></i> Save Automation Settings
                            </button>
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

    include_once("footer.php"); 
?>