<?php
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get additional required files
        include("../../includes/config.php");
        include("../../includes/functions.php");
        include("../../getSettings.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "EDIT_EMPLOYEES"))
        {
            // get the employee ID from POST
            if (isset($_POST["change_id"]) && $_POST["change_id"] <> "") { $change_id = $_POST["change_id"]; } else { $change_id = null; }

            if ($change_id <> "" && $change_id != null && $change_id != "undefined")
            {
                ?>
                    <div class="modal fade" tabindex="-1" role="dialog" id="removeMarkedChangeModal" data-bs-backdrop="static" aria-labelledby="removeMarkedChangeModalLabel" aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header primary-modal-header">
                                    <h5 class="modal-title primary-modal-title" id="removeMarkedChangeModalLabel">Remove Marked Change</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>

                                <div class="modal-body">
                                    <p>Are you sure you want to delete this change mark? We will not revert the employee's change, we will just remove the change note.<p>
                                </div>

                                <div class="modal-footer">
                                    <button type="button" class="btn btn-primary" onclick="removeMarkedChange(<?php echo $change_id; ?>);"><i class="fa-solid fa-trash-can"></i> Remove Marked Change</button>
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