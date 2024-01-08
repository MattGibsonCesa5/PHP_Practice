<?php
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get additional required files
        include("../../includes/functions.php");
        include("../../includes/config.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "DELETE_STUDENTS"))
        {
            // get the student ID from POST
            if (isset($_POST["student_id"]) && $_POST["student_id"] <> "") { $student_id = $_POST["student_id"]; } else { $student_id = null; }

            if ($student_id <> "" && $student_id != null && $student_id != "undefined")
            {
                ?>
                    <div class="modal fade" tabindex="-1" role="dialog" id="deleteStudentModal" data-bs-backdrop="static" aria-labelledby="deleteStudentModalLabel" aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header primary-modal-header">
                                    <h5 class="modal-title primary-modal-title" id="deleteStudentModalLabel">Delete Student</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>

                                <div class="modal-body">
                                    <p>Are you sure you want to delete this student? This action cannot be undone.</p>
                                </div>

                                <div class="modal-footer">
                                    <button type="button" class="btn btn-primary" onclick="deleteStudent(<?php echo $student_id; ?>);"><i class="fa-solid fa-trash-can"></i> Delete Student</button>
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> Close</button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php
            }
        }

        // disconnect from the database
        mysqli_close($conn);
    }
?>