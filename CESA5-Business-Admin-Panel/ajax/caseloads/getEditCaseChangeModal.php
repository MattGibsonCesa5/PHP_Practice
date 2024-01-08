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
            if (isset($_POST["change_id"]) && $_POST["change_id"] <> "") { $change_id = $_POST["change_id"]; } else { $change_id = null; }

            // get the student's current data
            $getChange = mysqli_prepare($conn, "SELECT cc.*, c.student_id, c.caseload_id FROM case_changes cc JOIN cases c ON cc.case_id=c.id WHERE cc.id=?");
            mysqli_stmt_bind_param($getChange, "i", $change_id);
            if (mysqli_stmt_execute($getChange))
            {
                $getChangeResult = mysqli_stmt_get_result($getChange);
                if (mysqli_num_rows($getChangeResult) > 0)
                {
                    // store caseload change details locally
                    $change = mysqli_fetch_array($getChangeResult);
                    $case_id = $change["id"];
                    $student_id = $change["student_id"];
                    $caseload_id = $change["caseload_id"];
                    if (isset($change["start_date"])) { $date = date("m/d/Y", strtotime($change["start_date"])); } else { $date = date("m/d/Y"); }
                    $frequency = $change["frequency"];
                    $uos = $change["uos"];
                    $additional_iep = $change["iep_meeting"];

                    // check to see if UOS is required for the caseload
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
                        <!-- Edit Case Change Modal -->
                        <div class="modal fade" tabindex="-1" role="dialog" id="editCaseChangeModal" data-bs-backdrop="static" aria-labelledby="editCaseChangeModalLabel" aria-hidden="true">
                            <div class="modal-dialog" role="document">
                                <div class="modal-content">
                                    <div class="modal-header primary-modal-header">
                                        <h5 class="modal-title primary-modal-title" id="editCaseChangeModalLabel">Edit Case Change</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>

                                    <div class="modal-body">
                                        <div class="form-row d-flex justify-content-center align-items-center my-3">
                                            <!-- Student -->
                                            <div class="form-group col-11">
                                                <label for="edit-case_changes-student">Student:</label>
                                                <input class="form-control w-100" id="edit-case_changes-student" name="edit-case_changes-student" value="<?php echo $name; ?>" readonly disabled>
                                            </div>
                                        </div>

                                        <div class="form-row d-flex justify-content-center align-items-center my-3">
                                            <!-- Change Date -->
                                            <div class="form-group col-11">
                                                <label for="edit-case_changes-change_date"><span class="required-field">*</span> Change Date:</label>
                                                <input type="text" class="form-control w-100" id="edit-case_changes-change_date" name="edit-case_changes-change_date" value="<?php echo $date; ?>" required>
                                            </div>
                                        </div>

                                        <div class="form-row d-flex justify-content-center align-items-center my-3">
                                            <!-- Frequency -->
                                            <div class="form-group col-6">
                                                <label for="edit-case_changes-frequency"><span class="required-field">*</span> New Frequency:</label>
                                                <input type="text" class="form-control w-100" id="edit-case_changes-frequency" name="edit-case_changes-frequency" value="<?php echo $frequency; ?>" required>
                                            </div>

                                            <!-- Divider -->
                                            <div class="form-group col-1 p-0"></div>

                                            <!-- Units Of Service -->
                                            <div class="form-group col-3">
                                                <label for="edit-case_changes-uos">
                                                    <?php if ($uosRequired === true) { ?>
                                                        <span class="required-field">*</span> 
                                                    <?php } ?>
                                                    New UOS:
                                                </label>
                                                <input type="text" class="form-control w-100" id="edit-case_changes-uos" name="edit-case_changes-uos" value="<?php echo $uos; ?>" required>
                                            </div>

                                            <div class="form-group col-1">
                                                <label></label> <!-- spacer -->
                                                <a class="btn btn-secondary" target="popup" onclick="window.open('uos_calculator_mini.php', 'UOS Calculator', 'width=768, height=700');" title="Open the UOS Calculator in a new tab!"><i class="fa-solid fa-calculator"></i></a>
                                            </div>
                                        </div>

                                        <div class="form-row d-flex justify-content-center align-items-center my-3">
                                            <!-- Additional IEP meeting -->
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="edit-additional_iep" name="edit-additional_iep" <?php if ($additional_iep == 1) { echo "checked"; } ?>>
                                                <label class="form-check-label" for="edit-additional_iep">Was an additional IEP meeting required?</label>
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
                                        <button type="button" class="btn btn-primary" onclick="editCaseChange(<?php echo $change_id; ?>);"><i class="fa-solid fa-floppy-disk"></i> Edit Case Change</button>
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> Close</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- End Edit Case Change Modal -->
                    <?php
                }
            }
        }
        
        // disconnect from the database
        mysqli_close($conn);
    }
?>