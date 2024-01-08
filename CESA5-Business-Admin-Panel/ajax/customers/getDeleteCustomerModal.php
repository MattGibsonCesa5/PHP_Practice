<?php
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get additional required files
        include("../../includes/config.php");
        include("../../includes/functions.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "DELETE_CUSTOMERS"))
        {
            // get the customer ID from POST
            if (isset($_POST["customer_id"]) && $_POST["customer_id"] <> "") { $customer_id = $_POST["customer_id"]; } else { $customer_id = null; }

            if ($customer_id != null && is_numeric($customer_id))
            {
                ?>
                    <div class="modal fade" tabindex="-1" role="dialog" id="deleteCustomerModal" data-bs-backdrop="static" aria-labelledby="deleteCustomerModalLabel" aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header primary-modal-header">
                                    <h5 class="modal-title primary-modal-title" id="deleteCustomerModalLabel">Delete Customer</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>

                                <div class="modal-body">
                                    Are you sure you want to delete this customer? This will delete all data associated with the customer including both contacts and addresses.
                                </div>

                                <div class="modal-footer">
                                    <button type="button" class="btn btn-primary" onclick="deleteCustomer(<?php echo $customer_id; ?>);"><i class="fa-solid fa-trash-can"></i> Delete Customer</button>
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