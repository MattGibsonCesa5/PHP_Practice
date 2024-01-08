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
                <!-- Upload Project Employees Modal -->
                <div class="modal fade" tabindex="-1" role="dialog" id="uploadProjectEmployeesModal" data-bs-backdrop="static" aria-labelledby="uploadProjectEmployeesModalLabel" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header primary-modal-header">
                                <h5 class="modal-title primary-modal-title" id="uploadProjectEmployeesModalLabel">Upload Project Employees</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>

                            <form action="processUploadProjectEmployees.php" method="POST" enctype="multipart/form-data">
                                <div class="modal-body">
                                    <!-- period selection -->
                                    <div class="form-group mb-3">
                                        <label for="ProjEmp-Up-period_id"><span class="required-field">*</span> Select a period to upload project employees into:</label>
                                        <select class="form-select" id="ProjEmp-Up-period_id" name="ProjEmp-Up-period_id" required>
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

                                    <!-- file selection -->
                                    <p><label for="fileToUpload">Select a CSV file following the <a class="template-link" href="https://docs.google.com/spreadsheets/d/1Hj6igStbyPqHGmNpAeHcFW23MJteL7314ZHgsFgu7Bs/copy" target="_blank">correct upload template</a> to upload...</label></p>
                                    <input type="file" name="fileToUpload" id="fileToUpload" required>
                                </div>

                                <div class="modal-footer">
                                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-cloud-arrow-up"></i> Upload Project Employees</button>
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