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
            // initiaze default variables
            $status = 0;
            $quarterly_cost_sum = 0.00;

            // get the parameters from POST
            if (isset($_POST["invoice_id"]) && $_POST["invoice_id"] <> "") { $invoice_id = $_POST["invoice_id"]; } else { $invoice_id = null; }
            if (isset($_POST["q1"]) && $_POST["q1"] <> "") { $q1 = $_POST["q1"]; } else { $q1 = null; }
            if (isset($_POST["q2"]) && $_POST["q2"] <> "") { $q2 = $_POST["q2"]; } else { $q2 = null; }
            if (isset($_POST["q3"]) && $_POST["q3"] <> "") { $q3 = $_POST["q3"]; } else { $q3 = null; }
            if (isset($_POST["q4"]) && $_POST["q4"] <> "") { $q4 = $_POST["q4"]; } else { $q4 = null; }

            if ($invoice_id != null && is_numeric($invoice_id))
            {
                if ($q1 != null && $q2 != null && $q3 != null && $q4 != null)
                {
                    // verify that the quarterly costs received are numeric
                    if (is_numeric($q1) && is_numeric($q2) && is_numeric($q3) && is_numeric($q4)) // quarterly costs are numeric; continue
                    {
                        // get the setting to see if the invoice is allowed to be "zeroed out"
                        $allow_zero = 0; // assume we are not allowing zeros
                        $getAllowZero = mysqli_prepare($conn, "SELECT allow_zero FROM services_provided WHERE id=?");
                        mysqli_stmt_bind_param($getAllowZero, "i", $invoice_id);
                        if (mysqli_stmt_execute($getAllowZero))
                        {
                            $getAllowZeroResult = mysqli_stmt_get_result($getAllowZero);
                            if (mysqli_num_rows($getAllowZeroResult) > 0)
                            {
                                $allow_zero = mysqli_fetch_array($getAllowZeroResult)["allow_zero"];
                            }
                        }

                        // get the total cost based on the current quarterly costs
                        $getCurrentCost = mysqli_prepare($conn, "SELECT SUM(cost) AS total_cost FROM quarterly_costs WHERE invoice_id=?");
                        mysqli_stmt_bind_param($getCurrentCost, "i", $invoice_id);
                        if (mysqli_stmt_execute($getCurrentCost))
                        {
                            $currentCostResult = mysqli_stmt_get_result($getCurrentCost);
                            if (mysqli_num_rows($currentCostResult) > 0)
                            {
                                $total_cost = mysqli_fetch_array($currentCostResult)["total_cost"];
                                $quarterly_cost_sum = ($q1 + $q2 + $q3 + $q4);

                                // check to see if all quarterly costs equals the total cost
                                if ((number_format($quarterly_cost_sum, 2, ".", "") == number_format($total_cost, 2, ".", "")) || ($allow_zero == 1 && (number_format($quarterly_cost_sum, 2, ".", "") == number_format(0, 2, ".", "")))) { $status = 1; }
                            }
                        }
                    }
                }
            }

            // return results
            $result = [];
            $result["status"] = $status;
            $result["sum"] = number_format($quarterly_cost_sum, 2, ".", "");
            echo json_encode($result);
        }

        // disconnect from the database
        mysqli_close($conn);
    }
?>