<?php
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get additional required files
        include("../../includes/config.php");
        include("../../includes/functions.php");
        include("../../getSettings.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "VIEW_CUSTOMER_GROUPS") && checkUserPermission($conn, "ADD_INVOICES"))
        {
            // get parameters from POST
            if (isset($_POST["service_id"]) && $_POST["service_id"] <> "") { $service_id = $_POST["service_id"]; } else { $service_id = null; }

            if ($service_id != null)
            {
                // verify the service exists and is a membership cost
                $checkService = mysqli_prepare($conn, "SELECT id FROM services WHERE id=? AND cost_type=2");
                mysqli_stmt_bind_param($checkService, "s", $service_id);
                if (mysqli_stmt_execute($checkService))
                {
                    $checkServiceResult = mysqli_stmt_get_result($checkService);
                    if (mysqli_num_rows($checkServiceResult) > 0) // service exists and is a membership cost
                    {
                        // get the total combined cost
                        $getCost = mysqli_prepare($conn, "SELECT cost FROM costs WHERE service_id=? AND period_id=? AND cost_type=2");
                        mysqli_stmt_bind_param($getCost, "si", $service_id, $GLOBAL_SETTINGS["active_period"]);
                        if (mysqli_stmt_execute($getCost))
                        {
                            $getCostResult = mysqli_stmt_get_result($getCost);
                            if (mysqli_num_rows($getCostResult) > 0) // cost found
                            {
                                $total_cost = mysqli_fetch_array($getCostResult)["cost"];
                                echo printDollar($total_cost);
                            }
                            else { echo "ERROR: Cost not found."; }
                        }
                        else { echo "ERROR: Cost not found."; }
                    }
                    else { echo "ERROR: Cost not found."; }
                }
                else { echo "ERROR: Cost not found."; }
            }
            else { echo "ERROR: Cost not found."; }
        }
        else { echo "ERROR: Cost not found."; }

        // disconnect from the database
        mysqli_close($conn);
    }
?>