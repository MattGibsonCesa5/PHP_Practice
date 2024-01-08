<?php 
    include_once("header.php");

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1) 
    { 
        if (isset($PERMISSIONS["VIEW_EMPLOYEES_ALL"]) || isset($PERMISSIONS["VIEW_EMPLOYEES_ASSIGNED"]))
        {
            // connect to the database
            $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

            // initialize an array to store all periods; then get all periods and store in the array
            $periods = [];
            $getPeriods = mysqli_query($conn, "SELECT id, name, active, start_date, end_date FROM `periods` ORDER BY active DESC, name ASC");
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
                        $active_start_date = date("m/d/Y", strtotime($period["start_date"]));
                        $active_end_date = date("m/d/Y", strtotime($period["end_date"])); 
                    }
                }
            }

            ?>
                <!-- Page Styling Override -->
                <style>
                    .dt-buttons
                    {
                        height: 100% !important;
                        width: 100% !important;
                        display: flex;
                        justify-content: center;
                    }

                    #employees tbody td
                    {
                        font-size: 14px !important;
                    }
                </style>

                <script>
                    <?php if (isset($PERMISSIONS["ADD_EMPLOYEES"])) { ?>
                        /** function to add a new employee */
                        function addEmployee()
                        {
                            // get the fixed period name
                            let period = document.getElementById("fixed-period").value;

                            // initialize the string of data to send
                            let sendString = "";

                            // get employee information form fields
                            let id = document.getElementById("add-id").value;
                            let fname = document.getElementById("add-fname").value;
                            let lname = document.getElementById("add-lname").value;
                            let email = document.getElementById("add-email").value;
                            let phone = document.getElementById("add-phone").value;
                            let birthday = document.getElementById("add-birthday").value;
                            let gender = document.getElementById("add-gender").value;
                            sendString += "id="+id+"&fname="+fname+"&lname="+lname+"&email="+email+"&phone="+phone+"&birthday="+birthday+"&gender="+gender;

                            // get employee address form fields
                            let line1 = document.getElementById("add-address_line1").value;
                            let line2 = document.getElementById("add-address_line2").value;
                            let city = document.getElementById("add-address_city").value;
                            let state = document.getElementById("add-address_state").value;
                            let zip = document.getElementById("add-address_zip").value;
                            sendString += "&line1="+line1+"&line2="+line2+"&city="+city+"&state="+state+"&zip="+zip;

                            // get employee role details form fields
                            let title = document.getElementById("add-title").value;
                            let department = document.getElementById("add-dept").value;
                            let supervisor = document.getElementById("add-supervisor").value;
                            let position = document.getElementById("add-position").value;
                            let area = document.getElementById("add-area").value;
                            let experience = document.getElementById("add-experience").value;
                            let experience_adjustment = document.getElementById("add-experience_adjustment").value;
                            let degree = document.getElementById("add-degree").value;
                            sendString += "&title="+title+"&department="+department+"&position="+position+"&area="+area+"&experience="+experience+"&degree="+degree+"&supervisor="+supervisor+"&experience_adjustment";

                            // get employee contract details form fields
                            let hire_date = document.getElementById("add-hire_date").value;
                            let end_date = document.getElementById("add-end_date").value;
                            let original_hire_date = document.getElementById("add-original_hire_date").value;
                            let original_end_date = document.getElementById("add-original_end_date").value;
                            let contract_start_date = document.getElementById("add-contract_start_date").value;
                            let contract_end_date = document.getElementById("add-contract_end_date").value;
                            let contract_type = document.getElementById("add-contract_type").value;
                            let days = document.getElementById("add-days").value;
                            let calendar_type = document.getElementById("add-calendar_type").value;
                            let rate = document.getElementById("add-rate").value;
                            let num_of_pays = document.getElementById("add-num_of_pays").value;
                            let health = document.getElementById("add-health").value;
                            let dental = document.getElementById("add-dental").value;
                            let wrs = document.getElementById("add-wrs").value;
                            sendString += "&contract_type="+contract_type+"&days="+days+"&rate="+rate+"&health="+health+"&dental="+dental+"&wrs="+wrs+"&contract_start_date="+contract_start_date+"&contract_end_date="+contract_end_date+"&calendar_type="+calendar_type+"&num_of_pays="+num_of_pays+"&hire_date="+hire_date+"&end_date="+end_date+"&original_hire_date="+original_hire_date+"&original_end_date="+original_end_date;

                            // get account information form fields
                            let status = document.getElementById("add-status").value;
                            let global = document.getElementById("add-global").value;
                            sendString += "&status="+status+"&global="+global;

                            // get syncing statuses
                            let sync_demographics = document.getElementById("add-sync-demographics").value;
                            let sync_position = document.getElementById("add-sync-position").value;
                            let sync_contract = document.getElementById("add-sync-contract").value;
                            sendString+="&sync_demographics="+sync_demographics+"&sync_position="+sync_position+"&sync_contract="+sync_contract;

                            // send the data to process the add employee request
                            var xmlhttp = new XMLHttpRequest();
                            xmlhttp.open("POST", "ajax/employees/addEmployee.php", true);
                            xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                            xmlhttp.onreadystatechange = function() 
                            {
                                if (this.readyState == 4 && this.status == 200)
                                {
                                    // create the status modal
                                    let status_title = "Add Employee Status";
                                    let status_body = this.responseText;
                                    createStatusModal("refresh", status_title, status_body);

                                    // hide the current modal
                                    $("#addEmployeeModal").modal("hide");
                                }
                            };
                            xmlhttp.send(sendString+"&period="+period);
                        }
                    <?php } ?>

                    <?php if (isset($PERMISSIONS["DELETE_EMPLOYEES"])) { ?>
                        /** function to delete the employee */
                        function deleteEmployee(id)
                        {
                            var xmlhttp = new XMLHttpRequest();
                            xmlhttp.open("POST", "ajax/employees/deleteEmployee.php", true);
                            xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                            xmlhttp.onreadystatechange = function() 
                            {
                                if (this.readyState == 4 && this.status == 200)
                                {
                                    // create the status modal
                                    let status_title = "Delete Employee Status";
                                    let status_body = this.responseText;
                                    createStatusModal("refresh", status_title, status_body);

                                    // hide the current modal
                                    $("#deleteEmployeeModal").modal("hide");
                                }
                            };
                            xmlhttp.send("employee_id="+id);
                        }

                        /** function to get the delete employee modal */
                        function getDeleteEmployeeModal(id)
                        {
                            // send the data to create the delete employee modal
                            var xmlhttp = new XMLHttpRequest();
                            xmlhttp.open("POST", "ajax/employees/getDeleteEmployeeModal.php", true);
                            xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                            xmlhttp.onreadystatechange = function() 
                            {
                                if (this.readyState == 4 && this.status == 200)
                                {
                                    // display the delete employee modal
                                    document.getElementById("delete-employee-modal-div").innerHTML = this.responseText;     
                                    $("#deleteEmployeeModal").modal("show");
                                }
                            };
                            xmlhttp.send("employee_id="+id);
                        }
                    <?php } ?>

                    <?php if (isset($PERMISSIONS["EDIT_EMPLOYEES"])) { ?>
                        /** function to edit the employee */
                        function editEmployee(id)
                        {
                            // get the fixed period name
                            let period = document.getElementById("fixed-period").value;

                            // initialize the string of data to send
                            let sendString = "";

                            // get employee information form fields
                            let fname = document.getElementById("edit-fname").value;
                            let lname = document.getElementById("edit-lname").value;
                            let email = document.getElementById("edit-email").value;
                            let phone = document.getElementById("edit-phone").value;
                            let birthday = document.getElementById("edit-birthday").value;
                            let gender = document.getElementById("edit-gender").value;
                            sendString += "employee_id="+id+"&fname="+fname+"&lname="+lname+"&email="+email+"&phone="+phone+"&birthday="+birthday+"&gender="+gender;

                            // get employee address form fields
                            let line1 = document.getElementById("edit-address_line1").value;
                            let line2 = document.getElementById("edit-address_line2").value;
                            let city = document.getElementById("edit-address_city").value;
                            let state = document.getElementById("edit-address_state").value;
                            let zip = document.getElementById("edit-address_zip").value;
                            sendString += "&line1="+line1+"&line2="+line2+"&city="+city+"&state="+state+"&zip="+zip;

                            // get employee role details form fields
                            let title = document.getElementById("edit-title").value;
                            let department = document.getElementById("edit-dept").value;
                            let supervisor = document.getElementById("edit-supervisor").value;
                            let position = document.getElementById("edit-position").value;
                            let area = document.getElementById("edit-area").value;
                            let experience = document.getElementById("edit-experience").value;
                            let experience_adjustment = document.getElementById("edit-experience_adjustment").value;
                            let degree = document.getElementById("edit-degree").value;
                            sendString += "&title="+title+"&department="+department+"&position="+position+"&area="+area+"&experience="+experience+"&degree="+degree+"&supervisor="+supervisor+"&experience_adjustment="+experience_adjustment;

                            // get employee contract details form fields
                            let hire_date = document.getElementById("edit-hire_date").value;
                            let end_date = document.getElementById("edit-end_date").value;
                            let original_hire_date = document.getElementById("edit-original_hire_date").value;
                            let original_end_date = document.getElementById("edit-original_end_date").value;
                            let contract_start_date = document.getElementById("edit-contract_start_date").value;
                            let contract_end_date = document.getElementById("edit-contract_end_date").value;
                            let contract_type = document.getElementById("edit-contract_type").value;
                            let days = document.getElementById("edit-days").value;
                            let calendar_type = document.getElementById("edit-calendar_type").value;
                            let rate = document.getElementById("edit-rate").value;
                            let num_of_pays = document.getElementById("edit-num_of_pays").value;
                            let health = document.getElementById("edit-health").value;
                            let dental = document.getElementById("edit-dental").value;
                            let wrs = document.getElementById("edit-wrs").value;
                            sendString += "&contract_type="+contract_type+"&title="+title+"&department="+department+"&days="+days+"&rate="+rate+"&health="+health+"&dental="+dental+"&wrs="+wrs+"&contract_start_date="+contract_start_date+"&contract_end_date="+contract_end_date+"&calendar_type="+calendar_type+"&num_of_pays="+num_of_pays+"&hire_date="+hire_date+"&end_date="+end_date+"&original_hire_date="+original_hire_date+"&original_end_date="+original_end_date;

                            // get account information form fields
                            let status = document.getElementById("edit-status").value;
                            let global = document.getElementById("edit-global").value;
                            sendString += "&status="+status+"&global="+global;

                            // get syncing statuses
                            let sync_demographics = document.getElementById("edit-sync-demographics").value;
                            let sync_position = document.getElementById("edit-sync-position").value;
                            let sync_contract = document.getElementById("edit-sync-contract").value;
                            sendString+="&sync_demographics="+sync_demographics+"&sync_position="+sync_position+"&sync_contract="+sync_contract;

                            var xmlhttp = new XMLHttpRequest();
                            xmlhttp.open("POST", "ajax/employees/editEmployee.php", true);
                            xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                            xmlhttp.onreadystatechange = function() 
                            {
                                if (this.readyState == 4 && this.status == 200)
                                {
                                    // create the status modal
                                    let status_title = "Edit Employee Status";
                                    let status_body = this.responseText;
                                    createStatusModal("refresh", status_title, status_body);

                                    // hide the current modal
                                    $("#editEmployeeModal").modal("hide");
                                }
                            };
                            xmlhttp.send(sendString+"&period="+period);
                        }

                        /** function to get the edit employee modal */
                        function getEditEmployeeModal(id)
                        {
                            // get the fixed period name
                            let period = document.getElementById("fixed-period").value;

                            // send the data to create the edit employee modal
                            var xmlhttp = new XMLHttpRequest();
                            xmlhttp.open("POST", "ajax/employees/getEditEmployeeModal.php", true);
                            xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                            xmlhttp.onreadystatechange = function() 
                            {
                                if (this.readyState == 4 && this.status == 200)
                                {
                                    // display the edit employee modal
                                    document.getElementById("edit-employee-modal-div").innerHTML = this.responseText;
                                    $("#editEmployeeModal").modal("show");

                                    $(function() {
                                        $("#edit-birthday").daterangepicker({
                                            singleDatePicker: true,
                                            showDropdowns: true,
                                            minYear: 1900,
                                            maxYear: <?php echo date("Y"); ?>
                                        });
                                        if ($("#edit-birthday").val() == "") { $("#edit-birthday").val(""); }

                                        $("#edit-hire_date").daterangepicker({
                                            singleDatePicker: true,
                                            showDropdowns: true,
                                            minYear: 1900,
                                            maxYear: <?php echo date("Y"); ?>
                                        });
                                        if ($("#edit-hire_date").val() == "") { $("#edit-hire_date").val(""); }

                                        $("#edit-end_date").daterangepicker({
                                            singleDatePicker: true,
                                            showDropdowns: true,
                                            minYear: 1900,
                                            maxYear: <?php echo date("Y"); ?>
                                        });
                                        if ($("#edit-end_date").val() == "") { $("#edit-end_date").val(""); }

                                        $("#edit-contract_start_date").daterangepicker({
                                            singleDatePicker: true,
                                            showDropdowns: true,
                                            minYear: 1900,
                                            maxYear: <?php echo date("Y"); ?>
                                        });
                                        if ($("#edit-contract_start_date").val() == "") { $("#edit-contract_start_date").val(""); }

                                        $("#edit-contract_end_date").daterangepicker({
                                            singleDatePicker: true,
                                            showDropdowns: true,
                                            minYear: 1900,
                                            maxYear: <?php echo date("Y"); ?>
                                        });
                                        if ($("#edit-contract_end_date").val() == "") { $("#edit-contract_end_date").val(""); }
                                    });

                                    // check for sliding within the add employees carousel
                                    $('#edit-employee-carousel').on('slide.bs.carousel', function(e) {
                                        // get the slide we are moving to
                                        let next = e.to;

                                        // set the next or previous slide to active
                                        slideTo("edit", "edit-slider-page-"+(next + 1));
                                    });
                                }
                            };
                            xmlhttp.send("employee_id="+id+"&period="+period);
                        }
                        
                        /** function to get the modal to mark employee changes */
                        function getMarkEmployeeChangesModal(id)
                        {
                            // send the data to create the edit employee modal
                            var xmlhttp = new XMLHttpRequest();
                            xmlhttp.open("POST", "ajax/employees/getMarkEmployeeChangesModal.php", true);
                            xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                            xmlhttp.onreadystatechange = function() 
                            {
                                if (this.readyState == 4 && this.status == 200)
                                {
                                    // display the edit employee modal
                                    document.getElementById("mark_changes-employee-modal-div").innerHTML = this.responseText;
                                    $("#markEmployeeChangesModal").modal("show");
                                }
                            }
                            xmlhttp.send("employee_id="+id);
                        }

                        /** function to mark employee changes */
                        function markChanges(id)
                        {
                            // get the form fields
                            let item = document.getElementById("ec-item_changed").value;
                            let init_period = document.getElementById("ec-from-period").value;
                            let change_period = document.getElementById("ec-to-period").value;
                            let notes = document.getElementById("ec-notes").value;

                            // send the data to create the edit employee modal
                            var xmlhttp = new XMLHttpRequest();
                            xmlhttp.open("POST", "ajax/employees/markEmployeeChanges.php", true);
                            xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                            xmlhttp.onreadystatechange = function() 
                            {
                                if (this.readyState == 4 && this.status == 200)
                                {
                                    // create the status modal
                                    let status_title = "Mark Employee Changes Status";
                                    let status_body = this.responseText;
                                    createStatusModal("refresh", status_title, status_body);

                                    // hide the current modal
                                    $("#markEmployeeChangesModal").modal("hide");
                                }
                            }
                            xmlhttp.send("employee_id="+id+"&item_changed="+item+"&initial_period="+init_period+"&change_period="+change_period+"&notes="+encodeURIComponent(notes));
                        }
                    <?php } ?>

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

                    /** function to update the global element */
                    function updateGlobal(id)
                    {
                        // get current status of the element
                        let element = document.getElementById(id);
                        let status = element.value;

                        if (status == 0) // currently set to no
                        {
                            // update status to yes
                            element.value = 1;
                            element.innerHTML = "Yes";
                            element.classList.remove("btn-danger");
                            element.classList.add("btn-success");
                        }
                        else // currently set to yes, or other?
                        {
                            // update status to no
                            element.value = 0;
                            element.innerHTML = "No";
                            element.classList.remove("btn-success");
                            element.classList.add("btn-danger");
                        }
                    }

                    /** function to update the access element */
                    function updateAccess(id)
                    {
                        // get current status of the element
                        let element = document.getElementById(id);
                        let status = element.value;

                        if (status == 0) // currently set to inactive
                        {
                            // update status to active
                            element.value = 1;
                            element.innerHTML = "Access Allowed";
                            element.classList.remove("btn-danger");
                            element.classList.add("btn-success");
                        }
                        else // currently set to active, or other?
                        {
                            // update status to inactive
                            element.value = 0;
                            element.innerHTML = "Access Restricted";
                            element.classList.remove("btn-success");
                            element.classList.add("btn-danger");
                        }
                    }

                    /** function to update the position area dropdown */
                    function updatePositionArea(origin, value)
                    {
                        // send the data to create the edit employee modal
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/misc/getPositionAreas.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                document.getElementById(origin+"-area").innerHTML = this.responseText;
                            }
                        };
                        xmlhttp.send("position="+value);
                    }

                    /** function to update the slider styling */
                    function slideTo(type, id)
                    {
                        // set all sliders to outline
                        document.getElementById(type+"-slider-page-1").classList.remove("btn-secondary");
                        document.getElementById(type+"-slider-page-2").classList.remove("btn-secondary");
                        document.getElementById(type+"-slider-page-3").classList.remove("btn-secondary");
                        document.getElementById(type+"-slider-page-1").classList.add("btn-outline-secondary");
                        document.getElementById(type+"-slider-page-2").classList.add("btn-outline-secondary");
                        document.getElementById(type+"-slider-page-3").classList.add("btn-outline-secondary");

                        // set the selected slider to active
                        document.getElementById(id).classList.add("btn-secondary");
                    }

                    /** function to toggle additional filters */
                    function toggleFilters(value)
                    {
                        if (value == 1) // filters are currently displayed; hide filters
                        {
                            // hide div
                            document.getElementById("showFilters").value = 0;
                            document.getElementById("showFilters-icon").innerHTML = "<i class='fa-solid fa-angle-down'></i>";
                            document.getElementById("employees-filters-div").classList.add("d-none");
                            
                            // store current status in a cookie
                            document.cookie = "BAP_EmployeesFiltersDisplayed=0; expires=Tue, 19 Jan 2038 04:14:07 GMT";
                        }
                        else // filters are currently hidden; display filters
                        {
                            // display div
                            document.getElementById("showFilters").value = 1;
                            document.getElementById("showFilters-icon").innerHTML = "<i class='fa-solid fa-angle-up'></i>";
                            document.getElementById("employees-filters-div").classList.remove("d-none");

                            // store current status in a cookie
                            document.cookie = "BAP_EmployeesFiltersDisplayed=1; expires=Tue, 19 Jan 2038 04:14:07 GMT";
                        }
                    }

                    /** function to get the modal to request employee changes */
                    function getRequestEmployeeChangesModal(id) 
                    {
                        // send the data to create the edit employee modal
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/employees/getRequestEmployeeChangesModal.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // display the edit employee modal
                                document.getElementById("request_change-employee-modal-div").innerHTML = this.responseText;
                                $("#requestEmployeeChangeModal").modal("show");
                            }
                        }
                        xmlhttp.send("employee_id="+id);
                    }

                    /** function to submit the employee change request */
                    function requestEmployeeChange(id)
                    {
                        // get the form fields
                        let period_id = document.getElementById("rc-period").value;
                        let new_days = document.getElementById("rc-new_days").value;
                        let comment = document.getElementById("rc-notes").value;

                        // send the data to create the edit employee modal
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/employees/requestEmployeeChange.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // create the status modal
                                let status_title = "Request Employee Change Status";
                                let status_body = this.responseText;
                                createStatusModal("refresh", status_title, status_body);

                                // hide the current modal
                                $("#requestEmployeeChangeModal").modal("hide");
                            }
                        }
                        xmlhttp.send("employee_id="+id+"&period_id="+period_id+"&new_days="+new_days+"&comment="+encodeURIComponent(comment));
                    }

                    <?php if (isset($PERMISSIONS["VIEW_PROJECT_BUDGETS_ALL"]) || isset($PERMISSIONS["VIEW_PROJECT_BUDGETS_ASSIGNED"])) { ?>
                        /** function to get the modal to view the projects an employee is budgeted into */
                        function getViewEmployeeProjectsModal(employee_id, period_id)
                        {
                            var xmlhttp = new XMLHttpRequest();
                            xmlhttp.open("POST", "ajax/employees/getViewEmployeeProjectsModal.php", true);
                            xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                            xmlhttp.onreadystatechange = function() 
                            {
                                if (this.readyState == 4 && this.status == 200)
                                {
                                    document.getElementById("employee_projects-modal-div").innerHTML = this.responseText;
                                    $("#employeeProjectsModal").modal("show");
                                }
                            }
                            xmlhttp.send("employee_id="+employee_id+"&period_id="+period_id);
                        }
                    <?php } ?>

                    /** function to estimate an employee's yearly salary based on day change */
                    function estimateYearlySalary(employee_id)
                    {
                        // get form fields
                        let days = document.getElementById("rc-new_days").value;
                        let period_id = document.getElementById("rc-period").value;

                        // send request
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/employees/estimateYearlySalary.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // update estimated salary field
                                document.getElementById("rc-estimated_salary").value = this.responseText;
                            }
                        }
                        xmlhttp.send("employee_id="+employee_id+"&period_id="+period_id+"&days="+days);
                    }

                    /** function to get an employee's compensation when requesting an employee change */
                    function getEmployeeCompensation(employee_id)
                    {
                        // get period from form field
                        let period_id = document.getElementById("rc-period").value;

                        // send request
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/employees/getEmployeeCompensation.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // store employee compensation locally
                                let comp = JSON.parse(this.responseText);
                                let salary = comp["salary"];
                                let days = comp["days"];
                                let daily = comp["daily"];

                                // update fields to compensation for the period selected
                                document.getElementById("rc-current_days").value = days;
                                document.getElementById("rc-current_salary").value = salary;
                                document.getElementById("rc-daily_rate").value = daily;
                            }
                        }
                        xmlhttp.send("employee_id="+employee_id+"&period_id="+period_id);
                    }

                    /** function to toggle the syncing of a category */
                    function toggleSync(origin, category)
                    {
                        // store element
                        let element = document.getElementById(origin+"-sync-"+category);

                        // if syncing is enabled
                        if (element.value == 1)
                        {
                            element.classList.remove("btn-success");
                            element.classList.add("btn-danger");
                            element.value = 0;
                        }
                        // if snycing is not enabled
                        else
                        {
                            element.classList.remove("btn-danger");
                            element.classList.add("btn-success");
                            element.value = 1;
                        }
                    }
                </script>

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
                                                <select class="form-select" id="search-period" name="search-period" onchange="showEmployees();">
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

                                                    <!-- Filter By Status -->
                                                    <div class="row d-flex justify-content-between align-items-center mx-0 mt-0 mb-2">
                                                        <div class="col-4 ps-0 pe-1">
                                                            <label for="search-status">Status:</label>
                                                        </div>

                                                        <div class="col-8 ps-1 pe-0">
                                                            <select class="form-select w-100" id="search-status" name="search-status">
                                                                <option value="">Show All</option>
                                                                <option value="Active" style="background-color: #006900; color: #ffffff;" selected>Active</option>
                                                                <option value="Inactive" style="background-color: #e40000; color: #ffffff;">Inactive</option>
                                                            </select>
                                                        </div>
                                                    </div>

                                                    <?php if (isset($PERMISSIONS["VIEW_EMPLOYEES_ALL"])) { ?>
                                                        <!-- Filter By Role -->
                                                        <div class="row d-flex justify-content-between align-items-center mx-0 mt-0 mb-2">
                                                            <div class="col-4 ps-0 pe-1">
                                                                <label for="search-customers">Role:</label>
                                                            </div>

                                                            <div class="col-8 ps-1 pe-0">
                                                                <select class="form-select" id="search-role" name="search-role">
                                                                    <option></option>
                                                                    <?php
                                                                        // create the role selection dropdown options
                                                                        $getRoles = mysqli_query($conn, "SELECT * FROM roles ORDER BY default_generated DESC, name ASC");
                                                                        if (mysqli_num_rows($getRoles) > 0) // roles found
                                                                        {
                                                                            while ($role_details = mysqli_fetch_array($getRoles))
                                                                            {
                                                                                // store role details locally
                                                                                $role_id = $role_details["id"];
                                                                                $role_name = $role_details["name"];
                                                                                $default_generated = $role_details["default_generated"];

                                                                                // create the option (bold option if it is a default role)
                                                                                if ($default_generated == 1) { echo "<option class='fw-bold'>".$role_name."</option>"; }
                                                                                else { echo "<option>".$role_name."</option>"; }
                                                                            }
                                                                        }
                                                                    ?>
                                                                </select>
                                                            </div>
                                                        </div>
                                                    <?php } ?>

                                                    <!-- Filter By Department -->
                                                    <div class="row d-flex justify-content-between align-items-center mx-0 mt-0 mb-2">
                                                        <div class="col-4 ps-0 pe-1">
                                                            <label for="search-dept">Department:</label>
                                                        </div>

                                                        <div class="col-8 ps-1 pe-0">
                                                            <select class="form-select w-100" id="search-dept" name="search-dept">
                                                                <option></option>
                                                                <option>No primary department assigned</option>
                                                                <?php
                                                                    $getDepts = mysqli_query($conn, "SELECT id, name FROM departments ORDER BY name ASC");
                                                                    if (mysqli_num_rows($getDepts) > 0) // departments found
                                                                    {
                                                                        while ($dept = mysqli_fetch_array($getDepts))
                                                                        {
                                                                            echo "<option>".$dept["name"]."</option>";
                                                                        }
                                                                    }
                                                                ?>
                                                            </select>
                                                        </div>
                                                    </div>

                                                    <?php if (isset($PERMISSIONS["VIEW_EMPLOYEES_ALL"])) { ?>
                                                        <!-- Filter By Supervisor -->
                                                        <div class="row d-flex justify-content-between align-items-center mx-0 mt-0 mb-2">
                                                            <div class="col-4 ps-0 pe-1">
                                                                <label for="search-supervisor">Supervisor:</label>
                                                            </div>

                                                            <div class="col-8 ps-1 pe-0">
                                                                <select class="form-select w-100" id="search-supervisor" name="search-supervisor">
                                                                    <option></option>
                                                                    <option>No supervisor assigned</option>
                                                                    <?php 
                                                                        $getSupervisors = mysqli_query($conn, "SELECT DISTINCT d.user_id, u.lname, u.fname FROM directors d
                                                                                                                JOIN users u ON d.user_id=u.id
                                                                                                                ORDER BY u.lname ASC, u.fname ASC");
                                                                        while ($supervisor = mysqli_fetch_array($getSupervisors))
                                                                        {
                                                                            // store supervisor details locally
                                                                            $supervisor_id = $supervisor["user_id"];
                                                                            
                                                                            // get supervisor name
                                                                            $supervisor_name = getUserDisplayName($conn, $supervisor_id);

                                                                            // build the option
                                                                            echo "<option value='".$supervisor_name."'>".$supervisor_name."</option>";
                                                                        }
                                                                    ?>
                                                                </select>
                                                            </div>
                                                        </div>

                                                        <!-- Filter By Number Of Pays -->
                                                        <div class="row d-flex justify-content-between align-items-center mx-0 mt-0 mb-2">
                                                            <div class="col-4 ps-0 pe-1">
                                                                <label for="search-num_of_pays"># of Pays:</label>
                                                            </div>

                                                            <div class="col-8 ps-1 pe-0">
                                                                <select class="form-select" id="search-num_of_pays" name="search-num_of_pays">
                                                                    <option></option>
                                                                    <?php
                                                                        $getNumberOfPays = mysqli_query($conn, "SELECT DISTINCT number_of_pays FROM employee_compensation ORDER BY number_of_pays ASC");
                                                                        if (mysqli_num_rows($getNumberOfPays) > 0)
                                                                        {
                                                                            while ($result = mysqli_fetch_array($getNumberOfPays))
                                                                            {
                                                                                echo "<option>".$result["number_of_pays"]."</option>";
                                                                            }
                                                                        }
                                                                    ?>
                                                                </select>
                                                            </div>
                                                        </div>
                                                    <?php } ?>

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
                                    <h2 class="m-0">Employees</h2>
                                </div>

                                <!-- Page Management Dropdown -->
                                <div class="col-6 col-sm-4 col-md-4 col-lg-3 col-xl-2 col-xxl-2 d-flex justify-content-end p-0">
                                    <div id="colVis-buttons" class="mx-1"></div>

                                    <?php if ($_SESSION["role"] == 1 || isset($PERMISSIONS["ADD_EMPLOYEES"])) { ?>
                                        <div class="dropdown mx-1">
                                            <button class="btn btn-primary dropdown-toggle" type="button" id="dropdownMenuButton1" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                                                Manage Employees
                                            </button>
                                            <ul class="dropdown-menu p-0" aria-labelledby="dropdownMenuButton1">
                                                <?php if (isset($PERMISSIONS["ADD_EMPLOYEES"])) { ?>
                                                    <li><button class="dropdown-item quickNav-dropdown-item text-center w-100 px-3 py-2 rounded-0" type="button" data-bs-toggle="modal" data-bs-target="#addEmployeeModal">Add Employee</button></li>
                                                    <li><button class="dropdown-item quickNav-dropdown-item text-center w-100 px-3 py-2 rounded-0" type="button" data-bs-toggle="modal" data-bs-target="#uploadEmployeesModal">Upload Employees</button></li>
                                                <?php }
                                                if ($_SESSION["role"] == 1) { // ADMINISTRATOR ONLY ?>
                                                    <li><button class="dropdown-item quickNav-dropdown-item text-center w-100 px-3 py-2 rounded-0" type="button" data-bs-toggle="modal" data-bs-target="#employeesRaiseModal">Mass Raise Salary</button></li>
                                                    <li><button class="dropdown-item quickNav-dropdown-item text-center w-100 px-3 py-2 rounded-0" type="button" data-bs-toggle="modal" data-bs-target="#employeesExperienceModal">Add Year Of Experience</button></li>
                                                    <li class="dropdown w-100">
                                                        <button class="dropdown-item quickNav-dropdown-item text-center w-100 px-3 py-2 rounded-0 dropdown-toggle" id="exportsMenu" type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                                                            Exports
                                                        </button>
                                                        <ul class="quickNav-dropdown dropdown-menu p-0" aria-labelledby="exportsMenu">
                                                            <li id="list_csv-export-div"></li>
                                                            <li id="list_xlsx-export-div"></li>
                                                            <li id="TalentEd-export-div"></li>
                                                            <li id="TalentEd-verification-export-div"></li>
                                                        </ul>
                                                    </li>
                                                <?php } ?>
                                            </ul>
                                        </div>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>

                        <table id="employees" class="report_table w-100">
                            <thead>
                                <tr>
                                    <th class="text-center py-1 px-2" colspan="7">Demographics</th>
                                    <th class="text-center py-1 px-2" colspan="14">Benefits & Compensation</th>
                                    <th class="text-center py-1 px-2" rowspan="3">Actions</th>
                                    <?php if ($_SESSION["role"] == 1) { ?>
                                        <!-- DataTables Export Column Headers -->
                                        <th class="text-center py-1 px-2" rowspan="3">Role</th>
                                        <th class="text-center py-1 px-2" rowspan="3">Employee ID</th>
                                        <th class="text-center py-1 px-2" rowspan="3">Date Of Birth</th>
                                        <th class="text-center py-1 px-2" rowspan="3">Email</th>
                                        <th class="text-center py-1 px-2" rowspan="3">Phone</th>
                                        <th class="text-center py-1 px-2" rowspan="3">Gender</th>
                                        <th class="text-center py-1 px-2" rowspan="3">Address Line 1</th>
                                        <th class="text-center py-1 px-2" rowspan="3">Address Line 2</th>
                                        <th class="text-center py-1 px-2" rowspan="3">City</th>
                                        <th class="text-center py-1 px-2" rowspan="3">State</th>
                                        <th class="text-center py-1 px-2" rowspan="3">Zip</th>
                                        <th class="text-center py-1 px-2" rowspan="3">Hire Date</th>
                                        <th class="text-center py-1 px-2" rowspan="3">End Date</th>
                                        <th class="text-center py-1 px-2" rowspan="3">Contract Start Date</th>
                                        <th class="text-center py-1 px-2" rowspan="3">Contract End Date</th>
                                        <th class="text-center py-1 px-2" rowspan="3">Contract Type</th>
                                        <th class="text-center py-1 px-2" rowspan="3">Calendar Type</th>
                                        <th class="text-center py-1 px-2" rowspan="3">Yearly Rate</th>
                                        <th class="text-center py-1 px-2" rowspan="3">Number Of Pays</th>
                                        <th class="text-center py-1 px-2" rowspan="3">Health</th>
                                        <th class="text-center py-1 px-2" rowspan="3">Dental</th>
                                        <th class="text-center py-1 px-2" rowspan="3">WRS Eligible</th>
                                        <th class="text-center py-1 px-2" rowspan="3">Title</th>
                                        <th class="text-center py-1 px-2" rowspan="3">Primary Department</th>
                                        <th class="text-center py-1 px-2" rowspan="3">Supervisor</th>
                                        <th class="text-center py-1 px-2" rowspan="3">DPI Assignment Position</th>
                                        <th class="text-center py-1 px-2" rowspan="3">DPI Assignment Area</th>
                                        <th class="text-center py-1 px-2" rowspan="3">Years Of Total Experience</th>
                                        <th class="text-center py-1 px-2" rowspan="3">Highest Degree Obtained</th>
                                        <th class="text-center py-1 px-2" rowspan="3">Status</th>
                                        <th class="text-center py-1 px-2" rowspan="3">Projected Days</th>
                                        <th class="text-center py-1 px-2" rowspan="3">Projected Yearly Rate</th>
                                        <th class="text-center py-1 px-2" rowspan="3">Projected Rate Increase (%)</th>
                                        <th class="text-center py-1 px-2" rowspan="3">Hourly Rate</th>
                                    <?php } ?>
                                </tr>

                                <tr>
                                    <!-- Demographics -->
                                    <th class="text-center py-1 px-2" rowspan="2">ID</th>
                                    <th class="text-center py-1 px-2" rowspan="2">Last Name</th>
                                    <th class="text-center py-1 px-2" rowspan="2">First Name</th>
                                    <th class="text-center py-1 px-2" rowspan="2">Date Of Birth</th>
                                    <th class="text-center py-1 px-2" rowspan="2">Contact Info</th>
                                    <th class="text-center py-1 px-2" rowspan="2">Address</th>
                                    <th class="text-center py-1 px-2" rowspan="2">Position</th>

                                    <!-- Benefits & Compensation -->
                                    <th class="text-center py-1 px-2" rowspan="2">Days</th>
                                    <th class="text-center py-1 px-2" colspan="3">Salary</th>
                                    <th class="text-center py-1 px-2" rowspan="2">Benefits</th>
                                    <th class="text-center py-1 px-2" colspan="9">Benefit Costs</th>
                                </tr>

                                <tr>
                                    <!-- Salary Breakdown -->
                                    <th class="text-center py-1 px-2">Yearly</th>
                                    <th class="text-center py-1 px-2">Daily</th>
                                    <th class="text-center py-1 px-2">Hourly</th>

                                    <!-- Benefits Breakdown -->
                                    <th class="text-center py-1 px-2">Health</th>
                                    <th class="text-center py-1 px-2">Dental</th>
                                    <th class="text-center py-1 px-2">WRS</th>
                                    <th class="text-center py-1 px-2">FICA</th>
                                    <th class="text-center py-1 px-2">Life</th>
                                    <th class="text-center py-1 px-2">LTD</th>
                                    <th class="text-center py-1 px-2">Total Fringe</th>
                                    <th class="text-center py-1 px-2">Total Compensation</th>
                                    <th class="text-center py-1 px-2">Daily Compensation</th>
                                </tr>
                            </thead>
                        </table>
                        <?php createTableFooterV2("employees", "BAP_Employees_PageLength", $USER_SETTINGS["page_length"], true, true); ?>
                    </div>
                </div>

                <!--
                    ### MODALS ###
                -->
                <!-- Add Employee Modal -->
                <div class="modal fade" tabindex="-1" role="dialog" id="addEmployeeModal" data-bs-backdrop="static" aria-labelledby="addEmployeeModalLabel" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header primary-modal-header">
                                <h5 class="modal-title primary-modal-title" id="addEmployeeModalLabel">Add Employee</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>

                            <div class="modal-body">
                                <div class="d-flex justify-content-between align-items-center align-middle">
                                    <!-- Previous Slide -->
                                    <button class="btn btn-primary" type="button" data-bs-target="#add-employee-carousel" data-bs-slide="prev"><i class="fa-solid fa-angles-left fa-xl"></i></button>

                                    <!-- Page 1 -->
                                    <button class="btn btn-secondary btn-carousel-slider" id="add-slider-page-1" data-bs-target="#add-employee-carousel" data-bs-slide-to="0" aria-label="1. Employee Demographics" onclick="slideTo('add', 'add-slider-page-1');"><span aria-hidden="true">O</span></button>

                                    <!-- Page 2 -->
                                    <button class="btn btn-outline-secondary btn-carousel-slider" id="add-slider-page-2" data-bs-target="#add-employee-carousel" data-bs-slide-to="1" aria-label="2. Employee Position" onclick="slideTo('add', 'add-slider-page-2');"><span aria-hidden="true">O</span></button>

                                    <!-- Page 3 -->
                                    <button class="btn btn-outline-secondary btn-carousel-slider" id="add-slider-page-3" type="button" data-bs-target="#add-employee-carousel" data-bs-slide-to="2" aria-label="3. Employee Contract" onclick="slideTo('add', 'add-slider-page-3');"><span aria-hidden="true">O</span></button>

                                    <!-- Next Slide -->
                                    <button class="btn btn-primary" type="button" data-bs-target="#add-employee-carousel" data-bs-slide="next"><i class="fa-solid fa-angles-right fa-xl"></i></button>
                                </div>

                                <div id="add-employee-carousel" class="carousel carousel-dark slide" data-bs-ride="carousel" data-bs-interval="false">
                                    <div class="carousel-inner">
                                        <div class="carousel-item active" data-bs-interval="false">
                                            <h3 class="d-flex justify-content-between align-items-center my-3 px-3">
                                                Employee Demographics
                                                <button class="btn btn-success btn-sm float-end" id="add-sync-demographics" value="1" onclick="toggleSync('add', 'demographics');" title="Sync employee demographics?"><i class="fa-solid fa-rotate"></i></button>
                                            </h3>

                                            <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                <div class="form-group col-11">
                                                    <label for="add-id"><span class="required-field">*</span> Employee ID</label>
                                                    <input type="text" class="form-control w-100" id="add-id" name="add-id" required>
                                                </div>
                                            </div>

                                            <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                <!-- First Name -->
                                                <div class="form-group col-5">
                                                    <label for="add-fname"><span class="required-field">*</span> First Name:</label>
                                                    <input type="text" class="form-control w-100" id="add-fname" name="add-fname" required>
                                                </div>

                                                <!-- Divider -->
                                                <div class="form-group col-1 p-0"></div>

                                                <!-- Last Name -->
                                                <div class="form-group col-5">
                                                    <label for="add-lname"><span class="required-field">*</span> Last Name:</label>
                                                    <input type="text" class="form-control w-100" id="add-lname" name="add-lname" required>
                                                </div>
                                            </div>

                                            <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                <!-- Email -->
                                                <div class="form-group col-11">
                                                    <label for="add-email"><span class="required-field">*</span> Email:</label>
                                                    <input type="text" class="form-control w-100" id="add-email" name="add-email" required>
                                                </div>
                                            </div>

                                            <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                <!-- Phone -->
                                                <div class="form-group col-11">
                                                    <label for="add-phone"><span class="required-field">*</span> Phone:</label>
                                                    <input type="text" class="form-control w-100" id="add-phone" name="add-phone" required>
                                                </div>
                                            </div>

                                            <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                <!-- Date Of Birth -->
                                                <div class="form-group col-11">
                                                    <label for="add-birthday"><span class="required-field">*</span> Date Of Birth:</label>
                                                    <input type="text" class="form-control w-100" id="add-birthday" name="add-birthday" required>
                                                </div>
                                            </div>

                                            <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                <!-- Gender -->
                                                <div class="form-group col-11">
                                                    <label for="add-gender"><span class="required-field">*</span> Gender:</label>
                                                    <select class="form-select w-100" id="add-gender" name="add-gender" required>
                                                        <option value=1>Male</option>
                                                        <option value=2>Female</option>
                                                        <option value=0>Other / Unknown</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                <!-- Line 1 -->
                                                <div class="form-group col-11">
                                                    <label for="add-address_line1"><span class="required-field">*</span> Address Line 1 (Street/P.O. Box):</label>
                                                    <input type="text" class="form-control w-100" id="add-address_line1" name="add-address_line1" required>
                                                </div>
                                            </div>

                                            <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                <!-- Line 2 -->
                                                <div class="form-group col-11">
                                                    <label for="add-address_line2">Address Line 2 (Apt/Suite/Unit #):</label>
                                                    <input type="text" class="form-control w-100" id="add-address_line2" name="add-address_line2">
                                                </div>
                                            </div>

                                            <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                <!-- City -->
                                                <div class="form-group col-11">
                                                    <label for="add-address_city"><span class="required-field">*</span> City:</label>
                                                    <input type="text" class="form-control w-100" id="add-address_city" name="add-address_city" required>
                                                </div>
                                            </div>

                                            <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                <!-- State -->
                                                <div class="form-group col-6">
                                                    <label for="add-address_state"><span class="required-field">*</span> State:</label>
                                                    <select class="form-select w-100" id="add-address_state" name="add-address_state" required>
                                                        <option value=0></option>
                                                        <?php
                                                            $getStates = mysqli_query($conn, "SELECT id, state FROM states");
                                                            while ($state = mysqli_fetch_array($getStates)) 
                                                            { 
                                                                if ($state["state"] == "Wisconsin") { echo "<option value='".$state["id"]."' selected>".$state["state"]."</option>"; }
                                                                else { echo "<option value='".$state["id"]."'>".$state["state"]."</option>"; }
                                                            }
                                                        ?>
                                                    </select> 
                                                </div>

                                                <!-- Spacer -->
                                                <div class="form-group col-1"></div>

                                                <!-- Zip -->
                                                <div class="form-group col-4">
                                                    <label for="add-address_zip"><span class="required-field">*</span> Zip Code:</label>
                                                    <input type="text" class="form-control w-100" id="add-address_zip" name="add-address_zip" required>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="carousel-item" data-bs-interval="false">
                                            <h3 class="d-flex justify-content-between align-items-center my-3 px-3">
                                                Employee Position
                                                <button class="btn btn-success btn-sm float-end" id="add-sync-position" value="1" onclick="toggleSync('add', 'position');" title="Sync employee position?"><i class="fa-solid fa-rotate"></i></button>
                                            </h3>

                                            <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                <!-- Title -->
                                                <div class="form-group col-11">
                                                    <label for="add-title"><span class="required-field">*</span> Title:</label>
                                                    <select class="form-select w-100" id="add-title" name="add-title" required>
                                                        <option></option>
                                                        <?php
                                                            $getTitles = mysqli_query($conn, "SELECT * FROM employee_titles ORDER BY name ASC");
                                                            if (mysqli_num_rows($getTitles) > 0)
                                                            {
                                                                while ($title = mysqli_fetch_array($getTitles))
                                                                {
                                                                    // store title details locally
                                                                    $title_id = $title["id"];
                                                                    $title_name = $title["name"];

                                                                    // build dropdown option
                                                                    echo "<option value='".$title_id."'>".$title_name."</option>";
                                                                }
                                                            }
                                                        ?>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                <!-- Primary Department -->
                                                <div class="form-group col-11">
                                                    <label for="add-dept">Primary Department:</label>
                                                    <select class="form-select w-100" id="add-dept" name="add-dept">
                                                        <option></option>
                                                        <?php 
                                                            $getDepartments = mysqli_query($conn, "SELECT id, name FROM departments");
                                                            while ($department = mysqli_fetch_array($getDepartments))
                                                            {
                                                                if (isset($department["name"]) && ($department["name"] != null && $department["name"] <> ""))
                                                                {
                                                                    echo "<option value='".$department["id"]."'>".$department["name"]."</option>";
                                                                }
                                                            }
                                                        ?>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                <!-- Supervisor -->
                                                <div class="form-group col-11">
                                                    <label for="add-supervisor">Supervisor:</label>
                                                    <select class="form-select w-100" id="add-supervisor" name="add-supervisor">
                                                        <option></option>
                                                        <?php 
                                                            $getSupervisors = mysqli_query($conn, "SELECT DISTINCT d.user_id, u.lname, u.fname FROM directors d
                                                                                                    JOIN users u ON d.user_id=u.id
                                                                                                    ORDER BY u.lname ASC, u.fname ASC");
                                                            while ($supervisor = mysqli_fetch_array($getSupervisors))
                                                            {
                                                                // store supervisor details locally
                                                                $supervisor_id = $supervisor["user_id"];
                                                                
                                                                // get supervisor name
                                                                $supervisor_name = getUserDisplayName($conn, $supervisor_id);

                                                                // build the option
                                                                echo "<option value='".$supervisor_id."'>".$supervisor_name."</option>";
                                                            }
                                                        ?>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                <!-- Hire Date -->
                                                <div class="form-group col-5">
                                                    <label for="add-hire_date"><span class="required-field">*</span> Most Recent Hire Date:</label>
                                                    <input type="text" class="form-control w-100" id="add-hire_date" name="add-hire_date" autocomplete="off" required>
                                                </div>

                                                <!-- Spacer -->
                                                <div class="form-group col-1"></div>

                                                <!-- End Date -->
                                                <div class="form-group col-5">
                                                    <label for="add-end_date">Most Recent End Date:</label>
                                                    <input type="text" class="form-control w-100" id="add-end_date" name="add-end_date" autocomplete="off">
                                                </div>
                                            </div>

                                            <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                <!-- Hire Date -->
                                                <div class="form-group col-5">
                                                    <label for="add-original_hire_date"><span class="required-field">*</span> Original Hire Date:</label>
                                                    <input type="text" class="form-control w-100" id="add-original_hire_date" name="add-original_hire_date" autocomplete="off" required>
                                                </div>

                                                <!-- Spacer -->
                                                <div class="form-group col-1"></div>

                                                <!-- End Date -->
                                                <div class="form-group col-5">
                                                    <label for="add-original_end_date">Original End Date:</label>
                                                    <input type="text" class="form-control w-100" id="add-original_end_date" name="add-original_end_date" autocomplete="off">
                                                </div>
                                            </div>

                                            <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                <!-- Total Years Of Experience -->
                                                <div class="form-group col-7">
                                                    <label for="add-experience"><span class="required-field">*</span> Total Years Of Experience:</label>
                                                    <input type="number" class="form-control w-100" id="add-experience" name="add-experience" required>
                                                </div>

                                                <!-- Spacer -->
                                                <div class="form-group col-1"></div>

                                                <!-- Local Years Of Experience Adjustment -->
                                                <div class="form-group col-3">
                                                    <label for="add-experience_adjustment">Local +/-</label>
                                                    <input type="number" class="form-control w-100" id="add-experience_adjustment" name="add-experience_adjustment" min="0">
                                                </div>
                                            </div>

                                            <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                <!-- Assignment Position -->
                                                <div class="form-group col-11">
                                                    <label for="add-position"><span class="required-field">*</span> Assignment Position:</label>
                                                    <select class="form-select w-100" id="add-position" name="add-position" onchange="updatePositionArea('add', this.value);" required>
                                                        <option></option>
                                                        <?php
                                                            $positions = getDPIPositions($conn);
                                                            for ($p = 0; $p < count($positions); $p++)
                                                            {
                                                                echo "<option value='".$positions[$p]["position_code"]."'>".$positions[$p]["position_code"]." - ".$positions[$p]["position_name"]."</option>";
                                                            }
                                                        ?>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                <!-- Position Area -->
                                                <div class="form-group col-11">
                                                    <label for="add-area"><span class="required-field">*</span> Subcategory (Assignment Position):</label>
                                                    <select class="form-select w-100" id="add-area" name="add-area" required>
                                                        <option></option>
                                                    </select>
                                                </div>
                                            </div>


                                            <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                <!-- Highest Degree -->
                                                <div class="form-group col-11">
                                                    <label for="add-title"><span class="required-field">*</span> Highest Degree:</label>
                                                    <select class="form-select w-100" id="add-degree" name="add-degree" required>
                                                        <option></option>
                                                        <?php
                                                            $degrees = getDegrees($conn);
                                                            for ($d = 0; $d < count($degrees); $d++)
                                                            {
                                                                echo "<option>".$degrees[$d]["code"]." - ".$degrees[$d]["label"]."</option>";
                                                            }
                                                        ?>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                <!-- Global Employee -->
                                                <div class="form-group col-11">
                                                    <label for="add-global"><span class="required-field">*</span> Global Employee:</label>
                                                    <button class="btn btn-danger w-100" id="add-global" name="add-global" value=0 onclick="updateStatus('add-global');">No</button>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="carousel-item" data-bs-interval="false">
                                            <h3 class="d-flex justify-content-between align-items-center my-3 px-3">
                                                Employee Contract
                                                <button class="btn btn-success btn-sm float-end" id="add-sync-contract" value="1" onclick="toggleSync('add', 'contract');" title="Sync employee contract details?"><i class="fa-solid fa-rotate"></i></button>
                                            </h3>

                                            <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                <!-- Start Date -->
                                                <div class="form-group col-5">
                                                    <label for="add-contract_start_date"><span class="required-field">*</span> Contract Start Date:</label>
                                                    <input type="text" class="form-control w-100" id="add-contract_start_date" name="add-contract_start_date" value="<?php echo $active_start_date; ?>" autocomplete="off" required>
                                                </div>

                                                <!-- Spacer -->
                                                <div class="form-group col-1"></div>

                                                <!-- End Date -->
                                                <div class="form-group col-5">
                                                    <label for="add-contract_end_date"><span class="required-field">*</span> Contract End Date:</label>
                                                    <input type="text" class="form-control w-100" id="add-contract_end_date" name="add-contract_end_date" value="<?php echo $active_end_date; ?>" autocomplete="off" required>
                                                </div>
                                            </div>
                                            
                                            <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                <!-- Contract Type -->
                                                <div class="form-group col-11">
                                                    <label for="add-contract_type"><span class="required-field">*</span> Contract Type:</label>
                                                    <select class="form-select w-100" id="add-contract_type" name="add-contract_type" required>
                                                        <option value=0>Regular</option>
                                                        <option value=1>Limited</option>
                                                        <option value=2>At-Will</option>
                                                        <option value=3>Section 118</option>
                                                        <option value=4>Hourly</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                <!-- Contract Days -->
                                                <div class="form-group col-11">
                                                    <label for="add-days"><span class="required-field">*</span> Contact Days:</label>
                                                    <input type="number" min="0" max="365" class="form-control w-100" id="add-days" name="add-days" value="0" required>
                                                </div>
                                            </div>

                                            <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                <!-- Calendar Type -->
                                                <div class="form-group col-11">
                                                    <label for="add-calendar_type"><span class="required-field">*</span> Calendar Type:</label>
                                                    <select class="form-select w-100" id="add-calendar_type" name="add-calendar_type" required>
                                                        <option value=0>N/A</option>
                                                        <option value=1>Hourly</option>
                                                        <option value=2>Salary</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                <!-- Yearly Salary -->
                                                <div class="form-group col-11">
                                                    <label for="add-rate"><span class="required-field">*</span> Yearly Rate:</label>
                                                    <input type="number" min="0.00" class="form-control w-100" id="add-rate" name="add-rate" value="0.00" required>
                                                </div>
                                            </div>

                                            <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                <!-- Number Of Pays -->
                                                <div class="form-group col-11">
                                                    <label for="add-num_of_pays"><span class="required-field">*</span> Number Of Pays:</label>
                                                    <input type="number" min="0" max="365" class="form-control w-100" id="add-num_of_pays" name="add-num_of_pays" value="0" required>
                                                </div>
                                            </div>

                                            <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                <!-- Health Coverage -->
                                                <div class="form-group col-11">
                                                    <label for="add-health"><span class="required-field">*</span> Health Coverage:</label>
                                                    <select class="form-select w-100" id="add-health" name="add-health" required>
                                                        <option value=0 selected>None</option>
                                                        <option value=2>Single</option>
                                                        <option value=1>Family</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                <!-- Dental Coverage -->
                                                <div class="form-group col-11">
                                                    <label for="add-dental"><span class="required-field">*</span> Dental Coverage:</label>
                                                    <select class="form-select w-100" id="add-dental" name="add-dental" required>
                                                        <option value=0 selected>None</option>
                                                        <option value=2>Single</option>
                                                        <option value=1>Family</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                <!-- WRS Eligibility -->
                                                <div class="form-group col-11">
                                                    <label for="add-wrs"><span class="required-field">*</span> WRS Eligible:</label>
                                                    <select class="form-select w-100" id="add-wrs" name="add-wrs" required>
                                                        <option value=0 selected>No</option>
                                                        <option value=1>Yes</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                <!-- Employee Status -->
                                                <div class="form-group col-11">
                                                    <label for="add-status"><span class="required-field">*</span> Status:</label>
                                                    <button class="btn btn-success w-100" id="add-status" name="add-status" value=1 onclick="updateStatus('add-status');" aria-describedby="statusHelpBlock">Active</button>
                                                    <div id="statusHelpBlock" class="form-text">
                                                        Employee status is on a per-period basis.
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Required Field Indicator -->
                                <div class="row justify-content-center">
                                    <div class="col-11 text-center fst-italic">
                                        <span class="required-field">*</span> indicates a required field
                                    </div>
                                </div>
                            </div>

                            <div class="modal-footer">
                                <button type="button" class="btn btn-primary" onclick="addEmployee();"><i class="fa-solid fa-floppy-disk"></i> Save New Employee</button>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> Close</button>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- End Add Employee Modal -->

                <!-- Upload Employees Modal -->
                <div class="modal fade" tabindex="-1" role="dialog" id="uploadEmployeesModal" data-bs-backdrop="static" aria-labelledby="uploadEmployeesModalLabel" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header primary-modal-header">
                                <h5 class="modal-title primary-modal-title" id="uploadEmployeesModalLabel">Upload Employees</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>

                            <form action="processUploadEmployees.php" method="POST" enctype="multipart/form-data">
                                <div class="modal-body">
                                    <div class="alert alert-danger">
                                        <i class="fa-solid fa-triangle-exclamation"></i> uploading employees will add and edit employees for the <b>current active period</b>.
                                    </div>

                                    <p><label for="fileToUpload">Select a CSV file following the <a class="template-link" href="https://docs.google.com/spreadsheets/d/1wnwv8QqX0cExA4zl5zS1EG7-QIYuFQm8PhzbT-oNYpA/copy" target="_blank">correct upload template</a> to upload...</label></p>
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
                <!-- End Upload Employees Modal -->

                <?php if ($_SESSION["role"] == 1) { // ADMINISTRATOR ONLY ?>
                <!-- Employees Experience Modal -->
                <div class="modal fade" tabindex="-1" role="dialog" id="employeesExperienceModal" data-bs-backdrop="static" aria-labelledby="employeesExperienceModalLabel" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header primary-modal-header">
                                <h5 class="modal-title primary-modal-title" id="employeesExperienceModalLabel">Employees Experience</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>

                            <div class="modal-body">
                                <!-- Period Selection -->
                                <div class="form-group col-12">
                                    <label for="ee-period"><span class="required-field">*</span> Select a period in which to add a year of experience in:</label>
                                    <select class="form-select font-awesome" id="ee-period" name="ee-period" aria-describedby="periodHelp" required>
                                        <option></option>
                                        <?php
                                            // create a list of all periods
                                            $getPeriods = mysqli_query($conn, "SELECT id, name, active FROM periods ORDER BY active DESC, start_date ASC");
                                            if (mysqli_num_rows($getPeriods) > 0) // periods found; continue
                                            {
                                                while ($period = mysqli_fetch_array($getPeriods))
                                                {
                                                    // store period details locally
                                                    $period_id = $period["id"];
                                                    $period_name = $period["name"];
                                                    $period_active = $period["active"];

                                                    if ($period_active == 1) { echo "<option value='".$period["id"]."' selected>★ ".$period["name"]."</option>"; }
                                                    else { echo "<option value='".$period["id"]."'>".$period["name"]."</option>"; }
                                                }
                                            }
                                        ?>
                                    </select>
                                    <p class="form-text m-0" id="periodHelp">★ indicates the current active period</p>
                                </div>
                            </div>

                            <div class="modal-footer">
                                <button type="submit" class="btn btn-primary" onclick="addYearOfExperience();"><i class="fa-solid fa-calendar-plus"></i> Add Year</button>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> Close</button>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- End Employees Experience Modal -->

                <!-- Employees Raise Modal -->
                <div class="modal fade" tabindex="-1" role="dialog" id="employeesRaiseModal" data-bs-backdrop="static" aria-labelledby="employeesRaiseModalLabel" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header primary-modal-header">
                                <h5 class="modal-title primary-modal-title" id="employeesRaiseModalLabel">Mass Employees Raise</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>

                            <div class="modal-body">
                                <div class="alert alert-warning text-center p-2" role="alert">
                                    <i class="fa-solid fa-triangle-exclamation"></i> We will only raise salaries for <b>active</b> employees!
                                </div>

                                <!-- Period Selection -->
                                <div class="form-row d-flex justify-content-center align-items-center">
                                    <!-- Base Period -->
                                    <div class="form-group col-5">
                                        <label for="er-base-period"><span class="required-field">*</span> Salary Base Period:</label>
                                        <select class="form-select font-awesome" id="er-base-period" name="er-base-period" aria-describedby="periodHelp" required>
                                            <option></option>
                                            <?php
                                                // create a list of all periods
                                                $getPeriods = mysqli_query($conn, "SELECT id, name, active FROM periods ORDER BY active DESC, start_date ASC");
                                                if (mysqli_num_rows($getPeriods) > 0) // periods found; continue
                                                {
                                                    while ($period = mysqli_fetch_array($getPeriods))
                                                    {
                                                        // store period details locally
                                                        $period_id = $period["id"];
                                                        $period_name = $period["name"];
                                                        $period_active = $period["active"];

                                                        if ($period_active == 1) { echo "<option value='".$period["id"]."' selected>★ ".$period["name"]."</option>"; }
                                                        else { echo "<option value='".$period["id"]."'>".$period["name"]."</option>"; }
                                                    }
                                                }
                                            ?>
                                        </select>
                                    </div>

                                    <!-- Divider -->
                                    <div class="form-group col-1 p-0"></div>

                                    <!-- Raise Period -->
                                    <div class="form-group col-5">
                                        <label for="er-raise-period"><span class="required-field">*</span> Salary Raise Period:</label>
                                        <select class="form-select font-awesome" id="er-raise-period" name="er-raise-period" aria-describedby="periodHelp" required>
                                            <option></option>
                                            <?php
                                                // create a list of all periods
                                                $getPeriods = mysqli_query($conn, "SELECT id, name, active FROM periods ORDER BY active DESC, start_date ASC");
                                                if (mysqli_num_rows($getPeriods) > 0) // periods found; continue
                                                {
                                                    while ($period = mysqli_fetch_array($getPeriods))
                                                    {
                                                        // store period details locally
                                                        $period_id = $period["id"];
                                                        $period_name = $period["name"];
                                                        $period_active = $period["active"];

                                                        if ($period_active == 1) { echo "<option value='".$period["id"]."' selected>★ ".$period["name"]."</option>"; }
                                                        else { echo "<option value='".$period["id"]."'>".$period["name"]."</option>"; }
                                                    }
                                                }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-row d-flex justify-content-center align-items-center">
                                    <p class="form-text m-0" id="periodHelp">★ indicates the current active period</p>
                                </div>

                                <!-- Raise Rate -->
                                <div class="form-row d-flex justify-content-center align-items-center">
                                    <div class="form-group col-11">
                                        <label for="er-raise-rate"><span class="required-field">*</span> Raise Rate:</label>
                                        <div class="input-group w-100 h-auto">
                                            <input class="form-control" type="number" id="er-raise-rate" name="er-raise-rate" min="0" step="0.1">
                                            <div class="input-group-prepend"><span class="input-group-text h-100" id="nav-search-icon"><i class="fa-solid fa-percent"></i></span></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="modal-footer">
                                <button type="submit" class="btn btn-primary" onclick="applyRaise();"><i class="fa-solid fa-money-bill-trend-up"></i> Apply Raise</button>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> Close</button>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- End Employees Experience Modal -->
                <?php } ?>

                <!-- Edit Employee Modal -->
                <div id="edit-employee-modal-div"></div>
                <!-- End Edit Employee Modal -->

                <!-- Mark Employee Changes Modal -->
                <div id="mark_changes-employee-modal-div"></div>
                <!-- End Mark Employee Changes Modal -->

                <!-- Delete Employee Modal -->
                <div id="delete-employee-modal-div"></div>
                <!-- End Delete Employee Modal -->

                <!-- Request Employee Change Modal -->
                <div id="request_change-employee-modal-div"></div>
                <!-- End Request Employee Change Modal -->

                <!-- Employee Projects Modal -->
                <div id="employee_projects-modal-div"></div>
                <!-- End Employee Projects Modal -->
                <!--
                    ### END MODALS ###
                -->

                <script>
                    // initialize variable to state if we've drawn the table or not
                    var drawn = 0; // assume we have not drawn the table (0)

                    // get the current active period
                    let active_period = "<?php echo $active_period_label; ?>"; 

                    // set page length to prior saved state
                    let saved_page_length = sessionStorage["BAP_Employees_PageLength"];
                    if (saved_page_length != "" && saved_page_length != null && saved_page_length != undefined)
                    {
                        $("#DT_PageLength").val(sessionStorage["BAP_Employees_PageLength"]);
                    }

                    // set the search filters to values we have saved in storage
                    if (sessionStorage["BAP_Employees_Search_Period"] != "" && sessionStorage["BAP_Employees_Search_Period"] != null && sessionStorage["BAP_Employees_Search_Period"] != undefined) { $('#search-period').val(sessionStorage["BAP_Employees_Search_Period"]); }
                    else { $('#search-period').val(active_period); } // no period set; default to active period 
                    $('#search-all').val(sessionStorage["BAP_Employees_Search_All"]);
                    $('#search-status').val(sessionStorage["BAP_Employees_Search_Status"]);
                    $('#search-role').val(sessionStorage["BAP_Employees_Search_Role"]);
                    $('#search-dept').val(sessionStorage["BAP_Employees_Search_Department"]);
                    $('#search-supervisor').val(sessionStorage["BAP_Employees_Search_Supervisor"]);

                    /** function to show employee data for the selected period */
                    function showEmployees()
                    {
                        // get the value of the period we are searching
                        var period = document.getElementById("search-period").value;

                        if (period != "" && period != null && period != undefined)
                        {
                            // update session storage stored search parameter
                            sessionStorage["BAP_Employees_Search_Period"] = period;

                            // set the fixed period
                            document.getElementById("fixed-period").value = period;

                            // if we have already drawn the table, destroy existing table
                            if (drawn == 1) { $("#employees").DataTable().destroy(); }

                            // initialize employees table
                            var employees = $("#employees").DataTable({
                                ajax: {
                                    url: "ajax/employees/getEmployees.php",
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
                                    { data: "id", orderable: true, width: "5%", className: "text-center" }, // 0
                                    { data: "lname", orderable: true, width: "10%", className: "text-center" },
                                    { data: "fname", orderable: true, width: "10%", className: "text-center" },
                                    { data: "birthday", orderable: true, width: "8%", className: "text-center" },
                                    { data: "contact", orderable: true, width: "10%", className: "text-center" },
                                    { data: "address", orderable: true, visible: false }, // 5
                                    { data: "position", orderable: true, width: "19%" },
                                    { data: "days", orderable: true, width: "4.5%", className: "text-center" },
                                    { data: "yearly_rate", orderable: true, width: "6%", className: "text-center" },
                                    { data: "daily_rate", orderable: true, width: "5%", className: "text-center" },
                                    { data: "hourly_rate", orderable: true, width: "5%", className: "text-center" }, // 10
                                    { data: "benefits", orderable: true, width: "7.5%" },
                                    { data: "health_costs", orderable: true, visible: false, className: "text-center" }, 
                                    { data: "dental_costs", orderable: true, visible: false, className: "text-center" }, 
                                    { data: "wrs_costs", orderable: true, visible: false, className: "text-center" }, 
                                    { data: "fica_costs", orderable: true, visible: false, className: "text-center" }, // 15
                                    { data: "life_costs", orderable: true, visible: false, className: "text-center" }, 
                                    { data: "ltd_costs", orderable: true, visible: false, className: "text-center" }, 
                                    { data: "total_fringe", orderable: true, visible: false, className: "text-center" }, 
                                    { data: "total_compensation", orderable: true, visible: false, className: "text-center" }, 
                                    { data: "daily_compensation", orderable: true, visible: false, className: "text-center" }, // 20
                                    { data: "actions", orderable: false, width: "10%" }, 
                                    { data: "role", orderable: true, visible: false },
                                    <?php if ($_SESSION["role"] == 1) { ?> 
                                        // hidden columns for exports and sorts
                                        { data: "export_id", orderable: false, visible: false }, // 23
                                        { data: "DOB", orderable: false, visible: false },
                                        { data: "email", orderable: false, visible: false }, // 25
                                        { data: "phone", orderable: false, visible: false },
                                        { data: "gender", orderable: false, visible: false },
                                        { data: "line1", orderable: false, visible: false },
                                        { data: "line2", orderable: false, visible: false },
                                        { data: "city", orderable: false, visible: false }, // 30
                                        { data: "state", orderable: false, visible: false },
                                        { data: "zip", orderable: false, visible: false }, 
                                        { data: "hire_date", orderable: false, visible: false },
                                        { data: "end_date", orderable: false, visible: false },
                                        { data: "contract_start_date", orderable: false, visible: false }, // 35
                                        { data: "contract_end_date", orderable: false, visible: false }, 
                                        { data: "export_contract_type", orderable: false, visible: false },
                                        { data: "export_calendar_type", orderable: false, visible: false },
                                        { data: "export_yearly_rate", orderable: false, visible: false },
                                        { data: "number_of_pays", orderable: false, visible: false }, // 40
                                        { data: "health", orderable: false, visible: false }, 
                                        { data: "dental", orderable: false, visible: false }, 
                                        { data: "wrs", orderable: false, visible: false }, // 40
                                        { data: "export_title", orderable: false, visible: false },
                                        { data: "department", orderable: false, visible: false },
                                        { data: "supervisor", orderable: false, visible: false }, 
                                        { data: "DPI_position", orderable: true, visible: false, className: "text-center" },
                                        { data: "DPI_area", orderable: true, visible: false, className: "text-center" }, // 45
                                        { data: "experience", orderable: true, visible: false, className: "text-center" },
                                        { data: "degree", orderable: true, visible: false, className: "text-center" }, // 50
                                        { data: "export_status", orderable: false, visible: false },
                                        { data: "nextPeriod_contract_days", orderable: false, visible: false },
                                        { data: "nextPeriod_contract_rate", orderable: false, visible: false }, 
                                        { data: "nextPeriod_rate_diff", orderable: false, visible: false },
                                        { data: "export_hourly_rate", orderable: false, visible: false }, // 55
                                    <?php } ?>
                                ],
                                order: [ // order alphabetically by default
                                    [ 1, "asc" ],
                                    [ 2, "asc" ]
                                ],
                                dom: 'rt',
                                language: {
                                    search: '<div class="input-group mb-1"><div class="input-group-prepend"><span class="d-flex align-items-center h-100 mx-2" id="nav-search-icon"><i class="fa fa-search" aria-hidden="true"></i></span></div>_INPUT_</div>',
                                    lengthMenu: '<div class="mx-1 mb-1">Show _MENU_ entries</div>',
                                    info: '<div class="mx-1">Showing _START_ to _END_ of _TOTAL_ entries</div>'
                                },
                                stateSave: true,
                                initComplete: function ()
                                {
                                    // if we are not redrawing the table (on page load), attempt to jump to prior spot
                                    if (drawn == 0)
                                    {
                                        let y = getCookie("BAP_Employees_yPos");
                                        window.scrollTo(0, y);
                                    }
                                    
                                    // mark that we have drawn the table
                                    drawn = 1;
                                },
                                rowCallback: function (row, data, index)
                                {
                                    // initialie page selection
                                    updatePageSelection("employees");

                                    // initialize popovers
                                    $(document).ready(function(){
                                        $('[data-bs-toggle="popover"]').popover({
                                            trigger: "hover click", // triggers on hover and click
                                            placement: "bottom",
                                            container: "body",
                                            html: true,
                                        });
                                    });
                                },
                            });
                            // create the column visibility buttons
                            new $.fn.dataTable.Buttons(employees, {
                                buttons: [
                                    {
                                        extend:    'colvis',
                                        text:      '<i class="fa-solid fa-eye fa-sm"></i>',
                                        titleAttr: 'Column Visibility',
                                        className: "btn btn-secondary m-0 px-2 py-0",
                                        columns: [12, 13, 14, 15, 16, 17, 18, 19, 20<?php if ($_SESSION["role"] == 1) { ?>, 40, 47, 48, 49, 50 <?php } ?>], // only toggle visibility for benefits
                                        init: function(api, node, config) {
                                            // remove default button classes
                                            $(node).removeClass('dt-button');
                                        }
                                    }
                                ],
                            });
                            // add buttons to container
                            employees.buttons(0, null).container().appendTo("#colVis-buttons");

                            // search table by custom search filter
                            $('#search-all').keyup(function() {
                                employees.search($(this).val()).draw();
                                sessionStorage["BAP_Employees_Search_All"] = $(this).val();
                            });

                            // search table by employee status
                            $('#search-status').change(function() {
                                sessionStorage["BAP_Employees_Search_Status"] = $(this).val();
                                if ($(this).val() != "") { employees.columns(51).search("^" + $(this).val() + "$", true, false, true).draw(); }
                                else { employees.columns(51).search("").draw(); }
                            });

                            // search table by employee role
                            $('#search-role').change(function() {
                                employees.columns(22).search($(this).val()).draw();
                                sessionStorage["BAP_Employees_Search_Role"] = $(this).val();
                            });

                            <?php if ($_SESSION["role"] == 1) { ?>
                                // search table by employee primary department
                                $('#search-dept').change(function() {
                                    employees.columns(45).search($(this).val()).draw();
                                    sessionStorage["BAP_Employees_Search_Department"] = $(this).val();
                                });
                            <?php } else { ?>
                                // search table by employee primary department
                                $('#search-dept').change(function() {
                                    employees.columns(6).search($(this).val()).draw();
                                    sessionStorage["BAP_Employees_Search_Department"] = $(this).val();
                                });
                            <?php } ?>

                            // search table by employee supervisor
                            $('#search-supervisor').change(function() {
                                employees.columns(46).search($(this).val()).draw();
                                sessionStorage["BAP_Employees_Search_Supervisor"] = $(this).val();
                            });

                            // search table by project code
                            $('#search-num_of_pays').change(function() {
                                employees.columns(40).search($(this).val()).draw();
                                sessionStorage["BAP_Employees_Search_NumOfPays"] = $(this).val();
                            });

                            // function to clear search filters
                            $('#clearFilters').click(function() {
                                sessionStorage["BAP_Employees_Search_All"] = "";
                                sessionStorage["BAP_Employees_Search_Status"] = "";
                                sessionStorage["BAP_Employees_Search_Role"] = "";
                                sessionStorage["BAP_Employees_Search_Department"] = "";
                                sessionStorage["BAP_Employees_Search_Supervisor"] = "";
                                sessionStorage["BAP_Employees_Search_NumOfPays"] = "";
                                $('#search-all').val("");
                                $('#search-status').val("");
                                $('#search-dept').val("");
                                <?php if (isset($PERMISSIONS["VIEW_EMPLOYEES_ALL"])) { ?>
                                    $('#search-role').val("");
                                    $('#search-supervisor').val("");
                                    $('#search-num_of_pays').val("");
                                <?php } ?>
                                employees.search("").columns().search("").draw();
                            });

                            // redraw caseload table with current search fields
                            if ($('#search-all').val() != "") { employees.search($('#search-all').val()).draw(); }
                            if ($('#search-status').val() != "") { employees.columns(51).search("^" + $('#search-status').val() + "$", true, false, true).draw(); }

                            <?php if ($_SESSION["role"] == 1) { ?>
                                if ($('#search-dept').val() != "") { employees.columns(45).search("^" + $('#search-dept').val() + "$", true, false, true).draw(); }
                            <?php } else { ?>
                                if ($('#search-dept').val() != "") { employees.columns(6).search($('#search-dept').val(), true, false, true).draw(); }
                            <?php } ?>

                            <?php if (isset($PERMISSIONS["VIEW_EMPLOYEES_ALL"])) { ?>
                                if ($('#search-role').val() != "") { employees.columns(22).search("^" + $('#search-role').val() + "$", true, false, true).draw(); }
                                if ($('#search-supervisor').val() != "") { employees.columns(46).search("^" + $('#search-supervisor').val() + "$", true, false, true).draw(); }
                                if ($('#search-num_of_pays').val() != "") { employees.columns(40).search("^" + $('#search-num_of_pays').val() + "$", true, false, true).draw(); }
                            <?php } ?>

                            <?php if ($_SESSION["role"] == 1) { ?>
                                // create the export buttons
                                new $.fn.dataTable.Buttons(employees, {
                                    buttons: [
                                        // CSV BUTTON
                                        {
                                            extend: "csv",
                                            exportOptions: {
                                                columns: [ 23, 2, 1, 3, 25, 26, 27, 28, 29, 30, 31, 32, 33, 36, 37, 7, 38, 39, 40, 41, 51, 43, 44, 45, 47, 48, 49, 50, 51, 22 ]
                                            },
                                            text: "Employees List (.csv)",
                                            className: "dropdown-item quickNav-dropdown-item text-center w-100 px-3 py-2 rounded-0",
                                            title: "Employees List",
                                            titleAttr: "Export the employees list to a .csv file",
                                            init: function(api, node, config) {
                                                // remove default button classes
                                                $(node).removeClass('dt-button');
                                                $(node).removeClass('buttons-csv');
                                                $(node).removeClass('buttons-html5');
                                            }
                                        },
                                    ]
                                });
                                new $.fn.dataTable.Buttons(employees, {
                                    buttons: [
                                        // EXCEL BUTTON
                                        {
                                            extend: "excel",
                                            exportOptions: {
                                                columns: [ 23, 2, 1, 3, 25, 26, 27, 28, 29, 30, 31, 32, 33, 36, 37, 7, 38, 39, 40, 41, 51, 43, 44, 45, 47, 48, 49, 50, 51, 22 ]
                                            },
                                            text: "Employees List (.xlsx)",
                                            className: "dropdown-item quickNav-dropdown-item text-center w-100 px-3 py-2 rounded-0",
                                            title: "Employees List",
                                            titleAttr: "Export the employees list to a .xlsx file",
                                            init: function(api, node, config) {
                                                // remove default button classes
                                                $(node).removeClass('dt-button');
                                                $(node).removeClass('buttons-excel');
                                                $(node).removeClass('buttons-html5');
                                            }
                                        },
                                    ]
                                });
                                // add buttons to page description area
                                employees.buttons(1, null).container().appendTo("#list_csv-export-div");
                                employees.buttons(2, null).container().appendTo("#list_xlsx-export-div");

                                // create TalentEd export button
                                new $.fn.dataTable.Buttons(employees, {
                                    buttons: [
                                        // CSV BUTTON
                                        {
                                            extend: "csv",
                                            exportOptions: {
                                                columns: [ 23, 1, 2, 44, 7, 39, 55, 38 ],
                                                rows: function (idx, data, node) {
                                                    if (data["export_status"] == "Active") {
                                                        return true;
                                                    }
                                                }
                                            },
                                            text: "TalentEd Export (.csv)",
                                            className: "dropdown-item quickNav-dropdown-item text-center w-100 px-3 py-2 rounded-0",
                                            title: "Employees List - TalentEd Upload",
                                            titleAttr: "Export the employees list for TalentEd to a .csv file",
                                            init: function(api, node, config) {
                                                // remove default button classes
                                                $(node).removeClass('dt-button');
                                                $(node).removeClass('buttons-csv');
                                                $(node).removeClass('buttons-html5');
                                            }
                                        }
                                    ]
                                });
                                // add buttons to page description area
                                employees.buttons(3, null).container().appendTo("#TalentEd-export-div");

                                // create TalentEd export button
                                new $.fn.dataTable.Buttons(employees, {
                                    buttons: [
                                        // CSV BUTTON
                                        {
                                            extend: "csv",
                                            exportOptions: {
                                                columns: [ 23, 1, 2, 44, 7, 39, 55, 52, 53, 54, 38 ],
                                                rows: function (idx, data, node) {
                                                    if (data["export_status"] == "Active") {
                                                        return true;
                                                    }
                                                }
                                            },
                                            text: "TalentEd Verification Export (.csv)",
                                            className: "dropdown-item quickNav-dropdown-item text-center w-100 px-3 py-2 rounded-0",
                                            title: "Employees List - TalentEd Upload",
                                            titleAttr: "Export the employees list for TalentEd to a .csv file",
                                            init: function(api, node, config) {
                                                // remove default button classes
                                                $(node).removeClass('dt-button');
                                                $(node).removeClass('buttons-csv');
                                                $(node).removeClass('buttons-html5');
                                            }
                                        }
                                    ]
                                });
                                // add buttons to page description area
                                employees.buttons(4, null).container().appendTo("#TalentEd-verification-export-div");
                            <?php } ?>
                        }
                    }

                    <?php if ($_SESSION["role"] == 1) { ?>
                        /** function to add a year of experience to all employees within the selected period */
                        function addYearOfExperience()
                        {
                            // get the period selected
                            var period = document.getElementById("ee-period").value;

                            // send request to add a year of experience
                            var xmlhttp = new XMLHttpRequest();
                            xmlhttp.open("POST", "ajax/employees/addYearOfExperience.php", true);
                            xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                            xmlhttp.onreadystatechange = function() 
                            {
                                if (this.readyState == 4 && this.status == 200)
                                {
                                    // create the status modal
                                    let status_title = "Add Year Of Experience Status";
                                    let status_body = this.responseText;
                                    createStatusModal("refresh", status_title, status_body);

                                    // hide the current modal
                                    $("#employeesExperienceModal").modal("hide");
                                }
                            };
                            xmlhttp.send("period="+period);
                        }

                        /** function to increase the salary for all employees by the rate provided for the selected period */
                        function applyRaise()
                        {
                            // get the period selected
                            var base_period = document.getElementById("er-base-period").value;
                            var raise_period = document.getElementById("er-raise-period").value;
                            var raise_rate = document.getElementById("er-raise-rate").value;

                            // send request to add a year of experience
                            var xmlhttp = new XMLHttpRequest();
                            xmlhttp.open("POST", "ajax/employees/applyMassRaise.php", true);
                            xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                            xmlhttp.onreadystatechange = function() 
                            {
                                if (this.readyState == 4 && this.status == 200)
                                {
                                    // create the status modal
                                    let status_title = "Employee Raise Status";
                                    let status_body = this.responseText;
                                    createStatusModal("refresh", status_title, status_body);

                                    // hide the current modal
                                    $("#employeesRaiseModal").modal("hide");
                                }
                            };
                            xmlhttp.send("base_period="+base_period+"&raise_period="+raise_period+"&raise_rate="+raise_rate);
                        }
                    <?php } ?>

                    <?php if (isset($PERMISSIONS["ADD_EMPLOYEES"])) { ?>
                        // initialize datetime picker for adding an employees birthday
                        $(function() {
                            $("#add-birthday").daterangepicker({
                                singleDatePicker: true,
                                showDropdowns: true,
                                minYear: 1900,
                                maxYear: <?php echo date("Y"); ?>
                            });
                            $("#add-birthday").val("");

                            $("#add-hire_date").daterangepicker({
                                singleDatePicker: true,
                                showDropdowns: true,
                                minYear: 1900,
                                maxYear: <?php echo date("Y"); ?>
                            });
                            $("#add-hire_date").val("");

                            $("#add-end_date").daterangepicker({
                                singleDatePicker: true,
                                showDropdowns: true,
                                minYear: 1900,
                                maxYear: <?php echo date("Y"); ?>
                            });
                            $("#add-end_date").val("");

                            $("#add-contract_start_date").daterangepicker({
                                singleDatePicker: true,
                                showDropdowns: true,
                                minYear: 1900,
                                maxYear: <?php echo date("Y"); ?>
                            });
                            $("#add-contract_start_date").val("");

                            $("#add-contract_end_date").daterangepicker({
                                singleDatePicker: true,
                                showDropdowns: true,
                                minYear: 1900,
                                maxYear: <?php echo date("Y"); ?>
                            });
                            $("#add-contract_end_date").val("");
                        });

                        // check for sliding within the add employees carousel
                        $('#add-employee-carousel').on('slide.bs.carousel', function(e) {
                            // get the slide we are moving to
                            let next = e.to;

                            // set the next or previous slide to active
                            slideTo("add", "add-slider-page-"+(next + 1));
                        });
                    <?php } ?>

                    // show employees with default parameters
                    showEmployees();

                    // on page unload, store current position in a cookie to return to that spot 
                    window.onbeforeunload = function () {
                        let y = window.pageYOffset;
                        document.cookie = "BAP_Employees_yPos="+y+"; expires=Tue, 19 Jan 2038 04:14:07 GMT";
                    };
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