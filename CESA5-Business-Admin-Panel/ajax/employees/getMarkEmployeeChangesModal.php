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

        if (checkUserPermission($conn, "EDIT_EMPLOYEES"))
        {
            // get the employee ID from POST
            if (isset($_POST["employee_id"]) && $_POST["employee_id"] <> "") { $employee_id = $_POST["employee_id"]; } else { $employee_id = null; }

            if ($employee_id <> "" && $employee_id != null && $employee_id != "undefined")
            {
                // get the current employee details
                $getEmployeeInfo = mysqli_prepare($conn, "SELECT * FROM employees WHERE id=?");
                mysqli_stmt_bind_param($getEmployeeInfo, "i", $employee_id);
                if (mysqli_stmt_execute($getEmployeeInfo))
                {
                    $getEmployeeInfoResults = mysqli_stmt_get_result($getEmployeeInfo);
                    if (mysqli_num_rows($getEmployeeInfoResults) > 0) // employee exists; populate modal
                    {
                        $employee = mysqli_fetch_array($getEmployeeInfoResults);
                        
                        $fname = $employee["fname"];
                        $lname = $employee["lname"];

                        ?>
                            <div class="modal fade" tabindex="-1" role="dialog" id="markEmployeeChangesModal" data-bs-backdrop="static" aria-labelledby="markEmployeeChangesModalLabel" aria-hidden="true">
                                <div class="modal-dialog" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header primary-modal-header">
                                            <h5 class="modal-title primary-modal-title" id="markEmployeeChangesModalLabel">Mark Employee Changes</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>

                                        <div class="modal-body">
                                            <!-- Disclaimer -->
                                            <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                <div class="form-group col-11">
                                                    <div class="alert alert-warning text-center p-2" role="alert">
                                                        <p class="m-0">
                                                            <i class="fa-solid fa-triangle-exclamation"></i> Marking an employee's change <b>does not</b> actually change the employee's data.
                                                            It just allows a user to indicate when they've updated an employee from one period to another.
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Item Changed -->
                                            <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                <div class="form-group col-11">
                                                    <label for="ec-item_changed"><span class="required-field">*</span> Item Changed:</label>
                                                    <select class="form-select" type="text" id="ec-item_changed" name="ec-item_changed" required>
                                                        <option>Active Status</option>
                                                        <option>Address</option>
                                                        <option>Contract Days</option>
                                                        <option>Contract Type</option>
                                                        <option>Dental Insurance</option>
                                                        <option>DPI Assignment Area</option>
                                                        <option>DPI Assignment Position</option>
                                                        <option>Email Address</option>
                                                        <option>First Name</option>
                                                        <option>Gender</option>
                                                        <option>Health Insurance</option>
                                                        <option>Highest Degree Obtained</option>
                                                        <option>Last Name</option>
                                                        <option>Marital Status</option>
                                                        <option>Phone Number</option>
                                                        <option>Position Title</option>
                                                        <option>Primary Department</option>
                                                        <option>WRS Eligibility</option>
                                                        <option>Yearly Salary</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <!-- Period Selection -->
                                            <div class="form-row d-flex justify-content-center align-items-center mt-3">
                                                <!-- Base Period -->
                                                <div class="form-group col-5">
                                                    <label for="ec-from-period"><span class="required-field">*</span> Initial Period:</label>
                                                    <select class="form-select font-awesome" id="ec-from-period" name="ec-from-period" aria-describedby="periodHelp" required>
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

                                                <!-- Divider -->
                                                <div class="form-group col-1 p-0"></div>

                                                <!-- Raise Period -->
                                                <div class="form-group col-5">
                                                    <label for="ec-to-period"><span class="required-field">*</span> Change Period:</label>
                                                    <select class="form-select font-awesome" id="ec-to-period" name="ec-to-period" aria-describedby="periodHelp" required>
                                                        <option></option>
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

                                            <!-- Change Notes -->
                                            <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                <div class="form-group col-11">
                                                    <label for="ec-notes">Change Notes:</label>
                                                    <textarea class="form-control" id="ec-notes" name="ec-notes" rows="5"></textarea>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-primary" onclick="markChanges(<?php echo $employee_id; ?>);"><i class="fa-solid fa-check"></i> Mark Changes</button>
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