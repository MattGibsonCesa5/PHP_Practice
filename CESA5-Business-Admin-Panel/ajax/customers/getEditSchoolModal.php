<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get additional required files
        include("../../includes/config.php");
        include("../../includes/functions.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "VIEW_CASELOADS_ALL"))
        {
            // get the student ID from POST
            if (isset($_POST["school_id"]) && $_POST["school_id"] <> "") { $school_id = $_POST["school_id"]; } else { $school_id = null; }

            // get school details
            $getSchool = mysqli_prepare($conn, "SELECT id, name FROM schools WHERE id=?");
            mysqli_stmt_bind_param($getSchool, "i", $school_id);
            if (mysqli_stmt_execute($getSchool))
            {
                $getSchoolResult = mysqli_stmt_get_result($getSchool);
                if (mysqli_num_rows($getSchoolResult) > 0) // school exists, continue
                {
                    // store school details locally
                    $school_details = mysqli_fetch_array($getSchoolResult);
                    $school_name = $school_details["name"];

                    // build the modal
                    ?>
                        <div class="modal fade" tabindex="-1" role="dialog" id="editSchoolModal" data-bs-backdrop="static" aria-labelledby="editSchoolModalLabel" aria-hidden="true">
                            <div class="modal-dialog" role="document">
                                <div class="modal-content">
                                    <div class="modal-header primary-modal-header">
                                        <h5 class="modal-title primary-modal-title" id="editSchoolModalLabel">Edit School</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>

                                    <div class="modal-body">
                                        <div class="form-row d-flex justify-content-center align-items-center my-3">
                                            <!-- Name -->
                                            <div class="form-group col-11">
                                                <label for="edit-school_name">School Name:</label>
                                                <input type="text" class="form-control w-100" id="edit-school_name" name="edit-school_name" value="<?php echo $school_name; ?>">
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
                                        <button type="button" class="btn btn-primary" onclick="editSchool(<?php echo $school_id; ?>);"><i class="fa-solid fa-floppy-disk"></i> Save School</button>
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> Close</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php
                }
            }
        }

        // disconnect from the database
        mysqli_close($conn);
    }
?>