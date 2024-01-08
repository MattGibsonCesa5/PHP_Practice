<?php
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get additional required files
        include("../../../includes/config.php");
        include("../../../includes/functions.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "EDIT_INVOICES"))
        {
            // get the service ID from POST
            if (isset($_POST["invoice_id"]) && $_POST["invoice_id"] <> "") { $invoice_id = $_POST["invoice_id"]; } else { $invoice_id = null; }

            if ($invoice_id != null && $invoice_id <> "")
            {
                // get the current invoice details to generate the modal
                $getInvoice = mysqli_prepare($conn, "SELECT * FROM services_provided WHERE id=?");
                mysqli_stmt_bind_param($getInvoice, "i", $invoice_id);
                if (mysqli_stmt_execute($getInvoice));
                {
                    $getInvoiceResult = mysqli_stmt_get_result($getInvoice);
                    if (mysqli_num_rows($getInvoiceResult) > 0) // invoice exists; continue
                    {
                        $invoice = mysqli_fetch_array($getInvoiceResult);

                        $service_id = $invoice["service_id"];
                        $customer_id = $invoice["customer_id"];
                        $period_id = $invoice["period_id"];
                        $quantity = $invoice["quantity"];
                        $description = $invoice["description"];
                        $date = $invoice["date_provided"];
                        $cost = $invoice["total_cost"];
                        $allow_zero = $invoice["allow_zero"];

                        // get the service name and cost type
                        $getServiceInfo = mysqli_prepare($conn, "SELECT name, cost_type FROM services WHERE id=?");
                        mysqli_stmt_bind_param($getServiceInfo, "s", $service_id);
                        if (mysqli_stmt_execute($getServiceInfo))
                        {
                            $getServiceInfoResult = mysqli_stmt_get_result($getServiceInfo);
                            if (mysqli_num_rows($getServiceInfoResult) > 0) 
                            { 
                                $service_info = mysqli_fetch_array($getServiceInfoResult); 
                                $service_name = $service_info["name"];
                                $cost_type = $service_info["cost_type"];
                            }
                            else 
                            {
                                $service_name = "";
                                $cost_type = 0; 
                            }
                        }

                        // get the customer name
                        $getCustomerName = mysqli_prepare($conn, "SELECT name FROM customers WHERE id=?");
                        mysqli_stmt_bind_param($getCustomerName, "i", $customer_id);
                        if (mysqli_stmt_execute($getCustomerName))
                        {
                            $getCustomerNameResult = mysqli_stmt_get_result($getCustomerName);
                            if (mysqli_num_rows($getCustomerNameResult) > 0) { $customer_name = mysqli_fetch_array($getCustomerNameResult)["name"]; }
                            else { $customer_name = ""; }
                        }

                        // create the modal
                        ?>
                            <div class="modal fade" tabindex="-1" role="dialog" id="editInvoiceModal" aria-labelledby="editInvoiceModalLabel" aria-hidden="true">
                                <div class="modal-dialog" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header primary-modal-header">
                                            <h5 class="modal-title primary-modal-title" id="editInvoiceModalLabel">Edit Invoice</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>

                                        <div class="modal-body">
                                            <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                <!-- Invoice ID -->
                                                <div class="form-group col-11">
                                                    <label for="edit-service"><span class="required-field">*</span> Invoice ID:</label>
                                                    <input type="text" class="form-control w-100" id="edit-invoice_id" name="edit-invoice_id" value="<?php echo $invoice_id; ?>" disabled>
                                                </div>
                                            </div>

                                            <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                <!-- Service -->
                                                <div class="form-group col-11">
                                                    <label for="edit-service"><span class="required-field">*</span> Service:</label>
                                                    <input type="hidden" class="form-control w-100" id="edit-service" name="edit-service" value="<?php echo $service_id; ?>" aria-hidden="true" disabled>
                                                    <input type="text" class="form-control w-100" id="edit-service_name" name="edit-service_name" value="<?php echo $service_name; ?>" disabled>
                                                </div>
                                            </div>

                                            <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                <!-- Customer -->
                                                <div class="form-group col-11">
                                                    <label for="edit-customer"><span class="required-field">*</span> Customer:</label>
                                                    <input type="hidden" class="form-control w-100" id="edit-customer" name="edit-customer" value="<?php echo $customer_id; ?>" aria-hidden="true" disabled>
                                                    <input type="text" class="form-control w-100" id="edit-customer_name" name="edit-customer_name" value="<?php echo $customer_name; ?>" disabled>
                                                </div>
                                            </div>

                                            <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                <!-- Date Provided -->
                                                <div class="form-group col-11">
                                                    <label for="edit-date"><span class="required-field">*</span> Date Provided:</label>
                                                    <input type="text" class="form-control w-100" id="edit-date" name="edit-date" value="<?php echo date("m/d/Y", strtotime($date)); ?>" required>
                                                </div>
                                            </div>

                                            <div class="form-row <?php if ($cost_type == 4) { echo "d-none"; } else { echo "d-flex"; } ?> justify-content-center align-items-center my-3" id="edit-custom_cost-div">
                                                <!-- Quantity -->
                                                <div class="form-group col-11">
                                                    <label for="edit-quantity"><span class="required-field">*</span> Quantity:</label>
                                                    <input type="number" class="form-control w-100" id="edit-quantity" name="edit-quantity" value="<?php echo $quantity; ?>" onchange="updateCost('edit-quantity', 'edit-annual_cost', 'edit');" required>
                                                </div>
                                            </div>

                                            <div class="form-row <?php if ($cost_type == 3) { echo "d-flex"; } else { echo "d-none"; } ?> justify-content-center align-items-center my-3" id="edit-custom_cost-div">
                                                <!-- Custom Cost -->
                                                <div class="form-group col-11">
                                                    <label for="edit-custom_cost"><span class="required-field">*</span> Cost:</label>
                                                    <input type="number" class="form-control w-100" id="edit-custom_cost" name="edit-custom_cost" value="<?php echo $cost; ?>" onchange="updateCost('edit-quantity', 'edit-annual_cost', 'edit');" required>
                                                </div>
                                            </div>

                                            <div class="form-row <?php if ($cost_type == 4) { echo "d-flex"; } else { echo "d-none"; } ?> justify-content-center align-items-center my-3" id="edit-rate-div">
                                                <!-- Rate Tier -->
                                                <div class="form-group col-11">
                                                    <label for="edit-rate"><span class="required-field">*</span> Rate Tier:</label>
                                                    <div id="edit-rate-select-div">
                                                        <select class="form-select w-100" id="edit-rate" name="edit-rate" required>
                                                            <option></option>
                                                            <?php
                                                                // get each rate the service has
                                                                $getRates = mysqli_prepare($conn, "SELECT variable_order, cost FROM costs WHERE service_id=? AND period_id=? AND cost_type=4");
                                                                mysqli_stmt_bind_param($getRates, "si", $service_id, $period_id);
                                                                if (mysqli_stmt_execute($getRates))
                                                                {
                                                                    $getRatesResults = mysqli_stmt_get_result($getRates);
                                                                    if (mysqli_num_rows($getRatesResults) > 0) // rates found
                                                                    {
                                                                        // for each rate found, create a dropdown option
                                                                        while ($rate = mysqli_fetch_array($getRatesResults))
                                                                        {
                                                                            $tier = $rate["variable_order"];
                                                                            $rate_cost = $rate["cost"];
                                                                            if ($cost == $rate_cost) { echo "<option value='".$tier."' selected>Tier ".$tier." - ".printDollar($rate_cost)."</option>"; }
                                                                            else { echo "<option value='".$tier."'>Tier ".$tier." - ".printDollar($rate_cost)."</option>"; }
                                                                        }
                                                                    }
                                                                }
                                                            ?>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="form-row <?php if ($cost_type == 5) { echo "d-flex"; } else { echo "d-none"; } ?> justify-content-center align-items-center my-3" id="edit-rate-div">
                                                <!-- Group Rate Tier -->
                                                <div class="form-group col-11">
                                                    <label for="edit-group_rate"><span class="required-field">*</span> Group Rate Tier:</label>
                                                    <div id="edit-rate-select-div">
                                                        <select class="form-select w-100" id="edit-group_rate" name="edit-group_rate" required>
                                                            <option></option>
                                                            <?php
                                                                // get the cost associated to the selected tier
                                                                $getRateGroup = mysqli_prepare($conn, "SELECT group_id FROM costs WHERE service_id=? AND period_id=? AND variable_order=1 AND cost_type=5 LIMIT 1");
                                                                mysqli_stmt_bind_param($getRateGroup, "si", $service_id, $period_id);
                                                                if (mysqli_stmt_execute($getRateGroup))
                                                                {
                                                                    $getRateGroupResult = mysqli_stmt_get_result($getRateGroup);
                                                                    if (mysqli_num_rows($getRateGroupResult) > 0) // group found found
                                                                    {
                                                                        // store the rate group locally
                                                                        $rate_group = mysqli_fetch_array($getRateGroupResult)["group_id"];

                                                                        // check to see if the customer is a member of the group
                                                                        $isMember = 0; // assume customer is not a member of the group 
                                                                        $checkMembership = mysqli_prepare($conn, "SELECT id FROM group_members WHERE group_id=? AND customer_id=?");
                                                                        mysqli_stmt_bind_param($checkMembership, "ii", $rate_group, $customer_id);
                                                                        if (mysqli_stmt_execute($checkMembership))
                                                                        {
                                                                            $checkMembershipResult = mysqli_stmt_get_result($checkMembership);
                                                                            if (mysqli_num_rows($checkMembershipResult) > 0) { $isMember = 1; }
                                                                        }

                                                                        // get each group rate the service has based on customer membership
                                                                        $getGroupRates = mysqli_prepare($conn, "SELECT variable_order, cost FROM costs WHERE service_id=? AND period_id=? AND in_group=? AND cost_type=5");
                                                                        mysqli_stmt_bind_param($getGroupRates, "sii", $service_id, $period_id, $isMember);
                                                                        if (mysqli_stmt_execute($getGroupRates))
                                                                        {
                                                                            $getGroupRatesResults = mysqli_stmt_get_result($getGroupRates);
                                                                            if (mysqli_num_rows($getGroupRatesResults) > 0) // rates found
                                                                            {
                                                                                // for each rate found, create a dropdown option
                                                                                while ($group_rate = mysqli_fetch_array($getGroupRatesResults))
                                                                                {
                                                                                    $tier = $group_rate["variable_order"];
                                                                                    $rate_cost = $group_rate["cost"];
                                                                                    if ($cost == $rate_cost) { echo "<option value='".$tier."' selected>Tier ".$tier." - ".printDollar($rate_cost)."</option>"; }
                                                                                    else { echo "<option value='".$tier."'>Tier ".$tier." - ".printDollar($rate_cost)."</option>"; }
                                                                                }
                                                                            }
                                                                        }
                                                                    }
                                                                }
                                                            ?>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                <!-- Billing Notes -->
                                                <div class="form-group col-11">
                                                    <label for="edit-description">Billing Notes:</label>
                                                    <textarea class="form-control w-100" id="edit-description" name="edit-description" rows="3"><?php echo $description; ?></textarea>
                                                </div>
                                            </div>

                                            <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                <!-- Allow Zeroed Out Cost -->
                                                <div class="form-group col-11">
                                                    <label for="edit-zero"><span class="required-field">*</span>Allow Zero Total Cost:</label>
                                                    <?php if ($allow_zero == 1) { ?>
                                                        <button class="btn btn-success btn-sm w-100" id="edit-zero" value=1 onclick="updateZeroCosts('edit-zero');">Yes</button>
                                                    <?php } else { ?>
                                                        <button class="btn btn-danger btn-sm w-100" id="edit-zero" value=0 onclick="updateZeroCosts('edit-zero');">No</button>
                                                    <?php } ?>
                                                </div>
                                            </div>

                                            <div class="form-row <?php if ($cost_type != 3 && $cost_type != 4) { echo "d-flex"; } else { echo "d-none"; } ?> justify-content-center align-items-center my-3" id="edit-preview_cost-div">
                                                <!-- Estimated Cost -->
                                                <div class="form-group col-11 d-inline text-center">    
                                                    <div class="preview_cost-label d-inline">Estimated Annual Cost:</div>
                                                    <div class="preview_cost-cost d-inline" id="edit-annual_cost"><?php echo printDollar($cost); ?></div>
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
                                            <button type="button" class="btn btn-primary" onclick="editInvoice('<?php echo $invoice_id; ?>');"><i class="fa-solid fa-floppy-disk"></i> Save Changes</button>
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> Close</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php
                    }
                }
            }
        } 
        
        // disconnect from the database
        mysqli_close($conn);
    }
?>