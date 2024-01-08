<?php
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get additional required files
        include("../../../includes/config.php");
        include("../../../includes/functions.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "EDIT_OTHER_SERVICES"))
        {
            // get the service ID from POST
            if (isset($_POST["service_id"]) && $_POST["service_id"] <> "") { $service_id = $_POST["service_id"]; } else { $service_id = null; }

            if ($service_id != null)
            {
                // get the current service details based on service ID
                $getServiceDetails = mysqli_prepare($conn, "SELECT * FROM services_other WHERE id=?");
                mysqli_stmt_bind_param($getServiceDetails, "s", $service_id);
                if (mysqli_stmt_execute($getServiceDetails))
                {
                    $serviceResults = mysqli_stmt_get_result($getServiceDetails);
                    if (mysqli_num_rows($serviceResults) > 0) // service exists; continue
                    { 
                        $serviceDetails = mysqli_fetch_array($serviceResults);
                        $service_id = $serviceDetails["id"];
                        $service_name = $serviceDetails["name"];
                        $export_label = $serviceDetails["export_label"];
                        $fund_code = $serviceDetails["fund_code"];
                        $src_code = $serviceDetails["source_code"];
                        $func_code = $serviceDetails["function_code"];

                        ?>
                            <!-- Edit Service Modal -->
                            <div class="modal fade" tabindex="-1" role="dialog" id="editServiceModal" data-bs-backdrop="static" aria-labelledby="editServiceModalLabel" aria-hidden="true">
                                <div class="modal-dialog" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header primary-modal-header">
                                            <h5 class="modal-title primary-modal-title" id="editServiceModalLabel">Edit Service</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>

                                        <div class="modal-body">
                                            <!-- Service Details -->
                                            <fieldset class="form-group border p-3 mb-3">
                                                <legend class="w-auto px-2 m-0 float-none fieldset-legend">Service Details</legend>

                                                <div class="row align-items-center my-2">
                                                    <div class="col-4 text-end"><label for="edit-service_id"><span class="required-field">*</span> Service ID:</label></div>
                                                    <div class="col-8"><input type="text" class="form-control w-100" id="edit-service_id" name="edit-service_id" value="<?php echo $service_id; ?>" required></div>
                                                </div>

                                                <div class="row align-items-center my-2">
                                                    <div class="col-4 text-end"><label for="edit-service_name"><span class="required-field">*</span> Name:</label></div>
                                                    <div class="col-8"><input type="text" class="form-control w-100" id="edit-service_name" name="edit-service_name" value="<?php echo $service_name; ?>" required></div>
                                                </div>

                                                <div class="row align-items-center my-2">
                                                    <div class="col-4 text-end"><label for="edit-export_label">Export Label:</label></div>
                                                    <div class="col-8"><input type="text" class="form-control w-100" id="edit-export_label" name="edit-export_label" value="<?php echo $export_label; ?>"></div>
                                                </div>
                                            </fieldset>

                                            <!-- WUFAR Codes -->
                                            <fieldset class="form-group border p-3 mb-3">
                                                <legend class="w-auto px-2 m-0 float-none fieldset-legend">WUFAR Codes</legend>
                                                <div class="row align-items-center my-2">
                                                    <div class="col-4 text-end"><label for="edit-fund_code"><span class="required-field">*</span> Fund Code:</label></div>
                                                    <div class="col-8"><input type="number" class="form-control w-100" id="edit-fund_code" name="edit-fund_code" value="<?php echo $fund_code; ?>" min="10" max="99" required></div>
                                                </div>

                                                <div class="row align-items-center my-2">
                                                    <div class="col-4 text-end"><label for="edit-source_code"><span class="required-field">*</span> Source Code:</label></div>
                                                    <div class="col-8"><input type="text" class="form-control w-100" id="edit-source_code" name="edit-source_code" value="<?php echo $src_code; ?>" required></div>
                                                </div>

                                                <div class="row align-items-center my-2">
                                                    <div class="col-4 text-end"><label for="edit-function_code"><span class="required-field">*</span> Function Code:</label></div>
                                                    <div class="col-8"><input type="text" class="form-control w-100" id="edit-function_code" name="edit-function_code" value="<?php echo $func_code; ?>" required></div>
                                                </div>
                                            </fieldset>
                                        </div>

                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-primary" onclick="editOtherService('<?php echo $service_id; ?>');"><i class="fa-solid fa-floppy-disk"></i> Save Service</button>
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> Close</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- End Edit Service Modal -->
                        <?php
                    }
                }
            }
        }

        // disconnect from the database
        mysqli_close($conn);
    }
?>