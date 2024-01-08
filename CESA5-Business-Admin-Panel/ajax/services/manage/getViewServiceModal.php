<?php
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get additional required files
        include("../../../includes/config.php");
        include("../../../includes/functions.php");
        
        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "VIEW_SERVICES_ALL") || checkUserPermission($conn, "VIEW_SERVICES_ASSIGNED"))
        {
            // get the service ID from POST
            if (isset($_POST["service_id"]) && $_POST["service_id"] <> "") { $service_id = $_POST["service_id"]; } else { $service_id = null; }
            if (isset($_POST["period_id"]) && $_POST["period_id"] <> "") { $period_id = $_POST["period_id"]; } else { $period_id = null; }

            if ($service_id != null && $period_id != null)
            {
                if (verifyPeriod($conn, $period_id))
                {
                    // get the current service details based on service ID
                    $getServiceDetails = mysqli_prepare($conn, "SELECT * FROM services WHERE id=?");
                    mysqli_stmt_bind_param($getServiceDetails, "s", $service_id);
                    if (mysqli_stmt_execute($getServiceDetails))
                    {
                        $serviceResults = mysqli_stmt_get_result($getServiceDetails);
                        if (mysqli_num_rows($serviceResults) > 0)
                        {                                
                            // initialize vars
                            $cost = 0.00;
                            $var_costs = [];
                            $rates = [];
                            $inside_costs = [];
                            $outside_costs = [];

                            // store service details as local variables
                            $serviceDetails = mysqli_fetch_array($serviceResults);
                            $service_name = $serviceDetails["name"];
                            $cost_type = $serviceDetails["cost_type"];

                            // get service cost data
                            $getServiceCost = mysqli_prepare($conn, "SELECT * FROM costs WHERE service_id=? AND cost_type=? AND period_id=?");
                            mysqli_stmt_bind_param($getServiceCost, "sii", $service_id, $cost_type, $period_id);
                            if (mysqli_stmt_execute($getServiceCost))
                            {
                                $costResults = mysqli_stmt_get_result($getServiceCost);
                                if (mysqli_num_rows($costResults) > 0)
                                {
                                    // FIXED COST
                                    if ($cost_type == 0) { $cost = mysqli_fetch_array($costResults)["cost"]; }
                                    // VARIABLE COST
                                    else if ($cost_type == 1) { while ($range = mysqli_fetch_array($costResults)) { $var_costs[] = $range; } }
                                    // MEMBERSHIP COST
                                    else if ($cost_type == 2)
                                    {
                                        $cost_details = mysqli_fetch_array($costResults);
                                        $membership_group = $cost_details["group_id"];
                                        $total_membership_cost = $cost_details["cost"];
                                    }
                                    // CUSTOM COST
                                    else if ($cost_type == 3) { }
                                    // RATES COST
                                    else if ($cost_type == 4) { while ($range = mysqli_fetch_array($costResults)) { $rates[] = $range; } }
                                    // GROUP RATES COST
                                    else if ($cost_type == 5) 
                                    { 
                                        while ($range = mysqli_fetch_array($costResults)) 
                                        { 
                                            $rate_group = $range["group_id"];
                                            if ($range["in_group"] == 0) { $outside_costs[] = $range; }
                                            else if ($range["in_group"] == 1) { $inside_costs[] = $range; }
                                        } 
                                    }
                                }
                            }

                            ?>
                                <!-- View Service Modal -->
                                <div class="modal fade" tabindex="-1" role="dialog" id="viewServiceModal" data-bs-backdrop="static" aria-labelledby="viewServiceModalLabel" aria-hidden="true">
                                    <div class="modal-dialog modal-lg" role="document">
                                        <div class="modal-content">
                                            <!-- Header -->
                                            <div class="modal-header primary-modal-header">
                                                <h5 class="modal-title primary-modal-title" id="viewServiceModalLabel"><?php echo $service_name; ?></h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <!-- End Header -->

                                            <!-- Body -->
                                            <div class="modal-body">
                                                <!-- Fixed Cost -->
                                                <div id="edit-fixed_cost-div" <?php if ($cost_type == 0) { ?> style="visibility: visible; display: block;" <?php } else { ?> style="visibility: hidden; display: none;" <?php } ?>>
                                                    <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                        <!-- Cost Type -->
                                                        <div class="form-group col-12">
                                                            <label for="edit-fixed_cost"><span class="required-field">*</span> Cost:</label>
                                                            <input type="number" min="0.00" step="0.01" class="form-control w-100" id="edit-fixed_cost" name="edit-fixed_cost" value="<?php echo $cost; ?>" disabled readonly>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Variable Cost -->
                                                <div id="edit-variable_cost-div" <?php if ($cost_type == 1) { ?> style="visibility: visible; display: block;" <?php } else { ?> style="visibility: hidden; display: none;" <?php } ?>>
                                                    <div class="row align-items-center my-2">
                                                        <h3 class="text-center">Variable Cost Grid</h3>

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

                                                                <tbody id="edit-variable_cost-grid">
                                                                    <?php for ($order = 1; $order <= count($var_costs); $order++) { 
                                                                        if (isset($var_costs[$order - 1]) && is_numeric($var_costs[$order - 1]["min_quantity"])) { ?>
                                                                            <tr id="edit-variable_cost-range-<?php echo $order; ?>">
                                                                                <td><input type="number" class="form-control" id="edit-variable_cost-order-<?php echo $order; ?>" value="<?php echo $order; ?>" disabled readonly></td>
                                                                                <td><input type="number" class="form-control" id="edit-variable_cost-min-<?php echo $order; ?>" min="0" step="1" value="<?php echo $var_costs[$order - 1]["min_quantity"]; ?>" disabled readonly></td>
                                                                                <td><input type="number" class="form-control" id="edit-variable_cost-max-<?php echo $order; ?>" min="0" step="1" value="<?php if ($var_costs[$order - 1]["max_quantity"] != -1) { echo $var_costs[$order - 1]["max_quantity"]; } else { echo ""; } ?>" disabled readonly></td>
                                                                                <td><input type="number" class="form-control" id="edit-variable_cost-cost-<?php echo $order; ?>" min="0.00" step="0.01" value="<?php echo $var_costs[$order - 1]["cost"]; ?>" <?php if ($order == 1) { ?> value="0.00" <?php } ?> disabled readonly></td>
                                                                            </tr>
                                                                        <?php }
                                                                    } ?>
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <!-- Membership Cost -->
                                                <div id="edit-membership-div" <?php if ($cost_type == 2) { ?> style="visibility: visible; display: block;" <?php } else { ?> style="visibility: hidden; display: none;" <?php } ?>>
                                                    <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                        <!-- Combined Members Cost -->
                                                        <div class="form-group col-12">
                                                            <label for="edit-membership_total_cost"><span class="required-field">*</span> Total Combined Cost:</label>
                                                            <input type="number" min="0.00" step="0.01" class="form-control w-100" id="edit-membership_total_cost" name="edit-membership_total_cost" value="<?php if (isset($total_membership_cost)) { echo $total_membership_cost; } ?>" disabled readonly>
                                                        </div>
                                                    </div>

                                                    <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                        <!-- Membership Group -->
                                                        <div class="form-group col-12">
                                                            <label for="edit-membership_group"><span class="required-field">*</span> Membership Group:</label>
                                                            <select class="form-select w-100" id="edit-membership_group" name="edit-membership_group" disabled readonly>
                                                                <option value="0"></option>
                                                                <?php
                                                                    // create a dropdown list of all customer groups
                                                                    $getGroups = mysqli_query($conn, "SELECT id, name FROM `groups` ORDER BY name ASC");
                                                                    if (mysqli_num_rows($getGroups) > 0) // groups found
                                                                    {
                                                                        // create option for each group
                                                                        while ($group = mysqli_fetch_array($getGroups))
                                                                        {
                                                                            if (isset($membership_group) && ($group["id"] == $membership_group)) { echo "<option value='".$group["id"]."' selected>".$group["name"]."</option>"; }
                                                                            else { echo "<option value='".$group["id"]."'>".$group["name"]."</option>"; }
                                                                        }
                                                                    }
                                                                ?>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Rates Cost -->
                                                <div id="edit-rates_cost-div" <?php if ($cost_type == 4) { ?> style="visibility: visible; display: block;" <?php } else { ?> style="visibility: hidden; display: none;" <?php } ?>>
                                                    <div class="row align-items-center my-2">
                                                        <h3 class="text-center">Rates</h3>

                                                        <div class="row m-0">
                                                            <table>
                                                                <thead>
                                                                    <tr>
                                                                        <th class="text-center w-25">Tier</th>
                                                                        <th class="text-center w-75">Cost</th>
                                                                    </tr>
                                                                </thead>

                                                                <tbody id="edit-rates_cost-grid">
                                                                    <?php for ($tier = 1; $tier <= count($rates); $tier++) { 
                                                                        if (isset($rates[$tier - 1]) && is_numeric($rates[$tier - 1]["cost"])) { ?>
                                                                            <tr id="edit-rates_cost-range-<?php echo $tier; ?>">
                                                                                <td><input type="number" class="form-control" id="edit-rates_cost-order-<?php echo $tier; ?>" value="<?php echo $tier; ?>" disabled readonly></td>
                                                                                <td><input type="number" class="form-control" id="edit-rates_cost-cost-<?php echo $tier; ?>" min="0.00" step="0.01" value="<?php echo $rates[$tier - 1]["cost"]; ?>" <?php if ($tier == 1) { ?> value="0.00" <?php } ?> disabled readonly></td>
                                                                            </tr>
                                                                        <?php }
                                                                    } ?>
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Group Rates Cost -->
                                                <div id="edit-group_rates-div" <?php if ($cost_type == 5) { ?> style="visibility: visible; display: block;" <?php } else { ?> style="visibility: hidden; display: none;" <?php } ?>>
                                                    <div class="row align-items-center my-2">
                                                        <h3 class="text-center">Group Rates</h3>

                                                        <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                            <!-- Rate Group -->
                                                            <div class="form-group col-12">
                                                                <label for="edit-rate_group"><span class="required-field">*</span> Rate Group:</label>
                                                                <select class="form-select w-100" id="edit-rate_group" name="edit-rate_group" disabled readonly>
                                                                    <option value="0"></option>
                                                                    <?php
                                                                        // create a dropdown list of all customer groups
                                                                        $getGroups = mysqli_query($conn, "SELECT id, name FROM `groups` ORDER BY name ASC");
                                                                        if (mysqli_num_rows($getGroups) > 0) // groups found
                                                                        {
                                                                            // create option for each group
                                                                            while ($group = mysqli_fetch_array($getGroups))
                                                                            {
                                                                                if (isset($rate_group) && ($group["id"] == $rate_group)) { echo "<option value='".$group["id"]."' selected>".$group["name"]."</option>"; }
                                                                                else { echo "<option value='".$group["id"]."'>".$group["name"]."</option>"; }
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

                                                                <tbody id="edit-group_rates-grid">
                                                                    <?php for ($tier = 1; $tier <= count($inside_costs); $tier++) { 
                                                                        if ((isset($inside_costs[$tier - 1]) && is_numeric($inside_costs[$tier - 1]["cost"])) && (isset($outside_costs[$tier - 1]) && is_numeric($outside_costs[$tier - 1]["cost"]))) { ?>
                                                                            <tr id="edit-group_rates-range-<?php echo $tier; ?>">
                                                                                <td><input type="number" class="form-control" id="edit-group_rates-order-<?php echo $tier; ?>" value="<?php echo $tier; ?>" disabled readonly></td>
                                                                                <td><input type="number" class="form-control" id="edit-group_rates-inside-cost-<?php echo $tier; ?>" min="0.00" step="0.01" value="<?php echo $inside_costs[$tier - 1]["cost"]; ?>" <?php if ($tier == 1) { ?> value="0.00" <?php } ?> disabled readonly></td>
                                                                                <td><input type="number" class="form-control" id="edit-group_rates-outside-cost-<?php echo $tier; ?>" min="0.00" step="0.01" value="<?php echo $outside_costs[$tier - 1]["cost"]; ?>" <?php if ($tier == 1) { ?> value="0.00" <?php } ?> disabled readonly></td>
                                                                            </tr>
                                                                        <?php }
                                                                    } ?>
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <!-- Customer -->
                                                    <div class="col-5 px-2">
                                                        <label for="view-customer_id">Customer:</label>
                                                        <select class="form-select w-100" id="view-customer_id" name="view-customer_id" onchange="">
                                                            <option></option>
                                                            <?php
                                                                $getCustomers = mysqli_prepare($conn, "SELECT id, name FROM customers WHERE active=1 ORDER BY name ASC");
                                                                if (mysqli_stmt_execute($getCustomers))
                                                                {
                                                                    $results = mysqli_stmt_get_result($getCustomers);
                                                                    while ($customer = mysqli_fetch_array($results)) { echo "<option value='".$customer["id"]."'>".$customer["name"]."</option>"; }
                                                                }
                                                            ?> 
                                                        </select>
                                                    </div>

                                                    <!-- Quantity -->
                                                    <div class="col-2 px-2">
                                                        <label for="view-quantity">Quantity:</label>
                                                        <input type="number" class="form-control w-100" id="view-quantity" name="view-quantity" onchange="">
                                                    </div>

                                                    <!-- Calculate -->
                                                    <div class="col-1 px-2">
                                                        <label for="view-quantity"><span style="visibility: hidden;">Calculate:</span></label>
                                                        <button class="btn btn-success w-100" onclick="estimateCost('<?php echo $service_id; ?>');">
                                                            <i class="fa-solid fa-calculator"></i>
                                                        </button>
                                                    </div>

                                                    <!-- Estimate -->
                                                    <div class="col-4 px-2">
                                                        <label for="view-estimated_cost">Estimated Cost:</label>
                                                        <div class="input-group mb-3">
                                                            <span class="input-group-text"><i class="fa-solid fa-dollar-sign"></i></span>
                                                            <input type="text" class="form-control" id="view-estimated_cost" name="view-estimated_cost" value="0" readonly disabled>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <!-- End Body -->

                                            <!-- Footer -->
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> Close</button>
                                            </div>
                                            <!-- End Footer -->
                                        </div>
                                    </div>
                                </div>
                                <!-- End View Service Modal -->
                            <?php
                        }
                    }
                }
            }
        }

        // disconnect from the database
        mysqli_close($conn);
    }
?>