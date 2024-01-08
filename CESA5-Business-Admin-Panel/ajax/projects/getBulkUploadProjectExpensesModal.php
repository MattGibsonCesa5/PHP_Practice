<?php
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get the required files
        include("../../includes/config.php");
        include("../../includes/functions.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "BUDGET_PROJECTS_ALL") || checkUserPermission($conn, "BUDGET_PROJECTS_ASSIGNED"))
        {
            ?>
                <!-- Upload Project Expenses Modal -->
                <div class="modal fade" tabindex="-1" role="dialog" id="uploadBulkProjectExpensesModal" data-bs-backdrop="static" aria-labelledby="uploadBulkProjectExpensesModalLabel" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header primary-modal-header">
                                <h5 class="modal-title primary-modal-title" id="uploadBulkProjectExpensesModalLabel">Bulk Upload Project Expenses</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>

                            <form action="processBulkUploadProjectExpenses.php" method="POST" enctype="multipart/form-data">
                                <div class="modal-body">
                                    <!-- disclaimer -->
                                    <p><i class="fa-solid fa-triangle-exclamation"></i> Note: we will not check for duplicate expenses during the upload, so each upload will import a new expense!</p>
                                    
                                    <!-- period selection -->
                                    <div class="form-group mb-3">
                                        <label for="ProjExp-BulkUp-period_id"><span class="required-field">*</span> Select a period to upload project expeneses into:</label>
                                        <select class="form-select" id="ProjExp-BulkUp-period_id" name="ProjExp-BulkUp-period_id" required>
                                            <option></option>
                                            <?php
                                                // create a dropdown of periods
                                                $getPeriods = mysqli_query($conn, "SELECT id, name FROM periods ORDER BY active DESC, name DESC");
                                                if (mysqli_num_rows($getPeriods) > 0) // periods exist
                                                {
                                                    while ($period = mysqli_fetch_array($getPeriods))
                                                    {
                                                        // store period details locally
                                                        $period_id = $period["id"];
                                                        $period_name = $period["name"];

                                                        // create the option
                                                        echo "<option value='".$period_id."'>".$period_name."</option>";
                                                    }
                                                }
                                            ?>
                                        </select>
                                    </div>

                                    <!-- folder selection -->
                                    <p><label for="fileToUpload">Select a folder containing CSV file(s) following the <a class="template-link" href="https://docs.google.com/spreadsheets/d/10ug6RkyFeLNAgZgmgpH2i1F7GKUR0TGYwlSNVJB1w9U/copy" target="_blank">correct upload template</a> to upload...</label></p>
                                    <input type="file" id="files" name="files[]" aria-label="Select folder to upload." multiple directory="" webkitdirectory="" moxdirectory="" required>
                                </div>

                                <div class="modal-footer">
                                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-cloud-arrow-up"></i> Bulk Upload Project Expenses</button>
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> Close</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <!-- End Upload Project Employees Modal -->
            <?php
        }
        
        // disconnect from the database
        mysqli_close($conn);
    }
?>