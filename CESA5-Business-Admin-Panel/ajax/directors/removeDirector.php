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
                // attempt to remove the director
                $removeDirector = mysqli_prepare($conn, "DELETE FROM directors WHERE user_id=?");
                mysqli_stmt_bind_param($removeDirector, "i", $director_id);
                if (mysqli_stmt_execute($removeDirector))
                {
                    // get the directors name
                    $director_name = getUserDisplayName($conn, $director_id);

                    // log director removal
                    echo "<span class=\"log-success\">Successfully</span> removed $director_name as a director.<br>";
                    $message = "Successfully removed $director_name (user ID: $director_id) as a director.";
                    $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                    mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                    mysqli_stmt_execute($log);

                    // remove the director from departments
                    $removeDirectorFromDepartments = mysqli_prepare($conn, "UPDATE departments SET director_id=NULL WHERE director_id=?");
                    mysqli_stmt_bind_param($removeDirectorFromDepartments, "i", $director_id);
                    if (!mysqli_stmt_execute($removeDirectorFromDepartments)) { echo "<span class=\"log-fail\">Failed</span> to remove $director_name as the primary director from deparmtents.<br>"; }

                    // remove the director from departments
                    $removeDirectorFromDepartments = mysqli_prepare($conn, "UPDATE departments SET secondary_director_id=NULL WHERE secondary_director_id=?");
                    mysqli_stmt_bind_param($removeDirectorFromDepartments, "i", $director_id);
                    if (!mysqli_stmt_execute($removeDirectorFromDepartments)) { echo "<span class=\"log-fail\">Failed</span> to remove $director_name as the secondary director from deparmtents.<br>"; }
                }
                else { echo "<span class=\"log-fail\">Failed</span> to remove the director! An unexpected error has occurred! Please try again later.<br>"; }
            }
            else { echo "<span class=\"log-fail\">Failed</span> to remove the director! The user you are trying to remove no longer exists!<br>"; }
        }
        else { echo "Your account does not have permission to perform this action!<br>"; }

        // disconnect from the database 
        mysqli_close($conn);
    }
?>