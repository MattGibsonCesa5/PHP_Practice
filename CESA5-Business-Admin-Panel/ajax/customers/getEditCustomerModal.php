<?php
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get additional required files
        include("../../includes/config.php");
        include("../../includes/functions.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "EDIT_CUSTOMERS"))
        {
            // get the customer ID from POST
            if (isset($_POST["customer_id"]) && $_POST["customer_id"] <> "") { $customer_id = $_POST["customer_id"]; } else { $customer_id = null; }

            if ($customer_id != null && is_numeric($customer_id))
            {
                // get customer details from the database based on the customer ID
                $getCustomerDetails = mysqli_prepare($conn, "SELECT * FROM customers WHERE id=?");
                mysqli_stmt_bind_param($getCustomerDetails, "i", $customer_id);
                if (mysqli_stmt_execute($getCustomerDetails))
                {
                    $result = mysqli_stmt_get_result($getCustomerDetails);
                    $customerDetails = mysqli_fetch_array($result);

                    // build the address to be displayed 
                    $address_id = $customerDetails["address_id"];
                    $getAddress = mysqli_prepare($conn, "SELECT ca.street, ca.city, s.id, s.state, ca.zip FROM customer_addresses ca JOIN states s ON ca.state_id=s.id WHERE ca.id=? AND ca.customer_id=?");
                    mysqli_stmt_bind_param($getAddress, "ii", $address_id, $customer_id);
                    if (mysqli_stmt_execute($getAddress))
                    {
                        $result = mysqli_stmt_get_result($getAddress);
                        $address = mysqli_fetch_array($result);

                        $street = $address["street"];
                        $city = $address["city"];
                        $state_id = $address["id"];
                        $zip = $address["zip"];
                    }

                    // build the primary contact details to be displayed
                    $primary_contact_id = $customerDetails["primary_contact_id"];
                    $getPrimaryContact = mysqli_prepare($conn, "SELECT * FROM customer_contacts WHERE id=? AND customer_id=?");
                    mysqli_stmt_bind_param($getPrimaryContact, "ii", $primary_contact_id, $customer_id);
                    if (mysqli_stmt_execute($getPrimaryContact))
                    {
                        $result = mysqli_stmt_get_result($getPrimaryContact);
                        if (mysqli_num_rows($result) > 0)
                        {
                            $contactDetails = mysqli_fetch_array($result);
                            if ($contactDetails["fname"] <> "") { $pc_fname = $contactDetails["fname"]; } else { $pc_fname = ""; }
                            if ($contactDetails["lname"] <> "") { $pc_lname = $contactDetails["lname"]; } else { $pc_lname = ""; }
                            if ($contactDetails["email"] <> "") { $pc_email = $contactDetails["email"]; } else { $pc_email = ""; }
                            if ($contactDetails["phone"] <> "") { $pc_phone = $contactDetails["phone"]; } else { $pc_phone = ""; }
                            if ($contactDetails["title"] <> "") { $pc_title = $contactDetails["title"]; } else { $pc_title = ""; }
                        }
                        else
                        {
                            $pc_fname = "";
                            $pc_lname = "";
                            $pc_email = "";
                            $pc_phone = "";
                            $pc_title = "";
                        }
                    }

                    // build the secondary contact details to be displayed
                    $secondary_contact_id = $customerDetails["secondary_contact_id"];
                    $getSecondaryContact = mysqli_prepare($conn, "SELECT * FROM customer_contacts WHERE id=? AND customer_id=?");
                    mysqli_stmt_bind_param($getSecondaryContact, "ii", $secondary_contact_id, $customer_id);
                    if (mysqli_stmt_execute($getSecondaryContact))
                    {
                        $result = mysqli_stmt_get_result($getSecondaryContact);
                        if (mysqli_num_rows($result) > 0)
                        {
                            $contactDetails = mysqli_fetch_array($result);
                            if ($contactDetails["fname"] <> "") { $sc_fname = $contactDetails["fname"]; } else { $sc_fname = ""; }
                            if ($contactDetails["lname"] <> "") { $sc_lname = $contactDetails["lname"]; } else { $sc_lname = ""; }
                            if ($contactDetails["email"] <> "") { $sc_email = $contactDetails["email"]; } else { $sc_email = ""; }
                            if ($contactDetails["phone"] <> "") { $sc_phone = $contactDetails["phone"]; } else { $sc_phone = ""; }
                            if ($contactDetails["title"] <> "") { $sc_title = $contactDetails["title"]; } else { $sc_title = ""; }
                        }
                        else
                        {
                            $sc_fname = "";
                            $sc_lname = "";
                            $sc_email = "";
                            $sc_phone = ""; 
                            $sc_title = ""; 
                        }
                    }

                    ?>
                        <div class="modal fade" tabindex="-1" role="dialog" id="editCustomerModal" data-bs-backdrop="static" aria-labelledby="editCustomerModalLabel" aria-hidden="true">
                            <div class="modal-dialog modal-lg" role="document">
                                <div class="modal-content">
                                    <div class="modal-header primary-modal-header">
                                        <h5 class="modal-title primary-modal-title" id="editCustomerModalLabel">Edit Customer</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>

                                    <div class="modal-body">
                                        <!-- Customer Details -->
                                        <fieldset class="form-group border p-3 mb-3">
                                            <legend class="w-auto px-2 m-0 float-none fieldset-legend">Customer Details</legend>

                                            <div class="row align-items-center my-2">
                                                <div class="col-3 text-end"><label for="edit-customer_id"><span class="required-field">*</span> Customer ID:</label></div>
                                                <div class="col-9"><input type="text" class="form-control w-100" id="edit-customer_id" name="edit-customer_id" value="<?php echo $customer_id; ?>" disabled></div>
                                            </div>

                                            <div class="row align-items-center my-2">
                                                <div class="col-3 text-end"><label for="edit-customer_name"><span class="required-field">*</span> Name:</label></div>
                                                <div class="col-9"><input type="text" class="form-control w-100" id="edit-customer_name" name="edit-customer_name" value="<?php echo $customerDetails["name"]; ?>" required></div>
                                            </div>

                                            <div class="row align-items-center my-2">
                                                <div class="col-3 text-end"><label for="edit-location_code"><span class="required-field">*</span> Location Code:</label></div>
                                                <div class="col-9"><input type="text" class="form-control w-100" id="edit-location_code" name="edit-location_code" value="<?php echo $customerDetails["location_code"]; ?>" maxlength="3" required></div>
                                            </div>
                                            
                                            <div class="row align-items-center my-2">
                                                <div class="col-3 text-end"><label for="edit-members">Members:</label></div>
                                                <div class="col-9"><input type="number" class="form-control w-100" id="edit-members" name="edit-members" value="<?php echo $customerDetails["members"]; ?>"></div>
                                            </div>

                                            <div class="row align-items-center my-2">
                                                <div class="col-3 text-end"><label for="edit-invoice_number">Invoice Number:</label></div>
                                                <div class="col-9"><input type="text" class="form-control w-100" id="edit-invoice_number" name="edit-invoice_number" value="<?php echo $customerDetails["invoice_number"]; ?>" required></div>
                                            </div>

                                            <div class="row align-items-center my-2">
                                                <div class="col-3 text-end"><label for="edit-sc-folder_id">Service Contract Folder ID:</label></div>
                                                <div class="col-9"><input type="text" class="form-control w-100" id="edit-sc-folder_id" name="edit-sc-folder_id" value="<?php echo $customerDetails["contract_folder_id"]; ?>"></div>
                                            </div>

                                            <div class="row align-items-center my-2">
                                                <div class="col-3 text-end"><label for="edit-qi-folder_id">Quarterly Invoice Folder ID:</label></div>
                                                <div class="col-9"><input type="text" class="form-control w-100" id="edit-qi-folder_id" name="edit-qi-folder_id" value="<?php echo $customerDetails["invoice_folder_id"]; ?>"></div>
                                            </div>

                                            <div class="row align-items-center my-2">
                                                <div class="col-3 text-end"><label for="edit-cb-folder_id">SPED Billing Details Folder ID:</label></div>
                                                <div class="col-9"><input type="text" class="form-control w-100" id="edit-cb-folder_id" name="edit-cb-folder_id" value="<?php echo $customerDetails["caseload_billing_folder_id"]; ?>"></div>
                                            </div>
                                        </fieldset>

                                        <!-- Customer Address -->
                                        <fieldset class="form-group border p-3 mb-3">
                                            <legend class="w-auto px-2 m-0 float-none fieldset-legend">Customer Address</legend>

                                            <div class="row align-items-center my-2">
                                                <div class="col-3 text-end"><label for="edit-address_street"><span class="required-field">*</span> Street:</label></div>
                                                <div class="col-9"><input type="text" class="form-control w-100" id="edit-address_street" name="edit-address_street" value="<?php echo $street; ?>" required></div>
                                            </div>

                                            <div class="row align-items-center my-2">
                                                <div class="col-3 text-end"><label for="edit-address_city"><span class="required-field">*</span> City:</label></div>
                                                <div class="col-9"><input type="text" class="form-control w-100" id="edit-address_city" name="edit-address_city" value="<?php echo $city; ?>" required></div>
                                            </div>

                                            <div class="row align-items-center my-2">
                                                <div class="col-3 text-end"><label for="edit-address_state"><span class="required-field">*</span> State:</label></div>
                                                <div class="col-9">
                                                    <select class="form-select w-100" id="edit-address_state" name="edit-address_state" required>
                                                        <option value=0></option>
                                                        <?php
                                                            $getStates = mysqli_query($conn, "SELECT id, state FROM states");
                                                            while ($state = mysqli_fetch_array($getStates)) 
                                                            { 
                                                                if ($state["id"] == $state_id){ echo "<option value='".$state["id"]."' selected>".$state["state"]."</option>"; }
                                                                else { echo "<option value='".$state["id"]."'>".$state["state"]."</option>"; }
                                                            }
                                                        ?>
                                                    </select> 
                                                </div>
                                            </div>

                                            <div class="row align-items-center my-2">
                                                <div class="col-3 text-end"><label for="edit-address_zip"><span class="required-field">*</span> Zip Code:</label></div>
                                                <div class="col-9"><input type="text" class="form-control w-100" id="edit-address_zip" name="edit-address_zip" value="<?php echo $zip; ?>" required></div>
                                            </div>
                                        </fieldset>

                                        <!-- Primary Contact -->
                                        <fieldset class="form-group border p-3 mb-3">
                                            <legend class="w-auto px-2 m-0 float-none fieldset-legend">Primary Contact</legend>

                                            <div class="row align-items-center my-2">
                                                <div class="col-3 text-end"><label for="edit-pc_fname">First Name:</label></div>
                                                <div class="col-9"><input type="text" class="form-control w-100" id="edit-pc_fname" name="edit-pc_fname" value="<?php echo $pc_fname; ?>" required></div>
                                            </div>

                                            <div class="row align-items-center my-2">
                                                <div class="col-3 text-end"><label for="edit-pc_lname">Last Name:</label></div>
                                                <div class="col-9"><input type="text" class="form-control w-100" id="edit-pc_lname" name="edit-pc_lname" value="<?php echo $pc_lname; ?>" required></div>
                                            </div>

                                            <div class="row align-items-center my-2">
                                                <div class="col-3 text-end"><label for="edit-pc_email">Email:</label></div>
                                                <div class="col-9"><input type="text" class="form-control w-100" id="edit-pc_email" name="edit-pc_email" value="<?php echo $pc_email; ?>" required></div>
                                            </div>

                                            <div class="row align-items-center my-2">
                                                <div class="col-3 text-end"><label for="edit-pc_phone">Phone:</label></div>
                                                <div class="col-9"><input type="text" class="form-control w-100" id="edit-pc_phone" name="edit-pc_phone" value="<?php echo $pc_phone; ?>"></div>
                                            </div>

                                            <div class="row align-items-center my-2">
                                                <div class="col-3 text-end"><label for="edit-pc_title">Title:</label></div>
                                                <div class="col-9"><input type="text" class="form-control w-100" id="edit-pc_title" name="edit-pc_title" value="<?php echo $pc_title; ?>" required></div>
                                            </div>
                                        </fieldset>

                                        <!-- Secondary Contact -->
                                        <fieldset class="form-group border p-3 mb-3">
                                            <legend class="w-auto px-2 m-0 float-none fieldset-legend">Secondary Contact</legend>

                                            <div class="row align-items-center my-2">
                                                <div class="col-3 text-end"><label for="edit-sc_fname">First Name:</label></div>
                                                <div class="col-9"><input type="text" class="form-control w-100" id="edit-sc_fname" name="edit-sc_fname" value="<?php echo $sc_fname; ?>"></div>
                                            </div>

                                            <div class="row align-items-center my-2">
                                                <div class="col-3 text-end"><label for="edit-sc_lname">Last Name:</label></div>
                                                <div class="col-9"><input type="text" class="form-control w-100" id="edit-sc_lname" name="edit-sc_lname" value="<?php echo $sc_lname; ?>"></div>
                                            </div>

                                            <div class="row align-items-center my-2">
                                                <div class="col-3 text-end"><label for="edit-sc_email">Email:</label></div>
                                                <div class="col-9"><input type="text" class="form-control w-100" id="edit-sc_email" name="edit-sc_email" value="<?php echo $sc_email; ?>"></div>
                                            </div>

                                            <div class="row align-items-center my-2">
                                                <div class="col-3 text-end"><label for="edit-sc_phone">Phone:</label></div>
                                                <div class="col-9"><input type="text" class="form-control w-100" id="edit-sc_phone" name="edit-sc_phone" value="<?php echo $sc_phone; ?>"></div>
                                            </div>

                                            <div class="row align-items-center my-2">
                                                <div class="col-3 text-end"><label for="edit-sc_title">Title:</label></div>
                                                <div class="col-9"><input type="text" class="form-control w-100" id="edit-sc_title" name="edit-sc_title" value="<?php echo $sc_title; ?>" required></div>
                                            </div>
                                        </fieldset>
                                    </div>

                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-primary" onclick="editCustomer(<?php echo $customer_id; ?>);"><i class="fa-solid fa-floppy-disk"></i> Save Customer</button>
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> Close</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php
                }            
            }
        }

        // disconect from the database
        mysqli_close($conn);
    }
?>