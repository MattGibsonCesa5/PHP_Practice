<?php 
    include_once("header.php");

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1) 
    { 
        ?>
            <script>
                /** function to update the profile */
                function saveProfile()
                {
                    // get the settings to save
                    let dark_mode = document.getElementById("DM_Button").value;
                    let page_length = document.getElementById("table-page_length").value;

                    // create the string of data to send
                    let sendString = "";
                    sendString += "dark_mode="+dark_mode+"&page_length="+page_length;

                    var xmlhttp = new XMLHttpRequest();
                    xmlhttp.open("POST", "ajax/misc/saveProfile.php", true);
                    xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                    xmlhttp.onreadystatechange = function() 
                    {
                        if (this.readyState == 4 && this.status == 200)
                        {
                            // create the status modal
                            let status_title = "Save Profile Status";
                            let status_body = this.responseText;
                            createStatusModal("refresh", status_title, status_body);
                        }
                    };
                    xmlhttp.send(sendString);
                }

                /** function to toggle the button for the element provided */
                function toggle(element_id)
                {
                    let element = document.getElementById(element_id);
                    let icon = document.getElementById(element_id + "_Toggle");
                    let text = document.getElementById(element_id + "_Text");

                    // get the current value
                    let current = element.value;

                    if (current == 0) // dark mode currently set to off; switch to on
                    {
                        element.classList.remove("btn-secondary");
                        element.classList.remove("btn-profile-toggle-off");
                        element.classList.add("btn-primary");
                        element.classList.add("btn-profile-toggle-on");
                        element.value = 1;

                        icon.innerHTML = "<i class=\"fa-solid fa-toggle-on\"></i>";
                        text.innerHTML = "ON";
                    }
                    else // dark mode current set to on; switch to off
                    {
                        element.classList.remove("btn-primary");
                        element.classList.remove("btn-profile-toggle-on");
                        element.classList.add("btn-secondary");
                        element.classList.add("btn-profile-toggle-off");
                        element.value = 0;

                        icon.innerHTML = "<i class=\"fa-solid fa-toggle-off\"></i>";
                        text.innerHTML = "OFF";
                    }
                }
            </script>

            <!-- Header -->
            <div class="row m-0 p-0">
                <h1 class="col-12 col-sm-8 col-md-6 col-lg-4 col-xl-4 col-xxl-4 page-header my-3 py-3 ps-3 pe-5">
                    <a class="back-button" href="dashboard.php" title="Return to the dashboard."><i class="fa-solid fa-angles-left"></i></a>
                    <div class="d-inline float-end">My Profile</div>
                </h1>
            </div>


            <div class="report">
                <div class="row report-header justify-content-center mb-3 mx-0"> 
                    <div class="col-12 col-sm-12 col-md-6 col-lg-6 col-xl-4 col-xxl-3">
                        <fieldset class="border p-2">
                            <legend class="float-none w-auto px-4 py-0 m-0 profile-setting">Dark Mode</legend>

                            <?php if (isset($USER_SETTINGS) && $USER_SETTINGS["dark_mode"] == 1) { ?>
                                <button class="btn btn-primary w-100 btn-profile-toggle btn-profile-toggle-on" id="DM_Button" value="1" onclick="toggle('DM_Button');">
                                    <div class="row">
                                        <div class="col-1 text-left" id="DM_Button_Toggle"><i class="fa-solid fa-toggle-on"></i></div>
                                        <div class="col-10 text-center" id="DM_Button_Text">ON</div>
                                        <div class="col-1"></div>
                                    </div>
                                </button>
                            <?php } else { ?>
                                <button class="btn btn-secondary w-100 btn-profile-toggle btn-profile-toggle-off" id="DM_Button" value="0" onclick="toggle('DM_Button');">
                                    <div class="row">
                                        <div class="col-1 text-left" id="DM_Button_Toggle"><i class="fa-solid fa-toggle-off"></i></div>
                                        <div class="col-10 text-center" id="DM_Button_Text">OFF</div>
                                        <div class="col-1"></div>
                                    </div>
                                </button>
                            <?php } ?>
                        </fieldset>
                    </div>

                    <div class="col-12 col-sm-12 col-md-6 col-lg-6 col-xl-4 col-xxl-3">
                        <fieldset class="border p-2">
                            <legend class="float-none w-auto px-4 py-0 m-0 profile-setting">Table Page Length</legend>

                            <p>
                                This setting will impact how many entries are displayed in each table by default.
                                For tables that have many entries, setting this value to a higher value, or "All", could lead to a performance decrease, or in some cases, crash your browser.
                                This setting will be <b>overridden</b> by tables that save their current state.
                            </p>

                            <label for="table-page_length">Entries displayed per page:</label>
                            <select class="form-select" id="table-page_length">
                                <option value="10" <?php if (isset($USER_SETTINGS["page_length"]) && $USER_SETTINGS["page_length"] == 10) { echo "selected"; } ?>>10</option>
                                <option value="25" <?php if (isset($USER_SETTINGS["page_length"]) && $USER_SETTINGS["page_length"] == 25) { echo "selected"; } ?>>25</option>
                                <option value="50" <?php if (isset($USER_SETTINGS["page_length"]) && $USER_SETTINGS["page_length"] == 50) { echo "selected"; } ?>>50</option>
                                <option value="100" <?php if (isset($USER_SETTINGS["page_length"]) && $USER_SETTINGS["page_length"] == 100) { echo "selected"; } ?>>100</option>
                                <option value="250" <?php if (isset($USER_SETTINGS["page_length"]) && $USER_SETTINGS["page_length"] == 250) { echo "selected"; } ?>>250</option>
                                <option value="500" <?php if (isset($USER_SETTINGS["page_length"]) && $USER_SETTINGS["page_length"] == 500) { echo "selected"; } ?>>500</option>
                                <option value="1000" <?php if (isset($USER_SETTINGS["page_length"]) && $USER_SETTINGS["page_length"] == 1000) { echo "selected"; } ?>>1000</option>
                                <option value="-1" <?php if (isset($USER_SETTINGS["page_length"]) && $USER_SETTINGS["page_length"] == -1) { echo "selected"; } ?>>All</option>
                            </select>
                        </fieldset>
                    </div>
                </div>

                <div class="row justify-content-center">
                    <div class="col-12 col-sm-10 col-md-8 col-lg-6 col-xl-4 col-xxl-3">
                        <button class="btn btn-primary btn-lg w-100" id="btn-saveProfile" onclick="saveProfile();"><i class="fa-solid fa-floppy-disk"></i> Save Settings</button>
                    </div>
                </div>
            </div>
        <?php
    }
    else { goToLogin(); }
    
    include_once("footer.php"); 
?>