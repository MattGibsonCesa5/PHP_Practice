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
                    <div class="modal fade" tabindex="-1" role="dialog" id="editCoordinatorModal" data-bs-backdrop="static" aria-labelledby="editCoordinatorModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-lg" role="document">
                            <div class="modal-content">
                                <div class="modal-header primary-modal-header">
                                    <h5 class="modal-title primary-modal-title" id="editCoordinatorModalLabel">Edit Coordinator (<?php echo $coordinator_name; ?>)</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>

                                <div class="modal-body">
                                    <table id="edit-coordinators-caseloads" class="report_table">
                                        <thead>
                                            <tr>
                                                <th></th>
                                                <th></th>
                                                <th style="font-size: 14px !important;">Caseload</th>
                                            </tr>
                                        </thead>
                                    </table>
                                </div>

                                <div class="modal-footer">
                                    <button type="button" class="btn btn-primary" onclick="editCoordinator(<?php echo $coordinator_id; ?>);"><i class="fa-solid fa-floppy-disk"></i> Edit Coordinator</button>
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