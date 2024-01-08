<?php
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get additional required files
        include("../../includes/config.php");
        include("../../includes/functions.php");
        include("../../getSettings.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

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

        if (checkUserPermission($conn, "VIEW_DEPARTMENTS_ALL") || checkUserPermission($conn, "VIEW_DEPARTMENTS_ASSIGNED"))
        {
            // get the department ID from POST
            if (isset($_POST["department_id"]) && $_POST["department_id"] <> "") { $department_id = $_POST["department_id"]; } else { $department_id = null; }

            if (($department_id != null && $department_id <> ""))
            {
                // verify that the department exists
                if (checkUserPermission($conn, "VIEW_DEPARTMENTS_ALL"))
                {
                    $verifyDepartment = mysqli_prepare($conn, "SELECT id, name, description FROM departments WHERE id=?");
                    mysqli_stmt_bind_param($verifyDepartment, "i", $department_id);
                }
                // verify that the director is assigned to this department, and that the department exists
                else if (checkUserPermission($conn, "VIEW_DEPARTMENTS_ASSIGNED"))
                {
                    $verifyDepartment = mysqli_prepare($conn, "SELECT id, name, description FROM departments WHERE id=? AND (director_id=? OR secondary_director_id=?)");
                    mysqli_stmt_bind_param($verifyDepartment, "iii", $department_id, $_SESSION["id"], $_SESSION["id"]);
                }

                // execute the prepared query
                if (mysqli_stmt_execute($verifyDepartment))
                {
                    $verifyDepartmentResult = mysqli_stmt_get_result($verifyDepartment);
                    if (mysqli_num_rows($verifyDepartmentResult) > 0) // director is assigned to this department; continue
                    {
                        // store department details
                        $department = mysqli_fetch_array($verifyDepartmentResult);
                        $name = $department["name"];
                        $desc = $department["description"];

                        // get the department members
                        $employees = []; // initialize the array to store department employees
                        $getDepartmentMembers = mysqli_prepare($conn, "SELECT e.id, e.fname, e.lname, e.email, ec.active FROM employees e 
                                                                        JOIN employee_compensation ec ON e.id=ec.employee_id
                                                                        JOIN department_members dm ON e.id=dm.employee_id 
                                                                        WHERE dm.department_id=? AND ec.period_id=?");
                        mysqli_stmt_bind_param($getDepartmentMembers, "ii", $department_id, $GLOBAL_SETTINGS["active_period"]);
                        if (mysqli_stmt_execute($getDepartmentMembers))
                        {
                            $getDepartmentMembersResults = mysqli_stmt_get_result($getDepartmentMembers);
                            if (mysqli_num_rows($getDepartmentMembersResults) > 0)
                            {
                                while ($employee = mysqli_fetch_array($getDepartmentMembersResults))
                                {
                                    // store employee data in an array
                                    $employees[] = $employee;
                                }
                            }
                        }

                        ?>
                            <!-- View Department Modal -->
                            <div class="modal fade" tabindex="-1" role="dialog" id="viewDepartmentModal" data-bs-backdrop="static" aria-labelledby="viewDepartmentModalLabel" aria-hidden="true">
                                <div class="modal-dialog modal-lg" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header primary-modal-header">
                                            <h5 class="modal-title primary-modal-title" id="viewDepartmentModalLabel">View Department</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>

                                        <div class="modal-body p-0">
                                            <div class="table-header p-1">
                                                <div class="row mb-1">
                                                    <h1 class="text-center m-0"><?php echo $name; ?></h1>
                                                </div>
                                                <div class="row text-center">
                                                    <?php createPageLengthContainer("view-department_members", "BAP_ViewDepartmentMembers_PageLength", $USER_SETTINGS["page_length"]); ?>
                                                </div>
                                            </div>

                                            <table id="view-department_members" class="report_table w-100">
                                                <thead>
                                                    <tr>
                                                        <th>ID</th>
                                                        <th>First Name</th>
                                                        <th>Last Name</th>
                                                        <th>Email</th>
                                                        <th>Status</th>
                                                    </tr>
                                                </thead>

                                                <tbody>
                                                    <?php
                                                        // display all department members
                                                        for ($e = 0; $e < count($employees); $e++)
                                                        {
                                                            ?>
                                                                <tr>
                                                                    <td><?php echo $employees[$e]["id"]; ?></td>
                                                                    <td><?php echo $employees[$e]["fname"]; ?></td>
                                                                    <td><?php echo $employees[$e]["lname"]; ?></td>
                                                                    <td><?php echo $employees[$e]["email"]; ?></td>
                                                                    <td>
                                                                    <?php
                                                                        if ($employees[$e]["active"] == 1) { echo "<div class='active-div text-center px-3 py-1 float-end'>Active</div>"; }
                                                                        else { echo "<div class='inactive-div text-center px-3 py-1 float-end'>Inactive</div>"; }
                                                                    ?>
                                                                    </td>
                                                                </tr>
                                                            <?php
                                                        }
                                                    ?>
                                                </tbody>
                                            </table>
                                            <?php createTableFooter("view-department_members", false); ?>
                                        </div>

                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> Close</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- End View Department Modal -->
                        <?php                        
                    }
                }
            }
        }

        // disconect from the database
        mysqli_close($conn);
    }
?>