<?php
    include("header.php");

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1) 
    { 
        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);
        
        if (isset($PERMISSIONS["ADD_CUSTOMERS"]))
        {
            ?>
                <div class="row text-center">
                    <div class="col-2"></div>
                    <div class="col-8"><h1 class="upload-status-header">Customers Upload Status</h1></div>
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
                                    if (isset($data[0]) && ($data[0] != "Customer Information" && $data[0] != "ID")) // skip the first two rows by looking at cell data
                                    {
                                        // get and clean up the employee's data
                                        if (isset($data[0])) { $id = clean_data($data[0]); } else { $id = null; } // customer ID
                                        if (isset($data[1])) { $name = clean_data($data[1]); } else { $name = null; } // customer name
                                        if (isset($data[2])) { $location_code = clean_data($data[2]); } else { $location_code = null; } // location code
                                        if (isset($data[3])) { $street = clean_data($data[3]); } else { $street = null; } // street
                                        if (isset($data[4])) { $city = clean_data($data[4]); } else { $city = null; } // city
                                        if (isset($data[5])) { $state = clean_data($data[5]); } else { $state = null; } // state
                                        if (isset($data[6])) { $zip = clean_data($data[6]); } else { $zip = null; } // zip
                                        if (isset($data[7])) { $pc_fname = clean_data($data[7]); } else { $pc_fname = null; } // primary contact - first name
                                        if (isset($data[8])) { $pc_lname = clean_data($data[8]); } else { $pc_lname = null; } // primary contact - last name
                                        if (isset($data[9])) { $pc_email = clean_data($data[9]); } else { $pc_email = null; } // primary contact - email
                                        if (isset($data[10])) { $pc_phone = clean_data($data[10]); } else { $pc_phone = null; } // primary contact - phone
                                        if (isset($data[11])) { $pc_title = clean_data($data[11]); } else { $pc_title = null; } // primary contact - title
                                        if (isset($data[12])) { $sc_fname = clean_data($data[12]); } else { $sc_fname = null; } // secondary contact - first name
                                        if (isset($data[13])) { $sc_lname = clean_data($data[13]); } else { $sc_lname = null; } // secondary contact - last name
                                        if (isset($data[14])) { $sc_email = clean_data($data[14]); } else { $sc_email = null; } // secondary contact - email
                                        if (isset($data[15])) { $sc_phone = clean_data($data[15]); } else { $sc_phone = null; } // secondary contact - phone
                                        if (isset($data[16])) { $sc_title = clean_data($data[16]); } else { $sc_title = null; } // secondary contact - title
                                        if (isset($data[17])) { $members = clean_data($data[17]); } else { $members = 0; } // members
                                        if (isset($data[18])) { $contract_folder_id = clean_data($data[18]); } else { $contract_folder_id = null; } // folder ID
                                        if (isset($data[19])) { $invoice_folder_id = clean_data($data[19]); } else { $invoice_folder_id = null; } // folder ID
                                        if (isset($data[20])) { $status = clean_data($data[20]); } else { $status = null; } // status

                                        // verify and convert data from upload to database values if necessary
                                        if ($status == "Inactive") { $DB_status = 0; } else if ($status == "Active") { $DB_status = 1; } else { $DB_status = 0; }

                                        // verify members is numeric; otherwise default to 0
                                        if (!is_numeric($members)) { $members = 0; }

                                        if (($id != null && $id <> "") && ($name != null && $name <> ""))
                                        {
                                            if ($location_code != null && $location_code <> "")
                                            {
                                                if (($street != null && $street <> "") && ($city != null && $city <> "") && ($state != null && $state <> "") && ($zip != null && $zip <> ""))
                                                {
                                                    // check to see if the customer is already in the database or not; if so, edit customer; otherwise, add new customer
                                                    $checkCustomer = mysqli_prepare($conn, "SELECT id, primary_contact_id, secondary_contact_id, address_id FROM customers WHERE id=?");
                                                    mysqli_stmt_bind_param($checkCustomer, "i", $id);
                                                    if (mysqli_stmt_execute($checkCustomer))
                                                    {
                                                        $checkCustomerResult = mysqli_stmt_get_result($checkCustomer);
                                                        if (mysqli_num_rows($checkCustomerResult) > 0) // customer exists; edit current customer details
                                                        {
                                                            // get the current customer's contact and address IDs
                                                            $customerIDs = mysqli_fetch_array($checkCustomerResult);
                                                            if (isset($customerIDs["primary_contact_id"]) && $customerIDs["primary_contact_id"] != null) { $primary_contact_id = $customerIDs["primary_contact_id"]; } else { $primary_contact_id = null; }
                                                            if (isset($customerIDs["secondary_contact_id"]) && $customerIDs["secondary_contact_id"] != null) { $secondary_contact_id = $customerIDs["secondary_contact_id"]; } else { $secondary_contact_id = null; }
                                                            if (isset($customerIDs["address_id"]) && $customerIDs["address_id"] != null) { $address_id = $customerIDs["address_id"]; } else { $address_id = null; }

                                                            // update the customer's current information
                                                            $updateCustomer = mysqli_prepare($conn, "UPDATE customers SET name=?, location_code=?, contract_folder_id=?, invoice_folder_id=?, members=?, active=? WHERE id=?");
                                                            mysqli_stmt_bind_param($updateCustomer, "ssssiii", $name, $location_code, $contract_folder_id, $invoice_folder_id, $members, $DB_status, $id);
                                                            if (mysqli_stmt_execute($updateCustomer)) // successfully updated the customer's basic information; attempt to update contacts and address
                                                            {
                                                                $updated++;
                                                                echo "<span class=\"log-success\">Successfully</span> updated $name.<br>";

                                                                // verify the state is found in the database; check on both full name and abbreviation
                                                                $verifyState = mysqli_prepare($conn, "SELECT id FROM states WHERE state=? OR abbreviation=?");
                                                                mysqli_stmt_bind_param($verifyState, "ss", $state, $state);
                                                                if (mysqli_stmt_execute($verifyState))
                                                                {
                                                                    $verifyStateResult = mysqli_stmt_get_result($verifyState);
                                                                    if (mysqli_num_rows($verifyStateResult) > 0)
                                                                    {
                                                                        $state_id = mysqli_fetch_array($verifyStateResult)["id"];

                                                                        // update the customer address, or insert new address
                                                                        if ($address_id != null) // customer is currently assigned an address ID, update current address
                                                                        {
                                                                            $updateAddress = mysqli_prepare($conn, "UPDATE customer_addresses SET street=?, city=?, state_id=?, zip=? WHERE id=? AND customer_id=?");
                                                                            mysqli_stmt_bind_param($updateAddress, "ssisii", $street, $city, $state_id, $zip, $address_id, $id);
                                                                            if (!mysqli_stmt_execute($updateAddress)) { echo "<span class=\"log-fail\">Failed</span> to update the address for $name.<br>"; }
                                                                        }
                                                                        else // customer is not already assigned an address; insert new address
                                                                        {
                                                                            $addAddress = mysqli_prepare($conn, "INSERT INTO customer_addresses (customer_id, street, city, state_id, zip) VALUES (?, ?, ?, ?, ?)");
                                                                            mysqli_stmt_bind_param($addAddress, "issis", $id, $street, $city, $state_id, $zip);
                                                                            if (mysqli_stmt_execute($addAddress)) // successfully added the address; assign address to the customer
                                                                            {
                                                                                // get the newly created address_id
                                                                                $new_address_id = mysqli_insert_id($conn);

                                                                                $updateAddressID = mysqli_prepare($conn, "UPDATE customers SET address_id=? WHERE id=?");
                                                                                mysqli_stmt_bind_param($updateAddressID, "ii", $new_address_id, $id);
                                                                                if (!mysqli_stmt_execute($updateAddressID)) { echo "<span class=\"log-fail\">Failed</span> to assign $name their address.<br>" ; }
                                                                            }
                                                                            else { echo "<span class=\"log-fail\">Failed</span> to assign $name their address.<br>"; }
                                                                        }
                                                                    }
                                                                    else { echo "<span class=\"log-fail\">Failed</span> to update the address for $name. The state provided was not found in our listings of verified locations.<br>"; }
                                                                }
                                                                else { echo "<span class=\"log-fail\">Failed</span> to update the address for $name. An unknown error has occurred. Please try again later.<br>"; }

                                                                // update customer's primary contact
                                                                if ($primary_contact_id != null) // customer is currently assigned a primary contact; update contact
                                                                {
                                                                    if (($pc_fname != null && $pc_fname <> "") && ($pc_lname != null && $pc_lname <> ""))
                                                                    {
                                                                        $updatePrimaryContact = mysqli_prepare($conn, "UPDATE customer_contacts SET fname=?, lname=?, email=?, phone=?, title=? WHERE id=? AND customer_id=?");
                                                                        mysqli_stmt_bind_param($updatePrimaryContact, "sssssii", $pc_fname, $pc_lname, $pc_email, $pc_phone, $pc_title, $primary_contact_id, $id);
                                                                        if (!mysqli_stmt_execute($updatePrimaryContact)) { echo "<span class=\"log-fail\">Failed</span> to update the primary contact for $name.<br>"; }
                                                                    }
                                                                    else { echo "<span class=\"log-fail\">Failed</span> to update the primary contact for $name. You must provide both a first and last name for the contact.<br>"; }
                                                                }
                                                                else // customer is not assigned a primary contact; add new contact
                                                                {
                                                                    if (($pc_fname != null && $pc_fname <> "") && ($pc_lname != null && $pc_lname <> ""))
                                                                    {
                                                                        $addPrimaryContact = mysqli_prepare($conn, "INSERT INTO customer_contacts (customer_id, fname, lname, email, phone, title) VALUES (?, ?, ?, ?, ?, ?)");
                                                                        mysqli_stmt_bind_param($addPrimaryContact, "isssss", $id, $pc_fname, $pc_lname, $pc_email, $pc_phone, $pc_title);
                                                                        if (mysqli_stmt_execute($addPrimaryContact)) // successfully created the primary contact; assign contact to customer
                                                                        {
                                                                            // get the newly created primary contact id
                                                                            $new_primary_contact_id = mysqli_insert_id($conn);

                                                                            // update the customer's primary contact id
                                                                            $updatePrimaryContactID = mysqli_prepare($conn, "UPDATE customers SET primary_contact_id=? WHERE id=?");
                                                                            mysqli_stmt_bind_param($updatePrimaryContactID, "ii", $new_primary_contact_id, $id);
                                                                            if (!mysqli_stmt_execute($updatePrimaryContactID)) { echo "<span class=\"log-fail\">Failed</span> to assign $name their primary contact.<br>"; }
                                                                        }
                                                                        else { echo "<span class=\"log-fail\">Failed</span> to add the primary contact for $name.<br>"; }
                                                                    }
                                                                }

                                                                // update customer's secondary contact
                                                                if ($secondary_contact_id != null) // customer is currently assigned a secondary contact; update contact
                                                                {
                                                                    if (($sc_fname != null && $sc_fname <> "") && ($sc_lname != null && $sc_lname <> ""))
                                                                    {
                                                                        $updateSecondaryContact = mysqli_prepare($conn, "UPDATE customer_contacts SET fname=?, lname=?, email=?, phone=?, title=? WHERE id=? AND customer_id=?");
                                                                        mysqli_stmt_bind_param($updateSecondaryContact, "sssssii", $sc_fname, $sc_lname, $sc_email, $sc_phone, $sc_title, $secondary_contact_id, $id);
                                                                        if (!mysqli_stmt_execute($updateSecondaryContact)) { echo "<span class=\"log-fail\">Failed</span> to update the secondary contact for $name.<br>"; }
                                                                    }
                                                                    else { echo "<span class=\"log-fail\">Failed</span> to update the secondary contact for $name. You must provide both a first and last name for the contact.<br>"; }
                                                                }
                                                                else // customer is not assigned a secondary contact; add new contact
                                                                {
                                                                    if (($sc_fname != null && $sc_fname <> "") && ($sc_lname != null && $sc_lname <> ""))
                                                                    {
                                                                        $addSecondaryContact = mysqli_prepare($conn, "INSERT INTO customer_contacts (customer_id, fname, lname, email, phone, title) VALUES (?, ?, ?, ?, ?, ?)");
                                                                        mysqli_stmt_bind_param($addSecondaryContact, "isssss", $id, $sc_fname, $sc_lname, $sc_email, $sc_phone, $sc_title);
                                                                        if (mysqli_stmt_execute($addSecondaryContact)) // successfully created the secondary contact; assign contact to customer
                                                                        {
                                                                            // get the newly created secondary contact id
                                                                            $new_secondary_contact_id = mysqli_insert_id($conn);

                                                                            // update the customer's secondary contact id
                                                                            $updateSecondaryContactID = mysqli_prepare($conn, "UPDATE customers SET secondary_contact_id=? WHERE id=?");
                                                                            mysqli_stmt_bind_param($updateSecondaryContactID, "ii", $new_secondary_contact_id, $id);
                                                                            if (!mysqli_stmt_execute($updateSecondaryContactID)) { echo "<span class=\"log-fail\">Failed</span> to assign $name their secondary contact.<br>"; }
                                                                        }
                                                                        else { echo "<span class=\"log-fail\">Failed</span> to add the secondary contact for $name.<br>"; }
                                                                    }
                                                                }
                                                            }
                                                            else 
                                                            { 
                                                                $errors++;
                                                                echo "<span class=\"log-fail\">Failed</span> to edit $name. An unknwon error has occurred.<br>"; 
                                                            }
                                                        }
                                                        else // customer does not exist; insert new customer
                                                        {
                                                            $addCustomer = mysqli_prepare($conn, "INSERT INTO customers (id, name, location_code, contract_folder_id, invoice_folder_id, members, active) VALUES (?, ?, ?, ?, ?, ?, ?)");
                                                            mysqli_stmt_bind_param($addCustomer, "issssii", $id, $name, $location_code, $contract_folder_id, $invoice_folder_id, $members, $DB_status);
                                                            if (mysqli_stmt_execute($addCustomer)) // successfully added the customer; attempt to add address and contacts
                                                            {
                                                                echo "<span class=\"log-success\">Successfully</span> added $name.<br>";
                                                                $inserted++;

                                                                // verify the state is found in the database; check on both full name and abbreviation
                                                                $verifyState = mysqli_prepare($conn, "SELECT id FROM states WHERE state=? OR abbreviation=?");
                                                                mysqli_stmt_bind_param($verifyState, "ss", $state, $state);
                                                                if (mysqli_stmt_execute($verifyState))
                                                                {
                                                                    $verifyStateResult = mysqli_stmt_get_result($verifyState);
                                                                    if (mysqli_num_rows($verifyStateResult) > 0)
                                                                    {
                                                                        $state_id = mysqli_fetch_array($verifyStateResult)["id"];

                                                                        // add the employee address
                                                                        $addAddress = mysqli_prepare($conn, "INSERT INTO customer_addresses (customer_id, street, city, state_id, zip) VALUES (?, ?, ?, ?, ?)");
                                                                        mysqli_stmt_bind_param($addAddress, "issis", $id, $street, $city, $state_id, $zip);
                                                                        if (mysqli_stmt_execute($addAddress)) // successfully added the address; assign address ID to employee
                                                                        {
                                                                            $address_id = mysqli_insert_id($conn);
                                                                            
                                                                            $updateCustomerAddress = mysqli_prepare($conn, "UPDATE customers SET address_id=? WHERE id=?");
                                                                            mysqli_stmt_bind_param($updateCustomerAddress, "ii", $address_id, $id);
                                                                            if (!mysqli_stmt_execute($updateCustomerAddress)) { echo "<span class=\"log-fail\">Failed</span> to assign $name their address.<br>"; }
                                                                        }
                                                                        else { echo "<span class=\"log-fail\">Failed</span> to assign $name their address.<br>"; }
                                                                    }
                                                                    else { echo "<span class=\"log-fail\">Failed</span> to assign $name their address. The state provided was not found in our verified states listing.<br>"; }
                                                                }
                                                                else { echo "<span class=\"log-fail\">Failed</span> to assign $name their address.<br>"; }
                                                                
                                                                // if a first and last name are provided for the primary contact; insert
                                                                if (($pc_fname != null && $pc_fname <> "") && ($pc_lname != null && $pc_lname <> ""))
                                                                {
                                                                    $addPrimaryContact = mysqli_prepare($conn, "INSERT INTO customer_contacts (customer_id, fname, lname, email, phone, title) VALUES (?, ?, ?, ?, ?, ?)");
                                                                    mysqli_stmt_bind_param($addPrimaryContact, "isssss", $id, $pc_fname, $pc_lname, $pc_email, $pc_phone, $pc_title);
                                                                    if (mysqli_stmt_execute($addPrimaryContact)) // successfully added the primary contact; assign to customer
                                                                    {
                                                                        // get new primary contact id
                                                                        $primary_contact_id = mysqli_insert_id($conn);

                                                                        $updatePrimaryID = mysqli_prepare($conn, "UPDATE customers SET primary_contact_id=? WHERE id=?");
                                                                        mysqli_stmt_bind_param($updatePrimaryID, "ii", $primary_contact_id, $id);
                                                                        if (!mysqli_stmt_execute($updatePrimaryID)) { echo "<span class=\"log-fail\">Failed</span> to assign $name their primary contact.<br>"; }
                                                                    }
                                                                    else { echo "<span class=\"log-fail\">Failed</span> to create the primary contact for $name.<br>"; }
                                                                }

                                                                // if a first and last name are provided for the secondary contact; insert
                                                                if (($sc_fname != null && $sc_fname <> "") && ($sc_lname != null && $sc_lname <> ""))
                                                                {
                                                                    $addSecondaryContact = mysqli_prepare($conn, "INSERT INTO customer_contacts (customer_id, fname, lname, email, phone, title) VALUES (?, ?, ?, ?, ?, ?)");
                                                                    mysqli_stmt_bind_param($addSecondaryContact, "isssss", $id, $sc_fname, $sc_lname, $sc_email, $sc_phone, $sc_title);
                                                                    if (mysqli_stmt_execute($addSecondaryContact)) // successfully added the secondary contact; assign to customer
                                                                    {
                                                                        // get new secondary contact id
                                                                        $secondary_contact_id = mysqli_insert_id($conn);

                                                                        $updateSecondaryID = mysqli_prepare($conn, "UPDATE customers SET secondary_contact_id=? WHERE id=?");
                                                                        mysqli_stmt_bind_param($updateSecondaryID, "ii", $secondary_contact_id, $id);
                                                                        if (!mysqli_stmt_execute($updateSecondaryID)) { echo "<span class=\"log-fail\">Failed</span> to assign $name their secondary contact.<br>"; }
                                                                    }
                                                                    else { echo "<span class=\"log-fail\">Failed</span> to create the secondary contact for $name.<br>"; }
                                                                }
                                                            }
                                                            else 
                                                            { 
                                                                $errors++;
                                                                echo "<span class=\"log-fail\">Failed</span> to upload $name. An unknown error has occurred.<br>";
                                                            }
                                                        }
                                                    }
                                                    else 
                                                    { 
                                                        $errors++;
                                                        echo "<span class=\"log-fail\">Failed</span> to upload $name. An unknown error has occurred.<br>"; 
                                                    }
                                                }
                                                else 
                                                { 
                                                    $errors++;
                                                    echo "<span class=\"log-fail\">Failed</span> to upload $name. You must provide the customer an address.<br>"; 
                                                }
                                            }
                                            else 
                                            { 
                                                $errors++;
                                                echo "<span class=\"log-fail\">Failed</span> to upload $name. You must provide a location code.<br>"; 
                                            }
                                        }
                                        else 
                                        { 
                                            $errors++;
                                            echo "<span class=\"log-fail\">Failed</span> to upload a customer. You must provide both a customer ID and name.<br>"; 
                                        }
                                    }
                                }

                                echo "<i class=\"fa-solid fa-check\"></i> Upload complete!";

                                // log upload
                                $total_successes = $inserted + $updated;
                                $message = "Successfully uploaded $total_successes customers ($inserted inserts; $updated updates). ";
                                if ($errors > 0) { $message .= "Failed to upload $errors customers. "; }
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
        }
        else { denyAccess(); }

        // disconnect from the database
        mysqli_close($conn);
    }
    else { goToLogin(); }
?>