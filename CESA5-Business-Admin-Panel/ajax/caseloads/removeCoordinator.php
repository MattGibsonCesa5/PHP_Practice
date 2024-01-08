<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get additional required files
        include("../../includes/config.php");
        include("../../includes/functions.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        // verify the user has permission to manage coordinators
        if (checkUserPermission($conn, "VIEW_CASELOADS_ALL") && checkUserPermission($conn, "VIEW_THERAPISTS"))
        {
            // get the parameters from POST
            if (isset($_POST["coordinator_id"]) && $_POST["coordinator_id"] <> "") { $coordinator_id = $_POST["coordinator_id"]; } else { $coordinator_id = null; }

            // verify the coordinator is set and valid
            if ($coordinator_id != null && verifyUser($conn, $coordinator_id))
            {
                // attempt to remove the coordinator
                $removeCoordinator = mysqli_prepare($conn, "DELETE FROM caseload_coordinators WHERE user_id=?");
                mysqli_stmt_bind_param($removeCoordinator, "i", $coordinator_id);
                if (mysqli_stmt_execute($removeCoordinator))
                {
                    // get the coordinators name
                    $coordinator_name = getUserDisplayName($conn, $coordinator_id);

                    // clear all coordinator assignments
                    $clearAssignments = mysqli_prepare($conn, "DELETE FROM caseload_coordinators_assignments WHERE user_id=?");
                    mysqli_stmt_bind_param($clearAssignments, "i", $coordinator_id);
                    if (!mysqli_stmt_execute($clearAssignments)) { /* TODO - handle assignment removal error */ }

                    // log coordinator removal
                    echo "<span class=\"log-success\">Successfully</span> removed $coordinator_name as a coordinator.<br>";
                    $message = "Successfully removed $coordinator_name (user ID: $coordinator_id) as a coordinator.";
                    $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                    mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                    mysqli_stmt_execute($log);
                }
                else { echo "<span class=\"log-fail\">Failed</span> to remove the coordinator! An unexpected error has occurred! Please try again later.<br>"; }
            }
            else { echo "<span class=\"log-fail\">Failed</span> to remove the coordinator! The user you are trying to remove no longer exists!<br>"; }
        }
        else { echo "Your account does not have permission to perform this action!<br>"; }

        // disconnect from the database 
        mysqli_close($conn);
    }
?>