<?php
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get additional required files
        include("../../../includes/config.php");
        include("../../../includes/functions.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        // verify user permissions
        if (checkUserPermission($conn, "INVOICE_OTHER_SERVICES"))
        {
            // get parameters from POST
            if (isset($_POST["period"]) && $_POST["period"] <> "") { $period = $_POST["period"]; } else { $period = null; }
            if (isset($_POST["project"]) && $_POST["project"] <> "") { $project = $_POST["project"]; } else { $project = null; }

            // verify the period exists; if it exists, store the period ID
            if ($period != null && $period_id = getPeriodID($conn, $period)) 
            {
                ?>
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
                                            <div class="col-4 text-end"><label for="add-invoice_other-service_id"><span class="required-field">*</span> Other Service:</label></div>
                                            <div class="col-8">
                                                <select class="form-select w-100" id="add-invoice_other-service_id" name="add-invoice_other-service_id" required>
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
                                            <div class="col-4 text-end"><label for="add-invoice_other-customer_id"><span class="required-field">*</span> Customer:</label></div>
                                            <div class="col-8">
                                                <select class="form-select w-100" id="add-invoice_other-customer_id" name="add-invoice_other-customer_id">
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
                                            <div class="col-4 text-end"><label for="add-invoice_other-project_code">Project Code:</label></div>
                                            <div class="col-8">
                                                <select class="form-select w-100" id="add-invoice_other-project_code" name="add-invoice_other-project_code" required>
                                                    <option></option>
                                                    <?php
                                                        // create a dropdown of all active projects to assign to the service
                                                        $getProjects = mysqli_query($conn, "SELECT code, name FROM projects");
                                                        while ($projectDetails = mysqli_fetch_array($getProjects))
                                                        {
                                                            // store project details locally
                                                            $project_code = $projectDetails["code"];
                                                            $project_name = $projectDetails["name"];

                                                            // build project dropdown option
                                                            if ($project == null)
                                                            {
                                                                if ($project == $project_code) { echo "<option value=".$project_code." selected>".$project_code." - ".$project_name."</option>"; }
                                                                else { echo "<option value=".$project_code.">".$project_code." - ".$project_name."</option>"; }
                                                            }
                                                            else if ($project == $project_code) { echo "<option value=".$project_code." selected>".$project_code." - ".$project_name."</option>"; }
                                                        }
                                                    ?>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="row align-items-center my-2">
                                            <div class="col-4 text-end"><label for="add-invoice_other-cost"><span class="required-field">*</span> Total Cost:</label></div>
                                            <div class="col-8"><input type="number" value="0.00" class="form-control w-100" id="add-invoice_other-cost" name="add-invoice_other-cost"></div>
                                        </div>

                                        <div class="row align-items-center my-2">
                                            <div class="col-4 text-end"><label for="add-invoice_other-qty"><span class="required-field">*</span> Quantity:</label></div>
                                            <div class="col-8"><input type="number" value="0" class="form-control w-100" id="add-invoice_other-qty" name="add-invoice_other-qty"></div>
                                        </div>

                                        <div class="row align-items-center my-2">
                                            <div class="col-4 text-end"><label for="add-invoice_other-unit"><span class="required-field">*</span> Unit Label:</label></div>
                                            <div class="col-8"><input type="text" placeholder="unit" class="form-control w-100" id="add-invoice_other-unit" name="add-invoice_other-unit"></div>
                                        </div>

                                        <div class="row align-items-center my-2">
                                            <div class="col-4 text-end"><label for="add-invoice_other-desc"><span class="required-field">*</span> Description:</label></div>
                                            <div class="col-8"><input type="text" class="form-control w-100" id="add-invoice_other-desc" name="add-invoice_other-desc"></div>
                                        </div>

                                        <div class="row align-items-center my-2">
                                            <div class="col-4 text-end"><label for="add-invoice_other-date"><span class="required-field">*</span> Date Provided:</label></div>
                                            <div class="col-8"><input type="text" class="form-control w-100" id="add-invoice_other-date" name="add-invoice_other-date" value="<?php echo date("m/d/Y"); ?>"></div>
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
                <?php
            }
        }

        // disconnect from the database
        mysqli_close($conn);
    }
?>