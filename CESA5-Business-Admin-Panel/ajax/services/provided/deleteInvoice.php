<?php
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get required files
        include("../../../includes/functions.php");
        include("../../../includes/config.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "DELETE_INVOICES"))
        {
            // get invoice ID from POST
            if (isset($_POST["invoice_id"]) && $_POST["invoice_id"] <> "") { $invoice_id = $_POST["invoice_id"]; } else { $invoice_id = null; }

            if ($invoice_id != null && $invoice_id <> "")
            {
                // get the service ID based on the invoice ID
                $getServiceID = mysqli_prepare($conn, "SELECT service_id, customer_id FROM services_provided WHERE id=?");
                mysqli_stmt_bind_param($getServiceID, "i", $invoice_id);
                if (mysqli_stmt_execute($getServiceID))
                {
                    $getServiceIDResult = mysqli_stmt_get_result($getServiceID);
                    if (mysqli_num_rows($getServiceIDResult) > 0) // invoice exists; continue
                    {
                        $invoice_details = mysqli_fetch_array($getServiceIDResult);
                        $service_id = $invoice_details["service_id"];
                        $customer_id = $invoice_details["customer_id"];

                        if (verifyUserService($conn, $_SESSION["id"], $service_id)) // user has been verified to interact with this service
                        {
                            $deleteInvoice = mysqli_prepare($conn, "DELETE FROM services_provided WHERE id=?");
                            mysqli_stmt_bind_param($deleteInvoice, "i", $invoice_id);
                            if (mysqli_stmt_execute($deleteInvoice)) // successfully deleted the invoice
                            {
                                echo "<span class=\"log-success\">Successfully</span> deleted the invoice.<br>";

                                // delete the quarterly costs associated with the invoice
                                $deleteCosts = mysqli_prepare($conn, "DELETE FROM quarterly_costs WHERE invoice_id=?");
                                mysqli_stmt_bind_param($deleteCosts, "i", $invoice_id);
                                if (!mysqli_stmt_execute($deleteCosts)) { echo "<span class=\"log-fail\">Failed</span> to delete the quarterly costs associated with the invoice.<br>"; }

                                // log invoice deletion
                                $message = "Successfully deleted the invoice with ID $invoice_id (service: $service_id; customer: $customer_id).";
                                $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                                mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                                mysqli_stmt_execute($log);
                            }
                            else { echo "<span class=\"log-fail\">Failed</span> to delete the invoice. An unknown error has occured. Please try again later.<br>"; }
                        }
                        else { echo "<span class=\"log-fail\">Failed</span> to delete the invoice. You are unauthorized to deleted this invoice.<br>"; }
                    }
                    else { echo "<span class=\"log-fail\">Failed</span> to delete the invoice. The invoice you are trying to delete no longer exists!<br>"; }
                }
                else { echo "<span class=\"log-fail\">Failed</span> to delete the invoice. An unknown error has occured. Please try again later.<br>"; }
            }
            else { echo "<span class=\"log-fail\">Failed</span> to delete the invoice. An unknown error has occured. Please try again later.<br>"; }
        }
        else { echo "<span class=\"log-fail\">Failed</span> to delete the invoice. Your account does not have permission to delete invoice!<br>"; }
        
        // disconnect from the database
        mysqli_close($conn);
    }
?>