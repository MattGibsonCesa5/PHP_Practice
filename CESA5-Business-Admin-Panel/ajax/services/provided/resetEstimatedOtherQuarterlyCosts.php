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
            if (isset($_POST["invoice_id"]) && $_POST["invoice_id"] <> "") { $invoice_id = $_POST["invoice_id"]; } else { $invoice_id = null; }

            if ($invoice_id != null && is_numeric($invoice_id))
            {
                // get the quarterly costs of the invoice
                $costs = []; // array to store the quarterly costs
                for ($q = 1; $q <= 4; $q++)
                {
                    $getQuarterlyCost = mysqli_prepare($conn, "SELECT cost FROM other_quarterly_costs WHERE other_invoice_id=? AND quarter=?");
                    mysqli_stmt_bind_param($getQuarterlyCost, "ii", $invoice_id, $q);
                    if (mysqli_stmt_execute($getQuarterlyCost))
                    {
                        $quarterResult = mysqli_stmt_get_result($getQuarterlyCost);
                        if (mysqli_num_rows($quarterResult) > 0) // quarter cost exists
                        {
                            $quarter_cost = mysqli_fetch_array($quarterResult)["cost"];
                            $costs["q$q"] = sprintf("%0.2f", $quarter_cost);
                        }
                        else { $costs["q$q"] = sprintf("%0.2f", 0); }
                    }
                }
                
                echo json_encode($costs);
            }
        }

        // disconnect from the database
        mysqli_close($conn);
    }
?>