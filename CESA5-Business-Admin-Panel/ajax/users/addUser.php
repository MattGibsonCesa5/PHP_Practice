<?php
    // start the session
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1) 
    { 
        // get additional required files
        include("../../includes/functions.php");
        include("../../includes/config.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        ///////////////////////////////////////////////////////////////////////////////////////////
        //
        //  Site/Global User
        //
        ///////////////////////////////////////////////////////////////////////////////////////////
        if (isset($_SESSION["role"]) && $_SESSION["role"] == 1)
        {
            // get the parameter from POST
            if (isset($_POST["email"]) && trim($_POST["email"]) <> "") { $email = trim($_POST["email"]); } else { $email = null; }
            if (isset($_POST["fname"]) && trim($_POST["fname"]) <> "") { $fname = trim($_POST["fname"]); } else { $fname = null; }
            if (isset($_POST["lname"]) && trim($_POST["lname"]) <> "") { $lname = trim($_POST["lname"]); } else { $lname = null; }
            if (isset($_POST["role_id"]) && is_numeric($_POST["role_id"])) { $role_id = $_POST["role_id"]; } else { $role_id = null; }
            if (isset($_POST["status"]) && (is_numeric($_POST["status"]) && $_POST["status"] == 1)) { $status = trim($_POST["status"]); } else { $status = 0; }

            // validate parameters
            if ($email != null)
            {
                // verify names are set
                if ($fname != null && $lname != null)
                {
                    // verify role
                    if (verifyRole($conn, $role_id))
                    {
                        // check to see if the email is unique
                        $emailUpper = strtoupper($email);
                        $checkEmail = mysqli_prepare($conn, "SELECT id FROM users WHERE UPPER(email)=? AND status!=2");
                        mysqli_stmt_bind_param($checkEmail, "s", $emailUpper);
                        if (mysqli_stmt_execute($checkEmail))
                        {
                            $checkEmailResult = mysqli_stmt_get_result($checkEmail);
                            if (mysqli_num_rows($checkEmailResult) == 0) // email is unique; continue account creation
                            {
                                // add the new user
                                $addUser = mysqli_prepare($conn, "INSERT INTO users (lname, fname, email, role_id, created_by, status) VALUES (?, ?, ?, ?, ?, ?)");
                                mysqli_stmt_bind_param($addUser, "sssisi", $lname, $fname, $email, $role_id, $_SESSION["id"], $status);
                                if (mysqli_stmt_execute($addUser)) 
                                { 
                                    // get the new user ID
                                    $user_id = mysqli_insert_id($conn);

                                    // log the user creation
                                    echo "<span class=\"log-success\">Successfully</span> added the new user with email address $email. Assigned the user the ID $user_id.<br>"; 
                                    $message = "Successfully added the new user with email address $email. Assigned the user the ID $user_id.";
                                    $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                                    mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                                    mysqli_stmt_execute($log);
                                }
                                else { echo "<span class=\"log-fail\">Failed</span> to add the new user. An unexpected error has occurred! Please try again later.<br>"; }
                            }
                            else { echo "<span class=\"log-fail\">Failed</span> to add the new user. A user with that email already exists!<br>"; } // email is already taken
                        }
                        else { echo "<span class=\"log-fail\">Failed</span> to add the new user. An unexpected error has occurred! Please try again later.<br>"; }
                    }
                    else { echo "<span class=\"log-fail\">Failed</span> to add the new user. You must provide a valid account role.<br>"; }
                }
                else { echo "<span class=\"log-fail\">Failed</span> ato add the new user. You must provide both a first and last name.<br>"; }
            }
            else { echo "<span class=\"log-fail\">Failed</span> to add the new user. You must provide an email address.<br>"; }
        }
        ///////////////////////////////////////////////////////////////////////////////////////////
        //
        //  District User
        //
        ///////////////////////////////////////////////////////////////////////////////////////////
        else if (isset($_SESSION["district"]) && $_SESSION["district"]["status"] == 1 && ($_SESSION["district"]["role"] == "Admin" || $_SESSION["district"]["role"] == "Editor"))
        {
            // get the parameter from POST
            if (isset($_POST["email"]) && trim($_POST["email"]) <> "") { $email = trim($_POST["email"]); } else { $email = null; }
            if (isset($_POST["fname"]) && trim($_POST["fname"]) <> "") { $fname = trim($_POST["fname"]); } else { $fname = null; }
            if (isset($_POST["lname"]) && trim($_POST["lname"]) <> "") { $lname = trim($_POST["lname"]); } else { $lname = null; }
            if (isset($_POST["role_id"]) && is_numeric($_POST["role_id"])) { $role_id = $_POST["role_id"]; } else { $role_id = null; }
            if (isset($_POST["status"]) && (is_numeric($_POST["status"]) && $_POST["status"] == 1)) { $status = trim($_POST["status"]); } else { $status = 0; }

            // validate parameters
            if ($email != null)
            {
                // verify names are set
                if ($fname != null && $lname != null)
                {
                    // verify role
                    if (verifyRole($conn, $role_id))
                    {
                        // verify district/customer exists
                        if (verifyCustomer($conn, $_SESSION["district"]["id"]))
                        {
                            // get the domain for the customer
                            $validDomian = null;
                            $getDomain = mysqli_prepare($conn, "SELECT domain FROM customers WHERE id=?");
                            mysqli_stmt_bind_param($getDomain, "i", $_SESSION["district"]["id"]);
                            if (mysqli_stmt_execute($getDomain))
                            {
                                $getDomainResult = mysqli_stmt_get_result($getDomain);
                                if (mysqli_num_rows($getDomainResult) > 0)
                                {
                                    $validDomain = mysqli_fetch_assoc($getDomainResult)["domain"];
                                }
                            }
                            
                            // get the email domain for the email submitted
                            $emailDomain = null;
                            $emailArr = explode("@", $email);
                            if (is_array($emailArr) && count($emailArr) == 2)
                            {
                                $emailDomain = $emailArr[1];
                            }

                            // verify the email domain matches the district domain
                            if (strtoupper($validDomain) == strtoupper($emailDomain))
                            {
                                // check to see if the email is unique
                                $emailUpper = strtoupper($email);
                                $checkEmail = mysqli_prepare($conn, "SELECT id FROM users WHERE UPPER(email)=? AND status!=2");
                                mysqli_stmt_bind_param($checkEmail, "s", $emailUpper);
                                if (mysqli_stmt_execute($checkEmail))
                                {
                                    $checkEmailResult = mysqli_stmt_get_result($checkEmail);
                                    if (mysqli_num_rows($checkEmailResult) == 0) // email is unique; continue account creation
                                    {
                                        // add the new user
                                        $addUser = mysqli_prepare($conn, "INSERT INTO users (lname, fname, email, role_id, customer_id, created_by, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
                                        mysqli_stmt_bind_param($addUser, "sssiisi", $lname, $fname, $email, $role_id, $_SESSION["district"]["id"], $_SESSION["id"], $status);
                                        if (mysqli_stmt_execute($addUser)) 
                                        { 
                                            // get the new user ID
                                            $user_id = mysqli_insert_id($conn);

                                            // log the user creation
                                            echo "<span class=\"log-success\">Successfully</span> added the new user with email address $email. Assigned the user the ID $user_id.<br>"; 
                                            $message = "Successfully added the new user with email address $email. Assigned the user the ID $user_id. User is assigned to the customer with ID of ".$_SESSION["district"]["id"].".";
                                            $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                                            mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                                            mysqli_stmt_execute($log);
                                        }
                                        else { echo "<span class=\"log-fail\">Failed</span> to add the new user. An unexpected error has occurred! Please try again later.<br>"; }
                                    }
                                    else { echo "<span class=\"log-fail\">Failed</span> to add the new user. A user with that email already exists!<br>"; } // email is already taken
                                }
                                else { echo "<span class=\"log-fail\">Failed</span> to add the new user. An unexpected error has occurred! Please try again later.<br>"; }
                            }
                            else { echo "<span class=\"log-fail\">Failed</span> to add the new user. The domain of the user you are trying to add does not match the district domain: $validDomain.<br>"; }
                        }
                        else { echo "<span class=\"log-fail\">Failed</span> to add the new user. The district your account is assigned to is not valid!<br>"; }
                    }
                    else { echo "<span class=\"log-fail\">Failed</span> to add the new user. You must provide a valid account role.<br>"; }
                }
                else { echo "<span class=\"log-fail\">Failed</span> ato add the new user. You must provide both a first and last name.<br>"; }
            }
            else { echo "<span class=\"log-fail\">Failed</span> to add the new user. You must provide an email address.<br>"; }
        }

        // disconnect from the database
        mysqli_close($conn);
    }
?>