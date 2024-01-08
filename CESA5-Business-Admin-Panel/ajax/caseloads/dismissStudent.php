<?php
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get additional required files
        include("../../includes/functions.php");
        include("../../includes/config.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "VIEW_CASELOADS_ALL") || checkUserPermission($conn, "VIEW_CASELOADS_ASSIGNED"))
        {
            // get the caseload ID from POST
            if (isset($_POST["case_id"]) && $_POST["case_id"] <> "") { $case_id = $_POST["case_id"]; } else { $case_id = null; }
            if (isset($_POST["dismissal_date"]) && $_POST["dismissal_date"] <> "") { $dismissal_date = $_POST["dismissal_date"]; } else { $dismissal_date = null; }
            if (isset($_POST["dismissal_iep"]) && $_POST["dismissal_iep"] <> "") { $dismissal_iep = $_POST["dismissal_iep"]; } else { $dismissal_iep = 0; }
            if (isset($_POST["reason_id"]) && $_POST["reason_id"] <> "") { $reason_id = $_POST["reason_id"]; } else { $reason_id = 0; }
            if (isset($_POST["medicaid_billing"]) && $_POST["medicaid_billing"] <> "") { $medicaid_billing = $_POST["medicaid_billing"]; } else { $medicaid_billing = null; }

            // validate parameters
            if ($dismissal_iep != 1) { $dismissal_iep = 0; }

            if ($dismissal_date != null)
            {
                // convert the date format to database format
                $DB_dismissal_date = date("Y-m-d", strtotime($dismissal_date));

                if ($case_id != null && verifyCase($conn, $case_id))
                {
                    if ($reason_id != null && verifyDismissalReasoning($conn, $reason_id))
                    {
                        if (is_numeric($medicaid_billing) && ($medicaid_billing == 0 || $medicaid_billing == 1))
                        {
                            // get the case's current data
                            $getCase = mysqli_prepare($conn, "SELECT id, caseload_id, student_id, start_date FROM cases WHERE id=?");
                            mysqli_stmt_bind_param($getCase, "i", $case_id);
                            if (mysqli_stmt_execute($getCase))
                            {
                                $getCaseResult = mysqli_stmt_get_result($getCase);
                                if (mysqli_num_rows($getCaseResult) > 0)
                                {
                                    // store case details locally
                                    $case = mysqli_fetch_array($getCaseResult);
                                    $case_id = $case["id"];
                                    $caseload_id = $case["caseload_id"];
                                    $student_id = $case["student_id"];
                                    $start_date = $case["start_date"];

                                    // verify dismissal data is after or on the start date
                                    if (strtotime($start_date) <= strtotime($DB_dismissal_date))
                                    {
                                        // get student display name based on ID
                                        $student_name = getStudentDisplayName($conn, $student_id);

                                        // add case change, setting frequency to 0
                                        $addChange = mysqli_prepare($conn, "INSERT INTO case_changes (case_id, start_date, frequency, uos, is_dismissal, changed_by) VALUES (?, ?, 'DISMISSED', 0, 1, ?)");
                                        mysqli_stmt_bind_param($addChange, "isi", $case_id, $DB_dismissal_date, $_SESSION["id"]);
                                        if (mysqli_stmt_execute($addChange))
                                        {
                                            // dismiss the student
                                            $dismissStudent = mysqli_prepare($conn, "UPDATE cases SET end_date=?, dismissal_iep=?, dismissed=1, dismissal_reasoning_id=?, medicaid_billing_done=?, active=0 WHERE id=?");
                                            mysqli_stmt_bind_param($dismissStudent, "siiii", $DB_dismissal_date, $dismissal_iep, $reason_id, $medicaid_billing, $case_id);
                                            if (mysqli_stmt_execute($dismissStudent)) 
                                            { 
                                                echo "<span class=\"log-success\">Successfully</span> dismissed $student_name from the caseload.<br>"; 

                                                // delete future/planned case changes for this case
                                                $deletedChanges = 0;
                                                $deletePlannedChanges = mysqli_prepare($conn, "DELETE FROM case_changes WHERE start_date>? AND case_id=?");
                                                mysqli_stmt_bind_param($deletePlannedChanges, "si", $DB_dismissal_date, $case_id);
                                                if (mysqli_stmt_execute($deletePlannedChanges))
                                                {
                                                    $deletedChanges = mysqli_affected_rows($conn);
                                                    if ($deletedChanges > 0)
                                                    {
                                                        // log future change deletions
                                                        echo "<span class=\"log-success\">Successfully</span> deleted $deletedChanges planned case changes that had a change date after the dismissal date.<br>";
                                                    }
                                                }

                                                // log case dismissal
                                                $message = "Successfully dismissed the case with the ID of $case_id with a dismissal date of $DB_dismissal_date.";
                                                if ($deletedChanges > 0) { $message .= " Successfully deleted $deletedChanges planned case changes that had a change date after the dismissal date."; }
                                                $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                                                mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                                                mysqli_stmt_execute($log);
                                            }
                                            else { echo "<span class=\"log-fail\">Failed</span> to dismiss the student from the caseload. An unexpected error has occurred! Please try again later.<br>"; }
                                        }
                                        else { echo "<span class=\"log-fail\">Failed</span> to dismiss the student from the caseload. An unexpected error has occurred! Please try again later.<br>"; }
                                    }
                                    else { echo "<span class=\"log-fail\">Failed</span> to dismiss the student from the caseload. The dismissal date cannot be before the start date!<br>"; }
                                }
                                else { echo "<span class=\"log-fail\">Failed</span> to dismiss the student from the caseload. The student was not found within the caseload.<br>"; }
                            }
                            else { echo "<span class=\"log-fail\">Failed</span> to dismiss the student from the caseload. An unexpected error has occurred! Please try again later.<br>"; }
                        }
                        else { echo "<span class=\"log-fail\">Failed</span> to dismiss the student from the caseload. You must select a valid selection for \"Medicaid Billing Completed?\"<br>"; }
                    }
                    else { echo "<span class=\"log-fail\">Failed</span> to dismiss the student from the caseload. You must provide a valid reasoning for dismissal.<br>"; }
                }
                else { echo "<span class=\"log-fail\">Failed</span> to dismiss the student from the caseload. The student was not found within the caseload.<br>"; }
            }
            else { echo "<span class=\"log-fail\">Failed</span> to dismiss the student from the caseload. You must select a dismissal date!<br>"; }
        }

        // disconnect from the database
        mysqli_close($conn);
    }
?>