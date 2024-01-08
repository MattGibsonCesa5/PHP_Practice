<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get additional required files
        include("../../includes/functions.php");
        include("../../includes/config.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        // verify the user has permission
        if (checkUserPermission($conn, "VIEW_CASELOADS_ALL"))
        {
            // get the parameters from POST
            if (isset($_POST["category_id"]) && $_POST["category_id"] <> "") { $category_id = $_POST["category_id"]; } else { $category_id = null; }

            // verify category exists
            if (verifyCaseloadCategory($conn, $category_id))
            {
                // get current category data 
                $getCategoryData = mysqli_prepare($conn, "SELECT * FROM caseload_categories WHERE id=?");
                mysqli_stmt_bind_param($getCategoryData, "i", $category_id);
                if (mysqli_stmt_execute($getCategoryData))
                {
                    $getCategoryDataResult = mysqli_stmt_get_result($getCategoryData);
                    if (mysqli_num_rows($getCategoryDataResult) > 0)
                    {
                        // store category data locally
                        $category = mysqli_fetch_array($getCategoryDataResult);
                        $name = $category["name"];
                        $is_classroom = $category["is_classroom"];
                        $frequency_enabled = $category["frequency_enabled"];
                        $uos_enabled = $category["uos_enabled"];
                        $uos_required = $category["uos_required"];
                        $extra_ieps_enabled = $category["extra_ieps_enabled"];
                        $extra_evals_enabled = $category["extra_evals_enabled"];
                        $allow_assistants = $category["allow_assistants"];
                        $medicaid = $category["medicaid"];
                        $days = $category["days"];
                        $service_id = $category["service_id"];
                        $locked = $category["locked"];

                        ?>
                            <!-- Edit Category Modal -->
                            <div class="modal fade" tabindex="-1" role="dialog" id="editCategoryModal" data-bs-backdrop="static" aria-labelledby="editCategoryModalLabel" aria-hidden="true">
                                <div class="modal-dialog" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header primary-modal-header">
                                            <h5 class="modal-title primary-modal-title" id="editCategoryModalLabel">Edit Category</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>

                                        <div class="modal-body px-4 py-2">
                                            <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                <!-- Name -->
                                                <div class="form-group col-12">
                                                    <label for="edit-name"><span class="required-field">*</span> Name:</label>
                                                    <input type="text" class="form-control" id="edit-name" name="edit-name" value="<?php echo $name; ?>" required>
                                                </div>
                                            </div>

                                            <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                <!-- Locked -->
                                                <div class="form-group col-12">
                                                    <?php if ($locked == 1) { ?>
                                                        <button class="btn btn-danger w-100" id="edit-locked" value=1 onclick="toggleLocked('edit-locked');" aria-describedby="lockedHelpBlock"><i class="fa-solid fa-lock"></i> Locked</button>
                                                    <?php } else { ?>
                                                        <button class="btn btn-success w-100" id="edit-locked" value=0 onclick="toggleLocked('edit-locked');" aria-describedby="lockedHelpBlock"><i class="fa-solid fa-lock-open"></i> Unlocked</button>
                                                    <?php } ?>
                                                    <div id="lockedHelpBlock" class="form-text">
                                                        If a category is locked, users who can only view assigned caseloads will have view-only rights and not be able to make changes.
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-primary" onclick="editCategory(<?php echo $category_id; ?>);"><i class="fa-solid fa-floppy-disk"></i> Edit Category</button>
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> Close</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- End Edit Category Modal -->
                        <?php
                    }
                }
            }
        }

        // disconnect from the database
        mysqli_close($conn);
    }
?>