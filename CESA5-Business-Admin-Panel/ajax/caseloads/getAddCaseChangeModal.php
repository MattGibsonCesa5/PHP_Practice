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
            // get the caseload ID from POST
            if (isset($_POST["case_id"]) && $_POST["case_id"] <> "") { $case_id = $_POST["case_id"]; } else { $case_id = null; }

            // verify case exists
            if ($case_id != null && verifyCase($conn, $case_id))
            {
                // get the student's current data
                $getCase = mysqli_prepare($conn, "SELECT * FROM cases WHERE id=?");
                mysqli_stmt_bind_param($getCase, "i", $case_id);
                if (mysqli_stmt_execute($getCase))
                {
                    $getCaseResult = mysqli_stmt_get_result($getCase);
                    if (mysqli_num_rows($getCaseResult) > 0)
                    {
                        // store case details locally
                        $case = mysqli_fetch_array($getCaseResult);
                        $case_id = $case["id"];
                        $caseload_id = $case["caseload_id"];
                        $student_id = $case["student_id"];

                        // get caseload settings
                        $isClassroom = isCaseloadClassroom($conn, $caseload_id);
                        $frequencyEnabled = isCaseloadFrequencyEnabled($conn, $caseload_id);
                        $uosEnabled = isCaseloadUOSEnabled($conn, $caseload_id);
                        $uosRequired = isCaseloadUOSRequired($conn, $caseload_id);

                        // get student details
                        $name = "";
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
                                $name = $lname.", ".$fname;
                            }
                        }

                        ?>
                            <!-- Add Caseload Change Modal -->
                            <div class="modal fade" tabindex="-1" role="dialog" id="addCaseChangeModal" data-bs-backdrop="static" aria-labelledby="addCaseChangeModalLabel" aria-hidden="true">
                                <div class="modal-dialog" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header primary-modal-header">
                                            <h5 class="modal-title primary-modal-title" id="addCaseChangeModalLabel">Add Caseload Change</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>

                                        <div class="modal-body">
                                            <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                <!-- Student -->
                                                <div class="form-group col-11">
                                                    <label for="add-case_changes-student">Student:</label>
                                                    <input class="form-control w-100" id="add-case_changes-student" name="add-case_changes-student" value="<?php echo $name; ?>" readonly disabled>
                                                </div>
                                            </div>

                                            <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                <!-- Change Date -->
                                                <div class="form-group col-11">
                                                    <label for="add-case_changes-change_date"><span class="required-field">*</span> Change Date:</label>
                                                    <input type="text" class="form-control w-100" id="add-case_changes-change_date" name="add-case_changes-change_date" autocomplete="off" required>
                                                </div>
                                            </div>

                                            <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                <!-- Frequency -->
                                                <div class="form-group col-6">
                                                    <label for="add-case_changes-frequency"><span class="required-field">*</span> New Frequency:</label>
                                                    <input type="text" class="form-control w-100" id="add-case_changes-frequency" name="add-case_changes-frequency" required>
                                                </div>

                                                <!-- Divider -->
                                                <div class="form-group col-1 p-0"></div>

                                                <!-- Units Of Service -->
                                                <div class="form-group col-3">
                                                    <label for="add-case_changes-uos">
                                                        <?php if ($uosRequired === true) { ?>
                                                            <span class="required-field">*</span> 
                                                        <?php } ?>
                                                        New UOS:
                                                    </label>
                                                    <input type="text" class="form-control w-100" id="add-case_changes-uos" name="add-case_changes-uos" required>
                                                </div>

                                                <div class="form-group col-1">
                                                    <label></label> <!-- spacer -->
                                                    <a class="btn btn-secondary" target="popup" onclick="window.open('uos_calculator_mini.php', 'UOS Calculator', 'width=768, height=700');" title="Open the UOS Calculator in a new tab!"><i class="fa-solid fa-calculator"></i></a>
                                                </div>
                                            </div>

                                            <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                <!-- Additional IEP meeting -->
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="add-additional_iep" name="add-additional_iep" checked>
                                                    <label class="form-check-label" for="add-additional_iep">Was an additional IEP meeting required?</label>
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
                                            <button type="button" class="btn btn-primary" onclick="addCaseChange(<?php echo $case_id; ?>);"><i class="fa-solid fa-plus"></i> Add Caseload Change</button>
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> Close</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- End Add Caseload Change Modal -->
                        <?php
                    }
                }
            }
        }

        // disconnect from the database
        mysqli_close($conn);
    }
?>