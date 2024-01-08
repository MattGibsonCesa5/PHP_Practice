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
            if (isset($_POST["caseload_id"]) && $_POST["caseload_id"] <> "") { $caseload_id = $_POST["caseload_id"]; } else { $caseload_id = null; }

            // verify the caseload exists
            if (verifyCaseload($conn, $caseload_id))
            {
                if ($caseload_id <> "" && $caseload_id != null && $caseload_id != "undefined")
                {
                    ?>
                        <div class="modal fade" tabindex="-1" role="dialog" id="deleteCaseloadModal" data-bs-backdrop="static" aria-labelledby="deleteCaseloadModalLabel" aria-hidden="true">
                            <div class="modal-dialog" role="document">
                                <div class="modal-content">
                                    <div class="modal-header primary-modal-header">
                                        <h5 class="modal-title primary-modal-title" id="deleteCaseloadModalLabel">Delete Caseload</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>

                                    <div class="modal-body">
                                        <div class="alert alert-danger m-0">
                                            <p class="m-0">
                                                <i class="fa-solid fa-triangle-exclamation"></i> Are you sure you want to <b>permanently delete</b> this therapist's caseload? 
                                                All student data stored within this caseload, both <b>current</b> and <b>historical</b> data, will be lost. 
                                                This action <b>cannot be undone</b>.
                                            </p>
                                        </div>
                                    </div>

                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-danger" onclick="deleteCaseload(<?php echo $caseload_id; ?>);"><i class="fa-solid fa-trash-can"></i> Delete Caseload</button>
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> Close</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php
                }
            }
        }

        // disconnect from the database
        mysqli_close($conn);
    }
?>