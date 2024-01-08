<?php
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get additional required files
        include("../../../includes/config.php");
        include("../../../includes/functions.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "ADD_OTHER_SERVICES"))
        { 
            // get service details from POST
            if (isset($_POST["service_id"]) && $_POST["service_id"] <> "") { $service_id = $_POST["service_id"]; } else { $service_id = null; }
            if (isset($_POST["service_name"]) && $_POST["service_name"] <> "") { $service_name = $_POST["service_name"]; } else { $service_name = null; }
            if (isset($_POST["export_label"]) && $_POST["export_label"] <> "") { $export_label = $_POST["export_label"]; } else { $export_label = null; }
            if (isset($_POST["fund_code"]) && $_POST["fund_code"] <> "") { $fund_code = $_POST["fund_code"]; } else { $fund_code = null; }
            if (isset($_POST["source_code"]) && $_POST["source_code"] <> "") { $source_code = $_POST["source_code"]; } else { $source_code = null; }
            if (isset($_POST["function_code"]) && $_POST["function_code"] <> "") { $function_code = $_POST["function_code"]; } else { $function_code = null; }

            if ($service_id != null && $service_name != null)
            {
                if ($fund_code != null && $source_code != null && $function_code != null)
                {
                    if (is_numeric($fund_code) && ($fund_code >= 10 && $fund_code <= 99))
                    {
                        // verify that a service with the ID does not already exist in the services table
                        $checkID = mysqli_prepare($conn, "SELECT id FROM services WHERE id=?");
                        mysqli_stmt_bind_param($checkID, "s", $service_id);
                        if (mysqli_stmt_execute($checkID))
                        {
                            $checkIDResult = mysqli_stmt_get_result($checkID);
                            if (mysqli_num_rows($checkIDResult) == 0) // service ID is unique; proceed with creation
                            {
                                // verify that a service with the ID does not already exist in the services table
                                $checkOtherID = mysqli_prepare($conn, "SELECT id FROM services_other WHERE id=?");
                                mysqli_stmt_bind_param($checkOtherID, "s", $service_id);
                                if (mysqli_stmt_execute($checkOtherID))
                                {
                                    $checkOtherIDResult = mysqli_stmt_get_result($checkOtherID);
                                    if (mysqli_num_rows($checkOtherIDResult) == 0) // service ID is unique; proceed with creation
                                    {
                                        $addOtherService = mysqli_prepare($conn, "INSERT INTO services_other (id, name, description, export_label, fund_code, source_code, function_code, active) VALUES (?, ?, 'Temporary Description', ?, ?, ?, ?, 1)");
                                        mysqli_stmt_bind_param($addOtherService, "ssssss", $service_id, $service_name, $export_label, $fund_code, $source_code, $function_code);
                                        if (mysqli_stmt_execute($addOtherService)) 
                                        { 
                                            echo "<span class=\"log-success\">Successfully</span> created the new service with ID $service_id.<br>"; 

                                            // log service creation
                                            $message = "Successfully created the service with ID $service_id. ";
                                            $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                                            mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                                            mysqli_stmt_execute($log);
                                        }
                                        else { echo "<span class=\"log-fail\">Failed</span> to create the service with the ID of $service_id!<br>"; }
                                    }
                                    else { echo "<span class=\"log-fail\">Failed</span> to create the service: an \"other service\" with the ID provided already exists!<br>"; }
                                }
                                else { echo "<span class=\"log-fail\">Failed</span> to create the service. An unknown error has occurred. Please try again later.<br>"; }
                            }
                            else { echo "<span class=\"log-fail\">Failed</span> to create the service: a service with the ID provided already exists!<br>"; }
                        }
                        else { echo "<span class=\"log-fail\">Failed</span> to create the service. An unknown error has occurred. Please try again later.<br>"; }
                    }
                    else { echo "<span class=\"log-fail\">Failed</span> to create the service. The fund code must follow the WUFAR convention and be a number within 10 and 99!<br>"; }
                }
                else { echo "<span class=\"log-fail\">Failed</span> to create the service. You must provide a fund code, source code, and function code.<br>"; }
            }
            else { echo "<span class=\"log-fail\">Failed</span> to create the service. You must provide the service both an ID and name.<br>"; }
        }
        else { echo "<span class=\"log-fail\">Failed</span> to create the service. Your account does not have permission to create other services!<br>";}

        // disconnect from the database
        mysqli_close($conn);
    }
?>