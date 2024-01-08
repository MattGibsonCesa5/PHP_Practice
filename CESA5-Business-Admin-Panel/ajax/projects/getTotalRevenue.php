<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // initialize the total cost
        $total_revenue = 0;

        // get additional required files
        include("../../includes/config.php");
        include("../../includes/functions.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "VIEW_PROJECTS_ALL"))
        {
            // get period name from POST
            if (isset($_POST["period"]) && $_POST["period"] <> "") { $period = $_POST["period"]; } else { $period = null; }

            if ($period != null)
            {

                if ($period_id = getPeriodID($conn, $period)) // verify the period exists; if it exists, store the period ID
                {
                    // get the total revenue of all services in the active period if the service is assigned to a project
                    $getServiceRevenue = mysqli_prepare($conn, "SELECT SUM(qc.cost) AS total_cost FROM quarterly_costs qc JOIN services_provided sp ON qc.invoice_id=sp.id JOIN services s ON sp.service_id=s.id JOIN projects p ON s.project_code=p.code WHERE sp.period_id=?");
                    mysqli_stmt_bind_param($getServiceRevenue, "i", $period_id);
                    if (mysqli_stmt_execute($getServiceRevenue))
                    {
                        $getServiceRevenueResult = mysqli_stmt_get_result($getServiceRevenue);
                        if (mysqli_num_rows($getServiceRevenueResult) > 0) 
                        { 
                            $total_revenue += mysqli_fetch_array($getServiceRevenueResult)["total_cost"];
                        }
                    }

                    // get the total cost of each service
                    $getRevenues = mysqli_prepare($conn, "SELECT SUM(total_cost) AS total_cost FROM revenues WHERE period_id=?");
                    mysqli_stmt_bind_param($getRevenues, "i", $period_id);
                    if (mysqli_stmt_execute($getRevenues))
                    {
                        $getRevenuesResults = mysqli_stmt_get_result($getRevenues);
                        if (mysqli_num_rows($getRevenuesResults) > 0) 
                        { 
                            $total_revenue += mysqli_fetch_array($getRevenuesResults)["total_cost"];
                        }
                    }

                    // get the total revenue of all services in the active period if the service is assigned to a project
                    $getOtherServiceRevenue = mysqli_prepare($conn, "SELECT SUM(oqc.cost) AS total_cost FROM other_quarterly_costs oqc JOIN services_other_provided sop ON oqc.other_invoice_id=sop.id JOIN projects p ON sop.project_code=p.code WHERE sop.period_id=?");
                    mysqli_stmt_bind_param($getOtherServiceRevenue, "i", $period_id);
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

        // disconnect from the database
        mysqli_close($conn);

        // send back the total revenue
        echo $total_revenue;
    }
?>