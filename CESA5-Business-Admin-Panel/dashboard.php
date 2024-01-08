<?php
    include_once("header.php");
    
    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1) 
    { 
        // get addtional settings and functions
        include("getSettings.php");
        
        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);
        
        // get active period label
        $active_label = getActivePeriodLabel($conn);

        ///////////////////////////////////////////////////////////////////////////////////////////
        //
        //  DISTRICT DASHBOARD
        //
        ///////////////////////////////////////////////////////////////////////////////////////////
        if (isset($_SESSION["district"]) && $_SESSION["district"]["status"] == 1 && verifyCustomer($conn, $_SESSION["district"]["id"]))
        {
            // get customer details
            if ($customer = getCustomerDetails($conn, $_SESSION["district"]["id"]))
            {
                ?>
                    <div class="container py-3">
                        <h1 class="d-flex justify-content-center align-items-center">
                            <?php echo $customer["name"]; ?>
                            <?php if (isset($customer["logo_path"]) && $customer["logo_path"] <> "") { ?>
                                <img src="<?php echo $customer["logo_path"]; ?>" alt="District logo" height="48px;" class="mx-3"/>
                            <?php } ?>
                        </h1>
                        <?php
                            // get the number of pending contracts 
                            $pendingContracts = 0;
                            $getPending = mysqli_prepare($conn, "SELECT COUNT(cc.id) AS pendingContracts FROM contracts_created cc 
                                                                    JOIN contract_types ct ON cc.contract_type=ct.id
                                                                    JOIN periods p ON cc.period_id=p.id
                                                                    WHERE cc.customer_id=? AND cc.contract_type=1 AND p.active=1 AND cc.status=0
                                                                    ORDER BY cc.timestamp DESC");
                            mysqli_stmt_bind_param($getPending, "i", $_SESSION["district"]["id"]);
                            if (mysqli_stmt_execute($getPending))
                            {
                                $getPendingResult = mysqli_stmt_get_result($getPending);
                                if (mysqli_num_rows($getPendingResult) > 0)
                                {
                                    $pendingContracts = mysqli_fetch_assoc($getPendingResult)["pendingContracts"];
                                }
                            }
                            
                            // create alert if user has pending contracts
                            if ($pendingContracts > 0) 
                            {
                                ?>
                                    <div class="alert alert-warning">
                                        <h3 class="mb-2"><i class="fa-solid fa-triangle-exclamation"></i> <?php echo $pendingContracts; ?> pending contract<?php if ($pendingContracts != 1) { echo "s"; } ?> for <?php echo $active_label; ?></h3>
                                        <p class="mb-0">
                                            To view <b>pending contracts</b> to <b>acknowledge & approve</b>, navigate to the <b><a style="color: inherit !important;" href="customer_files.php">District Files</a></b> page.
                                        </p>
                                    </div>
                                <?php
                            }
                        ?>
                    </div>
                <?php
            }
        }
        ///////////////////////////////////////////////////////////////////////////////////////////
        //
        //  USER DASHBOARD
        //
        ///////////////////////////////////////////////////////////////////////////////////////////
        else
        {
            ?> 
                <div class="dashboard">
                    <div class="row d-flex justify-content-center m-0">
                        <!-- Period -->
                        <div class="col-12 col-sm-12 col-md-12 col-lg-12 col-xl-12 col-xxl-3 p-3">
                            <div class="card bg-primary text-white h-100">
                                <div class="card-body d-flex justify-content-center align-items-center">
                                    <i class="fa-solid fa-calendar-days fa-4x me-5"></i>
                                    <h1 class="card-title text-center m-0"><?php echo $active_label; ?></h1>
                                </div>
                            </div>
                        </div>

                        <?php if (isset($PERMISSIONS["DASHBOARD_SHOW_REVENUES_TILE"])) { ?>
                        <!-- Revenues -->
                        <div class="col-12 col-sm-12 col-md-6 col-lg-4 col-xl-4 col-xxl-3 p-3">
                            <div class="card bg-success text-white h-100">
                                <div class="card-body row">
                                    <div class="col-3 d-flex justify-content-center align-items-center">
                                        <i class="fa-solid fa-sack-dollar fa-4x"></i>
                                    </div>

                                    <div class="col-9">
                                        <h3 class="card-title">Revenues</h3>
                                        <div id="tile-data-revenues">
                                            <i class='fa-solid fa-spinner fa-spin-pulse fa-3x'></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php } ?>
                        
                        <?php if (isset($PERMISSIONS["DASHBOARD_SHOW_EXPENSES_TILE"])) { ?>
                        <!-- Expenses -->
                        <div class="col-12 col-sm-12 col-md-6 col-lg-4 col-xl-4 col-xxl-3 p-3">
                            <div class="card bg-danger text-white h-100">
                                <div class="card-body row">
                                    <div class="col-3 d-flex justify-content-center align-items-center">
                                        <i class="bi bi-graph-down-arrow" style="font-size: 56px;"></i>
                                    </div>

                                    <div class="col-9">
                                        <h3 class="card-title">Expenses</h3>
                                        <div id="tile-data-expenses">
                                            <i class='fa-solid fa-spinner fa-spin-pulse fa-3x'></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php } ?>

                        <?php if (isset($PERMISSIONS["DASHBOARD_SHOW_NET_TILE"])) { ?>
                        <!-- Net Income -->
                        <div class="col-12 col-sm-12 col-md-12 col-lg-4 col-xl-4 col-xxl-3 p-3">
                            <div class="card bg-secondary text-white h-100" id="tile-card-net">
                                <div class="card-body row">
                                    <div class="col-3 d-flex justify-content-center align-items-center">
                                        <i class="fa-solid fa-sack-dollar fa-4x"></i>
                                    </div>

                                    <div class="col-9">
                                        <h3 class="card-title">Net <span id="tile-data-net-header"></span></h3>
                                        <div id="tile-data-net-content">
                                            <i class='fa-solid fa-spinner fa-spin-pulse fa-3x'></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php } ?>

                        <?php if (isset($PERMISSIONS["DASHBOARD_SHOW_EMPLOYEES_TILE"])) { ?>
                        <!-- Employees -->
                        <div class="col-12 col-sm-12 col-md-12 col-lg-4 col-xl-4 col-xxl-3 p-3">
                            <div class="card bg-primary text-white h-100">
                                <div class="card-body row">
                                    <div class="col-3 d-flex justify-content-center align-items-center">
                                        <i class="fa-solid fa-users fa-4x"></i>
                                    </div>

                                    <div class="col-9">
                                        <h3 class="card-title">Employees</h3>
                                        <div id="tile-data-employees">
                                            <i class='fa-solid fa-spinner fa-spin-pulse fa-3x'></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php } ?>

                        <?php if (isset($PERMISSIONS["DASHBOARD_SHOW_CONTRACT_DAYS_TILE"])) { ?>
                        <!-- Contract Days -->
                        <div class="col-12 col-sm-12 col-md-6 col-lg-4 col-xl-4 col-xxl-3 p-3">
                            <div class="card bg-secondary text-white h-100" id="tile-card-days">
                                <div class="card-body row">
                                    <div class="col-3 text-center my-auto">
                                        <i class="fa-solid fa-calendar-day fa-4x"></i>
                                        <h5 class="text-center m-0" id="tile-data-days-percent"></h5>
                                    </div>

                                    <div class="col-9">
                                        <h3 class="card-title">Contract Days</h3>
                                        <div id="tile-data-days-totals">
                                            <i class='fa-solid fa-spinner fa-spin-pulse fa-3x'></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php } ?>
                        
                        <?php if (isset($PERMISSIONS["DASHBOARD_SHOW_BUDGET_ERRORS_ALL_TILE"]) || isset($PERMISSIONS["DASHBOARD_SHOW_BUDGET_ERRORS_ASSIGNED_TILE"])) { ?>
                        <!-- Budget Errors -->
                        <div class="col-12 col-sm-12 col-md-6 col-lg-4 col-xl-4 col-xxl-3 p-3">
                            <div class="card bg-secondary text-white h-100" id="tile-card-budget_errors">
                                <div class="card-body row">
                                    <div class="col-3 d-flex justify-content-center align-items-center">
                                        <i class="fa-solid fa-user-clock fa-4x"></i>
                                    </div>

                                    <div class="col-9">
                                        <h3 class="card-title">Budget Errors</h3>
                                        <div class="d-inline" id="tile-data-budget_errors">
                                            <i class='fa-solid fa-spinner fa-spin-pulse fa-3x'></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php } ?>

                        <?php if (isset($PERMISSIONS["DASHBOARD_SHOW_CASELOADS_ALL_TILE"])) { ?>
                        <!-- Caseloads Breakdown -->
                        <div class="col-12 col-sm-12 col-md-6 col-lg-4 col-xl-4 col-xxl-3 p-3">
                            <div class="card bg-primary text-white h-100">
                                <div class="card-body row">
                                    <div class="col-3 text-center my-auto">
                                        <i class="fa-solid fa-children fa-4x"></i>
                                    </div>

                                    <div class="col-9">
                                        <h3 class="card-title">Caseloads</h3>
                                        <div id="tile-data-caseloads">
                                            <i class='fa-solid fa-spinner fa-spin-pulse fa-3x'></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php } ?>
                    </div>

                    <hr>

                    <div class="row d-flex m-0">
                        <?php if (isset($PERMISSIONS["VIEW_EMPLOYEES_ALL"]) || isset($PERMISSIONS["VIEW_EMPLOYEES_ASSIGNED"]) || isset($PERMISSIONS["VIEW_DEPARTMENTS_ALL"]) || isset($PERMISSIONS["VIEW_DEPARTMENTS_ASSIGNED"]) || isset($PERMISSIONS["VIEW_SALARY_COMPARISON_STATE"]) || isset($PERMISSIONS["VIEW_SALARY_COMPARISON_INTERNAL_ALL"]) || isset($PERMISSIONS["VIEW_SALARY_COMPARISON_INTERNAL_ASSIGNED"]) || isset($PERMISSIONS["VIEW_RAISE_PROJECTION"])) { ?>
                        <div class="col-12 col-sm-12 col-md-6 col-lg-4 col-xl-4 col-xxl-3 p-3">
                            <a class="btn-dash d-flex w-100 h-100 justify-content-center align-items-center p-5" href="employees.php">Employees</a>
                        </div>
                        <?php } ?>

                        <?php if (isset($PERMISSIONS["VIEW_PROJECT_EXPENSES"]) || isset($PERMISSIONS["VIEW_EMPLOYEE_EXPENSES"])) { ?>
                        <div class="col-12 col-sm-12 col-md-6 col-lg-4 col-xl-4 col-xxl-3 p-3">
                            <a class="btn-dash d-flex w-100 h-100 justify-content-center align-items-center p-5" href="expenses.php">Expenses</a>
                        </div>
                        <?php } ?>

                        <?php if (isset($PERMISSIONS["VIEW_SERVICES_ALL"]) || isset($PERMISSIONS["VIEW_SERVICES_ASSIGNED"]) || isset($PERMISSIONS["VIEW_INVOICES_ALL"]) || isset($PERMISSIONS["VIEW_INVOICES_ASSIGNED"]) || isset($PERMISSIONS["VIEW_OTHER_SERVICES"]) || isset($PERMISSIONS["VIEW_REVENUES_ALL"]) || isset($PERMISSIONS["VIEW_REVENUES_ASSIGNED"])) { ?>
                        <div class="col-12 col-sm-12 col-md-6 col-lg-4 col-xl-4 col-xxl-3 p-3">
                            <a class="btn-dash d-flex w-100 h-100 justify-content-center align-items-center p-5" href="services.php">Services</a>
                        </div>
                        <?php } ?>

                        <?php if (isset($PERMISSIONS["VIEW_PROJECTS_ALL"]) || isset($PERMISSIONS["VIEW_PROJECTS_ASSIGNED"]) || isset($PERMISSIONS["VIEW_PROJECT_BUDGETS_ALL"]) || isset($PERMISSIONS["VIEW_PROJECT_BUDGETS_ASSIGNED"])) { ?>
                        <div class="col-12 col-sm-12 col-md-6 col-lg-4 col-xl-4 col-xxl-3 p-3">
                            <a class="btn-dash d-flex w-100 h-100 justify-content-center align-items-center p-5" href="projects.php">Projects</a>
                        </div>
                        <?php } ?>

                        <?php if (isset($PERMISSIONS["VIEW_CUSTOMERS"]) || isset($PERMISSIONS["VIEW_CUSTOMER_GROUPS"])) { ?>
                        <div class="col-12 col-sm-12 col-md-6 col-lg-4 col-xl-4 col-xxl-3 p-3">
                            <a class="btn-dash d-flex w-100 h-100 justify-content-center align-items-center p-5" href="customers.php">Customers</a>
                        </div>
                        <?php } ?>

                        <?php if (isset($PERMISSIONS["VIEW_CASELOADS_ALL"]) || isset($PERMISSIONS["VIEW_CASELOADS_ASSIGNED"]) || isset($PERMISSIONS["VIEW_STUDENTS_ALL"]) || isset($PERMISSIONS["VIEW_STUDENTS_ASSIGNED"]) || isset($PERMISSIONS["VIEW_THERAPISTS"])) { ?>
                        <div class="col-12 col-sm-12 col-md-6 col-lg-4 col-xl-4 col-xxl-3 p-3">
                            <a class="btn-dash d-flex w-100 h-100 justify-content-center align-items-center p-5" href="caseloads.php">Caseloads</a>
                        </div>
                        <?php } ?>

                        <?php if (isset($PERMISSIONS["VIEW_REPORT_MISBUDGETED_EMPLOYEES_ALL"]) || isset($PERMISSIONS["VIEW_REPORT_MISBUDGETED_EMPLOYEES_ASSIGNED"]) || 
                                    isset($PERMISSIONS["VIEW_REPORT_BUDGETED_INACTIVE_EMPLOYEES_ALL"]) || isset($PERMISSIONS["VIEW_REPORT_BUDGETED_INACTIVE_EMPLOYEES_ASSIGNED"]) || 
                                    isset($PERMISSIONS["VIEW_REPORT_TEST_EMPLOYEES_ALL"]) || isset($PERMISSIONS["VIEW_REPORT_TEST_EMPLOYEES_ASSIGNED"]) || 
                                    isset($PERMISSIONS["VIEW_REPORT_SALARY_PROJECTION_ALL"]) || isset($PERMISSIONS["VIEW_REPORT_SALARY_PROJECTION_ASSIGNED"]) || 
                                    isset($PERMISSIONS["VIEW_REPORT_EMPLOYEE_CHANGES_ALL"]) || isset($PERMISSIONS["VIEW_REPORT_EMPLOYEE_CHANGES_ASSIGNED"])) 
                        { ?>
                        <div class="col-12 col-sm-12 col-md-6 col-lg-4 col-xl-4 col-xxl-3 p-3">
                            <a class="btn-dash d-flex w-100 h-100 justify-content-center align-items-center p-5" href="reports.php">Reports</a>
                        </div>
                        <?php } ?>

                        <?php if ($_SESSION["role"] == 1) { ?>
                        <div class="col-12 col-sm-12 col-md-6 col-lg-4 col-xl-4 col-xxl-3 p-3">
                            <a class="btn-dash d-flex w-100 h-100 justify-content-center align-items-center p-5" href="manage.php">Manage</a>
                        </div>
                        <?php } ?>
                    </div>
                </div>

                <!-- Modals -->
                <div id="QC-Modal-Div"></div>
                <div id="Expenses-Modal-Div"></div>
                <div id="Net-Modal-Div"></div>
                <!-- End Modals -->

                <script>
                    <?php if (isset($PERMISSIONS["DASHBOARD_SHOW_REVENUES_TILE"])) { ?>
                    /** function to create the revenues tile */
                    function getRevenuesTile()
                    {
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/dashboard/getRevenuesTile.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                document.getElementById("tile-data-revenues").innerHTML = this.responseText;
                            }
                        }
                        xmlhttp.send();
                    }
                    getRevenuesTile();

                    /** function to create the quarterly cost breakdown modal */
                    function getQuarterlyBreakdownModal()
                    {
                        // update the plus icon to a processing spinner
                        document.getElementById("revenues-breakdown-btn").innerHTML = "<i class='fa-solid fa-spinner fa-spin-pulse fa-xl'></i>";
                        document.getElementById("revenues-breakdown-btn").setAttribute("disabled", "true");

                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/dashboard/getQuarterlyCostBreakdownModal.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // load the modal into the container
                                document.getElementById("QC-Modal-Div").innerHTML = this.responseText;
                                
                                // initialize the quarterly costs table                  
                                $(document).ready(function () {
                                    var quarterlyCostsBreakdownTable = $("#quarterlyCostsBreakdownTable").DataTable({
                                        autoWidth: false,
                                        pageLength: -1,
                                        columns: [
                                            { orderable: true },
                                            { orderable: true },
                                            { orderable: true },
                                            { orderable: true },
                                            { orderable: true }
                                        ],
                                        dom: 'rtp',
                                        paging: false,
                                        order: [
                                            [ 0, "asc" ]
                                        ]
                                    });
                                });

                                // display the modal
                                $("#quarterlyCostBreakdownModal").modal("show");

                                // reset the plus icon
                                document.getElementById("revenues-breakdown-btn").innerHTML = "<i class='fa-solid fa-plus fa-xl'></i>";
                                document.getElementById("revenues-breakdown-btn").removeAttribute("disabled");
                            }
                        };
                        xmlhttp.send();
                    }
                    <?php } ?>

                    <?php if (isset($PERMISSIONS["DASHBOARD_SHOW_EXPENSES_TILE"])) { ?>
                    /** function to create the expenses tile */
                    function getExpensesTile()
                    {
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/dashboard/getExpensesTile.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                document.getElementById("tile-data-expenses").innerHTML = this.responseText;
                            }
                        }
                        xmlhttp.send();
                    }
                    getExpensesTile();

                    /** function to create the expenses breakdown modal */
                    function getExpensesBreakdownModal()
                    {
                        // update the plus icon to a processing spinner
                        document.getElementById("expenses-breakdown-btn").innerHTML = "<i class='fa-solid fa-spinner fa-spin-pulse fa-xl'></i>";
                        document.getElementById("expenses-breakdown-btn").setAttribute("disabled", "true");

                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/dashboard/getExpensesBreakdownModal.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // load the modal into the container
                                document.getElementById("Expenses-Modal-Div").innerHTML = this.responseText;
                                
                                // display the modal
                                $("#expensesBreakdownModal").modal("show");

                                // reset the plus icon
                                document.getElementById("expenses-breakdown-btn").innerHTML = "<i class='fa-solid fa-plus fa-xl'></i>";
                                document.getElementById("expenses-breakdown-btn").removeAttribute("disabled");
                            }
                        };
                        xmlhttp.send();
                    }
                    <?php } ?>
                    
                    <?php if (isset($PERMISSIONS["DASHBOARD_SHOW_NET_TILE"])) { ?>
                    /** function to create the net tile */
                    function getNetTile()
                    {
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/dashboard/getNetTile.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                let response = JSON.parse(this.responseText);
                                let net = response["net"];
                                let content = response["content"];

                                // display the content
                                document.getElementById("tile-data-net-content").innerHTML = content;

                                // update the net header
                                if (net < 0) { document.getElementById("tile-data-net-header").innerHTML = "Loss"; }
                                else { document.getElementById("tile-data-net-header").innerHTML = "Gain"; }

                                // update the class of the card
                                document.getElementById("tile-card-net").classList.remove("bg-secondary");
                                if (net > 0) { document.getElementById("tile-card-net").classList.add("bg-success"); }
                                else if (net < 0) { document.getElementById("tile-card-net").classList.add("bg-danger"); }
                                else { document.getElementById("tile-card-net").classList.add("bg-secondary"); }
                            }
                        }
                        xmlhttp.send();
                    }
                    getNetTile();

                    /** function to create the net breakdown modal */
                    function getNetBreakdownModal()
                    {
                        // update the plus icon to a processing spinner
                        document.getElementById("net-breakdown-btn").innerHTML = "<i class='fa-solid fa-spinner fa-spin-pulse fa-xl'></i>";
                        document.getElementById("net-breakdown-btn").setAttribute("disabled", "true");

                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/dashboard/getNetBreakdownModal.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // load the modal into the container
                                document.getElementById("Net-Modal-Div").innerHTML = this.responseText;
                                
                                // initialize the quarterly costs table                  
                                $(document).ready(function () {
                                    var netBreakdownTable = $("#netBreakdownTable").DataTable({
                                        autoWidth: false,
                                        pageLength: -1,
                                        columns: [
                                            { orderable: true },
                                            { orderable: true },
                                            { orderable: true },
                                            { orderable: true },
                                            { orderable: true }
                                        ],
                                        dom: 'rtp',
                                        paging: false,
                                        order: [
                                            [ 0, "asc" ]
                                        ]
                                    });
                                });

                                // display the modal
                                $("#netBreakdownModal").modal("show");

                                // reset the plus icon
                                document.getElementById("net-breakdown-btn").innerHTML = "<i class='fa-solid fa-plus fa-xl'></i>";
                                document.getElementById("net-breakdown-btn").removeAttribute("disabled");
                            }
                        };
                        xmlhttp.send();
                    }
                    <?php } ?>

                    <?php if (isset($PERMISSIONS["DASHBOARD_SHOW_EMPLOYEES_TILE"])) { ?>
                    /** function to create the employees tile */
                    function getEmployeesTile()
                    {
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/dashboard/getEmployeesTile.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                document.getElementById("tile-data-employees").innerHTML = this.responseText;
                            }
                        }
                        xmlhttp.send();
                    }
                    getEmployeesTile();
                    <?php } ?>

                    <?php if (isset($PERMISSIONS["DASHBOARD_SHOW_CONTRACT_DAYS_TILE"])) { ?>
                    /** function to create the contract days tile */
                    function getDaysTiles()
                    {
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/dashboard/getDaysTile.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // get the content
                                let response = JSON.parse(this.responseText);
                                let percent = response["percent"];
                                let content = response["content"];
                                
                                // update the content
                                document.getElementById("tile-data-days-totals").innerHTML = content;
                                if (percent != 100) {
                                    if ((percent < 100 && percent >= 99.9) || (percent > 100 && percent <= 100.1)) {
                                        document.getElementById("tile-data-days-percent").innerHTML = percent.toFixed(2)+"%";
                                    } else {
                                        document.getElementById("tile-data-days-percent").innerHTML = percent.toFixed(1)+"%";
                                    }
                                } else {
                                    document.getElementById("tile-data-days-percent").innerHTML = percent+"%";
                                }

                                // update the class of the card
                                document.getElementById("tile-card-days").classList.remove("bg-secondary");
                                if (percent > 100) { document.getElementById("tile-card-days").classList.add("bg-danger"); }
                                else if (percent < 100) { document.getElementById("tile-card-days").classList.add("bg-warning"); }
                                else { document.getElementById("tile-card-days").classList.add("bg-success"); }
                            }
                        }
                        xmlhttp.send();
                    }
                    getDaysTiles();
                    <?php } ?>

                    <?php if (isset($PERMISSIONS["DASHBOARD_SHOW_BUDGET_ERRORS_ALL_TILE"]) || isset($PERMISSIONS["DASHBOARD_SHOW_BUDGET_ERRORS_ASSIGNED_TILE"])) { ?>
                    /** function to create the contract days tile */
                    function getBudgetErrorsTile()
                    {
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/dashboard/getBudgetErrorsTile.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // get the content
                                let response = JSON.parse(this.responseText);
                                let count = response["count"];
                                let content = response["content"];

                                // display the content
                                document.getElementById("tile-data-budget_errors").innerHTML = content;

                                // update the class of the card
                                document.getElementById("tile-card-budget_errors").classList.remove("bg-secondary");
                                if (count == 0) { document.getElementById("tile-card-budget_errors").classList.add("bg-success"); }
                                else { document.getElementById("tile-card-budget_errors").classList.add("bg-danger"); }
                            }
                        }
                        xmlhttp.send();
                    }
                    getBudgetErrorsTile();
                    <?php } ?>

                    <?php if (isset($PERMISSIONS["DASHBOARD_SHOW_CASELOADS_ALL_TILE"])) { ?>
                    /** function to create the caseloads tile */
                    function getCaseloadsTile()
                    {
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/dashboard/getCaseloadsTile.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                document.getElementById("tile-data-caseloads").innerHTML = this.responseText;
                            }
                        }
                        xmlhttp.send();
                    }
                    getCaseloadsTile();
                    <?php } ?>
                </script>
            <?php
        }

        // disconnect from the database
        mysqli_close($conn);
    }
    else { goToLogin(); }
    
    include_once("footer.php"); 
?>