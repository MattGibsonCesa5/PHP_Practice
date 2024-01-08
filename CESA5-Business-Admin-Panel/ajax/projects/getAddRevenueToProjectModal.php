<?php
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get the required files
        include("../../includes/config.php");
        include("../../includes/functions.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if ((checkUserPermission($conn, "BUDGET_PROJECTS_ALL") || checkUserPermission($conn, "BUDGET_PROJECTS_ASSIGNED")) && checkUserPermission($conn, "ADD_REVENUES"))
        {
            // get the POSTed project code
            if (isset($_POST["project_code"]) && $_POST["project_code"] <> "") { $project = trim($_POST["project_code"]); } else { $project = null; }

            if (verifyProject($conn, $project)) // verify the project exists
            {
                ?>
                    <div class="modal fade" tabindex="-1" role="dialog" id="addRevenueToProjectModal" data-bs-backdrop="static" aria-labelledby="addRevenueToProjectModalLabel" aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header primary-modal-header">
                                    <h5 class="modal-title primary-modal-title" id="addRevenueToProjectModalLabel">Add Revenue To Project</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>

                                <div class="modal-body">
                                    <!-- Revenue Details -->
                                    <fieldset class="form-group border p-3 mb-3">
                                        <legend class="w-auto px-2 m-0 float-none fieldset-legend">Revenue Details</legend>

                                        <div class="row align-items-center my-2">
                                            <div class="col-4 text-end"><label for="add-revenue_to_project-name"><span class="required-field">*</span> Revenue Name:</label></div>
                                            <div class="col-8"><input type="text" class="form-control w-100" id="add-revenue_to_project-name" name="add-revenue_to_project-name" required></div>
                                        </div>

                                        <div class="row align-items-center my-2">
                                            <div class="col-4 text-end"><label for="add-revenue_to_project-desc">Description:</label></div>
                                            <div class="col-8"><input type="text" class="form-control w-100" id="add-revenue_to_project-desc" name="add-revenue_to_project-desc"></div>
                                        </div>

                                        <div class="row align-items-center my-2">
                                            <div class="col-4 text-end"><span class="required-field">*</span> <label for="add-revenue_to_project-date">Date:</label></div>
                                            <div class="col-8"><input type="text" class="form-control w-100" id="add-revenue_to_project-date" name="add-revenue_to_project-date" value="<?php echo date("m/d/Y"); ?>"></div>
                                        </div>

                                        <div class="row align-items-center my-2">
                                            <div class="col-4 text-end"><label for="add-revenue_to_project-cost"><span class="required-field">*</span> Total Revenue:</label></div>
                                            <div class="col-8"><input type="number" class="form-control w-100" id="add-revenue_to_project-cost" name="add-revenue_to_project-cost" required></div>
                                        </div>

                                        <div class="row align-items-center my-2">
                                            <div class="col-4 text-end"><label for="add-revenue_to_project-qty"><span class="required-field">*</span> Quantity:</label></div>
                                            <div class="col-8"><input type="number" class="form-control w-100" id="add-revenue_to_project-qty" name="add-revenue_to_project-qty" required></div>
                                        </div>
                                    </fieldset>

                                    <!-- WUFAR Codes -->
                                    <fieldset class="form-group border p-3 mb-3">
                                        <legend class="w-auto px-2 m-0 float-none fieldset-legend">WUFAR Codes</legend>

                                        <div class="row align-items-center my-2">
                                            <div class="col-4 text-end"><label for="add-revenue_to_project-fund"><span class="required-field">*</span> Fund Code:</label></div>
                                            <div class="col-8"><input type="text" class="form-control w-100" id="add-revenue_to_project-fund" name="add-revenue_to_project-fund" required></div>
                                        </div>

                                        <div class="row align-items-center my-2">
                                            <div class="col-4 text-end"><label for="add-revenue_to_project-loc"><span class="required-field">*</span> Location Code:</label></div>
                                            <div class="col-8"><input type="text" class="form-control w-100" id="add-revenue_to_project-loc" name="add-revenue_to_project-loc" required></div>
                                        </div>

                                        <div class="row align-items-center my-2">
                                            <div class="col-4 text-end"><label for="add-revenue_to_project-src"><span class="required-field">*</span> Source Code:</label></div>
                                            <div class="col-8"><input type="text" class="form-control w-100" id="add-revenue_to_project-src" name="add-revenue_to_project-src" required></div>
                                        </div>

                                        <div class="row align-items-center my-2">
                                            <div class="col-4 text-end"><label for="add-revenue_to_project-func"><span class="required-field">*</span> Function Code:</label></div>
                                            <div class="col-8"><input type="text" class="form-control w-100" id="add-revenue_to_project-func" name="add-revenue_to_project-func" required></div>
                                        </div>

                                        <div class="row align-items-center my-2">
                                            <div class="col-4 text-end"><label for="add-revenue_to_project-proj"><span class="required-field">*</span> Project Code:</label></div>
                                            <div class="col-8"><input type="text" class="form-control w-100" id="add-revenue_to_project-proj" name="add-revenue_to_project-proj" value="<?php echo $project; ?>" required readonly disabled></div>
                                        </div>
                                    </fieldset>

                                    <!-- Required Field Indicator -->
                                    <div class="row justify-content-center">
                                        <div class="text-center fst-italic">
                                            <span class="required-field">*</span> indicates a required field
                                        </div>
                                    </div>
                                </div>

                                <div class="modal-footer">
                                    <button type="button" class="btn btn-primary" onclick="addRevenueToProject();"><i class="fa-solid fa-plus"></i> Add Revenue</button>
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> Close</button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php
            }

            // disconnect from the database
            mysqli_close($conn);
        }
    }
?>