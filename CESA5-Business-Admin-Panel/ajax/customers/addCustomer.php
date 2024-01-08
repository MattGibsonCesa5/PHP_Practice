<?php
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get additional required files
        include("../../includes/config.php");
        include("../../includes/functions.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "ADD_CUSTOMERS"))
        {
            // get customer details from POST
            if (isset($_POST["customer_id"]) && $_POST["customer_id"] <> "") { $customer_id = $_POST["customer_id"]; } else { $customer_id = null; }
            if (isset($_POST["customer_name"]) && $_POST["customer_name"] <> "") { $customer_name = $_POST["customer_name"]; } else { $customer_name = null; }
            if (isset($_POST["location_code"]) && $_POST["location_code"] <> "") { $location_code = $_POST["location_code"]; } else { $location_code = null; }
            if (isset($_POST["members"]) && is_numeric($_POST["members"])) { $members = $_POST["members"]; } else { $members = 0; }
            if (isset($_POST["invoice_number"]) && $_POST["invoice_number"] <> "") { $invoice_number = $_POST["invoice_number"]; } else { $invoice_number = null; }
            if (isset($_POST["contract_folder_id"]) && $_POST["contract_folder_id"] <> "") { $contract_folder_id = $_POST["contract_folder_id"]; } else { $contract_folder_id = null; }
            if (isset($_POST["invoice_folder_id"]) && $_POST["invoice_folder_id"] <> "") { $invoice_folder_id = $_POST["invoice_folder_id"]; } else { $invoice_folder_id = null; }
            if (isset($_POST["caseload_billing_folder_id"]) && $_POST["caseload_billing_folder_id"] <> "") { $caseload_billing_folder_id = $_POST["caseload_billing_folder_id"]; } else { $caseload_billing_folder_id = null; }

            // get customer address from POST
            if (isset($_POST["address_street"]) && $_POST["address_street"] <> "") { $address_street = $_POST["address_street"]; } else { $address_street = null; }
            if (isset($_POST["address_city"]) && $_POST["address_city"] <> "") { $address_city = $_POST["address_city"]; } else { $address_city = null; }
            if (isset($_POST["address_state"]) && $_POST["address_state"] <> "") { $address_state = $_POST["address_state"]; } else { $address_state = null; }
            if (isset($_POST["address_zip"]) && $_POST["address_zip"] <> "") { $address_zip = $_POST["address_zip"]; } else { $address_zip = null; }

            // get primary contact from post
            if (isset($_POST["pc_fname"]) && $_POST["pc_fname"] <> "") { $pc_fname = clean_data($_POST["pc_fname"]); } else { $pc_fname = null; }
            if (isset($_POST["pc_lname"]) && $_POST["pc_lname"] <> "") { $pc_lname = clean_data($_POST["pc_lname"]); } else { $pc_lname = null; }
            if (isset($_POST["pc_email"]) && $_POST["pc_email"] <> "") { $pc_email = clean_data($_POST["pc_email"]); } else { $pc_email = null; }
            if (isset($_POST["pc_phone"]) && $_POST["pc_phone"] <> "") { $pc_phone = clean_data($_POST["pc_phone"]); } else { $pc_phone = null; }
            if (isset($_POST["pc_title"]) && $_POST["pc_title"] <> "") { $pc_title = clean_data($_POST["pc_title"]); } else { $pc_title = null; }

            // get secondary contact from post
            if (isset($_POST["sc_fname"]) && $_POST["sc_fname"] <> "") { $sc_fname = clean_data($_POST["sc_fname"]); } else { $sc_fname = null; }
            if (isset($_POST["sc_lname"]) && $_POST["sc_lname"] <> "") { $sc_lname = clean_data($_POST["sc_lname"]); } else { $sc_lname = null; }
            if (isset($_POST["sc_email"]) && $_POST["sc_email"] <> "") { $sc_email = clean_data($_POST["sc_email"]); } else { $sc_email = null; }
            if (isset($_POST["sc_phone"]) && $_POST["sc_phone"] <> "") { $sc_phone = clean_data($_POST["sc_phone"]); } else { $sc_phone = null; }
            if (isset($_POST["sc_title"]) && $_POST["sc_title"] <> "") { $sc_title = clean_data($_POST["sc_title"]); } else { $sc_title = null; }

            if (is_numeric($customer_id))
            {
                if ($customer_id != null && $customer_name != null)
                {
                    if ($location_code != null)
                    {
                        if (($address_street <> "" && $address_street != null) && ($address_city <> "" && $address_city != null) && ($address_state <> "" && $address_state != null) && ($address_zip <> "" && $address_zip != null))
                        {
                            // verify that the state ID exists
                            $checkState = mysqli_prepare($conn, "SELECT id FROM states WHERE id=?");
                            mysqli_stmt_bind_param($checkState, "i", $address_state);
                            if (mysqli_stmt_execute($checkState))
                            {
                                $stateResult = mysqli_stmt_get_result($checkState);
                                if (mysqli_num_rows($stateResult) > 0) // state ID is valid; continue with customer creation
                                {
                                    // check to see if a customer with the ID already exists
                                    $checkID = mysqli_prepare($conn, "SELECT id FROM customers WHERE id=?");
                                    mysqli_stmt_bind_param($checkID, "i", $customer_id);
                                    if (mysqli_stmt_execute($checkID))
                                    {
                                        $checkIDResult = mysqli_stmt_get_result($checkID);
                                        if (mysqli_num_rows($checkIDResult) == 0) // ID is unique; proceed with customer creation
                                        {
                                            // create the new customer
                                            $query = mysqli_prepare($conn, "INSERT INTO customers (id, name, location_code, invoice_number, contract_folder_id, invoice_folder_id, caseload_billing_folder_id, members) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                                            mysqli_stmt_bind_param($query, "issssssi", $customer_id, $customer_name, $location_code, $invoice_number, $contract_folder_id, $invoice_folder_id, $caseload_billing_folder_id, $members);
                                            if (mysqli_stmt_execute($query)) // successfulyl created the customer
                                            {
                                                echo "<span class=\"log-success\">Successfully</span> created the customer.<br>";

                                                // create the customer address
                                                $query = mysqli_prepare($conn, "INSERT INTO customer_addresses (customer_id, street, city, state_id, zip) VALUES (?, ?, ?, ?, ?)");
                                                mysqli_stmt_bind_param($query, "issis", $customer_id, $address_street, $address_city, $address_state, $address_zip);
                                                if (mysqli_stmt_execute($query))
                                                {
                                                    // assign the address and contacts to the customer
                                                    $address_id = mysqli_insert_id($conn);
                                                    $query = mysqli_prepare($conn, "UPDATE customers SET address_id=? WHERE id=?");
                                                    mysqli_stmt_bind_param($query, "ii", $address_id, $customer_id);
                                                    mysqli_stmt_execute($query);
                                                }
                                                else { echo "<span class=\"log-fail\">Failed</span> to assign the customer the address provided.<br>"; }

                                                // create the primary contact
                                                if (($pc_fname <> "" && $pc_fname != null) && ($pc_lname <> "" && $pc_lname != null))
                                                {
                                                    $query = mysqli_prepare($conn, "INSERT INTO customer_contacts (customer_id, fname, lname, email, phone, title) VALUES (?, ?, ?, ?, ?, ?)");
                                                    mysqli_stmt_bind_param($query, "isssss", $customer_id, $pc_fname, $pc_lname, $pc_email, $pc_phone, $pc_title);
                                                    if (mysqli_stmt_execute($query))
                                                    {
                                                        $primary_contact_id = mysqli_insert_id($conn);
                                                        $query = mysqli_prepare($conn, "UPDATE customers SET primary_contact_id=? WHERE id=?");
                                                        mysqli_stmt_bind_param($query, "ii", $primary_contact_id, $customer_id);
                                                        if (!mysqli_stmt_execute($query)) { echo "<span class=\"log-fail\">Failed</span> to assign the customer the primary contact.<br>"; }
                                                    }
                                                }
                                                
                                                // create the secondary contact if provided
                                                if (($sc_fname <> "" && $sc_fname != null) && ($sc_lname <> "" && $sc_lname != null))
                                                {
                                                    $query = mysqli_prepare($conn, "INSERT INTO customer_contacts (customer_id, fname, lname, email, phone, title) VALUES (?, ?, ?, ?, ?, ?)");
                                                    mysqli_stmt_bind_param($query, "isssss", $customer_id, $sc_fname, $sc_lname, $sc_email, $sc_phone, $sc_title);
                                                    if (mysqli_stmt_execute($query))
                                                    {
                                                        $secondary_contact_id = mysqli_insert_id($conn);
                                                        $query = mysqli_prepare($conn, "UPDATE customers SET secondary_contact_id=? WHERE id=?");
                                                        mysqli_stmt_bind_param($query, "ii", $secondary_contact_id, $customer_id);
                                                        if (!mysqli_stmt_execute($query)) { echo "<span class=\"log-fail\">Failed</span> to assign the customer the secondary contact.<br>"; }
                                                    }
                                                }

                                                // log customer creation
                                                $message = "Successfully created the customer with the ID of $customer_id. ";
                                                $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                                                mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                                                mysqli_stmt_execute($log);
                                            }
                                            else { echo "<span class=\"log-fail\">Failed</span> to create the customer.<br>"; }
                                        }
                                        else { echo "<span class=\"log-fail\">Failed</span> to create the customer: a customer with the ID provided already exists!<br>"; }
                                    }
                                    else { echo "<span class=\"log-fail\">Failed</span> to create the customer. An unknown error has occurred. Please try again later.<br>"; }
                                }
                                else { echo "<span class=\"log-fail\">Failed</span> to create the customer. The state selected was invalid!<br>"; }
                            }
                            else { echo "<span class=\"log-fail\">Failed</span> to create the customer. An unknown error has occurred. Please try again later.<br>"; }
                        }
                        else { echo "<span class=\"log-fail\">Failed</span> to create the customer. You must provide the customer an address.<br>"; }
                    }
                    else { echo "<span class=\"log-fail\">Failed</span> to create the customer. You must provide a location code.<br>"; }
                }
                else { echo "<span class=\"log-fail\">Failed</span> to create the customer. You must provide both a customer name and ID.<br>"; }
            }
            else { echo "<span class=\"log-fail\">Failed</span> to create the customer. The customer ID must be an integer.<br>"; }
        }
        else { echo "<span class=\"log-fail\">Failed</span> to add the customer. Your account does not have permission to add customers!<br>"; }

        // disconnect from the database
        mysqli_close($conn);
    }
?>