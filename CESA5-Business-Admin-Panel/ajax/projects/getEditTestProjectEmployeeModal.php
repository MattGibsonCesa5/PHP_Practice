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
            // get the employee ID from POST
            if (isset($_POST["code"]) && $_POST["code"] <> "") { $code = $_POST["code"]; } else { $code = null; }
            if (isset($_POST["id"]) && $_POST["id"] <> "") { $id = $_POST["id"]; } else { $id = null; }

            if ($code != null && $id != null)
            {
                // get employee project details
                $getTestEmployee = mysqli_prepare($conn, "SELECT * FROM project_employees_misc WHERE project_code=? AND id=? AND period_id=?");
                mysqli_stmt_bind_param($getTestEmployee, "sii", $code, $id, $GLOBAL_SETTINGS["active_period"]);
                if (mysqli_stmt_execute($getTestEmployee))
                {
                    $getTestEmployeeResults = mysqli_stmt_get_result($getTestEmployee);
                    if (mysqli_num_rows($getTestEmployeeResults) > 0) // employee is assigned to the project
                    {
                        // store test employees details locally
                        $employeeDetails = mysqli_fetch_array($getTestEmployeeResults);
                        $auto_id = $employeeDetails["id"];
                        $label = $employeeDetails["employee_label"];
                        $project_days = $employeeDetails["project_days"];
                        $yearly_rate = $employeeDetails["yearly_rate"];
                        $health = $employeeDetails["health_insurance"];
                        $dental = $employeeDetails["dental_insurance"];
                        $wrs = $employeeDetails["wrs_eligible"];
                        $inclusion = $employeeDetails["costs_inclusion"];
                        
                        ?>
                            <div class="modal fade" tabindex="-1" role="dialog" id="editTestProjectEmployeeModal" data-bs-backdrop="static" aria-labelledby="editTestProjectEmployeeModalLabel" aria-hidden="true">
                                <div class="modal-dialog" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header primary-modal-header">
                                            <h5 class="modal-title primary-modal-title" id="editTestProjectEmployeeModalLabel">Edit Test Employee In Project</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>

                                        <div class="modal-body">
                                            <div class="row align-items-center my-2">
                                                <p class="text-center">
                                                    Test employees will display in the project sheets; however, their totals will not be added to global total revenues and expenses unless you elect to include their costs.
                                                </p>
                                            </div>

                                            <div class="row align-items-center my-2">
                                                <div class="row d-flex align-items-center mb-2">
                                                    <div class="col-4 p-0 text-end"><span class="required-field">*</span> <label for="edit-test_emp-label">Label:</label></div>
                                                    <div class="col-8 pe-0"><input class="form-control" type="text" id="edit-test_emp-label" name="edit-test_emp-label" value="<?php echo $label; ?>"></div>
                                                </div>

                                                <div class="row d-flex align-items-center mb-2">
                                                    <div class="col-4 p-0 text-end"><span class="required-field">*</span> <label for="edit-test_emp-rate">Yearly Rate:</label></div>
                                                    <div class="col-8 pe-0"><input class="form-control" type="number" id="edit-test_emp-rate" name="edit-test_emp-rate" value="<?php echo $yearly_rate; ?>"></div>
                                                </div>

                                                <div class="row d-flex align-items-center mb-2">
                                                    <div class="col-4 p-0 text-end"><span class="required-field">*</span> <label for="edit-test_emp-days">Days In Project:</label></div>
                                                    <div class="col-8 pe-0"><input class="form-control" type="number" id="edit-test_emp-days" name="edit-test_emp-days" value="<?php echo $project_days; ?>"></div>
                                                </div>

                                                <div class="row d-flex align-items-center mb-2">
                                                    <div class="col-4 p-0 text-end"><span class="required-field">*</span> <label for="edit-test_emp-health">Health Coverage:</label></div>
                                                    <div class="col-8 pe-0">
                                                        <select class="form-select w-100" id="edit-test_emp-health" name="edit-test_emp-health" required>
                                                            <option value=0 <?php if ($health == 0 || ($health != 1 && $health != 2)) { echo "selected"; } ?>>None</option>
                                                            <option value=2 <?php if ($health == 2) { echo "selected"; } ?>>Single</option>
                                                            <option value=1 <?php if ($health == 1) { echo "selected"; } ?>>Family</option>
                                                        </select>
                                                    </div>
                                                </div>

                                                <div class="row d-flex align-items-center mb-2">
                                                    <div class="col-4 p-0 text-end"><span class="required-field">*</span> <label for="edit-test_emp-dental">Dental Coverage:</label></div>
                                                    <div class="col-8 pe-0">
                                                        <select class="form-select w-100" id="edit-test_emp-dental" name="edit-test_emp-dental" required>
                                                            <option value=0 <?php if ($dental == 0 || ($dental != 1 && $dental != 2)) { echo "selected"; } ?>>None</option>
                                                            <option value=2 <?php if ($dental == 2) { echo "selected"; } ?>>Single</option>
                                                            <option value=1 <?php if ($dental == 1) { echo "selected"; } ?>>Family</option>
                                                        </select>
                                                    </div>
                                                </div>

                                                <div class="row d-flex align-items-center mb-2">
                                                    <div class="col-4 p-0 text-end"><span class="required-field">*</span> <label for="edit-test_emp-wrs">WRS Eligible:</label></div>
                                                    <div class="col-8 pe-0">
                                                        <select class="form-select w-100" id="edit-test_emp-wrs" name="edit-test_emp-wrs" required>
                                                            <option value=0 <?php if ($wrs == 0 || $wrs != 1) { echo "selected"; } ?>>No</option>
                                                            <option value=1 <?php if ($wrs == 1) { echo "selected"; } ?>>Yes</option>
                                                        </select>
                                                    </div>
                                                </div>

                                                <div class="row d-flex align-items-center mb-2">
                                                    <div class="col-4 text-end"><span class="required-field">*</span> Cost Inclusion:</label></div>
                                                    <div class="col-8">
                                                    <?php if ($inclusion == 1) { ?>
                                                        <button class="btn btn-success btn-sm w-100" id="edit-test_emp-inclusion" value="1" onclick="toggleInclusion('edit-test_emp-inclusion');">Include</button>
                                                    <?php } else { ?>
                                                        <button class="btn btn-danger btn-sm w-100" id="edit-test_emp-inclusion" value="0" onclick="toggleInclusion('edit-test_emp-inclusion');">Don't Include</button>
                                                    <?php } ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-primary" onclick="editTestProjectEmployee(<?php echo $auto_id; ?>);">Edit Test Employee</button>
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