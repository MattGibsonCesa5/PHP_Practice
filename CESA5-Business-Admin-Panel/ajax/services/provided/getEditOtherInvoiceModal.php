<?php
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get additional required files
        include("../../../includes/config.php");
        include("../../../includes/functions.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (isset($_SESSION["role"]) && ($_SESSION["role"] == 1 || $_SESSION["role"] == 2 || $_SESSION["role"] == 4))
        {
            // get the service ID from POST
            if (isset($_POST["invoice_id"]) && $_POST["invoice_id"] <> "") { $invoice_id = $_POST["invoice_id"]; } else { $invoice_id = null; }

            if ($invoice_id != null && $invoice_id <> "")
            {
                // get the current invoice details to generate the modal
                $getInvoice = mysqli_prepare($conn, "SELECT * FROM services_other_provided WHERE id=?");
                mysqli_stmt_bind_param($getInvoice, "i", $invoice_id);
                if (mysqli_stmt_execute($getInvoice));
                {
                    $getInvoiceResult = mysqli_stmt_get_result($getInvoice);
                    if (mysqli_num_rows($getInvoiceResult) > 0) // invoice exists; continue
                    {
                        // store invoice details locally
                        $invoice = mysqli_fetch_array($getInvoiceResult);
                        $period_id = $invoice["period_id"];
                        $service_id = $invoice["service_id"];
                        $customer_id = $invoice["customer_id"];
                        $total_cost = $invoice["total_cost"];
                        $quantity = $invoice["quantity"];
                        $description = $invoice["description"];
                        $date = date("m/d/Y", strtotime($invoice["date_provided"]));
                        $unit = $invoice["unit_label"];
                        $project_code = $invoice["project_code"];

                        ?>
                            <!-- Provide Other Service Modal -->
                            <div class="modal fade" tabindex="-1" role="dialog" id="editOtherInvoiceModal" aria-labelledby="editOtherInvoiceModalLabel" aria-hidden="true">
                                <div class="modal-dialog" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header primary-modal-header">
                                            <h5 class="modal-title primary-modal-title" id="editOtherInvoiceModalLabel">Edit Invoice</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
            
                                        <div class="modal-body">
                                            <!-- Service Details -->
                                            <fieldset class="form-group border p-3 mb-3">
                                                <legend class="w-auto px-2 m-0 float-none fieldset-legend">Invoice Details</legend>
            
                                                <div class="row align-items-center my-2">
                                                    <div class="col-4 text-end"><label for="edit-invoice-service_id"><span class="required-field">*</span> Other Service:</label></div>
                                                    <div class="col-8">
                                                        <select class="form-select w-100" id="edit-invoice-service_id" name="edit-invoice-service_id" disabled readonly>
                                                            <?php 
                                                                $getOtherServices = mysqli_query($conn, "SELECT id, name FROM services_other WHERE active=1");
                                                                if (mysqli_num_rows($getOtherServices) > 0) // other services exist; continue
                                                                {
                                                                    while ($service = mysqli_fetch_array($getOtherServices))
                                                                    {
                                                                        $query_service_id = $service["id"];
                                                                        $query_service_name = $service["name"];
                                                                        if ($service_id == $query_service_id) { echo "<option value='".$query_service_id."' selected>".$query_service_name."</option>"; }
                                                                        else { echo "<option value='".$query_service_id."'>".$query_service_name."</option>"; }
                                                                    }
                                                                }
                                                            ?>
                                                        </select>
                                                    </div>
                                                </div>
                                                
                                                <div class="row align-items-center my-2">
                                                    <div class="col-4 text-end"><label for="edit-invoice-customer_id"><span class="required-field">*</span> Customer:</label></div>
                                                    <div class="col-8">
                                                        <select class="form-select w-100" id="edit-invoice-customer_id" name="edit-invoice-customer_id" disabled readonly>
                                                            <?php
                                                                $getCustomers = mysqli_query($conn, "SELECT id, name FROM customers WHERE active=1");
                                                                if (mysqli_num_rows($getCustomers) > 0) // customers exist; continue
                                                                {
                                                                    while ($customer = mysqli_fetch_array($getCustomers))
                                                                    {
                                                                        $query_customer_id = $customer["id"];
                                                                        $query_customer_name = $customer["name"];
                                                                        if ($customer_id == $query_customer_id) { echo "<option value='".$query_customer_id."' selected>".$query_customer_name."</option>"; }
                                                                        else { echo "<option value='".$query_customer_id."'>".$query_customer_name."</option>"; }
                                                                    }
                                                                }
                                                            ?>
                                                        </select>
                                                    </div>
                                                </div>
            
                                                <div class="row align-items-center my-2">
                                                    <div class="col-4 text-end"><label for="edit-invoice-project_code">Project Code:</label></div>
                                                    <div class="col-8">
                                                        <select class="form-select w-100" id="edit-invoice-project_code" name="edit-invoice-project_code" required>
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
                                                                        if ($project_code == $code) { echo "<option value=".$code." selected>".$code." - ".$name."</option>"; }
                                                                        else { echo "<option value=".$code.">".$code." - ".$name."</option>"; }
                                                                    }
                                                                }
                                                            ?>
                                                        </select>
                                                    </div>
                                                </div>
            
                                                <div class="row align-items-center my-2">
                                                    <div class="col-4 text-end"><label for="edit-invoice-cost"><span class="required-field">*</span> Total Cost:</label></div>
                                                    <div class="col-8"><input type="number" class="form-control w-100" id="edit-invoice-cost" name="edit-invoice-cost" value="<?php echo $total_cost; ?>"></div>
                                                </div>
            
                                                <div class="row align-items-center my-2">
                                                    <div class="col-4 text-end"><label for="edit-invoice-qty"><span class="required-field">*</span> Quantity:</label></div>
                                                    <div class="col-8"><input type="number" class="form-control w-100" id="edit-invoice-qty" name="edit-invoice-qty" value="<?php echo $quantity; ?>"></div>
                                                </div>
            
                                                <div class="row align-items-center my-2">
                                                    <div class="col-4 text-end"><label for="edit-invoice-unit"><span class="required-field">*</span> Unit Label:</label></div>
                                                    <div class="col-8"><input type="text" placeholder="unit" class="form-control w-100" id="edit-invoice-unit" name="edit-invoice-unit" value="<?php echo $unit; ?>"></div>
                                                </div>
            
                                                <div class="row align-items-center my-2">
                                                    <div class="col-4 text-end"><label for="edit-invoice-desc"><span class="required-field">*</span> Description:</label></div>
                                                    <div class="col-8"><input type="text" class="form-control w-100" id="edit-invoice-desc" name="edit-invoice-desc" value="<?php echo $description; ?>"></div>
                                                </div>
            
                                                <div class="row align-items-center my-2">
                                                    <div class="col-4 text-end"><label for="edit-invoice-date"><span class="required-field">*</span> Date Provided:</label></div>
                                                    <div class="col-8"><input type="text" class="form-control w-100" id="edit-invoice-date" name="edit-invoice-date" value="<?php echo $date; ?>"></div>
                                                </div>
                                            </fieldset>
                                        </div>
                                        
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-primary" onclick="editOtherInvoice(<?php echo $invoice_id; ?>);"><i class="fa-solid fa-floppy-disk"></i> Edit Invoice</button>
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> Close</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- End Provide Other Service Modal -->
                        <?php
                    }
                }
            }
        }

        // disconnect from the database
        mysqli_close($conn);
    }
?>