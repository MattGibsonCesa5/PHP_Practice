<?php
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get additional required file
        include("../../../includes/config.php");
        include("../../../includes/functions.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "ADD_INVOICES") || checkUserPermission($conn, "EDIT_INVOICES"))
        {
            // get parameters from POST
            if (isset($_POST["period"]) && $_POST["period"] <> "") { $period = $_POST["period"]; } else { $period = null; }
            if (isset($_POST["service_id"]) && $_POST["service_id"] <> "") { $service_id = $_POST["service_id"]; } else { $service_id = null; }
            if (isset($_POST["customer_id"]) && $_POST["customer_id"] <> "") { $customer_id = $_POST["customer_id"]; } else { $customer_id = null; }
            if (isset($_POST["quantity"]) && $_POST["quantity"] <> "") { $quantity = $_POST["quantity"]; } else { $quantity = null; }

            if ($period != null && $period_id = getPeriodID($conn, $period))
            {
                if ($service_id != null && verifyService($conn, $service_id))
                {
                    if ($quantity != null)
                    {
                        // get the cost of the service based on the service ID provided
                        $getServiceCostType = mysqli_prepare($conn, "SELECT cost_type, round_costs FROM services WHERE id=?");
                        mysqli_stmt_bind_param($getServiceCostType, "s", $service_id);
                        if (mysqli_stmt_execute($getServiceCostType))
                        {
                            $results = mysqli_stmt_get_result($getServiceCostType);
                            if (mysqli_num_rows($results) > 0) // cost type found 
                            {
                                $serviceDetails = mysqli_fetch_array($results);
                                $costType = $serviceDetails["cost_type"];
                                $roundCosts = $serviceDetails["round_costs"];

                                // if cost type if fixed (0)
                                if ($costType == 0)
                                {
                                    $getServiceCost = mysqli_prepare($conn, "SELECT cost FROM costs WHERE service_id=? AND cost_type=? AND period_id=?");
                                    mysqli_stmt_bind_param($getServiceCost, "sii", $service_id, $costType, $period_id);
                                    if (mysqli_stmt_execute($getServiceCost))
                                    {
                                        $result = mysqli_stmt_get_result($getServiceCost);
                                        if (mysqli_num_rows($result) > 0)
                                        {
                                            $cost = mysqli_fetch_array($result)["cost"];

                                            // calculate the total annual cost
                                            if ($roundCosts == 1) { $total_cost = printDollar(round(($cost * $quantity))); }
                                            else { $total_cost = printDollar(($cost * $quantity)); }
                                            echo $total_cost;
                                        }
                                        else { echo "<span class='missing-field'>Cost not found</span>"; }
                                    }
                                    else { echo "<span class='missing-field'>Cost not found</span>"; }
                                }
                                // if cost type is variable (1)
                                else if ($costType == 1)
                                {
                                    $getServiceCost = mysqli_prepare($conn, "SELECT * FROM costs WHERE service_id=? AND cost_type=? AND period_id=? ORDER BY min_quantity ASC");
                                    mysqli_stmt_bind_param($getServiceCost, "sii", $service_id, $costType, $period_id);
                                    if (mysqli_stmt_execute($getServiceCost))
                                    {
                                        $result = mysqli_stmt_get_result($getServiceCost);
                                        if (mysqli_num_rows($result) > 0)
                                        {
                                            while ($range = mysqli_fetch_array($result))
                                            {
                                                $min = $range["min_quantity"];
                                                $max = $range["max_quantity"];
                                                $cost = $range["cost"];

                                                if ($max != -1) // max is set
                                                {
                                                    if ($quantity >= $min && $quantity <= $max) // quantity is within the range
                                                    {
                                                        // calculate the total annual cost
                                                        if ($roundCosts == 1) { $total_cost = printDollar(round(($cost * $quantity))); }
                                                        else { $total_cost = printDollar(($cost * $quantity)); }
                                                        echo $total_cost;
                                                        return;
                                                    }
                                                }
                                                else // no max is set
                                                {
                                                    // calculate the total annual cost
                                                    if ($roundCosts == 1) { $total_cost = printDollar(round(($cost * $quantity))); }
                                                    else { $total_cost = printDollar(($cost * $quantity)); }
                                                    echo $total_cost;
                                                    return;
                                                }
                                            }
                                        }
                                        else { echo "<span class='missing-field'>Cost not found</span>"; }
                                    }
                                }
                                // if cost type is membership (2)
                                else if ($costType == 2)
                                {
                                    $getServiceCost = mysqli_prepare($conn, "SELECT * FROM costs WHERE service_id=? AND cost_type=? AND period_id=?");
                                    mysqli_stmt_bind_param($getServiceCost, "sii", $service_id, $costType, $period_id);
                                    if (mysqli_stmt_execute($getServiceCost))
                                    {
                                        $result = mysqli_stmt_get_result($getServiceCost);
                                        if (mysqli_num_rows($result) > 0) // cost found
                                        {
                                            // store cost details
                                            $cost_details = mysqli_fetch_array($result);
                                            $total_membership_cost = $cost_details["cost"];
                                            $membership_group = $cost_details["group_id"];

                                            // get total group submembers
                                            $total_submembers = 0;
                                            $getTotalMembers = mysqli_prepare($conn, "SELECT SUM(c.members) AS total_submembers FROM customers c
                                                                                    JOIN group_members g ON c.id=g.customer_id
                                                                                    WHERE g.group_id=?");
                                            mysqli_stmt_bind_param($getTotalMembers, "i", $membership_group);
                                            if (mysqli_stmt_execute($getTotalMembers))
                                            {
                                                $getTotalMembersResult = mysqli_stmt_get_result($getTotalMembers);
                                                if (mysqli_num_rows($getTotalMembersResult) > 0) // members found
                                                {
                                                    $total_submembers = mysqli_fetch_array($getTotalMembersResult)["total_submembers"];
                                                }
                                            }

                                            // get amount of members customer has
                                            $customer_members = 0; // assume 0 members
                                            $getCustomerMembers = mysqli_prepare($conn, "SELECT members FROM customers WHERE id=?");
                                            mysqli_stmt_bind_param($getCustomerMembers, "i", $customer_id);
                                            if (mysqli_stmt_execute($getCustomerMembers))
                                            {
                                                $getCustomerMembersResult = mysqli_stmt_get_result($getCustomerMembers);
                                                if (mysqli_num_rows($getCustomerMembersResult) > 0) // customer/members found
                                                {
                                                    $customer_members = mysqli_fetch_array($getCustomerMembersResult)["members"];
                                                }
                                            }

                                            // get percentage of customer members based on group total
                                            if ($total_submembers != 0) { $percentage_of_members = $customer_members / $total_submembers; }
                                            else { $percentage_of_members = 0; }

                                            // calculate the total cost based on percentage of members
                                            if ($roundCosts == 1) { $total_cost = number_format(round(($total_membership_cost * $percentage_of_members)), 2); }
                                            else { $total_cost = number_format(($total_membership_cost * $percentage_of_members), 2); }
                                            echo $total_cost;
                                            return;
                                        }
                                        else { echo "<span class='missing-field'>Cost not found</span>"; }
                                    }
                                    else { echo "<span class='missing-field'>Cost not found</span>"; }
                                }
                                else if ($costType == 5)
                                {
                                    // get group rate tier 
                                    if (isset($_POST["group_rate_tier"])) { $rate_tier = $_POST["group_rate_tier"]; } else { $rate_tier = null; }

                                    // attempt to estimate invoice cost
                                    if ($rate_tier != null && is_numeric($rate_tier)) // rate tier is set
                                    {
                                        // get the cost associated to the selected tier
                                        $getRateGroup = mysqli_prepare($conn, "SELECT group_id FROM costs WHERE service_id=? AND period_id=? AND variable_order=? AND cost_type=5");
                                        mysqli_stmt_bind_param($getRateGroup, "sii", $service_id, $period_id, $rate_tier);
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

                                                // get the services cost based on if the customer is a member or not
                                                $getCost = mysqli_prepare($conn, "SELECT cost FROM costs WHERE service_id=? AND period_id=? AND variable_order=? AND in_group=? AND cost_type=5");
                                                mysqli_stmt_bind_param($getCost, "siii", $service_id, $period_id, $rate_tier, $isMember);
                                                if (mysqli_stmt_execute($getCost))
                                                {
                                                    $getCostResult = mysqli_stmt_get_result($getCost);
                                                    if (mysqli_num_rows($getCostResult) > 0) // cost found
                                                    {
                                                        $service_cost = mysqli_fetch_array($getCostResult)["cost"];
                                                    }
                                                }

                                                // calculate the total annual cost
                                                if ($roundCosts == 1) { $total_cost = printDollar(round(($service_cost * $quantity))); }
                                                else { $total_cost = printDollar(($service_cost * $quantity)); }
                                                echo $total_cost;
                                                return;
                                            }
                                            else { echo "<span class='missing-field'>Cost not found</span>"; }
                                        }
                                        else { echo "<span class='missing-field'>Cost not found</span>"; }
                                    }
                                    else { echo "<span class='missing-field'>Cost not found</span>"; }
                                }
                                else { echo "<span class='missing-field'>Cost not found</span>"; }
                            }
                            else { echo "<span class='missing-field'>Cost not found</span>"; }
                        }
                        else { echo "<span class='missing-field'>Cost not found</span>"; }
                    }
                    else { echo "<span class='missing-field'>Cost not found</span>"; }
                }
                else { echo "<span class='missing-field'>Cost not found</span>"; }
            }
            else { echo "<span class='missing-field'>Cost not found</span>"; }
        }

        // disconnect from the database
        mysqli_close($conn);
    }
?>