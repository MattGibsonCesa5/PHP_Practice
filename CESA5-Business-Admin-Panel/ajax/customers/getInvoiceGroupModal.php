<?php
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get additional required files and addiional settings
        include("../../includes/config.php");
        include("../../includes/functions.php");
        include("../../getSettings.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "VIEW_CUSTOMER_GROUPS") && checkUserPermission($conn, "ADD_INVOICES"))
        {
            // get the group ID from POST
            if (isset($_POST["group_id"]) && $_POST["group_id"] <> "") { $group_id = $_POST["group_id"]; } else { $group_id = null; }

            if ($group_id != null && is_numeric($group_id))
            {
                ?>
                    <div class="modal fade" tabindex="-1" role="dialog" id="invoiceGroupModal" data-bs-backdrop="static" aria-labelledby="invoiceGroupModalLabel" aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header primary-modal-header">
                                    <h5 class="modal-title primary-modal-title" id="invoiceGroupModalLabel">Invoice Group</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>

                                <div class="modal-body">
                                    <!-- Service Details -->
                                    <fieldset class="form-group border p-3 mb-3">
                                        <legend class="w-auto px-2 m-0 float-none fieldset-legend">Invoice Details</legend>
                                        <div class="row align-items-center my-2">
                                            <div class="col-3 text-end"><label for="bill-service"><span class="required-field">*</span> Service:</label></div>
                                            <div class="col-9">
                                                <select class="form-select w-100" id="bill-service" name="bill-service" onchange="updateMembershipCost();" required>
                                                    <option value="0"></option>
                                                    <?php
                                                        // get all services that are active and have the membership cost type
                                                        $getServices = mysqli_query($conn, "SELECT id, name FROM services WHERE cost_type=2 AND active=1");
                                                        if (mysqli_num_rows($getServices) > 0) // membership services exist
                                                        {
                                                            while ($service = mysqli_fetch_array($getServices))
                                                            {
                                                                echo "<option value='".$service["id"]."'>".$service["name"]."</option>";
                                                            }
                                                        }
                                                    ?>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="row align-items-center my-2">
                                            <div class="col-3 text-end"><label for="bill-date"><span class="required-field">*</span> Invoice Date:</label></div>
                                            <div class="col-9"><input type="text" class="form-control w-100" id="bill-date" name="bill-date" value="<?php echo date("m/d/Y"); ?>" required></div>
                                        </div>

                                        <div class="row align-items-center my-2">
                                            <div class="col-3 text-end"><label for="bill-desc">Description:</label></div>
                                            <div class="col-9"><input type="text" class="form-control w-100" id="bill-desc" name="bill-desc"></div>
                                        </div>

                                        <div class="row justify-content-center align-items-center my-2">
                                            <div class="col-8 preview_cost-label text-end">Combined Annual Cost:</div>
                                            <div class="col-4 preview_cost-cost text-start" id="bill-total_cost">$0.00</div>
                                        </div>
                                    </fieldset>
                                </div>

                                <div class="modal-footer">
                                    <button type="button" class="btn btn-primary" onclick="invoiceGroup(<?php echo $group_id; ?>);"><i class="fa-solid fa-trash-can"></i> Invoice Group</button>
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> Close</button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php
            }
        }

        // disconnect from the database
        mysqli_close($conn);
    }
?>