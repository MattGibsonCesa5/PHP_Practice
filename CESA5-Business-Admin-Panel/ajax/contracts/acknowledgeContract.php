<?php
    session_start();

    // set timezone to Central
    date_default_timezone_set("America/Chicago");

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        if ((isset($_SESSION["district"]) && $_SESSION["district"]["status"] == 1) && ($_SESSION["district"]["role"] == "Admin" || $_SESSION["district"]["role"] == "Editor")) 
        {
            // get details from POST
            if (isset($_POST["contract_id"]) && $_POST["contract_id"] <> "") { $contract_id = $_POST["contract_id"]; } else { $contract_id = null; }
            if (isset($_POST["status"]) && is_numeric($_POST["status"])) { $status = $_POST["status"]; } else { $status = null; }
            if (isset($_POST["fname"]) && $_POST["fname"] <> "") { $fname = $_POST["fname"]; } else { $fname = null; }
            if (isset($_POST["lname"]) && $_POST["lname"] <> "") { $lname = $_POST["lname"]; } else { $lname = null; }
            if (isset($_POST["acknowledgement"]) && $_POST["acknowledgement"] == 1) { $acknowledgement = 1; } else { $acknowledgement = 0; }
            
            // if contract ID is set and status is valid; continue
            if ($contract_id != null && $status != null)
            {
                if (($status == 1 && $fname != null && $lname != null && $acknowledgement == 1) || $status == 3)
                {
                    // get additional required files
                    include("../../includes/config.php");
                    include("../../includes/functions.php");

                    // connect to the database
                    $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

                    // get the current details of the contract
                    $getContract = mysqli_prepare($conn, "SELECT * FROM contracts_created WHERE id=?");
                    mysqli_stmt_bind_param($getContract, "i", $contract_id);
                    if (mysqli_stmt_execute($getContract))
                    {
                        $getContractResult = mysqli_stmt_get_result($getContract);
                        if (mysqli_num_rows($getContractResult) > 0) // contract found 
                        {
                            // store contract details locally
                            $contractDetails = mysqli_fetch_assoc($getContractResult);
                            
                            // verify the contract is still pending acknowledgement
                            if ($contractDetails["status"] != 1 && $contractDetails["status"] != 2)
                            {
                                // verify the user is within the district of the contract
                                if ($_SESSION["district"]["id"] == $contractDetails["customer_id"])
                                {
                                    // get the customer name
                                    $customer_name = getCustomerDetails($conn, $contractDetails["customer_id"])["name"];

                                    // get the period name
                                    $period_name = getPeriodName($conn, $contractDetails["period_id"]);

                                    // get the current timestamp
                                    $timestamp = date("Y-m-d H:i:s");

                                    // approve the contract
                                    if ($status == 1)
                                    {
                                        // update the status of the contract
                                        $updateStatus = mysqli_prepare($conn, "UPDATE contracts_created SET status=1, action_user=?, signature_fname=?, signature_lname=?, action_time=? WHERE id=?");
                                        mysqli_stmt_bind_param($updateStatus, "isssi", $_SESSION["id"], $fname, $lname, $timestamp, $contract_id);
                                        if (mysqli_stmt_execute($updateStatus))
                                        {
                                            // log success
                                            echo "<span class='log-success'>Successfully</span> acknowledged and approved the service contract for ".$period_name.".";
                                            $message = "A user from ".$customer_name." (ID: ".$contractDetails["customer_id"].") successfully acknowledged and approved the service contract for ".$period_name." (ID: ".$contractDetails["period_id"].") with the electronic signature ".$fname." ".$lname.".";
                                            $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                                            mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                                            mysqli_stmt_execute($log);
                                        }
                                        else { echo "<span class='log-fail'>Failed</span> to acknowledge and approve the contract. An unexpected error has occurred! Please try again later."; }
                                    }
                                    // hold the contract for review
                                    else if ($status == 3)
                                    {
                                        // update the status of the contract
                                        $updateStatus = mysqli_prepare($conn, "UPDATE contracts_created SET status=3, action_user=?, action_time=? WHERE id=?");
                                        mysqli_stmt_bind_param($updateStatus, "isi", $_SESSION["id"], $timestamp, $contract_id);
                                        if (mysqli_stmt_execute($updateStatus))
                                        {
                                            // log success
                                            echo "Submitted the ".$period_name." service contract for additional review.";
                                            $message = "A user from ".$customer_name." (ID: ".$contractDetails["customer_id"].") submitted the service contract for ".$period_name." (ID: ".$contractDetails["period_id"].") for additional review.";
                                            $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                                            mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                                            mysqli_stmt_execute($log);
                                        }
                                        else { echo "<span class='log-fail'>Failed</span> to acknowledge and approve the contract. An unexpected error has occurred! Please try again later."; }
                                    }
                                    // invalid contract status
                                    else { echo "<span class='log-fail'>Failed</span> to acknowledge and approve the contract. An unexpected error has occurred! Please try again later."; }
                                }
                                else { echo "<span class='log-fail'>Failed</span> to acknowledge and approve the contract. You are unauthorized to approve this contract!"; }
                            }
                            else { echo "<span class='log-fail'>Failed</span> to acknowledge and approve the contract. Contract status provided was unknown!"; }
                        }
                        else { echo "<span class='log-fail'>Failed</span> to acknowledge and approve the contract. An unexpected error has occurred! Please try again later."; }
                    }
                    else { echo "<span class='log-fail'>Failed</span> to acknowledge and approve the contract. An unexpected error has occurred! Please try again later."; }

                    // disconnect from the database
                    mysqli_close($conn);
                }
                else { echo "<span class='log-fail'>Failed</span> to acknowledge and approve the contract. Required fields were missing!"; }
            }
            else { echo "<span class='log-fail'>Failed</span> to acknowledge and approve the contract. Required fields were missing!"; }
        }
        else { echo "<span class='log-fail'>Failed</span> to acknowledge and approve the contract. You are unauthorized to approve this contract!"; }
    }
?>