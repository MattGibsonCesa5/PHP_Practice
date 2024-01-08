<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get additional required files
        include("../../includes/config.php");
        include("../../includes/functions.php");

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

        if (checkUserPermission($conn, "VIEW_CUSTOMERS"))
        {
            // get parameters from POST
            if (isset($_POST["customer_id"]) && $_POST["customer_id"] <> "") { $customer_id = $_POST["customer_id"]; } else { $customer_id = null; }

            // verify customer
            if (verifyCustomer($conn, $customer_id))
            {
                ?>
                    <div class="modal fade" tabindex="-1" role="dialog" id="viewCustomerUsersModal" data-bs-backdrop="static" aria-labelledby="viewCustomerUsersModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-xl" role="document">
                            <div class="modal-content">
                                <div class="modal-header primary-modal-header">
                                    <h5 class="modal-title primary-modal-title" id="viewCustomerUsersModalLabel">Customer Users</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>

                                <div class="modal-body">
                                    <table class="report_table w-100" id="customer_users">
                                        <thead>
                                            <tr>
                                                <th class="text-center py-1 px-2">ID</th>
                                                <th class="text-center py-1 px-2">Last Name</th>
                                                <th class="text-center py-1 px-2">First Name</th>
                                                <th class="text-center py-1 px-2">Email</th>
                                                <th class="text-center py-1 px-2">Role</th>
                                            </tr>
                                        </thead>

                                        <tbody>
                                        <?php
                                            // get all users who are assigned this role
                                            $getUsers = mysqli_prepare($conn, "SELECT u.id, u.fname, u.lname, u.email, r.name AS role FROM users u 
                                                                                JOIN roles r ON u.role_id=r.id
                                                                                WHERE u.customer_id=? ORDER BY u.lname ASC, u.fname ASC");
                                            mysqli_stmt_bind_param($getUsers, "i", $customer_id);
                                            if (mysqli_stmt_execute($getUsers))
                                            {
                                                $getUsersResults = mysqli_stmt_get_result($getUsers);
                                                if (mysqli_num_rows($getUsersResults) > 0) // users found
                                                {
                                                    while ($users = mysqli_fetch_array($getUsersResults))
                                                    {
                                                        // store user's details locally
                                                        $user_id = $users["id"];
                                                        $fname = $users["fname"];
                                                        $lname = $users["lname"];
                                                        $email = $users["email"];
                                                        $role = $users["role"];

                                                        echo "<tr>
                                                            <td>".$user_id."</td>
                                                            <td>".$lname."</td>
                                                            <td>".$fname."</td>
                                                            <td>".$email."</td>
                                                            <td>".$role."</td>
                                                        </tr>";
                                                    }
                                                }
                                            }
                                        ?>
                                        </tbody>
                                    </table>
                                    <?php createTableFooterV2("customer_users", "BAP_CustomerUsers_PageLength", $USER_SETTINGS["page_length"], true, true); ?>
                                </div>

                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> Close</button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php
            }
        }
    }
?>