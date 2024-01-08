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
            if (isset($_POST["title_id"]) && is_numeric($_POST["title_id"])) { $title_id = $_POST["title_id"]; } else { $title_id = null; }

            // verify title exists
            if (verifyTitle($conn, $title_id))
            {
                // get the title name
                $title = getTitleName($conn, $title_id);

                // attempt to delete the title
                $deleteTitle = mysqli_prepare($conn, "DELETE FROM employee_titles WHERE id=?");
                mysqli_stmt_bind_param($deleteTitle, "i", $title_id);
                if (mysqli_stmt_execute($deleteTitle)) // successfully deleted the title
                {
                    // log to screen title deletion
                    echo "<span class=\"log-success\">Successfully</span> deleted the title.<br>Attempting to remove this title from employees who were assigned it...<br>";

                    // after successful title deletion, set all employee's titles to null for those who were assigned that title
                    $updateTitles = mysqli_prepare($conn, "UPDATE employee_compensation SET title_id=null WHERE title_id=?");
                    mysqli_stmt_bind_param($updateTitles, "i", $title_id);
                    if (mysqli_stmt_execute($updateTitles)) { echo "<span class=\"log-success\">Successfully</span> removed this title from employees who were assigned it.<br>"; }
                    else { echo "<span class=\"log-fail\">Failed</span> to remove this title from employees who were assigned it.<br>"; }

                    // log title deletion
                    $message = "Successfully deleted the position title with ID $title_id, labeled $title.";
                    $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                    mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                    mysqli_stmt_execute($log);
                }
                else { echo "<span class=\"log-fail\">Failed</span> to delete the title. An unexpected error has occurred! Please try again later.<br>"; }
            }
            else { echo "<span class=\"log-fail\">Failed</span> to delete the title. The title you are trying to delete no longer exists!<br>"; }
        }
        else { echo "<span class=\"log-fail\">Failed</span> to delete the title. Your account does not have permission to delete titles!<br>"; }
        
        // disconnect from the database
        mysqli_close($conn);
    }
?>