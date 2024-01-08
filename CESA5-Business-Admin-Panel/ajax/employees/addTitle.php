<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get additional required files
        include("../../includes/functions.php");
        include("../../includes/config.php");
        include("../../getSettings.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "VIEW_EMPLOYEES_ALL") && checkUserPermission($conn, "ADD_EMPLOYEES"))
        {
            // get form fields from POST
            if (isset($_POST["title"]) && trim($_POST["title"]) <> "") { $title = trim($_POST["title"]); } else { $title = null; }

            // verify title is set
            if ($title != null)
            {
                // check if there is already a title with that name
                $checkTitle = mysqli_prepare($conn, "SELECT id FROM employee_titles WHERE name=?");
                mysqli_stmt_bind_param($checkTitle, "s", $title);
                if (mysqli_stmt_execute($checkTitle))
                {
                    $checkTitleResult = mysqli_stmt_get_result($checkTitle);
                    if (mysqli_num_rows($checkTitleResult) == 0) // title does not already exist; continue
                    {
                        // attempt to add the title
                        $addTitle = mysqli_prepare($conn, "INSERT INTO employee_titles (name) VALUES (?)");
                        mysqli_stmt_bind_param($addTitle, "s", $title);
                        if (mysqli_stmt_execute($addTitle)) 
                        { 
                            // log to screen status
                            echo "<span class=\"log-success\">Successfully</span> added the new title!<br>"; 

                            // log adding new title
                            $message = "Successfully added a new position title labeled $title.";
                            $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                            mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                            mysqli_stmt_execute($log);
                        }
                        else { echo "Faield to add the title. An unexpected error has occurred! Please try again later."; }
                    }
                    else { echo "<span class=\"log-fail\">Failed</span> to add the new title. A title is already created with that name!<br>"; }
                }
                else { echo "Faield to add the title. An unexpected error has occurred! Please try again later."; }
            }
            else { echo "<span class=\"log-fail\">Failed</span> to add the title. The title cannot be blank!<br>"; }
        }
        else { echo "<span class=\"log-fail\">Failed</span> to add the title. Your account does not have permission to add titles!<br>"; }
        
        // disconnect from the database
        mysqli_close($conn);
    }
?>