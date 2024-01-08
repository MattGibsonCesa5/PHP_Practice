<?php 
    include_once("header.php");

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1) 
    { 
        if (isset($PERMISSIONS["VIEW_EMPLOYEE_EXPENSES"]))
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
                <script>
                    /** function to process updates when we modify an expense */
                    function modifiedExpense(expense)
                    {
                        // enable the button as we made a change
                        document.getElementById("edit-"+expense).removeAttribute("disabled");
                    }

                    /** function to save an expense */
                    function saveExpense(expense)
                    {
                        // get the new value
                        let value = document.getElementById(expense).value;
                        let code = document.getElementById(expense+"-code").value;

                        // create the string of data to send
                        let sendString = "expense="+expense+"&value="+value+"&code="+code;

                        // send the data to update the expense
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/expenses/editGlobalExpense.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                if (this.responseText == 1)
                                {
                                    // set the button to disabled as we saved the expense
                                    document.getElementById("edit-"+expense).setAttribute("disabled", true);
                                }
                            }
                        };
                        xmlhttp.send(sendString);
                    }
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
                                            <select class="form-select" id="search-period" name="search-period" onchange="showExpenses();">
                                                <?php
                                                    for ($p = 0; $p < count($periods); $p++)
                                                    {
                                                        echo "<option value='".$periods[$p]["name"]."'>".$periods[$p]["name"]."</option>";
                                                    }
                                                ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Page Header -->
                            <div class="col-12 col-sm-12 col-md-12 col-lg-12 col-xl-8 col-xxl-8 p-0">
                                <h2 class="m-0">Manage Employee Expenses</h2>
                            </div>

                            <!-- Page Management Dropdown -->
                            <div class="col-6 col-sm-4 col-md-4 col-lg-3 col-xl-2 col-xxl-2 p-0">
                                <?php if ($_SESSION["role"] == 1) { ?>
                                    <div class="dropdown float-end">
                                        <button class="btn btn-primary dropdown-toggle" type="button" id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">
                                            Manage Expenses
                                        </button>
                                        <ul class="dropdown-menu p-0" aria-labelledby="dropdownMenuButton1">
                                            <li>
                                                <form action="ajax/expenses/exportIndirectCostCodes.php" method="POST">
                                                    <input type="hidden" id="IC-export-period" name="IC-export-period" value="" aria-hidden="true">
                                                    <button id="IC-export-button" class="btn btn-primary w-100 px-3 py-2 rounded-0" type="submit" disabled>Export Indirect Cost Codes</button>
                                                </form>
                                            </li>

                                            <li>
                                                <form action="ajax/expenses/exportSupervisionCostCodes.php" method="POST">
                                                    <input type="hidden" id="SC-export-period" name="SC-export-period" value="" aria-hidden="true">
                                                    <button id="SC-export-button" class="btn btn-primary w-100 px-3 py-2 rounded-0" type="submit" disabled>Export Supervision Cost Codes</button>
                                                </form>
                                            </li>
                                        </ul>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>
                    </div>

                    <!-- Table Container -->
                    <div class="row report-body m-0" id="employeeExpensesTable-container"></div>
                </div>

                <script>
                    // initialize variable to state if we've drawn the table or not
                    var drawn = 0; // assume we have not drawn the table (0)

                    // get the current active period
                    let active_period = "<?php echo $active_period_label; ?>"; 

                    // set the search filters to values we have saved in storage
                    if (sessionStorage["BAP_EmployeeExpenses_Search_Period"] != "" && sessionStorage["BAP_EmployeeExpenses_Search_Period"] != null && sessionStorage["BAP_EmployeeExpenses_Search_Period"] != undefined) { $('#search-period').val(sessionStorage["BAP_EmployeeExpenses_Search_Period"]); }
                    else { $('#search-period').val(active_period); } // no period set; default to active period 

                    /** function to create and display the employee expenses table */
                    function showExpenses()
                    {
                        // get the value of the period we are searching
                        var period = document.getElementById("search-period").value;

                        // hide the current table
                        document.getElementById("employeeExpensesTable-container").classList.add("d-none");

                        // if we have already drawn the table, destroy existing table
                        if (drawn == 1) { $("#employeeExpensesTable").DataTable().destroy(); }

                        // disable the export buttons
                        document.getElementById("IC-export-button").setAttribute("disabled", true);
                        document.getElementById("SC-export-button").setAttribute("disabled", true);

                        // if the period is set, continue table creation
                        if (period != null && period != "" && period != undefined)
                        {
                            // update session storage stored search parameter
                            sessionStorage["BAP_EmployeeExpenses_Search_Period"] = period;

                            // create the table                      
                            var xmlhttp = new XMLHttpRequest();
                            xmlhttp.open("POST", "ajax/expenses/getEmployeeExpensesTable.php", true);
                            xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                            xmlhttp.send("period="+period);
                            xmlhttp.onreadystatechange = function() 
                            {
                                if (this.readyState == 4 && this.status == 200)
                                {
                                    // display the district report table
                                    document.getElementById("employeeExpensesTable-container").innerHTML = this.responseText;

                                    // initialize the table
                                    var expenses = $("#employeeExpensesTable").DataTable({
                                        autoWidth: false,
                                        paging: false,
                                        columns: [
                                            { orderable: true, width: "20%" },
                                            { orderable: true, width: "40%" },
                                            { orderable: true, width: "10%" },
                                            { orderable: false, width: "15%" },
                                            { orderable: true, width: "10%" },
                                            <?php if (isset($PERMISSIONS["EDIT_EMPLOYEE_EXPENSES"])) { ?>
                                            { orderable: false, width: "10%" },
                                            <?php } else { ?>
                                            { orderable: false, visible: false },
                                            <?php } ?>
                                            { orderable: false, visible: false }
                                        ],
                                        dom: 'rt',
                                        language: {
                                            search: '<div class="input-group mb-1"><div class="input-group-prepend"><span class="d-flex align-items-center h-100 mx-2" id="nav-search-icon"><i class="fa fa-search" aria-hidden="true"></i></span></div>_INPUT_</div>',
                                            lengthMenu: '<div class="mx-1 mb-1">Show _MENU_ entries</div>',
                                            info: '<div class="mx-1">Showing _START_ to _END_ of _TOTAL_ entries</div>'
                                        },
                                        paging: false,
                                        drawCallback: function ()
                                        {
                                            var api = this.api();

                                            // get the sum of all filtered expenses
                                            let sum = api.column(6, { search: "applied" }).data().sum().toFixed(2);

                                            // update the table footer
                                            document.getElementById("sum-all").innerHTML = "$"+numberWithCommas(sum);
                                        },
                                    });

                                    // mark that we have drawn the table
                                    drawn = 1;

                                    // update the export period values
                                    document.getElementById("IC-export-period").value = period;
                                    document.getElementById("SC-export-period").value = period;

                                    // enable the export buttons
                                    document.getElementById("IC-export-button").removeAttribute("disabled");
                                    document.getElementById("SC-export-button").removeAttribute("disabled");

                                    // display the table
                                    document.getElementById("employeeExpensesTable-container").classList.remove("d-none");
                                }
                            }
                        }
                    }

                    // show expense  with default parameters
                    showExpenses();
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