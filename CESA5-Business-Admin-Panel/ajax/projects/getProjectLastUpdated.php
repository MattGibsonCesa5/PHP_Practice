<?php
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get the required files
        include("../../includes/config.php");
        include("../../includes/functions.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "VIEW_PROJECT_BUDGETS_ALL") || checkUserPermission($conn, "VIEW_PROJECT_BUDGETS_ASSIGNED"))
        {
            // get parameters from POST
            if (isset($_POST["code"]) && $_POST["code"] <> "") { $code = $_POST["code"]; } else { $code = null; }

            if ($code != null)
            {
                // get the last updated time for the project
                $getTime = mysqli_prepare($conn, "SELECT updated FROM projects WHERE code=?");
                mysqli_stmt_bind_param($getTime, "s", $code);
                if (mysqli_stmt_execute($getTime))
                {
                    $getTimeResult = mysqli_stmt_get_result($getTime);
                    if (mysqli_num_rows($getTimeResult) > 0) // project exists; format and return last updated time
                    {
                        // set timezone
                        date_default_timezone_set("America/Chicago");

                        // get last updated timestamp from the database
                        $updated_timestamp = mysqli_fetch_array($getTimeResult)["updated"];
                        $formatted_updated_timestamp = date("n/j/Y g:i:s A", strtotime($updated_timestamp));
                        echo $formatted_updated_timestamp;
                    }
                    else { echo "Time not found."; }
                }
                else { echo "Time not found."; }
            }
            else { echo "Time not found."; }
        }
        else { echo "Time not found."; }
        
        // disconnect from the database
        mysqli_close($conn);
    }
    else { echo "Time not found."; }
?>