<?php
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get additional required files
        include("../../includes/config.php");
        include("../../includes/functions.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "ADD_CUSTOMER_GROUPS"))
        {
            // get customer details from POST
            if (isset($_POST["name"]) && $_POST["name"] <> "") { $name = $_POST["name"]; } else { $name = null; }
            if (isset($_POST["desc"]) && $_POST["desc"] <> "") { $desc = $_POST["desc"]; } else { $desc = null; }
            if (isset($_POST["members"]) && $_POST["members"] <> "") { $members = json_decode($_POST["members"]); } else { $members = []; }

            if ($name != null)
            {
                // check to see if a group already exists with the name
                $checkGroup = mysqli_prepare($conn, "SELECT id FROM `groups` WHERE name=?");
                mysqli_stmt_bind_param($checkGroup, "s", $name);
                if (mysqli_stmt_execute($checkGroup))
                {
                    $checkGroupResult = mysqli_stmt_get_result($checkGroup);
                    if (mysqli_num_rows($checkGroupResult) == 0) // group is unique; continue
                    {
                        // create the group
                        $addGroup = mysqli_prepare($conn, "INSERT INTO `groups` (name, description) VALUES (?, ?)");
                        mysqli_stmt_bind_param($addGroup, "ss", $name, $desc);
                        if (mysqli_stmt_execute($addGroup)) // successfully added the group; attempt to insert group members
                        {
                            // get the newly created group ID
                            $group_id = mysqli_insert_id($conn);

                            // log group creation success
                            echo "<span class=\"log-success\">Successfully</span> created the group $name!<br>";

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

                            // log group creation
                            $message = "Successfully created the group named $name. Assigned the department ID of $group_id. ";
                            $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                            mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                            mysqli_stmt_execute($log);
                        }
                        else { echo "<span class=\"log-fail\">Failed</span> to add the group. An unexpected error has ouccrered. Please try again later!<br>"; }
                    }
                    else { echo "<span class=\"log-fail\">Failed</span> to add the group. A group with that name already exists!<br>"; }
                }
                else { echo "<span class=\"log-fail\">Failed</span> to add the group. An unexpected error has occurred. Please try again later!<br>"; }
            }
            else { echo "<span class=\"log-fail\">Failed</span> to add the group. You must provide a group name!<br>"; }
        }
        else { echo "<span class=\"log-fail\">Failed</span> to add the group. Your account does not have permission to add customer groups!<br>"; }

        // disconnect from the database
        mysqli_close($conn);
    }
?>