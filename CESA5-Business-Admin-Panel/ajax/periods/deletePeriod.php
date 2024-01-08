<?php
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        if (isset($_SESSION["role"]) && $_SESSION["role"] == 1)
        {
            // get additional required files
            include("../../includes/config.php");

            // connect to the database
            $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

            // get period ID POST
            if (isset($_POST["period_id"]) && $_POST["period_id"] <> "") { $period_id = $_POST["period_id"]; } else { $period_id = null; }

            if ($period_id != null)
            {
                // verify that the period selected is not the active period
                $verifyStatus = mysqli_prepare($conn, "SELECT active FROM periods WHERE id=?");
                mysqli_stmt_bind_param($verifyStatus, "i", $period_id);
                if (mysqli_stmt_execute($verifyStatus))
                {
                    $verifyStatusResult = mysqli_stmt_get_result($verifyStatus);
                    if (mysqli_num_rows($verifyStatusResult) > 0) // period exists
                    {
                        $status = mysqli_fetch_array($verifyStatusResult)["active"];

                        if ($status == 0) // period is not the active period; continue period deletion
                        {                                    
                            $deletePeriod = mysqli_prepare($conn, "DELETE FROM periods WHERE id=?");
                            mysqli_stmt_bind_param($deletePeriod, "i", $period_id);
                            if (mysqli_stmt_execute($deletePeriod)) 
                            { 
                                echo "<span class=\"log-success\">Successfully</span> deleted the period. ";

                                // delete employee benefits and compensation associated with this period
                                $deleteCompensation = mysqli_prepare($conn, "DELETE FROM employee_compensation WHERE period_id=?");
                                mysqli_stmt_bind_param($deleteCompensation, "i", $period_id);
                                if (mysqli_stmt_execute($deleteCompensation)) { echo "<span class=\"log-fail\">Failed</span> to delete employee benefits and compensation within this period. "; }
                                
                                // delete all costs associated with this period
                                $deleteCosts = mysqli_prepare($conn, "DELETE FROM costs WHERE period_id=?");
                                mysqli_stmt_bind_param($deleteCosts, "i", $period_id);
                                if (!mysqli_stmt_execute($deleteCosts)) { echo "<span class=\"log-fail\">Failed</span> to delete the services costs within this period. "; }

                                // delete all quarterly costs for the invoices within the period being deleted
                                $deleteQuarterlyCosts = mysqli_prepare($conn, "DELETE FROM quarterly_costs WHERE period_id=?");
                                mysqli_stmt_bind_param($deleteQuarterlyCosts, "i", $period_id);
                                if (mysqli_stmt_execute($deleteQuarterlyCosts)) // successfully deleted quarterly costs; proceed with deleting the provided services
                                { 
                                    // delete all entries in the services_provided table associated with this period
                                    $deleteInvoices = mysqli_prepare($conn, "DELETE FROM services_provided WHERE period_id=?");
                                    mysqli_stmt_bind_param($deleteInvoices, "i", $period_id);
                                    if (!mysqli_stmt_execute($deleteInvoices)) { echo "<span class=\"log-fail\">Failed</span> to delete the services provided invoices within this period. "; }                                    
                                } 
                                else { echo "<span class=\"log-fail\">Failed</span> to delete the quarterly costs associated within this period. Failed to delete the services provided invoices within this period. "; }

                                // delete all other quarterly costs for the invoices within the period being deleted
                                $deleteOtherQuarterlyCosts = mysqli_prepare($conn, "DELETE FROM other_quarterly_costs WHERE period_id=?");
                                mysqli_stmt_bind_param($deleteOtherQuarterlyCosts, "i", $period_id);
                                if (mysqli_stmt_execute($deleteOtherQuarterlyCosts)) // successfully deleted quarterly costs; proceed with deleting the provided services
                                { 
                                    // delete all entries in the services_other_provided table associated with this period
                                    $deleteOtherInvoices = mysqli_prepare($conn, "DELETE FROM services_other_provided WHERE period_id=?");
                                    mysqli_stmt_bind_param($deleteOtherInvoices, "i", $period_id);
                                    if (!mysqli_stmt_execute($deleteOtherInvoices)) { echo "<span class=\"log-fail\">Failed</span> to delete the services provided invoices for \"other services\" within this period. "; }                                    
                                } 
                                else { echo "<span class=\"log-fail\">Failed</span> to delete the quarterly costs for \"other services\" associated within this period. Failed to delete the services provided invoices for \"other services\" within this period. "; }

                                // delete additional revenue sources for this period
                                $deleteRevenues = mysqli_prepare($conn, "DELETE FROM revenues WHERE period_id=?");
                                mysqli_stmt_bind_param($deleteRevenues, "i", $period_id);
                                if (!mysqli_stmt_execute($deleteRevenues)) { echo "<span class=\"log-fail\">Failed</span> to delete the other revenues within this period. "; }

                                // delete all the project employees for this period
                                $deleteProjectEmployees = mysqli_prepare($conn, "DELETE FROM project_employees WHERE period_id=?");
                                mysqli_stmt_bind_param($deleteProjectEmployees, "i", $period_id);
                                if (!mysqli_stmt_execute($deleteProjectEmployees)) { echo "<span class=\"log-fail\">Failed</span> to delete the project employees within this period. "; }

                                // delete all the project expenses for this period
                                $deleteProjectExpenses = mysqli_prepare($conn, "DELETE FROM project_expenses WHERE period_id=?");
                                mysqli_stmt_bind_param($deleteProjectExpenses, "i", $period_id);
                                if (!mysqli_stmt_execute($deleteProjectExpenses)) { echo "<span class=\"log-fail\">Failed</span> to delete the project expenses within this period. "; }

                                // delete the global expenses for this period
                                $deleteGlobalExpenses = mysqli_prepare($conn, "DELETE FROM global_expenses WHERE period_id=?");
                                mysqli_stmt_bind_param($deleteGlobalExpenses, "i", $period_id);
                                if (!mysqli_stmt_execute($deleteGlobalExpenses)) { echo "<span class=\"log-fail\">Failed</span> to delete the project expenses within this period. "; }

                                // delete the contract settings for this period
                                $deleteContractSettings = mysqli_prepare($conn, "DELETE FROM customer_contracts WHERE period_id=?");
                                mysqli_stmt_bind_param($deleteContractSettings, "i", $period_id);
                                if (!mysqli_stmt_execute($deleteContractSettings)) { echo "<span class=\"log-fail\">Failed</span> to delete the customer contract settings within this period. "; }

                                // log period deletion
                                $message = "Successfully deleted the period with the ID of $period_id.";
                                $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                                mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                                mysqli_stmt_execute($log);
                            }
                            else { echo "<span class=\"log-fail\">Failed</span> to delete the period. "; }
                        }
                        else if ($status == 1) { echo "<span class=\"log-fail\">Failed</span> to delete the period. You cannot delete the active period! "; } // period is the active period; DO NOT delete
                    }
                    else { echo "<span class=\"log-fail\">Failed</span> to delete the period. The period you selected to delete does not exist! "; } // period does not exist
                }
                else { echo "<span class=\"log-fail\">Failed</span> to delete the period. An unexpected error has occurred! Please try again later. "; }
            }
            else { echo "<span class=\"log-fail\">Failed</span> to delete the period. You must select a period to delete. "; }

            // disconnect from the database
            mysqli_close($conn);
        }
    }
?>