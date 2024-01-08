<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        if ($_SESSION["role"] == 1)
        {
            // get additional required files
            include("../../includes/functions.php");
            include("../../includes/config.php");

            // connect to the database
            $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

            // get parameters from POST
            if (isset($_POST["period"]) && $_POST["period"] <> "") { $period = $_POST["period"]; } else { $period = null; }
            if (isset($_POST["quarter"]) && $_POST["quarter"] <> "") { $quarter = $_POST["quarter"]; } else { $quarter = null; }

            // verify the period exists; if it exists, store the period ID
            if ($period != null && $period_id = getPeriodID($conn, $period))
            {
                // verify the quarter is valid
                if (is_numeric($quarter) && ($quarter >= 1 && $quarter <= 4))
                {
                    ?>
                        <!-- Export District Reports Modal -->
                        <div class="modal fade" tabindex="-1" role="dialog" id="exportDistrictReportModal" data-bs-backdrop="static" aria-labelledby="exportDistrictReportModalLabel" aria-hidden="true">
                            <div class="modal-dialog modal-lg" role="document">
                                <div class="modal-content">
                                    <div class="modal-header primary-modal-header">
                                        <h5 class="modal-title primary-modal-title" id="exportDistrictReportModalLabel">Export District Reports</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>

                                    <form action="createDistrictCaseloadBillingReports.php" method="POST" enctype="multipart/form-data">
                                        <div class="modal-body">
                                            <p>
                                                When exporting district quarterly billing reports, we will create both one for district-use (external), and a report for internal-use only.
                                                The internal report will not be exported to Google Drive, and can be viewed and exported within the Business Admin Panel.
                                            </p>

                                            <div class="form-group mb-3">
                                                <label for="export-filename" class="form-label m-0"><span class="required-field">*</span> Enter the name for the report(s): </label>
                                                <input type="text" id="export-filename" name="export-filename" class="form-control" aria-describedby="export-filenameDesc" required>
                                                <small id="export-filenameDesc" class="form-text text-muted">
                                                    To indicate customer name in file name, use the tag {CUSTOMER}.<br>
                                                    To indicate period name in file name, use the tag {PERIOD}.<br>
                                                    To indicate quarter in file name, use the tag {QUARTER}. We'll display this as Q#.
                                                </small>
                                            </div>

                                            <div class="form-group mb-3">
                                                <label for="export-period" class="form-label m-0"><span class="required-field">*</span> Select the period to create the report(s) for: </label>
                                                <select id="export-period" name="export-period" class="form-select" required readonly>
                                                    <?php
                                                        // create a dropdown list of all periods
                                                        $getPeriods = mysqli_query($conn, "SELECT id, name FROM periods ORDER BY active DESC");
                                                        while ($period = mysqli_fetch_array($getPeriods)) 
                                                        { 
                                                            if ($period["id"] == $period_id)
                                                            {
                                                                echo "<option value='".$period["id"]."' selected>".$period["name"]."</option>"; 
                                                            }
                                                        }
                                                    ?>
                                                </select>
                                            </div>

                                            <div class="form-group mb-3">
                                                <label for="export-quarter" class="form-label m-0"><span class="required-field">*</span> Select the quarter to create the report(s) for: </label>
                                                <select id="export-quarter" name="export-quarter" class="form-select" required readonly>
                                                    <?php if ($quarter == 1) { ?><option value="1" <?php if ($quarter == 1) { echo "selected"; } ?>>Q1</option><?php } ?>
                                                    <?php if ($quarter == 2) { ?><option value="2" <?php if ($quarter == 2) { echo "selected"; } ?>>Q2</option><?php } ?>
                                                    <?php if ($quarter == 3) { ?><option value="3" <?php if ($quarter == 3) { echo "selected"; } ?>>Q3</option><?php } ?>
                                                    <?php if ($quarter == 4) { ?><option value="4" <?php if ($quarter == 4) { echo "selected"; } ?>>Q4</option><?php } ?>
                                                </select>
                                            </div>

                                            <div class="form-group mb-3">
                                                <label for="export-customers" class="form-label"><span class="required-field">*</span> Select the customer(s) to create contracts for: </label>
                                                <select id="export-customers" name="export-customers[]" class="form-select" style="height: 250px" multiple required>
                                                    <?php
                                                        // for all customers with cases in the system for the selected period and category, find the number of units being billed to them for the period
                                                        $getCustomers = mysqli_prepare($conn, "SELECT DISTINCT d.id, d.name FROM customers d 
                                                                                                JOIN cases c ON d.id=c.district_attending OR d.id=c.residency
                                                                                                JOIN caseloads cl ON c.caseload_id=cl.id
                                                                                                WHERE c.period_id=?
                                                                                                ORDER BY d.name ASC");
                                                        mysqli_stmt_bind_param($getCustomers, "i", $period_id);
                                                        if (mysqli_stmt_execute($getCustomers))
                                                        {
                                                            $getCustomersResults = mysqli_stmt_get_result($getCustomers);
                                                            if (mysqli_num_rows($getCustomersResults) > 0)
                                                            {
                                                                while ($customer = mysqli_fetch_array($getCustomersResults))
                                                                {
                                                                    // store the customer ID and name locally
                                                                    $customer_id = $customer["id"];
                                                                    $customer_name = $customer["name"];

                                                                    // create the option
                                                                    echo "<option value='".$customer_id."'>".$customer_name."</option>";
                                                                }
                                                            }
                                                        }
                                                    ?>
                                                </select>
                                            </div>

                                            <div class="form-group mb-3">
                                                <input type="hidden" id="export-upload" name="export-upload" value="0">
                                                <p class="mb-1">
                                                    <label for="export-upload" class="form-label m-0">Would you like to upload the invoices to the customers' assigned Google Drive folders?</label> 
                                                    If the customer does not have an assigned Google Drive folder, we will still store their invoice locally.
                                                </p>
                                                <?php if (!isset($GLOBAL_SETTINGS["caseloads_billing_gid"]) || trim($GLOBAL_SETTINGS["caseloads_billing_gid"]) == "") { ?>
                                                    <div class="alert alert-danger">
                                                        <p class="m-0">
                                                            WARNING: you have not set a Google Drive parent folder directory to scan through. We will scan your entire Google Drive directory looking for all folders.
                                                            To improve performance, please enter a parent folder to scan through from the admin's Manage > Admin page.
                                                        </p>
                                                    </div>
                                                <?php } ?>
                                                <button type="button" class="btn btn-secondary w-100" id="export-upload-btn" name="export-upload-btn" value="0" onclick="toggleUpload('export-upload')">No, do not upload to Google Drive</button>
                                            </div>
                                        </div>

                                        <div class="modal-footer">
                                            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-arrow-up-from-bracket"></i> Generate Reports</button>
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> Close</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <!-- End Export District Reports Modal -->
                    <?php
                }
            }

            // disconnect from the database
            mysqli_close($conn);
        }
    }
?>