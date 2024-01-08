<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get additional required files
        include("../../../includes/config.php");
        include("../../../includes/functions.php");

        // initialize array to store services
        $services = [];

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        // get period name from POST
        if (isset($_POST["period"]) && $_POST["period"] <> "") { $period = $_POST["period"]; } else { $period = null; }

        // verify the period exists; if it exists, store the period ID
        if ($period != null && $period_id = getPeriodID($conn, $period))
        {
            if (checkUserPermission($conn, "VIEW_SERVICES_ALL") || checkUserPermission($conn, "VIEW_SERVICES_ASSIGNED"))
            {
                // build the query to get a list of services based on the user's role
                if (checkUserPermission($conn, "VIEW_SERVICES_ALL")) { $getServices = mysqli_prepare($conn, "SELECT * FROM services ORDER BY id ASC"); }
                else if (checkUserPermission($conn, "VIEW_SERVICES_ASSIGNED")) 
                { 
                    $getServices = mysqli_prepare($conn, "SELECT s.* FROM services s 
                                                            JOIN projects p ON s.project_code=p.code 
                                                            JOIN departments d ON p.department_id=d.id 
                                                            WHERE (d.director_id=? OR d.secondary_director_id=?)"); 
                    mysqli_stmt_bind_param($getServices, "ii", $_SESSION["id"], $_SESSION["id"]);
                }

                // execute the query to get a list of services based on user role
                if (mysqli_stmt_execute($getServices))
                {
                    $getServicesResults = mysqli_stmt_get_result($getServices);
                    if (mysqli_num_rows($getServicesResults) > 0) // services found; continue
                    {
                        while ($service = mysqli_fetch_array($getServicesResults)) 
                        { 
                            $temp = [];

                            $temp["id"] = $service["id"];
                            $temp["name"] = $service["name"];
                            $temp["description"] = $service["description"];
                            $temp["unit_label"] = $service["unit_label"];
                            
                            // build the service cost column
                            // fixed cost
                            if ($service["cost_type"] == 0) 
                            {
                                $temp["cost_type"] = "Fixed"; 
                                
                                // get the service cost
                                $getCost = mysqli_prepare($conn, "SELECT cost FROM costs WHERE service_id=? AND cost_type=0 AND period_id=?");
                                mysqli_stmt_bind_param($getCost, "si", $service["id"], $period_id);
                                if (mysqli_stmt_execute($getCost))
                                {
                                    $getCostResult = mysqli_stmt_get_result($getCost);
                                    if (mysqli_num_rows($getCostResult) > 0) // cost found
                                    {
                                        $cost = mysqli_fetch_array($getCostResult)["cost"];
                                        $temp["cost"] = printDollar($cost);
                                    }
                                    else { $temp["cost"] = "Unknown"; }
                                }
                                else { $temp["cost"] = "Unknown"; }
                            }
                            // variable cost
                            else if ($service["cost_type"] == 1) 
                            { 
                                $temp["cost_type"] = "Variable"; 

                                // get the min service cost
                                $min_cost = 0;
                                $getMinCost = mysqli_prepare($conn, "SELECT MIN(cost) AS min_cost FROM costs WHERE service_id=? AND cost_type=1 AND period_id=? ORDER BY variable_order ASC");
                                mysqli_stmt_bind_param($getMinCost, "si", $service["id"], $period_id);
                                if (mysqli_stmt_execute($getMinCost))
                                {
                                    $getMinCostResult = mysqli_stmt_get_result($getMinCost);
                                    if (mysqli_num_rows($getMinCostResult) > 0) // costs found
                                    {
                                        $min_cost = mysqli_fetch_array($getMinCostResult)["min_cost"];
                                        if ($min_cost == null) { $min_cost = 0; }
                                    }
                                    else { $min_cost = 0; }
                                }
                                else { $min_cost = 0; }

                                // get the max service cost
                                $max_cost = 0;
                                $getMaxCost = mysqli_prepare($conn, "SELECT MAX(cost) AS max_cost FROM costs WHERE service_id=? AND cost_type=1 AND period_id=? ORDER BY variable_order ASC");
                                mysqli_stmt_bind_param($getMaxCost, "si", $service["id"], $period_id);
                                if (mysqli_stmt_execute($getMaxCost))
                                {
                                    $getMaxCostResult = mysqli_stmt_get_result($getMaxCost);
                                    if (mysqli_num_rows($getMaxCostResult) > 0) // costs found
                                    {
                                        $max_cost = mysqli_fetch_array($getMaxCostResult)["max_cost"];
                                        if ($max_cost == null) { $max_cost = 0; }
                                    }
                                    else { $max_cost = 0; }
                                }
                                else { $max_cost = 0; }

                                $cost_range = printDollar($min_cost)." - ".printDollar($max_cost);
                                $temp["cost"] = $cost_range; 
                            }
                            // membership cost
                            else if ($service["cost_type"] == 2) 
                            {
                                $temp["cost_type"] = "Membership"; 
                                
                                // get the service cost
                                $getCost = mysqli_prepare($conn, "SELECT cost FROM costs WHERE service_id=? AND cost_type=2 AND period_id=?");
                                mysqli_stmt_bind_param($getCost, "si", $service["id"], $period_id);
                                if (mysqli_stmt_execute($getCost))
                                {
                                    $getCostResult = mysqli_stmt_get_result($getCost);
                                    if (mysqli_num_rows($getCostResult) > 0) // cost found
                                    {
                                        $cost = mysqli_fetch_array($getCostResult)["cost"];
                                        $temp["cost"] = printDollar($cost);
                                    }
                                    else { $temp["cost"] = "Unknown"; }
                                }
                                else { $temp["cost"] = "Unknown"; }
                            }
                            // custom cost
                            else if ($service["cost_type"] == 3) 
                            { 
                                $temp["cost_type"] = "Custom Cost";
                                $temp["cost"] = "Custom"; 
                            }
                            // rate-based cost
                            else if ($service["cost_type"] == 4)
                            {
                                $temp["cost_type"] = "Rate";
                                
                                // get the min rate
                                $min_cost = 0;
                                $getMinRate = mysqli_prepare($conn, "SELECT MIN(cost) AS min_cost FROM costs WHERE service_id=? AND cost_type=4 AND period_id=?");
                                mysqli_stmt_bind_param($getMinRate, "si", $service["id"], $period_id);
                                if (mysqli_stmt_execute($getMinRate))
                                {
                                    $getMinRateResult = mysqli_stmt_get_result($getMinRate);
                                    if (mysqli_num_rows($getMinRateResult) > 0) // rate found
                                    {
                                        $min_cost = mysqli_fetch_array($getMinRateResult)["min_cost"];
                                        if ($min_cost == null) { $min_cost = 0; }
                                    }
                                    else { $min_cost = 0; }
                                }
                                else { $min_cost = 0; }

                                // get the max rate
                                $max_cost = 0;
                                $getMaxRate = mysqli_prepare($conn, "SELECT MAX(cost) AS max_cost FROM costs WHERE service_id=? AND cost_type=4 AND period_id=?");
                                mysqli_stmt_bind_param($getMaxRate, "si", $service["id"], $period_id);
                                if (mysqli_stmt_execute($getMaxRate))
                                {
                                    $getMaxRateResult = mysqli_stmt_get_result($getMaxRate);
                                    if (mysqli_num_rows($getMaxRateResult) > 0) // rate found
                                    {
                                        $max_cost = mysqli_fetch_array($getMaxRateResult)["max_cost"];
                                        if ($max_cost == null) { $max_cost = 0; }
                                    }
                                    else { $max_cost = 0; }
                                }
                                else { $max_cost = 0; }

                                $cost_range = printDollar($min_cost)." - ".printDollar($max_cost);
                                $temp["cost"] = $cost_range; 
                            }
                            // group rate cost
                            else if ($service["cost_type"] == 5)
                            {
                                $temp["cost_type"] = "Group Rate";
                                
                                // get the min rate
                                $min_cost = 0;
                                $getMinRate = mysqli_prepare($conn, "SELECT MIN(cost) AS min_cost FROM costs WHERE service_id=? AND cost_type=5 AND period_id=?");
                                mysqli_stmt_bind_param($getMinRate, "si", $service["id"], $period_id);
                                if (mysqli_stmt_execute($getMinRate))
                                {
                                    $getMinRateResult = mysqli_stmt_get_result($getMinRate);
                                    if (mysqli_num_rows($getMinRateResult) > 0) // rate found
                                    {
                                        $min_cost = mysqli_fetch_array($getMinRateResult)["min_cost"];
                                        if ($min_cost == null) { $min_cost = 0; }
                                    }
                                    else { $min_cost = 0; }
                                }
                                else { $min_cost = 0; }

                                // get the max rate
                                $max_cost = 0;
                                $getMaxRate = mysqli_prepare($conn, "SELECT MAX(cost) AS max_cost FROM costs WHERE service_id=? AND cost_type=5 AND period_id=?");
                                mysqli_stmt_bind_param($getMaxRate, "si", $service["id"], $period_id);
                                if (mysqli_stmt_execute($getMaxRate))
                                {
                                    $getMaxRateResult = mysqli_stmt_get_result($getMaxRate);
                                    if (mysqli_num_rows($getMaxRateResult) > 0) // rate found
                                    {
                                        $max_cost = mysqli_fetch_array($getMaxRateResult)["max_cost"];
                                        if ($max_cost == null) { $max_cost = 0; }
                                    }
                                    else { $max_cost = 0; }
                                }
                                else { $max_cost = 0; }

                                $cost_range = printDollar($min_cost)." - ".printDollar($max_cost);
                                $temp["cost"] = $cost_range; 
                            }
                            // unknown cost
                            else 
                            { 
                                $temp["cost_type"] = "Unknown";
                                $temp["cost"] = "Unknown"; 
                            }

                            // build the WUFAR codes section
                            $temp["fund_code"] = $service["fund_code"];
                            $temp["object_code"] = $service["object_code"];
                            $temp["function_code"] = $service["function_code"];
                            if ($service["project_code"] == null) { $temp["project_code"] = "<span class=\"missing-field\">Unassigned</span>"; }
                            else { $temp["project_code"] = $service["project_code"]; }

                            // build the active totals columns
                            $total_qty = $total_cost = 0; // assume 0
                            $getTotals = mysqli_prepare($conn, "SELECT SUM(quantity) AS total_qty, SUM(total_cost) AS total_cost FROM services_provided WHERE service_id=? AND period_id=?");
                            mysqli_stmt_bind_param($getTotals, "si", $service["id"], $period_id);
                            if (mysqli_stmt_execute($getTotals))
                            {
                                $getTotalsResult = mysqli_stmt_get_result($getTotals);
                                if (mysqli_num_rows($getTotalsResult) > 0) // totals found
                                {
                                    // store totals locally
                                    $totals = mysqli_fetch_array($getTotalsResult);
                                    if (isset($totals["total_qty"]) && is_numeric($totals["total_qty"])) { $total_qty = $totals["total_qty"]; } else { $total_qty = 0; }
                                    if (isset($totals["total_cost"]) && is_numeric($totals["total_cost"])) { $total_cost = $totals["total_cost"]; } else { $total_cost = 0; }
                                }
                            }
                            $temp["total_qty"] = number_format($total_qty);
                            $temp["total_rev"] = printDollar($total_cost);
                            $temp["calc_total_qty"] = $total_qty;
                            $temp["calc_total_rev"] = $total_cost;

                            // build the actions column
                            $actions = "<div class='d-flex justify-content-end'>";
                                $actions .= "<button class='btn btn-primary btn-sm mx-1' type='button' onclick='getViewServiceModal(\"".$service["id"]."\", ".$period_id.");'><i class='fa-solid fa-eye'></i></button>";
                                if (checkUserPermission($conn, "EDIT_SERVICES")) { $actions .= "<button class='btn btn-primary btn-sm mx-1' type='button' onclick='getEditServiceModal(\"".$service["id"]."\", ".$period_id.");'><i class='fa-solid fa-pencil'></i></button>"; }
                                if (checkUserPermission($conn, "DELETE_SERVICES")) { $actions .= "<button class='btn btn-danger btn-sm mx-1' type='button' onclick='getDeleteServiceModal(\"".$service["id"]."\");'><i class='fa-solid fa-trash-can'></i></button>"; }
                            $actions .= "</div>";
                            $temp["actions"] = $actions;

                            $services[] = $temp;  
                        }
                    }
                }
            }

            // get other services
            if (checkUserPermission($conn, "VIEW_OTHER_SERVICES"))
            {
                // get a list of all other services
                $getOtherServices = mysqli_query($conn, "SELECT * FROM services_other ORDER BY id ASC");
                while ($service = mysqli_fetch_array($getOtherServices)) 
                { 
                    $temp = [];

                    $temp["id"] = $service["id"];
                    $temp["name"] = $service["name"];
                    $temp["description"] = $service["description"];
                    $temp["cost"] = "Custom";
                    $temp["cost_type"] = "Custom";
                    $temp["unit_label"] = "-";
                    $temp["fund_code"] = $service["fund_code"];
                    $temp["object_code"] = $service["source_code"];
                    $temp["function_code"] = $service["function_code"];
                    $temp["project_code"] = "-";

                    // build the active totals columns
                    $total_qty = $total_cost = 0; // assume 0
                    $getTotals = mysqli_prepare($conn, "SELECT SUM(quantity) AS total_qty, SUM(total_cost) AS total_cost FROM services_other_provided WHERE service_id=? AND period_id=?");
                    mysqli_stmt_bind_param($getTotals, "si", $service["id"], $period_id);
                    if (mysqli_stmt_execute($getTotals))
                    {
                        $getTotalsResult = mysqli_stmt_get_result($getTotals);
                        if (mysqli_num_rows($getTotalsResult) > 0) // totals found
                        {
                            // store totals locally
                            $totals = mysqli_fetch_array($getTotalsResult);
                            if (isset($totals["total_qty"]) && is_numeric($totals["total_qty"])) { $total_qty = $totals["total_qty"]; } else { $total_qty = 0; }
                            if (isset($totals["total_cost"]) && is_numeric($totals["total_cost"])) { $total_cost = $totals["total_cost"]; } else { $total_cost = 0; }
                        }
                    }
                    $temp["total_qty"] = number_format($total_qty);
                    $temp["total_rev"] = printDollar($total_cost);
                    $temp["calc_total_qty"] = $total_qty;
                    $temp["calc_total_rev"] = $total_cost;
                    
                    // build the actions column
                    $actions = "<div class='d-flex justify-content-end'>";
                        if (checkUserPermission($conn, "EDIT_OTHER_SERVICES")) { $actions .= "<button class='btn btn-primary btn-sm mx-1' type='button' onclick='getEditOtherServiceModal(\"".$service["id"]."\")'><i class='fa-solid fa-pencil'></i></button>"; } 
                        if (checkUserPermission($conn, "DELETE_OTHER_SERVICES")) { $actions .= "<button class='btn btn-danger btn-sm mx-1' type='button' onclick='getDeleteOtherServiceModal(\"".$service["id"]."\")'><i class='fa-solid fa-trash-can'></i></button>"; }
                    $actions .= "</div>";
                    $temp["actions"] = $actions;

                    $services[] = $temp;  
                }
            }
        }
        
        // send data to be printed
        $fullData = [];
        $fullData["draw"] = 1;
        $fullData["data"] = $services;
        echo json_encode($fullData);
        
        // disconnect from the database
        mysqli_close($conn);
    }
?>