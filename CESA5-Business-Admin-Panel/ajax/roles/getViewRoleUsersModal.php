<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        if (isset($_SESSION["role"]) && $_SESSION["role"] == 1)
        {
            // include additional required files
            include("../../includes/config.php");
            include("../../includes/functions.php");
            
            // get role ID from POST
            if (isset($_POST["role_id"]) && trim($_POST["role_id"])) { $role_id = trim($_POST["role_id"]); } else { $role_id = null; }

            if ($role_id != null)
            {
                // connect to the database
                $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

                // build default user settings array
                $USER_SETTINGS = [];
                $USER_SETTINGS["dark_mode"] = 0;
                $USER_SETTINGS["page_length"] = 10;

                // get user's settings
                $getUserSettings = mysqli_prepare($conn, "SELECT * FROM user_settings WHERE user_id=?");
                mysqli_stmt_bind_param($getUserSettings, "i", $_SESSION["id"]);
                if (mysqli_stmt_execute($getUserSettings))
                {
                    $getUserSettingsResult = mysqli_stmt_get_result($getUserSettings);
                    if (mysqli_num_rows($getUserSettingsResult)) // user's settings found
                    {
                        $USER_SETTINGS = mysqli_fetch_array($getUserSettingsResult);
                    }
                }

                if (verifyRole($conn, $role_id))
                {
                    // initialize variables
                    $role_name = "";

                    // get role details
                    $getRoleDetails = mysqli_prepare($conn, "SELECT * FROM roles WHERE id=?");
                    mysqli_stmt_bind_param($getRoleDetails, "i", $role_id);
                    if (mysqli_stmt_execute($getRoleDetails))
                    {
                        $getRoleDetailsResult = mysqli_stmt_get_result($getRoleDetails);
                        if (mysqli_num_rows($getRoleDetailsResult) > 0) // role exists
                        {
                            // store role details locally
                            $role_details = mysqli_fetch_array($getRoleDetailsResult);
                            $role_name = $role_details["name"];
                        }
                    }

                    // build the view role users modal
                    ?>
                        <!-- View Role Users Modal -->
                        <div class="modal fade" tabindex="-1" role="dialog" id="viewRoleUsersModal" data-bs-backdrop="static" aria-labelledby="viewRoleUsersModalLabel" aria-hidden="true">
                            <div class="modal-dialog modal-xl" role="document">
                                <div class="modal-content">
                                    <div class="modal-header primary-modal-header">
                                        <h5 class="modal-title primary-modal-title" id="viewRoleUsersModalLabel">View Role Users</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>

                                    <div class="modal-body">
                                        <div class="table-header p-1">
                                            <h1 class="text-center m-0"><?php echo $role_name; ?></h1>
                                        </div>
                                        <table id="role_users" class="report_table w-100">
                                            <thead>
                                                <tr>
                                                    <th class="text-center py-1 px-2">User ID</th>
                                                    <th class="text-center py-1 px-2">Last Name</th>
                                                    <th class="text-center py-1 px-2">First Name</th>
                                                    <th class="text-center py-1 px-2">Email</th>
                                                </tr>
                                            </thead>

                                            <tbody>
                                            <?php
                                                // get all users who are assigned this role
                                                $getRoleUsers = mysqli_prepare($conn, "SELECT id, fname, lname, email FROM users WHERE role_id=? ORDER BY lname ASC, fname ASC");
                                                mysqli_stmt_bind_param($getRoleUsers, "i", $role_id);
                                                if (mysqli_stmt_execute($getRoleUsers))
                                                {
                                                    $getRoleUsersResults = mysqli_stmt_get_result($getRoleUsers);
                                                    if (mysqli_num_rows($getRoleUsersResults) > 0) // users found
                                                    {
                                                        while ($role_users = mysqli_fetch_array($getRoleUsersResults))
                                                        {
                                                            // store user's details locally
                                                            $user_id = $role_users["id"];
                                                            $fname = $role_users["fname"];
                                                            $lname = $role_users["lname"];
                                                            $email = $role_users["email"];

                                                            echo "<tr>
                                                                <td>".$user_id."</td>
                                                                <td>".$lname."</td>
                                                                <td>".$fname."</td>
                                                                <td>".$email."</td>
                                                            </tr>";
                                                        }
                                                    }
                                                }
                                            ?>
                                            </tbody>
                                        </table> 
                                        <?php createTableFooterV2("role_users", "BAP_RoleUsers_PageLength", $USER_SETTINGS["page_length"], true, true); ?>
                                    </div>

                                    <div class="modal-footer p-0">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> Close</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- End View Role Users Modal -->
                    <?php
                }

                // disconnect from the database
                mysqli_close($conn);
            }
        }
    }
?>