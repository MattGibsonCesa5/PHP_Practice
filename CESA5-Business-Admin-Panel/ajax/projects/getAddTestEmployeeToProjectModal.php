<?php
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get the required files
        include("../../includes/config.php");
        include("../../includes/functions.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "BUDGET_PROJECTS_ALL") || checkUserPermission($conn, "BUDGET_PROJECTS_ASSIGNED"))
        {
            ?>
                <div class="modal fade" tabindex="-1" role="dialog" id="addTestEmployeeToProjectModal" data-bs-backdrop="static" aria-labelledby="addTestEmployeeToProjectModalLabel" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header primary-modal-header">
                                <h5 class="modal-title primary-modal-title" id="addTestEmployeeToProjectModalLabel">Add Test Employee To Project</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>

                            <div class="modal-body">
                                <div class="row align-items-center mb-3">
                                    <p class="text-center m-0">
                                        Test employees will display in the project sheets; however, their totals will not be added to global total revenues and expenses unless you elect to include their costs.
                                    </p>
                                </div>

                                <div class="form-row d-flex justify-content-center align-items-center my-3">
                                    <!-- Test Employee Label -->
                                    <div class="form-group col-11">
                                        <label for="add-test_emp-label"><span class="required-field">*</span> Test Employee Label:</label>
                                        <input class="form-control" type="text" id="add-test_emp-label" name="add-test_emp-label">
                                    </div>
                                </div>

                                <div class="form-row d-flex justify-content-center align-items-center my-3">
                                    <!-- Yearly Rate -->
                                    <div class="form-group col-6">
                                        <label for="add-test_emp-rate"><span class="required-field">*</span> Yearly Rate:</label>
                                        <input class="form-control" type="number" id="add-test_emp-rate" name="add-test_emp-rate">
                                    </div>

                                    <!-- Spacer -->
                                    <div class="form-group col-1"></div>

                                    <!-- Project Days -->
                                    <div class="form-group col-4">
                                        <label for="add-test_emp-days"><span class="required-field">*</span> Days In Project:</label>
                                        <input class="form-control" type="number" id="add-test_emp-days" name="add-test_emp-days">
                                    </div>
                                </div>

                                <div class="form-row d-flex justify-content-center align-items-center my-3">
                                    <!-- Health Insurance -->
                                    <div class="form-group col-3">
                                        <label for="add-test_emp-health"><span class="required-field">*</span> Health Ins.</label>
                                        <select class="form-select w-100" id="add-test_emp-health" name="add-test_emp-health" required>
                                            <option value=0>None</option>
                                            <option value=2>Single</option>
                                            <option value=1 selected>Family</option>
                                        </select>
                                    </div>

                                    <!-- Spacer -->
                                    <div class="form-group col-1"></div>
                                    
                                    <!-- Dental Insurance -->
                                    <div class="form-group col-3">
                                        <label for="add-test_emp-dental"><span class="required-field">*</span> Dental Ins.</label>
                                        <select class="form-select w-100" id="add-test_emp-dental" name="add-test_emp-dental" required>
                                            <option value=0>None</option>
                                            <option value=2>Single</option>
                                            <option value=1 selected>Family</option>
                                        </select>
                                    </div>

                                    <!-- Spacer -->
                                    <div class="form-group col-1"></div>

                                    <!-- WRS Eligibility -->
                                    <div class="form-group col-3">
                                        <label for="add-test_emp-wrs"><span class="required-field">*</span> WRS Eligible</label>
                                        <select class="form-select w-100" id="add-test_emp-wrs" name="add-test_emp-wrs" required>
                                            <option value=0>No</option>
                                            <option value=1 selected>Yes</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="form-row d-flex justify-content-center align-items-center my-3">
                                    <!-- Cost Inclusion -->
                                    <div class="form-group col-11">
                                        <label for="add-test_emp-inclusion"><span class="required-field">*</span> Cost Inclusion:</label>
                                        <button class="btn btn-danger btn-sm w-100" id="add-test_emp-inclusion" value="0" onclick="toggleInclusion('add-test_emp-inclusion');">Don't Include</button>
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
                                <button type="button" class="btn btn-primary" onclick="addTestEmployeeToProject();"><i class="fa-solid fa-plus"></i> Add Test Employee</button>
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
?>