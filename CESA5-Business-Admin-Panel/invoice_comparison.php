<?php 
    include_once("header.php");

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1) 
    { 
        if (isset($PERMISSIONS["VIEW_INVOICES_ALL"]) || isset($PERMISSIONS["VIEW_INVOICES_ASSIGNED"]))
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

            // initialize an array to store all periods; then get all periods and store in the array
            $getCompPeriod = mysqli_query($conn, "SELECT id, name FROM periods WHERE comparison=1");
            if (mysqli_num_rows($getCompPeriod) > 0)
            {
                while ($compPeriod = mysqli_fetch_array($getCompPeriod))
                {
                    $comp_period_label = $compPeriod["name"];
                }
            }

            ?>
                <script>
                    /** function to get the invoice details modal */
                    function getInvoiceDetailsModal(invoice_id)
                    {
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/services/provided/getInvoiceDetailsModal.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // display the edit employee modal
                                document.getElementById("invoice_details-modal-div").innerHTML = this.responseText;
                                $("#invoiceDetailsModal").modal("show");
                            }
                        };
                        xmlhttp.send("invoice_id="+invoice_id);
                    }

                    /** function to get the other invoice details modal */
                    function getOtherInvoiceDetailsModal(invoice_id)
                    {
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/services/provided/getOtherInvoiceDetailsModal.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // display the edit employee modal
                                document.getElementById("other_invoice_details-modal-div").innerHTML = this.responseText;
                                $("#otherInvoiceDetailsModal").modal("show");
                            }
                        };
                        xmlhttp.send("invoice_id="+invoice_id);
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

                                                <!-- Filter By Service -->
                                                <div class="row d-flex justify-content-between align-items-center mx-0 mt-0 mb-2">
                                                    <div class="col-4 ps-0 pe-1">
                                                        <label for="search-services">Service:</label>
                                                    </div>

                                                    <div class="col-8 ps-1 pe-0">
                                                        <select class="form-select" id="search-services" name="search-services">
                                                            <option></option>
                                                            <?php
                                                                $getServices = mysqli_query($conn, "SELECT id, name FROM `services` ORDER BY name ASC");
                                                                if (mysqli_num_rows($getServices) > 0) // services exist
                                                                {
                                                                    while ($service = mysqli_fetch_array($getServices))
                                                                    {
                                                                        echo "<option>".$service["name"]."</option>";
                                                                    }
                                                                }
                                                            ?>
                                                        </select>
                                                    </div>
                                                </div>

                                                <!-- Filter By Customer -->
                                                <div class="row d-flex justify-content-between align-items-center mx-0 mt-0 mb-2">
                                                    <div class="col-4 ps-0 pe-1">
                                                        <label for="search-customers">Customer:</label>
                                                    </div>

                                                    <div class="col-8 ps-1 pe-0">
                                                        <select class="form-select" id="search-customers" name="search-customers">
                                                            <option></option>
                                                            <?php
                                                                $getCustomers = mysqli_query($conn, "SELECT id, name FROM `customers` ORDER BY name ASC");
                                                                if (mysqli_num_rows($getCustomers) > 0) // services exist
                                                                {
                                                                    while ($customer = mysqli_fetch_array($getCustomers))
                                                                    {
                                                                        echo "<option>".$customer["name"]."</option>";
                                                                    }
                                                                }
                                                            ?>
                                                        </select>
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
                                <h2 class="m-0">Invoice Comparison</h2>
                            </div>

                            <!-- Page Management Dropdown -->
                            <div class="col-6 col-sm-4 col-md-4 col-lg-3 col-xl-2 col-xxl-2 p-0"></div>
                        </div>

                        <div class="row d-flex justify-content-center pb-2 px-3">
                            <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                                <div class="input-group h-auto">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text h-100" id="nav-search-icon"><i class="fa-solid fa-calendar-days"></i></span>
                                    </div>
                                    <select class="form-select" id="search-period1" name="search-period1" onchange="searchInvoices();">
                                        <?php
                                            for ($p = 0; $p < count($periods); $p++)
                                            {
                                                echo "<option value='".$periods[$p]["name"]."'>".$periods[$p]["name"]."</option>";
                                            }
                                        ?>
                                    </select>
                                </div>
                            </div>

                            <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                                <div class="input-group h-auto">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text h-100" id="nav-search-icon"><i class="fa-solid fa-calendar-days"></i></span>
                                    </div>
                                    <select class="form-select" id="search-period2" name="search-period2" onchange="searchInvoices();">
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

                    <div class="row report-body d-none m-0" id="invoices-table-div">
                        <!-- Invoices Table -->
                        <table id="report" class="report_table w-100">
                            <thead>
                                <tr>
                                    <th class="text-center py-1 px-2" colspan="2">Service</th>
                                    <th class="text-center py-1 px-2" rowspan="2">Customer</th>
                                    <th class="text-center py-1 px-2" colspan="6"><span id="period-base-label"></span> (Base) vs. <span id="period-comp-label"> (Comp)</span></th>
                                </tr>

                                <tr>
                                    <th class="text-center py-1 px-2">ID</th>
                                    <th class="text-center py-1 px-2">Name</th>
                                    <th class="text-center py-1 px-2">Qty</th>
                                    <th class="text-center py-1 px-2">Q1</th>
                                    <th class="text-center py-1 px-2">Q2</th>
                                    <th class="text-center py-1 px-2">Q3</th>
                                    <th class="text-center py-1 px-2">Q4</th>
                                    <th class="text-center py-1 px-2" style="text-align: center !important;">Total Billed</th>
                                </tr>
                            </thead>
                        </table>
                        <?php createTableFooterV2("report", "BAP_ServicesProvided_PageLength", $USER_SETTINGS["page_length"], true, true); ?>
                    </div>
                </div>

                <!--
                    ### MODALS ###
                -->
                <!-- Invoice Details Modal -->
                <div id="invoice_details-modal-div"></div>
                <!-- End Invoice Details Modal -->

                <!-- Other Invoice Details Modal -->
                <div id="other_invoice_details-modal-div"></div>
                <!-- End Other Invoice Details Modal -->
                <!--
                    ### END MODALS ###
                -->

                <script>
                    // get the current active period
                    let active_period = "<?php echo $active_period_label; ?>"; 
                    let comparison_period = "<?php echo $comp_period_label; ?>"; 

                    // set page length to prior saved state
                    let saved_page_length = sessionStorage["BAP_ServicesProvided_PageLength"];
                    if (saved_page_length != "" && saved_page_length != null && saved_page_length != undefined)
                    {
                        $("#services_provided-DT_PageLength").val(sessionStorage["BAP_ServicesProvided_PageLength"]);
                    }

                    // set the search filters to values we have saved in storage
                    $('#search-all').val(sessionStorage["BAP_ServicesProvided_Search_All"]);
                    if (sessionStorage["BAP_InvoicesComparison_Search_Period1"] != "" && sessionStorage["BAP_InvoicesComparison_Search_Period1"] != null && sessionStorage["BAP_InvoicesComparison_Search_Period1"] != undefined) { $('#search-period1').val(sessionStorage["BAP_InvoicesComparison_Search_Period1"]); }
                    else { $('#search-period1').val(comparison_period); } // no period set; default to active period 
                    if (sessionStorage["BAP_InvoicesComparison_Search_Period2"] != "" && sessionStorage["BAP_InvoicesComparison_Search_Period2"] != null && sessionStorage["BAP_InvoicesComparison_Search_Period2"] != undefined) { $('#search-period2').val(sessionStorage["BAP_InvoicesComparison_Search_Period2"]); }
                    else { $('#search-period2').val(active_period); } // no period set; default to active period 
                    $('#search-services').val(sessionStorage["BAP_ServicesProvided_Search_Service"]);
                    $('#search-customers').val(sessionStorage["BAP_ServicesProvided_Search_Customer"]);

                    /** function to generate the invoices table based on the period selected */
                    function searchInvoices()
                    {
                        // get the value of the period we are searching
                        var period1 = document.getElementById("search-period1").value;
                        var period2 = document.getElementById("search-period2").value;

                        if ((period1 != "" && period1 != null && period1 != undefined) && (period2 != "" && period2 != null && period2 != undefined))
                        {
                            // update session storage stored search parameter
                            sessionStorage["BAP_InvoicesComparison_Search_Period1"] = period1;
                            sessionStorage["BAP_InvoicesComparison_Search_Period2"] = period2;

                            // update headers
                            $("#period-base-label").html(period1);
                            $("#period-comp-label").html(period2);

                            var report = $("#report").DataTable({
                                ajax: {
                                    url: "ajax/services/provided/getInvoiceComparisonReport.php",
                                    type: "POST",
                                    data: {
                                        period1: period1,
                                        period2: period2
                                    }
                                },
                                destroy: true,
                                autoWidth: false,
                                pageLength: <?php if (isset($USER_SETTINGS["page_length"])) { echo $USER_SETTINGS["page_length"]; } else { echo 10; } ?>,
                                lengthMenu: [ [10, 25, 50, 100, 250, -1], [10, 25, 50, 100, 250, "All"] ],
                                columns: [
                                    { data: "service_id", orderable: true, width: "5%", className: "text-center" },
                                    { data: "service_name", orderable: true, width: "12.5%", className: "text-center" },
                                    { data: "customer_name", orderable: true, width: "12.5%", className: "text-center" },                                    
                                    { data: "quantity", orderable: true, width: "7.5%", className: "text-center" }, // 10
                                    { data: "q1_cost", orderable: false, width: "12.5%", className: "text-center" }, // 5
                                    { data: "q2_cost", orderable: false, width: "12.5%", className: "text-center" },
                                    { data: "q3_cost", orderable: false, width: "12.5%", className: "text-center" },
                                    { data: "q4_cost", orderable: false, width: "12.5%", className: "text-center" },
                                    { data: "total_cost", orderable: true, width: "12.5%", className: "text-center" },
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

                                    /*
                                    // get the sum of all filtered quarterly costs
                                    let q1_sum = api.column(13, { search: "applied" }).data().sum().toFixed(2);
                                    let q2_sum = api.column(14, { search: "applied" }).data().sum().toFixed(2);
                                    let q3_sum = api.column(15, { search: "applied" }).data().sum().toFixed(2);
                                    let q4_sum = api.column(16, { search: "applied" }).data().sum().toFixed(2);

                                    // sum the total quarterly sums
                                    let quarterly_cost_sum = parseFloat(q1_sum) + parseFloat(q2_sum) + parseFloat(q3_sum) + parseFloat(q4_sum);

                                    // get the sum of all filtered
                                    let total_sum = api.column(11, { search: "applied" }).data().sum().toFixed(2);
                                    
                                    // update the table footer
                                    document.getElementById("sum-q1").innerHTML = "$"+numberWithCommas(q1_sum);
                                    document.getElementById("sum-q2").innerHTML = "$"+numberWithCommas(q2_sum);
                                    document.getElementById("sum-q3").innerHTML = "$"+numberWithCommas(q3_sum);
                                    document.getElementById("sum-q4").innerHTML = "$"+numberWithCommas(q4_sum);
                                    document.getElementById("sum-qs").innerHTML = "$"+numberWithCommas(parseFloat(quarterly_cost_sum).toFixed(2));
                                    document.getElementById("sum-total").innerHTML = "$"+numberWithCommas(total_sum);
                                    */
                                },
                                rowCallback: function (row, data, index)
                                {
                                    // initialize page selection
                                    updatePageSelection("report");
                                },
                            }); 

                            // search table by custom search filter
                            $('#search-all').keyup(function() {
                                report.search($(this).val()).draw();
                                sessionStorage["BAP_ServicesProvided_Search_All"] = $(this).val();
                            });

                            // search table by service name
                            $('#search-services').change(function() {
                                report.columns(1).search($(this).val()).draw();
                                sessionStorage["BAP_ServicesProvided_Search_Service"] = $(this).val();
                            });

                            // search table by customer name
                            $('#search-customers').change(function() {
                                report.columns(2).search($(this).val()).draw();
                                sessionStorage["BAP_ServicesProvided_Search_Customer"] = $(this).val();
                            });

                            // function to clear search filters
                            $('#clearFilters').click(function() {
                                sessionStorage["BAP_ServicesProvided_Search_Service"] = "";
                                sessionStorage["BAP_ServicesProvided_Search_Customer"] = "";
                                sessionStorage["BAP_ServicesProvided_Search_All"] = "";
                                $('#search-all').val("");
                                $('#search-services').val("");
                                $('#search-customers').val("");
                                report.search("").columns().search("").draw();
                            });            
                            
                            // search table with parameters on page load
                            if ($('#search-all').val() != "") 
                            {
                                report.search($('#search-all').val()).draw();
                            }
                            if ($('#search-services').val() != "") 
                            {
                                report.columns(1).search($('#search-services').val()).draw();
                            }
                            if ($('#search-customers').val() != "") 
                            {
                                report.columns(2).search($('#search-customers').val()).draw();
                            }
                            
                            // display the table
                            document.getElementById("invoices-table-div").classList.remove("d-none");
                        }
                    }
                    
                    // search invoices from the default parameters
                    searchInvoices();
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