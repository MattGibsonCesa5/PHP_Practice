<?php
    include("header.php");

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1) 
    { 
        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);
        
        if (isset($PERMISSIONS["ADD_CUSTOMER_GROUPS"]))
        {
            ?>
                <div class="row text-center">
                    <div class="col-2"></div>
                    <div class="col-8"><h1 class="upload-status-header">Groups Upload Status</h1></div>
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
                                    if (isset($data[0]) && $data[0] != "Group Name") // skip the first row by looking at cell data
                                    {
                                        // get and clean up the upload's data
                                        if (isset($data[0]) && $data[0] <> "") { $group_name = clean_data($data[0]); } else { $group_name = null; } // group name
                                        if (isset($data[1]) && $data[1] <> "") { $customer_name = clean_data($data[1]); } else { $customer_name = null; } // customer name

                                        if ($group_name != null && $customer_name != null)
                                        {
                                            // check to see if group exists; if so, get ID; otherwise, create group 
                                            $checkGroup = mysqli_prepare($conn, "SELECT id FROM `groups` WHERE name=?");
                                            mysqli_stmt_bind_param($checkGroup, "s", $group_name);
                                            if (mysqli_stmt_execute($checkGroup))
                                            {
                                                $checkGroupResult = mysqli_stmt_get_result($checkGroup);
                                                if (mysqli_num_rows($checkGroupResult) > 0) // group already exists; continue adding member
                                                {
                                                    // store group ID locally
                                                    $group_id = mysqli_fetch_array($checkGroupResult)["id"];

                                                    // verify customer exists
                                                    $checkCustomer = mysqli_prepare($conn, "SELECT id FROM customers WHERE name=?");
                                                    mysqli_stmt_bind_param($checkCustomer, "s", $customer_name);
                                                    if (mysqli_stmt_execute($checkCustomer))
                                                    {
                                                        $checkCustomerResult = mysqli_stmt_get_result($checkCustomer);
                                                        if (mysqli_num_rows($checkCustomerResult) > 0) // customer found; continue
                                                        {
                                                            // store customer ID locally
                                                            $customer_id = mysqli_fetch_array($checkCustomerResult)["id"];

                                                            // check to see if customer is already in the group
                                                            $checkMembership = mysqli_prepare($conn, "SELECT id FROM group_members WHERE group_id=? AND customer_id=?");
                                                            mysqli_stmt_bind_param($checkMembership, "ii", $group_id, $customer_id);
                                                            if (mysqli_stmt_execute($checkMembership))
                                                            {
                                                                $checkMembershipResult = mysqli_stmt_get_result($checkMembership);
                                                                if (mysqli_num_rows($checkMembershipResult) == 0) // customer is not a member already; add member
                                                                {
                                                                    // add customer to the group
                                                                    $addMember = mysqli_prepare($conn, "INSERT INTO group_members (group_id, customer_id) VALUES (?, ?)");
                                                                    mysqli_stmt_bind_param($addMember, "ii", $group_id, $customer_id);
                                                                    if (mysqli_stmt_execute($addMember)) // successfully added the customer to the group
                                                                    {
                                                                        $inserted++;
                                                                        echo "<span class=\"log-success\">Successfully</span> added $customer_name to the group $group_name.<br>";
                                                                    }
                                                                    else // failed to add the customer to the group
                                                                    {
                                                                        $errors++;
                                                                        echo "<span class=\"log-fail\">Failed</span> to add $customer_name to the group $group_name.<br>";
                                                                    }
                                                                }
                                                                else
                                                                {
                                                                    $errors++;
                                                                    echo "$customer_name is already a member of $group_name.<br>";
                                                                }
                                                            }
                                                        }
                                                        else // customer does not exist
                                                        {
                                                            $errors++;
                                                            echo "<span class=\"log-fail\">Failed</span> to add $customer_name to the group $group_name. Customer not found.<br>";
                                                        }
                                                    }                                                        
                                                    else // failed to verify customer
                                                    {
                                                        $errors++;
                                                        echo "<span class=\"log-fail\">Failed</span> to add $customer_name to the group $group_name. An unknown error has occurred.<br>";
                                                    }
                                                }
                                                else // group does not exist; create group, then add member
                                                {
                                                    $addGroup = mysqli_prepare($conn, "INSERT INTO `groups` (name) VALUES (?)");
                                                    mysqli_stmt_bind_param($addGroup, "s", $group_name);
                                                    if (mysqli_stmt_execute($addGroup)) // successfully created the group
                                                    {
                                                        echo "<span class=\"log-success\">Successfully</span> created the group $group_name.<br>";

                                                        // store the newly created group ID locally
                                                        $group_id = mysqli_insert_id($conn);

                                                        // verify customer exists
                                                        $checkCustomer = mysqli_prepare($conn, "SELECT id FROM customers WHERE name=?");
                                                        mysqli_stmt_bind_param($checkCustomer, "s", $customer_name);
                                                        if (mysqli_stmt_execute($checkCustomer))
                                                        {
                                                            $checkCustomerResult = mysqli_stmt_get_result($checkCustomer);
                                                            if (mysqli_num_rows($checkCustomerResult) > 0) // customer found; continue
                                                            {
                                                                // store customer ID locally
                                                                $customer_id = mysqli_fetch_array($checkCustomerResult)["id"];

                                                                // check to see if customer is already in the group
                                                                $checkMembership = mysqli_prepare($conn, "SELECT id FROM group_members WHERE group_id=? AND customer_id=?");
                                                                mysqli_stmt_bind_param($checkMembership, "ii", $group_id, $customer_id);
                                                                if (mysqli_stmt_execute($checkMembership))
                                                                {
                                                                    $checkMembershipResult = mysqli_stmt_get_result($checkMembership);
                                                                    if (mysqli_num_rows($checkMembershipResult) == 0) // customer is not a member already; add member
                                                                    {
                                                                        // add customer to the group
                                                                        $addMember = mysqli_prepare($conn, "INSERT INTO group_members (group_id, customer_id) VALUES (?, ?)");
                                                                        mysqli_stmt_bind_param($addMember, "ii", $group_id, $customer_id);
                                                                        if (mysqli_stmt_execute($addMember)) // successfully added the customer to the group
                                                                        {
                                                                            $inserted++;
                                                                            echo "<span class=\"log-success\">Successfully</span> added $customer_name to the group $group_name.<br>";
                                                                        }
                                                                        else // failed to add the customer to the group
                                                                        {
                                                                            $errors++;
                                                                            echo "<span class=\"log-fail\">Failed</span> to add $customer_name to the group $group_name.<br>";
                                                                        }
                                                                    }
                                                                    else
                                                                    {
                                                                        $errors++;
                                                                        echo "$customer_name is already a member of $group_name.<br>";
                                                                    }
                                                                }
                                                            }
                                                            else // customer does not exist
                                                            {
                                                                $errors++;
                                                                echo "<span class=\"log-fail\">Failed</span> to add $customer_name to the group $group_name. Customer not found.<br>";
                                                            }
                                                        }                                                        
                                                        else // failed to verify customer
                                                        {
                                                            $errors++;
                                                            echo "<span class=\"log-fail\">Failed</span> to add $customer_name to the group $group_name. An unknown error has occurred.<br>";
                                                        }
                                                    }
                                                    else // failed to create the group
                                                    {
                                                        $errors++;
                                                        echo "<span class=\"log-fail\">Failed</span> to create the group $group_name.<br>";
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }

                                echo "<i class=\"fa-solid fa-check\"></i> Upload complete!";

                                // log upload
                                $message = "Successfully uploaded groups.";
                                $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                                mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                                mysqli_stmt_execute($log);

                                // disconnect from the database
                                mysqli_close($conn);
                            }
                            else { echo "ERROR! You must select a .csv file to upload.<br>"; }
                        }   
                        else { echo "ERROR! No upload file was found. Please select a file to upload and try again.<br>"; }
                    ?>
                    </div>
                    <div class="col-2"></div>
                </div>

                <div class="row justify-content-center text-center mt-3">
                    <div class="col-2"><button class="btn btn-primary w-100" onclick="goToCustomerGroups();">Return To Customer Groups</button></div>
                </div>

                <script>function goToCustomerGroups() { window.location.href = "customers_groups.php"; }</script>
            <?php
        }
        else { denyAccess(); }

        // disconnect from the database
        mysqli_close($conn);
    }
    else { goToLogin(); }
?>