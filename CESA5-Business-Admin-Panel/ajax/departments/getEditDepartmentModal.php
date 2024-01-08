<?php
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get additional required files
        include("../../includes/config.php");
        include("../../includes/functions.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);
        
        if (checkUserPermission($conn, "EDIT_EMPLOYEES"))
        {
            // get the department ID from POST
            if (isset($_POST["department_id"]) && $_POST["department_id"] <> "") { $department_id = $_POST["department_id"]; } else { $department_id = null; }

            if ($department_id != null && $department_id <> "")
            {
                // get current department details
                $getDepartmentDetails = mysqli_prepare($conn, "SELECT * FROM departments WHERE id=?");
                mysqli_stmt_bind_param($getDepartmentDetails, "i", $department_id);
                if (mysqli_stmt_execute($getDepartmentDetails))
                {
                    $departmentDetailsResults = mysqli_stmt_get_result($getDepartmentDetails);
                    if (mysqli_num_rows($departmentDetailsResults) > 0)
                    {
                        $department = mysqli_fetch_array($departmentDetailsResults);
                        $name = $department["name"];
                        $desc = $department["description"];
                        $director_id = $department["director_id"];
                        $secondary_director_id = $department["secondary_director_id"];

                        ?> 
                            <!-- Edit Department Modal -->
                            <div class="modal fade" tabindex="-1" role="dialog" id="editDepartmentModal" data-bs-backdrop="static" aria-labelledby="editDepartmentModalLabel" aria-hidden="true">
                                <div class="modal-dialog modal-lg" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header primary-modal-header">
                                            <h5 class="modal-title primary-modal-title" id="editDepartmentModalLabel">Edit Department</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>

                                        <div class="modal-body">
                                            <!-- Department Details -->
                                            <fieldset class="form-group border p-3 mb-3">
                                                <legend class="w-auto px-2 m-0 float-none fieldset-legend">Department Details</legend>

                                                <div class="row align-items-center my-2">
                                                    <div class="col-3 text-end"><label for="edit-name"><span class="required-field">*</span> Department Name:</label></div>
                                                    <div class="col-9"><input type="text" class="form-control w-100" id="edit-name" name="edit-name" value="<?php echo $name; ?>" required></div>
                                                </div>

                                                <div class="row align-items-center my-2">
                                                    <div class="col-3 text-end"><label for="edit-desc">Description:</label></div>
                                                    <div class="col-9"><input type="text" class="form-control w-100" id="edit-desc" name="edit-desc" value="<?php echo $desc; ?>" required></div>
                                                </div>

                                                <div class="row align-items-center my-2">
                                                    <div class="col-3 text-end"><label for="edit-director">Primary Director:</label></div>
                                                    <div class="col-9">
                                                        <select class="form-select w-100" id="edit-director" name="edit-director" required>
                                                            <option></option>
                                                            <?php
                                                                // populate a list of all active directors that can be assigned as the department director
                                                                $getDirectors = mysqli_query($conn, "SELECT u.id FROM users u
                                                                                                    JOIN directors d ON u.id=d.user_id
                                                                                                    WHERE u.status=1 ORDER BY u.fname ASC, u.lname ASC");
                                                                if (mysqli_num_rows($getDirectors) > 0) // there are valid directors; populate list
                                                                {
                                                                    while ($director = mysqli_fetch_array($getDirectors))
                                                                    {
                                                                        $DB_director_id = $director["id"];
                                                                        $director_name = getUserDisplayName($conn, $DB_director_id);
                                                                        if ($DB_director_id == $director_id) { echo "<option value=".$director_id." selected>".$director_name."</option>"; }
                                                                        else { echo "<option value=".$DB_director_id.">".$director_name."</option>"; }
                                                                    }
                                                                }
                                                            ?>
                                                        </select>
                                                    </div>
                                                </div>

                                                <div class="row align-items-center my-2">
                                                    <div class="col-3 text-end"><label for="edit-secondary_director">Secondary Director:</label></div>
                                                    <div class="col-9">
                                                        <select class="form-select w-100" id="edit-secondary_director" name="edit-secondary_director" required>
                                                            <option></option>
                                                            <?php
                                                                // populate a list of all active directors that can be assigned as the department director
                                                                $getDirectors = mysqli_query($conn, "SELECT u.id FROM users u
                                                                                                    JOIN directors d ON u.id=d.user_id
                                                                                                    WHERE u.status=1 ORDER BY u.fname ASC, u.lname ASC");
                                                                if (mysqli_num_rows($getDirectors) > 0) // there are valid directors; populate list
                                                                {
                                                                    while ($director = mysqli_fetch_array($getDirectors))
                                                                    {
                                                                        $DB_director_id = $director["id"];
                                                                        $director_name = getUserDisplayName($conn, $DB_director_id);
                                                                        if ($DB_director_id == $secondary_director_id) { echo "<option value=".$secondary_director_id." selected>".$director_name."</option>"; }
                                                                        else { echo "<option value=".$DB_director_id.">".$director_name."</option>"; }
                                                                    }
                                                                }
                                                            ?>
                                                        </select>
                                                    </div>
                                                </div>
                                            </fieldset>

                                            <!-- Department Members -->
                                            <fieldset class="form-group border p-3 mb-3">
                                                <legend class="w-auto px-2 m-0 float-none fieldset-legend">Department Members</legend>

                                                <table id="edit-department_members" class="report_table w-100">
                                                    <thead>
                                                        <tr>
                                                            <th>Employee ID</th>
                                                            <th>First Name</th>
                                                            <th>Last Name</th>
                                                        </tr>
                                                    </thead>
                                                </table>
                                            </fieldset>
                                        </div>

                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-primary" onclick="editDepartment(<?php echo $department_id; ?>);"><i class="fa-solid fa-floppy-disk"></i> Save Changes</button>
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> Close</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- End Edit Department Modal -->
                        <?php
                    }
                }
            }
        }
        
        // disconect from the database
        mysqli_close($conn);
    }
?>