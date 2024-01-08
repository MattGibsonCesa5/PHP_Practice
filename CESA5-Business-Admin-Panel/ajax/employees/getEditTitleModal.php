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

        if (checkUserPermission($conn, "VIEW_EMPLOYEES_ALL") && checkUserPermission($conn, "ADD_EMPLOYEES"))
        {
            // get the title ID from POST
            if (isset($_POST["title_id"]) && $_POST["title_id"] <> "") { $title_id = $_POST["title_id"]; } else { $title_id = null; }

            if (verifyTitle($conn, $title_id))
            {
                // get the title name
                $title = getTitleName($conn, $title_id);

                ?>
                    <!-- Edit Title Modal -->
                    <div class="modal fade" tabindex="-1" role="dialog" id="editTitleModal" data-bs-backdrop="static" aria-labelledby="editTitleModalLabel" aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header primary-modal-header">
                                    <h5 class="modal-title primary-modal-title" id="editTitleModalLabel">Edit Title</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>

                                <div class="modal-body">
                                    <div class="form-row d-flex justify-content-center align-items-center my-3">
                                        <!-- Title -->
                                        <div class="form-group col-11">
                                            <label for="edit-title"><span class="required-field">*</span> Title:</label>
                                            <input type="text" maxlength="128" class="form-control w-100" id="edit-title" name="edit-title" value="<?php echo $title; ?>" autocomplete="off" required>
                                        </div>
                                    </div>
                                </div>

                                <div class="modal-footer">
                                    <button type="button" class="btn btn-primary" onclick="editTitle(<?php echo $title_id; ?>);"><i class="fa-solid fa-plus"></i> Add Title</button>
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> Close</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- End Edit Title Modal -->
                <?php
            }
        }

        // disconnect from the database
        mysqli_close($conn);
    }
?>