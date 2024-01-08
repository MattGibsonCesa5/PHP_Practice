<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        if (isset($_SESSION["role"]) && $_SESSION["role"] == 1)
        {
            // include config
            include("../../includes/config.php");
            include("../../getSettings.php");

            // get parameters from POST
            if (isset($_POST["period"]) && $_POST["period"] <> "") { $period = $_POST["period"]; } else { $period = 0; }
            if (isset($_POST["service"]) && $_POST["service"] <> "") { $service = $_POST["service"]; } else { $service = null; }

            if ($period != null && $period != -1)
            {
                if ($service != null && $service != -1)
                {
                    // connect to the database
                    $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

                    if ($period == -2) // clearing invoices for all periods
                    {
                        if ($service == -2) // clear invoices for all services
                        {
                            $clearInvoices = mysqli_prepare($conn, "TRUNCATE `services_provided`");
                            if (mysqli_stmt_execute($clearInvoices)) // successfully cleared the invoices; continue
                            {
                                echo "<span class=\"log-success\">Successfully</span> cleared all invoices for all periods.<br>";

                                // attempt to delete all quarterly costs for the selected period
                                $clearQuarterlyCosts = mysqli_prepare($conn, "TRUNCATE `quarterly_costs`");
                                if (!mysqli_stmt_execute($clearQuarterlyCosts)) { echo "<span class=\"log-fail\">Failed</span> to clear quarterly costs for all periods.<br>"; }
                            }

                            $clearOtherInvoices = mysqli_prepare($conn, "TRUNCATE `services_other_provided`");
                            if (mysqli_stmt_execute($clearOtherInvoices)) // successfully cleared the invoices; continue
                            {
                                echo "<span class=\"log-success\">Successfully</span> cleared all invoices for \"other services\" for all periods.<br>";

                                // attempt to delete all quarterly costs for the selected period
                                $clearOtherQuarterlyCosts = mysqli_prepare($conn, "TRUNCATE `other_quarterly_costs`");
                                if (!mysqli_stmt_execute($clearOtherQuarterlyCosts)) { echo "<span class=\"log-fail\">Failed</span> to clear quarterly costs for all periods.<br>"; }
                            }
                        }
                        else // clearing invoices for a selected service
                        {
                            // verify service exists
                            $checkService = mysqli_prepare($conn, "SELECT id, name FROM services WHERE id=?");
                            mysqli_stmt_bind_param($checkService, "s", $service);
                            if (mysqli_stmt_execute($checkService))
                            {
                                $checkServiceResult = mysqli_stmt_get_result($checkService);
                                if (mysqli_num_rows($checkServiceResult) > 0) // service exists; continue
                                {
                                    $serviceName = mysqli_fetch_array($checkServiceResult)["name"];

                                    // clear invoices only for the selected service
                                    $clearInvoices = mysqli_prepare($conn, "DELETE FROM `services_provided` WHERE service_id=?");
                                    mysqli_stmt_bind_param($clearInvoices, "s", $service);
                                    if (mysqli_stmt_execute($clearInvoices)) // successfully cleared the invoices; continue
                                    {
                                        echo "<span class=\"log-success\">Successfully</span> cleared all invoices for the service $serviceName in all periods.<br>";

                                        // attempt to delete all quarterly costs for the selected period
                                        $clearQuarterlyCosts = mysqli_prepare($conn, "DELETE FROM `quarterly_costs` WHERE service_id=?");
                                        mysqli_stmt_bind_param($clearQuarterlyCosts, "i", $period);
                                        if (!mysqli_stmt_execute($clearQuarterlyCosts)) { echo "<span class=\"log-fail\">Failed</span> to clear quarterly costs for the service $serviceName in all periods.<br>"; }
                                    }
                                }
                                else // service not found; check in other services
                                {
                                    $checkOtherService = mysqli_prepare($conn, "SELECT id, name FROM services_other WHERE id=?");
                                    mysqli_stmt_bind_param($checkOtherService, "s", $service);
                                    if (mysqli_stmt_execute($checkOtherService))
                                    {
                                        $checkOtherServiceResult = mysqli_stmt_get_result($checkOtherService);
                                        if (mysqli_num_rows($checkOtherServiceResult) > 0) // service exists; continue
                                        {
                                            $serviceName = mysqli_fetch_array($checkOtherServiceResult)["name"];

                                            // clear invoices only for the selected service
                                            $clearInvoices = mysqli_prepare($conn, "DELETE FROM `services_other_provided` WHERE service_id=?");
                                            mysqli_stmt_bind_param($clearInvoices, "s", $service);
                                            if (mysqli_stmt_execute($clearInvoices)) // successfully cleared the invoices; continue
                                            {
                                                echo "<span class=\"log-success\">Successfully</span> cleared all invoices for the service $serviceName in all periods.<br>";

                                                // attempt to delete all quarterly costs for the selected period
                                                $clearQuarterlyCosts = mysqli_prepare($conn, "DELETE FROM `quarterly_costs` WHERE service_id=?");
                                                mysqli_stmt_bind_param($clearQuarterlyCosts, "i", $period);
                                                if (!mysqli_stmt_execute($clearQuarterlyCosts)) { echo "<span class=\"log-fail\">Failed</span> to clear quarterly costs for the service $serviceName in all periods.<br>"; }
                                            }
                                        }
                                        else { echo "<span class=\"log-fail\">Failed</span> to clear invoices for the service with ID of $service. The service selected does not exist!<br>"; }
                                    }
                                }
                            }
                        }
                    }
                    else // clear invoices for a selected period
                    {
                        // verify the period exists
                        $checkPeriod = mysqli_prepare($conn, "SELECT id FROM periods WHERE id=?");
                        mysqli_stmt_bind_param($checkPeriod, "i", $period);
                        if (mysqli_stmt_execute($checkPeriod))
                        {
                            $checkPeriodResult = mysqli_stmt_get_result($checkPeriod);
                            if (mysqli_num_rows($checkPeriodResult) > 0) // period exists; continue
                            {
                                if ($service == -2) // clear invoices for all services
                                {
                                    $clearInvoices = mysqli_prepare($conn, "DELETE FROM `services_provided` WHERE period_id=?");
                                    mysqli_stmt_bind_param($clearInvoices, "i", $period);
                                    if (mysqli_stmt_execute($clearInvoices)) // successfully cleared the invoices; continue
                                    {
                                        echo "<span class=\"log-success\">Successfully</span> cleared all invoices for the selected period.<br>";

                                        // attempt to delete all quarterly costs for the selected period
                                        $clearQuarterlyCosts = mysqli_prepare($conn, "DELETE FROM `quarterly_costs` WHERE period_id=?");
                                        mysqli_stmt_bind_param($clearQuarterlyCosts, "i", $period);
                                        if (!mysqli_stmt_execute($clearQuarterlyCosts)) { echo "<span class=\"log-fail\">Failed</span> to clear quarterly costs for the selected period.<br>"; }
                                    }

                                    $clearOtherInvoices = mysqli_prepare($conn, "DELETE FROM `services_other_provided` WHERE period_id=?");
                                    mysqli_stmt_bind_param($clearOtherInvoices, "i", $period);
                                    if (mysqli_stmt_execute($clearOtherInvoices)) // successfully cleared the invoices; continue
                                    {
                                        echo "<span class=\"log-success\">Successfully</span> cleared all invoices for \"other services\" for the selected period.<br>";

                                        // attempt to delete all quarterly costs for the selected period
                                        $clearQuarterlyCosts = mysqli_prepare($conn, "DELETE FROM `other_quarterly_costs` WHERE period_id=?");
                                        mysqli_stmt_bind_param($clearQuarterlyCosts, "i", $period);
                                        if (!mysqli_stmt_execute($clearQuarterlyCosts)) { echo "<span class=\"log-fail\">Failed</span> to clear quarterly costs for \"other services\" for the selected period.<br>"; }
                                    }
                                }
                                else // clearing invoices for a selected service
                                {
                                    // verify service exists
                                    $checkService = mysqli_prepare($conn, "SELECT id, name FROM services WHERE id=?");
                                    mysqli_stmt_bind_param($checkService, "s", $service);
                                    if (mysqli_stmt_execute($checkService))
                                    {
                                        $checkServiceResult = mysqli_stmt_get_result($checkService);
                                        if (mysqli_num_rows($checkServiceResult) > 0) // service exists; continue
                                        {
                                            $serviceName = mysqli_fetch_array($checkServiceResult)["name"];

                                            // clear invoices only for the selected service
                                            $clearInvoices = mysqli_prepare($conn, "DELETE FROM `services_provided` WHERE period_id=? AND service_id=?");
                                            mysqli_stmt_bind_param($clearInvoices, "is", $period, $service);
                                            if (mysqli_stmt_execute($clearInvoices)) // successfully cleared the invoices; continue
                                            {
                                                echo "<span class=\"log-success\">Successfully</span> cleared all invoices for the service $serviceName in the selected period.<br>";

                                                // attempt to delete all quarterly costs for the selected period
                                                $clearQuarterlyCosts = mysqli_prepare($conn, "DELETE FROM `quarterly_costs` WHERE period_id=? AND service_id=?");
                                                mysqli_stmt_bind_param($clearQuarterlyCosts, "is", $period, $service);
                                                if (!mysqli_stmt_execute($clearQuarterlyCosts)) { echo "<span class=\"log-fail\">Failed</span> to clear quarterly costs for the service $serviceName in the selected period.<br>"; }
                                            }
                                        }
                                        else // service not found; check in other services
                                        {
                                            $checkOtherService = mysqli_prepare($conn, "SELECT id, name FROM services_other WHERE id=?");
                                            mysqli_stmt_bind_param($checkOtherService, "s", $service);
                                            if (mysqli_stmt_execute($checkOtherService))
                                            {
                                                $checkOtherServiceResult = mysqli_stmt_get_result($checkOtherService);
                                                if (mysqli_num_rows($checkOtherServiceResult) > 0) // service exists; continue
                                                {
                                                    $serviceName = mysqli_fetch_array($checkOtherServiceResult)["name"];

                                                    // clear invoices only for the selected service
                                                    $clearInvoices = mysqli_prepare($conn, "DELETE FROM `services_other_provided` WHERE period_id=? AND service_id=?");
                                                    mysqli_stmt_bind_param($clearInvoices, "is", $period, $service);
                                                    if (mysqli_stmt_execute($clearInvoices)) // successfully cleared the invoices; continue
                                                    {
                                                        echo "<span class=\"log-success\">Successfully</span> cleared all invoices for the service $serviceName in the selected period.<br>";

                                                        // attempt to delete all quarterly costs for the selected period
                                                        $clearOtherQuarterlyCosts = mysqli_prepare($conn, "DELETE FROM `other_quarterly_costs` WHERE other_service_id=?");
                                                        mysqli_stmt_bind_param($clearOtherQuarterlyCosts, "i", $period);
                                                        if (!mysqli_stmt_execute($clearOtherQuarterlyCosts)) { echo "<span class=\"log-fail\">Failed</span> to clear quarterly costs for the service $serviceName in the selected period.<br>"; }
                                                    }
                                                }
                                                else { echo "<span class=\"log-fail\">Failed</span> to clear invoices for the service with ID of $service. The service selected does not exist!<br>"; }
                                            }
                                        }
                                    }
                                }
                            }
                            else { echo "ERROR! The period you selected does not exists!<br>"; }
                        }
                        else { echo "ERROR! An unexpected error has occurred. We could not clear invoices for the selected period at the moment. Please try again later.<br>"; }
                    }

                    // log clear
                    $message = "Deleted all invoices for period with ID $period. ";
                    $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                    mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                    mysqli_stmt_execute($log);

                    // disconnect from the database
                    mysqli_close($conn);
                }
                else { echo "ERROR: You must select a service to clear invoices for!<br>"; }
            }
            else { echo "ERROR: You must select a period to clear invoices for!<br>"; }
        }
    }
?>