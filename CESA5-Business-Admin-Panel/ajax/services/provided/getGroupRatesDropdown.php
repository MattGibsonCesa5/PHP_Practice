<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get additional required files
        include("../../../includes/config.php");
        include("../../../includes/functions.php");
        include("../../../getSettings.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "ADD_INVOICES") || checkUserPermission($conn, "EDIT_INVOICES"))
        {
            if (isset($_POST["service_id"]) && $_POST["service_id"] <> "") { $service_id = $_POST["service_id"]; } else { $service_id = null; }
            if (isset($_POST["customer_id"]) && $_POST["customer_id"] <> "") { $customer_id = $_POST["customer_id"]; } else { $customer_id = null; }

            if ($service_id != null && $customer_id != null)
            {
                ?>
                    <select class="form-select w-100" id="provide-group_rate" name="provide-group_rate" required>
                        <option></option>
                        <?php
                            // verify the service exists and is a rate-based cost
                            $checkService = mysqli_prepare($conn, "SELECT id FROM services WHERE id=? AND cost_type=5");
                            mysqli_stmt_bind_param($checkService, "s", $service_id);
                            if (mysqli_stmt_execute($checkService))
                            {
                                $checkServiceResult = mysqli_stmt_get_result($checkService);
                                if (mysqli_num_rows($checkServiceResult) > 0) // service exists; continue
                                {
                                    // get the cost associated to the selected tier
                                    $getRateGroup = mysqli_prepare($conn, "SELECT group_id FROM costs WHERE service_id=? AND period_id=? AND variable_order=1 AND cost_type=5 LIMIT 1");
                                    mysqli_stmt_bind_param($getRateGroup, "si", $service_id, $GLOBAL_SETTINGS["active_period"]);
                                    if (mysqli_stmt_execute($getRateGroup))
                                    {
                                        $getRateGroupResult = mysqli_stmt_get_result($getRateGroup);
                                        if (mysqli_num_rows($getRateGroupResult) > 0) // group found found
                                        {
                                            // store the rate group locally
                                            $rate_group = mysqli_fetch_array($getRateGroupResult)["group_id"];
                                    
                                            // check to see if the customer is a member of the group
                                            $isMember = 0; // assume customer is not a member of the group 
                                            $checkMembership = mysqli_prepare($conn, "SELECT id FROM group_members WHERE group_id=? AND customer_id=?");
                                            mysqli_stmt_bind_param($checkMembership, "ii", $rate_group, $customer_id);
                                            if (mysqli_stmt_execute($checkMembership))
                                            {
                                                $checkMembershipResult = mysqli_stmt_get_result($checkMembership);
                                                if (mysqli_num_rows($checkMembershipResult) > 0) { $isMember = 1; }
                                            }

                                            // get each group rate the service has based on customer membership
                                            $getGroupRates = mysqli_prepare($conn, "SELECT variable_order, cost FROM costs WHERE service_id=? AND period_id=? AND in_group=? AND cost_type=5");
                                            mysqli_stmt_bind_param($getGroupRates, "sii", $service_id, $GLOBAL_SETTINGS["active_period"], $isMember);
                                            if (mysqli_stmt_execute($getGroupRates))
                                            {
                                                $getGroupRatesResults = mysqli_stmt_get_result($getGroupRates);
                                                if (mysqli_num_rows($getGroupRatesResults) > 0) // rates found
                                                {
                                                    // for each rate found, create a dropdown option
                                                    while ($group_rate = mysqli_fetch_array($getGroupRatesResults))
                                                    {
                                                        $tier = $group_rate["variable_order"];
                                                        $cost = $group_rate["cost"];
                                                        echo "<option value='".$tier."'>Tier ".$tier." - ".printDollar($cost)."</option>";
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        ?>
                    </select>
                <?php
            }
        }

        // disconnect from the database
        mysqli_close($conn);
    }
?>