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
            if (isset($_POST["project_expense_id"]) && $_POST["project_expense_id"] <> "") { $project_expense_id = $_POST["project_expense_id"]; } else { $project_expense_id = null; }

            if ($project_expense_id != null)
            {
                // get project expense details
                $getExpenseDetails = mysqli_prepare($conn, "SELECT * FROM project_expenses WHERE id=?");
                mysqli_stmt_bind_param($getExpenseDetails, "i", $project_expense_id);
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
                                    <div class="modal fade" tabindex="-1" role="dialog" id="cloneProjectExpenseModal" data-bs-backdrop="static" aria-labelledby="cloneProjectExpenseModalLabel" aria-hidden="true">
                                        <div class="modal-dialog" role="document">
                                            <div class="modal-content">
                                                <div class="modal-header primary-modal-header">
                                                    <h5 class="modal-title primary-modal-title" id="cloneProjectExpenseModalLabel">Edit Project Expense</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>

                                                <div class="modal-body">
                                                    <div class="row align-items-center my-2">
                                                        <div class="col-4 text-end"><label for="clone-project_expense-expense"><span class="required-field">*</span> Expense Type:</label></div>
                                                        <div class="col-8">
                                                            <input class="form-control" id="clone-project_expense-expense" name="clone-project_expense-expense" value="<?php echo $expense_name; ?>" required disabled readonly>
                                                            <input type="hidden" class="form-control" id="clone-project_expense-expense_id" name="clone-project_expense-expense_id" value="<?php echo $expense_id; ?>" aria-hidden="true" required disabled readonly>
                                                        </div>
                                                    </div>

                                                    <div class="row align-items-center my-2">
                                                        <div class="col-4 text-end"><label for="clone-project_expense-cost"><span class="required-field">*</span> Cost:</label></div>
                                                        <div class="col-8">
                                                            <div class="input-group w-100 h-auto">
                                                                <div class="input-group-prepend">
                                                                    <span class="input-group-text h-100" id="nav-search-icon"><i class="fa-solid fa-dollar-sign"></i></span>
                                                                </div>    
                                                                <input type="number" class="form-control" id="clone-project_expense-cost" name="clone-project_expense-cost" value="<?php echo $cost; ?>" min="0" required>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="row align-items-center my-2">
                                                        <div class="col-4 text-end"><label for="clone-project_expense-fund">Fund Code:</label></div>
                                                        <div class="col-8"><input type="number" class="form-control" id="clone-project_expense-fund" name="clone-project_expense-fund" min="10" max="99" value="<?php echo $fund; ?>"></div>
                                                    </div>

                                                    <div class="row align-items-center my-2">
                                                        <div class="col-4 text-end"><label for="clone-project_expense-func">Function Code:</label></div>
                                                        <div class="col-8"><input type="number" class="form-control" id="clone-project_expense-func" name="clone-project_expense-func" value="<?php echo $func; ?>"></div>
                                                    </div>

                                                    <div class="row align-items-center my-2">
                                                        <div class="col-4 text-end"><label for="clone-project_expense-desc">Description:</label></div>
                                                        <div class="col-8"><input class="form-control" id="clone-project_expense-desc" name="clone-project_expense-desc" value="<?php echo $desc; ?>"></div>
                                                    </div>
                                                </div>

                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-primary" onclick="cloneProjectExpense();"><i class="fa-solid fa-plus"></i> Add Expense</button>
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