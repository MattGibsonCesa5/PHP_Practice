<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // initialize variable to store customer groups
        $groups = [];

        // include addition required files
        include("../../includes/config.php");
        include("../../includes/functions.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "VIEW_CUSTOMER_GROUPS"))
        {
            // store user permissions locally
            $can_user_edit = checkUserPermission($conn, "EDIT_CUSTOMER_GROUPS");
            $can_user_delete = checkUserPermission($conn, "DELETE_CUSTOMER_GROUPS");
            $can_user_invoice = checkUserPermission($conn, "ADD_INVOICES");

            // get a list of all customers
            $getGroups = mysqli_query($conn, "SELECT * FROM `groups`");
            while ($group = mysqli_fetch_array($getGroups)) 
            { 
                $temp = [];
                
                $group_id = $group["id"];
                $temp["id"] = $group_id;
                $temp["name"] = $group["name"];
                $temp["desc"] = $group["description"];

                // build group members column
                $members_count = 0; // assume no members in group
                $getGroupMembers = mysqli_prepare($conn, "SELECT customer_id FROM group_members WHERE group_id=?");
                mysqli_stmt_bind_param($getGroupMembers, "i", $group_id);
                if (mysqli_stmt_execute($getGroupMembers))
                {
                    $getGroupMembersResults = mysqli_stmt_get_result($getGroupMembers);
                    $members_count = mysqli_num_rows($getGroupMembersResults);
                }
                $temp["members_count"] = $members_count;
                if ($members_count > 0) { $temp["members"] = "<button class='btn btn-primary btn-sm' onclick='getViewGroupModal(".$group_id.");'>View ".$members_count." Group Members</button>"; }

                // build the customers' members total column
                $total_submembers = 0;
                $getTotalMembers = mysqli_prepare($conn, "SELECT SUM(c.members) AS total_submembers FROM customers c
                                                        JOIN group_members g ON c.id=g.customer_id
                                                        WHERE g.group_id=?");
                mysqli_stmt_bind_param($getTotalMembers, "i", $group_id);
                if (mysqli_stmt_execute($getTotalMembers))
                {
                    $getTotalMembersResult = mysqli_stmt_get_result($getTotalMembers);
                    if (mysqli_num_rows($getTotalMembersResult) > 0) // members found
                    {
                        $total_submembers = mysqli_fetch_array($getTotalMembersResult)["total_submembers"];
                    }
                }
                $temp["submembers"] = number_format($total_submembers);

                // build the actions column
                $actions = "<div class='d-flex justify-content-end'>";
                    if ($can_user_edit === true) { $actions .= "<button class='btn btn-primary btn-sm mx-1' type='button' onclick='getEditGroupModal(".$group_id.");'><i class='fa-solid fa-pencil'></i></button>"; } // edit button
                    if ($can_user_invoice === true) { $actions .= "<button class='btn btn-primary btn-sm mx-1' type='button' onclick='getInvoiceGroupModal(".$group_id.");'><i class='fa-solid fa-file-invoice-dollar'></i></button>"; } // invoice button
                    if ($can_user_delete === true) { $actions .= "<button class='btn btn-danger btn-sm mx-1' type='button' onclick='getDeleteGroupModal(".$group_id.");'><i class='fa-solid fa-trash-can'></i></button>"; } // delete button
                $actions .= "</div>";
                $temp["actions"] = $actions;

                $groups[] = $temp;
            }
        }

        // disconnect from the database
        mysqli_close($conn);

        // send data to be printed
        $fullData = [];
        $fullData["draw"] = 1;
        $fullData["data"] = $groups;
        echo json_encode($fullData);
    }
?>