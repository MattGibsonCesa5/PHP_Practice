<?php
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get additional required files
        include("../../includes/functions.php");
        include("../../includes/config.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "EDIT_CASELOADS") && checkUserPermission($conn, "ADD_THERAPISTS"))
        {
            // get the parameters from POST
            if (isset($_POST["caseload_id"]) && $_POST["caseload_id"] <> "") { $caseload_id = $_POST["caseload_id"]; } else { $caseload_id = null; }
            if (isset($_POST["period"]) && $_POST["period"] <> "") { $period = $_POST["period"]; } else { $period = null; }

            if ($caseload_id != null && verifyCaseload($conn, $caseload_id))
            {
                if ($period != null && $period_id = getPeriodID($conn, $period))
                {
                    $therapist_id = getCaseloadTherapist($conn, $caseload_id);
                    if ($therapist_id != null && $therapist_id != "") { $therapist_name = getUserDisplayName($conn, $therapist_id); } else { $therapist_name = ""; }
                    $category_id = getCaseloadCategory($conn, $caseload_id);
                    $subcategory_id = getCaseloadSubcategory($conn, $caseload_id);

                    // get the caseloads's status
                    $status = getCaseloadStatus($conn, $caseload_id, $period_id);

                    ?>
                        <!-- Edit Caseload Modal -->
                        <div class="modal fade" tabindex="-1" role="dialog" id="editCaseloadModal" data-bs-backdrop="static" aria-labelledby="editCaseloadModalLabel" aria-hidden="true">
                            <div class="modal-dialog" role="document">
                                <div class="modal-content">
                                    <div class="modal-header primary-modal-header">
                                        <h5 class="modal-title primary-modal-title" id="editCaseloadModalLabel">Edit Caseload</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>

                                    <div class="modal-body">
                                        <div class="form-row d-flex justify-content-center align-items-center my-3">
                                            <!-- Therapist -->
                                            <div class="form-group col-11">
                                                <label for="edit-therapist"><span class="required-field">*</span> Therapist:</label>
                                                <input type="text" class="form-control" id="edit-therapist" name="edit-therapist" value="<?php echo $therapist_name; ?>" readonly disabled>
                                            </div>
                                        </div>

                                        <div class="form-row d-flex justify-content-center align-items-center my-3">
                                            <!-- Category -->
                                            <div class="form-group col-11">
                                                <label for="edit-category"><span class="required-field">*</span> Category:</label>
                                                <select class="form-select" id="edit-category" name="edit-category" required onchange="categoryChanged(this.value, 'edit');">
                                                    <option></option>
                                                    <?php
                                                        $getCategories = mysqli_query($conn, "SELECT id, name FROM caseload_categories ORDER BY name ASC");
                                                        if (mysqli_num_rows($getCategories) > 0)
                                                        {
                                                            while ($category = mysqli_fetch_array($getCategories))
                                                            {
                                                                // store category details locally
                                                                $db_category_id = $category["id"];
                                                                $category_name = $category["name"];

                                                                // create selection option
                                                                if ($category_id == $db_category_id) { echo "<option value='".$db_category_id."' selected>".$category_name."</option>"; }
                                                                else { echo "<option value='".$db_category_id."'>".$category_name."</option>"; }
                                                            }
                                                        }
                                                    ?>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="form-row d-flex justify-content-center align-items-center my-3">
                                            <!-- Subcategory -->
                                            <div class="form-group col-11">
                                                <label for="edit-subcategory">Subcategory:</label>
                                                <select class="form-select" id="edit-subcategory" name="edit-subcategory">
                                                    <option></option>
                                                    <?php
                                                        // get a list of all subcategories for the category provided
                                                        $getSubcategories = mysqli_prepare($conn, "SELECT id, name FROM caseload_subcategories WHERE category_id=?");
                                                        mysqli_stmt_bind_param($getSubcategories, "i", $category_id);
                                                        if (mysqli_stmt_execute($getSubcategories))
                                                        {
                                                            $getSubcategoriesResults = mysqli_stmt_get_result($getSubcategories);
                                                            if (mysqli_num_rows($getSubcategoriesResults) > 0)
                                                            {
                                                                while ($subcategory = mysqli_fetch_array($getSubcategoriesResults))
                                                                {
                                                                    // store subcategory details locally
                                                                    $subcategory_name = $subcategory["name"];
                                                                    $db_subcategory_id = $subcategory["id"];

                                                                    // create the dropdown option
                                                                    if ($subcategory_id == $db_subcategory_id) { echo "<option value='".$db_subcategory_id."' selected>".$subcategory_name."</option>"; } 
                                                                    else { echo "<option value='".$db_subcategory_id."'>".$subcategory_name."</option>"; }
                                                                }
                                                            }
                                                        }
                                                    ?>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="form-row d-flex justify-content-center align-items-center my-3">
                                            <!-- Status -->
                                            <div class="form-group col-11">
                                                <span class="required-field">*</span> Status:</label>
                                                <?php if ($status == 1) { ?>
                                                    <button class="btn btn-success w-100" id="edit-status" value=1 onclick="updateStatus('edit-status');">Active</button>
                                                <?php } else { ?>
                                                    <button class="btn btn-danger w-100" id="edit-status" value=0 onclick="updateStatus('edit-status');">Inactive</button>
                                                <?php } ?>
                                            </div>
                                        </div>

                                        <!-- Required Field Indicator -->
                                        <div class="row justify-content-center">
                                            <div class="col-11 text-center fst-italic">
                                                <span class="required-field">*</span> indicates a required field
                                            </div>
                                        </div>
                                    </div>

                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-primary" onclick="editCaseload(<?php echo $caseload_id; ?>);"><i class="fa-solid fa-floppy-disk"></i> Edit Caseload</button>
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> Close</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- End Edit Caseload Modal -->
                    <?php
                }
            }
        }

        // disconnect from the database
        mysqli_close($conn);
    }
?>