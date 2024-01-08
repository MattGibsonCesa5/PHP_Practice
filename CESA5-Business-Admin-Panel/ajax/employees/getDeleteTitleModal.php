<?php
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get additional required files
        include("../../includes/config.php");
        include("../../includes/functions.php");
        include("../../getSettings.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "VIEW_EMPLOYEES_ALL") && checkUserPermission($conn, "ADD_EMPLOYEES"))
        {
            // get the title ID from POST
            if (isset($_POST["title_id"]) && $_POST["title_id"] <> "") { $title_id = $_POST["title_id"]; } else { $title_id = null; }

            if (verifyTitle($conn, $title_id))
            {
                ?>
                    <!-- Delete Title Modal -->
                    <div class="modal fade" tabindex="-1" role="dialog" id="deleteTitleModal" data-bs-backdrop="static" aria-labelledby="deleteTitleModalLabel" aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header primary-modal-header">
                                    <h5 class="modal-title primary-modal-title" id="deleteTitleModalLabel">Delete Title</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>

                                <div class="modal-body">
                                    <p class="m-0">
                                        Are you sure you want to delete this title? We will remove this title from all employees across all periods.
                                    </p>
                                </div>

                                <div class="modal-footer">
                                    <button type="button" class="btn btn-danger" onclick="deleteTitle(<?php echo $title_id; ?>);"><i class="fa-solid fa-trash-can"></i> Delete Title</button>
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> Close</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- End Delete Title Modal -->
                <?php
            }
        }

        // disconnect from the database
        mysqli_close($conn);
    }
?>