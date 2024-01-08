<?php 
    include_once("header.php");
    include("getSettings.php");

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1) 
    { 
        if (isset($_SESSION["role"]) && $_SESSION["role"] == 1)
        {
            ?> 
                <script>
                    /** function to toggle the button for the element provided */
                    function toggle(element_id)
                    {
                        let element = document.getElementById(element_id);
                        let icon = document.getElementById(element_id + "_Toggle");
                        let text = document.getElementById(element_id + "_Text");

                        // get the current value
                        let current = element.value;

                        if (current == 0) // setting is currently set to off; turn setting on
                        {                        
                            // send the AJAX request to update the setting
                            var xmlhttp = new XMLHttpRequest();
                            xmlhttp.open("POST", "ajax/misc/saveAdminSettings.php", true);
                            xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                            xmlhttp.onreadystatechange = function() 
                            {
                                if (this.readyState == 4 && this.status == 200)
                                {
                                    element.classList.remove("btn-secondary");
                                    element.classList.remove("btn-profile-toggle-off");
                                    element.classList.add("btn-primary");
                                    element.classList.add("btn-profile-toggle-on");
                                    element.value = 1;

                                    icon.innerHTML = "<i class=\"fa-solid fa-toggle-on\"></i>";
                                    text.innerHTML = "ON";
                                }
                            };
                            xmlhttp.send("setting="+element_id+"&value=1");
                        }
                        else // setting is currently set to on; turn setting off 
                        {
                            // send the AJAX request to update the setting
                            var xmlhttp = new XMLHttpRequest();
                            xmlhttp.open("POST", "ajax/misc/saveAdminSettings.php", true);
                            xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                            xmlhttp.onreadystatechange = function() 
                            {
                                if (this.readyState == 4 && this.status == 200)
                                {
                                    element.classList.remove("btn-primary");
                                    element.classList.remove("btn-profile-toggle-on");
                                    element.classList.add("btn-secondary");
                                    element.classList.add("btn-profile-toggle-off");
                                    element.value = 0;

                                    icon.innerHTML = "<i class=\"fa-solid fa-toggle-off\"></i>";
                                    text.innerHTML = "OFF";
                                }
                            };
                            xmlhttp.send("setting="+element_id+"&value=0");
                        }
                    }

                    /** function to save a setting */
                    function saveSetting(setting, value)
                    {
                        // send the AJAX request to update the setting
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/misc/saveAdminSettings.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {

                            }
                        };
                        xmlhttp.send("setting="+setting+"&value="+value);
                    }
                </script>

                <!-- Header -->
                <div class="row m-0 p-0">
                    <h1 class="col-12 col-sm-8 col-md-6 col-lg-4 col-xl-4 col-xxl-4 page-header my-3 py-3 ps-3 pe-5">
                        <a class="back-button" href="manage.php" title="Return to Manage."><i class="fa-solid fa-angles-left"></i></a>
                        <div class="d-inline float-end">Admin</div>
                    </h1>
                </div>

                <!-- Body -->
                <div class="row d-flex justify-content-center align-items-around m-0">
                    <!-- Compensation Settings -->
                    <div class="col-12 col-sm-12 col-md-8 col-lg-8 col-xl-6 col-xxl-4">
                        <fieldset class="border p-2">
                            <legend class="float-none w-auto px-4 py-0 m-0 text-center"><h1 class="report-title m-0">Compensation Settings</h1></legend>

                            <p class="body-desc text-center">
                                Set the number of hours in a work day and how many days are required for full compensation. 
                                Employees who work the at least the number of FTE days will receive full benefits.
                                We'll use the hours in a work day to calculate employees hourly rate.
                            </p>

                            <div class="report-description">
                                <div class="form-row d-flex justify-content-center align-items-center my-3">
                                    <!-- First Name -->
                                    <div class="form-group col-4">
                                        <label for="FTE"><span class="required-field">*</span> FTE Days:</label>
                                        <input type="number" class="form-control w-100" id="FTE" name="FTE" value="<?php if (isset($GLOBAL_SETTINGS["hours_per_workday"])) { echo $GLOBAL_SETTINGS["FTE_days"]; } ?>" onblur="saveSetting('FTE_days', this.value);" required>
                                    </div>

                                    <!-- Divider -->
                                    <div class="form-group col-1 p-0"></div>

                                    <!-- Last Name -->
                                    <div class="form-group col-4">
                                        <label for="hours"><span class="required-field">*</span> Hours Per Workday:</label>
                                        <input type="number" class="form-control w-100" id="hours" name="hours" value="<?php if (isset($GLOBAL_SETTINGS["hours_per_workday"])) { echo $GLOBAL_SETTINGS["hours_per_workday"]; } ?>" onblur="saveSetting('hours_per_workday', this.value);" required>
                                    </div>
                                </div>
                            </div>
                        </fieldset>
                    </div>

                    <!-- Expenses Settings -->
                    <div class="col-12 col-sm-12 col-md-8 col-lg-8 col-xl-6 col-xxl-4">
                        <fieldset class="border p-2">
                            <legend class="float-none w-auto px-4 py-0 m-0 text-center"><h1 class="report-title m-0">Expense Settings</h1></legend>

                            <p class="body-desc text-center">
                                Set global settings that are tied to expenses.
                            </p>

                            <div class="report-description">
                                <div class="form-row d-flex justify-content-center align-items-center my-3">
                                    <!-- Overhead Fund Code -->
                                    <div class="form-group col-12">
                                        <label for="overhead-fund"><span class="required-field">*</span> Overhead Costs Fund Code:</label>
                                        <input type="number" class="form-control w-100" id="overhead-fund" name="overhead-fund" value="<?php if (isset($GLOBAL_SETTINGS["overhead_costs_fund"])) { echo $GLOBAL_SETTINGS["overhead_costs_fund"]; } ?>" onblur="saveSetting('overhead_costs_fund', this.value);" min="10" max="99" required>
                                    </div>
                                </div>

                                <div class="form-row d-flex justify-content-center align-items-center my-3">
                                    <!-- Grant Projects Indirect Rate -->
                                    <div class="form-group col-12">
                                        <label for="grant-indirect"><span class="required-field">*</span> Grant Projects Indirect Rate:</label>
                                        <input type="number" class="form-control w-100" id="grant-indirect" name="grant-indirect" value="<?php if (isset($GLOBAL_SETTINGS["grant_indirect_rate"])) { echo $GLOBAL_SETTINGS["grant_indirect_rate"]; } ?>" onblur="saveSetting('grant_indirect_rate', this.value);" required>
                                    </div>
                                </div>
                            </div>
                        </fieldset>
                    </div>

                    <!-- Contract GIDs -->
                    <div class="col-12 col-sm-12 col-md-8 col-lg-8 col-xl-6 col-xxl-4">
                        <fieldset class="border p-2">
                            <legend class="float-none w-auto px-4 py-0 m-0 text-center"><h1 class="report-title m-0">Contracting GIDs</h1></legend>

                            <p class="body-desc text-center">
                                Set the parent folders that we should scan the contents of to verify there are existing folders for each customer we are creating a contract for.
                                We will scan the folder provided, as well as any children of those folders for additional folders. This is to prevent the need of scanning your entire
                                Google Drive directory.
                            </p>

                            <div class="report-description">
                                <div class="form-row d-flex justify-content-center align-items-center my-3">
                                    <div class="form-group col-12">
                                        <label for="gid-service_contracts">Annual Service Contracts GID:</label>
                                        <input type="text" class="form-control w-100" id="gid-service_contract" name="gid-service_contract" value="<?php if (isset($GLOBAL_SETTINGS["service_contracts_gid"])) { echo $GLOBAL_SETTINGS["service_contracts_gid"]; } ?>" onblur="saveSetting('service_contracts_gid', this.value);">
                                    </div>
                                </div>

                                <div class="form-row d-flex justify-content-center align-items-center my-3">
                                    <div class="form-group col-12">
                                        <label for="gid-quarterly_invoices">Quarterly Invoices GID:</label>
                                        <input type="text" class="form-control w-100" id="gid-quarterly_invoices" name="gid-quarterly_invoices" value="<?php if (isset($GLOBAL_SETTINGS["quarterly_invoices_gid"])) { echo $GLOBAL_SETTINGS["quarterly_invoices_gid"]; } ?>" onblur="saveSetting('quarterly_invoices_gid', this.value);">
                                    </div>
                                </div>
                            </div>
                        </fieldset>
                    </div>

                    <!-- Caseloads Units Threshold -->
                    <div class="col-12 col-sm-12 col-md-8 col-lg-8 col-xl-6 col-xxl-4">
                        <fieldset class="border p-2">
                            <legend class="float-none w-auto px-4 py-0 m-0 text-center"><h1 class="report-title m-0">Caseload Settings</h1></legend>

                            <p class="body-desc text-center">
                                Global settings in regards to caseloads.
                            </p>

                            <div class="report-description">
                                <div class="form-row d-flex justify-content-center align-items-center my-3">
                                    <!-- Caseloads Units Warning -->
                                    <div class="form-group col-12">
                                        <label for="caseload-units_warning"><span class="required-field">*</span> Units Of Service (UOS) Warning Amonut:</label>
                                        <input type="number" class="form-control w-100" id="caseload-units_warning" name="caseload-units_warning" value="<?php if (isset($GLOBAL_SETTINGS["caseloads_units_warning"])) { echo $GLOBAL_SETTINGS["caseloads_units_warning"]; } ?>" onblur="saveSetting('caseloads_units_warning', this.value);" min="0" required>
                                    </div>
                                </div>
                            </div>
                        </fieldset>
                    </div>

                    <!-- Inactivity Timeout Mode -->
                    <div class="col-12 col-sm-12 col-md-8 col-lg-8 col-xl-6 col-xxl-4">
                        <fieldset class="border p-2">
                            <legend class="float-none w-auto px-4 py-0 m-0 text-center"><h1 class="report-title m-0">Inactivity Timeout</h1></legend>

                            <p class="body-desc text-center">
                                The amount of time before a user gets logged out due to inactivity. As a site administrator, you control this timeout for all users!
                            </p>

                            <div class="report-description">
                                <select class="form-select" id="inactivity_timeout" name="inactivity_timeout" onblur="saveSetting('inactivity_timeout', this.value);">
                                    <option value="15" <?php if (isset($GLOBAL_SETTINGS["inactivity_timeout"]) && $GLOBAL_SETTINGS["inactivity_timeout"] == 15) { echo "selected"; } ?>>15 minutes</option>
                                    <option value="30" <?php if (isset($GLOBAL_SETTINGS["inactivity_timeout"]) && $GLOBAL_SETTINGS["inactivity_timeout"] == 30) { echo "selected"; } ?>>30 minutes</option>
                                    <option value="60" <?php if (isset($GLOBAL_SETTINGS["inactivity_timeout"]) && $GLOBAL_SETTINGS["inactivity_timeout"] == 60) { echo "selected"; } ?>>60 minutes</option>
                                    <option value="-1" <?php if (isset($GLOBAL_SETTINGS["inactivity_timeout"]) && $GLOBAL_SETTINGS["inactivity_timeout"] == -1) { echo "selected"; } ?>>Never</option>
                                </select>
                            </div>
                        </fieldset>
                    </div>

                    <!-- Maintenance Mode -->
                    <div class="col-12 col-sm-12 col-md-8 col-lg-8 col-xl-6 col-xxl-4">
                        <fieldset class="border p-2">
                            <legend class="float-none w-auto px-4 py-0 m-0 text-center"><h1 class="report-title m-0">Maintenance Mode</h1></legend>

                            <p class="body-desc text-center">
                                When maintenance mode is enabled, only admin and maintenance users will be able to login and complete tasks. 
                                All other users will be logged out, and won't be able to successfully log back in until maintenance mode is turned off.
                            </p>

                            <div class="report-description">
                                <?php if (isset($GLOBAL_SETTINGS) && $GLOBAL_SETTINGS["maintenance_mode"] == 1) { ?>
                                    <button class="btn btn-primary w-100 btn-profile-toggle btn-profile-toggle-on" id="MM_Button" value="1" onclick="toggle('MM_Button');">
                                        <div class="row">
                                            <div class="col-1 text-left" id="MM_Button_Toggle"><i class="fa-solid fa-toggle-on"></i></div>
                                            <div class="col-10 text-center" id="MM_Button_Text">ON</div>
                                            <div class="col-1"></div>
                                        </div>
                                    </button>
                                <?php } else { ?>
                                    <button class="btn btn-secondary w-100 btn-profile-toggle btn-profile-toggle-off" id="MM_Button" value="0" onclick="toggle('MM_Button');">
                                        <div class="row">
                                            <div class="col-1 text-left" id="MM_Button_Toggle"><i class="fa-solid fa-toggle-off"></i></div>
                                            <div class="col-10 text-center" id="MM_Button_Text">OFF</div>
                                            <div class="col-1"></div>
                                        </div>
                                    </button>
                                <?php } ?>
                            </div>
                        </fieldset>
                    </div>
                </div>
            <?php
        }
        else { denyAccess(); }
    }
    else { goToLogin(); }

    include_once("footer.php"); 
?>