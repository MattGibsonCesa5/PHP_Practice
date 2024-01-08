<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get additional required files
        include("../../includes/config.php");
        include("../../includes/functions.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        // verify the user has permission to manage therapists
        if (checkUserPermission($conn, "VIEW_EMPLOYEES_ALL") && checkUserPermission($conn, "EDIT_EMPLOYEES"))
        {
            // get the parameters from POST
            if (isset($_POST["therapist_id"]) && $_POST["therapist_id"] <> "") { $therapist_id = $_POST["therapist_id"]; } else { $therapist_id = null; }

            // verify the therapist is set and valid
            if ($therapist_id != null && verifyUser($conn, $therapist_id))
            {
                // get the user's display name
                $therapist_name = getUserDisplayName($conn, $therapist_id);

                // verify the user is not already a therapist
                if (!verifyTherapist($conn, $therapist_id))
                {
                    // add the user as a therapist
                    $addTherapist = mysqli_prepare($conn, "INSERT INTO therapists (user_id) VALUES (?)");
                    mysqli_stmt_bind_param($addTherapist, "i", $therapist_id);
                    if (mysqli_stmt_execute($addTherapist))
                    {
                        // log therapist add
                        echo "<span class=\"log-success\">Successfully</span> set $therapist_name as a therapist.<br>";
                        $message = "Successfully set $therapist_name (user ID: $therapist_id) as a therapist.";
                        $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                        mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                        mysqli_stmt_execute($log);
                    }
                    else { echo "<span class=\"log-fail\">Failed</span> to set $therapist_name as a therapist. An unexpected error has occurred! Please try again later.<br>"; }
                }
                else { echo "$therapist_name is already a therapist!<br>"; } // user is already a therapist; do not add again
            }
            else { echo "<span class=\"log-fail\">Failed</span> to add the therapist! You must select a valid user to be a therapist!<br>"; }
        }
        else { echo "Your account does not have permission to perform this action!<br>"; }

        // disconnect from the database 
        mysqli_close($conn);
    }
?>