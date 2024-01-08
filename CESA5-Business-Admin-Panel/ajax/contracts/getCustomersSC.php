<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get additional required files
        include("../../includes/config.php");
        include("../../includes/functions.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "BUILD_SERVICE_CONTRACTS"))
        {
            // get a list of all customers
            $customers = [];
            $getCustomers = mysqli_query($conn, "SELECT * FROM customers");
            while ($customer = mysqli_fetch_array($getCustomers)) 
            { 
                $temp = [];
                
                $customer_id = $customer["id"];
                $temp["id"] = $customer_id;
                $temp["name"] = $customer["name"];
                $temp["location_code"] = $customer["location_code"];
                $temp["invoice_number"] = $customer["invoice_number"];
                $temp["members"] = $customer["members"];
                $temp["isBuild"] = $customer["build_service_contract"];
                
                // build the folders column
                $folders_display = "";
                if ($customer["contract_folder_id"] == null || $customer["contract_folder_id"] == "") { $display_sc_folder = "<span class=\"missing-field\">No folder assigned</span>"; }
                else { $display_sc_folder = $customer["contract_folder_id"]; }
                $folders_display .= "$display_sc_folder";
                $temp["folders"] = $folders_display;
                
                // build the address to be displayed
                $address_id = $customer["address_id"];
                // get the address from the address ID
                $getAddress = mysqli_prepare($conn, "SELECT ca.street, ca.city, s.abbreviation, ca.zip FROM customer_addresses ca JOIN states s ON ca.state_id=s.id WHERE ca.id=? AND ca.customer_id=?");
                mysqli_stmt_bind_param($getAddress, "ii", $address_id, $customer_id);
                if (mysqli_stmt_execute($getAddress))
                {
                    $result = mysqli_stmt_get_result($getAddress);
                    $addressDetails = mysqli_fetch_array($result);
                    
                    if (isset($addressDetails))
                    {
                        $street = $addressDetails["street"];
                        $city = $addressDetails["city"];
                        $state = $addressDetails["abbreviation"];
                        $zip = $addressDetails["zip"];

                        $address = $street . "<br>" . $city . ", " . $state . " " . $zip;
                        $temp["address"] = $address;
                    }
                    else { $temp["address"] = "<span class=\"missing-field\">Unknown</span>"; }
                }
                else { $temp["address"] = "<span class=\"missing-field\">No address provided</span>"; }

                // build the primary contact to be displayed
                $primary_contact_id = $customer["primary_contact_id"];
                $getPrimaryContact = mysqli_prepare($conn, "SELECT * FROM customer_contacts WHERE id=? AND customer_id=?");
                mysqli_stmt_bind_param($getPrimaryContact, "ii", $primary_contact_id, $customer_id);
                if (mysqli_stmt_execute($getPrimaryContact))
                {
                    $result = mysqli_stmt_get_result($getPrimaryContact);
                    if (mysqli_num_rows($result) > 0)
                    {
                        $contactDetails = mysqli_fetch_array($result);
                        
                        $fname = $contactDetails["fname"];
                        $lname = $contactDetails["lname"];
                        $email = $contactDetails["email"];
                        $phone = $contactDetails["phone"];
                        $title = $contactDetails["title"];
                        
                        if ($fname != "" && $lname != "")
                        {
                            $primary_contact_name = $fname . " " . $lname;
                            $primary_contact_details = "";
                            if ($title != "" && $title != null) { $primary_contact_details .= $title; } else { $primary_contact_details .= "<span class=\"missing-field\">No contact title</span>"; }
                            if ($email != "" && $email != null) { $primary_contact_details .= "<br>" . $email; } else { $primary_contact_details .= "<br><span class=\"missing-field\">No contact email</span>"; }
                            if ($phone != "" && $phone != null) { $primary_contact_details .= "<br>" . $phone; } else { $primary_contact_details .= "<br><span class=\"missing-field\">No contact phone</span>"; }
                        }
                        else 
                        { 
                            $primary_contact_name = "<span class=\"missing-field\">No contact provided</span>";
                            $primary_contact_details = "<span class=\"missing-field\">No contact provided</span>"; 
                        }
                    }
                    else 
                    { 
                        $primary_contact_name = "<span class=\"missing-field\">No contact provided</span>";
                        $primary_contact_details = "<span class=\"missing-field\">No contact provided</span>"; 
                    }
                }
                else 
                { 
                    $primary_contact_name = "<span class=\"missing-field\">No contact provided</span>";
                    $primary_contact_details = "<span class=\"missing-field\">No contact provided</span>"; 
                }

                // build the secondary contact to be displayed
                $secondary_contact_id = $customer["secondary_contact_id"];
                $getSecondaryContact = mysqli_prepare($conn, "SELECT * FROM customer_contacts WHERE id=? AND customer_id=?");
                mysqli_stmt_bind_param($getSecondaryContact, "ii", $secondary_contact_id, $customer_id);
                if (mysqli_stmt_execute($getSecondaryContact))
                {
                    $result = mysqli_stmt_get_result($getSecondaryContact);
                    if (mysqli_num_rows($result) > 0)
                    {
                        $contactDetails = mysqli_fetch_array($result);

                        $fname = $contactDetails["fname"];
                        $lname = $contactDetails["lname"];
                        $email = $contactDetails["email"];
                        $phone = $contactDetails["phone"];
                        $title = $contactDetails["title"];

                        if ($fname != "" && $lname != "")
                        {
                            $secondary_contact_name = $fname . " " . $lname;
                            $secondary_contact_details = "";
                            if ($title != "" && $title != null) { $secondary_contact_details .= $title; } else { $secondary_contact_details .= "<span class=\"missing-field\">No contact title</span>"; }
                            if ($email != "" && $email != null) { $secondary_contact_details .= "<br>" . $email; } else { $secondary_contact_details .= "<br><span class=\"missing-field\">No contact email</span>"; }
                            if ($phone != "" && $phone != null) { $secondary_contact_details .= "<br>" . $phone; } else { $secondary_contact_details .= "<br><span class=\"missing-field\">No contact phone</span>"; }
                            
                        }
                        else 
                        { 
                            $secondary_contact_name = "<span class=\"missing-field\">No contact provided</span>";
                            $secondary_contact_details = "<span class=\"missing-field\">No contact provided</span>"; 
                        }
                    }
                    else 
                    { 
                        $secondary_contact_name = "<span class=\"missing-field\">No contact provided</span>";
                        $secondary_contact_details = "<span class=\"missing-field\">No contact provided</span>"; 
                    }
                }
                else 
                { 
                    $secondary_contact_name = "<span class=\"missing-field\">No contact provided</span>";
                    $secondary_contact_details = "<span class=\"missing-field\">No contact provided</span>"; 
                }

                // build the contacts display
                $contacts_display = "<div class='accordion id='contacts-$customer_id'>
                    <!-- Primary Contact -->
                    <div class='accordion-item'>
                        <h3 class='accordion-header' id='contacts-primary-header-$customer_id'>
                            <button class='accordion-button collapsed' type='button' data-bs-toggle='collapse' data-bs-target='#contacts-primary-$customer_id' aria-expanded='false' aria-controls='contacts-primary-$customer_id'>
                                <div><b>Primary Contact:</b> $primary_contact_name</div>
                            </button>
                        </h3>

                        <div id='contacts-primary-$customer_id' class='accordion-collapse collapse p-3' aria-labelledby='contacts-primary-header-$customer_id'>
                            $primary_contact_details
                        </div>
                    </div>

                    <!-- Secondary Contact -->
                    <div class='accordion-item'>
                        <h3 class='accordion-header' id='contacts-secondary-header-$customer_id'>
                            <button class='accordion-button collapsed' type='button' data-bs-toggle='collapse' data-bs-target='#contacts-secondary-$customer_id' aria-expanded='false' aria-controls='contacts-secondary-$customer_id'>
                                <div><b>Secondary Contact:</b> $secondary_contact_name</div>
                            </button>
                        </h3>

                        <div id='contacts-secondary-$customer_id' class='accordion-collapse collapse p-3' aria-labelledby='contacts-secondary-header-$customer_id'>
                            $secondary_contact_details
                        </div>
                    </div>
                </div>";
                $temp["contacts"] = $contacts_display;

                // build the hidden groups membership column
                $groups_string = "";
                $getGroups = mysqli_prepare($conn, "SELECT g.name FROM `groups` g JOIN group_members gm ON g.id=gm.group_id WHERE gm.customer_id=?");
                mysqli_stmt_bind_param($getGroups, "i", $customer_id);
                if (mysqli_stmt_execute($getGroups))
                {
                    $getGroupsResults = mysqli_stmt_get_result($getGroups);
                    if (mysqli_num_rows($getGroupsResults) > 0) // groups found
                    {
                        while ($group = mysqli_fetch_array($getGroupsResults))
                        {
                            $groups_string .= $group["name"].",";
                        }
                    }
                }
                $temp["groups_string"] = $groups_string;

                // build the actions column
                $actions = "";
                $build_contract = $customer["build_service_contract"];
                if ($build_contract == 1) { $actions .= "<button class='btn btn-success w-100 mb-1' id='SC-$customer_id' value='1' onclick='toggleBuild(\"SC\", $customer_id);'><i class='fa-solid fa-check'></i></button>"; }
                else { $actions .= "<button class='btn btn-danger w-100 mb-1' id='SC-$customer_id' value='0' onclick='toggleBuild(\"SC\", $customer_id);'><i class='fa-solid fa-xmark'></i></button>"; }
                $actions .= "<button class='btn btn-primary w-100 mb-1' type='button' onclick='getBuildModal(\"SC\", $customer_id);'><i class='fa-solid fa-hammer'></i></button>"; // build button
                $temp["actions"] = $actions;

                $customers[] = $temp;
            }
        }

        // send data to be printed
        $fullData = [];
        $fullData["draw"] = 1;
        $fullData["data"] = $customers;
        echo json_encode($fullData);

        // disconnect from the database
        mysqli_close($conn);
    }
?>