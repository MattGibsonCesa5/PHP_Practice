<?php 
    include_once("header.php");

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1) 
    { 
        if (isset($PERMISSIONS["VIEW_SERVICES_ALL"]) || isset($PERMISSIONS["VIEW_SERVICES_ASSIGNED"]) || isset($PERMISSIONS["VIEW_OTHER_SERVICES"]))
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
                    <?php if (isset($PERMISSIONS["ADD_SERVICES"])) { ?>
                    /** function to add a new service */
                    function addService()
                    {
                        // create the string of data to send
                        let sendString = "";

                        // get service details from the modal
                        let service_id = encodeURIComponent(document.getElementById("add-service_id").value);
                        let service_name = encodeURIComponent(document.getElementById("add-service_name").value);
                        let description = encodeURIComponent(document.getElementById("add-description").value);
                        let export_label = encodeURIComponent(document.getElementById("add-export_label").value);
                        let cost_type = encodeURIComponent(document.getElementById("add-cost_type").value);
                        let unit_label = encodeURIComponent(document.getElementById("add-unit_label").value);
                        let fund_code = encodeURIComponent(document.getElementById("add-fund_code").value);
                        let object_code = encodeURIComponent(document.getElementById("add-object_code").value);
                        let function_code = encodeURIComponent(document.getElementById("add-function_code").value);
                        let project_code = encodeURIComponent(document.getElementById("add-project_code").value);
                        let round_costs = encodeURIComponent(document.getElementById("add-round").value);
                        sendString += "service_id="+service_id+"&service_name="+service_name+"&cost_type="+cost_type+"&unit_label="+unit_label+"&description="+description+"&export_label="+export_label;
                        sendString += "&fund_code="+fund_code+"&object_code="+object_code+"&function_code="+function_code+"&project_code="+project_code+"&round_costs="+round_costs;

                        // if the cost type is fixed (0), get the fixed cost
                        if (cost_type == 0)
                        {
                            var fixed_cost = encodeURIComponent(document.getElementById("add-fixed_cost").value);
                            sendString += "&fixed_cost="+fixed_cost;
                        }
                        // if the cost type is variable (1), get the variable costs
                        else if (cost_type == 1)
                        {
                            // initialize the array to store the cost ranges
                            var ranges = [];

                            // get the number of ranges 
                            var numOfRanges = document.getElementById("add-numOfRanges").value;

                            // get all price ranges
                            for (var r = 1; r <= numOfRanges; r++)
                            {
                                // get the variables for range r
                                var order = document.getElementById("add-variable_cost-order-"+r).value;
                                var min = document.getElementById("add-variable_cost-min-"+r).value;
                                var max = document.getElementById("add-variable_cost-max-"+r).value;
                                var cost = document.getElementById("add-variable_cost-cost-"+r).value;

                                // create array to store variables for range r
                                var rangeArr = [];
                                rangeArr.push(order);
                                rangeArr.push(min);
                                rangeArr.push(max);
                                rangeArr.push(cost);
                                ranges.push(rangeArr);
                            }

                            sendString += "&variable_costs="+JSON.stringify(ranges);
                        }
                        // if the cost type is membership (2), get the membership cost fields
                        else if (cost_type == 2)
                        {
                            var membership_total_cost = document.getElementById("add-membership_total_cost").value;
                            var membership_group = document.getElementById("add-membership_group").value;
                            sendString += "&membership_total_cost="+membership_total_cost+"&membership_group="+membership_group;
                        }
                        // if the cost type is rates-based (4), get the rates
                        else if (cost_type == 4)
                        {
                            // initialize the array to store the rates
                            var rates = [];

                            // get the number of rates 
                            var numOfRates = document.getElementById("add-rates_cost-numOfRanges").value;

                            // get all rates
                            for (var r = 1; r <= numOfRates; r++)
                            {
                                // get the variables for range r
                                var tier = document.getElementById("add-rates_cost-order-"+r).value;
                                var cost = document.getElementById("add-rates_cost-cost-"+r).value;

                                // create array to store variables for range r
                                var rate = [];
                                rate.push(tier);
                                rate.push(cost);
                                rates.push(rate);
                            }

                            sendString += "&rates="+JSON.stringify(rates);
                        }
                        // if the cost type is group-rates-based (5), get the rates
                        else if (cost_type == 5)
                        {
                            // initialize the array to store the rates
                            var rates = [];

                            // get the number of rates 
                            var numOfRates = document.getElementById("add-group_rates-numOfRanges").value;

                            // get the rate group
                            var rate_group = document.getElementById("add-rate_group").value;

                            // get all rates
                            for (var r = 1; r <= numOfRates; r++)
                            {
                                // get the variables for range r
                                var tier = document.getElementById("add-group_rates-order-"+r).value;
                                var inside_cost = document.getElementById("add-group_rates-inside-cost-"+r).value;
                                var outside_cost = document.getElementById("add-group_rates-outside-cost-"+r).value;

                                // create array to store variables for range r
                                var rate = [];
                                rate.push(tier);
                                rate.push(inside_cost);
                                rate.push(outside_cost);
                                rates.push(rate);
                            }

                            sendString += "&group_rates="+JSON.stringify(rates)+"&rate_group="+rate_group;
                        }

                        // send the data to process the add customer request
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/services/manage/addService.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // create the status modal
                                let status_title = "Add Service Status";
                                let status_body = this.responseText;
                                createStatusModal("refresh", status_title, status_body);

                                // hide the current modal
                                $("#addServiceModal").modal("hide");
                            }
                        };
                        xmlhttp.send(sendString);
                    }
                    <?php } ?>

                    <?php if (isset($PERMISSIONS["DELETE_SERVICES"])) { ?>
                    /** function to delete the service */
                    function deleteService(id)
                    {
                        // send the data to process the edit customer request
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/services/manage/deleteService.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // create the status modal
                                let status_title = "Delete Service Status";
                                let status_body = this.responseText;
                                createStatusModal("refresh", status_title, status_body);

                                // hide the current modal
                                $("#deleteServiceModal").modal("hide");
                            }
                        };
                        xmlhttp.send("service_id="+id);
                    }

                    /** function to get the delete service modal */
                    function getDeleteServiceModal(id)
                    {
                        // send the data to create the delete service modal
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/services/manage/getDeleteServiceModal.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                document.getElementById("delete-service-modal-div").innerHTML = this.responseText;     

                                // display the edit customer modal
                                $("#deleteServiceModal").modal("show");
                            }
                        };
                        xmlhttp.send("service_id="+id);
                    }
                    <?php } ?>

                    <?php if (isset($PERMISSIONS["EDIT_SERVICES"])) { ?>
                    /** function to get the edit service modal */
                    function getEditServiceModal(service_id, period_id)
                    {
                        // send the data to create the edit service modal
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/services/manage/getEditServiceModal.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                document.getElementById("edit-service-modal-div").innerHTML = this.responseText;     

                                // display the edit customer modal
                                $("#editServiceModal").modal("show");
                            }
                        };
                        xmlhttp.send("service_id="+service_id+"&period_id="+period_id);
                    }

                    /** function to add a new service */
                    function editService(service_id, period_id)
                    {
                        // create the string of data to send
                        let sendString = "";

                        // get service details from the modal
                        let form_service_id = encodeURIComponent(document.getElementById("edit-service_id").value);
                        let service_name = encodeURIComponent(document.getElementById("edit-service_name").value);
                        let description = encodeURIComponent(document.getElementById("edit-description").value);
                        let export_label = encodeURIComponent(document.getElementById("edit-export_label").value);
                        let cost_type = encodeURIComponent(document.getElementById("edit-cost_type").value);
                        let unit_label = encodeURIComponent(document.getElementById("edit-unit_label").value); 
                        let fund_code = encodeURIComponent(document.getElementById("edit-fund_code").value);
                        let object_code = encodeURIComponent(document.getElementById("edit-object_code").value);
                        let function_code = encodeURIComponent(document.getElementById("edit-function_code").value);
                        let project_code = encodeURIComponent(document.getElementById("edit-project_code").value);
                        let round_costs = encodeURIComponent(document.getElementById("edit-round").value);
                        sendString += "service_id="+service_id+"&form_service_id="+form_service_id+"&period_id="+period_id+"&service_name="+service_name+"&cost_type="+cost_type+"&unit_label="+unit_label+"&description="+description+"&export_label="+export_label;
                        sendString += "&fund_code="+fund_code+"&object_code="+object_code+"&function_code="+function_code+"&project_code="+project_code+"&round_costs="+round_costs;

                        // if the cost type is fixed (0), get the fixed cost
                        if (cost_type == 0)
                        {
                            var fixed_cost = encodeURIComponent(document.getElementById("edit-fixed_cost").value);
                            sendString += "&fixed_cost="+fixed_cost;
                        }
                        // if the cost type is variable (1), get the variable costs
                        else if (cost_type == 1)
                        {
                            // initialize the array to store the cost ranges
                            var ranges = [];

                            // get the number of ranges 
                            var numOfRanges = document.getElementById("edit-numOfRanges").value;

                            // get all price ranges
                            for (var r = 1; r <= numOfRanges; r++)
                            {
                                // get the variables for range r
                                var order = document.getElementById("edit-variable_cost-order-"+r).value;
                                var min = document.getElementById("edit-variable_cost-min-"+r).value;
                                var max = document.getElementById("edit-variable_cost-max-"+r).value;
                                var cost = document.getElementById("edit-variable_cost-cost-"+r).value;

                                // create array to store variables for range r
                                var rangeArr = [];
                                rangeArr.push(order);
                                rangeArr.push(min);
                                rangeArr.push(max);
                                rangeArr.push(cost);
                                ranges.push(rangeArr);
                            }

                            sendString += "&variable_costs="+JSON.stringify(ranges);
                        }
                        // if the cost type is membership (2), get the membership cost fields
                        else if (cost_type == 2)
                        {
                            var membership_total_cost = document.getElementById("edit-membership_total_cost").value;
                            var membership_group = document.getElementById("edit-membership_group").value;
                            sendString += "&membership_total_cost="+membership_total_cost+"&membership_group="+membership_group;
                        }
                        // if the cost type is rates-based (4), get the rates
                        else if (cost_type == 4)
                        {
                            // initialize the array to store the rates
                            var rates = [];

                            // get the number of rates 
                            var numOfRates = document.getElementById("edit-rates_cost-numOfRanges").value;

                            // get all rates
                            for (var r = 1; r <= numOfRates; r++)
                            {
                                // get the variables for range r
                                var tier = document.getElementById("edit-rates_cost-order-"+r).value;
                                var cost = document.getElementById("edit-rates_cost-cost-"+r).value;

                                // create array to store variables for range r
                                var rate = [];
                                rate.push(tier);
                                rate.push(cost);
                                rates.push(rate);
                            }

                            sendString += "&rates="+JSON.stringify(rates);
                        }
                        // if the cost type is group-rates-based (5), get the rates
                        else if (cost_type == 5)
                        {
                            // initialize the array to store the rates
                            var rates = [];

                            // get the number of rates 
                            var numOfRates = document.getElementById("edit-group_rates-numOfRanges").value;

                            // get the rate group
                            var rate_group = document.getElementById("edit-rate_group").value;

                            // get all rates
                            for (var r = 1; r <= numOfRates; r++)
                            {
                                // get the variables for range r
                                var tier = document.getElementById("edit-group_rates-order-"+r).value;
                                var inside_cost = document.getElementById("edit-group_rates-inside-cost-"+r).value;
                                var outside_cost = document.getElementById("edit-group_rates-outside-cost-"+r).value;

                                // create array to store variables for range r
                                var rate = [];
                                rate.push(tier);
                                rate.push(inside_cost);
                                rate.push(outside_cost);
                                rates.push(rate);
                            }

                            sendString += "&group_rates="+JSON.stringify(rates)+"&rate_group="+rate_group;
                        }

                        // send the data to process the add customer request
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/services/manage/editService.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // create the status modal
                                let status_title = "Edit Service Status";
                                let status_body = this.responseText;
                                createStatusModal("refresh", status_title, status_body);

                                // hide the current modal
                                $("#editServiceModal").modal("hide");
                            }
                        };
                        xmlhttp.send(sendString);
                    }
                    <?php } ?>

                    <?php if (isset($PERMISSIONS["ADD_SERVICES"]) || isset($PERMISSIONS["EDIT_SERVICES"])) { ?>
                    /** function to add a range to the variable cost grid */
                    function addRange(type)
                    {
                        // get the current number of ranges
                        let numOfRanges = document.getElementById(type+"-numOfRanges").value;

                        // get the current range(s)
                        let currentRanges = document.getElementById(type+"-variable_cost-grid").innerHTML;

                        // create the new range
                        let newRange = parseInt(numOfRanges) + 1;
                        let newRangeRow = "<tr id='"+type+"-variable_cost-range-"+newRange+"'>" + 
                                            "<td><input type='number' class='form-control' id='"+type+"-variable_cost-order-"+newRange+"' value='"+newRange+"' disabled></td>" +
                                            "<td><input type='number' class='form-control' id='"+type+"-variable_cost-min-"+newRange+"' min='0' step='1' value='0'></td>" +
                                            "<td><input type='number' class='form-control' id='"+type+"-variable_cost-max-"+newRange+"' min='0' step='1' value='10'></td>" +
                                            "<td><input type='number' class='form-control' id='"+type+"-variable_cost-cost-"+newRange+"' min='0.00' step='0.01' value='0.00'></td>" +
                                        "</tr>";

                        // create the new grid (current ranges + new range)
                        let newGrid = currentRanges + newRangeRow;

                        // update the number of ranges
                        document.getElementById(type+"-numOfRanges").value = newRange;

                        // display the new cost grid
                        document.getElementById(type+"-variable_cost-grid").innerHTML = newGrid;

                        // if there is more than 1 range, enable the remove a range button
                        if (newRange > 1) { document.getElementById(type+"-variable_cost-removeRangeBtn").removeAttribute("disabled"); }
                    }

                    /** function to remove a range to the variable cost grid */
                    function removeRange(type)
                    {
                        // get the current number of ranges
                        let numOfRanges = document.getElementById(type+"-numOfRanges").value;

                        // only allow deletion of there is more than 1 range
                        if (numOfRanges > 1)
                        {
                            // remove the range
                            document.getElementById(type+"-variable_cost-range-"+numOfRanges).remove();

                            // update the new number of ranges
                            let newRange = parseInt(numOfRanges) - 1;
                            document.getElementById(type+"-numOfRanges").value = newRange;

                            // if there is only 1 range, disable the remove range button
                            if (newRange == 1) { document.getElementById(type+"-variable_cost-removeRangeBtn").setAttribute("disabled", true); }
                        }
                    }

                    /** function to add a range to the rates cost grid */
                    function addRatesRange(type)
                    {
                        // get the current number of ranges
                        let numOfRanges = document.getElementById(type+"-rates_cost-numOfRanges").value;

                        // get the current range(s)
                        let currentRanges = document.getElementById(type+"-rates_cost-grid").innerHTML;

                        // create the new range
                        let newRange = parseInt(numOfRanges) + 1;
                        let newRangeRow = "<tr id='"+type+"-rates_cost-range-"+newRange+"'>" + 
                                            "<td><input type='number' class='form-control' id='"+type+"-rates_cost-order-"+newRange+"' value='"+newRange+"' disabled></td>" +
                                            "<td><input type='number' class='form-control' id='"+type+"-rates_cost-cost-"+newRange+"' min='0.00' step='0.01' value='0.00'></td>" +
                                        "</tr>";

                        // create the new grid (current ranges + new range)
                        let newGrid = currentRanges + newRangeRow;

                        // update the number of ranges
                        document.getElementById(type+"-rates_cost-numOfRanges").value = newRange;

                        // display the new cost grid
                        document.getElementById(type+"-rates_cost-grid").innerHTML = newGrid;

                        // if there is more than 1 range, enable the remove a range button
                        if (newRange > 1) { document.getElementById(type+"-rates_cost-removeRangeBtn").removeAttribute("disabled"); }
                    }

                    /** function to remove a range from the rates grid */
                    function removeRatesRange(type)
                    {
                        // get the current number of ranges
                        let numOfRanges = document.getElementById(type+"-rates_cost-numOfRanges").value;

                        // only allow deletion of there is more than 1 range
                        if (numOfRanges > 1)
                        {
                            // remove the range
                            document.getElementById(type+"-rates_cost-range-"+numOfRanges).remove();

                            // update the new number of ranges
                            let newRange = parseInt(numOfRanges) - 1;
                            document.getElementById(type+"-rates_cost-numOfRanges").value = newRange;

                            // if there is only 1 range, disable the remove range button
                            if (newRange == 1) { document.getElementById(type+"-rates_cost-removeRangeBtn").setAttribute("disabled", true); }
                        }
                    }

                    /** function to add a range to the group rates grid */
                    function addGroupRatesRange(type)
                    {
                        // get the current number of ranges
                        let numOfRanges = document.getElementById(type+"-group_rates-numOfRanges").value;

                        // get the current range(s)
                        let currentRanges = document.getElementById(type+"-group_rates-grid").innerHTML;

                        // create the new range
                        let newRange = parseInt(numOfRanges) + 1;
                        let newRangeRow = "<tr id='"+type+"-group_rates-range-"+newRange+"'>" + 
                                            "<td><input type='number' class='form-control' id='"+type+"-group_rates-order-"+newRange+"' value='"+newRange+"' disabled></td>" +
                                            "<td><input type='number' class='form-control' id='"+type+"-group_rates-inside-cost-"+newRange+"' min='0.00' step='0.01' value='0.00'></td>" +
                                            "<td><input type='number' class='form-control' id='"+type+"-group_rates-outside-cost-"+newRange+"' min='0.00' step='0.01' value='0.00'></td>" +
                                        "</tr>";

                        // create the new grid (current ranges + new range)
                        let newGrid = currentRanges + newRangeRow;

                        // update the number of ranges
                        document.getElementById(type+"-group_rates-numOfRanges").value = newRange;

                        // display the new cost grid
                        document.getElementById(type+"-group_rates-grid").innerHTML = newGrid;

                        // if there is more than 1 range, enable the remove a range button
                        if (newRange > 1) { document.getElementById(type+"-group_rates-removeRangeBtn").removeAttribute("disabled"); }
                    }

                    /** function to remove a range from the group rates grid */
                    function removeGroupRatesRange(type)
                    {
                        // get the current number of ranges
                        let numOfRanges = document.getElementById(type+"-group_rates-numOfRanges").value;

                        // only allow deletion of there is more than 1 range
                        if (numOfRanges > 1)
                        {
                            // remove the range
                            document.getElementById(type+"-group_rates-range-"+numOfRanges).remove();

                            // update the new number of ranges
                            let newRange = parseInt(numOfRanges) - 1;
                            document.getElementById(type+"-group_rates-numOfRanges").value = newRange;

                            // if there is only 1 range, disable the remove range button
                            if (newRange == 1) { document.getElementById(type+"-group_rates-removeRangeBtn").setAttribute("disabled", true); }
                        }
                    }

                    /** function to update the cost form */
                    function updateCostForm(modal)
                    {
                        // get the value of cost type dropdown
                        let type = document.getElementById(modal+"-cost_type").value;

                        // hide all cost forms
                        $("#"+modal+"-fixed_cost-div").css("visibility", "hidden");
                        $("#"+modal+"-fixed_cost-div").css("display", "none");
                        $("#"+modal+"-variable_cost-div").css("visibility", "hidden");
                        $("#"+modal+"-variable_cost-div").css("display", "none");
                        $("#"+modal+"-membership-div").css("visibility", "hidden");
                        $("#"+modal+"-membership-div").css("display", "none");
                        $("#"+modal+"-custom_cost-div").css("visibility", "hidden");
                        $("#"+modal+"-custom_cost-div").css("display", "none");
                        $("#"+modal+"-rates_cost-div").css("visibility", "hidden");
                        $("#"+modal+"-rates_cost-div").css("display", "none");
                        $("#"+modal+"-group_rates-div").css("visibility", "hidden");
                        $("#"+modal+"-group_rates-div").css("display", "none");

                        if (type == 0) // display fixed cost form
                        {
                            $("#"+modal+"-fixed_cost-div").css("visibility", "visible");
                            $("#"+modal+"-fixed_cost-div").css("display", "block");
                        }
                        else if (type == 1) // display variable cost form
                        {
                            $("#"+modal+"-variable_cost-div").css("visibility", "visible");
                            $("#"+modal+"-variable_cost-div").css("display", "block");
                        }
                        else if (type == 2) // display membership cost form
                        {
                            $("#"+modal+"-membership-div").css("visibility", "visible");
                            $("#"+modal+"-membership-div").css("display", "block");
                        }
                        else if (type == 3) // display custom cost form
                        {
                            $("#"+modal+"-custom_cost-div").css("visibility", "visible");
                            $("#"+modal+"-custom_cost-div").css("display", "block");
                        }
                        else if (type == 4) // display rates cost form
                        {
                            $("#"+modal+"-rates_cost-div").css("visibility", "visible");
                            $("#"+modal+"-rates_cost-div").css("display", "block");
                        }
                        else if (type == 5) // display group rates cost form
                        {
                            $("#"+modal+"-group_rates-div").css("visibility", "visible");
                            $("#"+modal+"-group_rates-div").css("display", "block");
                        }
                    }
                    <?php } ?>

                    /** functions to look for closing/hiding of the edit service modal */
                    $(document).on("hide.bs.modal", "#editServiceModal", function() {
                        // delete modal from page so on edit click a new/refreshed modal appears
                        document.getElementById("edit-service-modal-div").innerHTML = "";
                    });

                    /** function to update the rounding costs setting */
                    function updateRoundCosts(id)
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

                    /** function to create the projects selection dropdown */
                    function createProjectsDropdown()
                    {
                        // get the selected period
                        let period = $("#search-period").val();

                        // get the projectd dropdown
                        let content = $.ajax({
                            type: "POST",
                            url: "ajax/projects/getProjectsDropdown.php",
                            data: {
                                period: period
                            },
                            async: false,
                        }).responseText;

                        // fill dropdown
                        document.getElementById("add-project_code").innerHTML = content;
                    }

                    /** function to create and display the View Service modal */
                    function getViewServiceModal(service_id, period_id)
                    {
                        // send the data to create the view service modal
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/services/manage/getViewServiceModal.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                document.getElementById("view-service-modal-div").innerHTML = this.responseText;     

                                // display the view customer modal
                                $("#viewServiceModal").modal("show");
                            }
                        };
                        xmlhttp.send("service_id="+service_id+"&period_id="+period_id);
                    }

                    /** function to update the estimated invoice cost */
                    function estimateCost(service_id)
                    {
                        // get the fixed period name
                        let period = document.getElementById("fixed-period").value;

                        // get the quantity and customer
                        let quantity = document.getElementById("view-quantity").value;
                        let customer_id = document.getElementById("view-customer_id").value;

                        // send the data to process the add customer request
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/services/manage/getEstimatedCost.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                document.getElementById("view-estimated_cost").value = this.responseText;
                            }
                        }
                        xmlhttp.send("service_id="+service_id+"&period="+period+"&quantity="+quantity+"&customer_id="+customer_id);
                    }

                    /** function to add a new other service */
                    function addOtherService()
                    {
                        // get form parameters
                        let id = document.getElementById("add-service_id").value;
                        let name = document.getElementById("add-service_name").value;
                        let label = document.getElementById("add-export_label").value;
                        let fund = document.getElementById("add-fund_code").value;
                        let src = document.getElementById("add-source_code").value;
                        let func = document.getElementById("add-function_code").value;

                        // create the string of data to send
                        let sendString = "service_id="+id+"&service_name="+name+"&fund_code="+fund+"&source_code="+src+"&function_code="+func+"&export_label="+label;

                        // send the data to process the add invoice request
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/services/manage/addOtherService.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                if (this.responseText != "")
                                {
                                    // create the status modal
                                    let status_title = "Add Other Service Status";
                                    let status_body = this.responseText;
                                    createStatusModal("refresh", status_title, status_body);

                                    // hide the current modal
                                    $("#addServiceModal").modal("hide");
                                }
                                else { window.location.reload(); }
                            }
                        };
                        xmlhttp.send(sendString);
                    }

                    /** function to get the modal to edit the other service */
                    function getEditOtherServiceModal(service_id)
                    {
                        // send the data to create the delete invoice modal
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/services/manage/getEditOtherServiceModal.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                document.getElementById("edit-service-modal-div").innerHTML = this.responseText;     

                                // display the edit customer modal
                                $("#editServiceModal").modal("show");
                            }
                        };
                        xmlhttp.send("service_id="+service_id);
                    }

                    /** function to get the modal to delete the other service */
                    function getDeleteOtherServiceModal(service_id)
                    {
                        // send the data to create the delete invoice modal
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/services/manage/getDeleteOtherServiceModal.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                document.getElementById("delete-service-modal-div").innerHTML = this.responseText;     

                                // display the edit customer modal
                                $("#deleteServiceModal").modal("show");
                            }
                        };
                        xmlhttp.send("service_id="+service_id);
                    }

                    /** function to edit the other service */
                    function editOtherService(service_id)
                    {
                        // get form parameters
                        let id = service_id;
                        let form_id = document.getElementById("edit-service_id").value;
                        let name = document.getElementById("edit-service_name").value;
                        let label = document.getElementById("edit-export_label").value;
                        let fund = document.getElementById("edit-fund_code").value;
                        let src = document.getElementById("edit-source_code").value;
                        let func = document.getElementById("edit-function_code").value;

                        // create the string of data to send
                        let sendString = "service_id="+id+"&form_service_id="+form_id+"&service_name="+name+"&fund_code="+fund+"&source_code="+src+"&function_code="+func+"&export_label="+label;

                        // send the data to process the add invoice request
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/services/manage/editOtherService.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                if (this.responseText != "")
                                {
                                    // create the status modal
                                    let status_title = "Edit Other Service Status";
                                    let status_body = this.responseText;
                                    createStatusModal("refresh", status_title, status_body);

                                    // hide the current modal
                                    $("#editServiceModal").modal("hide");
                                }
                                else { window.location.reload(); }
                            }
                        };
                        xmlhttp.send(sendString);
                    }
                    
                    /** function to delete the other service */
                    function deleteOtherService(service_id)
                    {
                        // send the data to process the delete other service request
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/services/manage/deleteOtherService.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // create the status modal
                                let status_title = "Delete Other Service Status";
                                let status_body = this.responseText;
                                createStatusModal("refresh", status_title, status_body);

                                // hide the current modal
                                $("#deleteServiceModal").modal("hide");
                            }
                        };
                        xmlhttp.send("service_id="+service_id);
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
                                            <select class="form-select" id="search-period" name="search-period" onchange="searchServices();">
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

                                                <!-- Search By Cost Type -->
                                                <div class="row d-flex justify-content-between align-items-center mx-0 mt-0 mb-2">
                                                    <div class="col-4 ps-0 pe-1">
                                                        <label for="search-status">Cost Type:</label>
                                                    </div>

                                                    <div class="col-8 ps-1 pe-0">
                                                        <select class="form-select" id="search-cost_type" name="search-cost_type">
                                                            <option></option>
                                                            <option>Fixed</option>
                                                            <option>Variable</option>
                                                            <option>Membership</option>
                                                            <option>Custom Cost</option>
                                                            <option>Rate</option>
                                                            <option>Group Rate</option>
                                                        </select>
                                                    </div>
                                                </div>

                                                <!-- Search By Project -->
                                                <div class="row d-flex justify-content-between align-items-center mx-0 mt-0 mb-2">
                                                    <div class="col-4 ps-0 pe-1">
                                                        <label for="search-status">Project:</label>
                                                    </div>

                                                    <div class="col-8 ps-1 pe-0">
                                                        <select class="form-select" id="search-project" name="search-project">
                                                            <option></option>
                                                            <?php
                                                                if ($_SESSION["role"] == 1 || $_SESSION["role"] == 4) // admin and maintenance accounts projects list
                                                                { 
                                                                    $getProjects = mysqli_query($conn, "SELECT code, name FROM projects ORDER BY code ASC");
                                                                    if (mysqli_num_rows($getProjects) > 0) // departments found
                                                                    {
                                                                        while ($proj = mysqli_fetch_array($getProjects))
                                                                        {
                                                                            echo "<option value='".$proj["code"]."'>".$proj["code"]." - ".$proj["name"]."</option>";
                                                                        }
                                                                    }
                                                                }
                                                                else if ($_SESSION["role"] == 2) // director's projects list
                                                                {
                                                                    $getProjects = mysqli_prepare($conn, "SELECT p.code, p.name FROM projects p
                                                                                                        JOIN departments d ON p.department_id=d.id 
                                                                                                        WHERE director_id=? OR secondary_director_id=? ORDER BY code ASC");
                                                                    mysqli_stmt_bind_param($getProjects, "ii", $_SESSION["id"], $_SESSION["id"]);
                                                                    if (mysqli_stmt_execute($getProjects))
                                                                    {
                                                                        $getProjectsResults = mysqli_stmt_get_result($getProjects);
                                                                        if (mysqli_num_rows($getProjectsResults) > 0) // departments found; populate list
                                                                        {
                                                                            while ($proj = mysqli_fetch_array($getProjectsResults))
                                                                            {
                                                                                echo "<option value='".$proj["code"]."'>".$proj["code"]." - ".$proj["name"]."</option>";
                                                                            }
                                                                        }
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
                                <h2 class="m-0">Manage Services</h2>
                            </div>

                            <!-- Page Management Dropdown -->
                            <div class="col-6 col-sm-4 col-md-4 col-lg-3 col-xl-2 col-xxl-2 p-0">
                                <?php if (isset($PERMISSIONS["ADD_SERVICES"]) || isset($PERMISSIONS["ADD_OTHER_SERVICES"])) { ?>
                                    <div class="dropdown float-end">
                                        <button class="btn btn-primary dropdown-toggle" type="button" id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">
                                            Manage Services
                                        </button>
                                        <ul class="dropdown-menu p-0" aria-labelledby="dropdownMenuButton1">
                                            <?php if (isset($PERMISSIONS["ADD_SERVICES"])) { ?>
                                                <li><button class="dropdown-item quickNav-dropdown-item text-center w-100 px-3 py-2 rounded-0" type="button" data-bs-toggle="modal" data-bs-target="#addServiceModal">Add Service</button></li>
                                            <?php } ?>
                                            <?php if (isset($PERMISSIONS["ADD_OTHER_SERVICES"])) { ?>
                                                <li><button class="dropdown-item quickNav-dropdown-item text-center w-100 px-3 py-2 rounded-0" type="button" data-bs-toggle="modal" data-bs-target="#addOtherServiceModal">Add Other Service</button></li>
                                            <?php } ?>
                                        </ul>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>
                    </div>

                    <div class="row report-body d-none m-0" id="services-table-div">
                        <!-- Services Table -->
                        <table id="services" class="report_table w-100">
                            <thead>
                                <tr>
                                    <th class="text-center py-1 px-2" colspan="5">Service Details</th>
                                    <th class="text-center py-1 px-2" colspan="4">WUFAR Codes</th>
                                    <th class="text-center py-1 px-2" colspan="2"><span id="table-period_totals-label"></span> Totals</th>
                                    <th class="text-center py-1 px-2" rowspan="2">Actions</th>
                                </tr>

                                <tr>
                                    <th class="text-center py-1 px-2">ID</th>
                                    <th class="text-center py-1 px-2">Name</th>
                                    <th class="text-center py-1 px-2">Description</th>
                                    <th class="text-center py-1 px-2">Cost</th>
                                    <th class="text-center py-1 px-2">Unit Label</th>
                                    <th class="text-center py-1 px-2">Fund</th>
                                    <th class="text-center py-1 px-2">Source</th>
                                    <th class="text-center py-1 px-2">Function</th>
                                    <th class="text-center py-1 px-2">Project</th>
                                    <th class="text-center py-1 px-2">Quantity</th>
                                    <th class="py-1 px-2" style="text-align: center !important;">Revenue</th>
                                </tr>
                            </thead>

                            <tfoot>
                                <tr>
                                    <th colspan="10" class="py-1 px-2" style="text-align: right !important;">TOTAL:</th>
                                    <th class="text-end py-1 px-2" id="sum-rev"></th> <!-- revenues sum -->
                                    <th colspan="2"></th> 
                                </tr>
                            </tfoot>
                        </table>
                        <?php createTableFooterV2("services", "BAP_Services_PageLength", $USER_SETTINGS["page_length"], true, true); ?>
                    </div>
                </div>

                <!--
                    ### MODALS ###
                -->
                <?php if (isset($PERMISSIONS["ADD_SERVICES"])) { ?>
                <!-- Add Service Modal -->
                <div class="modal fade" tabindex="-1" role="dialog" id="addServiceModal" aria-labelledby="addServiceModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg" role="document">
                        <div class="modal-content">
                            <div class="modal-header primary-modal-header">
                                <h5 class="modal-title primary-modal-title" id="addServiceModalLabel">Add Service</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>

                            <div class="modal-body">
                                <!-- Service Details -->
                                <fieldset class="form-group border p-1 mb-3">
                                    <legend class="w-auto px-2 m-0 float-none fieldset-legend">Service Details</legend>

                                    <div class="form-row d-flex justify-content-center align-items-center my-3">
                                        <!-- Service ID -->
                                        <div class="form-group col-3">
                                            <label for="add-service_id"><span class="required-field">*</span> Service ID:</label>
                                            <input type="text" class="form-control w-100" id="add-service_id" name="add-service_id" required>
                                        </div>

                                        <!-- Spacer -->
                                        <div class="form-group col-1"></div>

                                        <!-- Service Name -->
                                        <div class="form-group col-7">
                                            <label for="add-service_name"><span class="required-field">*</span> Name:</label>
                                            <input type="text" class="form-control w-100" id="add-service_name" name="add-service_name" required>
                                        </div>
                                    </div>

                                    <div class="form-row d-flex justify-content-center align-items-center my-3">
                                        <!-- Description -->
                                        <div class="form-group col-11">
                                            <label for="add-description">Description:</label>
                                            <textarea class="form-control w-100" id="add-description" name="add-description" rows="2" required></textarea>
                                        </div>
                                    </div>

                                    <div class="form-row d-flex justify-content-center align-items-center my-3">
                                        <!-- Unit Label -->
                                        <div class="form-group col-5">
                                            <label for="add-unit_label"><span class="required-field">*</span> Unit Label:</label>
                                            <input type="text" class="form-control w-100" id="add-unit_label" name="add-unit_label" required>
                                        </div>

                                        <!-- Spacer -->
                                        <div class="form-group col-1"></div>

                                        <!-- Export Label -->
                                        <div class="form-group col-5">
                                            <label for="add-export_label">Export Label:</label>
                                            <input type="text" class="form-control w-100" id="add-export_label" name="add-export_label" required>
                                        </div>
                                    </div>

                                    <div class="form-row d-flex justify-content-center align-items-center my-3">
                                        <!-- Fund Code -->
                                        <div class="form-group col-2">
                                            <label for="add-fund_code"><span class="required-field">*</span> Fund Code:</label>
                                            <input type="number" class="form-control w-100" id="add-fund_code" name="add-fund_code" min="10" max="99" required>
                                        </div>

                                        <!-- Spacer -->
                                        <div class="form-group col-1 p-1"></div>

                                        <!-- Source Code -->
                                        <div class="form-group col-2">
                                            <label for="add-object_code"><span class="required-field">*</span> Source Code:</label>
                                            <input type="text" class="form-control w-100" id="add-object_code" name="add-object_code" required>
                                        </div>

                                        <!-- Spacer -->
                                        <div class="form-group col-1 p-1"></div>

                                        <!-- Function Code -->
                                        <div class="form-group col-2">
                                            <label for="add-function_code"><span class="required-field">*</span> Function Code:</label>
                                            <input type="text" class="form-control w-100" id="add-function_code" name="add-function_code" required>
                                        </div>

                                        <!-- Spacer -->
                                        <div class="form-group col-1 p-1"></div>

                                        <!-- Project Code -->
                                        <div class="form-group col-2">
                                            <label for="add-project_code">Project Code:</label>
                                            <select class="form-select w-100" id="add-project_code" name="add-project_code" required>
                                                <option></option>
                                                <?php
                                                    // create a dropdown of all active projects to assign to the service
                                                    $getProjects = mysqli_prepare($conn, "SELECT p.code, p.name FROM projects p 
                                                                                        JOIN projects_status ps ON p.code=ps.code
                                                                                        WHERE ps.status=1 AND ps.period_id=?");
                                                    mysqli_stmt_bind_param($getProjects, "i", $period_id);
                                                    if (mysqli_stmt_execute($getProjects))
                                                    {
                                                        $getProjectsResults = mysqli_stmt_get_result($getProjects);
                                                        while ($project = mysqli_fetch_array($getProjectsResults))
                                                        {
                                                            $code = $project["code"];
                                                            $name = $project["name"];
                                                            if ($proj == $code) { echo "<option value=".$code." selected>".$code." - ".$name."</option>"; }
                                                            else { echo "<option value=".$code.">".$code." - ".$name."</option>"; }
                                                        }
                                                    }
                                                ?>
                                            </select>
                                        </div>
                                    </div>
                                </fieldset>

                                <!-- Service Cost -->
                                <fieldset class="form-group border p-1 mb-3">
                                    <legend class="w-auto px-2 m-0 float-none fieldset-legend">Service Cost</legend>

                                    <div class="row text-center">
                                        <p class="text-center fst-italic mb-1">
                                            <span class="required-field">*</span> setting a service's cost will only set the cost for the period selected.
                                        </p>
                                    </div>

                                    <div class="form-row d-flex justify-content-center align-items-center my-3">
                                        <!-- Cost Type -->
                                        <div class="form-group col-11">
                                            <label for="add-cost_type"><span class="required-field">*</span> Cost Type:</label>
                                            <select class="form-select w-100" id="add-cost_type" name="add-cost_type" onclick="updateCostForm('add');" required>
                                                <option value=0>Fixed</option>
                                                <option value=1>Variable</option>
                                                <option value=2>Membership</option>
                                                <option value=3>Custom Cost</option>
                                                <option value=4>Rate</option>
                                                <option value=5>Group Rates</option>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Fixed Cost -->
                                    <div id="add-fixed_cost-div" style="visibility: visible; display: block;">
                                        <div class="form-row d-flex justify-content-center align-items-center my-3">
                                            <!-- Cost -->
                                            <div class="form-group col-11">
                                                <label for="add-fixed_cost"><span class="required-field">*</span> Cost:</label>
                                                <input type="number" min="0.00" step="0.01" class="form-control w-100" id="add-fixed_cost" name="add-fixed_cost" value="0.00" required>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Variable Cost -->
                                    <div id="add-variable_cost-div" style="visibility: hidden; display: none;">
                                        <div class="row align-items-center my-2">
                                            <h3 class="text-center">Variable Cost Grid</h3>

                                            <input type="hidden" id="add-numOfRanges" value="1" aria-hidden="true">

                                            <div class="row m-0">
                                                <table>
                                                    <thead>
                                                        <tr>
                                                            <th>Order</th>
                                                            <th>Min Quantity</th>
                                                            <th>Max Quantity</th>
                                                            <th>Cost</th>
                                                        </tr>
                                                    </thead>

                                                    <tbody id="add-variable_cost-grid">
                                                        <tr id="add-variable_cost-range-1">
                                                            <td><input type="number" class="form-control" id="add-variable_cost-order-1" value="1" disabled></td>
                                                            <td><input type="number" class="form-control" id="add-variable_cost-min-1" min="0" step="1" value="0" required></td>
                                                            <td><input type="number" class="form-control" id="add-variable_cost-max-1" min="0" step="1" value="10" required></td>
                                                            <td><input type="number" class="form-control" id="add-variable_cost-cost-1" min="0.00" step="0.01" value="0.00" required></td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>

                                            <div class="row p-0 my-2">
                                                <div class="col-5"></div>
                                                <div class="col-2 text-center">
                                                    <button class="btn btn-secondary" onclick="addRange('add');"><i class="fa-solid fa-plus"></i></button>
                                                    <button class="btn btn-secondary" onclick="removeRange('add');" id="add-variable_cost-removeRangeBtn" disabled><i class="fa-solid fa-minus"></i></button>
                                                </div>
                                                <div class="col-5"></div>
                                            </div> 
                                        </div>
                                    </div>

                                    <!-- Membership Cost -->
                                    <div id="add-membership-div" style="visibility: hidden; display: none;">
                                        <div class="form-row d-flex justify-content-center align-items-center my-3">
                                            <!-- Combined Members Cost -->
                                            <div class="form-group col-11">
                                                <label for="add-membership_total_cost"><span class="required-field">*</span> Total Combined Cost:</label>
                                                <input type="number" min="0.00" step="0.01" class="form-control w-100" id="add-membership_total_cost" name="add-membership_total_cost" value="0.00" required>
                                            </div>
                                        </div>

                                        <div class="form-row d-flex justify-content-center align-items-center my-3">
                                            <!-- Membership Group -->
                                            <div class="form-group col-11">
                                                <label for="add-membership_group"><span class="required-field">*</span> Membership Group:</label>
                                                <select class="form-select w-100" id="add-membership_group" name="add-membership_group" required>
                                                    <option value="0"></option>
                                                    <?php
                                                        // create a dropdown list of all customer groups
                                                        $getGroups = mysqli_query($conn, "SELECT id, name FROM `groups` ORDER BY name ASC");
                                                        if (mysqli_num_rows($getGroups) > 0) // groups found
                                                        {
                                                            // create option for each group
                                                            while ($group = mysqli_fetch_array($getGroups))
                                                            {
                                                                echo "<option value='".$group["id"]."'>".$group["name"]."</option>";
                                                            }
                                                        }
                                                    ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Rates Cost -->
                                    <div id="add-rates_cost-div" style="visibility: hidden; display: none;">
                                        <div class="row align-items-center my-2">
                                            <h3 class="text-center">Rates</h3>

                                            <input type="hidden" id="add-rates_cost-numOfRanges" value="1" aria-hidden="true">

                                            <div class="row m-0">
                                                <table>
                                                    <thead>
                                                        <tr>
                                                            <th class="text-center w-25">Tier</th>
                                                            <th class="text-center w-75">Cost</th>
                                                        </tr>
                                                    </thead>

                                                    <tbody id="add-rates_cost-grid">
                                                        <tr id="add-rates_cost-range-1">
                                                            <td><input type="number" class="form-control" id="add-rates_cost-order-1" value="1" disabled></td>
                                                            <td><input type="number" class="form-control" id="add-rates_cost-cost-1" min="0.00" step="0.01" value="0.00" required></td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>

                                            <div class="row d-flex justify-content-center my-2">
                                                <div class="d-inline text-center fst-italic">
                                                    <span class="p-0 m-0" style="color: red;">*</span> rate tier does matter
                                                </div>
                                            </div>

                                            <div class="row d-flex justify-content-center my-2">
                                                <div class="col-2 text-center">
                                                    <button class="btn btn-secondary" onclick="addRatesRange('add');"><i class="fa-solid fa-plus"></i></button>
                                                    <button class="btn btn-secondary" onclick="removeRatesRange('add');" id="add-rates_cost-removeRangeBtn" disabled><i class="fa-solid fa-minus"></i></button>
                                                </div>
                                            </div> 
                                        </div>
                                    </div>

                                    <!-- Group Rates Cost -->
                                    <div id="add-group_rates-div" style="visibility: hidden; display: none;">
                                        <div class="row align-items-center my-2">
                                            <h3 class="text-center">Group Rates</h3>

                                            <input type="hidden" id="add-group_rates-numOfRanges" value="1" aria-hidden="true">

                                            <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                <!-- Rate Group -->
                                                <div class="form-group col-11">
                                                    <label for="add-rate_group"><span class="required-field">*</span> Rate Group:</label>
                                                    <select class="form-select w-100" id="add-rate_group" name="add-rate_group" required>
                                                        <option value="0"></option>
                                                        <?php
                                                            // create a dropdown list of all customer groups
                                                            $getGroups = mysqli_query($conn, "SELECT id, name FROM `groups` ORDER BY name ASC");
                                                            if (mysqli_num_rows($getGroups) > 0) // groups found
                                                            {
                                                                // create option for each group
                                                                while ($group = mysqli_fetch_array($getGroups))
                                                                {
                                                                    echo "<option value='".$group["id"]."'>".$group["name"]."</option>";
                                                                }
                                                            }
                                                        ?>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="row m-0">
                                                <table>
                                                    <thead>
                                                        <tr>
                                                            <th class="text-center" style="width: 25%;">Tier</th>
                                                            <th class="text-center" style="width: 37.5%;">Within Group Cost</th>
                                                            <th class="text-center" style="width: 37.5%;">Outside Of Group Cost</th>
                                                        </tr>
                                                    </thead>

                                                    <tbody id="add-group_rates-grid">
                                                        <tr id="add-group_rates-range-1">
                                                            <td><input type="number" class="form-control" id="add-group_rates-order-1" value="1" disabled></td>
                                                            <td><input type="number" class="form-control" id="add-group_rates-inside-cost-1" min="0.00" step="0.01" value="0.00" required></td>
                                                            <td><input type="number" class="form-control" id="add-group_rates-outside-cost-1" min="0.00" step="0.01" value="0.00" required></td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>

                                            <div class="row d-flex justify-content-center my-2">
                                                <div class="d-inline text-center fst-italic">
                                                    <span class="p-0 m-0" style="color: red;">*</span> rate tier does matter
                                                </div>
                                            </div>

                                            <div class="row d-flex justify-content-center my-2">
                                                <div class="col-2 text-center">
                                                    <button class="btn btn-secondary" onclick="addGroupRatesRange('add');"><i class="fa-solid fa-plus"></i></button>
                                                    <button class="btn btn-secondary" onclick="removeGroupRatesRange('add');" id="add-group_rates-removeRangeBtn" disabled><i class="fa-solid fa-minus"></i></button>
                                                </div>
                                            </div> 
                                        </div>
                                    </div>

                                    <div class="form-row d-flex justify-content-center align-items-center my-3">
                                        <!-- Round Costs -->
                                        <div class="form-group col-11">
                                            <label for="add-round"><span class="required-field">*</span> Round Costs:</label>
                                            <button class="btn btn-danger w-100" id="add-round" value=0 onclick="updateRoundCosts('add-round');">No</button>
                                        </div>
                                    </div>
                                </fieldset>

                                <!-- Required Field Indicator -->
                                <div class="row justify-content-center">
                                    <div class="col-11 text-center fst-italic">
                                        <span class="required-field">*</span> indicates a required field
                                    </div>
                                </div>
                            </div>

                            <div class="modal-footer">
                                <button type="button" class="btn btn-primary" onclick="addService();"><i class="fa-solid fa-plus"></i> Add Service</button>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> Close</button>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- End Add Service Modal -->
                <?php } ?>

                <?php if (isset($PERMISSIONS["EDIT_SERVICES"])) { ?>
                <!-- Edit Service Modal -->
                <div id="edit-service-modal-div"></div>
                <!-- End Edit Service Modal -->
                <?php } ?>

                <?php if (isset($PERMISSIONS["DELETE_SERVICES"])) { ?>
                <!-- Delete Service Modal -->
                <div id="delete-service-modal-div"></div>
                <!-- End Delete Service Modal -->
                <?php } ?>

                <!-- View Service Modal -->
                <div id="view-service-modal-div"></div>
                <!-- End View Service Modal -->

                <!-- Add Other Service Modal -->
                <div class="modal fade" tabindex="-1" role="dialog" id="addServiceModal" aria-labelledby="addServiceModalLabel" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header primary-modal-header">
                                <h5 class="modal-title primary-modal-title" id="addServiceModalLabel">Add Other Service</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>

                            <div class="modal-body">
                                <!-- Service Details -->
                                <fieldset class="form-group border p-3 mb-3">
                                    <legend class="w-auto px-2 m-0 float-none fieldset-legend">Service Details</legend>

                                    <div class="row align-items-center my-2">
                                        <div class="col-4 text-end"><label for="add-service_id"><span class="required-field">*</span> Service ID:</label></div>
                                        <div class="col-8"><input type="text" class="form-control w-100" id="add-service_id" name="add-service_id" required></div>
                                    </div>

                                    <div class="row align-items-center my-2">
                                        <div class="col-4 text-end"><label for="add-service_name"><span class="required-field">*</span> Name:</label></div>
                                        <div class="col-8"><input type="text" class="form-control w-100" id="add-service_name" name="add-service_name" required></div>
                                    </div>

                                    <div class="row align-items-center my-2">
                                        <div class="col-4 text-end"><label for="add-export_label">Export Label:</label></div>
                                        <div class="col-8"><input type="text" class="form-control w-100" id="add-export_label" name="add-export_label"></div>
                                    </div>
                                </fieldset>

                                <!-- WUFAR Codes -->
                                <fieldset class="form-group border p-3 mb-3">
                                    <legend class="w-auto px-2 m-0 float-none fieldset-legend">WUFAR Codes</legend>
                                    <div class="row align-items-center my-2">
                                        <div class="col-4 text-end"><label for="add-fund_code"><span class="required-field">*</span> Fund Code:</label></div>
                                        <div class="col-8"><input type="number" class="form-control w-100" id="add-fund_code" name="add-fund_code" min="10" max="99" required></div>
                                    </div>

                                    <div class="row align-items-center my-2">
                                        <div class="col-4 text-end"><label for="add-source_code"><span class="required-field">*</span> Source Code:</label></div>
                                        <div class="col-8"><input type="text" class="form-control w-100" id="add-source_code" name="add-source_code" required></div>
                                    </div>

                                    <div class="row align-items-center my-2">
                                        <div class="col-4 text-end"><label for="add-function_code"><span class="required-field">*</span> Function Code:</label></div>
                                        <div class="col-8"><input type="text" class="form-control w-100" id="add-function_code" name="add-function_code" required></div>
                                    </div>
                                </fieldset>
                            </div>
                            
                            <div class="modal-footer">
                                <button type="button" class="btn btn-primary" onclick="addOtherService();"><i class="fa-solid fa-floppy-disk"></i> Save New Service</button>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> Close</button>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- End Add Other Service Modal -->

                <!-- Provide Other Service Modal -->
                <div class="modal fade" tabindex="-1" role="dialog" id="provideServiceModal" aria-labelledby="provideServiceModalLabel" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header primary-modal-header">
                                <h5 class="modal-title primary-modal-title" id="provideServiceModalLabel">Provide Other Service</h5>
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
                                <button type="button" class="btn btn-primary" onclick="provideService();"><i class="fa-solid fa-floppy-disk"></i> Provide Service</button>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> Close</button>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- End Provide Other Service Modal -->
                <!--
                    ### END MODALS ###
                -->

                <script>
                    // initialize variable to state if we've drawn the table or not
                    var drawn = 0; // assume we have not drawn the table (0)

                    // get the current active period
                    let active_period = "<?php echo $active_period_label; ?>"; 

                    // set page length to prior saved state
                    let saved_page_length = sessionStorage["BAP_Services_PageLength"];
                    if (saved_page_length != "" && saved_page_length != null && saved_page_length != undefined)
                    {
                        $("#services-DT_PageLength").val(sessionStorage["BAP_Services_PageLength"]);
                    }

                    <?php if (isset($PERMISSIONS["ADD_SERVICES"])) { ?>
                        // run the function to create the projects dropdown
                        createProjectsDropdown();
                    <?php } ?>

                    // set the search filters to values we have saved in storage
                    $('#search-all').val(sessionStorage["BAP_Services_Search_All"]);
                    if (sessionStorage["BAP_Services_Search_Period"] != "" && sessionStorage["BAP_Services_Search_Period"] != null && sessionStorage["BAP_Services_Search_Period"] != undefined) { $('#search-period').val(sessionStorage["BAP_Services_Search_Period"]); }
                    else { $('#search-period').val(active_period); } // no period set; default to active period 
                    $('#search-cost_type').val(sessionStorage["BAP_Services_Search_CostType"]);
                    $('#search-project').val(sessionStorage["BAP_Services_Search_Project"]);

                    /** function to search the services based on the period selected */
                    function searchServices()
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
                            sessionStorage["BAP_Services_Search_Period"] = period;

                            // if we have already drawn the table, destroy existing table
                            if (drawn == 1) { $("#services").DataTable().destroy(); }

                            // initialize the services table
                            var services = $("#services").DataTable({
                                ajax: {
                                    url: "ajax/services/manage/getServices.php",
                                    type: "POST",
                                    data: {
                                        period: period
                                    }
                                },
                                autoWidth: false,
                                pageLength: <?php if (isset($USER_SETTINGS["page_length"])) { echo $USER_SETTINGS["page_length"]; } else { echo 10; } ?>,
                                lengthMenu: [ [10, 25, 50, 100, 250, -1], [10, 25, 50, 100, 250, "All"] ],
                                columns: [
                                    { data: "id", orderable: true, width: "5%", className: "text-center" },
                                    { data: "name", orderable: true, width: "15%", className: "text-center" },
                                    { data: "description", orderable: true, width: "17.5%", className: "text-center" },
                                    { data: "cost", orderable: true, width: "8%", className: "text-center" },
                                    { data: "unit_label", orderable: true, width: "7%", className: "text-center" },
                                    { data: "fund_code", orderable: true, width: "5%", className: "text-center" },
                                    { data: "object_code", orderable: true, width: "6%", className: "text-center" },
                                    { data: "function_code", orderable: true, width: "6%", className: "text-center" },
                                    { data: "project_code", orderable: true, width: "6%", className: "text-center" },
                                    { data: "total_qty", orderable: true, width: "7.5%", className: "text-center" },
                                    { data: "total_rev", orderable: true, width: "9%", className: "text-end" },
                                    { data: "actions", orderable: false, width: "7.5%" },
                                    { data: "cost_type", orderable: false, visible: false },
                                    { data: "calc_total_qty", orderable: false, visible: false },
                                    { data: "calc_total_rev", orderable: false, visible: false }
                                ],
                                dom: 'rt',
                                language: {
                                    search: '<div class="input-group mb-1"><div class="input-group-prepend"><span class="d-flex align-items-center h-100 mx-2" id="nav-search-icon"><i class="fa fa-search" aria-hidden="true"></i></span></div>_INPUT_</div>',
                                    lengthMenu: '<div class="mx-1 mb-1">Show _MENU_ entries</div>',
                                    info: '<div class="mx-1">Showing _START_ to _END_ of _TOTAL_ entries</div>'
                                },
                                order: [
                                    [0, "asc"]
                                ],
                                stateSave: true,
                                drawCallback: function ()
                                {
                                    var api = this.api();

                                    // get the sum of all filtered quarterly costs
                                    let rev_sum = api.column(14, { search: "applied" }).data().sum().toFixed(2);

                                    // update the table footer
                                    document.getElementById("sum-rev").innerHTML = "$"+numberWithCommas(rev_sum);
                                },
                                rowCallback: function (row, data, index)
                                {
                                    updatePageSelection("services");
                                },
                            });

                            // mark that we have drawn the table
                            drawn = 1;

                            // search table by custom search filter
                            $('#search-all').keyup(function() {
                                services.search($(this).val()).draw();
                                sessionStorage["BAP_Services_Search_All"] = $(this).val();
                            });

                            // search the hidden "Cost Type" column
                            $('#search-cost_type').change(function() {
                                services.columns(12).search($(this).val()).draw();
                                sessionStorage["BAP_Services_Search_CostType"] = $(this).val();
                            });

                            // search services by project code
                            $('#search-project').change(function() {
                                services.columns(8).search($(this).val()).draw();
                                sessionStorage["BAP_Services_Search_Project"] = $(this).val();
                            });
                            
                            // function to clear search filters
                            $('#clearFilters').click(function() {
                                sessionStorage["BAP_Services_Search_CostType"] = "";
                                sessionStorage["BAP_Services_Search_Project"] = "";
                                sessionStorage["BAP_Services_Search_All"] = "";
                                $('#search-all').val("");
                                $('#search-cost_type').val("");
                                $('#search-project').val("");
                                services.search("").columns().search("").draw();
                            });

                            // display the table
                            document.getElementById("services-table-div").classList.remove("d-none");
                        }
                        else { createStatusModal("alert", "Loading Services Error", "Failed to load services. You must select a period to display services for."); }
                    }

                    // search services from the default parameters
                    searchServices();
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