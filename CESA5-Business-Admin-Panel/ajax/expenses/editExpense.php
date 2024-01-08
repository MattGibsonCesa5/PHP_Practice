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
            if (isset($_POST["name"]) && $_POST["name"] <> "") { $name = $_POST["name"]; } else { $name = null; }
            if (isset($_POST["desc"]) && $_POST["desc"] <> "") { $desc = $_POST["desc"]; } else { $desc = null; }
            if (isset($_POST["loc"]) && $_POST["loc"] <> "") { $loc = $_POST["loc"]; } else { $loc = null; }
            if (isset($_POST["obj"]) && $_POST["obj"] <> "") { $obj = $_POST["obj"]; } else { $obj = null; }
            if (isset($_POST["status"]) && $_POST["status"] <> "") { $status = $_POST["status"]; } else { $status = null; }

            // validate status :: if status is anything but 1 (active); set to 0 (inactive)
            if (is_numeric($status) && $status != 1) { $status = 0; }
            else if (!is_numeric($status)) { $status = 0; }

            if ($name != null && $expense_id != null && $loc != null && $obj != null)
            {
                // verify the expense exists
                $checkExpense = mysqli_prepare($conn, "SELECT id FROM expenses WHERE id=?");
                mysqli_stmt_bind_param($checkExpense, "i", $expense_id);
                if (mysqli_stmt_execute($checkExpense))
                {
                    $checkExpenseResult = mysqli_stmt_get_result($checkExpense);
                    if (mysqli_num_rows($checkExpenseResult) > 0) // expense exists; continue edit
                    {
                        $editExpense = mysqli_prepare($conn, "UPDATE expenses SET name=?, description=?, location_code=?, object_code=?, status=? WHERE id=?");
                        mysqli_stmt_bind_param($editExpense, "ssssii", $name, $desc, $loc, $obj, $status, $expense_id);
                        if (mysqli_stmt_execute($editExpense)) 
                        { 
                            echo "<span class=\"log-success\">Successfully</span> edited the expense.<br>"; 

                            // log expense edit
                            $message = "Successfully edited the expense with the ID $expense_id. ";
                            $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                            mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                            mysqli_stmt_execute($log);
                        }
                        else { echo "<span class=\"log-fail\">Failed</span> to edit the expense.<br>"; }
                    }
                    else { echo "<span class=\"log-fail\">Failed</span> to edit the expense. The expense selected does not exist!<br>"; }
                }
                else { echo "<span class=\"log-fail\">Failed</span> to edit the expense. An unknown error has occurred. Please try again later.<br>"; }
            }
            else { echo "<span class=\"log-fail\">Failed</span> to edit the expense. You must provide data for all of the required fields.<br>"; }
        }
        else { echo "<span class=\"log-fail\">Failed</span> to edit the expenses. Your account does not have permission to edit expenses!<br>"; }
        
        // disconnect from the database
        mysqli_close($conn);
    }
?>