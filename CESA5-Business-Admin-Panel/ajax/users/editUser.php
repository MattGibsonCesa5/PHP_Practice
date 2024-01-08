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

            // get the parameter from POST
            if (isset($_POST["user_id"]) && trim($_POST["user_id"]) <> "") { $user_id = trim($_POST["user_id"]); } else { $user_id = null; }
            if (isset($_POST["email"]) && trim($_POST["email"]) <> "") { $email = trim($_POST["email"]); } else { $email = null; }
            if (isset($_POST["fname"]) && trim($_POST["fname"]) <> "") { $fname = trim($_POST["fname"]); } else { $fname = null; }
            if (isset($_POST["lname"]) && trim($_POST["lname"]) <> "") { $lname = trim($_POST["lname"]); } else { $lname = null; }
            if (isset($_POST["role_id"]) && is_numeric($_POST["role_id"])) { $role_id = $_POST["role_id"]; } else { $role_id = null; }
            if (isset($_POST["status"]) && (is_numeric($_POST["status"]) && $_POST["status"] == 1)) { $status = trim($_POST["status"]); } else { $status = 0; }

            // validate parameters
            if ($user_id != null && verifyUser($conn, $user_id))
            {
                if ($email != null)
                {
                    if ($fname != null && $lname != null)
                    {
                        if (verifyRole($conn, $role_id))
                        {
                            // check to see if the email is unique
                            $emailUpper = strtoupper($email);
                            $checkEmail = mysqli_prepare($conn, "SELECT id FROM users WHERE UPPER(email)=? AND status!=2 AND id!=?");
                            mysqli_stmt_bind_param($checkEmail, "si", $emailUpper, $user_id);
                            if (mysqli_stmt_execute($checkEmail))
                            {
                                $checkEmailResult = mysqli_stmt_get_result($checkEmail);
                                if (mysqli_num_rows($checkEmailResult) == 0) // email is unique; continue account creation
                                {
                                    // ADMIN EDIT
                                    if (isset($_SESSION["role"]) && $_SESSION["role"] == 1)
                                    {
                                        // edit the user
                                        $editUser = mysqli_prepare($conn, "UPDATE users SET email=?, lname=?, fname=?, role_id=?, status=? WHERE id=?");
                                        mysqli_stmt_bind_param($editUser, "sssiii", $email, $lname, $fname, $role_id, $status, $user_id);
                                        if (mysqli_stmt_execute($editUser))
                                        {
                                            // log the user edit
                                            echo "<span class=\"log-success\">Successfully</span> edited the user with ID $user_id.<br>"; 
                                            $message = "Successfully edited the user with ID $user_id.";
                                            $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                                            mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                                            mysqli_stmt_execute($log);
                                        }
                                        else { echo "<span class=\"log-fail\">Failed</span> to edit the user. An unexpected error has occurred! Please try again later.<br>"; }
                                    }
                                    // DISTRICT EDIT
                                    else if (isset($_SESSION["district"]) && $_SESSION["district"]["status"] == 1 && ($_SESSION["district"]["role"] == "Admin" || $_SESSION["district"]["role"] == "Editor"))
                                    {
                                        // edit the user
                                        $editUser = mysqli_prepare($conn, "UPDATE users SET email=?, lname=?, fname=?, role_id=?, status=? WHERE id=? AND customer_id=?");
                                        mysqli_stmt_bind_param($editUser, "sssiiii", $email, $lname, $fname, $role_id, $status, $user_id, $_SESSION["district"]["id"]);
                                        if (mysqli_stmt_execute($editUser))
                                        {
                                            if (mysqli_affected_rows($conn) == 1)
                                            {
                                                // log the user edit
                                                echo "<span class=\"log-success\">Successfully</span> edited the user with ID $user_id.<br>"; 
                                                $message = "Successfully edited the user with ID $user_id.";
                                                $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                                                mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                                                mysqli_stmt_execute($log);
                                            }
                                            else { echo "<span class=\"log-fail\">Failed</span> to edit the user. An unexpected error has occurred! Please try again later.<br>"; }
                                        }
                                        else { echo "<span class=\"log-fail\">Failed</span> to edit the user. An unexpected error has occurred! Please try again later.<br>"; }
                                    }
                                    else { echo "Unauthorized to perform this action."; }
                                }
                                else { echo "<span class=\"log-fail\">Failed</span> to edit the user. A user with that email address already exists!<br>"; }
                            }
                            else { echo "<span class=\"log-fail\">Failed</span> to edit the user. A user with that email address already exists!<br>"; }
                        }
                        else { echo "<span class=\"log-fail\">Failed</span> to edit the user. You must provide a valid role!<br>"; }
                    }
                    else { echo "<span class=\"log-fail\">Failed</span> to edit the user. You must provide both a first and last name!<br>"; }
                }
                else { echo "<span class=\"log-fail\">Failed</span> to edit the user. You must provide an email address!<br>"; }
            }
            else { echo "<span class=\"log-fail\">Failed</span> to edit the user. The user you are trying to edit does not exist!<br>"; }

            // disconnect from the database
            mysqli_close($conn);
        }
    }
?>