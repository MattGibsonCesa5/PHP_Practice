<?php
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get additional required files
        include("../../../includes/config.php");
        include("../../../includes/functions.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "EDIT_INVOICES"))
        {
            // get the parameters from POST
            if (isset($_POST["period"]) && $_POST["period"] <> "") { $period = $_POST["period"]; } else { $period = null; }
            if (isset($_POST["invoice_id"]) && $_POST["invoice_id"] <> "") { $invoice_id = $_POST["invoice_id"]; } else { $invoice_id = null; }
            if (isset($_POST["quantity"]) && $_POST["quantity"] <> "") { $quantity = $_POST["quantity"]; } else { $quantity = 0; }
            if (isset($_POST["description"]) && $_POST["description"] <> "") { $description = $_POST["description"]; } else { $description = null; }
            if (isset($_POST["date"]) && $_POST["date"] <> "") { $date = $_POST["date"]; } else { $date = null; }
            if (isset($_POST["allow_zero"]) && is_numeric($_POST["allow_zero"])) { $allow_zero = $_POST["allow_zero"]; } else { $allow_zero = 0; }
            if (isset($_POST["custom_cost"]) && $_POST["custom_cost"] <> "") { $custom_cost = $_POST["custom_cost"]; } else { $custom_cost = null; }
            if (isset($_POST["rate_tier"]) && $_POST["rate_tier"] <> "") { $rate_tier = $_POST["rate_tier"]; } else { $rate_tier = null; }
            if (isset($_POST["group_rate_tier"]) && $_POST["group_rate_tier"] <> "") { $group_rate_tier = $_POST["group_rate_tier"]; } else { $group_rate_tier = null; }

            // convert the m/d/Y date to Y-m-d to store in the database
            $DB_date = date("Y-m-d", strtotime($date));

            if ($period != null)
            {
                if ($period_id = getPeriodID($conn, $period)) // verify the period exists; if it exists, store the period ID
                {
                    if ($invoice_id != null && $invoice_id <> "")
                    {
                        if (is_numeric($quantity)) // quantity must be a number
                        {
                            // get the service ID and customer ID
                            $getIDs = mysqli_prepare($conn, "SELECT service_id, customer_id, period_id FROM services_provided WHERE id=?");
                            mysqli_stmt_bind_param($getIDs, "i", $invoice_id);
                            if (mysqli_stmt_execute($getIDs))
                            {
                                $getIDsResult = mysqli_stmt_get_result($getIDs);
                                if (mysqli_num_rows($getIDsResult) > 0) // service exists
                                {
                                    $IDs = mysqli_fetch_array($getIDsResult);
                                    $service_id = $IDs["service_id"];
                                    $customer_id = $IDs["customer_id"];
                                    $period_id = $IDs["period_id"];

                                    if (verifyUserService($conn, $_SESSION["id"], $service_id)) // user has been verified to interact with this service
                                    {
                                        editInvoice($conn, $invoice_id, $service_id, $customer_id, $period_id, $description, $DB_date, $allow_zero, $quantity, $custom_cost, $rate_tier, $group_rate_tier);
                                    }
                                }
                            }
                        }
                        else { echo "<span class=\"log-fail\">Failed</span> to update the invoice. The quantity must be a number!<br>"; }
                    }
                }
            }
        }
        
        // disconnect from the database
        mysqli_close($conn);
    }
?>