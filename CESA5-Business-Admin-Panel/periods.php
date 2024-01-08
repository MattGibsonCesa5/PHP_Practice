<?php 
    include_once("header.php");

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1) 
    { 
        if (isset($_SESSION["role"]) && $_SESSION["role"] == 1)
        {
            // connect to the database
            $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

            ?>
                <script>
                    /** function to create a new period */
                    function addPeriod()
                    {
                        // get the details from add period modal
                        let name = document.getElementById("add-name").value;
                        let desc = document.getElementById("add-desc").value;
                        let start = document.getElementById("add-start").value;
                        let end = document.getElementById("add-end").value;
                        let caseload_start = document.getElementById("add-caseload_term-start").value;
                        let caseload_end = document.getElementById("add-caseload_term-end").value;
                        let status = document.getElementById("add-status").value;
                        let comparison = document.getElementById("add-comp").value;
                        let editable = document.getElementById("add-editable").value;
                        let next = document.getElementById("add-next").value;

                        // get quarters data
                        let q1_label = document.getElementById("add-q1-label").value;
                        let q2_label = document.getElementById("add-q2-label").value;
                        let q3_label = document.getElementById("add-q3-label").value;
                        let q4_label = document.getElementById("add-q4-label").value;
                        let q1_status = document.getElementById("add-q1-status").value;
                        let q2_status = document.getElementById("add-q2-status").value;
                        let q3_status = document.getElementById("add-q3-status").value;
                        let q4_status = document.getElementById("add-q4-status").value;

                        // create the string of data to send
                        let sendString = "name="+name+"&desc="+desc+"&start="+start+"&end="+end+"&status="+status+"&comparison="+comparison+"&editable="+editable+"&next="+next+"&caseload_term_start="+caseload_start+"&caseload_term_end="+caseload_end;
                        sendString += "&q1_label="+q1_label+"&q2_label="+q2_label+"&q3_label="+q3_label+"&q4_label="+q4_label+"&q1_status="+q1_status+"&q2_status="+q2_status+"&q3_status="+q3_status+"&q4_status="+q4_status;

                        // send the data to create the new period
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/periods/addPeriod.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // create the status modal
                                let status_title = "Add Period Status";
                                let status_body = this.responseText;
                                createStatusModal("refresh", status_title, status_body);

                                // hide the current modal
                                $("#addPeriodModal").modal("hide");
                            }
                        };
                        xmlhttp.send(sendString);
                    }

                    /** function to update the status element */
                    function updateStatus(id)
                    {
                        // get current status of the element
                        let element = document.getElementById(id);
                        let status = element.value;

                        if (status == 0) // currently set to inactive
                        {
                            // update status to active
                            element.value = 1;
                            element.innerHTML = "Active";
                            element.classList.remove("btn-danger");
                            element.classList.add("btn-success");
                        }
                        else // currently set to active, or other?
                        {
                            // update status to inactive
                            element.value = 0;
                            element.innerHTML = "Inactive";
                            element.classList.remove("btn-success");
                            element.classList.add("btn-danger");
                        }
                    }

                    /** function to update the comparison element */
                    function updateYesNoToggle(id)
                    {
                        // get current status of the element
                        let element = document.getElementById(id);
                        let status = element.value;

                        if (status == 0) // currently set to non-comparison period
                        {
                            // update status to comparison period
                            element.value = 1;
                            element.innerHTML = "Yes";
                            element.classList.remove("btn-danger");
                            element.classList.add("btn-success");
                        }
                        else // currently set to comparison period, or other?
                        {
                            // update status to non-comparison period
                            element.value = 0;
                            element.innerHTML = "No";
                            element.classList.remove("btn-success");
                            element.classList.add("btn-danger");
                        }
                    }

                    /** function to get the edit period modal */
                    function getEditPeriodModal(period_id)
                    {
                        // send the data to create the edit period modal
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/periods/getEditPeriodModal.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                document.getElementById("edit-period-modal-div").innerHTML = this.responseText;     

                                // display the edit period modal
                                $("#editPeriodModal").modal("show");

                                // initialize the date range pickers in the edit period modal
                                $(function() {
                                    $("#edit-start").daterangepicker({
                                        singleDatePicker: true,
                                        showDropdowns: true,
                                        minYear: 2000,
                                        maxYear: <?php echo date("Y") + 10; ?>
                                    });

                                    $("#edit-end").daterangepicker({
                                        singleDatePicker: true,
                                        showDropdowns: true,
                                        minYear: 2000,
                                        maxYear: <?php echo date("Y") + 10; ?>
                                    });
                                });
                            }
                        };
                        xmlhttp.send("period_id="+period_id);
                    }

                    /** function to edit an existing period */
                    function editPeriod(period_id)
                    {
                        // get the details from the edit period modal
                        let name = document.getElementById("edit-name").value;
                        let desc = document.getElementById("edit-desc").value;
                        let start = document.getElementById("edit-start").value;
                        let end = document.getElementById("edit-end").value;
                        let caseload_start = document.getElementById("edit-caseload_term-start").value;
                        let caseload_end = document.getElementById("edit-caseload_term-end").value;
                        let status = document.getElementById("edit-status").value;
                        let comparison = document.getElementById("edit-comp").value;
                        let editable = document.getElementById("edit-editable").value;
                        let next = document.getElementById("edit-next").value;

                        // get quarters data
                        let q1_label = document.getElementById("edit-q1-label").value;
                        let q2_label = document.getElementById("edit-q2-label").value;
                        let q3_label = document.getElementById("edit-q3-label").value;
                        let q4_label = document.getElementById("edit-q4-label").value;
                        let q1_status = document.getElementById("edit-q1-status").value;
                        let q2_status = document.getElementById("edit-q2-status").value;
                        let q3_status = document.getElementById("edit-q3-status").value;
                        let q4_status = document.getElementById("edit-q4-status").value;

                        // creat the string of data to send
                        let sendString = "period_id="+period_id+"&name="+name+"&desc="+desc+"&start="+start+"&end="+end+"&status="+status+"&comparison="+comparison+"&editable="+editable+"&next="+next+"&caseload_term_start="+caseload_start+"&caseload_term_end="+caseload_end;
                        sendString += "&q1_label="+q1_label+"&q2_label="+q2_label+"&q3_label="+q3_label+"&q4_label="+q4_label+"&q1_status="+q1_status+"&q2_status="+q2_status+"&q3_status="+q3_status+"&q4_status="+q4_status;

                        // send the data to edit the period
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/periods/editPeriod.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // create the status modal
                                let status_title = "Edit Period Status";
                                let status_body = this.responseText;
                                createStatusModal("refresh", status_title, status_body);

                                // hide the current modal
                                $("#editPeriodModal").modal("hide");
                            }
                        };
                        xmlhttp.send(sendString);
                    }

                    /** function to get the delete period modal */
                    function getDeletePeriodModal(period_id)
                    {
                        // send the data to create the edit period modal
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/periods/getDeletePeriodModal.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                document.getElementById("delete-period-modal-div").innerHTML = this.responseText;     

                                // display the edit period modal
                                $("#deletePeriodModal").modal("show");
                            }
                        };
                        xmlhttp.send("period_id="+period_id);
                    }

                    /** function to delete the period */
                    function deletePeriod(id)
                    {
                        // send the data to process the edit customer request
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/periods/deletePeriod.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // create the status modal
                                let status_title = "Delete Period Status";
                                let status_body = this.responseText;
                                createStatusModal("refresh", status_title, status_body);

                                // hide the current modal
                                $("#deletePeriodModal").modal("hide");
                            }
                        };
                        xmlhttp.send("period_id="+id);
                    }

                    /** function to copy invoices from one period to another */
                    function copyData()
                    {
                        // disable the "Bill Districts" button, add processing spinner
                        let copyDataBtn = document.getElementById("copyDataBtn");
                        copyDataBtn.setAttribute("disabled", true);
                        let spinner = document.createElement("i");
                        spinner.className = "fa-solid fa-spinner fa-spin ms-2";
                        copyDataBtn.append(spinner);

                        // get the form data
                        let from = document.getElementById("copy-from").value;
                        let to = document.getElementById("copy-to").value;

                        // create the string of data to send
                        let sendString = "from_period="+from+"&to_period="+to;

                        // get data to copy
                        if ($("#copy-employee_compensation").is(":checked")) { sendString += "&employee_compensation=1"; }
                        if ($("#copy-employee_expenses").is(":checked")) { sendString += "&employee_expenses=1"; }
                        if ($("#copy-project_status").is(":checked")) { sendString += "&project_status=1"; }
                        if ($("#copy-project_employees").is(":checked")) { sendString += "&project_employees=1"; }
                        if ($("#copy-project_expenses").is(":checked")) { sendString += "&project_expenses=1"; }
                        if ($("#copy-service_costs").is(":checked")) { sendString += "&service_costs=1"; }
                        if ($("#copy-invoices").is(":checked")) { sendString += "&invoices=1"; }
                        if ($("#copy-revenues").is(":checked")) { sendString += "&revenues=1"; }

                        // send the data to process the copy invoices request
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/periods/copyData.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // create the status modal
                                let status_title = "Data Transfer Status";
                                let status_body = encodeURIComponent(this.responseText);
                                createStatusModal("refresh", status_title, status_body);

                                // hide the current modal
                                $("#dataTransferModal").modal("hide");
                            }
                        };
                        xmlhttp.send(sendString);
                    }

                    /** function to lock/unlock the quarter */
                    function toggleStatus(origin, quarter)
                    {
                        let element = document.getElementById(origin+"-q"+quarter+"-status");
                        let status = element.value;

                        // button is currently locked and set to locked
                        if (status == 1)
                        {
                            element.classList.remove("btn-danger");
                            element.classList.add("btn-success");
                            element.innerHTML = "<i class=\"fa-solid fa-lock-open\"></i>";
                            element.value = 0;
                        }
                        // button is currently set to unlocked (... or other...)
                        else
                        {
                            element.classList.remove("btn-success");
                            element.classList.add("btn-danger");
                            element.innerHTML = "<i class=\"fa-solid fa-lock\"></i>";
                            element.value = 1;
                        }
                    }

                    /** function to take a snapshot of a quarter */
                    function takeSnapshot(period_id, quarter)
                    {
                        // send the data to process the copy invoices request
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/periods/takeSnapshot.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // create the status modal
                                let status_title = "Snapshot Status";
                                let status_body = encodeURIComponent(this.responseText);
                                createStatusModal("alert", status_title, status_body);
                            }
                        };
                        xmlhttp.send("period_id="+period_id+"&quarter="+quarter);
                    }
                </script>

                <div class="report">
                    <!-- Page Header -->
                    <div class="table-header p-0">
                        <div class="row d-flex justify-content-center align-items-center text-center py-2 px-3">
                            <!-- Period & Filters-->
                            <div class="col-6 col-sm-4 col-md-4 col-lg-3 col-xl-2 col-xxl-2 p-0">
                                <div class="row px-3">
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
                                <h1 class="m-0">Period Management</h1>
                            </div>

                            <!-- Page Management Dropdown -->
                            <div class="col-6 col-sm-4 col-md-4 col-lg-3 col-xl-2 col-xxl-2 p-0">
                                <div class="dropdown float-end">
                                    <button class="btn btn-primary dropdown-toggle" type="button" id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">
                                        Manage Periods
                                    </button>
                                    <ul class="dropdown-menu p-0" aria-labelledby="dropdownMenuButton1">
                                        <li><button class="dropdown-item quickNav-dropdown-item text-center w-100 px-3 py-2 rounded-0" type="button" data-bs-toggle="modal" data-bs-target="#addPeriodModal">Add Period</button></li>
                                        <li><button class="dropdown-item quickNav-dropdown-item text-center w-100 px-3 py-2 rounded-0" type="button" data-bs-toggle="modal" data-bs-target="#dataTransferModal">Transfer Data</button></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row report-body m-0">
                        <table id="periods" class="report_table w-100">
                            <thead>
                                <tr>
                                    <th class="text-center py-1 px-2" rowspan="2">Name</th>
                                    <th class="text-center py-1 px-2" colspan="4">Quarters</th>
                                    <th class="text-center py-1 px-2" colspan="2">Fiscal Cycle</th>
                                    <th class="text-center py-1 px-2" colspan="2">School Cycle</th>
                                    <th class="text-center py-1 px-2" rowspan="2">Actions</th>
                                </tr>

                                <tr>
                                    <th class="text-center py-1 px-2">Q1</th>
                                    <th class="text-center py-1 px-2">Q2</th>
                                    <th class="text-center py-1 px-2">Q3</th>
                                    <th class="text-center py-1 px-2">Q4</th>
                                    <th class="text-center py-1 px-2">Start</th>
                                    <th class="text-center py-1 px-2">End</th>
                                    <th class="text-center py-1 px-2">Start</th>
                                    <th class="text-center py-1 px-2">End</th>
                                </tr>
                            </thead>
                        </table>
                        <?php createTableFooterV2("periods", "BAP_Periods_PageLength", $USER_SETTINGS["page_length"], true, true); ?>
                    </div>
                </div>

                <!-- Add Period Modal -->
                <div class="modal fade" tabindex="-1" role="dialog" id="addPeriodModal" data-bs-backdrop="static" aria-labelledby="addPeriodModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg" role="document">
                        <div class="modal-content">
                            <div class="modal-header primary-modal-header">
                                <h5 class="modal-title primary-modal-title" id="addPeriodModalLabel">Add Period</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>

                            <div class="modal-body">
                                <div class="form-group mb-3">
                                    <label for="add-name"><span class="required-field">*</span> Period Name:</label>
                                    <input type="text" class="form-control w-100" id="add-name" name="add-name" required>
                                </div>

                                <div class="form-group mb-3">
                                    <label for="add-desc">Description:</label>
                                    <input type="text" class="form-control w-100" id="add-desc" name="add-desc" required>
                                </div>

                                <!-- Quarters -->
                                <div class="form-group mb-3">
                                    <fieldset class="border p-2">
                                        <legend class="float-none w-auto px-4 py-0 m-0 text-center"><h4 class="mb-0">Quarters</h4></legend>
                                        
                                        <table class="report_table w-100">
                                            <thead>
                                                <tr>
                                                    <th class="text-center w-25" id="Q1-header">Q1</th>
                                                    <th class="text-center w-25" id="Q2-header">Q2</th>
                                                    <th class="text-center w-25" id="Q3-header">Q3</th>
                                                    <th class="text-center w-25" id="Q4-header">Q4</th>
                                                </tr>
                                            </thead>

                                            <tbody>
                                                <!-- Labels -->
                                                <tr>
                                                    <td><input type="text" class="form-control" id="add-q1-label" name="add-q1-label" aria-labelledby="#Q1-header" placeholder="Q1 Label"></td>
                                                    <td><input type="text" class="form-control" id="add-q2-label" name="add-q2-label" aria-labelledby="#Q2-header" placeholder="Q2 Label"></td>
                                                    <td><input type="text" class="form-control" id="add-q3-label" name="add-q3-label" aria-labelledby="#Q3-header" placeholder="Q3 Label"></td>
                                                    <td><input type="text" class="form-control" id="add-q4-label" name="add-q4-label" aria-labelledby="#Q4-header" placeholder="Q4 Label"></td>
                                                </tr>

                                                <!-- Lock Status -->
                                                <tr>
                                                    <td><button class="btn btn-success btn-sm w-100" id="add-q1-status" value="0" onclick="toggleStatus('add', 1);" aria-labelledby="#Q1-header"><i class="fa-solid fa-lock-open"></i></button></td>
                                                    <td><button class="btn btn-success btn-sm w-100" id="add-q2-status" value="0" onclick="toggleStatus('add', 2);" aria-labelledby="#Q2-header"><i class="fa-solid fa-lock-open"></i></button></td>
                                                    <td><button class="btn btn-success btn-sm w-100" id="add-q3-status" value="0" onclick="toggleStatus('add', 3);" aria-labelledby="#Q3-header"><i class="fa-solid fa-lock-open"></i></button></td>
                                                    <td><button class="btn btn-success btn-sm w-100" id="add-q4-status" value="0" onclick="toggleStatus('add', 4);" aria-labelledby="#Q4-header"><i class="fa-solid fa-lock-open"></i></button></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </fieldset>
                                </div>

                                <!-- Fiscal Cycle -->
                                <fieldset class="border p-2">
                                    <legend class="float-none w-auto px-4 py-0 m-0 text-center"><h4 class="mb-0">Fiscal Cycle</h4></legend>
                                    <div class="form-row d-flex justify-content-center align-items-center mb-3">
                                        <!-- Start Date -->
                                        <div class="form-group col-6 pe-2">
                                            <label for="add-start"><span class="required-field">*</span> Start Date:</label>
                                            <div class="input-group w-100 h-auto">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text h-100" id="nav-search-icon"><i class="fa-solid fa-calendar-days"></i></span>
                                                </div>
                                                <input type="text" class="form-control" id="add-start" name="add-start" required>
                                            </div>
                                        </div>

                                        <!-- End Date -->
                                        <div class="form-group col-6 ps-2">
                                            <label for="add-end"><span class="required-field">*</span> End Date:</label>
                                            <div class="input-group w-100 h-auto">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text h-100" id="nav-search-icon"><i class="fa-solid fa-calendar-days"></i></span>
                                                </div>
                                                <input type="text" class="form-control" id="add-end" name="add-end" required>
                                            </div>
                                        </div>
                                    </div>
                                </fieldset>

                                <!-- Caseload Term -->
                                <fieldset class="border p-2">
                                    <legend class="float-none w-auto px-4 py-0 m-0 text-center"><h4 class="mb-0">Caseload Term</h4></legend>
                                    <div class="form-row d-flex justify-content-center align-items-center mb-3">
                                        <!-- Start Date -->
                                        <div class="form-group col-6 pe-2">
                                            <label for="add-caseload_term-start"><span class="required-field">*</span> Start Date:</label>
                                            <div class="input-group w-100 h-auto">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text h-100" id="nav-search-icon"><i class="fa-solid fa-calendar-days"></i></span>
                                                </div>
                                                <input type="text" class="form-control" id="add-caseload_term-start" name="add-caseload_term-start" required>
                                            </div>
                                        </div>

                                        <!-- End Date -->
                                        <div class="form-group col-6 ps-2">
                                            <label for="add-caseload_term-end"><span class="required-field">*</span> End Date:</label>
                                            <div class="input-group w-100 h-auto">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text h-100" id="nav-search-icon"><i class="fa-solid fa-calendar-days"></i></span>
                                                </div>
                                                <input type="text" class="form-control" id="add-caseload_term-end" name="add-caseload_term-end" required>
                                            </div>
                                        </div>
                                    </div>
                                </fieldset>

                                <div class="form-group row mb-3">
                                    <div class="form-group col-3 text-center pe-2">
                                        <label for="add-status">Status</label>
                                        <button class="btn btn-danger w-100" id="add-status" value=0 onclick="updateStatus('add-status');">Inactive</button>
                                    </div>

                                    <div class="form-group col-3 text-center px-2">
                                        <label for="add-comp">Comparison Period</label>
                                        <button class="btn btn-danger w-100" id="add-comp" value=0 onclick="updateYesNoToggle('add-comp');">No</button>
                                    </div>

                                    <div class="form-group col-3 text-center px-2">
                                        <label for="add-next">Next Period</label>
                                        <button class="btn btn-danger w-100" id="add-next" value=0 onclick="updateYesNoToggle('add-next');">No</button>
                                    </div>

                                    <div class="form-group col-3 text-center ps-2">
                                        <label for="add-editable">Editable</label>
                                        <button class="btn btn-success w-100" id="add-editable" value=1 onclick="updateYesNoToggle('add-editable');">Yes</button>
                                    </div>
                                </div>

                                <!-- Required Field Indicator -->
                                <div class="row">
                                    <p class="text-center fst-italic m-0"><span class="required-field">*</span> indicates a required field</p>
                                </div>
                            </div>

                            <div class="modal-footer">
                                <button type="button" class="btn btn-primary" onclick="addPeriod();"><i class="fa-solid fa-floppy-disk"></i> Save New Period</button>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> Close</button>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- End Add Period Modal -->

                <!-- Period Data Transfer Modal -->
                <div class="modal fade" tabindex="-1" role="dialog" id="dataTransferModal" data-bs-backdrop="static" aria-labelledby="dataTransferModalLabel" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header primary-modal-header">
                                <h5 class="modal-title primary-modal-title" id="dataTransferModalLabel">Transfer Data</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>

                            <div class="modal-body px-5">
                                <div class="row align-items-center mt-0 mb-1">
                                    <h5 class="p-0">Periods To Transfer:</h5>
                                </div>
                                
                                <div class="row align-items-center mb-2">
                                    <label for="copy-from" class="p-0"><span class="required-field">*</span> Copy Data From:</label>
                                    <select class="form-select w-100" id="copy-from" name="copy-from" required>
                                        <option></option>
                                        <?php 
                                            // create a dropdown list of all periods
                                            $getPeriods = mysqli_query($conn, "SELECT id, name FROM periods ORDER BY name ASC");
                                            if (mysqli_num_rows($getPeriods) > 0) // periods found; continue
                                            {
                                                while ($period = mysqli_fetch_array($getPeriods))
                                                {
                                                    echo "<option value='".$period["id"]."'>".$period["name"]."</option>";
                                                }
                                            }
                                        ?>
                                    </select>
                                </div>

                                <div class="row align-items-center my-2">
                                    <label for="copy-to" class="p-0"><span class="required-field">*</span> Copy Data To:</label>
                                    <select class="form-select w-100" id="copy-to" name="copy-to" required>
                                        <option></option>
                                        <?php 
                                            // create a dropdown list of all periods
                                            $getPeriods = mysqli_query($conn, "SELECT id, name FROM periods ORDER BY name ASC");
                                            if (mysqli_num_rows($getPeriods) > 0) // periods found; continue
                                            {
                                                while ($period = mysqli_fetch_array($getPeriods))
                                                {
                                                    echo "<option value='".$period["id"]."'>".$period["name"]."</option>";
                                                }
                                            }
                                        ?>
                                    </select>
                                </div>

                                <div class="row align-items-center mt-4 mb-1">
                                    <h5 class="p-0">Data To Copy:</h5>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="copy-employee_compensation">
                                        <label class="form-check-label" for="copy-employee_compensation">
                                            Employee Compensation
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="copy-employee_expenses">
                                        <label class="form-check-label" for="copy-employee_expenses">
                                            Employee Expenses
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="copy-project_status">
                                        <label class="form-check-label" for="copy-project_status">
                                            Project Statuses
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="copy-project_employees">
                                        <label class="form-check-label" for="copy-project_employees">
                                            Project Employees
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="copy-project_expenses">
                                        <label class="form-check-label" for="copy-project_expenses">
                                            Project Expenses
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="copy-service_costs">
                                        <label class="form-check-label" for="copy-service_costs">
                                            Service Costs
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="copy-invoices">
                                        <label class="form-check-label" for="copy-invoices">
                                            Invoices
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="copy-revenues">
                                        <label class="form-check-label" for="copy-revenues">
                                            Other Revenues
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="modal-footer">
                                <button type="button" class="btn btn-primary" onclick="copyData();" id="copyDataBtn"><i class="fa-solid fa-copy"></i> Transfer Data</button>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> Close</button>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- End Period Data Transfer Modal -->

                <!-- Edit Period Modal -->
                <div id="edit-period-modal-div"></div>
                <!-- End Edit Period Modal -->

                <!-- Delete Period Modal -->
                <div id="delete-period-modal-div"></div>
                <!-- End Delete Period Modal -->

                <script>
                    var periods = $("#periods").DataTable({
                        ajax: {
                            url: "ajax/periods/getPeriods.php",
                            type: "POST"
                        },
                        autoWidth: false,
                        pageLength: <?php if (isset($USER_SETTINGS["page_length"])) { echo $USER_SETTINGS["page_length"]; } else { echo 10; } ?>,
                        lengthMenu: [ [10, 25, 50, 100, 250, -1], [10, 25, 50, 100, 250, "All"] ],
                        columns: [
                            { data: "name", orderable: true, width: "15%" },
                            { data: "q1", orderable: true, width: "11.875%" },
                            { data: "q2", orderable: true, width: "11.875%" },
                            { data: "q3", orderable: true, width: "11.875%" },
                            { data: "q4", orderable: true, width: "11.875%" },
                            { data: "fiscal_start", orderable: true, width: "7.5%", className: "text-center" },
                            { data: "fiscal_end", orderable: true, width: "7.5%", className: "text-center" },
                            { data: "school_start", orderable: true, width: "7.5%", className: "text-center" },
                            { data: "school_end", orderable: true, width: "7.5%", className: "text-center" },
                            { data: "actions", orderable: false, width: "7.5%", className: "text-center" },
                            { data: "comparison", orderable: true, visible: false },
                            { data: "next", orderable: true, visible: false },
                            { data: "editable", orderable: true, visible: false },
                            { data: "active", orderable: false, visible: false }
                        ],
                        order: [
                            [13, "desc"],
                            [0, "asc"]
                        ],
                        dom: 'rt',
                        language: {
                            search: '<div class="input-group mb-1"><div class="input-group-prepend"><span class="d-flex align-items-center h-100 mx-2" id="nav-search-icon"><i class="fa fa-search" aria-hidden="true"></i></span></div>_INPUT_</div>',
                            lengthMenu: '<div class="mx-1 mb-1">Show _MENU_ entries</div>',
                            info: '<div class="mx-1">Showing _START_ to _END_ of _TOTAL_ entries</div>'
                        },
                        paging: true,
                        rowCallback: function (row, data, index)
                        {
                            // initialize page selection
                            updatePageSelection("periods");
                        }
                    });

                    // initialize daterange fields
                    $(function() {
                        $("#add-start").daterangepicker({
                            singleDatePicker: true,
                            showDropdowns: true,
                            minYear: 2000,
                            maxYear: <?php echo date("Y") + 10; ?>
                        });

                        $("#add-end").daterangepicker({
                            singleDatePicker: true,
                            showDropdowns: true,
                            minYear: 2000,
                            maxYear: <?php echo date("Y") + 10; ?>
                        });
                    });

                    // search table by custom search filter
                    $('#search-all').keyup(function() {
                        periods.search($(this).val()).draw();
                    });
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