<?php 
    include_once("header.php");

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1) 
    { 
        if (isset($PERMISSIONS["EXPORT_INVOICES"]))
        {
            // connect to the database
            $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

            ?>
                <script>
                    // initialize the variable to indicate if we have drawn the table
                    var drawn = 0;

                    /** function to lock/unlock the quarter after export */
                    function lockClicked()
                    {
                        let element = document.getElementById("export-lock");
                        let status = element.value;

                        // button is currently set to locked
                        if (status == 1)
                        {
                            element.classList.remove("btn-danger");
                            element.classList.add("btn-secondary");
                            element.innerHTML = "<i class=\"fa-solid fa-unlock\"></i>";
                            element.value = 0;
                        }
                        // button is currently set to unlocked (... or other...)
                        else
                        {
                            element.classList.remove("btn-secondary");
                            element.classList.add("btn-danger");
                            element.innerHTML = "<i class=\"fa-solid fa-lock\"></i>";
                            element.value = 1;
                        }
                    }

                    /** function to export the invoice of the selected quarter */
                    function exportInvoice()
                    {
                        // get the values of the parameters; then create the string storing the values
                        let quarter = document.getElementById("export-quarter").value;
                        let locked = document.getElementById("export-lock").value;
                        let invoice_description = document.getElementById("export-desc").value;

                        // get today's date
                        let today = new Date().toLocaleDateString();

                        // create the file tile
                        let title = "Q"+quarter+" Skyward Invoice Export - "+today;

                        // if we have already drawn the table, destroy existing table
                        if (drawn == 1) { $("#invoice").DataTable().destroy(); }

                        var invoice = $("#invoice").DataTable({
                            ajax: {
                                url: "ajax/invoices/exportInvoice.php",
                                type: "POST",
                                data: {
                                    quarter: quarter,
                                    locked: locked
                                }
                            },
                            autoWidth: false,
                            pageLength: -1,
                            columns: [
                                { data: "customer_id", orderable: true, width: "6%" },
                                { defaultContent: "ITEM", orderable: false, width: "6%" },
                                { data: "service_label", orderable: true, width: "20%" },
                                { defaultContent: invoice_description, orderable: false, width: "20%" },
                                { defaultContent: 1, orderable: false, width: "5%" },
                                { data: "cost", orderable: true, width: "10%" },
                                { defaultContent: "", orderable: false, width: "1%" },
                                { data: "CR_Acct", orderable: true, width: "20%" },
                                { defaultContent: "", orderable: false, width: "1%" },
                                { defaultContent: "", orderable: false, width: "1%" },
                                { data: "date", orderable: false, width: "10%" },
                            ],
                            buttons: [
                                // CSV BUTTON
                                {
                                    extend: "csv",
                                    text: "<i class=\"fa-solid fa-file-csv fa-xl\"></i>",
                                    className: "btn btn-primary ms-1 py-2 px-3",
                                    title: title,
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
                                    text: "<i class=\"fa-solid fa-file-excel fa-xl\"></i>",
                                    className: "btn btn-primary ms-1 py-2 px-3",
                                    title: title,
                                    init: function(api, node, config) {
                                        // remove default button classes
                                        $(node).removeClass('dt-button');
                                        $(node).removeClass('buttons-excel');
                                        $(node).removeClass('buttons-html5');
                                    }
                                },
                            ],
                            dom: 'Bfrtip',
                            language: {
                                search: '<div class="input-group mb-1"><div class="input-group-prepend"><span class="d-flex align-items-center h-100 mx-2" id="nav-search-icon"><i class="fa fa-search" aria-hidden="true"></i></span></div>_INPUT_</div>',
                                lengthMenu: '<div class="mx-1 mb-1">Show _MENU_ entries</div>',
                                info: '<div class="mx-1">Showing _START_ to _END_ of _TOTAL_ entries</div>'
                            },
                            paging: false,
                            drawCallback: function ()
                            {
                                var api = this.api();

                                // get the sum of all filtered invoice quarterly costs
                                let sum = api.column(5, { search: "applied" }).data().sum().toFixed(2);

                                // update the table footer
                                document.getElementById("sum-costs").innerHTML = "$"+numberWithCommas(sum);
                            },
                        });

                        // make the table visible
                        document.getElementById("invoice").style.visibility = "visible";

                        // indicate we have drawn the table
                        drawn = 1;
                    }
                </script>

                <div class="report">
                    <div class="row report-header mb-3 mx-0"> 
                        <div class="col-3 p-0"></div>
                        <div class="col-6 p-0">
                            <fieldset class="border p-2">
                                <legend class="float-none w-auto px-4 py-0 m-0"><h1 class="report-title m-0">Export Quarterly Invoices</h1></legend>
                                <div class="report-description">
                                    Select the quarter of the invoice you want to export. We'll export this quarterly invoice in the format required to upload into Skyward.
                                </div>
                            </fieldset>
                        </div>
                        <div class="col-3 p-0"></div>
                    </div>

                    <div class="row align-items-center align-middle mb-3 mx-0"> 
                        <div class="col-4 p-0"></div>
                        <div class="col-1 p-0"><label for="export-desc">Invoice Description:</label></div>
                        <div class="col-3 p-0"><input type="text" class="form-control w-100" id="export-desc" name="export-desc"></div>
                        <div class="col-4 p-0"></div>
                    </div>

                    <div class="row mb-3 mx-0"> 
                        <div class="col-4 p-0"></div>
                        <div class="col-4 p-0">
                            <div class="input-group w-100 h-auto">
                                <select class="form-select" id="export-quarter" name="export-quarter">
                                    <option value="1">Quarter 1</option>
                                    <option value="2">Quarter 2</option>
                                    <option value="3">Quarter 3</option>
                                    <option value="4">Quarter 4</option>
                                </select>
                                <button class="btn btn-primary" type="button" onclick="exportInvoice();">Export Invoice</button>
                                <button class="btn btn-secondary ms-2" type="button" id="export-lock" value=0 onclick="lockClicked();"><i class="fa-solid fa-unlock"></i></button>
                            </div>
                        </div>
                        <div class="col-4 p-0"></div>
                    </div>

                    <div class="row report-body m-0">
                        <table id="invoice" class="report_table w-100" style="visibility: hidden;">
                            <thead>
                                <tr>
                                    <th>cust#</th>
                                    <th>Item</th>
                                    <th>Item Description</th>
                                    <th>Invoice Description</th>
                                    <th>Qty</th>
                                    <th>Unit $</th>
                                    <th></th>
                                    <th>CR Acct #</th>
                                    <th></th>
                                    <th></th>
                                    <th>Inv Date</th>
                                </tr>
                            </thead>

                            <tfoot>
                                <tr>
                                    <th colspan="5" class="text-end px-3 py-2">TOTAL:</th>
                                    <th class="text-end px-3 py-2" id="sum-costs"></th> <!-- total costs sum -->
                                    <th colspan="5" class="text-end px-3 py-2"></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            <?php 

            // disconnect from the database
            mysqli_close($conn);
        }
        else { denyAccess(); }
    }
    else { goToLogin(); }

    include("footer.php"); 
?>