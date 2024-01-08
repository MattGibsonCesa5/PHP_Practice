<?php
    include("header.php");

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1) 
    { 
        if ($_SESSION["role"] == 1)
        {
            // connect to the database
            $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

            ?>
                <div class="row text-center">
                    <div class="col-2"></div>
                    <div class="col-8"><h1 class="upload-status-header">Customer Users Upload Status</h1></div>
                    <div class="col-2"></div>
                </div>

                <div class="row text-center">
                    <div class="col-2"></div>
                    <div class="col-8 upload-status-report">
                    <?php
                        if (isset($_FILES["fileToUpload"])) 
                        {
                            // get and open the file
                            $file = $_FILES['fileToUpload']['tmp_name'];
                            $file_type = $_FILES["fileToUpload"]["type"];

                            // verify the file is set and it is a .csv file
                            if (isset($file) && (isset($file_type) && $file_type == "text/csv"))
                            {                   
                                // initialize variables 
                                $updated = $inserted = $errors = 0;

                                // open the file
                                $handle = fopen($file, "r");

                                while ($data = fgetcsv($handle, 1000, ",", '"'))
                                {
                                    if (isset($data[0]) && $data[0] != "District" && trim($data[0]) <> "")
                                    {
                                        // get, clean, and store data from CSV
                                        if (isset($data[0])) { $district = clean_data($data[0]); } else { $district = null; }
                                        if (isset($data[1])) { $fname = clean_data($data[1]); } else { $fname = null; }
                                        if (isset($data[2])) { $lname = clean_data($data[2]); } else { $lname = null; }
                                        if (isset($data[3])) { $email = clean_data($data[3]); } else { $email = null; }

                                        // get customer ID based on district name
                                        $getCustomerID = mysqli_prepare($conn, "SELECT id FROM customers WHERE name=?");
                                        mysqli_stmt_bind_param($getCustomerID, "s", $district);
                                        if (mysqli_stmt_execute($getCustomerID))
                                        {
                                            $getCustomerIDResult = mysqli_stmt_get_result($getCustomerID);
                                            if (mysqli_num_rows($getCustomerIDResult) > 0)
                                            {
                                                // store customer ID
                                                $customer_id = mysqli_fetch_assoc($getCustomerIDResult)["id"];

                                                // attempt to create user account for new employee
                                                if (trim($email) <> "")
                                                {
                                                    $emailUpper = strtoupper($email);
                                                    $checkEmail = mysqli_prepare($conn, "SELECT id FROM users WHERE UPPER(email)=? AND status!=2");
                                                    mysqli_stmt_bind_param($checkEmail, "s", $emailUpper);
                                                    if (mysqli_stmt_execute($checkEmail))
                                                    {
                                                        $checkEmailResult = mysqli_stmt_get_result($checkEmail);
                                                        if (mysqli_num_rows($checkEmailResult) == 0) // email is unique; continue account creation
                                                        {
                                                            $addUser = mysqli_prepare($conn, "INSERT INTO users (lname, fname, email, role_id, customer_id, status) VALUES (?, ?, ?, (SELECT id FROM roles WHERE name='District Administrator' AND default_generated=1), ?, 0)");
                                                            mysqli_stmt_bind_param($addUser, "sssi", $lname, $fname, $email, $customer_id);
                                                            if (mysqli_stmt_execute($addUser))
                                                            {
                                                                // log to screen
                                                                $inserted++;
                                                                echo "<span class='log-success'>Successfully</span> added $lname, $fname ($email) as a District Administrator for $district. The user account has been set as <span class='log-fail'>inactive</span>. To enable the account, please manually activate the user.<br>";
                                                            
                                                                // get the new user ID
                                                                $user_id = mysqli_insert_id($conn);

                                                                // log the user creation
                                                                $message = "Successfully added the new District Administrator user with email address $email via upload. Assigned the user the ID $user_id.";
                                                                $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                                                                mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                                                                mysqli_stmt_execute($log);
                                                            }
                                                            else
                                                            {
                                                                $errors++;
                                                                echo "<span class='log-fail'>Failed</span> to add $lname, $fname ($email) as a user for $district. An unexpected error has occurred!<br>";
                                                            }
                                                        }
                                                        else
                                                        {
                                                            $errors++;
                                                            echo "<span class='log-fail'>Failed</span> to add $lname, $fname as a user for $district. A user with the email address $email already exists!<br>";
                                                        }
                                                    }
                                                }
                                                else
                                                {
                                                    $errors++;
                                                    echo "<span class='log-fail'>Failed</span> to add $lname, $fname as a user for $district. You must provide a valid email address!<br>";
                                                }
                                            }
                                            else
                                            {
                                                $errors++;
                                                echo "<span class='log-fail'>Failed</span> to add $lname, $fname as a user for $district. The district does not exist as a customer!<br>";
                                            }
                                        }
                                    }
                                }

                                echo "<i class=\"fa-solid fa-check\"></i> Upload complete!";

                                // log upload
                                $total_successes = $inserted;
                                $message = "Successfully uploaded $total_successes customer user accounts. ";
                                if ($errors > 0) { $message .= "Failed to upload $errors customer user accounts. "; }
                                $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                                mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                                mysqli_stmt_execute($log);
                            }
                            else { echo "ERROR! You must select a .csv file to upload.<br>"; }
                        }   
                        else { echo "ERROR! No upload file was found. Please select a file to upload and try again.<br>"; }
                    ?>
                    </div>
                    <div class="col-2"></div>
                </div>

                <div class="row text-center mt-3">
                    <div class="col-5"></div>
                    <div class="col-2"><button class="btn btn-primary w-100" onclick="goToCustomers();">Return To Manage Customers</button></div>
                    <div class="col-5"></div>
                </div>

                <script>function goToCustomers() { window.location.href = "customers_manage.php"; }</script>
            <?php

            // disconnect from the database
            mysqli_close($conn);
        }
        else { denyAccess(); }
    }
    else { goToLogin(); }
?>