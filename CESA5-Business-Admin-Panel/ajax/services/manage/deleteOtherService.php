<?php
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get additional required files
        include("../../../includes/config.php");
        include("../../../includes/functions.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "DELETE_OTHER_SERVICES"))
        {
            // get service ID from POST
            if (isset($_POST["service_id"]) && $_POST["service_id"] <> "") { $service_id = $_POST["service_id"]; } else { $service_id = null; }

            if ($service_id != null && $service_id <> "")
            {
                // check to see if the service exists
                $checkService = mysqli_prepare($conn, "SELECT id FROM services_other WHERE id=?");
                mysqli_stmt_bind_param($checkService, "s", $service_id);
                if (mysqli_stmt_execute($checkService))
                {
                    $checkServiceResult = mysqli_stmt_get_result($checkService);
                    if (mysqli_num_rows($checkServiceResult) > 0) // service exists; continue deletion
                    {
                        // delete the service
                        $deleteService = mysqli_prepare($conn, "DELETE FROM services_other WHERE id=?");
                        mysqli_stmt_bind_param($deleteService, "s", $service_id);
                        if (mysqli_stmt_execute($deleteService)) // successfully deleted the service; delete other data associated with this service
                        {
                            echo "<span class=\"log-success\">Successfully</span> deleted the service.<br>";

                            // delete the billing info we have for this service
                            $deleteServicesProvided = mysqli_prepare($conn, "DELETE FROM services_other_provided WHERE service_id=?");
                            mysqli_stmt_bind_param($deleteServicesProvided, "s", $service_id);
                            if (!mysqli_stmt_execute($deleteServicesProvided)) { echo "<span class=\"log-fail\">Failed</span> to delete the invoices assoicated with this service.<br>"; }

                            // delete the quarterly costs we have associated to this service
                            $deleteServiceQuarterlyCosts = mysqli_prepare($conn, "DELETE FROM other_quarterly_costs WHERE other_service_id=?");
                            mysqli_stmt_bind_param($deleteServiceQuarterlyCosts, "s", $service_id);
                            if (!mysqli_stmt_execute($deleteServiceQuarterlyCosts)) { echo "<span class=\"log-fail\">Failed</span> to delete the quarterly costs assoicated with this service.<br>"; }

                            // log service deletion
                            $message = "Successfully deleted the service with ID $service_id. ";
                            $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                            mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                            mysqli_stmt_execute($log);
                        }
                        else { echo "<span class=\"log-fail\">Failed</span> to delete the service. An unknown error has occurred. Please try again later.<br>"; }
                    }
                    else { echo "<span class=\"log-fail\">Failed</span> to delete the service. The service you are trying to delete does not exist!<br>"; }
                }
                else { echo "<span class=\"log-fail\">Failed</span> to delete the service. An unknown error has occurred. Please try again later.<br>"; }
            }
            else { echo "<span class=\"log-fail\">Failed</span> to delete the service. The service ID provided was invalid.<br>"; }
        }
        else { echo "<span class=\"log-fail\">Failed</span> to delete the service. Your account does not have permission to delete other services!<br>";}

        // disconnect from the database
        mysqli_close($conn);
    }
?>