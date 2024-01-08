<?php
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get additional required files
        include("../../../includes/config.php");
        include("../../../includes/functions.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "VIEW_INVOICES_ALL") || checkUserPermission($conn, "VIEW_INVOICES_ASSIGNED"))
        {
            // set the host timezone
            $DB_Timezone = HOST_TIMEZONE;

            // get the service ID from POST
            if (isset($_POST["invoice_id"]) && $_POST["invoice_id"] <> "") { $invoice_id = $_POST["invoice_id"]; } else { $invoice_id = null; }

            if ($invoice_id != null && $invoice_id <> "")
            {
                // get the current invoice details to generate the modal
                $getInvoice = mysqli_prepare($conn, "SELECT total_cost, quantity, description, date_provided, updated_time, updated_user FROM services_provided WHERE id=?");
                mysqli_stmt_bind_param($getInvoice, "i", $invoice_id);
                if (mysqli_stmt_execute($getInvoice));
                {
                    $getInvoiceResult = mysqli_stmt_get_result($getInvoice);
                    if (mysqli_num_rows($getInvoiceResult) > 0) // invoice exists; continue
                    {
                        // store invoice details locally
                        $invoice_details = mysqli_fetch_array($getInvoiceResult);
                        $total_cost = $invoice_details["total_cost"];
                        $quantity = $invoice_details["quantity"];
                        $description = $invoice_details["description"];
                        if (isset($invoice_details["date_provided"])) { $date = date("n/j/Y", strtotime($invoice_details["date_provided"])); } else { $date = "Unknown"; }
                        $update_time = date_convert(date("Y-m-d H:i:s", strtotime($invoice_details["updated_time"])), $DB_Timezone, "America/Chicago", "n/j/Y");
                        $update_user = getUpdateUser($conn, $invoice_details["updated_user"]);

                        // create the modal
                        ?>
                            <div class="modal fade" tabindex="-1" role="dialog" id="invoiceDetailsModal" aria-labelledby="invoiceDetailsModalLabel" aria-hidden="true">
                                <div class="modal-dialog" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header primary-modal-header">
                                            <h5 class="modal-title primary-modal-title" id="invoiceDetailsModalLabel">Invoice Details</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>

                                        <div class="modal-body">
                                            <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                <div class="form-group col-6">
                                                    <label for="cost">Invoice Cost</label>
                                                    <div class="input-group w-100 h-auto">
                                                        <div class="input-group-prepend">
                                                            <span class="input-group-text h-100" id="nav-search-icon"><i class="fa-solid fa-dollar-sign"></i></span>
                                                        </div>
                                                        <input type="text" class="form-control" id="cost" name="cost" value="<?php echo number_format($total_cost, 2); ?>" readonly>
                                                    </div>
                                                </div>

                                                <div class="form-group col-1"></div>

                                                <div class="form-group col-4">
                                                    <label for="qty">Quantity</label>
                                                    <input type="text" class="form-control" id="qty" name="qty" value="<?php echo $quantity; ?>" readonly>
                                                </div>
                                            </div>

                                            <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                <div class="form-group col-11">
                                                    <label for="desc">Billing Notes</label>
                                                    <textarea type="text" class="form-control" id="desc" name="desc" readonly><?php echo $description; ?></textarea>
                                                </div>
                                            </div>

                                            <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                <div class="form-group col-11">
                                                    <label for="date">Date Provided</label>
                                                    <div class="input-group w-100 h-auto">
                                                        <div class="input-group-prepend">
                                                            <span class="input-group-text h-100" id="nav-search-icon"><i class="fa-solid fa-calendar-days"></i></span>
                                                        </div>
                                                        <input type="text" class="form-control" id="date" name="date" value="<?php echo $date; ?>" readonly>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                <div class="form-group col-6">
                                                    <label for="user">Last Updated By</label>
                                                    <div class="input-group w-100 h-auto">
                                                        <div class="input-group-prepend">
                                                            <span class="input-group-text h-100" id="nav-search-icon"><i class="fa-solid fa-user"></i></span>
                                                        </div>
                                                        <input type="text" class="form-control" id="user" name="user" value="<?php echo $update_user; ?>" readonly>
                                                    </div>
                                                </div>

                                                <div class="form-group col-1"></div>

                                                <div class="form-group col-4">
                                                    <label for="updated">Last Updated At</label>
                                                    <div class="input-group w-100 h-auto">
                                                        <div class="input-group-prepend">
                                                            <span class="input-group-text h-100" id="nav-search-icon"><i class="fa-solid fa-clock"></i></span>
                                                        </div>
                                                        <input type="text" class="form-control" id="updated" name="updated" value="<?php echo $update_time; ?>" readonly>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="modal-footer">
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