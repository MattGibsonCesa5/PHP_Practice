<?php
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get additional required files
        include("../../includes/config.php");
        include("../../includes/functions.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "EDIT_PROJECT_EXPENSES"))
        {
            // get the expense ID from POST
            if (isset($_POST["expense_id"]) && $_POST["expense_id"] <> "") { $expense_id = $_POST["expense_id"]; } else { $expense_id = null; }

            if ($expense_id != null && $expense_id <> "")
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
                            <div class="modal fade" tabindex="-1" role="dialog" id="editExpenseModal" data-bs-backdrop="static" aria-labelledby="editExpenseModalLabel" aria-hidden="true">
                                <div class="modal-dialog" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header primary-modal-header">
                                            <h5 class="modal-title primary-modal-title" id="editExpenseModalLabel">Edit Expense</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>

                                        <div class="modal-body">
                                            <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                <!-- Expenses Name -->
                                                <div class="form-group col-11">
                                                    <label for="edit-name"><span class="required-field">*</span> Expense Name:</label>
                                                    <input type="text" class="form-control w-100" id="edit-name" name="edit-name" value="<?php echo $name; ?>" required>
                                                </div>
                                            </div>

                                            <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                <!-- Expenses Description -->
                                                <div class="form-group col-11">
                                                    <label for="edit-desc">Description:</label>
                                                    <input type="text" class="form-control w-100" id="edit-desc" name="edit-desc" value="<?php echo $desc; ?>" required>
                                                </div>
                                            </div>

                                            <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                <!-- Location Code -->
                                                <div class="form-group col-5">
                                                    <label for="edit-loc"><span class="required-field">*</span> Location Code:</label>
                                                    <input type="text" class="form-control w-100" id="edit-loc" name="edit-loc" value="<?php echo $loc; ?>" required>
                                                </div>

                                                <!-- Spacer -->
                                                <div class="form-group col-1"></div>

                                                <!-- Object Code -->
                                                <div class="form-group col-5">
                                                    <label for="edit-obj"><span class="required-field">*</span> Object Code:</label>
                                                    <input type="text" class="form-control w-100" id="edit-obj" name="edit-obj" value="<?php echo $obj; ?>" required>
                                                </div>
                                            </div>

                                            <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                <!-- Status -->
                                                <div class="form-group col-11">
                                                    <span class="required-field">*</span> Status:</label>
                                                    <?php if ($status == 1) { ?>
                                                        <button class="btn btn-success w-100" id="edit-status" value=1 onclick="updateStatus('edit-status');">Active</button>
                                                    <?php } else { ?>
                                                        <button class="btn btn-danger w-100" id="edit-status" value=0 onclick="updateStatus('edit-status');">Inactive</button>
                                                    <?php } ?>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-primary" onclick="editExpense(<?php echo $expense_id; ?>);"><i class="fa-solid fa-floppy-disk"></i> Save Changes</button>
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
        } 
        
        // disconect from the database
        mysqli_close($conn);
    }
?>