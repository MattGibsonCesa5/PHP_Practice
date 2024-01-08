<?php
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get additional required files
        include("../../../includes/config.php");
        include("../../../includes/functions.php");
        
        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "EDIT_OTHER_SERVICES"))
        { 
            // get service details from POST
            if (isset($_POST["service_id"]) && $_POST["service_id"] <> "") { $service_id = $_POST["service_id"]; } else { $service_id = null; }
            if (isset($_POST["form_service_id"]) && $_POST["form_service_id"] <> "") { $form_service_id = $_POST["form_service_id"]; } else { $form_service_id = null; }
            if (isset($_POST["service_name"]) && $_POST["service_name"] <> "") { $service_name = $_POST["service_name"]; } else { $service_name = null; }
            if (isset($_POST["export_label"]) && $_POST["export_label"] <> "") { $export_label = $_POST["export_label"]; } else { $export_label = null; }
            if (isset($_POST["fund_code"]) && $_POST["fund_code"] <> "") { $fund_code = $_POST["fund_code"]; } else { $fund_code = null; }
            if (isset($_POST["source_code"]) && $_POST["source_code"] <> "") { $source_code = $_POST["source_code"]; } else { $source_code = null; }
            if (isset($_POST["function_code"]) && $_POST["function_code"] <> "") { $function_code = $_POST["function_code"]; } else { $function_code = null; }

            if (($service_id != null && verifyOtherService($conn, $service_id)) && $service_name != null)
            {
                if ($fund_code != null && $source_code != null && $function_code != null)
                {
                    if (is_numeric($fund_code) && ($fund_code >= 10 && $fund_code <= 99))
                    {
                        // verify that a service with the ID does not already exist in the services table
                        $checkOtherID = mysqli_prepare($conn, "SELECT id FROM services_other WHERE id=?");
                        mysqli_stmt_bind_param($checkOtherID, "s", $service_id);
                        if (mysqli_stmt_execute($checkOtherID))
                        {
                            $checkOtherIDResult = mysqli_stmt_get_result($checkOtherID);
                            if (mysqli_num_rows($checkOtherIDResult) > 0) // service ID is unique; proceed with creation
                            {
                                $editOtherService = mysqli_prepare($conn, "UPDATE services_other SET name=?, export_label=?, fund_code=?, source_code=?, function_code=? WHERE id=?");
                                mysqli_stmt_bind_param($editOtherService, "ssssss", $service_name, $export_label, $fund_code, $source_code, $function_code, $service_id);
                                if (mysqli_stmt_execute($editOtherService)) 
                                { 
                                    echo "<span class=\"log-success\">Successfully</span> edited the service.<br>"; 
                                    $message = "Successfully edited the service with ID $service_id. ";

                                    // attempt to edit the service ID if changed
                                    if ($form_service_id != $service_id && !verifyOtherService($conn, $form_service_id))
                                    {
                                        $editServiceID = mysqli_prepare($conn, "UPDATE services_other SET id=? WHERE id=?");
                                        mysqli_stmt_bind_param($editServiceID, "ss", $form_service_id, $service_id);
                                        if (mysqli_stmt_execute($editServiceID)) // successfully edited the service ID
                                        {
                                            // log ID edit
                                            echo "<span class=\"log-success\">Successfully</span> changed the ID from $service_id to $form_service_id.<br>";
                                            $message .= "Successfully changed the ID from $service_id to $form_service_id.";

                                            $updateID = mysqli_prepare($conn, "UPDATE other_quarterly_costs SET other_service_id=? WHERE other_service_id=?");
                                            mysqli_stmt_bind_param($updateID, "ss", $form_service_id, $service_id);
                                            if (!mysqli_stmt_execute($updateID)) { /* TODO - handle ID update error */ }

                                            $updateID = mysqli_prepare($conn, "UPDATE services_other_provided SET service_id=? WHERE service_id=?");
                                            mysqli_stmt_bind_param($updateID, "ss", $form_service_id, $service_id);
                                            if (!mysqli_stmt_execute($updateID)) { /* TODO - handle ID update error */ }
                                        }
                                        else { echo "<span class=\"log-fail\">Failed</span> to change the ID from $service_id to $form_service_id. An unexpected error has occured! Please try again later.<br>"; }
                                    }

                                    // log service edit
                                    $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                                    mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                                    mysqli_stmt_execute($log);
                                }
                                else { echo "<span class=\"log-fail\">Failed</span> to edit the service with the ID of $service_id. An unexpected error has occurred. Please try again later!<br>"; }
                            }
                            else { echo "<span class=\"log-fail\">Failed</span> to edit the service! The service you are trying to edit does not exist!<br>"; }
                        }
                        else { echo "<span class=\"log-fail\">Failed</span> to edit the service. An unknown error has occurred. Please try again later.<br>"; }
                    }
                    else { echo "<span class=\"log-fail\">Failed</span> to edit the service. The fund code must follow the WUFAR convention and be a number within 10 and 99!<br>"; }
                }
                else { echo "<span class=\"log-fail\">Failed</span> to edit the service. You must provide a fund code, source code, and function code.<br>"; }
            }
            else { echo "<span class=\"log-fail\">Failed</span> to edit the service. You must provide the service both an ID and name.<br>"; }
        }
        else { echo "<span class=\"log-fail\">Failed</span> to edit the service. Your account does not have permission to edit other services!<br>"; }

        // disconnect from the database
        mysqli_close($conn);
    }
?>