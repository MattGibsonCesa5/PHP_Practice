<?php
    include("header.php");

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1) 
    { 
        if (isset($_SESSION["role"]) && ($_SESSION["role"] == 1 || $_SESSION["role"] == 4))
        {
            // include additonal files
            include("getSettings.php");

            ?>
                <div class="row text-center">
                    <div class="col-2"></div>
                    <div class="col-8"><h1 class="upload-status-header">Invoices Upload Status</h1></div>
                    <div class="col-2"></div>
                </div>

                <div class="row text-center">
                    <div class="col-2"></div>
                    <div class="col-8 upload-status-report">
                    <?php
                        if (isset($_FILES["files"]))
                        {
                            // initialize variables
                            $updated = $inserted = $errors = $files_counter = 0;

                            // connect to the database
                            $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

                            // go through the folder and transfer student folders to server
                            foreach ($_FILES["files"]["name"] as $i => $name)
                            {
                                // verify the file type is valid
                                if ($_FILES["files"]["type"][$i] == "text/csv")
                                {
                                    if (strlen($_FILES["files"]["name"][$i]) > 1)
                                    {
                                        // get and open the file for reading
                                        $file = $_FILES['files']['tmp_name'][$i];
                                        $handle = fopen($file, "r");

                                        // increment the files counter
                                        $files_counter++;

                                        echo "#===============# PROCESSING FILE ".$_FILES["files"]["name"][$i]." #===============#<br>";

                                        while ($data = fgetcsv($handle, 1000, ",", '"'))
                                        {
                                            if (isset($data[0]) && ($data[0] != "Invoice Details" && $data[0] != "Service ID")) // skip the first two rows by looking at cell data
                                            {
                                                if (isset($data[0]) && $data[0] <> "") { $service_id = clean_data($data[0]); } else { $service_id = null; } 
                                                if (isset($data[1]) && $data[1] <> "") { $customer_name = clean_data($data[1]); } else { $customer_name = null; } 
                                                if (isset($data[2]) && $data[2] <> "") { $param = str_replace(",", "", clean_data($data[2])); } else { $param = null; } 
                                                if (isset($data[3]) && $data[3] <> "") { $desc = clean_data($data[3]); } else { $desc = null; } 
                                                if (isset($data[4]) && $data[4] <> "") { $date = clean_data($data[4]); } else { $date = null; } 

                                                // convert the date to Y-m-d to store in the database
                                                $DB_date = date("Y-m-d", strtotime($date));

                                                if ($service_id != null && $customer_name != null && $param != null)
                                                {
                                                    // get customer ID based on the customer name
                                                    $getCustomerID = mysqli_prepare($conn, "SELECT id FROM customers WHERE name=?");
                                                    mysqli_stmt_bind_param($getCustomerID, "s", $customer_name);
                                                    if (mysqli_stmt_execute($getCustomerID))
                                                    {
                                                        $getCustomerIDResult = mysqli_stmt_get_result($getCustomerID);
                                                        if (mysqli_num_rows($getCustomerIDResult) > 0) // customer exists; continue
                                                        {
                                                            // store the customer ID
                                                            $customer_id = mysqli_fetch_array($getCustomerIDResult)["id"];

                                                            // set quantity, custom cost, and rate tier to the variable parameter (column 2 in upload)
                                                            $quantity = $param;
                                                            $custom_cost = $param;
                                                            $rate_tier = $param;

                                                            // attempt to create the invoice
                                                            if (!createInvoice($conn, $service_id, $customer_id, $GLOBAL_SETTINGS["active_period"], $desc, $DB_date, $quantity, $custom_cost, $rate_tier, $rate_tier, "upload")) { $errors++; }
                                                        }
                                                        else
                                                        {
                                                            $errors++;
                                                            echo "<span class=\"log-fail\">Failed</span> to upload invoice. The customer $customer_name does not exist!<br>";
                                                        }
                                                    }
                                                    else
                                                    {
                                                        $errors++;
                                                        echo "<span class=\"log-fail\">Failed</span> to upload invoice. Failed to validate the customer $customer_name.<br>";
                                                    }
                                                }
                                                else // missing required parameters
                                                { 
                                                    $errors++;
                                                    echo "<span class=\"log-fail\">Failed</span> to upload invoice. You must provide a service ID, customer name, and quantity!<br>"; 
                                                }
                                            }
                                        }
                                    }
                                }
                                else { echo "ERROR! The file ".$_FILES["files"]["name"][$i]." is not a csv. Skipping file.<br>"; }
                            }                                    

                            echo "<i class=\"fa-solid fa-check\"></i> Upload complete!";

                            // log upload
                            
                            $total_successes = $inserted + $updated;
                            $message = "Successfully bulk uploaded $total_successes invoices ($files_counter files). ";
                            if ($errors > 0) { $message .= "Failed to upload $errors invoices. "; }
                            $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                            mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                            mysqli_stmt_execute($log);

                            // disconnect from the database
                            mysqli_close($conn);
                        }   
                        else { echo "<i class='fa-solid fa-triangle-exclamation'></i> ERROR! No upload folder was found. Please select a folder of files to upload and try again. "; }
                    ?>
                    </div>
                    <div class="col-2"></div>
                </div>

                <div class="row justify-content-center text-center my-3">
                    <div class="col-2"><button class="btn btn-primary w-100" onclick="goToProvideServices();">Return To Services Billed</button></div>
                </div>

                <script>function goToProvideServices() { window.location.href = "services_billed.php"; }</script>
            <?php
        }
        else { denyAccess(); }
    }
    else { goToLogin(); }
?>