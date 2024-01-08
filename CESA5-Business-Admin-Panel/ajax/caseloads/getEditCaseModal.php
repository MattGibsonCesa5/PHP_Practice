<?php
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get additional required files
        include("../../includes/functions.php");
        include("../../includes/config.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "EDIT_CASELOADS"))
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

            if (verifyCase($conn, $case_id))
            {
                // get the student's current data
                $getCase = mysqli_prepare($conn, "SELECT * FROM cases WHERE id=?");
                mysqli_stmt_bind_param($getCase, "i", $case_id);
                if (mysqli_stmt_execute($getCase))
                {
                    $getCaseResult = mysqli_stmt_get_result($getCase);
                    if (mysqli_num_rows($getCaseResult) > 0)
                    {
                        // store caseload details locally
                        $case = mysqli_fetch_array($getCaseResult);
                        $case_id = $case["id"];
                        $caseload_id = $case["caseload_id"];
                        $period_id = $case["period_id"];
                        $student_id = $case["student_id"];
                        if (isset($case["assistant_id"])) { $assistant_id = $case["assistant_id"]; } else { $assistant_id = -1; }
                        if (isset($case["start_date"])) { $start_date = date("m/d/Y", strtotime($case["start_date"])); } else { $start_date = $active_start_date; }
                        if (isset($case["end_date"])) { $end_date = date("m/d/Y",  strtotime($case["end_date"])); } else { $end_date = $active_end_date; }
                        $grade_level = $case["grade_level"];
                        $evaluation_method = $case["evaluation_method"];
                        $enrollment_type = $case["enrollment_type"];
                        $educational_plan = $case["educational_plan"];
                        $residency = $case["residency"];
                        $district = $case["district_attending"];
                        $school_id = $case["school_attending"];
                        $bill_to = $case["bill_to"];
                        $billing_type = $case["billing_type"];
                        $billing_notes = $case["billing_notes"];
                        $frequency = $case["frequency"];
                        $units = $case["estimated_uos"];
                        $extra_ieps = $case["extra_ieps"];
                        $extra_evals = $case["extra_evaluations"];
                        $temp_fname = $case["temp_fname"];
                        $temp_lname = $case["temp_lname"];
                        $active = $case["active"];
                        $medicaid_billed = $case["medicaid_billed"];
                        $eval_month = $case["medicaid_evaluation_month"];
                        $membership_days = $case["membership_days"];
                        $classroom_id = $case["classroom_id"];
                        $eval_only_reason = $case["dismissal_reasoning_id"];

                        // get caseload settings
                        $isClassroom = isCaseloadClassroom($conn, $caseload_id);
                        $frequencyEnabled = isCaseloadFrequencyEnabled($conn, $caseload_id);
                        $uosEnabled = isCaseloadUOSEnabled($conn, $caseload_id);
                        $uosRequired = isCaseloadUOSRequired($conn, $caseload_id);
                        $extraIEPsEnabled = isCaseloadExtraIEPSEnabled($conn, $caseload_id);
                        $extraEvalsEnabled = isCaseloadExtraEvalsEnabled($conn, $caseload_id);
                        $allowAssistants = isCaseloadAssistantsEnabled($conn, $caseload_id);
                        $medicaid = isCaseloadMedicaid($conn, $caseload_id);
                        $daysEnabled = isCaseloadDaysEnabled($conn, $caseload_id);

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

                        // get the name of the caseload
                        $caseload_name = getCaseloadDisplayName($conn, $caseload_id);

                        // get caseload category ID
                        $category_id = getCaseloadCategory($conn, $caseload_id);

                        ?>
                            <!-- Edit Case Modal -->
                            <div class="modal fade" tabindex="-1" role="dialog" id="editCaseModal" data-bs-backdrop="static" aria-labelledby="editCaseModalLabel" aria-hidden="true">
                                <div class="modal-dialog" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header primary-modal-header">
                                            <h5 class="modal-title primary-modal-title" id="editCaseModalLabel">Edit Case</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>

                                        <div class="modal-body">
                                            <form id="edit-student-form" class="needs-validation" novalidate>
                                                <?php if (!isset($student_id) || $student_id == null) { ?>
                                                    <!-- Student -->
                                                    <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                        <!-- First Name -->
                                                        <div class="form-group col-5">
                                                            <label for="edit-fname"><span class="required-field">*</span> First Name:</label>
                                                            <input type="text" class="form-control w-100" id="edit-fname" name="edit-fname" autocomplete="off" value="<?php echo $temp_fname; ?>" required>
                                                        </div>

                                                        <!-- Divider -->
                                                        <div class="form-group col-1 p-0"></div>

                                                        <!-- Last Name -->
                                                        <div class="form-group col-5">
                                                            <label for="edit-lname"><span class="required-field">*</span> Last Name:</label>
                                                            <input type="text" class="form-control w-100" id="edit-lname" name="edit-lname" autocomplete="off" value="<?php echo $temp_lname; ?>" required>
                                                        </div>
                                                    </div>
                                                    <div class="form-row d-flex justify-content-center align-items-center mt-3">
                                                        <!-- Date Of Birth -->
                                                        <div class="form-group col-5">
                                                            <label for="edit-date_of_birth"><span class="required-field">*</span> Date Of Birth:</label>
                                                            <input type="text" class="form-control w-100" id="edit-date_of_birth" name="edit-date_of_birth" onchange="updateAge(this.value, 'edit');" autocomplete="off" aria-describedby="dobHelpBlock">
                                                        </div>
                                                        
                                                        <!-- Divider -->
                                                        <div class="form-group col-1 p-0"></div>
                                                        
                                                        <!-- Age -->
                                                        <div class="form-group col-5">
                                                            <label for="edit-age">Age:</label>
                                                            <input type="number" class="form-control w-100" id="edit-age" name="edit-age" value="0" disabled readonly>
                                                        </div>
                                                    </div>
                                                    <?php /*
                                                    <div id="dobHelpBlock" class="form-text px-3">
                                                        Date of birth is only required if the evaluation method is set to a method other than "Pending Evaluation".
                                                    </div>
                                                    */ ?>
                                                <?php } else { 
                                                    // get student details
                                                    $student_fname = $student_lname = $student_dob = $student_age = "";
                                                    $getDetails = mysqli_prepare($conn, "SELECT fname, lname, date_of_birth FROM caseload_students WHERE id=?");
                                                    mysqli_stmt_bind_param($getDetails, "i", $student_id);
                                                    if (mysqli_stmt_execute($getDetails))
                                                    {
                                                        $getDetailsResults = mysqli_stmt_get_result($getDetails);
                                                        if (mysqli_num_rows($getDetailsResults) > 0) // student exists
                                                        {
                                                            // store student values locally
                                                            $student_details = mysqli_fetch_array($getDetailsResults);
                                                            $student_fname = $student_details["fname"];
                                                            $student_lname = $student_details["lname"];
                                                            if (isset($student_details["date_of_birth"]) && $student_details["date_of_birth"] != null) { $student_dob = date("m/d/Y", strtotime($student_details["date_of_birth"])); }
                                                            $student_age = getAge($student_dob);
                                                        }
                                                    }
                                                    
                                                    ?>
                                                    <!-- Student -->
                                                    <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                        <!-- First Name -->
                                                        <div class="form-group col-5">
                                                            <label for="edit-fname">First Name:</label>
                                                            <input type="text" class="form-control w-100" id="edit-fname" name="edit-fname" autocomplete="off" value="<?php echo $student_fname; ?>" disabled readonly>
                                                        </div>

                                                        <!-- Divider -->
                                                        <div class="form-group col-1 p-0"></div>

                                                        <!-- Last Name -->
                                                        <div class="form-group col-5">
                                                            <label for="edit-lname">Last Name:</label>
                                                            <input type="text" class="form-control w-100" id="edit-lname" name="edit-lname" autocomplete="off" value="<?php echo $student_lname; ?>"  disabled readonly>
                                                        </div>
                                                    </div>
                                                    <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                        <!-- Date Of Birth -->
                                                        <div class="form-group col-5">
                                                            <label for="edit-date_of_birth">Date Of Birth:</label>
                                                            <input type="text" class="form-control w-100" id="edit-date_of_birth" name="edit-date_of_birth" autocomplete="off" value="<?php echo $student_dob; ?>" disabled readonly>
                                                        </div>
                                                        
                                                        <!-- Divider -->
                                                        <div class="form-group col-1 p-0"></div>
                                                        
                                                        <!-- Age -->
                                                        <div class="form-group col-5">
                                                            <label for="edit-age">Age:</label>
                                                            <input type="number" class="form-control w-100" id="edit-age" name="edit-age" value="<?php echo $student_age; ?>" disabled readonly>
                                                        </div>
                                                    </div>
                                                <?php } ?>
                                            </form>

                                            <form id="edit-case_details-form" class="needs-validation" novalidate>
                                                <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                    <!-- Evaluation Method -->
                                                    <div class="form-group col-11">
                                                        <label for="edit-evaluation_method"><span class="required-field">*</span> Evaluation Method:</label>
                                                        <select class="form-select w-100" id="edit-evaluation_method" name="edit-evaluation_method" onchange="checkEvaluationMethod(this.value, 'edit');" required>
                                                            <?php /* <option value="0" <?php if ($evaluation_method == 0) { echo "selected"; } ?>>Pending Evaluation</option> */ ?>
                                                            <option value="2" <?php if ($evaluation_method == 2) { echo "selected"; } ?>>Evaluation Only</option>
                                                            <option value="1" <?php if ($evaluation_method == 1) { echo "selected"; } ?>>Regular</option>
                                                        </select>
                                                    </div>
                                                </div>

                                                <div id="edit-caseload_details" class="<?php if ($evaluation_method == 0) { echo "d-none"; } ?>">
                                                    <div class="form-row <?php if ($evaluation_method == 1) { echo "d-flex"; } else { echo "d-none"; } ?> justify-content-center align-items-center my-3" id="edit-caseload_details-regular">
                                                        <!-- Start Date -->
                                                        <div class="form-group col-5">
                                                            <label for="edit-start_date"><span class="required-field">*</span> Start Date:</label>
                                                            <div class="input-group h-auto">
                                                                <div class="input-group-prepend">
                                                                    <span class="input-group-text h-100" id="nav-search-icon"><i class="fa-solid fa-calendar-day"></i></span>
                                                                </div>
                                                                <input type="text" class="form-control" id="edit-start_date" name="edit-start_date" value="<?php echo $start_date; ?>" required>
                                                            </div>
                                                        </div>

                                                        <!-- Divider -->
                                                        <div class="form-group col-1 p-0"></div>
                                                        
                                                        <!-- End Date -->
                                                        <div class="form-group col-5" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-html="true" title="To change the end date, you must either <b>dismiss</b> or <b>transfer</b> the student.">
                                                            <label for="edit-end_date">End Date:</label>
                                                            <div class="input-group h-auto">
                                                                <div class="input-group-prepend">
                                                                    <span class="input-group-text h-100" id="nav-search-icon"><i class="fa-solid fa-calendar-day"></i></span>
                                                                </div>
                                                                <input type="text" class="form-control" id="edit-end_date" name="edit-end_date" value="<?php echo $end_date; ?>" disabled readonly>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="form-row <?php if ($evaluation_method == 2) { echo "d-flex"; } else { echo "d-none"; } ?> flex-column justify-content-center align-items-center my-3" id="edit-caseload_details-evaluation_only">
                                                        <!-- Evaluation Date -->
                                                        <div class="form-group col-11">
                                                            <label for="edit-eval_date"><span class="required-field">*</span> Evaluation Meeting Date:</label>
                                                            <div class="input-group h-auto">
                                                                <div class="input-group-prepend">
                                                                    <span class="input-group-text h-100" id="nav-search-icon"><i class="fa-solid fa-calendar-day"></i></span>
                                                                </div>
                                                                <input type="text" class="form-control" id="edit-eval_date" name="edit-eval_date" value="<?php echo $start_date; ?>" required>
                                                            </div>
                                                        </div>

                                                        <?php if ($medicaid === true) { ?>
                                                            <div class="form-group col-11 mt-3">
                                                                <label for="edit-medicaid_billing"><span class="required-field">*</span> Medicaid Billing Completed?</label>
                                                                <select class="form-select w-100" id="edit-medicaid_billing" name="edit-medicaid_billing" required>
                                                                    <option value="-1"></option>
                                                                    <option value="0" <?php if ($medicaid_billed == 0) { echo "selected"; } ?>>N/A</option>
                                                                    <option value="1" <?php if ($medicaid_billed == 1) { echo "selected"; } ?>>Yes</option>
                                                                </select>
                                                            </div>
                                                        <?php } else { ?>
                                                            <div class="d-none" id="edit-eval_month-div" style="height: 0px; visibility: hidden; display: none !important;">
                                                                <input class="d-none" type="hidden" id="edit-medicaid_billing" name="edit-medicaid_billing" value="0" aria-hidden="true" readonly disabled>
                                                            </div>
                                                        <?php } ?>
                                                    </div>

                                                    <?php if ($medicaid === true) { ?>
                                                        <div class="form-row d-flex justify-content-center align-items-center my-3" id="edit-eval_month-div">
                                                            <!-- Evaluation Month (Medicaid) -->
                                                            <div class="form-group col-11">
                                                                <label for="edit-eval_month"><span class="required-field">*</span> Month Evaluation Started (Medicaid):</label>
                                                                <div class="input-group h-auto">
                                                                    <div class="input-group-prepend">
                                                                        <span class="input-group-text h-100" id="nav-search-icon"><i class="fa-solid fa-calendar-days"></i></span>
                                                                    </div>
                                                                    <select class="form-select" id="edit-eval_month" name="edit-eval_month" required>
                                                                        <option value="0" <?php if ($eval_month == 0) { echo "selected"; } ?>>N/A</option>
                                                                        <option value="1" <?php if ($eval_month == 1) { echo "selected"; } ?>>January</option>
                                                                        <option value="2" <?php if ($eval_month == 2) { echo "selected"; } ?>>February</option>
                                                                        <option value="3" <?php if ($eval_month == 3) { echo "selected"; } ?>>March</option>
                                                                        <option value="4" <?php if ($eval_month == 4) { echo "selected"; } ?>>April</option>
                                                                        <option value="5" <?php if ($eval_month == 5) { echo "selected"; } ?>>May</option>
                                                                        <option value="6" <?php if ($eval_month == 6) { echo "selected"; } ?>>June</option>
                                                                        <option value="7" <?php if ($eval_month == 7) { echo "selected"; } ?>>July</option>
                                                                        <option value="8" <?php if ($eval_month == 8) { echo "selected"; } ?>>August</option>
                                                                        <option value="9" <?php if ($eval_month == 9) { echo "selected"; } ?>>September</option>
                                                                        <option value="10" <?php if ($eval_month == 10) { echo "selected"; } ?>>October</option>
                                                                        <option value="11" <?php if ($eval_month == 11) { echo "selected"; } ?>>November</option>
                                                                        <option value="12" <?php if ($eval_month == 12) { echo "selected"; } ?>>December</option>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    <?php } else { ?>
                                                        <div class="d-none">
                                                            <input class="d-none" type="hidden" id="edit-eval_month" name="edit-eval_month" value="0" aria-hidden="true">
                                                        </div>
                                                    <?php } ?>

                                                    <div class="form-row <?php if ($evaluation_method == 2) { echo "d-flex"; } else { echo "d-none"; } ?> justify-content-center align-items-center my-3" id="edit-eval_only_reasoning-div">
                                                        <!-- Eval Only Reasoning -->
                                                        <div class="form-group col-11">
                                                            <label for="edit-eval_only-reason"><span class="required-field">*</span> Evaluation Only Reasoning:</label>
                                                            <select class="form-select w-100" id="edit-eval_only-reason" name="edit-eval_only-reason" required>
                                                                <option value="0" <?php if ($evaluation_method == 1) { echo "selected"; } ?>>N/A</option>
                                                                <?php
                                                                    $getReasons = mysqli_query($conn, "SELECT id, reason FROM caseload_dismissal_reasonings WHERE dnq=1");
                                                                    if (mysqli_num_rows($getReasons) > 0)
                                                                    {
                                                                        while ($reason = mysqli_fetch_array($getReasons))
                                                                        {
                                                                            if ($eval_only_reason == $reason["id"] && $evaluation_method == 2)
                                                                            {
                                                                                echo "<option value='".$reason["id"]."' selected>".$reason["reason"]."</option>";
                                                                            }
                                                                            else
                                                                            {
                                                                                echo "<option value='".$reason["id"]."'>".$reason["reason"]."</option>";
                                                                            }
                                                                        }
                                                                    }
                                                                ?>
                                                            </select>
                                                        </div>
                                                    </div>

                                                    <?php if ($allowAssistants === true) { ?>
                                                        <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                            <!-- Assistants -->
                                                            <div class="form-group col-10">
                                                                <label for="edit-assistant_id">Assistant:</label>
                                                                <select id="edit-assistant_id" name="edit-assistant_id" placeholder="Please select an assistant..." required>
                                                                    <option value="-1" style="font-style: italic;">None</option>
                                                                    <?php
                                                                        $getAssistants = mysqli_prepare($conn, "SELECT a.*, e.fname, e.lname FROM caseload_assistants a 
                                                                                                            JOIN employees e ON a.employee_id=e.id
                                                                                                            LEFT JOIN employee_compensation ec ON e.id=ec.employee_id
                                                                                                            WHERE ec.period_id=? AND ec.active=1 AND a.category_id=?");
                                                                        mysqli_stmt_bind_param($getAssistants, "ii", $period_id, $category_id);
                                                                        if (mysqli_stmt_execute($getAssistants))
                                                                        {
                                                                            $getAssistantsResults = mysqli_stmt_get_result($getAssistants);
                                                                            if (mysqli_num_rows($getAssistantsResults) > 0) // assistants found
                                                                            {
                                                                                while ($assistant = mysqli_fetch_array($getAssistantsResults))
                                                                                {
                                                                                    // store assistant details locally
                                                                                    $DB_assistant_id = $assistant["id"];
                                                                                    $assistant_fname = $assistant["fname"];
                                                                                    $assistant_lname = $assistant["lname"];

                                                                                    // create the option
                                                                                    if ($assistant_id == $DB_assistant_id) { echo "<option value='".$DB_assistant_id."' selected>".$assistant_lname.", ".$assistant_fname."</option>"; } 
                                                                                    else { echo "<option value='".$DB_assistant_id."'>".$assistant_lname.", ".$assistant_fname."</option>"; }
                                                                                }
                                                                            }
                                                                        }
                                                                    ?>
                                                                </select>
                                                            </div>
                                                            <div class="form-group col-1 p-0">
                                                                <label for="edit-clear_assistant"></label>
                                                                <button class="btn btn-secondary" id="edit-clear_assistant" onclick="clearAssistantSelected('edit');">
                                                                    <i class="fa-solid fa-xmark"></i>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    <?php } else { ?>
                                                        <div class="d-none">
                                                            <input class="d-none" type="hidden" id="edit-assistant_id" name="edit-assistant_id" value="-1" aria-hidden="true">
                                                        </div>
                                                    <?php } ?>

                                                    <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                        <!-- Residency -->
                                                        <div class="form-group col-11">
                                                            <label for="edit-residency"><span class="required-field">*</span> Residency:</label>
                                                            <div class="input-group h-auto">
                                                                <div class="input-group-prepend">
                                                                    <span class="input-group-text h-100" id="nav-search-icon"><i class="fa-solid fa-house"></i></span>
                                                                </div>
                                                                <select class="flex-grow-1" id="edit-residency" name="edit-residency" required>
                                                                    <option value disabled></option>
                                                                    <?php
                                                                        $getDistricts = mysqli_query($conn, "SELECT id, name FROM customers WHERE active=1 ORDER BY name ASC");
                                                                        if (mysqli_num_rows($getDistricts) > 0) // districts (customers) found; continue
                                                                        {
                                                                            while ($district_details = mysqli_fetch_array($getDistricts))
                                                                            {
                                                                                // store district details locally
                                                                                $district_id = $district_details["id"];
                                                                                $district_name = $district_details["name"];

                                                                                // create the selection option
                                                                                if ($district_id == $residency) { echo "<option value='".$district_id."' selected>".$district_name."</option>"; }
                                                                                else { echo "<option value='".$district_id."'>".$district_name."</option>"; }
                                                                            }
                                                                        }
                                                                    ?>
                                                                </select>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                        <!-- District -->
                                                        <div class="form-group col-11">
                                                            <label for="edit-district"><span class="required-field">*</span> District Attending:</label>
                                                            <div class="input-group h-auto">
                                                                <div class="input-group-prepend">
                                                                    <span class="input-group-text h-100" id="nav-search-icon"><i class="fa-solid fa-building-flag"></i></span>
                                                                </div>
                                                                <select class="flex-grow-1" id="edit-district" name="edit-district" required onchange="getSchoolsForDistrict(this.value, 'edit');">
                                                                    <option value disabled></option>
                                                                    <?php
                                                                        $getDistricts = mysqli_query($conn, "SELECT id, name FROM customers WHERE active=1 ORDER BY name ASC");
                                                                        if (mysqli_num_rows($getDistricts) > 0) // districts (customers) found; continue
                                                                        {
                                                                            while ($district_details = mysqli_fetch_array($getDistricts))
                                                                            {
                                                                                // store district details locally
                                                                                $district_id = $district_details["id"];
                                                                                $district_name = $district_details["name"];

                                                                                // create the selection option
                                                                                if ($district_id == $district) { echo "<option value='".$district_id."' selected>".$district_name."</option>"; }
                                                                                else { echo "<option value='".$district_id."'>".$district_name."</option>"; }
                                                                            }
                                                                        }
                                                                    ?>
                                                                </select>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                        <!-- School -->
                                                        <div class="form-group col-11">
                                                            <label for="edit-school"><span class="required-field">*</span> School Attending:</label>
                                                            <div class="input-group h-auto">
                                                                <div class="input-group-prepend">
                                                                    <span class="input-group-text h-100" id="nav-search-icon"><i class="fa-solid fa-school"></i></span>
                                                                </div>
                                                                <select class="form-select" id="edit-school" name="edit-school" required>
                                                                    <option value disabled></option>
                                                                    <optgroup label='District Schools'>
                                                                        <?php
                                                                            // get schools for the district
                                                                            $getSchools = mysqli_prepare($conn, "SELECT id, name FROM schools WHERE district_id=? ORDER BY name ASC");
                                                                            mysqli_stmt_bind_param($getSchools, "i", $district);
                                                                            if (mysqli_stmt_execute($getSchools))
                                                                            {
                                                                                $getSchoolsResults = mysqli_stmt_get_result($getSchools);
                                                                                if (mysqli_num_rows($getSchoolsResults) > 0) // schools found for district
                                                                                {
                                                                                    while ($school = mysqli_fetch_array($getSchoolsResults))
                                                                                    {
                                                                                        // store school details locally
                                                                                        $loop_school_id = $school["id"];
                                                                                        $school_name = $school["name"];

                                                                                        // add the school to the dropdown
                                                                                        if ($loop_school_id == $school_id) { echo "<option value='".$loop_school_id."' selected>".$school_name."</option>"; }
                                                                                        else { echo "<option value='".$loop_school_id."'>".$school_name."</option>"; }
                                                                                    }
                                                                                }
                                                                            }
                                                                        ?>
                                                                    </optgroup>

                                                                    <?php
                                                                        // get CESA 5 schools only if CESA 5 was not the selected district
                                                                        if ($district != 0) 
                                                                        {
                                                                            echo "<optgroup label='CESA 5 Programs'>";
                                                                                $getCESASchools = mysqli_query($conn, "SELECT id, name FROM schools WHERE district_id=0 ORDER BY name ASC");
                                                                                if (mysqli_num_rows($getCESASchools) > 0)
                                                                                {
                                                                                    while ($school = mysqli_fetch_array($getCESASchools))
                                                                                    {
                                                                                        // store school details locally
                                                                                        $loop_school_id = $school["id"];
                                                                                        $school_name = $school["name"];

                                                                                        // add the school to the dropdown
                                                                                        if ($loop_school_id == $school_id) { echo "<option value='".$loop_school_id."' selected>".$school_name."</option>"; }
                                                                                        else { echo "<option value='".$loop_school_id."'>".$school_name."</option>"; }
                                                                                    }
                                                                                }
                                                                            echo "</optgroup>";
                                                                        }
                                                                    ?>

                                                                    <optgroup label='Other'>
                                                                        <option value='-1' <?php if ($school_id == -1) { echo "selected"; } ?>>Other</option>
                                                                        <option value='-2' <?php if ($school_id == -2) { echo "selected"; } ?>>External Tutor</option>
                                                                        <option value='-3' <?php if ($school_id == -3) { echo "selected"; } ?>>Home</option>
                                                                    </optgroup>
                                                                </select>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <?php if ($isClassroom === true) { ?>
                                                        <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                            <!-- Classroom -->
                                                            <div class="form-group col-11">
                                                                <label for="edit-classroom"><span class="required-field">*</span> Classroom:</label>
                                                                <div class="input-group h-auto">
                                                                    <div class="input-group-prepend">
                                                                        <span class="input-group-text h-100" id="nav-search-icon"><i class="fa-solid fa-chalkboard"></i></span>
                                                                    </div>
                                                                    <select class="form-select" id="edit-classroom" name="edit-classroom" required>
                                                                        <option></option>
                                                                        <?php
                                                                            $getClassrooms = mysqli_prepare($conn, "SELECT id, name FROM caseload_classrooms WHERE category_id=?");
                                                                            mysqli_stmt_bind_param($getClassrooms, "i", $category_id);
                                                                            if (mysqli_stmt_execute($getClassrooms))
                                                                            {
                                                                                $getClassroomsResults = mysqli_stmt_get_result($getClassrooms);
                                                                                if (mysqli_num_rows($getClassroomsResults) > 0) // classrooms found
                                                                                {
                                                                                    // for each classroom, create a dropdown selection option
                                                                                    while ($classroom = mysqli_fetch_array($getClassroomsResults))
                                                                                    {
                                                                                        // store classroom details locally
                                                                                        $db_classroom_id = $classroom["id"];
                                                                                        $classroom_name = $classroom["name"];

                                                                                        // create the option
                                                                                        if ($db_classroom_id == $classroom_id) { echo "<option value='".$db_classroom_id."' selected>".$classroom_name."</option>"; }
                                                                                        else { echo "<option value='".$db_classroom_id."'>".$classroom_name."</option>"; } 
                                                                                    }
                                                                                }
                                                                            }
                                                                        ?>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    <?php } else { ?>
                                                        <input type="hidden" class="form-control w-100" id="edit-classroom" name="edit-classroom" value="" aria-hidden="true" readonly disabled>
                                                    <?php } ?>

                                                    <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                        <!-- Grade Level -->
                                                        <div class="form-group col-11">
                                                            <label for="edit-grade_level"><span class="required-field">*</span> Current Grade Level:</label>
                                                            <div class="input-group h-auto">
                                                                <div class="input-group-prepend">
                                                                    <span class="input-group-text h-100" id="nav-search-icon"><i class="fa-solid fa-graduation-cap"></i></span>
                                                                </div>
                                                                <select class="form-select" id="edit-grade_level" name="edit-grade_level" required>
                                                                    <option value="0" <?php if ($grade_level == 0) { echo "selected"; } ?>>Kindergarten</option>
                                                                    <option value="1" <?php if ($grade_level == 1) { echo "selected"; } ?>>1st Grade</option>
                                                                    <option value="2" <?php if ($grade_level == 2) { echo "selected"; } ?>>2nd Grade</option>
                                                                    <option value="3" <?php if ($grade_level == 3) { echo "selected"; } ?>>3rd Grade</option>
                                                                    <option value="4" <?php if ($grade_level == 4) { echo "selected"; } ?>>4th Grade</option>
                                                                    <option value="5" <?php if ($grade_level == 5) { echo "selected"; } ?>>5th Grade</option>
                                                                    <option value="6" <?php if ($grade_level == 6) { echo "selected"; } ?>>6th Grade</option>
                                                                    <option value="7" <?php if ($grade_level == 7) { echo "selected"; } ?>>7th Grade</option>
                                                                    <option value="8" <?php if ($grade_level == 8) { echo "selected"; } ?>>8th Grade</option>
                                                                    <option value="9" <?php if ($grade_level == 9) { echo "selected"; } ?>>9th Grade</option>
                                                                    <option value="10" <?php if ($grade_level == 10) { echo "selected"; } ?>>10th Grade</option>
                                                                    <option value="11" <?php if ($grade_level == 11) { echo "selected"; } ?>>11th Grade</option>
                                                                    <option value="12" <?php if ($grade_level == 12) { echo "selected"; } ?>>12th Grade</option>
                                                                    <option value="13" <?php if ($grade_level == 13) { echo "selected"; } ?>>Post 12th Grade</option>
                                                                    <option value="-1" <?php if ($grade_level == -1) { echo "selected"; } ?>>Pre-Kindergarten</option>
                                                                    <option value="-2" <?php if ($grade_level == -2) { echo "selected"; } ?>>4-year-old Kindergarten</option>
                                                                    <option value="-3" <?php if ($grade_level == -3) { echo "selected"; } ?>>3-year-old Kindergarten</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                        <!-- Enrollment Type -->
                                                        <div class="form-group col-5">
                                                            <label for="edit-enrollment_type"><span class="required-field">*</span> Enrollment Type:</label>
                                                            <select class="form-select w-100" id="edit-enrollment_type" name="edit-enrollment_type" required>
                                                                <option value disabled></option>
                                                                <option value="1" <?php if ($enrollment_type == 1) { echo "selected"; } ?>>Resident</option>
                                                                <option value="2" <?php if ($enrollment_type == 2) { echo "selected"; } ?>>Open Enrollment</option>
                                                                <option value="3" <?php if ($enrollment_type == 3) { echo "selected"; } ?>>Placed</option>
                                                                <option value="4" <?php if ($enrollment_type == 4) { echo "selected"; } ?>>66.0301</option>
                                                                <option value="5" <?php if ($enrollment_type == 5) { echo "selected"; } ?>>Other</option>
                                                            </select>
                                                        </div>

                                                        <!-- Divider -->
                                                        <div class="form-group col-1 p-0"></div>

                                                        <!-- Educational Plan -->
                                                        <div class="form-group col-5">
                                                            <label for="edit-educational_plan"><span class="required-field">*</span> Educational Plan:</label>
                                                            <select class="form-select w-100" id="edit-educational_plan" name="edit-educational_plan" required>
                                                                <option value disabled></option>
                                                                <option value="1" <?php if ($educational_plan == 1) { echo "selected"; } ?>>504</option>
                                                                <option value="2" <?php if ($educational_plan == 2) { echo "selected"; } ?>>IEP</option>
                                                                <option value="3" <?php if ($educational_plan == 3) { echo "selected"; } ?>>ISP</option>
                                                                <option value="4" <?php if ($educational_plan == 4) { echo "selected"; } ?>>Other</option>
                                                            </select>
                                                        </div>
                                                    </div>

                                                    <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                        <!-- Bill To -->
                                                        <div class="form-group col-5">
                                                            <label for="edit-bill_to"><span class="required-field">*</span> Bill To:</label>
                                                            <select class="form-select w-100" id="edit-bill_to" name="edit-bill_to" required>
                                                                <option value disabled></option>
                                                                <option value="1" <?php if ($bill_to == 1) { echo "selected"; } ?>>Residency (R)</option>
                                                                <option value="2" <?php if ($bill_to == 2) { echo "selected"; } ?>>Attending (A)</option>
                                                                <option value="3" <?php if ($bill_to == 3) { echo "selected"; } ?>>Other</option>
                                                            </select>
                                                        </div>

                                                        <!-- Divider -->
                                                        <div class="form-group col-1 p-0"></div>

                                                        <!-- Billing Type -->
                                                        <div class="form-group col-5">
                                                            <label for="edit-billing_type"><span class="required-field">*</span> Billing Type:</label>
                                                            <select class="form-select w-100" id="edit-billing_type" name="edit-billing_type" onchange="checkBillingType(this.value, 'edit');" required>
                                                                <?php if ($uosEnabled === true) { ?><option value="1" <?php if ($billing_type == 1) { echo "selected"; } ?>>Bill UOS</option><?php } ?>
                                                                <?php if ($daysEnabled === true) { ?><option value="2" <?php if ($billing_type == 2) { echo "selected"; } ?>>Membership Days</option><?php } ?>
                                                            </select>
                                                        </div>
                                                    </div>

                                                    <div class="<?php if ($evaluation_method == 1) { } else { echo "d-none"; } ?>" id="edit-caseload_details-regular-extra">
                                                        <?php if ($frequencyEnabled === true && $uosEnabled === true) { ?>
                                                            <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                                <!-- Frequency -->
                                                                <div class="form-group col-6">
                                                                    <label for="edit-frequency"><span class="required-field">*</span> Starting Frequency:</label>
                                                                    <input type="text" class="form-control w-100" id="edit-frequency" name="edit-frequency" value="<?php if ($evaluation_method == 1) { echo $frequency; } else { echo "N/A"; } ?>" required>
                                                                </div>

                                                                <!-- Divider -->
                                                                <div class="form-group col-1 p-0"></div>

                                                                <!-- Units Of Service -->
                                                                <div class="form-group col-3">
                                                                    <label for="edit-uos">
                                                                        <?php if ($uosRequired === true) { ?>
                                                                            <span class="required-field">*</span> UOS:
                                                                        <?php } else { ?> 
                                                                            UOS:
                                                                        <?php } ?>
                                                                    </label>
                                                                    <input type="number" min="0" class="form-control w-100" id="edit-uos" name="edit-uos" value="<?php echo $units; ?>" <?php if ($uosRequired === true) { echo "required"; } ?>>
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
                                                                    <label for="edit-frequency"><span class="required-field">*</span> Starting Frequency:</label>
                                                                    <input type="text" class="form-control w-100" id="edit-frequency" name="edit-frequency" value="<?php if ($evaluation_method == 1) { echo $frequency; } else { echo "N/A"; } ?>" required>
                                                                    <input type="hidden" class="form-control w-100" id="edit-uos" name="edit-uos" value="0" aria-hidden="true" readonly disabled>
                                                                </div>
                                                            </div>
                                                        <?php } else if ($frequencyEnabled === false && $uosEnabled === true) { ?>
                                                            <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                                <!-- Units Of Service -->
                                                                <div class="form-group col-9">
                                                                    <label for="edit-uos">
                                                                        <?php if ($uosRequired === true) { ?>
                                                                            <span class="required-field">*</span> UOS:
                                                                        <?php } else { ?> 
                                                                            UOS:
                                                                        <?php } ?>
                                                                    </label>
                                                                    <input type="text" class="form-control w-100" id="edit-uos" name="edit-uos" value="<?php echo $units; ?>" <?php if ($uosRequired === true) { echo "required"; } ?>>
                                                                    <input type="hidden" class="form-control w-100" id="edit-frequency" name="edit-frequency" value="N/A" aria-hidden="true" readonly disabled>
                                                                </div>

                                                                <!-- Divider -->
                                                                <div class="form-group col-1 p-0"></div>

                                                                <div class="form-group col-1">
                                                                    <label></label> <!-- spacer -->
                                                                    <a class="btn btn-secondary" target="popup" onclick="window.open('uos_calculator_mini.php', 'UOS Calculator', 'width=768, height=700');" title="Open the UOS Calculator in a new tab!"><i class="fa-solid fa-calculator"></i></a>
                                                                </div>
                                                            </div>
                                                        <?php } else { ?>
                                                            <input type="hidden" class="form-control w-100" id="edit-frequency" name="edit-frequency" value="N/A" aria-hidden="true" readonly disabled>
                                                            <input type="hidden" class="form-control w-100" id="edit-uos" name="edit-uos" value="0" aria-hidden="true" readonly disabled>
                                                        <?php } ?>

                                                        <?php if ($extraIEPsEnabled === true && $extraEvalsEnabled === true) { ?>
                                                            <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                                <!-- Extra IEPs -->
                                                                <div class="form-group col-5" data-bs-toggle="tooltip" data-bs-placement="top" title="To add/edit the number of extra IEP meetings, you can click the eyeball button on your caseload sheet.">
                                                                    <label for="edit-extra_ieps"># of Extra IEPs:</label>
                                                                    <input type="number" class="form-control w-100" id="edit-extra_ieps" name="edit-extra_ieps" min="0" value="<?php echo $extra_ieps; ?>" disabled readonly>
                                                                </div>

                                                                <!-- Divider -->
                                                                <div class="form-group col-1 p-0"></div>

                                                                <!-- Extra Evals -->
                                                                <div class="form-group col-5" data-bs-toggle="tooltip" data-bs-placement="top" title="To add/edit the number of extra evaluation meetings, you can click the eyeball button on your caseload sheet.">
                                                                    <label for="edit-extra_evals"># of Extra Evaluations:</label>
                                                                    <input type="number" class="form-control w-100" id="edit-extra_evals" name="edit-extra_evals" min="0" value="<?php echo $extra_evals; ?>" disabled readonly>
                                                                </div>
                                                            </div>
                                                        <?php } else if ($extraIEPsEnabled === true && $extraEvalsEnabled === false) { ?>
                                                            <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                                <!-- Extra IEPs -->
                                                                <div class="form-group col-11" data-bs-toggle="tooltip" data-bs-placement="top" title="To add/edit the number of extra IEP meetings, you can click the eyeball button on your caseload sheet.">
                                                                    <label for="edit-extra_ieps"># of Extra IEPs:</label>
                                                                    <input type="number" class="form-control w-100" id="edit-extra_ieps" name="edit-extra_ieps" min="0" value="<?php echo $extra_ieps; ?>" disabled readonly>
                                                                    <input type="hidden" class="form-control w-100" id="edit-extra_evals" name="edit-extra_evals" value="0" aria-hidden="true" readonly disabled>
                                                                </div>
                                                            </div>
                                                        <?php } else if ($extraIEPsEnabled === false && $extraEvalsEnabled === true) { ?>
                                                            <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                                <!-- Extra Evals -->
                                                                <div class="form-group col-11" data-bs-toggle="tooltip" data-bs-placement="top" title="To add/edit the number of extra evaluation meetings, you can click the eyeball button on your caseload sheet.">
                                                                    <label for="edit-extra_evals"># of Extra Evaluations:</label>
                                                                    <input type="number" class="form-control w-100" id="edit-extra_evals" name="edit-extra_evals" min="0" value="<?php echo $extra_evals; ?>" disabled readonly>
                                                                    <input type="hidden" class="form-control w-100" id="edit-extra_ieps" name="edit-extra_ieps" value="0" aria-hidden="true" readonly disabled>
                                                                </div>
                                                            </div>
                                                        <?php } else { ?>
                                                            <input type="hidden" class="form-control w-100" id="edit-extra_evals" name="edit-extra_evals" value="0" aria-hidden="true" readonly disabled>
                                                            <input type="hidden" class="form-control w-100" id="edit-extra_ieps" name="edit-extra_ieps" value="0" aria-hidden="true" readonly disabled>
                                                        <?php } ?>
                                                    </div>
                                                </div>

                                                <div class="<?php if ($uosEnabled === false && $daysEnabled === true) { } else { echo "d-none"; } ?>" id="edit-caseload_details-regular-day_use">
                                                    <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                        <!-- Membership Days -->
                                                        <div class="form-group col-11">
                                                            <label for="edit-membership_days">Membership Days:</label>
                                                            <input type="number" class="form-control w-100" id="edit-membership_days" name="edit-membership_days" min="0" value="<?php echo $membership_days; ?>" required>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                    <!-- Billing Notes -->
                                                    <div class="form-group col-11">
                                                        <label for="edit-billing_notes">Billing Notes:</label>
                                                        <input type="text" class="form-control w-100" id="edit-billing_notes" name="edit-billing_notes" value="<?php echo $billing_notes; ?>" autocomplete="off">
                                                    </div>
                                                </div>

                                                <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                    <!-- Status -->
                                                    <div class="form-group col-11">
                                                        <label for="edit-status"><span class="required-field">*</span> Status:</label>
                                                        <?php if ($active == 1) { ?>
                                                            <button class="btn btn-success w-100" type="button" id="edit-status" value=1 onclick="updateStatus('edit-status');" aria-describedby="statusHelpBlock">Active</button>
                                                        <?php } else { ?>
                                                            <button class="btn btn-danger w-100" type="button" id="edit-status" value=0 onclick="updateStatus('edit-status');" aria-describedby="statusHelpBlock">Inactive</button>
                                                        <?php } ?>
                                                        <div id="statusHelpBlock" class="form-text">
                                                            Student status is on a per-caseload basis. If the student is pending evaluation, we'll set their status to inactive.
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Required Field Indicator -->
                                                <div class="row justify-content-center">
                                                    <div class="col-11 text-center fst-italic">
                                                        <span class="required-field">*</span> indicates a required field
                                                    </div>
                                                </div>
                                            </form>
                                        </div>

                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-primary" onclick="editCase(<?php echo $case_id; ?>);"><i class="fa-solid fa-floppy-disk"></i> Save Changes</button>
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> Close</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- End Edit Case Modal -->
                        <?php
                    }
                }
            }
        }

        // disconnect from the database
        mysqli_close($conn);
    }
?>