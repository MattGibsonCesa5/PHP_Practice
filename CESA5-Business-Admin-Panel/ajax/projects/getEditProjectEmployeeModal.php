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
            if (isset($_POST["period"]) && $_POST["period"] <> "") { $period = $_POST["period"]; } else { $period = null; }
            if (isset($_POST["record"]) && $_POST["record"] <> "") { $record = $_POST["record"]; } else { $record = null; }

            if ($period != null && $period_id = getPeriodID($conn, $period)) // verify the period exists; if it exists, store the period ID
            {
                if ($code != null && verifyProject($conn, $code)) // verify the project exists
                {
                    // get additional employee information based on the employee ID
                    $getEmployeeInfo = mysqli_prepare($conn, "SELECT e.lname, e.fname, ec.contract_days FROM employees e
                                                            JOIN employee_compensation ec ON e.id=ec.employee_id
                                                            WHERE e.id=?");
                    mysqli_stmt_bind_param($getEmployeeInfo, "i", $id);
                    if (mysqli_stmt_execute($getEmployeeInfo))
                    {
                        $getEmployeeInfoResults = mysqli_stmt_get_result($getEmployeeInfo);
                        if (mysqli_num_rows($getEmployeeInfoResults) > 0) // employee exists; create modal
                        {
                            $employeeInfo = mysqli_fetch_array($getEmployeeInfoResults);
                            $fname = $employeeInfo["fname"];
                            $lname = $employeeInfo["lname"];
                            $contract_days = $employeeInfo["contract_days"];
                            $displayName = $lname.", ".$fname." (".$contract_days.")";

                            // get employee project details
                            $getProjectDetails = mysqli_prepare($conn, "SELECT * FROM project_employees WHERE id=?");
                            mysqli_stmt_bind_param($getProjectDetails, "i", $record);
                            if (mysqli_stmt_execute($getProjectDetails))
                            {
                                $getProjectDetailsResults = mysqli_stmt_get_result($getProjectDetails);
                                if (mysqli_num_rows($getProjectDetailsResults) > 0) // employee is assigned to the project
                                {
                                    // store project employee details
                                    $projectDetails = mysqli_fetch_array($getProjectDetailsResults);
                                    $project_days = $projectDetails["project_days"];
                                    $fund = $projectDetails["fund_code"];
                                    $loc = $projectDetails["location_code"];
                                    $obj = $projectDetails["object_code"];
                                    $func = $projectDetails["function_code"];
                                    $location_id = $projectDetails["location_id"];

                                    // get the project's fund and function codes
                                    $proj_fund_code = $proj_function_code = null; 
                                    $proj_staff_location = 0;
                                    $getCodes = mysqli_prepare($conn, "SELECT fund_code, function_code, staff_location FROM projects WHERE code=?");
                                    mysqli_stmt_bind_param($getCodes, "s", $code);
                                    if (mysqli_stmt_execute($getCodes))
                                    {
                                        $getCodesResult = mysqli_stmt_get_result($getCodes);
                                        if (mysqli_num_rows($getCodesResult) > 0)
                                        {
                                            $codes = mysqli_fetch_array($getCodesResult);
                                            $proj_fund_code = $codes["fund_code"];
                                            $proj_function_code = $codes["function_code"];
                                            $proj_staff_location = $codes["staff_location"];
                                        }
                                    }

                                    ?>
                                        <div class="modal fade" tabindex="-1" role="dialog" id="editProjectEmployeeModal" data-bs-backdrop="static" aria-labelledby="editProjectEmployeeModalLabel" aria-hidden="true">
                                            <div class="modal-dialog" role="document">
                                                <div class="modal-content">
                                                    <div class="modal-header primary-modal-header">
                                                        <h5 class="modal-title primary-modal-title" id="editProjectEmployeeModalLabel">Edit Project Employee</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>

                                                    <div class="modal-body">
                                                        <div class="row align-items-center my-2">
                                                            <div class="col-4 text-end"><label for="add-project_employee-employee"><span class="required-field">*</span> Employee:</label></div>
                                                            <div class="col-8"><input class="form-control w-100" id="edit-project_employee-employee" name="edit-project_employee-employee" value="<?php echo $displayName; ?>" disabled></div>
                                                        </div>

                                                        <div class="row align-items-center my-2">
                                                            <div class="col-4 text-end"><label for="edit-project_employee-days"><span class="required-field">*</span> Days In Project:</label></div>
                                                            <div class="col-8"><input type="number" min="0" max="365" class="form-control w-100" id="edit-project_employee-days" name="edit-project_employee-days" value="<?php echo $project_days; ?>" required></div>
                                                        </div>

                                                        <div class="row align-items-center my-2">
                                                            <div class="col-4 text-end"><label for="edit-project_employee-fund"><span class="required-field">*</span> Fund Code:</label></div>
                                                            <div class="col-8"><input type="text" class="form-control w-100" id="edit-project_employee-fund" name="edit-project_employee-fund" value="<?php if (isset($fund) && $fund <> "") { echo $fund; } else if (isset($project_fund_code) && $project_fund_code <> "") { echo $project_fund_code; } ?>" required></div>
                                                        </div>

                                                        <div class="row align-items-center my-2">
                                                            <div class="col-4 text-end"><label for="edit-project_employee-loc"><span class="required-field">*</span> Location Code:</label></div>
                                                            <div class="col-8"><input type="text" class="form-control w-100" id="edit-project_employee-loc" name="edit-project_employee-loc" value="<?php echo $loc; ?>" required></div>
                                                        </div>

                                                        <div class="row align-items-center my-2">
                                                            <div class="col-4 text-end"><label for="edit-project_employee-obj"><span class="required-field">*</span> Object Code:</label></div>
                                                            <div class="col-8"><input type="text" class="form-control w-100" id="edit-project_employee-obj" name="edit-project_employee-obj" value="<?php echo $obj; ?>" required></div>
                                                        </div>

                                                        <div class="row align-items-center my-2">
                                                            <div class="col-4 text-end"><label for="edit-project_employee-func"><span class="required-field">*</span> Function Code:</label></div>
                                                            <div class="col-8"><input type="text" class="form-control w-100" id="edit-project_employee-func" name="edit-project_employee-func" value="<?php if (isset($func) && $func <> "") { echo $func; } else if (isset($project_func_code) && $project_func_code <> "") { echo $project_func_code; } ?>" required></div>
                                                        </div>

                                                        <?php if ($proj_staff_location == 1 || $proj_staff_location == 2) { ?>
                                                            <div class="row align-items-center my-2">
                                                                <div class="col-4 text-end">
                                                                    <label for="edit-project_employee-staff_location">Staff Location</label>
                                                                </div>
                                                                <div class="col-8">
                                                                    <?php if ($proj_staff_location == 1) { ?>
                                                                        <select class="form-select" id="edit-project_employee-staff_location">
                                                                            <option></option>
                                                                            <?php
                                                                                // get customers to populate options
                                                                                $getCustomers = mysqli_query($conn, "SELECT id, name FROM customers ORDER BY name ASC");
                                                                                if (mysqli_num_rows($getCustomers) > 0)
                                                                                {
                                                                                    while ($customer = mysqli_fetch_array($getCustomers))
                                                                                    {
                                                                                        // store customer details locally
                                                                                        $customer_id = $customer["id"];
                                                                                        $customer_name = $customer["name"];

                                                                                        // build option
                                                                                        if ($location_id == $customer_id) { echo "<option id='".$customer_id."' selected>".$customer_name."</option>"; }
                                                                                        else { echo "<option id='".$customer_id."'>".$customer_name."</option>"; }
                                                                                    }
                                                                                }
                                                                            ?>
                                                                        </select>
                                                                    <?php } else if ($proj_staff_location == 2) { ?>
                                                                        <select class="form-select" id="edit-project_employee-staff_location">
                                                                            <option></option>
                                                                            <?php
                                                                                // get categories/classrooms to populate options
                                                                                $getCategories = mysqli_query($conn, "SELECT id, name FROM caseload_categories WHERE is_classroom=1 ORDER BY name ASC");
                                                                                if (mysqli_num_rows($getCategories) > 0)
                                                                                {
                                                                                    while ($category = mysqli_fetch_array($getCategories))
                                                                                    {
                                                                                        // store category details locally
                                                                                        $category_id = $category["id"];
                                                                                        $category_name = $category["name"];

                                                                                        // create option group for the category
                                                                                        ?>
                                                                                            <optgroup label="<?php echo $category_name; ?>">
                                                                                            <?php
                                                                                                // get classrooms to populate options
                                                                                                $getClassrooms = mysqli_prepare($conn, "SELECT id, name FROM caseload_classrooms WHERE category_id=? ORDER BY name ASC");
                                                                                                mysqli_stmt_bind_param($getClassrooms, "i", $category_id);
                                                                                                if (mysqli_stmt_execute($getClassrooms))
                                                                                                {
                                                                                                    $getClassroomsResult = mysqli_stmt_get_result($getClassrooms);
                                                                                                    if (mysqli_num_rows($getClassroomsResult) > 0)
                                                                                                    {
                                                                                                        while ($classroom = mysqli_fetch_array($getClassroomsResult))
                                                                                                        {
                                                                                                            // store classroom details locally
                                                                                                            $classroom_id = $classroom["id"];
                                                                                                            $classroom_name = $classroom["name"];

                                                                                                            // build option 
                                                                                                            if ($location_id == $classroom_id) { echo "<option value='".$classroom_id."' selected>".$classroom_name."</option>"; }
                                                                                                            else { echo "<option value='".$classroom_id."'>".$classroom_name."</option>"; }
                                                                                                        }
                                                                                                    }
                                                                                                }
                                                                                            ?>
                                                                                            </optgroup>
                                                                                        <?php
                                                                                    }
                                                                                }
                                                                            ?>
                                                                        </select>
                                                                    <?php } ?>
                                                                </div>
                                                            </div>
                                                        <?php } else { ?>
                                                            <input type="hidden" id="edit-project_employee-staff_location" value="0" aria-hidden="true">
                                                        <?php } ?>

                                                        <!-- Required Field Indicator -->
                                                        <div class="row justify-content-center">
                                                            <div class="text-center fst-italic">
                                                                <span class="required-field">*</span> indicates a required field
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-primary" onclick="editProjectEmployee(<?php echo $id; ?>, '<?php echo $code; ?>', <?php echo $record; ?>);"><i class="fa-solid fa-floppy-disk"></i> Save Changes</button>
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
                }
            }
        }
        
        // disconnect from the database
        mysqli_close($conn);
    }
?>