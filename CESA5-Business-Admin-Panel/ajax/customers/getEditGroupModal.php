<?php
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get additional required files
        include("../../includes/config.php");
        include("../../includes/functions.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "EDIT_CUSTOMER_GROUPS"))
        {
            // get group ID from POST
            if (isset($_POST["group_id"]) && $_POST["group_id"] <> "") { $group_id = $_POST["group_id"]; } else { $group_id = null; }

            if ($group_id != null && is_numeric($group_id))
            {
                // get group details
                $getGroupDetails = mysqli_prepare($conn, "SELECT id, name, description FROM `groups` WHERE id=?");
                mysqli_stmt_bind_param($getGroupDetails, "i", $group_id);
                if (mysqli_stmt_execute($getGroupDetails))
                {
                    $getGroupDetailsResults = mysqli_stmt_get_result($getGroupDetails);
                    if (mysqli_num_rows($getGroupDetailsResults) > 0) // group exists; build edit modal
                    {
                        $groupDetails = mysqli_fetch_array($getGroupDetailsResults);
                        $name = $groupDetails["name"];
                        $desc = $groupDetails["description"];

                        ?>
                            <div class="modal fade" tabindex="-1" role="dialog" id="editGroupModal" data-bs-backdrop="static" aria-labelledby="editGroupModalLabel" aria-hidden="true">
                                <div class="modal-dialog modal-lg" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header primary-modal-header">
                                            <h5 class="modal-title primary-modal-title" id="editGroupModalLabel">Edit Group</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
        
                                        <div class="modal-body">
                                            <!-- Group Details -->
                                            <fieldset class="form-group border p-3 mb-3">
                                                <legend class="w-auto px-2 m-0 float-none fieldset-legend">Group Details</legend>
        
                                                <div class="row align-items-center my-2">
                                                    <div class="col-3 text-end"><label for="edit-name"><span class="required-field">*</span> Group Name:</label></div>
                                                    <div class="col-9"><input type="text" class="form-control w-100" id="edit-name" name="edit-name" value="<?php echo $name; ?>" required></div>
                                                </div>
        
                                                <div class="row align-items-center my-2">
                                                    <div class="col-3 text-end"><label for="edit-desc">Description:</label></div>
                                                    <div class="col-9"><input type="text" class="form-control w-100" id="edit-desc" name="edit-desc" value="<?php echo $desc; ?>" required></div>
                                                </div>
                                            </fieldset>
        
                                            <!-- Group Members -->
                                            <fieldset class="form-group border p-3 mb-3">
                                                <legend class="w-auto px-2 m-0 float-none fieldset-legend">Group Members</legend>
        
                                                <table id="edit-group_members" class="report_table w-100">
                                                    <thead>
                                                        <tr>
                                                            <th class="text-center">Customer ID</th>
                                                            <th class="text-center">Customer Name</th>
                                                            <th class="text-center">Is Member?</th>
                                                        </tr>
                                                    </thead>
                                                </table>
                                            </fieldset>
                                        </div>
        
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-primary" onclick="editGroup(<?php echo $group_id; ?>);"><i class="fa-solid fa-floppy-disk"></i> Save Group</button>
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

        // disconnect from the database
        mysqli_close($conn);
    }
?>