<?php
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get additional required files
        include("../../includes/config.php");
        include("../../includes/functions.php");
        include("../../getSettings.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "VIEW_CUSTOMER_GROUPS") && checkUserPermission($conn, "ADD_INVOICES"))
        {
            // get customer details from POST
            if (isset($_POST["group_id"]) && $_POST["group_id"] <> "") { $group_id = $_POST["group_id"]; } else { $group_id = null; }
            if (isset($_POST["service_id"]) && $_POST["service_id"] <> "") { $service_id = $_POST["service_id"]; } else { $service_id = null; }
            if (isset($_POST["date"]) && $_POST["date"] <> "") { $date = $_POST["date"]; } else { $date = null; }
            if (isset($_POST["desc"]) && $_POST["desc"] <> "") { $desc = $_POST["desc"]; } else { $desc = null; }

            // convert the m/d/Y date to Y-m-d to store in the database
            $DB_date = date("Y-m-d", strtotime($date));

            if ($group_id != null && $service_id != null)
            {
                // verify the group exists
                $checkGroup = mysqli_prepare($conn, "SELECT id FROM `groups` WHERE id=?");
                mysqli_stmt_bind_param($checkGroup, "i", $group_id);
                if (mysqli_stmt_execute($checkGroup))
                {
                    $checkGroupResult = mysqli_stmt_get_result($checkGroup);
                    if (mysqli_num_rows($checkGroupResult) > 0) // group exists; continue
                    {
                        // for each customer within the group; attempt to invoice them for the selected service
                        $getGroupCustomers = mysqli_prepare($conn, "SELECT customer_id FROM group_members WHERE group_id=?");
                        mysqli_stmt_bind_param($getGroupCustomers, "i", $group_id);
                        if (mysqli_stmt_execute($getGroupCustomers))
                        {
                            $getGroupCustomersResults = mysqli_stmt_get_result($getGroupCustomers);
                            if (mysqli_num_rows($getGroupCustomersResults) > 0) // customers exist within the group
                            {
                                while ($customer = mysqli_fetch_array($getGroupCustomersResults))
                                {                   
                                    // store the customer ID locally
                                    $customer_id = $customer["customer_id"];
                                    
                                    // attempt to create the invoice
                                    createInvoice($conn, $service_id, $customer_id, $GLOBAL_SETTINGS["active_period"], $desc, $DB_date, 0, 0, 0, 0);
                                }
                            }
                            else { echo "<span class=\"log-fail\">Failed</span> to group invoice the group. No customers found within the group.<br>"; } // no group members
                        }
                        else { echo "<span class=\"log-fail\">Failed</span> to group invoice the group. An unexpected error has occurred.<br>"; }
                    }
                    else { echo "<span class=\"log-fail\">Failed</span> to group invoice the group. The group selected does not exist!<br>"; }
                }
                else { echo "<span class=\"log-fail\">Failed</span> to group invoice the group. An unexpected error has occurred.<br>"; }
            }
            else { echo "<span class=\"log-fail\">Failed</span> to group invoice the group. You must select both a group and a service.<br>"; }
        }
        else { echo "<span class=\"log-fail\">Failed</span> to group invoice the group. An unexpected error has occurred.<br>"; }

        // disconnect from the database
        mysqli_close($conn);
    }
?>