<?php
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get additional required files
        include("../../includes/functions.php");
        include("../../includes/config.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "VIEW_CASELOADS_ALL") && checkUserPermission($conn, "VIEW_THERAPISTS"))
        {
            // initialize an array to store all periods; then get all periods and store in the array
            $periods = [];
            $getPeriods = mysqli_query($conn, "SELECT id, name, active, start_date, end_date FROM `periods` ORDER BY active DESC, name ASC");
            if (mysqli_num_rows($getPeriods) > 0) // periods exist
            {
                while ($period = mysqli_fetch_array($getPeriods))
                {
                    // store period's data in array
                    $periods[] = $period;

                    // store the acitve period's name
                    if ($period["active"] == 1) 
                    { 
                        $active_period_label = $period["name"];
                        $active_start_date = date("m/d/Y", strtotime($period["start_date"]));
                        $active_end_date = date("m/d/Y", strtotime($period["end_date"])); 
                    }
                }
            }

            // get the caseload ID from POST
            if (isset($_POST["case_id"]) && $_POST["case_id"] <> "") { $case_id = $_POST["case_id"]; } else { $case_id = null; }

            // get request ID from POST (not required)
            if (isset($_POST["request_id"]) && $_POST["request_id"] <> "") { $request_id = $_POST["request_id"]; } else { $request_id = null; }

            if (verifyCase($conn, $case_id))
            {
                // get the transfer request details if set
                if ($request_id != null)
                {
                    $getRequestDetails = mysqli_prepare($conn, "SELECT new_caseload_id, iep_completed, transfer_date FROM caseload_transfers WHERE id=?");
                    mysqli_stmt_bind_param($getRequestDetails, "i", $request_id);
                    if (mysqli_stmt_execute($getRequestDetails))
                    {
                        $getRequestDetailsResult = mysqli_stmt_get_result($getRequestDetails);
                        if (mysqli_num_rows($getRequestDetailsResult) > 0) // request found
                        {
                            // store request details locally
                            $requestDetails = mysqli_fetch_array($getRequestDetailsResult);
                            $REQUEST["new_caseload_id"] = $requestDetails["new_caseload_id"];
                            $REQUEST["iep_completed"] = $requestDetails["iep_completed"];
                            $REQUEST["transfer_date"] = $requestDetails["transfer_date"];
                        }
                    }
                }

                // get the student's current data
                $getCaseload = mysqli_prepare($conn, "SELECT * FROM cases WHERE id=?");
                mysqli_stmt_bind_param($getCaseload, "i", $case_id);
                if (mysqli_stmt_execute($getCaseload))
                {
                    $getCaseloadResult = mysqli_stmt_get_result($getCaseload);
                    if (mysqli_num_rows($getCaseloadResult) > 0)
                    {
                        // store caseload details locally
                        $caseload = mysqli_fetch_array($getCaseloadResult);
                        $case_id = $caseload["id"];
                        $caseload_id = $caseload["caseload_id"];
                        $student_id = $caseload["student_id"];
                        $period_id = $caseload["period_id"];
                        if (isset($caseload["therapist_id"])) { $therapist_id = $caseload["therapist_id"]; } else { $therapist_id = "-"; }
                        if (isset($caseload["start_date"])) { $start_date = date("m/d/Y", strtotime($caseload["start_date"])); } else { $start_date = $active_start_date; }
                        if (isset($caseload["end_date"])) { $end_date = date("m/d/Y",  strtotime($caseload["end_date"])); } else { $end_date = $active_end_date; }
                        $frequency = $caseload["frequency"];
                        $units = $caseload["estimated_uos"];

                        // get student details
                        $student_name = "";
                        $getStudent = mysqli_prepare($conn, "SELECT fname, lname FROM caseload_students WHERE id=?");
                        mysqli_stmt_bind_param($getStudent, "i", $student_id);
                        if (mysqli_stmt_execute($getStudent))
                        {
                            $getStudentResult = mysqli_stmt_get_result($getStudent);
                            if (mysqli_num_rows($getStudentResult) > 0) // student found
                            {
                                // store student data locally
                                $student = mysqli_fetch_array($getStudentResult);
                                $fname = $student["fname"];
                                $lname = $student["lname"];

                                // create the name to be displayed
                                $student_name = $lname.", ".$fname;
                            }
                        }

                        // get caseload name
                        $caseload_name = getCaseloadDisplayName($conn, $caseload_id);

                        // check to see if units and frequency are enabled, or if it is a classroom for membership days
                        $isClassroom = isCaseloadClassroom($conn, $caseload_id);
                        $frequencyEnabled = isCaseloadFrequencyEnabled($conn, $caseload_id);
                        $uosEnabled = isCaseloadUOSEnabled($conn, $caseload_id);
                        $daysEnabled = isCaseloadDaysEnabled($conn, $caseload_id);

                        // get the category of teh caseload
                        $caseload_category_id = getCaseloadCategory($conn, $caseload_id);

                        ?>
                            <!-- Transfer Caseload Modal -->
                            <div class="modal fade" tabindex="-1" role="dialog" id="transferCaseloadModal" data-bs-backdrop="static" aria-labelledby="transferCaseloadModalLabel" aria-hidden="true">
                                <div class="modal-dialog" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header primary-modal-header">
                                            <h5 class="modal-title primary-modal-title" id="transferCaseloadModalLabel">Transfer Student</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>

                                        <div class="modal-body">
                                            <?php if ($request_id != null && isset($REQUEST)) { ?>
                                                <input type="hidden" id="transfer-request_id" value="<?php echo $request_id; ?>" aria-hidden="true">
                                            <?php } ?>

                                            <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                <!-- Student -->
                                                <div class="form-group col-11">
                                                    <label for="transfer-student">Student:</label>
                                                    <input type="text" class="form-control" id="transfer-student" name="transfer-student" value="<?php echo $student_name; ?>" readonly disabled>
                                                </div>
                                            </div>

                                            <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                <!-- Old Therapist -->
                                                <div class="form-group col-11">
                                                    <label for="transfer-previous_therapist">Current Caseload:</label>
                                                    <input type="text" class="form-control" id="transfer-previous_therapist" name="transfer-previous_therapist" value="<?php echo $caseload_name; ?>" readonly disabled>
                                                </div>
                                            </div>

                                            <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                <!-- New Therapist -->
                                                <div class="form-group col-11">
                                                    <label for="transfer-new_caseload"><span class="required-field">*</span> Transfer To:</label>
                                                    <select class="w-100" id="transfer-new_caseload" name="transfer-new_caseload">
                                                        <?php
                                                            // get a list of all caseload categories
                                                            $getCategories = mysqli_query($conn, "SELECT * FROM caseload_categories ORDER BY name ASC");
                                                            if (mysqli_num_rows($getCategories) > 0)
                                                            {
                                                                // for each category, attempt to bill districts
                                                                while ($category = mysqli_fetch_array($getCategories))
                                                                {
                                                                    // store category details locally
                                                                    $category_id = $category["id"];
                                                                    $category_name = $category["name"];

                                                                    // create the option group
                                                                    echo "<optgroup label='".$category_name."'>";

                                                                    // get all caseloads for the category
                                                                    $getCaseloads = mysqli_prepare($conn, "SELECT c.id AS caseload_id FROM caseloads c 
                                                                                                        JOIN users u ON u.id=c.employee_id
                                                                                                        LEFT JOIN caseloads_status cs ON c.id=cs.caseload_id
                                                                                                        WHERE cs.status=1 AND cs.period_id=? AND c.category_id=?
                                                                                                        ORDER BY u.lname ASC, u.fname ASC");
                                                                    mysqli_stmt_bind_param($getCaseloads, "ii", $period_id, $category_id);
                                                                    if (mysqli_stmt_execute($getCaseloads))
                                                                    {
                                                                        $getCaseloadsResults = mysqli_stmt_get_result($getCaseloads);
                                                                        if (mysqli_num_rows($getCaseloadsResults) > 0) // caseloads found
                                                                        {
                                                                            while ($caseloads = mysqli_fetch_array($getCaseloadsResults))
                                                                            {
                                                                                // store caseload details locally
                                                                                $new_caseload_id = $caseloads["caseload_id"];

                                                                                // get caseload display name
                                                                                $caseload_name = getCaseloadDisplayName($conn, $new_caseload_id);

                                                                                // create the option
                                                                                if (isset($REQUEST["new_caseload_id"]) && ($REQUEST["new_caseload_id"] == $new_caseload_id)) { echo "<option value='".$new_caseload_id."' selected>".$caseload_name."</option>"; }
                                                                                else { echo "<option value='".$new_caseload_id."'>".$caseload_name."</option>"; }
                                                                            }
                                                                        }
                                                                    }

                                                                    // close the option group
                                                                    echo "</optgroup>";
                                                                }
                                                            }
                                                        ?>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="form-row d-flex justify-content-center align-items-center mt-3">
                                                <!-- Start Date -->
                                                <div class="form-group col-5">
                                                    <label for="transfer-transfer_date"><span class="required-field">*</span> Transfer Date:</label>
                                                    <input type="text" class="form-control w-100" id="transfer-transfer_date" name="transfer-transfer_date" value="<?php if (isset($REQUEST["transfer_date"]) && $REQUEST["transfer_date"] != null) { echo date("m/d/Y", strtotime($REQUEST["transfer_date"])); } else { echo date("m/d/Y"); } ?>" autocomplete="off" aria-describedby="dateHelpBlock" required>
                                                </div>

                                                <!-- Divider -->
                                                <div class="form-group col-1 p-0"></div>
                                                
                                                <!-- End Date -->
                                                <div class="form-group col-5">
                                                    <label for="transfer-end_date"><span class="required-field">*</span> End Date:</label>
                                                    <input type="text" class="form-control w-100" id="transfer-end_date" name="transfer-end_date" value="<?php echo $end_date; ?>" autocomplete="off" required>
                                                </div>
                                            </div>
                                            <div class="form-row d-flex justify-content-center align-items-center mb-3 px-3">
                                                <div id="dateHelpBlock" class="form-text">
                                                    The "transfer date" will become the end date from the caseload we are transferring from, 
                                                    and become the start date for the caseload we are transferring into.
                                                </div>
                                            </div>

                                            <?php if ($frequencyEnabled === true && $uosEnabled === true) { ?>
                                                <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                    <!-- Frequency -->
                                                    <div class="form-group col-6">
                                                        <label for="transfer-frequency"><span class="required-field">*</span> Starting Frequency:</label>
                                                        <input type="text" class="form-control w-100" id="transfer-frequency" name="transfer-frequency" required>
                                                    </div>

                                                    <!-- Divider -->
                                                    <div class="form-group col-1 p-0"></div>

                                                    <!-- Units Of Service -->
                                                    <div class="form-group col-3">
                                                        <label for="transfer-uos"><span class="required-field">*</span> UOS:</label>
                                                        <input type="number" min="0" class="form-control w-100" id="transfer-uos" name="transfer-uos" required>
                                                    </div>

                                                    <div class="form-group col-1">
                                                        <label></label> <!-- spacer -->
                                                        <a class="btn btn-secondary" target="popup" onclick="window.open('uos_calculator_mini.php', 'UOS Calculator', 'width=768, height=700');" title="Open the UOS Calculator in a new tab!"><i class="fa-solid fa-calculator"></i></a>
                                                    </div>
                                                </div>
                                            <?php } else if ($frequencyEnabled === true && $uosEnabled === false) { ?>
                                                <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                    <!-- Frequency -->
                                                    <div class="form-group col-11">
                                                        <label for="transfer-frequency"><span class="required-field">*</span> Starting Frequency:</label>
                                                        <input type="text" class="form-control w-100" id="transfer-frequency" name="transfer-frequency" required>
                                                        <input type="hidden" class="form-control w-100" id="transfer-uos" name="transfer-uos" value="0" aria-hidden="true" required disabled>
                                                    </div>
                                                </div>
                                            <?php } else if ($frequencyEnabled === false && $uosEnabled === true) { ?>
                                                <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                    <!-- Units Of Service -->
                                                    <div class="form-group col-9">
                                                        <label for="transfer-uos"><span class="required-field">*</span> UOS:</label>
                                                        <input type="number" class="form-control w-100" id="transfer-uos" name="transfer-uos" required>
                                                        <input type="hidden" class="form-control w-100" id="transfer-frequency" name="transfer-frequency" value="N/A" aria-hidden="true" required disabled>
                                                    </div>

                                                    <!-- Divider -->
                                                    <div class="form-group col-1 p-0"></div>

                                                    <div class="form-group col-1">
                                                        <label></label> <!-- spacer -->
                                                        <a class="btn btn-secondary" target="popup" onclick="window.open('uos_calculator_mini.php', 'UOS Calculator', 'width=768, height=700');" title="Open the UOS Calculator in a new tab!"><i class="fa-solid fa-calculator"></i></a>
                                                    </div>
                                                </div>
                                            <?php } else { ?>
                                                <input type="hidden" class="form-control w-100" id="transfer-frequency" name="transfer-frequency" value="N/A" aria-hidden="true" required disabled>
                                                <input type="hidden" class="form-control w-100" id="transfer-uos" name="transfer-uos" value="0" aria-hidden="true" required disabled>
                                            <?php } ?>

                                            <?php if ($isClassroom === true) { ?>
                                                <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                    <!-- Classroom -->
                                                    <div class="form-group col-11">
                                                        <label for="transfer-classroom"><span class="required-field">*</span> Classroom:</label>
                                                        <select class="form-select w-100" id="transfer-classroom" name="transfer-classroom" required>
                                                            <option></option>
                                                            <?php
                                                                $getClassrooms = mysqli_prepare($conn, "SELECT id, name FROM caseload_classrooms WHERE category_id=? ORDER BY name ASC");
                                                                mysqli_stmt_bind_param($getClassrooms, "i", $caseload_category_id);
                                                                if (mysqli_stmt_execute($getClassrooms))
                                                                {
                                                                    $getClassroomsResults = mysqli_stmt_get_result($getClassrooms);
                                                                    if (mysqli_num_rows($getClassroomsResults) > 0) // classrooms found
                                                                    {
                                                                        // for each classroom, create a dropdown selection option
                                                                        while ($classroom = mysqli_fetch_array($getClassroomsResults))
                                                                        {
                                                                            // store classroom details locally
                                                                            $classroom_id = $classroom["id"];
                                                                            $classroom_name = $classroom["name"];

                                                                            // create the option
                                                                            echo "<option value='".$classroom_id."'>".$classroom_name."</option>";
                                                                        }
                                                                    }
                                                                }
                                                            ?>
                                                        </select>
                                                    </div>
                                                </div>
                                            <?php } else { ?>
                                                <input type="hidden" class="form-control w-100" id="transfer-classroom" name="transfer-classroom" value="" readonly disabled>
                                            <?php } ?>

                                            <?php if ($uosEnabled === false && $daysEnabled === true) { ?>
                                                <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                    <!-- Membership Days -->
                                                    <div class="form-group col-11">
                                                        <label for="transfer-days">Membership Days:</label>
                                                        <input type="number" class="form-control w-100" id="transfer-days" name="transfer-days" min="0" max="365" value="180" required>
                                                    </div>
                                                </div>
                                            <?php } else { ?>
                                                <input type="hidden" class="form-control w-100" id="transfer-days" name="transfer-uos" value="0" aria-hidden="true" required disabled>
                                            <?php } ?>

                                            <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="tranfer-IEP_status" <?php if (isset($REQUEST["iep_completed"]) && $REQUEST["iep_completed"] == 1) { echo "checked"; } ?>>
                                                    <label class="form-check-label" for="tranfer-IEP_status">Was the IEP completed prior to transfer?</label>
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
                                            <button type="button" class="btn btn-primary" onclick="transferCaseload(<?php echo $case_id; ?>);"><i class="fa-solid fa-right-left"></i> Transfer Student</button>
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> Close</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- End Transfer Caseload Modal -->
                        <?php
                    }
                }
            }
        }

        // disconnect from the database
        mysqli_close($conn);
    }
?>