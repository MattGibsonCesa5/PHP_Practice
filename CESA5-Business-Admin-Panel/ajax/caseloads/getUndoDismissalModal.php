<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get additional required files
        include("../../includes/functions.php");
        include("../../includes/config.php");
        include("../../getSettings.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "VIEW_CASELOADS_ALL") && checkUserPermission($conn, "VIEW_THERAPISTS"))
        {
            // get case and verify case
            if (isset($_POST["case_id"]) && $_POST["case_id"] <> "") { $case_id = $_POST["case_id"]; } else { $case_id = null; }
            if ($case_id != null && verifyCase($conn, $case_id))
            {
                ?>
                    <!-- Undo Dismissal Modal -->
                    <div class="modal fade" tabindex="-1" role="dialog" id="undoDismissalModal" data-bs-backdrop="static" aria-labelledby="undoDismissalModalLabel" aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header primary-modal-header">
                                    <h5 class="modal-title primary-modal-title" id="undoDismissalModalLabel">Undo Dismissal</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>

                                <div class="modal-body">
                                    <p>Are you sure you want to undo the case dismissal? We will set the end date of the case to the term end date, and set the student as active.
                                </div>

                                <div class="modal-footer">
                                    <button type="button" class="btn btn-primary" onclick="undoDismissal(<?php echo $case_id; ?>);"><i class="fa-solid fa-rotate-left"></i> Undo Dismissal</button>
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> Close</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- End Dismiss Student Modal -->
                <?php
            }
        }


        // disconnect from the database
        mysqli_close($conn);
    }
?>