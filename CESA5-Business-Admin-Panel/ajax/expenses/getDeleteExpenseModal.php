<?php
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get additional required files
        include("../../includes/config.php");
        include("../../includes/functions.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "DELETE_PROJECT_EXPENSES"))
        {
            // get the expense ID from POST
            if (isset($_POST["expense_id"]) && $_POST["expense_id"] <> "") { $expense_id = $_POST["expense_id"]; } else { $expense_id = null; }

            if ($expense_id != null)
            {
                // verify that the expense exists
                $checkExpense = mysqli_prepare($conn, "SELECT id FROM expenses WHERE id=?");
                mysqli_stmt_bind_param($checkExpense, "i", $expense_id);
                if (mysqli_stmt_execute($checkExpense))
                {
                    $checkExpenseResult = mysqli_stmt_get_result($checkExpense);
                    if (mysqli_num_rows($checkExpenseResult) > 0) // expense exists; continue deletion
                    {
                        ?>
                            <div class="modal fade" tabindex="-1" role="dialog" id="deleteExpenseModal" data-bs-backdrop="static" aria-labelledby="deleteExpenseModalLabel" aria-hidden="true">
                                <div class="modal-dialog" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header primary-modal-header">
                                            <h5 class="modal-title primary-modal-title" id="deleteExpenseModalLabel">Delete Expense</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>

                                        <div class="modal-body">
                                            <p>Are you sure you want to delete this expense? This expense will be deleted from all project's budget reports in the active period.</p>
                                            <p>
                                                Deleting this expense could lead to historical data inaccuracies as the expense details will be deleted. If you want to keep accurate
                                                historical data, we recommend setting this expense's status to inactive.
                                            </p>
                                        </div>

                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-primary" onclick="deleteExpense(<?php echo $expense_id; ?>);"><i class="fa-solid fa-trash-can"></i> Delete Expense</button>
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