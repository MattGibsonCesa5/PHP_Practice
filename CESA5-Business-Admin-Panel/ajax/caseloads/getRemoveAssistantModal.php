<?php
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get additional required files
        include("../../includes/functions.php");
        include("../../includes/config.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "REMOVE_THERAPISTS"))
        {
            // get the caseload ID from POST
            if (isset($_POST["assistant_id"]) && $_POST["assistant_id"] <> "") { $assistant_id = $_POST["assistant_id"]; } else { $assistant_id = null; }

            // verify the assistant exists
            if (verifyAssistant($conn, $assistant_id))
            {
                ?>
                    <div class="modal fade" tabindex="-1" role="dialog" id="removeAssistantModal" data-bs-backdrop="static" aria-labelledby="removeAssistantModalLabel" aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header primary-modal-header">
                                    <h5 class="modal-title primary-modal-title" id="removeAssistantModalLabel">Remove Assistant</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>

                                <div class="modal-body">
                                    <p>Are you sure you want to remove this assistant? All cases, both current and historical, that have this assistant assigned to them, will be set to have no assistant.</p>
                                </div>

                                <div class="modal-footer">
                                    <button type="button" class="btn btn-primary" onclick="removeAssistant(<?php echo $assistant_id; ?>);"><i class="fa-solid fa-trash-can"></i> Remove Assistant</button>
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