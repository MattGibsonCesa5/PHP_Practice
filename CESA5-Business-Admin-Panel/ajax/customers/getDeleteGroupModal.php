<?php
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get additional required files
        include("../../includes/config.php");
        include("../../includes/functions.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "DELETE_CUSTOMER_GROUPS"))
        {
            // get the group ID from POST
            if (isset($_POST["group_id"]) && $_POST["group_id"] <> "") { $group_id = $_POST["group_id"]; } else { $group_id = null; }

            if ($group_id != null && is_numeric($group_id))
            {
                ?>
                    <div class="modal fade" tabindex="-1" role="dialog" id="deleteGroupModal" data-bs-backdrop="static" aria-labelledby="deleteGroupModalLabel" aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header primary-modal-header">
                                    <h5 class="modal-title primary-modal-title" id="deleteGroupModalLabel">Delete Group</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>

                                <div class="modal-body">
                                    Are you sure you want to delete this group? This will delete all data associated with the group.
                                </div>

                                <div class="modal-footer">
                                    <button type="button" class="btn btn-primary" onclick="deleteGroup(<?php echo $group_id; ?>);"><i class="fa-solid fa-trash-can"></i> Delete Group</button>
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