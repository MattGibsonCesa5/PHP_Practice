<?php
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // include additional required files
        include("../../includes/config.php");
        include("../../includes/functions.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "VIEW_CUSTOMER_GROUPS"))
        {
            // get the department ID from POST
            if (isset($_POST["group_id"]) && $_POST["group_id"] <> "") { $group_id = $_POST["group_id"]; } else { $group_id = null; }

            if ($group_id != null)
            {
                // verify that the group exists
                $checkGroup = mysqli_prepare($conn, "SELECT id, name, description FROM `groups` WHERE id=?");
                mysqli_stmt_bind_param($checkGroup, "i", $group_id);
                if (mysqli_stmt_execute($checkGroup))
                {
                    $checkGroupResult = mysqli_stmt_get_result($checkGroup);
                    if (mysqli_num_rows($checkGroupResult) > 0) // group exists; continue
                    {
                        $group_details = mysqli_fetch_array($checkGroupResult);
                        $group_name = $group_details["name"];
                        $group_desc = $group_details["description"];

                        // get total group submembers
                        $total_members = 0;
                        $getTotalMembers = mysqli_prepare($conn, "SELECT SUM(c.members) AS total_members FROM customers c
                                                                JOIN group_members g ON c.id=g.customer_id
                                                                WHERE g.group_id=?");
                        mysqli_stmt_bind_param($getTotalMembers, "i", $group_id);
                        if (mysqli_stmt_execute($getTotalMembers))
                        {
                            $getTotalMembersResult = mysqli_stmt_get_result($getTotalMembers);
                            if (mysqli_num_rows($getTotalMembersResult) > 0) // members found
                            {
                                $total_members = mysqli_fetch_array($getTotalMembersResult)["total_members"];
                            }
                        }

                        ?>
                            <!-- View Group Modal -->
                            <div class="modal fade" tabindex="-1" role="dialog" id="viewGroupModal" data-bs-backdrop="static" aria-labelledby="viewGroupModalLabel" aria-hidden="true">
                                <div class="modal-dialog modal-lg" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header primary-modal-header">
                                            <h5 class="modal-title primary-modal-title" id="viewGroupModalLabel">View Group (<?php echo $group_name; ?>)</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>

                                        <div class="modal-body">
                                            <!-- Group Details -->
                                            <fieldset class="form-group border p-3 mb-3">
                                                <legend class="w-auto px-2 m-0 float-none fieldset-legend">Group Details</legend>

                                                <div class="row align-items-center my-2">
                                                    <div class="col-3 text-end"><label for="view-name">Group Name:</label></div>
                                                    <div class="col-9"><input type="text" class="form-control w-100" id="view-name" name="view-name" value="<?php echo $group_name; ?>" disabled readonly></div>
                                                </div>

                                                <div class="row align-items-center my-2">
                                                    <div class="col-3 text-end"><label for="view-desc">Description:</label></div>
                                                    <div class="col-9"><input type="text" class="form-control w-100" id="view-desc" name="view-desc" value="<?php echo $group_desc; ?>" disabled readonly></div>
                                                </div>
                                            </fieldset>

                                            <!-- Group Members -->
                                            <fieldset class="form-group border p-3 mb-3">
                                                <legend class="w-auto px-2 m-0 float-none fieldset-legend">Group Members</legend>

                                                <table id="view-group_members" class="report_table w-100">
                                                    <thead>
                                                        <tr>
                                                            <th>Customer ID</th>
                                                            <th>Customer Name</th>
                                                            <th>% of Total Members</th>
                                                        </tr>
                                                    </thead>

                                                    <tbody>
                                                        <?php
                                                            // display all group members
                                                            $getGroupMembers = mysqli_prepare($conn, "SELECT c.id AS customer_id, c.name, c.members FROM customers c
                                                                                                    JOIN group_members g ON c.id=g.customer_id
                                                                                                    WHERE g.group_id=?");
                                                            mysqli_stmt_bind_param($getGroupMembers, "i", $group_id);
                                                            if (mysqli_stmt_execute($getGroupMembers))
                                                            {
                                                                $getGroupMembersResults = mysqli_stmt_get_result($getGroupMembers);
                                                                if (mysqli_num_rows($getGroupMembersResults) > 0) // group has members; display members in table
                                                                {
                                                                    // for each group member, display customer ID and customer name
                                                                    while ($member = mysqli_fetch_array($getGroupMembersResults))
                                                                    {
                                                                        $percentage_of_membership = 0;
                                                                        if ($total_members != 0) { $percentage_of_membership = (($member["members"] / $total_members) * 100); }

                                                                        echo "<tr>
                                                                            <td>".$member["customer_id"]."</td>
                                                                            <td>".$member["name"]."</td>
                                                                            <td>".round($percentage_of_membership, 2)."%</td>
                                                                        </tr>";
                                                                    }
                                                                }
                                                            }
                                                        ?>
                                                    </tbody>
                                                </table>
                                            </fieldset>
                                        </div>

                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> Close</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- End View Group Modal -->
                        <?php  
                    }
                }
            }
        }

        // disconect from the database
        mysqli_close($conn);
    }
?>