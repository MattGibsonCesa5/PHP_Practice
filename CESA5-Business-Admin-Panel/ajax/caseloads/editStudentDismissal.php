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
            if (isset($_POST["eval_month"]) && is_numeric($_POST["eval_month"])) { $eval_month = $_POST["eval_month"]; } else { $eval_month = 0; }
            if (isset($_POST["medicaid_billing_completed"]) && $_POST["medicaid_billing_completed"] <> "") { $medicaid_billing_completed = $_POST["medicaid_billing_completed"]; } else { $medicaid_billing_completed = null; }

            // validate parameters
            if ($dismissal_iep != 1) { $dismissal_iep = 0; }

            if ($dismissal_date != null)
            {
                // convert the date format to database format
                $DB_dismissal_date = date("Y-m-d", strtotime($dismissal_date));

                if (is_numeric($medicaid_billing_completed) && ($medicaid_billing_completed == 0 || $medicaid_billing_completed == 1))
                {
                    if ($case_id != null && verifyCase($conn, $case_id))
                    {
                        if ($reason_id != null && verifyDismissalReasoning($conn, $reason_id))
                        {
                            // get the caseload's current data
                            $getCaseload = mysqli_prepare($conn, "SELECT id, caseload_id, student_id FROM cases WHERE id=?");
                            mysqli_stmt_bind_param($getCaseload, "i", $case_id);
                            if (mysqli_stmt_execute($getCaseload))
                            {
                                $getCaseloadResult = mysqli_stmt_get_result($getCaseload);
                                if (mysqli_num_rows($getCaseloadResult) > 0)
                                {
                                    // store caseload details locally
                                    $caseload = mysqli_fetch_array($getCaseloadResult);
                                    $case_id = $caseload["id"];
                                    $caseload_id = $caseload["caseload_id"];
                                    $student_id = $caseload["student_id"];

                                    // get caseload settings
                                    $medicaid = isCaseloadMedicaid($conn, $caseload_id);

                                    // validate evaluation month
                                    if ($medicaid === true)
                                    {
                                        // verify month is valid
                                        if ($eval_month < 0 || $eval_month > 12) { $eval_month = null; }
                                    }
                                    else { $eval_month = 0; } // set to N/A (0) if not Medicaid required

                                    // get student display name based on ID
                                    $student_name = getStudentDisplayName($conn, $student_id);

                                    // update case change
                                    $editChange = mysqli_prepare($conn, "UPDATE case_changes SET start_date=?, changed_by=? WHERE case_id=? AND is_dismissal=1");
                                    mysqli_stmt_bind_param($editChange, "sii", $DB_dismissal_date, $_SESSION["id"], $case_id);
                                    if (mysqli_stmt_execute($editChange))
                                    {
                                        // dismiss the student
                                        $dismissStudent = mysqli_prepare($conn, "UPDATE cases SET end_date=?, dismissal_iep=?, dismissal_reasoning_id=?, medicaid_evaluation_month=?, medicaid_billing_done=?, active=0 WHERE id=?");
                                        mysqli_stmt_bind_param($dismissStudent, "siiiii", $DB_dismissal_date, $dismissal_iep, $reason_id, $eval_month, $medicaid_billing_completed, $case_id);
                                        if (mysqli_stmt_execute($dismissStudent)) 
                                        { 
                                            echo "<span class=\"log-success\">Successfully</span> edited the student dismissal for $student_name.<br>"; 

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

                                            // log case dismissal edit
                                            $message = "Successfully edited the dismissal for the case with the ID of $case_id with a dismissal date of $DB_dismissal_date.";
                                            if ($deletedChanges > 0) { $message .= " Successfully deleted $deletedChanges planned case changes that had a change date after the dismissal date."; }
                                            $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                                            mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                                            mysqli_stmt_execute($log);
                                        }
                                        else { echo "<span class=\"log-fail\">Failed</span> to edit the student dismissal. An unexpected error has occurred! Please try again later.<br>"; }
                                    }
                                    else { echo "<span class=\"log-fail\">Failed</span> to edit the student dismissal. An unexpected error has occurred! Please try again later.<br>"; }
                                }
                                else { echo "<span class=\"log-fail\">Failed</span> to edit the student dismissal. The student was not found within the caseload.<br>"; }
                            }
                            else { echo "<span class=\"log-fail\">Failed</span> to edit the student dismissal. An unexpected error has occurred! Please try again later.<br>"; }
                        }
                        else { echo "<span class=\"log-fail\">Failed</span> to edit the student dismissal. The dismissal reasoning was invalid!<br>"; }
                    }
                    else { echo "<span class=\"log-fail\">Failed</span> to edit the student dismissal. An unexpected error has occurred! Please try again later.<br>"; }
                }
                else { echo "<span class=\"log-fail\">Failed</span> to edit the student dismissal. You must select a valid selection for \"Medicaid Billing Completed?\"<br>"; }
            }
            else { echo "<span class=\"log-fail\">Failed</span> to edit the student dismissal. You must select a dismissal date!<br>"; }
        }

        // disconnect from the database
        mysqli_close($conn);
    }
?>