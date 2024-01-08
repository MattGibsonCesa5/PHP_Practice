<?php
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get additional required files
        include("../../includes/config.php");
        include("../../includes/functions.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "VIEW_EMPLOYEES_ALL") && checkUserPermission($conn, "EDIT_EMPLOYEES"))
        {
            // get the director ID from POST
            if (isset($_POST["director_id"]) && $_POST["director_id"] <> "") { $director_id = $_POST["director_id"]; } else { $director_id = null; }

            if (verifyDirector($conn, $director_id))
            {
                // get the directors name
                $director_name = getUserDisplayName($conn, $director_id);

                ?>
                    <div class="modal fade" tabindex="-1" role="dialog" id="removeDirectorModal" data-bs-backdrop="static" aria-labelledby="removeDirectorModalLabel" aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header primary-modal-header">
                                    <h5 class="modal-title primary-modal-title" id="removeDirectorModalLabel">Remove Director</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>

                                <div class="modal-body">
                                    <p class="m-0">
                                        Are you sure you want to remove <?php echo $director_name; ?> as a director? 
                                        We will remove them as a director, or secondary director, from all departments they are assigned to.
                                    </p>
                                </div>

                                <div class="modal-footer">
                                    <button type="button" class="btn btn-danger" onclick="removeDirector(<?php echo $director_id; ?>);"><i class="fa-solid fa-trash-can"></i> Remove Director</button>
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