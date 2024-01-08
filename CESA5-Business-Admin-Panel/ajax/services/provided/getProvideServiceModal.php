<?php
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get additional required files
        include("../../../includes/config.php");
        include("../../../includes/functions.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "ADD_INVOICES"))
        {
            // get parameters from POST if provided
            if (isset($_POST["code"]) && $_POST["code"] <> "") { $code = $_POST["code"]; } else { $code = null; }

            ?>
                <!-- Provide Service Modal -->
                <div class="modal fade" tabindex="-1" role="dialog" id="provideServiceModal" aria-labelledby="provideServiceModalLabel" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header primary-modal-header">
                                <h5 class="modal-title primary-modal-title" id="provideServiceModalLabel">Provide A Service</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>

                            <div class="modal-body">
                                <div class="form-row d-flex justify-content-center align-items-center my-3">
                                    <!-- Service -->
                                    <div class="form-group col-11">
                                        <label for="provide-service"><span class="required-field">*</span> Service:</label>
                                        <select class="form-select w-100" id="provide-service" name="provide-service" onchange="checkCostType('provide'); updateCost('provide-quantity', 'provide-annual_cost', 'provide');" required>
                                            <option></option>
                                            <?php
                                                if (isset($code) && $code != null)
                                                {
                                                    // get a list of all services connected to this project
                                                    $getServices = mysqli_prepare($conn, "SELECT id, name FROM services WHERE project_code=?");
                                                    mysqli_stmt_bind_param($getServices, "s", $code);
                                                    if (mysqli_stmt_execute($getServices))
                                                    {
                                                        $getServicesResults = mysqli_stmt_get_result($getServices);
                                                        if (mysqli_num_rows($getServicesResults) > 0) // services are assigned to this project
                                                        {
                                                            // for each service, create a dropdown option
                                                            while ($service = mysqli_fetch_array($getServicesResults))
                                                            {
                                                                echo "<option value='".$service["id"]."'>".$service["name"]."</option>";
                                                            }
                                                        }
                                                    }
                                                }
                                                else
                                                {
                                                    // create the service selection dropdown depending on the user's role
                                                    if ($_SESSION["role"] == 1 || $_SESSION["role"] == 4) // user is an admin or maintenance account; show all active services
                                                    {
                                                        $getServices = mysqli_query($conn, "SELECT id, name FROM services WHERE active=1 ORDER BY id ASC");
                                                        if (mysqli_num_rows($getServices) > 0) // services found
                                                        {
                                                            while ($service = mysqli_fetch_array($getServices)) 
                                                            { 
                                                                echo "<option value='".$service["id"]."'>".$service["name"]."</option>"; 
                                                            } 
                                                        }
                                                    }
                                                    else if ($_SESSION["role"] == 2) // user is a director; show only services assigned to their projects
                                                    {
                                                        $getServices = mysqli_prepare($conn, "SELECT s.id, s.name FROM services s JOIN projects p ON s.project_code=p.code JOIN departments d ON p.department_id=d.id WHERE d.director_id=? AND s.active=1 ORDER BY s.id ASC");
                                                        mysqli_stmt_bind_param($getServices, "i", $_SESSION["id"]);
                                                        if (mysqli_stmt_execute($getServices))
                                                        {
                                                            $results = mysqli_stmt_get_result($getServices);
                                                            if (mysqli_num_rows($results) > 0) // services found
                                                            {
                                                                while ($service = mysqli_fetch_array($results)) 
                                                                { 
                                                                    echo "<option value='".$service["id"]."'>".$service["name"]."</option>"; 
                                                                }
                                                            }
                                                        }
                                                    }
                                                }
                                            ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="form-row d-flex justify-content-center align-items-center my-3">
                                    <!-- Customer -->
                                    <div class="form-group col-11">
                                        <label for="provide-customer"><span class="required-field">*</span> Customer:</label>
                                        <select class="form-select w-100" id="provide-customer" name="provide-customer" onchange="checkCostType('provide'); updateCost('provide-quantity', 'provide-annual_cost', 'provide');" required>
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
                                </div>

                                <div class="form-row d-flex justify-content-center align-items-center my-3">
                                    <!-- Date Provided -->
                                    <div class="form-group col-11">
                                        <label for="provide-date"><span class="required-field">*</span> Date Provided:</label>
                                        <input type="text" class="form-control w-100" id="provide-date" name="provide-date" value="<?php echo date("m/d/Y"); ?>" required>
                                    </div>
                                </div>

                                <div class="form-row d-flex justify-content-center align-items-center my-3" id="provide-quantity-div">
                                    <!-- Quantity -->
                                    <div class="form-group col-11">
                                        <label for="provide-quantity"><span class="required-field">*</span> Quantity:</label>
                                        <input type="number" class="form-control w-100" id="provide-quantity" name="provide-quantity" onchange="updateCost('provide-quantity', 'provide-annual_cost', 'provide');" required>
                                    </div>
                                </div>

                                <div class="form-row d-none justify-content-center align-items-center my-3" id="provide-custom_cost-div">
                                    <!-- Custom Cost -->
                                    <div class="form-group col-11">
                                        <label for="provide-custom_cost"><span class="required-field">*</span> Cost:</label>
                                        <input type="number" class="form-control w-100" id="provide-custom_cost" name="provide-custom_cost" required>
                                    </div>
                                </div>

                                <div class="form-row d-none justify-content-center align-items-center my-3" id="provide-rate-div">
                                    <!-- Rate Tier -->
                                    <div class="form-group col-11">
                                        <label for="provide-rate"><span class="required-field">*</span> Rate Tier:</label>
                                        <div id="provide-rate-select-div">
                                            <select class="form-select w-100" id="provide-rate" name="provide-rate" required>
                                                <option></option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-row d-none justify-content-center align-items-center my-3" id="provide-group_rate-div">
                                    <!-- Group Rate -->
                                    <div class="form-group col-11">
                                        <label for="provide-group_rate"><span class="required-field">*</span> Group Rate Tier:</label>
                                        <div id="provide-group_rate-select-div">
                                            <select class="form-select w-100" id="provide-group_rate" name="provide-group_rate" required>
                                                <option></option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-row d-flex justify-content-center align-items-center my-3">
                                    <!-- Billing Notes -->
                                    <div class="form-group col-11">
                                        <label for="provide-description">Billing Notes:</label>
                                        <textarea class="form-control w-100" id="provide-description" name="provide-description" rows="3"></textarea>
                                    </div>
                                </div>

                                <div class="row justify-content-center align-items-center my-3" id="provide-preview_cost-div">
                                    <!-- Estimated Cost -->
                                    <div class="form-group col-11 d-inline text-center">
                                        <div class="preview_cost-label d-inline">Estimated Annual Cost: </div>
                                        <div class="preview_cost-cost d-inline" id="provide-annual_cost">$0.00</div>
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
                                <button type="button" class="btn btn-primary" onclick="provideService();"><i class="fa-solid fa-plus"></i> Provide Service</button>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> Close</button>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- End Provide Service Modal -->
            <?php
        }

        // disconnect from the database
        mysqli_close($conn);
    }
?>