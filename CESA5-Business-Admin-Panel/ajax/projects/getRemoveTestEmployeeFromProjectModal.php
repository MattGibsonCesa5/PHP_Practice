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
            // get parameters from POST
            if (isset($_POST["id"]) && $_POST["id"] <> "") { $id = $_POST["id"]; } else { $id = null; }
            if (isset($_POST["code"]) && $_POST["code"] <> "") { $code = $_POST["code"]; } else { $code = null; }
            if (isset($_POST["period"]) && $_POST["period"] <> "") { $period = $_POST["period"]; } else { $period = null; }

            if ($id != null && $code != null && $period != null)
            {
                if ($period_id = getPeriodID($conn, $period)) // verify the period exists; if it exists, store the period ID
                {
                    if (verifyProject($conn, $code)) // verify the project exists
                    {
                        // verify test employee exists
                        $checkTestEmployee = mysqli_prepare($conn, "SELECT id FROM project_employees_misc WHERE id=? AND project_code=? AND period_id=?");
                        mysqli_stmt_bind_param($checkTestEmployee, "isi", $id, $code, $period_id);
                        if (mysqli_stmt_execute($checkTestEmployee))
                        {
                            $checkTestEmployeeResult = mysqli_stmt_get_result($checkTestEmployee);
                            if (mysqli_num_rows($checkTestEmployeeResult) > 0) // test employee exists in the active period for the selected project
                            {
                                ?>
                                    <div class="modal fade" tabindex="-1" role="dialog" id="removeTestEmployeeFromProjectModal" data-bs-backdrop="static" aria-labelledby="removeTestEmployeeFromProjectModalLabel" aria-hidden="true">
                                        <div class="modal-dialog" role="document">
                                            <div class="modal-content">
                                                <div class="modal-header primary-modal-header">
                                                    <h5 class="modal-title primary-modal-title" id="removeEmployeeFromProjectModalLabel">Remove Test Employee From Project</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>

                                                <div class="modal-body">
                                                    Are you sure you want to remove this test employee from the project?
                                                </div>

                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-primary" onclick="removeTestEmployeeFromProject(<?php echo $id; ?>, '<?php echo $code; ?>');">Remove Test Employee</button>
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> Close</button>
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