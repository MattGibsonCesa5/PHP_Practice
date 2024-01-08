<?php
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get additional required files
        include("../../includes/config.php");
        include("../../incldues/functions.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "DELETE_CUSTOMERS"))
        {
            // get customer ID from POST
            if (isset($_POST["customer_id"]) && $_POST["customer_id"] <> "") { $customer_id = $_POST["customer_id"]; } else { $customer_id = null; }

            if ($customer_id != null && is_numeric($customer_id))
            {
                // verify the customer exists
                $checkCustomer = mysqli_prepare($conn, "SELECT id FROM customers WHERE id=?");
                mysqli_stmt_bind_param($checkCustomer, "i", $customer_id);
                if (mysqli_stmt_execute($checkCustomer))
                {
                    $checkCustomerResult = mysqli_stmt_get_result($checkCustomer);
                    if (mysqli_num_rows($checkCustomerResult) > 0) // customer exists; continue
                    {
                        // delete the customer
                        $deleteCustomer = mysqli_prepare($conn, "DELETE FROM customers WHERE id=?");
                        mysqli_stmt_bind_param($deleteCustomer, "i", $customer_id);
                        if (mysqli_stmt_execute($deleteCustomer)) // successfully delete the customer; delete other data associated to this customer
                        {
                            echo "<span class=\"log-success\">Successfully</span> deleted the customer.<br>";

                            // delete the address associated to the deleted customer
                            $deleteCustomerAddress = mysqli_prepare($conn, "DELETE FROM customer_addresses WHERE customer_id=?");
                            mysqli_stmt_bind_param($deleteCustomerAddress, "i", $customer_id);
                            if (!mysqli_stmt_execute($deleteCustomerAddress)) { echo "<span class=\"log-fail\">Failed</span> to delete the address associated with the customer.<br>"; }

                            // delete the contacts associated to the deleted customer
                            $deleteCustomerContacts = mysqli_prepare($conn, "DELETE FROM customer_contacts WHERE customer_id=?");
                            mysqli_stmt_bind_param($deleteCustomerContacts, "i", $customer_id);
                            if (!mysqli_stmt_execute($deleteCustomerContacts)) { echo "<span class=\"log-fail\">Failed</span> to delete the contacts associated with the customer.<br>"; }

                            // delete the billing data we have for this customer
                            $deleteCustomerBilling = mysqli_prepare($conn, "DELETE FROM services_provided WHERE customer_id=?");
                            mysqli_stmt_bind_param($deleteCustomerBilling, "i", $customer_id);
                            if (!mysqli_stmt_execute($deleteCustomerBilling)) { echo "<span class=\"log-fail\">Failed</span> to delete the invoices associated with the customer.<br>"; }

                            // delete the quarterly costs we have for this customer
                            $deleteCustomerQuarterlyBilling = mysqli_prepare($conn, "DELETE FROM quarterly_costs WHERE customer_id=?");
                            mysqli_stmt_bind_param($deleteCustomerQuarterlyBilling, "i", $customer_id);
                            if (!mysqli_stmt_execute($deleteCustomerQuarterlyBilling)) { echo "<span class=\"log-fail\">Failed</span> to delete the quarterly costs associated with the customer.<br>"; }

                            // delete the billing data we have for this customer
                            $deleteCustomerOtherBilling = mysqli_prepare($conn, "DELETE FROM services_other_provided WHERE customer_id=?");
                            mysqli_stmt_bind_param($deleteCustomerOtherBilling, "i", $customer_id);
                            if (!mysqli_stmt_execute($deleteCustomerOtherBilling)) { echo "<span class=\"log-fail\">Failed</span> to delete the invoices for \"other services\" associated with the customer.<br>"; }

                            // delete the quarterly costs we have for this customer
                            $deleteCustomerOtherQuarterlyBilling = mysqli_prepare($conn, "DELETE FROM other_quarterly_costs WHERE customer_id=?");
                            mysqli_stmt_bind_param($deleteCustomerOtherQuarterlyBilling, "i", $customer_id);
                            if (!mysqli_stmt_execute($deleteCustomerOtherQuarterlyBilling)) { echo "<span class=\"log-fail\">Failed</span> to delete the quarterly costs for \"other services\" associated with the customer.<br>"; }

                            // log customer deletion
                            $message = "Successfully deleted the customer with the ID of $customer_id. ";
                            $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                            mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                            mysqli_stmt_execute($log);
                        }
                        else { echo "<span class=\"log-fail\">Failed</span> to delete the customer. An unknown error has occurred. Please try again later.<br>"; }
                    }
                    else { echo "<span class=\"log-fail\">Failed</span> to delete the customer. The customer you are trying to delete does not exist!<br>"; }
                }
                else { echo "<span class=\"log-fail\">Failed</span> to delete the customer. An unknown error has occurred. Please try again later.<br>"; }
            }
            else { echo "<span class=\"log-fail\">Failed</span> to delete the customer. The customer ID was invalid.<br>"; }
        }
        else { echo "<span class=\"log-fail\">Failed</span> to delete the customer. Your account does not have permission to delete customers!<br>"; }

        // disconnect from the database
        mysqli_close($conn);
    }
?>