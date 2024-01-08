<?php 
    include_once("header.php");

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1) 
    { 
        if (isset($PERMISSIONS["CREATE_SERVICE_CONTRACTS"]) || isset($PERMISSIONS["CREATE_QUARTERLY_INVOICES"]))
        {
            // get additional settings
            include("getSettings.php");

            // connect to the database
            $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

            ?>  
                <script>
                    /** function to toggle the uplaod button */
                    function toggleUpload(element_id)
                    {
                        // store the element
                        var element = document.getElementById(element_id);
                        var input_element = document.getElementById(element_id+"_input");
                        
                        // get current status of the element
                        var status = element.value;
                        
                        if (status == 1) // disable the upload button
                        {
                            element.classList.remove("btn-success");
                            element.classList.add("btn-secondary");
                            element.innerHTML = "No, do not upload to Google Drive";
                            element.value = 0;
                            input_element.value = 0;
                        }
                        else // enable the upload button
                        {
                            element.classList.remove("btn-secondary");
                            element.classList.add("btn-success");
                            element.innerHTML = "Yes, upload to Google Drive";
                            element.value = 1;
                            input_element.value = 1;
                        }
                    }
                </script>

                <!-- Header -->
                <div class="row m-0 p-0">
                    <h1 class="col-12 col-sm-8 col-md-6 col-lg-4 col-xl-4 col-xxl-4 page-header my-3 py-3 ps-3 pe-5">
                        <a class="back-button" href="contracts.php" title="Return to Contracts."><i class="fa-solid fa-angles-left"></i></a>
                        <div class="d-inline float-end">Create Contracts</div>
                    </h1>
                </div>

                <!-- Contract Type Selection -->
                <div class="row m-0">
                    <div class="btn-group" role="group" aria-label="Button group to select which type of contract to create">
                        <?php if (isset($PERMISSIONS["CREATE_SERVICE_CONTRACTS"])) { ?>
                        <button class="btn btn-secondary btn-lg w-100" id="contract-annual-button" onclick="toggleContract('annual');">Annual Service Contract</button>
                        <?php } ?>

                        <?php if (isset($PERMISSIONS["CREATE_QUARTERLY_INVOICES"])) { ?>
                        <button class="btn btn-secondary btn-lg w-100" id="contract-quarterly-button" onclick="toggleContract('quarterly');">Quarterly Service Invoice</button>
                        <?php } ?>
                    </div>
                </div>

                <?php if (isset($PERMISSIONS["CREATE_SERVICE_CONTRACTS"])) { ?>
                <!-- Annual Service Contract -->
                <div class="row justify-content-center d-none m-0" id="contract-annual">
                    <div class="col-12 col-sm-12 col-md-12 col-lg-8 col-xl-6 col-xxl-6 p-0">
                        <fieldset class="border p-2">
                            <legend class="float-none w-auto px-4 py-0 m-0 text-center"><h1 class="report-title m-0">Create Annual Service Contracts</h1></legend>

                            <div class="alert alert-danger">
                                <p class="m-0">
                                    Please use the new <a class="btn-link" href="contract_creator.php">Contract Creator</a> to create annual service contracts.
                                </p>
                            </div>
                        </fieldset>
                    </div>
                </div>
                <?php } ?>

                <?php if (isset($PERMISSIONS["CREATE_QUARTERLY_INVOICES"])) { ?>
                <!-- Quarterly Service Invoice -->
                <div class="row justify-content-center d-none m-0" id="contract-quarterly">
                    <div class="col-12 col-sm-12 col-md-12 col-lg-8 col-xl-6 col-xxl-6 p-0">
                        <fieldset class="border p-2">
                            <legend class="float-none w-auto px-4 py-0 m-0 text-center"><h1 class="report-title m-0">Create Quarterly Service Invoice</h1></legend>

                            <p class="text-center">
                                Enter in the required fields and we'll generate contracts for the selected customers for the current active period. 
                                If they have a Google Drive folder ID assigned to them, we will automatically upload the contract to their Google Drive folder.
                            </p>

                            <form action="createQuarterlyInvoices.php" method="POST">
                                <div class="form-group mb-3">
                                    <label for="filename" class="form-label"><span class="required-field">*</span> Enter the name for the contract files: </label>
                                    <input id="filename" name="filename" class="form-control" aria-describedby="filenameDesc" required>
                                    <small id="filenameDesc" class="form-text text-muted">
                                        To indicate customer name in file name, use the tag {CUSTOMER}.<br>
                                        To indicate period name in file name, use the tag {PERIOD}.<br>
                                        To indicate quarter in file name, use the tag {QUARTER}. We'll display this as Q#.
                                    </small>
                                </div>

                                <div class="form-group mb-3">
                                    <label for="QI-period" class="form-label m-0"><span class="required-field">*</span> Select the period to create the quarterly invoices for: </label>
                                    <select id="QI-period" name="QI-period" class="form-select" required>
                                        <option></option>
                                        <?php
                                            // create a dropdown list of all periods
                                            $getPeriods = mysqli_query($conn, "SELECT id, name FROM periods ORDER BY active DESC");
                                            while ($period = mysqli_fetch_array($getPeriods)) { echo "<option value='".$period["id"]."'>".$period["name"]."</option>"; }
                                        ?>
                                    </select>
                                </div>

                                <div class="form-group mb-3">
                                    <label for="quarter" class="form-label"><span class="required-field">*</span> Select the quarter to create the quarterly invoices for: </label>
                                    <select id="quarter" name="quarter" class="form-select" required>
                                        <option></option>
                                        <?php
                                            // create a dropdown list of all quarters
                                            $getQuarters = mysqli_query($conn, "SELECT quarter, label, locked FROM quarters ORDER BY quarter ASC");
                                            while ($quarter = mysqli_fetch_array($getQuarters)) 
                                            { 
                                                if ($quarter["locked"] == 1) { echo "<option value='".$quarter["quarter"]."' disabled>Q".$quarter["quarter"].": ".$quarter["label"]." (locked)</option>"; }
                                                else { echo "<option value='".$quarter["quarter"]."'>Q".$quarter["quarter"].": ".$quarter["label"]."</option>"; }
                                            }
                                        ?>
                                    </select>
                                </div>

                                <div class="form-group mb-3">
                                    <label for="customers" class="form-label"><span class="required-field">*</span> Select the customer(s) to create contracts for: </label>
                                    <select id="customers" name="customers[]" class="form-select" style="height: 250px" multiple required>
                                        <?php
                                            // create a dropdown list of all customers who have been enabled to build service contracts for
                                            $getCustomers = mysqli_query($conn, "SELECT id, name FROM customers WHERE build_quarterly_invoice=1 AND active=1 ORDER BY name ASC");
                                            while ($customers = mysqli_fetch_array($getCustomers)) { echo "<option value='".$customers["id"]."'>".$customers["name"]."</option>"; }
                                        ?>
                                    </select>
                                </div>

                                <div class="form-group mb-3">
                                    <input type="hidden" id="QI_upload_input" name="QI_upload_input" value="0">
                                    <p class="mb-1">
                                        <label for="QI_upload" class="form-label m-0">Would you like to upload the invoices to the customers' assigned Google Drive folders?</label> 
                                        If the customer does not have an assigned Google Drive folder, we will still store their invoice locally.
                                    </p>
                                    <?php if (!isset($GLOBAL_SETTINGS["quarterly_invoices_gid"]) || trim($GLOBAL_SETTINGS["quarterly_invoices_gid"]) == "") { ?>
                                        <div class="alert alert-danger">
                                            <p class="m-0">
                                                WARNING: you have not set a Google Drive parent folder directory to scan through. We will scan your entire Google Drive directory looking for all folders.
                                                To improve performance, please enter a parent folder to scan through from the admin's Manage > Admin page.
                                            </p>
                                        </div>
                                    <?php } ?>
                                    <button type="button" class="btn btn-secondary w-100" id="QI_upload" name="QI_upload" value="0" onclick="toggleUpload('QI_upload')">No, do not upload to Google Drive</button>
                                </div>

                                <div class="text-center w-100 mb-3">
                                    <button class="btn btn-primary btn-lg">Create Contracts</button>
                                </div>
                            </form>
                        </fieldset>
                    </div>
                </div>
                <?php } ?>

                <script>
                    // view the table stored in session
                    var view_table = sessionStorage["BAP_ContractsCreate_ViewTable"];
                    if (view_table != null && view_table != "")
                    {
                        // select and display the table previously viewed within the session
                        document.getElementById("contract-"+view_table).classList.remove("d-none");
                        document.getElementById("contract-"+view_table+"-button").classList.remove("btn-secondary");
                        document.getElementById("contract-"+view_table+"-button").classList.add("btn-primary");
                    }

                    /** function to display the form for the contract selected */
                    function toggleContract(type)
                    {
                        // deselect both contracts
                        <?php if (isset($PERMISSIONS["CREATE_SERVICE_CONTRACTS"])) { ?>
                        document.getElementById("contract-annual").classList.add("d-none");
                        document.getElementById("contract-annual-button").classList.remove("btn-primary");
                        document.getElementById("contract-annual-button").classList.add("btn-secondary");
                        <?php } ?>

                        <?php if (isset($PERMISSIONS["CREATE_QUARTERLY_INVOICES"])) { ?>
                        document.getElementById("contract-quarterly").classList.add("d-none");
                        document.getElementById("contract-quarterly-button").classList.remove("btn-primary");
                        document.getElementById("contract-quarterly-button").classList.add("btn-secondary");
                        <?php } ?>

                        // select the contract toggled
                        document.getElementById("contract-"+type+"-button").classList.add("btn-primary");
                        document.getElementById("contract-"+type).classList.remove("d-none");

                        // store the table we are viewing in session storage
                        sessionStorage["BAP_ContractsCreate_ViewTable"] = type;
                    }
                </script>
            <?php 

            // disconnect from the database
            mysqli_close($conn);
        }
        else { denyAccess(); }
    }
    else { goToLogin(); }

    include_once("footer.php"); 
?>