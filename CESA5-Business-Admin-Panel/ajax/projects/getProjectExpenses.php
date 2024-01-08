<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // initialize array to store all project expenses
        $expenses = [];

        // get the required files
        include("../../includes/config.php");
        include("../../includes/functions.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "VIEW_PROJECT_BUDGETS_ALL") || checkUserPermission($conn, "VIEW_PROJECT_BUDGETS_ASSIGNED"))
        {
            // get the parameters from POST
            if (isset($_POST["code"]) && $_POST["code"] <> "") { $code = $_POST["code"]; } else { $code = null; }
            if (isset($_POST["period"]) && $_POST["period"] <> "") { $period = $_POST["period"]; } else { $period = null; }

            if ($code != null && $period != null)
            {
                if ($period_id = getPeriodID($conn, $period)) // verify the period exists; if it exists, store the period ID
                {
                    // get the period's details
                    $periodDetails = getPeriodDetails($conn, $period_id);

                    if (verifyProject($conn, $code) && verifyUserCanViewProject($conn, $_SESSION["id"], $code)) // verify the project exists and user is assigned to it
                    {
                        $getExpenses = mysqli_prepare($conn, "SELECT * FROM project_expenses WHERE project_code=? AND period_id=?");
                        mysqli_stmt_bind_param($getExpenses, "si", $code, $period_id);
                        if (mysqli_stmt_execute($getExpenses))
                        {
                            $getExpensesResult = mysqli_stmt_get_result($getExpenses);
                            while ($expense = mysqli_fetch_array($getExpensesResult))
                            {
                                // store expense details locally
                                $project_expense_id = $expense["id"];
                                $expense_id = $expense["expense_id"];
                                $desc = $expense["description"];
                                $cost = $expense["cost"];
                                $fund = $expense["fund_code"];
                                $func = $expense["function_code"];

                                // get expense details
                                $getExpenseDetails = mysqli_prepare($conn, "SELECT * FROM expenses WHERE id=?");
                                mysqli_stmt_bind_param($getExpenseDetails, "i", $expense_id);
                                if (mysqli_stmt_execute($getExpenseDetails))
                                {
                                    $getExpenseDetailsResult = mysqli_stmt_get_result($getExpenseDetails);
                                    if (mysqli_num_rows($getExpenseDetailsResult) > 0) // expense exists
                                    {
                                        $expenseDetails = mysqli_fetch_array($getExpenseDetailsResult);
                                        $name = $expenseDetails["name"];
                                        $loc = $expenseDetails["location_code"];
                                        $obj = $expenseDetails["object_code"];
                                        $isGlobal = $expenseDetails["global"];

                                        $temp = [];
                                        if (isset($fund)) { $temp["fund"] = $fund." E"; } else { $temp["fund"] = "<span class='missing-field'>Missing</span>"; }
                                        if (isset($loc)) { $temp["loc"] = $loc; } else { $temp["loc"] = "<span class='missing-field'>Missing</span>"; }
                                        if (isset($obj)) { $temp["obj"] = $obj; } else { $temp["obj"] = "<span class='missing-field'>Missing</span>"; }
                                        if (isset($func)) { $temp["func"] = $func; } else { $temp["func"] = "<span class='missing-field'>Missing</span>"; }
                                        $temp["proj"] = $code;
                                        $temp["name"] = $name;
                                        $temp["desc"] = $desc;
                                        $temp["cost"] = printDollar($cost);

                                        // build the actions column
                                        $actions = "<div class='d-flex justify-content-end'>";
                                        if ($isGlobal == 0 && $periodDetails["editable"] == 1) 
                                        { 
                                            $actions .= "<button class='btn btn-primary btn-sm mx-1' type='button' onclick='getEditProjectExpenseModal(".$project_expense_id.");'><i class='fa-solid fa-pencil'></i></button>
                                                <button class='btn btn-primary btn-sm mx-1' type='button' onclick='getCloneProjectExpenseModal(".$project_expense_id.");'><i class='fa-solid fa-clone'></i></button>
                                                <button class='btn btn-danger btn-sm mx-1' type='button' onclick='getRemoveProjectExpenseModal(".$project_expense_id.");'><i class='fa-solid fa-trash-can'></i></button>";
                                        }
                                        else if ($isGlobal == 1 && $periodDetails["editable"] == 1)
                                        {
                                            $actions .= "<button class='btn btn-primary btn-sm mx-1' type='button' onclick='getEditProjectExpenseModal(".$project_expense_id.");'><i class='fa-solid fa-pencil'></i></button>";
                                        }
                                        $actions .= "</div>";
                                        $temp["actions"] = $actions;
                                        
                                        $expenses[] = $temp;
                                    }
                                }
                            }
                        }
                    }
                }
            }  
        }
        
        // send data to be printed
        $fullData = [];
        $fullData["draw"] = 1;
        $fullData["data"] = $expenses;
        echo json_encode($fullData);
        
        // disconnect from the database
        mysqli_close($conn);
    }
?>