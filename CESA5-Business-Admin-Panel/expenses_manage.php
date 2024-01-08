<?php 
    include_once("header.php");

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1) 
    { 
        if (isset($PERMISSIONS["VIEW_PROJECT_EXPENSES"]))
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

            ?>
                <script>
                    <?php if (isset($PERMISSIONS["ADD_PROJECT_EXPENSES"])) { ?>
                    /** function to add a new expense */
                    function addExpense()
                    {
                        // initialize the string of data to send
                        let sendString = "";
                        
                        // get the fields from the form
                        let name = encodeURIComponent(document.getElementById("add-name").value);
                        let desc = encodeURIComponent(document.getElementById("add-desc").value);
                        let loc = encodeURIComponent(document.getElementById("add-location_code").value);
                        let obj = encodeURIComponent(document.getElementById("add-object_code").value);
                        let status = encodeURIComponent(document.getElementById("add-status").value);

                        // add fields to the string of data
                        sendString += "name="+name+"&desc="+desc+"&loc="+loc+"&obj="+obj+"&status="+status;

                        // send the data to process the add customer request
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/expenses/addExpense.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // create the status modal
                                let status_title = "Add Expense Status";
                                let status_body = this.responseText;
                                createStatusModal("refresh", status_title, status_body);

                                // hide the current modal
                                $("#addExpenseModal").modal("hide");
                            }
                        };
                        xmlhttp.send(sendString);
                    }
                    <?php } ?>

                    <?php if (isset($PERMISSIONS["DELETE_PROJECT_EXPENSES"])) { ?>
                    /** function to delete the expense */
                    function deleteExpense(id)
                    {
                        // send the data to process the edit customer request
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/expenses/deleteExpense.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // create the status modal
                                let status_title = "Delete Expense Status";
                                let status_body = this.responseText;
                                createStatusModal("refresh", status_title, status_body);

                                // hide the current modal
                                $("#deleteExpenseModal").modal("hide");
                            }
                        };
                        xmlhttp.send("expense_id="+id);
                    }

                    /** function to get the delete expense modal */
                    function getDeleteExpenseModal(id)
                    {
                        // send the data to create the delete expense modal
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/expenses/getDeleteExpenseModal.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                document.getElementById("delete-expense-modal-div").innerHTML = this.responseText;     

                                // display the edit customer modal
                                $("#deleteExpenseModal").modal("show");
                            }
                        };
                        xmlhttp.send("expense_id="+id);
                    }
                    <?php } ?>

                    <?php if (isset($PERMISSIONS["EDIT_PROJECT_EXPENSES"])) { ?>
                    /** function to edit the expense */
                    function editExpense(id)
                    {
                        // initialize the string of data to send
                        let sendString = "";
                        
                        // get the fields from the form
                        let name = encodeURIComponent(document.getElementById("edit-name").value);
                        let desc = encodeURIComponent(document.getElementById("edit-desc").value);
                        let loc = encodeURIComponent(document.getElementById("edit-loc").value);
                        let obj = encodeURIComponent(document.getElementById("edit-obj").value);
                        let status = encodeURIComponent(document.getElementById("edit-status").value);

                        // add fields to the string of data
                        sendString += "expense_id="+id+"&name="+name+"&desc="+desc+"&loc="+loc+"&obj="+obj+"&status="+status;

                        // send the data to process the edit expense request
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/expenses/editExpense.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // create the status modal
                                let status_title = "Edit Expense Status";
                                let status_body = this.responseText;
                                createStatusModal("refresh", status_title, status_body);

                                // hide the current modal
                                $("#editExpenseModal").modal("hide");
                            }
                        };
                        xmlhttp.send(sendString);
                    }

                    /** function to get the edit expense modal */
                    function getEditExpenseModal(id)
                    {
                        // send the data to create the edit expense modal
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/expenses/getEditExpenseModal.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                document.getElementById("edit-expense-modal-div").innerHTML = this.responseText;     

                                // display the edit expense modal
                                $("#editExpenseModal").modal("show");
                            }
                        };
                        xmlhttp.send("expense_id="+id);
                    }
                    <?php } ?>

                    /** function to get the view expense modal */
                    function getViewExpenseModal(id)
                    {
                        // get the fixed period name
                        let period = document.getElementById("fixed-period").value;

                        // send the data to create the edit expense modal
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/expenses/getViewExpenseModal.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                document.getElementById("view-expense-modal-div").innerHTML = this.responseText;

                                // initialize the view department members table                  
                                $(document).ready(function () {
                                    var edit_department_members = $("#view-expense").DataTable({
                                        autoWidth: false,
                                        pageLength: <?php if (isset($USER_SETTINGS["page_length"])) { echo $USER_SETTINGS["page_length"]; } else { echo 10; } ?>,
                                        lengthMenu: [ [10, 25, 50, 100, 250, -1], [10, 25, 50, 100, 250, "All"] ],
                                        columns: [
                                            { orderable: true, width: "10%" },
                                            { orderable: true, width: "10%" },
                                            { orderable: true, width: "10%" },
                                            { orderable: true, width: "10%" },
                                            { orderable: true, width: "10%" },
                                            { orderable: true, width: "25%" },
                                            { orderable: true, width: "25%" }
                                        ],
                                        dom: 'rt',
                                        language: {
                                            search: '<div class="input-group mb-1"><div class="input-group-prepend"><span class="d-flex align-items-center h-100 mx-2" id="nav-search-icon"><i class="fa fa-search" aria-hidden="true"></i></span></div>_INPUT_</div>',
                                            lengthMenu: '<div class="mx-1 mb-1">Show _MENU_ entries</div>',
                                            info: '<div class="mx-1">Showing _START_ to _END_ of _TOTAL_ entries</div>'
                                        },
                                        order: [
                                            [ 4, "asc" ],
                                            [ 5, "desc" ]
                                        ],
                                        rowCallback: function (row, data, index)
                                        {
                                            updatePageSelection("view-expense");
                                        },
                                    });
                                });

                                // display the edit expense modal
                                $("#viewExpenseModal").modal("show");
                            }
                        };
                        xmlhttp.send("expense_id="+id+"&period="+period);
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

                    /** functions to look for closing/hiding of the edit expense modal */
                    $(document).on("hide.bs.modal", "#editExpenseModal", function() {
                        // delete modal from page so on edit click a new/refreshed modal appears
                        document.getElementById("edit-expense-modal-div").innerHTML = "";
                    });
                </script>

                <div class="report">
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
                                            <select class="form-select" id="search-period" name="search-period" onchange="searchExpenses();">
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

                                                <!-- Clear Filters -->
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
                                <h2 class="m-0">Manage Project Expenses</h2>
                            </div>

                            <!-- Page Management Dropdown -->
                            <div class="col-6 col-sm-4 col-md-4 col-lg-3 col-xl-2 col-xxl-2 p-0">
                                <?php if (isset($PERMISSIONS["ADD_PROJECT_EXPENSES"])) { ?>
                                    <div class="dropdown float-end">
                                        <button class="btn btn-primary dropdown-toggle" type="button" id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">
                                            Manage Expenses
                                        </button>
                                        <ul class="dropdown-menu p-0" aria-labelledby="dropdownMenuButton1">
                                            <li><button class="dropdown-item quickNav-dropdown-item text-center w-100 px-3 py-2 rounded-0" type="button" data-bs-toggle="modal" data-bs-target="#addExpenseModal">Add Expense</button></li>
                                        </ul>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>
                    </div>

                    <div class="row report-body d-none m-0" id="expenses-table-div">
                        <!-- Project Expenses Table -->
                        <table id="expenses" class="report_table w-100">
                            <thead>
                                <tr>
                                    <th class="text-center" colspan="2">Expense Details</th>
                                    <th class="text-center" colspan="2">WUFAR Codes</th>
                                    <th class="text-center" colspan="2"><span id="table-header-totals-label"></span> Totals</th>
                                    <th class="text-center" rowspan="2">Actions</th>
                                </tr>

                                <tr>
                                    <th>Name</th>
                                    <th>Description</th>
                                    <th>Location</th>
                                    <th>Object</th>
                                    <th>Quantity</th>
                                    <th style="text-align: left !important;">Costs</th>
                                </tr>
                            </thead>

                            <tfoot>
                                <tr>
                                    <th colspan="5" class="text-end px-3 py-2">TOTAL:</th>
                                    <th class="text-end px-3 py-2" id="sum-all"></th> <!-- total costs sum -->
                                    <th class="text-end px-3 py-2"></th>
                                </tr>
                            </tfoot>
                        </table>
                        <?php createTableFooter("expenses", "BAP_ProjectExpenses_PageLength", $USER_SETTINGS["page_length"], true, true); ?>
                    </div>
                </div>

                <!--
                    ### MODALS ###
                -->
                <?php if (isset($PERMISSIONS["ADD_PROJECT_EXPENSES"])) { ?>
                <!-- Add Expense Modal -->
                <div class="modal fade" tabindex="-1" role="dialog" id="addExpenseModal" aria-labelledby="addExpenseModalLabel" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header primary-modal-header">
                                <h5 class="modal-title primary-modal-title" id="addExpenseModalLabel">Add Expense</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>

                            <div class="modal-body">
                                <div class="form-row d-flex justify-content-center align-items-center my-3">
                                    <!-- Expenses Name -->
                                    <div class="form-group col-11">
                                        <label for="add-name"><span class="required-field">*</span> Name:</label>
                                        <input type="text" class="form-control w-100" id="add-name" name="add-name" required>
                                    </div>
                                </div>

                                <div class="form-row d-flex justify-content-center align-items-center my-3">
                                    <!-- Expenses Description -->
                                    <div class="form-group col-11">
                                        <label for="add-desc">Description:</label>
                                        <input type="text" class="form-control w-100" id="add-desc" name="add-desc" required>
                                    </div>
                                </div>

                                <div class="form-row d-flex justify-content-center align-items-center my-3">
                                    <!-- Location Code -->
                                    <div class="form-group col-5">
                                        <label for="add-location_code"><span class="required-field">*</span> Location Code:</label>
                                        <input type="text" class="form-control w-100" id="add-location_code" name="add-location_code" required>
                                    </div>

                                    <!-- Spacer -->
                                    <div class="form-group col-1"></div>

                                    <!-- Object Code -->
                                    <div class="form-group col-5">
                                        <label for="add-object_code"><span class="required-field">*</span> Object Code:</label>
                                        <input type="text" class="form-control w-100" id="add-object_code" name="add-object_code" required>
                                    </div>
                                </div>

                                <div class="form-row d-flex justify-content-center align-items-center my-3">
                                    <!-- Status -->
                                    <div class="form-group col-11">
                                        <label for="add-status"><span class="required-field">*</span> Status:</label>
                                        <button class="btn btn-success w-100" id="add-status" value=1 onclick="updateStatus('add-status');">Active</button>
                                    </div>
                                </div>
                            </div>

                            <div class="modal-footer">
                                <button type="button" class="btn btn-primary" onclick="addExpense();"><i class="fa-solid fa-floppy-disk"></i> Save New Expense</button>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> Close</button>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- End Add Expense Modal -->
                <?php } ?>

                <?php if (isset($PERMISSIONS["EDIT_PROJECT_EXPENSES"])) { ?>
                <!-- Edit Expense Modal -->
                <div id="edit-expense-modal-div"></div>
                <!-- End Edit Expense Modal -->
                <?php } ?>

                <?php if (isset($PERMISSIONS["DELETE_PROJECT_EXPENSES"])) { ?>
                <!-- Delete Expense Modal -->
                <div id="delete-expense-modal-div"></div>
                <!-- End Delete Expense Modal -->
                <?php } ?>

                <!-- View Expense Modal -->
                <div id="view-expense-modal-div"></div>
                <!-- End View Expense Modal -->

                <script>
                    // initialize variable to state if we've drawn the table or not
                    var drawn = 0; // assume we have not drawn the table (0)

                    // get the current active period
                    let active_period = "<?php echo $active_period_label; ?>"; 

                    // set the search filters to values we have saved in storage
                    if (sessionStorage["BAP_ProjectExpenses_Search_Period"] != "" && sessionStorage["BAP_ProjectExpenses_Search_Period"] != null && sessionStorage["BAP_ProjectExpenses_Search_Period"] != undefined) { $('#search-period').val(sessionStorage["BAP_ProjectExpenses_Search_Period"]); }
                    else { $('#search-period').val(active_period); } // no period set; default to active period 

                    /** function to search the expenses based on the period selected */
                    function searchExpenses()
                    { 
                        // get the value of the period we are searching
                        var period = document.getElementById("search-period").value;

                        if (period != "" && period != null && period != undefined)
                        {
                            // set the fixed period
                            document.getElementById("fixed-period").value = period;

                            // update the table header
                            document.getElementById("table-header-totals-label").innerHTML = period;

                            // update session storage stored search parameter
                            sessionStorage["BAP_ProjectExpenses_Search_Period"] = period;

                            // if we have already drawn the table, destroy existing table
                            if (drawn == 1) { $("#expenses").DataTable().destroy(); }

                            var expenses = $("#expenses").DataTable({
                                ajax: {
                                    url: "ajax/expenses/getExpenses.php",
                                    type: "POST",
                                    data: {
                                        period: period
                                    }
                                },
                                autoWidth: false,
                                pageLength: <?php if (isset($USER_SETTINGS["page_length"])) { echo $USER_SETTINGS["page_length"]; } else { echo 10; } ?>,
                                lengthMenu: [ [10, 25, 50, 100, 250, -1], [10, 25, 50, 100, 250, "All"] ],
                                columns: [
                                    { data: "name", orderable: true, width: "25%" },
                                    { data: "description", orderable: true, width: "32.5%" },
                                    { data: "location_code", orderable: true, width: "7.5%" },
                                    { data: "object_code", orderable: true, width: "7.5%" },
                                    { data: "total_qty", orderable: true, width: "7.5%" },
                                    { data: "total_costs", orderable: true, width: "10%", className: "text-end" },
                                    { data: "actions", orderable: false, width: "12.5%" },
                                    { data: "costs_calc", orderable: false, visible: false }
                                ],
                                dom: 'rt',
                                order: [
                                    [0, "asc"], 
                                ],
                                language: {
                                    search: '<div class="input-group mb-1"><div class="input-group-prepend"><span class="d-flex align-items-center h-100 mx-2" id="nav-search-icon"><i class="fa fa-search" aria-hidden="true"></i></span></div>_INPUT_</div>',
                                    lengthMenu: '<div class="mx-1 mb-1">Show _MENU_ entries</div>',
                                    info: '<div class="mx-1">Showing _START_ to _END_ of _TOTAL_ entries</div>'
                                },
                                drawCallback: function ()
                                {
                                    var api = this.api();

                                    // get the sum of all filtered quarterly costs
                                    let sum = api.column(7, { search: "applied" }).data().sum().toFixed(2);

                                    // update the table footer
                                    document.getElementById("sum-all").innerHTML = "$"+numberWithCommas(sum);
                                },
                                rowCallback: function (row, data, index)
                                {
                                    updatePageSelection("expenses");
                                },
                            });

                            // mark that we have drawn the table
                            drawn = 1;

                            // display the table
                            document.getElementById("expenses-table-div").classList.remove("d-none");

                            // search table by custom search filter
                            $('#search-all').keyup(function() {
                                expenses.search($(this).val()).draw();
                                sessionStorage["BAP_ProjectExpenses_Search_All"] = $(this).val();
                            });
                        }
                        else { createStatusModal("alert", "Loading Expenses Error", "Failed to load expenses. You must select a period to display expenses for."); }
                    }

                    // search expenses from the default parameters
                    searchExpenses();
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