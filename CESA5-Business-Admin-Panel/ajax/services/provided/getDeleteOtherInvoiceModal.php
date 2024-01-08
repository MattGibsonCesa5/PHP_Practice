<?php
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get additional required files
        include("../../../includes/config.php");
        include("../../../includes/functions.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "INVOICE_OTHER_SERVICES"))
        {
            // get the invoice ID from POST
            if (isset($_POST["invoice_id"]) && $_POST["invoice_id"] <> "") { $invoice_id = $_POST["invoice_id"]; } else { $invoice_id = null; }

            if ($invoice_id != null && is_numeric($invoice_id))
            {
                // verify that the invoice exists
                $checkInvoice = mysqli_prepare($conn, "SELECT id FROM services_other_provided WHERE id=?");
                mysqli_stmt_bind_param($checkInvoice, "i", $invoice_id);
                if (mysqli_stmt_execute($checkInvoice))
                {
                    $checkInvoiceResult = mysqli_stmt_get_result($checkInvoice);
                    if (mysqli_num_rows($checkInvoiceResult) > 0) // invoice exists; continue
                    {
                        ?>
                            <div class="modal fade" tabindex="-1" role="dialog" id="deleteOtherInvoiceModal" data-bs-backdrop="static" aria-labelledby="deleteOtherInvoiceModalLabel" aria-hidden="true">
                                <div class="modal-dialog" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header primary-modal-header">
                                            <h5 class="modal-title primary-modal-title" id="deleteOtherInvoiceModalLabel">Delete Invoice</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>

                                        <div class="modal-body">
                                            Are you sure you want to delete this invoice? 
                                            This will delete all data associated with the invoice.
                                        </div>

                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-primary" onclick="deleteOtherInvoice('<?php echo $invoice_id; ?>');"><i class="fa-solid fa-trash-can"></i> Delete Invoice</button>
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