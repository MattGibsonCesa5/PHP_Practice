<?php
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get additional required files
        include("../../includes/config.php");
        include("../../includes/functions.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        // verify the user has permission to edit employees
        if (checkUserPermission($conn, "EDIT_EMPLOYEES"))
        {   
            // get period name from POST
            if (isset($_POST["period"]) && $_POST["period"] <> "") { $period = $_POST["period"]; } else { $period = null; }

            // verify the period exists; if it exists, store the period ID
            if ($period != null && $period_id = getPeriodID($conn, $period)) 
            {
                // get the employee ID from POST
                if (isset($_POST["employee_id"]) && $_POST["employee_id"] <> "") { $employee_id = $_POST["employee_id"]; } else { $employee_id = null; }

                // if the employee ID is set; continue
                if ($employee_id != null)
                {
                    // get the current employee details
                    $getEmployeeInfo = mysqli_prepare($conn, "SELECT e.fname, e.lname, e.email, e.phone, e.birthday, e.gender, e.address_id, e.most_recent_hire_date, e.most_recent_end_date, e.original_hire_date, e.original_end_date, e.role_id, e.global, e.sync_demographics, e.sync_position, e.sync_contract,
                                                                    ec.contract_days, ec.contract_type, ec.yearly_rate, ec.health_insurance, ec.dental_insurance, ec.wrs_eligible, ec.assignment_position, ec.sub_assignment, ec.experience, ec.experience_adjustment, ec.highest_degree, ec.active,
                                                                    ec.title_id, ec.contract_start_date, ec.contract_end_date, ec.calendar_type, ec.number_of_pays, ec.supervisor_id
                                                            FROM employees e
                                                            LEFT JOIN employee_compensation ec ON e.id=ec.employee_id
                                                            WHERE e.id=? AND ec.period_id=?");
                    mysqli_stmt_bind_param($getEmployeeInfo, "ii", $employee_id, $period_id);
                    if (mysqli_stmt_execute($getEmployeeInfo))
                    {
                        $getEmployeeInfoResults = mysqli_stmt_get_result($getEmployeeInfo);
                        if (mysqli_num_rows($getEmployeeInfoResults) > 0) // employee exists; populate modal
                        {
                            // store employee details locally
                            $employee = mysqli_fetch_array($getEmployeeInfoResults);
                            $fname = $employee["fname"];
                            $lname = $employee["lname"];
                            $email = $employee["email"];
                            $phone = $employee["phone"];
                            $address_id = $employee["address_id"];
                            $displayDOB = date("m/d/Y", strtotime($employee["birthday"]));
                            $gender = $employee["gender"];
                            $hire_date = $employee["most_recent_hire_date"];
                            $end_date = $employee["most_recent_end_date"];
                            $original_hire_date = $employee["original_hire_date"];
                            $original_end_date = $employee["original_end_date"];
                            $role_id = $employee["role_id"];
                            $global = $employee["global"];
                            $sync_demographics = $employee["sync_demographics"];
                            $sync_position = $employee["sync_position"];
                            $sync_contract = $employee["sync_contract"];
                            $days = $employee["contract_days"];
                            $contract_type = $employee["contract_type"];
                            $rate = $employee["yearly_rate"];
                            $health = $employee["health_insurance"];
                            $dental = $employee["dental_insurance"];
                            $wrs = $employee["wrs_eligible"];
                            $position = $employee["assignment_position"];
                            $area = $employee["sub_assignment"];
                            $experience = $employee["experience"];
                            $experience_adjustment = $employee["experience_adjustment"];
                            $degree = $employee["highest_degree"];
                            $active = $employee["active"];
                            $title_id = $employee["title_id"];
                            $contract_start_date = $employee["contract_start_date"];
                            $contract_end_date = $employee["contract_end_date"];
                            $calendar_type = $employee["calendar_type"];
                            $num_of_pays = $employee["number_of_pays"];
                            $supervisor_id = $employee["supervisor_id"];

                            // handle date validation
                            if (isset($hire_date) && $hire_date != null) { $hire_date = date("m/d/Y", strtotime($hire_date)); } else { $hire_date = ""; }
                            if (isset($end_date) && $end_date != null) { $end_date = date("m/d/Y", strtotime($end_date)); } else { $end_date = ""; }
                            if (isset($original_hire_date) && $original_hire_date != null) { $original_hire_date = date("m/d/Y", strtotime($original_hire_date)); } else { $original_hire_date = ""; }
                            if (isset($original_end_date) && $original_end_date != null) { $original_end_date = date("m/d/Y", strtotime($original_end_date)); } else { $original_end_date = ""; }
                            if (isset($contract_start_date) && $contract_start_date != null) { $contract_start_date = date("m/d/Y", strtotime($contract_start_date)); } else { $contract_start_date = ""; }
                            if (isset($contract_end_date) && $contract_end_date != null) { $contract_end_date = date("m/d/Y", strtotime($contract_end_date)); } else { $contract_end_date = ""; }

                            // get the employee's address
                            $line1 = $line2 = $city = $state_id = $zip = ""; // initialize employee address to blank
                            $getAddress = mysqli_prepare($conn, "SELECT * FROM employee_addresses WHERE employee_id=? AND id=?");
                            mysqli_stmt_bind_param($getAddress, "ii", $employee_id, $address_id);
                            if (mysqli_stmt_execute($getAddress))
                            {
                                $getAddressResult = mysqli_stmt_get_result($getAddress);
                                if (mysqli_num_rows($getAddressResult) > 0)
                                {
                                    $address = mysqli_fetch_array($getAddressResult);
                                    $line1 = $address["line1"];
                                    $line2 = $address["line2"];
                                    $city = $address["city"];
                                    $state_id = $address["state_id"];
                                    $zip = $address["zip"];
                                }
                            }

                            // get the employee's primary department
                            $department_id = null; // assume no primary department assigned
                            $getDepartment = mysqli_prepare($conn, "SELECT d.id FROM departments d JOIN department_members dm ON d.id=dm.department_id WHERE dm.is_primary=1 AND dm.employee_id=?");
                            mysqli_stmt_bind_param($getDepartment, "i", $employee_id);
                            if (mysqli_stmt_execute($getDepartment))
                            {
                                $getDepartmentResult = mysqli_stmt_get_result($getDepartment);
                                if (mysqli_num_rows($getDepartmentResult) > 0) // primary department found
                                {
                                    $department_id = mysqli_fetch_array($getDepartmentResult)["id"];
                                }
                            }

                            ?>
                                <div class="modal fade" tabindex="-1" role="dialog" id="editEmployeeModal" data-bs-backdrop="static" aria-labelledby="editEmployeeModalLabel" aria-hidden="true">
                                    <div class="modal-dialog" role="document">
                                        <div class="modal-content">
                                            <div class="modal-header primary-modal-header">
                                                <h5 class="modal-title primary-modal-title" id="editEmployeeModalLabel">Edit Employee</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>

                                            <div class="modal-body">
                                                <div class="d-flex justify-content-between align-items-center align-middle">
                                                    <!-- Previous Slide -->
                                                    <button class="btn btn-primary" type="button" data-bs-target="#edit-employee-carousel" data-bs-slide="prev"><i class="fa-solid fa-angles-left fa-xl"></i></button>

                                                    <!-- Page 1 -->
                                                    <button class="btn btn-secondary btn-carousel-slider" id="edit-slider-page-1" data-bs-target="#edit-employee-carousel" data-bs-slide-to="0" aria-label="1. Employee Demographics" onclick="slideTo('edit', 'edit-slider-page-1');"><span aria-hidden="true">O</span></button>

                                                    <!-- Page 2 -->
                                                    <button class="btn btn-outline-secondary btn-carousel-slider" id="edit-slider-page-2" data-bs-target="#edit-employee-carousel" data-bs-slide-to="1" aria-label="2. Employee Position" onclick="slideTo('edit', 'edit-slider-page-2');"><span aria-hidden="true">O</span></button>

                                                    <!-- Page 3 -->
                                                    <button class="btn btn-outline-secondary btn-carousel-slider" id="edit-slider-page-3" type="button" data-bs-target="#edit-employee-carousel" data-bs-slide-to="2" aria-label="3. Employee Contract" onclick="slideTo('edit', 'edit-slider-page-3');"><span aria-hidden="true">O</span></button>

                                                    <!-- Next Slide -->
                                                    <button class="btn btn-primary" type="button" data-bs-target="#edit-employee-carousel" data-bs-slide="next"><i class="fa-solid fa-angles-right fa-xl"></i></button>
                                                </div>

                                                <div id="edit-employee-carousel" class="carousel carousel-dark slide" data-bs-ride="carousel" data-bs-interval="false">
                                                    <div class="carousel-inner">
                                                        <div class="carousel-item active" data-bs-interval="false">
                                                            <h3 class="d-flex justify-content-between align-items-center my-3 px-3">
                                                                Employee Demographics
                                                                <?php if ($sync_demographics == 1) { ?>
                                                                    <button class="btn btn-success btn-sm float-end" id="edit-sync-demographics" value="1" onclick="toggleSync('edit', 'demographics');" title="Sync employee demographics?"><i class="fa-solid fa-rotate"></i></button>
                                                                <?php } else { ?>
                                                                    <button class="btn btn-danger btn-sm float-end" id="edit-sync-demographics" value="0" onclick="toggleSync('edit', 'demographics');" title="Sync employee demographics?"><i class="fa-solid fa-rotate"></i></button>
                                                                <?php } ?>
                                                            </h3>

                                                            <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                                <!-- Employee ID -->
                                                                <div class="form-group col-11">
                                                                    <label for="edit-id"><span class="required-field">*</span> Employee ID:</label>
                                                                    <input type="text" class="form-control w-100" id="edit-id" name="edit-id" value="<?php echo $employee_id; ?>" disabled readonly>
                                                                </div>
                                                            </div>                                                            
                                                            
                                                            <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                                <!-- First Name -->
                                                                <div class="form-group col-5">
                                                                    <label for="edit-fname"><span class="required-field">*</span> First Name:</label>
                                                                    <input type="text" class="form-control w-100" id="edit-fname" name="edit-fname" value="<?php echo $fname; ?>" required>
                                                                </div>

                                                                <!-- Divider -->
                                                                <div class="form-group col-1 p-0"></div>

                                                                <!-- Last Name -->
                                                                <div class="form-group col-5">
                                                                    <label for="edit-lname"><span class="required-field">*</span> Last Name:</label>
                                                                    <input type="text" class="form-control w-100" id="edit-lname" name="edit-lname" value="<?php echo $lname; ?>" required>
                                                                </div>
                                                            </div>

                                                            <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                                <!-- Email -->
                                                                <div class="form-group col-11">
                                                                    <label for="edit-email"><span class="required-field">*</span> Email:</label>
                                                                    <input type="text" class="form-control w-100" id="edit-email" name="edit-email" value="<?php echo $email; ?>" required>
                                                                </div>
                                                            </div>

                                                            <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                                <!-- Phone -->
                                                                <div class="form-group col-11">
                                                                    <label for="edit-phone"><span class="required-field">*</span> Phone:</label>
                                                                    <input type="text" class="form-control w-100" id="edit-phone" name="edit-phone" value="<?php echo $phone; ?>" required>
                                                                </div>
                                                            </div>

                                                            <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                                <!-- Date Of Birth -->
                                                                <div class="form-group col-11">
                                                                    <label for="edit-birthday"><span class="required-field">*</span> Date Of Birth:</label>
                                                                    <input type="text" class="form-control w-100" id="edit-birthday" name="edit-birthday" value="<?php echo $displayDOB; ?>" required>
                                                                </div>
                                                            </div>

                                                            <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                                <!-- Gender -->
                                                                <div class="form-group col-11">
                                                                    <label for="edit-gender"><span class="required-field">*</span> Gender:</label>
                                                                    <select class="form-select w-100" id="edit-gender" name="edit-gender" required>
                                                                        <option value=1 <?php if ($gender == 1) { echo "selected"; } ?>>Male</option>
                                                                        <option value=2 <?php if ($gender == 2) { echo "selected"; } ?>>Female</option>
                                                                        <option value=0 <?php if ($gender == 0) { echo "selected"; } ?>>Unknown</option>
                                                                    </select>
                                                                </div>
                                                            </div>

                                                            <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                                <!-- Line 1 -->
                                                                <div class="form-group col-11">
                                                                    <label for="edit-address_line1"><span class="required-field">*</span> Address Line 1 (Street/P.O. Box):</label>
                                                                    <input type="text" class="form-control w-100" id="edit-address_line1" name="edit-address_line1" value="<?php echo $line1; ?>" required>
                                                                </div>
                                                            </div>

                                                            <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                                <!-- Line 2 -->
                                                                <div class="form-group col-11">
                                                                    <label for="edit-address_line2">Address Line 2 (Apt/Suite/Unit #):</label>
                                                                    <input type="text" class="form-control w-100" id="edit-address_line2" name="edit-address_line2" value="<?php echo $line2; ?>">
                                                                </div>
                                                            </div>

                                                            <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                                <!-- City -->
                                                                <div class="form-group col-11">
                                                                    <label for="edit-address_city"><span class="required-field">*</span> City:</label>
                                                                    <input type="text" class="form-control w-100" id="edit-address_city" name="edit-address_city" value="<?php echo $city; ?>" required>
                                                                </div>
                                                            </div>

                                                            <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                                <!-- State -->
                                                                <div class="form-group col-6">
                                                                    <label for="edit-address_state"><span class="required-field">*</span> State:</label>
                                                                    <select class="form-select w-100" id="edit-address_state" name="edit-address_state" required>
                                                                        <option value=0></option>
                                                                        <?php
                                                                            $getStates = mysqli_query($conn, "SELECT id, state FROM states");
                                                                            while ($state = mysqli_fetch_array($getStates)) 
                                                                            {
                                                                                if ($state_id == $state["id"]) { echo "<option value='".$state["id"]."' selected>".$state["state"]."</option>"; }
                                                                                else { echo "<option value='".$state["id"]."'>".$state["state"]."</option>"; }
                                                                            }
                                                                        ?>
                                                                    </select> 
                                                                </div>

                                                                <!-- Spacer -->
                                                                <div class="form-group col-1"></div>

                                                                <!-- Zip -->
                                                                <div class="form-group col-4">
                                                                    <label for="edit-address_zip"><span class="required-field">*</span> Zip Code:</label>
                                                                    <input type="text" class="form-control w-100" id="edit-address_zip" name="edit-address_zip" value="<?php echo $zip; ?>" required>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="carousel-item" data-bs-interval="false">
                                                            <h3 class="d-flex justify-content-between align-items-center my-3 px-3">
                                                                Employee Position
                                                                <?php if ($sync_position == 1) { ?>
                                                                    <button class="btn btn-success btn-sm float-end" id="edit-sync-position" value="1" onclick="toggleSync('edit', 'position');" title="Sync employee position?"><i class="fa-solid fa-rotate"></i></button>
                                                                <?php } else { ?>
                                                                    <button class="btn btn-danger btn-sm float-end" id="edit-sync-position" value="0" onclick="toggleSync('edit', 'position');" title="Sync employee position?"><i class="fa-solid fa-rotate"></i></button>
                                                                <?php } ?>
                                                            </h3>

                                                            <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                                <!-- Title -->
                                                                <div class="form-group col-11">
                                                                    <label for="edit-title"><span class="required-field">*</span> Title:</label>
                                                                    <select class="form-select w-100" id="edit-title" name="edit-title" required>
                                                                        <option></option>
                                                                        <?php
                                                                            $getTitles = mysqli_query($conn, "SELECT * FROM employee_titles ORDER BY name ASC");
                                                                            if (mysqli_num_rows($getTitles) > 0)
                                                                            {
                                                                                while ($title = mysqli_fetch_array($getTitles))
                                                                                {
                                                                                    // store title details locally
                                                                                    $select_title_id = $title["id"];
                                                                                    $title_name = $title["name"];

                                                                                    // build dropdown option
                                                                                    if ($select_title_id == $title_id)
                                                                                    {
                                                                                        echo "<option value='".$select_title_id."' selected>".$title_name."</option>";
                                                                                    }
                                                                                    else 
                                                                                    {
                                                                                        echo "<option value='".$select_title_id."'>".$title_name."</option>";
                                                                                    }
                                                                                }
                                                                            }
                                                                        ?>
                                                                    </select>
                                                                </div>
                                                            </div>

                                                            <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                                <!-- Department-->
                                                                <div class="form-group col-11">
                                                                    <label for="edit-dept">Primary Department:</label>
                                                                    <select class="form-select w-100" id="edit-dept" name="edit-dept">
                                                                        <option></option>
                                                                        <?php 
                                                                            $getDepartments = mysqli_query($conn, "SELECT id, name FROM departments");
                                                                            while ($department = mysqli_fetch_array($getDepartments))
                                                                            {
                                                                                if (isset($department["name"]) && ($department["name"] != null && $department["name"] <> ""))
                                                                                {
                                                                                    if ($department_id == $department["id"]) { echo "<option value='".$department["id"]."' selected>".$department["name"]."</option>"; }
                                                                                    else { echo "<option value='".$department["id"]."'>".$department["name"]."</option>"; }
                                                                                }
                                                                            }
                                                                        ?>
                                                                    </select>
                                                                </div>
                                                            </div>

                                                            <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                                <!-- Supervisor -->
                                                                <div class="form-group col-11">
                                                                    <label for="edit-supervisor">Supervisor:</label>
                                                                    <select class="form-select w-100" id="edit-supervisor" name="edit-supervisor">
                                                                        <option></option>
                                                                        <?php 
                                                                            $getSupervisors = mysqli_query($conn, "SELECT DISTINCT d.user_id, u.lname, u.fname FROM directors d
                                                                                                                    JOIN users u ON d.user_id=u.id
                                                                                                                    ORDER BY u.lname ASC, u.fname ASC");
                                                                            while ($supervisor = mysqli_fetch_array($getSupervisors))
                                                                            {
                                                                                // store supervisor details locally
                                                                                $db_supervisor_id = $supervisor["user_id"];
                                                                                
                                                                                // get supervisor name
                                                                                $supervisor_name = getUserDisplayName($conn, $db_supervisor_id);

                                                                                // build the option
                                                                                if ($supervisor_id == $db_supervisor_id) { echo "<option value='".$db_supervisor_id."' selected>".$supervisor_name."</option>"; }
                                                                                else { echo "<option value='".$db_supervisor_id."'>".$supervisor_name."</option>"; }
                                                                            }
                                                                        ?>
                                                                    </select>
                                                                </div>
                                                            </div>

                                                            <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                                <!-- Hire Date -->
                                                                <div class="form-group col-5">
                                                                    <label for="edit-hire_date"><span class="required-field">*</span> Most Recent Hire Date:</label>
                                                                    <input type="text" class="form-control w-100" id="edit-hire_date" name="edit-hire_date" value="<?php echo $hire_date; ?>" autocomplete="off" required>
                                                                </div>

                                                                <!-- Spacer -->
                                                                <div class="form-group col-1"></div>

                                                                <!-- End Date -->
                                                                <div class="form-group col-5">
                                                                    <label for="edit-end_date">Most Recent End Date:</label>
                                                                    <input type="text" class="form-control w-100" id="edit-end_date" name="edit-end_date" value="<?php echo $end_date; ?>" autocomplete="off">
                                                                </div>
                                                            </div>

                                                            <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                                <!-- Hire Date -->
                                                                <div class="form-group col-5">
                                                                    <label for="edit-original_hire_date"><span class="required-field">*</span> Original Hire Date:</label>
                                                                    <input type="text" class="form-control w-100" id="edit-original_hire_date" name="edit-original_hire_date" value="<?php echo $original_hire_date; ?>" autocomplete="off" required>
                                                                </div>

                                                                <!-- Spacer -->
                                                                <div class="form-group col-1"></div>

                                                                <!-- End Date -->
                                                                <div class="form-group col-5">
                                                                    <label for="edit-original_end_date">Original End Date:</label>
                                                                    <input type="text" class="form-control w-100" id="edit-original_end_date" name="edit-original_end_date" value="<?php echo $original_end_date; ?>" autocomplete="off">
                                                                </div>
                                                            </div>

                                                            <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                                <!-- Total Years Of Experience -->
                                                                <div class="form-group col-7">
                                                                    <label for="edit-experience"><span class="required-field">*</span> Total Years Of Experience:</label>
                                                                    <input type="number" class="form-control w-100" id="edit-experience" name="edit-experience" value="<?php echo $experience; ?>" required>
                                                                </div>

                                                                <!-- Spacer -->
                                                                <div class="form-group col-1"></div>

                                                                <!-- Local Years Of Experience Adjustment -->
                                                                <div class="form-group col-3">
                                                                    <label for="edit-experience_adjustment">Local +/-</label>
                                                                    <input type="number" class="form-control w-100" id="edit-experience_adjustment" name="edit-experience_adjustment" min="0" value="<?php echo $experience_adjustment; ?>">
                                                                </div>
                                                            </div>

                                                            <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                                <!-- Assignment Position -->
                                                                <div class="form-group col-11">
                                                                    <label for="edit-position"><span class="required-field">*</span> Assignment Position:</label>
                                                                    <select class="form-select w-100" id="edit-position" name="edit-position" onchange="updatePositionArea('edit', this.value);" required>
                                                                        <option></option>
                                                                        <?php
                                                                            $positions = getDPIPositions($conn);
                                                                            for ($p = 0; $p < count($positions); $p++)
                                                                            {
                                                                                // create the combined positions string
                                                                                $position_str = $positions[$p]["position_code"] . " - " . $positions[$p]["position_name"];

                                                                                if ($position == $position_str) { echo "<option value='".$positions[$p]["position_code"]."' selected>".$positions[$p]["position_code"]." - ".$positions[$p]["position_name"]."</option>"; }
                                                                                else { echo "<option value='".$positions[$p]["position_code"]."'>".$positions[$p]["position_code"]." - ".$positions[$p]["position_name"]."</option>"; }
                                                                            }
                                                                        ?>
                                                                    </select>
                                                                </div>
                                                            </div>

                                                            <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                                <!-- Position Area -->
                                                                <div class="form-group col-11">
                                                                    <label for="edit-area"><span class="required-field">*</span> Subcategory (Assignment Position):</label>
                                                                    <select class="form-select w-100" id="edit-area" name="edit-area" required>
                                                                        <option></option>
                                                                        <?php
                                                                            if (isset($position) && $position != null)
                                                                            {
                                                                                $areas = getPositionAreas($conn, substr($position, 0, 2));
                                                                                for ($a = 0; $a < count($areas); $a++)
                                                                                {
                                                                                    // create the combined areas string
                                                                                    $area_str = $areas[$a]["area_code"] . " - " . $areas[$a]["area_name"];

                                                                                    if ($area == $area_str) { echo "<option value='".$areas[$a]["area_code"]."' selected>".$areas[$a]["area_code"]." - ".$areas[$a]["area_name"]."</option>"; }
                                                                                    else { echo "<option value='".$areas[$a]["area_code"]."'>".$areas[$a]["area_code"]." - ".$areas[$a]["area_name"]."</option>"; }
                                                                                }
                                                                            }
                                                                        ?>
                                                                    </select>
                                                                </div>
                                                            </div>

                                                            <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                                <!-- Highest Degree -->
                                                                <div class="form-group col-11">
                                                                    <label for="edit-title"><span class="required-field">*</span> Highest Degree:</label>
                                                                    <select class="form-select w-100" id="edit-degree" name="edit-degree" required>
                                                                        <option></option>
                                                                        <?php
                                                                            $degrees = getDegrees($conn);
                                                                            for ($d = 0; $d < count($degrees); $d++)
                                                                            {
                                                                                $degree_str = $degrees[$d]["code"] . " - " . $degrees[$d]["label"];
                                                                                if ($degree == $degree_str) { echo "<option selected>".$degrees[$d]["code"]." - ".$degrees[$d]["label"]."</option>"; }
                                                                                else { echo "<option>".$degrees[$d]["code"]." - ".$degrees[$d]["label"]."</option>"; }
                                                                            }
                                                                        ?>
                                                                    </select>
                                                                </div>
                                                            </div>

                                                            <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                                <!-- Global Employee -->
                                                                <div class="form-group col-11">
                                                                <span class="required-field">*</span> Global Employee:</label>
                                                                    <?php if ($global == 1) { ?>
                                                                        <button class="btn btn-success w-100" id="edit-global" value=1 onclick="updateGlobal('edit-global');">Yes</button>
                                                                    <?php } else { ?>
                                                                        <button class="btn btn-danger w-100" id="edit-global" value=0 onclick="updateGlobal('edit-global');">No</button>
                                                                    <?php } ?>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="carousel-item" data-bs-interval="false">
                                                            <h3 class="d-flex justify-content-between align-items-center my-3 px-3">
                                                                Employee Contract
                                                                <?php if ($sync_contract == 1) { ?>
                                                                    <button class="btn btn-success btn-sm float-end" id="edit-sync-contract" value="1" onclick="toggleSync('edit', 'contract');" title="Sync employee contract details?"><i class="fa-solid fa-rotate"></i></button>
                                                                <?php } else { ?>
                                                                    <button class="btn btn-danger btn-sm float-end" id="edit-sync-contract" value="0" onclick="toggleSync('edit', 'contract');" title="Sync employee contract details?"><i class="fa-solid fa-rotate"></i></button>
                                                                <?php } ?>
                                                            </h3>

                                                            <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                                <!-- Start Date -->
                                                                <div class="form-group col-5">
                                                                    <label for="edit-contract_start_date">Contract Start Date:</label>
                                                                    <input type="text" class="form-control w-100" id="edit-contract_start_date" name="edit-contract_start_date" value="<?php echo $contract_start_date; ?>" autocomplete="off" required>
                                                                </div>

                                                                <!-- Spacer -->
                                                                <div class="form-group col-1"></div>

                                                                <!-- End Date -->
                                                                <div class="form-group col-5">
                                                                    <label for="edit-contract_end_date">Contract End Date:</label>
                                                                    <input type="text" class="form-control w-100" id="edit-contract_end_date" name="edit-contract_end_date" value="<?php echo $contract_end_date; ?>" autocomplete="off" required>
                                                                </div>
                                                            </div>

                                                            <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                                <!-- Contract Type -->
                                                                <div class="form-group col-11">
                                                                    <label for="edit-contract_type"><span class="required-field">*</span> Contract Type:</label>
                                                                    <select class="form-select w-100" id="edit-contract_type" name="edit-contract_type" required>
                                                                        <option value=0 <?php if ($contract_type == 0) { echo "selected"; } ?>>Regular</option>
                                                                        <option value=1 <?php if ($contract_type == 1) { echo "selected"; } ?>>Limited</option>
                                                                        <option value=2 <?php if ($contract_type == 2) { echo "selected"; } ?>>At-Will</option>
                                                                        <option value=3 <?php if ($contract_type == 3) { echo "selected"; } ?>>Section 118</option>
                                                                        <option value=4 <?php if ($contract_type == 4) { echo "selected"; } ?>>Hourly</option>
                                                                    </select>
                                                                </div>
                                                            </div>

                                                            <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                                <!-- Contract Days -->
                                                                <div class="form-group col-11">
                                                                    <label for="edit-days"><span class="required-field">*</span> Contact Days:</label>
                                                                    <input type="number" min="0" max="365" class="form-control w-100" id="edit-days" name="edit-days" value="<?php echo $days; ?>" required>
                                                                </div>
                                                            </div>

                                                            <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                                <!-- Calendar Type -->
                                                                <div class="form-group col-11">
                                                                    <label for="edit-calendar_type"><span class="required-field">*</span> Calendar Type:</label>
                                                                    <select class="form-select w-100" id="edit-calendar_type" name="edit-calendar_type" required>
                                                                        <option value=0 <?php if ($calendar_type == 0) { echo "selected"; } ?>>N/A</option>
                                                                        <option value=1 <?php if ($calendar_type == 1) { echo "selected"; } ?>>Hourly</option>
                                                                        <option value=2 <?php if ($calendar_type == 2) { echo "selected"; } ?>>Salary</option>
                                                                    </select>
                                                                </div>
                                                            </div>

                                                            <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                                <!-- Yearly Rate -->
                                                                <div class="form-group col-11">
                                                                    <label for="edit-rate"><span class="required-field">*</span> Yearly Rate:</label>
                                                                    <input type="number" min="0.00" class="form-control w-100" id="edit-rate" name="edit-rate" value="<?php echo sprintf("%0.2f", $rate); ?>" required>
                                                                </div>
                                                            </div>

                                                            <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                                <!-- Number Of Pays -->
                                                                <div class="form-group col-11">
                                                                    <label for="edit-num_of_pays"><span class="required-field">*</span> Number Of Pays:</label>
                                                                    <input type="number" min="0" max="365" class="form-control w-100" id="edit-num_of_pays" name="edit-num_of_pays" value="<?php echo $num_of_pays; ?>" required>
                                                                </div>
                                                            </div>

                                                            <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                                <!-- Health Coverage -->
                                                                <div class="form-group col-11">
                                                                    <label for="edit-health"><span class="required-field">*</span> Health Coverage:</label>
                                                                    <select class="form-select w-100" id="edit-health" name="edit-health" required>
                                                                        <option value=0 <?php if ($health == 0) { echo "selected"; } ?>>None</option>
                                                                        <option value=2 <?php if ($health == 2) { echo "selected"; } ?>>Single</option>
                                                                        <option value=1 <?php if ($health == 1) { echo "selected"; } ?>>Family</option>
                                                                    </select>
                                                                </div>
                                                            </div>

                                                            <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                                <!-- Dental Coverage -->
                                                                <div class="form-group col-11">
                                                                    <label for="edit-dental"><span class="required-field">*</span> Dental Coverage:</label>
                                                                    <select class="form-select w-100" id="edit-dental" name="edit-dental" required>
                                                                        <option value=0 <?php if ($dental == 0) { echo "selected"; } ?>>None</option>
                                                                        <option value=2 <?php if ($dental == 2) { echo "selected"; } ?>>Single</option>
                                                                        <option value=1 <?php if ($dental == 1) { echo "selected"; } ?>>Family</option>
                                                                    </select>
                                                                </div>
                                                            </div>

                                                            <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                                <!-- WRS Eligibility -->
                                                                <div class="form-group col-11">
                                                                    <label for="edit-wrs"><span class="required-field">*</span> WRS Eligible:</label>
                                                                    <select class="form-select w-100" id="edit-wrs" name="edit-wrs" required>
                                                                        <option value=0 <?php if ($wrs == 0) { echo "selected"; } ?>>No</option>
                                                                        <option value=1 <?php if ($wrs == 1) { echo "selected"; } ?>>Yes</option>
                                                                    </select>
                                                                </div>
                                                            </div>

                                                            <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                                <!-- Status -->
                                                                <div class="form-group col-11">
                                                                    <label for="edit-status"><span class="required-field">*</span> Status:</label>
                                                                    <?php if ($active == 1) { ?>
                                                                        <button class="btn btn-success w-100" id="edit-status" value=1 onclick="updateStatus('edit-status');" aria-describedby="statusHelpBlock">Active</button>
                                                                    <?php } else { ?>
                                                                        <button class="btn btn-danger w-100" id="edit-status" value=0 onclick="updateStatus('edit-status');" aria-describedby="statusHelpBlock">Inactive</button>
                                                                    <?php } ?>
                                                                    <div id="statusHelpBlock" class="form-text">
                                                                        Employee status is on a per-period basis.
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-primary" onclick="editEmployee(<?php echo $employee_id; ?>);"><i class="fa-solid fa-floppy-disk"></i> Save Employee</button>
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

        // disconnect from the database
        mysqli_close($conn);
    }
?>