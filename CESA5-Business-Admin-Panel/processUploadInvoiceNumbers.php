<?php
    include("header.php");

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1) 
    { 
        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if ($_SESSION["role"] == 1)
        {
            ?>
                <div class="row text-center">
                    <div class="col-2"></div>
                    <div class="col-8"><h1 class="upload-status-header">Customers Upload Status</h1></div>
                    <div class="col-2"></div>
                </div>

                <div class="row text-center">
                    <div class="col-2"></div>
                    <div class="col-8 upload-status-report">
                    <?php
                        if (isset($_FILES["fileToUpload"])) 
                        {
                            // get and open the file
                            $file = $_FILES['fileToUpload']['tmp_name'];
                            $file_type = $_FILES["fileToUpload"]["type"];

                            // verify the file is set and it is a .csv file
                            if (isset($file) && (isset($file_type) && $file_type == "text/csv"))
                            {
                                // initialize variables
                                $updated = $inserted = $errors = 0;

                                // open the uploaded file
                                $handle = fopen($file, "r");

                                while ($data = fgetcsv($handle, 1000, ",", '"'))
                                {
                                    if (isset($data[0]) && $data[0] != "ID") // skip the first row by looking at cell data
                                    {
                                        // get and clean up the employee's data
                                        if (isset($data[0]) && $data[0] <> "") { $id = clean_data($data[0]); } else { $id = null; } // customer ID
                                        if (isset($data[1])) { $invoice_number = clean_data($data[1]); } else { $invoice_number = null; } // customer invoice number

                                        if ($id != null && $invoice_number != null)
                                        {
                                            // check to see if the customer exists
                                            $checkCustomer = mysqli_prepare($conn, "SELECT id FROM customers WHERE id=?");
                                            mysqli_stmt_bind_param($checkCustomer, "i", $id);
                                            if (mysqli_stmt_execute($checkCustomer))
                                            {
                                                $checkCustomerResult = mysqli_stmt_get_result($checkCustomer);
                                                if (mysqli_num_rows($checkCustomerResult) > 0) // customer exists; continue update
                                                {
                                                    $updateInvoiceNumber = mysqli_prepare($conn, "UPDATE customers SET invoice_number=? WHERE id=?");
                                                    mysqli_stmt_bind_param($updateInvoiceNumber, "si", $invoice_number, $id);
                                                    if (!mysqli_stmt_execute($updateInvoiceNumber)) // failed to set new invoice number
                                                    { 
                                                        $errors++;
                                                        echo "<span class=\"log-fail\">Failed</span> to update the invoice number for customer with the ID $id"; 
                                                    } 
                                                    else { $updated++; } // successfully set new invoice number
                                                }
                                                else 
                                                { 
                                                    $errors++;
                                                    echo "<span class=\"log-fail\">Failed</span> to update the invoice number for customer with the ID $id, the customer does not exist!"; 
                                                }
                                            }
                                            else 
                                            { 
                                                $errors++;
                                                echo "<span class=\"log-fail\">Failed</span> to update the invoice number for customer with the ID $id"; 
                                            } 
                                        }
                                        else 
                                        { 
                                            $errors++;
                                            echo "<span class=\"log-fail\">Failed</span> to update the invoice number - you must have both a customer ID and invoice number!"; 
                                        } 
                                    }
                                }

                                echo "<i class=\"fa-solid fa-check\"></i> Upload complete!";

                                // log upload
                                $total_successes = $inserted + $updated;
                                $message = "Successfully uploaded $total_successes invoice numbers. ";
                                if ($errors > 0) { $message .= "Failed to upload $errors invoice numbers. "; }
                                $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                                mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                                mysqli_stmt_execute($log);
                            }
                            else { echo "ERROR! You must select a .csv file to upload.<br>"; }
                        }   
                        else { echo "ERROR! No upload file was found. Please select a file to upload and try again.<br>"; }
                    ?>
                    </div>
                    <div class="col-2"></div>
                </div>

                <div class="row text-center mt-3">
                    <div class="col-5"></div>
                    <div class="col-2"><button class="btn btn-primary w-100" onclick="goToCustomers();">Return To Manage Customers</button></div>
                    <div class="col-5"></div>
                </div>

                <script>function goToCustomers() { window.location.href = "customers_manage.php"; }</script>
            <?php
        }
        else { denyAccess(); }

        // disconnect from the database
        mysqli_close($conn);
    }
    else { goToLogin(); }
?>