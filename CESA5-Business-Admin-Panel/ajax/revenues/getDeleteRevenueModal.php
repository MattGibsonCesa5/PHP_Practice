<?php
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get additional required files
        include("../../includes/config.php");
        include("../../includes/functions.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "DELETE_REVENUES"))
        {
            // get revenue ID POST
            if (isset($_POST["id"]) && $_POST["id"] <> "") { $id = $_POST["id"]; } else { $id = null; }

            if ($id != null)
            {
                if (verifyRevenue($conn, $id)) // verify the revenue exists
                {
                    ?>
                        <div class="modal fade" tabindex="-1" role="dialog" id="deleteRevenueModal" data-bs-backdrop="static" aria-labelledby="deleteRevenueModalLabel" aria-hidden="true">
                            <div class="modal-dialog" role="document">
                                <div class="modal-content">
                                    <div class="modal-header primary-modal-header">
                                        <h5 class="modal-title primary-modal-title" id="deleteRevenueModalLabel">Delete Revenue</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>

                                    <div class="modal-body">
                                        Are you sure you want to delete this revenue?
                                    </div>

                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-primary" onclick="deleteRevenue(<?php echo $id; ?>);"><i class="fa-solid fa-trash-can"></i> Delete Revenue</button>
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