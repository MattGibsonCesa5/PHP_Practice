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
                if (isset($_SESSION["role"]) && $_SESSION["role"] == 1) {
                    $getUserDetails = mysqli_prepare($conn, "SELECT * FROM users WHERE id=?");
                    mysqli_stmt_bind_param($getUserDetails, "i", $user_id);
                } else if (isset($_SESSION["district"]) && $_SESSION["district"]["status"] == 1 && ($_SESSION["district"]["role"] == "Admin" || $_SESSION["district"]["role"] == "Editor")) {
                    $getUserDetails = mysqli_prepare($conn, "SELECT * FROM users WHERE id=? AND customer_id=?");
                    mysqli_stmt_bind_param($getUserDetails, "ii", $user_id, $_SESSION["district"]["id"]);
                }
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
                        $role_id = $userDetails["role_id"];
                        $status = $userDetails["status"];

                        ?>
                            <!-- Add User Modal -->
                            <div class="modal fade" tabindex="-1" role="dialog" id="editUserModal" data-bs-backdrop="static" aria-labelledby="editUserModalLabel" aria-hidden="true">
                                <div class="modal-dialog" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header primary-modal-header">
                                            <h5 class="modal-title primary-modal-title" id="editUserModalLabel">Edit User</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>

                                        <div class="modal-body">
                                            <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                <div class="form-group col-11">
                                                    <label for="edit-email"><span class="required-field">*</span> Email Address:</label>
                                                    <input type="text" class="form-control w-100" id="edit-email" name="edit-email" autocomplete="off" value="<?php echo $email; ?>" required>
                                                </div>
                                            </div>
                                            
                                            <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                <!-- First Name -->
                                                <div class="form-group col-5">
                                                    <label for="edit-fname"><span class="required-field">*</span> First Name:</label>
                                                    <input type="text" class="form-control w-100" id="edit-fname" name="edit-fname" autocomplete="off" value="<?php echo $fname; ?>" required>
                                                </div>

                                                <!-- Divider -->
                                                <div class="form-group col-1 p-0"></div>

                                                <!-- Last Name -->
                                                <div class="form-group col-5">
                                                    <label for="edit-lname"><span class="required-field">*</span> Last Name:</label>
                                                    <input type="text" class="form-control w-100" id="edit-lname" name="edit-lname" autocomplete="off" value="<?php echo $lname; ?>" required>
                                                </div>
                                            </div>

                                            <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                <div class="form-group col-11">
                                                    <label for="edit-role_id"><span class="required-field">*</span> Account Role:</label>
                                                    <select class="form-select w-100" id="edit-role_id" name="edit-role_id" autocomplete="off" required>
                                                        <option></option>
                                                        <?php
                                                            if (isset($_SESSION["role"]) && $_SESSION["role"] == 1)
                                                            {
                                                                // create the role selection dropdown options
                                                                $getRoles = mysqli_query($conn, "SELECT * FROM roles ORDER BY default_generated DESC, name ASC");
                                                                if (mysqli_num_rows($getRoles) > 0) // roles found
                                                                {
                                                                    while ($role_details = mysqli_fetch_array($getRoles))
                                                                    {
                                                                        // store role details locally
                                                                        $DB_role_id = $role_details["id"];
                                                                        $role_name = $role_details["name"];
                                                                        $default_generated = $role_details["default_generated"];

                                                                        // create the option (bold option if it is a default role)
                                                                        if ($DB_role_id == $role_id)
                                                                        {
                                                                            if ($default_generated == 1) { echo "<option value='".$DB_role_id."' class='fw-bold' selected>".$role_name."</option>"; }
                                                                            else { echo "<option value='".$DB_role_id."' selected>".$role_name."</option>"; }
                                                                        } else {
                                                                            if ($default_generated == 1) { echo "<option value='".$DB_role_id."' class='fw-bold'>".$role_name."</option>"; }
                                                                            else { echo "<option value='".$DB_role_id."'>".$role_name."</option>"; }
                                                                        }
                                                                    }
                                                                }
                                                            }
                                                            else if (isset($_SESSION["district"]) && $_SESSION["district"]["status"] == 1 && ($_SESSION["district"]["role"] == "Admin" || $_SESSION["district"]["role"] == "Editor"))
                                                            {
                                                                // create the role selection dropdown options
                                                                $getRoles = mysqli_query($conn, "SELECT * FROM roles WHERE name='District Administrator' OR name='District Editor' OR name='District Viewer' ORDER BY name ASC");
                                                                if (mysqli_num_rows($getRoles) > 0) // roles found
                                                                {
                                                                    while ($role_details = mysqli_fetch_array($getRoles))
                                                                    {
                                                                        // store role details locally
                                                                        $DB_role_id = $role_details["id"];
                                                                        $role_name = $role_details["name"];
                                                                        $default_generated = $role_details["default_generated"];

                                                                        // create the option (bold option if it is a default role)
                                                                        if ($DB_role_id == $role_id)
                                                                        {
                                                                            if ($default_generated == 1) { echo "<option value='".$DB_role_id."' class='fw-bold' selected>".$role_name."</option>"; }
                                                                            else { echo "<option value='".$DB_role_id."' selected>".$role_name."</option>"; }
                                                                        } else {
                                                                            if ($default_generated == 1) { echo "<option value='".$DB_role_id."' class='fw-bold'>".$role_name."</option>"; }
                                                                            else { echo "<option value='".$DB_role_id."'>".$role_name."</option>"; }
                                                                        }
                                                                    }
                                                                }
                                                            }
                                                        ?>  
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                <!-- Status -->
                                                <div class="form-group col-11">
                                                    <label for="edit-status"><span class="required-field">*</span> Status:</label>
                                                    <?php if ($status == 1) { ?>
                                                        <button class="btn btn-success w-100" id="edit-status" name="edit-status" value=1 onclick="updateStatus('edit-status');">Active</button>
                                                    <?php } else { ?>
                                                        <button class="btn btn-danger w-100" id="edit-status" name="edit-status" value=0 onclick="updateStatus('edit-status');">Inactive</button>
                                                    <?php } ?>
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
                                            <button type="button" class="btn btn-primary" onclick="editUser(<?php echo $user_id; ?>);"><i class="fa-solid fa-floppy-disk"></i> Edit User</button>
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