<?php
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1) 
    {
        // get additional required files
        include("../../includes/functions.php");
        include("../../includes/config.php");
        include("../../getSettings.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "ADD_CASELOADS"))
        {
            // get period name from POST
            if (isset($_POST["period"]) && $_POST["period"] <> "") { $period = $_POST["period"]; } else { $period = null; }
            if (isset($_POST["caseload_id"]) && $_POST["caseload_id"] <> "") { $caseload_id = $_POST["caseload_id"]; } else { $caseload_id = null; }

            // verify the period exists; if it exists, store the period ID
            if ($period != null && $period_id = getPeriodID($conn, $period))
            {
                // verify the caseload exists and the user has access to it
                if ($caseload_id != null && verifyCaseload($conn, $caseload_id))
                {
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

                    // get caseload term start and end dates
                    $term_start = "2022-09-01";
                    $term_end = "2023-06-01";
                    $getCaseloadTerm = mysqli_prepare($conn, "SELECT caseload_term_start, caseload_term_end FROM periods WHERE id=?");
                    mysqli_stmt_bind_param($getCaseloadTerm, "i", $period_id);
                    if (mysqli_stmt_execute($getCaseloadTerm))
                    {
                        $getCaseloadTermResult = mysqli_stmt_get_result($getCaseloadTerm);
                        if (mysqli_num_rows($getCaseloadTermResult) > 0)
                        {
                            $caseload_details = mysqli_fetch_array($getCaseloadTermResult);
                            $term_start = $caseload_details["caseload_term_start"];
                            $term_end = $caseload_details["caseload_term_end"];
                        }
                    }

                    // set term start and end dates in proper format
                    $term_start = date("m/d/Y", strtotime($term_start));
                    $term_end = date("m/d/Y", strtotime($term_end));

                    // get caseload category ID
                    if ($caseload_id > 0) { $category_id = getCaseloadCategory($conn, $caseload_id); }
                    else { $category_id = abs($caseload_id); }

                    ?>
                        <!-- Add Caseload Modal -->
                        <div class="modal fade" tabindex="-1" role="dialog" id="addCaseModal" data-bs-backdrop="static" aria-labelledby="addCaseModalLabel" aria-hidden="true">
                            <div class="modal-dialog" role="document">
                                <div class="modal-content">
                                    <div class="modal-header primary-modal-header">
                                        <h5 class="modal-title primary-modal-title" id="addCaseModalLabel">Add Student To Caseload</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>

                                    <div class="modal-body">
                                        <form id="add-existing_student-form" class="needs-validation" novalidate>
                                            <!-- Student -->
                                            <div class="form-row d-flex justify-content-center align-items-center mt-3">
                                                <!-- Existing Students -->
                                                <div class="form-group col-8">
                                                    <label for="add-student_id"><span class="required-field">*</span> Student:</label>
                                                    <select id="add-student_id" name="add-student_id" placeholder="Please select a student or create a new student..." onchange="getGradeLevel(this.value); getLocations(this.value);" required>
                                                        <option></option>
                                                        <?php
                                                            $getStudents = mysqli_query($conn, "SELECT s.id, s.fname, s.lname, s.date_of_birth, s.status FROM caseload_students s ORDER BY s.lname ASC, s.fname ASC");
                                                            if (mysqli_num_rows($getStudents) > 0) // students found
                                                            {
                                                                while ($student = mysqli_fetch_array($getStudents))
                                                                {
                                                                    // store therapist details locally
                                                                    $student_id = $student["id"];
                                                                    $student_fname = $student["fname"];
                                                                    $student_lname = $student["lname"];
                                                                    $student_dob = $student["date_of_birth"];
                                                                    $status = $student["status"];

                                                                    // convert date of birth
                                                                    if (isset($student_dob) && $student_dob != null) { $student_dob = date("m/d/Y", strtotime($student_dob)); } else { $student_dob = "?"; }

                                                                    // create the option
                                                                    if ($status == 1) { echo "<option value='".$student_id."'>".$student_lname.", ".$student_fname." (".$student_dob.")</option>"; }
                                                                    else { echo "<option value='".$student_id."' style='color: #ff0000; font-style: italic;'>".$student_lname.", ".$student_fname." (".$student_dob.")</option>"; }
                                                                }
                                                            }
                                                        ?>
                                                    </select>
                                                </div>
                                                <div class="form-group col-1 p-0">
                                                    <label for="add-clear_student"></label>
                                                    <button class="btn btn-secondary" id="add-clear_student" onclick="clearStudentSelected('add');" type="button"> 
                                                        <i class="fa-solid fa-xmark"></i>
                                                    </button>
                                                </div>

                                                <!-- Divider -->
                                                <div class="form-group col-1 p-0"></div>

                                                <!-- Add New Student Button -->
                                                <div class="form-group col-1 p-0">
                                                    <label for="add-student_button"></label>
                                                    <button class="btn btn-primary" id="add-student_button" value="0" onclick="showAddNewStudent('add', this.value);" type="button">
                                                        <i class="fa-solid fa-plus"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="form-row d-flex justify-content-center mt-0 mb-3">
                                                <div class="form-group col-11 p-0">
                                                    <div class="invalid-feedback" id="add-student_id-feedback">
                                                        Please select or add a new student.
                                                    </div>
                                                </div>
                                            </div>
                                        </form>

                                        <form id="add-new_student-form" class="needs-validation" novalidate>
                                            <!-- New Student Div -->
                                            <div class="d-none" id="add-new_student-div">
                                                <div class="form-text px-3">
                                                    <i class="fa-solid fa-triangle-exclamation"></i> If you create a new student, please ensure that the student dropdown above does not have a student selected.
                                                </div>
                                                <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                    <!-- First Name -->
                                                    <div class="form-group col-5">
                                                        <label for="add-fname"><span class="required-field">*</span> First Name:</label>
                                                        <input type="text" class="form-control w-100" id="add-fname" name="add-fname" autocomplete="off" required>
                                                    </div>

                                                    <!-- Divider -->
                                                    <div class="form-group col-1 p-0"></div>

                                                    <!-- Last Name -->
                                                    <div class="form-group col-5">
                                                        <label for="add-lname"><span class="required-field">*</span> Last Name:</label>
                                                        <input type="text" class="form-control w-100" id="add-lname" name="add-lname" autocomplete="off" required>
                                                    </div>
                                                </div>
                                                <div class="form-row d-flex justify-content-center align-items-center mt-3">
                                                    <!-- Date Of Birth -->
                                                    <div class="form-group col-5">
                                                        <label for="add-date_of_birth"><span class="required-field">*</span> Date Of Birth:</label>
                                                        <input type="text" class="form-control w-100" id="add-date_of_birth" name="add-date_of_birth" onchange="updateAge(this.value, 'add');" autocomplete="off" aria-describedby="dobHelpBlock" required>
                                                    </div>
                                                    
                                                    <!-- Divider -->
                                                    <div class="form-group col-1 p-0"></div>
                                                    
                                                    <!-- Age -->
                                                    <div class="form-group col-5">
                                                        <label for="add-age">Age:</label>
                                                        <input type="number" class="form-control w-100" id="add-age" name="add-age" value="0" disabled readonly>
                                                    </div>
                                                </div>
                                                <?php /*
                                                <div id="dobHelpBlock" class="form-text px-3">
                                                    Date of birth is only required if the evaluation method is set to a method other than "Pending Evaluation".
                                                </div>
                                                */ ?>
                                            </div>
                                        </form>

                                        <form id="add-case_details-form" class="needs-validation" novalidate>
                                            <input type="hidden" id="add-caseload_id" name="add-caseload_id" value="<?php echo $caseload_id; ?>">

                                            <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                <!-- Evaluation Method -->
                                                <div class="form-group col-11">
                                                    <label for="add-evaluation_method"><span class="required-field">*</span> Evaluation Method:</label>
                                                    <select class="form-select w-100" id="add-evaluation_method" name="add-evaluation_method" onchange="checkEvaluationMethod(this.value, 'add');" required>
                                                        <?php /* <option>Pending Evaluation</option> */ ?>
                                                        <option value="2">Evaluation Only</option>
                                                        <option value="1" selected>Regular</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <div id="add-caseload_details">
                                                <div class="form-row d-flex justify-content-center align-items-center my-3" id="add-caseload_details-regular">
                                                    <!-- Start Date -->
                                                    <div class="form-group col-5">
                                                        <label for="add-start_date"><span class="required-field">*</span> Start Date:</label>
                                                        <div class="input-group h-auto">
                                                            <div class="input-group-prepend">
                                                                <span class="input-group-text h-100" id="nav-search-icon"><i class="fa-solid fa-calendar-day"></i></span>
                                                            </div>
                                                            <input type="text" class="form-control " id="add-start_date" name="add-start_date" value="<?php echo $term_start; ?>" required>
                                                        </div>
                                                    </div>

                                                    <!-- Divider -->
                                                    <div class="form-group col-1 p-0"></div>
                                                    
                                                    <!-- End Date -->
                                                    <div class="form-group col-5" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-html="true" title="To change the end date, you must either <b>dismiss</b> the student or <b>transfer</b> the student to a different caseload.">
                                                        <label for="add-end_date">End Date:</label>
                                                        <div class="input-group h-auto">
                                                            <div class="input-group-prepend">
                                                                <span class="input-group-text h-100" id="nav-search-icon"><i class="fa-solid fa-calendar-day"></i></span>
                                                            </div>
                                                            <input type="text" class="form-control" id="add-end_date" name="add-end_date" value="<?php echo $term_end; ?>" disabled readonly>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="form-row d-none flex-column justify-content-center align-items-center my-3" id="add-caseload_details-evaluation_only">
                                                    <!-- Evaluation Date -->
                                                    <div class="form-group col-11">
                                                        <label for="add-eval_date"><span class="required-field">*</span> Evaluation Meeting Date:</label>
                                                        <div class="input-group h-auto">
                                                            <div class="input-group-prepend">
                                                                <span class="input-group-text h-100" id="nav-search-icon"><i class="fa-solid fa-calendar-day"></i></span>
                                                            </div>
                                                            <input type="text" class="form-control" id="add-eval_date" name="add-eval_date" value="<?php echo date("m/d/Y"); ?>" autocomplete="off" required>
                                                        </div>
                                                    </div>

                                                    <?php if ($medicaid === true) { ?>
                                                        <div class="form-group col-11 mt-3">
                                                            <label for="add-medicaid_billing"><span class="required-field">*</span> Medicaid Billing Completed?</label>
                                                            <select class="form-select w-100" id="add-medicaid_billing" name="add-medicaid_billing" required>
                                                                <option value="-1"></option>
                                                                <option value="0">N/A</option>
                                                                <option value="1">Yes</option>
                                                            </select>
                                                        </div>
                                                    <?php } else { ?>
                                                        <div class="d-none" id="add-eval_month-div" style="height: 0px; visibility: hidden; display: none !important;">
                                                            <input class="d-none" type="hidden" id="add-medicaid_billing" name="add-medicaid_billing" value="0" aria-hidden="true" readonly disabled>
                                                        </div>
                                                    <?php } ?>
                                                </div>

                                                <?php if ($medicaid === true) { ?>
                                                    <div class="form-row d-flex justify-content-center align-items-center my-3" id="add-eval_month-div">
                                                        <!-- Evaluation Month (Medicaid) -->
                                                        <div class="form-group col-11">
                                                            <label for="add-eval_month"><span class="required-field">*</span> Month Evaluation Started (Medicaid):</label>
                                                            <div class="input-group h-auto">
                                                                <div class="input-group-prepend">
                                                                    <span class="input-group-text h-100" id="nav-search-icon"><i class="fa-solid fa-calendar-days"></i></span>
                                                                </div>
                                                                <select class="form-select" id="add-eval_month" name="add-eval_month" required>
                                                                    <option value="0" selected>N/A</option>
                                                                    <option value="1">January</option>
                                                                    <option value="2">February</option>
                                                                    <option value="3">March</option>
                                                                    <option value="4">April</option>
                                                                    <option value="5">May</option>
                                                                    <option value="6">June</option>
                                                                    <option value="7">July</option>
                                                                    <option value="8">August</option>
                                                                    <option value="9">September</option>
                                                                    <option value="10">October</option>
                                                                    <option value="11">November</option>
                                                                    <option value="12">December</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php } else { ?>
                                                    <div class="d-none" id="add-eval_month-div" style="height: 0px; visibility: hidden; display: none !important;">
                                                        <input class="d-none" type="hidden" id="add-eval_month" name="add-eval_month" value="0" aria-hidden="true" readonly disabled>
                                                    </div>
                                                <?php } ?>

                                                <div class="form-row d-none justify-content-center align-items-center my-3" id="add-eval_only_reasoning-div">
                                                    <!-- Eval Only Reasoning -->
                                                    <div class="form-group col-11">
                                                        <label for="add-eval_only-reason"><span class="required-field">*</span> Evaluation Only Reasoning:</label>
                                                        <select class="form-select w-100" id="add-eval_only-reason" name="edit-eval_only-reason" required>
                                                            <option value="0" selected>N/A</option>
                                                            <?php
                                                                $getReasons = mysqli_query($conn, "SELECT id, reason FROM caseload_dismissal_reasonings WHERE dnq=1");
                                                                if (mysqli_num_rows($getReasons) > 0)
                                                                {
                                                                    while ($reason = mysqli_fetch_array($getReasons))
                                                                    {
                                                                        echo "<option value='".$reason["id"]."'>".$reason["reason"]."</option>";
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
                                                            <label for="add-assistant_id">Assistant:</label>
                                                            <select id="add-assistant_id" name="add-assistant_id" placeholder="Please select an assistant..." required>
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
                                                                                $assistant_id = $assistant["id"];
                                                                                $assistant_fname = $assistant["fname"];
                                                                                $assistant_lname = $assistant["lname"];

                                                                                // create the option
                                                                                echo "<option value='".$assistant_id."'>".$assistant_lname.", ".$assistant_fname."</option>";
                                                                            }
                                                                        }
                                                                    }
                                                                ?>
                                                            </select>
                                                        </div>
                                                        <div class="form-group col-1 p-0">
                                                            <label for="add-clear_assistant"></label>
                                                            <button class="btn btn-secondary" id="add-clear_assistant" onclick="clearAssistantSelected('add');" type="button">
                                                                <i class="fa-solid fa-xmark"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                <?php } else { ?>
                                                    <div class="d-none">
                                                        <input class="d-none" type="hidden" id="add-assistant_id" name="add-assistant_id" value="-1" aria-hidden="true" readonly disabled>
                                                    </div>
                                                <?php } ?>

                                                <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                    <!-- Residency -->
                                                    <div class="form-group col-11">
                                                        <label for="add-residency"><span class="required-field">*</span> Residency:</label>
                                                        <div class="input-group h-auto">
                                                            <div class="input-group-prepend">
                                                                <span class="input-group-text h-100" id="nav-search-icon"><i class="fa-solid fa-house"></i></span>
                                                            </div>
                                                            <select class="flex-grow-1" id="add-residency" name="add-residency" required placeholder="Please select a residency...">
                                                                <option></option>
                                                                <?php
                                                                    $getDistricts = mysqli_query($conn, "SELECT id, name FROM customers WHERE active=1 ORDER BY name ASC");
                                                                    if (mysqli_num_rows($getDistricts) > 0) // districts (customers) found; continue
                                                                    {
                                                                        while ($district = mysqli_fetch_array($getDistricts))
                                                                        {
                                                                            // store district details locally
                                                                            $district_id = $district["id"];
                                                                            $district_name = $district["name"];

                                                                            // create the selection option
                                                                            echo "<option value='".$district_id."'>".$district_name."</option>";
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
                                                        <label for="add-district"><span class="required-field">*</span> District Attending:</label>
                                                        <div class="input-group h-auto">
                                                            <div class="input-group-prepend">
                                                                <span class="input-group-text h-100" id="nav-search-icon"><i class="fa-solid fa-building-flag"></i></span>
                                                            </div>
                                                            <select class="flex-grow-1" id="add-district" name="add-district" required placeholder="Please select a district..." onchange="getSchoolsForDistrict(this.value, 'add');">
                                                                <option></option>
                                                                <?php
                                                                    $getDistricts = mysqli_query($conn, "SELECT id, name FROM customers WHERE active=1 ORDER BY name ASC");
                                                                    if (mysqli_num_rows($getDistricts) > 0) // districts (customers) found; continue
                                                                    {
                                                                        while ($district = mysqli_fetch_array($getDistricts))
                                                                        {
                                                                            // store district details locally
                                                                            $district_id = $district["id"];
                                                                            $district_name = $district["name"];

                                                                            // create the selection option
                                                                            echo "<option value='".$district_id."'>".$district_name."</option>";
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
                                                        <label for="add-school"><span class="required-field">*</span> School Attending:</label>
                                                        <div class="input-group h-auto">
                                                            <div class="input-group-prepend">
                                                                <span class="input-group-text h-100" id="nav-search-icon"><i class="fa-solid fa-school"></i></span>
                                                            </div>
                                                            <select class="form-select" id="add-school" name="add-school" required>
                                                                <option selected disabled value></option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>

                                                <?php if ($isClassroom === true) { ?>
                                                    <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                        <!-- Classroom -->
                                                        <div class="form-group col-11">
                                                            <label for="add-classroom"><span class="required-field">*</span> Classroom:</label>
                                                            <div class="input-group h-auto">
                                                                <div class="input-group-prepend">
                                                                    <span class="input-group-text h-100" id="nav-search-icon"><i class="fa-solid fa-chalkboard"></i></span>
                                                                </div>
                                                                <select class="form-select" id="add-classroom" name="add-classroom" required>
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
                                                    </div>
                                                <?php } else { ?>
                                                    <input type="hidden" class="form-control w-100" id="add-classroom" name="add-classroom" value="" readonly disabled>
                                                <?php } ?>

                                                <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                    <!-- Grade Level -->
                                                    <div class="form-group col-11">
                                                        <label for="add-grade_level"><span class="required-field">*</span> Current Grade Level:</label>
                                                        <div class="input-group h-auto">
                                                            <div class="input-group-prepend">
                                                                <span class="input-group-text h-100" id="nav-search-icon"><i class="fa-solid fa-graduation-cap"></i></span>
                                                            </div>
                                                            <select class="form-select" id="add-grade_level" name="add-grade_level" required>
                                                                <option value="0">Kindergarten</option>
                                                                <option value="1">1st Grade</option>
                                                                <option value="2">2nd Grade</option>
                                                                <option value="3">3rd Grade</option>
                                                                <option value="4">4th Grade</option>
                                                                <option value="5">5th Grade</option>
                                                                <option value="6">6th Grade</option>
                                                                <option value="7">7th Grade</option>
                                                                <option value="8">8th Grade</option>
                                                                <option value="9">9th Grade</option>
                                                                <option value="10">10th Grade</option>
                                                                <option value="11">11th Grade</option>
                                                                <option value="12">12th Grade</option>
                                                                <option value="13">Post 12th Grade</option>
                                                                <option value="-1">Pre-Kindergarten</option>
                                                                <option value="-2">4-year-old Kindergarten</option>
                                                                <option value="-3">3-year-old Kindergarten</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                    <!-- Enrollment Type -->
                                                    <div class="form-group col-5">
                                                        <label for="add-enrollment_type"><span class="required-field">*</span> Enrollment Type:</label>
                                                        <select class="form-select w-100" id="add-enrollment_type" name="add-enrollment_type" required>
                                                            <option value selected disabled></option>
                                                            <option value="1">Resident</option>
                                                            <option value="2">Open Enrollment</option>
                                                            <option value="3">Placed</option>
                                                            <option value="4">66.0301</option>
                                                            <option value="5">Other</option>
                                                        </select>
                                                    </div>

                                                    <!-- Divider -->
                                                    <div class="form-group col-1 p-0"></div>

                                                    <!-- Educational Plan -->
                                                    <div class="form-group col-5">
                                                        <label for="add-educational_plan"><span class="required-field">*</span> Educational Plan:</label>
                                                        <select class="form-select w-100" id="add-educational_plan" name="add-educational_plan" required>
                                                            <option value selected disabled></option>
                                                            <option value="1">504</option>
                                                            <option value="2">IEP</option>
                                                            <option value="3">ISP</option>
                                                            <option value="4">Other</option>
                                                        </select>
                                                    </div>
                                                </div>

                                                <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                    <!-- Bill To -->
                                                    <div class="form-group col-5">
                                                        <label for="add-bill_to"><span class="required-field">*</span> Bill To:</label>
                                                        <select class="form-select w-100" id="add-bill_to" name="add-bill_to" required>
                                                            <option value selected disabled></option>
                                                            <option value="1">Residency (R)</option>
                                                            <option value="2">Attending (A)</option>
                                                            <option value="3">Other</option>
                                                        </select>
                                                    </div>

                                                    <!-- Divider -->
                                                    <div class="form-group col-1 p-0"></div>

                                                    <!-- Billing Type -->
                                                    <div class="form-group col-5">
                                                        <label for="add-billing_type"><span class="required-field">*</span> Billing Type:</label>
                                                        <select class="form-select w-100" id="add-billing_type" name="add-billing_type" onchange="checkBillingType(this.value, 'add');" required>
                                                            <?php if ($uosEnabled === true) { ?><option value="1" selected>Bill UOS</option><?php } ?>
                                                            <?php if ($daysEnabled === true) { ?><option value="2" <?php if ($uosEnabled === false) { echo "selected"; } ?>>Membership Days</option><?php } ?>
                                                        </select>
                                                    </div>
                                                </div>

                                                <div id="add-caseload_details-regular-extra">
                                                    <?php if ($frequencyEnabled === true && $uosEnabled === true) { ?>
                                                        <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                            <!-- Frequency -->
                                                            <div class="form-group col-6">
                                                                <label for="add-frequency"><span class="required-field">*</span> Starting Frequency:</label>
                                                                <input type="text" class="form-control w-100" id="add-frequency" name="add-frequency" required>
                                                            </div>

                                                            <!-- Divider -->
                                                            <div class="form-group col-1 p-0"></div>

                                                            <!-- Units Of Service -->
                                                            <div class="form-group col-3">
                                                                <label for="add-uos">
                                                                    <?php if ($uosRequired === true) { ?>
                                                                        <span class="required-field">*</span> UOS:
                                                                    <?php } else { ?> 
                                                                        UOS:
                                                                    <?php } ?>
                                                                </label>
                                                                <input type="number" min="0" class="form-control w-100" id="add-uos" name="add-uos" <?php if ($uosRequired === true) { echo "required"; } ?>>
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
                                                                <label for="add-frequency"><span class="required-field">*</span> Starting Frequency:</label>
                                                                <input type="text" class="form-control w-100" id="add-frequency" name="add-frequency" required>
                                                                <input type="hidden" class="form-control w-100" id="add-uos" name="add-uos" value="0" aria-hidden="true" readonly disabled>
                                                            </div>
                                                        </div>
                                                    <?php } else if ($frequencyEnabled === false && $uosEnabled === true) { ?>
                                                        <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                            <!-- Units Of Service -->
                                                            <div class="form-group col-9">
                                                                <label for="add-uos">
                                                                    <?php if ($uosRequired === true) { ?>
                                                                        <span class="required-field">*</span> UOS:
                                                                    <?php } else { ?> 
                                                                        UOS:
                                                                    <?php } ?>
                                                                </label>
                                                                <input type="number" class="form-control w-100" id="add-uos" name="add-uos" <?php if ($uosRequired === true) { echo "required"; } ?>>
                                                                <input type="hidden" class="form-control w-100" id="add-frequency" name="add-frequency" value="N/A" aria-hidden="true" readonly disabled>
                                                            </div>

                                                            <!-- Divider -->
                                                            <div class="form-group col-1 p-0"></div>

                                                            <div class="form-group col-1">
                                                                <label></label> <!-- spacer -->
                                                                <a class="btn btn-secondary" target="popup" onclick="window.open('uos_calculator_mini.php', 'UOS Calculator', 'width=768, height=700');" title="Open the UOS Calculator in a new tab!"><i class="fa-solid fa-calculator"></i></a>
                                                            </div>
                                                        </div>
                                                    <?php } else { ?>
                                                        <input type="hidden" class="form-control w-100" id="add-frequency" name="add-frequency" value="N/A" aria-hidden="true" readonly disabled>
                                                        <input type="hidden" class="form-control w-100" id="add-uos" name="add-uos" value="0" aria-hidden="true" readonly disabled>
                                                    <?php } ?>

                                                    <?php if ($extraIEPsEnabled === true && $extraEvalsEnabled === true) { ?>
                                                        <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                            <!-- Extra IEPs -->
                                                            <div class="form-group col-5">
                                                                <label for="add-extra_ieps"># of Extra IEPs:</label>
                                                                <input type="number" class="form-control w-100" id="add-extra_ieps" name="add-extra_ieps" min="0" value="0" required>
                                                            </div>

                                                            <!-- Divider -->
                                                            <div class="form-group col-1 p-0"></div>

                                                            <!-- Extra Evals -->
                                                            <div class="form-group col-5">
                                                                <label for="add-extra_evals"># of Extra Evaluations:</label>
                                                                <input type="number" class="form-control w-100" id="add-extra_evals" name="add-extra_evals" min="0" value="0" required>
                                                            </div>
                                                        </div>
                                                    <?php } else if ($extraIEPsEnabled === true && $extraEvalsEnabled === false) { ?>
                                                        <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                            <!-- Extra IEPs -->
                                                            <div class="form-group col-11">
                                                                <label for="add-extra_ieps"># of Extra IEPs:</label>
                                                                <input type="number" class="form-control w-100" id="add-extra_ieps" name="add-extra_ieps" min="0" value="0" required>
                                                                <input type="hidden" class="form-control w-100" id="add-extra_evals" name="add-extra_evals" value="0" aria-hidden="true" readonly disabled>
                                                            </div>
                                                        </div>
                                                    <?php } else if ($extraIEPsEnabled === false && $extraEvalsEnabled === true) { ?>
                                                        <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                            <!-- Extra Evals -->
                                                            <div class="form-group col-11">
                                                                <label for="add-extra_evals"># of Extra Evaluations:</label>
                                                                <input type="number" class="form-control w-100" id="add-extra_evals" name="add-extra_evals" min="0" value="0" required>
                                                                <input type="hidden" class="form-control w-100" id="add-extra_ieps" name="add-extra_ieps" value="0" aria-hidden="true" readonly disabled>
                                                            </div>
                                                        </div>
                                                    <?php } else { ?>
                                                        <input type="hidden" class="form-control w-100" id="add-extra_evals" name="add-extra_evals" value="0" aria-hidden="true" readonly disabled>
                                                        <input type="hidden" class="form-control w-100" id="add-extra_ieps" name="add-extra_ieps" value="0" aria-hidden="true" readonly disabled>
                                                    <?php } ?>
                                                </div>
                                                
                                                <div class="<?php if ($uosEnabled === false && $daysEnabled === true) { } else { echo "d-none"; } ?>" id="add-caseload_details-regular-day_use">
                                                    <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                        <!-- Membership Days -->
                                                        <div class="form-group col-11">
                                                            <label for="add-membership_days">Membership Days:</label>
                                                            <input type="number" class="form-control w-100" id="add-membership_days" name="add-membership_days" min="0" value="180" required>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                <!-- Billing Notes -->
                                                <div class="form-group col-11">
                                                    <label for="add-billing_notes">Billing Notes:</label>
                                                    <input type="text" class="form-control w-100" id="add-billing_notes" name="add-billing_notes" autocomplete="off">
                                                </div>
                                            </div>

                                            <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                <!-- Caseload Status -->
                                                <div class="form-group col-11">
                                                    <label for="add-status"><span class="required-field">*</span> Status:</label>
                                                    <button class="btn btn-success w-100" id="add-status" name="add-status" value=1 onclick="updateStatus('add-status');" aria-describedby="statusHelpBlock" type="button">Active</button>
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
                                        <button type="submit" class="btn btn-primary" onclick="addCase();"><i class="fa-solid fa-plus"></i> Add Student To Caseload</button>
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> Close</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- End Add Caseload Modal -->
                    <?php
                }
            }
        }

        // disconnect from the database
        mysqli_close($conn);
    }
?>