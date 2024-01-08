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
            // get the parameters from POST
            if (isset($_POST["code"]) && $_POST["code"] <> "") { $code = $_POST["code"]; } else { $code = null; }
            if (isset($_POST["id"]) && $_POST["id"] <> "") { $id = $_POST["id"]; } else { $id = null; }
            if (isset($_POST["period"]) && $_POST["period"] <> "") { $period = $_POST["period"]; } else { $period = null; }

            if ($code != null && $id != null && $period != null)
            {
                if ($period_id = getPeriodID($conn, $period)) // verify the period exists; if it exists, store the period ID
                {
                    if (verifyProject($conn, $code)) // verify the project exists
                    {
                        // get project expense details
                        $getExpenseDetails = mysqli_prepare($conn, "SELECT * FROM project_expenses WHERE id=? AND project_code=? AND period_id=?");
                        mysqli_stmt_bind_param($getExpenseDetails, "isi", $id, $code, $period_id);
                        if (mysqli_stmt_execute($getExpenseDetails))
                        {
                            $getExpenseDetailsResults = mysqli_stmt_get_result($getExpenseDetails);
                            if (mysqli_num_rows($getExpenseDetailsResults))
                            {
                                $expenseDetails = mysqli_fetch_array($getExpenseDetailsResults);
                                $expense_id = $expenseDetails["expense_id"];
                                $cost = $expenseDetails["cost"];
                                $fund = $expenseDetails["fund_code"];
                                $func = $expenseDetails["function_code"];
                                $desc = $expenseDetails["description"];
                                $auto = $expenseDetails["auto"];

                                // get the expenses name based on it's ID
                                $getExpenseName = mysqli_prepare($conn, "SELECT name FROM expenses WHERE id=?");
                                mysqli_stmt_bind_param($getExpenseName, "i", $expense_id);
                                if (mysqli_stmt_execute($getExpenseName))
                                {
                                    $getExpenseNameResult = mysqli_stmt_get_result($getExpenseName);
                                    if (mysqli_num_rows($getExpenseNameResult) > 0) // expense exists; proceed
                                    {
                                        $expense_name = mysqli_fetch_array($getExpenseNameResult)["name"];                    
                                        
                                        ?>
                                            <div class="modal fade" tabindex="-1" role="dialog" id="editProjectExpenseModal" data-bs-backdrop="static" aria-labelledby="editProjectExpenseModalLabel" aria-hidden="true">
                                                <div class="modal-dialog" role="document">
                                                    <div class="modal-content">
                                                        <div class="modal-header primary-modal-header">
                                                            <h5 class="modal-title primary-modal-title" id="editProjectExpenseModalLabel">Edit Project Expense</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>

                                                        <div class="modal-body">
                                                            <?php if ($auto == 1) { ?>
                                                                <div class="row align-items-center my-2 px-3">
                                                                    <div class="alert alert-warning">
                                                                        <p class="m-0">This project expense is autocalculated. You'll be able to set the fund and function code, but not set the cost or description.</p>
                                                                    </div>
                                                                </div>
                                                            <?php } ?>

                                                            <div class="row align-items-center my-2">
                                                                <div class="col-4 text-end"><label for="edit-project_expense-expense"><span class="required-field">*</span> Expense Type:</label></div>
                                                                <div class="col-8"><input class="form-control" id="edit-project_expense-expense" name="edit-project_expense-expense" value="<?php echo $expense_name; ?>" disabled></div>
                                                            </div>

                                                            <div class="row align-items-center my-2">
                                                                <div class="col-4 text-end"><label for="edit-project_expense-cost"><span class="required-field">*</span> Cost:</label></div>
                                                                <div class="col-8">
                                                                    <div class="input-group w-100 h-auto">
                                                                        <div class="input-group-prepend">
                                                                            <span class="input-group-text h-100" id="nav-search-icon"><i class="fa-solid fa-dollar-sign"></i></span>
                                                                        </div>    
                                                                        <input type="number" class="form-control" id="edit-project_expense-cost" name="edit-project_expense-cost" value="<?php echo $cost; ?>" <?php if ($auto == 1) { echo "disabled readonly"; } ?> min="0" required>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <div class="row align-items-center my-2">
                                                                <div class="col-4 text-end"><label for="edit-project_expense-fund">Fund Code:</label></div>
                                                                <div class="col-8"><input class="form-control" id="edit-project_expense-fund" name="edit-project_expense-fund" value="<?php echo $fund; ?>"></div>
                                                            </div>

                                                            <div class="row align-items-center my-2">
                                                                <div class="col-4 text-end"><label for="edit-project_expense-func">Function Code:</label></div>
                                                                <div class="col-8"><input class="form-control" id="edit-project_expense-func" name="edit-project_expense-func" value="<?php echo $func; ?>"></div>
                                                            </div>

                                                            <div class="row align-items-center my-2">
                                                                <div class="col-4 text-end"><label for="edit-project_expense-desc">Description:</label></div>
                                                                <div class="col-8"><input class="form-control" id="edit-project_expense-desc" name="edit-project_expense-desc" value="<?php echo $desc; ?>" <?php if ($auto == 1) { echo "disabled readonly"; } ?>></div>
                                                            </div>
                                                        </div>

                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-primary" onclick="editProjectExpense(<?php echo $id; ?>);"><i class="fa-solid fa-floppy-disk"></i> Save Changes</button>
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
            }
        }
        
        // disconnect from the database
        mysqli_close($conn);
    }
?>