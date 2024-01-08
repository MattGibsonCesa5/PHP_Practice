<?php
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get additional required files
        include("../../includes/functions.php");
        include("../../includes/config.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "VIEW_CASELOADS_ALL"))
        {
            // get and verify the case ID from POST
            if (isset($_POST["case_id"]) && $_POST["case_id"] <> "") { $case_id = $_POST["case_id"]; } else { $case_id = null; }
            if ($case_id != null && verifyCase($conn, $case_id))
            {
                // get the case's current data
                $getCase = mysqli_prepare($conn, "SELECT c.student_id, p.caseload_term_end, p.editable FROM cases c 
                                                JOIN periods p ON c.period_id=p.id
                                                WHERE c.id=?");
                mysqli_stmt_bind_param($getCase, "i", $case_id);
                if (mysqli_stmt_execute($getCase))
                {
                    $getCaseResult = mysqli_stmt_get_result($getCase);
                    if (mysqli_num_rows($getCaseResult) > 0)
                    {
                        // store case details locally
                        $case = mysqli_fetch_array($getCaseResult);
                        $student_id = $case["student_id"];
                        $term_end = $case["caseload_term_end"];
                        $is_editable = $case["editable"];

                        // get student display name based on ID
                        $student_name = getStudentDisplayName($conn, $student_id);

                        // verify the period is editalbe
                        if ($is_editable == 1)
                        {
                            // delete the change in the case
                            $deleteChange = mysqli_prepare($conn, "DELETE FROM case_changes WHERE case_id=? AND is_dismissal=1");
                            mysqli_stmt_bind_param($deleteChange, "i", $case_id);
                            if (mysqli_stmt_execute($deleteChange))
                            {
                                // update case details
                                $undismissStudent = mysqli_prepare($conn, "UPDATE cases SET end_date=?, dismissal_iep=0, dismissed=0, dismissal_reasoning_id=null, active=1 WHERE id=?");
                                mysqli_stmt_bind_param($undismissStudent, "si", $term_end, $case_id);
                                if (mysqli_stmt_execute($undismissStudent))
                                {
                                    // log case dismissal
                                    echo "<span class=\"log-success\">Successfully</span> undismissed $student_name from the caseload.<br>"; 
                                    $message = "Successfully undismissed the case with the ID of $case_id.";
                                    $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                                    mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                                    mysqli_stmt_execute($log);
                                }
                                else { echo "<span class=\"log-fail\">Failed</span> to undismiss $student_name from the caseload. An unexpected error has occurred! Please try again later.<br>"; }
                            }
                            else { echo "<span class=\"log-fail\">Failed</span> to undismiss $student_name from the caseload. An unexpected error has occurred! Please try again later.<br>"; }
                        }
                        else { echo "<span class=\"log-fail\">Failed</span> to undismiss $student_name from the caseload. The period/term is no longer editable!<br>"; }
                    }
                    else { echo "<span class=\"log-fail\">Failed</span> to undismiss $student_name from the caseload. An unexpected error has occurred! Please try again later.<br>"; }
                }
                else { echo "<span class=\"log-fail\">Failed</span> to undismiss $student_name from the caseload. An unexpected error has occurred! Please try again later.<br>"; }
            }
            else { echo "<span class=\"log-fail\">Failed</span> to undismiss $student_name from the caseload. An unexpected error has occurred! Please try again later.<br>"; }
        }
        else { echo "<span class=\"log-fail\">Failed</span> to perform the task. You do not have permission to perform this task.<br>"; }
    }
?>