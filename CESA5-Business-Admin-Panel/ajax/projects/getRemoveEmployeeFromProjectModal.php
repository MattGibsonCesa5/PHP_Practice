<?php
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get the required files
        include("../../includes/config.php");
        include("../../includes/functions.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "BUDGET_PROJECTS_ALL") || checkUserPermission($conn, "BUDGET_PROJECTS_ASSIGNED"))
        {
            // get the employee ID from POST
            if (isset($_POST["id"]) && $_POST["id"] <> "") { $id = $_POST["id"]; } else { $id = null; }
            if (isset($_POST["code"]) && $_POST["code"] <> "") { $code = $_POST["code"]; } else { $code = null; }
            if (isset($_POST["record"]) && $_POST["record"] <> "") { $record = $_POST["record"]; } else { $record = null; }

            if ($id != null && $record != null)
            {
                ?>
                    <div class="modal fade" tabindex="-1" role="dialog" id="removeEmployeeFromProjectModal" data-bs-backdrop="static" aria-labelledby="removeEmployeeFromProjectModalLabel" aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header primary-modal-header">
                                    <h5 class="modal-title primary-modal-title" id="removeEmployeeFromProjectModalLabel">Remove Employee From Project</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>

                                <div class="modal-body">
                                    Are you sure you want to remove this employee from the project?
                                </div>

                                <div class="modal-footer">
                                    <?php if ($code == null) { ?>
                                        <button type="button" class="btn btn-danger" onclick="removeEmployeeFromProject(<?php echo $id; ?>, <?php echo null; ?>, <?php echo $record; ?>);">Remove Employee</button>
                                    <?php } else { ?>
                                        <button type="button" class="btn btn-danger" onclick="removeEmployeeFromProject(<?php echo $id; ?>, '<?php echo $code; ?>', <?php echo $record; ?>);">Remove Employee</button>
                                    <?php } ?>
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