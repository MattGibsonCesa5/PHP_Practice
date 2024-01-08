<?php
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // include additonal files
        include("../../../includes/config.php");
        include("../../../includes/functions.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "ADD_INVOICES"))
        {
            // get service details from POST
            if (isset($_POST["period"]) && $_POST["period"] <> "") { $period = $_POST["period"]; } else { $period = null; }
            if (isset($_POST["service_id"]) && $_POST["service_id"] <> "") { $service_id = $_POST["service_id"]; } else { $service_id = null; }
            if (isset($_POST["customer_id"]) && $_POST["customer_id"] <> "") { $customer_id = $_POST["customer_id"]; } else { $customer_id = null; }
            if (isset($_POST["quantity"]) && $_POST["quantity"] <> "") { $quantity = $_POST["quantity"]; } else { $quantity = 0; }
            if (isset($_POST["description"]) && $_POST["description"] <> "") { $description = $_POST["description"]; } else { $description = null; }
            if (isset($_POST["date"]) && $_POST["date"] <> "") { $date = $_POST["date"]; } else { $date = null; }
            if (isset($_POST["custom_cost"]) && $_POST["custom_cost"] <> "") { $custom_cost = $_POST["custom_cost"]; } else { $custom_cost = null; }
            if (isset($_POST["rate_tier"]) && $_POST["rate_tier"] <> "") { $rate_tier = $_POST["rate_tier"]; } else { $rate_tier = null; }
            if (isset($_POST["group_rate_tier"]) && $_POST["group_rate_tier"] <> "") { $group_rate_tier = $_POST["group_rate_tier"]; } else { $group_rate_tier = null; }
            
            // convert the m/d/Y date to Y-m-d to store in the database
            $DB_date = date("Y-m-d", strtotime($date));

            if ($service_id != null && ($customer_id != null && is_numeric($customer_id)) && $period != null)
            {
                if (is_numeric($quantity))
                {
                    if ($period_id = getPeriodID($conn, $period))
                    {
                        if (verifyUserService($conn, $_SESSION["id"], $service_id)) // user has been verified to interact with this service
                        {
                            createInvoice($conn, $service_id, $customer_id, $period_id, $description, $DB_date, $quantity, $custom_cost, $rate_tier, $group_rate_tier);
                        }
                        else { echo "<span class=\"log-fail\">Failed</span> to provide the service with ID $service_id. Failed to verify the user status. Please try again later!<br>"; }
                    }
                    else { echo "<span class=\"log-fail\">Failed</span> to provide the service with ID $service_id. Failed to verify the period selected. Please try again later!<br>"; }
                }
                else { echo "<span class=\"log-fail\">Failed</span> to provide the service with ID $service_id. The quantity must be a number!<br>"; }
            }
        }

        // disconnect from the database
        mysqli_close($conn);
    }
?>