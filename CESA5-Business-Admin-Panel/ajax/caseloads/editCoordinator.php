<?php
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get additional required files
        include("../../includes/config.php");
        include("../../includes/functions.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "VIEW_CASELOADS_ALL") && checkUserPermission($conn, "VIEW_THERAPISTS"))
        {
            // get the parameters from POST
            if (isset($_POST["coordinator_id"]) && $_POST["coordinator_id"] <> "") { $coordinator_id = $_POST["coordinator_id"]; } else { $coordinator_id = null; }
            if (isset($_POST["caseloads"]) && $_POST["caseloads"] <> "") { $caseloads = json_decode($_POST["caseloads"]); } else { $caseloads = null; }

            // verify the coordinator
            if (verifyCoordinator($conn, $coordinator_id))
            {
                // get the coordinators name
                $coordinator_name = getUserDisplayName($conn, $coordinator_id);

                // clear all coordinator assignments
                $clearAssignments = mysqli_prepare($conn, "DELETE FROM caseload_coordinators_assignments WHERE user_id=?");
                mysqli_stmt_bind_param($clearAssignments, "i", $coordinator_id);
                if (mysqli_stmt_execute($clearAssignments)) 
                { 
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

                    // log coordinator edit
                    echo "<span class=\"log-success\">Successfully</span> edited the coordinator $coordinator_name.<br>";
                    $message = "Successfully edited the coordinator $coordinator_name (ID: $coordinator_id).";
                    $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                    mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                    mysqli_stmt_execute($log);
                }
                else { echo "<span class=\"log-fail\">Failed</span> to edit the coordinator. An unexpected error has occurred! Please try again later.<br>"; }
            }
            else { echo "<span class=\"log-fail\">Failed</span> to edit the coordinator. The coordinator you are trying to edit does not exist!<br>"; }
        }
        else { echo "Your account does not have permission to perform this action!<br>"; }

        // disconnect from the database
        mysqli_close($conn);
    }
?>