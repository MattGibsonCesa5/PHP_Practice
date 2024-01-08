<?php
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        if (isset($_SESSION["role"]) && $_SESSION["role"] == 1)
        {
            // get the parameters from POST
            if (isset($_POST["user_id"]) && $_POST["user_id"] <> "") { $user_id = $_POST["user_id"]; } else { $user_id = null; }

            // attempt to login as the user specified
            if ($user_id != null) 
            {
                // get additional required files
                include("../../includes/config.php");
                include("../../includes/functions.php");

                // connect to the database
                $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

                // verify the user exists
                if (verifyUser($conn, $user_id))
                {
                    // get additional user details
                    $checkUser = mysqli_prepare($conn, "SELECT u.id, u.email, u.role_id, u.status, u.customer_id, r.name AS role FROM users u 
                                                        LEFT JOIN roles r ON u.role_id=r.id
                                                        WHERE u.id=?");
                    mysqli_stmt_bind_param($checkUser, "i", $user_id);
                    if (mysqli_stmt_execute($checkUser))
                    {
                        $checkUserResult = mysqli_stmt_get_result($checkUser);
                        if (mysqli_num_rows($checkUserResult) > 0) // user exists; continue masquerade process
                        {
                            // store user details locally
                            $userDetails = mysqli_fetch_array($checkUserResult);
                            $email = $userDetails["email"];
                            $role_id = $userDetails["role_id"];
                            $status = $userDetails["status"];
                            $customer_id = $userDetails["customer_id"];
                            $role_name = $userDetails["role"];

                            // store current session variables
                            $_SESSION["masq_id"] = $_SESSION["id"];
                            $_SESSION["masq_email"] = $_SESSION["email"];
                            $_SESSION["masq_role"] = $_SESSION["role"];

                            // override current session variables as user we are masquerading into
                            $_SESSION["masquerade"] = 1;
                            $_SESSION["id"] = $user_id;
                            $_SESSION["email"] = $email;
                            $_SESSION["role"] = $role_id;

                            // set district account status
                            if ($role_name == "District Administrator" || $role_name == "District Editor" || $role_name == "District Viewer") {
                                $_SESSION["district"]["status"] = 1;
                                $_SESSION["district"]["id"] = $customer_id;
                                if ($role_name == "District Administrator") {
                                    $_SESSION["district"]["role"] = "Admin";
                                } else if ($role_name == "District Editor") {
                                    $_SESSION["district"]["role"] = "Editor";
                                } else if ($role_name == "District Viewer") { 
                                    $_SESSION["district"]["role"] == "Viewer";
                                }
                            } else {
                                $_SESSION["district"]["status"] = 0;
                                $_SESSION["district"]["role"] = null;
                            }
                        }
                    }
                }

                // disconnect from the database
                mysqli_close($conn);
            }
        }
    }
?>