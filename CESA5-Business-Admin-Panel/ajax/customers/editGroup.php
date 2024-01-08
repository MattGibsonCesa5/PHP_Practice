<?php
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get additional required files
        include("../../includes/config.php");
        include("../../includes/functions.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "EDIT_CUSTOMER_GROUPS"))
        {
            // get customer details from POST
            if (isset($_POST["id"]) && $_POST["id"] <> "") { $group_id = $_POST["id"]; } else { $group_id = null; }
            if (isset($_POST["name"]) && $_POST["name"] <> "") { $name = $_POST["name"]; } else { $name = null; }
            if (isset($_POST["desc"]) && $_POST["desc"] <> "") { $desc = $_POST["desc"]; } else { $desc = null; }
            if (isset($_POST["members"]) && $_POST["members"] <> "") { $members = json_decode($_POST["members"]); } else { $members = []; }

            if ($group_id != null && is_numeric($group_id))
            {
                if ($name != null)
                {
                    // verify the group exists
                    $checkGroup = mysqli_prepare($conn, "SELECT id FROM `groups` WHERE id=?");
                    mysqli_stmt_bind_param($checkGroup, "i", $group_id);
                    if (mysqli_stmt_execute($checkGroup))
                    {
                        $checkGroupResult = mysqli_stmt_get_result($checkGroup);
                        if (mysqli_num_rows($checkGroupResult) > 0) // group exists; continue
                        {
                            // verify no other group has the name
                            $checkName = mysqli_prepare($conn, "SELECT id FROM `groups` WHERE name=? AND id!=?");
                            mysqli_stmt_bind_param($checkName, "si", $name, $group_id);
                            if (mysqli_stmt_execute($checkName))
                            {
                                $checkNameResult = mysqli_stmt_get_result($checkName);
                                if (mysqli_num_rows($checkNameResult) == 0) // name is unique; continue edit
                                {
                                    // edit the group
                                    $editGroup = mysqli_prepare($conn, "UPDATE `groups` SET name=?, description=? WHERE id=?");
                                    mysqli_stmt_bind_param($editGroup, "ssi", $name, $desc, $group_id);
                                    if (mysqli_stmt_execute($editGroup)) // successfully edited the group
                                    {
                                        // alert user group was edited successfully
                                        echo "<span class=\"log-success\">Successfully</span> edited the group!<br>";

                                        // remove all current group members
                                        $removeMembers = mysqli_prepare($conn, "DELETE FROM group_members WHERE group_id=?");
                                        mysqli_stmt_bind_param($removeMembers, "i", $group_id);
                                        if (mysqli_stmt_execute($removeMembers)) // successfully removed current group members; add newly selected members
                                        {
                                            // attempt to insert all selected customers into the group
                                            for ($m = 0; $m < count($members); $m++)
                                            {
                                                // store the customer ID locally
                                                $customer_id = $members[$m];

                                                // verify the customer exists
                                                $checkCustomer = mysqli_prepare($conn, "SELECT id, name FROM customers WHERE id=?");
                                                mysqli_stmt_bind_param($checkCustomer, "i", $members[$m]);
                                                if (mysqli_stmt_execute($checkCustomer))
                                                {
                                                    $checkCustomerResult = mysqli_stmt_get_result($checkCustomer);
                                                    if (mysqli_num_rows($checkCustomerResult) > 0) // customer exists; continue
                                                    {
                                                        // store customer name
                                                        $customer_name = mysqli_fetch_array($checkCustomerResult)["name"];

                                                        // add customer to group
                                                        $addMember = mysqli_prepare($conn, "INSERT INTO group_members (group_id, customer_id) VALUES (?, ?)");
                                                        mysqli_stmt_bind_param($addMember, "ii", $group_id, $customer_id);
                                                        if (mysqli_stmt_execute($addMember)) { echo "<span class=\"log-success\">Successfully</span> added $customer_name to the group.<br>"; }
                                                        else { echo "<span class=\"log-fail\">Failed</span> to add $customer_name to the group.<br>"; }
                                                    }
                                                    else { echo "<span class=\"log-fail\">Failed</span> to add customer with ID of $customer_id to the group. Customer does not exist!<br>"; }
                                                }
                                                else { echo "<span class=\"log-fail\">Failed</span> to add customer with ID of $customer_id to the group. An unexpected error has occurred!<br>"; }
                                            }
                                        }
                                        else { echo "<span class=\"log-fail\">Failed</span> to add members to the group.<br>"; }

                                        // log group edit
                                        $message = "Successfully edited the group with the ID of $group_id. ";
                                        $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                                        mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                                        mysqli_stmt_execute($log);
                                    }
                                    else { echo "<span class=\"log-fail\">Failed</span> to edit the group. An unknown error has occurred.<br>"; }
                                }
                                else { echo "<span class=\"log-fail\">Failed</span> to edit the group. An other group already exists with that name!<br>"; }
                            }
                            else { echo "<span class=\"log-fail\">Failed</span> to edit the group. An unknown error has occurred.<br>"; }
                        }
                        else { echo "<span class=\"log-fail\">Failed</span> to edit the group. The group you are trying to edit does not exist!<br>"; }
                    }
                    else { echo "<span class=\"log-fail\">Failed</span> to edit the group. An unknown error has occurred.<br>"; }
                }
                else { echo "<span class=\"log-fail\">Failed</span> to edit the group. You must provided a group name.<br>"; }
            }
            else { echo "<span class=\"log-fail\">Failed</span> to edit the group. An unknown error has occurred.<br>"; }
        }
        else { echo "<span class=\"log-fail\">Failed</span> to edit the group. Your account does not have permission to edit customer groups!<br>"; }

        // disconnect from the database
        mysqli_close($conn);
    }
?>