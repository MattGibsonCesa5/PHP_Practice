<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get additional required files
        include("../../includes/config.php");
        include("../../includes/functions.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        // verify the user has permission to manage directors
        if (checkUserPermission($conn, "VIEW_EMPLOYEES_ALL") && checkUserPermission($conn, "EDIT_EMPLOYEES"))
        {
            // get the parameters from POST
            if (isset($_POST["director_id"]) && $_POST["director_id"] <> "") { $director_id = $_POST["director_id"]; } else { $director_id = null; }

            // verify the director is set and valid
            if ($director_id != null && verifyUser($conn, $director_id))
            {
                // get the user's display name
                $director_name = getUserDisplayName($conn, $director_id);

                // verify the user is not already a director
                if (!verifyDirector($conn, $director_id))
                {
                    // add the user as a director
                    $addDirector = mysqli_prepare($conn, "INSERT INTO directors (user_id) VALUES (?)");
                    mysqli_stmt_bind_param($addDirector, "i", $director_id);
                    if (mysqli_stmt_execute($addDirector))
                    {
                        // log director add
                        echo "<span class=\"log-success\">Successfully</span> set $director_name as a director.<br>";
                        $message = "Successfully set $director_name (user ID: $director_id) as a director.";
                        $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                        mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                        mysqli_stmt_execute($log);
                    }
                    else { echo "<span class=\"log-fail\">Failed</span> to set $director_name as a director. An unexpected error has occurred! Please try again later.<br>"; }
                }
                else { echo "$director_name is already a director!<br>"; } // user is already a director; do not add again
            }
            else { echo "<span class=\"log-fail\">Failed</span> to add the director! You must select a valid user to be a director!<br>"; }
        }
        else { echo "Your account does not have permission to perform this action!<br>"; }

        // disconnect from the database 
        mysqli_close($conn);
    }
?>