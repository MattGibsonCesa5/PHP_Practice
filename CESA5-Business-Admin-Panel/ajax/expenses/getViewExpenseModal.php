<?php
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get additional required files
        include("../../includes/config.php");
        include("../../includes/functions.php");
        
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

        if (checkUserPermission($conn, "VIEW_PROJECT_EXPENSES"))
        {
            // get parameters from POST
            if (isset($_POST["expense_id"]) && $_POST["expense_id"] <> "") { $expense_id = $_POST["expense_id"]; } else { $expense_id = null; }
            if (isset($_POST["period"]) && $_POST["period"] <> "") { $period = $_POST["period"]; } else { $period = null; }

            if ($expense_id != null && $period != null)
            {
                if ($period_id = getPeriodID($conn, $period)) // verify the period exists; if it exists, store the period ID
                {
                    // get current expense details
                    $getExpenseDetails = mysqli_prepare($conn, "SELECT * FROM expenses WHERE id=?");
                    mysqli_stmt_bind_param($getExpenseDetails, "i", $expense_id);
                    if (mysqli_stmt_execute($getExpenseDetails))
                    {
                        $expenseDetailsResults = mysqli_stmt_get_result($getExpenseDetails);
                        if (mysqli_num_rows($expenseDetailsResults) > 0) // expense exists
                        {
                            $expense = mysqli_fetch_array($expenseDetailsResults);
                            $name = $expense["name"];
                            $desc = $expense["description"];
                            $loc = $expense["location_code"];
                            $obj = $expense["object_code"];
                            $status = $expense["status"];

                            ?> 
                                <!-- Edit Expense Modal -->
                                <div class="modal fade" tabindex="-1" role="dialog" id="viewExpenseModal" data-bs-backdrop="static" aria-labelledby="viewExpenseModalLabel" aria-hidden="true">
                                    <div class="modal-dialog modal-lg" role="document">
                                        <div class="modal-content">
                                            <div class="modal-header primary-modal-header">
                                                <h5 class="modal-title primary-modal-title" id="viewExpenseModalLabel">View Expense</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>

                                            <div class="modal-body p-0">
                                                <div class="table-header p-1">
                                                    <div class="row mb-1">
                                                        <h1 class="text-center m-0"><?php echo $name; ?></h1>
                                                    </div>
                                                    <div class="row text-center">
                                                        <?php createPageLengthContainer("view-expense", "BAP_ViewExpense_PageLength", $USER_SETTINGS["page_length"]); ?>
                                                    </div>
                                                </div>

                                                <table id="view-expense" class="report_table w-100">
                                                    <thead>
                                                        <tr>
                                                            <th class="text-center" colspan="5">WUFAR Codes</th>
                                                            <th class="text-center" rowspan="2">Cost</th>
                                                            <th class="text-center" rowspan="2">Description</th>
                                                        </tr>

                                                        <tr>
                                                            <th class="text-center">Fund</th>
                                                            <th class="text-center">Location</th>
                                                            <th class="text-center">Object</th>
                                                            <th class="text-center">Function</th>
                                                            <th class="text-center">Project</th>
                                                        </tr>
                                                    </thead>

                                                    <tbody>
                                                        <?php
                                                            // get each instance this expense has been added to a project in the active period
                                                            $getProjectExpenses = mysqli_prepare($conn, "SELECT * FROM project_expenses WHERE expense_id=? AND period_id=?");
                                                            mysqli_stmt_bind_param($getProjectExpenses, "ii", $expense_id, $period_id);
                                                            if (mysqli_stmt_execute($getProjectExpenses))
                                                            {
                                                                $getProjectExpensesResults = mysqli_stmt_get_result($getProjectExpenses);
                                                                if (mysqli_num_rows($getProjectExpensesResults) > 0) // expense is in projects; display expense entry
                                                                {
                                                                    while ($projectExpense = mysqli_fetch_array($getProjectExpensesResults))
                                                                    {
                                                                        echo "<tr>
                                                                            <td class='text-center'>".$projectExpense["fund_code"]."</td>
                                                                            <td class='text-center'>".$loc."</td>
                                                                            <td class='text-center'>".$obj."</td>
                                                                            <td class='text-center'>".$projectExpense["function_code"]."</td>
                                                                            <td class='text-center'>".getProjectLink($projectExpense["project_code"], $period_id, true)."</td>
                                                                            <td class='text-center'>".printDollar($projectExpense["cost"])."</td>
                                                                            <td class='text-center'>".$projectExpense["description"]."</td>
                                                                        </tr>";
                                                                    }
                                                                }
                                                            }
                                                        ?>
                                                    </tbody>
                                                </table>
                                                <?php createTableFooter("view-expense", false); ?>
                                            </div>

                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> Close</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- End Edit Expense Modal -->
                            <?php
                        }
                    }
                }

                // disconect from the database
                mysqli_close($conn);
            } 
        }
    }
?>