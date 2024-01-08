<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // initialize an array to store expenses
        $expenses = [];

        // get required additional files
        include("../../includes/config.php");
        include("../../includes/functions.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "VIEW_PROJECT_EXPENSES"))
        {
            // get the period from POST
            if (isset($_POST["period"]) && $_POST["period"] <> "") { $period = $_POST["period"]; } else { $period = null; }

            if ($period != null)
            {
                if ($period_id = getPeriodID($conn, $period)) // verify the period exists; if it exists, store the period ID
                {
                    // get a list of all expenses
                    $getExpenses = mysqli_prepare($conn, "SELECT * FROM expenses WHERE global=0");
                    if (mysqli_stmt_execute($getExpenses))
                    {
                        $getExpensesResults = mysqli_stmt_get_result($getExpenses);
                        while ($expense = mysqli_fetch_array($getExpensesResults)) 
                        { 
                            $temp = [];

                            $temp["id"] = $expense["id"];
                            $temp["name"] = $expense["name"];
                            $temp["description"] = $expense["description"];
                            $temp["location_code"] = $expense["location_code"];
                            $temp["object_code"] = $expense["object_code"];

                            // get the total amount of time this expense has been added to a project in the active period
                            $qty = 0; // assume expense has not been added to any projects
                            $getQty = mysqli_prepare($conn, "SELECT COUNT(id) AS qty_count FROM project_expenses WHERE expense_id=? AND period_id=?");
                            mysqli_stmt_bind_param($getQty, "ii", $expense["id"], $period_id);
                            if (mysqli_stmt_execute($getQty))
                            {
                                $getQtyResult = mysqli_stmt_get_result($getQty);
                                if (mysqli_num_rows($getQtyResult) > 0)
                                {
                                    $qty = mysqli_fetch_array($getQtyResult)["qty_count"];
                                }
                            }
                            if (isset($qty) && is_numeric($qty)) { $temp["total_qty"] = $qty; }
                            else { $temp["total_qty"] = 0; }

                            // get the total costs this expense has
                            $costs = 0; // assume expense has total cost of 0
                            $getCosts = mysqli_prepare($conn, "SELECT SUM(cost) AS total_costs FROM project_expenses WHERE expense_id=? AND period_id=?");
                            mysqli_stmt_bind_param($getCosts, "ii", $expense["id"], $period_id);
                            if (mysqli_stmt_execute($getCosts))
                            {
                                $getCostsResult = mysqli_stmt_get_result($getCosts);
                                if (mysqli_num_rows($getCostsResult) > 0)
                                {
                                    $costs = mysqli_fetch_array($getCostsResult)["total_costs"];
                                }
                            }
                            $temp["costs_calc"] = $costs;
                            // format the total costs
                            if (isset($costs) && is_numeric($costs)) { $temp["total_costs"] = printDollar($costs); }
                            else { $temp["total_costs"] = "$0.00"; }

                            // build the actions column
                            $actions = "<div class='d-flex justify-content-end'>
                                <button class='btn btn-primary btn-sm mx-1' type='button' onclick='getViewExpenseModal(".$expense["id"].");'><i class='fa-solid fa-eye'></i></button>";
                                if (checkUserPermission($conn, "EDIT_PROJECT_EXPENSES")) { $actions .= "<button class='btn btn-primary btn-sm mx-1' type='button' onclick='getEditExpenseModal(".$expense["id"].");'><i class='fa-solid fa-pencil'></i></button>"; }
                                if (checkUserPermission($conn, "DELETE_PROJECT_EXPENSES")) { $actions .= "<button class='btn btn-danger btn-sm mx-1' type='button' onclick='getDeleteExpenseModal(".$expense["id"].");'><i class='fa-solid fa-trash-can'></i></button>"; }
                            $actions .= "</div>";

                            $temp["actions"] = $actions;

                            $expenses[] = $temp; 
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