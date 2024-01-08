<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // initialize the total qty
        $total_cost = 0;

        // get the required files
        include("../../includes/config.php");
        include("../../includes/functions.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "VIEW_PROJECT_BUDGETS_ALL") || checkUserPermission($conn, "VIEW_PROJECT_BUDGETS_ASSIGNED"))
        {
            // get the period from POST
            if (isset($_POST["period"]) && $_POST["period"] <> "") { $period = $_POST["period"]; } else { $period = null; }
            if (isset($_POST["code"]) && $_POST["code"] <> "") { $code = $_POST["code"]; } else { $code = null; }

            if ($period != null && $code != null)
            {
                // connect to the database
                $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

                if ($period_id = getPeriodID($conn, $period)) // verify the period exists; if it exists, store the period ID
                {
                    if (verifyProject($conn, $code)) // verify the project exists
                    {
                        // get all of the services assigned to this project
                        $getServices = mysqli_prepare($conn, "SELECT id FROM services WHERE project_code=?");
                        mysqli_stmt_bind_param($getServices, "s", $code);
                        if (mysqli_stmt_execute($getServices))
                        {
                            $getServicesResults = mysqli_stmt_get_result($getServices);
                            if (mysqli_num_rows($getServicesResults) > 0)
                            {
                                // get the quantity of each service provided
                                while ($service = mysqli_fetch_array($getServicesResults))
                                {
                                    $service_id = $service["id"];
                                    $getTotalCost = mysqli_prepare($conn, "SELECT SUM(total_cost) AS service_total_cost FROM services_provided WHERE service_id=? AND period_id=?");
                                    mysqli_stmt_bind_param($getTotalCost, "si", $service_id, $period_id);
                                    if (mysqli_stmt_execute($getTotalCost))
                                    {
                                        $getTotalCostResult = mysqli_stmt_get_result($getTotalCost);
                                        if (mysqli_num_rows($getTotalCostResult) > 0) { $total_cost += mysqli_fetch_array($getTotalCostResult)["service_total_cost"]; }
                                    }
                                }
                            }
                        }

                        // get all of the "other services" assigned to this project
                        $getOtherServicesTotalCost = mysqli_prepare($conn, "SELECT SUM(total_cost) AS other_service_total_cost FROM services_other_provided WHERE project_code=? AND period_id=?");
                        mysqli_stmt_bind_param($getOtherServicesTotalCost, "si", $code, $period_id);
                        if (mysqli_stmt_execute($getOtherServicesTotalCost))
                        {
                            $getOtherServicesTotalCostResult = mysqli_stmt_get_result($getOtherServicesTotalCost);
                            if (mysqli_num_rows($getOtherServicesTotalCostResult) > 0)
                            {
                                if (mysqli_num_rows($getOtherServicesTotalCostResult) > 0) { $total_cost += mysqli_fetch_array($getOtherServicesTotalCostResult)["other_service_total_cost"]; }
                            }
                        }

                        // get all of the "other revenues" assigned to this project
                        $getOtherRevenues = mysqli_prepare($conn, "SELECT SUM(total_cost) AS other_revenues FROM revenues WHERE project_code=? AND period_id=?");
                        mysqli_stmt_bind_param($getOtherRevenues, "si", $code, $period_id);
                        if (mysqli_stmt_execute($getOtherRevenues))
                        {
                            $getOtherRevenuesResult = mysqli_stmt_get_result($getOtherRevenues);
                            if (mysqli_num_rows($getOtherRevenuesResult) > 0)
                            {
                                if (mysqli_num_rows($getOtherRevenuesResult) > 0) { $total_cost += mysqli_fetch_array($getOtherRevenuesResult)["other_revenues"]; }
                            }
                        }
                    }
                }
            }
        }
        
        // disconnect from the database
        mysqli_close($conn); 
        
        // send back the total expenses
        echo $total_cost;
    }
?>