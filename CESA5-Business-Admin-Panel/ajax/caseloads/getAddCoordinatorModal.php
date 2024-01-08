<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // initialize array to hold caseloads to be displayed
        $caseloads = [];
        
        // get additional required files
        include("../../includes/functions.php");
        include("../../includes/config.php");
        include("../../getSettings.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "VIEW_CASELOADS_ALL") && checkUserPermission($conn, "VIEW_THERAPISTS"))
        {
            ?>
                <div class="modal fade" tabindex="-1" role="dialog" id="addCoordinatorModal" data-bs-backdrop="static" aria-labelledby="addCoordinatorModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg" role="document">
                        <div class="modal-content">
                            <div class="modal-header primary-modal-header">
                                <h5 class="modal-title primary-modal-title" id="addCoordinatorModalLabel">Add Coordinator</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>

                            <div class="modal-body">
                                <div class="row align-items-center my-2">
                                    <div class="col-3 text-end"><label for="add-coordinator_id">Coordinator:</label></div>
                                    <div class="col-9">
                                        <select class="form-select w-100" id="add-coordinator_id" name="add-coordinator_id">
                                            <option></option>
                                            <?php
                                                // populate a list of all active users that can be assigned as a coordinator
                                                $getUsers = mysqli_query($conn, "SELECT id FROM users ORDER BY fname ASC, lname ASC");
                                                if (mysqli_num_rows($getUsers) > 0) // there are valid coordinators; populate list
                                                {
                                                    while ($user = mysqli_fetch_array($getUsers))
                                                    {
                                                        $user_id = $user["id"];
                                                        $user_name = getUserDisplayName($conn, $user_id);
                                                        echo "<option value=".$user_id.">".$user_name."</option>";
                                                    }
                                                }
                                            ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="row align-items-center my-2">
                                    <table id="add-coordinators-caseloads" class="report_table">
                                        <thead>
                                            <tr>
                                                <th></th>
                                                <th></th>
                                                <th style="font-size: 14px !important;">Caseload</th>
                                            </tr>
                                        </thead>
                                    </table>
                                </div>
                            </div>

                            <div class="modal-footer">
                                <button type="button" class="btn btn-primary" onclick="addCoordinator();"><i class="fa-solid fa-floppy-disk"></i> Add Coordinator</button>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> Close</button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php
        }

        // disconnect from the database
        mysqli_close($conn);
    }
?>