<?php
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get additional required files
        include("../../includes/config.php");
        include("../../includes/functions.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "VIEW_CASELOADS_ALL") && checkUserPermission($conn, "VIEW_THERAPISTS"))
        {
            // get the coordinator ID from POST
            if (isset($_POST["coordinator_id"]) && $_POST["coordinator_id"] <> "") { $coordinator_id = $_POST["coordinator_id"]; } else { $coordinator_id = null; }

            if (verifyCoordinator($conn, $coordinator_id))
            {
                // get the coordinators name
                $coordinator_name = getUserDisplayName($conn, $coordinator_id);

                ?>
                    <div class="modal fade" tabindex="-1" role="dialog" id="removeCoordinatorModal" data-bs-backdrop="static" aria-labelledby="removeCoordinatorModalLabel" aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header primary-modal-header">
                                    <h5 class="modal-title primary-modal-title" id="removeCoordinatorModalLabel">Remove Coordinator</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>

                                <div class="modal-body">
                                    <p class="m-0">
                                        Are you sure you want to remove <?php echo $coordinator_name; ?> as a coordinator? 
                                        We will remove them as a coordinator, or secondary coordinator, from all departments they are assigned to.
                                    </p>
                                </div>

                                <div class="modal-footer">
                                    <button type="button" class="btn btn-danger" onclick="removeCoordinator(<?php echo $coordinator_id; ?>);"><i class="fa-solid fa-trash-can"></i> Remove Coordinator</button>
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