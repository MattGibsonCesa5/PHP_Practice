<?php
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get additional required files
        include("../../includes/config.php");
        include("../../includes/functions.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        // verify the user has permission to manage therapists
        if (checkUserPermission($conn, "VIEW_EMPLOYEES_ALL") && checkUserPermission($conn, "EDIT_EMPLOYEES"))
        {
            // get the therapist ID from POST
            if (isset($_POST["therapist_id"]) && $_POST["therapist_id"] <> "") { $therapist_id = $_POST["therapist_id"]; } else { $therapist_id = null; }

            if (verifyTherapist($conn, $therapist_id))
            {
                // get the therapists name
                $therapist_name = getUserDisplayName($conn, $therapist_id);

                ?>
                    <div class="modal fade" tabindex="-1" role="dialog" id="removeTherapistModal" data-bs-backdrop="static" aria-labelledby="removeTherapistModalLabel" aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header primary-modal-header">
                                    <h5 class="modal-title primary-modal-title" id="removeTherapistModalLabel">Remove Therapist</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>

                                <div class="modal-body">
                                    <p class="m-0">
                                        Are you sure you want to remove <?php echo $therapist_name; ?> as a therapist? 
                                    </p>
                                </div>

                                <div class="modal-footer">
                                    <button type="button" class="btn btn-danger" onclick="removeTherapist(<?php echo $therapist_id; ?>);"><i class="fa-solid fa-trash-can"></i> Remove Therapist</button>
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