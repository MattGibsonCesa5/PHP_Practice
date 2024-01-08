<?php
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get additional required files
        include("../../includes/config.php");
        include("../../includes/functions.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "DELETE_PROJECTS"))
        {
            // get the project code from POST
            if (isset($_POST["code"]) && $_POST["code"] <> "") { $code = $_POST["code"]; } else { $code = null; }

            ?>
                <div class="modal fade" tabindex="-1" role="dialog" id="deleteProjectModal" data-bs-backdrop="static" aria-labelledby="deleteProjectModalLabel" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header primary-modal-header">
                                <h5 class="modal-title primary-modal-title" id="deleteProjectModalLabel">Delete Project</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>

                            <div class="modal-body">
                                <div class="alert alert-danger">
                                    <p class="m-0">
                                        Are you sure you want to delete this project? This will <b>delete <u>ALL</u> project data</b>, including both historical and current project budget data, associated with this project.
                                        Services, revenues, and expenses that are assigned to this project will remain; however, they'll not be assigned to a project.
                                    </p>
                                </div>
                            </div>

                            <div class="modal-footer">
                                <button type="button" class="btn btn-danger" onclick="deleteProject('<?php echo $code; ?>');"><i class="fa-solid fa-trash-can"></i> Delete Project</button>
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