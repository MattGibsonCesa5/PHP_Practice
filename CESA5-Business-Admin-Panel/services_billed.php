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

            ?>
                <script>
                    // initialize the variable to indicate if we have drawn the table
                    var drawn = 0;

                    <?php if (isset($PERMISSIONS["ADD_INVOICES"])) { ?>
                    /** function to get the modal to provide a service */
                    function getProvideServiceModal()
                    {
                        // send the data to create the delete invoice modal
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/services/provided/getProvideServiceModal.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                document.getElementById("provide-service-modal-div").innerHTML = this.responseText;     

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
                        xmlhttp.send();
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
                    <?php } ?>

                    <?php if (isset($PERMISSIONS["DELETE_INVOICES"])) { ?>
                    /** function to get the delete invoice modal */
                    function getDeleteInvoiceModal(id)
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
                        xmlhttp.send("invoice_id="+id);
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
                    <?php } ?>

                    <?php if (isset($PERMISSIONS["EDIT_INVOICES"])) { ?>
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

                                // display the edit invoice modal
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
                    <?php } ?>

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
                                    // display quantity div
                                    document.getElementById(mode+"-quantity-div").classList.remove("d-none");
                                    document.getElementById(mode+"-quantity-div").classList.add("d-flex");

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
                                    // display quantity div
                                    document.getElementById(mode+"-quantity-div").classList.remove("d-none");
                                    document.getElementById(mode+"-quantity-div").classList.add("d-flex");

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

                    /** function to check the quarterly costs to verify their update status */
                    function checkQuarterlyCosts(invoice_id)
                    {
                        // get the set quarterly costs
                        let q1_cost = document.getElementById("edit-q1_cost-"+invoice_id).value;
                        let q2_cost = document.getElementById("edit-q2_cost-"+invoice_id).value;
                        let q3_cost = document.getElementById("edit-q3_cost-"+invoice_id).value;
                        let q4_cost = document.getElementById("edit-q4_cost-"+invoice_id).value;

                        // send data to get the status
                        let result = JSON.parse($.ajax({
                            type: "POST",
                            url: "ajax/services/provided/checkQuarterlyCosts.php",
                            async: false,
                            data : {
                                invoice_id: invoice_id,
                                q1: q1_cost,
                                q2: q2_cost,
                                q3: q3_cost,
                                q4: q4_cost
                            }
                        }).responseText);

                        let status = result["status"];
                        let sum = result["sum"];

                        // update the quarterly cost sum
                        document.getElementById("edit-quarterly_cost_sum-"+invoice_id).value = sum;

                        // update status icon
                        if (status == 1) // success 
                        { 
                            document.getElementById("edit-status-"+invoice_id).innerHTML = "<i class='fa-solid fa-check'></i>";
                            document.getElementById("edit-status-"+invoice_id).classList.remove("btn-danger");
                            document.getElementById("edit-status-"+invoice_id).classList.add("btn-success");
                            document.getElementById("edit-update-"+invoice_id).removeAttribute("disabled");
                        }
                        else // failed
                        {
                            document.getElementById("edit-status-"+invoice_id).innerHTML = "<i class='fa-solid fa-xmark'></i>";
                            document.getElementById("edit-status-"+invoice_id).classList.remove("btn-success");
                            document.getElementById("edit-status-"+invoice_id).classList.add("btn-danger");
                            document.getElementById("edit-update-"+invoice_id).setAttribute("disabled", true);
                        }
                    }

                    /** function to update the quarterly costs */
                    function updateQuarterlyCosts(invoice_id)
                    {
                        // get the period
                        let period = document.getElementById("fixed-period").value;

                        // get the set quarterly costs
                        let q1_cost = document.getElementById("edit-q1_cost-"+invoice_id).value;
                        let q2_cost = document.getElementById("edit-q2_cost-"+invoice_id).value;
                        let q3_cost = document.getElementById("edit-q3_cost-"+invoice_id).value;
                        let q4_cost = document.getElementById("edit-q4_cost-"+invoice_id).value;

                        // send data to get the updated costs
                        let updated_costs = $.ajax({
                            type: "POST",
                            url: "ajax/services/provided/updateQuarterlyCosts.php",
                            async: false,
                            data : {
                                invoice_id: invoice_id,
                                q1: q1_cost,
                                q2: q2_cost,
                                q3: q3_cost,
                                q4: q4_cost,
                                period: period
                            }
                        }).responseText;

                        window.location.reload();
                    }

                    /** function to reset the quarterly costs */
                    function resetQuarterlyCosts(invoice_id)
                    {
                        // send data to get the current costs
                        let reset_costs = JSON.parse($.ajax({
                            type: "POST",
                            url: "ajax/services/provided/resetEstimatedQuarterlyCosts.php",
                            async: false,
                            data : {
                                invoice_id: invoice_id
                            }
                        }).responseText);

                        // reset the input boxes with the current costs
                        document.getElementById("edit-q1_cost-"+invoice_id).value = reset_costs["q1"];
                        document.getElementById("edit-q2_cost-"+invoice_id).value = reset_costs["q2"];
                        document.getElementById("edit-q3_cost-"+invoice_id).value = reset_costs["q3"];
                        document.getElementById("edit-q4_cost-"+invoice_id).value = reset_costs["q4"];

                        // check the quarterly costs to reverify
                        checkQuarterlyCosts(invoice_id);
                    }

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

                    /** function to check the quarterly costs to verify their update status */
                    function checkOtherQuarterlyCosts(invoice_id)
                    {
                        // get the set quarterly costs
                        let q1_cost = document.getElementById("edit-q1_cost-"+invoice_id).value;
                        let q2_cost = document.getElementById("edit-q2_cost-"+invoice_id).value;
                        let q3_cost = document.getElementById("edit-q3_cost-"+invoice_id).value;
                        let q4_cost = document.getElementById("edit-q4_cost-"+invoice_id).value;

                        // send data to get the status
                        let result = JSON.parse($.ajax({
                            type: "POST",
                            url: "ajax/services/provided/checkOtherQuarterlyCosts.php",
                            async: false,
                            data : {
                                invoice_id: invoice_id,
                                q1: q1_cost,
                                q2: q2_cost,
                                q3: q3_cost,
                                q4: q4_cost
                            }
                        }).responseText);

                        let status = result["status"];
                        let sum = result["sum"];

                        // update the quarterly cost sum
                        document.getElementById("edit-quarterly_cost_sum-"+invoice_id).value = sum;

                        // update status icon
                        if (status == 1) // success 
                        { 
                            document.getElementById("edit-status-"+invoice_id).innerHTML = "<i class='fa-solid fa-check'></i>";
                            document.getElementById("edit-status-"+invoice_id).classList.remove("btn-danger");
                            document.getElementById("edit-status-"+invoice_id).classList.add("btn-success");
                            document.getElementById("edit-update-"+invoice_id).removeAttribute("disabled");
                        }
                        else // failed
                        {
                            document.getElementById("edit-status-"+invoice_id).innerHTML = "<i class='fa-solid fa-xmark'></i>";
                            document.getElementById("edit-status-"+invoice_id).classList.remove("btn-success");
                            document.getElementById("edit-status-"+invoice_id).classList.add("btn-danger");
                            document.getElementById("edit-update-"+invoice_id).setAttribute("disabled", true);
                        }
                    }

                    /** function to update the quarterly costs */
                    function updateOtherQuarterlyCosts(invoice_id)
                    {
                        // get the period
                        let period = document.getElementById("fixed-period").value;

                        // get the set quarterly costs
                        let q1_cost = document.getElementById("edit-q1_cost-"+invoice_id).value;
                        let q2_cost = document.getElementById("edit-q2_cost-"+invoice_id).value;
                        let q3_cost = document.getElementById("edit-q3_cost-"+invoice_id).value;
                        let q4_cost = document.getElementById("edit-q4_cost-"+invoice_id).value;

                        // send data to get the updated costs
                        let updated_costs = $.ajax({
                            type: "POST",
                            url: "ajax/services/provided/updateOtherQuarterlyCosts.php",
                            async: false,
                            data : {
                                invoice_id: invoice_id,
                                q1: q1_cost,
                                q2: q2_cost,
                                q3: q3_cost,
                                q4: q4_cost,
                                period: period,
                            }
                        }).responseText;

                        window.location.reload();
                    }

                    /** function to reset the quarterly costs */
                    function resetOtherQuarterlyCosts(invoice_id)
                    {
                        // send data to get the current costs
                        let reset_costs = JSON.parse($.ajax({
                            type: "POST",
                            url: "ajax/services/provided/resetEstimatedOtherQuarterlyCosts.php",
                            async: false,
                            data : {
                                invoice_id: invoice_id
                            }
                        }).responseText);

                        // reset the input boxes with the current costs
                        document.getElementById("edit-q1_cost-"+invoice_id).value = reset_costs["q1"];
                        document.getElementById("edit-q2_cost-"+invoice_id).value = reset_costs["q2"];
                        document.getElementById("edit-q3_cost-"+invoice_id).value = reset_costs["q3"];
                        document.getElementById("edit-q4_cost-"+invoice_id).value = reset_costs["q4"];

                        // check the quarterly costs to reverify
                        checkOtherQuarterlyCosts(invoice_id);
                    }

                    /** function to provide a other service */
                    function provideOtherService()
                    {
                        // get the fixed period name
                        let period = document.getElementById("fixed-period").value;

                        // get form paramters
                        let service_id = document.getElementById("add-invoice-service_id").value;
                        let customer_id = document.getElementById("add-invoice-customer_id").value;
                        let project_code = document.getElementById("add-invoice-project_code").value;
                        let total_cost = document.getElementById("add-invoice-cost").value;
                        let quantity = document.getElementById("add-invoice-qty").value;
                        let unit_label = document.getElementById("add-invoice-unit").value;
                        let description = document.getElementById("add-invoice-desc").value;
                        let date = document.getElementById("add-invoice-date").value;

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

                    /** function to get the modal to delete an "other service" invoice */
                    function getDeleteOtherInvoiceModal(invoice_id)
                    {
                        // send the data to create the delete invoice modal
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/services/provided/getDeleteOtherInvoiceModal.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                document.getElementById("delete-other_invoice-modal-div").innerHTML = this.responseText;     

                                // display the delete other invoice modal
                                $("#deleteOtherInvoiceModal").modal("show");
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

                                // display the edit other invoice modal
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
                                $("#deleteOtherInvoiceModal").modal("hide");
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
                                    $("#editOtherInvoiceModal").modal("hide");
                                }
                                else { window.location.reload(); }
                            }
                        };
                        xmlhttp.send(sendString);
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
                                    <!-- Period Selection -->
                                    <div class="col-9 p-0">
                                        <div class="input-group h-auto">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text h-100" id="nav-search-icon"><i class="fa-solid fa-calendar-days"></i></span>
                                            </div>
                                            <input id="fixed-period" type="hidden" value="" aria-hidden="true">
                                            <select class="form-select" id="search-period" name="search-period" onchange="searchInvoices();">
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
                                <h2 class="m-0">Services Billed</h2>
                            </div>

                            <!-- Page Management Dropdown -->
                            <div class="col-6 col-sm-4 col-md-4 col-lg-3 col-xl-2 col-xxl-2 p-0">
                                <?php if (isset($PERMISSIONS["ADD_INVOICES"]) || isset($PERMISSIONS["INVOICE_OTHER_SERVICES"])) { ?>
                                    <div class="dropdown float-end">
                                        <button class="btn btn-primary dropdown-toggle" type="button" id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">
                                            Manage Invoices
                                        </button>
                                        <ul class="dropdown-menu p-0" aria-labelledby="dropdownMenuButton1">
                                            <?php if (isset($PERMISSIONS["ADD_INVOICES"])) { ?>
                                                <li><button class="dropdown-item quickNav-dropdown-item text-center w-100 px-3 py-2 rounded-0" type="button" onclick="getProvideServiceModal();">Provide A Service</button></li>
                                                <li><button class="dropdown-item quickNav-dropdown-item text-center w-100 px-3 py-2 rounded-0" type="button" data-bs-toggle="modal" data-bs-target="#uploadInvoicesModal">Upload Invoices</button></li>
                                                <li><button class="dropdown-item quickNav-dropdown-item text-center w-100 px-3 py-2 rounded-0" type="button" data-bs-toggle="modal" data-bs-target="#uploadBulkInvoicesModal">Bulk Upload Invoices</button></li>
                                            <?php } ?>
                                            
                                            <?php if (isset($PERMISSIONS["INVOICE_OTHER_SERVICES"])) { ?>
                                                <li><button class="btn btn-primary w-100 h-100 px-4" type="button" data-bs-toggle="modal" data-bs-target="#provideOtherServiceModal">Provide Other Service</button></li>
                                            <?php } ?>
                                        </ul>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>
                    </div>

                    <div class="row report-body d-none m-0" id="invoices-table-div">
                        <!-- Invoices Table -->
                        <table id="services_provided" class="report_table w-100">
                            <thead>
                                <tr>
                                    <th class="text-center py-1 px-2" rowspan="2">Invoice ID</th>
                                    <th class="text-center py-1 px-2" colspan="2">Service</th>
                                    <th class="text-center py-1 px-2" colspan="2">Customer</th>
                                    <th class="text-center py-1 px-2" colspan="5">Cost By Quarter</th>
                                    <th class="text-center py-1 px-2" colspan="2"><span id="table-period_totals-label"></span> Totals</th>
                                    <th class="text-center py-1 px-2" rowspan="2">Actions</th>
                                </tr>

                                <tr>
                                    <th class="text-center py-1 px-2">ID</th>
                                    <th class="text-center py-1 px-2">Name</th>
                                    <th class="text-center py-1 px-2">ID</th>
                                    <th class="text-center py-1 px-2">Name</th>
                                    <th class="text-center py-1 px-2">Q1 <?php /* printLocked($conn, 1); */ ?></th>
                                    <th class="text-center py-1 px-2">Q2 <?php /* printLocked($conn, 2); */ ?></th>
                                    <th class="text-center py-1 px-2">Q3 <?php /* printLocked($conn, 3); */ ?></th>
                                    <th class="text-center py-1 px-2">Q4 <?php /* printLocked($conn, 4); */ ?></th>
                                    <th class="text-center py-1 px-2">Quarterly Total</th>
                                    <th class="text-center py-1 px-2">Quantity</th>
                                    <th class="text-center py-1 px-2" style="text-align: center !important;">Cost</th>
                                </tr>
                            </thead>

                            <tfoot>
                                <tr>
                                    <th class="text-end py-1 px-2" colspan="5">TOTAL:</th>
                                    <th class="text-end py-1 px-2" id="sum-q1"></th> <!-- Q1 Total -->
                                    <th class="text-end py-1 px-2" id="sum-q2"></th> <!-- Q2 Total -->
                                    <th class="text-end py-1 px-2" id="sum-q3"></th> <!-- Q3 Total -->
                                    <th class="text-end py-1 px-2" id="sum-q4"></th> <!-- Q4 Total -->
                                    <th class="text-end py-1 px-2" id="sum-qs"></th> <!-- Quarterly Total -->
                                    <th class="text-end py-1 px-2" id="sum-qty"></th> <!-- Quantity Sum -->
                                    <th class="text-end py-1 px-2" id="sum-total"></th> <!-- TOTAL -->
                                    <th class="py-1 px-2"></th>
                                </tr>
                            </tfoot>
                        </table>
                        <?php createTableFooterV2("services_provided", "BAP_ServicesProvided_PageLength", $USER_SETTINGS["page_length"], true, true); ?>
                    </div>
                </div>

                <!--
                    ### MODALS ###
                -->
                <?php if (isset($PERMISSIONS["ADD_INVOICES"])) { ?>
                <!-- Provide Service Modal -->
                <div id="provide-service-modal-div"></div>
                <!-- End Provide Service Modal -->

                <!-- Upload Projects Modal -->
                <div class="modal fade" tabindex="-1" role="dialog" id="uploadInvoicesModal" data-bs-backdrop="static" aria-labelledby="uploadInvoicesModalLabel" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header primary-modal-header">
                                <h5 class="modal-title primary-modal-title" id="uploadInvoicesModalLabel">Upload Invoices</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>

                            <form action="processUploadInvoices.php" method="POST" enctype="multipart/form-data">
                                <div class="modal-body">
                                    <p><label for="fileToUpload">Select a CSV file following the <a class="template-link" href="https://docs.google.com/spreadsheets/d/1cR4N0D3lrfLosko8JWNJoWY9S2tvxcgsGhExYjDQnFQ/copy" target="_blank">correct upload template</a> to upload...</label></p>
                                    <input type="file" name="fileToUpload" id="fileToUpload">
                                </div>

                                <div class="modal-footer">
                                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-cloud-arrow-up"></i> Upload Invoices</button>
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> Close</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <!-- End Upload Projects Modal -->

                <!-- Bulk Upload Projects Modal -->
                <div class="modal fade" tabindex="-1" role="dialog" id="uploadBulkInvoicesModal" data-bs-backdrop="static" aria-labelledby="uploadBulkInvoicesModalLabel" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header primary-modal-header">
                                <h5 class="modal-title primary-modal-title" id="uploadBulkInvoicesModalLabel">Bulk Upload Invoices</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>

                            <form action="processBulkUploadInvoices.php" method="POST" enctype="multipart/form-data">
                                <div class="modal-body">
                                    <p><label for="fileToUpload">Select a foler storing CSV files that follow the <a class="template-link" href="https://docs.google.com/spreadsheets/d/1cR4N0D3lrfLosko8JWNJoWY9S2tvxcgsGhExYjDQnFQ/copy" target="_blank">correct upload template</a> to bulk upload...</label></p>
                                    <input type="file" id="files" name="files[]" aria-label="Select folder to upload." multiple directory="" webkitdirectory="" moxdirectory="">
                                </div>

                                <div class="modal-footer">
                                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-cloud-arrow-up"></i> Bulk Upload Invoices</button>
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> Close</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <!-- End Bulk Upload Projects Modal -->
                <?php } ?>

                <?php if (isset($PERMISSIONS["EDIT_INVOICES"])) { ?>
                <!-- Edit Invoice Modal -->
                <div id="edit-invoice-modal-div"></div>
                <!-- End Edit Invoice Modal -->
                <?php } ?>

                <?php if (isset($PERMISSIONS["DELETE_INVOICES"])) { ?>
                <!-- Delete Invoice Modal -->
                <div id="delete-invoice-modal-div"></div>
                <!-- End Delete Invoice Modal -->
                <?php } ?>

                <?php if (isset($PERMISSIONS["INVOICE_OTHER_SERVICES"])) { ?>
                <!-- Provide Other Service Modal -->
                <div class="modal fade" tabindex="-1" role="dialog" id="provideOtherServiceModal" aria-labelledby="provideOtherServiceModalLabel" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header primary-modal-header">
                                <h5 class="modal-title primary-modal-title" id="provideOtherServiceModalLabel">Provide Other Service</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>

                            <div class="modal-body">
                                <!-- Service Details -->
                                <fieldset class="form-group border p-3 mb-3">
                                    <legend class="w-auto px-2 m-0 float-none fieldset-legend">Invoice Details</legend>

                                    <div class="row align-items-center my-2">
                                        <div class="col-4 text-end"><label for="add-invoice-service_id"><span class="required-field">*</span> Other Service:</label></div>
                                        <div class="col-8">
                                            <select class="form-select w-100" id="add-invoice-service_id" name="add-invoice-service_id" required>
                                                <option></option>
                                                <?php 
                                                    $getOtherServices = mysqli_query($conn, "SELECT id, name FROM services_other WHERE active=1");
                                                    if (mysqli_num_rows($getOtherServices) > 0) // other services exist; continue
                                                    {
                                                        while ($service = mysqli_fetch_array($getOtherServices))
                                                        {
                                                            $service_id = $service["id"];
                                                            $service_name = $service["name"];
                                                            echo "<option value='".$service_id."'>".$service_name."</option>";
                                                        }
                                                    }
                                                ?>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="row align-items-center my-2">
                                        <div class="col-4 text-end"><label for="add-invoice-customer_id"><span class="required-field">*</span> Customer:</label></div>
                                        <div class="col-8">
                                            <select class="form-select w-100" id="add-invoice-customer_id" name="add-invoice-customer_id">
                                                <option></option>
                                                <?php
                                                    $getCustomers = mysqli_query($conn, "SELECT id, name FROM customers WHERE active=1 ORDER BY name ASC");
                                                    if (mysqli_num_rows($getCustomers) > 0) // customers exist; continue
                                                    {
                                                        while ($customer = mysqli_fetch_array($getCustomers))
                                                        {
                                                            $customer_id = $customer["id"];
                                                            $customer_name = $customer["name"];
                                                            echo "<option value='".$customer_id."'>".$customer_name."</option>";
                                                        }
                                                    }
                                                ?>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="row align-items-center my-2">
                                        <div class="col-4 text-end"><label for="add-invoice-project_code">Project Code:</label></div>
                                        <div class="col-8">
                                            <select class="form-select w-100" id="add-invoice-project_code" name="add-invoice-project_code" required>
                                                <option></option>
                                                <?php
                                                    // create a dropdown of all active projects to assign to the service
                                                    $getProjects = mysqli_query($conn, "SELECT code, name FROM projects");
                                                    while ($project = mysqli_fetch_array($getProjects))
                                                    {
                                                        $project_code = $project["code"];
                                                        $project_name = $project["name"];
                                                        echo "<option value=".$project_code.">".$project_code." - ".$project_name."</option>";
                                                    }
                                                ?>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="row align-items-center my-2">
                                        <div class="col-4 text-end"><label for="add-invoice-cost"><span class="required-field">*</span> Total Cost:</label></div>
                                        <div class="col-8"><input type="number" value="0.00" class="form-control w-100" id="add-invoice-cost" name="add-invoice-cost"></div>
                                    </div>

                                    <div class="row align-items-center my-2">
                                        <div class="col-4 text-end"><label for="add-invoice-qty"><span class="required-field">*</span> Quantity:</label></div>
                                        <div class="col-8"><input type="number" value="0" class="form-control w-100" id="add-invoice-qty" name="add-invoice-qty"></div>
                                    </div>

                                    <div class="row align-items-center my-2">
                                        <div class="col-4 text-end"><label for="add-invoice-unit"><span class="required-field">*</span> Unit Label:</label></div>
                                        <div class="col-8"><input type="text" placeholder="unit" class="form-control w-100" id="add-invoice-unit" name="add-invoice-unit"></div>
                                    </div>

                                    <div class="row align-items-center my-2">
                                        <div class="col-4 text-end"><label for="add-invoice-desc"><span class="required-field">*</span> Description:</label></div>
                                        <div class="col-8"><input type="text" class="form-control w-100" id="add-invoice-desc" name="add-invoice-desc"></div>
                                    </div>

                                    <div class="row align-items-center my-2">
                                        <div class="col-4 text-end"><label for="add-invoice-date"><span class="required-field">*</span> Date Provided:</label></div>
                                        <div class="col-8"><input type="text" class="form-control w-100" id="add-invoice-date" name="add-invoice-date" value="<?php echo date("m/d/Y"); ?>"></div>
                                    </div>
                                </fieldset>
                            </div>
                            
                            <div class="modal-footer">
                                <button type="button" class="btn btn-primary" onclick="provideOtherService();"><i class="fa-solid fa-floppy-disk"></i> Provide Service</button>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> Close</button>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- End Provide Other Service Modal -->

                <!-- Delete Other Invoice Modal -->
                <div id="delete-other_invoice-modal-div"></div>
                <!-- End Other Delete Invoice Modal -->

                <!-- Edit Other Invoice Modal -->
                <div id="edit-other_invoice-modal-div"></div>
                <!-- End Edit Other Invoice Modal -->
                <?php } ?>
                
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

                    // set page length to prior saved state
                    let saved_page_length = sessionStorage["BAP_ServicesProvided_PageLength"];
                    if (saved_page_length != "" && saved_page_length != null && saved_page_length != undefined)
                    {
                        $("#services_provided-DT_PageLength").val(sessionStorage["BAP_ServicesProvided_PageLength"]);
                    }

                    // set the search filters to values we have saved in storage
                    $('#search-all').val(sessionStorage["BAP_ServicesProvided_Search_All"]);
                    if (sessionStorage["BAP_ServicesProvided_Search_Period"] != "" && sessionStorage["BAP_ServicesProvided_Search_Period"] != null && sessionStorage["BAP_ServicesProvided_Search_Period"] != undefined) { $('#search-period').val(sessionStorage["BAP_ServicesProvided_Search_Period"]); }
                    else { $('#search-period').val(active_period); } // no period set; default to active period 
                    $('#search-services').val(sessionStorage["BAP_ServicesProvided_Search_Service"]);
                    $('#search-customers').val(sessionStorage["BAP_ServicesProvided_Search_Customer"]);

                    /** function to generate the invoices table based on the period selected */
                    function searchInvoices()
                    {
                        // get the value of the period we are searching
                        var period = document.getElementById("search-period").value;

                        if (period != "" && period != null && period != undefined)
                        {
                            // update the table headers
                            document.getElementById("table-period_totals-label").innerHTML = period;

                            // set the fixed period
                            document.getElementById("fixed-period").value = period;

                            // update session storage stored search parameter
                            sessionStorage["BAP_ServicesProvided_Search_Period"] = period;

                            // if we have already drawn the table, destroy existing table
                            if (drawn == 1) { $("#services_provided").DataTable().destroy(); }

                            var services_provided = $("#services_provided").DataTable({
                                ajax: {
                                    url: "ajax/services/provided/getProvided.php",
                                    type: "POST",
                                    data: {
                                        period: period
                                    }
                                },
                                autoWidth: false,
                                pageLength: <?php if (isset($USER_SETTINGS["page_length"])) { echo $USER_SETTINGS["page_length"]; } else { echo 10; } ?>,
                                lengthMenu: [ [10, 25, 50, 100, 250, -1], [10, 25, 50, 100, 250, "All"] ],
                                columns: [
                                    { data: "invoice_id", orderable: true, width: "5%", }, // 0
                                    { data: "service_id", orderable: true, width: "5%" },
                                    { data: "service_name", orderable: true, width: "13.5%" },
                                    { data: "customer_id", orderable: true, width: "5%" },
                                    { data: "customer_name", orderable: true, width: "13.5%" },
                                    { data: "q1_cost_display", orderable: false, width: "8%" }, // 5
                                    { data: "q2_cost_display", orderable: false, width: "8%" },
                                    { data: "q3_cost_display", orderable: false, width: "8%" },
                                    { data: "q4_cost_display", orderable: false, width: "8%" },
                                    { data: "quarterly_cost_sum", orderable: false, width: "9.5%" },
                                    { data: "quantity", orderable: true, width: "7%", className: "text-center" }, // 10
                                    { data: "total_cost", orderable: true, width: "9%", className: "text-end" },
                                    <?php if (isset($PERMISSIONS["EDIT_INVOICES"]) || isset($PERMISSIONS["DELETE_INVOICES"])) { ?>
                                    { data: "actions", orderable: false, width: "10%" },
                                    <?php } else { ?>
                                    { data: "actions", orderable: false, visible: false },
                                    <?php } ?>
                                    { data: "q1_cost", orderable: false, visible: false },
                                    { data: "q2_cost", orderable: false, visible: false },
                                    { data: "q3_cost", orderable: false, visible: false }, // 15
                                    { data: "q4_cost", orderable: false, visible: false }
                                ],
                                dom: 'rt',
                                language: {
                                    search: '<div class="input-group mb-1"><div class="input-group-prepend"><span class="d-flex align-items-center h-100 mx-2" id="nav-search-icon"><i class="fa fa-search" aria-hidden="true"></i></span></div>_INPUT_</div>',
                                    lengthMenu: '<div class="mx-1 mb-1">Show _MENU_ entries</div>',
                                    info: '<div class="mx-1">Showing _START_ to _END_ of _TOTAL_ entries</div>'
                                },
                                stateSave: true,
                                drawCallback: function ()
                                {
                                    var api = this.api();

                                    // get the sum of all filtered quarterly costs
                                    let q1_sum = api.column(13, { search: "applied" }).data().sum().toFixed(2);
                                    let q2_sum = api.column(14, { search: "applied" }).data().sum().toFixed(2);
                                    let q3_sum = api.column(15, { search: "applied" }).data().sum().toFixed(2);
                                    let q4_sum = api.column(16, { search: "applied" }).data().sum().toFixed(2);

                                    // sum the total quarterly sums
                                    let quarterly_cost_sum = parseFloat(q1_sum) + parseFloat(q2_sum) + parseFloat(q3_sum) + parseFloat(q4_sum);

                                    // get the sum of all filtered
                                    let total_qty = api.column(10, { search: "applied" }).data().sum();
                                    let total_sum = api.column(11, { search: "applied" }).data().sum().toFixed(2);
                                    
                                    // update the table footer
                                    document.getElementById("sum-q1").innerHTML = "$"+numberWithCommas(q1_sum);
                                    document.getElementById("sum-q2").innerHTML = "$"+numberWithCommas(q2_sum);
                                    document.getElementById("sum-q3").innerHTML = "$"+numberWithCommas(q3_sum);
                                    document.getElementById("sum-q4").innerHTML = "$"+numberWithCommas(q4_sum);
                                    document.getElementById("sum-qs").innerHTML = "$"+numberWithCommas(parseFloat(quarterly_cost_sum).toFixed(2));
                                    document.getElementById("sum-qty").innerHTML = numberWithCommas(Math.round(total_qty), 2);
                                    document.getElementById("sum-total").innerHTML = "$"+numberWithCommas(total_sum);
                                },
                                rowCallback: function (row, data, index)
                                {
                                    updatePageSelection("services_provided");
                                },
                            }); 

                            // mark that we have drawn the table
                            drawn = 1;

                            // search table by custom search filter
                            $('#search-all').keyup(function() {
                                services_provided.search($(this).val()).draw();
                                sessionStorage["BAP_ServicesProvided_Search_All"] = $(this).val();
                            });

                            // search table by service name
                            $('#search-services').change(function() {
                                services_provided.columns(2).search($(this).val()).draw();
                                sessionStorage["BAP_ServicesProvided_Search_Service"] = $(this).val();
                            });

                            // search table by customer name
                            $('#search-customers').change(function() {
                                services_provided.columns(4).search($(this).val()).draw();
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
                                services_provided.search("").columns().search("").draw();
                            });                       
                            
                            // display the table
                            document.getElementById("invoices-table-div").classList.remove("d-none");
                        }
                        else { createStatusModal("alert", "Loading Invoices Error", "Failed to load invoices. You must select a period to display invoices for."); }
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