<?php
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get additional required files
        include("../../includes/config.php");
        include("../../includes/functions.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "DELETE_DEPARTMENTS"))
        {
            // get the department ID from POST
            if (isset($_POST["department_id"]) && $_POST["department_id"] <> "") { $department_id = $_POST["department_id"]; } else { $department_id = null; }

            ?>
                <div class="modal fade" tabindex="-1" role="dialog" id="deleteDepartmentModal" data-bs-backdrop="static" aria-labelledby="deleteDepartmentModalLabel" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header primary-modal-header">
                                <h5 class="modal-title primary-modal-title" id="deleteDepartmentModalLabel">Delete Department</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>

                            <div class="modal-body">
                                Are you sure you want to delete this department? All employees assigned to this department will no longer be assigned to this department.
                            </div>

                            <div class="modal-footer">
                                <button type="button" class="btn btn-primary" onclick="deleteDepartment(<?php echo $department_id; ?>);"><i class="fa-solid fa-trash-can"></i> Delete Department</button>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> Close</button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php
        }

        // disconnect from the database
        mysqli_close($conn);
    }
?>