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
                    /** function to clear all employees */
                    function clearEmployees()
                    {
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/clear/clearEmployees.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // create the status modal
                                let status_title = "Clear Employees Status";
                                let status_body = this.responseText;
                                createStatusModal("refresh", status_title, status_body);

                                // hide the current modal
                                $("#clearEmployeesModal").modal("hide");
                            }
                        };
                        xmlhttp.send();
                    }

                    /** function to clear/delete departments */
                    function toggleClearDepartments(option)
                    {
                        // get the current value of button
                        let value = document.getElementById(option).value;

                        // set both buttons to off
                        document.getElementById("clearDepts-all").value = 0;
                        document.getElementById("clearDepts-members").value = 0;
                        document.getElementById("clearDepts-all").classList.remove("btn-primary");
                        document.getElementById("clearDepts-members").classList.remove("btn-primary");
                        document.getElementById("clearDepts-all").classList.add("btn-secondary");
                        document.getElementById("clearDepts-members").classList.add("btn-secondary");

                        // toggle button on if currently off
                        if (value == 0)
                        {
                            document.getElementById(option).value = 1;
                            document.getElementById(option).classList.remove("btn-secondary");
                            document.getElementById(option).classList.add("btn-primary");
                        }
                    }

                    /** function to clear/delete projects */
                    function toggleClearProjects(option)
                    {
                        // get the current value of button
                        let value = document.getElementById(option).value;

                        if (option == "clearProjects-all")
                        {
                            // toggle button on if currently off
                            if (value == 0)
                            {
                                // toggle all buttons on 
                                document.getElementById(option).value = 1;
                                document.getElementById(option).classList.remove("btn-secondary");
                                document.getElementById(option).classList.add("btn-primary");

                                document.getElementById("clearProjects-emps").value = 1;
                                document.getElementById("clearProjects-emps").classList.remove("btn-secondary");
                                document.getElementById("clearProjects-emps").classList.add("btn-primary");

                                document.getElementById("clearProjects-exps").value = 1;
                                document.getElementById("clearProjects-exps").classList.remove("btn-secondary");
                                document.getElementById("clearProjects-exps").classList.add("btn-primary");

                                document.getElementById("clearProjects-revs").value = 1;
                                document.getElementById("clearProjects-revs").classList.remove("btn-secondary");
                                document.getElementById("clearProjects-revs").classList.add("btn-primary");

                                // show disclaimer
                                document.getElementById("clearProjects-all-alert").style.display = "block";
                            }
                            // toggle button off
                            else
                            {
                                document.getElementById(option).value = 0;
                                document.getElementById(option).classList.remove("btn-primary");
                                document.getElementById(option).classList.add("btn-secondary");

                                // hide disclaimer
                                document.getElementById("clearProjects-all-alert").style.display = "none";
                            }
                        }
                        else
                        {
                            // get all value
                            let allValue = document.getElementById("clearProjects-all").value;

                            // toggle button on if currently off
                            if (value == 0)
                            {
                                document.getElementById(option).value = 1;
                                document.getElementById(option).classList.remove("btn-secondary");
                                document.getElementById(option).classList.add("btn-primary");
                            }
                            // toggle button off
                            else
                            {
                                document.getElementById(option).value = 0;
                                document.getElementById(option).classList.remove("btn-primary");
                                document.getElementById(option).classList.add("btn-secondary");

                                if (allValue == 1)
                                {
                                    // turn off all button
                                    document.getElementById("clearProjects-all").value = 0;
                                    document.getElementById("clearProjects-all").classList.remove("btn-primary");
                                    document.getElementById("clearProjects-all").classList.add("btn-secondary");

                                    // hide disclaimer
                                    document.getElementById("clearProjects-all-alert").style.display = "none";
                                }
                            }
                        }
                    }

                    /** function to clear or delete departments */
                    function clearDepartments()
                    {
                        // get which option was selected
                        let allValue = document.getElementById("clearDepts-all").value;
                        let membersValue = document.getElementById("clearDepts-members").value;

                        // create the string of data to send
                        let sendString = "clearAll="+allValue+"&clearMembers="+membersValue;

                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/clear/clearDepartments.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // create the status modal
                                let status_title = "Clear Departments Status";
                                let status_body = this.responseText;
                                createStatusModal("refresh", status_title, status_body);

                                // hide the current modal
                                $("#clearDepartmentsModal").modal("hide");
                            }
                        };
                        xmlhttp.send(sendString);
                    }

                    /** function to clear invoices */
                    function clearInvoices()
                    {
                        // get which option was selected
                        let period = document.getElementById("clearInvoices-period").value;
                        let service = document.getElementById("clearInvoices-service").value;

                        // create the string of data to send
                        let sendString = "period="+period+"&service="+service;

                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/clear/clearInvoices.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // create the status modal
                                let status_title = "Clear Invoices Status";
                                let status_body = this.responseText;
                                createStatusModal("refresh", status_title, status_body);

                                // hide the current modal
                                $("#clearInvoicesModal").modal("hide");
                            }
                        };
                        xmlhttp.send(sendString);
                    }

                    /** function to clear customers */
                    function clearCustomers()
                    {
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/clear/clearCustomers.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // create the status modal
                                let status_title = "Clear Customers Status";
                                let status_body = this.responseText;
                                createStatusModal("refresh", status_title, status_body);

                                // hide the current modal
                                $("#clearCustomersModal").modal("hide");
                            }
                        };
                        xmlhttp.send();
                    }

                    /** function to clear services */
                    function clearServices()
                    {
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/clear/clearServices.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // create the status modalS
                                let status_title = "Clear Services Status";
                                let status_body = this.responseText;
                                createStatusModal("refresh", status_title, status_body);

                                // hide the current modal
                                $("#clearServicesModal").modal("hide");
                            }
                        };
                        xmlhttp.send();
                    }

                    /** function to clear projects */
                    function clearProjects()
                    {
                        // get which option was selected and period
                        let period = document.getElementById("clearProjects-period").value;
                        let allValue = document.getElementById("clearProjects-all").value;
                        let empsValue = document.getElementById("clearProjects-emps").value;
                        let expsValue = document.getElementById("clearProjects-exps").value;
                        let revsValue = document.getElementById("clearProjects-revs").value;

                        // create the string of data to send
                        let sendString = "period="+period+"&clearAll="+allValue+"&clearEmps="+empsValue+"&clearExps="+expsValue+"&clearRevs="+revsValue;

                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/clear/clearProjects.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // create the status modal
                                let status_title = "Clear Projects Status";
                                let status_body = this.responseText;
                                createStatusModal("refresh", status_title, status_body);

                                // hide the current modal
                                $("#clearProjectsModal").modal("hide");
                            }
                        };
                        xmlhttp.send(sendString);
                    }
                </script>

                <!-- Header -->
                <div class="row m-0 p-0">
                    <h1 class="col-12 col-sm-8 col-md-6 col-lg-4 col-xl-4 col-xxl-4 page-header my-3 py-3 ps-3 pe-5">
                        <a class="back-button" href="manage.php" title="Return to Manage."><i class="fa-solid fa-angles-left"></i></a>
                        <div class="d-inline float-end">Clear</div>
                    </h1>
                </div>

                <!-- Body -->
                <div class="row d-flex justify-content-center align-items-around m-0">
                    <div class="col-12 col-sm-12 col-md-6 col-lg-4 col-xl-4 col-xxl-3 p-3">
                        <button class="btn-dash d-flex w-100 h-100 justify-content-center align-items-center p-5" type="button" data-bs-toggle="modal" data-bs-target="#clearEmployeesModal">Clear Employees</button>
                    </div>

                    <div class="col-12 col-sm-12 col-md-6 col-lg-4 col-xl-4 col-xxl-3 p-3">
                        <button class="btn-dash d-flex w-100 h-100 justify-content-center align-items-center p-5" type="button" data-bs-toggle="modal" data-bs-target="#clearDepartmentsModal">Clear Departments</button>
                    </div>

                    <div class="col-12 col-sm-12 col-md-6 col-lg-4 col-xl-4 col-xxl-3 p-3">
                        <button class="btn-dash d-flex w-100 h-100 justify-content-center align-items-center p-5" type="button" data-bs-toggle="modal" data-bs-target="#clearCustomersModal">Clear Customers</button>
                    </div>

                    <div class="col-12 col-sm-12 col-md-6 col-lg-4 col-xl-4 col-xxl-3 p-3">
                        <button class="btn-dash d-flex w-100 h-100 justify-content-center align-items-center p-5" type="button" data-bs-toggle="modal" data-bs-target="#clearServicesModal">Clear Services</button>
                    </div>

                    <div class="col-12 col-sm-12 col-md-6 col-lg-4 col-xl-4 col-xxl-3 p-3">
                        <button class="btn-dash d-flex w-100 h-100 justify-content-center align-items-center p-5" type="button" data-bs-toggle="modal" data-bs-target="#clearInvoicesModal">Clear Invoices</button>
                    </div>

                    <div class="col-12 col-sm-12 col-md-6 col-lg-4 col-xl-4 col-xxl-3 p-3">
                        <button class="btn-dash d-flex w-100 h-100 justify-content-center align-items-center p-5" type="button" data-bs-toggle="modal" data-bs-target="#clearProjectsModal">Clear Projects</button>
                    </div>
                </div>

                <!-- MODALS --> 
                <!-- Clear Employees Modal -->
                <div class="modal fade" tabindex="-1" role="dialog" id="clearEmployeesModal" data-bs-backdrop="static" aria-labelledby="clearEmployeesModalLabel" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header primary-modal-header">
                                <h5 class="modal-title primary-modal-title" id="clearEmployeesModalLabel">Clear Employees</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>

                            <div class="modal-body">
                                <p>Are you sure you want to clear all employees? This action is irreversible!</p>
                            </div>

                            <div class="modal-footer">
                                <button type="submit" class="btn btn-primary" onclick="clearEmployees();"><i class="fa-solid fa-trash-can"></i> Clear Employees</button>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> Close</button>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- End Clear Employees Modal -->

                <!-- Clear Departments Modal -->
                <div class="modal fade" tabindex="-1" role="dialog" id="clearDepartmentsModal" data-bs-backdrop="static" aria-labelledby="clearDepartmentsModalLabel" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header primary-modal-header">
                                <h5 class="modal-title primary-modal-title" id="clearDepartmentsModalLabel">Clear Departments</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>

                            <div class="modal-body">
                                <div class="btn-group w-100" role="group" aria-label="Clear department options">
                                    <button type="button" id="clearDepts-all" class="btn btn-secondary w-100" value="0" onclick="toggleClearDepartments('clearDepts-all');">Delete All Departments</button>
                                    <button type="button" id="clearDepts-members" class="btn btn-secondary w-100" value="0" onclick="toggleClearDepartments('clearDepts-members');">Clear Department Members</button>
                                </div>
                            </div>

                            <div class="modal-footer">
                                <button type="submit" class="btn btn-primary" onclick="clearDepartments();"><i class="fa-solid fa-trash-can"></i> Clear Departments</button>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> Close</button>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- End Clear Departments Modal -->

                <!-- Clear Customers Modal -->
                <div class="modal fade" tabindex="-1" role="dialog" id="clearCustomersModal" data-bs-backdrop="static" aria-labelledby="clearCustomersModalLabel" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header primary-modal-header">
                                <h5 class="modal-title primary-modal-title" id="clearCustomersModalLabel">Clear Customers</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>

                            <div class="modal-body">
                                <p>Are you sure you want to clear all customers? This action is irreversible! This action will also <b>delete all invoices</b> across all periods.</p>
                            </div>

                            <div class="modal-footer">
                                <button type="submit" class="btn btn-primary" onclick="clearCustomers();"><i class="fa-solid fa-trash-can"></i> Clear Customers</button>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> Close</button>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- End Clear Customers Modal -->

                <!-- Clear Services Modal -->
                <div class="modal fade" tabindex="-1" role="dialog" id="clearServicesModal" data-bs-backdrop="static" aria-labelledby="clearServicesModalLabel" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header primary-modal-header">
                                <h5 class="modal-title primary-modal-title" id="clearServicesModalLabel">Clear Services</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>

                            <div class="modal-body">
                                <p>Are you sure you want to clear all services? This action is irreversible! This action will also <b>delete all invoices</b> across all periods.</p></p>
                            </div>

                            <div class="modal-footer">
                                <button type="submit" class="btn btn-primary" onclick="clearServices();"><i class="fa-solid fa-trash-can"></i> Clear Services</button>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> Close</button>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- End Clear Services Modal -->

                <!-- Clear Invoices Modal -->
                <div class="modal fade" tabindex="-1" role="dialog" id="clearInvoicesModal" data-bs-backdrop="static" aria-labelledby="clearInvoicesModalLabel" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header primary-modal-header">
                                <h5 class="modal-title primary-modal-title" id="clearInvoicesModalLabel">Clear Invoices</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>

                            <div class="modal-body">
                                <label for="clearInvoices-period">Select a period to clear invoices for:</label>
                                <select class="form-select mb-3" id="clearInvoices-period" name="clearInvoices-period">
                                    <option value="-1"></option>
                                    <option value="-2">All Periods</option>
                                    <?php
                                        // create a list of all periods
                                        $getPeriods = mysqli_query($conn, "SELECT id, name FROM periods ORDER BY start_date ASC");
                                        if (mysqli_num_rows($getPeriods) > 0) // periods found; continue
                                        {
                                            while ($period = mysqli_fetch_array($getPeriods))
                                            {
                                                echo "<option value='".$period["id"]."'>".$period["name"]."</option>";
                                            }
                                        }
                                    ?>
                                </select>

                                <label for="clearInvoices-service">Select a service to clear invoices for:</label>
                                <select class="form-select mb-3" id="clearInvoices-service" name="clearInvoices-service">
                                    <option value="-1"></option>
                                    <option value="-2">All Services</option>
                                    <?php
                                        // create a list of all services
                                        $getServices = mysqli_query($conn, "SELECT id, name FROM services ORDER BY name ASC");
                                        if (mysqli_num_rows($getServices) > 0) // services found; continue
                                        {
                                            while ($service = mysqli_fetch_array($getServices))
                                            {
                                                echo "<option value='".$service["id"]."'>".$service["name"]."</option>";
                                            }
                                        }

                                        // create a list of all other services
                                        $getOtherServices = mysqli_query($conn, "SELECT id, name FROM services_other ORDER BY name ASC");
                                        if (mysqli_num_rows($getOtherServices) > 0) // services found; continue
                                        {
                                            while ($service = mysqli_fetch_array($getOtherServices))
                                            {
                                                echo "<option value='".$service["id"]."'>".$service["name"]."</option>";
                                            }
                                        }
                                    ?>
                                </select>

                                <p class="m-0">Are you sure you want to clear invoices for the selected period and service? This action is irreversible!</p>
                            </div>

                            <div class="modal-footer">
                                <button type="submit" class="btn btn-primary" onclick="clearInvoices();"><i class="fa-solid fa-trash-can"></i> Clear Invoices</button>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> Close</button>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- End Clear Invoices Modal -->

                <!-- Clear Projects Modal -->
                <div class="modal fade" tabindex="-1" role="dialog" id="clearProjectsModal" data-bs-backdrop="static" aria-labelledby="clearProjectsModalLabel" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header primary-modal-header">
                                <h5 class="modal-title primary-modal-title" id="clearProjectsModalLabel">Clear Projects</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>

                            <div class="modal-body">
                                <label for="clearProjects-period">Select a period to clear project data for:</label>
                                <select class="form-select mb-3" id="clearProjects-period" name="clearProjects-period">
                                    <option value="-1"></option>
                                    <option value="-2">All Periods</option>
                                    <?php
                                        // create a list of all periods
                                        $getPeriods = mysqli_query($conn, "SELECT id, name FROM periods ORDER BY start_date ASC");
                                        if (mysqli_num_rows($getPeriods) > 0) // periods found; continue
                                        {
                                            while ($period = mysqli_fetch_array($getPeriods))
                                            {
                                                echo "<option value='".$period["id"]."'>".$period["name"]."</option>";
                                            }
                                        }
                                    ?>
                                </select>

                                <div class="btn-group w-100" role="group" aria-label="Clear projects options">
                                    <button type="button" id="clearProjects-all" class="btn btn-secondary w-100" value="0" onclick="toggleClearProjects('clearProjects-all');">Delete All Projects</button>
                                    <button type="button" id="clearProjects-emps" class="btn btn-secondary w-100" value="0" onclick="toggleClearProjects('clearProjects-emps');">Clear Project Employees</button>
                                    <button type="button" id="clearProjects-exps" class="btn btn-secondary w-100" value="0" onclick="toggleClearProjects('clearProjects-exps');">Clear Project Expenses</button>
                                    <button type="button" id="clearProjects-revs" class="btn btn-secondary w-100" value="0" onclick="toggleClearProjects('clearProjects-revs');">Clear Project Revenues</button>
                                </div>

                                <div class="alert alert-danger mt-3 mb-0" role="alert" id="clearProjects-all-alert" style="display: none;">
                                    <i class="fa-solid fa-triangle-exclamation"></i> Deleting all projects will delete all invoices for all periods, no matter which period you have selected. Please proceed with caution.
                                </div>
                            </div>

                            <div class="modal-footer">
                                <button type="submit" class="btn btn-primary" onclick="clearProjects();"><i class="fa-solid fa-trash-can"></i> Clear Projects</button>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> Close</button>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- End Clear Projects Modal -->
                <!-- END MODALS -->
            <?php 

            // disconnect from the database
            mysqli_close($conn);
        }
        else { denyAccess(); }
    }
    else { goToLogin(); }

    include_once("footer.php"); 
?>