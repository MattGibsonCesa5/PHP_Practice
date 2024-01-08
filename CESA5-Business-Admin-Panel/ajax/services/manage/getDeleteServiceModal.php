<?php
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get additional required files
        include("../../../includes/config.php");
        include("../../../includes/functions.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "DELETE_SERVICES"))
        {
            // get the service ID from POST
            if (isset($_POST["service_id"]) && $_POST["service_id"] <> "") { $service_id = $_POST["service_id"]; } else { $service_id = null; }

            if ($service_id != null)
            {
                // verify the service exists
                $checkService = mysqli_prepare($conn, "SELECT id FROM services WHERE id=?");
                mysqli_stmt_bind_param($checkService, "s", $service_id);
                if (mysqli_stmt_execute($checkService))
                {
                    $serviceResults = mysqli_stmt_get_result($checkService);
                    if (mysqli_num_rows($serviceResults) > 0)
                    {
                        ?>
                            <div class="modal fade" tabindex="-1" role="dialog" id="deleteServiceModal" data-bs-backdrop="static" aria-labelledby="deleteServiceModalLabel" aria-hidden="true">
                                <div class="modal-dialog" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header primary-modal-header">
                                            <h5 class="modal-title primary-modal-title" id="deleteServiceModalLabel">Delete Service</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>

                                        <div class="modal-body">
                                            Are you sure you want to delete this service? 
                                            This will delete all data associated with the service, including any billing we have done for this service in this period.
                                        </div>

                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-primary" onclick="deleteService('<?php echo $service_id; ?>');"><i class="fa-solid fa-trash-can"></i> Delete Service</button>
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
    }
?>