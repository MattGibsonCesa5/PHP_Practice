<?php
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get additional required files
        include("../../includes/config.php");
        include("../../includes/functions.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "EDIT_PROJECTS"))
        { 
            // get the project code and period name from POST
            if (isset($_POST["code"]) && $_POST["code"] <> "") { $code = $_POST["code"]; } else { $code = null; }
            if (isset($_POST["period"]) && $_POST["period"] <> "") { $period = $_POST["period"]; } else { $period = null; }

            if ($code != null && $code <> "")
            {
                if ($period != null && $period_id = getPeriodID($conn, $period))
                {
                    // get current project details
                    $getProjectDetails = mysqli_prepare($conn, "SELECT * FROM projects WHERE code=?");
                    mysqli_stmt_bind_param($getProjectDetails, "s", $code);
                    if (mysqli_stmt_execute($getProjectDetails))
                    {
                        $projectDetailsResults = mysqli_stmt_get_result($getProjectDetails);
                        if (mysqli_num_rows($projectDetailsResults) > 0)
                        {
                            $project = mysqli_fetch_array($projectDetailsResults);
                            $name = $project["name"];
                            $desc = $project["description"];
                            $department_id = $project["department_id"];
                            $supervision_costs = $project["supervision_costs"];
                            $indirect_costs = $project["indirect_costs"];
                            $fund = $project["fund_code"];
                            $func = $project["function_code"];
                            $calcFTE = $project["calc_fte"];
                            $FTE_days = $project["FTE_days"];
                            $leave_time = $project["leave_time"];
                            $prep_work = $project["prep_work"];
                            $personal_development = $project["personal_development"];
                            $staff_location = $project["staff_location"];

                            // get the project's status
                            $status = getProjectStatus($conn, $code, $period_id);

                            ?> 
                                <!-- Edit Project Modal -->
                                <div class="modal fade" tabindex="-1" role="dialog" id="editProjectModal" data-bs-backdrop="static" aria-labelledby="editProjectModalLabel" aria-hidden="true">
                                    <div class="modal-dialog" role="document">
                                        <div class="modal-content">
                                            <div class="modal-header primary-modal-header">
                                                <h5 class="modal-title primary-modal-title" id="editProjectModalLabel">Edit Project</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>

                                            <div class="modal-body px-4">
                                                <div class="form-row d-flex justify-content-center align-items-center mt-3 mb-0">
                                                    <!-- Project Code -->
                                                    <div class="form-group col-12">
                                                        <label for="edit-code"><span class="required-field">*</span> Project Code:</label>
                                                        <input type="number" class="form-control w-100" id="edit-code" name="edit-code" value="<?php echo $code; ?>" min="100" max="999" aria-describedby="projCodeHelpBlock" onchange="document.getElementById('projectCodeEditAlertBlock').classList.remove('d-none');" required>
                                                    </div>
                                                </div>
                                                <div id="projCodeHelpBlock" class="form-text p-0">
                                                    The fund code must follow the WUFAR convention. It must be a number between 100 and 999.
                                                </div>
                                                <div id="projectCodeEditAlertBlock" class="alert alert-danger d-none py-1">
                                                    <p class="m-0"><i class="fa-solid fa-triangle-exclamation"></i> Editing a project's code will adjust the project code in all budgets, services, and more!</p>
                                                </div>

                                                <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                    <!-- Project Name -->
                                                    <div class="form-group col-12">
                                                        <label for="edit-name"><span class="required-field">*</span> Name:</label>
                                                        <input type="text" class="form-control w-100" id="edit-name" name="edit-name" value="<?php echo $name; ?>" required>
                                                    </div>
                                                </div>

                                                <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                    <!-- Description -->
                                                    <div class="form-group col-12">
                                                        <label for="edit-desc">Description:</label>
                                                        <input type="text" class="form-control w-100" id="edit-desc" name="edit-desc" value="<?php echo $desc; ?>" required>
                                                    </div>
                                                </div>

                                                <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                    <!-- Department -->
                                                    <div class="form-group col-12">
                                                        <label for="edit-dept">Department:</label>
                                                        <select class="form-select w-100" id="edit-dept" name="edit-dept" required>
                                                            <option value=0></option>
                                                            <?php 
                                                                // create the dropdown options of departments
                                                                $getDepartments = mysqli_query($conn, "SELECT id, name FROM departments");
                                                                while ($dept = mysqli_fetch_array($getDepartments))
                                                                {
                                                                    if ($dept["name"] <> "") 
                                                                    { 
                                                                        if ($dept["id"] == $department_id) { echo "<option value=".$dept["id"]." selected>".$dept["name"]."</option>"; }
                                                                        else { echo "<option value=".$dept["id"].">".$dept["name"]."</option>"; }
                                                                    }
                                                                }
                                                            ?>
                                                        </select>
                                                    </div>
                                                </div>
                                                
                                                <div class="form-row d-flex justify-content-center align-items-center mt-3">
                                                    <!-- Fund Code -->
                                                    <div class="form-group col-12">
                                                        <label for="edit-fund"><span class="required-field">*</span> Fund Code:</label>
                                                        <input type="number" class="form-control w-100" id="edit-fund" name="edit-fund" value="<?php echo $fund; ?>" min="10" max="99" aria-describedby="fundCodeHelpBlock" required>
                                                    </div>
                                                </div>
                                                <div id="fundCodeHelpBlock" class="form-text p-0">
                                                    The fund code must follow the WUFAR convention. It must be a number between 10 and 99.
                                                </div>

                                                <div class="form-row d-flex justify-content-center align-items-center mt-3">
                                                    <!-- Function Code -->
                                                    <div class="form-group col-12">
                                                        <label for="edit-func"><span class="required-field">*</span> Function Code:</label>
                                                        <input type="number" class="form-control w-100" id="edit-func" name="edit-func" value="<?php echo $func; ?>" min="100000" max="999999" aria-describedby="funcCodeHelpBlock" required>
                                                    </div>
                                                </div>
                                                <div id="funcCodeHelpBlock" class="form-text p-0">
                                                    The function code must follow the WUFAR convention. It must be a number between 100000 and 999999.
                                                </div>

                                                <!-- Staff Locations -->
                                                <label class="mt-3 mb-0">Staff Location:</label>
                                                <div class="form-row mb-3">
                                                    <div class="btn-group w-100" role="group">
                                                        <button type="button" class="btn btn-sm btn-<?php if ($staff_location != 1 && $staff_location != 2) { echo "primary"; } else { echo "secondary"; } ?>" id="edit-location-none" value="<?php if ($staff_location != 1 && $staff_location != 2) { echo 1; } else { echo 0; } ?>" onclick="toggleLocation('edit', 'none');">None</button>
                                                        <button type="button" class="btn btn-sm btn-<?php if ($staff_location == 1) { echo "primary"; } else { echo "secondary"; } ?>" id="edit-location-customer" value="<?php if ($staff_location == 1) { echo 1; } else { echo 0; } ?>" onclick="toggleLocation('edit', 'customer');">Customer</button>
                                                        <button type="button" class="btn btn-sm btn-<?php if ($staff_location == 2) { echo "primary"; } else { echo "secondary"; } ?>" id="edit-location-classroom" value="<?php if ($staff_location == 2) { echo 1; } else { echo 0; } ?>" onclick="toggleLocation('edit', 'classroom');">Classroom</button>
                                                    </div>
                                                </div>

                                                <!-- Indirect Rate -->
                                                <label class="m-0">Indirect Rate:</label>
                                                <div class="form-row mb-3">
                                                    <div class="btn-group w-100" role="group">
                                                        <button type="button" class="btn btn-sm btn-<?php if ($indirect_costs != 1 && $indirect_costs != 2 && $indirect_costs != 3) { echo "primary"; } else { echo "secondary"; } ?>" id="edit-indirect-none" value="<?php if ($indirect_costs != 1 && $indirect_costs != 2 && $indirect_costs != 3) { echo 1; } else { echo 0; } ?>" onclick="toggleIndirect('edit', 'none');">None</button>
                                                        <button type="button" class="btn btn-sm btn-<?php if ($indirect_costs == 1) { echo "primary"; } else { echo "secondary"; } ?>" id="edit-indirect-agency" value="<?php if ($indirect_costs == 1) { echo 1; } else { echo 0; } ?>" onclick="toggleIndirect('edit', 'agency');">Agency Rate</button>
                                                        <button type="button" class="btn btn-sm btn-<?php if ($indirect_costs == 2) { echo "primary"; } else { echo "secondary"; } ?>" id="edit-indirect-grant" value="<?php if ($indirect_costs == 2) { echo 1; } else { echo 0; } ?>" onclick="toggleIndirect('edit', 'grant');">Grant Rate</button>
                                                        <button type="button" class="btn btn-sm btn-<?php if ($indirect_costs == 3) { echo "primary"; } else { echo "secondary"; } ?>" id="edit-indirect-dpi_grant" value="<?php if ($indirect_costs == 3) { echo 1; } else { echo 0; } ?>" onclick="toggleIndirect('edit', 'dpi_grant');">DPI Rate</button>
                                                    </div>
                                                </div>

                                                <div class="form-row my-3">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="edit-supervision" <?php if ($supervision_costs == 1) { echo "checked"; } ?>>
                                                        <label class="form-check-label" for="edit-supervision">Supervision Costs</label>
                                                    </div>

                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="edit-calc_fte" onchange="toggleCalcFTE(this.checked, 'edit');" <?php if ($calcFTE == 1) { echo "checked"; } ?>>
                                                        <label class="form-check-label" for="edit-calc_fte">Calculate Project FTE</label>
                                                    </div>
                                                </div>

                                                <!-- Calculate Project FTE -->
                                                <div class="<?php if ($calcFTE != 1) { echo "d-none"; } ?>" id="edit-calc_fte-div">
                                                    <!-- Project Costs By Days -->
                                                    <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                        <!-- FTE -->
                                                        <div class="form-group col-12">
                                                            <label for="edit-fte">FTE (Days):</label>
                                                            <input type="number" class="form-control w-100" id="edit-fte" name="edit-fte" value="<?php echo $FTE_days; ?>" min="0" max="365" required>
                                                        </div>
                                                    </div>
                                                    <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                        <!-- Leave Time -->
                                                        <div class="form-group col-12">
                                                            <label for="edit-leave_time">Leave Time (Days):</label>
                                                            <input type="number" class="form-control w-100" id="edit-leave_time" name="edit-leave_time" value="<?php echo $leave_time; ?>" min="0" max="365" required>
                                                        </div>
                                                    </div>
                                                    <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                        <!-- Prep Work -->
                                                        <div class="form-group col-12">
                                                            <label for="edit-prep_work">Prep Work (Days):</label>
                                                            <input type="number" class="form-control w-100" id="edit-prep_work" name="edit-prep_work" value="<?php echo $prep_work; ?>" min="0" max="365" required>
                                                        </div>
                                                    </div>
                                                    <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                        <!-- Personal Development -->
                                                        <div class="form-group col-12">
                                                            <label for="edit-personal_development">Personal Development (Days):</label>
                                                            <input type="number" class="form-control w-100" id="edit-personal_development" name="edit-personal_development" value="<?php echo $personal_development; ?>" min="0" max="365" required>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                    <!-- Status -->
                                                    <div class="form-group col-12">
                                                        <span class="required-field">*</span> Status:</label>
                                                        <?php if ($status == 1) { ?>
                                                            <button class="btn btn-success w-100" id="edit-status" value=1 onclick="updateStatus('edit-status');">Active</button>
                                                        <?php } else { ?>
                                                            <button class="btn btn-danger w-100" id="edit-status" value=0 onclick="updateStatus('edit-status');">Inactive</button>
                                                        <?php } ?>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-primary" onclick="editProject('<?php echo $code; ?>');"><i class="fa-solid fa-floppy-disk"></i> Save Changes</button>
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> Close</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- End Edit Project Modal -->
                            <?php
                        }
                    }
                }
            }
        }

        // disconect from the database
        mysqli_close($conn);
    }
?>