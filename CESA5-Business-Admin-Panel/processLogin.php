<?php
    include("includes/config.php");
    include("getSettings.php");

    // get variables from POST
    if (isset($_POST["email"])) { $email = $_POST["email"]; } else { $email = null; }
    if (isset($_POST["password"])) { $password = $_POST["password"]; } else { $password = null; }

    // get the user's IP address
    $user_ip = $_SERVER["REMOTE_ADDR"];

    // both email and password are set; continue login process
    if ($email != null && $password != null)
    {
        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        // check to see if the user is attempting to login as the super user
        if ($email == SUPER_LOGIN_EMAIL)
        {
            // check to see if the password matches
            if ($password == SUPER_LOGIN_PASSWORD)
            {
                session_start();

                // store information in session 
                $_SESSION["id"] = 0;
                $_SESSION["email"] = $email;
                $_SESSION["role"] = 1;
                $_SESSION["status"] = 1;

                // log the login attempt
                $logLogin = mysqli_prepare($conn, "INSERT INTO logins (user_id, user_email, ip_address, status) VALUES (0, ?, ?, 1)");
                mysqli_stmt_bind_param($logLogin, "ss", $email, $user_ip);
                mysqli_stmt_execute($logLogin);

                // redirect user to the dashboard
                header("Location: dashboard.php");
            }
            else // username and/or password was incorrect 
            { 
                // log the login attempt
                $logLogin = mysqli_prepare($conn, "INSERT INTO logins (user_id, user_email, ip_address) VALUES (0, ?, ?)");
                mysqli_stmt_bind_param($logLogin, "ss", $email, $user_ip);
                mysqli_stmt_execute($logLogin);

                // redirect user to login page
                header("Location: login.php?error=1"); 
            }
        }
        else // username and/or password was incorrect 
        { 
            // log the login attempt
            $logLogin = mysqli_prepare($conn, "INSERT INTO logins (user_email, ip_address) VALUES (?, ?)");
            mysqli_stmt_bind_param($logLogin, "ss", $email, $user_ip);
            mysqli_stmt_execute($logLogin);

            // redirect user to login page
            header("Location: login.php?error=1"); 
        }

        // disconnect from the database
        mysqli_close($conn);
    }
    // Google email is set; continue login process
    else if ($google_email != null)
    {
        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        // check to see if the user exists and is an authenticated account who is a currently active employee
        $checkUser = mysqli_prepare($conn, "SELECT u.id, u.role_id, u.status, u.customer_id, r.name AS role FROM users u 
                                            LEFT JOIN roles r ON u.role_id=r.id
                                            WHERE u.email=?");
        mysqli_stmt_bind_param($checkUser, "s", $google_email);
        if (mysqli_stmt_execute($checkUser))
        {
            $checkUserResult = mysqli_stmt_get_result($checkUser);
            if (mysqli_num_rows($checkUserResult) > 0) // user is in the system; login
            {
                // store employee details in local variables
                $details = mysqli_fetch_array($checkUserResult);
                $user_id = $details["id"];
                $user_role = $details["role_id"];
                $status = $details["status"];
                $customer_id = $details["customer_id"];
                $role_name = $details["role"];

                if ($status == 1)
                {
                    // start the session
                    session_start();

                    // store information in session variables
                    $_SESSION["id"] = $user_id;
                    $_SESSION["email"] = $google_email;
                    $_SESSION["role"] = $user_role;
                    $_SESSION["status"] = 1; // set to 1 (login success)

                    // set district account status
                    if ($role_name == "District Administrator" || $role_name == "District Editor" || $role_name == "District Viewer") {
                        $_SESSION["district"]["status"] = 1;
                        $_SESSION["district"]["id"] = $customer_id;
                        if ($role_name == "District Adminstrator") {
                            $_SESSION["district"]["role"] = "Admin";
                        } else if ($role_name == "District Editor") {
                            $_SESSION["district"]["role"] = "Editor";
                        } else if ($role_name == "role Viewer") { 
                            $_SESSION["district"]["type"] == "Viewer";
                        }
                    } else {
                        $_SESSION["district"]["status"] = 0;
                        $_SESSION["district"]["role"] = null;
                    }

                    // log the login attempt
                    $logLogin = mysqli_prepare($conn, "INSERT INTO logins (user_id, user_email, ip_address, status) VALUES (?, ?, ?, 1)");
                    mysqli_stmt_bind_param($logLogin, "iss", $user_id, $google_email, $user_ip);
                    mysqli_stmt_execute($logLogin);
                    
                    // redirect user to the dashboard
                    header("Location: dashboard.php");
                }
                else // user is in the site; but does not have access to the application 
                { 
                    // log the login attempt
                    $logLogin = mysqli_prepare($conn, "INSERT INTO logins (user_id, user_email, ip_address, status) VALUES (?, ?, ?, 2)");
                    mysqli_stmt_bind_param($logLogin, "iss", $user_id, $google_email, $user_ip);
                    mysqli_stmt_execute($logLogin);

                    // redirect user to login page
                    header("Location: login.php?error=4"); 
                }
            }
            else // Google user was not found within the system or account is inactive
            { 
                // log the login attempt
                $logLogin = mysqli_prepare($conn, "INSERT INTO logins (user_email, ip_address) VALUES (?, ?)");
                mysqli_stmt_bind_param($logLogin, "ss", $google_email, $user_ip);
                mysqli_stmt_execute($logLogin);

                // redirect user to login page
                header("Location: login.php?error=3"); 
            }
        }
        else // unexpected login attempt (nothing set) 
        {
            // log the login attempt
            $logLogin = mysqli_prepare($conn, "INSERT INTO logins (user_email, ip_address) VALUES (?, ?)");
            mysqli_stmt_bind_param($logLogin, "ss", $google_email, $user_ip);
            mysqli_stmt_execute($logLogin);

            // redirect user to login page
            header("Location: login.php?error=2"); 
        }

        // disconnect from the databse
        mysqli_close($conn);
    }
    else // unexpected login attempt (nothing set)
    { 
        // log the login attempt
        $logLogin = mysqli_prepare($conn, "INSERT INTO logins (ip_address) VALUES (?)");
        mysqli_stmt_bind_param($logLogin, "s", $user_ip);
        mysqli_stmt_execute($logLogin);

        // redirect user to login page
        header("Location: login.php?error=2"); 
    }
?>