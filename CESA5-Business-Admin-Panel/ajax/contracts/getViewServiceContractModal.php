<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // include config
        include("../../includes/config.php");
        include("../../includes/functions.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "VIEW_SERVICE_CONTRACTS"))
        {
            if (isset($_POST["period_id"]) && $_POST["period_id"] <> "") { $period_id = $_POST["period_id"]; } else { $period_id = null; }
            if (isset($_POST["customer_id"]) && $_POST["customer_id"] <> "") { $customer_id = $_POST["customer_id"]; } else { $customer_id = null; }

            if ($period_id != null && $customer_id != null)
            {
                // verify the period exists
                $checkPeriod = mysqli_prepare($conn, "SELECT id, name FROM periods WHERE id=?");
                mysqli_stmt_bind_param($checkPeriod, "i", $period_id);
                if (mysqli_stmt_execute($checkPeriod))
                {
                    $checkPeriodResult = mysqli_stmt_get_result($checkPeriod);
                    if (mysqli_num_rows($checkPeriodResult) > 0) // period exists; continue
                    {
                        $period_details = mysqli_fetch_array($checkPeriodResult);
                        $period_name = $period_details["name"];

                        // verify the customer exists
                        $checkCustomer = mysqli_prepare($conn, "SELECT id, name FROM customers WHERE id=?");
                        mysqli_stmt_bind_param($checkCustomer, "i", $customer_id);
                        if (mysqli_stmt_execute($checkCustomer))
                        {
                            $checkCustomerResult = mysqli_stmt_get_result($checkCustomer);
                            if (mysqli_num_rows($checkCustomerResult) > 0) // customer exists; continue
                            {
                                $customer_details = mysqli_fetch_array($checkCustomerResult);
                                $customer_name = $customer_details["name"];

                                // create the filename of the PDF
                                $filename = "local_data/service_contracts/$period_id/$customer_id.pdf";

                                ?>
                                    <div class="modal fade" tabindex="-1" role="dialog" id="viewContractModal" data-bs-backdrop="static" aria-labelledby="viewContractModalLabel" aria-hidden="true">
                                        <div class="modal-dialog modal-xl" role="document">
                                            <div class="modal-content">
                                                <div class="modal-header primary-modal-header">
                                                    <h5 class="modal-title primary-modal-title" id="viewContractModalLabel">View Contract</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>

                                                <div class="modal-body">
                                                    <h2><?php echo $customer_name; ?> (<?php echo $period_name; ?>)</h2>
                                                    <div class="alert alert-warning row" role="alert">
                                                        <div class="col-4 col-sm-4 col-md-3 col-lg-2 col-xl-1 col-xxl-1">
                                                            <span class="fa-stack fa-2x">
                                                                <i class="fa-solid fa-square fa-stack-2x"></i>
                                                                <i class="fa-solid fa-exclamation fa-stack-1x fa-inverse"></i>
                                                            </span>
                                                        </div>
                                                        <div class="col-8 co-sm-8 col-md-9 col-lg-10 col-xl-11 col-xxl-11">
                                                            <p class="d-inline">
                                                                WARNING: there are instances where the contract shown is not the most recently created contract. 
                                                                This is due to the browser caching an earlier version of the contract. 
                                                                To prevent this, we recommend using an incognito window to view the most recent contract.
                                                            </p>
                                                        </div>
                                                    </div>
                                                    <embed src="<?php echo $filename; ?>" width="100%" height="768px"/>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php
                            }
                        }                        
                    }
                }
            }
        }

        // disconnect from the database
        mysqli_close($conn);
    }
?>