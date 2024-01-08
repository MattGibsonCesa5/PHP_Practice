<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get additional required files
        include("../../includes/functions.php");
        include("../../includes/config.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "EDIT_CASELOADS"))
        {
            // get parameters from POST
            if (isset($_POST["case_id"]) && $_POST["case_id"] <> "") { $case_id = $_POST["case_id"]; } else { $case_id = null; }
            if (isset($_POST["date"]) && $_POST["date"] <> "") { $date = date("Y-m-d", strtotime($_POST["date"])); } else { $date = null; }
            if (isset($_POST["frequency"]) && $_POST["frequency"] <> "") { $frequency = $_POST["frequency"]; } else { $frequency = null; }
            if (isset($_POST["units"]) && is_numeric($_POST["units"])) { $units = $_POST["units"]; } else { $units = 0; }
            if (isset($_POST["iep_meeting"]) && is_numeric($_POST["iep_meeting"])) { $iep_meeting = $_POST["iep_meeting"]; } else { $iep_meeting = 0; }

            // validate parameters
            if ($iep_meeting != 1) { $iep_meeting = 0; } 
            if ($units < 0) { $units = 0; } // do not allow negative units

            // verify the caseload exists
            if (verifyCase($conn, $case_id))
            {
                // get the case's current data
                $getCase = mysqli_prepare($conn, "SELECT id, caseload_id, student_id, start_date, end_date FROM cases WHERE id=?");
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
                        $end_date = $case["end_date"];

                        // verify change data is after or on the start date
                        if (strtotime($start_date) <= strtotime($date))
                        {
                            // verify change data is after or on the start date
                            if (strtotime($end_date) >= strtotime($date))
                            {
                                // add the caseload change
                                $addChange = mysqli_prepare($conn, "INSERT INTO case_changes (case_id, start_date, frequency, uos, iep_meeting, changed_by) VALUES (?, ?, ?, ?, ?, ?)");
                                mysqli_stmt_bind_param($addChange, "issdii", $case_id, $date, $frequency, $units, $iep_meeting, $_SESSION["id"]);
                                if (mysqli_stmt_execute($addChange)) 
                                { 
                                    // get the newly created case change ID
                                    $case_change_id = mysqli_insert_id($conn);

                                    // log case change addition
                                    echo "<span class=\"log-success\">Successfully</span> added a change to the student in the caseload.<br>"; 
                                    $message = "Successfully added a change in the case with case ID $case_id. The case change has been assigned case change ID $case_change_id.";
                                    $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                                    mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                                    mysqli_stmt_execute($log);
                                }
                                else { echo "<span class=\"log-fail\">Failed</span> to add a change to the student in the caseload. An unexpected error has occurred! Please try again later.<br>"; }
                            }
                            else { echo "<span class=\"log-fail\">Failed</span> to add a change to the student in the caseload. The change date cannot be after the end date!<br>"; }
                        }
                        else { echo "<span class=\"log-fail\">Failed</span> to add a change to the student in the caseload. The change date cannot be before the start date!<br>"; }
                    }
                    else { echo "<span class=\"log-fail\">Failed</span> to add a change to the student in the caseload. An unexpected error has occurred! Please try again later.<br>"; }
                }
                else { echo "<span class=\"log-fail\">Failed</span> to add a change to the student in the caseload. An unexpected error has occurred! Please try again later.<br>"; }
            }
            else { echo "<span class=\"log-fail\">Failed</span> to add a change to the student in the caseload. The caseload selected does not exist!<br>"; }
        }

        // disconnect from the database
        mysqli_close($conn);
    }
?>