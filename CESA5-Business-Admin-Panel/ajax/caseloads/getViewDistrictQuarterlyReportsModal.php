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
        if (checkUserPermission($conn, "VIEW_CASELOADS_ALL"))
        {
            // build default user settings array
            $USER_SETTINGS = [];
            $USER_SETTINGS["dark_mode"] = 0;
            $USER_SETTINGS["page_length"] = 10;

            // get user's settings
            $getUserSettings = mysqli_prepare($conn, "SELECT * FROM user_settings WHERE user_id=?");
            mysqli_stmt_bind_param($getUserSettings, "i", $_SESSION["id"]);
            if (mysqli_stmt_execute($getUserSettings))
            {
                $getUserSettingsResult = mysqli_stmt_get_result($getUserSettings);
                if (mysqli_num_rows($getUserSettingsResult)) // user's settings found
                {
                    $USER_SETTINGS = mysqli_fetch_array($getUserSettingsResult);
                }
            }

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
                        <!-- Export View Reports Modal -->
                        <div class="modal fade" tabindex="-1" role="dialog" id="viewDistrictReportsModal" data-bs-backdrop="static" aria-labelledby="viewDistrictReportsModalLabel" aria-hidden="true">
                            <div class="modal-dialog modal-lg" role="document">
                                <div class="modal-content">
                                    <div class="modal-header primary-modal-header">
                                        <h5 class="modal-title primary-modal-title" id="viewDistrictReportsModalLabel">View District Reports</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>

                                    <div class="modal-body">
                                        <div class="form-group row mb-3">
                                            <div class="col-6 px-2">
                                                <label for="view-period" class="form-label m-0"><span class="required-field">*</span> Period:</label>
                                                <select id="view-period" name="view-period" class="form-select" required disabled readonly>
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

                                            <div class="col-6 px-2">
                                                <label for="view-quarter" class="form-label m-0"><span class="required-field">*</span> Quarter:</label>
                                                <select id="view-quarter" name="view-quarter" class="form-select" required disabled readonly>
                                                    <?php if ($quarter == 1) { ?><option value="1" <?php if ($quarter == 1) { echo "selected"; } ?>>Q1</option><?php } ?>
                                                    <?php if ($quarter == 2) { ?><option value="2" <?php if ($quarter == 2) { echo "selected"; } ?>>Q2</option><?php } ?>
                                                    <?php if ($quarter == 3) { ?><option value="3" <?php if ($quarter == 3) { echo "selected"; } ?>>Q3</option><?php } ?>
                                                    <?php if ($quarter == 4) { ?><option value="4" <?php if ($quarter == 4) { echo "selected"; } ?>>Q4</option><?php } ?>
                                                </select>
                                            </div>
                                        </div>

                                        <!-- View Subpage Buttons -->
                                        <div class="btn-group w-100 m-0 p-0" role="group" aria-label="Button group to select which the page view">
                                            <button class="btn btn-primary btn-subpages-primary w-100 rounded-0" id="view-external-button" style="border-top: 2px solid white;" onclick="toggleView('external');" value="1">External</button>
                                            <button class="btn btn-secondary btn-subpages-primary w-100 rounded-0" id="view-internal-button" style="border-top: 2px solid white;" onclick="toggleView('internal');" value="0">Internal</button>
                                        </div>

                                        <div class="" id="external-div">
                                            <div class="table-header">
                                                <!-- Search Table -->
                                                <div class="row mx-0 p-2">
                                                    <div class="input-group h-auto p-0">
                                                        <div class="input-group-prepend">
                                                            <span class="input-group-text h-100" id="nav-search-icon">
                                                                <label for="external-view_reports-search-all"><i class="fa-solid fa-magnifying-glass"></i></label>
                                                            </span>
                                                        </div>
                                                        <input class="form-control" type="text" placeholder="Search table" id="external-view_reports-search-all" name="external-view_reports-search-all" autocomplete="off">
                                                    </div>
                                                </div>
                                            </div>
                                            <table class="report_table w-100" id="viewReports">
                                                <thead>
                                                    <tr>
                                                        <th>District</th>
                                                        <th>File(s)</th>
                                                    </tr>
                                                </thead>

                                                <tbody>
                                                <?php
                                                    // get all reports created for the districts
                                                    $directory = "../../local_data/caseloads/quarterly_billing/$period_id/$quarter/";
                                                    if (is_dir($directory) && $files = scandir($directory, 1))
                                                    {
                                                        for ($f = 0; $f < count($files); $f++)
                                                        {
                                                            // get the customer ID from the file name (ID is pre .pdf file extension)
                                                            $file = $files[$f];
                                                            $customer_id = pathinfo($file, PATHINFO_FILENAME);

                                                            // verify the customer is valid
                                                            if (is_numeric($customer_id) && verifyCustomer($conn, $customer_id))
                                                            {
                                                                // get the cusotmer's name
                                                                $customer_details = getCustomerDetails($conn, $customer_id);
                                                                $customer_name = $customer_details["name"];

                                                                // build the table row
                                                                ?>
                                                                    <tr>
                                                                        <td><?php echo $customer_name; ?></td>
                                                                        <td>
                                                                        <?php
                                                                            // initialize variable for number of files (PDFs) found
                                                                            $files_found = 0;

                                                                            // get all reports saved for this customer for this period and quarter
                                                                            $customer_directory = "../../local_data/caseloads/quarterly_billing/$period_id/$quarter/$customer_id/";
                                                                            $customer_files = scandir($customer_directory, 1);
                                                                            for ($cf = 0; $cf < count($customer_files); $cf++)
                                                                            {
                                                                                // get the file
                                                                                $customer_file = $customer_files[$cf];
                                                                                $file_name = pathinfo($customer_file, PATHINFO_FILENAME);
                                                                                $file_ext = pathinfo($customer_file, PATHINFO_EXTENSION);
                                                                                
                                                                                // ensure that the file is a pdf
                                                                                if ($file_ext == "pdf")
                                                                                {
                                                                                    // increment that we found a file
                                                                                    $files_found++;

                                                                                    // create button to view the file
                                                                                    ?>
                                                                                        <div class="my-1">
                                                                                            <button class="btn btn-secondary w-100 mx-1" type='button' onclick='getViewDistrictReport(<?php echo $customer_id; ?>, <?php echo $period_id; ?>, <?php echo $quarter; ?>, "<?php echo $file_name; ?>", 0);'>
                                                                                                <?php echo $file_name; ?>.<?php echo $file_ext; ?>
                                                                                            </button>
                                                                                        </div>
                                                                                    <?php
                                                                                }
                                                                            }

                                                                            // if no PDF files found, display no files found
                                                                            if ($files_found == 0)
                                                                            {
                                                                                ?>
                                                                                    <div class="my-1">
                                                                                        <span class="missing-field">
                                                                                            No file(s) found
                                                                                        </span>
                                                                                    </div>
                                                                                <?php
                                                                            }
                                                                        ?>
                                                                        </td>
                                                                    </tr>
                                                                <?php
                                                            }
                                                        }
                                                    }
                                                ?>
                                                </tbody>
                                            </table>
                                            <?php createTableFooterV2("viewReports", "BAP_ViewCaseloadQuarterlyBillingDetailsReport", $USER_SETTINGS["page_length"], true, false); ?>
                                        </div>

                                        <div class="d-none" id="internal-div">
                                            <div class="table-header">
                                                <!-- Search Table -->
                                                <div class="row mx-0 p-2">
                                                    <div class="input-group h-auto p-0">
                                                        <div class="input-group-prepend">
                                                            <span class="input-group-text h-100" id="nav-search-icon">
                                                                <label for="internal-view_reports-search-all"><i class="fa-solid fa-magnifying-glass"></i></label>
                                                            </span>
                                                        </div>
                                                        <input class="form-control" type="text" placeholder="Search table" id="internal-view_reports-search-all" name="internal-view_reports-search-all" autocomplete="off">
                                                    </div>
                                                </div>
                                            </div>
                                            <table class="report_table w-100" id="viewInternalReports">
                                                <thead>
                                                    <tr>
                                                        <th>District</th>
                                                        <th>File(s)</th>
                                                    </tr>
                                                </thead>

                                                <tbody>
                                                <?php
                                                    // get all reports created for the districts
                                                    $directory = "../../local_data/caseloads/internal_quarterly_billing/$period_id/$quarter/";
                                                    if (is_dir($directory) && $files = scandir($directory, 1))
                                                    {
                                                        for ($f = 0; $f < count($files); $f++)
                                                        {
                                                            // get the customer ID from the file name (ID is pre .pdf file extension)
                                                            $file = $files[$f];
                                                            $customer_id = pathinfo($file, PATHINFO_FILENAME);

                                                            // verify the customer is valid
                                                            if (is_numeric($customer_id) && verifyCustomer($conn, $customer_id))
                                                            {
                                                                // get the cusotmer's name
                                                                $customer_details = getCustomerDetails($conn, $customer_id);
                                                                $customer_name = $customer_details["name"];

                                                                // build the table row
                                                                ?>
                                                                    <tr>
                                                                        <td><?php echo $customer_name; ?></td>
                                                                        <td>
                                                                        <?php
                                                                            // initialize variable for number of files (PDFs) found
                                                                            $files_found = 0;

                                                                            // get all reports saved for this customer for this period and quarter
                                                                            $customer_directory = "../../local_data/caseloads/internal_quarterly_billing/$period_id/$quarter/$customer_id/";
                                                                            $customer_files = scandir($customer_directory, 1);
                                                                            for ($cf = 0; $cf < count($customer_files); $cf++)
                                                                            {
                                                                                // get the file
                                                                                $customer_file = $customer_files[$cf];
                                                                                $file_name = pathinfo($customer_file, PATHINFO_FILENAME);
                                                                                $file_ext = pathinfo($customer_file, PATHINFO_EXTENSION);
                                                                                
                                                                                // ensure that the file is a pdf
                                                                                if ($file_ext == "pdf")
                                                                                {
                                                                                    // increment that we found a file
                                                                                    $files_found++;

                                                                                    // create button to view the file
                                                                                    ?>
                                                                                        <div class="my-1">
                                                                                            <button class="btn btn-secondary w-100 mx-1" type='button' onclick='getViewDistrictReport(<?php echo $customer_id; ?>, <?php echo $period_id; ?>, <?php echo $quarter; ?>, "<?php echo $file_name; ?>", 1);'>
                                                                                                <?php echo $file_name; ?>.<?php echo $file_ext; ?>
                                                                                            </button>
                                                                                        </div>
                                                                                    <?php
                                                                                }
                                                                            }

                                                                            // if no PDF files found, display no files found
                                                                            if ($files_found == 0)
                                                                            {
                                                                                ?>
                                                                                    <div class="my-1">
                                                                                        <span class="missing-field">
                                                                                            No file(s) found
                                                                                        </span>
                                                                                    </div>
                                                                                <?php
                                                                            }
                                                                        ?>
                                                                        </td>
                                                                    </tr>
                                                                <?php
                                                            }
                                                        }
                                                    }
                                                ?>
                                                </tbody>
                                            </table>
                                            <?php createTableFooterV2("viewInternalReports", "BAP_ViewCaseloadQuarterlyBillingDetailsReport", $USER_SETTINGS["page_length"], true, false); ?>
                                        </div>
                                    </div>

                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> Close</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- End View District Reports Modal -->
                    <?php
                }
            }
        }

        // disconnect from the database
        mysqli_close($conn);
    }
?>