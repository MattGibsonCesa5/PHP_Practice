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
            if (isset($_POST["caseloads"]) && $_POST["caseloads"] <> "") { $caseloads = json_decode($_POST["caseloads"]); } else { $caseloads = null; }

            // verify the coordinator is set and valid
            if ($coordinator_id != null && verifyUser($conn, $coordinator_id))
            {
                // get the user's display name
                $coordinator_name = getUserDisplayName($conn, $coordinator_id);

                // verify the user is not already a coordinator
                if (!verifyCoordinator($conn, $coordinator_id))
                {
                    // add the user as a coordinator
                    $addCoordinator = mysqli_prepare($conn, "INSERT INTO caseload_coordinators (user_id) VALUES (?)");
                    mysqli_stmt_bind_param($addCoordinator, "i", $coordinator_id);
                    if (mysqli_stmt_execute($addCoordinator))
                    {
                        // log coordinator add
                        echo "<span class=\"log-success\">Successfully</span> set $coordinator_name as a coordinator.<br>";
                        $message = "Successfully set $coordinator_name (user ID: $coordinator_id) as a coordinator.";
                        $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                        mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                        mysqli_stmt_execute($log);

                        // assign the coordinator to caseloads
                        // verify that caseloads were selected
                        if (is_array($caseloads) && count($caseloads) > 0)
                        {
                            // for each caseload, rollover cases
                            for ($c = 0; $c < count($caseloads); $c++)
                            {
                                // store the caseload ID locally
                                $caseload_id = $caseloads[$c];

                                // verify the caseload exists
                                if (verifyCaseload($conn, $caseload_id))
                                {
                                    // get the caseload name
                                    $caseload_name = getCaseloadDisplayName($conn, $caseload_id);

                                    // check to see if the coordinator is already assigned to this caseload
                                    if (!isCoordinatorAssigned($conn, $coordinator_id, $caseload_id))
                                    {
                                        // assign the coordinator to the caseload
                                        $addAssignment = mysqli_prepare($conn, "INSERT INTO caseload_coordinators_assignments (user_id, caseload_id) VALUES (?, ?)");
                                        mysqli_stmt_bind_param($addAssignment, "ii", $coordinator_id, $caseload_id);
                                        if (mysqli_stmt_execute($addAssignment)) { echo "<span class=\"log-success\">Successfully</span> assigned $coordinator_name to the $caseload_name caseload.<br>"; }
                                        else { echo "<span class=\"log-fail\">Failed</span> to assign $coordinator_name to the $caseload_name caseload.<br>"; }
                                    }
                                }
                            }
                        }
                    }
                    else { echo "<span class=\"log-fail\">Failed</span> to set $coordinator_name as a coordinator. An unexpected error has occurred! Please try again later.<br>"; }
                }
                else { echo "$coordinator_name is already a coordinator!<br>"; } // user is already a coordinator; do not add again
            }
            else { echo "<span class=\"log-fail\">Failed</span> to add the coordinator! You must select a valid user to be a coordinator!<br>"; }
        }
        else { echo "Your account does not have permission to perform this action!<br>"; }

        // disconnect from the database 
        mysqli_close($conn);
    }
?>