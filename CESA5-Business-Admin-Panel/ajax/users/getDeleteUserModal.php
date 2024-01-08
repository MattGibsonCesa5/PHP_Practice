<?php
    // start the session
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1) 
    { 
        if ((isset($_SESSION["role"]) && $_SESSION["role"] == 1) || (isset($_SESSION["district"]) && $_SESSION["district"]["status"] == 1 && ($_SESSION["district"]["role"] == "Admin" || $_SESSION["district"]["role"] == "Editor")))
        {
            // get additional required files
            include("../../includes/functions.php");
            include("../../includes/config.php");

            // connect to the database
            $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

            // get the parameters from POST
            if (isset($_POST["user_id"]) && trim($_POST["user_id"]) <> "") { $user_id = trim($_POST["user_id"]); } else { $user_id = null; }

            // verify user exists
            if ($user_id != null && verifyUser($conn, $user_id))
            {
                // get existing user details
                $getUserDetails = mysqli_prepare($conn, "SELECT * FROM users WHERE id=?");
                mysqli_stmt_bind_param($getUserDetails, "i", $user_id);
                if (mysqli_stmt_execute($getUserDetails))
                {
                    $getUserDetailsResults = mysqli_stmt_get_result($getUserDetails);
                    if (mysqli_num_rows($getUserDetailsResults) > 0)
                    {
                        // store the user's current details locally
                        $userDetails = mysqli_fetch_array($getUserDetailsResults);
                        $email = $userDetails["email"];
                        $fname = $userDetails["fname"];
                        $lname = $userDetails["lname"];

                        ?>
                            <!-- Add User Modal -->
                            <div class="modal fade" tabindex="-1" role="dialog" id="deleteUserModal" data-bs-backdrop="static" aria-labelledby="deleteUserModalLabel" aria-hidden="true">
                                <div class="modal-dialog" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header primary-modal-header">
                                            <h5 class="modal-title primary-modal-title" id="deleteUserModalLabel">Delete User</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>

                                        <div class="modal-body">
                                            <p class="my-0">
                                                Are you sure you want to delete <?php echo $lname.",".$fname; ?> with the email address of <?php echo $email; ?> as a user?
                                            </p>
                                        </div>

                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-danger" onclick="deleteUser(<?php echo $user_id; ?>);"><i class="fa-solid fa-trash-can"></i> Delete User</button>
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> Close</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- End Edit User Modal -->
                        <?php
                    }
                }
            }

            // disconnect from the database
            mysqli_close($conn);
        }
    }
?>