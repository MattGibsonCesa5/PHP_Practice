<?php
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get additional required files
        include("../../includes/functions.php");
        include("../../includes/config.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "VIEW_CASELOADS_ALL") && checkUserPermission($conn, "VIEW_THERAPISTS"))
        {
            // get request ID from POST
            if (isset($_POST["request_id"]) && $_POST["request_id"] <> "") { $request_id = $_POST["request_id"]; } else { $request_id = null; }

            // verify the request ID exists
            $checkRequest = mysqli_prepare($conn, "SELECT id FROM caseload_transfers WHERE id=?");
            mysqli_stmt_bind_param($checkRequest, "i", $request_id);
            if (mysqli_stmt_execute($checkRequest))
            {
                $checkRequestResult = mysqli_stmt_get_result($checkRequest);
                if (mysqli_num_rows($checkRequestResult) > 0) // request exists; build modal
                {
                    ?>
                        <!-- Reject Transfer Caseload Modal -->
                        <div class="modal fade" tabindex="-1" role="dialog" id="rejectTransferCaseloadModal" data-bs-backdrop="static" aria-labelledby="rejectTransferCaseloadModalLabel" aria-hidden="true">
                            <div class="modal-dialog" role="document">
                                <div class="modal-content">
                                    <div class="modal-header primary-modal-header">
                                        <h5 class="modal-title primary-modal-title" id="rejectTransferCaseloadModalLabel">Reject Transfer Student</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>

                                    <div class="modal-body">
                                        <p class="m-0">Are you sure you want to reject this student transfer request?</p>
                                    </div>

                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-danger" onclick="rejectTransferCaseload(<?php echo $request_id; ?>);"><i class="fa-solid fa-xmark"></i> Reject</button>
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> Close</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- End Reject Transfer Caseload Modal -->
                    <?php                    
                }
            }
        }

        // disconnect from the database
        mysqli_close($conn);
    }
?>