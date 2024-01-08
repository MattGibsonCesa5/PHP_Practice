<?php
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get additional required files
        include("../../includes/functions.php");
        include("../../includes/config.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "EDIT_STUDENTS"))
        {
            // get the student ID from POST
            if (isset($_POST["student_id"]) && $_POST["student_id"] <> "") { $student_id = $_POST["student_id"]; } else { $student_id = null; }

            if (verifyStudent($conn, $student_id))
            {
                // get the student's current data
                $getStudent = mysqli_prepare($conn, "SELECT * FROM caseload_students WHERE id=?");
                mysqli_stmt_bind_param($getStudent, "i", $student_id);
                if (mysqli_stmt_execute($getStudent))
                {
                    $getStudentResult = mysqli_stmt_get_result($getStudent);
                    if (mysqli_num_rows($getStudentResult) > 0)
                    {
                        // store student data locally
                        $student = mysqli_fetch_array($getStudentResult);
                        $id = $student["id"];
                        $fname = $student["fname"];
                        $lname = $student["lname"];
                        $status = $student["status"];
                        if (isset($student["date_of_birth"])) { $date_of_birth = date("n/j/Y", strtotime($student["date_of_birth"])); } else { $date_of_birth = ""; }
                        $age = getAge($date_of_birth);
                        $gender = $student["gender"];

                        ?>
                            <div class="modal fade" tabindex="-1" role="dialog" id="editStudentModal" data-bs-backdrop="static" aria-labelledby="editStudentModalLabel" aria-hidden="true">
                                <div class="modal-dialog" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header primary-modal-header">
                                            <h5 class="modal-title primary-modal-title" id="editStudentModalLabel">Edit Student</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>

                                        <div class="modal-body">
                                            <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                <!-- Student ID -->
                                                <div class="form-group col-11">
                                                    <label for="edit-id">Student ID:</label>
                                                    <input type="text" class="form-control w-100" id="edit-id" name="edit-id" value="<?php echo $id; ?>" disabled readonly>
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
                                                <!-- Date Of Birth -->
                                                <div class="form-group col-5">
                                                    <label for="edit-date_of_birth"><span class="required-field">*</span> Date Of Birth:</label>
                                                    <input type="text" class="form-control w-100" id="edit-date_of_birth" name="edit-date_of_birth" value="<?php echo date("m/d/Y", strtotime($date_of_birth)); ?>" onchange="updateAge(this.value, 'edit');" required>
                                                </div>

                                                <!-- Divider -->
                                                <div class="form-group col-1 p-0"></div>

                                                <!-- Age -->
                                                <div class="form-group col-5">
                                                    <label for="add-age">Age:</label>
                                                    <input type="number" class="form-control w-100" id="edit-age" name="edit-age" value="<?php echo $age; ?>" disabled readonly>
                                                </div>
                                            </div>

                                            <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                <!-- Status -->
                                                <div class="form-group col-11">
                                                    <label for="edit-status"><span class="required-field">*</span> Status:</label>
                                                    <?php if ($status == 1) { ?>
                                                        <button class="btn btn-success w-100" id="edit-status" value="1" onclick="updateStatus('edit-status');">Active</button>
                                                    <?php } else { ?>
                                                        <button class="btn btn-danger w-100" id="edit-status" value="0" onclick="updateStatus('edit-status');">Inactive</button>
                                                    <?php } ?>
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
                                            <button type="button" class="btn btn-primary" onclick="editStudent(<?php echo $student_id; ?>);"><i class="fa-solid fa-floppy-disk"></i> Save Student</button>
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