<?php 
    include_once("header.php");

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1) 
    { 
        if (isset($PERMISSIONS["VIEW_PROJECT_BUDGETS_ALL"]) || isset($PERMISSIONS["VIEW_PROJECT_BUDGETS_ASSIGNED"]))
        {
            // connect to the database
            $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

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

            // get the project code and period ID received from POST if available
            $project_code = $period_id = null;
            if (isset($_POST["project_code"]) && trim($_POST["project_code"]) <> "") { $project_code = trim($_POST["project_code"]); } else { $project_code = null; }
            if (isset($_POST["period_id"]) && trim($_POST["period_id"]) <> "") { $period_id = trim($_POST["period_id"]); } else { $period_id = null; }

            ?>
                <style>
                    /* custom accordion styling */
                    .accordion-button, .accordion-button:active, .accordion-button:focus, .accordion-button:focus-visible, .accordion-button:focus-within, .accordion-button:hover, .accordion-button:visited, .accordion-button:target, .accordion-button:not(.collapsed)
                    {
                        background-color: #f05323;
                        display: block;
                        text-align: center;
                        color: #ffffff;
                        border: none !important;
                        box-shadow: none;
                    }

                    .accordion-button:hover, .accordion-button:focus
                    {
                        background-color: #f26b41 !important;
                    }
                    
                    .accordion-item:last-of-type .accordion-button.collapsed 
                    {
                        border-bottom-right-radius: 0;
                        border-bottom-left-radius: 0;
                        box-shadow: none;
                    }

                    .accordion-item
                    {
                        border: none !important;
                        box-shadow: none;
                    }

                    .accordion-body
                    {
                        background-color: #f05323;
                        border: none !important;
                        box-shadow: none;
                    }

                    .accordion-flush
                    {
                        border: none !important;
                        box-shadow: none;
                    }
                    
                    .rotate
                    {
                        transform: rotate(180deg);
                    }

                    #project_employees td, #project_revenues td, #project_expenses td, #project_codes td
                    {
                        font-size: 16px !important;
                    }

                    #period-icon-div:hover #period-label
                    {
                        display: inline;
                        color: #000000;
                        transform: translate(4px, 00%);
                    }

                    #period-label
                    {
                        display: none;
                        color: #000000;
                        transition: 1s;
                    }

                    #project-icon-div:hover #project-label
                    {
                        display: inline;
                        color: #000000;
                        transform: translate(4px, 00%);
                    }

                    #project-label
                    {
                        display: none;
                        color: #000000;
                        transition: 1s;
                    }

                    .buttons-colvis
                    {
                        background-color: #6c757d !important;
                        border-color: #6c757d !important;
                        color: #ffffff !important;
                    }
                </style>

                <script>
                    // prevent POST resubmission on refresh
                    if (window.history.replaceState) 
                    {
                        window.history.replaceState(null, null, window.location.href);
                    }

                    // initialize the variable to indicate if we have drawn the table
                    var drawn = 0;

                    /** function to get the add employee to a project modal */
                    function getAddEmployeeToProjectModal()
                    {
                        // get the fixed period name and project code
                        let period = document.getElementById("fixed-period").value;
                        let code = document.getElementById("fixed-project_code").value;

                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/projects/getAddEmployeeToProjectModal.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // display the delete department modal
                                document.getElementById("add-employee_to_project-modal-div").innerHTML = this.responseText;     
                                $("#addEmployeeToProjectModal").modal("show");
                            }
                        };
                        xmlhttp.send("period="+period+"&code="+code);
                    }

                    /** function to get the add a test employee to the project modal */
                    function getAddTestEmployeeToProjectModal()
                    {
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/projects/getAddTestEmployeeToProjectModal.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // display the delete department modal
                                document.getElementById("add-test_employee_to_project-modal-div").innerHTML = this.responseText;     
                                $("#addTestEmployeeToProjectModal").modal("show");
                            }
                        };
                        xmlhttp.send();
                    }

                    /** function to get the upload project employees modal */
                    function getUploadProjectEmployeesModal()
                    {
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/projects/getUploadProjectEmployeesModal.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // display the delete department modal
                                document.getElementById("upload-project_employees-modal-div").innerHTML = this.responseText;     
                                $("#uploadProjectEmployeesModal").modal("show");
                            }
                        };
                        xmlhttp.send();
                    }

                    /** function to get the upload project employees modal */
                    function getBulkUploadProjectEmployeesModal()
                    {
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/projects/getBulkUploadProjectEmployeesModal.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // display the delete department modal
                                document.getElementById("upload-bulk-project_employees-modal-div").innerHTML = this.responseText;     
                                $("#uploadBulkProjectEmployeesModal").modal("show");
                            }
                        };
                        xmlhttp.send();
                    }

                    /** function to get the upload project expenses modal */
                    function getBulkUploadProjectExpensesModal()
                    {
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/projects/getBulkUploadProjectExpensesModal.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // display the delete department modal
                                document.getElementById("upload-bulk-project_expenses-modal-div").innerHTML = this.responseText;     
                                $("#uploadBulkProjectExpensesModal").modal("show");
                            }
                        };
                        xmlhttp.send();
                    }

                    /** function to add an employee to the project */
                    function addEmployeeToProject()
                    {
                        // get the parameters
                        let code = document.getElementById("fixed-project_code").value;
                        let period = document.getElementById("fixed-period").value;

                        let employee = document.getElementById("add-employee_to_project-employee").value;
                        let days = document.getElementById("add-employee_to_project-days").value;
                        let fund = document.getElementById("add-employee_to_project-fund").value;
                        let loc = document.getElementById("add-employee_to_project-loc").value;
                        let obj = document.getElementById("add-employee_to_project-obj").value;
                        let func = document.getElementById("add-employee_to_project-func").value;
                        let location_id = document.getElementById("add-employee_to_project-staff_location").value;

                        // create the string of data to send
                        let sendString = "period="+period+"&code="+code+"&employee="+employee+"&days="+days+"&fund="+fund+"&loc="+loc+"&obj="+obj+"&func="+func+"&location_id="+location_id;

                        // send the data to add the employee selected to the project selected
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/projects/addEmployeeToProject.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // create the status modal
                                let status_title = "Add Employees To Project Status";
                                let status_body = this.responseText;
                                createStatusModal("refresh", status_title, status_body);

                                // hide the current modal
                                $("#addEmployeeToProjectModal").modal("hide");
                            }
                        };
                        xmlhttp.send(sendString);
                    }

                    /** function to add an employee to the project */
                    function addTestEmployeeToProject()
                    {
                        // get the parameters
                        let period = document.getElementById("fixed-period").value;
                        let code = document.getElementById("fixed-project_code").value;
                        let label = document.getElementById("add-test_emp-label").value;
                        let rate = document.getElementById("add-test_emp-rate").value;
                        let days = document.getElementById("add-test_emp-days").value;
                        let health = document.getElementById("add-test_emp-health").value;
                        let dental = document.getElementById("add-test_emp-dental").value;
                        let wrs = document.getElementById("add-test_emp-wrs").value;
                        let inclusion = document.getElementById("add-test_emp-inclusion").value;

                        // create the string of data to send
                        let sendString = "period="+period+"&code="+code+"&label="+label+"&rate="+rate+"&days="+days+"&health="+health+"&dental="+dental+"&wrs="+wrs+"&inclusion="+inclusion;

                        // send the data to add the employee selected to the project selected
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/projects/addTestEmployeeToProject.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // create the status modal
                                let status_title = "Add Test Employee To Project Status";
                                let status_body = this.responseText;
                                createStatusModal("refresh", status_title, status_body);

                                // hide the current modal
                                $("#addTestEmployeeToProjectModal").modal("hide");
                            }
                        };
                        xmlhttp.send(sendString);
                    }

                    /** function to add an employee to the project */
                    function editTestProjectEmployee(id)
                    {
                        // get the parameters
                        let period = document.getElementById("fixed-period").value;
                        let code = document.getElementById("fixed-project_code").value;
                        let label = document.getElementById("edit-test_emp-label").value;
                        let rate = document.getElementById("edit-test_emp-rate").value;
                        let days = document.getElementById("edit-test_emp-days").value;
                        let health = document.getElementById("edit-test_emp-health").value;
                        let dental = document.getElementById("edit-test_emp-dental").value;
                        let wrs = document.getElementById("edit-test_emp-wrs").value;
                        let inclusion = document.getElementById("edit-test_emp-inclusion").value;

                        // create the string of data to send
                        let sendString = "id="+id+"&period="+period+"&code="+code+"&label="+label+"&rate="+rate+"&days="+days+"&health="+health+"&dental="+dental+"&wrs="+wrs+"&inclusion="+inclusion;

                        // send the data to add the employee selected to the project selected
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/projects/editTestProjectEmployee.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // create the status modal
                                let status_title = "Edit Project Employee Status";
                                let status_body = this.responseText;
                                createStatusModal("refresh", status_title, status_body);

                                // hide the current modal
                                $("#editTestProjectEmployeeModal").modal("hide");
                            }
                        };
                        xmlhttp.send(sendString);
                    }

                    /** function to get the add revenue to a project modal */
                    function getAddRevenueToProjectModal()
                    {
                        // get current project
                        let fixed_proj = document.getElementById("fixed-project_code").value;

                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/projects/getAddRevenueToProjectModal.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                document.getElementById("add-revenue_to_project-modal-div").innerHTML = this.responseText;     
                                $("#addRevenueToProjectModal").modal("show");

                                $(function() {
                                    $("#add-revenue_to_project-date").daterangepicker({
                                        singleDatePicker: true,
                                        showDropdowns: true,
                                        minYear: 2000,
                                        maxYear: <?php echo date("Y") + 10; ?>
                                    });
                                });
                            }
                        }
                        xmlhttp.send("project_code="+fixed_proj);
                    }

                    /** function to get the edit revenue modal */
                    function getEditRevenueModal(revenue_id)
                    {
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/revenues/getEditRevenueModal.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                document.getElementById("edit-project_revenue-modal-div").innerHTML = this.responseText;     
                                $("#editRevenueModal").modal("show");

                                $(function() {
                                    $("#edit-revenue-date").daterangepicker({
                                        singleDatePicker: true,
                                        showDropdowns: true,
                                        minYear: 2000,
                                        maxYear: <?php echo date("Y") + 10; ?>
                                    });
                                });
                            }
                        }
                        xmlhttp.send("id="+revenue_id+"&source=1");
                    }

                    /** function to get the remove revenue from project modal */
                    function getRemoveRevenueFromProjectModal(revenue_id)
                    {
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/revenues/getDeleteRevenueModal.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                document.getElementById("delete-project_revenue-modal-div").innerHTML = this.responseText;     
                                $("#deleteRevenueModal").modal("show");
                            }
                        }
                        xmlhttp.send("id="+revenue_id);
                    }

                    /** function to delete the revenue */
                    function deleteRevenue(revenue_id)
                    {
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/revenues/deleteRevenue.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // create the status modal
                                let status_title = "Delete Revenue Status";
                                let status_body = this.responseText;
                                createStatusModal("refresh", status_title, status_body);

                                // hide the current modal
                                $("#deleteRevenueModal").modal("hide");
                            }
                        };
                        xmlhttp.send("id="+revenue_id);
                    }

                    /** function to edit the revenue */
                    function editRevenue(id)
                    {
                        // get current project
                        let fixed_proj = document.getElementById("fixed-project_code").value;

                        // get revenue details from the modal
                        let name = encodeURIComponent(document.getElementById("edit-revenue-name").value);
                        let desc = encodeURIComponent(document.getElementById("edit-revenue-desc").value);
                        let date = encodeURIComponent(document.getElementById("edit-revenue-date").value);
                        let rev = encodeURIComponent(document.getElementById("edit-revenue-total").value);
                        let fund = encodeURIComponent(document.getElementById("edit-revenue-fund").value);
                        let loc = encodeURIComponent(document.getElementById("edit-revenue-loc").value);
                        let src = encodeURIComponent(document.getElementById("edit-revenue-src").value);
                        let func = encodeURIComponent(document.getElementById("edit-revenue-func").value);
                        let proj = encodeURIComponent(document.getElementById("edit-revenue-proj").value);
                        let quantity = encodeURIComponent(document.getElementById("edit-revenue-qty").value);

                        if (fixed_proj == proj)
                        {
                            // create the string of data to send
                            let sendString = "id="+id+"&name="+name+"&desc="+desc+"&date="+date+"&revenue="+rev+"&fund="+fund+"&loc="+loc+"&src="+src+"&func="+func+"&proj="+proj+"&quantity="+quantity;

                            var xmlhttp = new XMLHttpRequest();
                            xmlhttp.open("POST", "ajax/revenues/editRevenue.php", true);
                            xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                            xmlhttp.onreadystatechange = function() 
                            {
                                if (this.readyState == 4 && this.status == 200)
                                {
                                    // create the status modal
                                    let status_title = "Edit Revenue Status";
                                    let status_body = this.responseText;
                                    createStatusModal("refresh", status_title, status_body);

                                    // hide the current modal
                                    $("#editRevenueModal").modal("hide");
                                }
                            };
                            xmlhttp.send(sendString);
                        }
                    }

                    /** function to add a revenue to the project */
                    function addRevenueToProject()
                    {
                        // get the parameters
                        let period = document.getElementById("fixed-period").value;
                        let fixed_proj = document.getElementById("fixed-project_code").value;
                        let fund = document.getElementById("add-revenue_to_project-fund").value;
                        let loc = document.getElementById("add-revenue_to_project-loc").value;
                        let src = document.getElementById("add-revenue_to_project-src").value;
                        let func = document.getElementById("add-revenue_to_project-func").value;
                        let proj = document.getElementById("add-revenue_to_project-proj").value
                        let cost = document.getElementById("add-revenue_to_project-cost").value;
                        let name = encodeURIComponent(document.getElementById("add-revenue_to_project-name").value);
                        let desc = encodeURIComponent(document.getElementById("add-revenue_to_project-desc").value);
                        let date = encodeURIComponent(document.getElementById("add-revenue_to_project-date").value);

                        if (fixed_proj == proj)
                        {
                            // create the string of data to send
                            let sendString = "period="+period+"&proj="+proj+"&fund="+fund+"&loc="+loc+"&src="+src+"&func="+func+"&name="+name+"&desc="+desc+"&date="+date+"&cost="+cost;

                            // send the data to add the revenue to the project selected
                            var xmlhttp = new XMLHttpRequest();
                            xmlhttp.open("POST", "ajax/projects/addRevenueToProject.php", true);
                            xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                            xmlhttp.onreadystatechange = function() 
                            {
                                if (this.readyState == 4 && this.status == 200)
                                {
                                    // create the status modal
                                    let status_title = "Add Revenue To Project Status";
                                    let status_body = this.responseText;
                                    createStatusModal("refresh", status_title, status_body);

                                    // hide the current modal
                                    $("#addRevenueToProjectModal").modal("hide");
                                }
                            };
                            xmlhttp.send(sendString);
                        }
                    }

                    /** function to get the add expense to a project modal */
                    function getAddExpenseToProjectModal()
                    {
                        // get the project code
                        let project = document.getElementById("fixed-project_code").value;

                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/projects/getAddExpensesToProjectModal.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                document.getElementById("add-expense_to_project-modal-div").innerHTML = this.responseText;     
                                $("#addExpensesToProjectModal").modal("show");
                            }
                        };
                        xmlhttp.send("project="+project);
                    }

                    /** function to add an expense to the project */
                    function addExpensesToProject()
                    {
                        // get the parameters
                        let code = document.getElementById("fixed-project_code").value;
                        let period = document.getElementById("fixed-period").value;

                        // get the number of expenses
                        let numOfExps = document.getElementById("add-expense_to_project-numOfRanges").value;

                        // for each expense, add to array
                        let expenses = [];
                        for (let e = 1; e <= numOfExps; e++)
                        {
                            let expense = [];
                            let expense_id = document.getElementById("add-expense_to_project-expense-"+e).value;
                            let cost = document.getElementById("add-expense_to_project-cost-"+e).value;
                            let fund = document.getElementById("add-expense_to_project-fund-"+e).value;
                            let func = document.getElementById("add-expense_to_project-func-"+e).value;
                            let desc = document.getElementById("add-expense_to_project-desc-"+e).value;
                            expense = [expense_id, cost, fund, func, desc];
                            expenses.push(expense);
                        }

                        // create the string of data to send
                        let sendString = "period="+period+"&code="+code+"&expenses="+JSON.stringify(expenses);

                        // send the data to add the employee selected to the project selected
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/projects/addExpensesToProject.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // create the status modal
                                let status_title = "Add Expense To Project Status";
                                let status_body = this.responseText;
                                createStatusModal("refresh", status_title, status_body);

                                // hide the current modal
                                $("#addExpensesToProjectModal").modal("hide");
                            }
                        };
                        xmlhttp.send(sendString);
                    }

                    /** function to get the upload project expenses modal */
                    function getUploadProjectExpensesModal()
                    {
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/projects/getUploadProjectExpensesModal.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                document.getElementById("upload-project_expenses-modal-div").innerHTML = this.responseText;     
                                $("#uploadProjectExpensesModal").modal("show");
                            }
                        };
                        xmlhttp.send();
                    }

                    /** function to remove an employee from the project */
                    function removeEmployeeFromProject(id, code, record)
                    {
                        // get the fixed project code and period
                        let period = document.getElementById("fixed-period").value;

                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/projects/removeEmployeeFromProject.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // create the status modal
                                let status_title = "Remove Employee From Project Status";
                                let status_body = this.responseText;
                                createStatusModal("refresh", status_title, status_body);

                                // hide the current modal
                                $("#removeEmployeeFromProjectModal").modal("hide");
                            }
                        };
                        xmlhttp.send("period="+period+"&code="+code+"&id="+id+"&record="+record);
                    }

                    /** function to remove a test employee from the project */
                    function removeTestEmployeeFromProject(id)
                    {
                        // get the fixed project code and period
                        let period = document.getElementById("fixed-period").value;
                        let code = document.getElementById("fixed-project_code").value;

                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/projects/removeTestEmployeeFromProject.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // create the status modal
                                let status_title = "Remove Test Employee From Project Status";
                                let status_body = this.responseText;
                                createStatusModal("refresh", status_title, status_body);

                                // hide the current modal
                                $("#removeTestEmployeeFromProjectModal").modal("hide");
                            }
                        };
                        xmlhttp.send("period="+period+"&code="+code+"&id="+id);
                    }

                    /** function to get the delete department modal */
                    function getRemoveEmployeeFromProjectModal(id, code, record)
                    {
                        // send the data to create the delete department modal
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/projects/getRemoveEmployeeFromProjectModal.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // display the delete department modal
                                document.getElementById("remove-employee_from_project-modal-div").innerHTML = this.responseText;     
                                $("#removeEmployeeFromProjectModal").modal("show");
                            }
                        };
                        xmlhttp.send("id="+id+"&code="+code+"&record="+record);
                    }

                    /** function to get the delete department modal */
                    function getRemoveTestEmployeeFromProjectModal(id)
                    {
                        // get the fixed project code and perido
                        let code = document.getElementById("fixed-project_code").value;
                        let period = document.getElementById("fixed-period").value;

                        // send the data to create the delete department modal
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/projects/getRemoveTestEmployeeFromProjectModal.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // display the delete department modal
                                document.getElementById("remove-test_employee_from_project-modal-div").innerHTML = this.responseText;     
                                $("#removeTestEmployeeFromProjectModal").modal("show");
                            }
                        };
                        xmlhttp.send("id="+id+"&code="+code+"&period="+period);
                    }

                    /** function to remove an employee from the project */
                    function editProjectEmployee(id, code, record)
                    {
                        // get the fixed project code and period
                        let period = document.getElementById("fixed-period").value;

                        // get other form fields
                        let days = document.getElementById("edit-project_employee-days").value;
                        let fund = document.getElementById("edit-project_employee-fund").value;
                        let loc = document.getElementById("edit-project_employee-loc").value;
                        let obj = document.getElementById("edit-project_employee-obj").value;
                        let func = document.getElementById("edit-project_employee-func").value;
                        let location_id = document.getElementById("edit-project_employee-staff_location").value;

                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/projects/editProjectEmployee.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // create the status modal
                                let status_title = "Edit Project Employee Status";
                                let status_body = this.responseText;
                                createStatusModal("refresh", status_title, status_body);

                                // hide the current modal
                                $("#editProjectEmployeeModal").modal("hide");
                            }
                        };
                        xmlhttp.send("period="+period+"&code="+code+"&id="+id+"&days="+days+"&fund="+fund+"&loc="+loc+"&obj="+obj+"&func="+func+"&location_id="+location_id+"&record="+record);
                    }

                    /** function to get the edit project employee modal */
                    function getEditProjectEmployeeModal(id, code, record)
                    {
                        // get the fixed project code and period
                        let period = document.getElementById("fixed-period").value;

                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/projects/getEditProjectEmployeeModal.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // display the delete department modal
                                document.getElementById("edit-project_employee-modal-div").innerHTML = this.responseText;     
                                $("#editProjectEmployeeModal").modal("show");
                            }
                        };
                        xmlhttp.send("period="+period+"&code="+code+"&id="+id+"&record="+record);
                    }

                    /** function to get the edit test project employee modal */
                    function getEditTestProjectEmployeeModal(id)
                    {
                        // get the fixed project code and period
                        let period = document.getElementById("fixed-period").value;
                        let code = document.getElementById("fixed-project_code").value;

                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/projects/getEditTestProjectEmployeeModal.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // display the delete department modal
                                document.getElementById("edit-test_project_employee-modal-div").innerHTML = this.responseText;     
                                $("#editTestProjectEmployeeModal").modal("show");
                            }
                        };
                        xmlhttp.send("period="+period+"&code="+code+"&id="+id);
                    }

                    /** function to remove an expense from the project */
                    function removeProjectExpense(id)
                    {
                        // get the fixed project code
                        let code = document.getElementById("fixed-project_code").value;
                        let period = document.getElementById("fixed-period").value;

                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/projects/removeProjectExpense.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // create the status modal
                                let status_title = "Remove Employee From Project Status";
                                let status_body = this.responseText;
                                createStatusModal("refresh", status_title, status_body);

                                // hide the current modal
                                $("#removeProjectExpenseModal").modal("hide");
                            }
                        };
                        xmlhttp.send("period="+period+"&code="+code+"&id="+id);
                    }

                    /** function to get the remove project expnese modal */
                    function getRemoveProjectExpenseModal(id)
                    {
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/projects/getRemoveProjectExpenseModal.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // display the delete department modal
                                document.getElementById("remove-project_expense-modal-div").innerHTML = this.responseText;     
                                $("#removeProjectExpenseModal").modal("show");
                            }
                        };
                        xmlhttp.send("id="+id);
                    }

                    /** function to edit the project expense */
                    function editProjectExpense(id)
                    {
                        // get the fixed project code
                        let code = document.getElementById("fixed-project_code").value;
                        let cost = document.getElementById("edit-project_expense-cost").value;
                        let fund = document.getElementById("edit-project_expense-fund").value;
                        let func = document.getElementById("edit-project_expense-func").value;
                        let desc = encodeURIComponent(document.getElementById("edit-project_expense-desc").value);
                        let period = document.getElementById("fixed-period").value;

                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/projects/editProjectExpense.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // create the status modal
                                let status_title = "Edit Project Expense Status";
                                let status_body = this.responseText;
                                createStatusModal("refresh", status_title, status_body);

                                // hide the current modal
                                $("#editProjectExpenseModal").modal("hide");
                            }
                        };
                        xmlhttp.send("period="+period+"&code="+code+"&id="+id+"&cost="+cost+"&desc="+desc+"&fund="+fund+"&func="+func);
                    }

                    /** function to get the edit project employee modal */
                    function getEditProjectExpenseModal(id)
                    {
                        // get the fixed project code and period
                        let code = document.getElementById("fixed-project_code").value;
                        let period = document.getElementById("fixed-period").value;

                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/projects/getEditProjectExpenseModal.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // display the delete department modal
                                document.getElementById("edit-project_expense-modal-div").innerHTML = this.responseText;     
                                $("#editProjectExpenseModal").modal("show");
                            }
                        };
                        xmlhttp.send("period="+period+"&code="+code+"&id="+id);
                    }

                    /** function to get the modal to provide a service */
                    function getProvideServiceModal()
                    {
                        // get the fixed project code
                        let code = document.getElementById("fixed-project_code").value;

                        // send the data to create the delete invoice modal
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/services/provided/getProvideServiceModal.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                document.getElementById("add-service_to_project-modal-div").innerHTML = this.responseText;     

                                // display the provide service modal
                                $("#provideServiceModal").modal("show");

                                $(function() {
                                    $("#provide-date").daterangepicker({
                                        singleDatePicker: true,
                                        showDropdowns: true,
                                        minYear: 2000,
                                        maxYear: <?php echo date("Y") + 10; ?>
                                    });
                                });
                            }
                        }
                        xmlhttp.send("code="+code);
                    }

                    /** function to get the modal to provide a service */
                    function getProvideOtherServiceModal()
                    {
                        // get the fixed parameters
                        let code = document.getElementById("fixed-project_code").value;
                        let period = document.getElementById("fixed-period").value;

                        // send the data to create the delete invoice modal
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/services/provided/getProvideOtherServiceModal.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                document.getElementById("add-other_invoice-modal-div").innerHTML = this.responseText;     

                                // display the provide service modal
                                $("#provideOtherServiceModal").modal("show");

                                $(function() {
                                    $("#add-invoice_other-date").daterangepicker({
                                        singleDatePicker: true,
                                        showDropdowns: true,
                                        minYear: 2000,
                                        maxYear: <?php echo date("Y") + 10; ?>
                                    });
                                });
                            }
                        }
                        xmlhttp.send("project="+code+"&period="+period);
                    }

                    /** function to provide a other service */
                    function provideOtherService()
                    {
                        // get the fixed period name
                        let period = document.getElementById("fixed-period").value;

                        // get form paramters
                        let service_id = document.getElementById("add-invoice_other-service_id").value;
                        let customer_id = document.getElementById("add-invoice_other-customer_id").value;
                        let project_code = document.getElementById("add-invoice_other-project_code").value;
                        let total_cost = document.getElementById("add-invoice_other-cost").value;
                        let quantity = document.getElementById("add-invoice_other-qty").value;
                        let unit_label = document.getElementById("add-invoice_other-unit").value;
                        let description = document.getElementById("add-invoice_other-desc").value;
                        let date = document.getElementById("add-invoice_other-date").value;

                        // create the string of data to send
                        let sendString = "period="+period+"&service_id="+service_id+"&customer_id="+customer_id+"&project_code="+project_code+"&total_cost="+total_cost+"&quantity="+quantity+"&unit_label="+unit_label+"&description="+description+"&date="+date;

                        // send the data to process the add invoice request
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/services/provided/provideOtherService.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                if (this.responseText != "")
                                {
                                    // create the status modal
                                    let status_title = "Provide Service Status";
                                    let status_body = this.responseText;
                                    createStatusModal("refresh", status_title, status_body);

                                    // hide the current modal
                                    $("#provideOtherServiceModal").modal("hide");
                                }
                                else { window.location.reload(); }
                            }
                        };
                        xmlhttp.send(sendString);
                    }

                    /** function to update the total annual cost preview */
                    function updateCost(quantity_id, preview_id, mode)
                    {
                        // get the quantity and service ID
                        let period = document.getElementById("fixed-period").value;
                        let service_id = encodeURIComponent(document.getElementById(mode+"-service").value);
                        let quantity = encodeURIComponent(document.getElementById(mode+"-quantity").value);
                        let rate_tier = encodeURIComponent(document.getElementById(mode+"-rate").value);
                        let group_rate_tier = encodeURIComponent(document.getElementById(mode+"-group_rate").value);
                        let sendString = "period="+period+"&service_id="+service_id+"&quantity="+quantity+"&rate_tier="+rate_tier+"&group_rate_tier="+group_rate_tier;

                        // send the data to process the add customer request
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/services/provided/getEstimatedCost.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                document.getElementById(preview_id).innerHTML = this.responseText;
                            }
                        };
                        xmlhttp.send(sendString);
                    }

                    /** function to add a new service */
                    function provideService()
                    {
                        // create the string of data to send
                        let sendString = "";

                        // get the fixed period name
                        let period = document.getElementById("fixed-period").value;

                        // get service details from the modal
                        let service_id = encodeURIComponent(document.getElementById("provide-service").value);
                        let customer_id = encodeURIComponent(document.getElementById("provide-customer").value);
                        let quantity = encodeURIComponent(document.getElementById("provide-quantity").value);
                        let custom_cost = encodeURIComponent(document.getElementById("provide-custom_cost").value);
                        let rate_tier = encodeURIComponent(document.getElementById("provide-rate").value);
                        let group_rate_tier = encodeURIComponent(document.getElementById("provide-group_rate").value);
                        let description = encodeURIComponent(document.getElementById("provide-description").value);
                        let date = encodeURIComponent(document.getElementById("provide-date").value);
                        sendString += "period="+period+"&service_id="+service_id+"&customer_id="+customer_id+"&quantity="+quantity+"&description="+description+"&date="+date+"&custom_cost="+custom_cost+"&rate_tier="+rate_tier+"&group_rate_tier="+group_rate_tier;

                        // send the data to process the add customer request
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/services/provided/provideService.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                if (this.responseText != "")
                                {
                                    // create the status modal
                                    let status_title = "Provide Service Status";
                                    let status_body = encodeURIComponent(this.responseText);
                                    createStatusModal("refresh", status_title, status_body);

                                    // hide the current modal
                                    $("#provideServiceModal").modal("hide");
                                }
                                else { window.location.reload(); }
                            }
                        };
                        xmlhttp.send(sendString);
                    }

                    /** function to recalculate the project's automated expenses */
                    function recalculateAutomatedExpenses()
                    {
                        // get the fixed project code and period
                        let period = document.getElementById("fixed-period").value;
                        let code = document.getElementById("fixed-project_code").value;

                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/projects/recalculateProjectExpenses.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                window.location.reload();
                            }
                        };
                        xmlhttp.send("period="+period+"&code="+code);
                    }

                    /** function to change the table */
                    function selectTable(selection)
                    {
                        // remove selected class from all buttons
                        document.getElementById("btn-project_employees").classList.remove("btn-project_select-selected");
                        document.getElementById("btn-project_revenues").classList.remove("btn-project_select-selected");
                        document.getElementById("btn-project_expenses").classList.remove("btn-project_select-selected");
                        <?php if ($_SESSION["role"] == 1) { ?>
                            document.getElementById("btn-project_codes").classList.remove("btn-project_select-selected");
                        <?php } ?>

                        // hide all divs
                        document.getElementById("div-project_employees").classList.add("project-hidden");
                        document.getElementById("div-project_revenues").classList.add("project-hidden");
                        document.getElementById("div-project_expenses").classList.add("project-hidden");
                        <?php if ($_SESSION["role"] == 1) { ?>
                            document.getElementById("div-project_codes").classList.add("project-hidden");
                        <?php } ?>

                        // add selected class to button clicked
                        document.getElementById("btn-"+selection).classList.add("btn-project_select-selected");

                        // unhide div selected
                        document.getElementById("div-"+selection).classList.remove("project-hidden");

                        // update session storage stored view parameter
                        sessionStorage["BAP_ProjectsBudget_ViewTable"] = selection;
                    }

                    /** function to get the remove invoice modal */
                    function getRemoveInvoiceFromProjectModal(invoice_id)
                    {
                        // send the data to create the delete invoice modal
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/services/provided/getDeleteInvoiceModal.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                document.getElementById("delete-invoice-modal-div").innerHTML = this.responseText;     

                                // display the edit customer modal
                                $("#deleteInvoiceModal").modal("show");
                            }
                        };
                        xmlhttp.send("invoice_id="+invoice_id);
                    }

                    /** function to get the remove other invoice modal */
                    function getRemoveOtherInvoiceFromProjectModal(invoice_id)
                    {
                        // send the data to create the delete invoice modal
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/services/provided/getDeleteOtherInvoiceModal.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                document.getElementById("delete-invoice-modal-div").innerHTML = this.responseText;     

                                // display the edit customer modal
                                $("#deleteInvoiceModal").modal("show");
                            }
                        };
                        xmlhttp.send("invoice_id="+invoice_id);
                    }
                    
                    /** function to delete the invoice */
                    function deleteInvoice(id)
                    {
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/services/provided/deleteInvoice.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // create the status modal
                                let status_title = "Delete Invoice Status";
                                let status_body = this.responseText;
                                createStatusModal("refresh", status_title, status_body);

                                // hide the current modal
                                $("#deleteInvoiceModal").modal("hide");
                            }
                        };
                        xmlhttp.send("invoice_id="+id);
                    }

                    /** function to get the edit invoice modal */
                    function getEditInvoiceModal(id)
                    {
                        // send the data to create the delete invoice modal
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/services/provided/getEditInvoiceModal.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                document.getElementById("edit-invoice-modal-div").innerHTML = this.responseText;     

                                // display the edit customer modal
                                $("#editInvoiceModal").modal("show");

                                $(function() {
                                    $("#edit-date").daterangepicker({
                                        singleDatePicker: true,
                                        showDropdowns: true,
                                        minYear: 2000,
                                        maxYear: <?php echo date("Y") + 10; ?>
                                    });
                                });
                            }
                        };
                        xmlhttp.send("invoice_id="+id);
                    }

                    /** function to edit an invoice */
                    function editInvoice()
                    {
                        // create the string of data to send
                        let sendString = "";

                        // get the fixed period name
                        let period = document.getElementById("fixed-period").value;

                        // get service details from the modal
                        let invoice_id = encodeURIComponent(document.getElementById("edit-invoice_id").value);
                        let quantity = encodeURIComponent(document.getElementById("edit-quantity").value);
                        let custom_cost = encodeURIComponent(document.getElementById("edit-custom_cost").value);
                        let rate_tier = encodeURIComponent(document.getElementById("edit-rate").value);
                        let group_rate_tier = encodeURIComponent(document.getElementById("edit-group_rate").value);
                        let description = encodeURIComponent(document.getElementById("edit-description").value);
                        let date = encodeURIComponent(document.getElementById("edit-date").value);
                        let allow_zero = encodeURIComponent(document.getElementById("edit-zero").value);
                        sendString += "period="+period+"&invoice_id="+invoice_id+"&quantity="+quantity+"&description="+description+"&date="+date+"&custom_cost="+custom_cost+"&allow_zero="+allow_zero+"&rate_tier="+rate_tier+"&group_rate_tier="+group_rate_tier;

                        // send the data to process the add customer request
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/services/provided/editInvoice.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                if (this.responseText != "")
                                {
                                    // create the status modal
                                    let status_title = "Edit Invoice Status";
                                    let status_body = encodeURIComponent(this.responseText);
                                    createStatusModal("refresh", status_title, status_body);

                                    // hide the current modal
                                    $("#editInvoiceModal").modal("hide");
                                }
                                else { window.location.reload(); }
                            }
                        };
                        xmlhttp.send(sendString);
                    }

                    /** function to edit an invoice */
                    function editOtherInvoice(id)
                    {
                        // create the string of data to send
                        let sendString = "";

                        // get service details from the modal
                        let invoice_id = encodeURIComponent(document.getElementById("edit-invoice_id").value);
                        let quantity = encodeURIComponent(document.getElementById("edit-quantity").value);
                        let description = encodeURIComponent(document.getElementById("edit-description").value);
                        let date = encodeURIComponent(document.getElementById("edit-date").value);
                        sendString += "invoice_id="+invoice_id+"&quantity="+quantity+"&description="+description+"&date="+date;

                        if (id == invoice_id)
                        {
                            // send the data to process the add customer request
                            var xmlhttp = new XMLHttpRequest();
                            xmlhttp.open("POST", "ajax/services/provided/editInvoice.php", true);
                            xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                            xmlhttp.onreadystatechange = function() 
                            {
                                if (this.readyState == 4 && this.status == 200)
                                {
                                    window.location.reload();
                                }
                            };
                            xmlhttp.send(sendString);
                        }
                    }

                    /** function to add a new employee row */
                    function addRange()
                    {
                        // get the current number of ranges
                        let numOfRanges = document.getElementById("add-numOfRanges").value;

                        // get the current employees
                        let currentEmps = document.getElementById("add-employee_to_project-employees_grid").innerHTML;

                        // get the current employees dropdown
                        let empDropdown = getEmployeeDropdown();

                        // create the new range
                        let newEmp = parseInt(numOfRanges) + 1;
                        let newEmpRow = "<div class='row m-0 p-0 mb-1' id='add-employee_to_project-row-"+ newEmp +"'>" +
                                            "<div class='col-3 py-0 px-1'><select class='form-select w-100' id='add-employee_to_project-employee-"+ newEmp +"' name='add-employee_to_project-employee-"+ newEmp +"' required>"+ empDropdown +"</select></div>" +
                                            "<div class='col-1 py-0 px-1'><input type='number' min='0' max='365' class='form-control w-100' id='add-employee_to_project-days-"+ newEmp +"' name='add-employee_to_project-days-"+ newEmp +"' required></div>" +
                                            "<div class='col-2 py-0 px-1'><input type='number' min='10' max='99' class='form-control w-100' id='add-employee_to_project-fund-"+ newEmp +"' name='add-employee_to_project-fund-"+ newEmp +"' required></div>" +
                                            "<div class='col-2 py-0 px-1'><input type='number' min='100' max='999' value='999' class='form-control w-100' id='add-employee_to_project-loc-"+ newEmp +"' name='add-employee_to_project-loc-"+ newEmp +"' required></div>" +
                                            "<div class='col-2 py-0 px-1'><input type='number' min='100' max='999' value='100' class='form-control w-100' id='add-employee_to_project-obj-"+ newEmp +"' name='add-employee_to_project-obj-"+ newEmp +"' required></div>" +
                                            "<div class='col-2 py-0 px-1'><input type='number' class='form-control w-100' id='add-employee_to_project-func-"+ newEmp +"' name='add-employee_to_project-func-"+ newEmp +"' required></div>" +
                                        "</div>";

                        // create the new grid (current ranges + new range)
                        let newGrid = currentEmps + newEmpRow;

                        // update the number of rows
                        document.getElementById("add-numOfRanges").value = newEmp;

                        // display the new employees grid
                        document.getElementById("add-employee_to_project-employees_grid").innerHTML = newGrid;

                        // if there is more than 1 row, enable the remove a range button
                        if (newEmp > 1) { document.getElementById("add-employee_to_project-removeRangeBtn").removeAttribute("disabled"); }
                    }

                    /** function to remove a new employee row */
                    function removeRange()
                    {
                        // get the current number of ranges
                        let numOfRanges = document.getElementById("add-numOfRanges").value;

                        // only allow deletion of there is more than 1 range
                        if (numOfRanges > 1)
                        {
                            // remove the range
                            document.getElementById("add-employee_to_project-row-"+numOfRanges).remove();

                            // update the new number of ranges
                            let newRange = parseInt(numOfRanges) - 1;
                            document.getElementById("add-numOfRanges").value = newRange;

                            // if there is only 1 range, disable the remove range button
                            if (newRange == 1) { document.getElementById("add-employee_to_project-removeRangeBtn").setAttribute("disabled", true); }
                        }
                    }

                    /** function to get the employee dropdown */
                    function getEmployeeDropdown()
                    {
                        // get the fixed period name
                        let period = document.getElementById("fixed-period").value;

                        return $.ajax({
                            type: "POST",
                            url: "ajax/misc/getEmployeeDropdown.php",
                            data: {
                                period: period
                            },
                            async: false,
                        }).responseText;  
                    }

                    /** function to get the expenses dropdown */
                    function getExpensesDropdown()
                    {
                        return $.ajax({
                            type: "POST",
                            url: "ajax/misc/getExpensesDropdown.php",
                            async: false,
                        }).responseText;  
                    }

                    /** function to add a new project_expense row */
                    function addProjectExpenseRange()
                    {
                        // get the current number of ranges
                        let numOfRanges = document.getElementById("add-expense_to_project-numOfRanges").value;

                        // get the current employees
                        let currentExps = document.getElementById("add-expense_to_project-expenses_grid").innerHTML;

                        // get the current expenses dropdown
                        let expsDropdown = getExpensesDropdown();

                        // create the new range
                        let newExp = parseInt(numOfRanges) + 1;
                        let newExpsRow = "<div class='row m-0 p-0 mb-1' id='add-expense_to_project-row-"+ newExp +"'>" +
                                            "<div class='col-3 py-0 px-1'><select class='form-select w-100' id='add-expense_to_project-expense-"+ newExp +"' name='add-expense_to_project-expense' required>"+ expsDropdown +"</select></div>" +
                                            "<div class='col-2 py-0 px-1'><input type='number' min='0.00' class='form-control w-100' id='add-expense_to_project-cost-"+ newExp +"' name='add-expense_to_project-cost' required></div>" +
                                            "<div class='col-2 py-0 px-1'><input type='text' class='form-control w-100' id='add-expense_to_project-fund-"+ newExp +"' name='add-expense_to_project-fund'></div>" +
                                            "<div class='col-2 py-0 px-1'><input type='text' class='form-control w-100' id='add-expense_to_project-func-"+ newExp +"' name='add-expense_to_project-func'></div>" +
                                            "<div class='col-3 py-0 px-1'><input type='text' class='form-control w-100' id='add-expense_to_project-desc-"+ newExp +"' name='add-expense_to_project-desc'></div>" +
                                        "</div>";

                        // create the new grid (current ranges + new range)
                        let newGrid = currentExps + newExpsRow;

                        // update the number of rows
                        document.getElementById("add-expense_to_project-numOfRanges").value = newExp;

                        // display the new expenses grid
                        document.getElementById("add-expense_to_project-expenses_grid").innerHTML = newGrid;

                        // if there is more than 1 row, enable the remove a range button
                        if (newExp > 1) { document.getElementById("add-expense_to_project-removeRangeBtn").removeAttribute("disabled"); }
                    }

                    /** function to remove a new project expense row */
                    function removeProjectExpenseRange()
                    {
                        // get the current number of ranges
                        let numOfRanges = document.getElementById("add-numOfRanges").value;

                        // only allow deletion of there is more than 1 range
                        if (numOfRanges > 1)
                        {
                            // remove the range
                            document.getElementById("add-employee_to_project-row-"+numOfRanges).remove();

                            // update the new number of ranges
                            let newRange = parseInt(numOfRanges) - 1;
                            document.getElementById("add-numOfRanges").value = newRange;

                            // if there is only 1 range, disable the remove range button
                            if (newRange == 1) { document.getElementById("add-employee_to_project-removeRangeBtn").setAttribute("disabled", true); }
                        }
                    }

                    /** function to get the modal to delete an invoice for an "other service" */
                    function getRemoveOtherInvoiceFromProjectModal(invoice_id)
                    {
                        // send the data to create the delete invoice modal
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/services/provided/getDeleteOtherInvoiceModal.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                document.getElementById("delete-invoice-modal-div").innerHTML = this.responseText;     

                                // display the edit customer modal
                                $("#deleteInvoiceModal").modal("show");
                            }
                        };
                        xmlhttp.send("invoice_id="+invoice_id);
                    }

                    /** function to delete an invoice for an "other service" */
                    function deleteOtherInvoice(invoice_id)
                    {
                        // send the data to process the delete other service request
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/services/provided/deleteOtherInvoice.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // create the status modal
                                let status_title = "Delete Invoice Status";
                                let status_body = this.responseText;
                                createStatusModal("refresh", status_title, status_body);

                                // hide the current modal
                                $("#deleteInvoiceModal").modal("hide");
                            }
                        };
                        xmlhttp.send("invoice_id="+invoice_id);
                    }

                    /** function to get the modal to edit an "other service" invoice */
                    function getEditOtherInvoiceModal(invoice_id)
                    {
                        // send the data to create the edit invoice modal
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/services/provided/getEditOtherInvoiceModal.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                document.getElementById("edit-other_invoice-modal-div").innerHTML = this.responseText;     

                                // display the edit customer modal
                                $("#editOtherInvoiceModal").modal("show");

                                // initialize datepicker in edit invoice modal
                                $(function() {
                                    $("#edit-invoice-date").daterangepicker({
                                        singleDatePicker: true,
                                        showDropdowns: true,
                                        minYear: 2000,
                                        maxYear: <?php echo date("Y") + 10; ?>
                                    });
                                });
                            }
                        };
                        xmlhttp.send("invoice_id="+invoice_id);
                    }

                    /** function to edit an invoice for an "other service" */
                    function editOtherInvoice(invoice_id)
                    {
                        // get form paramters
                        let project_code = document.getElementById("edit-invoice-project_code").value;
                        let total_cost = document.getElementById("edit-invoice-cost").value;
                        let quantity = document.getElementById("edit-invoice-qty").value;
                        let unit_label = document.getElementById("edit-invoice-unit").value;
                        let description = document.getElementById("edit-invoice-desc").value;
                        let date = document.getElementById("edit-invoice-date").value;

                        // create the string of data to send
                        let sendString = "invoice_id="+invoice_id+"&project_code="+project_code+"&total_cost="+total_cost+"&quantity="+quantity+"&unit_label="+unit_label+"&description="+description+"&date="+date;

                        // send the data to process the add invoice request
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/services/provided/editOtherInvoice.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                if (this.responseText != "")
                                {
                                    // create the status modal
                                    let status_title = "Edit Invoice Status";
                                    let status_body = this.responseText;
                                    createStatusModal("refresh", status_title, status_body);

                                    // hide the current modal
                                    $("#editInvoiceModal").modal("hide");
                                }
                                else { window.location.reload(); }
                            }
                        };
                        xmlhttp.send(sendString);
                    }

                    /** function to get the cost type of a service */
                    function checkCostType(mode)
                    {
                        // get the fixed period name
                        let period = document.getElementById("fixed-period").value;

                        // get customer and service ID from form fields
                        let service_id = encodeURIComponent(document.getElementById(mode+"-service").value);
                        let customer_id = encodeURIComponent(document.getElementById(mode+"-customer").value);

                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/misc/getServiceCostType.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                let cost_type = this.responseText;
                                if (cost_type == 0 || cost_type == 1 || cost_type == 2) // fixed, variable, membership costs
                                {
                                    // hide cost div
                                    document.getElementById(mode+"-custom_cost-div").classList.remove("d-flex");
                                    document.getElementById(mode+"-custom_cost-div").classList.add("d-none");
                                    
                                    // display preview cost div
                                    document.getElementById(mode+"-preview_cost-div").classList.remove("d-none");
                                    document.getElementById(mode+"-preview_cost-div").classList.add("d-flex");

                                    // hide rates div
                                    document.getElementById(mode+"-rate-div").classList.remove("d-flex");
                                    document.getElementById(mode+"-rate-div").classList.add("d-none");
                                }
                                else if (cost_type == 3) // custom cost
                                {
                                    // display custom cost div 
                                    document.getElementById(mode+"-custom_cost-div").classList.remove("d-none");
                                    document.getElementById(mode+"-custom_cost-div").classList.add("d-flex");

                                    // hide preview cost div
                                    document.getElementById(mode+"-preview_cost-div").classList.remove("d-flex");
                                    document.getElementById(mode+"-preview_cost-div").classList.add("d-none");

                                    // hide rates div
                                    document.getElementById(mode+"-rate-div").classList.remove("d-flex");
                                    document.getElementById(mode+"-rate-div").classList.add("d-none");
                                }
                                else if (cost_type == 4) // rate
                                {
                                    // hide quantity div
                                    document.getElementById(mode+"-quantity-div").classList.remove("d-flex");
                                    document.getElementById(mode+"-quantity-div").classList.add("d-none");

                                    // create and display the selection dropdown for rates
                                    let rates_dropdown = $.ajax({
                                        type: "POST",
                                        url: "ajax/services/provided/getRatesDropdown.php",
                                        async: false,
                                        data: {
                                            service_id: service_id,
                                            period: period
                                        }
                                    }).responseText;
                                    document.getElementById(mode+"-rate-select-div").innerHTML = rates_dropdown;

                                    // display rate div
                                    document.getElementById(mode+"-rate-div").classList.remove("d-none");
                                    document.getElementById(mode+"-rate-div").classList.add("d-flex");

                                    // hide preview cost div
                                    document.getElementById(mode+"-preview_cost-div").classList.remove("d-flex");
                                    document.getElementById(mode+"-preview_cost-div").classList.add("d-none");
                                }
                                else if (cost_type == 5) // group rate
                                {
                                    // hide quantity div
                                    document.getElementById(mode+"-quantity-div").classList.remove("d-flex");
                                    document.getElementById(mode+"-quantity-div").classList.add("d-none");

                                    // create and display the selection dropdown for rates
                                    let rates_dropdown = $.ajax({
                                        type: "POST",
                                        url: "ajax/services/provided/getGroupRatesDropdown.php",
                                        async: false,
                                        data: {
                                            service_id: service_id,
                                            customer_id: customer_id
                                        }
                                    }).responseText;
                                    document.getElementById(mode+"-group_rate-select-div").innerHTML = rates_dropdown;

                                    // display group rate div
                                    document.getElementById(mode+"-group_rate-div").classList.remove("d-none");
                                    document.getElementById(mode+"-group_rate-div").classList.add("d-flex");

                                    // hide preview cost div
                                    document.getElementById(mode+"-preview_cost-div").classList.remove("d-flex");
                                    document.getElementById(mode+"-preview_cost-div").classList.add("d-none");
                                }
                            }
                        };
                        xmlhttp.send("service_id="+service_id);
                    }

                    /** function to update the zero costs setting */
                    function updateZeroCosts(id)
                    {
                        // get current status of the element
                        let element = document.getElementById(id);
                        let status = element.value;

                        if (status == 0) // currently set to no
                        {
                            element.value = 1;
                            element.innerHTML = "Yes";
                            element.classList.remove("btn-danger");
                            element.classList.add("btn-success");
                        }
                        else // currently set to yes, or other?
                        {
                            element.value = 0;
                            element.innerHTML = "No";
                            element.classList.remove("btn-success");
                            element.classList.add("btn-danger");
                        }
                    }

                    /** function to toggle the cost inclusion setting */
                    function toggleInclusion(id)
                    {
                        // get current status of the element
                        let element = document.getElementById(id);
                        let status = element.value;

                        if (status == 0) // currently set to not include
                        {
                            // update status to active
                            element.value = 1;
                            element.innerHTML = "Include";
                            element.classList.remove("btn-danger");
                            element.classList.add("btn-success");
                        }
                        else // currently set to include, or other
                        {
                            // update status to not include
                            element.value = 0;
                            element.innerHTML = "Don't Include";
                            element.classList.remove("btn-success");
                            element.classList.add("btn-danger");
                        }
                    }

                    /** function to toggle the project details accordion button */
                    function toggleProjectDetails(value)
                    {
                        if (value == 0)
                        {
                            document.getElementById("PD-collapse-btn").value = 1;
                            document.getElementById("dropdown-arrow").classList.add("rotate");
                        }
                        else
                        {
                            document.getElementById("PD-collapse-btn").value = 0;
                            document.getElementById("dropdown-arrow").classList.remove("rotate");
                        }
                    }

                    /** function to create the projects selection dropdown */
                    function createProjectsDropdown()
                    {
                        // get the selected period
                        let period = $("#search-period").val();

                        // get the currently selected project
                        let selected_project_code = document.getElementById("search-project").value;

                        // get the projectd dropdown
                        let content = $.ajax({
                            type: "POST",
                            url: "ajax/projects/getProjectsDropdown.php",
                            data: {
                                period: period
                            },
                            async: false,
                        }).responseText;

                        // fill dropdown and select the previously selected option
                        document.getElementById("search-project").innerHTML = content;
                        document.getElementById("search-project").value = selected_project_code;
                    }

                    /** function to get the modal to clone an existing revenue */
                    function getCloneRevenueModal(revenue_id)
                    {
                        // send the data to create the edit invoice modal
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/revenues/getCloneRevenueModal.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                document.getElementById("clone-revenue-modal-div").innerHTML = this.responseText;     

                                // display the edit customer modal
                                $("#cloneRevenueModal").modal("show");

                                // initialize datepicker in edit invoice modal
                                $(function() {
                                    $("#add-revenue_to_project-date").daterangepicker({
                                        singleDatePicker: true,
                                        showDropdowns: true,
                                        minYear: 2000,
                                        maxYear: <?php echo date("Y") + 10; ?>
                                    });
                                });
                            }
                        };
                        xmlhttp.send("revenue_id="+revenue_id+"&source=1");
                    }

                    /** function to add a revenue to the project from the clone revenue modal */
                    function cloneRevenue()
                    {
                        // get the parameters
                        let period = document.getElementById("fixed-period").value;
                        let fixed_proj = document.getElementById("fixed-project_code").value;
                        let fund = document.getElementById("clone-revenue-fund").value;
                        let loc = document.getElementById("clone-revenue-loc").value;
                        let src = document.getElementById("clone-revenue-src").value;
                        let func = document.getElementById("clone-revenue-func").value;
                        let proj = document.getElementById("clone-revenue-proj").value
                        let cost = document.getElementById("clone-revenue-cost").value;
                        let name = encodeURIComponent(document.getElementById("clone-revenue-name").value);
                        let desc = encodeURIComponent(document.getElementById("clone-revenue-desc").value);
                        let date = encodeURIComponent(document.getElementById("clone-revenue-date").value);

                        if (fixed_proj == proj)
                        {
                            // create the string of data to send
                            let sendString = "period="+period+"&proj="+proj+"&fund="+fund+"&loc="+loc+"&src="+src+"&func="+func+"&name="+name+"&desc="+desc+"&date="+date+"&cost="+cost;

                            // send the data to add the revenue to the project selected
                            var xmlhttp = new XMLHttpRequest();
                            xmlhttp.open("POST", "ajax/projects/addRevenueToProject.php", true);
                            xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                            xmlhttp.onreadystatechange = function() 
                            {
                                if (this.readyState == 4 && this.status == 200)
                                {
                                    // create the status modal
                                    let status_title = "Clone Revenue Status";
                                    let status_body = this.responseText;
                                    createStatusModal("refresh", status_title, status_body);

                                    // hide the current modal
                                    $("#cloneRevenueModal").modal("hide");
                                }
                            };
                            xmlhttp.send(sendString);
                        }
                    }

                    /** function to get the modal to clone a project expense */
                    function getCloneProjectExpenseModal(project_expense_id)
                    {
                        // send the data to create the edit invoice modal
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/projects/getCloneProjectExpenseModal.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                document.getElementById("clone-expense-modal-div").innerHTML = this.responseText;     

                                // display the edit customer modal
                                $("#cloneProjectExpenseModal").modal("show");
                            }
                        };
                        xmlhttp.send("project_expense_id="+project_expense_id);
                    }

                    /** function to clone an existing project expense */
                    function cloneProjectExpense()
                    {
                        // get form parameters
                        let period = document.getElementById("fixed-period").value;
                        let code = document.getElementById("fixed-project_code").value;
                        let expense_id = document.getElementById("clone-project_expense-expense_id").value;
                        let cost = document.getElementById("clone-project_expense-cost").value;
                        let fund = document.getElementById("clone-project_expense-fund").value;
                        let func = document.getElementById("clone-project_expense-func").value;
                        let desc = encodeURIComponent(document.getElementById("clone-project_expense-desc").value);

                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/projects/addExpenseToProject.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // create the status modal
                                let status_title = "Clone Project Expense Status";
                                let status_body = this.responseText;
                                createStatusModal("refresh", status_title, status_body);

                                // hide the current modal
                                $("#cloneProjectExpenseModal").modal("hide");
                            }
                        };
                        xmlhttp.send("period="+period+"&code="+code+"&expense_id="+expense_id+"&cost="+cost+"&desc="+desc+"&fund="+fund+"&func="+func);
                    }

                    /** function to toggle additional details */
                    function toggleDetails(value)
                    {
                        if (value == 1) // details are currently displayed; hide details
                        {
                            // hide div
                            document.getElementById("showDetails").value = 0;
                            document.getElementById("showDetails-icon").innerHTML = "<i class='fa-solid fa-angle-down'></i>";
                            document.getElementById("details-div").classList.add("d-none");
                        }
                        else // details are currently hidden; display details
                        {
                            // display div
                            document.getElementById("showDetails").value = 1;
                            document.getElementById("showDetails-icon").innerHTML = "<i class='fa-solid fa-angle-up'></i>";
                            document.getElementById("details-div").classList.remove("d-none");
                        }
                    }
                </script>

                <div class="report">
                    <!-- Page Header -->
                    <div class="table-header p-0">
                        <div class="row d-flex justify-content-center align-items-center text-center py-2 px-3 m-0">
                            <!-- Period & Filters-->
                            <div class="col-6 col-sm-4 col-md-4 col-lg-3 col-xl-2 col-xxl-2 p-0">
                                <div class="row p-0">
                                    <!-- Period Selection -->
                                    <div class="col-9 p-0">
                                        <div class="row mb-1">
                                            <div class="input-group h-auto">
                                                <div class="input-group-prepend" id="period-icon-div">
                                                    <span class="input-group-text h-100" id="nav-search-icon">
                                                        <i class="fa-solid fa-calendar-days"></i>
                                                        <span id="period-label">Period</span>
                                                    </span>
                                                </div>
                                                <input id="fixed-period" type="hidden" value="" aria-hidden="true">
                                                <select class="form-select" id="search-period" name="search-period" onchange="createProjectsDropdown(); searchProject();">
                                                    <?php
                                                        for ($p = 0; $p < count($periods); $p++)
                                                        {
                                                            echo "<option value='".$periods[$p]["name"]."'>".$periods[$p]["name"]."</option>";
                                                        }
                                                    ?>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="row mt-1">
                                            <div class="input-group h-auto">
                                                <div class="input-group-prepend" id="project-icon-div">
                                                    <span class="input-group-text h-100" id="nav-search-icon">
                                                        <i class="fa-solid fa-folder"></i>
                                                        <span id="project-label">Project</span>
                                                    </span>
                                                </div>
                                                <select class="form-select" id="search-project" name="search-project" placeholder="Search projects" aria-describedby="nav-search-icon" onchange="searchProject();">
                                                    <option></option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Filters -->
                                    <div class="col-3 d-flex ps-2 py-0">
                                        <div class="dropdown float-start">
                                            <button class="btn btn-primary h-100" type="button" id="filtersMenu" data-bs-toggle="dropdown" aria-expanded="false">
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
                                <h1 class="m-0">Project Budgets</h1>
                            </div>

                            <!-- Page Management Dropdown -->
                            <div class="col-6 col-sm-4 col-md-4 col-lg-3 col-xl-2 col-xxl-2 d-flex justify-content-end p-0">
                                <!-- Show Details -->
                                <button class="btn btn-primary mx-1" id="showDetails" value="0" onclick="toggleDetails(this.value);">
                                    <i class="fa-solid fa-eye"></i>
                                    <span class="float-end ps-2" id="showDetails-icon">
                                        <i class="fa-solid fa-angle-down"></i>
                                    </span>
                                </button>

                                <?php if (isset($PERMISSIONS["BUDGET_PROJECTS_ALL"]) || isset($PERMISSIONS["BUDGET_PROJECTS_ASSIGNED"])) { ?>
                                    <div class="dropdown mx-1">
                                        <button class="btn btn-primary dropdown-toggle" type="button" id="dropdownMenuButton1" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                                            Manage Project's Budget
                                        </button>
                                        <ul class="dropdown-menu p-0" aria-labelledby="dropdownMenuButton1">
                                            <!-- Project Employees -->
                                            <li>
                                                <div class="dropdown dropstart float-end w-100">
                                                    <button class="btn btn-primary dropdown-toggle w-100 rounded-0" type="button" id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">
                                                        Manage Project Employees
                                                    </button>
                                                    <ul class="dropdown-menu drop-start p-0" aria-labelledby="dropdownMenuButton1">
                                                        <li><button class="dropdown-item quickNav-dropdown-item text-center w-100 px-3 py-2 rounded-0" type="button" onclick="getAddEmployeeToProjectModal();">Add Employees To Project</button></li>
                                                        <li><button class="dropdown-item quickNav-dropdown-item text-center w-100 px-3 py-2 rounded-0" type="button" onclick="getAddTestEmployeeToProjectModal();">Add Test Employee To Project</button></li>
                                                        <li><button class="dropdown-item quickNav-dropdown-item text-center w-100 px-3 py-2 rounded-0" type="button" onclick="getUploadProjectEmployeesModal();">Upload Project Employees</button></li>
                                                        <li><button class="dropdown-item quickNav-dropdown-item text-center w-100 px-3 py-2 rounded-0" type="button" onclick="getBulkUploadProjectEmployeesModal();">Bulk Upload Project Employees</button></li>
                                                    </ul>
                                                </div>
                                            </li>

                                            <!-- Project Expenses -->
                                            <li>
                                                <div class="dropdown dropstart float-end w-100">
                                                    <button class="btn btn-primary dropdown-toggle w-100 rounded-0" type="button" id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">
                                                        Manage Project Expenses
                                                    </button>
                                                    <ul class="dropdown-menu p-0" aria-labelledby="dropdownMenuButton1">
                                                        <li><button class="dropdown-item quickNav-dropdown-item text-center w-100 px-3 py-2 rounded-0" type="button" onclick="getAddExpenseToProjectModal();">Add Expense To Project</button></li>
                                                        <li><button class="dropdown-item quickNav-dropdown-item text-center w-100 px-3 py-2 rounded-0" type="button" onclick="recalculateAutomatedExpenses();">Recalculate Automated Expenses</button></li>
                                                    </ul>
                                                </div>
                                            </li>

                                            <!-- Project Revenues -->
                                            <?php if (isset($PERMISSIONS["ADD_INVOICES"]) || isset($PERMISSIONS["ADD_REVENUES"])) { ?>
                                                <li>    
                                                    <div class="dropdown dropstart float-end w-100">
                                                        <button class="btn btn-primary dropdown-toggle w-100 rounded-0" type="button" id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">
                                                            Manage Project Revenues
                                                        </button>
                                                        <ul class="dropdown-menu p-0" aria-labelledby="dropdownMenuButton1">
                                                            <?php if (isset($PERMISSIONS["ADD_INVOICES"])) { ?>
                                                            <li><button class="dropdown-item quickNav-dropdown-item text-center w-100 px-3 py-2 rounded-0" type="button" onclick="getProvideServiceModal();">Provide Services</button></li>
                                                            <?php } ?>

                                                            <?php if (isset($PERMISSIONS["INVOICE_OTHER_SERVICES"])) { ?>
                                                            <li><button class="dropdown-item quickNav-dropdown-item text-center w-100 px-3 py-2 rounded-0" type="button" onclick="getProvideOtherServiceModal();">Provide Other Service</button></li>
                                                            <?php } ?>

                                                            <?php if (isset($PERMISSIONS["ADD_REVENUES"])) { ?>
                                                            <li><button class="dropdown-item quickNav-dropdown-item text-center w-100 px-3 py-2 rounded-0" type="button" onclick="getAddRevenueToProjectModal();">Add "Other Revenue" To Project</button></li>
                                                            <?php } ?>
                                                        </ul>
                                                    </div>
                                                </li>
                                            <?php } ?>
                                        </ul>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>
                    </div>

                    <!-- Hidden Parameters -->
                    <div style="visibility: hidden; display: none;">
                        <input id="fixed-period" type="hidden" value="" aria-hidden="true">
                        <input id="fixed-project_code" type="hidden" value="" aria-hidden="true">
                    </div>

                    <!-- Default Page Container --> 
                    <div class="row m-0 p-0" id="default-budget-div">
                        <div class='alert alert-warning m-0'>
                            <p class='text-center m-0'>You must select both a valid <b>period</b> and <b>project</b> in order to view the project's budget.</p>
                        </div>
                    </div>

                    <!-- Project Container -->
                    <div class="row d-none m-0 p-0" id="project-budget-div">
                        <!-- Project Details -->
                        <div class="table-header d-none" id="details-div">
                            <div class="row d-flex justify-content-center align-items-center text-center p-0 my-2">
                                <div class="col-12 col-sm-12 col-md-12 col-lg-12 col-xl-12 col-xxl-12 px-2">
                                    <p class="m-0"><b>Project Director(s): </b><span id="project-directors"></span></p>
                                    <p class="m-0"><b>Number Of Employees In Project:</b> <span id="project-num_of_emps"></span></p>
                                    <p class="updated-text m-0"><b>Project Last Updated:</b> <span id="project-updated"></span></p>
                                </div>

                                <div class="col-12 col-sm-12 col-md-12 col-lg-9 col-xl-9 col-xxl-6 px-2">
                                    <div class="row">
                                        <div class="col-12 my-1">
                                            <table class="report_table-inverse w-100">
                                                <thead>
                                                    <tr>
                                                        <th class="text-center">Revenues</th>
                                                        <th class="text-center">Expenses</th>
                                                        <th class="text-center">Net Income</th>
                                                    </tr>
                                                </thead>

                                                <tbody>
                                                    <tr style="background-color: #ffffff !important;">
                                                        <td class="text-center" id="project-total_revenues">$0.00</td>
                                                        <td class="text-center" id="project-total_expenses">$0.00</td>
                                                        <td class="text-center" id="project-net_income">$0.00</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>

                                        <div class="col-12 col-sm-12 col-md-6 col-lg-6 col-xl-6 col-xxl-6 my-1">
                                            <table class="report_table-inverse w-100">
                                                <thead>
                                                    <tr>
                                                        <th class="text-center"><span title="For services that offer variable costs, this cost/unit will take the average cost/unit, rather than a fixed cost.">*Billing</span> Cost/Unit</th>
                                                        <th class="text-center">Actual Cost/Unit</th>
                                                    </tr>
                                                </thead>

                                                <tbody>
                                                    <tr style="background-color: #ffffff !important;">
                                                        <td class="text-center" id="project-ppu"></td> <!-- price per unit -->
                                                        <td class="text-center" id="project-cpu"></td> <!-- cost per unit -->
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>

                                        <div class="col-12 col-sm-12 col-md-6 col-lg-6 col-xl-6 col-xxl-6 my-1">
                                            <table class="report_table-inverse w-100">
                                                <thead>
                                                    <tr> 
                                                        <th class="text-center">Units Provided</th>
                                                        <th class="text-center">Break-Even Units</th>
                                                    </tr>
                                                </thead>

                                                <tbody>
                                                    <tr style="background-color: #ffffff !important;">
                                                        <td class="text-center" id="project-tup"></td> <!-- total units provided point -->
                                                        <td class="text-center" id="project-bep"></td> <!-- break-even point -->
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>

                                        <div class="row d-none m-0 p-0" id="fteBreakdown-div">
                                            <div class="col-12 col-sm-12 col-md-6 col-lg-6 col-xl-6 col-xxl-6 my-1">
                                                <table class="report_table-inverse w-100">
                                                    <thead>
                                                        <tr>
                                                            <th class="text-center" colspan="2">Daily Breakdown</th>
                                                        </tr>
                                                    </thead>

                                                    <tbody>
                                                        <tr style="background-color: #ffffff !important;">
                                                            <td class="text-start">Daily Rate</td>
                                                            <td class="text-end" id="dailyRate"></td>
                                                        </tr>

                                                        <tr style="background-color: #ffffff !important;">
                                                            <td class="text-start">Daily Rate w/ Unbilled Days</td>
                                                            <td class="text-end" id="dailyRateUnbilled"></td>
                                                        </tr>
                                                    </tbody>
                                                </table>

                                                <table class="report_table-inverse w-100">
                                                    <thead>
                                                        <tr>
                                                            <th class="text-center" colspan="2"><span class="text-decoration-underline" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Full Time Equivalent">FTE</span> Breakdown</th>
                                                        </tr>
                                                    </thead>

                                                    <tbody>
                                                        <tr style="background-color: #ffffff !important;">
                                                            <td class="text-start">Total <span data-bs-toggle="tooltip" data-bs-placement="bottom" title="Full Time Equivalent">FTE</span> in Project</td>
                                                            <td class="text-end" id="projectFTE"></td>
                                                        </tr>

                                                        <tr style="background-color: #ffffff !important;">
                                                            <td class="text-start">Unbillable Days Per <span data-bs-toggle="tooltip" data-bs-placement="bottom" title="Full Time Equivalent">FTE</span></td>
                                                            <td class="text-end" id="unbillableFTE"></td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>

                                            <div class="col-12 col-sm-12 col-md-6 col-lg-6 col-xl-6 col-xxl-6 my-1">
                                                <table class="report_table-inverse w-100">
                                                    <thead>
                                                        <tr> 
                                                            <th class="text-center" colspan="3">Project Leave Time</th>
                                                        </tr>

                                                        <tr> 
                                                            <th class="text-center"></th>
                                                            <th class="text-center">Days</th>
                                                            <th class="text-center">Total Cost</th>
                                                        </tr>
                                                    </thead>

                                                    <tbody>
                                                        <tr style="background-color: #ffffff !important;">
                                                            <td class="text-start">Leave Time</td>
                                                            <td class="text-end" id="leaveTimeDays"></td>
                                                            <td class="text-end" id="leaveTimeCost"></td>
                                                        </tr>

                                                        <tr style="background-color: #ffffff !important;">
                                                            <td class="text-start">Prep Work</td>
                                                            <td class="text-end" id="prepDays"></td>
                                                            <td class="text-end" id="prepCost"></td>
                                                        </tr>

                                                        <tr style="background-color: #ffffff !important;">
                                                            <td class="text-start">Personal Development</td>
                                                            <td class="text-end" id="pdDays"></td>
                                                            <td class="text-end" id="pdCost"></td>
                                                        </tr>

                                                        <tr style="background-color: #ffffff !important;">
                                                            <td class="text-start"><b>Total Unbillable</b></td>
                                                            <td class="text-end" id="unbillableDays"></td>
                                                            <td class="text-end" id="unbillableCost"></td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Project -->
                        <div class="row report-body m-0 p-0" id="project_fieldset" style="visibility: hidden;">
                            <!-- Project Buttons -->
                            <div class="btn-group w-100 m-0 p-0" role="group" aria-label="Project table selection.">
                                <button id="btn-project_employees" type="button" class="btn btn-primary btn-project_select" onclick="selectTable('project_employees');">Project Employees</button>
                                <button id="btn-project_revenues" type="button" class="btn btn-primary btn-project_select" onclick="selectTable('project_revenues');">Project Revenues</button>
                                <button id="btn-project_expenses" type="button" class="btn btn-primary btn-project_select" onclick="selectTable('project_expenses');">Project Expenses</button>
                                <?php if ($_SESSION["role"] == 1) { ?><button id="btn-project_codes" type="button" class="btn btn-primary btn-project_select" onclick="selectTable('project_codes');">Codes</button><?php } ?>
                            </div>
                        
                            <!-- Project Employees -->
                            <div id="div-project_employees" class="project-hidden p-0">
                                <!-- Project Employees Table -->
                                <table id="project_employees" class="report_table w-100">
                                    <thead>
                                        <tr>
                                            <th class="text-center py-1 px-2" colspan="4">Employee Information</th>
                                            <th class="text-center py-1 px-2" colspan="12">Employee Costs</th>
                                            <th class="text-center py-1 px-2" colspan="5">WUFAR Codes</th>
                                            <th class="text-center py-1 px-2" rowspan="2">Staff Location</th>
                                            <th class="text-center py-1 px-2" rowspan="2">Actions</th>
                                        </tr>

                                        <tr>
                                            <!-- Employee Information -->
                                            <th class="text-center py-1 px-2 project_employees_th">ID</th>
                                            <th class="text-center py-1 px-2 project_employees_th">Name</th>
                                            <th class="text-center py-1 px-2 project_employees_th">Contract Days</th>
                                            <th class="text-center py-1 px-2 project_employees_th">Days In Project</th>
                                                
                                            <!-- Employee Costs -->
                                            <th class="text-center py-1 px-2 project_employees_th">Daily Salary</th>
                                            <th class="text-center py-1 px-2 project_employees_th">Benefits</th>
                                            <th class="py-1 px-2 project_employees_th" style="text-align: center !important;">Salary In Project</th>
                                            <th class="py-1 px-2 project_employees_th" style="text-align: center !important;">FICA Costs</th>
                                            <th class="py-1 px-2 project_employees_th" style="text-align: center !important;">WRS Costs</th>
                                            <th class="py-1 px-2 project_employees_th" style="text-align: center !important;">LTD Costs</th>
                                            <th class="py-1 px-2 project_employees_th" style="text-align: center !important;">Life Insurance Costs</th>
                                            <th class="py-1 px-2 project_employees_th" style="text-align: center !important;">Health Costs</th>
                                            <th class="py-1 px-2 project_employees_th" style="text-align: center !important;">Dental Costs</th>
                                            <th class="py-1 px-2 project_employees_th" style="text-align: center !important;">Benefits In Project</th>
                                            <th class="py-1 px-2 project_employees_th" style="text-align: center !important;">Total Compensation</th>
                                            <th class="py-1 px-2 project_employees_th" style="text-align: center !important;">Daily Cost</th>

                                            <!-- WUFAR Codes -->
                                            <th class="text-center py-1 px-2 project_employees_th">Fund</th>
                                            <th class="text-center py-1 px-2 project_employees_th">Location</th>
                                            <th class="text-center py-1 px-2 project_employees_th">Object</th>
                                            <th class="text-center py-1 px-2 project_employees_th">Function</th>
                                            <th class="text-center py-1 px-2 project_employees_th">Project</th>
                                        </tr>
                                    </thead>

                                    <tfoot>
                                        <tr>
                                            <th class="px-3 py-2" style="text-align: right !important;" colspan="3">TOTALS:</th>
                                            <th class="text-center" id="project-employees-days_in_project"></th> <!-- Total Days In Project -->
                                            <th colspan="2"> <!-- spacer -->
                                            <th class="text-end" id="project-employees-salary"></th> <!-- Total Salary Sum -->
                                            <th class="text-end" id="project-employees-FICA"></th> <!-- FICA Sum -->
                                            <th class="text-end" id="project-employees-WRS"></th> <!-- WRS Sum -->
                                            <th class="text-end" id="project-employees-LTD"></th> <!-- LTD Sum -->
                                            <th class="text-end" id="project-employees-life"></th> <!-- Life Insurance Sum -->
                                            <th class="text-end" id="project-employees-health"></th> <!-- Health Insurance Sum -->
                                            <th class="text-end" id="project-employees-dental"></th> <!-- Dental Insurance Sum -->
                                            <th class="text-end" id="project-employees-benefits"></th> <!-- Benefits Sum -->
                                            <th class="text-end" id="project-employees-total"></th> <!-- Total Compensation Sum -->
                                            <th class="text-end" id="project-employees-daily_cost"></th> <!-- Daily Cost Sum -->
                                            <th colspan="7" id="project-employees-buttons"></th> <!-- Actions -->
                                        </tr>
                                    </tfoot>
                                </table>
                                <?php createTableFooterV2("project_employees", "BAP_BudgetProjects_PageLength_Employees", $USER_SETTINGS["page_length"], true, true); ?>
                            </div>

                            <!-- Project Revenues -->
                            <div id="div-project_revenues" class="project-hidden p-0">
                                <!-- Project Revenues Table -->
                                <table id="project_revenues" class="report_table w-100">
                                    <thead>
                                        <tr>
                                            <th class="text-center py-1 px-2" colspan="2">Customer Details</th>
                                            <th class="text-center py-1 px-2" colspan="2">Service Details</th>
                                            <th class="text-center py-1 px-2" colspan="5">WUFAR Codes</th>
                                            <th class="text-center py-1 px-2" colspan="4">Invoice Details</th>
                                            <th class="text-center py-1 px-2" rowspan="2">Actions</th>
                                        </tr>
                                        
                                        <tr>
                                            <!-- Customer Details -->
                                            <th class="text-center py-1 px-2 project_employees_th">Customer ID</th>
                                            <th class="text-center py-1 px-2 project_employees_th">Customer Name</th>

                                            <!-- Service Details -->
                                            <th class="text-center py-1 px-2 project_employees_th">Service ID</th>
                                            <th class="text-center py-1 px-2 project_employees_th">Service Name</th>

                                            <!-- Codes -->
                                            <th class="text-center py-1 px-2 project_employees_th">Fund</th>
                                            <th class="text-center py-1 px-2 project_employees_th">Location</th>
                                            <th class="text-center py-1 px-2 project_employees_th">Source</th>
                                            <th class="text-center py-1 px-2 project_employees_th">Function</th>
                                            <th class="text-center py-1 px-2 project_employees_th">Project</th>

                                            <!-- Invoice Details -->
                                            <th class="text-center py-1 px-2 project_employees_th">Invoice ID</th>
                                            <th class="text-center py-1 px-2 project_employees_th">Date Provided</th>
                                            <th class="text-center py-1 px-2 project_employees_th">Quantity</th>
                                            <th class="py-1 px-2 project_employees_th" style="text-align: center !important;">Amount</th>
                                        </tr>
                                    </thead>

                                    <tfoot>
                                        <tr>
                                            <th class="py-1 px-2" style="text-align: right !important;" colspan="11">TOTALS:</th>
                                            <th class="py-1 px-2" id="project-revenues-total_qty"></th> <!-- Total Quantity Sum -->
                                            <th class="py-1 px-2" id="project-revenues-total_cost"></th> <!-- Total Cost Sum -->
                                            <th class="py-1 px-2"></th> <!-- Actions -->
                                        </tr>
                                    </tfoot>
                                </table>
                                <?php createTableFooterV2("project_revenues", "BAP_BudgetProjects_PageLength_Revenues", $USER_SETTINGS["page_length"], true, true); ?>
                            </div>

                            <!-- Project Expenses -->
                            <div id="div-project_expenses" class="project-hidden p-0">
                                <!-- Project Expenses Table -->
                                <table id="project_expenses" class="report_table w-100">
                                    <thead>
                                        <tr>
                                            <th class="text-center py-1 px-2" rowspan="2">WUFAR Description</th>
                                            <th class="text-center py-1 px-2" rowspan="2">Line Item Description</th>
                                            <th class="text-center py-1 px-2" colspan="5">WUFAR Codes</th>
                                            <th class="text-center py-1 px-2" rowspan="2">Amount</th>
                                            <th class="text-center py-1 px-2" rowspan="2">Actions</th>
                                        </tr>

                                        <tr>
                                            <th class="text-center py-1 px-2 project_employees_th">Fund</th>
                                            <th class="text-center py-1 px-2 project_employees_th">Location</th>
                                            <th class="text-center py-1 px-2 project_employees_th">Object</th>
                                            <th class="text-center py-1 px-2 project_employees_th">Function</th>
                                            <th class="text-center py-1 px-2 project_employees_th">Project</th>
                                        </tr>
                                    </thead>

                                    <tfoot>
                                        <tr>
                                            <th class="py-1 px-2" style="text-align: right !important;" colspan="7">TOTALS:</th>
                                            <th class="py-1 px-2" id="project-expenses-total"></th> <!-- Total Compensation Sum -->
                                            <th class="py-1 px-2"></th> <!-- Actions -->
                                        </tr>
                                    </tfoot>
                                </table>
                                <?php createTableFooterV2("project_expenses", "BAP_BudgetProjects_PageLength_Expenses", $USER_SETTINGS["page_length"], true, true); ?>
                            </div>

                            <?php if ($_SESSION["role"] == 1) { ?>
                                <!-- Project Codes -->
                                <div id="div-project_codes" class="project-hidden p-0">
                                    <!-- Project Codes Table -->
                                    <table id="project_codes" class="report_table w-100">
                                        <thead>
                                            <tr>
                                                <th class="text-center py-1 px-2" rowspan="2">Category</th>
                                                <th class="text-center py-1 px-2" colspan="5">WUFAR Codes</th>
                                                <th class="text-center py-1 px-2" rowspan="2">Amount</th>
                                                <th class="text-center py-1 px-2" rowspan="2">Account Code</th> <!-- hidden -->
                                                <th class="text-center py-1 px-2" rowspan="2">Amount</th> <!-- hidden -->
                                            </tr>

                                            <tr>
                                                <th class="text-center py-1 px-2 project_employees_th">Fund</th>
                                                <th class="text-center py-1 px-2 project_employees_th">Location</th>
                                                <th class="text-center py-1 px-2 project_employees_th">Source/Object</th>
                                                <th class="text-center py-1 px-2 project_employees_th">Function</th>
                                                <th class="text-center py-1 px-2 project_employees_th">Project</th>
                                            </tr>
                                        </thead>

                                        <tfoot>
                                            <tr>
                                                <th class="py-1 px-2 border-0" colspan="6" id="project-codes-buttons"></th>
                                                <th class="py-1 px-2 border-0" id="project-codes-total"></th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                    <?php createTableFooterV2("project_codes", "BAP_BudgetProjects_PageLength_Codes", $USER_SETTINGS["page_length"], true, true); ?>
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>

                <!-- 
                    ### MODALS ### 
                -->
                <!-- Project Employees Modals -->
                <div id="add-employee_to_project-modal-div"></div>
                <div id="add-test_employee_to_project-modal-div"></div>
                <div id="upload-project_employees-modal-div"></div>
                <div id="upload-bulk-project_employees-modal-div"></div>
                <div id="remove-employee_from_project-modal-div"></div>
                <div id="remove-test_employee_from_project-modal-div"></div>
                <div id="edit-project_employee-modal-div"></div>
                <div id="edit-test_project_employee-modal-div"></div>

                <!-- Project Revenues Modals -->
                <div id="add-service_to_project-modal-div"></div>
                <div id="add-other_invoice-modal-div"></div>
                <div id="add-revenue_to_project-modal-div"></div>
                <div id="edit-invoice-modal-div"></div>
                <div id="edit-other_invoice-modal-div"></div>
                <div id="edit-project_revenue-modal-div"></div>
                <div id="delete-invoice-modal-div"></div>
                <div id="delete-project_revenue-modal-div"></div>
                <div id="clone-revenue-modal-div"></div>

                <!-- Project Expenses Modals -->
                <div id="add-expense_to_project-modal-div"></div>
                <div id="upload-project_expenses-modal-div"></div>
                <div id="remove-project_expense-modal-div"></div>
                <div id="edit-project_expense-modal-div"></div>
                <div id="upload-bulk-project_expenses-modal-div"></div>
                <div id="clone-expense-modal-div"></div>
                <!-- 
                    ### END MODALS ### 
                -->

                <script>
                    // get the current active period
                    let active_period = "<?php echo $active_period_label; ?>"; 

                    <?php if ($period_id != null && verifyPeriod($conn, $period_id)) {
                        /* setting period to previously selected on Manage Projects handled above */
                    } else { ?>
                        // set the search filters to values we have saved in storage
                        if (sessionStorage["BAP_ProjectsBudget_Period"] != "" && sessionStorage["BAP_ProjectsBudget_Period"] != null && sessionStorage["BAP_ProjectsBudget_Period"] != undefined) { $('#search-period').val(sessionStorage["BAP_ProjectsBudget_Period"]); }
                        else { $('#search-period').val(active_period); } // no period set; default to active period 
                    <?php } ?>

                    // run the function to create the projects dropdown
                    createProjectsDropdown();

                    <?php if ($project_code != null && verifyProject($conn, $project_code)) { ?>
                        var search_project_code = <?php echo $project_code; ?>;
                        if (search_project_code != "" && search_project_code != null && search_project_code != undefined) 
                        { 
                            document.getElementById("search-project").value = search_project_code;
                            searchProject();
                        }
                    <?php } else { ?>
                        var search_project_code = sessionStorage["BAP_ProjectsBudget_ProjectCode"];
                        if (search_project_code != "" && search_project_code != null && search_project_code != undefined) 
                        { 
                            document.getElementById("search-project").value = search_project_code;
                            searchProject();
                        }
                    <?php } ?>

                    // initialize tooltips
                    $('[data-bs-toggle="tooltip"]').tooltip();

                    // view the table stored in session
                    var view_table = sessionStorage["BAP_ProjectsBudget_ViewTable"];
                    if (view_table != null && view_table != "")
                    {
                        document.getElementById("div-"+view_table).classList.remove("project-hidden");
                        document.getElementById("btn-"+view_table).classList.add("btn-project_select-selected");
                    }
                    else
                    {
                        document.getElementById("div-project_employees").classList.remove("project-hidden");
                        document.getElementById("btn-project_employees").classList.add("btn-project_select-selected");
                    }

                    /** function to display a project's budget */
                    function searchProject()
                    {
                        // get the value of the period we are searching
                        var period = document.getElementById("search-period").value;

                        // get the project from selection dropdown
                        var project_code = document.getElementById("search-project").value;

                        if ((period != "" && period != null && period != undefined) && (project_code != "" && project_code != null && project_code != undefined))
                        {
                            // set the project as selected code
                            document.getElementById("fixed-period").value = period;
                            document.getElementById("fixed-project_code").value = project_code;

                            // update session storage stored search parameter
                            sessionStorage["BAP_ProjectsBudget_Period"] = period;
                            sessionStorage["BAP_ProjectsBudget_ProjectCode"] = project_code;

                            // if we have already drawn the tables, destroy the existing tables
                            if (drawn == 1) 
                            { 
                                $("#project_employees").DataTable().destroy(); 
                                $("#project_revenues").DataTable().destroy(); 
                                $("#project_expenses").DataTable().destroy(); 
                                $("#project_codes").DataTable().destroy(); 
                            }

                            var project_employees = $("#project_employees").DataTable({
                                ajax: {
                                    url: "ajax/projects/getProjectEmployees.php",
                                    type: "POST",
                                    data: {
                                        code: project_code,
                                        period: period
                                    }
                                },
                                async: false,
                                autoWidth: false,
                                pageLength: <?php if (isset($USER_SETTINGS["page_length"])) { echo $USER_SETTINGS["page_length"]; } else { echo 10; } ?>,
                                lengthMenu: [ [10, 25, 50, 100, 250, -1], [10, 25, 50, 100, 250, "All"] ],
                                columns: [
                                    // Employee Information
                                    { data: "id", orderable: true, visible: false, className: "text-center" },
                                    { data: "name", orderable: true },
                                    { data: "contract_days", orderable: true, className: "text-center" },
                                    { data: "project_days", orderable: true, className: "text-center" },
                                    // Employee Costs
                                    { data: "rate", orderable: true, className: "text-center" },
                                    { data: "benefits", orderable: true, visible: false },
                                    { data: "project_salary", orderable: true, className: "text-center" },
                                    { data: "FICA_Cost", orderable: true, visible: false, className: "text-center" },
                                    { data: "WRS_Cost", orderable: true, visible: false, className: "text-center" },
                                    { data: "LTD_Cost", orderable: true, visible: false, className: "text-center" },
                                    { data: "Life_Cost", orderable: true, visible: false, className: "text-center" },
                                    { data: "Health_Cost", orderable: true, visible: false, className: "text-center" },
                                    { data: "Dental_Cost", orderable: true, visible: false, className: "text-center" },
                                    { data: "project_benefits", orderable: true, className: "text-center" },
                                    { data: "project_compensation", orderable: true, className: "text-center" },
                                    { data: "daily_cost", orderable: true, className: "text-center" },
                                    // WUFAR Codes
                                    { data: "fund_code", orderable: true, visible: false, className: "text-center" },
                                    { data: "location_code", orderable: true, visible: false, className: "text-center" },
                                    { data: "object_code", orderable: true, visible: false, className: "text-center" },
                                    { data: "function_code", orderable: true, visible: false, className: "text-center" },
                                    { data: "project_code", orderable: true, visible: false, className: "text-center" },
                                    // Actions
                                    { data: "staff_location", orderable: true, visible: false, className: "text-center" },
                                    { data: "actions", orderable: false }
                                ],
                                order: [
                                    [ 1, "asc" ] // sort by last name ascending by default
                                ],
                                dom: 'rt',
                                language: {
                                    search: '<div class="input-group mb-1"><div class="input-group-prepend"><span class="d-flex align-items-center h-100 mx-2" id="nav-search-icon"><i class="fa fa-search" aria-hidden="true"></i></span></div>_INPUT_</div>',
                                    lengthMenu: '<div class="mx-1 mb-1">Show _MENU_ entries</div>',
                                    info: '<div class="mx-1">Showing _START_ to _END_ of _TOTAL_ entries</div>'
                                },
                                drawCallback: function ()
                                {
                                    var api = this.api();

                                    // initialize all the sums
                                    var salary_sum = FICA_sum = WRS_sum = LTD_sum = life_sum = health_sum = dental_sum = benefits_sum = total_sum = dailyCost_sum = 0; 

                                    // get the sum of all filtered
                                    if (api.column(3).visible()) 
                                    { 
                                        days_sum = api.column(3, { search: "applied" }).data().sum(); 
                                        document.getElementById("project-employees-days_in_project").innerHTML = numberWithCommas(days_sum);
                                    }
                                    if (api.column(6).visible()) 
                                    { 
                                        salary_sum = api.column(6, { search: "applied" }).data().sum().toFixed(2); 
                                        document.getElementById("project-employees-salary").innerHTML = "$"+numberWithCommas(salary_sum);
                                    }
                                    if (api.column(7).visible()) 
                                    { 
                                        FICA_sum = api.column(7, { search: "applied" }).data().sum().toFixed(2); 
                                        document.getElementById("project-employees-FICA").innerHTML = "$"+numberWithCommas(FICA_sum);
                                    }
                                    if (api.column(8).visible()) 
                                    { 
                                        WRS_sum = api.column(8, { search: "applied" }).data().sum().toFixed(2); 
                                        document.getElementById("project-employees-WRS").innerHTML = "$"+numberWithCommas(WRS_sum);
                                    }
                                    if (api.column(9).visible()) 
                                    { 
                                        LTD_sum = api.column(9, { search: "applied" }).data().sum().toFixed(2); 
                                        document.getElementById("project-employees-LTD").innerHTML = "$"+numberWithCommas(LTD_sum);
                                    } 
                                    if (api.column(10).visible()) 
                                    { 
                                        life_sum = api.column(10, { search: "applied" }).data().sum().toFixed(2); 
                                        document.getElementById("project-employees-life").innerHTML = "$"+numberWithCommas(life_sum);
                                    } 
                                    if (api.column(11).visible()) 
                                    { 
                                        health_sum = api.column(11, { search: "applied" }).data().sum().toFixed(2); 
                                        document.getElementById("project-employees-health").innerHTML = "$"+numberWithCommas(health_sum);
                                    } 
                                    if (api.column(12).visible()) 
                                    { 
                                        dental_sum = api.column(12, { search: "applied" }).data().sum().toFixed(2); 
                                        document.getElementById("project-employees-dental").innerHTML = "$"+numberWithCommas(dental_sum);
                                    } 
                                    if (api.column(13).visible()) 
                                    { 
                                        benefits_sum = api.column(13, { search: "applied" }).data().sum().toFixed(2); 
                                        document.getElementById("project-employees-benefits").innerHTML = "$"+numberWithCommas(benefits_sum);
                                    }
                                    if (api.column(14).visible()) 
                                    { 
                                        total_sum = api.column(14, { search: "applied" }).data().sum().toFixed(2); 
                                        document.getElementById("project-employees-total").innerHTML = "$"+numberWithCommas(total_sum);
                                    }
                                    if (api.column(15).visible()) 
                                    { 
                                        dailyCost_sum = api.column(15, { search: "applied" }).data().sum().toFixed(2); 
                                        document.getElementById("project-employees-daily_cost").innerHTML = "$"+numberWithCommas(dailyCost_sum);
                                    }
                                },
                                rowCallback: function (row, data, index)
                                {
                                    updatePageSelection("project_employees");
                                },
                            });
                            // create the column visibility buttons
                            new $.fn.dataTable.Buttons(project_employees, {
                                buttons: [
                                    {
                                        extend:    'colvis',
                                        text:      '<i class="fa-solid fa-eye fa-sm"></i>',
                                        titleAttr: 'Column Visibility',
                                        className: "m-0 px-2 py-0",
                                        columns: [0, 5, 7, 8, 9, 10, 11, 12, 15, 16, 17, 18, 19, 20, 21], // only toggle visibility for benefits and WUFAR
                                    }
                                ],
                            });
                            // add buttons to container
                            project_employees.buttons().container().appendTo("#project-employees-buttons");
                            // add additional styling to the buttons container for the project employees table
                            project_employees.buttons().container().addClass("float-end");

                            // redraw the table when column visibility is changed
                            $("#project_employees").on("column-visibility.dt", function(e, settings, column, state) {
                                project_employees.draw();
                            });

                            var project_revenues = $("#project_revenues").DataTable({
                                ajax: {
                                    url: "ajax/projects/getProjectRevenues.php",
                                    type: "POST",
                                    data: {
                                        code: project_code,
                                        period: period
                                    }
                                },
                                async: false,
                                autoWidth: false,
                                pageLength: <?php if (isset($USER_SETTINGS["page_length"])) { echo $USER_SETTINGS["page_length"]; } else { echo 10; } ?>,
                                lengthMenu: [ [10, 25, 50, 100, 250, -1], [10, 25, 50, 100, 250, "All"] ],
                                columns: [
                                    // Employee Information
                                    { data: "customer_id", orderable: true, className: "text-center" },
                                    { data: "customer_name", orderable: true, className: "text-center" },
                                    { data: "service_id", orderable: true, className: "text-center" },
                                    { data: "service_name", orderable: true, className: "text-center" },
                                    { data: "fund", orderable: true, className: "text-center" },
                                    { data: "loc", orderable: true, className: "text-center" },
                                    { data: "obj", orderable: true, className: "text-center" },
                                    { data: "func", orderable: true, className: "text-center" },
                                    { data: "proj", orderable: true, className: "text-center" },
                                    { data: "invoice_id", orderable: true, className: "text-center" },
                                    { data: "date", orderable: true, className: "text-center" },
                                    { data: "qty", orderable: true, className: "text-center" },
                                    { data: "cost", orderable: true, className: "text-end" },
                                    { data: "actions", orderable: false }
                                ],
                                order: [ // default order by service ID, then customer name
                                    [ 2, "asc" ],
                                    [ 1, "asc" ]
                                ],
                                dom: 'rt',
                                language: {
                                    search: '<div class="input-group mb-1"><div class="input-group-prepend"><span class="d-flex align-items-center h-100 mx-2" id="nav-search-icon"><i class="fa fa-search" aria-hidden="true"></i></span></div>_INPUT_</div>',
                                    lengthMenu: '<div class="mx-1 mb-1">Show _MENU_ entries</div>',
                                    info: '<div class="mx-1">Showing _START_ to _END_ of _TOTAL_ entries</div>'
                                },
                                drawCallback: function ()
                                {
                                    var api = this.api();

                                    // get the sum of all filtered
                                    var qty_sum = api.column(11, { search: "applied" }).data().sum().toFixed(2);
                                    var cost_sum = api.column(12, { search: "applied" }).data().sum().toFixed(2);

                                    // update the table footer
                                    document.getElementById("project-revenues-total_qty").innerHTML = numberWithCommas(qty_sum);
                                    document.getElementById("project-revenues-total_cost").innerHTML = "$"+numberWithCommas(cost_sum);
                                },
                                rowCallback: function (row, data, index)
                                {
                                    updatePageSelection("project_revenues");
                                },
                            });

                            var project_expenses = $("#project_expenses").DataTable({
                                ajax: {
                                    url: "ajax/projects/getProjectExpenses.php",
                                    type: "POST",
                                    data: {
                                        code: project_code,
                                        period: period
                                    }
                                },
                                async: false,
                                autoWidth: false,
                                pageLength: <?php if (isset($USER_SETTINGS["page_length"])) { echo $USER_SETTINGS["page_length"]; } else { echo 10; } ?>,
                                lengthMenu: [ [10, 25, 50, 100, 250, -1], [10, 25, 50, 100, 250, "All"] ],
                                columns: [
                                    // Employee Information
                                    { data: "name", orderable: true, width: "17.5%", className: "text-center" },
                                    { data: "desc", orderable: true, width: "23.5%", className: "text-center" },
                                    { data: "fund", orderable: true, width: "8.5%", className: "text-center" },
                                    { data: "loc", orderable: true, width: "7.5%", className: "text-center" },
                                    { data: "obj", orderable: true, width: "7.5%", className: "text-center" },
                                    { data: "func", orderable: true, width: "8.5%", className: "text-center" },
                                    { data: "proj", orderable: true, width: "7.5%", className: "text-center" },
                                    { data: "cost", orderable: true, width: "12%", className: "text-end" },
                                    { data: "actions", orderable: false, width: "7.5%" }
                                ],
                                dom: 'rt',
                                language: {
                                    search: '<div class="input-group mb-1"><div class="input-group-prepend"><span class="d-flex align-items-center h-100 mx-2" id="nav-search-icon"><i class="fa fa-search" aria-hidden="true"></i></span></div>_INPUT_</div>',
                                    lengthMenu: '<div class="mx-1 mb-1">Show _MENU_ entries</div>',
                                    info: '<div class="mx-1">Showing _START_ to _END_ of _TOTAL_ entries</div>'
                                },
                                drawCallback: function ()
                                {
                                    var api = this.api();

                                    // get the sum of all filtered
                                    var cost_sum = api.column(7, { search: "applied" }).data().sum().toFixed(2);

                                    // update the table footer
                                    document.getElementById("project-expenses-total").innerHTML = "$"+numberWithCommas(cost_sum);
                                },
                                rowCallback: function (row, data, index)
                                {
                                    updatePageSelection("project_expenses");
                                },
                            });

                            <?php if ($_SESSION["role"] == 1) { ?>
                                var project_codes = $("#project_codes").DataTable({
                                    ajax: {
                                        url: "ajax/projects/getProjectCodes.php",
                                        type: "POST",
                                        data: {
                                            code: project_code,
                                            period: period
                                        }
                                    },
                                    async: false,
                                    autoWidth: false,
                                    pageLength: <?php if (isset($USER_SETTINGS["page_length"])) { echo $USER_SETTINGS["page_length"]; } else { echo 10; } ?>,
                                    lengthMenu: [ [10, 25, 50, 100, 250, -1], [10, 25, 50, 100, 250, "All"] ],
                                    columns: [
                                        // Employee Information
                                        { data: "type", orderable: true, width: "10%", className: "text-center" },
                                        { data: "fund", orderable: true, width: "10%", className: "text-center" },
                                        { data: "location", orderable: true, width: "10%", className: "text-center" },
                                        { data: "object", orderable: true, width: "10%", className: "text-center" },
                                        { data: "function", orderable: true, width: "10%", className: "text-center" },
                                        { data: "project", orderable: true, width: "10%", className: "text-center" },
                                        { data: "amount", orderable: true, width: "40%", className: "text-end" },
                                        { data: "amount_calc", orderable: false, visible: false },
                                        { data: "account_code", orderable: false, visible: false },
                                    ],
                                    dom: 'rt',
                                    language: {
                                        search: '<div class="input-group mb-1"><div class="input-group-prepend"><span class="d-flex align-items-center h-100 mx-2" id="nav-search-icon"><i class="fa fa-search" aria-hidden="true"></i></span></div>_INPUT_</div>',
                                        lengthMenu: '<div class="mx-1 mb-1">Show _MENU_ entries</div>',
                                        info: '<div class="mx-1">Showing _START_ to _END_ of _TOTAL_ entries</div>'
                                    },
                                    drawCallback: function ()
                                    {
                                        var api = this.api();

                                        // get the sum of all filtered
                                        var cost_sum = api.column(7, { search: "applied" }).data().sum().toFixed(2);

                                        // update the table footer
                                        document.getElementById("project-codes-total").innerHTML = "$"+numberWithCommas(cost_sum);
                                    },
                                    rowCallback: function (row, data, index)
                                    {
                                        updatePageSelection("project_codes");
                                    },
                                });

                                // create the export buttons
                                new $.fn.dataTable.Buttons(project_codes, {
                                    buttons: [
                                        // CSV BUTTON
                                        {
                                            extend: "csv",
                                            exportOptions: {
                                                columns: [ 8, 7 ]
                                            },
                                            text: "<i class=\"fa-solid fa-file-csv fa-xl\"></i>",
                                            className: "btn btn-primary py-2 px-3",
                                            title: period + " - " + project_code + " - Consolidated Codes",
                                            titleAttr: "Export the consolidated codes to a .csv file",
                                            init: function(api, node, config) {
                                                // remove default button classes
                                                $(node).removeClass('dt-button');
                                                $(node).removeClass('buttons-csv');
                                                $(node).removeClass('buttons-html5');
                                            }
                                        },
                                        // EXCEL BUTTON
                                        {
                                            extend: "excel",
                                            exportOptions: {
                                                columns: [ 8, 7 ]
                                            },
                                            text: "<i class=\"fa-solid fa-file-excel fa-xl\"></i>",
                                            className: "btn btn-primary py-2 px-3",
                                            title: period + " - " + project_code + " - Consolidated Codes",
                                            titleAttr: "Export the consolidated codes to a .xlsx file",
                                            init: function(api, node, config) {
                                                // remove default button classes
                                                $(node).removeClass('dt-button');
                                                $(node).removeClass('buttons-excel');
                                                $(node).removeClass('buttons-html5');
                                            }
                                        },
                                    ]
                                });

                                // add buttons to table footer container
                                project_codes.buttons().container().appendTo("#project-codes-buttons");
                            <?php } ?>

                            // create the custom search filters
                            $("#search-project_employees").keyup(function() {
                                project_employees.search($(this).val()).draw();
                            });
                            $("#search-project_revenues").keyup(function() {
                                project_revenues.search($(this).val()).draw();
                            });
                            $("#search-project_expenses").keyup(function() {
                                project_expenses.search($(this).val()).draw();
                            });
                            $("#search-project_codes").keyup(function() {
                                project_codes.search($(this).val()).draw();
                            });

                            // create the project directors to be displayed
                            let directors_string = $.ajax({
                                type: "POST",
                                url: "ajax/projects/getProjectDirectors.php",
                                async: false,
                                data: {
                                    code: project_code,
                                }
                            }).responseText;
                            document.getElementById("project-directors").innerHTML = directors_string;

                            // create the number of employees in project to be displayed
                            let employeesInProjectCount = $.ajax({
                                type: "POST",
                                url: "ajax/projects/getEmployeesInProjectCount.php",
                                async: false,
                                data: {
                                    code: project_code,
                                    period: period
                                }
                            }).responseText;
                            document.getElementById("project-num_of_emps").innerHTML = employeesInProjectCount;

                            // calculate the total revenues
                            let total_revenue = parseFloat($.ajax({
                                type: "POST",
                                url: "ajax/projects/getProjectTotalRevenue.php",
                                async: false,
                                data: {
                                    code: project_code,
                                    period: period
                                }
                            }).responseText);

                            // calculate the total expenses
                            let total_expenses = parseFloat($.ajax({
                                type: "POST",
                                url: "ajax/projects/getProjectTotalExpenses.php",
                                async: false,
                                data: {
                                    code: project_code,
                                    period: period
                                }
                            }).responseText);

                            // get the average cost of services provided
                            let total_cost = parseFloat($.ajax({
                                type: "POST",
                                url: "ajax/projects/getProjectAvgCost.php",
                                async: false,
                                data: {
                                    code: project_code,
                                    period: period
                                }
                            }).responseText);

                            // calculate the total quantity
                            let total_qty = parseFloat($.ajax({
                                type: "POST",
                                url: "ajax/projects/getProjectTotalQty.php",
                                async: false,
                                data: {
                                    code: project_code,
                                    period: period
                                }
                            }).responseText);

                            // get project leave time array
                            let projectLeaveTime = JSON.parse($.ajax({
                                type: "POST",
                                url: "ajax/projects/getProjectLeaveTime.php",
                                async: false,
                                data: {
                                    code: project_code,
                                    period: period
                                }
                            }).responseText);
                            if (projectLeaveTime["showFTEBreakdown"] == 1)
                            {
                                document.getElementById("dailyRate").innerHTML = projectLeaveTime["dailyRate"];
                                document.getElementById("dailyRateUnbilled").innerHTML = projectLeaveTime["dailyRateUnbilled"];
                                document.getElementById("projectFTE").innerHTML = projectLeaveTime["projectFTE"];
                                document.getElementById("unbillableFTE").innerHTML = projectLeaveTime["unbillableFTE"];
                                document.getElementById("leaveTimeDays").innerHTML = projectLeaveTime["leaveTimeDays"];
                                document.getElementById("leaveTimeCost").innerHTML = projectLeaveTime["leaveTimeCost"];
                                document.getElementById("prepDays").innerHTML = projectLeaveTime["prepDays"];
                                document.getElementById("prepCost").innerHTML = projectLeaveTime["prepCost"];
                                document.getElementById("pdDays").innerHTML = projectLeaveTime["pdDays"];
                                document.getElementById("pdCost").innerHTML = projectLeaveTime["pdCost"];
                                document.getElementById("unbillableDays").innerHTML = projectLeaveTime["unbillableDays"];
                                document.getElementById("unbillableCost").innerHTML = projectLeaveTime["unbillableCost"];
                                document.getElementById("fteBreakdown-div").classList.remove("d-none");
                            } else {
                                document.getElementById("fteBreakdown-div").classList.add("d-none");
                            }

                            // calculate averages and breakeven point
                            let avg_price = (total_cost / total_qty);
                            let avg_cost = (total_expenses / total_qty);
                            let bep = (total_expenses / avg_price);

                            // calculate the net income
                            let net_income = total_revenue - total_expenses;

                            // build the average price/unit display
                            let ppu_display = "<div>";
                                ppu_display += "<span title='$"+ total_cost.toFixed(2) +"/"+ total_qty.toFixed(2) +" (total cost / total quantity)'>$" + numberWithCommas(avg_price.toFixed(2)) + "</span>";
                            ppu_display += "</div>";

                            let cpu_display = "<div>";
                                cpu_display += "<span title='$"+ total_expenses.toFixed(2) +"/"+ total_qty.toFixed(2) +" (total expenses / total quantity)'>$" + numberWithCommas(avg_cost.toFixed(2)) + "</span>";
                            cpu_display += "</div>";

                            let bep_display = "<div>";
                                bep_display += "<span title='$"+ total_expenses.toFixed(2) +"/"+ avg_price.toFixed(2) +" (total expenses / average price per unit)'>" + numberWithCommas(bep.toFixed(2)) + " units</span>";
                            bep_display += "</div>";

                            // display the global project data
                            document.getElementById("project-total_revenues").innerHTML = "$" + numberWithCommas(total_revenue.toFixed(2));
                            document.getElementById("project-total_expenses").innerHTML = "($" + numberWithCommas(total_expenses.toFixed(2)) + ")";
                            if (net_income < 0) { document.getElementById("project-net_income").innerHTML = "($" + numberWithCommas(Math.abs(net_income).toFixed(2)) + ")"; }
                            else { document.getElementById("project-net_income").innerHTML = "$" + numberWithCommas(net_income.toFixed(2)); }
                            document.getElementById("project-ppu").innerHTML = ppu_display;
                            document.getElementById("project-cpu").innerHTML = cpu_display;
                            document.getElementById("project-tup").innerHTML = numberWithCommas(total_qty.toFixed(2)) + " units";
                            document.getElementById("project-bep").innerHTML = bep_display;

                            // style the project net income table cell
                            document.getElementById("project-net_income").classList.remove("project-net_profit");
                            document.getElementById("project-net_income").classList.remove("project-net_loss");
                            if (net_income > 0) { document.getElementById("project-net_income").classList.add("project-net_profit"); }
                            else if (net_income < 0) { document.getElementById("project-net_income").classList.add("project-net_loss"); }

                            // style the cost per unit table cell
                            document.getElementById("project-cpu").classList.remove("project-net_profit");
                            document.getElementById("project-cpu").classList.remove("project-net_loss");
                            if (avg_price < avg_cost) { document.getElementById("project-cpu").classList.add("project-net_loss"); }
                            else if (avg_price > avg_cost) { document.getElementById("project-cpu").classList.add("project-net_profit"); }

                            // style the break-even point cell
                            document.getElementById("project-bep").classList.remove("project-net_profit");
                            document.getElementById("project-bep").classList.remove("project-net_loss");
                            if (total_qty < bep) { document.getElementById("project-bep").classList.add("project-net_loss"); }
                            else if (total_qty > bep) { document.getElementById("project-bep").classList.add("project-net_profit"); }

                            // get and display the time the project was last updated
                            let last_updated = $.ajax({
                                type: "POST",
                                url: "ajax/projects/getProjectLastUpdated.php",
                                async: false,
                                data: {
                                    code: project_code,
                                }
                            }).responseText;
                            document.getElementById("project-updated").innerHTML = last_updated;

                            // make the table visible
                            document.getElementById("project_fieldset").style.visibility = "visible";

                            // indicate we have drawn the table
                            drawn = 1;

                            // display the container
                            document.getElementById("default-budget-div").classList.add("d-none");
                            document.getElementById("project-budget-div").classList.remove("d-none");
                        }
                        else 
                        { 
                            // show the default container; hide the project budget container
                            document.getElementById("default-budget-div").classList.remove("d-none");
                            document.getElementById("project-budget-div").classList.add("d-none");
                        }
                    }

                    // load in the project's budget based on default values
                    searchProject();
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