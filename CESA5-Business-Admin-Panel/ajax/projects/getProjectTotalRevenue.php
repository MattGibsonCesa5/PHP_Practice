<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // initialize the total revenues
        $total_revenue = 0;

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
                        // get a list of services that are assigned to the project
                        $getServices = mysqli_prepare($conn, "SELECT * FROM services WHERE project_code=?");
                        mysqli_stmt_bind_param($getServices, "s", $code);
                        if (mysqli_stmt_execute($getServices))
                        {
                            $getServicesResult = mysqli_stmt_get_result($getServices);
                            if (mysqli_num_rows($getServicesResult) > 0) // service(s) are assigned to this project
                            {
                                while ($service = mysqli_fetch_array($getServicesResult))
                                {
                                    $service_id = $service["id"];

                                    // get the total cost of each service
                                    $getServiceRevenue = mysqli_prepare($conn, "SELECT SUM(total_cost) AS total_cost FROM services_provided WHERE service_id=? AND period_id=?");
                                    mysqli_stmt_bind_param($getServiceRevenue, "si", $service_id, $period_id);
                                    if (mysqli_stmt_execute($getServiceRevenue))
                                    {
                                        $getServiceRevenueResult = mysqli_stmt_get_result($getServiceRevenue);
                                        if (mysqli_num_rows($getServiceRevenueResult) > 0) 
                                        { 
                                            $total_revenue += mysqli_fetch_array($getServiceRevenueResult)["total_cost"];
                                        }
                                    }
                                }
                            }
                        }

                        // get additional revenues for the project
                        $getProjectRevenues = mysqli_prepare($conn, "SELECT SUM(total_cost) AS total_cost FROM revenues WHERE project_code=? AND period_id=?");
                        mysqli_stmt_bind_param($getProjectRevenues, "si", $code, $period_id);
                        if (mysqli_stmt_execute($getProjectRevenues))
                        {
                            $getProjectRevenuesResult = mysqli_stmt_get_result($getProjectRevenues);
                            if (mysqli_num_rows($getProjectRevenuesResult) > 0) 
                            { 
                                $total_revenue += mysqli_fetch_array($getProjectRevenuesResult)["total_cost"];
                            }
                        }

                        // get "other services" revenues
                        $getOtherServiceRevenue = mysqli_prepare($conn, "SELECT SUM(oqc.cost) AS total_cost FROM other_quarterly_costs oqc JOIN services_other_provided sop ON oqc.other_invoice_id=sop.id JOIN projects p ON sop.project_code=p.code WHERE sop.period_id=? AND p.code=?");
                        mysqli_stmt_bind_param($getOtherServiceRevenue, "is", $period_id, $code);
                        if (mysqli_stmt_execute($getOtherServiceRevenue))
                        {
                            $getOtherServiceRevenueResult = mysqli_stmt_get_result($getOtherServiceRevenue);
                            if (mysqli_num_rows($getOtherServiceRevenueResult) > 0) 
                            { 
                                $total_revenue += mysqli_fetch_array($getOtherServiceRevenueResult)["total_cost"];
                            }
                        }
                    }
                }
            }
        }
        
        // send back the total revenue
        echo $total_revenue;
        
        // disconnect from the database
        mysqli_close($conn);
    }
?>