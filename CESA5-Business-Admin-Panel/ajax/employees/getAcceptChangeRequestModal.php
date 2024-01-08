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

        if (checkUserPermission($conn, "VIEW_EMPLOYEES_ALL") && checkUserPermission($conn, "EDIT_EMPLOYEES"))
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
                        $request_period = $request_details["period_id"];
                        $requester_id = $request_details["requested_by"];
                        $new_days = $request_details["new_contract_days"];
                        $reason = $request_details["reason"];

                        // get the employee's current salary and contract days
                        $current_salary = getEmployeeSalary($conn, $employee_id, $request_period);
                        $current_days = getEmployeeContractDays($conn, $employee_id, $request_period);

                        // calculate the current daily rate
                        $daily_rate = 0;
                        if ($current_days > 0) { $daily_rate = $current_salary / $current_days; }

                        // calculate the employee's new estimated yearly salary
                        $new_salary = 0;
                        $new_salary = round(($daily_rate * $new_days), 2);

                        // get the employee's display name
                        $employee_name = getEmployeeDisplayName($conn, $employee_id);
                        $requester_name = getUserDisplayName($conn, $requester_id);
                
                        ?>
                            <div class="modal fade" tabindex="-1" role="dialog" id="acceptChangeRequestModal" data-bs-backdrop="static" aria-labelledby="acceptChangeRequestModalLabel" aria-hidden="true">
                                <div class="modal-dialog" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header primary-modal-header">
                                            <h5 class="modal-title primary-modal-title" id="acceptChangeRequestModalLabel">Accept Change Request</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>

                                        <div class="modal-body">
                                            <p>
                                                Are you sure you want to reject the employee compensation change request for <?php echo $employee_name; ?>, 
                                                that was requested by <?php echo $requester_name; ?>? We will set the employee's compensation for the selected
                                                period to the values indicated below.
                                            </p>

                                            <!-- Employee Details -->
                                            <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                <div class="form-group col-11">
                                                    <label for="acr-employee">Employee:</label>
                                                    <input class="form-control" id="acr-employee" name="acr-employee" value="<?php echo $employee_name; ?>" disabled readonly>
                                                </div>
                                            </div>

                                            <!-- Period Selection -->
                                            <div class="form-row d-flex justify-content-center align-items-center mt-3">
                                                <div class="form-group col-11">
                                                    <label for="acr-period">Fiscal Period:</label>
                                                    <select class="form-control" id="acr-period" name="acr-period" aria-describedby="periodHelp" disabled readonly>
                                                        <?php
                                                            // create a list of all periods
                                                            $getPeriods = mysqli_query($conn, "SELECT id, name, active FROM periods ORDER BY active DESC, start_date ASC");
                                                            if (mysqli_num_rows($getPeriods) > 0) // periods found; continue
                                                            {
                                                                while ($period = mysqli_fetch_array($getPeriods))
                                                                {
                                                                    // store period details locally
                                                                    $period_id = $period["id"];
                                                                    $period_name = $period["name"];
                                                                    $period_active = $period["active"];

                                                                    if ($request_period == $period_id) { echo "<option value='".$period["id"]."' selected>".$period["name"]."</option>"; }
                                                                    else { echo "<option value='".$period["id"]."'>".$period["name"]."</option>"; }
                                                                }
                                                            }
                                                        ?>
                                                    </select>
                                                </div>
                                            </div>

                                            <!-- Contract Days -->
                                            <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                <!-- Current Contract Days -->
                                                <div class="form-group col-5">
                                                    <label for="acr-current_days">Current Contract Days:</label>
                                                    <input type="number" class="form-control" id="acr-current_days" name="acr-current_days" value="<?php echo $current_days; ?>" readonly disabled>
                                                </div>

                                                <!-- spacer -->
                                                <div class="form-group col-1"></div>

                                                <!-- New Contract Days -->
                                                <div class="form-group col-5">
                                                    <label for="acr-new_days"><span class="required-field">*</span> New Contract Days:</label>
                                                    <input type="number" class="form-control" id="acr-new_days" name="acr-new_days" min="0" max="365" value="<?php echo $new_days; ?>">
                                                </div>
                                            </div>

                                            <!-- Yearly Salary -->
                                            <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                <!-- Current Yearly Salary -->
                                                <div class="form-group col-5">
                                                    <label for="acr-current_salary">Current Yearly Salary:</label>
                                                    <input type="number" class="form-control" id="acr-current_salary" name="acr-current_salary" value="<?php echo $current_salary; ?>" readonly disabled>
                                                </div>

                                                <!-- spacer -->
                                                <div class="form-group col-1"></div>

                                                <!-- New Yearly Salary -->
                                                <div class="form-group col-5">
                                                    <label for="acr-new_salary"><span class="required-field">*</span> New Yearly Salary:</label>
                                                    <input type="number" class="form-control" id="acr-new_salary" name="acr-new_salary" value="<?php echo $new_salary; ?>">
                                                </div>
                                            </div>

                                            <!-- Change Notes -->
                                            <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                <div class="form-group col-11">
                                                    <label for="acr-notes">Change Notes:</label>
                                                    <textarea class="form-control" id="acr-notes" name="acr-notes" rows="5"><?php echo $reason; ?></textarea>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-success" onclick="acceptChangeRequest(<?php echo $request_id; ?>);"><i class="fa-solid fa-check"></i> Accept Request</button>
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