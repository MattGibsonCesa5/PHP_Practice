<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // initialize the total qty
        $total_qty = 0;

        // get the required files
        include("../../includes/config.php");
        include("../../includes/functions.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "VIEW_PROJECT_BUDGETS_ALL") || checkUserPermission($conn, "VIEW_PROJECT_BUDGETS_ASSIGNED"))
        {
            // get the parameters from POST
            if (isset($_POST["code"]) && $_POST["code"] <> "") { $code = $_POST["code"]; } else { $code = null; }
            if (isset($_POST["period"]) && $_POST["period"] <> "") { $period = $_POST["period"]; } else { $period = null; }

            if ($code != null && $period != null)
            {
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
                                    $getQty = mysqli_prepare($conn, "SELECT SUM(quantity) AS service_qty FROM services_provided WHERE service_id=? AND period_id=?");
                                    mysqli_stmt_bind_param($getQty, "si", $service_id, $period_id);
                                    if (mysqli_stmt_execute($getQty))
                                    {
                                        $getQtyResult = mysqli_stmt_get_result($getQty);
                                        if (mysqli_num_rows($getQtyResult) > 0) { $total_qty += mysqli_fetch_array($getQtyResult)["service_qty"]; }
                                    }
                                }
                            }
                        }

                        // get all of the "other services" assigned to this project
                        $getOtherServicesTotalQty = mysqli_prepare($conn, "SELECT SUM(quantity) AS other_service_qty FROM services_other_provided WHERE project_code=? AND period_id=?");
                        mysqli_stmt_bind_param($getOtherServicesTotalQty, "si", $code, $period_id);
                        if (mysqli_stmt_execute($getOtherServicesTotalQty))
                        {
                            $getOtherServicesTotalQtyResult = mysqli_stmt_get_result($getOtherServicesTotalQty);
                            if (mysqli_num_rows($getOtherServicesTotalQtyResult) > 0)
                            {
                                if (mysqli_num_rows($getOtherServicesTotalQtyResult) > 0) { $total_qty += mysqli_fetch_array($getOtherServicesTotalQtyResult)["other_service_qty"]; }
                            }
                        }

                        // get all of the "other revenues" assigned to this project
                        $getOtherRevenuesQty = mysqli_prepare($conn, "SELECT SUM(quantity) AS other_revenues_qty FROM revenues WHERE project_code=? AND period_id=?");
                        mysqli_stmt_bind_param($getOtherRevenuesQty, "si", $code, $period_id);
                        if (mysqli_stmt_execute($getOtherRevenuesQty))
                        {
                            $getOtherRevenuesQtyResult = mysqli_stmt_get_result($getOtherRevenuesQty);
                            if (mysqli_num_rows($getOtherRevenuesQtyResult) > 0)
                            {
                                if (mysqli_num_rows($getOtherRevenuesQtyResult) > 0) { $total_qty += mysqli_fetch_array($getOtherRevenuesQtyResult)["other_revenues_qty"]; }
                            }
                        }
                    }
                } 
            }
        }
        
        // send back the total quantity
        echo $total_qty;
        
        // disconnect from the database
        mysqli_close($conn);
    }
?>