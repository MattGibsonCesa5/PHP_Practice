<?php
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get additional required files
        include("../../../includes/config.php");
        include("../../../includes/functions.php");
        
        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "EDIT_SERVICES"))
        {
            // get the service ID from POST
            if (isset($_POST["service_id"]) && $_POST["service_id"] <> "") { $service_id = $_POST["service_id"]; } else { $service_id = null; }
            if (isset($_POST["period_id"]) && $_POST["period_id"] <> "") { $period_id = $_POST["period_id"]; } else { $period_id = null; }

            if ($service_id != null && $period_id != null)
            {
                // verify the period exists
                $checkPeriod = mysqli_prepare($conn, "SELECT id FROM periods WHERE id=?");
                mysqli_stmt_bind_param($checkPeriod, "i", $period_id);
                if (mysqli_stmt_execute($checkPeriod))
                {
                    $checkPeriodResult = mysqli_stmt_get_result($checkPeriod);
                    if (mysqli_num_rows($checkPeriodResult) > 0) // period exists; continue
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
                                $description = $serviceDetails["description"];
                                $export_label = $serviceDetails["export_label"];
                                $cost_type = $serviceDetails["cost_type"];
                                $unit_label = $serviceDetails["unit_label"];
                                $fund = $serviceDetails["fund_code"];
                                $src = $serviceDetails["object_code"];
                                $func = $serviceDetails["function_code"];
                                $proj = $serviceDetails["project_code"];
                                $round_costs = $serviceDetails["round_costs"];

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
                                    <!-- Edit Service Modal -->
                                    <div class="modal fade" tabindex="-1" role="dialog" id="editServiceModal" data-bs-backdrop="static" aria-labelledby="editServiceModalLabel" aria-hidden="true">
                                        <div class="modal-dialog modal-lg" role="document">
                                            <div class="modal-content">
                                                <div class="modal-header primary-modal-header">
                                                    <h5 class="modal-title primary-modal-title" id="editServiceModalLabel">Edit Service</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>

                                                <div class="modal-body">
                                                    <!-- Service Details -->
                                                    <fieldset class="form-group border p-1 mb-3">
                                                        <legend class="w-auto px-2 m-0 float-none fieldset-legend">Service Details</legend>

                                                        <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                            <!-- Service ID -->
                                                            <div class="form-group col-3">
                                                                <label for="edit-service_id"><span class="required-field">*</span> Service ID:</label>
                                                                <input type="text" class="form-control w-100" id="edit-service_id" name="edit-service_id" value="<?php echo $service_id; ?>" required>
                                                            </div>

                                                            <!-- Spacer -->
                                                            <div class="form-group col-1"></div>

                                                            <!-- Service Name -->
                                                            <div class="form-group col-7">
                                                                <label for="edit-service_name"><span class="required-field">*</span> Name:</label>
                                                                <input type="text" class="form-control w-100" id="edit-service_name" name="edit-service_name" value="<?php echo $service_name; ?>" required>
                                                            </div>
                                                        </div>

                                                        <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                            <!-- Description -->
                                                            <div class="form-group col-11">
                                                                <label for="edit-description">Description:</label>
                                                                <textarea class="form-control w-100" id="edit-description" name="edit-description" rows="2" required><?php echo $description; ?></textarea>
                                                            </div>
                                                        </div>

                                                        <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                            <!-- Unit Label -->
                                                            <div class="form-group col-5">
                                                                <label for="edit-unit_label"><span class="required-field">*</span> Unit Label:</label>
                                                                <input type="text" class="form-control w-100" id="edit-unit_label" name="edit-unit_label" value="<?php echo $unit_label; ?>" required>
                                                            </div>

                                                            <!-- Spacer -->
                                                            <div class="form-group col-1"></div>

                                                            <!-- Export Label -->
                                                            <div class="form-group col-5">
                                                                <label for="edit-export_label">Export Label:</label>
                                                                <input type="text" class="form-control w-100" id="edit-export_label" name="edit-export_label" value="<?php echo $export_label; ?>" required>
                                                            </div>
                                                        </div>

                                                        <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                            <!-- Fund Code -->
                                                            <div class="form-group col-2">
                                                                <label for="edit-fund_code"><span class="required-field">*</span> Fund Code:</label>
                                                                <input type="number" class="form-control w-100" id="edit-fund_code" name="edit-fund_code" value="<?php echo $fund; ?>" min="10" max="99" required>
                                                            </div>

                                                            <!-- Spacer -->
                                                            <div class="form-group col-1 p-1"></div>

                                                            <!-- Source Code -->
                                                            <div class="form-group col-2">
                                                                <label for="edit-object_code"><span class="required-field">*</span> Source Code:</label>
                                                                <input type="text" class="form-control w-100" id="edit-object_code" name="edit-object_code" value="<?php echo $src; ?>" required>
                                                            </div>

                                                            <!-- Spacer -->
                                                            <div class="form-group col-1 p-1"></div>

                                                            <!-- Function Code -->
                                                            <div class="form-group col-2">
                                                                <label for="edit-function_code"><span class="required-field">*</span> Function Code:</label>
                                                                <input type="text" class="form-control w-100" id="edit-function_code" name="edit-function_code" value="<?php echo $func; ?>" required>
                                                            </div>

                                                            <!-- Spacer -->
                                                            <div class="form-group col-1 p-1"></div>

                                                            <!-- Project Code -->
                                                            <div class="form-group col-2">
                                                                <label for="edit-project_code">Project Code:</label>
                                                                <select class="form-select w-100" id="edit-project_code" name="edit-project_code" required>
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
                                                            <p class="text-center fst-italic mb-2">
                                                                <span class="required-field">*</span> editing a service's cost will only edit the cost for the period selected.
                                                            </p>
                                                        </div>

                                                        <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                            <!-- Cost Type -->
                                                            <div class="form-group col-11">
                                                                <label for="edit-cost_type"><span class="required-field">*</span> Cost Type:</label>
                                                                <select class="form-select w-100" id="edit-cost_type" name="edit-cost_type" onclick="updateCostForm('edit');" required>
                                                                    <option value=0 <?php if ($cost_type == 0) { echo "selected"; } ?>>Fixed</option>
                                                                    <option value=1 <?php if ($cost_type == 1) { echo "selected"; } ?>>Variable</option>
                                                                    <option value=2 <?php if ($cost_type == 2) { echo "selected"; } ?>>Membership</option>
                                                                    <option value=3 <?php if ($cost_type == 3) { echo "selected"; } ?>>Custom Cost</option>
                                                                    <option value=4 <?php if ($cost_type == 4) { echo "selected"; } ?>>Rate</option>
                                                                    <option value=5 <?php if ($cost_type == 5) { echo "selected"; } ?>>Group Rates</option>
                                                                </select>
                                                            </div>
                                                        </div>

                                                        <!-- Fixed Cost -->
                                                        <div id="edit-fixed_cost-div" <?php if ($cost_type == 0) { ?> style="visibility: visible; display: block;" <?php } else { ?> style="visibility: hidden; display: none;" <?php } ?>>
                                                            <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                                <!-- Cost Type -->
                                                                <div class="form-group col-11">
                                                                    <label for="edit-fixed_cost"><span class="required-field">*</span> Cost:</label>
                                                                    <input type="number" min="0.00" step="0.01" class="form-control w-100" id="edit-fixed_cost" name="edit-fixed_cost" value="<?php echo $cost; ?>" required>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <!-- Variable Cost -->
                                                        <div id="edit-variable_cost-div" <?php if ($cost_type == 1) { ?> style="visibility: visible; display: block;" <?php } else { ?> style="visibility: hidden; display: none;" <?php } ?>>
                                                            <div class="row align-items-center my-2">
                                                                <h3 class="text-center">Variable Cost Grid</h3>

                                                                <input type="hidden" id="edit-numOfRanges" value="<?php echo count($var_costs); ?>" aria-hidden="true">

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
                                                                                        <td><input type="number" class="form-control" id="edit-variable_cost-order-<?php echo $order; ?>" value="<?php echo $order; ?>" disabled></td>
                                                                                        <td><input type="number" class="form-control" id="edit-variable_cost-min-<?php echo $order; ?>" min="0" step="1" value="<?php echo $var_costs[$order - 1]["min_quantity"]; ?>" <?php if ($order == 1) { ?> required <?php } ?>></td>
                                                                                        <td><input type="number" class="form-control" id="edit-variable_cost-max-<?php echo $order; ?>" min="0" step="1" value="<?php if ($var_costs[$order - 1]["max_quantity"] != -1) { echo $var_costs[$order - 1]["max_quantity"]; } else { echo ""; } ?>" <?php if ($order == 1) { ?> required <?php } ?>></td>
                                                                                        <td><input type="number" class="form-control" id="edit-variable_cost-cost-<?php echo $order; ?>" min="0.00" step="0.01" value="<?php echo $var_costs[$order - 1]["cost"]; ?>" <?php if ($order == 1) { ?> value="0.00" required <?php } ?>></td>
                                                                                    </tr>
                                                                                <?php }
                                                                            } ?>
                                                                        </tbody>
                                                                    </table>
                                                                </div>

                                                                <div class="row p-0 my-2">
                                                                    <div class="col-5"></div>
                                                                    <div class="col-2 text-center">
                                                                        <button class="btn btn-secondary" onclick="addRange('edit');"><i class="fa-solid fa-plus"></i></button>
                                                                        <button class="btn btn-secondary" onclick="removeRange('edit');" id="edit-variable_cost-removeRangeBtn" <?php if (count($var_costs) <= 1) { echo "disabled"; } ?>><i class="fa-solid fa-minus"></i></button>
                                                                    </div>
                                                                    <div class="col-5"></div>
                                                                </div> 
                                                            </div>
                                                        </div>
                                                        
                                                        <!-- Membership Cost -->
                                                        <div id="edit-membership-div" <?php if ($cost_type == 2) { ?> style="visibility: visible; display: block;" <?php } else { ?> style="visibility: hidden; display: none;" <?php } ?>>
                                                            <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                                <!-- Combined Members Cost -->
                                                                <div class="form-group col-11">
                                                                    <label for="edit-membership_total_cost"><span class="required-field">*</span> Total Combined Cost:</label>
                                                                    <input type="number" min="0.00" step="0.01" class="form-control w-100" id="edit-membership_total_cost" name="edit-membership_total_cost" value="<?php if (isset($total_membership_cost)) { echo $total_membership_cost; } ?>" required>
                                                                </div>
                                                            </div>

                                                            <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                                <!-- Membership Group -->
                                                                <div class="form-group col-11">
                                                                    <label for="edit-membership_group"><span class="required-field">*</span> Membership Group:</label>
                                                                    <select class="form-select w-100" id="edit-membership_group" name="edit-membership_group" required>
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

                                                                <input type="hidden" id="edit-rates_cost-numOfRanges" value="<?php echo count($rates); ?>" aria-hidden="true">

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
                                                                                        <td><input type="number" class="form-control" id="edit-rates_cost-order-<?php echo $tier; ?>" value="<?php echo $tier; ?>" disabled></td>
                                                                                        <td><input type="number" class="form-control" id="edit-rates_cost-cost-<?php echo $tier; ?>" min="0.00" step="0.01" value="<?php echo $rates[$tier - 1]["cost"]; ?>" <?php if ($tier == 1) { ?> value="0.00" required <?php } ?>></td>
                                                                                    </tr>
                                                                                <?php }
                                                                            } ?>
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
                                                                        <button class="btn btn-secondary" onclick="addRatesRange('edit');"><i class="fa-solid fa-plus"></i></button>
                                                                        <button class="btn btn-secondary" onclick="removeRatesRange('edit');" id="edit-rates_cost-removeRangeBtn"  <?php if (count($rates) <= 1) { echo "disabled"; } ?>><i class="fa-solid fa-minus"></i></button>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <!-- Group Rates Cost -->
                                                        <div id="edit-group_rates-div" <?php if ($cost_type == 5) { ?> style="visibility: visible; display: block;" <?php } else { ?> style="visibility: hidden; display: none;" <?php } ?>>
                                                            <div class="row align-items-center my-2">
                                                                <h3 class="text-center">Group Rates</h3>

                                                                <input type="hidden" id="edit-group_rates-numOfRanges" value="<?php echo count($inside_costs); ?>" aria-hidden="true">

                                                                <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                                    <!-- Rate Group -->
                                                                    <div class="form-group col-11">
                                                                        <label for="edit-rate_group"><span class="required-field">*</span> Rate Group:</label>
                                                                        <select class="form-select w-100" id="edit-rate_group" name="edit-rate_group" required>
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
                                                                                        <td><input type="number" class="form-control" id="edit-group_rates-order-<?php echo $tier; ?>" value="<?php echo $tier; ?>" disabled></td>
                                                                                        <td><input type="number" class="form-control" id="edit-group_rates-inside-cost-<?php echo $tier; ?>" min="0.00" step="0.01" value="<?php echo $inside_costs[$tier - 1]["cost"]; ?>" <?php if ($tier == 1) { ?> value="0.00" required <?php } ?>></td>
                                                                                        <td><input type="number" class="form-control" id="edit-group_rates-outside-cost-<?php echo $tier; ?>" min="0.00" step="0.01" value="<?php echo $outside_costs[$tier - 1]["cost"]; ?>" <?php if ($tier == 1) { ?> value="0.00" required <?php } ?>></td>
                                                                                    </tr>
                                                                                <?php }
                                                                            } ?>
                                                                        </tbody>
                                                                    </table>
                                                                </div>

                                                                <div class="row d-flex justify-content-center my-2">
                                                                    <div class="d-inline text-center fst-italic">
                                                                        <span class="p-0 m-0" style="color: red;">*</span> rate tier does matter
                                                                    </div>
                                                                </div>

                                                                <div class="row d-flex justify-content-center mb-0 mt-3">
                                                                    <div class="col-2 text-center">
                                                                        <button class="btn btn-secondary" onclick="addGroupRatesRange('edit');"><i class="fa-solid fa-plus"></i></button>
                                                                        <button class="btn btn-secondary" onclick="removeGroupRatesRange('edit');" id="edit-group_rates-removeRangeBtn"  <?php if (count($rates) <= 1) { echo "disabled"; } ?>><i class="fa-solid fa-minus"></i></button>
                                                                    </div>
                                                                </div> 
                                                            </div>
                                                        </div>

                                                        <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                            <!-- Round Costs -->
                                                            <div class="form-group col-11">
                                                                <label for="edit-round"><span class="required-field">*</span> Round Costs:</label>
                                                                <?php if ($round_costs == 1) { ?>
                                                                    <button class="btn btn-success w-100" id="edit-round" value=1 onclick="updateRoundCosts('edit-round');">Yes</button>
                                                                <?php } else { ?>
                                                                    <button class="btn btn-danger w-100" id="edit-round" value=0 onclick="updateRoundCosts('edit-round');">No</button>
                                                                <?php } ?>
                                                            </div>
                                                        </div>
                                                    </fieldset>
                                                </div>

                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-primary" onclick="editService('<?php echo $service_id; ?>', <?php echo $period_id; ?>);"><i class="fa-solid fa-floppy-disk"></i> Save Service</button>
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> Close</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- End Add Service Modal -->
                                <?php
                            }
                        }
                    }
                }
            }
        }

        // disconnect from the database
        mysqli_close($conn);
    }
?>