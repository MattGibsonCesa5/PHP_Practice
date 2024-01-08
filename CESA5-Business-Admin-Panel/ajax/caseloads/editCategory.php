<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get additional required files
        include("../../includes/functions.php");
        include("../../includes/config.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        // verify the user has permission
        if (checkUserPermission($conn, "VIEW_CASELOADS_ALL"))
        {
            // get the parameters from POST
            if (isset($_POST["category_id"]) && $_POST["category_id"] <> "") { $category_id = $_POST["category_id"]; } else { $category_id = null; }
            if (isset($_POST["name"]) && trim($_POST["name"]) <> "") { $name = trim($_POST["name"]); } else { $name = null; }
            if (isset($_POST["locked"]) && is_numeric($_POST["locked"]) && $_POST["locked"] == 1) { $locked = 1; } else { $locked = 0; }

            // verify category exists
            if (verifyCaseloadCategory($conn, $category_id))
            {
                // verify the name is set and valid
                if ($name != null && trim($name) <> "")
                {
                    // update the category
                    $editCategory = mysqli_prepare($conn, "UPDATE caseload_categories SET name=?, locked=? WHERE id=?");
                    mysqli_stmt_bind_param($editCategory, "sii", $name, $locked, $category_id);
                    if (mysqli_stmt_execute($editCategory)) 
                    { 
                        // log coordinator edit
                        echo "<span class=\"log-success\">Successfully</span> edited the category.<br>";
                        $message = "Successfully edited the category with ID $category_id. Set the name to $name. Set locked status to $locked.";
                        $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                        mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                        mysqli_stmt_execute($log);
                    } 
                    else { echo "<span class=\"log-fail\">Failed</span> to edit the category. An unexpected error has occurred! Please try again later.<br>"; }
                }
                else { echo "<span class=\"log-fail\">Failed</span> to edit the category. You must provide a name for the category.<br>"; }
            }
            else { echo "<span class=\"log-fail\">Failed</span> to edit the category. The category you are trying to edit does not exist!<br>"; }
        }
        else { echo "Your account does not have permission to perform this action.<br>"; }

        // disconnect from the database
        mysqli_close($conn);
    }
?>