<?php
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get additional required files
        include("../../../includes/config.php");
        include("../../../includes/functions.php");
        include("../../../getSettings.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "INVOICE_OTHER_SERVICES"))
        {
            // get the parameters from POST
            if (isset($_POST["invoice_id"]) && $_POST["invoice_id"] <> "") { $invoice_id = $_POST["invoice_id"]; } else { $invoice_id = null; }
            if (isset($_POST["project_code"]) && $_POST["project_code"] <> "") { $project_code = $_POST["project_code"]; } else { $project_code = null; }
            if (isset($_POST["total_cost"]) && $_POST["total_cost"] <> "" && is_numeric($_POST["total_cost"])) { $total_cost = $_POST["total_cost"]; } else { $total_cost = 0; }
            if (isset($_POST["quantity"]) && $_POST["quantity"] <> "" && is_numeric($_POST["quantity"])) { $quantity = $_POST["quantity"]; } else { $quantity = 0; }
            if (isset($_POST["unit_label"]) && $_POST["unit_label"] <> "") { $unit_label = $_POST["unit_label"]; } else { $unit_label = null; }
            if (isset($_POST["description"]) && $_POST["description"] <> "") { $description = $_POST["description"]; } else { $description = null; }
            if (isset($_POST["date"]) && $_POST["date"] <> "") { $date = date("Y-m-d", strtotime($_POST["date"])); } else { $date = date("Y-m-d"); }

            if ($invoice_id != null && $invoice_id <> "")
            {
                if ($description != null)
                {
                    if ($unit_label != null)
                    {
                        // verify the invoice exists
                        $checkInvoice = mysqli_prepare($conn, "SELECT id, service_id, customer_id, period_id FROM services_other_provided WHERE id=?");
                        mysqli_stmt_bind_param($checkInvoice, "i", $invoice_id);
                        if (mysqli_stmt_execute($checkInvoice))
                        {
                            $checkInvoiceResult = mysqli_stmt_get_result($checkInvoice);
                            if (mysqli_num_rows($checkInvoiceResult) > 0) // invoice exists; continue
                            {
                                // store existing invoice details
                                $invoice_details = mysqli_fetch_array($checkInvoiceResult);
                                $service_id = $invoice_details["service_id"];
                                $customer_id = $invoice_details["customer_id"];
                                $period_id = $invoice_details["period_id"];

                                // initialize variable to verify project code
                                $project_verified = false;

                                if ($project_code != null) // project code was selected; verify it exists
                                {
                                    $checkProject = mysqli_prepare($conn, "SELECT code FROM projects WHERE code=?");
                                    mysqli_stmt_bind_param($checkProject, "s", $project_code);
                                    if (mysqli_stmt_execute($checkProject))
                                    {
                                        $checkProjectResult = mysqli_stmt_get_result($checkProject);
                                        if (mysqli_num_rows($checkProjectResult) > 0) // project exists; verify
                                        {
                                            $project_verified = true;
                                        }
                                    }
                                }
                                else { $project_verified = true; } // no project selected; verify project exists (project not required)

                                if ($project_verified === true) // project is verified (or no project selected)
                                {
                                    // edit the existing invoice
                                    $editInvoice = mysqli_prepare($conn, "UPDATE services_other_provided SET total_cost=?, quantity=?, description=?, date_provided=?, unit_label=?, project_code=? WHERE id=?");
                                    mysqli_stmt_bind_param($editInvoice, "ddssssi", $total_cost, $quantity, $description, $date, $unit_label, $project_code, $invoice_id);
                                    if (mysqli_stmt_execute($editInvoice)) // successfully edited the invoice
                                    {
                                        echo "<span class=\"log-success\">Successfully</span> edited the invoice.";

                                        // remove current quarterly costs for the invoice
                                        $removeQuarterlyCosts = mysqli_prepare($conn, "DELETE FROM other_quarterly_costs WHERE other_invoice_id=?");
                                        mysqli_stmt_bind_param($removeQuarterlyCosts, "i", $invoice_id);
                                        if (mysqli_stmt_execute($removeQuarterlyCosts)) // successfully removed outdated quarterly costs; insert new quarterly costs
                                        {
                                            // by default, insert the quarterly costs equally divided for quarters that are unlocked
                                            $getQuarters = mysqli_prepare($conn, "SELECT * FROM quarters WHERE locked=0 AND period_id=?");
                                            mysqli_stmt_bind_param($getQuarters, "i", $period_id);
                                            if (mysqli_stmt_execute($getQuarters))
                                            {
                                                $results = mysqli_stmt_get_result($getQuarters);
                                                $unlockedQuarters = mysqli_num_rows($results);
                                                
                                                if ($unlockedQuarters > 0) // at least 1 quarter is unlocked
                                                {
                                                    // calculate the quarterly cost
                                                    $quarterlyCost = number_format((str_replace(",", "", $total_cost) / $unlockedQuarters), 2, ".", "");

                                                    // insert the quarterly costs into the database for each quarter
                                                    while ($quarter = mysqli_fetch_array($results))
                                                    {
                                                        $insertQuarterlyCosts = mysqli_prepare($conn, "INSERT INTO other_quarterly_costs (other_invoice_id, other_service_id, customer_id, quarter, cost, period_id) VALUES (?, ?, ?, ?, ?, ?)");
                                                        mysqli_stmt_bind_param($insertQuarterlyCosts, "isiidi", $invoice_id, $service_id, $customer_id, $quarter["quarter"], $quarterlyCost, $period_id);
                                                        mysqli_stmt_execute($insertQuarterlyCosts);
                                                    }
                                                }
                                            }
                                        }
                                    }
                                    else { echo "<span class=\"log-fail\">Failed</span> to edit the invoice. An unexpected error has occurred. Please try again later!"; }
                                }
                                else { echo "<span class=\"log-fail\">Failed</span> to edit the invoice. The projecte selected does not exist!"; }
                            }
                            else { echo "<span class=\"log-fail\">Failed</span> to edit the invoice. The invoice you are trying to edit does not exist!"; }
                        }
                        else { echo "<span class=\"log-fail\">Failed</span> to edit the invoice. An unexpected error has occurred. Please try again later!"; }
                    }
                    else { echo "<span class=\"log-fail\">Failed</span> to edit the invoice. You must provide a unit label for the invoice."; }
                }
                else { echo "<span class=\"log-fail\">Failed</span> to edit the invoice. You must provide a description for the invoice."; }
            }
            else { echo "<span class=\"log-fail\">Failed</span> to edit the invoice. An unexpected error has occurred. Please try again later!"; }
        }
        else { echo "<span class=\"log-fail\">Failed</span> to edit the invoice. Your account does not have permission to manage invoices for other services!<br>"; }

        // disconnect from the database
        mysqli_close($conn);
    }
?>