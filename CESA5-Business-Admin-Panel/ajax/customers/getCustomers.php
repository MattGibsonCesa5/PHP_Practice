<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // initialize array to store all customers
        $customers = [];

        // get additional required files
        include("../../includes/config.php");
        include("../../includes/functions.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "VIEW_CUSTOMERS"))
        {
            // store user permissions for managing customers locally
            $can_user_edit = checkUserPermission($conn, "EDIT_CUSTOMERS");
            $can_user_delete = checkUserPermission($conn, "DELETE_CUSTOMERS");

            // get the period from POST
            if (isset($_POST["period"]) && $_POST["period"] <> "") { $period = $_POST["period"]; } else { $period = null; }

            if ($period != null && $period_id = getPeriodID($conn, $period))
            {
                // get a list of all customers
                $getCustomers = mysqli_query($conn, "SELECT * FROM customers");
                while ($customer = mysqli_fetch_array($getCustomers)) 
                { 
                    $temp = [];
                    
                    $customer_id = $customer["id"];
                    $temp["id"] = $customer_id;
                    $temp["name"] = $customer["name"];
                    $temp["location_code"] = $customer["location_code"];
                    $temp["invoice_number"] = $customer["invoice_number"];

                    // build the members column
                    $members_count = $customer["members"];
                    if (is_numeric($members_count)) { $members_display = number_format($members_count); } else { $members_display = "0"; }
                    $temp["members"] = $members_display;
                    
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

                    // build the primary contact card to be displayed
                    $primary_contact_card = "";
                    $primary_contact_id = $customer["primary_contact_id"];
                    $getPrimaryContact = mysqli_prepare($conn, "SELECT * FROM customer_contacts WHERE id=? AND customer_id=?");
                    mysqli_stmt_bind_param($getPrimaryContact, "ii", $primary_contact_id, $customer_id);
                    if (mysqli_stmt_execute($getPrimaryContact))
                    {
                        $result = mysqli_stmt_get_result($getPrimaryContact);
                        if (mysqli_num_rows($result) > 0)
                        {
                            // store the contact's details locally
                            $contactDetails = mysqli_fetch_array($result);
                            $fname = $contactDetails["fname"];
                            $lname = $contactDetails["lname"];
                            $email = $contactDetails["email"];
                            $phone = $contactDetails["phone"];
                            $title = $contactDetails["title"];

                            // build the contact display name
                            $display_name = $fname." ".$lname;

                            if (trim($display_name) <> "")
                            {
                                // build the contact card contents
                                $primary_contact_card = "<div>
                                    <p class='m-0'>
                                        <b>Name: </b>".$display_name."
                                    </p>
                                    <p class='m-0'>
                                        <b>Title: </b> ";
                                        if ($title <> "") { $primary_contact_card .= $title; } else { $primary_contact_card .= "<span class='missing-field'>Missing</span>"; }
                                    $primary_contact_card .= "</p>
                                    <p class='m-0'>
                                        <b>Email: </b> ";
                                        if ($email <> "") { $primary_contact_card .= $email; } else { $primary_contact_card .= "<span class='missing-field'>Missing</span>"; }
                                    $primary_contact_card .= "</p>
                                    <p class='m-0'>
                                        <b>Phone: </b> ";
                                        if ($phone <> "") { $primary_contact_card .= $phone; } else { $primary_contact_card .= "<span class='missing-field'>Missing</span>"; }
                                    $primary_contact_card .= "</p>
                                </div>";
                            }
                        }
                    }

                    // build the secondary contact to be displayed
                    $secondary_contact_card = "";
                    $secondary_contact_id = $customer["secondary_contact_id"];
                    $getSecondaryContact = mysqli_prepare($conn, "SELECT * FROM customer_contacts WHERE id=? AND customer_id=?");
                    mysqli_stmt_bind_param($getSecondaryContact, "ii", $secondary_contact_id, $customer_id);
                    if (mysqli_stmt_execute($getSecondaryContact))
                    {
                        $result = mysqli_stmt_get_result($getSecondaryContact);
                        if (mysqli_num_rows($result) > 0)
                        {
                            // store the contact's details locally
                            $contactDetails = mysqli_fetch_array($result);
                            $fname = $contactDetails["fname"];
                            $lname = $contactDetails["lname"];
                            $email = $contactDetails["email"];
                            $phone = $contactDetails["phone"];
                            $title = $contactDetails["title"];

                            // build the contact display name
                            $display_name = $fname." ".$lname;

                            if (trim($display_name) <> "")
                            {
                                // build the contact card contents
                                $secondary_contact_card = "<div>
                                    <p class='m-0'>
                                        <b>Name: </b>".$display_name."
                                    </p>
                                    <p class='m-0'>
                                        <b>Title: </b> ";
                                        if ($title <> "") { $secondary_contact_card .= $title; } else { $secondary_contact_card .= "<span class='missing-field'>Missing</span>"; }
                                    $secondary_contact_card .= "</p>
                                    <p class='m-0'>
                                        <b>Email: </b> ";
                                        if ($email <> "") { $secondary_contact_card .= $email; } else { $secondary_contact_card .= "<span class='missing-field'>Missing</span>"; }
                                    $secondary_contact_card .= "</p>
                                    <p class='m-0'>
                                        <b>Phone: </b> ";
                                        if ($phone <> "") { $secondary_contact_card .= $phone; } else { $secondary_contact_card .= "<span class='missing-field'>Missing</span>"; }
                                    $secondary_contact_card .= "</p>
                                </div>";
                            }
                        }
                    }

                    // build the customer contact card
                    $contact_card = "<div class='contact-card text-center'>";
                        if ($primary_contact_card <> "") 
                        { 
                            $contact_card .= "<button type='button' class='btn btn-secondary btn-sm mx-1 my-1' data-bs-container='body' data-bs-toggle='popover' data-bs-placement='bottom' data-bs-content='".htmlspecialchars($primary_contact_card)."'>
                                <i class='fa-solid fa-user'></i> Primary
                            </button>"; 
                        } else {
                            $contact_card .= "<button type='button' class='btn btn-danger btn-sm mx-1 my-1' disabled>
                                <i class='fa-solid fa-user'></i> Primary
                            </button>"; 
                        }
                        if ($secondary_contact_card <> "") 
                        { 
                            $contact_card .= "<button type='button' class='btn btn-secondary btn-sm mx-1 my-1' data-bs-container='body' data-bs-toggle='popover' data-bs-placement='bottom' data-bs-content='".htmlspecialchars($secondary_contact_card)."'>
                                <i class='fa-solid fa-user'></i> Secondary
                            </button>"; 
                        } else {
                            $contact_card .= "<button type='button' class='btn btn-danger btn-sm mx-1 my-1' disabled>
                                <i class='fa-solid fa-user'></i> Secondary
                            </button>"; 
                        }
                    $contact_card .= "</div>";
                    $temp["contacts"] = $contact_card;

                    // build the customer users card
                    $users_card = "<div class='contact-card text-center'>";
                    $users_count = 0;
                    $getUsersCount = mysqli_prepare($conn, "SELECT COUNT(id) AS users_count FROM users WHERE customer_id=?");
                    mysqli_stmt_bind_param($getUsersCount, "i", $customer_id);
                    if (mysqli_stmt_execute($getUsersCount))
                    {
                        $getUsersCountResult = mysqli_stmt_get_result($getUsersCount);
                        if (mysqli_num_rows($getUsersCountResult) > 0)
                        {
                            $users_count = mysqli_fetch_assoc($getUsersCountResult)["users_count"];
                        }
                    }
                    if ($users_count > 0)
                    {
                        $users_card .= "<button type='button' class='btn btn-secondary btn-sm mx-1 my-1' onclick='getViewCustomerUsersModal(".$customer_id.");'>
                            View ".$users_count." Users
                        </button>"; 
                    } else {
                        $users_card .= "<button type='button' class='btn btn-danger btn-sm mx-1 my-1' disabled>
                            No Users
                        </button>"; 
                    }
                    $users_card .= "</div>";
                    $temp["users"] = $users_card;

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

                    // build the column to display what services we have provided to the customer
                    $services_display = "";
                    $getServices = mysqli_prepare($conn, "SELECT DISTINCT s.id, s.name FROM services s 
                                                        JOIN services_provided sp ON s.id=sp.service_id
                                                        WHERE sp.period_id=? AND sp.customer_id=?");
                    mysqli_stmt_bind_param($getServices, "ii", $period_id, $customer_id);
                    if (mysqli_stmt_execute($getServices))
                    {
                        $getServicesResults = mysqli_stmt_get_result($getServices);
                        if (mysqli_num_rows($getServicesResults) > 0) // services found
                        {
                            $services_display .= "<div class='d-flex justify-content-center'>
                                <button class='btn btn-secondary btn-sm mx-1 my-1' onclick='getViewServicesModal(".$customer_id.", ".$period_id.");'>View Services</button>
                            </div>";
                        }
                    }
                    $temp["services"] = $services_display;

                    // build the actions column
                    $actions = "<div class='d-flex justify-content-end'>";
                        if ($can_user_edit === true) { $actions .= "<button class='btn btn-primary btn-sm mx-1' type='button' onclick='getEditCustomerModal(".$customer_id.");'><i class='fa-solid fa-pencil'></i></button>"; } // edit button
                        if ($can_user_delete === true) { $actions .= "<button class='btn btn-danger btn-sm mx-1' type='button' onclick='getDeleteCustomerModal(".$customer_id.");'><i class='fa-solid fa-trash-can'></i></button>"; } // delete button
                    $actions .= "</div>";
                    $temp["actions"] = $actions;

                    $customers[] = $temp;
                }
            }
        }

        // disconnect from the database
        mysqli_close($conn);

        // send data to be printed
        $fullData = [];
        $fullData["draw"] = 1;
        $fullData["data"] = $customers;
        echo json_encode($fullData);
    }
?>