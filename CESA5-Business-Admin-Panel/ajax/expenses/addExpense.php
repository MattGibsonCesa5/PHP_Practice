<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get additional required files
        include("../../includes/config.php");
        include("../../includes/functions.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "ADD_PROJECT_EXPENSES"))
        {
            // get parameters from POST
            if (isset($_POST["name"]) && $_POST["name"] <> "") { $name = $_POST["name"]; } else { $name = null; }
            if (isset($_POST["desc"]) && $_POST["desc"] <> "") { $desc = $_POST["desc"]; } else { $desc = null; }
            if (isset($_POST["loc"]) && $_POST["loc"] <> "") { $loc = $_POST["loc"]; } else { $loc = null; }
            if (isset($_POST["obj"]) && $_POST["obj"] <> "") { $obj = $_POST["obj"]; } else { $obj = null; }
            if (isset($_POST["status"]) && $_POST["status"] <> "") { $status = $_POST["status"]; } else { $status = null; }

            // validate status :: if status is anything but 1 (active); set to 0 (inactive)
            if (is_numeric($status) && $status != 1) { $status = 0; }
            else if (!is_numeric($status)) { $status = 0; }

            if ($name != null && $loc != null && $obj != null)
            {
                $addExpense = mysqli_prepare($conn, "INSERT INTO expenses (name, description, location_code, object_code, status) VALUES (?, ?, ?, ?, ?)");
                mysqli_stmt_bind_param($addExpense, "ssssi", $name, $desc, $loc, $obj, $status);
                if (mysqli_stmt_execute($addExpense)) 
                { 
                    echo "<span class=\"log-success\">Successfully</span> created the new expense.<br>"; 

                    // get the ID for the newly created expense
                    $expense_id = mysqli_insert_id($conn);

                    // log expense creation
                    $message = "Successfully added a new expense with the name $name. The ID assigned to the expense is $expense_id. ";
                    $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                    mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                    mysqli_stmt_execute($log);
                }
                else { echo "<span class=\"log-fail\">Failed</span> to create the new expense.<br>"; }
            }
            else { echo "<span class=\"log-fail\">Failed</span> to create the new expense. You must provide data for all of the required fields.<br>"; } 
        }
        else { echo "<span class=\"log-fail\">Failed</span> to add the new expense. Your account does not have permission to add new expenses!<br>"; }
        
        // disconnect from the database
        mysqli_close($conn);
    }
?>