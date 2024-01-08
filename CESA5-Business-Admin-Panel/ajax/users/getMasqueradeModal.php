<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        if (isset($_SESSION["role"]) && $_SESSION["role"] == 1)
        {
            // get the parameters from POST
            if (isset($_POST["user_id"]) && $_POST["user_id"] <> "") { $user_id = $_POST["user_id"]; } else { $user_id = null; }

            // get additional required files
            include("../../includes/config.php");
            include("../../includes/functions.php");

            // connect to the database
            $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

            // verify the user is valid and exists
            if ($user_id != null && verifyUser($conn, $user_id))
            {
                ?>
                    <div class="modal fade" tabindex="-1" role="dialog" id="masqueradeModal" data-bs-backdrop="static" aria-labelledby="masqueradeModalLabel" aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header primary-modal-header">
                                    <h5 class="modal-title primary-modal-title" id="masqueradeModalLabel">Login As User</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>

                                <div class="modal-body">
                                    Are you sure you want to login as this user?
                                </div>

                                <div class="modal-footer">
                                    <button type="button" class="btn btn-primary" onclick="masquerade(<?php echo $user_id; ?>);"><i class="fa-solid fa-user-secret"></i> Login As User</button>
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
    }
?>