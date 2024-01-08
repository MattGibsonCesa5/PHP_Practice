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

        if ((checkUserPermission($conn, "VIEW_PROJECT_BUDGETS_ALL") || checkUserPermission($conn, "VIEW_PROJECT_BUDGETS_ASSIGNED")) && (checkUserPermission($conn, "VIEW_EMPLOYEES_ALL") || checkUserPermission($conn, "VIEW_EMPLOYEES_ASSIGNED")))
        {
            // get the parameters from POST
            if (isset($_POST["employee_id"]) && $_POST["employee_id"] <> "") { $employee_id = $_POST["employee_id"]; } else { $employee_id = null; }
            if (isset($_POST["period_id"]) && $_POST["period_id"] <> "") { $period_id = $_POST["period_id"]; } else { $period_id = null; }

            if (verifyPeriod($conn, $period_id))
            {
                // store the period name locally
                $period_name = getPeriodName($conn, $period_id);

                if (checkExistingEmployee($conn, $employee_id))
                {
                    if (verifyUserEmployee($conn, $_SESSION["id"], $employee_id))
                    {
                        // get the employee display name
                        $name = getEmployeeDisplayName($conn, $employee_id);

                        ?>
                            <div class="modal fade" tabindex="-1" role="dialog" id="employeeProjectsModal" data-bs-backdrop="static" aria-labelledby="employeeProjectsModalLabel" aria-hidden="true">
                                <div class="modal-dialog" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header primary-modal-header">
                                            <h5 class="modal-title primary-modal-title" id="employeeProjectsModalLabel">Employee Projects (<?php echo $period_name; ?>)</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>

                                        <div class="modal-body p-0">
                                            <div class="table-header p-1">
                                                <div class="row mb-1">
                                                    <h1 class="text-center m-0"><?php echo $name; ?></h1>
                                                </div>
                                                <div class="row text-center">
                                                    <?php createPageLengthContainer("view-employees_projects", "BAP_ViewEmployeesProjects_PageLength", $USER_SETTINGS["page_length"]); ?>
                                                </div>
                                            </div>

                                            <table id="view-employees_projects" class="report_table w-100">
                                                <thead>
                                                    <tr>
                                                        <th class="text-center">Project Code</th>
                                                        <th class="text-center">Name</th>
                                                        <th class="text-center">Days In Project</th>
                                                    </tr>
                                                </thead>

                                                <tbody>
                                                    <?php
                                                        $totalBudgetedDays = 0;
                                                        $getProjects = mysqli_prepare($conn, "SELECT p.code, p.name, pe.project_days FROM projects p
                                                                                                JOIN project_employees pe ON p.code=pe.project_code
                                                                                                WHERE pe.employee_id=? AND pe.period_id=?");
                                                        mysqli_stmt_bind_param($getProjects, "ii", $employee_id, $period_id);
                                                        if (mysqli_stmt_execute($getProjects))
                                                        {
                                                            $getProjectsResults = mysqli_stmt_get_result($getProjects);
                                                            if (mysqli_num_rows($getProjectsResults) > 0)
                                                            {
                                                                while ($project = mysqli_fetch_array($getProjectsResults))
                                                                {
                                                                    // store project budget details locally
                                                                    $projectCode = $project["code"];
                                                                    $projectName = $project["name"];
                                                                    $daysInProject = $project["project_days"];

                                                                    // add days in project to total days in project
                                                                    $totalBudgetedDays += $daysInProject;

                                                                    ?>
                                                                        <tr>
                                                                            <td class="text-center"><?php echo getProjectLink($projectCode, $period_id, true); ?></td>
                                                                            <td class="text-center"><?php echo $projectName; ?></td>
                                                                            <td class="text-center"><?php echo $daysInProject; ?></td>
                                                                        </tr>
                                                                    <?php
                                                                }
                                                            }
                                                        }
                                                    ?>
                                                </tbody>

                                                <tfoot>
                                                    <tr>
                                                        <th colspan="3" class="text-end"><?php echo $totalBudgetedDays; ?></th>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                            <?php createTableFooter("view-employees_projects", false); ?>
                                        </div>

                                        <div class="modal-footer">
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
        
        // disconnect from the database
        mysqli_close($conn);
    }
?>