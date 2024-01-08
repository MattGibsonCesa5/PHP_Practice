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

        if (checkUserPermission($conn, "BUILD_SERVICE_CONTRACTS") || checkUserPermission($conn, "BUILD_QUARTERLY_INVOICES"))
        {
            // get POSTed parameters
            if (isset($_POST["type"]) && $_POST["type"] <> "") { $type = $_POST["type"]; } else { $type = null; }
            if (isset($_POST["customer_id"]) && $_POST["customer_id"] <> "") { $customer_id = $_POST["customer_id"]; } else { $customer_id = null; }
            if (isset($_POST["status"]) && is_numeric($_POST["status"])) { $status = $_POST["status"]; } else { $status = 0; }

            if ($type != null && $customer_id != null)
            {
                if ($type == "SC" || $type == "QI") // valid contract types
                {
                    // verify customer exists
                    $checkCustomer = mysqli_prepare($conn, "SELECT id FROM customers WHERE id=?");
                    mysqli_stmt_bind_param($checkCustomer, "i", $customer_id);
                    if (mysqli_stmt_execute($checkCustomer))
                    {
                        $checkCustomerResult = mysqli_stmt_get_result($checkCustomer);
                        if (mysqli_num_rows($checkCustomerResult) > 0) // customer exists; continue
                        {
                            // set status to 0 if it is anything other than 1
                            if ($status != 1) { $status = 0; } 

                            if ($type == "SC" && checkUserPermission($conn, "BUILD_SERVICE_CONTRACTS")) // update build_service_contract setting
                            {
                                if ($status == 1)
                                {
                                    $updateSC = mysqli_prepare($conn, "UPDATE customers SET build_service_contract=0 WHERE id=?");
                                    mysqli_stmt_bind_param($updateSC, "i", $customer_id);
                                    if (mysqli_stmt_execute($updateSC)) { echo 0; }
                                }
                                else
                                {
                                    $updateSC = mysqli_prepare($conn, "UPDATE customers SET build_service_contract=1 WHERE id=?");
                                    mysqli_stmt_bind_param($updateSC, "i", $customer_id);
                                    if (mysqli_stmt_execute($updateSC)) { echo 1; }
                                }
                            }
                            else if ($type == "QI" && checkUserPermission($conn, "BUILD_QUARTERLY_INVOICES")) // update build_quarterly_invoice setting
                            {
                                if ($status == 1)
                                {
                                    $updateQI = mysqli_prepare($conn, "UPDATE customers SET build_quarterly_invoice=0 WHERE id=?");
                                    mysqli_stmt_bind_param($updateQI, "i", $customer_id);
                                    if (mysqli_stmt_execute($updateQI)) { echo 0; }
                                }
                                else
                                {
                                    $updateQI = mysqli_prepare($conn, "UPDATE customers SET build_quarterly_invoice=1 WHERE id=?");
                                    mysqli_stmt_bind_param($updateQI, "i", $customer_id);
                                    if (mysqli_stmt_execute($updateQI)) { echo 1; }
                                }
                            }
                        }
                    }
                }
            }

            // disconnect from the database
            mysqli_close($conn);
        }
    }
?>