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

        if (isset($PERMISSIONS["VIEW_EMPLOYEES_ALL"]) || checkUserPermission($conn, "VIEW_EMPLOYEES_ASSIGNED"))
        {
            // get the employee ID from POST
            if (isset($_POST["employee_id"]) && $_POST["employee_id"] <> "") { $employee_id = $_POST["employee_id"]; } else { $employee_id = null; }

            if (checkExistingEmployee($conn, $employee_id))
            {
                if (verifyUserEmployee($conn, $_SESSION["id"], $employee_id))
                {
                    // get the employee display name
                    $name = getEmployeeDisplayName($conn, $employee_id);

                    // get the employee's compensation and benefits
                    $salary = $daily_rate = $days = 0; // initialize compensation and benefits to 0
                    $getCompensation = mysqli_prepare($conn, "SELECT contract_days, yearly_rate FROM employee_compensation WHERE employee_id=? AND period_id=?");
                    mysqli_stmt_bind_param($getCompensation, "ii", $employee_id, $GLOBAL_SETTINGS["active_period"]);
                    if (mysqli_stmt_execute($getCompensation))
                    {
                        $getCompensationResults = mysqli_stmt_get_result($getCompensation);
                        if (mysqli_num_rows($getCompensationResults) > 0) // employee's compensation and benefits found
                        {
                            // store the employee's benefits and compensation locally
                            $employeeCompensation = mysqli_fetch_array($getCompensationResults);
                            $days = $employeeCompensation["contract_days"];
                            $salary = $employeeCompensation["yearly_rate"];

                            // calculate the employee's daily rate
                            if ($days > 0) { $daily_rate = round(($salary / $days), 2); }
                        }
                    }

                    ?>
                        <div class="modal fade" tabindex="-1" role="dialog" id="requestEmployeeChangeModal" data-bs-backdrop="static" aria-labelledby="requestEmployeeChangeModalLabel" aria-hidden="true">
                            <div class="modal-dialog modal-lg" role="document">
                                <div class="modal-content">
                                    <div class="modal-header primary-modal-header">
                                        <h5 class="modal-title primary-modal-title" id="requestEmployeeChangeModalLabel">Request Employee Change</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>

                                    <div class="modal-body">
                                        <!-- Employee Details -->
                                        <div class="form-row d-flex justify-content-center align-items-center my-3">
                                            <div class="form-group col-12 px-3">
                                                <label for="rc-employee">Employee:</label>
                                                <input class="form-control" id="rc-employee" name="rc-employee" value="<?php echo $name; ?>" disabled readonly>
                                            </div>
                                        </div>

                                        <!-- Period Selection -->
                                        <div class="form-row d-flex justify-content-center align-items-center mt-3">
                                            <div class="form-group col-12 px-3">
                                                <label for="rc-period"><span class="required-field">*</span> Fiscal Period:</label>
                                                <select class="form-select font-awesome" id="rc-period" name="rc-period" aria-describedby="periodHelp" required onchange="getEmployeeCompensation(<?php echo $employee_id; ?>);">
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

                                                                if ($period_active == 1) { echo "<option value='".$period["id"]."' selected>★ ".$period["name"]."</option>"; }
                                                                else { echo "<option value='".$period["id"]."'>".$period["name"]."</option>"; }
                                                            }
                                                        }
                                                    ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="form-row d-flex justify-content-center align-items-center mb-3">
                                            <p class="form-text m-0" id="periodHelp">★ indicates the current active period</p>
                                        </div>

                                        <!-- Contract Days -->
                                        <div class="form-row d-flex justify-content-center align-items-center my-3">
                                            <!-- Current Contract Days -->
                                            <div class="form-group col-6 px-3">
                                                <label for="rc-current_days">Current Contract Days:</label>
                                                <input type="number" class="form-control" id="rc-current_days" name="rc-current_days" value="<?php echo $days; ?>" readonly disabled>
                                            </div>

                                            <!-- New Contract Days -->
                                            <div class="form-group col-6 px-3">
                                                <label for="rc-new_days"><span class="required-field">*</span> New Contract Days:</label>
                                                <input type="number" class="form-control" id="rc-new_days" name="rc-new_days" min="0" max="365" value="0" onchange="estimateYearlySalary(<?php echo $employee_id; ?>);">
                                            </div>
                                        </div>

                                        <!-- Yearly Salary -->
                                        <div class="form-row d-flex justify-content-center align-items-center my-3">
                                            <!-- Current Yearly Salary -->
                                            <div class="form-group col-4 px-3">
                                                <label for="rc-current_salary">Current Yearly Salary:</label>
                                                <div class="input-group mb-3">
                                                    <span class="input-group-text"><i class="fa-solid fa-dollar-sign"></i></span>
                                                    <input type="text" class="form-control" id="rc-current_salary" name="rc-current_salary" value="<?php echo $salary; ?>" readonly disabled>
                                                </div>
                                            </div>

                                            <!-- Current Daily Rate -->
                                            <div class="form-group col-4 px-3">
                                                <label for="rc-daily_rate">Current Daily Rate:</label>
                                                <div class="input-group mb-3">
                                                    <span class="input-group-text"><i class="fa-solid fa-dollar-sign"></i></span>
                                                    <input type="text" class="form-control" id="rc-daily_rate" name="rc-daily_rate" value="<?php echo $daily_rate; ?>" readonly disabled>
                                                </div>
                                            </div>

                                            <!-- Estimated Yearly Salary -->
                                            <div class="form-group col-4 px-3">
                                                <label for="rc-estimated_salary"><span class="required-field">*</span> Estimated Yearly Salary:</label>
                                                <div class="input-group mb-3">
                                                    <span class="input-group-text"><i class="fa-solid fa-dollar-sign"></i></span>
                                                    <input type="text" class="form-control" id="rc-estimated_salary" name="rc-estimated_salary" value="0" readonly disabled>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Change Notes -->
                                        <div class="form-row d-flex justify-content-center align-items-center my-3">
                                            <div class="form-group col-12 px-3">
                                                <label for="rc-notes">Change Notes:</label>
                                                <textarea class="form-control" id="rc-notes" name="rc-notes" rows="5"></textarea>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-primary" onclick="requestEmployeeChange(<?php echo $employee_id; ?>);">Request Change</button>
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> Close</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php
                }
            }
        }
        
        // disconnect from the database
        mysqli_close($conn);
    }
?>