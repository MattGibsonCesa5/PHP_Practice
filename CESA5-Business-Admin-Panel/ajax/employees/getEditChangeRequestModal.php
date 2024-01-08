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

        if (checkUserPermission($conn, "VIEW_EMPLOYEES_ASSIGNED"))
        {
            // get the request ID from POST
            if (isset($_POST["request_id"]) && $_POST["request_id"] <> "") { $request_id = $_POST["request_id"]; } else { $request_id = null; }

            if ($request_id != null)
            {
                // get request details
                $getRequest = mysqli_prepare($conn, "SELECT * FROM employee_compensation_change_requests WHERE id=?");
                mysqli_stmt_bind_param($getRequest, "i", $request_id);
                if (mysqli_stmt_execute($getRequest))
                {
                    $getRequestResult = mysqli_stmt_get_result($getRequest);
                    if (mysqli_num_rows($getRequestResult) > 0) // request exists; continue
                    {
                        // store request details
                        $request_details = mysqli_fetch_array($getRequestResult);
                        $employee_id = $request_details["employee_id"];
                        $period_id = $request_details["period_id"];
                        $days = $request_details["current_contract_days"];
                        $new_days = $request_details["new_contract_days"];
                        $salary = $request_details["current_yearly_salary"];
                        $new_salary = $request_details["new_yearly_salary"];
                        $reason = $request_details["reason"];
                        
                        // calculate daily rate
                        if ($days > 0) { $daily_rate = round(($salary / $days), 2); } else {$daily_rate = 0; }

                        // get period name
                        $period_name = getPeriodName($conn, $period_id);

                        // get the employee's display name
                        $employee_name = getEmployeeDisplayName($conn, $employee_id);

                        ?>
                            <div class="modal fade" tabindex="-1" role="dialog" id="editChangeRequestModal" data-bs-backdrop="static" aria-labelledby="editChangeRequestModalLabel" aria-hidden="true">
                                <div class="modal-dialog modal-lg" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header primary-modal-header">
                                            <h5 class="modal-title primary-modal-title" id="editChangeRequestModalLabel">Edit Change Request</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>

                                        <div class="modal-body">
                                            <!-- Employee Details -->
                                            <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                <div class="form-group col-12 px-3">
                                                    <label for="ecr-employee">Employee:</label>
                                                    <input class="form-control" id="ecr-employee" name="ecr-employee" value="<?php echo $employee_name; ?>" disabled readonly>
                                                </div>
                                            </div>

                                            <!-- Period Selection -->
                                            <div class="form-row d-flex justify-content-center align-items-center mt-3">
                                                <div class="form-group col-12 px-3">
                                                    <label for="ecr-period"><span class="required-field">*</span> Fiscal Period:</label>
                                                    <input class="form-control" id="ecr-period" name="ecr-period" value="<?php echo $period_name; ?>" disabled readonly>
                                                    <input class="form-control" type="hidden" id="ecr-period_id" name="ecr-period_id" value="<?php echo $period_id; ?>" disabled readonly>
                                                </div>
                                            </div>

                                            <!-- Contract Days -->
                                            <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                <!-- Current Contract Days -->
                                                <div class="form-group col-6 px-3">
                                                    <label for="ecr-current_days">Current Contract Days:</label>
                                                    <input type="number" class="form-control" id="ecr-current_days" name="ecr-current_days" value="<?php echo $days; ?>" readonly disabled>
                                                </div>

                                                <!-- New Contract Days -->
                                                <div class="form-group col-6 px-3">
                                                    <label for="ecr-new_days"><span class="required-field">*</span> New Contract Days:</label>
                                                    <input type="number" class="form-control" id="ecr-new_days" name="ecr-new_days" min="0" max="365" value="<?php echo $new_days; ?>" onchange="estimateYearlySalary(<?php echo $employee_id; ?>);">
                                                </div>
                                            </div>

                                            <!-- Yearly Salary -->
                                            <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                <!-- Current Yearly Salary -->
                                                <div class="form-group col-4 px-3">
                                                    <label for="ecr-current_salary">Current Yearly Salary:</label>
                                                    <div class="input-group mb-3">
                                                        <span class="input-group-text"><i class="fa-solid fa-dollar-sign"></i></span>
                                                        <input type="text" class="form-control" id="ecr-current_salary" name="ecr-current_salary" value="<?php echo $salary; ?>" readonly disabled>
                                                    </div>
                                                </div>

                                                <!-- Current Daily Rate -->
                                                <div class="form-group col-4 px-3">
                                                    <label for="ecr-daily_rate">Current Daily Rate:</label>
                                                    <div class="input-group mb-3">
                                                        <span class="input-group-text"><i class="fa-solid fa-dollar-sign"></i></span>
                                                        <input type="text" class="form-control" id="ecr-daily_rate" name="ecr-daily_rate" value="<?php echo $daily_rate; ?>" readonly disabled>
                                                    </div>
                                                </div>

                                                <!-- Estimated Yearly Salary -->
                                                <div class="form-group col-4 px-3">
                                                    <label for="ecr-estimated_salary"><span class="required-field">*</span> Estimated Yearly Salary:</label>
                                                    <div class="input-group mb-3">
                                                        <span class="input-group-text"><i class="fa-solid fa-dollar-sign"></i></span>
                                                        <input type="text" class="form-control" id="ecr-estimated_salary" name="ecr-estimated_salary" value="<?php echo $new_salary; ?>" readonly disabled>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Change Notes -->
                                            <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                <div class="form-group col-12 px-3">
                                                    <label for="ecr-notes">Change Notes:</label>
                                                    <textarea class="form-control" id="ecr-notes" name="ecr-notes" rows="5"><?php echo $reason; ?></textarea>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-primary" onclick="editChangeRequest(<?php echo $request_id; ?>);"><i class="fa-solid fa-floppy-disk"></i> Save Request</button>
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