<?php
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // initialize variables
        $members = [];

        // get additional required files
        include("../../includes/config.php");
        include("../../includes/functions.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "EDIT_CUSTOMER_GROUPS"))
        {
            // get group ID from POST
            if (isset($_POST["group_id"]) && $_POST["group_id"] <> "") { $group_id = $_POST["group_id"]; } else { $group_id = null; }

            if ($group_id != null && is_numeric($group_id))
            {
                $getCustomers = mysqli_query($conn, "SELECT id, name FROM customers WHERE active=1 ORDER BY name ASC");
                if (mysqli_num_rows($getCustomers) > 0) // customers found; build table
                {
                    while ($customer = mysqli_fetch_array($getCustomers))
                    {
                        // check to see if the customer is a member of the group or not
                        $isMember = 0; // assume customer is not a member
                        $checkMembership = mysqli_prepare($conn, "SELECT id FROM group_members WHERE group_id=? AND customer_id=?");
                        mysqli_stmt_bind_param($checkMembership, "ii", $group_id, $customer["id"]);
                        if (mysqli_stmt_execute($checkMembership))
                        {
                            $checkMembershipResult = mysqli_stmt_get_result($checkMembership);
                            if (mysqli_num_rows($checkMembershipResult) > 0) { $isMember = 1; } // customer is already a member of the group
                        }

                        $temp = [];
                        $temp["id"] = $customer["id"];
                        $temp["name"] = $customer["name"];
                        $temp["isMember"] = $isMember;
                        $members[] = $temp;
                    }
                }
            }
        }

        // disconnect from the database
        mysqli_close($conn);

        // send data to be printed
        $fullData = [];
        $fullData["draw"] = 1;
        $fullData["data"] = $members;
        echo json_encode($fullData);
    }
?>