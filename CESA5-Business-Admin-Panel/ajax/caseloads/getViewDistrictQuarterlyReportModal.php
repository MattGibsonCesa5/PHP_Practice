<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get additional required files
        include("../../includes/functions.php");
        include("../../includes/config.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        // verify user permissions
        if (checkUserPermission($conn, "VIEW_CASELOADS_ALL") || (isset($_SESSION["district"]) && $_SESSION["district"]["status"] == 1))
        {
            // get parameters from POST
            if (isset($_POST["period_id"]) && $_POST["period_id"] <> "") { $period_id = $_POST["period_id"]; } else { $period_id = null; }
            if (isset($_POST["quarter"]) && $_POST["quarter"] <> "") { $quarter = $_POST["quarter"]; } else { $quarter = null; }
            if (isset($_POST["customer_id"]) && $_POST["customer_id"] <> "") { $customer_id = $_POST["customer_id"]; } else { $customer_id = null; }
            if (isset($_POST["filename"]) && $_POST["filename"] <> "") { $filename = $_POST["filename"]; } else { $filename = null; }
            if (isset($_POST["internal"]) && $_POST["internal"] == 1) { $internal = 1; } else { $internal = 0; }

            // if user is a district user, override customer ID to customer ID stored in SESSION
            if (isset($_SESSION["district"]) && $_SESSION["district"]["status"] == 1) {
                $customer_id = $_SESSION["district"]["id"];
            }

            // verify the period exists
            if (verifyPeriod($conn, $period_id))
            {
                // get the period name
                $period_name = getPeriodName($conn, $period_id);

                // verify the quarter is valid
                if (is_numeric($quarter) && ($quarter >= 1 && $quarter <= 4))
                {
                    // verify the customer
                    if ($customer_id != null && verifyCustomer($conn, $customer_id))
                    {
                        // verify a filename was sent
                        if ($filename != null && trim($filename) <> "")
                        {
                            // get customer details
                            $customer_details = getCustomerDetails($conn, $customer_id);
                            $customer_name = $customer_details["name"];

                            if ($internal == 1 && !isset($_SESSION["district"]) && checkUserPermission($conn, "VIEW_CASELOADS_ALL"))
                            {
                                // create the filename of the PDF
                                $filepath = "local_data/caseloads/internal_quarterly_billing/$period_id/$quarter/$customer_id/$filename.pdf";

                                ?>
                                    <!-- View Report Modal -->
                                    <div class="modal fade" tabindex="-1" role="dialog" id="viewDistrictReportModal" data-bs-backdrop="static" aria-labelledby="viewDistrictReportModalLabel" aria-hidden="true">
                                        <div class="modal-dialog modal-xl" role="document">
                                            <div class="modal-content">
                                                <div class="modal-header primary-modal-header">
                                                    <h5 class="modal-title primary-modal-title" id="viewDistrictReportModalLabel"><?php echo $customer_name; ?> (<?php echo $period_name; ?> Q<?php echo $quarter; ?> (INTERNAL-USE ONLY))</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>

                                                <div class="modal-body">
                                                    <embed src="<?php echo $filepath; ?>" width="100%" height="768px"/>
                                                </div>

                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> Close</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- End View Report Modal -->
                                <?php
                            }
                            else
                            {
                                // create the filename of the PDF
                                $filepath = "local_data/caseloads/quarterly_billing/$period_id/$quarter/$customer_id/$filename.pdf";

                                ?>
                                    <!-- View Report Modal -->
                                    <div class="modal fade" tabindex="-1" role="dialog" id="viewDistrictReportModal" data-bs-backdrop="static" aria-labelledby="viewDistrictReportModalLabel" aria-hidden="true">
                                        <div class="modal-dialog modal-xl" role="document">
                                            <div class="modal-content">
                                                <div class="modal-header primary-modal-header">
                                                    <h5 class="modal-title primary-modal-title" id="viewDistrictReportModalLabel"><?php echo $customer_name; ?> (<?php echo $period_name; ?> Q<?php echo $quarter; ?>)</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>

                                                <div class="modal-body">
                                                    <embed src="<?php echo $filepath; ?>" width="100%" height="768px"/>
                                                </div>

                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> Close</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- End View Report Modal -->
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