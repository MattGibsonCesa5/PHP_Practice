<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // initialize the array of data we will print
        $master = [];

        // get additional required files
        include("../../includes/config.php");
        include("../../includes/functions.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        // get the period from POST
        if (isset($_POST["period"]) && $_POST["period"] <> "") { $period = $_POST["period"]; } else { $period = null; }

        if ($period != null && $period_id = getPeriodID($conn, $period)) // verify the period exists; if it exists, store the period ID
        {
            // get a list of all customers that were billed for the quarter selected
            $getCustomers = mysqli_query($conn, "SELECT id, name FROM customers");
            if (mysqli_num_rows($getCustomers) > 0)
            {
                while ($customer = mysqli_fetch_assoc($getCustomers))
                {
                    // store details locally
                    $customer_id = $customer["id"];
                    $customer_name = $customer["name"];

                    // initialize quarterly costs
                    $quarterlyCosts = [];
                    $quarterlyCosts["Q1"] = $quarterlyCosts["Q2"] = $quarterlyCosts["Q3"] = $quarterlyCosts["Q4"] = 0;
                    
                    // for each quarter, get quarterly cost
                    for ($q = 1; $q <= 4; $q++)
                    {
                        // get quarterly costs for "Other Services"
                        $getInvoices = mysqli_prepare($conn, "SELECT SUM(cost) AS q_cost FROM quarterly_costs WHERE period_id=? AND quarter=? AND customer_id=?");
                        mysqli_stmt_bind_param($getInvoices, "iii", $period_id, $q, $customer_id);
                        if (mysqli_stmt_execute($getInvoices))
                        {
                            $getInvoicesResult = mysqli_stmt_get_result($getInvoices);
                            if (mysqli_num_rows($getInvoicesResult) > 0)
                            {
                                $quarterlyCosts["Q$q"] += mysqli_fetch_assoc($getInvoicesResult)["q_cost"];
                            }
                        }

                        // get quarterly costs for "Other Services"
                        $getOther = mysqli_prepare($conn, "SELECT SUM(cost) AS total_cost FROM other_quarterly_costs WHERE period_id=? AND quarter=? AND customer_id=?");
                        mysqli_stmt_bind_param($getOther, "iii", $period_id, $q, $customer_id);
                        if (mysqli_stmt_execute($getOther))
                        {
                            $getOtherResult = mysqli_stmt_get_result($getOther);
                            if (mysqli_num_rows($getOtherResult) > 0)
                            {
                                $quarterlyCosts["Q$q"] += mysqli_fetch_assoc($getOtherResult)["total_cost"];
                            }
                        }
                    }

                    // calculate annual cost
                    $totalCost = $quarterlyCosts["Q1"] + $quarterlyCosts["Q2"] + $quarterlyCosts["Q3"] + $quarterlyCosts["Q4"];

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

                    // only build array if any cost is not 0
                    if ($quarterlyCosts["Q1"] != 0 || $quarterlyCosts["Q2"] != 0 || $quarterlyCosts["Q3"] != 0 || $quarterlyCosts["Q4"] != 0 || $totalCost != 0)
                    {
                        // build array of data to display
                        $temp = [];
                        $temp["id"] = $customer_id;
                        $temp["name"] = $customer_name;
                        $temp["q1"] = printDollar($quarterlyCosts["Q1"]);
                        $temp["q2"] = printDollar($quarterlyCosts["Q2"]);
                        $temp["q3"] = printDollar($quarterlyCosts["Q3"]);
                        $temp["q4"] = printDollar($quarterlyCosts["Q4"]);
                        $temp["total"] = printDollar($totalCost);
                        $temp["calc_q1"] = $quarterlyCosts["Q1"];
                        $temp["calc_q2"] = $quarterlyCosts["Q2"];
                        $temp["calc_q3"] = $quarterlyCosts["Q3"];
                        $temp["calc_q4"] = $quarterlyCosts["Q4"];
                        $temp["calc_total"] = $totalCost;
                        $temp["groups_string"] = $groups_string;
                        $master[] = $temp;
                    }
                }
            }
        }
            
        // send data to be printed
        echo json_encode($master);

        // disconnect from the database
        mysqli_close($conn);
    }
?>