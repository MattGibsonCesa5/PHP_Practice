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
            // initialize the variable to store the default fund code
            $default_fund = null;

            // get the project code from POST
            if (isset($_POST["project"]) && $_POST["project"] <> "") { $code = $_POST["project"]; } else { $code = null; }

            // if the project was set, get and store the project's fund code
            if ($code != null)
            {
                // get the project's fund and function codes
                $fund_code = $function_code = null; 
                $getCodes = mysqli_prepare($conn, "SELECT fund_code, function_code FROM projects WHERE code=?");
                mysqli_stmt_bind_param($getCodes, "s", $code);
                if (mysqli_stmt_execute($getCodes))
                {
                    $getCodesResult = mysqli_stmt_get_result($getCodes);
                    if (mysqli_num_rows($getCodesResult) > 0)
                    {
                        $codes = mysqli_fetch_array($getCodesResult);
                        $fund_code = $codes["fund_code"];
                        $function_code = $codes["function_code"];
                    }
                }
            }
            
            ?>
                <div class="modal fade" tabindex="-1" role="dialog" id="addExpensesToProjectModal" data-bs-backdrop="static" aria-labelledby="addExpensesToProjectModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg" role="document">
                        <div class="modal-content">
                            <div class="modal-header primary-modal-header">
                                <h5 class="modal-title primary-modal-title" id="addExpensesToProjectModalLabel">Add Expense To Project</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>

                            <div class="modal-body">
                                <input type="hidden" id="add-expense_to_project-numOfRanges" value="1" aria-hidden="true">

                                <div class="row align-items-center my-2">
                                    <div class="col-3 text-center py-0 px-1"><label id="add-expense_to_project-expense"><span class="required-field">*</span> <b>Expense</b></label></div>
                                    <div class="col-2 text-center py-0 px-1"><label id="add-expense_to_project-cost"><span class="required-field">*</span> <b>Cost</b></label></div>
                                    <div class="col-2 text-center py-0 px-1"><label id="add-expense_to_project-fund"><span class="required-field">*</span> <b>Fund Code</b></label></div>
                                    <div class="col-2 text-center py-0 px-1"><label id="add-expense_to_project-func"><span class="required-field">*</span> <b>Function Code</b></label></div>
                                    <div class="col-3 text-center py-0 px-1"><label id="add-expense_to_project-func"><b>Description</b></label></div>
                                </div>

                                <div class="row align-items-center my-2" id="add-expense_to_project-expenses_grid">
                                    <div class="row m-0 p-0 mb-1" id="add-expense_to_project-row-1">
                                        <div class="col-3 py-0 px-1">
                                            <select class="form-select w-100" id="add-expense_to_project-expense-1" name="add-expense_to_project-expense" required>
                                                <option></option>
                                                <?php
                                                    // create a dropdown of all active expenses
                                                    $getExpenses = mysqli_query($conn, "SELECT id, name, object_code FROM expenses WHERE status=1 AND global=0 ORDER BY name ASC");
                                                    while ($expense = mysqli_fetch_array($getExpenses))
                                                    {
                                                        // store expense details locally
                                                        $id = $expense["id"];
                                                        $name = $expense["name"];
                                                        $obj = $expense["object_code"];

                                                        // build option display
                                                        $display = $name;
                                                        if (isset($obj) && trim($obj) <> "") { $display .= " (".$obj.")"; }

                                                        // create option
                                                        echo "<option value=".$id.">".$display."</option>";
                                                    }
                                                ?>
                                            </select>
                                        </div>

                                        <div class="col-2 py-0 px-1">
                                            <input type="number" min="0.00" class="form-control w-100" id="add-expense_to_project-cost-1" name="add-expense_to_project-cost" required>
                                        </div>

                                        <div class="col-2 py-0 px-1">
                                            <input type="number" min="10" max="99" class="form-control w-100" id="add-expense_to_project-fund-1" name="add-expense_to_project-fund" value="<?php if (isset($fund_code)) { echo $fund_code; } ?>">
                                        </div>

                                        <div class="col-2 py-0 px-1">
                                            <input type="number" min="100000" max="999999" class="form-control w-100" id="add-expense_to_project-func-1" name="add-expense_to_project-func" value="<?php if (isset($function_code)) { echo $function_code; } ?>">
                                        </div>

                                        <div class="col-3 py-0 px-1">
                                            <input type="text" class="form-control w-100" id="add-expense_to_project-desc-1" name="add-expense_to_project-desc">
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-center p-0 mx-0 my-3">
                                    <button class="btn btn-secondary" onclick="addProjectExpenseRange();"><i class="fa-solid fa-plus"></i></button>
                                    <button class="btn btn-secondary ms-1" onclick="removeProjectExpenseRange();" id="add-expense_to_project-removeRangeBtn" disabled><i class="fa-solid fa-minus"></i></button>
                                </div>

                                <div class="alert alert-danger">
                                    <p class="text-center m-0"><b><i class="fa-solid fa-triangle-exclamation"></i> WARNING!</b> When using the [+] button, all current data entered will be removed!</p>
                                </div>

                                <!-- Required Field Indicator -->
                                <div class="row justify-content-center">
                                    <div class="col-11 text-center fst-italic">
                                        <span class="required-field">*</span> indicates a required field
                                    </div>
                                </div>
                            </div>

                            <div class="modal-footer">
                                <button type="button" class="btn btn-primary" onclick="addExpensesToProject();"><i class="fa-solid fa-plus"></i> Add Expenses</button>
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